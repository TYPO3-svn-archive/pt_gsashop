<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Rainer Kuhn <kuhn@punkt.de>
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
 * Submodule 'GSA Cache' of the 'pt_gsashop' extension.
 *
 * $Id: class.tx_ptgsashop_module1.php,v 1.7 2008/12/16 16:09:03 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2008-02-06
 */ 



/**
 * Inclusion of TYPO3 resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_beSubmodule.php'; // abstract backend submodule parent class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_cacheController.php';

/**
 * Debugging config for development
 */
#$trace     = 1; // (int) trace options @see tx_pttools_debug::trace() [for local temporary debugging use only, please COMMENT OUT this line if finished with debugging!]
#$errStrict = 1; // (bool) set strict error reporting level for development (requires $trace to be set to 1)  [for local temporary debugging use only, please COMMENT OUT this line if finished with debugging!]



/**
 * Class for backend submodule 'GSA Cache' of the 'pt_gsashop' extension.
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2008-02-06
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_module1 extends tx_pttools_beSubmodule {
    
    
    
    /***************************************************************************
     *   INHERITED TYPO3 BE API METHODS
     **************************************************************************/
     
    /**
     * Initializes the module
     *
     * @param       void
     * @return      void
     * @author      Rainer Kuhn <kuhn@punkt.de>
     * @since       2008-02-06
     */
    public function init() {
        
        try {
            
            // set parent class properties
            $this->extKey = 'pt_gsashop';
            $this->extPrefix = 'tx_ptgsashop';  // extension prefix (for CSS classes, session keys etc.)
            
            # TODO: maybe we need the following parent properties in the future...
            #$this->cssRelPath = '../res/css/submodules.css';  // path to the CSS file to use for this module (relative path from the module's index.php file)
            #$extConfArray = tx_pttools_div::returnExtConfArray($this->extKey);
            #$this->conf = tx_pttools_div::returnTyposcriptSetup($extConfArray['tsConfigurationPid'], 'plugin.'.$this->extPrefix.'.');
        
            parent::init(); // calls tx_pttools_submodules::init()
            
        } catch (Exception $excObj) {
            
            if (method_exists($excObj, 'handle')) {
                $excObj->handle();    
            }
            die(($excObj instanceof tx_pttools_exception) ? $excObj->__toString() : $this->ll('exception_catched'));
            
        }
        
    }

    /**
     * Empty method prevents jump menu to be displayed by parent method
     *
     * @param       void
     * @return      void
     * @author      Rainer Kuhn <kuhn@punkt.de>
     * @since       2008-02-06
     */
    public function menuConfig() { 
    
    }

    /**
     * "Controller": Calls the appropriate action and returns the module's HTML content [only one action possible currently]
     *
     * @param       void
     * @return      string      the module's HTML content
     * @global      $GLOBALS['LANG']
     * @author      Rainer Kuhn <kuhn@punkt.de>
     * @since       2008-02-06
     */
    public function moduleContent() {
        
        $moduleContent = '';
        
        $cacheSuccess = tx_ptgsashop_cacheController::getInstance()->insertArticlesIntoCacheTable();
        
        if ($cacheSuccess == true) {
            $moduleContent .= $this->ll('confirmation_cache_refreshed');
        } else {
            $moduleContent .= $this->ll('notice_cache_no_articles');
        }
        
        return $moduleContent;
        
    }
    
    
    
    /***************************************************************************
     *   BUSINESS LOGIC METHODS
     **************************************************************************/
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/mod_gsacache/class.tx_ptgsashop_module1.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/mod_gsacache/class.tx_ptgsashop_module1.php']);
}

?>