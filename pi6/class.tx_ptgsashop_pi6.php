<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2006-2008 Rainer Kuhn (kuhn@punkt.de)
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
 * Frontend Plugin 'GSA Shop: ePayment return' for the 'pt_gsashop' extension.
 *
 * $Id: class.tx_ptgsashop_pi6.php,v 1.30 2008/12/11 14:40:39 ry44 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2006-09-04
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
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_epaymentReturn.php';  // GSA shop ePayment return class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_orderAccessor.php';  // GSA Shop database accessor class for orders

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_smartyAdapter.php';  // Smarty template engine adapter
require_once t3lib_extMgm::extPath('pt_mail').'res/class.tx_ptmail_mail.php';
require_once t3lib_extMgm::extPath('pt_mail').'res/class.tx_ptmail_address.php';
require_once t3lib_extMgm::extPath('pt_mail').'res/class.tx_ptmail_addressCollection.php';


/**
 * Debugging config for development
 */
#$trace     = 1; // (int) trace options @see tx_pttools_debug::trace() [for local temporary debugging use only, please COMMENT OUT this line if finished with debugging!]
#$errStrict = 1; // (bool) set strict error reporting level for development (requires $trace to be set to 1)  [for local temporary debugging use only, please COMMENT OUT this line if finished with debugging!]


// debugging output for development (uncomment to use)
#trace(TYPO3_db);
#trace($TYPO3_CONF_VARS);
#trace(t3lib_div::GPvar('tx_ptgsashop_pi6'));
#trace($_POST, 0, '$_POST');
#trace($GLOBALS['TSFE'], 0, '$GLOBALS[TSFE]');
#trace($GLOBALS['TSFE']->fe_user, 0, '$GLOBALS[TSFE]->fe_user');
#trace($GLOBALS['TSFE']->fe_user->sesData, 0, '$GLOBALS[TSFE]->fe_user->sesData');



