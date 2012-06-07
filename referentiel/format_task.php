<?php  // $Id: format_task.php,v 1.0 2008/05/01 00:00:00 jfruitet Exp $ 
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
	
	
    function print_error( $message, $text='', $referentielname='' ) {
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
              $this->print_error( get_string('cannotcreatepath', 'referentiel',$export_dir) );
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
		
        $expout .= $this->write_liste_tasks( $this->ireferentiel ) . "\n";

        // continue path for following error checks
        $coursemodule = $this->coursemodule;
        $continuepath = "$CFG->wwwroot/mod/referentiel/export_task.php?id=$coursemodule->id"; 

        // did we actually process anything
        if ($count==0) {
           $this->print_error( 'notask', 'referentiel', $continuepath );        
        }

        // final pre-process on exported data
        $expout = $this->presave_process( $expout );
       
        // write file
        $filepath = $path."/".$this->filename . $this->export_file_extension();
        if (!$fh=fopen($filepath,"w")) {
            $this->print_error( 'cannotopen', 'referentiel' ,$continuepath, $filepath );
        }
        if (!fwrite($fh, $expout, strlen($expout) )) {
           $this->print_error( 'cannotwrite', 'referentiel', $continuepath, $filepath );
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
    function write_liste_tasks($referentiel) {
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
 // NOTHING TO DO YET

}



