<?php // $Id: print_rtf.php,v 1.0.0.0 2010/1/09 11:32:00 jf Exp $

/**
 * file print_rtf.php
 * print rtf certificate
 */
// Utilisation de la CLASSE RTF de phprtflite
// http://sourceforge.net/projects/phprtf/

// JF
     
// traitement des chaines de caracteres
require_once('textlib.php');

// RTF    
require_once('./rtf/Rtf.php');


class RTFClass extends RTF {

  var $font= NULL;
  var $paragraphe= NULL;
  var $section= NULL;
  var $table= NULL;
  var $cell=NULL;
  
  function SetFont($police, $taille)
  {
    $this->font=new Font($taille, $police);
  }

  function Write($indentation, $texte){
    $this->paragraphe= new ParFormat();
    $this->paragraphe->setSpaceBefore(3);
    $this->paragraphe->setSpaceAfter(8);
    $this->paragraphe->setIndentRight(5);
    $this->paragraphe->setIndentRight(0.5);
    $this->paragraphe->setIndentLeft($indentation);
    $this->section->writeText($texte, $this->font, $this->paragraphe);
  }
  
  function WriteTable($textArray, $nblig, $nbcol, $police='Arial', $taille=9){
  // Affiche un tableau  
    // combien de tables
    $nbt=1;   
    if ($nbcol>10){
      $nbt = ($nbcol / 10) + 1;
      $colWidth = ($this->section->getLayoutWidth()-1) / 10;
      $colmax=10;
    }
    elseif ($nbcol>0){
      $colWidth = ($this->section->getLayoutWidth()-1) / $nbcol;
      $colmax=$nbcol;
    }
    else{
      $colWidth = ($this->section->getLayoutWidth()-1) ;
      $colmax=1;
    }
    
    $this->table = new Table($this);
    $this->table = $this->section->addTable();
    for ($i=0; $i<$nblig; $i++){
      $this->table ->addRows($nbt);
      for ($j=0; $j<$colmax; $j++){        
        $this->table->addColumn($colWidth);
      }
    }
    
    //borders
    // $this->table->setBordersOfCells(new BorderFormat(1, '#555555'), 1, 1, $nblig*$nbt, $colmax);

    for ($i=0; $i<$nblig; $i++){
      for ($j=0; $j<$nbcol; $j++){        
        $lig=$i*$nbt+(int)($j/10)+1;        
        $col=($j%10)+1;      
        $this->table->writeToCell($lig, $col, $textArray[$i][$j], new Font($taille,$police), new ParFormat('center'));
 	      $this->table->setBordersOfCells(new BorderFormat(1, '#000000'), $lig, $col);
  	    $this->table->setBackgroundOfCells('#ffffdd', $lig, $col);        
      }
    }
  }
  
  function AddPage(){
    $this->section=$this->addSection();
  }
  
  
} // fin de la classe


function no_recode($s){
  return $s;
}

	/**
     * generates <text></text> tags, processing raw text therein 
     * @param int ilev the current indent level
     * @param boolean short stick it on one line
     * @return string printted text
     */


