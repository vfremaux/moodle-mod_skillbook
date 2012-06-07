<?php  // $Id:  print_lib_activite.php,v 1.0 2008/04/29 00:00:00 jfruitet Exp $
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
 * Print Library of functions for activities of module referentiel
 * 
 * @author jfruitet
 * @version $Id: print_lib_activite.php,v 1.0 2008/04/29 00:00:00 jfruitet Exp $
 * @version $Id: print_lib_activite.php,v 2.0 2009/11/30 00:00:00 jfruitet Exp $
 * @package referentiel
 **/

 
require_once("lib.php");
require_once("overlib_item.php");

//
/************** A FINIR ********
function referentiel_upload_ressources($referentiel, $activite, $userid, $select_acc){

$s='';

    $s.='
<form name="form" method="post" action="upload.php?d='.$referentiel->id.'">

<input type="hidden" name="select_acc" value="'.$select_acc.'" />
<!-- input type="hidden" name="old_liste_competences" value="'.$activite->old_liste_competences.'" / -->
<input type="hidden" name="activityid" value="'.$activite->id.'" />
<input type="hidden" name="approved" value="'.$activite->approved.'" />
<input type="hidden" name="userid" value="'.$activite->userid.'" />
<input type="hidden" name="teacherid" value="'.$activiteteacherid.'" />
<input type="hidden" name="activite_id" value="'.$activite->id.'" />
<input type="hidden" name="referentielid" value="'.$activite->referentielid.'" />
<input type="hidden" name="course" value="'.$activite->course.'" />
<input type="hidden" name="instanceid" value="'.$activite->instanceid.'" />
<input type="hidden" name="action" value="creer_document" />
<!-- These hidden variables are always the same -->
<input type="hidden" name="course"        value="'.$form->course.'" />
<input type="hidden" name="sesskey"     value="'.sesskey().'" />
<input type="hidden" name="modulename"    value="'.$form->modulename.'" />
<input type="hidden" name="instance"      value="'.$form->instance.'" />
<input type="hidden" name="mode"          value="'.$mode.'" />
<input type="submit" value="'.get_string('document_ajout', 'referentiel').'" />
<!-- input type="submit" name="cancel" value="'.get_string("quit","referentiel").'" / -->

}
********************/

// ----------------------------------------------------
function referentiel_affiche_competences_declarees($separateur1, $separateur2, $liste_certificat, $liste_activite, $liste_empreintes){
// Affiche les codes competences declarees en tenant compte de l'empreinte et de la validite
// Necessaire à l'affichage des overlib

	$MAXCOL = 30;
	
	$t_empreinte = explode($separateur1, $liste_empreintes);
	$okc = false;
	$oka = false;
	$s='';
	$tc=array();
	$liste_certificate = referentiel_purge_dernier_separateur($liste_certificat, $separateur1);
	$liste_activite = referentiel_purge_dernier_separateur($liste_activite, $separateur1);	
	if ((!empty($liste_certificat) || !empty($liste_activite)) && ($separateur1!="") && ($separateur2!="")){
		if (!empty($liste_certificat)){
			$tc = explode ($separateur1, $liste_certificat);
			$okc=true;
		}
		if (!empty($liste_activite)){
			$ta = explode ($separateur1, $liste_activite);
			$oka=true;
		}
		// DEBUG 
		// echo "<br />CODE <br />\n";
		// print_r($tc);
		if ($oka){
			$i = 0;
			
			$s.="\n".'<table class="activite">'."\n".'<tr>';
			while ($i < count($ta)){
				// CODE1:N1
				// DEBUG 
				// echo "<br />".$tc[$i]." <br />\n";
				// exit;
				$tca = explode($separateur2, $ta[$i]);
				if ($okc){
					$tcc = explode($separateur2, $tc[$i]);
				}
				// echo "<br />".$tc[$i]." <br />\n";
				// print_r($tcc);
				// exit;
				if (($i!=0) && ($i%$MAXCOL==0)){
					$s.='</tr>'."\n".'<tr>';
				}
				
				// Overlib
				$code_s = referentiel_affiche_overlib_un_item($separateur2, $tca[0]);
				
				if ($okc && isset($tcc[1]) && ($tcc[1] >= @$t_empreinte[$i])){
					$s.='<td class="vert"><b>'.$code_s.'</b></td> ';
				}
				else if ($okc && isset($tcc[1]) && ($tcc[1]>0)){
					$s.='<td class="orange"><b>'.$code_s.'</b></td> ';
				}
				else if (isset($tca[1]) && ($tca[1]>0)){
					$s.='<td class="rouge"><i>'.$code_s.'</i></td> ';
				}
				else {
					$s.='<td class="nondefini">'.$code_s.'</td> ';
				}
				$i++;
			} 
			if ($i>$MAXCOL){
				$k=$MAXCOL-$i%$MAXCOL;
				$j=0;
				while ($j<$k){
					$s.='<td class="nondefini">&nbsp;</td>';
					$j++;
				}
			}
			$s.='</tr>'."\n".'</table>'."\n";
		}
	}
	return $s;
}



// Retourne un tableau de competences declarees
// *****************************************************************
// input @param a user id and a referentiel_referentiel id         *
// output string jauge competence declarees                        *
// *****************************************************************

function referentiel_print_jauge_activite($userid, $referentiel_referentiel_id ){
// MODIF JF 2009/11/28
// affiche la liste des competences declarees dans les activites par userid pour le referentiel $referentiel_referentiel_id
	$str = '';
	
	if ($userid && $referentiel_referentiel_id){ 
		if (!referentiel_certificate_user_exists($userid, $referentiel_referentiel_id)){
			// CREER ce certificat
			referentiel_genere_certificat($userid, $referentiel_referentiel_id);
		}
		$record_certificat=referentiel_get_certificate_user($userid, $referentiel_referentiel_id);
		if ($record_certificat){
			// empreintes
			$liste_empreintes = referentiel_get_liste_empreintes_competence($referentiel_referentiel_id);
			$str .= referentiel_affiche_competences_declarees('/',':',$record_certificat->competences_certificat, $record_certificat->comptencies, $liste_empreintes);
		}
	}
	return $str;
}


// ----------------------------------------------------
function referentiel_print_activite_2($record_a, $context, $actif=true, $select_acc=0, $mode='listactivity'){
// $actif = true : le menu est active, sinon il ne l'est pas
//	fusion de referentiel_print_activite($record) et de referentiel_menu_activite($context, $record->id, $referentiel_instance->id, $record->approved);
global $CFG;
global $COURSE;
	$s="";
	if ($record_a){
		$activite_id=$record_a->id;
		$type_activite = stripslashes($record_a->type);
		$description = stripslashes($record_a->description);
		$comptencies = $record_a->comptencies;
		$comment = stripslashes($record_a->comment);
		$instanceid = $record_a->instanceid;
		$referentielid = $record_a->referentielid;
		$course = $record_a->course;
		$userid = $record_a->userid;
		$teacherid = $record_a->teacherid;
		$timecreated = $record_a->timecreated;
		$timemodified = $record_a->timemodified;
		$timemodifiedstudent = $record_a->timemodifiedstudent;
		$approved = $record_a->approved;
		$taskid = $record_a->taskid;
		
		// MODIF JF 20091108
		// afficher le menu si on l'activité est affichee dans son propre cours de création 
		$menu_actif = $actif || ($course == $COURSE->id);
		
		$user_info=referentiel_get_user_info($userid);
		$teacher_info=referentiel_get_user_info($teacherid);
		
		// dates
		if ($timecreated!=0){
			$date_creation_info=userdate($timecreated);
		}
		else{
			$date_creation_info='';
		}

		if ($timemodified!=0){
			$date_modif_info=userdate($timemodified);
		}
		else{
			$date_modif_info='';
		}

		if ($timemodifiedstudent==0){
			$timemodifiedstudent=$timecreated;
		}
		if ($timemodifiedstudent!=0){
			$date_modif_student_info=userdate($timemodifiedstudent);
		}
		else{
			$date_modif_student_info='';
		}

		
		// MODIF JF 209/10/23
		$url_course=referentiel_get_course_link($course);
		
		echo '<tr><td>';
		echo  $activite_id;
		echo '</td><td align="center">';
		echo $user_info;
		echo '</td><td align="center">';
		echo $url_course;
		echo '</td><td align="center">';
		echo $type_activite;
		// Modif JF 06/10/2010
		if ($taskid){
            // consignes associées à une tâche
            $titre_task=referentiel_get_theme_task($taskid);
            $info_task=referentiel_get_content_task($taskid);
            if ($info_task!=''){
                // lien vers la tâche
                echo '<br>'.referentiel_affiche_overlib_texte($titre_task, $info_task);
            }
            // documents associés à une tâche
            echo referentiel_print_liste_documents_task($taskid);
        }

		
		if (isset($approved) && ($approved)){
			echo '</td><td class="valide">';
		}
		else{
			echo '</td><td class="invalide">';
		}
		// Pas d'overlib
		//echo referentiel_affiche_liste_codes_competence('/',$comptencies);
		// avec overlib
		// referentiel_initialise_data_referentiel($referentielid);
		referentiel_initialise_descriptions_items_referentiel($referentielid);
		echo referentiel_affiche_overlib_item('/',$comptencies);
		
		echo '</td><td align="center">';
		echo $teacher_info;
		echo '</td><td align="center">';
		if (isset($approved) && ($approved)){
			echo get_string('approved','referentiel');
		}
		else{
			echo get_string('not_approved','referentiel');	
		}
		echo '</td>';
		echo '<td align="center">';
		echo '<span class="small">'.$date_modif_student_info.'</span>';
		echo '</td>'."\n";
		echo '<td align="center">';
		echo '<span class="small">'.$date_modif_info.'</span>';
		echo '</td>';
		// menu
		echo '<td align="center">'."\n";

		if ($menu_actif){ 
			// echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=listactivityall&amp;sesskey='.sesskey().'#activite"><img src="pix/search.gif" alt="'.get_string('plus', 'referentiel').'" title="'.get_string('plus', 'referentiel').'" /></a>'."\n";
			echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=listactivitysingle&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'#activite"><img src="pix/search.gif" alt="'.get_string('plus', 'referentiel').'" title="'.get_string('plus', 'referentiel').'" /></a>'."\n";
			$has_capability=has_capability('mod/referentiel:approve', $context);
			$is_owner=referentiel_activite_isowner($activite_id);
			
			if ($has_capability	or $is_owner){
				if ($has_capability || ($is_owner && !$approved)) {
	        		echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=updateactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/edit.gif" alt="'.get_string('edit').'" title="'.get_string('edit').'" /></a>'."\n";
				}
				if ($has_capability || $is_owner) {
			    	echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=deleteactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/delete.gif" alt="'.get_string('delete').'" title="'.get_string('delete').'" /></a>'."\n";
    			}
			}
			// valider
		    if ($has_capability){
				if (!$approved){
					echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=approveactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="pix/nonvalide.gif" alt="'.get_string('approve', 'referentiel').'" title="'.get_string('approve', 'referentiel').'" /></a>'."\n";
				}
				else{
    				echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=desapproveactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="pix/valide.gif" alt="'.get_string('desapprove', 'referentiel').'" title="'.get_string('desapprove', 'referentiel').'" /></a>'."\n";
				}
			}
			// commentaires
    		if (has_capability('mod/referentiel:comment', $context)){
    			echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=commentactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="pix/feedback.gif" alt="'.get_string('more', 'referentiel').'" title="'.get_string('comment', 'referentiel').'" /></a>'."\n";
			}
		}
		else{
			echo '&nbsp; '.get_string('activite_exterieure', 'referentiel');
		}
		echo '</td></tr>'."\n";
	}
	return $s;
}


// ----------------------------------------------------
function referentiel_affiche_liste_codes_competence($separateur, $liste){
// supprime separateur
	return referentiel_affiche_overlib_item($separateur, $liste);
}

// ----------------------------------------------------
function referentiel_selection_liste_codes_item_competence($separateur, $liste){
// input : liste de code de la forme 'CODE''SEPARATEUR' 
// retourne le selecteur
	global $t_item_description_competence;
	
	$nl='';
	$s1='<input type="checkbox" id="code_item_';
	$s2='" name="code[]" value="';
	$s3='" />';
	$s4='<label for="code_item_';
	$s5='">';
	$s6='</label> '."\n";	
	$tl = explode($separateur, $liste);
	if (!isset($t_item_description_competence) || (!$t_item_description_competence)){
		$ne = count($tl);
		$select='';
		for ($i=0; $i<$ne;$i++){
			if (trim($tl[$i])!=""){
				//$nl.=$s1.$i.$s2.$tl[$i].$s3.$s4.$i.$s5.$tl[$i].$s6;
				echo $s1.$i.$s2.$tl[$i].$s3.$s4.$i.$s5.$tl[$i].$s6;
			}
		}
	}
	else{
		$ne = count($tl);
		$select='';
		for ($i=0; $i<$ne;$i++){
			if (trim($tl[$i])!=""){
				// $nl.=$s1.$i.$s2.$tl[$i].$s3.$s4.$i.$s5.referentiel_affiche_overlib_un_item($separateur, $tl[$i]).$s6;
				echo $s1.$i.$s2.$tl[$i].$s3.$s4.$i.$s5.referentiel_affiche_overlib_un_item($separateur, $tl[$i]).$s6;
			}
		}
	}
	return $nl;
}

// ----------------------------------------------------
function referentiel_modifier_selection_liste_codes_item_competence($separateur, $liste_complete, $liste_saisie, $is_task=false, $id_activite=0, $comportement=''){
// input : liste de code de la forme 'CODE''SEPARATEUR' 
// input : liste2 de code de la forme 'CODE''SEPARATEUR' codes declares
// retourne le selecteur
	// DEBUG
	// echo "$liste_saisie<br />\n";
global $t_item_description_competence;
	$nl='';

	if ($id_activite==0){
			$s1='<input type="checkbox" id="code_item_';
			$s2='" name="code[]" value="';
			$s3='"';
			$s4=' /><label for="code_item_';
			$s5='">';
			$s6='</label> '."\n";	
	}
	else{
			$s1='<input type="checkbox" id="code_item_'.$id_activite.'_';
			$s2='" name="code_item_'.$id_activite.'[]" value="';
			$s3='"';
			if (!empty($comportement)){
			$s4=' '.$comportement.' /><label for="code_item_'.$id_activite.'_';
      }
      else{
        $s4=' /><label for="code_item_'.$id_activite.'_';
      }
			$s5='">';
			$s6='</label> '."\n";	
	}
	
	$checked=' checked="checked"';
	$tl=explode($separateur, $liste_complete);
	$liste_saisie=strtr($liste_saisie, $separateur, ' ');
	$liste_saisie=trim(strtr($liste_saisie, '.', '_'));
	// echo "<br>DEBUG :: 201 :: $liste_saisie<br />\n";	
	$ne=count($tl);
	$select='';
	for ($i=0; $i<$ne;$i++){
		$code=trim($tl[$i]);
		
		$le_code=referentiel_affiche_overlib_un_item($separateur, $code);
		
		if ($code!=""){
			// $code_search='/'.strtr($code, '.', '_').'/';
			// echo "RECHERCHE '$code_search' dans '$liste_saisie'<br />\n";
			// echo "<br>DEBUG :: print_lib_activite :: 213 :: $code_search<br />\n";	
			// if (preg_match($code_search, $liste_saisie)){
			
			$code_search=strtr($code, '.', '_');
			// if (eregi($code_search, $liste_saisie)){
			if (stristr($liste_saisie, $code_search)){
				if (!$is_task){
					//$nl.=$s1.$i.$s2.$code.$s3.$checked.$s4.$i.$s5.$le_code.$s6;
					echo $s1.$i.$s2.$code.$s3.$checked.$s4.$i.$s5.$le_code.$s6;
				}
				else{
					// $nl.='<i>'.$le_code.'</i> ';
					//$nl.=$s1.$i.$s2.$code.$s3.$checked.$s4.$i.$s5.$le_code.$s6."\n";
					echo $s1.$i.$s2.$code.$s3.$checked.$s4.$i.$s5.$le_code.$s6;
				}
			}
			else {
				if (!$is_task){
					// $nl.=$s1.$i.$s2.$code.$s3.$s4.$i.$s5.$le_code.$s6;
					echo $s1.$i.$s2.$code.$s3.$s4.$i.$s5.$le_code.$s6;
				}
				else{
					// $nl.=$s1.$i.$s2.$code.$s3.$s4.$i.$s5.$le_code.$s6;
					echo $s1.$i.$s2.$code.$s3.$s4.$i.$s5.$le_code.$s6;
				}
			}
		}
	}

	return $nl;
}



