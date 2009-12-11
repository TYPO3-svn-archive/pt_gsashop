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
 * Article collection class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_articleCollection.php,v 1.36 2008/09/30 15:30:29 ry44 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2005-09-27
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_article.php'; // GSA shop article class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_articleFactory.php';  // GSA shop article factory class
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_articleAccessor.php';  // GSA Shop database accessor class for articles

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function



/**
 * Article collection class
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2005-09-27
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_articleCollection implements IteratorAggregate, Countable {  // (Interface Countable since PHP 5.1)
    
    /**
     * Properties
     */
    protected $itemsArr = array(); // (array) array using articleID as key and article object as value
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
    
    /**
     * Class constructor
     *
     * @param   boolean     (optional) flag whether a collection of all online articles in the database should be created (default: false = create empty collection)
     * @return  void     
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-04-19
     */
    public function __construct($createAllOnlineArticlesCollection=false) {
        
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        // create a collection of all online articles in the database if requested by param
        if ($createAllOnlineArticlesCollection == true) {
            $onlineArticlesArr = tx_ptgsashop_articleAccessor::getInstance()->selectOnlineArticles();
            if (is_array($onlineArticlesArr)) {
                foreach ($onlineArticlesArr as $articleDataArr) {
                    $this->addItem(new tx_ptgsashop_article($articleDataArr['NUMMER']));
                }
            }
        }
        
    }
    
    /**
     * Load from order archive: restores the object's properties of data retrieved from the order archive database. This method should be called only directly after new instantiation of the (empty) object.
     * 
     * @param   integer     UID of the related parent order record in the order archive database
     * @param   integer     UID of the related parent delivery record in the order archive database
     * @return  tx_ptgsashop_articleCollection      object of type tx_ptgsashop_articleCollection, "filled" with properties from order archive database
     * @author  Fabrizio Branca <branca@punkt.de>
     * @since   2007-04
     */
    public function loadFromOrderArchive($ordersId, $deliveriesId) {
        
        $orderAccessor = tx_ptgsashop_orderAccessor::getInstance();
        $orderData = $orderAccessor->selectOrdersArticleList($ordersId, $deliveriesId);

        foreach ($orderData as $item){
            $tmparticleObj = tx_ptgsashop_articleFactory::createArticle($item['gsa_id_artikel']);
            $tmparticleObj->loadFromOrderArchive($item['uid']);

            $this->addItem($tmparticleObj);
            unset($tmparticleObj);
        }
        
        return $this;

    }
    
    
     
    /***************************************************************************
     *   ITERATORAGGREGATE API METHODS
     **************************************************************************/
     
    /**
     * Definded by IteratorAggregate interface: returns an iterator for the object 
     *
     * @param   void 
     * @return  ArrayIterator     object of type ArrayIterator: Iterator for articles within this collection
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-09-25 
     */ 
    public function getIterator() {
        
        $articleIterator = new ArrayIterator($this->itemsArr);
        #trace($articleIterator, 0, '$articleIterator');
        
        return $articleIterator;
        
    }
    
    
    
    /***************************************************************************
     *   COUNTABLE INTERFACE API METHODS
     **************************************************************************/
     
    /**
     * Definded by Countable interface: Returns the number of items (the number of different article types/item groups)  
     *
     * @param   void 
     * @return  integer     number of items in the items array (the number of different article types/item groups)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-28 
     */ 
    public function count() {
        
        return count($this->itemsArr);
        
    }
     
     
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
     
    /**
     * Return the total number of articles in the article collection
     *
     * @param   void 
     * @return  integer     number of articles in the article collection
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-10-04 
     */ 
    public function countArticles() {
        
            $articleCount = 0;
            foreach ($this->itemsArr as $artObj) { 
                $articleCount += $artObj->get_quantity();
            }
            
            return $articleCount;
            
    }
    
    /**
     * Returns one article specified by ID from the article collection
     *
     * @param   integer     ID of the required article
     * @return  mixed       object of type tx_ptgsashop_baseArticle if specified article is found, false otherwise
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-10-11
     */
    public function getItem($articleId) { 
        
        $reqArtObj = false;
        
        if (array_key_exists($articleId, $this->itemsArr)) {
            $reqArtObj = $this->itemsArr[$articleId];
        }
        
        return $reqArtObj;
    }
    
    /**
     * Adds an article item (with arbitrary internal quantity) to the article collection
     *
     * @param   tx_ptgsashop_article     article to add, object of type tx_ptgsashop_article (Type Hint)
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-26
     */
    public function addItem(tx_ptgsashop_baseArticle $articleObj) { 
        
        if (array_key_exists($articleObj->get_id(), $this->itemsArr)) {
            $newArticleQty = $this->itemsArr[$articleObj->get_id()]->get_quantity() + $articleObj->get_quantity();
            $this->itemsArr[$articleObj->get_id()]->set_quantity($newArticleQty);
            
            // put item at the end of the array
            $tmp = $this->itemsArr[$articleObj->get_id()];
            unset($this->itemsArr[$articleObj->get_id()]);
            $this->itemsArr[$articleObj->get_id()] = $tmp;
            
            // process additional action if both articles have a valid applSpecDataObj
            if (is_object($this->itemsArr[$articleObj->get_id()]->get_applSpecDataObj()) && is_object($articleObj->get_applSpecDataObj())) {
                $this->itemsArr[$articleObj->get_id()]->get_applSpecDataObj()->processOnAddItem($articleObj->get_applSpecDataObj(), $articleObj->get_quantity());
            }
            
        } else {
            $this->itemsArr[$articleObj->get_id()] = $articleObj;
        }
        
    }
    
    /**
     * Removes an article item (with arbitrary internal quantity) from the article collection 
     *
     * @param   tx_ptgsashop_baseArticle      article to remove, object of type tx_ptgsashop_baseArticle required (Type Hint)
     * @return  boolean     status of operation (false if article did not exist in collection or on quantity mismatch, true otherwise)
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-09-27
     */
    public function removeItem(tx_ptgsashop_baseArticle $articleObj) { 
        
        if (!array_key_exists($articleObj->get_id(), $this->itemsArr)) {
            return false;
        }
        
        if ($this->itemsArr[$articleObj->get_id()]->get_quantity() > $articleObj->get_quantity()) {
            $newArticleQty = $this->itemsArr[$articleObj->get_id()]->get_quantity() - $articleObj->get_quantity();
            $this->itemsArr[$articleObj->get_id()]->set_quantity($newArticleQty);
            
            // process additional action if both articles have a valid applSpecDataObj
            if (is_object($this->itemsArr[$articleObj->get_id()]->get_applSpecDataObj()) && is_object($articleObj->get_applSpecDataObj())) {
                $this->itemsArr[$articleObj->get_id()]->get_applSpecDataObj()->processOnRemoveItem($articleObj->get_applSpecDataObj(), $articleObj->get_quantity());
            }
            
        } elseif ($this->itemsArr[$articleObj->get_id()]->get_quantity() == $articleObj->get_quantity()) {
            $this->deleteItem($this->itemsArr[$articleObj->get_id()]);
        } else {
            return false;
        }
        
        return true;
        
    }
    
    /**
     * Deletes one item from the article collection
     *
     * @param   integer     ID of the item (=ID of the article/Array-Key in $items-Array)
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-28
     */
    public function deleteItem($itemId) {
        
        if (array_key_exists($itemId, $this->itemsArr)) {
            unset($this->itemsArr[$itemId]);
        }
        
    }
    
    /**
     * Clears all items of the article collection
     *
     * @param   void
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-28
     */
    public function clearItems() {
        
        $this->itemsArr = array();
        
    }
    
    /**
     * Updates the properties of all items found in the article collection by retrieving up-to-date article data
     *
     * @param   integer   (optional) price category to use for the article (ERP GUI: "VK-Preis" 1-5), depending on the customer's legitimation
     * @param   integer   (optional) UID of the current customer's main address data record in the GSA database (relates GSA database field "ADRESSE.NUMMER")
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-28
     */
    public function updateItemsData($priceCategory=1, $customerId=0) {
        
        foreach ($this->itemsArr as $key=>$artObj) {
            $artObj->updateArticle($priceCategory, $customerId);
            // $this->itemsArr[$key] = $artObj; // needed for PHP4 only (due to the article object being not a reference, but a copy in PHP4)
        }
        
    }
    
    /**
     * Updates the quantity of an article collection item
     *
     * @param   integer     ID of the item (=ID of the article/Array-Key in $items-Array) 
     * @param   integer     new quantity of the item to set in article collection 
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-28
     */
    public function updateItemQuantity($itemId, $quantity) {
        
        if (array_key_exists($itemId, $this->itemsArr)) {
            $this->itemsArr[$itemId]->set_quantity($quantity);
            
            // process additional action if existing article has a valid applSpecDataObj
            if (is_object($this->itemsArr[$itemId]->get_applSpecDataObj())) {
                $this->itemsArr[$itemId]->get_applSpecDataObj()->processOnUpdateItemQuantity($quantity);
            }
        }
        
    }
    
    /**
     * Returns the total price sum of all pieces of one item within the article collection
     *
     * @param   integer     ID of the item (=ID of the article/Array-Key in $items-Array) 
     * @param   boolean     flag wether the sum should be returned as net sum (optional): 0 returns gross sum, 1 returns net sum (default)
     * @return  double      total sum of all article collection pieces of the specified item
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-27
     */
    public function getItemSubtotal($itemId, $getNet=1) { 
        
        $itemSubTotal = $this->itemsArr[$itemId]->getItemSubtotal($getNet);
        
        trace($itemSubTotal, 0, '$itemSubTotal (ITEM ID: '.$itemId.')');
        return $itemSubTotal;
        
    }
    
    /**
     * Returns the total tax cost sum of one item within the article collection
     *
     * @param   integer     ID of the item (=ID of the article/Array-Key in $items-Array) 
     * @param   boolean     (optional) flag wether the order is tax free (default:0)
     * @return  double      total tax cost sum of all article collection pieces of the specified item
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-08-01
     */
    public function getItemTaxSubtotal($itemId, $isTaxFreeOrder=0) {
        
        $itemTaxSubtotal = $this->itemsArr[$itemId]->getItemTaxSubtotal($isTaxFreeOrder);
        
        trace($itemTaxSubtotal, 0, '$itemTaxSubtotal (ITEM ID: '.$itemId.')');
        return $itemTaxSubtotal;
        
    }
    
    /**
     * Returns the total price sum of all items found in the article collection
     *
     * @param   boolean     flag wether the sum should be returned as net sum (optional): 0 returns gross sum, 1 returns net sum (default) 
     * @return  double      total sum of all article collection items
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-28
     */
    public function getItemsTotal($getNet=1) {
        
        $itemsTotal = 0;
        foreach ($this->itemsArr as $key=>$artObj) {
            $itemsTotal += $this->getItemSubtotal($key, $getNet);
        }
         
        trace($itemsTotal, 0, '$itemsTotal');
        return $itemsTotal;
        
    }
    
    /**
     * Returns the total tax cost sum of all items found in the article collection
     *
     * @param   boolean     (optional) flag wether the order is tax free (default:0)
     * @return  double      total tax cost sum of all article collection items
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-08-01
     */
    public function getItemsTaxTotal($isTaxFreeOrder=0) {
        
        $itemsTaxTotal = 0;
        foreach ($this->itemsArr as $key=>$artObj) {
            // float operations may lead to precision problems (see www.php.net/float), using bcmath instead: this requires PHP to be configured with  '--enable-bcmath'
            $itemsTaxTotal = bcadd($itemsTaxTotal, $this->getItemTaxSubtotal($key, $isTaxFreeOrder), 4);
                 // original calculation: $itemsTaxTotal += $this->getItemTaxSubtotal($key, $isTaxFreeOrder);
        }
         
        trace((double)$itemsTaxTotal, 0, '$itemsTaxTotal');
        return (double)$itemsTaxTotal;
        
    }
    
    /**
     * Returns an array of tax subtotals of all article collection items (excl. dispatch cost tax), seperated by different taxcodes
     *
     * @param   boolean     (optional) flag wether the order is tax free (default:0)
     * @return  array       tax subtotals of all article collection items: array( [string]taxcode => [double]tax subtotal of all article collection items with this taxcode )
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-08-04
     */
    public function getItemsTaxTotalArray($isTaxFreeOrder=0) {
        
        $itemsTaxTotalArray = array();
        
        foreach ($this->itemsArr as $key=>$artObj) {
            // initialize array indexes for strict parsing
            if (!isset($itemsTaxTotalArray[$artObj->get_taxCodeInland()])) {
                   $itemsTaxTotalArray[$artObj->get_taxCodeInland()] = 0;
            }
            // float operations may lead to precision problems (see www.php.net/float), using bcmath instead: this requires PHP to be configured with  '--enable-bcmath'
            $itemsTaxTotalArray[$artObj->get_taxCodeInland()] = bcadd($itemsTaxTotalArray[$artObj->get_taxCodeInland()], $this->getItemTaxSubtotal($key, $isTaxFreeOrder), 4);
                 // original calculation: $itemsTaxTotalArray[$artObj->get_taxCodeInland()] += $this->getItemTaxSubtotal($key, $isTaxFreeOrder);
        }
        
        foreach ($itemsTaxTotalArray as $key=>$taxCodeTotal) {
            $itemsTaxTotalArray[$key] = (double)$taxCodeTotal;
        }
         
        trace($itemsTaxTotalArray, 0, '$itemsTaxTotalArray');
        return $itemsTaxTotalArray;
        
    }
    
    /**
     * Returns a flag whether the article collection contains at least one physical article
     *
     * @param   void
     * @return  boolean    TRUE if the article collection contains at least one physical article, FALSE otherwise
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-11-01
     */
    public function getIsPhysical() {
        
        $isPhysical = false;
        
        foreach ($this->itemsArr as $artObj) {
            if ($artObj->get_isPhysical() == 1) {
                $isPhysical = true;
                break;
            }
        }
        
        return $isPhysical;
        
    }
    
    
    
} // end class




/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_articleCollection.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_articleCollection.php']);
}

?>