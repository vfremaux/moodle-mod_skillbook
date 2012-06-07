<?php 
// Based on default.php, included by ../import.php

class rformat_csv extends rformat_default {

	var $sep = ";";
	
	var $table_caractere_input='latin1'; // par defaut import latin1
	var $table_caractere_output='latin1'; // par defaut export latin1



	// ----------------
	function guillemets($texte){
		return '"'.trim($texte).'"';
	}

	// ----------------
	function purge_sep($texte){
		$cherche= array('"',$this->sep,"\r\n", "\n", "\r");
		$remplace= array("''",",", " ", " ", " ");
		return $this->guillemets(str_replace($cherche, $remplace, $texte));
	}

	// ----------------
	function recode_latin1_vers_utf8($string) {
		return mb_convert_encoding($string, "UTF-8", mb_detect_encoding($string, "UTF-8, ISO-8859-1, ISO-8859-15", true));
	}


	// ----------------
	function recode_utf8_vers_latin1($string) {
		return mb_convert_encoding($string, "ISO-8859-1", mb_detect_encoding($string, "UTF-8, ISO-8859-1, ISO-8859-15", true));
	}
	
	
	 /**
     * @param 
     * @return string recode latin1
	 * 
     */
    function input_codage_caractere($s){
		if (!isset($this->table_caractere_input) || ($this->table_caractere_input=="")){
			$this->table_caractere_input='latin1';
		}
		
		if ($this->table_caractere_input=='latin1'){
			$s=$this->recode_latin1_vers_utf8($s);
		}
		return $s;
	}
	
	 /**
     * @param 
     * @return string recode utf8
	 * 
     */
    function output_codage_caractere($s){
		if (!isset($this->table_caractere_output) || ($this->table_caractere_output=="")){
			$this->table_caractere_output='latin1';
		}
		
		if ($this->table_caractere_output=='latin1'){
			$s=$this->recode_utf8_vers_latin1($s);
		}
		return $s;
	}
	
    function provide_export() {
      return true;
    }

    function provide_import() {
      return true;
    }

	function repchar( $text ) {
	    // escapes 'reserved' characters # = ~ { ) and removes new lines
    	$reserved = array( '#','=','~','{','}',"\n","\r" );
	    $escaped = array( '\#','\=','\~','\{','\}',' ','' );
		return str_replace( $reserved, $escaped, $text ); 
	}

	function presave_process( $content ) {
	  // override method to allow us to add xhtml headers and footers

  		global $CFG;
		global $USER;
		$xp =  "#Moodle Referentiel CSV Export;latin1;".referentiel_get_user_info($USER->id)."\n";
		$xp .= $content;
  		return $xp;
	}

	function export_file_extension() {
  		return ".csv";
	}

    /**
     * Turns item into an xml segment
     * @param item object
     * @return string xml segment
     */

    function write_item( $item ) {
    global $CFG;
        // initial string;
        $expout = "";
        // add comment
		// 
		if ($item){
			// DEBUG
			// echo "<br />\n";
			// print_r($item);
            $code = $item->code;
            $description = $this->purge_sep($item->description);
            $referentielid = $item->referentielid;
            $skillid = $item->skillid;
			$type = $item->type;
			$weight = $item->weight;
			$sortorder = $item->sortorder;
			$footprint = $item->footprint;
            $expout .= stripslashes($this->output_codage_caractere($code)).";".stripslashes($this->output_codage_caractere($description)).";".$this->output_codage_caractere($type).";$weight;$sortorder;$footprint\n";
		}
        return $expout;
    }

	 /**
     * Turns competence into an xml segment
     * @param competence object
     * @return string xml segment
     */

    function write_competence( $competence ) {
    global $CFG;
        // initial string;
		$expout = "";
        // add comment		
		if ($competence){
            $code = $competence->code;
            $description = $this->purge_sep($competence->description);
            $domainid = $competence->domainid;
			$sortorder = $competence->sortorder;
			$nb_item_competences = $competence->nb_item_competences;
			$expout .= stripslashes($this->output_codage_caractere($code)).";".stripslashes($this->output_codage_caractere($description)).";$sortorder;$nb_item_competences\n";
							
			// ITEM
			$compteur_item=0;
			$records_items = referentiel_get_item_competences($competence->id);
			
			if ($records_items){
				// DEBUG
				// echo "<br/>DEBUG :: ITEMS <br />\n";
				// print_r($records_items);
				$expout .= "#code;description;type;weight;sortorder;footprint\n";				
				foreach ($records_items as $record_i){
					// DEBUG
					// echo "<br/>DEBUG :: ITEM <br />\n";
					// print_r($record_i);
					$expout .= $this->write_item( $record_i );
				}
			}
        }
        return $expout;
    }


	 /**
     * Turns domaine into an xml segment
     * @param domaine object
     * @return string xml segment
     */

    function write_domaine( $domaine ) {
    global $CFG;
        // initial string;
		$expout = "";
        // add comment		
		if ($domaine){
            $code = $domaine->code;
            $description = $this->purge_sep($domaine->description);
            $referentielid = $domaine->referentielid;
			$sortorder = $domaine->sortorder;
			$nb_competences = $domaine->nb_competences;			
			$expout .= stripslashes($this->output_codage_caractere($code)).";".stripslashes($this->output_codage_caractere($description)).";$sortorder;$nb_competences\n";
			
			// LISTE DES COMPETENCES DE CE DOMAINE
			$compteur_competence=0;
			$records_competences = referentiel_get_competences($domaine->id);
			if ($records_competences){
				// DEBUG
				// echo "<br/>DEBUG :: COMPETENCES <br />\n";
				// print_r($records_competences);
				foreach ($records_competences as $record_c){
					$expout .= "#code;description;sortorder;nb_item_competences\n";
					$expout .= $this->write_competence( $record_c );
				}
			}
        }
        return $expout;
    }



	 /**
     * Turns referentiel into an xml segment
     * @param competence object
     * @return string xml segment
     */

    function write_referentiel() {
    	global $CFG;		
        // initial string;
        $expout = "";
    	// add header
		$expout .= "#code;nom_referentiel;description;url;timecreated;nb_domaines;certificatethreshold;liste_codes_competences;liste_empreintes;logo\n";

		if ($this->rreferentiel){
		    $id = $this->rreferentiel->id;
        $name = $this->rreferentiel->name;
        $code = $this->rreferentiel->code;
        $description = $this->purge_sep($this->rreferentiel->description);
        $url = $this->rreferentiel->url;
			  $certificatethreshold = $this->rreferentiel->certificatethreshold;
			  $timemodified = $this->rreferentiel->timemodified;
			  $nb_domaines = $this->rreferentiel->nb_domaines;
			  $liste_codes_competence = $this->rreferentiel->liste_codes_competence;
			  $local = $this->rreferentiel->local;
			  $liste_empreintes_competence = $this->rreferentiel->liste_empreintes_competence;
			  $logo = $this->rreferentiel->logo;

			  // PAS DE LOGO ICI 
			  // $expout .= stripslashes($this->output_codage_caractere($code)).";".stripslashes($this->output_codage_caractere($name)).";".$this->output_codage_caractere($description).";$url;$timemodified;$nb_domaines;$certificatethreshold;".stripslashes($this->output_codage_caractere($liste_codes_competence)).";".stripslashes($this->output_codage_caractere($liste_empreintes_competence)).";".$logo."\n";
			  $expout .= stripslashes($this->output_codage_caractere($code)).";".stripslashes($this->output_codage_caractere($name)).";".$this->output_codage_caractere($description).";$url;".referentiel_timestamp_date_special($timemodified).";$nb_domaines;$certificatethreshold;".stripslashes($this->output_codage_caractere($liste_codes_competence)).";".stripslashes($this->output_codage_caractere($liste_empreintes_competence))."\n";			
			  // DOMAINES
			  if (isset($this->rreferentiel->id) && ($this->rreferentiel->id>0)){
				  // LISTE DES DOMAINES
				  $compteur_domaine=0;
				  $records_domaine = referentiel_get_domaines($this->rreferentiel->id);
		    	if ($records_domaine){
    				// afficher
					// DEBUG
					// echo "<br/>DEBUG ::<br />\n";
					// print_r($records_domaine);
					foreach ($records_domaine as $record_d){
						// DEBUG
						// echo "<br/>DEBUG ::<br />\n";
						// print_r($records_domaine);
						$expout .= "#code;description;referentielid;sortorder;nb_competences\n";						
						$expout .= $this->write_domaine( $record_d );
					}
				}
			} 
    }
    return $expout;
  }
	
