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
 * Database accessor class for GSA transactions (ERP: "Vorgang") of the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_gsaTransactionAccessor.php,v 1.28 2008/12/11 09:09:39 ry44 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2007-06-19 (based on parts of tx_ptgsashop_orderAccessor, since 2005-11-25)
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */


/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_lib.php';  // GSA Shop library with static methods
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_order.php';  // GSA Shop order class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_articleAccessor.php';  // GSA Shop database accessor class for articles

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_gsasocket').'res/class.tx_ptgsasocket_gsaDbAccessor.php'; // parent class for all GSA database accessor classes
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_iSingleton.php'; // interface for Singleton design pattern
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_assert.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general helper library class
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_feCustomer.php';  // combined GSA/TYPO3 FE customer class
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_customer.php';  // GSA specific customer class



/**
 * Database accessor class for GSA transactions (ERP: "Vorgang"), based on ERP compatible GSA database structure
 * TODO: This class contains lots of unsightly temporary ERP specific database logic - this should be overhauled in a clean OOP way if the mysteries of the ERP database have cleared up
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2007-06-19 (based on parts of tx_ptgsashop_orderAccessor, since 2005-11-25)
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_gsaTransactionAccessor extends tx_ptgsasocket_gsaDbAccessor implements tx_pttools_iSingleton {
    
    
    
    /**
     * Properties
     */
    private static $uniqueInstance = NULL; // (tx_ptgsashop_gsaTransactionAccessor object) Singleton unique instance
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
    
    /**
     * Returns a unique instance (Singleton) of the object. Use this method instead of the private/protected class constructor.
     *
     * @param   void
     * @return  tx_ptgsashop_gsaTransactionAccessor      unique instance of the object (Singleton) 
     * @global     
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-11-25
     */
    public static function getInstance() {
        
        if (self::$uniqueInstance === NULL) {
            $className = __CLASS__;
            self::$uniqueInstance = new $className;
        }
        return self::$uniqueInstance;
        
    }
    
    
    
    /***************************************************************************
     *   GSA DB RELATED METHODS
     **************************************************************************/
     
    /**
     * Inserts a new online order related document record into the GSA DB-table 'FSCHRIFT' and returns the inserted record's UID
     * 
     * @param   tx_ptgsashop_order      object of type tx_ptgsashop_order containing the order data to insert
     * @param   integer     Number of delivery (in the order's delivery collection) to generate document for: use -1 for confirmations of the whole order, or for delivery notes use the number (key) of the appropriate delivery in the order's delivery collection 
     * @param   integer     UID of the related predecessing document ("Vorgaengerdokument"): use -1 for order confirmations, or for delivery notes use the UID of the related predecessing order confirmation
     * @param   tx_ptgsauserreg_feCustomer      object of type tx_ptgsauserreg_feCustomer containing the required customer data
     * @return  integer     UID of the inserted record
     * @throws  tx_pttools_exception   if no deliveries found in order
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-11-25 (original code from tx_ptgsashop_orderAccessor::insertErpOnlineOrderRelatedDocument())
     */
    public function insertShopOrderTransactionDocument(tx_ptgsashop_order $orderObj, $deliveryNo, $predecessorDocUid, tx_ptgsauserreg_feCustomer $feCustObj) {
                
        // throw exception if no deliveries found in order
        if ($orderObj->countDeliveries() < 1) {
            throw new tx_pttools_exception('No deliveries found in order', 3);
        }
        
        $insertFieldsArr = array();
        $tmpDataArr = array();
        $gsaZahlArt = '';
        
        // resolve GSA "Zahlart" from payment method
        if ($orderObj->get_paymentMethodObj()->get_method() == 'bt') {
            $gsaZahlArt = tx_ptgsauserreg_customer::PM_INVOICE;
        } elseif ($orderObj->get_paymentMethodObj()->get_method() == 'cc') {
            $gsaZahlArt = tx_ptgsauserreg_customer::PM_CCARD;
        } elseif ($orderObj->get_paymentMethodObj()->get_method() == 'dd') {
            $gsaZahlArt = tx_ptgsauserreg_customer::PM_DEBIT;
        }
        
        // case differentiation for different document types
        if ($predecessorDocUid == -1) { 
            // document is order confirmation ("Auftragsbestaetigung")
            $tmpDataArr['gsaPredecessorDocUid'] = NULL;
            $tmpDataArr['gsaDocTypeNo']         = '02';
            $tmpDataArr['gsaDocTypeAbbr']       = 'AU';      #### TODO: multilang?
            $tmpDataArr['gsaPredecessorDocNo']  = NULL;
            $tmpDataArr['deliveryAddressUid']   = NULL;
            $tmpDataArr['totalSumGross']        = $orderObj->getOrderSumTotal(0);
            $tmpDataArr['totalSumNet']          = $orderObj->getOrderSumTotal(1);
            $tmpDataArr['dispatchCostTypeName'] = $orderObj->get_deliveryCollObj()->getIterator()->current()->get_dispatchObj()->get_costTypeName(); // for complete orders: retrieves the dispatch cost type name of an arbitrary delivery in the order
            $tmpDataArr['dispatchCost']         = $orderObj->getDispatchSumTotal(1); ##### TODO: clarify net/gross?!
            $tmpDataArr['comment']              = 'Automatisch generierte Auftragsbestaetigung fuer Online-Bestellung von '.date('d.m.Y H:i:s', $orderObj->get_timestamp());
        
        } else { 
            // document is delivery note ("Lieferschein")
            $tmpDataArr['gsaPredecessorDocUid'] = $predecessorDocUid;
            $tmpDataArr['gsaDocTypeNo']         = '03';
            $tmpDataArr['gsaDocTypeAbbr']       = 'LI';     #### multilang?
            $tmpDataArr['gsaPredecessorDocNo']  = $this->selectTransactionDocumentNumber($predecessorDocUid);
            $tmpDataArr['deliveryAddressUid']   = $orderObj->getDelivery($deliveryNo)->get_shippingAddrObj()->get_uid();
            $tmpDataArr['totalSumGross']        = $orderObj->getDelivery($deliveryNo)->getDeliveryTotal(0);
            $tmpDataArr['totalSumNet']          = $orderObj->getDelivery($deliveryNo)->getDeliveryTotal(1);
            $tmpDataArr['dispatchCostTypeName'] = $orderObj->getDelivery($deliveryNo)->get_dispatchObj()->get_costTypeName();
            $tmpDataArr['dispatchCost']         = $orderObj->getDelivery($deliveryNo)->getDeliveryDispatchCost(1); ##### TODO: clarify net/gross?! 
            $tmpDataArr['comment']              = '('.date('d.m.Y H:i:s').') Automatisch generierter Lieferschein zur Auftragsbestaetigung fuer Online-Bestellung von '.date('d.m.Y H:i:s', $orderObj->get_timestamp());
        }
        
        
        // query preparation
        $table = $this->getTableName('FSCHRIFT');
        
        $insertFieldsArr['NUMMER']      = $this->getNextId($table);
        $insertFieldsArr['ALTNUMMER']   = $tmpDataArr['gsaPredecessorDocUid'];
        $insertFieldsArr['SCHRIFTNR']   = $this->getNextNumber('VORGANG');
        $insertFieldsArr['ERFART']      = $tmpDataArr['gsaDocTypeNo'].$tmpDataArr['gsaDocTypeAbbr'];  // example string: '02AU' 
        $insertFieldsArr['USER']        = 'Shop'; #### TODO: multilang/CE??
        $insertFieldsArr['DATUM']       = date('Y-m-d', $orderObj->get_timestamp());
        $insertFieldsArr['AUFNR']       = $tmpDataArr['gsaDocTypeAbbr'].'-'.date('Ym', $orderObj->get_timestamp()).'/'.sprintf("%05s", $this->updateNextNumber($tmpDataArr['gsaDocTypeAbbr'], tx_ptgsasocket_gsaDbAccessor::WN_JAHR)); // example string: 'AU-200511/00005' 
            # TODO: Jahr-Zaehler temporaer hardcodiert, umstellen auf von wz noch zu erstellende Methode getNextTransactionDocumentNumber(), die kompl. Vorgandsnummer je nach GSA Einstellungen zurueckliefert
        $insertFieldsArr['ALTAUFNR']    = $tmpDataArr['gsaPredecessorDocNo'];
        $insertFieldsArr['ADRINR']      = $feCustObj->get_gsaMasterAddressId();
        $insertFieldsArr['LIEFERINR']   = $tmpDataArr['deliveryAddressUid'];
        $insertFieldsArr['ENDPRB']      = $tmpDataArr['totalSumGross'];
        $insertFieldsArr['ENDPRN']      = $tmpDataArr['totalSumNet'];
        $insertFieldsArr['PRBRUTTO']    = $feCustObj->getIsNationalGrossPriceCust();
        $insertFieldsArr['LTERMIN']     = date('d.m.Y', $orderObj->get_timestamp());
        $insertFieldsArr['TAGNETTO']    = $feCustObj->get_gsaCustomerObj()->get_gsa_tagnetto();
        $insertFieldsArr['VERSART']     = $tmpDataArr['dispatchCostTypeName']; ##### TODO: clarify net/gross?! 
        $insertFieldsArr['ZAHLART']     = $gsaZahlArt;
        $insertFieldsArr['PREISGR']     = $feCustObj->get_priceCategory();
        $insertFieldsArr['AUSLAND']     = $feCustObj->get_isForeign();
        $insertFieldsArr['EGAUSLAND']   = $feCustObj->get_isEuForeign();
        $insertFieldsArr['FLDN01']      = $tmpDataArr['dispatchCost']; ##### TODO: clarify net/gross?! Splitting to 4 dispatchCost components?
        $insertFieldsArr['BEMERKUNG']   = $tmpDataArr['comment'];
        $insertFieldsArr['EURO']        = 1;  // hardcoded here - this is probably a relict from german currency change DM-EURO, value may be retrieved from KUNDE.EURO or DEBITOR.EURO (?)
        $insertFieldsArr['VERTRETER']   = 'Online-Shop';  #### TODO: multilang/CE??
        $insertFieldsArr['KUNDGR']      = $feCustObj->get_gsaCustomerObj()->get_gsa_kundgr();
        $insertFieldsArr['EGIDENTNR']   = $feCustObj->get_vatId();
        $insertFieldsArr['LETZTERUSER'] = 'Online-Shop';  #### TODO: multilang/CE??
        $insertFieldsArr['LETZTERUSERDATE'] = date('Y-m-d');
        $insertFieldsArr['NAME']        = ($feCustObj->get_gsaCustomerObj()->get_company() == '' ? $feCustObj->get_gsaCustomerObj()->get_lastname() : 
                                                                                                   $feCustObj->get_gsaCustomerObj()->get_company());
        
        // prepare query
        foreach ($insertFieldsArr as $key=>$value) {
            if (is_null($value)) {
                unset($insertFieldsArr[$key]); // this is crucial since TYPO3's exec_INSERTquery() will quote all fields including NULL otherwise!!
            }
        }
        trace($insertFieldsArr, 0, '$insertFieldsArr'); 
        
        // if enabled, do charset conversion of all non-binary string data 
        if ($this->charsetConvEnabled == 1) {
            $insertFieldsArr = tx_pttools_div::iconvArray($insertFieldsArr, $this->siteCharset, $this->gsaCharset);
        }

        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_INSERTquery($table, $insertFieldsArr);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        
        trace($insertFieldsArr['NUMMER']); 
        return $insertFieldsArr['NUMMER'];
        
    }
     
    /**
     * Inserts a new online order related document position record into the GSA DB-table 'FPOS' and returns the inserted record's GSA number (FPOS.NUMMER)
     * 
     * Note: The GSA database field name `MATCH` is a reserved (My)SQL word, so it has to be used with backticks or <tablename>.MATCH !
     * 
     * @param   integer     UID of the parent document (FSCHRIFT.NUMMER)
     * @param   integer     position number within the current document (starting at 1, consecutive)
     * @param   mixed       NULL for positions of order confirmations ("Auftragsbestaetigung"), or object of type tx_ptgsashop_delivery for positions of delivery notes ("Lieferschein")
     * @param   integer     UID of the related predecessing document ("Vorgaengerdokument"): -1 for positions of order confirmations ("Auftragsbestaetigung"), or the UID of the related predecessing order confirmation for positions of delivery notes ("Lieferschein")
     * @param   tx_ptgsauserreg_feCustomer      object of type tx_ptgsauserreg_feCustomer containing the required customer data
     * @param   tx_ptgsashop_article            object of type tx_ptgsashop_article containing the article data to insert 
     * @param   integer     position number within the predecessing document (use -1 if there is no predecessing document)
     * @return  integer     position number of the current position within it's predecessing document
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-11-28 (original code from tx_ptgsashop_orderAccessor::insertErpOnlineOrderRelatedDocPosition())
     */
    public function insertShopOrderTransactionDocPosition($parentDocId, $posCounter, $deliveryObj, $predecessorDocUid, 
                                                          tx_ptgsauserreg_feCustomer $feCustObj, tx_ptgsashop_article $articleObj, $predecessorDocPosNo) {
        
        $insertFieldsArr = array();
        $tmpDataArr = array();
        
        // retrieve required additional GSA database data
        $tmpDataArr['gsaArticleDataArr'] = tx_ptgsashop_articleAccessor::getInstance()->selectCompleteArticleData($articleObj->get_id());
            #### TODO: this is a temporary solution - wenn Bedeutung/Funktion der einz. GSA-Artikel-Eigenschaften geklärt ist und diese korrekt verwendet werden, diese hier nicht mehr aus DB lesen, sondern aus Artikel-Properties!
        
        
        // case differentiation for different parent document types
        if ($predecessorDocUid == -1) {
            // parent document is order confirmation ("Auftragsbestaetigung")
            $tmpDataArr['gsaPredecessorDocUid'] = 0;
            $tmpDataArr['gsaSuccessorPosNo'] = NULL; # TODO: clarify appropriate value
            $tmpDataArr['gsaPredecessorDocType'] = NULL;
            $tmpDataArr['posQuantity'] = $articleObj->get_quantity(); // use quantity of complete order
            
        
        } else {
            // parent document is delivery note ("Lieferschein")
            $tmpDataArr['gsaPredecessorDocUid'] = $predecessorDocUid;
            $tmpDataArr['gsaPredecessorPosNo'] = $predecessorDocPosNo;
            $tmpDataArr['gsaSuccessorPosNo'] = $predecessorDocPosNo; # TODO: clarify appropriate value
            $tmpDataArr['gsaPredecessorDocType'] = '02AU'; // GSA document type for order confirmation ("Auftragsbestaetigung")
            $tmpDataArr['posQuantity'] = $deliveryObj->get_articleCollObj()->getItem($articleObj->get_id())->get_quantity(); // use quantity of given delivery
        }
        
        
        // query preparation
        $table = $this->getTableName('FPOS');
        
        $insertFieldsArr['AUFINR']      = $parentDocId;
        $insertFieldsArr['POSINR']      = $posCounter;
        $insertFieldsArr['NUMMER']      = $this->getNextNumber('POSZAEHLER');
        $insertFieldsArr['ALTAUFINR']   = $tmpDataArr['gsaPredecessorDocUid'];
        $insertFieldsArr['ALTNUMMER']   = $tmpDataArr['gsaPredecessorPosNo'];
        $insertFieldsArr['FORTNUMMER']  = $tmpDataArr['gsaSuccessorPosNo']; # TODO: clarify appropriate value
        $insertFieldsArr['POSART']      = 'PO'; # TODO: to clarify for dispatch cost (fuer Versandkosten klaeren)
        $insertFieldsArr['POSNR']       = '#'.sprintf("%03s", $insertFieldsArr['POSINR']);
        $insertFieldsArr['ADRINR']      = $feCustObj->get_gsaMasterAddressId();
        $insertFieldsArr['ARTNR']       = $articleObj->get_artNo();
        $insertFieldsArr['ARTINR']      = $articleObj->get_id();
        $insertFieldsArr['BESTNR']      = $tmpDataArr['gsaArticleDataArr']['BESTNR'];
        $insertFieldsArr[$table.'.MATCH']  = $articleObj->get_match1(); // Note: The GSA database field name `MATCH` is a reserved (My)SQL word, so it has to be used with backticks or <tablename>.MATCH !
        $insertFieldsArr['WG']          = $tmpDataArr['gsaArticleDataArr']['WG'];
        $insertFieldsArr['EINHEIT']     = $tmpDataArr['gsaArticleDataArr']['EINHEIT'];
        $insertFieldsArr['GEWICHT']     = floatval($tmpDataArr['gsaArticleDataArr']['GEWICHT']);
        $insertFieldsArr['MENGE']       = $tmpDataArr['posQuantity'];
        $insertFieldsArr['MENGE2']      = $tmpDataArr['gsaArticleDataArr']['MENGE2'];
        $insertFieldsArr['MENGE3']      = $tmpDataArr['gsaArticleDataArr']['MENGE3'];
        $insertFieldsArr['MENGE4']      = $tmpDataArr['gsaArticleDataArr']['MENGE4'];
        $insertFieldsArr['ZU1']         = $tmpDataArr['gsaArticleDataArr']['ZU1'];
        $insertFieldsArr['ZU2']         = $tmpDataArr['gsaArticleDataArr']['ZU2'];
        $insertFieldsArr['EKPREIS']     = $tmpDataArr['gsaArticleDataArr']['EKPR01'];
        $insertFieldsArr['EP']          = $articleObj->getDisplayPrice(!$feCustObj->getIsNationalGrossPriceCust()); // GROSS retail prices for NationalGrossPriceCustomers, NET for all others
        $insertFieldsArr['UREP']        = $articleObj->getDisplayPrice(!$feCustObj->getIsNationalGrossPriceCust());  # TODO: clarify appropriate value  // NOTE: do not use delivery->get_orderBaseIsNet() here, since deliveryObj is  NULL for case "Auftragsbestaetigung"
        $insertFieldsArr['GP']          = $insertFieldsArr['EP'] * $insertFieldsArr['MENGE'];
        $insertFieldsArr['USTSATZ']     = $articleObj->get_taxCodeInland();
        $insertFieldsArr['PEINHEIT']    = $tmpDataArr['gsaArticleDataArr']['PEINHEIT'];
        $insertFieldsArr['LEINHEIT']    = $tmpDataArr['gsaArticleDataArr']['LEINHEIT'];
        $insertFieldsArr['LAGART']      = $tmpDataArr['gsaArticleDataArr']['LAGART'];
        $insertFieldsArr['VEINHEIT']    = $tmpDataArr['gsaArticleDataArr']['VEINHEIT'];
        $insertFieldsArr['LAGER']       = $tmpDataArr['gsaArticleDataArr']['LAGER'];
        $insertFieldsArr['FIXKOST1']    = floatval($tmpDataArr['gsaArticleDataArr']['FIXKOST1']);
        $insertFieldsArr['FIXKOST2']    = floatval($tmpDataArr['gsaArticleDataArr']['FIXKOST2']);
        $insertFieldsArr['FIXPROZ1']    = $tmpDataArr['gsaArticleDataArr']['FIXPROZ1'];
        $insertFieldsArr['FIXPROZ2']    = $tmpDataArr['gsaArticleDataArr']['FIXPROZ2'];
        $insertFieldsArr['USTEG']       = $tmpDataArr['gsaArticleDataArr']['USTEG'];
        $insertFieldsArr['USTAUSLAND']  = $articleObj->get_taxCodeAbroad();
        $insertFieldsArr['ZUSTEXT1']    = $articleObj->get_defText();
        $insertFieldsArr['ZUSTEXT2']    = $articleObj->get_altText();
        $insertFieldsArr['RABATTGR']    = $tmpDataArr['gsaArticleDataArr']['RABATTGR'];
        $insertFieldsArr['SNRART']      = $tmpDataArr['gsaArticleDataArr']['SNRART'];
        $insertFieldsArr['ALTTEIL']     = $tmpDataArr['gsaArticleDataArr']['ALTTEIL'];
        $insertFieldsArr['PROVART']     = $tmpDataArr['gsaArticleDataArr']['PROVART'];
        $insertFieldsArr['PROVGRUPPE']  = intval($tmpDataArr['gsaArticleDataArr']['PROVGRUPPE']);
        $insertFieldsArr['FORMELINR']   = intval($tmpDataArr['gsaArticleDataArr']['FORMELINR']);
        $insertFieldsArr['DPOSTEN']     = intval($tmpDataArr['gsaArticleDataArr']['DPOSTEN']);
        #$insertFieldsArr['EKONTOEG']    = '4125'; // TODO: this value is found in ERP-GUI at "Erloeskonten fuer DATEV-Schnittstelle" => this is stored on GSA's server directory in file "Datei.ini", entry "EGKONTO0". If needed once in the future, is has to be checked if this is written for all position records or only for EG abroad customers!
        $insertFieldsArr['GESPEICHERT'] = 1; // do not change to 0 since tgis will lead in a errors continuing to credit notes ("GUTRSCHRIFT") in ERP GUI!!
        $insertFieldsArr['ALTERFART']   = $tmpDataArr['gsaPredecessorDocType'];
        $insertFieldsArr['GRUNDPREISFAKTOR'] = floatval($tmpDataArr['gsaArticleDataArr']['GRUNDPREISFAKTOR']); 
        $insertFieldsArr['INSZAEHLER']       = ($posCounter * 10000); ### ??? TODO: find out where this comes from and what it is needed for....
        $insertFieldsArr['ABOSTUECKLISTE']   = intval($tmpDataArr['gsaArticleDataArr']['ABOSTUECKLISTE']);
        $insertFieldsArr['MONTAGEART']       = intval($tmpDataArr['gsaArticleDataArr']['MONTAGEART']);
        
         // prepare query
        foreach ($insertFieldsArr as $key=>$value) {
            if (is_null($value)) {
                unset($insertFieldsArr[$key]); // this is crucial since TYPO3's exec_INSERTquery() will quote all fields including NULL otherwise!!
            }
        }
        trace($insertFieldsArr, 0, '$insertFieldsArr'); 
        
        // if enabled, do charset conversion of all non-binary string data 
        if ($this->charsetConvEnabled == 1) {
            $insertFieldsArr = tx_pttools_div::iconvArray($insertFieldsArr, $this->siteCharset, $this->gsaCharset);
        }
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_INSERTquery($table, $insertFieldsArr);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        
        trace($insertFieldsArr['NUMMER']); 
        return $insertFieldsArr['NUMMER'];
        
    }
     
    /**
     * Inserts a data carrier exchange (ERP: "DTA/Datenträgeraustausch") record into the GSA DB table 'px_DTABUCH' and returns the inserted record's UID.
     * 
     * This method requires the database structure of paradox file 'DTABUCH.DB' to be imported into GSA MySQL-DB as 'px_DTABUCH'. This may be done e.g. by importing the MySQL dump pt_gsasocket/res/sql/px_DTABUCH.sql.
     * 
     * @param   string      the GSA ERP transaction document number of the invoice (ERP: "Rechnung/RE")
     * @param   double      booking sum of the transaction 
     * @param   string      booking date of the transaction in format YYYY-MM-DD (PHP: date('Y-m-d'))
     * @param   tx_ptgsauserreg_customer      object of type tx_ptgsauserreg_customer containing the required customer data
     * @param   string      (optional) additional string to insert in the "purpose" field of the data carrier exchange (max. 27 chars)
     * @return  integer     UID of the inserted record ('px_DTABUCH.NUMMER')
     * @throws  tx_pttools_exception   if the target table does not exist
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-21
     */
    public function insertDtaRecord($invoiceErpDocNo, $bookingSum, $bookingDate, 
                                    tx_ptgsauserreg_customer $customerObj, $additionalPurposeString='') {
        
        $table = $this->getTableName('px_DTABUCH');
        $insertFieldsArr = array();
        
        // check existance of target table
        if (tx_pttools_div::dbTableExists($table, $this->gsaDbObj) == false) {
            throw new tx_pttools_exception('Required database table does not exist.', 1, 
                                           'Database table '.$table.' does not exist in the GSA database - it has to be inserted manually by using pt_gsasocket/res/sql/'.$table.'.sql');
        }
        
        $bookingDateTs = mktime(0, 0, 0, substr($bookingDate, 5, 2), substr($bookingDate, 8, 2), substr($bookingDate, 0, 4));
        
        // query preparation
        $insertFieldsArr['NAME']    = substr($customerObj->get_bankAccountHolder(), 0, 27); // varchar(27)
        $insertFieldsArr['NAME2']   = substr($customerObj->get_bankAccountHolder(), 27, 27); // varchar(27)
        $insertFieldsArr['BLZ']     = $customerObj->get_bankCode(); // varchar(8)       # not set for non-German customers (wz)
        $insertFieldsArr['KONTO']   = $customerObj->get_bankAccount(); // varchar(10) # not set for non-German customers (wz)
        $insertFieldsArr['TYP']     = 'Einzug'; // varchar(20) ### TODO: find out where this comes from....
        $insertFieldsArr['BETRAG']  = (double)$bookingSum; // decimal(12,2)
        $insertFieldsArr['ZWECK']   = $invoiceErpDocNo; // varchar(27)
        $insertFieldsArr['ZWECK2']  = substr($additionalPurposeString, 0, 27); // varchar(27)
        $insertFieldsArr['PROGRAMM']= 'AUFW'; // varchar(10)  ### TODO: find out where this comes from and what it is needed for....
        $insertFieldsArr['DATUM']   = $bookingDate; // date
        $insertFieldsArr['FAELLIG'] = date('Y-m-d', $bookingDateTs + ($customerObj->get_gsa_tagnetto() * 24 * 60 * 60)); // date
        #$insertFieldsArr['BUCHDAT'] = NULL; // date ### TODO: ??? do not uncomment until used with a value since NULL will result in an '0000-00-00' entry for the date field type
        #$insertFieldsArr['DISKID']  = NULL; // date ### TODO: ???
        #$insertFieldsArr['MEHRZWECK'] = NULL; // blob  ###TODO: ???
        $insertFieldsArr['EURO']    = 1; // int(1) # hardcoded here - this is probably a relict from german currency change DM-EURO, value may be retrieved from KUNDE.EURO or DEBITOR.EURO (?)
        
        // if enabled, do charset conversion of all non-binary string data 
        if ($this->charsetConvEnabled == 1) {
            $insertFieldsArr = tx_pttools_div::iconvArray($insertFieldsArr, $this->siteCharset, $this->gsaCharset);
        }
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_INSERTquery($table, $insertFieldsArr);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        $lastInsertedId = $this->gsaDbObj->sql_insert_id();
        
        trace($lastInsertedId); 
        return $lastInsertedId;
        
    }
    
    /**
     * Returns the document number (ERP: "Vorgangsnummer") of a FSCHRIFT record specified by NUMMER (=uid) from the GSA database.
     *
     * @param   integer     UID of the FSCHRIFT record (FSCHRIFT.NUMMER)
     * @return  string      the document number (ERP: "Vorgangsnummer") of the specified FSCHRIFT record
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-04 (original code from tx_ptgsashop_orderAccessor::selectErpDocumentNumber())
     */
    public function selectTransactionDocumentNumber($fschriftId) {
        
        // query preparation
        $select  = 'AUFNR';
        $from    = $this->getTableName('FSCHRIFT');
        $where   = 'NUMMER = '.intval($fschriftId);
        $groupBy = '';
        $orderBy = '';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        $a_row = $this->gsaDbObj->sql_fetch_assoc($res);
        $this->gsaDbObj->sql_free_result($res);
        
        // if enabled, do charset conversion of all non-binary string data 
        if ($this->charsetConvEnabled == 1) {
            $a_row = tx_pttools_div::iconvArray($a_row, $this->gsaCharset, $this->siteCharset);
        }
        
        trace($a_row['AUFNR']); 
        return $a_row['AUFNR'];
        
    }
    
    /**
     * Returns the record uid ("NUMMER") of a FSCHRIFT record specified by document number (ERP: "Vorgangsnummer") from the GSA database.
     *
     * @param   string      the document number (ERP: "Vorgangsnummer") of the document (FSCHRIFT.AUFNR)
     * @return  integer     UID of the specified FSCHRIFT record (FSCHRIFT.NUMMER)
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @throws	tx_pttools_exceptionAssert	if more than one records found
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-26
     */
    public function selectTransactionDocumentUid($erpDocNo) {
        
        // query preparation
        $select  = 'NUMMER';
        $from    = $this->getTableName('FSCHRIFT');
        $where   = 'AUFNR = '.$this->gsaDbObj->fullQuoteStr($erpDocNo, $from);
        $groupBy = '';
        $orderBy = '';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        $a_row = $this->gsaDbObj->sql_fetch_assoc($res);
        tx_pttools_assert::isFalse($this->gsaDbObj->sql_fetch_assoc($res), array('message' => sprintf('Found more than one records for AUFNR "%s"', $erpDocNo)));
        $this->gsaDbObj->sql_free_result($res);
        
        // if enabled, do charset conversion of all non-binary string data 
        if ($this->charsetConvEnabled == 1) {
            $a_row = tx_pttools_div::iconvArray($a_row, $this->gsaCharset, $this->siteCharset);
        }
        
        trace($a_row['NUMMER']); 
        return $a_row['NUMMER'];
        
    }
    
    /**
     * Returns all data of a FSCHRIFT transaction document record specified by AUFNR (=erpDocNo, ERP: "Vorgangsnummer") from the GSA database.
     *
     * @param   string      the document number (ERP: "Vorgangsnummer") of the document (FSCHRIFT.AUFNR)
     * @return  array       complete data of the specified FSCHRIFT transaction document record
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @throws	tx_pttools_exceptionAssert	if more than one records found
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-19
     */
    public function selectTransactionDocumentData($erpDocNo) {
        
        // query preparation
        $select  = '*';
        $from    = $this->getTableName('FSCHRIFT');
        $where   = 'AUFNR = '.$this->gsaDbObj->fullQuoteStr($erpDocNo, $from);
        $groupBy = '';
        $orderBy = '';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        $a_row = $this->gsaDbObj->sql_fetch_assoc($res);
        tx_pttools_assert::isFalse($this->gsaDbObj->sql_fetch_assoc($res), array('message' => sprintf('Found more than one records for AUFNR "%s"', $erpDocNo)));
        $this->gsaDbObj->sql_free_result($res);
        
        // if enabled, do charset conversion of all non-binary string data 
        if ($this->charsetConvEnabled == 1) {
            $a_row = tx_pttools_div::iconvArray($a_row, $this->gsaCharset, $this->siteCharset);
        }
        
        trace($a_row); 
        return $a_row;
        
    }
    
    /**
     * Returns all FPOS (=positions) records of a specified AUFINR (=uid) of the related FSCHRIFT (=transaction/document) record from the GSA database.
     *
     * @param   integer     AUFINR (=uid) of the related FSCHRIFT (=transaction/document) record (FSCHRIFT.NUMMER)
     * @return  array       twodimensional array with records of all related FPOS position records
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-19
     */
    public function selectTransactionDocPositions($transactionDocId) {
        
        // query preparation
        $select  = '*';
        $from    = $this->getTableName('FPOS');
        $where   = 'AUFINR = '.intval($transactionDocId);
        $groupBy = '';
        $orderBy = 'POSINR';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false || $this->gsaDbObj->sql_num_rows($res) == 0) {
            throw new tx_pttools_exception('Query failed or returned empty result', 1, $this->gsaDbObj->sql_error());
        } 
            
        // store all data in twodimensional array
        $a_result = array();
        while (($a_row = $this->gsaDbObj->sql_fetch_assoc($res)) !== false) {
            // if enabled, do charset conversion of all non-binary string data 
            if ($this->charsetConvEnabled == 1) {
                $a_row = tx_pttools_div::iconvArray($a_row, $this->gsaCharset, $this->siteCharset);
            }
            $a_result[] = $a_row;
        }
        $this->gsaDbObj->sql_free_result($res);
        
        trace($a_result);
        return $a_result;
        
    }  
     
    /**
     * Inserts a transaction document record into the GSA DB-table 'FSCHRIFT' and returns the inserted record's UID - this requires all data to be inserted passed in an array with array keys exactly like the GSA FSCHRIFT database table field names (except 'NUMMER', 'OPNUMMER', 'AUFNR')
     * 
     * @param   array       all data to be inserted, prepared in an array with array keys exactly like the GSA FSCHRIFT database table field names (except 'NUMMER', 'OPNUMMER' and 'AUFNR' which will be created within this method)
     * @param   string      GSA transaction document type abbreviation (e.g. 'RE' for "Rechnung" (=invoice), 'AU' for "Auftragsbestätigung" (=order confirmation), ...)
     * @return  integer     UID of the inserted record ('FSCHRIFT.NUMMER') 
     * @throws  tx_pttools_exception   if the first param containing the data to insert is not a valid array
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-19
     */
    public function insertTransactionDocument($fschriftFieldsArr, $gsaDocTypeAbbr) {
        
        $insertFieldsArr = array();
        $extConfigArr = tx_ptgsashop_lib::getGsaShopConfig();
        
        #trace($fschriftFieldsArr, 0, '$fschriftFieldsArr'); 
        if (!is_array($fschriftFieldsArr) || empty($fschriftFieldsArr)) {
            throw new tx_pttools_exception('Wrong transaction document data format', 3, 'Data to insert in FSCHRIFT is not an array or is an empty array');
        }
        
        // query preparation
        $table = $this->getTableName('FSCHRIFT');
        foreach ($fschriftFieldsArr as $key=>$value) {
            if (!is_null($value)) {
                $insertFieldsArr[$table.'.'.$key] = $value; // prefix insert field names with table name to prevent SQL errors with GSA DB fields name like SQL reserved words (e.g. 'MATCH')
            } else {
                unset($insertFieldsArr[$table.'.'.$key]); // this is crucial since TYPO3's exec_INSERTquery() will quote all fields including NULL otherwise!!
            }
        }
        
        // if enabled, do charset conversion of all non-binary string data 
        if ($this->charsetConvEnabled == 1) {
            $insertFieldsArr = tx_pttools_div::iconvArray($insertFieldsArr, $this->siteCharset, $this->gsaCharset);
        }
        
        // get unique identifiers (overwrite possibly existing array keys)
        $insertFieldsArr[$table.'.NUMMER']     = $this->getNextId($table); // database ID of the record
        # $insertFieldsArr[$table.'.SCHRIFTNR']  = $this->getNextNumber('VORGANG'); // DO NOT UNCOMMENT (see todo note below)! // continued transaction document number ("fortlaufende Vorgangsnummer")
            ### TODO: check out the ERP "magic of DB field 'SCHRIFTNR' - should be increased IMHO, but ERP GUI does _not_ increase it when continuing an oder confirmation to an invoice (rather it copies the o.c.'s SCHRIFTNR to the invoice's SCHRIFTNR)!
       $insertFieldsArr[$table.'.OPNUMMER']   = $this->getNextId($extConfigArr['gsaVirtualTableOpNr'], $extConfigArr['gsaVirtualOpNrMin']); // outstanding items numbers of invoices (ERP: "Offene Posten")
            ### TODO: ERP GUI writes a different number based on file OPNR.INI (not multi-user safe!)
        $insertFieldsArr[$table.'.AUFNR']      = $gsaDocTypeAbbr.'-'.date('Ym').'/'.sprintf("%05s", $this->updateNextNumber($gsaDocTypeAbbr, tx_ptgsasocket_gsaDbAccessor::WN_JAHR)); // transaction document number (ERP: "Vorgangsnummer"), example string: 'RE-200706/00005' 
            
            ### TODO: Jahr-Zaehler temporaer hardcodiert, umstellen auf von wz noch zu erstellende Methode getNextTransactionDocumentNumber(), die kompl. Vorgandsnummer je nach GSA Einstellungen zurueckliefert
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_INSERTquery($table, $insertFieldsArr);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        
        trace($insertFieldsArr[$table.'.NUMMER']); 
        return $insertFieldsArr[$table.'.NUMMER'];
        
    }
     
    /**
     * Inserts a transaction document record into the GSA DB-table 'FPOS' and returns the inserted record's UID - this requires all data to be inserted passed in an array with array keys exactly like the GSA FPOS database table field names (except 'NUMMER')
     * 
     * @param   array       all data to be inserted, prepared in an array with array keys exactly like the GSA FPOS database table field names (except 'NUMMER' which will be created within this method)
     * @return  integer     UID of the inserted record ('FPOS.NUMMER') 
     * @throws  tx_pttools_exception   if the first param containing the data to insert is not a valid array
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-19
     */
    public function insertTransactionDocPosition($fposFieldsArr) {
        
        $insertFieldsArr = array();
        
        #trace($fposFieldsArr, 0, '$fposFieldsArr'); 
        if (!is_array($fposFieldsArr) || empty($fposFieldsArr)) {
            throw new tx_pttools_exception('Wrong transaction document position data format', 3, 'Data to insert in FPOS is not an array or is an empty array');
        }
        
        // query preparation
        $table = $this->getTableName('FPOS');
        foreach ($fposFieldsArr as $key=>$value) {
            if (!is_null($value)) {
                $insertFieldsArr[$table.'.'.$key] = $value; // prefix insert field names with table name to prevent SQL errors with GSA DB fields name like SQL reserved words (e.g. 'MATCH')
            } else {
                unset($insertFieldsArr[$table.'.'.$key]); // this is crucial since TYPO3's exec_INSERTquery() will quote all fields including NULL otherwise!!
            }
        }
                
        // get unique identifiers (overwrite possibly existing array keys)
        $insertFieldsArr[$table.'.NUMMER'] = $this->getNextNumber('POSZAEHLER'); // database ID of the record
        
        // if enabled, do charset conversion of all non-binary string data 
        if ($this->charsetConvEnabled == 1) {
            $insertFieldsArr = tx_pttools_div::iconvArray($insertFieldsArr, $this->siteCharset, $this->gsaCharset);
        }

        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_INSERTquery($table, $insertFieldsArr);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        
        trace($insertFieldsArr[$table.'.NUMMER']); 
        return $insertFieldsArr[$table.'.NUMMER'];
        
    }
     
    /**
     * Updates a set of given database fields in the GSA database table FSCHRIFT for a specified transaction document
     *
     * @param   string      the document number (ERP: "Vorgangsnummer") of the document to update (FSCHRIFT.AUFNR)
     * @param   array       all data to be updated, prepared in an array with array keys exactly like the GSA FSCHRIFT database table field names
     * @return  boolean     TRUE on success or FALSE on error
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-20 
     */
    public function updateTransactionDocument($erpDocNo, $fschriftUpdateFieldsArr) {
        
        $updateFieldsArr = array();
        
        // query preparation
        $table   = $this->getTableName('FSCHRIFT');
        $where   = 'AUFNR = '.$this->gsaDbObj->fullQuoteStr($erpDocNo, $table);
        foreach ($fschriftUpdateFieldsArr as $key=>$value) {
            $updateFieldsArr[$table.'.'.$key] = $value; // prefix update field names with table name to prevent SQL errors with GSA DB fields name like SQL reserved words (e.g. 'MATCH')
        }
        
        // if enabled, do charset conversion of all non-binary string data 
        if ($this->charsetConvEnabled == 1) {
            $updateFieldsArr = tx_pttools_div::iconvArray($updateFieldsArr, $this->siteCharset, $this->gsaCharset);
        }
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_UPDATEquery($table, $where, $updateFieldsArr);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        
        trace($res); 
        return $res;
        
    }
     
    /**
     * Updates a set of given database fields in the GSA database table FPOS for a specified transaction document position
     *
     * @param   integer     the record uid number of the position record to update (FPOS.NUMMER)
     * @param   array       all data to be updated, prepared in an array with array keys exactly like the GSA FPOS database table field names
     * @return  boolean     TRUE on success or FALSE on error
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-20 
     */
    public function updateTransactionDocPosition($fposNumber, $fposUpdateFieldsArr) {
        
        $updateFieldsArr = array();
        
        // query preparation
        $table   = $this->getTableName('FPOS');
        $where   = 'NUMMER = '.intval($fposNumber);
        foreach ($fposUpdateFieldsArr as $key=>$value) {
            $updateFieldsArr[$table.'.'.$key] = $value; // prefix update field names with table name to prevent SQL errors with GSA DB fields name like SQL reserved words (e.g. 'MATCH')
        }
        
        // if enabled, do charset conversion of all non-binary string data 
        if ($this->charsetConvEnabled == 1) {
            $updateFieldsArr = tx_pttools_div::iconvArray($updateFieldsArr, $this->siteCharset, $this->gsaCharset);
        }
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_UPDATEquery($table, $where, $updateFieldsArr);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        
        trace($res); 
        return $res;
        
    }
    
    /**
     * Returns the transaction document number (ERP: "Vorgangsnummer") for a possibly existing invoice continued (ERP: "fortgefuehrt") from a given predecessor transaction document number (ERP: "Vorgaengervorgangsnummer")
     *
     * @param   string      the predecessor transaction document number (FSCHRIFT.AUFNR, ERP: "Vorgaengervorgangsnummer") to check for its invoice
     * @return  mixed       (string) the transaction document number for the (last) invoice continued from the given predecessor document OR (boolean) FALSE if no invoice has been found
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @throws	tx_pttools_exceptionAssert	if there are more than one invoices for a predecessor document number 
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-07-02
     */
    public function selectInvoiceDocNumberForPredecessorDocNumber($predecessorErpDocNo) {
        
        // query preparation
        $select  = 'AUFNR';
        $from    = $this->getTableName('FSCHRIFT');
        $where   = 'ERFART = "04RE" '.
                   'AND ALTAUFNR = '.$this->gsaDbObj->fullQuoteStr($predecessorErpDocNo, $from);
        $groupBy = '';
        $orderBy = 'NUMMER DESC';
        $limit   = ''; 
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        $a_row = $this->gsaDbObj->sql_fetch_assoc($res);
        tx_pttools_assert::isFalse($this->gsaDbObj->sql_fetch_assoc($res), array('message' => sprintf('Found more than one invoices for predecessor document number "%s"', $predecessorErpDocNo)));
        $this->gsaDbObj->sql_free_result($res);
        
        // if enabled, do charset conversion of all non-binary string data 
        if ($this->charsetConvEnabled == 1) {
            $a_row = tx_pttools_div::iconvArray($a_row, $this->gsaCharset, $this->siteCharset);
        }
        
        $return = (isset($a_row['AUFNR']) ? $a_row['AUFNR'] : false);
        trace($return); 
        return $return;
        
    }
    
    /**
     * Returns the predecessor transaction document number (ERP: "Vorgaengervorgangsnummer") for a possibly existing predecessor document of a invoice transaction document number (ERP: "Vorgangsnummer")
     *
     * @param   string      the invoice transaction document number (FSCHRIFT.AUFNR, ERP: "Vorgangsnummer") of the invoice to to check
     * @return  mixed       (string) the predecessor transaction document number of the specified invoice OR (boolean) FALSE if no match has been found
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-07-19
     */
    public function selectPredecessorDocNumberForInvoiceDocNumber($invoiceErpDocNo) {
        
        // query preparation
        $select  = 'ALTAUFNR';
        $from    = $this->getTableName('FSCHRIFT');
        $where   = 'ERFART = "04RE" '.
                   'AND AUFNR = '.$this->gsaDbObj->fullQuoteStr($invoiceErpDocNo, $from);
        $groupBy = '';
        $orderBy = 'NUMMER DESC';
        $limit   = '1';  // this is double bottom if there are multiple invoice records errornously 
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        $a_row = $this->gsaDbObj->sql_fetch_assoc($res);
        $this->gsaDbObj->sql_free_result($res);
        
        // if enabled, do charset conversion of all non-binary string data 
        if ($this->charsetConvEnabled == 1) {
            $a_row = tx_pttools_div::iconvArray($a_row, $this->gsaCharset, $this->siteCharset);
        }
        
        $return = (isset($a_row['ALTAUFNR']) ? $a_row['ALTAUFNR'] : false);
        trace($return); 
        return $return;
        
    }
    
    /**
     * Returns the purchase data of the first supplier found for an article from the GSA database table 'LIEFART' 
     * TODO: at the moment we're using only one supplier per article, so we get data of the first supplier found for the article only. This may be changed to a real n:m relation in a later version.
     * 
     * @param   integer     UID of the article from the GSA database (GSA database field 'LIEFART.IARTNR')
     * @return  mixed       (array) purchase data of the first supplier found for the specified article OR (boolean) false if no record found
     * @throws  tx_pttools_exception   if the query fails
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-19
     */
    public function selectArticleSupplierData($gsaArticleUid) {
     
        // query preparation
        $select  = 'ILIEFNR, BESTELLNUMMER, EANNUMMER, EKPREIS1, EKPREIS2, RAB1, RAB2, RAB3, PRBRUTTO';
        $from    = $this->getTableName('LIEFART');
        $where   = 'IARTNR = '.intval($gsaArticleUid).' ';
        $groupBy = '';
        $orderBy = 'ILIEFNR';   // TODO: may be changed in a later version.
        $limit   = '1';         // TODO: may be changed in a later version.
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        $a_row = $this->gsaDbObj->sql_fetch_assoc($res);
        $this->gsaDbObj->sql_free_result($res);
        
        // if enabled, do charset conversion of all non-binary string data 
        if ($this->charsetConvEnabled == 1) {
            $a_row = tx_pttools_div::iconvArray($a_row, $this->gsaCharset, $this->siteCharset);
        }
        
        trace($a_row); 
        return $a_row;
        
    }
     
    /**
     * Inserts a supplier control record for an article into the userdefined GSA DB table 'pt_gsashop_supplierControl' and returns the inserted record's UID.
     * 
     * This method requires the userdefined database table 'pt_gsashop_supplierControl' to be existent in the GSA MySQL-DB. This can be done by importing the MySQL dump pt_gsashop/doc/pt_gsashop_supplierControl.sql.
     * 
     * @param   string      the GSA ERP transaction document number of the related invoice (FSCHRIFT.AUFNR)
     * @param   string      booking date of the related invoice in format YYYY-MM-DD (PHP: date('Y-m-d'))
     * @param   integer     GSA database UID of the article (ARTIKEL.NUMMER)
     * @param   string      GSA article number of the article (ARTIKEL.ARTNR)
     * @param   integer     purchase quantity the article
     * @return  integer     UID of the inserted record
     * @throws  tx_pttools_exception   if the target table does not exist
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-07-05
     */
    public function insertSupplierControlRecord($invoiceDocNumber, $bookingDate, $gsaArticleUid, $gsaArticleNumber, $articleQty) {
        
        static $tableExists = false;  // remember result of check if already done (prevents multiple checks in a calling loop)
        $table = $this->getTableName('pt_gsashop_supplierControl');
        $insertFieldsArr = array();
        
        // check existance of target table
        if ($tableExists == false && tx_pttools_div::dbTableExists($table, $this->gsaDbObj) == false) {
            throw new tx_pttools_exception('Required database table does not exist.', 1, 
                                           'Database table '.$table.' does not exist in the GSA database - it has to be inserted manually by using pt_gsashop/doc/'.$table.'.sql');
        }
        $tableExists = true;
        
        // retrieve supplier data for the article from GSA DB table LIEFART
        $articleSupplierArr = $this->selectArticleSupplierData($gsaArticleUid);  
        
        // query preparation
        $insertFieldsArr['invoiceDocNumber']        = $invoiceDocNumber;  // points to FSCHRIFT.AUFNR
        $insertFieldsArr['bookingDate']             = $bookingDate;      
        $insertFieldsArr['articleUid']              = $gsaArticleUid;     // points to ARTIKEL.NUMMER
        $insertFieldsArr['articleNumber']           = $gsaArticleNumber;  // points to ARTIKEL.ARTNR
        $insertFieldsArr['articleQty']              = $articleQty;
        if (is_array($articleSupplierArr)) {
            $insertFieldsArr['supplierUid']             = $articleSupplierArr['ILIEFNR'];       // points to ADRESSE.NUMMER (=uid of the supplier's master address record in GSA)
            if (isset($articleSupplierArr['BESTELLNUMMER'])) {
                $insertFieldsArr['supplierArticleNumber']   = $articleSupplierArr['BESTELLNUMMER'];
            }
            if (isset($articleSupplierArr['EANNUMMER'])) {
                $insertFieldsArr['supplierEanNumber']       = $articleSupplierArr['EANNUMMER'];
            }
            if (isset($articleSupplierArr['EKPREIS1'])) {
                $insertFieldsArr['supplierUnitPrice1']      = $articleSupplierArr['EKPREIS1'];
            }
            if (isset($articleSupplierArr['EKPREIS2'])) {
                $insertFieldsArr['supplierUnitPrice2']      = $articleSupplierArr['EKPREIS2'];
            }
            if (isset($articleSupplierArr['EKPREIS3'])) {
                $insertFieldsArr['supplierUnitPrice3']      = $articleSupplierArr['EKPREIS3'];
            }
            if (isset($articleSupplierArr['RAB1'])) {
                $insertFieldsArr['supplierDiscount1']       = $articleSupplierArr['RAB1'];
            }
            if (isset($articleSupplierArr['RAB2'])) {
                $insertFieldsArr['supplierDiscount2']       = $articleSupplierArr['RAB2'];
            }
            if (isset($articleSupplierArr['RAB3'])) {
                $insertFieldsArr['supplierDiscount3']       = $articleSupplierArr['RAB3'];
            }
            if (isset($articleSupplierArr['PRBRUTTO'])) {
                $insertFieldsArr['isGrossPrice']            = $articleSupplierArr['PRBRUTTO'];
            }
        }
        
        // if enabled, do charset conversion of all non-binary string data 
        if ($this->charsetConvEnabled == 1) {
            $insertFieldsArr = tx_pttools_div::iconvArray($insertFieldsArr, $this->siteCharset, $this->gsaCharset);
        }
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_INSERTquery($table, $insertFieldsArr);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        $lastInsertedId = $this->gsaDbObj->sql_insert_id();
        
        trace($lastInsertedId); 
        return $lastInsertedId;
        
    }
    
    /**
     * Deletes a FSCHRIFT transaction document record specified by AUFNR (=erpDocNo, ERP: "Vorgangsnummer") from the GSA database.
     *
     * @param   string      the document number (ERP: "Vorgangsnummer") of the document (FSCHRIFT.AUFNR)
     * @return  void        
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-07-26
     */
    public function deleteTransactionDocument($erpDocNo) {
        
        // query preparation
        $table   = $this->getTableName('FSCHRIFT');
        $where   = 'AUFNR = '.$this->gsaDbObj->fullQuoteStr($erpDocNo, $table);
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_DELETEquery($table, $where);
        trace($res); 
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        
    }
    
    /**
     * Deletes all FPOS position records for a transaction document record specified by uid (FPOS.AUFINR/FSCHRIFT.NUMMER) from the GSA database.
     *
     * @param   integer     AUFINR (=uid) of the related FSCHRIFT (=transaction/document) record (FSCHRIFT.NUMMER)
     * @return  void        
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-07-26
     */
    public function deleteTransactionDocPositions($transactionDocId) {
        
        // query preparation
        $table   = $this->getTableName('FPOS');
        $where   = 'AUFINR = '.intval($transactionDocId);
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_DELETEquery($table, $where);
        trace($res); 
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        
    }
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_gsaTransactionAccessor.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_gsaTransactionAccessor.php']);
}

?>