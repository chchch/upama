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

    protected $tagFILTERS = array( "unclear" => self::IGNORETAG,
                                "gap" => self::IGNORE,
                                "pb" => self::IGNORE,
                                "lb" => self::IGNORE,
                                "note" => self::IGNORE,
                                "g" => self::IGNORETAG,
                                "subst" => self::IGNORETAG,
                                "add" => self::IGNORETAG,
                                "del" => self::IGNORE,
                                "choice" => self::IGNORETAG,
                                "corr" => self::IGNORETAG,
                                "sic" => self::IGNORE,
                                "orig" => self::IGNORE,
                                "space" => self::IGNORE,
                                "#comment" => self::IGNORE,
                                );
    
    protected $origHideFILTERS = array( 
                                    "explicit hiatus (_)" => '_',
                                    //"dandas with numbers" => '\s*\|+\s*[\d-]+\s*\|+\s*',
                                    "dandas" => '\s+\|+|\|+\s*',
                                    "line fillers (¦)" => '¦',
                                    "numbers" => '\s+[\d-]+|[\d-]+\s*',
                                    "hyphens and dashes" => array('\-', '—'),
                                    "avagrahas" => '\'',
                                    "commas" => ',',
                                    "periods/elipsises" => ".",
                                    "quotation marks" => array('"','“','”','‘','’'),
                                    );
    protected $hideFILTERS = array();


    protected $origSubFILTERS = array(
        "final d/t" => array('d\b', "replace_with" => 't'),
        "visarga aḥ" => array('a[ḥsśrṣ](?!\S)', "replace_with" => 'o'),
        "other visargas" => array('[rsśṣ](?!\S)', "replace_with" => 'ḥ'),
        "final au/āv" => array('āv(?!\S)', "replace_with" => 'au'),
        "final anusvāra" => array('ṃ?m(?!\S)', "replace_with" => 'ṃ'),
        "kcch/kś" => 'k(?:[ c]ch| ?ś)',
        "cch/ch" => array('(?<![\s\Z])c(?:c| c|)h(?![\s\Z])','(?<![\s\Z])t ś(?![\s\Z])'),
        "nasals" => array('[ñṅṇ](?![\s\Z])','m(?=[db])','n(?=[tdn])', "replace_with" => 'ṃ'),
        "ddh/dh" => array('(?<![\s\Z])ddh(?![\s\Z])', "replace_with" => 'dh'),
        "sya,tra,ma before iti" => '(?<=sy|tr|m)a i|e(?=t[iy])',
        // most of these iti rules can be applied only to printed editions
        "e/a + iti" => array('e(?= it[iy])',"replace_with" => 'a'),
        "i iti/īti" => array('i i(?=t[iy])', "replace_with" => 'ī'),
        "iti + vowel" => array('y(?= [āauūeo])', "replace_with" => "i"),
        // replacing i with y fails in some cases,
        // as in "abhyupaiti | etad"
        // or in "ity bhāvaḥ" compared to "iti āśaṇkyaḥ"
        "tt/t" => array('(?<=[rṛi]|pa)tt(?![\s\Z])','\Btt(?=v[^\s\Z])', "replace_with" =>"t"),
        // replace \B with something like (?!\b) or (?![\s\Z])
                                    );

    protected $subFILTERS = array();


    protected $blockLevelNames = array('text','body','group','div','div1','div2','div3','div4','div5','div6','div7','p','l','lg','head');
    
    protected $blockLevelElements = '';

    function __construct() {
        foreach($this->blockLevelNames as $name) {
            $this->blockLevelElements .= './x:'.$name;
            if($name !== end($this->blockLevelNames))
                $this->blockLevelElements .= '|';
        }
    }

    public function compare($file1,$file2) {
        
        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');

        $this->implodeSubFilters();
        $this->optimizeHideFilters();

        $return = "";
        list($text1,$xpath1) = $this->loadFile($file1);
        list($text2,$xpath2) = $this->loadFile($file2);
        $xpathpath = "/x:TEI/x:text";
        $elements1 = $xpath1->query($xpathpath)->item(0);
        $elements2 = $xpath2->query($xpathpath)->item(0);
        
        $msid = $this->getSiglum($xpath2);
        if(!$msid) $msid = basename($file2);
                
        $this->recurse_elements($elements1,$elements2,$xpath1,$xpath2,$msid,$return);
    return $return;
            }

    public function getSiglum($xpath) {
        $msidpath = $xpath->query("/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:msDesc/x:msIdentifier/x:idno[@type='siglum']")->item(0);
        return $msidpath->nodeValue;
    }

    public function getTitle($xpath) {
        return $xpath->query("/x:TEI/x:teiHeader/x:fileDesc/x:titleStmt/x:title")->item(0)->nodeValue;
    }
    
    public function transform($str,$xsl="styles.xsl") {
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
    public function getSubFIlters() {
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
            elseif(array_key_exists($value,$settings))
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
        foreach($this->origSubFILTERS as $key => $value) {
            if(is_array($value)) {
                if(array_key_exists("replace_with",$value)) {
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
            $this->subFILTERS[] = array($value,$replacechar);
        }
        return 1; 
    }

    private function optimizeHideFilters($reset=FALSE) {
        if(!$reset && array_key_exists("_optimized",$this->hideFILTERS)) {
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
            else $this->hideFILTERS[$key] = $value;
        }
        if($newfilter) {
                $this->hideFILTERS["_optimized"] = "[".$newfilter."]";
        }
        return 1;
    }

    public function collate($strs) {
        
        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');

        $this->implodeSubFilters();
        $this->optimizeHideFilters();

        $witnesses = array();
        foreach($strs as $str) {
            $witnesses[] = $this->loadText($str);
        }
        $edition = $witnesses[0];
        
        $mainapparati = $edition[1]->query("//apparatus");
        $length = $mainapparati->length;
        for($n=0;$n < $length;$n++) {
            $collated = array();
            $parentnode = $mainapparati->item($n);
            foreach($witnesses as $witness) {
                $apparatus = $witness[1]->query("//apparatus")->item($n);
                $variants = $witness[1]->query("*",$apparatus);
                foreach($variants as $variant) {
                    $arrkey = $variant->getAttribute("location");
                    $newcontent = $this->DOMinnerXML($variant->firstChild);
                    $ms = $variant->getAttribute("mss");
                    if(!array_key_exists($arrkey,$collated)) {
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
                    "' mss='".$allmss."'><mainreading>".$entry['content']."</mainreading>".$readings."</variant>";
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
            if($str1 == $str2) return $cleanstr;
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
            $str = mb_ereg_replace($regex,'',$str);
        }
        $cleanstr = $str;
        foreach($this->subFILTERS as $subfilter) {
            $str = mb_ereg_replace($subfilter[0],$subfilter[1],$str);
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

    private function filterText($text,&$ignored) {
        $origlength = mb_strlen($text);
        $subarray = array();

        foreach ($this->hideFILTERS as $regex) {
            $text = mb_ereg_replace_callback($regex,
                    function($matches) use(&$subarray) {
                        return $this->unicodeReplace($matches[0],$subarray,2,TRUE);
                    }       
                    ,$text);
        }
        $hidePos = array();
        if(!empty($subarray)) {
            foreach($subarray as $placeholder => $value) {
                $place = mb_strpos($text,$placeholder);
                $hidePos[$place] = $value;
            }
            ksort($hidePos);
            $text = str_replace(array_keys($subarray),"",$text);
            $subarray = array();
        }
        
        foreach ($this->subFILTERS as $item) {
            list($regex, $subchar) = $item;
            $text = mb_ereg_replace_callback($regex,
                    function($matches) use(&$subarray,$subchar) {
                        $save = array($matches[0],$subchar);
                        return $this->unicodeReplace($save,$subarray,2,TRUE);
                    }       
                    ,$text);
        }
        // normalize whitespace characters
        $text = preg_replace_callback("/\s{2,}|\n+|\r+|\t+/",
                    function($matches) use(&$subarray) {
                        $save = array($matches[0]," ");
                        return $this->unicodeReplace($save,$subarray,2,TRUE);
                    }       
                    ,$text);

        $subPos = array();
        if(!empty($subarray)) {
            foreach($subarray as $key => $value) {
                $place = mb_strpos($text,$key);
                $subPos[$place] = array($value[0],mb_strlen($value[1]));
                $subarray[$key] = $value[1];
            }
            ksort($subPos);
        }

        $ignored = array($hidePos,$subPos);
        $text = str_replace(array_keys($subarray),array_values($subarray),$text);
        return $text;
    }
    
    private function ignoreTag(&$startpos,$ignorestr,&$ignored) {
        $ignoreIndex = $startpos;
        $ignored[$ignoreIndex] = $ignorestr;
        $startpos += mb_strlen($ignorestr);

    }

    private function filterNode(DOMNode $element,$range = 0) {
        $finalXML = "";
        $subarray = array();
        $ignoredTags = array();
        $ignoredText = array();
        
        $children = $element->childNodes;

        // Trims any space at the beginning and end of the block
        if($children->length != 0) {
            if($element->firstChild->nodeType == 3)
                $element->firstChild->nodeValue = ltrim($element->firstChild->nodeValue);
            if($element->lastChild->nodeType == 3)
                $element->lastChild->nodeValue = rtrim($element->lastChild->nodeValue);
        }
        $startpos = 0;
        foreach($children as $child) { // check each node, whether tag or text, within the element
            $filter = $this->checkTagFilters($child);
            foreach($filter as $el) {
                if(is_string($el)) {
                    $finalXML .= $el;
                    $startpos += mb_strlen($el);
                }
                elseif($el[0] == self::IGNORETAG || $el[0] == self::IGNORE) {
                    $this->ignoreTag($startpos,$el[1],$ignoredTags);
                }
                elseif($el[0] == self::SHOW) {
                    $finalXML .= $this->unicodeReplace($el[1],$subarray,$range);
                    $startpos++;
                }
                        // else hide
            }
        } // end foreach
        
        $finalXML = $this->filterText($finalXML,$ignoredText);
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
        $count += mb_strlen($text); // + 1 if missing a space after split
        $pos = key($posArray);
        while(($startpos <= $pos) && ($pos < $count)) {
            $ins = current($posArray);
            
            if(is_array($ins)) {
                $inslength = $ins[1];
                $ins = $ins[0];
            } else $inslength = 0;
            
            $strbegin = mb_substr($text,0,$pos-$startpos);
            $strend = mb_substr($text,$pos-$startpos+$inslength);
            $text = $strbegin . $ins . $strend;
            $count += mb_strlen($ins) - $inslength;
            
            if(next($posArray) === FALSE) { // if there are no more replacements
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
                $spacecount += substr_count($maintext,' ');
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
                $spacecount += substr_count($section1,' ');

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
