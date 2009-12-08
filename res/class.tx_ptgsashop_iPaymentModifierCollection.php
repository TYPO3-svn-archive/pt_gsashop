<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2008 Rainer Kuhn (kuhn@punkt.de)
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
 * Payment modifier collection interface
 *
 * $Id: class.tx_ptgsashop_iPaymentModifierCollection.php,v 1.2 2008/11/24 15:52:43 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2008-10-17
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
 * Payment modifier collection interface: to be implemented for voucher etc. to be processed by the shop core
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2008-10-17
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
interface tx_ptgsashop_iPaymentModifierCollection {
     
    /**
     * Returns the total value of all payment modifiers of the collection to be processed by the shop core
     *
     * @param   void
     * @return  double      total value of all payment modifiers
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-10-17
     */
     public function getValue();
     
    /**
     * Handles a possible value excess of the payment modifiers collection (if value of collection is bigger than order total)
     *
     * @param   double      amount of the value excess of the payment modifiers collection (excess sum if value of collection is bigger than order total, 0 otherwise)
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-10-17
     */
     public function handleValueExcess($valueExcessSum);
     
    /**
     * Returns a HTML representation of all payment modifiers of the collection
     *
     * @param   void
     * @return  string      HTML representation of all payment modifiers of the collection
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-10-17
     */
     public function getViewHtml();
     
    /**
     * Returns a plain text representation of all payment modifiers of the collection
     *
     * @param   void
     * @return  string      plain text representation of all payment modifiers of the collection
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-10-17
     */
     public function getViewPlainText();
     
    /**
     * Handles the storage of all payment modifiers of the collection into the ERP data basis
     *
     * @param  string      document number ("Vorgangsnummer") of the already saved related order document in the ERP system
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-10-17
     */
     public function storeToErp($erpDocNo);
     
    /**
     * Handles the storage of all payment modifiers of the collection into GSA shop's order archive
     *
     * @param   integer     UID of the archived order the payment modifiers are used for
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-10-17
     */
     public function storeToOrderArchive($orderId);
     
    
    
} // end class



?>