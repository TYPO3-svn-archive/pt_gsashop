<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2008 Rainer Kuhn (kuhn@punkt.de)
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
 * Order processor class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_orderProcessor.php,v 1.20 2009/02/20 10:54:42 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>, Fabrizio Branca <branca@punkt.de>
 * @since   2008-11-10, based on refactored code of former tx_ptgsashop_pi3::processOrderSubmission() since 2005-06-24
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_lib.php';  // GSA Shop library with static methods
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_sessionOrder.php';  // GSA shop session order class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_order.php';  // GSA Shop order class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_orderWrapper.php';  // GSA Shop order wrapper class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_orderAccessor.php';
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_orderPresentator.php';
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_cart.php';  // GSA shop cart class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_gsaTransactionHandler.php';  // GSA shop handler class for GSA transactions
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_epaymentRequest.php';  // GSA shop ePayment request class

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_assert.php'; 
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_sessionStorageAdapter.php'; // storage adapter for TYPO3 _browser_ sessions
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_paymentRequestInformation.php';
require_once t3lib_extMgm::extPath('pt_mail').'res/class.tx_ptmail_mail.php';
require_once t3lib_extMgm::extPath('pt_mail').'res/class.tx_ptmail_address.php';
require_once t3lib_extMgm::extPath('pt_mail').'res/class.tx_ptmail_addressCollection.php';
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_feCustomer.php';  // GSA/TYPO3 frontend customer class



