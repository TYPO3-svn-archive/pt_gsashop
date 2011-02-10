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
 * Database accessor class for orders of the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_orderAccessor.php,v 1.57 2008/11/28 09:34:58 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2005-11-25
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
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_iSingleton.php'; // interface for Singleton design pattern
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general helper library class
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_gsansch.php';  // combined GSA/TYPO3 address class
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_paymentMethod.php';// combined GSA/TYPO3 payment method class



/**
 *  Database accessor class for orders
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2005-11-25
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_orderAccessor implements tx_pttools_iSingleton {
    
    // TODO: (Fabrizio) one accessor for each object! (articles, addresses,...)
    
    /**
     * Properties
     */
    private static $uniqueInstance = NULL; // (tx_ptgsashop_orderAccessor object) Singleton unique instance
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
    
    /**
     * Returns a unique instance (Singleton) of the object. Use this method instead of the private/protected class constructor.
     *
     * @param   void
     * @return  tx_ptgsashop_orderAccessor      unique instance of the object (Singleton) 
     * @global     
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-11-25
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
     * Inserts a new order record into the TYPO3 order archive database
     *
     * @param   tx_ptgsashop_order      object of type tx_ptgsashop_order containing the data to insert
     * @param   integer     ID of the TYPO3 page initiating this insert
     * @param   integer     (optional) uid of the record. 
     * @return  integer     ID of the inserted record
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-12-23
     */
    public function insertOrder(tx_ptgsashop_order $orderObj, $pid, $uid=NULL) {
        
        $insertFieldsArr = array();
        
        // query preparation
        $table = 'tx_ptgsashop_orders';
        if (!is_null($uid)) {
            $insertFieldsArr['uid']         = $uid;    
        }
        $insertFieldsArr['pid']             = $pid;
        $insertFieldsArr['tstamp']          = time();
        $insertFieldsArr['crdate']          = time();
        $insertFieldsArr['order_timestamp'] = $orderObj->get_timestamp();
        $insertFieldsArr['is_net']          = $orderObj->get_isNet();
        $insertFieldsArr['is_taxfree']      = $orderObj->get_isTaxFree();
        $insertFieldsArr['is_tc_acc']       = $orderObj->get_termsCondAccepted();
        $insertFieldsArr['is_wd_acc']       = $orderObj->get_withdrawalAccepted();
        $insertFieldsArr['is_mult_del']     = $orderObj->get_isMultDeliveries();
        $insertFieldsArr['fe_cruser_id']    = $orderObj->get_feCrUserId();
        if (is_object($orderObj->get_applSpecOrderDataObj())){
            $insertFieldsArr['applSpecData']      = $orderObj->get_applSpecOrderDataObj()->getDataAsString();
            $insertFieldsArr['applSpecDataClass'] = get_class($orderObj->get_applSpecOrderDataObj()); // needed for factory method
        }
        
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
     
    /**
     * Inserts a new delivery record related to a specified archived order record into the TYPO3 order archive database
     *
     * @param   tx_ptgsashop_delivery      object of type tx_ptgsashop_delivery containing the data to insert
     * @param   integer     ID of the TYPO3 page initiating this insert
     * @param   integer     ID of the TYPO3 FE user initiating this insert
     * @param   integer     ID of the related order record in the TYPO3 order archive database
     * @return  integer     ID of the inserted record
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-29 
     */
    public function insertOrdersDelivery(tx_ptgsashop_delivery $deliveryObj, $pid, $feUserId, $orderRecordId) {
        
        $insertFieldsArr = array();
        
        // query preparation
        $table = 'tx_ptgsashop_orders_deliveries';
        $insertFieldsArr['pid']             = $pid;
        $insertFieldsArr['tstamp']          = time();
        $insertFieldsArr['crdate']          = time();
        $insertFieldsArr['fe_cruser_id']    = $feUserId;
        $insertFieldsArr['orders_id']       = $orderRecordId;
        $insertFieldsArr['is_orderbase_net']        = $deliveryObj->get_orderBaseIsNet();
        $insertFieldsArr['is_orderbase_taxfree']    = $deliveryObj->get_orderBaseIsTaxFree();
        $insertFieldsArr['is_physical']             = $deliveryObj->getDeliveryIsPhysical();
        
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
     
    /**
     * Inserts a new address record related to a specified archived order record (billing address) or delivery record (shipping address) into the TYPO3 order archive database
     *
     * @param   tx_ptgsauserreg_gsansch      object of type tx_ptgsauserreg_gsansch containing the data to insert
     * @param   integer     ID of the TYPO3 page initiating this insert
     * @param   integer     ID of the TYPO3 FE user initiating this insert
     * @param   integer     ID of the related order record in the TYPO3 order archive database
     * @param   integer     required for shipping addresses only: ID of the related delivery record in the TYPO3 order archive database (set to 0 if address to insert is a billing address!)
     * @return  integer     ID of the inserted record
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-29 
     */
    public function insertOrdersAddress(tx_ptgsauserreg_gsansch $addressObj, $pid, $feUserId, $orderRecordId, $deliveryRecordId) {
        
        $insertFieldsArr = array();
        
        // query preparation
        $table = 'tx_ptgsashop_orders_addresses';
        $insertFieldsArr['pid']             = $pid;
        $insertFieldsArr['tstamp']          = time();
        $insertFieldsArr['crdate']          = time();
        $insertFieldsArr['fe_cruser_id']    = $feUserId;
        $insertFieldsArr['orders_id']       = $orderRecordId;
        $insertFieldsArr['deliveries_id']   = $deliveryRecordId;
        $insertFieldsArr['post1']           = $addressObj->get_post1();
        $insertFieldsArr['post2']           = $addressObj->get_post2();
        $insertFieldsArr['post3']           = $addressObj->get_post3();
        $insertFieldsArr['post4']           = $addressObj->get_post4();
        $insertFieldsArr['post5']           = $addressObj->get_post5();
        $insertFieldsArr['post6']           = $addressObj->get_post6();
        $insertFieldsArr['post7']           = $addressObj->get_post7();
        $insertFieldsArr['country']         = $addressObj->get_country();
        $insertFieldsArr['gsa_id_adresse']  = $addressObj->get_gsauid(); 
        $insertFieldsArr['gsa_id_ansch']    = $addressObj->get_anschid(); // 0 means the used address is retrieved from the customer's master data record
        $insertFieldsArr['t3_id_ansch']     = $addressObj->get_uid();
        
        // needed for IRRE (added by ry44, 2008-06-03)
        $insertFieldsArr['irreParentTable'] = ($deliveryRecordId == 0) ? 'tx_ptgsashop_orders' : 'tx_ptgsashop_orders_deliveries';
        
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
    
    /** 
     * Updates an order related address record in the TYPO3 order archive database
     *
     * @param   tx_ptgsauserreg_gsansch      updated address to use its data, object of type tx_ptgsauserreg_gsansch
     * @param   integer     ID of the related order record in the TYPO3 order archive database (used in combination with $deliveryRecordId)
     * @param   integer     ID of the related delivery record in the TYPO3 order archive database (used in combination with $orderRecordId)
     * @return  boolean     TRUE on success or FALSE on error
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-04 
     */
    public function updateOrdersAddress(tx_ptgsauserreg_gsansch $addressObj, $orderRecordId, $deliveryRecordId) {
        
        $updateFieldsArr = array();
        
        // query preparation
        $table = 'tx_ptgsashop_orders_addresses';
        
        $updateFieldsArr['tstamp']          = time();
        $updateFieldsArr['post1']           = $addressObj->get_post1();
        $updateFieldsArr['post2']           = $addressObj->get_post2();
        $updateFieldsArr['post3']           = $addressObj->get_post3();
        $updateFieldsArr['post4']           = $addressObj->get_post4();
        $updateFieldsArr['post5']           = $addressObj->get_post5();
        $updateFieldsArr['post6']           = $addressObj->get_post6();
        $updateFieldsArr['post7']           = $addressObj->get_post7();
        $updateFieldsArr['country']         = $addressObj->get_country();
            // gsa_id_adresse, gsa_id_ansch and t3_id_ansch should not be re-written( according to wz 2007-06-04)
        
        $where = 'orders_id = '.intval($orderRecordId).' '.
                 'AND deliveries_id = '.intval($deliveryRecordId);
        
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $updateFieldsArr);
        trace(tx_pttools_div::returnLastBuiltInsertQuery($GLOBALS['TYPO3_DB'], $table, $updateFieldsArr));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        
        trace($res); 
        return $res;
        
    }
     
    /**
     * Inserts a new article record related to a specified delivery record of an archived order into the TYPO3 order archive database
     *
     * @param   tx_ptgsashop_article      object of type tx_ptgsashop_article containing the data to insert
     * @param   integer     ID of the TYPO3 page initiating this insert
     * @param   integer     ID of the TYPO3 FE user initiating this insert
     * @param   integer     ID of the related order record in the TYPO3 order archive database
     * @param   integer     ID of the related delivery record in the TYPO3 order archive database
     * @param   boolean     (optional) flag wether the underlying order is tax free (default:0)
     * @return  integer     ID of the inserted record
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-29 
     */
    public function insertOrdersArticle(tx_ptgsashop_article $articleObj, $pid, $feUserId, $orderRecordId, $deliveryRecordId, $isTaxFreeOrder=0) {
        
        $insertFieldsArr = array();
        
        // query preparation
        $table = 'tx_ptgsashop_orders_articles';
        $insertFieldsArr['pid']             = $pid;
        $insertFieldsArr['tstamp']          = time();
        $insertFieldsArr['crdate']          = time();
        $insertFieldsArr['fe_cruser_id']    = $feUserId;
        $insertFieldsArr['orders_id']       = $orderRecordId;
        $insertFieldsArr['deliveries_id']   = $deliveryRecordId;
        $insertFieldsArr['gsa_id_artikel']  = $articleObj->get_id();
        $insertFieldsArr['quantity']        = $articleObj->get_quantity();
        $insertFieldsArr['art_no']          = $articleObj->get_artNo();
        $insertFieldsArr['description']     = $articleObj->get_description();
        $insertFieldsArr['price_calc_qty']  = $articleObj->get_priceCalcQty();
        $insertFieldsArr['price_category']  = $articleObj->get_priceCategory();
        $insertFieldsArr['date_string']     = $articleObj->get_date();
        $insertFieldsArr['tax_code']        = $articleObj->get_taxCodeInland();
        $insertFieldsArr['tax_percentage']  = $articleObj->getTaxRate();
        $insertFieldsArr['fixedCost1']      = $articleObj->get_fixedCost1();
        $insertFieldsArr['fixedCost2']      = $articleObj->get_fixedCost2();
        $insertFieldsArr['price_net']       = $articleObj->getDisplayPrice(1, 0); // IMPORTANT: 2nd param 0 = do not round (database has to be filled with precision of 6 decimals places)!!
        $insertFieldsArr['price_gross']     = $articleObj->getDisplayPrice($isTaxFreeOrder); // get gross for non tax-free orders, get net for tax-free orders (price_net = price_gross in this case)
        
        $insertFieldsArr['userField01']     = $articleObj->get_userField01();
        $insertFieldsArr['userField02']     = $articleObj->get_userField02();
        $insertFieldsArr['userField03']     = $articleObj->get_userField03();
        $insertFieldsArr['userField04']     = $articleObj->get_userField04();
        $insertFieldsArr['userField05']     = $articleObj->get_userField05();
        $insertFieldsArr['userField06']     = $articleObj->get_userField06();
        $insertFieldsArr['userField07']     = $articleObj->get_userField07();
        $insertFieldsArr['userField08']     = $articleObj->get_userField08();
        
        $insertFieldsArr['artrelApplSpecUid'] = $articleObj->get_artrelApplSpecUid();
        $insertFieldsArr['artrelApplIdentifier'] = $articleObj->get_artrelApplIdentifier();
        
        if (is_object($articleObj->get_applSpecDataObj())){
            $insertFieldsArr['applSpecData']      = $articleObj->get_applSpecDataObj()->getDataAsString();
            $insertFieldsArr['applSpecDataClass'] = get_class($articleObj->get_applSpecDataObj()); // needed for factory method
        }
        
        
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $insertFieldsArr);
        trace(tx_pttools_div::returnLastBuiltInsertQuery($GLOBALS['TYPO3_DB'], $table, $insertFieldsArr));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        $lastInsertedId = $GLOBALS['TYPO3_DB']->sql_insert_id();
        
        $articleObj->set_orderArchiveUid($lastInsertedId);
        
        trace($lastInsertedId);
        return $lastInsertedId;
        
    }
    
    /**
     * Updates an order related article record in the TYPO3 order archive database
     * 
     * @param   tx_ptgsashop_article      updated article to use its data, object of type tx_ptgsashop_article
     * @param   integer     ID of the related order record in the TYPO3 order archive database (used in combination with $deliveryRecordId)
     * @param   integer     ID of the related delivery record in the TYPO3 order archive database (used in combination with $orderRecordId)
     * @param   boolean     (optional) flag wether the underlying order is tax free (default:0)
     * @return  boolean     TRUE on success or FALSE on error
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-04
     */
    public function updateOrdersArticle(tx_ptgsashop_article $articleObj, $orderRecordId, $deliveryRecordId, $isTaxFreeOrder=0) {
        
        $updateFieldsArrFieldsArr = array();
        
        // query preparation
        $table = 'tx_ptgsashop_orders_articles';
        
        $updateFieldsArr['tstamp']          = time();
        $updateFieldsArr['quantity']        = $articleObj->get_quantity();
        $updateFieldsArr['art_no']          = $articleObj->get_artNo();
        $updateFieldsArr['description']     = $articleObj->get_description();
        $updateFieldsArr['price_calc_qty']  = $articleObj->get_priceCalcQty();
        $updateFieldsArr['price_category']  = $articleObj->get_priceCategory();
        $updateFieldsArr['date_string']     = $articleObj->get_date();
        $updateFieldsArr['tax_code']        = $articleObj->get_taxCodeInland();
        $updateFieldsArr['tax_percentage']  = $articleObj->getTaxRate();
        $updateFieldsArr['fixedCost1']      = $articleObj->get_fixedCost1();
        $updateFieldsArr['fixedCost2']      = $articleObj->get_fixedCost2();
        $updateFieldsArr['price_net']       = $articleObj->getDisplayPrice(1, 0); // IMPORTANT: 2nd param 0 = do not round (database has to be filled with precision of 6 decimals places)!!
        $updateFieldsArr['price_gross']     = $articleObj->getDisplayPrice($isTaxFreeOrder); // get gross for non tax-free orders, get net for tax-free orders (price_net = price_gross in this case)
        
        $updateFieldsArr['userField01']     = $articleObj->get_userField01();
        $updateFieldsArr['userField02']     = $articleObj->get_userField02();
        $updateFieldsArr['userField03']     = $articleObj->get_userField03();
        $updateFieldsArr['userField04']     = $articleObj->get_userField04();
        $updateFieldsArr['userField05']     = $articleObj->get_userField05();
        $updateFieldsArr['userField06']     = $articleObj->get_userField06();
        $updateFieldsArr['userField07']     = $articleObj->get_userField07();
        $updateFieldsArr['userField08']     = $articleObj->get_userField08();
        
        $updateFieldsArr['artrelApplSpecUid'] = $articleObj->get_artrelApplSpecUid();
        $updateFieldsArr['artrelApplIdentifier'] = $articleObj->get_artrelApplIdentifier();
        
        if (is_object($articleObj->get_applSpecDataObj())) {
            $updateFieldsArr['applSpecData']    = $articleObj->get_applSpecDataObj()->getDataAsString();
            $updateFieldsArr['applSpecDataClass'] = get_class($articleObj->get_applSpecDataObj()); // needed for factory method
        }
        
        $where = 'orders_id = '.intval($orderRecordId).' '.
                 'AND deliveries_id = '.intval($deliveryRecordId).' '.
                 'AND gsa_id_artikel = '.intval($articleObj->get_id());
        
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $updateFieldsArr);
        trace(tx_pttools_div::returnLastBuiltInsertQuery($GLOBALS['TYPO3_DB'], $table, $updateFieldsArr));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        
        trace($res); 
        return $res;
        
    }
     
    /**
     * Inserts a new dispatch cost record related to a specified delivery record of an archived order into the TYPO3 order archive database
     *
     * @param   tx_ptgsashop_delivery      object of type tx_ptgsashop_delivery containing dispatchObj to insert
     * @param   integer     ID of the TYPO3 page initiating this insert
     * @param   integer     ID of the TYPO3 FE user initiating this insert
     * @param   integer     ID of the related order record in the TYPO3 order archive database
     * @param   integer     ID of the related delivery record in the TYPO3 order archive database
     * @param   double      total sum of the articles to calculate the dispatch cost for
     * @param   boolean     flag wether the dispatch cost should be inserted as net price: 0=gross sum, 1=net sum
     * @return  integer     ID of the inserted record
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-29 
     */
    public function insertOrdersDispatchCost(tx_ptgsashop_delivery $delObj, $pid, $feUserId, $orderRecordId, $deliveryRecordId, $getNetSum) {
        
        $insertFieldsArr = array();
        
        $dispatchObj = $delObj->get_dispatchObj();
        
        // query preparation
        $table = 'tx_ptgsashop_orders_dispatchcost';
        $insertFieldsArr['pid']             = $pid;
        $insertFieldsArr['tstamp']          = time();
        $insertFieldsArr['crdate']          = time();
        $insertFieldsArr['fe_cruser_id']    = $feUserId;
        $insertFieldsArr['orders_id']       = $orderRecordId;
        $insertFieldsArr['deliveries_id']   = $deliveryRecordId;
        $insertFieldsArr['cost_type_name']  = $dispatchObj->get_costTypeName();
        $insertFieldsArr['cost_comp_1']     = $dispatchObj->get_costComp1();
        $insertFieldsArr['cost_comp_2']     = $dispatchObj->get_costComp2();
        $insertFieldsArr['cost_comp_3']     = $dispatchObj->get_costComp3();
        $insertFieldsArr['cost_comp_4']     = $dispatchObj->get_costComp4();
        $insertFieldsArr['allowance_comp_1']= $dispatchObj->get_allowanceComp1();
        $insertFieldsArr['allowance_comp_2']= $dispatchObj->get_allowanceComp2();
        $insertFieldsArr['allowance_comp_3']= $dispatchObj->get_allowanceComp3();
        $insertFieldsArr['allowance_comp_4']= $dispatchObj->get_allowanceComp4();
        $insertFieldsArr['cost_tax_code']   = $dispatchObj->get_costTaxCode();
        $insertFieldsArr['tax_percentage']  = $dispatchObj->getTaxRate();
        $insertFieldsArr['dispatch_cost']   = $delObj->getDeliveryDispatchCost($getNetSum);
        
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
    
    /**
     * Updates an order related dispatch cost record in the TYPO3 order archive database
     * 
     * @param   tx_ptgsashop_delivery      updated delivery to use its data, object of type tx_ptgsashop_deleivery
     * @param   integer     ID of the related order record in the TYPO3 order archive database (used in combination with $gsaArticleId)
     * @param   boolean     flag wether the dispatch cost should be inserted as net price: 0=gross sum, 1=net sum
     * @return  boolean     TRUE on success or FALSE on error
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-04
     */
    public function updateOrdersDispatchCost(tx_ptgsashop_delivery $delObj, $orderRecordId,$getNetSum) {
        
        $updateFieldsArr = array();
        
        $dispatchObj = $delObj->get_dispatchObj();
        // query preparation
        $table = 'tx_ptgsashop_orders_dispatchcost';
        
        $updateFieldsArr['tstamp']          = time();
        $updateFieldsArr['cost_type_name']  = $dispatchObj->get_costTypeName();
        $updateFieldsArr['cost_comp_1']     = $dispatchObj->get_costComp1();
        $updateFieldsArr['cost_comp_2']     = $dispatchObj->get_costComp2();
        $updateFieldsArr['cost_comp_3']     = $dispatchObj->get_costComp3();
        $updateFieldsArr['cost_comp_4']     = $dispatchObj->get_costComp4();
        $updateFieldsArr['allowance_comp_1']= $dispatchObj->get_allowanceComp1();
        $updateFieldsArr['allowance_comp_2']= $dispatchObj->get_allowanceComp2();
        $updateFieldsArr['allowance_comp_3']= $dispatchObj->get_allowanceComp3();
        $updateFieldsArr['allowance_comp_4']= $dispatchObj->get_allowanceComp4();
        $updateFieldsArr['cost_tax_code']   = $dispatchObj->get_costTaxCode();
        $updateFieldsArr['tax_percentage']  = $dispatchObj->getTaxRate();
        $updateFieldsArr['dispatch_cost']   = $delObj->getDeliveryDispatchCost($getNetSum);
        
        $where =  'orders_id = '.intval($orderRecordId).' '.
                  'AND deliveries_id = '.intval($delObj->get_orderArchiveId());
        
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $updateFieldsArr);
        trace(tx_pttools_div::returnLastBuiltInsertQuery($GLOBALS['TYPO3_DB'], $table, $updateFieldsArr));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        
        trace($res); 
        return $res;
        
    }
     
    /**
     * Inserts a new payment method record related to a specified archived order record into the TYPO3 order archive database
     *
     * @param   tx_ptgsauserreg_paymentMethod      object of type tx_ptgsauserreg_paymentMethod containing the data to insert
     * @param   integer     ID of the TYPO3 page initiating this insert
     * @param   integer     ID of the TYPO3 FE user initiating this insert
     * @param   integer     ID of the related order record in the TYPO3 order archive database
     * @return  integer     ID of the inserted record
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-03-28 
     */
    public function insertOrdersPaymentMethod(tx_ptgsauserreg_paymentMethod $paymentMethodObj, $pid, $feUserId, $orderRecordId) {
        
        $insertFieldsArr = array();
        
        // query preparation
        $table = 'tx_ptgsashop_orders_paymentmethods';
        $insertFieldsArr['pid']             = $pid;
        $insertFieldsArr['tstamp']          = time();
        $insertFieldsArr['crdate']          = time();
        $insertFieldsArr['fe_cruser_id']    = $feUserId;
        $insertFieldsArr['orders_id']       = $orderRecordId;
        $insertFieldsArr['method']              = $paymentMethodObj->get_method();
        $insertFieldsArr['epayment_success']    = $paymentMethodObj->get_epaymentSuccess();
        $insertFieldsArr['epayment_trans_id']   = $paymentMethodObj->get_epaymentTransId();
        $insertFieldsArr['epayment_ref_id']     = $paymentMethodObj->get_epaymentRefId();
        $insertFieldsArr['epayment_short_id']   = $paymentMethodObj->get_epaymentShortId();
        $insertFieldsArr['bank_account_holder'] = $paymentMethodObj->get_bankAccountHolder();
        $insertFieldsArr['bank_name']           = $paymentMethodObj->get_bankName();
        $insertFieldsArr['bank_account_number'] = $paymentMethodObj->get_bankAccountNo();
        $insertFieldsArr['bank_code']           = $paymentMethodObj->get_bankCode();
        $insertFieldsArr['bank_bic']            = $paymentMethodObj->get_bankBic();
        $insertFieldsArr['bank_iban']           = $paymentMethodObj->get_bankIban();
        $insertFieldsArr['gsa_dta_acc_no']      = $paymentMethodObj->get_gsaDtaAccountIndex();
        
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
     
    /**
     * Updates an order related payment method record in the TYPO3 order archive database
     *
     * @param   tx_ptgsauserreg_paymentMethod      updated payment method to use its data, object of type tx_ptgsauserreg_paymentMethod
     * @param   integer     ID of the related order record in the TYPO3 order archive database
     * @return  boolean     TRUE on success or FALSE on error
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-04 
     */
    public function updateOrdersPaymentMethod(tx_ptgsauserreg_paymentMethod $paymentMethodObj, $orderRecordId) {
        
        $updateFieldsArr = array();
        
        // query preparation
        $table = 'tx_ptgsashop_orders_paymentmethods';
        
        $updateFieldsArr['tstamp']          = time();
        $updateFieldsArr['method']              = $paymentMethodObj->get_method();
        $updateFieldsArr['epayment_success']    = $paymentMethodObj->get_epaymentSuccess();
        $updateFieldsArr['epayment_trans_id']   = $paymentMethodObj->get_epaymentTransId();
        $updateFieldsArr['epayment_ref_id']     = $paymentMethodObj->get_epaymentRefId();
        $updateFieldsArr['epayment_short_id']   = $paymentMethodObj->get_epaymentShortId();
        $updateFieldsArr['bank_account_holder'] = $paymentMethodObj->get_bankAccountHolder();
        $updateFieldsArr['bank_name']           = $paymentMethodObj->get_bankName();
        $updateFieldsArr['bank_account_number'] = $paymentMethodObj->get_bankAccountNo();
        $updateFieldsArr['bank_code']           = $paymentMethodObj->get_bankCode();
        $updateFieldsArr['bank_bic']            = $paymentMethodObj->get_bankBic();
        $updateFieldsArr['bank_iban']           = $paymentMethodObj->get_bankIban();
        $updateFieldsArr['gsa_dta_acc_no']      = $paymentMethodObj->get_gsaDtaAccountIndex();
        
        $where = 'orders_id = '.intval($orderRecordId);
        
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $updateFieldsArr);
        trace(tx_pttools_div::returnLastBuiltInsertQuery($GLOBALS['TYPO3_DB'], $table, $updateFieldsArr));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        
        trace($res); 
        return $res;
        
    }
     
    /**
     * Updates the credit card epayment transaction ID and optionally the reference ID/short ID of an archived order record (specified by related ERP document number/"Vorgangsnummer") in the TYPO3 order archive database
     *
     * @param   string      related ERP document number ("Vorgangsnummer") of the saved order confirmation document in the ERP system
     * @param   boolean     sucess status of the epayment transaction
     * @param   string      credit card epayment transaction ID (used for all epayments trials)
     * @param   string      (optional) credit card epayment reference ID (retrieved from payment server)
     * @param   string      (optional) credit card epayment short ID (retrieved from payment server)
     * @global  object      $GLOBALS['TYPO3_DB']: t3lib_db Object (TYPO3 DB API)
     * @return  boolean     TRUE on success or FALSE on error
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-09-04 
     */
    public function updateEpaymentId($relatedErpDocNo, $epaymentSuccess, $epaymentTransactionId, $epaymentReferenceId='', $epaymentShortId='') {
        
        $updateFieldsArr = array();
        
        // query preparation
        $table           = 'tx_ptgsashop_orders_paymentmethods p '.
                           'JOIN tx_ptgsashop_order_wrappers w ON p.orders_id = w.orders_id ';
        $where           = 'w.related_doc_no = '.$GLOBALS['TYPO3_DB']->fullQuoteStr($relatedErpDocNo, 'tx_ptgsashop_order_wrappers');
        $updateFieldsArr['p.epayment_success'] = $epaymentSuccess;
        $updateFieldsArr['p.epayment_trans_id'] = $epaymentTransactionId;
        if (!empty($epaymentReferenceId)) {
            $updateFieldsArr['p.epayment_ref_id'] = $epaymentReferenceId;
        }
        if (!empty($epaymentShortId)) {
            $updateFieldsArr['p.epayment_short_id'] = $epaymentShortId;
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
     * Returns an array with the archived data of an order (specified by UID) from the TYPO3 order archive database
     * 
     * @param   integer     ID of the order record in the TYPO3 order archive database
     * @return  array       associative array with archived data of the specified order
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-04
     */
    public function selectOrder($orderRecordId) {

        // query preparation
        $select  = 'uid,
                    pid,
                    tstamp,
                    crdate,
                    cruser_id,
                    deleted,
                    hidden,
                    order_timestamp,
                    is_net,
                    is_taxfree,
                    is_tc_acc,
                    is_wd_acc,
                    is_mult_del,
                    fe_cruser_id,
                    applSpecData, 
                    applSpecDataClass';
                    // TODO: (Fabrizio) do we really need all fields?
        $from    = 'tx_ptgsashop_orders';
        $where   = 'uid = '.intval($orderRecordId);
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

        // if enabled, do charset conversion of all non-binary string data
        if ($this->charsetConvEnabled == 1) {
            $a_row = tx_pttools_div::iconvArray($a_row, $this->gsaCharset, $this->siteCharset);
        }

        trace($a_row);
        return $a_row;
        
    }

    /**
     * Returns an 2D array with the archived data of all deliveries of an order (specified by UID) from the TYPO3 order archive database
     * 
     * @param   integer     ID of the order record in the TYPO3 order archive database
     * @return  array       twodimensional array with data of all archived deliveries of the specified order
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-04
     */
    public function selectOrdersDeliveryList($orderRecordId) {

        // query preparation
        $select  = 'uid,
                    pid,
                    tstamp,
                    crdate,
                    cruser_id,
                    deleted,
                    hidden,
                    orders_id,
                    is_orderbase_net,
                    is_orderbase_taxfree,
                    is_physical,
                    fe_cruser_id';
                    // TODO: (Fabrizio) do we really need all fields?
        $from    = 'tx_ptgsashop_orders_deliveries';
        $where   = 'orders_id = '.intval($orderRecordId);
        $groupBy = '';
        $orderBy = '';
        $limit   = '';

        // exec query using TYPO3 DB API
        $a_rows = array();
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        trace(tx_pttools_div::returnLastBuiltSelectQuery($GLOBALS['TYPO3_DB'], $select, $from, $where, $groupBy, $orderBy, $limit));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }

        while ($item = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            // if enabled, do charset conversion of all non-binary string data
            $a_rows[] = ($this->charsetConvEnabled == 1 ? tx_pttools_div::iconvArray($item, $this->gsaCharset, $this->siteCharset) : $item);
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);

        trace($a_rows);
        return $a_rows;

    }

    
    /**
     * Returns an array with the archived address data of a specified delivery from the TYPO3 order archive database
     * 
     * @param   integer     ID of the related order record in the TYPO3 order archive database
     * @param   integer     ID of the related delivery record in the TYPO3 order archive database
     * @return  array       associative array with archived address data
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-04
     */
    public function selectOrdersAddress($orderRecordId, $deliveryRecordId) {

        // query preparation

        $select  = 'uid,
                    pid,
                    tstamp,
                    crdate,
                    cruser_id,
                    deleted,
                    hidden,
                    orders_id,
                    deliveries_id,
                    post1,
                    post2,
                    post3,
                    post4,
                    post5,
                    post6,
                    post7,
                    country, 
                    gsa_id_adresse,
                    gsa_id_ansch,
                    fe_cruser_id,
                    t3_id_ansch';
                    // TODO: (Fabrizio) do we really need all fields?
        $from    = 'tx_ptgsashop_orders_addresses';
        $where   = 'orders_id = '.intval($orderRecordId).' ';
        $where  .= 'AND deliveries_id = '.intval($deliveryRecordId);
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

        // if enabled, do charset conversion of all non-binary string data
        if ($this->charsetConvEnabled == 1) {
            $a_row = tx_pttools_div::iconvArray($a_row, $this->gsaCharset, $this->siteCharset);
        }

        trace($a_row);
        return $a_row;
    }
    
    /**
     * Returns an 2D array with the archived data of all articles of a delivery (specified by UID) from the TYPO3 order archive database
     * 
     * @param   integer     ID of the related order record in the TYPO3 order archive database
     * @param   integer     ID of the delivery record (to get its articles) in the TYPO3 order archive database
     * @return  array       twodimensional array with data of all archived deliveries of the specified order
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-04
     */
    public function selectOrdersArticleList($orderRecordId, $deliveryRecordId) {

        // query preparation
        $select  = 'uid,
                    pid,
                    tstamp,
                    crdate,
                    cruser_id,
                    deleted,
                    hidden,
                    fe_cruser_id, 
                    orders_id,
                    deliveries_id,
                    gsa_id_artikel,
                    quantity,
                    art_no,
                    description,
                    price_calc_qty,
                    price_category,
                    date_string,
                    tax_code, 
                    tax_percentage, 
                    fixedCost1, 
                    fixedCost2, 
                    price_net,
                    price_gross,
                    userField01, 
                    userField02, 
                    userField03, 
                    userField04, 
                    userField05, 
                    userField06, 
                    userField07, 
                    userField08, 
                    applSpecData, 
                    applSpecDataClass, 
                    artrelApplSpecUid,
                    artrelApplIdentifier';
                    // TODO: (Fabrizio) do we really need all fields?
                    
        $from    = 'tx_ptgsashop_orders_articles';
        $where   = 'orders_id = '.intval($orderRecordId).' ';
        $where  .= 'AND deliveries_id = '.intval($deliveryRecordId);
        $groupBy = '';
        $orderBy = '';
        $limit   = '';

        // exec query using TYPO3 DB API
        $a_rows = array();
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        trace(tx_pttools_div::returnLastBuiltSelectQuery($GLOBALS['TYPO3_DB'], $select, $from, $where, $groupBy, $orderBy, $limit));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        
        while ($item = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
            // if enabled, do charset conversion of all non-binary string data
            $a_rows[] = ($this->charsetConvEnabled == 1 ? tx_pttools_div::iconvArray($item, $this->gsaCharset, $this->siteCharset) : $item);
        }
        
        $GLOBALS['TYPO3_DB']->sql_free_result($res);

        trace($a_rows);
        return $a_rows;

    }
    
    /**
     * Returns an array with the archived data of an article (specified by UID) from the TYPO3 order archive database
     * 
     * @param   integer     ID of the article record in the TYPO3 orders-articles archive database table
     * @return  array       associative array with archived data of the specified article
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-04
     */
    public function selectOrdersArticle($articleRecordId) {

        // query preparation
        $select  = 'uid,
                    pid,
                    tstamp,
                    crdate,
                    cruser_id,
                    deleted,
                    hidden,
                    fe_cruser_id, 
                    orders_id,
                    deliveries_id,
                    gsa_id_artikel,
                    quantity,
                    art_no,
                    description,
                    price_calc_qty,
                    price_category,
                    date_string,
                    tax_code,
                    tax_percentage,
                    fixedCost1, 
                    fixedCost2, 
                    price_net,
                    price_gross,
                    userField01, 
                    userField02, 
                    userField03, 
                    userField05, 
                    userField06, 
                    userField07, 
                    userField08, 
                    applSpecData, 
                    applSpecDataClass, 
                    artrelApplSpecUid,
                    artrelApplIdentifier';
                    // TODO: (Fabrizio) do we really need all fields?
                    
        $from    = 'tx_ptgsashop_orders_articles';
        $where   = 'uid = '.intval($articleRecordId);
        $groupBy = '';
        $orderBy = '';
        $limit   = '';

        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        trace(tx_pttools_div::returnLastBuiltSelectQuery($GLOBALS['TYPO3_DB'], $select, $from, $where, $groupBy, $orderBy, $limit));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        $item = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        // if enabled, do charset conversion of all non-binary string data
        $a_row = ($this->charsetConvEnabled == 1 ? tx_pttools_div::iconvArray($item, $this->gsaCharset, $this->siteCharset) : $item);

        $GLOBALS['TYPO3_DB']->sql_free_result($res);

        trace($a_row);
        return $a_row;
        
    }

    /**
     * Returns an array with the archived dispatch cost data of a specified delivery from the TYPO3 order archive database
     * 
     * @param   integer     ID of the related order record in the TYPO3 order archive database
     * @param   integer     ID of the related delivery record in the TYPO3 order archive database
     * @return  array       associative array with archived dispatch cost data
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-04
     */
    public function selectOrdersDispatchCost($orderRecordId, $deliveryRecordId) {

        // query preparation
        $select  = 'uid,
                    pid,
                    tstamp,
                    crdate,
                    cruser_id,
                    deleted,
                    hidden,
                    orders_id,
                    deliveries_id,
                    cost_type_name,
                    cost_comp_1,
                    cost_comp_2,
                    cost_comp_3,
                    cost_comp_4,
                    allowance_comp_1,
                    allowance_comp_2,
                    allowance_comp_3,
                    allowance_comp_4,
                    cost_tax_code';
                    // TODO: (Fabrizio) do we really need all fields?
        $from    = 'tx_ptgsashop_orders_dispatchcost';
        $where   = 'orders_id = '.intval($orderRecordId).' ';
        $where  .= 'AND deliveries_id = '.intval($deliveryRecordId);
        $groupBy = '';
        $orderBy = '';
        $limit   = '';

        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        trace(tx_pttools_div::returnLastBuiltSelectQuery($GLOBALS['TYPO3_DB'], $select, $from, $where, $groupBy, $orderBy, $limit));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        $item = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        // if enabled, do charset conversion of all non-binary string data
        $a_row = ($this->charsetConvEnabled == 1 ? tx_pttools_div::iconvArray($item, $this->gsaCharset, $this->siteCharset) : $item);

        $GLOBALS['TYPO3_DB']->sql_free_result($res);

        trace($a_row);
        return $a_row;
    }
    
    /**
     * Returns an array with the archived payment method data of a specified order from the TYPO3 order archive database
     * 
     * @param   integer     ID of the related order record in the TYPO3 order archive database
     * @return  array       associative array with archived payment method data
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-04
     */
    public function selectOrdersPaymentmethod($orderRecordId) {
        
        // query preparation
        $select  = 'uid,
                    pid,
                    tstamp,
                    crdate,
                    cruser_id,
                    deleted,
                    hidden,
                    fe_cruser_id,
                    orders_id,
                    method,
                    epayment_success,
                    epayment_trans_id,
                    epayment_ref_id,
                    epayment_short_id,
                    bank_account_holder,
                    bank_name,
                    bank_account_number,
                    bank_code,
                    bank_bic,
                    bank_iban,
                    gsa_dta_acc_no';
                    // TODO: (Fabrizio) do we really need all fields?
        $from    = 'tx_ptgsashop_orders_paymentmethods';
        $where   = 'orders_id = '.intval($orderRecordId);
        $groupBy = '';
        $orderBy = '';
        $limit   = '';

        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        trace(tx_pttools_div::returnLastBuiltSelectQuery($GLOBALS['TYPO3_DB'], $select, $from, $where, $groupBy, $orderBy, $limit));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        $item = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        
        // if enabled, do charset conversion of all non-binary string data
        $a_row = ($this->charsetConvEnabled == 1 ? tx_pttools_div::iconvArray($item, $this->gsaCharset, $this->siteCharset) : $item);

        $GLOBALS['TYPO3_DB']->sql_free_result($res);

        trace($a_row);
        return $a_row;    
            
    }
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_orderAccessor.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_orderAccessor.php']);
}

?>