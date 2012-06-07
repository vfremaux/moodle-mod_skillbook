<?php 

/**********************************************************************
***********************************************************************
									ACTIVITES
***********************************************************************
**********************************************************************/


// ACTIVITES : export des activites
class aformat_xhtml extends aformat_default {

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
        $expout .= "\n\n<!-- item: $item->id  -->
<div class='item'>\n";
		// 
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
            $expout .= "   <ul>\n";
			$expout .= "     <li><b>".get_string('code','referentiel')."</b> : $code</li>\n";   
            $expout .= "     <li><b>".get_string('description','referentiel')."</b> : $description</li>\n";
            $expout .= "     <li><b>".get_string('referentielid','referentiel')."</b> : $referentielid</li>\n";
            $expout .= "     <li><b>".get_string('skillid','referentiel')."</b> : $skillid</li>\n";
            $expout .= "     <li><b>".get_string('type','referentiel')."</b> : $type</li>\n";
            $expout .= "     <li><b>".get_string('weight','referentiel')."</b> : $weight</li>\n";
            $expout .= "     <li><b>".get_string('sortorder','referentiel')."</b> : $sortorder</li>\n";			
			$expout .= "   </ul>\n";   
        }
		$expout .= "</div>\n";
        return $expout;
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
        $expout .= "\n\n<!-- document: $document->id  -->
<div class='item'>\n";
		if ($document){
			$id = $document->id ;		
            $type = trim($document->type);
            $description = trim($document->description);
			$url = $document->url;
            $activityid = $document->activityid;
            $expout .= "   <ul>\n";
            $expout .= "     <li><b>".get_string('type','referentiel')."</b> : $type</li>\n";   
            $expout .= "     <li><b>".get_string('description','referentiel')."</b> : $description</li>\n";
            $expout .= "     <li><b>".get_string('url','referentiel')."</b> : $url</li>\n";
            $expout .= "     <li><b>".get_string('activityid','referentiel')."</b> : $activityid</li>\n";
			$expout .= "   </ul>\n";   
        }
		$expout .= "</div>\n";
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
<div class='competence'>\n";
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
			
            $expout .= "<ul>\n";
            $expout .= "<li><b>".get_string('id','referentiel')."</b> : $id</li>\n";
			$expout .= "<li><b>".get_string('type_activite','referentiel')."</b> : $type_activite</li>\n";
            $expout .= "<li><b>".get_string('description','referentiel')."</b> : $description</li>\n";
            $expout .= "<li><b>".get_string('comptencies','referentiel')."</b> : $comptencies</li>\n";
            $expout .= "<li><b>".get_string('comment','referentiel')."</b> : $comment</li>\n";
            $expout .= "<li><b>".get_string('instanceid','referentiel')."</b> : $instanceid</li>\n";
            $expout .= "<li><b>".get_string('referentielid','referentiel')."</b> : $referentielid</li>\n";
            $expout .= "<li><b>".get_string('course','referentiel')."</b> : $course</li>\n";
            $expout .= "<li><b>".get_string('userid','referentiel')."</b> : $userid</li>\n";
            $expout .= "<li><b>".get_string('teacherid','referentiel')."</b> : $teacherid</li>\n";
            $expout .= "<li><b>".get_string('timecreated','referentiel')."</b> : $timecreated</li>\n";
            $expout .= "<li><b>".get_string('timemodified','referentiel')."</b> : $timemodified</li>\n";
            $expout .= "<li><b>".get_string('approved','referentiel')."</b> : $approved</li>\n";
			
			// DOCUMENTS
			$records_documents = referentiel_get_documents($activite->id);
			
			if ($records_documents){
				foreach ($records_documents as $record_d){
					$expout .= $this->write_document( $record_d );
				}
			}
		}	
		$expout .= "</div>\n";
        return $expout;
    }

	
	 /**
     * Turns referentiel instance into an xml segment
     * @param referentiel instanceobject
     * @return string xml segment
     */

    function write_liste_activites( $referentiel_instance ) {
    	global $CFG;
        // initial string;
        $expout = "";
	    $id = $referentiel_instance->id;

    	// add comment and div tags
    	$expout .= "<!-- certification :  $referentiel_instance->id  name: $referentiel_instance->name -->\n";
    	$expout .= "<div class=\"referentiel\">\n";

    	// add header
    	$expout .= "<h3>$referentiel_instance->name</h3>\n";
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
			$expout .= " <li><b>".get_string('description','referentiel')."</b> : description</li>\n";   
            $expout .= " <li><b>".get_string('domainlabel','referentiel')."</b> : $domainlabel</li>\n";
            $expout .= " <li><b>".get_string('skilllabel','referentiel')."</b> : $skilllabel</li>\n";
            $expout .= " <li><b>".get_string('itemlabel','referentiel')."</b> : $itemlabel</li>\n";			
            $expout .= " <li><b>".get_string('timecreated','referentiel')."</b> : $timecreated</li>\n";
            $expout .= " <li><b>".get_string('course')."</b> : $course</li>\n";
            $expout .= " <li><b>".get_string('referentielid','referentiel')."</b> : $referentielid</li>\n";
            $expout .= " <li><b>".get_string('visible','referentiel')."</b> : $visible</li>\n";
			
			// ACTIVITES
			if (isset($referentiel_instance->id) && ($referentiel_instance->id>0)){
				$records_activites = referentiel_get_activites_instance($referentiel_instance->id);
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


?>