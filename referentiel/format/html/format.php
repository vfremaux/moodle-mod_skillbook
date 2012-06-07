<?php 
// Based on default.php, included by ../import.php

class rformat_html extends rformat_default {

    function provide_export() {
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
  		// get css bit
		$css_lines = file( "$CFG->dirroot/mod/referentiel/format/html/html.css" );
		$css = implode( ' ',$css_lines ); 
		$xp = "<html>\n";
  		$xp .= "<head>\n";
  		$xp .= "<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\" />\n";
		$xp .= "<meta author=\"".referentiel_get_user_info($USER->id)."\">\n";
  		$xp .= "<title>Moodle Referentiel HTML Export</title>\n";
  		$xp .= $css;
  		$xp .= "</head>\n";
		$xp .= "<body>\n";
		$xp .= $content;
		$xp .= "</body>\n";
		$xp .= "</html>\n";

  		return $xp;
	}

	function export_file_extension() {
  		return "_h.html";
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

    function write_ligne( $raw, $sep="/", $nmaxcar=60) {
        // insere un saut de ligne apres le 80 caracter 
		$s=$raw;
		$s1="";
		$s2="";
		$out="";
		$nbcar=strlen($s);
		while ($nbcar>$nmaxcar){
			$s1=substr( $s,0,$nmaxcar);
			$pos1=strrpos($s1,$sep);
			if ($pos1>0){
				$s1=substr( $s,0,$pos1);
				$s=substr( $s,$pos1+1);
			}
			else {
				$s1=substr( $s,0,$nmaxcar);
				$s=substr( $s,$nmaxcar);
			}
			$out.=$s1. " ";
			$nbcar=strlen($s);
		}
		$out.=$s;
		return $out;
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
		if ($item){
			// DEBUG
			// echo "<br />\n";
			// print_r($item);
            $code = $item->code;
            $description = $item->description;
            $referentielid = $item->referentielid;
            $skillid = $item->skillid;
			$type = $item->type;
			$weight = $item->weight;
			$sortorder = $item->sortorder;

            $expout .= "   <tr>\n";
			$expout .= "     <td class=\"item\"> ".stripslashes($code)."</td>\n";   
            $expout .= "     <td class=\"item\"> ".stripslashes($description)."</td>\n";
            // $expout .= "   <td class=\"item\"> $referentielid</td>\n";
            // $expout .= "   <td class=\"item\"> $skillid</td>\n";
            $expout .= "     <td class=\"item\"> ".stripslashes($type)."</td>\n";
            $expout .= "     <td class=\"item\"> $weight</td>\n";
            $expout .= "     <td class=\"item\"> $sortorder</td>\n";			
			$expout .= "   </tr>\n";   
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
		
		if ($competence){
            $code = $competence->code;
            $description = $competence->description;
            $domainid = $competence->domainid;
			$sortorder = $competence->sortorder;
			$nb_item_competences = $competence->nb_item_competences;
	    	
   			$expout .= "<table class='competence'>\n";
	        $expout .= "<tr>\n";
			$expout .= "    <th class=\"competence\"><b>".get_string('code','referentiel')."</b></th>\n";   
   	        $expout .= "    <th class=\"competence\"><b>".get_string('description','referentiel')."</b></th>\n";
       	    // $expout .= "    <th class=\"competence\"><b>".get_string('domainid','referentiel')."</b></th>\n";
           	$expout .= "    <th class=\"competence\"><b>".get_string('sortorder','referentiel')."</b></th>\n";
            $expout .= "    <th class=\"competence\"><b>".get_string('nb_item_competences','referentiel')."</b></th>\n";
			$expout .= "</tr>\n";
			
			$expout .= "  <tr>\n";
			$expout .= "    <td class=\"competence\"> ".stripslashes($code)."</td>\n";   
            $expout .= "    <td class=\"competence\"> ".stripslashes($description)."</td>\n";
            // $expout .= "  <td class=\"competence\"> $domainid</td>\n";
            $expout .= "    <td class=\"competence\"> $sortorder</td>\n";
            $expout .= "    <td class=\"competence\"> $nb_item_competences</td>\n";
			$expout .= "  </tr>\n";
			$expout .= "</table>\n";

			// ITEM
			$compteur_item=0;
			$records_items = referentiel_get_item_competences($competence->id);
			
			if ($records_items){
				// DEBUG
				// echo "<br/>DEBUG :: ITEMS <br />\n";
				// print_r($records_items);
   				$expout .= "<table class='item'>\n";
	       	 	$expout .= "   <tr>\n";
				$expout .= "     <th class=\"item\"><b>".get_string('code','referentiel')."</b></th>\n";   
           		$expout .= "     <th class=\"item\"><b>".get_string('description','referentiel')."</b></th>\n";
		        // $expout .= "     <th class=\"item\"><b>".get_string('referentielid','referentiel')."</b></th>\n";
    	   		// $expout .= "     <th class=\"item\"><b>".get_string('skillid','referentiel')."</b></th>\n";
	    	    $expout .= "     <th class=\"item\"><b>".get_string('type','referentiel')."</b></th>\n";
       			$expout .= "     <th class=\"item\"><b>".get_string('weight','referentiel')."</b></th>\n";
		        $expout .= "     <th class=\"item\"><b>".get_string('sortorder','referentiel')."</b></th>\n";
				$expout .= "   </tr>\n"; 

				foreach ($records_items as $record_i){
					// DEBUG
					// echo "<br/>DEBUG :: ITEM <br />\n";
					// print_r($record_i);
					$expout .= $this->write_item( $record_i );
				}
				$expout .= "</table>\n";
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
            $description = $domaine->description;
            $referentielid = $domaine->referentielid;
			$sortorder = $domaine->sortorder;
			$nb_competences = $domaine->nb_competences;

			$expout .= "<br /><table class='domaine'>\n";
			$expout .= "<tr>\n";			
			$expout .= "   <th class=\"domaine\"><b>".get_string('code','referentiel')."</b></th>\n";   
		    $expout .= "   <th class=\"domaine\"><b>".get_string('description','referentiel')."</b></th>\n";
        	// $expout .= "   <th class=\"domaine\"><b>".get_string('referentielid','referentiel')."</b></th>\n";
		    $expout .= "   <th class=\"domaine\"><b>".get_string('sortorder','referentiel')."</b></th>\n";
	        $expout .= "   <th class=\"domaine\"><b>".get_string('nb_competences','referentiel')."</b></th>\n";
			$expout .= "</tr>\n";	
			
			$expout .= "<tr>\n";			
			$expout .= "   <td class=\"domaine\"> ".stripslashes($code)."</td>\n";   
            $expout .= "   <td class=\"domaine\"> ".stripslashes($description)."</td>\n";
            // $expout .= "   </td><td class=\"domaine\"> $referentielid</td>\n";
            $expout .= "   <td class=\"domaine\"> $sortorder</td>\n";
            $expout .= "   <td class=\"domaine\"> $nb_competences</td>\n";
			$expout .= "</tr>\n";
			$expout .= "</table>\n";
			
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
		global $USER;
        // initial string;
        $expout = "";
	    $id = $this->rreferentiel->id;

    	// add comment and div tags
    	$expout .= "<!-- date: ".date("Y/m/d")." referentiel:  ".$this->rreferentiel->id."  name: ".stripslashes($this->rreferentiel->name)." -->\n";
    	// add header
    	$expout .= "<h3>".stripslashes($this->rreferentiel->name)."</h3>\n";
		
		if ($this->rreferentiel){
            $name = $this->rreferentiel->name;
            $code = $this->rreferentiel->code;
            $description = $this->rreferentiel->description;
            $url = $this->rreferentiel->url;
			$certificatethreshold = $this->rreferentiel->certificatethreshold;
			$timemodified = $this->rreferentiel->timemodified;			
			$nb_domaines = $this->rreferentiel->nb_domaines;
			$liste_codes_competence = $this->rreferentiel->liste_codes_competence;
			$liste_empreintes_competence = $this->rreferentiel->liste_empreintes_competence;
			$local = $this->rreferentiel->local;
			$logo = $this->rreferentiel->logo;
			
	    	$expout .= "<table class=\"referentiel\">\n";
			$expout .= "<tr>\n";
			$expout .= " <th class=\"referentiel\"><b>".get_string('name','referentiel')."</b></th>\n";
			$expout .= " <th class=\"referentiel\"><b>".get_string('code','referentiel')."</b></th>\n";   
            $expout .= " <th class=\"referentiel\" colspan=\"2\"><b>".get_string('description','referentiel')."</b></th>\n";
			$expout .= " </tr>\n";
			$expout .= "<tr>\n";
			$expout .= " <td class=\"referentiel\"> ".stripslashes($name)."</td>\n";
			$expout .= " <td class=\"referentiel\"> ".stripslashes($code)."</td>\n";   
            $expout .= " <td class=\"referentiel\" colspan=\"2\"> ".stripslashes($description)."</td>\n";
			$expout .= " </tr>\n";			
			$expout .= "<tr>\n";
			$expout .= "<tr>\n";
            $expout .= " <th class=\"referentiel\"><b>".get_string('url','referentiel')."</b></th>\n";
            $expout .= " <th class=\"referentiel\"><b>".get_string('liste_codes_competence','referentiel')."</b></th>\n";
            $expout .= " <th class=\"referentiel\"><b>".get_string('certificatethreshold','referentiel')."</b></th>\n";
            $expout .= " <th class=\"referentiel\"><b>".get_string('nb_domaines','referentiel')."</b></th>\n";
            // $expout .= " <td class\"referentiel\"><b>".get_string('local','referentiel')."</b></td>\n";
			$expout .= "</tr>\n";			
            $expout .= " <td class=\"referentiel\"> <a href=\"".$url."\" title=\"".$url."\" target=\"_blank\">".$url."</a></td>\n";
            $expout .= " <td class=\"referentiel\"> ".$this->write_ligne($liste_codes_competence,"/",60)."</td>\n";
            $expout .= " <td class=\"referentiel\"> ".$this->write_ligne($liste_empreintes_competence,"/",60)."</td>\n";
            $expout .= " <td class=\"referentiel\"> $certificatethreshold</td>\n";
            $expout .= " <td class=\"referentiel\"> $nb_domaines</td>\n";
            // $expout .= " <td class\"referentiel\"> $local</td>\n";
			$expout .= " <td class\"referentiel\"> $logo</td>\n";
			$expout .= "</tr>\n";
			$expout .= "</table>\n\n\n";
			
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
}

/**********************************************************************
***********************************************************************
									ACTIVITES
***********************************************************************
**********************************************************************/


// ACTIVITES : export des activites
class aformat_html extends aformat_default {

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

  		// get css bit
		$css_lines = file( "$CFG->dirroot/mod/referentiel/format/xhtml/xhtml.css" );
		$css = implode( ' ',$css_lines ); 
		$xp =  "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n";
		$xp .= "  \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
		$xp .= "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n";
  		$xp .= "<head>\n";
  		$xp .= "<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\" />\n";
  		$xp .= "<title>Moodle Referentiel :: Activite XHTML Export</title>\n";
  		$xp .= $css;
  		$xp .= "</head>\n";
		$xp .= "<body>\n";
		$xp .= $content;
		$xp .= "</body>\n";
		$xp .= "</html>\n";

  		return $xp;
	}

	function export_file_extension() {
  		return "_h.html";
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

    /**
     * Turns item into an xml segment
     * @param item object
     * @return string xml segment
     */


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
        $expout .= "\n\n<!-- document: $document->id  -->
<table class='item'>\n";
		if ($document){
			$id = $document->id ;		
            $type = trim($document->type);
            $description = trim($document->description);
			$url = $document->url;
            $activityid = $document->activityid;
            $expout .= "   <tr>\n";
            $expout .= "     <td><b>".get_string('type','referentiel')."</b></td><td> $type</td>\n";   
            $expout .= "     <td><b>".get_string('description','referentiel')."</b></td><td> $description</td>\n";
            $expout .= "     <td><b>".get_string('url','referentiel')."</b></td><td> $url</td>\n";
            $expout .= "     <td><b>".get_string('activityid','referentiel')."</b></td><td> $activityid</td>\n";
			$expout .= "   </tr>\n";   
        }
		$expout .= "</table>\n";
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
        $expout .= "\n\n<!-- activite: $activite->id  -->
<table class='competence'>\n";
		// 
		if ($activite){
			// DEBUG
			// echo "<br />\n";
			// print_r($activite);
			$id = $activite->id;
            $type_activite = trim($activite->type_activite);
            $description = trim($activite->description);
            $comptencies = trim($activite->comptencies);
            $comment = trim($activite->comment);
            $instanceid = $activite->instanceid;
            $referentielid = $activite->referentielid;
            $course = $activite->course;
			$userid = trim($activite->userid);
			$teacherid = $activite->teacherid;
			$timecreated = $activite->timecreated;
			$timemodified = $activite->timemodified;
			$approved = $activite->approved;
			
            $expout .= "<tr>\n";
            $expout .= "<td><b>".get_string('id','referentiel')."</b></td><td> $id</td>\n";
			$expout .= "<td><b>".get_string('type_activite','referentiel')."</b></td><td> $type_activite</td>\n";
            $expout .= "<td><b>".get_string('description','referentiel')."</b></td><td> $description</td>\n";
            $expout .= "<td><b>".get_string('comptencies','referentiel')."</b></td><td> $comptencies</td>\n";
            $expout .= "<td><b>".get_string('commentaire','referentiel')."</b></td><td> $comment</td>\n";
            $expout .= "<td><b>".get_string('instance','referentiel')."</b></td><td> $instanceid</td>\n";
            $expout .= "<td><b>".get_string('referentielid','referentiel')."</b></td><td> $referentielid</td>\n";
            $expout .= "<td><b>".get_string('course','referentiel')."</b></td><td> $course</td>\n";
            $expout .= "<td><b>".get_string('userid','referentiel')."</b></td><td> $userid</td>\n";
            $expout .= "<td><b>".get_string('teacherid','referentiel')."</b></td><td> $teacherid</td>\n";
            $expout .= "<td><b>".get_string('timecreated','referentiel')."</b></td><td>".date("Y-m-d H:i:s",$timecreated)."</td>\n";
            $expout .= "<td><b>".get_string('timemodified','referentiel')."</b></td><td>".date("Y-m-d H:i:s",$timemodified)."</td>\n";
            $expout .= "<td><b>".get_string('approved','referentiel')."</b></td><td> $approved</td>\n";
			
			// DOCUMENTS
			$records_documents = referentiel_get_documents($activite->id);
			
			if ($records_documents){
				foreach ($records_documents as $record_d){
					$expout .= $this->write_document( $record_d );
				}
			}
		}	
		$expout .= "</table>\n";
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
	    $id = $this->ireferentiel->id;

    	// add comment and div tags
    	$expout .= "<!-- certification :  $this->ireferentiel->id  name: $this->ireferentiel->name -->\n";
    	$expout .= "<table class=\"referentiel\">\n";

    	// add header
    	$expout .= "<h3>$this->ireferentiel->name</h3>\n";
		// 
		$expout .= "<tr>\n";
		// 
		if ($this->ireferentiel){
			$id = $this->ireferentiel->id;
            $name = trim($this->ireferentiel->name);
            $description = trim($this->ireferentiel->description);
            $domainlabel = trim($this->ireferentiel->domainlabel);
            $skilllabel = trim($this->ireferentiel->skilllabel);
            $itemlabel = trim($this->ireferentiel->itemlabel);
            $timecreated = $this->ireferentiel->timecreated;
            $course = $this->ireferentiel->course;
            $referentielid = $this->ireferentiel->referentielid;
			$visible = $this->ireferentiel->visible;

			$expout .= " <td><b>".get_string('id','referentiel')."</b></td><td> $id</td>\n";
			$expout .= " <td><b>".get_string('name','referentiel')."</b></td><td> $name</td>\n";
			$expout .= " <td><b>".get_string('description','referentiel')."</b></td><td> description</td>\n";   
            $expout .= " <td><b>".get_string('domainlabel','referentiel')."</b></td><td> $domainlabel</td>\n";
            $expout .= " <td><b>".get_string('skilllabel','referentiel')."</b></td><td> $skilllabel</td>\n";
            $expout .= " <td><b>".get_string('itemlabel','referentiel')."</b></td><td> $itemlabel</td>\n";			
            $expout .= " <td><b>".get_string('timecreated','referentiel')."</b></td><td>".date("Y-m-d H:i:s",$timecreated)."</td>\n";
            $expout .= " <td><b>".get_string('course')."</b></td><td> $course</td>\n";
            $expout .= " <td><b>".get_string('referentielid','referentiel')."</b></td><td> $referentielid</td>\n";
            $expout .= " <td><b>".get_string('visible','referentiel')."</b></td><td> $visible</td>\n";
			
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


// //////////////////////////////////////////////////////////////////////////////////////////////////////
// certificate : export des certificats
// //////////////////////////////////////////////////////////////////////////////////////////////////////


class cformat_html extends cformat_default {

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

  		// get css bit
		$css_lines = file( "$CFG->dirroot/mod/referentiel/format/xhtml/xhtml.css" );
		$css = implode( ' ',$css_lines ); 
		$xp =  "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n";
		$xp .= "  \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
		$xp .= "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n";
  		$xp .= "<head>\n";
  		$xp .= "<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\" />\n";
  		$xp .= "<title>Moodle Referentiel :: Certificats XHTML Export</title>\n";
  		$xp .= $css;
  		$xp .= "</head>\n";
		$xp .= "<body>\n";
		$xp .= $content;
		$xp .= "</body>\n";
		$xp .= "</html>\n";

  		return $xp;
	}

	function export_file_extension() {
  		return "_h.html";
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


    function write_etablissement( $record ) {
        // initial string;
        $expout = "";
        // add comment
        $expout .= "\n\n<!-- etablissement: $record->id  -->\n";
		if ($record){
			$id = trim( $record->id );
			$idnumber = trim( $record->idnumber);
			$name = trim( $record->name);
			$address = trim( $record->address);
			$logo = trim( $record->logo);
			if (!$this->format_condense){
        		$expout .= "<tr><td colspan='9'>\n";
                $expout .= "<b>".get_string('etablissement','referentiel')."</b>\n";
                $expout .= "</td></tr><tr>\n";
                $expout .= " <td><b>".get_string('id','referentiel')."</b></td>
<td><b>".get_string('idnumber','referentiel')."</b></td>
<td colspan='2'><b>".get_string('name','referentiel')."</b></td>
<td colspan='2'><b>".get_string('address','referentiel')."</b></td>
<td colspan='3'><b>".get_string('logo','referentiel')."</b></td></tr>\n";

                $expout .= "<tr>\n<td> $id</td>
            <td> $idnumber</td>
<td colspan='2'> $name</td>
<td colspan='2'> $address</td>
<td colspan='3'> $logo</td>\n</tr>\n";
            }
        }
        return $expout;
    }


	
	function write_student( $record ) {
        // initial string;
        $expout = "";
        // add comment

		if ($record){
			// DEBUG
			// echo "<br />\n";
			// print_r($record);
			$id = trim( $record->id );
			$userid = trim( $record->userid );
            $ref_etablissement = trim( $record->ref_etablissement);
			$num_student = trim( $record->num_student);
			$ddn_student = trim( $record->ddn_student);
			$lieu_naissance = trim( $record->lieu_naissance);
			$departement_naissance = trim( $record->departement_naissance);
			$adresse_student = trim( $record->adresse_student);			

            if (!$this->format_condense){
                $expout .= "\n\n<!-- record student: $id  -->\n";
                $expout .= "<table class=\"referentiel\">\n";
                $expout .= "<tr><td colspan='9'><b>student</b></td></tr>\n";
                $expout .= "<tr><td><b>".get_string('id','referentiel')."</b></td>
<td><b>".get_string('userid','referentiel')."</b></td>
<td><b>".get_string('nom_prenom','referentiel')."</b></td>
<td><b>".get_string('num_student','referentiel')."</b></td>
<td><b>".get_string('ddn_student','referentiel')."</b></td>
<td><b>".get_string('lieu_naissance','referentiel')."</b></td
<td><b>".get_string('departement_naissance','referentiel')."</b></td>
<td><b>".get_string('adresse_student','referentiel')."</b></td>
<td><b>".get_string('ref_etablissement','referentiel')."</b></td>
</td>\n<tr>\n";
                $expout .= " <td> $id</td><td> $userid</td><td> ".referentiel_get_user_info($record->userid)."</td>
<td> $num_student</td><td> $ddn_student</td><td> $lieu_naissance</td><td> $departement_naissance</td>
<td> $adresse_student</td><td> $ref_etablissement</td>\n";

                // Etablissement
                $record_etablissement=referentiel_get_etablissement($record->ref_etablissement);
                if ($record_etablissement){
                    $expout .= $this->write_etablissement( $record_etablissement );
                }
                $expout .= "</table>\n\n";
            }
            else{
                $expout .= "<tr><td> $userid</td><td> ".referentiel_get_user_info($record->userid)."</td><td> $num_student</td>";
            }
        }
        return $expout;
    }

	 /**
     * Turns referentiel instance into an html segment
     * @param referentiel instanceobject
     * @return string xml segment
     */

    function write_certificat( $record ) {
    	global $CFG;
        // initial string;
        $expout = "";
    	// add comment and div tags

		if ($record){
            // DEBUG
            // echo "<br />DEBUG LIGNE 1021<br />\n";
            // print_r($referentiel_instance);
            $id = trim( $record->id );
            if (isset($record->synthese_certificat)){
                $synthese_certificate = trim($record->synthese_certificat);
            }
            else{
                $synthese_certificate = '';
            }
            $comment = trim($record->comment);
            $synthese_certificate = trim($record->synthese_certificat);
            $competences_certificate =  trim($record->competences_certificat) ;
            $decision_jury = trim($record->decision_jury);
            $date_decision = userdate(trim($record->date_decision));
            $userid = trim( $record->userid);
            $teacherid = trim( $record->teacherid);
            $referentielid = trim( $record->referentielid);
            $verrou = trim( $record->verrou );
            $valide = trim( $record->valide );
            $evaluation = trim( $record->evaluation );
            $synthese_certificate = trim($record->synthese_certificat);

    	    // add header
            if (!$this->format_condense){
                $expout .= "<!-- record certification :  $record->id  -->\n";
                $expout .= "<p>&nbsp;</p>\n<p align='center'><b>".get_string('certificat','referentiel')."</b> #".$record->id."</p>\n";
            }

            // USER
            if (isset($userid) && ($userid>0)){
                $record_student = referentiel_get_student_user($userid);
		    	if ($record_student){
                    $expout .= $this->write_student( $record_student );
                }
                if (!$this->format_condense){
                    $expout .= "<table class=\"referentiel\">\n";

                    $expout .= "<tr>\n
<td><b>".get_string('synthese_certificat','referentiel')."</b></td>
<td><b>".get_string('comment','referentiel')."</b></td>
<td><b>".get_string('synthese_certificat','referentiel')."</b></td>
<td><b>".get_string('competences_certificat','referentiel')."</b></td>
<td><b>".get_string('decision_jury','referentiel')."</b></td>
<td><b>".get_string('date_decision','referentiel')."</b></td>
<!-- <td><b>".get_string('referentielid','referentiel')."</b></td> -->
<td><b>".get_string('verrou','referentiel')."</b></td>
<!-- <td><b>".get_string('valide','referentiel')."</b></td> -->
<td><b>".get_string('evaluation','referentiel')."</b></td>
<td><b>".get_string('synthese','referentiel')."</b></td>
</tr>\n";
                    $expout .= "<tr>\n
<td> $synthese_certificat</td>
<td> $comment</td>
<td> $synthese_certificat</td>
<td> $competences_certificat</td>
<td> $decision_jury</td>
<td>".date("Y-m-d H:i:s",$date_decision)."</td>
<!-- <td> $referentielid</td>  -->
<td> $verrou</td>
<!-- <td> $valide</td>  -->
<td> $evaluation</td>\n
<td> $synthese_certificat</td>
</tr>\n";
                    $expout .= "</table>\n<br />\n<br />\n";
                }
                else{
                    $expout .= $this->certificate_pourcentage($competences_certificat, $this->referentielid)."\n";
                    $expout .= "</tr>\n";
                }
            }
		}
        return $expout;
    }

    function write_referentiel( $referentiel ) {
    	global $CFG;
        // initial string;
		$expout ="";
		if ($referentiel){
			$id = $referentiel->id;
            $name = $referentiel->name;
            $code = $referentiel->code;
            $description = $referentiel->description;
            $url = $referentiel->url;
			$certificatethreshold = $referentiel->certificatethreshold;
			$timemodified = $referentiel->timemodified;
			$nb_domaines = $referentiel->nb_domaines;
			$liste_codes_competence = $referentiel->liste_codes_competence;
			$liste_empreintes_competence = $referentiel->liste_empreintes_competence;
			$local = $referentiel->local;

			// $expout = "#Referentiel : ".$referentiel->id." : ".stripslashes($referentiel->name)."\n";
            // add header
            if ($this->format_condense){
                // echo "DEBUG :: ".$this->format_condense."\n";
                // exit;
                $expout .= "<table class=\"referentiel\">\n";
                $expout .= "<tr><td><b>".get_string('referentiel','referentiel')."</b></td>\n";
                $expout .= "<td>".stripslashes($name)."</td><td>".stripslashes($code)."</td><td>".stripslashes($description)."</td></tr>\n";
                $expout .= "</table>\n";
                $expout .= "<table class=\"referentiel\">\n";
                $expout .= "<tr><th>user_id</th><th>".get_string('firstname')." ".get_string('lastname')."</th><th>".get_string('num_student','referentiel')."</th>\n";
                $expout .= $this->liste_codes_competences($referentiel->id)."</tr>\n";
            }
            /*
            else{     // developper le referentiel ?
                $expout .= "#id_referentiel;name;code;description;url;certificatethreshold;timemodified;nb_domaines;liste_codes_competences;liste_empreintes_competences;local\n";
                $expout .= "$id;".stripslashes($name).";".stripslashes($code).";".stripslashes($description).";$url;$certificatethreshold;".referentiel_timestamp_date_special($timemodified).";$nb_domaines;".stripslashes($liste_codes_competence).";".stripslashes($liste_empreintes_competence).";$local\n";

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
			*/
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
	    $id = $this->ireferentiel->id;

		//
		if ($this->ireferentiel){
            $id = $this->ireferentiel->id;
            $name = trim($this->ireferentiel->name);
            $description = trim($this->ireferentiel->description);
            $domainlabel = trim($this->ireferentiel->domainlabel);
            $skilllabel = trim($this->ireferentiel->skilllabel);
            $itemlabel = trim($this->ireferentiel->itemlabel);
            $timecreated = $this->ireferentiel->timecreated;
            $course = $this->ireferentiel->course;
            $referentielid = $this->ireferentiel->referentielid;
            $visible = $this->ireferentiel->visible;

            $expout .= "<!-- certification :  ".$this->ireferentiel->id."  name: ".$this->ireferentiel->name." -->\n";
            $expout .= "<h2>".$this->ireferentiel->name."</h2>\n";



            if ($this->format_condense){
                $expout .= "<table class=\"referentiel\">\n";
                $expout .= "<tr>\n";
                $expout .= " <td><b>".get_string('instance','referentiel')."</b></td>";
                $expout .= " <td> $name</td>\n";
                $expout .= " <td> $description</td>\n";
                $expout .= "</tr>\n</table>";
            }
            else{
    	        //
                $expout .= "<table class=\"referentiel\">\n";
                $expout .= " <td colspan='10'><b>".get_string('instance','referentiel')."</b></td>";
                $expout .= "</tr>\n<tr>\n";
                $expout .= " <td><b>".get_string('id','referentiel')."</b></td><td><b>".get_string('name','referentiel')."</b></td><td><b>".get_string('description','referentiel')."</b></td>
<td><b>".get_string('domainlabel','referentiel')."</b></td><td><b>".get_string('skilllabel','referentiel')."</b></td><td><b>".get_string('itemlabel','referentiel')."</b></td>
<td><b>".get_string('timecreated','referentiel')."</b></td><td><b>".get_string('course')."</b></td><td><b>".get_string('referentielid','referentiel')."</b></td>
<td><b>".get_string('visible','referentiel')."</b></td>\n";
                $expout .= "</tr>\n<tr>\n";
                $expout .= " <td> $id</td>\n";
                $expout .= " <td> $name</td>\n";
                $expout .= " <td> $description</td>\n";
                $expout .= " <td> $domainlabel</td>\n";
                $expout .= " <td> $skilllabel</td>\n";
                $expout .= " <td> $itemlabel</td>\n";
                $expout .= " <td>".date("Y-m-d H:i:s",$timecreated)."</td>\n";
                $expout .= " <td> $course</td>\n";
                $expout .= " <td> $referentielid</td>\n";
                $expout .= " <td> $visible</td>\n";
                $expout .= "</tr>\n</table><br />";
            }
            // CERTIFICATS
            if (empty($this->rreferentiel) && (!empty($this->ireferentiel->referentielid) && ($this->ireferentiel->referentielid>0))){
                $this->rreferentiel = referentiel_get_referentiel_referentiel($this->ireferentiel->referentielid);
            }
            if (!empty($this->rreferentiel)){
                $expout .= $this->write_referentiel($this->rreferentiel);
            
                if (!$this->records_certificats){
                    $this->records_certificats = referentiel_get_certificats($this->rreferentiel->id);
                }
                // print_r($this->records_certificats);
                    	
                if ($this->records_certificats){
                    foreach ($this->records_certificats as $record){
                        $expout .= $this->write_certificat( $record );
                    }
                }
            }
        }

        return $expout;
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
                    		$s.='<th>'.$t_competence[$i].'</th>';
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
                    		$s.='<td>'.referentiel_pourcentage($t_certif_competence_poids[$i], $t_competence_coeff[$i]).'%</td>';
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

} // fin de la classe

// studentS : export des students
class eformat_html extends eformat_default {

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

  		// get css bit
		$css_lines = file( "$CFG->dirroot/mod/referentiel/format/xhtml/xhtml.css" );
		$css = implode( ' ',$css_lines ); 
		$xp =  "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n";
		$xp .= "  \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
		$xp .= "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n";
  		$xp .= "<head>\n";
  		$xp .= "<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\" />\n";
  		$xp .= "<title>Moodle Referentiel :: Certificats XHTML Export</title>\n";
  		$xp .= $css;
  		$xp .= "</head>\n";
		$xp .= "<body>\n";
		$xp .= $content;
		$xp .= "</body>\n";
		$xp .= "</html>\n";

  		return $xp;
	}

	function export_file_extension() {
  		return "_h.html";
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


	function write_etablissement( $record ) {
        // initial string;
        $expout = "";
        // add comment
        // $expout .= "\n\n<!-- etablissement: $record->id  -->\n";
		if ($record){
    		$expout .= "<table class=\"referentiel\">\n";
                    // $expout .= "<h4>".get_string('etablissement','referentiel')."</h4>\n";
                    $expout .= "<tr>\n";
                    $id = trim( $record->id );
                    $idnumber = trim( $record->idnumber);
                    $name = trim( $record->name);
                    $address = trim( $record->address);
                    $logo = trim( $record->logo);
                    
                    $expout .= " <td><b>".get_string('id','referentiel')."</b></td><td> $id</td>\n";
                    $expout .= " <td><b>".get_string('idnumber','referentiel')."</b></td><td> $idnumber</td>\n";
                    $expout .= " <td><b>".get_string('name','referentiel')."</b></td><td> $name</td>\n";                    
                    $expout .= " <td><b>".get_string('address','referentiel')."</b></td><td> $address</td>\n";
//                    $expout .= " <td><b>".get_string('logo','referentiel')."</b></td><td> $logo</td>\n";                    
                    $expout .= " </tr>\n";
                    $expout .= "</table>\n\n";
        }
        return $expout;
    }


	
	function write_student( $record ) {
        // initial string;
        $expout = "";
        // add comment
        // $expout .= "\n\n<!-- student: $record->id  -->\n";
		if ($record){
	    	// add header
   		// $expout .= "<h4>".get_string('student','referentiel')."</h4>\n";
                    $expout .= "<tr>\n";		// 
                    
                    $id = trim( $record->id );
                    $userid = trim( $record->userid );
      $ref_etablissement = trim( $record->ref_etablissement);
                    $num_student = trim( $record->num_student);
                    $ddn_student = trim( $record->ddn_student);
                    $lieu_naissance = trim( $record->lieu_naissance);
                    $departement_naissance = trim( $record->departement_naissance);
                    $adresse_student = trim( $record->adresse_student);                    
                    
                    $expout .= " <td> $id</td>\n";
                    $expout .= " <td> $userid</td>\n";	
                    $expout .= " <td> ".referentiel_get_user_info($record->userid)."</td>\n";
                    $expout .= " <td> $num_student</td>\n";
                    $expout .= " <td> $ddn_student</td>\n";
                    $expout .= " <td> $lieu_naissance</td>\n";
                    $expout .= " <td> $departement_naissance</td>\n";                    
                    $expout .= " <td> $adresse_student</td>\n";
                    $expout .= " <td> $ref_etablissement</td>\n";
                    /*
                    // Etablissement
                    $record_etablissement=referentiel_get_etablissement($record->ref_etablissement);
	    	if ($record_etablissement){
                    	$expout .= $this->write_etablissement( $record_etablissement );
                    }
                    */
		    $expout .= " </tr>\n";//                    
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
	    $id = $this->ireferentiel->id;

    	// add comment and div tags
//    	$expout .= "<!-- students :  $this->ireferentiel->id  name: $this->ireferentiel->name -->\n";
    	// add header
    	$expout .= "<h2>".get_string('student','referentiel')."</h2>\n";
		// 
    	$expout .= "<table class=\"referentiel\">\n";

		$expout .= "<tr>\n";
		// 
		if ($this->ireferentiel){
                    $id = $this->ireferentiel->id;
                    $name = trim($this->ireferentiel->name);
                    $description = trim($this->ireferentiel->description);
                    $domainlabel = trim($this->ireferentiel->domainlabel);
                    $skilllabel = trim($this->ireferentiel->skilllabel);
                    $itemlabel = trim($this->ireferentiel->itemlabel);
                    $timecreated = $this->ireferentiel->timecreated;
                    $course = $this->ireferentiel->course;
                    $referentielid = $this->ireferentiel->referentielid;
                    $visible = $this->ireferentiel->visible;
/*
                    $expout .= " <td><b>".get_string('id','referentiel')."</b></td><td> $id</td>\n";
                    $expout .= " <td><b>".get_string('name','referentiel')."</b></td><td> $name</td>\n";
                    $expout .= " <td><b>".get_string('description','referentiel')."</b></td><td> $description</td>\n";   
                    $expout .= " <td><b>".get_string('domainlabel','referentiel')."</b></td><td> $domainlabel</td>\n";
                    $expout .= " <td><b>".get_string('skilllabel','referentiel')."</b></td><td> $skilllabel</td>\n";
                    $expout .= " <td><b>".get_string('itemlabel','referentiel')."</b></td><td> $itemlabel</td>\n";                    
                    $expout .= " <td><b>".get_string('timecreated','referentiel')."</b></td><td>".date("Y-m-d H:i:s",$timecreated)."</td>\n";
                    $expout .= " <td><b>".get_string('course')."</b></td><td> $course</td>\n";
                    $expout .= " <td><b>".get_string('referentielid','referentiel')."</b></td><td> $referentielid</td>\n";
                    $expout .= " <td><b>".get_string('visible','referentiel')."</b></td><td> $visible</td>\n";
*/                    
                    // studentS
                    if (isset($this->ireferentiel->course) && ($this->ireferentiel->course>0)){
                    	// studentS
                    	$records_all_students = referentiel_get_students_course($this->ireferentiel->course);
                    	if ($records_all_students){
                          $expout .= "<table class=\"referentiel\">\n";		
    	 	    // $expout .= "<h4>".get_string('student','referentiel')."</h4>\n";
                          $expout .= "<tr>\n";		//                           
                          $expout .= "<th>".get_string('id','referentiel')."</th>\n";
                    $expout .= " <th>".get_string('userid','referentiel')."</th>\n";	
                          $expout .= " <th>".get_string('nom_prenom','referentiel')."</th>\n";
                          $expout .= " <th>".get_string('num_student','referentiel')."</th>\n";
                    $expout .= " <th>".get_string('ddn_student','referentiel')."</th>\n";
                    $expout .= " <th>".get_string('lieu_naissance','referentiel')."</th>\n";
                    $expout .= " <th>".get_string('departement_naissance','referentiel')."</th>\n";                    
                    $expout .= " <th>".get_string('adresse_student','referentiel')."</th>\n";
                          $expout .= " <th>".get_string('ref_etablissement','referentiel')."</th>\n";
    		    $expout .= " </tr>\n";
                    		
                    	
                    		  foreach ($records_all_students as $record){
                                          // USER
                                          if (isset($record->userid) && ($record->userid>0)){
                                        	  $record_student = referentiel_get_student_user($record->userid);
		                        	if ($record_student){
                                        		  $expout .= $this->write_student( $record_student );
                                        	  }
                                          }
                    		  }
                    		  $expout .= "</table>\n\n";		//
                    	}
                    }
    }
	  $expout .= " </tr>\n";
		$expout .= "</table>\n";
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
                                                                taskS
***********************************************************************
**********************************************************************/


// taskS : export des tasks
class tformat_html extends tformat_default {

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

  		// get css bit
		$css_lines = file( "$CFG->dirroot/mod/referentiel/format/html/html.css" );
		$css = implode( ' ',$css_lines ); 
		$xp =  "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n";
		$xp .= "  \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
		$xp .= "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n";
  		$xp .= "<head>\n";
  		$xp .= "<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\" />\n";
  		$xp .= "<title>Moodle Referentiel :: TASK XHTML Export</title>\n";
  		$xp .= $css;
  		$xp .= "</head>\n";
		$xp .= "<body>\n";
		$xp .= $content;
		$xp .= "</body>\n";
		$xp .= "</html>\n";

  		return $xp;
	}

	function export_file_extension() {
  		return "_h.html";
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

 
	 /**
     * Turns consigne into an html segment
     * @param consigne object
     * @return string html 
     */

    function write_consigne( $consigne ) {
    global $CFG;
       // initial string;
        $expout = "";
        // add comment

		if ($consigne){
            $id = $consigne->id ;
            $type = trim($consigne->type);
            $description = trim($consigne->description);
            $url = $consigne->url;
            if (preg_match("/http/",$url)){
                $url='<a href="'.$url.'" target="_blank">'.$url.'</a>';
            }
            $taskid = $consigne->taskid;
            $expout .= "   <tr>\n";
            $expout .= "     <td class=\"item\"> $type</td>\n";
            $expout .= "     <td class=\"item\"> $description</td>\n";
            $expout .= "     <td class=\"item\"> $url</td>\n";
            $expout .= "     <td class=\"item\"> $taskid</td>\n";
            $expout .= "   </tr>\n";
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
		if ($task){
            // DEBUG
            // echo "<br />\n";
            // print_r($task);
            $id = $task->id;
            $type = trim($task->type);
            $description = trim($task->description);
            $competences_task = trim($task->competences_task);
            $criteres_evaluation = trim($task->criteres_evaluation);
            $instanceid = $task->instanceid;
            $referentielid = $task->referentielid;
            $course = $task->course;
            $auteurid = trim($task->auteurid);
            $timecreated = $task->timecreated;
            $timemodified = $task->timemodified;
            $timestart = $task->timestart;
            $timeend = $task->timeend;

	        $expout .= "\n\n<!-- task: $task->id  -->\n";

            $expout .= "<table class='competence'>\n";
	        $expout .= "<tr>\n";
            $expout .= "    <th class=\"competence\"><b>".get_string('id','referentiel')."</b></th>\n";
   	        $expout .= "    <th class=\"competence\"><b>".get_string('type','referentiel')."</b></th>\n";
       	    $expout .= "    <th class=\"competence\"><b>".get_string('description','referentiel')."</b></th>\n";
           	$expout .= "    <th class=\"competence\"><b>".get_string('competences','referentiel')."</b></th>\n";
            $expout .= "    <th class=\"competence\"><b>".get_string('criteres_evaluation','referentiel')."</b></th>\n";
            // $expout .= "    <th class=\"competence\"><b>".get_string('instance','referentiel')."</b></th>\n";
            // $expout .= "    <th class=\"competence\"><b>".get_string('referentiel','referentiel')."</b></th>\n";
            $expout .= "    <th class=\"competence\"><b>".get_string('course')."</b></th>\n";
            $expout .= "    <th class=\"competence\"><b>".get_string('auteur','referentiel')."</b></th>\n";
            $expout .= "    <th class=\"competence\"><b>".get_string('timecreated','referentiel')."</b></th>\n";
            $expout .= "    <th class=\"competence\"><b>".get_string('timemodified','referentiel')."</b></th>\n";
            $expout .= "    <th class=\"competence\"><b>".get_string('timestart','referentiel')."</b></th>\n";
            $expout .= "    <th class=\"competence\"><b>".get_string('timeend','referentiel')."</b></th>\n";
            $expout .= "</tr>\n";
           	$expout .= "<tr>\n";
            $expout .= "<td class=\"competence\"> $id</td>\n";
            $expout .= "<td class=\"competence\"> $type</td>\n";
            $expout .= "<td class=\"competence\"> $description</td>\n";
            $expout .= "<td class=\"competence\"> $competences_task</td>\n";
            $expout .= "<td class=\"competence\"> $criteres_evaluation</td>\n";
/*
            $expout .= "<td class=\"competence\"> $instanceid</td>\n";
            $expout .= "<td class=\"competence\"> $referentielid</td>\n";
*/
            $expout .= "<td class=\"competence\">".referentiel_get_course_link($course,true)."</td>\n";
            $expout .= "<td class=\"competence\">".referentiel_get_user_info($auteurid)."</td>\n";
            $expout .= "<td class=\"competence\">".date("Y-m-d H:i:s",$timecreated)."</td>\n";
            $expout .= "<td class=\"competence\">".date("Y-m-d H:i:s",$timemodified)."</td>\n";
            $expout .= "<td class=\"competence\">".date("Y-m-d H:i:s",$timestart)."</td>\n";
            $expout .= "<td class=\"competence\">".date("Y-m-d H:i:s",$timeend)."</td>\n";
            $expout .= "</tr>\n";
            $expout .= "</table>\n";
                    
            // consigneS
            $records_consignes = referentiel_get_consignes($task->id);
                    
            if ($records_consignes){
                    	// DEBUG
                    	// echo "<br/>DEBUG :: ITEMS <br />\n";
                    	// print_r($records_consignes);
                       	$expout .= "<table class='item'>\n";
	       	 	$expout .= "   <tr>\n";
                    	$expout .= "     <th class=\"item\"><b>".get_string('type','referentiel')."</b></th>\n";   
           		$expout .= "     <th class=\"item\"><b>".get_string('description','referentiel')."</b></th>\n";
	    	    $expout .= "     <th class=\"item\"><b>".get_string('url','referentiel')."</b></th>\n";
                           $expout .= "     <th class=\"item\"><b>".get_string('task','referentiel')."</b></th>\n";
                    	$expout .= "   </tr>\n"; 
                    	foreach ($records_consignes as $record_d){
                    		$expout .= $this->write_consigne( $record_d );
                    	}
                    	$expout .= "</table>\n";
            }
		}	

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
		if ($item){
                    // DEBUG
                    // echo "<br />\n";
                    // print_r($item);
                    $code = $item->code;
                    $description = $item->description;
                    $referentielid = $item->referentielid;
                    $skillid = $item->skillid;
                    $type = $item->type;
                    $weight = $item->weight;
                    $sortorder = $item->sortorder;

                    $expout .= "   <tr>\n";
                    $expout .= "     <td class=\"item\"> ".stripslashes($code)."</td>\n";   
                    $expout .= "     <td class=\"item\"> ".stripslashes($description)."</td>\n";
                    // $expout .= "   <td class=\"item\"> $referentielid</td>\n";
                    // $expout .= "   <td class=\"item\"> $skillid</td>\n";
                    $expout .= "     <td class=\"item\"> ".stripslashes($type)."</td>\n";
                    $expout .= "     <td class=\"item\"> $weight</td>\n";
                    $expout .= "     <td class=\"item\"> $sortorder</td>\n";                    
                    $expout .= "   </tr>\n";   
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
		
		if ($competence){
                    $code = $competence->code;
                    $description = $competence->description;
                    $domainid = $competence->domainid;
                    $sortorder = $competence->sortorder;
                    $nb_item_competences = $competence->nb_item_competences;
	    	
                       $expout .= "<table class='competence'>\n";
	        $expout .= "<tr>\n";
                    $expout .= "    <th class=\"competence\"><b>".get_string('code','referentiel')."</b></th>\n";   
   	        $expout .= "    <th class=\"competence\"><b>".get_string('description','referentiel')."</b></th>\n";
       	    // $expout .= "    <th class=\"competence\"><b>".get_string('domainid','referentiel')."</b></th>\n";
           	$expout .= "    <th class=\"competence\"><b>".get_string('sortorder','referentiel')."</b></th>\n";
                    $expout .= "    <th class=\"competence\"><b>".get_string('nb_item_competences','referentiel')."</b></th>\n";
                    $expout .= "</tr>\n";
                    
                    $expout .= "  <tr>\n";
                    $expout .= "    <td class=\"competence\"> ".stripslashes($code)."</td>\n";   
                    $expout .= "    <td class=\"competence\"> ".stripslashes($description)."</td>\n";
                    // $expout .= "  <td class=\"competence\"> $domainid</td>\n";
                    $expout .= "    <td class=\"competence\"> $sortorder</td>\n";
                    $expout .= "    <td class=\"competence\"> $nb_item_competences</td>\n";
                    $expout .= "  </tr>\n";
                    $expout .= "</table>\n";

                    // ITEM
                    $compteur_item=0;
                    $records_items = referentiel_get_item_competences($competence->id);
                    
                    if ($records_items){
                    	// DEBUG
                    	// echo "<br/>DEBUG :: ITEMS <br />\n";
                    	// print_r($records_items);
                       	$expout .= "<table class='item'>\n";
	       	 	$expout .= "   <tr>\n";
                    	$expout .= "     <th class=\"item\"><b>".get_string('code','referentiel')."</b></th>\n";   
           		$expout .= "     <th class=\"item\"><b>".get_string('description','referentiel')."</b></th>\n";
		        // $expout .= "     <th class=\"item\"><b>".get_string('referentielid','referentiel')."</b></th>\n";
    	   		// $expout .= "     <th class=\"item\"><b>".get_string('skillid','referentiel')."</b></th>\n";
	    	    $expout .= "     <th class=\"item\"><b>".get_string('type','referentiel')."</b></th>\n";
                           $expout .= "     <th class=\"item\"><b>".get_string('weight','referentiel')."</b></th>\n";
		        $expout .= "     <th class=\"item\"><b>".get_string('sortorder','referentiel')."</b></th>\n";
                    	$expout .= "   </tr>\n"; 

                    	foreach ($records_items as $record_i){
                    		// DEBUG
                    		// echo "<br/>DEBUG :: ITEM <br />\n";
                    		// print_r($record_i);
                    		$expout .= $this->write_item( $record_i );
                    	}
                    	$expout .= "</table>\n";
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
                    $description = $domaine->description;
                    $referentielid = $domaine->referentielid;
                    $sortorder = $domaine->sortorder;
                    $nb_competences = $domaine->nb_competences;

                    $expout .= "<br /><table class='domaine'>\n";
                    $expout .= "<tr>\n";                    
                    $expout .= "   <th class=\"domaine\"><b>".get_string('code','referentiel')."</b></th>\n";   
		    $expout .= "   <th class=\"domaine\"><b>".get_string('description','referentiel')."</b></th>\n";
        	// $expout .= "   <th class=\"domaine\"><b>".get_string('referentielid','referentiel')."</b></th>\n";
		    $expout .= "   <th class=\"domaine\"><b>".get_string('sortorder','referentiel')."</b></th>\n";
	        $expout .= "   <th class=\"domaine\"><b>".get_string('nb_competences','referentiel')."</b></th>\n";
                    $expout .= "</tr>\n";	
                    
                    $expout .= "<tr>\n";                    
                    $expout .= "   <td class=\"domaine\"> ".stripslashes($code)."</td>\n";   
                    $expout .= "   <td class=\"domaine\"> ".stripslashes($description)."</td>\n";
                    // $expout .= "   </td><td class=\"domaine\"> $referentielid</td>\n";
                    $expout .= "   <td class=\"domaine\"> $sortorder</td>\n";
                    $expout .= "   <td class=\"domaine\"> $nb_competences</td>\n";
                    $expout .= "</tr>\n";
                    $expout .= "</table>\n";
                    
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
		global $USER;
        // initial string;
        $expout = "";
	    $id = $referentiel->id;

    	// add comment and div tags
    	$expout .= "<!-- date: ".date("Y/m/d")." referentiel:  $referentiel->id  name: ".stripslashes($referentiel->name)." -->\n";
    	// add header
    	$expout .= "<h3>".stripslashes($referentiel->name)."</h3>\n";
		
		if ($referentiel){
            $name = $referentiel->name;
            $code = $referentiel->code;
            $description = $referentiel->description;
            $url = $referentiel->url;
            $certificatethreshold = $referentiel->certificatethreshold;
            $timemodified = $referentiel->timemodified;
            $nb_domaines = $referentiel->nb_domaines;
            $liste_codes_competence = $referentiel->liste_codes_competence;
                    $liste_empreintes_competence = $referentiel->liste_empreintes_competence;
                    $local = $referentiel->local;
                    $logo = $referentiel->logo;
                    
	    $expout .= "<table class=\"referentiel\">\n";
                    $expout .= "<tr>\n";
                    $expout .= " <th class=\"referentiel\"><b>".get_string('name','referentiel')."</b></th>\n";
                    $expout .= " <th class=\"referentiel\"><b>".get_string('code','referentiel')."</b></th>\n";   
      $expout .= " <th class=\"referentiel\" colspan=\"4\"><b>".get_string('description','referentiel')."</b></th>\n";
                    $expout .= " </tr>\n";
                    $expout .= "<tr>\n";
                    $expout .= " <td class=\"referentiel\"> ".stripslashes($name)."</td>\n";
                    $expout .= " <td class=\"referentiel\"> ".stripslashes($code)."</td>\n";   
      $expout .= " <td class=\"referentiel\" colspan=\"4\"> ".stripslashes($description)."</td>\n";
                    $expout .= " </tr>\n";                    
                    $expout .= "<tr>\n";
                    $expout .= "<tr>\n";
      $expout .= " <th class=\"referentiel\"><b>".get_string('url','referentiel')."</b></th>\n";
      $expout .= " <th class=\"referentiel\"><b>".get_string('liste_codes_competence','referentiel')."</b></th>\n";
      $expout .= " <th class=\"referentiel\"><b>".get_string('liste_empreintes_competence','referentiel')."</b></th>\n";
      $expout .= " <th class=\"referentiel\"><b>".get_string('certificatethreshold','referentiel')."</b></th>\n";
      $expout .= " <th class=\"referentiel\"><b>".get_string('nb_domaines','referentiel')."</b></th>\n";
                    // $expout .= " <td class\"referentiel\"><b>".get_string('local','referentiel')."</b></td>\n";
      $expout .= " <th class=\"referentiel\"><b>".get_string('logo','referentiel')."</b></th>\n";

      $expout .= "</tr>\n";                    
      $expout .= " <td class=\"referentiel\"> <a href=\"".$url."\" title=\"".$url."\" target=\"_blank\">".$url."</a></td>\n";
      $expout .= " <td class=\"referentiel\"> ".$this->write_ligne($liste_codes_competence,"/",60)."</td>\n";
      $expout .= " <td class=\"referentiel\"> ".$this->write_ligne($liste_empreintes_competence,"/",60)."</td>\n";
      $expout .= " <td class=\"referentiel\"> $certificatethreshold</td>\n";
      $expout .= " <td class=\"referentiel\"> $nb_domaines</td>\n";
      // $expout .= " <td class\"referentiel\"> $local</td>\n";
                    $expout .= " <td class\"referentiel\">&nbsp; $logo</td>\n";
                    $expout .= "</tr>\n";
                    $expout .= "</table>\n\n\n";
                    
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
            $name = trim($this->rreferentiel->name);
            $code =  trim($this->rreferentiel->code);
			$referentielauthormail = trim($this->rreferentiel->referentielauthormail);
			$cle_referentiel = trim($this->rreferentiel->cle_referentiel);
			$password = trim($this->rreferentiel->password);
            $description = trim($this->rreferentiel->description);
            $url =  trim($this->rreferentiel->url);
			$certificatethreshold =trim($this->rreferentiel->certificatethreshold);
			$timemodified = trim($this->rreferentiel->timemodified);
			$nb_domaines = trim($this->rreferentiel->nb_domaines);
			$liste_codes_competence = trim($this->rreferentiel->liste_codes_competence);
			$liste_empreintes_competence = trim($this->rreferentiel->liste_empreintes_competence);
			$local =  trim($this->rreferentiel->local);
			$logo =  trim($this->rreferentiel->logo);

	    	$expout .= "<!-- referentiel :  ".$id."  name: ".$name." -->\n";
            $expout .= "<h3>".get_string('referentiel','referentiel')."</h3>\n";
            $expout .= "<table class=\"referentiel\">\n";
            $expout .= "<tr>\n";
            $expout .= " <th class=\"referentiel\"><b>".get_string('name','referentiel')."</b></th>\n";
            $expout .= " <th class=\"referentiel\"><b>".get_string('code','referentiel')."</b></th>\n";
            $expout .= " <th class=\"referentiel\" colspan=\"4\"><b>".get_string('description','referentiel')."</b></th>\n";
            $expout .= " <th class=\"referentiel\"><b>".get_string('liste_codes_competence','referentiel')."</b></th>\n";

            $expout .= " </tr>\n";
            $expout .= "<tr>\n";
            $expout .= " <td class=\"referentiel\"> ".stripslashes($name)."</td>\n";
            $expout .= " <td class=\"referentiel\"> ".stripslashes($code)."</td>\n";
            $expout .= " <td class=\"referentiel\" colspan=\"4\"> ".stripslashes($description)."</td>\n";
            $expout .= " <td class=\"referentiel\"> ".$this->write_ligne($liste_codes_competence,"/",60)."</td>\n";
            $expout .= "</tr>\n";
            $expout .= "</table>\n";
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

    	// add comment and div tags
		$expout .= "<h1>".get_string('tasks','referentiel')."</h1>\n";
 		// 
		if ($this->rreferentiel){
            $expout .= $this->write_referentiel_reduit();
		}

		if ($this->ireferentiel){
            $id = $this->ireferentiel->id;
            $name = trim($this->ireferentiel->name);
                    $description = trim($this->ireferentiel->description);
                    $domainlabel = trim($this->ireferentiel->domainlabel);
                    $skilllabel = trim($this->ireferentiel->skilllabel);
                    $itemlabel = trim($this->ireferentiel->itemlabel);
                    $timecreated = $this->ireferentiel->timecreated;
                    $course = $this->ireferentiel->course;
                    $referentielid = $this->ireferentiel->referentielid;
                    $visible = $this->ireferentiel->visible;

	    	$expout .= "<!-- instance :  ".$this->ireferentiel->id."  name: ".$this->ireferentiel->name." -->\n";
    		$expout .= "<h3>".get_string('instance','referentiel')."</h3>\n";
                    $expout .= "<table class=\"referentiel\">\n";
                    $expout .= "<tr>\n";
                    $expout .= " <th  class=\"referentiel\"><b>".get_string('id','referentiel')."</b></th>\n";
                    $expout .= " <th  class=\"referentiel\"><b>".get_string('name','referentiel')."</b></th>\n";
                    $expout .= " <th  class=\"referentiel\"><b>".get_string('description','referentiel')."</b></th>\n";   
                    $expout .= " <th  class=\"referentiel\"><b>".get_string('domainlabel','referentiel')."</b></th>\n";
                    $expout .= " <th  class=\"referentiel\"><b>".get_string('skilllabel','referentiel')."</b></th>\n";
                    $expout .= " <th  class=\"referentiel\"><b>".get_string('itemlabel','referentiel')."</b></th>\n";                    
                    $expout .= " <th  class=\"referentiel\"><b>".get_string('timecreated','referentiel')."</b></th>\n";
                    $expout .= " <th  class=\"referentiel\"><b>".get_string('course')."</b></th>\n";
                    $expout .= " <th  class=\"referentiel\"><b>".get_string('referentielid','referentiel')."</b></th>\n";
                    $expout .= " <th  class=\"referentiel\"><b>".get_string('visible','referentiel')."</b></th>\n";
                    $expout .= "</tr>\n";
                    $expout .= "<tr>\n";
                    $expout .= " <td  class=\"referentiel\"> $id</td>\n";
                    $expout .= " <td  class=\"referentiel\"> $name</td>\n";
                    $expout .= " <td  class=\"referentiel\"> $description</td>\n";   
                    $expout .= " <td  class=\"referentiel\"> $domainlabel</td>\n";
                    $expout .= " <td  class=\"referentiel\"> $skilllabel</td>\n";
                    $expout .= " <td  class=\"referentiel\"> $itemlabel</td>\n";                    
                    $expout .= " <td  class=\"referentiel\">".date("Y-m-d H:i:s",$timecreated)."</td>\n";
                    $expout .= " <td  class=\"referentiel\"> $course</td>\n";
                    $expout .= " <td  class=\"referentiel\"> $referentielid</td>\n";
                    $expout .= " <td  class=\"referentiel\"> $visible</td>\n";
                    $expout .= "</tr>\n";
            $expout .= "</table>\n";
                    // taskS
            if (isset($this->ireferentiel->id) && ($this->ireferentiel->id>0)){
                $records_tasks = referentiel_get_tasks_instance($this->ireferentiel->id);
                if ($records_tasks){
                    $expout .= "<h3>".get_string('tasks','referentiel')."</h3>\n";
                    foreach ($records_tasks as $record_a){
                        $expout .= $this->write_task( $record_a );
                    }
                }
            }
        }
        return $expout;
    }
}


// //////////////////////////////////////////////////////////////////////////////////////////////////////


?>
