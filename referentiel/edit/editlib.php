<?php

/**
* add a domain securely
* @uses $CFG
* @param object $project
* @param string $redirect
*/
function referentiel_add_domain($domain, $redirect){
    
	if (get_record('referentiel_domain', 'code', $domain->code, 'referentielid', $domain->referentielid)){
    	print_error('err_duplicatecode', 'referentiel', $redirect."&amp;what=add");
	}    
	if($newdomainid = insert_record('referentiel_domain', $domain)){
		print_string('newdomainrecorded', 'referentiel');
		if (!empty($redirect))
		    redirect($redirect);
		return $newdomainid;
	} else {
    	print_error('err_domainrecorded', 'referentiel', $redirect."&amp;what=add");
    }    	
}

/**
* Add a skill securely checking unicity of code in domain
* @uses $CFG
* @param object $lp
* @param string $redirect
* old : add_course
*/
function referentiel_add_skill($skill, $redirect){
    global $CFG;

	if (get_record('referentiel_skill', 'code', $skill->code, 'domainid', $skill->domainid)){
    	print_error('err_duplicatecode', 'referentiel', $redirect."&amp;what=add");
	}    
	if($newskillid = insert_record('referentiel_skill', $skill)){
		print(get_string('newskillrecorded', 'referentiel'));
		if (!empty($redirect))
		    redirect($redirect);
		return $newskillid;
	} else {
    	print_error('err_skillrecorded', 'referentiel', $redirect."&amp;what=add");
    }    	
}

/**
*
*/
function referentiel_add_item($item, $redirect){
	
	if (get_record('referentiel_skill_item', 'code', $item->code, 'skillid', $item->skillid)){
    	print_error('err_duplicatecode', 'referentiel', $redirect."&amp;what=add");
	}    
	if ($newitemid = insert_record('referentiel_skill_item', $item)){
		print(get_string('newitemrecorded', 'referentiel'));
		if (!empty($redirect)) 
			redirect($redirect);
		return $newitemid;
	} else {
    	print_error('err_itemrecorded', 'referentiel', $redirect.'&amp;what=add');
    }
}

/**
* Updates securely a domain
* @param object $project
* @param string $redirect
*/
function referentiel_update_domain($domain, $redirect){

	if (get_record_select('referentiel_domain', " code = $domain->code AND referentielid = $domain->referentielid AND id != $domain->id ")){
    	print_error('err_duplicatecode', 'referentiel', $redirect."&amp;what=update&amp;domainid={$domain->id}");
	}    
	if (update_record('referentiel_domain', $domain)){
        print_string('domainupdated', 'referentiel');
		redirect($redirect);
    } else {
    	print_error('err_domainupdated', 'referentiel', $redirect);
    }
}

/**
* Updates securely a domain
* @param object $project
* @param string $redirect
*/
function referentiel_update_skill($skill, $redirect){

	if (get_record('referentiel_skill', 'code', $skill->code, 'domainid', $skill->domainid)){
    	print_error('err_duplicatecode', 'referentiel', $redirect."&amp;what=add");
	}    
	if (update_record('referentiel_skill', $skill)){
        print_string('skillupdated', 'referentiel');
		redirect($redirect);
    } else {
    	print_error('err_skillupdated', 'referentiel', $redirect);
    }
}

/**
* updates securely an item
*/
function referentiel_update_item($item, $redirect){

	if (get_record('referentiel_skill_item', 'code', $item->code, 'skillid', $item->skillid)){
    	print_error('err_duplicatecode', 'referentiel', $redirect."&amp;what=add");
	}    
	if (update_record('referentiel_skill_item', $item)){
        print_string('itemupdated', 'referentiel');
		redirect($redirect);
    } else {
    	print_error('err_itemupdated', 'referentiel', $redirect);
    }
}

/**
* DO NOT delete user records 
*/
function referentiel_delete_domain($ids, $redirect){
    global $CFG;

    if (!is_array($ids)){
    	$idlist = "'$id'";
    } else {
    	$idlist = implode("','", $ids);
    }

    if (!$domains = get_records_select('referentiel_domain', " id IN ('$idlist') ")){
        return;
    }

	if (delete_records_select('referentiel_domain', " id IN ('$idlist') ")){
	    
	    // deleting all skills attached to this domain
	    if ($skilllist = get_records_select_menu('referentiel_skill', " domainid IN ('$idlist') ", '', 'id,code')){
	        // print_object($lplist);
	        delete_records('referentiel_skill', " domainid IN ('$idlist') ");
	    }

	    if ($skilllist){
	    	$skillids = array_keys($skilllist);
	    	$itemlist = get_records_menu_list('referentiel_skill_item', 'skillid', $skillids, '', 'id,code');
	    	$skillidslist = imlode("','", $skillids);
	        // print_object($skillidslist);
	        delete_records_select('referentiel_skill_items', " skillid IN ('$skillidslist') ");
	    }
	    
	    // continue deleting dependancies in tasks and activity mappings
	    
	    // printing final status
		print_string('domaindeleted', 'referentiel');
	} else {
    	print_error('err_domaindeleted', 'referentiel', $redirect);
    }	
}