function rtf_write_etablissement( $record ) {
    // initial string;
	global $rtf;
		if ($record){
			$id = trim( $record->id );
			$idnumber = trim( $record->idnumber);
			$name = trim( $record->name);
			$address = trim( $record->address);
			$logo=$record->logo;
			$rtf->SetFont('Arial',10); 
			$texte=no_recode(get_string('idnumber','referentiel').' : '.$idnumber);
			$rtf->Write(0.0,$texte);
			$rtf->SetFont('Arial',12); 
			$texte='<b>'.no_recode(get_string('name','referentiel').' : '.$name).'</b>';
			$rtf->Write(0.0,$texte);
			$texte=no_recode(get_string('address','referentiel').' : '.$address);
			$rtf->SetFont('Arial',10); 
			$rtf->Write(0.0,$texte);
    }
  }
	
	function rtf_write_student( $record, $param ) {
	global $rtf;
		if ($record){
			$id = trim( $record->id );
			$userid = trim( $record->userid );
      $ref_etablissement = trim( $record->ref_etablissement);
			$num_student = trim( $record->num_student);
			$ddn_student = trim( $record->ddn_student);
			$lieu_naissance = trim( $record->lieu_naissance);
			$departement_naissance = trim( $record->departement_naissance);
			$adresse_student = trim( $record->adresse_student);			
			
			// Etablissement
			$record_etablissement=referentiel_get_etablissement($record->ref_etablissement);
	    	if ($record_etablissement){
				if ($param->certificate_sel_student_etablissement){
					rtf_write_etablissement( $record_etablissement );
				}
			}
			if ($param->certificate_sel_student_numero){
				$rtf->SetFont('Arial',10); 
				$texte=no_recode(get_string('num_student','referentiel')." : ".$num_student);
				$rtf->Write(0.0,$texte);
				
			}
			
			if ($param->certificate_sel_student_nom_prenom){
				$rtf->SetFont('Arial',12); 
				$rtf->Write(0.0,no_recode(referentiel_get_user_info($record->userid)));
				$rtf->SetFont('Arial',10); 
				
			}
			if ($param->certificate_sel_student_ddn || $param->certificate_sel_student_lieu_naissance){
				$texte='';
				if ($param->certificate_sel_student_ddn){
					$texte.=no_recode(get_string('ddn_student','referentiel')." ".$ddn_student." ");
				}
				if ($param->certificate_sel_student_lieu_naissance){
					$texte.=no_recode(get_string('lieu_naissance','referentiel')." : ".$lieu_naissance.", ".get_string('departement_naissance','referentiel')." : ".$departement_naissance);
				}
				$rtf->Write(0.0,$texte);
				
      }
			if ($param->certificate_sel_student_adresse){
				$texte=no_recode(get_string('adresse_student','referentiel'). " : ".$adresse_student);
				$rtf->Write(0.0, $texte);
				
			}
    }

}


