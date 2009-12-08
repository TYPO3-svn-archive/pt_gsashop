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
 * Article factory class for the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsashop_articleFactory.php,v 1.5 2007/10/15 13:03:25 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2007-05-04
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
 * Article factory class
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2007-05-04
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
class tx_ptgsashop_articleFactory {
     
    /**
     * Static factory method: Creates and returns and article object from a given UID. The article type can be individually changed using the internal hook, e.g. based on the article's $applSpecDataObj property.
     * 
     * @param   integer     Database UID of the article to create, see tx_ptgsashop_baseArticle::__construct()
     * @param   integer     (optional) see tx_ptgsashop_baseArticle::__construct()
     * @param   integer     (optional) see tx_ptgsashop_baseArticle::__construct()
     * @param   integer     (optional) see tx_ptgsashop_baseArticle::__construct()
     * @param   string      (optional) see tx_ptgsashop_baseArticle::__construct()
     * @param   boolean     (optional) see tx_ptgsashop_baseArticle::__construct()
     * @return  tx_ptgsashop_baseArticle      article object of type tx_ptgsashop_baseArticle respective its appopriate subclass
     * @throws  tx_pttools_exception   if the internal article type is not supported
     * @see     tx_ptgsashop_baseArticle::__construct()
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-05-04
     */
    static public function createArticle($id, $priceCategory=1, $customerId=0, $quantity=1, $date='', $imageFlag=0) {
        
        $articleObj = NULL;                     // (object) article object to return: tx_ptgsashop_baseArticle respective its appopriate subclass
        $articleType = 'tx_ptgsashop_article';  // (string) class name of the article object to return
        
        // HOOK: allow multiple hooks to change the required article type, e.g. based on the article's $applSpecDataObj property
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['articleFactory_hooks']['createArticleHook'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsashop']['articleFactory_hooks']['createArticleHook'] as $className) {
                $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                $articleType = $hookObj->createArticleHook($id);
            }
        }
        
        switch ($articleType) {
            case 'tx_ptgsashop_article':
                require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_article.php';
                $articleObj =  new tx_ptgsashop_article($id, $priceCategory, $customerId, $quantity, $date, $imageFlag);
                break;
            case 'tx_ptgsashop_compoundArticle':
                require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_compoundArticle.php';
                $articleObj =  new tx_ptgsashop_compoundArticle($id, $priceCategory, $customerId, $quantity, $date, $imageFlag);
                break;
            case 'tx_ptgsashop_componentArticle':
                require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_componentArticle.php';
                $articleObj =  new tx_ptgsashop_componentArticle($id, $priceCategory, $customerId, $quantity, $date, $imageFlag);
                break;
            default:
                throw new tx_pttools_exception('Wrong article type in article factory', 3, 
                                               'Article type '.$articleType.' not supported in '.__METHOD__);
                break;
        }
        
        return $articleObj;
        
    }
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_articleFactory.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsashop/res/class.tx_ptgsashop_articleFactory.php']);
}

?>