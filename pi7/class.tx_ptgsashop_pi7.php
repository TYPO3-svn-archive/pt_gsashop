<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2006 Rainer Kuhn (kuhn@punkt.de)
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
 * Frontend Plugin 'GSA Shop: Cart box' for the 'pt_gsashop' extension.
 *
 * $Id: class.tx_ptgsashop_pi7.php,v 1.13 2008/12/11 14:40:39 ry44 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2006-12-18
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of TYPO3 libraries
 *
 * @see tslib_pibase
 */
require_once(PATH_tslib.'class.tslib_pibase.php');

/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_lib.php';  // GSA shop library class with static methods
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_cart.php';  // GSA shop cart class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_sessionFeCustomer.php';  // GSA shop frontend customer class

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_smartyAdapter.php';  // Smarty template engine adapter


/**
 * Debugging config for development
 */
#$trace     = 1; // (int) trace options @see tx_pttools_debug::trace() [for local temporary debugging use only, please COMMENT OUT this line if finished with debugging!]
#$errStrict = 1; // (bool) set strict error reporting level for development (requires $trace to be set to 1)  [for local temporary debugging use only, please COMMENT OUT this line if finished with debugging!]


// debugging output for development (uncomment to use)
#trace(TYPO3_db);
#trace($TYPO3_CONF_VARS);
#trace(t3lib_div::GPvar('tx_ptgsashop_pi7'));
#trace($_POST, 0, '$_POST');
#trace($GLOBALS['TSFE'], 0, '$GLOBALS[TSFE]');
#trace($GLOBALS['TSFE']->fe_user, 0, '$GLOBALS[TSFE]->fe_user');
#trace($GLOBALS['TSFE']->fe_user->sesData, 0, '$GLOBALS[TSFE]->fe_user->sesData');



