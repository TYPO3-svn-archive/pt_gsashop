<?php
/**
 * $Id: conf.php,v 1.2 2008/05/02 14:23:01 ry44 Exp $
 */

    // DO NOT REMOVE OR CHANGE THESE 3 LINES:
define('TYPO3_MOD_PATH', '../typo3conf/ext/pt_gsashop/mod_gsacache/');
$BACK_PATH = '../../../../typo3/';
$MCONF['name'] = 'tools_txptgsashopM1';

$MCONF['access'] = 'user,group';
$MCONF['script'] = 'index.php';

$MLANG['default']['tabs_images']['tab'] = 'moduleicon.gif';
$MLANG['default']['ll_ref'] = 'LLL:EXT:pt_gsashop/mod_gsacache/locallang_mod.xml';
?>