	/***************************************************************************
		
	// IMPORT FUNCTIONS START HERE
	
	***************************************************************************/
     /**
	 * @param array referentiel array from xml tree
     * @return object import_referentiel object
	 * modifie la base de donnees 
     */
    function importation_referentiel_possible(){
	// selon les parametres soit cree une nouvelle instance 
	// soit modifie une instance courante de referentiel
	global $CFG;
		
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
		else if (isset($this->action) && ($this->action=="selectreferentiel")){
			if (!(isset($this->course->id) && ($this->course->id>0))){
				$this->error( get_string( 'incompletedata', 'referentiel' ) );
				return false;
			}
		}
		else if (isset($this->action) && ($this->action=="importreferentiel")){
			if (!(isset($this->course->id) && ($this->course->id>0))){
				$this->error( get_string( 'incompletedata', 'referentiel' ) );
				return false;
			}
		}
		return true;
	}
	
	
     /**
	 * @param array referentiel array from xml tree
     * @return object import_referentiel object
	 * modifie la base de donnees 
     */
    function import_referentiel( $lines ) {
	// recupere le tableau de lignes 
	// selon les parametres soit cree une nouvelle instance 
	// soit modifie une instance courante de referentiel
	global $SESSION;
	global $USER;
	global $CFG;
	
	if (!$this->importation_referentiel_possible()){
		exit;
	}
	
	// initialiser les variables
	// id du nouveau referentiel si celui ci doit être cree
	$new_referentiel_id=0;
	$auteur="";	
	$l_id_referentiel = "id_referentiel";
   	$l_code_referentiel = "code";
    $l_description_referentiel = "description";
	$l_date_creation = "timecreated";
	$l_nb_domaine = "nb_domaine";
	$l_seuil_certification = "certificatethreshold";
	$l_local = "local";
	$l_name = "name";
	$l_url_referentiel = "url";
	$l_liste_competences= "liste_competences";
	$l_liste_empreintes= "liste_empreintes";
	$l_logo= "logo";	
	$ok_referentiel_charge=false;
	$ok_domaine_charge=false;
	$ok_competence_charge=false;
	$ok_item_charge=false;
	
	$risque_ecrasement=false;
		
    // get some error strings
    $error_noname = get_string( 'xmlimportnoname', 'referentiel' );
    $error_nocode = get_string( 'xmlimportnocode', 'referentiel' );
	$error_override = get_string( 'overriderisk', 'referentiel' );
	
	// DEBUT	
	// Decodage 
	$line = 0;
	// TRAITER LA LIGNE D'ENTETE
	$nbl=count($lines);
	if ($nbl>0){ // premiere ligne entete fichier csv
		// echo "<br>$line : ".$lines[$line]."\n";
		
		// "#Moodle Referentiel CSV Export;latin1;Prénom NOM\n";
		
        $fields = explode($this->sep, str_replace( "\r", "", $lines[$line] ) );
	  	$line++;
		if (substr($lines[$line],0,1)=='#'){
			// labels			
	        /// If a line is incorrectly formatted 
            if (count($fields) < 3 ) {
	           	if ( count($fields) > 1 or strlen($fields[0]) > 1) { // no error for blank lines
					$this->error("ERROR ".$lines[$line].": Line ".$line."incorrectly formatted - ignoring\n");
				}
           	}
			if (isset($fields[1]) && ($fields[1]!="")){
		        $this->table_caractere_input=trim($fields[1]);
			}
			$auteur=trim($fields[2]);
		}
	}
	else{
		$this->error("ERROR : CSV File incorrect\n");
	}
	
	// echo "<br />DEBUG :: 991 : $this->table_caractere_input\n";
	
	
	if ($nbl>1){ // deuxieme ligne : entete referentiel
		// echo "<br>$line : ".$lines[$line]."\n";
		// #code;name;description;url;timecreated;
		// nb_domaines;seuil_certification;liste_competences
        $fields = explode($this->sep, str_replace( "\r", "", $lines[$line] ) );
        /// If a line is incorrectly formatted 
        if (count($fields) < 3 ) {
           	if ( count($fields) > 1 or strlen($fields[0]) > 1) { // no error for blank lines
				$this->error("ERROR ".$lines[$line].": Line ".$line."incorrectly formatted");
    		}
		}
		if (substr($lines[$line],0,1)=='#'){
			// labels
    	    $l_code_referentiel = trim($fields[0]);
			$l_name = trim($fields[1]);
	        $l_description_referentiel = trim($fields[2]);
			if (isset($fields[3]))
				$l_url_referentiel = trim($fields[3]);
			else
				$l_url_referentiel = "";
			if (isset($fields[4]))
			    $l_date_creation = trim($fields[4]);
			else
				$l_date_creation = "";
			if (isset($fields[5])) 
				$l_nb_domaines = trim($fields[5]);
			else
				$l_nb_domaines = "";
			if (isset($fields[6])) 
				$l_seuil_certificate = trim($fields[6]);
			else
				$l_seuil_certificate = "";
			if (isset($fields[7]))
				$l_liste_competences = trim($fields[7]);
			else
				$l_liste_competences = "";
			if (isset($fields[8]))
				$l_liste_empreintes = trim($fields[8]);
			else
				$l_liste_empreintes = "";
			if (isset($fields[9]))
				$l_logo = trim($fields[9]);
			else
				$l_logo = "";
		}
		else{
			// data  : referentiel
    		$code = $this->input_codage_caractere(trim($fields[0]));
	    	$name = $this->input_codage_caractere(trim($fields[1]));
			$description = $this->input_codage_caractere(trim($fields[2]));
			if (isset($fields[3]))
				$url = trim($fields[3]);
			else
				$url = "";
			if (isset($fields[4]))
				$timecreated = trim($fields[4]);
			else
				$timecreated = "";
			if (isset($fields[5]))
				$nb_domaines = trim($fields[5]);
			else
				$nb_domaines = "";
			if (isset($fields[6]))
				$certificatethreshold = trim($fields[6]);
			else
				$certificatethreshold = "";
			if (isset($fields[7]))
				$liste_competences = $this->input_codage_caractere(trim($fields[7]));
			else
				$liste_competences = "";
			if (isset($fields[8]))
				$liste_empreintes = $this->input_codage_caractere(trim($fields[8]));
			else
				$liste_empreintes = "";
			if (isset($fields[9]))
				$logo = trim($fields[9]);
			else
				$logo = "";
			$ok_referentiel_charge=true;
		}
		$line++;
	}
	
	// maintenant les données indispensables
	while (($line<$nbl) && ($ok_referentiel_charge==false)){ // data : referentiel
		// echo "<br>$line : ".$lines[$line]."\n";
		// #referentiel_id;code;description;timecreated;
		// nb_domaines;certificatethreshold;local;name;url;liste_competences
        $fields = explode($this->sep, str_replace( "\r", "", $lines[$line] ) );
        /// If a line is incorrectly formatted 
        if (count($fields) < 3 ) {
          	if ( count($fields) > 1 or strlen($fields[0]) > 1) { // no error for blank lines
				$this->error("ERROR ".$lines[$line].": Line ".$line."incorrectly formatted");
    		}
			continue;
		}
		// DEBUG
		// print_r($fields);
		// data  : referentiel
    	$code = $this->input_codage_caractere(trim($fields[0]));
	    $name = $this->input_codage_caractere(trim($fields[1]));
		$description = $this->input_codage_caractere(trim($fields[2]));
		if (isset($fields[3]))
			$url = trim($fields[3]);
		else
			$url = "";
		if (isset($fields[4]))
			$timecreated = trim($fields[4]);
		else
			$timecreated = "";
		if (isset($fields[5]))
			$nb_domaines = trim($fields[5]);
		else
			$nb_domaines = "";
		if (isset($fields[6]))
			$certificatethreshold = trim($fields[6]);
		else
			$certificatethreshold = "";
		if (isset($fields[7]))
			$liste_competences= $this->input_codage_caractere(trim($fields[7]));
		else
			$liste_competences= "";
		if (isset($fields[8]))
			$liste_empreintes = $this->input_codage_caractere(trim($fields[8]));
		else
			$liste_empreintes = "";
		if (isset($fields[9]))
			$logo = trim($fields[9]);
		else
			$logo = "";
			
		$ok_referentiel_charge=true;
	  	$line++;
	}
	
	if (!$ok_referentiel_charge){
		$this->error( get_string( 'incompletedata', 'referentiel' ) );
	}
	// this routine initialises the import object
    $re = $this->defaultreferentiel();
	
	$re->name=str_replace("'", " ",$name);
	// $re->name=addslashes($name);
	$re->code=$code;
	$re->description=str_replace("'", "`",$description);
	// $re->description=addslashes($description);
	$re->url=$url;
	$re->certificatethreshold=$certificatethreshold;
	$re->timemodified = $timecreated;
	$re->nb_domaines=$nb_domaines;
	$re->liste_codes_competence=$liste_competences;
	$re->liste_empreintes_competence=$liste_empreintes;
	$re->logo=$logo;
	
	/*
	// GROS BUG
	if ($id_referentiel!=""){
		$re->id=$id_referentiel;
	}
	*/
	$re->id=0;
	
	// DEBUG
	// print_r($re);
	
	// RISQUE ECRASEMENT ?
	$risque_ecrasement = false; // 
	if (($this->rreferentiel) && ($this->rreferentiel->id>0)){ // charger le referentiel associé à l'instance
		$this->rreferentiel = referentiel_get_referentiel_referentiel($this->rreferentiel->id);
		if ($this->rreferentiel){
			$risque_ecrasement = (($this->rreferentiel->name==$re->name) && ($this->rreferentiel->code==$re->code));
		}
	}
	
	// SI OUI arrêter
	if ($risque_ecrasement==true){
		if ($this->override!=1){
			$this->error($error_override);
		}
		else {
			// le referentiel courant est remplace
			$new_referentiel_id=$this->rreferentiel->id;
			$re->id=$new_referentiel_id;
		}
	}
	
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
			isset($re->course) && ($re->course>0))
		{
			// sauvegarder ?
			if ($this->course->id==$re->course){
				if 	(
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
	
	// DEBUG
	/*
	if ($risque_ecrasement)
		echo "<br />DEBUG : 607 : Risque d'ecrasement N:$this->newinstance O:$this->override\n";
	else 
		echo "<br />DEBUG : 607 : Pas de risque d'ecrasement N:$this->newinstance O:$this->override\n";
	*/
	
	if (($risque_ecrasement==false) || ($this->newinstance==1)) {
		// Enregistrer dans la base comme un nouveau referentiel du cours courant
		// DEBUG
		// echo "<br />DEBUG csv/format.php ligne 628<br />\n";
		// print_object($re);
		
		$new_referentiel_id=referentiel_add_referentiel($re); // retourne un id de la table refrentiel_referentiel
		$this->setReferentielId($new_referentiel_id);
	}
	else if (($risque_ecrasement==true) && ($this->override==1)) {
		// Enregistrer dans la base en remplaçant la version courante (update)
		// NE FAUDRAIT IL PAS SUPPRIMER LE REFERENTIEL AVANT DE LA RECHARGER ?
		$re->instance=$this->rreferentiel->id;
		$re->referentiel_id=$this->rreferentiel->id;
		// DEBUG
		// echo "<br />DEBUG csv/format.php ligne 638<br />MISE A JOUR  : ".$r->rreferentiel_id."\n";

		$ok=referentiel_update_referentiel($re); // retourne un id de la table referentiel_referentiel
		$new_referentiel_id=$this->rreferentiel->id;
	}
	else {
		// ni nouvelle instance ni recouvrement
		$this->error("ERREUR 2 ".$error_override );
		return false;
	}
	
	if (isset($new_referentiel_id) && ($new_referentiel_id>0)){
		// IMPORTER LE RESTE DU REFERENTIEL
		$dindex=0;
		$cindex=0;
		$iindex=0;
		
        $re->domaines = array();
		$new_domaine_id=0;
		$new_competence_id=0;
				
		$numero_domaine=0; // compteur pour suppleer le numero si non importe
		$numero_competence=0;
		$numero_item=0;
		$sortorder=0;
		$sortorder=0;
		$sortorder=0;
		
		$is="";
		$is_domaine=false;
		$is_competence=false;
		$is_item=false;

		$mode="add";
		
		while ($line<$nbl) {
			// echo "<br />DEBUG 652 :: <br />".$lines[$line]."\n";
           	$fields = explode($this->sep, str_replace( "\r", "", $lines[$line] ) );
	        if (count($fields) < 2 ) {
    	      	if ( count($fields) > 1 or strlen($fields[0]) > 1) { // no error for blank lines
					$this->error("ERROR ".$lines[$line].": Line ".$line."incorrectly formatted");
    			}
				continue;
			}
			
			// print_r($fields);
			// Label ou data ?
			// echo "<br />".substr($fields[0],0,1)."\n";
			
			if (substr($fields[0],0,1)=='#'){
				// labels
				// on s'en sert pour construire l'arbre
				$is=trim($fields[0]);
				$is_domaine=false;
				$is_competence=false;
				$is_item=false;
				
				switch($is){
					case '#code' :
						// #code;description;sortorder;nb_competences
						$is_domaine=true;
					break;
					case '#code' :
						// #code;description;sortorder;nb_item_competences
						$is_competence=true;
					break;
					case '#code' :
						// #code;description;type;weight;sortorder
						$is_item=true;
					break;
					default :
						$this->error("ERROR : CSV File incorrect line number:".$line."\n");
					break;
				}
			}
			else if (isset($is) && ($is!="")){
				// data
				switch($is){
					case '#code' :
						// $code;$description;$sortorder;$nb_competences
						// Domaines
						// data
						$code = addslashes($this->input_codage_caractere(trim($fields[0])));
						$description = addslashes($this->input_codage_caractere(trim($fields[1])));
						$sortorder = trim($fields[2]);
						$nb_competences = trim($fields[3]);
						
						if ($code!=""){
							// Creer un domaine
							$numero_domaine++; 
							$new_domaine = array();
							$new_domaine = $this->defaultdomaine();
							
							$new_domaine->code=$code;
							if ($description!="")
								$new_domaine->description = $description;
							
							if ($sortorder!="")
								$new_domaine->sortorder=$sortorder;
							else
								$new_domaine->sortorder=$numero_domaine;
							if ($nb_competences!="")
								$new_domaine->nb_competences=$nb_competences;
							else
								$new_domaine->nb_competences=0;
							
							$new_domaine->referentielid=$new_referentiel_id;
							
							// sauvegarder dans la base
							// remplacer l'id du referentiel importe par l'id du referentiel cree
							// trafiquer les donnees pour appeler la fonction ad hoc
							$new_domaine->referentielid=$new_referentiel_id;
							$new_domaine->instance=$new_referentiel_id; // pour que ca marche
							$new_domaine->new_code_domaine=$new_domaine->code;
							$new_domaine->new_description_domaine=$new_domaine->description;
							$new_domaine->new_num_domaine=$new_domaine->sortorder;
							$new_domaine->new_nb_competences=$new_domaine->sortorder;
							$new_domaine_id=referentiel_add_domaine($new_domaine);
							
							if (isset($new_domaine_id) && ($new_domaine_id>0)){
								$new_domaine->id=$new_domaine_id;
							}
							else{
								$new_domaine->id=0;
							}
							
							// enregistrer
							$dindex++;
							$re->domaines[$dindex]=$new_domaine;
							$cindex=0;
							$re->domaines[$dindex]->competences=array();
							$numero_competence=0;
							$ok_domaine_charge=true;
						}
						else{
							$ok_domaine_charge=false;
						}
					break;
					case '#code' :
						// $competence_id;$code;$description;$domainid;
						// $sortorder;$nb_item_competences
						$code = addslashes($this->input_codage_caractere(trim($fields[0])));
						$description = addslashes($this->input_codage_caractere(trim($fields[1])));
						$sortorder = trim($fields[2]);
						$nb_item_competences = trim($fields[3]);
						
						if (($code!="") && ($ok_domaine_charge) && ($new_domaine_id>0)){
							// Creer une competence
							$new_competence_id=0;
							$numero_competence++; 
							$new_competence = array();
							$new_competence = $this->defaultcompetence();
							
							$new_competence->id=0;	
							$new_competence->code=$code;
							if ($description!="")
								$new_competence->description = $description;
							if ($sortorder!="")
								$new_competence->sortorder=$sortorder;
							else
								$new_competence->sortorder=$numero_competence;
							if ($nb_item_competences!="")
								$new_competence->nb_item_competences=$nb_item_competences;
							else
								$new_competence->nb_item_competences=0;
								
							if (isset($new_domaine_id) && ($new_domaine_id>0)){
								$new_competence->domainid=$new_domaine_id;
							}
							else{
								$new_competence->domainid=0;
							}
							
							// sauvegarder dans la base
							// remplacer l'id du referentiel importe par l'id du referentiel cree
							$new_competence->domainid=$new_domaine_id;
							// trafiquer les donnees pour appeler la fonction ad hoc
							$new_competence->instance=$new_referentiel_id; // pour que ca marche
							$new_competence->new_code_competence=$new_competence->code;
							$new_competence->new_description_competence=$new_competence->description;
							$new_competence->new_ref_domaine=$new_domaine_id;
							$new_competence->new_num_competence=$new_competence->sortorder;
							$new_competence->new_nb_item_competences=$new_competence->nb_item_competences;
							// creation
							$new_competence_id=referentiel_add_competence($new_competence);
							$new_competence->id=$new_competence_id;
							
							// enregistrer
							$cindex++;
							$re->domaines[$dindex]->competences[$cindex]=$new_competence;
							$iindex=0; // nouveaux items à suivre
							$re->domaines[$dindex]->competences[$cindex]->items=array();
							
							$numero_item=0;
							$ok_competence_charge=true;
						}
						else{
							$ok_competence_charge=false;
						}
					break;
					case '#code' :
						// $code;$description;$type;$weight;$sortorder;$footprint
						$code = $this->input_codage_caractere(addslashes(trim($fields[0])));
						$description = $this->input_codage_caractere(addslashes(trim($fields[1])));
						$type = $this->input_codage_caractere(addslashes(trim($fields[2])));
						$weight = trim($fields[3]);
						$sortorder = trim($fields[4]);
						if (isset($fields[5]) && (trim($fields[5])!="")){
							$footprint = trim($fields[5]);
						}
						else{
							$footprint = "1";
						}
						if (($code!="") && ($ok_competence_charge) && ($new_competence_id>0)){
							// Creer un domaine
							$numero_item++; 
							$new_item = array();
							$new_item = $this->defaultitem();
							$new_item->code=$code;
							if ($description!="")
								$new_item->description = $description;
							$new_item->referentielid=$new_referentiel_id;
							$new_item->skillid=$new_competence_id;
							$new_item->type=$type;
							$new_item->weight=$weight;
							if ($sortorder!="")
								$new_item->sortorder=$sortorder;
							else
								$new_item->sortorder=$numero_item;
							$new_item->footprint=$footprint;
														
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
							$new_item->id=$new_item_id;
							
							$iindex++;				
							$re->domaines[$dindex]->competences[$cindex]->items[$iindex]=$new_item; 
							$ok_item_charge=true;
						}
						else{
							$ok_item_charge=false;
						}
					break;
					default :
						$this->error("ERROR : CSV File incorrect line number:".$line."\n");
					break;
				}
			}
			// that's all folks
			$line++;
        } // end of while loop
		if ($mode=="add"){
			// rien de special ici ?
		}
	    return $re;
	}
	return false;
}


    /**
     * parse the array of lines into an array of questions
     * this *could* burn memory - but it won't happen that much
     * so fingers crossed!
     * @param array lines array of lines from the input file
     * @return array (of objects) question objects
     */
    function read_import_referentiel($lines) {
        // we just need it as one array
		$re=$this->import_referentiel($lines);
        return $re;
    }
}


/** ******************************************

EXPORT ACTIVITES

*/

// ACTIVITES : export des activites
class aformat_csv extends aformat_default {
// NON SUPPORTE POUR LE FORMAT CSV
	var $sep = ";";
	
	var $table_caractere_input='latin1'; // par defaut import latin1
	var $table_caractere_output='latin1'; // par defaut export latin1


	// ----------------
	function guillemets($texte){
		return '"'.trim($texte).'"';
	}

	// ----------------
	function purge_sep($texte){
		$cherche= array('"',$this->sep,"\r\n", "\n", "\r");
		$remplace= array("''",",", " ", " ", " ");
		return $this->guillemets(str_replace($cherche, $remplace, $texte));
	}


	// ----------------
	function recode_latin1_vers_utf8($string) {
		return mb_convert_encoding($string, "UTF-8", mb_detect_encoding($string, "UTF-8, ISO-8859-1, ISO-8859-15", true));
	}


	// ----------------
	function recode_utf8_vers_latin1($string) {
		return mb_convert_encoding($string, "ISO-8859-1", mb_detect_encoding($string, "UTF-8, ISO-8859-1, ISO-8859-15", true));
	}
	

	 /**
     * @param 
     * @return string recode latin1
	 * 
     */
    function input_codage_caractere($s){
		if (!isset($this->table_caractere_input) || ($this->table_caractere_input=="")){
			$this->table_caractere_input='latin1';
		}
		
		if ($this->table_caractere_input=='latin1'){
			$s=$this->recode_latin1_vers_utf8($s);
		}
		return $s;
	}
	
	 /**
     * @param 
     * @return string recode utf8
	 * 
     */
    function output_codage_caractere($s){
		if (!isset($this->table_caractere_output) || ($this->table_caractere_output=="")){
			$this->table_caractere_output='latin1';
		}
		
		if ($this->table_caractere_output=='latin1'){
			$s=$this->recode_utf8_vers_latin1($s);
		}
		return $s;
	}



    function provide_export() {
      return true;
    }

	function provide_import() {
        return false;
    }

	function repchar( $text ) {
	    // escapes 'reserved' characters # = ~ { ) and removes new lines
    	$reserved = array( '#','=','~','{','}',"\n","\r" );
	    $escaped = array( '\#','\=','\~','\{','\}',' ','' );
		return str_replace( $reserved, $escaped, $text ); 
	}

	function presave_process( $content ) {
	  // override method to allow us to add xhtml headers and footers

  		global $CFG;
		$xp =  "Moodle Referentiel CSV Export\n";
		$xp .= $content;
  		return $xp;
	}

	function export_file_extension() {
  		return ".csv";
	}

    /**
     * Include an image encoded in base 64
     * @param string imagepath The location of the image file
     * @return string xml code segment 
     */
    function writeimage( $imagepath ) {
        global $CFG;
   		
        if (empty($imagepath)) {
            return '';
        }

        $courseid = $this->course->id;
        if (!$binary = file_get_contents( "{$CFG->dataroot}/$courseid/$imagepath" )) {
            return '';
        }

        $content = "    <image_base64>\n".addslashes(base64_encode( $binary ))."\n".
            "\n    </image_base64>\n";
        return $content;
    }

	
	/**
     * generates <text></text> tags, processing raw text therein 
     * @param int ilev the current indent level
     * @param boolean short stick it on one line
     * @return string formatted text
     */

    function write_ligne( $raw, $sep="/", $nmaxcar=80) {
        // insere un saut de ligne apres le 80 caractere 
		$nbcar=strlen($raw);
		if ($nbcar>$nmaxcar){
			$s1=substr( $raw,0,$nmaxcar);
			$pos1=strrpos($s1,$sep);
			if ($pos1>0){
				$s1=substr( $raw,0,$pos1);
				$s2=substr( $raw,$pos1+1);
			}
			else {
				$s1=substr( $raw,0,$nmaxcar);
				$s2=substr( $raw,$nmaxcar);
			}
		    return $s1." ".$s2;
		}
		else{
			return $raw;
		}
    }

	 /**
     * Turns document into an xml segment
     * @param document object
     * @return string xml segment
     */

    function write_document( $document ) {
    global $CFG;
       // initial string;
        $expout = "";
		if ($document){
			$id_document = $document->id ;		
            $type = trim($document->type);
            $description = $this->purge_sep($document->description);
			$url = $document->url;
            $activityid = $document->activityid;
            $expout .= "$id_document;".stripslashes($this->output_codage_caractere($type)).";".stripslashes($this->output_codage_caractere($description)).";$url;$activityid\n";   
        }
        return $expout;
    }

    /**
     * Turns activite into an xml segment
     * @param activite object
     * @return string xml segment
     */

    function write_activite( $activite ) {
    global $CFG;
       // initial string;
        $expout = "";
        // add comment
		if ($activite){
			// DEBUG
			// echo "<br />\n";
			// print_r($activite);
			$id_activite = $activite->id;
            $type_activite = $this->purge_sep(strip_tags($activite->type_activite));
			$description = $this->purge_sep(strip_tags($activite->description));
            $comptencies = trim($activite->comptencies);
            $comment = $this->purge_sep($activite->comment);
            $instanceid = $activite->instanceid;
            $referentielid = $activite->referentielid;
            $course = $activite->course;
			$userid = trim($activite->userid);
			$teacherid = $activite->teacherid;
			$timecreated = $activite->timecreated;
			$timemodified = $activite->timemodified;
			$approved = $activite->approved;
			
			$expout .= "#id_activite;type_activite;description;comptencies;comment;instanceid;referentielid;course;userid;teacherid;timecreated;timemodified;approved\n";
			$expout .= "$id_activite;".stripslashes($this->output_codage_caractere($type_activite)).";".stripslashes($this->output_codage_caractere($description)).";".stripslashes($this->output_codage_caractere($comptencies)).";".stripslashes($this->output_codage_caractere($comment)).";$instanceid;$referentielid;$course;$userid;$teacherid;".referentiel_timestamp_date_special($timecreated).";".referentiel_timestamp_date_special($timemodified).";$approved\n";
			
			// DOCUMENTS
			$records_documents = referentiel_get_documents($activite->id);
			
			if ($records_documents){
				$expout .= "#id_document;type;description;url;activityid\n";
				foreach ($records_documents as $record_d){
					$expout .= $this->write_document( $record_d );
				}
			}
		}	
        return $expout;
    }

	
	 /**
     * Turns referentiel instance into an xml segment
     * @param referentiel instanceobject
     * @return string xml segment
     */

    function write_liste_activites() {
    	global $CFG;
        // initial string;
        $expout = "";
		// 
		if ($this->ireferentiel){
			$id = $this->ireferentiel->id;
            $name = $this->purge_sep($this->ireferentiel->name);
            $description = $this->purge_sep($this->ireferentiel->description);
            $domainlabel = trim($this->ireferentiel->domainlabel);
            $skilllabel = trim($this->ireferentiel->skilllabel);
            $itemlabel = trim($this->ireferentiel->itemlabel);
            $timecreated = $this->ireferentiel->timecreated;
            $course = $this->ireferentiel->course;
            $referentielid = $this->ireferentiel->referentielid;
			$visible = $this->ireferentiel->visible;
			
			// $expout .= "#Instance de referentiel : $this->ireferentiel->name\n";
			$expout .= "#id_instance;name;description;domainlabel;skilllabel;itemlabel;timecreated;course;referentielid;visible\n";
			$expout .= "$id;".stripslashes($this->output_codage_caractere($name)).";".stripslashes($this->output_codage_caractere($description)).";".stripslashes($this->output_codage_caractere($domainlabel)).";".stripslashes($this->output_codage_caractere($skilllabel)).";".stripslashes($this->output_codage_caractere($itemlabel)).";".referentiel_timestamp_date_special($timecreated).";$course;$referentielid;$visible\n";
			
			// ACTIVITES
			if (isset($this->ireferentiel->id) && ($this->ireferentiel->id>0)){
				$records_activites = referentiel_get_activites_instance($this->ireferentiel->id);
		    	if ($records_activites){
					foreach ($records_activites as $record_a){
						// DEBUG
						// print_r($record_a);
						// echo "<br />\n";
						$expout .= $this->write_activite( $record_a );
					}
				}
			}
		}
        return $expout;
    }
}

// ##########################################################################################################
// *************************************
// CERTIFICATS : export des certificats
// *************************************
class cformat_csv extends cformat_default {
	var $sep = ";";
	
	var $table_caractere_input='latin1'; // par defaut import latin1
	var $table_caractere_output='latin1'; // par defaut export latin1

	// ----------------
	function guillemets($texte){
		return '"'.trim($texte).'"';
	}


	// ----------------
	function purge_sep($texte){
		$cherche= array('"',$this->sep,"\r\n", "\n", "\r");
		$remplace= array("''",",", " ", " ", " ");
		return $this->guillemets(str_replace($cherche, $remplace, $texte));
	}


	// ----------------
	function recode_latin1_vers_utf8($string) {
		return mb_convert_encoding($string, "UTF-8", mb_detect_encoding($string, "UTF-8, ISO-8859-1, ISO-8859-15", true));
	}


	// ----------------
	function recode_utf8_vers_latin1($string) {
		return mb_convert_encoding($string, "ISO-8859-1", mb_detect_encoding($string, "UTF-8, ISO-8859-1, ISO-8859-15", true));
	}
	

	 /**
     * @param 
     * @return string recode latin1
	 * 
     */
    function input_codage_caractere($s){
		if (!isset($this->table_caractere_input) || ($this->table_caractere_input=="")){
			$this->table_caractere_input='latin1';
		}
		
		if ($this->table_caractere_input=='latin1'){
			$s=$this->recode_latin1_vers_utf8($s);
		}
		return $s;
	}
	
	 /**
     * @param 
     * @return string recode utf8
	 * 
     */
    function output_codage_caractere($s){
		if (!isset($this->table_caractere_output) || ($this->table_caractere_output=="")){
			$this->table_caractere_output='latin1';
		}
		
		if ($this->table_caractere_output=='latin1'){
			$s=$this->recode_utf8_vers_latin1($s);
		}
		return $s;
	}

    function provide_export() {
      return true;
    }

	function provide_import() {
        return false;
    }

	function repchar( $text ) {
	    // escapes 'reserved' characters # = ~ { ) and removes new lines
    	$reserved = array( '#','=','~','{','}',"\n","\r" );
	    $escaped = array( '\#','\=','\~','\{','\}',' ','' );
		return str_replace( $reserved, $escaped, $text ); 
	}

	function presave_process( $content ) {
	  // override method to allow us to add xhtml headers and footers
  		global $CFG;
		$xp =  "#Moodle Certification CSV Export;latin1;\n";
		$xp .= $content;
  		return $xp;
	}

	function export_file_extension() {
  		return ".csv";
	}

    /**
     * Include an image encoded in base 64
     * @param string imagepath The location of the image file
     * @return string xml code segment 
     */
    function writeimage( $imagepath ) {
        global $CFG;
   		
        if (empty($imagepath)) {
            return '';
        }

        $courseid = $this->course->id;
        if (!$binary = file_get_contents( "{$CFG->dataroot}/$courseid/$imagepath" )) {
            return '';
        }

        $content = "    <image_base64>\n".addslashes(base64_encode( $binary ))."\n".
            "\n    </image_base64>\n";
        return $content;
    }

	
	/**
     * generates <text></text> tags, processing raw text therein 
     * @param int ilev the current indent level
     * @param boolean short stick it on one line
     * @return string formatted text
     */

    function write_ligne( $raw, $sep="/", $nmaxcar=80) {
        // insere un saut de ligne apres le 80 caracter 
		$nbcar=strlen($raw);
		if ($nbcar>$nmaxcar){
			$s1=substr( $raw,0,$nmaxcar);
			$pos1=strrpos($s1,$sep);
			if ($pos1>0){
				$s1=substr( $raw,0,$pos1);
				$s2=substr( $raw,$pos1+1);
			}
			else {
				$s1=substr( $raw,0,$nmaxcar);
				$s2=substr( $raw,$nmaxcar);
			}
		    return $s1." ".$s2;
		}
		else{
			return $raw;
		}
    }

    function write_item( $item ) {
    global $CFG;
        // initial string;
        $expout = "";
        // add comment
        // $expout .= "\nitem: $item->id\n";
		// $expout .= "id;code;description;referentielid;skillid;type;weight;sortorder\n";
		// 
		if ($item){
			// DEBUG
			// echo "<br />\n";
			// print_r($item);
			$id_item = $item->id;
            $code = $item->code;
            $description = $this->purge_sep($item->description);
            $referentielid = $item->referentielid;
            $skillid = $item->skillid;
			$type = $item->type;
			$weight = $item->weight;
			$sortorder = $item->sortorder;
			$footprint = $item->footprint;
            $expout .= "$id_item;".stripslashes($this->output_codage_caractere($code)).";".stripslashes($this->output_codage_caractere($description)).";".$this->output_codage_caractere($type).";$weight;$sortorder;$footprint\n";
		}
        return $expout;
    }

	 /**
     * Turns competence into an xml segment
     * @param competence object
     * @return string xml segment
     */

    function write_competence( $competence ) {
    global $CFG;
        // initial string;
        $expout = "";
        // add comment		
        // $expout .= "\ncompetence: $competence->id\n";
		if ($competence){
			$id_competence = $competence->id;
            $code = $competence->code;
            $description = $this->purge_sep($competence->description);
            $domainid = $competence->domainid;
			$sortorder = $competence->sortorder;
			$nb_item_competences = $competence->nb_item_competences;
			$expout .= "$id_competence;".stripslashes($this->output_codage_caractere($code)).";".stripslashes($this->output_codage_caractere($description)).";$domainid;$sortorder;$nb_item_competences\n";
			
			// ITEM
			$compteur_item=0;
			$records_items = referentiel_get_item_competences($competence->id);
			
			if ($records_items){
				// DEBUG
				// echo "<br/>DEBUG :: ITEMS <br />\n";
				// print_r($records_items);
				$expout .= "#id_item;code;description;referentielid;skillid;type;weight;sortorder\n";				
				foreach ($records_items as $record_i){
					// DEBUG
					// echo "<br/>DEBUG :: ITEM <br />\n";
					// print_r($record_i);
					$expout .= $this->write_item( $record_i );
				}
			}
        }
        return $expout;
    }


	 /**
     * Turns domaine into an xml segment
     * @param domaine object
     * @return string xml segment
     */

    function write_domaine( $domaine ) {
    global $CFG;
        // initial string;
        $expout = "#domaine_id;code;description;referentielid;sortorder;nb_competences\n";
        // add comment		
		if ($domaine){
            $code = $domaine->code;
            $description = $this->purge_sep($domaine->description);
            $referentielid = $domaine->referentielid;
			$sortorder = $domaine->sortorder;
			$nb_competences = $domaine->nb_competences;			
			$expout .= stripslashes($this->output_codage_caractere($code)).";".stripslashes($this->output_codage_caractere($description)).";$referentielid;$sortorder;$nb_competences\n";
			
			// LISTE DES COMPETENCES DE CE DOMAINE
			$compteur_competence=0;
			$records_competences = referentiel_get_competences($domaine->id);
			if ($records_competences){
				// DEBUG
				// echo "<br/>DEBUG :: COMPETENCES <br />\n";
				// print_r($records_competences);
				foreach ($records_competences as $record_c){
					$expout .= "#id_competence;code;description;sortorder;nb_item_competences\n";								
					$expout .= $this->write_competence( $record_c );
				}
			}
        }
        return $expout;
    }



	 /**
     * Turns referentiel into an xml segment
     * @param competence object
     * @return string xml segment
     */

    function write_referentiel( $referentiel ) {
    	global $CFG;
        // initial string;
		$expout ="";
		if ($referentiel){
			$id = $referentiel->id;
            $name = $referentiel->name;
            $code = $referentiel->code;
            $description = $this->purge_sep($referentiel->description);
            $url = $referentiel->url;
			$certificatethreshold = $referentiel->certificatethreshold;
			$timemodified = $referentiel->timemodified;			
			$nb_domaines = $referentiel->nb_domaines;
			$liste_codes_competence = $referentiel->liste_codes_competence;
			$liste_empreintes_competence = $referentiel->liste_empreintes_competence;
			$local = $referentiel->local;
        	
			// $expout = "#Referentiel : ".$referentiel->id." : ".stripslashes($this->output_codage_caractere($referentiel->name))."\n";
            // add header
            if ($this->format_condense){
                $expout .= "#name;code;description;\n";
                $expout .= stripslashes($this->output_codage_caractere($name)).";".stripslashes($this->output_codage_caractere($code)).";".stripslashes($this->output_codage_caractere($description))."\n";

                $expout .= "#user_id;Prenom;NOM;num_student;";
                $expout .= $this->liste_codes_competences($referentiel->id)."\n";
            }
            else{
                $expout .= "#id_referentiel;name;code;description;url;certificatethreshold;timemodified;nb_domaines;liste_codes_competences;liste_empreintes_competences;local\n";
                $expout .= "$id;".stripslashes($this->output_codage_caractere($name)).";".stripslashes($this->output_codage_caractere($code)).";".stripslashes($this->output_codage_caractere($description)).";$url;$certificatethreshold;".referentiel_timestamp_date_special($timemodified).";$nb_domaines;".stripslashes($this->output_codage_caractere($liste_codes_competence)).";".stripslashes($this->output_codage_caractere($liste_empreintes_competence)).";$local\n";

                // DOMAINES
                if (isset($referentiel->id) && ($referentiel->id>0)){
                    // LISTE DES DOMAINES
                    $compteur_domaine=0;
                    $records_domaine = referentiel_get_domaines($referentiel->id);
                    if ($records_domaine){
                        // afficher
                        // DEBUG
                        // echo "<br/>DEBUG ::<br />\n";
                        // print_r($records_domaine);
                        foreach ($records_domaine as $record_d){
                            // DEBUG
                            // echo "<br/>DEBUG ::<br />\n";
                            // print_r($records_domaine);
                            $expout .= $this->write_domaine( $record_d );
                        }
                    }
				}
			} 
    }
    return $expout;
  }

	function write_etablissement( $record ) {
        // initial string;
        $expout = "";
        // add comment
        // $expout .= "\netablissement: $record->id\n";
		if ($record){
			// $expout .= "#id_etablissement;idnumber;name;dresse_etablissement\n";
			$id = trim( $record->id );
			$idnumber = trim( $record->idnumber);
			$name = $this->purge_sep($record->name);
			$address = $this->purge_sep($record->address);
			
			$expout .= "$id;$idnumber;".stripslashes($this->output_codage_caractere($name)).";".stripslashes($this->output_codage_caractere($address))."\n";
        }
        return $expout;
    }

    function write_liste_etablissements() {
    	global $CFG;
        // initial string;
        $expout = ""; 
		// ETABLISSEMENTS
		$records_all_etablissements = referentiel_get_etablissements();
		if ($records_all_etablissements){
			$expout .= "#id_etablissement;idnumber;name;address\n";		
			foreach ($records_all_etablissements as $record){
				if ($record){
					$expout.=$this->write_etablissement($record);
				}
			}
        }
        return $expout;
    }

	 /**
     * Turns referentiel instance into an xml segment
     * @param referentiel instanceobject
     * @return string xml segment
     */
	 /**
     *
     * @param referentiel instanceobject
     * @return string xml segment
     */

    function write_certificat( $record ) {
    	global $CFG;
        // initial string;
        $expout = "";
    	// $expout .= "\ncertificate : $record->id\n";
		// USER
        if ($record){
			//$expout .= "#id_student;user_id;login;Prenom;NOM;num_student;ddn_student;lieu_naissance;departement_naissance;adresse_student;ref_etablissement;id_certificat;comment;competences_certificat;decision_jury;date_decision;referentielid;verrou;valide;evaluation\n";
			$ok_student=false;

			$record_student = referentiel_get_student_user($record->userid);
            if ($record_student){
                $id_student = trim($record_student->id );
                $ref_etablissement = trim($record_student->ref_etablissement);
                $num_student = trim($record_student->num_student);
                $ddn_student = trim($record_student->ddn_student);
                $lieu_naissance = $this->purge_sep($record_student->lieu_naissance);
                $departement_naissance = $this->purge_sep($record_student->departement_naissance);
                $adresse_student = $this->purge_sep($record_student->adresse_student);
                if (!$this->format_condense){
                    $expout .= "$id_student;".$record->userid.";".$this->output_codage_caractere(referentiel_get_user_login($record->userid)).";".stripslashes($this->output_codage_caractere(referentiel_get_user_prenom($record->userid))).";".stripslashes($this->output_codage_caractere(referentiel_get_user_nom($record->userid))).";$num_student;$ddn_student;".stripslashes($this->output_codage_caractere($lieu_naissance)).";".stripslashes($this->output_codage_caractere($departement_naissance)).";".stripslashes($this->output_codage_caractere($adresse_student)).";$ref_etablissement;";
                }
                else{
                    $expout .= $record->userid.";".stripslashes($this->output_codage_caractere(referentiel_get_user_prenom($record->userid))).";".stripslashes($this->output_codage_caractere(referentiel_get_user_nom($record->userid))).";$num_student;";
                }
                $ok_student=true;
			}
			if ($ok_student==false){
                if (!$this->format_condense){
                    $expout .= ";".$record->userid.";;;;;;;;;;";
                }
                else{
                    $expout .= $record->userid.";;;;";
                }
			}

			// DEBUG
			// echo "<br />DEBUG LIGNE 1021<br />\n";
			// print_r($this->ireferentiel);
			$id = trim( $record->id );
            $comment = $this->purge_sep($record->comment);
            $synthese_certificate = $this->purge_sep($record->synthese_certificat);
            $competences_certificate =  trim($record->competences_certificat) ;
            $decision_jury = $this->purge_sep($record->decision_jury);
            $date_decision = trim($record->date_decision);
            $userid = trim( $record->userid);
            $teacherid = trim( $record->teacherid);
            $referentielid = trim( $record->referentielid);
			$verrou = trim( $record->verrou );
			$valide = trim( $record->valide );
			$evaluation = trim( $record->evaluation );
            $synthese_certificate = $this->purge_sep($record->synthese_certificat);

			if (!$this->format_condense){
                $expout .= "$id;".stripslashes($this->output_codage_caractere($comment)).";".stripslashes($this->output_codage_caractere($synthese_certificat)).";".stripslashes($this->output_codage_caractere($competences_certificat)).";".stripslashes($this->output_codage_caractere($decision_jury)).";".referentiel_timestamp_date_special($date_decision).";$referentielid;$verrou;$valide;$evaluation;$synthese_certificat\n";
            }
            else{
                $expout .= $this->certificate_pourcentage($competences_certificat, $this->referentielid)."\n";
            }
        }

        return $expout;
}

	 /**
     * Turns referentiel instance into an xml segment
     * @param referentiel instanceobject
     * @return string xml segment
     */

    function write_certification() {
    	global $CFG;
        // initial string;
        $expout = "";
		// 
		if ($this->ireferentiel){
            $id = $this->ireferentiel->id;
            $name = trim($this->ireferentiel->name);
            $description = $this->purge_sep($this->ireferentiel->description);
            $domainlabel = trim($this->ireferentiel->domainlabel);
            $skilllabel = trim($this->ireferentiel->skilllabel);
            $itemlabel = trim($this->ireferentiel->itemlabel);
            $timecreated = $this->ireferentiel->timecreated;
            $course = $this->ireferentiel->course;
            $referentielid = $this->ireferentiel->referentielid;
            $visible = $this->ireferentiel->visible;
			
    		// $expout .= "#Instance de referentiel : $this->ireferentiel->name\n";
            $expout .= "#id_instance;name;description;domainlabel;skilllabel;itemlabel;timecreated;course;referentielid;visible\n";
            $expout .= "$id;".stripslashes($this->output_codage_caractere($name)).";".stripslashes($this->output_codage_caractere($description)).";".stripslashes($this->output_codage_caractere($domainlabel)).";".stripslashes($this->output_codage_caractere($skilllabel)).";".stripslashes($this->output_codage_caractere($itemlabel)).";".referentiel_timestamp_date_special($timecreated).";$course;$referentielid;$visible\n";

            if (empty($this->rreferentiel) && (!empty($this->ireferentiel->referentielid) && ($this->ireferentiel->referentielid>0))){
                $this->rreferentiel = referentiel_get_referentiel_referentiel($this->ireferentiel->referentielid);
            }
    
            if (!empty($this->rreferentiel)){
                $expout .= $this->write_referentiel($this->rreferentiel);
                if (!$this->format_condense){
                    $expout .= $this->write_liste_etablissements($this->rreferentiel);
				}
				
                if (!$this->records_certificats){
                    $this->records_certificats = referentiel_get_certificats($this->rreferentiel->id);
                }

                if ($this->records_certificats){
                    if (!$this->format_condense){
                        $expout .= "#id_student;user_id;login;Prenom;NOM;num_student;ddn_student;lieu_naissance;departement_naissance;adresse_student;ref_etablissement;id_certificat;comment;synthese_certificat;competences_certificat;decision_jury;date_decision;referentielid;verrou;valide;evaluation\n";
                    }
                    foreach ($this->records_certificats as $record){
                        $expout .= $this->write_certificat( $record );
                    }
                }
            }
            return $expout;
        }
    }
    
    // -------------------
    function liste_codes_competences($referentielid){
    global $OK_REFERENTIEL_DATA;
    // COMPETENCES
    global $t_competence;  // codes des competences
	// affichage
	$s='';

        if ($referentielid){
            if (!isset($OK_REFERENTIEL_DATA) || ($OK_REFERENTIEL_DATA==false) ){
                $OK_REFERENTIEL_DATA=referentiel_initialise_data_referentiel($referentielid);
            }

            if (isset($OK_REFERENTIEL_DATA) && ($OK_REFERENTIEL_DATA==true)){
                for ($i=0; $i<count($t_competence); $i++){
					$s.=$t_competence[$i].';';
                }
            }
        }
        return $s;

    }
    
    
    // -------------------
    function certificate_pourcentage($liste_code, $referentielid){
    // retourne les pourcentages par competence

    $separateur1='/';
    $separateur2=':';
    
    global $OK_REFERENTIEL_DATA;
    global $t_domaine;
    global $t_domaine_coeff;

    // COMPETENCES
    global $t_competence;
    global $t_competence_coeff;

    // ITEMS
    global $t_item_code;
    global $t_item_coeff; // coefficient poids determine par le modele de calcul (soit poids soit poids / empreinte)
    global $t_item_domaine; // index du domaine associé à un item
    global $t_item_competence; // index de la competence associée à un item
    global $t_item_poids; // poids
    global $t_item_empreinte;
    global $t_nb_item_domaine;
    global $t_nb_item_competence;

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
	// affichage
	$s='';

	// donnees globales du referentiel
	if ($referentielid){

		if (!isset($OK_REFERENTIEL_DATA) || ($OK_REFERENTIEL_DATA==false) ){
			$OK_REFERENTIEL_DATA=referentiel_initialise_data_referentiel($referentielid);
		}

		if (isset($OK_REFERENTIEL_DATA) && ($OK_REFERENTIEL_DATA==true)){
		// DEBUG
		// echo "<br />CODE <br />\n";
		// referentiel_affiche_data_referentiel($referentielid, $params);

		// recuperer les items valides
		$tc=array();
		$liste_code=referentiel_purge_dernier_separateur($liste_code, $separateur1);

		// DEBUG
		// echo "<br />DEBUG :: print_lib_certificate.php :: 917 :: LISTE : $liste_code<br />\n";

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
            /*
        	for ($i=0; $i<count($t_domaine_coeff); $i++){
				if ($t_domaine_coeff[$i]){
					$s.=$t_domaine[$i].';';
				}
				else{
					$s.=$t_domaine[$i].';';
				}
			}
			$s.="\n";

            for ($i=0; $i<count($t_domaine_coeff); $i++){
				if ($t_domaine_coeff[$i]){
					$s.=referentiel_pourcentage($t_certif_domaine_poids[$i], $t_domaine_coeff[$i]).'%;';
				}
				else{
					$s.='0%;';
				}
			}
			$s.="\n";
            */

            /*
			for ($i=0; $i<count($t_competence); $i++){
					$s.=$t_competence[$i].';';
			}
			$s.="\n";
			*/
			for ($i=0; $i<count($t_competence); $i++){
				if ($t_competence_coeff[$i]){
					$s.=referentiel_pourcentage($t_certif_competence_poids[$i], $t_competence_coeff[$i]).'%;';
				}
				else{
					$s.='0%;';
				}
			}
			// $s.="\n";

			// ITEMS
            /*
			for ($i=0; $i<count($t_item_code); $i++){
				if ($t_item_empreinte[$i]){
					if ($t_certif_item_valeur[$i]>=$t_item_empreinte[$i])
						$s.=$t_item_code[$i].';';
					else
						$s.=$t_item_code[$i].';';
				}
				else{
					$s.=';';
				}
			}
			$s.="\n";

			for ($i=0; $i<count($t_item_coeff); $i++){
				if ($t_item_empreinte[$i]){
					if ($t_certif_item_valeur[$i]>=$t_item_empreinte[$i]){
						$s.='100%;';
					}
					else{
						$s.=referentiel_pourcentage($t_certif_item_valeur[$i], $t_item_empreinte[$i]).'%;';
					}
				}
				else {
					$s.=';';
				}
			}
			$s.="\n";
            */
		}
	}
	}

	return $s;
    }
}  // fin de la classe cformat


// ################################################################################################################
// studentS : export des students
class eformat_csv extends eformat_default {
	var $sep = ";";

