<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2005-2006 Rainer Kuhn (kuhn@punkt.de)
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
 * Dispatch cost class for the 'pt_gsashop' extension.
 *
 * $Id: class.tx_ptgsashop_dispatchCost.php,v 1.46 2008/10/16 15:02:16 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2005-08-02
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 * 
 */
 
 

/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_dispatchCostAccessor.php';  // GSA Shop database accessor class for dispatch cost data
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_lib.php';  // GSA Shop library with static methods

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_finance.php'; // library class with finance related static methods



/**
 * Dispatch cost class (based on GSA database structure)
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2005-08-02
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_dispatchCost {
    
    /**
     * Properties
     */
    protected $costTypeName = '';  // (string) name of the dispatch cost type to use / GSA: VERSART.VERSART
    protected $costUid = 0;  // (integer) UID of the dispatch cost type record to use / GSA: VERSART.NUMMER
    protected $costComp1 = NULL; // (mixed: null or double) dispatch net cost component 1 / GSA: VERSART.FLDN01
    protected $costComp2 = NULL; // (mixed: null or double) dispatch net cost component 2 / GSA: VERSART.FLDN02
    protected $costComp3 = NULL; // (mixed: null or double) dispatch net cost component 3 / GSA: VERSART.FLDN03
    protected $costComp4 = NULL; // (mixed: null or double) dispatch net cost component 4 / GSA: VERSART.FLDN04
    protected $allowanceComp1 = NULL; // (mixed: null or double) dispatch net allowance for cost component 1 / GSA: VERSART.FREIAB01
    protected $allowanceComp2 = NULL; // (mixed: null or double) dispatch net allowance for cost component 2 / GSA: VERSART.FREIAB02
    protected $allowanceComp3 = NULL; // (mixed: null or double) dispatch net allowance for cost component 3 / GSA: VERSART.FREIAB03
    protected $allowanceComp4 = NULL; // (mixed: null or double) dispatch net allowance for cost component 4 / GSA: VERSART.FREIAB04
    protected $costIsEuro = 1; // (boolean) flag wether dispatch costs  and allowances are Euro currency / GSA: VERSART.EURO
    
    protected $costTaxCode = ''; // (string) tax code for dispatch cost / relates to GSA: STEUER.CODE (ERP: currently '00'-'19' in GSA table 'STEUER')
    protected $displayName = ''; // (string) _Frontend_ only: display name of the dispatch cost type (frontend language specific)
    
    /**
     * Class Constants
     */
    const EXT_KEY     = 'pt_gsashop';                       // (string) the extension key
    const LL_FILEPATH = 'res/locallang_res_classes.xml';    // (string) path to the locallang file to use within this class
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
     
    /**
     * Class constructor: Sets the dispatch cost properties from data specified by params
     *
     * @param   string      (optional) name of the dispatch type record to use (GSA-DB 'VERSART.VERSART'). If set, the object will be built from the related GSA DB record. If not set, an "empty" object with default properties will be created.
     * @param   integer     (optional) UID of the dispatch type record to use (GSA-DB field 'VERSART.NUMMER'). This setting has no effect if 1st param not empty! If 1st param is empty and this one is set to a positive integer, the object properties will be set from the related GSA DB record. If both params are not set, an "empty" object with default properties will be created.
     * @return  void        
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-08-02
     */
    public function __construct($costTypeName='', $costUid=0) {
    
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        $this->costTypeName = (string)$costTypeName;
        $this->costUid = (integer)$costUid;
        
        // this is needed to use this method from outside TSFE, too
        $shopConfigArr = tx_ptgsashop_lib::getGsaShopConfig();
        $this->costTaxCode = (string)$shopConfigArr['dispatchTaxCode'];
        
        // if $costTypeName or $costUid is given, set object properties
        if (strlen($this->costTypeName) > 0 || $this->costUid > 0) {
            
            // set default properties from the related GSA DB data
            $this->setDispatchCostData();
        
            // frontend only: set language specific frontend display name for given dispatch cost type
            if (TYPO3_MODE == 'FE' && is_object($GLOBALS['TSFE'])) { 
                
                switch ($this->costTypeName) {
                    case $shopConfigArr['gsaDispatchTypeCostFree']:
                        $llKey = 'gsaDispatchTypeCostFree'; 
                        break;
                    case $shopConfigArr['gsaDispatchTypeInlandNet']:
                        $llKey = 'gsaDispatchTypeInlandNet'; 
                        break;
                    case $shopConfigArr['gsaDispatchTypeInlandGross']:
                        $llKey = 'gsaDispatchTypeInlandGross'; 
                        break;
                    case $shopConfigArr['gsaDispatchTypeAbroadNet']:
                        $llKey = 'gsaDispatchTypeAbroadNet'; 
                        break;
                    case $shopConfigArr['gsaDispatchTypeAbroadGross']:
                        $llKey = 'gsaDispatchTypeAbroadGross'; 
                        break;
                    default:
                        $llKey = ''; 
                        break;
                }
                
                $llFile = t3lib_extMgm::extPath(self::EXT_KEY).self::LL_FILEPATH;
                $llArray = tx_pttools_div::readLLfile($llFile);
                
                $this->displayName = tx_pttools_div::getLLL(__CLASS__.'.'.$llKey, $llArray);
            }
            
        }
        
        trace($this);
        
    }
    
    /**
     * Load from order archive: restores the object's properties of data retrieved from the order archive database. This method should be called only directly after new instantiation of the (empty) object.
     * 
     * @param   integer     UID of the related parent order record in the order archive database
     * @param   integer     UID of the related parent delivery record in the order archive database
     * @return  tx_ptgsashop_dispatchCost      object of type tx_ptgsashop_dispatchCost, "filled" with properties from order archive database
     * @author Fabrizio Branca <branca@punkt.de>
     * @since   2007-05-11
     */
    public function loadFromOrderArchive($ordersId, $deliveriesId) {

        // fetch data from table 'tx_ptgsashop_orders_dispatchcost'
        $orderAccessor = tx_ptgsashop_orderAccessor::getInstance();
        $dispatchData = $orderAccessor->selectOrdersDispatchCost($ordersId, $deliveriesId);
        
        // set properties
        $this->costTypeName = (string)$dispatchData['cost_type_name'];
        $this->costComp1 = (double)$dispatchData['cost_comp_1'];
        $this->costComp2 = (double)$dispatchData['cost_comp_2'];
        $this->costComp3 = (double)$dispatchData['cost_comp_3'];
        $this->costComp4 = (double)$dispatchData['cost_comp_4'];
        $this->allowanceComp1 = (double)$dispatchData['allowance_comp_1'];
        $this->allowanceComp2 = (double)$dispatchData['allowance_comp_2'];
        $this->allowanceComp3 = (double)$dispatchData['allowance_comp_3'];
        $this->allowanceComp4 = (double)$dispatchData['allowance_comp_4'];
        $this->costIsEuro = 1; // TODO: Constant ?
        $this->costTaxCode = (string)$dispatchData['cost_tax_code']; // TODO: oder nicht überschreiben ?

        return $this;
        
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
     
    /**
     * Sets the dispatch cost properties using data retrieved from a GSA database query
     *
     * @param   void
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-08-02
     */
    protected function setDispatchCostData() {
        
        // retrieve cost data by cost type name if it is set, retrieve by uid if cost type name is not set
        if (strlen($this->costTypeName) > 0) {
            $dispatchArr = tx_ptgsashop_dispatchCostAccessor::getInstance()->selectDispatchDataByName($this->costTypeName);
            $this->costUid = (integer)$dispatchArr['NUMMER'];
        } else {
            $dispatchArr = tx_ptgsashop_dispatchCostAccessor::getInstance()->selectDispatchDataByUid($this->costUid);
            $this->costTypeName = (string)$dispatchArr['VERSART'];
        }
        
        if (!is_null($dispatchArr['FLDN01']))       $this->costComp1 = (double)$dispatchArr['FLDN01'];
        if (!is_null($dispatchArr['FLDN02']))       $this->costComp2 = (double)$dispatchArr['FLDN02']; 
        if (!is_null($dispatchArr['FLDN03']))       $this->costComp3 = (double)$dispatchArr['FLDN03']; 
        if (!is_null($dispatchArr['FLDN04']))       $this->costComp4 = (double)$dispatchArr['FLDN04'];
        if (!is_null($dispatchArr['FREIAB01']))     $this->allowanceComp1 = (double)$dispatchArr['FREIAB01'];
        if (!is_null($dispatchArr['FREIAB02']))     $this->allowanceComp2 = (double)$dispatchArr['FREIAB02'];
        if (!is_null($dispatchArr['FREIAB03']))     $this->allowanceComp3 = (double)$dispatchArr['FREIAB03'];
        if (!is_null($dispatchArr['FREIAB04']))     $this->allowanceComp4 = (double)$dispatchArr['FREIAB04'];
        $this->costIsEuro = (boolean)$dispatchArr['EURO'];
        
    }
    
    /**
     * Returns the dispatch cost for a given articles sum
     * 
     * Note: dispatch cost allowances stay unaccounted if they are not set or if they are set to 0.0000
     *
     * @param   double      total sum of the articles to calculate the dispatch cost for
     * @return  double      dispatch cost for given order
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-08-02
     */
    public function getDispatchCostForGivenSum($articlesSumTotal) {
        
        $dispatchCostTotal = 0.00;
        
        // add dispatch cost components depending on setting of allowances and order sum 
        if ( 
             !isset($this->allowanceComp1) || 
             $this->allowanceComp1 == 0 || 
             ($this->allowanceComp1 > 0 && $articlesSumTotal < $this->allowanceComp1) 
           ) {
                // float operations may lead to precision problems (see www.php.net/float), using bcmath instead: this requires PHP to be configured with  '--enable-bcmath'
                $dispatchCostTotal = bcadd($dispatchCostTotal, $this->costComp1, 4);
                    // original calculation: $dispatchCostTotal += $this->costComp1;
        }
        if ( 
             !isset($this->allowanceComp2) || 
             $this->allowanceComp2 == 0 || 
             ($this->allowanceComp2 > 0 && $articlesSumTotal < $this->allowanceComp2) 
           ) {
                // float operations may lead to precision problems (see www.php.net/float), using bcmath instead: this requires PHP to be configured with  '--enable-bcmath'
                $dispatchCostTotal = bcadd($dispatchCostTotal, $this->costComp2, 4);
                    // original calculation: $dispatchCostTotal += $this->costComp2;
        }
        if (
             !isset($this->allowanceComp3) || 
             $this->allowanceComp3 == 0 || 
             ($this->allowanceComp3 > 0 && $articlesSumTotal < $this->allowanceComp3) 
           ) {
                // float operations may lead to precision problems (see www.php.net/float), using bcmath instead: this requires PHP to be configured with  '--enable-bcmath'
                $dispatchCostTotal = bcadd($dispatchCostTotal, $this->costComp3, 4);
                    // original calculation: $dispatchCostTotal += $this->costComp3;
        }
        if ( 
             !isset($this->allowanceComp4) || 
             $this->allowanceComp4 == 0 || 
             ($this->allowanceComp4 > 0 && $articlesSumTotal < $this->allowanceComp4) 
           ) {
                // float operations may lead to precision problems (see www.php.net/float), using bcmath instead: this requires PHP to be configured with  '--enable-bcmath'
                $dispatchCostTotal = bcadd($dispatchCostTotal, $this->costComp4, 4);
                    // original calculation: $dispatchCostTotal += $this->costComp4;
        }
        
        // HOOK: allow multiple hooks to manipulate $dispatchCostTotal
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['dispatchCost_hooks']['getDispatchCostForGivenSumHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['dispatchCost_hooks']['getDispatchCostForGivenSumHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $dispatchCostTotalBeforeHooking = $dispatchCostTotal;
                $dispatchCostTotal = (double)$hookObj->getDispatchCostForGivenSumHook($this, $dispatchCostTotalBeforeHooking); // $this is passed as a reference (since PHP5) and can be manipulated in the hook method
            }
        }
        
        trace((double)$dispatchCostTotal, 0, '$dispatchCostTotal');
        return (double)$dispatchCostTotal;
        
    }
    
    /**
     * Returns the tax for the dispatch cost
     *
     * @param   double      total sum of the articles to calculate the dispatch cost's tax for
     * @param   boolean     flag wether the current dispatch cost prices are net (1=net prices, 0=gross prices)
     * @param   boolean     (optional) flag wether the order is tax free (default:0)
     * @return  double      tax for the dispatch cost
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-08-02
     */
    public function getDispatchCostTax($dispatchCost, $dispatchCostIsNet, $isTaxFreeOrder=0) {
        
        $dispatchCostTax = 0.00; // (double)
        
        if ((boolean)$isTaxFreeOrder != 1) {
            
            if ($dispatchCostIsNet == 1) {
                $dispatchCostTax = tx_pttools_finance::getTaxCostFromNet($dispatchCost, $this->getTaxRate());
            } else {
                $dispatchCostTax = tx_pttools_finance::getTaxCostFromGross($dispatchCost, $this->getTaxRate());
            }
            
        }
        
        return $dispatchCostTax;
        
    }
    
    /**
     * Returns the dispatch cost's current tax rate
     * 
     * @param   void
     * @return  double      current tax rate of the dispatch cost (double with 4 digits after the decimal point)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-30
     */
    public function getTaxRate() {
        
        $taxRate = tx_ptgsashop_lib::getTaxRate($this->get_costTaxCode());
        
        return round($taxRate, 4);
        
    }
    
    
    
    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2005-11-25
     */
    public function get_costTypeName() {
        
        return $this->costTypeName;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer      property value
     * @since   2007-10-31
     */
    public function get_costUid() {
        
        return $this->costUid;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  mixed       null or double: the property value
     * @since   2006-08-30
     */
    public function get_costComp1() {
        
        return $this->costComp1;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  mixed       null or double: the property value
     * @since   2006-08-30
     */
    public function get_costComp2() {
        
        return $this->costComp2;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  mixed       null or double: the property value
     * @since   2006-08-30
     */
    public function get_costComp3() {
        
        return $this->costComp3;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  mixed       null or double: the property value
     * @since   2006-08-30
     */
    public function get_costComp4() {
        
        return $this->costComp4;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  mixed       null or double: the property value
     * @since   2006-08-30
     */
    public function get_allowanceComp1() {
        
        return $this->allowanceComp1;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  mixed       null or double: the property value
     * @since   2006-08-30
     */
    public function get_allowanceComp2() {
        
        return $this->allowanceComp2;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  mixed       null or double: the property value
     * @since   2006-08-30
     */
    public function get_allowanceComp3() {
        
        return $this->allowanceComp3;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  mixed       null or double: the property value
     * @since   2006-08-30
     */
    public function get_allowanceComp4() {
        
        return $this->allowanceComp4;
        
    }
    
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2005-09-20
     */
    public function get_costTaxCode() {
        
        return $this->costTaxCode;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      _frontend_ display name of the cost type
     * @since   2007-06-28
     */
    public function get_displayName() {
        
        return $this->displayName;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   string      property value       
     * @return  void
     * @since   2007-10-30
     */
    public function set_costTypeName($costTypeName) {
        
        $this->costTypeName = (string)$costTypeName;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   mixed       null or double: the property value to set (differentiates between 0 (=price 0.00) and NULL (price not set))  
     * @return  void
     * @since   2007-10-30
     */
    public function set_costComp1($costComp1) {
        
        $this->costComp1 = (is_null($costComp1) ? NULL : (double)$costComp1);
        
    }
    
    /**
     * Returns the property value
     *
     * @param   mixed       null or double: the property value to set (differentiates between 0 (=price 0.00) and NULL (price not set))  
     * @return  void
     * @since   2007-10-30
     */
    public function set_costComp2($costComp2) {
        
        $this->costComp2 = (is_null($costComp2) ? NULL : (double)$costComp2);
        
    }
    
    /**
     * Returns the property value
     *
     * @param   mixed       null or double: the property value to set (differentiates between 0 (=price 0.00) and NULL (price not set))  
     * @return  void
     * @since   2007-10-30
     */
    public function set_costComp3($costComp3) {
        
        $this->costComp3 = (is_null($costComp3) ? NULL : (double)$costComp3);
        
    }
    
    /**
     * Returns the property value
     *
     * @param   mixed       null or double: the property value to set (differentiates between 0 (=price 0.00) and NULL (price not set))  
     * @return  void
     * @since   2007-10-30
     */
    public function set_costComp4($costComp4) {
        
        $this->costComp4 = (is_null($costComp4) ? NULL : (double)$costComp4);
        
    }
    
    /**
     * Returns the property value
     *
     * @param   mixed       null or double: the property value to set (differentiates between 0 (=price 0.00) and NULL (price not set))  
     * @return  void
     * @since   2007-10-30
     */
    public function set_allowanceComp1($allowanceComp1) {
        
        $this->allowanceComp1 = (is_null($allowanceComp1) ? NULL : (double)$allowanceComp1);
        
    }
    
    /**
     * Returns the property value
     *
     * @param   mixed       null or double: the property value to set (differentiates between 0 (=price 0.00) and NULL (price not set))  
     * @return  void
     * @since   2007-10-30
     */
    public function set_allowanceComp2($allowanceComp2) {
        
        $this->allowanceComp2 = (is_null($allowanceComp2) ? NULL : (double)$allowanceComp2);
        
    }
    
    /**
     * Returns the property value
     *
     * @param   mixed       null or double: the property value to set (differentiates between 0 (=price 0.00) and NULL (price not set))  
     * @return  void
     * @since   2007-10-30
     */
    public function set_allowanceComp3($allowanceComp3) {
        
        $this->allowanceComp3 = (is_null($allowanceComp3) ? NULL : (double)$allowanceComp3);
        
    }
    
    /**
     * Returns the property value
     *
     * @param   mixed       null or double: the property value to set (differentiates between 0 (=price 0.00) and NULL (price not set))  
     * @return  void
     * @since   2007-10-30
     */
    public function set_allowanceComp4($allowanceComp4) {
        
        $this->allowanceComp4 = (is_null($allowanceComp4) ? NULL : (double)$allowanceComp4);
        
    }
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_dispatchCost.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_dispatchCost.php']);
}

?>