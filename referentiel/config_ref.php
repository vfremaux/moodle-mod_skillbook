<?php  // $Id: config_ref.php,v 1.0 2010/10/19 00:00:00 jfruitet Exp $
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
 * Settings page of a referentiel
 * 
 * @author JF
 * @version $Id: config_ref.php,v 1.0 2010/10/19 00:00:00 jfruitet Exp $
 * @package referentiel
 **/

  require_once("../../config.php");
  require_once("lib.php");

  $id   			= optional_param('id', 0, PARAM_INT);    // course module id
  $d 				= optional_param('d', 0, PARAM_INT); // Referentiel ID
  $pass  			= optional_param('pass', 0, PARAM_INT);    // mot de passe ok
  $checkpass 		= optional_param('checkpass','', PARAM_ALPHA); // mot de passe fourni
  $mode 			= optional_param('mode', '', PARAM_ALPHA);    // Force the browse mode  ('single')
  $sesskey     		= optional_param('sesskey', '', PARAM_ALPHA);
  $coursemodule     = optional_param('coursemodule', 0, PARAM_INT);
  $section 			= optional_param('section', 0, PARAM_INT);
  $module 			= optional_param('module', 0, PARAM_INT);
  $modulename     	= optional_param('modulename', '', PARAM_ALPHA);
  $instance 		= optional_param('instance', 0, PARAM_INT);

  $noredirect 		= optional_param('noredirect', 0, PARAM_INT);    // par defaut on redirige vers activite
  $select_acc 		= optional_param('select_acc', 0, PARAM_INT);      // coaching

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
        	print_error('Course Module ID is incorrect');
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
		error(get_string('erreurscript', 'referentiel', 'Erreur01 : config_ref.php'));
	}

/// CONTEXT

  	$context = get_context_instance(CONTEXT_MODULE, $cm->id);
	
  	require_login($course->id);

  	if (!isloggedin() or isguest()) {
        redirect("$CFG->wwwroot/course/view.php?id=$course->id");
  	}
  
  	$strreferentiels = get_string('modulenameplural', 'referentiel');
  	$strreferentiel  = get_string('modulename', 'referentiel');
	
	/// If it's hidden then it's don't show anything.  :)
	/// Some capability checks.

  	if (!$cm->visible and !has_capability('moodle/course:viewhiddenactivities', $context)) {
		$strreferentielbases = get_string('modulenameplural', 'referentiel');
	  	$navigation = build_navigation($strreferentielbases, $cm);
		print_header_simple(format_string($referentiel->name), '', $navigation, '', '', true, '', navmenu($course, $cm));
    	notice(get_string('activityiscurrentlyhidden'),"$CFG->wwwroot/course/view.php?id=$course->id"); 
    	print_footer($course);
    	die;
  	}

	// lien vers le referentiel lui-meme
	if (!empty($referentiel->referentielid)){
	    if (! $referentiel_referentiel = get_record('referentiel_referentiel', 'id', $referentiel->referentielid)) {
    		print_error('Referentiel id is incorrect');
    	}
    } else {
		// rediriger vers la creation du referentiel
		require_login($course->id);

        if (!isloggedin() or isguest()) {
            redirect("$CFG->wwwroot/course/view.php?id=$course->id");
        }

		$returnlink = "$CFG->wwwroot/mod/referentiel/add.php?id=$cm->id&amp;sesskey=".sesskey();
        redirect($returnlink);
	}


	// A MODIFIER / ADAPTER
    require_capability('mod/referentiel:writereferentiel', $context);

    /// Check further parameters that set browsing preferences
    if (!isset($SESSION->dataprefs)) {
        $SESSION->dataprefs = array();
    }
    if (!isset($SESSION->dataprefs[$referentiel->id])) {
        $SESSION->dataprefs[$referentiel->id] = array();
        $SESSION->dataprefs[$referentiel->id]['local'] = 0;
    }

    $textlib = new textlib();

	if ($course->category) {
        	$navigation = "<a href=\"../../course/view.php?id=$cm->id\">$course->shortname</a> ->";
	} else {
        	$navigation = '';
	}


    // RECUPERER LES FORMULAIRES
    if (isset($SESSION->modform)) {   // Variables are stored in the session
        $form = $SESSION->modform;
        unset($SESSION->modform);
    } else {
        $form = (object)$_POST;
    }

	$msg = "";

	if (!empty($course) && !empty($cm) && !empty($referentiel_referentiel) && isset($form)) {
		if (!$pass && ($checkpass == 'checkpass')){
            if (!empty($form->password) && $referentiel_referentiel){
                if (!empty($form->force_pass)){  // forcer la sauvegarde sans verification
                    $pass = referentiel_set_pass($referentiel_referentiel->id, $form->password);
                } else { // tester le mot de passe
                    $pass = referentiel_check_pass($referentiel_referentiel, $form->password);
                }
                if (!$pass){
                    // Abandonner
                    redirect("$CFG->wwwroot/mod/referentiel/view.php?id=$cm->id");
                    exit;
                }
            } else {    // mot de passe vide mais c'est un admin qui est connecté
                if (!empty($form->force_pass)){
                    $pass = 1; // on passe... le mot de passe !
                }
            }
		}

		// variable d'action
		if (!empty($form->cancel)){
			if ($form->cancel == get_string('quit', 'referentiel')){
				// Abandonner
    		    redirect("$CFG->wwwroot/mod/referentiel/view.php?id=$cm->id");
       			exit;
			}
		}
		// mise à jour de la configuration
		// variable d'action Enregistrer
		else if (!empty($form->action) && ($form->action == 'modifierconfig') && !empty($form->mode) && ($form->mode == 'configref')){
            // sauvegarder
            $config = referentiel_form2config($form, 'config');
            referentiel_global_set_vecteur_config($config, $referentiel_referentiel->id);
		    $printconfig = referentiel_form2config($form, 'printconfig');
            referentiel_global_set_vecteur_config_imp($printconfig, $referentiel_referentiel->id);

            add_to_log($course->id, 'referentiel', 'config', "config_ref.php?id=$cm->id", "$course->id");

	        if (isset($form->redirect)) {
                $SESSION->returnpage = $form->redirecturl;
        	}
			else {
                $SESSION->returnpage = "$CFG->wwwroot/mod/referentiel/view.php?id=$cm->id";
	        }

	        redirect($SESSION->returnpage);
		}
	}
	

	/// Print the page header
	$strreferentiels = get_string('modulenameplural','referentiel');
	$strreferentiel = get_string('referentiel','referentiel');
	// $strreferentiel = get_string('modulename-intance','referentiel');
	$icon = '<img class="icon" src="'.$CFG->wwwroot.'/mod/referentiel/icon.gif" alt="'.get_string('modulename','referentiel').'"/>';
	/// RSS and CSS and JS meta
    $meta = '<link rel="stylesheet" type="text/css" href="referentiel.css" />';;

	$strpagename=get_string('configref','referentiel');
	if (function_exists('build_navigation')){
		// Moodle 1.9
		$navigation = build_navigation($strpagename, $cm);
		
		print_header($course->shortname.': '.$strreferentiel, 
		$course->fullname, 
		$navigation, 
		'', // focus
		$meta,
		true, // page is cacheable
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
		update_module_button($cm->id, $course->id, get_string('modulename-intance', 'referentiel')), // HTML code for a button (usually for module editing)
        navmenu($course, $cm), // HTML code for a popup menu
		false, // use XML for this page
		'', // This text will be included verbatim in the <body> tag (useful for onload() etc)
		false) // If true, return the visible elements of the header instead of echoing them.
		;
	}

	$strmessage = format_string($referentiel_referentiel->name);
	
	echo '<link rel="stylesheet" type="text/css" href="referentiel.css"></link>'."\n";
	echo '<table align="center" cellspacing="0" cellpadding="2" border="0" width="100%">
