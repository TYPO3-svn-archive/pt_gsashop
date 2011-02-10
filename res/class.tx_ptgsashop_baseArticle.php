<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2005-2007 Rainer Kuhn (kuhn@punkt.de)
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
 * Base article class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_baseArticle.php,v 1.78 2009/03/26 14:34:05 ry21 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2005-07-19 / 2007-01-04 as tx_ptgsashop_baseArticle: based on former tx_ptgsashop_article
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_articleAccessor.php';  // GSA Shop database accessor class for articles
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_scalePriceCollection.php';// GSA Shop article scale price collection class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_lib.php';  // GSA Shop library with static methods
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_articleImage.php';  // GSA Shop article image class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_articleImageAccessor.php';  // GSA Shop database accessor class for article images
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_articleImageCollection.php';  // GSA Shop article image collection class

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general helper library class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_finance.php'; // library class with finance related static methods



/**
 * Abstract base article class (based on GSA database structure)
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2005-07-19 / 2007-01-04 as tx_ptgsashop_baseArticle: based on former tx_ptgsashop_article
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
abstract class tx_ptgsashop_baseArticle {

    /**
     * Properties
     */
    protected $id;        // (integer) article ID / GSA: ARTIKEL.NUMMER
    
    protected $quantity;        // (integer) "physical" quantity of the article (e.g. in a cart or delivery)
    protected $priceCalcQty;    // (integer) "virtual" total purchase quantity of the article to be used for price calculation (e.g. in complete oder)
    protected $priceCategory;   // (integer) n=1-5: number of retail price category to use for the requested article (relates to GSA: VKPREIS.PR0n)
    protected $orderArchiveNetPrice = NULL; // (mixed: NULL or double) This is set as double for restored and non-updated articles from the order archive only, NULL otherwise
    
    /**
     * Order Archive UID after saving or restoring from order archive datatable
     * @var int
     */
    protected $orderArchiveUid = 0;
    
    protected $date;            // (string) date of the article request (date string format: YYYY-MM-DD)
    protected $imageFlag;       // (boolean) flag whether the article's image is needed
    
    protected $description;     // (string) article description for _frontend_ only / will be mapped from the GSA DB field configured in Constant Editor (taken from $this->classConfigArr)
    protected $isPhysical = 1;  // (boolean) flag whether the article is physical (deliverable articles, e.g. books) or not ("virtual", non-deliverable articles, e.g. webhosting products)
    protected $applSpecDataObj = NULL; // (mixed: NULL or object implementing interface tx_ptgsashop_iApplSpecArticleDataObj) application specific data object for the article
    
    // basic article data properties
    protected $artNo;     // (string) article number / GSA-DB: ARTIKEL.ARTNR
    protected $match1;    // (string) article match / GSA-DB: ARTIKEL.MATCH (ERP-GUI: "Suchbegriff")
    protected $match2;    // (string) article description / GSA-DB: ARTIKEL.MATCH2  (ERP-GUI: "Beschreibung")
    protected $defText;   // (string) default article text / GSA-DB: ARTIKEL.ZUSTEXT1 (ERP-GUI: "Artikeltext")
    protected $altText;   // (string) alternative article text / GSA-DB: ARTIKEL.ZUSTEXT2 (ERP-GUI: "Alternativtext")
    protected $grossPriceFlag = 0; // (boolean) flag whether article prices are gross prices / GSA-DB: ARTIKEL.PRBRUTTO
    protected $taxCodeInland;      // (string) tax code for inland purchases / GSA-DB: ARTIKEL.USTSATZ 
    protected $taxCodeAbroad;      // (string) tax code for abroad (non-EG) purchases / GSA-DB: ARTIKEL.USTAUSLAND 
    protected $fixedCost1;         // (double) _net_ fixed cost 1 for the article / GSA-DB: ARTIKEL.FIXKOST1
    protected $fixedCost2;         // (double) _net_ fixed cost 2 for the article / GSA-DB: ARTIKEL.FIXKOST2
    protected $isOnlineArticle;    // (boolean) flag whether the article is an online article (article could be selled using the online shop) / GSA-DB: ARTIKEL.ONLINEARTIKEL 
    protected $isPassive = 0;      // (boolean) flag whether the article is a passive article (currently available for order/display in shop) / GSA-DB: ARTIKEL.PASSIV
    protected $webAddress;   //  (string) TYPO3 page ID or TYPO3 page alias of the article's detail page / GSA-DB: ARTIKEL.WEBADRESSE (ERP-GUI: "Webadresse")
    protected $userField01;  //  (string) user field no. 01 / GSA-DB: ARTIKEL.FLD01  (ERP-GUI: "Freifeld 1")
    protected $userField02;  //  (string) user field no. 02 / GSA-DB: ARTIKEL.FLD02  (ERP-GUI: "Freifeld 2")
    protected $userField03;  //  (string) user field no. 03 / GSA-DB: ARTIKEL.FLD03  (ERP-GUI: "Freifeld 3")
    protected $userField04;  //  (string) user field no. 04 / GSA-DB: ARTIKEL.FLD04  (ERP-GUI: "Freifeld 4")
    protected $userField05;  //  (string) user field no. 05 / GSA-DB: ARTIKEL.FLD05  (ERP-GUI: "Freifeld 5")
    protected $userField06;  //  (string) user field no. 06 / GSA-DB: ARTIKEL.FLD06  (ERP-GUI: "Freifeld 6")
    protected $userField07;  //  (string) user field no. 07 / GSA-DB: ARTIKEL.FLD07  (ERP-GUI: "Freifeld 7")
    protected $userField08;  //  (string) user field no. 08 / GSA-DB: ARTIKEL.FLD08  (ERP-GUI: "Freifeld 8")
    
    // default pricing data properties (NOTE: originally existent properties $basicRetailPriceCategory* and $specialOffer* have moved to tx_ptgsashop_scalePrice on 2007-10-24)
    /**
     * @var tx_ptgsashop_scalePriceCollection 	scale price collection for the article / relates to records in GSA-DB: VKPREIS
     */
    protected $scalePriceCollectionObj; 
    
    // customer specific pricing data properties
    protected $customerId = 0;               // (integer) the main address data record UID of the customer to retrieve his customer specific price / relates to GSA database field "ADRESSE.NUMMER"
    protected $custSpecRetailPrice =-1;      // (double) customer specific retail price / GSA-DB: KUNPREIS.PREIS
    protected $custSpecGrossPriceFlag = 0;   // (double) customer specific price: flag whether the customer specific retail price is a gross price / GSA-DB: KUNPREIS.PRBRUTTO
    protected $custSpecSpecialOfferFlag = 0; // (double) customer specific price: flag whether the customer specific retail price is a limited special offer / GSA-DB: KUNPREIS.AKTION
    protected $custSpecSpecialOfferStartDate;// (string) customer specific price special offer start date (date string format: YYYY-MM-DD) / GSA-DB: KUNPREIS.DATUMVON 
    protected $custSpecSpecialOfferEndDate;  // (string) customer specific price special offer end date (date string format: YYYY-MM-DD) / GSA-DB: KUNPREIS.DATUMBIS
    
    // supplier data
    protected $suppliersArr = array(); // (array) array containing address record UIDs (GSA-DB: ADRESSE.NUMMER) of the articles' suppliers
    
    // article relation properties
    protected $artrelMaxAmount = 0;          // (integer) max. amount of the article in the cart (tx_ptgsashop_artrel.max_amount)
    protected $artrelExclusionArr = array(); // (array) array containing UIDs of articles that could not be bought with this article (CSL in tx_ptgsashop_artrel.exclusion_articles)
    protected $artrelRequiredArr = array();  // (array) array containing UIDs of articles that are required to buy this article (CSL in tx_ptgsashop_artrel.required_articles)
    protected $artrelRelatedArr = array();   // (array) array containing UIDs of "related" articles (CSL in tx_ptgsashop_artrel.related_articles)  ### TODO: [NOT IMPLEMENTED YET]
    protected $artrelBundledArr = array();   // (array) array containing UIDs of bundled articles(CSL in tx_ptgsashop_artrel.bundled_articles) ### TODO: [NOT IMPLEMENTED YET]
    protected $artrelApplSpecUid = 0;        // (integer) application specific uid to use by add-on applications for additional data (tx_ptgsashop_artrel.appl_spec_uid)
    protected $artrelApplIdentifier = '';    // (string) application identifier to specify the add-on application (tx_ptgsashop_artrel.appl_identifier)
    
    // article delivery properties
    protected $artdelStartTs = 0;          // (integer) timestamp of the start date of the article's delivery (optional)
    protected $artdelRuntime = 0;        // (integer) runtime of the article/article's delivery in seconds (optional)
    
    // "old" GSA database stored image handling (unfinished) -- DEPRECATED: use the $articleImageCollectionObj property instead
    #protected $image = '';     // (string) DEPRECATED: use the $articleImageCollectionObj property instead! -- picture of article as binary string / GSA-DB: ARTIKEL.BILD  // TODO: optional: image string-path??
    protected $imageWebWidth = 0;      // (integer) DEPRECATED: use the $articleImageCollectionObj property instead! -- width of the web formatted article image
    protected $imageWebHeight = 0;     // (integer) DEPRECATED: use the $articleImageCollectionObj property instead! -- height of the web formatted article image
    protected $imageWebFilePath = '';   // (string) DEPRECATED: use the $articleImageCollectionObj property instead! -- webserver access path of the web formatted article image
    
    // "new" TYPO3 database based multiple image handling
    /**
     * @var tx_ptgsashop_articleImageCollection    collection of article related images (stored in tx_ptgsashop_articleImage - not in GSA-DB!)
     */
    protected $articleImageCollectionObj;
    
    /**
     * @var string	ean number (barcode) GSA-DB: EANNUMMER (ERP-GUI: "EAN-Nummer")
     */
    protected $eanNumber;

    protected $classConfigArr = array(); // (array) array with _frontend_ configuration values used by this class (this is set once in the class constructor for frontend usage)
    
    
    /**
     * Class Constants
     */
    const EXT_KEY = 'pt_gsashop';                            // (string) the extension key
    const ARTICLESINGLEVIEW_CLASS_NAME = 'tx_ptgsashop_pi2'; // (string) class name of the article single view plugin to use combined with this plugin
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
     
    /**
     * Class constructor: sets the properties of an article using given params and internal (protected) setter methods
     *
     * @param   integer     UID of the article in the GSA database (positive integer); use 0 to create a new/empty article
     * @param   integer     (optional) price category to use for the article (ERP: "VK-Preis" 1-5), depending on the customer's legitimation
     * @param   integer     (optional) UID of the customer's main address data record in the GSA database (GSA database field "ADRESSE.NUMMER") - used for customer specific price calculation
     * @param   integer     (optional) purchase quantity of the article (Note: multiple pricing data records for different quantities!)
     * @param   string      (optional) optional date of the article request (date string format: YYYY-MM-DD) - if not set today's date will be used
     * @param   boolean     (optional) flag whether the article image(s) should be retrieved as well (optional, default=0)
     * @return  void         
     * @global  object      $GLOBALS['TSFE']->tmpl
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-19
     */
    public function __construct($id, $priceCategory=1, $customerId=0, $quantity=1, $date='', $imageFlag=0) {
        
        // $GLOBALS['TT']->push('', 'Constructing article');
        
        // get configuration values
        $this->classConfigArr = tx_ptgsashop_lib::getGsaShopConfig();
 
        // set properties from constructor parameters
        $this->id = (integer)$id;
        $this->priceCategory = (integer)$priceCategory;
        $this->customerId = (integer)$customerId;
        $this->quantity = (integer)$quantity;
        $this->priceCalcQty = (integer)$quantity;  // initial price calculation quantity is set to requested quantity
        $this->date = (string)($date=='' ? date('Y-m-d') : $date); // if no date set use today's date
        $this->imageFlag = (boolean)$imageFlag;
        $this->scalePriceCollectionObj = new tx_ptgsashop_scalePriceCollection();
        $this->articleImageCollectionObj = new tx_ptgsashop_articleImageCollection();
        
        // for non-new articles: retrieve article data, set properties from external data sources (database etc.)
        if ($id > 0) {
            $this->setArticleBasicData();
            $this->setDefaultRetailPricingData();
            $this->setCustomerSpecificPricingData();
            $this->setArticleSuppliersData();
            if ($this->imageFlag == 1) {
                // old images (from ERP) -- DEPRECATED: use the $articleImageCollectionObj property instead
                $this->setImageData();
                // new image (from TYPO3)
                $this->setArticleImagesCollection();
            }
            $this->setArticleRelationData();
        }
        
        // HOOK: allow multiple hooks to append their execution to the article constructor [NOTICE 01/07: do not rename 'article_hooks' to 'baseArticle_hooks' here since this hook may be used already!]
        // This hook may be used -amongst other things- to set the article's $isPhysical property (using set_isPhysical()) depending on addon-application-specific data (e.g. retrieved from get_artrelApplSpecUid())
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['article_hooks']['constructor_additionalActionHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['article_hooks']['constructor_additionalActionHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $hookObj->constructor_additionalActionHook($this);
            }
        }
        // $GLOBALS['TT']->pull();
    }
    
    /**
     * Load from order archive: restores the object's properties of data retrieved from the order archive database. This method should be called only directly after new instantiation of the (empty) object.
     * 
     * @param   integer     UID of the article record in the order archive database
     * @return  tx_ptgsashop_baseArticle      object of type tx_ptgsashop_baseArticle, "filled" with properties from orders database record
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-04
     */
    public function loadFromOrderArchive($uid) {
    	
        $orderAccessor = tx_ptgsashop_orderAccessor::getInstance();
        $articleDataArr = $orderAccessor->selectOrdersArticle($uid);
        $this->orderArchiveUid =(integer)$uid;
        $this->id =             (integer)$articleDataArr['gsa_id_artikel'];
        $this->quantity =       (integer)$articleDataArr['quantity'];
        $this->priceCalcQty =   (integer)$articleDataArr['price_calc_qty'];
        $this->priceCategory =  (integer)$articleDataArr['price_category'];
        $this->orderArchiveNetPrice =  (double)$articleDataArr['price_net'];
        $this->date =           (string)$articleDataArr['date_string'];
        $this->description =    (string)$articleDataArr['description'];
        $this->artNo =          (string)$articleDataArr['art_no'];
        $this->taxCodeInland =  (string)$articleDataArr['tax_code'];
        $this->fixedCost1 =     (double)$articleDataArr['fixedCost1'];
        $this->fixedCost2 =     (double)$articleDataArr['fixedCost2'];
        $this->userField01 =    (string)$articleDataArr['userField01'];
        $this->userField02 =    (string)$articleDataArr['userField02'];
        $this->userField03 =    (string)$articleDataArr['userField03'];
        $this->userField04 =    (string)$articleDataArr['userField04'];
        $this->userField05 =    (string)$articleDataArr['userField05'];
        $this->userField06 =    (string)$articleDataArr['userField06'];
        $this->userField07 =    (string)$articleDataArr['userField07'];
        $this->userField08 =    (string)$articleDataArr['userField08'];
        
        $this->artrelApplSpecUid = (integer)$articleDataArr['artrelApplSpecUid'];
        $this->artrelApplIdentifier = (string)$articleDataArr['artrelApplIdentifier'];
        
        // rebuild object from database 
        $applSpecDataClass =    (string)$articleDataArr['applSpecDataClass'];
        if (strlen($applSpecDataClass) > 0){
            // only rebuild if class_exists
        	if (class_exists($applSpecDataClass)){
                $tmp = new $applSpecDataClass(); 
                $tmp->setDataFromString($articleDataArr['applSpecData']);
                $this->set_applSpecDataObj($tmp);
            } else {
                // TODO: (ry44/ry42): implement notification for developer
            }
        }
        
        return $this;
        
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**
     * Sets the basic properties of an article using data retrieved from a GSA database query
     *
     * @param   void
     * @return  void
     * @throws  tx_pttools_exception   if no article data is found in the database for the current ID
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-21
     */
    protected function setArticleBasicData() { 
        
        $articleDataArr = tx_ptgsashop_articleAccessor::getInstance()->selectArticleData($this->id);
        if (!is_array($articleDataArr)) {
            throw new tx_pttools_exception('No valid article data found', 3,
                                           'tx_ptgsashop_articleAccessor::getInstance()->selectArticleData('.$this->id.') did not return any data.');
        }
                
        // frontend only: use field specified in  configuration for _frontend_ article description property
        if (TYPO3_MODE == 'FE' && is_object($GLOBALS['TSFE'])) { 
            $this->description = (string)$articleDataArr[$this->classConfigArr['articleDescriptionSourceField']];
        }
        
        if (!is_null($articleDataArr['ARTNR']))         $this->artNo = (string)$articleDataArr['ARTNR'];
        if (!is_null($articleDataArr['MATCH']))         $this->match1 = (string)$articleDataArr['MATCH'];
        if (!is_null($articleDataArr['MATCH2']))        $this->match2 = (string)$articleDataArr['MATCH2'];
        if (!is_null($articleDataArr['ZUSTEXT1']))      $this->defText = (string)$articleDataArr['ZUSTEXT1'];
        if (!is_null($articleDataArr['ZUSTEXT2']))      $this->altText = (string)$articleDataArr['ZUSTEXT2'];
        if (!is_null($articleDataArr['USTSATZ']))       $this->taxCodeInland = (string)$articleDataArr['USTSATZ'];
        if (!is_null($articleDataArr['USTAUSLAND']))    $this->taxCodeAbroad = (string)$articleDataArr['USTAUSLAND'];
        if (!is_null($articleDataArr['FIXKOST1']))      $this->fixedCost1 = (double)$articleDataArr['FIXKOST1'];
        if (!is_null($articleDataArr['FIXKOST2']))      $this->fixedCost2 = (double)$articleDataArr['FIXKOST2'];
        if (!is_null($articleDataArr['PRBRUTTO']))      $this->grossPriceFlag = (bool)$articleDataArr['PRBRUTTO'];
        if (!is_null($articleDataArr['ONLINEARTIKEL'])) $this->isOnlineArticle = (bool)$articleDataArr['ONLINEARTIKEL'];
        if (!is_null($articleDataArr['PASSIV']))        $this->isPassive = (bool)$articleDataArr['PASSIV'];
        if (!is_null($articleDataArr['WEBADRESSE']))    $this->webAddress = (string)$articleDataArr['WEBADRESSE'];
        if (!is_null($articleDataArr['FLD01']))         $this->userField01 = (string)$articleDataArr['FLD01'];
        if (!is_null($articleDataArr['FLD02']))         $this->userField02 = (string)$articleDataArr['FLD02'];
        if (!is_null($articleDataArr['FLD03']))         $this->userField03 = (string)$articleDataArr['FLD03'];
        if (!is_null($articleDataArr['FLD04']))         $this->userField04 = (string)$articleDataArr['FLD04'];
        if (!is_null($articleDataArr['FLD05']))         $this->userField05 = (string)$articleDataArr['FLD05'];
        if (!is_null($articleDataArr['FLD06']))         $this->userField06 = (string)$articleDataArr['FLD06'];
        if (!is_null($articleDataArr['FLD07']))         $this->userField07 = (string)$articleDataArr['FLD07'];
        if (!is_null($articleDataArr['FLD08']))         $this->userField08 = (string)$articleDataArr['FLD08'];
        if (!is_null($articleDataArr['EANNUMMER']))     $this->eanNumber = (string)$articleDataArr['EANNUMMER'];
        
    }
     
    /**
     * Sets the default pricing properties of an article in it's scalePriceCollection nusing data retrieved from a GSA database query
     * NOTE: The original code of this method has moved to tx_ptgsashop_scalePrice::setScalePriceData() on 2007-10-24
     *
     * @param   void
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-22 (complete change to scalePriceCollection on 2007-10-24)
     */
    protected function setDefaultRetailPricingData() {
        
        $this->scalePriceCollectionObj = new tx_ptgsashop_scalePriceCollection($this->id);
        
    }
     
    /**
     * Sets the customer specific pricing properties of an article using data retrieved from a GSA database query (if set for the article/customer combination)
     *
     * @param   void
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-01
     */
    protected function setCustomerSpecificPricingData() {
        
        $custSpecPriceDataArr = tx_ptgsashop_articleAccessor::getInstance()->selectCustomerSpecificPricingData($this->id, $this->customerId);
        
        // if the query returns record data, set appropriate article properties
        if (is_array($custSpecPriceDataArr)) {
            
            if (!is_null($custSpecPriceDataArr['PREIS']))     $this->custSpecRetailPrice = (double)$custSpecPriceDataArr['PREIS'];
            if (!is_null($custSpecPriceDataArr['PRBRUTTO']))  $this->custSpecGrossPriceFlag = (bool)$custSpecPriceDataArr['PRBRUTTO'];
            if (!is_null($custSpecPriceDataArr['AKTION']))    $this->custSpecSpecialOfferFlag = (bool)$custSpecPriceDataArr['AKTION'];
            if (!is_null($custSpecPriceDataArr['DATUMVON']))  $this->custSpecSpecialOfferStartDate = (string)$custSpecPriceDataArr['DATUMVON'];
            if (!is_null($custSpecPriceDataArr['DATUMBIS']))  $this->custSpecSpecialOfferEndDate = (string)$custSpecPriceDataArr['DATUMBIS'];
            
        // if the query returns no record data, (re-)set appropriate article properties to default values
        } else {
            
            $this->custSpecRetailPrice = (double)-1;
            $this->custSpecGrossPriceFlag = (bool)0;
            $this->custSpecSpecialOfferFlag = (bool)0;
            $this->custSpecSpecialOfferStartDate = (string)'';
            $this->custSpecSpecialOfferEndDate = (string)'';
            
        }
        
    }
     
    /**
     * Sets the suppliers data properties of an article using data retrieved from a GSA database query
     *
     * @param   void
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-22
     */
    protected function setArticleSuppliersData() {
        
        $this->suppliersArr = tx_ptgsashop_articleAccessor::getInstance()->selectArticleSuppliersUids($this->id);
        
    }
     
    /**
     * Sets the article relation properties of an article using data retrieved from a TYPO3 database query
     *
     * @param   void
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-03
     */
    protected function setArticleRelationData() {
        
        $artrelDataArr = tx_ptgsashop_articleAccessor::getInstance()->selectArticleRelationData($this->id);
        
        // try to assign properties only if an artzicle relation record for the article has been found
        if (is_array($artrelDataArr)) {
            
            $this->artrelMaxAmount = (integer)$artrelDataArr['max_amount'];
            $this->artrelApplSpecUid = (integer)$artrelDataArr['appl_spec_uid'];
            $this->artrelApplIdentifier = (string)$artrelDataArr['appl_identifier'];
            
            // convert existing comma seperated lists to property arrays
            if (strlen($artrelDataArr['exclusion_articles']) > 0) {
                $this->artrelExclusionArr = tx_pttools_div::returnArrayFromCsl($artrelDataArr['exclusion_articles']);
            }
            if (strlen($artrelDataArr['required_articles']) > 0) {
                $this->artrelRequiredArr = tx_pttools_div::returnArrayFromCsl($artrelDataArr['required_articles']);
            }
            if (strlen($artrelDataArr['related_articles']) > 0) {
                $this->artrelRelatedArr = tx_pttools_div::returnArrayFromCsl($artrelDataArr['related_articles']);
            }
            if (strlen($artrelDataArr['bundled_articles']) > 0) {
                $this->artrelBundledArr = tx_pttools_div::returnArrayFromCsl($artrelDataArr['bundled_articles']);  
            } 
        }
        
    }
    
    /**
     * Loads and sets the articleImageCollection property
     * 
     * @param	void
     * @return 	void
     * @author	Fabrizio Branca <branca@punkt.de>
     * @since 	2008-01-21
     */
    protected function setArticleImagesCollection() {
        
        $this->articleImageCollectionObj = new tx_ptgsashop_articleImageCollection($this->id);
        
    }
    
    /**  
     * Sets the image properties of an article using image blob data retrieved from a GSA database query
     * DEPRECATED: this function is unfinished - use setArticleImagesCollection() / the $articleImageCollectionObj property instead
     *
     * @deprecated  use setArticleImagesCollection() / the $articleImageCollectionObj property instead
     * @param   void
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-01-03
     */
    protected function setImageData() {
        
        // check if image file already exists: if yes, use existing image and return
        $checkFilePath = 'typo3temp/pics/article'.$this->id.'.png';
        if (@file_exists($checkFilePath)) {
            $imgDataArr = @getimagesize($checkFilePath);
            trace($imgDataArr, 0, '$imgDataArr (existing file)');
            $this->imageWebWidth    = $imgDataArr[0];
            $this->imageWebHeight   = $imgDataArr[1];
            $this->imageWebFilePath = $checkFilePath;
            return;
        }
        
        // if no image file found: get image as binary string from ERP BMP image
        $imageBinary = (string)tx_ptgsashop_articleAccessor::getInstance()->selectArticleImage($this->id); // binary string
        if (!empty($imageBinary)) {
            // ...convert ERP BMP image to web formatted image using TYPO3's graphics library
            $tmpImgFile = 'typo3temp/'.uniqid(rand(), true).'.bmp';
            t3lib_div::writeFileToTypo3tempDir(PATH_site.$tmpImgFile, $imageBinary);
            $stdGraphicLibObj = t3lib_div::makeInstance('t3lib_stdGraphic'); 
            $stdGraphicLibObj->init();
            $imgDataArr = $stdGraphicLibObj->imageMagickConvert($tmpImgFile, 'png',$w='',$h=''); # TODO: make dimension editable
            trace($imgDataArr, 0, '$imgDataArr (new file)');
            @unlink($tmpImgFile);
            
            $newFilePath = substr_replace($imgDataArr[3], 'article'.$this->id, (strrpos($imgDataArr[3], '/')+1), -(strlen($imgDataArr[3])-strrpos($imgDataArr[3], '.')));
            @rename($imgDataArr[3], $newFilePath);
            
            // ....set properties for web formatted image
            $this->imageWebFilePath = $newFilePath; 
            $this->imageWebWidth    = $imgDataArr[0];
            $this->imageWebHeight   = $imgDataArr[1];
        }
        
    }
    
    /**  
     * Sets the properties for an article that could not be found in the GSA database
     * ##### TODO: to be improved (see also updateArticle()) ####
     * 
     * @param   void
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-17
     */
    protected function setArticleNotFound() { 
        
        $this->artNo = (string)'##### Article not found #####';
        $this->match1 = (string)'##### Article not found #####';
        $this->match2 = (string)'##### Article not found #####';
        $this->defText = (string)'';
        $this->altText = (string)'';
        $this->webAddress = (string)'';
        $this->userField01 = (string)'';
        $this->userField02 = (string)'';
        $this->userField03 = (string)'';
        $this->userField04 = (string)'';
        $this->userField05 = (string)'';
        $this->userField06 = (string)'';
        $this->userField07 = (string)'';
        $this->userField08 = (string)'';
        $this->taxCodeInland = (string)'';
        $this->taxCodeAbroad = (string)'';
        $this->fixedCost1 = (double)0;
        $this->fixedCost2 = (double)0;
        $this->grossPriceFlag = (bool)0;
        $this->scalePriceCollectionObj = new tx_ptgsashop_scalePriceCollection(0);
        $this->custSpecRetailPrice = (double)-1;
        $this->custSpecGrossPriceFlag = (bool)0;
        $this->custSpecSpecialOfferFlag = (bool)0;
        $this->custSpecSpecialOfferStartDate = (string)'';
        $this->custSpecSpecialOfferEndDate = (string)'';
        $this->suppliersArr = array();
        $this->artrelMaxAmount = (integer)0;
        $this->artrelExclusionArr = array();
        $this->artrelRequiredArr = array();
        $this->artrelRelatedArr = array();
        $this->artrelBundledArr = array();
        $this->imageWebWidth    = (integer)0;
        $this->imageWebHeight   = (integer)0;
        $this->imageWebFilePath = (integer)0; 
        $this->eanNumber = (string)'';
                
    }
    
    /**
     * (Re-)Sets the properties of an article by retrieving up-to-date article data
     *
     * @param   integer   (optional) price category to use for the article (ERP: "VK-Preis" 1-5), depending on the customer's legitimation
     * @param   integer   (optional) UID of the current customer's main address data record in the GSA database (relates GSA database field "ADRESSE.NUMMER")
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-28
     */
    public function updateArticle($priceCategory=1, $customerId=0) {
        
        try {
            // set up-to-date usage relevant properties
            $this->date = (string)date('Y-m-d');
            $this->priceCategory = (integer)$priceCategory;
            $this->customerId = (integer)$customerId;
            
            // get up-to-date article and pricing data
            $this->setArticleBasicData();
            $this->setDefaultRetailPricingData();
            if ($this->customerId > 0) {
                $this->setCustomerSpecificPricingData();
            }
            $this->setArticleSuppliersData();
            $this->setArticleRelationData();
            
            // reset the archived price on every update
            $this->orderArchiveNetPrice = NULL; 
            
        ##### TODO: to be improved ####
        } catch (tx_pttools_exception $excObj) {
            $this->setArticleNotFound();
        }
        
    }
    
    /**
     * Calculates and returns the online net retail unit price of the current GSA article. If a customer specific price is set and it is lower than the default retail price, the customer specific price will be used.
     *
     * Underlying assumptions of ERP DB design (own test results):
     * 1.) Prices are fixed in records of the table VKPREIS for each purchase quantity graduation (DB field ABMENGE) of an article 
     * 2.) Join condition between tables VKPREIS and ARTIKEL is: VKPREIS.ARTINR = ARTIKEL.NUMMER
     * 3.) VKPREIS.PR01-PR05 is the retail basic price to use for an article's price (see ERP GUI "VK-Preis")
     * 4.) Price type (net/gross) for an article is noted ARTIKEL.PRBROTTO (0: net, 1: gross)
     * 5.) Special offers are possible for any quantity graduation (VKPREIS.ABMENGE).   
     *     If a special offer exists, VKPREIS.AKTION is set to 1 (else 0) and the offer price is found in VKPREIS.PR99. 
     *     VKPREIS.DATUMVON and VKPREIS.DATUMBIS are the start end end dates for the special offer period.
     * 6.) Notice: GSA DB fields PR99_2-PR99_5, DATUMVON2-DATUMVON5 and DATUMBIS2-DATUMBIS5 are currently 
     *     unaccounted for price calculation as nobody knows what they are used for :)
     *
     * @param   void
     * @return  double       calculated online net retail price of the given article
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-04-04
     */
    public function getNetRetailPrice() {
        
        $baseRetailPrice = 0.000000; // (double)
        $netCustSpecPrice = $this->getNetCustomerSpecificPrice(); // (double)
        
        // use orderArchiveNetPrice if this is set currently
        if (isset($this->orderArchiveNetPrice)) {
            
            $netRetailPrice = (double)$this->orderArchiveNetPrice;
            
        // use price calculation if no orderArchiveNetPrice is set
        } else {
        
            // use special offer price if offer exists and is valid for the current date
            if ($this->get_specialOfferFlag() == 1 && $this->get_specialOfferStartDate() <= $this->date && $this->get_specialOfferEndDate() >= $this->date) {
                $baseRetailPrice = $this->get_specialOfferRetailPrice();
                
            // else use default price of the given price category (if this price is 0, use next lower price category > 0)
            } else {
                $priceCategoryToUse = $this->priceCategory;
                while ($baseRetailPrice == 0 && $priceCategoryToUse >= 1) {
                    $basicRetailPriceCategoryGetter = 'get_basicRetailPriceCategory'.(string)$priceCategoryToUse;
                    $baseRetailPrice = (double)$this->$basicRetailPriceCategoryGetter();   // method call results e.g. in get_basicRetailPriceCategory1()
                    if (! $baseRetailPrice > 0) {
                        $priceCategoryToUse -= 1;
                    }
                }
            }
            
            // calcute net online retail price if price is marked as gross [ARTIKEL.PRBRUTTO: 0=net, 1=gross]
            if ($this->grossPriceFlag == 1) {
                $netDefaultRetailPrice = tx_pttools_finance::getNetPriceFromGross($baseRetailPrice, $this->getTaxRate(), 6); // we need precision 6 here for the case of double conversion (gross price in database (flag), net base price retrieval from this gross price, gross price retrieval from this base net price)
            // else use base net retail price
            } else {
                $netDefaultRetailPrice = $baseRetailPrice;
            }
            
            // if a valid customer specific price is set AND the shop is configured to use it always OR it is lower the default retail price: use customer specific price
            if ($netCustSpecPrice >= 0 && ($this->classConfigArr['custSpecPriceOverridesDefaultPrice'] == 1 || $netCustSpecPrice < $netDefaultRetailPrice)) {
                $netRetailPrice = $netCustSpecPrice;
                trace('Using customer specific price for article '.$this->id.'...');
            // if no valid customer specific price is set or the above conditions do not match: use default retail price
            } else {
                $netRetailPrice = $netDefaultRetailPrice;
            }
        
        }
        
        // HOOK: allow multiple hooks to modify netRetailPrice [NOTICE 04/07: do not rename 'article_hooks' to 'baseArticle_hooks' here since "old" hooks of this class are called 'article_hooks', too]
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['article_hooks']['getNetRetailPriceHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['article_hooks']['getNetRetailPriceHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $netRetailPrice = $hookObj->getNetRetailPriceHook($this, $netRetailPrice); // object params are passed as a reference (since PHP5) and can be manipulated in the hook method
            }
        }
        
        trace($netRetailPrice, 0, '$netRetailPrice');  
        return $netRetailPrice;
        
    }
    
    /**
     * Calculates and returns the customer specific net unit price of the current GSA article
     *
     * Notice: GSA DB table KUNPREIS fields RABATT, EURO, ARTNRSONPREIS, EKPREIS, ARTIKELPREIS, ARTIKELEURO are currently unaccounted for customer specific pricing data (as of 2007-06)
     *
     * @param   void
     * @return  double       -1.0000 if no valid customer specific price found OR calculated customer specific net retail price of the given article/customer combination
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-06-01
     */
    protected function getNetCustomerSpecificPrice() {
        
        $netCustSpecPrice = -1.0000;  // (double) -1 means: no valid customer specific price found
        
        // do customer specific price calculation only if a customer specific price is set (-1 means no price set)
        if ($this->custSpecRetailPrice >= 0) {
        
            // use special offer price if offer exists and is valid for the current date
            if ($this->custSpecSpecialOfferFlag == 1) {
                if ($this->custSpecSpecialOfferStartDate <= $this->date && $this->custSpecSpecialOfferEndDate >= $this->date) {
                    $baseCustSpecPrice = $this->custSpecRetailPrice;
                } else {
                    $baseCustSpecPrice = -1.0000; // (double) -1 means: no valid customer specific price found
                }
            } else {
                $baseCustSpecPrice = $this->custSpecRetailPrice;
            }
            
            // calcute net price if customer specific price is marked as gross (for positive prices only)
            if ($baseCustSpecPrice > 0 && $this->custSpecGrossPriceFlag == 1) {
                $netCustSpecPrice = tx_pttools_finance::getNetPriceFromGross($baseCustSpecPrice, $this->getTaxRate());
            // else use base net retail price
            } else {
                $netCustSpecPrice = $baseCustSpecPrice;
            }
            
        }
        
        trace($netCustSpecPrice, 0, '$netCustSpecPrice');  
        return $netCustSpecPrice;
        
    }
    
    /**
     * Returns an article's unit price to display (net or gross depending on param)
     * 
     * @param   boolean     flag whether the price should be returned as net price (optional): 0 returns gross price, 1 returns net price (default)
     * @param   boolean     (optional) flag whether the price should be rounded to the default precision of 4 decimal prices (default). 0 does not round at all.
     * @return  double      display price (net or gross depending on param)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-29
     */
    public function getDisplayPrice($getNet=1, $roundToDefaultPrecision=1) {
        
        if ($getNet == 1) {
            $rawPrice = $this->getNetRetailPrice();
        } else {
            $rawPrice = tx_pttools_finance::getGrossPriceFromNet($this->getNetRetailPrice(), $this->getTaxRate(), 6);  // we need precision 6 here for the case of double conversion (gross price in database (flag), net base price retrieval from this gross price, gross price retrieval from this base net price)
        }
        
        if ($roundToDefaultPrecision == 1) {
            $displayPrice = round($rawPrice, 4);
        } else {
            $displayPrice = $rawPrice;
        }
        
        return $displayPrice;
        
    }
    
    /**
     * Returns an article's unit tax cost
     * 
     * @param   boolean     (optional) flag whether the order is tax free (default:0)
     * @return  double      tax cost of the article (rounded to 4 digits after the decimal point)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-08-01
     */
    public function getTaxCost($isTaxFreeOrder=0) {
        
        $rawTaxCost = 0.000000; // (double)
        
        if ((boolean)$isTaxFreeOrder != 1) {
            $rawTaxCost = tx_pttools_finance::getTaxCostFromNet($this->getNetRetailPrice(), $this->getTaxRate(), 6);  // we need precision 6 here for the case of double conversion (gross price in database (flag), net base price retrieval from this gross price, gross price retrieval from this base net price)
        }
        
        $taxCost = round($rawTaxCost, 4);
        
        return $taxCost;
        
    }
    
    /**
     * Returns the fixed cost sum for this article item (fixed costs are always
     *
     * @param   boolean     flag wether the sum should be returned as net sum (optional): 0 returns gross sum, 1 returns net sum (default)
     * @return  double      total fixed cost sum for this article item
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-07-04
     */
    public function getFixedCost($getNet=1) {
        
        // fixed costs are always net in ERP!
        $netFixedCostTotal = bcadd($this->fixedCost1, $this->fixedCost2, 4);  
            // original calculation: $netFixedCostTotal = $this->fixedCost1 + $this->fixedCost2;
            // float operations may lead to precision problems (see www.php.net/float), using bcmath instead: this requires PHP to be configured with  '--enable-bcmath'
        
        // return requested fixed cost total
        if ($getNet == 1) {
            $fixedCostTotal = $netFixedCostTotal;
        } else {
            $fixedCostTotal = tx_pttools_finance::getGrossPriceFromNet($netFixedCostTotal, $this->getTaxRate());
        }
        
        return (double)$fixedCostTotal;
        
    }
    
    /**
     * Returns an article's fixed cost tax
     * 
     * @param   boolean     (optional) flag whether the order is tax free (default:0)
     * @return  double      tax cost of the article's fixed cost (rounded to 4 digits after the decimal point)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-08-01
     */
    protected function getFixedCostTax($isTaxFreeOrder=0) {
        
        $taxCost = 0.0000; // (double)
        
        if ((boolean)$isTaxFreeOrder != 1) {
            $taxCost = tx_pttools_finance::getTaxCostFromNet($this->getFixedCost(1), $this->getTaxRate());
        }
        
        return $taxCost;
        
    }
    
    /**
     * Returns the total price sum of all pieces (out of article's quantity) of this article item including fix costs 
     *
     * @param   boolean     flag wether the sum should be returned as net sum (optional): 0 returns gross sum, 1 returns net sum (default)
     * @return  double      total sum of all pieces (out of article's quantity) of this article item including fix costs 
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-01-08
     */
    public function getItemSubtotal($getNet=1) { 
        
        // float operations may lead to precision problems (see www.php.net/float), using bcmath instead: this requires PHP to be configured with  '--enable-bcmath'
        $articleSubTotal = bcmul($this->getDisplayPrice($getNet), $this->get_quantity(), 4);
            // original calculation: $articleSubTotal = ($this->getDisplayPrice($getNet) * $this->get_quantity());
        $itemSubTotal = bcadd($articleSubTotal, $this->getFixedCost($getNet), 4);  
            // original calculation: $itemSubTotal = $articleSubTotal + $this->getFixedCost($getNet);
            
        return (double)$itemSubTotal;
        
    }
    
    /**
     * Returns the total tax cost sum of all pieces (out of article's quantity) of this article item
     *
     * @param   boolean     (optional) flag whether the order is tax free (default:0)
     * @return  double      total tax cost sum of all pieces (out of article's quantity) of this article item
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-01-08
     */
    public function getItemTaxSubtotal($isTaxFreeOrder=0) {
        
        // float operations may lead to precision problems (see www.php.net/float), using bcmath instead: this requires PHP to be configured with  '--enable-bcmath'
        $articleTaxSubtotal = bcmul($this->getTaxCost($isTaxFreeOrder), $this->get_quantity(), 4);
            // original calculation: $this->getTaxCost($isTaxFreeOrder) * $this->get_quantity();
        $itemTaxSubtotal = bcadd($articleTaxSubtotal, $this->getFixedCostTax($isTaxFreeOrder), 4); 
            // original calculation: $articleTaxSubtotal + $this->getFixedCostTax($isTaxFreeOrder);
            
        return (double)$itemTaxSubtotal;
        
    }
    
    /**
     * Returns the article's unit tax rate
     * 
     * @return  double      current tax rate of the article (double with 4 digits after the decimal point)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-30
     */
    public function getTaxRate() {
        
        $taxRate = tx_ptgsashop_lib::getTaxRate($this->taxCodeInland, $this->date);
        
        return round($taxRate, 4);
        
    }
    
    /**
     * Returns the timestamp of article's delivery end date
     * 
     * @return  integer     timestamp of article's delivery end date
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-10-30
     */
    public function getArtdelEndTs() {
        
        $deliveryEndTs = 0; // (integer)
        
        // HOOK for alternative setting of the  article's delivery end date [NOTICE 01/07: do not rename 'article_hooks' to 'baseArticle_hooks' here since this hook may be used already!] 
        if (($hookObj = tx_pttools_div::hookRequest('pt_gsashop', 'article_hooks', 'getArtdelEndTsHook')) !== false) {
            $deliveryEndTs = (integer)$hookObj->getArtdelEndTsHook($this); // use hook method if hook has been found
        // default setting = start timestamp + runtime
        } else {
            $deliveryEndTs = $this->artdelStartTs + $this->artdelRuntime;
        }
        
        return $deliveryEndTs;
        
    }
    
    /**
     * Returns an optional additional text string for the article to be set by a hook. This may be used e.g. for activating special display purposes by hooking into this method.
     * PLEASE NOTE: at the current state (04/2007) the additional text will be cut in the order email text template after 42 chars (see tx_ptgsashop_orderPresentator::getPlaintextPresentation)!
     * 
     * @param   void        
     * @return  string     additional text string for the article to be set by a hook, default is empty string
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-04-20
     */
    public function getAdditionalText() {
        
        $addText = '';
            
        // HOOK: allow multiple hooks to set the additional text [NOTICE 04/07: do not rename 'article_hooks' to 'baseArticle_hooks' here since "old" hooks of this class are called 'article_hooks', too]
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['article_hooks']['getAdditionalTextHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['article_hooks']['getAdditionalTextHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                // PLEASE NOTE: at the current state (04/2007) the additional text will be cut in the order email text template after 42 chars (see tx_ptgsashop_orderPresentator::getPlaintextPresentation)!
                $addText = $hookObj->getAdditionalTextHook($this, $addText); // object params are passed as a reference (since PHP5) and can be manipulated in the hook method
            }
        }
        
        return $addText;
        
    }
    
    /**
     * Returns a TYPO3 frontend page url for the article detail page. For TYPO3 frontend use only!
     * TODO: this method should be "outsourced" to an articleFePresentator (or similar name) class...
     * 
     * @return  string      TYPO3 frontend page url for the article detail page or empty string if article should not be linked (depending on configuration in GSA-DB field ARTIKEL.WEBADRESSE)
     * @global  $GLOBALS['TSFE']
     * @author  Rainer Kuhn <kuhn@punkt.de>, typolink reorganization (06/2008) with Fabrizio Branca <branca@punkt.de>
     * @since   2007-04-24, major revision 2008-06-24
     */
    public function getFePageLink() {
        
        $fePageLink = ''; // (string)
        
        // prepare typolink
        $typolinkConf = $this->classConfigArr['articleSingleViewTypoLink.'];  // TS typo link object
        $typolinkConf['returnLast'] = 'url';
        
        // do not link article if GSA-DB field ARTIKEL.WEBADRESSE value is -1
        if (trim($this->webAddress) == '-1') {
            
            $fePageLink = '';
                                                
        // use TYPO3 page ID or alias from page in GSA-DB field ARTIKEL.WEBADRESSE if the field is not empty (but anything other than -1)
        } elseif (strlen(trim($this->webAddress)) > 0) {
            
            unset($typolinkConf['additionalParams']);
            unset($typolinkConf['additionalParams.']);
            $typolinkConf['parameter'] = tx_pttools_div::htmlOutput($this->webAddress);
            
            $fePageLink = $GLOBALS['TSFE']->cObj->typolink(NULL, $typolinkConf);
        
        // use default article single view page in all other cases (including the GSA-DB field ARTIKEL.WEBADRESSE being empty)
        } else {
            
            $securityHash = md5($this->id . $this->classConfigArr['md5SecurityCheckSalt']); // hash to prevent article single view page from unwanted access
            
            $typolinkConf['additionalParams'] = $GLOBALS['TSFE']->cObj->stdWrap($typolinkConf['additionalParams'], $typolinkConf['additionalParams.']);
            $typolinkConf['additionalParams'] .= '&'.self::ARTICLESINGLEVIEW_CLASS_NAME.'[asv_id]='.intval($this->id);
            $typolinkConf['additionalParams'] .= '&'.self::ARTICLESINGLEVIEW_CLASS_NAME.'[asv_hash]='.$securityHash;
            
            // handle empty typolink parameter as exception
            try {
                $typolinkConf['parameter'] = $GLOBALS['TSFE']->cObj->stdWrap($typolinkConf['parameter'], $typolinkConf['parameter.']);
                tx_pttools_assert::isNotEmptyString($typolinkConf['parameter']);
                
            } catch (tx_pttools_exceptionAssertion $parameterExceptionObj) {
                $parameterExceptionObj->handle();
            }
            
            $fePageLink = $GLOBALS['TSFE']->cObj->typolink(NULL, $typolinkConf);
            
        }
        
        return $fePageLink;
        
    }
    
    /**
     * Returns the basic retail price of price category 1 for the current quantity of the article
     *
     * @param   void        
     * @return  double      the basic retail price of price category 1 for the current quantity of the article
     * @since   2005-09-20 (changed from original property getter to dummy method 2007-10-24)
     */
    public function get_basicRetailPriceCategory1() {
        
        return $this->scalePriceCollectionObj->getItemByQuantity($this->priceCalcQty)->get_basicRetailPriceCategory1();
        
    }
    
    /**
     * Returns the basic retail price of price category 2 for the current quantity of the article
     *
     * @param   void        
     * @return  double      the basic retail price of price category 2 for the current quantity of the article
     * @since   2005-09-20 (changed from original property getter to dummy method 2007-10-24)
     */
    public function get_basicRetailPriceCategory2() {
        
        return $this->scalePriceCollectionObj->getItemByQuantity($this->priceCalcQty)->get_basicRetailPriceCategory2();
        
    }
    
    /**
     * Returns the basic retail price of price category 3 for the current quantity of the article
     *
     * @param   void        
     * @return  double      the basic retail price of price category 3 for the current quantity of the article
     * @since   2005-09-20 (changed from original property getter to dummy method 2007-10-24)
     */
    public function get_basicRetailPriceCategory3() {
        
        return $this->scalePriceCollectionObj->getItemByQuantity($this->priceCalcQty)->get_basicRetailPriceCategory3();
        
    }
    
    /**
     * Returns the basic retail price of price category 4 for the current quantity of the article
     *
     * @param   void        
     * @return  double      the basic retail price of price category 4 for the current quantity of the article
     * @since   2005-09-20 (changed from original property getter to dummy method 2007-10-24)
     */
    public function get_basicRetailPriceCategory4() {
        
        return $this->scalePriceCollectionObj->getItemByQuantity($this->priceCalcQty)->get_basicRetailPriceCategory4();
        
    }
    
    /**
     * Returns the basic retail price of price category 5 for the current quantity of the article
     *
     * @param   void        
     * @return  double      the basic retail price of price category 5 for the current quantity of the article
     * @since   2005-09-20 (changed from original property getter to dummy method 2007-10-24)
     */
    public function get_basicRetailPriceCategory5() {
        
        return $this->scalePriceCollectionObj->getItemByQuantity($this->priceCalcQty)->get_basicRetailPriceCategory5();
        
    }
    
    /**
     * Returns the special offer flag for the current quantity of the article
     *
     * @param   void
     * @throws	tx_pttools_exceptionInternal	if no scale price object was found in the scale price collection        
     * @return  boolean      special offer flag for the current quantity of the article
     * @since   2005-09-20 (changed from original property getter to dummy method 2007-10-24)
     */
    public function get_specialOfferFlag() {
        
        $scalePriceObj = $this->scalePriceCollectionObj->getItemByQuantity($this->priceCalcQty);
        
        if (!($scalePriceObj instanceof tx_ptgsashop_scalePrice)) {
            $debugMsg = sprintf('No scale price object found in collection for quantity "%s" (Collection contains "%s" elements. Article: "%s")', 
                                $this->priceCalcQty, $this->scalePriceCollectionObj->count(), $this->id);
            throw new tx_pttools_exceptionInternal('No scale price object found', $debugMsg);
        }
        
        return $scalePriceObj->get_specialOfferFlag();
        
    }
    
    /**
     * Returns the special offer retail price for the current quantity of the article
     *
     * @param   void        
     * @return  double      special offer retail price for the current quantity of the article
     * @since   2005-09-20 (changed from original property getter to dummy method 2007-10-24)
     */
    public function get_specialOfferRetailPrice() {
        
        return $this->scalePriceCollectionObj->getItemByQuantity($this->priceCalcQty)->get_specialOfferRetailPrice();
        
    }
    
    /**
     * Returns the special offer start date for the current quantity of the article
     *
     * @param   void        
     * @return  string      special offer start date for the current quantity of the article
     * @since   2005-09-20 (changed from original property getter to dummy method 2007-10-24)
     */
    public function get_specialOfferStartDate() {
        
        return $this->scalePriceCollectionObj->getItemByQuantity($this->priceCalcQty)->get_specialOfferStartDate();
        
    }
    
    /**
     * Returns the special offer end date for the current quantity of the article
     *
     * @param   void        
     * @return  string      special offer end date for the current quantity of the article
     * @since   2005-09-20 (changed from original property getter to dummy method 2007-10-24)
     */
    public function get_specialOfferEndDate() {
        
        return $this->scalePriceCollectionObj->getItemByQuantity($this->priceCalcQty)->get_specialOfferEndDate();
        
    }
    
    
    
    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer      property value
     * @since   2005-09-20
     */
    public function get_id() {
        
        return $this->id;
        
    }
    
    /**
     * @return int uid of this article in the order archive article table
     * @author Daniel Lienert <lienert@punkt.de>
     */
    public function get_orderArchiveUid() {
    	return $this->orderArchiveUid;
    }
    
    /**
     * Returns the property value
     *
     * @param   void
     * @return  integer     property value 
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-28
     */
    public function get_quantity() {
        
        return $this->quantity;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void
     * @return  integer     property value 
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-29
     */
    public function get_priceCalcQty() {
        
        return $this->priceCalcQty;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer      property value
     * @since   2005-09-20
     */
    public function get_priceCategory() {
        
        return $this->priceCategory;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2005-09-20
     */
    public function get_date() {
        
        return $this->date;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  boolean      property value
     * @since   2005-09-20
     */
    public function get_imageFlag() {
        
        return $this->imageFlag;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-08-28
     */
    public function get_description() {
        
        return $this->description;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  boolean      property value
     * @since   2007-01-30
     */
    public function get_isPhysical() {
        
        return $this->isPhysical;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  mixed       NULL or object implementing interface tx_ptgsashop_iApplSpecArticleDataObj     
     * @since   2007-04-20
     */
    public function get_applSpecDataObj() {
        
        return $this->applSpecDataObj;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2005-09-20
     */
    public function get_artNo() {
        
        return $this->artNo;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2005-09-20
     */
    public function get_match1() {
        
        return $this->match1;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2005-09-20
     */
    public function get_match2() {
        
        return $this->match2;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2005-09-20
     */
    public function get_defText() {
        
        return $this->defText;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value (may habe been modified by hooking of other extensions)
     * @since   2005-09-20 / hook version: 2007-04-20
     */
    public function get_altText() {
        
        $altText = $this->altText;
            
        // HOOK: allow multiple hooks to modify altText [NOTICE 04/07: do not rename 'article_hooks' to 'baseArticle_hooks' here since "old" hooks of this class are called 'article_hooks', too]
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['article_hooks']['get_altTextHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['article_hooks']['get_altTextHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $altText = $hookObj->get_altTextHook($this, $altText); // object params are passed as a reference (since PHP5) and can be manipulated in the hook method
            }
        }
        
        return $altText;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  boolean      property value
     * @since   2005-09-20
     */
    public function get_grossPriceFlag () {
        
        return $this->grossPriceFlag ;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2005-09-20
     */
    public function get_taxCodeInland() {
        
        return $this->taxCodeInland;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2005-09-20
     */
    public function get_taxCodeAbroad() {
        
        return $this->taxCodeAbroad;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  double      _net_ fixed cost 1 for the article
     * @since   2007-07-04
     */
    public function get_fixedCost1() {
        
        return $this->fixedCost1;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  double      _net_ fixed cost 2 for the article
     * @since   2007-07-04
     */
    public function get_fixedCost2() {
        
        return $this->fixedCost2;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  boolean      property value
     * @since   2007-01-04
     */
    public function get_isOnlineArticle() {
        
        return $this->isOnlineArticle;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  boolean      property value
     * @since   2008-01-24
     */
    public function get_isPassive() {
        
        return $this->isPassive;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2007-04-24
     */
    public function get_webAddress() {
        
        return $this->webAddress;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2007-04-20
     */
    public function get_userField01() {
        
        return $this->userField01;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2007-04-20
     */
    public function get_userField02() {
        
        return $this->userField02;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2007-04-20
     */
    public function get_userField03() {
        
        return $this->userField03;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2007-04-20
     */
    public function get_userField04() {
        
        return $this->userField04;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2007-04-20
     */
    public function get_userField05() {
        
        return $this->userField05;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2007-04-20
     */
    public function get_userField06() {
        
        return $this->userField06;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2007-04-20
     */
    public function get_userField07() {
        
        return $this->userField07;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2007-04-20
     */
    public function get_userField08() {
        
        return $this->userField08;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  tx_ptgsashop_scalePriceCollection       scale price collection object of type tx_ptgsashop_scalePriceCollection
     * @since   2007-10-24
     */
    public function get_scalePriceCollectionObj() {
        
        return $this->scalePriceCollectionObj;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer      property value
     * @since   2007-06-01
     */
    public function get_customerId() {
        
        return $this->customerId;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  array      property value
     * @since   2007-06-22
     */
    public function get_suppliersArr() {
        
        return $this->suppliersArr;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer      property value
     * @since   2006-08-03
     */
    public function get_artrelMaxAmount() {
        
        return $this->artrelMaxAmount;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  array      property value
     * @since   2006-08-03
     */
    public function get_artrelExclusionArr() {
        
        return $this->artrelExclusionArr;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  array      property value
     * @since   2006-08-03
     */
    public function get_artrelRequiredArr() {
        
        return $this->artrelRequiredArr;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  array      property value
     * @since   2006-08-03
     */
    public function get_artrelRelatedArr() {
        
        return $this->artrelRelatedArr;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  array      property value
     * @since   2006-08-03
     */
    public function get_artrelBundledArr() {
        
        return $this->artrelBundledArr;
        
    }
    
    /**
     * Returns the property value (application specific uid to use by add-on applications for additional data)
     *
     * @param   void        
     * @return  integer      property value
     * @since   2007-01-11
     */
    public function get_artrelApplSpecUid() {
        
        return $this->artrelApplSpecUid;
        
    }
    
    /**
     * Returns the property value (application identifier to specify the add-on application)
     *
     * @param   void        
     * @return  string      property value
     * @since   2008-10-24
     */
    public function get_artrelApplIdentifier() {
        
        return $this->artrelApplIdentifier;
        
    }
    
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer      property value
     * @since   2006-10-24
     */
    public function get_artdelStartTs() {
        
        return $this->artdelStartTs;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer      property value
     * @since   2006-10-30
     */
    public function get_artdelRuntime() {
        
        return $this->artdelRuntime;
        
    }
    
    /**
     * Returns the property value
     * DEPRECATED: use get_articleImageCollectionObj() / the $articleImageCollectionObj property instead
     *
     * @deprecated  use get_articleImageCollectionObj() / the $articleImageCollectionObj property instead
     * @param   void        
     * @return  integer      property value
     * @since   2006-01-03
     */
    public function get_imageWebWidth() {
        
        return $this->imageWebWidth;
        
    }
    
    /**
     * Returns the property value
     * DEPRECATED: use get_articleImageCollectionObj() / the $articleImageCollectionObj property instead
     *
     * @deprecated  use get_articleImageCollectionObj() / the $articleImageCollectionObj property instead
     * @param   void        
     * @return  integer      property value
     * @since   2006-01-03
     */
    public function get_imageWebHeight() {
        
        return $this->imageWebHeight;
        
    }
    
    /**
     * Returns the property value
     * DEPRECATED: use get_articleImageCollectionObj() / the $articleImageCollectionObj property instead
     *
     * @deprecated  use get_articleImageCollectionObj() / the $articleImageCollectionObj property instead
     * @param   void        
     * @return  string      property value
     * @since   2006-01-03
     */
    public function get_imageWebFilePath() {
        
        return $this->imageWebFilePath;
        
    }
    
    /**
     * Returns the property value
     * 
     * @param 	void
     * @return 	tx_ptgsashop_articleImageCollection	property value
     * @since 	2008-01-11
     */
    public function get_articleImageCollectionObj() {
        
        return $this->articleImageCollectionObj;
        
    }
    
    /**
     * Returns the property value
     * 
     * @param 	void
     * @return 	string	property value
     * @author	Fabrizio Branca <branca@punkt.de>
     * @since 	2009-09-22
     */
    public function get_eanNumber() {
    
        return $this->eanNumber;
    
    }
    
    /**
     * Sets the quantity of an article and on demand updates the retail pricing data depending on this quantity (multiple pricing data records for different quantities)
     *
     * @param   integer     quantity of the article 
     * @param   boolean     (optional) flag whether pricing data related to quantity should be updated, too (default=true)
     * @return  void
     * @throws  tx_pttools_exception   if article quantity to set is less than zero
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-28
     */
    public function set_quantity($quantity, $updatePricingData=true) {
        
        if ($quantity < 0) {
            throw new tx_pttools_exception('Invalid article quantity', 3, 'Article quantity must not be below zero');
        }
        
        $this->quantity = (integer)$quantity;
        
        if ($updatePricingData == true && $this->quantity > 0) {
            $this->priceCalcQty = (integer)$quantity;
        }
        
    }
    
    /**
     * Sets the price calculation quantity of an article (used because of multiple scale price records for different quantities)
     *
     * @param   integer     price calculation quantity of the article 
     * @return  void
     * @throws  tx_pttools_exception   if price calculation quantity to set is less than zero
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-25
     */
    public function set_priceCalcQty($priceCalcQty) {
        
        if ((integer)$priceCalcQty < 1) {
            throw new tx_pttools_exception('Invalid article quantity', 3, 'The article\'s price calculation quantity quantity must not be below 1');
        }
        
        $this->priceCalcQty = (integer)$priceCalcQty;
        
    }
    
    /**
     * Sets the property value: flag whether the article is physical (deliverable articles, e.g. books) or not ("virtual", non-deliverable articles, e.g. webhosting products)
     *
     * @param   boolean     flag whether the article is physical or not
     * @return  void
     * @since   2007-01-30
     */
    public function set_isPhysical($isPhysical) {
        
        $this->isPhysical = (boolean)$isPhysical;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   tx_ptgsashop_iApplSpecArticleDataObj      object implementing interface tx_ptgsashop_iApplSpecArticleDataObj        
     * @return  void
     * @since   2007-04-20
     */
    public function set_applSpecDataObj(tx_ptgsashop_iApplSpecArticleDataObj $applSpecDataObj) {
        
        $this->applSpecDataObj = $applSpecDataObj;
        
    }
    
    /**
     * Sets the property value
     *
     * @param   string       property value       
     * @return  void
     * @since   2007-10-17
     */
    public function set_artNo($artNo) {
        $this->artNo = (string) $artNo;
    }
    
    /**
     * Sets the property value
     *
     * @param   string       property value       
     * @return  void
     * @since   2007-10-17
     */
    public function set_match1($match1) {
        $this->match1 = (string) $match1;
    }
    
    /**
     * Sets the property value
     *
     * @param   string       property value       
     * @return  void
     * @since   2007-10-17
     */
    public function set_match2($match2) {
        $this->match2 = (string) $match2;
    }
    
    /**
     * Sets the property value
     *
     * @param   string       property value       
     * @return  void
     * @since   2007-10-17
     */
    public function set_defText($defText) {
        $this->defText = (string) $defText;
    }
    
    /**
     * Sets the property value
     *
     * @param   string       property value       
     * @return  void
     * @since   2007-10-17
     */
    public function set_altText($altText) {
        $this->altText = (string) $altText;
    }
    
    /**
     * @param $uid int  
     * @return void
     */
    public function set_orderArchiveUid($orderArchiveUid) {
    	$this->orderArchiveUid = (int) $orderArchiveUid;
    }
    
    /**
     * Sets the property value
     *
     * @param   boolean       property value       
     * @return  void
     * @since   2007-10-17
     */
    public function set_grossPriceFlag($grossPriceFlag) {
        $this->grossPriceFlag = (boolean) $grossPriceFlag;
    }
    
    /**
     * Sets the property value
     *
     * @param   string       property value       
     * @return  void
     * @since   2007-10-17
     */
    public function set_taxCodeInland($taxCodeInland) {
        $this->taxCodeInland = (string) $taxCodeInland;
    }
    
    /**
     * Sets the property value
     *
     * @param   double       property value       
     * @return  void
     * @since   2007-10-17
     */
    public function set_fixedCost1($fixedCost1) {
        $this->fixedCost1 = (double) $fixedCost1;
    }
    
    /**
     * Sets the property value
     *
     * @param   double       property value       
     * @return  void
     * @since   2007-10-17
     */
    public function set_fixedCost2($fixedCost2) {
        $this->fixedCost2 = (double) $fixedCost2;
    }
    
    /**
     * Sets the property value: flag whether the article is an online article
     *
     * @param   boolean     flag whether the article is an online article
     * @return  void
     * @since   2007-10-10
     */
    protected function set_isOnlineArticle($isOnlineArticle) {
        
        $this->isOnlineArticle = (boolean)$isOnlineArticle;
        
    }
    
    /**
     * Sets the property value: flag whether the article is a passive article
     *
     * @param   boolean     flag whether the article is a passive article
     * @return  void
     * @since   2008-01-24
     */
    public function set_isPassive($isPassive) {
        
        $this->isPassive = (boolean)$isPassive;
        
    }
    
    /**
     * Sets the property value
     *
     * @param   string       property value       
     * @return  void
     * @since   2007-10-17
     */
    public function set_webAddress($webAddress) {
        $this->webAddress = (string) $webAddress;
    }
    
    /**
     * Sets the property value
     *
     * @param   string       property value       
     * @return  void
     * @since   2007-10-17
     */
    public function set_userField01($userField01) {
        $this->userField01 = (string) $userField01;
    }
    
    /**
     * Sets the property value
     *
     * @param   string       property value       
     * @return  void
     * @since   2007-10-17
     */
    public function set_userField02($userField02) {
        $this->userField02 = (string) $userField02;
    }
    
    /**
     * Sets the property value
     *
     * @param   string       property value       
     * @return  void
     * @since   2007-10-17
     */
    public function set_userField03($userField03) {
        $this->userField03 = (string) $userField03;
    }
    
    /**
     * Sets the property value
     *
     * @param   string       property value       
     * @return  void
     * @since   2007-10-17
     */
    public function set_userField04($userField04) {
        $this->userField04 = (string) $userField04;
    }
    
    /**
     * Sets the property value
     *
     * @param   string       property value       
     * @return  void
     * @since   2007-10-17
     */
    public function set_userField05($userField05) {
        $this->userField05 = (string) $userField05;
    }
    
    /**
     * Sets the property value
     *
     * @param   string       property value       
     * @return  void
     * @since   2007-10-17
     */
    public function set_userField06($userField06) {
        $this->userField06 = (string) $userField06;
    }
    
    /**
     * Sets the property value
     *
     * @param   string       property value       
     * @return  void
     * @since   2007-10-17
     */
    public function set_userField07($userField07) {
        $this->userField07 = (string) $userField07;
    }
    
    /**
     * Sets the property value
     *
     * @param   string       property value       
     * @return  void
     * @since   2007-10-17
     */
    public function set_userField08($userField08) {
        $this->userField08 = (string) $userField08;
    }
    
    /**
     * Sets the property value: timestamp of the start date of the article's delivery
     *
     * @param   integer     timestamp of the start date of the article's delivery
     * @return  void
     * @since   2006-10-24
     */
    public function set_artdelStartTs($artdelStartTs) {
        
        $this->artdelStartTs = (integer)$artdelStartTs;
        
    }
    
    /**
     * Sets the property value: runtime of the article's delivery in seconds
     *
     * @param   integer     runtime of the article's delivery in seconds
     * @return  void
     * @since   2006-10-30
     */
    public function set_artdelRuntime($artdelRuntime) {
        
        $this->artdelRuntime = (integer)$artdelRuntime;
        
    }
    
    /**
     * Sets the property value: article image collection
     * 
     * @param 	tx_ptgsashop_articleImageCollection		property value
     * @return 	void
     * @since 	2008-01-11
     */
    public function set_articleImageCollectionObj(tx_ptgsashop_articleImageCollection $articleImageCollectionObj) {
        
        $this->articleImageCollectionObj = $articleImageCollectionObj;
        
    }
    
    /**
     * Sets the property value: ean number
     * 
     * @param 	string		property value
     * @return 	void
     * @author	Fabrizio Branca <branca@punkt.de>
     * @since	2008-09-22
     */
    public function set_eanNumber($eanNumber) {
    
        $this->eanNumber = (string)$eanNumber;
    
    }
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_baseArticle.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_baseArticle.php']);
}

?>