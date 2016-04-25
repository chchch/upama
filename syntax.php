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
                $meta = array();
                $meta['plugin_upama'] = array();
                global $INPUT;
                $INFO = pageinfo();
                global $conf;
                $cachedir = $conf['cachedir']."/upama";
                if(!file_exists($cachedir)) mkdir($cachedir);
                $witnesses = $INPUT->post->arr('upama_witnesses');
                $tagfilters = $INPUT->post->arr('upama_tagfilters');
                $hidefilters = $INPUT->post->arr('upama_hidefilters');
                $subfilters = $INPUT->post->arr('upama_subfilters');

                $xsltproc = new XsltProcessor();
                $xsltproc->registerPHPFunctions();
                $xsl = new DomDocument;
                $upama = new Upama();
                list($xml,$xpath) = $upama->loadText($match);
                
                // render the TEI header
                $xsl->load(DOKU_PLUGIN. 'upama/xslt/teiheader.xsl');
                $xsltproc->importStyleSheet($xsl);
                $data = $xsltproc->transformToXML($xml);

                // Set metadata to be rendered later
                $meta['shorttitle'] = $upama->getSiglum($xpath) ?: NULL;
                $meta['title'] = $upama->getTitle($xpath) ?: NULL;
                //$meta['plugin_upama']['witnesses'] = $witnesses ? $witnesses : array();
                $cached_comparisons = p_get_metadata($INFO['id'],'plugin_upama',false)['cached'] ?: array();
                $meta['plugin_upama']['cached'] = $cached_comparisons;
                $meta['plugin_upama']['current'] = 
                    array( 'witnesses' => $witnesses,
                          'tagfilters' => $tagfilters,
                          'hidefilters' => $hidefilters,
                          'subfilters' => $subfilters,
                        );

                if($witnesses) {
                    if(!checkSecurityToken()) die();
                    
                    $tagfiltersall = $upama->getTagFilters();
                    $hidefiltersall = $upama->getHideFilters();
                    $subfiltersall = $upama->getSubFilters();

                    $tagfiltersdiff = array();
                    foreach($tagfilters as $tag => $status) {
                        if($tagfiltersall[$tag] != $status)
                            $tagfiltersdiff[$tag] = $status;
                    }
                    /*$hidefiltersoff = array();
                    foreach($hidefiltersall as $name => $regex) {
                        if(!in_array($name,$hidefilters))
                            $hidefiltersoff[] = $name;
                    }
                    $subfiltersoff = array();
                    foreach($subfiltersall as $name => $regex) {
                        if(!in_array($name,$subfilters))
                            $subfiltersoff[] = $name;
                    }
                    */
                    $hidefiltersoff = array_diff(array_keys($hidefiltersall),$hidefilters);
                    $subfiltersoff = array_diff(array_keys($subfiltersall),$subfilters);

                   $compared = array();
                   
                   foreach($witnesses as $witness) {
                       $upama = new Upama();
                       $usecache = FALSE;
                       $thisfile = $INFO['filepath'];
                       $thatfile = dirname($thisfile)."/".$witness;
                       $cachefilename = '';
                       $cachefileindex = NULL;

                       if(array_key_exists($witness,$cached_comparisons)) {
                           /** use the cached copy of a particular comparison
                               if 1) all the filter settings are the same
                                  2) the file exists
                                  3) the file is newer than the root text file
                                  4) the file is newer than the witness text file
                            **/
                            foreach($cached_comparisons[$witness] as $index => $file) {
                                if($file['tagfilters'] == $tagfiltersdiff &&
                                   $file['hidefilters'] == $hidefiltersoff &&
                                   $file['subfilters'] == $subfiltersoff) {
                                        
                                        $cachefilename = $file['filename'];
                                        $cachefileindex = $index;
                                }
                            }
                            
                            if(file_exists($cachefilename)) {
                               $cachetime = filemtime($cachefilename);
                               if($cachetime > filemtime($thisfile) && $cachetime > filemtime($thatfile)) $usecache = TRUE;
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
                       }
                       else {
                            foreach($tagfiltersdiff as $tag => $status)
                                $upama->setFilter("tag",$tag,$status);
                            foreach($hidefiltersoff as $tag)
                                $upama->removeFilter("hidetext",$tag);
                            foreach($subfiltersoff as $tag)
                                $upama->removeFilter("subtext",$tag);

                            $comparison = $upama->compare($thisfile,$thatfile);
                            $compared[] = $comparison;
                            $newcachefilename = $cachedir."/".uniqid().".xml";
                            $cachefile = fopen($newcachefilename, "w");
                            fwrite($cachefile,$comparison);
                            fclose($cachefile);
                            $meta['plugin_upama']['cached'][$witness][] = 
                                array('filename' => $newcachefilename,
                                      'tagfilters' => $tagfiltersdiff,
                                      'hidefilters' => $hidefiltersoff,
                                      'subfilters' => $subfiltersoff,
                                    );
                       }
                   }
                   if(count($compared) > 1) { // collate comparisons if thare are more than one
                       $upama = new Upama();
                       $final = $upama->collate($compared);
                       $data .= $upama->transform($final,DOKU_PLUGIN.'upama/xslt/with_apparatus.xsl');
                   }
                   else {
                        $upama = new Upama();
                        $data .= $upama->transform($compared[0],DOKU_PLUGIN.'upama/xslt/with_apparatus.xsl');
                   }
               } else { // if there are no witnesses to compare
                   $xsl->load(DOKU_PLUGIN. 'upama/xslt/no_apparatus.xsl');
                   $xsltproc->importStyleSheet($xsl);
                   $data .= $xsltproc->transformToXML($xml);
               }

                if (!$data) {
                    $errors = libxml_get_errors();
                    foreach ($errors as $error) {
                        $data = $this->display_xml_error($error, $xml);
                    }
                    libxml_clear_errors();
                }                

                unset($xsltproc);
                return array($state, $data, $meta);
 
          case DOKU_LEXER_UNMATCHED :  return array($state, $match, '');
          case DOKU_LEXER_EXIT :       return array($state, '', '');
        }
        return array();
    }
    
    function render($mode, Doku_renderer $renderer, $data) {
         if($mode == 'xhtml'){
            list($state, $match, $meta) = $data;
            switch ($state) {
              case DOKU_LEXER_SPECIAL :      
                $renderer->doc .= $match;
                break;
 
              case DOKU_LEXER_UNMATCHED :  $renderer->doc .= $renderer->_xmlEntities($match); break;
              case DOKU_LEXER_EXIT :       $renderer->doc .= ""; break;
            }
            return true;
        }
        elseif($mode == 'metadata') {
            list($state, $match, $meta) = $data;
            if($state == DOKU_LEXER_SPECIAL) {
                foreach($meta as $key => $value)
                    $renderer->meta[$key] = $value;
            }

            return true;
        }
        return false;
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
