<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2006-2008 Rainer Kuhn (kuhn@punkt.de)
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
 * Frontend Plugin 'GSA Shop: Workflow / Order archive' for the 'pt_gsashop' extension.
 *
 * $Id: class.tx_ptgsashop_pi4.php,v 1.77 2008/12/11 14:40:38 ry44 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2006-02-24
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
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_sessionOrder.php';  // GSA Shop session order class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_order.php';  // GSA Shop order class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_cart.php';  // GSA Shop cart class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_workflowStatus.php';// GSA Shop workflow status class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_workflow.php';// GSA Shop workflow class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_orderWrapperCollection.php';  // GSA Shop order wrapper collection class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_sessionFeCustomer.php';  // GSA shop session frontend customer class


/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_smartyAdapter.php';  // Smarty template engine adapter
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_msgBox.php'; // message box class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_finance.php'; // library class with finance related static methods
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_formReloadHandler.php'; // web form reload handler class
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_feCustomer.php';  // GSA frontend customer class




/**
 * Debugging config for development
 */
#$trace     = 1; // (int) trace options @see tx_pttools_debug::trace() [for local temporary debugging use only, please COMMENT OUT this line if finished with debugging!]
#$errStrict = 1; // (bool) set strict error reporting level for development (requires $trace to be set to 1)  [for local temporary debugging use only, please COMMENT OUT this line if finished with debugging!]



// debugging output for development (uncomment to use)
//echo '<pre>'; 
//Reflection::export(new ReflectionClass('Iterator'));
//Reflection::export(new ReflectionClass('IteratorAggregate'));
//Reflection::export(new ReflectionClass('ArrayIterator'));
//Reflection::export(new ReflectionClass('ArrayObject'));
//echo '</pre>'; 
#trace(TYPO3_db);
#trace($TYPO3_CONF_VARS);
#trace(t3lib_div::GPvar('tx_ptgsashop_pi4'));
#trace($_POST, 0, '$_POST');
#trace($GLOBALS['TSFE'], 0, '$GLOBALS[TSFE]');
#trace($GLOBALS['TSFE']->fe_user, 0, '$GLOBALS[TSFE]->fe_user');
#trace($GLOBALS['TSFE']->fe_user->sesData, 0, '$GLOBALS[TSFE]->fe_user->sesData');



