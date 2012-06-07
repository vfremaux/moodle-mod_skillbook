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
    require_once("print_lib_certificate.php");	// AFFICHAGES 
	
	// PAS DE RSS
    // require_once("$CFG->libdir/rsslib.php");

    $id    = optional_param('id', 0, PARAM_INT);    // course module id    
	$d     = optional_param('d', 0, PARAM_INT);    // referentielbase id
	
    $certificate_id   = optional_param('certificate_id', 0, PARAM_INT);    //record certificate id
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
	
	if ($d) {
        if (! $referentiel = get_record('referentiel', 'id', $d)) {
            error('Referentiel instance is incorrect');
        }
        if (! $referentiel_referentiel = get_record('referentiel_referentiel', 'id', $referentiel->referentielid)) {
            error('Réferentiel id is incorrect');
        }
        
		if (! $course = get_record('course', 'id', $referentiel->course)) {
	            error('Course is misconfigured');
    	}
        	
		if (! $cm = get_coursemodule_from_instance('referentiel', $referentiel->id, $course->id)) {
    	        error('Course Module ID is incorrect');
		}
	} 
	elseif ($id) {
        if (! $cm = get_coursemodule_from_id('referentiel', $id)) {
        	error('Course Module ID was incorrect');
        }
        if (! $course = get_record('course', 'id', $cm->course)) {
            error('Course is misconfigured');
        }
        if (! $referentiel = get_record('referentiel', 'id', $cm->instance)) {
            error('Referentiel instance is incorrect');
        }
        if (! $referentiel_referentiel = get_record('referentiel_referentiel', 'id', $referentiel->referentielid)) {
            error('Referentiel is incorrect');
        }
    } 
	else{
        // error('You cannot call this script in that way');	
		error(get_string('erreurscript','referentiel','Erreur01 : certification.php'));
	}

	if ($certificate_id) { // id certificat
        if (! $record = get_record('referentiel_certificate', 'id', $certificate_id)) {
            error('certificate ID is incorrect');
        }
	}
	

    require_login($course->id, false, $cm);

    if (!isloggedin() or isguest()) {
        redirect('view.php?id='.$cm->id);
    }

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
	/// If it's hidden then it's don't show anything.  :)
    if (empty($cm->visible) and !has_capability('course:viewhiddenactivities', $context)) {
        $strreferentielbases = get_string("modulenameplural", "referentiel");
        $navigation = "<a href=\"index.php?id=$course->id\">$strreferentielbases</a> ->";
        print_header_simple(format_string($referentiel->name), "",
                 "$navigation ".format_string($referentiel->name), "", "", true, '', navmenu($course, $cm));
        notice(get_string("certificatiscurrentlyhidden"));
    }
	
    if ($certificate_id) {    // So do you have access?
        if (!(has_capability('mod/referentiel:viewrate', $context) 
			or referentiel_certificate_isowner($certificate_id)) or !confirm_sesskey() ) {
            error(get_string('noaccess_certificat','referentiel'));
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
            redirect('certificate.php?d='.$referentiel->id);
        }
    }

	/// selection d'utilisateurs
    if (isset($action) && ($action=='selectuser')
		&& isset($form->userid) && ($form->userid>0)
		&& confirm_sesskey() ){
		// && (has_capability('mod/referentiel:write', $context) or referentiel_certificate_isowner($delete))) {
		$search="  AND userid=".$form->userid." ";
		// DEBUG
		// echo "<br />ACTION : $action  SEARCH : $search\n";
		unset($form);
		unset($action);
		// exit;
    }
 	
	/// Delete any requested records
    if (isset($delete) && ($delete>0 )
			&& confirm_sesskey() 
			&& (has_capability('mod/referentiel:rate', $context) or referentiel_certificate_isowner($delete))) {
        if ($confirm = optional_param('confirm',0,PARAM_INT)) {
            if (referentiel_delete_certificate_record($delete)){
				// DEBUG
				// echo "<br /> certificate REMIS A ZERO\n";
				// exit;
				add_to_log($course->id, 'referentiel', 'record delete', "certificate.php?d=$referentiel->id", $delete, $cm->id);
                // notify(get_string('recorddeleted','referentiel'), 'notifysuccess');
            }
		} 
    }
	
	/// Approve any requested records
    if (isset($approve) && ($approve>0) && confirm_sesskey() 
		&& has_capability('mod/referentiel:rate', $context)) 
		{
        if ($approverecord = get_record('referentiel_certificate', 'id', $approve)) {
	        $confirm = optional_param('confirm',0,PARAM_INT);
			if ($confirm) {
	            $approverecord->verrou = 1;
			}
			else{
				$approverecord->verrou = 0;
			}
			$approverecord->teacherid=$USER->id;
			// DEBUG
			// print_r($approverecord);
			// echo "<br />\n";
			
            if (update_record('referentiel_certificate', $approverecord)) {
            	// notify(get_string('recordapproved','referentiel'), 'notifysuccess');
            }
        }
    }
	
	/// Approve any requested records
    if (isset($comment) && ($comment>0) && confirm_sesskey()  
		&& has_capability('mod/referentiel:rate', $context)) 
	{
		if (isset($form) && isset($form->certificate_id) && ($form->certificate_id>0)){
			if ($approverecord = get_record('referentiel_certificate', 'id', $comment)) {
				$approverecord->teacherid=$USER->id;
				$approverecord->comment=addslashes($form->comment);
				// DEBUG
				// print_r($approverecord);
				// echo "<br />\n";
				
		        if (update_record('referentiel_certificate', $approverecord)) {
        		   	// notify(get_string('recordapproved','referentiel'), 'notifysuccess');
            	}
			}
			unset($form);
        }
    }

