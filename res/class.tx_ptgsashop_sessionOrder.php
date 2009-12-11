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
 * Session order class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_sessionOrder.php,v 1.10 2008/10/27 10:22:11 ry44 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2007-04-18
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_order.php';  // GSA Shop order class

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_iSingleton.php'; // interface for Singleton design pattern
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_sessionStorageAdapter.php'; // storage adapter for TYPO3 _browser_ sessions


/**
 * Session order class
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2007-04-18
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_sessionOrder extends tx_ptgsashop_order implements tx_pttools_iSingleton {
    
    /**
     * Properties
     */
    private static $uniqueInstance = NULL;   // (tx_ptgsashop_sessionOrder object) Singleton unique instance
    private static $canInstantiate = false;  // (boolean) flag wether the Singleton class can be instatiated (needed because of public constructor inherited from parent class)
    
    /**
     * Class Constants
     */
    const SESSION_KEY_NAME = 'tx_ptgsashop_sessionOrder'; // (string) session key name to store order in session
    
    
    
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
     * @since   2007-04-18
     */
     public function __construct() {
        
        if (self::$canInstantiate !== true) {
            trigger_error (__CLASS__.' is not supposed to be instantiated from the global scope because it is a Singleton class', E_USER_ERROR);
        }
        
        trace('***** Creating new '.__CLASS__.' object. *****');
        parent::__construct();
        trace($this);
        
    }
    
    /**
     * Returns a unique instance (Singleton) of the object. Use this method instead of the protected class constructor.
     *
     * If an instance of the object already exists, this unique instance is returned. 
     * Otherwise it is tried to retrieve and return an existing instance from the browser session; if this fails, a new instance will be created.
     * 
     * @param   void   
     * @return  tx_ptgsashop_sessionOrder      unique instance of the object (Singleton) 
     * @global     
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-04-18 (original code from 2005-07-26)
     */
    public static function getInstance() {
        
        // if no instance of the object already exists in memory, get instance from browser session or create new instance
        if (self::$uniqueInstance === NULL) {
            
            // try to get session order object from session
            trace('LOOKING FOR EXISTING SESSION ORDER IN BROWSER SESSION');
            $sessionOrderObj = tx_pttools_sessionStorageAdapter::getInstance()->read(self::SESSION_KEY_NAME);
            
            // use instance from browser session if it is a valid session order object
            $selfClassName = __CLASS__;
            if (is_object($sessionOrderObj) && ($sessionOrderObj instanceof $selfClassName)) { 
                self::$uniqueInstance = $sessionOrderObj;
                trace($sessionOrderObj, 0, 'USING SESSION ORDER FOUND IN SESSION KEY '.self::SESSION_KEY_NAME);
            
            // create new instance if no valid session order object is found in browser session
            } else {
                trace('NO SESSION ORDER FOUND IN BROWSER SESSION');
                self::$canInstantiate = true;
                self::$uniqueInstance = new $selfClassName;
                self::$canInstantiate = false;
            }
            
        } else {
            trace('USING EXISTING SESSION ORDER INSTANCE FROM MEMORY');
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
        
        trigger_error('Clone is not allowed for '.__CLASS__.' (Singleton)', E_USER_ERROR);
        
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**
     * Stores the complete session order to the browser session
     *
     * @param   void
     * @return  void
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-29
     */
    public function store() { 
        
        trace($this, 0, 'STORING COMPLETE SESSION ORDER (AS SERIALIZED OBJECT) TO BROWSER SESSION KEY '.self::SESSION_KEY_NAME);
        tx_pttools_sessionStorageAdapter::getInstance()->store(self::SESSION_KEY_NAME, $this);
        
    }
    
    /**
     * Deletes the complete the session order from the browser session
     *
     * @param   void
     * @return  void
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-02-20
     */
    public function delete() { 
        
        trace('DELETING COMPLETE SESSION ORDER FROM BROWSER SESSION KEY '.self::SESSION_KEY_NAME);
        tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_NAME, $this);
        
    }
    
    /**
     * Deletes existing session order object and recreates it with data of the given orderObj
     * 
     * @param   tx_ptgsashop_order      order object of type tx_ptgsashop_order
     * @return  void
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-04-19
     */
    public function createFromOrderObj(tx_ptgsashop_order $orderObj) {
        
        $this->delete();
        
        foreach ($orderObj->getPropertyArray() as $propertyname => $pvalue) {
            $setter = 'set_'.$propertyname;
            if (method_exists($this, $setter) && $pvalue != NULL) {
                $this->$setter($pvalue);
            }
        }
          
        $this->store();
       
    }
    
    
    
} // end class




/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_sessionOrder.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_sessionOrder.php']);
}

?>