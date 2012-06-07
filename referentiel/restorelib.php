<?php //$Id: restorelib.php,v 0.1 2009/08/07 16:32:01 jfruitet Exp $
    //This php script contains all the stuff to backup/restore
    //referentiel mods

    //This is the "graphical" structure of the referentiel mod:
    //
    //                     referentiel (instance)
    //                    (CL,pk->id)
    //                        |
    //                        |---------------------------------------|-------------------------------|-------------------------------|-----------------------------|
    //                        |                                       |                               |                               |                             |
    //                 referentiel_activites                referentiel_task                referentiel_referentiel                referentiel_certificate        referentiel_etablisssement
    //           (UL,pk->id, fk->referentiel,files)	  (UL,pk->id, fk->referentiel,files) (pk->id, fk->referentielid)       (pk->id, fk->referentielid)     (pk->id, files)
    //                        |                  |      |             |                               |                                                             |
    //                        |                  |      |             |                               |                                                             |
    //                        |                  |      |             |                               |                                                             |
    //                 referentiel_document      |      |        referentiel_consigne        referentiel_domain                                           referentiel_student
    //           (pk->id, fk->id_activite, files)|      |     (pk->id, fk->id_task, files)    (pk->id, fk->referentielid)                        (UL, pk->id, fk->id_etablissement)
	//                                           |      |                                             |
	//                                           |      |                                    referentiel_skill
	//                                           |------|                                     (pk->id, fk->domainid)
	//										         |                                                |
	//                                               |                                       referentiel_skill_item
	//                                    referentiel_a_user_task                            (pk->id, fk->skillid, fk->referentielid)
	//                           (UL, pk->id, fk->id_activite, fk->id_task) 
	//
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          nt->nested field (recursive data)
    //          CL->course level info
    //          UL->user level info
    //          files->table may have files)
    //
    //-----------------------------------------------------------

    //This function executes all the restore procedure about this mod
    function referentiel_restore_mods($mod,$restore) {

        global $CFG;
		
		// structure pour enregistrer les coorespondances entre old et new ids
		global $referentiel_ids;
		
		$referentiel_ids = new object();
		$referentiel_ids->referentiel_activity= array();
		$referentiel_ids->referentiel_task= array();
		// $referentiel_ids->referentiel_institution= array();
		$referentiel_ids->referentiel_userid= array();
		$referentiel_ids->referentiel_teacherid= array();

        $status = true;

        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

        if ($data) {
            //Now get completed xmlized object
            $info = $data->info;
            //if necessary, write to restorelog and adjust date/time fields
            if ($restore->course_startdateoffset) {
                restore_log_date_changes('referentiel', $restore, $info['MOD']['#'], array('timecreated', 'CONFIG'));
            }
            // DEBUG
			// traverse_xmlize($info);                                                                     //Debug
            // print_object ($GLOBALS['traverse_array']);                                                  //Debug
            // $GLOBALS['traverse_array']="";                                                              //Debug

            //Now, build the referentiel record structure
			/*
			CREATE TABLE mdl_referentiel (
  id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL DEFAULT '',
  description text NOT NULL,
  domainlabel varchar(80) NOT NULL DEFAULT '',
  skilllabel varchar(80) NOT NULL DEFAULT '',
  itemlabel varchar(80) NOT NULL DEFAULT '',
  timecreated bigint(10) unsigned NOT NULL DEFAULT '0',
  course bigint(10) unsigned NOT NULL DEFAULT '0',
  referentielid bigint(10) unsigned NOT NULL DEFAULT '0',
  visible tinyint(1) unsigned NOT NULL DEFAULT '1',
  config varchar(255) NOT NULL DEFAULT 'scol:0;creref:0;selref:0;impcert:0;',
  PRIMARY KEY (id)
			*/
            $referentiel->course = $restore->course_id;
            $referentiel->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $referentiel->description = backup_todb($info['MOD']['#']['description']['0']['#']);
            $referentiel->domainlabel = backup_todb($info['MOD']['#']['domainlabel']['0']['#']);
            $referentiel->skilllabel = backup_todb($info['MOD']['#']['skilllabel']['0']['#']);
            $referentiel->itemlabel = backup_todb($info['MOD']['#']['itemlabel']['0']['#']);
            $referentiel->timecreated = backup_todb($info['MOD']['#']['timecreated']['0']['#']);
            $referentiel->referentielid = backup_todb($info['MOD']['#']['referentielid']['0']['#']);
            $referentiel->visible = backup_todb($info['MOD']['#']['VISIBLE']['0']['#']);
            $referentiel->config = backup_todb($info['MOD']['#']['CONFIG']['0']['#']);
			$referentiel->printconfig = backup_todb($info['MOD']['#']['printconfig']['0']['#']);
			      
			// recuperer l'id du referentiel_referentiel
			// restore referentiel_referentiel
			$new_referentiel_referentiel_id = referentiel_referentiel_restore_mods($info, $restore);
			
			if ($new_referentiel_referentiel_id) { 
				// mettre a jour
	            $referentiel->referentielid = $new_referentiel_referentiel_id;
				
        	    //The structure is equal to the refrentiel instance, so insert the referentiel
            	$newid = insert_record ("referentiel",$referentiel);
				
	            //Do some output     
    	        if (!defined('RESTORE_SILENTLY')) {
        	        echo "<li>".get_string("modulename","referentiel")." \"".format_string(stripslashes($referentiel->name),true)."\"</li>";
            	}
	            backup_flush(300);
				
            	if ($newid) {
                	//We have the newid, update backup_ids
	                backup_putid($restore->backup_unique_code,$mod->modtype,$mod->id, $newid);
				    //Now check if want to restore user data and do it.
                	if (restore_userdata_selected($restore,'referentiel',$mod->id)) { 
						//Restore taches
	                    $status = referentiel_tasks_restore_mods($mod->id, $newid, $new_referentiel_referentiel_id, $info, $restore) && $status;
						//Restore activites
        	            $status = referentiel_activites_restore_mods($mod->id, $newid, $new_referentiel_referentiel_id, $info, $restore) && $status;
						
						//Restore certificats
                    	$status = referentiel_certificats_restore_mods($mod->id, $newid, $new_referentiel_referentiel_id, $info, $restore) && $status;
						
						//Restore etablissements
    	                $status = referentiel_etablissements_restore_mods($mod->id, $newid, $info, $restore) && $status;
        	        }
            	} 
				else {
                	$status = false;
            	}
        	} 
			else {
            	$status = false;
        	}
		}
		else {
            	$status = false;
        }
        return $status;
    }

