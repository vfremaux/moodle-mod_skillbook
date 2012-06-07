<?php  // $Id:  lib.php,v 1.0 2008/04/29 00:00:00 jfruitet Exp $
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
 * Library of functions and constants for module referentiel
 * 
 * @author jfruitet
 * @version $Id: lib.php,v 5.0 2010/03/27 00:00:00 jfruitet Exp $
 * @package referentiel v 5.0 2010/03/27 00:00:00 
 **/
 
// 2010/10/18 : configuration
include_once $CFG->dirroot.'/mod/referentiel/lib_config.php';
include_once $CFG->dirroot.'/mod/referentiel/locallib.php';

/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted referentiel record
 **/
function referentiel_add_instance($form) {
// La premiere creation sans saisie d'un domaine, d'une compétence et d'un item 

	$referentiel_id = 0;
    // temp added for debugging
    // echo "DEBUG : ADD INSTANCE CALLED";
    // DEBUG
	// print_object($form);
    // echo "<br />";
	// exit;
			// saisie de l'instance
	if (isset($form) && !empty($form)){
			$referentiel = new object();
			$referentiel->name = ($form->name_instance);
			$referentiel->description = ($form->description);
			$referentiel->domainlabel = ($form->domainlabel);
			$referentiel->skilllabel = ($form->skilllabel);
			$referentiel->itemlabel = ($form->itemlabel);
		    $referentiel->timecreated = time();
			$referentiel->course = $form->course;
			// configuration
			$referentiel->config = referentiel_form2config($form, 'config');
			$referentiel->printconfig = referentiel_form2config($form, 'printconfig');
			
			$referentiel->referentielid = $referentiel_id;
		    // DEBUG
			// print_object($referentiel);
		    // echo "<br />";
			$newid = insert_record('referentiel', $referentiel);
	}
	return $newid;
}

/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will update an existing instance with new referentiel.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function referentiel_update_instance($form) {
$ok=false;	
// DEBUG
// print_object($form);
// echo "<br />";
	if (isset($form->instance) && ($form->instance>0)){
		// echo "<br /> REFERENTIEL : $form->instance\n";
		$referentiel = new object();
		$referentiel->id = $form->instance;		
		$referentiel->name = ($form->name);
		$referentiel->description = ($form->description);
		$referentiel->domainlabel = ($form->domainlabel);
		$referentiel->skilllabel = ($form->skilllabel);
		$referentiel->itemlabel = ($form->itemlabel);
		$referentiel->timecreated = time();
		$referentiel->course = $form->course;
		$referentiel->config = referentiel_form2config($form,'config');
		$referentiel->printconfig = referentiel_form2config($form,'printconfig');

		// $referentiel->referentielid=$form->referentielid;
		// DEBUG
		// print_object($referentiel);
		// echo "<br />";
		$ok = update_record('referentiel', $referentiel);
	}
	return $ok;
}

/**
 * Given an ID of an instance of this module, 
 * this function will permanently delete the instance 
 * and any activity and certificate that depends on it. 
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function referentiel_delete_instance($id) {
// La suppression de l'instance ne supprime pas le referentiel associé
	$ok=true;
	// verifier existence
    if (! $referentiel = get_record("referentiel", "id", "$id")) {
        return false;
    }
	// suppression des activités associees
	$activites=referentiel_get_activites_instance($id);
	if ($activites){
		foreach ($activites as $activite){
			referentiel_delete_activity_record($activite->id);
		}
	}
	// suppression des taches associees
	$taches=referentiel_get_tasks_instance($id);
	if ($taches){
		foreach ($taches as $tache){
			referentiel_delete_task_record($tache->id);
		}
	}
	// suppression des certificats associes
	$certificats=referentiel_get_certificats($referentiel->referentielid);
	if ($certificats){
		foreach ($certificats as $certificat){
			referentiel_delete_certificate_record($certificat->id);
		}
	}
	// suppression du referentiel
    if (! delete_records("referentiel", "id", "$id")) {
        $ok = $ok && false;
    }
    
	return ($ok);
}

/**
 * Return a small object with summary information about what a 
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
 **/
