<?php  // $Id: import_instance.php,v 1.1 2010/12/17 21:49:36 vf Exp $
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 2005 Martin Dougiamas  http://dougiamas.com             //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
* Importation d'un referentiel
* D'apres competency/import.php 
*
* @package referentiel
*/
	
    require_once('../../config.php');
    require_once('lib.php');
    require_once('import_export_lib.php');	// IMPORT / EXPORT	
    require_once($CFG->libdir . '/uploadlib.php');

    $id    = optional_param('id', 0, PARAM_INT);    // course module id	
    $d     = optional_param('d', 0, PARAM_INT);    // referentiel instance id

    $action  			= optional_param('action','', PARAM_ALPHA); // pour distinguer differentes formes de vcreatin de referentiel
    $mode  				= optional_param('mode','', PARAM_ALPHA);	
    $format 			= optional_param('format','', PARAM_FILE );

	$name_instance		= optional_param('name_instance','', PARAM_CLEAN);
	$description_instance		= optional_param('description_instance','', PARAM_CLEAN);
	$label_domaine    = optional_param('label_domaine','', PARAM_CLEAN);
	$label_competence = optional_param('label_competence','', PARAM_CLEAN);
	$label_item= optional_param('label_item','', PARAM_CLEAN);

    $sesskey     		= optional_param('sesskey', '', PARAM_ALPHA);
	$instance 			= optional_param('instance', 0, PARAM_INT);

	if ($d) {
        if (! $referentiel = get_record('referentiel', 'id', $d)) {
            error('Certification instance is incorrect');
        }
		if (! $course = get_record('course', 'id', $referentiel->course)) {
	            error('Course is misconfigured');
    	}
		if (! $cm = get_coursemodule_from_instance('referentiel', $referentiel->id, $course->id)) {
    	        error('Course Module ID is incorrect');
		}
	} 
	elseif ($id) {
        if (! $cm = get_coursemodule_from_id('referentiel', $id)) {
        	error('Course Module ID was incorrect');
        }
        if (! $course = get_record('course', 'id', $cm->course)) {
            error('Course is misconfigured');
        }
        if (! $referentiel = get_record('referentiel', 'id', $cm->instance)) {
            error('Certification instance is incorrect');
        }
    } 
	else{
        // error('You cannot call this script in that way');	
		error(get_string('erreurscript','referentiel','Erreur01 : import_instance.php'));
	}
	
	if (!isset($mode)){
		$mode='add'; // un seul mode possible
	}
	if (!isset($action) || empty($action)){
		$action='importreferentiel'; // une seule action possible
	}
    
	
	// get parameters
	
    $params = new stdClass;
    $params->choosefile = optional_param('choosefile','',PARAM_PATH);
    $params->stoponerror = optional_param('stoponerror', 0, PARAM_BOOL);
    $params->override = optional_param('override', 0, PARAM_BOOL);	
    $params->newinstance = optional_param('newinstance', 0, PARAM_BOOL);		

    // get display strings
    $txt = new stdClass();
    $txt->referentiel = get_string('referentiel','referentiel');
    $txt->fileformat = get_string('fileformat','referentiel');
	$txt->choosefile = get_string('choosefile','referentiel');
	
    $txt->file = get_string('file');
    $txt->fileformat = get_string('fileformat','referentiel');
    $txt->fromfile = get_string('fromfile','referentiel');
	$txt->importerror_referentiel_id = get_string('importerror_referentiel_id','referentiel');
    $txt->importerror = get_string('importerror','referentiel');
    $txt->importfilearea = get_string('importfilearea','referentiel');
    $txt->importfileupload = get_string('importfileupload','referentiel');
    $txt->importfromthisfile = get_string('importfromthisfile','referentiel');
    $txt->modulename = get_string('modulename','referentiel');
    $txt->modulenameplural = get_string('modulenameplural','referentiel');
    $txt->onlyteachersimport = get_string('onlyteachersimport','referentiel');
    $txt->stoponerror = get_string('stoponerror', 'referentiel');
	$txt->upload = get_string('upload');
    $txt->uploadproblem = get_string('uploadproblem');
    $txt->uploadthisfile = get_string('uploadthisfile');
	$txt->importreferentiel	= get_string('importreferentiel','referentiel');
	$txt->newinstance	= get_string('newinstance','referentiel');	
	$txt->choix_newinstance	= get_string('choix_newinstance','referentiel');
	$txt->choix_notnewinstance	= get_string('choix_notnewinstance','referentiel');
	$txt->override = get_string('override', 'referentiel');
	$txt->choix_override	= get_string('choix_override','referentiel');
	$txt->choix_notoverride	= get_string('choix_notoverride','referentiel');
		
    require_login();

    if (!isloggedin() or isguest()) {
        redirect($CFG->wwwroot.'/index.php?id='.$course->id);
    }


    // check role capability
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    require_capability('mod/referentiel:import', $context);

    // ensure the files area exists for this course
    make_upload_directory( "$course->id/$CFG->moddata/referentiel" );

	/// RSS and CSS and JS meta
    $meta = '';

	/// Print the page header
	$strreferentiels = get_string('modulenameplural','referentiel');
	$strreferentiel = get_string('referentiel','referentiel');
	$strmessage = get_string('importreferentiel','referentiel');		
	$icon = '<img class="icon" src="'.$CFG->wwwroot.'/mod/referentiel/icon.gif" alt="'.get_string('modulename','referentiel').'"/>';

	$strpagename=get_string('modifier_referentiel','referentiel');
	if (function_exists('build_navigation')){
		// Moodle 1.9
		$navigation = build_navigation($strpagename, $cm);
		
		print_header($course->shortname.': '.$strreferentiel, $course->fullname, $navigation, 
		'', // focus
		$meta,
		true, // page is cacheable
//		update_module_button($cm->id, $course->id, get_string('modulename', 'referentiel')), // HTML code for a button (usually for module editing)
		update_module_button($cm->id, $course->id, get_string('modulename-intance', 'referentiel')), // HTML code for a button (usually for module editing)
        navmenu($course, $cm), // HTML code for a popup menu
		false, // use XML for this page
		'', // This text will be included verbatim in the <body> tag (useful for onload() etc)
		false); // If true, return the visible elements of the header instead of echoing them.
	}
	else{
		// 1.8
		print_header_simple("$course->shortname : $strreferentiels", // title
		"", // heading
		"<a href='index.php?id=$course->id'>$strreferentiel</a> -> $strmessage", // navigation
		'', // focus
		$meta, // meta tag
		true, // page is cacheable
		update_module_button($cm->id, $course->id, get_string('modulename-intance', 'referentiel')), // HTML code for a button (usually for module editing)
        navmenu($course, $cm), // HTML code for a popup menu
		false, // use XML for this page
		'', // This text will be included verbatim in the <body> tag (useful for onload() etc)
		false) // If true, return the visible elements of the header instead of echoing them.
		;
	}

    print_heading_with_help($strmessage, 'importreferentiel', 'referentiel', $icon);

    // file upload form submitted
    if (!empty($format)) { 
        if (!confirm_sesskey()) {
        	print_error( 'sesskey' );
        }
        // file checks out ok
        $fileisgood = false;
        // work out if this is an uploaded file 
        // or one from the filesarea.
		
        if (!empty($params->choosefile)) {
            $importfile = "{$CFG->dataroot}/{$course->id}/{$params->choosefile}";
            if (file_exists($importfile)) {
                $fileisgood = true;
            }
            else {
                notify($txt->uploadproblem);
            }
        }
        else {
            // must be upload file
            if (empty($_FILES['newfile'])) {
                notify( $txt->uploadproblem );
            }
            else if ((!is_uploaded_file($_FILES['newfile']['tmp_name']) or $_FILES['newfile']['size'] == 0)) {
                notify( $txt->uploadproblem );
            }
            else {
                $importfile = $_FILES['newfile']['tmp_name'];
                $fileisgood = true;
            }
        }

        // process if we are happy, file is ok
        if ($fileisgood) { 
			$returnlink="$CFG->wwwroot/mod/referentiel/import_instance.php?courseid=$course->id&amp;sesskey=$sesskey&amp;instance=$instance&amp;mode=$mode&amp;action=$action";
			// DEBUG
			// echo "<br/>RETURNLINK : $returnlink\n";
			
            if (! is_readable("format/$format/format.php")) {
                error( get_string('formatnotfound','referentiel', $format) );
            }
            require("format.php");  // Parent class
            require("format/$format/format.php");
            $classname = "rformat_$format";
            $rformat = new $classname();
            // load data into class
            $rformat->setIReferentiel( $referentiel ); // instance
            // $rformat->setRReferentiel( $referentiel_referentiel ); // not yet
            $rformat->setCourse( $course );
			$rformat->setCoursemodule( $cm); 
            $rformat->setFilename( $importfile );
            $rformat->setStoponerror( $params->stoponerror );
			$rformat->setOverride( $params->override );
			$rformat->setNewinstance( $params->newinstance );
			$rformat->setAction( $action );
			
			// $rformat->setReturnpage("");
			
            // Do anything before that we need to
            if (! $rformat->importpreprocess()) {             
                error( $txt->importerror , $returnlink);
            }
			
            // Process the uploaded file
			
            if (! $rformat->importprocess() ) {     
                error( $txt->importerror , $returnlink);
            }
			
            // In case anything needs to be done after
            if (! $rformat->importpostprocess()) {
                error( $txt->importerror , $returnlink);
            }

			// Verifier si  referentiel charge
            if (! $rformat->new_referentiel_id) {
                error( $txt->importerror_referentiel_id , $returnlink);
            }
            echo "<hr />";
			// 
?>
<form name="form" method="post" action="<?php echo 'add.php?id='.$cm->id; ?>">

<input type="hidden" name="name_instance" value="<?php  p($name_instance) ?>" />
<input type="hidden" name="description_instance" value="<?php  p($description_instance) ?>" />
<input type="hidden" name="label_domaine" value="<?php  p($label_domaine) ?>" />
<input type="hidden" name="label_competence" value="<?php  p($label_competence) ?>" />
<input type="hidden" name="label_item" value="<?php  p($label_item) ?>" />

<input type="hidden" name="action" value="importreferentiel" />	

<input type="hidden" name="new_referentiel_id" value="<?php  p($rformat->new_referentiel_id); ?>" />
<input type="hidden" name="action" value="<?php  p($rformat->action); ?>" />

<input type="hidden" name="sesskey" value="<?php p(sesskey()); ?>" />
<input type="hidden" name="course" value="<?php p($course->id); ?>" />
<input type="hidden" name="instance" value="<?php  echo $referentiel->id; ?>" />
<input type="hidden" name="mode" value="<?php  p($mode); ?>" />	

<center>
<input type="submit" value="<?php  print_string("continue"); ?>" />
</center>
</form>
<div>
<?php			
            print_footer($course);
            exit;
        }
    }

    /// Print upload form
	
    // get list of available import formats
    $fileformatnames = referentiel_get_import_export_formats( 'import', 'rformat' );
	
	//==========
    // DISPLAY
    //==========
 	
    ?>

    <form id="form" enctype="multipart/form-data" method="post" action="import_instance.php?id=<?php echo($cm->id); ?>">
        <fieldset class="invisiblefieldset" style="display: block;">
            <?php print_box_start('generalbox boxwidthnormal boxaligncenter'); ?>
            <table cellpadding="5">
                <tr>
                    <td align="right"><?php p( $txt->fileformat); ?>:</td>
                    <td><?php choose_from_menu($fileformatnames, 'format', 'xml', '');
                        helpbutton("format", $txt->importreferentiel, 'referentiel'); ?></td>
                </tr>

                <tr>					
                   <td align="right"><?php p( $txt->stoponerror); ?></td>
                   <td><input name="stoponerror" type="checkbox" checked="checked" /></td>
                </tr>

                <tr>					
                   <td align="right"><?php p( $txt->override); ?></td>
				   <td>
				   <input name="override" type="radio" value="1" /> <?php p( $txt->choix_override); ?>
				   <br />
				   <input name="override" type="radio"  value="0"  checked="checked" /> <?php p( $txt->choix_notoverride); ?>
                    <?php helpbutton('override', $txt->override, 'referentiel'); ?></td>
                </tr>

                <tr>					
                   <td align="right"><?php p( $txt->newinstance); ?></td>
				   <td>
				   <input name="newinstance" type="radio"  value="1"  checked="checked"/> <?php p( $txt->choix_newinstance); ?>
				   <br />
				   <input name="newinstance" type="radio"   value="0" /> <?php p( $txt->choix_notnewinstance); ?>
                    <?php helpbutton('override', $txt->override, 'referentiel'); ?></td>
                </tr>

            </table>
            <?php
            print_box_end();

            print_box_start('generalbox boxwidthnormal boxaligncenter'); ?>
            <?php p( $txt->importfileupload); ?>
            <table cellpadding="5">
                <tr>
                    <!-- td align="right"><?php p( $txt->upload); ?>:</td -->
                    <td colspan="2"><?php upload_print_form_fragment(1,array('newfile'),null,false,null,$course->maxbytes,0,false); ?></td>
                </tr>

                <tr>
                    <td>&nbsp;</td>
                    <td><input type="submit" name="save" value="<?php p( $txt->uploadthisfile); ?>" /></td>
                </tr>
            </table>
            <?php
            print_box_end();

            print_box_start('generalbox boxwidthnormal boxaligncenter'); ?>
            <?php p( $txt->importfilearea); ?>
            <table cellpadding="5">
                <tr>
                    <td align="right"><?php p( $txt->file); ?>:</td>
                    <td><input type="text" name="choosefile" size="60" /></td>
                </tr>

                <tr>
                    <td>&nbsp;</td>
                    <td><?php  button_to_popup_window ("/files/index.php?id={$course->id}&amp;choose=form.choosefile", 
                        "coursefiles", $txt->choosefile, 500, 750, $txt->choosefile); ?>
<br />
<input type="submit" name="save" value="<?php p( $txt->importfromthisfile); ?>" /></td>
                </tr>
            </table>
            <?php 
            print_box_end(); ?>
<input type="hidden" name="action" value="<?php  p($action); ?>" />	

<input type="hidden" name="name_instance" value="<?php  p($name_instance) ?>" />
<input type="hidden" name="description_instance" value="<?php  p($description_instance) ?>" />
<input type="hidden" name="label_domaine" value="<?php  p($label_domaine) ?>" />
<input type="hidden" name="label_competence" value="<?php  p($label_competence) ?>" />
<input type="hidden" name="label_item" value="<?php  p($label_item) ?>" />
			
<!-- These hidden variables are always the same -->

<input type="hidden" name="sesskey" value="<?php p(sesskey()); ?>" />
<input type="hidden" name="course" value="<?php p($course->id); ?>" />
<input type="hidden" name="instance" value="<?php  echo $referentiel->id; ?>" />
<input type="hidden" name="mode" value="<?php  p($mode); ?>" />	

        </fieldset>
    </form>

    <?php
    print_footer($course);

?>
