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
 * Database accessor class for workflow of the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_workflowAccessor.php,v 1.13 2007/10/15 13:03:25 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2006-03-03
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */



/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general helper library class
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_iSingleton.php'; // interface for Singleton design pattern



/**
 *  Database accessor class for workflow
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2006-03-03
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_workflowAccessor implements tx_pttools_iSingleton {
    
    /**
     * Properties
     */
    private static $uniqueInstance = NULL; // (tx_ptgsashop_workflowAccessor object) Singleton unique instance
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
    
    /**
     * Private class constructor: must not be called directly in order to use getInstance() to get the unique instance of the object.
     *
     * @param   void
     * @return  void
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-10
     */
    private function __construct() {
    
        trace('***** Creating new '.__CLASS__.' object. *****');
        
    }
    
    /**
     * Returns a unique instance (Singleton) of the object. Use this method instead of the private/protected class constructor.
     *
     * @param   void
     * @return  tx_ptgsashop_workflowAccessor      unique instance of the object (Singleton) 
     * @global     
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-03
     */
    public static function getInstance() {
        
        if (self::$uniqueInstance === NULL) {
            $className = __CLASS__;
            self::$uniqueInstance = new $className;
        }
        
        return self::$uniqueInstance;
        
    }
    
    /**
     * Final method to prevent object cloning (using 'clone'), in order to use only the unique instance of the Singleton object.
     * @param   void
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-15
     */
    public final function __clone() {
        
        trigger_error('Clone is not allowed for '.get_class($this).' (Singleton)', E_USER_ERROR);
        
    }
    
    
    
    /***************************************************************************
     *   TYPO3 DB RELATED METHODS
     **************************************************************************/
     
    /**
     * Returns data of an workflow status record (specified by UID) from the TYPO3 database
     *
     * @param   integer     status code of the workflow status record in the TYPO3 database
     * @param   integer     (optional, integer: -1 to n) ID of the required content language (e.g. retrieved by $GLOBALS['TSFE']->sys_language_content): 0 is the default language, -1 is all languages
     * @global  object      $GLOBALS['TYPO3_DB']: t3lib_db Object (TYPO3 DB API)
     * @global  object      $GLOBALS['TSFE']->cObj: tslib_cObj Object (TYPO3 content object)
     * @return  array       data of the specified workflow status record
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-03
     */
    public function selectWorkflowStatus($statusCode, $langUid=0) {
        
        // query preparation
        $select  = 'status_code, name, description, auth_groups_view, auth_groups_use, update_order, '.
                   'condition_method, permission_method, approve_action_method, deny_action_method, advance_action_method, halt_action_method, '.
                   'approve_status_code, deny_status_code, advance_status_code, '.
                   'label_choice, label_approve, label_deny, label_confirm_approve, label_confirm_deny, sys_language_uid';
        $from    = 'tx_ptgsashop_workflow';
        $where   = 'status_code = '.intval($statusCode).' '.
                   'AND sys_language_uid IN ('.intval($langUid).', -1) '. // TODO: improve language handling - this is a temporary fallback hack to try to use -1 if no record for given $langUid has been found
                   tx_pttools_div::enableFields($from); 
        $groupBy = '';
        $orderBy = 'sys_language_uid DESC'; // TODO: improve language handling - this is a temporary hack prevent the WHERE-IN-Clause (see above) to return multiple records
        $limit   = '1';                     // TODO: improve language handling - this is a temporary hack prevent the WHERE-IN-Clause (see above) to return multiple records
        
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        trace(tx_pttools_div::returnLastBuiltSelectQuery($GLOBALS['TYPO3_DB'], $select, $from, $where, $groupBy, $orderBy, $limit));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        
        $a_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        
        trace($a_row); 
        return $a_row;
        
    }
     
    /**
     * Returns all workflow status codes from the TYPO3 database
     *
     * @param   void
     * @global  object      $GLOBALS['TYPO3_DB']: t3lib_db Object (TYPO3 DB API)
     * @global  object      $GLOBALS['TSFE']->cObj: tslib_cObj Object (TYPO3 content object)
     * @return  array       array containing all workflow status codes
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-03-03
     */
    public function selectAllWorkflowStatusCodes() {
        
        // query preparation
        $select  = 'status_code';
        $from    = 'tx_ptgsashop_workflow';
        $where   = '1 '.
                   tx_pttools_div::enableFields($from); 
        $groupBy = '';
        $orderBy = 'status_code';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        trace(tx_pttools_div::returnLastBuiltSelectQuery($GLOBALS['TYPO3_DB'], $select, $from, $where, $groupBy, $orderBy, $limit));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
            
        // store all data in twodimensional array
        $a_result = array();
        while ($a_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $a_result[] = $a_row[$select];
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        
        trace($a_result);
        return $a_result;
        
    }
    
//     
//    /**
//     * Returns all workflow status records from the TYPO3 database
//     *
//     * @param   void
//     * @global  object      $GLOBALS['TYPO3_DB']: t3lib_db Object (TYPO3 DB API)
//     * @global  object      $GLOBALS['TSFE']->cObj: tslib_cObj Object (TYPO3 content object)
//     * @return  array       2D-array containing all workflow status records
//     * @throws  tx_pttools_exception   if the query fails/returns false
//     * @author  Rainer Kuhn <kuhn@punkt.de>
//     * @since   2006-03-03
//     */
//    public function selectWorkflowStatusList() {   // ### TODO: Localisation / multiple languages  -> see selectWorkflowStatus()
//        
//        // query preparation
//        $select  = 'status_code, name, description, auth_groups_view, auth_groups_use, update_order, '.
//                   'condition_method, permission_method, approve_action_method, deny_action_method, advance_action_method, halt_action_method, '.
//                   'approve_status_code, deny_status_code, advance_status_code, '.
//                   'label_choice, label_approve, label_deny, label_confirm_approve, label_confirm_deny';
//        $from    = 'tx_ptgsashop_workflow';
//        $where   = '1 '.
//                   tx_pttools_div::enableFields($from); 
//        $groupBy = '';
//        $orderBy = 'status_code';
//        $limit   = '';
//        
//        // exec query using TYPO3 DB API
//        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
//        trace(tx_pttools_div::returnLastBuiltSelectQuery($GLOBALS['TYPO3_DB'], $select, $from, $where, $groupBy, $orderBy, $limit));
//        if ($res == false) {
//            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
//        }
//            
//        // store all data in twodimensional array
//        $a_result = array();
//        while ($a_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
//            $a_result[] = $a_row;
//        }
//        $GLOBALS['TYPO3_DB']->sql_free_result($res);
//        
//        trace($a_result);
//        return $a_result;
//        
//    }
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_workflowAccessor.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_workflowAccessor.php']);
}

?>