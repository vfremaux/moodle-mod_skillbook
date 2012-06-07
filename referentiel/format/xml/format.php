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
        return true;
    }


    // EXPORT FUNCTIONS START HERE

    function export_file_extension() {
    // override default type so extension is .xml
        return ".xml";
    }


    /**
     * Convert internal Moodle text format code into
     * human readable form
     * @param int id internal code
     * @return string format text
     */
    function get_format( $id ) {
        switch( $id ) {
        case 0:
            $name = "moodle_auto_format";
            break;
        case 1:
            $name = "html";
            break;
        case 2:
            $name = "plain_text";
            break;
        case 3:
            $name = "wiki_like";
            break;
        case 4:
            $name = "markdown";
            break;
        default:
            $name = "unknown";
        }
        return $name;
    }

    /**
     * Convert internal single question code into 
     * human readable form
     * @param int id single question code
     * @return string single question string
     */
    function get_single( $id ) {
        switch( $id ) {
        case 0:
            $name = "false";
            break;
        case 1:
            $name = "true";
            break;
        default:
            $name = "unknown";
        }
        return $name;
    }

    /**
     * generates <text></text> tags, processing raw text therein 
     * @param int ilev the current indent level
     * @param boolean short stick it on one line
     * @return string formatted text
     */

    function writetext( $raw, $ilev=0, $short=true) {
        $indent = str_repeat( "  ",$ilev );

        // encode the text to 'disguise' HTML content 
		$raw=ereg_replace("\r", "", $raw);
		$raw=ereg_replace("\n", "", $raw);
		
        $raw = htmlspecialchars( $raw );

        if ($short) {
            $xml = "$indent<text>$raw</text>\n";
        }
        else {
            $xml = "$indent<text>\n$raw\n$indent</text>\n";
        }

        return $xml;
    }

    /**
     * generates raw text therein 
     * @return string not formatted text
     */

    function writeraw( $raw) {
		$raw=ereg_replace("\r", "", $raw);
		$raw=ereg_replace("\n", "", $raw);
	    return $raw;
    }
  
    function xmltidy( $content ) {
        // can only do this if tidy is installed
        if (extension_loaded('tidy')) {
            $config = array( 'input-xml'=>true, 'output-xml'=>true, 'indent'=>true, 'wrap'=>0 );
            $tidy = new tidy;
            $tidy->parseString($content, $config, 'utf8');
            $tidy->cleanRepair(); 
            return $tidy->value;
        }
        else {
            return $content;
        }
    }


    function presave_process( $content ) {
    // override method to allow us to add xml headers and footers

        // add the xml headers and footers
        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
                       "<referentiel>\n" .
                       $content .
                       "</referentiel>\n\n";

        // make the xml look nice
        $content = $this->xmltidy( $content );

        return $content;
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
     * Turns item into an xml segment
     * @param item object
     * @return string xml segment
     */

    function write_item( $item ) {
    global $CFG;
        // initial string;
        $expout = "";
        // add comment
        // $expout .= "\n\n<!-- item: $item->id  -->\n";
		// 
		if ($item){
			// DEBUG
			// echo "<br />\n";
			// print_r($item);
			$id = $this->writeraw( $item->id );
            $code = $this->writeraw( trim($item->code));
            $description = $this->writetext(trim($item->description));
            $referentielid = $this->writeraw( $item->referentielid);
            $skillid = $this->writeraw( $item->skillid);
			$type = $this->writeraw( trim($item->type));
			$weight = $this->writeraw( $item->weight);
			$footprint = $this->writeraw( $item->footprint);
			$sortorder = $this->writeraw( $item->sortorder);
            $expout .= "   <item>\n";
			// $expout .= "    <id>$id</id>\n";
			$expout .= "    <code>$code</code>\n";
            $expout .= "    <description>\n$description</description>\n";
            // $expout .= "    <referentielid>$referentielid</referentielid>\n";
            // $expout .= "    <skillid>$skillid</skillid>\n";
            $expout .= "    <type>$type</type>\n";
            $expout .= "    <weight>$weight</weight>\n";
            $expout .= "    <footprint>$footprint</footprint>\n";			
            $expout .= "    <sortorder>$sortorder</sortorder>\n";
			$expout .= "   </item>\n\n";
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
        // $expout .= "\n\n<!-- competence: $competence->id  -->\n";
		//
		if ($competence){
			$id = $this->writeraw( $competence->id );		
            $code = $this->writeraw( trim($competence->code));
            $description = $this->writetext(trim($competence->description));
            $domainid = $this->writeraw( $competence->domainid);
			$sortorder = $this->writeraw( $competence->sortorder);
			$nb_item_competences = $this->writeraw( $competence->nb_item_competences);
			
            $expout .= "  <competence>\n";
			// $expout .= "<id>$id</id>\n";
			$expout .= "   <code>$code</code>\n";   
            $expout .= "   <description>\n$description</description>\n";
            // $expout .= "   <domainid>$domainid</domainid>\n";
            $expout .= "   <sortorder>$sortorder</sortorder>\n";
            $expout .= "   <nb_item_competences>$nb_item_competences</nb_item_competences>\n\n";
							
			// ITEM
			$compteur_item=0;
			$records_items = referentiel_get_item_competences($competence->id);
			
			if ($records_items){
				// DEBUG
				// echo "<br/>DEBUG :: ITEMS <br />\n";
				// print_r($records_items);
				foreach ($records_items as $record_i){
					// DEBUG
					// echo "<br/>DEBUG :: ITEM <br />\n";
					// print_r($record_i);
					$expout .= $this->write_item( $record_i );
				}
			}
			$expout .= "  </competence>\n\n";   
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
        // $expout .= "\n\n<!-- domaine: $domaine->id  -->\n";
		// 
		if ($domaine){
			$id = $this->writeraw( $domaine->id );
            $code = $this->writeraw( trim($domaine->code) );
            $description = $this->writetext(trim($domaine->description));
            $referentielid = $this->writeraw( $domaine->referentielid );
			$sortorder = $this->writeraw( $domaine->sortorder );
			$nb_competences = $this->writeraw( $domaine->nb_competences );
			
            $expout .= " <domaine>\n";
			// $expout .= "  <id>$id</id>\n";
			$expout .= "  <code>$code</code>\n";   
            $expout .= "  <description>\n$description</description>\n";
            // $expout .= " <referentielid>$referentielid</referentielid>\n";
            $expout .= "  <sortorder>$sortorder</sortorder>\n";
            $expout .= "  <nb_competences>$nb_competences</nb_competences>\n\n";
			
			// LISTE DES COMPETENCES DE CE DOMAINE
			$compteur_competence=0;
			$records_competences = referentiel_get_competences($domaine->id);
			if ($records_competences){
				// DEBUG
				// echo "<br/>DEBUG :: COMPETENCES <br />\n";
				// print_r($records_competences);
				foreach ($records_competences as $record_c){
					$expout .= $this->write_competence( $record_c );
				}
			}
			$expout .= " </domaine>\n\n";   
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
        // add comment		
		//         $rreferentiel
		if ($this->rreferentiel){
			$id = $this->writeraw( $this->rreferentiel->id );
            $name = $this->writeraw( trim($this->rreferentiel->name) );
            $code = $this->writeraw( trim($this->rreferentiel->code));
            $description = $this->writetext(trim($this->rreferentiel->description));
            $url = $this->writeraw( trim($this->rreferentiel->url) );
			$certificatethreshold = $this->writeraw( $this->rreferentiel->certificatethreshold );
			$timemodified = $this->writeraw( $this->rreferentiel->timemodified );
			$nb_domaines = $this->writeraw( $this->rreferentiel->nb_domaines );
			$liste_codes_competence = $this->writeraw( trim($this->rreferentiel->liste_codes_competence) );
			$liste_empreintes_competence = $this->writeraw( trim($this->rreferentiel->liste_empreintes_competence) );
			$local = $this->writeraw( $this->rreferentiel->local );
			$logo = $this->writeraw( $this->rreferentiel->logo );
			
			// $expout .= "<id>$id</id>\n";
			$expout .= " <name>$name</name>\n";   
			$expout .= " <code>$code</code>\n";   
            $expout .= " <description>\n$description</description>\n";
            $expout .= " <url>$url</url>\n";
            $expout .= " <certificatethreshold>$certificatethreshold</certificatethreshold>\n";
            $expout .= " <timemodified>$timemodified</timemodified>\n";			
            $expout .= " <nb_domaines>$nb_domaines</nb_domaines>\n";
            $expout .= " <liste_codes_competence>$liste_codes_competence</liste_codes_competence>\n";
            $expout .= " <liste_empreintes_competence>$liste_empreintes_competence</liste_empreintes_competence>\n";
			// $expout .= " <local>$local</local>\n";
			// PAS DE LOGO ICI
			// $expout .= " <logo>$logo</logo>\n";
			
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
						$expout .= $this->write_domaine( $record_d );
					}
				}
			}
        }
        return $expout;
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

		// print_r($xmlreferentiel);
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
        $re->code = $this->getpath( $xmlreferentiel, array('#','code',0,'#'), '', true, $error_nocode);
        $re->description = $this->getpath( $xmlreferentiel, array('#','description',0,'#','text',0,'#'), '', true, '');
        $re->url = $this->getpath( $xmlreferentiel, array('#','url',0,'#'), '', true, '');		
		$re->certificatethreshold = $this->getpath( $xmlreferentiel, array('#','certificatethreshold',0,'#'), '', false, '');
		$re->timemodified = $this->getpath( $xmlreferentiel, array('#','timemodified',0,'#'), '', false, '');		
		$re->nb_domaines = $this->getpath( $xmlreferentiel, array('#','nb_domaines',0,'#'), '', false, '');				
		$re->liste_codes_competence = $this->getpath( $xmlreferentiel, array('#','liste_codes_competence',0,'#'), '', true, '');
		$re->liste_empreintes_competence = $this->getpath( $xmlreferentiel, array('#','liste_empreintes_competence',0,'#'), '', true, '');
		$re->logo = $this->getpath( $xmlreferentiel, array('#','logo',0,'#'), '', true, '');
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
		$xmldomaines = $xmlreferentiel['#']['domaine'];
		$dindex=0;
        $re->domaines = array();
		
		$nbdomaines=0;        // compteur
        foreach ($xmldomaines as $domaine) {
			// DOMAINES
			// print_r($domaine);
			$dindex++;
			$new_domaine = array();
			$new_domaine = $this->defaultdomaine();
			// $new_domaine->id=$this->getpath( $domaine, array('#','id',0,'#'), '', false, '');	
			$new_domaine->code=$this->getpath( $domaine, array('#','code',0,'#'), '', true, $error_nocode);
			$new_domaine->description=$this->getpath( $domaine, array('#','description',0,'#','text',0,'#'), '', true, '');
			$new_domaine->sortorder=$this->getpath( $domaine, array('#','sortorder',0,'#'), '', false, '');
			$new_domaine->nb_competences=$this->getpath( $domaine, array('#','nb_competences',0,'#'), '', false, '');
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
			$new_domaine->new_nb_competences=$new_domaine->sortorder;
			
			$new_domaine_id=referentiel_add_domaine($new_domaine);
			if ($new_domaine_id){
                $nbdomaines++;
            }

			// importer les competences
			$xmlcompetences = $domaine['#']['competence'];
			
			$cindex=0;
			$re->domaines[$dindex]->competences=array();
			
			$nbcompetences=0;        // compteur
            foreach ($xmlcompetences as $competence) {
				$cindex++;
				$new_competence = array();
				$new_competence = $this->defaultcompetence();
		    	// $new_competence->id = $this->getpath( $competence, array('#','id',0,'#'), '', false, '');
				$new_competence->code=$this->getpath( $competence, array('#','code',0,'#'), '', true, $error_nocode);
				$new_competence->description=$this->getpath( $competence, array('#','description',0,'#','text',0,'#'), '', true, '');
				$new_competence->sortorder=$this->getpath( $competence, array('#','sortorder',0,'#'), '', false, '');
				$new_competence->nb_item_competences=$this->getpath( $competence, array('#','nb_item_competences',0,'#'), '', false, '');
				// $new_competence->domainid=$this->getpath( $competence, array('#','domainid',0,'#'), '', false, '');
				
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

                $nbitems=0; // compteur
		        foreach ($xmlitems as $item) {
					$iindex++;
					$new_item = array();
					$new_item = $this->defaultitem();
					// $new_item->id = $this->getpath( $item, array('#','id',0,'#'), '', false, '');
					$new_item->code = $this->getpath( $item, array('#','code',0,'#'), '', true, $error_nocode);
					$new_item->description=$this->getpath( $item, array('#','description',0,'#','text',0,'#'), '', true, '');
					$new_item->sortorder=$this->getpath( $item, array('#','sortorder',0,'#'), '', false, '');
					$new_item->type=$this->getpath( $item, array('#','type',0,'#'), '', true, '');
					$new_item->weight=$this->getpath( $item, array('#','weight',0,'#'), '', false, '');
					// $new_item->skillid=$this->getpath( $item, array('#','skillid',0,'#'), '', false, '');
					// $new_item->referentielid=$this->getpath( $item, array('#','referentielid',0,'#'), '', false, '');
					$new_item->footprint=$this->getpath( $item, array('#','footprint',0,'#'), '', false, '');					
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
        // mettre a jour
        if ($nbdomaines>0){
            // mettre a jour
            referentiel_set_referentiel_nb_domaine($new_referentiel_id, $nbdomaines);
        }
        return $re;
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
		// echo "<br />DEBUG xml/format.php :: ligne 580<br />\n";
		// print_r($xml);
		// echo "<br /><br />\n";		
		// print_r($xml['referentiel']['domaine']['competence']);
		// print_r($xml['referentiel']['#']['domaine']['#']);
		// echo "<br /><br />\n";		
		// exit;
		$re=$this->import_referentiel($xml['referentiel']);
        // stick the result in the $treferentiel array
 		// DEBUG
		// echo "<br />DEBUG xml/format.php :: ligne 632\n";
		// print_r($re);
        return $re;
    }
}
 
 
/**********************************************************************
***********************************************************************
									ACTIVITES
***********************************************************************
**********************************************************************/

class aformat_xml extends aformat_default {

    function provide_import() {
        return false;
    }

    function provide_export() {
        return true;
    }


    // EXPORT FUNCTIONS START HERE

    function export_file_extension() {
    // override default type so extension is .xml
        return ".xml";
    }


    /**
     * Convert internal Moodle text format code into
     * human readable form
     * @param int id internal code
     * @return string format text
     */
    function get_format( $id ) {
        switch( $id ) {
        case 0:
            $name = "moodle_auto_format";
            break;
        case 1:
            $name = "html";
            break;
        case 2:
            $name = "plain_text";
            break;
        case 3:
            $name = "wiki_like";
            break;
        case 4:
            $name = "markdown";
            break;
        default:
            $name = "unknown";
        }
        return $name;
    }

    /**
     * Convert internal single question code into 
     * human readable form
     * @param int id single question code
     * @return string single question string
     */
    function get_single( $id ) {
        switch( $id ) {
        case 0:
            $name = "false";
            break;
        case 1:
            $name = "true";
            break;
        default:
            $name = "unknown";
        }
        return $name;
    }

    /**
     * generates <text></text> tags, processing raw text therein 
     * @param int ilev the current indent level
     * @param boolean short stick it on one line
     * @return string formatted text
     */

    function writetext( $raw, $ilev=0, $short=true) {
        $indent = str_repeat( "  ",$ilev );

        // encode the text to 'disguise' HTML content 
		$raw=ereg_replace("\r", "", $raw);
		$raw=ereg_replace("\n", "", $raw);
		
        $raw = htmlspecialchars( $raw );

        if ($short) {
            $xml = "$indent<text>$raw</text>\n";
        }
        else {
            $xml = "$indent<text>\n$raw\n$indent</text>\n";
        }

        return $xml;
    }

    /**
     * generates raw text therein 
     * @return string not formatted text
     */

    function writeraw( $raw) {
		$raw=ereg_replace("\r", "", $raw);
		$raw=ereg_replace("\n", "", $raw);
	    return $raw;
    }
  
    function xmltidy( $content ) {
        // can only do this if tidy is installed
        if (extension_loaded('tidy')) {
            $config = array( 'input-xml'=>true, 'output-xml'=>true, 'indent'=>true, 'wrap'=>0 );
            $tidy = new tidy;
            $tidy->parseString($content, $config, 'utf8');
            $tidy->cleanRepair(); 
            return $tidy->value;
        }
        else {
            return $content;
        }
    }


    function presave_process( $content ) {
    // override method to allow us to add xml headers and footers

        // add the xml headers and footers
        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
                       "<certification>\n" .
                       $content . "\n" .
                       "</certification>";

        // make the xml look nice
        $content = $this->xmltidy( $content );

        return $content;
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
     * Turns document into an xml segment
     * @param document object
     * @return string xml segment
     */

    function write_document( $document ) {
    global $CFG;
        // initial string;
        $expout = "";
        // add comment		
        $expout .= "\n\n<!-- document: $document->id  -->\n";
		//
		if ($document){
			$id = $this->writeraw( $document->id );		
            $type = $this->writeraw( trim($document->type));
            $description = $this->writetext(trim($document->description));
			$url = $this->writeraw( $document->url);
            $activityid = $this->writeraw( $document->activityid);

            $expout .= "<document>\n";
			$expout .= "<id>$id</id>\n";
			$expout .= "<type>$type</type>\n";   
            $expout .= "<description>\n$description</description>\n";
            $expout .= "<url>$url</url>\n";
            $expout .= "<activityid>$activityid</activityid>\n";
			$expout .= "</document>\n";   
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
        $expout .= "\n\n<!-- activite: $activite->id  -->\n";
		// 
		if ($activite){
			// DEBUG
			// echo "<br />DEBUG LIGNE 960<br />\n";
			// print_r($activite);
			
			$id = $this->writeraw( $activite->id );
            $type_activite = $this->writeraw( trim($activite->type_activite));
            $description = $this->writetext(trim($activite->description));
            $comptencies = $this->writeraw(trim($activite->comptencies));
            $comment = $this->writetext(trim($activite->comment));
            $instanceid = $this->writeraw( $activite->instanceid);
            $referentielid = $this->writeraw( $activite->referentielid);
            $course = $this->writeraw( $activite->course);
			$userid = $this->writeraw( trim($activite->userid));
			$teacherid = $this->writeraw( $activite->teacherid);
			$timecreated = $this->writeraw( $activite->timecreated);
			$timemodified = $this->writeraw( $activite->timemodified);
			$approved = $this->writeraw( $activite->approved);
			
            $expout .= "<activite>\n";
			$expout .= "<id>$id</id>\n";
			$expout .= "<type_activite>$type_activite</type_activite>\n";
            $expout .= "<description>\n$description</description>\n";
            $expout .= "<comptencies>$comptencies</comptencies>\n";
            $expout .= "<comment>\n$comment</comment>\n";
            $expout .= "<instanceid>$instanceid</instanceid>\n";
            $expout .= "<referentielid>$referentielid</referentielid>\n";
            $expout .= "<course>$course</course>\n";
            $expout .= "<userid>$userid</userid>\n";
            $expout .= "<teacherid>$teacherid</teacherid>\n";
            $expout .= "<timecreated>$timecreated</timecreated>\n";
            $expout .= "<timemodified>$timemodified</timemodified>\n";
            $expout .= "<approved>$approved</approved>\n";

			// DOCUMENTS
			$records_documents = referentiel_get_documents($activite->id);
			
			if ($records_documents){
				foreach ($records_documents as $record_d){
					$expout .= $this->write_document( $record_d );
				}
			}
			
			$expout .= "</activite>\n";
        }
        return $expout;
    }


    function write_liste_activites() {
    	global $CFG;
        // initial string;
        $expout = "";
        // add comment		
        $expout .= "\n\n<!-- instance : ".$this->ireferentiel->id."  -->\n";
		// 
		if ($this->ireferentiel){
			// DEBUG
			// echo "<br />DEBUG LIGNE 1021<br />\n";
			// print_r($this->ireferentiel);
			
			$id = $this->writeraw( $this->ireferentiel->id );
            $name = $this->writeraw( trim($this->ireferentiel->name) );
            $description = $this->writetext(trim($this->ireferentiel->description));
            $domainlabel = $this->writeraw( trim($this->ireferentiel->domainlabel) );
            $skilllabel = $this->writeraw( trim($this->ireferentiel->skilllabel) );
            $itemlabel = $this->writeraw( trim($this->ireferentiel->itemlabel) );
            $timecreated = $this->writeraw( $this->ireferentiel->timecreated);
            $course = $this->writeraw( $this->ireferentiel->course);
            $referentielid = $this->writeraw( $this->ireferentiel->referentielid);
			$visible = $this->writeraw( $this->ireferentiel->visible );
			
			$expout .= "<id>$id</id>\n";
			$expout .= "<name>$name</name>\n";   
            $expout .= "<description>\n$description</description>\n";
            $expout .= "<domainlabel>$domainlabel</domainlabel>\n";
            $expout .= "<skilllabel>$skilllabel</skilllabel>\n";
            $expout .= "<itemlabel>$itemlabel</itemlabel>\n";
            $expout .= "<timecreated>$timecreated</timecreated>\n";
            $expout .= "<course>$course</course>\n";
            $expout .= "<referentielid>$referentielid</referentielid>\n";
            $expout .= "<visible>$visible</visible>\n";
			
			// ACTIVITES
			if (isset($this->ireferentiel->id) && ($this->ireferentiel->id>0)){
				$records_activites = referentiel_get_activites_instance($this->ireferentiel->id);
				// print_r($records_activites);
				
		    	if ($records_activites){
					foreach ($records_activites as $record_a){
						$expout .= $this->write_activite( $record_a );
					}
				}
			}
        }
        return $expout;
    }
}


class cformat_xml extends cformat_default {

    function provide_import() {
        return false;
    }

    function provide_export() {
        return true;
    }


    // EXPORT FUNCTIONS START HERE

    function export_file_extension() {
    // override default type so extension is .xml
        return ".xml";
    }


    /**
     * Convert internal Moodle text format code into
     * human readable form
     * @param int id internal code
     * @return string format text
     */
    function get_format( $id ) {
        switch( $id ) {
        case 0:
            $name = "moodle_auto_format";
            break;
        case 1:
            $name = "html";
            break;
        case 2:
            $name = "plain_text";
            break;
        case 3:
            $name = "wiki_like";
            break;
        case 4:
            $name = "markdown";
            break;
        default:
            $name = "unknown";
        }
        return $name;
    }

    /**
     * Convert internal single question code into 
     * human readable form
     * @param int id single question code
     * @return string single question string
     */
    function get_single( $id ) {
        switch( $id ) {
        case 0:
            $name = "false";
            break;
        case 1:
            $name = "true";
            break;
        default:
            $name = "unknown";
        }
        return $name;
    }

    /**
     * generates <text></text> tags, processing raw text therein 
     * @param int ilev the current indent level
     * @param boolean short stick it on one line
     * @return string formatted text
     */

    function writetext( $raw, $ilev=0, $short=true) {
        $indent = str_repeat( "  ",$ilev );

        // encode the text to 'disguise' HTML content 
		$raw=ereg_replace("\r", "", $raw);
		$raw=ereg_replace("\n", "", $raw);
		
        $raw = htmlspecialchars( $raw );

        if ($short) {
            $xml = "$indent<text>$raw</text>\n";
        }
        else {
            $xml = "$indent<text>\n$raw\n$indent</text>\n";
        }

        return $xml;
    }

    /**
     * generates raw text therein 
     * @return string not formatted text
     */

    function writeraw( $raw) {
		$raw=ereg_replace("\r", "", $raw);
		$raw=ereg_replace("\n", "", $raw);
	    return $raw;
    }
  
    function xmltidy( $content ) {
        // can only do this if tidy is installed
        if (extension_loaded('tidy')) {
            $config = array( 'input-xml'=>true, 'output-xml'=>true, 'indent'=>true, 'wrap'=>0 );
            $tidy = new tidy;
            $tidy->parseString($content, $config, 'utf8');
            $tidy->cleanRepair(); 
            return $tidy->value;
        }
        else {
            return $content;
        }
    }


    function presave_process( $content ) {
    // override method to allow us to add xml headers and footers

        // add the xml headers and footers
        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
                       "<certification>\n" .
                       $content . "\n" .
                       "</certification>";

        // make the xml look nice
        $content = $this->xmltidy( $content );

        return $content;
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

		function write_etablissement( $record ) {
        // initial string;
        $expout = "";
        // add comment
        $expout .= "\n\n<!-- etablissement: $record->id  -->\n";
		if ($record){
			// DEBUG
			// echo "<br />\n";
			// print_r($record);
			$id = $this->writeraw( $record->id );
			$idnumber = $this->writeraw( $record->idnumber);
			$name = $this->writeraw( $record->name);
			$address = $this->writetext( $record->address);
			$logo = $this->writeraw( $record->logo);
						
            $expout .= "<etablissement>\n";
			$expout .= "<id>$id</id>\n";
            $expout .= "<idnumber>$idnumber</$idnumber>\n";
            $expout .= "<name>$name</name>\n";			
            $expout .= "<address>\n$address</address>\n";
            $expout .= "<logo>$logo</logo>\n";			
			$expout .= "</etablissement>\n\n";
        }
        return $expout;
    }


	
	function write_student( $record ) {
        // initial string;
        $expout = "";
        // add comment
        $expout .= "\n\n<!-- student: $record->id  -->\n";
		if ($record){
			// DEBUG
			// echo "<br />\n";
			// print_r($record);
			$id = $this->writeraw( $record->id );
			$userid = $this->writeraw( $record->userid );
            $ref_etablissement = $this->writeraw( $record->ref_etablissement);
			$num_student = $this->writeraw( $record->num_student);
			$ddn_student = $this->writeraw( $record->ddn_student);
			$lieu_naissance = $this->writeraw( $record->lieu_naissance);
			$departement_naissance = $this->writeraw( $record->departement_naissance);
			$adresse_student = $this->writeraw( $record->adresse_student);
            $expout .= "<student>\n";
			$expout .= "<id>$id</id>\n";
			$expout .= "<userid>$userid</userid>\n";
			$expout .= "<lastname_firstname>".referentiel_get_user_info($record->userid)."</lastname_firstname>\n";
			$expout .= "<num_student>$num_student</num_student>\n";
            $expout .= "<ddn_student>$ddn_student</ddn_student>\n";
            $expout .= "<lieu_naissance>$lieu_naissance</lieu_naissance>\n";
            $expout .= "<departement_naissance>$departement_naissance</departement_naissance>\n";			
            $expout .= "<adresse_student>$adresse_student</adresse_student>\n";
			$expout .= "<ref_etablissement>$ref_etablissement</ref_etablissement>\n";
			// Etablissement
			$record_etablissement=referentiel_get_etablissement($record->ref_etablissement);
	    	if ($record_etablissement){
				$expout .= $this->write_etablissement( $record_etablissement );
			}
			$expout .= "</student>\n\n";
        }
        return $expout;
    }

	 /**
     * Turns referentiel instance into an xml segment
     * @param referentiel instanceobject
     * @return string xml segment
     */

    function write_certificat( $record ) {
    	global $CFG;
        // initial string;
        $expout = "";
        // add comment		
        $expout .= "\n\n<!-- certificate : $record->id  -->\n";
		// 
		if ($record){
			// DEBUG
			// echo "<br />DEBUG LIGNE 1298<br />\n";
			// print_r($record);
			
			$id = $this->writeraw( $record->id );
            $comment = $this->writetext(trim($record->comment));
            $competences_certificate = $this->writeraw( trim($record->competences_certificat) );
            $decision_jury = $this->writeraw( trim($record->decision_jury) );
            $date_decision = $this->writeraw( userdate(trim($record->date_decision)) );
            $userid = $this->writeraw( $record->userid);
            $teacherid = $this->writeraw( $record->teacherid);
            $referentielid = $this->writeraw( $record->referentielid);
			$verrou = $this->writeraw( $record->verrou );
			$valide = $this->writeraw( $record->valide );
			$evaluation = $this->writeraw( $record->evaluation );			
			$synthese_certificate = $this->writetext(trim($record->synthese_certificat));
			
			$expout .= "<certificat>\n";
			$expout .= "<id>$id</id>\n";
		// DEBUG
		// echo "<br />DEBUG LIGNE 1314<br />\n";
		// echo htmlentities ($expout, ENT_QUOTES, 'UTF-8')  ;
		
			
			// USER
			if (isset($record->userid) && ($record->userid>0)){
				$record_student = referentiel_get_student_user($record->userid);
		    	if ($record_student){
					$expout .= $this->write_student( $record_student );
				}
			}
		// DEBUG
		// echo "<br />DEBUG LIGNE 1326<br />\n";
		// echo htmlentities ($expout, ENT_QUOTES, 'UTF-8')  ;
            $expout .= "<comment>\n$comment</comment>\n";			
            $expout .= "<competences_certificat>$competences_certificat</competences_certificat>\n";
            $expout .= "<decision_jury>$decision_jury</decision_jury>\n";
            $expout .= "<date_decision>$date_decision</date_decision>\n";			
            $expout .= "<referentielid>$referentielid</referentielid>\n";
            $expout .= "<verrou>$verrou</verrou>\n";
            $expout .= "<valide>$valide</valide>\n";
			$expout .= "<evaluation>$evaluation</evaluation>\n";
            $expout .= "<synthese>\n$synthese_certificat</synthese>\n";
			$expout .= "</certificat>\n\n";
        }
		// DEBUG
		// echo "<br />DEBUG LIGNE 1330<br />\n";
		// echo htmlentities ($expout, ENT_QUOTES, 'UTF-8')  ;
		
        return $expout;
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
        $expout .= "\n\n<!-- item: $item->id  -->\n";
		// 
		if ($item){
			// DEBUG
			// echo "<br />\n";
			// print_r($item);
			$id = $this->writeraw( $item->id );
            $code = $this->writeraw( trim($item->code));
            $description = $this->writetext(trim($item->description));
            $referentielid = $this->writeraw( $item->referentielid);
            $skillid = $this->writeraw( $item->skillid);
			$type = $this->writeraw( trim($item->type));
			$weight = $this->writeraw( $item->weight);
			$sortorder = $this->writeraw( $item->sortorder);
            $expout .= "<item>\n";
			$expout .= "<id>$id</id>\n";
			$expout .= "<code>$code</code>\n";
            $expout .= "<description>\n$description</description>\n";
            $expout .= "<referentielid>$referentielid</referentielid>\n";
            $expout .= "<skillid>$skillid</skillid>\n";
            $expout .= "<type>$type</type>\n";
            $expout .= "<weight>$weight</weight>\n";
            $expout .= "<sortorder>$sortorder</sortorder>\n";
			$expout .= "</item>\n";
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
        $expout .= "\n\n<!-- competence: $competence->id  -->\n";
		//
		if ($competence){
			$id = $this->writeraw( $competence->id );		
            $code = $this->writeraw( trim($competence->code));
            $description = $this->writetext(trim($competence->description));
            $domainid = $this->writeraw( $competence->domainid);
			$sortorder = $this->writeraw( $competence->sortorder);
			$nb_item_competences = $this->writeraw( $competence->nb_item_competences);
            $expout .= "<competence>\n";
			$expout .= "<id>$id</id>\n";
			$expout .= "<code>$code</code>\n";   
            $expout .= "<description>\n$description</description>\n";
            $expout .= "<domainid>$domainid</domainid>\n";
            $expout .= "<sortorder>$sortorder</sortorder>\n";
            $expout .= "<nb_item_competences>$nb_item_competences</nb_item_competences>\n";
							
			// ITEM
			$compteur_item=0;
			$records_items = referentiel_get_item_competences($competence->id);
			
			if ($records_items){
				// DEBUG
				// echo "<br/>DEBUG :: ITEMS <br />\n";
				// print_r($records_items);
				foreach ($records_items as $record_i){
					// DEBUG
					// echo "<br/>DEBUG :: ITEM <br />\n";
					// print_r($record_i);
					$expout .= $this->write_item( $record_i );
				}
			}
			$expout .= "</competence>\n";   
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
        $expout .= "\n\n<!-- domaine: $domaine->id  -->\n";
		// 
		if ($domaine){
			$id = $this->writeraw( $domaine->id );
            $code = $this->writeraw( trim($domaine->code) );
            $description = $this->writetext(trim($domaine->description));
            $referentielid = $this->writeraw( $domaine->referentielid );
			$sortorder = $this->writeraw( $domaine->sortorder );
			$nb_competences = $this->writeraw( $domaine->nb_competences );
            $expout .= "<domaine>\n";
			$expout .= "<id>$id</id>\n";
			$expout .= "<code>$code</code>\n";   
            $expout .= "<description>\n$description</description>\n";
            $expout .= "<referentielid>$referentielid</referentielid>\n";
            $expout .= "<sortorder>$sortorder</sortorder>\n";
            $expout .= "<nb_competences>$nb_competences</nb_competences>\n";
			
			// LISTE DES COMPETENCES DE CE DOMAINE
			$compteur_competence=0;
			$records_competences = referentiel_get_competences($domaine->id);
			if ($records_competences){
				// DEBUG
				// echo "<br/>DEBUG :: COMPETENCES <br />\n";
				// print_r($records_competences);
				foreach ($records_competences as $record_c){
					$expout .= $this->write_competence( $record_c );
				}
			}
			$expout .= " </domaine>\n";   
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
        $expout = "";
        // add comment		
        $expout .= "\n\n<!-- referentiel: $referentiel->id  -->\n";
		// 
		if ($referentiel){
			$id = $this->writeraw( $referentiel->id );
            $name = $this->writeraw( trim($referentiel->name) );
            $code = $this->writeraw( trim($referentiel->code));
            $description = $this->writetext(trim($referentiel->description));
            $url = $this->writeraw( trim($referentiel->url) );
			$certificatethreshold = $this->writeraw( $referentiel->certificatethreshold );
			$timemodified = $this->writeraw( $referentiel->timemodified );			
			$nb_domaines = $this->writeraw( $referentiel->nb_domaines );
			$liste_codes_competence = $this->writeraw( trim($referentiel->liste_codes_competence) );
			$local = $this->writeraw( $referentiel->local );
			$expout .= "<id>$id</id>\n";
			$expout .= "<name>$name</name>\n";   
			$expout .= "<code>$code</code>\n";   
            $expout .= "<description>\n$description</description>\n";
            $expout .= "<url>$url</url>\n";
            $expout .= "<certificatethreshold>$certificatethreshold</certificatethreshold>\n";
            $expout .= "<timemodified>$timemodified</timemodified>\n";			
            $expout .= "<nb_domaines>$nb_domaines</nb_domaines>\n";
            $expout .= "<liste_codes_competence>$liste_codes_competence</liste_codes_competence>\n";
			$expout .= "<local>$local</local>\n";
			
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
        // add comment		
        $expout .= "\n\n<!-- instance : ".$this->ireferentiel->id."  -->\n";
		// 

		if ($this->ireferentiel){
			// DEBUG
			// echo "<br />DEBUG LIGNE 1348<br />\n";
			// print_r($this->ireferentiel);
			
			$id = $this->writeraw( $this->ireferentiel->id );
            $name = $this->writeraw( trim($this->ireferentiel->name) );
            $description = $this->writetext(trim($this->ireferentiel->description));
            $domainlabel = $this->writeraw( trim($this->ireferentiel->domainlabel) );
            $skilllabel = $this->writeraw( trim($this->ireferentiel->skilllabel) );
            $itemlabel = $this->writeraw( trim($this->ireferentiel->itemlabel) );
            $timecreated = $this->writeraw( userdate($this->ireferentiel->timecreated));
            $course = $this->writeraw( $this->ireferentiel->course);
            $referentielid = $this->writeraw( $this->ireferentiel->referentielid);
			$visible = $this->writeraw( $this->ireferentiel->visible );

			$expout .= "<instance>\n";
			$expout .= "<id>$id</id>\n";
			$expout .= "<name>$name</name>\n";   
            $expout .= "<description>\n$description</description>\n";
            $expout .= "<domainlabel>$domainlabel</domainlabel>\n";
            $expout .= "<skilllabel>$skilllabel</skilllabel>\n";
            $expout .= "<itemlabel>$itemlabel</itemlabel>\n";
            $expout .= "<timecreated>$timecreated</timecreated>\n";
            $expout .= "<course>$course</course>\n";			
            $expout .= "<visible>$visible</visible>\n";
			// referentiel
            $expout .= "<referentielid>$referentielid</referentielid>\n";
			
			
			// CERTIFICATS
			if (isset($this->ireferentiel->referentielid) && ($this->ireferentiel->referentielid>0)){
				
				$record_referentiel = referentiel_get_referentiel_referentiel($this->ireferentiel->referentielid);
				$expout .= $this->write_referentiel($record_referentiel);
				
				$records_certificats = referentiel_get_certificats($this->ireferentiel->referentielid);
				// echo "<br />DEBUG LIGNE 1377<br />\n";
				// print_r($records_certificats);
				// exit;
		    	if ($records_certificats){
					foreach ($records_certificats as $record){
						$expout .= $this->write_certificat( $record );
					}
				}
			}
			$expout .= "</instance>\n\n";
        }
        return $expout;
    }
}


// ****************************************************************
// student

class eformat_xml extends eformat_default {

    function provide_import() {
        return false;
    }

    function provide_export() {
        return true;
    }


    // EXPORT FUNCTIONS START HERE

    function export_file_extension() {
    // override default type so extension is .xml
        return ".xml";
    }


    /**
     * Convert internal Moodle text format code into
     * human readable form
     * @param int id internal code
     * @return string format text
     */
    function get_format( $id ) {
        switch( $id ) {
        case 0:
            $name = "moodle_auto_format";
            break;
        case 1:
            $name = "html";
            break;
        case 2:
            $name = "plain_text";
            break;
        case 3:
            $name = "wiki_like";
            break;
        case 4:
            $name = "markdown";
            break;
        default:
            $name = "unknown";
        }
        return $name;
    }

    /**
     * Convert internal single question code into 
     * human readable form
     * @param int id single question code
     * @return string single question string
     */
    function get_single( $id ) {
        switch( $id ) {
        case 0:
            $name = "false";
            break;
        case 1:
            $name = "true";
            break;
        default:
            $name = "unknown";
        }
        return $name;
    }

    /**
     * generates <text></text> tags, processing raw text therein 
     * @param int ilev the current indent level
     * @param boolean short stick it on one line
     * @return string formatted text
     */

    function writetext( $raw, $ilev=0, $short=true) {
        $indent = str_repeat( "  ",$ilev );

        // encode the text to 'disguise' HTML content 
		$raw=ereg_replace("\r", "", $raw);
		$raw=ereg_replace("\n", "", $raw);
		
        $raw = htmlspecialchars( $raw );

        if ($short) {
            $xml = "$indent<text>$raw</text>\n";
        }
        else {
            $xml = "$indent<text>\n$raw\n$indent</text>\n";
        }

        return $xml;
    }

    /**
     * generates raw text therein 
     * @return string not formatted text
     */

    function writeraw( $raw) {
		$raw=ereg_replace("\r", "", $raw);
		$raw=ereg_replace("\n", "", $raw);
	    return $raw;
    }
  
    function xmltidy( $content ) {
        // can only do this if tidy is installed
        if (extension_loaded('tidy')) {
            $config = array( 'input-xml'=>true, 'output-xml'=>true, 'indent'=>true, 'wrap'=>0 );
            $tidy = new tidy;
            $tidy->parseString($content, $config, 'utf8');
            $tidy->cleanRepair(); 
            return $tidy->value;
        }
        else {
            return $content;
        }
    }


    function presave_process( $content ) {
    // override method to allow us to add xml headers and footers

        // add the xml headers and footers
        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
                       "<students>\n" .
                       $content . "\n" .
                       "</students>";

        // make the xml look nice
        $content = $this->xmltidy( $content );

        return $content;
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

	function write_etablissement( $record ) {
        // initial string;
        $expout = "";
		if ($record){
			$id = $this->writeraw( $record->id );
			$idnumber = $this->writeraw( $record->idnumber);
			$name = $this->writeraw( $record->name);
			$address = $this->writetext( $record->address);
			// $logo = $this->writeraw( $record->logo);
            $expout .= "<etablissement>\n";
			$expout .= "<id>$id</id>\n";
            $expout .= "<idnumber>$idnumber</$idnumber>\n";
            $expout .= "<name>$name</name>\n";			
            $expout .= "<address>\n$address</address>\n";
            // $expout .= "<logo>$logo</logo>\n";			
			$expout .= "</etablissement>\n";
        }
        return $expout;
    }
	
	function write_student( $record ) {
        // initial string;
        $expout = "";
        // add comment
        $expout .= "\n\n<!-- student: $record->id  -->\n";
		if ($record){
			// DEBUG
			// echo "<br />\n";
			// print_r($record);
			$id = $this->writeraw( $record->id );
			$userid = $this->writeraw( $record->userid );
            $ref_etablissement = $this->writeraw( $record->ref_etablissement);
			$num_student = $this->writeraw( $record->num_student);
			$ddn_student = $this->writeraw( $record->ddn_student);
			$lieu_naissance = $this->writeraw( $record->lieu_naissance);
			$departement_naissance = $this->writeraw( $record->departement_naissance);
			$adresse_student = $this->writeraw( $record->adresse_student);
            $expout .= "<student>\n";
			$expout .= "<id>$id</id>\n";
			$expout .= "<userid>$userid</userid>\n";
			$expout .= "<lastname_firstname>".referentiel_get_user_info($record->userid)."</lastname_firstname>\n";
			$expout .= "<num_student>$num_student</num_student>\n";
            $expout .= "<ddn_student>$ddn_student</ddn_student>\n";
            $expout .= "<lieu_naissance>$lieu_naissance</lieu_naissance>\n";
            $expout .= "<departement_naissance>$departement_naissance</departement_naissance>\n";			
            $expout .= "<adresse_student>$adresse_student</adresse_student>\n";
			$expout .= "<ref_etablissement>$ref_etablissement</ref_etablissement>\n";
			
	/*
			// Etablissement
			$record_etablissement=referentiel_get_etablissement($record->ref_etablissement);
	    	if ($record_etablissement){
				$expout .= $this->write_etablissement( $record_etablissement );
			}
	*/
			$expout .= "</student>\n";
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
			// studentS
			if (isset($this->ireferentiel->course) && ($this->ireferentiel->course>0)){
				// studentS
				$records_all_students = referentiel_get_students_course($this->ireferentiel->course);
				if ($records_all_students){
					foreach ($records_all_students as $record){
						// USER
						if (isset($record->userid) && ($record->userid>0)){
							$record_student = referentiel_get_student_user($record->userid);
		    				if ($record_student){
								$expout .= $this->write_student($record_student);
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
			foreach ($records_all_etablissements as $record){
				if ($record){
					$expout.=$this->write_etablissement($record);
				}
			}
        }
        return $expout;
    }

	
}	


/**********************************************************************
***********************************************************************
									TACHES
***********************************************************************
**********************************************************************/

class tformat_xml extends tformat_default {

    function provide_import() {
        return true;
    }

    function provide_export() {
        return true;
    }


    // EXPORT FUNCTIONS START HERE

    function export_file_extension() {
    // override default type so extension is .xml
        return ".xml";
    }


    /**
     * Convert internal Moodle text format code into
     * human readable form
     * @param int id internal code
     * @return string format text
     */
    function get_format( $id ) {
        switch( $id ) {
        case 0:
            $name = "moodle_auto_format";
            break;
        case 1:
            $name = "html";
            break;
        case 2:
            $name = "plain_text";
            break;
        case 3:
            $name = "wiki_like";
            break;
        case 4:
            $name = "markdown";
            break;
        default:
            $name = "unknown";
        }
        return $name;
    }

    /**
     * Convert internal single question code into 
     * human readable form
     * @param int id single question code
     * @return string single question string
     */
    function get_single( $id ) {
        switch( $id ) {
        case 0:
            $name = "false";
            break;
        case 1:
            $name = "true";
            break;
        default:
            $name = "unknown";
        }
        return $name;
    }

    /**
     * generates <text></text> tags, processing raw text therein 
     * @param int ilev the current indent level
     * @param boolean short stick it on one line
     * @return string formatted text
     */

    function writetext( $raw, $ilev=0, $short=true) {
        $indent = str_repeat( "  ",$ilev );

        // encode the text to 'disguise' HTML content 
		$raw=ereg_replace("\r", "", $raw);
		$raw=ereg_replace("\n", "", $raw);
		
        $raw = htmlspecialchars( $raw );

        if ($short) {
            $xml = "$indent<text>$raw</text>\n";
        }
        else {
            $xml = "$indent<text>\n$raw\n$indent</text>\n";
        }

        return $xml;
    }

    /**
     * generates raw text therein 
     * @return string not formatted text
     */

    function writeraw( $raw) {
		$raw=ereg_replace("\r", "", $raw);
		$raw=ereg_replace("\n", "", $raw);
	    return $raw;
    }
  
    function xmltidy( $content ) {
        // can only do this if tidy is installed
        if (extension_loaded('tidy')) {
            $config = array( 'input-xml'=>true, 'output-xml'=>true, 'indent'=>true, 'wrap'=>0 );
            $tidy = new tidy;
            $tidy->parseString($content, $config, 'utf8');
            $tidy->cleanRepair(); 
            return $tidy->value;
        }
        else {
            return $content;
        }
    }


    function presave_process( $content ) {
    // override method to allow us to add xml headers and footers

        // add the xml headers and footers
        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
                       "<referentiel>\n" .
                       $content . "\n" .
                       "</referentiel>";

        // make the xml look nice
        $content = $this->xmltidy( $content );

        return $content;
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
     * Turns consigne into an xml segment
     * @param consigne object
     * @return string xml segment
     */

    function write_consigne( $consigne ) {
    global $CFG;
        // initial string;
        $expout = "";
        // add comment		
        $expout .= "\n\n<!-- consigne: $consigne->id  -->\n";
		//
		if ($consigne){
			$id = $this->writeraw( $consigne->id );		
            $type = $this->writeraw( trim($consigne->type));
            $description = $this->writetext(trim($consigne->description));
			$url = $this->writeraw( $consigne->url);
            $taskid = $this->writeraw( $consigne->taskid);

            $expout .= "<consigne>\n";
			$expout .= "<id>$id</id>\n";
			$expout .= "<type>$type</type>\n";   
            $expout .= "<description>\n$description</description>\n";
            if (!eregi("http", $url)){  // completer l'adresse relative
                $url=$CFG->wwwroot.'/file.php/'.$url;
            }
            $expout .= "<url>$url</url>\n";
            $expout .= "<taskid>$taskid</taskid>\n";
			$expout .= "</consigne>\n";   
        }
        return $expout;
    }

    /**
     * Turns task into an xml segment
     * @param task object
     * @return string xml segment
     */

    function write_task( $task ) {
    global $CFG;
        // initial string;
        $expout = "";
        // add comment
        $expout .= "\n\n<!-- task: $task->id  -->\n";
		// 
		if ($task){
			// DEBUG
			// echo "<br />DEBUG LIGNE 960<br />\n";
			// print_r($task);
			
			$id = $this->writeraw( $task->id );
            $type = $this->writeraw( trim($task->type));
            $description = $this->writetext(trim($task->description));
            $competences_task = $this->writeraw(trim($task->competences_task));
            $criteres_evaluation = $this->writetext(trim($task->criteres_evaluation));
            $instanceid = $this->writeraw( $task->instanceid);
            $referentielid = $this->writeraw( $task->referentielid);
            $course = $this->writeraw( $task->course);
			$auteurid = $this->writeraw( trim($task->auteurid));
			$timecreated = $this->writeraw( $task->timecreated);
			$timemodified = $this->writeraw( $task->timemodified);
			$timestart = $this->writeraw( $task->timestart);
			$timeend = $this->writeraw( $task->timeend);
			
            $expout .= "<task>\n";
			$expout .= "<id>$id</id>\n";
			$expout .= "<type>$type</type>\n";
            $expout .= "<description>\n$description</description>\n";
            $expout .= "<competences_task>$competences_task</competences_task>\n";
            $expout .= "<criteres_evaluation>\n$criteres_evaluation</criteres_evaluation>\n";
            $expout .= "<instanceid>$instanceid</instanceid>\n";
            $expout .= "<referentielid>$referentielid</referentielid>\n";
            $expout .= "<course>$course</course>\n";
            $expout .= "<auteurid>$auteurid</auteurid>\n";
            $expout .= "<timecreated>$timecreated</timecreated>\n";
            $expout .= "<timemodified>$timemodified</timemodified>\n";
            $expout .= "<timestart>$timestart</timestart>\n";
			$expout .= "<timeend>$timeend</timeend>\n";

			// consigneS
			$records_consignes = referentiel_get_consignes($task->id);
			
			if ($records_consignes){
				foreach ($records_consignes as $record_d){
					$expout .= $this->write_consigne( $record_d );
				}
			}
			
			$expout .= "</task>\n";
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
		global $USER;
        // initial string;
        $expout = "";
	    $id = $this->rreferentiel->id;
		
		if ($this->rreferentiel){
		/*
CREATE TABLE mdl_referentiel_referentiel (
  id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL DEFAULT '',
  code varchar(20) NOT NULL DEFAULT '',
  referentielauthormail varchar(255) NOT NULL DEFAULT '',
  cle_referentiel varchar(255) NOT NULL DEFAULT '',
  password varchar(255) NOT NULL DEFAULT '',
  description text NOT NULL,
  url varchar(255) NOT NULL DEFAULT '',
  certificatethreshold double NOT NULL DEFAULT '0',
  timemodified bigint(10) unsigned NOT NULL DEFAULT '0',
  nb_domaines tinyint(2) unsigned NOT NULL DEFAULT '0',
  liste_codes_competence text NOT NULL,
  liste_empreintes_competence text NOT NULL,
  local bigint(10) unsigned NOT NULL DEFAULT '0',
  logo varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Referentiel de competence';
		
		*/
            $name = $this->writeraw( trim($this->rreferentiel->name));
            $code = $this->writeraw( trim($this->rreferentiel->code));
			$referentielauthormail = $this->writeraw( trim($this->rreferentiel->referentielauthormail));
			$cle_referentiel = $this->writeraw( trim($this->rreferentiel->cle_referentiel));
			$password = $this->writeraw( trim($this->rreferentiel->password));
            $description = $this->writetext(trim($this->rreferentiel->description));
            $url = $this->writeraw( trim($this->rreferentiel->url));
			$certificatethreshold = $this->writeraw( trim($this->rreferentiel->certificatethreshold));
			$timemodified = $this->writeraw( trim($this->rreferentiel->timemodified));			
			$nb_domaines = $this->writeraw( trim($this->rreferentiel->nb_domaines));
			$liste_codes_competence = $this->writeraw( trim($this->rreferentiel->liste_codes_competence));
			$liste_empreintes_competence = $this->writeraw( trim($this->rreferentiel->liste_empreintes_competence));
			$local = $this->writeraw( trim($this->rreferentiel->local));
			$logo = $this->writeraw( trim($this->rreferentiel->logo));
			
			// $expout .= "<id>$id</id>\n";
			$expout .= " <name>$name</name>\n";   
			$expout .= " <code>$code</code>\n";   
            $expout .= " <description>\n$description</description>\n";
            $expout .= " <cle_referentiel>$cle_referentiel</cle_referentiel>\n";			
            // $expout .= " <url>$url</url>\n";
            // $expout .= " <certificatethreshold>$certificatethreshold</certificatethreshold>\n";
            // $expout .= " <timemodified>$timemodified</timemodified>\n";			
            // $expout .= " <nb_domaines>$nb_domaines</nb_domaines>\n";
            $expout .= " <liste_codes_competence>$liste_codes_competence</liste_codes_competence>\n";
            // $expout .= " <liste_empreintes_competence>$liste_empreintes_competence</liste_empreintes_competence>\n";
			// $expout .= " <local>$local</local>\n";
			// PAS DE LOGO ICI
			// $expout .= " <logo>$logo</logo>\n";
		}
        return $expout;
    }
	

    function write_liste_tasks() {
    	global $CFG;
        // initial string;
        $expout = "";
		if ($this->rreferentiel){
			$expout .= $this->write_referentiel_reduit();
		}

		if ($this->ireferentiel){
			// DEBUG
			// echo "<br />DEBUG LIGNE 1021<br />\n";
			// print_r($this->ireferentiel);
			
			$id = $this->writeraw( $this->ireferentiel->id );
            $name = $this->writeraw( trim($this->ireferentiel->name) );
            $description = $this->writetext(trim($this->ireferentiel->description));
            $domainlabel = $this->writeraw( trim($this->ireferentiel->domainlabel) );
            $skilllabel = $this->writeraw( trim($this->ireferentiel->skilllabel) );
            $itemlabel = $this->writeraw( trim($this->ireferentiel->itemlabel) );
            $timecreated = $this->writeraw( $this->ireferentiel->timecreated);
            $course = $this->writeraw( $this->ireferentiel->course);
            $referentielid = $this->writeraw( $this->ireferentiel->referentielid);
			$visible = $this->writeraw( $this->ireferentiel->visible );
			
			/*
	        // INUTILE ICI
    	    $expout .= "<instance>\n";
			$expout .= "<id>$id</id>\n";
			$expout .= "<name>$name</name>\n";   
            $expout .= "<description>$description</description>\n";
            $expout .= "<domainlabel>$domainlabel</domainlabel>\n";
            $expout .= "<skilllabel>$skilllabel</skilllabel>\n";
            $expout .= "<itemlabel>$itemlabel</itemlabel>\n";
            $expout .= "<timecreated>$timecreated</timecreated>\n";
            $expout .= "<course>$course</course>\n";
            $expout .= "<referentielid>$referentielid</referentielid>\n";
            $expout .= "<visible>$visible</visible>\n";
			$expout .= "</instance>\n";
			*/
			
			// tasks
			if (isset($this->ireferentiel->id) && ($this->ireferentiel->id>0)){
				$records_tasks = referentiel_get_tasks_instance($this->ireferentiel->id);
				// print_r($records_tasks);
		    	if ($records_tasks){
					foreach ($records_tasks as $record_a){
						$expout .= $this->write_task( $record_a );
					}
				}
			}
        }
        return $expout;
    }
	
	
/***********************
 * IMPORTING FUNCTIONS
 ***********************/



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
    function import_tasks( $xmlreferentiel ) {
	// recupere le fichier xml 
	// selon les parametres soit cree une nouvelle instance 
	// soit modifie une instance courante de referentiel
	global $SESSION;
	global $USER;
	global $CFG;
		// print_r($xmlreferentiel);
		if (!(isset($this->course->id) && ($this->course->id>0)) 
			|| 
			!(isset($this->rreferentiel->id) && ($this->rreferentiel->id>0))
			|| 
			!(isset($this->coursemodule->id) && ($this->coursemodule->id>0))
			){
			$this->error( get_string( 'incompletedata', 'referentiel' ) );
			return false;
		}
		
		$referentiel_reconnu=false;
		
        // get some error strings
        $error_noname = get_string( 'xmlimportnoname', 'referentiel' );
        $error_nocode = get_string( 'xmlimportnocode', 'referentiel' );
		$error_override = get_string( 'overriderisk', 'referentiel' );
		$error_incompatible = get_string( 'incompatible_task', 'referentiel' );
		
        // this routine initialises the import object
        $re = $this->defaultreferentiel_reduit();
		
        // Une partie des données seulement sont utiles
		// $re->id = $this->getpath( $xmlreferentiel, array('#','id',0,'#'), '', false, '');
        $re->name = $this->getpath( $xmlreferentiel, array('#','name','0','#'), '', true, $error_noname);
        $re->code = $this->getpath( $xmlreferentiel, array('#','code',0,'#'), '', true, $error_nocode);
        $re->description = $this->getpath( $xmlreferentiel, array('#','description',0,'#','text',0,'#'), '', true, '');
		$re->cle_referentiel = $this->getpath( $xmlreferentiel, array('#','cle_referentiel',0,'#'), '', true, '');
        /*
		$re->url = $this->getpath( $xmlreferentiel, array('#','url',0,'#'), '', true, '');		
		$re->certificatethreshold = $this->getpath( $xmlreferentiel, array('#','certificatethreshold',0,'#'), '', false, '');
		$re->timemodified = $this->getpath( $xmlreferentiel, array('#','timemodified',0,'#'), '', false, '');		
		$re->nb_domaines = $this->getpath( $xmlreferentiel, array('#','nb_domaines',0,'#'), '', false, '');				
		*/
		$re->liste_codes_competence = $this->getpath( $xmlreferentiel, array('#','liste_codes_competence',0,'#'), '', true, '');
		/*
		$re->liste_empreintes_competence = $this->getpath( $xmlreferentiel, array('#','liste_empreintes_competence',0,'#'), '', true, '');
		/*
		$re->logo = $this->getpath( $xmlreferentiel, array('#','logo',0,'#'), '', true, '');
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
		
		$referentiel_reconnu=false;

		// importer dans le cours courant et l'instance courante
		// Verifier si referentiel referentiel local identique a referentiel referentiel importe
		if (isset($re->cle_referentiel) && ($re->cle_referentiel!="")){	
			if (isset($this->rreferentiel->cle_referentiel) && ($this->rreferentiel->cle_referentiel==$re->cle_referentiel)){
				$referentiel_reconnu=true;
			}
		}

        if (($referentiel_reconnu==false) && isset($re->code) && ($re->code!="") && ($this->rreferentiel->code==$re->code)){
            // verifier la liste des items
            if ($this->rreferentiel->liste_codes_competence==$re->liste_codes_competence){
    			$referentiel_reconnu=true;
            }
    	}
        // DEBUG
        // echo "<br>FORMAT XML :: 2461 :: CODE REFERENTIEL: '".$re->code."' --- CODE IMPORTE :'".$this->rreferentiel->code."'\n";


		if ($referentiel_reconnu==false) {
			// ni nouvelle instance ni recouvrement
			$this->error( $error_incompatible );
			return false;
		}
		else{
			// importer les taches
			$xmltasks = $xmlreferentiel['#']['task'];
			
        	foreach ($xmltasks as $xmltask) {
				// TACHE
				// print_r($task);
				$new_task = $this->defaulttask();
				$new_task->type=$this->getpath( $xmltask, array('#','type',0,'#'), '', true, $error_nocode);
				$new_task->description=$this->getpath( $xmltask, array('#','description',0,'#','text',0,'#'), '', true, '');
				$new_task->competences_task=$this->getpath( $xmltask, array('#','competences_task',0,'#'), '', true, $error_nocode);
				$new_task->criteres_evaluation=$this->getpath( $xmltask, array('#','criteres_evaluation',0,'#','text',0,'#'), '', true, '');
				$new_task->instanceid=$this->ireferentiel->id;
				$new_task->referentielid=$this->rreferentiel->id;
				$new_task->course=$this->course->id;
				$new_task->auteurid=$USER->id;
				$new_task->timecreated=$this->getpath( $xmltask, array('#','timecreated',0,'#'), '', true, $error_nocode);
				$new_task->timemodified=$this->getpath( $xmltask, array('#','timemodified',0,'#'), '', true, $error_nocode);
				$new_task->timestart=$this->getpath( $xmltask, array('#','timestart',0,'#'), '', true, $error_nocode);
				$new_task->timeend=$this->getpath( $xmltask, array('#','timeend',0,'#'), '', true, $error_nocode);
				// MODIF JF 2010/10/07
				$new_task->hidden=1;
				
				// enregistrer
				$new_task_id=insert_record("referentiel_task", $new_task);
				if ($new_task_id){
					// importer les consignes
					if (!empty($xmltask['#']['consigne'])){
                        $xmlconsignes = $xmltask['#']['consigne'];
                        $cindex=0;
                        foreach ($xmlconsignes as $xmlconsigne) {
                            $new_consigne = $this->defaultconsigne();
		    			    // $new_consigne->id = $this->getpath( $xmlconsigne, array('#','id',0,'#'), '', false, '');
						    $new_consigne->type=$this->getpath( $xmlconsigne, array('#','type',0,'#'), '', true, $error_nocode);
						    $new_consigne->description=$this->getpath( $xmlconsigne, array('#','description',0,'#','text',0,'#'), '', true, '');
						    $new_consigne->url=$this->getpath( $xmlconsigne, array('#','url',0,'#'), '', true, $error_nocode);

                            if (!eregi("http", $new_consigne->url)){
							   $url=$CFG->wwwroot.'/file.php/'.$url; // pas de liens relatifs sur les fichiers telecharges
						    }

                            $new_consigne->taskid=$new_task_id;
						    // enregistrer :  creation
						    $new_consigne_id=insert_record("referentiel_consigne", $new_consigne);
                        }
                    }
				}
			}
        }
        return $re;
    }



    /**
     * parse the array of lines into an array of questions
     * this *could* burn memory - but it won't happen that much
     * so fingers crossed!
     * @param array lines array of lines from the input file
     * @return array (of objects) question objects
     */
    function read_import_tasks($lines) {
        // we just need it as one big string
        $text = implode($lines, " ");
        unset( $lines );

        // this converts xml to big nasty data structure
        // the 0 means keep white space as it is (important for markdown format)
        // print_r it if you want to see what it looks like!
        $xml = xmlize( $text, 0 ); 
		
		// DEBUG
		// echo "<br />DEBUG xml/format.php :: ligne 580<br />\n";
		// print_r($xml);
		// echo "<br /><br />\n";		
		// exit;
        if (!empty($xml['referentiel'])){
            $re=$this->import_tasks($xml['referentiel']);
            // stick the result in the $treferentiel array
            // DEBUG
            // echo "<br />DEBUG xml/format.php :: ligne 632\n";
            // print_r($re);
            return $re;
        }
        return NULL;
    }
}

?>