	// ----------------
	function guillemets($texte){
		return '"'.trim($texte).'"';
	}


	// ----------------
	function purge_sep($texte){
		$cherche= array('"',$this->sep,"\r\n", "\n", "\r");
		$remplace= array("''",",", " ", " ", " ");
		return $this->guillemets(str_replace($cherche, $remplace, $texte));
	}


	var $table_caractere_input='latin1'; // par defaut import latin1
	var $table_caractere_output='latin1'; // par defaut export latin1
	
	// ----------------
	function recode_latin1_vers_utf8($string) {
		return mb_convert_encoding($string, "UTF-8", mb_detect_encoding($string, "UTF-8, ISO-8859-1, ISO-8859-15", true));
	}


	// ----------------
	function recode_utf8_vers_latin1($string) {
		return mb_convert_encoding($string, "ISO-8859-1", mb_detect_encoding($string, "UTF-8, ISO-8859-1, ISO-8859-15", true));
	}
	

	 /**
     * @param 
     * @return string recode latin1
	 * 
     */
    function input_codage_caractere($s){
		if (!isset($this->table_caractere_input) || ($this->table_caractere_input=="")){
			$this->table_caractere_input='latin1';
		}
		
		if ($this->table_caractere_input=='latin1'){
			$s=$this->recode_latin1_vers_utf8($s);
		}
		return $s;
	}
	
