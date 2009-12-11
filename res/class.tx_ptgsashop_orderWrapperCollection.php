<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2006 Rainer Kuhn (kuhn@punkt.de)
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
 * Order wrapper collection class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_orderWrapperCollection.php,v 1.15 2008/06/16 09:48:56 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2006-03-08
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_orderWrapper.php';// GSA Shop order wrapper class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_orderWrapperAccessor.php';  // GSA Shop database accessor class for order wrappers

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_objectCollection.php'; // abstract object collection class



/**
 * Order wrapper collection class for archived orders
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2006-03-08
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_orderWrapperCollection extends tx_pttools_objectCollection {
    
    
    /***************************************************************************
     *   CONSTRUCTOR
     **************************************************************************/
     
    /**
     * Class constructor: Creates an order wrapper collection object prefilled with order wrapper objects selected by param specification
     *
     * @param   integer     (optional) TYPO3 FE user ID to limit the collection to (TYPO3: fe_users.uid) [default=-1: do not use this param for order wrapper selection]
     * @param   integer     (optional) GSA customer user ID to limit the results to (GSA: ADRESSE.NUMMER) [default=-1: do not use this param for order wrapper selection]
     * @param   integer     (optional) order status code to leave out in collection (e.g. status code for 'finished' orders) [default=-1: do not use this param for order wrapper selection]
     * @param   integer     (optional) order status code to limit the collection to [default=-1: do not use this param for order wrapper selection]
     * @param   integer     (optional) timestamp of the start date/time to limit the collection to [default=-1: do not use this param for order wrapper selection]
     * @param   integer     (optional) timestamp of the end date/time to limit the collection to [default=-1: do not use this param for order wrapper selection]
     * @param   double      (optional) minimal order net sum to limit the collection to [default=-1: do not use this param for order wrapper selection]
     * @param   double      (optional) minimal order gross sum to limit the collection to [default=-1: do not use this param for order wrapper selection]
     * @param   boolean     (optional) flag wether inactive records (e.g. deleted, hidden) should be added, too [default=0]
     * @return  void     
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-10
     */
    public function __construct($feUserId=-1, $customerId=-1, $hideStatusCode=-1, $limitStatusCode=-1, 
                                $startTimestamp=-1, $endTimestamp=-1, $sumNetMin=-1, $sumGrossMin=-1, $displayInactive=0) { 
    
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        $orderWrappersArray =  tx_ptgsashop_orderWrapperAccessor::selectOrderWrappers(
                                    $feUserId, $customerId, $hideStatusCode, $limitStatusCode, 
                                    $startTimestamp, $endTimestamp, $sumNetMin, $sumGrossMin, $displayInactive
                               );
        if (is_array($orderWrappersArray) && !empty($orderWrappersArray)) {
            foreach($orderWrappersArray as $singleWrapperArray) {
                $this->addItem(new tx_ptgsashop_orderWrapper(0, 0, $singleWrapperArray, false));
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
     * @param   tx_ptgsashop_orderWrapper      object to add
     * @param   integer     (optional) array key, see tx_pttools_objectCollection::addItem() 
     * @return  void
     * @see     tx_pttools_objectCollection::addItem()
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-05-30
     */ 
    public function addItem(tx_ptgsashop_orderWrapper $itemObj, $id=0) {
        
        parent::addItem($itemObj, $id);
        
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
     
    /**
     * Returns the net sum total of the current order wrapper collection
     *
     * @param   void 
     * @return  double      net sum total of the current order wrapper collection
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-05-30
     */ 
    public function getCollectionSumNet() {
        
        $sumNet = 0.00;
        
        if (is_array($this->itemsArr)) {
            foreach($this->itemsArr as $orderWrapperObj) {
                $sumNet += $orderWrapperObj->get_sumNet();
            }
        }
        
        return $sumNet;
        
    }
     
    /**
     * Returns the gross sum total of the current order wrapper collection
     *
     * @param   void 
     * @return  double      gross sum total of the current order wrapper collection
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-05-30
     */ 
    public function getCollectionSumGross() {
        
        $sumGross = 0.00;
        
        if (is_array($this->itemsArr)) {
            foreach($this->itemsArr as $orderWrapperObj) {
                $sumGross += $orderWrapperObj->get_sumGross();
            }
        }
        
        return $sumGross;
        
    }
    
    
    
    
} // end class




/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_orderWrapperCollection.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_orderWrapperCollection.php']);
}

?>