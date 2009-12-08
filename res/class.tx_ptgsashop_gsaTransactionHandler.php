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
 * GSA transaction (ERP: "Vorgang") handler class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_gsaTransactionHandler.php,v 1.33 2008/11/28 09:43:14 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2007-06-19
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of TYPO3 libraries
 *
 * @see t3lib_div
 */
require_once(PATH_t3lib.'class.t3lib_div.php');

/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_gsaTransactionAccessor.php';  // GSA Shop database accessor class for GSA transactions
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_articleAccessor.php';  // GSA Shop database accessor class for articles
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_order.php';  // GSA Shop order class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_lib.php';  // GSA Shop library with static methods

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general helper library class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_finance.php'; // library class with finance related static methods
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_feCustomer.php';  // combined GSA/TYPO3 FE customer class
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_customer.php';  // GSA specific customer class



/**
 * GSA transaction (ERP: "Vorgang") handler class. 
 * NOTICE: This class contains temporary solutions since structure and meaning of the GSA database tables is not completely investigated!!
 * TODO: This class contains lots of unsightly temporary GSA specific database logic - this should be overhauled in a clean OOP way if the mysteries of the GSA database have cleared up
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2007-06-19 (based on parts of tx_ptgsashop_orderAccessor, since 2005-11-25)
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_gsaTransactionHandler {
    
    /**
     * Properties
     */
    protected $classConfigArr = array(); // (array) array with configuration values used by this class (this is set once in the class constructor)
    

        
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
     
    /**
     * Class constructor: sets the object's properties
     *
     * @param   void
     * @return  void     
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-19
     */
    public function __construct() {
        
        $this->classConfigArr = tx_ptgsashop_lib::getGsaShopConfig();
    
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
     
    /**
     * Prepares the enterprise resource planning (ERP) database storage (GSA DB used here) of a GSA shop order and calls the approriate insert methods
     * 
     * @param   tx_ptgsashop_order              object of type tx_ptgsashop_order: the order to process and store to the database
     * @param   tx_ptgsauserreg_feCustomer      object of type tx_ptgsauserreg_feCustomer: the customer who placed the order
     * @return  string      document number ("Vorgangsnummer") of the order confirmation ("Auftragsbestaetigung") in the ERP system
     * @throws  tx_pttools_exception   if no deliveries found in order
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-11-25 (original code from class tx_ptgsashop_orderAccessor::processErpDatabaseStorage()
     */
    public function processShopOrderTransactionStorage(tx_ptgsashop_order $orderObj, tx_ptgsauserreg_feCustomer $feCustObj) {
        
        $orderConfDocId = 0; // (integer) UID of the order confirmation document record
        $orderDocNo = ''; // (string) document number ("Vorgangsnummer") of the order confirmation ("Auftragsbestaetigung") in the ERP system
        $gsaTransactionAccessorObj = tx_ptgsashop_gsaTransactionAccessor::getInstance();
        
        // throw exception if no deliveries found in order
        if ($orderObj->countDeliveries() < 1) {
            throw new tx_pttools_exception('No deliveries found in order', 3);
        }
        
        // insert order confirmation (ERP-GUI: "Auftragsbestaetigung") document record (ERP-GUI: "Vorgang"/"Schriftstueck") for complete order
        $orderConfDocId = 
            $gsaTransactionAccessorObj->insertShopOrderTransactionDocument($orderObj, -1, -1, $feCustObj);
            
        // HOOK: allow multiple hooks to manipulate the just written order confirmation document record
        // IMPORTANT: THIS IS A TEMPORARY HOOK - HOOK WILL BE REMOVED AFTER COMPLETE ERP STORAGE REWRITE! 
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['gsaTransactionHandler_hooks']['processShopOrderTransactionStorage_manipulateOrderConfDocRecordHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['gsaTransactionHandler_hooks']['processShopOrderTransactionStorage_manipulateOrderConfDocRecordHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $hookObj->processShopOrderTransactionStorage_manipulateOrderConfDocRecordHook($orderConfDocId, $orderObj);
            }
        }
        
        // insert document position records (ERP-GUI: "Position") for all articles of the complete order
        $orderConfDocPosArr = array();
        $posCounter = 0;
        foreach ($orderObj->getCompleteArticleCollection() as $articleKey=>$articleObj) {
            $posCounter += 1;
            $orderConfDocPosArr[$articleKey] = 
                $gsaTransactionAccessorObj->insertShopOrderTransactionDocPosition($orderConfDocId, $posCounter, NULL, -1, 
                                                                                   $feCustObj, $articleObj, -1);
            
            // HOOK: allow multiple hooks to manipulate the just written order confirmation position record 
            // IMPORTANT: THIS IS A TEMPORARY HOOK - HOOK WILL BE REMOVED AFTER COMPLETE ERP STORAGE REWRITE! 
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['gsaTransactionHandler_hooks']['processShopOrderTransactionStorage_manipulateOrderConfPosRecordHook'])) {
                foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['gsaTransactionHandler_hooks']['processShopOrderTransactionStorage_manipulateOrderConfPosRecordHook'] as $className) {
                    $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                    $hookObj->processShopOrderTransactionStorage_manipulateOrderConfPosRecordHook($orderConfDocId, $posCounter, $articleKey, $orderObj);
                }
            }                                                                  
                                                                                   
        }
        
        // if enabled in TS config: insert delivery note (ERP-GUI: "Lieferschein") document records (ERP-GUI: "Vorgang"/"Schriftstueck") and related document position (ERP-GUI: "Position") records for all deliveries of the order
        if ($this->classConfigArr['gsaCreateDeliveryReceipt'] == 1) {
            foreach ($orderObj->get_deliveryCollObj() as $delKey=>$delObj) {
                
                // insert delivery note (ERP-GUI: "Lieferschein") document record (ERP-GUI: "Vorgang"/"Schriftstueck") for each delivery
                $deliveryConfDocId = 
                    $gsaTransactionAccessorObj->insertShopOrderTransactionDocument($orderObj, $delKey, $orderConfDocId, $feCustObj);
            
                // HOOK: allow multiple hooks to manipulate the just written delivery note document record
                // IMPORTANT: THIS IS A TEMPORARY HOOK - HOOK WILL BE REMOVED AFTER COMPLETE ERP STORAGE REWRITE! 
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['gsaTransactionHandler_hooks']['processShopOrderTransactionStorage_manipulateDelNoteDocRecordHook'])) {
                    foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['gsaTransactionHandler_hooks']['processShopOrderTransactionStorage_manipulateDelNoteDocRecordHook'] as $className) {
                        $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                        $hookObj->processShopOrderTransactionStorage_manipulateDelNoteDocRecordHook($deliveryConfDocId, $orderObj);
                    }
                }
                                                           
                // insert document position records (ERP-GUI: "Position") for all articles of each delivery
                $posCounter = 0;
                foreach ($delObj->get_articleCollObj() as $articleKey=>$articleObj) {
                    $posCounter += 1;
                    $gsaTransactionAccessorObj->insertShopOrderTransactionDocPosition($deliveryConfDocId, $posCounter, $delObj, $orderConfDocId, 
                                                                                       $feCustObj, $articleObj, $orderConfDocPosArr[$articleKey]);
            
                    // HOOK: allow multiple hooks to manipulate the just written delivery note position record 
                    // IMPORTANT: THIS IS A TEMPORARY HOOK - HOOK WILL BE REMOVED AFTER COMPLETE ERP STORAGE REWRITE! 
                    if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['gsaTransactionHandler_hooks']['processShopOrderTransactionStorage_manipulateDelNotePosRecordHook'])) {
                        foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['gsaTransactionHandler_hooks']['processShopOrderTransactionStorage_manipulateDelNotePosRecordHook'] as $className) {
                            $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                            $hookObj->processShopOrderTransactionStorage_manipulateDelNotePosRecordHook($deliveryConfDocId, $posCounter, $articleKey, $orderObj);
                        }
                    }                            
                    
                }
            } 
        }
         
        // return document number ("Vorgangsnummer") of the order confirmation ("Auftragsbestaetigung") in the ERP system
        $orderDocNo = $gsaTransactionAccessorObj->selectTransactionDocumentNumber($orderConfDocId);
        return $orderDocNo;
        
    }
    
    /** 
     * Continues an order confirmation to an invoice in the GSA database (ERP: "Auftrag zur Rechnung fortfuehren") and processes all appropriate GSA DB updates
     * NOTE: Depending on your environment, you may want to to update the "order wrapper" ERP doc no. too. This has to be done seperately using tx_ptgsashop_orderWrapperAccessor::updateOrderWrapperDocNoByReplacement()
     *
     * @param   string      the ERP document number (ERP: "Vorgangsnummer") of the order confirmation (=ERP: "Auftragsbestaetigung/AU")
     * @param   boolean     (optional) a passed-by-reference flag whether the returned invoice document number is from an already existing invoice document record (this param is returned to the method call context)
     * @return  string      the document number (ERP: "Vorgangsnummer") of the inserted invoice document record (or of an already existing inserted invoice document record)
     * @see     tx_ptgsashop_orderWrapperAccessor::updateOrderWrapperDocNoByReplacement()
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-19
     */ 
    public function continueOrderConfirmationToInvoice($orderConfirmationErpDocNo, &$invoiceAlreadyExists=false) {
        
        $invoiceDocFieldsArr = array();
        $invoicePosFieldsArr = array();
        $orderconfDocUpdateFieldsArr = array(); 
        $orderconfPosUpdateFieldsArr = array(); 
        $gsaTransactionAccessorObj = tx_ptgsashop_gsaTransactionAccessor::getInstance();
        $gsaArticleAccessorObj = tx_ptgsashop_articleAccessor::getInstance();
        $invoiceAlreadyExists = false;
        
        
        // ---------- check for an already existing invoice (if found: do nothing than return doc number of existing invoice) ---------- 
        $alreadyExistingInvoice = $gsaTransactionAccessorObj->selectInvoiceDocNumberForPredecessorDocNumber($orderConfirmationErpDocNo);
        if ($alreadyExistingInvoice != false) {
            
            trace('Invoice for order confirmation "'.$orderConfirmationErpDocNo.'" already exists: '.$alreadyExistingInvoice);
            $invoiceAlreadyExists = true;
            $invoiceErpDocNo = $alreadyExistingInvoice;  // this should be the document number (ERP: "Vorgangsnummer") of the existing invoice document record
            
            
        } else {
        // ---------- PROCESS TRANSACTION DOCUMENT RECORDS (GSA DB: FSCHRIFT) ---------- 
        
            // get order confirmation data
            $orderconfDocFieldsArr = $gsaTransactionAccessorObj->selectTransactionDocumentData($orderConfirmationErpDocNo);
            
            // prepare invoice data: copy order confirmation record data to invoice record data and change/overwrite relevant values
            $invoiceDocFieldsArr = $orderconfDocFieldsArr;
            
            $invoiceDocFieldsArr['NUMMER']     = 0; // will be overwritten/set by insertTransactionDocument(): database ID of the record
            $invoiceDocFieldsArr['ALTNUMMER']  = $orderconfDocFieldsArr['NUMMER'];
            # $invoiceDocFieldsArr['SCHRIFTNR']  = 0; ### TODO: do not uncomment - ERP seems _not_ to increase but to copy when using the GUI (see comment in tx_ptgsashop_gsaTransactionAccessor::insertTransactionDocument())!  // will be overwritten/set by insertTransactionDocument(): continued transaction document number ("fortlaufende Vorgangsnummer")
            $invoiceDocFieldsArr['ERFART']     = '04RE';
            $invoiceDocFieldsArr['ALTERFART']  = $orderconfDocFieldsArr['ERFART'];
            $invoiceDocFieldsArr['OPNUMMER']   = 0; // ### TODO: ERP GUI writes a different number based on file OPNR.INI (not multi-user safe!) // will be overwritten/set by insertTransactionDocument(): outstanding items numbers of invoices (ERP: "Offene Posten")
            $invoiceDocFieldsArr['GEBUCHT']    = 0;
            $invoiceDocFieldsArr['GEDRUCKT']   = 0;
            $invoiceDocFieldsArr['AUFTRAGOK']  = 0;
            $invoiceDocFieldsArr['FORTGEFUEHRT'] = 0;
            $invoiceDocFieldsArr['RESTOK']     = 0;
            $invoiceDocFieldsArr['GEMAILT']    = 0;
            $invoiceDocFieldsArr['DATUM']      = date('Y-m-d');
            $invoiceDocFieldsArr['AUFNR']      = ''; // will be overwritten/set by insertTransactionDocument(): transaction document number (ERP: "Vorgangsnummer")
            $invoiceDocFieldsArr['ALTAUFNR']   = $orderconfDocFieldsArr['AUFNR'];
            $invoiceDocFieldsArr['GUTSUMME']   = 0.0000;
            $invoiceDocFieldsArr['BEZSUMME']   = 0.0000;
            $invoiceDocFieldsArr['MKZ']        = 0;      # TODO: find out where this comes from and what it is needed for
            $invoiceDocFieldsArr['IVERTNR']    = 0;      # TODO: find out where this comes from and what it is needed for 
            $invoiceDocFieldsArr['VORLAGEERLEDIGT'] = 0; # TODO: find out where this comes from and what it is needed for 
            $invoiceDocFieldsArr['GESGEWICHT']      = 0.0000; # TODO: find out where this comes from and what it is needed for 
            #$invoiceDocFieldsArr['UHRZEIT']         = ''; # TODO: ERP GUI writes nonsense here (e.g. '1899-12-30 13:10:27')
            $invoiceDocFieldsArr['LTERMIN']         = date('d.m.Y');
            $invoiceDocFieldsArr['BEMERKUNG']       = 'Automatisch generierte Rechnung fuer Auftragsbestaetigung aus Online-Bestellung'; // data type: longblob
            $invoiceDocFieldsArr['LETZTERUSER']     = 'Online-Shop Rechnungsgenerator';  // data type: varchar(30)
            $invoiceDocFieldsArr['LETZTERUSERDATE'] = date('Y-m-d');

            // special treatment for invoices with sum total equals zero: these are marked as settled (ERP: "erledigt") now
            if ($orderconfDocFieldsArr['ENDPRB'] == 0) {
                $invoiceDocFieldsArr['AUFTRAGOK']    = 1;
                $invoiceDocFieldsArr['RESTOK']       = 1;
                $invoiceDocFieldsArr['SKONTOBETRAG'] = 0.0000;
            }
            
            // insert new invoice document record
            $invoiceDocRecordId = $gsaTransactionAccessorObj->insertTransactionDocument($invoiceDocFieldsArr, 'RE');
            $invoiceErpDocNo = $gsaTransactionAccessorObj->selectTransactionDocumentNumber($invoiceDocRecordId);
            
            // update existing order confirmation document record
            $orderconfDocUpdateFieldsArr['GEBUCHT']        = 1;
            $orderconfDocUpdateFieldsArr['AUFTRAGOK']      = 1;
            $orderconfDocUpdateFieldsArr['FORTGEFUEHRT']   = 1;
            $orderconfDocUpdateFieldsArr['RESTOK']         = 1;
            $gsaTransactionAccessorObj->updateTransactionDocument($orderConfirmationErpDocNo, $orderconfDocUpdateFieldsArr);
            
            
        // ---------- PROCESS TRANSACTION POSITION RECORDS (GSA DB: FPOS and others) ---------- 
            
            // get order confirmation positions data
            $orderconfPositionsArr = $gsaTransactionAccessorObj->selectTransactionDocPositions($orderconfDocFieldsArr['NUMMER']);
            
            // for all positions of the order confirmation document...
            if (is_array($orderconfPositionsArr)) {
                foreach ($orderconfPositionsArr as $key=>$orderconfPosFieldsArr) {
                    
                  // ...insert new invoice document position records 
                    $invoicePosFieldsArr = $orderconfPosFieldsArr; // prepare invoice position data: copy order confirmation position data to invoice position data and change/overwrite relevant values
                    $invoicePosFieldsArr['AUFINR']     = $invoiceDocRecordId;
                    $invoicePosFieldsArr['NUMMER']     = 0; // will be overwritten/set by insertTransactionDocPosition()
                    $invoicePosFieldsArr['ALTAUFINR']  = $orderconfDocFieldsArr['NUMMER'];  // uid of predeceeding document record
                    $invoicePosFieldsArr['ALTNUMMER']  = $orderconfPosFieldsArr['NUMMER'];  // uid of predeceeding position record
                    $invoicePosFieldsArr['FORTNUMMER'] = $orderconfPosFieldsArr['NUMMER'];  # TODO: find out where this comes from and what it is needed for
                    $invoicePosFieldsArr['AUFTRAG']    = 0.0000; // this is written for invoices continued from an order confirmation only (NULL for directly ERP-GUI-generated invoices)
                    $invoicePosFieldsArr['LIEFERUNG']  = 0.0000; // this is written for invoices continued from an order confirmation only (NULL for directly ERP-GUI-generated invoices)
                    $invoicePosFieldsArr['RECHNUNG']   = 0.0000; // this is written for invoices continued from an order confirmation only (NULL for directly ERP-GUI-generated invoices)
                    $invoicePosFieldsArr['GUTSCHRIFT'] = 0.0000; // this is written for invoices continued from an order confirmation only (NULL for directly ERP-GUI-generated invoices)
                    $invoicePosFieldsArr['STORNO']     = 0.0000; // this is written for invoices continued from an order confirmation only (NULL for directly ERP-GUI-generated invoices)
                    $invoicePosFieldsArr['BESTELLT']   = 0.0000; // this is written for invoices continued from an order confirmation only (NULL for directly ERP-GUI-generated invoices)
                    $invoicePosFieldsArr['BESTORNO']   = 0.0000; // this is written for invoices continued from an order confirmation only (NULL for directly ERP-GUI-generated invoices)
                    $invoicePosFieldsArr['EINGANG']    = 0.0000; // this is written for invoices continued from an order confirmation only (NULL for directly ERP-GUI-generated invoices)
                    $invoicePosFieldsArr['TEILMENGE']  = 0.0000; // this is written for invoices continued from an order confirmation only (NULL for directly ERP-GUI-generated invoices)
                    $invoicePosFieldsArr['RABATT']     = 0.0000; ### ??? TODO: find out where this comes from and what it is needed for....
                    $invoicePosFieldsArr['ZWSUMME']    = 0.0000; ### ??? TODO: find out where this comes from and what it is needed for....
                    $invoicePosFieldsArr['MITGESRAB']  = 0.0000; ### ??? TODO: find out where this comes from and what it is needed for....
                    $invoicePosFieldsArr['MITSKONTO']  = 0.0000; ### ??? TODO: find out where this comes from and what it is needed for....
                    $invoicePosFieldsArr['SONPREIS']   = 0; ### ??? TODO: find out where this comes from and what it is needed for....
                    $invoicePosFieldsArr['SONPREIS']   = 0; ### ??? TODO: find out where this comes from and what it is needed for....
                    $invoicePosFieldsArr['RGARANTIE']  = 0; ### ??? TODO: find out where this comes from and what it is needed for....
                    $invoicePosFieldsArr['RREKLAMATION']= 0; ### ??? TODO: find out where this comes from and what it is needed for....
                    $invoicePosFieldsArr['RBEMERKUNG'] = strval($orderconfPosFieldsArr['RBEMERKUNG']); // this is not NULL but empty string in ERP-GUI-generated invoices ### ??? TODO: find out where this comes from and what it is needed for....
                    $invoicePosFieldsArr['RKOSTENPFLICHT']= 0; ### ??? TODO: find out where this comes from and what it is needed for....
                    $invoicePosFieldsArr['RTEXTKUNDE'] = ''; ### ??? TODO: find out where this comes from and what it is needed for....
                    $invoicePosFieldsArr['RTEXTBERICHT']= ''; ### ??? TODO: find out where this comes from and what it is needed for....
                    $invoicePosFieldsArr['USTCODEEKONTOEG']= '00'; ### ??? TODO: find out where this comes from and what it is needed for....
                    $invoicePosFieldsArr['PROVMANU']= 0; ### ??? TODO: find out where this comes from and what it is needed for....
                    $invoicePosFieldsArr['NRESERVIERT']= $orderconfPosFieldsArr['MENGE'];
                    $invoicePosFieldsArr['ALTERFART']  = $orderconfDocFieldsArr['ERFART'];
                    $invoicePosFieldsArr['NGEBUCHT']   = 0.0000;
                    $invoicePosRecordId = $gsaTransactionAccessorObj->insertTransactionDocPosition($invoicePosFieldsArr);
                    
                  // ...update existing order confirmation position records
                    $orderconfPosUpdateFieldsArr['LIEFERUNG'] = $orderconfPosFieldsArr['MENGE'];  // copies the quantity of the position
                    $orderconfPosUpdateFieldsArr['RECHNUNG']  = $orderconfPosFieldsArr['MENGE'];  // copies the quantity of the position
                    $orderconfPosUpdateFieldsArr['NEUERWERT'] = 0;
                    $gsaTransactionAccessorObj->updateTransactionDocPosition($orderconfPosFieldsArr['NUMMER'], $orderconfPosUpdateFieldsArr);
                    
                  // ...update the related article's transaction volume ("Umsatz") data: ARTIKEL.UMSATZ (net prices!) and ARTIKEL.LETZTERUMSATZ (Format: '2007-06-19 12:00:25')
                    if ($invoiceDocFieldsArr['PRBRUTTO'] == 1) {
                        // retrieve net price from gross price if the invoice document has the gross price flag set to 1!
                        $positionTaxRate = tx_ptgsashop_lib::getTaxRate($invoicePosFieldsArr['USTSATZ'], $invoiceDocFieldsArr['DATUM']);
                        $posTotalNetPrice = tx_pttools_finance::getNetPriceFromGross($invoicePosFieldsArr['GP'], $positionTaxRate);
                    } else {
                        $posTotalNetPrice = $invoicePosFieldsArr['GP']; 
                    }
                    $gsaArticleAccessorObj->updateTransactionVolume($invoicePosFieldsArr['ARTINR'], (double)$posTotalNetPrice);
                }
            }
        }
        
        
        // ---------- return transaction document number of inserted (or already existing) invoice ---------- 
        
        return $invoiceErpDocNo;
        
    }
    
    /** 
     * Processes all appropriate GSA DB updates for the "Print Invoice" action of the ERP GUI
     * 
     * @param   string      the ERP document number of the invoice (ERP: "Rechnung/RE")
     * @return  void        
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-21
     */ 
    public function setInvoicePrintedStatus($invoiceErpDocNo) {
        
        $gsaTransactionAccessorObj = tx_ptgsashop_gsaTransactionAccessor::getInstance();
        
        // get complete transaction document data and related customer object
        $invoiceDocFieldsArr = $gsaTransactionAccessorObj->selectTransactionDocumentData($invoiceErpDocNo);
        $customerObj = new tx_ptgsauserreg_customer($invoiceDocFieldsArr['ADRINR']);
        
        // update existing invoice document record: FSCHRIFT.GEDRUCKT
        $invoiceDocUpdateFieldsArr['GEDRUCKT'] = 1;
        $gsaTransactionAccessorObj->updateTransactionDocument($invoiceErpDocNo, $invoiceDocUpdateFieldsArr);
        
        // update the related customer's contact data (ADRESSE.LKONTAKT)
        $customerObj->registerLastContact();
        
    }
    
    /** 
     * Processes all appropriate GSA DB updates for the "Book Invoice" action of the ERP GUI
     * 
     * @param   string      the GSA ERP document number of the invoice (ERP: "Rechnung/RE")
     * @param   integer     -1 (default): use TS config from shop / 0: disable supplier control / 1: enable supplier control (0/1: independently of TS config from shop)
     * @return  integer     UID of the inserted DTA record ('px_DTABUCH.NUMMER')
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-21
     */ 
    public function bookInvoice($invoiceErpDocNo, $overrideSupplierControlTsConfig=-1) {
        
        $dtaRecordId = 0;  // (integer)
        $gsaTransactionAccessorObj = tx_ptgsashop_gsaTransactionAccessor::getInstance();
        $invoiceDocFieldsArr = $gsaTransactionAccessorObj->selectTransactionDocumentData($invoiceErpDocNo); // get complete transaction document data
        $useSupplierControl = $this->classConfigArr['useSupplierControl'];
        
        // this enables a command line script without TS config to set the useSupplierControl flag which is set in TS/Constant Editor by default)
        if ($overrideSupplierControlTsConfig != -1) {
            $useSupplierControl = $overrideSupplierControlTsConfig;
        }
        
        // do booking only if booked flag is not set already for given invoice document record
        if ($invoiceDocFieldsArr['GEBUCHT'] != 1) {
        
            // get related customer object, set booking data
            $customerObj = new tx_ptgsauserreg_customer($invoiceDocFieldsArr['ADRINR']);
            $bookingSumGross = (double)$invoiceDocFieldsArr['ENDPRB'];
            $bookingDate = date('Y-m-d');
            
            // update existing invoice document record: FSCHRIFT.GEBUCHT
            $invoiceDocUpdateFieldsArr['GEBUCHT'] = 1;
            $gsaTransactionAccessorObj->updateTransactionDocument($invoiceErpDocNo, $invoiceDocUpdateFieldsArr);
            
            // update the related customer's transaction volume ("Umsatz"): ADRESSE.KUMSATZ, KUNDE.UMSATZ, KUNDE.SALDO, KUNDE.LETZTERUMSATZ
            $customerObj->registerTransactionVolume($bookingSumGross);  // TODO: netto oder brutto übergeben?? -> Analyse von wz abwarten!
            
            // if appropriate payment method for invoice is set: insert data carrier exchange record (ERP: "DTA-Buchung/Datenträgeraustausch")
            if ($invoiceDocFieldsArr['ZAHLART'] == tx_ptgsauserreg_customer::PM_DEBIT) {
                $dtaRecordId = $gsaTransactionAccessorObj->insertDtaRecord($invoiceErpDocNo, $bookingSumGross, $bookingDate, $customerObj);
            }
            
            // insert supplier control records for all article positions of this invoice (if enabled in extension configuration)
            if ($useSupplierControl == 1) {
                $invoicePositionsArr = $gsaTransactionAccessorObj->selectTransactionDocPositions($invoiceDocFieldsArr['NUMMER']);
                if (is_array($invoicePositionsArr)) {
                    foreach ($invoicePositionsArr as $positionDataArr) { 
                        $gsaTransactionAccessorObj->insertSupplierControlRecord($invoiceErpDocNo, $bookingDate, $positionDataArr['ARTINR'], 
                                                                                $positionDataArr['ARTNR'], $positionDataArr['MENGE']);
                    }
                }
            }
            
        } 
        
        return $dtaRecordId;
        
    }
    
    /** 
     * WARNING: This method is EXPERIMENTAL - DO NOT USE IN PRODUCTION ENVIRONMENT!
     * Deletes an order confirmation document record and its related position records in the GSA database and resets the related invoice's predecessor doc number
     * 
     * @param   string      the ERP document number (ERP: "Vorgangsnummer") of the order confirmation (=ERP: "Auftragsbestaetigung/AU") to delete
     * @return  void       
     * @throws  tx_pttools_exception   if param is invalid
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-07-26
     */ 
    public function deleteOrderConfirmation($orderConfirmationErpDocNo) {  // WARNING: This method is EXPERIMENTAL - DO NOT USE IN PRODUCTION ENVIRONMENT!
        
        $invoiceDocUpdateFieldsArr = array();
        $invoicePosUpdateFieldsArr = array();
        $gsaTransactionAccessorObj = tx_ptgsashop_gsaTransactionAccessor::getInstance();
        
        // throw exception if param is invalid
        if (substr($orderConfirmationErpDocNo, 0, 2) != 'AU') {
            throw new tx_pttools_exception('Wrong param', 3, 'Param for '.__METHOD__.' has to begin with "AU"');
        }
        
        // delete order confirmation position records
        $orderconfRecordUid = $gsaTransactionAccessorObj->selectTransactionDocumentUid($orderConfirmationErpDocNo);
        $posDelResult = $gsaTransactionAccessorObj->deleteTransactionDocPositions($orderconfRecordUid);
        
        // delete order confirmation document record
        $docDelResult = $gsaTransactionAccessorObj->deleteTransactionDocument($orderConfirmationErpDocNo);
              
        // reset predecessor doc data of related invoice document
        $relatedInvoiceDocNumber = $gsaTransactionAccessorObj->selectInvoiceDocNumberForPredecessorDocNumber($orderConfirmationErpDocNo);
        $invoiceDocUpdateFieldsArr['ALTNUMMER'] = NULL;  
        $invoiceDocUpdateFieldsArr['ALTAUFNR'] = NULL;  
        $invoiceDocUpdateFieldsArr['ALTERFART'] = NULL;
        #$invoiceDocUpdateFieldsArr['RMNEU'] = 1;  # TODO: find out where this comes from and what it is needed for
        $gsaTransactionAccessorObj->updateTransactionDocument($relatedInvoiceDocNumber, $invoiceDocUpdateFieldsArr);
        
        // reset predecessor doc data of related invoice's positions 
        // (NOTE: if used, GU will not have positions; if unused, ERP-GUI will crash with MySQL error when creating GU)
        $invoiceUid = $gsaTransactionAccessorObj->selectTransactionDocumentUid($relatedInvoiceDocNumber);
        $invoicePositionsArr = $gsaTransactionAccessorObj->selectTransactionDocPositions($invoiceUid);
        if (is_array($invoicePositionsArr)) {
            foreach ($invoicePositionsArr as $key=>$invoicePosFieldsArr) {    
                $invoicePosUpdateFieldsArr['ALTAUFINR'] = 0;
                $invoicePosUpdateFieldsArr['ALTNUMMER'] = NULL;
                $invoicePosUpdateFieldsArr['FORTNUMMER'] = NULL; # TODO: ???
                $invoicePosUpdateFieldsArr['GESPEICHERT'] = 1; 
                $gsaTransactionAccessorObj->updateTransactionDocPosition($invoicePosFieldsArr['NUMMER'], $invoicePosUpdateFieldsArr);
            }
        }
               
    }
    
    
    
} // end class




/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_gsaTransactionHandler.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_gsaTransactionHandler.php']);
}

?>