// -------------------
function rtf_referentiel_affiche_certificate_consolide($referentielid, $separateur1, $separateur2, $liste_code, $font1=10, $font2=9, $font3=8, $params=NULL){
// ce certificate comporte des pourcentages par domaine et competence
// decalque de referentiel_affiche_certificate_consolide() de lib.php
global $rtf;

global $OK_REFERENTIEL_DATA;
global $t_domaine;
global $t_domaine_coeff;
		
// COMPETENCES
global $t_competence;
global $t_competence_coeff;
		
// ITEMS
global $t_item_code;
global $t_item_coeff; // coefficient poids determeine par le modele de calcul (soit poids soit poids / empreinte)
global $t_item_domaine; // index du domaine associé à un item 
global $t_item_competence; // index de la competence associée à un item 
global $t_item_poids; // poids
global $t_item_empreinte;
global $t_nb_item_domaine;
global $t_nb_item_competence;

	// nom des domaines, compétences, items
	$label_d="";
	$label_c="";
	$label_i="";
	if (isset($params) && !empty($params)){
		if (isset($params->domainlabel)){
					$label_d=$params->domainlabel;
		}
		if (isset($params->skilllabel)){
					$label_c=$params->skilllabel;
		}
		if (isset($params->itemlabel)){
					$label_i=$params->itemlabel;
		}
	}
	$t_certif_item_valeur=array();	// table des nombres d'items valides 
	$t_certif_item_coeff=array(); // somme des poids du domaine
	$t_certif_competence_poids=array(); // somme des poids de la competence
	$t_certif_domaine_poids=array(); // poids certifies
	for ($i=0; $i<count($t_item_code); $i++){
		$t_certif_item_valeur[$i]=0.0;
		$t_certif_item_coeff[$i]=0.0;
	}
	for ($i=0; $i<count($t_competence); $i++){
		$t_certif_competence_poids[$i]=0.0;
	}
	for ($i=0; $i<count($t_domaine); $i++){
		$t_certif_domaine_poids[$i]=0.0;
	}

		
	// donnees globales du referentiel
	if ($referentielid){
		
		if (!isset($OK_REFERENTIEL_DATA) || ($OK_REFERENTIEL_DATA==false) ){
			$OK_REFERENTIEL_DATA=referentiel_initialise_data_referentiel($referentielid);
		}

		if (isset($OK_REFERENTIEL_DATA) && ($OK_REFERENTIEL_DATA==true)){		
		// recuperer les items valides
		$tc=array();
		$liste_code=referentiel_purge_dernier_separateur($liste_code, $separateur1);

		if (!empty($liste_code) && ($separateur1!="") && ($separateur2!="")){
			$tc = explode ($separateur1, $liste_code);
			for ($i=0; $i<count($t_item_domaine); $i++){
				$t_certif_domaine_poids[$i]=0.0;
			}
			for ($i=0; $i<count($t_item_competence); $i++){
				$t_certif_competence_poids[$i]=0.0;
			}

			$i=0;
			while ($i<count($tc)){
				$t_cc=explode($separateur2, $tc[$i]); // tableau des items valides
				if (isset($t_cc[1])){
					if (isset($t_item_poids[$i]) && isset($t_item_empreinte[$i])){
						if (($t_item_poids[$i]>0) && ($t_item_empreinte[$i]>0)){
							// echo "<br>".min($t_cc[1],$t_item_empreinte[$i]);
							$t_certif_item_valeur[$i]=min($t_cc[1],$t_item_empreinte[$i]);
							// calculer le taux
							$coeff=(float)$t_certif_item_valeur[$i] * (float)$t_item_coeff[$i];
							// stocker la valeur pour l'item
							$t_certif_item_coeff[$i]=$coeff;
							// stocker le taux pour la competence
							$t_certif_domaine_poids[$t_item_domaine[$i]]+=$coeff;
							// stocker le taux pour le domaine
							$t_certif_competence_poids[$t_item_competence[$i]]+=$coeff;
						}
						else{
							// echo "<br>".min($t_cc[1],$t_item_empreinte[$i]);
							$t_certif_item_valeur[$i]=0.0;
							$t_certif_item_coeff[$i]=0.0;
							// $t_certif_domaine_poids[$t_item_domaine[$i]]+=0.0;
							// $t_certif_competence_poids[$t_item_competence[$i]]+=0.0;
						}
					}
				}
				
				$i++;
			}
			
			// Affichage
			// DOMAINES
      $rtf->SetFont('Arial',$font1);
      $rtf->Write(0.0,'<b>'.no_recode(get_string('domaine','referentiel')).'</b>');
      
      $textArray= array();
      
      $nd=count($t_domaine_coeff);
			$espaced=80 / $nd;
      // $s.= '<table width="100%" cellspacing="0" cellpadding="2"><tr valign="top" >'."\n";
			for ($i=0; $i<$nd; $i++){
			  if ($t_domaine_coeff[$i]){
          $textArray[0][$i]= '<b>'.$t_domaine[$i].'</b> ('.referentiel_pourcentage($t_certif_domaine_poids[$i], $t_domaine_coeff[$i]).'%) ';
			  }
			  else{
          $textArray[0][$i]= '<b>'.$t_domaine[$i].'</b> (0%) ';        
        }           
			}
			$rtf->WriteTable($textArray, 1, $nd);
      
      $rtf->SetFont('Arial',$font1);
      $rtf->Write(0.0,'<b>'.no_recode(get_string('competence','referentiel').'</b>'));
      
      
      $nc=count($t_competence);
			$espacec= 80 / $nc;
      reset($textArray);
      
			// $s.=  '<tr valign="top"  >'."\n";
			for ($i=0; $i<$nc; $i++){
				if ($t_competence_coeff[$i]){
          $textArray[0][$i]='<b>'.$t_competence[$i].'</b> ('.referentiel_pourcentage($t_certif_competence_poids[$i], $t_competence_coeff[$i]).'%)';									
				}
				else{
          $textArray[0][$i]= '<b>'.$t_competence[$i].'</b> (0%) ';  
        }			
			}
			$rtf->WriteTable($textArray, 1, $nc);
						
			// ITEMS
      $rtf->SetFont('Arial',$font1);
      $rtf->Write(0.0,'<b>'.no_recode(get_string('item','referentiel')).'</b>');
      
      $ni=count($t_item_code);
			reset($textArray);
			// $s.= '<tr valign="top" >'."\n";
			for ($i=0; $i<$ni; $i++){
				if ($t_item_empreinte[$i]){
					if ($t_certif_item_valeur[$i]>=$t_item_empreinte[$i]) {
						$textArray[0][$i]= '<b>'.$t_item_code[$i].'</b> ';
					}	
					else {
						$textArray[0][$i]= $t_item_code[$i].' ';
          }
          // pourcentages
					if ($t_certif_item_valeur[$i]>=$t_item_empreinte[$i]){
						$textArray[0][$i].=' (100%) ';				
					}
					else{
						$textArray[0][$i].= ' ('.referentiel_pourcentage($t_certif_item_valeur[$i], $t_item_empreinte[$i]).'%) ';				
					}  
				}
				else{
					$textArray[0][$i]= '<i>'.$t_item_code[$i].'</i> ';		
				} 
			}
			
			$rtf->WriteTable($textArray, 1, $ni);
				
		}
	}
	}

}

