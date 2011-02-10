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
 * Frontend Plugin 'GSA Shop: Order' for the 'pt_gsashop' extension.
 *
 * $Id: class.tx_ptgsashop_pi3.php,v 1.167 2009/07/28 08:18:50 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2005-12-05
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
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_articleCollection.php';  // GSA shop article collection class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_cart.php';  // GSA shop cart class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_sessionOrder.php';  // GSA shop session order class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_orderWrapper.php';// GSA shop order wrapper class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_orderProcessor.php';// GSA shop order processor class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_delivery.php';  // GSA shop delivery class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_deliveryCollection.php';  // GSA shop delivery collection class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_dispatchCost.php';  // GSA shop dispatch cost class
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
//echo '<pre>'; ### TODO: Remove lines (dev only)
//Reflection::export(new ReflectionClass('Iterator'));
//Reflection::export(new ReflectionClass('IteratorAggregate'));
//Reflection::export(new ReflectionClass('ArrayIterator'));
//Reflection::export(new ReflectionClass('ArrayObject'));
//echo '</pre>'; 
#trace(TYPO3_db);
#trace($TYPO3_CONF_VARS);
#trace(t3lib_div::GPvar('tx_ptgsashop_pi3'));
#trace($_POST, 0, '$_POST');
#trace($GLOBALS['TSFE'], 0, '$GLOBALS[TSFE]');
#trace($GLOBALS['TSFE']->fe_user, 0, '$GLOBALS[TSFE]->fe_user');
#trace($GLOBALS['TSFE']->fe_user->sesData, 0, '$GLOBALS[TSFE]->fe_user->sesData');



