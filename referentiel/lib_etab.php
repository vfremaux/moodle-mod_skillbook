<?php // $Id:  lib_etab.php,v 1.0 2008/04/29 00:00:00 jfruitet Exp $
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

 // ////////////////////////// student /////////////////////


 // ---------------------------------------------
function referentiel_add_student_user($userid){
// retourne l'id cree
	$record=new object();
	
	$record->ddn_student = addslashes(get_string('inconnu', 'referentiel'));
	$record->lieu_naissance = addslashes(get_string('inconnu', 'referentiel'));
	$record->departement_naissance = addslashes(get_string('inconnu', 'referentiel'));
	$record->adresse_student = addslashes(get_string('inconnu', 'referentiel'));
	$record->ref_etablissement = referentiel_get_min_etablissement();
  if ($userid>0){
    $record->userid=$userid;
    $user=get_record('user','id',$userid);
    if ($user){
      if (!empty($user->idnumber)){
        $record->num_student = $user->idnumber;
      }
      else{
        $record->num_student = $user->username;      
      }
    }
    else{
      $record->userid=0;
      // $record->num_student = addslashes(get_string('inconnu', 'referentiel'));
      $record->num_student = '';
    }
  }
    
	// DEBUG
	// echo "<br>DEBUG :: lib_etab.php :: 145\n";
	// print_r($record);
	return (insert_record("referentiel_student", $record));
}

 // ---------------------------------------------
function referentiel_student_isowner($id){
global $USER;
	if (isset($id) && ($id>0)){
		$record=get_record("referentiel_student", "userid", "$id");
		// DEBUG
		// echo "<br >USERID : $USER->id ; OWNER : $record->userid\n";
		return ($USER->id == $record->userid);
	}
	else 
		return false; 
} 

function referentiel_get_student_id_by_userid($userid){
	if (isset($userid) && ($userid>0)){
		$record=get_record("referentiel_student", "userid", $userid);
		if ($record){
			return ($record->id);
		}
	}
	return 0;
}


/**
 * This function returns record from table referentiel_student
 *
 * @param id
 * @return object
 * @todo Finish documenting this function
 **/
function referentiel_get_student_user($userid){
global $CFG;
	if (isset($userid) && ($userid>0)){
		return get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_student WHERE userid='.$userid.' ');
	}
	else 
		return 0; 
}

/**
 * This function returns record from table referentiel_student
 *
 * @param id
 * @return object
 * @todo Finish documenting this function
 **/
function referentiel_get_student($id){
global $CFG;
	if (isset($id) && ($id>0)){
		return get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_student WHERE id='.$id.' ');
	}
	else 
		return 0; 
}

/**
 * This function returns record from table referentiel_student
 *
 * @param id
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_students($search=""){
global $CFG;
	return get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_student '.$search.' '); 
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
function referentiel_add_student($form) {
// creation certificat
global $USER;
$id=0;
	$record=new object();
	$record->num_student = $form->num_student;
	$record->ddn_student = $form->ddn_student ;
	$record->lieu_naissance = ($form->lieu_naissance);
	$record->departement_naissance = ($form->departement_naissance);
	$record->adresse_student = ($form->adresse_student);
	if ($form->ref_etablissement){
		$record->ref_etablissement = $form->ref_etablissement;
	}
	else{
		$record->ref_etablissement = referentiel_get_min_etablissement();
	}
	$record->userid = $form->userid;
	
	// controle
	if (
    ($record->userid>0) 
     && 
    (($record->num_student=='') || ($record->num_student==get_string('inconnu', 'referentiel')))
    )
    {
      $user=get_record('user','id',$record->userid);
      if ($user){
        if (!empty($user->idnumber)){
          $record->num_student = $user->idnumber;
        }
        else{
          $record->num_student = $user->username;      
        }
    }
  }
  
	$id=insert_record("referentiel_student", $record);
  return $id;
}


/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 *
 * @param object $instance An object from the form in eturdiant.html
 * @return int The id of the newly inserted referentiel record
 **/
function referentiel_update_student($form) {
// MAJ student
$ok=true;
    // DEBUG
    // echo "DEBUG : UPDATE student CALLED";
	// print_object($form);
    // echo "<br />";
	// certificat
	if (isset($form->action) && ($form->action=="modifier_student")){
		$record=new object();
		$record->id = $form->student_id;
		$record->num_student = $form->num_student;
		$record->ddn_student = ($form->ddn_student) ;
		$record->lieu_naissance = ($form->lieu_naissance);
		$record->departement_naissance = ($form->departement_naissance);
		$record->adresse_student = ($form->adresse_student);
		$record->ref_etablissement = $form->ref_etablissement;
		$record->userid = $form->userid;
	// controle	
	if (
    ($record->userid>0) 
     && 
    (($record->num_student=='') || ($record->num_student==get_string('inconnu', 'referentiel')))
    )
    {
      $user=get_record('user','id',$record->userid);
      if ($user){
        if (!empty($user->idnumber)){
          $record->num_student = $user->idnumber;
        }
        else{
          $record->num_student = $user->username;      
        }
      }
    }
		
		if(!update_record("referentiel_student", $record)){
			// echo "<br /> ERREUR UPDATE student\n";
			$ok=false;
		}
		else {
			// echo "<br /> UPDATE student $record->id\n";		
			$ok=true;
		}
		return $ok; 
	}
}


