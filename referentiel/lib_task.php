<?php

// $Id:  lib.php,v 1.0 2008/04/29 00:00:00 jfruitet Exp $
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
 * @version $Id: lib.php,v 1.4 2006/08/28 16:41:20 mark-nielsen Exp $
 * @version $Id: lib.php,v 1.0 2008/04/29 00:00:00 jfruitet Exp $
 * @package referentiel
 **/
/*
DROP TABLE IF EXISTS prefix_referentiel_task`;
CREATE TABLE IF NOT EXISTS `mdl_referentiel_task` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(80) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `competences_task` text NOT NULL,
  `criteria` text NOT NULL,
  `instanceid` bigint(10) unsigned NOT NULL DEFAULT '0',
  `referentielid` bigint(10) unsigned NOT NULL DEFAULT '0',
  `course` bigint(10) unsigned NOT NULL DEFAULT '0',
  `auteurid` bigint(10) unsigned NOT NULL,
  `timecreated` bigint(10) unsigned NOT NULL DEFAULT '0',
  `timemodified` bigint(10) unsigned NOT NULL DEFAULT '0',
  `timestart` bigint(10) unsigned NOT NULL DEFAULT '0',
  `timeend` bigint(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='task' AUTO_INCREMENT=1 ;

--
-- Structure de la table `mdl_referentiel_consigne`
--

DROP TABLE IF EXISTS `mdl_referentiel_consigne`;
CREATE TABLE IF NOT EXISTS `mdl_referentiel_consigne` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(20) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT '',
  `taskid` bigint(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='consigne' AUTO_INCREMENT=1 ;

--
-- Structure de la table `mdl_referentiel_a_user_task`
--

DROP TABLE IF EXISTS `mdl_referentiel_a_user_task`;
CREATE TABLE IF NOT EXISTS `mdl_referentiel_a_user_task` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(10) unsigned NOT NULL,
  `taskid` bigint(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='user_select_task' AUTO_INCREMENT=1 ;

*/

// Quelques fonctions regroupées dans lib.php



/**
 * Given an task id,
 * this function will permanently delete the task instance
 * and any consigne that depends on it.
 *
 * @param object $id
 * @return boolean Success/Failure
 **/

 // -----------------------
function referentiel_delete_task_and_activities($id) {
// suppression task + consignes associes + liens vers activite et user + activites dues a la tache
$ok_task=false;
	if (isset($id) && ($id>0)){
		if ($task = get_record("referentiel_task", "id", $id)) {
	   		// Delete any dependent records here
			if ($r_a_users_tasks = get_records("referentiel_a_user_task", "taskid", $id)) {
				// DEBUG
				// echo "<br />            \n";
				// print_object($r_a_users_tasks);
				// echo "<br />suppression des activites\n";
				foreach ($r_a_users_tasks as $r_a_user_task){
					// suppression de l'activite
					referentiel_delete_activity_record($r_a_user_task->activityid);
				}
			}
            $ok_task=referentiel_delete_task_record($id);
		}
	}
    return $ok_task;
}

// -----------------------
function referentiel_task_isowner($id){
global $USER;
	if (isset($id) && ($id>0)){
		$record=get_record("referentiel_task", "id", "$id");
		// DEBUG
		// echo "<br >USERID : $USER->id ; OWNER : $record->userid\n";
		return ($USER->id == $record->auteurid);
	}
	else {
		return false;
	} 
} 



/**
 * Given a form, 
 * this function will permanently delete the task instance 
 * and any consigne that depends on it. 
 *
 * @param object $form
 * @return boolean Success/Failure
 **/

 // -----------------------
