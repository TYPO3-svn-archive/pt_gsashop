<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2007 Fabrizio Branca (branca@punkt.de)
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

require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_assert.php'; 
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general helper library class



/**
 * Update class to update parameters for the pi2 plugin to flexforms
 *
 * @author      Fabrizio Branca <branca@punkt.de>
 * @since       2007-12-05
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class ext_update {
    
    /**
     * Main function, that is called from the backend
     *
     * @return 	string	HTML output for the backend
     * @author 	Fabrizio Branca <branca@punkt.de>
     * @since 	2007-12-05
     */    
    public function main() {
    	
    	$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;
        
        $content = '<h2>Converting old plugin configuration to flexforms</h2>';
        
        if (t3lib_div::GPvar('action') == 'doit') {
            foreach ($this->selectContentRowsToConvert() as $row){
                $content .= 'Updating uid '.$row['uid'].' on page '.$row['pid'].'<br />';
                $this->updateRow($row['uid'], $this->convertRowToFlexForm($row));    
            }
        } else {
            foreach ($this->selectContentRowsToConvert() as $row){
                $content .= 'Found uid '.$row['uid'].' on page '.$row['pid'].'<br />';
            }
        }
        
        $content .= '<h2>Filling column "tx_ptgsashop_orders_addresses.irreParentTable" with data</h2>';
        
    	if (t3lib_div::GPvar('action') == 'doit') {
    		$table = 'tx_ptgsashop_orders_addresses';
    		
        	$updateFieldsArr = array ('irreParentTable' => 'tx_ptgsashop_orders');        
        	$where = 'deliveries_id = 0';
    	    $res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $updateFieldsArr);
    	    #tx_pttools_assert::isMySQLRessource($res);
    	    $content .= 'Updating billing addresses... done.<br />';
    	    
        	$updateFieldsArr = array ('irreParentTable' => 'tx_ptgsashop_orders_deliveries');        
        	$where = 'deliveries_id != 0';
    	    $res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $updateFieldsArr);
    	    #tx_pttools_assert::isMySQLRessource($res);
    	    $content .= 'Updating delivery addresses... done.<br />';
        } else {
			$select  = 'count(*) as c';
        	$from    = 'tx_ptgsashop_orders_addresses';
        	$where   = 'irreParentTable = ""';
        
	        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
	        #tx_pttools_assert::isMySQLRessource($res);
	        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
	        $content .= 'Found "'. (empty($row['c']) ? '0' : $row['c']) . '" records without data in irreParentTable column<br />';
        }
        
        if (t3lib_div::GPvar('action') != 'doit') {
        	$content .= '<br><a href="index.php?&id=0&CMD[showExt]=pt_gsashop&SET[singleDetails]=updateModule&action=doit"><b>Start updating!</b></a>';
        }
        
        return $content;
    }
    
    /**
     * Access function, that is called from the backend to decide whether to shwo the "UPDATE!" menu item or not
     *
     * @return 	bool
     * @author 	Fabrizio Branca <branca@punkt.de>
     * @since 	2007-12-05
     */
    public function access() {
    	return true;
        // return count($this->selectContentRowsToConvert());
    }
    
    /**
     * Converts a row to the flexform data
     *
     * @param 	array	tt_content row data from database
     * @return 	string	XML (T3FlexForm)
     * @author 	Fabrizio Branca <branca@punkt.de>
     * @since 	2007-12-05
     */
    protected function convertRowToFlexForm($row){
        
        if ($row['list_type'] == 'pt_gsashop_pi2'){
             $currentValueArray['data']['sArticle']['lDEF']['article_uid']['vDEF'] = $row['tx_ptgsashop_article_uid'];
        }
        
        foreach ($row as $field => $value){
            if (strpos($field, 'tx_ptgsashop_display_') === 0){
                $field = substr($field, strlen('tx_ptgsashop_display'));
                $currentValueArray['data']['sDisplay']['lDEF']['articleDisplay'.ucfirst($field)]['vDEF'] = $value;
            }
        }
        
        $flexObj = t3lib_div::makeInstance('t3lib_flexformtools');
        return $flexObj->flexArray2Xml($currentValueArray, TRUE);
        
    }
    
    /**
     * Updates a row
     *
     * @param 	int		uid
     * @param 	string	xml for the pi_flexform field
     * @return 	unknown     
     * @author 	Fabrizio Branca <branca@punkt.de>
     * @since 	2007-12-05
     */
    protected function updateRow($uid, $pi_flexform) {

        $table = 'tt_content';
        $updateFieldsArr = array ('pi_flexform' => $pi_flexform);        
        $where = 'uid = '.intval($uid);
        
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $updateFieldsArr);
        trace(tx_pttools_div::returnLastBuiltInsertQuery($GLOBALS['TYPO3_DB'], $table, $updateFieldsArr));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        
        trace($res); 
        return $res;
        
    }
    
    /**
     * Select the tt_content rows with old style paramters
     *
     * @return 	array	array of tt_content row arrays
     * @author 	Fabrizio Branca <branca@punkt.de>
     * @since 	2007-12-05
     */
    protected function selectContentRowsToConvert() {
        
        $select  = '*';
        $from    = 'tt_content';
        $where   = 'list_type = "pt_gsashop_pi2"';
        $where  .= ' AND pi_flexform = ""';
        $groupBy = '';
        $orderBy = '';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        trace(tx_pttools_div::returnLastBuiltSelectQuery($GLOBALS['TYPO3_DB'], $select, $from, $where, $groupBy, $orderBy, $limit));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        
        $rows = array();
        while ($a_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
            $rows[] = $a_row; 
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        
        trace($a_row); 
        return $rows;
    }
    
     
    
}


/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/class.ext_update.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/class.ext_update.php']);
}

?>