function referentiel_user_outline($course, $user, $mod, $referentiel) {
    $return= new Object;

	$return->time = $referentiel->timecreated;
    $return->instance = $referentiel->id;
	$return->info = get_string('name_instance','referentiel').' : <i>'.$referentiel->name.'</i>';
	$return->info .= ", ".get_string('description','referentiel').' : <i>'.$referentiel->description.'</i>';
	
	if (isset($referentiel->referentielid) && ($referentiel->referentielid>0)){
		$referentiel_referentiel=referentiel_get_referentiel_referentiel($referentiel->referentielid);
		if ($referentiel_referentiel){
			$return->info .= ", ".get_string('name','referentiel').' : <i>'.$referentiel_referentiel->name.'</i>';		
			$return->info .= ", ".get_string('code','referentiel').' : <i>'.$referentiel_referentiel->code.'</i>';
			if (isset($referentiel_referentiel->local) && ($referentiel_referentiel->local!=0)){
				$return->info .= ", ".get_string('referentiel_global','referentiel').' : <i>' . get_string('no').'</i>';
			}
			else{
				$return->info .= ", ".get_string('referentiel_global','referentiel').' : <i>' . get_string('yes').'</i>';	
			}
		}
	}
    return $return;
}

/**
 * Print a detailed representation of what a user has done with 
 * a given particular instance of this module, for user activity reports.
 *
 * 
 * @todo Finish documenting this function
 **/
