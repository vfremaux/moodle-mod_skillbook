<?php  // $Id: format.php,v 1.0 2008/05/01 00:00:00 jfruitet Exp $ 
/**
 * Base class for referentiel import and export formats.
 * recupere de question/format.php
 *
 * @author Martin Dougiamas, Howard Miller, and many others.
 *         {@link http://moodle.org}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package referentiel
 */
 

class rformat_default {

    var $displayerrors = true;
	var $rinstance = NULL; // instance de referentiel 
	var $rreferentiel = NULL; // referentiel_referentiel
    var $coursemodule = NULL;	
    var $course = NULL;
    var $filename = '';
    var $importerrors = 0;
    var $stoponerror = true;
	var $override = false;
	var $returnpage = "";
	var $new_referentiel_id = ""; // id d'un referentiel_referentiel

// functions to indicate import/export functionality
// override to return true if implemented

    function provide_import() {
      return false;
    }

    function provide_export() {
      return false;
    }

// Accessor methods

    /**
     * set the referentiel
     * @param object referentiel the referentiel object
     */
	function setIReferentiel( $referentiel ) {
        $this->rinstance = $referentiel;
    }

	    /**
     * set the referentiel
     * @param object referentiel the referentiel object
     */
	function setRReferentiel( $referentiel ) {
        $this->rreferentiel = $referentiel;
    }


    /**
     * set the referentiel
     * @param id referentiel the referentiel referentiel id
     */
	function setReferentielId( $id ) {
        $this->new_referentiel_id = $id;
    }

    /**
     * set the action 
     * @param string action 
     */
	function setAction( $action ) {
        $this->action = $action;
    }

    /**
     * set the course class variable
     * @param course object Moodle course variable
     */
    function setCourse( $course ) {
        $this->course = $course;
    }

    /**
     * set the course class variable
     * @param course object Moodle course variable
     */
    function setCoursemodule( $cm ) {
        $this->coursemodule = $cm;
    }

    /**
     * set the filename
     * @param string filename name of file to import/export
     */
    function setFilename( $filename ) {
        $this->filename = $filename;
    }

    /**
     * set returnpage
     * @param bool stoponerror stops database write if any errors reported
     */
    function setReturnpage( $returnpage ) {
        $this->returnpage = $returnpage;
    }

    /**
     * set stoponerror
     * @param bool stoponerror stops database write if any errors reported
     */
    function setStoponerror( $stoponerror ) {
        $this->stoponerror = $stoponerror;
    }
	
    /**
     * set override
     * @param bool override database write 
     */
    function setOverride( $override ) {
        $this->override = $override;
    }
	
    /**
     * set newinstance
     * @param bool newinstance database write 
     */
    function setNewinstance( $newinstance ){
        $this->newinstance = $newinstance;
    }
	

/*******************
 * EXPORT FUNCTIONS
 *******************/

    /** 
     * Provide export functionality for plugin referentiel types
     * Do not override
     * @param name referentiel name
     * @param referentiel object data to export 
     * @param extra mixed any addition format specific data needed
     * @return string the data to append to export or false if error (or unhandled)
     */
    function try_exporting( $name, $referentiel, $extra=null ) {

        // work out the name of format in use
        $formatname = substr( get_class( $this ), strlen( 'rformat_' ));
        $methodname = "export_to_$formatname";

		if (method_exists( $methodname )) {
			if ($data = $methodname( $referentiel, $this, $extra )) {
				return $data;
            }
        }
        return false;
    }

    /**
     * Return the files extension appropriate for this type
     * override if you don't want .txt
     * @return string file extension
     */
    function export_file_extension() {
        return ".txt";
    }

    /**
     * Do any pre-processing that may be required
     * @param boolean success
     */
    function exportpreprocess() {
        return true;
    }

    /**
     * Enable any processing to be done on the content
     * just prior to the file being saved
     * default is to do nothing
     * @param string output text
     * @param string processed output text
     */
    function presave_process( $content ) {
        return $content;
    }

    /**
     * Do the export
     * For most types this should not need to be overrided
     * @return boolean success
     */
    function exportprocess() {
        global $CFG;

        // create a directory for the exports (if not already existing)
        if (! $export_dir = make_upload_directory($this->get_export_dir())) {
              $this->error( get_string('cannotcreatepath', 'referentiel', $export_dir) );
        }
        $path = $CFG->dataroot.'/'.$this->get_export_dir();

        notify( get_string('exportingreferentiels', 'referentiel') );
        $count = 0;

        // results are first written into string (and then to a file)
        // so create/initialize the string here
        $expout = "";
        
        // export the item displaying message
        $count++;
        echo "<hr /><p><b>$count</b>. ".$this->rreferentiel->name."</p>";
        $expout .= $this->write_referentiel() . "\n";

        // continue path for following error checks
        $course = $this->course;
        $continuepath = "$CFG->wwwroot/mod/referentiel/export.php?d=".$this->rreferentiel->id; 

        // did we actually process anything
        if ($count==0) {
            $this->error( 'noreferentiels',"referentiel",$continuepath );
        }

        // final pre-process on exported data
        $expout = $this->presave_process( $expout );
       
        // write file
        $filepath = $path."/".$this->filename . $this->export_file_extension();
        if (!$fh=fopen($filepath,"w")) {
            $this->error( 'cannotopen',"referentiel",$continuepath,$filepath );
        }
		// DEBUG
		
		// echo "<br /> FORMAT : 218<br />$expout\n";
		
        if (!fwrite($fh, $expout, strlen($expout) )) {
            $this->error( 'cannotwrite',"referentiel",$continuepath,$filepath );
        }
        fclose($fh);
        return true;
    }

    /**
     * Do an post-processing that may be required
     * @return boolean success
     */
    function exportpostprocess() {
        return true;
    }

    /**
     * convert a single referentiel object into text output in the given
     * format.
     * This must be overriden
     * @param object referentiel referentiel object
     * @return mixed referentiel export text or null if not implemented
     */
    function write_referentiel() {
        // if not overidden, then this is an error.
        $formatnotimplemented = get_string( 'formatnotimplemented', 'referentiel' );
        echo "<p>$formatnotimplemented</p>";

        return NULL;
    }

    /**
     * get directory into which export is going 
     * @return string file path
     */
    function get_export_dir() {
		global $CFG;
        $dirname = get_string('exportfilename', 'referentiel');
        $path =  $this->course->id.'/'.$CFG->moddata.'/'.$dirname; 
        return $path;
    }



/***********************
 * IMPORTING FUNCTIONS
 ***********************/

    /**
     * Handle parsing error
     */
    function error( $message, $text='', $referentielname='' ) {
        $importerrorreferentiel = get_string('importerror', 'referentiel');

        echo "<div class=\"importerror\">\n";
        echo "<strong>$importerrorreferentiel $referentielname</strong>";
        if (!empty($text)) {
            $text = s($text);
            echo "<blockquote>$text</blockquote>\n";
        }
        echo "<strong>$message</strong>\n";
        echo "</div>";

         $this->importerrors++;
    }

