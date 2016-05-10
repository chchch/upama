<?php
require_once(DOKU_PLUGIN . 'upama/upama.php');
$INFO = pageinfo();
global $INPUT;
global $conf;
$curdir = dirname($INFO['filepath']);
$curfile = basename($INFO['filepath']);
$dirfiles = array_diff(scandir($curdir),array('..','.',$curfile));
$meta = p_get_metadata($INFO['id'],'plugin_upama',false);

if($meta && sizeof($dirfiles) > 0) {

    $active = $INPUT->post->arr('upama_witnesses');
    $upama = new Upama();

    $allhidefilters = $upama->getHideFilters();
    $allsubfilters = $upama->getSubFilters();

    if($active) { // check POST data
        
        $tagfilters = $INPUT->post->arr('upama_tagfilters');
        $hidefilters = $INPUT->post->arr('upama_hidefilters');
        $subfilters = $INPUT->post->arr('upama_subfilters');
    
    }
    else { // no POST data

        $version = $meta['current'];
        $tagfilters = $upama->getTagFilters();
        if($INPUT->get->str('upama_ver') && $version) { // check GET data
            $active = $meta['versions'][$version]['witnesses'];
            $tagfiltersdiff = $meta['versions'][$version]['tagfilters'];
            $hidefiltersoff = $meta['versions'][$version]['hidefilters'];
            $subfiltersoff = $meta['versions'][$version]['subfilters'];
            foreach($tagfiltersdiff as $key => $value) {
                $tagfilters[$key] = $value;
            }
            $hidefilters = array_diff(array_keys($allhidefilters),$hidefiltersoff);
            $subfilters = array_diff(array_keys($allsubfilters),$subfiltersoff);
        
        }
        else { // use defaults
    
            $tagfilters = $upama->getTagFilters();
            $hidefilters = array_keys($allhidefilters);
            $subfilters = array_keys($allsubfilters);

        }
    }
    
    $script = $INPUT->post->str('_upama_script') ?: "iast";
    echo '<form id="__upama_form" method="post">';
    echo '<input name="_upama_script" type="hidden" id="__upama_hidden_script_selector" value="'.$script.'">';
    echo '<input type="hidden" name="id" value="'.$INFO['id'].'">';
    formSecurityToken();

    echo '<div id="__upama_witnesses" style="padding:0"><h3 class="upama_menu">Compare with other witnesses</h3>';
    echo '<div style="padding:0"><table class="options">';
    foreach($dirfiles as $file) {
        $pagebase = explode(".",$file)[0];
        $pageid = $INFO['namespace'].":".$pagebase;

        if($conf['userewrite'] == 0) $url = "doku.php?id=".$pageid;
        else $url = "/$pageid";
        
        if(p_get_metadata($pageid,"plugin_upama",false)) {
            $shorttitle = p_get_metadata($pageid,"shorttitle",false);
            if(!$shorttitle) $shorttitle = $pagebase;
            $longtitle = p_get_metadata($pageid,"title",false);
            echo '<tr><td><input type="checkbox" name="upama_witnesses[]"'; 
            
            if(in_array($file,$active))
                echo ' checked';
            
            echo ' value="'.$file . '"></td>'.
            '<td><a class="sidebar-witness" href="'.$url.'"><span class="sidebar-siglum" data-pageid="'.$pageid.'">'.$shorttitle.'</span><br>'.
            //'<div style="padding-bottom:0.5em">('.$longtitle.')</div>';
            '<span>('.$longtitle.')</span></a></td></tr>';
        }
    }
?>
</table></div></div>
<div id="__upama_options_button">Options</div>
<div id="__upama_options">
<h3 class="upama_menu">XML tags</h3>
<div style="padding: 0">
<table class="options">
<?php
    foreach($tagfilters as $tag => $status) {
        echo '<tr><td>';
        if($tag == "#comment") echo "comments";
        else echo '&lt;'.$tag.'&gt;';
        echo '</td>';
        echo '<td><select name="upama_tagfilters['.$tag.']">';
        echo '<option value="1"';
        if($status == 1) echo ' selected';
        echo '>hide</option>';
        echo '<option value="-1"';
        if($status == -1) echo ' selected';
        echo '>ignore all</option>';
        echo '<option value="-2"';
        if($status == -2) echo ' selected';
        echo '>ignore tags only</option>';
        echo '<option value="0"';
        if($status == 0) echo ' selected';
        echo '>include</option>';
        echo '</select></td></tr>';
    }
?>
</table></div>
<h3 class="upama_menu">Punctuation</h3>
<div style="padding: 0">
<table class="options">
<?php
    foreach($allhidefilters as $name => $regex) {
        echo '<tr><td><input type="checkbox" name="upama_hidefilters[]" value="';
        echo $name;
        echo '"';
        if(in_array($name,$hidefilters))
            echo ' checked';
        echo '></td><td><span>ignore '.$name.'</span></td></tr>';
    }
?>
</table></div>
<h3 class="upama_menu">Orthographic variants</h3>
<div style="padding: 0">
<table class="options">
<?php
    foreach($allsubfilters as $name => $regex) {
        echo '<tr><td><input type="checkbox" name="upama_subfilters[]" value="';
        echo $name;
        echo '"';
        if(in_array($name,$subfilters))
            echo ' checked';
        echo '></td><td><span>filter '.$name.'</span></td></tr>';
    }
?>
</table></div>
</div> <!-- _upama_options -->
<div style="padding: 0"><input type="submit" style="width: 100%;font-weight: bold; border-radius: 5px" value="Generate Apparatus"></div>
</form>
<script>
jQuery("#__upama_options").accordion({ collapsible: true, active: false, heightStyle: "content"});
jQuery("#__upama_witnesses").accordion({ collapsible: true, heightStyle: "content"});
</script>
<?php

} // end if($meta)
else echo ""; // What to write when it's not a TEI file
?>
