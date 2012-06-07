<?php  // $Id: print.php,v 1.0 2008/05/01 00:00:00 jfruitet Exp $ 
/**
 * Base class for referentiel import and export prints.
 * recupere de question/print.php
 *
 * @author Martin Dougiamas, Howard Miller, and many others.
 *         {@link http://moodle.org}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package referentiel
 */
 



class pprint_default { // certificat
  var $records_certificats = NULL;
  var $userid;
	var $certificate_sel_param ; // selection de parametres de selection
	var $liste_empreintes_competence='';
	var $liste_poids_competence='';
  var $displayerrors = true;
	var $referentiel_instance = NULL;
	var $referentiel_referentiel = NULL;
	var $coursemodule = NULL;	
  var $course = NULL;
  var $filename = '';
  var $importerrors = 0;
  var $stoponerror = true;

// functions to indicate import/export functionality
// override to return true if implemented


    function provide_print() {
      return false;
    }

// Accessor methods

    /**
     * set the records to exports
     * @param object records objets
     */
  	function setRCertificats($records_certificats ){
        $this->records_certificats=$records_certificats;
    }

    /**
     * set the course class variable
     * @param course object Moodle course variable
     */
    function setUserid( $userid ) {
        $this->userid = $userid;
    }

    /**
     * set the referentiel
     * @param object referentiel the referentiel object
     */
	function setParam( $param) {
        $this->certificate_sel_param = $param;
    }

    /**
     * set the referentiel
     * @param object referentiel the referentiel object
     */
	function setEmpreintes( $referentiel_referentiel ) {
        $this->liste_empreintes_competence = $referentiel_referentiel->liste_empreintes_competence;
    }

    /**
     * set the referentiel
     * @param object referentiel the referentiel object
     */
	function setPoids( $referentiel_referentiel ) {
        $this->liste_poids_competence = referentiel_purge_dernier_separateur(referentiel_get_liste_poids($referentiel_referentiel->id), '|');
    }

    /**
     * set the referentiel
     * @param object referentiel the referentiel object
     */
	function setReferentielInstance( $referentiel ) {
        $this->referentiel_instance = $referentiel;
    }

    /**
     * set the referentiel
     * @param object referentiel the referentiel object
     */
	function setReferentielReferentiel( $referentiel_referentiel ) {
        $this->referentiel_referentiel = $referentiel_referentiel;
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
    function referentiel_error( $message, $text='', $referentielname='' ) {
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
     * @param extra mixed any addition print specific data needed
     * @return string the data to append to export or false if error (or unhandled)
     */
    function try_exporting( $name, $referentiel, $extra=null ) {

        // work out the name of print in use
        $printname = substr( get_class( $this ), strlen( 'pprint_' ));
        $methodname = "export_to_$printname";

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
              $this->referentiel_error( get_string('cannotcreatepath', 'referentiel',$export_dir) );
        }
        $path = $CFG->dataroot.'/'.$this->get_export_dir();

        notify( get_string('exportingcertificats', 'referentiel') );
        $count = 0;

        // results are first written into string (and then to a file)
        // so create/initialize the string here
        $expout = "";
        
        // export the item displaying message
        $count++;
        echo "<hr /><p><b>$count</b>. ".$this->referentiel_instance->name."</p>";
		
        $expout .= $this->write_certification($this->referentiel_instance ) . "\n"; // on passe l'instance 

        // continue path for following error checks
        $coursemodule = $this->coursemodule;
        $continuepath = "$CFG->wwwroot/mod/referentiel/export_certificate.php?id=$coursemodule->id"; 

        // did we actually process anything
        if ($count==0) {
           $this->referentiel_error( 'nocertificat', 'referentiel', $continuepath );        
        }

        // final pre-process on exported data
        $expout = $this->presave_process( $expout );
       
        // write file
        $filepath = $path."/".$this->filename . $this->export_file_extension();
        if (!$fh=fopen($filepath,"w")) {
            $this->referentiel_error( 'cannotopen', 'referentiel' ,$continuepath, $filepath );
        }
        if (!fwrite($fh, $expout, strlen($expout) )) {
            $this->referentiel_error( 'cannotwrite', 'referentiel', $continuepath, $filepath );
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
     * print.
     * This must be overriden
     * @param object referentiel referentiel object
     * @return mixed referentiel export text or null if not implemented
     */
    function write_certification() {
        // if not overidden, then this is an error.
        $printnotimplemented = get_string( 'printnotimplemented', 'referentiel' );
        echo "<p>$printnotimplemented</p>";
        return NULL;
    }

    /**
     * get directory into which export is going 
     * @return string file path
     */
    function get_export_dir() {
	  global $CFG;
        $dirname = get_string('exportfilename', 'referentiel');
        $path = $this->course->id.'/'.$CFG->moddata.'/'.$dirname; // backupdata is protected directory
        return $path;
    }

}


?>