// ----------------------------------------------------
function rtf_referentiel_affiche_detail_competences($separateur1, $separateur2, $liste, $liste_empreintes, $liste_poids, $font1=10, $font2=9){
// decalque de referentiel_affiche_detail_competences() de print_lib_certificate.php
global $rtf;

	$t_empreinte=explode($separateur1, $liste_empreintes);
	$t_poids=explode('|', $liste_poids);	
	// DEBUG
	// echo "<br>DEBUG : print_lib_certificate.php :: 105<br>LISTE EMPREINTES : $liste_empreintes<br>\n";
	// print_r($t_empreinte);
	// DEBUG
	// echo "<br>DEBUG : print_lib_certificate.php :: 108<br>LISTE POIDS : $liste_poids<br>\n";
	// print_r($t_poids);
	// exit;
  
  $rtf->SetFont('Arial',$font1);
  
	$tc=array();
	$liste=referentiel_purge_dernier_separateur($liste, $separateur1);
		if (!empty($liste) && ($separateur1!="") && ($separateur2!="")){
			$tc = explode ($separateur1, $liste);
			$i=0;
			while ($i<count($tc)){
				if ($tc[$i]!=''){
					$tcc=explode($separateur2, $tc[$i]);					
					if (isset($tcc[1]) && ($tcc[1]>=$t_empreinte[$i])){
            $rtf->Write(0.0,'<b>'.$tcc[0].'</b> : ');          
          }
					else{
            $rtf->Write(0.0,'<i>'.$tcc[0].'</i> : ');          
					}
          $rtf->SetFont('Arial',$font2);
          $rtf->Write(0.0," ".no_recode(str_replace('#',"\n".get_string('p_item','referentiel').":",$t_poids[$i])." ".get_string('approved','referentiel').":".$tcc[1]." ".get_string('e_item','referentiel').":".$t_empreinte[$i]." "));						
					
				}
				$i++;
			} 
		}
	  
}



