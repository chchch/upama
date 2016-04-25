<?php
ini_set('display_errors','On');
ini_set('error_reporting', E_ALL);
require_once("DiffMatchPatch/DiffMatchPatch.php");
require_once("DiffMatchPatch/Diff.php");
require_once("DiffMatchPatch/DiffToolkit.php");
require_once("DiffMatchPatch/Match.php");
require_once("DiffMatchPatch/Patch.php");
require_once("DiffMatchPatch/PatchObject.php");
require_once("DiffMatchPatch/Utils.php");

use DiffMatchPatch\DiffMatchPatch;

class Upama
{
    const HIDE = 1;
    const IGNORE = -1;
    const IGNORETAG = -2;
    const SHOW = 0;

    protected $tagFILTERS = array();
    protected $origHideFILTERS = array();
    protected $hideFILTERS = array();
    protected $origSubFILTERS = array();
    protected $subFILTERS = array();
    protected $whitespaceFILTERS = array(
                    "ltrim" => array('^\s+', "replace_with" => ''),
                    "rtrim" => array('\s+$', "replace_with" => ''),
                    "middle" => array('\s\s+|[\n\t\f]', "replace_with" => ' '),
                                    );
  
    protected $unicodeReplacements = array();
    
    protected $blockLevelNames = array();
    protected $blockLevelElements = '';

    protected $affixlemmata = 15;

    function __construct() {
        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');

        // load filters from config files
        foreach(include('tagfilters.php') as $k => $v) $this->tagFILTERS[$k] = $v;
        foreach(include('hidefilters.php') as $k => $v) $this->origHideFILTERS[$k] = $v;
        foreach(include('subfilters.php') as $k => $v) $this->origSubFILTERS[$k] = $v;
        
    }

    public function compare($file1,$file2) {
        
        $this->implodeSubFilters();
        $this->optimizeHideFilters();

        $ret1 = $this->loadFile($file1);
        if(is_array($ret1)) list($text1,$xpath1) = $ret1;
        else
            throw new Exception($ret1);
    
        $ret2 = $this->loadFile($file2);
        if(is_array($ret2)) list($text2,$xpath2) = $ret2;
        else 
            throw new Exception($ret2);

        $msidnode = $this->getSiglum($xpath2);
        $msid = $msidnode->nodeValue;
        if(!$msid) {
            $msid = basename($file2);
            $msidnode = $msid;
        }
        else $msidnode = $this->DOMouterXML($msidnode);
        $sourceDesc = $xpath1->query("/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc")->item(0);
        $listWit = $text1->createDocumentFragment();
        $listWit->appendXML("<listWit><witness xml:id='$msid'>$msidnode</witness></listWit>");
        $sourceDesc->appendChild($listWit);

        $elements1 = $xpath1->query("/x:TEI/x:text//*[@xml:id]");
        $elements2 = $xpath2->query("/x:TEI/x:text//*[@xml:id]");
       
        if($elements1->length == 0 || $elements2->length == 0) {
            // no xml:id's, revert to stepping through all elements
            return $this->oldcompare($text1,$xpath1,$text2,$xpath2,$msid);
    
        }

        $el2indexed = array();
        foreach($elements2 as $el2) {
            $elname = $el2->getAttribute("xml:id");
            $el2indexed[$elname] = $el2;
        }

        foreach ($elements1 as $el1) {
            $elname = $el1->getAttribute("xml:id");
            $el2 = isset($el2indexed[$elname]) ? $el2indexed[$elname] : FALSE;
            if(!$el2) {
               $newel = $text1->createElement('maintext');
             /*  if($el1->firstChild->nodeType == 3)
                    $el1->firstChild->nodeValue = ltrim($el1->firstChild->nodeValue);
               */
               while($el1->hasChildNodes()) {
                   $newel->appendChild($el1->childNodes->item(0));
               }

               $this->prefilterNode($newel);
               $el1->appendChild($newel);
               $emptyapp = $text1->createElement('listApp');
               $el1->appendChild($emptyapp);   
            }
            else {
                list($dom1text,$ignored1) = $this->filterNode($el1);
                list($dom2text,$ignored2) = $this->filterNode($el2);
                $dmp = new DiffMatchPatch();
                $diffs = $dmp->diff_main($dom1text,$dom2text,false);
                $diffstring = $this->diffToXml($diffs,$ignored1,$ignored2,$msid);
                $frag = $text1->createDocumentFragment();
                $frag->appendXML($diffstring);
                $el1->nodeValue = '';
                $el1->appendChild($frag);
        
            }
        }
        return $text1->saveXML();
        // outputting as text fixes namespace issues

    }

    public function oldcompare($text1,$xpath1,$text2,$xpath2,$msid) {

            $this->blockLevelNames = array('text','body','group','div','div1','div2','div3','div4','div5','div6','div7','p','l','lg','head');
    
            // Xpaths need prefix
            foreach($this->blockLevelNames as $name) {
            $this->blockLevelElements .= './x:'.$name;
            if($name !== end($this->blockLevelNames))
                $this->blockLevelElements .= '|';
            }
            $return = '';
            $xpathpath = "/x:TEI/x:text";
            $elements1 = $xpath1->query($xpathpath)->item(0);
            $elements2 = $xpath2->query($xpathpath)->item(0);
                    
            $this->recurse_elements($elements1,$elements2,$xpath1,$xpath2,$msid,$return);
            
            $elements1->nodeValue = '';
            $frag = $text1->createDocumentFragment();
            $frag->appendXML($return);
            $elements1->appendChild($frag);
            return $text1->saveXML();
    }

    public function getSiglum($xpath) {
        $msidnode = $xpath->query("/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:msDesc/x:msIdentifier/x:idno[@type='siglum']")->item(0);
        return $msidnode;
    }

    public function getTitle($xpath) {
        return $xpath->query("/x:TEI/x:teiHeader/x:fileDesc/x:titleStmt/x:title")->item(0)->nodeValue;
    }
    