// ###############################################  REFERENTIEL_REFRERENTIEL ####################################################	

	function referentiel_referentiel_restore_mods($info, $restore){
        global $CFG;
		
        $new_referentiel_referentiel_id = 0;

        //Get the REFERENTIEL DATA - it might not be present
        if (isset($info['MOD']['#']['REFERENTIEL'])) {
            $sub_info = $info['MOD']['#']['REFERENTIEL'];
        } else {
            $sub_info = array();
        }
/*
CREATE TABLE mdl_referentiel_referentiel (
  id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL DEFAULT '',
  code varchar(20) NOT NULL DEFAULT '',
  referentielauthormail varchar(255) NOT NULL DEFAULT '',
  cle_referentiel varchar(255) NOT NULL DEFAULT '',
  password varchar(255) NOT NULL DEFAULT '',
  description text NOT NULL,
  url varchar(255) NOT NULL DEFAULT '',
  certificatethreshold smallint(3) NOT NULL DEFAULT '0',
  timemodified bigint(10) unsigned NOT NULL DEFAULT '0',
  nb_domaines tinyint(2) unsigned NOT NULL DEFAULT '0',
  liste_codes_competence text NOT NULL,
  liste_empreintes_competence text NOT NULL,
  `local` bigint(10) unsigned NOT NULL DEFAULT '0',
  logo varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Referentiel de competence';
*/
               
		// DEBUG
		// traverse_xmlize($sub_info);                                                                 //Debug
        // print_object ($GLOBALS['traverse_array']);                                                  //Debug
        // $GLOBALS['traverse_array']="";                                                              //Debug

        if ($sub_info){

            $old_referentiel_referentiel_id = backup_todb($sub_info[0]['#']['ID']['0']['#']);
        
		    //Now, build the referentiel_referentiel record structure
		    $referentiel->name = backup_todb($sub_info[0]['#']['NAME']['0']['#']);
		    $referentiel->code = backup_todb($sub_info[0]['#']['code']['0']['#']);
		    $referentiel->referentielauthormail = backup_todb($sub_info[0]['#']['referentielauthormail']['0']['#']);
		    $referentiel->cle_referentiel = backup_todb($sub_info[0]['#']['CLE_REFERENTIEL']['0']['#']);
		    $referentiel->password = backup_todb($sub_info[0]['#']['password']['0']['#']);
		    $referentiel->description = backup_todb($sub_info[0]['#']['description']['0']['#']);
		    $referentiel->url = backup_todb($sub_info[0]['#']['url']['0']['#']);
		    $referentiel->certificatethreshold = backup_todb($sub_info[0]['#']['certificatethreshold']['0']['#']);
		    $referentiel->timemodified = backup_todb($sub_info[0]['#']['TIMEMODIFIED']['0']['#']);
		    $referentiel->nb_domaines = backup_todb($sub_info[0]['#']['NB_DOMAINES']['0']['#']);
		    $referentiel->liste_codes_competence = backup_todb($sub_info[0]['#']['LISTE_CODES_COMPETENCE']['0']['#']);
		    $referentiel->liste_empreintes_competence = backup_todb($sub_info[0]['#']['LISTE_EMPREINTES_COMPETENCE']['0']['#']);
		    $referentiel->local = backup_todb($sub_info[0]['#']['LOCAL']['0']['#']);
		    $referentiel->logo = backup_todb($sub_info[0]['#']['logo']['0']['#']);

		    // We have to see if that referentiel_referentiel exists in DB
		    $referentiel_referentiel_exists=NULL;
		    
		    if (!empty($referentiel->cle_referentiel)){
                $sql="SELECT * FROM ". $CFG->prefix . "referentiel_referentiel  WHERE cle_referentiel='".$referentiel->cle_referentiel."' ";
                // DEBUG
                // echo '<br />DEBUG :: restorelib.php :: 195 :: SQL: '.$sql."\n";
		        $referentiel_referentiel_exists = get_record_sql($sql);
		    }
		    else{     // comparer le code et l'auteur
                $sql="SELECT * FROM ". $CFG->prefix . "referentiel_referentiel  WHERE code='".$referentiel->code."'
AND referentielauthormail='". $referentiel->referentielauthormail."' ";
                // DEBUG
                // echo '<br />DEBUG :: restorelib.php :: 195 :: SQL: '.$sql."\n";
		        $referentiel_referentiel_exists = get_record_sql($sql);
            }

            if (!empty($referentiel_referentiel_exists)){
                return  $referentiel_referentiel_exists->id; // that's all
		    }
		    else{
                //The structure is equal to the db, so insert the referentiel_referentiel
                $new_referentiel_referentiel_id = insert_record ("referentiel_referentiel",$referentiel);
			
                if ($new_referentiel_referentiel_id) {
				    // domaines, competences, items
				    $status = referentiel_domaines_restore_mods($new_referentiel_referentiel_id, $info, $restore);
                    return $new_referentiel_referentiel_id;
                }
		    }
        }

        return 0;

    }
	
	
	
    //This function restores the domaines / competences / item
    function referentiel_domaines_restore_mods($new_referentiel_referentiel_id, $info, $restore){

        global $CFG;
        $status = true;

        //Get the domains array - it might not be present
        if (isset($info['MOD']['#']['REFERENTIEL']['0']['#']['DOMAINES']['0']['#']['DOMAINE'])) {
            $domaines = $info['MOD']['#']['REFERENTIEL']['0']['#']['DOMAINES']['0']['#']['DOMAINE'];
        } else {
            $domaines = array();
        }
			/*
CREATE TABLE mdl_referentiel_domaine (
  id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  code varchar(20) NOT NULL DEFAULT '',
  description text NOT NULL,
  referentielid bigint(10) unsigned NOT NULL DEFAULT '0',
  sortorder tinyint(2) unsigned NOT NULL DEFAULT '0',
  nb_competences tinyint(2) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Domaine de competence';
			*/
        //Iterate over domaines
        for($i = 0; $i < sizeof($domaines); $i++) {
            $sub_info = $domaines[$i];
            
			// DEBUG
			// traverse_xmlize($sub_info);                                                                 //Debug
            // print_object ($GLOBALS['traverse_array']);                                                  //Debug
            // $GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $old_domaine_id = backup_todb($sub_info['#']['ID']['0']['#']);
			
            //Now, build the referentiel_domain record structure
			$domaine->code = backup_todb($sub_info['#']['code']['0']['#']);
			$domaine->description = backup_todb($sub_info['#']['description']['0']['#']);
			
			$domaine->referentielid = backup_todb($sub_info['#']['referentielid']['0']['#']);

			$domaine->sortorder = backup_todb($sub_info['#']['sortorder']['0']['#']);
			$domaine->nb_competences = backup_todb($sub_info['#']['NB_COMPETENCES']['0']['#']);
			
            //We have to recode the referentielid field 
			$domaine->referentielid = $new_referentiel_referentiel_id;
			
            //The structure is equal to the db, so insert the referentiel_submission
            $new_domaine_id = insert_record ("referentiel_domain",$domaine);
			
			if ($new_domaine_id) {
				// competences
				$status = referentiel_competences_restore_mods($new_domaine_id, $new_referentiel_referentiel_id, $sub_info, $restore);
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"referentiel_domain", $old_domaine_id, $new_domaine_id);
            } else {
                $status = false;
            }
        }
        return $status;
    }

    //This function restores the competences 
    function referentiel_competences_restore_mods($new_domaine_id, $new_referentiel_referentiel_id, $info, $restore){

        global $CFG;
        $status = true;

        //Get the domains array - it might not be present
        if (isset($info['#']['COMPETENCES']['0']['#']['COMPETENCE'])) {
            $competences = $info['#']['COMPETENCES']['0']['#']['COMPETENCE'];
        } else {
            $competences = array();
        }
/*
CREATE TABLE mdl_referentiel_competence (
  id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  code varchar(20) NOT NULL DEFAULT '',
  description text NOT NULL,
  domainid bigint(10) unsigned NOT NULL DEFAULT '0',
  sortorder tinyint(2) unsigned NOT NULL DEFAULT '0',
  nb_item_competences tinyint(2) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Competence';

*/
        //Iterate over competences
        for($i = 0; $i < sizeof($competences); $i++) {
            $sub_info = $competences[$i];
            
			// DEBUG
			// traverse_xmlize($sub_info);                                                                 //Debug
            // print_object ($GLOBALS['traverse_array']);                                                  //Debug
            // $GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $old_competence_id = backup_todb($sub_info['#']['ID']['0']['#']);
			
            //Now, build the referentiel_domain record structure
			$competence->code = backup_todb($sub_info['#']['code']['0']['#']);
			$competence->description = backup_todb($sub_info['#']['description']['0']['#']);
			
			$competence->domainid = backup_todb($sub_info['#']['domainid']['0']['#']);

			$competence->sortorder = backup_todb($sub_info['#']['sortorder']['0']['#']);
			$competence->nb_item_competences = backup_todb($sub_info['#']['NB_ITEM_COMPETENCES']['0']['#']);
			
            //We have to recode the referentielid field 
			$competence->domainid = $new_domaine_id;
			
            //The structure is equal to the db, so insert the referentiel_skill
            $new_competence_id = insert_record ("referentiel_skill",$competence);
			
			if ($new_competence_id) {
				// competences
				$status = referentiel_items_restore_mods($new_competence_id, $new_referentiel_referentiel_id, $sub_info, $restore);
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"referentiel_skill", $old_competence_id, $new_competence_id);
            } else {
                $status = false;
            }
        }
        return $status;
    }

    //This function restores the items
    function referentiel_items_restore_mods($new_competence_id,$new_referentiel_referentiel_id, $info, $restore){

        global $CFG;
        $status = true;
        //Get the competence array - it might not be present
        if (isset($info['#']['ITEMS']['0']['#']['ITEM'])) {
            $items = $info['#']['ITEMS']['0']['#']['ITEM'];
        } else {
            $items = array();
        }


/*

CREATE TABLE `mdl_referentiel_item_competence` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `referentielid` bigint(10) unsigned NOT NULL DEFAULT '0',
  `skillid` bigint(10) unsigned NOT NULL DEFAULT '0',
  `type` varchar(20) NOT NULL DEFAULT '',
  `weight` smallint(3) NOT NULL DEFAULT '0',
  `footprint` smallint(3) NOT NULL DEFAULT '1',
  `sortorder` tinyint(2) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Item de competence';

*/							
        //Iterate over competences
        for($i = 0; $i < sizeof($items); $i++) {
            $sub_info = $items[$i];
            
			// DEBUG
			// traverse_xmlize($sub_info);                                                                 //Debug
            // print_object ($GLOBALS['traverse_array']);                                                  //Debug
            // $GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $old_item_id = backup_todb($sub_info['#']['ID']['0']['#']);
			
            //Now, build the referentiel_domain record structure
			$item->code = backup_todb($sub_info['#']['code']['0']['#']);
			$item->description = backup_todb($sub_info['#']['description']['0']['#']);
			$item->referentielid = backup_todb($sub_info['#']['referentielid']['0']['#']);
			$item->skillid = backup_todb($sub_info['#']['skillid']['0']['#']);
			$item->type = backup_todb($sub_info['#']['type']['0']['#']);
			$item->weight = backup_todb($sub_info['#']['weight']['0']['#']);
			$item->footprint = backup_todb($sub_info['#']['footprint']['0']['#']);
			$item->sortorder = backup_todb($sub_info['#']['sortorder']['0']['#']);
						
            //We have to recode the referentielid and skillid fields
			$item->referentielid = $new_referentiel_referentiel_id; 
			$item->skillid = $new_competence_id;
			
            //The structure is equal to the db, so insert the referentiel_skill
            $new_item_id = insert_record ("referentiel_skill_item",$item);
			
			if ($new_item_id) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"referentiel_skill_item", $old_item_id, $new_item_id);
            } else {
                $status = false;
            }
        }
        return $status;
    }

