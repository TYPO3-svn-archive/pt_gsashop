<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2005-2006 Rainer Kuhn (kuhn@punkt.de)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is 
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
 * Extension specific library containing static methods and configuration constants for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_lib.php,v 1.42 2008/12/11 10:23:30 ry37 Exp $
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2005-03
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_taxAccessor.php';  // GSA shop database accessor class for tax data

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_finance.php'; // library class with finance related static methods



/**
 * Provides extension specific static library methods and configuration constants for the 'pt_gsashop' extension
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2005-03
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_lib {
    
    /**
     * Class Constants: general GSA shop configuration values
     */
    const SESSKEY_LASTORDERPAGE = 'tx_ptgsashop_lastOrderPage'; // (string) session key name to store the last displayed order page ID
    const SESSKEY_ORDERSUBMITTED = 'tx_ptgsashop_orderSubmitted'; // (string) session key name to store the flag wether the current order has been submitted
    
    
    
    
    /***************************************************************************
     *   STATIC EXTENSION SPECIFIC METHODS
     **************************************************************************/
     
    /**
     * For development only: clears all session-stored objects to get a "clean" testing environment
     *
     * @param   string      file name of the caller file
     * @param   integer     line number (of the caller file) where this method is called
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-10-06
     */ 
    public static function clearSession($file, $line) {
        
        include_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_cart.php';  // GSA Shop cart class
        include_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_sessionOrder.php';  // GSA Shop order class
        include_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_formReloadHandler.php'; // web form reload handler class
        include_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_sessionStorageAdapter.php'; // storage adapter for TYPO3 _browser_ sessions

        tx_pttools_sessionStorageAdapter::getInstance()->delete('tx_ptgsashop_pi1_msgBox'); 
        tx_pttools_sessionStorageAdapter::getInstance()->delete(tx_ptgsashop_cart::SESSION_KEY_NAME); 
        tx_pttools_sessionStorageAdapter::getInstance()->delete(tx_ptgsashop_sessionOrder::SESSION_KEY_NAME);
        tx_pttools_sessionStorageAdapter::getInstance()->delete(tx_pttools_formReloadHandler::TOKEN_ARRAY_SESSION_NAME);
        
        die('Session-stored objects UNSET!<br />To proceed please comment out line <b>'.$line.'</b> at <b>'.$file.'</b>.'); 
        
    }
     
    /**
     * Returns the tax rate of a given tax code (depending on the given date) using data retrieved from a GSA database query
     *
     * @param   string      tax code to use (currently '00'-'19' in GSA table 'STEUER')
     * @param   string      (optional) date to use (date string format: YYYY-MM-DD) - if not set today's date will be used
     * @return  double      tax rate depending on given params (double with 4 digits after the decimal point)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-25
     */
    public static function getTaxRate($taxCode, $date='') {
        
        $taxRate = 0.00; // (double)
        $date = (string)($date=='' ? date('Y-m-d') : $date);  // use today's date if no date is set 
        
        // "new" tax rate retrieval from DB table BHSTEUER (since ERP Version 2.8.x)
        if (tx_ptgsashop_taxAccessor::getInstance()->newTaxTableExists() == true) {
            $taxRate = tx_ptgsashop_taxAccessor::getInstance()->selectTaxRate($taxCode, $date);
         
        // "old" tax rate retrieval from DB table STEUER (up to ERP Version 2.7.x) - used for backwards compatibility to older ERP software versions
        } else {
            $taxDataArr = tx_ptgsashop_taxAccessor::getInstance()->selectTaxDataOld($taxCode);
            if ($date >= $taxDataArr['DATUM']) {
                $taxRate = (double)$taxDataArr['NSATZ'];
            } else {
                $taxRate = (double)$taxDataArr['ASATZ'];
            }
        }
        
        return $taxRate;
            
    }
     
    /**
     * Returns a given price as a string rounded and formatted to display with a calculated number of decimal digits (depending on original price and shop config)
     * 
     * IMPORTANT: If your TS shop config config.tx_ptgsashop.usePricesWithMoreThanTwoDecimals is set to 1 (enabled), this method requires PHP to be configured with  '--enable-bcmath' to enable the BCMath Arbitrary Precision Mathematics Functions (see www.php.net/bc)!
     * 
     * @param   double      price to format
     * @param   string      optional currency to add after the price
     * @param   integer     preset precision for decimals (default: -1 => retrieve precision from original price and shop config)
     * @return  string      rounded and formatted price string, number of decimals between 2 and 4 (depending on original price and shop config)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-07-13
     */
    public static function getDisplayPriceString($price, $currency='', $presetPrecision=-1) {
        
        $displayPrice = '';
        $shopConfigArr = tx_ptgsashop_lib::getGsaShopConfig();
        $presetPrecision = intval($presetPrecision);
        
        // for special needs: use preset precision for decimals
        if ($presetPrecision >=0) {
            $decimals = $presetPrecision;
        // default: retrieve precision from original price and shop config
        } else {
            $decimals = 2;
        
            // allow more than two decimals if needed (ONLY if prices with more than 2 decimals are enabled in shop config!)
            if ($shopConfigArr['usePricesWithMoreThanTwoDecimals'] == 1) {
                $decimals = 4;
                $pricePrecisionString = bcmul($price, '10000', 0); // results in a string without $price float precision problems and without decimal places (*10000 changes to integer scale for max. 4 decimals)
                trace($pricePrecisionString, 0, '$pricePrecisionString');            
                
                if (substr($pricePrecisionString, -2) === "00") {
                    $decimals = 2;
                } elseif (substr($pricePrecisionString, -1) === "0") {
                    $decimals = 3;
                }
            }
        }
        
        // get preformatted price with required number of decimals
        $displayPrice =  tx_pttools_finance::getFormattedPriceString($price, $currency, $decimals);
        
        return $displayPrice;
            
    }
     
    /**
     * Returns the typoscript config of GSA Shop
     * 
     * @param   string  (optional) name of the configuration value to get (if not set [=default], the complete "config.tx_ptgsashop." array is returned)
     * @param   boolean (optional) flag whether an exception will be thrown if no configuration found, default=true
     * @return  mixed   array: complete GSA Shop typoscript config "config.tx_ptgsashop." (if $configValue='') OR mixed: typoscript config value (if $configValue is given)
     * @throws  exceptionAssertion  if no GSA Shop typoscript config found and 2nd param is set to true
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-10-16
     */
    public static function getGsaShopConfig($configValue='', $throwExceptionIfNoConfigFound=true) {
        
        $gsaShopConfigArray = tx_pttools_div::typoscriptRegistry('config.tx_ptgsashop.', NULL, 'pt_gsashop', 'tsConfigurationPid');
        if ($throwExceptionIfNoConfigFound == true) {
            tx_pttools_assert::isNotEmptyArray($gsaShopConfigArray, array('message' => 'No GSA Shop typoscript config found.'));
        }
        
        if (!empty($configValue)) {
            $returnValue = $gsaShopConfigArray[$configValue];
        } else {
            $returnValue = $gsaShopConfigArray;
        }
        
        return $returnValue;
        
    }
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_lib.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_lib.php']);
}

?>
