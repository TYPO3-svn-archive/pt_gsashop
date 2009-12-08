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
 * Order wrapper class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_orderWrapper.php,v 1.43 2008/11/24 09:52:58 ry44 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2006-03-08
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_lib.php';  // GSA Shop library with static methods
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_orderWrapperAccessor.php';  // GSA Shop database accessor class for order wrappers
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_order.php';  // GSA Shop order class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_logEntry.php';// GSA Shop log entry class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_sessionFeCustomer.php';  // GSA shop frontend customer class

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_assert.php'; // general assertion class
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_feCustomer.php';  // GSA frontend customer class


/**
 * Order wrapper class for archiving orders
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2006-03-08
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_orderWrapper {
    
    /***************************************************************************
     *   CLASS PROPERTIES
     **************************************************************************/
    
    /**
     * @var integer     database uid of order wrapper
     */
    protected $uid = 0;
    
    /**
     * @var integer    database uid of the TYPO3 page that has created the order wrapper
     */
    protected $pid = 0;
    
    /**
     * @var string      related document number ("Vorgangsnummer") of the saved order document in the ERP system (optional) 
     */
    protected $relatedDocNo = '';
    
    /**
     * @var integer     ID of the DB record of the archived order in tx_ptgsashop_orders (Fabrizio Branca 2007-04)
     */
    protected $orderObjId = 0;
    
    /**
     * @var integer     timestamp of the wrapped order
     */
    protected $orderTimestamp = 0;
    
    /**
     * @var integer     timestamp of the last update of the order wrapper
     */
    protected $updateTimestamp = 0;
    
    /**
     * @var double      total sum net of the wrapped archived order
     */
    protected $sumNet = 0;
    
    /**
     * @var double       total sum gross of the wrapped archived order
     */
    protected $sumGross = 0;
    
    /**
     * @var integer     workflow status code of the order wrapper
     */
    protected $statusCode = -1;
    
    /**
     * @var integer     ID of the FE user who initially placed the wrapped order (TYPO3: fe_users.uid)
     */
    protected $feCrUserId = 0;
    
    /**
     * @var integer     ID of the customer the wrapped order is related to (GSA: ADRESSE.NUMMER)
     */
    protected $customerId = 0;
    
    /**
     * @var integer     database UID of the last FE User who modified the order wrapper
     */
    protected $lastUserId = 0;
    
    /**
     * @var tx_ptgsashop_order    order object - will be loaded automatically if param $loadOrderObj is set to true in constructor
     */
    protected $orderObj = NULL;
    
    /**
     * @var tx_ptgsauserreg_feCustomer    FE customer who submitted the order
     */
    protected $feCustomerObj = NULL;
    
    /**
     * @var array        array with configuration values used by this class (this is set once in the class constructor) 
     */
    protected $classConfigArr = array();
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
     
    /**
     * Class constructor: prefills the object's properies depending on given params
     *
     * @param   integer     (optional) Database UID of the order wrapper record. Set to 0 if you want to use the 2nd or 3rd param alternatively.
     * @param   integer     (optional) Database UID of the related order record to restore its wrappers . Set to 0 if you want to use the 1st or 3rd param alternatively. This param has no effect if the 1st param is set to a positive integer.
     * @param   array       (optional) Array containing data to set as the object's properties; array keys have to be named exactly like this classes' properties. This param has no effect if one of the 1st or 2nd param is set to a positive integer.
     * @param   boolean     (optional) flag wether the complete order object should be loaded from it's ID (default=true)
     * @return  void     
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-08
     */
    public function __construct($wrapperId=0, $orderId=0, $wrapperArr=array(), $loadOrderObj=true) {
        
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        if (!is_numeric($wrapperId)) {
            throw new tx_pttools_exception('Parameter error', 3, 'First parameter for '.__CLASS__.' constructor is not numeric');
        }
        
        $this->classConfigArr = tx_ptgsashop_lib::getGsaShopConfig();
        
        // if a order wrapper record ID is given, retrieve wrapper data array from database accessor (and overwrite 2nd param)
        if ($wrapperId > 0) {
            $wrapperArr = tx_ptgsashop_orderWrapperAccessor::getInstance()->selectOrderWrapperById($wrapperId, 0);
            if ($wrapperArr === false) {
                throw new tx_pttools_exception('Record not found', 0, sprintf('Record not found for wrapperId "%s"', $wrapperId));
            }
        } elseif ($orderId > 0) {
            $wrapperArr = tx_ptgsashop_orderWrapperAccessor::getInstance()->selectOrderWrapperById(0, $orderId);
            if ($wrapperArr === false) {
                throw new tx_pttools_exception('Record not found', 0, sprintf('Record not found for orderId "%s"', $orderId));
            }
        }
        
        $this->setPropertiesFromGivenArray($wrapperArr, $loadOrderObj);
        
        trace($this, 0, 'New '.__CLASS__.' object created');
        
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
     
    /**
     * Sets the objects properties using data given by param array
     *
     * @param   array       Array containing data to set as the object's properties; used array keys have to be named exactly like this classes' appropriate properties
     * @param   boolean     (optional) flag wether the complete order object shhould be loaded from it's ID (default=true)
     * @return  void        
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-08
     */
    protected function setPropertiesFromGivenArray(array $wrapperArr, $loadOrderObj=true) {
        
        if (isset($wrapperArr['uid'])) $this->uid = (integer)$wrapperArr['uid'];
        if (isset($wrapperArr['pid'])) $this->pid = (integer)$wrapperArr['pid'];
        if (isset($wrapperArr['orderTimestamp'])) $this->orderTimestamp = (integer)$wrapperArr['orderTimestamp'];
        if (isset($wrapperArr['updateTimestamp'])) $this->updateTimestamp = (integer)$wrapperArr['updateTimestamp'];
        if (isset($wrapperArr['relatedDocNo'])) $this->relatedDocNo = (string)$wrapperArr['relatedDocNo'];
        if (isset($wrapperArr['sumNet'])) $this->sumNet = (float)$wrapperArr['sumNet'];
        if (isset($wrapperArr['sumGross'])) $this->sumGross = (float)$wrapperArr['sumGross'];
        if (isset($wrapperArr['statusCode'])) $this->statusCode = (integer)$wrapperArr['statusCode'];
        if (isset($wrapperArr['creatorId'])) $this->creatorId = (integer)$wrapperArr['creatorId'];
        if (isset($wrapperArr['customerId'])) $this->customerId = (integer)$wrapperArr['customerId'];
        if (isset($wrapperArr['lastUserId'])) $this->lastUserId = (integer)$wrapperArr['lastUserId'];
        if (isset($wrapperArr['orderObjId'])) $this->orderObjId = (integer)$wrapperArr['orderObjId'];
        if (isset($wrapperArr['orderObj']) && $wrapperArr['orderObj'] instanceof tx_ptgsashop_order) $this->orderObj = (object)$wrapperArr['orderObj'];
        if ($loadOrderObj == true && isset($wrapperArr['orderObjId'])) {
            $this->orderObj = new tx_ptgsashop_order($wrapperArr['orderObjId']);
        }
        
    }
    
    /**
     * Updates the status code of an order wrapper in the object itself and in the database record of the wrapped order.
     * Additonally, the wrapped order object including its archived data is updated in the object itself and in the database record - if not disabled by second param (the sums and data of the wrapped/archived order may have changed during workflow due to changed prices/dispatch cost/customer data). 
     * 
     * @param   integer     new status to set
     * @param   boolean     (optional) flag the wrapped order object including its archived data should be updated, too (default: true)
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-13 
     */
    public function updateOrderWrapper($statusCode, $updateWrappedOrder=true) {
        
        $currentCustomerObj = tx_ptgsashop_sessionFeCustomer::getInstance();
        
        // update the workflow status of the order wrapper in the object itself
        $this->statusCode = $statusCode;
        
        // if update of wrapped order is requested: update order object, sums, the order's archive data and the workflow status of the order wrapper
        if ($updateWrappedOrder == true) { 
            
            // update order in session
            $orderCreatorCustomerObj = new tx_ptgsauserreg_feCustomer($this->orderObj->get_feCrUserId());
            $this->orderObj->updateOrder($orderCreatorCustomerObj);
            $this->sumNet = $this->orderObj->getOrderSumTotal(1);
            $this->sumGross = $this->orderObj->getOrderSumTotal(0);
            
            // update the order's data in the order archive
            $this->orderObj->updateArchivedOrderData();
            
            // update workflow status and wrapper sums of the order wrapper database record
            tx_ptgsashop_orderWrapperAccessor::getInstance()->updateOrderWrapperStatus($this->uid, $this->statusCode, $currentCustomerObj->get_feUserId(), $this->orderObj);
            
        // if dynamic order workflow is disabled: update the workflow status of the order wrapper database record only
        } else {
            tx_ptgsashop_orderWrapperAccessor::getInstance()->updateOrderWrapperStatus($this->uid, $this->statusCode, $currentCustomerObj->get_feUserId());
        }
        
    }
     
    /**
     * Updates the related ERP document number of an order wrapper in the database record
     * 
     * @param   string      related document number ("Vorgangsnummer") of the saved order document in the GSA database respective in the ERP system
     * @return  boolean     TRUE on success or FALSE on error
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-04 
     */
    public function updateOrderWrapperDocNo($relatedDocNo) {
        
        $result = tx_ptgsashop_orderWrapperAccessor::getInstance()->updateOrderWrapperDocNo($this->uid, $relatedDocNo);
        return $result;
        
    }   
     
    /**  
     * Saves the order wrapper to the extension's order wrapper archive within the TYPO3 DB and creates an initial amendment log entry
     * TODO: make amendentlog configurable
     * 
     * @param   string      (optional) initial amendment log entry text
     * @return  integer     ID of the inserted order wrapper record
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-31
     */
    public function saveToDatabase($logentry='') {
        
        $this->uid = tx_ptgsashop_orderWrapperAccessor::getInstance()->insertOrderWrapper($this);
        
        // insert log entry to amendment log table (set statusPrev = -1 since it is a new Dekoanfrage)
        $logEntryObj = new tx_ptgsashop_logEntry(0, $this->pid, $this->creatorId, $this->uid, -1, $this->statusCode, $logentry);
        $logEntryObj->saveLogEntry();
        
        return $this->uid;
        
    }
     
    /**
     * Saves the contained order to the GSA database and updates the newly assigned related document number in the order wrapper record (within the TYPO3 database)
     *
     * @param   void       
     * @return  string      related document number ("Vorgangsnummer") of the saved order document in the GSA database respective in the ERP system   
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-11-10
     */
    public function saveToGsaDatabase() {
        
        tx_pttools_assert::isInstanceOf($this->feCustomerObj, 'tx_ptgsauserreg_feCustomer');
        // valid order assertion is done within $this->get_orderObj()
        
        $this->relatedDocNo = $this->get_orderObj()->saveToErpDatabase($this->feCustomerObj); // save order to the GSA/ERP database tables
        $this->updateOrderWrapperDocNo($this->relatedDocNo); // store related GSA/ERP doc number in order wrapper record
        
        return $this->relatedDocNo;
        
    }
    
    /**
     * Loads the last user (identified by internal property $lastUserId) to the FE customer property $feCustomerObj
     *
     * @param   tx_ptgsauserreg_feCustomer       FE customer object     
     * @return  void
     * @throws  tx_pttools_exception    if valid uid assertion fails for internal property $lastUserId
     * @author  Rainer Kuhn <kuhn@punkt.de>, Fabrizio Branca <branca@punkt.de>
     * @since   2008-11-11
     */
    public function loadLastUserToFeCustomer() {
        
        tx_pttools_assert::isValidUid($this->lastUserId);
        
        $this->feCustomerObj = new tx_ptgsauserreg_feCustomer($this->lastUserId);
        
    }
    
    /**
     * Sets the order property ($orderObj) including its related properties ($orderObjId, $orderTimestamp, $sumNet, $sumGross)
     *
     * @param   tx_ptgsashop_order       order object to set     
     * @return  void
     * @throws  tx_pttools_exception    if order property $orderObj is not empty when trying to set it with new object
     * @author  Rainer Kuhn <kuhn@punkt.de>, Fabrizio Branca <branca@punkt.de>
     * @since   2008-11-11
     */
    public function setNewOrderObj(tx_ptgsashop_order $orderObj) {
        
        tx_pttools_assert::isNull($this->orderObj);
        
        $this->orderObj = $orderObj;
        
        $this->orderObjId = $orderObj->get_orderArchiveId();        
        $this->orderTimestamp = $this->orderObj->get_timestamp();
        $this->sumNet = $this->orderObj->getOrderSumTotal(1);
        $this->sumGross = $this->orderObj->getOrderSumTotal(0);

    }
    
    /**
     * Sets the feCustomer property ($feCustomerObj) including its related properties ($customerId, $creatorId, $lastUserId)
     *
     * @param   tx_ptgsauserreg_feCustomer       FE customer object to set    
     * @return  void
     * @throws  tx_pttools_exception    if feCustomer property $feCustomerObj is not empty when trying to set it with new object
     * @author  Rainer Kuhn <kuhn@punkt.de>, Fabrizio Branca <branca@punkt.de>
     * @since   2008-11-11
     */
    public function setNewFeCustomerObj(tx_ptgsauserreg_feCustomer $feCustomerObj) {
        
        tx_pttools_assert::isNull($this->feCustomerObj);
        
        $this->feCustomerObj = $feCustomerObj;
        
        $this->customerId = $this->feCustomerObj->get_gsaMasterAddressId();
        $this->creatorId = $this->feCustomerObj->get_feUserId();
        $this->lastUserId = $this->creatorId;
        
    }
    
    
    
    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer       property value
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-10
     */
    public function get_uid() {
        
        return $this->uid;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer       property value
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-31
     */
    public function get_pid() {
        
        return $this->pid;
        
    }
    
    /**
     * Sets the property value
     *
     * @param   integer       property value      
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-11-10
     */
    public function set_pid($pid) {
        
        tx_pttools_assert::isValidUid($pid);
        
        $this->pid = (integer)$pid;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string       property value
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-04
     */
    public function get_relatedDocNo() {
        
        return $this->relatedDocNo;
        
    }
    
    /**
     * Sets the property value
     *
     * @param   string       property value      
     * @return  void
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-04
     */
    public function set_relatedDocNo($relatedDocNo) {
        
        $this->relatedDocNo = (string)$relatedDocNo;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer       property value
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-10
     */
    public function get_orderTimestamp() {
        
        return $this->orderTimestamp;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer       property value
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-17
     */
    public function get_updateTimestamp() {
        
        return $this->updateTimestamp;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  double      property value
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-10
     */
    public function get_sumNet() {
        
        return $this->sumNet;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  double      property value
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-10
     */
    public function get_sumGross() {
        
        return $this->sumGross;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer      property value
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-04-07
     */
    public function get_statusCode() {
        
        return $this->statusCode;
        
    }
    
    /**
     * Sets the property value
     *
     * @param   integer       property value      
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-11-10
     */
    public function set_statusCode($statusCode) {
        
        $this->statusCode = (integer)$statusCode;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer      property value
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-10
     */
    public function get_creatorId() {
        
        return $this->creatorId;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer      property value
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-05-10
     */
    public function get_customerId() {
        
        return $this->customerId;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void
     * @return  integer
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-03-23
     */
    public function get_lastUserId() {
        
        return $this->lastUserId;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer       property value
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-04-23
     */
    public function get_orderObjId() {
        
        return $this->orderObjId;
        
    }
    
    /**
     * Sets the property value
     *
     * @param   integer       property value      
     * @return  void
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-04-23
     */
    public function set_orderObjId($orderObjId) {
        
        $this->orderObjId = (integer)$orderObjId;
        
    }
        
    /**
     * Returns the property value
     *
     * @param   void
     * @return  tx_ptgsashop_order     property value, object of type tx_ptgsashop_order
     * @throws  tx_pttools_exception   if no valid order object is found in the current order wrapper
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-08
     */
    public function get_orderObj() {
        
        if (!($this->orderObj instanceof tx_ptgsashop_order)) {
            throw new tx_pttools_exception('No valid order found in order wrapper.', 3,
                                           'No valid order object is found for the given order wrapper ID '.$this->get_uid());
        }
        
        return $this->orderObj;
        
    }
    
    /**
     * Sets the object property value
     *
     * @param   tx_ptgsashop_order       order object     
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-11-10
     */
    public function set_orderObj(tx_ptgsashop_order $orderObj) {
        
        $this->orderObj = $orderObj;

    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  tx_ptgsauserreg_feCustomer       property value
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-11-10
     */
    public function get_feCustomerObj() {

        return $this->feCustomerObj;
        
    }
    
    /**
     * Sets the object property value
     *
     * @param   tx_ptgsauserreg_feCustomer       FE customer object     
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-11-10
     */
    public function set_feCustomerObj(tx_ptgsauserreg_feCustomer $feCustomerObj) {
        
        $this->feCustomerObj = $feCustomerObj;
        
    }
    
    
    
} // end class




/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_orderWrapper.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_orderWrapper.php']);
}

?>