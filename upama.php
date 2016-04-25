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
  
    protected $blockLevelNames = array('text','body','group','div','div1','div2','div3','div4','div5','div6','div7','p','l','lg','head');
    
    protected $blockLevelElements = '';

    function __construct() {
        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');

        // load filters from config files
        foreach(include('tagfilters.php') as $k => $v) $this->tagFILTERS[$k] = $v;
        foreach(include('hidefilters.php') as $k => $v) $this->origHideFILTERS[$k] = $v;
        foreach(include('subfilters.php') as $k => $v) $this->origSubFILTERS[$k] = $v;
        
        // Xpaths need prefix
        foreach($this->blockLevelNames as $name) {
            $this->blockLevelElements .= './x:'.$name;
            if($name !== end($this->blockLevelNames))
                $this->blockLevelElements .= '|';
        }
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

            $return = '';
            $xpathpath = "/x:TEI/x:text";
            $elements1 = $xpath1->query($xpathpath)->item(0);
            $elements2 = $xpath2->query($xpathpath)->item(0);
                    
            $this->recurse_elements($elements1,$elements2,$xpath1,$xpath2,$msid,$return);
            
            return $return;
    
        }

        $el2indexed = array();
        foreach($elements2 as $el2) {
            $elname = $el2->getAttribute("xml:id");
            $el2indexed[$elname] = $el2;
        }

        foreach ($elements1 as $el1) {
            $elname = $el1->getAttribute("xml:id");
            //$el2 = $xpath2->query("/x:TEI/x:text//*[@xml:id='".$elname."']")->item(0);
            $el2 = isset($el2indexed[$elname]) ? $el2indexed[$elname] : FALSE;
            if(!$el2) {
               $newel = $text1->createElement('maintext');
             /*  if($el1->firstChild->nodeType == 3)
                    $el1->firstChild->nodeValue = ltrim($el1->firstChild->nodeValue);
               */
               while($el1->childNodes->length > 0) {
                   $newel->appendChild($el1->childNodes->item(0));
               }
               $el1->appendChild($newel);
               $emptyapp = $text1->createElement('apparatus');
               $el1->appendChild($emptyapp);   
            }
            else {
                list($dom1text,$ignored1) = $this->filterNode($el1);
                list($dom2text,$ignored2) = $this->filterNode($el2,1);
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
        $unival = 57344; // starting at range 3 as defined in unicodeReplace
        $allfilters = array_merge($this->origSubFILTERS, $this->whitespaceFILTERS);
        foreach($allfilters as $key => $value) {
            if(is_array($value)) {
                //if(array_key_exists("replace_with",$value)) {
                if(isset($value["replace_with"])) {
                    $replacechar = $value["replace_with"];
                    unset($value["replace_with"]);
                }
                else {
                    $replacechar = $this->unicodeChar($unival);
                    $unival++;
                }
                $value = implode("|",$value);
            } else {
                $replacechar = $this->unicodeChar($unival);
                $unival++;
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
            while($parentnode->hasChildNodes()) {
                $parentnode->removeChild($parentnode->firstChild);
            }
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

    private function checkTagFilters(DOMNode $element) {
        $XMLstring = $this->DOMouterXML($element);
        if($element->nodeType == 3) { // textNode, no tags
                return array($XMLstring);
        }
        else {
            $tagName = $element->nodeName;
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
                $opentag = "<".$element->nodeName . $this->DOMAttributes($element).">";
                $allels = array( array($status,$opentag) );
                foreach($element->childNodes as $ell) {
                    $others = $this->checkTagFilters($ell);
                    $allels = array_merge($allels,$others);
                }
                $closetag = "</".$element->nodeName.">";
                $allels[] = array($status, $closetag);
                return $allels;
            } 
            else {
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

    private function filterNode(DOMNode $element,$range = 0) {
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
            $filter = $this->checkTagFilters($child);
            foreach($filter as $el) {
                if(is_string($el)) {
                    $finalXML .= $el;
                    $startpos += strlen($el);
                }
                elseif($el[0] == self::IGNORETAG || $el[0] == self::IGNORE) {
                    $this->ignoreTag($startpos,$el[1],$ignoredTags);
                }
                elseif($el[0] == self::SHOW) {
                    $subchar = $this->unicodeReplace($el[1],$subarray,$range);
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

    private function unicodeReplace($original, &$subarray, $range = 0, $samelength = FALSE) {
        $startval = 57344 + (800*$range);      // 57344 - 63743 is the Unicode Private Use
        $endval = 57344 + (800 + 800*$range);  // Area; this is split into 4 ranges of 800
        $code = sizeof($subarray) + $startval; // characters each
        if($code > $endval)
            trigger_error("Too many replacements");
        $key = $this->unicodeChar($code);
        if($samelength == TRUE) {
            if(is_array($original)) 
                $length = mb_strlen($original[0]);
            else $length = mb_strlen($original);
            $key = str_repeat($key,$length);
        }
        $subarray[$key] = $original;
        return $key;
    } 
    private function restoreSubs($text, $subs) {
        return str_replace(array_keys($subs), array_values($subs), $text);
    }

    public function DOMAttributes(DOMNode $element) {
        if($element->hasAttributes()) {
            $retstr = "";
            foreach($element->attributes as $attr) {
                $retstr .= " ".$attr->nodeName ."=\"".$attr->nodeValue."\"";
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
        $text = new DomDocument();
        $text->load($filename);
        $xpath = new DomXpath($text);
        $rootNS = $text->lookupNamespaceUri($text->namespaceURI);
        $xpath->registerNamespace("x", $rootNS);
        return array($text,$xpath);
    }
    public function fixSpecialChars($data) {
        $text = str_replace(array(
        '&', '<', '>', '"', "'",
        ), array(
            '&amp;', '&lt;', '&gt;', '&quot;', '&#39;',
        ), $data);
        return $text;
    }

    private function replaceIgnored(&$count,$text,&$posArray) {
        if(empty($posArray)) return $text;

        $startpos = $count;
        $count += strlen($text); // + 1 if missing a space after split
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
        foreach ($diffs as $change) {
            $op = $change[0];
            $text = $change[1];
            $spacer = $this->unicodeChar(63743);
            if ($op == 1) { // text that is in text2 only
              $xmlstring .= '<ins>' . str_replace(" ",$spacer,$text) . '</ins>';

            } elseif ($op == -1) { // text that is in text1 only
               $xmlstring .= '<del>' . str_replace(" ",$spacer,$text) . '</del>';

            } else { // text common to both
                $xmlstring .= $text;
            }
        }

        $spaceSplit = explode(" ",$xmlstring);
        $finalXml = "<maintext>";
        $apparatus = "<apparatus>";
        foreach ($spaceSplit as $section) {
            $section .= " "; // replacing space after explode
            if(preg_match('/<(ins|del)>/',$section)) {
                $section = str_replace($spacer," ",$section);
                
                $maintext = preg_replace("/<ins>.*?<\/ins>|<\/{0,1}del>/",'',$section);
                #$maintext = mb_ereg_replace("<ins>.*?</ins>|<del>|</del>",'',$section);
                $maintext = $this->replaceIgnored($text1counta,$maintext,$ignored1["text"][1]);
                $maintext = $this->replaceIgnored($text1countb,$maintext,$ignored1["text"][0]);
                $oldspacecount = $spacecount;
                //$spacecount += substr_count($maintext,' ');
                $spacecount += preg_match_all('/\s+/',$maintext);
                $maintext = $this->replaceIgnored($tags1count,$maintext,$ignored1["tags"]);
                $maintext = $this->restoreSubs($maintext,$ignored1["subs"]);
                if($maintext == " ") $maintext = "<editor>[om.]</editor> "; // this currently never happens unless the whole block is empty
                
                $vartext = preg_replace("/<del>.*?<\/del>|<\/{0,1}ins>/",'',$section);
                #$vartext = mb_ereg_replace("<del>.*?</del>|<ins>|</ins>",'',$section);
                $vartext = $this->replaceIgnored($text2counta,$vartext,$ignored2["text"][1]);
                $vartext = $this->replaceIgnored($text2countb,$vartext,$ignored2["text"][0]);
                $vartext = $this->replaceIgnored($tags2count,$vartext,$ignored2["tags"]);
                $vartext = $this->restoreSubs($vartext,$ignored2["subs"]);
                if($vartext == " ") $vartext = "<editor>[om.]</editor>";
                $vartext = trim($vartext);
                $vartext = $this->closeTags($vartext);

                $finalXml .= $maintext;
                $apparatus .= "<variant location='".$oldspacecount."x".$spacecount."' mss='".$msid."'><mainreading>" . $vartext . "</mainreading></variant> ";
     
            }
            else {
                $section1 = $this->replaceIgnored($text1counta,$section,$ignored1["text"][1]);
                $section1 = $this->replaceIgnored($text1countb,$section1,$ignored1["text"][0]);
                //$spacecount += substr_count($section1,' ');
                $spacecount += preg_match_all('/\s+/',$section1);

                $section1 = $this->replaceIgnored($tags1count,$section1,$ignored1["tags"]);
                $section1 = $this->restoreSubs($section1,$ignored1["subs"]);
                
                $section2 = $this->replaceIgnored($text2counta,$section,$ignored2["text"][1]);
                $section2 = $this->replaceIgnored($text2countb,$section2,$ignored2["text"][0]);
                $this->replaceIgnored($tags2count,$section2,$ignored2["tags"]);
                
                $finalXml .= $section1;
            }
        }
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
            list($dom2text,$ignored2) = $this->filterNode($dom2,1);
            $dmp = new DiffMatchPatch();
            $diffs = $dmp->diff_main($dom1text, $dom2text,false);
            $diffstring = $this->prettyXml($diffs,$ignored1,$ignored2,$msid);
            return $diffstring;
        }
        else {
            $kids2 = $xpath2->query($this->blockLevelElements,$dom2);
            if($kids1length != $kids2->length) {
                trigger_error("unequal number of text blocks (".$kids1length." vs ".$kids2->length.") in ".$dom1->nodeName);
            }
            $dom1 = $dom1->firstChild;
            $dom2 = $kids2->item(0);
            $nn = 0;
            do {
                $dom1name = $dom1->nodeName;
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
