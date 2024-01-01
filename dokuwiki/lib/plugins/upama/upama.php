<?php
//ini_set('display_errors','On');
//error_reporting(E_ALL);
require_once("DiffMatchPatch/DiffMatchPatch.php");
require_once("DiffMatchPatch/Diff.php");
require_once("DiffMatchPatch/DiffToolkit.php");
require_once("DiffMatchPatch/Matcher.php");
require_once("DiffMatchPatch/Patch.php");
require_once("DiffMatchPatch/PatchObject.php");
require_once("DiffMatchPatch/Utils.php");
require_once("affinealign.php");

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
    protected $simpleSubFILTERS = array();
    protected $whitespaceFILTERS = array(
                    ["name" => "ltrim",
                     "find" => '^\s+',
                     "replace" => ''],
                    ["name" => "rtrim",
                     "find" => '\s+$', // do we need a space at the end of each section?
                     "replace" => ''],
                    ["name" => "middle",
                     "find" => '\s\s+|[\n\t\f]',
                     "replace" => ' '],
                    );
  
    protected $unicodeReplacements = array();
    
    protected $blockLevelNames = array();
    protected $blockLevelElements = '';

    protected $affixlemmata = 10;
    protected $minaffixlength = 2;
    protected $maxOmissionLength = 15;
    protected $longerAffixes = true;

    function __construct() {
        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');

        // load filters from config files
        foreach(include('tagfilters.php') as $k => $v) $this->tagFILTERS[$k] = $v;
        foreach(include('hidefilters.php') as $k => $v) $this->origHideFILTERS[$k] = $v;
        foreach(include('subfilters.php') as $k => $v) $this->origSubFILTERS[$k] = $v;
        
    }

    public function compare(string $file1, string $file2, string $basename) {
        $text1 = file_get_contents($file1);
        $text2 = file_get_contents($file2);
        $url = $basename ?: $file2;
        return $this->docompare($text1,$text2,$url);
    }

    public function compareFileStr(string $file1, string $text2, string $basename) {
        $text1 = file_get_contents($file1);
        return $this->docompare($text1,$text2,$basename);
    }

    public function docompare(string $str1, string $str2, string $basename) {
    
        $ret1 = $this->loadText($str1);
        if(is_array($ret1)) list($text1,$xpath1) = $ret1;
        else
            throw new Exception($ret1);
    
        $ret2 = $this->loadText($str2);
        if(is_array($ret2)) list($text2,$xpath2) = $ret2;
        else 
            throw new Exception($ret2);

        $this->implodeSubFilters([$xpath1,$xpath2]);
        $this->optimizeHideFilters();

        $siglum = $this->getSiglum($xpath2);

        if(!$siglum) {
            $msid = basename($basename,'.txt');
            $msidnode = '<idno type="siglum">'.$msid.'</idno>';
        }
        else { 
            $msid = $siglum->nodeValue;
            $msidnode = $this->DOMouterXML($siglum);
        }
        $msid = $this->sanitizeNCName($msid);
        $sourceDesc = $xpath1->query("/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc")->item(0);
        $listWit = $text1->createDocumentFragment();
        $listWit->appendXML("<listWit resp='upama'><witness xml:id='$msid' ref='$basename'>$msidnode</witness></listWit>");
        $listWit->appendXML($this->getFiliation($xpath2));

        $sourceDesc->appendChild($listWit);
        $elements1 = $xpath1->query("/x:TEI/x:text//*[@xml:id]");
        $elements2 = $xpath2->query("/x:TEI/x:text//*[@xml:id]");
      
        $el2indexed = array();
        foreach($elements2 as $el2) {
            $elname = $el2->getAttribute("xml:id");
            $el2indexed[$elname] = $el2;
        }
        
        $rootNS = $text1->lookupNamespaceUri($text1->namespaceURI);

        foreach ($elements1 as $el1) {

            if($el1->getAttribute("type") == 'apparatus') continue;

            $elname = $el1->getAttribute("xml:id");

            
            $el2 = isset($el2indexed[$elname]) ? $el2indexed[$elname] : FALSE;
            if(!$el2) {
               $newel = $text1->createElementNS($rootNS,'maintext');
             /*  if($el1->firstChild->nodeType == 3)
                    $el1->firstChild->nodeValue = ltrim($el1->firstChild->nodeValue);
               */
               while($el1->hasChildNodes()) {
                   $newel->appendChild($el1->childNodes->item(0));
               }

               $this->prefilterNode($newel);
               $el1->appendChild($newel);
              
               $appel = $this->makeAppDiv($text1,$rootNS,$elname);
               $emptyapp = $text1->createElementNS($rootNS,'listApp');
               $exattr = $text1->createAttribute('exclude');
               $exattr->value = '#' . $msid;
               $emptyapp->appendChild($exattr);
               $appel->appendChild($emptyapp);
               
               $app2 = $xpath1->query("//x:div2[@type='apparatus' and @target='#".$elname."'] | //x:ab[@type='apparatus' and @corresp='#".$elname."']")->item(0);
               if($app2) $appel->appendChild($app2);
               $el1->appendChild($appel);
            }
            else {
                list($dom1text,$ignored1) = $this->filterNode($el1);
                list($dom2text,$ignored2) = $this->filterNode($el2);
                $dmp = new DiffMatchPatch();
                $diffs = $dmp->diff_main($dom1text,$dom2text,false);
                //$dmp->diff_cleanupSemantic($diffs);
                //$dmp->diff_cleanupEfficiency($diffs);
                $diffstring = $this->diffToXml($diffs,$ignored1,$ignored2,$msid);
                $newel = $text1->createElementNS($rootNS,'maintext');
                while($el1->hasChildNodes()) {
                    $newel->appendChild($el1->childNodes->item(0));
                }
                $el1->appendChild($newel);

                $frag = $text1->createDocumentFragment();
                $frag->appendXML($diffstring);
                //$el1->nodeValue = '';

                $appel = $this->makeAppDiv($text1,$rootNS,$elname);
                $appel->appendChild($frag);
                $app2 = $xpath1->query("//x:div2[@type='apparatus' and @target='#".$elname."'] | //x:ab[@type='apparatus' and @corresp='#".$elname."']")->item(0);
                if($app2) $appel->appendChild($app2);
                
                $el1->appendChild($appel);
        
            }
        }

        $otherwitnesses = $xpath2->query("/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:listWit[not(@resp='upama') and not(@resp='upama-groups')]");
        if(!$otherwitnesses || $otherwitnesses->length === 0) {
            return $text1->saveXML();
            // outputting as text fixes namespace issues
        }
        else {
            $addwits = $xpath2->query("./x:witness",$otherwitnesses->item(0));
            $witarr = [];
            foreach($addwits as $addwit) {
                $witref = $addwit->getAttribute('xml:id');
                $witarr[] = $this->additionalWitness($str2,$witref,$msid);
            }
            return array($text1->saveXML(),$witarr);
        }
    }
    private function sanitizeNCName(string $str): string {
        $newstr = preg_replace('/[^\w\-.]/','_',urlencode($str));
        return preg_match('/^[A-Za-z_]/',$newstr) ?
            $newstr : "w$newstr";
    }

    private function makeAppDiv(DOMDocument $doc, string $ns, string $name) : DOMElement {
        $appel = $doc->createElementNS($ns,'div');
        $appel->setAttribute('type','apparatus');
        $appel->setAttribute('target','#'.$name);
        return $appel;
    }
    
    private function getFiliation(DOMXPath $xpath) : string {
        $fils = $xpath->query('//x:filiation');
        if($fils->length === 0) return '';

        $str = array_map(function($fil) {
            $corresp = ltrim($fil->getAttribute('corresp'),'#');
            $xml = $this->DOMinnerXML($fil);
            return "<witness xml:id='$corresp'>$xml</witness>";
        },iterator_to_array($fils));
        return '<listWit resp="upama-groups">'.implode('',$str).'</listWit>';
    }

    private function additionalWitness(string $str, string $ref, string $parref): string {
        $ret = $this->loadText($str);
        if(is_array($ret)) list($text,$xpath) = $ret;
        else 
            throw new Exception($ret);
        $listWit = $xpath->query("/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:listWit[not(@resp='upama') and not(@resp='upama-groups')]")->item(0);
        $wit = $xpath->query("./x:witness[@xml:id='$ref']",$listWit)->item(0);
        $xmlid = '#' . $ref;
        $newsiglum = $xpath->query('./x:idno',$wit)->item(0);
        $newsiglum = $newsiglum ? $this->DOMinnerXML($newsiglum) : $ref;

        $oldsiglum = $this->getSiglum($xpath);
        if(!$oldsiglum) {
            $msid = $parref . '-' . $ref;
            $msidxml = $msid . '-' . $newsiglum;
        }
        else {
            $msid = $oldsiglum->nodeValue . '-' . $ref;
            $msidxml = $oldsiglum->nodeValue . '-' . $newsiglum;
        }

        $sourceDesc = $xpath->query("/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc")->item(0);
        $sourceDesc->removeChild($listWit);

        $siglum = $text->createDocumentFragment();
        $siglum->appendXML("<idno type='siglum' source='#$parref'>$msidxml</idno>");

        $msIdentifier = $xpath->query("/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:msDesc/x:msIdentifier")->item(0);
        if($oldsiglum) {
            $msIdentifier->replaceChild($siglum,$oldsiglum);
        }
        else {
            $msIdentifier->appendChild($siglum);
        }

        $apps = $xpath->query("/x:TEI/x:text//x:app");
        foreach($apps as $app) {
            $lemma = $xpath->query("./x:lem",$app)->item(0);
            $rdgs = $xpath->query("./x:rdg",$app);
            $rightrdg = NULL;
            foreach($rdgs as $rdg) {
                $witnesses = $rdg->getAttribute('wit');
                $witlist = explode(' ',$witnesses);
                if(in_array($xmlid,$witlist)) {
                    $rightrdg = $rdg;
                    break;
                }
            }
            if($rightrdg) {
                $frag = $text->createDocumentFragment();
                $frag->appendXML($this->DOMinnerXML($rightrdg));
                $app->parentNode->replaceChild($frag,$app);
            }
            else {
                $frag = $text->createDocumentFragment();
                $frag->appendXML($this->DOMinnerXML($lemma));
                $app->parentNode->replaceChild($frag,$app);
            }
        }
        return $text->saveXML();
}

    public function latex(string $text, string $xsl = 'latex.xsl'): string {
        $res = $this->loadText($text);
        if(is_array($res)) list($xml,$xpath) = $res;
        else {
            print($res);
            return null;
        }
        //list($xml,$xpath) = $this->loadText($text);
        $this->mergeAdds($xml,$xpath);
        $elements = $xpath->query("/x:TEI/x:text//*[@xml:id]");
        $listwit = $xpath->query("/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:listWit[@resp='upama']/x:witness");
        $witnesses = array();
        foreach($listwit as $wit) {
            $witnesses["#" . $wit->getAttribute("xml:id")] = 
                $this->DOMinnerXML($xpath->query("./x:idno",$wit)->item(0));
        }
        
        $return = <<<'EOT'
\documentclass[14pt]{extarticle}
\usepackage{polyglossia,fontspec,xunicode}
\usepackage[normalem]{ulem}
\usepackage[noend,noeledsec,noledgroup]{reledmac}
\usepackage[margin=1in]{geometry}

\arrangementX[A]{paragraph}
\arrangementX[B]{paragraph}
\renewcommand*{\thefootnoteB}{\Roman{footnoteB}}
\arrangementX[C]{paragraph}
\renewcommand*{\thefootnoteC}{\roman{footnoteC}}

\newcommand*{\caesura}{\linebreak}

\Xarrangement[A]{paragraph}
\Xnotenumfont[A]{\bfseries}
\Xlemmafont[A]{\bfseries}

\setdefaultlanguage{sanskrit}
\setotherlanguage{english}
\newfontfamily\devanagarifont{Brill}
\newfontfamily{\devafont}{Pedantic Devanagari}

\usepackage[Devanagari,DevanagariExtended]{ucharclasses}

\makeatletter
\setTransitionsFor{Devanagari}%
 {\let\curfamily\f@family\let\curshape\f@shape\let\curseries\f@series\devafont}
 {\fontfamily{\curfamily}\fontshape{\curshape}\fontseries{\curseries}\selectfont}
\makeatother

\makeatletter
\setTransitionsFor{DevanagariExtended}%
 {\let\curfamily\f@family\let\curshape\f@shape\let\curseries\f@series\devafont}
 {\fontfamily{\curfamily}\fontshape{\curshape}\fontseries{\curseries}\selectfont}
\makeatother

\begin{document}
    \raggedright
\input{sanskrit-hyphenations}

\lineation{page}
\begingroup
\beginnumbering

EOT;
        foreach ($elements as $el) {

            $xmlid = $el->getAttribute('xml:id');
            $main = $xpath->query("./x:maintext",$el)->item(0);
            if(!$main)
              $main = $el; 
            
            if($main->childNodes->length === 0) {
                $main->appendChild($xml->createTextNode(' '));
            }

            if($el->getAttribute('type') === 'apparatus')
                continue;
        
            $apps = $xpath->query("./x:div[@type='apparatus']/x:listApp/*",$el);

            $mains = $this->latexSplit($main);
            $openbrackets = [];
            $closebrackets = [];
            $edlabels = [];
            foreach($apps as $app) {
/*                if($app->nodeName == 'app') {
                    $rdgs = $xpath->query("./x:rdg", $app);
                    $loc = explode("x",$app->getAttribute("loc"));
                }
                else { // <rdgGrp>
                    $rdgs = $xpath->query("./x:app/x:rdg",$app);
                    $loc = explode("x",$app->firstChild->getAttribute("loc"));
                }

                $note = $this->latexCriticalNote($rdgs,$witnesses);
*/
                if($app->nodeName == 'app') {
                    $loc = array_map('intval',explode("x",$app->getAttribute("loc")));
                    $note = $this->shorterLatexCriticalNote($app,$witnesses,$xpath);
                }
                else { // <rdgGrp>
                    $apps = $xpath->query("./x:app",$app);
                    $loc = array_map('intval',explode("x",$app->firstChild->getAttribute("loc")));
                    $note = $this->shorterLatexCriticalNote($apps,$witnesses,$xpath);
                }
                if(!array_key_exists($loc[1],$closebrackets))
                    $closebrackets[$loc[1]] = '';
                
                if($loc[1] - $loc[0] == 1) { // one-word lemma
                    $openbrackets[$loc[0]] = '\edtext{';
                    if($closebrackets[$loc[1]] != '') {
                        $closebrackets[$loc[1]] .= '\lemma{' . $this->latexCleanLemma($mains[$loc[0]]) . '}';
                    }
                    $closebrackets[$loc[1]] .= '  \Afootnote{'.$note."}\n";
                }
                else { // multi-word lemma
                    if(!array_key_exists($loc[0],$edlabels)) {
                        $edlabels[$loc[0]] = "$xmlid-$loc[0]";
                    }
                    
                    if(!array_key_exists($loc[1]-1,$openbrackets))
                        $openbrackets[$loc[1]-1] = '\edtext{';

                    if($loc[1] - $loc[0] == 2)
                        $ldots = ' ';
                    else
                        $ldots = '\ldots ';

                    $lemma = $this->latexCleanLemma($mains[$loc[0]] . $ldots . $mains[$loc[1]-1]);

                    $closebrackets[$loc[1]] .= '  \linenum{|\xlineref{'.$edlabels[$loc[0]].'}}\lemma{'.$lemma.'}\Afootnote{'.$note."}\n";
                }
            }
            $app2 = $xpath->query("//x:div2[@target='#$xmlid'] | //x:ab[@corresp='#$xmlid']",$el)->item(0);
            $sources = $app2 ? $xpath->query("//x:list[@type='sources']/x:item",$app2) : [];
            $parallels = $app2 ? $xpath->query("//x:list[@type='parallels']/x:item",$app2) : [];
            $testimonia = $app2 ? $xpath->query("//x:list[@type='testimonia']/x:item",$app2) : [];
            $notes = $app2 ? $xpath->query("//x:list[@type='notes']/x:item",$app2) : [];

            $outstr = '';
            $closetag = false;
            foreach($mains as $n => $main) {
                if(array_key_exists($n,$closebrackets))
                    $outstr .= "}{\n" . $closebrackets[$n] . "} ";

                if($closetag) {
                    $outstr .= $closetag;
                    $closetag = false;
                }
                
                $maintrim = rtrim($main);
                $lpos = strpos($maintrim,'<l>');
                if($lpos !== false) {
                    list($opentag,$maintrim) = explode('<l>',$maintrim,2);
                    $outstr .= $opentag . '<l>';
                }

                if(array_key_exists($n,$openbrackets))
                    $outstr .= $openbrackets[$n];

                if(substr($maintrim, -4) == '</l>') {
                    $maintrim = substr($maintrim,0,-4);
                    $closetag = '</l>';
                }

                else if(substr($maintrim, -5) == '</lg>') {
                    $maintrim = strstr($maintrim,'</l>',true);
                    $closetag = '</l></lg>';
                }

                // now do anchors
                preg_match_all('/<anchor n="(.+?)".*?\/>/',$maintrim,$matches,PREG_OFFSET_CAPTURE);
           
                $max = sizeof($matches[0]);
                $fullnote = '';

                for($m = 0; $m < $max; $m++) {
                    $match = $matches[0][$m];
                    $matchtxt = $match[0];
                    $matchlen = strlen($matchtxt);
                    $matchstart = $match[1];
                    $fnote = "\n";
                    $anchorid = $matches[1][$m][0];

                    foreach($sources as $item)
                        $fnote .= $this->makeNote($item,$anchorid,'footnoteA');
                    foreach($parallels as $item)
                        $fnote .= $this->makeNote($item,$anchorid,'footnoteB');
                    foreach($testimonia as $item)
                        $fnote .= $this->makeNote($item,$anchorid,'footnoteC');
                    foreach($notes as $item)
                        $fnote .= $this->makeNote($item,$anchorid,'footnoteD');

                    $maintrim = substr_replace($maintrim,'',$matchstart,$matchlen);
                    $fullnote .= $fnote;
                }
  
                $outstr .= rtrim($maintrim);

                if(array_key_exists($n,$edlabels)) {
                    if($n == 0)
                        $outstr .= '\emph{\edlabel{'.$edlabels[$n].'}}';
                    else
                        $outstr .= '\edlabel{'.$edlabels[$n].'}';
                }
                
                $outstr .= $fullnote;
                if(!array_key_exists($n+1,$closebrackets))
                    $outstr .= " ";
            }
            if(array_key_exists($n+1,$closebrackets))
                $outstr .= "}{\n" . $closebrackets[$n+1] . "}";
            if($closetag)
                $outstr .= $closetag;
            
//            $outstr .= $fullnote;

            $outertag = $el->nodeName;
            $ret = $this->loadText("<$outertag>$outstr</$outertag>",'',true);
            $out = $ret[0];
            $return .= $this->transform($out->saveXML(),$xsl) . ' ';
        } //end foreach $elements
        $return .= "\n\n\\endnumbering\n\\endgroup\n\\end{document}";
    
        return $return;
    }
    
    private function makeNote(DOMElement $item, string $anchorid, string $n): string {
        $targid = $item->getAttribute('target') ?: $item->getAttribute('corresp');
        $targ = ltrim($targid,'#');
        if($targ === $anchorid) {
            return "\\".$n."{".$this->latexCleanLemma($this->DOMouterXML($item))."}\n";
            
        }
        else
            return '';
    }

    private function mergeAdds(DOMDocument &$xml, DOMXPath $xpath): void {
        $rootNS = $xml->lookupNamespaceUri($xml->namespaceURI);
        $elements = $xpath->query("/x:TEI/x:text//*[@xml:id]");
        foreach($elements as $el) {
            $apps = $xpath->query("./x:div[@type='apparatus']/x:listApp/*",$el);
            $locs = array();
            $adds = array();
            foreach($apps as $app) {
                if($app->nodeName == 'app') {
                    $loc = array_map('intval',explode("x",$app->getAttribute("loc")));
                    if($loc[0] === $loc[1])
                        $adds[$loc[0]] = $app;
                    else
                        $locs[$loc[0]."x".$loc[1]] = $app;
                }
                else { // <rdgGrp>
                    $loc = array_map('intval',explode("x",$app->firstChild->getAttribute("loc")));
                    if($loc[0] === $loc[1])
                        $adds[$loc[0]] = $app;
                    else
                        $locs[$loc[0]."x".$loc[1]] = $app;
                }
            }

            foreach($adds as $key => $val) {
                if($key === 0) {
                    $newloc = "0x1";
                    foreach($xpath->query(".//x:label",$val) as $label)
                        $label->nodeValue = "pre";
                }
                else
                    $newloc = ($key-1)."x".$key;

                if(!array_key_exists($newloc,$locs)) {
                    if($val->nodeName === 'app')
                        $val->setAttribute("loc",$newloc);
                    else { // rdgGrp
                        $grpApps = $xpath->query("x:app",$val);
                        foreach($grpApps as $grpApp)
                            $grpApp->setAttribute('loc',$newloc);
                    }
                }
                else {
                    $appendtoApp = $locs[$newloc];
                    $add = $val->parentNode->removeChild($val);
                    $rdgs = $xpath->query("x:rdg",$add);
                    $rdgsArr = array();
                    foreach($rdgs as $rdg) $rdgsArr[] = $rdg;

                    foreach($rdgsArr as $rdg) {
                        $wit = $rdg->getAttribute('wit');
                        $possibleRdgs = $xpath->query(".//x:rdg[@wit='".$wit."']",$appendtoApp);
                        if($possibleRdgs->length > 0) {
                            // append to existing variant from same MSS
                            $appendtoRdg = $possibleRdgs->item(0);
                            while($rdg->childNodes->length > 0) {
                                $curNode = $rdg->childNodes->item(0);
                                if($curNode->nodeName != 'label')
                                    $appendtoRdg->appendChild($curNode);
                                else $rdg->removeChild($curNode);
                            }
                        }

                        else if($appendtoApp->nodeName == 'app') {
                            // make new rdgGrp and append
                            $rdg->setAttribute('type','main');
                            $newrdgGrp = $xml->createElementNS($rootNS,'rdgGrp');
                            $appendtoApp->parentNode->insertBefore($newrdgGrp,$appendtoApp);
                            $newrdgGrp->appendChild($appendtoApp);

                            $newApp = $xml->createElementNS($rootNS,'app');
                            $newApp->setAttribute('loc',$newloc);
                            $newApp->setAttribute('mss',$wit);
                            $newApp->appendChild($rdg);
                            $newrdgGrp->appendChild($newApp);
                            $locs[$newloc] = $newrdgGrp;
                            $appendtoApp = $newrdgGrp;
                        }
                        else { // <rdgGrp>
                            $rdg->setAttribute('type','main');
                            $newApp = $xml->createElementNS($rootNS,'app');
                            $newApp->setAttribute('loc',$newloc);
                            $newApp->setAttribute('mss',$wit);
                            $newApp->appendChild($rdg);
                            $appendtoApp->appendChild($newApp);
                        }
                    } // foreach $rdgs
                
                } // else
            } // foreach $adds;
        }
    }

    private function latexCleanLemma(?string $lemma) : string {
        if(!$lemma)
            return '□';
        $lemma = trim($lemma);
        $lemma = preg_replace('/\s+/',' ',$lemma);
        $lemma = preg_replace('/<\/?l\/*>/','',$lemma);
        $lemma = preg_replace('/<lg .*>/','',$lemma);
        $lemma = preg_replace('/<lg>/','',$lemma);
        $lemma = preg_replace('/<\/lg>/','',$lemma);
        $lemma = preg_replace('/<caesura ignored="TRUE"\/>/','',$lemma);
        $lemma = $this->latexEnhance($lemma);
        return $lemma;
    }

    private function latexEnhance(string $text) : string {
        $text = preg_replace("/&amp;/","\ampersand",$text);
//        $text = preg_replace("/&/","\ampersand",$text);
        $text = preg_replace('/(?<!\\\\)_/',"\_",$text);
//        $text = preg_replace("/ \|/","\~|",$text);
        return $text;
    }
    
    private function latexCriticalNote(DOMNodeList $nodelist, array $witnesses) : string {
        $note = '';
        $lastrdg = $nodelist->length - 1;
        foreach($nodelist as $n => $rdg) {
            $wits = $rdg->getAttribute("wit");
            if(!$wits) continue;

            $witline = '';
            $witarr = array();
            
            foreach(preg_split('/\s+/',$wits,NULL,PREG_SPLIT_NO_EMPTY) as $wit) {
                $witarr[] = $witnesses[$wit];
            }
            $witline = implode(' ',$witarr);

            $note .= $this->latexCleanLemma($this->DOMinnerXML($rdg)) . " " . $witline; 
            if($n == $lastrdg) $note .= ".";
            else $note .= "; ";
        }
        return $note;
    }

    private function shorterLatexCriticalNote(/*?DOMNodeList|DOMNode*/ $nodelist, array $witnesses, DOMXPath $xpath) : string {
        $note = '';
        if(!is_a($nodelist,'DOMNodeList')) {
            $nodelist = array($nodelist);
            $lastrdg = 0;
        }
        else
            $lastrdg = $nodelist->length - 1;

        foreach($nodelist as $n => $app) {
            $wits = $app->getAttribute("mss");

            $witline = '';
            $witarr = array();
            $altrdgs = array();

            foreach($xpath->query('./x:rdg[not(@type="main")]',$app) as $rdg) {
                $rdgwits = $rdg->getAttribute('wit');
                foreach(preg_split('/\s+/',$rdgwits,NULL,PREG_SPLIT_NO_EMPTY) as $rdgwit)
                    $altrdgs[] = $rdgwit;
            }
            
            foreach(preg_split('/\s+/',$wits,NULL,PREG_SPLIT_NO_EMPTY) as $wit) {
                if(in_array($wit,$altrdgs))
                    $witarr[] = '\uline{'.$witnesses[$wit].'}';
                else
                    $witarr[] = $witnesses[$wit];
            }
            $witline = implode(' ',$witarr);
            
            $mainrdg = $xpath->query('./x:rdg[@type="main"]',$app)->item(0);
            $note .= $this->latexCleanLemma($this->DOMinnerXML($mainrdg)) . " " . $witline; 
            if($n == $lastrdg) $note .= ".";
            else $note .= "; ";
        }
        return $note;
    }    
    
    private function latexSplit(DOMNode $node,bool $concatSpaces = false, array $tags = ['','']) : array {
        $troublesometags = ['hi','unclear','add','corr','supplied'];
        $kids = $node->childNodes;
        $splitted = array();
        $carryover = '';

        foreach ($kids as $kidno => $kid) {
            if($kid->nodeType === 8) continue; // remove comments

            if($kid->nodeType !== 3) {

                if($kid->getAttribute("ignored") == "TRUE") {
                    // if it was preceded by a space, concat any spaces following this
                    if($carryover == '') $concatSpaces = true;
                    $carryover = $carryover . $kid->ownerDocument->saveXML($kid);
                }
                else { // recursively check nodes that aren't ignored
                    $opentag = "<".$kid->localName . $this->DOMAttributes($kid).">";
                    $closetag = "</".$kid->localName.">";
                    if(in_array($kid->localName,$troublesometags)) {
                        $kidsplit = $this->latexSplit($kid,true,[$opentag,$closetag]);
                        $opentag = '';
                        $closetag = '';
                    }
                    else
                        $kidsplit = $this->latexSplit($kid,true);
                    

                    if(count($kidsplit) == 1) {
                        $kidcontent = $opentag . $kidsplit[0] . $closetag;

                        // for <pc> </pc>
                        if(trim($kidsplit[0]) == '') {
                           $splitted[] = $carryover . $opentag;
                           $carryover = $closetag;
                        }
                        else if(preg_match('/<\/\w+>\s+/',$carryover)) {
                            $splitted[] = $carryover;
                            $carryover = $kidcontent;
                        } else
                            $carryover = $carryover . $kidcontent;
/*                        if($carryover == '') $carryover = $kidcontent;
                        else { 
                            $splitted[] = $carryover . $opentag . $kidsplit[0] . $closetag;
                            $carryover = '';
                        } */
                    }
                    else {
                        $kidstart = $opentag . array_shift($kidsplit);
                        $kidend = array_pop($kidsplit);
                        if(trim($kidend) == '')
                            $kidend = array_pop($kidsplit) . $kidend;
                        if(preg_match('/<\/\w+>\s+/',$carryover)) {
                            $splitted[] = $carryover;
                            $splitted[] = $kidstart;
                        } else
                            $splitted[] = $carryover . $kidstart;
                        
                        $splitted = array_merge($splitted, $kidsplit);
                        
                        $carryover = $kidend . $closetag;
                    }
                }
            }
            else { // text node
                $text = $this->latexEnhance($kid->nodeValue);
                
                if(strlen($text) != 0 && trim($text) == '') {// space node
                    if($carryover != '') {
                        if(!$concatSpaces) {
                            $splitted[] = $tags[0] . $carryover . $text . $tags[1];
                            $carryover = '';
                        }
                        else {
                            $carryover .= $text;
                        }
                    }
                    else {   
                        if(!$concatSpaces) $splitted[] = $tags[0] . $text . $tags[1];
                        else {
                            $carryover = $text;
                        }
                    } 
                    continue;
                }
                
                else {
                    $textsplit = preg_split("/\s+/",$text);
                    if (count($textsplit) === 1) {
                        $carryover .= $textsplit[0]; 
                    }
                    else {
                        $firstsplit = array_shift($textsplit);

//                        $splitted[] = $tags[0] . $carryover . $firstsplit . $tags[1];
                        
                        if($concatSpaces) {
                            if(trim($firstsplit) === '' && trim($carryover) !== '') {
                                // e.g. budhaḥ <caesura/> tato
                                // we want ['budhaḥ <caesura/>','tato']
                                $poppedout = array_pop($splitted);
                                $splitted[] = "$poppedout $tags[0]$carryover$tags[1]";
                            }
                            else 
                                $splitted[] = $tags[0] . $carryover . $firstsplit . $tags[1];
                        }
                        else {
                            if(trim($firstsplit) === '') {
                                if($carryover !== '')
                                    $splitted[] = $tags[0] . $carryover . $tags[1]; 
                                if(count($splitted) == 0)
                                    $splitted[] = $tags[0] . $firstsplit . $tags[1];
                            }
                            else $splitted[] = $tags[0] . $carryover . $firstsplit . $tags[1];

                        }

                        $lastsplit = array_pop($textsplit);
                        if($tags[0] != '') {
                            foreach($textsplit as &$split)
                                $split = $tags[0] . $split . $tags[1];
                        }
                        
                        $splitted = array_merge($splitted, $textsplit);
                        if(trim($lastsplit) == '')
                            $carryover = '';
                        else
                            $carryover = $lastsplit;
                    }
                }
            }
            if($kidno == 0) $concatSpaces = false;
        }
        if($carryover != '') $splitted[] = $tags[0] . $carryover . $tags[1];
        return $splitted;
    }

    public function getStartEnd(DOMXpath $xpath, ?string $startnode, ?string $endnode): array {
        $allnodes = $xpath->query("/x:TEI/x:text//*[@xml:id]");
        $started = false;
        $selectednodes = [];

        if($allnodes->length === 0) {
            throw new Exception (";WARNING: no blocks with @xml:id attributes in ".$msid);
        }

        foreach($allnodes as $n) {
            
            if ($n->getAttribute("type") === "apparatus") continue;

            $name = $n->getAttribute("xml:id");

            if($started || !$startnode || $name === $startnode) {
                $started = true;
                $selectednodes[] = $name;
                if($endnode && $name === $endnode) break;
            }
        }

        return $selectednodes;
    }
    // $node is the name of the first node
    // $endnode is the name of the last node; if $endnode is not specified, only the first node is returned
    // if both $node and $endnode are not specified, the whole document is returned
    public function fasta(string $file, ?object $startnode = null, ?object $endnode = null): void {
        $ret = $this->loadFile($file);
        if(is_array($ret)) list($text,$xpath) = $ret;
        else
            throw new Exception($ret);
    
        $this->implodeSubFilters([$xpath]);
        //$this->setFilter("hidetext","spaces","\s");
        $this->optimizeHideFilters();
       
        $msidnode = $this->getSiglum($xpath);

        if(!$msidnode) {
            $msid = basename($file,'.txt');
        }
        else $msid = $msidnode->nodeValue;
        
        $selected = $this->getStartEnd($xpath,$startnode,$endnode);

        $filtered = ">$msid\n" . $this->fastaLoop($selected,$xpath);


        $otherwitnesses = $xpath->query("/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:listWit[not(@resp='upama') and not(@resp='upama-groups')]");
        if($otherwitnesses && $otherwitnesses->length > 0) {
            $addwits = $xpath->query("./x:witness",$otherwitnesses->item(0));
            foreach($addwits as $addwit) {
                $witref = $addwit->getAttribute('xml:id');
                $newfile = $this->additionalWitness(file_get_contents($file),$witref,$msid);
                list($addtext,$addxpath) = $this->loadText($newfile);
                $addnodes = $this->getStartEnd($addxpath,$startnode,$endnode);
                $filtered .= "\n\n>$witref\n" . $this->fastaLoop($addnodes,$addxpath);
            }
        }

        echo $filtered;
    }

    private function fastaLoop(array $nodes, DOMXPath $xpath): string {

        $filtered = '';
        foreach($nodes as $name) {
            $n = $xpath->query("/x:TEI/x:text//*[@xml:id='".$name."']");
            if(!$n || $n->length === 0) continue;
            $this->fastaFilter($n[0]);
            $ntext = $this->filterText($n[0]->nodeValue)[0];
            $ntext = normalizer_normalize($ntext,Normalizer::FORM_C);
            $ntext = $this->slp1($ntext);
            $ntext = preg_replace("/\s/u","",$ntext);
            $filtered .= $ntext;
        }

        $notallowed = "/[^aAiIuUefFxX3eE0oOM2HkKgGNcCjJYwWqQRtTdDnpPbBmyrlvSzshL\-]/";
        $matched = preg_match_all($notallowed,$filtered,$matches,PREG_PATTERN_ORDER);
        if($matched) {
            echo ";WARNING: sequence contains the following unpermitted characters: ";
            $uni_matches = array_unique($matches[0]);
            foreach($uni_matches as $val) {
                echo $val . " ";
            }
            echo "\n";
        }
        $filtered = preg_replace("/(.{70})/u","$1\n",$filtered);
        return $filtered;

    }

     public function fasta2(string $file, array $nodes, ?array $options = NULL) : string {
        $ret = $this->loadFile($file);
        if(is_array($ret)) list($text,$xpath) = $ret;
        else
            throw new Exception($ret);
        $dofilter = FALSE;
        if($options && $options['normalize'] === TRUE) {
            $this->implodeSubFilters([$xpath]);
                //$this->setFilter("hidetext","spaces","\s");
            $this->optimizeHideFilters();
            $dofilter = TRUE;
        }

        $msidnode = $this->getSiglum($xpath);

        if(!$msidnode) {
            $msid = basename($file,'.txt');
        }
        else $msid = $msidnode->nodeValue;

        $filtered = ">$msid\n" . $this->fasta2Loop($nodes,$xpath,$dofilter);

        $otherwitnesses = $xpath->query("/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:listWit[not(@resp='upama') and not(@resp='upama-groups')]");
        if($otherwitnesses && $otherwitnesses->length > 0) {
            $addwits = $xpath->query("./x:witness",$otherwitnesses->item(0));
            foreach($addwits as $addwit) {
                $witref = $addwit->getAttribute('xml:id');
                $newfile = $this->additionalWitness(file_get_contents($file),$witref,$msid);
                list($addtext,$addxpath) = $this->loadText($newfile);
                $filtered .= "\n\n>$witref\n" . $this->fasta2Loop($nodes,$addxpath,$dofilter);
            }
        }

        return $filtered;
    }
    
    private function fasta2Loop(array $nodes, DOMXPath $xpath, bool $dofilter): string {
        $filtered = '';
        foreach($nodes as $name) {
            $n = $xpath->query("/x:TEI/x:text//*[@xml:id='".$name."']");
            if(!$n || $n->length === 0) continue;
            else {
                $this->fastaFilter($n[0]);
                $ntext = $dofilter ? $this->filterText($n[0]->nodeValue)[0] : $this->collapseSpaces($n[0]->nodeValue);
                $ntext = normalizer_normalize($ntext,Normalizer::FORM_C);
                $filtered .= $ntext;
            }
        }
        return $filtered;
    }

    private function collapseSpaces(string $txt): string {
        $txt = preg_replace('/^\s+/u','',$txt);
        $txt = preg_replace('/\s+$/u',' ',$txt); // not right trim!!
        $txt = preg_replace('/\s\s+|[\n\t\f]/u',' ',$txt);
        return $txt;
    }
   
    public function slp1(string $text): string {
        $iast2slp1 = array(
            "ā" => "A",
            "ai" => "E",
            "au" => "O", // was 3
            //"i" => "1",
            "î" => "i", // was 1
            "ī" => "I", // was 2
            "û" => "u",
            "ū" => "U",
            "ṛ" => "f",
            "ṝ" => "F",
            "ê" => "e",
            "ṃ" => "M",
            "ḥ" => "H",
            "m̐" => "2", // this is ~ in SLP1
            "ṁ" => "2", // this is ~ in SLP1
            "ḿ" => "2", // this is ~ in SLP1
            "o" => "o", // was 0
            "ô" => "o",
            "kh" => "K",
            "gh" => "G",
            "ṅ" => "N",
            "ch" => "C",
            "jh" => "J",
            "ñ" => "Y",
            "ṭh" => "W",
            "ṭ" => "w",
            "ḍh" => "Q",
            "ḍ" => "q",
            "ṇ" => "R",
            "th" => "T",
            "dh" => "D",
            "ph" => "P",
            "bh" => "B",
            "ṙ" => "r",
            "ś" => "S",
            "ṣ" => "z",
            //"l" => "8",
            "ḻh" => "1",
            "ḻ" => "L", // was 8
            "ḷ" => "x",
            "ḹ" => "X",
            "‾" => "-",
        );
        return str_replace(array_keys($iast2slp1),array_values($iast2slp1),$text);
}
    public function fastaFilter(DOMElement $node): void {

        $children = $node->childNodes;
        $hidelist = array();

        if(!$children) return;

        foreach($children as $child) {
            
            if($child->nodeType == 3)
                continue;

            $tagName = $child->localName ?: $child->nodeName;
            if(isset($this->tagFILTERS[$tagName])) {
                $status = $this->tagFILTERS[$tagName];
            }
            else $status = self::SHOW;

            if($status == self::HIDE) {
                $hidelist[] = $child;
            }
            elseif($status == self::IGNORE) {
                $hidelist[] = $child;
            }
            elseif($status == self::SHOW) {
                $this->fastaFilter($child);
            }
            elseif($status == self::IGNORETAG) {
                $this->fastaFilter($child);
            }
        }
        foreach($hidelist as $el) $el->parentNode->removeChild($el);
    }
    public function getSiglum(DOMXPath $xpath) : DOMNode {
        $msidnode = $xpath->query("/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:msDesc/x:msIdentifier/x:idno[@type='siglum']")->item(0);
        return $msidnode;
    }

    public function getTitle(DOMXPath $xpath): string {
        return $xpath->query("/x:TEI/x:teiHeader/x:fileDesc/x:titleStmt/x:title")->item(0)->nodeValue;
    }
    
    public function transform(string $str,string $xsl) : string {
        $xmlDoc = new DOMDocument();
        $xmlDoc->loadXML($str);
        $xslDoc = new DOMDocument();
        $xslDoc->load($xsl);
    
        $proc = new XSLTProcessor();
        $proc->importStyleSheet($xslDoc);
        return $proc->transformtoXML($xmlDoc);
    }

    public function getTagFilters() : array {
        return $this->tagFILTERS;
    }
    public function getHideFilters() : array {
        return $this->origHideFILTERS;
    }
    public function getSubFilters() : array {
        return $this->origSubFILTERS;
    }

    public function setFilter(string $type,string $name,int $value) : void {
        if($type === 'tag') {
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
                trigger_error("Invalid filter status for ".$name.", setting to SHOW", E_USER_NOTICE);
            $this->tagFILTERS[$name] = $setting;
        }
        elseif($type == 'hidetext') {
            $this->origHideFILTERS[$name] = $value;
            //$this->optimizeHideFilters(TRUE);
        }
        elseif($type == 'subtext') {
            $this->origSubFILTERS[] = ["name" => $name, 
                                        "find" => $value['find'],
                                        "replace" => $value['replace']
                                        ];
            //$this->implodeSubFilters(TRUE);
        }
    }
    
    public function removeFilter(string $type,string $name) : void {
        if($type === 'tag')
            unset($this->tagFILTERS[$name]);
        elseif($type === 'hidetext') {
            unset($this->origHideFILTERS[$name]);
            //$this->optimizeHideFilters(TRUE);
        }
        elseif($type === 'subtext') {
            unset($this->origSubFILTERS[$name]);
            //$this->implodeSubFilters(TRUE);
        }
    }
    
    private function parseFilter(array $filter,array $xpaths) : ?array {
            if(isset($filter['include'])) {
                $q = preg_replace('/(\/+)/u','\1x:',$filter['include']);
                $include = false;
                foreach($xpaths as $xpath) {
                    if($xpath->query($q)->length > 0) {
                        $include = true;
                        break;
                    }
                }
                if(!$include) return null;
            }

            if(isset($filter['exclude'])) {
                $q = preg_replace('/(\/+)/u','\1x:',$filter['exclude']);
                $exclude = false;
                foreach($xpaths as $xpath) {
                    if($xpath->query($q)->length > 0) {
                        $exclude = true;
                        break;
                    }
                }
                if($exclude) return null;
            }
            $replacechar = isset($filter['replace']) ?
                $filter['replace'] :
                // these don't get unset; fix this?
                $this->unicodeReplace();

//            if(is_array($value['replace'])) {
//                $findstr = array_map(function ($s) {return '/'.$s.'/u';},$value['find']);
//            }
//           else {

                $findstr = is_array($filter['find']) ?
                    implode("|",$filter['find']) :
                    $filter['find'];
            //}
            return array('/'.$findstr.'/u',$replacechar);
    }

    private function implodeSubFilters(array $xpaths) : void {
//        $allfilters = array_merge($this->origSubFILTERS, $this->whitespaceFILTERS);
//        foreach($allfilters as $value) {
          foreach($this->whitespaceFILTERS as $value) {
            $regexarr = $this->parseFilter($value,$xpaths);
            if($regexarr) $this->simpleSubFILTERS[] = $regexarr;
          }
          foreach($this->origSubFILTERS as $value) {
            if(isset($value['first'])) {
                $regexarr = $this->parseFilter($value,$xpaths);
                if($regexarr) $this->simpleSubFILTERS[] = $regexarr;
            }
            else {
                $regexarr = $this->parseFilter($value,$xpaths);
                if($regexarr) $this->subFILTERS[] = $regexarr;
            }

//            $this->subFILTERS[] = array('/'.$findstr.'/u',$replacechar);
        }
        // return true; 
    }

    private function optimizeHideFilters(bool $reset=false) : int {
        //if(!$reset && array_key_exists("_optimized",$this->hideFILTERS)) {
        if(!$reset && isset($this->hideFILTERS["_optimized"])) {
            return 0;
        }
        $newfilter = "";
        foreach($this->origHideFILTERS as $key => $value) {
            if(is_array($value)) {
                $newfilter .= implode("",$value);
            }
            elseif(mb_strlen($value) === 1) {
                $newfilter .= $value;
            }
            elseif(mb_strlen($value) === 2 && substr($value,0,1) === "\\") {
                $newfilter .= $value;
            }
            else $this->hideFILTERS[$key] = '/'.$value.'/u';
        }
        if($newfilter) {
                $this->hideFILTERS["_optimized"] = "/[".$newfilter."]/u";
        }
        return 1;
    }

    private function witnessList(array &$ed, array $wits, array $groups): void {
        $edlist = $ed[1]->query("/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:listWit[@resp='upama']")->item(0);
        $edfrag = $ed[0]->createDocumentFragment();
        $witstr = '';
        foreach($wits as $wit) {
            $witlist = $wit[1]->query("/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:listWit[@resp='upama']")->item(0);
            $witstr .= $this->DOMinnerXML($witlist);
        }
        foreach($groups as $key => $val) {
            $corresp = implode(' ',$val['members']);
            $witstr .= "<witness xml:id='$key' corresp='$corresp'><idno type='siglum'>{$val['name']}</idno></witness>";
        }
        $edfrag->appendXML($witstr);
        $edlist->appendChild($edfrag);

        // remove useless upama-groups from edition
        $edwitgroups = $ed[1]->query("/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:listWit[@resp='upama-groups']")->item(0);
        if($edwitgroups) $edwitgroups->parentNode->removeChild($edwitgroups);
    }

    public function collate(array $strs): string {
        
        $witnesses = array_map( function($s) {return $this->loadText($s);}, $strs );

        $edition = $witnesses[0];
       
        $this->implodeSubFilters([$edition[1]]);
        $this->optimizeHideFilters();
        
        $otherwits = array_slice($witnesses,1);
        $groups = $this->getWitGroups($witnesses);
        $this->witnessList($edition,$otherwits,$groups);

        $mainapparati = $edition[1]->query("//x:listApp");
        
        $apparati = array_map( function($w) {return $w[1]->query("//x:listApp");}, $witnesses );

        $length = $mainapparati->length;
        for($n=0; $n < $length; $n++) {
            $collated = array();
            /* $collated: 
            // [ '00x00' => // location of word(s)
            //      [ // lemma
            //          'mss'      => ['A','B','C'],
            //          'location' => '00x00x00x00',
            //          'content'  => '(normalized) reading',
            //          'readings' => [
                            'A' => 'variant reading',
                            'C' => 'another variant reading'
                        ]               '
            //      ]
            // ]
            */
            $exclude = array();
            $parentnode = $mainapparati->item($n);
            foreach($witnesses as $m => $witness) {
                $pW = $witness[1]->query("/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:listWit[@resp='upama']/x:witness/x:idno/@source")->item(0);
                $parentWitness = $pW ? $pW->nodeValue : NULL;
                $apparatus = $apparati[$m]->item($n);
                //if(!$apparatus) continue; 
                if($apparatus->hasAttribute('exclude')) {
                    if(!$parentWitness) {
                        $exclude[] = $apparatus->getAttribute('exclude');
                    }
                    continue;
                 }
                
                 $variants = $apparatus->childNodes;
                 foreach($variants as $variant) {
                    $this->collateVarLoop($variant,$parentWitness,$collated); 
                 }
            }

            uksort($collated, function($i1,$i2) {
                if($i1 === $i2) return 0; // this shouldn't be needed
                else {
                    $n1 = explode("x",$i1);
                    $n2 = explode("x",$i2);
                    if($n1[0] < $n2[0]) return -1;
                    if($n1[0] > $n2[0]) return 1;

                    return $n1[1] < $n2[1] ? -1 : 1;
                }
            });

            $parentnode->nodeValue = '';
            if(count($exclude)) {
                $filtered = $this->filterWitGroups($exclude, $groups);
                $exattr = $edition[0]->createAttribute('exclude');
                $exattr->value = implode(' ',$filtered);
                $parentnode->appendChild($exattr);
            }

            $fragment = $edition[0]->createDocumentFragment();
            foreach($collated as $entries) {
                $newstr = $this->printVarLoop($entries,$groups);
                $fragment->appendXML($newstr);
            }
            if($fragment->hasChildNodes())
                $parentnode->appendChild($fragment);
        } // end for($n=0...

        return $edition[0]->saveXML($edition[0]);
    }

    private function getWitGroups(array $wits) : array {
        $groups = array();
        foreach($wits as $wit) {
            $xmlid = '#' . $wit[1]->query('//x:listWit[@resp="upama"]/x:witness')->item(0)->getAttribute('xml:id');
            $fils = $wit[1]->query('//x:listWit[@resp="upama-groups"]/x:witness');
            foreach($fils as $fil) {
                $c = $fil->getAttribute('xml:id');
                if(isset($groups[$c]))
                    $groups[$c]['members'][] = $xmlid;
                else
                    $groups[$c] = [
                        'name' => $this->DOMinnerXML($fil),
                        'members' => array($xmlid)
                    ];
            }
        }
        uasort($groups, function($a, $b) {
            $alen = count($a['members']);
            $blen = count($b['members']);
            if($alen === $blen) return 0;
            return ($alen < $blen) ? 1 : -1;
        });
        return $groups;
    }

    private function filterWitGroups(array $mss, array $groups) : array {
        $return = $mss;
        $accepted = array();
        foreach($groups as $key => $group) {
            $found = $this->groupDiff($key,$group,$return);
            if($found !== null) $return = $found;
        }
        ksort($return);
        return $return;
    }
    
    private function groupDiff(string $key, array $group, array $mss) : ?array {
        $is = array();
        $leftover = $mss;
        foreach($group['members'] as $m) {
            $found = array_search($m,$leftover);
            if($found === false) return null;
            else {
                $is[] = $found;
                unset($leftover[$found]);
            }
        }
        $leftover[min($is)] = "#$key";
        return $leftover;
    }

    private function collateVarLoop(DOMElement $variant,?string $parentWitness,array &$collated): void {
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
                // $arrkey is less specific than $loc
                if($loc !== $entry['location']) continue;

                $oldcontent = $entry['content'];
                $oldms = $entry['mss'][0];
                $compared = $this->compareVariants($oldcontent,$newcontent);
                if($compared !== 0) {
                   
                   if($parentWitness && in_array($parentWitness,$entry['mss'])) {
                        $done = TRUE;
                        break;
                    }
                    
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

    private function printVarLoop(array $entries,array $groups) : string {
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
            $msswithgroups = $this->filterWitGroups($entry['mss'],$groups);
            $allmss = implode(' ',$msswithgroups);
            $nonmain = [];
            $main = [];
            if(isset($entry['readings'])) {
                foreach($entry['readings'] as $ms => $reading) {
                    if($reading !== $entry['content']) {
                        $readings .= "<rdg wit='$ms'>$reading</rdg>"; 
                        $nonmain[] = $ms;
                    }
                }
            }
            $main = implode(' ',array_diff($entry['mss'],$nonmain));
            $newstr .= "<app loc='{$entry['location']}' mss='$allmss'>".
                "<rdg wit='$main' type='main'>{$entry['content']}</rdg>".
                "$readings</app>";
        }
        if(count($entries) > 1) $newstr .= '</rdgGrp>';
        $newstr .= ' ';
        return $newstr;
    }

    private function compareVariants(string $str1,string $str2)/*: int|string*/ {
        if($str1 === $str2) return 1;
        else {
            $cleanstr = "";
            list($str1,$cleanstr) = $this->filterVariant($str1);
            $str2 = $this->filterVariant($str2)[0];
            if($str1 === $str2) {
               // normalize whitespace characters
                $cleanstr = trim($cleanstr);
                $cleanstr = preg_replace("/\s\s+/u"," ",$cleanstr);
                return $cleanstr;
            }
            else return 0;
        }
    }

    private function filterVariant(string $str): array {
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
        
        foreach($this->simpleSubFILTERS as $simplesubfilter) {
            $str = preg_replace($simplesubfilter[0],$simplesubfilter[1],$str);
        }

        foreach($this->subFILTERS as $subfilter) {
            $str = preg_replace($subfilter[0],$subfilter[1],$str);
        }  
        return array($str,$cleanstr);
    }


/*
    private function str_replace_limit($search,$replace,$subject,$limit) {
        return implode($replace, explode($search, $subject, $limit+1));
    }
*/
    public function DOMinnerXML(DOMNode $element): string {
        $innerXML = "";
        $children = $element->childNodes;
        foreach($children as $child)
            $innerXML .= $element->ownerDocument->saveXML($child);
        return $innerXML;
    }
   
    public function DOMouterXML(DOMNode $element): string {
        return $element->ownerDocument->saveXML($element);
    }
    private function filterTextLoop(string $text, array $filters): array {
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
                    if($backref !== '' && trim($backref) == '') { // backreference is spaces
                        list($matchA,$matchB) = explode($backref,$match[0],2);
                        $matchAlen = strlen($matchA);
                        $matchBlen = strlen($matchB);
                        list($subA,$subB) = explode('\1',$subchar,2);
                        $results[$match[1]] = array($matchA,$matchAlen,$subA);
                        if($matchA == $backref) {
                            $subBstart = $match[1] + $matchAlen;
                            $text = substr_replace($text,str_repeat("*",$matchBlen),$subBstart,$matchBlen);
                        }
                        else {
                            $subBstart = $match[1] + $matchAlen + strlen($backref);
                            $text = substr_replace($text,
                                str_repeat("*",$matchAlen).$backref.str_repeat("*",$matchBlen),
                                $match[1],$matchlen);
                        }
                        $results[$subBstart] = array($matchB,$matchBlen,$subB);
                    }
                    else { // backreference is not spaces, or is empty
                        $newsubchar = str_replace('\1',$backref,$subchar);
                        $results[$match[1]] = array($match[0],$matchlen,$newsubchar);
                        
                        // replace with asterisks so that the match can't be filtered again
                        $text = substr_replace($text,str_repeat("*",$matchlen),$match[1],$matchlen);
                    }
                }

                else { // no backreferences
                    $results[$match[1]] = array($match[0],$matchlen,$subchar);
                    $text = substr_replace($text,str_repeat("*",$matchlen),$match[1],$matchlen);
                }
            }
        }

        ksort($results);

        foreach(array_reverse($results,true) as $pos => $el) {
            $text = substr_replace($text,$el[2],$pos,$el[1]);
        }

        return [$text,$results];
    }

    private function filterText(string $text): array {
        $hidePos = array();
        $subPos = array();
        list($text,$hidePos) = $this->filterTextLoop($text,$this->hideFILTERS);
        list($text,$simpleSubPos) = $this->filterTextLoop($text,$this->simpleSubFILTERS);
        list($text,$subPos) = $this->filterTextLoop($text,$this->subFILTERS);

        $ignoredText = array($hidePos,$simpleSubPos,$subPos);
        return [$text,$ignoredText];
    }
  
    private function ignoreTag(int &$startpos,string $ignorestr,array &$ignored): void {
        $ignoreIndex = $startpos;
        $ignorelen = strlen($ignorestr);
        $ignored[$ignoreIndex] = array($ignorestr,$ignorelen,'');
        $startpos += $ignorelen;

    }
    
    private function prefilterNode(DOMNode $node): void {
        
        $children = $node->childNodes;
        $hidelist = array();

        if(!$children) return;

        foreach($children as $child) {
            
            if($child->nodeType == 3)
                continue;

            $tagName = $child->localName ?: $child->nodeName;
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
/*
                $showattr = $child->ownerDocument->createAttribute('upama-show');
                $showattr->value = 'TRUE';
                $child->appendChild($showattr);
*/
                $this->prefilterNode($child);
            }
            elseif($status == self::IGNORETAG) {
                $this->prefilterNode($child);
            }
        }
        foreach($hidelist as $el) $el->parentNode->removeChild($el);
    }

    private function filterNode(DOMNode $node): array {
        
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
    
    private function checkTagFilters(DOMNode $node,int &$startpos = 0,array $ignoredTags = array(),array $subarray = array()): array {
        $returnstr = '';
        $hidelist = array();

        $children = $node->childNodes;

        foreach($children as $child) {
            
            if($child->nodeType == 3) { // text node
                $returnstr .= $child->nodeValue;
                $startpos += strlen($child->nodeValue);
                continue;
            }

            $tagName = $child->localName ?: $child->nodeName;
            // comments have null as localName

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

                $opentag = "<".$tagName . $this->DOMAttributes($child).">";
                $this->ignoreTag($startpos,$opentag,$ignoredTags);

                list($middlestr,$ignoredTags,$subarray) = $this->checkTagFilters($child,$startpos,$ignoredTags,$subarray);

                $closetag = "</".$tagName.">";
                $this->ignoreTag($startpos,$closetag,$ignoredTags);

                $returnstr .= $middlestr;

            }
            elseif($status == self::SHOW) {

               if(!$child->hasChildNodes()) {
/*
                    $showattr = $child->ownerDocument->createAttribute('upama-show');
                    $showattr->value = 'TRUE';
                    $child->appendChild($showattr);
*/
                    $replacestr = $this->DOMouterXML($child);
                    $subchar = $this->unicodeReplace();
                    $subarray[$subchar] = $replacestr;
                    $returnstr .= $subchar;
                    $startpos += strlen($subchar);
                    continue;
                }
                           
                $opentag = "<".$tagName . $this->DOMAttributes($child).">";
                $subchar = $this->unicodeReplace();
                $subarray[$subchar] = $opentag;
                $returnstr .= $subchar;
                $startpos += strlen($subchar);

                list($middlestr,$ignoredTags,$subarray) = $this->checkTagFilters($child,$startpos,$ignoredTags,$subarray);
                $returnstr .= $middlestr;
                
                $closetag = "</".$tagName.">";
                $subchar = $this->unicodeReplace();
                $subarray[$subchar] = $closetag;
                $returnstr .= $subchar;
                $startpos += strlen($subchar);
            }
        }

        // this isn't needed for $returnstr, but it does change the node itself
        foreach($hidelist as $hideel) $hideel->parentNode->removeChild($hideel);
        return [$returnstr,$ignoredTags,$subarray];

    }    
  
    private function checkHideTags(DOMNode $node): void {
    
        $children = $node->childNodes;
        $hidelist = array();

        foreach($children as $child) {

            if($child->nodeType == 3) { // text node
                continue;
            }

            $tagName = $child->localName ?: $child->nodeName;
            
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
    public function unicodeChar(int $code): string {
        $key = json_decode('"\u'.dechex($code).'"');
        $key = iconv('UTF-8', mb_internal_encoding(), $key);
        return $key;
    }

    private function unicodeReplace(): string {
        //$startval = 57344 + (800*$range);
        //$endval = 57344 + (800 + 800*$range); 
        // 57344 - 63743 is the Unicode Private Use Area; this is split into 4 ranges of 800 characters each
        
        $startval = 57344;
        $endval = 63743;
        $code = false;
        
        //$code = sizeof($subarray) + $startval; 
/*        $testval = $startval;
        while(!$code) {
            if(isset($this->unicodeReplacements[$testval])) {
                $testval++;
            }
            else {
                $code = $testval;
            }
        }
*/        
        $code = count($this->unicodeReplacements) + $startval;
        
        if($code > $endval)
            trigger_error("Too many replacements");

        $key = $this->unicodeChar($code);
        
        $this->unicodeReplacements[] = $code;
        
        return $key;
    }
    
    private function restoreSubs(string $text, array $subs): string {
        $keys = array_keys($subs);
/*
        foreach($keys as $key) {
            unset($this->unicodeReplacements[$key]);
        }
*/
        return str_replace($keys, array_values($subs), $text);
    }

    public function DOMAttributes(DOMNode $element): string {
        if($element->hasAttributes()) {
            $retstr = '';
            foreach($element->attributes as $attr) {
                $retstr .= " ".$attr->name ."=\"".$attr->value."\"";
            }
            return $retstr;
        }
        else return '';
    }

    public function loadText(string $str,string $filename = '',bool $fixerrors = false)/*: array|string*/ {
        $text = new DomDocument('1,0','UTF-8');
        libxml_use_internal_errors(true);
        if($fixerrors) {
            $text2 = new DomDocument('1.0','UTF-8');
            $text2->loadHTML('<?xml version="1.0" encoding="UTF-8"><xml_tags>'.$str.'</xml_tags>');
            libxml_clear_errors();
            $str = $this->DOMinnerXML($text2->getElementsByTagName('xml_tags')->item(0));
        }
        
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
    public function loadFile(string $filename) {
        $text = file_get_contents($filename);
        return $this->loadText($text,$filename);
    }

    public function fixSpecialChars(string $data): string {
        $text = str_replace(array(
        '&', '<', '>', '"', "'",
        ), array(
            '&amp;', '&lt;', '&gt;', '&quot;', '&#39;',
        ), $data);
        return $text;
    }

    private function replaceIgnored(int &$count,string $text,array &$posArray,bool $atlast = false,/*bool|array*/ &$kept=false): string {
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
            if(is_array($kept)) $kept[] = $ins[0];
            
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

    private function cleanVartext(string $str): string {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml version="1.0" encoding="UTF-8"><xml_tags>'
        .trim($str).
        '</xml_tags>');
        $unwrap = array();
//        if(!empty(libxml_get_errors()) ) {
            libxml_clear_errors();
            $tagnames = ['lg','l'];
            foreach($tagnames as $tagname) {
                foreach($doc->getElementsByTagName($tagname) as $el) {
                    if(trim($el->textContent) === '')
                        $el->parentNode->removeChild($el);
                    else $unwrap[] = $el;
                }
            }
            /*
            foreach($doc->getElementsByTagName('lg') as $el) {
                if(trim($el->textContent) === '')
                    $el->parentNode->removeChild($el);
                else $unwrap[] = $el;
            }
            foreach($doc->getElementsByTagName('l') as $el) {
                if(trim($el->textContent) === '')
                    $el->parentNode->removeChild($el);
                else $unwrap[] = $el;
            }
            */
            foreach($unwrap as $el) {
                while($el->hasChildNodes())
                    //$el->parentNode->appendChild($el->childNodes->item(0));
                    $el->parentNode->insertBefore($el->childNodes->item(0),$el);
                $el->parentNode->removeChild($el);
            }
            return trim( // trim again to catch spaces before closing tags
                       preg_replace('/\s+/',' ',
                          $this->DOMinnerXML($doc->getElementsByTagName('xml_tags')->item(0))
                       )//;
                   );
//        }
//        else {
//            return $str;
 //       }
    }
    
    private function splitDiffs(array $diffs): array {
        
        $start = array();
        $sections = array();
        $AddOm = false;
        $lastdiff = count($diffs) - 1;
       
        foreach ($diffs as $key => $change) {
            $op = $change[0];
            $text = $change[1];

            if ($op == 1) { // text that is in text2 only
/*
              $start[] = [1,$text];
              $AddOm = false;
*/
                if(empty($start) && $text[strlen($text)-1] == ' ') {
                    // splits the inserted text so it shows up as [add.]
                    $sections[] = [[1,$text]];
                    $AddOm = false;
               }
               else {
                   
                   if(!empty($start) && $text[0] == ' ') {
                        // if the next block starts with a space, split the inserted text so it shows up as [add.]
                        $AddOm = array($start,$text,1);
                   }
                   else
                        $AddOm = false;
                   $start[] = [1,$text];
               }

            } elseif ($op == -1) { // text that is in text1 only
/*               if(trim($text) !== '' && strpos(trim($text),' ') !== false) {
                    $ts = preg_split('/(?<=\S) (?=\S)/u',$text);
                    $starttext = array_shift($ts) . ' ';
                    $endtext = array_pop($ts);
                    if(empty($start))
                        $sections[] = [[-1,$starttext]];
                    elseif($starttext[0] == ' ') {
                        $sections[] = $start;
                        $sections[] = [[-1,$starttext]];
                    }
                    else {
                       $start[] = [-1,$starttext];
                       $sections[] = $start;
                    }
                    if(count($ts) > 0)  
                        $sections[] = [[-1,implode(' ',$ts).' ']];
                    if($endtext[strlen($endtext)-1] == ' ') {
                        $sections[] = [[-1,$endtext]];
                        $start = [];
                    }
                    else {
                        $start = [[-1,$endtext]];
                    }
               }
               else */
               if(empty($start) && $text[strlen($text)-1] == ' ') {
                    // splits the deleted text so it shows up as [om.]
                    $sections[] = [[-1,$text]];
                    //$start = [];
               }
               else {
                   if(!empty($start) && $text[0] == ' ') {
                        // if the next block starts with a space, split the deleted text so it shows up as [om.]
                        $AddOm = array($start,$text,-1);      
                   }

                   $start[] = [-1,$text];
               }
            } else { // text common to both
                
                if($text == ' ') { // text is a single space
                    $start[] = [0,' '];
                    $sections[] = $start;
                    $start = [];
                    continue;
                }

                $t1 = $text[0];
                $t2 = $text[strlen($text)-1];
                $texts = explode(' ',$text);
                
                if($t1 != ' ' && $t2 != ' ') { // no spaces on either side
                    if(count($texts) == 1) {
                        $start[] = [0,$text];
                    }
                    elseif($key == 0) {
                        $start[] = [0,array_pop($texts)];
                        $sections[] = [[0,implode(' ',$texts) . ' ']];
                    }
                    else {
                        $start[] = [0,array_shift($texts) . ' '];
                        $sections[] = $start;

                        if($key == $lastdiff) {
                            $sections[] = [[0,implode(' ',$texts)]];
                            $start = [];
                        }
                        else {
                            $last = array_pop($texts);
                            if(!empty($texts))
                                $sections[] = [[0,implode(' ',$texts) . ' ']];
                            $start = [[0,$last]];
                        }
                    }
                }

                elseif($t1 == ' ' && $t2 == ' ') { // spaces both sides
                    if($AddOm) {
                        $sectionin = $AddOm[0];
                        //$sectionin[] = [-1,' '];
                        // shift the space from the beginning of the omission... 
                        $sectionin[] = [0, ' '];
                        $sections[] = $sectionin;
                        $start = [[$AddOm[2],ltrim($AddOm[1])]];
                        //$AddOm = false;
                        // to the end
                        $sections[] = array_merge($start,[[$AddOm[2],' ']]);
                    }
                    else $sections[] = array_merge($start,[[0,' ']]);

                    $sections[] = [[0,ltrim($text)]];
                    $start = [];
                }

                elseif($t1 == ' ' && $t2 != ' ') { // space at start
                    if($AddOm) {
                        //$sections[] = array_merge($AddOm[0],[[-1,' ']]);
                        // shift the space from the beginning of the omission...
                        $sections[] = array_merge($AddOm[0],[[0,' ']]);
                        $start = [[$AddOm[2],ltrim($AddOm[1])]];
                        //$AddOm = false;
                        // ... to the end
                        $sections[] = array_merge($start,[[$AddOm[2], ' ']]);
                    }
                    else $sections[] = array_merge($start,[[0,' ']]);
                    array_shift($texts);
                    if($key == $lastdiff) {
                        $sections[] = [[0,implode(' ',$texts)]];
                        $start = [];
                    }
                    else {
                        $last = array_pop($texts);
                        if(!empty($texts))
                            $sections[] = [[0,implode(' ',$texts) . ' ']];
                        $start = [[0,$last]];
                    }
                }

                elseif($t1 != ' ' && $t2 == ' ') { // space at end
                    if($key == 0) { // first element
                        $sections[] = [[0,$text]];
                    }
                    else {
                        array_pop($texts);
                        $sections[] = array_merge($start,[[0,array_shift($texts) . ' ']]);
                        if(!empty($texts)) 
                            $sections[] = [[0,implode(' ',$texts) . ' ']];
                        $start = [];
                    }
                }

                $AddOm = false;
            }
            // add leftover text to the array
            if($key == $lastdiff && !empty($start)) $sections[] = $start;       
        }
        return $sections;
    }

    private function diffsToWords($el) {
        $maintext = '';
        $vartext = '';
        $sharedtext = '';
        foreach($el as $sec) {
            if($sec[0] === -1) $maintext .= $sec[1];
            elseif($sec[0] === 1) $vartext .= $sec[1];
            else {
               $maintext .= $sec[1];
               $vartext .= $sec[1];
               $sharedtext .= $sec[1];
            }
        }
        $trimvar = trim($vartext); // why rtrim?
        $trimmain = trim($maintext);
        $eq = $trimvar === $trimmain;
        // this covers sections like dvayasiddhau<d> </d>
        $jiggled = null;
        if(!$eq &&
            // if they're not equal
            ($trimvar !== '' && $trimmain !== '') &&
            // and if they're not both empty
            (strpos($trimvar,' ') !== false || 
            // and if there's a space, not counting the space at the (beginning and) end
            strpos($trimmain,' ') !== false) ) {
            // or if there's a space in the main text (this finds some really short common affixes though -- fix??)
            $aa = new AffineAlign();
            $jiggled = $aa->jiggle($maintext,$vartext);
        }

        if($jiggled)
            return $jiggled;
        else
            return array(
                "maintext" => $eq ? '' : $maintext,
                "vartext" => $eq ? '' : $vartext,
                "sharedtext" => $sharedtext
            );
    }

    private function diffToXml(array $diffs,array $ignored1,array $ignored2,string $msid): string {
        $xmlstring = '';
        $counters1 = array(
                           "text" => array(0,0,0),
                           "tags" => 0,
                           "startspace" => 0,
                           "endspace" => 0,
                           );
        $counters2 = array(
                           "text" => array(0,0,0),
                           "tags" => 0,
                           "prependtags" => [],
                           );
        $postcount = false;
        $precount = false;
        $spaceSplit = array_reduce($this->splitDiffs($diffs),
            function($acc,$cur) {
                $mapped = $this->diffsToWords($cur);
                if(array_key_exists("maintext",$mapped))
                    $acc[] = $mapped;
                else {
                    foreach($mapped as $m)
                        $acc[] = $m;
                }
                return $acc;
            },
            []);
        $lastsection = count($spaceSplit) - 1;
        $apparatus = "<listApp>";
        $atlast = false;

        foreach ($spaceSplit as $key => $section) {
           $charpos = false;
           $startspace = -1;
           $endspace = -1;
           $maintext = $section["maintext"];
           $vartext = $section["vartext"];
           $sharedtext = $section["sharedtext"];

            if($key === $lastsection) $atlast = true;

            if($maintext|$vartext) {

                $vartexts = false;
                $cleanmain = '';
                $main1 = '';
                $main2 = '';
                $varlen = strlen($vartext);
                if( $maintext && (
                    ($varlen > $this->affixlemmata) //|| // if the variant is long
                    //($varlen && strpos(rtrim($vartext),' ') !== false) || // or there's a space, not counting the space at the end
                    //($varlen && strpos(trim($maintext),' ') !== false) // or if there's a space in the main text (this finds some really short common affixes though -- fix??)
                        )
                    ) {
                    $vartexts = $this->findAffixes($maintext,$vartext/*,$section*/);
                }

                if(isset($vartexts["prefix"])) {
                    $precount = "ltrim";
                    $main1 = $this->unfilterText($vartexts["prefix"],$counters1,$ignored1,false,$precount);
                    $startspace = $counters1["startspace"];
                    $cleanmain = $this->unfilterText1($vartexts["main"],$counters1,$ignored1,$atlast);
                    $main2 = $this->unfilterText2($cleanmain,$counters1,$ignored1,$atlast);
                    $endspace = 'x' . $counters1["endspace"];
                    if(substr($vartexts["prefix"],-1) === ' ' ||
                       //substr($main2,0,1) == ' ' ||
                       substr($cleanmain,0,1) === ' ' ||
                       (strlen($vartexts["var"]) > 1 && substr($vartexts["var"],0,1) === ' ')
                       ) {
                    // if there's a space separating the prefix and the variant text
                        if(trim($vartexts["var"]) === '') {
                            // this is an omission.
                            $startspace = $counters1["startspace"];
                            //if(substr($main2,0,1) == ' ')
                            if(!$atlast && substr($cleanmain,0,1) === ' ') // vartext does not end in space if it is last
                                $startspace++;
                        }
                        else
                            // this is an addition
                            $startspace = $counters1["endspace"];
                            // this seems to do the same thing?
                            //$startspace = $counters1["startspace"];
                        $charpos = '';
                    } else 
                        $charpos = "x".$precount;
                }

                elseif(isset($vartexts["suffix"])) {
                    $postcount = "ltrim";
                    $main1 = $this->unfilterText($vartexts["main"],$counters1,$ignored1,false,$postcount);
                    if(substr($vartexts["main"],-1) === ' ' && trim($vartexts["var"]) === '') {
                    // if there is a space separating the lemma and the suffix,
                    // e.g., "<d>jaḍo </d>'jaḍe"
                    // and the variant is an omission
                        $endspace = 'x'.$counters1["endspace"];
                        $charpos = '';
                    }
                    else if(substr($vartexts["var"],-1) === ' ' && trim($vartexts["main"]) === '') {
                        $endspace = 'x' . $counters1["startspace"];
                        $charpos = '';
                    }
                    $startspace = $counters1["startspace"];
                    $main2 = $this->unfilterText($vartexts["suffix"],$counters1,$ignored1,$atlast);
                    if($endspace === -1) $endspace = 'x'.$counters1["endspace"];
                    if($charpos === false) $charpos = "x0x".$postcount;

                }
                else {
                    $cleanmain = $this->unfilterText1($maintext,$counters1,$ignored1,$atlast);
                    $this->unfilterText2($cleanmain,$counters1,$ignored1,$atlast);
                    $startspace = $counters1["startspace"];
                    $endspace = 'x' . $counters1["endspace"];
                }
                
                // now deal with variant text
                
                if(trim($vartext) === '') {
                    $this->unfilterText($vartext,$counters2,$ignored2,$atlast);
                    $vartext = "<label>om</label>";
                }
                else if(!$maintext) {
                        $vartext = $this->unfilterText($vartext,$counters2,$ignored2,$atlast);
                        //$vartext = trim($vartext);
                        $vartext = $this->cleanVartext($vartext);
                        $vartext = "<label>add</label> " . $vartext;
                }
                else {
                    if($vartexts) {
                        if(isset($vartexts["prefix"])) {
                            //$prefix = $vartexts["prefix"];          
                            /*$prefix = */$this->unfilterText($vartexts["prefix"],$counters2,$ignored2);
                            if(trim($vartexts["var"]) === '') {
                                $vartext = "<label>om</label>";
                                if($charpos) {
                                    $omission = $this->cleanVartext($main2);
                                    $stripped = trim(strip_tags($omission));
                                        if(strlen($stripped) > $this->maxOmissionLength)
                                            $omission = $this->shorten($stripped);
                                    $vartext .= " °$omission";
                                }
                                
                                $this->unfilterText($vartexts["var"],$counters2,$ignored2,$atlast);
                            }
                            else {
                                //$vartext = "$prefix  *$vartext";
                                $vartext1 = $this->unfilterText1($vartexts["var"],$counters2,$ignored2,$atlast);
                                $vartext = $this->unfilterText2($vartext1,$counters2,$ignored2,$atlast);
 
                                // this is to catch some visarga sandhi cases, i.e., dravyavacanaḥ ākṛti... vs dravyavacanaḥ sākṛti...
                                // the first one will be normalized to dravyavacana, and the second one will be unchanged
                                if(trim($vartext1) === trim($cleanmain)) continue;
                                $vartext = $this->cleanVartext($vartext);
                                if($charpos) 
                                    $vartext = "°".$vartext;
                                else
                                    $vartext = "<label>add</label> ".$vartext;
                                //$vartext = trim($vartext);
                            }
                        }
                        else { // common suffix
                            if(trim($vartexts["var"]) == '') {
                                $vartext = "<label>om</label>";
                                if($charpos) {
                                    $omission = $this->cleanVartext($main1);
                                    $stripped = trim(strip_tags($omission));
                                        if(strlen($stripped) > $this->maxOmissionLength)
                                            $omission = $this->shorten($stripped);
                                    $vartext .= " ".$omission."°";
                                }

                                $this->unfilterText($vartexts["var"],$counters2,$ignored2);
                            }
                            else {
                                //$vartext = "$vartext*  $suffix";
                                $vartext = $this->unfilterText($vartexts["var"],$counters2,$ignored2);
                                $vartext = $this->cleanVartext($vartext);
                                if($charpos) 
                                    $vartext = $vartext."°";
                                else
                                    $vartext = "<label>add</label> ".$vartext;
                                //$vartext = trim($vartext);
                            }
                            $suffix = $this->unfilterText($vartexts["suffix"],$counters2,$ignored2,$atlast);
                        }
                    }
                    else { // no common affixes
                        $vartext1 = $this->unfilterText1($vartext,$counters2,$ignored2,$atlast);
                        $vartext = $this->unfilterText2($vartext1,$counters2,$ignored2,$atlast);
                        if(trim($vartext1) === trim($cleanmain)) continue;

                        //$vartext = trim($vartext);
                        $vartext = $this->cleanVartext($vartext);
                    }
                }  

                $apparatus .= "<app loc='$startspace$endspace$charpos'".
                    " mss='#$msid'>".
                    "<rdg wit='#$msid' type='main'>$vartext</rdg>".
                    "</app>";

            } // end if($maintext|$vartext)

            else {
                $this->unfilterText($sharedtext,$counters1,$ignored1,$atlast);
                if(!$atlast) {
                    $this->unfilterText($sharedtext,$counters2,$ignored2);
                } // if it's the last section, no need to continue processing the apparatus
            }
        } // end foreach ($spaceSplit as $key => $section)
        
        $apparatus .= "</listApp>";

        return $apparatus;
    }
/*    
    private function shorten($text) {
        $vowels = '/[aāiīeuūoṛṝḷṃḥ\s]/u';
        $consonants = '/[kgcjṭḍtdpbṅñṇnmyrlḻvśṣsh\s]/u';
        
        $arr = preg_split('//u',$text,-1,PREG_SPLIT_NO_EMPTY);
        
        $start = array(array_shift($arr), array_shift($arr), array_shift($arr));
        while(count($arr) > 0 && !preg_match($vowels,$start[count($start)-1])) 
            $start[] = array_shift($arr);

        $end = array(array_pop($arr), array_pop($arr), array_pop($arr));

        while(count($arr) > 0 && !preg_match($consonants,$end[count($end)-1]))
            $end[] = array_pop($arr);

        return implode($start) . "..." . implode(array_reverse($end));
    }
 */

    private function shorten(string $txt): string {
        $arr = $this->aksaraSplit($txt);
        $start = array_slice($arr,0,2);
        $end = array_slice($arr,-2);
        return implode($start) . "…" . implode($end);
    }
 
    private function unfilterText(string $text,array &$counters,array &$ignored,bool $atlast = false,bool &$charcount = false): string {
        $text = $this->unfilterText1($text,$counters,$ignored,$atlast,$charcount);
        $text = $this->unfilterText2($text,$counters,$ignored,$atlast,$charcount);
        return $text;
    }
   
    private function unfilterText1(string $text,array &$counters,array &$ignored,bool $atlast = false,bool &$charcount = false): string {
        $text = $this->replaceIgnored($counters["text"][2],$text,$ignored["text"][2],$atlast);
        $text = $this->replaceIgnored($counters["text"][1],$text,$ignored["text"][1],$atlast);
        return $text;
    }
    private function unfilterText2(string $text,array &$counters,array &$ignored,bool $atlast = false,bool &$charcount = false): string {
        $no = false;
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
            // if the section starts with a space
            if(strlen($text) > 0 && preg_match('/^\s/',$text[0])) $counters["startspace"]++;
            $counters["endspace"] += preg_match_all('/\s+/',$text);
            if($atlast && strlen($text) > 0 && !preg_match('/\s/',$text[strlen($text)-1]) )
                $counters["endspace"]++;
        }
        $doprepend = isset($counters["prependtags"]);
        $tags = $doprepend ? [] : false;
        $text = $this->replaceIgnored($counters["tags"],$text,$ignored["tags"],$atlast,$tags);
        //list($prepend,$postpend) = $this->tagsToStr($counters["prependtags"]);

        $prepend = $doprepend ? $this->tagsToStr($counters["prependtags"]) : '';
        $text = $prepend . $this->restoreSubs($text,$ignored["subs"]); // . $postpend;
        if($doprepend) $this->prependTagsAppend($tags,$counters["prependtags"]);
        return $text;
    }

    private function tagsToStr(?array $tags): string {
        if($tags === false || empty($tags)) return '';
        return array_reduce($tags,function($acc,$el) {return $acc . $el[0];});
        //if(empty($tags)) return ['',''];
        /*return array_reduce($tags,function($acc,$el) {
            $pre = $acc[0] . $el[0];
            $post = $acc[1] . "</".$el[1].">";
            return [$pre,$post];
        },['','']);
        */
    }

    private function prependTagsAppend(?array $tags,array &$tagcounter): void {
        if($tags !== false && !empty($tags)) { 
            
            $opentags = array_map(function($str) {
                if(preg_match('/^<(\w+)\b(?:"[^"]*"|\'[^\']*\'|[^\'">\/])*?>$/',$str,$matches) === 1) return [$str,$matches[1]];
                else return false;
            },$tags);
            $opentags = array_filter($opentags,function($el) {return $el;}); // removes empty elements
            
            $closetags = array_map(function($str) {
                if(preg_match('/^<\/(\w+)>/',$str,$matches) === 1) return [$str,$matches[1]];
            },$tags);
            $closetags = array_filter($closetags,function($el) {return $el;});
            $unclosed = [];
            $index = $this->lastOpenTag($opentags);
            while($index !== null) {
                $tagname = $opentags[$index][1];
                $closetag = $this->correspCloseTag($closetags,$index,$tagname);
                if($closetag !== null) {
                    unset($closetags[$closetag]);
                }
                else {
                    array_unshift($unclosed,$opentags[$index]);
                }
                unset($opentags[$index]);
                $index = $this->lastOpenTag($opentags);
            }
            if(!empty($closetags)) {
                foreach($closetags as $closeindex => $closeval)  {
                    $k = array_keys($tagcounter);
                    $openindex = end($k);
                    //$openindex = array_key_last($tagcounter);
                    if($tagcounter[$openindex][1] === $closeval[1]) {
                        array_pop($tagcounter);
                    }
                    else
                        break;
                }
            }

            $tagcounter = array_merge($tagcounter,$unclosed);
        }
    }

    private function lastOpenTag(array $arr): ?int {
        if(count($arr) === 0) return null;
        $k = array_keys($arr);
        $last = end($k); 
        //$last = array_key_last($arr);
        return $last;
    }

    private function correspCloseTag(array $arr,int $start,string $tagname): ?int {
        if(count($arr) === 0) return null;
        foreach($arr as $key => $val) {
            if($key > $start) {
                if($val[1] === $tagname)
                    return $key;
                else
                    return null;
            }
        }
        return null;
    }
    
    private function binarySearch(array $arr1,array $arr2,int $len1,int $len2,int $min=0): int {
        
//       if($arr1[0] != $arr2[0]) return false;

        $pointermin = $min;  
        $pointermax = min($len1,$len2);  
        $pointermid = $pointermax;  
        $pointerstart = 0;

        while ($pointermin < $pointermid) {  
            if (array_slice($arr1,$pointerstart,$pointermid-$pointerstart) ==  
                   array_slice($arr2,$pointerstart,$pointermid-$pointerstart)) {  
                $pointermin = $pointermid;  
                $pointerstart = $pointermin;  
            } else {  
                $pointermax = $pointermid;  
            }  
            $pointermid = (int)(($pointermax - $pointermin) / 2 + $pointermin);  
        }
        return $pointermid;

    }
    
    private function revBinarySearch(array $arr1,array $arr2,int $len1,int $len2,int $min=0): int {
        
//       if($arr1[0] != $arr2[0]) return false;

        $pointermin = $min;  
        $pointermax = min($len1,$len2);  
        $pointermid = $pointermax;  
        $pointerend = 0;

        while ($pointermin < $pointermid) {  
            if (array_slice($arr1,-$pointermid,$pointermid-$pointerend) ==  
                   array_slice($arr2,-$pointermid,$pointermid-$pointerend)) {  
                $pointermin = $pointermid;  
                $pointerend = $pointermin;  
            } else {  
                $pointermax = $pointermid;  
            }  
            $pointermid = (int)(($pointermax - $pointermin) / 2 + $pointermin);  
        }
        return $pointermid;

    }

    private function linearSearch(array $arr1,array $arr2,int $len1,int $len2,int $min = 0): int {
        $max = min($len1,$len2);
        $pre_end = $min;
        for($r=$min;$r<$max;$r++) {
            if($arr1[$r] == $arr2[$r]) {
                if($r == $max-1) $pre_end = $r+1;
            }
            else {
                $pre_end = $r;
                break;
            }
        }
        return $pre_end;
    }
    
    private function revLinearSearch(array $arr1,array $arr2,int $len1,int $len2,int $min=1): int {
        $post_start = 0;
        $max = min($len1,$len2);
        for($r=$min;$r<=$max;$r++) {
            if($arr1[$len1-$r] == $arr2[$len2-$r]) {
                $post_start = $r;
            }
            else
                break;
        }
        return $post_start;
    }
/*    
    private function compareSlice($arr1,$arr2,$pre_end=false,$post_start=false) {
        if(!$pre_end) {
            $arr2 = array_slice($arr2,0,-$post_start);
            if($arr2 == []) return false;
        }
        else {
            $arr2 = array_slice($arr2,$pre_end);
            if($arr2[0] == ' ') array_shift($arr2); // for "...nayenātmādiśabdānāṃ" and "...nayena ātmādiśabdānāṃ"; we want the variant to be "na ātmādiśabdānāṃ" not " ātmādiśabdānāṃ"
        }
        
        $arrcount = count($arr2);
        
        if(!$pre_end) $arr1 = array_slice($arr1,0,$arrcount);
        else $arr1 = array_slice($arr1,-$arrcount);
        return(implode('',$arr1) === implode('',$arr2));
    }
    
    private function findPrefix($text1,$text2,$min) {
        $arr1 = $this->aksaraSplit($text1);
        $arr2 = $this->aksaraSplit($text2);
        $len1 = count($arr1);
        $len2 = count($arr2);

        $pre_end = $this->linearSearch($arr1,$arr2,$len1,$len2,$min);
        if(($len2 - $pre_end > 1) && $this->compareSlice($arr1,$arr2,$pre_end))
            $pre_end--;
        return [$arr1,$arr2,$len1,$len2,$pre_end];
    }
*/
/*
    private function compareSlice($text1,$arr2,$pre_end=false,$post_start=false) {
        if($post_start === 0) return false;
        if(!$pre_end) {
            $arr2 = array_slice($arr2,0,-$post_start);
            //if($arr2 == []) return false;
        }
        else {
            $arr2 = array_slice($arr2,$pre_end);
            //if($arr2[0] == ' ') array_shift($arr2); // for "...nayenātmādiśabdānāṃ" and "...nayena ātmādiśabdānāṃ"; we want the variant to be "na ātmādiśabdānāṃ" not " ātmādiśabdānāṃ"
        }
        $text2 = implode('',$arr2);
        $count2 = strlen($text2);
        
        if(!$pre_end) $text1 = substr($text1,0,$count2);
        else $text1 = substr($text1,-$count2);
        if($pre_end) echo "PRE::";
        if($post_start) echo "POST::";
        echo "$text1::$text2\n\n";
        if($text1 === $text2) {
            return true;
        }
        else return false;
    }
    */
    private function compareSlicePre(string $text1,array $arr2,int $pre_end): int {
        $adjustment = -1;
        do {
            $vararr = array_slice($arr2,$pre_end);
            $vartxt = implode('',$vararr);
            $varcount = strlen($vartxt);
            if($pre_end === 0) break;
            $maintxt = substr($text1,-$varcount);
            $adjustment++;
            $pre_end--;
        } while($maintxt === $vartxt);

        return $adjustment;
    }
    private function compareSlicePost(string $text1,array $arr2,int $post_start): int {
        if($post_start === 0) return 0;
        $adjustment = -1;
        do {
            $vararr = array_slice($arr2,0,-$post_start);
            $vartxt = implode('',$vararr);
            $count2 = strlen($vartxt);
            if($count2 === 0) break;
            $maintxt = substr($text1,0,$count2);

            $adjustment++;
            $post_start--;
        } while ($maintxt === $vartxt);

        return $adjustment;
    }

    private function findPrefix(string $text1,array $arr1,array $arr2,int $len1,int $len2,int $min): int {

        $pre_end = $this->binarySearch($arr1,$arr2,$len1,$len2,$min);
        /*
        switch($len1 - $pre_end)  {
            case 0:
                $pre_end = $pre_end -2;
                break;
            case 1:
                if($arr1[$len1-1] == ' ') 
                    $pre_end--;
                break;
        }*/
        if($this->longerAffixes) {
            // make variant a little longer for more context
            if($pre_end > 0 && $len1 > $pre_end && $len2 > $pre_end &&
               $arr2[$pre_end-1] != ' ')
                $pre_end--;
        }
        if($len2 - $pre_end > 1) {
           // if($arr2[$pre_end] == ' ') $pre_end--;
           // else 
                if($pre_end > $this->minaffixlength || 
                   ($pre_end > 1 && $arr1[$pre_end-1] == ' ')) 
                    $pre_end = $pre_end - $this->compareSlicePre($text1,$arr2,$pre_end);
        }
        return $pre_end;
    }

    private function findSuffix(string $text1,array $arr1,array $arr2,int $len1,int $len2,int $min): int {

        $post_start = $this->revBinarySearch($arr1,$arr2,$len1,$len2,$min);
        //if($len1 - $post_start == 0) 
        //    $post_start--;
        if($this->longerAffixes) {
            if($post_start > 0 && $len1 > $post_start && $len2 > $post_start &&
           $arr2[$len2-$post_start-1] != ' ')
                $post_start--;
        }
        if($len2-$post_start > 0) {
            if($len1 - $post_start == 0 && $arr2[$len2-$post_start-1] != ' ')
                $post_start--;
            if($arr2[$len2-$post_start-1] == ' ' && $len1 > $post_start) 
                $post_start--;
            else if($post_start > $this->minaffixlength || 
                 ($len1 > $post_start && $arr1[$len1-$post_start-1] == ' '))
                $post_start = $post_start - $this->compareSlicePost($text1,$arr2,$post_start);
        }
        return $post_start;
    }
   
/*    private function findPrefix($text1,$text2,$min) {

        $arr1 = preg_split('//u',$text1,-1,PREG_SPLIT_NO_EMPTY);
        $arr2 = preg_split('//u',$text2,-1,PREG_SPLIT_NO_EMPTY);
        $len1 = count($arr1);
        $len2 = count($arr2);

        $pre_end = $this->linearSearch($arr1,$arr2,$len1,$len2,$min);

        // first half should end in vowel, second half should start with consonant
        $vowels = '/[aāiīeuūoṛṝḷṃḥ\s]/u';
        $consonants = '/[kgcjṭḍtdpbṅñṇnmyrlḻvśṣsh\s]/u';
       
        // if maintext is empty or a space, need to shorten the prefix so that there's a lemma to associate with the vartext
        switch($len1 - $pre_end)  {
            case 0:
                $pre_end = $pre_end -2;
                break;
            case 1:
                if($arr1[$len1-1] == ' ') 
                    $pre_end--;
                break;
        }
        while($pre_end > 0) {
            if(!preg_match($vowels,$arr1[$pre_end-1]))
                $pre_end--;
//            elseif(!preg_match($vowels,$arr2[$pre_end-1]))
//                $pre_end--;
            elseif(isset($arr1[$pre_end]) &&
                   !preg_match($consonants,$arr1[$pre_end]))
                $pre_end--;
            elseif(isset($arr2[$pre_end]) &&
               !preg_match($consonants,$arr2[$pre_end]))
                $pre_end--;
            elseif(($len2 - $pre_end > 1) && $this->compareSlice($arr1,$arr2,$pre_end))
                $pre_end--;
            else break;
        }

        //if($pre_end == $len2-1) return false;
        return [$arr1,$arr2,$len1,$len2,$pre_end];
    }

*/
/*
    private function findSuffix($arr1,$arr2,$len1,$len2,$min,$pre_end) {
        $post_start = $this->revLinearSearch($arr1,$arr2,$len1,$len2,$min);
        if($len1 - $post_start == 0) $post_start--;
        
        if($post_start > $pre_end &&
            (isset($arr1[$len1-$post_start-1]) && $arr1[$len1-$post_start-1] != ' ') ||
            (isset($arr2[$len2-$post_start-1]) && $arr2[$len2-$post_start-1] != ' ')) {
            $vowels = '/[aāiīeuūoṛṝḷṃḥ\s]/u';
            $consonants = '/[kgcjṭḍtdpbṅñṇnmyrlḻvśṣsh\s]/u';
            while($post_start > 0) {
                if(isset($arr1[$len1-$post_start-1]) && !preg_match($vowels,$arr1[$len1-$post_start-1]))
                    $post_start--;
                elseif(isset($arr2[$len2-$post_start-1]) && !preg_match($vowels,$arr2[$len2-$post_start-1]))
                    $post_start--;
                elseif(isset($arr1[$len1-$post_start]) &&
                       !preg_match($consonants,$arr1[$len1-$post_start]))
                    $post_start--;
                elseif(isset($arr2[$len2-$post_start]) &&
                   !preg_match($consonants,$arr2[$len2-$post_start]))
                    $post_start--;
                elseif($this->compareSlice($arr1,$arr2,false,$post_start))
                    $post_start--;
                else break;
            }
        }
        return $post_start; */
/*
            $rev1 = array_reverse($arr1);
            $rev2 = array_reverse($arr2);
            $post_start = $this->linearSearch($rev1,$rev2,$len1,$len2);
            if($post_start > $pre_end &&
               (isset($rev2[$post_start]) && $rev2[$post_start] != ' ') ||
               (isset($rev1[$post_start]) && $rev1[$post_start] != ' ')) {
                while($post_start > 0) {
                    if(!preg_match($consonants,$rev2[$post_start-1]))
                        $post_start--;
                    elseif(isset($rev2[$post_start]) &&
                       !preg_match($vowels,$rev2[$post_start]))
                        $post_start--;
                    elseif(isset($rev1[$post_start]) &&
                       !preg_match($vowels,$rev1[$post_start]))
                        $post_start--;
                    else break;
                }
            } */
            //if($post_start == $len2) return false;

//    }

    public function aksaraSplit(string $str): array {
        $arr = preg_split('//u',$str,-1,PREG_SPLIT_NO_EMPTY);
        $was_vowel = false;
//        $was_consonant = false;
        $was_other = false;
        $aksaras = [];

        $vowels = '/[aāiīíeéuúūoóṛṝḷṃṁḥ]/u';
        $consonants = '/[kgcjṭḍtdpbṅñṇnmyrŕlḻvśṣsh]/u';
       
        foreach($arr as $a) {

            if(preg_match($vowels,$a)) {
                if(empty($aksaras) || $was_other)
                    $aksaras[] = $a;
                else 
                    $aksaras[] = array_pop($aksaras) . $a;

                $was_vowel = true;
//                $was_consonant = false;
                $was_other = false;
            }
            elseif(preg_match($consonants,$a)) {
                if(empty($aksaras) || $was_vowel || $was_other)
                    $aksaras[] = $a;
                else
                    $aksaras[] = array_pop($aksaras) . $a;
                $was_vowel = false;
//                $was_consonant = true;
                $was_other = false;

            }
            else {
                $aksaras[] = $a;
                $was_vowel = false;
//                $was_consonant = false;
                $was_other = true;
            }
        }
        return $aksaras;
    }
    
    private function findAffixes(string $text1,string $text2/*,array $diffs*/): ?array {
//        if($text1 = '' || $text2 = '') return false;
        $arr1 = $this->aksaraSplit($text1);
        $arr2 = $this->aksaraSplit($text2);
        $len1 = count($arr1); 
        $len2 = count($arr2);
        if($text1[0] != $text2[0])
            $pre_end = 0;
        else {
            //$min = $diffs[0][0] == 0 ? mb_strlen($diffs[0][1]) : 0;
            //$min = $diffs[0][0] == 0 ? count($this->aksaraSplit($diffs[0][1])) : 0;
            $min = 0;
            
            $pre_end = $this->findPrefix($text1,$arr1,$arr2,$len1,$len2,$min);
        }
        if($text1[strlen($text1)-1] != $text2[strlen($text2)-1])
              $post_start = 0;
          
        elseif($pre_end == 0 || 2*$pre_end < $len1+$len2) {
// prefix is less than half the length of the main text or variant text; try computing the suffix

//            $lastdiff = $diffs[count($diffs)-1];
//            $min = $lastdiff[0] == 0 ? mb_strlen($lastdiff[1]) : 1;
            //$min = $lastdiff[0] == 0 ? count($this->aksaraSplit($lastdiff[1])) : 1;
            $min = 0; // last character not always a space (i.e., at end of line)
            $post_start = $this->findSuffix($text1,$arr1,$arr2,$len1,$len2,$min);
        }

        if($pre_end >= $post_start &&
                ($pre_end > $this->minaffixlength || 
        /*        ($pre_end > 1 && $arr1[$pre_end-1] == ' ') ||
                ($pre_end == 1 && count($arr1) > 1 && $arr1[$pre_end] == ' ') ||
                ($pre_end > 1 && count($arr2) > 2 && $arr2[$pre_end] == ' ')
          */
                (array_key_exists($pre_end-1,$arr1) && $arr1[$pre_end-1] == ' ') ||
                (array_key_exists($pre_end,$arr1) && $arr1[$pre_end] == ' ') ||
                (array_key_exists($pre_end,$arr2) && $arr2[$pre_end] == ' ')
                ) 
            ) {
         //   $prefix = mb_substr($text2,0,$pre_end);
         //   $var = mb_substr($text2,$pre_end);
         //   $main = mb_substr($text1,$pre_end);
            $prefix = implode('',array_slice($arr2,0,$pre_end));
            $var = implode('',array_slice($arr2,$pre_end));
            $main = implode('',array_slice($arr1,$pre_end));
            
            return array("var" => $var,
                         "prefix" => $prefix,
                         "main" => $main,
                         );
        } 
        if($post_start > $this->minaffixlength || 
                (array_key_exists($len1-$post_start-1,$arr1) && $arr1[$len1-$post_start-1] == ' ') ||
                (array_key_exists($len2-$post_start-1,$arr2) && $arr2[$len2-$post_start-1] == ' ')
          ) {
       //     $var = mb_substr($text2,0,$len2-$post_start);
       //     $suffix = mb_substr($text2,$len2-$post_start);
       //     $main = mb_substr($text1,0,$len1-$post_start);
            $var = implode('',array_slice($arr2,0,$len2-$post_start));
            $suffix = implode('',array_slice($arr2,$len2-$post_start));
            $main = implode('',array_slice($arr1,0,$len1-$post_start));

            return array("var" => $var,
                         "suffix" => $suffix,
                         "main" => $main,
                         );
        } 
        else return null;
        
    }  
/*
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
*/
}

