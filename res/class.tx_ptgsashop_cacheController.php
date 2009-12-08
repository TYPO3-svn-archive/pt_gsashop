<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2008 Fabrizio Branca (branca@punkt.de)
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
 * Cache controller class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_cacheController.php,v 1.9 2008/12/16 16:09:03 ry37 Exp $
 *
 * @author  Fabrizio Branca <branca@punkt.de>
 * @since   2008-02-07
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT])
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_articleAccessor.php'; 
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_lib.php';  // GSA shop library class with static methods

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_iSingleton.php'; // interface for Singleton design pattern
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php';




/**
 * Cache controller class
 *
 * @author      Fabrizio Branca <branca@punkt.de>
 * @since       2008-02-07
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_cacheController implements tx_pttools_iSingleton {
    
    /***************************************************************************
     *   PROPERTIES
     **************************************************************************/
	
	/**
	 * @var int    storage pid for cached article rows
	 */
	protected $storagePid;
    
    /**
     * @var tx_ptgsashop_cacheController     Singleton unique instance
     */
    private static $uniqueInstance = NULL;   
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
    
    /**
     * Returns a unique instance of the Singleton object. Use this method instead of the private/protected class constructor.
     * 
     * @param   void
     * @return  tx_ptgsashop_cacheController     unique instance of the Singleton object
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2008-02-07
     */
    public static function getInstance() {
        
        if (self::$uniqueInstance === NULL) {
            $className = __CLASS__;
            self::$uniqueInstance = new $className;
        }
        
        return self::$uniqueInstance;    
    }
    
    /**
     * Constructor
     *
     * @param   void
     * @return  void
     * @author  Fabrizio Branca <branca@punkt.de>, Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-02-07
     */
    protected function __construct() {
        
        $this->storagePid = tx_pttools_div::getPid(tx_ptgsashop_lib::getGsaShopConfig('cacheArticlesStoragePid'));
        
    }
    
    /**
     * Final method to prevent object cloning (using 'clone'), in order to use only the unique instance of the Singleton object.
     * 
     * @param   void
     * @return  void
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2008-02-07
     */
    public final function __clone() {
        
        trigger_error('Clone is not allowed for '.get_class($this).' (Singleton)', E_USER_ERROR);
        
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**
     * Clears the tx_ptgsashop_cache_articles table
     * Without a parameter all online articles will be cached
     * 
     * @param   int     (optional)  uid of the cached article to delete (default: -1 => all cached articles will be deleted)
     * @return  void
     * @throws  tx_pttools_exception    if sql delete fails
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2008-02-07
     */
    public function clearCache($uid = -1) {
        
    	if ($uid == -1) {
            $where = '1'; // clear all		    		  
    	} else {
    		$where = 'uid='.intval($uid); // clear only one entry
    	}
        $res = $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_ptgsashop_cache_articles', $where);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        
    }
    
    /**
     * Insert an article into the local cache
     *
     * @param   array   article data
     * @return  int     uid of the inserted row
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2008-02-07
     */
    protected function insertArticleRow(array $row) {
        
        // var_dump($row);
        // query preparation
        $table = 'tx_ptgsashop_cache_articles';

        // TYPO3 specific fields
        $insertFieldsArr = array();
        $insertFieldsArr['pid']             = $this->storagePid;
        $insertFieldsArr['uid']             = $row['NUMMER'];
        $insertFieldsArr['tstamp']          = time();
        $insertFieldsArr['crdate']          = time();
        $insertFieldsArr['cruser_id']       = $GLOBALS['BE_USER']->user['uid'];
        
        // GSA specific fields
        $insertFieldsArr['gsadb_artnr']     = $row['ARTNR'];
        $insertFieldsArr['gsadb_match']     = $row['MATCH'];
        $insertFieldsArr['gsadb_match2']    = $row['MATCH2'];
        $insertFieldsArr['gsadb_passiv']    = $row['PASSIV'];
        
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $insertFieldsArr);
        trace(tx_pttools_div::returnLastBuiltInsertQuery($GLOBALS['TYPO3_DB'], $table, $insertFieldsArr));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        $lastInsertedId = $GLOBALS['TYPO3_DB']->sql_insert_id();
        
        trace($lastInsertedId);
        return $lastInsertedId;
        
    }

    /**
     * Insert articles into the local cache table
     * Without a parameter all online articles will be cached
     *
     * @param   int         (optional) uid of the article, if only one article should be updated  (default: -1 => all cached articles will be deleted)
     * @return  boolean     success status of the cache operation   : TRUE on success, FALSE on failure (means in case of $uid = -1: no articles found in GSA database)
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2008-02-07
     */
    public function insertArticlesIntoCacheTable($uid = -1) {
        
        $operationStatus = true;
    	$articleAccessor = tx_ptgsashop_articleAccessor::getInstance();
    	
    	// delete complete cache / delete single row
    	$this->clearCache($uid);
    	
    	if ($uid == -1) {
    		
    		// inserts all online articles into the cache
    		$articles = $articleAccessor->selectOnlineArticles('', '', '', '', false);
    		if (empty($articles)) {
    		    $operationStatus = false;
    		} else {
        		foreach ($articles as $articleData) {
        			if (is_array($articleData)) {
                        $this->insertArticleRow($articleData);
        			}
        		}
    		}
    		
    	} else {
    		
    		// inserts single record into the cache	
    		$articleData = $articleAccessor->selectArticleData($uid);
    		if (is_array($articleData)) {
                $this->insertArticleRow($articleData);
    		}
    		
    	}
    	
    	return $operationStatus;
        
    }



} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_cacheController.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_cacheController.php']);
}

?>