// ----------------------------------------------------
function rtf_liste_competences_certificat($referentiel_id, $separateur1, $separateur2, $liste, $liste_empreintes, $all=0, $font1=10, $font2=9){
global $rtf;
global $copyright;
global $registere;
global $puce;
// Affiche les codes competences en tenant compte de l'empreinte
	$t_empreinte=explode($separateur1, $liste_empreintes);
	
		$tc=array();
		$liste=referentiel_purge_dernier_separateur($liste, $separateur1);
		if (!empty($liste) && ($separateur1!="") && ($separateur2!="")){
			$tc = explode ($separateur1, $liste);
			// DEBUG 
			// echo "<br />CODE <br />\n";
			// print_r($tc);
			$i=0;
			while ($i<count($tc)){
				// CODE1:N1
				// DEBUG 
				// echo "<br />".$tc[$i]." <br />\n";
				// exit;
				$tcc=explode($separateur2, $tc[$i]);
				// echo "<br />CODE : ".$tc[$i]." <br />\n";
				// echo "<br />REFERENTIEL ID : ".$referentiel_id." <br />\n";
				// print_r($tcc);
				
				// exit;
				if ($referentiel_id){
					$descriptif_item=no_recode(referentiel_get_description_item($tcc[0], $referentiel_id));
				}
				else{
					$descriptif_item='';
				}
				if (isset($tcc[1]) && ($tcc[1]>=$t_empreinte[$i])){
					$rtf->SetFont('Arial',$font1); 
					$rtf->Write(0.0, "    $puce <b>".$tcc[0]."</b> ");
					$rtf->SetFont('Arial',$font2);
					$rtf->Write(0.0," : $descriptif_item");
					
				}
				else if ($all){
					$rtf->SetFont('Arial',$font1); 
					$rtf->Write(0.0, "     $puce <i>".$tcc[0]."</i> ");
					$rtf->SetFont('Arial',$font2);
					$rtf->Write(0.0," : $descriptif_item");
					
				}
				$i++;
			} 
		}
}

    /**
     * Turns item into an xml segment
     * @param item object
     * @return string xml segment
     */

function rtf_write_item( $item ) {
    global $rtf;
    if ($item){
      $code = $item->code;
      $description = $item->description;
      $referentielid = $item->referentielid;
      $skillid = $item->skillid;
			$type = $item->type;
			$weight = $item->weight;
			$footprint = $item->footprint;
			$sortorder = $item->sortorder;
      $rtf->SetFont('Arial',9); 
   	  $rtf->Write(0.0, '<b>'.no_recode(trim(stripslashes($code))).'</b>');
 	   	
 	   	$rtf->SetFont('Arial',9);
   	  $rtf->Write(0.0, '<i>'.no_recode(trim(stripslashes($description))).'</i>');
   	  
   	  $rtf->SetFont('Arial',9);
      $rtf->Write(0.0, no_recode(trim(get_string('t_item','referentiel')." : ".$type.", ".get_string('p_item','referentiel')." : ".$weight.", ".get_string('e_item','referentiel')." : ".$footprint)));
      
    } 
}
    
	 /**
     * Turns competence into an xml segment
     * @param competence object
     * @return string xml segment
     */

function rtf_write_competence( $competence ) {
    global $rtf;
 		  if ($competence){
        $code = $competence->code;
        $description = $competence->description;
        $domainid = $competence->domainid;
        $sortorder = $competence->sortorder;
			  $nb_item_competences = $competence->nb_item_competences;
        $rtf->SetFont('Arial',10); 
	   	  $rtf->Write(0.0,'<b>'.no_recode(trim(get_string('competence','referentiel')." : ".stripslashes($code))).'</b>');
        
        $rtf->SetFont('Arial',10); 
        $rtf->Write(0.0, no_recode(trim(stripslashes($description))));
	 	   	
			
			  // ITEM
			  $records_items = referentiel_get_item_competences($competence->id);
        if ($records_items){				  
    	    $rtf->SetFont('Arial',10); 
	        $rtf->Write(0.0,'<b>'.no_recode(trim(get_string('items','referentiel'))).'</b>');
          

				  foreach ($records_items as $record_i){
						rtf_write_item( $record_i );
				  }
				  
			   }
        }
}


	 /**
     * Turns domaine into an xml segment
     * @param domaine object
     * @return string xml segment
     */

