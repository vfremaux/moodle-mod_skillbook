<?php  // $Id: delete.php,v 1.0 2009/08/01/ 00:00:00 jfruitet Exp $
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
* @version $Id: delete.php,v 1.0 2009/08/01/ 00:00:00 jfruitet Exp $
* @author Martin Dougiamas, Howard Miller, and many others.
*         {@link http://moodle.org}
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package referentiel
*/


    require_once('../../config.php');
    require_once('lib.php');
    // require_once('pagelib.php'); // ENTETES
	
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
    	    	if (isset($SESSION->returnpage) && !empty($SESSION->returnpage)) {
	            	$return = $SESSION->returnpage;
    		        unset($SESSION->returnpage);
   	        		redirect($return);
       			} 
				else {
    		        redirect("$CFG->wwwroot/course/view.php?id=$course->id");
   	    		}
       			exit;
			}
		}
		
		// variable d'action
		if (!empty($form->cancel)){
			if ($form->cancel == get_string("quit", "referentiel")){
				// Abandonner
    	    	if (isset($SESSION->returnpage) && !empty($SESSION->returnpage)) {
	            	$return = $SESSION->returnpage;
    		        unset($SESSION->returnpage);
   	        		redirect($return);
       			} 
				else {
					
    		        redirect("$CFG->wwwroot/course/view.php?id=$course->id");
   	    		}
				
       			exit;
			}
		}
		
		// variable d'action
		else if (!empty($form->delete)){
			if ($form->delete == get_string("delete")){
				// Suppression instances
				if ($form->action=="supprimerinstances"){
					// enregistre les modifications
					if (isset($form->t_ref_instance) && ($form->t_ref_instance) && is_array($form->t_ref_instance)){
						/*
						if (isset($form->referentiel_id) && ($form->referentiel_id>0)){
							$records_instance=referentiel_referentiel_list_of_instance($form->referentiel_id);
						*/
						while (list($key, $val)=each($form->t_ref_instance)){
							if ($val){
								// suppression sans confirmation 
								/*
								// REPRIS DE course/mod.php
								*/
								// DEBUG
								// echo '<br />'. $key.' : '.$val."\n";
								$sql = "SELECT * FROM {$CFG->prefix}course_modules WHERE module = ".$cm->module." AND instance=".$val." ";
								$courses_modules = get_records_sql($sql);
								
								if ($courses_modules){
  									foreach($courses_modules as $course_module){
										if (!$courses_modules) {
            								print_error("This course module doesn't exist");
        								}
										else{
											if (! $course_record = get_record("course", "id", $course_module->course)) {
            									print_error("This course doesn't exist");
    	    								}
											// echo '<br />MODULE<br />'."\n";
											// print_r($coursemodule);
											// echo '<br />COURSE :<br />'."\n";
											// print_r($course_record);
											
											require_login($course_module->course); // needed to setup proper $COURSE
			        						$context_course = get_context_instance(CONTEXT_COURSE, $course_module->course);
        									require_capability('moodle/course:manageactivities', $context_course);
											
											$that_instance = get_record('referentiel', 'id', $course_module->instance);
											// echo '<br />INSTANCE :<br />'."\n";
											// print_r($that_instance );
											// exit;
											
											if 	($that_instance){
												if (!referentiel_delete_instance($that_instance->id)) {
			                    					print_error("Could not delete that referentiel instance");
            			    					}
					    			            if (! delete_course_module($course_module->id)) {
                    								print_error("Could not delete the referentiel (coursemodule)");
                								}
								                if (! delete_mod_from_section($course_module->id, "$course_module->section")) {
            			        					print_error("Could not delete the referentiel from that section");
                								}
												
												rebuild_course_cache($course_record->id);
												$msg=get_string('instance_deleted', 'referentiel').' '.$that_instance->name;
				    							add_to_log($course->id, "referentiel", "delete", "delete.php?d=".$referentiel->id, $msg, $cm->module);
											}
										}
									}
								}
							}
						}
					}
					
					if (isset($form->referentiel_id) && ($form->referentiel_id>0)){
						$records_instance=referentiel_referentiel_list_of_instance($form->referentiel_id);
						if ($records_instance){
							$msg=get_string("suppression_referentiel_impossible", "referentiel")." ".$form->referentiel_id;
							print_error("$msg", "$CFG->wwwroot/course/view.php?id=$course->id");
						}
						else{
							// suppression du referentiel
							$return=referentiel_delete_referentiel_domaines($form->referentiel_id);
							if (!isset($return) || (!$return)) {
    	        				print_error("Could not delete $msg", "view.php?d=$referentiel->id&amp;noredirect=1");
        	    			}
	            			if (is_string($return)) {
    	        				print_error($return, "view.php?d=$referentiel->id&amp;noredirect=1");
	    	    			}
							
							$msg=get_string('deletereferentiel', 'referentiel').' '.$form->referentiel_id;
		    				add_to_log($course->id, "referentiel", "delete", "delete.php?d=".$referentiel->id, $msg, $cm->module);
							redirect("$CFG->wwwroot/course/view.php?id=$course->id");
							exit;
						}
					}
				}
				// Suppression
				elseif ($form->action=="modifierreferentiel"){
					// enregistre les modifications
					if (isset($form->referentiel_id) && ($form->referentiel_id>0)){
						$records_instance=referentiel_referentiel_list_of_instance($form->referentiel_id);
						if ($records_instance){
							$msg=get_string("suppression_referentiel_impossible", "referentiel")." ".$form->referentiel_id;
							print_error("$msg", "view.php?d=$referentiel->id&amp;noredirect=1");
						}
						else{
							// suppression du referentiel
							$return=referentiel_delete_referentiel_domaines($form->referentiel_id);
							if (!isset($return) || (!$return)) {
    	        				print_error("Could not delete $msg", "view.php?d=$referentiel->id&amp;noredirect=1");
        	    			}
	            			if (is_string($return)) {
    	        				print_error($return, "view.php?d=$referentiel->id&amp;noredirect=1");
	    	    			}
							
							if ($return) {
								// Mise à jour de la reference du referentiel dans l'instance de certification
								referentiel_de_associe_referentiel_instance($form->instance);
							}
							
							$msg=get_string('deletereferentiel', 'referentiel').' '.$form->referentiel_id;
		    				add_to_log($course->id, "referentiel", "delete", "delete.php?d=".$referentiel->id, $msg, $cm->module);
							redirect("$CFG->wwwroot/course/view.php?id=$course->id");
							exit;	
						}
					}
				}
				
	        	if (isset($form->redirect)) {
    	        	$SESSION->returnpage = $form->redirecturl;
        	    } 
				else {
            		$SESSION->returnpage = "$CFG->wwwroot/mod/referentiel/view.php?id=$cm->id&amp;noredirect=1";
	            }
			}
		}
	}
	
	// afficher les formulaires

    // unset($SESSION->modform); // Clear any old ones that may be hanging around.

    $modform = "delete.html";

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
        notice("ERREUR : No file found at : $modform)", "view.php?id=$course->id&amp;d=$referentiel->id&amp;noredirect=1");
    }
	
	/// RSS and CSS and JS meta
    $meta = '';

	/// Print the page header
	$strreferentiels = get_string('modulenameplural','referentiel');
	$strreferentiel = get_string('referentiel','referentiel');
	$strmessage = get_string('supprimer_referentiel','referentiel');		
	$icon = '<img class="icon" src="'.$CFG->wwwroot.'/mod/referentiel/icon.gif" alt="'.get_string('modulename','referentiel').'"/>';
	$strpagename=get_string('supprimer_referentiel','referentiel');
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
    /*
 		/// Check to see if groups are being used here
		/// find out current groups mode
   		$groupmode = groups_get_activity_groupmode($cm);
	    $currentgroup = groups_get_activity_group($cm, true);
    	groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/referentiel/delete.php?d='.$referentiel->id.'&amp;mode='.$mode.'&amp;select_acc='.$select_acc);
    */		
	}
	else{
		// 1.8
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
		false); // If true, return the visible elements of the header instead of echoing them.
		/*
		// 1.8
		$groupmode = groupmode($course, $cm);
    $currentgroup = setup_and_print_groups($course, $groupmode, $CFG->wwwroot . '/mod/referentiel/delete.php?d='.$referentiel->id.'&amp;mode='.$mode.'&amp;select_acc='.$select_acc);
    */						
	}	
	print_heading(format_string($referentiel->name));
	/// Print the tabs
	if (!isset($mode)){
		$mode='deletereferentiel'; // un seul mode possible
	}
	$currenttab = 'deletereferentiel';
    if ($referentiel->id) {
       	$editentry = true;  //used in tabs
    }
	include('tabs.php');

    print_heading_with_help($strmessage, 'delete', 'referentiel', $icon);
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
			$appli_appelante="delete.php";
			include_once("pass.html");
		}
		else{
			include_once($modform);
	    }
		print_simple_box_end();
	}
	
	print_footer($course);
	
?>
