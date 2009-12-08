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
 * Compound article class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_compoundArticle.php,v 1.7 2007/10/15 13:03:25 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2007-01-05
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_article.php'; // GSA shop article class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_componentArticleCollection.php'; // GSA shop component article collection class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_iApplSpecArticleDataObj.php'; // GSA shop application specific article data interface
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_lib.php';  // GSA Shop library with static methods

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general helper library class



/**
 * Compound article class (composite online articles, available for direct order) 
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2007-01-05
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_compoundArticle extends tx_ptgsashop_article {
    
    /**
     * Additional Properties
     */
    protected $componentArtCollObj;  // (object of type tx_ptgsashop_componentArticleCollection) component article collection object
    protected $applSpecCompoundDataObj;  // (object implementing interface tx_ptgsashop_iApplSpecArticleDataObj) application specific article data object for the compound article
    
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR
     **************************************************************************/
     
    /**
     * Class constructor: calls the parent constructor and sets additional properties
     *
     * @param   integer     UID of the compound article definition in GSA DB table ARTIKEL, see tx_ptgsashop_baseArticle::__construct()
     * @param   integer     (optional) see tx_ptgsashop_baseArticle::__construct()
     * @param   integer     (optional) see tx_ptgsashop_baseArticle::__construct()
     * @param   integer     (optional) see tx_ptgsashop_baseArticle::__construct()
     * @param   string      (optional) see tx_ptgsashop_baseArticle::__construct()
     * @param   boolean     (optional) see tx_ptgsashop_baseArticle::__construct()
     * @return  void
     * @throws  tx_pttools_exception   if the article is not a valid component article
     * @see     tx_ptgsashop_baseArticle::__construct()
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-01-05
     */
    public function __construct($id, $priceCategory=1, $customerId=0, $quantity=1, $date='', $imageFlag=0) {
        
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        parent::__construct($id, $priceCategory, $customerId, $quantity, $date, $imageFlag);
        
        // set empty componentArticleCollection - this has to be re-set manually with setter method
        $this->componentArtCollObj = new tx_ptgsashop_componentArticleCollection;
        
        
        // TODO: this has to be changed to bcmath functions!!
        
        
        // if no retail prices are found in GSA compound article: set compound article retail price 1 as addition of component article prices 
        if ($this->basicRetailPriceCategory1 == 0 && $this->basicRetailPriceCategory2 == 0 && $this->basicRetailPriceCategory3 == 0 
            && $this->basicRetailPriceCategory4 == 0 && $this->basicRetailPriceCategory5 == 0) {
            $this->basicRetailPriceCategory1 = 0.00;
            foreach ($this->componentArtCollObj as $componentArtObj) {
                // float operations may lead to precision problems (see www.php.net/float), using bcmath instead: this requires PHP to be configured with  '--enable-bcmath'
                $this->basicRetailPriceCategory1 = (double)bcadd($this->basicRetailPriceCategory1, $componentArtObj->getItemSubtotal(1), 4); // single component net price multiplied with component quantity
                     // original calculation: $this->basicRetailPriceCategory1 += $componentArtObj->getItemSubtotal(1);  
            }
            $this->grossPriceFlag = 0; // important to know that current price is a net price
        }
            
        // if inland tax code not set in GSA compound article: set tax code to code of highest tax rate of component articles  #### TODO: prüfen, ob in aktueller GSA-Version Ust-Code oder -Satz im Artikel gespeichert wird!!
        if (empty($this->taxCodeInland) || $this->taxCodeInland = '00') {
            $maxTaxRateInland = 0;
            $this->taxCodeInland = '00';
            foreach ($this->componentArtCollObj as $componentArtObj) {
                $compTaxRateInland = tx_ptgsashop_lib::getTaxRate($componentArtObj->get_taxCodeInland(), $this->date);
                if ($compTaxRateInland > $maxTaxRateInland) {
                    $maxTaxRateInland = $compTaxRateInland;
                    $this->taxCodeInland = $componentArtObj->get_taxCodeInland();
                }
            }
        }
       
        // if abroad tax code not set in GSA compound article: set tax code to code of highest tax rate of component articles  #### TODO: prüfen, ob in aktueller GSA-Version Ust-Code oder -Satz im Artikel gespeichert wird!!
       if (empty($this->taxCodeAbroad) || $this->taxCodeAbroad = '00') {
            $maxTaxRateAbroad = 0;
            $this->taxCodeAbroad = '00';
            foreach ($this->componentArtCollObj as $componentArtObj) {
                $compTaxRateAbroad = tx_ptgsashop_lib::getTaxRate($componentArtObj->get_taxCodeAbroad(), $this->date);
                if ($compTaxRateAbroad > $maxTaxRateAbroad) {
                    $maxTaxRateAbroad = $compTaxRateAbroad;
                    $this->taxCodeAbroad = $componentArtObj->get_taxCodeAbroad();
                }
            }
        }
        
    }
    
    
    
    /***************************************************************************
     *   REDEFINED INHERITED METHODS
     **************************************************************************/
    
    
    
    /***************************************************************************
     *   ADDITIONAL METHODS
     **************************************************************************/
     
    /**
     * Adds a component to the component article collection object of the compound article
     *
     * @param   tx_ptgsashop_componentArticle     component article to add, object of type tx_ptgsashop_componentArticle
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-01-08
     */
    public function addComponent(tx_ptgsashop_componentArticle $componentArticleObj) {
        
        return $this->componentArtCollObj->addItem($componentArticleObj);
        
    }
     
    /**
     * Removes a component article from the component article collection object of the compound article
     *
     * @param   tx_ptgsashop_componentArticle      component article to remove, object of type tx_ptgsashop_componentArticle
     * @return  boolean     status of operation (FALSE if article did not exist in collection or on quantity mismatch, TRUE otherwise)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-01-08
     */
    public function removeComponent(tx_ptgsashop_componentArticle $componentArticleObj) {
        
        return $this->componentArtCollObj->removeItem($componentArticleObj);
        
    }
    
    
    
    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
    
    /**
     * Returns the component article collection object of the compound article
     *
     * @param   void
     * @return  tx_ptgsashop_componentArticleCollection      object of type tx_ptgsashop_componentArticleCollection
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-01-05
     */
    public function get_componentArtCollObj() {
        
        return $this->componentArtCollObj;
        
    }
    
    /**
     * Sets the component article collection object of the compound article
     *
     * @param   tx_ptgsashop_componentArticleCollection      component article collection object for the compound article, object of type tx_ptgsashop_componentArticleCollection
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-05-04
     */
    public function set_componentArtCollObj(tx_ptgsashop_componentArticleCollection $componentArtCollObj) {
        
        $this->componentArtCollObj = $componentArtCollObj;
        
    }
    
    /**
     * Returns the application specific article data object of the compound article
     *
     * @param   void
     * @return  tx_ptgsashop_iApplSpecArticleDataObj      application specific article data object for the compound article, object implementing interface tx_ptgsashop_iApplSpecArticleDataObj
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-01-09
     */
    public function get_applSpecCompoundDataObj() {
        
        return $this->applSpecCompoundDataObj;
        
    }
    
    /**
     * Sets the application specific article data object of the compound article
     *
     * @param   tx_ptgsashop_iApplSpecArticleDataObj      application specific article data object for the compound article, object implementing interface tx_ptgsashop_iApplSpecArticleDataObj
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-01-09
     */
    public function set_applSpecCompoundDataObj(tx_ptgsashop_iApplSpecArticleDataObj $applSpecCompoundDataObj) {
        
        $this->applSpecCompoundDataObj = $applSpecCompoundDataObj;
        
    }
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_compoundArticle.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_compoundArticle.php']);
}

?>