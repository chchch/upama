<?php
/**
 * codologic Template
 *
 * @link     http://dokuwiki.org/template
 * 
 * Author: Avinash D'Silva <avinash.roshan.dsilva@gmail.com|codologic.com>
 * 
 * Previous Authors:
 * @author   Anika Henke <anika@selfthinker.org>
 * @author   Clarence Lee <clarencedglee@gmail.com>
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */

// require functions
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR .'bootstrap.php');

if (!defined('DOKU_INC')) die(); /* must be run from within DokuWiki */
header('X-UA-Compatible: IE=edge,chrome=1');
$hasSidebar = page_findnearest($conf['sidebar']);
$showSidebar = $hasSidebar && ($ACT=='show');

?><!DOCTYPE html>
<html lang="<?php echo $conf['lang'] ?>" dir="<?php echo $lang['direction'] ?>" class="no-js">
<head>
    <meta charset="utf-8" />
    <title><?php tpl_pagetitle() ?> [<?php echo strip_tags($conf['title']) ?>]</title>
    <!--?php echo tpl_js('analyticstracking.js'); ?-->
    <!--script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/es6-shim/0.35.2/es6-shim.min.js"></script-->
    <script type="text/javascript">
        if(!HTMLCollection.prototype.hasOwnProperty(Symbol.iterator)) {
            HTMLCollection.prototype[Symbol.iterator] = Array.prototype[Symbol.iterator];
            NodeList.prototype[Symbol.iterator] = Array.prototype[Symbol.iterator];
            NamedNodeMap.prototype[Symbol.iterator] = Array.prototype[Symbol.iterator];
    }
    </script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/dom4/1.8.3/dom4.js"></script>
    <?php tpl_metaheaders() ?>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <?php echo tpl_favicon(array('favicon', 'mobile')) ?>
    <?php tpl_includeFile('meta.html') ?>

<link href='https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,600' rel='stylesheet' type='text/css'>
<!--<?php
    if($INPUT->post->arr('upama_witnesses') || 
       $INPUT->get->str('upama_ver')) {
?>
<link href="<?php print DOKU_TPL; ?>css/with_apparatus.css" rel="stylesheet">
<?php    
    }
    else {
?>
<link href="<?php print DOKU_TPL; ?>css/no_apparatus.css" rel="stylesheet">
<?php
    }
?>-->
<link href="<?php print DOKU_TPL; ?>css/with_apparatus.css" rel="stylesheet">
<?php echo tpl_js('layout.js'); ?>
</head>

<body>
    
     <div id="container">
            <div class="ui-layout-center">
    
    <div id="dokuwiki__site"><div id="dokuwiki__top" class="site <?php echo tpl_classes(); ?>">

        <?php include('tpl_header.php') ?>

        <div class="wrapper group">



            <!-- ********** CONTENT ********** -->
            <div id="dokuwiki__content"><div class="pad group">

                <!--<div class="pageId"><span><?php echo hsc($ID) ?></span></div>-->

                <div class="page group">
                    <?php tpl_flush() ?>
                    <?php tpl_includeFile('pageheader.html') ?>
                    <!-- wikipage start -->
                    <?php tpl_content() ?>
                    <!-- wikipage stop -->
                    <?php tpl_includeFile('pagefooter.html') ?>
                </div>

                <div class="docInfo"><?php //tpl_pageinfo() ?></div>

                <?php tpl_flush() ?>
            </div></div><!-- /content -->

            <hr class="a11y" />

            <!-- PAGE ACTIONS -->
            <div id="dokuwiki__pagetools">
                <h3 class="a11y"><?php echo $lang['page_tools']; ?></h3>
                <div class="tools">
                    <ul>
                        <?php
                            $data = array(
                                'view'  => 'main',
                                'items' => array(
                                    'edit'      => tpl_action('edit',      1, 'li', 1, '<span>', '</span>'),
                                    'revert'    => tpl_action('revert',    1, 'li', 1, '<span>', '</span>'),
                                    'revisions' => tpl_action('revisions', 1, 'li', 1, '<span>', '</span>'),
                                   // 'backlink'  => tpl_action('backlink',  1, 'li', 1, '<span>', '</span>'),
                                    'subscribe' => tpl_action('subscribe', 1, 'li', 1, '<span>', '</span>'),
                                    'top'       => tpl_action('top',       1, 'li', 1, '<span>', '</span>')
                                )
                            );

                            // the page tools can be amended through a custom plugin hook
                            $evt = new Doku_Event('TEMPLATE_PAGETOOLS_DISPLAY', $data);
                            if($evt->advise_before()){
                                foreach($evt->data['items'] as $k => $html) echo $html;
                            }
                            $evt->advise_after();
                            unset($data);
                            unset($evt);
                        ?>
                    </ul>
                </div>
            </div>
        </div><!-- /wrapper -->

        <?php //include('tpl_footer.php') ?>
    </div></div><!-- /site -->

    <div class="no"><?php tpl_indexerWebBug() /* provide DokuWiki housekeeping, required in all templates */ ?></div>
    <div id="screen__mode" class="no"></div><?php /* helper to detect CSS media query in script.js */ ?>
    
    </div>
