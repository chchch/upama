<?php
 
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define ('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'upama/upama.php');

class action_plugin_upama extends DokuWiki_Action_Plugin { 

    public function register(Doku_Event_Handler $controller) {

        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, '_stop_cache');
//        $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, '_add_jsinfo');
//        $controller->register_hook('HTML_SECEDIT_BUTTON', 'BEFORE', $this, 'secedit_button');
//        $controller->register_hook('HTML_EDIT_FORMSELECTION', 'BEFORE', $this, 'upama_section_edit');
        $controller->register_hook('EDIT_FORM_ADDTEXTAREA', 'BEFORE', $this, 'upama_section_edit');
        
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'upama_export');
        $controller->register_hook('TEMPLATE_PAGETOOLS_DISPLAY', 'BEFORE', $this, 'upama_addbutton');
    }

/**
     * Handle write action before event
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  empty optional 5th parameter of register_hook()
     * @return void
     */
    public function _stop_cache(&$event, $param) {
        global $ID;
        if(p_get_metadata($ID,'plugin_upama',false)) {
/*            global $INPUT;
            $INFO = pageinfo();
            $meta = p_get_metadata($ID,'plugin_upama',false);
            $newwitnesses = $INPUT->post->arr('upama_witnesses');
            $newtagfilters = $INPUT->post->arr('upama_tagfilters');
            $newhidefilters = $INPUT->post->arr('upama_hidefilters');
            $newsubfilters = $INPUT->post->arr('upama_subfilters');
            $stopcache = false;
            if($newwitnesses != $meta['current']['witnesses'])
                $stopcache = true;

            elseif($newtagfilters != $meta['current']['tagfilters'] ||
                   $newhidefilters != $meta['current']['hidefilters'] ||
                   $newsubfilters != $meta['current']['subfilters'])
                   $stopcache = true;
            else {
             // also check the timestamp of the witness files
                foreach($newwitnesses as &$witness) {
                    $witness = dirname($INFO['filepath'])."/".$witness;
                }
                unset($witness);
                $event->data->depends['files'] = array_merge($event->data->depends['files'],$newwitnesses);
            }
            if($stopcache) { */
                $event->preventDefault();
                $event->stopPropagation();
                $event->result = false;
           // }
        }
    }

