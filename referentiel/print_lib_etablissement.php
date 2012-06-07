<?php  // $Id:  print_lib_etablissement.php,v 1.0 2008/04/29 00:00:00 jfruitet Exp $
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
 * Print Library of functions for etablissement of module referentiel
 * 
 * @author jfruitet
 * @version $Id: lib.php,v 1.4 2006/08/28 16:41:20 mark-nielsen Exp $
 * @version $Id: lib.php,v 1.0 2008/04/29 00:00:00 jfruitet Exp $
 * @package referentiel
 **/


require_once("lib.php");


// ///////////////////// ETABLISSEMENT ////////////////////////

/*
CREATE TABLE mdl_referentiel_etablissement (
  id bigint(10) unsigned NOT NULL auto_increment,
  idnumber varchar(20) NOT NULL default '',
  name varchar(80) NOT NULL default '',
  address varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Etablissement'
*/


// Affiche une etablissement 
// *****************************************************************
// input @param a $record_e   of etablissement                          *
// output string                                                   *
// *****************************************************************


// --------------------------------------------
function referentiel_print_etablissement($record){
	$s='';
	// echo $etablissement_id."<br />\n";
	if ($record){
		$s.= '<tr bgcolor="white"><td>'.stripslashes($record->idnumber);
	    $s.= '</td><td>'.stripslashes($record->name);
		$s.= '</td><td>'.stripslashes($record->address);
		if (isset($record->logo) && ($record->logo!="")){
			$s.= '</td><td>'.stripslashes($record->logo);
		}
		else{
			$s.= '</td><td>&nbsp;';
		}
		$s.= '</td></tr>';
		$s.= "\n";
		return $s;
	}
	return "";
}


// Affiche les etablissements de ce referentiel
function referentiel_menu_etablissement($context, $referentiel_id, $etablissement_id){
	global $CFG;
	global $USER;
	$s="";
	
	if (has_capability('mod/referentiel:managecertif', $context)) {
//         $s.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/etablissement.php?d='.$referentiel_id.'&amp;etablissement_id='.$etablissement_id.'&amp;mode=updateetablissement&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/edit.gif" alt="'.get_string('edit').'" title="'.get_string('edit').'" /></a>'."\n";
        $s.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/etablissement.php?d='.$referentiel_id.'&amp;etablissement_id='.$etablissement_id.'&amp;mode=updateetab&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/edit.gif" alt="'.get_string('edit').'" title="'.get_string('edit').'" /></a>'."\n";
	    $s.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/etablissement.php?d='.$referentiel_id.'&amp;etablissement_id='.$etablissement_id.'&amp;mode=deleteetab&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/delete.gif" alt="'.get_string('etablissement_initialiser', 'referentiel').'" title="'.get_string('etablissement_initialiser', 'referentiel').'" /></a>'."\n";
	}
	return $s;
}


/************************************************************************
 * takes a list of records, the current referentiel, a search string,   *
 * and mode to display                                                  *
 * input @param array $records   of etablissement                            *
 *       @param object $referentiel    instance                         *
 *       @param string $search                                          *
 *       @param string $page                                            *
 * output null                                                          *
 ************************************************************************/
function referentiel_print_liste_etablissements($mode, $referentiel) {
global $CFG;

	if ($referentiel){
		
		// contexte
	    $cm = get_coursemodule_from_instance('referentiel', $referentiel->id);
    	$course = get_record('course', 'id', $cm->course);
		if (empty($cm) or empty($course)){
    	    print_error('REFERENTIEL_ERROR 5 :: print_lib_etablissement.php :: You cannot call this script in that way');
		}
		
	    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
	    $records=referentiel_get_etablissements();
		// print_r($records);
		if (!$records){	
			referentiel_genere_etablissement();
			$records=referentiel_get_etablissements();
		}
		if ($records){	
			if ( has_capability('mod/referentiel:managecertif', $context)){
				echo '<table class="certificat"">
<tr><th>'.get_string('idnumber','referentiel').'</th><th>'.get_string('name','referentiel').'</th><th>'.get_string('address','referentiel').'</th><th>'.get_string('logo','referentiel').'</th></tr>'."\n";
				// 
				foreach ($records as $record) {   // afficher la liste 
					// 
					echo referentiel_print_etablissement($record);
					echo '<tr><td colspan="4" align="center">'.referentiel_menu_etablissement($context, $referentiel->id, $record->id).'</td></tr>'."\n";
					
				}
				echo '</table><br />'."\n";
			}
		}
	}
}


?>