    public function transform($str,$xsl) {
        $xmlDoc = new DOMDocument();
        $xmlDoc->loadXML($str);
        $xslDoc = new DOMDocument();
        $xslDoc->load($xsl);
    
        $proc = new XSLTProcessor();
        $proc->importStyleSheet($xslDoc);
        return $proc->transformtoXML($xmlDoc);
    }

    public function getTagFilters() {
        return $this->tagFILTERS;
    }
    public function getHideFilters() {
        return $this->origHideFILTERS;
    }
    public function getSubFilters() {
        return $this->origSubFILTERS;
    }

    public function setFilter($type,$name,$value) {
        if($type == 'tag') {
            $settings = array("ignore" => self::IGNORE,
                              "ignore tag" => self::IGNORETAG,
                              "hide" => self::HIDE,
                              "show" => self::SHOW
                              );

            $setting = self::SHOW;
            if(is_numeric($value))
                $setting = $value;
            //elseif(array_key_exists($value,$settings))
            elseif(isset($settings[$value]))
                $setting = $settings[$value];
            else
                trigger_error("Invalid filter status for ".$name.", setting to SHOW", E_WARNING);
            $this->tagFILTERS[$name] = $setting;
        }
        elseif($type == 'hidetext') {
            $this->origHideFILTERS[$name] = $value;
            $this->optimizeHideFilters(TRUE);
        }
        elseif($type == 'subtext') {
            $this->origSubFILTERS[$name] = $value;
            $this->implodeSubFilters(TRUE);
        }
    }
    
    public function removeFilter($type,$name) {
        if($type == 'tag')
            unset($this->tagFILTERS[$name]);
        elseif($type == 'hidetext') {
            unset($this->origHideFILTERS[$name]);
            $this->optimizeHideFilters(TRUE);
        }
        elseif($type == 'subtext') {
            unset($this->origSubFILTERS[$name]);
            $this->implodeSubFilters(TRUE);
        }
    }
    
    private function implodeSubFilters($reset = FALSE) {
        if(!$reset && !empty($this->subFILTERS)) return 0;
        //$unival = 57344; // starting at range 3 as defined in unicodeReplace
        $allfilters = array_merge($this->origSubFILTERS, $this->whitespaceFILTERS);
        foreach($allfilters as $key => $value) {
            if(is_array($value)) {
                //if(array_key_exists("replace_with",$value)) {
                if(isset($value["replace_with"])) {
                    $replacechar = $value["replace_with"];
                    unset($value["replace_with"]);
                }
                else {
                    // these don't get unset; fix this?
                    $replacechar = $this->unicodeReplace(false);
                    //$unival++;
                }
                $value = implode("|",$value);
            } else {
                $replacechar = $this->unicodeReplace(false);
                //$unival++;
            }
            $this->subFILTERS[] = array('/'.$value.'/u',$replacechar);
        }
        return 1; 
    }

    private function optimizeHideFilters($reset=FALSE) {
        //if(!$reset && array_key_exists("_optimized",$this->hideFILTERS)) {
        if(!$reset && isset($this->hideFILTERS["_optimized"])) {
            return 0;
        }
        $newfilter = "";
        foreach($this->origHideFILTERS as $key => $value) {
            if(is_array($value)) {
                $newfilter .= implode("",$value);
            }
            elseif(mb_strlen($value) == 1) {
                $newfilter .= $value;
            }
            elseif(mb_strlen($value) == 2 && substr($value,0,1) == "\\") {
                $newfilter .= $value;
            }
            else $this->hideFILTERS[$key] = '/'.$value.'/u';
        }
        if($newfilter) {
                $this->hideFILTERS["_optimized"] = "/[".$newfilter."]/u";
        }
        return 1;
    }

    private function witnessList(&$ed,$wits) {
        $edlist = $ed[1]->query("/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:listWit")->item(0);
        $edfrag = $ed[0]->createDocumentFragment();
        $witstr = '';
        foreach($wits as $wit) {
            $witlist = $wit[1]->query("/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:listWit")->item(0);
            $witstr .= $this->DOMinnerXML($witlist);
        }
        $edfrag->appendXML($witstr);
        $edlist->appendChild($edfrag);
    }

