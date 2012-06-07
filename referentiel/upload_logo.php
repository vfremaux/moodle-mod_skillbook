<?php  // $Id: upload.php,v 1.0 2008/05/03 00:00:00 jfruitet Exp $
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

    require_once('../../config.php');
    require_once('lib.php');
	require_once($CFG->libdir . '/uploadlib.php');	// pour charger un fichier
    require_once("print_lib_referentiel.php");	// AFFICHAGES 
	
	// PAS DE RSS
    // require_once("$CFG->libdir/rsslib.php");

    $id    = optional_param('id', 0, PARAM_INT);    // course module id    
	$d     = optional_param('d', 0, PARAM_INT);    // referentielbase id

    $mode       = optional_param('mode','', PARAM_ALPHA);	
    $add        = optional_param('add','', PARAM_ALPHA);
    $update     = optional_param('update', 0, PARAM_INT);
    $delete     = optional_param('delete', 0, PARAM_INT);
    $approve    = optional_param('approve', 0, PARAM_INT);	
    $comment    = optional_param('comment', 0, PARAM_INT);		
    $courseid     = optional_param('courseid', 0, PARAM_INT);
    $groupmode  = optional_param('groupmode', -1, PARAM_INT);
    $cancel     = optional_param('cancel', 0, PARAM_BOOL);
	$select_acc = optional_param('select_acc', 0, PARAM_INT);      // coaching
	
	if ($d) {
        if (! $referentiel = get_record('referentiel', 'id', $d)) {
            print_error('Referentiel instance is incorrect');
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
            print_error('Referentiel instance is incorrect');
        }
        if (! $referentiel_referentiel = get_record('referentiel_referentiel', 'id', $referentiel->referentielid)) {
            print_error('Referentiel is incorrect');
        }
    } 
	else{
        // print_error('You cannot call this script in that way');	
		error(get_string('erreurscript','referentiel','Erreur01 : upload.php'));
	}
	
	$returnlink="$CFG->wwwroot/mod/referentiel/view.php?d=$referentiel->id";
    require_login($course->id, false, $cm);

    if (!isloggedin() or isguest()) {
        redirect($returnlink);
    }

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
	/// If it's hidden then it's don't show anything.  :)
	/// Some capability checks.
  if (empty($cm->visible)
    && (
        !has_capability('moodle/course:viewhiddenactivities', $context)
            &&
        !has_capability('mod/referentiel:managecomments', $context)
        )

  ) {
    $strreferentielbases = get_string('modulenameplural', 'referentiel');
    if (function_exists('build_navigation')){
		  // Moodle 1.9
		  $navigation = build_navigation($strreferentielbases, $cm);
      print_header_simple(format_string($referentiel->name),
        '',
      	$navigation,
		    '', // focus
        '',
        true,
        '',
        navmenu($course, $cm));
    }
    else{
      $navigation = "<a href=\"index.php?id=$course->id\">$strreferentielbases</a> ->";
      print_header_simple(format_string($referentiel->name),
        '',
        $navigation.' '.format_string($referentiel->name),
        '',
        '',
        true,
        '',
        navmenu($course, $cm));
    }
    notice(get_string("activityiscurrentlyhidden"),"$CFG->wwwroot/course/view.php?id=$course->id");
  }

    if ($referentiel->id) {    // So do you have access?
        if (!has_capability('mod/referentiel:writereferentiel', $context) or !confirm_sesskey() ) {
            error(get_string('noaccess','referentiel'));
        }
    }

	// variable d'action
	if (isset($mode)){
		if ($mode == "delete"){
			// Suppression du logo 
			if (isset($referentiel_referentiel) && $referentiel_referentiel){ // referentiel
				// MISE A JOUR
				$form->referentiel_id = $referentiel_referentiel->id;
				$form->instance = $referentiel_referentiel->id;
				$form->instance = $referentiel_referentiel->id;
		    	$form->name = $referentiel_referentiel->name;
    			$form->code = $referentiel_referentiel->code;
		    	$form->description = $referentiel_referentiel->description;
		    	$form->url = $referentiel_referentiel->url;
		    	$form->certificatethreshold = $referentiel_referentiel->certificatethreshold;
		    	$form->nb_domaines = $referentiel_referentiel->nb_domaines;
		    	$form->liste_codes_competence = $referentiel_referentiel->liste_codes_competence;
		    	$form->local = $referentiel_referentiel->local;
		    	$form->liste_empreintes_competence = $referentiel_referentiel->liste_empreintes_competence;
				$form->logo = "";
				if (referentiel_update_referentiel($form)){
					redirect($returnlink);
				}
			}
		}
	}
	
    // Traitement des POST
	$msg="";
    
    // get parameters
    $params = new stdClass;
    $params->choosefile = optional_param('choosefile','',PARAM_PATH);
	$params->newdocument = optional_param('newdocument',0,PARAM_BOOL);
	
    // get display strings
    $txt = new stdClass();
    $txt->referentiel = get_string('referentiel','referentiel');
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
	$txt->uploadserverlimit = get_string('uploadserverlimit');	
	
	
    // ensure the files area exists for this course	
	$path_to_data=referentiel_get_export_dir($course->id, "$referentiel->id");
    make_upload_directory($path_to_data);	
	
	// selecteur
	$search="";
	
	// RECUPERER LES FORMULAIRES
    if (isset($SESSION->modform)) {   // Variables are stored in the session
        $form = $SESSION->modform;
        unset($SESSION->modform);
    } else {
        $form = (object)$_POST;
    }

	// DOCUMENT ASSOCIE
	// file checks out ok
    $fileisgood = false;
	$urlfile = ""; // le chemin relatif du fichier charge
	
    // work out if this is an uploaded file 
    // or one from the filesarea.
	if (isset($form->newlogo) && ($form->newlogo==1)) {
		// DEBUG
		// echo "<br /> Ligne 170 :<br />\n";
		// print_r($params);
		// echo "<br />\n";
		// print_r($form);
		// echo "<br />\n";
		// print_r($_FILES);		
		
	    if (!empty($params->choosefile)) {
			// DEBUG
			// echo "<br /> Ligne 175 : $params->choosefile \n";
    		$importfile = "{$CFG->dataroot}/{$course->id}/{$params->choosefile}";
			$importfilename = $params->choosefile;
			// echo "<br /> Ligne 179 : $importfile\n";
			// echo "<br /> Ligne 180 : $importfilename\n";
        	if (file_exists($importfile)) {
        		$fileisgood = true;
				$urlfile="$course->id/$importfilename";	
				// echo "<br /> Ligne 166 : OK FILE-IS-GOOD !:>)) \n";
	        }
    	    else {
        		notify($txt->uploadproblem );
	        }
    	}
	    else if (isset($_FILES)) {
    		// must be upload file
			// DEBUG
			// echo "<br /> Ligne 192 :<br />\n";
			// print_r($_FILES);
			// echo "<br />\n";
			
        	if (empty($_FILES['newfile'])) {
				notify($txt->uploadproblem );
	        }
    	    else if (!is_uploaded_file($_FILES['newfile']['tmp_name'])) {
				notify($txt->uploadproblem );
			}
			else if ($_FILES['newfile']['size'] == 0) {
				notify( $txt->uploadproblem  );
        	}
	        else {
				// print_string('upload_succes', 'referentiel');   
				
				// echo "DEBUG : Affichage du contenu <br />\n";   
				// readfile($_FILES['newfile']['tmp_name']);
				
   		     	$importfile = $_FILES['newfile']['tmp_name'];
				$importfilename = $_FILES['newfile']['name'];
				// echo "<br /> Ligne 216 : $importfile\n";
				// echo "<br /> Ligne 217 : $importfilename\n";
				$urlfile=referentiel_upload_fichier($path_to_data, $importfile, $importfilename);
				if ($urlfile!=""){
					$fileisgood = true;
				}
        	}
   		}
		
   		if ($fileisgood && ($urlfile!="")) { // process if we are happy, file is ok
    		// traitement ad hoc
			$form->logo=$urlfile;
			if (!referentiel_update_referentiel($form)){
				; // RAS ?
			}
		}
		redirect($returnlink);
   	}
	
	
	if ($cancel) {
        if (!empty($SESSION->returnpage)) {
            $return = $SESSION->returnpage;
            unset($SESSION->returnpage);
            redirect($return);
        } else {
            redirect($returnlink);
        }
    }

	// afficher les formulaires
    unset($SESSION->modform); // Clear any old ones that may be hanging around.
    $modform = "upload_logo.html";

    if (file_exists($modform)) {
        if ($usehtmleditor = can_use_html_editor()) {
            $defaultformat = FORMAT_HTML;
            $editorfields = '';
        } 
		else {
            $defaultformat = FORMAT_MOODLE;
        }
	}
	else {
        notice("ERREUR : No file found at : $modform)", "view.php?id=$course->id&amp;d=$referentiel->id");
    }
	
	/// RSS and CSS and JS meta
    
	/// Print the page header
	$strreferentiels = get_string('modulenameplural','referentiel');
	$strreferentiel = get_string('referentiel','referentiel');
	$strmessage = get_string('modifier_referentiel','referentiel');		
	$icon = '<img class="icon" src="'.$CFG->wwwroot.'/mod/referentiel/icon.gif" alt="'.get_string('modulename','referentiel').'"/>';
	$strpagename=get_string('modifier_referentiel','referentiel');
	$meta = '<link rel="stylesheet" type="text/css" href="referentiel.css" />';
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
		// 1.8
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
		false); // If true, return the visible elements of the header instead of echoing them.
		
	}
	
	/// Check to see if groups are being used here
	$groupmode = groupmode($course, $cm);
	$currentgroup = setup_and_print_groups($course, $groupmode, "edit.php?d=".$referentiel->id);
		
	print_heading(format_string($referentiel->name));
	/// Print the tabs
	if (!isset($mode)){
		$mode='editreferentiel'; // un seul mode possible
	}
	$currenttab = 'editreferentiel';
    if ($referentiel->id) {
       	$editentry = true;  //used in tabs
    }
	include('tabs.php');

    print_heading_with_help($strmessage, 'referentiel', 'referentiel', $icon);
	if ($mode=='listreferentiel'){
		referentiel_affiche_referentiel($referentiel->id); 
	}
	else {
		print_simple_box_start('center', '', '', 5, 'generalbox', $referentiel->name);
        // formulaires
		include_once($modform);
	    print_simple_box_end();
	}
	
	print_footer($course);
	
?>
