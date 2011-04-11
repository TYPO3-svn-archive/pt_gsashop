<?php
/**
 * $Id: ext_tables.php,v 1.75 2008/11/13 15:13:18 ry37 Exp $
 */

if (! defined('TYPO3_MODE')) {
    die ('Access denied.');
}

// include classes for itemsProcFunc callback functions
if (TYPO3_MODE=='BE') {
    include_once(t3lib_extMgm::extPath('pt_gsashop').'class.tx_ptgsashop_flexformItemFunctions.php');
}

// add GSA Shop modules for backend
if (TYPO3_MODE == 'BE') {
    // add GSA cache module
    t3lib_extMgm::addModule('tools', 'txptgsashopM1', '', t3lib_extMgm::extPath($_EXTKEY).'mod_gsacache/');
}



t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key,pages,recursive';


t3lib_extMgm::addPlugin(array('LLL:EXT:pt_gsashop/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');


t3lib_extMgm::addStaticFile($_EXTKEY,'static/','GSA Shop: Basic Config');
t3lib_extMgm::addStaticFile($_EXTKEY,'static/pt_mail_config/','GSA Shop: pt_mail Config');


// add folder type and icon
if (t3lib_div::compat_version('4.4')) {
	t3lib_SpriteManager::addTcaTypeIcon('pages', 'contains-news', '../typo3conf/ext/tt_news/ext_icon_ttnews_folder.gif');
	t3lib_SpriteManager::addTcaTypeIcon('pages', 'contains-gsacache', '../typo3conf/ext/pt_gsashop/res/img/icon_tx_ptgsashop_sysfolder_articles.png');
	t3lib_SpriteManager::addTcaTypeIcon('pages', 'contains-gsaorders', '../typo3conf/ext/pt_gsashop/res/img/icon_tx_ptgsashop_sysfolder_orders.png');
	t3lib_SpriteManager::addTcaTypeIcon('pages', 'contains-gsaimg', '../typo3conf/ext/pt_gsashop/res/img/icon_tx_ptgsashop_sysfolder_article_images.png');
	t3lib_SpriteManager::addTcaTypeIcon('pages', 'contains-fe_users', '../typo3conf/ext/pt_gsashop/res/img/icon_tx_ptgsashop_sysfolder_feusers.png');
	t3lib_SpriteManager::addTcaTypeIcon('pages', 'contains-shop', '../typo3conf/ext/pt_gsashop/res/img/icon_tx_ptgsashop_sysfolder_shop.png');
} else {
	$ICON_TYPES['gsacache'] = array('icon' => t3lib_extMgm::extRelPath($_EXTKEY).'res/img/icon_tx_ptgsashop_sysfolder_articles.png');
	$ICON_TYPES['gsaorders'] = array('icon' => t3lib_extMgm::extRelPath($_EXTKEY).'res/img/icon_tx_ptgsashop_sysfolder_orders.png');
	$ICON_TYPES['gsaimg'] = array('icon' => t3lib_extMgm::extRelPath($_EXTKEY).'res/img/icon_tx_ptgsashop_sysfolder_article_images.png') ;
	$ICON_TYPES['fe_users'] = array('icon' => t3lib_extMgm::extRelPath($_EXTKEY).'res/img/icon_tx_ptgsashop_sysfolder_feusers.png');
	$ICON_TYPES['shop'] = array('icon' => t3lib_extMgm::extRelPath($_EXTKEY).'res/img/icon_tx_ptgsashop_sysfolder_shop.png');
}
$TCA['pages']['columns']['module']['config']['items'][] = array('LLL:EXT:pt_gsashop/locallang_db:pages.tx_ptgsashop_gsacache', 'gsacache');
$TCA['pages']['columns']['module']['config']['items'][] = array('LLL:EXT:pt_gsashop/locallang_db:pages.tx_ptgsashop_gsaorders', 'gsaorders');
$TCA['pages']['columns']['module']['config']['items'][] = array('LLL:EXT:pt_gsashop/locallang_db:pages.tx_ptgsashop_gsaimg', 'gsaimg');

// predefined in the TYPO3 Core, but without any icons by default




// ry44, 2007-12-05: Replaced individual tt_content fields by a flexform field
t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi2'] = 'layout,select_key,pages,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi2'] = 'pi_flexform';
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi2', 'FILE:EXT:'.$_EXTKEY.'/pi2/flexform_ds.xml');

t3lib_extMgm::addPlugin(array('LLL:EXT:pt_gsashop/locallang_db.xml:tt_content.list_type_pi2', $_EXTKEY.'_pi2'),'list_type');


if (TYPO3_MODE=='BE') {
    $TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_ptgsashop_pi2_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'pi2/class.tx_ptgsashop_pi2_wizicon.php';
}






t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi3']='layout,select_key,pages,recursive';


t3lib_extMgm::addPlugin(array('LLL:EXT:pt_gsashop/locallang_db.xml:tt_content.list_type_pi3', $_EXTKEY.'_pi3'),'list_type');






// NEW pi4 plugin type using flexforms (since 2007-06-26)
t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi4']='layout,select_key,pages,recursive';  // hide 'traditional', non-used plugin fields of tt_content for pi4
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi4']='pi_flexform';  // display flexform field of tt_content for pi4
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi4', 'FILE:EXT:'.$_EXTKEY.'/pi4/flexform_ds.xml');  // add flexform datastructure (stored in a XML file) to TCA
t3lib_extMgm::addPlugin(array('LLL:EXT:pt_gsashop/locallang_db.xml:tt_content.list_type_pi4', $_EXTKEY.'_pi4'),'list_type');  // add plugin pi4 to plugin selection list







t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi6']='layout,select_key';


t3lib_extMgm::addPlugin(array('LLL:EXT:pt_gsashop/locallang_db.xml:tt_content.list_type_pi6', $_EXTKEY.'_pi6'),'list_type');







t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi7']='layout,select_key';


t3lib_extMgm::addPlugin(array('LLL:EXT:pt_gsashop/locallang_db.xml:tt_content.list_type_pi7', $_EXTKEY.'_pi7'),'list_type');






$TCA['tx_ptgsashop_orders'] = array(
    'ctrl' => array(
        'title' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders',
        'readOnly' => true,
        'hideTable' => true,
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY order_timestamp',
        'delete' => 'deleted',
        'dividers2tabs' => true,
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'res/img/icon_tx_ptgsashop_orders.png',
    ),
    'feInterface' => array(
        'fe_admin_fieldList' => 'hidden, fe_cruser_id, order_timestamp, is_net, is_taxfree, is_tc_acc, is_wd_acc, is_mult_del, applSpecData, applSpecDataClass',
    )
);

$TCA['tx_ptgsashop_orders_deliveries'] = array(
    'ctrl' => array(
        'title' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_deliveries',
        'readOnly' => true,
        'hideTable' => true,
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'dividers2tabs' => true,
        'default_sortby' => 'ORDER BY crdate',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'res/img/icon_tx_ptgsashop_orders_deliveries.png',
    ),
    'feInterface' => array(
        'fe_admin_fieldList' => 'hidden, fe_cruser_id, orders_id, is_orderbase_net, is_orderbase_taxfree, is_physical',
    )
);

$TCA['tx_ptgsashop_orders_addresses'] = array(
    'ctrl' => array(
        'title' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_addresses',
        'readOnly' => true,
        'hideTable' => true,
        'label' => 'post2',
        'label_alt' => 'post3',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
		'typeicon_column' => 'irreParentTable',
		'typeicons' => Array (
			'tx_ptgsashop_orders' => t3lib_extMgm::extRelPath($_EXTKEY).'res/img/icon_tx_ptgsashop_orders_addresses_billing.png', // billing address
			'tx_ptgsashop_orders_deliveries' => t3lib_extMgm::extRelPath($_EXTKEY).'res/img/icon_tx_ptgsashop_orders_addresses_delivery.png', // delivery address
		),
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'res/img/icon_tx_ptgsashop_orders_addresses_billing.png',
    ),
    'feInterface' => array(
        'fe_admin_fieldList' => 'hidden, fe_cruser_id, orders_id, deliveries_id, post1, post2, post3, post4, post5, post6, post7, country, gsa_id_adresse, gsa_id_ansch',
    )
);

$TCA['tx_ptgsashop_orders_articles'] = array(
    'ctrl' => array(
        'title' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles',
        'readOnly' => true,
        'hideTable' => true,
        'label' => 'art_no',
        'label_alt' => 'quantity',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'dividers2tabs' => true,
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'res/img/icon_tx_ptgsashop_orders_articles.png',
    ),
    'feInterface' => array(
        'fe_admin_fieldList' => 'hidden, fe_cruser_id, orders_id, deliveries_id, gsa_id_artikel, quantity, art_no, description, price_calc_qty, price_category, date_string, tax_code, fixedCost1, fixedCost2, tax_percentage, price_net, price_gross, userField01, userField02, userField03, userField04, userField05, userField06, userField07, userField08, applSpecData, applSpecDataClass, artrelApplSpecUid, artrelApplIdentifier',
    )
);

$TCA['tx_ptgsashop_orders_dispatchcost'] = array(
    'ctrl' => array(
        'title' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_dispatchcost',
        'readOnly' => true,
        'hideTable' => true,
        'label' => 'cost_type_name',
        'label_alt' => 'dispatch_cost',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'res/img/icon_tx_ptgsashop_orders_dispatchcost.png',
    ),
    'feInterface' => array(
        'fe_admin_fieldList' => 'hidden, fe_cruser_id, orders_id, deliveries_id, cost_type_name, cost_comp_1, cost_comp_2, cost_comp_3, cost_comp_4, allowance_comp_1, allowance_comp_2, allowance_comp_3, allowance_comp_4, cost_tax_code, tax_percentage, dispatch_cost',
    )
);

$TCA['tx_ptgsashop_orders_paymentmethods'] = array(
    'ctrl' => array(
        'title' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_paymentmethods',
        'readOnly' => true,
        'hideTable' => true,
        'dividers2tabs' => true,
        'label' => 'method',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'type' => 'method',
        'default_sortby' => 'ORDER BY crdate',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
        'type' => 'method',
		'typeicon_column' => 'method',
		'typeicons' => Array (
			'dd' => t3lib_extMgm::extRelPath($_EXTKEY).'res/img/icon_tx_ptgsashop_orders_paymentmethods_dd.png', // direct debit
			'cc' => t3lib_extMgm::extRelPath($_EXTKEY).'res/img/icon_tx_ptgsashop_orders_paymentmethods_cc.png', // credit card
        	'bt' => t3lib_extMgm::extRelPath($_EXTKEY).'res/img/icon_tx_ptgsashop_orders_paymentmethods_bt.png', // bank transfer
		),
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'res/img/icon_tx_ptgsashop_orders_paymentmethods_bt.png',
    ),
    'feInterface' => array(
        'fe_admin_fieldList' => 'hidden, fe_cruser_id, orders_id, method, epayment_success, epayment_trans_id, epayment_ref_id, epayment_short_id, bank_account_holder, bank_name, bank_account_number, bank_code, bank_bic, bank_iban, gsa_dta_acc_no',
    )
);

$TCA['tx_ptgsashop_order_wrappers'] = array(
    'ctrl' => array(
        'title' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_order_wrappers',
        'readOnly' => true,
        'label' => 'order_timestamp',
        'label_alt' => 'related_doc_no',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'dividers2tabs' => true,
        'default_sortby' => 'ORDER BY order_timestamp desc',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'res/img/icon_tx_ptgsashop_order_wrappers.png',
    ),
    'feInterface' => array(
        'fe_admin_fieldList' => 'hidden, fe_cruser_id, customer_id, related_doc_no, orders_id, order_timestamp, sum_net, sum_gross, wf_status_code, wf_lock_userid, wf_lock_timestamp, wf_lastuser_id',
    )
);


t3lib_extMgm::allowTableOnStandardPages('tx_ptgsashop_workflow');

$TCA['tx_ptgsashop_workflow'] = array(
    'ctrl' => array(
        'title' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_workflow',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY status_code',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'res/img/icon_tx_ptgsashop_workflow.gif',

        // additional config for multilanguage support
        'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
        'copyAfterDuplFields' => 'sys_language_uid',
        'useColumnsForDefaultValues' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'languageField' => 'sys_language_uid',
    ),
    'feInterface' => array(
        'fe_admin_fieldList' => 'hidden, status_code, name, description, auth_groups_view, auth_groups_use, update_order, condition_method, permission_method, approve_action_method, deny_action_method, advance_action_method, halt_action_method, approve_status_code, deny_status_code, advance_status_code, label_choice, label_approve, label_deny, label_confirm_approve, label_confirm_deny, sys_language_uid',
    )
);


$TCA['tx_ptgsashop_amendmentlog'] = array(
    'ctrl' => array(
        'title' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_amendmentlog',
        'readOnly' => true,
        'hideTable' => true,
        'label' => 'log_entry',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY tstamp',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'res/img/icon_tx_ptgsashop_amendmentlog.gif',
    ),
    'feInterface' => array(
        'fe_admin_fieldList' => 'hidden, fe_cruser_id, order_wrapper_id, log_entry, status_prev, status_new',
    )
);


t3lib_extMgm::allowTableOnStandardPages('tx_ptgsashop_artrel');

$TCA['tx_ptgsashop_artrel'] = array(
    'ctrl' => array(
        'title' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_artrel',
        'label' => 'gsa_art_nummer',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY gsa_art_nummer',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'res/img/icon_tx_ptgsashop_artrel.gif',
    ),
    'feInterface' => array(
        'fe_admin_fieldList' => 'hidden, gsa_art_nummer, max_amount, exclusion_articles, required_articles, related_articles, bundled_articles, appl_spec_uid, appl_identifier',
    )
);


$TCA['tx_ptgsashop_article_images'] = array(
    'ctrl' => array(
        'title' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_article_images',
        'label' => 'path',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY tstamp',
        'delete' => 'deleted',
		'thumbnail' => 'path', // "User settings"-Module -> "Startup"-Tab -> activate "Show Thumbnails by default" checkbox to see thumbnails
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'res/img/icon_tx_ptgsashop_article_images.png',
    ),
    'feInterface' => array(
        'fe_admin_fieldList' => 'hidden, gsa_art_nummer, path, description, alt, title',
    )
);


$TCA['tx_ptgsashop_cache_articles'] = array(
    'ctrl' => array(
        'title' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_cache_articles',
        'readOnly' => true,
        'label' => 'gsadb_artnr',
        'label_alt' => 'gsadb_match, gsadb_match2',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY gsadb_artnr',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
		'typeicon_column' => 'gsadb_passiv',
		'typeicons' => Array (
			'1' => t3lib_extMgm::extRelPath($_EXTKEY).'res/img/icon_tx_ptgsashop_cache_articles_passive.png',
		),
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'res/img/icon_tx_ptgsashop_cache_articles.png',
    ),
    'feInterface' => array(
        'fe_admin_fieldList' => 'gsadb_artnr, gsadb_match, gsadb_match2, gsadb_passiv',
    )
);
?>