    public function collate($strs) {
        
        $this->implodeSubFilters();
        $this->optimizeHideFilters();

        $witnesses = array();
        foreach($strs as $str) {
            $witnesses[] = $this->loadText($str);
        }
        
        $edition = $witnesses[0];
        $this->witnessList($edition,array_slice($witnesses,1));

        $mainapparati = $edition[1]->query("//x:listApp");
        $apparati = array();
        foreach($witnesses as $witness) {
            $apparati[] = $witness[1]->query("//x:listApp");
        }

        $length = $mainapparati->length;
        for($n=0;$n < $length;$n++) {
            $collated = array();
            $parentnode = $mainapparati->item($n);
            foreach($witnesses as $m => $witness) {
                $apparatus = $apparati[$m]->item($n);
                //$variants = $witness[1]->query("*",$apparatus);
                //$variants = $apparatus->getElementsByTagName('variant');
                $variants = $apparatus->childNodes;
                foreach($variants as $variant) {
                    $loc = $variant->getAttribute("loc");
                    $arrkey = implode("x",
                        array_slice(explode("x",$loc),0,2)
                        );
                    $newcontent = $this->DOMinnerXML($variant->firstChild);
                    $ms = $variant->getAttribute("mss");
                    //if(!array_key_exists($arrkey,$collated)) {
                    if(!isset($collated[$arrkey])) {
                        $collated[$arrkey] = 
                            array( 
                                array( 'mss' => array($ms),
                                       'location' => $loc,
                                       'content' => $newcontent
                                )   
                            );
                    }
                    else {
                        $done = FALSE;
                        foreach ($collated[$arrkey] as &$entry) {
                            $oldcontent = $entry['content'];
                            $oldms = $entry['mss'][0];
                            $compared = $this->compareVariants($oldcontent,$newcontent);
                            if($compared !== 0) {
                                $entry['mss'][] = $ms;
                                if(!isset($entry['readings'])) {
                                    $entry['readings'] = array();
                                    $entry['readings'][$oldms] = $oldcontent;
                                }
                                $entry['readings'][$ms] = $newcontent;
                                if(is_string($compared))
                                    $entry['content'] = $compared;
                                $done = TRUE;
                                break;
                            }
                        }
                        unset($entry);
                        if(!$done)
                            $collated[$arrkey][] = 
                                array( 'mss' => array($ms), 
                                       'location' => $loc,
                                       'content' => $newcontent
                                    );
                    }
                }
            }

            uksort($collated, function($i1,$i2) {
                if($i1 == $i2) return 0; // this shouldn't be needed
                else {
                    $n1 = explode("x",$i1);
                    $n2 = explode("x",$i2);
                    if($n1[0] < $n2[0]) return -1;
                    if($n1[0] > $n2[0]) return 1;
                    return $n1[1] < $n2[1] ? -1 : 1;
                }
            });
            $parentnode->nodeValue = '';
            $fragment = $edition[0]->createDocumentFragment();
            foreach($collated as $entries) {
                $newstr = '';
                if(count($entries) > 1) {    
                    $newstr .= '<rdgGrp>';
                    
                    // sort grouped variants
                    usort($entries, function($i1,$i2) {
                        if($i1 == $i2) return 0;
                        $i1 = array_slice(explode("x",$i1['location']),2);
                        $i2 = array_slice(explode("x",$i2['location']),2);
                        
                        // put full-length variants between prefixed and suffixed ones
                        if(empty($i1))
                            return (!isset($i2[1])) ? -1 : 1;
                        if(empty($i2))
                            return (!isset($i1[1])) ? 1 : -1;

                        if($i1[0] < $i2[0]) return -1;
                        if($i1[0] > $i2[0]) return 1;
                        if(!isset($i2[1])) return -1;
                        if(!isset($i1[1])) return 1;
                        return ($i1[1] < $i2[1]) ? -1 : 1;
                    });
                }

                foreach($entries as $entry) {
                    $readings = '';
                    $allmss = implode(" ",$entry['mss']);
                    $nonmain = [];
                    $main = [];
                    if(isset($entry['readings'])) {
                        foreach($entry['readings'] as $ms => $reading) {
                            if($reading != $entry['content']) {
                                $readings .= '<rdg wit="'.$ms.'">'.$reading.'</rdg>'; 
                                $nonmain[] = $ms;
                            }
                        }
                    }
                    $main = implode(" ",array_diff($entry['mss'],$nonmain));
                    $newstr .= "<app loc='".$entry['location'].
                    "' mss='".$allmss."'><rdg wit='$main' type='main'>".$entry['content']."</rdg>".$readings."</app>";
                }
                if(count($entries) > 1) $newstr .= '</rdgGrp>';
                $newstr .= ' ';
                $fragment->appendXML($newstr);
            }
            if($fragment->hasChildNodes())
                $parentnode->appendChild($fragment);
        }
        return $edition[0]->saveXML($edition[0]);
    }
    
    private function compareVariants($str1,$str2) {
        if($str1 == $str2) return 1;
        else {
            $cleanstr = "";
            list($str1,$cleanstr) = $this->filterVariant($str1);
            $str2 = $this->filterVariant($str2)[0];
            if($str1 == $str2) {
               // normalize whitespace characters
                $cleanstr = trim($cleanstr);
                $cleanstr = preg_replace("/\s\s+/u"," ",$cleanstr);
                return $cleanstr;
            }
            else return 0;
        }
    }

    private function filterVariant($str) {
        foreach($this->tagFILTERS as $tag => $status) {
            if($status == self::IGNORE || $status == self::HIDE) {
                $str = preg_replace('/<'.$tag.'\b(?>"[^"]*"|\'[^\']*\'|[^\'">])*>.*?<\/'.$tag.'>/','',$str);
                $str = preg_replace('/<'.$tag.'\b(?>"[^"]*"|\'[^\']*\'|[^\'">])*\/>/','',$str);
            }
            elseif($status == self::IGNORETAG) {
                $str = preg_replace('/<\/?'.$tag.'\b(?:"[^"]*"|\'[^\']*\'|[^\'">])*?>/','',$str);
            }
        }
        $hidefilters = array();
        $subfilters = array();
        $cleanstr = "";
        foreach($this->hideFILTERS as $regex) {
            $str = preg_replace($regex,'',$str);
        }

        $cleanstr = $str;
        
        foreach($this->subFILTERS as $subfilter) {
            $str = preg_replace($subfilter[0],$subfilter[1],$str);
        }  
        return array($str,$cleanstr);
    }



    private function str_replace_limit($search,$replace,$subject,$limit) {
        return implode($replace, explode($search, $subject, $limit+1));
    }

    public function DOMinnerXML(DOMNode $element) {
        $innerXML = "";
        $children = $element->childNodes;
        foreach($children as $child)
            $innerXML .= $element->ownerDocument->saveXML($child);
        return $innerXML;
    }
   
    public function DOMouterXML(DOMNode $element) {
        return $element->ownerDocument->saveXML($element);
    }

    private function oldcheckTagFilters(DOMNode $element) {
        $XMLstring = $this->DOMouterXML($element);
        if($element->nodeType == 3) { // textNode, no tags
                return array($XMLstring);
        }
        else {

            $tagName = $element->localName;
            if(isset($this->tagFILTERS[$tagName])) {
                $status = $this->tagFILTERS[$tagName];
            }
            else
                $status = self::SHOW;

            if($status == self::IGNORE) {
                $ignoreattr = $element->ownerDocument->createAttribute('ignored');
                $ignoreattr->value = 'TRUE';
                $element->appendChild($ignoreattr);
                $XMLstring = $this->DOMouterXML($element);
                return array( array($status, $XMLstring) );
            
            }
            elseif(!$element->hasChildNodes()) { // SHOW or HIDE, empty tag
                return array( array($status,$XMLstring) );
            }
            elseif($status != self::HIDE) { // SHOW or IGNORETAG
                $opentag = "<".$element->localName . $this->DOMAttributes($element).">";
                $allels = array( array($status,$opentag) );
                foreach($element->childNodes as $ell) {
                    $others = $this->oldcheckTagFilters($ell);
                    $allels = array_merge($allels,$others);
                }
                $closetag = "</".$element->localName.">";
                $allels[] = array($status, $closetag);
                return $allels;
            } 
            else { // HIDE on a non-empty tag
                return array( array( $status, $XMLstring ) );
            }
        }
    } 

