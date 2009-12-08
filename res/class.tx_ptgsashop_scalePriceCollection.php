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
 * Scale price collection class for articles of the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_scalePriceCollection.php,v 1.11 2008/10/16 15:15:13 ry37 Exp $
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
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_scalePrice.php';// GSA Shop article scale price class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_articleAccessor.php';  // GSA Shop database accessor class for articles
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_lib.php';  // GSA Shop library with static methods

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_objectCollection.php'; // abstract object collection class



/**
 * Scale price collection class for articles: collection IDs are the scale price quantities, ordered descending
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2007-10-23
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_scalePriceCollection extends tx_pttools_objectCollection {
    
    /**
     * Properties
     */
    
    /**
     * @var integer     tolerance for scale prices in percent: to be added to the processed article quantity for scale price calculation (the resulting floating point number will be rounded to an integer). Example: There are the following scale prices set for an article: 1+ pieces => price A, from 10+ pieces => price B,  100+ pieces => price C: the tolerance setting of this option set to 5 (percent) results in 1-9 pieces => price A, 10-94 pieces => price B, 95+ pieces => price C.
     */
    protected $scalePriceQtyTolerance = 0; 
    
    /**
     * Class Constants
     */
    const EXT_KEY     = 'pt_gsashop';                       // (string) the extension key
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR
     **************************************************************************/
     
    /**
     * Class constructor: fills the scale price collection object for an article specified by uid; collection IDs are the scale price quantities, ordered descending
     *
     * @param   integer     UID of the related article in the GSA database (positive integer); use 0 to create a new/empty collection
     * @return  void     
     * @throws  tx_pttools_exception   if params are not valid
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-10-23
     */
    public function __construct($articleUid=0) { 
        
        if (!is_numeric($articleUid) || $articleUid < 0) {
            throw new tx_pttools_exception('Parameter error', 3, 'First parameter for '.__METHOD__.' is not a valid UID');
        }
        
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        // retrieve scale price objects for collection
        if ($articleUid > 0) {
            $quantitiesArr = tx_ptgsashop_articleAccessor::getInstance()->selectScalePriceQuantities($articleUid);
            foreach ($quantitiesArr as $scalePriceQty) {
                $this->addItem(new tx_ptgsashop_scalePrice($articleUid, $scalePriceQty));
            }
        }
    
        // retrieve scale price quantity tolerance
        $this->scalePriceQtyTolerance = tx_ptgsashop_lib::getGsaShopConfig('scalePriceQtyTolerance');
        
    }     
    
    
    
    /***************************************************************************
     *   INHERITED METHODS
     **************************************************************************/
     
    /**
     * Adds one item to the collection (with scale price quantity as collection id) and sorts the scale price items by quantity after addding the element
     *
     * @param   tx_ptgsashop_scalePrice      scale price object to add
     * @return  void
     * @see     tx_pttools_objectCollection::addItem()
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-10-23
     */ 
    public function addItem(tx_ptgsashop_scalePrice $itemObj) {
        
        parent::addItem($itemObj, $itemObj->get_quantity());
        krsort($this->itemsArr);
        
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
     
    /**
     * Returns the appropriate scale price item from the collection for a given article quantity (scale price quantity tolerance -if configured- will be taken into account)
     *
     * @param   integer     article quantity to get the appropriate scale price item for
     * @return  tx_ptgsashop_scalePrice      appropriate scale price object for the given article quantity
     * @see     tx_pttools_objectCollection::addItem()
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-10-24
     */ 
    public function getItemByQuantity($articleQuantity) {
        
        $appropriateScaleQty = 1;
        
        // add scale price quantity tolerance (if configured) to processed quantity
        if ($this->scalePriceQtyTolerance > 0) {
            $articleQuantity = round($articleQuantity * (1 + $this->scalePriceQtyTolerance / 100));
        }
        
        // retrieve appropriate scale price object for given quantity
        foreach ($this->itemsArr as $scalePriceQty=>$scalePriceObj) {
            if ($articleQuantity >= $scalePriceQty) {
                $appropriateScaleQty = $scalePriceQty;
                break;
            }
        }
        
        return $this->itemsArr[$appropriateScaleQty];
        
    }
    
    
    
    
} // end class




/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_scalePriceCollection.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_scalePriceCollection.php']);
}

?>