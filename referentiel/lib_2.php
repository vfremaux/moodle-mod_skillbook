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
 * @version $Id: lib.php,v 1.4 2006/08/28 16:41:20 mark-nielsen Exp $
 * @version $Id: lib.php,v 1.0 2008/04/29 00:00:00 jfruitet Exp $
 * @package referentiel
 **/


$referentiel_CONSTANT = 7;     /// for example

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
	$referentiel_id=0;
    // temp added for debugging
    // echo "DEBUG : ADD INSTANCE CALLED";
    // DEBUG
	// print_object($form);
    // echo "<br />";
	// exit;
			// saisie de l'instance
	if (isset($form) && !empty($form)){
			$referentiel = new object();
			$referentiel->name=($form->name_instance);
			$referentiel->description=($form->description);
			$referentiel->domainlabel=($form->domainlabel);
			$referentiel->skilllabel=($form->skilllabel);
			$referentiel->itemlabel=($form->itemlabel);
		    $referentiel->timecreated = time();
			$referentiel->course=$form->course;
			$referentiel->referentielid=$referentiel_id;
		    // DEBUG
			// print_object($referentiel);
		    // echo "<br />";
			$referentiel_id= insert_record("referentiel", $referentiel);
	}
	return $referentiel_id;
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
		$referentiel->id=$form->instance;		
		$referentiel->name=($form->name_instance);
		$referentiel->description=($form->description);
		$referentiel->domainlabel=($form->domainlabel);
		$referentiel->skilllabel=($form->skilllabel);
		$referentiel->itemlabel=($form->itemlabel);
		$referentiel->timecreated = time();
		$referentiel->course=$form->course;
		// $referentiel->referentielid=$form->referentielid;
		// DEBUG
		// print_object($referentiel);
		// echo "<br />";
		$ok=update_record("referentiel", $referentiel);
	}
	return $ok;
}

/**
 * Given an ID of an instance of this module, 
 * this function will permanently delete the instance 
 * and any referentiel that depends on it. 
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
	
    if (! delete_records("referentiel", "id", "$id")) {
        $ok = $ok && false;
    }
    
	return ($ok);
}


/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 *
 * @param object $form An object from the form in edit.html
 * @return int The id of the newly inserted referentiel record
 **/
function referentiel_associe_referentiel_instance($form){
// importation ou selection ou creation
	if (isset($form->instance) && ($form->instance)
		&& isset($form->new_referentiel_id) && ($form->new_referentiel_id)){
		// id referentiel doit être numerique
		$referentiel_id = intval(trim($form->instance));
		$referentiel_referentiel_id = intval(trim($form->new_referentiel_id));
		$referentiel = referentiel_get_referentiel($referentiel_id);
		$referentiel->name_instance=addslashes($referentiel->name);
		$referentiel->description=addslashes($referentiel->description);
		$referentiel->domainlabel=addslashes($referentiel->domainlabel);
		$referentiel->skilllabel=addslashes($referentiel->skilllabel);
		$referentiel->itemlabel=addslashes($referentiel->itemlabel);
		$referentiel->referentielid=$referentiel_referentiel_id;
		
		// DEBUG
		// echo "<br />DEBUG :: lib.php :: 152 :: referentiel_associe_referentiel_instance()<br />\n";
		// print_object($referentiel);
		// echo "<br />";
		$ok=update_record("referentiel", $referentiel);
		return $ok;
	}
	return 0;
}

/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 *
 * @param object $form An object from the form in edit.html
 * @return int The id of the newly inserted referentiel record
 **/
function referentiel_de_associe_referentiel_instance($id){
// suppression de la reference vers un referentiel
	if (isset($id) && ($id)){
		// id referentiel doit être numerique
		$id = intval(trim($id));
		$referentiel = referentiel_get_referentiel($id);
		$referentiel->name_instance=addslashes($referentiel->name);
		$referentiel->description=addslashes($referentiel->description);
		$referentiel->domainlabel=addslashes($referentiel->domainlabel);
		$referentiel->skilllabel=addslashes($referentiel->skilllabel);
		$referentiel->itemlabel=addslashes($referentiel->itemlabel);		
		$referentiel->referentielid=0;
		// DEBUG
		// print_object($referentiel);
		// echo "<br />";
		return (update_record("referentiel", $referentiel));
	}
	return 0;
}


/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in add.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 *
 * @param object $instance An object from the form in add.html
 * @return int The id of the newly inserted referentiel record
 **/
function referentiel_add_referentiel_domaines($form) {
// La premiere creation permet aussi la saisie d'un domaine, d'une compétence et d'un item 
	$referentiel_referentiel_id=0;
    // temp added for debugging
    // echo "<br />DEBUG :: lib.php :: 196 :: ADD INSTANCE CALLED";
    // DEBUG
	// print_object($form);
    // echo "<br />";
	// exit;
	// saisie d'un referentiel
	if (isset($form->name) && ($form->name!="") 
		&& isset($form->code) && ($form->code!="")){
		// creer
		$referentiel_referentiel = new object();
		$referentiel_referentiel->name=($form->name);
		$referentiel_referentiel->code=($form->code);
		$referentiel_referentiel->description=($form->description);
		$referentiel_referentiel->url=($form->url);
		$referentiel_referentiel->certificatethreshold=$form->certificatethreshold;
		$referentiel_referentiel->nb_domaines=$form->nb_domaines;	
		$referentiel_referentiel->liste_codes_competence=$form->liste_codes_competence;	
		$referentiel_referentiel->liste_empreintes_competence=$form->liste_empreintes_competence;
		$referentiel_referentiel->timemodified = time();
		if (isset($form->local) && ($form->local!=0) && isset($form->course) && ($form->course!=0)){
			$referentiel_referentiel->local=$form->course;
		}
		else{
			$referentiel_referentiel->local=0;
		}
		$referentiel_referentiel->logo = $form->logo;

	    // DEBUG
	    // echo "<br />DEBUG :: lib.php :: 221";		
		// print_object($referentiel_referentiel);
	    // echo "<br />";
		
		$referentiel_referentiel_id = insert_record("referentiel_referentiel", $referentiel_referentiel);
    	// echo "REFERENTIEL ID : $referentiel_referentiel_id<br />";
		
		if ($referentiel_referentiel_id>0){
			// saisie de l'instance
			$referentiel = new object();
			$referentiel->name=($form->name_instance);
			$referentiel->description=($form->description);
			$referentiel->domainlabel=($form->domainlabel);
			$referentiel->skilllabel=($form->skilllabel);
			$referentiel->itemlabel=($form->itemlabel);
		    $referentiel->timecreated = time();
			$referentiel->course=$form->course;
			$referentiel->referentielid=$referentiel_referentiel_id;
		    // DEBUG
			// echo "<br />DEBUG :: lib.php :: 240";
			// print_object($referentiel);
		    // echo "<br />";
			$referentiel_id= insert_record("referentiel", $referentiel);
				
			// saisie du domaine
			$domaine = new object();
			$domaine->referentielid=$referentiel_referentiel_id;
			$domaine->code=$form->code;
			$domaine->description=$form->description;
			$domaine->sortorder=$form->sortorder;
			$domaine->nb_competences=$form->nb_competences;
		    // DEBUG
			// echo "<br />DEBUG :: lib.php :: 253";
			// print_object($domaine);
			// echo "<br />";
			
			$domaine_id = insert_record("referentiel_domain", $domaine);
    		// echo "DOMAINE ID / $domaine_id<br />";
			if ($domaine_id>0){
				$competence = new object();
				$competence->domainid=$domaine_id;
				$competence->code=($form->code);
				$competence->description=($form->description);
				$competence->sortorder=$form->sortorder;
				$competence->nb_item_competences=$form->nb_item_competences;
				
    			// DEBUG
				// echo "<br />DEBUG :: lib.php :: 268";
				// print_object($competence);
    			// echo "<br />";
				
				$competence_id = insert_record("referentiel_skill", $competence);
		    	// echo "COMPETENCE ID / $competence_id<br />";
				if ($competence_id>0){
					$item = new object();
					$item->referentielid=$referentiel_referentiel_id;
					$item->skillid=$competence_id;
					$item->code=($form->code);
					$item->description=($form->description);
					$item->type=$form->type;		
					$item->weight=$form->weight;
					$item->footprint=$form->footprint;
					$item->sortorder=$form->sortorder;
    				// DEBUG
					// echo "<br />DEBUG :: lib.php :: 283";
					// print_object($item);
    				// echo "<br />";
					
					$item_id=insert_record("referentiel_skill_item", $item);
				    // echo "ITEM ID / $item_id<br />";	
				}
			}
		}
		if ($referentiel_referentiel_id>0){
			$liste_codes_competence=referentiel_new_liste_codes_competence($referentiel_referentiel_id);
			referentiel_set_liste_codes_competence($referentiel_referentiel_id, $liste_codes_competence);
			$liste_empreintes_competence=referentiel_new_liste_empreintes_competence($referentiel_referentiel_id);
			referentiel_set_liste_empreintes_competence($referentiel_referentiel_id, $liste_empreintes_competence);		
		}
    	# May have to add extra stuff in here #
	}
	return $referentiel_referentiel_id;
}


/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in edit.html) this function 
 * will update an existing instance .
 *
 * @param object $instance An object from the form in edit.html
 * @return boolean Success/Fail
 **/
