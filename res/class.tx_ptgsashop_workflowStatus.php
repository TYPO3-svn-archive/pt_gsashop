<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2006 Rainer Kuhn (kuhn@punkt.de)
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
 * Workflow status class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_workflowStatus.php,v 1.19 2007/10/15 13:03:25 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2006-03-03
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 * 
 */


/**
 * Inclusion of TYPO3 libraries
 *
 * @see t3lib_div
 */
require_once(PATH_t3lib.'class.t3lib_div.php');

/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_workflowAccessor.php';  // GSA Shop database accessor class for workflow

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class




/**
 * Workflow status class
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2006-03-03
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_workflowStatus {

    /**
     * Properties
     */
    protected $statusCode; // (integer) numeric code for this workflow status, max. 2 digits (0 to 99)
    protected $prevStatusCode; // (integer) next available status code in workflow, max. 2 digits (0 to 99)   ########### braucht man das irgendwo? *allgemein* untauglich wg. condition method! ####
    protected $nextStatusCode; // (integer) previous available status code in workflow, max. 2 digits (0 to 99) ########### braucht man das irgendwo? *allgemein* untauglich wg. condition method! ####
    
    protected $configClassName = '';    // (string) the name of workflow status' configuration class (in external extension)
    
    protected $name; // (string) name of the workflow status
    protected $description; // (string) description of the workflow status
    protected $authViewGroups; // (string) comma seperated list of all FE usergroups who are allowed to view this workflow status
    protected $authUseGroups; // (string) comma seperated list of all FE usergroups who are allowed to use (approve/deny) this workflow status
    protected $updateOrder; // (boolean) flag whether the underlying oddr should be updated after approval or denial of this workflow status (dynamic order workflow)
    protected $conditionMethod; // (string) flag whether there's a static method in the worklow status' config file to use for the condition to activate this workflow status (if applicable)
    protected $permissionMethod; // (string) flag whether there's a static method in the worklow status' config file to use for the permission to use this workflow status (if applicable)
    protected $approveActionMethod; // (boolean) flag whether there's a static method in the worklow status' config file to use for the actions to perform after approval of this this workflow status (if different from default approval method)
    protected $denyActionMethod; // (boolean) flag whether there's a static method in the worklow status' config file to use for the actions to perform after denial of this this workflow status (if different from default denial method)
    protected $advanceActionMethod; // (boolean) flag whether there's a static method in the worklow status' config file to use for the actions to perform after automatic advance of this this workflow status (if different from default advance method)
    protected $haltActionMethod; // (boolean) flag whether there's a static method in the worklow status' config file to use for the actions to perform after halt of automatic advance at this workflow status (if different from default halt method)
    protected $approveStatusCode; // (integer) status code to set for order after approval of this workflow status (if different from default approval status code), max. 2 digits (0 to 99)
    protected $denyStatusCode; // (integer) status code to set for order after denial of this workflow status (if different from default denial status code), max. 2 digits (0 to 99)
    protected $advanceStatusCode; // (integer) status code to set for order after automatic advance of this workflow status (if different from default advance status code), max. 2 digits (0 to 99)
    protected $labelChoice; // (string) text/question label to place above the approval/denial buttons (if different from default label)
    protected $labelApprove; // (string) text label for the approval button (if different from default label)
    protected $labelDeny; // (string) text label for the denial button (if different from default label)
    protected $labelApprovalConfirmation; // (string) text label for the approval confirmation (if different from default label)
    protected $labelDenialConfirmation; // (string) text label for the denial confirmation (if different from default label)
    
    /** 
     * Class Constants
     */
    const EXT_CONFIG_CLASS_DIR = 'res/pt_gsashop_workflow/'; // (string) path to the directory where to find the workflow status config classes within an external extension
    const METHOD_NAME_CONDITION_CHECK = 'returnConditionCheck'; // (string) name of the condition check method to use if the appropriate flag is set (as defined in tx_ptgsashop_iWfsConfigurator)
    const METHOD_NAME_PERMISSION_CHECK = 'returnPermissionCheck'; // (string) name of the permission check method to use if the appropriate flag is set (as defined in tx_ptgsashop_iWfsConfigurator)
    const METHOD_NAME_APPROVE_ACTION = 'execApprovalAction'; // (string) name of the approval action method to use if the appropriate flag is set (as defined in tx_ptgsashop_iWfsConfigurator)
    const METHOD_NAME_DENY_ACTION = 'execDenialAction'; // (string) name of the denial action method to use if the appropriate flag is set (as defined in tx_ptgsashop_iWfsConfigurator)
    const METHOD_NAME_ADVANCE_ACTION = 'execAdvanceAction'; // (string) name of the advance action method to use if the appropriate flag is set (as defined in tx_ptgsashop_iWfsConfigurator)
    const METHOD_NAME_HALT_ACTION = 'execHaltAction'; // (string) name of the halt action method to use if the appropriate flag is set (as defined in tx_ptgsashop_iWfsConfigurator)
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR
     **************************************************************************/
     
    /**
     * Class constructor: creates new object with prefilled properties
     *
     * @param   integer     status code of the workflow status
     * @param   string      TYPO3 extension key of the extension where to search for the workflow statuses' configuration classes
     * @param   integer     (optional) status code of the previous workflow status
     * @param   integer     (optional) status code of the following workflow status
     * @return  void
     * @global  
     * @throws  tx_pttools_exception    if workflow configuration class could not be included
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-03
     */
    public function __construct($statusCode, $configExtKey, $prevStatusCode=NULL, $nextStatusCode=NULL) {
    
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        $this->statusCode = (integer)$statusCode;
        $this->prevStatusCode = (integer)$prevStatusCode;
        $this->nextStatusCode = (integer)$nextStatusCode;
        
        $this->setWorkflowStatusConfigData();
        
        // use external config class file if one of the external config method flags is set
        if ($this->conditionMethod == 1 || $this->permissionMethod == 1 || $this->approveActionMethod == 1 
            || $this->denyActionMethod == 1  || $this->advanceActionMethod == 1  || $this->haltActionMethod == 1 ) { 
            
            $this->configClassName = 'tx_'.str_replace ('_', '', $configExtKey).'_wfsConfig_'.$statusCode;
            $configClassFilePath = t3lib_extMgm::extPath($configExtKey).self::EXT_CONFIG_CLASS_DIR.'class.'.$this->configClassName.'.php'; // path to the directory where to search for the workflow statuses' configuration classes
        
            // include config class file
            if (@file_exists($configClassFilePath) && @is_readable($configClassFilePath)) {
                require_once $configClassFilePath; // if/else construct used to prevent full path display on error
            } else {
                throw new tx_pttools_exception('Workflow configuration class could not be read.', 2,
                                                $configClassFilePath.' could not be found or read.');
            }
            
        }
        
        trace($this, 0, __CLASS__);
        
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**
     * Sets the configured properties of an workflow status using data retrieved from a database query
     *
     * @param   void
     * @return  void
     * @global  integer     $GLOBALS['TSFE']->sys_language_content
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-03
     */
    protected function setWorkflowStatusConfigData() { 
        
        $wfsDataArr = tx_ptgsashop_workflowAccessor::getInstance()->selectWorkflowStatus($this->statusCode, $GLOBALS['TSFE']->sys_language_content);
        
        $this->name = (string)$wfsDataArr['name'];
        $this->description = (string)$wfsDataArr['description'];
        $this->authViewGroups = (string)$wfsDataArr['auth_groups_view'];
        $this->authUseGroups = (string)$wfsDataArr['auth_groups_use'];
        $this->updateOrder = (boolean)$wfsDataArr['update_order'];
        $this->conditionMethod = (boolean)$wfsDataArr['condition_method'];
        $this->permissionMethod = (boolean)$wfsDataArr['permission_method'];
        $this->approveActionMethod = (boolean)$wfsDataArr['approve_action_method'];
        $this->denyActionMethod = (boolean)$wfsDataArr['deny_action_method'];
        $this->advanceActionMethod = (boolean)$wfsDataArr['advance_action_method'];
        $this->haltActionMethod = (boolean)$wfsDataArr['halt_action_method'];
        $this->approveStatusCode = (integer)$wfsDataArr['approve_status_code'];
        $this->denyStatusCode = (integer)$wfsDataArr['deny_status_code'];
        $this->advanceStatusCode = (integer)$wfsDataArr['advance_status_code'];
        $this->labelChoice = (string)$wfsDataArr['label_choice'];
        $this->labelApprove = (string)$wfsDataArr['label_approve'];
        $this->labelDeny = (string)$wfsDataArr['label_deny'];
        $this->labelApprovalConfirmation = (string)$wfsDataArr['label_confirm_approve'];
        $this->labelDenialConfirmation = (string)$wfsDataArr['label_confirm_deny'];
        
    }
    
    /**
     * Returns the result of the "view workflow" authorisation for the currently logged-in FE user
     *
     * @param   void
     * @return  boolean     TRUE if the user is allowed to view the workflow status, FALSE otherwise
     * @global  object      $GLOBALS['TSFE']->fe_user: tslib_feuserauth Object
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-13
     */
    public function getViewAuth() { 
        
        $authResult = false;
        $usersGroupArr = t3lib_div::intExplode(',', $GLOBALS['TSFE']->fe_user->user['usergroup']);
        $authViewGroupArr = t3lib_div::intExplode(',', $this->authViewGroups);
        
        // check view authorisation by membership of allowed usergroup
        foreach ($usersGroupArr as $userGroup) {
            if (in_array($userGroup, $authViewGroupArr)) {
                $authResult = true;
                break;
            }
        }
        
        return $authResult;
        
    }
    
    /**
     * Returns the result of the "use workflow" authorisation for the currently logged-in FE user
     *
     * @param   object      order object to handle in workflow (this may be an abitrary, project specific order object)
     * @return  boolean     TRUE if the user is allowed to use the workflow status, FALSE otherwise
     * @global  object      $GLOBALS['TSFE']->fe_user: tslib_feuserauth Object
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-13
     */
    public function getUseAuth($orderObj) { 
        
        $authResult = false;
        $usersGroupArr = t3lib_div::intExplode(',', $GLOBALS['TSFE']->fe_user->user['usergroup']);
        $authUseGroupArr = t3lib_div::intExplode(',', $this->authUseGroups);
        
        // check usage authorisation by membership of allowed usergroup
        foreach ($usersGroupArr as $userGroup) {
            if (in_array($userGroup, $authUseGroupArr)) {
                $authResult = true;
                break;
            }
        }
        
        // if authorised by usergroup, check usage authorisation additionaly by special permissions (if applicable method exists)
        if ($authResult == true) {
            $authResult = $this->returnPermissionMethodCheckResult($orderObj); // returns TRUE is returned if permission method flag is set and the permission method is fulfilled OR if no special wfs permission is set
        }
        
        return $authResult;
        
    }
    
    /** 
     * Returns the result of the execution of an external configuration method in an external class
     *
     * @param   string      class name of the external class where to find the configuration method
     * @param   string      method name of the configuration method to execute
     * @param   object      order object to handle in workflow (this may be an abitrary, project specific order object)
     * @return  boolean     TRUE if external method has been executed successfully, FALSE otherwise
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-04-10
     */
    protected function returnExternalMethodCallResult($className, $methodName, $orderObj) { 
        
        $callResult = true; // if no special configuration set, everything's o.k. and TRUE is returned
        
        // do check only if external configuration class and method are configured
        if (!empty($className) && !empty($methodName)) {
            $callClassMethodArr = array($className, $methodName);
            if (is_callable($callClassMethodArr)) {
                $callResult = call_user_func($callClassMethodArr, $orderObj);
            } else {
                throw new tx_pttools_exception('Workflow configuration method fault.', 2,
                                                $callClassMethodArr[0].'::'.$callClassMethodArr[1].'() could not be called.');
            }
        }
        
        return $callResult;
        
    }
    
    /** 
     * Returns the result of the check of the workflow status' condition method
     *
     * @param   object      order object to handle in workflow (this may be an abitrary, project specific order object)
     * @return  boolean     TRUE if condition check has been passed successfully, FALSE otherwise
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-04-04
     */
    public function returnConditionMethodCheckResult($orderObj) { 
        
        $return = true; 
        
        if ($this->conditionMethod == 1) {
           $return = $this->returnExternalMethodCallResult($this->configClassName, self::METHOD_NAME_CONDITION_CHECK, $orderObj);
        }
        
        // TRUE is returned if condition method flag is set and condition method is fulfilled OR if no special wfs condition is set, so condition is fulfilled
        return $return;
        
    }
    
    /** 
     * Returns the result of the check of the workflow status' permission method
     *
     * @param   object      order object to handle in workflow (this may be an abitrary, project specific order object)
     * @return  boolean     TRUE if permission check has been passed successfully, FALSE otherwise
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-08
     */
    public function returnPermissionMethodCheckResult($orderObj) { 
        
        $return = true; 
        
        if ($this->permissionMethod == 1) {
           $return = $this->returnExternalMethodCallResult($this->configClassName, self::METHOD_NAME_PERMISSION_CHECK, $orderObj);
        }
        
        // TRUE is returned if permission method flag is set and the permission method is fulfilled OR if no special wfs permission is set, so permission is granted
        return $return;
        
    }
    
    /** 
     * Returns the result of the execution of the workflow status' approval action method
     *
     * @param   object      order object to handle in workflow (this may be an abitrary, project specific order object)
     * @return  boolean     TRUE if approval action has been executed successfully, FALSE otherwise
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-04-07
     */
    public function returnApprovalActionMethodResult($orderObj) { 
        
        $return = true; 
        
        if ($this->approveActionMethod == 1) {
           $return = $this->returnExternalMethodCallResult($this->configClassName, self::METHOD_NAME_APPROVE_ACTION, $orderObj);
        }
        
        // TRUE is returned if a special approval action method flag is set and action method is properly executed OR if no special wfs approval action is set, so everything is o.k.
        return $return;
        
    }
    
    /** 
     * Returns the result of the execution of the workflow status' denial action method
     *
     * @param   object      order object to handle in workflow (this may be an abitrary, project specific order object)
     * @return  boolean     TRUE if denial action has been executed successfully, FALSE otherwise
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-04-07
     */
    public function returnDenialActionMethodResult($orderObj) { 
        
        $return = true;   
        
        if ($this->denyActionMethod == 1) {
           $return = $this->returnExternalMethodCallResult($this->configClassName, self::METHOD_NAME_DENY_ACTION, $orderObj);
        }
        
        // TRUE is returned if a special denial action method flag is set and action method is properly executed OR if no special wfs denial action is set, so everything is o.k. 
        return $return;
        
    }
    
    /** 
     * Returns the result of the execution of the workflow status' advance action method
     *
     * @param   object      order object to handle in workflow (this may be an abitrary, project specific order object)
     * @return  boolean     TRUE if advance action has been executed successfully, FALSE otherwise
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-28
     */
    public function returnAdvanceActionMethodResult($orderObj) { 
        
        $return = true;   
        
        if ($this->advanceActionMethod == 1) {
           $return = $this->returnExternalMethodCallResult($this->configClassName, self::METHOD_NAME_ADVANCE_ACTION, $orderObj);
        }
        
        // TRUE is returned if a special advance action method flag is set and action method is properly executed OR if no special wfs advance action is set, so everything is o.k. 
        return $return;
        
    }
    
    /** 
     * Returns the result of the execution of the workflow status' halt action method
     *
     * @param   object      order object to handle in workflow (this may be an abitrary, project specific order object)
     * @return  boolean     TRUE if halt action has been executed successfully, FALSE otherwise
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-28
     */
    public function returnHaltActionMethodResult($orderObj) { 
        
        $return = true;   
        
        if ($this->haltActionMethod == 1) {
           $return = $this->returnExternalMethodCallResult($this->configClassName, self::METHOD_NAME_HALT_ACTION, $orderObj);
        }
        
        // TRUE is returned if a special halt action method flag is set and action method is properly executed OR if no special wfs halt action is set, so everything is o.k. 
        return $return;
        
    }
    
    
    
    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer      property value
     * @since   2006-03-03
     */
    public function get_statusCode() {
        
        return $this->statusCode;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer      property value
     * @since   2006-03-07
     */
    public function get_nextStatusCode() {
        
        return $this->nextStatusCode;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer      property value
     * @since   2006-03-07
     */
    public function get_prevStatusCode() {
        
        return $this->prevStatusCode;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-03-03
     */
    public function get_name() {
        
        return $this->name;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-03-03
     */
    public function get_description() {
        
        return $this->description;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-03-03
     */
    public function get_authViewGroups() {
        
        return $this->authViewGroups;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-03-03
     */
    public function get_authUseGroups() {
        
        return $this->authUseGroups;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  boolean      property value
     * @since   2007-06-05
     */
    public function get_updateOrder() {
        
        return $this->updateOrder;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  boolean      property value
     * @since   2006-03-03
     */
    public function get_conditionMethod() {
        
        return $this->conditionMethod;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  boolean      property value
     * @since   2006-06-08
     */
    public function get_permissionMethod() {
        
        return $this->permissionMethod;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  boolean      property value
     * @since   2006-03-03
     */
    public function get_approveActionMethod() {
        
        return $this->approveActionMethod;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  boolean      property value
     * @since   2006-03-03
     */
    public function get_denyActionMethod() {
        
        return $this->denyActionMethod;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  boolean      property value
     * @since   2007-06-28
     */
    public function get_advanceActionMethod() {
        
        return $this->advanceActionMethod;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  boolean      property value
     * @since   2007-06-28
     */
    public function get_haltActionMethod() {
        
        return $this->haltActionMethod;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer      property value
     * @since   2006-03-03
     */
    public function get_approveStatusCode() {
        
        return $this->approveStatusCode;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer      property value
     * @since   2006-03-03
     */
    public function get_denyStatusCode() {
        
        return $this->denyStatusCode;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer      property value
     * @since   2007-06-28
     */
    public function get_advanceStatusCode() {
        
        return $this->advanceStatusCode;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-03-03
     */
    public function get_labelChoice() {
        
        return $this->labelChoice;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-03-03
     */
    public function get_labelApprove() {
        
        return $this->labelApprove;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-03-03
     */
    public function get_labelDeny() {
        
        return $this->labelDeny;
        
    }
    
    /**
     * Sets the property value
     *     
     * @param   integer     property value
     * @return  void   
     * @since   2006-03-06
     */
    protected function set_statusCode($statusCode) {
        
        $this->statusCode = (integer)$statusCode;
        
    }
    
    /**
     * Sets the property value
     *      
     * @param   string      property value
     * @return  void   
     * @since   2006-03-06
     */
    protected function set_name($name) {
        
        $this->name = (string)$name;
        
    }
    
    /**
     * Sets the property value
     *     
     * @param   string      property value
     * @return  void   
     * @since   2006-03-06
     */
    protected function set_description($description) {
        
        $this->description = (string)$description;
        
    }
    
    /**
     * Sets the property value
     *     
     * @param   string      property value
     * @return  void   
     * @since   2006-03-06
     */
    protected function set_authViewGroups($authViewGroups) {
        
        $this->authViewGroups = (string)$authViewGroups;
        
    }
    
    /**
     * Sets the property value
     *       
     * @param   string      property value
     * @return  void   
     * @since   2006-03-06
     */
    protected function set_authUseGroups($authUseGroups) {
        
        $this->authUseGroups = (string)$authUseGroups;
        
    }
    
    /**
     * Sets the property value
     *   
     * @param   boolean      property value
     * @return  void   
     * @since   2007-06-05
     */
    protected function set_updateOrder($updateOrder) {
        
        $this->updateOrder = (boolean)$updateOrder;
        
    }
    
    /**
     * Sets the property value
     *   
     * @param   boolean      property value
     * @return  void   
     * @since   2006-03-06
     */
    protected function set_conditionMethod($conditionMethod) {
        
        $this->conditionMethod = (boolean)$conditionMethod;
        
    }
    
    /**
     * Sets the property value
     *   
     * @param   boolean      property value
     * @return  void   
     * @since   2006-06-08
     */
    protected function set_permissionMethod($permissionMethod) {
        
        $this->permissionMethod = (boolean)$permissionMethod;
        
    }
    
    /**
     * Sets the property value
     *     
     * @param   boolean      property value
     * @return  void   
     * @since   2006-03-06
     */
    protected function set_approveActionMethod($approveActionMethod) {
        
        $this->approveActionMethod = (boolean)$approveActionMethod;
        
    }
    
    /**
     * Sets the property value
     *   
     * @param   boolean      property value
     * @return  void   
     * @since   2006-03-06
     */
    protected function set_denyActionMethod($denyActionMethod) {
        
        $this->denyActionMethod = (boolean)$denyActionMethod;
        
    }
    
    /**
     * Sets the property value
     *   
     * @param   boolean      property value
     * @return  void   
     * @since   2007-06-28
     */
    protected function set_advanceActionMethod($advanceActionMethod) {
        
        $this->advanceActionMethod = (boolean)$advanceActionMethod;
        
    }
    
    /**
     * Sets the property value
     *   
     * @param   boolean      property value
     * @return  void   
     * @since   2007-06-28
     */
    protected function set_haltActionMethod($haltActionMethod) {
        
        $this->haltActionMethod = (boolean)$haltActionMethod;
        
    }
    
    /**
     * Sets the property value
     *    
     * @param   integer     property value
     * @return  void   
     * @since   2006-03-06
     */
    public function set_approveStatusCode($approveStatusCode) {
        
        $this->approveStatusCode = (integer)$approveStatusCode;
        
    }
    
    /**
     * Sets the property value
     *   
     * @param   integer     property value
     * @return  void   
     * @since   2006-03-06
     */
    public function set_denyStatusCode($denyStatusCode) {
        
        $this->denyStatusCode = (integer)$denyStatusCode;
        
    }
    
    /**
     * Sets the property value
     *   
     * @param   integer     property value
     * @return  void   
     * @since   2007-06-28
     */
    public function set_advanceStatusCode($advanceStatusCode) {
        
        $this->advanceStatusCode = (integer)$advanceStatusCode;
        
    }
    
    /**
     * Sets the property value
     *      
     * @param   string      property value
     * @return  void   
     * @since   2006-03-06
     */
    public function set_labelChoice($labelChoice) {
        
        $this->labelChoice = (string)$labelChoice;
        
    }
    
    /**
     * Sets the property value
     *      
     * @param   string      property value
     * @return  void   
     * @since   2006-03-06
     */
    public function set_labelApprove($labelApprove) {
        
        $this->labelApprove = (string)$labelApprove;
        
    }
    
    /**
     * Sets the property value
     *     
     * @param   string      property value
     * @return  void   
     * @since   2006-03-06
     */
    public function set_labelDeny($labelDeny) {
        
        $this->labelDeny = (string)$labelDeny;
        
    }
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_workflowStatus.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_workflowStatus.php']);
}

?>