<tr>
<td width="30%" align="center">&nbsp;</td>
<td width="30%" align="center"><h2><b>'.$strmessage.'</b></h2></td>
<td width="30%" align="right"><span class="small"><a href="'.$CFG->wwwroot.'/mod/referentiel/affiche.php?d='.$referentiel->id.'" target="_blank">'.get_string('ouvrir','referentiel').
'<span class="surligne"><b>'.get_string('referentiel','referentiel').'</b></span> '
.get_string('nouvelle_fenetre','referentiel').'</a></span>
</td></tr></table>'."\n";

    /// Print the main part of the page

    /// Print the tabs
	
	if (empty($mode)){
		$mode = 'configref';
	}
	
	if (isset($mode)){
		if ($mode == 'configref') {
			$currenttab = 'configref';
    	}
		else {
			$currenttab = 'listreferentiel';
    	}
	}
	// Onglets
	
    include('tabs.php');
	
    print_heading_with_help($strpagename, 'configref', 'referentiel', $icon);

    // formulaires
    $modform = "config_ref.html";
	if (file_exists($modform)) {
        if ($usehtmleditor = can_use_html_editor()) {
            $defaultformat = FORMAT_HTML;
            $editorfields = '';
	    } else {
            $defaultformat = FORMAT_MOODLE;
        }
    }
	else {
        notice("ERREUR : No file found at : $modform)", "config_ref.php?d=$referentiel->id");
    }
	// verifer si le mot de passe est fourni
	if ((!$pass && !empty($referentiel_referentiel->password)) && !$isreferentielauteur && !$isadmin){
		// demander le mot de passe
		$appli_appelante = "config_ref.php";
		include_once("pass.html");
	} else {
	   include_once($modform);
	}

/// Finish the page
    print_footer($course);
?>
