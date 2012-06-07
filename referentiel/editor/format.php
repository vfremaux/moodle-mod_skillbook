<?php // $Id: format.php,v 1.21.2.16 2008/01/15 14:58:10 thepurpleblob Exp $
//
///////////////////////////////////////////////////////////////
// XML import/export
//
//////////////////////////////////////////////////////////////////////////
// Based on default.php, included by ../import.php
/**
 * @package referetielbank
 * @subpackage importexport
 */
require_once( "$CFG->libdir/xmlize.php" );

class rformat_xml extends rformat_default {

    function provide_import() {
        return true;
    }

    function provide_export() {
        return false;
    }



    // IMPORT FUNCTIONS START HERE

    /** 
     * Translate human readable format name
     * into internal Moodle code number
     * @param string name format name from xml file
     * @return int Moodle format code
     */
    function trans_format( $name ) {
        $name = trim($name); 
 
        if ($name=='moodle_auto_format') {
            $id = 0;
        }
        elseif ($name=='html') {
            $id = 1;
        }
        elseif ($name=='plain_text') {
            $id = 2;
        }
        elseif ($name=='wiki_like') {
            $id = 3;
        }
        elseif ($name=='markdown') {
            $id = 4;
        }
        else {
            $id = 0; // or maybe warning required
        }
        return $id;
    }

    /**
     * Translate human readable single answer option
     * to internal code number
     * @param string name true/false
     * @return int internal code number
     */
    function trans_single( $name ) {
        $name = trim($name);
        if ($name == "false" || !$name) {
            return 0;
        } else {
            return 1;
        }
    }

    /**
     * process text string from xml file
     * @param array $text bit of xml tree after ['text']
     * @return string processed text
     */
    function import_text( $text ) {
        // quick sanity check
        if (empty($text)) {
            return '';
        }
        $data = $text[0]['#'];
        return addslashes(trim( $data ));
    }

    /**
     * return the value of a node, given a path to the node
     * if it doesn't exist return the default value
     * @param array xml data to read
     * @param array path path to node expressed as array 
     * @param mixed default 
     * @param bool istext process as text
     * @param string error if set value must exist, return false and issue message if not
     * @return mixed value
     */
    function getpath( $xml, $path, $default, $istext=false, $error='' ) {
        foreach ($path as $index) {
			// echo " $index ";
            if (!isset($xml[$index])) {
                if (!empty($error)) {
                    $this->error( $error );
                    return false;
                } else {
					// echo " erreur ";
                    return $default;
                }
            }
            else {
				$xml = $xml[$index];
				// echo " $xml ";
			}
        }
        if ($istext) {
            $xml = addslashes( trim( $xml ) );
        }
		
        return $xml;
    }


