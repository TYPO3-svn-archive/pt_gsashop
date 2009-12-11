<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2008 Rainer Kuhn (kuhn@punkt.de)
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
 * XCLASS-Extension of TYPO3 core class localRecordList (file typo3/class.db_list_extra.inc)
 *
 * $Id: class.ux_db_list_extra.php,v 1.1 2008/03/07 16:28:23 ry37 Exp $
 *
 * @author	Rainer Kuhn <kuhn@punkt.de>
 * @since   2008-03-07
 */ 



/**
 * Inclusion of TYPO3 resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class



/**
 * XCLASS-Extension of TYPO3 core class recordList (contained in core file typo3/class.db_list_extra.inc!) for adding TCA fields of type "none" to the backend list module's search 
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2008-03-07
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class ux_localRecordList extends localRecordList {	

    
        
    /***************************************************************************
     *   REDECLARED METHODS
     **************************************************************************/
    
	/**
     * TODO: THIS IS A TEMPORARAY QUICK HACK - we're waiting for a TYPO3 core adaption! 
     *       (method redundant to ux_TBE_browser_recordList::makeSearchString() in class.ux_browse_links.php!)
     * 
     * Redeclared function recordList::makeSearchString() (of class.db_list.inc) for adding TCA fields of type "none" to the backend list module's search (required e.g. for search in cached articles)
     * 
     * @param       see recordList::makeSearchString()
	 * @return	    see recordList::makeSearchString()
     * @author      Rainer Kuhn <kuhn@punkt.de>, based on recordList::makeSearchString()
     * @since       2008-03-07
	 */
	public function makeSearchString($table) {
        
        global $TCA;

            // Make query, only if table is valid and a search string is actually defined:
        if ($TCA[$table] && $this->searchString)    {

                // Loading full table description - we need to traverse fields:
            t3lib_div::loadTCA($table);

                // Initialize field array:
            $sfields=array();
            $sfields[]='uid';   // Adding "uid" by default.

                // Traverse the configured columns and add all columns that can be searched:
            foreach($TCA[$table]['columns'] as $fieldName => $info) {
                if ($info['config']['type']=='text' || ($info['config']['type']=='input' && !ereg('date|time|int',$info['config']['eval'])) || $info['config']['type']=='none')    {
                    $sfields[]=$fieldName;
                }
            }

                // If search-fields were defined (and there always are) we create the query:
            if (count($sfields))    {
                $like = ' LIKE \'%'.$GLOBALS['TYPO3_DB']->quoteStr($this->searchString, $table).'%\'';      // Free-text searching...
                $queryPart = ' AND ('.implode($like.' OR ',$sfields).$like.')';

                    // Return query:
                return $queryPart;
            }
        }
    
    }
    
    
    
} // end class

?>