// ###############################################  TACHES ####################################################


    //This function restores the referentiel_activity
    function referentiel_tasks_restore_mods($old_referentiel_id, $new_referentiel_id, $new_referentiel_referentiel_id, $info, $restore) {

        global $CFG;
		global $referentiel_ids;

        $status = true;

        //Get the activites array - it might not be present
        if (isset($info['MOD']['#']['TASKS']['0']['#']['TASK'])) {
            $tasks = $info['MOD']['#']['TASKS']['0']['#']['TASK'];
        } else {
            $tasks = array();
        }
/*
CREATE TABLE mdl_referentiel_task (
  id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  type varchar(80) NOT NULL DEFAULT '',
  description text NOT NULL,
  competences_task text NOT NULL,
  criteres_evaluation text NOT NULL,
  instanceid bigint(10) unsigned NOT NULL DEFAULT '0',
  referentielid bigint(10) unsigned NOT NULL DEFAULT '0',
  course bigint(10) unsigned NOT NULL DEFAULT '0',
  auteurid bigint(10) unsigned NOT NULL,
  timecreated bigint(10) unsigned NOT NULL DEFAULT '0',
  timemodified bigint(10) unsigned NOT NULL DEFAULT '0',
  timestart bigint(10) unsigned NOT NULL DEFAULT '0',
  timeend bigint(10) unsigned NOT NULL DEFAULT '0',
`cle_souscription` varchar(255) NOT NULL DEFAULT '',
  `souscription_libre` int(4) NOT NULL DEFAULT '1',  
  PRIMARY KEY (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='task';

*/
        //Iterate over activites
        for($i = 0; $i < sizeof($tasks); $i++) {
            $sub_info = $tasks[$i];
            
			// DEBUG
			// traverse_xmlize($sub_info);                                                                 //Debug
            // print_object ($GLOBALS['traverse_array']);                                                  //Debug
            // $GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $old_task_id = backup_todb($sub_info['#']['ID']['0']['#']);
            $oldauteurid = backup_todb($sub_info['#']['AUTEURID']['0']['#']);
			
            //Now, build the referentiel_task record structure
            $task->instanceid = $new_referentiel_id;
            $task->course = $restore->course_id;
			$task->type = backup_todb($sub_info['#']['type']['0']['#']);
			$task->description = backup_todb($sub_info['#']['description']['0']['#']);
			$task->competences_task = backup_todb($sub_info['#']['COMPETENCES_TASK']['0']['#']);
		    $task->criteres_evaluation = backup_todb($sub_info['#']['CRITERES_EVALUATION']['0']['#']);
			$task->referentielid = backup_todb($sub_info['#']['referentielid']['0']['#']);
            $task->auteurid = backup_todb($sub_info['#']['AUTEURID']['0']['#']);
            $task->timecreated = backup_todb($sub_info['#']['timecreated']['0']['#']);
		    $task->timemodified = backup_todb($sub_info['#']['timemodified']['0']['#']);
			$task->timestart = backup_todb($sub_info['#']['timestart']['0']['#']);
			$task->timeend = backup_todb($sub_info['#']['timeend']['0']['#']);
			$task->cle_souscription = backup_todb($sub_info['#']['CLE_SOUSCRIPTION']['0']['#']);
			$task->souscription_libre = backup_todb($sub_info['#']['SOUSCRIPTION_LIBRE']['0']['#']);
			
			//We have to recode the referentielid field
			$task->referentielid = $new_referentiel_referentiel_id;

            //We have to recode the auteurid field
            $user = backup_getid($restore->backup_unique_code,"user",$task->auteurid);
            if ($user) {
                $task->auteurid = $user->new_id;
				// stocker
            	$referentiel_ids->referentiel_userid[$oldauteurid]= $user->new_id;
			}

            //We have to recode the referentiel_referentiel field
            $user = backup_getid($restore->backup_unique_code,"user",$task->auteurid);
            if ($user) {
                $task->auteurid = $user->new_id;
				        // stocker
            	 $referentiel_ids->referentiel_userid[$oldauteurid]= $user->new_id;
			}

            //The structure is equal to the db, so insert the referentiel_submission
            $new_task_id = insert_record ("referentiel_task",$task);
			// stocker
			$referentiel_ids->referentiel_task[$old_task_id]=$new_task_id;
			
			
            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

			if ($new_task_id) {
				// documents
                $status_document = referentiel_consignes_restore_mods($new_task_id, $info, $restore);
			}
			
            if ($new_task_id) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"referentiel_task", $old_task_id, $new_task_id);

                //Now copy moddata associated files
                $status = referentiel_restore_files ($old_task_id, $new_task_id, $oldauteurid, $task->auteurid, $restore);
            } else {
                $status = false;
            }
        }

        return $status;
    }

    //This function restores the referentiel_consigne
    function referentiel_consignes_restore_mods($new_task_id, $info, $restore) {

        global $CFG;

        $status = true;

        //Get the consignes array - it might not be present
        if (isset($info['MOD']['#']['TASKS']['0']['#']['TASK']['0']['#']['CONSIGNES']['0']['#']['CONSIGNE'])) {
            $consignes = $info['MOD']['#']['TASKS']['0']['#']['TASK']['0']['#']['CONSIGNES']['0']['#']['CONSIGNE'];
        } else {
            $consignes = array();
        }
/*
				// Documents consignes associes
				/*
CREATE TABLE mdl_referentiel_consigne (
  id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  type varchar(20) NOT NULL DEFAULT '',
  description text NOT NULL,
  url varchar(255) NOT NULL DEFAULT '',
  taskid bigint(10) unsigned NOT NULL,
  PRIMARY KEY (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='consigne';
				*/
		//Iterate over documents
        for($i = 0; $i < sizeof($consignes); $i++) {
            $sub_info = $consignes[$i];
            
			// DEBUG
			// traverse_xmlize($sub_info);                                                                 //Debug
            // print_object ($GLOBALS['traverse_array']);                                                  //Debug
            // $GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $old_consigne_id = backup_todb($sub_info['#']['ID']['0']['#']);

            //Now, build the referentiel_activity record structure
			
			$consigne->type = backup_todb($sub_info['#']['type']['0']['#']);
			$consigne->description = backup_todb($sub_info['#']['description']['0']['#']);
			$consigne->url = backup_todb($sub_info['#']['url']['0']['#']);
			$consigne->taskid = backup_todb($sub_info['#']['taskid']['0']['#']);
			// Mise a jour
			$consigne->taskid = $new_task_id;
			
            //The structure is equal to the db, so insert the referentiel_submission
            $new_consigne_id = insert_record ("referentiel_consigne", $consigne);

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if ($new_consigne_id) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"referentiel_consigne",$old_consigne_id, $new_consigne_id);
				// les fichers sont associes a l'utilisateur et pas au document
            } else {
                $status = false;
            }
        }

        return $status;
    }

    //This function restores the referentiel_submissions
    function referentiel_a_user_task_restore_mods($new_task_id, $info, $restore) {
	// n'est pas utilise dans cette appli
        global $CFG;

        $status = true;

        //Get the consignes array - it might not be present
        if (isset($info['MOD']['#']['TASKS']['0']['#']['TASK']['0']['#']['USERS_TASKS']['0']['#']['USER_TASK'])) {
            $a_users_tasks = $info['MOD']['#']['TASKS']['0']['#']['TASK']['0']['#']['USERS_TASKS']['0']['#']['USER_TASK'];
        } else {
            $a_users_tasks = array();
        }
/*

CREATE TABLE mdl_referentiel_a_user_task (
  id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  userid bigint(10) unsigned NOT NULL,
  taskid bigint(10) unsigned NOT NULL,
  date_selection bigint(10) unsigned NOT NULL DEFAULT '0',
  activityid bigint(10) unsigned NOT NULL,
  PRIMARY KEY (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='user_select_task';

*/
				        //Iterate over documents
        for($i = 0; $i < sizeof($a_users_tasks); $i++) {
            $sub_info = $a_users_tasks[$i];
            
			// DEBUG
			// traverse_xmlize($sub_info);                                                                 //Debug
            // print_object ($GLOBALS['traverse_array']);                                                  //Debug
            // $GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $old_a_user_task_id = backup_todb($sub_info['#']['ID']['0']['#']);
			$olduserid = backup_todb($sub_info['#']['userid']['0']['#']);
			
            //Now, build the referentiel_activity record structure
			
			$a_user_task->userid = backup_todb($sub_info['#']['userid']['0']['#']);
			$a_user_task->taskid = backup_todb($sub_info['#']['taskid']['0']['#']);
			$a_user_task->date_selection = backup_todb($sub_info['#']['DATE_SELECTION']['0']['#']);
			$a_user_task->activityid = backup_todb($sub_info['#']['activityid']['0']['#']);
			
			// mise a jour
			$a_user_task->taskid = $new_task_id;
            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$a_user_task->userid);
            if ($user) {
                $a_user_task->userid = $user->new_id;
				// stocker
            	$referentiel_ids->referentiel_userid[$olduserid]= $user->new_id;
			}
			// mise a jour de l'id d'activite reporté après lecture des activites
			
            //The structure is equal to the db, so insert the referentiel_submission
            $new_a_user_task_id = insert_record ("referentiel_a_user_task", $a_user_task);

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if ($new_a_user_task_id) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"referentiel_a_user_task",$old_a_user_task_id, $new_a_user_task_id);
				// les fichers sont associes a l'utilisateur et pas au document
            } else {
                $status = false;
            }
        }

        return $status;
    }
	