/*    public function _add_jsinfo(&$event, $param) {
        global $JSINFO;
        global $INPUT;
        $JSINFO['_upama_script'] = $INPUT->post->str('_upama_script','iast');
    } */

    public function secedit_button(Doku_Event $event) {
        if($event->data['target'] !== 'plugin_upama')
            return;
        $event->data['name'] = $this->getLang('sectioneditname');
    }

    public function upama_section_edit(Doku_Event $event) {
        if($event->data['target'] === 'plugin_upama') {
            // use default editor
            $event->data['target'] = 'section';
            return;
        }
    }
    
    public function upama_addbutton(Doku_Event $event) {
        global $ID;
        global $INPUT;
        global $ACT;
        if($ACT != 'show') return;
        if(!p_get_metadata($ID,'plugin_upama',false)) return;
            
        $selected = $INPUT->post->str('_upama_script');
        $deva_select = '';
        $mala_select = '';
        $telu_select = '';
        $newa_select = '';
        $sarada_select = '';
        $bali_select = '';
        $beng_select = '';
        switch($selected) {
            case 'devanagari':
                $deva_select = ' selected';
                break;
            case 'malayalam':
                $mala_select = ' selected';
                break;
            case 'telugu':
                $telu_select = ' selected';
                break;
            case 'newa':
                $newa_select = ' selected';
                break;
            case 'sarada':
                $sarada_select = ' selected';
                break;
            case 'balinese':
                $bali_select = ' selected';
                break;
            case 'bengali':
                $beng_select = ' selected';
                break;
        };
        $event->data['items'] = array_slice($event->data['items'], 0, 1, true) +
        array('changescript' =>
            '<li class="lazyhide"><a class="action changescript" title="Change display script"><span>'.
            '<select id="__upama_script_selector">' .
                '<option value="iast">IAST</option>' .
                '<option value="devanagari"'.$deva_select.'>Devanāgarī</option>' .
                '<option value="balinese"'.$bali_select.'>Akṣara Bālī</option>' .
                '<option value="bengali"'.$beng_select.'>Bāṅglā</option>' .
                '<option value="malayalam"'.$mala_select.'>Malayālam</option>' .
                '<option value="newa"'.$newa_select.'>Newa</option>' .
                '<option value="sarada"'.$sarada_select.'>Śāradā</option>' .
                '<option value="telugu"'.$telu_select.'>Telugu</option>' .
            '</select></span></a></li>'
            ) +
        array_slice($event->data['items'],1,null,true);
        
        $event->data['items'] = array_slice($event->data['items'], 0, 4, true) + 
        array('upama_export' =>
            '<li class="lazyhide"><a class="action upama_export" title="Export file"><span>'.
            '<select id="__upama_export">'.
                '<option value="default" selected>Export as...</option>'.
                '<option value="tei">TEI XML</option>'.
                '<option value="latex">LaTeX</option>'.
                '<option value="fasta">FASTA</option>'.
                '<option value="fastt">FASTT</option>'.
                '</select></span></a></li>'
                ) +
        array_slice($event->data['items'],4,null,true);
    }

    public function upama_export(Doku_Event $event) {
    
        global $ACT;
        global $ID;
        global $INPUT;
        global $conf;
        $INFO = pageinfo();
        $upama = new Upama();

        if(auth_quickaclcheck($ID) < AUTH_READ)
            return false;

         $meta = p_get_metadata($INFO['id'],'plugin_upama',false);
        if(!$meta)
            return false;

        $ACT = act_clean($ACT);
        if($ACT == 'export_tei') {
            $event->preventDefault();

            $version = $INPUT->get->str('upama_ver');
            $thisfile = $INFO['filepath'];
            $thisdir = dirname($thisfile) . '/';

            header('Content-Type: text/xml');

            header('Content-Disposition: attachment; filename="'.basename($thisfile,".txt").'-'.$version.'.xml"');

            
            if(!$version) {
                readfile($thisfile);   
            }
            else {
                $cachefilename = $conf['cachedir'] . "/upama/" . $version . ".xml";
                readfile($cachefilename);
                
            }
            exit();
        }

        if($ACT == 'export_latex') {
            $event->preventDefault();

            $version = $INPUT->get->str('upama_ver');
            $thisfile = $INFO['filepath'];
            $thisdir = dirname($thisfile) . '/';

            header('Content-Type: application/x-latex');

            header('Content-Disposition: attachment; filename="'.basename($thisfile,".txt").'-'.$version.'.tex"');

            $xsl = DOKU_PLUGIN . "upama/xslt/latex.xsl";
            if(!$version) {
                echo $upama->latex(file_get_contents($thisfile),$xsl);
            }
            else {
                $cachefilename = $conf['cachedir'] . "/upama/" . $version . ".xml";
                echo $upama->latex(file_get_contents($cachefilename),$xsl);
                
            }
            exit();
        }
       
        if($ACT == 'export_fasta') {
        
            $event->preventDefault();
            
            $version = $INPUT->get->str('upama_ver');
            $thisfile = $INFO['filepath'];
            $thisdir = dirname($thisfile) . '/';

            header('Content-Type: text/plain');

            header('Content-Disposition: attachment; filename="'.basename($thisfile,".txt").'-'.$version.'.fas"');

            echo $upama->fasta($thisfile);
            if($version) {
                echo "\n\n";
                foreach ($meta['versions'][$version]['witnesses'] as $file) {
                    echo $upama->fasta($thisdir . $file);
                    echo "\n\n";
                }
            }
            exit();
        }

        if($ACT == 'export_fasta2') {
        
            $event->preventDefault();
            
            $optstr = $INPUT->get->str('export_options');
            $version = $INPUT->get->str('upama_ver');
            $thisfile = $INFO['filepath'];
            $thisdir = dirname($thisfile) . '/';
            $ret = $upama->loadFile($thisfile);
            if(is_array($ret)) list($text,$xpath) = $ret;
            else
                throw new Exception($ret);
                
            $optarr = [
                "normalize" => FALSE,
                ]; 
            $startnode = NULL;
            $endnode = NULL;
            $dozip = FALSE;

            if($optstr) {
                $opts = explode(' ',$optstr);
                foreach($opts as $o) {
                    $os = NULL;
                    if($o === 'option_normalize') {
                        $optarr['normalize'] = TRUE;
                        continue;
                    }
                    if($o === 'option_zip') {
                        $dozip = TRUE;
                        continue;
                    }if(preg_match('/^option_start~/',$o)) {
                        $os = explode('~',$o,2);
                        $startnode = $os[1];
                        continue;
                    }
                    if(preg_match('/^option_end~/',$o)) {
                        $os = explode('~',$o,2);
                        $endnode = $os[1];
                        continue;
                    }
                }
            }
            /*
            $allnodes = $xpath->query("/x:TEI/x:text//*[@xml:id]");
            $selectednodes = [];
            $started = FALSE;
            foreach($allnodes as $n) {
            
                if ($n->getAttribute("type") === "apparatus") continue;

                $name = $n->getAttribute("xml:id");

                if($started || $name === $startnode) {
                    $started = true;
                    $selectednodes[] = $name; 
                    if($name === $endnode) break;
                }
            }*/
            $selectednodes = $upama->getStartEnd($xpath,$startnode,$endnode);


            if(!$dozip) {
                header('Content-Type: text/plain');
                header('Content-Disposition: attachment; filename="'.basename($thisfile,".txt").'-'.$version.'.fas"');
                echo $upama->fasta2($thisfile,$selectednodes,$optarr);
                if($version) {
                    echo "\n\n";
                    foreach ($meta['versions'][$version]['witnesses'] as $file) {
                        echo $upama->fasta2($thisdir . $file,$selectednodes,$optarr);
                        echo "\n\n";
                    }
                }
            }
            else {
                $zipfile = new ZipArchive();
                $zipbasename = basename($thisfile,'.txt').'-'.$version.'.zip';
                $zipfilename = $conf['cachedir'] . "/upama/" . $zipbasename;
                if($zipfile->open($zipfilename, ZipArchive::CREATE) !== TRUE) {
                    exit("cannot open <$zipfilename>\n");
                }
                
                $witnesses = [$thisfile];
                $addwitnesses = $meta['versions'][$version]['witnesses'];
                if($addwitnesses) {
                    foreach($addwitnesses as $add) {
                        $witnesses[] = $thisdir . $add;
                    }
                }

                foreach($selectednodes as $s) {
                    $nodefilename = $s.".fas";
                    $strarr = [];
                    foreach($witnesses as $w) {
                        $strarr[] = $upama->fasta2($w,[$s],$optarr);
                    }
                    $zipfile->addFromString($nodefilename,implode("\n\n",$strarr));
                }
                
                $zipfile->close();

                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="'.$zipbasename.'"');
                header('Content-Length: ' . filesize($zipfilename));
                header('Pragma: no-cache');
                header('Expires: 0');
                ob_clean();
                flush();
                readfile($zipfilename);
                unlink($zipfilename);
            }
            exit();
        }
        return false;
    }

}
?>