    /** 
     * Import for referentieltype plugins
     * Do not override.
     * @param data mixed The segment of data containing the referentiel
     * @param referentiel object processed (so far) by standard import code if appropriate
     * @param extra mixed any additional format specific data that may be passed by the format
     * @return object referentiel object suitable for save_options() or false if cannot handle
     */
    function try_importing( $data, $referentiel=null, $extra=null ) {

        // work out what format we are using
        $formatname = substr( get_class( $this ), strlen('rformat_'));
        $methodname = "import_from_$formatname";

        // loop through installed referentieltypes checking for
        // function to handle this referentiel
        if (method_exists( $methodname)) {
        	if ($referentiel = $methodname( $data, $referentiel, $this, $extra )) {
            	return $referentiel;
            }
        }
        return false;   
    }

    /**
     * Perform any required pre-processing
     * @return boolean success
     */
    function importpreprocess() {
        return true;
    }

    /**
     * Process the file
     * This method should not normally be overidden
     * @return boolean success
     */
    function importprocess() {

       	// reset the timer in case file upload was slow
       	@set_time_limit();

       	// STAGE 1: Parse the file
       	notify( get_string('parsing', 'referentiel') );
         
		if (! $lines = $this->readdata($this->filename)) {
            notify( get_string('cannotread', 'referentiel') );
            return false;
        }
		$newly_imported_referentiel = new stdClass();
        if (! $newly_imported_referentiel = $this->lines_2_referentiel($lines)) {   // Extract the referentiel
            notify( get_string('noinfile', 'referentiel') );
            return false;
        }

        // STAGE 2: Write data to database
		// echo "<br />\n";
		// print_object($newly_imported_referentiel);
		// echo "<br />\n";
        // check for errors before we continue
        if ($this->stoponerror and ($this->importerrors>0)) {
            return false;
        }

		notify( get_string('importdone', 'referentiel') );
		

		return true;
    }

    /**
     * Return complete file within an array, one item per line
     * @param string filename name of file
     * @return mixed contents array or false on failure
     */
    function readdata($filename) {
        if (is_readable($filename)) {
            $filearray = file($filename);
            /// Check for Macintosh OS line returns (ie file on one line), and fix
            if (ereg("\r", $filearray[0]) AND !ereg("\n", $filearray[0])) {
                return explode("\r", $filearray[0]);
            } else {
                return $filearray;
            }
        }
        return false;
    }

    /**
     * Parses an array of lines into a referentiel, 
     * where is a newly_imported_referentiel object as defined by 
     * readimportedreferentiel().
     *
     * @param array lines array of lines from readdata
     * @return array referentiel object
     */
    function lines_2_referentiel($lines) {
	// 
        $tline = array();
		
        foreach ($lines as $line) {
            $line = trim($line);
			
            if (!empty($line)) {
                $tline[] = $line;
            }
        }
		// echo "<br />DEBUG 3 : format.php :: ligne 453 :: fonction lines_2_referentiel()<br />\n";
		// print_r($treferentiel);
		// echo "<br />\n";
		// exit;
        if (!empty($tline)) {  // conversion
            $imported_referentiel = $this->read_import_referentiel($tline);
        }
        return $imported_referentiel ;
    }


    /**
     * return an "empty" referentiel
     * Somewhere to specify referentiel parameters that are not handled
     * by import but are required db fields.
     * This should not be overridden.
     * @return object default referentiel
	*/      
    function defaultreferentiel() {
	// retourne un objet import_referentiel qui mime l'objet refrentiel
        $import_referentiel = new stdClass();
		$import_referentiel->name="";
		$import_referentiel->code="";
		$import_referentiel->description="";
		$import_referentiel->url="";
		$import_referentiel->certificatethreshold="";
    	$import_referentiel->timemodified = time();
		$import_referentiel->nb_tasks="";
		$import_referentiel->liste_codes_competence="";
		$import_referentiel->local=0;
    	$import_referentiel->id = 0;
        // this option in case the referentieltypes class wants
        // to know where the data came from
        $import_referentiel->export_process = true;
        $import_referentiel->import_process = true;
        return $import_referentiel;
    }

    function defaultdomaine() {
        // retourne un objet domaine
        $domaine = new stdClass();
    	$domaine->id = 0;
		$domaine->code="";
		$domaine->description="";
		$domaine->sortorder=0;
		$domaine->nb_competences=0;
		$domaine->referentielid=0;
        return $domaine;
    }

    function defaultcompetence() {
	// retourne un objet competence	
        $competence = new stdClass();
    	$competence->id = 0;
		$competence->code="";
		$competence->description="";
		$competence->sortorder=0;
		$competence->nb_item_competences=0;
		$competence->domainid=0;
        return $competence;
    }

    function defaultitem() {
	// retourne un objet item de competence
        $item = new stdClass();
    	$item->id = 0;
		$item->code="";
		$item->description="";
		$item->sortorder=0;
		$item->type="";
		$item->weight=0;
		$item->skillid=0;
		$item->referentielid=0;
        return $item;
    }

    /**
     * Given the data known to define a referentiel in 
     * this format, this function converts it into a referentiel 
     * object suitable for processing and insertion into Moodle.
     *
     * If your format does not use blank lines to delimit referentiels
     * (e.g. an XML format) you must override 'readreferentiels' too
     * @param $lines mixed data that represents referentiel
     * @return object referentiel object
     */
	function read_import_referentiel($lines) {

        $formatnotimplemented = get_string( 'formatnotimplemented', 'referentiel' );
        echo "<p>$formatnotimplemented</p>";

        return NULL;
    }

    /**
     * Override if any post-processing is required
     * @return boolean success
     */
    function importpostprocess() {
        return true;
    }

    /**
     * Import an image file encoded in base64 format
     * @param string path path (in course data) to store picture
     * @param string base64 encoded picture
     * @return string filename (nb. collisions are handled)
     */
    function importimagefile( $path, $base64 ) {
        global $CFG;

        // all this to get the destination directory
        // and filename!
        $fullpath = "{$CFG->dataroot}/{$this->course->id}/$path";
        $path_parts = pathinfo( $fullpath );
        $destination = $path_parts['dirname'];
        $file = clean_filename( $path_parts['basename'] );

        // check if path exists
        check_dir_exists($destination, true, true );

        // detect and fix any filename collision - get unique filename
        $newfiles = resolve_filename_collisions( $destination, array($file) );        
        $newfile = $newfiles[0];

        // convert and save file contents
        if (!$content = base64_decode( $base64 )) {
            return '';
        }
        $newfullpath = "$destination/$newfile";
        if (!$fh = fopen( $newfullpath, 'w' )) {
            return '';
        }
        if (!fwrite( $fh, $content )) {
            return '';
        }
        fclose( $fh );

        // return the (possibly) new filename
        $newfile = ereg_replace("{$CFG->dataroot}/{$this->course->id}/", '',$newfullpath);
        return $newfile;
    }
}