	 /**
     * @param 
     * @return string recode utf8
	 * 
     */
    function output_codage_caractere($s){
		if (!isset($this->table_caractere_output) || ($this->table_caractere_output=="")){
			$this->table_caractere_output='latin1';
		}
		
		if ($this->table_caractere_output=='latin1'){
			$s=$this->recode_utf8_vers_latin1($s);
		}
		return $s;
	}



    function provide_export() {
      return true;
    }

	function provide_import() {
        return true;
    }

	function repchar( $text ) {
	    // escapes 'reserved' characters # = ~ { ) and removes new lines
    	$reserved = array( '#','=','~','{','}',"\n","\r" );
	    $escaped = array( '\#','\=','\~','\{','\}',' ','' );
		return str_replace( $reserved, $escaped, $text ); 
	}

	function presave_process( $content ) {
	  // override method to allow us to add xhtml headers and footers
  		global $CFG;
		$xp =  "#Moodle Referentiel Students CSV Export;latin1;".referentiel_timestamp_date_special(time())."\n";
		$xp .= $content;
  		return $xp;
	}

	function export_file_extension() {
  		return ".csv";
	}

    /**
     * Include an image encoded in base 64
     * @param string imagepath The location of the image file
     * @return string xml code segment 
     */
    function writeimage( $imagepath ) {
        global $CFG;
   		
        if (empty($imagepath)) {
            return '';
        }

        $courseid = $this->course->id;
        if (!$binary = file_get_contents( "{$CFG->dataroot}/$courseid/$imagepath" )) {
            return '';
        }

        $content = "    <image_base64>\n".addslashes(base64_encode( $binary ))."\n".
            "\n    </image_base64>\n";
        return $content;
    }

	
	/**
     * generates <text></text> tags, processing raw text therein 
     * @param int ilev the current indent level
     * @param boolean short stick it on one line
     * @return string formatted text
     */

