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
    * @package    mod-referentiel
    * @autor   Valery Fremaux <valery.fremaux@club-internet.fr>
    * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
    * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
    *
    */

    /// Security

    if (!defined('MOODLE_INTERNAL')) die("You cannot directly invoke this script");

    /// Master controller

    $result = 0;

    if (!empty($action)){
        $result = include_once('view_domains.controller.php');
    }
    
    if($result == -1){ 
        // if controller already output the screen we might jump
        return;
    }
    
    $adddomainstr = (empty($referentiel->domainlabel)) ? get_string('adddomain', 'referentiel') : get_string('adda', 'referentiel', $referentiel->domainlabel) ;

	echo("<p align=\"right\"><a href=\"view.php?id={$id}&amp;tab=domains&amp;what=add\" >".$adddomainstr.'</a></p>');
	
	$data = referentiel_get_all_domains($referentiel->referentielid);

    if(!empty($data)){
		$table->head = array(	'', "<b>".get_string('code', 'referentiel')."</b>", 
		    						"<b>".get_string('description', 'referentiel')."</b>",
		    						"<b>".get_string('numberofskills', 'referentiel')."</b>",
									"<b></b>");
		$table->width = "100%";
		$table->align = array('center', 'left', 'left', 'left', 'center');
		$table->size = array('5%', '5%', '60%', '10%', '20%');
		$table->data = array();

        $seelpstr = get_string('seeskills');
        
		foreach($data as $domain){

			$view = array();

            $skillscount = count_records('referentiel_skill', 'domainid', $domain->id);

            if ($skillscount){
    		    $domain->code = "<a href=\"{$CFG->wwwroot}/mod/referentiel/edit/view.php?id={$id}&amp;tab=skills&amp;domainid={$domain->id}\" title\"$seelpstr\">{$domain->code}</a>";
    		}

			$view[] = "<input type=\"checkbox\" name=\"domainid[]\" value=\"$domain->id\" />";
			$view[] = "<a href=\"{$CFG->wwwroot}/mod/referentiel/edit/view.php?id={$id}&amp;tab=skills&amp;domainid={$domain->id}\">$domain->code</a>";
			$view[] = $domain->description;
			$deletestr = get_string('delete');
			$updatestr = get_string('update');
			$viewstr = get_string('view');
			$view[] = $skillscount;
			$cmds = "<a href=\"{$CFG->wwwroot}/mod/referentiel/edit/view.php?id={$id}&amp;tab=domains&amp;what=read&amp;domainid=$domain->id\" title=\"$viewstr\" ><img src=\"{$CFG->wwwroot}/pix/t/preview.gif\"></a>";
			$cmds .= " <a href=\"{$CFG->wwwroot}/mod/referentiel/edit/view.php?id={$id}&amp;tab=domains&amp;what=update&amp;domainid=$domain->id\" title=\"$updatestr\" ><img src=\"{$CFG->wwwroot}/pix/t/edit.gif\"></a>";
			$cmds .= " <a href=\"{$CFG->wwwroot}/mod/referentiel/edit/view.php?id={$id}&amp;tab=domains&amp;what=delete&amp;domainid=$domain->id\" title=\"$deletestr\" ><img src=\"{$CFG->wwwroot}/pix/t/delete.gif\"></a>";
			$view[] = $cmds;
			$table->data[]= $view;
		}
		referentiel_print_group_control_form('domains', $table, $id);
    } else {
		print("<p style=\"text-align: center; font-style: italic;\">".get_string('nodomain', 'referentiel')."</p>");
	}
?>