/**
* DO NOT delete user records 
* silent errors on all failures...
*/
function referentiel_delete_skill($ids, $redirect){

    if (!is_array($ids)){
    	$idlist = "'$id'";
    } else {
    	$idlist = implode("','", $ids);
    }

    if (!$skills = get_records_select('referentiel_skill', " id IN ('$idlist') ")){
        return;
    }

	if (delete_records_select('referentiel_skill', " id IN ('$idlist') ")){
	    
	    // deleting all items
	    delete_records_select('referentiel_skill_item', " skillid IN ('$idlist') ");

	    // printing final status
		print(get_string('skillsdeleted', 'referentiel'));
	} else {
    	print_error('err_skilldeleted', 'referentiel', $redirect);
    }	
}

/**
* DO NOT delete user records 
*/
function referentiel_delete_item($ids, $redirect){
    global $CFG;
    
    if (!is_array($id)){
    	$idlist = "'$id'";
    } else {
    	$idlist = implode("','", $ids);
    }
    
    if (!$items = get_record_select('referentiel_skill_item', " id IN ('$idlist') ")){
        return;
    }

	foreach($items as $item){
	    if (delete_records_select('referentiel_skill_item', " id IN ('$idlist') ")){
	        
	       // TODO : delete assignations to activities and modules
	    }
	}
}

/**
* get all domains
*
*/
function referentiel_get_all_domains($referentielid){
	
	$domains = get_records('referentiel_domain', 'referentielid', $referentielid, 'sortorder');

	return $domains;	
}

/**
* 
*/
function referentiel_get_all_skills($domainid, $referentielid = '', $param = ''){

    if ($domainid)
    	$skills = get_records('referentiel_skill', 'domainid', $domainid);
    elseif ($referentielid)
    	$skills = get_records('referentiel_skill', 'referentielid', $referentielid);

	$data = array();
	
	switch($param){
		case '':
			if(!empty($skills)){
				foreach($skills as $skill){
					$skill->items = referentiel_get_all_items($skill->id);
				}		
				return $skills;
			}
			return array();
			break;
			
		case 'code':
			if(!empty($skills)){
				foreach($skills as $skill){
					$data[] = $skill->code;
				}		
			}
			break;
	}
	return $data;
}
	
/**
*
*/
function referentiel_get_full_skill($id){

	$skill = get_record('referentiel_skill', 'id', $id);

	$skill->items = get_all_items($skill->id);

	return $skill;
}

/**
 * Get the id of the course
 * @param $by
 * 		possible values are :
 * 		code
 * 		description
 * @param $info
 * @return unknown_type
 * old : get_course_id
 */
function referentiel_get_skill_id($by = '', $info){
	$skill = get_record('referentiel_skill', $by, $info);
	return $skill->id;	
}



/**
* get the array of all known team assignations grouped by LPs
* @uses $CFG
* @param int $lpid if present will return the team of the lp
* @param int $projectid if present will return the teams for the entire project
*/
function referentiel_get_all_items($skillid = '', $domainid = ''){
    global $CFG;

    if (!empty($skillid)){
    	$items = get_records('referentiel_skill_item', 'skillid', $skillid, 'sortorder');
    } elseif (!empty($domainid)) {
    	if ($skills = get_records_menu('referentiel_skill', 'domainid', $domainid, 'sortorder', 'id, code')){
    	    $list = implode(',', array_keys($skills));
        	$items = get_records_list('referentiel_skill_item', 'skillid', $list, 'skillid,sortorder');
        } else {
            $items = array();
        }        
    } else {
    	$items = get_records('referentiel_skill_item', '', '', 'sortorder');
    }

	if (!$items) return array();
	return $items;	
}


/**
*
*/
function referentiel_print_filters($filters, $contexts = null){
    if (!empty($filters)){
        foreach($filters as $afilter){
            echo ' ';
            $afilter->print_html($contexts);
        }
    }
}

function referentiel_print_group_control_form($scope, &$table, $id){
		echo "<form name=\"{$scope}form\" action=\"#\">";
		echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />";
		echo "<input type=\"hidden\" name=\"tab\" value=\"{$scope}\" />";
		print_table($table);
		echo get_string('withselection', 'referentiel');
		echo '<select name="what" onchange="document.forms[\''.$scope.'form\'].submit();"  ><option value=""></option>';
		echo '<option value="delete">'.get_string('delete').'</option>';
		echo '</select>';
		echo '</form>';
}
?>