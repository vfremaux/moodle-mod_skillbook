<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

// Exports selected outcomes from referentiel module in CSV format. 

// REPRIS par JF DE grades/edit/outcomes/export.php


require_once('../../../config.php');
require_once('../lib.php');
require_once('../import_export_lib.php');	// IMPORT / EXPORT	

  $id    = optional_param('id', 0, PARAM_INT);    // course module id	
  $d     = optional_param('d', 0, PARAM_INT);    // referentiel base id

  $exportfilename = optional_param('exportfilename','',PARAM_FILE );


	if ($d) {
    if (! $referentiel = get_record('referentiel', 'id', $d)) {
       print_error('Certification instance is incorrect');
    }
    if (! $referentiel_referentiel = get_record('referentiel_referentiel', 'id', $referentiel->referentielid)) {
       print_error('Referentiel id is incorrect');
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
            print_error('Certification instance is incorrect');
        }
        if (! $referentiel_referentiel = get_record('referentiel_referentiel', 'id', $referentiel->referentielid)) {
            print_error('Referentiel is incorrect');
        }
    } 
	else{
    // print_error('You cannot call this script in that way');	
		error(get_string('erreurscript','referentiel','Erreur01 : export_objectifs_grades.php'));
	}
 	
  require_login($course->id, false, $cm);

  if (!isloggedin() or isguest()) {
        redirect($CFG->wwwroot.'/mod/referentiel/view.php?id='.$cm->id.'&amp;noredirect=1');
  }


  // check role capability
  $context = get_context_instance(CONTEXT_MODULE, $cm->id);
  require_capability('mod/referentiel:export', $context);
  /*
  $context = get_context_instance(CONTEXT_COURSE, $course->id);
  // a remplacer par capacite sur le r�f�rentiel ?
  require_capability('moodle/grade:manage', $context);
  */
  
  if (empty($CFG->enableoutcomes)) {
        redirect($CFG->wwwroot.'/mod/referentiel/view.php?id='.$cm->id.'&amp;noredirect=1');
  }

  /*
  if (!confirm_sesskey()) {
      break;
  }
  */
  // ensure the files area exists for this course
  // Inutile car pas de sauvegarde dans les donn�es du cours.
  // make_upload_directory( "$course->id/$CFG->moddata/referentiel" );
  
  if (empty($exportfilename)) {
      $exportfilename = "outcomes_".referentiel_default_export_filename($course, $referentiel).'.csv';
  }
  
  $systemcontext = get_context_instance(CONTEXT_SYSTEM);

  header("Content-Type: text/csv; charset=utf-8"); 
  header("Content-Disposition: attachment; filename=$exportfilename");

  // sending header with clear names, to make 'what is what' as easy as possible to understand
  $header = array('outcome_name', 'outcome_shortname', 'outcome_description', 'scale_name', 'scale_items', 'scale_description');
  echo format_csv($header, ';', '"');

  
  $outcomes = array();
  $outcomes = referentiel_get_outcomes($referentiel_referentiel);
  /*
outcome_name;outcome_shortname;outcome_description;scale_name;scale_items;scale_description;
C2i2e A.1.1;A.1.1;A.1.1 : Identifier les personnes ressources TIC et leurs rôles respectifs dans l'école ou l'établissement, et en dehors (circonscription, bassin, Académie, niveau national...) ;Item référentiel;Non acquis,En cours d'acquisition,Acquis;Ce barème est destiné à évaluer (noter) les items de compétences du module référentiel. 
C2i2e A.1.2 	A.1.2 	A.1.2 S'approprier les différentes composantes informatiques (lieux, outils...) de son environnement professionnel 	Item référentiel	Non acquis,En cours d'acquisition,Acquis	Ce barème est destiné à évaluer (noter) les items de compétences du module référentiel.   
  */
  
foreach($outcomes as $outcome) {

    $line = array();

    $line[] = $outcome->name;
    $line[] = $outcome->shortname;
    $line[] = $outcome->description;
    $line[] = get_string('nom_bareme','referentiel');
    $line[] = get_string('bareme','referentiel');
    $line[] = get_string('description_bareme','referentiel');
    
    echo format_csv($line, ';', '"');
}