// Affiche une entete activite
// *****************************************************************
// *
// output string                                                    *
// *****************************************************************

function referentiel_print_entete_activite(){
// Affiche une entete activite
$s='';
	$s.='<table class="activite" width="100%">'."\n";
	$s.='<tr>';
	$s.='<th>'.get_string('id','referentiel').'</th>';
	$s.='<th>'.get_string('auteur','referentiel').'</th>';
	$s.='<th>'.get_string('course').'</th>';	
	$s.='<th>'.get_string('type_activite','referentiel').'</th>';
	$s.='<th>'.get_string('liste_codes_competence','referentiel').'</th>';
	$s.='<th>'.get_string('referent','referentiel').'</th>';
	$s.='<th>'.get_string('validation','referentiel').'</th>';
	$s.='<th>'.get_string('timemodifiedstudent','referentiel').'</th>';	
	$s.='<th>'.get_string('timemodified','referentiel').'</th>';
	$s.='<th>&nbsp;</th>';	
	$s.='</tr>'."\n";
	return $s;
}

// Affiche une entete activite
// *****************************************************************
// *
// output string                                                    *
// *****************************************************************
function referentiel_print_entete_activite_complete(){
// Affiche une entete activite complete
$s='';
	$s.='<table class="activite" width="100%">'."\n";
	$s.='<th>'.get_string('id','referentiel').'</th>';
	$s.='<th>'.get_string('auteur','referentiel').'</th>';
	$s.='<th>'.get_string('course').'</th>';	
	$s.='<th>'.get_string('type_activite','referentiel').'</th>';
	$s.='<th colspan="2">'.get_string('description','referentiel').'</th>';
	$s.='<th rowspan="3">'.get_string('menu','referentiel').'</th>'."\n";
	$s.='</tr>'."\n";
	$s.='<tr>';	
	$s.='<th colspan="2">'.get_string('liste_codes_competence','referentiel').'</th>';
	$s.='<th>'.get_string('referent','referentiel').'</th>';
	$s.='<th>'.get_string('validation','referentiel').'</th>';
	$s.='<th>'.get_string('timemodifiedstudent','referentiel').'</th>';	
	$s.='<th>'.get_string('timemodified','referentiel').'</th>';
	$s.='</tr>'."\n";
	$s.='<tr>';
	$s.='<th colspan="3">'.get_string('commentaire','referentiel').'</th>';
	$s.='<td colspan="3" class="yellow" align="center">'.get_string('document','referentiel').'</td>';
	$s.='</tr>'."\n";
	return $s;
}

function referentiel_print_enqueue_activite(){
// Affiche une entete activite
	$s='</table>'."\n";
	return $s;
}

// Affiche une ligne de la table quand il n'y a pas d'activite pour userid 
// *****************************************************************
// input @param a user id                                          *
// output string                                                     *
// *****************************************************************

function referentiel_print_aucune_activite_user($userid){
	$s='';
	if ($userid){
		$user_info=referentiel_get_user_info($userid);
		$date_modif_info=userdate(time());
	}
	else{
		$user_info="&nbsp;";
		$date_modif_info="&nbsp;";
	}
	
	$s.=get_string('zero_activite','referentiel',$date_modif_info).' '.$user_info."\n";
	
	return $s;
}


// Affiche une activite et les documents associés
// *****************************************************************
// input @param a $record_a   of activite                          *
// output null                                                     *
// *****************************************************************

function referentiel_print_activite($record_a){
$s="";
	if ($record_a){
		$activite_id=$record_a->id;
		$type_activite = stripslashes($record_a->type);
		$description = stripslashes($record_a->description);
		$comptencies = $record_a->comptencies;
		$comment = stripslashes($record_a->comment);
		$instanceid = $record_a->instanceid;
		$referentielid = $record_a->referentielid;
		$course = $record_a->course;
		$userid = $record_a->userid;
		$teacherid = $record_a->teacherid;
		$timecreated = $record_a->timecreated;
		$timemodified = $record_a->timemodified;
		$approved = $record_a->approved;
		
		$user_info=referentiel_get_user_info($userid);
		$teacher_info=referentiel_get_user_info($teacherid);
		
		// dates
		$date_creation_info=userdate($timecreated);		
		$date_modif_info=userdate($timemodified);
		$taskid = $record_a->taskid;
		
		$s.='<tr><td>';
		$s.= $activite_id;
		$s.='</td><td>';
		$s.=$user_info;
		$s.='</td><td>';
		$s.=$type_activite;
		// Modif JF 06/10/2010
		if ($taskid){
            // consignes associées à une tâche
            $titre_task=referentiel_get_theme_task($taskid);
            $info_task=referentiel_get_content_task($taskid);
            if ($info_task!=''){
                // lien vers la tâche
                $s.='<br>'.referentiel_affiche_overlib_texte($titre_task, $info_task);
            }
            // documents associés à une tâche
            echo referentiel_print_liste_documents_task($taskid);
        }
/*
		p($type_activite);
		$s.=nl2br($description);
*/

		if (isset($approved) && ($approved)){
			$s.='</td><td class="valide">';
		}
		else{
			$s.='</td><td class="invalide">';
		}
		$s.=referentiel_affiche_liste_codes_competence('/',$comptencies);
/*
		$s.=nl2br($comment);
*/
		$s.='</td><td>';
		$s.=$teacher_info;
		$s.='</td><td>';
		if (isset($approved) && ($approved)){
			$s.=get_string('approved','referentiel');
		}
		else{
			$s.=get_string('not_approved','referentiel');	
		}
		$s.='</td><td>';
		$s.='<span class="small">'.$date_modif_info.'</span>';
		
/***************************** DOCUMENTS *******************************
		// charger les documents associes à l'activite courante
		if (isset($activite_id) && ($activite_id>0)){
			$activityid=$activite_id; // plus pratique
			// AFFICHER LA LISTE DES DOCUMENTS
			$compteur_document=0;
			$records_document = referentiel_get_documents($activityid);
	    	if ($records_document){
    			// afficher
				// DEBUG
				// echo "<br/>DEBUG ::<br />\n";
				// print_r($records_document);
				foreach ($records_document as $record_d){
					$compteur_document++;
        			$document_id=$record_d->id;
					$type = $record_d->type;
					$description = $record_d->description;
					$url = $record_d->url;
					$activityid = $record_d->activityid;
					if (isset($record_d->target) && ($record_d->target==1)){
						$target='_blank'; // fenêtre cible
					}
					else{
						$target='';
					}
					if (isset($record_d->label)){
						$label=$record_d->label; // fenêtre cible
					}
					else{
						$label='';
					}

					print_string('document','referentiel');
					p($document_id);
					print_string('type','referentiel');
					p($type); 
					print_string('description','referentiel');
					echo (nl2br($description)); 
					print_string('url','referentiel'); 
					if (eregi("http",$url)){
						echo '<a href="'.$url.'" target="_blank">'.$url.'</a>';
					}
					else{
						echo $url;
					}
				}
			}
		}
******************************************************************************/
		$s.='</td></tr>'."\n";
	}
	return $s;
}

// Affiche une activite et les documents associés
// *****************************************************************
// input @param a $record_a   of activite                            *
// output null                                                     *
// *****************************************************************

function referentiel_print_activite_detail($record_a){
    $s='';
    $s0='';
    $s1='';
    $s2='';
    $nblignes=4; // hauteur du tableau
    $nbressource=0;
    
	if ($record_a){
		$activite_id=$record_a->id;
		$type_activite = stripslashes($record_a->type);
		$description = stripslashes($record_a->description);
		$comptencies = $record_a->comptencies;
		$comment = stripslashes($record_a->comment);
		$instanceid = $record_a->instanceid;
		$referentielid = $record_a->referentielid;
		$course = $record_a->course;
		$userid = $record_a->userid;
		$teacherid = $record_a->teacherid;
		$timecreated = $record_a->timecreated;
		$timemodified = $record_a->timemodified;
		$timemodifiedstudent = $record_a->timemodifiedstudent;
		$approved = $record_a->approved;
		
		$user_info=referentiel_get_user_info($userid);
		$teacher_info=referentiel_get_user_info($teacherid);
		
		// dates
		$date_creation_info=userdate($timecreated);
		$date_modif_info=userdate($timemodified);
		if ($timemodifiedstudent!=0){
			$date_modif_student_info=userdate($timemodifiedstudent);
		}
		else{
			$date_modif_student_info='';
		}
		$taskid = $record_a->taskid;

        $s0.='
<a name="activite_'.$activite_id.'"></a>
<table class="activite" width="100%">
<tr valign="top">';
		if (isset($approved) && ($approved)){
			$s0.='<td class="valide" ';
		}
		else{
			$s0.='<td class="invalide" ';
		}

        $s0.=' rowspan="';
        
        $s1.='">
	<b>'.get_string('id_activite','referentiel', $activite_id).'  </b>
    </td>
    <td>
	<b>'.get_string('type_activite','referentiel').'</b> '.$type_activite."\n";

		if ($taskid){
            // consignes associées à une tâche
            $titre_task=referentiel_get_theme_task($taskid);
            $info_task=referentiel_get_content_task($taskid);
            if ($info_task!=''){
                // lien vers la tâche
                $s1.='<br>'.referentiel_affiche_overlib_texte($titre_task, $info_task)."\n";
            }
            // documents associés à une tâche
            $s1.=referentiel_print_liste_documents_task($taskid)."\n";
        }

        $s1.='</td>
    <td>
     <b>'.get_string('auteur','referentiel').'  </b>'.$user_info.'
    </td>
	<td>
	<b>'.get_string('timecreated','referentiel').'  </b>
<span class="small">'.$date_creation_info.'</span>'.'
    </td>	
    <td>
     <b>'.get_string('timemodifiedstudent','referentiel').' </b>
<span class="small">'.$date_modif_student_info.'</span>'.'
    </td>
    <td>
     <b>'.get_string('referent','referentiel').'  </b>'.$teacher_info.'
    </td>
    <td>
     <b>'.get_string('validation','referentiel').'  </b>
';
		if (isset($approved) && ($approved)){
			$s1.=get_string('approved','referentiel');
		}
		else{
			$s1.=get_string('not_approved','referentiel');
		}
        $s1.='
    </td>
    <td>
     <b>'.get_string('timemodified','referentiel').' </b>
<span class="small">'.$date_modif_info.'</span>'.'
    </td>
</tr>
<tr valign="top">
';

		if (isset($approved) && ($approved)){
			$s1.='<td class="valide" colspan="7">'."\n";
		}
		else{
			$s1.='<td class="invalide" colspan="7">'."\n";
		}
		$s1.='<b>'.get_string('liste_codes_competence','referentiel').'</b>'."\n";
		$s1.=referentiel_affiche_liste_codes_competence('/',$comptencies)."\n";
        $s1.='
    </td>
</tr>

<tr valign="top">
    <td align="left" colspan="7">
	<b>'.get_string('description','referentiel').'</b><br>
        '.nl2br($description).'
    </td>
</tr>
<tr valign="top">
    <td align="left" colspan="7">
	<b>'.get_string('commentaire','referentiel').'</b>
    <br>'.nl2br($comment).'
    </td>
</tr>
';

        // charger les documents associes à l'activite courante
		if (isset($activite_id) && ($activite_id>0)){
			$activityid=$activite_id; // plus pratique
			// AFFICHER LA LISTE DES DOCUMENTS
			$compteur_document=0;
			$records_document = referentiel_get_documents($activityid);
	    	if ($records_document){
    			// afficher
				// DEBUG
				// echo "<br/>DEBUG <br />\n";
				// print_r($records_document);
                $nbressource=count($records_document);
                $s2.='<!-- DOCUMENTS -->
<tr valign="top"><td class="jaune" colspan="7">';
                if ($nbressource>1){
            		$s2.=get_string('ressources_associees','referentiel',$nbressource);
                }
                else{
            		$s2.=get_string('ressource_associee','referentiel',$nbressource);
                }
                $s2.="\n";

				foreach ($records_document as $record_d){
					$compteur_document++;
        			$document_id=$record_d->id;
					$type = stripslashes($record_d->type);
					$description = stripslashes($record_d->description);
					$url = $record_d->url;
					$activityid = $record_d->activityid;
					if (isset($record_d->target) && ($record_d->target==1)){
						$target='_blank'; // fenêtre cible
					}
					else{
						$target='';
					}
					if (isset($record_d->label)){
						$label=$record_d->label; // fenêtre cible
					}
					else{
						$label='';
					}
					
                    $s.='<br><b>'.get_string('num','referentiel').'</b><i>'.$document_id.'</i></b>
&nbsp; &nbsp; &nbsp;
<b>'.get_string('type','referentiel').'</b> : '.$type.'
&nbsp; &nbsp; &nbsp;
<b>'.get_string('url','referentiel').'</b>  :
';
                    $s.=referentiel_affiche_url($url, $label, $target);
                    $s.='&nbsp; &nbsp; &nbsp; <b>'.get_string('description','referentiel').'</b> : '.nl2br($description).'
';
				}
			}
		}
        if ($nbressource){
            $nblignes=$nblignes+$nbressource+1;
        }
        echo $s0.$nblignes.$s1;

		if ($s2){
            echo $s2;
        }
		if ($s){
            echo $s.'</td></tr>'."\n";
        }

        echo '</table>
';

	}
}

// Affiche les activites de ce referentiel
function referentiel_liste_toutes_activites($id_referentiel){
	if (isset($id_referentiel) && ($id_referentiel>0)){
		// DEBUG
		// echo "<br/>DEBUG :: $id_referentiel<br />\n";
		
		$records = referentiel_get_activites($id_referentiel);
		if (!$records){
			print_error(get_string('noactivite','referentiel'), "activite.php?d=$id_referentiel&amp;mode=add");
		}
	    else {
    		// afficher
			// DEBUG
			// echo "<br/>DEBUG ::<br />\n";
			// print_r($records);
			foreach ($records as $record){
				referentiel_print_activite($record);
			}
		}
	}
}




