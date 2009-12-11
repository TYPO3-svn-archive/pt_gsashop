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
 * Database accessor class for articles of the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_articleAccessor.php,v 1.49 2009/03/26 14:34:05 ry21 Exp $
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
 *  Database accessor class for articles (based on GSA database structure)
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2005-11-10
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_articleAccessor extends tx_ptgsasocket_gsaDbAccessor implements tx_pttools_iSingleton {
    
    /**
     * Properties
     */
    protected static $uniqueInstance = NULL; // (tx_ptgsashop_articleAccessor object) Singleton unique instance
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/ 
    
    /**
     * Returns a unique instance (Singleton) of the object. Use this method instead of the protected class constructor.
     *
     * @param   void
     * @return  tx_ptgsashop_articleAccessor      unique instance of the object (Singleton) 
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
     * Returns an array with the basic data of an article (specified by UID) from the GSA database.
     *
     * The image blob data query is seperated to function selectArticleImage(), as it would make the standard data query extremely slow and inefficient.
     * Note: The GSA database field name `MATCH` is a reserved (My)SQL word, so it has to be used with backticks or <tablename>.MATCH !
     *
     * @param   integer     UID of the article from the GSA database (GSA database field "ARTIKEL.NUMMER")
     * @return  array       associative array with data of an article from the GSA database
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @see     tx_ptgsashop_articleAccessor::selectArticleImage(), tx_ptgsashop_articleAccessor::selectCompleteArticleData(), 
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-03-04 / DBAL 2005-04-21
     */
    public function selectArticleData($gsaArticleUid) {
        
        // query preparation
        $select  = 'art.NUMMER, art.ARTNR, art.MATCH, art.MATCH2, art.ZUSTEXT1, art.ZUSTEXT2, '.
                   'art.PRBRUTTO, art.USTSATZ, art.USTAUSLAND, art.FIXKOST1, art.FIXKOST2, art.ONLINEARTIKEL, art.PASSIV, art.WEBADRESSE,'.
                   'art.FLD01, art.FLD02, art.FLD03, art.FLD04, art.FLD05, art.FLD06, art.FLD07, art.FLD08, art.EANNUMMER';
        $from    = $this->getTableName('ARTIKEL').' art';
        $where   = 'NUMMER = '.intval($gsaArticleUid);
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
     * Returns an array with the basic data of an article (specified by EAN) from the GSA database.
     *
     * @param 	string	ean
     * @return 	array|false	associative array with data of an article from the GSA database or false if no article found
     * @throws  tx_pttools_exception   if the query fails/returns false or if more than 1 article found
     * @author	Fabrizio Branca <branca@punkt.de>
     * @since	2008-09-22
     */
    public function selectArticleDataByEAN($ean) {
        
        // query preparation
        $select  = 'art.NUMMER, art.ARTNR, art.MATCH, art.MATCH2, art.ZUSTEXT1, art.ZUSTEXT2, '.
                   'art.PRBRUTTO, art.USTSATZ, art.USTAUSLAND, art.FIXKOST1, art.FIXKOST2, art.ONLINEARTIKEL, art.PASSIV, art.WEBADRESSE,'.
                   'art.FLD01, art.FLD02, art.FLD03, art.FLD04, art.FLD05, art.FLD06, art.FLD07, art.FLD08, art.EANNUMMER';
        $from    = $this->getTableName('ARTIKEL').' art';
        $where   = 'EANNUMMER = '.$this->gsaDbObj->fullQuoteStr($ean, $from);
        $groupBy = '';
        $orderBy = '';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        $a_row = $this->gsaDbObj->sql_fetch_assoc($res);
        if ($this->gsaDbObj->sql_fetch_assoc($res)) {
        	throw new tx_pttools_exception('Inconsistent data', 0, 'Found more than one article with the ean "'.$ean.'"');
        }
        $this->gsaDbObj->sql_free_result($res);
        
        // if enabled, do charset conversion of all non-binary string data 
        if ($this->charsetConvEnabled == 1) {
            $a_row = tx_pttools_div::iconvArray($a_row, $this->gsaCharset, $this->siteCharset);
        }
        
        trace($a_row); 
        return $a_row;
        
    }
    
    /**
     * Returns the binary image data of an article (specified by UID) from the GSA database
     *
     * @param   integer     UID of the article from the GSA database (GSA database field "NUMMER")
     * @return  string      binary image data of an article from the GSA database
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-03-07 / DBAL 2005-04-21
     */
    public function selectArticleImage($gsaArticleUid) {
        
        // query preparation
        $select  = 'BILD';
        $from    = $this->getTableName('ARTIKEL');
        $where   = 'NUMMER = '.intval($gsaArticleUid).' ';
        $groupBy = '';
        $orderBy = '';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        $a_row = $this->gsaDbObj->sql_fetch_assoc($res);
        $this->gsaDbObj->sql_free_result($res); // $res may contain large result sets because of longblob use for images
        
        // do NO charset conversion for binary string data!
        
        trace($a_row[$select]); 
        return $a_row[$select];
        
    }
    
    /**
     * Returns an array with all GSA specific article data of an article (specified by UID) from the GSA database.
     *
     * Note: The most data retrieved by this method is not needed for the shop itself, but for the storage of an GSA order document.
     *
     * @param   integer     UID of the article from the GSA database (GSA database field "ARTIKEL.NUMMER")
     * @return  array       associative array with additional GSA specific data of an article from the GSA database
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @see     tx_ptgsashop_articleAccessor::selectArticleData(), tx_ptgsashop_articleAccessor::selectArticleImage() 
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-11-29
     */
    public function selectCompleteArticleData($gsaArticleUid) {
        
        // query preparation
        $select  = '*';
        $from    = $this->getTableName('ARTIKEL');
        $where   = 'NUMMER = '.intval($gsaArticleUid);
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
        
        // if enabled, do charset conversion of all non-binary string data (exclude binary field 'BILD' from conversion)
        if ($this->charsetConvEnabled == 1) {
            $a_row = tx_pttools_div::iconvArray($a_row, $this->gsaCharset, $this->siteCharset, array('BILD'));
        }
        
        trace($a_row); 
        return $a_row;
        
    }
    
    /**
     * Returns an array with selected retail pricing data of an article (specified by UID, depending on purchase quantity) from the GSA database.
     * TODO: this method should be moved to a new class tx_ptgsashop_scalePriceAccessor as selectScalePriceData()!
     *
     * Notice: GSA DB table VKPREIS fields PR99_2-PR99_5, DATUMVON2-DATUMVON5 and DATUMBIS2-DATUMBIS5 
     * are currently unaccounted for pricing data since nobody knows what they are used for :)
     *
     * @param   integer     UID of the article from the GSA database (GSA database field "VKPREIS.ARTINR")
     * @param   integer     purchase quantity for article (GSA database field "VKPREIS.ABMENGE")
     * @return  array       associative array with retail pricing data of an article from the GSA database
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-04-05 / DBAL 2005-04-21
     */
    public function selectRetailPricingData($gsaArticleUid, $purchaseQty) {
         
        // query preparation (notice for  VKPREIS fields PR99_2-PR99_5, DATUMVON2-DATUMVON5 and DATUMBIS2-DATUMBIS5: see function comment)
        $select  = 'ABMENGE, PR01, PR02, PR03, PR04, PR05, AKTION, DATUMVON, DATUMBIS, PR99';
        $from    = $this->getTableName('VKPREIS');
        $where   = 'ARTINR = '.intval($gsaArticleUid).' '.
                   'AND ABMENGE <= '.intval($purchaseQty).' ';
        $groupBy = '';
        $orderBy = 'ABMENGE DESC';
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
     * Returns the quantities of all scale price records for an article from the GSA database table 'VKPREIS' 
     * TODO: this method should be moved to a new class tx_ptgsashop_scalePriceAccessor!
     *
     * Notice: GSA DB table VKPREIS fields PR99_2-PR99_5, DATUMVON2-DATUMVON5 and DATUMBIS2-DATUMBIS5 
     * are currently unaccounted for pricing data since nobody knows what they are used for :)
     *
     * @param   integer     UID of the article from the GSA database (GSA database field "VKPREIS.ARTINR")
     * @return  array       array with quantities of all scale price records related to the article
     * @throws  tx_pttools_exception   if the query fails or returns empty result
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-10-23
     */
    public function selectScalePriceQuantities($gsaArticleUid) {
        
        // query preparation
        $select  = 'ABMENGE';
        $from    = $this->getTableName('VKPREIS');
        $where   = 'ARTINR = '.intval($gsaArticleUid);
        $groupBy = '';
        $orderBy = 'ABMENGE';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        } 
            
        // store all data in twodimensional array
        $a_result = array();
        while ($a_row = $this->gsaDbObj->sql_fetch_assoc($res)) {
            // if enabled, do charset conversion of all non-binary string data 
            if ($this->charsetConvEnabled == 1) {
                $a_row = tx_pttools_div::iconvArray($a_row, $this->gsaCharset, $this->siteCharset);
            }
            $a_result[] = $a_row['ABMENGE'];
        }
        $this->gsaDbObj->sql_free_result($res);
        
        trace($a_result);
        return $a_result;
        
    }  
    
    /**
     * Returns an array with selected customer specific pricing data of an article (specified by article UID and customer UID) from the GSA database.
     *
     * Notice: GSA DB table KUNPREIS fields RABATT, EURO, ARTNRSONPREIS, EKPREIS, ARTIKELPREIS, ARTIKELEURO
     * are currently unaccounted for customer specific pricing data since nobody knows what they are used for :)
     *
     * @param   integer     UID of the article from the GSA database (GSA database field "VKPREIS.ARTINR")
     * @param   integer     UID of the customer's main address data record in the GSA database (GSA database field "ADRESSE.NUMMER")
     * @return  array       associative array with customer specific pricing data of an article from the GSA database
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-01
     */
    public function selectCustomerSpecificPricingData($gsaArticleUid, $gsaCustomerAddressUid) {
         
        // query preparation (notice for KUNPREIS fields RABATT, EURO, ARTNRSONPREIS, EKPREIS, ARTIKELPREIS, ARTIKELEURO: see function comment)
        $select  = 'NUMMER, PREIS, PRBRUTTO, AKTION, DATUMVON, DATUMBIS';
        $from    = $this->getTableName('KUNPREIS');
        $where   = 'ARTINR = '.intval($gsaArticleUid).' '.
                   'AND ADRINR = '.intval($gsaCustomerAddressUid).' ';
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
     * Returns data of all "online articles" (records specified by 'ONLINEARTIKEL = 1') from the GSA database table 'ARTIKEL' 
     *
     * @param   string      (optional) exact database field name (or comma seperated list of field names) of the GSA database table 'ARTIKEL' to order the result list by
     * @param   string      (optional) string to be used by the SQL LIMIT clause (e.g. '15' or '15,30')
     * @param	string		(optional) search string to use for WHERE clause
     * @param	string		(optional) additional WHERE clause, that is appended with "AND"
     * @param 	bool		(optional) throw an exception on empty result, default is true (to be downwards compatible), added by ry44, 2008-09-11
     * @return  array       twodimensional array with records of all online articles (or empty array on empty result when $throwExceptionOnEmptyResult is set to false)
     * @throws  tx_pttools_exception   if the query fails or returns empty result
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-08-18
     */
    public function selectOnlineArticles($orderField='', $limitString='', $searchString='', $andWhere='', $throwExceptionOnEmptyResult=true) {
        
        // query preparation
        $select  = 'art.NUMMER, art.ARTNR, art.MATCH, art.MATCH2, art.PASSIV, '.
                   'art.FLD01, art.FLD02, art.FLD03, art.FLD04, art.FLD05, art.FLD06, art.FLD07, art.FLD08, art.SUCHBEGRIFF2 ';
        $from    = $this->getTableName('ARTIKEL').' art';
        $where   = 'ONLINEARTIKEL = 1 ';
        if ($searchString != '') {
            $search = 'art.NUMMER like "%'.$searchString.'%" ';
            $search .= 'OR art.ARTNR like "%'.$searchString.'%" ';
            $search .= 'OR art.MATCH like "%'.$searchString.'%" ';
            $search .= 'OR art.MATCH2 like "%'.$searchString.'%" ';
            $search .= 'OR art.SUCHBEGRIFF2 like "%'.$searchString.'%" ';
            $where   .= ' AND ('.$search.')';
        }
        if ($andWhere != '') {
            $where .= ' AND '.$andWhere;
        }
        
        $groupBy = '';
        if (empty($orderField)) {
            $orderBy = 'art.ARTNR';
        } else {
            $orderByParts = array();
            foreach (t3lib_div::trimExplode(',', $orderField) as $value) {
                $orderByParts[] = 'art.'.$value;
            }
            $orderBy = implode(', ',$orderByParts);
        }
        $limit   = $limitString;
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        } 
        if ($throwExceptionOnEmptyResult && $this->gsaDbObj->sql_num_rows($res) == 0) {
            throw new tx_pttools_exception('Empty result', 0);
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
     * Returns quantity of all "online articles" (records specified by 'ONLINEARTIKEL = 1') from the GSA database table 'ARTIKEL' 
     *
 	 * @param	string		(optional) search string
 	 * @param	string		(optional) additional where clause, that is appended with "AND"
     * @return  integer     quantity of online articles
     * @throws  tx_pttools_exception   if the query fails or returns empty result
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-10-30
     */
    public function selectOnlineArticlesQuantity($searchString='', $andWhere='') {
        
        // query preparation
        $select  = 'COUNT(*) as qty ';
        $from    = $this->getTableName('ARTIKEL').' art';
        $where   = 'ONLINEARTIKEL = 1 ';
        if ($searchString != '') {
            $search = 'art.NUMMER like "%'.$searchString.'%" ';
            $search .= 'OR art.ARTNR like "%'.$searchString.'%" ';
            $search .= 'OR art.MATCH like "%'.$searchString.'%" ';
            $search .= 'OR art.MATCH2 like "%'.$searchString.'%" ';
            $search .= 'OR art.SUCHBEGRIFF2 like "%'.$searchString.'%" ';
            $where   .= ' AND ('.$search.')';
        }
        if ($andWhere != '') {
            $where .= ' AND '.$andWhere;
        }
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
        
        trace($a_row['qty']); 
        return $a_row['qty'];
        
    }  
    
    /**
     * Returns the UIDs of all suppliers of an article from the GSA database table 'LIEFART' 
     *
     * @param   integer     UID of the article from the GSA database (GSA database field 'LIEFART.IARTNR')
     * @return  array       array with UIDs of the article suppliers (GSA database field 'LIEFART.IARTNR', refers to 'ADRESSE.NUMMER') or empty array if no suplliers found
     * @throws  tx_pttools_exception   if the query fails
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-22
     */
    public function selectArticleSuppliersUids($gsaArticleUid) {
        
        // query preparation
        $select  = 'ILIEFNR';
        $from    = $this->getTableName('LIEFART');
        $where   = 'IARTNR = '.intval($gsaArticleUid).' ';
        $groupBy = '';
        $orderBy = 'ILIEFNR';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {  // this query may return an empty result if no suppliers are set for the article
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        } 
            
        // store all data in twodimensional array
        $a_result = array();
        while ($a_row = $this->gsaDbObj->sql_fetch_assoc($res)) {
            // if enabled, do charset conversion of all non-binary string data 
            if ($this->charsetConvEnabled == 1) {
                $a_row = tx_pttools_div::iconvArray($a_row, $this->gsaCharset, $this->siteCharset);
            }
            $a_result[] = $a_row['ILIEFNR'];
        }
        $this->gsaDbObj->sql_free_result($res);
        
        trace($a_result);
        return $a_result;
        
    }  
    
    /**
     * Returns the UID of an article (specified by article number) from the GSA database.
     *
     * @param   string      article number of the article to get its uid     
     * @return  integer     UID of the article from the GSA database (GSA database field "ARTIKEL.NUMMER")
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-09-27
     */
    public function selectUidFromArtNo($artNo) {
        
        // query preparation
        $select  = 'art.NUMMER';
        $from    = $this->getTableName('ARTIKEL').' art';
        $where   = 'ARTNR = '.$this->gsaDbObj->fullQuoteStr($artNo, $from);
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
        
        trace((integer)$a_row['NUMMER']); 
        return (integer)$a_row['NUMMER'];
        
    }
    
    /**
     * Registers a given transaction volume (ERP: "Umsatz") in the article record of the GSA database ('multiuser-safe')
     *
     * @param   integer     UID of the article to update (GSA database field "ARTIKEL.NUMMER")
     * @param   double      net amount of transaction volume (ERP: "Umsatz") to register
     * @return  boolean     TRUE on success or FALSE on error
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-22
     */
    public function updateTransactionVolume($gsaArticleUid, $amount) {
        
        $updateFieldsArr = array();
        $amount = (double)$amount; // make sure amount is of type double
        
        // query preparation
        $table   = $this->getTableName('ARTIKEL');
        $where   = 'NUMMER = '.intval($gsaArticleUid);
        $updateFieldsArr['UMSATZ']        = 'IFNULL(UMSATZ, 0) + '.$amount;
        $updateFieldsArr['LETZTERUMSATZ'] = 'NOW()';        // ARTIKEL.LETZTERUMSATZ format example: '2007-06-19 13:00:25'
        $noQuoteFields                    = array('UMSATZ', 'LETZTERUMSATZ');
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_UPDATEquery($table, $where, $updateFieldsArr, $noQuoteFields);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        
        trace($res); 
        return $res;
        
    }
    
    
    
    
    /***************************************************************************
     *   TYPO3 DB RELATED METHODS
     **************************************************************************/
     
    /**
     * Returns data of an article relation record (specified by GSA ARTIKEL.NUMMER) from the TYPO3 database
     *
     * @param   integer     UID of the article record in the GSA database (ARTIKEL.NUMMER)
     * @global  object      $GLOBALS['TYPO3_DB']: t3lib_db Object (TYPO3 DB API)
     * @global  object      $GLOBALS['TSFE']->cObj: tslib_cObj Object (TYPO3 content object)
     * @return  mixed       array of the specified order record  on success, FALSE otherwise
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-03
     */
    public function selectArticleRelationData($articleId) {
        
        // query preparation
        $select  = 'max_amount, '.
                   'exclusion_articles, '.
                   'required_articles, '.
                   'related_articles, '.
                   'bundled_articles, '.
                   'appl_spec_uid, '.
                   'appl_identifier';
        $from    = 'tx_ptgsashop_artrel';
        $where   = 'gsa_art_nummer = '.intval($articleId).' '. 
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
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_articleAccessor.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_articleAccessor.php']);
}

?>