    private function filterTextLoop($text, $filters) {
        $results = array();
        
        if(sizeof($filters) == 1) {
            if(is_array(current($filters)))
                list($regex, $subchar) = current($filters);
            else
                list($regex, $subchar) = [current($filters), ''];

            $matches = array();
            
            preg_match_all($regex,$text,$matches,PREG_OFFSET_CAPTURE);
           
            $n = sizeof($matches[0]) -1;
            while ($n >= 0) {
                $match = $matches[0][$n];
                $matchlen = strlen($match[0]);
                $results[$match[1]] = array($match[0],$matchlen,$subchar);
                $text = substr_replace($text,$subchar,$match[1],$matchlen);
                $n--;
            }

            ksort($results);
        
            return [$text, $results];
        }

        // else if there is more than one regex filter:

        foreach ($filters as $item) {
            if(is_array($item))
                list($regex, $subchar) = $item;
            else list($regex, $subchar) = [$item, ''];

            $matches = array();
            
            preg_match_all($regex,$text,$matches,PREG_OFFSET_CAPTURE);
            
            foreach($matches[0] as $n => $match) {
                $matchlen = strlen($match[0]);
                if(isset($matches[1])) { // there is a backreference
                    $backref = $matches[1][$n][0];
                    $newsubchar = str_replace('\1',$backref,$subchar);
                    $results[$match[1]] = array($match[0],$matchlen,$newsubchar);
                }
                else
                    $results[$match[1]] = array($match[0],$matchlen,$subchar);
                $text = substr_replace($text,str_repeat("*",$matchlen),$match[1],$matchlen);
            }
        }

        ksort($results);

        foreach(array_reverse($results,true) as $pos => $el) {
            $text = substr_replace($text,$el[2],$pos,$el[1]);
        }

        return [$text,$results];
    }

    private function filterText($text) {
        $hidePos = array();
        $subPos = array();
        
        list($text,$hidePos) = $this->filterTextLoop($text,$this->hideFILTERS);
        list($text,$subPos) = $this->filterTextLoop($text,$this->subFILTERS);

        $ignoredText = array($hidePos,$subPos);
        
        return [$text,$ignoredText];
    }
  
    private function ignoreTag(&$startpos,$ignorestr,&$ignored) {
        $ignoreIndex = $startpos;
        $ignorelen = strlen($ignorestr);
        $ignored[$ignoreIndex] = array($ignorestr,$ignorelen,'');
        $startpos += $ignorelen;

    }
    
    private function prefilterNode(DOMNode $node) {
        
        $children = $node->childNodes;
        $hidelist = array();

        if(!$children) return;

        foreach($children as $child) {
            
            if($child->nodeType == 3)
                continue;

            $tagName = $child->localName;
            if(isset($this->tagFILTERS[$tagName])) {
                $status = $this->tagFILTERS[$tagName];
            }
            else $status = self::SHOW;

            if($status == self::HIDE) {
                $hidelist[] = $child;
            }
            elseif($status == self::IGNORE) {
                $ignoreattr = $child->ownerDocument->createAttribute('ignored');
                $ignoreattr->value = 'TRUE';
                $child->appendChild($ignoreattr);
                if($child->hasChildNodes()) $this->checkHideTags($child);
                //$this->prefilterNode($child);
            }
            elseif($status == self::SHOW) {
                $showattr = $child->ownerDocument->createAttribute('upama-show');
                $showattr->value = 'TRUE';
                $child->appendChild($showattr);
                $this->prefilterNode($child);
            }
            elseif($status == self::IGNORETAG) {
                $this->prefilterNode($child);
            }
        }
        foreach($hidelist as $el) $el->parentNode->removeChild($el);
    }

    private function filterNode(DOMNode $node) {
        
        $xmlStr = '';
        $subarray = array();
        $ignoredTags = array();
        $ignoredText = array();

        list($xmlStr,$ignoredTags,$subarray) = $this->checkTagFilters($node);

        list($xmlStr,$ignoredText) = $this->filterText($xmlStr);
        
        $ignored = array( "tags" => $ignoredTags,
                          "text" => $ignoredText,
                          "subs" => $subarray,
                          );
        return array($xmlStr,$ignored);

    }
    
    private function checkTagFilters(DOMNode $node,&$startpos = 0,$ignoredTags = array(),$subarray = array()) {
    
        $returnstr = '';
        $hidelist = array();

        $children = $node->childNodes;

        foreach($children as $child) {
            
            if($child->nodeType == 3) { // text node
                $returnstr .= $child->nodeValue;
                $startpos += strlen($child->nodeValue);
                continue;
            }

            $tagName = $child->localName;
            if(isset($this->tagFILTERS[$tagName])) {
                $status = $this->tagFILTERS[$tagName];
            }
            else $status = self::SHOW;

            if($status == self::HIDE) {
                $hidelist[] = $child;
            }

            elseif($status == self::IGNORE) {
                $ignoreattr = $child->ownerDocument->createAttribute('ignored');
                $ignoreattr->value = 'TRUE';
                $child->appendChild($ignoreattr);
                if($child->hasChildNodes()) {
                    $this->checkHideTags($child);
                }
                $this->ignoreTag($startpos,$this->DOMouterXML($child),$ignoredTags);
            }

            elseif($status == self::IGNORETAG) {
                
                if(!$child->hasChildNodes()) {
                    $this->ignoreTag($startpos,$this->DOMouterXML($child),$ignoredTags);
                    continue;
                }

                $opentag = "<".$child->localName . $this->DOMAttributes($child).">";
                $this->ignoreTag($startpos,$opentag,$ignoredTags);

                list($middlestr,$ignoredTags,$subarray) = $this->checkTagFilters($child,$startpos,$ignoredTags,$subarray);

                $closetag = "</".$child->localName.">";
                $this->ignoreTag($startpos,$closetag,$ignoredTags);

                $returnstr .= $middlestr;

            }
            elseif($status == self::SHOW) {

               if(!$child->hasChildNodes()) {
                    $showattr = $child->ownerDocument->createAttribute('upama-show');
                    $showattr->value = 'TRUE';
                    $child->appendChild($showattr);
                    $replacestr = $this->DOMouterXML($child);
                    $subchar = $this->unicodeReplace($replacestr);
                    $subarray[$subchar] = $replacestr;
                    $returnstr .= $subchar;
                    $startpos += strlen($subchar);
                    continue;
                }
                           
                $opentag = "<".$child->localName . $this->DOMAttributes($child).">";
                $subchar = $this->unicodeReplace($opentag);
                $subarray[$subchar] = $opentag;
                $returnstr .= $subchar;
                $startpos += strlen($subchar);

                list($middlestr,$ignoredTags,$subarray) = $this->checkTagFilters($child,$startpos,$ignoredTags,$subarray);
                $returnstr .= $middlestr;
                
                $closetag = "</".$child->localName.">";
                $subchar = $this->unicodeReplace($closetag);
                $subarray[$subchar] = $closetag;
                $returnstr .= $subchar;
                $startpos += strlen($subchar);
            }
        }
        foreach($hidelist as $hideel) $hideel->parentNode->removeChild($hideel);
        return [$returnstr,$ignoredTags,$subarray];

    }    
  