/**
 * Provides a frontend plugin displaying a small cart overview box
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2006-12-18
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_pi7 extends tslib_pibase {
    
    /**
     * tslib_pibase (parent class) instance variables
     */
    public $extKey = 'pt_gsashop';    // The extension key.
    public $prefixId = 'tx_ptgsashop_pi7';    // Same as class name
    public $scriptRelPath = 'pi7/class.tx_ptgsashop_pi7.php';    // Path to this script relative to the extension dir.
    
    /**
     * tx_ptgsashop_pi7 instance variables
     */
    protected $extConfArr = array();      // (array) basic extension configuration data from localconf.php (configurable in Extension Manager)
    
    protected $cartObj = NULL;        // (tx_ptgsashop_cart object) shopping cart object
    protected $customerObj = NULL;    // (tx_ptgsashop_sessionFeCustomer object) frontend customer object (FE user who uses this plugin)
    protected $isNetPriceDisplay = 0; // (boolean) flag wether this plugin is called by a FE user who is legitimated to use net prices (0=gross prices, 1=net prices)
    
    /**
     * Class Constants
     */
    const CARTPLUGIN_CLASS_NAME = 'tx_ptgsashop_pi1'; // (string) class name of the main shopping cart plugin to use combined with this plugin
    
    
    
    /***************************************************************************
     *   MAIN
     **************************************************************************/
    
    /** 
     * Main method of the plugin: Prepares properties and instances, interprets submit buttons to control plugin behaviour and returns the page content
     *
     * @param   string      HTML-Content of the plugin to be displayed within the TYPO3 page
     * @param   array       Global configuration for this plugin (mostly done in Constant Editor/TS setup)
     * @return  string      HTML plugin content for output on the page (if not redirected before)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-12-18
     */
    public function main($content, $conf) {
           
        // ********** DEFAULT PLUGIN INITIALIZATION **********
        
        $this->conf = $conf; // Extension configuration (mostly taken from Constant Editor)
        $this->pi_setPiVarDefaults();
        $this->pi_loadLL();
        $this->pi_USER_INT_obj = 1; // Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
        
        // for TYPO3 3.8.0+: enable storage of last built SQL query in $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery for all query building functions of class t3lib_DB
        $GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;
            
        // debug tools for development (uncomment to use)
        #tx_ptgsashop_lib::clearSession(__FILE__, __LINE__); // unset all session objects
        trace($this->conf, 0, '$this->conf'); // extension config data (mainly from TS setup/Constant Editor)
        #trace($this->piVars, 0, '$this->piVars');
        #trace($this->cObj->data, 0, '$this->cObj->data'); // content element data, containing tx_ptgsashop_* plugin config
            
        // get basic extension configuration data from localconf.php (configurable in Extension Manager) - HAS TO BE PLACED BEFORE 'date_default_timezone_set()'!
        $this->extConfArr = tx_pttools_div::returnExtConfArray($this->extKey);
        
        // set the timezone (see http://php.net/manual/en/timezones.php) since it is not safe to rely on the system's timezone settings
        $previousTimeZone = date_default_timezone_get();
        date_default_timezone_set($this->extConfArr['timezone']);
        
        // DEV ONLY: set strict error reporting level for development
        if ($this->extConfArr['prodEnv'] == 0 && $GLOBALS['errStrict'] == 1 && $GLOBALS['trace'] == 1) { 
            $oldErrorReportingLevel = error_reporting(E_ALL | E_STRICT);
        } 
        
        
        try {
            
            // ********** CHECK PLUGIN REQUIREMENTS (required for all further steps) **********
            
            
            
            // ********** SET PLUGIN-OBJECT PROPERTIES **********
            
            $this->cartObj           = tx_ptgsashop_cart::getInstance(); // get unique instance (Singleton) of shopping cart (filled with session items)
            $this->customerObj       = tx_ptgsashop_sessionFeCustomer::getInstance(); // get unique instance (Singleton) of current FE customer
            $this->isNetPriceDisplay = $this->customerObj->getNetPriceLegitimation(); // set flag wether this plugin has been called by a FE user who is legitimated to use net prices
            
            
            // ********** CONTROLLER: execute approriate method for any action command (retrieved form buttons/GET vars) **********
            
            // [CMD] Clear cart
            if (isset($this->piVars['cart_clear_button'])) {
                #$content .= $this->exec_clearCart();  // not implemented here - this is done by pi1 (shopping cart plugin)
            // [CMD] Default action: display cart box
            } else {                
                $content .= $this->exec_defaultAction();
            }
            
            
            // ********** DEFAULT PLUGIN FINALIZATION ********** 
            
            
            
        } catch (Exception $excObj) {
            
            // if an exception has been catched, handle it and overwrite plugin content with error message
            if (method_exists($excObj, 'handle')) {
                $excObj->handle();    
            }
            $content = '<i>'.($excObj instanceof tx_pttools_exception) ? $excObj->__toString() : $this->pi_getLL('exception_catched').'</i>';
            
        }
        
        // reset the timezone to the "old" timezone
        date_default_timezone_set($previousTimeZone);
        
        // DEV ONLY: reset error reporting level
        if ($this->extConfArr['prodEnv'] == 0 && $GLOBALS['errStrict'] == 1 && isset($oldErrorReportingLevel)) {
            error_reporting($oldErrorReportingLevel);
        }
        
        return $this->pi_wrapInBaseClass($content);
        
        
    } // end fuction main
    
    
    
    /***************************************************************************
     *   BUSINESS LOGIC METHODS: CONTROLLER ACTIONS
     **************************************************************************/
     
    /**
     * Controller default action: processes non-successful epayment transaction result
     *
     * @param   void        
     * @return  string      HTML plugin content for output on the page
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-12-18
     */
    protected function exec_defaultAction() {
        
        $content = '';
        trace('[CMD] '.__METHOD__);
        
        $content .= $this->displayCartBox();
        return $content;
        
    }
    
    
    
    /***************************************************************************
     *   BUSINESS LOGIC METHODS: GENERAL
     **************************************************************************/
    
    
    
    /***************************************************************************
     *   DISPLAY METHODS
     **************************************************************************/
    
    /**
     * Generates and returns the HTML code of the cart box display
     *
     * @param   void      
     * @return  string      HTML code of the cart box display
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-12-18
     */
    protected function displayCartBox() { 
        
        $markerArray = array();
        
        // assign template placeholders
        $markerArray['faction_cartbox'] = $this->pi_getPageLink($this->conf['shoppingcartPage']);
        
        if ($this->conf['cartboxDisplayHeader']) { 
            $markerArray['cond_displayHeader'] = true;
            $markerArray['cartlinkTarget'] = $this->pi_getPageLink($this->conf['shoppingcartPage']);
            $markerArray['ll_cartlink_title'] = $this->pi_getLL('cartlink_title');
            if ($this->conf['cartboxUseImageHeader']) { 
                $markerArray['cond_useImageHeader'] = true;
                $markerArray['ll_box_header_imgsrc'] = $GLOBALS['TSFE']->tmpl->getFileName($this->pi_getLL('box_header_imgsrc'));
            } else {
                $markerArray['cond_useImageHeader'] = false;
                $markerArray['ll_box_header'] = $this->pi_getLL('box_header');
            }
        }
        if ($this->conf['cartboxDisplayPositions']) { 
            $markerArray['cond_displayPositions'] = true;
            $markerArray['ll_cart_contents'] = $this->pi_getLL('cart_contents');
            $markerArray['qtyCartPos'] = $this->cartObj->count();
            $markerArray['ll_cart_positions'] = $this->pi_getLL('cart_positions');
        }
        if ($this->conf['cartboxDisplayCartSum']) { 
            $markerArray['cond_displayCartSum'] = true;
            $markerArray['ll_cart_sum_total'] = $this->pi_getLL('cart_sum_total');
            $markerArray['itemsSumTotal'] = tx_pttools_div::htmlOutput(tx_ptgsashop_lib::getDisplayPriceString($this->cartObj->getItemsTotal($this->isNetPriceDisplay), '', 2));
            $markerArray['currencyCode'] = $this->conf['currencyCode'];
        }
        if ($this->conf['cartboxDisplayClearCartButton']) { 
            $markerArray['cond_displayClearCartButton'] = true;
            $markerArray['fname_clearButton'] = self::CARTPLUGIN_CLASS_NAME.'[cart_clear_button]';
            $markerArray['ll_cart_clear_button'] = $this->pi_getLL('cart_clear_button');
            $markerArray['ll_cart_clear_warning'] = $this->pi_getLL('cart_clear_warning');
        }
        
        
        // HOOK: allow multiple hooks to manipulate $markerArray
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi7_hooks']['displayCartBox_MarkerArrayHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi7_hooks']['displayCartBox_MarkerArrayHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $markerArray = $hookObj->displayCartBox_MarkerArrayHook($this, $markerArray); // $this is passed as a reference (since PHP5) and can be manipulated in the hook method
            }
        }
        
        // return prepared template to display
        $smarty = new tx_pttools_smartyAdapter($this);
        foreach ($markerArray as $markerKey=>$markerValue) {
            $smarty->assign($markerKey, $markerValue);
        }
        $filePath = $smarty->getTplResFromTsRes($this->conf['templateFileCartBox']);
        trace($filePath, 0, 'Smarty template resource $filePath');
        return $smarty->fetch($filePath);
        
    }
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/pi7/class.tx_ptgsashop_pi7.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/pi7/class.tx_ptgsashop_pi7.php']);
}

?>
