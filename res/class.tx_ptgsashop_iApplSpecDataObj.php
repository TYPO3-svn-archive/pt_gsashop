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
 * Application specific data object interface
 * This interface has to be implemented by all "application specific data" classes used by the 'pt_gsashop' extension.
 *
 * $Id: class.tx_ptgsashop_iApplSpecDataObj.php,v 1.4 2007/07/27 10:57:51 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2007-01-09
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Application specific data object interface: This interface has to be implemented by all "application specific data" classes used by the 'pt_gsashop' extension.
 *
 * @author      Rainer Kuhn <kuhn@punkt.de>
 * @since       2007-01-09
 * @package     TYPO3
 * @subpackage  tx_ptgsashop
 */
interface tx_ptgsashop_iApplSpecDataObj {
    
    /**
     * Returns the application specific data from the inheriting object as a string representation
     *
     * @param   void
     * @return  string      application specific data as a string representation
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-01-08
     */
     public function getDataAsString();
     
    /**
     * Sets the application specific data in the inheriting object from a given string representation
     *
     * @param   string      string representation of the application specific data to set
     * @return  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-01-08
     */
     public function setDataFromString($applSpecDataString);
    
    
    
} // end class



?>