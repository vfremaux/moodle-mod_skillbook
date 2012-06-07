<?php  // $Id: add.php,v 1.0 2008/04/29/ 00:00:00 jfruitet Exp $
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
* Creation d'une premiere version du referentiel
* association a l'instance
*
* @version $Id: add.php,v 1.0 2008/04/29/ 00:00:00 jfruitet Exp $
* @author Martin Dougiamas, Howard Miller, and many others.
*         {@link http://moodle.org}
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package referentiel
*/
	
    require_once('../../config.php');
    require_once($CFG->dirroot.'/mod/referentiel/lib.php');
	require_once($CFG->dirroot.'/mod/referentiel/print_lib_referentiel.php');

    $id    	= optional_param('id', 0, PARAM_INT);    // course module id	
    $d     	= optional_param('d', 0, PARAM_INT);    // referentiel instance id
	
	$action = optional_param('action','', PARAM_ALPHA); // pour distinguer differentes formes de creation de referentiel
    $mode 	= optional_param('mode','', PARAM_ALPHA);	

	$name_instance		= optional_param('name_instance','', PARAM_CLEAN);
	$description		= optional_param('description','', PARAM_CLEAN);
	$domainlabel    	= optional_param('domainlabel','', PARAM_CLEAN);
	$skilllabel 		= optional_param('skilllabel','', PARAM_CLEAN);
	$itemlabel			= optional_param('itemlabel','', PARAM_CLEAN);

    $sesskey     		= optional_param('sesskey', '', PARAM_ALPHA);
    $coursemodule     	= optional_param('coursemodule', 0, PARAM_INT);
    $section 			= optional_param('section', 0, PARAM_INT);	
    $module 			= optional_param('module', 0, PARAM_INT);
	$modulename     	= optional_param('modulename', '', PARAM_ALPHA);
	$instance 			= optional_param('instance', 0, PARAM_INT);
 	$select_acc = optional_param('select_acc', 0, PARAM_INT);      // coaching

	if ($d) {
        if (! $referentiel = get_record('referentiel', 'id', $d)) {
            print_error('Certification instance is incorrect');
        }
		if (! $course = get_record('course', 'id', $referentiel->course)) {
	            print_error('Course is misconfigured');
    	}
		if (! $cm = get_coursemodule_from_instance('referentiel', $referentiel->id, $course->id)) {
    	        print_error('Course Module ID is incorrect');
		}
	} elseif ($id) {
        if (! $cm = get_coursemodule_from_id('referentiel', $id)) {
        	print_error('Course Module ID was incorrect');
        }
        if (! $course = get_record('course', 'id', $cm->course)) {
            print_error('Course is misconfigured');
        }
        if (! $referentiel = get_record('referentiel', 'id', $cm->instance)) {
            print_error('Certification instance is incorrect');
        }
    } else {
        // print_error('You cannot call this script in that way');	
		print_error(get_string('erreurscript','referentiel','Erreur01 : add.php'));
	}
	
	if (!isset($mode)){
		$mode = 'add'; // un seul mode possible
	}
    
	$returnlink_on_error 	= "$CFG->wwwroot/course/view.php?id=$course->id";
	$returnlink 			= "$CFG->wwwroot/mod/referentiel/view.php?id=$cm->id&amp;noredirect=1";
	$returnlink_on_update 	= "$CFG->wwwroot/mod/referentiel/edit.php?id=$cm->id&amp;sesskey=".sesskey();
	
    require_login();
    if (!isloggedin() or isguest()) {
        redirect($returnlink_on_error);
    }
	
    // check role capability
	$context = get_context_instance(CONTEXT_MODULE, $cm->id);
	
    if ($referentiel->id) {    // So do you have access?
        if (!has_capability('mod/referentiel:writereferentiel', $context) or !confirm_sesskey() ) {
            print_error(get_string('noaccess','referentiel'));
        }
    } else {
		print_error('Referentiel instance is incorrect');
	}
	
	if (isset($referentiel->referentielid) && ($referentiel->referentielid > 0)){
		redirect($returnlink_on_update);
	}
	
	// GET FORM DATA

    if (isset($SESSION->modform)) {   // Variables are stored in the session
        $form = $SESSION->modform;
        unset($SESSION->modform);
    } else {
        $form = (object)$_POST;
    }
	
	if (!empty($course) && !empty($referentiel) && !empty($form)) {    
		// select form submitted	
		// DEBUG
		// echo "<br /> MODE : $mode ACTION : $action<br />\n";
		// echo "<br />FORM<br />\n";		
		// print_r($form);

		// variable d'action
		if (!empty($form->cancel)){
			if ($form->cancel == get_string('quit', 'referentiel')){
				// Abandonner
    		    redirect($returnlink_on_error);
       			exit;
			}
		}
		
		if (!empty($cancel)){
			if ($cancel == get_string('quit', 'referentiel')){
				// Abandonner
    		    redirect($returnlink_on_error);
       			exit;
			}
		}

		if (!empty($action) && ($action == "modifierinstance")){
			// modifier l'instance du referentiel
			$return = referentiel_update_instance($form);
        	if (!$return) {
				print_error("ERROR 1 : Could not update the referentiel instance", $returnlink_on_error);
			}
			//  recharger le referentiel modifie
	        if (! $referentiel = get_record('referentiel', 'id', $referentiel->id)) {
    	        print_error('Certification instance '.$return.' is incorrect');
        	}
			
    	    add_to_log($course->id, "referentiel", "update", "add.php?d=$referentiel->id", "$form->name $referentiel->id");
			// pas de redirection car il faut peut être encore selectionner le referentiel
		}
		
		if (!empty($action) && (($action == "selectreferentiel") || ($action == "importreferentiel"))){
			// retour de selection ou d'importation : associer l'instance au referentiel
			// echo "<br />add.php :: 145 :: ACTION : $action ; FORM<br />\n";		
			// print_r($form);
			
			$return = referentiel_associe_referentiel_instance($form);
        	if (!$return) {
				print_error("Error 2 : Could not update the referentiel instance", $returnlink_on_error);
			}
			//  recharger le referentiel modifie
	        if (! $referentiel = get_record('referentiel', 'id', $referentiel->id)) {
    	        print_error('Certification instance '.$return.' is incorrect');
        	}			
    	    add_to_log($course->id, "referentiel", "update", "add.php?id=$cm->id", "$form->name_instance $referentiel->id");
			// echo "<br />add.php :: 157 :: RETOUR : $returnlink ; FORM<br />\n";					
			redirect($returnlink);
	        exit;
		}
		
		if (!empty($action) && ($action == "modifierreferentiel")){
			// sauvegarder le referentiel
			// echo "<br />add.php :: 189 :: ACTION : $action ; FORM<br />\n";		
			$return_referentiel_id = referentiel_add_referentiel_domaines($form);
    	    if (!$return_referentiel_id) {
				print_error(get_string('erreur_creation','referentiel'), $returnlink_on_error);
			}
    	    if (is_string($return_referentiel_id)) {
        		print_error($return_referentiel_id, $returnlink_on_error);
	        }
			
    	    add_to_log($course->id, "referentiel", "write", "add.php?id=$cm->id", "$form->name $return_referentiel_id");
			// DEBUG
			// echo "<br /> add.php :: 200 :: INSTANCE : $form->instance : REFERENTIEL : $return_referentiel_id<br />\n";

			// associer le referentiel
			$form->new_referentiel_id = $return_referentiel_id;
			// echo "<br />add.php :: 204 :: FORM<br />\n";		
			// print_r($form);

			$return = referentiel_associe_referentiel_instance($form);
        	if (!$return) {
				print_error("Error 3 : Could not update the referentiel instance 3 ", $returnlink_on_error);
			}
			//  recharger le referentiel modifie
	        if (! $referentiel = get_record('referentiel', 'id', $referentiel->id)) {
    	        print_error('Certification instance '.$return_referentiel_id.' is incorrect');
        	}
			
    	    add_to_log($course->id, "referentiel", "update", "add.php?d=$referentiel->id", "$form->name_instance $referentiel->id");
			// echo "<br />add.php :: 276 :: EXIT<br />\n";		
			redirect($returnlink);
	        exit;
		}
	}
	
	/// RSS and CSS and JS meta
    $meta = '';

	/// Print the page header
	$strreferentiels = get_string('modulenameplural','referentiel');
	$strreferentiel = get_string('referentiel','referentiel');
	$strmessage = get_string('referentiel','referentiel');		
	$icon = '<img class="icon" src="'.$CFG->wwwroot.'/mod/referentiel/icon.gif" alt="'.get_string('modulename','referentiel').'"/>';
	$strpagename=get_string('modifier_referentiel','referentiel');
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
		print_header_simple("$course->shortname : $strreferentiels", // title
		"", // heading
		"<a href='index.php?id=$course->id'>$strreferentiel</a> -> $strmessage", // navigation
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
    print_heading_with_help($strmessage, 'referentiel', 'referentiel', $icon);
	require_once('add.html');
	print_footer($course);
?>
