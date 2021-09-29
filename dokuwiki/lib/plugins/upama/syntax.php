<?php
/**
 * Plugin Upama : Compares TEI XML files and generates an apparatus with variants
 * 
 * To be run with Dokuwiki only
 *
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Charles Li <cchl2@cam.ac.uk>
 */
 
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_PLUGIN.'upama/upama.php');
class syntax_plugin_upama extends DokuWiki_Syntax_Plugin {
 
    function getInfo(){
      return array(
        'author' => 'Charles Li',
        'email'  => 'cchl2@cam.ac.uk',
        'date'   => '2015-06-01',
        'name'   => 'Upama Plugin',
        'desc'   => 'Compares TEI XML files and generates an apparatus with variants',
        'url'    => 'http://saktumiva.org/',
      );
    }
 
    function getType() { return 'substition'; }
    function getPType() { return 'block'; }
    function getSort() { return 1242; }
    function connectTo($mode) { $this->Lexer->addSpecialPattern('<TEI\b.*?>.*</TEI>',$mode,'plugin_upama'); }

    function handle($match, $state, $pos, Doku_Handler $handler)
    { 
        switch ($state) {
          case DOKU_LEXER_SPECIAL :
                $data = '';
                global $INPUT;
                $INFO = pageinfo();
                global $conf;
                $upama = new Upama();

                $oldmeta = p_get_metadata($INFO['id'],'plugin_upama',false);
                $meta = array();
                $meta['plugin_upama'] = array();
                
                $cachedir = $conf['cachedir']."/upama/";
                if(!file_exists($cachedir)) mkdir($cachedir);

                $tagfiltersdiff = array();
                $hidefiltersoff = array();
                $subfiltersoff = array();


                $versions = isset($oldmeta['versions']) ? $oldmeta['versions'] : array();
                $version = false;

                $witnesses = $INPUT->post->arr('upama_witnesses');
                $tagfilters = $INPUT->post->arr('upama_tagfilters');
                $hidefilters = $INPUT->post->arr('upama_hidefilters');
                $subfilters = $INPUT->post->arr('upama_subfilters');

                if(!$witnesses) { // no POST data, check GET data
                    $version = $INPUT->get->str('upama_ver');
                    if($version && isset($versions[$version])) {
                        $witnesses = $versions[$version]['witnesses'];
                        $tagfiltersdiff = $versions[$version]['tagfilters'];
                        $hidefiltersoff = $versions[$version]['hidefilters'];
                        $subfiltersoff = $versions[$version]['subfilters'];
                    }
                }
                else {
                    if(!checkSecurityToken()) die(); // POST data isn't valid

                    $tagfiltersall = $upama->getTagFilters();
                    $hidefiltersall = $upama->getHideFilters();
                    $subfiltersall = $upama->getSubFilters();

                    foreach($tagfilters as $tag => $status) {
                        if($tagfiltersall[$tag] != $status)
                            $tagfiltersdiff[$tag] = $status;
                    }
                    $hidefiltersoff = array_diff(array_keys($hidefiltersall),$hidefilters);
                    $subfiltersoff = array_diff(array_keys($subfiltersall),$subfilters);

                }

                $settings = 
                    array( 'witnesses' => $witnesses,
                          'tagfilters' => $tagfiltersdiff,
                          'hidefilters' => $hidefiltersoff,
                          'subfilters' => $subfiltersoff,
                        );

                if($witnesses && !$version) {
                    // if using POST data, check for existing cached version
                    $version = array_search($settings,$versions);

                    // make a new version number if none exists
                    if($version == false) {
                       $version = base_convert(uniqid(''),16,36);
                       $versions[$version] = $settings;
                    }
                }

                $cached_comparisons = isset($oldmeta['cached']) ? $oldmeta['cached'] : array();

                 
                // Set metadata to be rendered later
                $meta['plugin_upama']['versions'] = $versions;
                $meta['plugin_upama']['cached'] = $cached_comparisons;
                //$meta['plugin_upama']['current'] = false;  

                $loaded = $upama->loadText($match);
                if(!is_array($loaded)) {
                    $data = $loaded;
                    return array($state, $data, $meta);
                }
                
                list($xml,$xpath) = $loaded;
                $siglum = $upama->getSiglum($xpath);
                $meta['shorttitle'] = $siglum ? $upama->DOMinnerXML($siglum) : NULL;
                $meta['title'] = $upama->getTitle($xpath) ?: NULL;
                
                // render TEI header
                $data = $this->renderXML($xml, DOKU_PLUGIN. 'upama/xslt/teiheader.xsl');
                // add version number
                $data .= '<div id="__upama_ver">'.$version.'</div>';
                
                $compared = array();
                $thisfile = $INFO['filepath'];
                $thisdir = dirname($thisfile) . "/";
                $otherfiles = array();
                
                $keepgoing = true;
                if($version) {
                    $cachefilename = $cachedir . $version . ".xml";
                    $otherfiles[] = $thisfile;
                    foreach($witnesses as $wit)
                        $otherfiles[] = $thisdir . $wit;
                    if($this->checkCache($cachefilename,$otherfiles)) {
                        $cache = fopen($cachefilename, "r");
                        $str = fread($cache,filesize($cachefilename));
                        fclose($cache);
                        $data .= $upama->transform($str,DOKU_PLUGIN.'upama/xslt/with_apparatus.xsl');
                        $keepgoing = false;
                    }
                }

                if($witnesses && $keepgoing) {
                    
                   foreach($witnesses as $witness) {
                       $upama = new Upama();
                       $usecache = FALSE;
                       $thatfile = $thisdir . $witness;
                       $cachefilename = '';
                       $cachefileindex = NULL;
                       $additionalcache = NULL;

                       if(array_key_exists($witness,$cached_comparisons)) {
                           /** use the cached copy of a particular comparison
                               if 1) all the filter settings are the same
                                  2) the file exists
                                  3) the file is newer than the root text file
                                  4) the file is newer than the witness text file
                            **/
                            foreach($cached_comparisons[$witness] as $index => $file) {
                                if(!file_exists($file['filename'])) {
                                    unset($meta['plugin_upama']['cached'][$witness][$index]); 
                                }
                                else if($file['tagfilters'] == $tagfiltersdiff &&
                                   $file['hidefilters'] == $hidefiltersoff &&
                                   $file['subfilters'] == $subfiltersoff) {
                                        
                                        $cachefilename = $file['filename'];
                                        $cachefileindex = $index;
                                        if(array_key_exists('additional',$file)) {
                                            $additionalcache = $file['additional'];
                                        }
                                }
                            }
                           if($cachefilename) {
                               if($this->checkCache($cachefilename,array($thisfile,$thatfile)))
                                    $usecache = TRUE;
                               else {
                                    unlink($cachefilename);
                                    unset($meta['plugin_upama']['cached'][$witness][$cachefileindex]); 
                               }
                           }
                       }

                       if($usecache) {
                            $cache = fopen($cachefilename, "r");
                            $compared[] = fread($cache,filesize($cachefilename));
                            fclose($cache);
                            if($additionalcache) {
                                foreach($additionalcache as $a) {
                                    $aa = fopen($a, 'r');
                                    $compared[] = fread($aa,filesize($a));
                                    fclose($aa);
                                }
                            }
                       }
                       else {
                            foreach($tagfiltersdiff as $tag => $status)
                                $upama->setFilter("tag",$tag,$status);
                            foreach($hidefiltersoff as $tag)
                                $upama->removeFilter("hidetext",$tag);
                            foreach($subfiltersoff as $tag)
                                $upama->removeFilter("subtext",$tag);

                            $separator = ($conf['useslash'] == 1) ? "/" : ":";
                            $namespace = ($separator == '/') ?
                                str_replace(":","/",$INFO['namespace']) :
                                $INFO['namespace'];

                            $pageid = $namespace.$separator.basename($thatfile,'.txt');
                            $basedir = $conf['basedir'] ? $conf['basedir'] : getBaseURL();

                            switch($conf['userewrite']) {

                                case 0: 
                                    $url = $basedir."doku.php?id=".$pageid;
                                    break;
                                case 1:
                                    $url = $basedir."$pageid";
                                    break;
                                case 2:
                                    $url = $basedir."doku.php/$pageid";
                                    break;
                            }
                            
                            try {
                                $comparison = $upama->compare($thisfile,$thatfile,$url);
                            } catch (Exception $e) {
                                $data .= $e->getMessage();
                                return array($state, $data, $meta);
                            }
                            
                            if(is_array($comparison)) { // returns array when there are inline <app>s
                                
                                list($basecomp,$others) = $comparison;

                                $newcachefilename = $this->writeCacheFile($basecomp,$cachedir);
                                $compared[] = $basecomp;

                                $otherfilenames = [];
                                foreach($others as $other) {
                                    $othercomp = $upama->compareFileStr($thisfile,$other,$url);
                                    $compared[] = $othercomp;
                                    $otherfilename = $this->writeCacheFile($othercomp,$cachedir);
                                    $otherfilenames[] = $otherfilename;
                                }

                                $meta['plugin_upama']['cached'][$witness][] =
                                    array('filename' => $newcachefilename,
                                          'additional' => $otherfilenames,
                                          'tagfilters' => $tagfiltersdiff,
                                          'hidefilters' => $hidefiltersoff,
                                          'subfilters' => $subfiltersoff
                                          );

                            }
                            else {
                                $newcachefilename = $this->writeCacheFile($comparison,$cachedir);
                                $compared[] = $comparison;
                                $meta['plugin_upama']['cached'][$witness][] = 
                                    array('filename' => $newcachefilename,
                                          'tagfilters' => $tagfiltersdiff,
                                          'hidefilters' => $hidefiltersoff,
                                          'subfilters' => $subfiltersoff,
                                        );
                            }
                       }
                   }
                   
                   $upama = new Upama();
                   foreach($tagfiltersdiff as $tag => $status)
                       $upama->setFilter("tag",$tag,$status);
                   foreach($hidefiltersoff as $tag)
                       $upama->removeFilter("hidetext",$tag);
                   foreach($subfiltersoff as $tag)
                       $upama->removeFilter("subtext",$tag);
$final = '';
                   if(count($compared) > 1) // collate comparisons if thare are more than one
                       $final = $upama->collate($compared);
                   else
                       $final = $compared[0];

                   $data .= $upama->transform($final,DOKU_PLUGIN.'upama/xslt/with_apparatus.xsl');
                    
                 // FIXME?: saves a cache file even if no collation is done (i.e., there was only one witness compared)
                   $newcachefilename = $cachedir . $version . ".xml";
                   $cachefile = fopen($newcachefilename, "w");
                   fwrite($cachefile,$final);
                   fclose($cachefile);

               //    $meta['plugin_upama']['current'] = $version;

               } // end if($witnesses) 
               
               else if(!$witnesses) { // if there are no witnesses to compare
                    if($xpath->query('//x:div2[@type="apparatus"]')->length > 0 ||
                       $xpath->query('//x:ab[@type="apparatus"]')->length > 0 ||
                       $xpath->query('//x:app')->length > 0)
                       $data .= $this->renderXML($xml,DOKU_PLUGIN. 'upama/xslt/with_apparatus2.xsl');
                   else
                       $data .= $this->renderXML($xml,DOKU_PLUGIN. 'upama/xslt/no_apparatus.xsl');
               //    $meta['plugin_upama']['current'] = 0;
               }

                if (!$data) {
                    $errors = libxml_get_errors();
                    foreach ($errors as $error) {
                        $data = $this->display_xml_error($error, $xml);
                    }
                    libxml_clear_errors();
                }                

                $this->xml_parser($match);

                return array($state, $data, $meta, $this->xml_posarray);
 
          case DOKU_LEXER_UNMATCHED :  return array($state, $match, '');
          case DOKU_LEXER_EXIT :       return array($state, '', '');
        }
        return array();
    }

