<?php
require_once(DOKU_PLUGIN . 'upama/upama.php');
$INFO = pageinfo();
global $INPUT;
global $conf;
$curdir = dirname($INFO['filepath']);
$curfile = basename($INFO['filepath']);
$dirfiles = array_diff(scandir($curdir),array('..','.',$curfile));
$meta = p_get_metadata($INFO['id'],'plugin_upama',false);

if(sizeof($dirfiles) > 0) {

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

        //$version = $meta['current'];
        $version = $INPUT->get->str('upama_ver');
        $tagfilters = $upama->getTagFilters();
        //if($INPUT->get->str('upama_ver') && $version) { // check GET data
        if($version) {
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
    
            $active = [];
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
    echo '<div style="padding: 0"><input type="submit" id="__upama_generate_button" value="Generate apparatus"';
    if(empty($active)) echo ' disabled title="select witnesses to compare"';
    echo'></div>';
?>
    <ul class="accordion css-accordion">
        <li class="accordion-item" id="__upama_witnesses">
            <input class="accordion-item-input upama_menu" type="checkbox" name="accordion" id="item1" />
            <label for="item1" class="accordion-item-hd">Other witnesses<span class="accordion-item-hd-cta">&#9650;</span></label>
            <div class="accordion-item-bd">
                <table class="options" id="__upama_witness_list">
                    <tr>
                        <td><input name="select_all" value="" onclick="WitnessSelectAll();" type="checkbox"></input></td>
                        <td id="select_all_text">Select all</td></tr> 
<?php
    foreach($dirfiles as $file) {
        //$pagebase = explode(".",$file)[0];
        $pagebase = basename($file,"txt");

        $namespace = ($conf['useslash'] == 1) ? 
            preg_replace('/:/','/',$INFO['namespace']).'/' : 
            $INFO['namespace'].':';

        $pageid = $namespace.$pagebase;

        $url = ($conf['userewrite'] == 0) ? "doku.php?id=".$pageid : "/$pageid";
        
        if(p_get_metadata($pageid,"plugin_upama",false)) {
            $shorttitle = p_get_metadata($pageid,"shorttitle",false);
            if(!$shorttitle) $shorttitle = $pagebase;
            $longtitle = p_get_metadata($pageid,"title",false);
            echo '<tr><td><input type="checkbox" name="upama_witnesses[]"'; 
            
            if(in_array($file,$active))
                echo ' checked';
            
            echo ' value="'.$file . '" onclick="toggleGenButton();"></td>'.
            '<td><a class="sidebar-witness" href="'.$url.'"><span class="sidebar-siglum">'.$shorttitle.'</span><br>'.
            //'<div style="padding-bottom:0.5em">('.$longtitle.')</div>';
            '<span class="witness-longtitle">'.$longtitle.'</span></a></td></tr>';
        }
    }
?>
                </table>
            </div>
        </li>
    </ul>
    
<div id="__upama_options_button">Options</div>
<ul class="accordion" id="__upama_options">
    <li class="accordion-item upama_menu">
        <input class="accordion-item-input upama_menu" type="radio" name="accordion" id="item2" />
        <label for="item2" class="accordion-item-hd">XML tags<span class="accordion-item-hd-cta">&#9650;</span></label>
        <div class="accordion-item-bd">
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
            </table>
        </div>
    </li>
    <li class="accordion-item upama_menu">
        <input class="accordion-item-input upama_menu" type="radio" name="accordion" id="item3" />
        <label for="item3" class="accordion-item-hd">Punctuation<span class="accordion-item-hd-cta">&#9650;</span></label>
        <div class="accordion-item-bd">
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

            </table>
        </div>
    </li>
    <li class="accordion-item upama_menu">
        <input class="accordion-item-input upama_menu" type="radio" name="accordion" id="item4" />
        <label for="item4" class="accordion-item-hd">Orthographic variants<span class="accordion-item-hd-cta">&#9650;</span></label>
        <div class="accordion-item-bd">
            <table class="options">
<?php
    foreach($allsubfilters as $key => $value) {
        echo '<tr><td><input type="checkbox" name="upama_subfilters[]" value="';
        echo $key;
        echo '"';
        if(in_array($key,$subfilters))
            echo ' checked';
        echo '></td><td><span>filter '.$value['name'].'</span></td></tr>';
    }
?>
            </table>
        </div>
    </li>
</ul> <!-- _upama_options -->
</form>
<script>
//var $upama_options = jQuery("#__upama_options");
//$upama_options.accordion({ collapsible: true, active: false, heightStyle: "content"});
//var $upama_witnesses = jQuery("#__upama_witnesses");
//$upama_witnesses.accordion({ collapsible: true, active: false, heightStyle: "content"});
//$upama_options.css('display','block');
//$upama_witnesses.css('display','block');

function toggleGenButton() {
    var checked = false;
    var button = document.getElementById("__upama_generate_button");
    var $witnesses = jQuery("#__upama_witness_list").find("input");
    $witnesses.each(function() {
        if(this.checked) {
            button.disabled = false;
            button.title = "Generate apparatus with selected witnesses";
            checked = true;
            return false;
        }
    });
    if(!checked) {
        button.disabled = true;
        button.title = "Select witnesses to compare";
    }
}

function WitnessSelectAll() {
    var selectall = document.querySelector('input[name="select_all"]');
    var selectalltext = document.getElementById('select_all_text');
    var checkboxes = document.querySelectorAll('input[name="upama_witnesses[]"]');

    if(selectalltext.textContent === 'Deselect all') {
        selectalltext.textContent = 'Select all';
        for(let checkbox of checkboxes)
            checkbox.checked = false;
        toggleGenButton();
    }
    else { 
        selectalltext.textContent = 'Deselect all';
        for(let checkbox of checkboxes)
            checkbox.checked = true;
    }
    
    toggleGenButton();
}
</script>
<?php

} // end if($meta)
else echo ""; // What to write when it's not a TEI file
?>