/**************************************************************************

ACTIVITES

***************************************************************************/
class aformat_default {

    var $displayerrors = true;
	var $ireferentiel = NULL; // instance
	var $rreferentiel = NULL; // referentiel
	var $referentielid = 0;
	var $coursemodule = NULL;	
    var $course = NULL;
    var $filename = '';
    var $importerrors = 0;
    var $stoponerror = true;

// functions to indicate import/export functionality
// override to return true if implemented

    function provide_import() {
      return false;
    }

    function provide_export() {
      return false;
    }

// Accessor methods
    /**
     * set the referentiel
     * @param object referentiel the referentiel instance object
     */
	function setIReferentiel( $referentiel_instance ) {
        $this->ireferentiel = $referentiel_instance;
    }

	    /**
     * set the referentiel
     * @param object referentiel the referentiel object
     */
	function setRefInstance( $id ) {
        $this->instanceid = $id;
    }

    /**
     * set the referentiel
     * @param object referentiel the referentiel referentiel object
     */
	function setRReferentiel( $referentiel_referentiel ) {
        $this->rreferentiel = $referentiel_referentiel;
    }

    /**
     * set the referentiel
     * @param object referentiel the referentiel object
     */
	function setRefReferentiel( $id ) {
        $this->referentielid = $id;
    }

    /**
     * set the action 
     * @param string action 
     */
	function setAction( $action ) {
        $this->action = $action;
    }

    /**
     * set the course class variable
     * @param course object Moodle course variable
     */
    function setCourse( $course ) {
        $this->course = $course;
    }

    /**
     * set the course class variable
     * @param course object Moodle course variable
     */
    function setCoursemodule( $cm ) {
        $this->coursemodule = $cm;
    }

    /**
     * set the filename
     * @param string filename name of file to import/export
     */
    function setFilename( $filename ) {
        $this->filename = $filename;
    }

    /**
     * set returnpage
     * @param bool stoponerror stops database write if any errors reported
     */
    function setReturnpage( $returnpage ) {
        $this->returnpage = $returnpage;
    }

    /**
     * set stoponerror
     * @param bool stoponerror stops database write if any errors reported
     */
    function setStoponerror( $stoponerror ) {
        $this->stoponerror = $stoponerror;
    }
	
    /**
     * set override
     * @param bool override database write 
     */
    function setOverride( $override ) {
        $this->override = $override;
    }
	
	
    function error( $message, $text='', $referentielname='' ) {
        $importerrorreferentiel = get_string('exporterror', 'referentiel');

        echo "<div class=\"importerror\">\n";
        echo "<strong>$importerrorreferentiel $referentielname</strong>";
        if (!empty($text)) {
            $text = s($text);
            echo "<blockquote>$text</blockquote>\n";
        }
        echo "<strong>$message</strong>\n";
        echo "</div>";

         $this->importerrors++;
    }

/*******************
 * EXPORT FUNCTIONS
 *******************/

    /** 
     * Provide export functionality for plugin referentiel types
     * Do not override
     * @param name referentiel name
     * @param referentiel object data to export 
     * @param extra mixed any addition format specific data needed
     * @return string the data to append to export or false if error (or unhandled)
     */
    function try_exporting( $name, $referentiel, $extra=null ) {

        // work out the name of format in use
        $formatname = substr( get_class( $this ), strlen( 'aformat_' ));
        $methodname = "export_to_$formatname";

		if (method_exists( $methodname )) {
			if ($data = $methodname( $referentiel, $this, $extra )) {
				return $data;
            }
        }
        return false;
    }

    /**
     * Return the files extension appropriate for this type
     * override if you don't want .txt
     * @return string file extension
     */
    function export_file_extension() {
        return ".txt";
    }

    /**
     * Do any pre-processing that may be required
     * @param boolean success
     */
    function exportpreprocess() {
        return true;
    }

    /**
     * Enable any processing to be done on the content
     * just prior to the file being saved
     * default is to do nothing
     * @param string output text
     * @param string processed output text
     */
    function presave_process( $content ) {
        return $content;
    }

    /**
     * Do the export
     * For most types this should not need to be overrided
     * @return boolean success
     */
    function exportprocess() {
        global $CFG;

        // create a directory for the exports (if not already existing)
        if (! $export_dir = make_upload_directory($this->get_export_dir())) {
              $this->error( get_string('cannotcreatepath', 'referentiel',$export_dir) );
        }
        $path = $CFG->dataroot.'/'.$this->get_export_dir();

        notify( get_string('exportingactivites', 'referentiel') );
        $count = 0;

        // results are first written into string (and then to a file)
        // so create/initialize the string here
        $expout = "";
        
        // export the item displaying message
        $count++;
        echo "<hr /><p><b>$count</b>. ".$this->ireferentiel->name."</p>";
		
        $expout .= $this->write_liste_activites( $this->ireferentiel ) . "\n";

        // continue path for following error checks
        $coursemodule = $this->coursemodule;
        $continuepath = "$CFG->wwwroot/mod/referentiel/export_activite.php?id=$coursemodule->id"; 

        // did we actually process anything
        if ($count==0) {
           $this->error( 'noactivite', 'referentiel', $continuepath );        
        }

        // final pre-process on exported data
        $expout = $this->presave_process( $expout );
       
        // write file
        $filepath = $path."/".$this->filename . $this->export_file_extension();
        if (!$fh=fopen($filepath,"w")) {
            $this->error( 'cannotopen', 'referentiel' ,$continuepath, $filepath );
        }
        if (!fwrite($fh, $expout, strlen($expout) )) {
           $this->error( 'cannotwrite', 'referentiel', $continuepath, $filepath );
        }
        fclose($fh);
        return true;
    }

    /**
     * Do an post-processing that may be required
     * @return boolean success
     */
    function exportpostprocess() {
        return true;
    }

    /**
     * convert a single referentiel object into text output in the given
     * format.
     * This must be overriden
     * @param object referentiel referentiel object
     * @return mixed referentiel export text or null if not implemented
     */
    function write_liste_activites() {
        // if not overidden, then this is an error.
        $formatnotimplemented = get_string( 'formatnotimplemented', 'referentiel' );
        echo "<p>$formatnotimplemented</p>";
        return NULL;
    }

    /**
     * get directory into which export is going 
     * @return string file path
     */
    function get_export_dir() {
		global $CFG;
        $dirname = get_string('exportfilename', 'referentiel');
        $path =  $this->course->id.'/'.$CFG->moddata.'/'.$dirname; 
        return $path;
    }



/***********************
 * IMPORTING FUNCTIONS
 ***********************/
 // NOTHING TO DO 

}


