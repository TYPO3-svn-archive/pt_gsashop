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
 * Payment method class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_paymentMethod.php,v 1.9 2007/10/15 13:03:25 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2007-05-15
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_paymentMethod.php';// combined GSA/TYPO3 payment method class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function


/**
 * Payment method class for shop orders
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2007-05-15
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_paymentMethod extends tx_ptgsauserreg_paymentMethod  {
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
    
    /**
     * Class constructor: calls the parent constructor
     * 
     * @param   string      (optional) see tx_ptgsauserreg_paymentMethod::__construct()    
     * @return  void     
     * @see     tx_ptgsauserreg_paymentMethod::__construct()
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-05-15
     */
     public function __construct($method='bt') {
        
        trace('***** Creating new '.__CLASS__.' object. *****');
        parent::__construct($method);
        
    }

    /**
     * Load from order archive: restores the object's properties of data retrieved from the order archive database. This method should be called only directly after new instantiation of the (empty) object.
     * 
     * @param   integer     UID of the related parent order record in the order archive database
     * @return  tx_ptgsashop_paymentMethod      object of type tx_ptgsashop_paymentMethod, "filled" with properties from order archive database
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-04
     */
    public function loadFromOrderArchive($ordersId) {
        
        $orderAccessor = tx_ptgsashop_orderAccessor::getInstance();

        // Fetch Data from table 'tx_ptgsashop_orders_paymentmethods'
        $payMethData = $orderAccessor->selectOrdersPaymentmethod($ordersId);

        // set properties
        $this->method =             (string)$payMethData['method'];  
        
        $this->epaymentSuccess =    (boolean)$payMethData['epayment_success'];  
        $this->epaymentTransId =    (string)$payMethData['epayment_trans_id'];    
        $this->epaymentRefId =      (string)$payMethData['epayment_ref_id'];      
        $this->epaymentShortId =    (string)$payMethData['epayment_short_id'];      
        
        $this->bankAccountHolder =  (string)$payMethData['bank_account_holder'];  
        $this->bankName =           (string)$payMethData['bank_name'];           
        $this->bankAccountNo =      (string)$payMethData['bank_account_number'];      
        $this->bankCode =           (string)$payMethData['bank_code'];           
        $this->bankBic =            (string)$payMethData['bank_bic'];            
        $this->bankIban =           (string)$payMethData['bank_iban'];           
        $this->gsaDtaAccountIndex = (integer)$payMethData['gsa_dta_acc_no'];
        
        return $this;
        
    }



} // end class




/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_paymentMethod.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_paymentMethod.php']);
}

?>