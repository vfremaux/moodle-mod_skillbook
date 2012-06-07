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
    *
    * @package    block-prf-catalogue
    * @subpackage classes
    * @author     Emeline Daude <daude.emeline@gmail.com>
    * @reviewer   Valery Fremaux <valery.fremaux@club-internet.fr>
    * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
    * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
    *
    */

    /**
    * A filter object stores all usefulll information to provide filtering
    * elements to an array of results.
    * The filter object provides HTML filtering form output, and
    * the relevant SQL clause to filter queries. 
    */
    class Form_Filter{
    
        /**
        * the parameter name for form transmission
        */
        var $name;
    
        /**
        * the db field in the underlying query that will be used for filtering
        * through a WHERE clause
        */        
        var $dbfield;
    
        /**
        * the key/value pairs to provide to a filtering select
        */
        var $menuoptions;
    
        /**
        * All excluded filters should be reset when changing this filter's value
        */
        var $excludes;
        
        function __construct($name, $dbf, $options){
            $this->name = $name;
            $this->dbfield = $dbf;
            $this->menuoptions = $options;
            $this->excludes = array();
        }
        
        /**
        * Provides the HTML output.
        * Exclude controls will provide the javascript to
        * ensure mechanical exclusion between filters
        * @param array $contexts key/value pairs for keeping the CGI external context stable
        * @return string
        */
        public function print_html($contexts = null){    
            $excludecontrol = '';
            if (!empty($this->excludes)){
                foreach($this->excludes as $exclude){
                    $excludecontrols[] = "filter_reset('$exclude');"; 
                }
                $excludecontrol = implode('', $excludecontrols);
            }
            
            $nofilterstr = get_string('nofilter', 'block_prf_catalogue');
            $filternamestr = get_string($this->name, 'block_prf_catalogue');
            echo "&nbsp;&nbsp;&nbsp; ".$filternamestr.' : ';
            echo "<form name=\"form_{$this->name}\" style=\"display:inline\" method=\"get\">";
    
            // get CGI content from environement that should be preserved
            if (!empty($contexts)){
                foreach($contexts as $key => $value){
                    echo "<input type=\"hidden\" name=\"$key\" value=\"$value\" />";
                }
            }
    
            echo "<select name=\"{$this->name}\" onchange=\"$excludecontrol ; this.form.submit(); \" >";
            if (!empty($this->menuoptions)){
                echo "<option value=\"0\">$nofilterstr</option>";
                foreach($this->menuoptions as $key => $value){
                    $selected = ($key == optional_param($this->name, null, PARAM_TEXT)) ? 'selected="selected"' : '' ;
                    echo "<option value=\"$key\" $selected >$value</option>";
                }
            }
            echo "</select></form>";
        }
        
        /**
        * Exclusion accessor 
        * @param array $excludes array of filter names.
        */
        function setExcludes($excludes){
            if (!is_array($excludes)){
                return;
            }
            $this->excludes = $excludes;
        }
        
        /**
        *
        */
        function getFilterClause(){
            $filtervalue = optional_param($this->name, '', PARAM_TEXT);
            if (!empty($filtervalue)){
                return " {$this->dbfield} = '{$filtervalue}' ";
            }
            return '';
        }
    }
?>