/* A TERMINER

/// Upload records section. Only for teachers and the admin.

    if (has_capability('mod/referentiel:manageentries',$context)) {
        if ($import) {
            print_simple_box_start('center','80%');
            print_heading(get_string('uploadrecords', 'referentiel'), '', 3);

            $maxuploadsize = get_max_upload_file_size();
            echo '<div style="text-align:center">';
            echo '<form enctype="multipart/form-referentiel" action="import.php" method="post">';
            echo '<input type="hidden" name="MAX_FILE_SIZE" value="'.$maxuploadsize.'" />';
            echo '<input name="d" value="'.$referentiel->id.'" type="hidden" />';
            echo '<input name="sesskey" value="'.sesskey().'" type="hidden" />';
            echo '<table align="center" cellspacing="0" cellpadding="2" border="0">';
            echo '<tr>';
            echo '<td align="right">'.get_string('csvfile', 'referentiel').':</td>';
            echo '<td><input type="file" name="recordsfile" size="30" />';
            helpbutton('importcsv', get_string('csvimport', 'referentiel'), 'referentiel', true, false);
            echo '</td><tr>';
            echo '<td align="right">'.get_string('fielddelimiter', 'referentiel').':</td>';
            echo '<td><input type="text" name="fielddelimiter" size="6" />';
            echo get_string('defaultfielddelimiter', 'referentiel').'</td>';
            echo '</tr>';
            echo '<td align="right">'.get_string('fieldenclosure', 'referentiel').':</td>';
            echo '<td><input type="text" name="fieldenclosure" size="6" />';
            echo get_string('defaultfieldenclosure', 'referentiel').'</td>';
            echo '</tr>';
            echo '</table>';
            echo '<input type="submit" value="'.get_string('uploadfile', 'referentiel').'" />';
            echo '</form>';
            echo '</div>';
            print_simple_box_end();
        } else {
            echo '<div style="text-align:center">';
            echo '<a href="edit.php?d='.$referentiel->id.'&amp;import=1">'.get_string('uploadrecords', 'referentiel').'</a>';
            echo '</div>';
        }
    }
*****************************/
	
	
	
    // if (!empty($course) and confirm_sesskey()) {    // add, delete or update form submitted
	
	// print_r($form);
	
	if (!empty($referentiel) && !empty($course) 
		&& isset($form) && isset($form->mode)
		)
	{
		// add, delete or update form submitted	
		$addfunction    = "referentiel_add_certificat";
        $updatefunction = "referentiel_update_certificat";
        $deletefunction = "referentiel_delete_certificat";
		
		switch ($form->mode) {
    		case "updatecertif":
			
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
    	         	      	error("Could not update certificate $certificate_id of the referentiel", "certificate.php?d=$referentiel->id");
        	    	}
	                if (is_string($return)) {
    	           	    error($return, "certificate.php?d=$referentiel->id");
	    	        }
	        	    if (isset($form->redirect)) {
    	                $SESSION->returnpage = $form->redirecturl;
        	       	} else {
            	       	$SESSION->returnpage = "$CFG->wwwroot/mod/referentiel/certificate.php?d=$referentiel->id";
	               	}
					
	    	        add_to_log($course->id, "referentiel", "delete",
            	          "mise a jour certificate $form->certificate_id",
                          "$form->instance", "");
					
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
    	            	error("Could not update certificate $form->id of the referentiel", "certificate.php?d=$referentiel->id");
					}
		            if (is_string($return)) {
    		        	error($return, "certificate.php?d=$referentiel->id");
	    		    }
	        		if (isset($form->redirect)) {
    	        		$SESSION->returnpage = $form->redirecturl;
					} 
					else {
        	    		$SESSION->returnpage = "$CFG->wwwroot/mod/referentiel/certificate.php?d=$referentiel->id";
	        	    }
					add_to_log($course->id, "referentiel", "update",
            	           "mise a jour certificate $form->certificate_id",
                           "$form->instance", "");
    	    	}

			break;
			
			case "addcertif":
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
					error("Could not add a new certificate to the referentiel", "certificate.php?d=$referentiel->id");
				}
	        	if (is_string($return)) {
    	        	error($return, "certificate.php?d=$referentiel->id");
				}
				if (isset($form->redirect)) {
    	    		$SESSION->returnpage = $form->redirecturl;
				} 
				else {
					$SESSION->returnpage = "$CFG->wwwroot/mod/referentiel/certificate.php?d=$referentiel->id";
				}
				add_to_log($course->id, referentiel, "add",
                           "creation certificate $form->certificate_id ",
                           "$form->instance", "");
            break;
			
	        case "deletecertif":
				if (! $deletefunction($form)) {
	            	notify("Could not delete certificate of  the referentiel");
                }
	            unset($SESSION->returnpage);
	            add_to_log($course->id, referentiel, "add",
                           "suppression certificate $form->certificate_id ",
                           "$form->instance", "");
            break;
            
			default:
            	// error("No mode defined");
        }
       	
    	if (!empty($SESSION->returnpage)) {
            $return = $SESSION->returnpage;
	        unset($SESSION->returnpage);
    	    redirect($return);
        } 
		else {
	    	redirect("certificate.php?d=$referentiel->id");
    	}
		
        exit;
	}

	// afficher les formulaires

    unset($SESSION->modform); // Clear any old ones that may be hanging around.

    $modform = "certificate.html";

    if (file_exists($modform)) {
        if ($usehtmleditor = can_use_html_editor()) {
            $defaultformat = FORMAT_HTML;
            $editorfields = '';
        } else {
            $defaultformat = FORMAT_MOODLE;
        }
		

/// Can't use this if there are no certificat
/*
    if (has_capability('mod/referentiel:managetemplates', $context)) {
        if (!record_exists('referentiel_certificate','referentielid',$referentiel->id)) {      // Brand new referentielbase!
            redirect($CFG->wwwroot.'/mod/referentiel/certificate.php?d='.$referentiel->id);  // Redirect to field entry
        }
    }
*/

		/// RSS and CSS and JS meta
    	$meta = '';

		/// Print the page header
	    $strreferentiel = get_string('modulenameplural','referentiel');
		$strcertificate = get_string('certificat','referentiel');
		$icon = '<img class="icon" src="'.$CFG->wwwroot.'/mod/referentiel/icon.gif" alt="'.get_string('modulename','referentiel').'"/>';
		
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
		false) // If true, return the visible elements of the header instead of echoing them.
		;

		/// Check to see if groups are being used here
		$groupmode = groupmode($course, $cm);
	    $currentgroup = setup_and_print_groups($course, $groupmode, 'certificate.php?d='.$referentiel->id);

		print_heading(format_string($referentiel->name));
		
		
		/// Print the tabs
		if (!isset($mode) || ($mode=="")){
			$mode='listcertif';		
		}
		if (isset($mode) && ($mode=="certificat")){
			$mode='listcertif';		
		}
		
		// DEBUG
		// echo "<br /> MODE : $mode\n";
		
		if (isset($mode) && (($mode=="deletecertif") || ($mode=="updatecertif")  || ($mode=="approvecertif") || ($mode=="commentcertif"))){
			$currenttab ='editcertif';		
		}
		else if (isset($mode) && ($mode=='listcertifsingle')){
			$currenttab ='listcertif';
		}
		else{
			$currenttab = $mode;
		}
    	if ($certificate_id) {
        	$editentry = true;  //used in tabs
    	}
	    include('tabs.php');

		// DEBUG
		// echo "<br /> MODE : $mode  ; CURRENTTABLE : $currenttab \n";
		// exit;

        print_heading_with_help($strcertificat, 'certificat', 'referentiel', $icon);
		
		if (($mode=='list') || ($mode=='listcertif')  || ($mode=='listcertifsingle')){
			referentiel_print_liste_certificats($mode, $referentiel, $search); 
		}
		else {
	        print_simple_box_start('center', '', '', 5, 'generalbox', $referentiel->name);
    	    
			// formulaires
			if ($mode=='editcertif'){
				if ($certificate_id) { 
					// id certificate : un certificate particulier
    	    		if (! $record = get_record('referentiel_certificate', 'id', $certificate_id)) {
			            error('certificate ID is incorrect');
    			    }
					$modform = "certificate_edit.html";
				}
				else {
					$modform = "certificate.html";
				}
			}
			else if ($mode=='updatecertif'){
				// recuperer l'id du certificate après l'avoir genere automatiquement et mettre en place les competences
				
				if ($certificate_id) { // id certificat
    	    		if (! $record = get_record('referentiel_certificate', 'id', $certificate_id)) {
			            error('certificate ID is incorrect');
    			    }
				}
				else{
					error('certificate ID is incorrect');
				}
				$modform = "certificate_edit.html";
			}
			else if ($mode=='addcertif'){
				// recuperer l'id du certificate après l'avoir genere automatiquement et mettre en place les competences
				if (!$certificate_id){
					$certificate_id=referentiel_genere_certificat($USER->id, $referentiel_referentiel->id);
				}
				if ($certificate_id) { // id certificat
    	    		if (! $record = get_record('referentiel_certificate', 'id', $certificate_id)) {
			            error('certificate ID is incorrect');
    			    }
				}
				else{
					error('certificate ID is incorrect');
				}
				$modform = "certificate_add.html";
			}
			
			include_once($modform);
	        print_simple_box_end();
		}
    } 
	else {
        notice("ERREUR : No file found at : $modform)", "certificate.php?d=$referentiel->id");
    }
    print_footer($course);

?>
