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
 * Database accessor class for tax data of the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_taxAccessor.php,v 1.15 2008/11/18 16:45:31 ry37 Exp $
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
 *  Database accessor class for GSA tax data (based on GSA database structure)
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2005-11-10
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_taxAccessor extends tx_ptgsasocket_gsaDbAccessor implements tx_pttools_iSingleton {

    /**
     * Properties
     */
    private static $uniqueInstance = NULL; // (tx_ptgsashop_taxAccessor object) Singleton unique instance
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
    
    /**
     * Returns a unique instance (Singleton) of the object. Use this method instead of the private/protected class constructor.
     *
     * @param   void
     * @return  tx_ptgsashop_taxAccessor      unique instance of the object (Singleton) 
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
     * Returns true if the "old" database table STEUER exists in the GSA database [Notice: DB-table STEUER was used exclusively by the ERP in all ERP software versions up to 2.7.x]
     *
     * @param   void
     * @return  boolean     TRUE if table exists in in the GSA database, FALSE if not
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-04-03
     */
    public function oldTaxTableExists() {
        
        static $alreadyChecked = false;
        static $tableExists = false;    // remember result of check if already done (prevents multiple checks per script execution)
        
        if ($alreadyChecked == false) {
            $tableExists = tx_pttools_div::dbTableExists($this->getTableName('STEUER'), $this->gsaDbObj);
            $alreadyChecked = true;
        }
        
        return $tableExists; 
        
    }  
    
    /**
     * Returns an array with "old" tax data of a given tax code (or FALSE on empty result) from the "old" STEUER database table of the GSA database [Notice: DB-table STEUER was used exclusively by the ERP in all ERP software versions up to 2.7.x]
     *
     * @param   string      tax code to use (ERP: currently '00'-'19' in GSA database table 'STEUER')
     * @return  mixed       associative array with tax rate data from the GSA database or FALSE on empty result
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @see     tx_ptgsashop_lib::getTaxRate()
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-22
     */
    public function selectTaxDataOld($taxCode) {
        
        // query preparation
        $select  = 'ASATZ, DATUM, NSATZ, BEMERKUNG';
        $from    = $this->getTableName('STEUER');
        $where   = 'CODE LIKE '.$this->gsaDbObj->fullQuoteStr($taxCode, $from);
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
     * Returns true if the "new" database table BHSTEUER exists in the GSA database [Notice: new DB-table BHSTEUER was used introcuded with ERP software version 2.8.x]
     *
     * @param   void
     * @return  boolean     TRUE if table exists in in the GSA database, FALSE if not
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-01-11
     */
    public function newTaxTableExists() {
        
        static $alreadyChecked = false;
        static $tableExists = false;    // remember result of check if already done (prevents multiple checks per script execution)
        
        if ($alreadyChecked == false) {
            $tableExists = tx_pttools_div::dbTableExists($this->getTableName('BHSTEUER'), $this->gsaDbObj);
            $alreadyChecked = true;
        }
        
        return $tableExists; 
        
    }  
    
    /**
     * Returns the "new" tax rate of a given tax code for a given date from the GSA database [Notice: new DB-table BHSTEUER has been introcuded with ERP software version 2.8.x]
     *
     * @param   string      see tx_ptgsashop_taxAccessor::selectTaxDataByCode()
     * @param   string      see tx_ptgsashop_taxAccessor::selectTaxDataByCode()
     * @return  double      tax rate depending on given params (double with 4 digits after the decimal point)
     * @see     tx_ptgsashop_taxAccessor::selectTaxDataByCode()
     * @see     tx_ptgsashop_lib::getTaxRate()
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-01-11 (contents moved to selectTaxDataByCode() 2008-04-03)
     */
    public function selectTaxRate($taxCode, $date='') {
        
        $a_row = $this->selectTaxDataByCode($taxCode, $date);
        
        trace($a_row['STEUERSATZPROZ']); 
        return $a_row['STEUERSATZPROZ'];
        
    }  
    
    /**
     * Returns an array with tax data of a given tax code from the GSA database [Notice: new DB-table BHSTEUER has been introcuded with ERP software version 2.8.x]
     *
     * @param   string      tax code to use (ERP: currently '00'-'19' in GSA database table 'STEUER')
     * @param   string      (optional) date to use (date string format: YYYY-MM-DD) - if not set today's date will be used
     * @return  array       associative array with tax rate data from the GSA database
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-04-03 (based on code from selectTaxRate() since 2007-01-11)
     */
    public function selectTaxDataByCode($taxCode, $date='') {
        
        $date = (string)($date=='' ? date('Y-m-d') : $date); // if no date set use today's date
        
        // query preparation
        $select  = 'NUMMER, STEUERSATZCODE, STEUERSATZPROZ, GUELTIGABTTMMJJJJ'.
                   ($this->oldTaxTableExists() == true ? ', st.BEMERKUNG AS BEMERKUNG' : '').' ';
        $from    = $this->getTableName('BHSTEUER').' bh'. 
                   ($this->oldTaxTableExists() == true ? ' LEFT JOIN '.$this->getTableName('STEUER').' st ON bh.STEUERSATZCODE LIKE st.CODE' : '');
        $where   = 'STEUERSATZCODE LIKE '.$this->gsaDbObj->fullQuoteStr($taxCode, $from).' '.
                   'AND GUELTIGABTTMMJJJJ <= '.$this->gsaDbObj->fullQuoteStr($date, $from);  // Note: field GUELTIGABTTMMJJJJ contains data in string format 'YYYY-MM-DD'! :-)
        $groupBy = '';
        $orderBy = 'GUELTIGABTTMMJJJJ DESC';
        $limit   = '1';
        
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
     * Returns an array with tax data of a given tax record UID  from the GSA database [Notice: new DB-table BHSTEUER has been introcuded with ERP software version 2.8.x]
     *
     * @param   string      record UID to use (GSA-DB: BHSTEUER.NUMMER)
     * @return  array       associative array with tax rate data from the GSA database
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-04-04
     */
    public function selectTaxDataByUid($uid) {
        
        // query preparation
        $select  = 'NUMMER, STEUERSATZCODE, STEUERSATZPROZ, GUELTIGABTTMMJJJJ'.
                   ($this->oldTaxTableExists() == true ? ', st.BEMERKUNG AS BEMERKUNG' : '').' ';
        $from    = $this->getTableName('BHSTEUER').' bh'. 
                   ($this->oldTaxTableExists() == true ? ' LEFT JOIN '.$this->getTableName('STEUER').' st ON bh.STEUERSATZCODE LIKE st.CODE' : '');
        $where   = 'bh.NUMMER = '.intval($uid);
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
     * Returns data of all tax rate records from the GSA database tables 'BHSTEUER' and 'STEUER' (if existent)
     *
     * @param   string      (optional) tax code to limit the result set to (ERP: currently '00'-'19' in GSA database table 'STEUER'); if not set (default), records with all tax codes are retrieved
     * @return  array       twodimensional array with records of all valid tax rate records
     * @throws  tx_pttools_exception   if the query fails (no exception thrown here on or empty result!)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-04-03
     */
    public function selectTaxRateRecords($taxCode='') {
        
        // query preparation
        $select  = 'bh.NUMMER, bh.STEUERSATZCODE, bh.STEUERSATZPROZ, bh.GUELTIGABTTMMJJJJ'.
                   ($this->oldTaxTableExists() == true ? ', st.BEMERKUNG AS BEMERKUNG' : '').' ';
        $from    = $this->getTableName('BHSTEUER').' bh'. 
                   ($this->oldTaxTableExists() == true ? ' LEFT JOIN '.$this->getTableName('STEUER').' st ON bh.STEUERSATZCODE LIKE st.CODE' : '');
        $where   = (empty($taxCode) ? '1' : 'STEUERSATZCODE LIKE '.$this->gsaDbObj->fullQuoteStr($taxCode, $from)).' ';
        $groupBy = 'bh.STEUERSATZCODE, bh.GUELTIGABTTMMJJJJ, bh.STEUERSATZPROZ';  // this is required due to a bug of the ERP system: it creates multiple redundant records (but with different UIDs in field NUMMER) in the DB table BHSTEUER
        $orderBy = 'bh.STEUERSATZCODE, bh.GUELTIGABTTMMJJJJ';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
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
     * Returns tax rate (and eventually note) of all tax codes from the GSA database [Notice: This requires the "new" DB-table BHSTEUER was used introcuded with ERP software version 2.8.x]
     *
     * @param   void
     * @return  array       twodimensional array with data of tax codes: key = (string)taxcode, value = array('taxrate'=>(double)taxrate, 'taxnote'=>(string)'taxnote') 
     * @throws  tx_pttools_exception   if the query fails or returns empty result
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-10-11
     */
    public function selectTaxCodes() {
        
        // query preparation
        $select  = 'STEUERSATZCODE';
        $from    = $this->getTableName('BHSTEUER');
        $where   = '1';
        $groupBy = 'STEUERSATZCODE';
        $orderBy = 'STEUERSATZCODE';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false || $this->gsaDbObj->sql_num_rows($res) == 0) {
            throw new tx_pttools_exception('Query failed or returned empty result', 1, $this->gsaDbObj->sql_error());
        } 
            
        // store codes in array keys
        $a_result = array();
        while ($a_row = $this->gsaDbObj->sql_fetch_assoc($res)) {
            // if enabled, do charset conversion of all non-binary string data 
            if ($this->charsetConvEnabled == 1) {
                $a_row = tx_pttools_div::iconvArray($a_row, $this->gsaCharset, $this->siteCharset);
            }
            $a_result[$a_row['STEUERSATZCODE']] = array('taxRate'=>0, 'taxNote'=>'');
        }
        $this->gsaDbObj->sql_free_result($res);
        
        // query current tax rates and notes (get notes from "old" DB table STEUER, if this table exists in the GSA DB) for all codes
        foreach ($a_result as $taxCode=>$taxDataArr) {
            $taxDataArr['taxRate'] = $this->selectTaxRate($taxCode);
            // get note from "old" DB table STEUER, if this table exists in the GSA DB
            if ($this->oldTaxTableExists() == true) {
                $tmpOldTaxDataArr = $this->selectTaxDataOld($taxCode);
                $taxDataArr['taxNote'] = $tmpOldTaxDataArr['BEMERKUNG'];
            }
            $a_result[$taxCode] = $taxDataArr;
        }
        
        trace($a_result);
        return $a_result;
        
    }  
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_taxAccessor.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_taxAccessor.php']);
}

?>