<?php
 
if(!defined('DOKU_INC')) die();
 
class action_plugin_upama extends DokuWiki_Action_Plugin { 

    public function register(Doku_Event_Handler $controller) {

        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, '_stop_cache');
//        $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, '_add_jsinfo');
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
            global $INPUT;
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
            if($stopcache) {
                $event->preventDefault();
                $event->stopPropagation();
                $event->result = false;
            }
        }
    }

/*    public function _add_jsinfo(&$event, $param) {
        global $JSINFO;
        global $INPUT;
        $JSINFO['_upama_script'] = $INPUT->post->str('_upama_script','iast');
    } */

}
?>