class cformat_default { // certificat
  var $records_certificats = NULL;
  var $displayerrors = true;
	var $ireferentiel = NULL;	
	var $rreferentiel = NULL;
	var $referentielid = 0;
	var $coursemodule = NULL;	
  var $course = NULL;
  var $filename = '';
  var $importerrors = 0;
  var $stoponerror = true;
  var $format_condense=0;
  
// functions to indicate import/export functionality
// override to return true if implemented

    function provide_import() {
      return false;
    }

    function provide_export() {
      return false;
    }

// Accessor methods

    /**
     * set the records to exports
     * @param object records objets
     */
  	function setRCFormat($format_condense ){
        $this->format_condense =$format_condense ;
    }

    /**
     * set the records to exports
     * @param object records objets
     */
  	function setRCertificats($records_certificats ){
        $this->records_certificats=$records_certificats;
    }

    /**
     * set the referentiel instance
     * @param object referentiel the referentiel object
     */
	function setIReferentiel( $referentiel ) {
        $this->ireferentiel = $referentiel;
    }

    /**
     * set the referentiel referentiel
     * @param object referentiel the referentiel object
     */
	function setRReferentiel( $referentiel ) {
        $this->rreferentiel = $referentiel;
    }

    /**
     * set the referentiel
     * @param object referentiel the referentiel object
     */
	function setRefReferentiel( $id ) {
        $this->referentielid = $id;
    }

    /**
     * set the action 
     * @param string action 
     */
	function setAction( $action ) {
        $this->action = $action;
    }

    /**
     * set the course class variable
     * @param course object Moodle course variable
     */
    function setCourse( $course ) {
        $this->course = $course;
    }

    /**
     * set the course class variable
     * @param course object Moodle course variable
     */
    function setCoursemodule( $cm ) {
        $this->coursemodule = $cm;
    }

    /**
     * set the filename
     * @param string filename name of file to import/export
     */
    function setFilename( $filename ) {
        $this->filename = $filename;
    }

    /**
     * set returnpage
     * @param bool stoponerror stops database write if any errors reported
     */
    function setReturnpage( $returnpage ) {
        $this->returnpage = $returnpage;
    }

    /**
     * set stoponerror
     * @param bool stoponerror stops database write if any errors reported
     */
    function setStoponerror( $stoponerror ) {
        $this->stoponerror = $stoponerror;
    }
	
    /**
     * set override
     * @param bool override database write 
     */
    function setOverride( $override ) {
        $this->override = $override;
    }
	

/*******************
 * EXPORT FUNCTIONS
 *******************/
    function error( $message, $text='', $referentielname='' ) {
        $importerrorreferentiel = get_string('exporterror', 'referentiel');

        echo "<div class=\"importerror\">\n";
        echo "<strong>$importerrorreferentiel $referentielname</strong>";
        if (!empty($text)) {
            $text = s($text);
            echo "<blockquote>$text</blockquote>\n";
        }
        echo "<strong>$message</strong>\n";
        echo "</div>";

         $this->importerrors++;
    }


    /** 
     * Provide export functionality for plugin referentiel types
     * Do not override
     * @param name referentiel name
     * @param referentiel object data to export 
     * @param extra mixed any addition format specific data needed
     * @return string the data to append to export or false if error (or unhandled)
     */
    function try_exporting( $name, $referentiel, $extra=null ) {

        // work out the name of format in use
        $formatname = substr( get_class( $this ), strlen( 'cformat_' ));
        $methodname = "export_to_$formatname";

		if (method_exists( $methodname )) {
			if ($data = $methodname( $referentiel, $this, $extra )) {
				return $data;
            }
        }
        return false;
    }

    /**
     * Return the files extension appropriate for this type
     * override if you don't want .txt
     * @return string file extension
     */
    function export_file_extension() {
        return ".txt";
    }

    /**
     * Do any pre-processing that may be required
     * @param boolean success
     */
    function exportpreprocess() {
        return true;
    }

    /**
     * Enable any processing to be done on the content
     * just prior to the file being saved
     * default is to do nothing
     * @param string output text
     * @param string processed output text
     */
    function presave_process( $content ) {
        return $content;
    }

    /**
     * Do the export
     * For most types this should not need to be overrided
     * @return boolean success
     */
    function exportprocess() {
        global $CFG;

        // create a directory for the exports (if not already existing)
        if (! $export_dir = make_upload_directory($this->get_export_dir())) {
              $this->error( get_string('cannotcreatepath', 'referentiel',$export_dir) );
        }
        $path = $CFG->dataroot.'/'.$this->get_export_dir();

        notify( get_string('exportingcertificats', 'referentiel') );
        $count = 0;

        // results are first written into string (and then to a file)
        // so create/initialize the string here
        $expout = "";
        
        // export the item displaying message
        $count++;
        echo "<hr /><p><b>$count</b>. ".$this->rreferentiel->name."</p>";
		
        $expout .= $this->write_certification() . "\n"; // on passe l'instance 

        // continue path for following error checks
        $coursemodule = $this->coursemodule;
        $continuepath = "$CFG->wwwroot/mod/referentiel/export_certificate.php?id=$coursemodule->id"; 

        // did we actually process anything
        if ($count==0) {
           $this->error( 'nocertificat', 'referentiel', $continuepath );        
        }

        // final pre-process on exported data
        $expout = $this->presave_process( $expout );
       
        // write file
        $filepath = $path."/".$this->filename . $this->export_file_extension();
        if (!$fh=fopen($filepath,"w")) {
            $this->error( 'cannotopen', 'referentiel' ,$continuepath, $filepath );
        }
        if (!fwrite($fh, $expout, strlen($expout) )) {
            $this->error( 'cannotwrite', 'referentiel', $continuepath, $filepath );
        }
        fclose($fh);
        return true;
    }

    /**
     * Do an post-processing that may be required
     * @return boolean success
     */
    function exportpostprocess() {
        return true;
    }

    /**
     * convert a single referentiel object into text output in the given
     * format.
     * This must be overriden
     * @param object referentiel referentiel object
     * @return mixed referentiel export text or null if not implemented
     */
    function write_certification() {
        // if not overidden, then this is an error.
        $formatnotimplemented = get_string( 'formatnotimplemented', 'referentiel' );
        echo "<p>$formatnotimplemented</p>";
        return NULL;
    }

    /**
     * get directory into which export is going 
     * @return string file path
     */
	function get_export_dir() {
		global $CFG;
        $dirname = get_string('exportfilename', 'referentiel');
        $path =  $this->course->id.'/'.$CFG->moddata.'/'.$dirname; 
        return $path;
    }


}


// *********************************************************
// student

class eformat_default { // students

    var $displayerrors = true;
	var $ireferentiel = NULL;	
	var $rreferentiel = NULL;
	var $referentielid = 0;
	var $coursemodule = NULL;	
    var $course = NULL;
    var $filename = '';
    var $importerrors = 0;
    var $stoponerror = true;

// functions to indicate import/export functionality
// override to return true if implemented