    function writeCacheFile($str,$cachedir) {
        $fname = $cachedir . base_convert(uniqid(''),16,36).".xml";
        $cachefile = fopen($fname, "w");
        fwrite($cachefile,$str);
        fclose($cachefile);
        return $fname;
    }

    function renderXML($xml,$xslt) {
        
        $xsltproc = new XsltProcessor();
        $xsltproc->registerPHPFunctions();
        $xsl = new DomDocument;
        
        // render the TEI header
        $xsl->load($xslt);
        $xsltproc->importStyleSheet($xsl);
        $return = $xsltproc->transformToXML($xml);
        
        unset($xsltproc);
        return $return;
    }
    
    function checkCache($cachefile,$otherfiles) {
        if(!file_exists($cachefile)) return false;
        $cachetime = filemtime($cachefile);
        if(!$cachetime) return false;
        foreach($otherfiles as $file) {
            if(!file_exists($file)) return false;
            $filetime = filemtime($file);
            if($cachetime < $filetime)
                return false;
        }
        return true;
    }
    
    function render($mode, Doku_renderer $renderer, $data) {
         if($mode == 'xhtml'){
            list($state, $match, $meta, $bytepos) = $data;
            switch ($state) {
              case DOKU_LEXER_SPECIAL :      
                
                if(!method_exists($renderer, 'startSectionEdit')) {
                    $renderer->doc .= $match;
                    break;
                }

                $matches = explode("<!--SECTION_START-->",$match);
                if(count($matches) == 1) {
                    $renderer->doc .= $match;
                    break;
                }
                foreach($matches as $section) {
                    // check if the section id is there
                    /*$this->sec_id = '';
                    $section = preg_replace_callback('/^<!--(.+?)=UPAMA_SECTION-->/',function($preg_matches) {
                        $this->sec_id = $preg_matches[1];
                        return '';
                    },
                    $section);
                    if($this->sec_id && isset($bytepos[$this->sec_id])) {
                        $thispos = $bytepos[$this->sec_id];
                    */
                    $secsplit = explode("=UPAMA_SECTION-->",$section);
                    $sec_id_lang = substr($secsplit[0],4);
                    list($sec_id,$sec_lang) = explode("=",$sec_id_lang);

                    if(count($secsplit) > 1 && isset($bytepos[$sec_id])) {
                        $section = $secsplit[1];
                        $thispos = $bytepos[$sec_id];
                        $halves = explode("<!--SECTION_END-->",$section);
                        
                        if(method_exists($renderer, 'startSectionEdit')) {
                            if(!defined('SEC_EDIT_PATTERN'))
                                // backwards compatibility for Frusterick Manners (2017-02-19)
                                $sec_class = $renderer->startSectionEdit($thispos[0],'plugin_upama','Edit section '.$sec_id);
                            else
                                $sec_class = $renderer->startSectionEdit($thispos[0],['target'=>'plugin_upama','name'=>'Edit section '.$sec_id,'hid'=>null]);
                        }
                        
                        $renderer->doc .= '<div class="'.$sec_class.' sectiontext" lang="'.$sec_lang.'">';
                        $renderer->doc .= $halves[0];
                        $renderer->doc .= '</div>';
                        $renderer->finishSectionEdit($thispos[1]);
                        $renderer->doc .= $halves[1];
                    }
                    else
                        $renderer->doc .= $section;

                }
                break;
 
              case DOKU_LEXER_UNMATCHED :  $renderer->doc .= $renderer->_xmlEntities($match); break;
              case DOKU_LEXER_EXIT :       $renderer->doc .= ""; break;
            }
            return true;
        }
        elseif($mode == 'metadata') {
            list($state, $match, $meta, $bytepos) = $data;
            if($state == DOKU_LEXER_SPECIAL) {
                foreach($meta as $key => $value)
                    $renderer->meta[$key] = $value;
            }
            global $JSINFO;
            //$JSINFO['_upama_current'] = $meta['plugin_upama']['current'];
            $JSINFO['_codemirror_syntax_lang'] = "xml";

            return true;
        }
        return false;
    }

