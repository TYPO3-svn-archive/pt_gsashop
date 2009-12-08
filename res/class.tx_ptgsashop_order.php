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
 * Order class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_order.php,v 1.86 2008/11/28 09:34:58 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2005-09-27
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_articleCollection.php';// GSA shop article collection class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_deliveryCollection.php';// GSA Shop delivery collection class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_orderAccessor.php';  // GSA Shop database accessor class for orders
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_orderWrapperAccessor.php';  // GSA Shop database accessor class for order wrappers
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_gsaTransactionHandler.php';  // GSA Shop handler class for GSA transactions
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_address.php';  // GSA Shop specific combined GSA/TYPO3 address class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_paymentMethod.php';  // GSA Shop specific combined GSA/TYPO3 payment method class

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_sessionStorageAdapter.php'; // storage adapter for TYPO3 _browser_ sessions
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_feCustomer.php';  // GSA specific FE customer class
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_gsansch.php';  // combined GSA/TYPO3 address class
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_paymentMethod.php';  // combined GSA/TYPO3 payment method class


/**
 * Order class
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2005-09-27
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_order {
    
    /***************************************************************************
     *   PROPERTIES
     **************************************************************************/
    
    /**
     * @var tx_ptgsashop_deliveryCollection    delivery collection object
    */
    protected $deliveryCollObj;
    
    /**
     * @var tx_ptgsashop_address    billing address object
     */
    protected $billingAddrObj;
    
    /**
     * @var tx_ptgsashop_paymentMethod    payment method object
     */
    protected $paymentMethodObj;

    /**
     * @var int        ID of the FE user who initially placed the order (TYPO3: fe_users.uid)
     */
    protected $feCrUserId = 0;
    
    /**
     * @var int        timestamp of the final order
     */
    protected $timestamp = 0;
    
    /**
     * @var bool    flag whether the order has been based on net prices: 0=gross prices (B2C), 1=net prices (B2B)
     */
    protected $isNet = false; 
    
    /**
     * @var bool    flag whether the order is tax free
     */
    protected $isTaxFree = false; 
    
    /**
     * @var bool    flag whether the order has been/should be distributed to multiple deliveries: 0=false, 1=true
     */
    protected $isMultDeliveries = false; 
    
    /**
     * @var bool    flag whether the orderer has accepted the terms and conditions of the seller
     */
    protected $termsCondAccepted = false; 
    
    /**
     * @var bool    flag whether the orderer has accepted the right of withdrawal notice
     */
    protected $withdrawalAccepted = false;
    
    /**
     * @var tx_ptgsashop_iApplSpecDataObj|NULL    application specific data object for the article
     */
    protected $applSpecOrderDataObj = NULL; 
    
    /**
     * @var int        UID of the main order record in the order archive (0 for a non-archived order)
     */
    protected $orderArchiveId = 0; 
    
    /**
     * @var tx_ptgsashop_iPaymentModifierCollection|NULL    payment modifier collection object (e.g. vouchers, credit balances...)
     */
    protected $paymentModifierCollObj = NULL; 
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
     
    /**
     * Class constructor: sets the object's properties
     *
     * @param   integer       (optional) UID of an archived order to restore. If not set, an "empty" order will be created. (Fabrizio Branca 2007-04)
     * @return  void     
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-27
     */
    public function __construct($archivedOrderId=0) {
        
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        $this->deliveryCollObj = new tx_ptgsashop_deliveryCollection();
        $this->billingAddrObj = new tx_ptgsashop_address(); // tx_ptgsashop_address (instead of parent tx_ptgsauserreg_gsansch) needed here for ->loadFromOrderArchive()
        $this->paymentMethodObj = new tx_ptgsashop_paymentMethod();  // tx_ptgsashop_paymentMethod (instead of parent tx_ptgsauserreg_paymentMethod) needed here for ->loadFromOrderArchive()
        
        $this->feCrUserId = (int)$GLOBALS['TSFE']->fe_user->user['uid'];
        $this->timestamp = time();
        
        // Fabrizio Branca 2007-04
        if ($archivedOrderId > 0) {
            trace('Loading order object with id '.$archivedOrderId.' from archived orders database.');
            $this->loadFromOrderArchive($archivedOrderId);
        }
        
        trace($this);
        
    }
    
    /**
     * Load from order archive: restores the object's properties of data retrieved from the order archive database. This method should be called only directly after new instantiation of the (empty) object.
     * 
     * @param   integer     UID of the order record in the order archive database
     * @return  tx_ptgsashop_order      object of type tx_ptgsashop_order, "filled" with properties from order archive database
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-04
     */
    public function loadFromOrderArchive($archivedOrderId) {
        
        // fetch data from table 'tx_ptgsashop_orders'
        $orderAccessor = tx_ptgsashop_orderAccessor::getInstance();
        $orderData = $orderAccessor->selectOrder($archivedOrderId);
          
        $this->orderArchiveId =     (integer)$archivedOrderId;
        $this->feCrUserId =         (integer)$orderData['fe_cruser_id'];  
        $this->timestamp =          (integer)$orderData['order_timestamp'];
        $this->isNet =              (boolean)$orderData['is_net'];
        $this->isTaxFree =          (boolean)$orderData['is_taxfree'];
        $this->termsCondAccepted =  (boolean)$orderData['is_tc_acc'];
        $this->withdrawalAccepted = (boolean)$orderData['is_wd_acc'];
        $this->isMultDeliveries =   (boolean)$orderData['is_mult_del'];
        
        // rebuild application specific data object from database 
        $applSpecDataClass = (string)$orderData['applSpecDataClass'];
        if (strlen($applSpecDataClass) > 0){
            if (class_exists($applSpecDataClass)){
                $tmp = new $applSpecDataClass(); 
                $tmp->setDataFromString($orderData['applSpecData']);
                $this->set_applSpecOrderDataObj($tmp);
            } else {
                // TODO: (ry44/ry42): implement notification for developer
            }
        }
        
        $this->deliveryCollObj->loadFromOrderArchive($archivedOrderId);
        $this->billingAddrObj->loadFromOrderArchive($archivedOrderId, 0);
        $this->paymentMethodObj->loadFromOrderArchive($archivedOrderId);
                
        return $this;
        
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**  
     * Saves the complete order to the extension's order archive database tables
     *
     * @param   integer     ID of the TYPO3 page initiating the order
     * @param   integer     (optional) uid of the archived orders record
     * @return  integer     ID of the inserted order record
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-30
     */
    public function saveToOrderArchive($pid, $uid =NULL) {
        
        $delRecordIdArr = array();  // (array) array of IDs of the orders deliveries (key=delivery key in order, value= delivery record ID)
        $artSumTotalArr = array();  // (array) array of the orders deliveries total article sums (key=delivery key in order, value= delivery's total article sum)
        $orderAccessor = tx_ptgsashop_orderAccessor::getInstance();
        
        
        // insert main order record (=order wrapper) and update appropriate property
        $this->orderArchiveId = $orderAccessor->insertOrder($this, $pid, $uid);
        
        // insert order's billing address record
        $orderAccessor->insertOrdersAddress($this->billingAddrObj, $pid, $this->feCrUserId, $this->orderArchiveId, 0);
        
        // insert order's payment method record
        $orderAccessor->insertOrdersPaymentMethod($this->paymentMethodObj, $pid, $this->feCrUserId, $this->orderArchiveId);
        
        // insert order's delivery records and their sub-components
        foreach ($this->deliveryCollObj as $delKey=>$delObj) {
            // insert delivery record  
            $delRecordIdArr[$delKey] = $orderAccessor->insertOrdersDelivery($delObj, $pid, $this->feCrUserId, $this->orderArchiveId);
                
            // insert delivery's shipping address record
            $orderAccessor->insertOrdersAddress($delObj->get_shippingAddrObj(), $pid, $this->feCrUserId, $this->orderArchiveId, $delRecordIdArr[$delKey]);
                
            // insert delivery's dispatch cost record
            $artSumTotalArr[$delKey] = $delObj->get_articleCollObj()->getItemsTotal($this->get_isNet());
            $orderAccessor->insertOrdersDispatchCost($delObj->get_dispatchObj(), $pid, $this->feCrUserId, $this->orderArchiveId, $delRecordIdArr[$delKey], $artSumTotalArr[$delKey]);
            
            // insert delivery's article records
            foreach ($delObj->get_articleCollObj() as $articleObj) {
                $orderAccessor->insertOrdersArticle($articleObj, $pid, $this->feCrUserId, $this->orderArchiveId, $delRecordIdArr[$delKey], $this->get_isTaxFree());
            }
        }
        
        // storage of payment modifiers to their appropriate order archive representation
        if (!is_null($this->get_paymentModifierCollObj())) {
            $this->get_paymentModifierCollObj()->storeToOrderArchive($this->orderArchiveId);
        }
        
        return $this->orderArchiveId;
        
    }
    
    /**  
     * Saves the complete order to the enterprise resource planning [abbr.: ERP] database
     *
     * @param   tx_ptgsauserreg_feCustomer      object of type tx_ptgsauserreg_feCustomer: the customer who placed the order
     * @return  string      document number ("Vorgangsnummer") of the saved order document in the ERP system
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-11-25
     */
    public function saveToErpDatabase(tx_ptgsauserreg_feCustomer $customerObj) {
        
        $gsaTransactionHandlerObj = new tx_ptgsashop_gsaTransactionHandler();
        $erpDocNo = $gsaTransactionHandlerObj->processShopOrderTransactionStorage($this, $customerObj);
        
        // storage of payment modifier data to ERP DB
        if (!is_null($this->get_paymentModifierCollObj())) {
            $this->get_paymentModifierCollObj()->storeToErp($erpDocNo);
        }
        
        return $erpDocNo;
        
    }
    
    /**  
     * Updates the archived order data in the extension's order archive database tables (updates change relevant fields data only: addresses, payment data, articles, dispatch cost and address data)
     *
     * @param   void        
     * @return  void        
     * @throws  tx_pttools_exception   if no valid archived order UID is set for the order
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-05-31
     */
    public function updateArchivedOrderData() {
        
        if ($this->orderArchiveId < 1) {
            throw new tx_pttools_exception('Cannot update archived order data', 3, 'Cannot update archived order data due to lacking valid archived order UID in'.__METHOD__);
        }
        
        $orderAccessor = tx_ptgsashop_orderAccessor::getInstance();
        
        // update order's billing address record
        $orderAccessor->updateOrdersAddress($this->billingAddrObj, $this->orderArchiveId, 0);
        
        // update order's payment method record
        $orderAccessor->updateOrdersPaymentMethod($this->paymentMethodObj, $this->orderArchiveId);
        
        // update order's delivery records and their sub-components
        foreach ($this->deliveryCollObj as $delObj) {
            // update delivery's shipping address record
            $orderAccessor->updateOrdersAddress($delObj->get_shippingAddrObj(), $this->orderArchiveId, $delObj->get_orderArchiveId());
                
            // update delivery's dispatch cost record
            $deliveryArticlesSumTotal = $delObj->get_articleCollObj()->getItemsTotal($this->get_isNet());
            $orderAccessor->updateOrdersDispatchCost($delObj->get_dispatchObj(), $this->orderArchiveId, $delObj->get_orderArchiveId(), $deliveryArticlesSumTotal);
            
            // update delivery's article records
            foreach ($delObj->get_articleCollObj() as $articleObj) {
                $orderAccessor->updateOrdersArticle($articleObj, $this->orderArchiveId, $delObj->get_orderArchiveId(), $this->get_isTaxFree());
            }
        }
        
    }
    
    /**  
     * Returns an article collection of all articles in all deliveries of the order
     *
     * @param   void        
     * @return  tx_ptgsashop_articleCollection      object of type tx_ptgsashop_articleCollection, collection of all articles in all deliveries of the order
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-05-30
     */
    public function getCompleteArticleCollection() {
        
       $articleCollectionObj = new tx_ptgsashop_articleCollection(); 
        
        foreach ($this->deliveryCollObj as $deliveryObj) {
            foreach ($deliveryObj->get_articleCollObj() as $articleObj) {
                $articleCollectionObj->addItem($articleObj);
            }
        }
        
        return $articleCollectionObj;
        
    }
    
    /**
     * Returns the date of the order
     *
     * @param   void
     * @return  string      date of the order in format DD.MM.YYYY
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-29
     */
    public function getDate() {
        
        return date('d.m.Y', $this->timestamp);
        
    }
    
    /**
     * Returns the time of the order
     *
     * @param   boolean     (optional) flag whether the the time should be returned including seconds (default=1)
     * @return  string      time of the order in format HH:MM:SS
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-29
     */
    public function getTime($includeSeconds=1) {
        
        return date('H:i'.($includeSeconds == 1 ? ':s' : ''), $this->timestamp);
        
    }
    
    /**
     * Returns a delivery object specified by the array key of the delivery collection's items array
     *
     * @param   void
     * @return  mixed      object of type tx_ptgsashop_delivery, or FALSE if key is not found
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-10-04
     */
    public function getDelivery($key) { 
        
        $key = (int)$key; // Workaround for PHP SPL Bug http://bugs.php.net/bug.php?id=40872&edit=1
        
        if (!$this->deliveryCollObj->getIterator()->offsetExists($key)) {
            return false;
        }
        
        return $this->deliveryCollObj->getIterator()->offsetGet($key);
        
    }
    
    /**
     * Returns the number of deliveries contained in the order's delivery collection
     *
     * @param   void
     * @return  integer     number of deliveries contained in the order's delivery collection
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-29
     */
    public function countDeliveries() { 
        
        return $this->deliveryCollObj->count();
        
    }
    
    /**
     * Removes all empty deliveries (not containing any articles anymore) from order
     *
     * @param   void
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-11-30
     */
     public function removeEmptyDeliveries() {   
         
         // remove all empty deliveries (not containing any articles anymore) from order
        foreach ($this->deliveryCollObj as $delKey=>$delObj) {
            if ($delObj->get_articleCollObj()->count() < 1) {
                $this->get_deliveryCollObj()->deleteItem($delKey);
            }
        }
    
     }
    
    /**
     * Updates the complete order (billing address, payment method and all deliveries) for a given FE customer by retrieving up-to-date data from the database
     *
     * @param   tx_ptgsauserreg_feCustomer      FE customer to update the order for, object of type tx_ptgsauserreg_feCustomer
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-04
     */
    public function updateOrder(tx_ptgsauserreg_feCustomer $customerObj) {
            
        // set up-to-date billing address and payment method
        
        $this->set_billingAddrObj(new tx_ptgsashop_address($customerObj->getValidatedAddressId($this->billingAddrObj->get_uid()), false));  // gets validated (possibly updated/changed) data of address record based on existing uid
        $this->set_paymentMethodObj($customerObj->getPaymentObject());
        
        // update all deliveries (originally capsulated in legacy method updateAllDeliveries())
        foreach ($this->deliveryCollObj as $delKey=>$delObj) {
            $delObj->updateDelivery($customerObj, $this->isNet, $this->isTaxFree);
        }
            
    }
    
    /**
     * Updates the price calculation quantity of a specified article and it's depending retail pricing data in all deliveries containing this article
     *
     * @param   integer   ID of article to update
     * @param   integer   new price calculation quantity of this article
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-25
     */
    public function updateArticlePriceCalcQtyInAllDeliveries($articleId, $newPriceCalcQty) {
        
        foreach ($this->deliveryCollObj as $delKey=>$delObj) {
            if ($delObj->get_articleCollObj()->getItem($articleId) != false) {
                $delObj->get_articleCollObj()->getItem($articleId)->set_priceCalcQty($newPriceCalcQty);
            }
        }
        
    }
        
    /**
     * Returns the address objects of all deliveries within the order's delivery collection
     *
     * @param   void
     * @return  array       numbered array containing the address objects (of type tx_ptgsauserreg_gsansch) of all deliveries
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-10-12
     */
    public function getAllDeliveryAdresses() {
        
        $deliveryAdressesArr = array();
        
        foreach ($this->deliveryCollObj as $delObj) {
            $deliveryAdressesArr[] = $delObj->get_shippingAddrObj();
        }
        
        return $deliveryAdressesArr;
        
    }
    
    /**
     * Returns the total number of articles contained in the order
     *
     * @param   void
     * @return  integer     total number of articles contained in the order
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-30
     */
    public function countArticlesTotal() { 
        
        $articleCountTotal = 0;
        
        foreach ($this->deliveryCollObj as $delKey=>$delObj) {
            $articleCountTotal += $delObj->get_articleCollObj()->countArticles();
        }
        
        return $articleCountTotal;
        
    }
    
    /**
     * Returns the total price sum of all articles contained in the order
     *
     * @param   boolean     flag whether the sum should be returned as net sum: 0 returns gross sum, 1 returns net sum
     * @return  double      total price sum of all articles contained in the order (rounded to 2 decimal places)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-30
     */
    public function getArticleSumTotal($getNetSum) { 
        
        $articleSumTotal = 0;
        
        // override getNetSum request parameter (set to true) if order is tax free (=always net)
        if ($this->isTaxFree == 1) {
            $getNetSum = 1;
        }
        
        foreach ($this->deliveryCollObj as $delObj) { 
            // float operations may lead to precision problems (see www.php.net/float), using bcmath instead: this requires PHP to be configured with  '--enable-bcmath'
            $articleSumTotal = bcadd($articleSumTotal, $delObj->get_articleCollObj()->getItemsTotal($getNetSum), 4);
                 // original calculation: $articleSumTotal += $delObj->get_articleCollObj()->getItemsTotal($getNetSum);
        }
        
        // round return sum to 2 decimal digits 
        $articleSumTotalRounded = round((double)$articleSumTotal, 2);
        
        return (double)$articleSumTotalRounded;
        
    }
    
    /**
     * Returns the total price sum of all dispatch cost contained in the order
     *
     * @param   boolean     flag whether the sum should be returned as net sum: 0 returns gross sum, 1 returns net sum
     * @return  double      total price sum of all dispatch cost contained in the order (rounded to 2 decimal places)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-30
     */
    public function getDispatchSumTotal($getNetSum) { 
        
        $dispatchSumTotal = 0;
        
        // override getNetSum request parameter (set to true) if order is tax free (=always net)
        if ($this->isTaxFree == 1) {
            $getNetSum = 1;
        }
        
        foreach ($this->deliveryCollObj as $delObj) {  
            // float operations may lead to precision problems (see www.php.net/float), using bcmath instead: this requires PHP to be configured with  '--enable-bcmath'
            $dispatchSumTotal = bcadd($dispatchSumTotal, $delObj->getDeliveryDispatchCost($getNetSum), 4);
                 // original calculation: $dispatchSumTotal += $delObj->getDeliveryDispatchCost($getNetSum);
        }
        
        
        // round return sum to 2 decimal digits 
        $dispatchSumTotalRounded = round((double)$dispatchSumTotal, 2);
        
        return (double)$dispatchSumTotalRounded;
        
    }
    
    /**
     * Returns the total price sum of the order (including all articles and dispatch cost). 
     * NOTE: this may not be equivalent with the payment sum, use getPaymentSumTotal() for payments
     *
     * @param   boolean     flag whether the sum should be returned as net sum: 0 returns gross sum, 1 returns net sum
     * @return  double      total price sum of the order, including all articles and dispatch cost  (rounded to 2 decimal places)
     * @see     getPaymentSumTotal()
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-30
     */
    public function getOrderSumTotal($getNetSum) { 
        
        $orderSumTotal = 0;
        
        // override getNetSum request parameter (set to true) if order is tax free (=always net)
        if ($this->isTaxFree == 1) {
            $getNetSum = 1;
        }
        
        foreach ($this->deliveryCollObj as $delObj) { 
            // float operations may lead to precision problems (see www.php.net/float), using bcmath instead: this requires PHP to be configured with  '--enable-bcmath'
            $orderSumTotal = bcadd($orderSumTotal, $delObj->getDeliveryTotal($getNetSum), 4);
                 // original calculation: $orderSumTotal += $delObj->getDeliveryTotal($getNetSum);
        }
        
        // round total sum _down_ to 2 decimal digits 
        $orderSumTotalRounded = tx_pttools_finance::roundDownTwoDecimalPlaces((double)$orderSumTotal);
        
        return $orderSumTotalRounded;
        
    }
    
    /**
     * Returns the total payment sum of the order: total gross sum less payment modifiers (like vouchers, credit balances etc.)
     *
     * @param   void
     * @return  double      total payment sum of the order (total gross sum less payment modifiers, rounded to 2 decimal places)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-10-17
     */
    public function getPaymentSumTotal() { 
        
        $paymentSumTotal = $this->getOrderSumTotal(0);
        
        // if a paymentModifierCollObj is set: substract payment modifiers sum from order sum total, handle eventually resulting excess
        if (!is_null($this->paymentModifierCollObj)) {
            
            $paymentSumTotal = bcsub($this->getOrderSumTotal(0), $this->paymentModifierCollObj->getValue(), 4);
            
            if ($paymentSumTotal < 0) {
                $this->paymentModifierCollObj->handleValueExcess($paymentSumTotal);
                $paymentSumTotal = 0;
            }
        }
        
        // round payment sum _down_ to 2 decimal digits 
        $paymentSumTotal = tx_pttools_finance::roundDownTwoDecimalPlaces((double)$paymentSumTotal);
        
        return $paymentSumTotal;
        
    }
    
    /**
     * Returns the total tax sum of the order (including all articles and dispatch cost)
     *
     * @param   void
     * @return  double      total tax sum of the order, including all articles and dispatch cost (rounded to 2 decimal places)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-30
     */
    public function getOrderTaxTotal() { 
        
        $orderTaxTotal = 0;
        
        foreach ($this->deliveryCollObj as $delObj) { 
            // float operations may lead to precision problems (see www.php.net/float), using bcmath instead: this requires PHP to be configured with  '--enable-bcmath'
            $orderTaxTotal = bcadd($orderTaxTotal, $delObj->getDeliveryTaxTotal(), 4);
                 // original calculation: $orderTaxTotal += $delObj->getDeliveryTaxTotal();
        }
        
        // round return sum to 2 decimal digits 
        $orderTaxTotalRounded = round((double)$orderTaxTotal, 2);
        
        return $orderTaxTotalRounded;
        
    }
    
    /**
     * Returns an array of tax subtotals of the order (including all articles and dispatch cost), seperated by different taxcodes
     *
     * @param   void
     * @return  array       tax subtotals of the order, including all articles and dispatch cost: array( [string]taxcode => [double]tax subtotal of all order items with this taxcode,  rounded to 2 decimal places )
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-30
     */
    public function getOrderTaxTotalArray() { 
        
        $orderTaxTotalArr = array();
        
        foreach ($this->deliveryCollObj as $delObj) { 
            foreach ($delObj->getDeliveryTaxTotalArray() as $key=>$value) {
                if (!array_key_exists($key, $orderTaxTotalArr)) {
                    $orderTaxTotalArr[$key] = 0;
                }
                // float operations may lead to precision problems (see www.php.net/float), using bcmath instead: this requires PHP to be configured with  '--enable-bcmath'
                $orderTaxTotalArr[$key] = bcadd($orderTaxTotalArr[$key], $value, 4);
                     // original calculation: $orderTaxTotalArr[$key] += $value;
            }
        }
        
        // round return sums within the array to 2 decimal digits each
        foreach ($orderTaxTotalArr as $key=>$taxCodeTotal) {
            $orderTaxTotalArr[$key] = round((double)$taxCodeTotal, 2);
        }
        
        trace($orderTaxTotalArr, 0, '$orderTaxTotalArr');
        return $orderTaxTotalArr;
        
    }
    
    /**
     * Returns a flag whether the order is distributable or not (distributable means the order contains only _one_ delivery with more than one physical article contained)
     *
     * @param   void
     * @return  boolean     flag whether the order is distributable (TRUE) or not (FALSE)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-11-01
     */
    public function getOrderIsDistributable() {
        
        $orderIsDistributable = false;
        $physicalArticleQty = 0;
        
        // if the order has only one _physical_ delivery: check if there is more than one physical article contained
        if ($this->countDeliveries() == 1 && $this->deliveryCollObj->getIterator()->current()->getDeliveryIsPhysical() == true) {
            foreach ($this->deliveryCollObj->getIterator()->current()->get_articleCollObj() as $artObj) {
                // if article is physical add its quantity to $physicalArticleQty
                if ($artObj->get_isPhysical() == 1) { 
                    $physicalArticleQty += $artObj->get_quantity();
                    // if there is more than one physical article the order is distributable (break foreach loop)
                    if ($physicalArticleQty > 1) {
                        $orderIsDistributable = true;
                        break;
                    }
                }
            }
        }
        
        return $orderIsDistributable;
        
    }
    
    /**
     * Returns array with data from all properties
     *
     * @param   void
     * @return  array   array with data from all properties  
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-04
     */
    protected function getPropertyArray() {

        $dataArray = array();

        foreach (get_class_vars( __CLASS__ ) as $propertyname => $pvalue) {
            $getter = 'get_'.$propertyname;
            $dataArray[$propertyname] = $this->$getter();
        }

        return $dataArray;
        
    }
    
    
    
    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  tx_ptgsashop_deliveryCollection|NULL       property value, object of type tx_ptgsashop_deliveryCollection if set, NULL otherwise
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-10-13
     */
    public function get_deliveryCollObj() {
        
        return $this->deliveryCollObj;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  tx_ptgsauserreg_gsansch|NULL       property value, object of type tx_ptgsauserreg_gsansch if set, NULL otherwise
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-29
     */
    public function get_billingAddrObj() {
        
        return $this->billingAddrObj;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  tx_ptgsauserreg_paymentMethod|NULL       property value, object of type tx_ptgsauserreg_paymentMethod if set, NULL otherwise
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-03-23
     */
    public function get_paymentMethodObj() {
        
        return $this->paymentMethodObj;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void
     * @return  integer
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-03-23
     */
    public function get_feCrUserId() {
        
        return $this->feCrUserId;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void
     * @return  integer
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-11-28
     */
    public function get_timestamp() {
        
        return $this->timestamp;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void
     * @return  boolean
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-29
     */
    public function get_isNet() {
        
        return $this->isNet;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void
     * @return  boolean
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-24
     */
    public function get_isTaxFree() {
        
        return $this->isTaxFree;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void
     * @return  boolean
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-05-10
     */
    public function get_isMultDeliveries() {
        
        return $this->isMultDeliveries;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void
     * @return  boolean
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-31
     */
    public function get_termsCondAccepted() {
        
        return $this->termsCondAccepted;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void
     * @return  boolean
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-31
     */
    public function get_withdrawalAccepted() {
        
        return $this->withdrawalAccepted;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void
     * @return  integer
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-05-31
     */
    public function get_orderArchiveId() {
        
        return $this->orderArchiveId;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  tx_ptgsashop_iApplSpecDataObj|NULL       NULL or object implementing interface tx_ptgsashop_iApplSpecDataObj     
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-19
     */
    public function get_applSpecOrderDataObj() {
        
        return $this->applSpecOrderDataObj;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  tx_ptgsashop_iPaymentModifierCollection|NULL       NULL or object implementing interface tx_ptgsashop_iPaymentModifierCollection     
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-10-17
     */
    public function get_paymentModifierCollObj() {
        
        return $this->paymentModifierCollObj;
        
    }
    
    /**
     * Sets the property value
     *
     * @param   tx_ptgsashop_deliveryCollection      property value to set, object of type tx_ptgsashop_deliveryCollection
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-29
     */
    public function set_deliveryCollObj(tx_ptgsashop_deliveryCollection $deliveryCollObj) {
        
        $this->deliveryCollObj = $deliveryCollObj;
        
    }
    
    /**
     * Sets the property value
     *
     * @param   tx_ptgsauserreg_gsansch      property value to set, object of type tx_ptgsauserreg_gsansch
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-29
     */
    public function set_billingAddrObj(tx_ptgsauserreg_gsansch $billingAddrObj) {
        
        $this->billingAddrObj = $billingAddrObj;
        
    }
    
    /**
     * Sets the property value
     *
     * @param   tx_ptgsauserreg_paymentMethod      property value to set, object of type tx_ptgsauserreg_paymentMethod
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-03-23
     */
    public function set_paymentMethodObj(tx_ptgsauserreg_paymentMethod $paymentMethodObj) {
        
        $this->paymentMethodObj = $paymentMethodObj;
        
    }
    
    /**
     * Sets the property value
     *
     * @param   integer     property value to set
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-03-23
     */
    public function set_feCrUserId($feCrUserId) {
        
        $this->feCrUserId = (integer)$feCrUserId;
        
    }
    
    /**
     * Sets the property value
     *
     * @param   integer     property value to set
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-29
     */
    public function set_timestamp($timestamp) {
        
        $this->timestamp = (integer)$timestamp;
        
    }
    
    /**
     * Sets the property value
     *
     * @param   boolean     property value to set
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-29
     */
    public function set_isNet($isNet) {
        
        $this->isNet = (boolean)$isNet;
        
    }
    
    /**
     * Sets the property value
     *
     * @param   boolean     property value to set
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-24
     */
    public function set_isTaxFree($isTaxFree) {
        
        $this->isTaxFree = (boolean)$isTaxFree;
        
    }
    
    /**
     * Sets the property value
     *
     * @param   boolean     property value to set
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-05-10
     */
    public function set_isMultDeliveries($isMultDeliveries) {
        
        $this->isMultDeliveries = (boolean)$isMultDeliveries;
        
    }
    
    /**
     * Sets the property value
     *
     * @param   boolean     property value to set
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-31
     */
    public function set_termsCondAccepted($termsCondAccepted) {
        
        $this->termsCondAccepted = (boolean)$termsCondAccepted;
        
    }
    
    /**
     * Sets the property value
     *
     * @param   boolean     property value to set
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-31
     */
    public function set_withdrawalAccepted($withdrawalAccepted) {
        
        $this->withdrawalAccepted = (boolean)$withdrawalAccepted;
        
    }
    
    /**
     * Sets the property value
     *
     * @param   integer     property value to set
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-05-31
     */
    public function set_orderArchiveId($orderArchiveId) {
        
        $this->orderArchiveId = (integer)$orderArchiveId;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   tx_ptgsashop_iApplSpecDataObj       object implementing the tx_ptgsashop_iApplSpecDataObj interface               
     * @return  void
     * @since   2007-06-19
     */
    public function set_applSpecOrderDataObj(tx_ptgsashop_iApplSpecDataObj $applSpecOrderDataObj) {
        
        $this->applSpecOrderDataObj = $applSpecOrderDataObj;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   tx_ptgsashop_iPaymentModifierCollection       object implementing the tx_ptgsashop_iPaymentModifierCollection interface          
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-10-17
     */
    public function set_paymentModifierCollObj(tx_ptgsashop_iPaymentModifierCollection $paymentModifierCollObj) {
        
        $this->paymentModifierCollObj = $paymentModifierCollObj;
        
    }
    
    
    
} // end class




/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_order.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_order.php']);
}

?>