// Menu
function referentiel_menu_activite_detail($context, $activite_id, $referentiel_instance_id, $approved, $select_acc=0, $mode='updateactivity'){
	global $CFG;
			echo '<div align="center">';
        	// echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel_instance_id.'&amp;activite_id='.$activite_id.'&amp;mode=listactivity&amp;old_mode='.$mode.'&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'#activite"><img src="pix/search.gif" alt="'.get_string('moins', 'referentiel').'" title="'.get_string('moins', 'referentiel').'" /></a>';
			echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel_instance_id.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;mode=listactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'#activite"><img src="pix/nosearch.gif" alt="'.get_string('moins', 'referentiel').'" title="'.get_string('moins', 'referentiel').'" /></a>';
			if (has_capability('mod/referentiel:approve', $context)){
				echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel_instance_id.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;mode=updateactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/edit.gif" alt="'.get_string('edit').'" title="'.get_string('edit').'" /></a>';
				echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel_instance_id.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;mode=deleteactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/delete.gif" alt="'.get_string('delete').'" title="'.get_string('delete').'" /></a>';
    	    }
			else if (referentiel_activite_isowner($activite_id)) {
            	if (!$approved){
					echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel_instance_id.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;mode=updateactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/edit.gif" alt="'.get_string('edit').'" title="'.get_string('edit').'" /></a>';
	            }
				echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel_instance_id.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;mode=deleteactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/delete.gif" alt="'.get_string('delete').'" title="'.get_string('delete').'" /></a>';
    	    }
			// valider
    	    if (has_capability('mod/referentiel:approve', $context)){
				if (!$approved){
            		echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel_instance_id.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;mode=approveactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="pix/nonvalide.gif" alt="'.get_string('approve', 'referentiel').'" title="'.get_string('approve', 'referentiel').'" /></a>';
				}
	       		else{
    	        	echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel_instance_id.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;mode=desapproveactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="pix/valide.gif" alt="'.get_string('desapprove', 'referentiel').'" title="'.get_string('desapprove', 'referentiel').'" /></a>';
				}
			}
	        // commentaires
    	    if (has_capability('mod/referentiel:comment', $context)){
        		echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel_instance_id.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;mode=commentactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="pix/feedback.gif" alt="'.get_string('more', 'referentiel').'" title="'.get_string('comment', 'referentiel').'" /></a>';
			}
			echo '</div>'."\n";
			
}



// Affiche les activites de ce referentiel
function referentiel_menu_activite($context, $activite_id, $referentiel_instance_id, $approved, $select_acc=0, $mode='updateactivity'){
	global $CFG;
	$s="";
	$s.='<tr><td align="center" colspan="7">'."\n";
	$s.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel_instance_id.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;mode=listactivityall&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'#activite"><img src="pix/search.gif" alt="'.get_string('plus', 'referentiel').'" title="'.get_string('plus', 'referentiel').'" /></a>'."\n";
	
	$has_capability=has_capability('mod/referentiel:approve', $context);
	$is_owner=referentiel_activite_isowner($activite_id);
	
	if ($has_capability	or $is_owner){
		if ($has_capability || ($is_owner && !$approved)) {
	        $s.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel_instance_id.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;mode=updateactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/edit.gif" alt="'.get_string('edit').'" title="'.get_string('edit').'" /></a>'."\n";
		}
		if ($has_capability || $is_owner) {
		    $s.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel_instance_id.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;mode=deleteactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/delete.gif" alt="'.get_string('delete').'" title="'.get_string('delete').'" /></a>'."\n";
    	}
	}
	// valider
    if ($has_capability){
		if (!$approved){
			$s.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel_instance_id.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;mode=approveactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="pix/nonvalide.gif" alt="'.get_string('approve', 'referentiel').'" title="'.get_string('approve', 'referentiel').'" /></a>'."\n";
		}
		else{
    		$s.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel_instance_id.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;mode=desapproveactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="pix/valide.gif" alt="'.get_string('desapprove', 'referentiel').'" title="'.get_string('desapprove', 'referentiel').'" /></a>'."\n";
		}
	}
	// commentaires
    if (has_capability('mod/referentiel:comment', $context)){
    	$s.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel_instance_id.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;mode=commentactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="pix/feedback.gif" alt="'.get_string('more', 'referentiel').'" title="'.get_string('comment', 'referentiel').'" /></a>'."\n";
	}
	$s.='</td></tr>'."\n";
	return $s;
}



/**************************************************************************
 * takes a list of records, the current referentiel, an optionnal user id *
 * and mode to display                                                    *
 * input @param string  $mode                                             *
 *       @param object $referentiel_instance                              *
 *       @param int $userid_filtre                                        *
 *       @param array of objects $gusers of users get from current group  *
 *       @param string $sql_filtre_where, $sql_filtre_order               *
 * output null                                                            *
 **************************************************************************/
function referentiel_print_liste_activites($mode, $referentiel_instance, $userid_filtre=0, $gusers=NULL, $sql_filtre_where, $sql_filtre_order, $select_acc) {
global $CFG;
global $USER;
static $istutor=false;
static $isteacher=false;
static $isauthor=false;
static $iseditor=false;
static $referentiel_id = NULL;
global $appli;

	// contexte
    $cm = get_coursemodule_from_instance('referentiel', $referentiel_instance->id);
    $course = get_record('course', 'id', $cm->course);
	if (empty($cm) or empty($course)){
        print_error('REFERENTIEL_ERROR 5 :: print_lib_activite.php :: You cannot call this script in that way');
	}
	
  $context = get_context_instance(CONTEXT_MODULE, $cm->id);
	
	$records = array();
	$referentiel_id = $referentiel_instance->referentielid;
	$iseditor = has_capability('mod/referentiel:writereferentiel', $context);
	$isteacher = has_capability('mod/referentiel:approve', $context)&& !$iseditor;
	$istutor = has_capability('mod/referentiel:comment', $context) && !$iseditor  && !$isteacher;	
	$isauthor = has_capability('mod/referentiel:write', $context) && !$iseditor  && !$isteacher  && !$istutor;
	/*
	// DEBUG
	if ($isteacher) echo "Teacher ";
	if ($iseditor) echo "Editor ";
	if ($istutor) echo "Tutor ";
	if ($isauthor) echo "Author ";
	*/
	
	
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
	 	// preparer les variables globales pour Overlib
		// referentiel_initialise_data_referentiel($referentiel_referentiel->id);
		referentiel_initialise_descriptions_items_referentiel($referentiel_referentiel->id);

		// boite pour selectionner les utilisateurs ?
		if ($isteacher || $iseditor || $istutor){
			if (!empty($select_acc)){
			  // eleves accompagnes
        $record_id_users  = referentiel_get_accompagnements_teacher($referentiel_instance->id, $course->id, $USER->id);
      }
			else{
  			// tous les users possibles (pour la boite de selection)
	   		// Get your userids the normal way
		  	$record_id_users  = referentiel_get_students_course($course->id,0,0);  //seulement les stagiaires
			}
      if ($gusers && $record_id_users){ // liste des utilisateurs du groupe courant
				// echo "<br />DEBUG :: print_lib_activite.php :: 740 :: GUSERS<br />\n";
				// print_object($gusers);
				// echo "<br />\n";
				// exit;
				$record_users  = array_intersect($gusers, array_keys($record_id_users));
				// $record_users  = array_intersect_assoc($record_id_users, array_keys($gusers));
				// echo "<br />DEBUG :: print_lib_activite.php :: 745 :: RECORD_USERS<br />\n";
				// print_r($record_users  );
				// echo "<br />\n";
				// recopier 
				$record_id_users=array();
				foreach ($record_users  as $record_id){
					$record_id_users[]->userid=$record_id;
				}
			}
			// DEBUG
			// echo "<br />DEBUG :: print_lib_activite.php :: 734 :: Record_id_users<br />\n";
			// print_object($record_id_users  );
			// echo "<br />\n";
			// exit;

			echo referentiel_select_users_activite($record_id_users, $userid_filtre, $mode);
		}
		// filtres
		if ((!$isteacher) && (!$iseditor) && (!$istutor)){
			$userid_filtre=$USER->id; 
		}
		
		// recuperer les utilisateurs filtres
		if (!empty($select_acc)){
			  // eleves accompagnes
        $record_id_users  = referentiel_get_accompagnements_teacher($referentiel_instance->id, $course->id, $USER->id);
    }
		else{
		  // $userid_filtre est l'id de l'utilisateur dont on affiche les activites
		  // si $userid_filtre ==0 on retourne tous les utilisateurs du cours et du groupe
		  $record_id_users = referentiel_get_students_course($course->id, $userid_filtre, 0);
		}
		
    if ($record_id_users && $gusers){ // liste des utilisateurs du groupe courant
			$record_users  = array_intersect($gusers, array_keys($record_id_users));
			// recopier 
			$record_id_users=array();
			foreach ($record_users  as $record_id){
				$record_id_users[]->userid=$record_id;
			}
		}

		if ($record_id_users){
			// Afficher 		
			if (isset($mode) && ($mode=='listactivityall')){
				echo referentiel_print_entete_activite_complete();
			}
			else if (isset($mode) && ($mode=='listactivitysingle')){
				echo referentiel_print_entete_activite_complete();
			}
			else{
				echo referentiel_print_entete_activite();
			}
		    foreach ($record_id_users  as $record_id) {   // afficher la liste d'users
				// recupere les enregistrements
				// MODIF JF 23/10/2009
				if (isset($userid_filtre) && ($userid_filtre==$USER->id)){
					$actif=true; 
					$records=referentiel_get_all_activites_user($referentiel_instance->referentielid, $record_id->userid, $sql_filtre_where, $sql_filtre_order);
				}
				else if (isset($mode) && ($mode=='listactivityall')){
					$actif=false;
					$records=referentiel_get_all_activites_user($referentiel_instance->referentielid, $record_id->userid, $sql_filtre_where, $sql_filtre_order);
				}
				else{
					$actif=false;
					// 	$records=referentiel_get_all_activites_user_course($referentiel_instance->referentielid, $record_id->userid, $course->id);
					$records=referentiel_get_all_activites_user($referentiel_instance->referentielid, $record_id->userid, $sql_filtre_where, $sql_filtre_order);
				}
				
				if ($records){
					// MODIF JF 2009/11/28
					// Liste des competences declarees
					echo '<td colspan="10" align="center">'.get_string('competences_declarees','referentiel', referentiel_get_user_info($record_id->userid))."\n".referentiel_print_jauge_activite($record_id->userid, $referentiel_id).'</td>'."\n";
				    foreach ($records as $record) {   // afficher l'activite
						// Afficher 	
						if (isset($mode) && ($mode=='listactivityall')){
							// referentiel_print_activite_detail($record);
							// referentiel_menu_activite_detail($context, $record->id, $referentiel_instance->id, $record->approved, $select_acc);
							echo referentiel_print_activite_2_complete($record, $context, $actif, $select_acc);
						}
						elseif (isset($mode) && ($mode=='listactivitysingle')){
						/*
							referentiel_print_activite_detail($record);
							referentiel_menu_activite_detail($context, $record->id, $referentiel_instance->id, $record->approved, $select_acc);
						*/
							echo referentiel_print_activite_2_complete($record, $context, $actif, $select_acc);
						}
						else{
							echo referentiel_print_activite_2($record, $context, $actif, $select_acc);
						}
					}
					// MODIF JF 2009/11/28
					// echo '<td colspan="10" align="center">'.get_string('competences_declarees','referentiel', referentiel_get_user_info($record_id->userid))."\n".referentiel_print_jauge_activite($record_id->userid, $referentiel_id).'</td>'."\n";
				}
				else{
					if (isset($mode) && ($mode=='listactivity')){
						echo '<tr><td class="zero" colspan="10" align="center">'.referentiel_print_aucune_activite_user($record_id->userid).'</td></tr>'."\n";
					}
					else if (isset($mode) && ($mode=='listactivityall')){
						// echo '<tr><td class="zero" colspan="7" align="center">'.referentiel_print_aucune_activite_user($record_id->userid).'</td></tr>'."\n";
					}
				}
    		}
			// Afficher 		
			//if ($mode!='listactivitysingle'){
				echo referentiel_print_enqueue_activite();
			//}
		}
	}
	echo '<br /><br />'."\n";
	return true;
}


/**************************************************************************
 * takes a the current referentiel, an user id                            *
 * input                                                                  *
 *       @param object $referentiel_instance                              *
 *       @param int $userid                                               *
 * output true                                                            *
 **************************************************************************/

function referentiel_print_liste_activites_user($referentiel_instance, $userid, $sql_filtre_where='', $sql_filtre_order='') {
global $CFG;
global $USER;
static $istutor=false;
static $isteacher=false;
static $isauthor=false;
static $iseditor=false;
static $referentiel_id = NULL;
global $appli;

	// contexte
    $cm = get_coursemodule_from_instance('referentiel', $referentiel_instance->id);
    $course = get_record('course', 'id', $cm->course);
	if (empty($cm) or empty($course)){
        print_error('REFERENTIEL_ERROR 5 :: print_lib_activite.php :: You cannot call this script in that way');
	}
	
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
	
	$records = array();
	$referentiel_id = $referentiel_instance->referentielid;
	$iseditor = has_capability('mod/referentiel:writereferentiel', $context);
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
	 	// preparer les variables globales pour Overlib
		// referentiel_initialise_data_referentiel($referentiel_referentiel->id);
		referentiel_initialise_descriptions_items_referentiel($referentiel_referentiel->id);
		
		if (isset($userid) && ($userid==$USER->id)){ 
			$records=referentiel_get_all_activites_user($referentiel_instance->referentielid, $record_id->userid, $sql_filtre_where, $sql_filtre_order);
		}
		else{
			$records=referentiel_get_all_activites_user_course($referentiel_instance->referentielid, $record_id->userid, $course->id);
		}
		if ($records){
			foreach ($records as $record) {   
				// Afficher 	
				referentiel_print_activite_detail($record);
				referentiel_menu_activite_detail($context, $record->id, $referentiel_instance->id, $record->approved, $select_acc);
			}
		}
		else{
			echo referentiel_print_aucune_activite_user($record_id->userid);
		}
		// Afficher le menu
		// referentiel_menu_activite_detail($context, $record->id, $referentiel_instance->id, $record->approved, $select_acc);
	}
	echo '<br /><br />'."\n";
	return true;
}



/************************************************************************
 * takes a list of records, a search string,                            *
 * input @param array $records   of users                               *
 *       @param string $search                                          *
 * output null                                                          *
 ************************************************************************/


function referentiel_select_users_activite($record_users, $userid=0, $mode='listactivity', $select_acc=0){
global $cm;
global $course;
$maxcol=MAXBOITESSELECTION;
$s="";
	if ($record_users){
		
		$s.='<div align="center">
		
<form name="form" method="post" action="activite.php?id='.$cm->id.'&amp;action=selectuser">'."\n"; 
		$s.='<table class="selection">'."\n";
		$s.='<tr>';
		$s.='<td>';
		if (($userid=='') || ($userid==0)){
			$s.='<input type="radio" name="userid" id="userid" value="" checked="checked" />'.get_string('tous', 'referentiel').'</td>'."\n";;
		}
		else{
			$s.='<input type="radio" name="userid" id="userid" value="" />'.get_string('tous', 'referentiel').'</td>'."\n";;
		}
		$s.='</tr>';
		$s.='<tr>';
		
		$col=0;
		$lig=0;
		foreach ($record_users as $record_u) {   // liste d'id users
			$user_info=referentiel_get_user_info($record_u->userid);
			if ($record_u->userid==$userid){
				$s.='<td><input type="radio" name="userid" id="userid" value="'.$record_u->userid.'" checked="checked" />'.$user_info.'</td>'."\n";;
			}
			else{
				$s.='<td><input type="radio" name="userid" id="userid" value="'.$record_u->userid.'" />'.$user_info.'</td>'."\n";;
			}
			if ($col<$maxcol){
				$col++;
			}
			else{
				$s.='</tr><tr>'."\n";
				$col=0;
				$lig++;
			}
		}
		if ($lig>0){
			while ($col<$maxcol){
				$s.='<td>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </td>'."\n";
				$col++;
			}
		}
			
		$s.='<td>&nbsp; &nbsp; &nbsp; <input type="submit" value="'.get_string('select', 'referentiel').'" /></td>';
		$s.='
<input type="hidden" name="select_acc" value="'.$select_acc.'" />
<!-- These hidden variables are always the same -->
<input type="hidden" name="course"        value="'.$course->id.'" />
<input type="hidden" name="sesskey"     value="'.sesskey().'" />
<input type="hidden" name="mode"          value="'.$mode.'" />
</tr></table>
</form>
</div>'."\n";
	}
	return $s;
}


