<?php  // $Id: view.php,v 1.0 2008/02/28 00:00:00 mark-nielsen jfruitet Exp $
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
 * This page prints a  referentiel
 * 
 * @author JF
 * @version $Id: view.php,v 1.0 2008/02/28 00:00:00 jfruitet Exp $
 * @package referentiel
 **/

/// (Replace newmodule with the name of your module reference)
/// inspire du module data

  require_once("../../config.php");
  require_once("lib.php");
  require_once("print_lib_referentiel.php");	// AFFICHAGES 
  require_once("lib_accompagnement.php");

  $id    	= optional_param('id', 0, PARAM_INT);    // course module id
  $d 		= optional_param('d', 0, PARAM_INT); // Referentiel ID	
  $mode  	= optional_param('mode', '', PARAM_ALPHA);    // Force the browse mode  ('single')
	
	// editer rubrique
  $edit 	= optional_param('edit', -1, PARAM_BOOL);

	/// These can be added to perform an action on a record actuivite
  $approve 	= optional_param('approve', 0, PARAM_INT);    //approval recordid
  $delete 	= optional_param('delete', 0, PARAM_INT);    //delete recordid
  
  // MODIF JF 22/01/2010
  $noredirect = optional_param('noredirect', 0, PARAM_INT);    // par defaut on redirige vers activite	
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
		error(get_string('erreurscript','referentiel','Erreur01 : view.php'));
	}

  	// CONTEXTE
  	$context = get_context_instance(CONTEXT_MODULE, $cm->id);
	
	/*
	require_login($course->id);
	if (!isloggedin() or isguest()) {
	    redirect("$CFG->wwwroot/course/view.php?id=$course->id");
	}
	*/
  
  	$strreferentiels = get_string('modulenameplural', 'referentiel');
  	$strreferentiel  = get_string('modulename', 'referentiel');
	
	/// If it's hidden then it's don't show anything.  :)
	/// Some capability checks.
  	if (empty($cm->visible) and !has_capability('moodle/course:viewhiddenactivities', $context)) {
    	$strreferentielbases = get_string('modulenameplural', 'referentiel');
		$navigation = build_navigation($strreferentielbases, $cm);
  		print_header_simple(format_string($referentiel->name), '', $navigation, '',  '', true, '', navmenu($course, $cm));
    	notice(get_string("activityiscurrentlyhidden"),"$CFG->wwwroot/course/view.php?id=$course->id"); 
  	}

	// lien vers le referentiel lui-meme
	if (!empty($referentiel->referentielid)){
	    if (!$referentiel_referentiel = get_record('referentiel_referentiel', 'id', $referentiel->referentielid)) {
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
  	require_capability('mod/referentiel:view', $context);

  	// REDIRECTION ? 
  	if (!isset($noredirect) || ($noredirect == 0)){
      	// redirection vers les activites sans message "continuer"
      	if (referentiel_has_pupils($referentiel->id, $course->id, $USER->id)>0){
            redirect($CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel->id.'&amp;select_acc=1&amp;mode=listactivity&amp;sesskey='.sesskey(),  '', 0);
      	} else {
            redirect($CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel->id.'&amp;select_acc=0&amp;mode=listactivity&amp;sesskey='.sesskey(),  '', 0);
      	}  
	  	exit;
  	}

	
/* A UTILISER POUR LISTAGE DES ACTIVITE */

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
        	$navigation = "<a href=\"{$CFG->wwwroot}/course/view.php?id=$cm->id\">$course->shortname</a> ->";
	} else {
        	$navigation = '';
	}

    add_to_log($course->id, 'referentiel', "view", "view.php?id=$cm->id", "$course->id");

	/// Print the page header
	$strreferentiels = get_string('modulenameplural','referentiel');
	$strreferentiel = get_string('referentiel','referentiel');
	// $strreferentiel = get_string('modulename-intance','referentiel');
	$icon = '<img class="icon" src="'.$CFG->wwwroot.'/mod/referentiel/icon.gif" alt="'.get_string('modulename','referentiel').'"/>';
	/// RSS and CSS and JS meta
    $meta = '<link rel="stylesheet" type="text/css" href="referentiel.css" />';;

	$strpagename = get_string('listreferentiel','referentiel');
	if (function_exists('build_navigation')){
		// Moodle 1.9
		$navigation = build_navigation($strpagename, $cm);
		
		print_header($course->shortname.': '.$strreferentiel, 
		$course->fullname, 
		$navigation, 
		'', // focus
		$meta,
		true, // page is cacheable
		// update_module_button($cm->id, $course->id, get_string('modulename', 'referentiel')), // HTML code for a button (usually for module editing)
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
//		update_module_button($cm->id, $course->id, get_string('modulename', 'referentiel')), // HTML code for a button (usually for module editing)
		update_module_button($cm->id, $course->id, get_string('modulename-intance', 'referentiel')), // HTML code for a button (usually for module editing)
        navmenu($course, $cm), // HTML code for a popup menu
		false, // use XML for this page
		'', // This text will be included verbatim in the <body> tag (useful for onload() etc)
		false) // If true, return the visible elements of the header instead of echoing them.
		;
	}
	/*
	/// find out current groups mode
    $groupmode = groups_get_activity_groupmode($cm);
    $currentgroup = groups_get_activity_group($cm, true);
	groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/referentiel/view.php?d='.$referentiel->id);
	*/

	$strmessage=format_string($referentiel_referentiel->name);
	
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
	
	if (!isset($mode) || ($mode == "")) {
		$mode='configreferentiel';
	}
	
	if (isset($mode)){
		if ($mode == 'editreferentiel') {
			$currenttab = 'editreferentiel';
    	} else {
			$currenttab = 'listreferentiel';
    	}
	}
	// Onglets
	
    include('tabs.php');
	
    print_heading_with_help($strpagename, 'referentiel', 'referentiel', $icon);
	referentiel_affiche_referentiel_instance($referentiel->id);

/// Finish the page
    print_footer($course);
?>