function referentiel_update_referentiel_domaines($form) {
	$ok=true;	
	// DEBUG
	// echo "<br />DEBUG :: lib.php :: 326 <br />";
	// print_object($form);
	// echo "<br />";
	if (isset($form->referentiel_id) && ($form->referentiel_id>0)){
		if (isset($form->action) && ($form->action=="modifierreferentiel")){
			// referentiel
			$referentiel_referentiel = new object();
			$referentiel_referentiel->name=($form->name);
			$referentiel_referentiel->code=($form->code);
			$referentiel_referentiel->description=($form->description);
			$referentiel_referentiel->url=($form->url);
			$referentiel_referentiel->certificatethreshold=($form->certificatethreshold);
    		$referentiel_referentiel->timemodified = time();
			$referentiel_referentiel->nb_domaines=$form->nb_domaines;
			$referentiel_referentiel->liste_codes_competence=$form->liste_codes_competence;
			$referentiel_referentiel->liste_empreintes_competence=$form->liste_empreintes_competence;

			// local ou global
			if (isset($form->local) && ($form->local!=0) && isset($form->course) && ($form->course!=0))
				$referentiel_referentiel->local=$form->course;
			else
				$referentiel_referentiel->local=0;
			
			$referentiel_referentiel->timemodified = time();
    		$referentiel_referentiel->id = $form->referentiel_id;
			$referentiel_referentiel->logo = $form->logo;
			
	    	// DEBUG
		    // echo "<br />";		
			// print_object($referentiel_referentiel);
	    	// echo "<br />";
			$ok=update_record("referentiel_referentiel", $referentiel_referentiel);
		}
		else if (isset($form->action) && ($form->action=="completerreferentiel")){
			if (isset($form->domaine_id) && is_array($form->domaine_id)){
				for ($i=0; $i<count($form->domaine_id); $i++){
					$domaine = new object();
					$domaine->id=$form->domaine_id[$i];
					$domaine->referentielid=$form->referentiel_id;
					$domaine->code=($form->code[$i]);
					$domaine->description=($form->description[$i]);
					$domaine->sortorder=$form->sortorder[$i];
					$domaine->nb_competences=$form->nb_competences[$i];
					
					if (!update_record("referentiel_domain", $domaine)){
						// DEBUG
						// print_object($domaine);
						// echo "<br />ERREUR DE MISE A JOUR...";
						$ok=$ok && false;
						// exit;
					}
					else{
						// DEBUG
						// print_object($domaine);
						// echo "<br />MISE A JOUR DOMAINE...";
						$ok=$ok && true;
					}
				}
			}
			// NOUVEAU DOMAINE
			if (isset($form->new_code_domaine) && is_array($form->new_code_domaine)){
				for ($i=0; $i<count($form->new_code_domaine); $i++){
					$domaine = new object();
					$domaine->referentielid=$form->referentiel_id;
					$domaine->code=($form->new_code_domaine[$i]);
					$domaine->description=($form->new_description_domaine[$i]);
					$domaine->sortorder=$form->new_num_domaine[$i];
					$domaine->nb_competences=$form->new_nb_competences[$i];
					// DEBUG
					// print_object($domaine);
					// echo "<br />";
					$new_domaine_id = insert_record("referentiel_domain", $domaine);
					$ok=$ok && ($new_domaine_id>0); 
    				// echo "DOMAINE ID / $new_domaine_id<br />";
				}
			}
			// COMPETENCES
			if (isset($form->competence_id) && is_array($form->competence_id)){
				for ($i=0; $i<count($form->competence_id); $i++){
					$competence = new object();
					$competence->id=$form->competence_id[$i];
					$competence->code=($form->code[$i]);
					$competence->description=($form->description[$i]);
					$competence->domainid=$form->domainid[$i];
					$competence->sortorder=$form->sortorder[$i];
					$competence->nb_item_competences=$form->nb_item_competences[$i];
					// DEBUG
					// print_object($competence);
					if (!update_record("referentiel_skill", $competence)){
						// echo "<br />ERREUR DE MISE A JOUR...";
						$ok=$ok && false;
						// exit;
					}
					else{
						// echo "<br />MISE A JOUR COMPETENCES...";
						$ok=$ok && true;
					}
				}
			}
			// NOUVElle competence
			if (isset($form->new_code_competence) && is_array($form->new_code_competence)){
				for ($i=0; $i<count($form->new_code_competence); $i++){
					$competence = new object();
					$competence->code=($form->new_code_competence[$i]);
					$competence->description=($form->new_description_competence[$i]);
					$competence->domainid=$form->new_ref_domaine[$i];
					$competence->sortorder=$form->new_num_competence[$i];
					$competence->nb_item_competences=$form->new_nb_item_competences[$i];
					// DEBUG
					// print_object($competence);
					// echo "<br />";
					$new_competence_id = insert_record("referentiel_skill", $competence);
					$ok=$ok && ($new_competence_id>0); 
   					// echo "competence ID / $new_competence_id<br />";
				}
			}
			// ITEM COMPETENCES
			if (isset($form->item_id) && is_array($form->item_id)){
				for ($i=0; $i<count($form->item_id); $i++){
					$item = new object();
					$item->id=$form->item_id[$i];
					$item->referentielid=$form->referentiel_id;
					$item->skillid=$form->skillid[$i];
					$item->code=($form->code[$i]);
					$item->description=($form->description[$i]);
					$item->sortorder=$form->sortorder[$i];
					$item->type=$form->type[$i];
					$item->weight=$form->weight[$i];
					$item->footprint=$form->footprint[$i];
					
					// DEBUG
					// print_object($item);
					// echo "<br />";
					if (!update_record("referentiel_skill_item", $item)){
						// echo "<br />ERREUR DE MISE A JOUR ITEM COMPETENCE...";
						$ok=$ok && false;
						// exit;
					}
					else {
						// echo "<br />MISE A JOUR ITEM COMPETENCES...";
						$ok=$ok && true;
					}
				}
			}
			// NOUVEL item
			if (isset($form->new_code_item) && is_array($form->new_code_item)){
				for ($i=0; $i<count($form->new_code_item); $i++){
					$item = new object();
					$item->referentielid=$form->referentiel_id;
					$item->skillid=$form->new_ref_competence[$i];
					$item->code=($form->new_code_item[$i]);
					$item->description=($form->new_description_item[$i]);
					$item->sortorder=$form->new_num_item[$i];
					$item->type=($form->new_type_item[$i]);
					$item->weight=$form->new_poids_item[$i];
					$item->footprint=$form->new_empreinte_item[$i];
					
					// DEBUG
					// print_object($item);
					// echo "<br />";
					$new_item_id = insert_record("referentiel_skill_item", $item);
					$ok=$ok && ($new_item_id>0); 
   					// echo "item ID / $new_item_id<br />";
				}
			}
			
			// Mise à jour de la liste de competences
			$liste_codes_competence=referentiel_new_liste_codes_competence($form->referentiel_id);
			// echo "<br />LISTE_CODES_COMPETENCE : $liste_codes_competence\n";
			referentiel_set_liste_codes_competence($form->referentiel_id, $liste_codes_competence);
			$liste_empreintes_competence=referentiel_new_liste_empreintes_competence($form->referentiel_id);
			// echo "<br />LISTE_empreintes_COMPETENCE : $liste_empreintes_competence\n";
			referentiel_set_liste_empreintes_competence($form->referentiel_id, $liste_empreintes_competence);
			
		}
	}
	return $ok;
}


/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in edit.html) this function 
 * will delete an existing instance .
 *
 * @param object $instance An object from the form in edit.html
 * @return boolean Success/Fail
 **/
function referentiel_delete_referentiel_domaines($id) {
$ok_domaine=true;
$ok_competence=true;
$ok_item=true;
$ok=true;
	// verifier existence
    if (! $referentiel = get_record("referentiel_referentiel", "id", "$id")) {
        return false;
    }
	
    # Delete any dependent records here #
    if ($domaines = get_records("referentiel_domain", "referentielid", "$id")) {
		// DEBUG
		// print_object($domaines);
		// echo "<br />";
		foreach ($domaines as $domaine){
			// Competences
			if ($competences = get_records("referentiel_skill", "domainid", "$domaine->id")) {
				// DEBUG
				// print_object($competences);
				// echo "<br />";
				// Item
				foreach ($competences as $competence){
					if ($items = get_records("referentiel_skill_item", "skillid", "$competence->id")) {
						// DEBUG
						// print_object($items);
						// echo "<br />";
						foreach ($items as $item){
							// suppression
							$ok_item=$ok_item && delete_records("referentiel_skill_item", "id", "$item->id");
						}
					}	
					$ok_competence=$ok_competence && delete_records("referentiel_skill", "id", "$competence->id");
				}
			}
			// suppression
			$ok_domaine=$ok_domaine && delete_records("referentiel_domain", "id", "$domaine->id");			
		}
    }
    if (! delete_records("referentiel_referentiel", "id", "$id")) {
        $ok = $ok && false;
    }
	
    return ($ok && $ok_domaine && $ok_competence && $ok_item);
}


/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 *
 * @param object $form An object
 * @return int The id of the newly inserted referentiel record
 **/
function referentiel_add_referentiel($form) {
// Creer un referentiel sans domaine ni competence ni item
    // temp added for debugging
    // echo "<br />DEBUG : ADD REFERENTIEL CALLED";
    // DEBUG
	print_object($form);
    echo "<br />";
	
	// referentiel
	$referentiel = new object();
	$referentiel->name=($form->name);
	$referentiel->code=($form->code);
	$referentiel->description=($form->description);
	$referentiel->url=($form->url);
	$referentiel->certificatethreshold=$form->certificatethreshold;
	$referentiel->nb_domaines=$form->nb_domaines;	
	$referentiel->liste_codes_competence=$form->liste_codes_competence;	
    $referentiel->timemodified = time();
	$referentiel->liste_empreintes_competence=$form->liste_empreintes_competence;		
	$referentiel->logo=$form->logo;		

	// local ou global
	if (isset($form->local) && ($form->local!=0) && isset($form->course) && ($form->course!=0))
		$referentiel->local=$form->course;
	else
		$referentiel->local=0;
    // DEBUG
	// print_object($referentiel);
    // echo "<br />";
	
	$new_referentiel_id= insert_record("referentiel_referentiel", $referentiel);
    // echo "REFERENTIEL ID / $referentiel_id<br />";
	return $new_referentiel_id;
}

/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will update an instance and return true
 *
 * @param object $form An object from the form in mod.html
 * @return boolean 
 **/
function referentiel_update_referentiel($form) {
// $form : formulaire
	// DEBUG
	// print_object($form);
	// echo "<br />";
	$ok=false;
	if (isset($form->referentiel_id) && ($form->referentiel_id>0)){
		// referentiel
		$referentiel = new object();
		$referentiel->name=($form->name);
		$referentiel->code=($form->code);
		$referentiel->description=($form->description);
		$referentiel->url=($form->url);
		$referentiel->certificatethreshold=$form->certificatethreshold;
    	$referentiel->timemodified = time();
		$referentiel->nb_domaines=$form->nb_domaines;
		$referentiel->liste_codes_competence=$form->liste_codes_competence;
		$referentiel->liste_empreintes_competence=$form->liste_empreintes_competence;		
		$referentiel->logo=$form->logo;		

		// local ou global
		if (isset($form->local) && ($form->local!=0) && isset($form->course) && ($form->course!=0))
			$referentiel->local=$form->course;
		else
			$referentiel->local=0;
		$referentiel->timemodified = time();
    	$referentiel->id = $form->referentiel_id;
	    // DEBUG
	    // echo "<br />";		
		// print_object($referentiel);
	    // echo "<br />";
		if (!update_record("referentiel_referentiel", $referentiel)){
			// echo "<br />ERREUR DE MISE A JOUR...";
			$ok=false;
		}
		else {
			$ok=true;
		}
    }
	// DEBUG
	// exit;
    return $ok;
}

