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
  require_once('lib_etab.php');
	require_once($CFG->libdir . '/uploadlib.php');	// pour charger un fichier
  require_once("print_lib_activite.php");	// AFFICHAGES 
	
	// PAS DE RSS
    // require_once("$CFG->libdir/rsslib.php");

    $id    = optional_param('id', 0, PARAM_INT);    // course module id    
	  $d     = optional_param('d', 0, PARAM_INT);    // referentielbase id
	
    $activite_id   = optional_param('activite_id', 0, PARAM_INT);    //record activite id
    $document_id   = optional_param('document_id', 0, PARAM_INT);    //record document id	
	
    // $import   = optional_param('import', 0, PARAM_INT);    // show import form

    $action  	= optional_param('action','', PARAM_CLEAN); // pour distinguer differentes formes de traitements
    $mode       = optional_param('mode','', PARAM_ALPHA);	
    $add        = optional_param('add','', PARAM_ALPHA);
    $update     = optional_param('update', 0, PARAM_INT);
    $delete     = optional_param('delete', 0, PARAM_INT);
    $approve    = optional_param('approve', 0, PARAM_INT);	
    $comment    = optional_param('comment', 0, PARAM_INT);		
    $courseid     = optional_param('courseid', 0, PARAM_INT);
    $groupmode  = optional_param('groupmode', -1, PARAM_INT);
    $cancel     = optional_param('cancel', 0, PARAM_BOOL);
	  $userid = optional_param('userid', 0, PARAM_INT);
		$select_acc = optional_param('select_acc', 0, PARAM_INT);      // coaching

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
		error(get_string('erreurscript','referentiel','Erreur01 : upload.php'));
	}
	
	if ($activite_id) { // id activite
        if (! $record = get_record('referentiel_activity', 'id', $activite_id)) {
            print_error('Activite ID is incorrect');
        }
	}
	if ($document_id) { // id activite
        if (! $record_document = get_record('referentiel_document', 'id', $document_id)) {
            print_error('Document ID is incorrect');
        }
	}
	
  require_login($course->id, false, $cm);

  if (!isloggedin() or isguest()) {
        redirect($CFG->wwwroot.'/mod/referentiel/view.php?id='.$cm->id.'&amp;noredirect=1');

  }

	if ($cancel) {
        if (!empty($SESSION->returnpage)) {
            $return = $SESSION->returnpage;
            unset($SESSION->returnpage);
            redirect($return);
        } else {
            redirect('activite.php?d='.$referentiel->id.'&amp;userid='.$userid.'&amp;mode=listactivityall');
        }
    }
	
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
    $txt->importfileupload = get_string('choiximportfileupload','referentiel');
    $txt->importfromthisfile = get_string('importfromthisfile','referentiel');
    $txt->modulename = get_string('modulename','referentiel');
    $txt->modulenameplural = get_string('modulenameplural','referentiel');
    $txt->onlyteachersimport = get_string('onlyteachersimport','referentiel');
    $txt->stoponerror = get_string('stoponerror', 'referentiel');
	$txt->upload = get_string('upload');
    $txt->uploadproblem = get_string('uploadproblem');
    $txt->uploadthisfile = get_string('uploadthisfile');
	$txt->uploadserverlimit = get_string('uploadserverlimit');	
	$txt->link = get_string('choix_web_link','referentiel');
	
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
    if ($activite_id) {    // So do you have access?
        if (!(has_capability('mod/referentiel:writereferentiel', $context) 
			or referentiel_activite_isowner($activite_id)) or !confirm_sesskey() ) {
            error(get_string('noaccess','referentiel'));
        }
    }
	
    // ensure the files area exists for this course	
	$path_to_data=referentiel_get_export_dir($course->id, "$referentiel->id/$USER->id");
	// $path_to_data=referentiel_get_export_dir($course->id);
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
	if (isset($form->newdocument) && ($form->newdocument==1)) {
		// DEBUG
		// echo "<br /> Ligne 170 :<br />\n";
		// print_r($params);
		// echo "<br />\n";
		// print_r($form);
		// echo "<br />\n";
		// print_r($_FILES);		
		if (isset($form->url) && !empty($form->url)){
			if (isset($form->url) && !empty($form->url)){
				$urlfile=$form->url;
				$fileisgood=true;
			}
		}
	    else if (!empty($params->choosefile)) {
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
        		print_error($txt->uploadproblem );
	        }
    	}
	    else if (isset($_FILES)) {
    		// must be upload file
			// DEBUG
			// echo "<br /> Ligne 192 :<br />\n";
			// print_r($_FILES);
			// echo "<br />\n";
			
        	if (empty($_FILES['newfile'])) {
				print_error($txt->uploadproblem );
	        }
    	    else if (!is_uploaded_file($_FILES['newfile']['tmp_name'])) {
				print_error($txt->uploadproblem );
			}
			else if ($_FILES['newfile']['size'] == 0) {
				print_error( $txt->uploadproblem  );
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
			$form->url=$urlfile;
			
		}
		
		// pas de fichier mais on peut enregistrer les modifs sur le reste du document
		if (isset($form) && isset($form->activityid) && ($form->activityid>0)){
			if (isset($form->document_id) && ($form->document_id>0)){
				if (!referentiel_update_document($form)){
					// RAS ?
				}
			}
			else{
				referentiel_add_document($form);
			}
		}
		redirect('activite.php?d='.$referentiel->id.'&amp;userid='.$userid.'&amp;mode=listactivityall');
   	}
	
	
