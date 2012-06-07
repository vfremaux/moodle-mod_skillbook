<?php


// ACTIVITES : export des certificats
class cformat_xhtml extends cformat_default {

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
  		return ".html";
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
    		$expout .= "<div class=\"referentiel\">\n";
			$expout .= "<h4>".get_string('etablissement','referentiel')."</h4>\n";
			// 
			
			$expout .= "<ul>\n";		// 
			$id = trim( $record->id );
			$idnumber = trim( $record->idnumber);
			$name = trim( $record->name);
			$address = trim( $record->address);
			$logo = trim( $record->logo);
						
			$expout .= " <li><b>".get_string('id','referentiel')."</b> : $id</li>\n";
            $expout .= " <li><b>".get_string('idnumber','referentiel')."</b> : $idnumber</li>\n";
            $expout .= " <li><b>".get_string('name','referentiel')."</b> : $name</li>\n";			
            $expout .= " <li><b>".get_string('address','referentiel')."</b> : $address</li>\n";
            $expout .= " <li><b>".get_string('logo','referentiel')."</b> : $logo</li>\n";			
			$expout .= " </ul>\n";
			$expout .= "</div>\n\n";		//			
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
	    	// add header
			$expout .= "<div class=\"referentiel\">\n";
			
    		$expout .= "<h4>student</h4>\n";
			// 
			$expout .= "<ul>\n";		// 
			
			$id = trim( $record->id );
			$userid = trim( $record->userid );
            $ref_etablissement = trim( $record->ref_etablissement);
			$num_student = trim( $record->num_student);
			$ddn_student = trim( $record->ddn_student);
			$lieu_naissance = trim( $record->lieu_naissance);
			$departement_naissance = trim( $record->departement_naissance);
			$adresse_student = trim( $record->adresse_student);			

			$expout .= " <li><b>".get_string('id','referentiel')."</b> : $id</li>\n";
			$expout .= " <li><b>".get_string('userid','referentiel')."</b> : $userid</li>\n";	
			$expout .= " <li><b>".get_string('nom_prenom','referentiel')."</b> : ".referentiel_get_user_info($record->userid)."</li>\n";
			$expout .= " <li><b>".get_string('num_student','referentiel')."</b> : $num_student</li>\n";
            $expout .= " <li><b>".get_string('ddn_student','referentiel')."</b> : $ddn_student</li>\n";
            $expout .= " <li><b>".get_string('lieu_naissance','referentiel')."</b> : $lieu_naissance</li>\n";
            $expout .= " <li><b>".get_string('departement_naissance','referentiel')."</b> : $departement_naissance</li>\n";			
            $expout .= " <li><b>".get_string('adresse_student','referentiel')."</b> : $adresse_student</li>\n";
			$expout .= " <li><b>".get_string('ref_etablissement','referentiel')."</b> : $ref_etablissement</li>\n";
			// Etablissement
			$record_etablissement=referentiel_get_etablissement($record->ref_etablissement);
	    	if ($record_etablissement){
				$expout .= $this->write_etablissement( $record_etablissement );
			}
		    $expout .= " </ul>\n";
			$expout .= "</div>\n\n";		//			
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
    	// add comment and div tags
    	$expout .= "<!-- certification :  $record->id  -->\n";
    	$expout .= "<div class=\"referentiel\">\n";

    	// add header
		
