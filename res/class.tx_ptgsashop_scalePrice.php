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
 * Scale price class for articles of the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_scalePrice.php,v 1.7 2008/03/19 16:19:31 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2007-10-23
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_articleAccessor.php';  // GSA Shop database accessor class for articles

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general helper library class



/**
 * Scale price class for articles (based on GSA database structure)
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2007-10-23 (based on code from tx_ptgsashop_baseArticle: since 2005-07-19)
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_scalePrice {

    /**
     * Properties
     */
    protected $articleUid = 0;      // (integer) article ID / GSA: VKPREIS.ARTINR (points to ARTIKEL.NUMMER) 
    protected $quantity = 0;        // (integer) minimum quantity of the article to use this scale price / GSA-DB: VKPREIS.ABMENGE (ERP-GUI: "Menge ab")
    
    // default article pricing data properties
    protected $basicRetailPriceCategory1 = NULL; // (mixed: null or double) basic retail price for price category 1 / GSA-DB: VKPREIS.PR01
    protected $basicRetailPriceCategory2 = NULL; // (mixed: null or double) basic retail price for price category 2 / GSA-DB: VKPREIS.PR02
    protected $basicRetailPriceCategory3 = NULL; // (mixed: null or double) basic retail price for price category 3 / GSA-DB: VKPREIS.PR03
    protected $basicRetailPriceCategory4 = NULL; // (mixed: null or double) basic retail price for price category 4 / GSA-DB: VKPREIS.PR04
    protected $basicRetailPriceCategory5 = NULL; // (mixed: null or double) basic retail price for price category 5 / GSA-DB: VKPREIS.PR05
    protected $specialOfferFlag = 0;             // (boolean) flag whether there's a special offer for this article / GSA-DB: VKPREIS.AKTION 
    protected $specialOfferRetailPrice = NULL;   // (mixed: null or double) special offer retail price / GSA-DB: VKPREIS.PR99
    protected $specialOfferStartDate;            // (string) special offer start date (date string format: YYYY-MM-DD) / GSA-DB: VKPREIS.DATUMVON 
    protected $specialOfferEndDate;              // (string) special offer start date (date string format: YYYY-MM-DD) / GSA-DB: VKPREIS.DATUMBIS 
    
    protected $isDeleted = 0;           // (boolean) flag whether the scale price has been marked as deleted
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
     
    /**
     * Class constructor: sets the properties of a scale price
     *
     * @param   integer     UID of the related article in the GSA database (positive integer); use 0 to create a scale price for a new/empty article
     * @param   integer     (optional) purchase quantity of the article (Note: multiple pricing data records for different quantities!)
     * @return  void         
     * @throws  tx_pttools_exception   if params are not valid
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-10-23
     */
    public function __construct($articleUid, $quantity=1) {
        
        if (!is_numeric($articleUid) || $articleUid < 0) {
            throw new tx_pttools_exception('Parameter error', 3, 'First parameter for '.__METHOD__.' is not a valid UID');
        }
        if (!is_numeric($quantity) || $quantity < 1) {
            throw new tx_pttools_exception('Parameter error', 3, '2nd parameter for '.__METHOD__.' is not valid');
        }
        
        // set properties from constructor parameters
        $this->articleUid = (integer)$articleUid;
        $this->quantity = (integer)$quantity;
        
        // for non-new article prices: retrieve pricing data, set properties from external data sources (database etc.)
        if ($articleUid > 0) {
            $this->setScalePriceData();
        }
        
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
     
    /**
     * Sets the default pricing properties of an article using data retrieved from a GSA database query
     *
     * @param   void
     * @return  void
     * @throws  tx_pttools_exception    if no scale price data found in the database
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-22 (taken from tx_ptgsashop_baseArticle)
     */
    protected function setScalePriceData() {
        
        $defaultPriceDataArr = tx_ptgsashop_articleAccessor::getInstance()->selectRetailPricingData($this->articleUid, $this->quantity);
        
        if (!is_array($defaultPriceDataArr)) {
            throw new tx_pttools_exception('No valid article pricing data found', 3,
                                           'tx_ptgsashop_articleAccessor::getInstance()->selectRetailPricingData('.$this->articleUid.', '.$this->quantity.') did not return any data.');
        }
        
        if (!is_null($defaultPriceDataArr['PR01']))      $this->basicRetailPriceCategory1 = (double)$defaultPriceDataArr['PR01'];
        if (!is_null($defaultPriceDataArr['PR02']))      $this->basicRetailPriceCategory2 = (double)$defaultPriceDataArr['PR02'];
        if (!is_null($defaultPriceDataArr['PR03']))      $this->basicRetailPriceCategory3 = (double)$defaultPriceDataArr['PR03'];
        if (!is_null($defaultPriceDataArr['PR04']))      $this->basicRetailPriceCategory4 = (double)$defaultPriceDataArr['PR04'];
        if (!is_null($defaultPriceDataArr['PR05']))      $this->basicRetailPriceCategory5 = (double)$defaultPriceDataArr['PR05'];
        if (!is_null($defaultPriceDataArr['AKTION']))    $this->specialOfferFlag = (bool)$defaultPriceDataArr['AKTION'];
        if (!is_null($defaultPriceDataArr['PR99']))      $this->specialOfferRetailPrice = (double)$defaultPriceDataArr['PR99'];
        if (!is_null($defaultPriceDataArr['DATUMVON']))  $this->specialOfferStartDate = (string)$defaultPriceDataArr['DATUMVON'];
        if (!is_null($defaultPriceDataArr['DATUMBIS']))  $this->specialOfferEndDate = (string)$defaultPriceDataArr['DATUMBIS'];
        
    }
    
    
    
    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer      property value
     * @since   2007-10-23
     */
    public function get_articleUid() {
        
        return $this->articleUid;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer      property value
     * @since   2007-10-23
     */
    public function get_quantity() {
        
        return $this->quantity;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  mixed       null or double: the property value
     * @since   2007-10-23
     */
    public function get_basicRetailPriceCategory1() {
        
        return $this->basicRetailPriceCategory1;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  mixed       null or double: the property value
     * @since   2007-10-23
     */
    public function get_basicRetailPriceCategory2() {
        
        return $this->basicRetailPriceCategory2;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  mixed       null or double: the property value
     * @since   2007-10-23
     */
    public function get_basicRetailPriceCategory3() {
        
        return $this->basicRetailPriceCategory3;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  mixed       null or double: the property value
     * @since   2007-10-23
     */
    public function get_basicRetailPriceCategory4() {
        
        return $this->basicRetailPriceCategory4;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  mixed       null or double: the property value
     * @since   2007-10-23
     */
    public function get_basicRetailPriceCategory5() {
        
        return $this->basicRetailPriceCategory5;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  boolean      property value
     * @since   2007-10-23
     */
    public function get_specialOfferFlag() {
        
        return $this->specialOfferFlag;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  mixed       null or double: the property value
     * @since   2007-10-23
     */
    public function get_specialOfferRetailPrice() {
        
        return $this->specialOfferRetailPrice;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2007-10-23
     */
    public function get_specialOfferStartDate() {
        
        return $this->specialOfferStartDate;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2007-10-23
     */
    public function get_specialOfferEndDate() {
        
        return $this->specialOfferEndDate;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  boolean     property value
     * @since   2008-03-03
     */
    public function get_isDeleted() {
        
        return $this->isDeleted;
        
    }
    
    /**
     * Sets the property value
     *
     * @param   mixed       null or double: the property value to set (differentiates between 0 (=price 0.00) and NULL (price not set))
     * @return  void
     * @since   2007-10-17
     */
    public function set_basicRetailPriceCategory1($basicRetailPriceCategory1) {
        
        $this->basicRetailPriceCategory1 = (is_null($basicRetailPriceCategory1) ? NULL : (double)$basicRetailPriceCategory1);
        
    }
    
    /**
     * Sets the property value
     *
     * @param   mixed       null or double: the property value to set (differentiates between 0 (=price 0.00) and NULL (price not set))      
     * @return  void
     * @since   2007-10-17
     */
    public function set_basicRetailPriceCategory2($basicRetailPriceCategory2) {
        
        $this->basicRetailPriceCategory2 = (is_null($basicRetailPriceCategory2) ? NULL : (double)$basicRetailPriceCategory2);
        
    }
    
    /**
     * Sets the property value
     *
     * @param   mixed       null or double: the property value to set (differentiates between 0 (=price 0.00) and NULL (price not set))      
     * @return  void
     * @since   2007-10-17
     */
    public function set_basicRetailPriceCategory3($basicRetailPriceCategory3) {
        
        $this->basicRetailPriceCategory3 = (is_null($basicRetailPriceCategory3) ? NULL : (double)$basicRetailPriceCategory3);
        
    }
    
    /**
     * Sets the property value
     *
     * @param   mixed       null or double: the property value to set (differentiates between 0 (=price 0.00) and NULL (price not set))     
     * @return  void
     * @since   2007-10-17
     */
    public function set_basicRetailPriceCategory4($basicRetailPriceCategory4) {
        
        $this->basicRetailPriceCategory4 = (is_null($basicRetailPriceCategory4) ? NULL : (double)$basicRetailPriceCategory4);
        
    }
    
    /**
     * Sets the property value
     *
     * @param   mixed       null or double: the property value to set (differentiates between 0 (=price 0.00) and NULL (price not set))    
     * @return  void
     * @since   2007-10-17
     */
    public function set_basicRetailPriceCategory5($basicRetailPriceCategory5) {
        
        $this->basicRetailPriceCategory5 = (is_null($basicRetailPriceCategory5) ? NULL : (double)$basicRetailPriceCategory5);
        
    }
    
    /**
     * Sets the property value
     *
     * @param   boolean       property value       
     * @return  void
     * @since   2007-10-17
     */
    public function set_specialOfferFlag($specialOfferFlag) {
        
        $this->specialOfferFlag = (boolean) $specialOfferFlag;
        
    }
    
    /**
     * Sets the property value
     *
     * @param   mixed       null or double: the property value to set (differentiates between 0 (=price 0.00) and NULL (price not set))
     * @return  void
     * @since   2007-10-17
     */
    public function set_specialOfferRetailPrice($specialOfferRetailPrice) {
        
        $this->specialOfferRetailPrice = (is_null($specialOfferRetailPrice) ? NULL : (double)$specialOfferRetailPrice);
        
    }
    
    /**
     * Sets the property value
     *
     * @param   string       special offer start date (date string format: YYYY-MM-DD)      
     * @return  void
     * @since   2007-10-17
     */
    public function set_specialOfferStartDate($specialOfferStartDate) {
        
        $this->specialOfferStartDate = (string) $specialOfferStartDate;
        
    }
    
    /**
     * Sets the property value
     *
     * @param   string       special offer start date (date string format: YYYY-MM-DD)      
     * @return  void
     * @since   2007-10-17
     */
    public function set_specialOfferEndDate($specialOfferEndDate) {
        
        $this->specialOfferEndDate = (string) $specialOfferEndDate;
        
    }
    
    /**
     * Sets the property value
     *
     * @param   boolean       property value       
     * @return  void
     * @since   2008-03-03
     */
    public function set_isDeleted($isDeleted) {
        
        $this->isDeleted = (boolean) $isDeleted;
        
    }
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_scalePrice.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_scalePrice.php']);
}

?>