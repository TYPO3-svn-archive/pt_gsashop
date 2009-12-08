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
 * ePayment request class for the payment extension 'pt_heidelpay'
 *
 * $Id: class.tx_ptgsashop_epaymentRequest.php,v 1.15 2008/10/16 15:02:16 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2006-09-04
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_lib.php';  // GSA Shop library with static methods
 
/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_sessionStorageAdapter.php'; // storage adapter for TYPO3 _browser_ sessions



/**
 * ePayment request class
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2006-09-04
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_epaymentRequest {
    
    /**
     * Properties
     */
    protected $type = 'creditCardDebit'; // (string)
    protected $amount = 0.00; // (float) total sum to pay (use "." as decimal point, 2 digits after decimal point!)
    protected $currency = ''; // (string) currency of payment (the ISO three letter currency code specifying the used currency, e.g. EUR for Euro, USD for US Dollar)
    protected $bookingId = ''; // (string) unique identifier of the user's shopping transaction ("Vorgangsnummer"), used by the shop system to re-assign the payment transaction to the reason for payment
    protected $description = ''; // (string) description of payment, will be sent to Heidelpay and will be printed on the customers invoice from Heidelpay
    protected $salt = ''; // (string) "salt" string to use for building an md5 hash within the epayment extension (used for security check of successful payment returns)
     
    protected $lastname = ''; // (string) lastname of the epayment customer
    protected $firstname = ''; // (string) firstname of the epayment customer
    
    protected $streetAndNo = ''; // (string) street and house number to submit to epayment provider
    protected $zip = ''; // (string) zip to submit to epayment provider
    protected $city = ''; // (string) city to submit to epayment provider
    protected $state = ''; // (string) state to submit to epayment provider
    protected $country = ''; // (string) country code (as specified in ISO 3166-1) to submit to epayment provider
    
    protected $phone = ''; // (string) phone number to submit to epayment provider
    protected $mobile = ''; // (string) mobile phone number to submit to epayment provider
    protected $email = ''; // (string) email address to submit to epayment provider
    
    protected $classConfigArr = array(); // (array) array with configuration values used by this class (this is set once in the class constructor)
    
    /**
     * Class Constants
     */
    const SESSION_KEY_NAME_REQUEST_ARRAY = 'pt_heidelpay_payment'; // (string) name of the session array used to store the request into (to be read be epayment extension)
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR
     **************************************************************************/
     
    /**
     * Class constructor
     *
     * @param   double      total sum to pay for the request (use "." as decimal point, 2 digits after decimal point!)
     * @param   string      currency of payment (3 letter code like EUR, USD, NOK etc.)
     * @param   string      booking id of the related ordering process (dt.: "Vorgangsnumer")
     * @param   string      description of payment (to be used individually)
     * @param   string      "salt" string to use for building an md5 hash within the epayment extension (used for security check of successful payment returns)   
     * @param   tx_pttools_address      object of class tx_pttools_address containing all required customer address data
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-09-04
     */
    public function __construct($amount, $currency, $bookingId, $description, $salt, tx_pttools_address $billingAddrObj) {
        
        $this->classConfigArr = tx_ptgsashop_lib::getGsaShopConfig();
        
        $this->amount = (double)$amount;
        $this->currency = (string)$currency;
        $this->bookingId = (string)$bookingId;
        $this->description = (string)$description;
        $this->salt = (string)$salt; 
        
        // customer name may be used to prefill form fields by epayment provider
        $this->lastname = (string)$billingAddrObj->get_lastname();
        $this->firstname = (string)$billingAddrObj->get_firstname();
        
        // usage of shop operator dummy data for epayment request (no transfer of customer data!)
        $this->streetAndNo = (string)$this->classConfigArr['shopOperatorStreetNo'];
        $this->zip = (string)$this->classConfigArr['shopOperatorZip'];
        $this->city = (string)$this->classConfigArr['shopOperatorCity'];
        $this->country = (string)$this->classConfigArr['shopOperatorCountryCode'];
        $this->email = (string)$this->classConfigArr['shopOperatorEmail'];
        
        /* 
        // alternative usage of real customer data for epayment request (not intended at the moment)
        $this->streetAndNo = (string)$billingAddrObj->get_streetAndNo();
        $this->zip = (string)$billingAddrObj->get_zip();
        $this->city = (string)$billingAddrObj->get_city();
        $this->state = (string)$billingAddrObj->get_state();
        $this->country = (string)$billingAddrObj->get_country();
        $this->phone = (string)$billingAddrObj->get_phone1();
        $this->mobile = (string)$billingAddrObj->get_mobile1();
        $this->email = (string)$billingAddrObj->get_email1();
         */
         
        trace($this);
        
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**
     * Stores the payment request as a serialized array to the browser session
     *
     * @param   void        
     * @return  void          
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-09-04
     */
    public function storeToSession() {
        
        $paymentArr = array();
        
        $paymentArr['type'] = $this->type;
        $paymentArr['amount'] = $this->amount;
        $paymentArr['currency'] = $this->currency;
        $paymentArr['book_id'] = $this->bookingId;
        $paymentArr['description'] = $this->description;
        $paymentArr['salt'] = $this->salt;
        
        $paymentArr['lastname'] = $this->lastname;
        $paymentArr['firstname'] = $this->firstname;
        
        $paymentArr['street'] = $this->streetAndNo;
        $paymentArr['zip'] = $this->zip;
        $paymentArr['city'] = $this->city;
        $paymentArr['state'] = $this->state;
        $paymentArr['country'] = $this->country;
        
        $paymentArr['email'] = $this->email;
        $paymentArr['phone'] = $this->phone;
        $paymentArr['mobile'] = $this->mobile;
        
        tx_pttools_sessionStorageAdapter::getInstance()->store(self::SESSION_KEY_NAME_REQUEST_ARRAY, $paymentArr);
        
    }
    
    
    
    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
     
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-09-04
     */
    public function get_type() {
        
        return $this->type;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  double      property value
     * @since   2006-09-04
     */
    public function get_amount() {
        
        return $this->amount;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-09-04
     */
    public function get_currency() {
        
        return $this->currency;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-09-15
     */
    public function get_bookingId() {
        
        return $this->bookingId;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-09-04
     */
    public function get_description() {
        
        return $this->description;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-09-04
     */
    public function get_lastname() {
        
        return $this->lastname;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-09-04
     */
    public function get_firstname() {
        
        return $this->firstname;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-09-04
     */
    public function get_streetAndNo() {
        
        return $this->streetAndNo;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-09-04
     */
    public function get_zip() {
        
        return $this->zip;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-09-04
     */
    public function get_city() {
        
        return $this->city;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-09-04
     */
    public function get_state() {
        
        return $this->state;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-09-04
     */
    public function get_country() {
        
        return $this->country;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-09-04
     */
    public function get_phone() {
        
        return $this->phone;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-09-04
     */
    public function get_mobile() {
        
        return $this->mobile;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-09-04
     */
    public function get_email() {
        
        return $this->email;
        
    }

    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_epaymentRequest.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_epaymentRequest.php']);
}

?>