// ###############################################  ACTIVITES ####################################################	

    //This function restores the referentiel_activity
    function referentiel_activites_restore_mods($old_referentiel_id, $new_referentiel_id, $new_referentiel_referentiel_id, $info, $restore) {

        global $CFG;
		global $referentiel_ids;

        $status = true;

        //Get the activites array - it might not be present
        if (isset($info['MOD']['#']['ACTIVITES']['0']['#']['ACTIVITE'])) {
            $activites = $info['MOD']['#']['ACTIVITES']['0']['#']['ACTIVITE'];
        } else {
            $activites = array();
        }
/*
CREATE TABLE mdl_referentiel_activite (
  id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  type_activite varchar(80) NOT NULL DEFAULT '',
  description text NOT NULL,
  comptencies text NOT NULL,
  comment text NOT NULL,
  instanceid bigint(10) unsigned NOT NULL DEFAULT '0',
  referentielid bigint(10) unsigned NOT NULL DEFAULT '0',
  course bigint(10) unsigned NOT NULL DEFAULT '0',
  userid bigint(10) unsigned NOT NULL,
  teacherid bigint(10) unsigned NOT NULL,
  timecreated bigint(10) unsigned NOT NULL DEFAULT '0',
  timemodifiedstudent bigint(10) unsigned NOT NULL DEFAULT '0',
  timemodified bigint(10) unsigned NOT NULL DEFAULT '0',
  approved smallint(4) unsigned NOT NULL DEFAULT '0',
  taskid bigint(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Activite';
*/
        //Iterate over activites
        for($i = 0; $i < sizeof($activites); $i++) {
            $sub_info = $activites[$i];
            
			// DEBUG
			// traverse_xmlize($sub_info);                                                                 //Debug
            // print_object ($GLOBALS['traverse_array']);                                                  //Debug
            // $GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $old_activite_id = backup_todb($sub_info['#']['ID']['0']['#']);
            $olduserid = backup_todb($sub_info['#']['USERID']['0']['#']);
            $oldteacherid = backup_todb($sub_info['#']['TEACHERID']['0']['#']);
			
            //Now, build the referentiel_activity record structure
            $activite->instanceid = $new_referentiel_id;
			$activite->course = $restore->course_id;
			$activite->type_activite = backup_todb($sub_info['#']['TYPE_ACTIVITE']['0']['#']);
			$activite->description = backup_todb($sub_info['#']['description']['0']['#']);
			$activite->comptencies = backup_todb($sub_info['#']['comptencies']['0']['#']);
			$activite->comment = backup_todb($sub_info['#']['comment']['0']['#']);
			
			$activite->referentielid = backup_todb($sub_info['#']['referentielid']['0']['#']);
			
			$activite->userid = backup_todb($sub_info['#']['USERID']['0']['#']);
			$activite->teacherid = backup_todb($sub_info['#']['TEACHERID']['0']['#']);
			$activite->timecreated = backup_todb($sub_info['#']['timecreated']['0']['#']);
			$activite->timemodified = backup_todb($sub_info['#']['timemodified']['0']['#']);
			$activite->approved = backup_todb($sub_info['#']['APPROVED']['0']['#']);
			$activite->taskid = backup_todb($sub_info['#']['taskid']['0']['#']);
			$activite->timemodifiedstudent = backup_todb($sub_info['#']['timemodifiedstudent']['0']['#']);
			//We have to recode the referentielid field 
			$activite->referentielid = $new_referentiel_referentiel_id;
			
            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$activite->userid);
            if ($user) {
                $activite->userid = $user->new_id;
				// stocker
            	$referentiel_ids->referentiel_userid[$olduserid]= $user->new_id;
			}

            //We have to recode the teacher field
            $teacher = backup_getid($restore->backup_unique_code,"user",$activite->teacherid);
            if ($teacher) {
                $activite->teacherid = $teacher->new_id;
				// stocker
            	$referentiel_ids->referentiel_teacherid[$oldteacherid]= $teacher->new_id;
			} 

			// Have we to recode task ref ?
			if (($activite->taskid != 0) && isset($referentiel_ids->referentiel_task[$activite->taskid]) && ($referentiel_ids->referentiel_task[$activite->taskid]>0)){ 
				$activite->taskid = $referentiel_ids->referentiel_task[$activite->taskid];
				// Penser à mettre a jour la table referentiel_a_user_task 
			}
			
            //The structure is equal to the db, so insert the referentiel_submission
            $new_activite_id = insert_record ("referentiel_activity",$activite);

			if ($new_activite_id){ 
				// Taches ?
				if ($activite->taskid>0){
					// mettre a jour la table referentiel_a_user_task plus bas
					$status_a_task=referentiel_a_activite_task_restore_mods($new_activite_id, $activite->taskid, $activite->userid, $info, $restore);
				}
				
				// documents
				$status_document = referentiel_documents_restore_mods($new_activite_id, $info, $restore);
			}
			
            if ($new_activite_id) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"referentiel_activity", $old_activite_id, $new_activite_id);

                //Now copy moddata associated files
                $status = referentiel_restore_files ($old_activite_id, $new_activite_id, $olduserid, $activite->userid, $restore);
            } else {
                $status = false;
            }

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }
        }

        return $status;
    }
	
	
	    //This function restores the referentiel_submissions
    function referentiel_a_activite_task_restore_mods($new_activite_id, $new_task_id, $new_user_id, $info, $restore) {
	// retablit l'association entre l'activite et la tache
        global $CFG;

        $status = true;

        //Get the user_task array - it might not be present
        if (isset($info['MOD']['#']['ACTIVITES']['0']['#']['ACTIVITE']['0']['#']['USERS_TASKS']['0']['#']['USER_TASK'])) {
            $a_users_tasks = $info['MOD']['#']['ACTIVITES']['0']['#']['ACTIVITE']['0']['#']['USERS_TASKS']['0']['#']['USER_TASK'];
        } else {
            $a_users_tasks = array();
        }
/*

CREATE TABLE mdl_referentiel_a_user_task (
  id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  userid bigint(10) unsigned NOT NULL,
  taskid bigint(10) unsigned NOT NULL,
  date_selection bigint(10) unsigned NOT NULL DEFAULT '0',
  activityid bigint(10) unsigned NOT NULL,
  PRIMARY KEY (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='user_select_task';

*/
        //Iterate over documents
        for($i = 0; $i < sizeof($a_users_tasks); $i++) {
            $sub_info = $a_users_tasks[$i];
			
	        // DEBUG
			traverse_xmlize($sub_info);                                                                 //Debug
        	print_object ($GLOBALS['traverse_array']);                                                  //Debug
	        $GLOBALS['traverse_array']="";                                                              //Debug
	
			//We'll need this later!!
        	$old_a_user_task_id = backup_todb($sub_info['#']['ID']['0']['#']);
			$olduserid = backup_todb($sub_info['#']['userid']['0']['#']);
			$oldtaskid = backup_todb($sub_info['#']['taskid']['0']['#']);
			$oldactiviteid = backup_todb($sub_info['#']['activityid']['0']['#']);
			//Now, build the referentiel_activity record structure
			$a_user_task->userid = backup_todb($sub_info['#']['userid']['0']['#']);
			$a_user_task->taskid = backup_todb($sub_info['#']['taskid']['0']['#']);
			$a_user_task->date_selection = backup_todb($sub_info['#']['DATE_SELECTION']['0']['#']);
			$a_user_task->activityid = backup_todb($sub_info['#']['activityid']['0']['#']);
			// mise a jour
			$a_user_task->taskid = $new_task_id;
			$a_user_task->activityid = $new_activite_id;
			$a_user_task->userid = $new_user_id;
		/*
        //We have to recode the userid field
        $user = backup_getid($restore->backup_unique_code,"user",$a_user_task->userid);
        if ($user) {
        	$a_user_task->userid = $user->new_id;
			// stocker
            $referentiel_ids->referentiel_userid[$olduserid]= $user->new_id;
		}
		*/
			//The structure is equal to the db, so insert the referentiel_submission
    	    $new_a_user_task_id = insert_record ("referentiel_a_user_task", $a_user_task);
			
	        if ($new_a_user_task_id) {
    	    	//We have the newid, update backup_ids
        	    backup_putid($restore->backup_unique_code,"referentiel_a_user_task",$old_a_user_task_id, $new_a_user_task_id);
			} 
			else {
        		$status = false;
			}
		}
        return $status;
    }
	


    //This function restores the referentiel_submissions
    function referentiel_documents_restore_mods($new_activite_id, $info, $restore) {

        global $CFG;

        $status = true;

        //Get the activites array - it might not be present
        if (isset($info['MOD']['#']['ACTIVITES']['0']['#']['ACTIVITE']['0']['#']['DOCUMENTS']['0']['#']['DOCUMENT'])) {
            $documents = $info['MOD']['#']['ACTIVITES']['0']['#']['ACTIVITE']['0']['#']['DOCUMENTS']['0']['#']['DOCUMENT'];
        } else {
            $documents = array();
        }
/*
				// Documents associes
				
CREATE TABLE mdl_referentiel_document (
  id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  type varchar(20) NOT NULL DEFAULT '',
  description text NOT NULL,
  url varchar(255) NOT NULL DEFAULT '',
  activityid bigint(10) unsigned NOT NULL,
  PRIMARY KEY (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Document';
                		fwrite ($bf,full_tag("ID",8,false,$document->id));       
                		fwrite ($bf,full_tag("type",8,false,$document->type));
						fwrite ($bf,full_tag("description",8,false,$document->description));
						fwrite ($bf,full_tag("url",8,false,$document->url));
						fwrite ($bf,full_tag("activityid",8,false,$document->activityid));

				
*/
        //Iterate over documents
        for($i = 0; $i < sizeof($documents); $i++) {
            $sub_info = $documents[$i];
            
			// DEBUG
			// traverse_xmlize($sub_info);                                                                 //Debug
            // print_object ($GLOBALS['traverse_array']);                                                  //Debug
            // $GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $old_document_id = backup_todb($sub_info['#']['ID']['0']['#']);

            //Now, build the referentiel_activity record structure
			
			$document->type_activite = backup_todb($sub_info['#']['type']['0']['#']);
			$document->description = backup_todb($sub_info['#']['description']['0']['#']);
			$document->url = backup_todb($sub_info['#']['url']['0']['#']);
			$document->activityid = backup_todb($sub_info['#']['activityid']['0']['#']);
			
			// mise a jour
			$document->activityid = $new_activite_id;
            //The structure is equal to the db, so insert the referentiel_submission
            $new_document_id = insert_record ("referentiel_document", $document);

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if ($new_document_id) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"referentiel_document",$old_document_id, $new_document_id);
				// les fichers sont associes a l'utilisateur et pas au document
            } else {
                $status = false;
            }
        }

        return $status;
    }
	
	

