<?php  // $Id: export_certificat.php,v 1.1 2010/12/17 21:49:31 vf Exp $
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
* Export instance referentiel + certificat
*
* @version $Id: export_certificat.php,v 1.1 2010/12/17 21:49:31 vf Exp $
* @author Martin Dougiamas, Howard Miller, and many others.
*         {@link http://moodle.org}
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package referentiel
*/

    require_once('../../config.php');
    require_once('lib.php');
    require_once('lib_etab.php');
    // require_once('pagelib.php'); // ENTETES
    require_once('print_lib_certificat.php');	// AFFICHAGES 
    require_once('import_export_lib.php');	// IMPORT / EXPORT	

    $exportfilename = optional_param('exportfilename','',PARAM_FILE );
    $format = optional_param('format','', PARAM_FILE );
	
    $id    = optional_param('id', 0, PARAM_INT);    // course module id    
	$d     = optional_param('d', 0, PARAM_INT);    // referentielbase id
	
    $certificat_id   = optional_param('certificat_id', 0, PARAM_INT);    //record certificat id

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
		error(get_string('erreurscript','referentiel','Erreur01 : export_certificat.php'));
	}
	
	if ($certificat_id) { // id certificat
        if (! $record = get_record('referentiel_certificat', 'id', $certificat_id)) {
            error('certificat ID is incorrect');
        }
	}


    // get display strings
    $txt = new object;
    $txt->referentiel = get_string('referentiel','referentiel');
    $txt->download = get_string('download','referentiel');
    $txt->downloadextra = get_string('downloadextra','referentiel');
    $txt->exporterror = get_string('exporterror','referentiel');
    $txt->exportname = get_string('exportname','referentiel');
    $txt->exportcertificat = get_string('exportcertificat', 'referentiel');
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

    if ($certificat_id) {    // So do you have access?
        if (!(has_capability('mod/referentiel:writereferentiel', $context) 
			or referentiel_certificat_isowner($certificat_id)) or !confirm_sesskey() ) {
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
            redirect('certificat.php?d='.$referentiel->id);
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
	$strmessage = get_string('exportcertificat','referentiel');		
	$icon = '<img class="icon" src="'.$CFG->wwwroot.'/mod/referentiel/icon.gif" alt="'.get_string('modulename','referentiel').'"/>';
	
	$strpagename=get_string('exportcertificat','referentiel');
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
		// Moodle 1.8
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
		$mode='managecertif'; // un seul mode possible
	}
	$currenttab = 'managecertif';

	include('tabs.php');

    print_heading_with_help($strmessage, 'exportcertificat', 'referentiel', $icon);
	
	
    if (!empty($format) && !empty($referentiel) && !empty($course)) {   
		/// Filename et format d'exportation
// DEBUG 
//echo "<br /> OK 1\n";
        if (!confirm_sesskey()) {
            print_error( 'sesskey' );
        }
// DEBUG 
//echo "<br /> OK 2\n";
        if (! is_readable("format/$format/format.php")) {
            error( "Format not known ($format)" );  
		}
// DEBUG 
//echo "<br /> OK 3\n";
        // load parent class for import/export
        require("format.php");
// DEBUG 
//echo "<br /> OK 4\n";
        // and then the class for the selected format
        require("format/$format/format.php");
// DEBUG 
//echo "<br /> OK 5\n";
        $classname = "cformat_$format";
        $cformat = new $classname();
// DEBUG 
// echo "<br /> OK 6\n";
        // $cformat->setCategory( $category );
		$cformat->setCoursemodule( $cm );
        $cformat->setCourse( $course );
        $cformat->setFilename( $exportfilename );
        $cformat->setRReferentiel( $referentiel);
		$cformat->setRefReferentiel( $referentiel->referentielid);
// DEBUG 
// echo "<br /> OK 7\n";

        if (! $cformat->exportpreprocess()) {   // Do anything before that we need to
            error( $txt->exporterror, $CFG->wwwroot.'/mod/referentiel/export_certificat.php?id='.$cm->id);
        }
// echo "<br /> OK 8\n";
        if (! $cformat->exportprocess()) {         // Process the export data
            error( $txt->exporterror, $CFG->wwwroot.'/mod/referentiel/export_certificat.php?id='.$cm->id);
        }
// echo "<br /> OK 9\n";
        if (! $cformat->exportpostprocess()) {                    // In case anything needs to be done after
            error( $txt->exporterror, $CFG->wwwroot.'/mod/referentiel/export_certificat.php?d='.$cm->id);
        }
        echo "<hr />";
// echo "<br /> OK 10\n";

        // link to download the finished file
        $file_ext = $cformat->export_file_extension();
        if ($CFG->slasharguments) {
          $efile = "{$CFG->wwwroot}/file.php/".$cformat->get_export_dir()."/$exportfilename".$file_ext."?forcedownload=1";
        }
        else {
          $efile = "{$CFG->wwwroot}/file.php?file=/".$cformat->get_export_dir()."/$exportfilename".$file_ext."&forcedownload=1";
        }
        echo "<p><div class=\"boxaligncenter\"><a href=\"$efile\">$txt->download</a></div></p>";
        echo "<p><div class=\"boxaligncenter\"><font size=\"-1\">$txt->downloadextra</font></div></p>";

        print_continue($CFG->wwwroot.'/mod/referentiel/certificat.php?id='.$cm->id);
        print_footer($course);
        exit;
    }

    /// Display upload form

    // get valid formats to generate dropdown list
    $fileformatnames = referentiel_get_import_export_formats( 'export', 'cformat' );

    // get filename
    if (empty($exportfilename)) {
        $exportfilename = referentiel_default_export_filename($course, $referentiel, 'certificat');
    }

    // print_heading_with_help($txt->exportreferentiel, 'export', 'referentiel');
    print_box_start('generalbox boxwidthnormal boxaligncenter');
?>

    <form enctype="multipart/form-data" method="post" action="export_certificat.php?id=<?php echo $cm->id; ?>">
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
                        <input type="submit" name="save" value="<?php echo $txt->exportcertificat; ?>" />
                    </td>
                </tr>
            </table>
        </fieldset>
    </form>
    <?php

    print_box_end();
    print_footer($course);
?>
