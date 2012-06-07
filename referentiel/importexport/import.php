<?php  // $Id: import_instance.php,v 1.0 2008/04/29/ 00:00:00 jfruitet Exp $
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
    // require_once('pagelib.php'); // ENTETES
    // require_once('print_lib_referentiel.php');	// AFFICHAGES 
    require_once('import_export_lib.php');	// IMPORT / EXPORT	
    require_once($CFG->libdir . '/uploadlib.php');
    // require_once($CFG->libdir . '/questionlib.php');

    $id    = optional_param('id', 0, PARAM_INT);    // course module id
    $d     = optional_param('d', 0, PARAM_INT);    // referentiel base id
	$pass  = optional_param('pass', 0, PARAM_INT);    // mot de passe ok
    $checkpass = optional_param('checkpass','', PARAM_ALPHA); // mot de passe fourni

    $mode           = optional_param('mode','', PARAM_ALPHA);	

    $format = optional_param('format','', PARAM_FILE );
    $courseid = optional_param('courseid', 0, PARAM_INT);
	$select_acc = optional_param('select_acc', 0, PARAM_INT);      // coaching
    
    
	if ($d) {
        if (! $referentiel = get_record('referentiel', 'id', $d)) {
            print_error('Certification instance is incorrect');
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
            print_error('Certification instance is incorrect');
        }
        if (! $referentiel_referentiel = get_record('referentiel_referentiel', 'id', $referentiel->referentielid)) {
            print_error('Referentiel is incorrect');
        }
    } 
	else{
        // print_error('You cannot call this script in that way');	
		error(get_string('erreurscript','referentiel','Erreur01 : import.php'));
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
	
	$txt->formatincompatible= get_string('formatincompatible','referentiel');
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
        redirect($CFG->wwwroot.'/mod/referentiel/view.php?id='.$cm->id.'&amp;noredirect=1');
    }

    // check role capability
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    require_capability('mod/referentiel:import', $context);

    // ensure the files area exists for this course
    make_upload_directory( "$course->id/$CFG->moddata/referentiel" );
		
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
	$strmessage = get_string('importreferentiel','referentiel');		
	$icon = '<img class="icon" src="'.$CFG->wwwroot.'/mod/referentiel/icon.gif" alt="'.get_string('modulename','referentiel').'"/>';
	
	$strpagename=get_string('import','referentiel');
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
    /*
 		/// Check to see if groups are being used here
		/// find out current groups mode
   		$groupmode = groups_get_activity_groupmode($cm);
	    $currentgroup = groups_get_activity_group($cm, true);
    	groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/referentiel/import.php?d='.$referentiel->id.'&amp;mode='.$mode.'&amp;select_acc='.$select_acc);
	  */	
	}
	else{
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
		/*
		// 1.8
		$groupmode = groupmode($course, $cm);
    $currentgroup = setup_and_print_groups($course, $groupmode, $CFG->wwwroot . '/mod/referentiel/import.php?d='.$referentiel->id.'&amp;mode='.$mode.'&amp;select_acc='.$select_acc);
    */				
	}
	print_heading(format_string($referentiel->name));
	/// Print the tabs
	if (!isset($mode)){
		$mode='import'; // un seul mode possible
	}
	$currenttab = 'import';
    if ($referentiel->id) {
    	$editentry = true;  //used in tabs
    }
	include('tabs.php');

    print_heading_with_help($strmessage, 'importreferentiel', 'referentiel', $icon);
	if ($mode=='listreferentiel'){
		referentiel_affiche_referentiel_instance($referentiel->id); 
	}

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
			// echo "<br />DEBUG :: import.php :: 209 :: <br />{$CFG->dataroot}/{$course->id}/{$params->choosefile}\n";
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

                // tester l'extention du fichier
                // DEBUG
                // echo "<br>DEBUG : 214 import_instance.php<br>FORMAT : $format<br>IMPORT_FILE $importfile\n";
       			// Les données suivantes sont disponibles après chargement
			    // echo "<br>DEBUG :: Fichier téléchargé : '". $_FILES['newfile']['tmp_name'] ."'\n";
                // echo "<br>DEBUG :: Nom : '". $_FILES['newfile']['name'] ."'\n";
			    // echo "<br>DEBUG :: Erreur : '". $_FILES['newfile']['error'] ."'\n";
			    // echo "<br>DEBUG :: Taille : '". $_FILES['newfile']['size'] ."'\n";

                // echo "<br>DEBUG :: Type : '". $_FILES['newfile']['type'] ."'\n";
			    $nom_fichier_charge_extension = substr( strrchr($_FILES['newfile']['name'], "." ), 1);
			    // echo "<br>DEBUG :: LIGNE 223 :: Extension : '". $nom_fichier_charge_extension ."'\n";
			    // echo "<br>DEBUG :: LE FICHIER EST CHARGE\n";
                if ($nom_fichier_charge_extension!=$format){
                     notify( $txt->formatincompatible);
                }
                else{
                    $fileisgood = true;
                }

            }
        }

        // process if we are happy, file is ok
        if ($fileisgood) { 
            if (! is_readable("format/$format/format.php")) {
                print_error( get_string('formatnotfound','referentiel', $format) );
            }
            require("format.php");  // Parent class
            require("format/$format/format.php");
            $classname = "rformat_$format";
            $rformat = new $classname();
            // load data into class
			// DEBUG
			// print_r($params);
			// echo "<br />\n";
			
            $rformat->setIReferentiel( $referentiel );			
            $rformat->setRReferentiel( $referentiel_referentiel );
            $rformat->setCourse( $course );
			$rformat->setCoursemodule( $cm); 
            $rformat->setFilename( $importfile );
            $rformat->setStoponerror( $params->stoponerror );
			$rformat->setOverride( $params->override );
			$rformat->setNewinstance( $params->newinstance );
			$rformat->setReturnpage("");
            // Do anything before that we need to
            if (! $rformat->importpreprocess()) { 
                print_error( $txt->importerror ,
                      "$CFG->wwwroot/mod/referentiel/import.php?d={$referentiel->id}");
            }
			
            // Process the uploaded file
			
            if (! $rformat->importprocess() ) {     
                print_error( $txt->importerror ,
                      "$CFG->wwwroot/mod/referentiel/import.php?d={$referentiel->id}");
            }
			
            // In case anything needs to be done after
            if (! $rformat->importpostprocess()) {
                print_error( $txt->importerror ,
                      "$CFG->wwwroot/mod/referentiel/import.php?d={$referentiel->id}");
            }
			
            echo "<hr />";
			if (isset($rformat->returnpage) && ($rformat->returnpage!="")){
				print_continue($rformat->returnpage);
			}
			else{
		        print_continue($CFG->wwwroot.'/mod/referentiel/view.php?id='.$cm->id.'&amp;noredirect=1');
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

	if ($referentiel_referentiel){
		if (!$pass && ($checkpass=='checkpass') && !empty($form->password)){
			$pass=referentiel_check_pass($referentiel_referentiel, $form->password);
			if (!$pass){
				// Abandonner
 				print_continue($CFG->wwwroot.'/mod/referentiel/view.php?id='.$cm->id.'&amp;noredirect=1');
      			exit;
			}
		}
		else{
			// saisie du mot de  passe 
			if (isset($referentiel_referentiel->referentielauthormail) && ($referentiel_referentiel->referentielauthormail!='') 
				&& (referentiel_get_user_mail($USER->id)!=$referentiel_referentiel->referentielauthormail)) { 
				// 
				print_simple_box_start('center', '', '', 5, 'generalbox', $referentiel->name);
    	    	// formulaires
				$appli_appelante="import.php";
				include_once("pass.html");
				print_simple_box_end();
				print_footer($course);
				exit;
			}
		}
	}
	

    /// Print upload form
	
    // get list of available import formats
    $fileformatnames = referentiel_get_import_export_formats( 'import','rformat' );
	
	//==========
    // DISPLAY
    //==========
 	
    ?>

    <form id="form" enctype="multipart/form-data" method="post" action="<?php p("import.php?d=$referentiel->id&pass=$pass") ?>">
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

                <tr>					
                   <td align="right"><?php p($txt->override); ?></td>
				   <td>
				   <input name="override" type="radio" value="1" /> <?php p($txt->choix_override); ?>
				   <br />
				   <input name="override" type="radio"  value="0"  checked="checked" /> <?php p($txt->choix_notoverride); ?>
                    <?php helpbutton('override', $txt->override, 'referentiel'); ?></td>
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
