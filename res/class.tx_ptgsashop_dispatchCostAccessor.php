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
 * Database accessor class for dispatch cost data of the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_dispatchCostAccessor.php,v 1.15 2008/11/18 16:45:31 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2005-11-10
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */



/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_gsasocket').'res/class.tx_ptgsasocket_gsaDbAccessor.php'; // parent class for all GSA database accessor classes
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_iSingleton.php'; // interface for Singleton design pattern
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general helper library class



/**
 *  Database accessor class for dispatch cost data (based on GSA database structure)
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2005-11-10
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_dispatchCostAccessor extends tx_ptgsasocket_gsaDbAccessor implements tx_pttools_iSingleton {

    /**
     * Properties
     */
    private static $uniqueInstance = NULL; // (tx_ptgsashop_dispatchCostAccessor object) Singleton unique instance
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
     
    /**
     * Returns a unique instance (Singleton) of the object. Use this method instead of the private/protected class constructor.
     *
     * @param   void
     * @return  tx_ptgsashop_dispatchCostAccessor      unique instance of the object (Singleton) 
     * @global     
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-11-10
     */
    public static function getInstance() {
        
        if (self::$uniqueInstance === NULL) {
            $className = __CLASS__;
            self::$uniqueInstance = new $className;
        }
        
        return self::$uniqueInstance;
        
    }
    
    
    
    /***************************************************************************
     *   GSA DB RELATED METHODS
     **************************************************************************/
    
    /**
     * Returns the data of a dispatch record (specified by dispatch type's name) from the GSA database table 'VERSART'
     *
     * @param   string      dispatch type's name in GSA DB and ERP GUI (GSA database field 'VERSART.VERSART')
     * @return  array       associative array with data of the specified dispatch record
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-06-30
     */
    public function selectDispatchDataByName($dispatchType) {
        
        // if enabled, do charset conversion of where clause param
        if ($this->charsetConvEnabled == 1) {
            $dispatchType = iconv($this->siteCharset, $this->gsaCharset, $dispatchType);
        }
        
        // query preparation
        $select  = 'NUMMER, FLDN01, FLDN02, FLDN03, FLDN04, EURO, FREIAB01, FREIAB02, FREIAB03, FREIAB04';
        $from    = $this->getTableName('VERSART');
        $where   = 'VERSART LIKE '.$this->gsaDbObj->fullQuoteStr($dispatchType, $from);
        $groupBy = '';
        $orderBy = '';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        $a_row = $this->gsaDbObj->sql_fetch_assoc($res);
        $this->gsaDbObj->sql_free_result($res);
        
        // if enabled, do charset conversion of all non-binary string data 
        if ($this->charsetConvEnabled == 1) {
            $a_row = tx_pttools_div::iconvArray($a_row, $this->gsaCharset, $this->siteCharset);
        }
        
        trace($a_row); 
        return $a_row;
        
    }
    
    /**
     * Returns the data of a dispatch record (specified by dispatch type's UID) from the GSA database table 'VERSART'
     *
     * @param   integer     dispatch type's UID in GSA (GSA database field 'VERSART.NUMMER')
     * @return  array       associative array with data of the specified dispatch record
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-10-30
     */
    public function selectDispatchDataByUid($dispatchType) {
        
        // query preparation
        $select  = 'VERSART, FLDN01, FLDN02, FLDN03, FLDN04, EURO, FREIAB01, FREIAB02, FREIAB03, FREIAB04';
        $from    = $this->getTableName('VERSART');
        $where   = 'NUMMER = '.intval($dispatchType);
        $groupBy = '';
        $orderBy = '';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        $a_row = $this->gsaDbObj->sql_fetch_assoc($res);
        $this->gsaDbObj->sql_free_result($res);
        
        // if enabled, do charset conversion of all non-binary string data 
        if ($this->charsetConvEnabled == 1) {
            $a_row = tx_pttools_div::iconvArray($a_row, $this->gsaCharset, $this->siteCharset);
        }
        
        trace($a_row); 
        return $a_row;
        
    }
    
    /**
     * Returns data of all dispatch cost records from the GSA database table 'VERSART' 
     *
     * @param   void        
     * @return  array       twodimensional array with records dispatch cost records
     * @throws  tx_pttools_exception   if the query fails or returns empty result
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-10-29
     */
    public function selectDispatchCostRecords() {
        
        // query preparation
        $select  = 'NUMMER, VERSART, FLDN01, FLDN02, FLDN03, FLDN04, EURO, FREIAB01, FREIAB02, FREIAB03, FREIAB04';
        $from    = $this->getTableName('VERSART');
        $where   = '';
        $groupBy = '';
        $orderBy = 'VERSART';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false || $this->gsaDbObj->sql_num_rows($res) == 0) {
            throw new tx_pttools_exception('Query failed or returned empty result', 1, $this->gsaDbObj->sql_error());
        } 
            
        // store all data in twodimensional array
        $a_result = array();
        while ($a_row = $this->gsaDbObj->sql_fetch_assoc($res)) {
            // if enabled, do charset conversion of all non-binary string data 
            if ($this->charsetConvEnabled == 1) {
                $a_row = tx_pttools_div::iconvArray($a_row, $this->gsaCharset, $this->siteCharset);
            }
            $a_result[] = $a_row;
        }
        $this->gsaDbObj->sql_free_result($res);
        
        trace($a_result);
        return $a_result;
        
    }  
    
    /**
     * Inserts a dispatch cost record into the GSA DB table 'VERSART' and returns the inserted record's UID
     * 
     * @param   tx_ptgsashop_dispatchCost    object of type tx_ptgsashop_dispatchCost to insert
     * @return  integer     UID of the inserted record
     * @throws  tx_pttools_exception   if params are not valid
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-10-31
     */
    public function insertDispatchCostRecord(tx_ptgsashop_dispatchCost $costObj) {
        
        if (!strlen($costObj->get_costTypeName()) > 0) {
            throw new tx_pttools_exception('Parameter error', 3, 'Dispatch cost object passed to '.__METHOD__.' is not valid.');
        }
        
        $table = $this->getTableName('VERSART');
        $insertFieldsArr = array();  
        
        // query preparation: known fields to set  
        $insertFieldsArr['NUMMER']      = $this->getNextId($table);
        $insertFieldsArr['VERSART']     = $costObj->get_costTypeName();
        $insertFieldsArr['FLDN01']      = $costObj->get_costComp1();
        $insertFieldsArr['FLDN02']      = $costObj->get_costComp2();
        $insertFieldsArr['FLDN03']      = $costObj->get_costComp3();
        $insertFieldsArr['FLDN04']      = $costObj->get_costComp4();
        $insertFieldsArr['FREIAB01']    = $costObj->get_allowanceComp1();
        $insertFieldsArr['FREIAB02']    = $costObj->get_allowanceComp2();
        $insertFieldsArr['FREIAB03']    = $costObj->get_allowanceComp3();
        $insertFieldsArr['FREIAB04']    = $costObj->get_allowanceComp4();
        
        // TODO: GSA DB fields set per default by ERP-GUI, currently unused by pt_gsaadmin GUI
        $insertFieldsArr['EURO']    = 1;
        
        
        // unset NULL values - this is crucial since TYPO3's exec_INSERTquery() will quote all fields including NULL otherwise!!
        foreach ($insertFieldsArr as $key=>$value) {
            if (is_null($value)) {
                unset($insertFieldsArr[$key]);
            }
        }
        trace($insertFieldsArr, 0, '$insertFieldsArr ('.__METHOD__.')');
        
        // if enabled, do charset conversion of all non-binary string data 
        if ($this->charsetConvEnabled == 1) {
            $insertFieldsArr = tx_pttools_div::iconvArray($insertFieldsArr, $this->siteCharset, $this->gsaCharset);
        }
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_INSERTquery($table, $insertFieldsArr);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        $lastInsertedId = $insertFieldsArr['NUMMER'];
        
        trace($lastInsertedId); 
        return $lastInsertedId;
        
    }
    
    /**
     * Updates a dispatch cost record in the GSA DB table 'VERSART'
     * 
     * @param   tx_ptgsashop_dispatchCost     dispatch cost object to update
     * @return  boolean     TRUE on success or FALSE on error
     * @throws  tx_pttools_exception   if params are not valid
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-10-31
     */
    public function updateDispatchCostRecord(tx_ptgsashop_dispatchCost $costObj) {
        
        if ($costObj->get_costUid() < 1 || !strlen($costObj->get_costTypeName()) > 0) {
            throw new tx_pttools_exception('Parameter error', 3, '1st param for '.__METHOD__.' is not a valid dispatch cost object to store in the database!');
        }
        
        $table = $this->getTableName('VERSART');
        $where = 'NUMMER = '.intval($costObj->get_costUid());
        $updateFieldsArr = array();  
        $noQuoteFieldsArr = array(); 
        
        // query preparation
        $updateFieldsArr['VERSART']     = $costObj->get_costTypeName();
        $updateFieldsArr['FLDN01']      = $costObj->get_costComp1();
        $updateFieldsArr['FLDN02']      = $costObj->get_costComp2();
        $updateFieldsArr['FLDN03']      = $costObj->get_costComp3();
        $updateFieldsArr['FLDN04']      = $costObj->get_costComp4();
        $updateFieldsArr['FREIAB01']    = $costObj->get_allowanceComp1();
        $updateFieldsArr['FREIAB02']    = $costObj->get_allowanceComp2();
        $updateFieldsArr['FREIAB03']    = $costObj->get_allowanceComp3();
        $updateFieldsArr['FREIAB04']    = $costObj->get_allowanceComp4();
        
        
        // check for NULL values - this is crucial since TYPO3's exec_UPDATEquery() will quote all fields including NULL otherwise!!
        foreach ($updateFieldsArr as $key=>$value) {
            if (is_null($value)) {
                $noQuoteFieldsArr[] = $key;
                $updateFieldsArr[$key] = 'NULL';  // combined with $noQuoteFieldsArr this is a hack to force TYPO3's exec_UPDATEquery() to update NULL
            }
        }
        trace($updateFieldsArr, 0, '$updateFieldsArr ('.__METHOD__.')');
        trace($noQuoteFieldsArr, 0, '$noQuoteFieldsArr ('.__METHOD__.')');
        
        // if enabled, do charset conversion of all non-binary string data 
        if ($this->charsetConvEnabled == 1) {
            $updateFieldsArr = tx_pttools_div::iconvArray($updateFieldsArr, $this->siteCharset, $this->gsaCharset);
        }
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_UPDATEquery($table, $where, $updateFieldsArr, $noQuoteFieldsArr);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        
        trace($res); 
        return $res;
        
    }
    
    /**
     * Deletes a dispatch cost record from the GSA DB table 'VERSART' 
     * 
     * @param   integer     GSA database UID of the dispatch cost record (VERSART.NUMMER)
     * @return  void                    
     * @throws  tx_pttools_exception   if params are not valid
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-10-31
     */
    public function deleteDispatchCostRecord($gsaRecordUid) {
        
        if (!is_numeric($gsaRecordUid)) {
            throw new tx_pttools_exception('Parameter error', 3, 'First parameter for '.__METHOD__.' is not a UID');
        }
        
        // query preparation      
        $table = $this->getTableName('VERSART');
        $where = 'NUMMER = '.intval($gsaRecordUid);
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_DELETEquery($table, $where);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        
    }
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_dispatchCostAccessor.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_dispatchCostAccessor.php']);
}

?>