<?php
// inclus dans format/xhtml/format.php

/**********************************************************************
***********************************************************************
									TACHES
***********************************************************************
**********************************************************************/


// taches : export des taches
class tformat_xhtml extends tformat_default {

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
  		$xp .= "<title>Moodle Referentiel :: TASKS XHTML Export</title>\n";
  		$xp .= $css;
  		$xp .= "</head>\n";
		$xp .= "<body>\n";
		$xp .= $content;
		$xp .= "</body>\n";
		$xp .= "</html>\n";

  		return $xp;
	}

	function export_file_extension() {
  		return "_x.html";
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
     * Turns consigne into an xml segment
     * @param consigne object
     * @return string xml segment
     */

    function write_consigne( $consigne ) {
    global $CFG;
       // initial string;
        $expout = "";
        // add comment
        $expout .= "\n\n<!-- consigne: $consigne->id  -->
<div class='item'>\n";
		if ($consigne){
			$id = $consigne->id ;		
            $type = trim($consigne->type);
            $description = trim($consigne->description);
			$url = $consigne->url;
            $taskid = $consigne->taskid;
            $expout .= "   <ul>\n";
            $expout .= "     <li><b>".get_string('type','referentiel')."</b> : $type</li>\n";   
            $expout .= "     <li><b>".get_string('description','referentiel')."</b> : $description</li>\n";
            $expout .= "     <li><b>".get_string('url','referentiel')."</b> : $url</li>\n";
            $expout .= "     <li><b>".get_string('taskid','referentiel')."</b> : $taskid</li>\n";
			$expout .= "   </ul>\n";   
        }
		$expout .= "</div>\n";
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
        $expout .= "\n\n<!-- task: $task->id  -->
<div class='competence'>\n";
		// 
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
			
            $expout .= "<ul>\n";
            $expout .= "<li><b>".get_string('id','referentiel')."</b> : $id</li>\n";
			$expout .= "<li><b>".get_string('type','referentiel')."</b> : $type</li>\n";
            $expout .= "<li><b>".get_string('description','referentiel')."</b> : $description</li>\n";
            $expout .= "<li><b>".get_string('competences_task','referentiel')."</b> : $competences_task</li>\n";
            $expout .= "<li><b>".get_string('criteres_evaluation','referentiel')."</b> : $criteres_evaluation</li>\n";
            $expout .= "<li><b>".get_string('instanceid','referentiel')."</b> : $instanceid</li>\n";
            $expout .= "<li><b>".get_string('referentielid','referentiel')."</b> : $referentielid</li>\n";
            $expout .= "<li><b>".get_string('course','referentiel')."</b> : $course</li>\n";
            $expout .= "<li><b>".get_string('auteurid','referentiel')."</b> : $auteurid</li>\n";
            $expout .= "<li><b>".get_string('timecreated','referentiel')."</b> : $timecreated</li>\n";
            $expout .= "<li><b>".get_string('timemodified','referentiel')."</b> : $timemodified</li>\n";
            $expout .= "<li><b>".get_string('timestart','referentiel')."</b> : $timestart</li>\n";
            $expout .= "<li><b>".get_string('timeend','referentiel')."</b> : $timeend</li>\n";

			
			// consigneS
			$records_consignes = referentiel_get_consignes($task->id);
			
			if ($records_consignes){
				foreach ($records_consignes as $record_d){
					$expout .= $this->write_consigne( $record_d );
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

    function write_liste_taches( $referentiel_instance ) {
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
			
			// taches
			if (isset($referentiel_instance->id) && ($referentiel_instance->id>0)){
				$records_taches = referentiel_get_taches_instance($referentiel_instance->id);
		    	if ($records_taches){
					foreach ($records_taches as $record_a){
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
}



/* *****************************************************************************
?>