/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will update an existing instance with new referentiel.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function referentiel_update_domaine($form) {
	$ok=false;	
	// DEBUG
	// echo "<br />DEBUG :: lib.php :: 652 <br />\n";
	// print_object($form);
	// echo "<br />";

	if (isset($form->domaine_id) && ($form->domaine_id>0)){
			$domaine = new object();
			$domaine->id=$form->domaine_id;
			$domaine->referentielid=$form->instance;
			$domaine->code=($form->code);
			$domaine->description=($form->description);
			$domaine->sortorder=$form->sortorder;
			$domaine->nb_competences=$form->nb_competences;
			if (!update_record("referentiel_domain", $domaine)){
				// DEBUG
				// print_object($domaine);
				// echo "<br />ERREUR DE MISE A JOUR...";
				$ok=false;
				// exit;
			}
			else{
				// DEBUG
				// print_object($domaine);
				// echo "<br />MISE A JOUR DOMAINE...";
				$ok=true;
			}
	}

	return $ok;
}

/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will add an existing instance with new domaine.
 *
 * @param object $instance An object from the form in mod.html
 * @return new_domaine_id
 **/
function referentiel_add_domaine($form) {
	$new_domaine_id=0;	
    // temp added for debugging
    // echo "<br />DEBUG : ADD DOMAINE CALLED";
    // DEBUG
	// print_object($form);
    // echo "<br />";

		// NOUVEAU DOMAINE
		if (isset($form->new_code_domaine) && ($form->new_code_domaine!="")){
			$domaine = new object();
			$domaine->referentielid=$form->instance;
			$domaine->code=$form->new_code_domaine;
			$domaine->description=($form->new_description_domaine);
			$domaine->sortorder=$form->new_num_domaine;
			$domaine->nb_competences=$form->new_nb_competences;
			// DEBUG
			// print_object($domaine);
			// echo "<br />";
			$new_domaine_id = insert_record("referentiel_domain", $domaine); 
    		// echo "DOMAINE ID / $new_domaine_id<br />";
		}

	return $new_domaine_id; 
}

/**
 * Given an item id, 
 * this function will delete of this item.
 *
 * @param int id
 * @return boolean 
 **/
function referentiel_delete_domaine($domaine_id){
// suppression
$ok_domaine=true;
$ok_competence=true;
$ok_item=true;
    # Delete any dependent records here #
	// Competences
	if ($competences = get_records("referentiel_skill", "domainid", $domaine_id)) {
		// DEBUG
		// print_object($competences);
		// echo "<br />";
		// Item
		foreach ($competences as $competence){
			if ($items = get_records("referentiel_skill_item", "skillid", $competence->id)) {
				// DEBUG
				// print_object($items);
				// echo "<br />";
				foreach ($items as $item){
					// suppression
					$ok_item=$ok_item && delete_records("referentiel_skill_item", "id", $item->id);
				}
			}	
			$ok_competence=$ok_competence && delete_records("referentiel_skill", "id", $competence->id);
		}
	}
	// suppression
	$ok_domaine=$ok_domaine && delete_records("referentiel_domain", "id", $domaine->id);
	
    return ($ok_domaine && $ok_competence && $ok_item);
}


/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will add an existing instance with new domaine.
 *
 * @param object $instance An object from the form in mod.html
 * @return new_competence_id
 **/
function referentiel_add_competence($form) {
	$new_competence_id=0;	
    // temp added for debugging
    // echo "<br />DEBUG : ADD COMPETENCE CALLED";
    // DEBUG
	// print_object($form);
    // echo "<br />";

		// NOUVElle competence
		if (isset($form->new_code_competence) && ($form->new_code_competence!="")){
			$competence = new object();
			$competence->code=($form->new_code_competence);
			$competence->description=($form->new_description_competence);
			$competence->domainid=$form->new_ref_domaine;
			$competence->sortorder=$form->new_num_competence;
			$competence->nb_item_competences=$form->new_nb_item_competences;
			// DEBUG
			// print_object($competence);
			// echo "<br />";
			$new_competence_id = insert_record("referentiel_skill", $competence);
			// echo "competence ID / $new_competence_id<br />";
		}

	return $new_competence_id; 
}

/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will update an existing instance with new referentiel.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function referentiel_update_competence($form) {
	$ok=false;	
	// DEBUG
	// print_object($form);
	// echo "<br />";

		if (isset($form->competence_id) && ($form->competence_id>0)){
			$competence = new object();
			$competence->id=$form->competence_id;
			$competence->code=($form->code);
			$competence->description=($form->description);
			$competence->domainid=$form->domainid;
			$competence->sortorder=$form->sortorder;
			$competence->nb_item_competences=$form->nb_item_competences;
			// DEBUG
			// print_object($competence);
			if (!update_record("referentiel_skill", $competence)){
				// echo "<br />ERREUR DE MISE A JOUR...";
				$ok=false;
				// exit;
			}
			else{
				// echo "<br />MISE A JOUR COMPETENCES...";
				$ok=true;
			}
		}

	return $ok;
}

/**
 * Given an item id, 
 * this function will delete of this item.
 *
 * @param int id
 * @return boolean 
 **/
function referentiel_delete_competence($competence_id){
// suppression
$ok_competence=true;
$ok_item=true;
    # Delete any dependent records here #
	// items
	if ($items = get_records("referentiel_skill_item", "skillid", $competence->id)) {
		// DEBUG
		// print_object($items);
		// echo "<br />";
		foreach ($items as $item){
			// suppression
			$ok_item=$ok_item && delete_records("referentiel_skill_item", "id", $item->id);
		}
	}	
	// suppression
	$ok_competence=$ok_competence && delete_records("referentiel_skill", "id", $competence->id);
	
    return ($ok_competence && $ok_item);
}

/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will update an existing instance with new referentiel.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function referentiel_update_item($form) {
	$ok=false;	
	// DEBUG
	// print_object($form);
	// echo "<br />";
		// ITEM COMPETENCES
		if (isset($form->item_id) && ($form->item_id>0)){
			$item = new object();
			$item->id=$form->item_id;
			$item->referentielid=$form->instance;
			$item->skillid=$form->skillid;
			$item->code=($form->code);
			$item->description=($form->description);
			$item->sortorder=$form->sortorder;
			$item->type=($form->type);
			$item->weight=$form->weight;
			$item->footprint=$form->footprint;
			// DEBUG
			// print_object($item);
			// echo "<br />";
			if (!update_record("referentiel_skill_item", $item)){
				// echo "<br />ERREUR DE MISE A JOUR ITEM COMPETENCE...";
				$ok=false;
			}
			else {
				// echo "<br />MISE A JOUR ITEM COMPETENCES...";
				$ok=true;
				// Mise à jour de la liste de competences
				$liste_codes_competence=referentiel_new_liste_codes_competence($form->referentiel_id);
				// echo "<br />LISTE_CODES_COMPETENCE : $liste_codes_competence\n";
				referentiel_set_liste_codes_competence($form->referentiel_id, $liste_codes_competence);
				$liste_empreintes_competence=referentiel_new_liste_empreintes_competence($form->referentiel_id);
				// echo "<br />LISTE_empreintes_COMPETENCE : $liste_empreintes_competence\n";
				referentiel_set_liste_empreintes_competence($form->referentiel_id, $liste_empreintes_competence);
				
			}
		}
	return $ok;
}

/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will add an existing instance with new item.
 *
 * @param object $instance An object from the form
 * @return new_item_id
 **/

function referentiel_add_item($form) {		
// NOUVEL item
	$new_item_id=0;	
		if (isset($form->new_code_item) && ($form->new_code_item!="")){
			$item = new object();
			$item->referentielid=$form->instance;
			$item->skillid=$form->new_ref_competence;
			$item->code=($form->new_code_item);
			$item->description=($form->new_description_item);
			$item->sortorder=$form->new_num_item;
			$item->type=($form->new_type_item);
			$item->weight=$form->new_poids_item;
			$item->footprint=$form->new_empreinte_item;
			
			// DEBUG
			// echo "<br />DEBUG :: lib.php :: 921<br />\n";
			// print_object($item);
			// echo "<br />";
			$new_item_id = insert_record("referentiel_skill_item", $item);
   			// echo "item ID / $new_item_id<br />";
			if ($new_item_id > 0){
				// Mise à jour de la liste de competences
				$liste_codes_competence=referentiel_new_liste_codes_competence($form->instance);
				// echo "<br />LISTE_CODES_COMPETENCE : $liste_codes_competence\n";
				referentiel_set_liste_codes_competence($form->instance, $liste_codes_competence);
				$liste_empreintes_competence=referentiel_new_liste_empreintes_competence($form->instance);
				// echo "<br />LISTE_empreintes_COMPETENCE : $liste_empreintes_competence\n";
				referentiel_set_liste_empreintes_competence($form->instance, $liste_empreintes_competence);
				
			}
		}
	return $new_item_id;
}

/**
 * Given an item id, 
 * this function will delete of this item.
 *
 * @param int id
 * @return boolean 
 **/
function referentiel_delete_item($item_id){
// suppression
	return delete_records("referentiel_skill_item", "id", $item_id);
}

/**
 * Given a doucment id, 
 * this function will permanently delete the document instance 
 *
 * @param object $id
 * @return boolean Success/Failure
 **/

function referentiel_delete_document_record($id) {
// suppression document
$ok_document=false;
	if (isset($id) && ($id>0)){
		if ($document = get_record("referentiel_document", "id", $id)) {
			//  CODE A AJOUTER SI GESTION DE FICHIERS DEPOSES SUR LE SERVEUR
			$ok_document= delete_records("referentiel_document", "id", $id);
		}
	}
	return $ok_document;
}


/**
 * Given an activity id, 
 * this function will permanently delete the activite instance 
 * and any document that depends on it. 
 *
 * @param object $id
 * @return boolean Success/Failure
 **/

function referentiel_delete_activity_record($id) {
// suppression activite + documents associes
$ok_activite=false;	
	if (isset($id) && ($id>0)){
		if ($activite = get_record("referentiel_activity", "id", $id)) {
	   		// Delete any dependent records here 
			$ok_document=true;
			if ($documents = get_records("referentiel_document", "activityid", $id)) {
				// DEBUG
				// print_object($documents);
				// echo "<br />";
				// suppression des documents associes dans la table referentiel_document
				foreach ($documents as $document){
					// suppression
					$ok_document=$ok_document && referentiel_delete_document_record($document->id);
				}
			}
			// suppression activite
			if ($ok_document){
				$ok_activite = delete_records("referentiel_activity", "id", $id);
			}
		}
	}
    return $ok_activite;
}


/**
 * Given a form, 
 * this function will permanently delete the activite instance 
 * and any document that depends on it. 
 *
 * @param object $form
 * @return boolean Success/Failure
 **/

