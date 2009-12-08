<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2005-2008 Rainer Kuhn (kuhn@punkt.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is 
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
* 
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
* 
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/** 
 * Frontend Plugin 'GSA Shop: Article display' for the 'pt_gsashop' extension.
 *
 * $Id: class.tx_ptgsashop_pi2.php,v 1.104 2009/10/06 09:48:51 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2005-03-02
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of TYPO3 libraries
 *
 * @see tslib_pibase
 */
require_once(PATH_tslib.'class.tslib_pibase.php');

/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_lib.php';  // GSA shop library class with static methods
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_article.php';  // GSA shop article class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_articleFactory.php';  // GSA shop article factory class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_cart.php';  // GSA shop cart class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_sessionFeCustomer.php';  // GSA shop frontend customer class

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_smartyAdapter.php';  // Smarty template engine adapter
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_formReloadHandler.php'; // web form reload handler class
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_sessionStorageAdapter.php'; // storage adapter for TYPO3 _browser_ sessions


/**
 * Debugging config for development
 */
#$trace     = 1; // (int) trace options @see tx_pttools_debug::trace() [for local temporary debugging use only, please COMMENT OUT this line if finished with debugging!]
#$errStrict = 1; // (bool) set strict error reporting level for development (requires $trace to be set to 1)  [for local temporary debugging use only, please COMMENT OUT this line if finished with debugging!]


// debugging output for development (uncomment to use)
#trace(TYPO3_db);
#trace($TYPO3_CONF_VARS);
#trace(t3lib_div::GPvar('tx_ptgsashop_pi2'));
#trace($_POST, 0, '$_POST');
#trace($GLOBALS['TSFE'], 0, '$GLOBALS[TSFE]');
#trace($GLOBALS['TSFE']->fe_user, 0, '$GLOBALS[TSFE]->fe_user');
#trace($GLOBALS['TSFE']->fe_user->sesData, 0, '$GLOBALS[TSFE]->fe_user->sesData');



/**
 * Provides a frontend plugin displaying an article with article information and online order button for a specified article from the GSA database
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2005-03-02
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_pi2 extends tslib_pibase {
    
    /**
     * tslib_pibase (parent class) instance variables
     */
    public $extKey = 'pt_gsashop';    // The extension key.
    public $prefixId = 'tx_ptgsashop_pi2';    // Same as class name
    public $scriptRelPath = 'pi2/class.tx_ptgsashop_pi2.php';    // Path to this script relative to the extension dir.
    
    
    
    /***************************************************************************
     * tx_ptgsashop_pi2 instance variables
     **************************************************************************/
    
    /**
     * @var array					basic extension configuration data from localconf.php (configurable in Extension Manager)
     */
    protected $extConfArr = array();
    
    /**
	 * @var string					address for HTML forms' 'action' attributes to send a form of this page to itself
	 */
    protected $formActionSelf = '';

    /**
	 * @var	tx_pttools_formReloadHandler	web form reload handler object
	 */
    protected $formReloadHandler = NULL;  
    
    /**
     * @var tx_ptgsashop_article 	article to use in this plugin
     */
    protected $articleObj = NULL;         
    
    /**
     * @var tx_ptgsashop_cart		shopping cart object
     */
    protected $cartObj = NULL;

    /**
     * @var	tx_ptgsashop_sessionFeCustomer	frontend customer object (FE user who uses this plugin)
     */
    protected $customerObj = NULL;        
    
    /**
	 * @var bool	flag wether this plugin is called by a FE user who is legitimated to use net prices (0=gross prices, 1=net prices)
	 */
    protected $isNetPriceDisplay = 0;   
    
    /**
     * @var bool    flag wether this plugin is called as article single view page  (0=article display (regular content element), 1=article single view mode)
     */
    protected $isArticleSingleViewMode = 0;
    
    
    
    /***************************************************************************
     *   Class Constants
     **************************************************************************/
    
    const ARTICLECONFIRMATION_CLASS_NAME = 'tx_ptgsashop_pi1'; // (string) class name of the article confirmation plugin to use combined with this plugin (if configured in Constant Editor)
    
    
    
    /***************************************************************************
     *   MAIN
     **************************************************************************/
    
    /** 
     * Main method of the plugin: Prepares properties and instances, interprets submit buttons to control plugin behaviour and returns the page content
     *
     * @param   string      HTML-Content of the plugin to be displayed within the TYPO3 page
     * @param   array       Global configuration for this plugin (mostly done in Constant Editor/TS setup)
     * @return  string      HTML plugin content for output on the page (if not redirected before)
     * @global  integer     $GLOBALS['TSFE']->id: UID of the current page
     * @throws  tx_pttools_exception   if no security hash is found (for article single view mode only)
     * @throws  tx_pttools_exception   if the security hash check fails for article single view mode (for article single view mode only)
     * @throws  tx_pttools_exception   if no flexform data found
     * @throws  tx_pttools_exception   if no valid article UID found
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-03-02
     */
    public function main($content, $conf) {
           
        // ********** DEFAULT PLUGIN INITIALIZATION **********
        
        $this->conf = $conf; // Extension configuration (mostly taken from Constant Editor)
        $this->pi_setPiVarDefaults();
        $this->pi_loadLL();
        $this->pi_USER_INT_obj = 1; // Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
        
        // for TYPO3 3.8.0+: enable storage of last built SQL query in $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery for all query building functions of class t3lib_DB
        $GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;
            
        // debug tools for development (uncomment to use)
        #tx_ptgsashop_lib::clearSession(__FILE__, __LINE__); // unset all session objects
        trace($this->conf, 0, '$this->conf'); // extension config data (mainly from TS setup/Constant Editor)
        trace($this->piVars, 0, '$this->piVars');
        #trace($this->cObj->data, 0, '$this->cObj->data'); // content element data, containing tx_ptgsashop_* plugin config
            
        // get basic extension configuration data from localconf.php (configurable in Extension Manager) - HAS TO BE PLACED BEFORE 'date_default_timezone_set()'!
        $this->extConfArr = tx_pttools_div::returnExtConfArray($this->extKey);
        
        // set the timezone (see http://php.net/manual/en/timezones.php) since it is not safe to rely on the system's timezone settings
        $previousTimeZone = date_default_timezone_get();
        date_default_timezone_set($this->extConfArr['timezone']);
        
        // DEV ONLY: set strict error reporting level for development
        if ($this->extConfArr['prodEnv'] == 0 && $GLOBALS['errStrict'] == 1 && $GLOBALS['trace'] == 1) { 
            $oldErrorReportingLevel = error_reporting(E_ALL | E_STRICT);
        }
        
        try {
            
            // ********** CHECK PLUGIN REQUIREMENTS (for article single view mode only) **********
        
            // throw exception if article single view ID given but no valid security hash found (stop further script processing)
            if (isset($this->piVars['asv_id'])) {
                if (!isset($this->piVars['asv_hash'])) {
                    throw new tx_pttools_exception('Invalid plugin call.', 0, 'Article single view called without security hash!');
                } else {
                    $checkHash = md5($this->piVars['asv_id'] . $this->conf['md5SecurityCheckSalt']);
                    if ($this->piVars['asv_hash'] != $checkHash) {
                        throw new tx_pttools_exception('Invalid plugin call!', 0, 'MD5 security hash check for article single view failed!');
                    }
                }
            }
            
            // ********** SET PLUGIN-OBJECT PROPERTIES **********
            
            // get conf array/flexform values and override regular typoscript configuration (added by Fabrizio Branca 2007/12/11)
            tx_pttools_div::mergeConfAndFlexform($this, true);
            
            // set basic properties
            $this->formReloadHandler = new tx_pttools_formReloadHandler; // set form reload handler object
            $this->cartObj = tx_ptgsashop_cart::getInstance(); // get unique instance (Singleton) of shopping cart
            $this->customerObj = tx_ptgsashop_sessionFeCustomer::getInstance(); // get unique instance (Singleton) of current FE customer
            $this->isNetPriceDisplay = $this->customerObj->getNetPriceLegitimation(); // set flag wether this plugin has been called by a FE user who is legitimated to use net prices
            $this->conf['article_uid'] = $this->cObj->stdWrap($this->conf['article_uid'], $this->conf['article_uid.']); // added for usage of the plugin via TS
            $this->isArticleSingleViewMode = (isset($this->conf['article_uid']) ? 0 : 1);
            
            // set article object and self URL depending on plugin mode
            $articleUid = ($this->isArticleSingleViewMode == 1 ? $this->piVars['asv_id'] : $this->conf['article_uid']);
            if (empty($articleUid) && isset($this->piVars['gsa_id'])) {
                $articleUid = $this->piVars['gsa_id']; // if coming from form button in article single view none of the above will be set
            }
            if (empty($articleUid) || $articleUid < 1) {
                throw new tx_pttools_exception('Invalid plugin call: no article to display.', 0, 'No valid article UID found - cannot instantiate any article object');
            }
            $this->articleObj = tx_ptgsashop_articleFactory::createArticle(           
                                    $articleUid, 
                                    $this->customerObj->get_priceCategory(), 
                                    $this->customerObj->get_gsaMasterAddressId(), 
                                    1, 
                                    '',  
                                    $this->conf['articleDisplayImg']
                                ); 
            trace($this->articleObj, 0, '$this->articleObj');
            $this->formActionSelf = ($this->isArticleSingleViewMode == 1 ? $this->articleObj->getFePageLink() : $this->pi_getPageLink($GLOBALS['TSFE']->id));  // self url for HTML form action attributes
            
            
            // ********** CONTROLLER: execute approriate method for any action command (retrieved form buttons/GET vars) **********
            
            // [CMD] add to cart: if appropriate button has been used for the plugin related article: store article in shopping cart
            if (isset($this->piVars['cart_button']) && ($this->piVars['gsa_id'] == $articleUid)) { 
                $content .= $this->exec_addArticleToCart();
            // [CMD] remove from cart: if appropriate button has been used for the plugin related article: remove article from shopping cart
            } elseif (isset($this->piVars['remove_button']) && ($this->piVars['gsa_id'] == $articleUid)) { 
                $content .= $this->exec_deleteArticleFromCart();
            // [CMD] Default action: allow additional controller hook or process default action (get quantity of article in current shopping cart and display article infobox)
            } else { 
                // HOOK for alternative setting of an article's data at checkout
                if (($hookObj = tx_pttools_div::hookRequest($this->extKey, 'pi2_hooks', 'mainControllerHook')) !== false) {
                    $content .= $hookObj->mainControllerHook($this); // use hook method if hook has been found
                                        // IMPORTANT: this hook must include the default action below, too!
                // default action: get quantity of article in current shopping cart and display article infobox 
                } else {              
                    $content .= $this->exec_defaultAction();
                }
            }
            
            
            // ********** DEFAULT PLUGIN FINALIZATION ********** 
            
            $this->cartObj->store(); // save current shopping cart to session
            
            
        } catch (Exception $excObj) {
            
            // if an exception has been catched, handle it and overwrite plugin content with error message
            if (method_exists($excObj, 'handle')) {
                $excObj->handle();    
            }
            $content = '<i>'.($excObj instanceof tx_pttools_exception) ? $excObj->__toString() : $this->pi_getLL('exception_catched').'</i>';
            
        }
        
        // reset the timezone to the "old" timezone
        date_default_timezone_set($previousTimeZone);
        
        // DEV ONLY: reset error reporting level
        if ($this->extConfArr['prodEnv'] == 0 && $GLOBALS['errStrict'] == 1 && isset($oldErrorReportingLevel)) {
            error_reporting($oldErrorReportingLevel);
        }
        
        return $this->pi_wrapInBaseClass($content);
        
        
    } // end fuction main
    
    
    
    /***************************************************************************
     *   BUSINESS LOGIC METHODS: CONTROLLER ACTIONS
     **************************************************************************/
     
    /**
     * Controller default action: get quantity of article in current shopping cart and display article infobox
     *
     * @param   boolean     flag whether a redirect to the current page should be executed (true: enables other plugins placed above this one to process the results of this plugin's actions) or if the plugin should be displayed without redirect (false: default)
     * @return  string      HTML plugin content for output on the page
     * @global  integer     $GLOBALS['TSFE']->id: UID of the current page
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-07-10 (based on previous code code from main() method)
     */
    protected function exec_defaultAction($selfRedirect=false) {
        
        $content = '';
        trace('[CMD] '.__METHOD__);
            
        // get quantity of article in current shopping cart
        $cartArticleObj = $this->cartObj->getItem($this->articleObj->get_id());
        $cartArticleQty = (integer)(isset($cartArticleObj) && is_object($cartArticleObj) ? $cartArticleObj->get_quantity() : 0);
        trace($cartArticleQty, 0, '$cartArticleQty for article '.$this->articleObj->get_id().' IN CART');
        
        // save current page id to session (used for possible return from shopping cart) - if _not_ in single view mode only
        if ($this->isArticleSingleViewMode != 1) {
            tx_pttools_sessionStorageAdapter::getInstance()->store(tx_ptgsashop_lib::SESSKEY_LASTORDERPAGE, $GLOBALS['TSFE']->id);
        }
        
        // redirect to the current page enables other plugins placed above this one to process the session results of this plugin's actions
        if ($selfRedirect == true) {
            $this->cartObj->store(); // save current shopping cart to session
            tx_pttools_div::localRedirect($this->formActionSelf);
        }
        
        // add article infobox with order button to the page's content  
        $content .= $this->displayArticleInfobox($cartArticleQty);
        return $content;
        
    }
     
    /**
     * Controller action: add article to shopping cart (if not initiated by page reload)
     *
     * @param   void
     * @return  void        (redirects to the current page)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-07-10 (based on previous code code from main() method)
     */
    protected function exec_addArticleToCart() {
        
        $content = '';
        trace('[CMD] '.__METHOD__);
        
        // check for cookies (required for session storage)
        tx_pttools_div::checkCookies($this);
        
        // add article to cart (if not initiated by page reload)
        if ($this->formReloadHandler->checkToken($this->piVars['__formToken']) == true) {
            $this->cartObj->addItem($this->articleObj);
        }
            
        // HOOK: allow multiple hooks to add required action
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi2_hooks']['exec_addArticleToCartHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi2_hooks']['exec_addArticleToCartHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $hookObj->exec_addArticleToCartHook($this); // $this is passed as a reference (since PHP5) and can be manipulated in the hook method
            }
        }
        
        // execute default action (with redirect)
        $content = $this->exec_defaultAction(true);  // this should redirect to the current page
        return $content;
        
    }
     
    /**
     * Controller action: remove article from shopping cart (if not initiated by page reload)
     *
     * @param   void
     * @return  string      HTML plugin content for output on the page
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-02-27
     */
    protected function exec_deleteArticleFromCart() {
        
        $content = '';
        trace('[CMD] '.__METHOD__);
        
        if ($this->formReloadHandler->checkToken($this->piVars['__formToken']) == true) {
            $this->cartObj->deleteItem($this->articleObj->get_id());
        }
        
        // execute default action (with redirect)
        $content = $this->exec_defaultAction(true);  // this redirects to the current page
        return $content;
        
    }
    
    
    
    /***************************************************************************
     *   DISPLAY METHODS
     **************************************************************************/
    
    /**
     * Generates and returns the HTML code of an article infobox with order button
     *
     * @param   integer     article quantity in the user's current shopping cart session
     * @global  integer     $GLOBALS['TSFE']->id: UID of the current page
     * @global  object      $GLOBALS['TSFE']->tmpl: t3lib_TStemplate Object
     * @return  string      HTML code of an article infobox with order button
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-17 (non-template-based version generateInfoboxHTML(): 2005-03-03)
     */
    protected function displayArticleInfobox($cartArticleQty) { 
        
        $markerArray = $this->getMarkerArrayFromArticle($this->articleObj, $cartArticleQty);
        
        // return prepared template to display
        $smarty = new tx_pttools_smartyAdapter($this);
        foreach ($markerArray as $markerKey=>$markerValue) {
            $smarty->assign($markerKey, $markerValue);
        }
        $confDisplayTemplate = ($this->isArticleSingleViewMode == 1 ? $this->conf['templateFileArticleSingleView'] : $this->conf['templateFileArticleBox']);
        $filePath = $smarty->getTplResFromTsRes($confDisplayTemplate);
        trace($filePath, 0, 'Smarty template resource $filePath');
        return $smarty->fetch($filePath);
        
    }
    
    /**
     * Returns a filled marker array for an article object
     *
     * @param 	tx_ptgsashop_baseArticle 	article object
     * @param 	int							article quantity in the user's current shopping cart session
     * @return 	array						marker array
     * @author 	Rainer Kuhn <kuhn@punkt.de>, Fabrizio Branca <branca@punkt.de>
     * @since	2008-01-21 (based on former displayArticleInfobox() since 2006-03-17)
     */
    protected function getMarkerArrayFromArticle(tx_ptgsashop_baseArticle $articleObj, $cartArticleQty) { 

        $markerArray = array();
        
        // define form action target and form elements prefix depending on extension configuration
        $formAction = $this->formActionSelf;
        $formPrefixId = $this->prefixId;
        if ($this->conf['addToCartAction'] == 1 || $this->conf['enableArticleRelations'] == 1) { 
            $formAction = $this->pi_getPageLink($this->conf['shoppingcartPage']);
            $formPrefixId = self::ARTICLECONFIRMATION_CLASS_NAME;
        }
        
        
        // assign template placeholders for default pt_gsashop template
        $markerArray['faction_addToCart'] = $formAction;
        $markerArray['class_tableWidth'] = ($this->conf['articleDisplayBoxdefault'] == 1 ? ' '.$this->pi_getClassName('boxdefault') : '');
        
        if ($this->conf['articleDisplayDescription'] == 1) {
            $markerArray['cond_articleDisplayDescription'] = true;
            $markerArray['articleDescription'] = tx_pttools_div::htmlOutput($articleObj->get_description());
        }
        if ($this->conf['articleDisplayArticleno'] == 1) {
            $markerArray['cond_articleDisplayArticleno'] = true;
            $markerArray['ll_artNo'] = $this->pi_getLL('artNo');
            $markerArray['articleNo'] = tx_pttools_div::htmlOutput($articleObj->get_artNo());
        }
        if ($this->conf['articleDisplayPrice'] == 1) {
            $markerArray['cond_articleDisplayPrice'] = true;
            $markerArray['ll_price'] = $this->pi_getLL('price');
            
            $articlePrice = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString(
                                $articleObj->getDisplayPrice($this->isNetPriceDisplay), 
                                $this->conf['currencyCode']
                            ));            
                            
            // HOOK: allow multiple hooks to modify article display price
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi2_hooks']['displayArticleInfobox_articlePriceHook'])) {
                foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi2_hooks']['displayArticleInfobox_articlePriceHook'] as $className) {
                    $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                    $articlePrice = $hookObj->displayArticleInfobox_articlePriceHook($this); // object params are passed as a reference (since PHP5) and can be manipulated in the hook method
                }
            }
            $markerArray['articlePrice'] = $articlePrice;
            
            // display article's fixed cost if set for this article
            if ($articleObj->getFixedCost($this->isNetPriceDisplay) > 0) {
                $markerArray['cond_fixedCost'] = true; 
                $markerArray['artFixedCostTotal'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString(
                                                          $articleObj->getFixedCost($this->isNetPriceDisplay), 
                                                          $this->conf['currencyCode']
                                                    ));
                $markerArray['ll_artfixedcost_info'] = $this->pi_getLL('artfixedcost_info'); 
            }
        }
        
            // display of pricescales: 1 = only if available, 2 = always, other values = never (added by Fabrizio Branca 2007/12/11)
        if (($this->conf['articleDisplayPricescales'] == 1) && ($articleObj->get_scalePriceCollectionObj()->count() > 1) 
            || ($this->conf['articleDisplayPricescales'] == 2)) {
            $markerArray['cond_articleDisplayPricescales'] = true;
            $markerArray['ll_pricescales_qty'] = $this->pi_getLL('pricescales_qty');
            $markerArray['ll_pricescales_price'] = $this->pi_getLL('pricescales_price');
            $tmpPriceCalcQty = $articleObj->get_priceCalcQty();
            $articlePriceScales = array();
            foreach ($articleObj->get_scalePriceCollectionObj() as $scalePriceObj){
                $qty = $scalePriceObj->get_quantity();
                $articleObj->set_priceCalcQty($qty);
                $tmpArticlePrice = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString(
                                       $articleObj->getDisplayPrice($this->isNetPriceDisplay), 
                                       $this->conf['currencyCode']
                                   ));
                $articlePriceScales[$qty] = $tmpArticlePrice;
            }
            ksort($articlePriceScales);
            $articleObj->set_priceCalcQty($tmpPriceCalcQty);
            
            $markerArray['articlePriceScales'] = $articlePriceScales;
        }
        
        $markerArray['fname_articleId'] = $formPrefixId.'[gsa_id]';
        $markerArray['fval_articleId'] = tx_pttools_div::htmlOutput($articleObj->get_id());
        $markerArray['fhidden_reloadHandlerToken'] = $this->formReloadHandler->returnTokenHiddenInputTag($formPrefixId.'[__formToken]');
        if ($this->conf['articleDisplayOrderbutton'] == 1) {
        	$markerArray['cond_articleDisplayOrderbutton'] = true;
        	$markerArray['imgsrc_cartButton'] = $GLOBALS['TSFE']->tmpl->getFileName($this->conf['imgAddToCartButtonArticleBox']);
	        $markerArray['fname_cartButton'] = $formPrefixId.'[cart_button]';
	        $markerArray['ll_titleCartButton'] = $this->pi_getLL('add_to_cart');
        }
        if ($this->conf['articleDisplayCartqty'] == 1) {
            $markerArray['cond_articleDisplayCartqty'] = true;
            $markerArray['cartArticleQty'] = tx_pttools_div::htmlOutput($cartArticleQty);
            $markerArray['ll_titleCartQty'] = $this->pi_getLL('qty_cart');
        }
        if ($this->conf['articleDisplayRemovebutton'] == 1) {
            $markerArray['cond_articleDisplayRemovebutton'] = true;
            if ($cartArticleQty > 0) {
                $markerArray['cond_article_in_cart'] = true;
                $markerArray['fname_removeButton'] = $formPrefixId.'[remove_button]';
                $markerArray['imgsrc_removeButton'] = $GLOBALS['TSFE']->tmpl->getFileName($this->conf['imgRemoveFromCartButtonArticleBox']);
                $markerArray['ll_titleRemoveButton'] = $this->pi_getLL('remove_from_cart');
            } else {
                $markerArray['cond_article_in_cart'] = false;
                $imgDataRemoveButton = getimagesize($GLOBALS['TSFE']->tmpl->getFileName($this->conf['imgRemoveFromCartButtonArticleBox']));
                $markerArray['emptygif_attributes'] = $imgDataRemoveButton[3];
            }
        }
        
        if ($this->conf['articleDisplayMatch1'] == 1 && strlen($articleObj->get_match1()) > 0) {
            $markerArray['cond_articleDisplayMatch1'] = true;
            $markerArray['articleMatch1'] = tx_pttools_div::htmlOutput($articleObj->get_match1());
        }
        if ($this->conf['articleDisplayMatch2'] == 1 && strlen($articleObj->get_match2()) > 0) {
            $markerArray['cond_articleDisplayMatch2'] = true;
            $markerArray['articleMatch2'] = tx_pttools_div::htmlOutput($articleObj->get_match2());
        }
        if ($this->conf['articleDisplayDeftext'] == 1 && strlen($articleObj->get_defText()) > 0) {
            $markerArray['cond_articleDisplayDeftext'] = true;
            if ($this->conf['enableRteForArticleText'] == 1) {
                $markerArray['articleDefText'] = ($this->conf['enableXssSecurityForArticleText'] == 0 ? $this->pi_RTEcssText($articleObj->get_defText()) : tx_pttools_div::htmlOutput($this->pi_RTEcssText($articleObj->get_defText())));
            } else {
                $markerArray['articleDefText'] = nl2br($this->conf['enableXssSecurityForArticleText'] == 0 ? $articleObj->get_defText() : tx_pttools_div::htmlOutput($articleObj->get_defText()));
            }
        }
        if ($this->conf['articleDisplayAlttext'] == 1 && strlen($articleObj->get_altText()) > 0) {
            $markerArray['cond_articleDisplayAlttext'] = true;
            if ($this->conf['enableRteForArticleText'] == 1 ) {
                $markerArray['articleAltText'] = ($this->conf['enableXssSecurityForArticleText'] == 0 ? $this->pi_RTEcssText($articleObj->get_altText()) : tx_pttools_div::htmlOutput($this->pi_RTEcssText($articleObj->get_altText())));
            } else {
                $markerArray['articleAltText'] = nl2br($this->conf['enableXssSecurityForArticleText'] == 0 ? $articleObj->get_altText() : tx_pttools_div::htmlOutput($articleObj->get_altText()));
            }
        }
        if ($this->conf['articleDisplayImg'] == 1 && (strlen($articleObj->get_imageWebFilePath()) > 0 || $articleObj->get_articleImageCollectionObj()->count() > 0) ) {
            $markerArray['cond_articleDisplayImg'] = true;
            
            // old images from GSA
            $markerArray['imgsrc_articleImg'] = tx_pttools_div::htmlOutput($articleObj->get_imageWebFilePath());
            $markerArray['width_articleImg'] = tx_pttools_div::htmlOutput($articleObj->get_imageWebWidth());
            $markerArray['height_articleImg'] = tx_pttools_div::htmlOutput($articleObj->get_imageWebHeight());
            // new images managed by TYPO3 (added by Fabrizio Branca 2007/12)
            $markerArray['images'] = array();
            foreach ($articleObj->get_articleImageCollectionObj() as $articleImageObj) {
                $this->conf['imageConf.']['file'] = 'uploads/pics/'.$articleImageObj->get_path();                   // TODO: (Fabrizio) do not hardcode the path! (ry44)    
                
                $markerArray['images'][] = array('img' => $GLOBALS['TSFE']->cObj->IMAGE($this->conf['imageConf.']), 
                                                 'description' => $articleImageObj->get_description());;
            }
        }
        if ($this->conf['articleDisplayCartlink'] == 1) {
            $markerArray['cond_articleDisplayCartlink'] = true;
            $markerArray['href_cartPage'] = $this->pi_getPageLink($this->conf['shoppingcartPage']);
            $markerArray['ll_cartLink'] = $this->pi_getLL('view_cart');
        }
        
        if ($this->conf['articleDisplayUrl'] == 1) {
            $markerArray['cond_articleDisplayUrl'] = true;
            $markerArray['singleViewUrl'] = $articleObj->getFePageLink();
        }
        
        
        // assign additional template placeholders (independent from default pt_gsashop template)
        $markerArray['articleNetPrice']   = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString(
                                                $articleObj->getDisplayPrice(true), 
                                                $this->conf['currencyCode']
                                            ));
        $markerArray['articleGrossPrice'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString(
                                                $articleObj->getDisplayPrice(false), 
                                                $this->conf['currencyCode']
                                            ));  
               // get net/gross price scales                             
        $tmpPriceCalcQty = $articleObj->get_priceCalcQty();
        $articleNetPriceScales = array();
        $articleGrossPriceScales = array();
        foreach ($articleObj->get_scalePriceCollectionObj() as $scalePriceObj){
            $qty = $scalePriceObj->get_quantity();
            $articleObj->set_priceCalcQty($qty);
            $articleNetPriceScales[$qty]   = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString(
                                                   $articleObj->getDisplayPrice(true), 
                                                   $this->conf['currencyCode']
                                               ));
            $articleGrossPriceScales[$qty] =   tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString(
                                                   $articleObj->getDisplayPrice(false), 
                                                   $this->conf['currencyCode']
                                               ));
        }
        ksort($articleNetPriceScales);
        ksort($articleGrossPriceScales);
        $articleObj->set_priceCalcQty($tmpPriceCalcQty);
        $markerArray['articleNetPriceScales'] = $articleNetPriceScales;
        $markerArray['articleGrossPriceScales'] = $articleGrossPriceScales;
        
        // userfields
        $markerArray['userfield'] = array(
            '1' => tx_pttools_div::htmlOutput($articleObj->get_userfield01()),
            '2' => tx_pttools_div::htmlOutput($articleObj->get_userfield02()),
            '3' => tx_pttools_div::htmlOutput($articleObj->get_userfield03()),
            '4' => tx_pttools_div::htmlOutput($articleObj->get_userfield04()),
            '5' => tx_pttools_div::htmlOutput($articleObj->get_userfield05()),
            '6' => tx_pttools_div::htmlOutput($articleObj->get_userfield06()),
            '7' => tx_pttools_div::htmlOutput($articleObj->get_userfield07()),
            '8' => tx_pttools_div::htmlOutput($articleObj->get_userfield08()),
        );
        
        // HOOK: allow multiple hooks to manipulate $markerArray
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi2_hooks']['displayArticleInfobox_MarkerArrayHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi2_hooks']['displayArticleInfobox_MarkerArrayHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $markerArray = $hookObj->displayArticleInfobox_MarkerArrayHook($this, $markerArray, $articleObj); // $this is passed as a reference (since PHP5) and can be manipulated in the hook method
            }
        }
        
        return $markerArray;
    }  
    
    
    
    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/ 

    /**
     * Returns the article object
     *
     * @param   void
     * @return  tx_ptgsashop_baseArticle  article object
     * @author  Joachim Mathes <mathes@punkt.de>
     * @since   2009-09-04
     */
    public function get_articleObj() {

        return $this->articleObj;

    }

    /**
     * Returns the cart object
     *
     * @param   void
     * @return  tx_ptgsashop_cart  cart object
     * @author  Joachim Mathes <mathes@punkt.de>
     * @since   2009-09-04
     */
    public function get_cartObj() {

        return $this->cartObj;

    }

    /**
     * Returns the customer object
     *
     * @param   void
     * @return  tx_ptgsashop_cart  cart object
     * @author  Joachim Mathes <mathes@punkt.de>
     * @since   2009-09-04
     */
    public function get_customerObj() {

        return $this->customerObj;

    }


    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/pi2/class.tx_ptgsashop_pi2.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/pi2/class.tx_ptgsashop_pi2.php']);
}

?>