    	$expout .= "<h3>".get_string('certificat','referentiel')."</h3>\n";
		// 
		$expout .= "<ul>\n";		// 
		if ($record){
			// DEBUG
			// echo "<br />DEBUG LIGNE 1021<br />\n";
			// print_r($referentiel_instance);
			$id = trim( $record->id );
            $comment = trim($record->comment);
            $competences_certificate =  trim($record->competences_certificat) ;
            $decision_jury = trim($record->decision_jury);
            $date_decision = userdate(trim($record->date_decision));
            $userid = trim( $record->userid);
            $teacherid = trim( $record->teacherid);
            $referentielid = trim( $record->referentielid);
			$verrou = trim( $record->verrou );
			$valide = trim( $record->valide );
			$evaluation = trim( $record->evaluation );
			
			$expout .= " <li><b>".get_string('id','referentiel')."</b> : $id</li>\n";
			// USER
			if (isset($record->userid) && ($record->userid>0)){
				$record_student = referentiel_get_student_user($record->userid);
		    	if ($record_student){
					$expout .= $this->write_student( $record_student );
				}
			}
			
            $expout .= "<li><b>".get_string('comment','referentiel')."</b> : $comment</li>\n";
            $expout .= "<li><b>".get_string('competences_certificat','referentiel')."</b> : $competences_certificat</li>\n";
            $expout .= "<li><b>".get_string('decision_jury','referentiel')."</b> : $decision_jury</li>\n";
            $expout .= "<li><b>".get_string('date_decision','referentiel')."</b> : $date_decision</li>\n";
            $expout .= "<li><b>".get_string('referentielid','referentiel')."</b> : $referentielid</li>\n";
            $expout .= "<li><b>".get_string('verrou','referentiel')."</b> : $verrou</li>\n";
            $expout .= "<li><b>".get_string('valide','referentiel')."</b> : $valide</li>\n";
            $expout .= "<li><b>".get_string('evaluation','referentiel')."</b> : $evaluation</li>\n";
		}
	    $expout .= " </ul>\n";
		$expout .= "</div>\n\n";
		
        return $expout;
    }



	
	 /**
     * Turns referentiel instance into an xml segment
     * @param referentiel instanceobject
     * @return string xml segment
     */

    function write_certification( $referentiel_instance ) {
    	global $CFG;
        // initial string;
        $expout = "";
	    $id = $referentiel_instance->id;

    	// add comment and div tags
    	$expout .= "<!-- certification :  $referentiel_instance->id  name: $referentiel_instance->name -->\n";
    	$expout .= "<div class=\"referentiel\">\n";

    	// add header
    	$expout .= "<h2>$referentiel_instance->name</h2>\n";
		// 
		$expout .= "<ul>\n";
		// 
		if ($referentiel_instance){
			$id = $referentiel_instance->id;
            $name = trim($referentiel_instance->name);
            $description = trim($referentiel_instance->description);
            $domainlabel = trim($referentiel_instance->domainlabel);
            $skilllabel = trim($referentiel_instance->skilllabel);
            $itemlabel = trim($referentiel_instance->itemlabel);
            $timecreated = $referentiel_instance->timecreated;
            $course = $referentiel_instance->course;
            $referentielid = $referentiel_instance->referentielid;
			$visible = $referentiel_instance->visible;

			$expout .= " <li><b>".get_string('id','referentiel')."</b> : $id</li>\n";
			$expout .= " <li><b>".get_string('name','referentiel')."</b> : $name</li>\n";
			$expout .= " <li><b>".get_string('description','referentiel')."</b> : $description</li>\n";   
            $expout .= " <li><b>".get_string('domainlabel','referentiel')."</b> : $domainlabel</li>\n";
            $expout .= " <li><b>".get_string('skilllabel','referentiel')."</b> : $skilllabel</li>\n";
            $expout .= " <li><b>".get_string('itemlabel','referentiel')."</b> : $itemlabel</li>\n";			
            $expout .= " <li><b>".get_string('timecreated','referentiel')."</b> : $timecreated</li>\n";
            $expout .= " <li><b>".get_string('course')."</b> : $course</li>\n";
            $expout .= " <li><b>".get_string('referentielid','referentiel')."</b> : $referentielid</li>\n";
            $expout .= " <li><b>".get_string('visible','referentiel')."</b> : $visible</li>\n";
			
			// CERTIFICATS
			if (isset($referentiel_instance->referentielid) && ($referentiel_instance->referentielid>0)){
				$records_certificats = referentiel_get_certificats($referentiel_instance->referentielid);
				// print_r($records_certificats);
				
		    	if ($records_certificats){
					foreach ($records_certificats as $record){
						$expout .= $this->write_certificat( $record );
					}
				}
			}
        }
	    $expout .= " </ul>\n";
		$expout .= "</div>\n";
        return $expout;
    }
}
?>