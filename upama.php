<?php
ini_set('display_errors','On');
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
                    "middle" => array('\s\s+', "replace_with" => ' '),
                                    );
  
    protected $unicodeReplacements = array();
    
    protected $blockLevelNames = array();
    protected $blockLevelElements = '';

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

        list($text1,$xpath1) = $this->loadFile($file1);
        list($text2,$xpath2) = $this->loadFile($file2);

        $msid = $this->getSiglum($xpath2);
        if(!$msid) $msid = basename($file2);

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
               $emptyapp = $text1->createElement('apparatus');
               $el1->appendChild($emptyapp);   
            }
            else {
                list($dom1text,$ignored1) = $this->filterNode($el1);
                list($dom2text,$ignored2) = $this->filterNode($el2);
                $dmp = new DiffMatchPatch();
                $diffs = $dmp->diff_main($dom1text,$dom2text,false);
                $diffstring = $this->prettyXml($diffs,$ignored1,$ignored2,$msid);
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
        $msidpath = $xpath->query("/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:msDesc/x:msIdentifier/x:idno[@type='siglum']")->item(0);
        return $msidpath->nodeValue;
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

    public function collate($strs) {
        
        $this->implodeSubFilters();
        $this->optimizeHideFilters();

        $witnesses = array();
        foreach($strs as $str) {
            $witnesses[] = $this->loadText($str);
        }
        $edition = $witnesses[0];
        
        $mainapparati = $edition[1]->query("//x:apparatus");
        $length = $mainapparati->length;
        for($n=0;$n < $length;$n++) {
            $collated = array();
            $parentnode = $mainapparati->item($n);
            foreach($witnesses as $witness) {
                $apparatus = $witness[1]->query("//x:apparatus")->item($n);
                $variants = $witness[1]->query("*",$apparatus);
                foreach($variants as $variant) {
                    $arrkey = $variant->getAttribute("location");
                    $newcontent = $this->DOMinnerXML($variant->firstChild);
                    $ms = $variant->getAttribute("mss");
                    //if(!array_key_exists($arrkey,$collated)) {
                    if(!isset($collated[$arrkey])) {
                        $collated[$arrkey] = 
                            array( 
                                array( 'mss' => array($ms), 
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
                            $collated[$arrkey][] = array( 'mss' => array($ms), 
                                                          'content' => $newcontent
                                                        );
                    }
                }
            }

            uksort($collated, function($i1,$i2) {
                if($i1 == $i2) return 0; // this shouldn't be needed
                else {
                    $n1 = (float) str_replace("x",".",$i1);
                    $n2 = (float) str_replace("x",".",$i2);
                    return $n1 < $n2 ? -1 : 1;
                }
            });
            /*while($parentnode->hasChildNodes()) {
                $parentnode->removeChild($parentnode->firstChild);
            }*/
            $parentnode->nodeValue = '';
            $fragment = $edition[0]->createDocumentFragment();
            foreach($collated as $location => $entries) {
                $newstr = '';
                if(count($entries) > 1) $newstr .= '<varGroup>';
                foreach($entries as $entry) {
                    $readings = '';
                    if(isset($entry['readings'])) {
                        foreach($entry['readings'] as $ms => $reading) {
                            if($reading != $entry['content'])
                                $readings .= '<reading ms="'.$ms.'">'.$reading.'</reading>';
                        }
                    }
                    $allmss = implode(";",$entry['mss']);
                    $newstr .= "<variant location='".$location.
                    "' mss='".$allmss."'><mainreading>".$entry['content']."</mainreading>".$readings."</variant> ";
                }
                if(count($entries) > 1) $newstr .= '</varGroup>';
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
            
            foreach($matches[0] as $match) {
                $matchlen = strlen($match[0]);
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
            elseif($status == self::SHOW || $status == self::IGNORETAG) {
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

    public function loadText($str) {
        $text = new DomDocument();
        $text->loadXML($str);
        $xpath = new DomXpath($text);
        $rootNS = $text->lookupNamespaceUri($text->namespaceURI);
        $xpath->registerNamespace("x", $rootNS);
        return array($text,$xpath);
    }
    public function loadFile($filename) {
        $text = file_get_contents($filename);
        return $this->loadText($text);
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
        if($atlast) $count++;

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
    
    private function prettyXml($diffs,$ignored1,$ignored2,$msid)   {
        $xmlstring = '';
        $text1counta = 0;
        $text1countb = 0;
        $tags1count = 0;
        $text2counta = 0;
        $text2countb = 0;
        $tags2count = 0;
        $spacecount = 0;
        $spacer = $this->unicodeReplace(' ');

        foreach ($diffs as $change) {
            $op = $change[0];
            $text = $change[1];
            //$spacer = $this->unicodeChar(57345);
            if ($op == 1) { // text that is in text2 only
              $xmlstring .= '<ins>' . str_replace(" ",$spacer,$text) . '</ins>';

            } elseif ($op == -1) { // text that is in text1 only
               $xmlstring .= '<del>' . str_replace(" ",$spacer,$text) . '</del>';

            } else { // text common to both
                $xmlstring .= $text;
            }
        }

        $oldspaceSplit = explode(" ",$xmlstring);
        $finalXml = "<maintext>";
        $apparatus = "<apparatus>";
        $atlast = false;

        $spaceSplit = array();
        $lastsection = count($oldspaceSplit) - 1;
        
        foreach($oldspaceSplit as $n => $section) {

            // if you use '.+?'.$spacer instead of '[^<]+?'.$spacer, the regex engine will keep searching past other tags until it finds $spacer.'<\/del>'
            if(preg_match('/^<del>[^<]+?'.$spacer.'<\/del>/',$section)) {
                $splits = explode('</del>',$section,2);
               
                $spaceSplit[] = $splits[0] . '</del>';
                if(count($splits[1]) > 0)
                    $spaceSplit[] = ($n == $lastsection) ? $splits[1] : $splits[1] . ' ';
            }
            elseif(preg_match('/<del>'.$spacer.'[^<]+?<\/del>$/',$section)) {
                // in this case, we need to move the space to $first from $last, or else $spacecount will be the same as $oldspacecount when processing $first

                $splits = explode('<del>'.$spacer,$section);
                $last = '<del>' . array_pop($splits);
                $first = count($splits) > 1 ? implode('<del>'.$spacer,$splits) : $splits[0];
                $spaceSplit[] = $first . '<del> </del>';
                $spaceSplit[] = ($n == $lastsection) ? $last : $last . ' ';
            } 
            else { 
                $spaceSplit[] = ($n == $lastsection) ? $section : $section . ' ';
            }
        }   

        $lastsection = count($spaceSplit) - 1;
        
        foreach ($spaceSplit as $key => $section) {

           // if($key < $lastsection)
           //     $section .= " "; // replacing space after explode
           // else $atlast = true;
           if($key == $lastsection) $atlast = true;

            if(preg_match('/<(ins|del)>/',$section)) {
                $section = str_replace($spacer," ",$section);
                
                $maintext = preg_replace("/<ins>.+?<\/ins>|<\/{0,1}del>/",'',$section);
                #$maintext = mb_ereg_replace("<ins>.*?</ins>|<del>|</del>",'',$section);
                $maintext = $this->replaceIgnored($text1counta,$maintext,$ignored1["text"][1],$atlast);
                $maintext = $this->replaceIgnored($text1countb,$maintext,$ignored1["text"][0],$atlast);
                $oldspacecount = $spacecount;
                //$spacecount += substr_count($maintext,' ');
                $spacecount += preg_match_all('/\s+/',$maintext);
                if($atlast && !preg_match('/\s/',substr($maintext,-1)) )
                    $spacecount++;
                
                $maintext = $this->replaceIgnored($tags1count,$maintext,$ignored1["tags"],$atlast);
                $maintext = $this->restoreSubs($maintext,$ignored1["subs"]);
                //if($maintext == " ") $maintext = "<editor>[om.]</editor> "; // this currently never happens unless the whole block is empty
                
                $vartext = preg_replace("/<del>.+?<\/del>|<\/{0,1}ins>/",'',$section);
                #$vartext = mb_ereg_replace("<del>.*?</del>|<ins>|</ins>",'',$section);
                $omitted = (trim($vartext) == '') ? true : false;

                $vartext = $this->replaceIgnored($text2counta,$vartext,$ignored2["text"][1],$atlast);
                $vartext = $this->replaceIgnored($text2countb,$vartext,$ignored2["text"][0],$atlast);
                $vartext = $this->replaceIgnored($tags2count,$vartext,$ignored2["tags"],$atlast);
                $vartext = $this->restoreSubs($vartext,$ignored2["subs"]);
                
                if($omitted)
                    $vartext = "<editor>[om.]</editor>";
                else {
                    $vartext = trim($vartext);
                    $vartext = $this->closeTags($vartext);
                }

                $finalXml .= $maintext;
                $apparatus .= "<variant location='".$oldspacecount."x".$spacecount."' mss='".$msid."'><mainreading>" . $vartext . "</mainreading></variant> ";
     
            }
            else {
                $section1 = $this->replaceIgnored($text1counta,$section,$ignored1["text"][1],$atlast);
                $section1 = $this->replaceIgnored($text1countb,$section1,$ignored1["text"][0],$atlast);
                //$spacecount += substr_count($section1,' ');
                $spacecount += preg_match_all('/\s+/',$section1);

                $section1 = $this->replaceIgnored($tags1count,$section1,$ignored1["tags"],$atlast);
                $section1 = $this->restoreSubs($section1,$ignored1["subs"]);
                if($key < $lastsection) {
                    $section2 = $this->replaceIgnored($text2counta,$section,$ignored2["text"][1],$atlast);
                    $section2 = $this->replaceIgnored($text2countb,$section2,$ignored2["text"][0],$atlast);
                    $this->replaceIgnored($tags2count,$section2,$ignored2["tags"]);
                }

                $finalXml .= $section1;
            }
        }
        
        unset($this->unicodeReplacements[$spacer]);

        $finalXml .= "</maintext>\n";
        $apparatus .= " </apparatus>"; // whitespace so xslt doesn't self-close the tag
        $finalXml .= $apparatus;

        return $finalXml;
    }

    private function recurse_elements($dom1,$dom2,$xpath1,$xpath2,$msid,&$return) {
        $kids1 = $xpath1->query($this->blockLevelElements,$dom1);
        $kids1length = $kids1->length;
        if($kids1length == 0) {// if the current element has no block-level children
            list($dom1text,$ignored1) = $this->filterNode($dom1);
            list($dom2text,$ignored2) = $this->filterNode($dom2);
            $dmp = new DiffMatchPatch();
            $diffs = $dmp->diff_main($dom1text, $dom2text,false);
            $diffstring = $this->prettyXml($diffs,$ignored1,$ignored2,$msid);
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