    function provide_import() {
      return false;
    }

    function provide_export() {
      return false;
    }

// Accessor methods

    /**
     * set the referentiel
     * @param object referentiel the referentiel object
     */
	function setIReferentiel( $referentiel ) {
        $this->ireferentiel = $referentiel;
    }
    /**
     * set the referentiel
     * @param object referentiel the referentiel object
     */
	function setRReferentiel( $referentiel ) {
        $this->rreferentiel = $referentiel;
    }

    /**
     * set the referentiel
     * @param object referentiel the referentiel object
     */
	function setRefReferentiel( $id ) {
        $this->referentielid = $id;
    }

    /**
     * set the action 
     * @param string action 
     */
	function setAction( $action ) {
        $this->action = $action;
    }

    /**
     * set the course class variable
     * @param course object Moodle course variable
     */
    function setCourse( $course ) {
        $this->course = $course;
    }

    /**
     * set the course class variable
     * @param course object Moodle course variable
     */
    function setCoursemodule( $cm ) {
        $this->coursemodule = $cm;
    }

    /**
     * set the filename
     * @param string filename name of file to import/export
     */
    function setFilename( $filename ) {
        $this->filename = $filename;
    }

    /**
     * set returnpage
     * @param bool stoponerror stops database write if any errors reported
     */
    function setReturnpage( $returnpage ) {
        $this->returnpage = $returnpage;
    }

    /**
     * set stoponerror
     * @param bool stoponerror stops database write if any errors reported
     */
    function setStoponerror( $stoponerror ) {
        $this->stoponerror = $stoponerror;
    }
	
    /**
     * set override
     * @param bool override database write 
     */
    function setOverride( $override ) {
        $this->override = $override;
    }
	

/*******************
 * EXPORT FUNCTIONS
 *******************/
    function error( $message, $text='', $referentielname='' ) {
        $importerrorreferentiel = get_string('exporterror', 'referentiel');

        echo "<div class=\"importerror\">\n";
        echo "<strong>$importerrorreferentiel $referentielname</strong>";
        if (!empty($text)) {
            $text = s($text);
            echo "<blockquote>$text</blockquote>\n";
        }
        echo "<strong>$message</strong>\n";
        echo "</div>";

         $this->importerrors++;
    }


    /** 
     * Provide export functionality for plugin referentiel types
     * Do not override
     * @param name referentiel name
     * @param referentiel object data to export 
     * @param extra mixed any addition format specific data needed
     * @return string the data to append to export or false if error (or unhandled)
     */
    function try_exporting( $name, $referentiel, $extra=null ) {

        // work out the name of format in use
        $formatname = substr( get_class( $this ), strlen( 'eformat_' ));
        $methodname = "export_to_$formatname";

		if (method_exists( $methodname )) {
			if ($data = $methodname( $referentiel, $this, $extra )) {
				return $data;
            }
        }
        return false;
    }

    /**
     * Return the files extension appropriate for this type
     * override if you don't want .txt
     * @return string file extension
     */
    function export_file_extension() {
        return ".txt";
    }

    /**
     * Do any pre-processing that may be required
     * @param boolean success
     */
    function exportpreprocess() {
        return true;
    }

    /**
     * Enable any processing to be done on the content
     * just prior to the file being saved
     * default is to do nothing
     * @param string output text
     * @param string processed output text
     */
    function presave_process( $content ) {
        return $content;
    }

    /**
     * Do the export
     * For most types this should not need to be overrided
     * @return boolean success
     */
    function exportprocess() {
        global $CFG;

        // create a directory for the exports (if not already existing)
        if (! $export_dir = make_upload_directory($this->get_export_dir())) {
              $this->error( get_string('cannotcreatepath', 'referentiel',$export_dir) );
        }
        $path = $CFG->dataroot.'/'.$this->get_export_dir();

        notify( get_string('exportingstudents', 'referentiel') );
        $count = 0;

        // results are first written into string (and then to a file)
        // so create/initialize the string here
        $expout = "";
        
        // export the item displaying message
        $count++;
        echo "<hr /><p><b>$count</b>. ".$this->rreferentiel->name."</p>";
		$expout .= $this->write_liste_etablissements() . "\n"; // Liste des etablissements
        $expout .= $this->write_liste_students() . "\n"; // on passe l'instance 

        // continue path for following error checks
        $coursemodule = $this->coursemodule;
        $continuepath = "$CFG->wwwroot/mod/referentiel/export_student.php?id=$coursemodule->id"; 

        // did we actually process anything
        if ($count==0) {
           $this->error( 'nostudent', 'referentiel', $continuepath );        
        }

        // final pre-process on exported data
        $expout = $this->presave_process( $expout );
       
        // write file
        $filepath = $path."/".$this->filename . $this->export_file_extension();
        if (!$fh=fopen($filepath,"w")) {
            $this->error( 'cannotopen', 'referentiel' ,$continuepath, $filepath );
        }
        if (!fwrite($fh, $expout, strlen($expout) )) {
            $this->error( 'cannotwrite', 'referentiel', $continuepath, $filepath );
        }
        fclose($fh);
        return true;
    }

    /**
     * Do an post-processing that may be required
     * @return boolean success
     */
    function exportpostprocess() {
        return true;
    }

    /**
     * convert a single referentiel object into text output in the given
     * format.
     * This must be overriden
     * @param object referentiel referentiel object
     * @return mixed referentiel export text or null if not implemented
     */
    function write_liste_students() {
        // if not overidden, then this is an error.
        $formatnotimplemented = get_string( 'formatnotimplemented', 'referentiel' );
        echo "<p>$formatnotimplemented</p>";
        return NULL;
    }

    /**
     * get directory into which export is going 
     * @return string file path
     */
	function get_export_dir() {
		global $CFG;
        $dirname = get_string('exportfilename', 'referentiel');
        $path =  $this->course->id.'/'.$CFG->moddata.'/'.$dirname; 
        return $path;
    }

/***********************
 * IMPORTING FUNCTIONS
 ***********************/


    /** 
     * Import for students type plugins
     * Do not override.
     * @param data mixed The segment of data containing the referentiel
     * @param referentiel object processed (so far) by standard import code if appropriate
     * @param extra mixed any additional format specific data that may be passed by the format
     * @return object referentiel object suitable for save_options() or false if cannot handle
     */
    function try_importing( $data, $students=null, $extra=null ) {

        // work out what format we are using
        $formatname = substr( get_class( $this ), strlen('eformat_'));
        $methodname = "import_from_$formatname";

        // loop through installed referentieltypes checking for
        // function to handle this referentiel
        if (method_exists( $methodname)) {
        	if ($students = $methodname( $data, $referentiel, $this, $extra )) {
            	return $students;
            }
        }
        return false;   
    }

