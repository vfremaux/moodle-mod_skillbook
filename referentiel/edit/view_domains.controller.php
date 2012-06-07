<?php

/**
* Master Controler for domain
*/

/**
* Security
*/
if (!defined('MOODLE_INTERNAL')) die("You cannot directly invoke this script");

$returnurl = "{$CFG->wwwroot}/mod/referentiel/edit/view.php?id=$id&amp;tab=domains";

/**
* Requires and includes
*/

switch($action){
    /*************************************** displays a single domain ***************************/
    
	case 'read':
        $domainid = required_param('domainid', PARAM_INT);

		if (!$domain = get_record('referentiel_domain', 'id', $domainid)) {
				error('Domain ID was incorrect');
				die;
		}

		//Affichage des infos
		print_heading(get_string('domain', 'referentiel').': '.$domain->code);
		print("<p align=\"right\"><a href=\"{$returnurl}\">".get_string('go_listofdomains', 'referentiel').'</a></p>');
		include('html/domain.html');

		if($skills = referentiel_get_all_skills($domain->id)){			
			//Affichage du listing des skills
			$table->head = array(	"<b>".get_string('code', 'referentiel')."</b>", 
											"<b>".get_string('description', 'referentiel')."</b>",
						 					"<b></b>");
			$table->width = "90%";
			$table->align = array('left', 'left', 'left');
			$table->size = array('15px', '70px', '15px');
			$table->data = array();

			//Mise en forme des données pour la vue générale
    		foreach($skills as $skill){
    		    $view = array();
    			$view[] = $skill->code;
    			$view[] = $skill->description;
    			$view[] = "<a href=\"$CFG->wwwroot.'/mod/referentiel/edit/view.php?id={$id}&amp;tab=skills&amp;what=read&amp;skillid=$skill->id\"><img src=\"$CFG->wwwroot/pix/t/preview.gif\"></a>";

				$table->data[] = $view;
			}
			print_table($table);
		} else {
			print_string('noskill', 'referentiel');
		}
		return -1;
    /*************************************** adds a new domain ***************************/
    case 'add':
		require_once($CFG->dirroot.'/mod/referentiel/edit/classes/form_domain.class.php');

		$domainform = new Domain_Form('add', "{$returnurl}&what=add");    
		
		if ($domainform->is_cancelled()) redirect($returnurl);
					
		if ($data = $domainform->get_data()){
    	// if there is some error
	        if ($data->code == '') {
	        	print_error('err_domaincode', 'referentiel', "{$returnurl}&amp;what=add");
	        }
	    	else if ($data->description == '') {
	        	print_error('err_domaindesc', 'referentiel', "{$returnurl}&amp;what=add");
	        } else {
            	//data was submitted from this form, process it
                $domain->code = clean_param($data->code, PARAM_TEXT);
                $domain->referentielid = $referentiel->referentielid;
	        	$domain->description = clean_param($data->description, PARAM_CLEANHTML);					           
	            $domain = addslashes_recursive($domain);
	            	            
	            if (get_record('referentiel_domain', 'code', $data->code)){
	            	//If the title already exist in the database
	                print_error('err_codedomainexists', 'referentiel', "{$returnurl}&amp;what=add");
	            } else {
	            	$lastorder = get_field('referentiel_domain', ' MAX(sortorder) ', 'referentielid', $referentiel->referentielid);
	            	$domain->sortorder = $lastorder + 1;
	            	referentiel_add_domain($domain, $returnurl);
	            }
	        }
		} else {
			$domainform->display();
			return -1;
		}
        break;
    /********************************** Updates a domain **************************************/
    case 'update':
    	//If a domain is selected
    	$domainid = required_param('domainid', PARAM_INT);

		// Check the domain
	    if (!$domain = get_record('referentiel_domain', 'id', $domainid)) {
	        error('domain ID was incorrect');
	    }

		require_once('classes/form_domain.class.php');
		$domainform = new Domain_Form('update',"{$CFG->wwwroot}/mod/referentiel/edit/view.php?id={$id}&tab=domains&domainid={$domainid}");    
		
		if ($domainform->is_cancelled()) redirect($returnurl);

		// data was submitted from this form, process it
    	if ($data = $domainform->get_data()){
        	$domain->id = $domainid;
            $domain->code = clean_param($data->code, PARAM_TEXT);
	        $domain->description = clean_param($data->description, PARAM_CLEANHTML);					           
	        $domain = addslashes_recursive($domain);
	        referentiel_update_domain($domain, $returnurl);
		} else {
		    //no data submitted : print the form
    		$domainform->set_data($domain);
			$domainform->display();
			return -1;
		}
    	break;
	    	
/// Tab DELETE	    	
    case "delete":
    	$domainid = required_param('domainid', PARAM_INT); // can be array

    	referentiel_delete_domain($domainid, $returnurl);
		redirect($returnurl);

    	break;
}	