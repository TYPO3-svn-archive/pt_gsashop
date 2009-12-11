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
 * Database accessor class for log entries of the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_logEntryAccessor.php,v 1.8 2007/10/15 13:03:25 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2006-06-23
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */


/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_logEntry.php';// GSA Shop log entry class

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general helper library class
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_iSingleton.php'; // interface for Singleton design pattern



/**
 *  Database accessor class for log entries (related to orders)
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2006-06-23
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_logEntryAccessor implements tx_pttools_iSingleton {
    
    /**
     * Properties
     */
    private static $uniqueInstance = NULL; // (tx_ptgsashop_logEntryAccessor object) Singleton unique instance
    
    
    
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
     * @since   2006-06-23
     */
    private function __construct() {
    
        trace('***** Creating new '.__CLASS__.' object. *****');
        
    }
    
    /**
     * Returns a unique instance (Singleton) of the object. Use this method instead of the private/protected class constructor.
     *
     * @param   void
     * @return  tx_ptgsashop_logEntryAccessor      unique instance of the object (Singleton) 
     * @global     
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-23
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
     * Returns data of an log entry record (specified by UID) from the TYPO3 database
     *
     * @param   integer     UID of the log entry record in the TYPO3 database
     * @global  object      $GLOBALS['TYPO3_DB']: t3lib_db Object (TYPO3 DB API)
     * @global  object      $GLOBALS['TSFE']->cObj: tslib_cObj Object (TYPO3 content object)
     * @return  mixed       array of the specified order record  on success, FALSE otherwise
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-23
     */
    public function selectLogEntryById($uid) {
        
        // query preparation
        $select  = 'uid, '.
                   'pid, '.
                   'tstamp, '.
                   'fe_cruser_id AS userId, '.
                   'order_wrapper_id AS orderWrapperId, '.
                   'log_entry AS text, '.
                   'status_prev AS statusPrev, '.
                   'status_new AS statusNew';
        $from    = 'tx_ptgsashop_amendmentlog';
        $where   = 'uid = '.intval($uid).' '.
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
     * Returns all log entry records specified by params from the TYPO3 database
     *
     * @param   integer     related order wrapper record UID to limit the results to
     * @param   integer     (optional) TYPO3 FE user ID to limit the results to
     * @param   integer     (optional) timestamp of the start date/time to limit the results to
     * @param   integer     (optional) timestamp of the end date/time to limit the results to
     * @global  object      $GLOBALS['TYPO3_DB']: t3lib_db Object (TYPO3 DB API)
     * @global  object      $GLOBALS['TSFE']->cObj: tslib_cObj Object (TYPO3 content object)
     * @return  array       2D-array containing all log entry records of the specified FE user on sucess, empty array on fault
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-23
     */
    public function selectLogEntries($orderWrapperId, $feUserId=-1, $startTimestamp=-1, $endTimestamp=-1) {
        
        // query preparation
        $select  = 'uid, '.
                   'pid, '.
                   'tstamp, '.
                   'deleted, '.
                   'hidden, '.
                   'fe_cruser_id AS userId, '.
                   'order_wrapper_id AS orderWrapperId, '.
                   'log_entry AS text, '.
                   'status_prev AS statusPrev, '.
                   'status_new AS statusNew';
        $from    = 'tx_ptgsashop_amendmentlog';
        $where   = 'order_wrapper_id = '.intval($orderWrapperId).' '.
                   ($feUserId >= 0 ? 'AND fe_cruser_id = '.intval($feUserId).' ' : '').
                   ($startTimestamp >= 0 ? 'AND tstamp >= '.intval($startTimestamp).' ' : '').
                   ($endTimestamp >= 0 ? 'AND tstamp <= '.intval($endTimestamp).' ' : '').
                   tx_pttools_div::enableFields($from); 
        $groupBy = '';
        $orderBy = 'tstamp';
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
     * Inserts a new log entry record into the TYPO3 database
     *
     * @param   tx_ptgsashop_logEntry      object of type tx_ptgsashop_logEntry containing the data to insert
     * @return  integer     ID of the inserted record
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-23 
     */
    public function insertLogEntry(tx_ptgsashop_logEntry $logentryObj) {
        
        $insertFieldsArr = array();
        
        // query preparation
        $table = 'tx_ptgsashop_amendmentlog';
        $insertFieldsArr['pid']             = $logentryObj->get_pid();
        $insertFieldsArr['tstamp']          = $logentryObj->get_tstamp();
        $insertFieldsArr['crdate']          = time();
        $insertFieldsArr['fe_cruser_id']    = $logentryObj->get_userId();
        $insertFieldsArr['order_wrapper_id']= $logentryObj->get_orderWrapperId();
        $insertFieldsArr['log_entry']       = $logentryObj->get_text();
        $insertFieldsArr['status_prev']     = $logentryObj->get_statusPrev();
        $insertFieldsArr['status_new']      = $logentryObj->get_statusNew();

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
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_logEntryAccessor.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_logEntryAccessor.php']);
}

?>