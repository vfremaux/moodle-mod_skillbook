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
    require_once('print_lib_student.php');	// AFFICHAGES 

	// PAS DE RSS
    // require_once("$CFG->libdir/rsslib.php");

    $id    = optional_param('id', 0, PARAM_INT);    // course module id    
	$d     = optional_param('d', 0, PARAM_INT);    // referentielbase id
	
    $userid   = optional_param('userid', 0, PARAM_INT);    //record student id
	$student_id   = optional_param('student_id', 0, PARAM_INT);    //record student id
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

  $context = get_context_instance(CONTEXT_MODULE, $cm->id);

	if (isset($userid) && ($userid>0)) { 
		// id student
		$record = get_record('referentiel_student', 'userid', $userid);
		
		if (!$record) {
            $record=referentiel_add_student_user($userid);
        }
	}
	if (isset($userid) && ($userid>0)) { 
		// id student
        if (! $record = get_record('referentiel_student', 'userid', $userid)) {
            print_error('student userid is incorrect');
        }
	}
	
	if (isset($student_id) && ($student_id>0)) { 
		// id student
        if (! $record = get_record('referentiel_student', 'id', $student_id)) {
            print_error('student id is incorrect');
        }
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

    
	/// If it's hidden then it's don't show anything.  :)
	/// Some capability checks.
  if (empty($cm->visible) and !has_capability('moodle/course:viewhiddenactivities', $context)) {
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
	
  if ($userid) {    // So do you have access?
        if (!(has_capability('mod/referentiel:write', $context) 
			or ($USER->id==$userid)) or !confirm_sesskey() ) {
            error(get_string('noaccess','referentiel'));
        }
  }
	
	// selecteur
	$userid_filtre=0;
	
	// RECUPERER LES FORMULAIRES
    if (isset($SESSION->modform)) {   // Variables are stored in the session
        $form = $SESSION->modform;
        unset($SESSION->modform);
    } else {
        $form = (object)$_POST;
    }

	/// selection etablissement
   	if (isset($mode) && ($mode=='selectetab')
		&& isset($userid) && ($userid>0)
		&& isset($etablissement_id) && ($etablissement_id>0)
		&& confirm_sesskey() ){
		referentiel_student_set_etablissement($userid, $etablissement_id);
		$mode='liststudent';
    }

	if ($cancel) {
        if (!empty($SESSION->returnpage)) {
            $return = $SESSION->returnpage;
            unset($SESSION->returnpage);
            redirect($return);
        } else {
            redirect('student.php?d='.$referentiel->id);
        }
    }

	/// selection d'utilisateurs
    if (isset($action) && ($action=='selectuser')
		&& isset($form->userid) && ($form->userid>0)
		&& confirm_sesskey() ){
		$userid_filtre=$form->userid;
		// DEBUG
		// echo "<br />DEBUG :: student.php :: Ligen 172 :: ACTION : $action  User: $userid_filtre\n";
		unset($form);
		unset($action);
		// exit;
    }

 	
	/// Delete any requested records
    if (isset($delete) && ($delete>0 )
			&& confirm_sesskey() 
			&& (has_capability('mod/referentiel:managecertif', $context) or referentiel_student_isowner($delete))) {
        if ($confirm = optional_param('confirm',0,PARAM_INT)) {
            if (referentiel_delete_student_user($delete)){
				// DEBUG
				// echo "<br /> student REMIS A ZERO\n";
				// exit;
				add_to_log($course->id, 'referentiel', 'record delete', "student.php?d=$referentiel->id", $delete, $cm->id);
                // notify(get_string('recorddeleted','referentiel'), 'notifysuccess');
            }
		} 
    }
	
	if (!empty($referentiel) && !empty($course) 
		&& isset($form) && isset($form->mode)
		)
	{
		// add, delete or update form submitted	
		$addfunction    = "referentiel_add_student";
        $updatefunction = "referentiel_update_student";
        $deletefunction = "referentiel_delete_student";
		
		switch ($form->mode) {
    		case "updatestudent":
			
				// DEBUG
				// echo "<br /> $form->mode\n";
				
				if (isset($form->name)) {
   		        	if (trim($form->name) == '') {
       		        	unset($form->name);
           		    }
               	}
				
				if (isset($form->delete) && ($form->delete==get_string('delete'))){
					// suppression 	
					// echo "<br />SUPPRESSION\n";
	    	        $return = $deletefunction($form);
    	    	    if (!$return) {
							/*
            	        	if (file_exists($moderr)) {
                	        	$form = $form;
	                   		    include_once($moderr);
    	                   		die;
	    	               	}
							*/
    	         	      	print_error("Could not update student $form->userid of the referentiel", "student.php?d=$referentiel->id");
        	    	}
	                if (is_string($return)) {
    	           	    print_error($return, "student.php?d=$referentiel->id");
	    	        }
	        	    if (isset($form->redirect)) {
    	                $SESSION->returnpage = $form->redirecturl;
        	       	} else {
            	       	$SESSION->returnpage = "$CFG->wwwroot/mod/referentiel/student.php?d=$referentiel->id";
	               	}
					
	    	        add_to_log($course->id, "referentiel", "delete",
            	          "mise a jour student $form->userid",
                          "$form->student_id", "");
					
				}
				else {
				// DEBUG
				// echo "<br /> UPDATE\n";
				
	    	    	$return = $updatefunction($form);
    	    	    if (!$return) {
					/*
            		    if (file_exists($moderr)) {
                			$form = $form;
                    		include_once($moderr);
                        	die;
	                    }
					*/
    	            	print_error("Could not update student $form->userid of the referentiel", "student.php?d=$referentiel->id");
					}
		            if (is_string($return)) {
    		        	print_error($return, "student.php?d=$referentiel->id");
	    		    }
	        		if (isset($form->redirect)) {
    	        		$SESSION->returnpage = $form->redirecturl;
					} 
					else {
        	    		$SESSION->returnpage = "$CFG->wwwroot/mod/referentiel/student.php?d=$referentiel->id";
	        	    }
					add_to_log($course->id, "referentiel", "update",
            	           "mise a jour student $form->userid",
                           "$form->student_id", "");
    	    	}

			break;
			
			case "addstudent":
				if (!isset($form->name) || trim($form->name) == '') {
        			$form->name = get_string("modulename", "referentiel");
        		}
				$return = $addfunction($form);
				if (!$return) {
    	        	/*
					if (file_exists($moderr)) {
    	    	    	$form = $form;
        	    	    include_once($moderr);
            	    	die;
					}
	            	*/
					print_error("Could not add a new student to the referentiel", "student.php?d=$referentiel->id");
				}
	        	if (is_string($return)) {
    	        	print_error($return, "student.php?d=$referentiel->id");
				}
				if (isset($form->redirect)) {
    	    		$SESSION->returnpage = $form->redirecturl;
				} 
				else {
					$SESSION->returnpage = "$CFG->wwwroot/mod/referentiel/student.php?d=$referentiel->id";
				}
				add_to_log($course->id, referentiel, "add",
                           "creation student $form->student_id ",
                           "$form->instance", "");
            break;
			
	        case "deletestudent":
				if (! $deletefunction($form)) {
	            	print_error("Could not delete student of  the referentiel");
                }
	            unset($SESSION->returnpage);
	            add_to_log($course->id, referentiel, "add",
                           "suppression student $form->userid ",
                           "$form->student_id", "");
            break;
            
			default:
            	// print_error("No mode defined");
        }
       	
    	if (!empty($SESSION->returnpage)) {
            $return = $SESSION->returnpage;
	        unset($SESSION->returnpage);
    	    redirect($return);
        } 
		else {
	    	redirect("student.php?d=$referentiel->id");
    	}
		
        exit;
	}

	// afficher les formulaires

    unset($SESSION->modform); // Clear any old ones that may be hanging around.

    $modform = "student.html";

/// Can't use this if there are no student
/*
    if (has_capability('mod/referentiel:managetemplates', $context)) {
        if (!record_exists('referentiel_student','referentielid',$referentiel->id)) {      // Brand new referentielbase!
            redirect($CFG->wwwroot.'/mod/referentiel/student.php?d='.$referentiel->id);  // Redirect to field entry
        }
    }
*/

	/// RSS and CSS and JS meta
	$meta = '<link rel="stylesheet" type="text/css" href="activite.css" />
<link rel="stylesheet" type="text/css" href="certificate.css" />';


	/// Print the page header
    $strreferentiel = get_string('modulenameplural','referentiel');
	$strstudent = get_string('student','referentiel');
	$icon = '<img class="icon" src="'.$CFG->wwwroot.'/mod/referentiel/icon.gif" alt="'.get_string('modulename','referentiel').'"/>';
	$strpagename=get_string('students','referentiel');
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
    	groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/referentiel/student.php?d='.$referentiel->id);		
	}
	else{

	    print_header_simple($referentiel->name, // title
		'', // heading
		"<a href='index.php?id=$course->id'>$strreferentiel</a> -> $referentiel->name", // navigation
		'', // focus
		$meta, // meta tag
		true, // page is cacheable
// 		update_module_button($cm->id, $course->id, get_string('modulename', 'referentiel')), // HTML code for a button (usually for module editing)
		update_module_button($cm->id, $course->id, get_string('modulename-intance', 'referentiel')), // HTML code for a button (usually for module editing)

        navmenu($course, $cm), // HTML code for a popup menu
		false, // use XML for this page
		'', // This text will be included verbatim in the <body> tag (useful for onload() etc)
		false) // If true, return the visible elements of the header instead of echoing them.
		;
		
		/// Check to see if groups are being used here
		/// find out current groups mode
	    // 1.9 $groupmode = groups_get_activity_groupmode($cm);
    	// 1.9 $currentgroup = groups_get_activity_group($cm, true);
		// 1.9 groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/referentiel/student.php?d='.$referentiel->id);
		// 1.8
		$groupmode = groupmode($course, $cm);
        $currentgroup = setup_and_print_groups($course, $groupmode, 'activite.php?d='.$referentiel->id);
		// 	$currentgroup = get_and_set_current_group($course, groupmode($course, $cm));
  		
	}
	/// Get all users that are allowed to submit activite
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

	
	print_heading(format_string($referentiel->name));
	
	
	/// Print the tabs
	if (!isset($mode) || ($mode=="")){
		$mode='liststudent';		
	}
	if (isset($mode) && ($mode=="student")){
		$mode='liststudent';		
	}
	
	// DEBUG
	// echo "<br /> MODE : $mode\n";
	
	if (isset($mode) && (($mode=="deletestudent") || ($mode=="updatestudent"))){
		$currenttab ='editstudent';
	}
	else{
		$currenttab = $mode;
	}
	
    if ($userid) {
       	$editentry = true;  //used in tabs
    }
	include('tabs.php');
	// DEBUG
	// echo "<br /> MODE : $mode  ; CURRENTTABLE : $currenttab \n";
	// exit;
    print_heading_with_help($strstudent, 'student', 'referentiel', $icon);
	
	if (($mode=='scolarite') || ($mode=='liststudent')){
		referentiel_print_liste_students($mode, $referentiel, $userid_filtre, $gusers); 
	}
	else {
        print_simple_box_start('center', '', '', 5, 'generalbox', $referentiel->name);
   	    
		// formulaires
		if ($mode=='updatestudent'){
			// recuperer l'id du student après l'avoir genere automatiquement et mettre en place les competences
			
			if ($userid) { // id student
   	    		if (! $record = get_record('referentiel_student', 'userid', $userid)) {
		            print_error('student ID is incorrect');
   			    }
			}
			else{
				print_error('student ID is incorrect');
			}
			$modform = "student_edit.html";
		}
		else if ($mode=='deletestudent'){
			// recuperer l'id du student après l'avoir genere automatiquement et mettre en place les competences
			
			if ($userid) { // id student
    	    	if (! $record = get_record('referentiel_student', 'userid', $userid)) {
					print_error('student ID is incorrect');
    	    	}
			}
			else{
				print_error('student ID is incorrect');
			}
			$modform = "student_edit.html";
		}
		
	    if (file_exists($modform)) {
	        if ($usehtmleditor = can_use_html_editor()) {
    	        $defaultformat = FORMAT_HTML;
        	    $editorfields = '';
	        } else {
    	        $defaultformat = FORMAT_MOODLE;
        	}
		}
		else {
    	    notice("ERREUR : No file found at : $modform)", "student.php?d=$referentiel->id");
    	}
		include_once($modform);
	    print_simple_box_end();
    } 
    print_footer($course);

?>