// ###############################################  CERTIFICATS ####################################################


    //This function restores the referentiel_activity
    function referentiel_certificats_restore_mods($old_referentiel_id, $new_referentiel_id, $new_referentiel_referentiel_id, $info, $restore) {

        global $CFG;
		global $referentiel_ids;

        $status = true;

        //Get the activites array - it might not be present
        if (isset($info['MOD']['#']['CERTIFICATS']['0']['#']['CERTIFICAT'])) {
            $certificats = $info['MOD']['#']['CERTIFICATS']['0']['#']['CERTIFICAT'];
        } else {
            $certificats = array();
        }
/*
CREATE TABLE mdl_referentiel_certificate (
  id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  comment text NOT NULL,
  competences_certificate text NOT NULL,
  decision_jury varchar(80) NOT NULL DEFAULT '',
  date_decision bigint(10) unsigned NOT NULL DEFAULT '0',
  referentielid bigint(10) unsigned NOT NULL DEFAULT '0',
  userid bigint(10) unsigned NOT NULL,
  teacherid bigint(10) unsigned NOT NULL,
  verrou tinyint(1) unsigned NOT NULL,
  valide tinyint(1) unsigned NOT NULL,
  evaluation bigint(10) unsigned NOT NULL,
  PRIMARY KEY (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Certificat';
*/
        //Iterate over certificats
        for($i = 0; $i < sizeof($certificats); $i++) {
            $sub_info = $certificats[$i];
            
			// DEBUG
			// traverse_xmlize($sub_info);                                                                 //Debug
            // print_object ($GLOBALS['traverse_array']);                                                  //Debug
            // $GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $old_certificate_id = backup_todb($sub_info['#']['ID']['0']['#']);
            $olduserid = backup_todb($sub_info['#']['USERID']['0']['#']);
            $oldteacherid = backup_todb($sub_info['#']['TEACHERID']['0']['#']);
						
            //Now, build the referentiel_task record structure
			$certificat->comment = backup_todb($sub_info['#']['comment']['0']['#']);
			$certificat->competences_certificate = backup_todb($sub_info['#']['COMPETENCES_CERTIFICAT']['0']['#']);
			$certificat->decision_jury = backup_todb($sub_info['#']['DECISION_JURY']['0']['#']);
			$certificat->date_decision = backup_todb($sub_info['#']['DATE_DECISION']['0']['#']);
			$certificat->referentielid = backup_todb($sub_info['#']['referentielid']['0']['#']);
            $certificat->userid = backup_todb($sub_info['#']['USERID']['0']['#']);
            $certificat->teacherid = backup_todb($sub_info['#']['TEACHERID']['0']['#']);
			$certificat->verrou = backup_todb($sub_info['#']['VERROU']['0']['#']);
            $certificat->valide = backup_todb($sub_info['#']['VALIDE']['0']['#']);
            $certificat->evaluation = backup_todb($sub_info['#']['EVALUATION']['0']['#']);
            $certificat->competences_certificate = backup_todb($sub_info['#']['SYNTHESE_CERTIFICAT']['0']['#']);
			$certificat->synthese_certificate = backup_todb($sub_info['#']['SYNTHESE_CERTIFICAT']['0']['#']);

			//We have to recode the referentielid field 
			$certificat->referentielid = $new_referentiel_referentiel_id;

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$certificat->userid);
            if ($user) {
                $certificat->userid = $user->new_id;
				// stocker
            	$referentiel_ids->referentiel_userid[$olduserid]= $user->new_id;
			}
            //We have to recode the teacherid field
            $teacher = backup_getid($restore->backup_unique_code,"user",$certificat->teacherid);
            if ($teacher) {
                $certificat->teacherid = $teacher->new_id;
				// stocker
            	$referentiel_ids->referentiel_teacherid[$oldteacherid]= $teacher->new_id;
			}

			// Verifier si le certificate existe pour cet utilisateur dans la base de donnees
			$sql="SELECT * FROM ". $CFG->prefix . "referentiel_certificate  WHERE referentielid=".$certificat->referentielid." AND userid=".$certificat->userid." ";
			// DEBUG
			// echo '<br />DEBUG :: restorelib.php :: 1084 :: SQL: '.$sql."\n";
			$certificate_exists = get_record_sql($sql);
			
			if (!$certificate_exists){
            	//The structure is equal to the db, so insert the referentiel_submission
            	$new_certificate_id = insert_record ("referentiel_certificate", $certificat);
				
	            if ($new_certificate_id) {
        	        //We have the newid, update backup_ids
            	    backup_putid($restore->backup_unique_code,"referentiel_certificate", $old_certificate_id, $new_certificate_id);
	            } 
				else {
                	$status = false;
            	}
        	}
		}

        return $status;
    }

	
	