/**
 * Order processor class for GSA Shop
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2008-11-10, refactored from code of former tx_ptgsashop_pi3::processOrderSubmission() since 2005-06-24
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_orderProcessor {
    
    /***************************************************************************
     *   CLASS PROPERTIES
     **************************************************************************/
    
    /**
     * @var string      the extension key
     */
    public $extKey = 'pt_gsashop';   
    
    /**
     * @var tx_ptgsashop_orderWrapper  order wrapper object to process (containing a valid order object of type tx_ptgsashop_order!)
     */
    protected $orderWrapperObj = NULL; 
    
    /**
     * @var boolean     flag whether credit card payment should be used for order processing
     */
    protected $useCcPayment = false;
    
    /**
     * @var array    multilingual language labels (locallang) for this class
     */
    protected $llArray = array();
    
    /**
     * @var array       GSA Shop extension config
     */
    protected $gsaShopConfig = array(); 
    
    
    
    /***************************************************************************
     *   CLASS CONSTANTS
     **************************************************************************/
    
    /**
     * @var string      path to the locallang file to use within this class
     */
    const LL_FILEPATH = 'res/locallang_res_classes.xml';
    
    /**
     * @var string      class name of the workflow plugin to use combined with this class
     */
    const WORKFLOWPLUGIN_CLASS_NAME = 'tx_ptgsashop_pi4';
    
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
     
    /**
     * Class constructor: sets the object's basic properies
     *
     * @param   tx_ptgsashop_orderWrapper   order wrapper object to process (containing a valid order object of type tx_ptgsashop_order!)
     * @param   boolean   flag whether credit card payment should be used for order processing
     * @return  void     
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-11-10
     */
    public function __construct(tx_ptgsashop_orderWrapper $orderWrapperObj, $useCcPayment=false) {
        
        $this->gsaShopConfig = tx_ptgsashop_lib::getGsaShopConfig();
        $this->llArray = tx_pttools_div::readLLfile(t3lib_extMgm::extPath($this->extKey).self::LL_FILEPATH); // get locallang data
        
        $this->set_orderWrapperObj($orderWrapperObj); // this validates the given orderWrapper object for a contained valid order object of type tx_ptgsashop_order
        $this->set_useCcPayment($useCcPayment);
        
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
     
    /**
     * Processes an order submission - this method is intended *for frontend use only* (since several redirects are performed in called sub-methods)
     *
     * @param   void       
     * @return  void    (does not return, as there will be a redirect in any case)      
     * @throws  tx_pttools_exception    if no redirect is performed at the end of processing
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-11-10, based on code of former tx_ptgsashop_pi3::processOrderSubmission() since 2005-06-24
     */
    public function processSubmission() {
        
        tx_pttools_assert::isInstanceOf($this->orderWrapperObj, 'tx_ptgsashop_orderWrapper');
        
        // save order to the order archive and to order wrapper archive (both within the TYPO3 DB)
        $this->saveToArchive();
        
        // workflow enabled: clean up session and redirect to workflow engine
        if ($this->gsaShopConfig['enableOrderWorkflow'] == true) {
            
            $this->cleanUpSession();
            $this->redirectToWorkflow();

        // workflow disabled: "fix final order" (save to GSA DB, book invoice, send order confirmation email, clean up session and redirect to payment handling) 
        } else {
            
            // save order to the GSA database and update related doc number in order wrapper record
            $this->saveToGsaDatabase();
            
            // book ERP invoices automatically (if enabled in TS config)
            if ($this->gsaShopConfig['useAutomaticInvoiceBooking'] == true) {
                $this->bookOrderConfirmationToInvoice($this->orderWrapperObj->get_relatedDocNo());
            }
            
            // process hooks to add arbitrary action to "fixing" the order
            $this->processFixFinalOrderHooks();
            
            // send order confirmation email (if enabled in TS config)
            if ($this->gsaShopConfig['sendFinalOrderEmailToCustomer'] == true) {
                try {
                    $this->sendOrderConfirmationEmail();
                // catch mailing error exceptions to log them
                } catch (tx_pttools_exception $exceptionObj) {
                    $exceptionObj->handle(); // TODO: inform site admin about mailing error?
                }
            }
            
            // process hooks to add arbitrary action after processing the order and before session clean-up
            $this->processPostOrderProcessingHooks();
            
            // clean up the browser session for a submitted order
           $this->cleanUpSession();
            
            // handle credit card payment if requested
            if ($this->useCcPayment == true) {
                $this->redirectToCcPayment();
            }
            
            // if not redirected above: redirect to order confirmation page
            $this->redirectToConfirmationPage();
            
        }
                              
        // fallback if the above redirects fail - we should not end here...
        throw new tx_pttools_exception ('Page redirection error');  
        
    }
     
    /**
     * Saves the order with an updated timestamp to the order archive and to the order wrappers archive (both within the TYPO3 database)
     *
     * @param   void       
     * @return  integer     ID of the inserted order wrapper record  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-11-10, based on code of former tx_ptgsashop_pi3::processOrderSubmission() since 2005-06-24
     */
    public function saveToArchive() {
        
        // (re-)set the order's datetime and  save it to the order archive
        $this->orderWrapperObj->get_orderObj()->set_timestamp(time());
        $archivedOrderId = $this->orderWrapperObj->get_orderObj()->saveToOrderArchive(tx_pttools_div::getPid($this->gsaShopConfig['orderStoragePid']));
        $this->orderWrapperObj->set_orderObjId($archivedOrderId);
        
        // save order to wrapper archive and log this (status depends on workflow enabled/disabled) // TODO: make amendentlog configurable
        $logText = ($this->gsaShopConfig['enableOrderWorkflow'] == 1 ? tx_pttools_div::getLLL(__CLASS__.'.logentryWorkflowInitial', $this->llArray) : tx_pttools_div::getLLL(__CLASS__.'.logentryFinalOrder', $this->llArray));
        $orderWrapperId  = $this->orderWrapperObj->saveToDatabase($logText);
        
        return $orderWrapperId;
        
    }
     
    /**
     * Saves the order to the GSA/ERP database and updates the related doc number in the order wrapper record of TYPO3 database
     *
     * @param   void       
     * @return  string      related document number ("Vorgangsnummer") of the saved order document in the GSA database respective in the ERP system   
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-11-12
     */
    public function saveToGsaDatabase() {
        
        $relatedDocNo = $this->orderWrapperObj->saveToGsaDatabase();
        
        return $relatedDocNo;
        
    }
     
    /**
     * Passes the order to GSA Shop's workflow engine and redirects to the workflow page (intented for frontend use only!)
     *
     * @param   void  
     * @return  void    (does not return, as there will be a redirect in any case) 
     * @global  $GLOBALS['TSFE']->cObj     
     * @throws  tx_pttools_exception    if no valid $GLOBALS['TSFE']->cObj is found in global scope
     * @throws  tx_pttools_exception    if no redirect is performed at the end of processing       
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-11-10, based on code of former tx_ptgsashop_pi3::processOrderSubmission() since 2005-06-24
     */
    public function redirectToWorkflow() {
        
        // works only in FE context
        tx_pttools_assert::isInstanceOf($GLOBALS['TSFE']->cObj, 'tslib_cObj');
        tx_pttools_assert::isValidUid($this->orderWrapperObj->get_uid());
        
        // redirect to workflow engine
        $urlParamArray = array(
            self::WORKFLOWPLUGIN_CLASS_NAME.'[ow_id]' => $this->orderWrapperObj->get_uid(),
            self::WORKFLOWPLUGIN_CLASS_NAME.'[init]' => 1,
            self::WORKFLOWPLUGIN_CLASS_NAME.'[__formToken]' => tx_pttools_formReloadHandler::createToken()
        );
        $redirectTarget = $GLOBALS['TSFE']->cObj->getTypoLink_URL($this->gsaShopConfig['workflowPage'], $urlParamArray);
        tx_pttools_div::localRedirect($redirectTarget);
        
        throw new tx_pttools_exception ('Page redirection error');  // fallback if redirect fails
        
    }
     
    /**
     * Continues an ERP order confirmation document to an already booked invoice document in the GSA database
     *
     * @param   string  related document number of the ERP order confirmation record to continue   
     * @throws	tx_pttools_exceptionAssertion 	if relatedErpDocNo is no order confirmation
     * @throws	tx_pttools_exceptionAssertion 	if relatedErpDocNo was already continued to an invoice before    
     * @return  void   
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-11-10, based on code of former tx_ptgsashop_pi3::processOrderSubmission() since 2005-06-24
     */
    public function bookOrderConfirmationToInvoice($relatedErpDocNo) {
        
        // check if the given relatedErpDocNo represents an order confirmation
        $transactionDocument = tx_ptgsashop_gsaTransactionAccessor::getInstance()->selectTransactionDocumentData($relatedErpDocNo);
        tx_pttools_assert::isEqual($transactionDocument['ERFART'], '02AU', array('message' => sprintf('Given relatedErpDocNo "%s" is no order confirmation document', $relatedErpDocNo)));
                
        // check if the order confirmation was already continued to an invoice before
        tx_pttools_assert::isFalse(tx_ptgsashop_gsaTransactionAccessor::getInstance()->selectInvoiceDocNumberForPredecessorDocNumber($relatedErpDocNo), array('message' => sprintf('Invoice already exists for predecessor document number "%s"', $relatedErpDocNo)));
        
        $gsaTransactionHandlerObj = new tx_ptgsashop_gsaTransactionHandler();
        $invoiceAlreadyExists = false;
        
        $invoiceErpDocNo = $gsaTransactionHandlerObj->continueOrderConfirmationToInvoice($relatedErpDocNo, $invoiceAlreadyExists);
        
        // check if new erpDocNo is an invoice
        $transactionDocument = tx_ptgsashop_gsaTransactionAccessor::getInstance()->selectTransactionDocumentData($invoiceErpDocNo);
        tx_pttools_assert::isEqual($transactionDocument['ERFART'], '04RE', array('message' => sprintf('Given relatedErpDocNo "%s" is no invoice document', $invoiceErpDocNo)));
                   
        // change the doc number in the order wrapper database record
        tx_ptgsashop_orderWrapperAccessor::getInstance()->updateOrderWrapperDocNoByReplacement($relatedErpDocNo, $invoiceErpDocNo);
        #$gsaTransactionHandlerObj->deleteOrderConfirmation($relatedErpDocNo); // WARNING: EXPERIMENTAL - DO NOT USE IN PRODUCTION ENVIRONMENT! # TODO: check consequences for ERP system
            
        // update the doc number in the current order wrapper object in memory
        $this->orderWrapperObj->set_relatedDocNo($invoiceErpDocNo); 
        
        // book invoice as required from ERP
        $gsaTransactionHandlerObj->setInvoicePrintedStatus($invoiceErpDocNo);
        $gsaTransactionHandlerObj->bookInvoice($invoiceErpDocNo);
        
    }
    
    /**
     * Processes the "fix final order" hooks
     *
     * @param   void
     * @return  void
     * @global  $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['orderProcessor_hooks']['fixFinalOrderHook']
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2008-11-10
     */
    public function processFixFinalOrderHooks() {
        
        // HOOK: allow multiple hooks to append individual action
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['orderProcessor_hooks']['fixFinalOrderHook'])) {   // TODO: changelog, former hook name was ['pi3_hooks]['processOrderSubmission_fixFinalOrderHook']
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['orderProcessor_hooks']['fixFinalOrderHook'] as $funcName) {
                $params = array(
                    'orderWrapperObj' => $this->orderWrapperObj,
                );
                t3lib_div::callUserFunction($funcName, $params, $this, '');
                if (TYPO3_DLOG) t3lib_div::devLog(sprintf('Processing hook "%s" for "fixFinalOrderHook"', $funcName), $this->extKey, 1, array('params' => $params));
            }
        }
    }
    
    /**
     * Processes the hooks to add arbitrary action after processing the order and before session clean-up
     *
     * @param   void
     * @return  void
     * @global  $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['orderProcessor_hooks']['postOrderProcessingHook']
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2009-02-20
     */
    public function processPostOrderProcessingHooks() {
        
        // HOOK: allow multiple hooks to append individual action
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['orderProcessor_hooks']['postOrderProcessingHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['orderProcessor_hooks']['postOrderProcessingHook'] as $funcName) {
                $params = array(
                    'orderWrapperObj' => $this->orderWrapperObj,
                );
                t3lib_div::callUserFunction($funcName, $params, $this, '');
                if (TYPO3_DLOG) t3lib_div::devLog(sprintf('Processing hook "%s" for "postOrderProcessingHook"', $funcName), $this->extKey, 1, array('params' => $params));
            }
        }   
    }
    
    /**
     * Cleans up the browser session: sets the "order submitted" flag in session and deletes cart & order from session
     *
     * @param   void
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-11-10, based on code of former tx_ptgsashop_pi3::processOrderSubmission() since 2005-06-24
     */
    public static function cleanUpSession() {
        
        tx_pttools_sessionStorageAdapter::getInstance()->store(tx_ptgsashop_lib::SESSKEY_ORDERSUBMITTED, true);
        
        tx_ptgsashop_cart::getInstance()->delete(); // deleting the cart from the session AND empty it
        tx_ptgsashop_sessionOrder::getInstance()->delete(); // only deleting the orderObj from the session
        
    }
    
    /**
     * Sends the order confirmation email
     *
     * @param   void
     * @return  void        (exception thrown on mailing error) 
     * @throws  tx_pttools_exception   if errors occur while preparing or sending the mail
     * @author  Rainer Kuhn <kuhn@punkt.de>, Fabrizio Branca <branca@punkt.de>
     * @since   2008-11-10
     */
    public function sendOrderConfirmationEmail() {

        // prepare confirmation email data
        $httpHost = t3lib_div::getIndpEnv('HTTP_HOST'); // this is empty in CLI mode
        $orderHost = (!empty($httpHost) ? $httpHost : $this->gsaShopConfig['shopName']);
        $mailRecipient = $this->orderWrapperObj->get_feCustomerObj()->get_feUserObj()->get_email1();
        $mailSubject = sprintf(tx_pttools_div::getLLL(__CLASS__.'.orderEmailSubject', $this->llArray), $orderHost);
        
        // prepare email body
        $orderPresentatorObj = new tx_ptgsashop_orderPresentator($this->orderWrapperObj->get_orderObj());
        $mailBody = $orderPresentatorObj->getPlaintextPresentation($this->gsaShopConfig['templateFileFinalOrderMail'], $this->orderWrapperObj->get_relatedDocNo()); // get final order as plaintext
        if (is_int(stripos($mailBody, 'Smarty error'))) {
            throw new tx_pttools_exception('Error in smarty template'); // recognize error in mail body
        }
        #die('<pre>'.$mailBody.'</pre>'); // for development only: display mail body and die (outcomment this line in production environment!!)

        // prepare mail object
        $mail = new tx_ptmail_mail($this, 'orderConfirmation');    
        $mail->set_subject($mailSubject);   
        $mail->set_body($mailBody);
        $mail->set_to(new tx_ptmail_addressCollection(new tx_ptmail_address($mailRecipient))); 
        
        // additional email configuration (from, cc, bcc, reply-to) is done via typoscript, basic config see pt_gsashop/static/pt_mail_config/setup.txt
        /*
        $mailSender = (!empty($this->gsaShopConfig['orderEmailSender']) ? $this->gsaShopConfig['orderEmailSender'] : $this->extKey.'@'.$orderHost); 
        $mail->set_from(new tx_ptmail_address($mailSender));
        if (!empty($this->gsaShopConfig['orderEmailRecipient'])) {
            $mail->set_cc(new tx_ptmail_addressCollection(new tx_ptmail_address($this->gsaShopConfig['orderEmailRecipient'])));
        }
        if (!empty($this->gsaShopConfig['orderConfirmationEmailBcc'])) {
            $mail->set_bcc(new tx_ptmail_addressCollection(new tx_ptmail_address($this->gsaShopConfig['orderConfirmationEmailBcc'])));
        }
        if (!empty($this->gsaShopConfig['orderConfirmationEmailReplyTo'])) {
            $mail->set_reply(new tx_ptmail_address($this->gsaShopConfig['orderConfirmationEmailReplyTo']));
        }
        */
        
        // TODO: charsets? language?
        
        // send mail
        $result = $mail->sendMail();
        tx_pttools_assert::isTrue($result, array('message' => 'Error while sending the order confirmation email!'));
        
    }   
    
    /**
     * Redirects to the order confirmation page (intented for frontend use only!)
     *
     * @param   void
     * @return  void    (does not return, as there will be a redirect in any case)
     * @throws  tx_pttools_exception    if no valid $GLOBALS['TSFE']->cObj is found in global scope
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2008-11-10
     */
    public function redirectToConfirmationPage() {
        
        // works only in FE context
        tx_pttools_assert::isInstanceOf($GLOBALS['TSFE']->cObj, 'tslib_cObj');
        
        $redirectTarget = $GLOBALS['TSFE']->cObj->getTypoLink_URL($this->gsaShopConfig['orderConfirmPage']);
            
        // HOOK: allow alternative orderConfirmPage
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['orderProcessor_hooks']['redirectToConfirmationPageHook'])) {
            $funcName = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['orderProcessor_hooks']['redirectToConfirmationPageHook'];  // TODO: changelog, former hook name was ['pi3_hooks]['processOrderSubmission_alternativeOrderConfirmPage']
            $params = array(
                'redirectTarget' => $redirectTarget
            );
            $redirectTarget = t3lib_div::callUserFunction($funcName, $params, $this, '');
            if (TYPO3_DLOG) t3lib_div::devLog(sprintf('Processing hook "%s" for "alternativeOrderConfirmPage"', $funcName), $this->extKey, 1, array('params'=>$params, 'return'=>$redirectTarget));
        }
        
        tx_pttools_div::localRedirect($redirectTarget);
        
        throw new tx_pttools_exception ('Page redirection error');  // fallback if redirect fails
            
    }
    
    /**
     * Stores an credit card payment request for the order into the session and redirects to the cc handling page (intented for frontend use only!)
     *
     * @param   void
     * @return  void    (does not return, as there will be a redirect in any case)
     * @throws  tx_pttools_exception    if no valid $GLOBALS['TSFE']->cObj is found in global scope
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-11-10, based on code of former tx_ptgsashop_pi3::processOrderSubmission() since 2005-06-24
     */
    public function redirectToCcPayment() {
        
        // works only in FE context
        tx_pttools_assert::isInstanceOf($GLOBALS['TSFE']->cObj, 'tslib_cObj');
        
        // default action: set epayment description from shop operator name and related ERP doc number
        $epaymentDescription = $this->gsaShopConfig['shopOperatorName'].' '.$this->orderWrapperObj->get_relatedDocNo();
        // HOOK for alternative retrieval of epayment description: use hook method if a hook has been found
        if (($hookObj = tx_pttools_div::hookRequest($this->extKey, 'orderProcessor_hooks', 'getEpaymentDescrHook')) !== false) {   // TODO: changelog, former hook name was ['pi3_hooks]['getEpaymentDescrHook']
            $epaymentDescription = $hookObj->getEpaymentDescrHook($this);
        } 
        
        if (($hookObj = tx_pttools_div::hookRequest($this->extKey, 'orderProcessor_hooks', 'epaymentProcessHook')) !== false) {
            $params = array ('orderWrapperObj' => $this->orderWrapperObj,
                             'epaymentDescription' => $epaymentDescription);
            $hookObj->getEpaymentProcessHook($params, $this);
        } else {
	        // TODO: adapt this "old" code for pt_heidelpay, use new class tx_pttools_paymentRequestInformation (see example VR-ePay impementation below)
	        // TODO: use address object filled with shopOperator Data, see tx_ptgsashop_epaymentRequest::__construct()
	        // build epayment request object and store it into session
	        $epaymentRequest = new tx_ptgsashop_epaymentRequest(
	            $this->orderWrapperObj->get_orderObj()->getPaymentSumTotal(), 
	            $this->gsaShopConfig['currencyCode'], 
	            $this->orderWrapperObj->get_relatedDocNo(), 
	            $epaymentDescription, 
	            $this->gsaShopConfig['md5SecurityCheckSalt'], 
	            $this->orderWrapperObj->get_feCustomerObj()->getDefaultBillingAddress()  
	        );
	        $epaymentRequest->storeToSession();
        }
        
        /*
        // TODO: new implementation for VR-ePay: test this and implement pt_vrepay finally
        $epaymentRequestDataArray = array(
            'salt' => $this->gsaShopConfig['md5SecurityCheckSalt'],
            'referenceNumber' => $this->orderWrapperObj->get_relatedDocNo(),
            'amount' => $this->orderWrapperObj->get_orderObj()->getPaymentSumTotal(),
            'currencyCode' => $this->gsaShopConfig['currencyCode'],
            'articleQuantity' => $this->orderWrapperObj->get_orderObj()->countArticlesTotal(),  // TODO: for VR-ePay we need position quantity (number of different articles in order); order object needs new method countPositions()...
            'infotext' => $epaymentDescription,
            // 'billingAddress' => $this->orderWrapperObj->get_feCustomerObj()->getDefaultBillingAddress() // not needed for VR-ePay
        );
        $epaymentRequestDataObj = new tx_pttools_paymentRequestInformation($epaymentRequestDataArray);
        $epaymentRequestDataObj->storeToSession();
        */
        
        // redirect to cc handling page
        $redirectTarget = $GLOBALS['TSFE']->cObj->getTypoLink_URL($this->gsaShopConfig['paymentPage']);
        tx_pttools_div::localRedirect($redirectTarget);
        
        throw new tx_pttools_exception ('Page redirection error');  // fallback if redirect fails
        
    }
    
    
    
    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
    
    /**
     * Sets the property value
     *
     * @param   tx_ptgsashop_orderWrapper       order wrapper object (containing a valid order object of type tx_ptgsashop_order!) to set
     * @return  void
     * @throws  tx_pttools_exception    if no valid order object of type tx_ptgsashop_order is found inside the given order wrapper
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-11-10
     */
    protected function set_orderWrapperObj(tx_ptgsashop_orderWrapper $orderWrapperObj) {
        
        tx_pttools_assert::isInstanceOf($orderWrapperObj->get_orderObj(), 'tx_ptgsashop_order');
        
        $this->orderWrapperObj = $orderWrapperObj;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void   
     * @return  array    property value  
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2010-07-16
     */
    public function get_llArray() {
        return $this->llArray;
    }
    
    /**
     * Returns the property value
     *
     * @param   void   
     * @return  bool    property value  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-11-12
     */
    public function get_useCcPayment() {
        
        return $this->useCcPayment;
        
    }
    
    /**
     * Sets the property value
     *
     * @param   bool    property value      
     * @return  void
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2008-11-10
     */
    public function set_useCcPayment($useCcPayment) {
        
        $this->useCcPayment = $useCcPayment;
        
    }
    
    
    
} // end class




/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_orderProcessor.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_orderProcessor.php']);
}

?>