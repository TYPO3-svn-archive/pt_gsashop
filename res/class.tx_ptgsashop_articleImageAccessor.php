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
/** 
 * Database accessor class for article images of the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_articleImageAccessor.php,v 1.8 2008/10/16 15:15:13 ry37 Exp $
 *
 * @author  Fabrizio Branca <branca@punkt.de>
 * @since   2007-11-28
 */ 


/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_lib.php';  // GSA shop library class with static methods

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general helper library class
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_iSingleton.php'; // interface for Singleton design pattern



/**
 * Database accessor class for article images
 *
 * @author      Fabrizio Branca <branca@punkt.de>
 * @since       2007-11-28
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_articleImageAccessor implements tx_pttools_iSingleton {
    
    /**
     * Properties
     */
	
	/**
	 * @var tx_ptgsashop_orderWrapperAccessor  Singleton unique instance
	 */
    private static $uniqueInstance = NULL; 
    
    /**
     * @var int     pid where to store image data
     */
    protected $storagePid;
    
    
    
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
        
        $this->storagePid = tx_pttools_div::getPid(tx_ptgsashop_lib::getGsaShopConfig('articleImageStoragePid'));
        
    }
    
    /**
     * Returns a unique instance (Singleton) of the object. Use this method instead of the private/protected class constructor.
     *
     * @param   void
     * @return  tx_ptgsashop_articleImageAccessor      unique instance of the object (Singleton) 
     * @global     
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-11-28
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
     * 
     * @param   void
     * @return  void
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-11-28
     */
    public final function __clone() {
        trigger_error('Clone is not allowed for '.get_class($this).' (Singleton)', E_USER_ERROR);
    }
    
    

     
    /**
     * Returns data of an image record (specified by UID) from the TYPO3 database
     *
     * @param   integer     uid of the image record in the TYPO3 database. 
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-11-28
     */
    public function selectByUid($uid) {
        
        // query preparation
        $select  = 'uid,  
        		    gsa_art_nummer, 
        		    path, 
        		    description,
        		    alt,
        		    title';
        $from    = 'tx_ptgsashop_article_images';
        $where   = 'uid = '.intval($uid)  . tx_pttools_div::enableFields($from) ; 
        $groupBy = '';
        $orderBy = '';
        $limit   = '';
        
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
     * Returns all images for an article
     *
     * @param   integer     gsa_art_nummer
     * @return 	array		array of image data
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-11-28
     */
    public function selectByGsaArtNummer($gsaArtNummer) {
        
        // query preparation
        $select  = 'uid, 
        		    gsa_art_nummer,
        		    path,
        		    description,
        		    alt,
        		    title';
        $from    = 'tx_ptgsashop_article_images';
        $where   = 'gsa_art_nummer = '.intval($gsaArtNummer)  . tx_pttools_div::enableFields($from);
        $where   .= ' AND path != ""'; 
        $groupBy = '';
        $orderBy = 'sorting';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        trace(tx_pttools_div::returnLastBuiltSelectQuery($GLOBALS['TYPO3_DB'], $select, $from, $where, $groupBy, $orderBy, $limit));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
            
        // store all data in a twodimensional array
        $a_result = array();
        while ($a_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $a_result[] = $a_row;
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        
        trace($a_result);
        return $a_result;
        
    }
     
    /**
     * Updates images for a specified article
     *
     * @param 	int		uid of the article
     * @param 	array 	images
     * @return 	void
     * @author	Fabrizio Branca <branca@punkt.de>
     */
    public function updateImages($gsaUid, array $imgArray) {
                
        $oldImgArray = $this->getPathArray($this->selectByGsaArtNummer($gsaUid));
        
        // delete images that don't exist in imgArray
        foreach ($oldImgArray as $oldImgPath){
            if (!in_array($oldImgPath, $imgArray)){
                $this->deleteImageByPath($oldImgPath, $gsaUid, false);
            }
        }
        
        // insert images that don't exist in oldImgArray
        foreach ($imgArray as $imgPath){
            if (!in_array($imgPath, $oldImgArray)){
                $this->insertImage(array('gsa_art_nummer' => $gsaUid, 'path' => $imgPath));
            }
        }
    }
    
    /**
     * Get array of paths
     *
     * @param 	array	array of database rows from table tx_ptgsashop_article_images
     * @return 	array
     * @author	Fabrizio Branca <branca@punkt.de>
     */
    public function getPathArray(array $resArray) {
        
        $pathArray = array();
        foreach ($resArray as $img) $pathArray[] = $img['path'];
        return $pathArray;
        
    }
    
    /**
     * Stores an image into the database (update or insert, depending on if the uid is set)
     *
     * @param   array   data array
     * @return  int     uid of the record
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2008-01
     */
    public function storeImage(array $dataArr) {
        
        if (intval($dataArr['uid']) == 0){
            // create a new record
            $newUid = $this->insertImage($dataArr);
        } else {
            // update existing record
            $newUid = $this->updateImage($dataArr);
        }
        return $newUid;
    }
    
    /**
     * Inserts an image
     *
     * @param 	array 	data array
     * @return 	int		uid of the inserted image
     * @throws	tx_pttools_exception	if insert query fails
     * @author	Fabrizio Branca <branca@punkt.de>
     */
    protected function insertImage(array $dataArr) { 
        
        // query preparation
        $table = 'tx_ptgsashop_article_images';

        $insertFieldsArr = array();
        $insertFieldsArr['pid']             = $this->storagePid;
        $insertFieldsArr['tstamp']          = time();
        $insertFieldsArr['crdate']          = time();
        $insertFieldsArr['gsa_art_nummer']  = $dataArr['gsa_art_nummer'];
        $insertFieldsArr['path']            = $dataArr['path'];
        $insertFieldsArr['description']     = $dataArr['description'];
        $insertFieldsArr['alt']             = $dataArr['alt'];
        $insertFieldsArr['title']           = $dataArr['title'];
        
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
     * Updates an article image database row
     *
     * @param   array   data array
     * @return  int     uid of the inserted image
     * @throws  tx_pttools_exception    if insert query fails
     * @author  Fabrizio Branca <branca@punkt.de>
     */
    protected function updateImage(array $dataArr) {
        
        // query preparation
        $table = 'tx_ptgsashop_article_images';
        
        $where = 'uid='.intval($dataArr['uid']);

        $updateFieldsArr = array();
        $updateFieldsArr['tstamp']          = time();
        $updateFieldsArr['gsa_art_nummer']  = $dataArr['gsa_art_nummer'];
        $updateFieldsArr['path']            = $dataArr['path'];
        $updateFieldsArr['description']     = $dataArr['description'];
        $updateFieldsArr['alt']             = $dataArr['alt'];
        $updateFieldsArr['title']           = $dataArr['title'];
        
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $updateFieldsArr);
        trace(tx_pttools_div::returnLastBuiltInsertQuery($GLOBALS['TYPO3_DB'], $table, $updateFieldsArr));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        
        return $dataArr['uid'];
    	
    }
    
    /**
     * Delete article images by path
     *
     * @param	string	path
     * @param 	int		gsa article uid
     * @param 	bool	(optional) only mark as deleted, default: true
     * @throws	tx_pttools_exception	if update or delete query fails
     * @author	Fabrizio Branca <branca@punkt.de>
     */
    public function deleteImageByPath($path, $gsaUid, $onlymarkasdeleted = true){
        
        $table = 'tx_ptgsashop_article_images';
        $where = 'path = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($path, $table) . ' AND gsa_art_nummer='.intval($gsaUid);
        if ($onlymarkasdeleted){
        	$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, array('deleted' => 1));
            if ($res == false) {
                throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
            }
        } else {
            $res = $GLOBALS['TYPO3_DB']->exec_DELETEquery($table, $where);
            if ($res == false) {
                throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
            }
        }   
    }
    
    /**
     * Delete article images by uid
     *
     * @param 	int		article uid
     * @param 	bool	(optional) only mark as deleted, default: true
     * @throws	tx_pttools_exception	if update or delete query fails
     * @author	Fabrizio Branca <branca@punkt.de>
     * @since 	2008-01-25
     */
    public function deleteImageByUid($uid, $onlymarkasdeleted = true) {
        $table = 'tx_ptgsashop_article_images';
        $where = 'uid = ' . intval($uid);
        if ($onlymarkasdeleted){
        	$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, array('deleted' => 1));
            if ($res == false) {
                throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
            }
        } else {
            $res = $GLOBALS['TYPO3_DB']->exec_DELETEquery($table, $where);
            if ($res == false) {
                throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
            }
        }   
    }
    

    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_articleImageAccessor.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_articleImageAccessor.php']);
}

?>