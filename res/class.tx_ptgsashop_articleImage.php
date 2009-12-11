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
 * Article image class for article of the 'pt_gsashop' extension 
 *
 * $Id: class.tx_ptgsashop_articleImage.php,v 1.3 2008/02/06 13:24:08 ry44 Exp $
 *
 * @author  Fabrizio Branca <branca@punkt.de>
 * @since   2007-11-28
 */ 



/**
 * Inclusion of extension specific resources
 */


/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general helper library class



/**
 * Image class for articles
 * 
 * @author      Fabrizio Branca <branca@punkt.de>
 * @since       2007-11-29
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_articleImage {
    
    protected $uid;
    protected $gsa_art_nummer;
    protected $path = '';
    protected $description = '';
    protected $alt = '';
    protected $title = '';
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR
     **************************************************************************/
     
    /**
     * Class constructor
     *
     * @param	int		(optional) uid
     * @param	array	(optional) data array
     * @return	void
     * @author	Fabrizio Branca <branca@punkt.de>
     */
    public function __construct($uid = NULL, $dataArray = array()) {
    
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        if (!is_null($uid)){
            $dataArray = $this->loadSelf($uid);                
        }
        if (!empty($dataArray)) {
            $this->setFromDataArray($dataArray);
        }
        
    }
    


    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**
     * Load self from database
     *
     * @param 	int		uid
     * @return	void
     * @author	Fabrizio Branca <branca@punkt.de>
     */
    protected function loadSelf($uid) {
        $imgAcc = tx_ptgsashop_articleImageAccessor::getInstance();
        $dataArr = $imgAcc->selectByUid($uid);
        return $dataArr;
    }
    
    /**
     * Set properties from data array
     *
     * @param 	array data array
     * @return 	void
     * @author 	Fabrizio Branca <branca@punkt.de>
     * @since	2008-01-25
     */
    protected function setFromDataArray(array $dataArray) {
        foreach (get_class_vars( __CLASS__ ) as $propertyname => $pvalue) {
			if (isset($dataArray[$propertyname])) {
				$setter = 'set_'.$propertyname;
				$this->$setter($dataArray[$propertyname]);
			}
		}
    }
    
    /**
     * Get data array
     *
     * @param 	void
     * @return 	array	data array
     * @author 	Fabrizio Branca <branca@punkt.de>
     * @since	2008-01-25
     */
    protected function getDataArray() {
        $dataArray = array();
        foreach (get_class_vars( __CLASS__ ) as $propertyname => $pvalue) {
			if (isset($dataArray[$propertyname])) {
				$getter = 'get_'.$propertyname;
				$dataArray[$propertyname] = $this->$getter();
			}
		}
		return $dataArray;
    }
    
    /**
     * Stores itself to database
     *
     * @param 	void
     * @return 	void
     * @author 	Fabrizio Branca <branca@punkt.de>
     * @since 	2008-01-25
     */
    public function storeSelf() {
        $dataArr = $this->getDataArray();
        $imgAcc = tx_ptgsashop_articleImageAccessor::getInstance();
        $imgAcc->storeImage($dataArr);
    }
    
    /**
     * Deletes itself from database
     *
     * @param 	void
     * @return 	void
     * @author 	Fabrizio Branca <branca@punkt.de>
     * @since 	2008-01-25
     */
    public function deleteSelf() {
        if (!empty($this->uid)) {
            $imgAcc = tx_ptgsashop_articleImageAccessor::getInstance();
            $imgAcc->deleteImageByUid($this->uid);    
        } else {
            throw new tx_pttools_exception('No uid set');
        }
    }
    
	/**
     * Get property value
     *
     * @param 	void
     * @return 	string		property value
     * @author 	Fabrizio Branca <branca@punkt.de>
     * @since	2008-01-21
     */
    public function get_uid() {
        return $this->uid;
    }   
    
    /**
     * Get property value
     *
     * @param 	void
     * @return 	string		property value
     * @author 	Fabrizio Branca <branca@punkt.de>
     * @since	2008-01-21
     */
    public function get_gsa_art_nummer() {
        return $this->gsa_art_nummer;
    }   
     
	/**
     * Get property value
     *
     * @param 	void
     * @return 	string		property value
     * @author 	Fabrizio Branca <branca@punkt.de>
     * @since	2008-01-21
     */
    public function get_path() {
        return $this->path;
    }
    
    /**
     * Get property value
     *
     * @param 	void
     * @return 	string		property value
     * @author 	Fabrizio Branca <branca@punkt.de>
     * @since	2008-01-21
     */
    public function get_description() {
        return $this->description;
    }
    
    /**
     * Get property value
     *
     * @param 	void
     * @return 	string		property value
     * @author 	Fabrizio Branca <branca@punkt.de>
     * @since	2008-01-21
     */
    public function get_alt() {
        return $this->alt;
    }
    
    /**
     * Get property value
     *
     * @param 	void
     * @return 	string		property value
     * @author 	Fabrizio Branca <branca@punkt.de>
     * @since	2008-01-21
     */
    public function get_title() {
        return $this->title;
    }
    
	/**
     * Set property value
     *
     * @param 	string	property value
     * @return	void
     * @author 	Fabrizio Branca <branca@punkt.de>
     * @since	2008-01-21
     */
    public function set_uid($uid) {
         $this->uid = $uid;
    }   
    
    /**
     * Set property value
     *
     * @param 	string	property value
     * @return	void
     * @author 	Fabrizio Branca <branca@punkt.de>
     * @since	2008-01-21
     */
    public function set_gsa_art_nummer($gsa_art_nummer) {
         $this->gsa_art_nummer = $gsa_art_nummer;
    }   
     
	/**
     * Set property value
     *
     * @param 	string	property value
     * @return	void
     * @author 	Fabrizio Branca <branca@punkt.de>
     * @since	2008-01-21
     */
    public function set_path($path) {
         $this->path = $path;
    }
    
    /**
     * Set property value
     *
     * @param 	string	property value
     * @return	void
     * @author 	Fabrizio Branca <branca@punkt.de>
     * @since	2008-01-21
     */
    public function set_description($description) {
         $this->description = $description;
    }     
    
    /**
     * Set property value
     *
     * @param 	string	property value
     * @return	void
     * @author 	Fabrizio Branca <branca@punkt.de>
     * @since	2008-01-21
     */
    public function set_alt($alt) {
         $this->alt = $alt;
    }     
    
    /**
     * Set property value
     *
     * @param 	string	property value
     * @return	void
     * @author 	Fabrizio Branca <branca@punkt.de>
     * @since	2008-01-21
     */
    public function set_title($title) {
         $this->title = $title;
    }     
     
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_articleImage.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_articleImage.php']);
}

?>