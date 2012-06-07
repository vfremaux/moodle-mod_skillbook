<?php  // $Id: export_certificate.php,v 1.0 2008/04/29/ 00:00:00 jfruitet Exp $
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// GPL
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
* @version $Id: export_certificate.php,v 1.0 2008/04/29/ 00:00:00 jfruitet Exp $
* @author Martin Dougiamas, Howard Miller, and many others.
*         {@link http://moodle.org}
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package referentiel
*/

  require_once('../../config.php');
  require_once('lib.php');
  require_once('lib_etab.php');
  // require_once('pagelib.php'); // ENTETES
  include('lib_accompagnement.php');
  include('lib_certificate.php');	
  include('print_lib_certificate.php');	// AFFICHAGES 
  include('import_export_lib.php');	// IMPORT / EXPORT	

  $exportfilename = optional_param('exportfilename','',PARAM_FILE );
  $format = optional_param('format','', PARAM_FILE );
	
  $id    = optional_param('id', 0, PARAM_INT);    // course module id    
  $d     = optional_param('d', 0, PARAM_INT);    // referentielbase id
	
  $certificate_id   = optional_param('certificate_id', 0, PARAM_INT);    //record certificate id

  $mode           = optional_param('mode','', PARAM_ALPHA);	
  $add           = optional_param('add','', PARAM_ALPHA);
  $update        = optional_param('update', 0, PARAM_INT);
  $delete        = optional_param('delete', 0, PARAM_INT);
  $approve        = optional_param('approve', 0, PARAM_INT);	
  $comment        = optional_param('comment', 0, PARAM_INT);		
  $course        = optional_param('course', 0, PARAM_INT);
  $groupmode     = optional_param('groupmode', -1, PARAM_INT);
  $cancel        = optional_param('cancel', 0, PARAM_BOOL);
  $userid = optional_param('userid', 0, PARAM_INT);
  $select_acc = optional_param('select_acc', 0, PARAM_INT);      // coaching
  $select_all = optional_param('select_all', 0, PARAM_INT);      // tous les certificats
  $format_condense = optional_param('format_condense', 0, PARAM_INT);  // format compact
	
	if ($d) {
        if (! $referentiel = get_record('referentiel', 'id', $d)) {
            print_error('Referentiel instance is incorrect');
        }
        if (! $referentiel_referentiel = get_record('referentiel_referentiel', 'id', $referentiel->referentielid)) {
            print_error('Réferentiel id is incorrect');
        }
        
		if (! $course = get_record('course', 'id', $referentiel->course)) {
	            print_error('Course is misconfigured');
    	}
        	
		if (! $cm = get_coursemodule_from_instance('referentiel', $referentiel->id, $course->id)) {
    	        print_error('Course Module ID is incorrect');
		}
	} 
	elseif ($id) {
        if (! $cm = get_coursemodule_from_id('referentiel', $id)) {
        	print_error('Course Module ID was incorrect');
        }
        if (! $course = get_record('course', 'id', $cm->course)) {
            print_error('Course is misconfigured');
        }
        if (! $referentiel = get_record('referentiel', 'id', $cm->instance)) {
            print_error('Referentiel instance is incorrect');
        }
        if (! $referentiel_referentiel = get_record('referentiel_referentiel', 'id', $referentiel->referentielid)) {
            print_error('Referentiel is incorrect');
        }
    } 
	else{
        // print_error('You cannot call this script in that way');	
		error(get_string('erreurscript','referentiel','Erreur01 : export_certificate.php'));
	}
	
	if ($certificate_id) { // id certificat
        if (! $record = get_record('referentiel_certificate', 'id', $certificate_id)) {
            print_error('certificate ID is incorrect');
        }
	}


    // get display strings
    $txt = new object;
    $txt->condense = get_string('format_condense','referentiel');
    $txt->referentiel = get_string('referentiel','referentiel');
    $txt->download = get_string('download','referentiel');
    $txt->downloadextra = get_string('downloadextra','referentiel');
    $txt->exporterror = get_string('exporterror','referentiel');
    $txt->exportname = get_string('exportname','referentiel');
    $txt->exportcertificate = get_string('exportcertificat', 'referentiel');
    $txt->fileformat = get_string('fileformat','referentiel');
    $txt->modulename = get_string('modulename','referentiel');
    $txt->modulenameplural = get_string('modulenameplural','referentiel');
    $txt->tofile = get_string('tofile','referentiel');
	
	// PAS DE RSS
    // require_once("$CFG->libdir/rsslib.php");

	
    require_login($course->id, false, $cm);

    if (!isloggedin() or isguest()) {
        redirect('view.php?id='.$cm->id.'noredirect=1');
    }
    
    // check role capability
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    require_capability('mod/referentiel:export', $context);

    // ensure the files area exists for this course
    make_upload_directory( "$course->id/$CFG->moddata/referentiel" );

    if ($certificate_id) {    // So do you have access?
      if (!(has_capability('mod/referentiel:writereferentiel', $context) 
			or referentiel_certificate_isowner($certificate_id)) or !confirm_sesskey() ) {
            error(get_string('noaccess','referentiel'));
        }
    }
	
 
	// selecteur
	$userid_filtre=0;
    if (!empty($userid)) {
        $userid_filtre=$userid;
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
            redirect('certificate.php?d='.$referentiel->id);
        }
    }
 
    // tous les certificats ?
    /// selection utilisateurs accompagnés
    if (isset($action) && ($action=='select_acc')){
		  if (isset($form->select_acc) && confirm_sesskey() ){
		  	$select_acc=$form->select_acc;
		  }
		  if (isset($form->mode) && ($form->mode!='')){
			 $mode=$form->mode;
		  }
		  // echo "<br />ACTION : $action  SEARCH : $userid_filtre\n";
		  unset($form);
		  unset($action);
		  // exit;
    }

    /// selection utilisateurs
    if (isset($action) && ($action=='select_all_certificates')){
		  if (isset($form->select_all) && confirm_sesskey() ){
		  	$select_all=$form->select_all;
		  }
		  if (isset($form->mode) && ($form->mode!='')){
			 $mode=$form->mode;
		  }
		  // echo "<br />ACTION : $action  SEARCH : $userid_filtre\n";
		  unset($form);
		  unset($action);
		  // exit;
    }
    
    if (!isset($select_all)){
        if (isset($form->select_all)){
            $select_all=$form->select_all;
        }
        else{
            $select_all=0 ;
        }
    }

    // coaching
    if (!isset($select_acc)){
        if (isset($form->select_acc)){
            $select_acc=$form->select_acc;
        }
        else{
            $select_acc=0 ;
        }
    }
		
    $defaultformat = FORMAT_MOODLE;
        
		
	/// RSS and CSS and JS meta
    $meta = '<link rel="stylesheet" type="text/css" href="jauge.css" />';
	$meta .= '<link rel="stylesheet" type="text/css" href="activite.css" />';
    $meta .= '<link rel="stylesheet" type="text/css" href="certificate.css" />';


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
	
  		/// Check to see if groups are being used here
		/// find out current groups mode
   		$groupmode = groups_get_activity_groupmode($cm);
	    $currentgroup = groups_get_activity_group($cm, true);
    	groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/referentiel/export_certificate.php?d='.$referentiel->id.'&amp;mode='.$mode.'&amp;select_acc='.$select_acc);
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
		// 1.8
		$groupmode = groupmode($course, $cm);
        $currentgroup = setup_and_print_groups($course, $groupmode, $CFG->wwwroot . '/mod/referentiel/export_certificate.php?d='.$referentiel->id.'&amp;mode='.$mode.'&amp;select_acc='.$select_acc);
		
	}
		
	print_heading(format_string($referentiel->name));
	/// Print the tabs
	if (!isset($mode)){
		$mode='managecertif'; // un seul mode possible
	}
	$currenttab = 'managecertif';

	include('tabs.php');

	/// Get all users 
	$gusers=NULL;
    if ($gusers = get_users_by_capability($context, 'mod/referentiel:write', 'u.id', 'u.lastname', '', '', $currentgroup, '', false)) {
    	$gusers = array_keys($gusers);
    }
	// if groupmembersonly used, remove users who are not in any group
    if ($gusers and !empty($CFG->enablegroupings) and $cm->groupmembersonly) {
    	if ($groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id')) {
        	$gusers = array_intersect($gusers, array_keys($groupingusers));
      }
    }

    print_heading_with_help($strmessage, 'exportcertificat', 'referentiel', $icon);

	
    if (!empty($format) && !empty($referentiel) && !empty($course)) {
        $records_certificats=referentiel_get_liste_certificats($referentiel, $userid_filtre, $gusers, $select_acc, $mode, 'export_certificate.php', $select_all, false);

		/// Filename et format d'exportation
// DEBUG 
//echo "<br /> OK 1\n";
        if (!confirm_sesskey()) {
            print_error( 'sesskey' );
        }
// DEBUG 
//echo "<br /> OK 2\n";
        if (! is_readable("format/$format/format.php")) {
            print_error( "Format not known ($format)" );  
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
        $cformat->setIReferentiel( $referentiel);
        $cformat->setRReferentiel( $referentiel_referentiel);
        $cformat->setRefReferentiel( $referentiel->referentielid);
        $cformat->setRCertificats($records_certificats);
        // DEBUG
        if (isset($format_condense)){
            $cformat->setRCFormat($format_condense);
		}
		
        // DEBUG
        // echo "<br /> OK 7\n";

        if (! $cformat->exportpreprocess()) {   // Do anything before that we need to
              print_error( $txt->exporterror, $CFG->wwwroot.'/mod/referentiel/export_certificate.php?id='.$cm->id);
        }
        // echo "<br /> OK 8\n";
        if (! $cformat->exportprocess()) {         // Process the export data
            print_error( $txt->exporterror, $CFG->wwwroot.'/mod/referentiel/export_certificate.php?id='.$cm->id);
        }
        // echo "<br /> OK 9\n";
        if (! $cformat->exportpostprocess()) {                    // In case anything needs to be done after
            print_error( $txt->exporterror, $CFG->wwwroot.'/mod/referentiel/export_certificate.php?d='.$cm->id);
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

        print_continue($CFG->wwwroot.'/mod/referentiel/certificate.php?id='.$cm->id);
        print_footer($course);
        exit;
    }
    else{
        $records_certificats=referentiel_get_liste_certificats($referentiel, $userid_filtre, $gusers, $select_acc, $mode, 'export_certificate.php', $select_all, true);
    }

    /// Display upload form

    // get valid formats to generate dropdown list
    $fileformatnames = referentiel_get_import_export_formats( 'export', 'cformat' );

    // get filename
    if (empty($exportfilename)) {
        $exportfilename = referentiel_default_export_filename($course, $referentiel, 'certificat');
    }

    // print_heading_with_help($txt->exportreferentiel, 'export', 'referentiel');
    echo "\n<br />\n";
    print_box_start('generalbox boxwidthnormal boxaligncenter');
?>
  
    <form enctype="multipart/form-data" method="post" action="export_certificate.php?id=<?php echo $cm->id; ?>">
        <fieldset class="invisiblefieldset" style="display: block;">
            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>" />

            <table cellpadding="5">
                <tr>
                    <td><?php echo $txt->fileformat; ?>:</td>
                    <td>
                        <?php choose_from_menu($fileformatnames, 'format', 'xml', ''); ?>
                    </td>
                    <td>
                        <?php echo $txt->condense; ?>
                        <input type="checkbox" name="format_condense" value="1" />
                    </td>
                    <td>
                        <?php helpbutton('format_export_certificat', $txt->referentiel, 'referentiel'); ?>
                    </td>
                </tr>
                <tr>
                    <td><?php echo $txt->exportname; ?>:</td>
                    <td>
                        <input type="text" size="60" name="exportfilename" value="<?php echo $exportfilename; ?>" />
                    </td>
                    <td align="center" colspan="2">
                        <input type="submit" name="save" value="<?php echo $txt->exportcertificat; ?>" />
                    </td>
                </tr>
            </table>
<!-- tous les certificats -->
<input type="hidden" name="select_all" value="<?php echo $select_all; ?>" />				
<!-- coaching -->
<input type="hidden" name="select_acc" value="<?php echo $select_acc; ?>" />
<!-- user -->
<input type="hidden" name="userid" value="<?php echo $userid; ?>" />

    </form>
    <?php

    print_box_end();
    print_footer($course);
?>
