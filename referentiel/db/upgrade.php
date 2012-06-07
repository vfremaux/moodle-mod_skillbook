<?php  //$Id: upgrade.php,v 1.3 2006/12/13 23:09:35 skodak Exp $

// This file keeps track of upgrades to
// the data module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_referentiel_upgrade($oldversion = 0) {

    global $CFG, $THEME, $db;

    $result = true;

/// And upgrade begins here. 

    if ($result && $oldversion < 2008052700) {

    /// Define field evaluation to be added to referentiel_certificate
        $table = new XMLDBTable('referentiel_certificate');
        $field = new XMLDBField('evaluation');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'valide');
    /// Launch add field evaluation
        $result = $result && add_field($table, $field);
    }		
	
    if ($result && $oldversion < 2008052800) {	
    /// Define field logo to be added to referentiel_institution
        $table = new XMLDBTable('referentiel_institution');
        $field = new XMLDBField('logo');
        $field->setAttributes(XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null, null, '');
    /// Launch add field referentiel_institution
        $result = $result && add_field($table, $field);
		
		if ($result){
	    /// Add some values to referentiel_institution
    	    $rec = new stdClass;
			$rec->idnumber = 'INCONNU';
			$rec->name = 'A COMPLETER';
			$rec->address = 'A COMPLETER';
			$rec->logo = '';
    	/// Insert the add action in log_display
        	$result = insert_record('referentiel_institution', $rec);
    	}
	}
	
	if ($result && $oldversion < 2008062300) {	// VERSION 1.2
   /// Define new  field liste_codes_competence to be added to referentiel_referentiel
        $table1 = new XMLDBTable('referentiel_referentiel');
        $field1 = new XMLDBField('liste_codes_competence');
        $field1->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, '', 'nb_domaines');
    /// Launch add field referentiel_referentiel
        $result = $result && add_field($table1, $field1);
   /// Define new  field liste_empreintes_competence to be added to referentiel_referentiel
        $field2 = new XMLDBField('liste_empreintes_competence');
        $field2->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, '', 'liste_codes_competence');
    /// Launch add field referentiel_referentiel
        $result = $result && add_field($table1, $field2);
   /// Define new  field logo to be added to referentiel_referentiel
        $field3 = new XMLDBField('logo');
        $field3->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null, 'local');
    /// Launch add field referentiel_referentiel
        $result = $result && add_field($table1, $field3);

   /// Define new  field footprint to be added to referentiel_skill_item
        $table2 = new XMLDBTable('referentiel_skill_item');
        $field4 = new XMLDBField('footprint');
        $field4->setAttributes(XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, null, null, '0', 'weight');
    /// change fiel type field  etiquette_url in referentiel_document
        $result = $result && add_field($table2, $field4);
	}
	
	if ($result && $oldversion < 2009042900) { // VERSION 3.0
	   /// Define new  field config  to be added to referentiel
        $table = new XMLDBTable('referentiel');
        $field = new XMLDBField('config');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, 'scol:0;creref:0;selref:0;impcert:0;', 'visible');
    /// Launch add field referentiel_referentiel
        $result = $result && add_field($table, $field);

	   /// Redefinir type field liste_codes_competence de referentiel_referentiel
        $table = new XMLDBTable('referentiel_referentiel');
        $field = new XMLDBField('liste_codes_competence');
        $field->setAttributes(XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null, 'nb_domaines');
    /// Change field type
        $result = $result && change_field_type($table, $field, true, true);
		
		/// Refinir type field liste_empreintes_competence referentiel_referentiel
        $field = new XMLDBField('liste_empreintes_competence');
        $field->setAttributes(XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null, 'liste_codes_competence');
    /// Change field type
        $result = $result && change_field_type($table, $field, true, true);
		
		/// Refinir type field comptencies de referentiel_activity
        $table = new XMLDBTable('referentiel_activity');
        $field = new XMLDBField('comptencies');
        $field->setAttributes(XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null, 'description');
    /// Change field type
        $result = $result && change_field_type($table, $field, true, true);
		
		/// Refinir type field competences_certificate de referentiel_certificate
        $table = new XMLDBTable('referentiel_certificate');
        $field = new XMLDBField('competences_certificat');
        $field->setAttributes(XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null, 'comment');
    /// Change field type
        $result = $result && change_field_type($table, $field, true, true);
		
		// NOUVEAUX CHAMPS
		/// Define new  fields to be added to referentiel_referentiel
        $table = new XMLDBTable('referentiel_referentiel');
        $field = new XMLDBField('referentielauthormail');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null, 'code');
        $result = $result && add_field($table, $field);
        $field = new XMLDBField('cle_referentiel');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null, 'referentielauthormail');
        $result = $result && add_field($table, $field);
        $field = new XMLDBField('password');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null, 'cle_referentiel');
        $result = $result && add_field($table, $field);

	/// Nouvelles tables 
    /// Define table referentiel_task to be created
        $table = new XMLDBTable('referentiel_task');

    /// Adding fields to table referentiel_task
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('type', XMLDB_TYPE_CHAR, '80', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('competences_task', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('criteres_evaluation', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('instanceid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('referentielid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('course', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('auteurid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('timestart', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('timeend', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

    /// Adding keys to table referentiel_task
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for referentiel_task
        $result = $result && create_table($table);
		
    /// Define table referentiel_consigne to be created
        $table = new XMLDBTable('referentiel_consigne');

    /// Adding fields to table referentiel_consigne
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('type', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('url', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('taskid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table referentiel_consigne
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for referentiel_consigne
        $result = $result && create_table($table);
		
    /// Define table referentiel_a_user_task to be created
        $table = new XMLDBTable('referentiel_a_user_task');

    /// Adding fields to table referentiel_a_user_task
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('taskid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table referentiel_a_user_task
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for referentiel_a_user_task
        $result = $result && create_table($table);
		
		// AJOUT CHAMP a table referentiel_activity
        $table = new XMLDBTable('referentiel_activity');
        $field = new XMLDBField('taskid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'approved');
    /// Launch add field taskid
        $result = $result && add_field($table, $field);
		
        $table = new XMLDBTable('referentiel_a_user_task');
        $field = new XMLDBField('date_selection');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'taskid');
    /// Launch add field taskid
        $result = $result && add_field($table, $field);
		
        $table = new XMLDBTable('referentiel_a_user_task');
        $field = new XMLDBField('activityid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'date_selection');
    /// Launch add field taskid
        $result = $result && add_field($table, $field);

	}
	
	
	   if ($result && $oldversion < 2009083100) { // VERSION 3.2.5
    /// Changing type of field certificatethreshold on table referentiel_referentiel to float
        $table = new XMLDBTable('referentiel_referentiel');
        $field = new XMLDBField('certificatethreshold');
        $field->setAttributes(XMLDB_TYPE_FLOAT, null, null, XMLDB_NOTNULL, null, null, null, '0', 'url');
    /// Launch change of type for field certificatethreshold
        $result = $result && change_field_type($table, $field);
		
    /// Changing type of field weight on table referentiel_skill_item to float
        $table = new XMLDBTable('referentiel_skill_item');
        $field = new XMLDBField('weight');
        $field->setAttributes(XMLDB_TYPE_FLOAT, null, null, XMLDB_NOTNULL, null, null, null, '0', 'type');
    /// Launch change of type for field weight
        $result = $result && change_field_type($table, $field);
    }
	
	if ($result && $oldversion < 2009110100) { // VERSION 4.0.1
    	// Nouveau champ liste_poids_competence dans referentiel_referentiel
   		/// Define new  field liste_poids_competence to be added to referentiel_referentiel
        $table = new XMLDBTable('referentiel_referentiel');
        $field = new XMLDBField('liste_poids_competence');
        $field->setAttributes(XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, null, null, 'liste_empreintes_competence');
    	/// Launch add field referentiel_referentiel
        $result = $result && add_field($table, $field);
		
		// Nouveau champ timemodifiedstudent dans referentiel_activity
		/// Define new  field liste_poids_competence to be added to referentiel_referentiel
        $table = new XMLDBTable('referentiel_activity');
        $field = new XMLDBField('timemodifiedstudent');
        $field->setAttributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'timecreated');
    	/// Launch add field 
        $result = $result && add_field($table, $field);
	}
	if ($result && $oldversion < 2009112800) { // VERSION 4.1.2
    	// Nouveau champ comptencies dans referentiel_certificate
   		/// Define new  field comptencies to be added to referentiel_certificate
        $table = new XMLDBTable('referentiel_certificate');
        $field = new XMLDBField('comptencies');
        $field->setAttributes(XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, null, null, 'competences_certificat');
    	/// Launch add field 
        $result = $result && add_field($table, $field);
	}
	if ($result && $oldversion < 2009122009) { // VERSION 4.2.0
    	// Nouveau champ target dans referentiel_document
   		/// Define new  field target to be added to referentiel_document
        $table1 = new XMLDBTable('referentiel_document');
        $field1 = new XMLDBField('target');
        $field1->setAttributes(XMLDB_TYPE_INTEGER, 4, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '1', 'activityid');
    	/// Launch add field 
        $result = $result && add_field($table1, $field1);
    	// Nouveau champ label dans referentiel_document
   		/// Define new  field etiquette to be added to referentiel_document
        $field2 = new XMLDBField('label');
        $field2->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null, 'target');
    	/// Launch add field 
        $result = $result && add_field($table1, $field2);

        $table2 = new XMLDBTable('referentiel_consigne');
        $field3 = new XMLDBField('target');
        $field3->setAttributes(XMLDB_TYPE_INTEGER, 4, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '1', 'taskid');
        $result = $result && add_field($table2, $field3);
		    $field4 = new XMLDBField('label');
        $field4->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null, 'target');
    	/// Launch add field 
        $result = $result && add_field($table2, $field4);
	}

	if ($result && $oldversion < 2010010500) { // VERSION 4.2.1
	   	/// Define new  default for field config for referentiel table
        $table = new XMLDBTable('referentiel');
        /*
		$field = new XMLDBField('config');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, 'scol:0;creref:0;selref:0;impcert:0;', 'visible');
        $result = $result && change_field_default($table, $field);
		*/
		$field2 = new XMLDBField('printconfig');
        $field2->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, 'refcert:1;instcert:0;numetu:1;nometu:1;etabetu:0;ddnetu:0;lieuetu:0;adretu:0;detail:1;pourcent:0;compdec:0;compval:1;nomreferent:0;jurycert:1;comcert:0;', 'config');
	    /// Launch add field referentiel
        $result = $result && add_field($table, $field2);
	}
	if ($result && $oldversion < 2010011000) { // VERSION 4.2.3	
	   	/// Define new  fields for referentiel_task table
        $table = new XMLDBTable('referentiel_task');       
		    $field = new XMLDBField('cle_souscription');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null, 'timeend');
        $result = $result && add_field($table, $field);
		
		    $field2 = new XMLDBField('souscription_libre');
        $field2->setAttributes(XMLDB_TYPE_INTEGER, 4, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '1', 'cle_souscription');
    	    /// Launch add field referentiel
        $result = $result && add_field($table, $field2);
	}

  	if ($result && $oldversion < 2010021200) { // VERSION 4.4.4	      
    /// Define table referentiel_notification_queue to be created
        $table = new XMLDBTable('referentiel_notification_queue');

    /// Adding fields to table referentiel_notification_queue
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('activityid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('type', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

    /// Adding keys to table referentiel_notification_queue
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addKeyInfo('activityid', XMLDB_KEY_FOREIGN, array('activityid'), 'referentiel_activity', array('id') );

    /// Adding index to table referentiel_notification_queue
        $table->addIndexInfo('user', XMLDB_INDEX_NOTUNIQUE, array ('userid') );

    /// Launch create table for referentiel_notification_queue
        $result = $result && create_table($table); 
	        
	   	/// Define new  fields for referentiel_activity table
        $table = new XMLDBTable('referentiel_activity');       
		    $field = new XMLDBField('mailed');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 0, 'taskid');
        $result = $result && add_field($table, $field);
		
		    $field = new XMLDBField('mailnow');
        $field->setAttributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 0, 'mailed');
    	    /// Launch add field referentiel
        $result = $result && add_field($table, $field);

	   	/// Define new  fields for referentiel_task table
        $table = new XMLDBTable('referentiel_task');       
		$field = new XMLDBField('mailed');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'souscription_libre');
        $result = $result && add_field($table, $field);
		
		$field = new XMLDBField('mailnow');
        $field->setAttributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'mailed');
    	    /// Launch add field referentiel
        $result = $result && add_field($table, $field);

	   	/// Define new  fields for referentiel_certificate table
        $table = new XMLDBTable('referentiel_certificate'); 
		
        $field = new XMLDBField('mailed');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'evaluation');
        $result = $result && add_field($table, $field);
		
		$field = new XMLDBField('mailnow');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'mailed');
    	    /// Launch add field referentiel
        $result = $result && add_field($table, $field);        
  	}
  

  	if ($result && $oldversion < 2010022800) { // VERSION 5.1.0	      
    /// Define table referentiel_activity_modules to be created
        $table = new XMLDBTable('referentiel_activity_modules');

    /// Adding fields to table referentiel_activity_modules
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('type', XMLDB_TYPE_CHAR, '80', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('moduleid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('instanceid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('referentielid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('course', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('activityid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
    /// Adding keys to table referentiel_activity_modules
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for referentiel_activity_modules
        $result = $result && create_table($table); 
    }

	  if ($result && $oldversion < 2010031600) { // VERSION 5.2.0	
	   	/// Define new  fields for referentiel_task table
        $table = new XMLDBTable('referentiel_task');       
		    $field = new XMLDBField('hidden');
        $field->setAttributes(XMLDB_TYPE_INTEGER, 4, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'souscription_libre');
    	  /// Launch add field referentiel
        $result = $result && add_field($table, $field);
	 }


   	if ($result && $oldversion < 2010032500) { // VERSION 5.2.1	      
    /// Define table referentiel_accompagnement to be created
        $table = new XMLDBTable('referentiel_accompagnement');

    /// Adding fields to table referentiel_accompagnement
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('coaching', XMLDB_TYPE_CHAR, '3', null, XMLDB_NOTNULL, null, null, null, 'REF');
        $table->addFieldInfo('instanceid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('courseid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('teacherid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
    /// Adding keys to table referentiel_accompagnement
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for referentiel_accompagnement
        $result = $result && create_table($table); 
    }
 
    if ($result && $oldversion < 2010033110) { // VERSION 5.2.3

    /// Drop table referentiel_notification_queue
    /// because table name don't respect XML facet specification

    /// Define table referentiel_notification to be created
        $table = new XMLDBTable('referentiel_notification');

    /// Adding fields to table referentiel_notification
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('activityid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('type', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

    /// Adding keys to table referentiel_notification
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addKeyInfo('activityid', XMLDB_KEY_FOREIGN, array('activityid'), 'referentiel_activity', array('id') );

    /// Adding index to table referentiel_notification
        $table->addIndexInfo('user', XMLDB_INDEX_NOTUNIQUE, array ('userid') );

    /// Launch create table for referentiel_notification
        $result = $result && create_table($table);

        if ($result){
          $tableold = new XMLDBTable('referentiel_notification_queue');
          /// Silenty drop any previous referentiel_notification_queue table
          if (table_exists($tableold)) {
            $sql="SELECT * FROM {$CFG->prefix}referentiel_notification_queue";
            if ($rs= get_records_sql($sql)){
              foreach ($rs as $res){
                execute_sql("INSERT INTO {$CFG->prefix}referentiel_notification (id, userid, activityid, timemodified, type) VALUES ($res->id, $res->userid, $res->activityid, $res->timemodified, $res->type)", false);
              }
            }
            $status = drop_table($tableold, true, false);
          }    
        }
    }

    if ($result && $oldversion < 2010060900) { // VERSION 5.3.3
    /// Define table referentiel_certificate to be updated
        $table = new XMLDBTable('referentiel_certificate');
    /// Adding fields to table referentiel_certificate
        $field = new XMLDBField('synthese_certificat');
        $field->setAttributes(XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null, 'mailnow');
    /// Launch add field
        $result = $result && add_field($table, $field);
    }
    if ($result && $oldversion < 2010101800) { // VERSION 5.4.3
    /// Define table referentiel_certificate to be updated
        $table = new XMLDBTable('referentiel_referentiel');
    /// Adding fields to table referentiel_referentiel
        $field = new XMLDBField('config');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, 'scol:0;creref:0;selref:0;impcert:0;', 'logo');
    /// Launch add field referentiel_referentiel
        $result = $result && add_field($table, $field);
    /// Adding fields to table referentiel_referentiel
		$field2 = new XMLDBField('printconfig');
        $field2->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, 'refcert:1;instcert:0;numetu:1;nometu:1;etabetu:0;ddnetu:0;lieuetu:0;adretu:0;detail:1;pourcent:0;compdec:0;compval:1;nomreferent:0;jurycert:1;comcert:0;', 'config');
	    /// Launch add field referentiel
        $result = $result && add_field($table, $field2);
    }

  	if ($result && $oldversion < 2010111100) { // VERSION 5.4.4
    /// Define table referentiel_activity_modules to be created
        $table = new XMLDBTable('referentiel_activity_modules');
    /// Adding fields
        $field = new XMLDBField('activityid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 0, 'activityid');
    /// Launch add field
        $result = $result && add_field($table, $field);
    }

	  return $result;
}

?>
