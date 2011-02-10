<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2007 Rainer Kuhn (kuhn@punkt.de)
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
 * Order presentator class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_orderPresentator.php,v 1.24 2008/11/19 09:15:13 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2007-05-09
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of TYPO3 libraries
 *
 * @see t3lib_div
 */
require_once(PATH_t3lib.'class.t3lib_div.php');

/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_lib.php';  // GSA shop library class with static methods
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_order.php';  // GSA Shop order class

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_smartyAdapter.php';  // Smarty template engine adapter


/**
 * Order presentator class
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2007-05-09
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_orderPresentator {
    
    /**
     * Properties
     */
    
    /**
     * @var tx_ptgsashop_order order object to presentate
     */
    protected $orderObj; 
    
    /**
     * @var array    multilingual language labels (locallang) for this class
     */
    protected $llArray = array();
    
    /**
     * @var array    configuration values used by this class (this is set once in the class constructor)
     */
    protected $classConfigArr = array();
    
    /**
     * Class Constants
     */
    const EXT_KEY     = 'pt_gsashop';                       // (string) the extension key
    const LL_FILEPATH = 'res/locallang_res_classes.xml';    // (string) path to the locallang file to use within this class
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
     
    /**
     * Class constructor: sets the object's properties
     *
     * @param   tx_ptgsashop_order      order to presentate, object of type tx_ptgsashop_order
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-05-09
     */
    public function __construct(tx_ptgsashop_order $orderObj) {
        
        $this->orderObj = $orderObj;
        
        // get configuration values
        $this->classConfigArr = tx_ptgsashop_lib::getGsaShopConfig();
        
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /** 
     * Returns an order as a plain text string (shopping cart items, dispatch cost and customer addresses), e.g. for usage in emails
     * 
     * @param   string      the template filename, being a TypoScript resource data type
     * @param   string      (optional) document number ("Vorgangsnummer") of the saved order document in the ERP system
     * @param   boolean     (optional) flag whether the first two introduction text lines ("Thanks for your order...") should be displayed or not
     * @return  string      order as plain text string
     * @throws  tx_pttools_exception   if no deliveries found in order
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-05-09  (based on tx_ptgsashop_pi3::displayFinalOrderPlaintext() from 2006-03-22)
     */
    public function getPlaintextPresentation($templateFile, $relatedErpDocNo='', $displayIntroText=true) {
        
        $this->llArray = tx_pttools_div::readLLfile(t3lib_extMgm::extPath(self::EXT_KEY).self::LL_FILEPATH); // get locallang data
        
        $markerArray = array();
        
        // throw exception if no deliveries found in order
        if ($this->orderObj->countDeliveries() < 1) {
            throw new tx_pttools_exception('No deliveries found in order', 3);
        }
        
        
        // assign template placeholders: order header
        $markerArray['cond_displayIntroText'] = $displayIntroText;
        $httpHost = t3lib_div::getIndpEnv('HTTP_HOST'); // this is empty in CLI mode
        $markerArray['orderHost'] = (!empty($httpHost) ? $httpHost : $this->classConfigArr['shopName']);
        $markerArray['orderDate'] = $this->orderObj->getDate();
        $markerArray['orderTime'] = $this->orderObj->getTime(0);
        if (!empty($relatedErpDocNo)) {
            $markerArray['cond_erpDocNo'] = true;
            $markerArray['orderErpDocNo'] = $relatedErpDocNo;
        }
        
        
        // assign template placeholders: order sums
        $markerArray['orderSumTotalNet'] = sprintf("%9.2f", $this->orderObj->getOrderSumTotal(1));
        $markerArray['orderSumTotalGross'] = sprintf("%9.2f", $this->orderObj->getOrderSumTotal(0));
        $markerArray['orderPaymentSumTotal'] = sprintf("%9.2f", $this->orderObj->getPaymentSumTotal());
        if ($this->classConfigArr['displayPaymentSumByDefault'] == 1 || ($this->orderObj->getOrderSumTotal(0) != $this->orderObj->getPaymentSumTotal())) {
            $markerArray['cond_displayPaymentSumTotal'] = true;
        }
        
        $taxArr = array();
        foreach ($this->orderObj->getOrderTaxTotalArray() as $taxcode=>$taxSubtotal) {
            $taxArr[] = array('taxRate' => sprintf("%4.1f", tx_ptgsashop_lib::getTaxRate($taxcode)),
                              'taxSubTotal' => sprintf("%9.2f", $taxSubtotal));
        }
        $markerArray['orderTaxArr'] = $taxArr;
        
        if ($this->orderObj->get_isNet() == true) {
            $markerArray['cond_isNet'] = true;
            $markerArray['orderSumArticlesNet'] = sprintf("%9.2f", $this->orderObj->getArticleSumTotal(1));
            $markerArray['orderSumDispatchNet'] = sprintf("%9.2f", $this->orderObj->getDispatchSumTotal(1));
        } else {
            $markerArray['cond_isNet'] = false;
            $markerArray['orderSumArticlesGross'] = sprintf("%9.2f", $this->orderObj->getArticleSumTotal(0));
            $markerArray['orderSumDispatchGross'] = sprintf("%9.2f", $this->orderObj->getDispatchSumTotal(0));
        }
        
        
        // assign template placeholders: billing address
        $billingAddrObj = $this->orderObj->get_billingAddrObj();
        $markerArray['billingAddress'] = $billingAddrObj->getAddressLabel("\n", 0);
        
        
        // process deliveries from delivery collection
        $delArr = array();
        $i = 0;
        $articleCounter = 0; // article counter for delivery titles
        foreach ($this->orderObj->get_deliveryCollObj() as $delObj) {
            $delArr[$i]['delArtStart'] = ($articleCounter + 1);
            $delArr[$i]['delArtEnd'] = ($articleCounter = $articleCounter + $delObj->get_articleCollObj()->countArticles());
            $delArr[$i]['delArtTotal'] = $this->orderObj->countArticlesTotal();
            
            
            // process article lines (display depending on shop config)...
            $delArr[$i]['delArtLineArr'] = array();
            
                //... default: display articles with 2 decimal places 
            if ($this->classConfigArr['usePricesWithMoreThanTwoDecimals'] == 0) {
                foreach ($delObj->get_articleCollObj() as $articleObj) {
                    // create article line with exactly 2 decimal places  
                    $delArr[$i]['delArtLineArr'][] = ''.    
                        sprintf("%3u", $articleObj->get_quantity())."  ". // article quantity
                        sprintf("%' -42s", substr($articleObj->get_description(), 0, 42))." \n".
                        (strlen($articleObj->getAdditionalText()) > 0 ? 
                            ("     ".sprintf("%' -42s", substr($articleObj->getAdditionalText(), 0, 42))." \n") 
                            : ""
                        ).
                        ($articleObj->getFixedCost($this->orderObj->get_isNet()) > 0 ? 
                            ("     ".sprintf("%' -42s", substr("[".tx_ptgsashop_lib::getDisplayPriceString($articleObj->getFixedCost($this->orderObj->get_isNet()))." ".$this->classConfigArr['currencyCode']." ".tx_pttools_div::getLLL(__CLASS__.'.fixedCost', $this->llArray)."]", 0, 42))." \n") 
                            : ""
                        ).
                        "     ".sprintf("%'.-41s", substr("(".$articleObj->get_artNo().")", 0, 41))."  ".
                        sprintf("%4.1f", $articleObj->getTaxRate())."  ". // article taxrate
                        sprintf("%8.2f", $articleObj->getDisplayPrice($this->orderObj->get_isNet()))."  ". // article price
                        sprintf("%8.2f", $articleObj->getItemSubtotal($this->orderObj->get_isNet()))."\n"; // article subtotal
                }
             
            //... in configured: display articles with up to 4 decimal places    
            } else {
                foreach ($delObj->get_articleCollObj() as $articleObj) {
                    // get number of decimal places of article price
                    $articlePrice = tx_ptgsashop_lib::getDisplayPriceString($articleObj->getDisplayPrice($this->orderObj->get_isNet()));
                    list($apIntegers, $apDecimals) = explode('.', (string)$articlePrice);
                    $apNumberOfDecimals = strlen($apDecimals);
                    if ($apNumberOfDecimals < 2) {
                        $apNumberOfDecimals = '2';
                    } elseif ($apNumberOfDecimals > 4) {
                        $apNumberOfDecimals = '4';
                    }
                    // get number of decimal places of article subtotal
                    $articleSubtotal = $articleObj->getItemSubtotal($this->orderObj->get_isNet());
                    list($astIntegers, $astDecimals) = explode('.', (string)$articleSubtotal);
                    $astNumberOfDecimals = strlen($astDecimals);
                    if ($astNumberOfDecimals < 2) {
                        $astNumberOfDecimals = '2';
                    } elseif ($astNumberOfDecimals > 4) {
                        $astNumberOfDecimals = '4';
                    }
                    // create article line with up to 4 decimal places  
                    $delArr[$i]['delArtLineArr'][] = ''.    
                        sprintf("%3u", $articleObj->get_quantity())."  ". // article quantity
                        sprintf("%' -42s", substr($articleObj->get_description(), 0, 42))." \n".
                        (strlen($articleObj->getAdditionalText()) > 0 ? 
                            ("     ".sprintf("%' -42s", substr($articleObj->getAdditionalText(), 0, 42))." \n") 
                            : ""
                        ).
                        ($articleObj->getFixedCost($this->orderObj->get_isNet()) > 0 ? 
                            ("     ".sprintf("%' -42s", substr("[".tx_ptgsashop_lib::getDisplayPriceString($articleObj->getFixedCost($this->orderObj->get_isNet()))." ".$this->classConfigArr['currencyCode']." ".tx_pttools_div::getLLL(__CLASS__.'.fixedCost', $this->llArray)."]", 0, 42))." \n") 
                            : ""
                        ).
                        "     ".sprintf("%'.-41s", substr("(".$articleObj->get_artNo().")", 0, 41))."  ".
                        sprintf("%4.1f", $articleObj->getTaxRate())."  ". // article taxrate
                        sprintf("%8.".$apNumberOfDecimals."f", $articlePrice)."  ". // article price
                        sprintf("%8.".$astNumberOfDecimals."f", $articleSubtotal)."\n"; // article subtotal
                }                
            }
            
            $delArr[$i]['delSumArticles'] = sprintf("%9.2f", $delObj->get_articleCollObj()->getItemsTotal($this->orderObj->get_isNet()));
            $delArr[$i]['dispatchCostLine'] =  ''.
                sprintf("%5s", NULL). // blanks
                sprintf("%'.-41s", substr($delObj->get_dispatchObj()->get_displayName(), 0 , 41))."  ". // name of dispatch cost type
                sprintf("%4.1f", $delObj->get_dispatchObj()->getTaxRate()). // dispatch cost taxrate
                sprintf("%12s", NULL). // blanks
                sprintf("%8.2f", $delObj->get_dispatchObj()->getDispatchCostForGivenSum($delObj->get_articleCollObj()->getItemsTotal($this->orderObj->get_isNet()))); // dispatch cost sum
                
            $delArr[$i]['delSumTotalNet'] = sprintf("%9.2f", $delObj->getDeliveryTotal(1));
            $delArr[$i]['delSumTotalGross'] = sprintf("%9.2f", $delObj->getDeliveryTotal(0));
            
            $delArr[$i]['delTaxArr'] = array();
            foreach ($delObj->getDeliveryTaxTotalArray() as $taxcode=>$taxSubtotal) {
                $delArr[$i]['delTaxArr'][] = array('taxRate' => sprintf("%4.1f", tx_ptgsashop_lib::getTaxRate($taxcode)),
                                                   'taxSubTotal' => sprintf("%9.2f", $taxSubtotal));
            }
            
            // delivery shipping address
            if ($delObj->getDeliveryIsPhysical() == true) {
                $delArr[$i]['cond_displayShippingAddr'] = true;
                $delArr[$i]['delAddress'] = $delObj->get_shippingAddrObj()->getAddressLabel("\n", 0);
            }
                      
            // HOOK: allow multiple hooks to manipulate the delivery template data array
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['orderPresentator_hooks']['getPlaintextPresentation_returnDeliveryMarkersHook'])) {
                foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['orderPresentator_hooks']['getPlaintextPresentation_returnDeliveryMarkersHook'] as $className) {
                    $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                    $delArr[$i] = $hookObj->getPlaintextPresentation_returnDeliveryMarkersHook($this, $delObj, $delArr[$i]);
                }
            } 
            
            $i++;
            
        } // end foreach (processing of deliveries)
        
        $markerArray['delArr'] = $delArr;
        
        // payment (extended GSA based payment): only for order sums > 0 if TS config enableExtendedPaymentChoice is enabled
        if ($this->orderObj->getOrderSumTotal($this->orderObj->get_isNet()) > 0 && $this->classConfigArr['enableExtendedPaymentChoice'] == 1) {
            $markerArray['cond_displayPayment'] = true;
            $markerArray['ll_paymentMethod'] = 
                tx_pttools_div::getLLL(__CLASS__.'.paymentMethod_'.$this->orderObj->get_paymentMethodObj()->get_method(), $this->llArray);
        }
        
        
        // HOOK: allow multiple hooks to manipulate $markerArray
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['orderPresentator_hooks']['getPlaintextPresentation_MarkerArrayHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['orderPresentator_hooks']['getPlaintextPresentation_MarkerArrayHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $markerArray = $hookObj->getPlaintextPresentation_MarkerArrayHook($this, $markerArray); // $this is passed as a reference (since PHP5) and can be manipulated in the hook method
            }
        }
        
        // return prepared template to display
        $smarty = new tx_pttools_smartyAdapter(self::EXT_KEY);
        foreach ($markerArray as $markerKey=>$markerValue) {
            $smarty->assign($markerKey, $markerValue);
        }
        $filePath = $smarty->getTplResFromTsRes($templateFile);
        trace($filePath, 0, 'Smarty template resource $filePath');
        return $smarty->fetch($filePath);
        
    }
    
    /**
     * Get marker array from order object values
     * 
     * @param     void
     * @return   array    marker array
     * @author   Fabrizio Branca <branca@punkt.de>
     * @since    2008-06-16
     */
    public function getMarkerArray() {
        
        // Check assertions
        tx_pttools_assert::isType($this->orderObj, 'tx_ptgsashop_order');
        tx_pttools_assert::isTrue($this->orderObj->countDeliveries() >= 1, array('message' => 'No deliveries found in order'));
                
        $isNet = $this->orderObj->get_isNet();
        
        
        /***********************************************************************
         * Flags
         **********************************************************************/        
        $markerArray['isTaxFree'] = $this->orderObj->get_isTaxFree();
        $markerArray['isNet'] = $isNet;
        
        tx_pttools_assert::isFalse(!$markerArray['isNet'] && $markerArray['isTaxFree'], array('message' => 'An order cannot be gross AND tax-free!'));
        
        $markerArray['isMultDeliveries'] = $this->orderObj->get_isMultDeliveries();
        $markerArray['hasMultDeliveries'] = ($this->orderObj->get_deliveryCollObj()->count() > 1) ? true : false;
        if (!$this->markerArray['hasMultDeliveries'] && $this->orderObj->get_billingAddrObj()->get_uid() == $this->orderObj->get_deliveryCollObj()->getItemByIndex(0)->get_shippingAddrObj()->get_uid()) {
            $markerArray['billingAddrEqualsShippingAddr'] = true;
        } else {
            $markerArray['billingAddrEqualsShippingAddr'] = false;
        }
        
        
        
        /***********************************************************************
         * General data
         **********************************************************************/
        $markerArray['billingAddress'] = $this->orderObj->get_billingAddrObj()->getAddressLabel(chr(10));
        $markerArray['date'] = time();
        $markerArray['orderArchiveId'] = $this->orderObj->get_orderArchiveId();
        
        // Shop operator data
        $markerArray['shopOperatorBank'] = array(
            'name'        => $this->classConfigArr['shopOperatorBankName'],
            'code'        => $this->classConfigArr['shopOperatorBankCode'],
            'accountNo'   => $this->classConfigArr['shopOperatorBankAccountNo'],
            'bic'         => $this->classConfigArr['shopOperatorBankBic'],
            'iban'        => $this->classConfigArr['shopOperatorBankIban'],
        );
        $markerArray['shopOperatorContact'] = array(
            'name'        => $this->classConfigArr['shopOperatorName'],
            'streetNo'    => $this->classConfigArr['shopOperatorStreetNo'],
            'zip'         => $this->classConfigArr['shopOperatorZip'],
            'city'        => $this->classConfigArr['shopOperatorCity'],
            'countryCode' => $this->classConfigArr['shopOperatorCountryCode'],
            'email'       => $this->classConfigArr['shopOperatorEmail'],
        );
        
        // Payment data
        $markerArray['paymentMethod'] = $this->orderObj->get_paymentMethodObj()->get_method();
        $markerArray['customerBank'] = array(
            'name'      => $this->orderObj->get_paymentMethodObj()->get_bankName(),
            'code'      => $this->orderObj->get_paymentMethodObj()->get_bankCode(),
            'accountNo' => $this->orderObj->get_paymentMethodObj()->get_bankAccountNo(),
        );

        
        
        /***********************************************************************
         * Loop through the deliveries and their articles
         **********************************************************************/
        $markerArray['deliveries'] = array();
        $markerArray['deliveryDispatchCosts'] = array();
        foreach ($this->orderObj->get_deliveryCollObj() as $deliveryNumber => $delivery) { /* @var $delivery tx_ptgsashop_delivery  */

            $articles = array();
            foreach ($delivery->get_articleCollObj() as $article) { /* @var $article tx_ptgsashop_article */

                $articles[] = array (
                    'quantity' => $article->get_quantity(),
                    'artNo' => $article->get_artNo(),
                    'description' => $article->get_description(),
                    'altText' => $article->get_altText(),
                    'displayPrice' => array(
                        'standard'  => tx_ptgsashop_lib::getDisplayPriceString($article->getDisplayPrice($isNet)),
                        'net'       => tx_ptgsashop_lib::getDisplayPriceString($article->getDisplayPrice(true)),
                        'gross'     => tx_ptgsashop_lib::getDisplayPriceString($article->getDisplayPrice(false)),
                    ),
                    'itemSubtotal' => array(
                        'standard'  => tx_ptgsashop_lib::getDisplayPriceString($article->getItemSubtotal($isNet)),
                        'net'       => tx_ptgsashop_lib::getDisplayPriceString($article->getItemSubtotal(true)),
                        'gross'     => tx_ptgsashop_lib::getDisplayPriceString($article->getItemSubtotal(false)),
                    ),
                );

            } // foreach articles

            $markerArray['deliveries'][$deliveryNumber] = array(
                'articles' => $articles,
                'dispatchCost' => array(
                    'standard'  => tx_ptgsashop_lib::getDisplayPriceString($delivery->getDeliveryDispatchCost($isNet)),
                    'net'       => tx_ptgsashop_lib::getDisplayPriceString($delivery->getDeliveryDispatchCost(true)),
                    'gross'     => tx_ptgsashop_lib::getDisplayPriceString($delivery->getDeliveryDispatchCost(false)),
                ),
                'shippingAddress' => $delivery->get_shippingAddrObj()->getAddressLabel(', '),
                'shippingAddressLabel' => $delivery->get_shippingAddrObj()->getAddressLabel(chr(10)),
            );
        
        } // foreach deliveries

        

        /***********************************************************************
         * Sums
         **********************************************************************/
        $markerArray['articleSumTotal'] = array(
            'standard'  => tx_ptgsashop_lib::getDisplayPriceString($this->orderObj->getArticleSumTotal($isNet)),
            'net'       => tx_ptgsashop_lib::getDisplayPriceString($this->orderObj->getArticleSumTotal(true)),
            'gross'     => tx_ptgsashop_lib::getDisplayPriceString($this->orderObj->getArticleSumTotal(false)),
        );

        $markerArray['orderSumTotal'] = array(
            'standard'  => tx_ptgsashop_lib::getDisplayPriceString($this->orderObj->getOrderSumTotal($isNet)),
            'net'       => tx_ptgsashop_lib::getDisplayPriceString($this->orderObj->getOrderSumTotal(true)),
            'gross'     => tx_ptgsashop_lib::getDisplayPriceString($this->orderObj->getOrderSumTotal(false)),
        );

        $markerArray['dispatchCostSumTotal'] = array(
            'standard'  => tx_ptgsashop_lib::getDisplayPriceString($this->orderObj->getDispatchSumTotal($isNet)),
            'net'       => tx_ptgsashop_lib::getDisplayPriceString($this->orderObj->getDispatchSumTotal(true)),
            'gross'     => tx_ptgsashop_lib::getDisplayPriceString($this->orderObj->getDispatchSumTotal(false)),
        );
        
        $markerArray['orderPaymentSumTotal'] = sprintf("%9.2f", $this->orderObj->getPaymentSumTotal());
        if ($this->classConfigArr['displayPaymentSumByDefault'] == 1 || ($this->orderObj->getOrderSumTotal(0) != $this->orderObj->getPaymentSumTotal())) {
        	$markerArray['cond_displayPaymentSumTotal'] = true;
        }
        
        
        /***********************************************************************
         * Taxes
         **********************************************************************/
        $markerArray['taxes'] = array();
        foreach ($this->orderObj->getOrderTaxTotalArray() as $taxcode => $taxSubtotal) {
            if ($taxcode) {
                $markerArray['taxes'][] = array (
                    'code' => sprintf("%4.1f", tx_ptgsashop_lib::getTaxRate($taxcode)),
                    'tax' => tx_ptgsashop_lib::getDisplayPriceString($taxSubtotal)
                );
            }
        }
        
        return $markerArray;
        
    }
    
    
    
    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  tx_ptgsashop_order       order object
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-05-09
     */
    public function get_orderObj() {
        
        return $this->orderObj;
        
    }
    
    
    
} // end class




/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_orderPresentator.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_orderPresentator.php']);
}

?>