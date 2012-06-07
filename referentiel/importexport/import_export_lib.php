<?php

//===========================
// Import/Export Functions
//===========================

/**
 * Get list of available import or export formats
 * @param string $type 'import' if import list, otherwise export list assumed
 * @return array sorted list of import/export formats available
**/
function referentiel_get_import_export_formats( $type, $classprefix="" ) {

    global $CFG;
    $fileformats = get_list_of_plugins("mod/referentiel/format");
    $fileformatnames=array();
    require_once( "{$CFG->dirroot}/mod/referentiel/format.php" );
    foreach ($fileformats as $key => $fileformat) {
        $format_file = $CFG->dirroot . "/mod/referentiel/format/$fileformat/format.php";
        if (file_exists( $format_file ) ) {
           require_once( $format_file );
        }
        else {
            continue;
        }
		
		if ($classprefix){
	        $classname = $classprefix."_".$fileformat;
    	}
		else{
	        $classname = "rformat_$fileformat";
    	}
	    
		$format_class = new $classname();
        if ($type=='import') {
            $provided = $format_class->provide_import();
        }
        else {
            $provided = $format_class->provide_export();
        }
        if ($provided) {
            $formatname = get_string($fileformat, "referentiel");
            if ($formatname == "[[$fileformat]]") {
                $formatname = $fileformat;  // Just use the raw folder name
            }
            $fileformatnames[$fileformat] = $formatname;
        }
    }
    natcasesort($fileformatnames);
   return $fileformatnames;
}


/**
* Create default export filename
*
* @return string   default export filename
* @param object $course
* @param object $referentiel
* @param string $info
*/
function referentiel_default_export_filename($course, $referentiel, $info="") {
    //Take off some characters in the filename !!
    $takeoff = array(" ", ":", "/", "\\", "|");
    $export_word = str_replace($takeoff,"_",moodle_strtolower(get_string("exportfilename","referentiel")));
    //If non-translated, use "export"
    if (substr($export_word,0,1) == "[") {
        $export_word= "export";
    }

    //Calculate the date format string
    $export_date_format = str_replace(" ","_",get_string("exportnameformat","referentiel"));
    //If non-translated, use "%Y%m%d-%H%M"
    if (substr($export_date_format,0,1) == "[") {
        $export_date_format = "%%Y%%m%%d-%%H%%M";
    }

    //Calculate the shortname
    $export_shortname = clean_filename($course->shortname);
    if (empty($export_shortname) or $export_shortname == '_' ) {
        $export_shortname = $course->id;
    }

    //Calculate the instance name
    $export_instancename = clean_filename($referentiel->name);

    //Calculate the final export filename
    //The export word
    $export_name = $export_word."-";
    //The shortname
    $export_name .= moodle_strtolower($export_shortname)."-";
    //The instance name
    $export_name .= moodle_strtolower($export_instancename)."-";
	if ($info){
		$export_name .= moodle_strtolower($info)."-";
	}
	
    //The date format
    $export_name .= userdate(time(),$export_date_format,99,false);
    //The extension - no extension, supplied by format
    // $export_name .= ".txt";

    return $export_name;
}

?>
