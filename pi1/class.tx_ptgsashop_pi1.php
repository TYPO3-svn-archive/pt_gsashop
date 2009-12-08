<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2005-2006 Rainer Kuhn (kuhn@punkt.de)
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
 * Frontend Plugin 'GSA Shop: Shopping cart' for the 'pt_gsashop' extension.
 *
 * $Id: class.tx_ptgsashop_pi1.php,v 1.132 2009/07/28 08:18:50 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2005-03-07
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
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_articleCollection.php';// GSA shop article collection class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_sessionOrder.php';  // GSA shop session order class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_sessionFeCustomer.php';  // GSA shop frontend customer class

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_smartyAdapter.php';  // Smarty template engine adapter
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_formReloadHandler.php'; // web form reload handler class
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_msgBox.php'; // message box class
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_sessionStorageAdapter.php'; // storage adapter for TYPO3 _browser_ sessions
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_finance.php'; // library class with finance related static methods

/**
 * Debugging config for development
 */
#$trace     = 1; // (int) trace options @see tx_pttools_debug::trace() [for local temporary debugging use only, please COMMENT OUT this line if finished with debugging!]
#$errStrict = 1; // (bool) set strict error reporting level for development (requires $trace to be set to 1)  [for local temporary debugging use only, please COMMENT OUT this line if finished with debugging!]



// debugging output for development (uncomment to use)
#trace(TYPO3_db);
#trace($TYPO3_CONF_VARS);
#trace(t3lib_div::GPvar('tx_ptgsashop_pi1'));
#trace($_POST, 0, '$_POST');
#trace($GLOBALS['TSFE'], 0, '$GLOBALS[TSFE]');
#trace($GLOBALS['TSFE']->fe_user, 0, '$GLOBALS[TSFE]->fe_user');
#trace($GLOBALS['TSFE']->fe_user->sesData, 0, '$GLOBALS[TSFE]->fe_user->sesData');