    /**
     * Perform any required pre-processing
     * @return boolean success
     */
    function importpreprocess() {
        return true;
    }

    /**
     * Process the file
     * This method should not normally be overidden
     * @return boolean success
     */
    function importprocess() {

       	// reset the timer in case file upload was slow
       	@set_time_limit();

       	// STAGE 1: Parse the file
       	notify( get_string('parsing', 'referentiel') );
         
		if (! $lines = $this->readdata($this->filename)) {
            notify( get_string('cannotread', 'referentiel') );
            return false;
        }
		$newly_imported_students = new stdClass();
        if (! $newly_imported_students = $this->lines_2_students($lines)) {   // Extract the students
            notify( get_string('noinfile', 'referentiel') );
            return false;
        }

        // STAGE 2: Write data to database
		// echo "<br />\n";
		// print_object($newly_imported_referentiel);
		// echo "<br />\n";
		notify( get_string('importdone', 'referentiel') );
		
        // check for errors before we continue
        if ($this->stoponerror and ($this->importerrors>0)) {
            return false;
        }
		
		return true;
    }

    /**
     * Return complete file within an array, one item per line
     * @param string filename name of file
     * @return mixed contents array or false on failure
     */
    function readdata($filename) {
        if (is_readable($filename)) {
            $filearray = file($filename);
            /// Check for Macintosh OS line returns (ie file on one line), and fix
            if (ereg("\r", $filearray[0]) AND !ereg("\n", $filearray[0])) {
                return explode("\r", $filearray[0]);
            } else {
                return $filearray;
            }
        }
        return false;
    }

    /**
     * Parses an array of lines into a students array, 
     * where is a newly_imported_referentiel object as defined by 
     * readimportedreferentiel().
     *
     * @param array lines array of lines from readdata
     * @return array referentiel object
     */
    function lines_2_students($lines) {
	// 
        $tline = array();
		
        foreach ($lines as $line) {
            $line = trim($line);
			
            if (!empty($line)) {
                $tline[] = $line;
            }
        }
		// echo "<br />DEBUG 3 : format.php :: ligne 453 :: fonction lines_2_referentiel()<br />\n";
		// print_r($treferentiel);
		// echo "<br />\n";
		// exit;
        if (!empty($tline)) {  // conversion
            $imported_students = $this->read_import_students($tline);
        }
        return $imported_students ;
    }


    /**
     * return an "empty" etablissement
     * Somewhere to specify referentiel parameters that are not handled
     * by import but are required db fields.
     * This should not be overridden.
     * @return object default referentiel
	*/      
    function defaultetablissement() {
	// retourne un objet import_etablissement qui mime l'objet referentiel
        $import_etablissement = new stdClass();
		$import_etablissement->idnumber="";
		$import_etablissement->name="";
		$import_etablissement->address="";
		$import_etablissement->logo="";
    	$import_etablissement->id = 0;
        // this option in case the etablissement types class wants
        // to know where the data came from
        $import_etablissement->export_process = true;
        $import_etablissement->import_process = true;
        return $import_etablissement;
    }

    /**
     * return an "empty" student
     * Somewhere to specify referentiel parameters that are not handled
     * by import but are required db fields.
     * This should not be overridden.
     * @return object default referentiel
	*/      
    function defaultstudent() {
	// retourne un objet import_student qui mime l'objet referentiel
        $import_student = new stdClass();
    	$import_student->id = 0;
    	$import_student->userid = 0;		
		$import_student->num_student="";
		$import_student->ddn_student="";
		$import_student->lieu_naissance="";		
		$import_student->departement_naissance="";
		$import_student->adresse_student="";
		$import_student->ref_etablissement=0;		
        // this option in case the student types class wants
        // to know where the data came from
        $import_student->export_process = true;
        $import_student->import_process = true;
        return $import_student;
    }


    /**
     * Given the data known to define a referentiel in 
     * this format, this function converts it into a referentiel 
     * object suitable for processing and insertion into Moodle.
     *
     * If your format does not use blank lines to delimit referentiels
     * (e.g. an XML format) you must override 'readreferentiels' too
     * @param $lines mixed data that represents referentiel
     * @return object referentiel object
     */
	function read_import_students($lines) {

        $formatnotimplemented = get_string( 'formatnotimplemented', 'referentiel' );
        echo "<p>$formatnotimplemented</p>";

        return NULL;
    }

    /**
     * Override if any post-processing is required
     * @return boolean success
     */
    function importpostprocess() {
        return true;
    }

    /**
     * Import an image file encoded in base64 format
     * @param string path path (in course data) to store picture
     * @param string base64 encoded picture
     * @return string filename (nb. collisions are handled)
     */
    function importimagefile( $path, $base64 ) {
        global $CFG;

        // all this to get the destination directory
        // and filename!
        $fullpath = "{$CFG->dataroot}/{$this->course->id}/$path";
        $path_parts = pathinfo( $fullpath );
        $destination = $path_parts['dirname'];
        $file = clean_filename( $path_parts['basename'] );

        // check if path exists
        check_dir_exists($destination, true, true );

        // detect and fix any filename collision - get unique filename
        $newfiles = resolve_filename_collisions( $destination, array($file) );        
        $newfile = $newfiles[0];

        // convert and save file contents
        if (!$content = base64_decode( $base64 )) {
            return '';
        }
        $newfullpath = "$destination/$newfile";
        if (!$fh = fopen( $newfullpath, 'w' )) {
            return '';
        }
        if (!fwrite( $fh, $content )) {
            return '';
        }
        fclose( $fh );

        // return the (possibly) new filename
        $newfile = ereg_replace("{$CFG->dataroot}/{$this->course->id}/", '',$newfullpath);
        return $newfile;
    }

}


/**************************************************************************

TACHES

***************************************************************************/
class tformat_default {

    var $displayerrors = true;
	var $ireferentiel = NULL; // instance
	var $rreferentiel = NULL; // referentiel
	var $referentielid = 0;
	var $coursemodule = NULL;	
    var $course = NULL;
    var $filename = '';
    var $importerrors = 0;
    var $stoponerror = true;

// functions to indicate import/export functionality
// override to return true if implemented

    function provide_import() {
      return false;
    }

    function provide_export() {
      return false;
    }

// Accessor methods
    /**
     * set the referentiel
     * @param object referentiel the referentiel instance object
     */
	function setIReferentiel( $referentiel_instance ) {
        $this->ireferentiel = $referentiel_instance;
    }

	/**
     * set the referentiel instance ID
     * @param object referentiel the referentiel object
     */
	function setRefInstance( $id ) {
        $this->instanceid = $id;
    }

    /**
     * set the referentiel referentiel object
     * @param object referentiel the referentiel referentiel object
     */
	function setRReferentiel( $referentiel_referentiel ) {
        $this->rreferentiel = $referentiel_referentiel;
    }