    function write_ligne( $raw, $sep="/", $nmaxcar=80) {
        // insere un saut de ligne apres le 80 caracter 
		$nbcar=strlen($raw);
		if ($nbcar>$nmaxcar){
			$s1=substr( $raw,0,$nmaxcar);
			$pos1=strrpos($s1,$sep);
			if ($pos1>0){
				$s1=substr( $raw,0,$pos1);
				$s2=substr( $raw,$pos1+1);
			}
			else {
				$s1=substr( $raw,0,$nmaxcar);
				$s2=substr( $raw,$nmaxcar);
			}
		    return $s1." ".$s2;
		}
		else{
			return $raw;
		}
    }


	function write_etablissement($record) {
        // initial string;
        $expout = "";
        // add comment
        // $expout .= "\netablissement: $record->id\n";
		if ($record){
			//$expout .= "#id_etablissement;idnumber;name;address\n";
			$id = trim( $record->id );
			$idnumber = trim( $record->idnumber);
			$name = $this->output_codage_caractere($this->purge_sep($record->name));
			$address = $this->output_codage_caractere($this->purge_sep($record->address));
			$logo = trim( $record->logo);
			
			$expout .= "$id;$idnumber;$name;$address\n";
        }
        return $expout;
    }


	function write_student( $record ) {
        // initial string;
        $expout = "";
        // add comment
        // $expout .= "\nstudent: $record->id  -->\n";
		if ($record){
			$id = trim( $record->id );
			$userid = trim( $record->userid );
            $ref_etablissement = trim( $record->ref_etablissement);
			$num_student = trim( $record->num_student);
			$ddn_student = trim( $record->ddn_student);
			$lieu_naissance = $this->output_codage_caractere($this->purge_sep($record->lieu_naissance));
			$departement_naissance = $this->output_codage_caractere($this->purge_sep($record->departement_naissance));
			$adresse_student = $this->output_codage_caractere($this->purge_sep($record->adresse_student));
    		$expout .= "$id;$userid;".$this->output_codage_caractere(referentiel_get_user_login($record->userid)).";".$this->output_codage_caractere(referentiel_get_user_prenom($record->userid)).";".$this->output_codage_caractere(referentiel_get_user_nom($record->userid)).";$num_student;$ddn_student;$lieu_naissance;$departement_naissance;$adresse_student;$ref_etablissement\n"; 
/*
			// Etablissement
			$record_etablissement=referentiel_get_etablissement($record->ref_etablissement);
	    	if ($record_etablissement){
				$expout .= $this->write_etablissement( $record_etablissement );
			}
*/
        }
        return $expout;
    }

