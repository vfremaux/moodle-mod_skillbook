<?php  // $Id:  lib_certificate.php,v 1.0 2009/10/16 00:00:00 jfruitet Exp $
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
 * @version $Id: lib_certificate.php,v 1.0 2009/10/16 00:00:00 jfruitet Exp $
 * @package referentiel v 4.0 2009/04/29 00:00:00 
 **/

// CERTIFICATS


/**
 * This function returns record certificate from table referentiel_certificate
 *
 * @param userid reference user id
 * @param referentiel_id reference referentiel
 * @return object
 * @todo Finish documenting this function
 **/
function referentiel_certificate_user_select($userid, $referentiel_id, $sql_filtre_where='', $sql_filtre_order=''){
// Si certificate n'existe pas, cree le certificate et le retourne
// si les conditions sont remplies
global $CFG;
    if (empty($sql_filtre_where)){
        $sql_filtre_where=' WHERE referentielid='.$referentiel_id.' AND userid='.$userid.' ';
    }
    else{
        $sql_filtre_where=' WHERE referentielid='.$referentiel_id.' AND userid='.$userid.' '.$sql_filtre_where;
    }
    if (!empty($sql_filtre_order)){
        $sql_filtre_order=' ORDER BY '.$sql_filtre_order.' ';
    }

    // DEBUG
    // echo "DEBUG :: lib_certificate.php :: Ligne 44<br>WHERE : $sql_filtre_where<br>ORDER : $sql_filtre_order\n";
	if (isset($userid) && ($userid>0) && isset($referentiel_id) && ($referentiel_id>0)){
		if (!referentiel_certificate_user_exists($userid, $referentiel_id)){
			if (referentiel_genere_certificat($userid, $referentiel_id)){
                return(get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_certificate '. $sql_filtre_where.' '.$sql_filtre_order));
            }
 			else{
				return false;
			}
		}
		else{
            return(get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_certificate '. $sql_filtre_where.' '.$sql_filtre_order));
		}
	}
	else {
		return false;
	}
}


/**
 * This function returns records of certificate from table referentiel_certificate
 *
 * @param object referentiel instance
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_liste_certificats($referentiel_instance, $userid_filtre=0, $gusers, $select_acc=0, $mode='listcertif', $appli='certificate.php', $select_all=0, $affiche=true) {

global $CFG;
global $USER;
static $isteacher=false;
static $isauthor=false;
static $iseditor=false;
static $referentiel_id = NULL;

$records=array();

// contexte
$cm = get_coursemodule_from_instance('referentiel', $referentiel_instance->id);
$course = get_record('course', 'id', $cm->course);
if (empty($cm) or empty($course)){
    print_error('REFERENTIEL_ERROR :: lib_certificate.php :: 57 :: You cannot call this script in that way');
}
	
$context = get_context_instance(CONTEXT_MODULE, $cm->id);

$referentiel_id = $referentiel_instance->referentielid;
	
$isadmin = has_capability('mod/referentiel:exportcertif', $context);
$iseditor = has_capability('mod/referentiel:managecertif', $context);
$isteacher = has_capability('mod/referentiel:approve', $context)&& !$iseditor;
$istutor = has_capability('mod/referentiel:comment', $context) && !$iseditor  && !$isteacher;
$isauthor = has_capability('mod/referentiel:write', $context) && !$iseditor  && !$isteacher  && !$istutor;
	
if (isset($referentiel_id) && ($referentiel_id>0)){
    $referentiel_referentiel=referentiel_get_referentiel_referentiel($referentiel_id);
	if (!$referentiel_referentiel){
        if ($iseditor){
            error(get_string('creer_referentiel','referentiel'), "edit.php?d=$referentiel_instance->id&amp;mode=editreferentiel&amp;sesskey=".sesskey());
		}
        else {
            error(get_string('creer_referentiel','referentiel'), "../../course/view.php?id=$course->id&amp;sesskey=".sesskey());
		}
	}
    if ($affiche){

	// Selectionner les utilisateurs pour les boîtes de selection
    if ($isadmin || $isteacher || $iseditor || $istutor){
        if (!empty($select_acc)){
            // eleves accompagnes
            $record_id_users  = referentiel_get_accompagnements_teacher($referentiel_instance->id, $course->id, $USER->id);
        }
        else{
            // tous les users possibles (pour la boite de selection)
            // Get your userids the normal way
            $record_id_users  = referentiel_get_students_course($course->id,0,0);  //seulement les stagiaires
        }

        // tenir compte des groupes pour les boites de selection
        if ( ($isadmin && !$select_all) || $isteacher || $iseditor || $istutor){
            // groupe ?
            if ($gusers && $record_id_users){ // liste des utilisateurs du groupe courant
                $record_users  = array_intersect($gusers, array_keys($record_id_users));
                // recopier
                $record_id_users=array();
                foreach ($record_users  as $record_id){
                    $record_id_users[]->userid=$record_id;
                }
            }
        }

            if ($isadmin){ // admin
                echo referentiel_select_all_certificates($appli, $mode, $userid_filtre, $select_acc, $select_all);
                if ($select_all==0){
                    echo referentiel_select_users_certificat($record_id_users, $appli, $mode,  $userid_filtre, $select_acc, $select_all);
                }
            }
            else{
                echo referentiel_select_users_accompagnes($appli, $mode, $userid_filtre, $select_acc, $select_all);
                echo referentiel_select_users_certificat($record_id_users, $appli, $mode,  $userid_filtre, $select_acc, $select_all);
            }

    }
	else{
        $userid_filtre=$USER->id; // les étudiants ne peuvent voir que leur fiche
    }
    } // affiche

	// r   ecuperer les utilisateurs filtres
    if (!empty($userid_filtre)){      // un seul certificat
        $record_id_users = referentiel_get_students_course($course->id, $userid_filtre, 0);
    }
    else if ($isadmin && $select_all){  // tous les certificats
        $record_id_users  = referentiel_get_all_users_with_certificate($referentiel_instance->referentielid);
    }
    else { // teachers    :: les certificats du cours seulement
        if (!empty($select_acc)){
            // eleves accompagnes
            $record_id_users  = referentiel_get_accompagnements_teacher($referentiel_instance->id, $course->id, $USER->id);
        }
        else{
            $record_id_users = referentiel_get_students_course($course->id, $userid_filtre, 0);
        }

        // groupes ?
        if ($gusers && $record_id_users){
            $record_users  = array_intersect($gusers, array_keys($record_id_users));
            // recopier
            $record_id_users=array();
            foreach ($record_users  as $record_id){
                $record_id_users[]->userid=$record_id;
            }
        }
    }
    
	if ($record_id_users){
		  foreach ($record_id_users  as $record_id) {   // afficher la liste d'users
				$records[]=referentiel_certificate_user($record_id->userid, $referentiel_instance->referentielid);
		  }
    }
}
  return $records;
}


// ----------------------
function referentiel_select_all_certificates($appli='certificate.php', $mode='listcertif', $userid=0, $select_acc=0, $select_all=0){
// selection tous certificats ?
global $cm;
global $course;

$s="";

	$s.='<div align="center">'."\n"; 
	$s.='<table class="selection">'."\n";
	$s.='<tr><td>';		
	// coaching
	$s.="\n".'<form name="form" method="post" action="'.$appli.'?id='.$cm->id.'&amp;action=select_all_certificates">'."\n"; 		

	$s.=get_string('exportallcertificates', 'referentiel');
  if (empty($select_all)){
      $s.='<input type="radio" name="select_all" value="1" />'.get_string('yes')."\n";
		  $s.='<input type="radio" name="select_all" value="0" checked="checked" />'.get_string('no')."\n";
	}
	else{
      $s.='<input type="radio" name="select_all" value="1" checked="checked" />'.get_string('yes')."\n";
		  $s.='<input type="radio" name="select_all" value="0" />'.get_string('no')."\n";
  }
  $s.='</td><td><input type="submit" value="'.get_string('go').'" />'."\n";;
	$s.='

<!-- coaching -->
<input type="hidden" name="select_acc" value="'.$select_acc.'" />
<!-- These hidden variables are always the same -->
<input type="hidden" name="course"        value="'.$course->id.'" />
<input type="hidden" name="sesskey"     value="'.sesskey().'" />
<input type="hidden" name="mode"          value="'.$mode.'" />
</form>'."\n";
	$s.='</td>';
	$s.='</tr></table>'."\n";
	$s.='</div>'."\n";
			
	return $s;
}

// ----------------------
function referentiel_select_users_accompagnes($appli='certificate.php', $mode='listcertif', $userid=0, $select_acc=0, $select_all=0){

global $cm;
global $course;
$s="";

	$s.='<div align="center">'."\n"; 
	$s.='<table class="selection">'."\n";
	$s.='<tr><td>';		
	// coaching
	// $s.="\n".'<form name="form" method="post" action="activite.php?id='.$cm->id.'&amp;action=select_acc">'."\n";
	$s.="\n".'<form name="form" method="post" action="'.$appli.'?id='.$cm->id.'&amp;action=select_acc">'."\n"; 		

	$s.=get_string('select_acc', 'referentiel');
  if (empty($select_acc)){
      $s.=' <input type="radio" name="select_acc" value="1" />'.get_string('yes')."\n";
		  $s.='<input type="radio" name="select_acc" value="0" checked="checked" />'.get_string('no')."\n";
	}
	else{
      $s.=' <input type="radio" name="select_acc" value="1" checked="checked" />'.get_string('yes')."\n";
		  $s.='<input type="radio" name="select_acc" value="0" />'.get_string('no')."\n";
  }
  $s.='</td><td><input type="submit" value="'.get_string('go').'" />'."\n";;
	$s.='
<!-- tous les certificats -->
<input type="hidden" name="select_all" value="'.$select_all.'" />	
<!-- These hidden variables are always the same -->
<input type="hidden" name="course"        value="'.$course->id.'" />
<input type="hidden" name="sesskey"     value="'.sesskey().'" />
<input type="hidden" name="mode"          value="'.$mode.'" />
</form>'."\n";
	$s.='</td>';
	$s.='</tr></table>'."\n";
	$s.='</div>'."\n";
			
	return $s;
}


// ----------------------
function referentiel_select_users_certificat($record_users, $appli='certificate.php', $mode='listcertif', $userid=0, $select_acc=0, $select_all=0){

global $cm;
global $course;
$maxcol=MAXBOITESSELECTION;
$s="";
$t_users=array();

	if ($record_users){
    $s.='<div align="center">'."\n"; 		
		$s.='<table class="selection">'."\n";
		$s.='<tr>';
		
		// $s.='<option value="0" selected="selected">'.get_string('choisir', 'referentiel').'</option>'."\n";		
	  foreach ($record_users as $record_u) {   // liste d'id users
			// 
			$t_users[]= array('id' => $record_u->userid, 'lastname' => referentiel_get_user_nom($record_u->userid), 'firstname' => referentiel_get_user_prenom($record_u->userid));
			$t_users_id[]= $record_u->userid;
			$t_users_lastname[] = referentiel_get_user_nom($record_u->userid);
			$t_users_firstname[] = referentiel_get_user_prenom($record_u->userid);
		}
		array_multisort($t_users_lastname, SORT_ASC, $t_users_firstname, SORT_ASC, $t_users);
		// 
		// echo "<br />Debug :: print_lib_activite.php :: 1419 ::<br />\n";
		// print_r($t_users);
		
		// exit;
		$n=count($t_users);
    if ($n>=18){
			$l=$maxcol;
			$c=(int) ($n / $l);
		}
    elseif ($n>=6){
			$l=$maxcol-2;
			$c=(int) ($n / $l);    
    }
		else{
			$l=1;
			$c=(int) ($n);		
		}
		
		if ($c*$l==$n){
      $reste=false;
    }
    else{
      $reste=true;
    }
		$i=0;
		
		for ($j=0; $j<$l; $j++){
			$s.='<td>';
			$s.="\n".'<form name="form" method="post" action="'.$appli.'?id='.$cm->id.'&amp;select_acc='.$select_acc.'&amp;action=selectuser">'."\n";
			$s.='<select name="userid" id="userid" size="4">'."\n";
			
      if ($j<$l-1){
        if (($userid=='') || ($userid==0)){
	   			$s.='<option value="0" selected="selected">'.get_string('choisir', 'referentiel').'</option>'."\n";
		  	}
			  else{
				  $s.='<option value="0">'.get_string('choisir', 'referentiel').'</option>'."\n";
			  }
			}
			else{
			   if ($reste){
            if (($userid=='') || ($userid==0)){
	   	     		$s.='<option value="0" selected="selected">'.get_string('choisir', 'referentiel').'</option>'."\n";
		  	    }
			      else{
				      $s.='<option value="0">'.get_string('choisir', 'referentiel').'</option>'."\n";
			      }
         }
         else{
    	   		if (($userid=='') || ($userid==0)){
		  	     	$s.='<option value="0" selected="selected">'.get_string('tous', 'referentiel').'</option>'."\n";
			      }
			      else{
				      $s.='<option value="0">'.get_string('tous', 'referentiel').'</option>'."\n";
			      }         
         }
			}
			
			for ($k=0; $k<$c; $k++){
				if ($userid==$t_users[$i]['id']){
					$s.='<option value="'.$t_users[$i]['id'].'" selected="selected">'.$t_users[$i]['lastname'].' '.$t_users[$i]['firstname'].'</option>'."\n";
				}
				else{
					$s.='<option value="'.$t_users[$i]['id'].'">'.$t_users[$i]['lastname'].' '.$t_users[$i]['firstname'].'</option>'."\n";
				}
				$i++; 
			}
			$s.='</select>'."\n";
			$s.='<br /><input type="submit" value="'.get_string('select', 'referentiel').'" />'."\n";;
			$s.='
<!-- tous les certificats -->
<input type="hidden" name="select_all" value="'.$select_all.'" />				
<!-- coaching -->
<input type="hidden" name="select_acc" value="'.$select_acc.'" />
<!-- These hidden variables are always the same -->
<input type="hidden" name="course"        value="'.$course->id.'" />
<input type="hidden" name="sesskey"     value="'.sesskey().'" />
<input type="hidden" name="mode"          value="'.$mode.'" />
</form>'."\n";
			$s.='</td>';
		}
		
		if ($i<$n){
			$s.='<td>';
			$s.='<form name="form" method="post" action="'.$appli.'?id='.$cm->id.'&amp;action=selectuser">'."\n";	
			$s.='<select name="userid" id="userid" size="4">'."\n";
			if (($userid=='') || ($userid==0)){
				$s.='<option value="0" selected="selected">'.get_string('tous', 'referentiel').'</option>'."\n";
			}
			else{
				$s.='<option value="0">'.get_string('tous', 'referentiel').'</option>'."\n";
			}

			while ($i <$n){
				
				if ($userid==$t_users[$i]['id']){
					$s.='<option value="'.$t_users[$i]['id'].'" selected="selected">'.$t_users[$i]['lastname'].' '.$t_users[$i]['firstname'].'</option>'."\n";
				}
				else{
					$s.='<option value="'.$t_users[$i]['id'].'">'.$t_users[$i]['lastname'].' '.$t_users[$i]['firstname'].'</option>'."\n";
				}
				$i++;
			}
			$s.='</select>'."\n";
			$s.='<br /><input type="submit" value="'.get_string('select', 'referentiel').'" />'."\n";;
			$s.='
<!-- tous les certificats -->
<input type="hidden" name="select_all" value="'.$select_all.'" />			
<!-- coaching -->
<input type="hidden" name="select_acc" value="'.$select_acc.'" />
<!-- These hidden variables are always the same -->
<input type="hidden" name="select_acc" value="'.$select_acc.'" />
<input type="hidden" name="course"        value="'.$course->id.'" />
<input type="hidden" name="sesskey"     value="'.sesskey().'" />
<input type="hidden" name="mode"          value="'.$mode.'" />
</form>'."\n";
			$s.='</td>';
		}
		$s.='</tr></table>'."\n";
		$s.='</div>'."\n";
	}
	
	return $s;
}


?>