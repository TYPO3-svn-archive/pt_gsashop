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
 * Class for item processing functions of flexform elements (part of 'pt_gsashop' extension)
 *
 * $Id: class.tx_ptgsashop_flexformItemFunctions.php,v 1.4 2008/12/11 14:40:38 ry44 Exp $
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2007-06-26
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_workflowAccessor.php';  // GSA Shop database accessor class for workflow

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function

/**
 * Debugging config for development
 */
#$trace     = 1; // (int) trace options @see tx_pttools_debug::trace() [for local temporary debugging use only, please COMMENT OUT this line if finished with debugging!]



/**
 * Provides methods to manipulates the item-array for flexform elements
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2007-06-26
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_flexformItemFunctions {
    
    /**
     * itemsProcFunc function for using workflow status codes in a selectorbox (e.g. called from pi4/flexform_ds.xml). Fills an array of selectorbox items with keys and values named like the existing workflow status from the DB table tx_ptgsashop_workflow.
     *
     * @param   array   parameters with the selectorbox item-array in the array key 'items'
     * @return  void    (no return value - the $params and $pObj variables are passed by reference, so content is passed back automatically)
     * @see     pi4/flexform_ds.xml
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-26
    */
    public function getWorkflowStatusCodesAsSelectorboxItems($config) {
        
        try {
            
            $statusCodesArr = tx_ptgsashop_workflowAccessor::getInstance()->selectAllWorkflowStatusCodes();
            
            // create selectorbox menue items
            if (is_array($statusCodesArr)) {
                foreach ($statusCodesArr as $statusCode) {
                    $config['items'][] = array($statusCode, $statusCode); // array($value, $key), here $value=$key
                }
            }
            
        } catch (Exception $excObj) {
            
            // if an exception has been catched: handle it and write error message as option to pulldown menu
            if (method_exists($excObj, 'handle')) {
                $excObj->handle();    
            }
            
            $config['items'][] = array(($excObj instanceof tx_pttools_exception) ? $excObj->__toString() : 'An error has occured', '');
            
        }
        
        return $config;
        
    }
    
    

} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/class.tx_ptgsashop_flexformItemFunctions.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/class.tx_ptgsashop_flexformItemFunctions.php']);
}
?>