function rtf_write_domaine( $domaine ) {
    global $rtf;
    
		if ($domaine){
      $code = $domaine->code;
      $description = $domaine->description;
      $referentielid = $domaine->referentielid;
			$sortorder = $domaine->sortorder;
			$nb_competences = $domaine->nb_competences;
 			$rtf->SetFont('Arial',10); 
   	  $rtf->Write(0.0,'<b>'.no_recode(trim(get_string('domaine','referentiel')." : ".stripslashes($code))).'</b>');
      
      $rtf->SetFont('Arial',10); 
   	  $rtf->Write(0.0, no_recode(trim(stripslashes($description))));
 	   	
			
			// LISTE DES COMPETENCES DE CE DOMAINE
			$records_competences = referentiel_get_competences($domaine->id);
			if ($records_competences){
				foreach ($records_competences as $record_c){
          rtf_write_competence( $record_c );
				}
			}
    }
}


	 /**
     * Turns referentiel instance into an xml segment
     * @param referentiel instanceobject
     * @return string xml segment
     */
function rtf_write_referentiel( $referentiel_instance, $referentiel_referentiel, $param ) {
    global $CFG;
		global $rtf;
		global $image_logo;
		$ok_saut_page=false;
		
		if (($referentiel_instance) && ($referentiel_referentiel)) {
      $name = no_recode(trim($referentiel_referentiel->name));
      $code = no_recode(trim($referentiel_referentiel->code));
			$description = no_recode(trim($referentiel_referentiel->description));
			
			$id = $referentiel_instance->id;
      $name_instance = no_recode(trim($referentiel_instance->name));
      $description = no_recode(trim($referentiel_instance->description));
      $domainlabel = no_recode(trim($referentiel_instance->domainlabel));
      $skilllabel = no_recode(trim($referentiel_instance->skilllabel));
      $itemlabel = no_recode(trim($referentiel_instance->itemlabel));
      $timecreated = $referentiel_instance->timecreated;
      $course = $referentiel_instance->course;
      $referentielid = $referentiel_instance->referentielid;
			$visible = $referentiel_instance->visible;
      
      
			$rtf->AddPage();
			/*
      $rtf->SetAutoPageBreak(1, 27.0);     
			$rtf->SetCol(0);
			$rtf->SetDrawColor(128, 128, 128);    
			$rtf->SetLineWidth(0.4);     
			// logo
			$posy=$rtf->GetY();    
			*/
			
			if (isset($image_logo) && ($image_logo!="")){
				// $rtf->Image($image_logo,150,$posy,40);
				$rtf->section->addImage($image_logo,$rtf->paragraphe); 
			}
			
			/*
			// $posy=$rtf->GetY()+60;    
      $rtf->SetLeftMargin(15);
      // $rtf->SetX(20);
			*/
			
			$rtf->SetFont('Arial',14); 
		  $rtf->Write(0.0,'<b>'.get_string('certification','referentiel').'</b>');
			
			$rtf->SetFont('Arial',12); 
		  $rtf->Write(0.0, $name.' <i>('.$code.')</i>');
			
			$rtf->SetFont('Arial',10);
			$rtf->Write(0.0, $description);
			
      if ($param->certificate_sel_referentiel){				
				// DOMAINES
				// LISTE DES DOMAINES
				$compteur_domaine=0;
				$records_domaine = referentiel_get_domaines($referentiel_referentiel->id);
		    if ($records_domaine){
					foreach ($records_domaine as $record_d){
						rtf_write_domaine($record_d );
					}
				}
        $ok_saut_page=true;		
			} 

			if ($param->certificate_sel_referentiel_instance){
				$rtf->SetFont('Arial',10); 
				// $rtf->Write(0.0,"id : $id ");
				// 
				$rtf->Write(0.0,'<b>'.no_recode(get_string('instance','referentiel')." : ".$name_instance).'</b>');
				
				$rtf->SetFont('Arial',10);
				$rtf->Write(0.0,no_recode($description));   
				
        $rtf->Write(0.0,no_recode($domainlabel.", ".$skilllabel.", ".$itemlabel));
			
    	        /*
				$rtf->Write(0.0,"Cours : $course");
				
	            $rtf->Write(0.0,"Référentiel :  $referentielid");
				
        	    $rtf->Write(0.0,"Visible : $visible");
				
				*/
				$ok_saut_page=true;
			}
			
			
			if ($ok_saut_page==true){ // forcer le saut de page
			  $rtf->AddPage();
      }
		}
}
	
	 /**
     * Turns referentiel instance into an xml segment
     * @param referentiel instanceobject
     * @return string xml segment
     */
