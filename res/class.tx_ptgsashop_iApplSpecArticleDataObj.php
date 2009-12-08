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
 * Application specific article data object interface
 * This interface has to be implemented by all "application specific article data" classes used by the 'pt_gsashop' extension.
 *
 * $Id: class.tx_ptgsashop_iApplSpecArticleDataObj.php,v 1.5 2007/10/15 13:03:25 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2007-01-09
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_iApplSpecDataObj.php'; // interface for an application specific data object



/**
 * Application specific article data object interface: This interface has to be implemented by all "application specific article data" classes used by the 'pt_gsashop' extension.
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2007-01-09
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
interface tx_ptgsashop_iApplSpecArticleDataObj extends tx_ptgsashop_iApplSpecDataObj {
     
    /**
     * Processes additional action on the addItem() call of an article
     *
     * @param   tx_ptgsashop_iApplSpecArticleDataObj      object implementing interface tx_ptgsashop_iApplSpecArticleDataObj: the application specific article data object of the article to add
     * @param   integer     quantity of the article to add
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-21
     */
     public function processOnAddItem(tx_ptgsashop_iApplSpecArticleDataObj $applSpecDataObj, $quantity);
     
    /**
     * Processes additional action on the removeItem() call of an article
     *
     * @param   tx_ptgsashop_iApplSpecArticleDataObj      object implementing interface tx_ptgsashop_iApplSpecArticleDataObj: the application specific article data object of the article to remove
     * @param   integer     quantity of the article to remove
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-21
     */
     public function processOnRemoveItem(tx_ptgsashop_iApplSpecArticleDataObj $applSpecDataObj, $quantity);
     
    /**
     * Processes additional action on the updateItemQuantity() call of an article
     *
     * @param   integer     quantity of the article to update
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-21
     */
     public function processOnUpdateItemQuantity($quantity);
    
    
    
} // end class



?>