/**
 * Formats and returns a line of data, in CSV format. This code
 * is from http://au2.php.net/manual/en/function.fputcsv.php#77866
 *
 * @params array-of-string $fields data to be exported
 * @params char $delimiter char to be used to separate fields
 * @params char $enclosure char used to enclose strings that contains newlines, spaces, tabs or the delimiter char itself
 * @returns string one line of csv data
 */
function format_csv($fields = array(), $delimiter = ';', $enclosure = '"') {
    $str = '';
    $escape_char = '\\';
    foreach ($fields as $value) {
        if (strpos($value, $delimiter) !== false ||
                strpos($value, $enclosure) !== false ||
                strpos($value, "\n") !== false ||
                strpos($value, "\r") !== false ||
                strpos($value, "\t") !== false ||
                strpos($value, ' ') !== false) {
            $str2 = $enclosure;
            $escaped = 0;
            $len = strlen($value);
            for ($i=0;$i<$len;$i++) {
                if ($value[$i] == $escape_char) {
                    $escaped = 1;
                } else if (!$escaped && $value[$i] == $enclosure) {
                    $str2 .= $enclosure;
                } else {
                    $escaped = 0;
                }
                $str2 .= $value[$i];
            }
            $str2 .= $enclosure;
            $str .= $str2.$delimiter;
        } else {
            $str .= $value.$delimiter;
        }
    }
    $str = substr($str,0,-1);
    $str .= "\n";

    return $str;
}

/**
 * Gets Positiry items and returns an array of outcomes
 * @params referentiel_referentiel record
 * @returns array of outcome objects
 */

function referentiel_get_outcomes($referentiel_referentiel){
// genere les outcomes (objectifs) pour le module grades (notes) � partir des items du r�f�rentiel
  $outcomes=array();
	if ($referentiel_referentiel){
		$code = stripslashes($referentiel_referentiel->code);

		// charger les domaines associes au referentiel courant
		if (isset($referentiel_referentiel->id) && ($referentiel_referentiel->id>0)){
			// AFFICHER LA LISTE DES DOMAINES
			$compteur_domaine=0;
			$records_domaine = referentiel_get_domaines($referentiel_referentiel->id);
	    if ($records_domaine){
    			// afficher
				// DEBUG
				// echo "<br/>DEBUG ::<br />\n";
				// print_r($records_domaine);
				foreach ($records_domaine as $record){
					$compteur_domaine++;
        	$domaine_id=$record->id;
					$nb_competences = $record->nb_competences;
					$code = stripslashes($record->code);
					$description = stripslashes($record->description);
					$sortorder = $record->sortorder;

					// LISTE DES COMPETENCES DE CE DOMAINE
					$compteur_competence=0;
					$records_competences = referentiel_get_competences($domaine_id);
			    if ($records_competences){
						// DEBUG
						// echo "<br/>DEBUG :: COMPETENCES <br />\n";
						// print_r($records_competences);
						foreach ($records_competences as $record_c){
							$compteur_competence++;
        			$competence_id=$record_c->id;
							$nb_item_competences = $record_c->nb_item_competences;
							$code = stripslashes($record_c->code);
							$description = stripslashes($record_c->description);
							$sortorder = $record_c->sortorder;
							$domainid = $record_c->domainid;

							// ITEM
							$compteur_item=0;
							$records_items = referentiel_get_item_competences($competence_id);
							
						  if ($records_items){
								// DEBUG
								// echo "<br/>DEBUG :: ITEMS <br />\n";
								// print_r($records_items);

								
								foreach ($records_items as $record_i){
									$compteur_item++;
	    		    		$item_id=$record_i->id;
									$code = stripslashes($record_i->code);
									$description = stripslashes($record_i->description);
									$sortorder = $record_i->sortorder;
									$type = stripslashes($record_i->type);
									$weight = $record_i->weight;	
									$footprint = $record_i->footprint;	
									$skillid=$record_i->skillid;
									 
                  $outcome= new object();
                  $outcome->name=$code.' '.$code;
                  $outcome->shortname=$code;
                  $outcome->description=$description;
    
                  $outcomes[]=$outcome;

								}
							}
						}
					}
				}
			}
		}
	}
	return $outcomes; 
}

 
    
