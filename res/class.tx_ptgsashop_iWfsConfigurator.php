<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2006 Rainer Kuhn (kuhn@punkt.de)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is 
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
 * Workflow status configuration interface of the 'pt_gsashop' extension.
 * This interface has to be implemented by all workflow status configuration classes for the workflow engine of the 'pt_gsashop' extension.
 *
 * $Id: class.tx_ptgsashop_iWfsConfigurator.php,v 1.7 2007/10/15 13:03:25 ry37 Exp $
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2006-06-07
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */



/**
 * Workflow status configuration interface of the 'pt_gsashop' extension.
 * Provides the static methods to implement in workflow status configuration classes for the workflow engine of the 'pt_gsashop' extension.
 * Implement functions that return TRUE for the methods you do not need for your workflow status configuration (SEE COMMENTED "TEMPLATES" BELOW!).
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2006-06-07
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
interface tx_ptgsashop_iWfsConfigurator {
     
    /**
     * Executes an individual condition check (condition if the workflow status is applicable) for the workflow status and returns it's result
     *
     * @param   object      order object to handle in workflow (this may be an abitrary, project specific order object)
     * @return  boolean     TRUE if the condition is fulfilled, FALSE otherwise
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-07
     */ 
    public static function returnConditionCheck($orderObj);
    
    /* Template for "empty" method to copy into implementing class:
    
    public static function returnConditionCheck($orderObj) {
        
        $checkResult = true;
        return $checkResult;
        
    }
    
    */
     
    /**
     * Executes an individual permission check for the user/workflow status combination and returns it's result
     *
     * @param   object      order object to handle in workflow (this may be an abitrary, project specific order object)
     * @return  boolean     TRUE if the permission is granted, FALSE otherwise
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-08
     */ 
    public static function returnPermissionCheck($orderObj);
    
    /* Template for "empty" method to copy into implementing class:
    
    public static function returnPermissionCheck($orderObj) {
        
        $checkResult = true;
        return $checkResult;
        
    }
    
    */
    
    /**
     * Executes individual action on approval of the workflow status and returns the result
     *
     * @param   object      order object to handle in workflow (this may be an abitrary, project specific order object)
     * @return  boolean     TRUE if the intended action has been executed properly, FALSE otherwise
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-07
     */ 
    public static function execApprovalAction($orderObj);
    
    /* Template for "empty" method to copy into implementing class:
    
    public static function execApprovalAction($orderObj) {
        
        $actionResult = true;
        return $actionResult;
        
    }
    
    */
    
    /**
     * Executes individual action on denial of the workflow status and returns the result
     *
     * @param   object      order object to handle in workflow (this may be an abitrary, project specific order object)
     * @return  boolean     TRUE if the intended action has been executed properly, FALSE otherwise
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-07
     */ 
    public static function execDenialAction($orderObj);
    
    /* Template for "empty" method to copy into implementing class:
    
    public static function execDenialAction($orderObj) {
        
        $actionResult = true;
        return $actionResult;
        
    }
    
    */
    
    /**
     * Executes individual action on automatic advance of the workflow status and returns the result
     *
     * @param   object      order object to handle in workflow (this may be an abitrary, project specific order object)
     * @return  boolean     TRUE if the intended action has been executed properly, FALSE otherwise
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-28
     */ 
    public static function execAdvanceAction($orderObj);
    
    /* Template for "empty" method to copy into implementing class:
    
    public static function execAdvanceAction($orderObj) {
        
        $actionResult = true;
        return $actionResult;
        
    }
    
    */
    
    /**
     * Executes individual action on halt of the automatic workflow advance at the current status and returns the result
     *
     * @param   object      order object to handle in workflow (this may be an abitrary, project specific order object)
     * @return  boolean     TRUE if the intended action has been executed properly, FALSE otherwise
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-28
     */ 
    public static function execHaltAction($orderObj);
    
    /* Template for "empty" method to copy into implementing class:
    
    public static function execHaltAction($orderObj) {
        
        $actionResult = true;
        return $actionResult;
        
    }
    
    */
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_iWfsConfigurator.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_iWfsConfigurator.php']);
}

?>
