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
    $currentskillid = 0 + optional_param('skillid', @$SESSION->currentskill, PARAM_INT);
    $SESSION->currentskill = $currentskillid;

    $result = 0;

    if (!empty($action)){
	    $result = include_once('view_items.controller.php');
	}
	
	if($result == -1){
	    return;
	}

    $skillsopts = get_records_menu('referentiel_skill', 'domainid', $currentdomainid, 'sortorder', 'id,code');
    
    echo '<form name="skillselect" method="GET">';
    echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />";
    echo "<input type=\"hidden\" name=\"tab\" value=\"$tab\" />";
    choose_from_menu($skillsopts, 'skillid', $currentskillid, get_string('allskills', 'referentiel'), "document.forms['skillselect'].submit();");
    echo '</form>';
    
    if ($currentskillid){
    	echo("<p align=\"right\"><a href=\"view.php?id={$id}&amp;tab=items&amp;what=add\">".get_string('additem', 'referentiel').'</a></p>');
    } else {
        echo('<p align="right">');
        print_string('chooseaskill', 'referentiel');
        echo ('</p>');
    }

	$items = referentiel_get_all_items($currentskillid);
	if(!empty($items)){
		$table->head = array(	'', "<b>".get_string('code', 'referentiel')."</b>", 
									"<b>".get_string('description', 'referentiel')."</b>",
									"<b>".get_string('type', 'referentiel')."</b>",
									"<b>".get_string('weight', 'referentiel')."</b>",
									"<b>".get_string('footprint', 'referentiel')."</b>",
									"<b></b>");
		$table->width = "100%";
		$table->align = array('left', 'left', 'left', 'left', 'left', 'center');
		$table->size = array('5%', '5%', '50%', '10%', '10%', '10%', '10%');
		$table->data = array();
		
		//Mise en forme des données pour la vue générale
		foreach($items as $item){

		    $view = array();
			$view[] = "<input type=\"checkbox\" name=\"itemid[]\" value=\"$item->id\" />";
			$view[] = "$item->code";
			$view[] = $item->description;
			$view[] = $item->type;
			$view[] = $item->weight;
			$view[] = $item->footprint;
			
			$deletestr = get_string('delete');
			$updatestr = get_string('update');
			$viewstr = get_string('view');
			$cmds = "<a href=\"{$CFG->wwwroot}/mod/referentiel/edit/view.php?id={$id}&amp;tab=items&amp;what=read&amp;itemid={$item->id}\" title=\"$viewstr\" ><img src=\"$CFG->wwwroot/pix/t/preview.gif\"></a>";
			$cmds .= " <a href=\"{$CFG->wwwroot}/mod/referentiel/edit/view.php?id={$id}&amp;tab=items&amp;what=update&amp;itemid={$item->id}\" title=\"$updatestr\" ><img src=\"$CFG->wwwroot/pix/t/edit.gif\"></a>";
			$cmds .= " <a href=\"{$CFG->wwwroot}/mod/referentiel/edit/view.php?id={$id}&amp;tab=items&amp;what=delete&amp;itemid={$item->id}\" title=\"$deletestr\" ><img src=\"$CFG->wwwroot/pix/t/delete.gif\"></a>";
			$view[] = $cmds;
			
			$table->data[] = $view;
		}
		referentiel_print_group_control_form('items', $table, $id);
	}

?>