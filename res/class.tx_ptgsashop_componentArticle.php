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
 * Component article class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_componentArticle.php,v 1.6 2007/10/15 13:03:25 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2007-01-04
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
 * Component article class (non-online articles, not available for direct order) 
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2007-01-04
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_componentArticle extends tx_ptgsashop_baseArticle {
    
    /**
     * Additional Properties
     */
    protected $applSpecComponentDataObj;  // (object implementing interface tx_ptgsashop_iApplSpecArticleDataObj) application specific article data object for the component article
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR
     **************************************************************************/
     
    /**
     * Class constructor: calls the parent constructor and checks article validity (only articles _not_ marked as online articles are allowed!)
     *
     * @param   integer     UID of the article (only articles _not_ marked as online articles are allowed!), see tx_ptgsashop_baseArticle::__construct()
     * @param   integer     (optional) see tx_ptgsashop_baseArticle::__construct()
     * @param   integer     (optional) see tx_ptgsashop_baseArticle::__construct()
     * @param   integer     (optional) see tx_ptgsashop_baseArticle::__construct()
     * @param   string      (optional) see tx_ptgsashop_baseArticle::__construct()
     * @param   boolean     (optional) see tx_ptgsashop_baseArticle::__construct()
     * @return  void
     * @throws  tx_pttools_exception   if the article is not a valid component article
     * @see     tx_ptgsashop_baseArticle::__construct()
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-01-04
     */
    public function __construct($id, $priceCategory=1, $customerId=0, $quantity=1, $date='', $imageFlag=0) {
    
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        parent::__construct($id, $priceCategory, $customerId, $quantity, $date, $imageFlag);
        
        // TODO: optional - this condition may be removed in further development
        if ($this->isOnlineArticle == 1) {
            throw new tx_pttools_exception('Wrong article type', 3, 
                                           'Article with database UID='.$id.' cannot be instantiated as '.__CLASS__.' (article _is_ marked as online article)');
        }
        
    }
    
    
    
    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
    
    /**
     * Returns the application specific article data object of the component article
     *
     * @param   void
     * @return  tx_ptgsashop_iApplSpecArticleDataObj      application specific article data object for the component article, object implementing interface tx_ptgsashop_iApplSpecArticleDataObj
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-01-09
     */
    public function get_applSpecComponentDataObj() {
        
        return $this->applSpecComponentDataObj;
        
    }
    
    /**
     * Sets the application specific article data object of the component article
     *
     * @param   tx_ptgsashop_iApplSpecArticleDataObj      application specific article data object for the component article, object implementing interface tx_ptgsashop_iApplSpecArticleDataObj
     * @return  void
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-01-09
     */
    public function set_applSpecComponentDataObj(tx_ptgsashop_iApplSpecArticleDataObj $applSpecComponentDataObj) {
        
        $this->applSpecComponentDataObj = $applSpecComponentDataObj;
        
    }
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_componentArticle.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_componentArticle.php']);
}

?>