// ###############################################  ETABLISSEMENTS ####################################################


    //This function restores the referentiel_institution
    function referentiel_etablissements_restore_mods($mod, $newid, $info, $restore) {

        global $CFG;
		global $referentiel_ids;

        $status = true;

        //Get the etablissement array - it might not be present
        if (isset($info['MOD']['#']['ETABLISSEMENTS']['0']['#']['ETABLISSEMENT'])) {
            $etablissements = $info['MOD']['#']['ETABLISSEMENTS']['0']['#']['ETABLISSEMENT'];
        } else {
            $etablissements = array();
        }
/*
CREATE TABLE mdl_referentiel_etablissement (
  id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  idnumber varchar(20) NOT NULL DEFAULT 'INCONNU',
  name varchar(80) NOT NULL DEFAULT 'A COMPLETER',
  address varchar(255) NOT NULL DEFAULT 'A COMPLETER',
  logo text NOT NULL,
  PRIMARY KEY (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Etablissement';
*/
        //Iterate over etablissements
        for ($i = 0; $i < sizeof($etablissements); $i++) {
            $sub_info = $etablissements[$i];
            
			// DEBUG
			// traverse_xmlize($sub_info);                                                                 //Debug
            // print_object ($GLOBALS['traverse_array']);                                                  //Debug
            // $GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $old_etablissement_id = backup_todb($sub_info['#']['ID']['0']['#']);
						
            //Now, build the referentiel_task record structure
			$etablissement->idnumber = backup_todb($sub_info['#']['idnumber']['0']['#']);
			$etablissement->name = backup_todb($sub_info['#']['name']['0']['#']);
			$etablissement->address = backup_todb($sub_info['#']['address']['0']['#']);
			$etablissement->logo = backup_todb($sub_info['#']['logo']['0']['#']);

			// Verifier si l'etablissement existe pour dans la base de donnees
			$sql="SELECT * FROM ". $CFG->prefix . "referentiel_institution  WHERE idnumber='".$etablissement->idnumber."' ";
			// DEBUG
			// echo '<br />DEBUG :: restorelib.php :: 1084 :: SQL: '.$sql."\n";
			$etablissement_exists = get_record_sql($sql);
			
			if (!$etablissement_exists){
            	//The structure is equal to the db, so insert the referentiel_submission
            	$new_etablissement_id = insert_record ("referentiel_institution", $etablissement);
				
	            if ($new_etablissement_id) {
        	        //We have the newid, update backup_ids
            	    backup_putid($restore->backup_unique_code,"referentiel_institution", $old_etablissement_id, $new_etablissement_id);
					
					// students
					$status = referentiel_students_restore_mods($new_etablissement_id, $info, $restore);
	            } 
				else {
                	$status = false;
            	}
        	}
			else{
				// students
				$status = referentiel_students_restore_mods($etablissement_exists->id, $info, $restore);
			}
		}

        return $status;
    }

    //This function restores the referentiel_student
    function referentiel_students_restore_mods($etablissement_id, $info, $restore) {

        global $CFG;
		global $referentiel_ids;

        $status = true;

        //Get the student array - it might not be present
        if (isset($info['MOD']['#']['ETABLISSEMENTS']['0']['#']['ETABLISSEMENT']['0']['#']['studentS']['0']['#']['student'])) {
            $students = $info['MOD']['#']['ETABLISSEMENTS']['0']['#']['ETABLISSEMENT']['0']['#']['studentS']['0']['#']['student'];
        } else {
            $students = array();
        }
/*
CREATE TABLE mdl_referentiel_student (
  id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  num_student varchar(20) NOT NULL DEFAULT '',
  ddn_student varchar(14) NOT NULL DEFAULT '',
  lieu_naissance varchar(255) NOT NULL DEFAULT '',
  departement_naissance varchar(255) NOT NULL DEFAULT '',
  adresse_student varchar(255) NOT NULL DEFAULT '',
  userid bigint(10) unsigned NOT NULL,
  ref_etablissement bigint(10) unsigned NOT NULL,
  PRIMARY KEY (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Fiche student';
*/				
        //Iterate over students
        for ($i = 0; $i < sizeof($students); $i++) {
            $sub_info = $students[$i];
            
			// DEBUG
			// traverse_xmlize($sub_info);                                                                 //Debug
            // print_object ($GLOBALS['traverse_array']);                                                  //Debug
            // $GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $old_student_id = backup_todb($sub_info['#']['ID']['0']['#']);
			$olduserid = backup_todb($sub_info['#']['USERID']['0']['#']);
						
            //Now, build the referentiel_task record structure
			$student->num_student = backup_todb($sub_info['#']['NUM_student']['0']['#']);
			$student->ddn_student = backup_todb($sub_info['#']['DDN_student']['0']['#']);
			$student->lieu_naissance = backup_todb($sub_info['#']['LIEU_NAISSANCE']['0']['#']);
			$student->departement_naissance = backup_todb($sub_info['#']['DEPARTEMENT_NAISSANCE']['0']['#']);
			$student->adresse_student = backup_todb($sub_info['#']['ADRESSE_student']['0']['#']);
			$student->userid = backup_todb($sub_info['#']['USERID']['0']['#']);
			$student->ref_etablissement = backup_todb($sub_info['#']['REF_ETABLISSEMENT']['0']['#']);
			
			
			// mise a jour
			$student->ref_etablissement = $etablissement_id;
			
			            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$student->userid);
            if ($user) {
                $student->userid = $user->new_id;
				// stocker
            	$referentiel_ids->referentiel_userid[$olduserid]= $user->new_id;
			}

			// Verifier si l'student existe dans la base de donnees
			$sql="SELECT * FROM ". $CFG->prefix . "referentiel_student  WHERE num_student='".$student->num_student."' ";
			// DEBUG
			// echo '<br />DEBUG :: restorelib.php :: 1084 :: SQL: '.$sql."\n";
			$student_exists = get_record_sql($sql);
			
			if (!$student_exists){
            	//The structure is equal to the db, so insert the referentiel_submission
            	$new_student_id = insert_record ("referentiel_student", $student);
				
	            if ($new_student_id) {
        	        //We have the newid, update backup_ids
            	    backup_putid($restore->backup_unique_code,"referentiel_student", $old_student_id, $new_student_id);
	            } 
				else {
                	$status = false;
            	}
        	}
		}

        return $status;
    }

	
