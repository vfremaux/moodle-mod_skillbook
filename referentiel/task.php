<?php  // $Id: certificate.php,v 1.0 2008/05/03 00:00:00 jfruitet Exp $
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
    $deleteall  = optional_param('deleteall', 0, PARAM_INT);
    $select    = optional_param('select', 0, PARAM_INT);
    $course     = optional_param('course', 0, PARAM_INT);
    $groupmode  = optional_param('groupmode', -1, PARAM_INT);
    $cancel     = optional_param('cancel', 0, PARAM_BOOL);
  	$approve    = optional_param('approve', 0, PARAM_INT);	
	$souscription    = optional_param('souscription', 0, PARAM_INT);
	$hide    = optional_param('hide', 0, PARAM_INT);
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
	
	if ($task_id) { // id task
        if (! $record = get_record('referentiel_task', 'id', $task_id)) {
            print_error('task ID is incorrect');
        }
	}

	
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
	
    require_login($course->id, false, $cm);

    if (!isloggedin()) {
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

    /*
    // limiter l'accès aux tâches ?
    // NON
    if ($task_id) {    // So do you have access?
      if (!(has_capability('mod/referentiel:write', $context) 
			or has_capability('mod/referentiel:selecttask', $context) 
			or referentiel_task_isowner($task_id)) 
			// or !confirm_sesskey() 
      ) {
            print_error(get_string('noaccess','referentiel'));
        }
    }
	*/
	
	// selecteur
	$userid_filtre = 0;
	
	
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
            redirect('task.php?d='.$referentiel->id.'&amp;select_acc='.$select_acc);
        }
    }

	/// selection d'utilisateurs
    if (isset($action) && ($action=='selectuser')
		&& isset($form->userid) && ($form->userid>0)
		&& confirm_sesskey() ){
		$userid_filtre=$form->userid;
		// DEBUG
		// echo "<br />ACTION : $action  SEARCH : $userid_filtre\n";
		unset($form);
		unset($action);
		// exit;
    }

	/// Delete any requested records
    if (isset($delete) && ($delete>0 )
			&& confirm_sesskey() 
			&& (has_capability('mod/referentiel:addtask', $context) or referentiel_task_isowner($delete))) {
        if ($confirm = optional_param('confirm',0,PARAM_INT)) {
			// verifier que la tache existe
			if (referentiel_delete_task_record($delete)){
				add_to_log($course->id, 'referentiel', 'record delete', "task.php?d=$referentiel->id", $delete, $cm->id);
                // notify(get_string('recorddeleted','referentiel'), 'notifysuccess');
            }
        } 
    }

    if (isset($deleteall) && ($deleteall>0 )
        && has_capability('mod/referentiel:addtask', $context)
		&& confirm_sesskey()){
    	/// Delete any requested records  and mapped activities
        if ($confirm = optional_param('confirm',0,PARAM_INT)) {
			// detruire la taches, les consignes et les activites associes
            if (referentiel_delete_task_and_activities($deleteall)){
			     add_to_log($course->id, 'referentiel', 'record delete', "task.php?d=$referentiel->id", $deleteall, $cm->id);
                 // notify(get_string('recorddeleted','referentiel'), 'notifysuccess');
            }
		}
    }

	/// Hide any requested records
    if (!empty($task_id) && isset($hide) && confirm_sesskey() 
		  && has_capability('mod/referentiel:addtask', $context)) {
		// Masquer cette tache
        referentiel_mask_task($task_id, $hide);
    }
		
	
	/// Approve any requested records
    if (isset($approve) && ($approve>0) && confirm_sesskey() 
		&& has_capability('mod/referentiel:approve', $context)) {
		// Valider toutes les activités qui pointent vers cette tache
	    $confirm = optional_param('confirm',0,PARAM_INT);
		  if ($confirm) {
			 referentiel_validation_activite_task($approve);
		  }
    }
	
	
	/// Selection tache
    if (isset($select) && ($select>0) && confirm_sesskey()  
		&& has_capability('mod/referentiel:selecttask', $context)) {
		if (referentiel_association_user_task($USER->id, $select)){
				add_to_log($course->id, 'referentiel', 'task', "task.php?d=$referentiel->id", $select, $cm->id);
                // notify(get_string('task','referentiel').':'.$select, 'notifysuccess');
		}
    }
	

	if (isset($task_id) && ($task_id>0) && ($mode=='imposetask')
        && has_capability('mod/referentiel:addtask', $context) ){
        redirect("$CFG->wwwroot/mod/referentiel/souscription.php?d=$referentiel->id&amp;select_acc=$select_acc&amp;task_id=$task_id&amp;sesskey=".sesskey());
        exit;
	}

	
	if (!empty($referentiel) && !empty($course) 
		&& isset($form) 
		&& isset($form->mode)
		)
	{
        // pour eviter un warning
        if (!isset($select_all)){
            $select_all=0;
        }
        if (!isset($form->souscription_forcee)){
            $form->souscription_forcee=0;
        }

		// add, delete or update form submitted	
		$addfunction    = "referentiel_add_task";
        $updatefunction = "referentiel_update_task";
        $deletefunction = "referentiel_delete_task";
		// DEBUG
		// echo "<br />task.php : Ligne 244: Formulaire\n";
		// print_r($form);

		switch ($form->mode) {
		    case "deletetaskall":
                if (isset($form->name)) {
                    if (trim($form->name) == '') {
       		           unset($form->name);
                    }
                }

                if (isset($form) && !empty($form->task_id) && !empty($form->t_activite)){
                    $select_sql='';
                    foreach ($form->t_activite as $activite_id){
                        referentiel_delete_activity_record($activite_id);
                    }
                    referentiel_delete_task_record($form->task_id);
                }
                break;


		    case "approve":
                if (isset($form->name)) {
                    if (trim($form->name) == '') {
       		           unset($form->name);
                    }
                }
		    
                if (isset($form) && !empty($form->task_id) && !empty($form->t_activite)){
                    $select_sql='';
                    foreach ($form->t_activite as $activite_id){
                        if (empty($select_sql)){
                            $select_sql.= ' AND ((activityid='.$activite_id.') ';
                        }
                        else{
                            $select_sql.= ' OR (activityid='.$activite_id.') ';
                        }
                    }
                    if (!empty($select_sql)){
                        $select_sql.= ') ';
                        referentiel_validation_activite_task($form->task_id, $select_sql);
                    }
                }
                break;
		
    		case "updatetask":
                if (isset($form->name)) {
                    if (trim($form->name) == '') {
                        unset($form->name);
                    }
                }

                if (isset($form->delete_all_task_associations) && ($form->delete_all_task_associations==get_string('delete_all_task_associations', 'referentiel'))){
                    // suppression de la tache et de toutes les activites associes
					// echo "<br />SUPPRESSION\n";

                    $return = referentiel_delete_task_and_activities($form->task_id);
                    if (!$return) {
							/*
            	        	if (file_exists($moderr)) {
                	        	$form = $form;
	                   		    include_once($moderr);
    	                   		die;
	    	               	}
							*/
                        print_error("Could not delete task $task_id of the referentiel", "task.php?d=$referentiel->id");
                    }
                    add_to_log($course->id, "referentiel", "delete",
            	          "task $form->task_id deleted",
                          "$form->instance", "");
                }
                elseif (isset($form->delete) && ($form->delete==get_string('delete'))){
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
                        print_error("Could not delete task $task_id of the referentiel", "task.php?d=$referentiel->id");
                    }
                    if (is_string($return)) {
                        print_error($return, "task.php?d=$referentiel->id");
                    }
                    add_to_log($course->id, "referentiel", "delete",
            	          "mtask $form->task_id deleted",
                          "$form->instance", "");
                }
				else {
                    $return = $updatefunction($form);
                    if (!$return) {
						/*
            		    if (file_exists($moderr)) {
                			$form = $form;
                    		include_once($moderr);
                        	die;
	                    }
						*/
                        print_error("Could not update task $form->id of the referentiel", "task.php?d=$referentiel->id");
					}
                    if (is_string($return)) {
                        print_error($return, "task.php?d=$referentiel->id");
                    }
					add_to_log($course->id, "referentiel", "update",
            	           "task $form->task_id updated",
                           "$form->instance", "");
					// depot de consigne ?
					if (isset($form->depot_consigne) && ($form->depot_consigne==get_string('yes'))){
						// APPELER le script
						if (isset($form->taskid) && ($form->taskid>0)){
							if (isset($form->consigne_id) && ($form->consigne_id>0)){
								redirect("upload_consigne.php?d=$referentiel->id&amp;select_acc=$select_acc&amp;task_id=$form->taskid&amp;consigne_id=$form->consigne_id&amp;mode=updateconsigne&amp;soucription=$form->souscription_forcee&amp;sesskey=".sesskey());
								exit;
							}
							else{
								redirect("upload_consigne.php?d=$referentiel->id&amp;select_acc=$select_acc&amp;task_id=$form->taskid&amp;consigne_id=0&amp;mode=addconsigne&amp;soucription=$form->souscription_forcee&amp;sesskey=".sesskey());
								exit;
							}
							
						}
					}
					
					// souscription_forcee
					if (isset($form->souscription_forcee) && ($form->souscription_forcee=='1')){
                        if (isset($form->task_id) && ($form->task_id>0)){
							redirect("souscription.php?d=$referentiel->id&amp;select_acc=$select_acc&amp;task_id=$form->task_id&amp;sesskey=".sesskey());
							exit;
					   }
                    }

                }
                

                if (isset($form->redirect) and !empty($form->redirecturl)) {
                    $SESSION->returnpage = $form->redirecturl;
    		    }
                else {
                    $SESSION->returnpage = "$CFG->wwwroot/mod/referentiel/task.php?d=$referentiel->id&amp;select_acc=$select_acc&amp;mode=listtasksingle";
                }

			    break;
			
			case "addtask":
				// echo "<br />task.php : Ligne 337 : Formulaire\n";
				// print_r($form);
				
				
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
					print_error("Could not add a new task to the referentiel", "task.php?d=$referentiel->id");
				}
                if (is_string($return)) {
    	        	print_error($return, "task.php?d=$referentiel->id");
				}
				// depot de consigne ?
				if (isset($form->depot_consigne) && ($form->depot_consigne==get_string('yes'))){
					// APPELER le script
						if ($return){
							redirect("upload_consigne.php?d=$referentiel->id&amp;task_id=$return&amp;select_all=$select_all&amp;consigne_id=0&amp;mode=addconsigne&amp;soucription=$form->souscription_forcee&amp;sesskey=".sesskey());
							exit;
						}
				}
				// souscription_forcee
				if (isset($form->souscription_forcee) && ($form->souscription_forcee=='1')){
						if ($return){
							redirect("souscription.php?d=$referentiel->id&amp;select_all=$select_all&amp;task_id=$return&amp;sesskey=".sesskey());
							exit;
						}
                }

                if (isset($form->redirect) and !empty($form->redirecturl)) {
    	    		$SESSION->returnpage = $form->redirecturl;
				} 
				else {
					$SESSION->returnpage = "$CFG->wwwroot/mod/referentiel/task.php?d=$referentiel->id&amp;select_all=$select_all&amp;mode=listtasksingle";
				}
				add_to_log($course->id, "referentiel", "add",
                           "creation task $form->task_id ",
                           "$form->instance", "");
                break;
			
	        case "deletetask":
				if (! $deletefunction($form)) {
					print_error("Could not delete task of  the referentiel");
                }
	            unset($SESSION->returnpage);
	            add_to_log($course->id, "referentiel", "add",
                           "task $form->task_id deleted",
                           "$form->instance", "");
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
	    	redirect("task.php?d=$referentiel->id&amp;select_acc=$select_acc&amp;mode=listtasksingle");
    	}
		
        exit;
	}

	// afficher les formulaires

    unset($SESSION->modform); // Clear any old ones that may be hanging around.
    $modform = "task.html";
	/// RSS and CSS and JS meta
	$meta = '<link rel="stylesheet" type="text/css" href="activite.css" />
