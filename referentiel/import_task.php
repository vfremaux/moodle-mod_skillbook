<?php  // $Id: import_task.php,v 1.1 2010/12/17 21:49:36 vf Exp $
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
* Importation d'un referentiel
* D'apres competency/import.php 
*
* @package referentiel
*/

    require_once('../../config.php');
    require_once('lib.php');
    require_once('lib_task.php');
    // require_once('pagelib.php'); // ENTETES
    require_once('print_lib_task.php');	// AFFICHAGES 
    require_once('import_export_lib.php');	// IMPORT / EXPORT	
    require_once($CFG->libdir . '/uploadlib.php');
	
    $id    = optional_param('id', 0, PARAM_INT);    // course module id
    $d     = optional_param('d', 0, PARAM_INT);    // referentiel base id
	$pass  = optional_param('pass', 0, PARAM_INT);    // mot de passe ok
    $checkpass = optional_param('checkpass','', PARAM_ALPHA); // mot de passe fourni

    $mode           = optional_param('mode','', PARAM_ALPHA);	

    $format = optional_param('format','', PARAM_FILE );
    $courseid = optional_param('courseid', 0, PARAM_INT);
	if ($d) {
        if (! $referentiel = get_record('referentiel', 'id', $d)) {
            print_error('Certification instance is incorrect');
        }
        if (! $referentiel_referentiel = get_record('referentiel_referentiel', 'id', $referentiel->referentielid)) {
            print_error('Referentiel id is incorrect');
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
            print_error('Certification instance is incorrect');
        }
        if (! $referentiel_referentiel = get_record('referentiel_referentiel', 'id', $referentiel->referentielid)) {
            print_error('Referentiel is incorrect');
        }
    } 
	else{
        // print_error('You cannot call this script in that way');	
		error(get_string('erreurscript','referentiel','Erreur01 : import_task.php'));
	}
	
	
    // get parameters
    $params = new stdClass;
    $params->choosefile = optional_param('choosefile','',PARAM_PATH);
    $params->stoponerror = optional_param('stoponerror', 0, PARAM_BOOL);
    $params->override = optional_param('override', 0, PARAM_BOOL);	
    $params->newinstance = optional_param('newinstance', 0, PARAM_BOOL);		

    // get display strings
    $txt = new stdClass();
    $txt->referentiel = get_string('referentiel','referentiel');
    $txt->fileformat = get_string('fileformat','referentiel');
	$txt->choosefile = get_string('choosefile','referentiel');
	
    $txt->file = get_string('file');
    $txt->fileformat = get_string('fileformat','referentiel');
    $txt->fromfile = get_string('fromfile','referentiel');
    $txt->importerror = get_string('importerror','referentiel');
    $txt->importfilearea = get_string('importfilearea','referentiel');
    $txt->importfileupload = get_string('importfileupload','referentiel');
    $txt->importfromthisfile = get_string('importfromthisfile','referentiel');
    $txt->modulename = get_string('modulename','referentiel');
    $txt->modulenameplural = get_string('modulenameplural','referentiel');
    $txt->onlyteachersimport = get_string('onlyteachersimport','referentiel');
    $txt->stoponerror = get_string('stoponerror', 'referentiel');
	$txt->upload = get_string('upload');
    $txt->uploadproblem = get_string('uploadproblem');
    $txt->uploadthisfile = get_string('uploadthisfile');
	$txt->importreferentiel	= get_string('importreferentiel','referentiel');
	$txt->newinstance	= get_string('newinstance','referentiel');	
	$txt->choix_newinstance	= get_string('choix_newinstance','referentiel');
	$txt->choix_notnewinstance	= get_string('choix_notnewinstance','referentiel');
	$txt->override = get_string('override', 'referentiel');
	$txt->choix_override	= get_string('choix_override','referentiel');
	$txt->choix_notoverride	= get_string('choix_notoverride','referentiel');
		

    require_login($course->id, false, $cm);

    if (!isloggedin() or isguest()) {
        redirect('view.php?id='.$cm->id);
    }

    // check role capability
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    require_capability('mod/referentiel:import', $context);

    // ensure the files area exists for this course
    make_upload_directory( "$course->id/$CFG->moddata/referentiel" );

	/// Check to see if groups are being used here
		
	$groupmode = groupmode($course, $cm);
	$currentgroup = setup_and_print_groups($course, $groupmode, 'import_task.php?d='.$referentiel->id);
		
    if ($usehtmleditor = can_use_html_editor()) {
    	$defaultformat = FORMAT_HTML;
        $editorfields = '';
    } else {
    	$defaultformat = FORMAT_MOODLE;
    }
		
	/// RSS and CSS and JS meta
    $meta = '';

	/// Print the page header
	$strreferentiels = get_string('modulenameplural','referentiel');
	$strreferentiel = get_string('referentiel','referentiel');
	$strmessage = get_string('importtasks','referentiel');		
	$icon = '<img class="icon" src="'.$CFG->wwwroot.'/mod/referentiel/icon.gif" alt="'.get_string('modulename','referentiel').'"/>';
	
	$strpagename=get_string('import_task','referentiel');
	if (function_exists('build_navigation')){
		 // Moodle 1.9
		$navigation = build_navigation($strpagename, $cm);
		
		print_header($course->shortname.': '.$strreferentiel, $course->fullname, $navigation, 
		'', // focus
		$meta,
		true, // page is cacheable
		update_module_button($cm->id, $course->id, get_string('modulename-intance', 'referentiel')), // HTML code for a button (usually for module editing)
        navmenu($course, $cm), // HTML code for a popup menu
		false, // use XML for this page
		'', // This text will be included verbatim in the <body> tag (useful for onload() etc)
		false); // If true, return the visible elements of the header instead of echoing them.
	}
	else{
		print_header_simple($referentiel->name, // title
		'', // heading
		"<a href='index.php?id=$course->id'>$strreferentiel</a> -> $referentiel->name", // navigation
		'', // focus
		$meta, // meta tag
		true, // page is cacheable
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
		$mode='importtask'; // un seul mode possible
	}
	$currenttab = 'importtask';
    if ($referentiel->id) {
    	$editentry = true;  //used in tabs
    }
	include('tabs.php');

    print_heading_with_help($strmessage, 'importtask', 'referentiel', $icon);

    // file upload form sumitted
    if (!empty($format)) { 
        if (!confirm_sesskey()) {
            print_error( 'sesskey' );
        }
        // file checks out ok
        $fileisgood = false;
        // work out if this is an uploaded file 
        // or one from the filesarea.
        if (!empty($params->choosefile)) {
            $importfile = "{$CFG->dataroot}/{$course->id}/{$params->choosefile}";
			// DEBUG
			// echo "<br />DEBUG :: import_task.php :: 209 :: <br />{$CFG->dataroot}/{$course->id}/{$params->choosefile}\n";
			// exit;
			
            if (file_exists($importfile)) {
                $fileisgood = true;
            }
            else {
                notify($txt->uploadproblem);
            }
        }
        else {
            // must be upload file
            if (empty($_FILES['newfile'])) {
                notify( $txt->uploadproblem );
            }
            else if ((!is_uploaded_file($_FILES['newfile']['tmp_name']) or $_FILES['newfile']['size'] == 0)) {
                notify( $txt->uploadproblem );
            }
            else {
                $importfile = $_FILES['newfile']['tmp_name'];
                $fileisgood = true;
            }
        }

        // process if we are happy, file is ok
        if ($fileisgood) { 
            if (! is_readable("format/$format/format.php")) {
                print_error( get_string('formatnotfound','referentiel', $format) );
            }
            require("format.php");  // Parent class
            require("format/$format/format.php");
            $classname = "tformat_$format";
            $tformat = new $classname();
            // load data into class
			// DEBUG
			// print_r($params);
			// echo "<br />\n";
			
            $tformat->setIReferentiel( $referentiel );			
            $tformat->setRReferentiel( $referentiel_referentiel );
            $tformat->setCourse( $course );
			$tformat->setCoursemodule( $cm); 
            $tformat->setFilename( $importfile );
            $tformat->setStoponerror( $params->stoponerror );
			$tformat->setReturnpage("");
            // Do anything before that we need to
            if (! $tformat->importpreprocess()) { 
                print_error( $txt->importerror ,
                      "$CFG->wwwroot/mod/referentiel/import_task.php?d={$referentiel->id}");
            }
			
            // Process the uploaded file
			
            if (! $tformat->importprocess() ) {     
                print_error( $txt->importerror ,
                      "$CFG->wwwroot/mod/referentiel/import_task.php?d={$referentiel->id}");
            }
			
            // In case anything needs to be done after
            if (! $tformat->importpostprocess()) {
                print_error( $txt->importerror ,
                      "$CFG->wwwroot/mod/referentiel/import_task.php?d={$referentiel->id}");
            }
			
            echo "<hr />";
			if (isset($tformat->returnpage) && ($tformat->returnpage!="")){
				print_continue($tformat->returnpage);
			}
			else{
		        print_continue($CFG->wwwroot.'/mod/referentiel/task.php?id='.$cm->id);
			}
            print_footer($course);
            exit;
        }
    }

		
	// Le referentiel est-il protege par mot de passe ?
	// RECUPERER LES FORMULAIRES
    if (isset($SESSION->modform)) {   // Variables are stored in the session
        $form = $SESSION->modform;
        unset($SESSION->modform);
    } 
	else {
        $form = (object)$_POST;
    }
	

    /// Print upload form
	
    // get list of available import formats
    $fileformatnames = referentiel_get_import_export_formats( 'import','tformat' );
	
	//==========
    // DISPLAY
    //==========
 	
    ?>

    <form id="form" enctype="multipart/form-data" method="post" action="<?php p("import_task.php?d=$referentiel->id") ?>">
        <fieldset class="invisiblefieldset" style="display: block;">
            <input type="hidden" name="sesskey" value="<?php p(sesskey()); ?>" />
            <input type="hidden" name="courseid" value="<?php p($course->id) ?>" />
			<input name="newinstance" type="hidden" value="0"/> 
            <?php print_box_start('generalbox boxwidthnormal boxaligncenter'); ?>
            <table cellpadding="5">
                <tr>
                    <td align="right"><?php p($txt->fileformat); ?>:</td>
                    <td><?php choose_from_menu($fileformatnames, 'format', 'xml', '');
                        helpbutton("format", $txt->importreferentiel, 'referentiel'); ?></td>
                </tr>

                <tr>					
                   <td align="right"><?php p($txt->stoponerror); ?></td>
                   <td><input name="stoponerror" type="checkbox" checked="checked" />
                   </td>
                </tr>
            </table>
            <?php
            print_box_end();

            print_box_start('generalbox boxwidthnormal boxaligncenter'); ?>
            <?php p($txt->importfileupload); ?>
            <table cellpadding="5">
                <tr>
                    <!-- td align="right"><?php p($txt->upload); ?>:</td -->
                    <td colspan="2"><?php upload_print_form_fragment(1,array('newfile'),null,false,null,$course->maxbytes,0,false); ?></td>
                </tr>

                <tr>
                    <td>&nbsp;</td>
                    <td><input type="submit" name="save" value="<?php p($txt->uploadthisfile); ?>" /></td>
                </tr>
            </table>
            <?php
            print_box_end();

            print_box_start('generalbox boxwidthnormal boxaligncenter'); ?>
            <?php p($txt->importfilearea); ?>
            <table cellpadding="5">
                <tr>
                    <td align="right"><?php p($txt->file); ?>:</td>
                    <td><input type="text" name="choosefile" size="50" /></td>
                </tr>

                <tr>
                    <td>&nbsp;</td>
                    <td><?php  button_to_popup_window("/files/index.php?id={$course->id}&amp;choose=form.choosefile", 
                        "coursefiles", $txt->choosefile, 500, 750, $txt->choosefile); ?>
                        <input type="submit" name="save" value="<?php p($txt->importfromthisfile); ?>" /></td>
                </tr>
            </table>
            <?php 
            print_box_end(); ?>
        </fieldset>
    </form>

    <?php
    print_footer($course);

?>
