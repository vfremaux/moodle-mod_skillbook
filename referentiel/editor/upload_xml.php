<?php   // $Id: upload_xml.php,v 1.0 2010/06/26/ 00:00:00 jfruitet Exp $
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
* Création du fichier xml simplifié
*
* @package referentiel
*/
// editor/upload_xml.php


// dossier des referentiels enregistres; doit exister sur le serveur
    require_once('../../../config.php');
    require_once('../lib.php');
    // require_once('../import_export_lib.php');	// IMPORT / EXPORT
    require_once($CFG->libdir . '/uploadlib.php');


    $ext_xml='.xml';	// extension du fichier de donnees sauvegardee
    $suffixe='_sxml';   // pour distinguer du format xml basique des sauvegardes du référentiel
    $format='xml';      // un seul format possible
    
    $editor='';           // donnees a archiver
    $nom_fichier='referentiel_'.date("YmdHis"); // par defaut en principe c'est le contenu de la balise <name> </name>

    $id    = optional_param('id', 0, PARAM_INT);    // course module id
    $d     = optional_param('d', 0, PARAM_INT);    // referentiel instance id
    $sesskey = optional_param('sesskey', '', PARAM_ALPHA);

	if ($d) {
        if (! $referentiel = get_record('referentiel', 'id', $d)) {
            print_error('Certification instance is incorrect');
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
    }
	else{
        // print_error('You cannot call this script in that way');
		error(get_string('erreurscript','referentiel','Erreur01 : editor/upload_xml.php'));
	}
    require_login();

    if (!isloggedin() or isguest()) {
        redirect($CFG->wwwroot.'/index.php?id='.$course->id);
    }

    // check role capability
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    require_capability('mod/referentiel:import', $context);

    // ensure the files area exists for this course
    $dossier_referentiel=make_upload_directory( "$course->id/$CFG->moddata/referentiel" );



// ################### PROGRAMME ###################
    if (!empty($_GET) || !empty($_POST)){

        if (!empty($_GET)){
            if (!empty($_GET['editor'])){
                $editor=html_entity_decode($_GET['editor'],ENT_QUOTES,'UTF-8');
            }
        }

        if (!empty($_POST)){ // traiter les valeurs
            if (!empty($_POST['editor'])){
                $editor=html_entity_decode($_POST['editor'],ENT_QUOTES,'UTF-8');
            }
        }

        if (!empty($editor)){

            if (preg_match('/\[name\](.*)\[\/name\]/i',$editor,$matches)){
                $nom_fichier=referentiel_recode_nom($matches[1]);
                // echo 'Nom du fichier : '.$nom_fichier."<br />\n";
            }

            $xml='<?xml version="1.0" encoding="UTF-8"?>'."\n".referentiel_conversion_xml($editor)."\n";

            // enregistrer le fichier
            $nom_complet=referentiel_enregistrer_fichier($xml, $nom_fichier, $dossier_referentiel, $ext_xml);
            // renvoyer les données
            //echo "Nom du fichier : ".$nom_complet."<br />\nURL : ".$url_serveur_local.'/'.$dossier_referentiel.'/'.$nom_fichier.$ext_xml."\n<br />\n<pre>".htmlentities($xml,ENT_QUOTES,'UTF-8')."</pre>\n";
            // Ajax sur l'éditeur appelant : editeur_referentiel
            echo $dossier_referentiel.'/'.$nom_fichier.$suffixe.$ext_xml;

        }
        else{
            echo "";
        }

    }
    else{
        echo "";
    }


// ################### F O N C T I O N S  L O C A L E S ###################

// ----------------------------
function referentiel_get_url_pere($path) {
// Retourne l'URL du répertoire contenant le script
// global $PHP_SELF;
// DEBUG
// echo "<br>PHP_SELF : $PHP_SELF\n";
//	$path = $PHP_SELF;
	$nomf = substr( strrchr($path, "/" ), 1);
	if ($nomf){
		$pos = strlen($path) - strlen($nomf) - 1;
		$pere = substr($path,0,$pos);
	}
	else
		$pere = $path;
	return $pere;
}

// ----------------
function referentiel_conversion_xml($s){
//
    $search  = array("<br>","<br/>","<br />");
    $replace = array("\n","\n","\n");
    $s=str_replace($search, $replace, $s);
    $s=strip_tags($s);
    $search  = array('[referentiel]','[/referentiel]','[domaine]','[/domaine]','[competence]','[/competence]','[item]','[/item]','[idcode]','[/idcode]','[name]','[/name]','[url]','[/url]','[definition]','[/definition]','[text]','[/text]');
    $replace = array('<referentiel>','</referentiel>','<domaine>','</domaine>','<competence>','</competence>','<item>','</item>','<idcode>','</idcode>','<name>','</name>','<url>','</url>','<definition>','</definition>','<text>','</text>');

    $s=str_replace($search, $replace, $s);
    return $s;
}


// ----------------
function referentiel_recode_nom($nom){
// retourne un nom d'url acceptable
    $nom=html_entity_decode(trim($nom),ENT_QUOTES,'UTF-8');

	//$s = strtr(trim($nom), " -%£&/'àéèêïîöôùüûç", "_______aeeeiioouuuc");
	$search =array('"'," ","'","%","£","&","/","à","é","è","ê","ï","î","ö","ô","ù","ü","û","ç");
    $replace=array('_','_','_','_','_',"_","_","a","e","e","e","i","i","o","o","u","u","u","c");
    //$s = urlencode(str_replace($search, $replace,trim($nom)));
    $s = str_replace($search, $replace,$nom);
	return $s;
}


// -----------------------
function referentiel_enregistrer_fichier($contenu, $nom_fichier, $dossier, $ext){
// sauvegarde le fichier dans le dossier
global $dir_serveur;
global $suffixe;
	$trouve=false;
	$f_name=$dossier.'/'.$nom_fichier.$suffixe.$ext;

   //
   // if (file_exists($dir_serveur.'/'.$f_name)){
   //     // confirmer l'écrasement ?
   // }

    //$fp_data = fopen($dir_serveur.'/'.$f_name, 'w');
    $fp_data = fopen($f_name, 'w');
	if ($fp_data){
	   fwrite($fp_data, $contenu);
	   fclose($fp_data);
	   return $f_name;
	}
	return '';
}


?>