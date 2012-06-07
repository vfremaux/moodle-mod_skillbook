<?php //$Id: backuplib.php,v 1.12 2006/09/21 09:35:20 stronk7 Exp $
    //This php script contains all the stuff to backup/restore
    //referentiel mods

    //This is the "graphical" structure of the referentiel mod:
    //
    //                     referentiel (instance)
    //                    (CL,pk->id)
    //                        |
    //                        |---------------------------------------|-------------------------------|-------------------------------|-----------------------------|
    //                        |                                       |                               |                               |                             |
    //                 referentiel_activites                referentiel_task                referentiel_referentiel                referentiel_certificate        referentiel_etabisssement     
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

    //This function executes all the backup procedure about this mod
    function referentiel_backup_mods($bf,$preferences) {

        global $CFG;

        $status = true;

        //Iterate over referentiel table
        $referentiels = get_records ("referentiel","course",$preferences->backup_course,"id");
        if ($referentiels) {
            foreach ($referentiels as $referentiel) {
                if (backup_mod_selected($preferences,'referentiel',$referentiel->id)) {
                    $status = referentiel_backup_one_mod($bf,$preferences,$referentiel);
                    // backup files happens in backup_one_mod now too.
                }
            }
        }
        return $status;  
    }

    function referentiel_backup_one_mod($bf,$preferences,$referentiel) {
        
        global $CFG;
    	
        if (is_numeric($referentiel)) { // inutile ici
            $referentiel = get_record('referentiel','id',$referentiel);
        }
    	
        $status = true;
		/*
	Referentiel instance
	
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
  printconfig
  PRIMARY KEY (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Instance de referentiel de competence';

		*/
        //Start mod
        fwrite ($bf,start_tag("MOD",3,true));
        //Print referentiel data
        fwrite ($bf,full_tag("ID",4,false,$referentiel->id));
        fwrite ($bf,full_tag("MODTYPE",4,false,"referentiel"));
        fwrite ($bf,full_tag("NAME",4,false,$referentiel->name));
        fwrite ($bf,full_tag("description",4,false,$referentiel->description));
        fwrite ($bf,full_tag("domainlabel",4,false,$referentiel->domainlabel));
        fwrite ($bf,full_tag("skilllabel",4,false,$referentiel->skilllabel));
		    fwrite ($bf,full_tag("itemlabel",4,false,$referentiel->itemlabel));
        fwrite ($bf,full_tag("timecreated",4,false,$referentiel->timecreated));
        fwrite ($bf,full_tag("referentielid",4,false,$referentiel->referentielid));
        fwrite ($bf,full_tag("VISIBLE",4,false,$referentiel->visible));
        fwrite ($bf,full_tag("CONFIG",4,false,$referentiel->config));
        fwrite ($bf,full_tag("printconfig",4,false,$referentiel->printconfig));
        
		// referentiel_referentiel
		$status = backup_referentiel_referentiel ($bf,$preferences,$referentiel->referentielid);
		
        //if we've selected to backup users info, then execute backup_referentiel_submisions and
        //backup_referentiel_files_instance
        if (backup_userdata_selected($preferences,'referentiel',$referentiel->id)) {
           $status = backup_referentiel_taches($bf,$preferences,$referentiel->id);
           $status = backup_referentiel_activites($bf,$preferences,$referentiel->id);
           $status = backup_referentiel_certificats($bf,$preferences,$referentiel->referentielid);
			     $status = backup_referentiel_etablissement_students ($bf,$preferences);
			     $status = backup_referentiel_files_instance($bf,$preferences,$referentiel->id);
        }
        //End mod
        $status =fwrite ($bf,end_tag("MOD",3,true));

        return $status;
    }

    //Backup referentiel_referentiel contents (executed from referentiel_backup_mods)
    function backup_referentiel_referentiel ($bf,$preferences,$referentiel) {

        global $CFG;

        $status = true;
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
        $referentiel_referentiel = get_record("referentiel_referentiel","id",$referentiel);
        //If there is submissions
        if ($referentiel_referentiel) {
            //Write start tag
            $status =fwrite ($bf,start_tag("REFERENTIEL",4,true));
            
            //Print activity contents
            fwrite ($bf,full_tag("ID",5,false,$referentiel_referentiel->id));       
            fwrite ($bf,full_tag("NAME",5,false,$referentiel_referentiel->name));       
            fwrite ($bf,full_tag("code",5,false,$referentiel_referentiel->code));       
            fwrite ($bf,full_tag("referentielauthormail",5,false,$referentiel_referentiel->referentielauthormail));
			      fwrite ($bf,full_tag("CLE_REFERENTIEL",5,false,$referentiel_referentiel->cle_referentiel));
			      fwrite ($bf,full_tag("password",5,false,$referentiel_referentiel->password));
			      fwrite ($bf,full_tag("description",5,false,$referentiel_referentiel->description));
			      fwrite ($bf,full_tag("url",5,false,$referentiel_referentiel->url));
			      fwrite ($bf,full_tag("certificatethreshold",5,false,$referentiel_referentiel->certificatethreshold));
			      fwrite ($bf,full_tag("TIMEMODIFIED",5,false,$referentiel_referentiel->timemodified));
			      fwrite ($bf,full_tag("NB_DOMAINES",5,false,$referentiel_referentiel->nb_domaines));
			      fwrite ($bf,full_tag("LISTE_CODES_COMPETENCE",5,false,$referentiel_referentiel->liste_codes_competence));
			      fwrite ($bf,full_tag("LISTE_EMPREINTES_COMPETENCE",5,false,$referentiel_referentiel->liste_empreintes_competence));
			      fwrite ($bf,full_tag("LOCAL",5,false,$referentiel_referentiel->local));
			      fwrite ($bf,full_tag("logo",5,false,$referentiel_referentiel->logo));
				
			// domaines
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
			     $referentiel_domaines = get_records("referentiel_domain","referentielid",$referentiel_referentiel->id);
			     if ($referentiel_domaines){
				      //Iterate over each domaine
				      $status =fwrite ($bf,start_tag("DOMAINES",5,true));
            	foreach ($referentiel_domaines as $domaine) {
					     // stard domaine
					     $status =fwrite ($bf,start_tag("DOMAINE",6,true));
                	//Print domaine contents
                	fwrite ($bf,full_tag("ID",7,false,$domaine->id));   
                	fwrite ($bf,full_tag("code",7,false,$domaine->code));   
                	fwrite ($bf,full_tag("description",7,false,$domaine->description));   
                	fwrite ($bf,full_tag("referentielid",7,false,$domaine->referentielid));   
                	fwrite ($bf,full_tag("sortorder",7,false,$domaine->sortorder));
                	fwrite ($bf,full_tag("NB_COMPETENCES",7,false,$domaine->nb_competences));   
						
					// competences
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
					       $referentiel_competences = get_records("referentiel_skill","domainid",$domaine->id);
					       if ($referentiel_competences){
						        // Iterate
						        $status =fwrite ($bf,start_tag("COMPETENCES",7,true));
						        foreach ($referentiel_competences as $competence) {
							         // stard competence
							         $status =fwrite ($bf,start_tag("COMPETENCE",8,true));
							//Print competence contents
							fwrite ($bf,full_tag("ID",9,false,$competence->id));
							fwrite ($bf,full_tag("code",9,false,$competence->code));
							fwrite ($bf,full_tag("description",9,false,$competence->description));
							fwrite ($bf,full_tag("domainid",9,false,$competence->domainid));
							fwrite ($bf,full_tag("sortorder",9,false,$competence->sortorder));
							fwrite ($bf,full_tag("NB_ITEM_COMPETENCES",9,false,$competence->nb_item_competences));
							
							// items
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
							$referentiel_items = get_records("referentiel_skill_item","skillid",$competence->id);
							if ($referentiel_items){
								// Iterate
								$status =fwrite ($bf,start_tag("ITEMS",9,true));
								foreach ($referentiel_items as $item ) {
									// stard item
									$status =fwrite ($bf,start_tag("ITEM",10,true));
									//Print item contents
									fwrite ($bf,full_tag("ID",11,false,$item->id));
									fwrite ($bf,full_tag("code",11,false,$item->code));
									fwrite ($bf,full_tag("description",11,false,$item->description));
									fwrite ($bf,full_tag("referentielid",11,false,$item->referentielid));
									fwrite ($bf,full_tag("skillid",11,false,$item->skillid));
									fwrite ($bf,full_tag("type",11,false,$item->type));
									fwrite ($bf,full_tag("weight",11,false,$item->weight));
									fwrite ($bf,full_tag("footprint",11,false,$item->footprint));
									fwrite ($bf,full_tag("sortorder",11,false,$item->sortorder));
									$status =fwrite ($bf,end_tag("ITEM",10,true));
								}
								$status =fwrite ($bf,end_tag("ITEMS",9,true));
							}
							$status =fwrite ($bf,end_tag("COMPETENCE",8,true));
						}
						$status =fwrite ($bf,end_tag("COMPETENCES",7,true));
					}
					$status =fwrite ($bf,end_tag("DOMAINE",6,true));
				}
				$status =fwrite ($bf,end_tag("DOMAINES",5,true));
			}
			$status =fwrite ($bf,end_tag("REFERENTIEL",4,true));
		}
		//End referentiel
        return $status;
    }

	