function referentiel_user_complete($course, $user, $mod, $referentiel) {
  $return= new Object;
	$return->time = $referentiel->timecreated;
  $return->instance = $referentiel->id;
	$return->info = "<li>".get_string('name_instance','referentiel').' : <i>'.$referentiel->name.'</i>';
	$return->info .="</li><li>".get_string('description','referentiel').' : <i>'.$referentiel->description.'</i>';
	$return->info .="</li><li>".get_string('domainlabel','referentiel').' : <i>'.$referentiel->domainlabel.'</i>';	
	$return->info .="</li><li>".get_string('skilllabel','referentiel').' : <i>'.$referentiel->skilllabel.'</i>';	
	$return->info .="</li><li>".get_string('itemlabel','referentiel').' : <i>'.$referentiel->itemlabel.'</i>';	

	if (isset($referentiel->referentielid) && ($referentiel->referentielid>0)){
		$referentiel_referentiel=referentiel_get_referentiel_referentiel($referentiel->referentielid);
		if ($referentiel_referentiel){
			$return->info .="</li><li>".get_string('name','referentiel').' : <i>'.$referentiel_referentiel->name.'</i>';
			$return->info .="</li><li>".get_string('code','referentiel').' : <i>'.$referentiel_referentiel->code.'</i>';
			$return->info .="</li><li>".get_string('description','referentiel').' : <i>'.$referentiel_referentiel->description.'</i>';
			$return->info .="</li><li>".get_string('url','referentiel').' : <i>'.$referentiel_referentiel->url.'</i>';
			$return->info .="</li><li>".get_string('certificatethreshold','referentiel').' : <i>'.$referentiel_referentiel->certificatethreshold.'</i>';
			$return->info .="</li><li>".get_string('modification','referentiel').' : <i>'.date("Y/m/d",$referentiel_referentiel->timemodified).'</i>';	
			
			if (isset($referentiel_referentiel->local) && ($referentiel_referentiel->local!=0)){
				$return->info .="</li><li>".get_string('referentiel_global','referentiel').' : <i>' . get_string('no')."</i></li>";
			}
			else{
				$return->info .="</li><li>".get_string('referentiel_global','referentiel').' : <i>' . get_string('yes')."</i></li>";	
			}
		}
		$referentiel_certificate=referentiel_get_certificate_user($user->id, $referentiel->referentielid);
		if ($referentiel_certificate){
		/*
 id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  comment text NOT NULL,
  synthese_certificate text
  competences_certificate text NOT NULL,
  decision_jury varchar(80) NOT NULL DEFAULT '',
  date_decision bigint(10) unsigned NOT NULL DEFAULT '0',
  referentielid bigint(10) unsigned NOT NULL DEFAULT '0',
  userid bigint(10) unsigned NOT NULL,
  teacherid bigint(10) unsigned NOT NULL,
  verrou tinyint(1) unsigned NOT NULL,
  valide tinyint(1) unsigned NOT NULL,
  evalua*/	
			$return->info .="</li>\n<li><b>".get_string('certification','referentiel')."</b><ul>\n";
			if ($referentiel_certificate->decision_jury){
				$return->info .="<li>".get_string('certificate_etat','referentiel').' : <i>'.$referentiel_certificate->decision_jury.' ('.date("Y/m/d",$referentiel_certificate->date_decision).")</i></li>";
			}
			
			if ($referentiel_certificate->verrou!=0){
				$bgcolor=' color="#ffaaaa"';
			}
			else{
				$bgcolor=' color="#aaffaa"';
			}
			
			// Pas possible car la fonction ne retourne plus rien
			// $return->info .="<li>".get_string('competences_certificat','referentiel').' :<br />'.referentiel_affiche_certificate_consolide('/',':',$referentiel_certificate->competences_certificat, $referentiel->referentielid, $bgcolor)."</li>";
			// ca c'est ok
      $return->info .="<li>".get_string('competences_certificat','referentiel').' :<br />'.$referentiel_certificate->competences_certificate."</li>";
      $return->info .="<li>".get_string('evaluation','referentiel').' : <i>'.$referentiel_certificate->evaluation."</i></li>";
			$return->info .="</ul></li>";
		}
	}
	
    echo $return->info;
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such 
 * as sending out mail, toggling flags etc ... 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
**/

function referentiel_cron() {
global $CFG;
    if (NOTIFICATION_REFERENTIEL){
      referentiel_cron_activites();
      referentiel_cron_taches();
      referentiel_cron_certificats();
    }
    referentiel_cron_scolarite();    // a ne pas deplacer
    if (!empty($CFG->enableoutcomes) && (REFERENTIEL_OUTCOMES)){
      require_once('grade/cron_outcomes.php');
      referentiel_traitement_notations(); // 
    }
    return true;
}

/**
 * Must return an array of grades for a given instance of this module, 
 * indexed by user.  It also returns a maximum allowed grade.
 * 
 * Example:
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 *
 * @param int $referentielid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 **/
function referentiel_grades($referentielid) {
// A FAIRE
// renvoie le carnet de notes de l'instance à Moodle, afin que la plate-forme l'intègre dans son carnet de notes global 
// pour l'étudiant. Cette fonction DOIT retourner un combiné de deux tableau associatif : 
// { 'grades' => { userId => array of double }, 'maxgrades' => { userId = > array of double }}.
// Le premier tableau renvoie les notes obtenues, le deuxième renvoie les notes maximales 
// (ex : je renvoie pour l'utilisateur 23 les notes 7/20 et 13/15 :
// { 'grades' => { 23 => (7, 13)}, 'maxgrades' => { 23 => (20, 15)}}
// Un module peut donc renvoyer une série de notes pour chaque étudiant. 

   return false;
}


/**
 * This function returns if a scale is being used by one referentiel
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $referentielid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 **/
function referentiel_scale_used ($referentielid, $scaleid) {
    $return = false;

    //$rec = get_record("referentiel","id","$referentielid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}

    return $return;
}


/**
 * Checks if scale is being used by any instance of refrentiel
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any referentiel
 */
function referentiel_scale_used_anywhere($scaleid) {
/*
    if ($scaleid and record_exists('referentiel', 'grade', -$scaleid)) {
        return true;
    } else {
        return false;
    }
*/
    return false;
}

/**
 * Must return an array of user records (all referentiel) who are participants
 * for a given instance of referentiel. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $referentielid ID of an instance of this module
 * @return mixed boolean/array of students
 **/
function referentiel_get_participants($referentielid) {
    return false;
}

?>
