<?php // $Id: version.php,v 1.3 2006/08/28 16:41:20 mark-nielsen Exp $
/**
 * Code fragment to define the version of newmodule
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @author 
 * @version $Id: version.php,v 1.3 2006/08/28 16:41:20 mark-nielsen Exp $
 * @package newmodule
 **/

$module->version  = 2010111100;  // The current module version (Date: YYYYMMDDXX)
$module->release  =  '5.4.6 - 2010/11/29';    // User-friendly version number - date of release
$module->cron     =  60; //  Period for cron to check this module (secs)

?>
