<?php  // $Id:  print_lib_certificate.php,v 1.0 2008/04/29 00:00:00 jfruitet Exp $
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
 * Print Library of functions for certificate of module referentiel
 * 
 * @author jfruitet
 * @version $Id: lib.php,v 1.0 2009/06/22 07:00:00 jfruitet Exp $
 * @package referentiel
 **/


require_once("lib.php");

class OverItemCode {
// affichage overlib des descriptions d'item
	var $sep='/';
	var $tcode = array();// code item
	var $tdescription = array(); // description item

	function setSeparateur( $sep ) {
        $this->sep = $sep;
    }
	
	function setItemCode( $tcode ) {
        $this->tcode = $tcode;
    }
	
	function SetItemDescription( $tdescription ) {
        $this->tdescription = $tdescription;
    }

	//-------------------------
	function AfficheDescriptionItem($code){
		if ($code!=''){
		    return '<a class="overlib" href="javascript:void(0);" onmouseover="return overlib(\''.$this->tdescription[$code] .'\');" onmouseout="return nd();">'.$code.'</a>';
		}
		return '';
	}
	
	//-------------------------
	function AfficheListeDescription(){
		$s='';
		for ($i=0; $i<count($this->tcode); $i++){
			if ($this->tcode[$i]!=''){
			    $s.='<a class="overlib" href="javascript:void(0);" onmouseover="return overlib(\''.$this->tdescription[$this->tcode[$i]] .'\');" onmouseout="return nd();">'.$this->tcode[$i].'</a> ';
			}
		}
		return $s;
	}
	
	//-------------------------
	function AfficheListeDescriptionReduite($liste){
		$s='';
		$tlist=explode($this->sep,$liste);
		for ($i=0; $i<count($tlist); $i++){
			if ($tlist[$i]!=''){
			    $s.= '<a class="overlib" href="javascript:void(0);" onmouseover="return overlib(\''.$this->tdescription[$tlist[$i]] .'\');" onmouseout="return nd();">'.$tlist[$i].'</a> ';
			}
		}
		return $s;
	}
	
}
// FIN DE OverItemCode

// ----------------------------------------------------
function referentiel_affiche_overlib_item($sep, $liste){
	global $t_item_code;
	global $t_item_description_competence;
	if (isset($t_item_code) && $t_item_code && isset($t_item_description_competence) && $t_item_description_competence){
		$une_OverItemCode=new OverItemCode();
		$une_OverItemCode->SetSeparateur($sep);
		$une_OverItemCode->SetItemCode($t_item_code);
		$une_OverItemCode->SetItemDescription($t_item_description_competence);
		return ($une_OverItemCode->AfficheListeDescriptionReduite($liste));
	}
	else{
		return str_replace($sep, ' ', $liste);
	}
}

// ----------------------------------------------------
function referentiel_affiche_overlib_un_item($sep, $code){
	global $t_item_code;
	global $t_item_description_competence;
	if (isset($t_item_code) && $t_item_code && isset($t_item_description_competence) && $t_item_description_competence){
		$une_OverItemCode=new OverItemCode();
		$une_OverItemCode->SetSeparateur($sep);
		$une_OverItemCode->SetItemCode($t_item_code);
		$une_OverItemCode->SetItemDescription($t_item_description_competence);
		return ($une_OverItemCode->AfficheDescriptionItem($code));
	}
	else{
		return $code;
	}
}

// ----------------------------------------------------
function referentiel_affiche_overlib_texte($titre, $texte){
	if (!empty($texte)){
	    $texte=str_replace("\r", " ", $texte);
        $texte=str_replace("\n", " ", $texte);
        $texte=str_replace("'", "\'", $texte);
        $texte=str_replace('"', '&quot;', $texte);
        $titre=str_replace("'", "\'", $titre);
        $titre=str_replace('"', '&quot;', $titre);

		return '<a class="overlib" href="javascript:void(0);" onmouseover="return overlib(\''.$texte .'\', WIDTH, 640, STICKY, MOUSEOFF, VAUTO, FGCOLOR, \'#DDEEFF\', CAPTION, \''.$titre.'\');" onmouseout="return nd();"><b>'.get_string('consignes','referentiel').'</b></a> '."\n";
	}
	return '';
}





?>
