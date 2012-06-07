<?php  // $Id:  print_lib_task.php,v 1.0 2008/04/29 00:00:00 jfruitet Exp $
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
  `cle_souscription` varchar(255) NOT NULL DEFAULT '',
  `souscription_libre` int(4) NOT NULL DEFAULT '1',
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
  `$taskid` bigint(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='consigne' AUTO_INCREMENT=1 ;

--
-- Structure de la table `mdl_referentiel_a_user_task`
--

DROP TABLE IF EXISTS `mdl_referentiel_a_user_task`;
CREATE TABLE IF NOT EXISTS `mdl_referentiel_a_user_task` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(10) unsigned NOT NULL,
  `$taskid` bigint(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='user_select_task' AUTO_INCREMENT=1 ;

*/

/**
 * Print Library of functions for task of module referentiel
 * 
 * @author jfruitet
 * @version $Id: lib.php,v 1.4 2006/08/28 16:41:20 mark-nielsen Exp $
 * @version $Id: lib.php,v 1.0 2008/04/29 00:00:00 jfruitet Exp $
 * @package referentiel
 **/

 
require_once("lib.php");
require_once("lib_task.php");


// Affiche une task et les consignes associés
// *****************************************************************
// input @param a $record_t   of task                            *
// output null                                                     *
// *****************************************************************

function referentiel_print_liste_documents_task($taskid){
    $s_consigne='';
    if ($taskid){
            // charger les consignes associees à la tache courante
            $nbconsigne=0;
            // AFFICHER LA LISTE DES consigneS
            $compteur_consigne=0;
            $records_consigne = referentiel_get_consignes($taskid);

            if ($records_consigne){
                    $s_consigne.="\n".'<!-- consigne -->'."\n";
                    foreach ($records_consigne as $record_d){
                        $nbconsigne++;
                        $compteur_consigne++;
                        $consigne_id=$record_d->id;
                        $type = stripslashes($record_d->type);
                        $description = stripslashes($record_d->description);
                        $url = $record_d->url;
                        $$taskid = $record_d->taskid;
                        $target='_blank'; // fenêtre cible
                        if (isset($record_d->label)){
                        	$label=$record_d->label; // fenêtre cible
                        }
                        else{
                        	$label='';
                        }
                        $s_consigne.='<i>'.referentiel_affiche_url($url, $label, $target).' '."\n";
                    }
            }
    }
    return $s_consigne;
}

function referentiel_get_theme_task($taskid){
    $s='';
    if ($taskid){
        $record_t=referentiel_get_task($taskid);
        if ($record_t){
            return (stripslashes($record_t->type));
        }
    }
    return $s;
}


function referentiel_get_content_task($taskid, $all=false){
    $s='';
    if ($taskid){
        $record_t=referentiel_get_task($taskid);
        if ($record_t){
            $type = stripslashes($record_t->type);
            $description = stripslashes($record_t->description);
            $competences_task = $record_t->competences_task;
            $criteria = stripslashes($record_t->criteria);
            $instanceid = $record_t->instanceid;
            $referentielid = $record_t->referentielid;
            $course = $record_t->course;
            $auteurid = $record_t->auteurid;
            $timecreated = $record_t->timecreated;
            $timemodified = $record_t->timemodified;

            $timestart = $record_t->timestart;
            $timeend = $record_t->timeend;
            // Modalite souscription
            $souscription_libre = $record_t->souscription_libre;
            $cle_souscription = stripslashes($record_t->cle_souscription);
            $hidden = $record_t->hidden;

            $user_info=referentiel_get_user_info($auteurid);

            // dates
            $date_creation_info=userdate($timecreated);
            $date_modification_info=userdate($timemodified);
            $date_debut_info=userdate($timestart);
            $date_fin_info=userdate($timeend);

            // charger les consignes associees à la tache courante
            $s_consigne='';
            $nbconsigne=0;
            $$taskid=$taskid; // plus pratique
            // AFFICHER LA LISTE DES consigneS
            $compteur_consigne=0;
            $records_consigne = referentiel_get_consignes($$taskid);

            if ($records_consigne){
                    // afficher
                    // DEBUG
                    // echo "<br/>DEBUG ::<br />\n";
                    // print_r($records_consigne);
                    if ($all){
                        $s_consigne.='<!-- consigne -->'."\n";
                        $s_consigne.='<ul><b>'.get_string('consigne','referentiel').'</b>'."\n";
                    }
                    foreach ($records_consigne as $record_d){
                        $nbconsigne++;
                        $compteur_consigne++;
                        $consigne_id=$record_d->id;
                        $type = stripslashes($record_d->type);
                        $description = stripslashes($record_d->description);
                        $url = $record_d->url;
                        $$taskid = $record_d->taskid;
                        if (isset($record_d->target) && ($record_d->target == 1)){
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
                        if ($all){
                            $s_consigne.='<li><i>'.$consigne_id.'</i> '.$type.' '.nl2br($description_consign).' <b>'.get_string('url','referentiel').'</b> :';
                            $s_consigne.=referentiel_affiche_url($url, $label, $target);
                            $s_consigne.='</li>'."\n";
                        }
                        else{
                            $s_consigne.='<br>'.nl2br($description).'<br>'.referentiel_get_url($url, $label, $target);
                        }
                    }
                    if ($all){
                        $s_consigne.='</ul>'."\n";
                    }
            }

            $nblig=empty($s_consigne)?1:$nbconsigne;
            if ($all){
                $s.='<a name="task_'.$taskid.'"</a>'."\n".'<b>'.get_string('task','referentiel').'</b>'.$taskid.' '.get_string('auteur','referentiel').': ';
	       	    $s.=$user_info.' '.get_string('timestart','referentiel').': '.$date_debut_info.' '.get_string('timeend','referentiel').': '.$date_fin_info;

                $s.=get_string('type','referentiel').': '.$type.' '.get_string('description','referentiel').': '.nl2br($description)."\n";
                $s.=get_string('liste_codes_competence','referentiel').': '.referentiel_affiche_liste_codes_competence('/',$competences_task).' '.get_string('criteria','referentiel').': '.nl2br($criteria)."\n";
                // consignes
                if ($s_consigne!=''){
                    $s.=$s_consigne;
                }
            }
            else{
                if (strlen($description)>2048){
                    $description=substr($description,0,2048).'<br><i>(...)</i> ';
                }
                if (strlen($criteria)>1024){
                    $criteria=substr($criteria,0,1024).'<br><i>(...)</i> ';
                }

                $s.='<b>'.get_string('auteur','referentiel').'</b>: <i>'.$user_info.'</i><br><b>'.get_string('timeend','referentiel').'</b><i>'.$date_fin_info.'</i><br><b>'.get_string('description','referentiel').'</b>: '.nl2br($description).'<br><b>'.get_string('criteria','referentiel').':</b><br> '.nl2br(substr($criteria,0,1024));
            }
        }
    }
    return $s;
}


// ----------------------------------------------------
function referentiel_print_task($record_t, $context, $userid=0){
global $CFG;
	$s="";
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
  `cle_souscription` varchar(255) NOT NULL DEFAULT '',
  `souscription_libre` int(4) NOT NULL DEFAULT '1',
  `hidden` int(4) NOT NULL DEFAULT '0',  
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='task' AUTO_INCREMENT=1 ;
	*/
	if ($record_t){
		$taskid = $record_t->id;
		$type = stripslashes($record_t->type);
		$description = stripslashes($record_t->description);
		$competences_task = $record_t->competences_task;
		$criteria = stripslashes($record_t->criteria);
		$instanceid = $record_t->instanceid;
		$referentielid = $record_t->referentielid;
		$course = $record_t->course;
		$auteurid = $record_t->auteurid;
		$timecreated = $record_t->timecreated;
		$timemodified = $record_t->timemodified;
		
		$timestart = $record_t->timestart;
		$timeend = $record_t->timeend;
		$closed= ($timeend<time());
		// Modalite souscription	
        $souscription_libre = $record_t->souscription_libre;
        $cle_souscription = stripslashes($record_t->cle_souscription);
		$hidden = $record_t->hidden;
		
		$user_info=referentiel_get_user_info($auteurid);

		// dates
		$date_creation_info=userdate($timecreated);		
		$date_modif_info=userdate($timemodified);
		$date_debut_info=userdate($timestart);
		$date_fin_info=userdate($timeend);

		$has_capability_add=has_capability('mod/referentiel:addtask', $context);
		$has_capability_select=has_capability('mod/referentiel:selecttask', $context);
		$has_capability_view=has_capability('mod/referentiel:viewtask', $context);
		$is_owner=referentiel_task_isowner($taskid);
		
		
		if ((!$hidden) ||  $has_capability_add ){
		  $s.='<tr><td>';
		  $s.= $taskid;
		  $s.='</td><td>';
		  $s.=$user_info;
		  $s.='</td><td>';
		  $s.=$type;
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
            $s.=referentiel_print_liste_documents_task($taskid);
          }

		  $s.='</td><td>';
		  $s.=referentiel_affiche_liste_codes_competence('/',$competences_task);
		  $s.='</td><td>';
		  $s.='<span class="small">'.$date_debut_info.'</span>';
		  $s.='</td><td>';
		  $s.='<span class="small">'.$date_fin_info.'</span>';
		  $s.='</td><td>';
		  // Modalite souscription	
          if ($souscription_libre==1){
                $s.=get_string('libre','referentiel').'</span>';
		  }
          else{
                if ($cle_souscription!=''){
                    $s.=get_string('obtenir_cle_souscription', 'referentiel', $user_info).'</span>';
                }
                else{
                    $s.=get_string('avec_cle','referentiel').'</span>';
                }
          }
		  $s.='</td>';
		
		  // menu
		  $s.='<td align="center">'."\n";
		  $s.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$instanceid.'&amp;task_id='.$taskid.'&amp;mode=listtasksingle&amp;sesskey='.sesskey().'#task_'.$taskid.'"><img src="pix/search.gif" alt="'.get_string('plus', 'referentiel').'" title="'.get_string('plus', 'referentiel').'" /></a>'."\n";
		
		  if ($has_capability_add	or $is_owner){
            $s.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$instanceid.'&amp;task_id='.$taskid.'&amp;mode=imposetask&amp;sesskey='.sesskey().'"><img src="pix/assigner.gif" alt="'.get_string('assigner', 'referentiel').'"  title="'.get_string('assigner', 'referentiel').'" /></a>'."\n";
            $s.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$instanceid.'&amp;task_id='.$taskid.'&amp;mode=updatetask&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/edit.gif" alt="'.get_string('edit').'" title="'.get_string('edit').'" /></a>'."\n";
		    $s.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$instanceid.'&amp;task_id='.$taskid.'&amp;mode=deletetask&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/delete.gif" alt="'.get_string('delete').'" title="'.get_string('delete').'" /></a>'."\n";
		    $s.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$instanceid.'&amp;task_id='.$taskid.'&amp;mode=deletetaskactivites&amp;sesskey='.sesskey().'"><img src="pix/deleteall.gif" alt="'.get_string('delete_all_task_associations','referentiel').'" title="'.get_string('delete_all_task_associations','referentiel').'" /></a>'."\n";
		    if ($hidden){
          // masquee
          // http://localhost/moodle_dev/pix/t/hide.gif
  		    $s.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$instanceid.'&amp;task_id='.$taskid.'&amp;hide=0&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/show.gif" alt="'.get_string('show').'" title="'.get_string('show').'" /></a>'."\n";          
        }
        else{
          // affichee
  		    $s.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$instanceid.'&amp;task_id='.$taskid.'&amp;hide=1&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/hide.gif" alt="'.get_string('hide').'" title="'.get_string('hide').'" /></a>'."\n";          
        }
		  }
		  // selectionner
    if (has_capability('mod/referentiel:selecttask', $context)){
        if (!$closed){
            if ($userid && referentiel_user_tache_souscrite($userid, $taskid)){
                //$s.='&nbsp; <img src="'.$CFG->pixpath.'/i/tick_amber_big.gif" alt="'.get_string('subscribed_task', 'referentiel').'" title="'.get_string('subscribed_task', 'referentiel').'" />'."\n";
                $s.='&nbsp; <img src="pix/edit.gif" alt="'.get_string('subscribed_task', 'referentiel').'" title="'.get_string('subscribed_task', 'referentiel').'" />'."\n";

            }
            else{
                //$s.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$instanceid.'&amp;task_id='.$taskid.'&amp;mode=selecttask&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/i/tick_green_big.gif" alt="'.get_string('souscrire', 'referentiel').'"  title="'.get_string('souscrire', 'referentiel').'" /></a>';
                $s.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$instanceid.'&amp;task_id='.$taskid.'&amp;mode=selecttask&amp;sesskey='.sesskey().'"><img src="pix/copy.gif" alt="'.get_string('souscrire', 'referentiel').'"  title="'.get_string('souscrire', 'referentiel').'" /></a>';
            }
        }
        else{
    			$s.='&nbsp; <img src="pix/stop.gif" alt="'.get_string('closed_task', 'referentiel').'" title="'.get_string('closed_task', 'referentiel').'" />'."\n";
        }
    }
		  // valider
      if (has_capability('mod/referentiel:approve', $context)){
			 if (!$closed){
				$s.='&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$instanceid.'&amp;task_id='.$taskid.'&amp;mode=approvetask&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/go.gif" alt="'.get_string('approve', 'referentiel').'"  title="'.get_string('approve', 'referentiel').'"/></a>'."\n";
			 }
			 else{
    			$s.='&nbsp;  <a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$instanceid.'&amp;task_id='.$taskid.'&amp;mode=approvetask&amp;sesskey='.sesskey().'"><img src="pix/closed.gif" alt="'.get_string('approve', 'referentiel').'" title="'.get_string('approve', 'referentiel').'" /></a>'."\n";
			 }
		  }
		
		  $s.='</td></tr>'."\n";
	   }
  }
	return $s;
}



// Affiche une entete task
// *****************************************************************
// *
// output string                                                     *
// *****************************************************************

function referentiel_print_entete_task(){
// Affiche une entete task
$s="";
	$s.='<table class="activite">'."\n";
	$s.='<tr>';
	$s.='<th width="3%"><b>'.get_string('id','referentiel').'</b></th>';
	$s.='<th width="10%"><b>'.get_string('auteur','referentiel').'</b></th>';
	$s.='<th width="20%"><b>'.get_string('type','referentiel').'</b></th>';
	$s.='<th width="20%"><b>'.get_string('liste_codes_competence','referentiel').'</b></th>';
	$s.='<th width="10%"><b>'.get_string('timestart','referentiel').'</b></th>';
	$s.='<th width="10%"><b>'.get_string('timeend','referentiel').'</b></th>';
	$s.='<th width="10%"><b>'.get_string('souscription','referentiel').'</b></th>';
  $s.='<th width="20%">&nbsp;</th>';
	$s.='</tr>'."\n";
	return $s;
}

function referentiel_print_enqueue_task(){
// Affiche une entete task
	$s='</table>'."\n";
	return $s;
}


// Affiche une ligne de la table quand il n'y a pas d'task pour userid 
// *****************************************************************
// input @param a user id                                          *
// output string                                                     *
// *****************************************************************

function referentiel_print_aucune_task_user($userid){
	$s="";
	if ($userid){
		$user_info=referentiel_get_user_info($userid);
		$date_modif_info=userdate(time());
	}
	else{
		$user_info="&nbsp;";
		$date_modif_info="&nbsp;";
	}
	
	$s.='<tr><td class="zero">&nbsp;</td><td class="zero">';
	$s.=$user_info;
	$s.='</td><td class="zero">&nbsp;</td><td class="invalide">&nbsp;</td><td class="zero">&nbsp;</td><td class="zero">&nbsp;</td><td class="zero">';
	$s.='<span class="small">'.$date_modif_info.'</span>';
	$s.='</td><td class="zero">&nbsp;</td></tr>'."\n";
	
	return $s;
}


// Affiche une task et les consignes associés
// *****************************************************************
// input @param a $record_t   of task                            *
// output null                                                     *
// *****************************************************************

function referentiel_print_task_detail($record_t){
	if ($record_t){
		$taskid=$record_t->id;
		$type = stripslashes($record_t->type);
		$description = stripslashes($record_t->description);
		$competences_task = $record_t->competences_task;
		$criteria = stripslashes($record_t->criteria);
		$instanceid = $record_t->instanceid;
		$referentielid = $record_t->referentielid;
		$course = $record_t->course;
		$auteurid = $record_t->auteurid;
		$timecreated = $record_t->timecreated;
		$timemodified = $record_t->timemodified;
		
		$timestart = $record_t->timestart;
		$timeend = $record_t->timeend;
		// Modalite souscription	
        $souscription_libre = $record_t->souscription_libre;
        $cle_souscription = stripslashes($record_t->cle_souscription);
		$hidden = $record_t->hidden;
		
		$user_info=referentiel_get_user_info($auteurid);

		// dates
		$date_creation_info=userdate($timecreated);		
		$date_modification_info=userdate($timemodified);
		$date_debut_info=userdate($timestart);
		$date_fin_info=userdate($timeend);
		
		// charger les consignes associees à la tache courante
    $s='';	
    
  	
    $nbconsigne = 0;
    if (isset($taskid) && ($taskid > 0)){
			$$taskid = $taskid; // plus pratique
			// AFFICHER LA LISTE DES consigneS
			$compteur_consigne=0;
			$records_consigne = referentiel_get_consignes($$taskid);
	    
      if ($records_consigne){
    			// afficher
				// DEBUG
				// echo "<br/>DEBUG ::<br />\n";
				// print_r($records_consigne);
				
        foreach ($records_consigne as $record_d){
            $nbconsigne++;
            $compteur_consigne++;
            $consigne_id = $record_d->id;
			$type = stripslashes($record_d->type);
			$description = stripslashes($record_d->description);
			$url = $record_d->url;
			$$taskid = $record_d->taskid;
			if (isset($record_d->target) && ($record_d->target==1)){
				$target = '_blank'; // fenêtre cible
			} else {
				$target = '';
			}
			if (isset($record_d->label)){
				$label = $record_d->label; // fenêtre cible
			} else {
				$label = '';
			}
					
          $s.='<!-- consigne -->
<tr valign="top">
<td class="jaune">
<b>'.get_string('consigne','referentiel').'</b><br />
<i>'.$consigne_id.'</i>
</td>
<td class="jaune">
<b>'.get_string('type','referentiel').'</b><br />	
'.$type.'
</td>
<td align="left" colspan="2" class="jaune">
<b>'.get_string('description','referentiel').'</b>	
<br />'.nl2br($description).' 
</td>
<td align="center" colspan="2" class="jaune">
<b>'.get_string('url','referentiel').'</b><br />
';
        
          $s.=referentiel_affiche_url($url, $label, $target);
          $s.='
</td>
</tr>			
';
				}
			}
		}
    $nbconsigne+=3;
    $nblig=empty($s)?3:$nbconsigne;
   
?>

<a name="<?php  echo "task_$task_id"; ?>"></a>
<table class="activite" width="100%">
<tr valign="top">
    <td width="2%" rowspan="<?php echo $nblig ?>">
	<b><?php  print_string('id','referentiel'); ?> </b> 
	<?php  p($taskid) ?>
    </td>
    <td width="15%">
     <b><?php print_string('auteur','referentiel')?> </b><br />
		<?php p($user_info) ?>
    </td>
	<td width="15%">
	<b><?php  print_string('timecreated','referentiel') ?> </b><br />
		<?php  echo '<span class="small">'.$date_creation_info.'</span>'; ?>
    </td>	
	<td width="15%">
	<b><?php  print_string('date_modification','referentiel') ?> </b><br />
		<?php  echo '<span class="small">'.$date_modification_info.'</span>'; ?>
    </td>		
	<td width="15%">
	<b><?php  print_string('timestart','referentiel') ?> </b><br />
		<?php  echo '<span class="small">'.$date_debut_info.'</span>'; ?>
    </td>	
	<td width="15%">
	<b><?php  print_string('timeend','referentiel') ?> </b><br />
		<?php  echo '<span class="small">'.$date_fin_info.'</span>'; ?>
  </td><td width="20%">
  <b><?php  print_string('souscription','referentiel') ?> </b><br />
<?php
		// Modalite souscription	
    if ($souscription_libre==1){
      echo get_string('libre','referentiel').'</span>';
		}
    else{
      if ($cle_souscription!=''){
        echo get_string('obtenir_cle_souscription', 'referentiel', $user_info).'</span>';
      } 
      else{
        echo get_string('avec_cle','referentiel').'</span>';       
      }     
    }
?>    
    </td>		
</tr>

<tr valign="top">
    <td align="left" colspan="2">
	<b><?php  print_string('type','referentiel') ?></b>
	<br />
    
        <?php  p($type) ?>
    </td>
    <td class="valide" align="left" colspan="4">
	<b><?php  print_string('liste_codes_competence','referentiel') ?> </b>
	<br />
<?php
	echo(referentiel_affiche_liste_codes_competence('/',$competences_task));
?>
    </td>
</tr>
<tr valign="top">
    <td align="left" colspan="4">
	<b><?php  print_string('description','referentiel') ?></b>
	<br />

        <?php  echo (nl2br($description)); ?>
    </td>
    <td align="left" colspan="2">
	<b><?php  print_string('criteria','referentiel') ?></b>
	<br />
    
        <?php  echo (nl2br($criteria )); ?>
    </td>
</tr>
<?php
  // consignes
    if ($s!=''){
      echo $s;
    }
?>
</table>
<?php		
	}
}

// Affiche les tasks de ce referentiel
function referentiel_liste_toutes_taches($referentiel_instance_id){
	if (isset($referentiel_instance_id) && ($referentiel_instance_id>0)){
        $cm = get_coursemodule_from_instance('referentiel', $referentiel_instance_id);
        $course = get_record('course', 'id', $cm->course);
		if (empty($cm) or empty($course)){
        	print_error('REFERENTIEL_ERROR 5 :: print_lib_task.php :: You cannot call this script in that way');
		}
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
		
		$records = referentiel_get_all_tasks($course->id, $referentiel_instance_id);
		if (!$records){
            return false;
		}
	    else {
    		// afficher
			// DEBUG
			// echo "<br/>DEBUG ::<br />\n";
			// print_r($records);
			
			echo '<table class="activite">'."\n";
			foreach ($records as $record){
				referentiel_print_task($record, $context);
			}
			echo '</table>'."\n";
		}
	}
	return true;
}

// Affiche les taches de ce referentiel
function referentiel_menu_task_detail($context, $taskid, $referentiel_instance_id, $closed, $masquee){
	global $CFG;
	global $USER;
	$isauthor = has_capability('mod/referentiel:addtask', $context);
	$isstudent = has_capability('mod/referentiel:selecttask', $context) && !$isauthor;
	
	echo '<div align="center">';
	echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$referentiel_instance_id.'&amp;task_id='.$taskid.'&amp;mode=listtask&amp;sesskey='.sesskey().'#task_'.$taskid.'"><img src="pix/nosearch.gif" alt="'.get_string('moins', 'referentiel').'" title="'.get_string('moins', 'referentiel').'" /></a>';			
	if (has_capability('mod/referentiel:addtask', $context) 
				or referentiel_task_isowner($taskid)) {
        echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$referentiel_instance_id.'&amp;task_id='.$taskid.'&amp;mode=imposetask&amp;sesskey='.sesskey().'"><img src="pix/assigner.gif" alt="'.get_string('assigner', 'referentiel').'"  title="'.get_string('assigner', 'referentiel').'" /></a>';
       	echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$referentiel_instance_id.'&amp;task_id='.$taskid.'&amp;mode=updatetask&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/edit.gif" alt="'.get_string('edit').'" title="'.get_string('edit').'" /></a>';
        echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$referentiel_instance_id.'&amp;task_id='.$taskid.'&amp;mode=deletetask&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/delete.gif" alt="'.get_string('delete').'" title="'.get_string('delete').'" /></a>';
        echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$referentiel_instance_id.'&amp;task_id='.$taskid.'&amp;mode=deletetaskactivites&amp;sesskey='.sesskey().'"><img src="pix/deleteall.gif" alt="'.get_string('delete_all_task_associations','referentiel').'" title="'.get_string('delete_all_task_associations','referentiel').'" /></a>'."\n";
		    if ($masquee){
          // masquee
          // http://localhost/moodle_dev/pix/t/hide.gif
  		    echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$referentiel_instance_id.'&amp;task_id='.$taskid.'&amp;hide=0&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/show.gif" alt="'.get_string('show').'" title="'.get_string('show').'" /></a>'."\n";          
        }
        else{
          // affichee
  		    echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$referentiel_instance_id.'&amp;task_id='.$taskid.'&amp;hide=1&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/hide.gif" alt="'.get_string('hide').'" title="'.get_string('hide').'" /></a>'."\n";          
        }
	}
	// selectionner
  if (has_capability('mod/referentiel:selecttask', $context)){
		if (!$closed){
		  if ($isstudent && $USER->id && referentiel_user_tache_souscrite($USER->id, $taskid)){
    			//echo '&nbsp; <img src="'.$CFG->pixpath.'/i/tick_amber_big.gif" alt="'.get_string('subscribed_task', 'referentiel').'" title="'.get_string('subscribed_task', 'referentiel').'" />'."\n";
    			echo '&nbsp; <img src="pix/edit.gif" alt="'.get_string('subscribed_task', 'referentiel').'" title="'.get_string('subscribed_task', 'referentiel').'" />'."\n";

			}
      else{
			  // echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$referentiel_instance_id.'&amp;task_id='.$taskid.'&amp;mode=selecttask&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/i/tick_green_big.gif" alt="'.get_string('souscrire', 'referentiel').'"  title="'.get_string('souscrire', 'referentiel').'" /></a>';
                echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$referentiel_instance_id.'&amp;task_id='.$taskid.'&amp;mode=selecttask&amp;sesskey='.sesskey().'"><img src="pix/copy.gif" alt="'.get_string('souscrire', 'referentiel').'"  title="'.get_string('souscrire', 'referentiel').'" /></a>';
		  }
    }
		else{
    		echo '&nbsp; <img src="pix/stop.gif" alt="'.get_string('closed_task', 'referentiel').'" title="'.get_string('closed_task', 'referentiel').'" />'."\n";
		}
	}
	// valider
    if (has_capability('mod/referentiel:approve', $context)){
		if (!$closed){
			echo '&nbsp; <a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$referentiel_instance_id.'&amp;task_id='.$taskid.'&amp;mode=approvetask&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/go.gif" alt="'.get_string('approve', 'referentiel').'"  title="'.get_string('approve', 'referentiel').'"/></a>'."\n";
		}
		else{
    		echo '&nbsp;  <a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$referentiel_instance_id.'&amp;task_id='.$taskid.'&amp;mode=approvetask&amp;sesskey='.sesskey().'"><img src="pix/closed.gif" alt="'.get_string('approve', 'referentiel').'" title="'.get_string('approve', 'referentiel').'" /></a>'."\n";
		}
	}
	echo '</div><br />';
}


/***************************************************************************
 * takes the current referentiel instance, a user id,                      *
 * and mode to display                                                     *
 * input @param array $records   of task                           		   *
 *       @param object $referentiel                                        *
 *       @param string $page                                               *
 * output null                                                             *
 ***************************************************************************/
function referentiel_print_liste_tasks($mode, $referentiel_instance, $userid_filtre=0, $page=0) {
global $CFG;
global $USER;
static $isteacher=false;
static $isauthor=false;
static $iseditor=false;
static $referentielid = NULL;

	
	// contexte
  $cm = get_coursemodule_from_instance('referentiel', $referentiel_instance->id);
  $course = get_record('course', 'id', $cm->course);
	if (empty($cm) or empty($course)){
        print_error('REFERENTIEL_ERROR 5 :: print_lib_task.php :: 568 :: You cannot call this script in that way');
	}
	
  $context = get_context_instance(CONTEXT_MODULE, $cm->id);
	$records = array();
	$referentielid = $referentiel_instance->referentielid;
	
	$isauthor = has_capability('mod/referentiel:addtask', $context);
	$isstudent = has_capability('mod/referentiel:selecttask', $context) && !$isauthor;
	$iseditor = has_capability('mod/referentiel:writereferentiel', $context);
	
	if (isset($referentielid) && ($referentielid>0)){
		$referentiel_referentiel=referentiel_get_referentiel_referentiel($referentielid);
		if (!$referentiel_referentiel){
			if ($iseditor){
				error(get_string('creer_referentiel','referentiel'), "edit.php?d=$referentiel_instance->id&amp;mode=editreferentiel&amp;sesskey=".sesskey());
			}
			else {
				error(get_string('creer_referentiel','referentiel'), "../../course/view.php?id=$course->id&amp;sesskey=".sesskey());
			}
		}
		// preparer les variables globales pour Overlib
		referentiel_initialise_data_referentiel($referentiel_referentiel->id);

		// filtres
		/*
		if (($isauthor) || ($iseditor)){
			$userid_filtre=$USER->id; 
		}

		// filtrage
		if ($isauthor || $iseditor){
			$record_tasks = referentiel_get_all_tasks_user($userid_filtre, $course->id, $referentiel_instance->id); // toutes les taches
		}
		else{
			// tous les users possibles (pour la boite de selection)
			// $record_all_users = referentiel_get_students_course($course->id,0,0);  //seulement les stagiaires
			$record_tasks = referentiel_get_all_tasks($course->id, $referentiel_instance->id); // toutes les taches
		}
		*/
		
		$record_tasks = referentiel_get_all_tasks($course->id, $referentiel_instance->id); // toutes les taches
		// DEBUG
		// echo "<br />DEBUG :: print_lib_task.php :: 520 :: Record_tasks<br />\n";
		// print_object($record_tasks  );
		// echo "<br />\n";
		// exit;
		
		if ($record_tasks){
			// Afficher 		
			if (isset($mode) && ($mode=='listtasksingle')){
				;
			}
			else{
				echo referentiel_print_entete_task();
			}
            foreach ($record_tasks as $record) {   // afficher les taches
                if (!$record->hidden || $isauthor){
                    if (isset($mode) && ($mode=='listtasksingle')){
                        referentiel_print_task_detail($record);
                        referentiel_menu_task_detail($context, $record->id, $referentiel_instance->id, $record->timeend<time(), $record->hidden);
                    }
                    else{
                        if ($isstudent){
                            echo referentiel_print_task($record, $context, $USER->id);
                        }
                        else{
                            echo referentiel_print_task($record, $context);
                        }
                    }
                }
            }
			// Afficher 		
            if (isset($mode) && ($mode=='listtasksingle')){
			 ;
            }
            else{
                echo referentiel_print_enqueue_task();
            }
            echo '<br /><br />'."\n";
        }
	}
}

/***************************************************************************
 * takes the current referentiel instance, a user id,                      *
 * and mode to display                                                     *
 * input @param array $records   of task                           		   *
 *       @param object $referentiel                                        *
 *       @param string $page                                               *
 * output null                                                             *
 ***************************************************************************/
function referentiel_print_task_id($taskid, $referentiel_instance) {
global $CFG;
global $USER;
static $isstudent=false;
static $isauthor=false;
static $iseditor=false;
static $referentielid = NULL;

	
	// contexte
    $cm = get_coursemodule_from_instance('referentiel', $referentiel_instance->id);
    $course = get_record('course', 'id', $cm->course);
    if (empty($cm) or empty($course)){
        print_error('REFERENTIEL_ERROR 5 :: print_lib_task.php :: You cannot call this script in that way');
    }
	
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
	$referentielid = $referentiel_instance->referentielid;
	
	$isauthor = has_capability('mod/referentiel:addtask', $context);
	$isstudent = has_capability('mod/referentiel:selecttask', $context) && !$isauthor;
	$iseditor = has_capability('mod/referentiel:writereferentiel', $context);
	
	if (isset($referentielid) && ($referentielid>0)){
		$referentiel_referentiel=referentiel_get_referentiel_referentiel($referentielid);
		if (!$referentiel_referentiel){
			if ($iseditor){
				error(get_string('creer_referentiel','referentiel'), "edit.php?d=$referentiel_instance->id&amp;mode=editreferentiel&amp;sesskey=".sesskey());
			}
			else {
				error(get_string('creer_referentiel','referentiel'), "../../course/view.php?id=$course->id&amp;sesskey=".sesskey());
			}
		}
		/*
		// filtres
		if ((!$isauthor) && (!$iseditor)){
			$userid_filtre=$USER->id; 
		}

		// filtrage
		if ($isauthor || $iseditor){
			// tous les users possibles (pour la boite de selection)
			// $record_all_users = referentiel_get_students_course($course->id,0,0);  //seulement les stagiaires
			$record_task = referentiel_get_task($taskid); // cette tache même si non auteur
		}
		else{
			$record_task = referentiel_get_task($taskid, $userid_filtre); // cette tache si auteur
		}
		*/
		$record = referentiel_get_task($taskid); // cette tache même si non auteur
		if ($record){
		  if (!$record->hidden || $isauthor){
  			// Afficher 		
	   		referentiel_print_task_detail($record);
		  	referentiel_menu_task_detail($context, $record->id, $referentiel_instance->id, $record->timeend<time(), $record->hidden);
		  }
    }
	}
	echo '<br /><br />'."\n";
}


/***************************************************************************
 * takes the current referentiel instance, a task id,                      *
 * and mode to display                                                     *
 * input @param array $taskid   of task                           		     *
 *       @param object $referentiel_instance                               *                                             *
 * output null                                                             *
 ***************************************************************************/
function referentiel_print_activities_task($taskid, $referentiel_instance, $mode, $userid_filtre=0, $gusers=NULL) {
// Propose la validation globale
global $CFG;
global $USER;
static $isstudent=false;
static $isauthor=false;
static $iseditor=false;
static $referentielid = NULL;
	
	// contexte
  $cm = get_coursemodule_from_instance('referentiel', $referentiel_instance->id);
  $course = get_record('course', 'id', $cm->course);
  if (empty($cm) or empty($course)){
        print_error('REFERENTIEL_ERROR 5 :: print_lib_task.php :: You cannot call this script in that way');
  }
	
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
	
    $iseditor = has_capability('mod/referentiel:writereferentiel', $context);
	$isteacher = has_capability('mod/referentiel:approve', $context)&& !$iseditor;
	$istutor = has_capability('mod/referentiel:comment', $context) && !$iseditor  && !$isteacher;
	$isauthor = has_capability('mod/referentiel:write', $context) && !$iseditor  && !$isteacher  && !$istutor;
	$isstudent = has_capability('mod/referentiel:selecttask', $context) && !$isauthor;

	if (isset($referentiel_instance->id) && ($referentiel_instance->id>0)){
		$referentiel_referentiel=referentiel_get_referentiel_referentiel($referentiel_instance->referentielid);
		if (!$referentiel_referentiel){
			if ($iseditor){
				error(get_string('creer_referentiel','referentiel'), "edit.php?d=$referentiel_instance->id&amp;mode=editreferentiel&amp;sesskey=".sesskey());
			}
			else {
				error(get_string('creer_referentiel','referentiel'), "../../course/view.php?id=$course->id&amp;sesskey=".sesskey());
			}
		}
		
	   // valider les activites
   	    if (has_capability('mod/referentiel:approve', $context)){

            $records_activity = referentiel_get_activites_task($taskid); // liste des activites associes a cette tache
            if ($records_activity){
                // boite pour selectionner les utilisateurs ?
                $record_id_users=array();
                foreach ($records_activity as $record_a) {
                    $record_id_users[$record_a->userid]->userid=$record_a->userid;
                    $record_id_users[$record_a->userid]->afficher=true;
                }
                //echo "<br>DEBUG :: 956<br>";
                //print_r($record_id_users);
                //exit;
                if ($isteacher || $iseditor || $istutor){
                    // tous les users possibles (pour la boite de selection)
                    // Get your userids the normal way

                    if ($gusers && $record_id_users){ // liste des utilisateurs du groupe courant
				        // echo "<br />DEBUG :: print_lib_activite.php :: 740 :: GUSERS<br />\n";
				        // print_object($gusers);
				        // echo "<br />\n";
				        // exit;
				        $record_users  = array_intersect($gusers, array_keys($record_id_users));
				        // echo "<br />DEBUG :: print_lib_activite.php :: 745 :: RECORD_USERS<br />\n";
				        // print_r($record_users  );
				        // echo "<br />\n";

                        // RAZ
                        for ($i=0; $i<count($record_id_users); $i++) {
                            $record_id_users[$i]->afficher=false;
                        }
                        // reinitialiser
				        foreach ($record_users  as $record_id){
                            $record_id_users[$record_id]->userid=$record_id;
                            $record_id_users[$record_id]->afficher=true;
				        }
                    }
                    //echo "<br>DEBUG :: 985<br>";
                    //print_r($record_id_users);
                    //exit;
                }

                echo '<div align="center"><h4 align="center"></h4>';
                if (!referentiel_closed_task($taskid)){
                    if ($mode=='approvetask'){
				        $str_approve=get_string('approve_all_activity_task', 'referentiel',$taskid);
				        echo '<a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$referentiel_instance->id.'&amp;task_id='.$taskid.'&amp;mode=approve&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/go.gif" alt="'.$str_approve.'"  title="'.$str_approve.'"/> '.$str_approve.'</a>'."\n";
                    }
                    else{
                        $str_approve=get_string('delete_all_activities_task', 'referentiel',$taskid);
				        echo '<a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$referentiel_instance->id.'&amp;task_id='.$taskid.'&amp;mode=deletetaskall&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/go.gif" alt="'.$str_approve.'"  title="'.$str_approve.'"/> '.$str_approve.'</a>'."\n";
                    }
                }else{
                    if ($mode=='approvetask'){
                        $str_approve=get_string('approve_all_activity_task_closed', 'referentiel',$taskid);
                        echo '<a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$referentiel_instance->id.'&amp;task_id='.$taskid.'&amp;mode=approve&amp;sesskey='.sesskey().'"><img src="pix/closed.gif" alt="'.$str_approve.'" title="'.$str_approve.'" /> '.$str_approve.'</a>'."\n";
                    }
                    else{
                        $str_approve=get_string('delete_all_activity_task_closed', 'referentiel',$taskid);
                        echo '<a href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$referentiel_instance->id.'&amp;task_id='.$taskid.'&amp;mode=deletetaskall&amp;sesskey='.sesskey().'"><img src="pix/closed.gif" alt="'.$str_approve.'" title="'.$str_approve.'" /> '.$str_approve.'</a>'."\n";
                    }
                }
                echo '</div><br />'."\n";
		        // Modif JF 20100118
			    echo '<form name="form" method="post" action="task.php?d='.$referentiel_instance->id.'&amp;sesskey='.sesskey().'">
<center>'."\n";
                echo '<table class="activite" width="100%" cellpadding="5" align="center">'."\n";
			    echo '<tr valign="top"><th class="activite" width="5%">&nbsp;</td><th class="activite" width="95%">';
                if ($mode=='approvetask'){
                    print_string('activites_tache','referentiel');
                }
                else{
                    print_string('activites_tache_delete','referentiel');
                }
                echo '</td></tr>'."\n";
                foreach ($records_activity as $record) {
                    if ($record_id_users[$record->userid]->afficher==true){
                        if ($record->approved){
                            echo '<tr valign="top"><td width="5%"><input type="checkbox" name="t_activite[]" value="'.$record->id.'" />'."\n";
                        }
                        else{
                            echo '<tr valign="top"><td width="5%"><input type="checkbox" name="t_activite[]" value="'.$record->id.'" checked="checked" />'."\n";
                        }
                        // Afficher l'activite
                        echo '</td><td width="95%">'."\n";
                        referentiel_print_activite_detail($record);
                        referentiel_menu_activite_detail($context, $record->id, $referentiel_instance->id, $record->approved);
                        echo '</td></tr>'."\n";
                    }
                }
		        echo '</table>
<input type="hidden" name="referentielid" value="'.$referentiel_instance->referentielid.'" />
<!-- These hidden variables are always the same -->
<input type="hidden" name="task_id"        value="'.$taskid.'" />
<input type="hidden" name="course"        value="'.$course->id.'" />
<input type="hidden" name="sesskey"     value="'.sesskey().'" />
<input type="hidden" name="modulename"    value="referentiel" />
<input type="hidden" name="instance"      value="'.$referentiel_instance->id.'" />'."\n";

                if ($mode=='approvetask'){
                    echo '<input type="hidden" name="mode" value="approve" />
<input type="submit" value="'.get_string('approve', 'referentiel').'" />'."\n";
                }
                else{
                    echo '<input type="hidden" name="mode" value="deletetaskall" />
<input type="submit" value="'.get_string('delete').'" />'."\n";
                }
                echo '
<input type="reset" value="'.get_string("restore").'" />
<input type="submit" value="'.get_string("cancel").'" />
</center>
</form>

'."\n";
		        echo '<br /><br />'."\n";
		      }
	       }
        }
}

/**
* Selection d'une liste d'utilisateurs a associer a une tache
*
*/
function referentiel_users_task_select($taskid, $mode, $users, $userid = 0){

	global $cm;
	global $course;
  	$maxcol = 8;

  	$str = '';
  	$t_users = array();
	if ($users){
	  	foreach ($users as $u) {   // liste d'id users
	  		$user = get_record('user', 'id', $u->userid, '', '', '', '', 'id,firstname,lastname');
			$t_users[] = (array)$user;
			$t_users_id[] = $user->id;
			$t_users_lastname[] = $user->lastname;
			$t_users_firstname[] = $user->firstname;
		}
		array_multisort($t_users_lastname, SORT_ASC, $t_users_firstname, SORT_ASC, $t_users);
		
		// exit;
		$n = count($t_users);
		$i = 0;
		
		$str .= "\n".'<form name="form" method="post" action="souscription.php?id='.$cm->id.'&amp;action=selectuser">'."\n";
		
		$str .= '<div align="center">'."\n"; 

		$str .= "\n".'<h3>'.get_string('aide_souscription','referentiel').'</h3>'."\n";

		$str .= '<table class="selection" width="80%">'."\n";
		$str .= '<tr>';
		$str .= '<td>';
    	$str .= '<input type="checkbox" name="select_all" id="select_all" value="1" />'.get_string('tous', 'referentiel')."\n";

		$str .= "\n<br />\n";	
		for ($j = 0; $j < $n; $j++){
				if ($userid == $t_users[$i]['id']){
  			     $str .= '<input type="checkbox" name="tuserid[]" id="tuserid_'.$t_users[$i]['id'].'" value="'.$t_users[$i]['id'].'" checked="checked" />'.$t_users[$i]['lastname'].' '.$t_users[$i]['firstname']."\n";
				} else {
  			     $str .= '<input type="checkbox" name="tuserid[]" id="tuserid_'.$t_users[$i]['id'].'" value="'.$t_users[$i]['id'].'" />'.$t_users[$i]['lastname'].' '.$t_users[$i]['firstname']."\n";
				}
				$i++;
		}
		
		$str .= '<br /><br /><input type="submit" value="'.get_string('select', 'referentiel').'" />'."\n";
		$str .= '<input type="reset" value="'.get_string('corriger', 'referentiel').'" />'."\n";
		$str .= '<input type="submit" value="'.get_string('cancel').'" />'."\n";		
		$str .= '
			<!-- These hidden variables are always the same -->
			<input type="hidden" name="task_id" value="'.$taskid.'" />
			<input type="hidden" name="course" value="'.$course->id.'" />
			<input type="hidden" name="sesskey" value="'.sesskey().'" />
			<input type="hidden" name="mode" value="'.$mode.'" />
			</form>'."\n";
 		$str .= '</td>';
		
  	$str .= '</tr></table>'."\n";
		$str .= '</div>'."\n";
	}
		
	return $str;
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
function referentiel_print_task_user_selection($taskid, $mode, $referentiel_instance, $userid_filtre=0, $gusers=NULL, $sql_filtre_where='', $sql_filtre_order='', $data_filtre=NULL) {
	global $CFG;
	global $USER;
	static $istutor=false;
	static $isteacher=false;
	static $isauthor=false;
	static $iseditor=false;
	static $referentielid = NULL;

    $data = NULL;

	// context
    $cm = get_coursemodule_from_instance('referentiel', $referentiel_instance->id);
    $course = get_record('course', 'id', $cm->course);
	if (empty($cm) or empty($course)){
        print_error('REFERENTIEL_ERROR 5 :: print_lib_activite.php :: You cannot call this script in that way');
	}
	
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
	
    $records = array();
	$referentielid = $referentiel_instance->referentielid;
	$iseditor = has_capability('mod/referentiel:writereferentiel', $context);
	$isteacher = has_capability('mod/referentiel:approve', $context)&& !$iseditor;
	$istutor = has_capability('mod/referentiel:comment', $context) && !$iseditor  && !$isteacher;	
	$isauthor = has_capability('mod/referentiel:write', $context) && !$iseditor  && !$isteacher  && !$istutor;	
	
	if (isset($referentielid) && ($referentielid > 0)){
		$referentiel_referentiel = referentiel_get_referentiel_referentiel($referentielid);
		if (!$referentiel_referentiel){
			if ($iseditor){
				error(get_string('creer_referentiel','referentiel'), "edit.php?d=$referentiel_instance->id&amp;mode=editreferentiel&amp;sesskey=".sesskey());
			}
			else {
				error(get_string('creer_referentiel','referentiel'), "../../course/view.php?id=$course->id&amp;sesskey=".sesskey());
			}
		}

		// boite pour selectionner les utilisateurs ?
		if ($isteacher || $iseditor || $istutor){
			// tous les users possibles (pour la boite de selection)
				// Get your userids the normal way
			$record_id_users  = referentiel_get_students_course($course->id, 0, 0);  //seulement les stagiaires
			if ($gusers && $record_id_users){ // liste des utilisateurs du groupe courant
				$record_users = array_intersect($gusers, array_keys($record_id_users));
				$record_id_users = array();
				foreach ($record_users  as $record_id){
					$record_id_users[]->userid = $record_id;
				}
			}
			// Ajouter l'utilisateur courant pour qu'il puisse souscrire aussi a ses taches
			// $record_id_users[]->userid=$USER->id;
			
			echo referentiel_users_task_select($taskid, $mode, $record_id_users, $userid_filtre);
		}
	}
	echo '<br /><br />'."\n";
	return true;
}



?>