<link type="text/css" rel="stylesheet" href="dhtmlgoodies_calendar.css" media="screen" />'."\n";
	/// Print the page header
	$strreferentiels = get_string('modulenameplural','referentiel');
	$strreferentiel = get_string('referentiel','referentiel');
	$strtask = get_string('task','referentiel');
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
		update_module_button($cm->id, $course->id, get_string('modulename-intance', 'referentiel')), // HTML code for a button (usually for module editing)

        navmenu($course, $cm), // HTML code for a popup menu
		false, // use XML for this page
		'', // This text will be included verbatim in the <body> tag (useful for onload() etc)
		false); // If true, return the visible elements of the header instead of echoing them.

        if ($mode=='approvetask'){
    		/// Check to see if groups are being used here
	       	/// find out current groups mode
            $groupmode = groups_get_activity_groupmode($cm);
            $currentgroup = groups_get_activity_group($cm, true);
            groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/referentiel/task.php?d='.$referentiel->id.'&amp;task_id='.$task_id.'&amp;mode='.$mode.'&amp;sesskey='.sesskey());
        }
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
        if ($mode=='approvetask'){
            // Moodle 1.8
            $groupmode = groupmode($course, $cm);
            $currentgroup = setup_and_print_groups($course, $groupmode, $CFG->wwwroot . '/mod/referentiel/task.php?d='.$referentiel->id.'&amp;task_id='.$task_id.'&amp;mode='.$mode.'&amp;sesskey='.sesskey());
        }
    }

    if ($mode=='approvetask'){
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
    }
    
	print_heading(format_string($referentiel->name));
		
	/// Print the tabs
	if (!isset($mode) || ($mode=="")){
		$mode='listtask';		
	}	
	if (isset($mode) && (($mode=="approvetask") || ($mode=="deletetaskactivites"))){
		$currenttab ='listtasksingle';
	}
	elseif (isset($mode) && (($mode=="deletetask") 
    	|| ($mode=="deletetaskall")
		|| ($mode=="approve")
        || ($mode=="commenttask"))){
		$currenttab ='updatetask';		
	}
	else if (isset($mode) && ($mode=='listtasksingle')){
		$currenttab ='listtasksingle';
	}
	else if (isset($mode) && ($mode=='selecttask')){
		$currenttab ='selecttask';
	}
	else if (isset($mode) && ($mode=='listtask')){
		$currenttab ='listtask';
	}
	else{
		$currenttab = $mode;
	}
	
	include('tabs.php');

    print_heading_with_help($strtask, 'task', 'referentiel', $icon);
	
	if (($mode=='list') || ($mode=='listtask')){
		referentiel_print_liste_tasks($mode, $referentiel); 
	}
	else if ($mode=='listtasksingle'){
		if (isset($task_id) && ($task_id>0)){
			referentiel_print_task_id($task_id, $referentiel); 
		}
		else{
			referentiel_print_liste_tasks($mode, $referentiel); 
		}
	}
	else if ($mode=='approvetask'){
		if (isset($task_id) && ($task_id>0)){
			referentiel_print_activities_task($task_id, $referentiel,'approvetask', $userid_filtre, $gusers);
		}
		else{
			referentiel_print_liste_tasks($mode, $referentiel );
		}
	}
	else if ($mode=='deletetaskactivites'){
		if (isset($task_id) && ($task_id>0)){
			referentiel_print_activities_task($task_id, $referentiel,'deletetaskactivites', $userid_filtre, $gusers);
		}
		else{
			referentiel_print_liste_tasks($mode, $referentiel );
		}
	}
	else {
		print_simple_box_start('center', '', '', 5, 'generalbox', $referentiel->name);
		if ($mode=='updatetask'){
			// recuperer l'id de la tache
			if ($task_id) { // id 	task
    	    	if (! $record = get_record('referentiel_task', 'id', $task_id)) {
			    	print_error('task ID is incorrect');
    			}
			}
			$modform = "task.html";
			
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
    	    notice("ERREUR : No file found at : $modform)", "task.php?d=$referentiel->id");
    	}
		
		include_once($modform);
	    print_simple_box_end();
	} 
    print_footer($course);

?>