function referentiel_delete_task($form) {
// suppression task + consigne
$ok_task=false;
$ok_consigne=false;
    // DEBUG
	// echo "<br />";
	// print_object($form);
    // echo "<br />";
	if (isset($form->action) && ($form->action=="modifier_task")){
		// suppression d'une task et des consignes associes
		if (isset($form->task_id) && ($form->task_id>0)){
			$ok_task=referentiel_delete_task_record($form->task_id);
			// mise a zero du certificate associe a cette personne pour ce referentiel 
			// referentiel_certificate_user_invalider($form->userid, $form->referentielid);
			// referentiel_regenere_certificate_user($form->userid, $form->referentielid);
		}
	}
	else if (isset($form->action) && ($form->action=="modifier_consigne")){
		// suppression d'un consigne
		if (isset($form->consigne_id) && ($form->consigne_id>0)){
			$ok_consigne=referentiel_delete_consigne_record($form->consigne_id);
		}
	}
	
    return $ok_task or $ok_consigne;
}


/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted referentiel record
 **/
 /*
CREATE TABLE IF NOT EXISTS `mdl_referentiel_task` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(80) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `competences_task` text NOT NULL,
  `criteria` text NOT NULL,
  `instanceid` bigint(10) unsigned NOT NULL DEFAULT '0',
  `referentielid` bigint(10) unsigned NOT NULL DEFAULT '0',
  `course` bigint(10) unsigned NOT NULL DEFAULT '0',
  `auteurid` bigint(10) unsigned NOT NULL,
  `timecreated` bigint(10) unsigned NOT NULL DEFAULT '0',
  `timemodified` bigint(10) unsigned NOT NULL DEFAULT '0',
  `timestart` bigint(10) unsigned NOT NULL DEFAULT '0',
  `timeend` bigint(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='task' AUTO_INCREMENT=1 ;
 */

 // -----------------------
function referentiel_add_task($form) {
// creation task + consigne
global $USER;
    // DEBUG
    // echo "DEBUG : ADD task CALLED : lib.php : ligne 1033";
	// print_object($form);
    // echo "<br />";
	// referentiel
	$task = new object();
	$task->type=($form->type);
	$task->description=($form->description);
	if (isset($form->code)){
  	$task->competences_task = reference_conversion_code_2_liste_competence('/', $form->code);
	}
	else{
    $task->competences_task='';
  }
  $task->criteria = ($form->criteria);
	$task->instanceid = $form->instance;
	$task->referentielid = $form->referentielid;
	$task->course = $form->course;
	$task->auteurid = $USER->id;		
	$task->timecreated = time();
	$task->timemodified = time();
  $task->cle_souscription = ($form->cle_souscription);
  $task->souscription_libre = ($form->souscription_libre);
  if (isset($form->hidden)) 
    $task->hidden=$form->hidden;
  else 
    $task->hidden=0; 

    $task->mailed=1;  // MODIF JF 2010/10/05
    if (isset($form->mailnow)){
        $task->mailnow=$form->mailnow;
        if ($form->mailnow=='1'){ // renvoyer
            $task->mailed=0;   // annuler envoi precedent
        }
    }
    else{
      $task->mailnow=0;
    }

	// DEBUG
	// echo "<br>DEBUG :: lib_task.php :: 252 :: DATE DEBUT: ".$form->timestart."\n";
	// echo "<br>DEBUG :: lib_task.php :: 252 :: DATE FIN: ".$form->timeend."\n";
	list($date,$heure)=explode(' ',$form->timestart);
	list($h,$i)=explode(':',$heure);
	if (!$h) $h=0;
	if (!$i) $i=0;
	list($d,$m,$y)=explode('/',$date);
	$task->timestart=mktime($h,$i,0,$m,$d,$y);
	// echo "<br>DEBUG :: lib_task.php :: 27 :: $d,$m,$y $h,$i--- ".$task->timestart."\n";

	list($date,$heure)=explode(' ',$form->timeend);
	list($h,$i)=explode(':',$heure);
	if (!$h) $h=0;
	if (!$i) $i=0;
	list($d,$m,$y)=explode('/',$date);
	$task->timeend=mktime($h,$i,0,$m,$d,$y);
	//echo "<br>DEBUG :: lib_task.php :: 342 :: $d,$m,$y $h,$i--- ".$task->timeend."\n";	
		
	    // DEBUG
		// print_object($task);
	    // echo "<br />";
	
	$task_id= insert_record("referentiel_task", $task);
		
    // echo "task ID / $task_id<br />";
	if 	(isset($task_id) && ($task_id>0)
			&& 
			(	(isset($form->url) && !empty($form->url))
				|| 
				(isset($form->description) && !empty($form->description))
			)
	){
	/*
	DROP TABLE IF EXISTS `mdl_referentiel_consigne`;
CREATE TABLE IF NOT EXISTS `mdl_referentiel_consigne` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(20) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT '',
  `taskid` bigint(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='consigne' AUTO_INCREMENT=1 ;
	*/
		$consigne = new object();
		$consigne->url=$form->url;
		$consigne->type=$form->type;
		$consigne->description=$form->description;
		$consigne->taskid=$task_id;
		if (isset($form->target)){
			$consigne->target=$form->target;
   		}
		else{
			$consigne->target=1;
		}
		if (isset($form->label)){
			$consigne->label=$form->label;
   		}
		else{
			$consigne->label='';
		}

	   	// DEBUG
		// print_object($consigne);
    	// echo "<br />";
		
		$consigne_id = insert_record("referentiel_consigne", $consigne);
    	// echo "consigne ID / $consigne_id<br />";
	}
    return $task_id;
}


