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
 * This page prints a particular instance of referentiel in a special frame
 * 
 * @author JF
 * @version $Id: affiche.php,v 1.0 2008/02/28 00:00:00 mark-nielsen jfruitet Exp $
 * @package referentiel
 **/


    require_once("../../config.php");
    require_once("lib.php");
    require_once("print_lib_referentiel.php");	// AFFICHAGES 
	
    $id    = optional_param('id', 0, PARAM_INT);    // course module id
    $d = optional_param('d', 0, PARAM_INT); // Referentiel ID
	$select_acc = optional_param('select_acc', 0, PARAM_INT);      // coaching
	
	$erreur=false;
	
	if ($d) {
        if (! $referentiel = get_record('referentiel', 'id', $d)) {
			$erreur=true;
        }
		if (! $course = get_record('course', 'id', $referentiel->course)) {
			$erreur=true;
    	}
		if (! $cm = get_coursemodule_from_instance('referentiel', $referentiel->id, $course->id)) {
			$erreur=true;
		}
	} 
	elseif ($id) {
        if (! $cm = get_coursemodule_from_id('referentiel', $id)) {
        	$erreur=true;
        }
        if (! $course = get_record('course', 'id', $cm->course)) {
            $erreur=true;
        }
        if (! $referentiel = get_record('referentiel', 'id', $cm->instance)) {
            $erreur=true;
        }
    } 
	else{
        $erreur=true;
	}
	
	if (!$erreur){
		$strreferentiels = get_string('modulenameplural', 'referentiel');
		$strreferentiel  = get_string('modulename', 'referentiel');
		
		
		// lien vers le referentiel lui-meme
		if (isset($referentiel->referentielid) && ($referentiel->referentielid>0)){
	    	if (!$referentiel_referentiel = get_record('referentiel_referentiel', 'id', $referentiel->referentielid)) {
    			$erreur=true;
    		}
    	}
		$textlib = new textlib();
		
		/// Print the page header
		$strreferentiels = get_string('modulenameplural','referentiel');
		$strreferentiel = $referentiel->name;
		$icon = '<img class="icon" src="'.$CFG->wwwroot.'/mod/referentiel/icon.gif" alt="'.get_string('modulename','referentiel').'"/>';
		/// RSS and CSS and JS meta
    	$meta =  '<link rel="stylesheet" type="text/css" href="referentiel.css" />';
		
		$strpagename=get_string('listreferentiel','referentiel');
		if (function_exists('build_navigation')){
			// Moodle 1.9
			$navigation = NULL;
			
			print_header($course->shortname.': '.$strreferentiel, $course->fullname, $navigation, 
				'', // focus
				$meta);
		}
		else{
		    print_header_simple($course->shortname.': '.$referentiel->name, // title
			'', // heading
			'', // navigation
			'', // focus
			$meta) // If true, return the visible elements of the header instead of echoing them.
			;
		}
    	print_heading(format_string($referentiel->name));

		/// Print the main part of the page
		
	    print_heading_with_help($strpagename, 'referentiel', 'referentiel', $icon);
		referentiel_affiche_referentiel_instance($referentiel->id);
		
		/// Finish the page
	    print_footer($course);
	}
	else{
	
	}
?>