    var $xml_text;
    var $xml_parser;
    var $xml_startpos;
    var $xml_depth;
    var $xml_target_depth;
    var $xml_id;
    var $xml_start = FALSE;
    var $xml_posarray;
 
    function xml_parser($data) {
        $this->xml_parser = xml_parser_create();
        xml_set_object($this->xml_parser, $this);
        xml_set_element_handler($this->xml_parser, "xml_tag_open", "xml_tag_close");
        $this->xml_text = $data;
        $this->xml_posarray = array();
        $this->xml_depth = 0;
        $this->xml_target_depth = 0;
        $this->xml_id = FALSE;
        $this->xml_start = FALSE;
                
        xml_parse($this->xml_parser, $data);
        xml_parser_free($this->xml_parser);
    }
   
    function xml_tag_open($parser, $tag, $attr) {
            $this->xml_depth++;
            if($tag == 'TEXT' && $this->xml_depth == 2) $this->xml_start = TRUE;
            if(!$this->xml_start) return;
            if(array_key_exists('XML:ID', $attr)) {
                $this->xml_target_depth = $this->xml_depth;
                $this->xml_id = $attr['XML:ID'];
                $this->xml_startpos = xml_get_current_byte_index($parser);
            }
    }
    function xml_tag_close($parser, $tag) {
        if($this->xml_id && ($this->xml_depth == $this->xml_target_depth)) {
            $pos = xml_get_current_byte_index($parser);
            // walk back until start of closing tag is found
            $pos = $pos - 3;
            while(substr($this->xml_text,$pos+1,1) != '<') {
                $pos--;
            }
            $this->xml_posarray[$this->xml_id] = array($this->xml_startpos + 2,$pos + 2);
            $this->xml_id = FALSE;        
        }
        $this->xml_depth--;
    }

    function display_xml_error($error, $xml) {
        $return  = $xml[$error->line - 1] . "\n";
        $return .= str_repeat('-', $error->column) . "^\n";

        switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $return .= "Warning $error->code: ";
                break;
             case LIBXML_ERR_ERROR:
                $return .= "Error $error->code: ";
                break;
            case LIBXML_ERR_FATAL:
                $return .= "Fatal Error $error->code: ";
                break;
        }

        $return .= trim($error->message) .
                   "\n  Line: $error->line" .
                   "\n  Column: $error->column";

        if ($error->file) {
            $return .= "\n  File: $error->file";
        }

        return "$return\n\n--------------------------------------------\n\n";
    }

}
?>