	 /**
     * Turns referentiel instance into an xml segment
     * @param referentiel instanceobject
     * @return string xml segment
     */

    function write_liste_students() {
    	global $CFG;
        // initial string;
        $expout = ""; 

		if ($this->ireferentiel){
			$id = $this->ireferentiel->id;
      $name = $this->output_codage_caractere(trim($this->ireferentiel->name));
      $description = $this->output_codage_caractere($this->purge_sep($this->ireferentiel->description));
      $domainlabel = $this->output_codage_caractere(trim($this->ireferentiel->domainlabel));
      $skilllabel = $this->output_codage_caractere(trim($this->ireferentiel->skilllabel));
      $itemlabel = $this->output_codage_caractere(trim($this->ireferentiel->itemlabel));
      $timecreated = $this->ireferentiel->timecreated;
      $course = $this->ireferentiel->course;
      $referentielid = $this->ireferentiel->referentielid;
			$visible = $this->ireferentiel->visible;
			
//			$expout .= "Instance de referentiel : $this->ireferentiel->name\n";
//			$expout .= "id;name;description;domainlabel;skilllabel;itemlabel;timecreated;course;referentielid;visible\n";
//			$expout .= "$id;$name;$description;$domainlabel;$skilllabel;$itemlabel;$timecreated;$course;$referentielid;$visible\n";
			
			if (isset($this->ireferentiel->course) && ($this->ireferentiel->course>0)){
				// studentS
				$records_all_students = referentiel_get_students_course($this->ireferentiel->course);
				if ($records_all_students){
				    $expout .= "#id_student;user_id;login;Prenom;NOM;num_student;ddn_student;lieu_naissance;departement_naissance;adresse_student;ref_etablissement\n"; 
				    foreach ($records_all_students as $record){
						  // USER
						  if (isset($record->userid) && ($record->userid>0)){
							  $record_student = referentiel_get_student_user($record->userid);
		    				if ($record_student){
								  $expout .= $this->write_student( $record_student );
							  }
						  }
					  }
				}
			}
        }
        return $expout;
    }

