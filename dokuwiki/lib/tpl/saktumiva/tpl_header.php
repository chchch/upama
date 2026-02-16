<?php
/**
 * Template header, included in the main and detail files
 */

// must be run from within DokuWiki
if (!defined('DOKU_INC')) die();
?>

<!-- ********** HEADER ********** -->
<div id="dokuwiki__header"><div class="pad group">

    <?php tpl_includeFile('header.html') ?>

    <div class="tools group">
        <!-- USER TOOLS -->
        <?php if ($conf['useacl']): ?>
            <div id="dokuwiki__usertools">
                <h3 class="a11y"><?php echo $lang['user_tools']; ?></h3>
                <ul>
                    <?php
                    if (!empty($_SERVER['REMOTE_USER'])) {
                        echo '<li class="user">';
                        tpl_userinfo(); /* 'Logged in as ...' */
                        echo '</li>';
                    }
                    $items = (new \dokuwiki\Menu\UserMenu())->getItems('action');
                    foreach($items as $item) {
                      if($item->getTitle() === 'Show page') continue;
                      echo '<li class="action">'.
                      '<a href="'.$item->getLink().'" title="'.$item->getTitle().'">'.
                      '<span>'.$item->getLabel().'</span>'.
                      '</a></li>';
                    }   
                    ?>
                </ul>
            </div>
        <?php endif ?>

        <!-- SITE TOOLS -->
<!--        <div id="dokuwiki__sitetools">
            <h3 class="a11y"><?php echo $lang['site_tools']; ?></h3>
            <?php tpl_searchform(); ?>
            <div class="mobileTools">
                <?php echo (new \dokuwiki\Menu\MobileMenu())->getDropdown($lang['tools']); ?>
            </div>
            <ul>
                <?php echo (new \dokuwiki\Menu\SiteMenu())->getListItems('action ', false); ?>
            </ul>
        </div>
-->
    </div>

    <!-- BREADCRUMBS -->
    <?php if ($conf['breadcrumbs'] || $conf['youarehere']) : ?>
        <div class="breadcrumbs">
            <a href="?do=index" class="sitemap">Sitemap</a>
            <?php if ($conf['youarehere']) : ?>
                <div class="youarehere"><?php tpl_youarehere() ?></div>
            <?php endif ?>
            <?php if ($conf['breadcrumbs']) : ?>
                <div class="trace"><?php tpl_breadcrumbs() ?></div>
            <?php endif ?>
        </div>
    <?php endif ?>
    <?php html_msgarea() ?>

    <hr class="a11y" />
</div></div><!-- /header -->