/**
 * Provides the workflow / order archive features for the GSA based shop 
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2006-02-24
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_pi4 extends tslib_pibase {
    
    /**
     * tslib_pibase (parent class) instance variables
     */
    public $extKey = 'pt_gsashop';    // The extension key.
    public $prefixId = 'tx_ptgsashop_pi4';    // Same as class name
    public $scriptRelPath = 'pi4/class.tx_ptgsashop_pi4.php';    // Path to this script relative to the extension dir.
    
    /**
     * tx_ptgsashop_pi4 instance variables
     */
    protected $pluginType = '';           // (string) type of this plugin: 'workflow' (=workflow view) or 'archive' (=order archive view)
    protected $extConfArr = array();      // (array) basic extension configuration data from localconf.php (configurable in Extension Manager) 
    protected $formActionSelf = '';       // (string) address for HTML forms' 'action' attributes to send a form of this page to itself
    protected $formReloadHandler = NULL;  // (tx_pttools_formReloadHandler object) web form reload handler object
    
    protected $customerObj = NULL;        // (tx_ptgsashop_sessionFeCustomer object) frontend customer object (FE user who uses this plugin)
    protected $owObj = NULL;              // (tx_ptgsashop_orderWrapper object) order wrapper object
    
    protected $workflowObj = NULL;               // (tx_ptgsashop_workflow object) workflow object (set for plugin type 'workflow' only)
    protected $workflowListLimitCustomerId = 0;  // (integer) customer ID to limit results in workflow list view to (set for plugin type 'workflow' only)
    protected $workflowListLimitStatusCode = -1; // (integer) workflow status code to limit results in workflow list view to (set for plugin type 'workflow' only)
    
    /** 
     * Class Constants
     */
    const ORDERPLUGIN_CLASS_NAME = 'tx_ptgsashop_pi3'; // (string) class name of the order plugin to use combined with this plugin
    
    
    /***************************************************************************
     *   MAIN
     **************************************************************************/
    
    /**
     * Main method of the order archive / workflow plugin: Prepares plugin processing and interprets buttons to control plugin behaviour
     *
     * @param   string      HTML-Content of the plugin to be displayed within the TYPO3 page
     * @param   array       Global configuration for this plugin (mostly done in Constant Editor/TS setup)
     * @return  string      HTML plugin content for output on the page (if not redirected before)
     * @global  object      $GLOBALS['TSFE']: tslib_fe Object
     * @global  object      $GLOBALS['TYPO3_DB']: t3lib_db Object (TYPO3 DB API)
     * @throws  tx_pttools_exception   if no logged-in FE user found
     * @throws  tx_pttools_exception   if the workflow engine is disabled in plugin mode 'workflow'
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-02-24
     */
    public function main($content, $conf) {
               
        // ********** DEFAULT PLUGIN INITIALIZATION **********
        
        $this->conf = $conf; // Extension configuration (mostly taken from Constant Editor)
        $this->pi_setPiVarDefaults();
        $this->pi_loadLL();
        $this->pi_USER_INT_obj = 1; // Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
        
        // convert $this->cObj->data['pi_flexform']: parse original flexform XML data into PHP array
        $this->pi_initPIflexForm(); 
        
        // for TYPO3 3.8.0+: enable storage of last built SQL query in $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery for all query building functions of class t3lib_DB
        $GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;
        
        // debug tools for development (uncomment to use)
        #tx_ptgsashop_lib::clearSession(__FILE__, __LINE__); // unset all session objects
        trace('Plugin Type is: '.$this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'pluginType', 's_pluginType'));
        trace($this->conf, 0, '$this->conf'); // extension config data (mainly from TS setup/Constant Editor)
        trace($this->piVars, 0, '$this->piVars');
        #trace($this->cObj->data, 0, '$this->cObj->data'); // content element data, containing tx_ptgsashop_* plugin config
        trace($this->cObj->data['pi_flexform'], 0, '$this->cObj->data[pi_flexform]');
            
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
            
            // ********** CHECK PLUGIN REQUIREMENTS (REQUIRED FOR ALL OTHER STEPS) **********
            
            // throw exceptions if FE is not logged-in user, or if logged-in user is not GSA enabled and approved GSA online customer (stop further pi3 script processing)
            if (!$GLOBALS['TSFE']->loginUser) {
                throw new tx_pttools_exception('A logged-in frontend user is required to proceed.', 0);
            } elseif (tx_ptgsashop_sessionFeCustomer::getInstance()->getIsGsaAddressEnabled() != 1) {
                throw new tx_pttools_exception('A GSA enabled user is required to proceed.', 0);
            } elseif (tx_ptgsashop_sessionFeCustomer::getInstance()->getIsGsaOnlineCustomer() != 1) {
                throw new tx_pttools_exception('An GSA online customer approval is required to proceed. Please contact the shop operator.', 0);
            }
                
            // throw exception if plugin type is 'workflow' and the workflow engine is disabled (stop further pi4 script processing)
            if ($this->pluginType == 'workflow' && $this->conf['enableOrderWorkflow'] == 0) {
                throw new tx_pttools_exception('Workflow engine disabled', 2);
            }
            
            
            // ********** SET PLUGIN-OBJECT PROPERTIES **********
            
            // set plugin type from flexform
            $this->pluginType = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'pluginType', 's_pluginType');
            
            // get unique instance (Singleton) of current FE customer
            $this->customerObj = tx_ptgsashop_sessionFeCustomer::getInstance();
            
            // set self url for HTML form action attributes
            $this->formActionSelf = $this->pi_getPageLink($GLOBALS['TSFE']->id);
            
            // set form reload handler object
            $this->formReloadHandler = new tx_pttools_formReloadHandler;
            
            // for all non-listview modes: get current order wrapper object and create workflow object if plugin type is 'workflow'
            if (isset($this->piVars['ow_id'])) { 
                $this->owObj = new tx_ptgsashop_orderWrapper($this->piVars['ow_id']);
                
                if ($this->pluginType == 'workflow') {  
                    $this->workflowObj = new tx_ptgsashop_workflow(
                                                $this->owObj->get_orderObj(),
                                                $this->conf['workflowFinishStatusCode'], 
                                                $this->conf['workflowExtensionKey']
                                          );
                }
            }
            
            // set special properties plugin type 'workflow' (list view)
            if ($this->pluginType == 'workflow') {  
                $this->workflowListLimitCustomerId = ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'limitToCustomer', 's_workflowConfig') == 1 ? 
                                                      $this->customerObj->get_gsaMasterAddressId() : -1);
                $this->workflowListLimitStatusCode = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'limitStatus', 's_workflowConfig'); 
            }
            
            
            // ********** CONTROLLER: execute approriate method for any action command (retrieved form buttons/GET vars) **********
            
            // PLUGIN TYPE 'ARCHIVE'
            if ($this->pluginType == 'archive') {
                
                // [CMD] Archive order single view
                if (isset($this->piVars['listview_button_view'])) {
                    $content .= $this->exec_ar_singleView();
                // [CMD] Archive repeat order
                } elseif (isset($this->piVars['singleview_button_reorder'])) {                
                    $content .= $this->exec_ar_reorder();
                // [CMD] Archive default action: Display list view
                } else {                
                    $content .= $this->exec_ar_defaultAction();
                }
            
            // PLUGIN TYPE 'WORKFLOW'
            } elseif ($this->pluginType == 'workflow') {
                
                // [CMD] Workflow order single view
                if (isset($this->piVars['listview_button_view'])) { 
                    $content .= $this->exec_wf_singleView();
                // [CMD] Workflow status approval
                } elseif (isset($this->piVars['singleview_button_wf_appr'])) {                
                    $content .= $this->exec_wf_statusApproval();
                // [CMD] Workflow status denial
                } elseif (isset($this->piVars['singleview_button_wf_deny'])) {                
                    $content .= $this->exec_wf_statusDenial();
                // [CMD] Workflow initialisation (coming from external plugin: self::ORDERPLUGIN_CLASS_NAME!)
                } elseif (isset($this->piVars['init'])) {                
                    $content .= $this->exec_wf_init();
                // [CMD] Workflow default action: Display list view
                } else {                
                    $content .= $this->exec_wf_defaultAction();
                }
                
            }
            
            
            // ********** DEFAULT PLUGIN FINALIZATION **********
        
            
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
     * Controller default action for plugin type 'archive': display archive list view
     *
     * @param   void
     * @return  string      HTML plugin content for output on the page
     * @global  object      $GLOBALS['TSFE']: tslib_fe Object
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-07-10 (based on previous code from main() method)
     */
    protected function exec_ar_defaultAction() {
        
        $content = '';
        trace('[CMD] '.__METHOD__);

        $orderWrapperCollObj = new tx_ptgsashop_orderWrapperCollection($GLOBALS['TSFE']->fe_user->user['uid'], -1, -1, $this->conf['workflowFinishStatusCode']);        
                
        $content = $this->displayOrdersList($orderWrapperCollObj, $this->piVars['ow_id']);
        return $content;
        
    }
    
    /**
     * Controller action for plugin type 'archive': display selected order
     *
     * @param   void
     * @return  string      HTML plugin content for output on the page
     * @global  object      $GLOBALS['TSFE']: tslib_fe Object
     * @throws  tx_pttools_exception   if current user is not allowed to display the selected order
     * @author  Rainer Kuhn <kuhn@punkt.de>, Fabrizio Branca <branca@punkt.de>
     * @since   2006-07-10 (based on previous code code from main() method)
     */
    protected function exec_ar_singleView() {
        
        $content = '';
        $tmpdirty = false;
        $msgBox = '';
        trace('[CMD] '.__METHOD__);
        
        // authentication check: throw exception if order to display has a different user ID
        if ($this->owObj->get_creatorId() != $GLOBALS['TSFE']->fe_user->user['uid']) {
            throw new tx_pttools_exception('Access to order denied.', 4);
        }
           
        // check if addresses are "dirty" (Fabrizio Branca 2007-04)
        if ($this->owObj->get_orderObj()->get_billingAddrObj()->get_dirty()){
            $tmpdirty = true;
        } else {
            foreach ($this->owObj->get_orderObj()->get_deliveryCollObj() as $deliveryItem) {
                if ($deliveryItem->get_shippingAddrObj()->get_dirty()) {
                    $tmpdirty = true;
                }
            }
        }
        
        if ($tmpdirty) {
            // create HTML output of the message box
            $msgBoxObj = new tx_pttools_msgBox('warning', $this->pi_getLL('archived_address_changed'), $this->pi_getLL('archived_address_changed_header'));
            $msgBox = $msgBoxObj->__toString();
        } 
          
        $content = $this->displaySingleView($this->owObj->get_orderObj(), $this->piVars['ow_id'], 0, $this->checkSessionForUnfinishedData(), $msgBox);
        return $content;
        
    }
    
    /**
     * Controller action for plugin type 'archive': open selected archived order as new order and redirect to order plugin
     *
     * @param   void
     * @return  void
     * @global  object      $GLOBALS['TSFE']: tslib_fe Object
     * @throws  tx_pttools_exception   if current user is not allowed to open the selected order
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-07-10 (modified by Fabrizio Branca 2007-04)
     */
    protected function exec_ar_reorder() {
        
        trace('[CMD] '.__METHOD__);
        
        // authentication check: throw exception if order to open has a different user ID
        if ($this->owObj->get_creatorId() != $GLOBALS['TSFE']->fe_user->user['uid']) {
            throw new tx_pttools_exception('Access to order denied.', 4);
        }
        
        // Prepare repeat order: archived order will be used as new order and stored to session jointly with appropriate cart
        
        // delete possibly existent order from session
        $sessOrderObj = tx_ptgsashop_sessionOrder::getInstance();
        $sessOrderObj->createFromOrderObj($this->owObj->get_orderObj());
        // 2DFAB: TODO: (Fabrizio) was ist wenn Adresse sich geaendert hat? -> defaultadresse!
        
        // set new values
        $sessOrderObj->set_termsCondAccepted(false);  // reset checkbox flags from archived order
        $sessOrderObj->set_withdrawalAccepted(false); // reset checkbox flags from archived order
        $sessOrderObj->set_isNet($this->customerObj->getNetPriceLegitimation());  // set the order's net price flag depending on *current* FE customer's legitimation  
        $sessOrderObj->set_isTaxFree($this->customerObj->getTaxFreeLegitimation());  // set the order's tax free flag depending on *current* FE customer's legitimation
        $sessOrderObj->store();
        
        // empty possibly existent cart, fill it with articles from archived order and store cart to session
        $cartObj = tx_ptgsashop_cart::getInstance();
        $cartObj->delete();
        foreach ($sessOrderObj->get_deliveryCollObj() as $delKey=>$delObj) {
            foreach ($delObj->get_articleCollObj() as $articleKey=>$articleObj) {
               $cartObj->addItem($articleObj);
            }
        }
        $cartObj->store();
        
        // remove eventually existent "order submitted" flag from session
        tx_pttools_sessionStorageAdapter::getInstance()->delete(tx_ptgsashop_lib::SESSKEY_ORDERSUBMITTED);
        
        // redirect to order plugin (pi3)
        tx_pttools_div::localRedirect($this->pi_getPageLink($this->conf['orderPage']));
        
    }
    
    /**
     * Controller default action for plugin type 'workflow': display workflow list view
     *
     * @param   void
     * @return  string      HTML plugin content for output on the page
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-07-10 (based on previous code code from main() method)
     */
    protected function exec_wf_defaultAction() {     
        
        $content = '';
        trace('[CMD] '.__METHOD__);
        
        $content = $this->returnApplicableWorkflowGui(false, $this->piVars['ow_id']);
        return $content;
        
    }
    
    /**
     * Controller action for plugin type 'workflow': execute workflow status approval action & display list view
     *
     * @param   void
     * @return  string      HTML plugin content for output on the page
     * @global  object      $GLOBALS['TSFE']: tslib_fe Object
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-07-10 (based on previous code code from main() method)
     */
    protected function exec_wf_statusApproval() {  
        
        $content = '';
        trace('[CMD] '.__METHOD__);
        trace('WORKFLOW STATUS '.$this->owObj->get_statusCode().' APPROVED');
        
        if ($this->formReloadHandler->checkToken($this->piVars['__formToken']) == true) {      
            $currentFeUserActionPossible =  
                $this->workflowObj->execWfsApproval($this->owObj, $GLOBALS['TSFE']->id, $GLOBALS['TSFE']->fe_user->user['uid']);
        }
        
        $content = $this->returnApplicableWorkflowGui($currentFeUserActionPossible, $this->piVars['ow_id']); // 1. param decides here whether a user action is intended => single or list view
        return $content;
        
    }
    
    /**
     * Controller action for plugin type 'workflow': execute workflow status denial action & display list view
     *
     * @param   void
     * @return  string      HTML plugin content for output on the page
     * @global  object      $GLOBALS['TSFE']: tslib_fe Object
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-07-10 (based on previous code code from main() method)
     */
    protected function exec_wf_statusDenial() { 
        
        $content = '';
        $currentFeUserActionPossible = false;
        trace('[CMD] '.__METHOD__);
        trace('WORKFLOW STATUS '.$this->owObj->get_statusCode().' DENIED');
        
        if ($this->formReloadHandler->checkToken($this->piVars['__formToken']) == true) {    
            $this->workflowObj->execWfsDenial($this->owObj, $GLOBALS['TSFE']->id, $GLOBALS['TSFE']->fe_user->user['uid']);
        }
        
        $content = $this->returnApplicableWorkflowGui(false, $this->piVars['ow_id']);   // 1. param = false => no user action intended here, display list view
        return $content;
        
    }
    
    /**
     * Controller action for plugin type 'workflow': initialise workflow
     *
     * @param   void
     * @return  void
     * @global  object      $GLOBALS['TSFE']: tslib_fe Object
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-29
     */
    protected function exec_wf_init() { 
        
        $content = '';
        $currentFeUserActionPossible = false;
        trace('[CMD] '.__METHOD__);
        trace('Worklow initialised...');
        
        if ($this->formReloadHandler->checkToken($this->piVars['__formToken']) == true) {      
            $currentFeUserActionPossible = $this->workflowObj->initialise($this->owObj, $GLOBALS['TSFE']->id);
        }
        
        $content = $this->returnApplicableWorkflowGui($currentFeUserActionPossible, $this->piVars['ow_id']); // 1. param decides here whether a user action is intended => single or list view
        return $content;
        
    }
    
    /**
     * Controller action for plugin type 'workflow': display selected order in workflow
     *
     * @param   void
     * @return  string      HTML plugin content for output on the page
     * @throws  tx_pttools_exception   if current user is not allowed to display the selected order
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-07-10 (based on previous code code from main() method)
     */
    protected function exec_wf_singleView() {
        
        $content = '';
        trace('[CMD] '.__METHOD__);
        trace('WORKFLOW SINGLE VIEW: display selected order (current workflow status: '.$this->owObj->get_statusCode().')');
        
        $content = $this->returnApplicableWorkflowGui(true, $this->piVars['ow_id']);  // 1. param = true => user action intended, display single view
        return $content;
        
    }
    
    
    
    /***************************************************************************
     *   BUSINESS LOGIC METHODS
     **************************************************************************/
    
    /** 
     * Generates and returns the HTML code of the applicable workflow GUI (list view or single view) depending on the given param
     * 
     * @param   boolean     flag wether an action of the currently logged-in user is intended (this results in a workflow single view) or not (this results in a workflow list view)
     * @param   integer     (optional) ID of the currently used order wrapper (to display in single view or to highlight in list view)
     * @global  object      $GLOBALS['TSFE']: tslib_fe Object
     * @return  string      HTML code of the applicable workflow GUI (list view or single view)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-29
     */
    protected function returnApplicableWorkflowGui($currentFeUserActionIntended, $orderWrapperId=0) { 
        
        $guiContent = '';
        
        // action of the currently logged-in user is possible: SINGLE VIEW GUI
        if ($currentFeUserActionIntended == true && $orderWrapperId > 0) {
            
            // authentication check: throw exception if order to display has a different customer ID and logged-in user is not member of shop operator FE group
            if ($this->owObj->get_customerId() != $this->customerObj->get_gsaMasterAddressId() 
                && !in_array($this->conf['shopOperatorGroupUid'], $GLOBALS['TSFE']->fe_user->groupData['uid'])) {
                    throw new tx_pttools_exception('Access to order denied.', 4);
            }
            
            $orderObj = $this->owObj->get_orderObj();
            $wfsObj = $this->workflowObj->getWfs($this->owObj->get_statusCode());
            if ($wfsObj->get_updateOrder() == true) {
                // update (if configured for workflow status) displayed order (prices/dispatch cost/addresses/payment) for the originally creating customer
                $orderCreatorCustomerObj = new tx_ptgsauserreg_feCustomer($orderObj->get_feCrUserId());
                $orderObj->updateOrder($orderCreatorCustomerObj); // IMPORTANT: do NOT use $this->customerObj (=currently logged-in customer) here since he may be another user, e.g. the shop operator!
            }
            $guiContent = $this->displaySingleView($orderObj, $orderWrapperId, $wfsObj);
            
        // action of the currently logged-in user is not possible: LIST VIEW GUI
        } else {
            
            $orderWrapperCollObj = new tx_ptgsashop_orderWrapperCollection(-1, $this->workflowListLimitCustomerId, $this->conf['workflowFinishStatusCode'], $this->workflowListLimitStatusCode);
            $guiContent = $this->displayOrdersList($orderWrapperCollObj, $orderWrapperId);
            
        }
        
        return $guiContent;
        
    }
    
    /** 
     * Returns TRUE if a valid order or a valid cart could be found in session, FALSE otherwise
     * 
     * @param   void
     * @return  boolean     TRUE if a valid order or a valid cart has been found in session, FALSE otherwise
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-02
     */
    protected function checkSessionForUnfinishedData() { 
        
          // check possibly existent session order instance if it is a valid order object
         $sessionOrderObj = tx_pttools_sessionStorageAdapter::getInstance()->read(tx_ptgsashop_sessionOrder::SESSION_KEY_NAME);
         if (is_object($sessionOrderObj) && ($sessionOrderObj instanceof tx_ptgsashop_sessionOrder)) {
            if ($sessionOrderObj->countDeliveries() > 0) {
                return true; // valid order has been found
            }
         }
          // check possibly existent session cart instance if it is a valid order object
         $sessionCartObj = tx_pttools_sessionStorageAdapter::getInstance()->read(tx_ptgsashop_cart::SESSION_KEY_NAME);
         if (is_object($sessionCartObj) && ($sessionCartObj instanceof tx_ptgsashop_cart)) {
            if ($sessionCartObj->countArticles() > 0) {
                return true; // valid cart has been found
            }
         }
         
         return false; // no valid cart and no valid order has been found
         
    }
    
    
    
    /***************************************************************************
     *   PRESENTATION METHODS (TODO: should be moved to presentator classes)
     **************************************************************************/
    
    /**
     * Generates and returns the HTML code of an orders list view
     * 
     * @param   tx_ptgsashop_orderWrapperCollection      object of type tx_ptgsashop_orderWrapperCollection: order wrapper collection to list its orders
     * @param   integer     (optional) UID of the order wrapper database record to highlight in list view
     * @global  integer     id of the selected order to highlight in list
     * @return  string      HTML code of the list view
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-31 (non-template-based version generateListViewHTML(): 2006-02-24)
     */
    protected function displayOrdersList(tx_ptgsashop_orderWrapperCollection $owCollObj, $selectedOrderId=0) { 
        
        $markerArray = array();
        
        // assign template placeholders: list header and defaults
        $markerArray['ll_listview_action'] = $this->pi_getLL('listview_action');
        $markerArray['ll_listview_time'] = $this->pi_getLL('listview_time');
        $markerArray['ll_order_total'] = $this->pi_getLL('order_total') ;
        $markerArray['ll_net'] = $this->pi_getLL('net') ;
        $markerArray['ll_gross'] = $this->pi_getLL('gross');
        if ($this->pluginType == 'workflow') {
            $markerArray['cond_isWorkflow'] = true;
            $markerArray['ll_listview_creator'] = $this->pi_getLL('listview_creator');
            $markerArray['ll_listview_status'] = $this->pi_getLL('listview_status');
            $markerArray['ll_listview_lastUpdate'] = $this->pi_getLL('listview_lastUpdate');
            $markerArray['ll_listview_lastUser'] = $this->pi_getLL('listview_lastUser');
        } else {
            $markerArray['cond_isWorkflow'] = false;
            $markerArray['ll_relatedDocNo'] = $this->pi_getLL('relatedDocNo');
        }
        $markerArray['ll_listview_action'] = $this->pi_getLL('listview_action');
        $markerArray['ll_listview_button_view'] = $this->pi_getLL('listview_button_view');
        $markerArray['faction_listButtons'] = $this->formActionSelf;
        $markerArray['fname_viewButton'] = $this->prefixId.'[listview_button_view]';
        $markerArray['fname_orderIdHidden'] = $this->prefixId.'[ow_id]';
        
        // assign template placeholders: table content
        if ($owCollObj->count() > 0) {
            $markerArray['cond_previousOrdersFound'] = true;
            
            // process orders (order wrappers)
            $owArr = array();
            $i = 0;
            foreach ($owCollObj as $owObj) {
                $owArr[$i]['class_selected'] = ($owObj->get_uid() == $selectedOrderId ? ' '.$this->pi_getClassName('tablebgselected') : '');
                $owArr[$i]['orderId'] = tx_pttools_div::htmlOutput($owObj->get_uid());
                $owArr[$i]['orderDate'] = tx_pttools_div::htmlOutput(date('d.m.Y, H:i', $owObj->get_orderTimestamp()));
                $owArr[$i]['orderSumNet'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString(doubleval($owObj->get_sumNet()), $this->conf['currencyCode']));
                $owArr[$i]['orderSumGross'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString(doubleval($owObj->get_sumGross()), $this->conf['currencyCode']));
                if ($this->pluginType == 'workflow') {
                        // get user info
                    $creatorObj = new tx_ptgsauserreg_feCustomer($owObj->get_creatorId());
                    $lastUserObj = new tx_ptgsauserreg_feCustomer($owObj->get_lastUserId());
                    $owArr[$i]['orderCreator'] = tx_pttools_div::htmlOutput($creatorObj->getDisplayName());
                    $owArr[$i]['orderStatusCode'] = tx_pttools_div::htmlOutput($owObj->get_statusCode());
                    $owArr[$i]['orderLastUpdate'] = tx_pttools_div::htmlOutput(date('d.m.Y, H:i', $owObj->get_updateTimestamp()));
                    $owArr[$i]['orderLastUser'] = tx_pttools_div::htmlOutput($lastUserObj->getDisplayName());
                    unset($creatorObj); // delete large objects from memory...
                    unset($lastUserObj);
                        // get workflow info
                    $tmpWorkflowStatusObj = new tx_ptgsashop_workflowStatus($owObj->get_statusCode(), $this->conf['workflowExtensionKey']);
                    $owArr[$i]['cond_viewButton'] = $tmpWorkflowStatusObj->getViewAuth();  // getViewAuth() returns TRUE if the user is allowed to view the workflow status, FALSE otherwise
                    unset($tmpWorkflowStatusObj); 
                } else {
                    $owArr[$i]['cond_viewButton'] = true;
                    $owArr[$i]['orderRelDocNo'] = tx_pttools_div::htmlOutput($owObj->get_relatedDocNo());
                }
                      
                // HOOK: allow multiple hooks to manipulate the order wrapper template data array
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi4_hooks']['displayOrdersList_returnOrderWrapperMarkersHook'])) {
                    foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi4_hooks']['displayOrdersList_returnOrderWrapperMarkersHook'] as $className) {
                        $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                        $owArr[$i] = $hookObj->displayOrdersList_returnOrderWrapperMarkersHook($this, $owObj, $owArr[$i]);
                    }
                } 
                
                $i++;
            }
            $markerArray['ordersArr'] = $owArr;
            
        } else {
            
            // 'no orders found' message
            $markerArray['ll_listview_no_orders'] = $this->pi_getLL('listview_no_orders_'.$this->pluginType);
            
        }
        
        // HOOK: allow multiple hooks to manipulate $markerArray
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi4_hooks']['displayOrdersList_MarkerArrayHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi4_hooks']['displayOrdersList_MarkerArrayHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $markerArray = $hookObj->displayOrdersList_MarkerArrayHook($this, $markerArray); // $this is passed as a reference (since PHP5) and can be manipulated in the hook method
            }
        }
        
        // return prepared template to display
        $smarty = new tx_pttools_smartyAdapter($this);
        foreach ($markerArray as $markerKey=>$markerValue) {
            $smarty->assign($markerKey, $markerValue);
        }
        $filePath = $smarty->getTplResFromTsRes($this->conf['templateFileOrdersList']);
        trace($filePath, 0, 'Smarty template resource $filePath');
        return $smarty->fetch($filePath);
        
    }
    
    /**
     * Generates and returns the HTML code of the non-editable view of a single order
     * 
     * @param   tx_ptgsashop_order      order of type tx_ptgsashop_order
     * @param   integer     UID of the order's wrapper database record
     * @param   mixed       (optional) workflow status object of type tx_ptgsashop_workflowStatus for workflow mode, or NULL for order archive mode (default)
     * @param   boolean     (optional for order archive mode) flag wether a warning for overwriting session content should be displayed re-opening the displayed order (order archive mode: repeat order)
     * @param   string      (optional for order archive mode) HTML code of a message box
     * @return  string      HTML code of of a single order view
     * @throws  tx_pttools_exception   if no deliveries found in given order
     * @author  Rainer Kuhn <kuhn@punkt.de>, important modifications Fabrizio Branca <branca@punkt.de> 2007-04
     * @since   2006-04-10 (non-template-based version generateSingleViewHTML(): 2006-02-24)
     */
    protected function displaySingleView(tx_ptgsashop_order $orderObj, $orderWrapperId, $wfsObj=NULL, $sessionWarning=0, $msgBox='') {     
        
        $markerArray = array();    
            
        // throw exception if no deliveries found in order
        if ($orderObj->countDeliveries() < 1) {
            throw new tx_pttools_exception('No deliveries found in order', 3);
        }
        
        
        // assign template placeholders: header/general 
        $markerArray['ll_singleview_order_details'] = tx_pttools_div::htmlOutput(sprintf($this->pi_getLL('singleview_order_details'), $orderObj->getDate().' '.$orderObj->getTime()));
        $markerArray['ll_singleview_billing_data'] = $this->pi_getLL('singleview_billing_data');
        $markerArray['currenyCode'] = $this->conf['currencyCode'];
        
        // for workflow mode only: display update notice and order sums
        if ($wfsObj instanceof tx_ptgsashop_workflowStatus) {
            $markerArray['cond_workflowMode'] = true; 
            if ($wfsObj->get_updateOrder() == 1) {
                $markerArray['cond_workflowDynamicOrder'] = true; 
                $markerArray['ll_singleview_updated'] = sprintf($this->pi_getLL('singleview_updated'), date('d.m.Y H:i:s'));
                $markerArray['ll_singleview_updated_notice'] = $this->pi_getLL('singleview_updated_notice');
            }
            $markerArray['ll_singleview_billing_sum_total'] = $this->pi_getLL('singleview_billing_sum_total');
            $markerArray['ll_singleview_sum_articles'] = $this->pi_getLL('singleview_sum_articles');
            $markerArray['orderArticleSum'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($orderObj->getArticleSumTotal($orderObj->get_isNet())));
            $markerArray['ll_singleview_sum_service_charge'] = $this->pi_getLL('singleview_sum_service_charge');
            $markerArray['orderDispatchSum'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($orderObj->getDispatchSumTotal($orderObj->get_isNet())));
            $markerArray['ll_singleview_sum_total'] = $this->pi_getLL('singleview_sum_total');
            $markerArray['ll_singleview_price_notice'] = ($orderObj->get_isNet()==true ? $this->pi_getLL('singleview_price_notice_net') : $this->pi_getLL('singleview_price_notice_gross'));
            $markerArray['orderSumTotal'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($orderObj->getOrderSumTotal($orderObj->get_isNet())));
            
        // for order archive mode (default): display related ERP document number (GSA/dt.: "Vorgangsnummer")
        } else {
            $markerArray['cond_workflowMode'] = false;
            $markerArray['ll_relatedDocNo'] = $this->pi_getLL('relatedDocNo');
            $markerArray['archOrder_relatedDocNo'] = tx_pttools_div::htmlOutput($this->owObj->get_relatedDocNo());
            $markerArray['ll_singleview_billing_sum_net'] = $this->pi_getLL('singleview_billing_sum_net');
            $markerArray['archOrder_orderSumNet'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($this->owObj->get_sumNet()));
            $markerArray['ll_singleview_billing_sum_gross'] = $this->pi_getLL('singleview_billing_sum_gross');
            $markerArray['archOrder_orderSumGross'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($this->owObj->get_sumGross()));
        }
        
        // order's billing address
        $markerArray['ll_singleview_billing_addr'] = $this->pi_getLL('singleview_billing_addr');
        $billingAddrObj = $orderObj->get_billingAddrObj();
        $markerArray['billingAddr'] = $billingAddrObj->getAddressLabel();;
        
//        # alternative version for default addresses (non GSA addresses)
//        $billingAddr =
//            tx_pttools_div::htmlOutput($billingAddrObj->getFullName()).'<br />'.
//            ((bool)strlen($billingAddrObj->get_company()) ? tx_pttools_div::htmlOutput($billingAddrObj->get_company()).'<br />' : '').
//            tx_pttools_div::htmlOutput($billingAddrObj->get_streetAndNo()).'<br />'.
//            tx_pttools_div::htmlOutput($billingAddrObj->getCityWithZip()).'<br />'.
//            ((bool)strlen($billingAddrObj->get_state()) ? tx_pttools_div::htmlOutput($billingAddrObj->get_state()).'<br />' : '').
//            tx_pttools_div::htmlOutput($billingAddrObj->getCountryName($this->conf['staticCountriesLang'])).'<br />';
//        $markerArray['billingAddr'] = $billingAddr;
        
        
        // deliveries: assign default template placeholders for all deliveries
        $markerArray['ll_singleview_delivery_articles'] = $this->pi_getLL('singleview_delivery_articles'); 
        $markerArray['ll_singleview_service_charge'] = $this->pi_getLL('singleview_service_charge');
        $markerArray['ll_singleview_delivery_sum'] = $this->pi_getLL('singleview_delivery_sum');
        $markerArray['ll_singleview_price_notice'] = ($orderObj->get_isNet()==true ? $this->pi_getLL('singleview_price_notice_net') : $this->pi_getLL('singleview_price_notice_gross'));
        $markerArray['ll_singleview_shipping_addr'] = $this->pi_getLL('singleview_shipping_addr');
        $markerArray['ll_singleview_quantity'] = $this->pi_getLL('singleview_quantity');
        $markerArray['ll_singleview_price'] = $this->pi_getLL('singleview_price');
        $markerArray['ll_singleview_artno_abbrev'] = $this->pi_getLL('singleview_artno_abbrev');
        if ($orderObj->get_isMultDeliveries()==true) {
            #$markerArray['cond_displayDistributeButton'] = true;
            #$markerArray['faction_distributeArt'] = $this->formActionSelf;
            #$markerArray['ll_singleview_distribute_article'] = $this->pi_getLL('singleview_distribute_article');
            #$markerArray['fname_hiddenDistrArtId'] = $this->prefixId.'[art_distrib_article_id]';
            #$markerArray['fname_distrArtButton'] = $this->prefixId.'[singleview_distribute_article]';
        }
        
        
        // process deliveries from delivery collection
        $delArr = array();
        $i = 0;
        $articleCounter = 0; // article counter for delivery titles
        foreach ($orderObj->get_deliveryCollObj() as $delKey=>$delObj) {
            
            // delivery title
            $delArr[$i]['ll_singleview_delivery_title'] = sprintf($this->pi_getLL('singleview_delivery_title', '[Delivery #'.$delKey.']'),
                                                                ($articleCounter + 1), 
                                                                ($articleCounter = $articleCounter + $delObj->get_articleCollObj()->countArticles()), 
                                                                $orderObj->countArticlesTotal()             
                                                        );
            
            // delivery articles processing
            $delArr[$i]['artRowArr'] = array();
            if ($delObj->get_articleCollObj()->count() > 0) {
                foreach ($delObj->get_articleCollObj() as $artKey=>$artObj) {
                    $artTplDataArr = array('artDescription'    => tx_pttools_div::htmlOutput($artObj->get_description()),
                                           'artQuantity' => tx_pttools_div::htmlOutput($artObj->get_quantity()),
                                           'artPrice'    => tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString(
                                                                $artObj->getDisplayPrice($orderObj->get_isNet()), 
                                                                $this->conf['currencyCode']
                                                            )),
                                           'artNumber'   => tx_pttools_div::htmlOutput($artObj->get_artNo()),
                                           'artId'       => tx_pttools_div::htmlOutput($artObj->get_id()),
                                           'artSubtotal' => tx_pttools_div::htmlOutput(sprintf("%01.2f", $artObj->getItemSubtotal($orderObj->get_isNet())))
                                          );
                                          
                    if ($markerArray['cond_workflowMode'] == true && $artObj->getFixedCost($orderObj->get_isNet()) > 0) {
                        $artTplDataArr['cond_fixedCost'] = true; 
                        $artTplDataArr['artFixedCostTotal'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString(
                                                                  $artObj->getFixedCost($orderObj->get_isNet()), 
                                                                  $this->conf['currencyCode']
                                                              ));
                        $artTplDataArr['ll_singleview_artfixedcost_info'] = $this->pi_getLL('singleview_artfixedcost_info'); 
                    }
                    
                    $delArr[$i]['artRowArr'][] = $artTplDataArr;    
                }
            }
                 
                    
            // delivery sums
            $delArr[$i]['delDispatchCostTypeName'] = $delObj->get_dispatchObj()->get_displayName();
            $delArr[$i]['delDispatchCost'] = 
                    tx_ptgsashop_lib::getDisplayPriceString($delObj->get_dispatchObj()->getDispatchCostForGivenSum($delObj->get_articleCollObj()->getItemsTotal($orderObj->get_isNet())));
            $delArr[$i]['delTotalSum'] = 
                    tx_ptgsashop_lib::getDisplayPriceString($delObj->getDeliveryTotal($orderObj->get_isNet()));
                    
            // delivery shipping address
            $delArr[$i]['delAddress'] = $delObj->get_shippingAddrObj()->getAddressLabel();
            
//            # alternative version for default addresses (non GSA addresses)
//            $delArr[$i]['delAddress'] = 
//                tx_pttools_div::htmlOutput($delObj->get_shippingAddrObj()->getFullName()).'<br />'.
//                ((bool)strlen($delObj->get_shippingAddrObj()->get_company()) ? tx_pttools_div::htmlOutput($delObj->get_shippingAddrObj()->get_company()).'<br />' : '').
//                tx_pttools_div::htmlOutput($delObj->get_shippingAddrObj()->get_streetAndNo()).'<br />'.
//                tx_pttools_div::htmlOutput($delObj->get_shippingAddrObj()->getCityWithZip()).'<br />'.
//                ((bool)strlen($delObj->get_shippingAddrObj()->get_state()) ? tx_pttools_div::htmlOutput($delObj->get_shippingAddrObj()->get_state()).'<br />' : '').
//                tx_pttools_div::htmlOutput($delObj->get_shippingAddrObj()->getCountryName($this->conf['staticCountriesLang'])).'<br />';
            
                      
            // HOOK: allow multiple hooks to manipulate the delivery template data array
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi4_hooks']['displaySingleView_returnDeliveryMarkersHook'])) {
                foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi4_hooks']['displaySingleView_returnDeliveryMarkersHook'] as $className) {
                    $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                    $delArr[$i] = $hookObj->displaySingleView_returnDeliveryMarkersHook($this, $delObj, $delArr[$i]);
                }
            } 
            
            $i++;
        
        } // end foreach (processing of deliveries)
        $markerArray['delArr'] = $delArr;
        
        
        // payment (extended GSA based payment box): only for order sums > 0 if TS config enableExtendedPaymentChoice is enabled
        if ($orderObj->getOrderSumTotal($orderObj->get_isNet()) > 0 && $this->conf['enableExtendedPaymentChoice'] == 1) {
            $paymentObj = $orderObj->get_paymentMethodObj();
            
            $markerArray['ll_singleview_payment'] = $this->pi_getLL('singleview_payment');
            $markerArray['cond_displayPaymentEditBox'] = true;
            $markerArray['ll_singleview_paymentMethod'] = $this->pi_getLL('singleview_paymentMethod_'.$paymentObj->get_method());
            $markerArray['ll_singleview_paymentNotice'] = $this->pi_getLL('singleview_paymentNotice_'.$paymentObj->get_method());
            if ($paymentObj->get_method() == 'dd') {
                $markerArray['cond_paymentDirectDebit'] = true;
                if ($this->customerObj->get_isForeign() == 0) {
                    $markerArray['cond_paymentDdInland'] = true;
                    $markerArray['ll_singleview_payment_bankAccountNo'] = $this->pi_getLL('singleview_payment_bankAccountNo');
                    $markerArray['bankAccountNo'] = $paymentObj->get_bankAccountNo();
                    $markerArray['ll_singleview_payment_bankCode'] = $this->pi_getLL('singleview_payment_bankCode');
                    $markerArray['bankCode'] = $paymentObj->get_bankCode();
                } else {
                    $markerArray['cond_paymentDdInland'] = false;
                    $markerArray['ll_singleview_payment_bankBic'] = $this->pi_getLL('singleview_payment_bankBic');
                    $markerArray['bankBic'] = $paymentObj->get_bankBic();
                    $markerArray['ll_singleview_payment_bankIban'] = $this->pi_getLL('singleview_payment_bankIban');
                    $markerArray['bankIban'] = $paymentObj->get_bankIban();
                }
                $markerArray['ll_singleview_payment_bankName'] = $this->pi_getLL('singleview_payment_bankName');
                $markerArray['bankName'] = $paymentObj->get_bankName();
                $markerArray['ll_singleview_payment_bankAccountHolder'] = $this->pi_getLL('singleview_payment_bankAccountHolder');
                $markerArray['bankAccountHolder'] = $paymentObj->get_bankAccountHolder();
            }
        }
        
        
        // assign template placeholders: footer and user interaction buttons
        $markerArray['faction_singleview'] = $this->formActionSelf;
        $markerArray['fname_hiddenOrderWrapperId'] = $this->prefixId.'[ow_id]';
        $markerArray['fhidden_reloadHandlerToken'] = $this->formReloadHandler->returnTokenHiddenInputTag($this->prefixId.'[__formToken]');
        $markerArray['orderWrapperId'] = tx_pttools_div::htmlOutput($orderWrapperId); 
        $markerArray['fname_returnToListViewButton'] = $this->prefixId.'[singleview_button_listview]';
        $markerArray['ll_singleview_button_listview'] = $this->pi_getLL('singleview_button_listview');
        
        // for workflow mode only: display workflow status and choice options
        if ($wfsObj instanceof tx_ptgsashop_workflowStatus) {
            $markerArray['cond_workflowMode'] = true; 
            $markerArray['ll_workflow_statuscode'] = $this->pi_getLL('workflow_statuscode');
            $markerArray['wfsStatusCode'] = tx_pttools_div::htmlOutput($wfsObj->get_statusCode());
            $markerArray['wfsLabelChoice'] = tx_pttools_div::htmlOutput($wfsObj->get_labelChoice());
            $markerArray['fname_approveButton'] = $this->prefixId.'[singleview_button_wf_appr]';
            $markerArray['fval_approveButton'] = tx_pttools_div::htmlOutput($wfsObj->get_labelApprove());
            $markerArray['fname_denyButton'] = $this->prefixId.'[singleview_button_wf_deny]';
            $markerArray['fval_denyButton'] = tx_pttools_div::htmlOutput($wfsObj->get_labelDeny());
            
        // for order archive mode (default): display re-order button
        } else {
            $markerArray['cond_workflowMode'] = false;
            $markerArray['fname_reorderButton'] = $this->prefixId.'[singleview_button_reorder]';
            $markerArray['ll_singleview_button_reorder'] = $this->pi_getLL('singleview_button_reorder');
            $markerArray['onClickAttribute_reorder'] = ($sessionWarning == 1 ? ' onclick="return confirm(\''.$this->pi_getLL('singleview_reorder_warning').'\')"' : '');
            $markerArray['ll_singleview_reorder_notice'] = $this->pi_getLL('singleview_reorder_notice');
        }
        
        // assign conditional message box
        if (!empty($msgBox)) {
            $markerArray['cond_displayMsgBox'] = true;
            $markerArray['msgBox'] = $msgBox;
        }
        
        
        // HOOK: allow multiple hooks to manipulate $markerArray
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi4_hooks']['displaySingleView_MarkerArrayHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi4_hooks']['displaySingleView_MarkerArrayHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $markerArray = $hookObj->displaySingleView_MarkerArrayHook($this, $markerArray); // $this is passed as a reference (since PHP5) and can be manipulated in the hook method
            }
        }
        
        // return prepared template to display
        $smarty = new tx_pttools_smartyAdapter($this);
        foreach ($markerArray as $markerKey=>$markerValue) {
            $smarty->assign($markerKey, $markerValue);
        }
        $filePath = $smarty->getTplResFromTsRes($this->conf['templateFileOrdersSingleView']);
        trace($filePath, 0, 'Smarty template resource $filePath');
        return $smarty->fetch($filePath);
        
    }
     
    /**
     * Generates and returns the HTML code of a notice
     * 
     * @param   string      text of  the notice message to display (should be taken from LocalLang!)
     * @return  string      HTML code of a notice
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-04-11
     */
    protected function displayNotice($notice) { 
        
        $markerArray = array();
        
        $markerArray['ll_notice_message'] = $notice;
        
        // HOOK: allow multiple hooks to manipulate $markerArray
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi4_hooks']['displayNotice_MarkerArrayHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi4_hooks']['displayNotice_MarkerArrayHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $markerArray = $hookObj->displayNotice_MarkerArrayHook($this, $markerArray); // $this is passed as a reference (since PHP5) and can be manipulated in the hook method
            }
        }
        
        // return prepared template to display
        $smarty = new tx_pttools_smartyAdapter($this);
        foreach ($markerArray as $markerKey=>$markerValue) {
            $smarty->assign($markerKey, $markerValue);
        }
        $filePath = $smarty->getTplResFromTsRes($this->conf['templateFilePi4Notice']);
        trace($filePath, 0, 'Smarty template resource $filePath');
        return $smarty->fetch($filePath);
        
    }
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/pi4/class.tx_ptgsashop_pi4.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/pi4/class.tx_ptgsashop_pi4.php']);
}

?>
