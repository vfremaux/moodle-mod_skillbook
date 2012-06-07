<?php  // $Id: tabs.php,v 1.24.2.5 2007/09/24 17:15:31 skodak Exp $
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
/// This file to be included so we can assume config.php has already been included.
/// We also assume that $user, $course, $currenttab have been set


if (empty($referentiel) or empty($course) or empty($cm)) {
       // print_error('You cannot call this script in that way');
		error(get_string('erreurscript','referentiel','Erreur01 : activite.php'));
}

if (!isset($currenttab) || empty($currenttab)) {
    $currenttab = 'referentiel';
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);


$tabs = array();
$row  = array();
$inactive = NULL;
$activetwo = NULL;


// premier onglet
if (has_capability('mod/referentiel:view', $context)) {
	$row[] = new tabobject('referentiel', $CFG->wwwroot.'/mod/referentiel/view.php?d='.$referentiel->id.'&amp;noredirect=1', get_string('referentiel','referentiel'));
}


if (isloggedin()) {	

	// activites
	if (referentiel_user_can_addactivity($referentiel)) { 
		// took out participation list here!
    	// $addstring = empty($editentry) ? get_string('edit_activity', 'referentiel') : get_string('validation', 'referentiel');
		$addstring = get_string('edit_activity', 'referentiel');
        $row[] = new tabobject('list', $CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel->id.'&amp;mode=list', $addstring);
    }

	// taches
	if (referentiel_user_can_use_task($referentiel)) { 
		// took out participation list here!
    	// $addstring = empty($editentry) ? get_string('edit_activity', 'referentiel') : get_string('validation', 'referentiel');
		$addstring = get_string('tasks', 'referentiel');
        $row[] = new tabobject('task', $CFG->wwwroot.'/mod/referentiel/task.php?d='.$referentiel->id.'&amp;mode=listtask', $addstring);
    }


	// gestion des certificats
	if (has_capability('mod/referentiel:write', $context)) {
    	$row[] = new tabobject('certificat', $CFG->wwwroot.'/mod/referentiel/certificate.php?d='.$referentiel->id.'&amp;mode=listcertif',
        	get_string('certificat','referentiel'));
 	}
	
        // scolarite



        if (
        
              (has_capability('mod/referentiel:viewscolarite', $context)|| has_capability('mod/referentiel:managescolarite', $context))
          )



        {
           $row[] = new tabobject('scolarite', $CFG->wwwroot.'/mod/referentiel/student.php?d='.$referentiel->id, get_string('scolarite','referentiel'));
        }


	$tabs[] = $row;
	
	// ACTIVITE
	if (isset($currenttab) && (($currenttab == 'list') 
		|| ($currenttab == 'listactivity') 
		|| ($currenttab == 'listactivitysingle')
		|| ($currenttab == 'listactivityall') 
		|| ($currenttab == 'addactivity') 
		|| ($currenttab == 'updateactivity') 
		|| ($currenttab == 'exportactivity'))) {
		$row  = array();
        $inactive[] = 'list';
		$row[] = new tabobject('listactivity', $CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel->id.'&amp;mode=listactivity',  get_string('listactivity','referentiel'));
		$row[] = new tabobject('listactivityall', $CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel->id.'&amp;mode=listactivityall',  get_string('listactivityall','referentiel'));
		if (!has_capability('mod/referentiel:managecertif', $context)) {
			$row[] = new tabobject('addactivity', $CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel->id.'&amp;mode=addactivity',  get_string('addactivity','referentiel'));
			if ($mode=='updateactivity'){
				$row[] = new tabobject('updateactivity', $CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel->id.'&amp;mode=updateactivity',  get_string('updateactivity','referentiel'));				
			}
		}
		else {
			$row[] = new tabobject('addactivity', $CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel->id.'&amp;mode=addactivity',  get_string('addactivity','referentiel'));
			$row[] = new tabobject('updateactivity', $CFG->wwwroot.'/mod/referentiel/activite.php?d='.$referentiel->id.'&amp;mode=updateactivity',  get_string('updateactivity','referentiel'));	
		}
		if (has_capability('mod/referentiel:export', $context)) {
			$row[] = new tabobject('exportactivity', $CFG->wwwroot.'/mod/referentiel/export_activite.php?d='.$referentiel->id.'&amp;mode=exportactivity',  get_string('export','referentiel'));			
		}
		$tabs[] = $row;
        $activetwo = array('list');
    }
	
	// TACHES 
	if (isset($currenttab) && ( ($currenttab == 'listtask') 
		|| ($currenttab == 'listtasksingle') 
		|| ($currenttab == 'selecttask') 
		|| ($currenttab == 'addtask') 
		|| ($currenttab == 'updatetask') 
		|| ($currenttab == 'exporttask')
		|| ($currenttab == 'importtask')
		)) {
		$row  = array();
        $inactive[] = 'task';
		if (has_capability('mod/referentiel:viewtask', $context)) { 
			$row[] = new tabobject('listtask', $CFG->wwwroot.'/mod/referentiel/task.php?d='.$referentiel->id.'&amp;mode=listtask',  get_string('listtask','referentiel'));
			$row[] = new tabobject('listtasksingle', $CFG->wwwroot.'/mod/referentiel/task.php?d='.$referentiel->id.'&amp;mode=listtasksingle',  get_string('listtasksingle','referentiel'));
		}
		/*
		// inutile
		if (has_capability('mod/referentiel:selecttask', $context)) { 
			$row[] = new tabobject('selecttask', $CFG->wwwroot.'/mod/referentiel/task.php?d='.$referentiel->id.'&amp;mode=selecttask',  get_string('selecttask','referentiel'));
		}
		*/
	    if (has_capability('mod/referentiel:addtask', $context)) {
			$row[] = new tabobject('addtask', $CFG->wwwroot.'/mod/referentiel/task.php?d='.$referentiel->id.'&amp;mode=addtask',  get_string('addtask','referentiel'));
			$row[] = new tabobject('updatetask', $CFG->wwwroot.'/mod/referentiel/task.php?d='.$referentiel->id.'&amp;mode=updatetask',  get_string('updatetask','referentiel'));	
		}
		
		// IMPORT a faire
		
		if (has_capability('mod/referentiel:import', $context)) {
			$row[] = new tabobject('importtask', $CFG->wwwroot.'/mod/referentiel/import_task.php?d='.$referentiel->id.'&amp;mode=importtask',  get_string('import','referentiel'));
		}
		
		// EXPORT
		
		if (has_capability('mod/referentiel:export', $context)) {
			$row[] = new tabobject('exporttask', $CFG->wwwroot.'/mod/referentiel/export_task.php?d='.$referentiel->id.'&amp;mode=exporttask',  get_string('export','referentiel'));
		}
		
		$tabs[] = $row;
        $activetwo = array('task');
    }
	
	// CERTIFICATS
	else if (isset($currenttab) && (($currenttab == 'certificat') 
		|| ($currenttab == 'listcertif') 
		|| ($currenttab == 'listcertifsingle') 
		|| ($currenttab == 'scolarite') 
		|| ($currenttab == 'addcertif')
		|| ($currenttab == 'editcertif')
		|| ($currenttab == 'printcertif')
		|| ($currenttab == 'managecertif'))) {
		$row  = array();
        $inactive[] = 'certificat';
		
		if (has_capability('mod/referentiel:view', $context)) { // afficher un certificat
      	    $row[] = new tabobject('listcertif', $CFG->wwwroot.'/mod/referentiel/certificate.php?d='.$referentiel->id.'&amp;mode=listcertif&amp;sesskey='.sesskey(), get_string('listcertif', 'referentiel'));
		}
		/*
		if (has_capability('mod/referentiel:rate', $context)) { // rediger un certificat
      	    $row[] = new tabobject('addcertif', $CFG->wwwroot.'/mod/referentiel/certificate.php?d='.$referentiel->id.'&amp;mode=addcertif&amp;sesskey='.sesskey(), get_string('addcertif', 'referentiel'));
		}
		*/
		if (has_capability('mod/referentiel:rate', $context)) { // rediger un certificat
      	    $row[] = new tabobject('editcertif', $CFG->wwwroot.'/mod/referentiel/certificate.php?d='.$referentiel->id.'&amp;mode=editcertif&amp;sesskey='.sesskey(), get_string('editcertif', 'referentiel'));
		}
		if (has_capability('mod/referentiel:managecertif', $context)) {
      	    $row[] = new tabobject('managecertif', $CFG->wwwroot.'/mod/referentiel/export_certificate.php?d='.$referentiel->id.'&amp;mode=managecertif&amp;sesskey='.sesskey(), get_string('managecertif', 'referentiel'));
			if (referentiel_site_can_print_referentiel($referentiel->id)) { 
      	    	$row[] = new tabobject('printcertif', $CFG->wwwroot.'/mod/referentiel/print_certificate.php?d='.$referentiel->id.'&amp;mode=printcertif&amp;sesskey='.sesskey(), get_string('printcertif', 'referentiel'));
			}
		}
        if ($currenttab == '') {
            $currenttab = $mode = 'listcertif';
        }
        $tabs[] = $row;
        $activetwo = array('certificat');
    }
	
// SCOLARITE
else if (isset($currenttab) && (($currenttab == 'scolarite')
|| ($currenttab == 'liststudent')
|| ($currenttab == 'manageetab')
|| ($currenttab == 'addetab')
|| ($currenttab == 'listeetab')
|| ($currenttab == 'exportstudent')
|| ($currenttab == 'importstudent')
|| ($currenttab == 'editstudent')
)
) {
		$row  = array();
    $inactive[] = 'scolarite';
		
    $row[] = new tabobject('liststudent', $CFG->wwwroot.'/mod/referentiel/student.php?d='.$referentiel->id.'&amp;mode=liststudent&amp;sesskey='.sesskey(), get_string('liststudent', 'referentiel'));
    
		if (has_capability('mod/referentiel:managescolarite', $context)) { // import export
			if ($currenttab == 'editstudent'){
		    	$row[] = new tabobject('editstudent', $CFG->wwwroot.'/mod/referentiel/student.php?d='.$referentiel->id.'&amp;mode=updatestudent&amp;sesskey='.sesskey(), get_string('editstudent', 'referentiel'));
      	    }
			$row[] = new tabobject('exportstudent', $CFG->wwwroot.'/mod/referentiel/export_student.php?d='.$referentiel->id.'&amp;mode=exportstudent&amp;sesskey='.sesskey(), get_string('exportstudent', 'referentiel'));
      	    $row[] = new tabobject('importstudent', $CFG->wwwroot.'/mod/referentiel/import_student.php?d='.$referentiel->id.'&amp;mode=importstudent&amp;sesskey='.sesskey(), get_string('importstudent', 'referentiel'));
		}
		if (has_capability('mod/referentiel:viewscolarite', $context)) { // etablissement
      	    $row[] = new tabobject('listeetab', $CFG->wwwroot.'/mod/referentiel/etablissement.php?d='.$referentiel->id.'&amp;mode=listeetab&amp;sesskey='.sesskey(), get_string('etablissements', 'referentiel'));
      	}
		if (has_capability('mod/referentiel:managescolarite', $context)) { // etablissement
		    $row[] = new tabobject('manageetab', $CFG->wwwroot.'/mod/referentiel/etablissement.php?d='.$referentiel->id.'&amp;mode=addetab&amp;sesskey='.sesskey(), get_string('manageetab', 'referentiel'));
		}

        if ($currenttab == '') {
            $currenttab = $mode = 'liststudent';
        }
        $tabs[] = $row;
        $activetwo = array('scolarite');
    }

	// seconde ligne d'onglets referentiel
	else if (isset($currenttab) && (($currenttab == 'referentiel') || ($currenttab == 'listreferentiel') || ($currenttab == 'editreferentiel') || ($currenttab == 'deletereferentiel') || ($currenttab == 'import') || ($currenttab == 'export'))) {
		$row  = array();
		$inactive[] = 'referentiel';
		
		if (has_capability('mod/referentiel:view', $context)) {
			$row[] = new tabobject('listreferentiel', $CFG->wwwroot.'/mod/referentiel/view.php?d='.$referentiel->id.'&amp;mode=listreferentiel&amp;noredirect=1',  get_string('listreferentiel','referentiel'));
		}
		
		// NOUVEAU CONTROLE v3.0
		// 
		
		if (referentiel_site_can_write_or_import_referentiel($referentiel->id)) {
			if (has_capability('mod/referentiel:writereferentiel', $context)) {
    	    	$row[] = new tabobject('editreferentiel', $CFG->wwwroot.'/mod/referentiel/edit.php?d='.$referentiel->id.'&amp;mode=editreferentiel&amp;sesskey='.sesskey(),  get_string('editreferentiel','referentiel'));
    	    	$row[] = new tabobject('deletereferentiel', $CFG->wwwroot.'/mod/referentiel/delete.php?d='.$referentiel->id.'&amp;mode=deleteferentiel&amp;sesskey='.sesskey(),  get_string('deletereferentiel','referentiel'));
			}
			if (has_capability('mod/referentiel:import', $context)) {
    		    $row[] = new tabobject('import', $CFG->wwwroot.'/mod/referentiel/import.php?d='.$referentiel->id.'&amp;mode=import',  get_string('import','referentiel'));
			}		
    }
		if (has_capability('mod/referentiel:export', $context)) {
    		$row[] = new tabobject('export', $CFG->wwwroot.'/mod/referentiel/export.php?d='.$referentiel->id.'&amp;mode=export',  get_string('export','referentiel'));
    }
		if ($currenttab == '') {
            $currenttab = $mode = 'listreferentiel';
        }
		
		// print_r($row);
		// exit;
	    $tabs[] = $row;		
		$activetwo = array('referentiel');
    }
}
else{ // pas d'autre possibilite que l'affichage du r�ferentiel
	$tabs[] = $row;
	$currenttab='referentiel';
}

/// Print out the tabs and continue!
// print_r($tabs);
// exit;
print_tabs($tabs, $currenttab, $inactive, $activetwo);
	
?>
