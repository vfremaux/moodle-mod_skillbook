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

// Quelques fonctions regroupées dans lib.php


/**
 * Given a form, 
 * this function will permanently delete the task instance 
 * and any consigne that depends on it. 
 *
 * @param object $form
 * @return boolean Success/Failure
 **/

 $REFERENTIEL_ACCOMPAGEMENT="REF";


// -----------------------
function referentiel_delete_association_user_teacher($referentiel_instance_id, $course_id, $userid, $teacherid){  
global $CFG;
    $records_a=get_record_sql('SELECT id FROM '. $CFG->prefix . 'referentiel_accompagnement 
 WHERE instanceid='.$referentiel_instance_id. ' 
 AND courseid='.$course_id.' 
 AND userid='.$userid.' 
 AND teacherid='.$teacherid);
    
    if ($records_a){
      $ok=true;
      foreach ($records_a as $id){
        if ($id){         
          $ok= $ok && delete_records("referentiel_accompagnement", "id", $id);
        }
      }
      return $ok;
    }
    return false;
}


// -----------------------
function referentiel_set_association_user_teacher($referentiel_instance_id, $course_id, $userid, $teacherid, $type='REF'){
global $CFG;
    $records_a=get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_accompagnement 
 WHERE instanceid='.$referentiel_instance_id. ' 
 AND courseid='.$course_id.' 
 AND userid='.$userid.' 
 AND teacherid='.$teacherid);
   if ($records_a){
      $accompagnement_id=$records_a->id;
      $records_a->coaching=$type;
		  update_record("referentiel_accompagnement", $records_a);
	 }
	 else{
  	 $coaching = new object();
	   $coaching->coaching=$type;
	   $coaching->instanceid=$referentiel_instance_id;
	   $coaching->courseid=$course_id;
     $coaching->userid=$userid;
     $coaching->teacherid=$teacherid;		
	   $accompagnement_id= insert_record("referentiel_accompagnement", $coaching);
  }
  return $accompagnement_id;
   
}
    


// -----------------------
function referentiel_get_all_accompagnements($course_id, $referentiel_instance_id){
global $CFG;
	$t_records=array();
	if (!empty($referentiel_instance_id) && !empty($course_id)){
		$t_records = get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_accompagnement 
 WHERE instanceid='.$referentiel_instance_id. ' 
 AND courseid='.$course_id.' 
 ORDER BY userid ASC, teacherid ASC ');
  }
  return $t_records;
}

// -----------------------
function referentiel_get_accompagnements_teacher($referentiel_instance_id, $course_id, $ref_teacher) {
// retourne la liste des id des accompagnes
// 
global $CFG;
	if (!empty($referentiel_instance_id) && !empty($course_id) && !empty($ref_teacher)){
    return (get_records_sql('SELECT userid FROM '. $CFG->prefix . 'referentiel_accompagnement 
 WHERE instanceid='.$referentiel_instance_id. ' 
 AND courseid='.$course_id.' AND teacherid='.$ref_teacher.' 
 ORDER BY userid ASC '));
  }
  return false;
}

// -----------------------
function referentiel_has_pupils($referentiel_instance_id, $course_id, $ref_teacher) {
// retourne le nombre d'students accompagnés par $ref_techer
// 
	if (!empty($referentiel_instance_id) && !empty($course_id) && !empty($ref_teacher)){
    return count_records( 'referentiel_accompagnement', 'instanceid',$referentiel_instance_id,'courseid',$course_id,'teacherid',$ref_teacher);
  }
  return false;
}


?>