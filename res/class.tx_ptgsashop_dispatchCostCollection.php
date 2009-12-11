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
 * Dispatch cost collection class of the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_dispatchCostCollection.php,v 1.2 2008/06/16 09:48:56 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2007-11-02
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_dispatchCost.php';// GSA Shop dispatch cost class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_dispatchCostAccessor.php';  // GSA Shop database accessor class for dispatch cost data

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_objectCollection.php'; // abstract object collection class



/**
 * Dispatch cost collection class 
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2007-11-02
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_dispatchCostCollection extends tx_pttools_objectCollection {
    
    
    /***************************************************************************
     *   CONSTRUCTOR
     **************************************************************************/
     
    /**
     * Class constructor: fills the dispatch cost collection object
     *
     * @param   boolean     (optional) flag whether a collection of all dispatch costs in the database should be created (default: false = create empty collection)
     * @return  void     
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-11-02
     */
    public function __construct($createAllDispatchCostsCollection=false) { 
        
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        // create a collection of all dispatch cost records in the database if requested by param
        if ($createAllDispatchCostsCollection == true) {
            $dispatchCostsArr = tx_ptgsashop_dispatchCostAccessor::getInstance()->selectDispatchCostRecords();
            if (is_array($dispatchCostsArr)) {
                foreach ($dispatchCostsArr as $dispatchCostDataArr) {
                    $this->addItem(new tx_ptgsashop_dispatchCost('', $dispatchCostDataArr['NUMMER']));
                }
            }
        }
        
    }
    
    
    
    /***************************************************************************
     *   INHERITED METHODS
     **************************************************************************/
     
    /**
     * Adds one item to the collection
     *
     * @param   tx_ptgsashop_dispatchCost      dispatch cost object to add
     * @return  void
     * @see     tx_pttools_objectCollection::addItem()
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-02-11
     */ 
    public function addItem(tx_ptgsashop_dispatchCost $itemObj) {
        
        parent::addItem($itemObj);
        
    }
    
    
    
} // end class




/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_dispatchCostCollection.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_dispatchCostCollection.php']);
}

?>