// -----------------------
function referentiel_mask_task($id, $masque){
  if ($id){
    $record=get_record("referentiel_task", "id", $id);
    if ($record){
      if ($masque){
        $record->hidden=1;    
      }
      else{
        $record->hidden=0;
      }
      $record->type=addslashes($record->type);
      $record->description=addslashes($record->description);
      $record->criteria=addslashes($record->criteria);
      return update_record("referentiel_task", $record);
    }
  }
  return false; 
}

// -----------------------
function referentiel_update_task($form) {
// MAJ task + consigne;
// 19/01/2010 : la reference de l'auteur n'est pas actualisée.
global $USER;
$ok=true;
    // DEBUG
	// echo "<br />UPDATE task<br />\n";
	// print_object($form);
    // echo "<br />";
	
	if (isset($form->action) && ($form->action=="modifier_task")){
		// task
		$task = new object();
		$task->id=($form->task_id);
		$task->type=($form->type);
		$task->description=($form->description);
		$task->competences_task=reference_conversion_code_2_liste_competence('/', $form->code);
		$task->criteria=($form->criteria);
		$task->instanceid=$form->instance;
		$task->referentielid=$form->referentielid;
		$task->course=$form->course;
		if (empty($form->auteurid)){
            $task->auteurid=$USER->id;
        }
		$task->timecreated=$form->timecreated;
		$task->timemodified=time();
        $task->cle_souscription=($form->cle_souscription);
        $task->souscription_libre=$form->souscription_libre;
		if (isset($form->hidden))
            $task->hidden=$form->hidden;
        else
            $task->hidden=0;

        // MODIF JF 2010/02/11
        if (isset($form->mailnow)){
            $task->mailnow=$form->mailnow;
            if ($form->mailnow=='1'){ // renvoyer
                $task->mailed=0;   // annuler envoi precedent
            }
        }
        else{
            $task->mailnow=0;
        }

        /*
        $task->timestart=mktime($form->debut_heure, $form->debut_mois, $form->debut_jour, $form->debut_annee);
        $task->timeend=mktime($form->fin_heure, $form->fin_mois, $form->fin_jour, $form->fin_annee);
        */
        // DEBUG
        // echo "<br>DEBUG :: lib_task.php :: 252 :: DATE DEBUT: ".$form->timestart."\n";
        // echo "<br>DEBUG :: lib_task.php :: 252 :: DATE FIN: ".$form->timeend."\n";
        list($date,$heure)=explode(' ',$form->timestart);
        list($h,$i)=explode(':',$heure);
        if (!$h) $h=0;
        if (!$i) $i=0;
        list($d,$m,$y)=explode('/',$date);
        $task->timestart=mktime($h,$i,0,$m,$d,$y);
        // echo "<br>DEBUG :: lib_task.php :: 27 :: $d,$m,$y $h,$i--- ".$task->timestart."\n";

        list($date,$heure)=explode(' ',$form->timeend);
        list($h,$i)=explode(':',$heure);
        if (!$h) $h=0;
        if (!$i) $i=0;
        list($d,$m,$y)=explode('/',$date);
        $task->timeend=mktime($h,$i,0,$m,$d,$y);
        //echo "<br>DEBUG :: lib_task.php :: 342 :: $d,$m,$y $h,$i--- ".$task->timeend."\n";
		
	    // DEBUG
		// print_object($task);
	    // echo "<br />";
		$ok = $ok && update_record("referentiel_task", $task);
		// exit;
	    // echo "DEBUG :: lib_task.php :: 350 :: task ID : $task->id<br />";
	}
	else if (isset($form->action) && ($form->action=="modifier_consigne")){
		$consigne = new object();
		$consigne->id=$form->consigne_id;
		$consigne->url=($form->url);
		$consigne->type=($form->type);
		$consigne->description=($form->description);
		$consigne->taskid=$form->taskid;
		if (isset($form->target)){
			$consigne->target=$form->target;
   		}
		else{
			$consigne->target=1;
		}
		if (isset($form->label)){
			$consigne->label=$form->label;
   		}
		else{
			$consigne->label='';
		}
		
   		// DEBUG
		// print_object($consigne);
    	// echo "<br />";
		$ok= $ok && update_record("referentiel_consigne", $consigne);
		// exit;
	}
	else if (isset($form->action) && ($form->action=="creer_consigne")){
		$consigne = new object();
		$consigne->url=($form->url);
		$consigne->type=($form->type);
		$consigne->description=($form->description);
		$consigne->taskid=$form->taskid;
		if (isset($form->target)){
			$consigne->target=$form->target;
   		}
		else{
			$consigne->target=1;
		}
		if (isset($form->label)){
			$consigne->label=$form->label;
   		}
		else{
			$consigne->label='';
		}
		
   		// DEBUG
		// print_object($consigne);
    	// echo "<br />";
		$ok = insert_record("referentiel_consigne", $consigne);
    	// echo "consigne ID / $ok<br />";
		// exit;
	}
    return $ok;
}

