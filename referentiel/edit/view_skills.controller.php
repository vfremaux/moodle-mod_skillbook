<?php

/**
* Master controler for skills
*/

/**
* Security
*/
if (!defined('MOODLE_INTERNAL')) die("You cannot directly invoke this script");

$returnurl = "{$CFG->wwwroot}/mod/referentiel/edit/view.php?id={$id}&amp;tab=skills";

/**
* Requires and includes
*/

switch($action){
    /***************************** display a full skill sheet *********************/ 


	case "read":
        $skillid = required_param('skillid', PARAM_INT);

		if (!$skill = get_full_skill($skillid)) {
			error('LP ID was incorrect');
			die;
		}

		// Displaying data

		print_heading($skill->title);
		print("<p align=\"right\"><a href=\"view.php?id={$id}&amp;tab=skills\">".get_string('go_listofcourses', 'referentiel').'</a></p>');

        include('html/skill.html');			

		unset($table);
		$table->head = array('<b>'.get_string('code', 'referentiel').'</b>', 
								"<b>".get_string('description', 'referentiel')."</b>",
								"<b>".get_string('weight', 'referentiel')."</b>",
								"<b>".get_string('footprint', 'referentiel')."</b>",
								'');
		$table->width = "95%";
		$table->align = array('left', 'left', 'left', 'left', 'left');
		$table->size = array('10%', '50%', '10%', '10%', '20%' );
		$table->data = array();

        if (!empty($skill->items)){
        	foreach($skill->items as $item){
				
				$view = array();
				$view[] = $item->code;
				$view[] = $item->description;
				$view[] = $item->weight;
				$view[] = $item->footprint;
				
				$table->data[] = $view;
        	}
    		echo '<p align="center">';
    		print_table($table);
    		echo '</p>';
    	} else {
    	    echo '<p>';
    	    print_string('noitems', 'referentiel');
    	    echo '</p>';
    	}
						
        return -1;			
			
	/************************ add a skill project ************************************/
	case 'add':
		require_once('classes/form_skill.class.php');

		$newskill = new Skill_Form('add', "{$CFG->wwwroot}/mod/referentiel/edit/view.php?id={$id}&tab=skills&what=add");    // WARNING : no &amp escaping, Form constructor does
		
		if ($newskill->is_cancelled()){
			redirect($returnurl);
		}
		
		if ($data = data_submitted()){
    	// if there is some error
	        if ($data->code == '') {
	        	print_error('err_skillcode', 'referentiel', "{$returnurl}&amp;what=add");
	        } else {
            	//data was submitted from this form, process it
                $skill->code = clean_param($data->code, PARAM_TEXT);
                $skill->description = clean_param($data->description, PARAM_CLEANHTML);
                $skill->referentielid = $referentiel->referentielid;
                $skill->domainid = $currentdomainid;
	            $skill = addslashes_recursive($skill);
	            
	            if (get_record_select('referentiel_skill', " code = '$data->code' AND domainid = $currentdomainid ")){
	            	//If the title already exist in the database
	                print_error('err_duplicate', 'referentiel', "{$returnurl}&amp;what=add");
	            } else {
	            	$lastorder = get_field('referentiel_skill', ' MAX(sortorder) ', 'domainid', $currentdomainid);
	            	$skill->sortorder = $lastorder + 1;
	            	$skillid = referentiel_add_skill($skill, null); // do not redirect here, we need new id
	            }

                redirect($returnurl);
	        }
		} else {
			$newskill->display();
			return -1;
		}
		break;

    /************************************ updates a skill entry *********************/ 
    case "update":

        $skillid = required_param('skillid', PARAM_INT);

		// Check the skill
	    if (!$skill = get_record('referentiel_skill', 'id', $skillid)) {
	        error('Skill ID was incorrect');
	    }

		require_once('classes/form_skill.class.php');
		$skillform = new Skill_Form('update', "{$CFG->wwwroot}/mod/referentiel/edit/view.php?id={$id}&tab=skills&what=update&skillid={$skillid}");    // WARNING : no &amp escaping, Form constructor does

		if ($skillform->is_cancelled()) redirect($returnurl);

		// data was submitted from this form, process it
    	if ($data = data_submitted()){
    	    $skillrec = new StdClass;
        	$skillrec->id = $skillid;
            $skillrec->code = clean_param($data->code, PARAM_TEXT);
        	$skillrec->description = clean_param($data->description, PARAM_CLEANHTML);		
	        $skillrec = addslashes_recursive($skillrec);

	        if (!update_record('referentiel_skill', $skillrec)){
	            notice('Could not update Skill '.$skillid);
	        }

		} else { // we have to display the form
		    			    
    		$skillform->set_data($skill);
			$skillform->display();
			return -1;
		}
    	break;
    	
    /********************************* delete a learning path entry *******************************/
    case "delete":
        $skillid = required_param('skillid', PARAM_INT); // Can be array

    	referentiel_delete_skill($skillids, $returnurl);
    	redirect($returnurl);

    	break;
}
?>		   		