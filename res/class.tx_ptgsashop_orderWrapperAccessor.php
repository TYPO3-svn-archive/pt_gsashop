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
 * Database accessor class for order wrappers of the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_orderWrapperAccessor.php,v 1.33 2008/03/19 16:19:31 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2006-03-08 (methods since 2005-12-22)
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */


/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_order.php';  // GSA Shop order class

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general helper library class
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_iSingleton.php'; // interface for Singleton design pattern



/**
 *  Database accessor class for order wrappers (temporary archived serialized orders)
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2006-03-08 (methods since 2005-12-22)
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_orderWrapperAccessor implements tx_pttools_iSingleton {
    
    /**
     * Properties
     */
    private static $uniqueInstance = NULL; // (tx_ptgsashop_orderWrapperAccessor object) Singleton unique instance
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
    
    /**
     * Private class constructor: must not be called directly in order to use getInstance() to get the unique instance of the object.
     *
     * @param   void
     * @return  void
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-10
     */
    private function __construct() {
    
        trace('***** Creating new '.__CLASS__.' object. *****');
        
    }
    
    /**
     * Returns a unique instance (Singleton) of the object. Use this method instead of the private/protected class constructor.
     *
     * @param   void
     * @return  tx_ptgsashop_orderWrapperAccessor      unique instance of the object (Singleton) 
     * @global     
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-08
     */
    public static function getInstance() {
        
        if (self::$uniqueInstance === NULL) {
            $className = __CLASS__;
            self::$uniqueInstance = new $className;
        }
        return self::$uniqueInstance;
        
    }
    
    /**
     * Final method to prevent object cloning (using 'clone'), in order to use only the unique instance of the Singleton object.
     * @param   void
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-15
     */
    public final function __clone() {
        
        trigger_error('Clone is not allowed for '.get_class($this).' (Singleton)', E_USER_ERROR);
        
    }
    
    
    
    /***************************************************************************
     *   TYPO3 DB RELATED METHODS
     **************************************************************************/
     
    /**
     * Returns data of an order wrapper record (specified by UID) from the TYPO3 database
     *
     * @param   integer     (optional) UID of the order wrapper record in the TYPO3 database. At least one of the two params is required.
     * @param   integer     (optional) Database UID of the wrapped order to get its wrapper data from the  TYPO3 database. At least one of the two params is required.
     * @global  object      $GLOBALS['TYPO3_DB']: t3lib_db Object (TYPO3 DB API)
     * @return  mixed       array of the specified order wrapper record on success, FALSE otherwise
     * @throws  tx_pttools_exception   if none of the two params is set to a positive integer
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-12-22
     */
    public function selectOrderWrapperById($wrapperId=0, $orderId=0) {
        
        if ($wrapperId < 1 && $orderId < 1) {
            throw new tx_pttools_exception('Wrong query params', 3, 'None of the two params set to a positive integer in '.__METHOD__);
        }
        
        // query preparation
        $select  = 'uid, '.
                   'pid, '.
                   'tstamp AS updateTimestamp, '.
                   'fe_cruser_id AS creatorId, '.
                   'customer_id AS customerId, '.
                   'related_doc_no AS relatedDocNo, '.
                   'orders_id AS orderObjId, '.
                   'order_timestamp AS orderTimestamp, '.
                   'sum_net AS sumNet, '.
                   'sum_gross AS sumGross, '.
                   'wf_status_code AS statusCode, '.
                   'wf_lastuser_id AS lastUserId, '.
                   'wf_lock_userid AS lockUserid, '.
                   'wf_lock_timestamp AS lockTimestamp';
        $from    = 'tx_ptgsashop_order_wrappers';
        $where   = ($wrapperId > 0 ? 'uid = '.intval($wrapperId).' ' : '').
                   ($orderId > 0 ? 'orders_id = '.intval($orderId).' ' : '').
                   tx_pttools_div::enableFields($from); 
        $groupBy = '';
        $orderBy = '';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        trace(tx_pttools_div::returnLastBuiltSelectQuery($GLOBALS['TYPO3_DB'], $select, $from, $where, $groupBy, $orderBy, $limit));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        
        $a_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        
        trace($a_row); 
        return $a_row;
        
    }
     
    /**
     * Returns data of an order wrapper record (specified by related ERP transaction document number) from the TYPO3 database
     *
     * @param   string      the ERP transaction document number (FSCHRIFT.AUFNR, ERP: "Vorgangsnummer") related to the order wrapper
     * @param   boolean     flag wether the query should retrieve the uid only (default:false = get all order wrapper data)
     * @global  object      $GLOBALS['TYPO3_DB']: t3lib_db Object (TYPO3 DB API)
     * @return  mixed       $getUidOnly==false: array of the specified order wrapper record on success, FALSE otherwise; $getUidOnly==true: integer uid of order wrapper record (0 if nothing found)
     * @throws  tx_pttools_exception   if wrong first param is given
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-08-02
     */
    public function selectOrderWrapperByRelatedDocNo($relatedDocNo, $getUidOnly=false) {
        
        if (strlen($relatedDocNo) < 4) {
            throw new tx_pttools_exception('Wrong query param', 3, 'Wrong first param ("'.$relatedDocNo.'") set for '.__METHOD__);
        }
        
        // query preparation
        if ($getUidOnly == true) {
            $select  = 'uid';
        } else {
            $select  = 'uid, '.
                       'pid, '.
                       'tstamp AS updateTimestamp, '.
                       'fe_cruser_id AS creatorId, '.
                       'customer_id AS customerId, '.
                       'related_doc_no AS relatedDocNo, '.
                       'orders_id AS orderObjId, '.
                       'order_timestamp AS orderTimestamp, '.
                       'sum_net AS sumNet, '.
                       'sum_gross AS sumGross, '.
                       'wf_status_code AS statusCode, '.
                       'wf_lastuser_id AS lastUserId, '.
                       'wf_lock_userid AS lockUserid, '.
                       'wf_lock_timestamp AS lockTimestamp';
        }
        $from    = 'tx_ptgsashop_order_wrappers';
        $where   = 'related_doc_no = '.$GLOBALS['TYPO3_DB']->fullQuoteStr($relatedDocNo, $from).' '.
                   tx_pttools_div::enableFields($from); 
        $groupBy = '';
        $orderBy = '';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        trace(tx_pttools_div::returnLastBuiltSelectQuery($GLOBALS['TYPO3_DB'], $select, $from, $where, $groupBy, $orderBy, $limit));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        
        $a_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        
        // return uid as int if only uid is requested
        if ($getUidOnly == true) {
            trace(intval($a_row['uid'])); 
            return intval($a_row['uid']);
        }
        
        trace($a_row); 
        return $a_row;
        
    }
     
    /**
     * Returns all order wrapper records specified by params from the TYPO3 database
     *
     * @param   integer     (optional) TYPO3 FE user ID to limit the results to (TYPO3: fe_users.uid) [default=-1: do not use this param in result list]
     * @param   integer     (optional) GSA customer user ID to limit the results to (GSA: ADRESSE.NUMMER) [default=-1: do not use this param in result list]
     * @param   integer     (optional) order wrapper status code to hide in results (e.g. status code for 'finished' orders) [default=-1: do not use this param in result list]
     * @param   integer     (optional) order wrapper status code to limit the results to [default=-1: do not use this param in result list]
     * @param   integer     (optional) timestamp of the start date/time to limit the results to [default=-1: do not use this param in result list]
     * @param   integer     (optional) timestamp of the end date/time to limit the results to [default=-1: do not use this param in result list]
     * @param   double      (optional) minimal order net sum to limit the results to [default=-1: do not use this param in result list]
     * @param   double      (optional) minimal order gross sum to limit the results to [default=-1: do not use this param in result list]
     * @param   boolean     (optional) flag whether inactive records (e.g. deleted, hidden) should be diplayed, too [default=0]
     * @global  object      $GLOBALS['TYPO3_DB']: t3lib_db Object (TYPO3 DB API)
     * @return  array       2D-array containing all order wrapper records of the specified FE user on sucess, empty array on fault
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-12-23
     */
    public function selectOrderWrappers($feUserId=-1, $customerId=-1, $hideStatusCode=-1, $limitStatusCode=-1, 
                                        $startTimestamp=-1, $endTimestamp=-1, $sumNetMin=-1, $sumGrossMin=-1, $displayInactive=0) {
        
        // query preparation
        $select  = 'uid, '.
                   'pid, '.
                   'tstamp AS updateTimestamp, '.
                   'deleted, '.
                   'hidden, '.
                   'fe_cruser_id AS creatorId, '.
                   'customer_id AS customerId, '.
                   'related_doc_no AS relatedDocNo, '.
                   'orders_id AS orderObjId, '.
                   'order_timestamp AS orderTimestamp, '.
                   'sum_net AS sumNet, '.
                   'sum_gross AS sumGross, '.
                   'wf_status_code AS statusCode, '.
                   'wf_lastuser_id AS lastUserId, '.
                   'wf_lock_userid AS lockUserid, '.
                   'wf_lock_timestamp AS lockTimestamp';
        $from    = 'tx_ptgsashop_order_wrappers';
        $where   = '1 '.
                   ($feUserId >= 0 ? 'AND fe_cruser_id = '.intval($feUserId).' ' : '').
                   ($customerId >= 0 ? 'AND customer_id = '.intval($customerId).' ' : '').
                   ($hideStatusCode >= 0 ? 'AND wf_status_code <> '.intval($hideStatusCode).' ' : '').
                   ($limitStatusCode >= 0 ? 'AND wf_status_code = '.intval($limitStatusCode).' ' : '').
                   ($startTimestamp >= 0 ? 'AND order_timestamp >= '.intval($startTimestamp).' ' : '').
                   ($endTimestamp >= 0 ? 'AND order_timestamp <= '.intval($endTimestamp).' ' : '').
                   ($sumNetMin >= 0 ? 'AND sum_net >= '.doubleval($sumNetMin).' ' : '').
                   ($sumGrossMin >= 0 ? 'AND sum_gross >= '.doubleval($sumGrossMin).' ' : '').
                   ($displayInactive == 0 ? tx_pttools_div::enableFields($from) : ''); 
        $groupBy = '';
        $orderBy = 'order_timestamp';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        trace(tx_pttools_div::returnLastBuiltSelectQuery($GLOBALS['TYPO3_DB'], $select, $from, $where, $groupBy, $orderBy, $limit));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
            
        // store all data in twodimensional array
        $a_result = array();
        while ($a_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $a_result[] = $a_row;
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        
        trace($a_result);
        return $a_result;
        
    }
     
    /**
     * Updates the status code, timestamp and optionally the order sums of an order wrapper record (specified by UID) in the TYPO3 database
     *
     * @param   integer     UID of the order wrapper record in the TYPO3 database
     * @param   integer     new status to set
     * @param   integer     UID of the FE user who performs the update
     * @param   mixed       (optional) updated order object of type tx_ptgsashop_order: if set, the total net/gross sums will be updated in the order wrapper record, too (default:NULL = do not update order object and total sums)
     * @global  object      $GLOBALS['TYPO3_DB']: t3lib_db Object (TYPO3 DB API)
     * @return  boolean     TRUE on success or FALSE on error
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-12-22 
     */
    public function updateOrderWrapperStatus($uid, $statusCode, $updateFeUserId, $orderObj=NULL) {
        
        $updateFieldsArr = array();
        
        // query preparation
        $table           = 'tx_ptgsashop_order_wrappers';
        $where           = 'uid = '.intval($uid);
        $updateFieldsArr['tstamp']      = time();
        $updateFieldsArr['wf_status_code'] = intval($statusCode);
        $updateFieldsArr['wf_lastuser_id'] = intval($updateFeUserId);
        
        // if a valid order object is passed: additionally update the total net/gross sums in the order wrapper record
        if (isset($orderObj) && $orderObj instanceof tx_ptgsashop_order) {
            $updateFieldsArr['sum_net']      = $orderObj->getOrderSumTotal(1);
            $updateFieldsArr['sum_gross']    = $orderObj->getOrderSumTotal(0);
        }
        
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $updateFieldsArr);
        trace(tx_pttools_div::returnLastBuiltUpdateQuery($GLOBALS['TYPO3_DB'], $table, $where, $updateFieldsArr));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        
        trace($res); 
        return $res;
        
    }
     
    /**
     * Updates the related ERP document number of an order wrapper record (specified by UID) in the TYPO3 database
     *
     * @param   integer     UID of the order wrapper record in the TYPO3 database
     * @param   string      related ERP document number ("Vorgangsnummer") of the saved order confirmation document in the ERP system
     * @global  object      $GLOBALS['TYPO3_DB']: t3lib_db Object (TYPO3 DB API)
     * @return  boolean     TRUE on success or FALSE on error
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-04 
     */
    public function updateOrderWrapperDocNo($uid, $relatedErpDocNo) {
        
        // query preparation
        $table           = 'tx_ptgsashop_order_wrappers';
        $where           = 'uid = '.intval($uid);
        $updateFieldsArr = array('related_doc_no'=>$relatedErpDocNo);
        
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $updateFieldsArr);
        trace(tx_pttools_div::returnLastBuiltUpdateQuery($GLOBALS['TYPO3_DB'], $table, $where, $updateFieldsArr));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        
        trace($res); 
        return $res;
        
    }
     
    /**
     * Replaces the related ERP document number of an order wrapper record (specified by existing ERP document number) in the TYPO3 database
     *
     * @param   string      existing/old related ERP document number ("Vorgangsnummer") of the order wrapper record in the TYPO3 database
     * @param   string      new related ERP document number ("Vorgangsnummer") from the ERP system
     * @global  object      $GLOBALS['TYPO3_DB']: t3lib_db Object (TYPO3 DB API)
     * @return  boolean     TRUE on success or FALSE on error
     * @throws  tx_pttools_exception   if the first param is empty/invalid
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-07-27 
     */
    public function updateOrderWrapperDocNoByReplacement($oldErpDocNo, $newErpDocNo) {
        
        // check for invalid SQL where clause argument
        if (empty($oldErpDocNo) || strlen(trim($oldErpDocNo)) < 1) {
            throw new tx_pttools_exception('Parameter error for doc no. replacement', 3);
        }
        
        // query preparation
        $table           = 'tx_ptgsashop_order_wrappers';
        $where           = 'related_doc_no = '.$GLOBALS['TYPO3_DB']->fullQuoteStr($oldErpDocNo, $table);
        $updateFieldsArr = array('related_doc_no'=>$newErpDocNo);
        
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $updateFieldsArr);
        trace(tx_pttools_div::returnLastBuiltUpdateQuery($GLOBALS['TYPO3_DB'], $table, $where, $updateFieldsArr));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        
        trace($res); 
        return $res;
        
    }
     
    /**
     * Inserts a new order wrapper record into the TYPO3 database
     *
     * @param   tx_ptgsashop_orderWrapper      object of type tx_ptgsashop_orderWrapper containing the data to insert
     * @return  integer     ID of the inserted record
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-12-23
     */
    public function insertOrderWrapper(tx_ptgsashop_orderWrapper $orderWrapperObject) {
        
        $insertFieldsArr = array();
        
        // query preparation
        $table = 'tx_ptgsashop_order_wrappers';
        $insertFieldsArr['pid']             = $orderWrapperObject->get_pid();
        $insertFieldsArr['tstamp']          = time();
        $insertFieldsArr['crdate']          = time();
        $insertFieldsArr['fe_cruser_id']    = $orderWrapperObject->get_creatorId();
        $insertFieldsArr['customer_id']     = $orderWrapperObject->get_customerId();
        $insertFieldsArr['related_doc_no']  = $orderWrapperObject->get_relatedDocNo();
        $insertFieldsArr['orders_id']       = $orderWrapperObject->get_orderObjId();
        $insertFieldsArr['order_timestamp'] = $orderWrapperObject->get_orderTimestamp();
        $insertFieldsArr['sum_net']         = $orderWrapperObject->get_sumNet();
        $insertFieldsArr['sum_gross']       = $orderWrapperObject->get_sumGross();
        $insertFieldsArr['wf_status_code']  = $orderWrapperObject->get_statusCode();
        $insertFieldsArr['wf_lastuser_id']  = $orderWrapperObject->get_lastUserId();
        
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $insertFieldsArr);
        trace(tx_pttools_div::returnLastBuiltInsertQuery($GLOBALS['TYPO3_DB'], $table, $insertFieldsArr));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        $lastInsertedId = $GLOBALS['TYPO3_DB']->sql_insert_id();
        
        trace($lastInsertedId);
        return $lastInsertedId;
        
    }
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_orderWrapperAccessor.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_orderWrapperAccessor.php']);
}

?>