    private function checkHideTags(DOMNode $node) {
    
        $children = $node->childNodes;
        $hidelist = array();

        foreach($children as $child) {

            if($child->nodeType == 3) { // text node
                continue;
            }

            $tagName = $child->localName;
            
            $status = self::SHOW;
            if(isset($this->tagFILTERS[$tagName])) {
                $status = $this->tagFILTERS[$tagName];
            }

            if($status == self::HIDE)
                $hidelist[] = $child;
            
            else
                if($child->hasChildNodes()) $this->checkHideTags($child);
        }
        foreach($hidelist as $el) $el->parentNode->removeChild($el);
    }

    private function oldfilterNode(DOMNode $element) {
        $finalXML = "";
        $subarray = array();
        $ignoredTags = array();
        $ignoredText = array();
        
        $children = $element->childNodes;

        // Trims any space at the beginning and end of the block
/*        if($children->length != 0) {
            if($element->firstChild->nodeType == 3)
                $element->firstChild->nodeValue = ltrim($element->firstChild->nodeValue);
            if($element->lastChild->nodeType == 3)
                $element->lastChild->nodeValue = rtrim($element->lastChild->nodeValue);
        } */
        $startpos = 0;
        foreach($children as $child) { // check each node, whether tag or text, within the element
            $filter = $this->oldcheckTagFilters($child);
            foreach($filter as $el) {
                if(is_string($el)) {
                    $finalXML .= $el;
                    $startpos += strlen($el);
                }
                elseif($el[0] == self::IGNORETAG || $el[0] == self::IGNORE) {
                    // in the case of IGNORETAG, $el[1] is just the tag; but in the case if IGNORE, $el[1] is the outerXML including tags and content
                    $this->ignoreTag($startpos,$el[1],$ignoredTags);
                }
                elseif($el[0] == self::SHOW) {
                    $subchar = $this->unicodeReplace($el[1]);
                    $subarray[$subchar] = $el[1];
                    $finalXML .= $subchar;
                    $startpos += strlen($subchar);
                }
                        // else hide
            }
        } // end foreach
        
        list($finalXML,$ignoredText) = $this->filterText($finalXML);
        $ignored = array( "tags" => $ignoredTags,
                          "text" => $ignoredText,
                          "subs" => $subarray,
                          );
        return array($finalXML,$ignored);
    }

    public function unicodeChar($code) {
        $key = json_decode('"\u'.dechex($code).'"');
        $key = iconv('UTF-8', mb_internal_encoding(), $key);
        return $key;
    }

    private function unicodeReplace($original) {
        //$startval = 57344 + (800*$range);
        //$endval = 57344 + (800 + 800*$range); 
        // 57344 - 63743 is the Unicode Private Use Area; this is split into 4 ranges of 800 characters each
        
        $startval = 57344;
        $endval = 63743;
        $code = false;

        //$code = sizeof($subarray) + $startval; 
        $testval = $startval;
        while(!$code) {
            if(isset($this->unicodeReplacements[$testval])) {
                $testval++;
            }
            else {
                $code = $testval;
            }
        }
        
        //$code = count($this->unicodeReplacements) + $startval;
        
        if($code > $endval)
            trigger_error("Too many replacements");

        $key = $this->unicodeChar($code);
        
        $this->unicodeReplacements[$key] = $original;
        
        return $key;
    }
    
    private function restoreSubs($text, $subs) {
        $keys = array_keys($subs);
        foreach($keys as $key) {
            unset($this->unicodeReplacements[$key]);
        }
        return str_replace($keys, array_values($subs), $text);
    }

    public function DOMAttributes(DOMNode $element) {
        if($element->hasAttributes()) {
            $retstr = "";
            foreach($element->attributes as $attr) {
                $retstr .= " ".$attr->name ."=\"".$attr->value."\"";
            }
            return $retstr;
        }
        else return "";
    }

    public function loadText($str,$filename = '') {
        $text = new DomDocument();
        libxml_use_internal_errors(true);
        $text->loadXML($str);
        $errors = libxml_get_errors();
        if(!empty($errors)) {
            $errlist = '';
            foreach($errors as $error) {
                $errlist .= "<b>Error:</b> $error->message".
                " on line $error->line";
                if($filename)
                    $errlist .= " in $filename";
                $errlist .= "\n<br/>\n";
            }
            libxml_clear_errors();
            return $errlist;
        }
        $xpath = new DomXpath($text);
        $rootNS = $text->lookupNamespaceUri($text->namespaceURI);
        $xpath->registerNamespace("x", $rootNS);
        return array($text,$xpath);
    }
    public function loadFile($filename) {
        $text = file_get_contents($filename);
        return $this->loadText($text,$filename);
    }