    function write_liste_etablissements() {
    	global $CFG;
        // initial string;
        $expout = ""; 
		// ETABLISSEMENTS
		$records_all_etablissements = referentiel_get_etablissements();
		if ($records_all_etablissements){
			$expout .= "#id_etablissement;idnumber;name;address\n";		
			foreach ($records_all_etablissements as $record){
				if ($record){
					$expout.=$this->write_etablissement($record);
				}
			}
        }
        return $expout;
    }

	// IMPORTATION
	/***************************************************************************
		
	// IMPORT FUNCTIONS START HERE
	
	***************************************************************************/
     /**
	 * @param array referentiel array from xml tree
     * @return object import_referentiel object
	 * modifie la base de donnees 
     */
    function importation_referentiel_possible(){
	// selon les parametres soit cree une nouvelle instance 
	// soit modifie une instance courante de referentiel
	global $CFG;
		
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
		else if (isset($this->action) && ($this->action=="selectreferentiel")){
			if (!(isset($this->course->id) && ($this->course->id>0))){
				$this->error( get_string( 'incompletedata', 'referentiel' ) );
				return false;
			}
		}
		else if (isset($this->action) && ($this->action=="importreferentiel")){
			if (!(isset($this->course->id) && ($this->course->id>0))){
				$this->error( get_string( 'incompletedata', 'referentiel' ) );
				return false;
			}
		}
		return true;
	}
	
	
     /**
	 * @param array referentiel array from xml tree
     * @return object import_referentiel object
	 * modifie la base de donnees 
     */
    function import_etablissements_students( $lines ) {
	// recupere le tableau de lignes 
	// selon les parametres soit cree une nouvelle instance 
	// soit modifie une instance courante de students
	global $SESSION;
	global $USER;
	global $CFG;
	
	// initialiser les variables	
	$timecreated="";
	$in_etablissement=false; // drapeau
	$in_student=false;		// drapeau
		
    // get some error strings
    $error_noname = get_string( 'xmlimportnoname', 'referentiel' );
    $error_nocode = get_string( 'xmlimportnocode', 'referentiel' );
	$error_override = get_string( 'overriderisk', 'referentiel' );
	
	// DEBUT	
	// Decodage 
	$line = 0;
	// TRAITER LA LIGNE D'ENTETE
	$nbl=count($lines);
	if ($nbl>0){ // premiere ligne entete fichier csv
		// echo "<br>$line : ".$lines[$line]."\n";
		//"#Moodle Referentiel Students CSV Export;latin1;Y:2009m:06d:11\n"
		
        $fields = explode($this->sep, str_replace( "\r", "", $lines[$line] ) );
	  	$line++;
		if (substr($lines[$line],0,1)=='#'){
			// labels			
	        /// If a line is incorrectly formatted 
            if (count($fields) < 3 ) {
	           	if ( count($fields) > 1 or strlen($fields[0]) > 1) { // no error for blank lines
					$this->error("ERROR ".$lines[$line].": Line ".$line."incorrectly formatted - ignoring\n");
				}
           	}
			if (isset($fields[1]) && ($fields[1]!="")){
		        $this->table_caractere_input=trim($fields[1]);
			}
			$timecreated=trim($fields[2]);
		}
	}
	else{
		$this->error("ERROR : CSV File incorrect\n");
	}
	// echo "<br />DEBUG :: 2073 : $this->table_caractere_input\n";
	
	if ($nbl>1){ // deuxieme ligne : entete etablissment
		// echo "<br>$line : ".$lines[$line]."\n";
		while ($line<$nbl){ // data : referentiel		
			// #id_etablissement;idnumber;name;address
			// 
        	$fields = explode($this->sep, str_replace( "\r", "", $lines[$line] ) );
        	/// If a line is incorrectly formatted 
        	if (count($fields) < 3 ) {
           		if ( count($fields) > 1 or strlen($fields[0]) > 1) { // no error for blank lines
					$this->error("ERROR ".$lines[$line].": Line ".$line."incorrectly formatted");
    			}
			}
			else{
				if (substr($lines[$line],0,1)=='#'){
					// labels
    		    	$l_id = trim($fields[0]);
					if ($l_id=="#id_etablissement"){
						$l_id_etablissement = trim($fields[0]);
						$l_num_etablissement = trim($fields[1]);
		        		$l_nom_etablissement = trim($fields[2]);
						$l_adresse_etablissement = trim($fields[3]);
						if (isset($fields[4]))
							$l_logo_etablissement = trim($fields[4]);
						else
							$l_logo_etablissement = "";
						$in_etablissement=true;
						$in_student=false;
					}
					else if ($l_id=="#id_student"){
						// #id_student;user_id;login;NOM_Prenom;num_student;ddn_student;lieu_naissance;departement_naissance;adresse_student;ref_etablissement
						$l_id_student = trim($fields[0]);
						$l_user_id = trim($fields[1]);
						$l_login = trim($fields[2]);
						$l_Prenom = trim($fields[3]);
				        $l_NOM = trim($fields[4]);
						$l_num_student = trim($fields[5]);
						$l_ddn_student = trim($fields[6]);
						$l_lieu_naissance = trim($fields[7]);
						$l_departement_naissance = trim($fields[8]);
						$l_adresse_student = trim($fields[9]);
						$l_ref_etablissement = trim($fields[10]);
						
						$in_etablissement=false;
						$in_student=true;
					}
				}
				else{
					// data  : 
		    		if ($in_etablissement==true){ // etablissement
						$id_etablissement = trim($fields[0]);
						$idnumber = $this->input_codage_caractere(trim($fields[1]));
				        $name = $this->input_codage_caractere(trim($fields[2]));
						$address = $this->input_codage_caractere(trim($fields[3]));
						if (isset($fields[4]))
							$logo = trim($fields[4]);
						else
							$logo = "";
						
						// this routine initialises the import object
				        $import_etablissement = new stdClass();
						$import_etablissement->id=0;
						$import_etablissement->idnumber=$idnumber;
						$import_etablissement->name=str_replace("'", " ",$name);
						$import_etablissement->address=str_replace("'", " ",$address);
						$import_etablissement->logo=$logo;
						// sauvegarde dans la base
						if ($id_etablissement==0){
							$new_etablissement_id=insert_record("referentiel_institution", $import_etablissement);
						}
						else{
							$import_etablissement->id=$id_etablissement;
							if (!update_record("referentiel_institution", $import_etablissement)){
								// DEBUG
								// echo "<br /> ERREUR UPDATE etablissement\n";
							}
						}
					}
					elseif ($in_student==true){ // student
						$id_student = trim($fields[0]);
						$user_id = $this->input_codage_caractere(trim($fields[1]));
						$login = $this->input_codage_caractere(trim($fields[2]));
			        	$Prenom = $this->input_codage_caractere(trim($fields[3]));
						$NOM = $this->input_codage_caractere(trim($fields[4]));
						$num_student = $this->input_codage_caractere(trim($fields[5]));
						$ddn_student = trim($fields[6]);
						$lieu_naissance = $this->input_codage_caractere(trim($fields[7]));
						$departement_naissance = $this->input_codage_caractere(trim($fields[8]));
						$adresse_student = $this->input_codage_caractere(trim($fields[9]));
						$ref_etablissement = trim($fields[10]);
						// rechercher l'id 
						if (($id_student=='') || ($id_student==0)){
							if (($user_id!='') && ($user_id>0)){
								// rechercher l'id s'il existe
								$id_student=referentiel_get_student_id_by_userid($user_id);
							}
							else if ($login!=''){
								$id_student=referentiel_get_student_id_by_userid(referentiel_get_userid_by_login($login));
							}
						}
						// this routine initialises the import object
				        $import_student = new stdClass();
						$import_student->id=0;
						$import_student->num_student=$num_student;
						$import_student->adresse_student=str_replace("'", " ",$adresse_student);
						$import_student->ddn_student = $ddn_student ;
						$import_student->lieu_naissance =$lieu_naissance;
						$import_student->departement_naissance = $departement_naissance;
						$import_student->ref_etablissement = $ref_etablissement;
						$import_student->userid = $user_id;
						
						// sauvegarde dans la base
						if ($id_student==0){
							$new_student_id=insert_record("referentiel_student", $import_student);
						}
						else{
							$import_student->id=$id_student;
							if (!update_record("referentiel_student", $import_student)){
								// DEBUG
								// echo "<br /> ERREUR UPDATE student\n";
							}
						}
					}
				}
			}
			$line++;
		}
	}
	return true;
}


