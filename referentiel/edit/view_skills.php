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
    * @subpackage referentiel
    * @author   Valery Fremaux <valery.fremaux@club-internet.fr>
    * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
    * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
    *
    */


    /// Security 

    if (!defined('MOODLE_INTERNAL')) die("You cannot directly invoke this script");

    /**
    * Global context
    * Important : leave this here, before any call to controllers.
    */

    // this ensures keeping current domain id safe even when not propagating it.
    $currentdomainid = 0 + optional_param('domainid', @$SESSION->currentdomain, PARAM_INT);
    $SESSION->currentdomain = $currentdomainid;

    $result = 0;

    if (!empty($action)){
	    $result = include_once('view_skills.controller.php');
	}
	
	if($result == -1){
	    return;
	}

    $domains = get_records_menu('referentiel_domain', 'referentielid', $referentiel->referentielid, 'sortorder', 'id,code');
    
    echo '<form name="domainselect" method="GET">';
    echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />";
    echo "<input type=\"hidden\" name=\"tab\" value=\"$tab\" />";
    choose_from_menu($domains, 'domainid', $currentdomainid, get_string('alldomains', 'referentiel'), "document.forms['domainselect'].submit();");
    echo '</form>';
    
    if ($currentdomainid){
    	echo("<p align=\"right\"><a href=\"view.php?id={$id}&amp;tab=skills&amp;what=add\">".get_string('addskill', 'referentiel').'</a></p>');
    } else {
        echo('<p align="right">');
        print_string('chooseadomain', 'referentiel');
        echo ('</p>');
    }

	$skills = referentiel_get_all_skills($currentdomainid);
	if(!empty($skills)){
		$table->head = array(	'', "<b>".get_string('code', 'referentiel')."</b>", 
									"<b>".get_string('description', 'referentiel')."</b>",
									"<b>".get_string('itemcount', 'referentiel')."</b>",
									"<b></b>");
		$table->width = "100%";
		$table->align = array('center', 'left', 'left', 'center', 'center');
		$table->size = array('5%', '5%', '60%', '10%', '20%');
		$table->data = array();
		
		//Mise en forme des données pour la vue générale
		foreach($skills as $skill){

		    $view = array();
			$view[] = "<input type=\"checkbox\" name=\"skillid[]\" value=\"$skill->id\" />";
			$view[] = "<a href=\"{$CFG->wwwroot}/mod/referentiel/edit/view.php?id={$id}&amp;tab=items&amp;skillid={$skill->id}\">$skill->code</a>";
			$view[] = $skill->description;
			$view[] = count($skill->items);
			
			$deletestr = get_string('delete');
			$updatestr = get_string('update');
			$viewstr = get_string('view');
			$cmds = "<a href=\"{$CFG->wwwroot}/mod/referentiel/edit/view.php?id={$id}&amp;tab=skills&amp;what=read&amp;skillid={$skill->id}\" title=\"$viewstr\" ><img src=\"$CFG->wwwroot/pix/t/preview.gif\"></a>";
			$cmds .= " <a href=\"{$CFG->wwwroot}/mod/referentiel/edit/view.php?id={$id}&amp;tab=skills&amp;what=update&amp;skillid={$skill->id}\" title=\"$updatestr\" ><img src=\"$CFG->wwwroot/pix/t/edit.gif\"></a>";
			$cmds .= " <a href=\"{$CFG->wwwroot}/mod/referentiel/edit/view.php?id={$id}&amp;tab=skills&amp;what=delete&amp;skillid={$skill->id}\" title=\"$deletestr\" ><img src=\"$CFG->wwwroot/pix/t/delete.gif\"></a>";
			$view[] = $cmds;
			
			$table->data[] = $view;
		}
		referentiel_print_group_control_form('skills', $table, $id);
	}

?>