    /**
     * @param array referentiel array from xml tree
     * @return object import_referentiel object
	 * modifie la base de donnees 
     */
    function import_referentiel( $xmlreferentiel ) {
	// recupere le fichier xml 
	// selon les parametres soit cree une nouvelle instance 
	// soit modifie une instance courante de referentiel
	global $SESSION;
	global $USER;
	global $CFG;
	$nbdomaines=0;        // compteur
	$nbcompetences=0;        // compteur
    $nbitems=0;              // compteur
		// DEBUG
        // print_r($xmlreferentiel);
        // exit;
        if (!isset($this->action) || (isset($this->action) && ($this->action!="selectreferentiel") && ($this->action!="importreferentiel"))){
			if (!(isset($this->course->id) && ($this->course->id>0)) 
				|| 
				!(isset($this->rreferentiel->id) && ($this->rreferentiel->id>0))
				|| 
				!(isset($this->coursemodule->id) && ($this->coursemodule->id>0))
				){
				$this->error( get_string( 'incompletedata', 'referentiel' ) );
				return false;
			}
		}
        if (isset($this->action) && ($this->action=="importreferentiel")){
			if (!(isset($this->course->id) && ($this->course->id>0))){
				$this->error( get_string( 'incompletedata', 'referentiel' ) );
				return false;
			}
		}
		
		$risque_ecrasement=false;
		
        // get some error strings
        $error_noname = get_string( 'xmlimportnoname', 'referentiel' );
        $error_nocode = get_string( 'xmlimportnocode', 'referentiel' );
		$error_override = get_string( 'overriderisk', 'referentiel' );
		
        // this routine initialises the import object
        $re = $this->defaultreferentiel();
        // 
		// $re->id = $this->getpath( $xmlreferentiel, array('#','id',0,'#'), '', false, '');
        $re->name = $this->getpath( $xmlreferentiel, array('#','name','0','#'), '', true, $error_noname);
        $re->code = $this->getpath( $xmlreferentiel, array('#','idcode',0,'#'), '', true, $error_nocode);
        $re->description = $this->getpath( $xmlreferentiel, array('#','definition',0,'#','text',0,'#'), '', true, '');
        $re->url = $this->getpath( $xmlreferentiel, array('#','url',0,'#'), '', true, '');
		// $re->certificatethreshold = $this->getpath( $xmlreferentiel, array('#','certificatethreshold',0,'#'), '', false, '');
		$re->certificatethreshold = 0;
		// $re->timemodified = $this->getpath( $xmlreferentiel, array('#','timemodified',0,'#'), '', false, '');
		$re->timemodified = time();
		// $re->nb_domaines = $this->getpath( $xmlreferentiel, array('#','nb_domaines',0,'#'), '', false, '');
		$re->nb_domaines = 0;
		
		// $re->liste_codes_competence = $this->getpath( $xmlreferentiel, array('#','liste_codes_competence',0,'#'), '', true, '');
		$re->liste_codes_competence = '';
		// $re->liste_empreintes_competence = $this->getpath( $xmlreferentiel, array('#','liste_empreintes_competence',0,'#'), '', true, '');
		$re->liste_empreintes_competence = '';
		// $re->logo = $this->getpath( $xmlreferentiel, array('#','logo',0,'#'), '', true, '');
		$re->logo = '';
		// $re->local = $this->getpath( $xmlreferentiel, array('#','course',0,'#'), '', false, '');				
		
		/*
		// traitement d'une image associee
		// non implante
        $image = $this->getpath( $xmlreferentiel, array('#','image',0,'#'), $re->image );
        $image_base64 = $this->getpath( $xmlreferentiel, array('#','image_base64','0','#'),'' );
        if (!empty($image_base64)) {
            $re->image = $this->importimagefile( $image, stripslashes($image_base64) );
        }
		*/
		
		$re->export_process = false;
		$re->import_process = true;
		
		// le referentiel est toujours place dans le cours local d'appel 
		$re->course = $this->course->id;
		
		$risque_ecrasement=false;
		if (!isset($this->action) || ($this->action!="importreferentiel")){
			// importer dans le cours courant en remplacement du referentiel courant
			// Verifier si ecrasement referentiel local
			
			if (isset($re->name) && ($re->name!="")
				&& 
				isset($re->code) && ($re->code!="")
				&&
				isset($re->id) && ($re->id>0)
				&& 
				isset($re->course) && ($re->course>0)){
				// sauvegarder ?
				if ($this->course->id==$re->course){
					if (	
						(isset($this->rreferentiel->id) && ($this->rreferentiel->id==$re->id))
						|| 
						(
							(isset($this->rreferentiel->name) && ($this->rreferentiel->name==$re->name)) 
							&& 
							(isset($this->rreferentiel->code) && ($this->rreferentiel->code==$re->code))
						)
					)
					{
						$risque_ecrasement=true;
					}
				}
			}
		}
		
		if (($risque_ecrasement==false) || ($this->newinstance==1)) {
			// Enregistrer dans la base comme un nouveau referentiel du cours courant
			$new_referentiel_id=referentiel_add_referentiel($re);
			$this->setReferentielId($new_referentiel_id);
			// DEBUG
			// echo "<br />DEBUG xml/format.php ligne 572<br />NEW REFERENTIEL ID ENREGISTRE : ".$this->new_referentiel_id."\n";			
		}
		else if (($risque_ecrasement==true) && ($this->override==1)) {
			// Enregistrer dans la base en remplaçant la version courante (update)
			// NE FAUDRAIT IL PAS SUPPRIMER LE REFERENTIEL AVANT DE LA RECHARGER ?
			$re->instance=$this->rreferentiel->id;
			$re->referentiel_id=$this->rreferentiel->id;
			$ok=referentiel_update_referentiel($re);
			$new_referentiel_id=$this->rreferentiel->id;
		}
		else {
			// ni nouvelle instance ni recouvrement
			$this->error( $error_override );
			return false;
		}
		
		// importer les domaines
/*

				    echo "<br>DEBUG:: 260";
				    traverse_xmlize($xmlreferentiel, '$xmlreferentiel_'); // affiche la structure du fichier
 				    print '<pre>' . implode("", $GLOBALS['traverse_array']) . '</pre>';
                    exit;
*/

		if (!empty($xmlreferentiel['#']['domaine'])){
            $xmldomaines = $xmlreferentiel['#']['domaine'];
            $dindex=0;

            $re->domaines = array();
            if (empty($xmldomaines)){
				$this->error( get_string( 'incompletedata', 'referentiel' ).'editor/format.php :: ligne 255' );
				return false;
            }
        
            $nbdomaines=0;        // compteur
            foreach ($xmldomaines as $domaine) {
                // charger les domaine
                $dindex++;

                $new_domaine = array();
			$new_domaine = $this->defaultdomaine();
			// $new_domaine->id=$this->getpath( $domaine, array('#','id',0,'#'), '', false, '');	
			$new_domaine->code=$this->getpath( $domaine, array('#','idcode',0,'#'), '', true, $error_nocode);
			$new_domaine->description=$this->getpath( $domaine, array('#','definition',0,'#','text',0,'#'), '', true, '');
			//$new_domaine->sortorder=$this->getpath( $domaine, array('#','sortorder',0,'#'), '', false, '');
            $new_domaine->sortorder=$dindex;
            //$new_domaine->nb_competences=$this->getpath( $domaine, array('#','nb_competences',0,'#'), '', false, '');
            $new_domaine->nb_competences=0;
            // $new_domaine->referentielid=$this->getpath( $domaine, array('#','referentielid',0,'#'), '', false, '');
			
			// enregistrer
			$re->domaines[$dindex]=$new_domaine;
			
			// sauvegarder dans la base
			// remplacer l'id du referentiel importe par l'id du referentiel cree
			// trafiquer les donnees pour appeler la fonction ad hoc
			$new_domaine->referentielid=$new_referentiel_id;
			$new_domaine->instance=$new_referentiel_id; // pour que ca marche
			$new_domaine->new_code_domaine=$new_domaine->code;
			$new_domaine->new_description_domaine=$new_domaine->description;
			$new_domaine->new_num_domaine=$new_domaine->sortorder;
            $new_domaine->new_nb_competences=$new_domaine->nb_competences;

			$new_domaine_id=referentiel_add_domaine($new_domaine);
			if ($new_domaine_id){
                $nbdomaines++;
            }
			// importer les competences
			$xmlcompetences = $domaine['#']['competence'];
			
			$cindex=0;
			$re->domaines[$dindex]->competences=array();
    		if (empty($xmlcompetences)){
				$this->error( get_string( 'incompletedata', 'referentiel' ).'editor/format.php :: ligne 293' );
				return false;
            }

			$nbcompetences=0;        // compteur
	        foreach ($xmlcompetences as $competence) {
                // echo "<br>DEBUG :: exoport/format.php : 298 :: COMPETENCE\n";
                // print_r($competence);
                // echo "<br>\n";
				$cindex++;

				$new_competence = array();
				$new_competence = $this->defaultcompetence();
		    	// $new_competence->id = $this->getpath( $competence, array('#','id',0,'#'), '', false, '');
				$new_competence->code=$this->getpath( $competence, array('#','idcode',0,'#'), '', true, $error_nocode);
				$new_competence->description=$this->getpath( $competence, array('#','definition',0,'#','text',0,'#'), '', true, '');
				// $new_competence->sortorder=$this->getpath( $competence, array('#','sortorder',0,'#'), '', false, '');
                $new_competence->sortorder=$cindex;
                // $new_competence->nb_item_competences=$this->getpath( $competence, array('#','nb_item_competences',0,'#'), '', false, '');
                $new_competence->nb_item_competences=0;

                // $new_competence->domainid=$this->getpath( $competence, array('#','domainid',0,'#'), '', false, '');
                // echo "<br>DEBUG :: exoport/format.php : 312 :: COMPETENCE\n";
                // print_object($new_competence);
                // echo "<br>\n";

				// enregistrer
				$re->domaines[$dindex]->competences[$cindex]=$new_competence;
				
				// sauvegarder dans la base
				// remplacer l'id du referentiel importe par l'id du referentiel cree
				$new_competence->domainid=$new_domaine_id;
				// trafiquer les donnees pour appeler la fonction ad hoc
				$new_competence->instance=$new_referentiel_id; // pour que ca marche
				$new_competence->new_code_competence=$new_competence->code;
				$new_competence->new_description_competence=$new_competence->description;
				$new_competence->new_ref_domaine=$new_competence->domainid;
				$new_competence->new_num_competence=$new_competence->sortorder;
				$new_competence->new_nb_item_competences=$new_competence->nb_item_competences;
				// creation
				$new_competence_id=referentiel_add_competence($new_competence);
				if ($new_competence_id){
                    $nbcompetences++;        // compteur
                }
				// importer les items
				$xmlitems = $competence['#']['item'];
				$iindex=0;
				$re->domaines[$dindex]->competences[$cindex]->items=array();
				
        		if (empty($xmlitems)){
	       			$this->error( get_string( 'incompletedata', 'referentiel' ).'editor/format.php :: ligne 332' );
		      		return false;
                }
                
                $nbitems=0; // compteur
		        foreach ($xmlitems as $item) {
					$iindex++;
					$new_item = array();
					$new_item = $this->defaultitem();
					//// $new_item->id = $this->getpath( $item, array('#','id',0,'#'), '', false, '');
					$new_item->code = $this->getpath( $item, array('#','idcode',0,'#'), '', true, $error_nocode);
					$new_item->description=$this->getpath( $item, array('#','definition',0,'#','text',0,'#'), '', true, '');
					//$new_item->sortorder=$this->getpath( $item, array('#','sortorder',0,'#'), '', false, '');
					$new_item->sortorder=$iindex               ;
					// $new_item->type=$this->getpath( $item, array('#','type',0,'#'), '', true, '');
					$new_item->type='';
					//$new_item->weight=$this->getpath( $item, array('#','weight',0,'#'), '', false, '');
					$new_item->weight=1;
					//// $new_item->skillid=$this->getpath( $item, array('#','skillid',0,'#'), '', false, '');
					//// $new_item->referentielid=$this->getpath( $item, array('#','referentielid',0,'#'), '', false, '');
					// $new_item->footprint=$this->getpath( $item, array('#','footprint',0,'#'), '', false, '');
                    $new_item->footprint=1;
                    // enregistrer
					$re->domaines[$dindex]->competences[$cindex]->items[$iindex]=$new_item; 
					
					// sauvegarder dans la base

					// remplacer l'id du referentiel importe par l'id du referentiel cree
					$new_item->referentielid=$new_referentiel_id;
					$new_item->skillid=$new_competence_id;
					// trafiquer les donnees pour pouvoir appeler la fonction ad hoc
					$new_item->instance=$new_item->referentielid;
					$new_item->new_ref_competence=$new_item->skillid;
					$new_item->new_code_item=$new_item->code;
					$new_item->new_description_item=$new_item->description;
					$new_item->new_num_item=$new_item->sortorder;
					$new_item->new_type_item=$new_item->type;
					$new_item->new_poids_item=$new_item->weight;
					$new_item->new_empreinte_item=$new_item->footprint;
					// creer
					$new_item_id=referentiel_add_item($new_item);
					if ($new_item_id){
                        $nbitems++;
                    }
					// that's all folks
				} // items
    			if ($nbitems>0){
                    // mettre a jour
                    referentiel_set_competence_nb_item($new_competence_id, $nbitems);
                }
			} // competences
			if ($nbcompetences>0){
                // mettre a jour
                referentiel_set_domaine_nb_competence($new_domaine_id, $nbcompetences);
            }
        }
        }
        // mettre a jour
        if ($nbdomaines>0){
            // mettre a jour
            referentiel_set_referentiel_nb_domaine($new_referentiel_id, $nbdomaines);
            return $re;
        }
        else{
            return NULL;
        }


    }



    /**
     * parse the array of lines into an array of questions
     * this *could* burn memory - but it won't happen that much
     * so fingers crossed!
     * @param array lines array of lines from the input file
     * @return array (of objects) question objects
     */
    function read_import_referentiel($lines) {
        // we just need it as one big string
        $text = implode($lines, " ");
        unset( $lines );

        // this converts xml to big nasty data structure
        // the 0 means keep white space as it is (important for markdown format)
        // print_r it if you want to see what it looks like!
        $xml = xmlize( $text, 0 ); 
		
		// DEBUG
		// echo "<br />DEBUG editor/format.php :: ligne 398<br />\n";
		// print_r($xml);

		$re=$this->import_referentiel($xml['referentiel']);
        // stick the result in the $treferentiel array
 		// DEBUG
		// echo "<br />DEBUG xml/format.php :: ligne 632\n";
		// print_r($re);
        return $re;
    }
}
 

?>
