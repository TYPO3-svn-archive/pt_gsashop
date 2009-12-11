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
 * Log entry class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_logEntry.php,v 1.5 2007/07/27 10:57:51 ry37 Exp $
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
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_logEntryAccessor.php';  // GSA Shop database accessor class for log entries

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class


/**
 * Log entry class for order related log entries
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2006-06-23
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_logEntry {
    
    /**
     * Properties
     */
    protected $uid = 0;               // (integer) database UID of log entry
    protected $pid = NULL;            // (integer) page UID from the page the log entry has been set from
    protected $tstamp  = 0;           // (integer) timestamp of the log entry
    protected $userId  = 0;           // (integer) UID of the FE user who has initiated the log entry
    protected $orderWrapperId = 0;    // (integer) UID of the order wrapper record related to the log entry
    protected $text = 0;              // (string) text of the log entry
    protected $statusPrev = 0;        // (integer) previous workflow status of the related order wrapper
    protected $statusNew = 0;         // (integer) new workflow status of the related order wrapper
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
     
    /**
     * Class constructor: prefills the object's properies depending on given params
     *
     * @param   integer     (optional: required if you want to create a log entry from DB) UID of the existing log entry record in the database to create object from. Set to 0 if you want to use the other params to create a new log entry.
     * @param   integer     (optional: required if you want to create new log entry) UID of page the new log entry has been set from. This param has no effect if the 1st param is set to a positive integer.
     * @param   integer     (optional: required if you want to create new log entry) UID of FE user who has initiated the new log entry. This param has no effect if the 1st param is set to a positive integer.
     * @param   integer     (optional: if you want to create new log entry) UID of the order wrapper record related to the new log entry. This param has no effect if the 1st param is set to a positive integer.
     * @param   integer     (optional: if you want to create new log entry) previous workflow status of the related order wrapper. This param has no effect if the 1st param is set to a positive integer.
     * @param   integer     (optional: if you want to create new log entry) new workflow status of the related order wrapper. This param has no effect if the 1st param is set to a positive integer.
     * @param   string      (optional: if you want to create new log entry) optional text for the new log entry
     * @return  void     
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-23
     */
    public function __construct($logentryUid=0, $pid=0, $userId=0, $orderWrapperId=0, $statusPrev=-1, $statusNew=-1, $text='') {
        
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        if (!is_numeric($logentryUid)) {
            throw new tx_pttools_exception('Parameter error', 3, 'First parameter for '.__CLASS__.' constructor is not numeric');
        }
        
        // if a log entry record ID is given, retrieve wrapper data array from database accessor (and overwrite 2nd param)
        if ($logentryUid > 0) {
            $logentryArr = tx_ptgsashop_logEntryAccessor::getInstance()->selectLogEntryById($logentryUid);
        } else {
            $logentryArr['tstamp'] = (integer)time();
            $logentryArr['pid'] = (integer)$pid;
            $logentryArr['userId'] = (integer)$userId;
            $logentryArr['orderWrapperId'] = (integer)$orderWrapperId;
            $logentryArr['text'] = (string)$text;
            $logentryArr['statusPrev'] = (integer)$statusPrev;
            $logentryArr['statusNew'] = (integer)$statusNew;
        }
        
        $this->setPropertiesFromGivenArray($logentryArr);
        
        trace($this, 0, 'New '.__CLASS__.' object created');
        
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
     
    /**
     * Sets the objects properties using data given by param array
     *
     * @param   array       Array containing data to set as the object's properties; array keys have to be named exactly like this classes' properties.
     * @return  void        
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-23
     */
    protected function setPropertiesFromGivenArray($logentryArr) {
        
        if (isset($logentryArr['uid'])) $this->uid = (integer)$logentryArr['uid'];
        if (isset($logentryArr['pid'])) $this->pid = (integer)$logentryArr['pid'];
        if (isset($logentryArr['tstamp'])) $this->tstamp = (integer)$logentryArr['tstamp'];
        if (isset($logentryArr['userId'])) $this->userId = (integer)$logentryArr['userId'];
        if (isset($logentryArr['orderWrapperId'])) $this->orderWrapperId = (integer)$logentryArr['orderWrapperId'];
        if (isset($logentryArr['text'])) $this->text = (string)$logentryArr['text'];
        if (isset($logentryArr['statusPrev'])) $this->statusPrev = (integer)$logentryArr['statusPrev'];
        if (isset($logentryArr['statusNew'])) $this->statusNew = (integer)$logentryArr['statusNew'];
        
    }
     
    /**
     * Saves a new log entry to the database
     * 
     * @param   void
     * @global  
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-23 
     */
    public function saveLogEntry() {
        
        tx_ptgsashop_logEntryAccessor::getInstance()->insertLogEntry($this);
        
    }
    
    
    
    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer       property value
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-23
     */
    public function get_uid() {
        
        return $this->uid;
        
    }
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer       property value
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-23
     */
    public function get_pid() {
        
        return $this->pid;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer       property value
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-23
     */
    public function get_tstamp() {
        
        return $this->tstamp;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer       property value
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-23
     */
    public function get_userId() {
        
        return $this->userId;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer      property value
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-23
     */
    public function get_orderWrapperId() {
        
        return $this->orderWrapperId;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-23
     */
    public function get_text() {
        
        return $this->text;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer      property value
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-23
     */
    public function get_statusPrev() {
        
        return $this->statusPrev;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer      property value
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-23
     */
    public function get_statusNew() {
        
        return $this->statusNew;
        
    }
    
    
} // end class




/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_logEntry.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_logEntry.php']);
}

?>