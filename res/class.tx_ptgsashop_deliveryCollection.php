<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2005 Rainer Kuhn (kuhn@punkt.de)
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
 * Delivery collection class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_deliveryCollection.php,v 1.33 2008/06/18 16:13:56 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2005-09-27
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_delivery.php';// GSA shop delivery class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_address.php';  // GSA Shop specific combined GSA/TYPO3 payment method class

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_objectCollection.php'; // object collection class



/**
 * Delivery collection class
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2005-09-27
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_deliveryCollection extends tx_pttools_objectCollection {
    
    /**
     * @var string      if set, added objects will be type checked against this classname
     */
    protected $restrictedClassName = 'tx_ptgsashop_delivery';
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
     
    /**
     * Class constructor: creates an empty delivery collection object and adds an initial delivery if passed as param
     *
     * @param   mixed       (optional) NULL or object of type tx_ptgsashop_delivery: initial delivery to set in order
     * @return  void
     * @throws  tx_pttools_exception if the $initialDeliveryObj is not of type tx_ptgsashop_delivery    
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-29
     */
    public function __construct($initialDeliveryObj=NULL) { 
        
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        if (isset($initialDeliveryObj)) {
            $this->addItem($initialDeliveryObj);
        }
        
    }   
    
    /**
     * Load from order archive: restores the object's properties of data retrieved from the order archive database. This method should be called only directly after new instantiation of the (empty) object.
     * 
     * @param   integer     UID of the related parent order record in the order archive database
     * @return  tx_ptgsashop_deliveryCollection      object of type tx_ptgsashop_deliveryCollection, "filled" with properties from order archive database
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-05-11
     */
    public function loadFromOrderArchive($ordersId) {
        
        $orderAccessor = tx_ptgsashop_orderAccessor::getInstance();
        $deliveriesArray = $orderAccessor->selectOrdersDeliveryList($ordersId);
        
        // for each deliveryObj in the list
        foreach ($deliveriesArray as $deliveryArr) {
                
            // create an article collection
            $tmparticleCollObj = new tx_ptgsashop_articleCollection();
            $tmparticleCollObj->loadFromOrderArchive($deliveryArr['orders_id'], $deliveryArr['uid']);
                
            // create a delivery/shipping address object
            $tmpshippingAddrObj = new tx_ptgsashop_address(); // tx_ptgsashop_address (instead of parent tx_ptgsauserreg_gsansch) needed here for ->loadFromOrderArchive()
            $tmpshippingAddrObj->loadFromOrderArchive($deliveryArr['orders_id'], $deliveryArr['uid']);
                
            // create a dispatch cost object  
            $tmpdispatchObj = new tx_ptgsashop_dispatchCost(); // parameter costTypeName unknown, will be set in loadFromOrderArchive
            $tmpdispatchObj->loadFromOrderArchive($deliveryArr['orders_id'], $deliveryArr['uid']);
                
            // create the delivery object
            $tmpDeliveryObj = new tx_ptgsashop_delivery($tmparticleCollObj, $tmpshippingAddrObj, $deliveryArr['is_orderbase_net'], $deliveryArr['is_orderbase_taxfree'], $tmpdispatchObj);
            $tmpDeliveryObj->set_orderArchiveId($deliveryArr['uid']);
            
            $this->addItem($tmpDeliveryObj);
            
            unset($tmpDeliveryObj);
            unset($tmparticleCollObj);
            unset($tmpshippingAddrObj);
        }
        
        return $this;
        
    }
    
    
    
    /***************************************************************************
     *   PRESENTATION METHODS (TODO: should be moved to presentator classes)
     **************************************************************************/
    
    /**
     * Returns the HTML options for a HTML pulldown selectorbox of all deliveries' addresses
     * 
     * @param   mixed       (optional) NULL or object of type tx_ptgsauserreg_gsansch to preselect in selectorbox
     * @return  string      HTML options for a HTML pulldown selectorbox of all deliveries' addresses
     * @throws  tx_pttools_exception   if no deliveries found in delivery collection
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-10-12
     */
    public function generateDeliverySelectionOptionsHTML($selectedAddrObj=NULL) {
             
        // throw exception if no addresses found for customer
        if ($this->count() < 1) {
            throw new tx_pttools_exception('No deliveries found in delivery collection', 3);
        }
        
        $options = '';
        
        foreach ($this as $delKey=>$delObj) {
            $options .= '<option value="'.tx_pttools_div::htmlOutput($delKey).'"';
            $options .= ($delObj->get_shippingAddrObj() == $selectedAddrObj ? ' selected="selected">' : '>'); 
            $options .= tx_pttools_div::htmlOutput($delObj->get_shippingAddrObj()->getSelectionEntry());
            $options .= '</option>'.chr(10);
        }
        
        return $options;
        
    }
    
    
    
} // end class




/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_deliveryCollection.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_deliveryCollection.php']);
}

?>