function rtf_write_certificat( $record, $referentiel_instance, $referentiel_referentiel, $liste_empreintes, $liste_poids, $param) {
    	global $CFG;
		global $rtf;
    	// add comment and div tags
		
		if ($record){
			// DEBUG
			// echo "DEBUG LIGNE 1021";
			// print_r($referentiel_instance);
			$id = trim( $record->id );
            $comment = no_recode(trim($record->comment));
            $synthese_certificate = no_recode(trim($record->synthese_certificat));

			$comptencies =  no_recode(trim($record->comptencies)) ;
            $competences_certificate =  no_recode(trim($record->competences_certificat)) ;
            $decision_jury = no_recode(trim($record->decision_jury));
			if ($record->date_decision){
                $date_decision = userdate(trim($record->date_decision));
			}
			else{
				$date_decision ="";
			}
            $userid = trim( $record->userid);
            $teacherid = trim( $record->teacherid);
			if ($teacherid!=0){
				$nom_prenom_teacher=no_recode(referentiel_get_user_info($teacherid));
			}
			else{
				$nom_prenom_teacher="";
			}
      
            $referentielid = trim( $record->referentielid);
			// $referentielid=$referentiel_id;
			$verrou = trim( $record->verrou );
			$valide = trim( $record->valide );
			$evaluation = trim( $record->evaluation );
			
			
			// USER
			if (isset($record->userid) && ($record->userid>0)){
				$record_student = referentiel_get_student_user($record->userid);
		    if ($record_student){
					
					rtf_write_referentiel($referentiel_instance, $referentiel_referentiel, $param);
					
					rtf_write_student( $record_student, $param);
					
					$rtf->SetFont('Arial',12);
					if ($param->certificate_sel_decision_jury){
						if (($date_decision!="") && ($decision_jury!="")){
							$rtf->Write(0.0,no_recode($decision_jury));
						}
						
					}
					
					// $rtf->SetFont('Arial',10); 
					// $rtf->Write(0.0,"<b>ID : </b>");
					// $rtf->SetFont('Arial',10);
					// $rtf->Write(0.0,"$id");
					// 
					
					$rtf->SetFont('Arial',12); 
        	$rtf->Write(0.0,'<b>'.no_recode(get_string('competences','referentiel')).': </b>');
					
					if ($param->certificate_sel_activite_competences){
						$rtf->SetFont('Arial',9); 
	        	$rtf->Write(0.0,'<b>'.no_recode(get_string('comptencies','referentiel')).' : </b>');
						 
    	    	rtf_liste_competences_certificat($referentielid, '/',':', $comptencies, $liste_empreintes, 0, 9, 8);
						
					}
					if ($param->certificate_sel_certificate_competences){
						$rtf->SetFont('Arial',10); 
	        	$rtf->Write(0.0,'<b>'.no_recode(get_string('competences_certificat','referentiel')).' : </b>');
						
    	    	rtf_liste_competences_certificat($referentielid, '/',':', $competences_certificat, $liste_empreintes,0,10,9);
						
					}
					if (($param->certificate_sel_certificate_competences) 
            && ($param->certificate_sel_certificate_detail)){
						rtf_referentiel_affiche_detail_competences('/',':',$competences_certificat, $liste_empreintes, $liste_poids);
          }					
					if ($param->certificate_sel_certificate_pourcent){
            // $rtf->SetFont('Arial',10);
    	    	// $rtf->Write(0.0,'<b>'.no_recode(get_string('pourcentage','referentiel')).' : </b>');
					  // 
					  rtf_referentiel_affiche_certificate_consolide($referentielid, '/',':', $competences_certificat, 10,9,8);
					}
					
					if ($param->certificate_sel_commentaire){
						$rtf->SetFont('Arial',10);
                        $rtf->Write(0.0,'<b>'.no_recode(get_string('commentaire','referentiel')).' : </b>');
						$rtf->SetFont('Arial',10);
						$rtf->Write(0.0,no_recode($comment));

						$rtf->SetFont('Arial',10);
                        $rtf->Write(0.0,'<b>'.no_recode(get_string('$synthese_certificat','referentiel')).' : </b>');
						$rtf->SetFont('Arial',10);
                        $rtf->Write(0.0,no_recode($synthese_certificat));
					}
					if ($param->certificate_sel_decision_jury){
						$rtf->SetFont('Arial',10);
        			      $rtf->Write(0.0, '<b>'.no_recode(get_string('decision','referentiel')).' : </b>');
						$rtf->SetFont('Arial',10);
		    	  $rtf->Write(0.0,no_recode($decision_jury));
						
					}
					if ($param->certificate_sel_certificate_referents){
						$rtf->SetFont('Arial',10);
						$rtf->Write(0.0,'<b>'.no_recode(get_string('enseignant','referentiel')).' : </b>');
						$rtf->SetFont('Arial',10);
						$rtf->Write(0.0,no_recode($nom_prenom_teacher));
						
					}
					/*
					$rtf->Write(0.0," Référentiel : $referentielid");
					
		            $rtf->Write(0.0," Verrou : $verrou, Valide : $valide, Evaluation : $evaluation");
					
					*/
					// $rtf->Ln(20);
					$rtf->Write(0.0, get_string('date_signature','referentiel', date("d/m/Y")));
				}
			}
		}
}
	

