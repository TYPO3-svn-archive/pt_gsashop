<?php
/**
 * $Id: ext_localconf.php,v 1.17 2008/03/07 16:28:23 ry37 Exp $
 */
 
if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
    tt_content.CSS_editor.ch.tx_ptgsashop_pi1 = < plugin.tx_ptgsashop_pi1.CSS_editor
',43);

t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_ptgsashop_pi1.php','_pi1','list_type',0);


  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
    tt_content.CSS_editor.ch.tx_ptgsashop_pi2 = < plugin.tx_ptgsashop_pi2.CSS_editor
',43);

t3lib_extMgm::addPItoST43($_EXTKEY,'pi2/class.tx_ptgsashop_pi2.php','_pi2','list_type',0);


  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
    tt_content.CSS_editor.ch.tx_ptgsashop_pi3 = < plugin.tx_ptgsashop_pi3.CSS_editor
',43);

t3lib_extMgm::addPItoST43($_EXTKEY,'pi3/class.tx_ptgsashop_pi3.php','_pi3','list_type',0);


  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
    tt_content.CSS_editor.ch.tx_ptgsashop_pi4 = < plugin.tx_ptgsashop_pi4.CSS_editor
',43);

t3lib_extMgm::addPItoST43($_EXTKEY,'pi4/class.tx_ptgsashop_pi4.php','_pi4','list_type',0);


  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
    tt_content.CSS_editor.ch.tx_ptgsashop_pi6 = < plugin.tx_ptgsashop_pi6.CSS_editor
',43);

t3lib_extMgm::addPItoST43($_EXTKEY,'pi6/class.tx_ptgsashop_pi6.php','_pi6','list_type',0);


  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
    tt_content.CSS_editor.ch.tx_ptgsashop_pi7 = < plugin.tx_ptgsashop_pi7.CSS_editor
',43);

t3lib_extMgm::addPItoST43($_EXTKEY,'pi7/class.tx_ptgsashop_pi7.php','_pi7','list_type',0);




// SaveAndNew buttons for special database records
t3lib_extMgm::addUserTSConfig('
    options.saveDocNew.tx_ptgsashop_workflow=1
');

t3lib_extMgm::addUserTSConfig('
    options.saveDocNew.tx_ptgsashop_artrel=1
');



/*******************************************************************************
 * HACKS FOR CORE ADAPTION
 ******************************************************************************/
    
// XCLASSing of browse_links for displaying additional fields in backend "TYPO3 Element Browser" popup list view and for adding TCA fields of type "none" to the "TYPO3 Element Browser" popup list's search
if (TYPO3_MODE == 'BE') {
    $TYPO3_CONF_VARS['BE']['XCLASS']['typo3/class.browse_links.php'] =
        t3lib_extMgm::extPath($_EXTKEY).'res/class.ux_browse_links.php';
}
    
// XCLASSing of db_list for adding TCA fields of type "none" to the backend list module's search (required e.g. for search in cached articles)
if (TYPO3_MODE == 'BE') {
    $TYPO3_CONF_VARS['BE']['XCLASS']['typo3/class.db_list_extra.inc'] =
        t3lib_extMgm::extPath($_EXTKEY).'res/class.ux_db_list_extra.php';
        
}


?>