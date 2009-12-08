<?php

########################################################################
# Extension Manager/Repository config file for ext: "pt_gsashop"
#
# Auto generated 08-12-2009 17:34
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'GSA Shop',
	'description' => 'PHP5 Web Shop System. This extension is the core of the \'General Shop Applications\' (GSA) extension family. GSA Shop is based on a data layer compatible to the German ERP system "GS AUFTRAG Professional" and allows optional usage combined with the ERP.',
	'category' => 'General Shop Applications',
	'author' => 'Rainer Kuhn',
	'author_email' => 't3extensions@punkt.de',
	'shy' => '',
	'dependencies' => 'cms,lang,smarty,pt_tools,pt_mail,pt_gsasocket,pt_gsauserreg',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod_gsacache',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => 'typo3temp/smarty_cache,typo3temp/smarty_compile',
	'modify_tables' => 'tt_content',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author_company' => 'punkt.de GmbH',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '1.0.0',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
			'lang' => '',
			'smarty' => '1.0.2-',
			'pt_tools' => '1.0.0-',
			'pt_mail' => '0.0.1-',
			'pt_gsasocket' => '1.0.0-',
			'pt_gsauserreg' => '0.1.0-',
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.1.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'css_styled_content' => '',
			'pt_heidelpay' => '0.1.4-',
			'PHP with --enable-bcmath (THIS IS JUST A HINT, please ignore it if your server is correctly configured)' => '',
		),
	),
	'_md5_values_when_last_written' => 'a:142:{s:10:".cvsignore";s:4:"7c13";s:20:"class.ext_update.php";s:4:"f225";s:44:"class.tx_ptgsashop_flexformItemFunctions.php";s:4:"afa7";s:21:"ext_conf_template.txt";s:4:"e50a";s:12:"ext_icon.gif";s:4:"4546";s:17:"ext_localconf.php";s:4:"6a94";s:14:"ext_tables.php";s:4:"5e8f";s:14:"ext_tables.sql";s:4:"b427";s:13:"locallang.xml";s:4:"bb1e";s:16:"locallang_db.xml";s:4:"488d";s:7:"tca.php";s:4:"c157";s:13:"doc/.htaccess";s:4:"381e";s:14:"doc/DevDoc.txt";s:4:"154b";s:40:"doc/class.tx_example_wfsConfig_1.php.txt";s:4:"9f5e";s:14:"doc/manual.sxw";s:4:"cb22";s:43:"mod_gsacache/class.tx_ptgsashop_module1.php";s:4:"ffb4";s:22:"mod_gsacache/clear.gif";s:4:"cc11";s:21:"mod_gsacache/conf.php";s:4:"3414";s:22:"mod_gsacache/index.php";s:4:"018e";s:26:"mod_gsacache/locallang.xml";s:4:"b049";s:30:"mod_gsacache/locallang_mod.xml";s:4:"4b07";s:27:"mod_gsacache/moduleicon.gif";s:4:"38b8";s:30:"pi1/class.tx_ptgsashop_pi1.php";s:4:"8c6a";s:17:"pi1/locallang.xml";s:4:"bcab";s:14:"pi2/ce_wiz.png";s:4:"478b";s:30:"pi2/class.tx_ptgsashop_pi2.php";s:4:"dfc4";s:38:"pi2/class.tx_ptgsashop_pi2_wizicon.php";s:4:"960d";s:13:"pi2/clear.gif";s:4:"cc11";s:19:"pi2/flexform_ds.xml";s:4:"e5ca";s:17:"pi2/locallang.xml";s:4:"1245";s:21:"pi2/locallang_tca.xml";s:4:"7001";s:30:"pi3/class.tx_ptgsashop_pi3.php";s:4:"ba83";s:17:"pi3/locallang.xml";s:4:"77a0";s:30:"pi4/class.tx_ptgsashop_pi4.php";s:4:"9d17";s:19:"pi4/flexform_ds.xml";s:4:"475f";s:17:"pi4/locallang.xml";s:4:"0d22";s:21:"pi4/locallang_tca.xml";s:4:"30b8";s:30:"pi6/class.tx_ptgsashop_pi6.php";s:4:"bcfd";s:17:"pi6/locallang.xml";s:4:"6627";s:30:"pi7/class.tx_ptgsashop_pi7.php";s:4:"3f10";s:17:"pi7/locallang.xml";s:4:"4b87";s:34:"res/class.tx_ptgsashop_address.php";s:4:"c620";s:34:"res/class.tx_ptgsashop_article.php";s:4:"aadc";s:42:"res/class.tx_ptgsashop_articleAccessor.php";s:4:"d4d4";s:44:"res/class.tx_ptgsashop_articleCollection.php";s:4:"e6ad";s:41:"res/class.tx_ptgsashop_articleFactory.php";s:4:"2173";s:39:"res/class.tx_ptgsashop_articleImage.php";s:4:"fbf4";s:47:"res/class.tx_ptgsashop_articleImageAccessor.php";s:4:"d9d2";s:49:"res/class.tx_ptgsashop_articleImageCollection.php";s:4:"3002";s:38:"res/class.tx_ptgsashop_baseArticle.php";s:4:"7ac0";s:42:"res/class.tx_ptgsashop_cacheController.php";s:4:"4384";s:31:"res/class.tx_ptgsashop_cart.php";s:4:"4fb1";s:43:"res/class.tx_ptgsashop_componentArticle.php";s:4:"3fff";s:53:"res/class.tx_ptgsashop_componentArticleCollection.php";s:4:"a78c";s:42:"res/class.tx_ptgsashop_compoundArticle.php";s:4:"ccc2";s:35:"res/class.tx_ptgsashop_delivery.php";s:4:"aeee";s:45:"res/class.tx_ptgsashop_deliveryCollection.php";s:4:"e287";s:39:"res/class.tx_ptgsashop_dispatchCost.php";s:4:"758b";s:47:"res/class.tx_ptgsashop_dispatchCostAccessor.php";s:4:"af9c";s:49:"res/class.tx_ptgsashop_dispatchCostCollection.php";s:4:"a7b7";s:42:"res/class.tx_ptgsashop_epaymentRequest.php";s:4:"d621";s:41:"res/class.tx_ptgsashop_epaymentReturn.php";s:4:"e3d2";s:49:"res/class.tx_ptgsashop_gsaTransactionAccessor.php";s:4:"7aad";s:48:"res/class.tx_ptgsashop_gsaTransactionHandler.php";s:4:"abdd";s:50:"res/class.tx_ptgsashop_iApplSpecArticleDataObj.php";s:4:"7eb7";s:43:"res/class.tx_ptgsashop_iApplSpecDataObj.php";s:4:"bae8";s:53:"res/class.tx_ptgsashop_iPaymentModifierCollection.php";s:4:"6bb0";s:43:"res/class.tx_ptgsashop_iWfsConfigurator.php";s:4:"bf81";s:30:"res/class.tx_ptgsashop_lib.php";s:4:"3910";s:35:"res/class.tx_ptgsashop_logEntry.php";s:4:"ce15";s:43:"res/class.tx_ptgsashop_logEntryAccessor.php";s:4:"478b";s:45:"res/class.tx_ptgsashop_logEntryCollection.php";s:4:"04df";s:32:"res/class.tx_ptgsashop_order.php";s:4:"ee66";s:40:"res/class.tx_ptgsashop_orderAccessor.php";s:4:"70a0";s:43:"res/class.tx_ptgsashop_orderPresentator.php";s:4:"7fef";s:41:"res/class.tx_ptgsashop_orderProcessor.php";s:4:"e23d";s:39:"res/class.tx_ptgsashop_orderWrapper.php";s:4:"a067";s:47:"res/class.tx_ptgsashop_orderWrapperAccessor.php";s:4:"236f";s:49:"res/class.tx_ptgsashop_orderWrapperCollection.php";s:4:"aba6";s:40:"res/class.tx_ptgsashop_paymentMethod.php";s:4:"9b2e";s:37:"res/class.tx_ptgsashop_scalePrice.php";s:4:"2a53";s:47:"res/class.tx_ptgsashop_scalePriceCollection.php";s:4:"0813";s:44:"res/class.tx_ptgsashop_sessionFeCustomer.php";s:4:"3464";s:39:"res/class.tx_ptgsashop_sessionOrder.php";s:4:"23a4";s:38:"res/class.tx_ptgsashop_taxAccessor.php";s:4:"a1d0";s:35:"res/class.tx_ptgsashop_workflow.php";s:4:"031e";s:43:"res/class.tx_ptgsashop_workflowAccessor.php";s:4:"3f16";s:41:"res/class.tx_ptgsashop_workflowStatus.php";s:4:"2ec8";s:51:"res/class.tx_ptgsashop_workflowStatusCollection.php";s:4:"1bf4";s:29:"res/class.ux_browse_links.php";s:4:"fc2c";s:30:"res/class.ux_db_list_extra.php";s:4:"b212";s:29:"res/locallang_res_classes.xml";s:4:"b2bf";s:19:"res/img/article.png";s:4:"49df";s:27:"res/img/article_passive.png";s:4:"8d91";s:25:"res/img/btn_bestellen.gif";s:4:"508d";s:20:"res/img/btn_cart.gif";s:4:"aad8";s:26:"res/img/btn_cartbox_de.png";s:4:"cc30";s:31:"res/img/btn_cartbox_default.png";s:4:"ff54";s:22:"res/img/btn_remove.gif";s:4:"886f";s:17:"res/img/empty.gif";s:4:"cd20";s:42:"res/img/icon_tx_ptgsashop_amendmentlog.gif";s:4:"dc05";s:44:"res/img/icon_tx_ptgsashop_article_images.png";s:4:"d0f9";s:36:"res/img/icon_tx_ptgsashop_artrel.gif";s:4:"dc05";s:44:"res/img/icon_tx_ptgsashop_cache_articles.png";s:4:"f705";s:52:"res/img/icon_tx_ptgsashop_cache_articles_passive.png";s:4:"2445";s:44:"res/img/icon_tx_ptgsashop_order_wrappers.png";s:4:"550a";s:36:"res/img/icon_tx_ptgsashop_orders.png";s:4:"7d77";s:54:"res/img/icon_tx_ptgsashop_orders_addresses_billing.png";s:4:"6789";s:55:"res/img/icon_tx_ptgsashop_orders_addresses_delivery.png";s:4:"68f7";s:45:"res/img/icon_tx_ptgsashop_orders_articles.png";s:4:"f705";s:47:"res/img/icon_tx_ptgsashop_orders_deliveries.png";s:4:"7214";s:49:"res/img/icon_tx_ptgsashop_orders_dispatchcost.png";s:4:"4d26";s:54:"res/img/icon_tx_ptgsashop_orders_paymentmethods_bt.png";s:4:"142c";s:54:"res/img/icon_tx_ptgsashop_orders_paymentmethods_cc.png";s:4:"e081";s:54:"res/img/icon_tx_ptgsashop_orders_paymentmethods_dd.png";s:4:"8f34";s:54:"res/img/icon_tx_ptgsashop_sysfolder_article_images.png";s:4:"56f8";s:48:"res/img/icon_tx_ptgsashop_sysfolder_articles.png";s:4:"fca5";s:47:"res/img/icon_tx_ptgsashop_sysfolder_feusers.png";s:4:"879f";s:46:"res/img/icon_tx_ptgsashop_sysfolder_orders.png";s:4:"dedd";s:44:"res/img/icon_tx_ptgsashop_sysfolder_shop.png";s:4:"5d9b";s:38:"res/img/icon_tx_ptgsashop_workflow.gif";s:4:"dc05";s:24:"res/smarty_cfg/dummy.txt";s:4:"d41d";s:34:"res/smarty_tpl/articlebox.tpl.html";s:4:"074f";s:43:"res/smarty_tpl/articleconfirmation.tpl.html";s:4:"1695";s:43:"res/smarty_tpl/articledistribution.tpl.html";s:4:"577b";s:28:"res/smarty_tpl/cart.tpl.html";s:4:"b92b";s:31:"res/smarty_tpl/cartbox.tpl.html";s:4:"facc";s:37:"res/smarty_tpl/checkoutlogin.tpl.html";s:4:"eeb1";s:38:"res/smarty_tpl/epaymentreturn.tpl.html";s:4:"280d";s:38:"res/smarty_tpl/finalorder_utf8.tpl.txt";s:4:"a981";s:41:"res/smarty_tpl/finalorder_utf8_en.tpl.txt";s:4:"c5fb";s:34:"res/smarty_tpl/ordererror.tpl.html";s:4:"3cfa";s:37:"res/smarty_tpl/orderoverview.tpl.html";s:4:"5134";s:39:"res/smarty_tpl/ordersingleview.tpl.html";s:4:"051c";s:34:"res/smarty_tpl/orderslist.tpl.html";s:4:"f441";s:33:"res/smarty_tpl/pi4notice.tpl.html";s:4:"9727";s:17:"res/sql/.htaccess";s:4:"381e";s:38:"res/sql/pt_gsashop_supplierControl.sql";s:4:"c772";s:22:"res/sql/sql_readme.txt";s:4:"4c16";s:20:"static/constants.txt";s:4:"c182";s:16:"static/setup.txt";s:4:"5f26";s:31:"static/pt_mail_config/setup.txt";s:4:"b463";}',
	'suggests' => array(
	),
);

?>