function referentiel_delete_activity($form) {
// suppression activite + document
$ok_activite=false;
$ok_document=false;
    // DEBUG
	// echo "<br />";
	// print_object($form);
    // echo "<br />";
	if (isset($form->action) && ($form->action=="modifier_activite")){
		// suppression d'une activite et des documents associes
		if (isset($form->activite_id) && ($form->activite_id>0)){
			$ok_activite=referentiel_delete_activity_record($form->activite_id);
			// mise a zero du certificate associe a cette personne pour ce referentiel 
			referentiel_certificate_user_invalider($form->userid, $form->referentielid);
			referentiel_regenere_certificate_user($form->userid, $form->referentielid);
		}
	}
	else if (isset($form->action) && ($form->action=="modifier_document")){
		// suppression d'un document
		if (isset($form->document_id) && ($form->document_id>0)){
			$ok_document=referentiel_delete_document_record($form->document_id);
		}
	}
	
    return $ok_activite or $ok_document;
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
function referentiel_add_activity($form) {
// creation activite + document
global $USER;
    // DEBUG
    // echo "DEBUG : ADD ACTIVITY CALLED : lib.php : ligne 1033";
	// print_object($form);
    // echo "<br />";
	// referentiel
	$activite = new object();
	$activite->type_activite=($form->type_activite);
	$activite->comptencies=reference_conversion_code_2_liste_competence('/', $form->code);
	$activite->description=($form->description);
	$activite->comment=($form->comment);
	$activite->instanceid=$form->instance;
	$activite->referentielid=$form->referentielid;
	$activite->course=$form->course;
	$activite->timecreated=time();
	$activite->timemodified=time();
	$activite->approved=0;
	$activite->userid=$USER->id;	
	$activite->teacherid=0;
	
    // DEBUG
    echo "<br />DEBUG :: lib.php : 1106 : APRES CREATION\n";	
	print_object($activite);
    echo "<br />";
	
	$activite_id= insert_record("referentiel_activity", $activite);
	
	// mise a zero du certificate associe a cette personne pour ce referentiel 
	referentiel_certificate_user_invalider($activite->userid, $activite->referentielid);
	referentiel_regenere_certificate_user($activite->userid, $activite->referentielid);
	
    // echo "ACTIVITE ID / $activite_id<br />";
	if 	(isset($activite_id) && ($activite_id>0)
			&& 
			(	(isset($form->url) && !empty($form->url))
				|| 
				(isset($form->description) && !empty($form->description))
			)
	){
		$document = new object();
		$document->url=($form->url);
		$document->type=($form->type);
		$document->description=($form->description);
		$document->activityid=$activite_id;
 		
	   	// DEBUG
		// print_object($document);
    	// echo "<br />";
		
		$document_id = insert_record("referentiel_document", $document);
    	// echo "DOCUMENT ID / $document_id<br />";
	}
    return $activite_id;
}

function referentiel_update_activity($form) {
// MAJ activite + document;
$ok=true;
    // DEBUG
	// echo "<br />UPDATE ACTIVITY<br />\n";
	// print_object($form);
    // echo "<br />";
	
	if (isset($form->action) && ($form->action=="modifier_activite")){
		// activite
		$activite = new object();
		$activite->id=$form->activite_id;	
		$activite->type_activite=($form->type_activite);
		// $activite->comptencies=$form->comptencies;
		$activite->comptencies=reference_conversion_code_2_liste_competence('/', $form->code);
		$activite->description=($form->description);
		$activite->comment=($form->comment);
		$activite->instanceid=$form->instance;
		$activite->referentielid=$form->referentielid;
		$activite->course=$form->course;
		$activite->timecreated=$form->timecreated;
		$activite->timemodified=time();
		$activite->approved=$form->approved;
		$activite->userid=$form->userid;	
		$activite->teacherid=$form->teacherid;
		
	    // DEBUG
		// print_object($activite);
	    // echo "<br />";
		$ok = $ok && update_record("referentiel_activity", $activite);
		
		// mise a zero du certificate associe a cette personne pour ce referentiel 
		referentiel_certificate_user_invalider($activite->userid, $activite->referentielid);
		referentiel_regenere_certificate_user($activite->userid, $activite->referentielid);
		
	    // echo "DEBUG :: lib.php :: 1140 :: ACTIVITE ID / $activite->id<br />";
		// exit;
	}
	else if (isset($form->action) && ($form->action=="modifier_document")){
		$document = new object();
		$document->id=$form->document_id;
		$document->url=($form->url);
		$document->type=($form->type);
		$document->description=($form->description);
		$document->activityid=$form->activityid;
   		// DEBUG
		// print_object($document);
    	// echo "<br />";
		$ok= $ok && update_record("referentiel_document", $document);
		// exit;
	}
	else if (isset($form->action) && ($form->action=="creer_document")){
		$document = new object();
		$document->url=($form->url);
		$document->type=($form->type);
		$document->description=($form->description);
		$document->activityid=$form->activityid;
   		// DEBUG
		// print_object($document);
    	// echo "<br />";
		$ok = insert_record("referentiel_document", $document);
    	// echo "DOCUMENT ID / $ok<br />";
		// exit;
	}
    return $ok;
}

function referentiel_update_document($form) {
// MAJ document;
    // DEBUG
	// echo "<br />UPDATE ACTIVITY<br />\n";
	// print_object($form);
    // echo "<br />";
	if (isset($form->document_id) && $form->document_id
		&&
		isset($form->activityid) && $form->activityid){
		$document = new object();
		$document->id=$form->document_id;
		$document->url=($form->url);
		$document->type=($form->type);
		$document->description=($form->description);
		$document->activityid=$form->activityid;
   		// DEBUG
		// print_object($document);
    	// echo "<br />";
		return update_record("referentiel_document", $document);
	}
	return false;
}

function referentiel_add_document($form) {
// MAJ document;
	$id_document=0;
	if (isset($form->activityid) && $form->activityid){
		$document = new object();
		$document->url=($form->url);
		$document->type=($form->type);
		$document->description=($form->description);
		$document->activityid=$form->activityid;
   		// DEBUG
		// print_object($document);
    	// echo "<br />";
		$id_document = insert_record("referentiel_document", $document);
    	// echo "DOCUMENT ID / $ok<br />";
		// exit;
	}
    return $id_document;
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

	$return->time = date("y/m/d",$referentiel->timemodified);
	$return->time = date("Y/m/d", $referentiel->timecreated);
    $return->instance = $referentiel->id;
	$return->info = get_string('name_instance','referentiel').' : '.$referentiel->name;
	$return->info .= ", ".get_string('description','referentiel').' : '.$referentiel->description;
	
	if (isset($referentiel->referentielid) && ($referentiel->referentielid>0)){
		$referentiel_referentiel=referentiel_get_referentiel_referentiel($referentiel->referentielid);
		if ($referentiel_referentiel){
			$return->info .= ", ".get_string('name','referentiel').' : '.$referentiel_referentiel->name;		
			$return->info .= ", ".get_string('code','referentiel').' : '.$referentiel_referentiel->code;
			if (isset($referentiel_referentiel->local) && ($referentiel_referentiel->local!=0)){
				$return->info .= get_string('referentiel_global','referentiel').' : ' . get_string('no');
			}
			else{
				$return->info .= get_string('referentiel_global','referentiel').' : ' . get_string('yes');	
			}
		}
	}
    return $return;
}

/**
 * Print a detailed representation of what a user has done with 
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function referentiel_user_complete($course, $user, $mod, $referentiel) {
    $return= new Object;
	$return->time = date("Y/m/d", $referentiel->timecreated);
    $return->instance = $referentiel->id;
	$return->info = get_string('name_instance','referentiel').' : '.$referentiel->name;
	$return->info .= ", ".get_string('description','referentiel').' : '.$referentiel->description;
	$return->info .= ", ".get_string('domainlabel','referentiel').' : '.$referentiel->domainlabel;	
	$return->info .= ", ".get_string('skilllabel','referentiel').' : '.$referentiel->skilllabel;	
	$return->info .= ", ".get_string('itemlabel','referentiel').' : '.$referentiel->itemlabel;	

	if (isset($referentiel->referentielid) && ($referentiel->referentielid>0)){
		$referentiel_referentiel=referentiel_get_referentiel_referentiel($referentiel->referentielid);
		if ($referentiel_referentiel){
			$return->info .= ", ".get_string('name','referentiel').' : '.$referentiel_referentiel->name;
			$return->info .= ", ".get_string('code','referentiel').' : '.$referentiel_referentiel->code;
			$return->info .= ", ".get_string('description','referentiel').' : '.$referentiel_referentiel->description;
			$return->info .= ", ".get_string('url','referentiel').' : '.$referentiel_referentiel->url;
			$return->info .= ", ".get_string('seuil_certification','referentiel').' : '.$referentiel_referentiel->seuil_certification;
			$return->info .= ", ".get_string('modification','referentiel').' : '.date("y/m/d",$referentiel_referentiel->timemodified);	
			
			if (isset($referentiel_referentiel->local) && ($referentiel_referentiel->local!=0)){
				$return->info .= get_string('referentiel_global','referentiel').' : ' . get_string('no');
			}
			else{
				$return->info .= get_string('referentiel_global','referentiel').' : ' . get_string('yes');	
			}
		}
	}
    return $return;
}

/**
 * Given a course and a time, this module should find recent activity 
 * that has occurred in referentiel activities and print it out. 
 * Return true if there was output, or false is there was none. 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function referentiel_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG;

    return false;  //  True if anything was printed, otherwise false 
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
function referentiel_cron () {
    global $CFG;

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
   return NULL;
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
function referentiel_scale_used ($referentielid,$scaleid) {
    $return = false;

    //$rec = get_record("referentiel","id","$referentielid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}
   
    return $return;
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other referentiel functions go here.  Each of them must have a name that 
/// starts with referentiel_

/**
 * This function returns max id from table passed
 *
 * @param table name
 * @return id
 * @todo Finish documenting this function
 **/
function referentiel_get_max_id($table){
global $CFG;
	if (isset($table) && ($table!="")){
		return get_record_sql('SELECT MAX(id) FROM '. $CFG->prefix . $table);
	}
	else 
		return 0; 
}


function referentiel_get_table($id, $table) {
// retourn un objet  
    // DEBUG
    // temp added for debugging
    // echo "DEBUG : GET INSTANCE CALLED";
    // echo "<br />";
	
	// referentiel
	$objet = get_record($table, "id", $id);
    // DEBUG
	// print_object($objet);
    // echo "<br />";
	return $objet;
}

/**
 * This function returns nomber of domains from table referentiel
 *
 * @param id
 * @return int
 * @todo Finish documenting this function
 **/
function referentiel_get_nb_domaines($id){
global $CFG;
	if (isset($id) && ($id>0)){
		return get_record_sql('SELECT nb_domaines FROM '. $CFG->prefix . 'referentiel_referentiel WHERE id='.$id);
	}
	else 
		return 0; 
}

/**
 * This function returns records from table referentiel_domain
 *
 * @param ref
 * @return record
 * @todo Finish documenting this function
 **/
function referentiel_get_domaines($referentielid){
global $CFG;
	if (isset($referentielid) && ($referentielid>0)){
		return get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_domain WHERE referentielid='.$referentielid. ' ORDER BY sortorder ASC');
	}
	else 
		return 0; 
}


/**
 * This function returns nomber of competences from table referentiel_domain
 *
 * @param id
 * @return int
 * @todo Finish documenting this function
 **/
function referentiel_get_nb_competences($id){
global $CFG;
	if (isset($id) && ($id>0)){
		return get_records_sql('SELECT nb_competences FROM '. $CFG->prefix . 'referentiel_domain WHERE id='.$id);
	}
	else 
		return 0; 
}

/**
 * This function returns records from table referentiel_skill_item
 *
 * @param ref
 * @return id
 * @todo Finish documenting this function
 **/
function referentiel_get_competences($domainid){
global $CFG;
	if (isset($domainid) && ($domainid>0)){
		return get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_skill WHERE domainid='.$domainid. ' ORDER BY sortorder ASC');
	}
	else 
		return 0; 
}

/**
 * This function returns nomber of items from table referentiel_skill
 *
 * @param id
 * @return int
 * @todo Finish documenting this function
 **/
function referentiel_get_nb_item_competences($id){
global $CFG;
	if (isset($id) && ($id>0)){
		return get_record_sql('SELECT nb_item_competences FROM '. $CFG->prefix . 'referentiel_skill WHERE id='.$id.' ');
	}
	else 
		return 0; 
}

/**
 * This function returns records from table referentiel_skill_item
 *
 * @param id
 * @return int
 * @todo Finish documenting this function
 **/
function referentiel_get_item_competences($skillid){
global $CFG;
	if (isset($skillid) && ($skillid>0)){
		return get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_skill_item WHERE skillid='.$skillid. ' ORDER BY sortorder ASC ');
	}
	else 
		return 0; 
}

/**
 * This function returns an int from table referentiel_skill_item
 *
 * @param id
 * @return int of poids
 * @todo Finish documenting this function
 **/
function referentiel_get_poids_item($code, $referentiel_id){
global $CFG;
	if (isset($code) && ($code!="")){
		$record=get_record_sql("SELECT weight FROM ". $CFG->prefix . "referentiel_skill_item WHERE code='".$code."' AND referentielid=".$referentiel_id." ");
		if ($record){
			return $record->weight;
		}
	}
	return 0;
}


/**
 * This function returns an int from table referentiel_skill_item
 *
 * @param referentiel id
 * @return int of empreinte
 * @todo Finish documenting this function
 **/
function referentiel_get_empreinte_item($code, $referentiel_id){
global $CFG;
	if (isset($code) && ($code!="")){
		$record=get_record_sql("SELECT footprint FROM ". $CFG->prefix . "referentiel_skill_item WHERE code='".$code."' AND referentielid=".$referentiel_id." ");
		if ($record){
			return $record->footprint;
		}
	}
	return 0;
}


/**
 * This function returns an int from table referentiel_skill_item
 *
 * @param referentiel id
 * @return string of poids
 * @todo Finish documenting this function
 **/
function referentiel_get_liste_poids($referentiel_id){
global $CFG;
$liste="";
	$records=get_records_sql("SELECT id, description, weight FROM ". $CFG->prefix . "referentiel_skill_item WHERE referentielid=".$referentiel_id." ");
	if ($records){
		 foreach ($records as $record) {
		 	$liste.= $record->description.'#'.$record->weight.'|';
		 }
	}
	return $liste;
}



/**
 * This function returns an string from table referentiel_skill_item
 *
 * @param id
 * @return int
 * @todo Finish documenting this function
 **/
function referentiel_get_description_item($code){
global $CFG;
	if (isset($code) && ($code!="")){
		$record=get_record_sql("SELECT description FROM ". $CFG->prefix . "referentiel_skill_item WHERE code='".$code."' ");
		if ($record){
			return $record->description;
		}
	}
	return "";
}


/**
 * This function returns records from table referentiel
 *
 * @param $id : int id refrentiel to filter
 * $params filtering clause
 * @return int
 * @todo Finish documenting this function
 **/
function referentiel_filtrer($id, $params){
global $CFG;
	if (isset($id) && ($id>0)){
		$where = "WHERE id=".$id." ";
		if (isset($params)){
			if (isset($params->filtrerinstance) && ($params->filtrerinstance!=0)){
				if (isset($params->localinstance) && ($params->localinstance==0)){
					$where = " AND local==0 ";
				}
				else {
					$where = " AND local!=0 ";
				}
			}
		}
		$record = get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_referentiel '.$where.' ');
		if ($record){
			return $record->id;
		}
		else {
			return 0;
		}
	}
	else{
		return 0;
	}
}

/**
 * This function returns records from table referentiel_referentiel
 *
 * @param id
 * @return int
 * @todo Finish documenting this function
 **/
function referentiel_get_referentiel_referentiel($id){
global $CFG;
	if (isset($id) && ($id>0)){
		return get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_referentiel WHERE id='.$id.' ');
	}
	else 
		return 0; 
}



/**
 * This function returns string from table referentiel_referentiel
 *
 * @param id
 * @return int
 * @todo Finish documenting this function
 **/
function referentiel_get_nom_referentiel($id){
global $CFG;
$s="";
	if (isset($id) && ($id>0)){
		$record=get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_referentiel WHERE id='.$id.' ');
		if ($record){
			$s=$record->name;
		}
	}
	return $s; 
}


/**
 * This function returns records from table referentiel
 *
 * @param $params filtering clause
 * @return records
 * @todo Finish documenting this function
 **/
function referentiel_get_referentiel_referentiels($params){
global $CFG;
	$where = "";
	if (isset($params)){
		if (isset($params->filtrerinstance) && ($params->filtrerinstance!=0)){
			if (isset($params->localinstance) && ($params->localinstance==0)){
				$where = " WHERE local==0 ";
			}
			else {
				$where = " WHERE local!=0 ";
			}
		}
	}
	
	return get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_referentiel '.$where.' ORDER BY id ASC ');
}


/**
 * This function returns records from table referentiel_skill_item
 *
 * @param id
 * @return int
 * @todo Finish documenting this function
 **/
function referentiel_get_referentiel($id){
global $CFG;
	if (isset($id) && ($id>0)){
		return get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel WHERE id='.$id.' ');
	}
	else 
		return 0; 
}


/**
 * This function returns record from table referentiel_activity
 *
 * @param id
 * @return object
 * @todo Finish documenting this function
 **/
function referentiel_get_activite($id){
global $CFG;
	if (isset($id) && ($id>0)){
		return get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_activity WHERE id='.$id.' ');
	}
	else 
		return 0; 
}

/**
 * This function returns records from table referentiel_activity
 *
 * @param id reference activite
 * @param select clause : ' AND champ=valeur,  ... '
 * @param order clause : ' champ ASC|DESC, ... ' 
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_users_activites($referentiel_id, $select="", $order=""){
global $CFG;
	if (isset($referentiel_id) && ($referentiel_id>0)){
		if (empty($order)){
			$order= 'userid ASC ';
		}
		return get_records_sql('SELECT DISTINCT userid FROM '. $CFG->prefix . 'referentiel_activity WHERE referentielid='.$referentiel_id.' '.$select.' ORDER BY '.$order.' ');
	}
	else 
		return 0; 
}

/**
 * This function returns records from table referentiel_activity
 *
 * @param id reference activite
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_teachers_activites($referentiel_id){
global $CFG;
	if (isset($referentiel_id) && ($referentiel_id>0)){
		return get_records_sql('SELECT DISTINCT teacherid FROM '. $CFG->prefix . 'referentiel_activity WHERE referentielid='.$referentiel_id.' ORDER BY teacherid ASC ');
	}
	else 
		return 0; 
}



/**
 * This function returns records from table referentiel_activity
 *
 * @param id reference activite
 * @param select clause : ' AND champ=valeur,  ... '
 * @param order clause : ' champ ASC|DESC, ... '
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_activites($referentiel_id, $select="", $order=""){
global $CFG;
	if (isset($referentiel_id) && ($referentiel_id>0)){
		if (empty($order)){
			$order= 'userid ASC, timecreated DESC ';
		}
		return get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_activity WHERE referentielid='.$referentiel_id.' '.$select.' ORDER BY '.$order.' ');
	}
	else 
		return 0; 
}


/**
 * This function returns record document from table referentiel_document
 *
 * @param id activityid
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_documents($activite_id){
global $CFG;
	if (isset($activite_id) && ($activite_id>0)){
		return get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_document WHERE activityid='.$activite_id.' ORDER BY id ASC ');
	}
	else 
		return 0; 
}


function referentiel_user_can_addactivity($referentiel, $currentgroup, $groupmode) {
    global $USER;

    if (!$cm = get_coursemodule_from_instance('referentiel', $referentiel->id, $referentiel->course)) {
        error('Course Module ID was incorrect');
    }
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    if (!has_capability('mod/referentiel:write', $context)) {
        return false;
    }

    if (!$groupmode or has_capability('moodle/site:accessallgroups', $context)) {
        return true;
    }

    if ($currentgroup) {
        return ismember($currentgroup);
    } else {
        //else it might be group 0 in visible mode
        if ($groupmode == VISIBLEGROUPS){
            return true;
        } else {
            return false;
        }
    }
}


function referentiel_activite_isowner($id){
global $USER;
	if (isset($id) && ($id>0)){
		$record=get_record("referentiel_activity", "id", "$id");
		// DEBUG
		// echo "<br >USERID : $USER->id ; OWNER : $record->userid\n";
		return ($USER->id == $record->userid);
	}
	else 
		return false; 
} 

// Liste des codes de competences du référentiel
function referentiel_get_liste_codes_competence($id){
// retourne la liste des codes de competences pour la table referentiel
global $CFG;
	if (isset($id) && ($id>0)){
		$record_r=get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_referentiel WHERE id='.$id.' ');	    
		if ($record_r){
    		// afficher
			// DEBUG
			// echo "<br/>DEBUG ::<br />\n";
			// print_r($record_r);
			return ($record_r->liste_codes_competence);
		}
	}
	return 0;
}


// Liste des codes de competences du référentiel
function referentiel_new_liste_codes_competence($id){
// regenere la liste des codes de competences pour la table referentiel
global $CFG;
$liste_codes_competence="";
	if (isset($id) && ($id>0)){
		$record_r=get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_referentiel WHERE id='.$id.' ');	    
		if ($record_r){
    		// afficher
			// DEBUG
			// echo "<br/>DEBUG ::<br />\n";
			// print_r($record_r);
			$old_liste_codes_competence=$record_r->liste_codes_competence;
			$liste_codes_competence="";
			// charger les domaines associes au referentiel courant
			$referentiel_id=$id; // plus pratique
			// LISTE DES DOMAINES
			$records_domaine = get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_domain WHERE referentielid='.$referentiel_id. ' ORDER BY sortorder ASC ');
			if ($records_domaine){
    			// afficher
				// DEBUG
				// echo "<br/>DEBUG ::<br />\n";
				// print_r($records_domaine);
				foreach ($records_domaine as $record_d){
        			$domaine_id=$record_d->id;
					$records_competences = get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_skill WHERE domainid='.$domaine_id. ' ORDER BY sortorder ASC ');
			   		if ($records_competences){
						// DEBUG
						// echo "<br/>DEBUG :: COMPETENCES <br />\n";
						// print_r($records_competences);
						foreach ($records_competences as $record_c){
							$competence_id=$record_c->id;
							// ITEM
							$compteur_item=0;
							$records_items = get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_skill_item WHERE skillid='.$competence_id. ' ORDER BY sortorder ASC ');
					    	if ($records_items){
								// DEBUG
								// echo "<br/>DEBUG :: ITEMS <br />\n";
								// print_r($records_items);
								foreach ($records_items as $record_i){
									$liste_codes_competence.=$record_i->code."/";
								}
							}
						}
					}
				}
			}
		}
	}
	return $liste_codes_competence;
}

/**
 * Given an id referentiel, 
 * will update an existing instance with new liste_codes_competence.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function referentiel_set_liste_codes_competence($id, $liste_codes_competence){
	if (isset($id) && ($id>0)){
		$referentiel=referentiel_get_referentiel_referentiel($id);
		$referentiel->name=addslashes($referentiel->name);
		$referentiel->code=addslashes($referentiel->code);
		$referentiel->description=addslashes($referentiel->description);
	    $referentiel->timemodified = time();
		$referentiel->liste_codes_competence=$liste_codes_competence;
	    // DEBUG
		// echo "<br />DEBUG :: lib.php :: 1857";
		// print_object($referentiel);
	    // echo "<br />";
	    return(update_record("referentiel_referentiel", $referentiel));
	}
	return false;
}

/**
 * Given an array , 
 * return a new liste_codes_competence.
 *
 * @param array $instance An object from the form in mod_activite.html
 * @return string
 **/
function reference_conversion_code_2_liste_competence($separateur, $tab_code_item){
$lc="";
// print_r($tab_code_item);
// echo "<br />DEBUG\n";

	if (count($tab_code_item)>0){
		for ($i=0; $i<count($tab_code_item); $i++){
			$lc.=$tab_code_item[$i].$separateur;
		}
	}
	return $lc;
}


// Liste des empreintes de competences du référentiel
function referentiel_get_liste_empreintes_competence($id){
// retourne la liste des empreintes de competences pour la table referentiel
global $CFG;
	if (isset($id) && ($id>0)){
		$record_r=get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_referentiel WHERE id='.$id);	    
		if ($record_r){
    		// afficher
			// DEBUG
			// echo "<br/>DEBUG ::<br />\n";
			// print_r($record_r);
			return ($record_r->liste_empreintes_competence);
		}
	}
	return 0;
}


// Liste des empreintes de competences du référentiel
function referentiel_new_liste_empreintes_competence($id){
// regenere la liste des empreintes de competences pour la table referentiel
global $CFG;
$liste_empreintes_competence="";
	if (isset($id) && ($id>0)){
		$record_r=get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_referentiel WHERE id='.$id);	    
		if ($record_r){
    		// afficher
			// DEBUG
			// echo "<br/>DEBUG ::<br />\n";
			// print_r($record_r);
			$old_liste_empreintes_competence=$record_r->liste_empreintes_competence;
			$liste_empreintes_competence="";
			// charger les domaines associes au referentiel courant
			$referentiel_id=$id; // plus pratique
			// LISTE DES DOMAINES
			$records_domaine = get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_domain WHERE referentielid='.$referentiel_id. ' ORDER BY sortorder ASC');
			if ($records_domaine){
    			// afficher
				// DEBUG
				// echo "<br/>DEBUG ::<br />\n";
				// print_r($records_domaine);
				foreach ($records_domaine as $record_d){
        			$domaine_id=$record_d->id;
					$records_competences = get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_skill WHERE domainid='.$domaine_id. ' ORDER BY sortorder ASC');
			   		if ($records_competences){
						// DEBUG
						// echo "<br/>DEBUG :: COMPETENCES <br />\n";
						// print_r($records_competences);
						foreach ($records_competences as $record_c){
							$competence_id=$record_c->id;
							// ITEM
							$compteur_item=0;
							$records_items = get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_skill_item WHERE skillid='.$competence_id. ' ORDER BY sortorder ASC');
					    	if ($records_items){
								// DEBUG
								// echo "<br/>DEBUG :: ITEMS <br />\n";
								// print_r($records_items);
								foreach ($records_items as $record_i){
									$liste_empreintes_competence.=$record_i->footprint."/";
								}
							}
						}
					}
				}
			}
		}
	}
	return $liste_empreintes_competence;
}

/**
 * Given an id referentiel, 
 * will update an existing instance with new liste_empreintes_competence.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function referentiel_set_liste_empreintes_competence($id, $liste_empreintes_competence){
	if (isset($id) && ($id>0)){
		$referentiel=referentiel_get_referentiel_referentiel($id);
	    $referentiel->timemodified = time();
		$referentiel->liste_empreintes_competence=$liste_empreintes_competence;
	    // DEBUG
		// print_object($referentiel);
	    // echo "<br />";
	    return(update_record("referentiel_referentiel", $referentiel));
	}
	return false;
}


function referentiel_editingteachers($id_course) {
//liste des profs d'un cours
// version  MOODLE 1.7 !!!
    $context = get_context_instance(CONTEXT_COURSE, $id_course);
    $profs=get_users_by_capability($context,"moodle/legacy:editingteacher", "firstname,lastname,email","lastname");
    $liste="";
    foreach ($profs as $p){
       $liste .=$p->firstname. ' '.$p->lastname.'<'.$p->email.'>';
	}
   return $liste;
}

function referentiel_get_user_info($user_id) {
// retourne le NOM prenom à partir de l'id
global $CFG;
	$user_info="";
	if (isset($user_id) && ($user_id>0)){
		$sql = "SELECT firstname, lastname FROM {$CFG->prefix}user as a WHERE a.id = ".$user_id." ";
		$user = get_record_sql($sql);
		if ($user){
			$user_info=$user->firstname.' '.$user->lastname;
		}
	}
	return $user_info;
}

// CERTIFICATS

/**
 * This function returns records list of users from table referentiel_certificate
 *
 * @param userid reference user id
 * @param referentiel_id reference referentiel
 * @return object
 * @todo Finish documenting this function
 **/
function referentiel_certificate_user_exists($userid, $referentiel_id){
global $CFG;
	if (isset($userid) && ($userid>0) && isset($referentiel_id) && ($referentiel_id>0)){
		$r=get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_certificate WHERE referentielid='.$referentiel_id.' AND userid='.$userid.' ');
		if ($r){
			// echo "<br />\n";
			// print_r($r);
			return ($r->id);
		}
	}
	return 0; 
}

/**
 * This function returns records list of users from table referentiel_certificate
 *
 * @param userid reference user id
 * @param referentiel_id reference referentiel
 * @return object
 * @todo Finish documenting this function
 **/
function referentiel_certificate_user($userid, $referentiel_id){
global $CFG;
	if (isset($userid) && ($userid>0) && isset($referentiel_id) && ($referentiel_id>0)){
		return get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_certificate WHERE referentielid='.$referentiel_id.' AND userid='.$userid.' ');
	}
	else 
		return 0; 
}


/**
 * This function returns records list of users from table referentiel_certificate
 *
 * @param id reference certificat
 * @param select clause : ' AND champ=valeur,  ... '
 * @param order clause : ' champ ASC|DESC, ... ' 
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_users_certificats($referentiel_id, $select="", $order=""){
global $CFG;
	if (isset($referentiel_id) && ($referentiel_id>0)){
		if (empty($order)){
			$order= 'userid ASC ';
		}
		return get_records_sql('SELECT DISTINCT userid FROM '. $CFG->prefix . 'referentiel_certificate WHERE referentielid='.$referentiel_id.' '.$select.' ORDER BY '.$order.' ');
	}
	else 
		return 0; 
}

/**
 * This function returns records list of teachers from table referentiel_certificate
 *
 * @param id reference certificat
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_teachers_certificats($referentiel_id){
global $CFG;
	if (isset($referentiel_id) && ($referentiel_id>0)){
		return get_records_sql('SELECT DISTINCT teacherid FROM '. $CFG->prefix . 'referentiel_certificate WHERE referentielid='.$referentiel_id.' ORDER BY teacherid ASC ');
	}
	else 
		return 0; 
}

/**
 * This function returns record certificate from table referentiel_certificate
 *
 * @param userid reference user id of certificat
 * @param referentielid reference referentiel id of certificate 
 * @return object
 * @todo Finish documenting this function
 **/
function referentiel_get_certificate_user($userid, $referentielid){
global $CFG;
	if (isset($userid) && ($userid>0) && isset($referentielid) && ($referentielid>0)){
		return get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_certificate WHERE referentielid='.$referentielid.' AND userid='.$userid.' ');
	}
	else {
		return false; 
	}
}

/**
 purge 
*/

function referentiel_purge_dernier_separateur($s, $sep){
	if ($s){
		$s=trim($s);
		if ($sep){
			$pos = strrpos($s, $sep);
			if ($pos === false) { // note : trois signes égal  
				// pas trouvé
			}
			else{
				// supprimer le dernier "/"
				if ($pos==strlen($s)-1){
					return substr($s,0, $pos);
				}
			}
		}
	}
	return $s;
}

/**
 * This function get all valid competencies in activite and return a competencies list
 *
 * @param userid reference user id
 * @param $referentielid reference a referentiel id (not an instance of it !)
 * @return bolean
 * @todo Finish documenting this function
 * algorithme : cumule pour chaque competences le nombre d'activités où celle ci est validee
 **/
function referentiel_genere_certificate_liste_competences($userid, $referentielid){
	$t_liste_competences_valides=array();
	$t_competences_valides=array();
	$t_competences_referentiel=array(); // les competences du référentiel
	
	$liste_competences_valides=""; // la liste sous forme de string
	$jauge_competences=""; // la juge sous forme CODE_COMP_0:n0/CODE_COMP_1:n1/...
	// avec 0 si competence valide 0 fois, n>0 sinon
	
	if (isset($userid) && ($userid>0) && isset($referentielid) && ($referentielid>0)){
		// liste des competences definies dans le referentiel
		$liste_competences_referentiel=referentiel_purge_dernier_separateur(referentiel_get_liste_codes_competence($referentielid), "/");
		$t_competences_referentiel=explode("/", $liste_competences_referentiel);
		// creer un tableau dont les indices sont les codes de competence
		while (list($key, $val) = each($t_competences_referentiel)) {    
			$t_competences_valides[$val]=0;
		}
		// collecter les activites validees
		$select=" AND approved!=0 AND userid=".$userid." ";
		$order= ' id ASC ';
		$records_activite = referentiel_get_activites($referentielid, $select, $order);
		if (!$records_activite){
			return false;
		}
		// collecter les competences
		foreach ($records_activite  as $activite){
			$t_liste_competences_valides[]=referentiel_purge_dernier_separateur($activite->comptencies, "/");
		}
 		for ($i=0; $i<count($t_liste_competences_valides); $i++){
			$tcomp=explode("/", $t_liste_competences_valides[$i]);
			while (list($key, $val) = each($tcomp)) {    
				// echo "$key => $val\n";
				$t_competences_valides[$val]++;
			}
		}
		$i=0;
		while (list($key, $val) = each($t_competences_valides)) {    
			// echo "$key => $val\n";
			if ((!is_numeric($key) && ($key!=""))  && ($val!="") && ($val>0)){
				$liste_competences_valides.=$key."/";
			}
			$jauge_competences.=$key.":".trim($val)."/";
		}
	}
	return $jauge_competences; 
}

/**
 * This function get a competencies list and return a float
 *
 * @param userid reference user id
 * @param $referentielid reference a referentiel id (not an instance of it !)
 * @return bolean
 * @todo Finish documenting this function
 **/
function referentiel_evaluation($listecompetences, $referentiel_id){
//A.1.1:0/A.1.2:0/A.1.3:0/A.1.4:0/A.1.5:0/A.2.1:0/A.2.2:0/A.2.3:0/A.3.1:0/A.3.2:0/A.3.3:0/A.3.4:0/B.1.1:0/B.1.2:0/B.1.3:0/B.2.1:0/B.2.2:0/B.2.3:0/B.2.4:0/B.3.1:0/B.3.2:0/B.3.3:0/B.3.4:0/B.3.5:0/B.4.1:1/B.4.2:1/B.4.3:0/
	// DEBUG
	// echo "<br />LISTE ".$listecompetences."\n";
	$evaluation=0.0;
	$tcode=array();
	$tcode=explode("/",$listecompetences);
	for ($i=0; $i<count($tcode); $i++){
		$tvaleur=explode(":",$tcode[$i]);
		
		$code="";
		$svaleur="";
		
		if (isset($tvaleur[0])){ // le code
			$code=trim($tvaleur[0]);
		}
		if (isset($tvaleur[1])){ // la valeur
			$svaleur=trim($tvaleur[1]);
		} 
		// DEBUG
		// echo "<br />DEBUG :: lib.php : 2260 :: CODE : ".$code." VALEUR : ".$svaleur."\n";
		if (($code!="") && ($svaleur!="")){ 
			$poids=referentiel_get_poids_item($code, $referentiel_id);
			$empreinte=referentiel_get_empreinte_item($code, $referentiel_id);
			// echo "<br />POIDS : ".$poids."\n";
			if ($empreinte)
				$evaluation+= ( $poids * $svaleur / $empreinte);
			else
				$evaluation+= ( $poids * $svaleur);
		}
	}
	// echo "<br />EVALUATION : ".$evaluation."\n";
	return $evaluation;
}


/**
 * This function get all usr role student in courrent course
 *
 * @param courseid reference course id
  * @param contexteid reference context id
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_course_users($referentiel_instance){
global $CFG;
    if ($cm = get_coursemodule_from_instance('referentiel', $referentiel_instance->id, $referentiel_instance->course)) {
	    if ($context = get_context_instance(CONTEXT_MODULE, $cm->id)){
		
			// SQL
			/*
		    $select = "SELECT DISTINCT u.id FROM {$CFG->prefix}user u INNER JOIN
    {$CFG->prefix}role_assignments r on u.id=r.userid LEFT OUTER JOIN
    {$CFG->prefix}user_lastaccess ul on (r.userid=ul.userid and ul.courseid = ".$referentiel_instance->course.")
	WHERE (r.contextid = ".$context->id.")
        AND u.deleted = 0 AND r.roleid = 5 
        AND (ul.courseid = ".$referentiel_instance->course." OR ul.courseid IS NULL)
        AND u.username != 'guest'
 ";
 */
 		    $select = "SELECT DISTINCT u.id FROM {$CFG->prefix}user u 
LEFT OUTER JOIN
    {$CFG->prefix}user_lastaccess ul on (ul.courseid = ".$referentiel_instance->course.")
	WHERE u.deleted = 0  
        AND (ul.courseid = ".$referentiel_instance->course." OR ul.courseid IS NULL)
        AND u.username != 'guest'
 ";
		// DEBUG
			// echo "<br /> DEBUG <br />\n";
			// echo "<br /> lib.php :: referentiel_get_course_users() :: 1986<br />$select<br />\n";
		
			$ru=get_records_sql($select);
			// print_r($ru);
			// exit;
			return $ru;
		}
	}
	return NULL;
}


