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
 * Component article collection class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_componentArticleCollection.php,v 1.4 2007/10/15 13:03:25 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2007-01-04 / 2007-05-04
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_articleCollection.php';// GSA shop article collection class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_componentArticle.php'; // GSA shop component article class

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function



/**
 * Component article collection class: $itemsArr (inherited from parent class) contains only component articles (objects of type tx_ptgsashop_componentArticle)
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2007-01-04 / 2007-05-04
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_componentArticleCollection extends tx_ptgsashop_articleCollection {
    
    
    /***************************************************************************
     *   CONSTRUCTOR
     **************************************************************************/
     
     
    
    /***************************************************************************
     *   REDECLARED METHODS
     **************************************************************************/
    
    /**
     * Adds a component article item (with arbitrary internal quantity) to the article collection
     *
     * @param   tx_ptgsashop_componentArticle     component article to add, object of type tx_ptgsashop_componentArticle
     * @return  void
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-05-04
     */
    public function addItem(tx_ptgsashop_componentArticle $componentArticleObj) { 
        
        parent::addItem($componentArticleObj);
        
    }
    
    /**
     * Removes an component article item (with arbitrary internal quantity) from the article collection 
     *
     * @param   tx_ptgsashop_componentArticle      component article to remove, object of type tx_ptgsashop_componentArticle
     * @return  boolean     status of operation (false if article did not exist in collection or on quantity mismatch, true otherwise)
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-05-04
     */
    public function removeItem(tx_ptgsashop_componentArticle $componentArticleObj) { 
        
        parent::removeItem($componentArticleObj);
        
    }
    
    
    
} // end class




/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_componentArticleCollection.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_componentArticleCollection.php']);
}

?>