// ###################################### ACTIVITES ########################################
	
	//Backup referentiel_activites contents (executed from referentiel_backup_mods)
    function backup_referentiel_activites ($bf,$preferences,$referentiel) {

        global $CFG;

        $status = true;
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
        $referentiel_activites = get_records("referentiel_activity","instanceid",$referentiel,"id");
        //If there is submissions
        if ($referentiel_activites) {
            //Write start tag
            $status =fwrite ($bf,start_tag("ACTIVITES",4,true));
            //Iterate over each activite
            foreach ($referentiel_activites as $activite) {
                //Start activite
                $status =fwrite ($bf,start_tag("ACTIVITE",5,true));
                //Print activity contents
                fwrite ($bf,full_tag("ID",6,false,$activite->id));       
                fwrite ($bf,full_tag("TYPE_ACTIVITE",6,false,$activite->type_activite));       
                fwrite ($bf,full_tag("description",6,false,$activite->description));       
                fwrite ($bf,full_tag("comptencies",6,false,$activite->comptencies));       
                fwrite ($bf,full_tag("comment",6,false,$activite->comment));       
                fwrite ($bf,full_tag("instanceid",6,false,$activite->instanceid));       
                fwrite ($bf,full_tag("referentielid",6,false,$activite->referentielid));       
                fwrite ($bf,full_tag("course",6,false,$activite->course));       // en principe inutile 
				fwrite ($bf,full_tag("USERID",6,false,$activite->userid));       
                fwrite ($bf,full_tag("TEACHERID",6,false,$activite->teacherid));       
                fwrite ($bf,full_tag("timecreated",6,false,$activite->timecreated));       
                fwrite ($bf,full_tag("timemodified",6,false,$activite->timemodified));
                fwrite ($bf,full_tag("APPROVED",6,false,$activite->approved));
				fwrite ($bf,full_tag("taskid",6,false,$activite->taskid));
                fwrite ($bf,full_tag("timemodifiedstudent",6,false,$activite->timemodifiedstudent));				
				
                //End activite
				
				 
				// associations tache ?
				if ($activite->taskid!=0){ // activite associee a une tache
					$status =  backup_referentiel_activite_task($bf, $preferences, $activite->id, $activite->taskid);
				}

				// Documents associes
				/*
				
CREATE TABLE mdl_referentiel_document (
  id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  type varchar(20) NOT NULL DEFAULT '',
  description text NOT NULL,
  url varchar(255) NOT NULL DEFAULT '',
  activityid bigint(10) unsigned NOT NULL,
  PRIMARY KEY (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Document';

				*/
				$referentiel_documents = get_records("referentiel_document","activityid",$activite->id,"id");
				if ($referentiel_documents){
					//Iterate over each activite
					$status =fwrite ($bf,start_tag("DOCUMENTS",6,true));
          foreach ($referentiel_documents as $document) {
						//Start document
                		$status =fwrite ($bf,start_tag("DOCUMENT",7,true));
                		//Print activity contents
                		fwrite ($bf,full_tag("ID",8,false,$document->id));       
                		fwrite ($bf,full_tag("type",8,false,$document->type));
						        fwrite ($bf,full_tag("description",8,false,$document->description));
						        fwrite ($bf,full_tag("url",8,false,$document->url));
						        fwrite ($bf,full_tag("activityid",8,false,$document->activityid));
						        $status =fwrite ($bf,end_tag("DOCUMENT",7,true));
					}
					$status =fwrite ($bf,end_tag("DOCUMENTS",6,true));
				}
        $status =fwrite ($bf,end_tag("ACTIVITE",5,true));
      }
      //Write end tag
      $status =fwrite ($bf,end_tag("ACTIVITES",4,true));
    }
        return $status;
}

	
// ###################################### TACHES ########################################

    //Backup referentiel_tasks contents (executed from referentiel_backup_mods)
    function backup_referentiel_taches ($bf,$preferences,$referentiel) {

        global $CFG;

        $status = true;
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
        $referentiel_taches = get_records("referentiel_task","instanceid",$referentiel,"id");
        //If there is submissions
        if ($referentiel_taches) {
            //Write start tag
            $status =fwrite ($bf,start_tag("TASKS",4,true));
            //Iterate over each activite
            foreach ($referentiel_taches as $tache) {
                //Start task
                $status =fwrite ($bf,start_tag("TASK",5,true));
                //Print task contents
                fwrite ($bf,full_tag("ID",6,false,$tache->id));       
                fwrite ($bf,full_tag("type",6,false,$tache->type));       
                fwrite ($bf,full_tag("description",6,false,$tache->description));       
                fwrite ($bf,full_tag("COMPETENCES_TASK",6,false,$tache->competences_task));       
                fwrite ($bf,full_tag("CRITERES_EVALUATION",6,false,$tache->criteres_evaluation));       
                fwrite ($bf,full_tag("instanceid",6,false,$tache->instanceid));       
                fwrite ($bf,full_tag("referentielid",6,false,$tache->referentielid));       
                fwrite ($bf,full_tag("course",6,false,$tache->course));       // en principe inutile 
				        fwrite ($bf,full_tag("AUTEURID",6,false,$tache->auteurid));       
                fwrite ($bf,full_tag("timecreated",6,false,$tache->timecreated));       
                fwrite ($bf,full_tag("timemodified",6,false,$tache->timemodified));
				        fwrite ($bf,full_tag("timestart",6,false,$tache->timestart));
				        fwrite ($bf,full_tag("timeend",6,false,$tache->timeend));
				        fwrite ($bf,full_tag("CLE_SOUSCRIPTION",6,false,$tache->cle_souscription));
				        fwrite ($bf,full_tag("SOUSCRIPTION_LIBRE",6,false,$tache->souscription_libre));
				        
                //End task
				
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
				$referentiel_consignes = get_records("referentiel_consigne","taskid",$tache->id,"id");
				if ($referentiel_consignes){
					      //Iterate over each activite
					      $status =fwrite ($bf,start_tag("CONSIGNES",6,true));
            		foreach ($referentiel_consignes as $consigne) {
						        //Start document
                		$status =fwrite ($bf,start_tag("CONSIGNE",7,true));
                		//Print activity contents
                		fwrite ($bf,full_tag("ID",8,false,$consigne->id));       
                		fwrite ($bf,full_tag("type",8,false,$consigne->type));
						        fwrite ($bf,full_tag("description",8,false,$consigne->description));
						        fwrite ($bf,full_tag("url",8,false,$consigne->url));
						        fwrite ($bf,full_tag("taskid",8,false,$consigne->taskid));
						        $status =fwrite ($bf,end_tag("CONSIGNE",7,true));
					      }
					      $status =fwrite ($bf,end_tag("CONSIGNES",6,true));
				}
                
				$status =fwrite ($bf,end_tag("TASK",5,true));
      }
      //Write end tag
      $status =fwrite ($bf,end_tag("TASKS",4,true));
    }
        return $status;
}


    //Backup referentiel_a_user_task contents (executed from referentiel_backup_mods)
    function backup_referentiel_activite_task($bf,$preferences, $activite_id, $task_id) {
	// recupere l'association entre une activite et une tache
	// appele depuis backup_referentiel_activites_mod(
        global $CFG;

        $status = true;
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
		if (($activite_id>0) && ($task_id>0)){
			// verifier si association existe
			$sql='SELECT * FROM '. $CFG->prefix . 'referentiel_a_user_task  WHERE taskid='.$task_id.' AND activityid='.$activite_id.' ';
			// echo '<br />DEBUG :: backuplib.php :: 482 :: SQL: '.$sql."\n";
			$referentiel_a_users_tasks = get_records_sql($sql);
			if ($referentiel_a_users_tasks){
	            //Write start tag
    	        $status =fwrite ($bf,start_tag("USERS_TASKS",6,true));
        	    //Iterate over each record
            	foreach ($referentiel_a_users_tasks as $referentiel_a_user_task) {
			        //If there is a_user_task
    			    if ($referentiel_a_user_task) {
                		//Start task
	                	$status =fwrite ($bf,start_tag("USER_TASK",7,true));
	    	            //Print contents
    	    	        fwrite ($bf,full_tag("ID",8,false,$referentiel_a_user_task->id));       
        	    	    fwrite ($bf,full_tag("userid",8,false,$referentiel_a_user_task->userid));       
            	    	fwrite ($bf,full_tag("taskid",8,false,$referentiel_a_user_task->taskid));       
	            	    fwrite ($bf,full_tag("DATE_SELECTION",8,false,$referentiel_a_user_task->date_selection));       
    	            	fwrite ($bf,full_tag("activityid",8,false,$referentiel_a_user_task->activityid));       
	        	        //End task
    	        	    $status =fwrite ($bf,end_tag("USER_TASK",7,true));
        			}
				}
				//Write end tag
           		$status =fwrite ($bf,end_tag("USERS_TASKS",6,true));
			}
    	}
	    return $status;
    }

    //Backup referentiel_a_user_task contents (executed from referentiel_backup_mods)
    function backup_referentiel_a_user_task ($bf,$preferences,$task_id) {
	// recupere toutes les association ayant in taskid donné
	// n'est plus utilise dans cette appli
        global $CFG;

        $status = true;
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
        $referentiel_a_user_task = get_records("referentiel_a_user_task","taskid",$task_id);
        //If there is submissions
        if ($referentiel_a_user_task) {
            //Write start tag
            $status =fwrite ($bf,start_tag("USERS_TASKS",6,true));
            //Iterate over each record
            foreach ($referentiel_a_user_task as $tache) {
                //Start task
                $status =fwrite ($bf,start_tag("USER_TASK",7,true));
                //Print contents
                fwrite ($bf,full_tag("ID",8,false,$tache->id));       
                fwrite ($bf,full_tag("userid",8,false,$tache->userid));       
                fwrite ($bf,full_tag("taskid",8,false,$tache->taskid));       
                fwrite ($bf,full_tag("DATE_SELECTION",8,false,$tache->date_selection));       
                fwrite ($bf,full_tag("activityid",8,false,$tache->activityid));       
                //End task
                $status =fwrite ($bf,end_tag("USER_TASK",7,true));
            }
            //Write end tag
            $status =fwrite ($bf,end_tag("USERS_TASKS",6,true));
        }
        return $status;
    }