/* A REPRENDRE ? **********
	/// Delete any requested records
    if (isset($delete) && ($delete>0 )
			&& confirm_sesskey() 
			&& (has_capability('mod/referentiel:write', $context) or referentiel_activite_isowner($delete))) {
        if ($confirm = optional_param('confirm',0,PARAM_INT)) {
			// mise a zero du certificate associe a cette personne pour ce referentiel 
			if ($deletedrecord = get_record('referentiel_activity', 'id', $delete)) {
				// mise a zero du certificate associe a cette personne pour ce referentiel 
				referentiel_certificate_user_invalider($deletedrecord ->userid, $deletedrecord ->referentielid);
	        }
			// suppression
			if (referentiel_delete_activity_record($delete)){
				add_to_log($course->id, 'referentiel', 'record delete', "activite.php?d=$referentiel->id", $delete, $cm->id);
                // print_error(get_string('recorddeleted','referentiel'), 'notifysuccess');
            }
		} 
    }
****/

	// afficher les formulaires
    unset($SESSION->modform); // Clear any old ones that may be hanging around.
    $modform = "upload_document.html";

	/// Print the page header
	$strreferentiels = get_string('modulenameplural','referentiel');
	$strreferentiel = get_string('referentiel','referentiel');
	$stractivite = get_string('depot_document','referentiel');
	$icon = '<img class="icon" src="'.$CFG->wwwroot.'/mod/referentiel/icon.gif" alt="'.get_string('modulename','referentiel').'"/>';
	/// RSS and CSS and JS meta
    $meta = '<link rel="stylesheet" type="text/css" href="activite.css" />';

	$strpagename=get_string('modifier_document','referentiel');
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
	
	/// Check to see if groups are being used here
	$groupmode = groupmode($course, $cm);
	$currentgroup = setup_and_print_groups($course, $groupmode, 'activite.php?d='.$referentiel->id);

	print_heading(format_string($referentiel->name));
		
	/// Print the tabs
	if (!isset($mode) || ($mode=="")){
		$mode='listactivity';		
	}
	if (isset($mode) && ($mode=="list")){
		$mode='listactivity';		
	}
	
	if (isset($mode) && (($mode=="adddocument") || ($mode=="updatedocument"))){
		$currenttab ='updateactivity';		
	}
	else if (isset($mode) && (($mode=="deleteactivity") || ($mode=="approveactivity") || ($mode=="commentactivity"))){
		$currenttab ='updateactivity';		
	}
	else if (isset($mode) && ($mode=='listactivityall')){
		$currenttab ='listactivityall';
	}
	else if (isset($mode) && ($mode=='listactivitysingle')){
		$currenttab ='listactivity';
	}
	else{
		$currenttab = $mode;
	}
	// DEBUG
	if ($activite_id) {
    	$editentry = true;  //used in tabs
    }
	include('tabs.php');

    print_heading_with_help($stractivite, 'document', 'referentiel', $icon); 
	
	if (($mode=='list') || ($mode=='listactivity')  || ($mode=='listactivitysingle')){
		referentiel_print_liste_activites($mode, $referentiel, $search); 
	}
	else {
		print_simple_box_start('center', '', '', 5, 'generalbox', $referentiel->name);
		if ($mode=='updatedocument'){
			// recuperer l'id de l'activite
			if ($activite_id) { // id 	activite
    	    	if (! $record = get_record('referentiel_activity', 'id', $activite_id)) {
			    	notify('Activite ID is incorrect');
    			}
			}
			if ($document_id) { // id 	activite
    	    	if (! $record_document = get_record('referentiel_document', 'id', $document_id)) {
			    	notify('Document ID is incorrect');
    			}
			}
			
			$modform = "upload_document.html";
		}
		else if ($mode=='adddocument'){
			// recuperer l'id de l'activite
			if ($activite_id) { // id 	activite
    	    	if (! $record = get_record('referentiel_activity', 'id', $activite_id)) {
			    	notify('Activite ID is incorrect');
    			}
			}
			
			$modform = "upload_document.html";
		}
    	// formulaires
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
    	    notice("ERREUR : No file found at : $modform)", "activite.php?d=$referentiel->id");
    	}
		
		include_once($modform);
	    print_simple_box_end();
	} 
    print_footer($course);

?>