/**
 * Given a course and a time, this module should find recent task 
 * that has occurred in referentiel and print it out. 
 * Return true if there was output, or false is there was none. 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish this function
 **/
 // -----------------------
function referentiel_print_recent_task($course, $isteacher, $timestart) {
    global $CFG;

    return false;  //  True if anything was printed, otherwise false 
}

// -----------------------
function referentiel_update_consigne($form) {
// MAJ consigne;
    // DEBUG
	// echo "<br />UPDATE ACTIVITY<br />\n";
	// print_object($form);
    // echo "<br />";
	if (isset($form->consigne_id) && $form->consigne_id
		&&
		isset($form->taskid) && $form->taskid){
		$consigne = new object();
		$consigne->id=$form->consigne_id;
		$consigne->url=($form->url);
		$consigne->type=($form->type);
		$consigne->description=($form->description);
		$consigne->taskid=$form->taskid;
		if (isset($form->target)){
			$consigne->target=$form->target;
   		}
		else{
			$consigne->target=1;
		}
		if (isset($form->label)){
			$consigne->label=$form->label;
   		}
		else{
			$consigne->label='';
		}
		
   		// DEBUG
		// print_object($consigne);
    	// echo "<br />";
		return update_record("referentiel_consigne", $consigne);
	}
	return false;
}

