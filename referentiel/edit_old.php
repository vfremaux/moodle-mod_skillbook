<?php  // $Id: edit.php,v 1.0 2008/04/29/ 00:00:00 jfruitet Exp $
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
* Modification du referentiel
* association a l'instance
*
* @version $Id: edit.php,v 2.0 2009/08/04/ 00:00:00 jfruitet Exp $
* @author Martin Dougiamas, Howard Miller, and many others.
*         {@link http://moodle.org}
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package referentiel
*/


    require_once('../../config.php');
    require_once('lib.php');
    require_once('pagelib.php'); // ENTETES
	
	// PAS DE RSS
    // require_once("$CFG->libdir/rsslib.php");

    $id    = optional_param('id', 0, PARAM_INT);    // course module id	
    $d     = optional_param('d', 0, PARAM_INT);    // referentiel instance id
	$pass  = optional_param('pass', 0, PARAM_INT);    // mot de passe ok
    $checkpass = optional_param('checkpass','', PARAM_ALPHA); // mot de passe fourni
	
    $mode = optional_param('mode','', PARAM_ALPHA);	
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
    } 
	else{
        // print_error('You cannot call this script in that way');	
		error(get_string('erreurscript','referentiel','Erreur01 : edit.php'));
	}

	$returnlink="$CFG->wwwroot/course/view.php?id=$course->id";
    require_login($course->id, false, $cm);

    if (!isloggedin() or isguest()) {
        redirect($returnlink);
    }

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

	// lien vers le referentiel lui-meme
	if (isset($referentiel->referentielid) && ($referentiel->referentielid>0)){
	    if (! $referentiel_referentiel = get_record('referentiel_referentiel', 'id', $referentiel->referentielid)) {
    		print_error('Referentiel id is incorrect '.$referentiel->referentielid);
    	}
    }
	else{
		// rediriger vers la creation du referentiel
		$returnlink="$CFG->wwwroot/mod/referentiel/add.php?d=$referentiel->id";
        redirect($returnlink);
	}
	
    if ($referentiel->id) {    // So do you have access?
        if (!has_capability('mod/referentiel:writereferentiel', $context) or !confirm_sesskey() ) {
            error(get_string('noaccess','referentiel'));
        }
    }

	// RECUPERER LES FORMULAIRES
    if (isset($SESSION->modform)) {   // Variables are stored in the session
        $form = $SESSION->modform;
        unset($SESSION->modform);
    } 
	else {
        $form = (object)$_POST;
    }

    // Traitement des POST
	$msg="";
    
	if (!empty($course) && !empty($cm) && !empty($referentiel) && isset($form)) {    
	
	// DEBUG
	// echo "<br />DEBUG : edit.php :: Ligne 122<br />\n";
	// print_r($form);

		// add, delete or update form submitted	
		
		// le mot de passe est-il actif ?
		// cette fonction est due au paramétrage
		if (!$pass && ($checkpass=='checkpass') && !empty($form->password) && $referentiel_referentiel){
			$pass=referentiel_check_pass($referentiel_referentiel, $form->password);
			if (!$pass){
				// Abandonner
    		    redirect("view.php?id=$cm->id");
       			exit;
			}
		}
		
		// variable d'action
		if (!empty($form->cancel)){
			if ($form->cancel == get_string("quit", "referentiel")){
				// Abandonner
    		    redirect("view.php?id=$cm->id");
       			exit;
			}
		}
		
		// variable d'action
		else if (!empty($form->delete)){
			if ($form->delete == get_string("delete")){
				
				// Suppression
				if (($form->action=="modifierdomaine") || ($form->action=="modifiercompetence") || ($form->action=="modifieritem")){
					if ($form->action=="modifierdomaine"){
						// enregistre les modifications
						if (isset($form->domaine_id) && ($form->domaine_id>0)){
							$return=referentiel_supprime_domaine($form->domaine_id);
							$msg=get_string("referentiel", "referentiel")." ".$form->referentiel_id." ".get_string("domaine", "referentiel")." ".$form->domaine_id;
						}
					}
					else if ($form->action=="modifiercompetence"){
						// enregistre les modifications
						if (isset($form->competence_id) && ($form->competence_id>0)){
							$return=referentiel_supprime_competence($form->competence_id);
							$msg=get_string("referentiel", "referentiel")." ".$form->referentiel_id." ".get_string("competence", "referentiel")." ".$form->competence_id;
						}
					}
					else if ($form->action=="modifieritem"){
						// enregistre les modifications
						if (isset($form->item_id) && ($form->item_id>0)){
							$return=referentiel_supprime_item($form->item_id);
							$msg=get_string("referentiel", "referentiel")." ".$form->referentiel_id." ".get_string("item", "referentiel")." ".$form->item_id;
						}
					}
					
					if (!isset($return) || (!$return)) {
    	    	    	print_error("Could not delete $msg", "view.php?d=$referentiel->id");
        	    	}
		            if (is_string($return)) {
    		        	print_error($return, "view.php?d=$referentiel->id");
	    		    }
					
					if ($return) {
						// Mise à jour de la liste de competences dans le referentiel
						$liste_codes_competence=referentiel_new_liste_codes_competence($form->referentiel_id);
						// echo "<br />LISTE_CODES_COMPETENCE : $liste_codes_competence\n";
						referentiel_set_liste_codes_competence($form->referentiel_id, $liste_codes_competence);
					}
					
		    		add_to_log($course->id, "referentiel", "delete", "edit.php?d=".$referentiel->id, $msg, $cm->module);
				}
				
	        	if (isset($form->redirect)) {
    	        	$SESSION->returnpage = $form->redirecturl;
        	    } 
				else {
            		$SESSION->returnpage = "$CFG->wwwroot/mod/referentiel/view.php?id=$cm->id";
	            }
			}
		}
		
		// variable d'action Enregistrer
		else if (!empty($form->action)){
			if (isset($form->mode) && ($form->mode=="add")){
				// creer le referentiel
				if ($form->action=="modifierreferentiel"){
					// enregistre les modifications
					$return=referentiel_add_referentiel_domaines($form);
					$msg=get_string("referentiel", "referentiel")." ".$form->referentiel_id;
					$action="update";
				}
			}
			else if (isset($form->mode) && ($form->mode=="update")){
				if ($form->action=="modifierreferentiel"){
					// enregistre les modifications
		// DEBUG
		// echo "<br /> DEBUG :: edit.php :: 226 : MODE : $form->mode ACTION : $form->action<br />\n";
		// echo "<br />FORM<br />\n";		
		// print_r($form);
					// gestion du mot de passe
					
					if (isset($form->suppression_pass_referentiel) && ($form->suppression_pass_referentiel==1)){
						$form->old_pass_referentiel = '';
					}
					else if (isset($form->password) && ($form->password!='') 
						&& 
						(
							isset($form->old_pass_referentiel) && ($form->old_pass_referentiel!='') && ($form->old_pass_referentiel != md5($form->password))
							|| 
							isset($form->old_pass_referentiel) && ($form->old_pass_referentiel=='') 
						)
					){
						$form->old_pass_referentiel = md5($form->password);
					}
					
					$return=referentiel_update_referentiel_domaines($form);
					
					$msg=get_string("referentiel", "referentiel")." ".$form->referentiel_id;
					$action="update";
					
				}
				else if ($form->action=="modifierdomaine"){
					// enregistre les modifications
					if (isset($form->domaine_id) && ($form->domaine_id>0)){
						$return=referentiel_update_domaine($form);
						$msg=get_string("referentiel", "referentiel")." ".$form->referentiel_id." ".get_string("domaine", "referentiel")." ".$form->domaine_id;
						$action="update";
						
					}
				}
				else if ($form->action=="modifiercompetence"){
					// enregistre les modifications
					if (isset($form->competence_id) && ($form->competence_id>0)){
						$return=referentiel_update_competence($form);
						$msg=get_string("referentiel", "referentiel")." ".$form->referentiel_id." ".get_string("competence", "referentiel")." ".$form->competence_id;
						$action="update";
					}
				}
				else if ($form->action=="modifieritem"){
					// enregistre le nouveau domaine
					$return=referentiel_update_item($form);
					$msg=get_string("referentiel", "referentiel")." ".$form->referentiel_id." ".get_string("item", "referentiel")." ".$form->item_id;
					$action="update";
				}
				else if ($form->action=="newdomaine"){
					// enregistre le nouveau domaine
					$return=referentiel_add_domaine($form);
					$msg=get_string("referentiel", "referentiel")." ".$form->referentiel_id." ".get_string("domaine", "referentiel")." ".$return;
					$action="add";
				}
				else if ($form->action=="newcompetence"){
					// enregistre le nouvel item
					$return=referentiel_add_competence($form);
					$msg=get_string("referentiel", "referentiel")." ".$form->referentiel_id." ".get_string("competence", "referentiel")." ".$return;
					$action="add";
				}
				else if ($form->action=="newitem"){
					// enregistre les modifications
					$return=referentiel_add_item($form);
					$msg=get_string("referentiel", "referentiel")." ".$form->referentiel_id." ".get_string("item", "referentiel")." ".$return;
					$action="add";
				}
				
				if (!$return) {
					print_error("Could not update instance $form->referentiel_id of referentiel", "view.php?id=$cm->id");
        		}
	        	if (is_string($return)) {
					print_error($return, "view.php?id=$cm->id");
	    		}
				
		        if (isset($form->redirect)) {
    		    	$SESSION->returnpage = $form->redirecturl;
        		} else {
            		$SESSION->returnpage = "$CFG->wwwroot/mod/referentiel/view.php?id=cm->id";
	        	}
			}
			
			if (isset($action)){
				add_to_log($course->id, "referentiel", $action, "edit.php?d=".$referentiel->id, $msg, $cm->module);
			}
			
			/*
    	    if (!empty($SESSION->returnpage)) {
            	$return = $SESSION->returnpage;
	            unset($SESSION->returnpage);
    	        redirect($return);
        	} 
			else {
	            redirect("view.php?d=$referentiel->id");
    	    }
        	exit;
			*/
		}
	}
	
	// afficher les formulaires

    // unset($SESSION->modform); // Clear any old ones that may be hanging around.

    $modform = "edit.html";

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
    $meta = '';

	/// Print the page header
	$strreferentiels = get_string('modulenameplural','referentiel');
	$strreferentiel = get_string('referentiel','referentiel');
	$strmessage = get_string('modifier_referentiel','referentiel');		
	$icon = '<img class="icon" src="'.$CFG->wwwroot.'/mod/referentiel/icon.gif" alt="'.get_string('modulename','referentiel').'"/>';
	$strpagename=get_string('modifier_referentiel','referentiel');
	if (function_exists('build_navigation')){
		// Moodle 1.9
		$navigation = build_navigation($strpagename, $cm);
		
		print_header($course->shortname.': '.$strreferentiel, $course->fullname, $navigation, 
		'', // focus
		$meta,
		true, // page is cacheable
		update_module_button($cm->id, $course->id, get_string('modulename', 'referentiel')), // HTML code for a button (usually for module editing)
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
		update_module_button($cm->id, $course->id, get_string('modulename', 'referentiel')), // HTML code for a button (usually for module editing)
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
		
		// verifer si le mot de passe est fourni
		if (!$pass 
			&& 
			$referentiel
			&& 
			$referentiel_referentiel
			&& 
			isset($referentiel_referentiel->password)
			&&
			($referentiel_referentiel->password!='') 
			&& 
			isset($referentiel_referentiel->referentielauthormail)
			&&
			(referentiel_get_user_mail($USER->id)!=$referentiel_referentiel->referentielauthormail)){
			// demander le mot de passe
			$appli_appelante="edit.php";
			include_once("pass.html");
		}
		else{
			include_once($modform);
	    }
		print_simple_box_end();
	}
	
	print_footer($course);
	
?>