function referentiel_student_set_etablissement($userid, $etablissement_id){
// mise a jour de l'etablisssement
	if ($userid && $etablissement_id){
		$record=referentiel_get_student_user($userid);
		$record->lieu_naissance = addslashes($record->lieu_naissance);
		$record->departement_naissance = addslashes($record->departement_naissance);
		$record->adresse_student = addslashes($record->adresse_student);
		$record->ref_etablissement = $etablissement_id;
		if (update_record("referentiel_student", $record)){
			return true;
		} 
	}
	return false;	
}


/**
 * Given an certificate id, 
 * this function will permanently delete the certificate instance  
 *
 * @param object $id
 * @return boolean Success/Failure
 **/

function referentiel_delete_student($id) {
// suppression certificat
$ok_delete=false;	
	if (isset($id) && ($id>0)){
		if ($student = get_record("referentiel_student", "id", $id)) {
			// suppression 
			$ok_delete = delete_records("referentiel_student", "id", $id);
		}
	}
    return $ok_delete;
}

function referentiel_delete_student_user($userid) {
// suppression certificat
$ok_delete=false;	
	if (isset($userid) && ($userid>0)){
		if ($student = get_record("referentiel_student", "userid", $userid)) {
			// suppression 
			$ok_delete = delete_records("referentiel_student", "id", $student->id);
		}
	}
    return $ok_delete;
}

// ///////////// ETABLISSEMENT ///////////////////
/**
 * This function returns records from table referentiel_institution
 *
 * @param ref
 * @return record
 * @todo Finish documenting this function
 **/
function referentiel_get_etablissements(){
global $CFG;
	return get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_institution ');
}


function referentiel_add_etablissement($form){
// creer un etablissement
$id=0;
	if (isset($form->action) && ($form->action=="creer_etablissement")){
		$record=new object();
		$record->idnumber = ($form->idnumber);
		$record->name = ($form->name);
		$record->address = ($form->address);
		$id=insert_record("referentiel_institution", $record);
	}
	return $id;
}

function referentiel_update_etablissement($form){
	$ok=false;	
	// DEBUG
	// print_object($form);
	// echo "<br />";
// MAJ etablissement
$ok=true;
	if (isset($form->action) && ($form->action=="modifier_etablissement")){
		$record=new object();
		$record->id = $form->etablissement_id;
		$record->idnumber = ($form->idnumber);
		$record->name = ($form->name);
		$record->address = ($form->address);
		
		if(!update_record("referentiel_institution", $record)){
			// echo "<br /> ERREUR UPDATE etablissement\n";
			$ok=false;
		}
		else {
			$ok=true;
		}
		return $ok; 
	}
}


function referentiel_get_nom_etablissement($id){
	if ($id){
		$record = get_record("referentiel_institution", "id", $id);
		if ($record ){
			return $record->name;
		}
	}
	return "";
}

function referentiel_select_etablissement($userid, $etablissement_id, $appli){
$s='';
	$records=referentiel_get_etablissements();
	if ($records){
		$s.="\n".'<form action="'.$appli.'" method="get" id="selectetab'.$userid.'" class="popupform">'."\n";
		$s.='<div><select id="selectetab'.$userid.'_jump" name="jump" size="1" 
onchange="self.location=document.getElementById(\'selectetab'.$userid.'\').jump.options[document.getElementById(\'selectetab'.$userid.'\').jump.selectedIndex].value;">'."\n";
		foreach ($records as $record){
			if ($etablissement_id==$record->id){
				$s.='	<option value="'.$appli.'&amp;userid='.$userid.'&amp;etablissement_id='.$record->id.'&amp;sesskey='.sesskey().'" selected="selected" >'.$record->name.'</option>'."\n";
			}
			else{
				$s.='	<option value="'.$appli.'&amp;userid='.$userid.'&amp;etablissement_id='.$record->id.'&amp;sesskey='.sesskey().'">'.$record->name.'</option>'."\n";
			}
		}
		$s.='</select></div>'."\n";
		$s.='
<script type="text/javascript">
//<![CDATA[
document.getElementById("noscriptnavmenupopup").style.display = "none";
//]]>
</script>'."\n";
		$s.='</form>'."\n";
	}
	return $s;
}


function referentiel_delete_etablissement($id){
// suppression etablissement
	if (isset($id) && ($id>0)){
		// supprimer les enregistrements dependants
		$students=referentiel_get_students(" WHERE ref_etablissement=$id ");
		if ($students) {
			foreach ($students as $student){
				referentiel_student_set_etablissement($student->userid, 0);
			}
		}
		return delete_records("referentiel_institution", "id", $id);
	}
    return false;
}

function referentiel_get_etablissement($id){
global $CFG;
	if (isset($id) && ($id>0)){
		return get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_institution WHERE id='.$id.' ');
	}
	else 
		return 0; 
}


/**
 * This function returns an referentiel_institution id
 *
 * @param NULL
 * @return record
 * @todo Finish documenting this function
 **/
function referentiel_genere_etablissement(){
	$record=new object();
	$record->idnumber = get_string('inconnu', 'referentiel');
	$record->name = get_string('inconnu', 'referentiel');
	$record->address = get_string('inconnu', 'referentiel');
	return insert_record("referentiel_institution", $record);
}

/**
 * This function returns an referentiel_institution id
 *
 * @param NULL
 * @return id
 * @todo Finish documenting this function
 **/
function referentiel_get_min_etablissement(){
	$id_etab=referentiel_get_min_id("referentiel_institution");
	if (empty($id_etab)){
		// creer un etablissement par defaut
		$id_etab=referentiel_genere_etablissement();
	}
	return $id_etab;
}

?>