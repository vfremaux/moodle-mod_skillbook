<?php  // $Id: format_activite.php,v 1.0 2008/05/01 00:00:00 jfruitet Exp $ 
/**
 * INCLUS DANS format.php
 * Base class for referentiel import and export formats.
 * recupere de referentiel/format.php
 *
 * @author Martin Dougiamas, Howard Miller, and many others.
 *         {@link http://moodle.org}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package referentiel
 * @subpackage importexport
 */
 

class aformat_default {

    var $displayerrors = true;
	var $referentiel = NULL;
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
	function setReferentiel( $referentiel ) {
        $this->referentiel = $referentiel;
    }

    /**
     * set the referentiel
     * @param object referentiel the referentiel object
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
              referentiel_error( get_string('cannotcreatepath', 'referentiel',$export_dir) );
        }
        $path = $CFG->dataroot.'/'.$this->get_export_dir();

        notify( get_string('exportingactivites', 'referentiel') );
        $count = 0;

        // results are first written into string (and then to a file)
        // so create/initialize the string here
        $expout = "";
        
        // export the item displaying message
        $count++;
        echo "<hr /><p><b>$count</b>. ".$this->referentiel->name."</p>";
		
        $expout .= $this->write_activite( $this->referentiel ) . "\n";

        // continue path for following error checks
        $coursemodule = $this->coursemodule;
        $continuepath = "$CFG->wwwroot/mod/referentiel/export_activite.php?id=$coursemodule->id"; 

        // did we actually process anything
        if ($count==0) {
            print_referentiel_error( 'noactivites', 'referentiel', $continuepath );        
        }

        // final pre-process on exported data
        $expout = $this->presave_process( $expout );
       
        // write file
        $filepath = $path."/".$this->filename . $this->export_file_extension();
        if (!$fh=fopen($filepath,"w")) {
            print_referentiel_error( 'cannotopen', 'referentiel' ,$continuepath, $filepath );
        }
        if (!fwrite($fh, $expout, strlen($expout) )) {
            print_referentiel_error( 'cannotwrite', 'referentiel', $continuepath, $filepath );
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
    function write_certification(($referentiel) {
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
        $dirname = get_string('exportfilename', 'referentiel');
        $path = $this->course->id.'/'.$dirname; // backupdata is protected directory
        return $path;
    }



/***********************
 * IMPORTING FUNCTIONS
 ***********************/

/*********************************************************
//    **
//   * Handle parsing error
//   *
    function referentiel_error( $message, $text='', $referentielname='' ) {
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

//    ** 
//   * Import for referentieltype plugins
//   * Do not override.
//   * @param data mixed The segment of data containing the referentiel
//   * @param referentiel object processed (so far) by standard import code if appropriate
//   * @param extra mixed any additional format specific data that may be passed by the format
//   * @return object referentiel object suitable for save_options() or false if cannot handle
//   *
    function try_importing( $data, $referentiel=null, $extra=null ) {

        // work out what format we are using
        $formatname = substr( get_class( $this ), strlen('aformat_'));
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

//    **
//   * Perform any required pre-processing
//   * @return boolean success
//   *
    function importpreprocess() {
        return true;
    }

//    **
//   * Process the file
//   * This method should not normally be overidden
//   * @return boolean success
//   *
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
		echo "<br />\n";
		print_object($newly_imported_referentiel);
		echo "<br />\n";
		notify( get_string('importdone', 'referentiel') );
		
        // check for errors before we continue
        if ($this->stoponerror and ($this->importerrors>0)) {
            return false;
        }
		
		return true;
    }

//    **
//   * Return complete file within an array, one item per line
//   * @param string filename name of file
//   * @return mixed contents array or false on failure
//   *
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

//    **
//   * Parses an array of lines into a referentiel, 
//   * where is a newly_imported_referentiel object as defined by 
//   * readimportedreferentiel().
//   *
//   * @param array lines array of lines from readdata
//   * @return array referentiel object
//   *
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


//    **
//   * return an "empty" referentiel
//   * Somewhere to specify referentiel parameters that are not handled
//   * by import but are required db fields.
//   * This should not be overridden.
//   * @return object default referentiel
	*      
    function defaultreferentiel() {
	// retourne un objet import_referentiel qui mime l'objet refrentiel
        $import_referentiel = new stdClass();
		$import_referentiel->name="";
		$import_referentiel->code="";
		$import_referentiel->description="";
		$import_referentiel->url="";
		$import_referentiel->certificatethreshold="";
    	$import_referentiel->timemodified = time();
		$import_referentiel->nb_domaines="";
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

//    **
//   * Given the data known to define a referentiel in 
//   * this format, this function converts it into a referentiel 
//   * object suitable for processing and insertion into Moodle.
//   *
//   * If your format does not use blank lines to delimit referentiels
//   * (e.g. an XML format) you must override 'readreferentiels' too
//   * @param $lines mixed data that represents referentiel
//   * @return object referentiel object
//   *
	function read_import_referentiel($lines) {

        $formatnotimplemented = get_string( 'formatnotimplemented', 'referentiel' );
        echo "<p>$formatnotimplemented</p>";

        return NULL;
    }

//    **
////   * Override if any post-processing is required
////   * @return boolean success
////   *
    function importpostprocess() {
        return true;
    }

//    **
////   * Import an image file encoded in base64 format
////   * @param string path path (in course data) to store picture
////   * @param string base64 encoded picture
////   * @return string filename (nb. collisions are handled)
////   *
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

*********************************************************/
}

?>