<?php
    $sidebarclosed = $INPUT->get->str('sidebar') == 'closed';
    if($sidebarclosed) {
        ?>
            <div class="ui-layout-west sidebar-shrink" id="sidebar-wrapper">
               <div class="ui-layout-fixed sidebar-hide" id="sidebar"> 
<?php
    }
    else {
        ?>
            <div class="ui-layout-west" id="sidebar-wrapper">
               <div class="ui-layout-fixed" id="sidebar"> 
<?php 
    }
        ?>
            <div class='west_header'>
            <div class="headings group">
        <ul class="a11y skip">
            <li><a href="#dokuwiki__content"><?php echo $lang['skip_to_content']; ?></a></li>
        </ul>
        <h1 class="logo"><a href="/start" accesskey="h" title="saktumiva">saktumIva</a></h1>
        <?php if ($conf['tagline']): ?>
            <p class="claim"><?php echo $conf['tagline']; ?></p>
        <?php endif ?>
    </div>
            
            
       <!--     
        <div id="dokuwiki__sitetools">
            <h3 class="a11y"><?php echo $lang['site_tools']; ?></h3>
            <?php tpl_searchform(); ?>
            <div class="mobileTools">
                <?php tpl_actiondropdown($lang['tools']); ?>
            </div>
            <ul id="codowiki_search_ul">
                <?php
                    tpl_action('recent', 1, 'li');
                    //tpl_action('media', 1, 'li');
                    tpl_action('index', 1, 'li');
                ?>
            </ul>
        </div>
                -->
            
            </div>
            
            
            
                <!-- ********** ASIDE ********** -->
                    <div class="side_content">
                        <?php tpl_flush() ?>
                        <?php tpl_includeFile('sidebarheader.html') ?>
                <?php if($showSidebar): ?>
                        <?php tpl_include_page($conf['sidebar'], 1, 1); ?>
                        <?php 
                            $meta = p_get_metadata($INFO['id'],'plugin_upama',false);
                            if($meta)
                                tpl_includeFile('apparatus.php'); 
                        ?>

                <?php endif; ?>
                        <?php tpl_includeFile('sidebarfooter.html') ?>
                    </div>
                
            
            <!--below div is end WEST pane-->
            </div>
 <?php
    if($sidebarclosed) {
        ?>
            <div class="ui-layout-toggler toggler-closed" id="sidebar-toggler">
<?php
    }
    else {
        ?>
            <div class="ui-layout-toggler" id="sidebar-toggler">
<?php 
    }
        ?>           
    <div class="ui-layout-toggler-button">
    </div>
    </div>  
            </div>
    
   
    <!--below div is end content-->
    </div>
      <?php // include('tpl_footer.php') ?>
</body>
</html>