    /**
     * parse the array of lines into an array 
     * this *could* burn memory - but it won't happen that much
     * so fingers crossed!
     * @param array lines array of lines from the input file
     * @return array of student object
     */
	function read_import_students($lines) {
        // we just need it as one array
		return $this->import_etablissements_students($lines);
    }

}

/** ******************************************

EXPORT TASKS

*/

// TASKS : export des taches
class tformat_csv extends tformat_default {

	var $sep = ";";
	
	var $table_caractere_input='latin1'; // par defaut import latin1
	var $table_caractere_output='latin1'; // par defaut export latin1

	// ----------------
	function guillemets($texte){
		return '"'.trim($texte).'"';
	}


	// ----------------
	function purge_sep($texte){
		$cherche= array('"',$this->sep,"\r\n", "\n", "\r");
		$remplace= array("''",",", " ", " ", " ");
		return $this->guillemets(str_replace($cherche, $remplace, $texte));
	}

	
	// ----------------
	function recode_latin1_vers_utf8($string) {
		return mb_convert_encoding($string, "UTF-8", mb_detect_encoding($string, "UTF-8, ISO-8859-1, ISO-8859-15", true));
	}


	// ----------------
	function recode_utf8_vers_latin1($string) {
		return mb_convert_encoding($string, "ISO-8859-1", mb_detect_encoding($string, "UTF-8, ISO-8859-1, ISO-8859-15", true));
	}
	

	 /**
     * @param 
     * @return string recode latin1
	 * 
     */
    function input_codage_caractere($s){
		if (!isset($this->table_caractere_input) || ($this->table_caractere_input=="")){
			$this->table_caractere_input='latin1';
		}
		
		if ($this->table_caractere_input=='latin1'){
			$s=$this->recode_latin1_vers_utf8($s);
		}
		return $s;
	}
	
	 /**
     * @param 
     * @return string recode utf8
	 * 
     */
    function output_codage_caractere($s){
		if (!isset($this->table_caractere_output) || ($this->table_caractere_output=="")){
			$this->table_caractere_output='latin1';
		}
		
		if ($this->table_caractere_output=='latin1'){
			$s=$this->recode_utf8_vers_latin1($s);
		}
		return $s;
	}



    function provide_export() {
      return true;
    }

	function provide_import() {
        return false;
    }

	function repchar( $text ) {
	    // escapes 'reserved' characters # = ~ { ) and removes new lines
    	$reserved = array( '#','=','~','{','}',"\n","\r" );
	    $escaped = array( '\#','\=','\~','\{','\}',' ','' );
		return str_replace( $reserved, $escaped, $text ); 
	}

	function presave_process( $content ) {
	  // override method to allow us to add xhtml headers and footers

  		global $CFG;
		$xp =  "Moodle Referentiel CSV Export\n";
		$xp .= $content;
  		return $xp;
	}

	function export_file_extension() {
  		return ".csv";
	}

    /**
     * Include an image encoded in base 64
     * @param string imagepath The location of the image file
     * @return string xml code segment 
     */
    function writeimage( $imagepath ) {
        global $CFG;
   		
        if (empty($imagepath)) {
            return '';
        }

        $courseid = $this->course->id;
        if (!$binary = file_get_contents( "{$CFG->dataroot}/$courseid/$imagepath" )) {
            return '';
        }

        $content = "    <image_base64>\n".addslashes(base64_encode( $binary ))."\n".
            "\n    </image_base64>\n";
        return $content;
    }

	
	/**
     * generates <text></text> tags, processing raw text therein 
     * @param int ilev the current indent level
     * @param boolean short stick it on one line
     * @return string formatted text
     */

    function write_ligne( $raw, $sep="/", $nmaxcar=80) {
        // insere un saut de ligne apres le 80 caractere 
		$nbcar=strlen($raw);
		if ($nbcar>$nmaxcar){
			$s1=substr( $raw,0,$nmaxcar);
			$pos1=strrpos($s1,$sep);
			if ($pos1>0){
				$s1=substr( $raw,0,$pos1);
				$s2=substr( $raw,$pos1+1);
			}
			else {
				$s1=substr( $raw,0,$nmaxcar);
				$s2=substr( $raw,$nmaxcar);
			}
		    return $s1." ".$s2;
		}
		else{
			return $raw;
		}
    }

	 /**
     * Turns consigne into an xml segment
     * @param consigne object
     * @return string xml segment
     */

    function write_consigne( $consigne ) {
    global $CFG;
       // initial string;
        $expout = "";
		if ($consigne){
			$id_consigne = $consigne->id ;		
            $type = trim($consigne->type);
            $description = $this->purge_sep($consigne->description);
			$url = $consigne->url;
            $taskid = $consigne->taskid;
            $expout .= "$id_consigne;".stripslashes($this->output_codage_caractere($type)).";".stripslashes($this->output_codage_caractere($description)).";$url;$taskid\n";   
        }
        return $expout;
    }

    /**
     * Turns task into an csv segment
     * @param task object
     * @return string csv segment
     */

    function write_task( $task ) {
    global $CFG;
       // initial string;
        $expout = "";
        // add comment
		if ($task){
			// DEBUG
			// echo "<br />\n";
			// print_r($task);
			$id_task = $task->id;
            $type = trim($task->type);
			$description = $this->purge_sep($task->description);
            $competences_task = trim($task->competences_task);
            $criteres_evaluation = $this->purge_sep($task->criteres_evaluation);
            $instanceid = $task->instanceid;
            $referentielid = $task->referentielid;
            $course = $task->course;
			$auteurid = trim($task->auteurid);
			$timecreated = $task->timecreated;
			$timemodified = $task->timemodified;
			$timestart = $task->timestart;
			$timeend = $task->timeend;
			
			
			$expout .= "#id_task;type;description;competences_task;criteres_evaluation;instanceid;referentielid;course;auteurid;timecreated;timemodified;timestart;timeend\n";
			$expout .= "$id_task;".stripslashes($this->output_codage_caractere($type)).";".stripslashes($this->output_codage_caractere($description)).";".stripslashes($this->output_codage_caractere($competences_task)).";".stripslashes($this->output_codage_caractere($criteres_evaluation)).";$instanceid;$referentielid;$course;$auteurid;".referentiel_timestamp_date_special($timecreated).";".referentiel_timestamp_date_special($timemodified).";".referentiel_timestamp_date_special($timestart).";".referentiel_timestamp_date_special($timeend)."\n";
			
			// consigneS
			$records_consignes = referentiel_get_consignes($task->id);
			
			if ($records_consignes){
				$expout .= "#id_consigne;type;description;url;taskid\n";
				foreach ($records_consignes as $record_d){
					$expout .= $this->write_consigne( $record_d );
				}
			}
		}	
        return $expout;
    }

		 /**
     * Turns referentiel into an xml segment
     * @param competence object
     * @return string xml segment
     */

    function write_referentiel_reduit() {
    	global $CFG;		
        // initial string;
        $expout = "";
    	// add header
		if ($this->rreferentiel){
            $name = $this->rreferentiel->name;
            $code = $this->rreferentiel->code;
			$referentielauthormail = $this->rreferentiel->referentielauthormail;
			$cle_referentiel = $this->rreferentiel->cle_referentiel;
			$password = $this->rreferentiel->password;
            $description = $this->rreferentiel->description;
            $url = $this->rreferentiel->url;
			$certificatethreshold = $this->rreferentiel->certificatethreshold;
			$timemodified = $this->rreferentiel->timemodified;			
			$nb_domaines = $this->rreferentiel->nb_domaines;
			$liste_codes_competence = $this->rreferentiel->liste_codes_competence;
			$liste_empreintes_competence = $this->rreferentiel->liste_empreintes_competence;
			$local = $this->rreferentiel->local;
			$logo = $this->rreferentiel->logo;

			// INFORMATION REDUITE
			$expout .= "#code;nom_referentiel;description;cle_referentiel;liste_codes_competences\n";
			$expout .= stripslashes($this->output_codage_caractere($code)).";".stripslashes($this->output_codage_caractere($name)).";".$this->output_codage_caractere($description).";$cle_referentiel;".stripslashes($this->output_codage_caractere($liste_codes_competence))."\n";			
        }
        return $expout;
    }
	

	
	 /**
     * Turns referentiel instance into an xml segment
     * @param referentiel instanceobject
     * @return string xml segment
     */

  function write_liste_tasks() {
  	global $CFG;
        // initial string;
        $expout = "";
		if ($this->rreferentiel){
			$expout .= $this->write_referentiel_reduit();
		}

		// 
		if ($this->ireferentiel){
			$id = $this->ireferentiel->id;
      $name = trim($this->ireferentiel->name);
      $description = $this->purge_sep($this->ireferentiel->description);
      $domainlabel = trim($this->ireferentiel->domainlabel);
      $skilllabel = trim($this->ireferentiel->skilllabel);
      $itemlabel = trim($this->ireferentiel->itemlabel);
      $timecreated = $this->ireferentiel->timecreated;
      $course = $this->ireferentiel->course;
      $referentielid = $this->ireferentiel->referentielid;
			$visible = $this->ireferentiel->visible;
			
			/* INUTILE ICI
			// $expout .= "#Instance de referentiel : $this->ireferentiel->name\n";
			$expout .= "#id_instance;name;description;domainlabel;skilllabel;itemlabel;timecreated;course;referentielid;visible\n";
			$expout .= "$id;".stripslashes($this->output_codage_caractere($name)).";".stripslashes($this->output_codage_caractere($description)).";".stripslashes($this->output_codage_caractere($domainlabel)).";".stripslashes($this->output_codage_caractere($skilllabel)).";".stripslashes($this->output_codage_caractere($itemlabel)).";".referentiel_timestamp_date_special($timecreated).";$course;$referentielid;$visible\n";
			*/
			// tasks
			if (isset($this->ireferentiel->id) && ($this->ireferentiel->id>0)){
			  	$records_tasks = referentiel_get_tasks_instance($this->ireferentiel->id);
		    	if ($records_tasks){
					foreach ($records_tasks as $record_a){
						// DEBUG
						// print_r($record_a);
						// echo "<br />\n";
						$expout .= $this->write_task( $record_a );
					}
				}
			}
		}
    return $expout;
  }
  
// fin de la classe  
}

// ##########################################################################################################

?>
