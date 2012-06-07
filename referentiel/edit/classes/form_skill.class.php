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
    * Defines form to add a learning path
    *
    * @package    referentiel
    * @subpackage classes
    * @author     Emeline Daude <daude.emeline@gmail.com>
    * @reviewer   Valery Fremaux <valery.fremaux@club-internet.fr>
    * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
    * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
    *
    */

    require_once($CFG->libdir.'/formslib.php');

    class Skill_Form extends moodleform {
    
    	function __construct($mode, $action){
    		$this->mode = $mode;
        	parent::__construct($action);
        }
    	
    	function definition() {
    		global $CFG;
    		
    		// Setting variables
    		$mform =& $this->_form;
    		
    		//Adding title and description
    		$mform->addElement('html', print_heading(get_string($this->mode.'skillform', 'referentiel')));
    		
    		// Adding fieldset
    		$attributes = 'size="25" maxlength="64" ';
    		$attributes_selects = 'rows="1" ';
    		$attributes_comment = 'cols="50" rows="8" ';
    		
    		$mform->addElement('text', 'code', get_string('code', 'referentiel'), $attributes);
    		$mform->addElement('htmleditor', 'description', get_string('description', 'referentiel'), $attributes_comment);
    		
    		$mform->addRule('code', null, 'required');
    		$mform->addRule('description', null, 'required');
    		
    		// Adding submit and reset button
            $buttonarray = array();
            $buttonarray[] = &$mform->createElement('submit', 'go_submit', get_string('submit', 'referentiel'));
            $buttonarray[] = &$mform->createElement('cancel', 'go_cancel', get_string('cancel'));
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    
            $mform->closeHeaderBefore('buttonar');	
    	}
    }
?>