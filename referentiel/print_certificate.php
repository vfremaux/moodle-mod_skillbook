<?php  // $Id: print_certificate.php,v 1.0 2008/04/29/ 00:00:00 jfruitet Exp $
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
* D'apres quiz/export.php 
* Export instance referentiel + certificat
*
* @version $Id: print_certificate.php,v 1.0 2008/04/29/ 00:00:00 jfruitet Exp $
* @author Martin Dougiamas, Howard Miller, and many others.
*         {@link http://moodle.org}
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package referentiel
*/

  require_once('../../config.php');
  require_once('lib.php');
  require_once('lib_etab.php');
  // require_once('pagelib.php'); // ENTETES
  include('lib_accompagnement.php');
  include('lib_certificate.php');	
  include('print_lib_certificate.php');	// AFFICHAGES 
  include('print_lib.php');	// PRINT	

  $exportfilename = optional_param('exportfilename','',PARAM_FILE );
  $print = optional_param('print','', PARAM_FILE );
	
  $id    = optional_param('id', 0, PARAM_INT);    // course module id    
	$d     = optional_param('d', 0, PARAM_INT);    // referentielbase id
	
  $certificate_id   = optional_param('certificate_id', 0, PARAM_INT);    //record certificate id

  $mode           = optional_param('mode','', PARAM_ALPHA);	
  $add           = optional_param('add','', PARAM_ALPHA);
  $update        = optional_param('update', 0, PARAM_INT);
  $delete        = optional_param('delete', 0, PARAM_INT);
  $approve        = optional_param('approve', 0, PARAM_INT);	
  $comment        = optional_param('comment', 0, PARAM_INT);		
  $course        = optional_param('course', 0, PARAM_INT);
  $groupmode     = optional_param('groupmode', -1, PARAM_INT);
  $cancel        = optional_param('cancel', 0, PARAM_BOOL);
	$userid = optional_param('userid', 0, PARAM_INT);
	$select_acc = optional_param('select_acc', 0, PARAM_INT);      // coaching
	$select_all = optional_param('select_all', 0, PARAM_INT);      // tous les certificats
	
	
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
		error(get_string('erreurscript','referentiel','Erreur01 : print_certificate.php'));
	}
	
	if ($certificate_id) { // id certificat
        if (! $record = get_record('referentiel_certificate', 'id', $certificate_id)) {
            print_error('certificate ID is incorrect');
        }
	}

    // get display strings
    $txt = new object;
    $txt->referentiel = get_string('referentiel','referentiel');
    $txt->download = get_string('download','referentiel');
    $txt->downloadextra = get_string('downloadextra','referentiel');
    $txt->exporterror = get_string('exporterror','referentiel');
    $txt->exportname = get_string('exportname','referentiel');
    $txt->printcertificate = get_string('printcertificat', 'referentiel');
    $txt->fileprint = get_string('fileprint','referentiel');
    $txt->modulename = get_string('modulename','referentiel');
    $txt->modulenameplural = get_string('modulenameplural','referentiel');
    $txt->tofile = get_string('tofile','referentiel');
	
	// PAS DE RSS
    // require_once("$CFG->libdir/rsslib.php");

	
    require_login($course->id, 0, $cm);

    if (!isloggedin() or isguest()) {
        redirect('view.php?id='.$cm->id.'noredirect=1');
    }

    // check role capability
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    require_capability('mod/referentiel:export', $context);

	// ensure the files area exists for this course
    make_upload_directory( "$course->id/$CFG->moddata/referentiel" );

    if ($certificate_id) {    // So do you have access?
        if (!(has_capability('mod/referentiel:writereferentiel', $context) 
			or referentiel_certificate_isowner($certificate_id)) or !confirm_sesskey() ) {
            error(get_string('noaccess','referentiel'));
        }
    }
	
	// parametres d'impression
	$param=referentiel_get_param_configuration($referentiel->id, 'printconfig'); 
	// DEBUG
	// echo "<br />DEBUG :: print_certificate.php :: 168 \n";
	// print_r($param);
	// exit;

  // selecteur
	$userid_filtre=0;
  if (!empty($userid)) {
    $userid_filtre=$userid;	
  }
	

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
            redirect('certificate.php?id='.$cm->id);
        }
    }

 
