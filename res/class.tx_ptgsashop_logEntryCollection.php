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
 * Log entry collection class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_logEntryCollection.php,v 1.5 2008/04/08 08:20:00 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2006-06-23
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_logEntry.php';// GSA Shop log entry class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_logEntryAccessor.php';  // GSA Shop database accessor class for log entries

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function



/**
 * Log entry collection class for order related log entries
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2006-06-23
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_logEntryCollection implements IteratorAggregate, Countable {
    
    /**
     * Properties
     */
    protected $itemsArr = array();    // (array) array containing log entry objects as values
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR
     **************************************************************************/
     
    /**
     * Class constructor: Creates an log entry collection object prefilled with log entry objects selected by param specification
     *
     * @param   integer     related order wrapper record UID to limit the collection to
     * @param   integer     (optional) TYPO3 FE user ID to limit the collection to
     * @param   integer     (optional) timestamp of the start date/time to limit the collection to
     * @param   integer     (optional) timestamp of the end date/time to limit the collection to
     * @return  void     
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-23
     */
    public function __construct($orderWrapperId, $feUserId=-1, $startTimestamp=-1, $endTimestamp=-1) { 
    
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        $logentriesArr = 
            tx_ptgsashop_logEntryAccessor::selectLogEntries($orderWrapperId, $feUserId, $startTimestamp, $endTimestamp);
        
        if (is_array($logentriesArr) && !empty($logentriesArr)) {
            foreach($logentriesArr as $logentryArr) {
                $this->addItem(new tx_ptgsashop_logEntry($logentryArr['uid']));  // TODO: to be improved...
            }
        }
        
        trace($this, 0, 'New '.__CLASS__.' object created');
        
    }   
    
    
    
    /***************************************************************************
     *   ITERATORAGGREGATE API METHODS
     **************************************************************************/
     
    /**
     * Definded by IteratorAggregate interface: returns an iterator for the object 
     *
     * @param   void 
     * @return  ArrayIterator     object of type ArrayIterator: Iterator for deliveries within this collection
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-23 
     */ 
    public function getIterator() {
        
        $logEntryIterator = new ArrayIterator($this->itemsArr);
        #trace($logEntryIterator, 0, '$logEntryIterator');
        
        return $logEntryIterator;
        
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
     * @since   2006-06-23 
     */ 
    public function count() {
        
        return count($this->itemsArr);
        
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**
     * Adds one log entry to the log entry collection
     *
     * @param   tx_ptgsashop_logEntry     log entry to add, object of type tx_ptgsashop_logEntry required
     * @return  void
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-23
     */
    public function addItem(tx_ptgsashop_logEntry $logEntryObj) { 
        
        $this->itemsArr[] = $logEntryObj;
        
    }
    
    /**
     * Deletes one log entry from the log entry collection
     *
     * @param   integer      id of log entry to remove
     * @return  void
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-23
     */
    public function deleteItem($id) {
        
        if (array_key_exists($id, $this->itemsArr)) {
            unset($this->itemsArr[$id]);
        }
        
    }
    
    /**
     * Clears all items of the log entry collection
     *
     * @param   void
     * @return  void
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-23
     */
    public function clearItems() {
        
        $this->itemsArr = array();
        
    }
    
    
    
} // end class




/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_logEntryCollection.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_logEntryCollection.php']);
}

?>