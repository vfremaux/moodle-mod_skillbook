<?php  // $Id: scolarite.php,v 1.0 2008/05/03 00:00:00 jfruitet Exp $
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
    require_once('print_lib_etablissement.php');	// AFFICHAGES 
	
	// PAS DE RSS
    // require_once("$CFG->libdir/rsslib.php");

    $id    = optional_param('id', 0, PARAM_INT);    // course module id    
	$d     = optional_param('d', 0, PARAM_INT);    // referentielbase id
	
    $etablissement_id   = optional_param('etablissement_id', 0, PARAM_INT);    //record etablissement id

    // $import   = optional_param('import', 0, PARAM_INT);    // show import form

    $action  	= optional_param('action','', PARAM_CLEAN); // pour distinguer differentes formes de traitements
    $mode       = optional_param('mode','', PARAM_ALPHA);	
    $add        = optional_param('add','', PARAM_ALPHA);
    $update     = optional_param('update', 0, PARAM_INT);
    $delete     = optional_param('delete', 0, PARAM_INT);
    $approve    = optional_param('approve', 0, PARAM_INT);	
    $comment    = optional_param('comment', 0, PARAM_INT);		
    $course     = optional_param('course', 0, PARAM_INT);
    $groupmode  = optional_param('groupmode', -1, PARAM_INT);
    $cancel     = optional_param('cancel', 0, PARAM_BOOL);
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
		error(get_string('erreurscript','referentiel','Erreur01 : etablissement.php'));
	}
	
	
	if (isset($etablissement_id) && ($etablissement_id>0)) { 
		// id etablissement
        if (! $record = get_record('referentiel_institution', 'id', $etablissement_id)) {
            print_error('etablissement id is incorrect');
        }
	}

    require_login($course->id, false, $cm);

    if (!isloggedin() or isguest()) {
        redirect($CFG->wwwroot.'/mod/referentiel/view.php?id='.$cm->id.'&amp;noredirect=1');
    }

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
	/// If it's hidden then it's don't show anything.  :)
    if (empty($cm->visible) and !has_capability('moodle/course:viewhiddenactivities', $context)) {
        $strreferentielbases = get_string("modulenameplural", "referentiel");
        $navigation = "<a href=\"index.php?id=$course->id\">$strreferentielbases</a> ->";
        print_header_simple(format_string($referentiel->name), "",
                 "$navigation ".format_string($referentiel->name), "", "", true, '', navmenu($course, $cm));
        notice(get_string("refrentieliscurrentlyhidden"));
    }
	
    if ($etablissement_id) {    // So do you have access?
        if (!(has_capability('mod/referentiel:managecertif', $context) 
			or ($USER->id==$etablissement_id)) or !confirm_sesskey() ) {
            error(get_string('noaccess','referentiel'));
        }
    }
	
	// selecteur
	$search="";
	
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
            redirect('etablissement.php?d='.$referentiel->id);
        }
    }
 	
	/// Delete any requested records
    if (isset($delete) && ($delete>0 )
			&& confirm_sesskey() 
			&& (has_capability('mod/referentiel:managecertif', $context) or referentiel_etablissement_isowner($delete))) {
        if ($confirm = optional_param('confirm',0,PARAM_INT)) {
            if (referentiel_delete_etablissement($delete)){
				// DEBUG
				// echo "<br /> etablissement REMIS A ZERO\n";
				// exit;
				add_to_log($course->id, 'referentiel', 'record delete', "etablissement.php?d=$referentiel->id", $delete, $cm->id);
                // notify(get_string('recorddeleted','referentiel'), 'notifysuccess');
            }
		} 
		$mode='listeetab';
    }
	

	if (!empty($referentiel) && !empty($course) 
		&& isset($form) && isset($form->mode)
		)
	{
		// update form submitted
		switch ($form->mode) {
			case "addetab":
				// echo "<br /> $form->mode\n";
				
				if (isset($form->name)) {
   		        	if (trim($form->name) == '') {
       		        	unset($form->name);
           		    }
               	}
    	    	$return = referentiel_add_etablissement($form);
   	    	    if (!$return) {
					/*
            		    if (file_exists($moderr)) {
                			$form = $form;
                    		include_once($moderr);
                        	die;
	                    }
					*/
    		       	print_error("Could not create etablissement  of the referentiel", "etablissement.php?d=$referentiel->id");
				}
		        if (is_string($return)) {
    		       	print_error($return, "etablissement.php?d=$referentiel->id");
	    	    }
	        	if (isset($form->redirect)) {
    	       		$SESSION->returnpage = $form->redirecturl;
				} 
				else {
        	   		$SESSION->returnpage = "$CFG->wwwroot/mod/referentiel/etablissement.php?d=$referentiel->id";
	            }
				add_to_log($course->id, "referentiel", "update",
            	           "mise a jour etablissement ",
                           "$return", "");
			break;
			
    		case "updateetab":
			
				// DEBUG
				// echo "<br /> $form->mode\n";
				
				if (isset($form->name)) {
   		        	if (trim($form->name) == '') {
       		        	unset($form->name);
           		    }
               	}
				
				// DEBUG
				// echo "<br /> UPDATE\n";
				
	    	    	$return = referentiel_update_etablissement($form);
    	    	    if (!$return) {
					/*
            		    if (file_exists($moderr)) {
                			$form = $form;
                    		include_once($moderr);
                        	die;
	                    }
					*/
    	            	print_error("Could not update etablissement  of the referentiel", "etablissement.php?d=$referentiel->id");
					}
		            if (is_string($return)) {
    		        	print_error($return, "etablissement.php?d=$referentiel->id");
	    		    }
	        		if (isset($form->redirect)) {
    	        		$SESSION->returnpage = $form->redirecturl;
					} 
					else {
        	    		$SESSION->returnpage = "$CFG->wwwroot/mod/referentiel/etablissement.php?d=$referentiel->id";
	        	    }
					add_to_log($course->id, "referentiel", "update",
            	           "mise a jour etablissement ",
                           "$form->etablissement_id", "");
			break;
			
            
			default:
            	// print_error("No mode defined");
        }
       	/*
    	if (!empty($SESSION->returnpage)) {
            $return = $SESSION->returnpage;
	        unset($SESSION->returnpage);
    	    redirect($return);
        } 
		else {
	    	redirect("student.php?d=$referentiel->id");
    	}
		
        exit;
		*/
		$mode='listeetab';
	}

	// afficher les formulaires

    unset($SESSION->modform); // Clear any old ones that may be hanging around.

    $modform = "etablissement.html";

