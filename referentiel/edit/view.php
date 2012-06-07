<?php

    /**
    * Moodle - Modular Object-Oriented Dynamic Learning Environment
    *          http://moodle.org
    * Copyright (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
    *
    * This program is free software: you can redistribute it and/or modify
    * it under the terms of the GNU General Public License as published by
    * the Free Software Foundation, either version 2 of the License, or
    * (at your option) any later version.
    *
    * This program is distributed in the hope that it will be useful,
    * but WITHOUT ANY WARRANTY; without even the implied warranty of
    * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    * GNU General Public License for more details.
    *
    * You should have received a copy of the GNU General Public License
    * along with this program.  If not, see <http://www.gnu.org/licenses/>.
    *
    * @package    moodle
    * @subpackage mod-referentiel
    * @author     Valery Fremaux <valery@valeisti.com>
    * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
    * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
    *
    */
 
    /**
    * Requires and includes
    */
    require_once('../../../config.php');
    require_once('editlib.php');
    require_once('../lib.php');
    require_once($CFG->libdir.'/formslib.php');

    /// Context and access control
    $id = optional_param('id', 0, PARAM_INT); // the cm instance id
  	$d = optional_param('d', 0, PARAM_INT); // Referentiel ID

  if ($d) {
        if (! $referentiel = get_record('referentiel', 'id', $d)) {
            print_error('Certification instance is incorrect');
        }
		if (! $course = get_record('course', 'id', $referentiel->course)) {
	            print_error('Course is misconfigured');
    	}
		if (! $cm = get_coursemodule_from_instance('referentiel', $referentiel->id, $course->id)) {
    	        print_error('Course Module ID is incorrect');
		}
		$id = $cm->id;
	} elseif ($id) {
        if (! $cm = get_coursemodule_from_id('referentiel', $id)) {
        	print_error('Course Module ID is incorrect');
        }
        if (! $course = get_record('course', 'id', $cm->course)) {
            print_error('Course is misconfigured');
        }
        if (! $referentiel = get_record('referentiel', 'id', $cm->instance)) {
            print_error('Certification instance is incorrect');
        }
    } else {
    	die('Module error');
    }

	// lien vers le referentiel lui-meme
	if (!empty($referentiel->referentielid)){
	    if (!$referentiel_referentiel = get_record('referentiel_referentiel', 'id', $referentiel->referentielid)) {
			print_error('Referentiel id is incorrect');
    	}
    } else {
		// rediriger vers la creation du referentiel
		require_login($course->id);
        if (!isloggedin() or isguest()) {
            redirect("$CFG->wwwroot/course/view.php?id=$course->id");
        }

		$returnlink = "$CFG->wwwroot/mod/referentiel/add.php?id=$cm->id&amp;sesskey=".sesskey();
        redirect($returnlink);
	}

    $context = get_context_instance(CONTEXT_MODULE, $id);
        
    if (!$site = get_site()) {
    	redirect($CFG->wwwroot .'/'. $CFG->admin .'/index.php');
    }

    if (!has_capability('mod/referentiel:read', $context)){
    	redirect($CFG->wwwroot .'/index.php');
    } 	   

    /// get MVC inputs

    $action = optional_param('what', '', PARAM_TEXT);
	$tab = optional_param('tab', '', PARAM_TEXT); // current tab - defaults to domains
	$screen = optional_param('screen', '', PARAM_TEXT); // current screen - default to domains

    /// memorizes current view - typical session switch
    if (!empty($tab)){
        $_SESSION['currenttab'] = $tab;
    } elseif (empty($_SESSION['currenttab'])) {
        $_SESSION['currenttab'] = 'search';
    }
    $tab = $_SESSION['currenttab'];
    
    /// memorizes current screen - typical session switch
    if (!empty($screen)){
        $_SESSION['currentscreen'] = $screen;
    } elseif (empty($_SESSION['currentscreen'])) {
        $_SESSION['currentscreen'] = '';
    }
    $screen = $_SESSION['currentscreen'];
    // !PART OF MVC Implementation

    /// Page preparation
    
	$navlinks[] = array('name' => get_string('modname', 'referentiel'),
                        'url' => '',
                        'type' => 'title');
                        
    $navigation  = build_navigation($navlinks, $cm);

    print_header(strip_tags($site->fullname), $site->fullname, $navigation, '', '<meta name="description" content="'. s(strip_tags($site->summary)) .'">', true, '', '');

		
	if (isset($mode)){
		if ($mode == 'editreferentiel') {
			$currenttab = 'editreferentiel';
    	} else {
			$currenttab = 'listreferentiel';
    	}
	}
	// Onglets
	
    include('../tabs.php');
	
    /* echo '<table cellspacing="2" cellpadding="2" border="0" width="95%" class="boxaligncenter">';  
    echo '<tr>';

	/// Print out the tabs

    echo '<td>'; */
    $selected = null;
    $activated = null;
    $tabrows = array();
    if (!preg_match("/domains|skills|items/", $tab)) $tab = 'domains';
    // $tabrows[0][] = new tabobject('search', $CFG->wwwroot."/mod/referentiel/edit/view.php?tab=search&id={$id}", get_string('search', 'referentiel'));
    $tabrows[0][] = new tabobject('domains', $CFG->wwwroot."/mod/referentiel/edit/view.php?tab=domains&id={$id}", get_string('domains', 'referentiel'));
    $tabrows[0][] = new tabobject('skills', $CFG->wwwroot."/mod/referentiel/edit/view.php?tab=skills&id={$id}", get_string('skills', 'referentiel'));
    $tabrows[0][] = new tabobject('items', $CFG->wwwroot."/mod/referentiel/edit/view.php?tab=items&id={$id}", get_string('items', 'referentiel'));

    print_tabs($tabrows, $tab); 
    
    // if (debugging()) echo "[[$tab:$action]]";

	switch($tab){
/*
		case 'internships_search':
            include 'view_search.php';
			break;
*/
	    case 'domains':
	        include 'view_domains.php';
	    	break;		    	
	    case 'skills':
	        include 'view_skills.php';
	    	break;
	     case 'items':
	        include 'view_items.php';
	        break;
	}
	
    // echo '</td> </tr> </table>';	    	    
    print_footer(); 
?>