<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2005 Rainer Kuhn (kuhn@punkt.de)
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
 * Workflow class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_workflow.php,v 1.24 2007/12/07 14:16:06 ry42 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2006-03-03
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_workflowAccessor.php';  // GSA Shop database accessor class for workflow
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_workflowStatusCollection.php';// GSA Shop workflow status collection class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_logEntry.php';// GSA Shop log entry class

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function



/**
 * Workflow class providing methods to handle workflow steps for shop orders in the TYPO3 frontend
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2006-03-03
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_workflow {
    
    /**
     * Properties
     */
    protected $orderObj = NULL;    // (object) order object to handle in workflow (this may be an abitrary, project specific order object)
     
    protected $wfsCollObj = NULL;    // (tx_ptgsashop_workflowStatusCollection object) workflow status collection object
    
    protected $finishStatusCode = 99;  // (integer) status code to set after an orders workflow has been finished - this status code should not exist nor have a real representation in the workflow status database table
    protected $defApprovalLabel = '';  // (string) default text label for approval button
    protected $defDenialLabel = '';    // (string) default text label for denial button
    protected $defChoiceLabel = '';    // (string) default text label for approval choice question
    
    protected $llArray = array();     // (array) multilingual language labels (locallang) for the workflow
    
    /**
     * Class Constants
     */
    const EXT_KEY     = 'pt_gsashop';                       // (string) the extension key
    const LL_FILEPATH = 'res/locallang_res_classes.xml';    // (string) path to the locallang file to use within this class
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
     
    /**
     * Class constructor: creates a new workflow instance and updates all contained workflow status objects
     *
     * @param   object      order object to handle in workflow (this may be an abitrary, project specific order object)
     * @param   integer     status code to set after an orders workflow has been finished
     * @param   string      TYPO3 extension key of the extension where to search for the workflow statuses' configuration classes
     * @global  object      $GLOBALS['TSFE']: tslib_fe Object
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-03
     */
    public function __construct($orderObj, $finishStatusCode, $configExtKey) {
        
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        // set workflow properties
        $this->llArray = $GLOBALS['TSFE']->readLLfile(t3lib_extMgm::extPath(self::EXT_KEY).self::LL_FILEPATH); // get locallang data
        
        $this->wfsCollObj = new tx_ptgsashop_workflowStatusCollection($configExtKey);
        $this->orderObj = $orderObj;
        $this->finishStatusCode = $finishStatusCode;
        
        $this->defApprovalLabel = $GLOBALS['TSFE']->getLLL(__CLASS__.'.defaultApprovalButton', $this->llArray);
        $this->defDenialLabel = $GLOBALS['TSFE']->getLLL(__CLASS__.'.defaultDenialButton', $this->llArray);
        $this->defChoiceLabel = $GLOBALS['TSFE']->getLLL(__CLASS__.'.defaultChoiceQuestion', $this->llArray);
        
        // update all workflow status objects depending on their individual configuration and the general defaults
        foreach ($this->wfsCollObj as $wfsObj) {
            $this->updateWfs($wfsObj);     // &$wfsObj is passed by reference
        }
        
        trace($this, 0, __CLASS__);
        
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**
     * Initialises the workflow
     *
     * @param   tx_ptgsashop_orderWrapper      object of type tx_ptgsashop_orderWrapper: wrapper of the order to process in workflow
     * @param   integer     (optional) ID of the TYPO3 page using calling this mehod
     * @return  boolean     see return of processAutomaticAdvance()
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-29
     */
    public function initialise(tx_ptgsashop_orderWrapper $orderWrapperObj, $pid=0) {
        
        $currentFeUserActionPossible = $this->processAutomaticAdvance($orderWrapperObj, $pid);
        return $currentFeUserActionPossible;
        
    }
    
    /**
     * Returns an iterator for all workflow status objects within this workflow
     *
     * @param   void
     * @return  ArrayIterator     object of type ArrayIterator: Iterator for workflow status objects within this workflow
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-06
     */
    public function getWfsIterator() {
        
        return $this->wfsCollObj->getIterator();
        
    }
    
    /**
     * Returns an workflow status object specified by the array key of the workflow status collection's items array (= the status code of the workflow status)
     *
     * @param   integer     status code of the workflow status
     * @return  mixed       object of type tx_ptgsashop_workflowStatus, or FALSE if key is not found
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-06
     */
    public function getWfs($key) { 
        
        if (!$this->getWfsIterator()->offsetExists($key)) {
            return false;
        }
        
        return $this->getWfsIterator()->offsetGet($key);
        
    }
    
    /**
     * Processes the automatic workflow advance starting at the current status of the given order wrapper
     *
     * @param   tx_ptgsashop_orderWrapper      object of type tx_ptgsashop_orderWrapper: wrapper of the order to process in workflow
     * @param   integer     (optional) ID of the TYPO3 page processing the automatic workflow advance
     * @return  boolean     flag wether an action of the currently logged-in user is possible (this may result e.g. in a workflow single view) or not (this may result e.g. in a workflow list view)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-29
     */
    public function processAutomaticAdvance(tx_ptgsashop_orderWrapper $orderWrapperObj, $pid=0) {
        
        if ($this->wfsCollObj->count() < 1) {
            throw new tx_pttools_exception('No workflow steps found', 2);
        }
        $currentFeUserActionPossible = false;
        
        foreach ($this->wfsCollObj as $wfsCode=>$wfsObj) {
            
            // skip status codes below the current workflow status of the order
            if ($wfsCode < $orderWrapperObj->get_statusCode()) {
                continue;
            }
            
            // break automatic workflow status advance loop if order status is equal to workflow end status
            if ($orderWrapperObj->get_statusCode() >= $this->finishStatusCode) {
                break;
            }
            
            // if workflow status condition is not fulfilled (=nothing to do for this status), advance workflow status automatically
            if ($wfsObj->returnConditionMethodCheckResult($this->orderObj) == false) {
                $this->execWfsAdvance($orderWrapperObj, $pid);
                
            // if workflow status condition is fulfilled (=something to do for this status), stop advancing and check the current user's usage authentication for the now current workflow status
            } else {
                // current user is allowed to use workflow status: set the method's return flag to true
                if ($wfsObj->getUseAuth($this->orderObj) == true) {
                    $currentFeUserActionPossible = true;
                // current user is not allowed to use workflow status: execute automatic halt action  (and leave the method's return flag on false)
                } else {
                    $this->execWfsHalt($orderWrapperObj);
                }
                // stop further advancing of workflow status
                break;
            }
        }
        
        return $currentFeUserActionPossible;
        
    }
    
    /** 
     * Executes the approval action for a workflow status (current status code retrieved from the given order wrapper object)
     *
     * @param   tx_ptgsashop_orderWrapper      object of type tx_ptgsashop_orderWrapper: wrapper of the order to process in workflow
     * @param   integer     ID of the TYPO3 page using the workflow approval
     * @param   integer     ID of the TYPO3 FE user using the workflow approval
     * @return  boolean     flag whether a FE user interaction is possible
     * @global  object      $GLOBALS['TSFE']: tslib_fe Object
     * @throws  tx_pttools_exception   if there are no workflow statuses in the workflow
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-13
     */
    public function execWfsApproval(tx_ptgsashop_orderWrapper $orderWrapperObj, $pid, $feUserId) {
        
        $currentWfsObj = $this->getWfs($orderWrapperObj->get_statusCode());
        
        // check authentication of the logged-in FE user to use the given workflow status
        if (! $currentWfsObj->getUseAuth($this->orderObj)) { 
            throw new tx_pttools_exception('Workflow access denied', 4);
        }
        
        $prevStatus = $orderWrapperObj->get_statusCode();
        $newStatus = $currentWfsObj->get_approveStatusCode();
        
        // update the order wrappers's status code (and the wrapped order itself if configured for the current workflow status)
        $orderWrapperObj->updateOrderWrapper($newStatus, $currentWfsObj->get_updateOrder());
        
        // insert log entry to amendment log table
        $logEntryObj = new tx_ptgsashop_logEntry(0, $pid, $feUserId, $orderWrapperObj->get_uid(), $prevStatus, $newStatus, 
                                                 $GLOBALS['TSFE']->getLLL(__CLASS__.'.logentryWorkflowApproval', $this->llArray));
        $logEntryObj->saveLogEntry();
        
        // execute individual approval actions
        $currentWfsObj->returnApprovalActionMethodResult($this->orderObj);
        
        // process automatic advance after approval
        $currentFeUserActionPossible = $this->processAutomaticAdvance($orderWrapperObj, $pid);
        return $currentFeUserActionPossible;
        
    }
    
    /** 
     * Executes the denial action for a workflow status (current status code retrieved from the given order wrapper object)
     *
     * @param   tx_ptgsashop_orderWrapper      object of type tx_ptgsashop_orderWrapper: wrapper of the order to process in workflow
     * @param   integer     ID of the TYPO3 page using the workflow denial
     * @param   integer     ID of the TYPO3 FE user using the workflow denial
     * @return  void
     * @global  object      $GLOBALS['TSFE']: tslib_fe Object
     * @throws  tx_pttools_exception   if there are no workflow statuses in the workflow
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-04-07
     */
    public function execWfsDenial(tx_ptgsashop_orderWrapper $orderWrapperObj, $pid, $feUserId) { 
        
        $currentWfsObj = $this->getWfs($orderWrapperObj->get_statusCode());
        
        // check authentication of the logged-in FE user to use the given workflow status
        if (! $currentWfsObj->getUseAuth($this->orderObj)) { 
            throw new tx_pttools_exception('Workflow access denied', 4);
        }
        
        $prevStatus = $orderWrapperObj->get_statusCode();
        $newStatus = $currentWfsObj->get_denyStatusCode();
        
        // update the order wrappers's status code (and the wrapped order itself if configured for the current workflow status)
        $orderWrapperObj->updateOrderWrapper($newStatus, $currentWfsObj->get_updateOrder());
        
        // insert log entry to amendment log table
        $logEntryObj = new tx_ptgsashop_logEntry(0, $pid, $feUserId, $orderWrapperObj->get_uid(), $prevStatus, $newStatus, 
                                                 $GLOBALS['TSFE']->getLLL(__CLASS__.'.logentryWorkflowDenial', $this->llArray));
        $logEntryObj->saveLogEntry();
        
        // execute individual denial actions
        $currentWfsObj->returnDenialActionMethodResult($this->orderObj);
        
        // do _not_ process automatic advance after denial!
        
    }
    
    /** 
     * Executes the automatic advance action for a workflow status (current status code retrieved from the given order wrapper object)
     *
     * @param   tx_ptgsashop_orderWrapper      object of type tx_ptgsashop_orderWrapper: wrapper of the order to advance in workflow
     * @param   integer     ID of the TYPO3 page initiating the workflow's automatic advance
     * @param   string      (optional) amendment log entry text
     * @return  void
     * @global  object      $GLOBALS['TSFE']: tslib_fe Object
     * @throws  tx_pttools_exception   if there are no workflow statuses in the workflow
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-29
     */
    protected function execWfsAdvance(tx_ptgsashop_orderWrapper $orderWrapperObj, $pid) { 
        
        $currentWfsObj = $this->getWfs($orderWrapperObj->get_statusCode());
        
        $prevStatus = $orderWrapperObj->get_statusCode();
        $newStatus = $currentWfsObj->get_advanceStatusCode();
        
        // update the order wrappers's status code (do _not_ update order itself here since it is not approved by a user again)
        $orderWrapperObj->updateOrderWrapper($newStatus, false);
        
        // insert log entry to amendment log table        // set the FE user ID to 0 since the advance is done automatically by the system
        $logEntryObj = new tx_ptgsashop_logEntry(0, $pid, 0, $orderWrapperObj->get_uid(), $prevStatus, $newStatus, 
                                                 $GLOBALS['TSFE']->getLLL(__CLASS__.'.logentryWorkflowAdvance', $this->llArray));
        $logEntryObj->saveLogEntry();
        
        // execute individual automatic advance actions
        $currentWfsObj->returnAdvanceActionMethodResult($this->orderObj);
        
    }
    
    /** 
     * Executes the automatic halt action for a workflow status (current status code retrieved from the given order wrapper object)
     * 
     * @param   tx_ptgsashop_orderWrapper      object of type tx_ptgsashop_orderWrapper: wrapper of the order to halt in workflow
     * @return  void
     * @throws  tx_pttools_exception   if there are no workflow statuses in the workflow
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-29
     */
    protected function execWfsHalt(tx_ptgsashop_orderWrapper $orderWrapperObj) { 
        
        $currentWfsObj = $this->getWfs($orderWrapperObj->get_statusCode());
        
        // NOTE: no default action on automatic halt, so there's no status code change and no log entry here...
        
        // execute individual automatic halt actions
        $currentWfsObj->returnHaltActionMethodResult($this->orderObj);
        
    }
    
    /**
     * Updates a workflow status (passed by reference) depending on its individual configuration and the general defaults
     *
     * @param   tx_ptgsashop_workflowStatus     workflow status to update, object of type tx_ptgsashop_workflowStatus required (passed by reference)
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-07
     */
    protected function updateWfs(tx_ptgsashop_workflowStatus &$wfsObj) { 
        
        // update approval/denial/advance status codes (if no individual configuration value is found, the next/previous status code is set by default!)
        $wfsObj->set_approveStatusCode($this->getWfsApprovalStatusCode($wfsObj->get_statusCode()));
        $wfsObj->set_denyStatusCode($this->getWfsDenialStatusCode($wfsObj->get_statusCode()));
        $wfsObj->set_advanceStatusCode($this->getWfsAdvanceStatusCode($wfsObj->get_statusCode()));
        
        // update text labels
        if (!strlen($wfsObj->get_labelApprove()) > 0) {
            $wfsObj->set_labelApprove($this->defApprovalLabel);
        }
        if (!strlen($wfsObj->get_labelDeny()) > 0) {
            $wfsObj->set_labelDeny($this->defDenialLabel);
        }
        if (!strlen($wfsObj->get_labelChoice()) > 0) {
            $wfsObj->set_labelChoice($this->defChoiceLabel);
        }
        
    }
    
    /**
     * Returns the status code following an approval of a workflow status (if no individual configuration value is found, the "next" status code is returned as default!)
     *
     * @param   integer     status code of the workflow status to retrieve its approval status code
     * @return  mixed       (integer) status code to use after the workflow status' approval (see function comment)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-06
     */
    protected function getWfsApprovalStatusCode($statusCode) { 
        
        $configApprStatus = $this->getWfs($statusCode)->get_approveStatusCode();
        $nextStatus = $this->getWfs($statusCode)->get_nextStatusCode();
        
        // if no individual approval status code has been configured...
        if (empty($configApprStatus)) {
            // ...and the next status code is set: use next as approval status code
            if (!empty($nextStatus) && $this->getWfs($nextStatus) != false) {
                $approvalStatus = $nextStatus;
            // ...and the next status code is *not* set: set finishStatusCode as approval status code (=end of workflow on approval)
            } else {
                $approvalStatus = $this->finishStatusCode;
            }
        // if an individual approval status code has been configured...
        } else {
            // ...and the configured value is valid: use configured as approval status code
            if ($this->getWfs($configApprStatus) != false) {
                $approvalStatus = $configApprStatus;
            // ...and the configured value is *not* valid: set finishStatusCode as approval status code (=end of workflow on approval)
            } else {
                $approvalStatus = $this->finishStatusCode;
            }
        }
        
        return $approvalStatus;
        
    }
    
    /**
     * Returns the status code following an denial of a workflow status (if no individual configuration value is found, the previous status code is returned as default!)
     *
     * @param   integer     status code of the workflow status to retrieve its denial status code
     * @return  mixed       (integer) status code to use after the workflow status' denial (see function comment)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-06
     */
    protected function getWfsDenialStatusCode($statusCode) { 
        
        $configDenialStatus = $this->getWfs($statusCode)->get_denyStatusCode();
        $prevStatus = $this->getWfs($statusCode)->get_prevStatusCode();
        
        // if no individual denial status code has been configured...
        if (empty($configDenialStatus)) {
            // ...and the previous status code is set: use previous as denial status code
            if (!empty($prevStatus) && $this->getWfs($prevStatus) != false) {
                $denialStatus = $prevStatus;
            // ...and the previous status code is *not* set: set current status code as denial status code
            } else {
                $denialStatus = $statusCode;
            }
        // if an individual denial status code has been configured... 
        } else {
            // ...and the configured value is valid: use configured as denial status code
            if ($this->getWfs($configDenialStatus) != false) {
                $denialStatus = $configDenialStatus;
            // ...and the configured value is *not* valid: set current status code as denial status code
            } else {
                $denialStatus = $statusCode;
            }
        }
        
        return $denialStatus;
        
    }
    
    /**
     * Returns the status code following an automatic advance of a workflow status (if no individual configuration value is found, the "next" status code is returned as default!)
     *
     * @param   integer     status code of the workflow status to retrieve its advance status code
     * @return  mixed       (integer) status code to use after the workflow status' automatic advance (see function comment)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-29
     */
    protected function getWfsAdvanceStatusCode($statusCode) { 
        
        $configAdvanceStatus = $this->getWfs($statusCode)->get_advanceStatusCode();
        $nextStatus = $this->getWfs($statusCode)->get_nextStatusCode();
        
        // if no individual advance status code has been configured...
        if (empty($configAdvanceStatus)) {
            // ...and the next status code is set: use next as advance status code
            if (!empty($nextStatus) && $this->getWfs($nextStatus) != false) {
                $advanceStatus = $nextStatus;
            // ...and the next status code is *not* set: set finishStatusCode as advance status code (=end of workflow on automatic advance)
            } else {
                $advanceStatus = $this->finishStatusCode;
            }
        // if an individual advance status code has been configured...
        } else {
            // ...and the configured value is valid: use configured as advance status code
            if ($this->getWfs($configAdvanceStatus) != false) {
                $advanceStatus = $configAdvanceStatus;
            // ...and the configured value is *not* valid: set finishStatusCode as advance status code (=end of workflow on automatic advance)
            } else {
                $advanceStatus = $this->finishStatusCode;
            }
        }
        
        return $advanceStatus;
        
    }
    
    
    
    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  tx_ptgsashop_workflowStatusCollection      property value, object of type tx_ptgsashop_workflowStatusCollection
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-03
     */
    public function get_wfsCollObj() {
        
        return $this->wfsCollObj;
        
    }
    
    
    
} // end class




/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_workflow.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_workflow.php']);
}

?>