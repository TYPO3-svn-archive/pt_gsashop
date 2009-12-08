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
 * Delivery class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_delivery.php,v 1.45 2008/10/29 16:23:49 ry44 Exp $
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
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_lib.php';  // GSA Shop library with static methods
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_articleCollection.php';// GSA shop article collection class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_dispatchCost.php';// GSA shop dispatch cost class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_address.php';  // GSA Shop specific combined GSA/TYPO3 address class

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_finance.php'; // library class with finance related static methods
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_countrySpecifics.php';



/**
 * Delivery class
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2005-09-27
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_delivery {
    
    /**
     * Properties
     */
	/**
	 * @var bool	flag wether the underlying order is based on net prices: 0=gross price base, 1=net price base
	 */
    protected $orderBaseIsNet = false; 
    
    /**
     * @var bool	flag wether the underlying order is tax free
     */
    protected $orderBaseIsTaxFree = false; 
    
    /**
     * @var tx_ptgsashop_articleCollection	article collection object
     */
    protected $articleCollObj;
    
    /**
     * @var tx_ptgsashop_dispatchCost	dispatch cost object
     */
    protected $dispatchObj;

    /**
     * @var tx_ptgsauserreg_gsansch		address object used for shipping
     */
    protected $shippingAddrObj; 
    
    /**
     * @var int	UID of the delivery record in the order's delivery archive (0 for a non-archived delivery)
     */
    protected $orderArchiveId = 0; 
    
    /**
     * @var array	configuration values used by this class (this is set once in the class constructor)
     */
    protected $classConfigArr = array(); 
    
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
     
    /**
     * Class constructor: creates a new delivery instance "filled" with properties passed as parameters
     *
     * @param   tx_ptgsashop_articleCollection      object of type tx_ptgsashop_articleCollection: article collection of the delivery
     * @param   tx_ptgsauserreg_gsansch             object of type tx_ptgsauserreg_gsansch: shipping address of the delivery
     * @param   boolean     flag wether the underlying order is based on net prices: 0=gross price base, 1=net price base
     * @param   boolean     (optional) flag wether the underlying order is tax free (default:0)
     * @param   mixed       (optional) NULL or object of type tx_ptgsashop_dispatchCost: dispatch cost of the delivery
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-27
     */
    public function __construct(tx_ptgsashop_articleCollection $articleCollObj, tx_ptgsauserreg_gsansch $shippingAddrObj, $orderBaseIsNet, $orderBaseIsTaxFree=0, $dispatchObj=NULL) {
        
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        $this->classConfigArr = tx_ptgsashop_lib::getGsaShopConfig();
        
        $this->orderBaseIsNet = (boolean)$orderBaseIsNet;
        $this->orderBaseIsTaxFree = (boolean)$orderBaseIsTaxFree;
        $this->articleCollObj = $articleCollObj;
        $this->shippingAddrObj = $shippingAddrObj;
        
        $this->dispatchObj = ($dispatchObj instanceof tx_ptgsashop_dispatchCost ? $dispatchObj : new tx_ptgsashop_dispatchCost($this->getDeliveryDispatchCostTypeName()));
        
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**
     * Returns the name of the GSA dispatch cost type (dt.: "Versandart") to use for the delivery
     *
     * @param   void
     * @return  double      name of the GSA dispatch cost type to use for the delivery
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-10 
     */
    protected function getDeliveryDispatchCostTypeName() { 
        
        // default: cost free shipping/dispatch
        $dispatchCostTypeName = $this->classConfigArr['gsaDispatchTypeCostFree'];
        
        // for physical deliveries: set dispatch cost
        if ($this->getDeliveryIsPhysical() == true) {
            $delCountry = $this->shippingAddrObj->get_country();
            
            // delivery is inland delivery
            if (tx_ptgsauserreg_countrySpecifics::isForeignCountry($delCountry) == false) {
                
                // inland net delivery
                if ($this->orderBaseIsNet == true) {
                    $dispatchCostTypeName = $this->classConfigArr['gsaDispatchTypeInlandNet'];
                // inland gross delivery
                } else {
                    $dispatchCostTypeName = $this->classConfigArr['gsaDispatchTypeInlandGross'];
                }
            
            // delivery is abroad delivery    
            } else {
                
                // abroad net delivery
                if ($this->orderBaseIsNet == true) {
                    $dispatchCostTypeName = $this->classConfigArr['gsaDispatchTypeAbroadNet'];
                // abroad gross delivery
                } else {
                    $dispatchCostTypeName = $this->classConfigArr['gsaDispatchTypeAbroadGross'];
                }
            }
        }
        
        // HOOK for changing the dispatch cost name to return
        if (($hookObj = tx_pttools_div::hookRequest('pt_gsashop', 'delivery_hooks', 'getDeliveryDispatchCostTypeNameHook')) !== false) {
            $dispatchCostTypeNameBeforeHooking = $dispatchCostTypeName;
            $dispatchCostTypeName = (string)$hookObj->getDeliveryDispatchCostTypeNameHook($this, $dispatchCostTypeNameBeforeHooking); // use hook method if hook has been found
        }
        
        return $dispatchCostTypeName;
        
    }
    
    /**
     * Updates the dispatch cost, the net/gross base and all articles of the delivery by retrieving up-to-date data from the database
     *
     * @param   tx_ptgsauserreg_feCustomer   FE customer to update the delivery for, object of type tx_ptgsauserreg_feCustomer
     * @param   boolean   (optional) flag wether the underlying order is based on net prices: 0=gross price base, 1=net price base (default:0)
     * @param   boolean   (optional) flag wether the underlying order is tax free (default:0)
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-16
     */
    public function updateDelivery(tx_ptgsauserreg_feCustomer $customerObj, $orderBaseIsNet=0, $orderBaseIsTaxFree=0) {
        
        $this->orderBaseIsNet = $orderBaseIsNet;
        $this->orderBaseIsTaxFree = $orderBaseIsTaxFree;
        
        $this->get_articleCollObj()->updateItemsData($customerObj->get_priceCategory(), $customerObj->get_gsaMasterAddressId());
        $this->shippingAddrObj = new tx_ptgsashop_address($customerObj->getValidatedAddressId($this->shippingAddrObj->get_uid()), true);   // gets validated (possibly updated/changed) data of address record based on existing uid
        $this->dispatchObj = new tx_ptgsashop_dispatchCost($this->getDeliveryDispatchCostTypeName());
        
    }
    
    /**
     * Returns the dispatch cost of the delivery
     *
     * @param   boolean     flag wether the cost should be returned as net price: 0=return gross sum, 1=return net sum
     * @return  double      dispatch cost of the delivery
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-30 
     */
    public function getDeliveryDispatchCost($getNetSum) { 
        
        // get tax rate for dispatch cost
        $dispatchCostTaxRate = $this->dispatchObj->getTaxRate();
        
        // override getNetSum request parameter (set to true) if order is tax free (=always net)
        if ($this->orderBaseIsTaxFree == 1) {
            $getNetSum = 1;
        }
        
        // case: current order is net price based
        if ($this->orderBaseIsNet == 1) {
            
            $articlesTotalNet = $this->articleCollObj->getItemsTotal(1);
            $dispatchCostForNetOrder = $this->dispatchObj->getDispatchCostForGivenSum($articlesTotalNet);
            
            // if net sum is requested
            if ($getNetSum  == 1) {
                $deliveryDispatchCost = $dispatchCostForNetOrder;
            // if gross sum is requested    
            } else {
                $deliveryDispatchCost = tx_pttools_finance::getGrossPriceFromNet($dispatchCostForNetOrder, $dispatchCostTaxRate);
            }
        
        // case: current order is gross price based
        } else {
            
            $articlesTotalGross = $this->articleCollObj->getItemsTotal(0);
            $dispatchCostForGrossOrder = $this->dispatchObj->getDispatchCostForGivenSum($articlesTotalGross);
            
            // if gross sum is requested    
            if ($getNetSum  == 0) {
                $deliveryDispatchCost = $dispatchCostForGrossOrder;
            // if net sum is requested    
            } else {
                $deliveryDispatchCost = tx_pttools_finance::getNetPriceFromGross($dispatchCostForGrossOrder, $dispatchCostTaxRate);
            }
            
        }
         
        trace($deliveryDispatchCost, 0, 'getDeliveryDispatchCost($getNetSum='.$getNetSum.')');
        return $deliveryDispatchCost;
        
    }
    
    /**  ##### TODO: maybe shorten this method by using new method getDeliveryDispatchCost() #####
     * Returns total price sum of the delivery including all items found in the article collection AND dispatch cost
     *
     * @param   boolean     flag wether the sum should be returned as net sum: 0=return gross sum, 1=return net sum
     * @return  double      total price sum of the delivery including all items found in the article collection AND dispatch cost (rounded to 2 decimal places)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-08-03 / renamed 2005-09-29
     */
    public function getDeliveryTotal($getNetSum) { 
        
        // get tax rate for dispatch cost
        $dispatchCostTaxRate = $this->dispatchObj->getTaxRate();
        
        // override getNetSum request parameter (set to true) if order is tax free (=always net)
        if ($this->orderBaseIsTaxFree == 1) {
            $getNetSum = 1;
        }
        
        // case: current is order is net price based
        if ($this->orderBaseIsNet == 1) {
            
            $articlesTotalNet = $this->articleCollObj->getItemsTotal(1);
            $dispatchCostForNetOrder = $this->dispatchObj->getDispatchCostForGivenSum($articlesTotalNet);
            
            // if net total sum is requested
            if ($getNetSum  == 1) {
                // float operations may lead to precision problems (see www.php.net/float), using bcmath instead: this requires PHP to be configured with  '--enable-bcmath'
                $deliveryTotal = bcadd($articlesTotalNet, $dispatchCostForNetOrder, 4);
                // original calculation: $deliveryTotal = $articlesTotalNet + $dispatchCostForNetOrder;
            
            // if gross total sum is requested    
            } else {
                $articlesTotalGross = $this->articleCollObj->getItemsTotal(0);
                // float operations may lead to precision problems (see www.php.net/float), using bcmath instead: this requires PHP to be configured with  '--enable-bcmath'
                $deliveryTotal = bcadd($articlesTotalGross, tx_pttools_finance::getGrossPriceFromNet($dispatchCostForNetOrder, $dispatchCostTaxRate), 4);
                    // original calculation: $deliveryTotal = $articlesTotalGross + tx_pttools_finance::getGrossPriceFromNet($dispatchCostForNetOrder, $dispatchCostTaxRate);
            }
        
        // case: current is order is gross price based
        } else {
            
            $articlesTotalGross = $this->articleCollObj->getItemsTotal(0);
            $dispatchCostForGrossOrder = $this->dispatchObj->getDispatchCostForGivenSum($articlesTotalGross);
            
            // if gross total sum is requested    
            if ($getNetSum  == 0) {
                // float operations may lead to precision problems (see www.php.net/float), using bcmath instead: this requires PHP to be configured with  '--enable-bcmath'
                $deliveryTotal = bcadd($articlesTotalGross, $dispatchCostForGrossOrder, 4);
                    // original calculation: $deliveryTotal = $articlesTotalGross + $dispatchCostForGrossOrder;
                    
            // if net total sum is requested    
            } else {
                $articlesTotalNet = $this->articleCollObj->getItemsTotal(1);
                // float operations may lead to precision problems (see www.php.net/float), using bcmath instead: this requires PHP to be configured with  '--enable-bcmath'
                $deliveryTotal = bcadd($articlesTotalNet, tx_pttools_finance::getNetPriceFromGross($dispatchCostForGrossOrder, $dispatchCostTaxRate), 4);
                    // original calculation: $deliveryTotal = $articlesTotalNet + tx_pttools_finance::getNetPriceFromGross($dispatchCostForGrossOrder, $dispatchCostTaxRate);
            }
            
        }
        
        // round total sum _down_ to 2 decimal digits 
        $deliveryTotalRounded = tx_pttools_finance::roundDownTwoDecimalPlaces((double)$deliveryTotal, 2);
        
        trace($deliveryTotalRounded, 0, 'getDeliveryTotal($getNetSum='.$getNetSum.')');
        return $deliveryTotalRounded;
        
    }
    
    /**
     * Returns total tax sum of the delivery including all items found in the article collection AND dispatch cost
     *
     * @param   void
     * @return  double      total tax sum of the delivery including all items found in the article collection AND dispatch cost (rounded to 2 decimal places)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-08-03 / renamed 2005-09-29
     */
    public function getDeliveryTaxTotal() { 
        
        $itemsTaxTotal = $this->articleCollObj->getItemsTaxTotal($this->orderBaseIsTaxFree);
        $itemsSumTotal = $this->articleCollObj->getItemsTotal($this->orderBaseIsNet);
        $dispatchCostTax = $this->dispatchObj->getDispatchCostTax($itemsSumTotal, $this->orderBaseIsNet, $this->orderBaseIsTaxFree);
        
        // float operations may lead to precision problems (see www.php.net/float), using bcmath instead: this requires PHP to be configured with  '--enable-bcmath'
        $deliveryTaxTotal = bcadd($itemsTaxTotal, $dispatchCostTax, 4);
            // original calculation: $deliveryTaxTotal = $itemsTaxTotal + $dispatchCostTax;
         
        // round return sum to 2 decimal digits 
        $deliveryTaxTotalRounded = round((double)$deliveryTaxTotal, 2);
        
        trace($deliveryTaxTotalRounded, 0, '$deliveryTaxTotalRounded');
        return $deliveryTaxTotalRounded;
        
    }
    
    /**
     * Returns an array of tax subtotals of the delivery including all article collection items AND dispatch cost, seperated by different taxcodes
     *
     * @param   void
     * @return  array       tax subtotals of the delivery including all article collection items AND dispatch cost: array( [string]taxcode => [double]tax subtotal of all delivery items with this taxcode, rounded to 2 decimal places )
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-08-04 / renamed 2005-09-29
     */
    public function getDeliveryTaxTotalArray() {
        
        $deliveryTaxTotalArr = $this->articleCollObj->getItemsTaxTotalArray($this->orderBaseIsTaxFree);
        $itemsSumTotal = $this->articleCollObj->getItemsTotal($this->orderBaseIsNet);
        $dispatchCostTax = $this->dispatchObj->getDispatchCostTax($itemsSumTotal, $this->orderBaseIsNet, $this->orderBaseIsTaxFree);
        
        // float operations may lead to precision problems (see www.php.net/float), using bcmath instead: this requires PHP to be configured with  '--enable-bcmath'
        $deliveryTaxTotalArr[$this->dispatchObj->get_costTaxCode()] = (double)bcadd($deliveryTaxTotalArr[$this->dispatchObj->get_costTaxCode()], $dispatchCostTax, 4);
            // original calculation: $deliveryTaxTotalArr[$this->dispatchObj->get_costTaxCode()] += $dispatchCostTax;
         
        // round return sums within the array to 2 decimal digits each
        foreach ($deliveryTaxTotalArr as $key=>$taxCodeTotal) {
            $deliveryTaxTotalArr[$key] = round((double)$taxCodeTotal, 2);
        }
        
        trace($deliveryTaxTotalArr, 0, '$deliveryTaxTotalArr');
        return $deliveryTaxTotalArr;
        
    }
    
    /**
     * Returns a flag whether the delivery is physical/deliverable (this means at least one article in the deliveries article collection is _not_ virtual)
     *
     * @param   void
     * @return  boolean    TRUE if the delivery is physical/deliverable (this means at least one article in the deliveries article collection is _not_ virtual), FALSE otherwise
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-11-01
     */
    public function getDeliveryIsPhysical() {
        
        $isPhysical = $this->articleCollObj->getIsPhysical();
        return $isPhysical;
        
    }
    
    
    
    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
        
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  boolean      property value
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-25
     */
    public function get_orderBaseIsNet() {
        
        return $this->orderBaseIsNet;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  boolean      property value
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-25
     */
    public function get_orderBaseIsTaxFree() {
        
        return $this->orderBaseIsTaxFree;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  tx_ptgsashop_articleCollection      property value, object of type tx_ptgsashop_articleCollection
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-10-13
     */
    public function get_articleCollObj() {
        
        return $this->articleCollObj;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  tx_ptgsauserreg_gsansch      property value, object of type tx_ptgsauserreg_gsansch
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-10-13
     */
    public function get_shippingAddrObj() {
        
        return $this->shippingAddrObj;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  tx_ptgsashop_dispatchCost      property value, object of type tx_ptgsashop_dispatchCost
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-10-13
     */
    public function get_dispatchObj() {
        
        return $this->dispatchObj;
        
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
     * Sets the property value
     *
     * @param   tx_ptgsauserreg_gsansch      property value to set, object of type tx_ptgsauserreg_gsansch
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-10-05
     */
    public function set_shippingAddrObj(tx_ptgsauserreg_gsansch $shippingAddrObj) {
        
        $this->shippingAddrObj = $shippingAddrObj;
        
        // set new dispatch object since this is dependant from shipping adress (inland/abroad)
        $this->dispatchObj = new tx_ptgsashop_dispatchCost($this->getDeliveryDispatchCostTypeName());
        
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
    
    
    
} // end class




/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_delivery.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_delivery.php']);
}

?>