// ########################################  FICHIERS ########################################

    //This function copies the referentiel related info from backup temp dir to course moddata folder,
    //creating it if needed and recoding everything (referentiel id and user id) 
    function referentiel_restore_files ($oldassid, $newassid, $olduserid, $newuserid, $restore) {

        global $CFG;

        $status = true;
        $todo = false;
        $moddata_path = "";
        $referentiel_path = "";
        $temp_path = "";

        //First, we check to "course_id" exists and create is as necessary
        //in CFG->dataroot
        $dest_dir = $CFG->dataroot."/".$restore->course_id;
        $status = check_dir_exists($dest_dir,true);

        //Now, locate course's moddata directory
        $moddata_path = $CFG->dataroot."/".$restore->course_id."/".$CFG->moddata;
   
        //Check it exists and create it
        $status = check_dir_exists($moddata_path,true);

        //Now, locate referentiel directory
        if ($status) {
            $referentiel_path = $moddata_path."/referentiel";
            //Check it exists and create it
            $status = check_dir_exists($referentiel_path,true);
        }

        //Now locate the temp dir we are gong to restore
        if ($status) {
            $temp_path = $CFG->dataroot."/temp/backup/".$restore->backup_unique_code.
                         "/moddata/referentiel/".$oldassid."/".$olduserid;
            //Check it exists
            if (is_dir($temp_path)) {
                $todo = true;
            }
        }

        //If todo, we create the neccesary dirs in course moddata/referentiel
        if ($status and $todo) {
            //First this referentiel id
            $this_referentiel_path = $referentiel_path."/".$newassid;
            $status = check_dir_exists($this_referentiel_path,true);
            //Now this user id
            $user_referentiel_path = $this_referentiel_path."/".$newuserid;
            //And now, copy temp_path to user_referentiel_path
            $status = backup_copy_file($temp_path, $user_referentiel_path); 
        }
       
        return $status;
    }

    //Return a content decoded to support interactivities linking. Every module
    //should have its own. They are called automatically from
    //referentiel_decode_content_links_caller() function in each module
    //in the restore process
    function referentiel_decode_content_links ($content,$restore) {
            
        global $CFG;
            
        $result = $content;
                
        //Link to the list of referentiels
                
        $searchstring='/\$@(REFERENTIELINDEX)\*([0-9]+)@\$/';
        //We look for it
        preg_match_all($searchstring,$content,$foundset);
        //If found, then we are going to look for its new id (in backup tables)
        if ($foundset[0]) {
            //print_object($foundset);                                     //Debug
            //Iterate over foundset[2]. They are the old_ids
            foreach($foundset[2] as $old_id) {
                //We get the needed variables here (course id)
                $rec = backup_getid($restore->backup_unique_code,"course",$old_id);
                //Personalize the searchstring
                $searchstring='/\$@(REFERENTIELINDEX)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/referentiel/index.php?id='.$rec->new_id,$result);
                } else { 
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/referentiel/index.php?id='.$old_id,$result);
                }
            }
        }

        //Link to referentiel view by moduleid

        $searchstring='/\$@(referentielVIEWBYID)\*([0-9]+)@\$/';
        //We look for it
        preg_match_all($searchstring,$result,$foundset);
        //If found, then we are going to look for its new id (in backup tables)
        if ($foundset[0]) {
            //print_object($foundset);                                     //Debug
            //Iterate over foundset[2]. They are the old_ids
            foreach($foundset[2] as $old_id) {
                //We get the needed variables here (course_modules id)
                $rec = backup_getid($restore->backup_unique_code,"course_modules",$old_id);
                //Personalize the searchstring
                $searchstring='/\$@(referentielVIEWBYID)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/referentiel/view.php?id='.$rec->new_id,$result);
                } else {
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/referentiel/view.php?id='.$old_id,$result);
                }
            }
        }

        return $result;
    }

    //This function makes all the necessary calls to xxxx_decode_content_links()
    //function in each module, passing them the desired contents to be decoded
    //from backup format to destination site/course in order to mantain inter-activities
    //working in the backup/restore process. It's called from restore_decode_content_links()
    //function in restore process
    function referentiel_decode_content_links_caller($restore) {
        global $CFG;
        $status = true;

        if ($referentiels = get_records_sql ("SELECT a.id, a.description
                                   FROM {$CFG->prefix}referentiel a
                                   WHERE a.course = $restore->course_id")) {
            //Iterate over each referentiel->description
            $i = 0;   //Counter to send some output to the browser to avoid timeouts
            foreach ($referentiels as $referentiel) {
                //Increment counter
                $i++;
                $content = $referentiel->description;
                $result = restore_decode_content_links_worker($content,$restore);
                if ($result != $content) {
                    //Update record
                    $referentiel->description = addslashes($result);
                    $status = update_record("referentiel",$referentiel);
                    if (debugging()) {
                        if (!defined('RESTORE_SILENTLY')) {
                            echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                        }
                    }
                }
                //Do some output
                if (($i+1) % 5 == 0) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo ".";
                        if (($i+1) % 100 == 0) {
                            echo "<br />";
                        }
                    }
                    backup_flush(300);
                }
            }
        }
        return $status;
    }


    //This function returns a log record with all the necessay transformations
    //done. It's used by restore_log_module() to restore modules log.
    function referentiel_restore_logs($restore,$log) {
    // a adapter au referentiel
        $status = false;
                    
        //Depending of the action, we recode different things
        switch ($log->action) {
        case "add":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "update":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view all":
            $log->url = "index.php?id=".$log->course;
            $status = true;
            break;
        case "upload":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?a=".$mod->new_id;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view submission":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "submissions.php?id=".$mod->new_id;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "update grades":
            if ($log->cmid) {
                //Extract the referentiel id from the url field                             
                $assid = substr(strrchr($log->url,"="),1);
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$assid);
                if ($mod) {
                    $log->url = "submissions.php?id=".$mod->new_id;
                    $status = true;
                }
            }
            break;
        default:
            if (!defined('RESTORE_SILENTLY')) {
                echo "action (".$log->module."-".$log->action.") unknown. Not restored<br />";                 //Debug
            }
            break;
        }

        if ($status) {
            $status = $log;
        }
        return $status;
    }
?>
