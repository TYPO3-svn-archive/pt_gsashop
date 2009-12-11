<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2007 Fabrizio Branca (branca@punkt.de)
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
 * Image collection class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_articleImageCollection.php,v 1.5 2008/06/16 09:48:56 ry37 Exp $
 *
 * @author  Fabrizio Branca <branca@punkt.de>
 * @since   2007-11-28
 */ 


/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_articleImage.php'; // GSA Shop article image class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_articleImageAccessor.php';  // GSA Shop database accessor class for images

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_objectCollection.php'; // abstract object collection class



/**
 * Image collection class
 *
 * @author      Fabrizio Branca <branca@punkt.de>
 * @since       2007-11-28
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_articleImageCollection extends tx_pttools_objectCollection {
    
    
    /***************************************************************************
     *   CONSTRUCTOR
     **************************************************************************/
     
    /**
     * Class constructor: Creates an order wrapper collection object prefilled with order wrapper objects selected by param specification
     *
     * @param   integer     (optional) article ID / GSA: ARTIKEL.NUMMER
     * @return  void       
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-11-28
     */
    public function __construct($gsa_art_nummer = NULL) { 
    
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        if (!empty($gsa_art_nummer)) {
            $dataArray = tx_ptgsashop_articleImageAccessor::selectByGsaArtNummer($gsa_art_nummer);
            foreach ($dataArray as $imageData) {
                $this->addItem(new tx_ptgsashop_articleImage(NULL, $imageData));
            }
        }
        
        trace($this, 0, 'New '.__CLASS__.' object created');
        
    }   
    
    
    
    /***************************************************************************
     *   INHERITED METHODS
     **************************************************************************/
     
    /**
     * Adds one item to the collection
     *
     * @param   tx_ptgsashop_articleImage      object to add
     * @param   integer     (optional) array key, see tx_pttools_objectCollection::addItem() 
     * @return  void
     * @see     tx_pttools_objectCollection::addItem()
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-11-28
     */ 
    public function addItem(tx_ptgsashop_articleImage $itemObj, $id=0) {
        
        parent::addItem($itemObj, $id);
        
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**
     * Get all properties into one arrays
     *
     * @param 	string	property name
     * @return 	array
     * @author	Fabrizio Branca <branca@punkt.de>
     * @since	2008-01-24
     */
    public function getPropertyArray($property) {
        $propertyArray = array();
        
        /* @var img tx_ptgsashop_articleImage */
        foreach ($this as $img) {
            $getter = 'get_'.$property;
            $propertyArray[] = $img->$getter();
        }
        
        return $propertyArray;
    }
    
    
} // end class




/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_articleImageCollection.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_articleImageCollection.php']);
}

?>