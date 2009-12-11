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
 * Online article class for the 'pt_gsashop' extension (properties and methods moved to abstract parent class tx_ptgsashop_baseArticle 2007-01-04)
 *
 * $Id: class.tx_ptgsashop_article.php,v 1.57 2008/01/25 12:30:36 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2005-07-19 / 2007-01-04: properties and methods moved to abstract parent class tx_ptgsashop_baseArticle
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_baseArticle.php';  // GSA Shop abstract base class for articles

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general helper library class



/**
 * Online article class (default articles, available for online shop orders)
 * 
 * Properties and methods moved to abstract parent class tx_ptgsashop_baseArticle 2007-01-04)
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2005-07-19 / 2007-01-04: properties and methods moved to abstract parent class tx_ptgsashop_baseArticle
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_article extends tx_ptgsashop_baseArticle {
    
    /***************************************************************************
     *   CONSTRUCTOR
     **************************************************************************/
     
    /**
     * Class constructor: calls the parent constructor and checks article validity (only articles marked as online articles are allowed!)
     *
     * @param   integer     UID of the article (positive integer; only articles marked as online articles are allowed here!); use 0 to create a new/empty article. See tx_ptgsashop_baseArticle::__construct()
     * @param   integer     (optional) see tx_ptgsashop_baseArticle::__construct()
     * @param   integer     (optional) see tx_ptgsashop_baseArticle::__construct()
     * @param   integer     (optional) see tx_ptgsashop_baseArticle::__construct()
     * @param   string      (optional) see tx_ptgsashop_baseArticle::__construct()
     * @param   boolean     (optional) see tx_ptgsashop_baseArticle::__construct()
     * @return  void
     * @throws  tx_pttools_exception   if the article is not a valid online article
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-07-19 / 2007-01-04
     */
    public function __construct($id, $priceCategory=1, $customerId=0, $quantity=1, $date='', $imageFlag=0) {
    
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        parent::__construct($id, $priceCategory, $customerId, $quantity, $date, $imageFlag);
        
        // set online article flag to true for new/empty articles
        if ($id == 0) {
            $this->set_isOnlineArticle(1);
        }
         
        if ($this->get_isOnlineArticle() == 0) {
            throw new tx_pttools_exception('Wrong article type', 3, 
                                           'Article with database UID='.$id.' cannot be instantiated as '.__CLASS__.' (article is not marked as online article)');
        }
        
    }
    


    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
     
     
     
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_article.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_article.php']);
}

?>