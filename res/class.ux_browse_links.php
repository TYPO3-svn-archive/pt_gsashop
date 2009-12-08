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
 * XCLASS-Extension of TYPO3 core class TBE_browser_recordList
 *
 * $Id: class.ux_browse_links.php,v 1.7 2008/11/07 13:36:56 ry37 Exp $
 *
 * @author	Rainer Kuhn <kuhn@punkt.de>
 * @since   2008-02-07
 */ 



/**
 * Inclusion of TYPO3 resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_lib.php';  // GSA shop library class with static methods



/**
 * XCLASS-Extension of TYPO3 core class TBE_browser_recordList (contained in core file class.browse_links.php!) for modifying displaying fields in backend "TYPO3 Element Browser" popup list view
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2008-02-07
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class ux_TBE_browser_recordList extends TBE_browser_recordList {	

    
        
    /***************************************************************************
     *   REDECLARED METHODS
     **************************************************************************/
    
	/**
     * Redeclared function recordList::setDispFields() (of class.db_list.inc) for displaying an additional description field for Article Cache in backend "TYPO3 Element Browser" popup list view only
     * 
     * @param       void
	 * @return	    void
     * @author      Rainer Kuhn <kuhn@punkt.de> (thanks to Christian Jul Jensen <christian@jul.net> for core analysis)
     * @since       2008-02-07
	 */
	public function setDispFields() {
        
        $this->setFields = array();
        $this->allFields = true;
        
        // add additional fields for article cache display in backend "TYPO3 Element Browser" popup list view
        $descriptionSourceFieldName = tx_ptgsashop_lib::getGsaShopConfig('articleDescriptionSourceField', false); // 2nd param required to prevent no config exception in backend
        if (empty ($descriptionSourceFieldName)) {
            $descriptionSourceFieldName = 'MATCH';
        }
        $descriptionCacheFieldName = 'gsadb_'.strtolower($descriptionSourceFieldName);
        
        $this->setFields['tx_ptgsashop_cache_articles'] = array($descriptionCacheFieldName);
        
        // do not display the references column ('[Ref]')
        $this->dontShowClipControlPanels = true;
    
    }
    
    /**
     * TODO: THIS IS A TEMPORARY QUICK HACK - we're waiting for a TYPO3 core adaption! 
     *       (method redundant to ux_localRecordList::makeSearchString() in class.ux_db_list_extra.php!)
     * 
     * Redeclared function recordList::makeSearchString() (of class.db_list.inc) for adding TCA fields of type "none" to the "TYPO3 Element Browser" popup list's search (required e.g. for search in cached articles)
     * 
     * @param       see recordList::makeSearchString()
     * @return      see recordList::makeSearchString()
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