// ################################### ETABLISSEMENT ##################################
	//Backup referentiel_etablissements contents (executed from referentiel_backup_mods)
    function backup_referentiel_etablissement_students ($bf,$preferences) {

        global $CFG;

        $status = true;
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
        $referentiel_etablissements = get_records("referentiel_institution");
        //If there is submissions
        if ($referentiel_etablissements) {
            //Write start tag
            $status =fwrite ($bf,start_tag("ETABLISSEMENTS",4,true));
            //Iterate over each activite
            foreach ($referentiel_etablissements as $etablissement) {
                //Start etablissement
                $status =fwrite ($bf,start_tag("ETABLISSEMENT",5,true));
                //Print activity contents
                fwrite ($bf,full_tag("ID",6,false,$etablissement->id));       
                fwrite ($bf,full_tag("idnumber",6,false,$etablissement->idnumber));       
				fwrite ($bf,full_tag("name",6,false,$etablissement->name));
                fwrite ($bf,full_tag("address",6,false,$etablissement->address));
				fwrite ($bf,full_tag("logo",6,false,$etablissement->logo));
				//End etablissement
				
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
				$referentiel_students = get_records("referentiel_student","ref_etablissement",$etablissement->id);
				if ($referentiel_students){
					//Iterate over each student
					$status =fwrite ($bf,start_tag("studentS",6,true));
            		foreach ($referentiel_students as $student) {
						//Start document
                		$status =fwrite ($bf,start_tag("student",7,true));
                		//Print activity contents
                		fwrite ($bf,full_tag("ID",8,false,$student->id));       
						fwrite ($bf,full_tag("NUM_student",8,false,$student->num_student));
						fwrite ($bf,full_tag("DDN_student",8,false,$student->ddn_student));
						fwrite ($bf,full_tag("LIEU_NAISSANCE",8,false,$student->lieu_naissance));
						fwrite ($bf,full_tag("DEPARTEMENT_NAISSANCE",8,false,$student->departement_naissance));
						fwrite ($bf,full_tag("ADRESSE_student",8,false,$student->adresse_student));
						fwrite ($bf,full_tag("USERID",8,false,$student->userid));
						fwrite ($bf,full_tag("REF_ETABLISSEMENT",8,false,$student->ref_etablissement));
						
						$status =fwrite ($bf,end_tag("student",7,true));
					}
					$status =fwrite ($bf,end_tag("studentS",6,true));
				}
                $status =fwrite ($bf,end_tag("ETABLISSEMENT",5,true));
            }
            //Write end tag
            $status =fwrite ($bf,end_tag("ETABLISSEMENTS",4,true));
        }
        return $status;
    }

    //Backup referentiel_referentiel contents (executed from referentiel_backup_mods)
    function backup_referentiel_certificats($bf,$preferences,$referentiel) {

        global $CFG;

        $status = true;
/*

DROP TABLE IF EXISTS `mdl_referentiel_certificat`;
CREATE TABLE IF NOT EXISTS `mdl_referentiel_certificat` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `comment` text NOT NULL,
  `competences_certificat` text NOT NULL,
  `comptencies` text NOT NULL,
  `decision_jury` varchar(80) NOT NULL DEFAULT '',
  `date_decision` bigint(10) unsigned NOT NULL DEFAULT '0',
  `referentielid` bigint(10) unsigned NOT NULL DEFAULT '0',
  `userid` bigint(10) unsigned NOT NULL,
  `teacherid` bigint(10) unsigned NOT NULL,
  `verrou` tinyint(1) unsigned NOT NULL,
  `valide` tinyint(1) unsigned NOT NULL,
  `evaluation` bigint(10) unsigned NOT NULL,
  `mailed` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `mailnow` bigint(10) unsigned NOT NULL DEFAULT '0',
  `synthese_certificat` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Certificat' AUTO_INCREMENT=1 ;

*/
        $referentiel_certificats = get_records("referentiel_certificate","referentielid",$referentiel,"id");
        //If there is certificats
        if ($referentiel_certificats) {
            //Write start tag
            $status =fwrite ($bf,start_tag("CERTIFICATS",4,true));
			//Iterate over each certificat
            foreach ($referentiel_certificats as $certificat) {
				// stard domaine
				$status =fwrite ($bf,start_tag("CERTIFICAT",5,true));
                //Print contents
                fwrite ($bf,full_tag("ID",6,false,$certificat->id));
				fwrite ($bf,full_tag("comment",6,false,$certificat->comment));
				fwrite ($bf,full_tag("COMPETENCES_CERTIFICAT",6,false,$certificat->competences_certificat));
                fwrite ($bf,full_tag("DECISION_JURY",6,false,$certificat->decision_jury));
				fwrite ($bf,full_tag("DATE_DECISION",6,false,$certificat->date_decision));
				fwrite ($bf,full_tag("referentielid",6,false,$certificat->referentielid));
				fwrite ($bf,full_tag("USERID",6,false,$certificat->userid));
				fwrite ($bf,full_tag("TEACHERID",6,false,$certificat->teacherid));
				fwrite ($bf,full_tag("VERROU",6,false,$certificat->verrou));
				fwrite ($bf,full_tag("VALIDE",6,false,$certificat->valide));
				fwrite ($bf,full_tag("EVALUATION",6,false,$certificat->evaluation));
				fwrite ($bf,full_tag("SYNTHESE_CERTIFICAT",6,false,$certificat->synthese_certificat));
				$status =fwrite ($bf,end_tag("CERTIFICAT",5,true));
			}
			$status =fwrite ($bf,end_tag("CERTIFICATS",4,true));
		}
		//End certificats
        return $status;
    }

    //Backup referentiel files because we've selected to backup user info
    //and files are user info's level
    function backup_referentiel_files($bf,$preferences) {

        global $CFG;
       
        $status = true;

        //First we check to moddata exists and create it as necessary
        //in temp/backup/$backup_code  dir
        $status = check_and_create_moddata_dir($preferences->backup_unique_code);
        //Now copy the referentiel dir
        if ($status) {
            //Only if it exists !! Thanks to Daniel Miksik.
            if (is_dir($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/referentiel")) {
                $status = backup_copy_file($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/referentiel",
                                           $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/referentiel");
            }
        }

        return $status;

    } 

    function backup_referentiel_files_instance($bf,$preferences,$instanceid) {

        global $CFG;
       
        $status = true;

        //First we check to moddata exists and create it as necessary
        //in temp/backup/$backup_code  dir
        $status = check_and_create_moddata_dir($preferences->backup_unique_code);
        $status = check_dir_exists($CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/referentiel/",true);
        //Now copy the referentiel dir
        if ($status) {
            //Only if it exists !! Thanks to Daniel Miksik.
            if (is_dir($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/referentiel/".$instanceid)) {
                $status = backup_copy_file($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/referentiel/".$instanceid,
                                           $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/referentiel/".$instanceid);
            }
        }

        return $status;

    } 

    //Return an array of info (name,value)
    function referentiel_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
        if (!empty($instances) && is_array($instances) && count($instances)) {
            $info = array();
            foreach ($instances as $id => $instance) {
                $info += referentiel_check_backup_mods_instances($instance,$backup_unique_code);
            }
            return $info;
        }
        //First the course data
        $info[0][0] = get_string("modulenameplural","referentiel");
        if ($ids = referentiel_ids ($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }

        //Now, if requested, the user_data
        if ($user_data) {
            $info[1][0] = get_string("task","referentiel");
            if ($ids = referentiel_task_ids_by_course ($course)) { 
                $info[1][1] = count($ids);
            } else {
                $info[1][1] = 0;
            }
            $info[2][0] = get_string("activite","referentiel");
            if ($ids = referentiel_activite_ids_by_course ($course)) { 
                $info[2][1] = count($ids);
            } else {
                $info[2][1] = 0;
            }
        }
		
		// DEBUG
		// echo "<br /> DEBUG :: backuplib.php :: LIGNE 367 :: INFO <br />\n";
		// print_r($info);
		
        return $info;
    }

    //Return an array of info (name,value)
    function referentiel_check_backup_mods_instances($instance,$backup_unique_code) {
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';
        if (!empty($instance->userdata)) {
            $info[$instance->id.'1'][0] = get_string("task","referentiel");
            if ($ids = referentiel_task_ids_by_instance ($instance->id)) {
                $info[$instance->id.'1'][1] = count($ids);
            } else {
                $info[$instance->id.'1'][1] = 0;
            }
            $info[$instance->id.'2'][0] = get_string("activite","referentiel");
            if ($ids = referentiel_activite_ids_by_instance ($instance->id)) {
                $info[$instance->id.'2'][1] = count($ids);
            } else {
                $info[$instance->id.'2'][1] = 0;
            }
        }

		// DEBUG
		// echo "<br /> DEBUG :: backuplib.php :: LIGNE 393 :: INFO <btr />\n";
		// print_r($info);
        return $info;
	}

    //Return a content encoded to support interactivities linking. Every module
    //should have its own. They are called automatically from the backup procedure.
    function referentiel_encode_content_links ($content,$preferences) {

        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        //Link to the list of referentiels
        $buscar="/(".$base."\/mod\/referentiel\/index.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@REFERENTIELINDEX*$2@$',$content);

        //Link to referentiel view by moduleid
        $buscar="/(".$base."\/mod\/referentiel\/view.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@REFERENTIELVIEWBYID*$2@$',$result);

        return $result;
    }

    // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    //Returns an array of referentiels id 
    function referentiel_ids ($course) {

        global $CFG;

        return get_records_sql ("SELECT a.id, a.course
                                 FROM {$CFG->prefix}referentiel a
                                 WHERE a.course = '$course'");
    }
    
    //Returns an array of referentiel_activity id
    function referentiel_activite_ids_by_course ($course) {
        global $CFG;

        return get_records_sql ("SELECT s.id , s.instanceid
                                 FROM {$CFG->prefix}referentiel_activity s,
                                      {$CFG->prefix}referentiel a
                                 WHERE a.course = '$course' AND
                                       s.instanceid = a.id");
    }

    //Returns an array of referentiel_submissions id
    function referentiel_activite_ids_by_instance ($instanceid) {

        global $CFG;

        return get_records_sql ("SELECT s.id , s.instanceid
                                 FROM {$CFG->prefix}referentiel_activity s
                                 WHERE s.instanceid = $instanceid");
    }

	    //Returns an array of referentiel_activity id
    function referentiel_task_ids_by_course ($course) {
        global $CFG;

        return get_records_sql ("SELECT s.id , s.instanceid
                                 FROM {$CFG->prefix}referentiel_task s,
                                      {$CFG->prefix}referentiel a
                                 WHERE a.course = '$course' AND
                                       s.instanceid = a.id");
    }

    //Returns an array of referentiel_submissions id
    function referentiel_task_ids_by_instance ($instanceid) {

        global $CFG;

        return get_records_sql ("SELECT s.id , s.instanceid
                                 FROM {$CFG->prefix}referentiel_task s
                                 WHERE s.instanceid = $instanceid");
    }

?>
