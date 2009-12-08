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
 * Address class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_address.php,v 1.25 2008/02/04 13:53:04 ry44 Exp $
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
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_gsansch.php';  // combined GSA/TYPO3 address class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function


/**
 * Address class for shop orders (billing address/shipping address)
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2007-05-15
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_address extends tx_ptgsauserreg_gsansch  {
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
    
    /**
     * Class constructor: calls the parent constructor
     * 
     * @param   integer     (optional) see tx_ptgsauserreg_gsansch::__construct()
     * @return  void  
     * @see     tx_ptgsauserreg_gsansch::__construct()
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-05-15
     */
     public function __construct($anschId=0) {
        
        trace('***** Creating new '.__CLASS__.' object. *****');
        parent::__construct($anschId);
        
    }
     
    /**
     * Load from order archive: restores the object's properties of data retrieved from the order archive database. This method should be called only directly after new instantiation of the (empty) object.
     * 
     * @param   integer     UID of the related parent order record in the order archive database
     * @param   integer     UID of the related parent delivery record in the order archive database
     * @return  tx_ptgsashop_address      object of type tx_ptgsashop_address, "filled" with properties from order archive database
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-04
     */
     public function loadFromOrderArchive($ordersId, $deliveriesId) {

        // fetch data from table 'tx_ptgsashop_orders_addresses'
        $orderAccessor = tx_ptgsashop_orderAccessor::getInstance();
        $archivedAddrData = $orderAccessor->selectOrdersAddress($ordersId, $deliveriesId);
            
        $this->post1 =  (string)$archivedAddrData['post1'];
        $this->post2 =  (string)$archivedAddrData['post2'];
        $this->post3 =  (string)$archivedAddrData['post3'];
        $this->post4 =  (string)$archivedAddrData['post4'];
        $this->post5 =  (string)$archivedAddrData['post5'];
        $this->post6 =  (string)$archivedAddrData['post6'];
        $this->post7 =  (string)$archivedAddrData['post7'];
        
        $this->country = (string)$archivedAddrData['country'];  // important property of parent tx_pttools_address
        $this->gsauid =  (integer)$archivedAddrData['gsa_id_adresse'];
        $this->anschid = (integer)$archivedAddrData['gsa_id_ansch'];
        $this->uid  =    (integer)$archivedAddrData['t3_id_ansch'];
        
        // check if address data has been modified
        $gsanschAccessor = tx_ptgsauserreg_gsanschAccessor::getInstance();
        try {
            $currentAddrData = $gsanschAccessor->selectAnschData($this->uid);
    
            // check if address has changed ("dirty")
            if ($this->post1 == $currentAddrData['post1'] && $this->post2 == $currentAddrData['post2'] &&
                $this->post3 == $currentAddrData['post3'] && $this->post4 == $currentAddrData['post4'] &&
                $this->post5 == $currentAddrData['post5'] && $this->post6 == $currentAddrData['post6'] &&
                $this->post7 == $currentAddrData['post7'] && $this->country == $currentAddrData['country'] ) {
                // current address unchanged: do nothing
            } else { 
                // current address differs from archived address: set dirty flag
                $this->set_dirty(true);
                trace('tx_ptgsashop_address object with uid '.$this->uid.' is dirty!');
            }
        } catch (tx_pttools_exception $excObj) {
            
            $this->set_dirty(true);
            trace('Original tx_ptgsashop_address object with uid '.$this->uid.' does not exist anymore. So it is dirty!');
            
        }
        
        return $this;
        
    }
    
    
    
} // end class




/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_address.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_address.php']);
}

?>