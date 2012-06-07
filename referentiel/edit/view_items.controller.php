<?php

/**
* Master controler for items
*/

/**
* Security
*/
if (!defined('MOODLE_INTERNAL')) die("You cannot directly invoke this script");

$returnurl = "{$CFG->wwwroot}/mod/referentiel/edit/view.php?id={$id}&amp;tab=items";

/**
* Requires and includes
*/

switch($action){
    /***************************** display a full skill sheet *********************/ 


	case "read":
        $skillid = required_param('itemid', PARAM_INT);

		if (!$skill = referentiel_get_full_item($itemid)) {
			error('Item ID was incorrect');
			die;
		}

		// Displaying data

		print_heading($item->code);
		print("<p align=\"right\"><a href=\"view.php?id={$id}&amp;tab=items\">".get_string('go_listofcourses', 'referentiel').'</a></p>');

        include('html/item.html');			

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
			
	/************************ add an item ************************************/
	case 'add':
		require_once('classes/form_item.class.php');

		$newskill = new ITem_Form('add', "{$CFG->wwwroot}/mod/referentiel/edit/view.php?id={$id}&tab=items&what=add");    // WARNING : no &amp escaping, Form constructor does
		
		if ($newskill->is_cancelled()){
			redirect($returnurl);
		}
		
		if ($data = data_submitted()){
    	// if there is some error
	        if ($data->code == '') {
	        	print_error('err_skillcode', 'referentiel', "{$returnurl}&amp;what=add");
	        } else {
            	//data was submitted from this form, process it
                $item->code = clean_param($data->code, PARAM_TEXT);
                $item->description = clean_param($data->description, PARAM_CLEANHTML);
                $item->type = clean_param($data->type, PARAM_TEXT);
                $item->weight = clean_param($data->weight, PARAM_NUMBER);
                $item->footprint = clean_param($data->footprint, PARAM_INT);
                $item->referentielid = $referentiel->referentielid;
                $item->skillid = $currentskillid;
	            $item = addslashes_recursive($item);
	            
	            if (get_record_select('referentiel_skill_item', " code = '$data->code' AND skillid = $currentskillid ")){
	            	//If the code already exist in the database
	                print_error('err_duplicate', 'referentiel', "{$returnurl}&amp;what=add");
	            } else {
	            	$lastorder = get_field('referentiel_skill_item', ' MAX(sortorder) ', 'skillid', $currentskillid);
	            	$item->sortorder = $lastorder + 1;
	            	$itemid = referentiel_add_item($item, $returnurl); // do not redirect here, we need new id
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

        $itemid = required_param('itemid', PARAM_INT);

		// Check the item
	    if (!$item = get_record('referentiel_skill_item', 'id', $itemid)) {
	        error('Item ID was incorrect');
	    }

		require_once('classes/form_item.class.php');
		$itemform = new Item_Form('update', "{$CFG->wwwroot}/mod/referentiel/edit/view.php?id={$id}&tab=items&what=update&itemid={$itemid}");    // WARNING : no &amp escaping, Form constructor does

		if ($itemform->is_cancelled()){
			redirect($returnurl);
		}

		// data was submitted from this form, process it
    	if ($data = $itemform->get_data()){
    	    $itemrec = new StdClass;
        	$itemrec->id = $itemid;
            $itemrec->code = clean_param($data->code, PARAM_TEXT);
        	$itemrec->description = clean_param($data->description, PARAM_CLEANHTML);		
            $itemrec->type = clean_param($data->type, PARAM_TEXT);
            $itemrec->weight = clean_param($data->weight, PARAM_TEXT);
            $itemrec->footprint = clean_param($data->footprint, PARAM_TEXT);
	        $itemrec = addslashes_recursive($itemrec);

	        if (!update_record('referentiel_skill_item', $itemrec)){
	            notice('Could not update Item '.$itemid);
	        }

		} else { // we have to display the form
		    			    
    		$itemform->set_data($item);
			$itemform->display();
			return -1;
		}
    	break;
    	
    /********************************* delete a learning path entry *******************************/
    case "delete":
        $itemid = required_param('itemid', PARAM_INT); // can be array
	    
    	referentiel_delete_item($itemid, $returnurl);
    	redirect($returnurl);

    	break;
}
?>		   		