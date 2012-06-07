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
    include('lib_accompagnement.php');
    include('lib_certificate.php');	// AFFICHAGES
    include('print_lib_certificate.php');	// AFFICHAGES 
	
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
  $userid = optional_param('userid', 0, PARAM_INT);
  $select_acc = optional_param('select_acc', 0, PARAM_INT);      // coaching

  $mode_select   = optional_param('mode_select','', PARAM_ALPHA);
  $filtre_auteur = optional_param('filtre_auteur', 0, PARAM_INT);
  $filtre_verrou = optional_param('filtre_verrou', 0, PARAM_INT);
  $filtre_referent = optional_param('filtre_referent', 0, PARAM_INT);
  $filtre_date_decision = optional_param('filtre_date_decision', 0, PARAM_INT);

  $sql_filtre_where=optional_param('sql_filtre_where','', PARAM_ALPHA);
  $sql_filtre_order=optional_param('sql_filtre_order','', PARAM_ALPHA);
  $sql_filtre_user=optional_param('sql_filtre_user','', PARAM_ALPHA);

	$data_filtre= new Object(); // paramettres de filtrage
	if (isset($filtre_verrou)){
			$data_filtre->filtre_verrou=$filtre_verrou;
	}
	else {
		$data_filtre->filtre_verrou=0;
	}
	if (isset($filtre_referent)){
		$data_filtre->filtre_referent=$filtre_referent;
	}
	else{
		$data_filtre->filtre_referent=0;
	}
	if (isset($filtre_date_decision)){
		$data_filtre->filtre_date_decision=$filtre_date_decision;
	}
	else{
		$data_filtre->filtre_date_decision=0;
	}
	if (isset($filtre_auteur)){
		$data_filtre->filtre_auteur=$filtre_auteur;
	}
	else{
		$data_filtre->filtre_auteur=0;
	}

    // DEBUG
    // print_object($data_filtre);
    // exit;
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
		print_error(get_string('erreurscript','referentiel','Erreur01 : certificate.php'));
	}


  $context = get_context_instance(CONTEXT_MODULE, $cm->id);
	
  if ($certificate_id) { // id certificat
        if (! $record = get_record('referentiel_certificate', 'id', $certificate_id)) {
            print_error('certificate ID is incorrect');
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

	
    if ($certificate_id) {    // So do you have access?
        if (!(has_capability('mod/referentiel:viewrate', $context) 
			or referentiel_certificate_isowner($certificate_id)) or !confirm_sesskey() ) {
            print_error(get_string('noaccess_certificat','referentiel'));
        }
    }

  // coaching
	if (!isset($select_acc)){
    if (isset($form->select_acc)){
        $select_acc=$form->select_acc;
    }
    else{ 
      $select_acc=0 ;
    }
  }
	
	// selecteur
	$userid_filtre=0;
  if (!empty($userid)) {
    $userid_filtre=$userid;	
  }


	/// selection filtre
  if (isset($mode_select) && ($mode_select=='selectetab') && confirm_sesskey() ){

		// gestion des filtres;
		$sql_filtre_where='';
		$sql_filtre_order='';
        $sql_filtre_user='';
          
		if (isset($filtre_verrou) && ($filtre_verrou=='1')){
			if ($sql_filtre_where!='')
				$sql_filtre_where.=' AND verrou=\'1\' ';
			else
				$sql_filtre_where.=' AND verrou=\'1\' ';
		}
		else if (isset($filtre_verrou) && ($filtre_verrou=='-1')){
			if ($sql_filtre_where!='')
				$sql_filtre_where.=' AND verrou=\'0\' ';
			else
				$sql_filtre_where.=' AND verrou=\'0\' ';
		}
		
		if (isset($filtre_referent) && ($filtre_referent=='1')){
			if ($sql_filtre_where!='')
				$sql_filtre_where.=' AND teacherid<>0  ';
			else
				$sql_filtre_where.=' AND teacherid<>0  ';
		}
		else if (isset($filtre_referent) && ($filtre_referent=='-1')){
			if ($sql_filtre_where!='')
				$sql_filtre_where.=' AND teacherid=0  ';
			else
				$sql_filtre_where.=' AND teacherid=0  ';
		}

		if (isset($filtre_date_decision) && ($filtre_date_decision=='1')){
			if ($sql_filtre_order!='')
				$sql_filtre_order.=', date_decision ASC ';
			else
				$sql_filtre_order.=' date_decision ASC ';
		}
		else if (isset($filtre_date_decision) && ($filtre_date_decision=='-1')){
			if ($sql_filtre_order!='')
				$sql_filtre_order.=', date_decision DESC ';
			else
				$sql_filtre_order.=' date_decision DESC ';
		}
        /*
        // on utilise un autre procédé
		if (isset($filtre_auteur) && ($filtre_auteur=='1')){
			if ($sql_filtre_user!='')
				$sql_filtre_user.=', userid ASC ';
			else
				$sql_filtre_user.=' userid ASC ';
		}
		else if (isset($filtre_auteur) && ($filtre_auteur=='-1')){
			if ($sql_filtre_user!='')
				$sql_filtre_user.=', userid DESC ';
			else
				$sql_filtre_user.=' userid DESC ';
		}
        */

		// echo "<br />DEBUG :: certificate.php :: Ligne 199 :: FILTRES : $sql_filtre_where $sql_filtre_order,   $sql_filtre_user\n";

  }


	// RECUPERER LES FORMULAIRES
    if (isset($SESSION->modform)) {   // Variables are stored in the session
        $form = $SESSION->modform;
        unset($SESSION->modform);
    } else {
        $form = (object)$_POST;
    }

	if ($cancel) {
	    if (isset($form->select_acc)){
          $select_acc=$form->select_acc;
      }

	    $mode ='list';
		  if (has_capability('mod/referentiel:managecertif', $context)){
         $SESSION->returnpage = "$CFG->wwwroot/mod/referentiel/certificate.php?d=$referentiel->id&amp;select_acc=$select_acc&amp;userid=0&amp;mode=$mode&amp;";
		  }
		  else{
         $SESSION->returnpage = "$CFG->wwwroot/mod/referentiel/certificate.php?d=$referentiel->id&amp;select_acc=$select_acc&amp;userid=$userid&amp;mode=$mode";				
		  }
	   
      if (!empty($SESSION->returnpage)) {
            $return = $SESSION->returnpage;
            unset($SESSION->returnpage);
            redirect($return);
      } else {
            redirect("$CFG->wwwroot/mod/referentiel/certificate.php?d=$referentiel->id&amp;select_acc=$select_acc&amp;userid=$userid&amp;mode=$mode");
      }

       exit;
  }

	
	/// selection utilisateurs accompagnés
	if (isset($action) && ($action=='select_acc')){
		  if (isset($form->select_acc) && confirm_sesskey() ){
		  	$select_acc=$form->select_acc;
		  }
		  if (isset($form->mode) && ($form->mode!='')){
			 $mode=$form->mode;
		  }
		  // echo "<br />ACTION : $action  SEARCH : $userid_filtre\n";
		  unset($form);
		  unset($action);
		  // exit;
  }
    
  /// selection d'utilisateurs
  if (isset($action) && ($action=='selectuser')
		&& isset($form->userid) && ($form->userid>0)
		&& confirm_sesskey() ){
		$userid_filtre=$form->userid;
		if (isset($form->select_acc)){
		  	$select_acc=$form->select_acc;
		}
		// DEBUG
		// echo "<br />ACTION : $action  SEARCH : $userid_filtre\n";
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
	            $verrou = 1;
			 }
			 else{
				$verrou = 0;
			 }
             set_field('referentiel_certificate','verrou',$verrou,'id',$approve);
             set_field('referentiel_certificate','teacherid',$USER->id,'id',$approve);

/*
		     $approverecord->teacherid=$USER->id;
             $approverecord->comment=addslashes($approverecord->comment);
			 $approverecord->synthese_certificat=addslashes($approvedrecord->synthese_certificat);
			 $approverecord->competences_certificat=addslashes($approverecord->competences_certificat);
			 $approverecord->decision_jury=addslashes($approverecord->decision_jury);
			 // DEBUG
			 // print_r($approverecord);
			 // echo "<br />\n";
			
       if (update_record('referentiel_certificate', $approverecord)) {
            	// notify(get_string('recordapproved','referentiel'), 'notifysuccess');
       }
*/
			 if (isset($userid) && ($userid>0)){
				$userid_filtre=$userid;
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
				$approverecord->synthese_certificat=addslashes($form->synthese_certificat);
				$approverecord->competences_certificat=addslashes($approverecord->competences_certificat);
				$approverecord->decision_jury=addslashes($approverecord->decision_jury);
                // MODIF JF 2010/02/11
                if (isset($form->mailnow)){
                    $approverecord->mailnow=$form->mailnow;
                    if ($form->mailnow=='1'){ // renvoyer
                        $approverecord->mailed=0;   // annuler envoi precedent
                    }
                }
                else{
                    $approverecord->mailnow=0;
                }

				if (isset($form->userid) && ($form->userid>0)){
					$userid_filtre=$form->userid;
				} 
				
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

/*	if (!empty($referentiel) && !empty($course)
		&& isset($form) && isset($form->mode)
		)
*/
  // if (!empty($course) and confirm_sesskey()) {    // add, delete or update form submitted

    if (!empty($referentiel) && !empty($course) && isset($form)) {
        /// modification globale

        if (isset($_POST['action']) && ($_POST['action']=='modifier_certificate_global')){
		    $form=$_POST;
		    // echo "<br />DEBUG :: certificate.php :: 411 :: ACTION : $action \n";
            //echo "<br />DEBUG :: certificate.php :: 413 :: FORM: \n";
            //print_r($form);

		        // coaching
            if (isset($form['select_acc'])){
		    	$select_acc=$form['select_acc'];
		    }

 		    if (isset($form['tcertificate_id']) && ($form['tcertificate_id'])){
                //echo "<br />DEBUG :: certificate.php :: 422 :: FORM: \n";
                //print_r($form['tcertificate_id']);

                foreach ($form['tcertificate_id'] as $id_certificat){
                    // echo "<br />DEBUG :: certificate.PHP :: 422 <br />ID :: ".$id_certificate."\n";
                    $form2= new Object();
                    $form2->action='modifier_certificat';
                    $form2->certificate_id=$form['certificate_id_'.$id_certificat];
                    $form2->comment=$form['commentaire_certificate_'.$id_certificat];
                    $form2->competences_certificat=$form['competences_certificate_'.$id_certificat];
                    $form2->comptencies=$form['competences_activite_'.$id_certificat];
                    $form2->synthese_certificat=$form['synthese_certificate_'.$id_certificat];
                    if (isset($form['decision_jury_sel_'.$id_certificat])){
                        $form2->decision_jury_sel=$form['decision_jury_sel_'.$id_certificat];
                    }
                    $form2->decision_jury=$form['decision_jury_'.$id_certificat];
                    $form2->decision_jury_old=$form['decision_jury_old_'.$id_certificat];
                    $form2->date_decision=$form['date_decision_'.$id_certificat];

                    $form2->referentielid=$form['ref_referentiel_'.$id_certificat];
                    $form2->userid=$form['userid_'.$id_certificat];
                    $form2->teacherid=$form['teacherid_'.$id_certificat];

                    if (!empty($form['verrou_'.$id_certificat]))  {
                        $form2->verrou=$form['verrou_'.$id_certificat];
                    }
                    else {
                        $form2->verrou=0;
                    }
                    $form2->valide=$form['valide_'.$id_certificat];
                    $form2->evaluation=$form['evaluation_'.$id_certificat];
                    $form2->mailnow=$form['mailnow_'.$id_certificat];
                    $form2->instance=$form['instance_'.$id_certificat];

                    // echo "<br />DEBUG :: certificate.PHP :: 445\n";
                    // print_object($form2);
                    // echo "<br />\n";

                    $return = referentiel_update_certificat($form2);
                    if (!$return) {
                        print_error("Could not update certificate $form->certificate_id of the referentiel", "certificate.php?d=$referentiel->id");
                    }
                    if (is_string($return)) {
                        print_error($return, "certificate.php?d=$referentiel->id");
                    }
                    add_to_log($course->id, "referentiel", "update", "mise a jour certificate $form2->certificate_id", "$form2->instance", "");

                }
            }
            unset($form);
    }

	elseif (!empty($form->mode)){

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
    	         	      	print_error("Could not update certificate $certificate_id of the referentiel", "certificate.php?d=$referentiel->id");
        	    	}
	                if (is_string($return)) {
    	           	    print_error($return, "certificate.php?d=$referentiel->id");
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
					if (isset($form->userid) && ($form->userid>0)){
						$userid_filtre=$form->userid;
					} 
					
	    	    	$return = $updatefunction($form);

    	    	    if (!$return) {
					/*
            		    if (file_exists($moderr)) {
                			$form = $form;
                    		include_once($moderr);
                        	die;
	                    }
					*/
    	            	print_error("Could not update certificate $form->id of the referentiel", "certificate.php?d=$referentiel->id");
					}
		            if (is_string($return)) {
    		        	print_error($return, "certificate.php?d=$referentiel->id");
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
					print_error("Could not add a new certificate to the referentiel", "certificate.php?d=$referentiel->id");
				}
	        	if (is_string($return)) {
    	        	print_error($return, "certificate.php?d=$referentiel->id");
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
	            	print_error("Could not delete certificate of  the referentiel");
                }
	            unset($SESSION->returnpage);
	            add_to_log($course->id, referentiel, "add",
                           "suppression certificate $form->certificate_id ",
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
	    	redirect("certificate.php?d=$referentiel->id");
    	}
		
        exit;
	   }
    }
	// afficher les formulaires

    unset($SESSION->modform); // Clear any old ones that may be hanging around.

    $modform = "certificate.html";

/// Can't use this if there are no certificat
/*
    if (has_capability('mod/referentiel:managetemplates', $context)) {
        if (!record_exists('referentiel_certificate','referentielid',$referentiel->id)) {      // Brand new referentielbase!
            redirect($CFG->wwwroot.'/mod/referentiel/certificate.php?d='.$referentiel->id);  // Redirect to field entry
        }
    }
*/

	/// RSS and CSS and JS meta
    $meta = '<link rel="stylesheet" type="text/css" href="jauge.css" />';
    $meta .= '<link rel="stylesheet" type="text/css" href="activite.css" />';
    $meta .= '<link rel="stylesheet" type="text/css" href="certificate.css" />';
    $meta .= '<script type="text/javascript" src="functions.js"></script>'."\n";

	/// Print the page header
	$strreferentiel = get_string('modulenameplural','referentiel');
	$strcertificate = get_string('certificat','referentiel');
	$icon = '<img class="icon" src="'.$CFG->wwwroot.'/mod/referentiel/icon.gif" alt="'.get_string('modulename','referentiel').'"/>';
	$strpagename=get_string('certificats','referentiel');
	if (function_exists('build_navigation')){
		// Moodle 1.9
		$navigation = build_navigation($strpagename, $cm);
		
		print_header($course->shortname.': '.$strreferentiel, $course->fullname, $navigation, 
		'', // focus
		$meta,
		true, // page is cacheable
		// update_module_button($cm->id, $course->id, get_string('modulename', 'referentiel')), // HTML code for a button (usually for module editing)
		update_module_button($cm->id, $course->id, get_string('modulename-intance', 'referentiel')), // HTML code for a button (usually for module editing)

        navmenu($course, $cm), // HTML code for a popup menu
		false, // use XML for this page
		'', // This text will be included verbatim in the <body> tag (useful for onload() etc)
		false); // If true, return the visible elements of the header instead of echoing them.

		/// Check to see if groups are being used here
		/// find out current groups mode
   		$groupmode = groups_get_activity_groupmode($cm);
	    $currentgroup = groups_get_activity_group($cm, true);
    	groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/referentiel/certificate.php?d='.$referentiel->id.'&amp;mode='.$mode.'&amp;select_acc='.$select_acc);
		
	}
	else{
		// Moodle 1.8
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
		/// Check to see if groups are being used here
		/// find out current groups mode
	    // 1.9 $groupmode = groups_get_activity_groupmode($cm);
    	// 1.9 $currentgroup = groups_get_activity_group($cm, true);
		// 1.9 groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/referentiel/certificate.php?d='.$referentiel->id);
		// 1.8
		$groupmode = groupmode($course, $cm);
        $currentgroup = setup_and_print_groups($course, $groupmode, $CFG->wwwroot . '/mod/referentiel/certificate.php?d='.$referentiel->id.'&amp;mode='.$mode.'&amp;select_acc='.$select_acc);
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
		$mode='listcertif';		
	}
	if (isset($mode) && ($mode=="certificat")){
		$mode='listcertif';		
	}
	
	// DEBUG
	// echo "<br /> MODE : $mode\n";
	
	if (isset($mode) && (($mode=="deletecertif") 
		|| ($mode=="updatecertif")  
		|| ($mode=="approvecertif")
		|| ($mode=="deverrouiller")
		|| ($mode=="verrouiller")
		|| ($mode=="commentcertif"))){
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
    if (($mode=='listcertifsingle') && ($certificate_id>0)){
		// referentiel_print_un_certificate_detail($certificate_id, $referentiel, $userid);
        referentiel_print_un_certificate_detail($certificate_id, $referentiel, $userid_filtre, $select_acc);
	}
	elseif (($mode=='list') || ($mode=='listcertif')){
		referentiel_print_liste_certificats($mode, $referentiel, $userid_filtre, $gusers, $select_acc); 
	}
	else {
		// formulaires
        if (($mode=='editcertif') && !$certificate_id && has_capability('mod/referentiel:managecertif', $context)){
                referentiel_evalue_global_liste_certificats($mode, $referentiel, $userid_filtre, $gusers, $sql_filtre_where, $sql_filtre_order, $data_filtre, $select_acc);
        }
		else{

            print_simple_box_start('center', '', '', 5, 'generalbox', $referentiel->name);

            if ($mode=='editcertif') {

                if( $certificate_id) {
                    // id certificate : un certificate particulier
                    if (! $record = get_record('referentiel_certificate', 'id', $certificate_id)) {
                        print_error('certificate ID is incorrect');
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
    		            print_error('certificate ID is incorrect');
        		    }
    			}
    			else{
    				print_error('certificate ID is incorrect');
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
    		            print_error('certificate ID is incorrect');
        		    }
    			}
    			else{
    				print_error('certificate ID is incorrect');
    			}
    			$modform = "certificate_add.html";
    		}
		
            if (file_exists($modform)) {
                if ($usehtmleditor = can_use_html_editor()) {
        	       $defaultformat = FORMAT_HTML;
            	   $editorfields = '';
                }
                else {
                    $defaultformat = FORMAT_MOODLE;
                }
                include_once($modform);
            }
    	    print_simple_box_end();
        }
    }

    print_footer($course);

?>