/**
 * This function set all certificates
 *
 * @param $referentiel_instance reference an instance of referentiel !)
 * @return bolean
 * @todo Finish documenting this function
 **/
function referentiel_regenere_certificats($referentiel_instance){
	if ($referentiel_instance){
		$records_users=referentiel_get_course_users($referentiel_instance);
		// echo "<br /> lib.php :: referentiel_get_course_users() :: 2018<br />\n";		
		if ($records_users){
			foreach ($records_users as $record_u){
				// echo "<br />\n";
				// print_r($record_u);
				referentiel_regenere_certificate_user($record_u->id, $referentiel_instance->referentielid);	
			}
		}
	}
}

/**
 * This function set all certificates
 *
 * @param $referentiel_instance reference an instance of referentiel !)
 * @return bolean
 * @todo Finish documenting this function
 **/
function referentiel_regenere_certificate_user($userid, $referentielid){
	if ($referentielid && $userid){
		if (!referentiel_certificate_user_exists($userid, $referentielid)){
			// CREER ce certificat
			referentiel_genere_certificat($userid, $referentielid);
		}
		if (!referentiel_certificate_user_valide($userid, $referentielid)){
			// METTRE A JOUR ce certificat
			referentiel_genere_certificat($userid, $referentielid);
		}
	}
}