    /**
     * set the referentiel referentiel ID
     * @param object referentiel the referentiel object
     */
	function setRefReferentiel( $id ) {
        $this->referentielid = $id;
    }

    /**
     * set the action 
     * @param string action 
     */
	function setAction( $action ) {
        $this->action = $action;
    }

    /**
     * set the course class variable
     * @param course object Moodle course variable
     */
    function setCourse( $course ) {
        $this->course = $course;
    }

    /**
     * set the CourseModule class variable
     * @param course object Moodle course variable
     */
    function setCoursemodule( $cm ) {
        $this->coursemodule = $cm;
    }

    /**
     * set the filename
     * @param string filename name of file to import/export
     */
    function setFilename( $filename ) {
        $this->filename = $filename;
    }

    /**
     * set returnpage
     * @param bool stoponerror stops database write if any errors reported
     */
    function setReturnpage( $returnpage ) {
        $this->returnpage = $returnpage;
    }

    /**
     * set stoponerror
     * @param bool stoponerror stops database write if any errors reported
     */
    function setStoponerror( $stoponerror ) {
        $this->stoponerror = $stoponerror;
    }
	
    /**
     * set override
     * @param bool override database write 
     */
    function setOverride( $override ) {
        $this->override = $override;
    }
	
	
    function error( $message, $text='', $referentielname='' ) {
        $importerrorreferentiel = get_string('exporterror', 'referentiel');

        echo "<div class=\"importerror\">\n";
        echo "<strong>$importerrorreferentiel $referentielname</strong>";
        if (!empty($text)) {
            $text = s($text);
            echo "<blockquote>$text</blockquote>\n";
        }
        echo "<strong>$message</strong>\n";
        echo "</div>";

         $this->importerrors++;
    }

/*******************
 * EXPORT FUNCTIONS
 *******************/

    /** 
     * Provide export functionality for plugin referentiel types
     * Do not override
     * @param name referentiel name
     * @param referentiel object data to export 
     * @param extra mixed any addition format specific data needed
     * @return string the data to append to export or false if error (or unhandled)
     */
    function try_exporting( $name, $referentiel, $extra=null ) {

        // work out the name of format in use
        $formatname = substr( get_class( $this ), strlen( 'tformat_' ));
        $methodname = "export_to_$formatname";

		if (method_exists( $methodname )) {
			if ($data = $methodname( $referentiel, $this, $extra )) {
				return $data;
            }
        }
        return false;
    }

    /**
     * Return the files extension appropriate for this type
     * override if you don't want .txt
     * @return string file extension
     */
    function export_file_extension() {
        return ".txt";
    }

    /**
     * Do any pre-processing that may be required
     * @param boolean success
     */
    function exportpreprocess() {
        return true;
    }

    /**
     * Enable any processing to be done on the content
     * just prior to the file being saved
     * default is to do nothing
     * @param string output text
     * @param string processed output text
     */
    function presave_process( $content ) {
        return $content;
    }

    /**
     * Do the export
     * For most types this should not need to be overrided
     * @return boolean success
     */
    function exportprocess() {
        global $CFG;

        // create a directory for the exports (if not already existing)
        if (! $export_dir = make_upload_directory($this->get_export_dir())) {
              $this->error( get_string('cannotcreatepath', 'referentiel',$export_dir) );
        }
        $path = $CFG->dataroot.'/'.$this->get_export_dir();

        notify( get_string('exportingtasks', 'referentiel') );
        $count = 0;

        // results are first written into string (and then to a file)
        // so create/initialize the string here
        $expout = "";
        
        // export the item displaying message
        $count++;
        echo "<hr /><p><b>$count</b>. ".$this->ireferentiel->name."</p>";
		
        $expout .= $this->write_liste_tasks() . "\n";

        // continue path for following error checks
        $coursemodule = $this->coursemodule;
        $continuepath = "$CFG->wwwroot/mod/referentiel/export_task.php?id=$coursemodule->id"; 

        // did we actually process anything
        if ($count==0) {
           $this->error( 'notask', 'referentiel', $continuepath );        
        }

        // final pre-process on exported data
        $expout = $this->presave_process( $expout );
       
        // write file
        $filepath = $path."/".$this->filename . $this->export_file_extension();
        if (!$fh=fopen($filepath,"w")) {
            $this->error( 'cannotopen', 'referentiel' ,$continuepath, $filepath );
        }
        if (!fwrite($fh, $expout, strlen($expout) )) {
           $this->error( 'cannotwrite', 'referentiel', $continuepath, $filepath );
        }
        fclose($fh);
        return true;
    }

    /**
     * Do an post-processing that may be required
     * @return boolean success
     */
    function exportpostprocess() {
        return true;
    }

    /**
     * convert a single referentiel object into text output in the given
     * format.
     * This must be overriden
     * @param object referentiel referentiel object
     * @return mixed referentiel export text or null if not implemented
     */
    function write_liste_tasks() {
        // if not overidden, then this is an error.
        $formatnotimplemented = get_string( 'formatnotimplemented', 'referentiel' );
        echo "<p>$formatnotimplemented</p>";
        return NULL;
    }

    /**
     * get directory into which export is going 
     * @return string file path
     */
    function get_export_dir() {
		global $CFG;
        $dirname = get_string('exportfilename', 'referentiel');
        $path =  $this->course->id.'/'.$CFG->moddata.'/'.$dirname; 
        return $path;
    }



/***********************
 * IMPORTING FUNCTIONS
 ***********************/


    /** 
     * Import for referentieltype plugins
     * Do not override.
     * @param data mixed The segment of data containing the referentiel
     * @param referentiel object processed (so far) by standard import code if appropriate
     * @param extra mixed any additional format specific data that may be passed by the format
     * @return object referentiel object suitable for save_options() or false if cannot handle
     */
    function try_importing( $data, $referentiel=null, $extra=null ) {

        // work out what format we are using
        $formatname = substr( get_class( $this ), strlen('tformat_'));
        $methodname = "import_from_$formatname";

        // loop through installed referentieltypes checking for
        // function to handle this referentiel
        if (method_exists( $methodname)) {
        	if ($referentiel = $methodname( $data, $referentiel, $this, $extra )) {
            	return $referentiel;
            }
        }
        return false;   
    }

    /**
     * Perform any required pre-processing
     * @return boolean success
     */
    function importpreprocess() {
        return true;
    }