// -----------------------
function referentiel_add_consigne($form) {
// MAJ consigne;
	$id_consigne=0;
	if (isset($form->taskid)){
		$consigne = new object();
		$consigne->url=$form->url;
		$consigne->type=$form->type;
		$consigne->description=$form->description;
		$consigne->taskid=$form->taskid;
		if (isset($form->target)){
			$consigne->target=$form->target;
   		}
		else{
			$consigne->target=1;
		}
		if (isset($form->label)){
			$consigne->label=$form->label;
   		}
		else{
			$consigne->label='';
		}
		
   		// DEBUG
		// print_object($consigne);
    	// echo "<br />";
		$id_consigne = insert_record("referentiel_consigne", $consigne);
    	// echo "consigne ID / $ok<br />";
		// exit;
	}
    return $id_consigne;
}

// -----------------------
function referentiel_delete_all_associations_users_to_one_task($taskid) {
// supprime toutes les associations pour une tache donnee
	$ok_association=true;
	if (isset($taskid) && ($taskid>O)){
		$a_records = get_records("referentiel_a_user_task", "taskid", $taskid);
		if ($a_records){
			foreach ($a_records as $a_record){
				// suppression
				$ok_association =$ok_association && referentiel_delete_a_user_task_record($a_record->id);
			}
		}
	}
    return $ok_association;
}

// -----------------------
function referentiel_delete_all_associations_tasks_to_one_user($userid) {
// supprime toutes les associations pour un user donne
/*
			DROP TABLE IF EXISTS `mdl_referentiel_a_user_task`;
CREATE TABLE IF NOT EXISTS `mdl_referentiel_a_user_task` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(10) unsigned NOT NULL,
  `taskid` bigint(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='user_select_task' AUTO_INCREMENT=1 ;
*/
	$ok_association=true;
	if (isset($userid) && ($userid>O)){
		$a_records = get_records("referentiel_a_user_task", "userid", $userid);
		if ($a_records){
			foreach ($a_records as $a_record){
				// suppression
				$ok_association =$ok_association && referentiel_delete_a_user_task_record($a_record->id);
			}
		}
	}
    return $ok_association;
}

// -----------------------
function referentiel_get_all_tasks_user($userid, $course_id, $referentiel_instance_id) {
// retourne un tableau d'objets taches pour un user donne
/*
			DROP TABLE IF EXISTS `mdl_referentiel_a_user_task`;
CREATE TABLE IF NOT EXISTS `mdl_referentiel_a_user_task` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(10) unsigned NOT NULL,
  `taskid` bigint(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='user_select_task' AUTO_INCREMENT=1 ;
*/
global $CFG;
	$t_records=array();
	if (isset($referentiel_instance_id) && ($referentiel_instance_id>0)
		&& isset($course_id) && ($course_id>0)){
		if (isset($userid) && ($userid>0)){
			$a_records = get_records("referentiel_a_user_task", "userid", $userid);
			if ($a_records){
				foreach ($a_records as $a_record){
					$t_records[] = get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_task 
 WHERE instanceid='.$referentiel_instance_id. ' 
 AND course='.$course_id.' AND id='.$a_record->taskid.'  
 ORDER BY timestart DESC, timeend DESC, auteurid ASC ');
 				}
			}
		}
	}
    return $t_records;
}

// -----------------------
function referentiel_user_tache_souscrite($userid, $taskid) {
// retourne vrai si cet utilisateur a souscrit à cette tache
		if ($userid && $taskid){
			$a_record = get_record("referentiel_a_user_task", "userid", $userid, "taskid", $taskid);
			if ($a_record){
				return true;
			}
		}
    return false;
}

