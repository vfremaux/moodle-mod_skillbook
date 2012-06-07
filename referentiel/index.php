<?php // $Id: index.php,v 1.5 2006/08/28 16:41:20 mark-nielsen Exp $
/**
 * This page lists all the instances of referentiel in a particular course
 *
 * @author 
 * @version $Id: index.php,v 1.5 2006/08/28 16:41:20 mark-nielsen Exp $
 * @package referentiel
 **/

/// Replace newmodule by with the name of your module referentiel

    require_once("../../config.php");
    require_once("lib.php");
	require_once($CFG->dirroot.'/mod/referentiel/version.php');

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = get_record("course", "id", $id)) {
        print_error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, "referentiel", "view all", "index.php?id=$course->id", "");


/// Get all required stringsreferentiel

    $strreferentiels = get_string("modulenameplural", "referentiel");
    $strreferentiel  = get_string("modulename", "referentiel");

/// Print the header
	if (function_exists('build_navigation')){
		 // Moodle 1.9
    	if ($course->category) {
			$navigation = build_navigation($strreferentiels, array(array('name'=>$course->category,'link'=>'','type'=>'misc')));		
    	} else {
        	$navigation = NULL;
    	}
		
		print_header($course->shortname.': '.$strreferentiel, $course->fullname, $navigation, 
		'', // focus
		'',
		true, // page is cacheable
		'', // HTML code for a button (usually for module editing)
        '', // HTML code for a popup menu
		false, // use XML for this page
		'', // This text will be included verbatim in the <body> tag (useful for onload() etc)
		false); // If true, return the visible elements of the header instead of echoing them.
	}
	else{
    	if ($course->category) {
        	$navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    	} else {
        	$navigation = '';
    	}
	    print_header("$course->shortname: $strreferentiels", "$course->fullname", "$navigation $strreferentiels", "", "", true, "", navmenu($course));
	}

/// Get all the appropriate data
    if (! $referentiels = get_all_instances_in_course("referentiel", $course)) {
        notice(get_string('erreur_referentiel','referentiel'), "../../course/view.php?id=$course->id");
        die;
    }

/// Print the list of instances (your module will probably extend this)
	$s_version='';
	if (!empty($module->release)) {
        $s_version.= $module->release;
   	}

	if (!empty($module->version)){
		// 2009042600;  // The current module version (Date: YYYYMMDDXX)
		$s_version.= ' ('.get_string('release','referentiel').' '.$module->version.')'."\n";
	}

    $timenow = time();
    $strname  = get_string("name");
	$strdescription  = get_string("description");
    $strweek  = get_string("week");
    $strtopic  = get_string("topic");

    if ($course->format == "weeks") {
		$table->head  = array ($strweek, $strname, $strdescription);
        $table->align = array ("center", "left", "left");
    } else if ($course->format == "topics") {
        $table->head  = array ($strtopic, $strname, $strdescription);
        $table->align = array ("center", "left", "left", "left");
    } else {
        $table->head  = array ($strname, $strdescription);
        $table->align = array ("left", "left", "left");
    }
	
//
// debug
// echo "<br />";
// print_r($referentiels);



    foreach ($referentiels as $referentiel) {
        if (!$referentiel->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?d=$referentiel->id\">$referentiel->name</a>";
			$description = "$referentiel->description";
			
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?d=$referentiel->id\">$referentiel->name</a>";
			$description = "$referentiel->description";
        }
		
        if ($course->format == "weeks" or $course->format == "topics") {
            $table->data[] = array ($referentiel->section, $link, $description);
        } else {
            $table->data[] = array ($link, $description);
        }
    }
    echo "<br />";
	if ($s_version!=''){
	echo "<p align='right'>".get_string("version", "referentiel").'<a href="./info_module_referentiel.html" target="_blank"><i>'.$s_version."</i></a></p><br />\n";
	}

    print_table($table);

/// Finish the page

    print_footer($course);

?>