// ----------------------
function referentiel_select_users_activite_accompagnes($userid=0, $select_acc=0, $mode='listactivity'){
global $cm;
global $course;

$s="";
  $s.='<div align="center">'."\n"; 
	$s.='<table class="selection">'."\n";
	$s.='<tr><td>';		
	// coaching
	$s.="\n".'<form name="form" method="post" action="activite.php?id='.$cm->id.'&amp;action=select_acc">'."\n";
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
function referentiel_select_users_activite_2($record_users, $userid=0, $select_acc=0, $mode='listactivity'){
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
			$s.="\n".'<form name="form" method="post" action="activite.php?id='.$cm->id.'&amp;action=selectuser">'."\n";
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
<!-- coaching -->
<input type="hidden" name="select_acc"        value="'.$select_acc.'" />
<!-- These hidden variables are always the same -->
<input type="hidden" name="course"        value="'.$course->id.'" />
<input type="hidden" name="sesskey"     value="'.sesskey().'" />
<input type="hidden" name="mode"          value="'.$mode.'" />
</form>'."\n";
			$s.='</td>';
		}
		
		if ($i<$n){
			$s.='<td>';
			$s.='<form name="form" method="post" action="activite.php?id='.$cm->id.'&amp;action=selectuser">'."\n";	
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


// Saisie et validation

// Affiche une entete activite
// *****************************************************************
// *
// output string                                                    *
// *****************************************************************
function referentiel_modifie_entete_activite_complete_filtre($appli, $data, $oklistesimple=false, $menu_a_droite=true){
// Affiche une entete activite complete
$s="";
$appli=$appli.'&amp;mode_select=selectetab';

	if ($oklistesimple){
		$width="10%";
	}
	else{
		$width="15%";
	}
	$s.='<table class="activite" width="100%">'."\n";
	$s.='<th width="2%">'.get_string('id','referentiel').'</th>';
	$s.='<th width="'.$width.'">'.get_string('auteur','referentiel');
	$s.="\n".'<form action="'.$appli.'" method="get" id="selectetab_filtre_auteur" class="popupform">'."\n";
	$s.=' <select id="selectetab_filtre_auteur" name="filtre_auteur" size="1" 
onchange="self.location=document.getElementById(\'selectetab_filtre_auteur\').filtre_auteur.options[document.getElementById(\'selectetab_filtre_auteur\').filtre_auteur.selectedIndex].value;">'."\n";
	if (isset($data) && !empty($data)){
		if ($data->filtre_auteur=='1'){
			$s.='	<option value="'.$appli.'&amp;filtre_auteur=0&amp;filtre_referent=0&amp;filtre_validation='.$data->filtre_validation.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'">'.get_string('choisir','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_auteur=1&amp;filtre_referent=0&amp;filtre_validation='.$data->filtre_validation.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'" selected="selected">'.get_string('croissant','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_auteur=-1&amp;filtre_referent=0&amp;filtre_validation='.$data->filtre_validation.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'">'.get_string('decroissant','referentiel').'</option>'."\n";
		}
		else if ($data->filtre_auteur=='-1'){
			$s.='	<option value="'.$appli.'&amp;filtre_auteur=0&amp;filtre_referent=0&amp;filtre_validation='.$data->filtre_validation.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'">'.get_string('choisir','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_auteur=1&amp;filtre_referent=0&amp;filtre_validation='.$data->filtre_validation.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'">'.get_string('croissant','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_auteur=-1&amp;filtre_referent=0&amp;filtre_validation='.$data->filtre_validation.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'" selected="selected">'.get_string('decroissant','referentiel').'</option>'."\n";
		}
		else{
			$s.='	<option value="'.$appli.'&amp;filtre_auteur=0&amp;filtre_referent=0&amp;filtre_validation='.$data->filtre_validation.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'" selected="selected">'.get_string('choisir','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_auteur=1&amp;filtre_referent=0&amp;filtre_validation='.$data->filtre_validation.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'">'.get_string('croissant','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_auteur=-1&amp;filtre_referent=0&amp;filtre_validation='.$data->filtre_validation.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'">'.get_string('decroissant','referentiel').'</option>'."\n";
		}
	}
	else{
		$s.='	<option value="'.$appli.'&amp;filtre_auteur=0&amp;filtre_referent=0&amp;filtre_validation=0&amp;filtre_date_modif_student=0&amp;filtre_date_modif=0" selected="selected">'.get_string('choisir','referentiel').'</option>'."\n";
		$s.='	<option value="'.$appli.'&amp;filtre_auteur=1&amp;filtre_referent=0&amp;filtre_validation=0&amp;filtre_date_modif_student=0&amp;filtre_date_modif=0">'.get_string('croissant','referentiel').'</option>'."\n";
		$s.='	<option value="'.$appli.'&amp;filtre_auteur=-1&amp;filtre_referent=0&amp;filtre_validation=0&amp;filtre_date_modif_student=0&amp;filtre_date_modif=0">'.get_string('decroissant','referentiel').'</option>'."\n";
	}
	$s.='</select>'."\n";
	$s.='
<script type="text/javascript">
//<![CDATA[
document.getElementById("noscriptnavmenupopup").style.display = "none";
//]]>
</script>'."\n".'</form>'."\n";
	$s.='</th>';
	
	$s.='<th width="5%">'.get_string('course').'</th>';
	$s.='<th width="'.$width.'">'.get_string('type','referentiel').'</th>';
	if ($oklistesimple){
		$s.='<th width="25%">'.get_string('liste_codes_competence','referentiel').'</th>';
	}
	$s.='<th width="'.$width.'">'.get_string('suivi','referentiel');
	$s.="\n".'<form action="'.$appli.'" method="get" id="selectetab_filtre_referent" class="popupform">'."\n";
	$s.=' <select id="selectetab_filtre_referent" name="filtre_referent" size="1" 
onchange="self.location=document.getElementById(\'selectetab_filtre_referent\').filtre_referent.options[document.getElementById(\'selectetab_filtre_referent\').filtre_referent.selectedIndex].value;">'."\n";
	if (isset($data) && !empty($data)){
		if ($data->filtre_referent=='1'){
			$s.='	<option value="'.$appli.'&amp;filtre_referent=0&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_validation='.$data->filtre_validation.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'">'.get_string('choisir','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_referent=1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_validation='.$data->filtre_validation.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'" selected="selected">'.get_string('examine','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_referent=-1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_validation='.$data->filtre_validation.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'">'.get_string('non_examine','referentiel').'</option>'."\n";
		}
		else if ($data->filtre_referent=='-1'){
			$s.='	<option value="'.$appli.'&amp;filtre_referent=0&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_validation='.$data->filtre_validation.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'">'.get_string('choisir','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_referent=1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_validation='.$data->filtre_validation.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'">'.get_string('examine','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_referent=-1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_validation='.$data->filtre_validation.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'" selected="selected">'.get_string('non_examine','referentiel').'</option>'."\n";
		}
		else{
			$s.='	<option value="'.$appli.'&amp;filtre_referent=0&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_validation='.$data->filtre_validation.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'" selected="selected">'.get_string('choisir','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_referent=1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_validation='.$data->filtre_validation.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'">'.get_string('examine','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_referent=-1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_validation='.$data->filtre_validation.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'">'.get_string('non_examine','referentiel').'</option>'."\n";
		}
	}
	else{
		$s.='	<option value="'.$appli.'&amp;filtre_referent=0&amp;&amp;filtre_auteur=0&amp;filtre_validation=0&amp;filtre_date_modif_student=0&amp;filtre_date_modif=0" selected="selected">'.get_string('choisir','referentiel').'</option>'."\n";
		$s.='	<option value="'.$appli.'&amp;filtre_referent=1&amp;&amp;filtre_auteur=0&amp;filtre_validation=0&amp;filtre_date_modif_student=0&amp;filtre_date_modif=0">'.get_string('examine','referentiel').'</option>'."\n";
		$s.='	<option value="'.$appli.'&amp;filtre_referent=-1&amp;filtre_auteur=0&amp;filtre_validation=0&amp;filtre_date_modif_student=0&amp;filtre_date_modif=0">'.get_string('non_examine','referentiel').'</option>'."\n";
	}
	$s.='</select>'."\n";
	$s.='
<script type="text/javascript">
//<![CDATA[
document.getElementById("noscriptnavmenupopup").style.display = "none";
//]]>
</script>'."\n".'</form>'."\n";
	$s.='</th>';
	
	$s.='<th width="'.$width.'">'.get_string('filtre_validation','referentiel');
	$s.="\n".'<form action="'.$appli.'" method="get" id="selectetab_filtre_validation" class="popupform">'."\n";
	$s.=' <select id="selectetab_filtre_validation" name="filtre_validation" size="1" 
onchange="self.location=document.getElementById(\'selectetab_filtre_validation\').filtre_validation.options[document.getElementById(\'selectetab_filtre_validation\').filtre_validation.selectedIndex].value;">'."\n";
	if (isset($data) && !empty($data)){
		if ($data->filtre_validation=='1'){
			$s.='	<option value="'.$appli.'&amp;filtre_validation=0&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'">'.get_string('choisir','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_validation=1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'" selected="selected">'.get_string('approved','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_validation=-1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'">'.get_string('not_approved','referentiel').'</option>'."\n";
		}
		else if ($data->filtre_validation=='-1'){
			$s.='	<option value="'.$appli.'&amp;filtre_validation=0&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_validation=0&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'">'.get_string('choisir','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_validation=1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'">'.get_string('approved','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_validation=-1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'" selected="selected">'.get_string('not_approved','referentiel').'</option>'."\n";
		}
		else{
			$s.='	<option value="'.$appli.'&amp;filtre_validation=0&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'" selected="selected">'.get_string('choisir','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_validation=1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'">'.get_string('approved','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_validation=-1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'">'.get_string('not_approved','referentiel').'</option>'."\n";
		}
	}
	else{
		$s.='	<option value="'.$appli.'&amp;filtre_validation=0&amp;filtre_auteur=0&amp;filtre_referent=0&amp;filtre_date_modif_student=0&amp;filtre_date_modif=0" selected="selected">'.get_string('choisir','referentiel').'</option>'."\n";
		$s.='	<option value="'.$appli.'&amp;filtre_validation=1&amp;filtre_auteur=0&amp;filtre_referent=0&amp;filtre_date_modif_student=0&amp;filtre_date_modif=0">'.get_string('approved','referentiel').'</option>'."\n";
		$s.='	<option value="'.$appli.'&amp;filtre_validation=-1&amp;filtre_auteur=0&amp;filtre_referent=O&amp;filtre_date_modif_student=0&amp;filtre_date_modif=0">'.get_string('not_approved','referentiel').'</option>'."\n";
	}
	
	$s.='</select>'."\n";
	$s.='
<script type="text/javascript">
//<![CDATA[
document.getElementById("noscriptnavmenupopup").style.display = "none";
//]]>
</script>'."\n".'</form>'."\n";
	$s.='</th>';
	
	$s.='<th width="'.$width.'">'.get_string('filtre_date_modif_student','referentiel');
	$s.="\n".'<form action="'.$appli.'" method="get" id="selectetab_filtre_date_modif_student" class="popupform">'."\n";		
	$s.=' <select id="selectetab_filtre_date_modif_student" name="filtre_date_modif_student" size="1" 
onchange="self.location=document.getElementById(\'selectetab_filtre_date_modif_student\').filtre_date_modif_student.options[document.getElementById(\'selectetab_filtre_date_modif_student\').filtre_date_modif_student.selectedIndex].value;">'."\n";
	if (isset($data) && !empty($data)){
		if ($data->filtre_date_modif_student=='1'){
			$s.='	<option value="'.$appli.'&amp;filtre_date_modif_student=0&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_validation='.$data->filtre_validation.'">'.get_string('choisir','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_date_modif_student=1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_validation='.$data->filtre_validation.'" selected="selected">'.get_string('croissant','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_date_modif_student=-1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_validation='.$data->filtre_validation.'">'.get_string('decroissant','referentiel').'</option>'."\n";
		}
		else if ($data->filtre_date_modif_student=='-1'){
			$s.='	<option value="'.$appli.'&amp;filtre_date_modif_student=0&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_validation='.$data->filtre_validation.'">'.get_string('choisir','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_date_modif_student=1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_validation='.$data->filtre_validation.'">'.get_string('croissant','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_date_modif_student=-1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_validation='.$data->filtre_validation.'" selected="selected">'.get_string('decroissant','referentiel').'</option>'."\n";
		}
		else{
			$s.='	<option value="'.$appli.'&amp;filtre_date_modif_student=0&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_validation='.$data->filtre_validation.'" selected="selected">'.get_string('choisir','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_date_modif_student=1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_validation='.$data->filtre_validation.'">'.get_string('croissant','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_date_modif_student=-1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_date_modif='.$data->filtre_date_modif.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_validation='.$data->filtre_validation.'">'.get_string('decroissant','referentiel').'</option>'."\n";
		}
	}
	else{
		$s.='	<option value="'.$appli.'&amp;filtre_date_modif_student=0&amp;filtre_auteur=0&amp;filtre_date_modif=0&amp;filtre_referent=0&amp;filtre_auteur=0&amp;filtre_validation=0" selected="selected">'.get_string('choisir','referentiel').'</option>'."\n";
		$s.='	<option value="'.$appli.'&amp;filtre_date_modif_student=1&amp;filtre_auteur=0&amp;filtre_date_modif=0&amp;filtre_referent=0&amp;filtre_auteur=0&amp;filtre_validation=0">'.get_string('croissant','referentiel').'</option>'."\n";
		$s.='	<option value="'.$appli.'&amp;filtre_date_modif_student=-1&amp;filtre_auteur=0&amp;filtre_date_modif=0&amp;filtre_referent=0&amp;filtre_auteur=0&amp;filtre_validation=0">'.get_string('decroissant','referentiel').'</option>'."\n";
	}
	$s.='</select>'."\n";
	$s.='
<script type="text/javascript">
//<![CDATA[
document.getElementById("noscriptnavmenupopup").style.display = "none";
//]]>
</script>'."\n".'</form>'."\n";
	$s.='</th>';	

	$s.='<th width="'.$width.'">'.get_string('filtre_date_modif','referentiel');
	$s.="\n".'<form action="'.$appli.'" method="get" id="selectetab_filtre_date_modif" class="popupform">'."\n";	
	$s.=' <select id="selectetab_filtre_date_modif" name="filtre_date_modif" size="1" 
onchange="self.location=document.getElementById(\'selectetab_filtre_date_modif\').filtre_date_modif.options[document.getElementById(\'selectetab_filtre_date_modif\').filtre_date_modif.selectedIndex].value;">'."\n";
	if (isset($data) && !empty($data)){
		if ($data->filtre_date_modif=='1'){
			$s.='	<option value="'.$appli.'&amp;filtre_date_modif=0&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_validation='.$data->filtre_validation.'">'.get_string('choisir','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_date_modif=1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_validation='.$data->filtre_validation.'" selected="selected">'.get_string('croissant','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_date_modif=-1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_validation='.$data->filtre_validation.'">'.get_string('decroissant','referentiel').'</option>'."\n";
		}
		else if ($data->filtre_date_modif=='-1'){
			$s.='	<option value="'.$appli.'&amp;filtre_date_modif=0&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_validation='.$data->filtre_validation.'">'.get_string('choisir','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_date_modif=1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_validation='.$data->filtre_validation.'1">'.get_string('croissant','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_date_modif=-1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_validation='.$data->filtre_validation.'" selected="selected">'.get_string('decroissant','referentiel').'</option>'."\n";
		}
		else{
			$s.='	<option value="'.$appli.'&amp;filtre_date_modif=0&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_validation='.$data->filtre_validation.'" selected="selected">'.get_string('choisir','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_date_modif=1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_validation='.$data->filtre_validation.'">'.get_string('croissant','referentiel').'</option>'."\n";
			$s.='	<option value="'.$appli.'&amp;filtre_date_modif=-1&amp;filtre_auteur='.$data->filtre_auteur.'&amp;filtre_date_modif_student='.$data->filtre_date_modif_student.'&amp;filtre_referent='.$data->filtre_referent.'&amp;filtre_validation='.$data->filtre_validation.'">'.get_string('decroissant','referentiel').'</option>'."\n";
		}
	}
	else{
		$s.='	<option value="'.$appli.'&amp;filtre_date_modif=0&amp;filtre_auteur=0&amp;filtre_date_modif_student=0&amp;filtre_referent=0&amp;filtre_auteur=0&amp;filtre_validation=0" selected="selected">'.get_string('choisir','referentiel').'</option>'."\n";
		$s.='	<option value="'.$appli.'&amp;filtre_date_modif=1&amp;filtre_auteur=0&amp;filtre_date_modif_student=0&amp;&amp;filtre_referent=0&amp;filtre_auteur=0&amp;filtre_validation=0">'.get_string('croissant','referentiel').'</option>'."\n";
		$s.='	<option value="'.$appli.'&amp;filtre_date_modif=-1&amp;filtre_auteur=0&amp;filtre_date_modif_student=0&amp;&amp;filtre_referent=0&amp;filtre_auteur=0&amp;filtre_validation=0">'.get_string('decroissant','referentiel').'</option>'."\n";
	}
	$s.='</select>'."\n";
	$s.='
<script type="text/javascript">
//<![CDATA[
document.getElementById("noscriptnavmenupopup").style.display = "none";
//]]>
</script>'."\n".'</form>'."\n";
	$s.='</th>';
	if ($menu_a_droite){
        // MENU affiché à droite
	   $s.='<th width="'.$width.'">'.get_string('menu','referentiel').'</th>'."\n";
    }
    $s.='</tr>'."\n";

	/*
	$s.='<tr>';
	$s.='<th colspan="3" rowspan="2">'.get_string('type_activite','referentiel').'<br />'.get_string('description','referentiel').'</th>';
	$s.='<th colspan="3">'.get_string('liste_codes_competence','referentiel').'<br />'.get_string('commentaire','referentiel').'</th>';
	$s.='</tr>'."\n";
	$s.='<tr>';
	$s.='<th colspan="3" class="yellow" align="center">'.get_string('document','referentiel').'</th>';
	$s.='</tr>'."\n";
	*/
	return $s;
}

function referentiel_modifie_enqueue_activite(){
// Affiche une enqueue activite
	$s='';
	$s.='</table>'."\n";
	return $s;
}


/**************************************************************************
 * takes a list of records, the current referentiel, an optionnal user id *
 * and mode to display                                                    *
 * input @param string  $mode                                             *
 *       @param object $referentiel_instance                              *
 *       @param int $userid_filtre                                        *
 *       @param array of objects $gusers of users get from current group  *
 *       @param string $sql_filtre_where, $sql_filtre_order               *
 * output null                                                            *
 **************************************************************************/
function referentiel_print_evalue_liste_activites($mode, $referentiel_instance, $userid_filtre=0, $gusers=NULL, $sql_filtre_where='', $sql_filtre_order='', $data_filtre, $select_acc=0) {
global $CFG;
global $USER;
static $istutor=false;
static $isteacher=false;
static $isauthor=false;
static $iseditor=false;
static $referentiel_id = NULL;

// A COMPLETER
$data=NULL;
	// contexte
    $cm = get_coursemodule_from_instance('referentiel', $referentiel_instance->id);
    $course = get_record('course', 'id', $cm->course);
	if (empty($cm) or empty($course)){
        print_error('REFERENTIEL_ERROR 5 :: print_lib_activite.php :: You cannot call this script in that way');
	}
	
  $context = get_context_instance(CONTEXT_MODULE, $cm->id);
	
	$records = array();
	$referentiel_id = $referentiel_instance->referentielid;
	$iseditor = has_capability('mod/referentiel:writereferentiel', $context);
	$isteacher = has_capability('mod/referentiel:approve', $context)&& !$iseditor;
	$istutor = has_capability('mod/referentiel:comment', $context) && !$iseditor  && !$isteacher;	
	$isauthor = has_capability('mod/referentiel:write', $context) && !$iseditor  && !$isteacher  && !$istutor;
	/*
	// DEBUG
	if ($isteacher) echo "Teacher ";
	if ($iseditor) echo "Editor ";
	if ($istutor) echo "Tutor ";
	if ($isauthor) echo "Author ";
	*/
	
	
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
	 	// preparer les variables globales pour Overlib
		// referentiel_initialise_data_referentiel($referentiel_referentiel->id);
		referentiel_initialise_descriptions_items_referentiel($referentiel_referentiel->id);

		// boite pour selectionner les utilisateurs ?
		if ($isteacher || $iseditor || $istutor){
			if (!empty($select_acc)){
			  // eleves accompagnes
                $record_id_users  = referentiel_get_accompagnements_teacher($referentiel_instance->id, $course->id, $USER->id);
            }
			else{
                // tous les users possibles (pour la boite de selection)
				// Get your userids the normal way
                $record_id_users  = referentiel_get_students_course($course->id,0,0);  //seulement les stagiaires
			}
            if ($gusers && $record_id_users){ // liste des utilisateurs du groupe courant
				// echo "<br />DEBUG :: print_lib_activite.php :: 740 :: GUSERS<br />\n";
				// print_object($gusers);
				// echo "<br />\n";
				// exit;
				$record_users  = array_intersect($gusers, array_keys($record_id_users));
				// echo "<br />DEBUG :: print_lib_activite.php :: 745 :: RECORD_USERS<br />\n";
				// print_r($record_users  );
				// echo "<br />\n";
				// recopier 
				$record_id_users=array();
				foreach ($record_users  as $record_id){
					$record_id_users[]->userid=$record_id;
				}
			}
			// Ajouter l'utilisateur courant pour qu'il voit ses activités
			$record_id_users[]->userid=$USER->id;
			
			// echo referentiel_select_users_activite($record_id_users, $userid_filtre, $mode, $select_acc);
			echo referentiel_select_users_activite_accompagnes($userid_filtre, $select_acc, $mode);
            echo referentiel_select_users_activite_2($record_id_users, $userid_filtre, $select_acc, $mode);
		}
		else{
			$userid_filtre=$USER->id; // les étudiants ne peuvent voir que leur fiche
		}
		// recuperer les utilisateurs filtres
			// $userid_filtre est l'id de l'utilisateur dont on affiche les activites
			// si $userid_filtre ==0 on retourne tous les utilisateurs du cours et du groupe
        if (!empty($userid_filtre)){
            $record_id_users = referentiel_get_students_course($course->id, $userid_filtre, 0);
        }
		else{
            if (!empty($select_acc)){
                // eleves accompagnes
                $record_id_users  = referentiel_get_accompagnements_teacher($referentiel_instance->id, $course->id, $USER->id);
            }
            else{
                $record_id_users = referentiel_get_students_course($course->id, $userid_filtre, 0);
			  // echo "<br />DEBUG :: print_lib_activite.php :: 1850 :: RECORD_USERS<br />\n";
			  // print_r($record_users  );
			  // echo "<br />\n";		
            }
        }
		
		if ($record_id_users && $gusers){ // liste des utilisateurs du groupe courant
			$record_users  = array_intersect($gusers, array_keys($record_id_users));
			// recopier 
			$record_id_users=array();
			foreach ($record_users  as $record_id){
				$record_id_users[]->userid=$record_id;
			}
		}
		
		if ((($userid_filtre==$USER->id) || ($userid_filtre==0)) && ($isteacher || $iseditor|| $istutor)){
			// Ajouter l'utilisateur courant pour qu'il puisse voir ses activites
			$record_id_users[]->userid=$USER->id;
		}

		// echo "<br />DEBUG :: print_lib_activite.php :: 1870 :: RECORD_USERS<br />\n";
		// print_r($record_users  );
		// echo "<br />\n";
		
		if ($record_id_users){
			// Afficher 		
			if (isset($mode) && (($mode=='updateactivity') || ($mode=='listactivityall') || ($mode=='listactivitysingle'))){
				if ($mode=='updateactivity')
                    echo referentiel_modifie_entete_activite_complete_filtre("activite.php?id=$cm->id&amp;select_acc=$select_acc&amp;course=$course->id&amp;userid=$userid_filtre&amp;mode=$mode&amp;sesskey=".sesskey(), $data_filtre, false, false);
                else
                    echo referentiel_modifie_entete_activite_complete_filtre("activite.php?id=$cm->id&amp;select_acc=$select_acc&amp;course=$course->id&amp;userid=$userid_filtre&amp;mode=$mode&amp;sesskey=".sesskey(), $data_filtre, false, true);
			}
			else{
				//echo referentiel_print_entete_activite();
				echo referentiel_modifie_entete_activite_complete_filtre("activite.php?id=$cm->id&amp;select_acc=$select_acc&amp;course=$course->id&amp;userid=$userid_filtre&amp;mode=$mode&amp;sesskey=".sesskey(), $data_filtre, true);
			}
			
			// ordre d'affichage utilisateurs
			if (isset($data_filtre) && isset($data_filtre->filtre_auteur) && ($data_filtre->filtre_auteur=='-1')){ 
				$deb=(-count($record_id_users))+1;
				$fin=1;
			}
			else{
				$deb=0;
				$fin=count($record_id_users);
			}
			
			// Parcours des utilisateurs
			for ($j=$deb; $j<$fin; $j++){
				$i=abs($j);
				// recupere les enregistrements
				// MODIF JF 23/10/2009
				if (isset($userid_filtre) && ($userid_filtre==$USER->id)){
					$actif=true; 
				}
				else if (isset($mode) && ($mode=='listactivityall')){
					$actif=false;
				}
				else{
					$actif=false;
					// 	$records=referentiel_get_all_activites_user_course($referentiel_instance->referentielid, $record_id->userid, $course->id);
				}

				// MODIF JF 2009/11/28
				// Si des activites existent affichage de la liste des competences declarees
				if (referentiel_user_activites($referentiel_instance->referentielid, $record_id_users[$i]->userid)){
					if (isset($mode) && (($mode=='updateactivity') || ($mode=='listactivityall') || ($mode=='listactivitysingle'))){
                        if ($mode=='updateactivity')
                            echo '<tr><td colspan="9" align="center">'.get_string('competences_declarees','referentiel', '<b>'.referentiel_get_user_info($record_id_users[$i]->userid).'</b>')."\n".referentiel_print_jauge_activite($record_id_users[$i]->userid, $referentiel_id).'</td></tr>'."\n";
                        else
                            echo '<tr><td colspan="10" align="center">'.get_string('competences_declarees','referentiel', '<b>'.referentiel_get_user_info($record_id_users[$i]->userid).'</b>')."\n".referentiel_print_jauge_activite($record_id_users[$i]->userid, $referentiel_id).'</td></tr>'."\n";
					}
					else{
						echo '<tr><td colspan="10" align="center">'.get_string('competences_declarees','referentiel', '<b>'.referentiel_get_user_info($record_id_users[$i]->userid).'</b>')."\n".referentiel_print_jauge_activite($record_id_users[$i]->userid, $referentiel_id).'</td></tr>'."\n";
					}
				}
				else{
					if (isset($mode) && ($mode=='listactivity')){
						echo '<tr><td class="zero" colspan="9" align="center">'.referentiel_print_aucune_activite_user($record_id_users[$i]->userid).'</td></tr>'."\n";
					}
				}
				
				
				// filtrage des activites demandees
				$records=referentiel_get_all_activites_user($referentiel_instance->referentielid, $record_id_users[$i]->userid, $sql_filtre_where, $sql_filtre_order);
				if ($records){
					/*
					// MODIF JF 2009/11/28
					// Liste des competences declarees
					if (isset($mode) && (($mode=='updateactivity') || ($mode=='listactivityall') || ($mode=='listactivitysingle'))){
				    	echo '<tr><td colspan="9" align="center">'.get_string('competences_declarees','referentiel', '<b>'.referentiel_get_user_info($record_id_users[$i]->userid).'</b>')."\n".referentiel_print_jauge_activite($record_id_users[$i]->userid, $referentiel_id).'</td></tr>'."\n";
					}
					else{
				    	echo '<tr><td colspan="10" align="center">'.get_string('competences_declarees','referentiel', '<b>'.referentiel_get_user_info($record_id_users[$i]->userid).'</b>')."\n".referentiel_print_jauge_activite($record_id_users[$i]->userid, $referentiel_id).'</td></tr>'."\n";
					}
					*/
					foreach ($records as $record) {   // afficher l'activite
						// Afficher 	
						if (isset($mode) && ($mode=='updateactivity')){
							echo referentiel_modifie_activite_complete($data_filtre,$mode, $cm, $course, $referentiel_instance, $record, $context, $actif, $select_acc);
						}
						else if (isset($mode) && ($mode=='listactivityall')){
							echo referentiel_print_activite_3_complete($data_filtre,$mode, $cm, $course, $referentiel_instance, $record, $context, $actif, $select_acc);
						}
						elseif (isset($mode) && ($mode=='listactivitysingle')){
						/*
							referentiel_print_activite_detail($record);
							referentiel_menu_activite_detail($context, $record->id, $referentiel_instance->id, $record->approved, $select_acc);
						*/
							echo referentiel_print_activite_3_complete($data_filtre,$mode, $cm, $course, $referentiel_instance, $record, $context, $actif, $select_acc);
						}
						else{
							echo referentiel_print_activite_2($record, $context, $actif, $select_acc);
						}
					}
				}
    		}
			
			if (isset($mode) && ($mode=='updateactivity')){
				// echo referentiel_modifie_activite_2_complete($record, $context, $actif);
				echo referentiel_modifie_enqueue_activite();
			}
			else{
				echo referentiel_print_enqueue_activite();
			}
		}
	}
	echo '<br /><br />'."\n";
	return true;
}





/**************************************************************************
 * takes a list of records, the current referentiel, an optionnal user id *
 * and mode to display                                                    *
 * input @param string  $mode                                             *
 *       @param object $referentiel_instance                              *
 *       @param int $userid_filtre                                        *
 *       @param array of objects $gusers of users get from current group  *
 *       @param string $sql_filtre_where, $sql_filtre_order               *
 * output null                                                            *
 **************************************************************************/
function referentiel_print_evalue_global_liste_activites($mode, $referentiel_instance, $userid_filtre=0, $gusers=NULL, $sql_filtre_where='', $sql_filtre_order='', $data_filtre, $select_acc=0) {
// idem  que referentiel_print_evalue_liste_activite() 
// mais  specialise modification
// form globale

global $CFG;
global $USER;
static $istutor=false;
static $isteacher=false;
static $isauthor=false;
static $iseditor=false;
static $referentiel_id = NULL;

    // A COMPLETER
    $data=NULL;
	// contexte
    $cm = get_coursemodule_from_instance('referentiel', $referentiel_instance->id);
    $course = get_record('course', 'id', $cm->course);
	if (empty($cm) or empty($course)){
        print_error('REFERENTIEL_ERROR 5 :: print_lib_activite.php :: You cannot call this script in that way');
	}
	
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
	
	$records = array();
	$referentiel_id = $referentiel_instance->referentielid;
	$iseditor = has_capability('mod/referentiel:writereferentiel', $context);
	$isteacher = has_capability('mod/referentiel:approve', $context)&& !$iseditor;
	$istutor = has_capability('mod/referentiel:comment', $context) && !$iseditor  && !$isteacher;	
	$isauthor = has_capability('mod/referentiel:write', $context) && !$iseditor  && !$isteacher  && !$istutor;
	/*
	// DEBUG
	if ($isteacher) echo "Teacher ";
	if ($iseditor) echo "Editor ";
	if ($istutor) echo "Tutor ";
	if ($isauthor) echo "Author ";
	*/
	
	
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
	 	// preparer les variables globales pour Overlib
		// referentiel_initialise_data_referentiel($referentiel_referentiel->id);
		referentiel_initialise_descriptions_items_referentiel($referentiel_referentiel->id);

		// boite pour selectionner les utilisateurs ?
		if ($isteacher || $iseditor || $istutor){
			if (!empty($select_acc)){
                // eleves accompagnes
                $record_id_users  = referentiel_get_accompagnements_teacher($referentiel_instance->id, $course->id, $USER->id);
            }
			else{
                // tous les users possibles (pour la boite de selection)
				// Get your userids the normal way
                $record_id_users  = referentiel_get_students_course($course->id,0,0);  //seulement les stagiaires
			}
            if ($gusers && $record_id_users){ // liste des utilisateurs du groupe courant
				// echo "<br />DEBUG :: print_lib_activite.php :: 740 :: GUSERS<br />\n";
				// print_object($gusers);
				// echo "<br />\n";
				// exit;
				$record_users  = array_intersect($gusers, array_keys($record_id_users));
				// echo "<br />DEBUG :: print_lib_activite.php :: 745 :: RECORD_USERS<br />\n";
				// print_r($record_users  );
				// echo "<br />\n";
				// recopier 
				$record_id_users=array();
				foreach ($record_users  as $record_id){
					$record_id_users[]->userid=$record_id;
				}
			}
			// Ajouter l'utilisateur courant pour qu'il voit ses activités
			$record_id_users[]->userid=$USER->id;
            echo referentiel_select_users_activite_accompagnes($userid_filtre, $select_acc, $mode);
            echo referentiel_select_users_activite_2($record_id_users, $userid_filtre, $select_acc, $mode);
		}
		else{
			$userid_filtre=$USER->id; // les étudiants ne peuvent voir que leur fiche
		}
		// recuperer les utilisateurs filtres
				
        if (!empty($select_acc) && ($userid_filtre == 0)){
            // eleves accompagnes
            $record_id_users  = referentiel_get_accompagnements_teacher($referentiel_instance->id, $course->id, $USER->id);
        }
        else{
            // retourne les students du cours ou userid_filtre si != 0
            $record_id_users = referentiel_get_students_course($course->id, $userid_filtre, 0);
        }
		
		// afficher le groupe courant
		if ($record_id_users && $gusers){ // liste des utilisateurs du groupe courant
			$record_users  = array_intersect($gusers, array_keys($record_id_users));
			// recopier 
			$record_id_users=array();
			foreach ($record_users  as $record_id){
				$record_id_users[]->userid=$record_id;
			}
		}
		
		if ((($userid_filtre==$USER->id) || ($userid_filtre==0)) && ($isteacher || $iseditor|| $istutor)){
			// Ajouter l'utilisateur courant pour qu'il puisse voir ses propres activites
			$record_id_users[]->userid=$USER->id;
		}

		// echo "<br />DEBUG :: print_lib_activite.php :: 1870 :: RECORD_USERS<br />\n";
		// print_r($record_users  );
		// echo "<br />\n";
		// afficher les activites
		if ($record_id_users){
			// Afficher 		
			if (isset($mode) && (($mode=='updateactivity') || ($mode=='listactivityall') || ($mode=='listactivitysingle'))){
                if ($mode=='updateactivity'){
                    echo referentiel_modifie_entete_activite_complete_filtre("activite.php?id=".$cm->id."&amp;course=".$course->id."&amp;userid=".$userid_filtre."&amp;select_acc=".$select_acc."&amp;mode=".$mode."&amp;sesskey=".sesskey(), $data_filtre, false, false);
                }
                else{
                    echo referentiel_modifie_entete_activite_complete_filtre("activite.php?id=".$cm->id."&amp;course=".$course->id."&amp;userid=".$userid_filtre."&amp;select_acc=".$select_acc."&amp;mode=".$mode."&amp;sesskey=".sesskey(), $data_filtre, false);
                }
			}
			else{
				//echo referentiel_print_entete_activite();
				echo referentiel_modifie_entete_activite_complete_filtre("activite.php?id=".$cm->id."&amp;course=".$course->id."&amp;userid=".$userid_filtre."&amp;select_acc=".$select_acc."&amp;mode=".$mode."&amp;sesskey=".sesskey(), $data_filtre, true);
			}
			
			// ordre d'affichage utilisateurs
			if (isset($data_filtre) && isset($data_filtre->filtre_auteur) && ($data_filtre->filtre_auteur=='-1')){ 
				$deb=(-count($record_id_users))+1;
				$fin=1;
			}
			else{
				$deb=0;
				$fin=count($record_id_users);
			}

			// formulaire global
			echo "\n\n".'<form name="form" id="form" action="activite.php?id='.$cm->id.'&amp;course='.$course->id.'&amp;mode='.$mode.'&amp;filtre_auteur='.$data_filtre->filtre_auteur.'&amp;filtre_validation='.$data_filtre->filtre_validation.'&amp;filtre_referent='.$data_filtre->filtre_referent.'&amp;filtre_date_modif='.$data_filtre->filtre_date_modif.'&amp;filtre_date_modif_student='.$data_filtre->filtre_date_modif_student.'&amp;select_acc='.$select_acc.'&amp;sesskey='.sesskey().'" method="post">'."\n";
    	
            echo  '<tr valign="top">
<td class="ardoise" colspan="9">
 <img class="selectallarrow" src="./pix/arrow_ltr_bas.png" width="38" height="22" alt="Pour la sélection :" />
 <i>'.get_string('cocher_enregistrer', 'referentiel').'</i>
<input type="submit" value="'.get_string("savechanges").'" />
<input type="reset" value="'.get_string("corriger", "referentiel").'" />
<input type="submit" name="cancel" value="'.get_string("quit", "referentiel").'" />
</td></tr>'."\n";


			// Parcours des utilisateurs
			
			
			for ($j=$deb; $j<$fin; $j++){
				$i=abs($j);
				// recupere les enregistrements
				// MODIF JF 23/10/2009
				if (isset($userid_filtre) && ($userid_filtre==$USER->id)){
					$actif=true; 
				}
				else if (isset($mode) && ($mode=='listactivityall')){
					$actif=false;
				}
				else{
					$actif=false;
					// 	$records=referentiel_get_all_activites_user_course($referentiel_instance->referentielid, $record_id->userid, $course->id);
				}

				// MODIF JF 2009/11/28
				// Si des activites existent affichage de la liste des competences declarees
				if (referentiel_user_activites($referentiel_instance->referentielid, $record_id_users[$i]->userid)){
					if (isset($mode) && (($mode=='updateactivity') || ($mode=='listactivityall') || ($mode=='listactivitysingle'))){
                        if ($mode=='updateactivity'){
                            echo '<tr valign="top"><td colspan="8" align="center">'.get_string('competences_declarees','referentiel', '<b>'.referentiel_get_user_info($record_id_users[$i]->userid).'</b>')."\n".referentiel_print_jauge_activite($record_id_users[$i]->userid, $referentiel_id).'</td></tr>'."\n";
                        }
                        else{
                            echo '<tr valign="top"><td colspan="9" align="center">'.get_string('competences_declarees','referentiel', '<b>'.referentiel_get_user_info($record_id_users[$i]->userid).'</b>')."\n".referentiel_print_jauge_activite($record_id_users[$i]->userid, $referentiel_id).'</td></tr>'."\n";
                        }
					}
					else{
						echo '<tr valign="top"><td colspan="10" align="center">'.get_string('competences_declarees','referentiel', '<b>'.referentiel_get_user_info($record_id_users[$i]->userid).'</b>')."\n".referentiel_print_jauge_activite($record_id_users[$i]->userid, $referentiel_id).'</td></tr>'."\n";
					}
				}
				else{
					if (isset($mode) && ($mode=='listactivity')){
						echo '<tr valign="top"><td class="zero" colspan="10" align="center">'.referentiel_print_aucune_activite_user($record_id_users[$i]->userid).'</td></tr>'."\n";
					}
				}
				
				
				// filtrage des activites demandees
				$records=referentiel_get_all_activites_user($referentiel_instance->referentielid, $record_id_users[$i]->userid, $sql_filtre_where, $sql_filtre_order);
				if ($records){

  					foreach ($records as $record) {   // afficher l'activite
	   					// Afficher 	
		  				if (isset($mode) && ($mode=='updateactivity')){
			   				echo referentiel_modifie_global_activite_complete($data_filtre,$mode, $cm, $course, $referentiel_instance, $record, $context, $actif);		   				
				  		}
					   	else if (isset($mode) && ($mode=='listactivityall')){
						  	echo referentiel_print_activite_3_complete($data_filtre,$mode, $cm, $course, $referentiel_instance, $record, $context, $actif, $select_acc);
						  }
						  elseif (isset($mode) && ($mode=='listactivitysingle')){
						    /*
							   referentiel_print_activite_detail($record);
							   referentiel_menu_activite_detail($context, $record->id, $referentiel_instance->id, $record->approved, $select_acc);
						    */
							 echo referentiel_print_activite_3_complete($data_filtre,$mode, $cm, $course, $referentiel_instance, $record, $context, $actif, $select_acc);
						  }
						  else{
							 echo referentiel_print_activite_2($record, $context, $actif, $select_acc);
						  }
					}

				}
    	}
    	
    	echo  '<tr valign="top">
<td class="ardoise" colspan="9">
 <img class="selectallarrow" src="./pix/arrow_ltr.png"
    width="38" height="22" alt="Pour la sélection :" />
<i>'.get_string('cocher_enregistrer', 'referentiel').'</i>      
<input type="hidden" name="action" value="modifier_activite_global" />
<!-- coaching -->
<input type="hidden" name="select_acc" value="'.$select_acc.'" />
<!-- These hidden variables are always the same -->
<input type="hidden" name="sesskey"     value="'.sesskey().'" />
<input type="hidden" name="modulename"    value="referentiel" />
<input type="hidden" name="mode"          value="'.$mode.'" />
<input type="submit" value="'.get_string("savechanges").'" />
<input type="reset" value="'.get_string("corriger", "referentiel").'" />
<input type="submit" name="cancel" value="'.get_string("quit", "referentiel").'" />
</td></tr>
</form>'."\n";



			// liste des utilisateur achevee
			
			
			if (isset($mode) && ($mode=='updateactivity')){
				// echo referentiel_modifie_activite_2_complete($record, $context, $actif);
				echo referentiel_modifie_enqueue_activite();
			}
			else{
				echo referentiel_print_enqueue_activite();
			}
		}
	}
	echo '<br /><br />'."\n";
	return true;
}



// ----------------------------------------------------
function referentiel_modifie_activite_complete($data_filtre,$mode, $cm, $course, $referentiel_instance, $record, $context, $actif=true){
// $actif = true : le menu est active, sinon il ne l'est pas
//	Saisie et validation
// $data_filtre : parametres de filtrage
// $mode : mode d'affichage
// $cm : course_module
// $course : enregistrement cours
// referentiel_instance : enregistrement instance
// record : enregistrement activite
// $context : contexte roles et capacites
// $actif : affichage menu
global $USER;
global $CFG;
global $COURSE;
	$s='';
	$s_menu='';
	$s_document='';
	$s_out='';
	
	// Charger les activites
	// filtres	
	$isteacher = has_capability('mod/referentiel:approve', $context);
	$isauthor = has_capability('mod/referentiel:write', $context) && !$isteacher;
	$iseditor = has_capability('mod/referentiel:writereferentiel', $context);	

	if ($record){
		$activite_id=$record->id;
		$type_activite = stripslashes($record->type);
		$description = stripslashes(strip_tags($record->description));
		$comptencies = stripslashes(strip_tags($record->comptencies));
		$comment = stripslashes(strip_tags($record->comment));
		$instanceid = $record->instanceid;

		$referentielid = $record->referentielid;
		// liste des codes pour ce référentiel
		$liste_codes_competence = referentiel_get_liste_codes_competence($referentielid);	

		$course = $record->course;

		$userid = $record->userid;
		$teacherid = $record->teacherid;
		if ($teacherid==0){
			if ($isteacher || $iseditor){ 
				$teacherid=$USER->id;
			}
		} 

		$timecreated = $record->timecreated;
		$timemodified = $record->timemodified;
		$approved = $record->approved;
		$taskid = $record->taskid;
		if ($taskid>0){ // remplacer par la liste definie dans la tache
			$liste_codes_competences_tache=  referentiel_get_liste_codes_competence_tache($taskid);
			// DEBUG
			// echo "<br/>DEBUG ::<br />\n";
			// echo $liste_codes_competences_tache;				
		} 
		else{
			$liste_codes_competences_tache=$liste_codes_competence;
		}
		// DEBUG
		// echo "<br/>DEBUG ::<br />\n";
		// print_object($record);
		
		$user_info=referentiel_get_user_info($userid);
		$teacher_info=referentiel_get_user_info($teacherid);
		// dates
		$date_creation_info=userdate($timecreated);
		
		if ($timemodified!=0){
			$date_modif_info=userdate($timemodified);
		}
		else{
			$date_modif_info='';
		}

		// MODIF JF 2009/10/27
		$timemodifiedstudent = $record->timemodifiedstudent;
		if ($timemodifiedstudent==0){
			$timemodifiedstudent=$timecreated;
		}
		if ($timemodifiedstudent!=0){
			$date_modif_student_info=userdate($timemodifiedstudent);
		}
		else{
			$date_modif_student_info='';
		}
		
		
		// MODIF JF 2009/10/21						
		$old_liste_competences=stripslashes($record->comptencies);		
		
		// MODIF JF 2009/10/23
		$url_course=referentiel_get_course_link($course);

		// MODIF JF 2009/11/08
		// afficher le menu si l'activité est affiche dans son propre cours de création 
		$menu_actif = $actif || ($course == $COURSE->id);

		if ($menu_actif){ 
			// $s_menu.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=listactivityall&amp;sesskey='.sesskey().'#activite"><img src="pix/search.gif" alt="'.get_string('plus', 'referentiel').'" title="'.get_string('plus', 'referentiel').'" /></a>'."\n";
			$s_menu.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=listactivity&amp;sesskey='.sesskey().'#activite"><img src="pix/nosearch.gif" alt="'.get_string('moins', 'referentiel').'" title="'.get_string('moins', 'referentiel').'" /></a>'."\n";						
			$has_capability=has_capability('mod/referentiel:approve', $context);
			$is_owner=referentiel_activite_isowner($activite_id);
			
			if ($has_capability	or $is_owner){
				if ($has_capability || ($is_owner && !$approved)) {
	        		$s_menu.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=updateactivity&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/edit.gif" alt="'.get_string('edit').'" title="'.get_string('edit').'" /></a>'."\n";
				}
				if ($has_capability || $is_owner) {
			    	$s_menu.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=deleteactivity&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/delete.gif" alt="'.get_string('delete').'" title="'.get_string('delete').'" /></a>'."\n";
    			}
			}
			// valider
		    if ($has_capability){
				if (!$approved){
					$s_menu.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=approveactivity&amp;sesskey='.sesskey().'"><img src="pix/nonvalide.gif" alt="'.get_string('approve', 'referentiel').'" title="'.get_string('approve', 'referentiel').'" /></a>'."\n";
				}
				else{
    				$s_menu.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=desapproveactivity&amp;sesskey='.sesskey().'"><img src="pix/valide.gif" alt="'.get_string('desapprove', 'referentiel').'" title="'.get_string('desapprove', 'referentiel').'" /></a>'."\n";
				}
			}
			// commentaires
    		if (has_capability('mod/referentiel:comment', $context)){
    			$s_menu.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=commentactivity&amp;sesskey='.sesskey().'"><img src="pix/feedback.gif" alt="'.get_string('more', 'referentiel').'" title="'.get_string('comment', 'referentiel').'" /></a>'."\n";
			}
		}
		else{
			$s_menu.='&nbsp; '.get_string('activite_exterieure', 'referentiel');
		}

		// DOCUMENTS
		// charger les documents associes à l'activite courante
		$compteur_document=0;
		$s_document='';
		if (isset($activite_id) && ($activite_id>0)){
			$activityid=$activite_id; // plus pratique
			// AFFICHER LA LISTE DES DOCUMENTS
			$records_document = referentiel_get_documents($activityid);
	    	if ($records_document){
    			// afficher
				// DEBUG
				// echo "<br/>DEBUG <br />\n";
				// print_r($records_document);
				foreach ($records_document as $record_d){
					$compteur_document++;
        			$document_id=$record_d->id;
					$type = stripslashes($record_d->type);
					$description = stripslashes($record_d->description);
					$url = $record_d->url;
					$activityid = $record_d->activityid;
					if (isset($record_d->target) && ($record_d->target==1)){
						$target='_blank'; // fenêtre cible
					}
					else{
						$target='';
					}
					if (isset($record_d->label)){
						$label=$record_d->label; // fenêtre cible
					}
					else{
						$label='';
					}
					$s_document.=get_string('document', 'referentiel').' &nbsp; &nbsp; <i>'.$document_id.'</i> &nbsp; &nbsp; '.$type.' &nbsp; &nbsp; ';
					$s_document.=nl2br($description).' &nbsp; &nbsp; ';
					$s_document.=referentiel_affiche_url($url, $label, $target).'<br />'."\n";
				}
			}
		}
		
		// AFFICHAGE
		if ($course == $COURSE->id){
			echo "\n".'<form action="activite.php?id='.$cm->id.'&amp;course='.$course->id.'&amp;mode='.$mode.'&amp;filtre_auteur='.$data_filtre->filtre_auteur.'&amp;filtre_validation='.$data_filtre->filtre_validation.'&amp;filtre_referent='.$data_filtre->filtre_referent.'&amp;filtre_date_modif='.$data_filtre->filtre_date_modif.'&amp;filtre_date_modif_student='.$data_filtre->filtre_date_modif_student.'&amp;sesskey='.sesskey().'" method="post">'."\n";
		}
		echo '<tr valign="top">';
		if (isset($approved) && ($approved)){
			echo '<td class="valide" rowspan="3">';
		}
		else{
			echo '<td class="invalide" rowspan="3">';
		}
		echo  $activite_id;
		echo '</td><td align="center">';
		echo $user_info;
		echo '</td><td align="center">';
		echo $url_course;
		echo '</td><td align="center">';
		if ($course == $COURSE->id){
			echo '<input type="text" name="type_activite" size="40" maxlength="80" value="'.$type_activite.'" />'."\n";
		}
		else{
			echo $type_activite;
		}
		echo '</td><td align="center">';
		echo $teacher_info;
		echo '</td><td align="center">';
		
		if (($course == $COURSE->id) && (has_capability('mod/referentiel:approve', $context))){
			echo '<b>'.get_string('validation','referentiel').'</b> : ';
			if (isset($approved) && ($approved)){
				echo  '<input type="radio" name="approved"  id="approved" value="1" checked="checked" />'.get_string('yes').' &nbsp; <input type="radio" name="approved"  id="approved" value="0" />'.get_string('no').' &nbsp; &nbsp; '."\n";
			}
			else{
				echo '<input type="radio" name="approved"  id="approved" value="1" />'.get_string('yes').' &nbsp; <input type="radio" name="approved"  id="approved" value="0" checked="checked" />'.get_string('no').' &nbsp; &nbsp; '."\n";				
			}	
		}
		else{
			if (isset($approved) && ($approved)){
				echo get_string('approved','referentiel');
			}
			else{
				echo get_string('not_approved','referentiel');
			}	
			if ($course == $COURSE->id){
				echo  '<input type="hidden" name="approved" value="'.$approved.'" />'."\n";			
			}
		}
		
		echo '</td>';
		echo '<td align="center">';
		echo '<span class="small">'.$date_modif_student_info.'</span>';
		echo '</td>';
		echo '<td align="center">';
		echo '<span class="small">'.$date_modif_info.'</span>';
		echo '</td>'."\n";
		// menu
		// echo '<td align="center" rowspan="3">'."\n";
		// echo $s_menu;
		// echo '</td>';
		echo '</tr>'."\n";
		echo '<tr valign="top">';
		if (isset($approved) && ($approved)){
			echo '<td  colspan="4" class="valide">';
		}
		else{
			echo '<td colspan="4" class="invalide">';
		}
		if ($course == $COURSE->id){
			if ($taskid==0) { // activite normale
				referentiel_modifier_selection_liste_codes_item_competence('/', $liste_codes_competence, $comptencies, false);
			}
			else{ // activite issue d'une tache
				referentiel_modifier_selection_liste_codes_item_competence('/', $liste_codes_competences_tache, $comptencies, true );
				echo '<input type="hidden" name="comptencies" value="'.$comptencies.'" />'."\n"; 
			}
		}
		else{
			// INUTILE referentiel_initialise_descriptions_items_referentiel($referentielid);
			echo referentiel_affiche_overlib_item('/',$comptencies);
		}
		if (($course == $COURSE->id) && (has_capability('mod/referentiel:comment', $context))){
			echo '<textarea cols="80" rows="7" name="description">'.$description.'</textarea>'."\n";
		}
		else {
			echo nl2br($description);
		}

		echo '</td>';
		if (isset($approved) && ($approved)){
			echo '<td class="valide"  colspan="3">';
		}
		else{
			echo '<td class="invalide" colspan="3">';
		}
		
		if ($course == $COURSE->id){
			echo '<b>'.get_string('commentaire','referentiel').'</b><br />'."\n";			
			echo '<textarea cols="40" rows="7" name="comment">'.$comment.'</textarea>'."\n";
		}
		else{
			echo nl2br($comment);
			if ($course == $COURSE->id) {
				echo '<input type="hidden" name="comment" value="'.$comment.'" />'."\n";
			}
		}
		
		echo '</td>';

		echo '</tr>'."\n";
		echo '<tr valign="top">'."\n";
		echo '<td class="yellow" colspan="7" align="center">'."\n";
		if ($s_document!=''){
			echo $s_document;
		}
		else{
			echo '&nbsp;';
		}
		echo '</td></tr>'."\n";
		
		if ($course == $COURSE->id){
			echo  '<tr valign="top"><td colspan="9" align="center">
<input type="hidden" name="timecreated" value="'.$timecreated.'" />
<input type="hidden" name="timemodified" value="'.$timemodified.'" />
<input type="hidden" name="timemodifiedstudent" value="'.$timemodifiedstudent.'" />
<input type="hidden" name="old_liste_competences" value="'.$old_liste_competences.'" />
<input type="hidden" name="userid" value="'.$userid.'" />
<input type="hidden" name="teacherid" value="'.$teacherid.'" />
<input type="hidden" name="activite_id" value="'.$activite_id.'" />
<input type="hidden" name="referentielid" value="'.$referentielid.'" />
<input type="hidden" name="course" value="'.$course.'" />
<input type="hidden" name="instanceid" value="'.$instanceid.'" />
<input type="hidden" name="action" value="modifier_activite" />
<!-- These hidden variables are always the same -->
<input type="hidden" name="course"        value="'.$course->id.'" />
<input type="hidden" name="sesskey"     value="'.sesskey().'" />
<input type="hidden" name="modulename"    value="referentiel" />
<input type="hidden" name="instance"      value="'.$referentiel_instance->id.'" />
<input type="hidden" name="mode"          value="'.$mode.'" />
<input type="submit" value="'.get_string("savechanges").'" />
<input type="reset" value="'.get_string("corriger", "referentiel").'" />
<!-- input type="submit" name="cancel" value="'.get_string("quit", "referentiel").'" / -->
</td></tr>
</form>'."\n";
		}
	}
	return $s;
}



// ----------------------------------------------------
function referentiel_modifie_global_activite_complete($data_filtre,$mode, $cm, $course, $referentiel_instance, $record, $context, $actif=true){
//	Saisie et validation globale
// idem que referentiel_modifie_globale_activite_complete() sauf que le formulaire est globale
// $actif = true : le menu est active, sinon il ne l'est pas
// $data_filtre : parametres de filtrage
// $mode : mode d'affichage
// $cm : course_module
// $course : enregistrement cours
// referentiel_instance : enregistrement instance
// record : enregistrement activite
// $context : contexte roles et capacites
// $actif : affichage menu
global $USER;
global $CFG;
global $COURSE;
	$s='';
	$s_menu='';
	$s_document='';
	$s_out='';
	
	// Charger les activites
	// filtres	
	$isteacher = has_capability('mod/referentiel:approve', $context);
	$isauthor = has_capability('mod/referentiel:write', $context) && !$isteacher;
	$iseditor = has_capability('mod/referentiel:writereferentiel', $context);	

	if ($record){
		$activite_id=$record->id;
		$type_activite = stripslashes($record->type);
		$description = stripslashes(strip_tags($record->description));
		$comptencies = stripslashes(strip_tags($record->comptencies));
		$comment = stripslashes(strip_tags($record->comment));
		$instanceid = $record->instanceid;

		$referentielid = $record->referentielid;
		// liste des codes pur ce référentiel
		$liste_codes_competence = referentiel_get_liste_codes_competence($referentielid);	

		$course = $record->course;

		$userid = $record->userid;
		$teacherid = $record->teacherid;
		if ($teacherid==0){
			if ($isteacher || $iseditor){ 
				$teacherid=$USER->id;
			}
		} 

		$timecreated = $record->timecreated;
		$timemodified = $record->timemodified;
		$approved = $record->approved;
		$taskid = $record->taskid;
		if ($taskid>0){ // remplacer par la liste definie dans la tache
			$liste_codes_competences_tache = referentiel_get_liste_codes_competence_tache($taskid);
			// DEBUG
			// echo "<br/>DEBUG ::<br />\n";
			// echo $liste_codes_competences_tache;				
		} 
		else{
			$liste_codes_competences_tache=$liste_codes_competence;
		}
		// DEBUG
		// echo "<br/>DEBUG ::<br />\n";
		// print_object($record);
		
		$user_info=referentiel_get_user_info($userid);
		$teacher_info=referentiel_get_user_info($teacherid);
		// dates
		$date_creation_info=userdate($timecreated);
		
		if ($timemodified!=0){
			$date_modif_info=userdate($timemodified);
		}
		else{
			$date_modif_info='';
		}

		// MODIF JF 2009/10/27
		$timemodifiedstudent = $record->timemodifiedstudent;
		if ($timemodifiedstudent==0){
			$timemodifiedstudent=$timecreated;
		}
		if ($timemodifiedstudent!=0){
			$date_modif_student_info=userdate($timemodifiedstudent);
		}
		else{
			$date_modif_student_info='';
		}
		
		
		// MODIF JF 2009/10/21						
		$old_liste_competences=stripslashes($record->comptencies);		
		
		// MODIF JF 2009/10/23
		$url_course=referentiel_get_course_link($course);

		// MODIF JF 2009/11/08
		// afficher le menu si l'activité est affiche dans son propre cours de création 
		$menu_actif = $actif || ($course == $COURSE->id);

		if ($menu_actif){ 
   //             $s_menu.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=listactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'#activite"><img src="pix/nosearch.gif" alt="'.get_string('moins', 'referentiel').'" title="'.get_string('moins', 'referentiel').'" /></a>';
			$has_capability=has_capability('mod/referentiel:approve', $context);
			$is_owner=referentiel_activite_isowner($activite_id);
			
			if ($has_capability	or $is_owner){
				if ($has_capability || ($is_owner && !$approved)) {
	        		$s_menu.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=updateactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/edit.gif" alt="'.get_string('edit').'" title="'.get_string('edit').'" /></a>'."\n";
				}
				if ($has_capability || $is_owner) {
			    	$s_menu.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=deleteactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/delete.gif" alt="'.get_string('delete').'" title="'.get_string('delete').'" /></a>'."\n";
    			}
			}
			// valider
/*
		    if ($has_capability){
				if (!$approved){
					$s_menu.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=approveactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="pix/nonvalide.gif" alt="'.get_string('approve', 'referentiel').'" title="'.get_string('approve', 'referentiel').'" /></a>'."\n";
				}
				else{
    				$s_menu.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=desapproveactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="pix/valide.gif" alt="'.get_string('desapprove', 'referentiel').'" title="'.get_string('desapprove', 'referentiel').'" /></a>'."\n";
				}
			}
			// commentaires
    		if (has_capability('mod/referentiel:comment', $context)){
    			$s_menu.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=commentactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="pix/feedback.gif" alt="'.get_string('more', 'referentiel').'" title="'.get_string('comment', 'referentiel').'" /></a>'."\n";
			}
*/
		}
		else{
			$s_menu.='&nbsp; '.get_string('activite_exterieure', 'referentiel');
		}

		// DOCUMENTS
		// charger les documents associes à l'activite courante
		$compteur_document=0;
		$s_document='';
		if (isset($activite_id) && ($activite_id>0)){
			$activityid=$activite_id; // plus pratique
			// AFFICHER LA LISTE DES DOCUMENTS
			$records_document = referentiel_get_documents($activityid);
	    	if ($records_document){
    			// afficher
				// DEBUG
				// echo "<br/>DEBUG <br />\n";
				// print_r($records_document);
				foreach ($records_document as $record_d){
					$compteur_document++;
        			$document_id=$record_d->id;
					$type = stripslashes($record_d->type);
					$description = stripslashes($record_d->description);
					$url = $record_d->url;
					$activityid = $record_d->activityid;
					if (isset($record_d->target) && ($record_d->target==1)){
						$target='_blank'; // fenêtre cible
					}
					else{
						$target='';
					}
					if (isset($record_d->label)){
						$label=$record_d->label; // fenêtre cible
					}
					else{
						$label='';
					}
					$s_document.=get_string('document', 'referentiel').' &nbsp; &nbsp; <i>'.$document_id.'</i> &nbsp; &nbsp; '.$type.' &nbsp; &nbsp; ';
					$s_document.=nl2br($description).' &nbsp; &nbsp; ';
					$s_document.=referentiel_affiche_url($url, $label, $target).'<br />'."\n";
				}
			}
		}
		
		// AFFICHAGE
		/*
    if ($course == $COURSE->id){
			echo "\n".'<form action="activite.php?id='.$cm->id.'&amp;course='.$course->id.'&amp;mode='.$mode.'&amp;filtre_auteur='.$data_filtre->filtre_auteur.'&amp;filtre_validation='.$data_filtre->filtre_validation.'&amp;filtre_referent='.$data_filtre->filtre_referent.'&amp;filtre_date_modif='.$data_filtre->filtre_date_modif.'&amp;filtre_date_modif_student='.$data_filtre->filtre_date_modif_student.'&amp;sesskey='.sesskey().'" method="post">'."\n";
		}
		*/
    echo '<tr valign="top">';
		if (isset($approved) && ($approved)){
			echo '<td class="valide" rowspan="3">';
		}
		else{
			echo '<td class="invalide" rowspan="3">';
		}
		
    // selection de l'activite
		/*
		if ($course == $COURSE->id){
		  if (isset($approved) && ($approved)){
        echo  '<input type="checkbox" name="tactivite_id[]" id="tactivite_id_'.$activite_id.'" value="'.$activite_id.'" />';
		  }
		  else{
        echo  '<input type="checkbox" name="tactivite_id[]" id="tactivite_id_'.$activite_id.'" value="'.$activite_id.'" checked="checked" />';      
      }
    }
    */
    if ($course == $COURSE->id){
      echo  '<input type="checkbox" name="tactivite_id[]" id="tactivite_id_'.$activite_id.'" value="'.$activite_id.'" />';      
    }
    echo  $activite_id;
		// menu
		echo '<br>'."\n";
		echo $s_menu;

		echo '</td>'."\n".'<td align="center">';
		echo $user_info;
		echo '</td>'."\n".'<td align="center">';
		echo $url_course;
		echo '</td>'."\n".'<td align="center">';
		if ($course == $COURSE->id){
			echo '<input type="text" name="type_activite_'.$activite_id.'" size="40" maxlength="80" value="'.$type_activite.'" onchange="return validerCheckBox(\'tactivite_id_'.$activite_id.'\')"  />'."\n";
		}
		else{
			echo $type_activite;
		}
		echo '</td>'."\n".'<td align="center">';
		echo $teacher_info;
		echo '</td>'."\n".'<td align="center">';
		
		if (($course == $COURSE->id) && (has_capability('mod/referentiel:approve', $context))){
			echo '<b>'.get_string('validation','referentiel').'</b> : ';
			if (isset($approved) && ($approved)){
				echo  '<input type="radio" name="approved_'.$activite_id.'"  id="approved" value="1" checked="checked" onchange="return validerCheckBox(\'tactivite_id_'.$activite_id.'\')" />'.get_string('yes').' &nbsp; <input type="radio" name="approved_'.$activite_id.'" id="approved" value="0"  onchange="return validerCheckBox(\'tactivite_id_'.$activite_id.'\')" />'.get_string('no').' &nbsp; &nbsp; '."\n";
			}
			else{
				echo '<input type="radio" name="approved_'.$activite_id.'"  id="approved" value="1" onchange="return validerCheckBox(\'tactivite_id_'.$activite_id.'\')" />'.get_string('yes').' &nbsp; <input type="radio" name="approved_'.$activite_id.'"  id="approved" value="0" checked="checked"  onchange="return validerCheckBox(\'tactivite_id_'.$activite_id.'\')" />'.get_string('no').' &nbsp; &nbsp; '."\n";				
			}	
		}
		else{
			if (isset($approved) && ($approved)){
				echo get_string('approved','referentiel');
			}
			else{
				echo get_string('not_approved','referentiel');
			}	
			if ($course == $COURSE->id){
				echo  '<input type="hidden" name="approved_'.$activite_id.'" value="'.$approved.'" />'."\n";			
			}
		}
		
		echo '</td>';
		echo '<td align="center">';
		echo '<span class="small">'.$date_modif_student_info.'</span>';
		echo '</td>';
		echo '<td align="center">';
		echo '<span class="small">'.$date_modif_info.'</span>';
		echo '</td>'."\n";
		// menu
		// echo '<td align="center" rowspan="3">'."\n";
		// echo $s_menu;
		// echo '</td>';
		echo '</tr>'."\n";
		echo '<tr valign="top">';
		if (isset($approved) && ($approved)){
			echo '<td  colspan="4" class="valide">';
		}
		else{
			echo '<td colspan="4" class="invalide">';
		}
		if ($course == $COURSE->id){
			if ($taskid==0) { // activite normale
				referentiel_modifier_selection_liste_codes_item_competence('/', $liste_codes_competence, $comptencies, false,$activite_id, 'onchange="return validerCheckBox(\'tactivite_id_'.$activite_id.'\')" ' );
			}
			else{ // activite issue d'une tache
				referentiel_modifier_selection_liste_codes_item_competence('/', $liste_codes_competences_tache, $comptencies, true, $activite_id, 'onchange="return validerCheckBox(\'tactivite_id_'.$activite_id.'\')" ');
				echo '<input type="hidden" name="comptencies" value="'.$comptencies.'" />'."\n"; 
			}
		}
		else{
			// INUTILE referentiel_initialise_descriptions_items_referentiel($referentielid);
			echo referentiel_affiche_overlib_item('/',$comptencies);
		}
		if (($course == $COURSE->id) && (has_capability('mod/referentiel:comment', $context))){
			echo '<textarea cols="80" rows="7" name="description_activite_'.$activite_id.'" onchange="return validerCheckBox(\'tactivite_id_'.$activite_id.'\') ">'.$description.'</textarea>'."\n";
		}
		else {
			echo nl2br($description);
		}

		echo '</td>';
		/*
		if (isset($approved) && ($approved)){
			echo '<td class="valide"  colspan="3">';
		}
		else{
			echo '<td class="invalide" colspan="3">';
		}
		*/
        echo '<td class="ardoise" colspan="3">';
		if ($course == $COURSE->id){
			echo '<b>'.get_string('commentaire','referentiel').'</b><br />'."\n";			
			echo '<textarea cols="40" rows="7" name="commentaire_activite_'.$activite_id.'"  onchange="return validerCheckBox(\'tactivite_id_'.$activite_id.'\')" >'.$comment.'</textarea>'."\n";
		}
		else{
			echo nl2br($comment);
			if ($course == $COURSE->id) {
				echo '<input type="hidden" name="commentaire_activite_'.$activite_id.'" value="'.$comment.'" />'."\n";
			}
		}
		// MODIF 10/2/2010
		
		echo '<br />'.get_string('notification_activite','referentiel').'<input type="radio" name="mailnow_'.$activite_id.'" value="1" onchange="return validerCheckBox(\'tactivite_id_'.$activite_id.'\')" />'.get_string('yes').' &nbsp; <input type="radio" name="mailnow_'.$activite_id.'" value="0" checked="checked" onchange="return validerCheckBox(\'tactivite_id_'.$activite_id.'\')" />'.get_string('no').' &nbsp; &nbsp; '."\n";

		echo '</td>';

		echo '</tr>'."\n";
		echo '<tr valign="top">'."\n";
		echo '<td class="yellow" colspan="7" align="center">'."\n";
		if ($s_document!=''){
			echo $s_document;
		}
		else{
			echo '&nbsp;';
		}
		echo '</td></tr>'."\n";
		if ($course == $COURSE->id){
			echo  '
<input type="hidden" name="date_creation_'.$activite_id.'" value="'.$timecreated.'" />
<input type="hidden" name="date_modif_'.$activite_id.'" value="'.$timemodified.'" />
<input type="hidden" name="date_modif_student_'.$activite_id.'" value="'.$timemodifiedstudent.'" />
<input type="hidden" name="old_liste_competences_'.$activite_id.'" value="'.$old_liste_competences.'" />
<input type="hidden" name="userid_'.$activite_id.'" value="'.$userid.'" />
<input type="hidden" name="teacherid_'.$activite_id.'" value="'.$teacherid.'" />
<input type="hidden" name="activite_id_'.$activite_id.'" value="'.$activite_id.'" />
<input type="hidden" name="ref_referentiel_'.$activite_id.'" value="'.$referentielid.'" />
<input type="hidden" name="ref_course_'.$activite_id.'" value="'.$course.'" />
<input type="hidden" name="ref_instance_'.$activite_id.'" value="'.$instanceid.'" />'."\n\n";
		}

	}
	return $s;
}




// ----------------------------------------------------
function referentiel_print_activite_3_complete($data_filtre,$mode, $cm, $course, $referentiel_instance, $record, $context, $actif=true, $select_acc=0){
// $actif = true : le menu est active, sinon il ne l'est pas
// Saisie et validation
// $data_filtre : parametres de filtrage
// $mode : mode d'affichage
// $cm : course_module
// $course : enregistrement cours
// referentiel_instance : enregistrement instance
// record : enregistrement activite
// $context : contexte roles et capacites
// $actif : affichage menu
global $USER;
global $CFG;
global $COURSE;
	$s='';
	$s_menu='';
	$s_document='';
	$s_out='';
	
	// Charger les activites
	// filtres	
	$isteacher = has_capability('mod/referentiel:approve', $context);
	$isauthor = has_capability('mod/referentiel:write', $context) && !$isteacher;
	$iseditor = has_capability('mod/referentiel:writereferentiel', $context);	

	if ($record){
		$activite_id=$record->id;
		$type_activite = stripslashes($record->type);
		$description = stripslashes(strip_tags($record->description));
		$comptencies = stripslashes(strip_tags($record->comptencies));
		$comment = stripslashes(strip_tags($record->comment));
		$instanceid = $record->instanceid;

		$referentielid = $record->referentielid;
		// liste des codes pur ce référentiel
		$liste_codes_competence = referentiel_get_liste_codes_competence($referentielid);	

		$course = $record->course;

		$userid = $record->userid;
		$teacherid = $record->teacherid;
		if ($teacherid==0){
			if ($isteacher || $iseditor){ 
				$teacherid=$USER->id;
			}
		} 

		$timecreated = $record->timecreated;
		$timemodified = $record->timemodified;
		$approved = $record->approved;
		$taskid = $record->taskid;
		if ($taskid>0){ // remplacer par la liste definie dans la tache
			$liste_codes_competences_tache = referentiel_get_liste_codes_competence_tache($taskid);
			// DEBUG
			// echo "<br/>DEBUG ::<br />\n";
			// echo $liste_codes_competences_tache;				
		} 
		else{
			$liste_codes_competences_tache=$liste_codes_competence;
		}
		// DEBUG
		// echo "<br/>DEBUG ::<br />\n";
		// print_object($record);
		
		$user_info=referentiel_get_user_info($userid);
		$teacher_info=referentiel_get_user_info($teacherid);
		// dates
		$date_creation_info=userdate($timecreated);
		
		if ($timemodified!=0){
			$date_modif_info=userdate($timemodified);
		}
		else{
			$date_modif_info='';
		}

		// MODIF JF 2009/10/27
		$timemodifiedstudent = $record->timemodifiedstudent;
		if ($timemodifiedstudent==0){
			$timemodifiedstudent=$timecreated;
		}
		if ($timemodifiedstudent!=0){
			$date_modif_student_info=userdate($timemodifiedstudent);
		}
		else{
			$date_modif_student_info='';
		}
		
		
		// MODIF JF 2009/10/21						
		$old_liste_competences=stripslashes($record->comptencies);		
		
		// MODIF JF 2009/10/23
		$url_course=referentiel_get_course_link($course);

		// MODIF JF 2009/11/08
		// afficher le menu si l'activité est affiche dans son propre cours de création 
		$menu_actif = $actif || ($course == $COURSE->id);

		if ($menu_actif){ 
			$has_capability=has_capability('mod/referentiel:approve', $context);
			$is_owner=referentiel_activite_isowner($activite_id);
			$s_menu.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=listactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'#activite"><img src="pix/nosearch.gif" alt="'.get_string('moins', 'referentiel').'" title="'.get_string('moins', 'referentiel').'" /></a>'."\n";
			if ($has_capability	or $is_owner){
				if ($has_capability || ($is_owner && !$approved)) {
	        		$s_menu.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=updateactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/edit.gif" alt="'.get_string('edit').'" title="'.get_string('edit').'" /></a>'."\n";
				}
				if ($has_capability || $is_owner) {
			    	$s_menu.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=deleteactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/delete.gif" alt="'.get_string('delete').'" title="'.get_string('delete').'" /></a>'."\n";
    			}
			}
			// valider
		    if ($has_capability){
				if (!$approved){
					$s_menu.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=approveactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="pix/nonvalide.gif" alt="'.get_string('approve', 'referentiel').'" title="'.get_string('approve', 'referentiel').'" /></a>'."\n";
				}
				else{
    				$s_menu.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=desapproveactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="pix/valide.gif" alt="'.get_string('desapprove', 'referentiel').'" title="'.get_string('desapprove', 'referentiel').'" /></a>'."\n";
				}
			}
			// commentaires
    		if (has_capability('mod/referentiel:comment', $context)){
    			$s_menu.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$instanceid.'&amp;select_acc='.$select_acc.'&amp;activite_id='.$activite_id.'&amp;userid='.$userid.'&amp;mode=commentactivity&amp;old_mode='.$mode.'&amp;sesskey='.sesskey().'"><img src="pix/feedback.gif" alt="'.get_string('more', 'referentiel').'" title="'.get_string('comment', 'referentiel').'" /></a>'."\n";
			}
		}
		else{
			$s_menu.='&nbsp; '.get_string('activite_exterieure', 'referentiel');
		}

		// DOCUMENTS
		// charger les documents associes à l'activite courante
		$compteur_document=0;
		$s_document='';
		if (isset($activite_id) && ($activite_id>0)){
			$activityid=$activite_id; // plus pratique
			// AFFICHER LA LISTE DES DOCUMENTS
			$records_document = referentiel_get_documents($activityid);
	    	if ($records_document){
    			// afficher
				// DEBUG
				// echo "<br/>DEBUG <br />\n";
				// print_r($records_document);
				foreach ($records_document as $record_d){
					$compteur_document++;
        			$document_id=$record_d->id;
					$type = stripslashes($record_d->type);
					$description = stripslashes($record_d->description);
					$url = $record_d->url;
					$activityid = $record_d->activityid;
					if (isset($record_d->target) && ($record_d->target==1)){
						$target='_blank'; // fenêtre cible
					}
					else{
						$target='';
					}
					if (isset($record_d->label)){
						$label=$record_d->label; // fenêtre cible
					}
					else{
						$label='';
					}
					$s_document.=get_string('document', 'referentiel').' &nbsp; &nbsp; <i>'.$document_id.'</i> &nbsp; &nbsp; '.$type.' &nbsp; &nbsp; ';
					$s_document.=nl2br($description).' &nbsp; &nbsp; ';
					$s_document.=referentiel_affiche_url($url, $label, $target).'<br />'."\n";
				}
			}
		}
		
		// AFFICHAGE
		echo '<tr valign="top">';
		if (isset($approved) && ($approved)){
			echo '<td class="valide" rowspan="3">';
		}
		else{
			echo '<td class="invalide" rowspan="3">';
		}
		echo  $activite_id;
		echo '</td><td align="center">';
		echo $user_info;
		echo '</td><td align="center">';
		echo $url_course;
		echo '</td><td align="center">';
		echo $type_activite;
		// Modif JF 06/10/2010
		if ($taskid){
            // consignes associées à une tâche
            $titre_task=referentiel_get_theme_task($taskid);
            $info_task=referentiel_get_content_task($taskid);
            if ($info_task!=''){
                // lien vers la tâche
                echo '<br>'.referentiel_affiche_overlib_texte($titre_task, $info_task);
            }
            // documents associés à une tâche
            echo referentiel_print_liste_documents_task($taskid);
        }

		echo '</td><td align="center">';
		echo $teacher_info;
		echo '</td><td align="center">';
		
			if (isset($approved) && ($approved)){
				echo get_string('approved','referentiel');
			}
			else{
				echo get_string('not_approved','referentiel');
			}	
		
		
		echo '</td>';
		echo '<td align="center">';
		echo '<span class="small">'.$date_modif_student_info.'</span>';
		echo '</td>';
		echo '<td align="center">';
		echo '<span class="small">'.$date_modif_info.'</span>';
		echo '</td>'."\n";
		// menu
		echo '<td align="center" rowspan="3">'."\n";
		echo $s_menu;
		echo '</td>';
		echo '</tr><tr valign="top">'."\n";
		
		if (isset($approved) && ($approved)){
			echo '<td  colspan="5" class="valide">';
		}
		else{
			echo '<td colspan="5" class="invalide">';
		}
		echo referentiel_affiche_overlib_item('/',$comptencies);
		echo '<br />'."\n";
		echo nl2br($description);
		echo '</td>';
		/*
        if (isset($approved) && ($approved)){
			echo '<td class="valide"  colspan="2">';
		}
		else{
			echo '<td class="invalide" colspan="2">';
		}
		*/
		echo '<td class="ardoise" colspan="2">';
		if ($comment!=''){
			echo nl2br($comment);
		}
		else{
			// echo get_string('nocommentaire','referentiel');
			echo '&nbsp;';
		}	
		echo '</td>';
		echo '</tr>'."\n";
		echo '<tr valign="top">'."\n";
		echo '<td class="yellow" colspan="7" align="center">'."\n";
		if ($s_document!=''){
			echo $s_document;
		}
		else{
			echo '&nbsp;';
		}
		echo '</td></tr>'."\n";
	}
	return $s;
}
?>