// -----------------------
function referentiel_get_all_tasks($course_id, $referentiel_instance_id){
global $CFG;
	$t_records=array();
	if (isset($referentiel_instance_id) && ($referentiel_instance_id>0)
		&& isset($course_id) && ($course_id>0)){
		$t_records = get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_task 
 WHERE instanceid='.$referentiel_instance_id. ' 
 AND course='.$course_id.' 
 ORDER BY timestart DESC, timeend DESC, auteurid ASC ');
	}
    return $t_records;
}

// -----------------------
function referentiel_get_task($task_id, $auteur_id=0){
global $CFG;
	if (isset($task_id) && ($task_id>0)){
		if ($auteur_id>0){
			$t_record = get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_task WHERE id='.$task_id.' AND auteurid='.$auteur_id);		
		}
		else {
			$t_record = get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_task WHERE id='.$task_id);
		}
	}
    return $t_record;
}

// -----------------------
function referentiel_closed_task($task_id){
global $CFG;
	if (isset($task_id) && ($task_id>0)){
		if ($t_record = get_record_sql('SELECT timeend FROM '. $CFG->prefix . 'referentiel_task WHERE id='.$task_id)){
			return ($t_record->timeend<time());
		}
	}
    return false;
}



/**
 * This function returns record document from table referentiel_document
 *
 * @param id taskid
 * @return objects
 * @todo Finish documenting this function
 **/
 // -----------------------
function referentiel_get_consignes($task_id){
global $CFG;
	if (isset($task_id) && ($task_id>0)){
		return get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_consigne WHERE taskid='.$task_id.' ORDER BY id ASC ');
	}
	else 
		return 0; 
}

// -----------------------
function referentiel_association_user_task($userid, $taskid, $referent_id=0) {
// associe une tache à un utilisateur
//  cree l'activite a partir de l'association
global $CFG;
global $USER;
	// DEBUG
	// echo '<br />DEBUG :: lib_task.php :: 559 :: User : '.$userid.' Tache : '.$taskid."\n";

	$activite_id=0;
	if ($taskid && $userid){
		// verifier si association existe
		$sql1='SELECT * FROM '. $CFG->prefix . 'referentiel_a_user_task  WHERE userid='.$userid. ' AND taskid='.$taskid;
		// echo '<br />DEBUG :: lib_task.php :: 565 :: SQL: '.$sql1."\n";
		$record_association= get_record_sql($sql1);
 		
		if (!$record_association){
			// inexistant
			// Recuperer les info de la tache
			$t_record = get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_task WHERE id='.$taskid);
			if ($t_record){
				// Creer l'activite
		 	   	// DEBUG
			    // echo "DEBUG : ADD ACTIVITY CALLED : lib_task.php : ligne 578";
				$activite = new object();
				$activite->type_activite='['.get_string('task','referentiel').' '.$taskid.'] '.addslashes($t_record->type);
				$activite->comptencies=addslashes($t_record->competences_task);
				$activite->description='['.get_string('consigne_task','referentiel').' (<i>'.referentiel_get_user_info($t_record->auteurid).'</i>) : '.addslashes($t_record->description).']';
				$activite->comment=addslashes($t_record->criteria);
				$activite->instanceid=$t_record->instanceid;
				$activite->referentielid=$t_record->referentielid;
				$activite->course=$t_record->course;
				$activite->timecreated=time();
				$activite->timemodified=time();
				$activite->approved=0;
				$activite->userid=$userid;
				if (empty($referent_id)){
          $activite->teacherid=$t_record->auteurid;
				}
        else{
          $activite->teacherid=$referent_id;
        }
        $activite->taskid=$taskid;
				
    			// DEBUG
    			// echo "<br />DEBUG :: lib_task.php : 592 : APRES CREATION\n";	
				// print_object($activite);
    			// echo "<br />";
				$activite_id= insert_record("referentiel_activity", $activite);
				if ($activite_id){
					// echo "Activite ID : $activite_id<br />";
					// mise a zero du certificate associe a cette personne pour ce referentiel 
					referentiel_certificate_user_invalider($activite->userid, $activite->referentielid);
					referentiel_regenere_certificate_user($activite->userid, $activite->referentielid);
					$record_association = new object();
					$record_association->userid=$userid;
					$record_association->taskid=$taskid;
					$record_association->activityid=$activite_id;
					$record_association->date_selection=time();
   					// DEBUG
					//print_object($record_association);
    				//echo "<br />";
					$id_a = insert_record("referentiel_a_user_task", $record_association);
    				//echo "association ID : $id_a<br />";
					//exit;
				}
			}
		}
	}
  return $activite_id;
}

