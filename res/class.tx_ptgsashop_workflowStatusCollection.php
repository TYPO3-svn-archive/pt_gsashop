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
 * Workflow status collection class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_workflowStatusCollection.php,v 1.9 2008/04/08 08:20:00 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2006-03-03
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_workflowStatus.php';// GSA Shop workflow status class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_workflowAccessor.php';  // GSA Shop database accessor class for workflow

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function



/**
 * Workflow status collection class
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2006-03-03
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_workflowStatusCollection implements IteratorAggregate, Countable {
    
    /**
     * Properties
     */
    protected $itemsArr = array();    // (array) array containing workflow status objects as values
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR
     **************************************************************************/
     
    /**
     * Class constructor: creates workflow status collection object and fills it with all available status records
     *
     * @param   string    TYPO3 extension key of the extension where to search for the workflow statuses' configuration classes
     * @return  void     
     * @global  
     * @throws  tx_pttools_exception   if no workflow status records are found in the database
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-03
     */
    public function __construct($configExtKey) { 
    
        trace('***** Creating new '.__CLASS__.' object. *****');
        $statusCodesArr = tx_ptgsashop_workflowAccessor::getInstance()->selectAllWorkflowStatusCodes();
        
        if (!is_array($statusCodesArr) || empty($statusCodesArr)) {
            throw new tx_pttools_exception('No workflow data found.', 3, 'No workflow status records found in database.');
        }
        
        // create workflow status objects in collection and pass next and previous status codes to them
        $prevStatusCode = NULL;
        foreach ($statusCodesArr as $key=>$statusCode) {
            $nextStatusCode = (integer)(array_key_exists(($key+1), $statusCodesArr) ? $statusCodesArr[($key+1)] : 0);
            $this->addItem(new tx_ptgsashop_workflowStatus($statusCode, $configExtKey, $prevStatusCode, $nextStatusCode));
            $prevStatusCode = $statusCode;
        }
        
        trace($this, 0, __CLASS__);
    }   
    
    
    
    /***************************************************************************
     *   ITERATORAGGREGATE API METHODS
     **************************************************************************/
     
    /**
     * Definded by IteratorAggregate interface: returns an iterator for the object 
     *
     * @param   void 
     * @return  ArrayIterator     object of type ArrayIterator: Iterator for workflow statuses within this collection
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-03 
     */ 
    public function getIterator() {
        
        $wfsIterator = new ArrayIterator($this->itemsArr);
        #trace($wfsIterator, 0, '$wfsIterator');
        
        return $wfsIterator;
        
    }
    
    
    
    /***************************************************************************
     *   COUNTABLE INTERFACE API METHODS
     **************************************************************************/
     
    /**
     * Definded by Countable interface: Returns the number of items
     *
     * @param   void 
     * @return  integer     number of items in the items array
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-10 
     */ 
    public function count() {
        
        return count($this->itemsArr);
        
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**
     * Adds one workflow status to the workflow status collection with array key = status code
     *
     * @param   tx_ptgsashop_workflowStatus     workflow status to add, object of type tx_ptgsashop_workflowStatus required
     * @return  void
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-03
     */
    protected function addItem(tx_ptgsashop_workflowStatus $wfsObj) { 
        
        $this->itemsArr[$wfsObj->get_statusCode()] = $wfsObj;
        
    }
    
    /**
     * Returns the first workflow status object of the workflow status collection
     *
     * @param   void
     * @return  string       status code of first workflow status object of the workflow status collection
     * @global  
     * @throws  tx_pttools_exception   if there are no workflow statuses in the workflow
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-04-04
     */
    public function getInitialWfsCode() { 
        
        if ($this->count() < 1) {
            throw new tx_pttools_exception('Empty workflow status collection', 2);
        }
        
        ksort($this->itemsArr);
        reset($this->itemsArr);
        $currentWfsObj = current($this->itemsArr);
        
        return $currentWfsObj->getStatusCode;
        
    }
    
    
    
} // end class




/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_workflowStatusCollection.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_workflowStatusCollection.php']);
}

?>