/**
 * Provides all order features for the GSA based shop 
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2005-12-05
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_pi3 extends tslib_pibase {
    
    /***************************************************************************
     * tslib_pibase (parent class) instance variables
     **************************************************************************/
    
    public $extKey = 'pt_gsashop';    // The extension key.
    public $prefixId = 'tx_ptgsashop_pi3';    // Same as class name
    public $scriptRelPath = 'pi3/class.tx_ptgsashop_pi3.php';    // Path to this script relative to the extension dir.
    
    
    
    /***************************************************************************
     * tx_ptgsashop_pi3 instance variables
     **************************************************************************/
    
    /**
     * @var     array        basic extension configuration data from localconf.php (configurable in Extension Manager)
     */
    protected $extConfArr = array();
     
    /**
     * @var     string        address for HTML forms' 'action' attributes to send a form of this page to itself
     */
    protected $formActionSelf = '';
    
    /**
     * @var     tx_pttools_formReloadHandler    web form reload handler object
     */
    protected $formReloadHandler;
    
    /**
     * @var     tx_ptgsashop_cart    shopping cart object
     */
    protected $cartObj; 
       
    /**
     * @var     tx_ptgsashop_sessionOrder    order object
     */
    protected $orderObj;

    /**
     * @var     tx_ptgsashop_sessionFeCustomer    frontend customer object (FE user who uses this plugin)
     */
    protected $customerObj; 
    
    
    
    /***************************************************************************
     * Class Constants
     **************************************************************************/
    
    const USERREG_CLASS_NAME = 'tx_ptgsauserreg_pi1'; // (string) class name of the user registration plugin to use combined with this plugin
    const USERACCOUNT_CLASS_NAME = 'tx_ptgsauserreg_pi4'; // (string) class name of the user account plugin to use combined with this plugin
    
    
    
    /***************************************************************************
     *   MAIN
     **************************************************************************/
    
    /**
     * Main method of the order plugin: Prepares properties and instances, interprets submit buttons to control plugin behaviour
     *
     * @param   string      HTML-Content of the plugin to be displayed within the TYPO3 page
     * @param   array       Global configuration for this plugin (mostly done in Constant Editor/TS setup)
     * @return  string      HTML plugin content for output on the page (if not redirected before)
     * @global  boolean     $GLOBALS['TSFE']->loginUser: Flag indicating if a front-end user is logged in
     * @global  integer     $GLOBALS['TSFE']->id: UID of the current page
     * @throws  tx_pttools_exception   if no logged-in FE user found
     * @throws  tx_pttools_exception   if caller is not a cart checkout AND no valid order is found in session 
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-03-07 (adapted for pi3 2005-12-06)
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
            
            // throw exceptions if FE is not logged-in user, or if logged-in user is not GSA enabled and approved GSA online customer (stop further pi3 script processing)
            if (!$GLOBALS['TSFE']->loginUser) {
                throw new tx_pttools_exception('A logged-in frontend user is required to proceed.', 0);
            } elseif (tx_ptgsashop_sessionFeCustomer::getInstance()->getIsGsaAddressEnabled() != 1) {
                throw new tx_pttools_exception('A GSA enabled user is required to proceed.', 0);
            } elseif (tx_ptgsashop_sessionFeCustomer::getInstance()->getIsGsaOnlineCustomer() != 1) {
                throw new tx_pttools_exception('An GSA online customer approval is required to proceed. Please contact the shop operator.', 0);
            }
            
            // return error page if order has already been submitted
            if (tx_pttools_sessionStorageAdapter::getInstance()->read(tx_ptgsashop_lib::SESSKEY_ORDERSUBMITTED) == true) {
                return $this->pi_wrapInBaseClass($this->displayDoubleSubmissionError());
            }
        
            // throw exception if this is not a cart checkout AND no valid order is found in session (stop further pi3 script processing)
            if (!($this->piVars['checkOut'] == 1)) {
                $sessionOrderObj = tx_pttools_sessionStorageAdapter::getInstance()->read(tx_ptgsashop_sessionOrder::SESSION_KEY_NAME);
                if (!is_object($sessionOrderObj) || !($sessionOrderObj instanceof tx_ptgsashop_sessionOrder) || !is_object($sessionOrderObj->get_deliveryCollObj())) {
                    throw new tx_pttools_exception('No valid order found in session.', 0);
                }
            // throw exception if this is a cart checkout AND no valid cart is found in session (stop further pi3 script processing)
            } else {
                $sessionCartObj = tx_pttools_sessionStorageAdapter::getInstance()->read(tx_ptgsashop_cart::SESSION_KEY_NAME);
                if (!is_object($sessionCartObj) || !($sessionCartObj instanceof tx_ptgsashop_cart) || !($sessionCartObj->count() > 0)) {
                    throw new tx_pttools_exception('No valid cart found in session.', 0);
                }
            }
            
            
            
            // ********** SET PLUGIN-OBJECT PROPERTIES **********
            
            // get unique instances (Singleton) of shopping cart (filled with session items), order and current FE customer
            $this->cartObj = tx_ptgsashop_cart::getInstance();
            $this->orderObj = tx_ptgsashop_sessionOrder::getInstance();  // IMPORTANT NOTE: $this->orderObj->set_isNet()/set_isTaxFree() must not be used here since this could change an existing order after pressing the final order button!
            $this->customerObj = tx_ptgsashop_sessionFeCustomer::getInstance();
            
            // set self url for HTML form action attributes
            $this->formActionSelf = $this->pi_getPageLink($GLOBALS['TSFE']->id);
            
            // set form reload handler object
            $this->formReloadHandler = new tx_pttools_formReloadHandler;
            
            
            // ********** CONTROLLER: execute approriate method for any action command (retrieved form buttons/GET vars) **********
            
            // [CMD] Order Overview GUI: Edit cart
            if (isset($this->piVars['edit_cart'])) { 
                $content .= $this->exec_editCart();
            // [CMD] Order Overview GUI: Set new address (coming from external <USERACCOUNT_CLASS_NAME> plugin!)
            } elseif (isset($this->piVars['userreg_change_addr'])) {                
                $content .= $this->exec_setNewAddress();
            // [CMD] Order Overview GUI: Set new payment method (coming from external <USERREG_CLASS_NAME> plugin!)
            } elseif (isset($this->piVars['userreg_change_payment'])) {                
                $content .= $this->exec_setNewPayment();
            // [CMD] Order Overview GUI: Distribute order to multiple deliveries
            } elseif (isset($this->piVars['overview_multdel_button'])) {                
                $content .= $this->exec_distributeOrder();
            // [CMD] Order Overview GUI: Distribute article to multiple deliveries (maybe coming from external <USERACCOUNT_CLASS_NAME> plugin!)
            } elseif (isset($this->piVars['overview_distribute_article'])) {              
                $content .= $this->exec_distributeArticle();
            // [CMD] Article Distribution GUI: Create new delivery (maybe coming from external <USERACCOUNT_CLASS_NAME> plugin!)
            } elseif (isset($this->piVars['artdistr_new_delivery'])) {   
                $content .= $this->exec_addNewDelivery();
            // [CMD] Article Distribution GUI: Update article distribution
            } elseif (isset($this->piVars['artdistr_upd_distribution'])) {            
                $content .= $this->exec_updArticleDistribution();
            // [CMD] Article Distribution GUI: Keep previous article distribution
            } elseif (isset($this->piVars['artdistr_keep_distribution'])) {    
                $content .= $this->exec_keepArticleDistribution();
            // [CMD] Order Overview GUI: Order now (order submitted)
            } elseif (isset($this->piVars['overview_order_button'])) {               
                $content .= $this->exec_orderSubmission();
            // [CMD] Default action: allow additional controller hook or process default action (display order overview)
            } else { 
                // HOOK: allow multiple hooks to execute individual action
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi3_hooks']['mainControllerHook'])) {
                    foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi3_hooks']['mainControllerHook'] as $className) {
                        $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                        $content .= $hookObj->mainControllerHook($this);
                                        // IMPORTANT: any hook should include the default action below, too!
                    }
                // default action: Display order overview
                } else {              
                    $content .= $this->exec_defaultAction();
                }
            }
            
            
            // ********** DEFAULT PLUGIN FINALIZATION **********
            
            // save current shopping cart and order to session (customer is _not_ stored here)
            $this->cartObj->store();
            $this->orderObj->store();
        
            
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
     * Controller default action: display order overview
     *
     * @param   void
     * @return  string      HTML plugin content for output on the page
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-07-10 (based on previous code code from main() method)
     */
    protected function exec_defaultAction() {
        
        $content = '';
        trace('[CMD] '.__METHOD__);
       
        $content = $this->processOrderOverview();
        return $content;
        
    }
    
    /**
     * Controller action for Order Overview GUI: redirect to cart page
     *
     * @param   void
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-07-10 (based on previous code code from main() method)
     */
    protected function exec_editCart() {
        
        trace('[CMD] '.__METHOD__);
        tx_pttools_div::localRedirect($this->pi_getPageLink($this->conf['shoppingcartPage']));
        
    }
    
    /**
     * Controller action for Order Overview GUI: set new adress & display order overview
     * (coming from external <USERACCOUNT_CLASS_NAME> plugin!)
     *
     * @param   void
     * @return  string      HTML plugin content for output on the page
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-07-10 (based on previous code code from main() method)
     */
    protected function exec_setNewAddress() {
        
        $content = '';
        trace('[CMD] '.__METHOD__);
        
        trace('setting new address ['.$this->piVars['addr_change_target_key'].'] (coming from '.self::USERACCOUNT_CLASS_NAME.'!)...');
        $chosenAddress = $this->customerObj->getAddress($this->piVars['addr_change_source_id']);
        
        // billing address: target_id -1 , shipping addresses: target_id 0...n
        if ($this->piVars['addr_change_target_key'] == -1) {
            $this->orderObj->set_billingAddrObj($chosenAddress);
        } elseif ($this->piVars['addr_change_target_key'] >= 0) {
            $this->orderObj->getDelivery($this->piVars['addr_change_target_key'])->set_shippingAddrObj($chosenAddress);
        }
        
        $content = $this->processOrderOverview();  
        return $content;
        
    }
    
    /**
     * Controller action for Order Overview GUI: set new payment method & display order overview
     * (coming from external <USERREG_CLASS_NAME> plugin!)
     *
     * @param   void
     * @return  string      HTML plugin content for output on the page
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-03-29
     */
    protected function exec_setNewPayment() {
        
        $content = '';
        trace('[CMD] '.__METHOD__);
        
        trace('setting new payment (coming from '.self::USERREG_CLASS_NAME.'!)...');
        
        $this->orderObj->set_paymentMethodObj($this->customerObj->getPaymentObject($this->piVars['payment_account_index']));
        
        $content = $this->processOrderOverview();  
        return $content;
        
    }
    
    /**
     * Controller action for Order Overview GUI: set multiple deliveries flag in order & display order overview
     *
     * @param   void
     * @return  string      HTML plugin content for output on the page
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-07-10 (based on previous code code from main() method)
     */
    protected function exec_distributeOrder() {
        
        $content = '';
        trace('[CMD] '.__METHOD__);
                
        $this->orderObj->set_isMultDeliveries(true);
        
        $content = $this->processOrderOverview();
        return $content;
        
    }
    
    /**
     * Controller action for Order Overview GUI: Distribute article to multiple deliveries & display article distribution page
     * (maybe coming from external <USERACCOUNT_CLASS_NAME> plugin!)
     *
     * @param   void
     * @return  string      HTML plugin content for output on the page
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-07-10 (based on previous code code from main() method)
     */
    protected function exec_distributeArticle() {
        
        $content = '';
        $msgBox = ''; 
        trace('[CMD] '.__METHOD__);
                
        // if coming from external <USERACCOUNT_CLASS_NAME> plugin: display confirmation MsgBox for newly added address 
        if (isset($this->piVars['artdistr_new_address_added'])) {
            $msgBoxObj = new tx_pttools_msgBox('info', $this->pi_getLL('artdistr_add_new_address_confirm'), $this->pi_getLL('artdistr_add_new_address_confirm_header'));
            $msgBox = $msgBoxObj->__toString();
        }
                
        $content = $this->displayArticleDistribution($this->piVars['article_id'], $msgBox);
        return $content;
        
    }
    
    /**
     * Controller action for Order Overview GUI: add new delivery to order & display article distribution page
     * (coming internally from this plugin pi3 or from external <USERACCOUNT_CLASS_NAME> plugin!)
     * 
     * @param   void
     * @return  string      HTML plugin content for output on the page
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-07-10 (based on previous code code from main() method)
     */
    protected function exec_addNewDelivery() {
        
        $content = '';
        trace('[CMD] '.__METHOD__);
        
        // add new delivery with selected address to order (if not initiated by page reload)
        if ($this->formReloadHandler->checkToken($this->piVars['__formToken']) == true) {
            $addDelivery = new tx_ptgsashop_delivery(
                                   new tx_ptgsashop_articleCollection(), 
                                   $this->customerObj->getAddress($this->piVars['new_del_addr_source_id']), 
                                   $this->orderObj->get_isNet(),
                                   $this->orderObj->get_isTaxFree()
                               );
            $this->orderObj->get_deliveryCollObj()->addItem($addDelivery);
        }
        
        // return to originating article's distribution page
        $content = $this->displayArticleDistribution($this->piVars['article_id']);
        return $content;
        
    }
    
    /**
     * 
     * Controller action for Order Overview GUI: update article distribution (check form submission, distribute articles if applicable) & display article distribution page
     *
     * @param   void
     * @return  string      HTML plugin content for output on the page
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-07-10 (based on previous code code from main() method)
     */
    protected function exec_updArticleDistribution() {
        
        $content = '';
        $msgBox = ''; 
        $displayCartChangeCheckbox = 0;
        trace('[CMD] '.__METHOD__);
                
        // merge new delivery distribution array (combine all articles to go to one delivery) and check quantities submitted in distribution form
        $deliveryDistrArr = $this->returnMergedDistributionArray($this->piVars['artdistrib']);
        $distrCheckResult = $this->checkArticleDistributionForm($this->piVars['article_id'], 
                                                                $this->piVars['artdistrib'], 
                                                                $msgBox,                         // &$msgBox is passed by reference
                                                                $this->piVars['artdistrib_change_qty']);
        
        // evaluate the distribution quantity check result
        if ($distrCheckResult === 1) {
            // if distribution quantity check is ok: store new article distribution to deliveries
            $this->distributeArticlesToDeliveries($this->piVars['article_id'], $deliveryDistrArr);
        } elseif ($distrCheckResult === 2) {
            // if distribution quantity differs from cart but appropriate checkbox "change quantity" has been checked: update article quantity in cart, store new article distribution to deliveries
            $this->updateArtDistrQtyChangesConsequences($this->piVars['article_id'], $this->piVars['artdistrib']);
            $this->distributeArticlesToDeliveries($this->piVars['article_id'], $deliveryDistrArr);
         } elseif ($distrCheckResult === 3) {
            // if distribution quantity differs from cart and checkbox "change quantity" is not checked: display "change quantity" checkbox, do not change quantities
            $displayCartChangeCheckbox = 1;
        } elseif ($distrCheckResult === 4) {  
            // if user tried to delete last article from order: discard submitted article distribution
            $deliveryDistrArr = array();
        }
        
        // if article cannot be found (anymore) in current cart: display order overview
        if ($this->cartObj->getItem($this->piVars['article_id']) == false) {
            $content = $this->displayOrderOverview();
            
        // default: proceed with updated article's delivery distribution page and display appropriate MsgBox (on check error: initialize form with submitted data)
        } else {
            $content = $this->displayArticleDistribution($this->piVars['article_id'], $msgBox, $deliveryDistrArr, $displayCartChangeCheckbox);
        }
        
        return $content;
        
    }
    
    /**
     * Controller action for Order Overview GUI: (do not change article distribution &) display article distribution page
     *
     * @param   void
     * @return  string      HTML plugin content for output on the page
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-07-10 (based on previous code code from main() method)
     */
    protected function exec_keepArticleDistribution() {
        
        $content = '';
        trace('[CMD] '.__METHOD__);
        
        $content = $this->displayArticleDistribution($this->piVars['article_id']);
        return $content;
        
    }
    
    /**
     * Controller action for Order Overview GUI: process final order
     *
     * @param   void
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-07-10 (based on previous code code from main() method)
     */
    protected function exec_orderSubmission() {
                
        $content = '';
        $msgBox = '';
        $ccPayment = 0;  // (boolean) flag wether Credit Card Online Payment is requested
        trace('[CMD] '.__METHOD__);

        $msgBox = $this->checkOrderForm(); // check submitted form data
        if (!empty($msgBox)) {
            // on check failure: display form with message box
            $content .= $this->displayOrderOverview($msgBox);
            return $content;
        } 
        
        // on check sucess: set "accepted" flags and process order
        $this->orderObj->set_termsCondAccepted(true);
        $this->orderObj->set_withdrawalAccepted(true);
        $ccPayment = ($this->piVars['overview_payment'] == 'cc' ? 1 : 0);  // (boolean) flag wether Credit Card Online Payment is requested
        
        
        // TODO: set payment type into the paymentMethod object from this->orderObject
        if($ccPayment) {
	        $paymentMethod = $this->orderObj->get_paymentMethodObj();
	        $paymentMethod->set_method('cc');
        }
        
        
        
        
        // HOOK for alternative order processing after the "order now" button has been pressed
        if (($hookObj = tx_pttools_div::hookRequest($this->extKey, 'pi3_hooks', 'processOrderSubmission')) !== false) {
            // use hook method if hook has been found
            $hookObj->processOrderSubmission($this, $ccPayment);
        } else {
            // use default order processing if no hook is found
            $this->processOrderSubmission($ccPayment);
        } 
        
    }
     
    
    
    /***************************************************************************
     *   BUSINESS LOGIC METHODS
     **************************************************************************/
    
    /**
     * Processes an online order submission (after pressing the 'Order now' button)
     *
     * @param   boolean     (optional) flag wether credit card online payment is requested (default: false)
     * @return  void
     * @throws  tx_pttools_exception   if page redirects do not work
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-06-24, completely refactored (outsourced business logic to new class tx_ptgsashop_orderProcessor) since 2008-11-10
     */ 
    protected function processOrderSubmission($ccPayment=false) {
        
        // create order wrapper from current order data
        $orderWrapperObj = new tx_ptgsashop_orderWrapper();
        $orderWrapperObj->set_pid(tx_pttools_div::getPid(tx_ptgsashop_lib::getGsaShopConfig('orderStoragePid')));
        $orderWrapperObj->set_statusCode(($this->conf['enableOrderWorkflow'] == 1 ? $this->conf['workflowInitialStatusCode'] : $this->conf['workflowFinishStatusCode']));
        $orderWrapperObj->setNewOrderObj($this->orderObj);
        $orderWrapperObj->setNewFeCustomerObj($this->customerObj);
        
        // pass wrapped order to order submission processing
        $orderProcessorObj = new tx_ptgsashop_orderProcessor($orderWrapperObj);
        $orderProcessorObj->set_useCcPayment($ccPayment);
        $orderProcessorObj->processSubmission();
                              
        // fallback if the redirects in $orderProcessorObj->processSubmission() fail - we should not end here...
        throw new tx_pttools_exception ('Page redirection error');
        
    }
    
    /**
     * DEPRECATED since v0.14.0 (see below)!
     * 
     * Returns an order wrapper object built from the current order (has to be archived already in order archive database)
     * 
     * @deprecated  this method will be removed in one of the upcoming versions, use new tx_ptgsashop_orderWrapper with appropriate setters instead (see e.g. self::processOrderSubmission())
     * 
     * @param   integer     UID of the order in the order archive database
     * @param   string      (optional) document number ("Vorgangsnummer") of the saved order document in the ERP system
     * @return  tx_ptgsashop_orderWrapper      object of type tx_ptgsashop_orderWrapper (order wrapper object built from the current order)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-09-01
     */ 
    protected function returnWrapperForArchivedOrder($archivedOrderId, $relatedErpDocNo='') {
            
        $deprecatedMsg = __METHOD__.' is marked as DEPRECATED since v0.14.0 - this method will be removed in one of the upcoming versions';
        trigger_error($deprecatedMsg, E_USER_NOTICE);
        t3lib_div::sysLog($deprecatedMsg, $this->extKey, 1);
        
        $wrapperArr = array();
        
        $wrapperArr['pid'] = tx_pttools_div::getPid(tx_ptgsashop_lib::getGsaShopConfig('orderStoragePid')); 
        $wrapperArr['orderTimestamp'] = $this->orderObj->get_timestamp();
        if (strlen($relatedErpDocNo) > 0) {
            $wrapperArr['relatedDocNo'] = $relatedErpDocNo;
        }
        $wrapperArr['sumNet'] = $this->orderObj->getOrderSumTotal(1);
        $wrapperArr['sumGross'] = $this->orderObj->getOrderSumTotal(0);
        $wrapperArr['statusCode'] = ($this->conf['enableOrderWorkflow'] == 1 ? $this->conf['workflowInitialStatusCode'] : $this->conf['workflowFinishStatusCode']); // status depends on workflow enabled/disabled
        $wrapperArr['creatorId'] = $this->customerObj->get_feUserId();
        $wrapperArr['customerId'] = $this->customerObj->get_gsaMasterAddressId();
        $wrapperArr['lastUserId'] = $this->customerObj->get_feUserId();
        $wrapperArr['orderObjId'] = $archivedOrderId;
        $wrapperArr['orderObj'] = $this->orderObj;
        
        $orderWrapperObj = new tx_ptgsashop_orderWrapper(0, 0, $wrapperArr, false);
        
        return $orderWrapperObj;
        
    }
    
    /**
     * DEPRECATED since v0.14.0 (see below)!
     * 
     * Sends the final order as ASCII plain text representation email to the currently logged-in user and the shop's sales recipient configured in Constant Editor
     * 
     * @deprecated  this method will be removed in one of the upcoming versions, use tx_ptgsashop_orderProcessor::sendOrderConfirmationEmail() instead
     *
     * @deprecated 	mail will be sent in the orderProcessor object
     * @param   string      email body: final order as ASCII plain text representation
     * @return  void
     * @global  object      $GLOBALS['TSFE']->fe_user: tslib_feuserauth Object
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-09-01 (code base from 2005-06-24)
     */ 
    protected function sendFinalOrderEmail($finalOrderPlaintext) {
            
        $deprecatedMsg = __METHOD__.' is marked as DEPRECATED since v0.14.0 - this method will be removed in one of the upcoming versions';
        trigger_error($deprecatedMsg, E_USER_NOTICE);
        t3lib_div::sysLog($deprecatedMsg, $this->extKey, 1);
    
        $mailCharset = 'iso-8859-15';
        $templateCharset = $this->conf['templateCharsetFinalOrderMail'];
        #$siteCharset = tx_pttools_div::getSiteCharsetEncoding();
        $mailBodyError = (is_int(stripos($finalOrderPlaintext, 'Smarty error')) ? true : false); // recognize error in mail body
        
        // prepare final order as email 
        $orderHost      = t3lib_div::getIndpEnv('HTTP_HOST');
        $orderSender    = (!empty($this->conf['orderEmailSender']) ? 
                            $this->conf['orderEmailSender'] : $this->extKey.'@'.$orderHost);
        $mailRecipient  = ($this->conf['sendFinalOrderEmailToCustomer'] == 0 || $mailBodyError == true ?  
                           '' : $GLOBALS['TSFE']->fe_user->user['email']); // do not send to customer on error or if disabled in TS config
        $mailSubject    = sprintf($this->pi_getLL('orderEmail_subject', '[Online order]'), $orderHost);
        $mailHeaders    = "MIME-Version: 1.0\r\n".
                          "Content-Type: text/plain; charset=".$mailCharset."\r\n".
                          "Content-Transfer-Encoding: 8bit\r\n".
                          "From: ".$orderSender."\r\n".
                          (!empty($this->conf['orderEmailRecipient']) ? "Cc: ".$this->conf['orderEmailRecipient']."\r\n" : '').
                          (!empty($this->conf['orderConfirmationEmailBcc']) ? "Bcc: ".$this->conf['orderConfirmationEmailBcc']."\r\n" : '').
                          (!empty($this->conf['orderConfirmationEmailReplyTo']) ? "Reply-To: ".$this->conf['orderConfirmationEmailReplyTo']."\r\n" : '');
        $mailMessage    = ($templateCharset != $mailCharset ? 
                           iconv($templateCharset, $mailCharset.'//IGNORE', $finalOrderPlaintext) : $finalOrderPlaintext);
        
        // send order confirmation mail or display it as text on mailing error #### TODO: better error handling if mailing fails
        if (!mail($mailRecipient, $mailSubject, $mailMessage, $mailHeaders) && $mailBodyError == false) {
            die("<pre>".$mailMessage."</pre>");
        }
        
    }
    
    /**
     * Prepares the order overview: updates cart, prepares the order and returns the HTML of the order overview page
     * 
     * @param   void
     * @return  string      HTML code of the order overview
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-08-05
     */ 
    protected function processOrderOverview() {
        
        // get up-to-date article and pricing data for all items contained in shopping cart
        $this->cartObj->updateItemsData($this->customerObj->get_priceCategory(), $this->customerObj->get_gsaMasterAddressId()); 
                
        // remove all empty deliveries from order (empty = not containing any articles anymore) 
        $this->orderObj->removeEmptyDeliveries();
            
        // set up-to-date net price and tax free flags for the order (depending on current FE customer's legitimations)  
        $this->orderObj->set_isNet($this->customerObj->getNetPriceLegitimation());  
        $this->orderObj->set_isTaxFree($this->customerObj->getTaxFreeLegitimation()); 
        // IMPORTANT NOTE: set_isNet()/set_isTaxFree must not to be used in main() since this could change an existing order after pressing the "final order" button!
        
        // if coming from cart checkout, (re-)initialize the order (set the order's billing address and set a new delivery collection with only one delivery)
        if ($this->piVars['checkOut'] == 1) { 
        
            // set the order's billing address with customer's default billing address
            $this->orderObj->set_billingAddrObj($this->customerObj->getDefaultBillingAddress());
            
            // set the order's current user ID (available for logged in users only)
            $this->orderObj->set_feCrUserId($this->customerObj->get_feUserId());
            
            // set the order's payment method
            $this->orderObj->set_paymentMethodObj($this->customerObj->getPaymentObject());
            
            // set initial delivery with cart as article collection and customer's default shipping address
            $initialDeliveryObj = new tx_ptgsashop_delivery(
                                       $this->cartObj, 
                                       $this->customerObj->getDefaultShippingAddress(), 
                                       $this->orderObj->get_isNet(),
                                       $this->orderObj->get_isTaxFree()
                                  );
            $initialDeliveryCollectionObj = new tx_ptgsashop_deliveryCollection($initialDeliveryObj);
            $this->orderObj->set_deliveryCollObj($initialDeliveryCollectionObj);
            $this->orderObj->set_isMultDeliveries(false);
            
            // HOOK: allow multiple hooks to execute additional action
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi3_hooks']['processOrderOverview_checkoutHook'])) {
                foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi3_hooks']['processOrderOverview_checkoutHook'] as $className) {
                    $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                    $hookObj->processOrderOverview_checkoutHook($this);  // $this is passed as a reference
                }
            }  
        
        // if not coming from checkout (default): update the order for the current customer
        } else {
            $this->orderObj->updateOrder($this->customerObj);
        }
        
        return $this->displayOrderOverview();
        
    }
    
    /**
     * Checks the order form data and returns the result as a MessageBox-String on error 
     *
     * @param   void
     * @return  string      string of HTML messagebox if errors are found, else empty string (return from tx_pttools_formchecker::doFormCheck())
     * @see     tx_pttools_formchecker::doFormCheck()
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-07-31
     */
    protected function checkOrderForm() {
        
        $msgBox = '';
        $msg = '';
        
        if (!isset($this->piVars['overview_checkbox_terms'])) {
            $msg .= $this->pi_getLL('overview_checkbox_terms_error').'<br />';
        }
        if (!isset($this->piVars['overview_checkbox_withdrawal'])) {
            $msg .= $this->pi_getLL('overview_checkbox_withdrawal_error').'<br />';
        }
        
        if ($msg != '') {
            $msgBoxObj = new tx_pttools_msgBox('error', $msg);
            $msgBox = $msgBoxObj->__toString(); 
        }
        
        return $msgBox;
        
    } 
    
    /**
     * Checks the data submitted from the article distribution form, returns a messagebox on error
     *
     * @param   integer     ID of the article to distribute to multiple deliveries
     * @param   array       numbered 2-D array containing arrays [with keys 'del_to': (delivery address number of the customer's address collection), and 'del_qty': article quantity to deliver to this address]; passed by reference (del_qty values will be converted to integer)
     * @param   string      string containing HTML messagebox if errors are found, else empty string (passed by reference, returned with appopriate check result message))
     * @param   boolean     flag wether a quantity change (difference to cart quantity) is intended by user (default=0)
     * @global  
     * @return  integer     1= no quantity problems, everything ok; 2= intended quantity difference; 3= error: quantity check failed; 4= error: user tried to remove last article from order
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-10-10
     */
    protected function checkArticleDistributionForm($articleId, &$artDistrArr, &$msgBox, $qtyChangeIntended=0) {
        
        $articlesDistributed = 0;
        $articlesTotalInCart = $this->cartObj->getItem($articleId)->get_quantity();
       
        // convert all quantity values to integer and add them to total article distribution quantity
        foreach ($artDistrArr as $key=>$distribArr) {
            $artDistrArr[$key]['del_qty'] = tx_pttools_div::returnIntegerValue($distribArr['del_qty'], 1);
            $articlesDistributed += $artDistrArr[$key]['del_qty'];
        }
        
        // check if user tried to remove last article from order
        if ($articlesDistributed === 0 && $this->cartObj->count() === 1) {
            $msgBoxObj = new tx_pttools_msgBox('error', $this->pi_getLL('artdistr_error', '[Article distribution error]'));
            $msgBox = $msgBoxObj->__toString();
            return 4; // error: user tried to remove last article from order
        }
       
        // check if atricle distribution quantity matches cart article quantity
        if ($articlesDistributed != $articlesTotalInCart) {
            
            if ($qtyChangeIntended == 0) {
                $resultMsg = sprintf($this->pi_getLL('artdistr_warning_p1', '[Article distribution quantity warning]'), $articlesDistributed, $articlesTotalInCart).'<br /><br />'
                             .sprintf($this->pi_getLL('artdistr_warning_p2'), $this->pi_getLL('artdistrib_change_qty_confirm_1'), $this->pi_getLL('artdistr_upd_distribution_button'));
                $msgBoxObj = new tx_pttools_msgBox('warning', $resultMsg);
                $msgBox = $msgBoxObj->__toString();
                return 3; // error: quantity check failed
                
            } else {
                $msgBoxObj = new tx_pttools_msgBox('info', $this->pi_getLL('artdistr_upd_confirm'), $this->pi_getLL('artdistr_upd_confirm_header'));
                $msgBox = $msgBoxObj->__toString();     
                return 2; // success: intended quantity difference
            }
                
        }
        
        // default if no errors occured: article distribution can be stored
        $msgBoxObj = new tx_pttools_msgBox('info', $this->pi_getLL('artdistr_upd_confirm'), $this->pi_getLL('artdistr_upd_confirm_header'));
        $msgBox = $msgBoxObj->__toString();     
        return 1; // success: no quantity problems
          
    }    
    
    /**
     * Merges an delivery distribution array for an article and returns the merged array: merges all articles to go to one delivery into a new delivery distribution array
     *
     * @param   array       numbered 2-D array containing arrays with keys 'del_to': (delivery address number of the customer's address collection), and 'del_qty': article quantity to deliver to this address
     * @global  
     * @return  array       numbered array with delivery number as key and article quantity for this delivery as value
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-12-09
     */
    protected function returnMergedDistributionArray($artDistrArr) {
        
        trace($artDistrArr, 0, '$artDistrArr to merge'); 
        
        // merge all articles to go to one delivery into a new delivery distribution array
        $deliveryDistrArr  = array();
        foreach ($artDistrArr as $distribArr) {
            if (!array_key_exists($distribArr['del_to'], $deliveryDistrArr)) {
                $deliveryDistrArr[$distribArr['del_to']] = 0;
            }
            $deliveryDistrArr[$distribArr['del_to']] += $distribArr['del_qty'];
        }
        
        trace($deliveryDistrArr, 0, 'merged $deliveryDistrArr'); 
        return $deliveryDistrArr;
        
    }
    
    /**
     * Updates shopping cart and order according to changes made in the article distribution interface for a specified article
     *
     * @param   integer     ID of the article to update
     * @param   array       numbered 2-D array containing arrays with keys 'del_to': (delivery address number of the customer's address collection), and 'del_qty': article quantity to deliver to this address
     * @global  
     * @return  array       void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-02-23
     */
    protected function updateArtDistrQtyChangesConsequences($articleId, $artDistrArr) {
        
        $artTotalQty = 0;
        foreach ($artDistrArr as $distribArr) {
            $artTotalQty += $distribArr['del_qty'];
        }
            
        // HOOK: allow multiple hooks to execute additional action
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi3_hooks']['updateArtDistrQtyChangesConsequencesHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi3_hooks']['updateArtDistrQtyChangesConsequencesHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $hookObj->updateArtDistrQtyChangesConsequencesHook($this, $articleId, $artTotalQty);  // $this is passed as a reference
            }
        }  
        
        // set new quantity and related prices for the given article
        if ($artTotalQty > 0) {
            // update new total article qty in cart
            $this->cartObj->updateItemQuantity($articleId, $artTotalQty);
            
            // update the price calculation quantity of the article and it's depending retail pricing data in all deliveries of the order containing this article
            $this->orderObj->updateArticlePriceCalcQtyInAllDeliveries($articleId, $artTotalQty);
        } else {
            // remove article with total qty 0 from cart
            $this->cartObj->deleteItem($articleId);
        }
        
    }
    
    /**
     * Distributes an article to multiple deliveries of an order
     *
     * @param   integer     ID of the article to distribute to multiple deliveries
     * @param   array       numbered array with delivery number as key and article quantity for this delivery as value
     * @global  
     * @return  void
     * @throws  tx_pttools_exception   if no deliveries found in order
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-10-10
     */
    protected function distributeArticlesToDeliveries($articleId, $deliveryDistrArr) {
        
        // throw exception if no deliveries found in order
        if ($this->orderObj->countDeliveries() < 1) {
            throw new tx_pttools_exception('No deliveries found in order', 3);
        }
        
        // check distribution address in all deliveries from delivery collection
        foreach ($this->orderObj->get_deliveryCollObj() as $delKey=>$delObj) {
            
            // delete article preliminary from delivery (removes all "old" article quantities for all deliveries)
            $delObj->get_articleCollObj()->deleteItem($articleId);
            
            foreach ($deliveryDistrArr as $deliveryDistrKey=>$deliveryDistrQty) {
                
                // if distribution quantity > 0 and distribution address matches delivery address: re-add article and set new article quantity
                if ($deliveryDistrQty > 0 && $delKey == $deliveryDistrKey) {
                    
                    // re-add article to delivery (use article's clone from cart to keep presets)
                    $articleObj = clone($this->cartObj->getItem($articleId));
                    $delObj->get_articleCollObj()->addItem($articleObj);
                    
                    // set new article quantity in delivery
                    $delObj->get_articleCollObj()->getItem($articleId)->set_quantity($deliveryDistrQty, false); // do *not* update pricing data (keep pricing data from total article quantity in cart)
                    
                }
                
            } // end foreach distributions
            
        } // end foreach deliveries
        
        // remove all empty deliveries (not containing any articles anymore) from order
        $this->orderObj->removeEmptyDeliveries();
        
    }
    
    
    
    /***************************************************************************
     *   PRESENTATION METHODS (TODO: should be moved to presentator classes)
     **************************************************************************/
    
    /**
     * Generates and returns the HTML code of the order overview page containing billing and delivery (shipping) information
     * 
     * @param   string      (optional) HTML code of message box or empty string to display no message box (default:'')
     * @global  integer     $GLOBALS['TSFE']->id: page id of the current page
     * @return  string      HTML code of the order overview GUI
     * @throws  tx_pttools_exception   if no deliveries found in order
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-30 (non-template-based version generateOrderOverviewHTML(): 2005-04-11)
     */
    protected function displayOrderOverview($msgBox='') { 
        
        $markerArray = array();
        $taxArr = array();
        $paymentMethodString = 'bt'; // default payment method is bank transfer (value may be overwritten later in this function)
        
        // throw exception if no deliveries found in order
        if ($this->orderObj->countDeliveries() < 1) {
            throw new tx_pttools_exception('No deliveries found in order', 3);
        }
        
        
        // assign conditional message box
        if (!empty($msgBox)) {
            $markerArray['cond_displayMsgBox'] = true;
            $markerArray['msgBox'] = $msgBox;
        }
        
        
        // general template placeholders
        $markerArray['ll_overview_order_details'] = $this->pi_getLL('overview_order_details');
        $markerArray['currenyCode'] = $this->conf['currencyCode'];
        $markerArray['ll_overview_net'] = $this->pi_getLL('overview_net');
        $markerArray['ll_overview_gross'] = $this->pi_getLL('overview_gross');
        $markerArray['ll_overview_vat'] = $this->pi_getLL('overview_vat');
        
        
        // billing data template placeholders
        if ($this->orderObj->getOrderSumTotal(1) > 0) {
            
            // default billing data
            $markerArray['cond_displayBillingData'] = true;
            $markerArray['ll_overview_sum_total'] = $this->pi_getLL('overview_sum_total');
            $markerArray['ll_overview_billing_data'] = $this->pi_getLL('overview_billing_data');
            $markerArray['ll_overview_sum_articles'] = $this->pi_getLL('overview_sum_articles');
            $markerArray['ll_overview_sum_service_charge'] = $this->pi_getLL('overview_sum_service_charge');
            $markerArray['ll_payment_sum_total'] = $this->pi_getLL('overview_payment_sum_total');
            $markerArray['orderSumTotal_net'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($this->orderObj->getOrderSumTotal(1)));
            $markerArray['orderSumTotal_gross'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($this->orderObj->getOrderSumTotal(0)));
            $markerArray['orderPaymentSumTotal'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($this->orderObj->getPaymentSumTotal()));
            if ($this->conf['displayPaymentSumByDefault'] == 1 || ($this->orderObj->getOrderSumTotal(0) != $this->orderObj->getPaymentSumTotal())) {
                $markerArray['cond_displayPaymentSumTotal'] = true;
            }
            if ($this->orderObj->get_isNet() == 1) {
                $markerArray['cond_isNet'] = true;
                $markerArray['orderArticleSum'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($this->orderObj->getArticleSumTotal(1)));
                $markerArray['orderDispatchSum'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($this->orderObj->getDispatchSumTotal(1)));
            } else {
                $markerArray['cond_isNet'] = false;
                $markerArray['orderArticleSum'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($this->orderObj->getArticleSumTotal(0)));
                $markerArray['orderDispatchSum'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($this->orderObj->getDispatchSumTotal(0)));
            }
                // assign additional template placeholders (independent from default pt_gsashop template), added by ry44, 2008-11-12
            $markerArray['orderArticleNetSum'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($this->orderObj->getArticleSumTotal(1)));
            $markerArray['orderDispatchNetSum'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($this->orderObj->getDispatchSumTotal(1)));
            $markerArray['orderArticleGrossSum'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($this->orderObj->getArticleSumTotal(0)));
            $markerArray['orderDispatchGrossSum'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($this->orderObj->getDispatchSumTotal(0)));
            
            foreach ($this->orderObj->getOrderTaxTotalArray() as $taxcode=>$taxSubtotal) {
                $taxArr[] = array('taxRate' => sprintf("%4.1f", tx_ptgsashop_lib::getTaxRate($taxcode)),
                                  'taxSubTotal' => tx_ptgsashop_lib::getDisplayPriceString($taxSubtotal));
            }
            $markerArray['orderTaxArr'] = $taxArr;
            
            // order's billing adddress
            $markerArray['ll_overview_billing_addr'] = $this->pi_getLL('overview_billing_addr');
            $billingAddrObj = $this->orderObj->get_billingAddrObj();
            $markerArray['billingAddr'] = $billingAddrObj->getAddressLabel();
            
            // billing address change form (if allowed in TS): prepare backURL and other hidden input params for passing to the external user account plugin
            if ($this->conf['allowBillingAddrChange'] == 1) {
                $markerArray['cond_allowBillingAddrChange'] = true;
                $markerArray['ll_overview_edit'] = $this->pi_getLL('overview_edit');
                $markerArray['faction_editBillingAddr'] = $this->pi_getPageLink($this->conf['userAccountPage']);
                $markerArray['fname_editBillingAddrButton'] = self::USERACCOUNT_CLASS_NAME.'[edit_address]';
                $markerArray['fname_hiddenEditBillingAddrBackurl'] = self::USERACCOUNT_CLASS_NAME.'[backURL]';
                $billingAddrChangeBackUrl = $this->pi_getPageLink($GLOBALS['TSFE']->id, '',  array($this->prefixId.'[userreg_change_addr]' => 1,
                                                                                                   $this->prefixId.'[addr_change_target_key]' => -1)
                                                                 );
                $markerArray['fval_hiddenEditBillingAddrBackurl'] = $billingAddrChangeBackUrl; // backURL in hidden input field
                $markerArray['fname_hiddenEditBillingAddrAction'] = self::USERACCOUNT_CLASS_NAME.'[action]';
                $markerArray['fval_hiddenEditBillingAddrAction'] = 'choose'; // action command for user account plugin
                $markerArray['fname_hiddenEditBillingAddrReturnVar'] = self::USERACCOUNT_CLASS_NAME.'[return_pivar_name]';
                $markerArray['fval_hiddenEditBillingAddrReturnVar'] = $this->prefixId.'[addr_change_source_id]'; // name of the pivar to return the changed addresses id from the user account plugin
                $markerArray['fname_hiddenEditBillingAddrId'] = self::USERACCOUNT_CLASS_NAME.'[target_addr_id]'; // target_addr_id in hidden input field for usage in user account plugin (only needed for one customer project using the shop)
                $markerArray['fval_hiddenEditBillingAddrId'] = -1; // target_addr_id in hidden input field for usage in user account plugin (only needed for one customer project using the shop)
            }
            
        }
                
        
        // display "distribute to multiple deliveries" button if not chosen yet (and if multiple deliveries are allowed and if the order is distributable)
        if ($this->orderObj->get_isMultDeliveries() == false && $this->conf['allowMultipleDeliveries'] == 1 && $this->orderObj->getOrderIsDistributable() == true) {
            $markerArray['cond_displayMultDelButton'] = true;
            $markerArray['faction_multDelButton'] = $this->formActionSelf;
            $markerArray['ll_overview_multdel_button_prefix'] = $this->pi_getLL('overview_multdel_button_prefix');
            $markerArray['fname_multDelButton'] = $this->prefixId.'[overview_multdel_button]';
            $markerArray['ll_overview_multdel_button'] = $this->pi_getLL('overview_multdel_button');
            $markerArray['ll_overview_multdel_button_suffix'] = $this->pi_getLL('overview_multdel_button_suffix');
                        
        // display MsgBox with notice text if "distribute to multiple deliveries" button has been pressed just now
        } elseif (isset($this->piVars['overview_multdel_button'])) {
            $markerArray['cond_displayMultDelMsgBox'] = true;
            $msgBoxObj = new tx_pttools_msgBox('info', 
                                                sprintf($this->pi_getLL('overview_multdel_notice'), 
                                                $this->pi_getLL('overview_distribute_article')), 
                                                $this->pi_getLL('overview_multdel_notice_header')
                                              );
            $markerArray['msgBox_multipleDeliveries'] = $msgBoxObj->__toString();
        }
        
        
        // deliveries: assign default template placeholders for all deliveries
        $markerArray['ll_overview_delivery_articles'] = $this->pi_getLL('overview_delivery_articles'); 
        $markerArray['faction_editCart'] = $this->formActionSelf;
        $markerArray['fname_editCartButton'] = $this->prefixId.'[edit_cart]';
        $markerArray['ll_overview_edit_cart'] = $this->pi_getLL('overview_edit_cart');
        $markerArray['onClickAttribute_editCart'] = ($this->orderObj->get_isMultDeliveries() == true ? ' onclick="return confirm(\''.$this->pi_getLL('overview_edit_cart_warning').'\')"' : '');
        $markerArray['ll_overview_service_charge'] = $this->pi_getLL('overview_service_charge');
        $markerArray['ll_overview_delivery_sum'] = $this->pi_getLL('overview_delivery_sum');
        $markerArray['ll_overview_price_notice'] = ($this->orderObj->get_isNet()==true ? $this->pi_getLL('overview_price_notice_net') : $this->pi_getLL('overview_price_notice_gross'));
        $markerArray['ll_overview_shipping_addr'] = $this->pi_getLL('overview_shipping_addr');
        $markerArray['ll_overview_quantity'] = $this->pi_getLL('overview_quantity');
        $markerArray['ll_overview_price'] = $this->pi_getLL('overview_price');
        $markerArray['ll_overview_artno_abbrev'] = $this->pi_getLL('overview_artno_abbrev');
        
        // deliveries: shipping address change form (in enabled in TS config)
        if ($this->conf['allowShippingAddrChange'] == 1) {
            $markerArray['cond_allowShippingAddrChange'] = true;
            $markerArray['ll_overview_edit'] = $this->pi_getLL('overview_edit');
            $markerArray['faction_editShippingAddress'] = $this->pi_getPageLink($this->conf['userAccountPage']); // shipping address edit button form goes to user account plugin!
            $markerArray['fname_editShippingAddrButton'] = self::USERACCOUNT_CLASS_NAME.'[edit_address]';
            $markerArray['fname_hiddenEditShippingAddrBackurl'] = self::USERACCOUNT_CLASS_NAME.'[backURL]';
            $markerArray['fname_hiddenEditShippingAddrAction'] = self::USERACCOUNT_CLASS_NAME.'[action]';
            $markerArray['fval_hiddenEditShippingAddrAction'] = 'choose'; // action command for user account plugin
            $markerArray['fname_hiddenEditShippingAddrReturnVar'] = self::USERACCOUNT_CLASS_NAME.'[return_pivar_name]';
            $markerArray['fval_hiddenEditShippingAddrReturnVar'] = $this->prefixId.'[addr_change_source_id]'; // name of the pivar to return the changed addresses id from the user account plugin
            $markerArray['fname_hiddenEditShippingAddrId'] = self::USERACCOUNT_CLASS_NAME.'[target_addr_id]'; // target_addr_id in hidden input field for usage in user account plugin (only needed for one customer project using the shop)
        }
        // deliveries: article delivery dates (in enabled in TS config)
        if ($this->conf['enableArticleDeliveryDate'] == 1) {
            $markerArray['cond_useArticleDeliveryDate'] = true;
            $markerArray['ll_overview_artdel_start'] = $this->pi_getLL('overview_artdel_start');
            $markerArray['ll_overview_artdel_end'] = $this->pi_getLL('overview_artdel_end');
            $markerArray['ll_overview_artdel_edit'] = $this->pi_getLL('overview_artdel_edit');
            $markerArray['fname_hiddenArtDelArtId'] = $this->prefixId.'[artdel_article_id]';
            $markerArray['fname_hiddenArtDelDeliveryKey'] = $this->prefixId.'[artdel_delivery_key]';
            $markerArray['fname_artDelChangeButton'] = $this->prefixId.'[overview_artdel_change]';
            // HOOK for alternative setting of the form action of the article delivery date change button
            if (($hookObj = tx_pttools_div::hookRequest('pt_gsashop', 'pi3_hooks', 'displayOrderOverview_artDelChangeFormActionHook')) !== false) {
                $faction_artDelChange = (string)$hookObj->displayOrderOverview_artDelChangeFormActionHook($this); // use hook method if hook has been found
            // default setting = send to self URL
            } else {
                $faction_artDelChange = $this->formActionSelf;
            }
            $markerArray['faction_artDelChange'] = $faction_artDelChange;
        }
        // deliveries: article distribution to multiple deliveries
        if ($this->orderObj->get_isMultDeliveries() == true) {
            $markerArray['faction_distributeArt'] = $this->formActionSelf;
            $markerArray['ll_overview_distribute_article'] = $this->pi_getLL('overview_distribute_article');
            $markerArray['fname_hiddenDistrArtId'] = $this->prefixId.'[article_id]';
            $markerArray['fname_distrArtButton'] = $this->prefixId.'[overview_distribute_article]';
        }
        
        
        // process deliveries from delivery collection
        $delArr = array();
        $i = 0;
        $articleCounter = 0; // article counter for delivery titles
        foreach ($this->orderObj->get_deliveryCollObj() as $delKey=>$delObj) {
            
            // delivery title
            $delArr[$i]['ll_overview_delivery_title'] = sprintf($this->pi_getLL('overview_delivery_title', '[Delivery #'.$delKey.']'),
                                                                ($articleCounter + 1), 
                                                                ($articleCounter = $articleCounter + $delObj->get_articleCollObj()->countArticles()), 
                                                                $this->orderObj->countArticlesTotal()             
                                                        );
            // delivery articles processing
            $delArr[$i]['artRowArr'] = array();
            if ($delObj->get_articleCollObj()->count() > 0) {
                foreach ($delObj->get_articleCollObj() as $artKey=>$artObj) {
                    $artTplDataArr = array('artDescription' => tx_pttools_div::htmlOutput($artObj->get_description()),
                                           'artDelStart' => tx_pttools_div::htmlOutput(date("d.m.y", $artObj->get_artdelStartTs())),
                                           'artDelEnd' => tx_pttools_div::htmlOutput(date("d.m.y", $artObj->getArtdelEndTs())),
                                           'artQuantity' => tx_pttools_div::htmlOutput($artObj->get_quantity()),
                                           'artPrice'    => tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString(
                                                                $artObj->getDisplayPrice($this->orderObj->get_isNet()), 
                                                                $this->conf['currencyCode']
                                                            )),
                                           'artNumber'   => tx_pttools_div::htmlOutput($artObj->get_artNo()),
                                           'artId'       => tx_pttools_div::htmlOutput($artObj->get_id()),
                                           'artSubtotal' => tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($artObj->getItemSubtotal($this->orderObj->get_isNet()))),
                                           'deliveryKey' => tx_pttools_div::htmlOutput($delKey),
                                           'cond_displayDistributeButton' => ($this->orderObj->get_isMultDeliveries() == true ? $artObj->get_isPhysical() : false),  // sets distribution button flag for physical articles to true if multiple deliveries have been activated
                                          );
                    if (strlen($artObj->getAdditionalText()) > 0) {
                        $artTplDataArr['cond_additionalText'] = true; 
                        $artTplDataArr['additionalText'] = tx_pttools_div::htmlOutput($artObj->getAdditionalText()); 
                    }
                    if ($artObj->getFixedCost($this->orderObj->get_isNet()) > 0) {
                        $artTplDataArr['cond_fixedCost'] = true; 
                        $artTplDataArr['artFixedCostTotal'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString(
                                                                  $artObj->getFixedCost($this->orderObj->get_isNet()), 
                                                                  $this->conf['currencyCode']
                                                              ));
                        $artTplDataArr['ll_overview_artfixedcost_info'] = $this->pi_getLL('overview_artfixedcost_info'); 
                    }
                      
                    // HOOK: allow multiple hooks to manipulate the article template data array
                    if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi3_hooks']['displayOrderOverview_returnArtArrHook'])) {
                        foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi3_hooks']['displayOrderOverview_returnArtArrHook'] as $className) {
                            $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                            $artTplDataArr = $hookObj->displayOrderOverview_returnArtArrHook($this, $artObj, $artTplDataArr);
                        }
                    }  

                    $delArr[$i]['artRowArr'][] = $artTplDataArr; 
                }
            }
            // delivery sums
            $delArr[$i]['delDispatchCostTypeName'] = $delObj->get_dispatchObj()->get_displayName();
            $delArr[$i]['delDispatchCost'] = 
                    tx_ptgsashop_lib::getDisplayPriceString($delObj->getDeliveryDispatchCost($this->orderObj->get_isNet()));
            $delArr[$i]['delTotalSum_net'] = tx_ptgsashop_lib::getDisplayPriceString($delObj->getDeliveryTotal(1));
            $delArr[$i]['delTotalSum_gross'] = tx_ptgsashop_lib::getDisplayPriceString($delObj->getDeliveryTotal(0));
            $delArr[$i]['delTaxArr'] = array();
            foreach ($delObj->getDeliveryTaxTotalArray() as $taxcode=>$taxSubtotal) {
                $delArr[$i]['delTaxArr'][] = array('taxRate' => sprintf("%4.1f", tx_ptgsashop_lib::getTaxRate($taxcode)),
                                                   'taxSubTotal' => tx_ptgsashop_lib::getDisplayPriceString($taxSubtotal));
            }
            // delivery shipping address
            if ($delObj->getDeliveryIsPhysical() == true) {
                $delArr[$i]['cond_displayShippingAddr'] = true;
                $delArr[$i]['delAddress'] = $delObj->get_shippingAddrObj()->getAddressLabel();
                
                // shipping address change form (if allowed in TS): prepare backURL and other hidden input params for passing to the external user account plugin
                if ($this->conf['allowShippingAddrChange'] == 1) {
                    $shippingAddrChangeBackUrl = $this->pi_getPageLink($GLOBALS['TSFE']->id, '', array($this->prefixId.'[userreg_change_addr]' => 1,
                                                                                                       $this->prefixId.'[addr_change_target_key]' => $delKey)
                                                                      );
                    $delArr[$i]['fval_hiddenEditShippingAddrBackurl'] = $shippingAddrChangeBackUrl; // backURL in hidden input field
                    $delArr[$i]['fval_hiddenEditShippingAddrId']  = $delKey;  // target_addr_id in hidden input field for usage in user account plugin (only needed for one customer project using the shop)
                }
            } 
            
                      
            // HOOK: allow multiple hooks to manipulate the delivery template data array
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi3_hooks']['displayOrderOverview_returnDeliveryMarkersHook'])) {
                foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi3_hooks']['displayOrderOverview_returnDeliveryMarkersHook'] as $className) {
                    $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                    $delArr[$i] = $hookObj->displayOrderOverview_returnDeliveryMarkersHook($this, $delObj, $delArr[$i]);
                }
            } 
            
            $i++;
        
        } // end foreach (processing of deliveries)
        
        $markerArray['delArr'] = $delArr;
        
        
        
        // payment choice (extended GSA based payment choice box): only for order sums > 0 if TS config enableExtendedPaymentChoice is enabled
        if ($this->orderObj->getOrderSumTotal($this->orderObj->get_isNet()) > 0 && $this->conf['enableExtendedPaymentChoice'] == 1) {
            $paymentObj = $this->orderObj->get_paymentMethodObj();
            $paymentMethodString = $paymentObj->get_method();
            
            $markerArray['ll_overview_payment'] = $this->pi_getLL('overview_payment');
            $markerArray['cond_displayPaymentEditBox'] = true;
            $markerArray['faction_editPayment'] = $this->pi_getPageLink($this->conf['feUserRegPage']);
            $markerArray['fname_editPaymentButton'] = self::USERREG_CLASS_NAME.'[edit_payment]';
            $markerArray['fname_hiddenEditPaymentBackurl'] = self::USERREG_CLASS_NAME.'[backURL]';
            $markerArray['fval_hiddenEditPaymentBackurl'] = $this->pi_getPageLink($GLOBALS['TSFE']->id, '',  array($this->prefixId.'[userreg_change_payment]' => 1)); // backURL in hidden input field
            $markerArray['fname_hiddenEditPaymentAction'] = self::USERREG_CLASS_NAME.'[action]';
            $markerArray['fval_hiddenEditPaymentAction'] = 'bank'; // action command for userreg plugin
            $markerArray['fname_hiddenEditPaymentReturnVar'] = self::USERREG_CLASS_NAME.'[return_pivar_name]';
            $markerArray['fval_hiddenEditPaymentReturnVar'] = $this->prefixId.'[payment_account_index]'; // name of the pivar to return the used account index from the userreg plugin
            
            $markerArray['ll_overview_paymentMethod'] = $this->pi_getLL('overview_paymentMethod_'.$paymentObj->get_method());
            $markerArray['ll_overview_paymentNotice'] = $this->pi_getLL('overview_paymentNotice_'.$paymentObj->get_method());
            if ($paymentObj->get_method() == 'dd') {
                $markerArray['cond_paymentDirectDebit'] = true;
                if ($this->customerObj->get_isForeign() == 0) {
                    $markerArray['cond_paymentDdInland'] = true;
                    $markerArray['ll_overview_payment_bankAccountNo'] = $this->pi_getLL('overview_payment_bankAccountNo');
                    $markerArray['bankAccountNo'] = $paymentObj->get_bankAccountNo();
                    $markerArray['ll_overview_payment_bankCode'] = $this->pi_getLL('overview_payment_bankCode');
                    $markerArray['bankCode'] = $paymentObj->get_bankCode();
                } else {
                    $markerArray['cond_paymentDdInland'] = false;
                    $markerArray['ll_overview_payment_bankBic'] = $this->pi_getLL('overview_payment_bankBic');
                    $markerArray['bankBic'] = $paymentObj->get_bankBic();
                    $markerArray['ll_overview_payment_bankIban'] = $this->pi_getLL('overview_payment_bankIban');
                    $markerArray['bankIban'] = $paymentObj->get_bankIban();
                }
                $markerArray['ll_overview_payment_bankName'] = $this->pi_getLL('overview_payment_bankName');
                $markerArray['bankName'] = $paymentObj->get_bankName();
                $markerArray['ll_overview_payment_bankAccountHolder'] = $this->pi_getLL('overview_payment_bankAccountHolder');
                $markerArray['bankAccountHolder'] = $paymentObj->get_bankAccountHolder();
            }
        }
        
        
        // assign template placeholders: footer / "order now" form 
        $markerArray['faction_order'] = $this->formActionSelf;
        $markerArray['fname_payment'] = $this->prefixId.'[overview_payment]';
        
        // display simple payment options (radio buttons): only for order sums > 0 and TS config enableExtendedPaymentChoice is disabled and if TS config enableSimpleCcPaymentChoice is enabled
        if ($this->orderObj->getOrderSumTotal($this->orderObj->get_isNet()) > 0 && $this->conf['enableExtendedPaymentChoice'] == 0 && $this->conf['enableSimpleCcPaymentChoice'] == 1) {
            $markerArray['ll_overview_payment'] = $this->pi_getLL('overview_payment');
            $markerArray['cond_displayPaymentOptions'] = true;
            $markerArray['ll_overview_paymentMethod_bt'] = $this->pi_getLL('overview_paymentMethod_bt');
            $markerArray['cond_paymentBtChecked'] = (!isset($this->piVars['overview_payment']) || $this->piVars['overview_payment'] == 'bt' ? true : false);
            $markerArray['ll_overview_paymentMethod_cc'] = $this->pi_getLL('overview_paymentMethod_cc');
            $markerArray['cond_paymentCcChecked'] = ($this->piVars['overview_payment'] == 'cc' ? true : false);
        } else {
            $markerArray['fval_payment'] = $paymentMethodString;
        }
        
        $markerArray['fname_checkboxTerms'] = $this->prefixId.'[overview_checkbox_terms]';
        $markerArray['cond_checkboxTermsChecked'] = (isset($this->piVars['overview_checkbox_terms']) ? true : false);
        $markerArray['link_terms'] = $this->pi_getPageLink($this->conf['termsCondPage']);
        $markerArray['ll_overview_checkbox_terms_prefix'] = $this->pi_getLL('overview_checkbox_terms_prefix');
        $markerArray['ll_overview_checkbox_terms'] = $this->pi_getLL('overview_checkbox_terms');
        $markerArray['ll_overview_checkbox_terms_suffix'] = $this->pi_getLL('overview_checkbox_terms_suffix');
        $markerArray['fname_checkboxWithdrawal'] = $this->prefixId.'[overview_checkbox_withdrawal]';
        $markerArray['cond_checkboxWithdrawalChecked'] = (isset($this->piVars['overview_checkbox_withdrawal']) ? true : false);
        $markerArray['link_withdrawal'] = $this->pi_getPageLink($this->conf['withdrawalPage']);
        $markerArray['ll_overview_checkbox_withdrawal_prefix'] = $this->pi_getLL('overview_checkbox_withdrawal_prefix');
        $markerArray['ll_overview_checkbox_withdrawal'] = $this->pi_getLL('overview_checkbox_withdrawal');
        $markerArray['ll_overview_checkbox_withdrawal_suffix'] = $this->pi_getLL('overview_checkbox_withdrawal_suffix');
        $markerArray['fname_orderButton'] = $this->prefixId.'[overview_order_button]';
        $markerArray['ll_overview_order_button'] = $this->pi_getLL('overview_order_button');
        
        
        // HOOK: allow multiple hooks to manipulate $markerArray
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi3_hooks']['displayOrderOverview_MarkerArrayHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi3_hooks']['displayOrderOverview_MarkerArrayHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $markerArray = $hookObj->displayOrderOverview_MarkerArrayHook($this, $markerArray); // $this is passed as a reference (since PHP5) and can be manipulated in the hook method
            }
        }
        
        // return prepared template to display
        $smarty = new tx_pttools_smartyAdapter($this);
        foreach ($markerArray as $markerKey=>$markerValue) {
            $smarty->assign($markerKey, $markerValue);
        }
        $filePath = $smarty->getTplResFromTsRes($this->conf['templateFileOrderOverview']);
        trace($filePath, 0, 'Smarty template resource $filePath');
        return $smarty->fetch($filePath);
        
    }
     
    /**
     * Generates and returns the HTML code of an error page if the order has already been submitted
     * 
     * @param   void      
     * @return  string      HTML code of the error page
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-30 (non-template-based version generateSubmittedErrorHTML(): 2006-02-21)
     */
    protected function displayDoubleSubmissionError() { 
        
        $markerArray = array();
        
        $markerArray['ll_error_message'] = $this->pi_getLL('error_double_order_submission');
        
        
        // HOOK: allow multiple hooks to manipulate $markerArray
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi3_hooks']['displayDoubleSubmissionError_MarkerArrayHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi3_hooks']['displayDoubleSubmissionError_MarkerArrayHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $markerArray = $hookObj->displayDoubleSubmissionError_MarkerArrayHook($this, $markerArray); // $this is passed as a reference (since PHP5) and can be manipulated in the hook method
            }
        }
        
        // return prepared template to display
        $smarty = new tx_pttools_smartyAdapter($this);
        foreach ($markerArray as $markerKey=>$markerValue) {
            $smarty->assign($markerKey, $markerValue);
        }
        $filePath = $smarty->getTplResFromTsRes($this->conf['templateFileOrderError']);
        trace($filePath, 0, 'Smarty template resource $filePath');
        return $smarty->fetch($filePath);
        
    }
    
    /**
     * Generates and returns the HTML code of the article distribution GUI
     *
     * @param   integer     ID of the article to distribute to multiple deliveries
     * @param   string      (optional) string containing HTML of message box or empty string (default)
     * @param   array       (optional) numbered array with delivery number as key and article quantity for this delivery as value
     * @param   boolean     (optional) flag wether a checkbox to change article quantity in cart should be displayed (default=0)
     * @global  
     * @return  string      HTML code of the article distrubution GUI
     * @throws  tx_pttools_exception   if article cannot be found in current cart
     * @throws  tx_pttools_exception   if no deliveries found in order 
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-07-11 (non-template-based version generateArticleDistributionHTML(): 2005-10-07)
     */
    protected function displayArticleDistribution($articleId, $msgBox='', $deliveryDistrArr=array(), $displayCartChangeCheckbox=0, $displayKeepQtyButton=0) { 
        
        $markerArray = array();
        
        // throw exception if article cannot be found in current cart
        if ($this->cartObj->getItem($articleId) == false) {
            throw new tx_pttools_exception('Article not found in current cart', 3);
        }
        
        // throw exception if no deliveries found in order
        if ($this->orderObj->countDeliveries() < 1) {
            throw new tx_pttools_exception('No deliveries found in order', 3);
        }
        
        
        // assign default placeholders
        $markerArray['ll_artdistr_header'] = $this->pi_getLL('artdistr_header');
        $markerArray['ll_artdistr_article_prefix'] = $this->pi_getLL('artdistr_article_prefix');
        $markerArray['ll_artdistr_article'] = $this->pi_getLL('artdistr_article');
        $markerArray['artDescription'] = tx_pttools_div::htmlOutput($this->cartObj->getItem($articleId)->get_description());
        $markerArray['ll_artdistr_in_cart'] = $this->pi_getLL('artdistr_in_cart');
        $markerArray['articleQty'] = tx_pttools_div::htmlOutput($this->cartObj->getItem($articleId)->get_quantity());
        
        $markerArray['faction_artDistr'] = $this->formActionSelf;
        $markerArray['ll_artdistr_deliver_to'] = $this->pi_getLL('artdistr_deliver_to');
        $markerArray['fname_hiddenArticleId'] = $this->prefixId.'[article_id]';
        $markerArray['fval_hiddenArticleId'] = tx_pttools_div::htmlOutput($articleId);
        $markerArray['fname_artdistribUpdButton'] = $this->prefixId.'[artdistr_upd_distribution]';
        $markerArray['ll_artdistr_upd_distribution_button'] = $this->pi_getLL('artdistr_upd_distribution_button');
        
        // add delivery to new address form: prepare backURL params and params for passing to the external user account plugin
        $markerArray['faction_addNewAddress'] = $this->pi_getPageLink($this->conf['userAccountPage']);
        $markerArray['ll_artdistr_add_new_address_question'] = $this->pi_getLL('artdistr_add_new_address_question');  
        $markerArray['ll_artdistr_add_new_address_button_prefix'] = $this->pi_getLL('artdistr_add_new_address_button_prefix');
        $markerArray['fname_newAddressButton'] = self::USERACCOUNT_CLASS_NAME.'[new_address]';
        $markerArray['ll_artdistr_add_new_address_button'] = $this->pi_getLL('artdistr_add_new_address_button');
        $markerArray['fname_hiddenNewDelAddrBackurl'] = self::USERACCOUNT_CLASS_NAME.'[backURL]';
        $addNewDelAddrBackUrl = $this->pi_getPageLink($GLOBALS['TSFE']->id, '', array($this->prefixId.'[artdistr_new_delivery]' => 1, 
                                                                                      $this->prefixId.'[article_id]' => $articleId,
                                                                                      $this->prefixId.'[__formToken]' => $this->formReloadHandler->createToken())  // form token to prevent double addition of a new delivery on page reload when returning from the user account plugin
                                                     );
        $markerArray['fval_hiddenNewDelAddrBackurl'] = $addNewDelAddrBackUrl; // backURL in hidden input field
        $markerArray['fname_hiddenNewDelAddrAction'] = self::USERACCOUNT_CLASS_NAME.'[action]';
        $markerArray['fval_hiddenNewDelAddrAction'] = 'new'; // action command for user account plugin
        $markerArray['fname_hiddenNewDelAddrReturnVar'] = self::USERACCOUNT_CLASS_NAME.'[return_pivar_name]';
        $markerArray['fval_hiddenNewDelAddrReturnVar'] = $this->prefixId.'[new_del_addr_source_id]'; // name of the pivar to return the changed addresses id from the user account plugin
        
        $markerArray['faction_return'] = $this->formActionSelf;
        $markerArray['ll_artdistr_return_prefix'] = $this->pi_getLL('artdistr_return_prefix');
        $markerArray['fname_returnButton'] = $this->prefixId.'[artdistr_return]';
        $markerArray['ll_artdistr_return_button'] = $this->pi_getLL('artdistr_return_button');
        $markerArray['ll_artdistr_tips_header'] = $this->pi_getLL('artdistr_tips_header');
        $markerArray['ll_artdistr_tips_1'] = $this->pi_getLL('artdistr_tips_1');
        $markerArray['ll_artdistr_tips_2'] = $this->pi_getLL('artdistr_tips_2');
        $markerArray['ll_artdistr_tips_3'] = sprintf($this->pi_getLL('artdistr_tips_3'), $this->pi_getLL('artdistr_upd_distribution_button'));
        
        
        // assign placeholders: message box (conditional)
        if (!empty($msgBox)) {
            $markerArray['cond_displayMsgBox'] = true;
            $markerArray['msgBox'] = $msgBox;
        }
        
        // process deliveries from delivery collection (and assign as delivery array)
        $delArr = array();
        $i = 0;
        foreach ($this->orderObj->get_deliveryCollObj() as $delKey=>$delObj) {
            
            // retrieve article quantity per delivery
            $articleQty = 0;
            if (!empty($deliveryDistrArr)) {
                $articleQty = (integer)$deliveryDistrArr[$delKey];
            } elseif ($delObj->get_articleCollObj()->getItem($articleId) != false) {
                $articleQty = $delObj->get_articleCollObj()->getItem($articleId)->get_quantity();
            }
            
            $delArr[$i]['fname_delQty'] = $this->prefixId.'[artdistrib]['.$delKey.'][del_qty]';
            $delArr[$i]['fval_delQty'] = tx_pttools_div::htmlOutput($articleQty);
            $delArr[$i]['fname_delTo'] = $this->prefixId.'[artdistrib]['.$delKey.'][del_to]';
            $delArr[$i]['foptions_delTo']= $this->orderObj->get_deliveryCollObj()->generateDeliverySelectionOptionsHTML($delObj->get_shippingAddrObj());
            
            $i++;
        }
        $markerArray['delArr'] = $delArr;
        
        
        // assign placeholders: cart change quantity checkbox and dependencies (conditional)
        if ($displayCartChangeCheckbox == 1) {
            $markerArray['cond_displayCartChangeCheckbox'] = true;
            $markerArray['fname_artdistribChangeQtyCheckbox'] = $this->prefixId.'[artdistrib_change_qty]';
            $markerArray['ll_artdistrib_change_qty_confirm_1'] = $this->pi_getLL('artdistrib_change_qty_confirm_1');
            $markerArray['ll_artdistrib_change_qty_confirm_2'] = $this->pi_getLL('artdistrib_change_qty_confirm_2');
            $markerArray['ll_artdistrib_change_qty_confirm_3'] = $this->pi_getLL('artdistrib_change_qty_confirm_3');
            $markerArray['fname_artdistribKeepButton'] = $this->prefixId.'[artdistr_keep_distribution]';
            $markerArray['ll_artdistr_keep_distribution_button'] = $this->pi_getLL('artdistr_keep_distribution_button');
        }
        
//        // prepare new delivery selectorbox (only used if addresses are left that are not used for deliveries yet)
//        $newDeliveryOptions = $this->customerObj->get_shippingAddrCollObj()->generateAddressSelectionOptionsHTML(
//                                                                              $this->conf['noOfStringFieldsPerOption'], 
//                                                                              NULL, 
//                                                                              $this->orderObj->getAllDeliveryAdresses()
//                                                                             );
        
        
        // assign placeholders: new delivery selectorbox and dependencies (conditional, display only if addresses are left that are not used for deliveries yet)
        $hideAddrIdArr = array();
        foreach ($this->orderObj->getAllDeliveryAdresses() as $delAddrObj) {
            $hideAddrIdArr[] = $delAddrObj->get_uid();
        }
        $newDeliveryAddrArr = $this->customerObj->get_postalAddrCollObj()->getAddressSelectionArray($hideAddrIdArr);
        
        if (!empty($newDeliveryAddrArr)) {
            $markerArray['cond_displayNewDelSelectorbox'] = true;
            $markerArray['ll_artdistr_new_delivery_prefix'] = $this->pi_getLL('artdistr_new_delivery_prefix');
            $markerArray['ll_artdistr_new_delivery'] = $this->pi_getLL('artdistr_new_delivery');
            $markerArray['faction_newDelAddress'] = $this->formActionSelf;
            $markerArray['fname_newDelAddress'] = $this->prefixId.'[new_del_addr_source_id]';
            $markerArray['foptions_newDelAddress'] = $newDeliveryAddrArr;
            $markerArray['fhidden_reloadHandlerToken'] = $this->formReloadHandler->returnTokenHiddenInputTag($this->prefixId.'[__formToken]');
            $markerArray['fname_newDeliveryButton'] = $this->prefixId.'[artdistr_new_delivery]';
            $markerArray['ll_artdistr_new_delivery_button'] = $this->pi_getLL('artdistr_new_delivery_button');
        }
        
        
        // HOOK: allow multiple hooks to manipulate $markerArray
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi3_hooks']['displayArticleDistribution_MarkerArrayHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi3_hooks']['displayArticleDistribution_MarkerArrayHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $markerArray = $hookObj->displayArticleDistribution_MarkerArrayHook($this, $markerArray); // $this is passed as a reference (since PHP5) and can be manipulated in the hook method
            }
        }
        
        // return prepared template to display
        $smarty = new tx_pttools_smartyAdapter($this);
        foreach ($markerArray as $markerKey=>$markerValue) {
            $smarty->assign($markerKey, $markerValue);
        }
        $filePath = $smarty->getTplResFromTsRes($this->conf['templateFileArticleDistribution']);
        trace($filePath, 0, 'Smarty template resource $filePath');
        return $smarty->fetch($filePath);
        
    }
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/pi3/class.tx_ptgsashop_pi3.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/pi3/class.tx_ptgsashop_pi3.php']);
}

?>