// -----------------------
function referentiel_validation_activite_task($taskid, $select='') {
// Effectue la validation des activités souscrites a la tache
global $CFG;
global $USER;
	// DEBUG
	// echo '<br />DEBUG :: lib_task.php :: 669 :: Tache : '.$taskid."<br />Selection :".$select."\n";
	if ($taskid>0){
		$info_valideur=referentiel_get_user_info($USER->id);
		// verifier si association existe
		$sql1='SELECT * FROM '. $CFG->prefix . 'referentiel_a_user_task  WHERE taskid='.$taskid;
		if (!empty($select)){
          $sql1.=' '.$select.' ';
    }
		// echo '<br />DEBUG :: lib_task.php :: 677 :: SQL: '.$sql1."\n";
		$records_association= get_records_sql($sql1);
 		
		if ($records_association){
			foreach ($records_association as $record_association){
 				if ($record_association){
					$userid=$record_association->userid;
					$activityid=$record_association->activityid;
   					// DEBUG
					//print_object($record_association);
    				//echo "<br />";
					// Approuver l'activite
					// recuperer l'info sur l'activite
			    if ($approverecord = get_record('referentiel_activity', 'id', $activityid)) {
						$approverecord->approved = 1;
						$approverecord->teacherid=$USER->id;
						$approverecord->timemodified=time();
						$approverecord->type_activite=addslashes($approverecord->type_activite);
						$approverecord->description=addslashes($approverecord->description);
						$approverecord->comment=addslashes($approverecord->comment."\n".get_string('approved_task_by','referentiel')." ".$info_valideur." (".date("d/m/Y H:i").")\n");
						// DEBUG
						// print_r($approverecord);
						// echo "<br />\n";
						
			      if (update_record('referentiel_activity', $approverecord)) {
							// regeneration du certificate associe a cette personne pour ce referentiel 
							referentiel_certificate_user_invalider($approverecord->userid, $approverecord->referentielid);
							referentiel_regenere_certificate_user($approverecord->userid, $approverecord->referentielid);
            }
				  }
        }
		  }
	 }
  }
}

// -----------------------
function referentiel_get_activites_task($taskid) {
// Retourne la liste des activités liées à une tache
global $CFG;
	// DEBUG
	// echo '<br />DEBUG :: lib_task.php :: 685 :: Tache : '.$taskid."\n";
	if ($taskid>0){
		// verifier si association existe
		$sql='SELECT * FROM '. $CFG->prefix . 'referentiel_activity  WHERE taskid='.$taskid;
		// echo '<br />DEBUG :: lib_task.php :: 736 :: SQL: '.$sql1."\n";
		return get_records_sql($sql);
 	}
	return false;
}


// -----------------------
function referentiel_get_liste_codes_competence_tache($taskid) {
global $CFG;
	// DEBUG
	// echo '<br />DEBUG :: lib_task.php :: 652 :: Tache : '.$taskid."\n";
	if ($taskid>0){
		// verifier si association existe
		$sql='SELECT competences_task FROM '. $CFG->prefix . 'referentiel_task  WHERE id='.$taskid;
		// echo '<br />DEBUG :: lib_task.php :: 656 :: SQL: '.$sql."\n";
		$rtask=get_record_sql($sql);
		if ($rtask){
			return $rtask->competences_task;
		}
 	}
	return '';
}

?>