function rtf_write_certification($referentiel_instance, $referentiel_referentiel,  $userid=0, $param, $records_certificats) {
    	global $CFG;
		global $rtf;
		
		if ($referentiel_instance && $referentiel_referentiel) {
			// CERTIFICATS
			if (isset($referentiel_instance->referentielid) && ($referentiel_instance->referentielid>0)){
				// les empreintes
				$liste_empreintes = referentiel_purge_dernier_separateur(referentiel_get_liste_empreintes_competence($referentiel_instance->referentielid), '/');
    		$liste_poids=referentiel_purge_dernier_separateur(referentiel_get_liste_poids($referentiel_instance->referentielid), '|');
				
        if ($userid>0){
					$record = referentiel_get_certificate_user($userid, $referentiel_instance->referentielid);
					rtf_write_certificat( $record, $referentiel_instance, $referentiel_referentiel, $liste_empreintes, $liste_poids, $param);
				}
				else {
					if (!$records_certificats){
            $records_certificats = referentiel_get_certificats($referentiel_instance->referentielid);
					}
          if ($records_certificats){
						foreach ($records_certificats as $record){
							rtf_write_certificat( $record, $referentiel_instance, $referentiel_referentiel, $liste_empreintes, $liste_poids, $param);
						}
					}
				}
				// print_r($records_certificats);
		    	// exit;
			}
		}
}
 

?>