    /**
     * Process the file
     * This method should not normally be overidden
     * @return boolean success
     */
    function importprocess() {

       	// reset the timer in case file upload was slow
       	@set_time_limit();

       	// STAGE 1: Parse the file
       	notify( get_string('parsing', 'referentiel') );
         
		if (! $lines = $this->readdata($this->filename)) {
            notify( get_string('cannotread', 'referentiel') );
            return false;
        }
		$newly_imported_referentiel = new stdClass();
		$newly_imported_tasks = $this->lines_2_tasks($lines);
        if (empty($newly_imported_tasks)) {   // Extract the referentiel
            notify( get_string('noinfile', 'referentiel') );
            return false;
        }

        // STAGE 2: Write data to database
		// echo "<br />\n";
		// print_object($newly_imported_tasks);
		// echo "<br />\n";
		notify( get_string('importdone', 'referentiel') );
		
        // check for errors before we continue
        if ($this->stoponerror and ($this->importerrors>0)) {
            return false;
        }
		
		return true;
    }

    /**
     * Return complete file within an array, one item per line
     * @param string filename name of file
     * @return mixed contents array or false on failure
     */
    function readdata($filename) {
        if (is_readable($filename)) {
            $filearray = file($filename);
            /// Check for Macintosh OS line returns (ie file on one line), and fix
            if (ereg("\r", $filearray[0]) AND !ereg("\n", $filearray[0])) {
                return explode("\r", $filearray[0]);
            } else {
                return $filearray;
            }
        }
        return false;
    }

    /**
     * Parses an array of lines into a referentiel, 
     * where is a $newly_imported_tasks object as defined by 
     * readimportedtask().
     *
     * @param array lines array of lines from readdata
     * @return array referentiel object
     */
    function lines_2_tasks($lines) {
	// 
        $tline = array();
		
        foreach ($lines as $line) {
            $line = trim($line);
			
            if (!empty($line)) {
                $tline[] = $line;
            }
        }
		// echo "<br />DEBUG 3 : format.php :: ligne 453 :: fonction lines_2_tasks()<br />\n";
		// print_r($treferentiel);
		// echo "<br />\n";
		// exit;

        if (!empty($tline)) {  // conversion
            return($this->read_import_tasks($tline));
        }
        else{
            return NULL;
        }
    }


    /**
     * return an "empty" referentiel
     * Somewhere to specify referentiel parameters that are not handled
     * by import but are required db fields.
     * This should not be overridden.
     * @return object default referentiel
	*/      
    function defaultreferentiel_reduit() {
	// retourne un objet import_referentiel_reduit qui mime l'objet referentiel
        $import_referentiel_reduit = new stdClass();
		$import_referentiel_reduit->name="";
		$import_referentiel_reduit->code_referentiel_reduit="";
		$import_referentiel_reduit->description_referentiel_reduit="";
		$import_referentiel_reduit->cle_referentiel="";
		$import_referentiel_reduit->liste_codes_competence="";
		/*		
		$import_referentiel_reduit->url_referentiel_reduit="";
		$import_referentiel_reduit->certificatethreshold="";
    	$import_referentiel_reduit->timemodified = time();
		$import_referentiel_reduit->nb_tasks="";
		$import_referentiel_reduit->liste_codes_competence="";
		$import_referentiel_reduit->local=0;
    	$import_referentiel_reduit->id = 0;
		*/
        // this option in case the referentieltypes class wants
        // to know where the data came from
        $import_referentiel_reduit->export_process = false;
        $import_referentiel_reduit->import_process = true;
        return $import_referentiel_reduit;
    }

	
    function defaulttask() {
        // retourne un objet task
		/*
CREATE TABLE mdl_referentiel_task (
  id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  type varchar(80) NOT NULL DEFAULT '',
  description text NOT NULL,
  competences_task text NOT NULL,
  criteres_evaluation text NOT NULL,
  instanceid bigint(10) unsigned NOT NULL DEFAULT '0',
  referentielid bigint(10) unsigned NOT NULL DEFAULT '0',
  course bigint(10) unsigned NOT NULL DEFAULT '0',
  auteurid bigint(10) unsigned NOT NULL,
  timecreated bigint(10) unsigned NOT NULL DEFAULT '0',
  timemodified bigint(10) unsigned NOT NULL DEFAULT '0',
  timestart bigint(10) unsigned NOT NULL DEFAULT '0',
  timeend bigint(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='task';
		
		*/
        $task = new stdClass();
    	$task->id = 0;
		$task->type="";
		$task->description="";
		$task->competences_task="";
		$task->criteres_evaluation="";
		$task->instanceid=0;
		$task->referentielid=0;
		$task->course=0;
		$task->auteurid=0;
		$task->timecreated=0;
		$task->timemodified=0;
		$task->timestart=0;
		$task->timeend=0;
		
        return $task;
    }

    function defaultconsigne() {
	// retourne un objet consigne	
	/*

CREATE TABLE mdl_referentiel_consigne (
  id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  type varchar(20) NOT NULL DEFAULT '',
  description text NOT NULL,
  url varchar(255) NOT NULL DEFAULT '',
  taskid bigint(10) unsigned NOT NULL,
  PRIMARY KEY (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='consigne';	*/
        $consigne = new stdClass();
    	$consigne->id = 0;
		$consigne->type="";
		$consigne->description="";
		$consigne->url="";
		$consigne->taskid=0;
        return $consigne;
    }


    /**
     * Given the data known to define a referentiel in 
     * this format, this function converts it into a referentiel 
     * object suitable for processing and insertion into Moodle.
     *
     * If your format does not use blank lines to delimit referentiels
     * (e.g. an XML format) you must override 'readreferentiels' too
     * @param $lines mixed data that represents referentiel
     * @return object referentiel object
     */
	function read_import_tasks($lines) {

        $formatnotimplemented = get_string( 'formatnotimplemented', 'referentiel' );
        echo "<p>$formatnotimplemented</p>";

        return NULL;
    }

    /**
     * Override if any post-processing is required
     * @return boolean success
     */
    function importpostprocess() {
        return true;
    }

    /**
     * Import an image file encoded in base64 format
     * @param string path path (in course data) to store picture
     * @param string base64 encoded picture
     * @return string filename (nb. collisions are handled)
     */
    function importimagefile( $path, $base64 ) {
        global $CFG;

        // all this to get the destination directory
        // and filename!
        $fullpath = "{$CFG->dataroot}/{$this->course->id}/$path";
        $path_parts = pathinfo( $fullpath );
        $destination = $path_parts['dirname'];
        $file = clean_filename( $path_parts['basename'] );

        // check if path exists
        check_dir_exists($destination, true, true );

        // detect and fix any filename collision - get unique filename
        $newfiles = resolve_filename_collisions( $destination, array($file) );        
        $newfile = $newfiles[0];

        // convert and save file contents
        if (!$content = base64_decode( $base64 )) {
            return '';
        }
        $newfullpath = "$destination/$newfile";
        if (!$fh = fopen( $newfullpath, 'w' )) {
            return '';
        }
        if (!fwrite( $fh, $content )) {
            return '';
        }
        fclose( $fh );

        // return the (possibly) new filename
        $newfile = ereg_replace("{$CFG->dataroot}/{$this->course->id}/", '',$newfullpath);
        return $newfile;
    }


}
?>