/**
 * Provides a frontend plugin displaying confirmation pages after an ePayment return
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2006-09-04
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_pi6 extends tslib_pibase {
    
    /**
     * tslib_pibase (parent class) instance variables
     */
    public $extKey = 'pt_gsashop';    // The extension key.
    public $prefixId = 'tx_ptgsashop_pi6';    // Same as class name
    public $scriptRelPath = 'pi6/class.tx_ptgsashop_pi6.php';    // Path to this script relative to the extension dir.
    
    /**
     * tx_ptgsashop_pi6 instance variables
     */
    protected $extConfArr = array();      // (array) basic extension configuration data from localconf.php (configurable in Extension Manager)
    
    protected $epaymentReturnObj = NULL;  // (tx_ptgsashop_epaymentReturn object) ePayment return object
    
    
    
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
     * @since   2006-09-04
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
            
            $this->epaymentReturnObj = new tx_ptgsashop_epaymentReturn($this->conf['md5SecurityCheckSalt']);
            
            
            // ********** CONTROLLER: execute approriate method for any action command (retrieved form buttons/GET vars) **********
            
            // [CMD] epayment successful:
            if ($this->epaymentReturnObj->get_status() == 'successful') {
                $content .= $this->exec_epaymentSuccess();
            // [CMD] Default action: epayment not successful
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
     * @since   2006-09-04
     */
    protected function exec_defaultAction() {
        
        $content = '';
        trace('[CMD] '.__METHOD__);
        
        // set current transaction ID for order (this happens only once since after first epayment return the return data is deleted from tx_ptgsashop_epaymentReturn::__construct())
        tx_ptgsashop_orderAccessor::getInstance()->updateEpaymentId($this->epaymentReturnObj->get_bookingId(), 
                                                                    0,
                                                                    $this->epaymentReturnObj->get_transactionId(),
                                                                    $this->epaymentReturnObj->get_referenceId(),
                                                                    $this->epaymentReturnObj->get_shortId());
        
        // HOOK: allow multiple hooks to append their execution to fixing the order
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi6_hooks']['exec_defaultActionHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi6_hooks']['exec_defaultActionHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $hookObj->exec_defaultActionHook($this);
            }
        }
        
        // send epayment transaction result to shop operator recipient configured in Constant Editor
        if (!empty($this->conf['epaymentResultRecipient'])) {
            try {
                $this->sendEpaymentResultEmail(0);
            // catch mailing error exceptions to log them
            } catch (tx_pttools_exception $exceptionObj) {
                $exceptionObj->handle(); // TODO: inform site admin about mailing error?
            }
        }
        
        $content .= $this->displayPaymentResult(0);
        return $content;
        
    }
     
    /**
     * Controller default action: processes successful epayment transaction result
     *
     * @param   void        
     * @return  string      HTML plugin content for output on the page
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-09-04
     */
    protected function exec_epaymentSuccess() {
        
        $content = '';
        trace('[CMD] '.__METHOD__);
        
        // set current transaction ID  and reference ID for order (this happens only once since after first epayment return the return data is deleted from tx_ptgsashop_epaymentReturn::__construct())
        tx_ptgsashop_orderAccessor::getInstance()->updateEpaymentId($this->epaymentReturnObj->get_bookingId(), 
                                                                    1,
                                                                    $this->epaymentReturnObj->get_transactionId(),
                                                                    $this->epaymentReturnObj->get_referenceId(),
                                                                    $this->epaymentReturnObj->get_shortId());
                                                                    
        // HOOK: allow multiple hooks to append their execution to fixing the order
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi6_hooks']['exec_epaymentSuccessHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi6_hooks']['exec_epaymentSuccessHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $hookObj->exec_epaymentSuccessHook($this);
            }
        }
        
        // send epayment transaction result to shop operator recipient configured in Constant Editor
        if (!empty($this->conf['epaymentResultRecipient'])) {
            try {
                $this->sendEpaymentResultEmail(1);
            // catch mailing error exceptions to log them
            } catch (tx_pttools_exception $exceptionObj) {
                $exceptionObj->handle(); // TODO: inform site admin about mailing error?
            }
        }
        
        $content .= $this->displayPaymentResult(1);
        return $content;
        
    }
    
    
    
    /***************************************************************************
     *   BUSINESS LOGIC METHODS: GENERAL
     **************************************************************************/
    
    /**
     * Sends the ePayment result as ASCII plain text representation email to the shop's sales recipient configured in Constant Editor
     *
     * @param   boolean     flag wether the payment was successful
     * @return  void        (exception thrown on mailing error) 
     * @throws  tx_pttools_exception   if errors occur while preparing or sending the mail
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-09-01 (code base from 2005-06-24)
     */ 
    protected function sendEpaymentResultEmail($success) {

        // prepare email data
        $httpHost = t3lib_div::getIndpEnv('HTTP_HOST'); // this is empty in CLI mode
        $orderHost = (!empty($httpHost) ? $httpHost : $this->gsaShopConfig['shopName']);
        $mailSubject    = ($success == 1 ? sprintf($this->pi_getLL('email_subject_success', '[Online Payment Success]'), $orderHost)
                                         : sprintf($this->pi_getLL('email_subject_failure', '[Online Payment Failure]'), $orderHost));
        $mailMessage    = $mailSubject."\n".
                          "\n".
                          $this->pi_getLL('payment_booking_id').": ".$this->epaymentReturnObj->get_bookingId()."\n".
                          $this->pi_getLL('payment_sum').": ".sprintf("%01.2f", $this->epaymentReturnObj->get_amount())
                                                             ." ".$this->epaymentReturnObj->get_currency()."\n".
                          $this->pi_getLL('payment_description').": ".$this->epaymentReturnObj->get_description()."\n".
                          "\n".
                          "Status: ".$this->epaymentReturnObj->get_status()."\n".
                          "Transaction ID: ".$this->epaymentReturnObj->get_transactionId()."\n".
                          "Reference ID: ".$this->epaymentReturnObj->get_referenceId()."\n".
                          "Short ID: ".$this->epaymentReturnObj->get_shortId()."\n".
                          "Return Code: ".$this->epaymentReturnObj->get_returnCode()."\n".
                          "Return Message: ".$this->epaymentReturnObj->get_returnMsg()."\n"
                          ;
        #die('<pre>'.$mailMessage.'</pre>'); // for development only: display mail body and die (outcomment this line in production environment!!)

        // prepare mail object
        $mail = new tx_ptmail_mail($this, 'epaymentResult');    
        $mail->set_subject($mailSubject);   
        $mail->set_body($mailMessage);
        
        // additional email configuration (to, from, cc, bcc, reply-to) is done via typoscript, basic config see pt_gsashop/static/pt_mail_config/setup.txt
        /*
        $mailSender     = (!empty($this->conf['orderEmailSender']) ? $this->conf['orderEmailSender'] : $this->extKey.'@'.$orderHost);
        $mailRecipient  = $this->conf['epaymentResultRecipient'];
        $mail->set_to(new tx_ptmail_addressCollection(new tx_ptmail_address($mailRecipient))); 
        $mail->set_from(new tx_ptmail_address($mailSender));
        */
        
        // TODO: charsets? language?
        
        // send mail
        $result = $mail->sendMail();
        tx_pttools_assert::isTrue($result, array('message' => 'Error while sending epaymentResult email!'));
        
    }
    
    
    
    /***************************************************************************
     *   DISPLAY METHODS
     **************************************************************************/
    
    /**
     * Generates and returns the HTML code of the ePayment result display
     *
     * @param   boolean     flag wether the payment was successful        
     * @return  string      HTML code of the ePayment result display
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-09-04
     */
    protected function displayPaymentResult($success) { 
        
        $markerArray = array();
        
        // assign default placeholders
        $markerArray['ll_payment_sum'] = $this->pi_getLL('payment_sum');
        $markerArray['ll_payment_description'] = $this->pi_getLL('payment_description');
        $markerArray['payment_amount'] = sprintf("%01.2f", tx_pttools_div::htmlOutput($this->epaymentReturnObj->get_amount()));
        $markerArray['payment_currency'] = tx_pttools_div::htmlOutput($this->epaymentReturnObj->get_currency());
        $markerArray['payment_description'] = tx_pttools_div::htmlOutput($this->epaymentReturnObj->get_description());
        
        // assign conditional placeholders
        if ($success == 1) {
            $markerArray['cond_success'] = true;
            $markerArray['ll_payment_successful'] = $this->pi_getLL('payment_successful');
        } else {
            $markerArray['cond_success'] = false;
            $markerArray['ll_payment_not_successful'] = $this->pi_getLL('payment_not_successful');
        }
        
        // assign debugging placeholders (displayed for development/debugging purposes only)
        if ($this->extConfArr['prodEnv'] == 0) {
            $markerArray['cond_debugOutput'] = true;
            $markerArray['bookingId'] = tx_pttools_div::htmlOutput($this->epaymentReturnObj->get_bookingId());
            $markerArray['status'] = tx_pttools_div::htmlOutput($this->epaymentReturnObj->get_status());
            $markerArray['transactionId'] = tx_pttools_div::htmlOutput($this->epaymentReturnObj->get_transactionId());
            $markerArray['referenceId'] = tx_pttools_div::htmlOutput($this->epaymentReturnObj->get_referenceId());
            $markerArray['shortId'] = tx_pttools_div::htmlOutput($this->epaymentReturnObj->get_shortId());
            $markerArray['returnCode'] = tx_pttools_div::htmlOutput($this->epaymentReturnObj->get_returnCode());
            $markerArray['returnMsg'] = tx_pttools_div::htmlOutput($this->epaymentReturnObj->get_returnMsg());
        }
        
        
        // HOOK: allow multiple hooks to manipulate $markerArray
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi6_hooks']['displayPaymentResult_MarkerArrayHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['pi6_hooks']['displayPaymentResult_MarkerArrayHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $markerArray = $hookObj->displayPaymentResult_MarkerArrayHook($this, $markerArray); // $this is passed as a reference (since PHP5) and can be manipulated in the hook method
            }
        }
        
        // return prepared template to display
        $smarty = new tx_pttools_smartyAdapter($this);
        foreach ($markerArray as $markerKey=>$markerValue) {
            $smarty->assign($markerKey, $markerValue);
        }
        $filePath = $smarty->getTplResFromTsRes($this->conf['templateFileEpaymentReturn']);
        trace($filePath, 0, 'Smarty template resource $filePath');
        return $smarty->fetch($filePath);
        
    }
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/pi6/class.tx_ptgsashop_pi6.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/pi6/class.tx_ptgsashop_pi6.php']);
}

?>