    public function fixSpecialChars($data) {
        $text = str_replace(array(
        '&', '<', '>', '"', "'",
        ), array(
            '&amp;', '&lt;', '&gt;', '&quot;', '&#39;',
        ), $data);
        return $text;
    }

    private function replaceIgnored(&$count,$text,&$posArray,$atlast = false) {
        if(empty($posArray)) return $text;

        $startpos = $count;
        $count += strlen($text); // + 1 if missing a space after split
        if($atlast) $count++; // there might be ignored bits at the end

        $pos = key($posArray);
        while(($startpos <= $pos) && ($pos < $count)) {
            $ins = current($posArray);
            if($ins[2] == '')
                $inslength = 0;
            else
                $inslength = strlen($ins[2]);
             
            $text = substr_replace($text,$ins[0],$pos-$startpos,$inslength);
            $count += $ins[1] - $inslength;
            
            if(next($posArray) === FALSE) { 
                // if there are no more replacements
                $posArray = array();
                break;
            }
            else {
                $pos = key($posArray);
            }
        }

        return $text;
    }

    private function closeTags($str) {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml version="1.0" encoding="UTF-8"><xml_tags>'
        .$str.
        '</xml_tags>');
//        if(!empty(libxml_get_errors()) ) {
            libxml_clear_errors();
            return $this->DOMinnerXML($doc->getElementsByTagName('xml_tags')->item(0));
//        }
//        else {
//            return $str;
 //       }
    }
    
    private function splitDiffs(array $diffs) {
        
        $start = '';
        $sections = array();
        $possibleOmission = false;
        $lastdiff = count($diffs) - 1;
       
        foreach ($diffs as $key => $change) {
            $op = $change[0];
            $text = $change[1];

            if ($op == 1) { // text that is in text2 only
              $start .= '<i>' . $text . '</i>';
              $possibleOmission = false;

            } elseif ($op == -1) { // text that is in text1 only
               if($start == '' && $text[strlen($text)-1] == ' ') {
                    // splits the deleted text so it shows up as [om.]
                    $sections[] = '<d>' . $text . '</d>';
                    $start = '';
               }
               else {
                   if($start != '' && $text[0] == ' ') {
                        // if the next block starts with a space, split the deleted text so it shows up as [om.]
                        $possibleOmission = array($start,$text);      
                   }

                   $start .= '<d>' . $text . '</d>';
               }
            } else { // text common to both
                
                if($text == ' ') { // text is a single space
                    $sections[] = $start . ' ';
                    $start = '';
                    continue;
                }

                $t1 = $text[0];
                $t2 = $text[strlen($text)-1];
                $texts = explode(' ',$text);
                
                if($t1 != ' ' && $t2 != ' ') { // no spaces on either side
                    if(count($texts) == 1) {
                        $start .= $text;
                    }
                    elseif($key == 0) {
                        $start = array_pop($texts);
                        $sections[] = implode(' ',$texts) . ' ';
                    }
                    else {
                        $start .= array_shift($texts) . ' ';
                        $sections[] = $start;

                        if($key == $lastdiff) {
                            $sections[] = implode(' ',$texts);
                            $start = '';
                        }
                        else {
                            $last = array_pop($texts);
                            if(!empty($texts))
                                $sections[] = implode(' ',$texts) . ' ';
                            $start = $last;
                        }
                    }
                }

                elseif($t1 == ' ' && $t2 == ' ') { // spaces both sides
                    if($possibleOmission) {
                        $sections[] = $possibleOmission[0]."<d> </d>";
                        $start = "<d>".ltrim($possibleOmission[1])."</d>";
                        //$possibleOmission = false;
                    }
                    $sections[] = $start . ' ';
                    $sections[] = ltrim($text);
                    $start = '';
                }

                elseif($t1 == ' ' && $t2 != ' ') { // space at start
                    if($possibleOmission) {
                        $sections[] = $possibleOmission[0]."<d> </d>";
                        $start = "<d>".ltrim($possibleOmission[1])."</d>";
                        //$possibleOmission = false;
                    }
                    $sections[] = $start . ' ';
                    array_shift($texts);
                    if($key == $lastdiff) {
                        $sections[] = implode(' ',$texts);
                        $start = '';
                    }
                    else {
                        $last = array_pop($texts);
                        if(!empty($texts))
                            $sections[] = implode(' ',$texts) . ' ';
                        $start = $last;
                    }
                }

                elseif($t1 != ' ' && $t2 == ' ') { // space at end
                    if($key == 0) { // first element
                        $sections[] = $text;
                    }
                    else {
                        array_pop($texts);
                        $sections[] = $start . array_shift($texts) . ' ';
                        if(!empty($texts)) 
                            $sections[] = implode(' ',$texts) . ' ';
                        $start = '';
                    }
                }

                $possibleOmission = false;
            }
            // add leftover text to the array
            if($key == $lastdiff && $start != '') $sections[] = $start;       
        }
        return $sections;
    }

    private function diffToXml($diffs,$ignored1,$ignored2,$msid)   {
        $xmlstring = '';
        $counters1 = array(
                           "text" => array(0,0),
                           "tags" => 0,
                           "startspace" => 0,
                           "endspace" => 0,
                           );
        $counters2 = array(
                           "text" => array(0,0),
                           "tags" => 0,
                           );
        $postcount = false;
        $precount = false;

        $spaceSplit = $this->splitDiffs($diffs);
        $lastsection = count($spaceSplit) - 1;

        $finalXml = "<maintext>";
        $apparatus = "<listApp>";
        $atlast = false;

        foreach ($spaceSplit as $key => $section) {
           $charpos = '';
           $startspace = -1;
           if($key == $lastsection) $atlast = true;

            if(preg_match('/<[id]>/',$section)) {
                
                $maintext = preg_replace("/<(?:i>.+?<\/i|\/{0,1}d)>/",'',$section);
                $vartext = preg_replace("/<(?:d>.+?<\/d|\/{0,1}i)>/",'',$section);
                if(trim($maintext) == trim($vartext)) {
                    // this covers sections like dvayasiddhau<d> </d>
                    $maintext = $this->unfilterText($maintext,$counters1,$ignored1,$atlast);
                    
                    $finalXml .= $maintext;

                    if(!$atlast) {
                        $this->unfilterText($vartext,$counters2,$ignored2);
                    }
                    continue;
                }
                
                $vartexts = false;
                if($this->affixlemmata && strlen($vartext) > $this->affixlemmata) {
                    $vartexts = $this->mb_findAffixes($maintext,$vartext);
                }
                if(isset($vartexts["prefix"])) {
                    $precount = "ltrim";
                    $main1 = $this->unfilterText($vartexts["prefix"],$counters1,$ignored1,false,$precount);
                    $startspace = $counters1["startspace"];
                    $main2 = $this->unfilterText($vartexts["main"],$counters1,$ignored1,$atlast);
                    $maintext = $main1.$main2;
                    $charpos = "x".$precount;
                }
                elseif(isset($vartexts["suffix"])) {
                    $postcount = "ltrim";
                    $main1 = $this->unfilterText($vartexts["main"],$counters1,$ignored1,false,$postcount);
                    $startspace = $counters1["startspace"];
                    $main2 = $this->unfilterText($vartexts["suffix"],$counters1,$ignored1,$atlast);
                    $maintext = $main1.$main2;
                    $charpos = "x0x".$postcount;
                }
                else {
                    $maintext = $this->unfilterText($maintext,$counters1,$ignored1,$atlast);
                }
                
                $finalXml .= $maintext;

                // now deal with variant text
                
                if(trim($vartext) == '') {
                    $this->unfilterText($vartext,$counters2,$ignored2,$atlast);
                    $vartext = "<editor>[om.]</editor>";
                }
                else {
                    if($vartexts) {
                        if(isset($vartexts["prefix"])) {
                            $prefix = $vartexts["prefix"];           
                            if($prefix[strlen($prefix)-1] == ' ') {
                            // this shouldn't happen, but the diff algorithm isn't perfect
                                $startspace++;
                                $charpos = '';
                            }

                            $prefix = $this->unfilterText($vartexts["prefix"],$counters2,$ignored2);
                            $vartext = $this->unfilterText($vartexts["var"],$counters2,$ignored2,$atlast);
                            if(trim($vartext) == '') 
                                $vartext = "<editor>[om.]</editor>";
                            else {
                                //$vartext = "$prefix  *$vartext";
                                $vartext = "°".$vartext;
                                $vartext = trim($vartext);
                                $vartext = $this->closeTags($vartext);
                            }
                        }
                        else {
                            $vartext = $this->unfilterText($vartexts["var"],$counters2,$ignored2);
                            $suffix = $this->unfilterText($vartexts["suffix"],$counters2,$ignored2,$atlast);
                            if(trim($vartext) == '') 
                                $vartext = "<editor>[om.]</editor>";
                            else {
                                //$vartext = "$vartext*  $suffix";
                                $vartext = $vartext."°";
                                $vartext = trim($vartext);
                                $vartext = $this->closeTags($vartext);
                            }
                        }
                    }
                    else {
                        $vartext = $this->unfilterText($vartext,$counters2,$ignored2,$atlast);
                        $vartext = trim($vartext);
                        $vartext = $this->closeTags($vartext);
                    }
                }  
                if($startspace < 0) {
                    $startspace = $counters1["startspace"];
                }

                $apparatus .= "<app loc='".
                $startspace."x$counters1[endspace]$charpos' mss='#$msid'>".
                "<rdg wit='#$msid' type='main'>$vartext</rdg>".
                "</app>";

            } // end if(preg_match('/<[id]>/',$section))

            else {
                
                $maintext = $this->unfilterText($section,$counters1,$ignored1,$atlast);
                $finalXml .= $maintext;

                if(!$atlast) {
                    $this->unfilterText($section,$counters2,$ignored2);
                } // if it's the last section, no need to continue processing the apparatus

            }
        } // end foreach ($spaceSplit as $key => $section)
        
        $finalXml .= "</maintext>";
        $apparatus .= "</listApp>";
        $finalXml .= $apparatus;

        return $finalXml;
    }

    private function unfilterText($text,&$counters,&$ignored,$atlast = false,&$charcount = false) {
        $text = $this->replaceIgnored($counters["text"][1],$text,$ignored["text"][1],$atlast);
       
        $text = $this->replaceIgnored($counters["text"][0],$text,$ignored["text"][0],$atlast);
        $ctext = false;


        if($charcount) {
            if(count($ignored["subs"]) > 0) // remove any tags
                $ctext = str_replace(array_keys($ignored["subs"]),'', $text);
            else $ctext = $text;
            
            if($charcount == 'ltrim')
                $charcount = mb_strlen(ltrim($ctext));
            else
                $charcount = mb_strlen($ctext);
        }

        if(isset($counters["startspace"])) {
            
            $counters["startspace"] = $counters["endspace"];
            $counters["endspace"] += preg_match_all('/\s+/',$text);
                    if($atlast && !preg_match('/\s/',$text[strlen($text)-1]) )
                        $counters["endspace"]++;
        }

        $text = $this->replaceIgnored($counters["tags"],$text,$ignored["tags"],$atlast);
        $text = $this->restoreSubs($text,$ignored["subs"]);

        return $text;
    }
    
    private function mb_findAffixes($text1,$text2) {
        
        $arr1 = preg_split('//u',$text1,-1,PREG_SPLIT_NO_EMPTY);
        $arr2 = preg_split('//u',$text2,-1,PREG_SPLIT_NO_EMPTY);
        $len1 = count($arr1);
        $len2 = count($arr2);
        $pre_end = 0;
        $post_start = 0;

        foreach($arr1 as $r => $pre1) {
            $pre_end = $r;
            $pre2 = isset($arr2[$r]) ? $arr2[$r] : false;
            if($pre1 != $pre2) {
                break;
            }
        }
        $vowels = '/[aāiīeuūoṛṝḷṃḥ\s]/u';
        $consonants = '/[kgcjṭḍtdpbṅñṇnmyrlḻvśṣsh]/u';
        // first half should end in vowel, second half should start with consonant
        if($len1 - $pre_end == 0) $pre_end--;
        elseif($len1 - $pre_end == 1 && $arr1[$pre_end] == ' ') 
            $pre_end--;

        while(isset($arr2[$pre_end-1]) && $arr2[$pre_end-1] != ' ') {
            if(!preg_match($vowels,$arr2[$pre_end-1]))
                $pre_end--;
            elseif(isset($arr2[$pre_end]) && $arr2[$pre_end] != ' ' &&
                   !preg_match($consonants,$arr2[$pre_end]))
                $pre_end--;
            else break;
        }
        while(isset($arr1[$pre_end-1]) && $arr2[$pre_end-1] != ' ') {
            if(!preg_match($vowels,$arr1[$pre_end-1]))
                $pre_end--;
            elseif(isset($arr1[$pre_end]) && $arr1[$pre_end] != ' ' &&
               !preg_match($consonants,$arr1[$pre_end]))
                $pre_end--;
            else break;
        }

        //if($pre_end == $len2-1) return false;
        
        if($pre_end < $len2/2 - 1) { // prefix is less than half the length of the variant text; check if suffix is longer

            $rev1 = array_reverse($arr1);
            $rev2 = array_reverse($arr2);
            foreach($rev1 as $o => $post1) {
                $post_start = $o;

                $post2 = isset($rev2[$o]) ? $rev2[$o] : false;
                if($post1 != $post2) {
                    break;
                }
            }
            if($len1 - $post_start == 0) $post_start--;

            if(isset($rev2[$post_start]) && $rev2[$post_start] != ' ') {
                while(isset($rev2[$post_start-1])) {
                    if(!preg_match($vowels,$rev2[$post_start]) ||
                       !preg_match($consonants,$rev2[$post_start-1]))
                        $post_start--;
                    else break;
                }
            }
            if(isset($rev1[$post_start]) && $rev1[$post_start] != ' ') {
                while(isset($rev1[$post_start-1])) {
                    if(!preg_match($vowels,$rev1[$post_start]) ||
                       !preg_match($consonants,$rev1[$post_start-1]))
                        $post_start--;
                    else break;
                }
            }
            //if($post_start == $len2) return false;
        } 
        if($post_start > 5 && $post_start >= $pre_end) {
            $var = implode('',array_slice($arr2,0,$len2-$post_start));
            $suffix = implode('',array_slice($arr2,$len2-$post_start));
            $main = implode('',array_slice($arr1,0,$len1-$post_start));

            return array("var" => $var,
                         "suffix" => $suffix,
                         "main" => $main,
                         );
        } 
        if($pre_end > 5) {
            $prefix = implode('',array_slice($arr2,0,$pre_end));
            $var = implode('',array_slice($arr2,$pre_end));
            $main = implode('',array_slice($arr1,$pre_end));
            
            return array("var" => $var,
                         "prefix" => $prefix,
                         "main" => $main,
                         );
        } 
        else return false;
        
    }  
    
    private function findAffixes($text1,$text2) {
        $n = 0;
        $char1 = $text1[$n];
        $char2 = $text2[$n];
        while(!empty($char1) && !empty($char2)) {
            if($char1 == $char2) {
                $n++;
                $char1 = $text1[$n];
                $char2 = $text2[$n];
            }
            else break;
        }

        $n--; // we want the last matching character
        
        if($n > 5) {
            $n = $this->fixMultibyte($text2,$n);
            $prefix = substr($text2,0,$n+1);
            $var = substr($text2,$n+1);
            return array("prefix" => $prefix,
                         "vartext" => $var
                         );
        }
        else return false;
        
    } 
   
    private function fixMultibyte($str,$n) {
        
        $bin = decbin(ord($str[$n]));
        
        if(strlen($bin) == 7) // not multibyte
            return $n;

        if($bin[1] == 1) { // first byte of the string
            if ($bin[2] == 0) { // two-byte string
                return $n++;
            }
            elseif($bin[3] == 0) { // three-byte string
                return $n + 2;
            }
            else { // four-byte string
                return $n + 3;
            }
        }
        else { // this is byte 2, 3, or 4 of the string
            $n++;
            while(!empty($str[$n])) {

                $bin = decbin(ord($str[$n]));
                
                if(strlen($bin) == 7) // next character not multibyte
                    return $n-1;
                if($bin[1] == 1) // first byte of next character
                    return $n-1;
                
                $n++;
            }
            return $n-1;
        }
    } 
   
    private function recurse_elements($dom1,$dom2,$xpath1,$xpath2,$msid,&$return) {
        $kids1 = $xpath1->query($this->blockLevelElements,$dom1);
        $kids1length = $kids1->length;
        if($kids1length == 0) {// if the current element has no block-level children
            list($dom1text,$ignored1) = $this->filterNode($dom1);
            list($dom2text,$ignored2) = $this->filterNode($dom2);
            $dmp = new DiffMatchPatch();
            $diffs = $dmp->diff_main($dom1text, $dom2text,false);
            $diffstring = $this->diffToXml($diffs,$ignored1,$ignored2,$msid);
            return $diffstring;
        }
        else {
            $kids2 = $xpath2->query($this->blockLevelElements,$dom2);
            if($kids1length != $kids2->length) {
                trigger_error("unequal number of text blocks (".$kids1length." vs ".$kids2->length.") in ".$dom1->localName);
            }
            $dom1 = $dom1->firstChild;
            $dom2 = $kids2->item(0);
            $nn = 0;
            do {
                $dom1name = $dom1->localName;
                if(!in_array($dom1name,$this->blockLevelNames)) {
                    $return .= $this->DOMouterXML($dom1);
                }
                else {
                    $return .= "<".$dom1name . $this->DOMAttributes($dom1) .">\n";
                    $return .= $this->recurse_elements($dom1,$dom2,$xpath1,$xpath2,$msid,$return);
                    $return .= "</".$dom1name.">\n";
                    $nn++;
                    $dom2 = $kids2->item($nn);
                }
            } while($dom1 = $dom1->nextSibling);

        }

    }

}
?>
