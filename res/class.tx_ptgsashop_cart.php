<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2005 Rainer Kuhn (kuhn@punkt.de)
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
 * Shopping cart class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_cart.php,v 1.40 2008/10/16 15:02:17 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2005-07-19
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT])
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_lib.php';  // GSA Shop library with static methods
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_article.php'; // GSA shop article class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_articleFactory.php';  // GSA shop article factory class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_articleCollection.php';// GSA shop article collection class

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_iSingleton.php'; // interface for Singleton design pattern
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_sessionStorageAdapter.php'; // storage adapter for TYPO3 _browser_ sessions



/**
 * Shopping cart class: a (currently session-based) cart containing items
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2005-07-19
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_cart extends tx_ptgsashop_articleCollection implements tx_pttools_iSingleton {
    
    /**
     * Properties
     */
    private static $uniqueInstance = NULL;   // (tx_ptgsashop_cart object) Singleton unique instance
    private static $canInstantiate = false;  // (boolean) flag wether the Singleton class can be instatiated (needed because of public constructor inherited from parent class)
    
    protected $classConfigArr = array(); // (array) array with configuration values used by this class (this is set once in the class constructor)
    
    /**
     * Class Constants
     */
    const SESSION_KEY_NAME = 'tx_ptgsashop_sessionCart'; // (string) session key name to store cart in session
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
    
    /**
     * Class constructor: Use getInstance() to get the unique instance of this object (Singleton) - called from the global scope this constructor will trigger an error
     * 
     * This special Singleton constructor is needed because of the constructor's access level 'public' inherited from parent class
     * 
     * @param   void      
     * @return  void     
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-26/2007-04-18
     */
     public function __construct() {
        
        if (self::$canInstantiate !== true) {
            trigger_error (__CLASS__.' is not supposed to be instantiated from the global scope because it is a Singleton class', E_USER_ERROR);
        }
        
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        parent::__construct();
        $this->classConfigArr = tx_ptgsashop_lib::getGsaShopConfig();
        
        trace($this);
        
    }
    
    /**
     * Returns a unique instance (Singleton) of the object. Use this method instead of the private class constructor.
     *
     * If an instance of the object already exists, this unique instance is returned. 
     * Otherwise it is tried to retrieve and return an existing instance from the session; if this fails, a new instance will be created.
     * 
     * @param   void
     * @return  tx_ptgsashop_cart      unique instance of the object (Singleton) 
     * @global     
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-26
     */
    public static function getInstance() {
        
        // if no instance of the object already exists, get instance from session or create new instance
        if (self::$uniqueInstance === NULL) {
            
            // try to get cart object from session
            trace('LOOKING FOR CART IN SESSION');
            $sessionCartObj = tx_pttools_sessionStorageAdapter::getInstance()->read(self::SESSION_KEY_NAME);
            
            // use session cart instance if it is a valid cart object
            $selfClassName = __CLASS__;
            if (is_object($sessionCartObj) && ($sessionCartObj instanceof $selfClassName)) { 
                self::$uniqueInstance = $sessionCartObj;
                trace($sessionCartObj, 0, 'USING CART FOUND IN SESSION KEY '.self::SESSION_KEY_NAME);
            
            // create new instance if no valid cart object found in session
            } else {
                trace('NO CART FOUND IN SESSION');
                self::$canInstantiate = true;
                self::$uniqueInstance = new $selfClassName;
                self::$canInstantiate = false;
            }
            
        } else {
            trace('USING EXISTING CART INSTANCE');
        }
        
        return self::$uniqueInstance;
        
    }
    
    /**
     * Final method to prevent object cloning (using 'clone'), in order to use only the singleton unique instance of the object.
     * @param   void
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-15
     */
    public final function __clone() {
        
        trigger_error('Clone is not allowed for '.get_class($this).' (Singleton)', E_USER_ERROR);
        
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**
     * Stores the complete shopping cart to the browser session
     *
     * @param   void
     * @return  void
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-08-04
     */
    public function store() { 
        
        trace($this, 0, 'STORING COMPLETE CART (AS SERIALIZED OBJECT)TO SESSION KEY '.self::SESSION_KEY_NAME);
        tx_pttools_sessionStorageAdapter::getInstance()->store(self::SESSION_KEY_NAME, $this);
        
    }
    
    /**
     * Deletes the complete shopping cart from the browser session and clears all items from the current instance
     *
     * @param   void
     * @return  void
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-02-20
     */
    public function delete() { 
        
        trace('DELETING COMPLETE CART FROM SESSION KEY '.self::SESSION_KEY_NAME);
        tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_NAME, $this);
        $this->clearItems();
        
    }
    
    
    
    /***************************************************************************
     *   REDECLARED PARENT CLASS METHODS
     **************************************************************************/
    
    /**
     * Adds an article item (with arbitrary internal quantity) and a possibly bundled article(s) to the article collection
     *
     * @param   tx_ptgsashop_article     article to add, object of type tx_ptgsashop_article required (Type Hint)
     * @param   array      (optional) only for recursion check: set of already visited article IDs
     * @return  void
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>; Fabrizio Branca <branca@punkt.de>
     * @since   2007-04-24
     */
    public function addItem(tx_ptgsashop_article $articleObj, $visitedIdsArr=array()) { 
        
        // add passed article to cart by default
        parent::addItem($articleObj);
        $visitedIdsArr[] = $articleObj->get_id();
        
        // if article relations are enabled and if bundled article(s) are found for the originally added article: add bundled article(s), too
        if ($this->classConfigArr['enableArticleRelations'] == 1 && is_array($articleObj->get_artrelBundledArr())) {
            
            foreach ($articleObj->get_artrelBundledArr() as $bundledArtId) {
                
                // instantiate new bundled article with same constructor params as originally added article
                $bundledArtObj = tx_ptgsashop_articleFactory::createArticle(
                                        $bundledArtId, 
                                        $articleObj->get_priceCategory(), 
                                        $articleObj->get_customerId(), 
                                        $articleObj->get_quantity(), 
                                        $articleObj->get_date()
                                 ); 
                
                // add bundled article to cart (avoid recursion here)
                if (!in_array($bundledArtId, $visitedIdsArr)) {
                    $this->addItem($bundledArtObj, $visitedIdsArr);
                } 
//                ### TODO: this check/exception has to be implemented in the planned web based GUI for article (relation) editing
//                else {
//                    throw new tx_pttools_exception('Article relation recursion found!', 2);
//                }
                
            }
        }
        
    }
     
     
     
    
} // end class




/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_cart.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_cart.php']);
}

?>