// tous les certificats ?
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

	/// selection utilisateurs accompagnés
	if (isset($action) && ($action=='select_all_certificates')){
		  if (isset($form->select_all) && confirm_sesskey() ){
		  	$select_all=$form->select_all;
		  }
		  if (isset($form->mode) && ($form->mode!='')){
			 $mode=$form->mode;
		  }
		  // echo "<br />ACTION : $action  SEARCH : $userid_filtre\n";
		  unset($form);
		  unset($action);
		  // exit;
  }
    
	if (!isset($select_all)){
    if (isset($form->select_all)){
        $select_all=$form->select_all;
    }
    else{ 
      $select_all=0 ;
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

  if (!empty($print) && !empty($referentiel) && !empty($course)) {   
        if (!confirm_sesskey()) {
            print_error( 'sesskey' );
        }
		
		// selections
		if (isset($form->certificate_sel_referentiel) && ($form->certificate_sel_referentiel=="1")){
			$param->certificate_sel_referentiel=1;
		}
		else{
			$param->certificate_sel_referentiel=0;
		}
		if (isset($form->certificate_sel_referentiel_instance) && ($form->certificate_sel_referentiel_instance=="1")){
			$param->certificate_sel_referentiel_instance=1;
		}
		else{
			$param->certificate_sel_referentiel_instance=0;
		}

		if (isset($form->certificate_sel_student_numero) && ($form->certificate_sel_student_numero=="1")){
			$param->certificate_sel_student_numero=1;
		}
		else{
			$param->certificate_sel_student_numero=0;
		}
		if (isset($form->certificate_sel_student_nom_prenom) && ($form->certificate_sel_student_nom_prenom=="1")){
			$param->certificate_sel_student_nom_prenom=1;
		}
		else{
			$param->certificate_sel_student_nom_prenom=0;
		}
		
		if (isset($form->certificate_sel_student_etablissement) && ($form->certificate_sel_student_etablissement=="1")){
			$param->certificate_sel_student_etablissement=1;
		}
		else{
			$param->certificate_sel_student_etablissement=0;
		}
		
		if (isset($form->certificate_sel_student_ddn) && ($form->certificate_sel_student_ddn=="1")){
			$param->certificate_sel_student_ddn=1;
		}
		else{
			$param->certificate_sel_student_ddn=0;
		}
		
		if (isset($form->certificate_sel_student_lieu_naissance) && ($form->certificate_sel_student_lieu_naissance=="1")){
			$param->certificate_sel_student_lieu_naissance=1;
		}
		else{
			$param->certificate_sel_student_lieu_naissance=0;
		}
		
		if (isset($form->certificate_sel_student_adresse) && ($form->certificate_sel_student_adresse=="1")){
			$param->certificate_sel_student_adresse=1;
		}
		else{
			$param->certificate_sel_student_adresse=0;
		}

		if (isset($form->certificate_sel_certificate_detail) && ($form->certificate_sel_certificate_detail=="1")){
			$param->certificate_sel_certificate_detail=1;
		}
		else{
			$param->certificate_sel_certificate_detail=0;
		}

		if (isset($form->certificate_sel_certificate_pourcent) && ($form->certificate_sel_certificate_pourcent=="1")){
			$param->certificate_sel_certificate_pourcent=1;
		}
		else{
			$param->certificate_sel_certificate_pourcent=0;
		}
		if (isset($form->certificate_sel_activite_competences) && ($form->certificate_sel_activite_competences=="1")){
			$param->certificate_sel_activite_competences=1;
		}
		else{
			$param->certificate_sel_activite_competences=0;
		}
		if (isset($form->certificate_sel_certificate_competences) && ($form->certificate_sel_certificate_competences=="1")){
			$param->certificate_sel_certificate_competences=1;
		}
		else{
			$param->certificate_sel_certificate_competences=0;
		}
		if (isset($form->certificate_sel_certificate_referents) && ($form->certificate_sel_certificate_referents=="1")){
			$param->certificate_sel_certificate_referents=1;
		}
		else{
			$param->certificate_sel_certificate_referents=0;
		}

		if (isset($form->certificate_sel_decision_jury) && ($form->certificate_sel_decision_jury=="1")){
			$param->certificate_sel_decision_jury=1;
		}
		else{
			$param->certificate_sel_decision_jury=0;
		}
		if (isset($form->certificate_sel_commentaire) && ($form->certificate_sel_commentaire=="1")){
			$param->certificate_sel_commentaire=1;
		}
		else{
			$param->certificate_sel_commentaire=0;
		}
		// enregitrer les paramètres ?
		if (isset($form->sauver_parametre) && ($form->sauver_parametre=="1")){
			referentiel_set_param_configuration($param, $referentiel->id, 'printconfig');
		}

		// DEBUG
		// $param=referentiel_get_param_configuration($referentiel->id, 'printconfig'); 
		// echo "<br />DEBUG :: print_certificate.php :: 276 \n";
		// print_r($param);
		// exit;
    // Groupes ?
 		if (function_exists('build_navigation')){
	     //1.9
       /// find out current groups mode
   	   $groupmode = groups_get_activity_groupmode($cm);
	     $currentgroup = groups_get_activity_group($cm, 1);
		}
		else{
       // 1.8
		   $groupmode = groupmode($course, $cm);
       $currentgroup = get_current_groupe($course->id, false);
		}
    /// Get all users 
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

    $records_certificats=referentiel_get_liste_certificats($referentiel, $userid_filtre, $gusers, $select_acc, $mode, 'print_certificate.php', $select_all, false);     // pas d'affichage des botes de selection
			
		// TRAITEMENT SPECIAL PDF / RTF / DOC / ODT
		if ($print=="rtf"){
			require_once("print_rtf.php");
			
			// ************************** INITIALISATION RTF *********************
			$file_logo=referentiel_get_logo($referentiel);
			if ($file_logo!=""){
				$image_logo=referentiel_get_file($file_logo, $referentiel->course);
			}
			else{
				$image_logo="";
			}
      // Instanciation de la classe dérivée
			// A4 paysage en mm
			$nom_fichier = 'certification-'.date("Ymshis").'-'.md5(uniqid());
      $rtf=new RTFClass();
      /*
      'P','mm','A4');        
			$pdf->SetDisplayMode('real');
			$pdf->Open();
			*/
      $copyright = chr(169);
      $registered ="®";
      $puce =  chr(149);			
      // $pdf->AliasNbPages();
			 
	    rtf_write_certification($referentiel, $referentiel_referentiel, $userid, $param, $records_certificats);
			$rtf->sendRtf($nom_fichier);
			exit;
		}
		elseif ($print=="pdf"){
			require_once("print_pdf.php");
			
			// ************************** INITIALISATION PDF *********************
			$file_logo=referentiel_get_logo($referentiel);
			if ($file_logo!=""){
				$image_logo=referentiel_get_file($file_logo, $referentiel->course);
			}
			else{
				$image_logo="";
			}
			// Instanciation de la classe dérivée
			// A4 paysage en mm
			$pdf=new PDF('P','mm','A4');        
			$pdf->SetDisplayMode('real');
			$pdf->Open();
			$copyright = chr(169);
			$registered ="®";
			$puce =  chr(149);			
			$pdf->AliasNbPages();
			 
	    pdf_write_certification($referentiel, $referentiel_referentiel, $userid, $param, $records_certificats);
			$pdf->Output();
			exit;
		}
		elseif ($print=="msword"){
			require_once("print_doc_word.php");
			
			// ************************** INITIALISATION MSWORD *********************
			$file_logo=referentiel_get_logo($referentiel);
			if ($file_logo!=""){
				$image_logo=referentiel_get_file($file_logo, $referentiel->course);
			}
			else{
				$image_logo="";
			}
			// Instanciation de la classe dérivée
			// A4 paysage en mm
			$mswd=new MSWord();        
			$mswd->SetEntete();

			$copyright = chr(169);
			$registered ="®";
			$puce =  chr(149);
			$mswd->SetAutoPageBreak(1, 290);     
			$mswd->SetCol(0);
	    msword_write_certification($referentiel, $referentiel_referentiel, $userid, $param, $records_certificats);
			$mswd->SetEnqueue();
			exit;
		}
		elseif ($print=="ooffice"){
			require_once("print_doc_odt.php");
			
			// ************************** INITIALISATION OpenOffice *********************
			$file_logo=referentiel_get_logo($referentiel);
			if ($file_logo!=""){
				$image_logo=referentiel_get_file($file_logo, $referentiel->course);
			}
			else{
				$image_logo="";
			}
			// Instanciation de la classe dérivée
			// A4 paysage en mm
			$odt=new OOffice();        
			$odt->SetEntete();

			$copyright = chr(169);
			$registered ="®";
			$puce =  chr(149);
			$odt->SetAutoPageBreak(1, 290);     
			$odt->SetCol(0);
	    ooffice_write_certification($referentiel, $referentiel_referentiel, $userid, $param, $records_certificats);
			$odt->SetEnqueue();
			exit;
		}
		else{
			
    	$defaultprint = FORMAT_MOODLE;

	    /// RSS and CSS and JS meta
      $meta = '<link rel="stylesheet" type="text/css" href="jauge.css" />';
	    $meta .= '<link rel="stylesheet" type="text/css" href="activite.css" />';
      $meta .= '<link rel="stylesheet" type="text/css" href="certificate.css" />';
			
			/// Print the page header
			$strreferentiels = get_string('modulenameplural','referentiel');
			$strreferentiel = get_string('referentiel','referentiel');
			$strmessage = get_string('printcertificat','referentiel');		
			$icon = '<img class="icon" src="'.$CFG->wwwroot.'/mod/referentiel/icon.gif" alt="'.get_string('modulename','referentiel').'"/>';
			
			$strpagename=get_string('printcertificat','referentiel');

			if (function_exists('build_navigation')){
				// Moodle 1.9
				$navigation = build_navigation($strpagename, $cm);
				
				print_header($course->shortname.': '.$strreferentiel, $course->fullname, $navigation, 
					'', // focus
					$meta,
					1, // page is cacheable
					update_module_button($cm->id, $course->id, get_string('modulename', 'referentiel')), // HTML code for a button (usually for module editing)
			        navmenu($course, $cm), // HTML code for a popup menu
					0, // use XML for this page
					'', // This text will be included verbatim in the <body> tag (useful for onload() etc)
					0); // If 1, return the visible elements of the header instead of echoing them.
				
      		/// find out current groups mode
   	      $groupmode = groups_get_activity_groupmode($cm);
	        $currentgroup = groups_get_activity_group($cm, 1);
          groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/referentiel/print_certificate.php?d='.$referentiel->id.'&amp;mode='.$mode.'&amp;select_acc='.$select_acc);		
			}
			else{
				// Moodle 1.8
				print_header_simple($referentiel->name, // title
				'', // heading
				"<a href='index.php?id=$course->id'>$strreferentiel</a> -> $referentiel->name", // navigation
				'', // focus
				$meta, // meta tag
				1, // page is cacheable
				update_module_button($cm->id, $course->id, get_string('modulename', 'referentiel')), // HTML code for a button (usually for module editing)
		        navmenu($course, $cm), // HTML code for a popup menu
				0, // use XML for this page
				'', // This text will be included verbatim in the <body> tag (useful for onload() etc)
				0) // If 1, return the visible elements of the header instead of echoing them.
				;
    		
        // 1.8
		    $groupmode = groupmode($course, $cm);
        $currentgroup = setup_and_print_groups($course, $groupmode, $CFG->wwwroot . '/mod/referentiel/print_certificate.php?d='.$referentiel->id.'&amp;mode='.$mode.'&amp;select_acc='.$select_acc);
			}
			
			print_heading(format_string($referentiel->name));
			/// Print the tabs
			if (!isset($mode)){
				$mode='printcertif'; // un seul mode possible
			}
			$currenttab = 'printcertif';
			include('tabs.php');
			print_heading_with_help($strmessage, 'printcertificat', 'referentiel', $icon);
	
			
			if (! is_readable("print/$print/print.php")) {
    	    	print_error( "print not known ($print)" );  
			}

	    // load parent class for import/export
    	require("print.php");

		  // and then the class for the selected print
    	require("print/$print/print.php");
	    $classname = "pprint_$print";
	    $pprint = new $classname();
			
		  // $pprint->setCategory( $category );
		  $pprint->setUserid($userid);
			$pprint->setParam( $param); // paraemtres de selection
			$pprint->setCoursemodule( $cm );
	    $pprint->setCourse( $course );
		  $pprint->setFilename( $exportfilename );
			$pprint->setReferentielInstance($referentiel);
			$pprint->setReferentielReferentiel($referentiel_referentiel);
			$pprint->setEmpreintes($referentiel_referentiel);
			$pprint->setPoids($referentiel_referentiel);
			$pprint->setRCertificats($records_certificats);
			
	    if (! $pprint->exportpreprocess()) {   // Do anything before that we need to
    	        print_error( $txt->exporterror, $CFG->wwwroot.'/mod/referentiel/print_certificate.php?id='.$cm->id);
      }

	    if (! $pprint->exportprocess($userid)) {         // Process the export data
    	        print_error( $txt->exporterror, $CFG->wwwroot.'/mod/referentiel/print_certificate.php?id='.$cm->id);
      }

	    if (! $pprint->exportpostprocess()) {                    // In case anything needs to be done after
    	        print_error( $txt->exporterror, $CFG->wwwroot.'/mod/referentiel/print_certificate.php?d='.$cm->id);
      }
	    echo "<hr />";
	    // link to download the finished file
    	$file_ext = $pprint->export_file_extension();
      if ($CFG->slasharguments) {
	          $efile = "{$CFG->wwwroot}/file.php/".$pprint->get_export_dir()."/$exportfilename".$file_ext."?forcedownload=1";
    	}
      else {
	          $efile = "{$CFG->wwwroot}/file.php?file=/".$pprint->get_export_dir()."/$exportfilename".$file_ext."&forcedownload=1";
    	}
      echo "<p><div class=\"boxaligncenter\"><a href=\"$efile\">$txt->download</a></div></p>";
	    echo "<p><div class=\"boxaligncenter\"><font size=\"-1\">$txt->downloadextra</font></div></p>";
						
	    print_continue($CFG->wwwroot.'/mod/referentiel/certificate.php?id='.$cm->id);
    	print_footer($course);
      exit;
    }
	}
	
	$defaultprint = FORMAT_MOODLE;

	/// RSS and CSS and JS meta
  $meta = '<link rel="stylesheet" type="text/css" href="jauge.css" />';
	$meta .= '<link rel="stylesheet" type="text/css" href="activite.css" />';
  $meta .= '<link rel="stylesheet" type="text/css" href="certificate.css" />';

	/// Print the page header
	$strreferentiels = get_string('modulenameplural','referentiel');
	$strreferentiel = get_string('referentiel','referentiel');
	$strmessage = get_string('printcertificat','referentiel');		
	$icon = '<img class="icon" src="'.$CFG->wwwroot.'/mod/referentiel/icon.gif" alt="'.get_string('modulename','referentiel').'"/>';
	$strpagename=get_string('printcertificat','referentiel');

	if (function_exists('build_navigation')){
		// Moodle 1.9
		$navigation = build_navigation($strpagename, $cm);
				
		print_header($course->shortname.': '.$strreferentiel, $course->fullname, $navigation, 
		'', // focus
		$meta,
		1, // page is cacheable
		update_module_button($cm->id, $course->id, get_string('modulename', 'referentiel')), // HTML code for a button (usually for module editing)
        navmenu($course, $cm), // HTML code for a popup menu
		0, // use XML for this page
		'', // This text will be included verbatim in the <body> tag (useful for onload() etc)
		0); // If 1, return the visible elements of the header instead of echoing them.
		/// Check to see if groups are being used here
		
		/// find out current groups mode
   	$groupmode = groups_get_activity_groupmode($cm);
	  $currentgroup = groups_get_activity_group($cm, 1);
    groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/referentiel/print_certificate.php?d='.$referentiel->id.'&amp;mode='.$mode.'&amp;select_acc='.$select_acc);		
	}
	else{
		// Moodle 1.8
		print_header_simple($referentiel->name, // title
		'', // heading
		"<a href='index.php?id=$course->id'>$strreferentiel</a> -> $referentiel->name", // navigation
		'', // focus
		$meta, // meta tag
		1, // page is cacheable
		update_module_button($cm->id, $course->id, get_string('modulename', 'referentiel')), // HTML code for a button (usually for module editing)
        navmenu($course, $cm), // HTML code for a popup menu
		0, // use XML for this page
		'', // This text will be included verbatim in the <body> tag (useful for onload() etc)
		0) // If 1, return the visible elements of the header instead of echoing them.
		;		
		// 1.8
		$groupmode = groupmode($course, $cm);
    $currentgroup = setup_and_print_groups($course, $groupmode, $CFG->wwwroot . '/mod/referentiel/print_certificate.php?d='.$referentiel->id.'&amp;mode='.$mode.'&amp;select_acc='.$select_acc);
	}
	print_heading(format_string($referentiel->name));
	/// Print the tabs
	if (!isset($mode)){
		$mode='printcertif'; // un seul mode possible
	}
	$currenttab = 'printcertif';
	
	include('tabs.php');

	/// Get all users 
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

	print_heading_with_help($strmessage, 'printcertificat', 'referentiel', $icon);
  $records_certificats=referentiel_get_liste_certificats($referentiel, $userid_filtre, $gusers, $select_acc, $mode, 'print_certificate.php', $select_all, true); // affichage des boites de selection
	
  /// Display upload form
  // get valid prints to generate dropdown list
  $fileprintnames = referentiel_get_print_formats( 'print', 'pprint' );
  // get filename
  if (empty($exportfilename)) {
   	$exportfilename = referentiel_default_print_filename($course, $referentiel, 'certificat');
  }

	$modform='print_certificate.html';

  echo "\n<br />\n";
  print_box_start('generalbox boxwidthnormal boxaligncenter');
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
    	    notice("ERREUR : No file found at : $modform)", "certificate.php?d=$referentiel->id");
  }
	include_once($modform);	
	print_box_end();
  print_footer($course);

?>