/**
 * Provides a browser-session based online shopping cart as a frontend plugin
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2005-03-07
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_pi1 extends tslib_pibase {
    
    /**
     * tslib_pibase (parent class) properties
     */
    public $extKey = 'pt_gsashop';    // The extension key.
    public $prefixId = 'tx_ptgsashop_pi1';    // Same as class name
    public $scriptRelPath = 'pi1/class.tx_ptgsashop_pi1.php';    // Path to this script relative to the extension dir.
    
    /**
     * tx_ptgsashop_pi1 properties
     */
    
    /**
     * @var array basic extension configuration data from localconf.php (configurable in Extension Manager)
     */
    protected $extConfArr = array(); 

    /**
     * @var string address for HTML forms' 'action' attributes to send a form of this page to itself
     */
    protected $formActionSelf = ''; 
    
    /**
     * @var tx_pttools_formReloadHandler web form reload handler object
     */
    protected $formReloadHandler = NULL; 
    
    /**
     * @var tx_ptgsashop_cart shopping cart object
     */
    protected $cartObj = NULL;
    
    /**
     * @var tx_ptgsashop_sessionFeCustomer frontend customer object (FE user who uses this plugin)
     */
    protected $customerObj = NULL;
    
    /**
     * @var bool flag wether this plugin is called by a FE user who is legitimated to use net prices (0=gross prices, 1=net prices)
     */
    protected $isNetPriceDisplay = 0;
    
    
    /**
     * tx_ptgsashop_pi1 properties used for article relation checks only
     */
    
    /**
     * @var tx_ptgsashop_articleCollection article collection to check in all article relation check methods
     */
    protected $articlesToCheckCollObj = NULL;

    /**
     * @var bool Flag wether the maximum allowed amount of the requested article is already in the cart
     */
    protected $artrelMaxAmountFlag = false;

    /**
     * @var array Array of exclusion article objects needed to delete to order requested article
     */
    protected $artrelExclusionArticleArr = array(); 
    
    /**
     * @var array Array of required article objects needed to order before ordering the requested article
     */
    protected $artrelRequiredArticleArr = array(); 
    
    
    /**
     * Class Constants
     */
    const ORDERPLUGIN_CLASS_NAME = 'tx_ptgsashop_pi3'; // (string) class name of the order plugin to use combined with this plugin
    const MSGBOX_SESSION_KEY_NAME = 'tx_ptgsashop_pi1_msgBox'; // (string) session key name to store pi1 related message box in session
    
    
    
    /***************************************************************************
     *   MAIN
     **************************************************************************/
    
    /**
     * Main method of the shopping cart plugin: Prepares properties and instances, interprets submit buttons to control plugin behaviour
     *
     * @param   string      HTML-Content of the plugin to be displayed within the TYPO3 page
     * @param   array       Global configuration for this plugin (mostly done in Constant Editor/TS setup)
     * @return  string      HTML plugin content for output on the page (if not redirected before)
     * @global  integer     $GLOBALS['TSFE']->id: UID of the current page
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-03-07
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
        #trace($this->conf, 0, '$this->conf'); // extension config data (mainly from TS setup/Constant Editor)
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
            
            // ********** CHECK PLUGIN REQUIREMENTS (required for all further steps) **********
            
            // check for cookies (required for session storage)
            tx_pttools_div::checkCookies($this);
            
            
            // ********** SET PLUGIN-OBJECT PROPERTIES **********
            
            $this->formActionSelf    = $this->pi_getPageLink($GLOBALS['TSFE']->id); // set self url for HTML form action attributes
            $this->formReloadHandler = new tx_pttools_formReloadHandler; // set form reload handler object
            $this->cartObj           = tx_ptgsashop_cart::getInstance(); // get unique instance (Singleton) of shopping cart (filled with session items)
            $this->customerObj       = tx_ptgsashop_sessionFeCustomer::getInstance(); // get unique instance (Singleton) of current FE customer
            $this->isNetPriceDisplay = $this->customerObj->getNetPriceLegitimation(); // set flag wether this plugin has been called by a FE user who is legitimated to use net prices
            
            // properties used for article relation checks only
            if ($this->conf['enableArticleRelations'] == 1) { 
                $this->articlesToCheckCollObj = $this->returnArticlesToCheckCollObj(); // set article collection to check in all article relation check methods
            }
            
            
            // ********** CONTROLLER: execute approriate method for any action command (retrieved form buttons/GET vars) **********
            
            // [CMD] Cart checkout
            if (isset($this->piVars['cart_checkout_button']) || $this->piVars['checkOut']==1) {                
                $content .= $this->exec_checkout();
            // [CMD] Clear shopping cart
            } elseif (isset($this->piVars['cart_clear_button'])) {
                $content .= $this->exec_clearCart();
            // [CMD] Update cart units (item amount update, item delete)
            } elseif (isset($this->piVars['cart_upd_submitted'])) {                
                $content .= $this->exec_updateCartUnits();
            // [CMD] Add article if max amount check is ok (MAYBE COMING FROM PI2 depending on extension TS configuration)
            } elseif (isset($this->piVars['cart_button']) && isset($this->piVars['gsa_id'])) {                
                $content .= $this->exec_checkArticleBeforeAdding();
            // [CMD] Delete article if article relation check is ok (MAYBE COMING FROM PI2 depending on extension TS configuration)
            } elseif (isset($this->piVars['remove_button']) && isset($this->piVars['gsa_id'])) {                
                $content .= $this->exec_checkArticleBeforeDeleting();
            // [CMD] Default action: allow additional controller hook or process default action (update and display shopping cart)
            } else { 
                // HOOK for alternative setting of an article's data at checkout
                if (($hookObj = tx_pttools_div::hookRequest($this->extKey, 'pi1_hooks', 'mainControllerHook')) !== false) {
                    $content .= $hookObj->mainControllerHook($this); // use hook method if hook has been found
                                        // IMPORTANT: this hook must include the default action below, too!
                                        
                // default action: update and display shopping cart 
                } else {              
                    $content .= $this->exec_defaultAction();
                }
            }
            
            
            // ********** DEFAULT PLUGIN FINALIZATION ********** 
            
            $this->cartObj->store(); // save current shopping cart to session (customer is _not_ stored here)
            
            
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
     * Controller default action: display updated shopping cart
     *
     * @param   void
     * @return  string      HTML plugin content for output on the page
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-07-10 (based on code from 2005-03-07)
     */
    protected function exec_defaultAction() {
        
        $content = '';
        $msgBox = '';
        trace('[CMD] '.__METHOD__);
        
        // get up-to-date article and pricing data
        $this->cartObj->updateItemsData($this->customerObj->get_priceCategory(), $this->customerObj->get_gsaMasterAddressId()); 
        
        // check for session stored message box (if found, display it and unset session key)
        $sessionMsgBox = tx_pttools_sessionStorageAdapter::getInstance()->read(self::MSGBOX_SESSION_KEY_NAME);
        if (strlen($sessionMsgBox) > 0) {
            $msgBox = $sessionMsgBox;
            tx_pttools_sessionStorageAdapter::getInstance()->delete(self::MSGBOX_SESSION_KEY_NAME);
        }
        
        $content = $this->displayShoppingCart($msgBox);
        return $content;
        
    }
     
    /**
     * Controller action: check article relations (if enabled) and redirect to order overview or display login form (depending on login status)
     *
     * @param   void
     * @return  string      HTML plugin content for output on the page (if not redirected before)
     * @global  boolean     $GLOBALS['TSFE']->loginUser: Flag indicating if a front-end user is logged in
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-07-10 (based on previous code code from main() method)
     */
    protected function exec_checkout() {
        
        $content = '';
        $artrelLimitationsFound = false;
        trace('[CMD] '.__METHOD__);
        
        // FE user is already/still logged in: check cart, remove "order submitted" flag from session and redirect to order overview
        if ($GLOBALS['TSFE']->loginUser == 1) {
            
            // check if FE user is GSA enabled
            if ($this->customerObj->getIsGsaAddressEnabled() != 1) {
                
                // create current page's URL to return after login or registration (GET-Param is used to remember on return from where the user came to the login screen)
                $selfUrl = $this->pi_getPageLink($GLOBALS['TSFE']->id, '', array($this->prefixId.'[checkOut]'=>'1'));
                $content = $this->pi_linkToPage($this->pi_getLL('login_not_gsa_enabled'), $this->conf['feUserRegPage'], '', array('backURL'=>$selfUrl));
                
            } else {
                
                // check if FE user is an approved GSA online customer
                if ($this->customerObj->getIsGsaOnlineCustomer() != 1) {
                    
                    $content = $this->pi_getLL('login_not_gsa_approved');
                    
                } else {
            
                    // if article relations are enabled: process article relations check for each cart article
                    if ($this->conf['enableArticleRelations'] == 1) {
                        foreach ($this->cartObj as $articleObj) { // iterate through the cart's articles and process relation check for each article unless a problem is found
                            $artrelLimitationsFound = $this->checkArticleRelations($articleObj);
                            if ($artrelLimitationsFound != false) { // if a problem has been found for one article: stop loop processing of cart articles and display message for this article
                                $content .= $this->displayArticleConfirmation($articleObj);
                                break;
                            }
                        }
                    }  
                    
                    // if no article relation limitations are found: set articles' delivery start date, remove "order submitted" flag from session and redirect to order overview   
                    if ($artrelLimitationsFound == false) {
                        trace('CART CHECKOUT: user is logged-in => redirect to order overview');
                        
                        // set delivery start date for all articles and save updated shopping cart to session
                        foreach ($this->cartObj as $articleObj) {
                            // HOOK for alternative setting of an article's data at checkout
                            if (($hookObj = tx_pttools_div::hookRequest($this->extKey, 'pi1_hooks', 'exec_checkout_articleDataHook')) !==  false) {
                                $hookObj->exec_checkout_articleDataHook($this, $articleObj); // use hook method if hook has been found
                            // default setting 
                            } else {
                                $artdelStartTs = time();  ### TODO: set reasonable timestamp here when implementing articles' delivery dates
                                $articleObj->set_artdelStartTs($artdelStartTs);
                            }
                        }
                        $this->cartObj->store();
                        
                        // remove "order submitted" flag from session and redirect to order overview 
                        tx_pttools_sessionStorageAdapter::getInstance()->delete(tx_ptgsashop_lib::SESSKEY_ORDERSUBMITTED);
                        tx_pttools_div::localRedirect($this->pi_getPageLink($this->conf['orderPage'], '', array(self::ORDERPLUGIN_CLASS_NAME.'[checkOut]'=>1)));
                        
                    }
                }
            }
        
        // FE user is not logged in: display login form and link to user registration (or execute appropriate hook)
        } else {
             trace('CART CHECKOUT: user is not logged-in => display login form or execute appropriate hook');
             
            // HOOK for alternative action (including possible redirection!)
            if (($hookObj = tx_pttools_div::hookRequest($this->extKey, 'pi1_hooks', 'exec_checkout_loginHook')) !== false) {
                $content = $hookObj->exec_checkout_loginHook($this); // use hook method if hook has been found - NOTICE: this may redirect to another page!
            // default action: 
            } else {
                $content = $this->displayUserLogin('checkOut');  // 'checkOut' is used as GET param at return from user registration
            }
            
        }
        
        return $content;
        
    }
     
    /**
     * Controller action: clear complete shopping cart
     *
     * @param   void
     * @return  string      HTML plugin content for output on the page (or void on redirection = default)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-23 (based on code from 2005-03-07)
     */
    protected function exec_clearCart() {
        
        $content = '';
        trace('[CMD] '.__METHOD__);
        
        $this->cartObj->clearItems();
        
        // clear order additionally (if existent in session)
        $sessionOrderObj = tx_pttools_sessionStorageAdapter::getInstance()->read(tx_ptgsashop_sessionOrder::SESSION_KEY_NAME);
        if (is_object($sessionOrderObj) && $sessionOrderObj instanceof tx_ptgsashop_sessionOrder) {
            $sessionOrderObj->delete();
        }
        
        // redirect to the current page (enables other plugins placed above this one to process the session results of this plugin's actions)
        $this->storeAndSelfRedirect();
        
        // fallback if above redirect fails
        $content = $this->displayShoppingCart();
        return $content;
        
    }
     
    /**
     * Controller action: update shopping cart units depending on the usage of the appropriate form buttons (article update/delete)
     *
     * @param   void
     * @return  string      HTML plugin content for output on the page (or void on redirection = default)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-23 (based on code of former method updateShoppingCartUnits() from 2005-03-08)
     */
    protected function exec_updateCartUnits() {
        
        $content = '';
        $msgBox = ''; 
        trace('[CMD] '.__METHOD__);
        
        $msg = $this->updateCart();
        
        // redirect to the current page (enables other plugins placed above this one to process the session results of this plugin's actions) and keep possibly existing message
        $msgBoxObj = ($msg == '' ? NULL : new tx_pttools_msgBox('warning', $msg));
        $this->storeAndSelfRedirect($msgBoxObj);
        
        // fallback if above redirect fails
        $content = $this->displayShoppingCart($msgBox);
        return $content;
        
    }
     
    /**
     * Controller action: process article relation check and add article if check is passed (additionally this may redirect to last order page)
     *
     * @param   void        
     * @return  string      HTML plugin content for output on the page (or void on redirection)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-10-20 (based on code of former method tx_ptgsashop_pi5::exec_defaultAction() from 2006-08-21)
     */
    protected function exec_checkArticleBeforeAdding() {
              
        $content = '';
        $articleAdded = false; 
        trace('[CMD] '.__METHOD__);
                
        $articleObj = tx_ptgsashop_articleFactory::createArticle(  // instantiate new article with given uid 
                            intval($this->piVars['gsa_id']), 
                            $this->customerObj->get_priceCategory(), 
                            $this->customerObj->get_gsaMasterAddressId(), 
                            1
                      ); 
        trace($articleObj, 0, '$articleObj');
        
        if ($this->formReloadHandler->checkToken($this->piVars['__formToken']) == true) {
                   
            // if no appropriate article relations/limitations are found: add article to cart and execute "Put into Cart" action
            if ($this->conf['enableArticleRelations'] == 0 || $this->checkArtrelMaxAmount($articleObj) == false) {
                $this->cartObj->addItem($articleObj);
                $articleAdded = true;
            
                // HOOK: allow multiple hooks to add required action
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi1_hooks']['exec_checkArticleBeforeAddingHook'])) {
                    foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi1_hooks']['exec_checkArticleBeforeAddingHook'] as $className) {
                        $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                        $hookObj->exec_checkArticleBeforeAddingHook($this, $articleObj); // object params are passed as a reference (since PHP5) and can be manipulated in the hook method
                    }
                }
                
                // action to be executed after an article has successfully been put into the shopping cart: Return to order page depending on TS configuration
                if ($this->conf['addToCartAction'] == 2 && $this->piVars['is_artrel_addition'] != 1) {
                    $this->cartObj->store(); // save current shopping cart to session
                    $lastOrderPage = tx_pttools_sessionStorageAdapter::getInstance()->read(tx_ptgsashop_lib::SESSKEY_LASTORDERPAGE);
                    tx_pttools_div::localRedirect($this->pi_getPageLink($lastOrderPage));
                }
            }
            
            // if an article has been added: redirect to the current page (enables other plugins placed above this one to process the session results of this plugin's actions) and keep existing message
            if ($articleAdded == true) {
                $msgBoxObj = new tx_pttools_msgBox('info', tx_pttools_div::htmlOutput(sprintf($this->pi_getLL('artrel_msgbox_added_to_cart'), $articleObj->get_description())));
                $this->storeAndSelfRedirect($msgBoxObj);
            }
            // if no article has been added: display appropriate message
            $content .= $this->displayArticleConfirmation($articleObj, 1);
            
            
        } else {
            $content .= $this->displayShoppingCart();
        }
        
        return $content;
        
    }
     
    /**
     * Controller action: process article relation check and delete article if check is passed (additionally this may redirect to last order page)
     *
     * @param   void        
     * @return  string      HTML plugin content for output on the page (or void on redirection)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-02-27
     */
    protected function exec_checkArticleBeforeDeleting() {
                 
        $content = '';
        $msgBox = ''; 
        $msg = '';
        trace('[CMD] '.__METHOD__);
        
        if ($this->formReloadHandler->checkToken($this->piVars['__formToken']) == true) {
            
            // process article relation check (if enabled)
            if ($this->conf['enableArticleRelations'] == 1) {
                $msg .= $this->checkArtrelCartFormRemoval($this->piVars['gsa_id']);
            }
            
            // if no appropriate article relations/limitations are found: delete article from cart and execute "cart modification" action 
            if ($msg == '') {
                $this->cartObj->deleteItem($this->piVars['gsa_id']);
                
                // Action to be executed after an article has been modified in the shopping cart (depending on TS configuration):
                // return to order page (if configured in TS)
                if ($this->conf['addToCartAction'] == 2) {
                    $this->cartObj->store(); // save current shopping cart to session
                    $lastOrderPage = tx_pttools_sessionStorageAdapter::getInstance()->read(tx_ptgsashop_lib::SESSKEY_LASTORDERPAGE);
                    tx_pttools_div::localRedirect($this->pi_getPageLink($lastOrderPage));
                // otherwise redirect to current page (enables other plugins placed above this one to process the session results of this plugin's actions)
                } else {
                    $this->storeAndSelfRedirect();
                }
            }
            
        }
            
        if ($msg != '') {
            $msgBoxObj = new tx_pttools_msgBox('warning', $msg);
            $msgBox = $msgBoxObj->__toString(); 
        }
        $content = $this->displayShoppingCart($msgBox);
                
        return $content;
        
    }
    
    
    
    /***************************************************************************
     *   BUSINESS LOGIC METHODS: GENERAL
     **************************************************************************/
     
    /**
     * Stores the session cart and redirects to current page with the possibility of keeping keep eventually existing message boxes.
     * Redirecting to the current page enables other plugins placed above this one to process the session results of this plugin's actions.
     *
     * @param   mixed       NULL or object of type tx_pttools_msgBox: message box to keep and to display on redirected page 
     * @return  void        (method should process a redirect, but returns message string on error)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-04-20
     */
    protected function storeAndSelfRedirect($msgBoxObj=NULL) {
        
        // save current shopping cart to session
        $this->cartObj->store(); 
        
        // if there is a messagebox keep it wirh the redirect, else redirect without messagebox
        if ($msgBoxObj instanceof tx_pttools_msgBox) {
            tx_pttools_div::localRedirect($this->formActionSelf, $msgBoxObj->__toString(), self::MSGBOX_SESSION_KEY_NAME); 
        } else {
            tx_pttools_div::localRedirect($this->formActionSelf);
        }
        
        // fallback if above redirect fails
        return 'redirect failed';
        
    }
    
    /**
     * Updates quantities of the articles by processing incoming form data and updates items data
     *
     * @param   void
     * @return  string  message
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-09-22 (based on code extracted from exec_updateCartUnits() from 2006-08-23)
     */
    protected function updateCart() {
        
        // iterate through the cart's articles and process appropriate action
        foreach ($this->cartObj as $artKey=>$artObj) {
                      
            // perform updates initiated by update button (or by pressing 'enter' in form elements)
            if (!isset($this->piVars['cart_del_button']) || !is_array($this->piVars['cart_del_button'])) {
                $artQty = tx_pttools_div::returnIntegerValue($this->piVars['qty.'.$artKey], 1);
                if ($artQty == 0) {
                    if ($this->conf['enableArticleRelations'] == 1) { 
                        $msg .= $this->checkArtrelCartFormRemoval($artKey);
                    }
                    // delete article with qty 0 only if no article relations error occured
                    if ($msg == '') {
                        $this->cartObj->deleteItem($artKey);
                    }
                } else {
                    if ($this->conf['enableArticleRelations'] == 1) { 
                        $msg .= $this->checkArtrelCartFormMaxAmount($artObj, $artQty); // &$artQty is passed by reference
                    }
                    $this->cartObj->updateItemQuantity($artKey, $artQty);
                }
            }
            
            // perform removals initiated by delete button
            if (isset($this->piVars['cart_del_button'][$artKey])) {
                // check article relations (if enabled)
                if ($this->conf['enableArticleRelations'] == 1) {
                    $msg .= $this->checkArtrelCartFormRemoval($artKey);
                }
                // delete article only if article relations check is passed
                if ($msg == '') {
                    $this->cartObj->deleteItem($artKey);
                }
                
            }
            
        } // end foreach
        
        // get up-to-date article and pricing data
        $this->cartObj->updateItemsData($this->customerObj->get_priceCategory());

        return $msg;
        
    }
     
    /**
     * Article relations: Returns the article collection object containing all articles to check in all article relation check methods
     *
     * @param   void        
     * @return  tx_ptgsashop_articleCollection      object of type tx_ptgsashop_articleCollection containing all articles to check in all article relation check methods
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-10-20 (based on code of former method tx_ptgsashop_pi5::returnArticlesToCheckCollObj() from 2006-09-27)
     */
    protected function returnArticlesToCheckCollObj() {
     
        $articlesToCheckCollObj = new tx_ptgsashop_articleCollection;
        
        foreach ($this->cartObj as $artObj) {
            $articlesToCheckCollObj->addItem(clone($artObj));
        }
            
        // HOOK: allow multiple hooks to manipulate the articles-to-check collection built from the cart
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi1_hooks']['returnArticlesToCheckCollObjHook'])) {
            trace($articlesToCheckCollObj, 0, '$articlesToCheckCollObj BEFORE HOOKING');
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi1_hooks']['returnArticlesToCheckCollObjHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $hookObj->returnArticlesToCheckCollObjHook($this, $articlesToCheckCollObj); // $articlesToCheckCollObj is passed as a reference (since PHP5) and can be manipulated in the hook method
            }
            trace($articlesToCheckCollObj, 0, '$articlesToCheckCollObj AFTER HOOKING');
        }
               
        return $articlesToCheckCollObj;
        
    }
     
     
    /**
     * Article relations: Checks for article relation based limitations for the requested article (article passed in param)
     *
     * @param   tx_ptgsashop_baseArticle      article to check, object of type tx_ptgsashop_baseArticle    
     * @return  boolean     FALSE if no appropriate article relation limitations are found, TRUE otherwise
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-10-20 (based on code of former method tx_ptgsashop_pi5::checkArticleRelations() from 2006-08-21)
     */
    protected function checkArticleRelations(tx_ptgsashop_baseArticle $articleObj) {
        
        $limitationsFound = false; // (boolean)
        
//        // do max amount check if article max amount value is set => ### TODO: remove? (max. amount check is now done seperately when adding an article to the cart)
//        if ($articleObj->get_artrelMaxAmount() > 0) {
//            $limitationsFound = $this->checkArtrelMaxAmount($articleObj);
//        }
        
        // do exclusion articles check if exclusion array is set
        if ($limitationsFound == false && sizeof($articleObj->get_artrelExclusionArr()) > 0) {
            $limitationsFound = $this->checkArtrelExclusion($articleObj);
        }
        
        // do required articles check if required array is set
        if ($limitationsFound == false && sizeof($articleObj->get_artrelRequiredArr()) > 0) {
            $limitationsFound = $this->checkArtrelRequired($articleObj);
        }
        
        return $limitationsFound;
        
    }
    
    /**
     * Article relation check for max amount of article to add: checks if the article to add to the cart/articlesToCheckCollection exceeds the max. article amount (if max. amount is set to > 0)
     *
     * @param   tx_ptgsashop_baseArticle      article to check, object of type tx_ptgsashop_baseArticle    
     * @return  boolean     FALSE if no appropriate article relation limitations are found, TRUE otherwise
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-10-20 (based on code of former method tx_ptgsashop_pi5::checkArticleRelations() from 2006-08-21)
     */
    protected function checkArtrelMaxAmount(tx_ptgsashop_baseArticle $articleObj) {
        
        $limitationsFound = false; // (boolean)
        
        // check if appropriate article is found in cart/articlesToCheckCollection
        if ($articleObj->get_artrelMaxAmount() > 0 && $this->articlesToCheckCollObj->getItem($articleObj->get_id()) != false) {
            // check if article quantity in cart/articlesToCheckCollection exceeds max. article amount
            if ($this->articlesToCheckCollObj->getItem($articleObj->get_id())->get_quantity() >= $articleObj->get_artrelMaxAmount()) {
                $this->artrelMaxAmountFlag = true;
                $limitationsFound = true;
            }
        }
        
        return $limitationsFound;
        
    }
    
    /**
     * Article relation check for exclusion articles: checks if exclusion articles are found in cart/articlesToCheckCollection (uses logical "OR" for exclusion array values)
     *
     * @param   tx_ptgsashop_baseArticle      article to check, object of type tx_ptgsashop_baseArticle         
     * @return  boolean     FALSE if no appropriate article relation limitations are found, TRUE otherwise
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-10-20 (based on code of former method tx_ptgsashop_pi5::checkArticleRelations() from 2006-08-21)
     */
    protected function checkArtrelExclusion(tx_ptgsashop_baseArticle $articleObj) {
        
        $limitationsFound = false; // (boolean)
            
        // check if exclusion articles are found in cart/articlesToCheckCollection (uses "OR" relation of exclusion array values)
        foreach ($articleObj->get_artrelExclusionArr() as $exclArtId) {
            if ($this->articlesToCheckCollObj->getItem($exclArtId) != false) {
                $this->artrelExclusionArticleArr[] = $this->articlesToCheckCollObj->getItem($exclArtId); // store found exclusion articles in an array to remember
                $limitationsFound = true;
            }
        }
        
        return $limitationsFound;
        
    }
    
    /**
     * Article relation check for required articles: checks for required articles not found in cart/articlesToCheckCollection (uses logical "OR" or "AND" for exclusion array values, depending on configuration)
     *
     * @param   tx_ptgsashop_baseArticle      article to check, object of type tx_ptgsashop_baseArticle
     * @return  boolean     FALSE if no appropriate article relation limitations are found, TRUE otherwise
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-10-20 (based on code of former method tx_ptgsashop_pi5::checkArticleRelations() from 2006-08-21)
     */
    protected function checkArtrelRequired(tx_ptgsashop_baseArticle $articleObj) {
        
        $limitationsFound = false; // (boolean)
            
        // check for required articles not found in cart/articlesToCheckCollection using logical "AND" for exclusion array values
        if ($this->conf['artrelReqUseLogicalOr'] == 0) {
            foreach ($articleObj->get_artrelRequiredArr() as $reqArtId) {
                if ($this->articlesToCheckCollObj->getItem($reqArtId) == false) {
                    $this->artrelRequiredArticleArr[] = tx_ptgsashop_articleFactory::createArticle($reqArtId, $this->customerObj->get_priceCategory(), 1); // store required, but not-found-in-cart/-articlesToCheckCollection articles in an array to remember
                    $limitationsFound = true;
                }
            }
            
        // check for required articles not found in cart/articlesToCheckCollection using logical "OR" for exclusion array values
        } else {
            $oneReqArtFound = false;
            foreach ($articleObj->get_artrelRequiredArr() as $reqArtId) {
                if ($this->articlesToCheckCollObj->getItem($reqArtId) == true) {
                    $oneReqArtFound = true;
                    break;
                }
            }
            if ($oneReqArtFound == false) {
                foreach ($articleObj->get_artrelRequiredArr() as $reqArtId) {
                    $this->artrelRequiredArticleArr[] = tx_ptgsashop_articleFactory::createArticle($reqArtId, $this->customerObj->get_priceCategory(), 1); // store *all* alternatively required articles in an array to remember
                }
                $limitationsFound = true;
            }
        }
        
        return $limitationsFound;
        
    }
    
    /**
     * Article relation check for max amount of cart form input: checks if the article's quantity changed in the cart form exceeds the max. article amount in cart/articlesToCheckCollection (if yes, the article qty passed by reference will be set to this amount)
     *
     * @param   tx_ptgsashop_baseArticle     article to check, object of type tx_ptgsashop_baseArticle required
     * @param   integer    (passed by reference) requested article quantity to check - this will be set to max. amount on check failure
     * @return  string     Message to disdplay in MsgBox on check failure, empty string on success
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-23
     */
    protected function checkArtrelCartFormMaxAmount(tx_ptgsashop_baseArticle $artObj, &$artQty) {
        
        $msg = '';
        
        // check if article max amount value is set and if if requested quantity exceeds max. article amount
        if ($artObj->get_artrelMaxAmount() > 0 && $artQty > $artObj->get_artrelMaxAmount()) {
            $artQty = $artObj->get_artrelMaxAmount();
            $msg = sprintf($this->pi_getLL('cart_msgbox_max_amount_1'), $artObj->get_description(), $artObj->get_artrelMaxAmount()).'<br />'.
                   $this->pi_getLL('cart_msgbox_max_amount_2').'<br />';
        }
            
        return $msg;
        
    }
    
    /**
     * Article relation check for cart form deletion: Checks wether an article (specified by id) can be removed from cart or if there are dependencies that prevent this
     *
     * @param   integer     ID of the article to check
     * @return  string      Message to disdplay in MsgBox on check failure, empty string on success
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-23
     */
    protected function checkArtrelCartFormRemoval($removalArtId) {
        
        $msg = '';
        $dependentArticleList = '';
        $removalArtName = $this->cartObj->getItem($removalArtId)->get_description();
        
        // check for required articles in cart using logical "AND" for exclusion array values
        if ($this->conf['artrelReqUseLogicalOr'] == 0) {
            
            foreach ($this->cartObj as $artKey=>$artObj) {
                if (in_array($removalArtId, $artObj->get_artrelRequiredArr())) {
                    $dependentArticleList .= ($dependentArticleList == '' ? '' : ', ').$artObj->get_description();
                }
            }
            
            if ($dependentArticleList != '') {
                $msg = sprintf($this->pi_getLL('cart_msgbox_required_articles_AND_1'), $removalArtName).'<br />'.
                       '<b>'.$dependentArticleList.'</b><br />'.
                       sprintf($this->pi_getLL('cart_msgbox_required_articles_AND_2'), $removalArtName);
            }
        
        // check for required articles in cart using logical "OR" for exclusion array values
        } else {
             
            $dependentArticleObj = NULL;
            $oneAlternativeReqArtFound = false;
            
            // check if there is an article depending on the removal article
            foreach ($this->cartObj as $artKey=>$artObj) {
                if (in_array($removalArtId, $artObj->get_artrelRequiredArr())) {
                    $dependentArticleObj = $artObj;
                    break;
                }
            }
            
            // if there is an article depending on the removal article check alternative required articles
            if ($dependentArticleObj != NULL) {
                
                // check if alternative articles (any *other* required articles but the removal article) are found in the cart
                foreach ($dependentArticleObj->get_artrelRequiredArr() as $reqArtId) {
                    if ($reqArtId != $removalArtId && $this->cartObj->getItem($reqArtId) == true) {
                        $oneAlternativeReqArtFound = true;
                        break;
                    }
                }
                
                // if there no alternative required articles found: prevent article removal and display warning
                if ($oneAlternativeReqArtFound == false) {
                    
                    $requiredArticleList = '';
                    
                    foreach ($dependentArticleObj->get_artrelRequiredArr() as $reqArtId) {
                        $tmpArtObj = tx_ptgsashop_articleFactory::createArticle($reqArtId, $this->customerObj->get_priceCategory(), 1);
                        $requiredArticleList .= ($requiredArticleList == '' ? '' : ', ').$tmpArtObj->get_description();
                    }
                    $msg = sprintf($this->pi_getLL('cart_msgbox_required_articles_OR_1'), $dependentArticleObj->get_description()).'<br />'.
                           '<b>'.$requiredArticleList.'</b><br />'.
                           sprintf($this->pi_getLL('cart_msgbox_required_articles_OR_2'), $dependentArticleObj->get_description(), 
                                                                                          $this->cartObj->getItem($removalArtId)->get_description());
                }
                
            }
            
        }
          
        return $msg;
        
    }
    
    
    
    /***************************************************************************
     *   PRESENTATION METHODS (TODO: should be moved to presentator classes)
     **************************************************************************/
    
    /**
     * Generates and returns the HTML code of the current shopping cart
     *
     * @param   string      (optional) HTML code of message box or empty string to display no message box (default:'')
     * @global  
     * @return  string      HTML code of the shopping cart GUI
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-30 (non-template-based version generateShoppingCartHTML(): 2005-03-08)
     */
    protected function displayShoppingCart($msgBox='') {
        
        $markerArray = array();
        
        // assign conditional message box
        if (!empty($msgBox)) {
            $markerArray['cond_displayMsgBox'] = true;
            $markerArray['msgBox'] = $msgBox;
        }
        
        // assign template placeholders: table header
        $markerArray['faction_cart'] = $this->formActionSelf;
        $markerArray['fname_updButton'] = $this->prefixId.'[cart_upd_button]';
        $markerArray['currencyCode'] = $this->conf['currencyCode'];
        $markerArray['ll_cart_header_quantity'] = $this->pi_getLL('cart_header_quantity');
        $markerArray['ll_cart_header_artdescr'] = $this->pi_getLL('cart_header_artdescr');
        $markerArray['ll_cart_header_action'] = $this->pi_getLL('cart_header_action');
        $markerArray['ll_cart_header_artno'] = $this->pi_getLL('cart_header_artno');
        $markerArray['ll_cart_header_price'] = $this->pi_getLL('cart_header_price');
        $markerArray['ll_cart_header_fixedcost'] = $this->pi_getLL('cart_header_fixed_cost'); // unused by default, offered for individual cart templates
        $markerArray['ll_cart_header_sum'] = $this->pi_getLL('cart_header_sum');
        $markerArray['ll_cart_article_link_title'] = $this->pi_getLL('cart_article_link_title');
            // additional template placeholders, independent from default pt_gsashop template (added by ry44, 2008-03-05)
        $markerArray['ll_cart_header_net'] = $this->pi_getLL('cart_header_net');
        $markerArray['ll_cart_header_gross'] = $this->pi_getLL('cart_header_gross');
        
        // assign template placeholders: table content (articles and footer)
        if ($this->cartObj->count() > 0) {
            $markerArray['cond_articlesInCart'] = true;
            
            // item rows (articles)
            $artArr = array();
            $i = 0;
            foreach ($this->cartObj as $key=>$artObj) { /* @var $artObj tx_ptgsashop_article */
                $artArr[$i]['fname_quantityInput'] = $this->prefixId.'[qty.'.$key.']'; 
                $artArr[$i]['artQuantity'] = tx_pttools_div::htmlOutput($artObj->get_quantity()); 
                $artArr[$i]['artDescription'] = tx_pttools_div::htmlOutput($artObj->get_description()); 
                $artArr[$i]['artSingleViewLinkTarget'] = ($this->conf['displayCartArticleLinks'] == 1 ? $artObj->getFePageLink() : '');
                if (strlen($artArr[$i]['artSingleViewLinkTarget']) > 0) {
                    $artArr[$i]['cond_linkArticle'] = true;
                }
                if (strlen($artObj->getAdditionalText()) > 0) {
                    $artArr[$i]['cond_additionalText'] = true; 
                    $artArr[$i]['additionalText'] = tx_pttools_div::htmlOutput($artObj->getAdditionalText()); 
                }
                if ($artObj->getFixedCost($this->isNetPriceDisplay) > 0) {
                    $artArr[$i]['cond_fixedCost'] = true; 
                    $artArr[$i]['artFixedCostTotal'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($artObj->getFixedCost($this->isNetPriceDisplay))); 
                    $artArr[$i]['ll_cart_fixedcost_info'] = $this->pi_getLL('cart_fixedcost_info'); 
                }
                $artArr[$i]['fname_delButton'] = $this->prefixId.'[cart_del_button]['.$key.']'; 
                $artArr[$i]['ll_cart_del_button'] = $this->pi_getLL('cart_del_button'); 
                $artArr[$i]['artNo'] = tx_pttools_div::htmlOutput($artObj->get_artNo()); 
                $artArr[$i]['artPrice'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($artObj->getDisplayPrice($this->isNetPriceDisplay))); 
                $artArr[$i]['artSubtotal'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($artObj->getItemSubtotal($this->isNetPriceDisplay)));
                    // additional template placeholders, independent from default pt_gsashop template (added by ry44, 2008-03-05)
                $artArr[$i]['artNetPrice'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($artObj->getDisplayPrice(true))); 
                $artArr[$i]['artNetSubtotal'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($artObj->getItemSubtotal(true)));
                $artArr[$i]['artGrossPrice'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($artObj->getDisplayPrice(false))); 
                $artArr[$i]['artGrossSubtotal'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($artObj->getItemSubtotal(false)));
                
                $artArr[$i]['artImages'] = array();
                foreach ($artObj->get_articleImageCollectionObj() as $articleImageObj) { /* @var $articleImageObj tx_ptgsashop_articleImage */
                	$artArr[$i]['artImages'][] = array(
                		'path' => $articleImageObj->get_path(),
                		'alt' => $articleImageObj->get_alt(),
                		'description' => $articleImageObj->get_description(),
                		'title' => $articleImageObj->get_title(),
                	);
                }
                
                // HOOK: allow multiple hooks to manipulate the article array to display in Smarty template
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi1_hooks']['displayShoppingCart_returnArtArrHook'])) {
                    foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi1_hooks']['displayShoppingCart_returnArtArrHook'] as $className) {
                        $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                        $artArr[$i] = $hookObj->displayShoppingCart_returnArtArrHook($this, $artObj, $artArr[$i]);
                    }
                }
                
               $i++;
            }
            $markerArray['artArr'] = $artArr;
            
            // total sums and footer
            $markerArray['fname_updSubmittedButton'] = $this->prefixId.'[cart_upd_submitted]';
            $markerArray['ll_cart_upd_button'] = $this->pi_getLL('cart_upd_button');
            $markerArray['ll_cart_sum_total'] = $this->pi_getLL('cart_sum_total');
            $markerArray['itemsTotal'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($this->cartObj->getItemsTotal($this->isNetPriceDisplay), '', 2)); // rounded to 2 decimal places for the display
                // additional template placeholders, independent from default pt_gsashop template (added by ry44, 2008-03-05)
            $markerArray['itemsNetTotal'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($this->cartObj->getItemsTotal(true), '', 2)); // rounded to 2 decimal places for the display
            $markerArray['itemsGrossTotal'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($this->cartObj->getItemsTotal(false), '', 2)); // rounded to 2 decimal places for the display           
            $markerArray['ll_cart_price_notice_currency'] = tx_pttools_div::htmlOutput(sprintf($this->pi_getLL('cart_price_notice_currency'), $this->conf['currencyCode']));
            
            $markerArray['ll_price_notice'] = ($this->isNetPriceDisplay==true ? $this->pi_getLL('cart_price_notice_net') : $this->pi_getLL('cart_price_notice_gross'));
            $markerArray['fname_checkoutButton'] = $this->prefixId.'[cart_checkout_button]';
            $markerArray['ll_cart_checkout_button'] = $this->pi_getLL('cart_checkout_button');
            if ($this->conf['displayClearCartButton'] == 1) {
                $markerArray['cond_displayClearCartButton'] = true;
                $markerArray['fname_clearButton'] = $this->prefixId.'[cart_clear_button]';
                $markerArray['ll_cart_clear_button'] = $this->pi_getLL('cart_clear_button');
                $markerArray['ll_cart_clear_warning'] = $this->pi_getLL('cart_clear_warning');
            }
            
        } else {
            // empty cart message
            $markerArray['ll_cart_empty'] = new tx_pttools_msgBox('info', $this->pi_getLL('cart_empty'));
        }
        
        // assign template placeholders: additional navigation
        $lastOrderPage = tx_pttools_sessionStorageAdapter::getInstance()->read(tx_ptgsashop_lib::SESSKEY_LASTORDERPAGE);
        if (!empty($lastOrderPage)) { 
            $markerArray['cond_displayLastOrderPageLink'] = true;
            $markerArray['ll_cart_return_orderpage'] = $this->pi_getLL('cart_return_orderpage');
            $markerArray['href_lastOrderPage'] = $this->pi_getPageLink($lastOrderPage);
        }
        
        
        // HOOK: allow multiple hooks to manipulate $markerArray
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi1_hooks']['displayShoppingCart_MarkerArrayHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi1_hooks']['displayShoppingCart_MarkerArrayHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $markerArray = $hookObj->displayShoppingCart_MarkerArrayHook($this, $markerArray); // $this is passed as a reference (since PHP5) and can be manipulated in the hook method
            }
        }
        
        // return prepared template to display
        $smarty = new tx_pttools_smartyAdapter($this);
        foreach ($markerArray as $markerKey=>$markerValue) {
            $smarty->assign($markerKey, $markerValue);
        }
        $filePath = $smarty->getTplResFromTsRes($this->conf['templateFileCart']);
        trace($filePath, 0, 'Smarty template resource $filePath');
        return $smarty->fetch($filePath);
        
    }
    
    /**
     * Generates and returns the HTML code of a FE user login including a link to the registration page for non-registered users.
     *
     * Notice: to make the login work the field "Startingpoint" at the plugin configuration must contain the FE users sysfolder!
     * This results in a value of $this->cObj->data['pages']: one or more (comma seperated) page IDs of pages that must contain FE users sysfolder
     *
     * @param   string      name of the GET var to set (to remember from where the user came to the login screen)      
     * @global  integer     $GLOBALS['TSFE']->id: UID of the current page
     * @return  string      HTML code of the FE user login GUI
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-30 (non-template-based version generateUserLoginHTML(): 2005-04-12)
     */
    protected function displayUserLogin($getVarName) {
        
        $markerArray = array();
        
        // create current page's URL to return after login or registration (GET-Param is used to remember on return from where the user came to the login screen)
        $selfUrl = $this->pi_getPageLink($GLOBALS['TSFE']->id, '', array($this->prefixId.'['.$getVarName.']' => '1'));
        
        // assign general template placeholders
        $markerArray['faction_login'] = $selfUrl;
        $markerArray['ll_login_header'] = $this->pi_getLL('login_header');
        $markerArray['ll_login_username'] = $this->pi_getLL('login_username');
        $markerArray['ll_login_password'] = $this->pi_getLL('login_password');
        $markerArray['ll_login_button'] = $this->pi_getLL('login_button');
        $markerArray['feUsersSysfolderPid'] = tx_pttools_div::getPid($this->conf['feUsersSysfolderPid']);
        $markerArray['ll_login_new_customer'] = $this->pi_getLL('login_new_customer');
        $markerArray['ll_login_goto_regpage'] = $this->pi_getLL('login_goto_regpage');
        $markerArray['link_gotoRegistrationPage'] = $this->pi_getPageLink($this->conf['feUserRegPage'], '', array('backURL'=>$selfUrl));
        
        // assign "forgot password" placeholders only if configured in TS
        if (strlen($this->conf['forgotPwPage']) > 0) {
            $markerArray['cond_forgot_pw'] = true;
            $markerArray['ll_forgot_pw'] = $this->pi_getLL('login_forgot_pw');
            $markerArray['ll_login_goto_forgotPwPage'] = $this->pi_getLL('login_goto_forgotPwPage');
            $markerArray['link_gotoForgotPwPage'] = $this->pi_getPageLink($this->conf['forgotPwPage'], '', array('backURL'=>$selfUrl));
        } else {
            $markerArray['cond_forgot_pw'] = false;
        }
        
        
        // HOOK: allow multiple hooks to manipulate $markerArray
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi1_hooks']['displayUserLogin_MarkerArrayHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi1_hooks']['displayUserLogin_MarkerArrayHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $markerArray = $hookObj->displayUserLogin_MarkerArrayHook($this, $markerArray); // $this is passed as a reference (since PHP5) and can be manipulated in the hook method
            }
        }
        
        // return prepared template to display
        $smarty = new tx_pttools_smartyAdapter($this);
        foreach ($markerArray as $markerKey=>$markerValue) {
            $smarty->assign($markerKey, $markerValue);
        }
        $filePath = $smarty->getTplResFromTsRes($this->conf['templateFileCheckoutLogin']);
        trace($filePath, 0, 'Smarty template resource $filePath');
        return $smarty->fetch($filePath);
        
    }
    
    /**
     * Generates and returns the HTML code of the article confirmation displayed in combination with the shopping cart
     *
     * @param   tx_ptgsashop_baseArticle      article to check, object of type tx_ptgsashop_baseArticle   
     * @param   boolean     flag wether the confirmation is called while trying to add an article to the cart (default=0) 
     * @return  string      HTML code of the article confirmation displayed in combination with the shopping cart
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-10-20 (based on code of former method tx_ptgsashop_pi5::displayArticleConfirmation() from 2006-08-21)
     */
    protected function displayArticleConfirmation(tx_ptgsashop_baseArticle $articleObj, $artAddCheck=0) { 
        
        $markerArray = array();
        
        // assign template placeholders: message box
        if ($artAddCheck == 0) {
            $msg = sprintf($this->pi_getLL('artrel_msgbox_not_orderable'), $articleObj->get_description());
        } else {
            $msg = sprintf($this->pi_getLL('artrel_msgbox_not_added_to_cart'), $articleObj->get_description());
        }
        $msgBoxObj = new tx_pttools_msgBox('warning', tx_pttools_div::htmlOutput($msg));
        
        // display an optional suppot notice only if this is set and the article has not been added
        if (strlen($this->pi_getLL('artrel_optional_support_notice')) > 0) {
            $markerArray['cond_artrel_optional_support_notice'] = true;
            $markerArray['ll_artrel_optional_support_notice'] = $this->pi_getLL('artrel_optional_support_notice');
        }
        $markerArray['msgBox'] = $msgBoxObj->__toString();
        
        // assign template placeholders: conditional main content
        if ($this->artrelMaxAmountFlag == true) {
            $markerArray['cond_artrel_notice_max_amount'] = true;
            $markerArray['ll_artrel_notice_max_amount'] = tx_pttools_div::htmlOutput(sprintf($this->pi_getLL('artrel_notice_max_amount'), $articleObj->get_artrelMaxAmount()));
        }
        
        if (sizeof($this->artrelExclusionArticleArr) > 0) {
            $markerArray['cond_artrel_notice_exclusion_articles'] = true;
            $markerArray['ll_artrel_artno'] = $this->pi_getLL('artrel_artno');
            $markerArray['ll_artrel_notice_exclusion_articles_1'] = $this->pi_getLL('artrel_notice_exclusion_articles_1');
            $markerArray['ll_artrel_notice_exclusion_articles_2'] = tx_pttools_div::htmlOutput(sprintf($this->pi_getLL('artrel_notice_exclusion_articles_2'), $articleObj->get_description(), $articleObj->get_description()));
            $exclArtArr = array();
            $i = 0;
            foreach ($this->artrelExclusionArticleArr as $exclArtObj) {
                $exclArtArr[$i]['artDescription'] = tx_pttools_div::htmlOutput($exclArtObj->get_description()); 
                $exclArtArr[$i]['artNumber'] = tx_pttools_div::htmlOutput($exclArtObj->get_artNo()); 
                $i++;
            }
            $markerArray['exclArtArr'] = $exclArtArr;
        }
        
        if (sizeof($this->artrelRequiredArticleArr) > 0) {
            $markerArray['cond_artrel_notice_required_articles'] = true;
            $markerArray['ll_artrel_artno'] = $this->pi_getLL('artrel_artno');
            $markerArray['ll_artrel_notice_required_articles_1'] = ($this->conf['artrelReqUseLogicalOr'] == 0 ? $this->pi_getLL('artrel_notice_required_articles_1_and') : $this->pi_getLL('artrel_notice_required_articles_1_or'));
            $markerArray['ll_artrel_notice_required_articles_2'] = tx_pttools_div::htmlOutput(sprintf($this->pi_getLL('artrel_notice_required_articles_2'), $articleObj->get_description(), $articleObj->get_description()));
            
            $markerArray['faction_addToCart'] = $this->formActionSelf;
            $markerArray['ll_artrel_required_articles_price'] = $this->pi_getLL('artrel_required_articles_price');
            $markerArray['currencyCode'] = $this->conf['currencyCode'];
            $markerArray['ll_titleViewArticle'] = $this->pi_getLL('artrel_required_articles_view_article');
            $markerArray['fname_articleId'] = $this->prefixId.'[gsa_id]';
            $markerArray['fname_isArtrelAddition'] = $this->prefixId.'[is_artrel_addition]';
            $markerArray['fname_cartButton'] = $this->prefixId.'[cart_button]';
            $markerArray['imgsrc_cartButton'] = $GLOBALS['TSFE']->tmpl->getFileName($this->conf['imgAddToCartButtonArtRelCheck']);
            $markerArray['ll_titleCartButton'] = $this->pi_getLL('artrel_required_articles_add_to_cart');
            
            $reqArtArr = array();
            $i = 0;
            foreach ($this->artrelRequiredArticleArr as $reqArtObj) {
                $reqArtArr[$i]['artSingleViewLinkTarget'] = ($this->conf['displayCartArticleLinks'] == 1 ? $reqArtObj->getFePageLink() : '');
                if (strlen($reqArtArr[$i]['artSingleViewLinkTarget']) > 0) {
                    $reqArtArr[$i]['cond_linkArticle'] = true;
                }
                $reqArtArr[$i]['artDescription'] = tx_pttools_div::htmlOutput($reqArtObj->get_description()); 
                $reqArtArr[$i]['artNumber'] = tx_pttools_div::htmlOutput($reqArtObj->get_artNo()); 
                $reqArtArr[$i]['artPrice'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($reqArtObj->getDisplayPrice($this->isNetPriceDisplay))); 
                $reqArtArr[$i]['fval_articleId'] = tx_pttools_div::htmlOutput($reqArtObj->get_id());;
                $reqArtArr[$i]['fhidden_reloadHandlerToken'] = $this->formReloadHandler->returnTokenHiddenInputTag($this->prefixId.'[__formToken]');
                $i++;
            }
            $markerArray['reqArtArr'] = $reqArtArr;
        }
        
        
        // HOOK: allow multiple hooks to manipulate $markerArray
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi1_hooks']['displayArticleConfirmation_MarkerArrayHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi1_hooks']['displayArticleConfirmation_MarkerArrayHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $markerArray = $hookObj->displayArticleConfirmation_MarkerArrayHook($this, $markerArray); // $this is passed as a reference (since PHP5) and can be manipulated in the hook method
            }
        }
        
        // return prepared template to display
        $smarty = new tx_pttools_smartyAdapter($this);
        foreach ($markerArray as $markerKey=>$markerValue) {
            $smarty->assign($markerKey, $markerValue);
        }
        $filePath = $smarty->getTplResFromTsRes($this->conf['templateFileArticleConfirmation']);
        trace($filePath, 0, 'Smarty template resource $filePath');
        return $smarty->fetch($filePath) . $this->displayShoppingCart(); // display article confirmation above shopping cart
        
    }
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/pi1/class.tx_ptgsashop_pi1.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/pi1/class.tx_ptgsashop_pi1.php']);
}

?>