/// Can't use this if there are no etablissement
/*
    if (has_capability('mod/referentiel:managetemplates', $context)) {
        if (!record_exists('referentiel_institution','referentielid',$referentiel->id)) {      // Brand new referentielbase!
            redirect($CFG->wwwroot.'/mod/referentiel/etablissement.php?d='.$referentiel->id);  // Redirect to field entry
        }
    }
*/

	/// RSS and CSS and JS meta
    $meta = '<link rel="stylesheet" type="text/css" href="activite.css" />
<link rel="stylesheet" type="text/css" href="certificate.css" />';

	/// Print the page header
	$strreferentiel = get_string('modulenameplural','referentiel');
	$stretablissement = get_string('etablissement','referentiel');
	$icon = '<img class="icon" src="'.$CFG->wwwroot.'/mod/referentiel/icon.gif" alt="'.get_string('modulename','referentiel').'"/>';
	$strpagename=get_string('etablissements','referentiel');
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
	$currentgroup = setup_and_print_groups($course, $groupmode, 'etablissement.php?d='.$referentiel->id);

	print_heading(format_string($referentiel->name));
	
	
	/// Print the tabs
	if (!isset($mode) || ($mode=="")){
		$mode='scolarite';		
	}
	
	// DEBUG
	// echo "<br /> MODE : $mode\n";
	
	if (isset($mode) && (($mode=="deleteetab") || ($mode=="updateetab"))){
		$currenttab ='listeetab';		
	}
	else{
		$currenttab = $mode;
	}
	if ($currenttab == 'addetab'){
			$currenttab = 'manageetab';
	}
	
    if ($etablissement_id) {
       	$editentry = true;  //used in tabs
    }
	
	include('tabs.php');
	// DEBUG
	// echo "<br /> MODE : $mode  ; CURRENTTABLE : $currenttab \n";
	// exit;
    print_heading_with_help($stretablissement, 'etablissement', 'referentiel', $icon);
	if ($mode=='listeetab'){
		referentiel_print_liste_etablissements($mode, $referentiel, $search); 
	}
	else {
		print_simple_box_start('center', '', '', 5, 'generalbox', $referentiel->name);
    	
		// formulaires
		if ($mode=='updateetab'){
			// recuperer l'id du etablissement après l'avoir genere automatiquement et mettre en place les competences
			
			if ($etablissement_id) { // id etablissement
    	   		if (! $record = get_record('referentiel_institution', 'id', $etablissement_id)) {
		            print_error('etablissement ID is incorrect');
    		    }
			}
			else{
				print_error('etablissement ID is incorrect');
			}
			$modform = "etablissement.html";
		}
		else if ($mode=='deleteetab'){
			// recuperer l'id du etablissement après l'avoir genere automatiquement et mettre en place les competences
			
			if ($etablissement_id) { // id etablissement
    	   		if (! $record = get_record('referentiel_institution', 'id', $etablissement_id)) {
		            print_error('etablissement ID is incorrect');
    		    }
			}
			else{
				print_error('etablissement ID is incorrect');
			}
			$modform = "etablissement.html";
		}
		else if ($mode=='addetab'){
			// genere automatiquement
			if (!$etablissement_id){
				// confirmer la création d'un nouvel établissement ?
				$modform = "etablissement_add.html";
				// $etablissement_id=referentiel_genere_etablissement();
			}
			else { // id etablissement
    	   		if (! $record = get_record('referentiel_institution', 'id', $etablissement_id)) {
		            print_error('etablissement ID is incorrect');
    		    }
				$mode='updateetab';
				$modform = "etablissement.html";
			}
		}
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
    	 	notice("ERREUR : No file found at : $modform)", "etablissement.php?d=$referentiel->id");
    	}
		
		include_once($modform);
	    print_simple_box_end();
	}
   	
    print_footer($course);

?>
