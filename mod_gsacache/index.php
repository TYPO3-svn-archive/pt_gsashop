<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Rainer Kuhn <kuhn@punkt.de>
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
 * index.php for the backend sub module 'GSA Cache' of the 'pt_gsashop' extension
 *
 * $Id: index.php,v 1.1 2008/02/06 17:53:17 ry37 Exp $
 *
 * @author  Rainer Kuhn <kuhn@punkt.de>
 * @since   2008-02-06
 */ 



/**
 * Default module initialization (according to TYPO3 API in 'EXAMPLE PROTOTYPE' in t3lib_SCbase)
 */
unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:pt_gsashop/mod_gsacache/locallang.xml');
require_once(PATH_t3lib.'class.t3lib_scbase.php'); // parent class for 'ScriptClasses' in backend modules
$BE_USER->modAccess($MCONF, 1); // this checks permissions and exits if the users has no permission for entry



/**
 * Module class inclusion
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'mod_gsacache/class.tx_ptgsashop_module1.php';



/**
 * Default module finalization (according to TYPO3 API in 'EXAMPLE PROTOTYPE' in t3lib_SCbase)
 */
// make instance of the backend module script class and initialize it
$SOBE = t3lib_div::makeInstance('tx_ptgsashop_module1');
$SOBE->init();
// check for include files (after init() the internal array $SOBE->include_once may hold filenames to include)
foreach($SOBE->include_once as $INC_FILE) {
    include_once($INC_FILE);
}
// call main() method (this should spark the creation of the module output) and output the accumulated content
$SOBE->main();
$SOBE->printContent();

?>