/**
 * This function  create / update with valid competencies a certificate for the userid
 *
 * @param userid reference user id
 * @param $referentielid reference a referentiel id (not an instance of it !)
 * @return bolean
 * @todo Finish documenting this function
 **/
function referentiel_genere_certificat($userid, $referentielid){
	$certificate_id=0; // id du certificate cree / modifie
	if (isset($userid) && ($userid>0) && isset($referentielid) && ($referentielid>0)){
		$competences_certificat=referentiel_genere_certificate_liste_competences($userid, $referentielid);
		if ($competences_certificat!=""){
			// si existe update
			if ($certificat=referentiel_get_certificate_user($userid, $referentielid)){
				$certificate_id=$certificat->id;
				
				// update ?
				
				if ($certificat->verrou==0){
					$certificat->comment=addslashes($certificat->comment);
					$certificat->decision_jury=addslashes($certificat->decision_jury);
					$certificat->evaluation=addslashes($certificat->evaluation);
					$certificat->competences_certificat=$competences_certificat;
					$certificat->evaluation=referentiel_evaluation($competences_certificat, $referentielid);
					$certificat->valide=1;					
					if(!update_record("referentiel_certificate", $certificat)){
						// DEBUG 
						// echo "<br /> ERREUR UPDATE CERTIFICAT\n";
					}
				}
			}
			else {
				// sinon creer
				$certificate = new object();
				$certificat->competences_certificat=$competences_certificat;
				$certificat->comment="";
				$certificat->decision_jury="";
				$certificat->date_decision=0;
				$certificat->referentielid=$referentielid;
				$certificat->userid=$userid;	
				$certificat->teacherid=0;
				$certificat->verrou=0;
				$certificat->valide=1;
				$certificat->evaluation=referentiel_evaluation($competences_certificat, $referentielid);
    			// DEBUG
				// print_object($certificat);
    			// echo "<br />";
				$certificate_id= insert_record("referentiel_certificate", $certificat);
			}
		}
	}
	return $certificate_id;
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
function referentiel_add_certificat($form) {
// creation certificat
global $USER;
    // DEBUG
    //echo "DEBUG : ADD certificate CALLED";
	//print_object($form);
    //echo "<br />";
	// referentiel
	$certificate = new object();
	$certificat->competences_certificat=$form->competences_certificat;
	$certificat->comment=($form->comment);
	$certificat->decision_jury=($form->decision_jury);
	$certificat->date_decision=time();
	$certificat->referentielid=$form->referentielid;
	$certificat->userid=$USER->id;	
	$certificat->teacherid=$USER->id;
	$certificat->verrou=0;
	$certificat->valide=$form->valide;
	$certificat->evaluation=referentiel_evaluation($form->competences_certificat, $form->referentielid);	
    // DEBUG
	//print_object($certificat);
    //echo "<br />";
	
	$certificate_id= insert_record("referentiel_certificate", $certificat);
    // echo "certificate ID / $certificate_id<br />";
    // DEBUG
    return $certificate_id;
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
function referentiel_update_certificat($form) {
// MAJ certificat
$ok=true;
    // DEBUG
    // echo "DEBUG : UPDATE certificate CALLED";
	// print_object($form);
    // echo "<br />";
	// certificat
	if (isset($form->action) && ($form->action=="modifier_certificat")){
		$certificate = new object();
		$certificat->id=$form->certificate_id;
		$certificat->comment=($form->comment);
		$certificat->competences_certificat=$form->competences_certificat;
		$certificat->decision_jury=($form->decision_jury);
		if (empty($form->date_decision) || ($form->date_decision==0)){
			$form->date_decision=time();
		}
		else{
			$certificat->date_decision=$form->date_decision;
		}
		$certificat->referentielid=$form->referentielid;
		$certificat->userid=$form->userid;
		$certificat->teacherid=$form->teacherid;
		$certificat->verrou=$form->verrou;
		$certificat->valide=$form->valide;
		$certificat->evaluation=referentiel_evaluation($form->competences_certificat, $form->referentielid);	
		
	    // DEBUG
		// print_object($certificat);
	    // echo "<br />";
		if(!update_record("referentiel_certificate", $certificat)){
			//echo "<br /> ERREUR UPDATE CERTIFICAT\n";
			$ok=false;
		}
		else {
			// echo "<br /> UPDATE certificate $certificat->id\n";		
			$ok=true;
		}
		// exit;
		return $ok; 
	}
}

function referentiel_user_can_add_certificat($referentiel, $currentgroup, $groupmode) {
    global $USER;

    if (!$cm = get_coursemodule_from_instance('referentiel', $referentiel->id, $referentiel->course)) {
        error('Course Module ID was incorrect');
    }
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    if (!has_capability('mod/referentiel:writecertificat', $context)) {
        return false;
    }

    if (!$groupmode or has_capability('moodle/site:accessallgroups', $context)) {
        return true;
    }

    if ($currentgroup) {
        return ismember($currentgroup);
    } else {
        //else it might be group 0 in visible mode
        if ($groupmode == VISIBLEGROUPS){
            return true;
        } else {
            return false;
        }
    }
}


function referentiel_certificate_isowner($id){
global $USER;
	if (isset($id) && ($id>0)){
		$record=get_record("referentiel_certificate", "id", "$id");
		// DEBUG
		// echo "<br >USERID : $USER->id ; OWNER : $record->userid\n";
		return ($USER->id == $record->userid);
	}
	else 
		return false; 
} 


/**
 * This function returns record of certificate from table referentiel_certificate
 *
 * @param id reference certificate id
 * @return object
 * @todo Finish documenting this function
 **/
function referentiel_get_certificat($id){
global $CFG;
	if (isset($id) && ($id>0)){
		return get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_certificate WHERE id='.$id.' ');
	}
	else 
		return 0; 
}

/**
 * This function returns records of certificate from table referentiel_certificate
 *
 * @param id reference referentiel (no instance)
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_certificats($referentiel_id, $select="", $order=""){
global $CFG;
	if (isset($referentiel_id) && ($referentiel_id>0)){
		if (empty($order)){
			$order= 'userid ASC ';
		}
		return get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_certificate WHERE referentielid='.$referentiel_id.' '.$select.' ORDER BY '.$order.' ');
	}
	else 
		return 0; 
}


/**
 * Given an certificate id, 
 * this function will permanently delete the certificate instance  
 *
 * @param object $id
 * @return boolean Success/Failure
 **/

function referentiel_delete_certificate_record($id) {
// suppression certificat
$ok_certificat=false;	
	if (isset($id) && ($id>0)){
		if ($certificate = get_record("referentiel_certificate", "id", $id)) {
			// suppression 
			$ok_certificate = delete_records("referentiel_certificate", "id", $id);
		}
	}
    return $ok_certificat;
}

/**
 * Given a userid  and referentiel id
 * this function will set certificate valide to 0
 *
 * @param $userid user id
  * @param $referentielid refrentiel id
 * @return 
 **/

function referentiel_certificate_user_invalider($userid, $referentielid){
	if ($userid && $referentielid){
		$certificate = get_record('referentiel_certificate', 'userid', $userid, 'referentielid', $referentielid);
		if ($certificat) {
			// if ($record->verrou==0) // BUG car en cas de deverrouillage les activites crees entretemps ne seraient pas prises en compte
				$certificat->valide=0;
				// DEBUG
				// print_r($record);
				// echo "<br />\n";
				$certificat->comment=addslashes($certificat->comment);
				$certificat->decision_jury=addslashes($certificat->decision_jury);
				$certificat->evaluation=addslashes($certificat->evaluation);
	            update_record('referentiel_certificate', $certificat);
			// }
        }
	}
}

/**
 * Given a userid and referentiel id
 * this function will get valide 
 *
 * @param $userid user id
  * @param $referentielid refrentiel id
 * @return 
 **/

function referentiel_certificate_user_valide($userid, $referentielid){
	if ($userid && $referentielid){
		$record = get_record('referentiel_certificate', 'userid', $userid, 'referentielid', $referentielid);
		if ($record) {
			return (($record->valide==1) or ($record->verrou==1));
        }
	}
	return false;
}

/************************************************************************
 * takes a list of records, a search string,                            *
 * input @param array $records   of users                               *
 *       @param string $search                                          *
 * output null                                                          *
 ************************************************************************/
function referentiel_select_users_certificat($record_users, $appli="certificate.php"){
global $cm;
global $mode;
global $course;
$s="";
	if ($record_users){
		$s.='<div align="center">
<form name="form" method="post" action="'.$appli.'?id='.$cm->id.'&amp;action=selectuser">'."\n"; 
		$s.='<select name="userid" id="userid" size="2">'."\n";
		$s.='<option value="0" SELECTED>'.get_string('choisir', 'referentiel').'</option>'."\n";		
	    foreach ($record_users as $record_u) {   // liste d'id users
			// 
			$user_info=referentiel_get_user_info($record_u->userid);
			$s.='<option value="'.$record_u->userid.'">'.$user_info.'</option>'."\n";
    	}
		$s.='</select>
<!-- These hidden variables are always the same -->
<input type="hidden" name="course"        value="'.$course->id.'" />
<input type="hidden" name="sesskey"     value="'.sesskey().'" />
<input type="hidden" name="mode"          value="listcertif" />
<input type="submit" value="'.get_string('select', 'referentiel').'" />
</form>
</div>'."\n";
	}
	return $s;
}

    /**
     * get directory into which export is going 
     * @return string file path
	 * @ input $course_id : id of current course
	 * @ input $sous_repertoire : a relative path	 
     */
    function referentiel_get_export_dir($course_id, $sous_repertoire="") {
	global $CFG;
	/*
    // ensure the files area exists for this course	
	// $path_to_data=referentiel_get_export_dir($course->id,"$referentiel->id/$USER->id");
	$path_to_data=referentiel_get_export_dir($course->id);
    make_upload_directory($path_to_data);	
	*/
        $dirname = get_string('exportfilename', 'referentiel');
        $path = $course_id.'/'.$CFG->moddata.'/'.$dirname; 
		if ($sous_repertoire!=""){
			$pos=strpos($sous_repertoire,'/');
			if (($pos===false) || ($pos!=0)){ // separateur pas en tete
				// RAS
			}
			else {
				$sous_repertoire = substr($sous_repertoire,$pos+1);
			}
			$path .= '/'.$sous_repertoire;
		}
        return $path;
    }


    /**
     * display an url accorging to moodle file mangement 
     * @return string active link
	 * @ input $url : an uri
	 * @ input $etiquette : a label 
     */
    function referentiel_affiche_url($url, $etiquette="", $cible="") {
	global $CFG;
		if ($etiquette==""){
			$l=strlen($url);
			$posr=strrpos($url,'/');
			if ($posr===false){ // pas de separateur
				$etiquette=$url;
			}
			else if ($posr==$l-1){ // separateur en fin
				$etiquette=get_string("etiquette_inconnue", "referentiel");
			}
			else if ($posr==0){ // separateur en tete et en fin !
				$etiquette=get_string("etiquette_inconnue", "referentiel");
			}
			else {
				$etiquette=substr($url,$posr+1);
			}
		}
		$importfile = "{$CFG->dataroot}/{$url}";
		if (file_exists($importfile)) {
	        if ($CFG->slasharguments) {
    	    	$efile = "{$CFG->wwwroot}/file.php/$url";
        	}
		    else {
				$efile = "{$CFG->wwwroot}/file.php?file=/$url";
        	}
		}
		else{
			$efile = "$url";
		}
		
		return "<a href=\"$efile\" target=\"".$cible."\">$etiquette</a>";
    }
	
	
	
    /**
     * write a file 
     * @return boolean
	 * @ input $path_to_data : a data path
	 * @ input $filename : a filename
     */
    function referentiel_enregistre_fichier($path_to_data, $filename, $expout) {
        global $CFG;
        // create a directory for the exports (if not already existing)
        if (! $export_dir = make_upload_directory($path_to_data)) {
              error( get_string('cannotcreatepath', 'referentiel', $export_dir) );
			  return "";
        }
        $path = $CFG->dataroot.'/'.$path_to_data;

        // write file
        $filepath = $path."/".$filename;
		
		// echo "<br />DEBUG : 2580 :: FILENAME : $filename <br />PATH_TO_DATA : $path_to_data <br />PATH : $path <br />FILEPATH : $filepath\n";
		
        if (!$fh=fopen($filepath,"w")) {
            return "";
        }
        if (!fwrite($fh, $expout, strlen($expout) )) {
            return "";
        }
        fclose($fh);
        return $path_to_data.'/'.$filename;
    }

    /**
     * write a file 
     * @return boolean
	 * @ input $path_to_data : a data path
	 * @ input $filename : a filename
     */
    function referentiel_upload_fichier($path_to_data, $filename_source, $filename_dest) {
        global $CFG;
        // create a directory for the exports (if not already existing)
        if (! $export_dir = make_upload_directory($path_to_data)) {
              error( get_string('cannotcreatepath', 'referentiel', $export_dir) );
			  return "";
        }
        $path = $CFG->dataroot.'/'.$path_to_data;
		
		if (referentiel_deplace_fichier($path, $filename_source, $filename_dest, '/', true)){
			return $path_to_data.'/'.$filename_dest;
		}
		else {
			return "";
		}
    }
	
// ------------------
function referentiel_deplace_fichier($dest_path, $source, $dest, $sep, $deplace) {
// recopie un fichier sur le serveur
// pour effectuer un deplacement $deplace=true
// @ devant une fonction signifie qu'aucun message d'erreur n'est affiché
// $dest_path est le dossier de destination du fichier
// source est le nom du fichier source (sans chemin)
// dest est le nom du fichier destination (sans chemin)
// $sep est le séparateur de chemin
// retourne true si tout s'est bien déroulé

	// Securite
	if (strstr($dest, "..") || strstr($dest, $sep)) {
		// interdire de remonter dans l'arborescence
		// la source est detruite
		if ($deplace) @unlink($source);
		return false;
	}
	
	// repertoire de stockage des fichiers
	$loc = $dest_path.$sep.$dest;
// 	$ok = @copy($source, $loc);
	$ok =  @copy($source, $loc);
	if ($ok){ 
		// le fichier temporaire est supprimé
		if ($deplace)  @unlink($source);
	}
	else{ 
		// $ok = @move_uploaded_file($source, $loc);
		$ok =  @move_uploaded_file($source, $loc);
	}
	return $ok;
}

	// ------------------	
	function referentiel_get_logo($referentiel){
	// A TERMINER
		return "pix/logo_men.jpg";
	}
	
	// ------------------
	function referentiel_get_file($filename, $course_id, $path="" ) {
	// retourne un path/nom_de_fichier dans le dossier moodledata
 		global $CFG;
 		if ($path==""){
			$currdir = $CFG->dataroot."/$course_id/$CFG->moddata/referentiel/";
  		}
		else {
			$currdir = $CFG->dataroot."/$course_id/$CFG->moddata/referentiel/".$path;
		}
		  
	    if (!file_exists($currdir.'/'.$filename)) {
      		return "";
      	}
		else{
			return $currdir.'/'.$filename;
		}
 	}  

?>
