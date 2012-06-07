<?php  // $Id: export_activite.php,v 1.1 2010/12/17 21:49:31 vf Exp $
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 2005 Martin Dougiamas  http://dougiamas.com             //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
* D'apres quiz/export.php 
* Export instance referentiel + activite
*
* @version $Id: export_activite.php,v 1.1 2010/12/17 21:49:31 vf Exp $
* @author Martin Dougiamas, Howard Miller, and many others.
*         {@link http://moodle.org}
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package referentiel
*/

    require_once('../../config.php');
    require_once('lib.php');
    // require_once('pagelib.php'); // ENTETES
    require_once('print_lib_activite.php');	// AFFICHAGES 
    require_once('import_export_lib.php');	// IMPORT / EXPORT	

    $exportfilename = optional_param('exportfilename','',PARAM_FILE );
    $format = optional_param('format','', PARAM_FILE );
	
    $id    = optional_param('id', 0, PARAM_INT);    // course module id    
	$d     = optional_param('d', 0, PARAM_INT);    // referentielbase id
	
    $activite_id   = optional_param('activite_id', 0, PARAM_INT);    //record activite id

    $mode           = optional_param('mode','', PARAM_ALPHA);	
    $add           = optional_param('add','', PARAM_ALPHA);
    $update        = optional_param('update', 0, PARAM_INT);
    $delete        = optional_param('delete', 0, PARAM_INT);
    $approve        = optional_param('approve', 0, PARAM_INT);	
    $comment        = optional_param('comment', 0, PARAM_INT);		
    $course        = optional_param('course', 0, PARAM_INT);
    $groupmode     = optional_param('groupmode', -1, PARAM_INT);
    $cancel        = optional_param('cancel', 0, PARAM_BOOL);
	
	if ($d) {
        if (! $referentiel = get_record('referentiel', 'id', $d)) {
            error('Referentiel instance is incorrect');
        }
        if (! $referentiel_referentiel = get_record('referentiel_referentiel', 'id', $referentiel->referentielid)) {
            error('Réferentiel id is incorrect');
        }
        
		if (! $course = get_record('course', 'id', $referentiel->course)) {
	            error('Course is misconfigured');
    	}
        	
		if (! $cm = get_coursemodule_from_instance('referentiel', $referentiel->id, $course->id)) {
    	        error('Course Module ID is incorrect');
		}
	} 
	elseif ($id) {
        if (! $cm = get_coursemodule_from_id('referentiel', $id)) {
        	error('Course Module ID was incorrect');
        }
        if (! $course = get_record('course', 'id', $cm->course)) {
            error('Course is misconfigured');
        }
        if (! $referentiel = get_record('referentiel', 'id', $cm->instance)) {
            error('Referentiel instance is incorrect');
        }
        if (! $referentiel_referentiel = get_record('referentiel_referentiel', 'id', $referentiel->referentielid)) {
            error('Referentiel is incorrect');
        }
    } 
	else{
        // error('You cannot call this script in that way');	
		error(get_string('erreurscript','referentiel','Erreur01 : export_activite.php'));
	}
	
	if ($activite_id) { // id activite
        if (! $record = get_record('referentiel_activite', 'id', $activite_id)) {
            error('Activite ID is incorrect');
        }
	}


    // get display strings
    $txt = new object;
    $txt->referentiel = get_string('referentiel','referentiel');
    $txt->download = get_string('download','referentiel');
    $txt->downloadextra = get_string('downloadextra','referentiel');
    $txt->exporterror = get_string('exporterror','referentiel');
    $txt->exportname = get_string('exportname','referentiel');
    $txt->exportactivite = get_string('exportactivite', 'referentiel');
    $txt->fileformat = get_string('fileformat','referentiel');
    $txt->modulename = get_string('modulename','referentiel');
    $txt->modulenameplural = get_string('modulenameplural','referentiel');
    $txt->tofile = get_string('tofile','referentiel');
	
	// PAS DE RSS
    // require_once("$CFG->libdir/rsslib.php");

	
    require_login($course->id, false, $cm);

    if (!isloggedin() or isguest()) {
        redirect('view.php?id='.$cm->id);
    }
    // check role capability
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    require_capability('mod/referentiel:export', $context);

    // ensure the files area exists for this course
    make_upload_directory( "$course->id/$CFG->moddata/referentiel" );


    if ($activite_id) {    // So do you have access?
        if (!(has_capability('mod/referentiel:writereferentiel', $context) 
			or referentiel_activite_isowner($activite_id)) or !confirm_sesskey() ) {
            error(get_string('noaccess','referentiel'));
        }
    }
	
	
	// RECUPERER LES FORMULAIRES
    if (isset($SESSION->modform)) {   // Variables are stored in the session
        $form = $SESSION->modform;
        unset($SESSION->modform);
    } else {
        $form = (object)$_POST;
    }

	if ($cancel) {
        if (!empty($SESSION->returnpage)) {
            $return = $SESSION->returnpage;
            unset($SESSION->returnpage);
            redirect($return);
        } else {
            redirect('activite.php?d='.$referentiel->id);
        }
    }
 

	/// Check to see if groups are being used here
		
	$groupmode = groupmode($course, $cm);
	$currentgroup = setup_and_print_groups($course, $groupmode, 'export.php?d='.$referentiel->id);
		
    $defaultformat = FORMAT_MOODLE;
        
		
	/// RSS and CSS and JS meta
    $meta = '';

	/// Print the page header
	$strreferentiels = get_string('modulenameplural','referentiel');
	$strreferentiel = get_string('referentiel','referentiel');
	$strmessage = get_string('exportactivite','referentiel');		
	$icon = '<img class="icon" src="'.$CFG->wwwroot.'/mod/referentiel/icon.gif" alt="'.get_string('modulename','referentiel').'"/>';
	
	$strpagename=get_string('exportactivite','referentiel');
	if (function_exists('build_navigation')){
		// Moodle 1.9
		$navigation = build_navigation($strpagename, $cm);
		
		print_header($course->shortname.': '.$strreferentiel, $course->fullname, $navigation, 
		'', // focus
		$meta,
		true, // page is cacheable
//		update_module_button($cm->id, $course->id, get_string('modulename', 'referentiel')), // HTML code for a button (usually for module editing)
		update_module_button($cm->id, $course->id, get_string('modulename-intance', 'referentiel')), // HTML code for a button (usually for module editing)

        navmenu($course, $cm), // HTML code for a popup menu
		false, // use XML for this page
		'', // This text will be included verbatim in the <body> tag (useful for onload() etc)
		false); // If true, return the visible elements of the header instead of echoing them.
	}
	else{
		// moodle 1.8
		print_header_simple($referentiel->name, // title
		'', // heading
		"<a href='index.php?id=$course->id'>$strreferentiel</a> -> $referentiel->name", // navigation
		'', // focus
		$meta, // meta tag
		true, // page is cacheable
//		update_module_button($cm->id, $course->id, get_string('modulename', 'referentiel')), // HTML code for a button (usually for module editing)
		update_module_button($cm->id, $course->id, get_string('modulename-intance', 'referentiel')), // HTML code for a button (usually for module editing)

        navmenu($course, $cm), // HTML code for a popup menu
		false, // use XML for this page
		'', // This text will be included verbatim in the <body> tag (useful for onload() etc)
		false) // If true, return the visible elements of the header instead of echoing them.
		;
	}
	
	print_heading(format_string($referentiel->name));
	/// Print the tabs
	if (!isset($mode)){
		$mode='exportactivity'; // un seul mode possible
	}
	$currenttab = 'exportactivity';

	include('tabs.php');

    print_heading_with_help($strmessage, 'exportactivite', 'referentiel', $icon);
	
    if (!empty($format) && !empty($referentiel) && !empty($course)) {   
		/// Filename et format d'exportation

        if (!confirm_sesskey()) {
            print_error( 'sesskey' );
        }

        if (! is_readable("format/$format/format.php")) {
            error( "Format not known ($format)" );  }

        // load parent class for import/export
        require("format.php");

        // and then the class for the selected format
        require("format/$format/format.php");

        $classname = "aformat_$format";
        $aformat = new $classname();

        // $aformat->setCategory( $category );
		$aformat->setCoursemodule( $cm );
        $aformat->setCourse( $course );
        $aformat->setFilename( $exportfilename );
        $aformat->setIReferentiel($referentiel);
		$aformat->setRefInstance( $referentiel->id);		
		$aformat->setRReferentiel($referentiel_referentiel);
		$aformat->setRefReferentiel( $referentiel->referentielid);

        if (! $aformat->exportpreprocess()) {   // Do anything before that we need to
            error( $txt->exporterror, $CFG->wwwroot.'/mod/referentiel/export_activite.php?id='.$cm->id);
        }

        if (! $aformat->exportprocess()) {         // Process the export data
            error( $txt->exporterror, $CFG->wwwroot.'/mod/referentiel/export_activite.php?id='.$cm->id);
        }

        if (! $aformat->exportpostprocess()) {                    // In case anything needs to be done after
            error( $txt->exporterror, $CFG->wwwroot.'/mod/referentiel/export_activite.php?d='.$cm->id);
        }
        echo "<hr />";

        // link to download the finished file
        $file_ext = $aformat->export_file_extension();
        if ($CFG->slasharguments) {
          $efile = "{$CFG->wwwroot}/file.php/".$aformat->get_export_dir()."/$exportfilename".$file_ext."?forcedownload=1";
        }
        else {
          $efile = "{$CFG->wwwroot}/file.php?file=/".$aformat->get_export_dir()."/$exportfilename".$file_ext."&forcedownload=1";
        }
        echo "<p><div class=\"boxaligncenter\"><a href=\"$efile\">$txt->download</a></div></p>";
        echo "<p><div class=\"boxaligncenter\"><font size=\"-1\">$txt->downloadextra</font></div></p>";

        print_continue($CFG->wwwroot.'/mod/referentiel/activite.php?id='.$cm->id);
        print_footer($course);
        exit;
    }

    /// Display upload form

    // get valid formats to generate dropdown list
    $fileformatnames = referentiel_get_import_export_formats( 'export', 'aformat' );

    // get filename
    if (empty($exportfilename)) {
        $exportfilename = referentiel_default_export_filename($course, $referentiel, 'activite');
    }

    // print_heading_with_help($txt->exportreferentiel, 'export', 'referentiel');
    print_box_start('generalbox boxwidthnormal boxaligncenter');
?>

    <form enctype="multipart/form-data" method="post" action="export_activite.php?id=<?php echo $cm->id; ?>">
        <fieldset class="invisiblefieldset" style="display: block;">
            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>" />

            <table cellpadding="5">
                <tr>
                    <td><?php echo $txt->fileformat; ?>:</td>
                    <td>
                        <?php choose_from_menu($fileformatnames, 'format', 'xml', '');
                        helpbutton('format', $txt->referentiel, 'referentiel'); ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2"><?php echo $txt->exportname; ?>:</td>
                </tr>
                <tr>					
                    <td colspan="2">
                        <input type="text" size="60" name="exportfilename" value="<?php echo $exportfilename; ?>" />
                    </td>
                </tr>
                <tr>
                    <td align="center" colspan="2">
                        <input type="submit" name="save" value="<?php echo $txt->exportactivite; ?>" />
                    </td>
                </tr>
            </table>
        </fieldset>
    </form>
    <?php

    print_box_end();
    print_footer($course);
?>
