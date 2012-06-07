<?php  // $Id: souscription.php,v 1.0 2010/01/10 00:00:00 jfruitet Exp $
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
	require_once('print_lib_activite.php'); // AFFICHAGES ACTIVITES
	require_once('lib_task.php');
	require_once('print_lib_task.php');	// AFFICHAGES TACHES
	
	// PAS DE RSS
    // require_once("$CFG->libdir/rsslib.php");

    $id    = optional_param('id', 0, PARAM_INT);    // course module id    
	$d     = optional_param('d', 0, PARAM_INT);    // referentielbase id
	
    $task_id   = optional_param('task_id', 0, PARAM_INT);    //record task id
    // $import   = optional_param('import', 0, PARAM_INT);    // show import form

    $action  	= optional_param('action','', PARAM_CLEAN); // pour distinguer differentes formes de traitements
    $mode       = optional_param('mode','', PARAM_ALPHA);	
    $add        = optional_param('add','', PARAM_ALPHA);
    $update     = optional_param('update', 0, PARAM_INT);
    $delete     = optional_param('delete', 0, PARAM_INT);
    $select    	= optional_param('select', 0, PARAM_INT);
    $course     = optional_param('course', 0, PARAM_INT);
    $groupmode  = optional_param('groupmode', -1, PARAM_INT);
    $cancel     = optional_param('cancel', 0, PARAM_BOOL);
	$approve    = optional_param('approve', 0, PARAM_INT);
	$souscription    = optional_param('souscription', 0, PARAM_INT);
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
		print_error(get_string('erreurscript','referentiel','Erreur01 : task.php'));
	}
	
	$context = get_context_instance(CONTEXT_MODULE, $cm->id);
  	
	if ($task_id) { // id task
        if (! $record = get_record('referentiel_task', 'id', $task_id)) {
            print_error('task ID is incorrect');
        }
	}
	
	require_login($course->id, false, $cm);


	if (!isloggedin() or isguest()) {
	    redirect('view.php?id='.$cm->id.'noredirect=1');
	}
	

	/// If it's hidden then it's don't show anything.  :)
	/// Some capability checks.
  	if (empty($cm->visible) and !has_capability('moodle/course:viewhiddenactivities', $context)) {
		$strreferentielbases = get_string('modulenameplural', 'referentiel');
		$navigation = build_navigation($strreferentielbases, $cm);
      	print_header_simple(format_string($referentiel->name), '', $navigation, '', '', true, '', navmenu($course, $cm));
        notice(get_string("activityiscurrentlyhidden"),"$CFG->wwwroot/course/view.php?id=$course->id"); 
  }

  if ($task_id) {    // So do you have access?
    if (!(has_capability('mod/referentiel:write', $context) 
			or has_capability('mod/referentiel:selecttask', $context) 
			or referentiel_task_isowner($task_id)) 
			or !confirm_sesskey() ) {
         print_error(get_string('noaccess','referentiel'));
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
	
	if ($cancel) {
        if (!empty($SESSION->returnpage)) {
            $return = $SESSION->returnpage;
            unset($SESSION->returnpage);
            redirect($return);
        } else {
            redirect('task.php?d='.$referentiel->id);
        }
    
  }
  
	/// Check to see if groups are being used here
	/// find out current groups mode
  $groupmode = groups_get_activity_groupmode($cm);
  $currentgroup = groups_get_activity_group($cm, true);
		
  /// Get all users that are allowed to submit or subscribe task
	$gusers=NULL;
  if ($gusers = get_users_by_capability($context, 'mod/referentiel:write', 'u.id', '', '', '', $currentgroup, '', false)) {
    	$gusers = array_keys($gusers);
  }
	// if groupmembersonly used, remove users who are not in any group
  if ($gusers and !empty($CFG->enablegroupings) and $cm->groupmembersonly) {
    	if ($groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id')) {
        	$gusers = array_intersect($gusers, array_keys($groupingusers));
        }
  }

		

	if (!empty($referentiel) && !empty($course)
    && !empty($form->task_id) 
		&& isset($form) 
		&& isset($form->mode)
		)
	{
		
		if ($form->mode=="updatetask"){
				if (isset($form->name)) {
   		    if (trim($form->name) == '') {
       		   unset($form->name);
          }
        }
        if (!empty($form->select_all) && ($form->select_all=='1')){
          // recuperer tous les utilisateurs concernes
			    $record_id_users  = referentiel_get_students_course($course->id,0,0);  //seulement les stagiaires  
			    if ($gusers && $record_id_users){ // liste des utilisateurs du groupe courant
				      $record_users  = array_intersect($gusers, array_keys($record_id_users));
				      foreach ($record_users  as $userid){
					      referentiel_association_user_task($userid, $form->task_id, $USER->id);
				      }
			    }           
        } 
        else{
          if (!empty($form->tuserid)){
            // DEBUG
            // echo "<br />DEBUG :: souscription.php :: 159 :: $form->task_id <b />\n";
            // print_object($form->tuserid);
            // exit;
            foreach($form->tuserid as $userid){
              // echo "<br />DEBUG :: souscription.php :: 163 :: Tache : $form->task_id, $userid <br />\n";
              referentiel_association_user_task($userid, $form->task_id, $USER->id);
            }
          } 
    	   
    	  }
    }
    if (!empty($SESSION->returnpage)) {
            $return = $SESSION->returnpage;
	        unset($SESSION->returnpage);
    	    redirect($return);
    } 
    else {
	    	redirect("task.php?d=$referentiel->id&amp;mode=listtasksingle");
    }
		
    exit;
  }

	$mode='updatetask';
   
	// afficher les formulaires

  unset($SESSION->modform); // Clear any old ones that may be hanging around.
  $modform = "";
	/// RSS and CSS and JS meta
	$meta = '<link rel="stylesheet" type="text/css" href="activite.css" />'."\n";
	/// Print the page header
	$strreferentiels = get_string('modulenameplural','referentiel');
	$strreferentiel = get_string('referentiel','referentiel');
	$strtask = get_string('souscription','referentiel');
	$icon = '<img class="icon" src="'.$CFG->wwwroot.'/mod/referentiel/icon.gif" alt="'.get_string('modulename','referentiel').'"/>';
	/// RSS and CSS and JS meta

	$strpagename=get_string('tasks','referentiel');
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
	    groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/referentiel/souscription.php?d='.$referentiel->id.'&amp;souscription='.$souscription.'&amp;task_id='.$task_id.'&amp;mode='.$mode.'&amp;sesskey='.sesskey());
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
		
    // Moodle 1.8
		$groupmode = groupmode($course, $cm);
    $currentgroup = setup_and_print_groups($course, $groupmode, $CFG->wwwroot . '/mod/referentiel/souscription.php?d='.$referentiel->id.'&amp;souscription='.$souscription.'&amp;task_id='.$task_id.'&amp;mode='.$mode.'&amp;sesskey='.sesskey()); 	
	}
			
   	/// Get all users that are allowed to submit activite
	$gusers = NULL;
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
	$currenttab = $mode;
	
	include('tabs.php');

    print_heading_with_help($strtask, 'task', 'referentiel', $icon);
	
	print_simple_box_start('center', '', '', 5, 'generalbox', $referentiel->name);
	// recuperer l'id de la tache
	if ($task_id) { // id 	task
        if (! $record = get_record('referentiel_task', 'id', $task_id)) {
            print_error('task ID is incorrect');
        }

        // print_object($gusers);
        referentiel_print_task_user_selection($task_id, $mode, $referentiel, $userid_filtre, $gusers);
	}
			
	print_simple_box_end(); 
  	print_footer($course);

?>
