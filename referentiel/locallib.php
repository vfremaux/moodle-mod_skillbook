<?php  // $Id:  lib.php,v 1.0 2008/04/29 00:00:00 jfruitet Exp $
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 2005 Martin Dougiamas  http://dougiamas.com             //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Library of functions and constants for module referentiel
 * 
 * @author jfruitet
 * @version $Id: lib.php,v 5.0 2010/03/27 00:00:00 jfruitet Exp $
 * @package referentiel v 5.0 2010/03/27 00:00:00 
 **/
 
// les constantes suivantes permettent de tuner le fonctionnement du module
// a ne modifier qu'avec précaution

define('EDITOR_ON', 0);// editeur de referentiels simplifié wysiwyg actif (necessite le dossier mod/referentiel/editor sur le serveur)
// define('EDITOR_ON', 0);   // editeur inactif

define('MAXBOITESSELECTION', 6);  // à réduire si le nombre de boites de selection des students
// ne tient pas dans la page sans ascenceur horizontal

define('NOTIFICATION_REFERENTIEL', 1); // placer à 0 pour désactiver la notification
define('NOTIFICATION_AUTEUR', 0); // placer à 1 pour activer la notification de l'auteur de la declaration ; notification en général inutile
define('NOTIFICATION_DELAI', 0); // placer à la valeur à 1 pour activer la temporisation de la notification entre le moment ou l'activité
// est validée et celui où elle est notifiée
define('NOTIFICATION_INTERVALLE_JOUR', 2); // 2 jours d'intervalle de temps d'action du cron
// augmenter la valeur pour prendre en compte des évaluatios anciennes
// cela aura pour effet de réactiver des prise en compte d'évaluation par objectifs
// et de relancer des notifications
// surtout utile pour deboguer si le cron ne s'est pas exercé depuis un temps certain

// CONSTANTES  NE PAS MODIFIER
define('TYPE_ACTIVITE', 0);    // Ne pas modifier
define('TYPE_TACHE', 1);       // Ne pas modifier
define('TYPE_CERTIFICAT', 2);  // Ne pas modifier

// DEBUG ?
// si à 1 le cron devient très bavard :))
define ('REFERENTIEL_DEBUG', 0);    // DEBUG ACTIF
// define ('REFERENTIEL_DEBUG', 1);       // DEBUG INACTIF

// traitement des activites evaluées par objectifs
define ('REFERENTIEL_OUTCOMES', 1);   // placer à 0 pour désactiver le traitement
 
/// Liste des rubriques (non exhaustive)
/// CRON
/// CONFIGURATION
/// ACTIVITES
/// TACHES
/// CERTIFICATS
/// URL

/// FONCTIONS A ECRIRE /////////////////////////////////////////////////////////////////////////


/**
 * Given a course and a time, this module should find recent activity 
 * that has occurred in referentiel activities and print it out. 
 * Return true if there was output, or false is there was none. 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function referentiel_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG;

    return false;  //  True if anything was printed, otherwise false 
}
// ################################### EDITOR

function  referentiel_editor_is_ok(){
// editeur wisiwyg  appele depuis mod.html
// non implanté car en cours de developpement :))
    return EDITOR_ON;
}


// ###################################  DEBUT CRON 




// -----------------------------------------
function referentiel_cron_scolarite(){
// mise a jour de la table referentiel_scolarite
global $CFG;
/*
CREATE TABLE IF NOT EXISTS `mdl_referentiel_student` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `num_student` varchar(20) NOT NULL DEFAULT '',
  `ddn_student` varchar(14) NOT NULL DEFAULT '',
  `lieu_naissance` varchar(255) NOT NULL DEFAULT '',
  `departement_naissance` varchar(255) NOT NULL DEFAULT '',
  `adresse_student` varchar(255) NOT NULL DEFAULT '',
  `userid` bigint(10) unsigned NOT NULL,
  `ref_etablissement` bigint(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Fiche student' AUTO_INCREMENT=22 ;*/

    $sql="SELECT * FROM {$CFG->prefix}referentiel_student ";
    mtrace("Scolarite");
    $res=get_records_sql($sql);
    if ($res){
      foreach ($res as $record){
      	// controle
	      if (($record->userid>0) 
          && 
          (($record->num_student=='') || ($record->num_student==get_string('inconnu', 'referentiel')))
          )
        {
          $user=get_record('user','id',$record->userid);
          if ($user){
            if (!empty($user->idnumber)){
              $record->num_student = $user->idnumber;
            }
            else{
              $record->num_student = $user->username;      
            }
          }
        }  
	      update_record("referentiel_student", $record);
    }    
  }
  return true;
}

 
// -----------------------------------------
function referentiel_cron_certificats(){
global $CFG, $USER;

    $cronuser = clone($USER);
    $site = get_site();
    $course = get_site();    // LES CERTIFICATS NE SONT PAS LIES A UN COURS
    // all users that are subscribed to any post that needs sending
    $users = array();

    // status arrays
    $mailcount  = array();
    $errorcount = array();

    // caches
    $certificats     = array();
    $subscribedusers = array();

    // Posts older than 2 days will not be mailed.  This is to avoid the problem where
    // cron has not been running for a long time, and then suddenly people are flooded
    // with mail from the past few weeks or months
    $timenow   = time();
    if (NOTIFICATION_DELAI){
        $endtime   = $timenow - $CFG->maxeditingtime;
    }
    else{
        $endtime   = $timenow;
    }
    $starttime = $endtime - NOTIFICATION_INTERVALLE_JOUR * 24 * 3600;   // Two days earlier

// JF
// DEBUG
mtrace("DEBUT CRON REFERENTIEL CERTIFICATS.");

    $certificats_r = referentiel_get_unmailed_certificats($starttime, $endtime);
   
    if ($certificats_r) {
        // Mark them all now as being mailed.  It's unlikely but possible there
        // might be an error later so that a post is NOT actually mailed out,
        // but since mail isn't crucial, we can accept this risk.  Doing it now
        // prevents the risk of duplicated mails, which is a worse problem.

        if (!referentiel_mark_old_certificates_as_mailed($endtime)) {
            mtrace('Errors occurred while trying to mark some referentiel activities as being mailed.');
            return false;  // Don't continue trying to mail them, in case we are in a cron loop
        }

        // checking activity validity, and adding users to loop through later
        foreach ($certificats_r as $cid => $certificat) {
            $certificatid = $certificat->id;
            
            // DEBUG
            // mtrace('certificate '.$certificat->id);
            
            if (!isset($certificats[$certificatid])) {
                $certificats[$certificatid] = $certificat;
            }


            // caching subscribed teachers of each activity
            
            $students=array();
// DEBUG
// mtrace('DESTINATAIRES...');            
            if (!isset($subscribedusers[$certificatid])) {
                if (NOTIFICATION_AUTEUR){
                    if ($certificats[$certificatid]->userid){      // notifier l'auteur
                      $userid=$certificats[$certificatid]->userid;
                      $user=referentiel_get_user($userid);
                      if ($user->emailstop) {
                        if (!empty($CFG->forum_logblocked)) {
                            add_to_log(SITEID, 'referentiel', 'mail blocked', '', '', 0, $user->id);
                        }
                    }
                    else{
                        // this user is subscribed to this notification
                        $subscribedusers[$certificatid][$userid]=$userid;
                        // this user is a user we have to process later
                        $users[$userid] = $user;
                    
// DEBUG
// mtrace('DESTINATAIRE AUTEUR '.$userid);  
                    }
                    }
                }
                if ($certificats[$certificatid]->teacherid){          // le certificate est suivie
                  $userid=$certificats[$certificatid]->teacherid;
                  $user=referentiel_get_user($userid);
                  if ($user->emailstop) {
                    if (!empty($CFG->forum_logblocked)) {
                        add_to_log(SITEID, 'referentiel', 'mail blocked', '', '', 0, $user->id);
                    }
                  }
                  else{
                    // this user is subscribed to this notification
                    $subscribedusers[$certificatid][$userid]=$userid;
                    // this user is a user we have to process later
                    $users[$userid] = $user;
// DEBUG
// mtrace('DESTINATAIRE ENSEIGNANT REFERENT '.$userid);
                  }
                }
            }

            $mailcount[$cid] = 0;
            $errorcount[$cid] = 0;
        }
    }



    if ($users && $certificats) {
// DEBUG
// mtrace('TRAITEMENT DES MESSAGES ');
        $urlinfo = parse_url($CFG->wwwroot);
        $hostname = $urlinfo['host'];

        foreach ($users as $userto) {

            @set_time_limit(120); // terminate if processing of any account takes longer than 2 minutes

            // set this so that the capabilities are cached, and environment matches receiving user
            $USER = $userto;

            mtrace('Processing user '.$userto->id);

            // init caches
            $userto->viewfullnames = array();
            foreach ($certificats as $cid => $certificat) {
                // Do some checks  to see if we can mail out now
                if (!isset($subscribedusers[$certificat->id][$userto->id])) {
                    continue; // user does not subscribe to this activity
                }
                // Get info about the author user
                if (array_key_exists($certificat->teacherid, $users)) { // teacher is userfrom                    $userfrom = $users[$certificat->teacherid];
                    $userfrom = $users[$certificat->teacherid];
                } else if (array_key_exists($certificat->userid, $users)) { // we might know him/her already
                    $userfrom = $users[$certificat->userid];
                } else if ($userfrom = get_record('user', 'id', $certificat->userid)) {
                    $users[$userfrom->id] = $userfrom; // fetch only once, we can add it to user list, it will be skipped anyway
                } else {
                    mtrace('Could not find user '.$certificat->userid);
                    continue;
                }

                // setup global $COURSE properly - needed for roles and languages
                course_setup($course);   // More environment

                // Fill caches
                if (!isset($userto->viewfullnames[$certificat->id])) {
                    $modcontext = get_context_instance(CONTEXT_SYSTEM);
                    $userto->viewfullnames[$certificat->id] = has_capability('moodle/site:viewfullnames', $modcontext);
                }
                if (!isset($userfrom->groups[$certificat->id])) {
                    if (!isset($userfrom->groups)) {
                        $userfrom->groups = array();
                        $users[$userfrom->id]->groups = array();
                    }
                    $userfrom->groups[$certificat->id] = groups_get_all_groups($course->id, $userfrom->id);
                    $users[$userfrom->id]->groups[$certificat->id] = $userfrom->groups[$certificat->id];
                }


                // OK so we need to send the email.

                // Does the user want this post in a digest?  If so postpone it for now.
                if ($userto->maildigest > 0) {
                    $queue = new object();
                    $queue->userid       = $userto->id;
                    $queue->activityid   = $certificat->id;
                    $queue->timemodified = $certificat->date_decision;
                    $queue->type = TYPE_CERTIFICAT;
                    if (!insert_record('referentiel_notification', $queue)) {
                        mtrace("Error: mod/referentiel/lib.php/referentiel_cron_certificats() : Could not queue for digest mail for id $certificat->id to user $userto->id ($userto->email) .. not trying again.");
                    }
                    continue;
                }

                 // Prepare to actually send the post now, and build up the content
                $strcertificatename=get_string('certificat','referentiel').' '.referentiel_get_referentiel_name($certificat->referentielid);
                $cleancertificatename = str_replace('"', "'", strip_tags(format_string($strcertificatename)));

                $userfrom->customheaders = array (  // Headers to make emails easier to track
                           'Precedence: Bulk',
                           'List-Id: "'.$cleancertificatename.'" <moodle_referentiel_certificate_'.$certificat->id.'@'.$hostname.'>',
                           'List-Help: '.$CFG->wwwroot.'index.php',
                           'Message-ID: <moodle_referentiel_certificate_'.$certificat->id.'@'.$hostname.'>',
                           'X-Course-Id: '.$course->id,
                           'X-Course-Name: '.format_string($course->fullname, true)
                );

                                
                $postsubject = "$course->shortname: ".format_string($strcertificatename,true);
                $context = get_context_instance(CONTEXT_SYSTEM);                
                $posttext = referentiel_make_mail_text(TYPE_CERTIFICAT, $context, $course, $certificat, $userfrom, $userto);
                $posthtml = referentiel_make_mail_html(TYPE_CERTIFICAT, $context, $course, $certificat, $userfrom, $userto);

                // Send the post now!

                // mtrace('Sending ', '');

                if (!$mailresult = email_to_user($userto, $userfrom, $postsubject, $posttext,
                                                 $posthtml, '', '', $CFG->forum_replytouser)) {
                    mtrace("Error: certificates : Could not send out mail for id $certificat->id to user $userto->id ($userto->email) .. not trying again.");
                          $errorcount[$cid]++;
                } else if ($mailresult === 'emailstop') {
                    // should not be reached anymore - see check above
                } else {
                    $mailcount[$cid]++;                   
                }
            }
        }
    }

    if ($certificats) {
        foreach ($certificats as $certificat) {
            mtrace($mailcount[$certificat->id]." users were sent certificate $certificat->id");
            if ($errorcount[$certificat->id]) {
                set_field("referentiel_certificate", "mailed", "2", "id", "$certificat->id");
            }
        }
    }
   
    // release some memory
    unset($subscribedusers);
    unset($mailcount);
    unset($errorcount);

    $USER = clone($cronuser);
    course_setup(SITEID);

    $sitetimezone = $CFG->timezone;

    // Now see if there are any digest mails waiting to be sent, and if we should send them

    mtrace('Starting digest processing...');

    @set_time_limit(300); // terminate if not able to fetch all digests in 5 minutes

    if (!isset($CFG->digestcertificatetimelast)) {    // To catch the first time
        set_config('digestcertificatetimelast', 0);
    }

    $timenow = time();
    $digesttime = usergetmidnight($timenow, $sitetimezone) + ($CFG->digestmailtime * 3600);

    // Delete any really old ones (normally there shouldn't be any)
    $weekago = $timenow - (7 * 24 * 3600);
    delete_records_select('referentiel_notification', "timemodified < $weekago AND type='".TYPE_certificate."'");
    mtrace('Cleaned old digest records');

    if ($CFG->digestcertificatetimelast < $digesttime and $timenow > $digesttime) {
    
        mtrace("Sending activity digests: ".userdate($timenow, '', $sitetimezone));

        $digestposts_rs = get_recordset_select('referentiel_notification', " (timemodified < ".$digesttime.") AND type='".TYPE_certificate."' ");

        if (!rs_EOF($digestposts_rs)) {

            // We have work to do
            $usermailcount = 0;

            //caches - reuse the those filled before too
            $userposts = array();
            
            while ($digestpost = rs_fetch_next_record($digestposts_rs)) {
                if (!isset($users[$digestpost->userid])) {
                    if ($user = get_record('user', 'id', $digestpost->userid)) {
                        $users[$digestpost->userid] = $user;
                    } else {
                        continue;
                    }
                }
                $postuser = $users[$digestpost->userid];
                if ($postuser->emailstop) {
                    if (!empty($CFG->forum_logblocked)) {
                        add_to_log(SITEID, 'referentiel', 'mail blocked', '', '', 0, $postuser->id);
                    }
                    continue;
                }
                
                // contenu certificat
                // 0 : certificat
                if (!isset($certificats[$digestpost->activityid])) {
                    if ($certificate = get_record('referentiel_certificate', 'id', $digestpost->activityid)) {
                        $certificats[$digestpost->activityid] = $certificat;
                    } else {
                        continue;
                    }
                }


                $userposts[$digestpost->userid][$digestpost->activityid] = $digestpost->activityid;

            }
            
            rs_close($digestposts_rs); /// Finished iteration, let's close the resultset

            // Data collected, start sending out emails to each user
            // print_r($userposts);
            // mtrace("A SUPPRIMER  ligne 453");
            foreach ($userposts as $userid => $theseactivities) {
                @set_time_limit(120); // terminate if processing of any account takes longer than 2 minutes

                $USER = $cronuser;
                course_setup(SITEID); // reset cron user language, theme and timezone settings

                mtrace(get_string('processingdigest', 'referentiel', $userid), '... ');

                // First of all delete all the queue entries for this user
                delete_records_select('referentiel_notification', "userid = $userid AND (timemodified < $digesttime) AND type='".TYPE_certificate."'");
                $userto = $users[$userid];


                // Override the language and timezone of the "current" user, so that
                // mail is customised for the receiver.
                $USER = $userto;
                course_setup(SITEID);

                // init caches
                $userto->viewfullnames = array();

                $postsubject = get_string('digestmailsubject', 'referentiel', format_string($site->shortname, true));

                $headerdata = new object();
                $headerdata->sitename = format_string($site->fullname, true);
                $headerdata->userprefs = $CFG->wwwroot.'/user/edit.php?id='.$userid.'&amp;course='.$site->id;

                $posttext = get_string('digestmailheader', 'referentiel', $headerdata)."\n\n";
                $headerdata->userprefs = '<a target="_blank" href="'.$headerdata->userprefs.'">'.get_string('digestmailprefs', 'referentiel').'</a>';

                $posthtml = "<head>";
                foreach ($CFG->stylesheets as $stylesheet) {
                    $posthtml .= '<link rel="stylesheet" type="text/css" href="'.$stylesheet.'" />'."\n";
                }
                $posthtml .= "</head>\n<body id=\"email\">\n";
                $posthtml .= '<p>'.get_string('digestmailheader', 'referentiel', $headerdata).'</p><br /><hr size="1" noshade="noshade" />';


                foreach ($theseactivities as $tid) {
                    mtrace("ligne 495 TID $tid");
                    @set_time_limit(120);   // to be reset for each post
                    $type_notification=TYPE_CERTIFICAT;
                    $certificate = $certificats[$tid];
                      
                    //override language                   
                    course_setup($course);
                    
                    
                    // Fill caches
                    if (!isset($userto->viewfullnames[$certificat->id])) {
                        $modcontext = get_context_instance(CONTEXT_SYSTEM);
                        $userto->viewfullnames[$certificat->id] = has_capability('moodle/site:viewfullnames', $modcontext);
                    }

                    $strcertificate   = get_string('certificat', 'referentiel').' '.referentiel_get_referentiel_name($certificat->referentielid);                                       
 
                    $posttext .= "\n \n";
                    $posttext .= '=====================================================================';
                    $posttext .= "\n \n";
                    $posttext .= "$course->shortname -> ".format_string($strcertificat,true);
                    $posttext .= "\n";

                    $posthtml .= "<p><font face=\"sans-serif\">".
                    "<a target=\"_blank\" href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->shortname</a> -> ";
                    $posthtml .= "</font></p>";
                    $posthtml .= '<p>';

                    // $postsarray = $userposts[$certificat->id];
                    $postsarray = $userposts[$userid];
                    sort($postsarray);
                    // print_r($postsarray);
                    
                    foreach ($postsarray as $activityid) {
                        $post = $certificats[$activityid];

                        if (array_key_exists($post->userid, $users)) { // we might know him/her already
                            $userfrom = $users[$post->userid];
                        } else if ($userfrom = get_record('user', 'id', $post->userid)) {
                            $users[$userfrom->id] = $userfrom; // fetch only once, we can add it to user list, it will be skipped anyway
                        } else {
                            mtrace('Could not find user '.$post->userid);
                            continue;
                        }

                        if (!isset($userfrom->groups[$post->id])) {
                            if (!isset($userfrom->groups)) {
                                $userfrom->groups = array();
                                $users[$userfrom->id]->groups = array();
                            }
                            $userfrom->groups[$post->id] = groups_get_all_groups($course->id, $userfrom->id);
                            $users[$userfrom->id]->groups[$post->id] = $userfrom->groups[$post->id];
                        }

                        $userfrom->customheaders = array ("Precedence: Bulk");

                        if ($userto->maildigest == 2) {
                            // Subjects only
                            $by = new object();
                            $by->name = fullname($userfrom);
                            $by->date = userdate($post->date_decision);
                            $posttext .= "\n".format_string($post->subject,true).' '.get_string("bynameondate", "referentiel", $by);
                            $posttext .= "\n---------------------------------------------------------------------";

                            $by->name = "<a target=\"_blank\" href=\"$CFG->wwwroot/user/view.php?id=$userfrom->id&amp;course=$course->id\">$by->name</a>";
                            $posthtml .= '<div><a target="_blank" href="'.$CFG->wwwroot.'/index.php">'.format_string($strcertificat,true).'</a> '.get_string("bynameondate", "referentiel", $by).'</div>';

                        } else {
                            // The full treatment
                            $context = get_context_instance(CONTEXT_SYSTEM);                
                            $posttext = referentiel_make_mail_text(TYPE_CERTIFICAT, $context, $course, $post, $userfrom, $userto, true);
                            $posthtml = referentiel_make_mail_post(TYPE_CERTIFICAT, $context, $course, $post, $userfrom, $userto, false, true, false);                            
                        } 
                    }
                    $posthtml .= '<hr size="1" noshade="noshade" /></p>';
                }
                $posthtml .= '</body>';

                if ($userto->mailformat != 1) {
                    // This user DOESN'T want to receive HTML
                    $posthtml = '';
                }

                if (!$mailresult =  email_to_user($userto, $site->shortname, $postsubject, $posttext, $posthtml,
                                                  '', '', $CFG->forum_replytouser)) {
                    mtrace("ERROR!");
                    echo "Error: mod/referentiel/lib.php/referentiel_cron_certificats() : Could not send out digest mail to user $userto->id ($userto->email)... not trying again.\n";
                    add_to_log($course->id, 'referentiel', 'mail digest error', '', '', $cm->id, $userto->id);
                } else if ($mailresult === 'emailstop') {
                    // should not happen anymore - see check above
                } else {
                    mtrace("success.");
                    $usermailcount++;
                }
            }
        }
    /// We have finishied all digest emails activities, update $CFG->digestcertificatetimelast
        set_config('digestcertificatetimelast', $timenow);
    }

    $USER = $cronuser;
    course_setup(SITEID); // reset cron user language, theme and timezone settings

    if (!empty($usermailcount)) {
          mtrace(get_string('digestsentusers', 'referentiel', $usermailcount));
    }
    mtrace("FIN CRON REFERENTIEL certificate.\n");
    return true;
}

// -------------------------------------
function referentiel_cron_activites(){
// traite les declarations d'activite 
	global $CFG, $USER;
	
    $cronuser = clone($USER);
    $site = get_site();

    // all users that are subscribed to any post that needs sending
    $users = array();

    // status arrays
    $mailcount  = array();
    $errorcount = array();

    // caches
    $activites          = array();
    $courses         = array();
    $coursemodules   = array();
    $subscribedusers = array();

    // Posts older than 2 days will not be mailed.  This is to avoid the problem where
    // cron has not been running for a long time, and then suddenly people are flooded
    // with mail from the past few weeks or months
    $timenow   = time();
    if (NOTIFICATION_DELAI){
        $endtime   = $timenow - $CFG->maxeditingtime;
    }
    else{
        $endtime   = $timenow;
    }

    $starttime = $endtime - NOTIFICATION_INTERVALLE_JOUR * 24 * 3600;   // Two days earlier

	// JF
	// DEBUG
	mtrace("\nDEBUT CRON REFERENTIEL ACTIVITES\n");


    $activities = referentiel_get_unmailed_activities($starttime, $endtime);
   
    if ($activities) {
        // DEBUG
        // mtrace('ACTIVITES...');    
        
        // Mark them all now as being mailed.  It's unlikely but possible there
        // might be an error later so that a post is NOT actually mailed out,
        // but since mail isn't crucial, we can accept this risk.  Doing it now
        // prevents the risk of duplicated mails, which is a worse problem.

        if (!referentiel_mark_old_activities_as_mailed($endtime)) {
            mtrace('Errors occurred while trying to mark some referentiel activities as being mailed.');
            return false;  // Don't continue trying to mail them, in case we are in a cron loop
        }

        // checking activity validity, and adding users to loop through later
        foreach ($activities as $aid => $activite) {
            $activityid = $activite->id;
            
            // DEBUG
            // mtrace('ACTIVITE '.$activite->id);
            
            if (!isset($activites[$activityid])) {
                $activites[$activityid] = $activite;
            }
            // cours
            $courseid = $activites[$activityid]->course;
            if (!isset($courses[$courseid])) {
                if ($course = get_record('course', 'id', $courseid)) {
                    $courses[$courseid] = $course;
                } else {
                    mtrace('Could not find course '.$courseid);
                    unset($activities[$aid]);
                    continue;
                }
            }
            // modules
            $instanceid = $activite->instanceid;
            if (!isset($coursemodules[$instanceid])) {
                if ($cm = get_coursemodule_from_instance('referentiel', $instanceid, $courseid)) {
                    $coursemodules[$instanceid] = $cm;
                } else {
                    mtrace('./mod/referentiel/lib.php : 676 : Could not course module for referentiel instance '.$instanceid);
                    unset($activities[$aid]);
                    continue;
                }
            }


            // caching subscribed teachers of each activity
            
            $teachers=array();
// DEBUG
// mtrace('DESTINATAIRES...');            
            if (!isset($subscribedusers[$activityid])) {
                if (NOTIFICATION_AUTEUR){
                    if ($activites[$activityid]->userid){      // notifier l'auteur
                        $userid=$activites[$activityid]->userid;
                        $user=referentiel_get_user($userid);
                        if ($user->emailstop) {
                            if (!empty($CFG->forum_logblocked)) {
                                add_to_log(SITEID, 'referentiel', 'mail blocked', '', '', 0, $user->id);
                            }
                        }
                        else{
                            // this user is subscribed to this notification
                            $subscribedusers[$activityid][$userid]=$userid;
                            // this user is a user we have to process later
                            $users[$userid] = $user;
                    
                            // DEBUG
                            // mtrace('DESTINATAIRE AUTEUR '.$userid);
                        }
                    }
                }
                
                if ($activites[$activityid]->teacherid){          // l'activite est suivie
                  $userid=$activites[$activityid]->teacherid;
                  $user=referentiel_get_user($userid);
                  if ($user->emailstop) {
                    if (!empty($CFG->forum_logblocked)) {
                        add_to_log(SITEID, 'referentiel', 'mail blocked', '', '', 0, $user->id);
                    }
                  }
                  else{
                    // this user is subscribed to this notification
                    $subscribedusers[$activityid][$userid]=$userid;
                    // this user is a user we have to process later
                    $users[$userid] = $user;
// DEBUG
// mtrace('DESTINATAIRE ENSEIGNANT REFERENT '.$userid);
                  }
                }
                else{       
                  // ACCOMPAGEMENT
                  $teachers=referentiel_get_accompagnements_user($instanceid, $courseid, $activite->userid);
                  if (empty($teachers)){
                    // notifier tous les enseignants sauf les administrateurs et createurs de cours
                    $teachers=referentiel_get_teachers_course($courseid);
                  }
                  
                  foreach ($teachers as $teacher) {
                    $subscribedusers[$activityid][$teacher->userid]=referentiel_get_user($teacher->userid);
                    $user=referentiel_get_user($teacher->userid);
                    if ($user->emailstop) {
                      if (!empty($CFG->forum_logblocked)) {
                        add_to_log(SITEID, 'referentiel', 'mail blocked', '', '', 0, $user->id);
                      }
                    }
                    else{
                      // this user is subscribed to this notification
                      $subscribedusers[$activityid][$teacher->userid]=$teacher->userid;
                      // this user is a user we have to process later
                      $users[$teacher->userid] = $user;
// DEBUG
// mtrace('DESTINATAIRE ENSEIGNANT '.$teacher->userid);                      
                    }
                  }
                  unset($teachers); // release memory
                }
            }

            $mailcount[$aid] = 0;
            $errorcount[$aid] = 0;
        }
    }



    if ($users && $activites) {
// DEBUG
// mtrace('TRAITEMENT DES MESSAGES ');
        $urlinfo = parse_url($CFG->wwwroot);
        $hostname = $urlinfo['host'];

        foreach ($users as $userto) {

            @set_time_limit(120); // terminate if processing of any account takes longer than 2 minutes

            // set this so that the capabilities are cached, and environment matches receiving user
            $USER = $userto;

            // mtrace('./mod/referentiel/lib.php :: Line 767 : Processing user '.$userto->id);

            // init caches
            $userto->viewfullnames = array();
            $userto->enrolledin    = array();

            // reset the caches
            foreach ($coursemodules as $coursemoduleid => $unused) {
                $coursemodules[$coursemoduleid]->cache       = new object();
                $coursemodules[$coursemoduleid]->cache->caps = array();
                unset($coursemodules[$coursemoduleid]->uservisible);
            }

            foreach ($activites as $aid => $activite) {

                // Set up the environment for activity, course
                $course     = $courses[$activite->course];
                $cm         =& $coursemodules[$activite->instanceid];
                
                // Do some checks  to see if we can mail out now
                if (!isset($subscribedusers[$activite->id][$userto->id])) {
                    continue; // user does not subscribe to this activity
                }

                // Verify user is enrollend in course - if not do not send any email
                if (!isset($userto->enrolledin[$course->id])) {
                    $userto->enrolledin[$course->id] = has_capability('moodle/course:view', get_context_instance(CONTEXT_COURSE, $course->id));
                }
                if (!$userto->enrolledin[$course->id]) {
                    // oops - this user should not receive anything from this course
                    continue;
                }

                // Get info about the author user
                if ($activite->teacherid && $activite->timemodified && $activite->timemodifiedstudent && ($activite->timemodified>$activite->timemodifiedstudent)){
                    $userfrom = $users[$activite->teacherid];
                }
                else if ($activite->teacherid && $activite->timemodified && ($activite->timemodified>$activite->timecreated)){
                    $userfrom = $users[$activite->teacherid];
                }
                else if (array_key_exists($activite->userid, $users)) { // we might know him/her already
                    $userfrom = $users[$activite->userid];
                } 
                else if ($userfrom = get_record('user', 'id', $activite->userid)) {
                    $users[$userfrom->id] = $userfrom; // fetch only once, we can add it to user list, it will be skipped anyway
                } 
                else {
                    mtrace('Could not find user '.$activite->userid);
                    continue;
                }

                // setup global $COURSE properly - needed for roles and languages
                course_setup($course);   // More environment

                // Fill caches
                if (!isset($userto->viewfullnames[$activite->id])) {
                    $modcontext = get_context_instance(CONTEXT_MODULE, $cm->id);
                    $userto->viewfullnames[$activite->id] = has_capability('moodle/site:viewfullnames', $modcontext);
                }
                if (!isset($userfrom->groups[$activite->id])) {
                    if (!isset($userfrom->groups)) {
                        $userfrom->groups = array();
                        $users[$userfrom->id]->groups = array();
                    }
                    $userfrom->groups[$activite->id] = groups_get_all_groups($course->id, $userfrom->id, $cm->groupingid);
                    $users[$userfrom->id]->groups[$activite->id] = $userfrom->groups[$activite->id];
                }


                // OK so we need to send the email.

                // Does the user want this post in a digest?  If so postpone it for now.
                if ($userto->maildigest > 0) {
                    $queue = new object();
                    $queue->userid       = $userto->id;
                    $queue->activityid   = $activite->id;
                    $queue->timemodified = $activite->timemodified;
                    $queue->type = TYPE_ACTIVITE;
                    if (!insert_record('referentiel_notification', $queue)) {
                        mtrace("Error: mod/referentiel/lib.php : Line 325 : Could not queue for digest mail for id $activite->id to user $userto->id ($userto->email) .. not trying again.");
                    }
                    continue;
                }


                // Prepare to actually send the post now, and build up the content
                $strreferentielname=get_string('referentiel','referentiel').': '.referentiel_get_instance_name($activite->instanceid);
                $cleanactivityname = str_replace('"', "'", strip_tags(format_string($strreferentielname.' -> '.$activite->type_activite)));

                $userfrom->customheaders = array (  // Headers to make emails easier to track
                           'Precedence: Bulk',
                           'List-Id: "'.$cleanactivityname.'" <moodle_referentiel_activity_'.$activite->id.'@'.$hostname.'>',
                           'List-Help: '.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$activite->instanceid.'&amp;activite_id='.$activite->id.'&amp;mode=listactivityall',
                           'Message-ID: <moodle_referentiel_activity_'.$activite->id.'@'.$hostname.'>',
                           'X-Course-Id: '.$course->id,
                           'X-Course-Name: '.format_string($course->fullname, true)
                );

                
                
                $postsubject = "$course->shortname: ".format_string($strreferentielname.' '.$activite->type_activite,true);
                $context = get_context_instance(CONTEXT_MODULE, $cm->id);                
                $posttext = referentiel_make_mail_text(TYPE_ACTIVITE, $context, $course, $activite, $userfrom, $userto);
                $posthtml = referentiel_make_mail_html(TYPE_ACTIVITE, $context, $course, $activite, $userfrom, $userto);

                // Send the post now!

                // mtrace('Sending ', '');

                if (!$mailresult = email_to_user($userto, $userfrom, $postsubject, $posttext,
                                                 $posthtml, '', '', $CFG->forum_replytouser)) {
                    mtrace("\nError: mod/referentiel/lib.php:  Could not send out mail for id $activite->id to user $userto->id".
                         " ($userto->email) .. not trying again.");
                    add_to_log($course->id, 'referentiel', 'mail error', "activite $activite->id to user $userto->id ($userto->email)",
                               "", $cm->id, $userto->id);
                    $errorcount[$aid]++;
                } else if ($mailresult === 'emailstop') {
                    // should not be reached anymore - see check above
                } else {
                    $mailcount[$aid]++;
                }

                // mtrace('./mod/referentiel/lib.php :: Line 371 : post '.$activite->id. ': '.$activite->type_activite);
            }

        }
    }

    if ($activites) {
        foreach ($activites as $activite) {
            mtrace($mailcount[$activite->id]." users were sent activity $activite->id, $activite->type_activite");
            if ($errorcount[$activite->id]) {
                set_field("referentiel_activity", "mailed", "2", "id", "$activite->id");
            }
        }
    }
   
    // release some memory
    unset($subscribedusers);
    unset($mailcount);
    unset($errorcount);

    $USER = clone($cronuser);
    course_setup(SITEID);

    $sitetimezone = $CFG->timezone;

    // Now see if there are any digest mails waiting to be sent, and if we should send them

    mtrace("Starting digest processing...");

    @set_time_limit(300); // terminate if not able to fetch all digests in 5 minutes

    if (!isset($CFG->digestactivitytimelast)) {    // To catch the first time
        set_config('digestactivitytimelast', 0);
    }

    $timenow = time();
    $digesttime = usergetmidnight($timenow, $sitetimezone) + ($CFG->digestmailtime * 3600);

    // Delete any really old ones (normally there shouldn't be any)
    $weekago = $timenow - (7 * 24 * 3600);
    delete_records_select('referentiel_notification', "timemodified < $weekago AND type='".TYPE_ACTIVITE."'");

    if ($CFG->digestactivitytimelast < $digesttime and $timenow > $digesttime) {

        mtrace("Sending activity digests: ".userdate($timenow, '', $sitetimezone));

        $digestposts_rs = get_recordset_select('referentiel_notification', " (timemodified < ".$digesttime.") AND type='".TYPE_ACTIVITE."' ");

        if (!rs_EOF($digestposts_rs)) {

            // We have work to do
            $usermailcount = 0;
            $userposts = array();
            
            while ($digestpost = rs_fetch_next_record($digestposts_rs)) {
                if (!isset($users[$digestpost->userid])) {
                    if ($user = get_record('user', 'id', $digestpost->userid)) {
                        $users[$digestpost->userid] = $user;
                    } else {
                        continue;
                    }
                }
                $postuser = $users[$digestpost->userid];
                if ($postuser->emailstop) {
                    if (!empty($CFG->forum_logblocked)) {
                        add_to_log(SITEID, 'referentiel', 'mail blocked', '', '', 0, $postuser->id);
                    }
                    continue;
                }
                
                // contenu activite
                // 0 : ACTIVITE
                if (!isset($activites[$digestpost->activityid])) {
                    if ($activite = get_record('referentiel_activity', 'id', $digestpost->activityid)) {
                        $activites[$digestpost->activityid] = $activite;
                    } else {
                        continue;
                    }
                }
                $courseid = $activites[$digestpost->activityid]->course;
                if (!isset($courses[$courseid])) {
                    if ($course = get_record('course', 'id', $courseid)) {
                        $courses[$courseid] = $course;
                    } else {
                        continue;
                    }
                }

                if (!isset($coursemodules[$activites[$digestpost->activityid]->instanceid]) && $activites[$digestpost->activityid]) {
                    if ($cm = get_coursemodule_from_instance('referentiel', $activites[$digestpost->activityid]->instanceid, $courseid)) {
                        $coursemodules[$activites[$digestpost->activityid]->instanceid] = $cm;
                    } else {
                        continue;
                    }
                }  
                
                $userposts[$digestpost->userid][$digestpost->activityid] = $digestpost->activityid;
            }
            
            rs_close($digestposts_rs); /// Finished iteration, let's close the resultset

            // Data collected, start sending out emails to each user
            foreach ($userposts as $userid => $theseactivities) {
                 @set_time_limit(120); // terminate if processing of any account takes longer than 2 minutes

                $USER = $cronuser;
                course_setup(SITEID); // reset cron user language, theme and timezone settings

                mtrace(get_string('processingdigest', 'referentiel', $userid), '... ');

                // First of all delete all the queue entries for this user
                delete_records_select('referentiel_notification', "userid = $userid AND (timemodified < $digesttime) AND type='".TYPE_ACTIVITE."'");
                $userto = $users[$userid];


                // Override the language and timezone of the "current" user, so that
                // mail is customised for the receiver.
                $USER = $userto;
                course_setup(SITEID);

                // init caches
                $userto->viewfullnames = array();

                $postsubject = get_string('digestmailsubject', 'referentiel', format_string($site->shortname, true));

                $headerdata = new object();
                $headerdata->sitename = format_string($site->fullname, true);
                $headerdata->userprefs = $CFG->wwwroot.'/user/edit.php?id='.$userid.'&amp;course='.$site->id;

                $posttext = get_string('digestmailheader', 'referentiel', $headerdata)."\n\n";
                $headerdata->userprefs = '<a target="_blank" href="'.$headerdata->userprefs.'">'.get_string('digestmailprefs', 'referentiel').'</a>';

                $posthtml = "<head>";
                foreach ($CFG->stylesheets as $stylesheet) {
                    $posthtml .= '<link rel="stylesheet" type="text/css" href="'.$stylesheet.'" />'."\n";
                }
                $posthtml .= "</head>\n<body id=\"email\">\n";
                $posthtml .= '<p>'.get_string('digestmailheader', 'referentiel', $headerdata).'</p><br /><hr size="1" noshade="noshade" />';


                foreach ($theseactivities as $tid) {
                    // DEBUG
                    // mtrace("DEBUG :: lib.php 1031 :: TID : $tid");
                    // print_r($theseactivities);
                    
                    @set_time_limit(120);   // to be reset for each post
                    $type_notification=TYPE_ACTIVITE;
                    $activite = $activites[$tid];
                    $course     = $courses[$activites[$tid]->course];
                    $cm         = $coursemodules[$activites[$tid]->instanceid];                                  
                      
                    //override language
                    
                    course_setup($course);
                    
                    
                    // Fill caches
                    if (!isset($userto->viewfullnames[$activite->id])) {
                        $modcontext = get_context_instance(CONTEXT_MODULE, $cm->id);
                        $userto->viewfullnames[$activite->id] = has_capability('moodle/site:viewfullnames', $modcontext);
                    }

                    $strreferentiels      = get_string('referentiels', 'referentiel');                                       
 
                    $posttext .= "\n \n";
                    $posttext .= '=====================================================================';
                    $posttext .= "\n \n";
                    $posttext .= "$course->shortname -> $strreferentiels -> ".format_string($activite->type_activite,true);
                    $posttext .= "\n";

                    $posthtml .= "<p><font face=\"sans-serif\">".
                    "<a target=\"_blank\" href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->shortname</a> -> ".
                    "<a target=\"_blank\" href=\"$CFG->wwwroot/mod/referentiel/index.php?id=$course->id\">$strreferentiels</a> -> ".
                    "<a target=\"_blank\" href=\"$CFG->wwwroot/mod/referentiel/activite.php?id=$activite->instanceid&amp;activite_id=$activite->id\">".format_string($activite->type_activite,true)."</a>";
                    $posthtml .= "</font></p>";
                    $posthtml .= '<p>';

                    // $postsarray = $discussionposts[$discussionid];
                    // $postsarray = $userposts[$activite->id];
                    // mtrace("DEBUG :: lib.php 1068 :: USERPOST");                    
                    // print_r($userposts);
                    
                    // mtrace("DEBUG :: lib.php 1071 :: POSTARRAY");
                    $postsarray = $userposts[$userid];
                    // print_r($userposts);
                    
                    if (($postsarray) && is_array($postsarray)){
                      sort($postsarray);

                      foreach ($postsarray as $activityid) {
                        $post = $activites[$activityid];

                        if (array_key_exists($post->userid, $users)) { // we might know him/her already
                            $userfrom = $users[$post->userid];
                        } else if ($userfrom = get_record('user', 'id', $post->userid)) {
                            $users[$userfrom->id] = $userfrom; // fetch only once, we can add it to user list, it will be skipped anyway
                        } else {
                            mtrace('Could not find user '.$post->userid);
                            continue;
                        }

                        if (!isset($userfrom->groups[$post->id])) {
                            if (!isset($userfrom->groups)) {
                                $userfrom->groups = array();
                                $users[$userfrom->id]->groups = array();
                            }
                            $userfrom->groups[$post->id] = groups_get_all_groups($course->id, $userfrom->id, $cm->groupingid);
                            $users[$userfrom->id]->groups[$post->id] = $userfrom->groups[$post->id];
                        }

                        $userfrom->customheaders = array ("Precedence: Bulk");

                        if ($userto->maildigest == 2) {
                            // Subjects only
                            $by = new object();
                            $by->name = fullname($userfrom);
                            $by->date = userdate($post->timemodified);
                            $posttext .= "\n".format_string($post->subject,true).' '.get_string("bynameondate", "referentiel", $by);
                            $posttext .= "\n---------------------------------------------------------------------";

                            $by->name = "<a target=\"_blank\" href=\"$CFG->wwwroot/user/view.php?id=$userfrom->id&amp;course=$course->id\">$by->name</a>";
                            $posthtml .= '<div><a target="_blank" href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$post->instanceid.'&amp;activite_id='.$post->id.'">'.format_string($post->type_activite,true).'</a> '.get_string("bynameondate", "referentiel", $by).'</div>';

                        } else {
                            // The full treatment
                            $context = get_context_instance(CONTEXT_MODULE, $cm->id);                
                            $posttext = referentiel_make_mail_text(TYPE_ACTIVITE, $context, $course, $post, $userfrom, $userto, true);
                            $posthtml = referentiel_make_mail_post(TYPE_ACTIVITE, $context, $course, $post, $userfrom, $userto, false, true, false);
                        }
                      }
                    }
                    $posthtml .= '<hr size="1" noshade="noshade" /></p>';
                }
                $posthtml .= '</body>';

                if ($userto->mailformat != 1) {
                    // This user DOESN'T want to receive HTML
                    $posthtml = '';
                }

                if (!$mailresult =  email_to_user($userto, $site->shortname, $postsubject, $posttext, $posthtml,
                                                  '', '', $CFG->forum_replytouser)) {
                    mtrace("ERROR!: Could not send out referentiel activity digest mail to user $userto->id ($userto->email)... not trying again.");
                    add_to_log($course->id, 'referentiel', 'mail digest error', '', '', $cm->id, $userto->id);
                } else if ($mailresult === 'emailstop') {
                    // should not happen anymore - see check above
                } else {
                    mtrace("success.");
                    $usermailcount++;
                }
            }
        }
    /// We have finishied all digest emails activities, update $CFG->digestactivitytimelast
        set_config('digestactivitytimelast', $timenow);
    }

    $USER = $cronuser;
    course_setup(SITEID); // reset cron user language, theme and timezone settings

    if (!empty($usermailcount)) {
          mtrace(get_string('digestsentusers', 'referentiel', $usermailcount));
    }
    mtrace("FIN CRON REFERENTIEL ACTIVITE.\n");
    return true;
}

// -----------------------------------------
function referentiel_cron_taches(){
global $CFG, $USER;
    $cronuser = clone($USER);
    $site = get_site();

    // all users that are subscribed to any post that needs sending
    $users = array();

    // status arrays
    $mailcount  = array();
    $errorcount = array();

    // caches
    $tasks          = array();
    $courses         = array();
    $coursemodules   = array();
    $subscribedusers = array();

    // Posts older than 2 days will not be mailed.  This is to avoid the problem where
    // cron has not been running for a long time, and then suddenly people are flooded
    // with mail from the past few weeks or months
    $timenow   = time();
    if (NOTIFICATION_DELAI){
        $endtime   = $timenow - $CFG->maxeditingtime;
    }
    else{
        $endtime   = $timenow;
    }


    $starttime = $endtime - NOTIFICATION_INTERVALLE_JOUR * 24 * 3600;   // Two days earlier

// JF
// DEBUG
    mtrace("DEBUT CRON REFERENTIEL TACHES.");


    $taches = referentiel_get_unmailed_tasks($starttime, $endtime);
    
    if ($taches) {
        
        // Mark them all now as being mailed.  It's unlikely but possible there
        // might be an error later so that a post is NOT actually mailed out,
        // but since mail isn't crucial, we can accept this risk.  Doing it now
        // prevents the risk of duplicated mails, which is a worse problem.

        if (!referentiel_mark_old_tasks_as_mailed($endtime)) {
            mtrace('Errors occurred while trying to mark some referentiel tasks as being mailed.');
            return false;  // Don't continue trying to mail them, in case we are in a cron loop
        }

        // checking task validity, and adding users to loop through later
        foreach ($taches as $tid => $task) {
            $taskid = $task->id;
            
            // DEBUG
            // mtrace('task '.$task->id);
            
            if (!isset($tasks[$taskid])) {
                $tasks[$taskid] = $task;
            }
            // cours
            $courseid = $tasks[$taskid]->course;
            if (!isset($courses[$courseid])) {
                if ($course = get_record('course', 'id', $courseid)) {
                    $courses[$courseid] = $course;
                } else {
                    mtrace('Could not find course '.$courseid);
                    unset($tasks[$tid]);
                    continue;
                }
            }
            // modules
            $instanceid = $task->instanceid;
            if (!isset($coursemodules[$instanceid])) {
                if ($cm = get_coursemodule_from_instance('referentiel', $instanceid, $courseid)) {
                    $coursemodules[$instanceid] = $cm;
                } else {
                    mtrace('./mod/referentiel/lib.php : 1231 :  Could not load course module for referentiel instance '.$instanceid);
                    unset($tasks[$tid]);
                    continue;
                }
            }


            // caching subscribed students of each task
            
            $students=array();
// DEBUG
// mtrace('DESTINATAIRES...');            
            if (!isset($subscribedusers[$taskid])) {
                if (NOTIFICATION_AUTEUR){

                    if ($tasks[$taskid]->auteurid){      // notifier l'auteur
                        $userid=$tasks[$taskid]->auteurid;
                        $user=referentiel_get_user($userid);
                        if ($user->emailstop) {
                            if (!empty($CFG->forum_logblocked)) {
                                add_to_log(SITEID, 'referentiel', 'mail blocked', '', '', 0, $user->id);
                            }
                        }
                        else{
                            // this user is subscribed to this notification
                            $subscribedusers[$taskid][$userid]=$userid;
                            // this user is a user we have to process later
                            $users[$userid] = $user;
                            // DEBUG
                            // mtrace('DESTINATAIRE AUTEUR '.$userid);
                        }
                    }
                }
                // notifier tous les students
                $students=referentiel_get_students_course($courseid,0,0,false);
                if ($students){
                  foreach ($students as $student) {
                    $subscribedusers[$taskid][$student->userid]=referentiel_get_user($student->userid);
                    $user=referentiel_get_user($student->userid);
                    if ($user->emailstop) {
                      if (!empty($CFG->forum_logblocked)) {
                        add_to_log(SITEID, 'referentiel', 'mail blocked', '', '', 0, $user->id);
                      }
                    }
                    else{
                      // this user is subscribed to this notification
                      $subscribedusers[$taskid][$student->userid]=$student->userid;
                      // this user is a user we have to process later
                      $users[$student->userid] = $user;
// DEBUG
// mtrace('DESTINATAIRE student '.$student->userid);                      
                    }
                  }
                  unset($students); // release memory
                }
              }
            }

            $mailcount[$tid] = 0;
            $errorcount[$tid] = 0;
    }

    if ($users && $tasks) {
// DEBUG
// mtrace('TRAITEMENT DES MESSAGES TACHES');
        $urlinfo = parse_url($CFG->wwwroot);
        $hostname = $urlinfo['host'];

        foreach ($users as $userto) {

            @set_time_limit(120); // terminate if processing of any account takes longer than 2 minutes

            // set this so that the capabilities are cached, and environment matches receiving user
            $USER = $userto;

            // mtrace('./mod/referentiel/lib.php :: Line 253 : Processing user '.$userto->id);

            // init caches
            $userto->viewfullnames = array();
   //         $userto->canpost       = array();
            // $userto->markposts     = array();
            $userto->enrolledin    = array();

            // reset the caches
            foreach ($coursemodules as $coursemoduleid => $unused) {
                $coursemodules[$coursemoduleid]->cache       = new object();
                $coursemodules[$coursemoduleid]->cache->caps = array();
                unset($coursemodules[$coursemoduleid]->uservisible);
            }

            foreach ($tasks as $tid => $task) {

                // Set up the environment for activity, course
                $course     = $courses[$task->course];
                $cm         =& $coursemodules[$task->instanceid];
                
                // Do some checks  to see if we can mail out now
                if (!isset($subscribedusers[$task->id][$userto->id])) {
                    continue; // user does not subscribe to this activity
                }

                // Verify user is enrollend in course - if not do not send any email
                if (!isset($userto->enrolledin[$course->id])) {
                    $userto->enrolledin[$course->id] = has_capability('moodle/course:view', get_context_instance(CONTEXT_COURSE, $course->id));
                }
                if (!$userto->enrolledin[$course->id]) {
                    // oops - this user should not receive anything from this course
                    continue;
                }

                // Get info about the author user
                if (array_key_exists($task->auteurid, $users)) { // we might know him/her already
                    $userfrom = $users[$task->auteurid];
                } else if ($userfrom = get_record('user', 'id', $task->auteurid)) {
                    $users[$userfrom->id] = $userfrom; // fetch only once, we can add it to user list, it will be skipped anyway
                } else {
                    mtrace('Could not find user '.$task->auteurid);
                    continue;
                }

                // setup global $COURSE properly - needed for roles and languages
                course_setup($course);   // More environment

                // Fill caches
                if (!isset($userto->viewfullnames[$task->id])) {
                    $modcontext = get_context_instance(CONTEXT_MODULE, $cm->id);
                    $userto->viewfullnames[$task->id] = has_capability('moodle/site:viewfullnames', $modcontext);
                }
                if (!isset($userfrom->groups[$task->id])) {
                    if (!isset($userfrom->groups)) {
                        $userfrom->groups = array();
                        $users[$userfrom->id]->groups = array();
                    }
                    $userfrom->groups[$task->id] = groups_get_all_groups($course->id, $userfrom->id, $cm->groupingid);
                    $users[$userfrom->id]->groups[$task->id] = $userfrom->groups[$task->id];
                }


                // OK so we need to send the email.

                // Does the user want this post in a digest?  If so postpone it for now.
                if ($userto->maildigest > 0) {
                    $queue = new object();
                    $queue->userid       = $userto->id;
                    $queue->activityid   = $task->id;
                    $queue->timemodified = $task->timemodified;
                    $queue->type = TYPE_TACHE; // 1
                    if (!insert_record('referentiel_notification', $queue)) {
                        mtrace("Error: mod/referentiel/lib.php : Line 325 : Could not queue for digest mail for id $task->id to user $userto->id ($userto->email) .. not trying again.");
                    }
                    continue;
                }


                // Prepare to actually send the post now, and build up the content
                $strreferentielname=get_string('referentiel','referentiel').': '.referentiel_get_instance_name($task->instanceid);
                $cleanactivityname = str_replace('"', "'", strip_tags(format_string($strreferentielname.' -> '.$task->type)));

                $userfrom->customheaders = array (  // Headers to make emails easier to track
                           'Precedence: Bulk',
                           'List-Id: "'.$cleanactivityname.'" <moodle_referentiel_activity_'.$task->id.'@'.$hostname.'>',
                           'List-Help: '.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$task->instanceid.'&amp;task_id='.$task->id.'&amp;mode=listtaskall',
                           'Message-ID: <moodle_referentiel_task_'.$task->id.'@'.$hostname.'>',
                           'X-Course-Id: '.$course->id,
                           'X-Course-Name: '.format_string($course->fullname, true)
                );

                
                if (!$cm = get_coursemodule_from_instance('referentiel', $task->instanceid, $course->id)) {
                  error('Course Module ID was incorrect');
                }
                
                $postsubject = "$course->shortname: ".format_string($strreferentielname.' '.$task->type,true);
                $context = get_context_instance(CONTEXT_MODULE, $cm->id);                
                $posttext = referentiel_make_mail_text(TYPE_TACHE, $context, $course, $task, $userfrom, $userto);
                $posthtml = referentiel_make_mail_html(TYPE_TACHE, $context, $course, $task, $userfrom, $userto);

                // Send the post now!

                // mtrace('Sending ', '');

                if (!$mailresult = email_to_user($userto, $userfrom, $postsubject, $posttext,
                                                 $posthtml, '', '', $CFG->forum_replytouser)) {
                    mtrace("Error: mod/referentiel/lib.php/referentiel_cron_tache() : Could not send out mail for id $task->id to user $userto->id".
                         " ($userto->email) .. not trying again.");
                    add_to_log($course->id, 'referentiel', 'mail error', "task $task->id to $userto->id ($userto->email)",
                               "", $cm->id, $userto->id);
                    $errorcount[$tid]++;
                } else if ($mailresult === 'emailstop') {
                    // should not be reached anymore - see check above
                } else {
                    $mailcount[$tid]++;
                }
            }
        }
    }

    if ($tasks) {
        foreach ($tasks as $task) {
            mtrace($mailcount[$task->id]." users were sent task $task->id, $task->type");
            if ($errorcount[$task->id]) {
                set_field("referentiel_task", "mailed", "2", "id", "$task->id");
            }
        }
    }
   
    // release some memory
    unset($subscribedusers);
    unset($mailcount);
    unset($errorcount);

    $USER = clone($cronuser);
    course_setup(SITEID);

    $sitetimezone = $CFG->timezone;

    // Now see if there are any digest mails waiting to be sent, and if we should send them

    mtrace('Starting digest processing...');

    @set_time_limit(300); // terminate if not able to fetch all digests in 5 minutes

    if (!isset($CFG->digesttasktimelast)) {    // To catch the first time
        set_config('digesttasktimelast', 0);
    }

    $timenow = time();
    $digesttime = usergetmidnight($timenow, $sitetimezone) + ($CFG->digestmailtime * 3600);

    // Delete any really old ones (normally there shouldn't be any)
    $weekago = $timenow - (7 * 24 * 3600);
    delete_records_select('referentiel_notification', "timemodified < $weekago AND type='".TYPE_TACHE."'");
    mtrace('Cleaned old digest records');

    if ($CFG->digesttasktimelast < $digesttime and $timenow > $digesttime) {

        mtrace("Sending task digests: ".userdate($timenow, '', $sitetimezone));

        $digestposts_rs = get_recordset_select('referentiel_notification', "timemodified < $digesttime AND type='".TYPE_TACHE."'");

        if (!rs_EOF($digestposts_rs)) {

            // We have work to do
            $usermailcount = 0;

            //caches - reuse the those filled before too
            $userposts = array();
            
            while ($digestpost = rs_fetch_next_record($digestposts_rs)) {
                if (!isset($users[$digestpost->userid])) {
                    if ($user = get_record('user', 'id', $digestpost->userid)) {
                        $users[$digestpost->userid] = $user;
                    } else {
                        continue;
                    }
                }
                $postuser = $users[$digestpost->userid];
                if ($postuser->emailstop) {
                    if (!empty($CFG->forum_logblocked)) {
                        add_to_log(SITEID, 'referentiel', 'mail blocked', '', '', 0, $postuser->id);
                    }
                    continue;
                }
                
                // contenu activite
                  if (!isset($taches[$digestpost->activityid])) {
                    if ($tache = get_record('referentiel_task', 'id', $digestpost->activityid)) {
                        $taches[$digestpost->activityid] = $tache;
                    } else {
                        continue;
                    }
                  }
                  $courseid = $taches[$digestpost->activityid]->course;
                  if (!isset($courses[$courseid])) {
                    if ($course = get_record('course', 'id', $courseid)) {
                        $courses[$courseid] = $course;
                    } else {
                        continue;
                    }
                  }
                  if (!isset($coursemodules[$taches[$digestpost->activityid]->instanceid]) && $taches[$digestpost->activityid]) {
                    if ($cm = get_coursemodule_from_instance('referentiel', $taches[$digestpost->activityid]->instanceid, $courseid)) {
                        $coursemodules[$taches[$digestpost->activityid]->instanceid] = $cm;
                    } else {
                        continue;
                    }
                  }                    
                $userposts[$digestpost->userid][$digestpost->activityid] = $digestpost->activityid;
            }
            
            rs_close($digestposts_rs); /// Finished iteration, let's close the resultset

            // Data collected, start sending out emails to each user
            // foreach ($userdiscussions as $userid => $thesediscussions) {
            foreach ($userposts as $userid => $theseactivities) {
                @set_time_limit(120); // terminate if processing of any account takes longer than 2 minutes

                $USER = $cronuser;
                course_setup(SITEID); // reset cron user language, theme and timezone settings

                mtrace(get_string('processingdigest', 'referentiel', $userid), '... ');

                // First of all delete all the queue entries for this user
                delete_records_select('referentiel_notification', "userid = $userid AND timemodified < $digesttime AND type='".TYPE_TACHE."'");
                $userto = $users[$userid];


                // Override the language and timezone of the "current" user, so that
                // mail is customised for the receiver.
                $USER = $userto;
                course_setup(SITEID);

                // init caches
                $userto->viewfullnames = array();
                // $userto->canpost       = array();
                // $userto->markposts     = array();

                $postsubject = get_string('digestmailsubject', 'referentiel', format_string($site->shortname, true));

                $headerdata = new object();
                $headerdata->sitename = format_string($site->fullname, true);
                $headerdata->userprefs = $CFG->wwwroot.'/user/edit.php?id='.$userid.'&amp;course='.$site->id;

                $posttext = get_string('digestmailheader', 'referentiel', $headerdata)."\n\n";
                $headerdata->userprefs = '<a target="_blank" href="'.$headerdata->userprefs.'">'.get_string('digestmailprefs', 'referentiel').'</a>';

                $posthtml = "<head>";
                foreach ($CFG->stylesheets as $stylesheet) {
                    $posthtml .= '<link rel="stylesheet" type="text/css" href="'.$stylesheet.'" />'."\n";
                }
                $posthtml .= "</head>\n<body id=\"email\">\n";
                $posthtml .= '<p>'.get_string('digestmailheader', 'referentiel', $headerdata).'</p><br /><hr size="1" noshade="noshade" />';


                foreach ($theseactivities as $tid) {

                    @set_time_limit(120);   // to be reset for each post
                    $type_notification=TYPE_TACHE;
                    
                    $tache = $taches[$tid];
                    $course     = $courses[$taches[$tid]->course];
                    $cm         = $coursemodules[$taches[$tid]->instanceid];                                  
                      
                    //override language
                    
                    course_setup($course);
                    
                    
                    // Fill caches
                    if (!isset($userto->viewfullnames[$tache->id])) {
                        $modcontext = get_context_instance(CONTEXT_MODULE, $cm->id);
                        $userto->viewfullnames[$tache->id] = has_capability('moodle/site:viewfullnames', $modcontext);
                    }

                    $strreferentiels      = get_string('referentiels', 'referentiel');                                       
 
                    $posttext .= "\n \n";
                    $posttext .= '=====================================================================';
                    $posttext .= "\n \n";
                    $posttext .= "$course->shortname -> $strreferentiels -> ".format_string($tache->type,true);
                    $posttext .= "\n";

                    $posthtml .= "<p><font face=\"sans-serif\">".
                    "<a target=\"_blank\" href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->shortname</a> -> ".
                    "<a target=\"_blank\" href=\"$CFG->wwwroot/mod/referentiel/index.php?id=$course->id\">$strreferentiels</a> -> ".
                    "<a target=\"_blank\" href=\"$CFG->wwwroot/mod/referentiel/tache.php?id=$tache->instanceid&amp;activite_id=$tache->id\">".format_string($tache->type,true)."</a>";
                    $posthtml .= "</font></p>";
                    $posthtml .= '<p>';
                    //$postsarray = $userposts[$tache->id];
                    $postsarray = $userposts[$userid];
                    sort($postsarray);

                    foreach ($postsarray as $activityid) {
                        $post = $taches[$activityid];

                        if (array_key_exists($post->auteurid, $users)) { // we might know him/her already
                            $userfrom = $users[$post->auteurid];
                        } else if ($userfrom = get_record('user', 'id', $post->auteurid)) {
                            $users[$userfrom->id] = $userfrom; // fetch only once, we can add it to user list, it will be skipped anyway
                        } else {
                            mtrace('Could not find user '.$post->auteurid);
                            continue;
                        }

                        if (!isset($userfrom->groups[$post->id])) {
                            if (!isset($userfrom->groups)) {
                                $userfrom->groups = array();
                                $users[$userfrom->id]->groups = array();
                            }
                            $userfrom->groups[$post->id] = groups_get_all_groups($course->id, $userfrom->id, $cm->groupingid);
                            $users[$userfrom->id]->groups[$post->id] = $userfrom->groups[$post->id];
                        }

                        $userfrom->customheaders = array ("Precedence: Bulk");

                        if ($userto->maildigest == 2) {
                            // Subjects only
                            $by = new object();
                            $by->name = fullname($userfrom);
                            $by->date = userdate($post->timemodified);
                            $posttext .= "\n".format_string($post->type,true).' '.get_string("bynameondate", "referentiel", $by);
                            $posttext .= "\n---------------------------------------------------------------------";

                            $by->name = "<a target=\"_blank\" href=\"$CFG->wwwroot/user/view.php?id=$userfrom->id&amp;course=$course->id\">$by->name</a>";
                            $posthtml .= '<div><a target="_blank" href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$post->instanceid.'&amp;activite_id='.$post->id.'">'.format_string($post->type_activite,true).'</a> '.get_string("bynameondate", "referentiel", $by).'</div>';

                        } else {
                            // The full treatment
                            $context = get_context_instance(CONTEXT_MODULE, $cm->id);                
                            $posttext = referentiel_make_mail_text(TYPE_TACHE, $context, $course, $post, $userfrom, $userto, true);
                            $posthtml = referentiel_make_mail_post(TYPE_TACHE, $context, $course, $post, $userfrom, $userto, false, true, false);
                        }
                    }
                    $posthtml .= '<hr size="1" noshade="noshade" /></p>';
                }
                $posthtml .= '</body>';

                if ($userto->mailformat != 1) {
                    // This user DOESN'T want to receive HTML
                    $posthtml = '';
                }

                if (!$mailresult =  email_to_user($userto, $site->shortname, $postsubject, $posttext, $posthtml,
                                                  '', '', $CFG->forum_replytouser)) {
                    mtrace("ERROR!");
                    echo "Error: mod/referentiel/lib.php/referentiel_cron_tasks() : Could not send out digest mail to user $userto->id ($userto->email)... not trying again.\n";
                    add_to_log($course->id, 'referentiel', 'mail digest error', '', '', $cm->id, $userto->id);
                } else if ($mailresult === 'emailstop') {
                    // should not happen anymore - see check above
                } else {
                    mtrace("success.");
                    $usermailcount++;
                }
            }
        }
    /// We have finishied all digest emails activities, update $CFG->digestactivitytimelast
        set_config('digesttasktimelast', $timenow);
    }

    $USER = $cronuser;
    course_setup(SITEID); // reset cron user language, theme and timezone settings

    if (!empty($usermailcount)) {
        mtrace(get_string('digestsentusers', 'referentiel', $usermailcount));
    }
    mtrace("FIN CRON REFERENTIEL TACHE.\n");
    return true;
}




/**
 * Builds and returns the body of the email notification in html format.
 *
 * @param object $course
 * @param object $forum
 * @param object $discussion
 * @param object $post
 * @param object $userfrom
 * @param object $userto
 * @return string The email text in HTML format
 */
function referentiel_make_mail_html($type, $context, $course, $post, $userfrom, $userto) {
  global $CFG;
  $site=get_site();
  // DEBUG
  // mtrace("DEBUG : referentiel_make_mail_html TYPE: $type");
  if ($userto->mailformat != 1) {  // Needs to be HTML
        return '';
  }
  
  $posthtml = '<head>';
  foreach ($CFG->stylesheets as $stylesheet) {
    $posthtml .= '<link rel="stylesheet" type="text/css" href="'.$stylesheet.'" />'."\n";
  }

  if ($type==TYPE_CERTIFICAT){
    $strreferentiel = get_string('certificat','referentiel'). ' '. referentiel_get_referentiel_name($post->referentielid);
    $posthtml .= '</head>';
    $posthtml .= "\n<body id=\"email\">\n\n";
    $posthtml .= '<div class="navbar">'.
    '<a target="_blank" href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">'.$course->shortname.'</a> &raquo; '.$strreferentiel;
    $posthtml .= '</div>';  
    $posthtml .= referentiel_make_mail_post($type, $context, $course, $post, $userfrom, $userto, false, true, false);
  }
  else if ($type==TYPE_TACHE){     
    $strreferentiel = referentiel_get_referentiel_name($post->referentielid).' ('.referentiel_get_instance_name($post->instanceid).') ';
    $posthtml .= '</head>';
    $posthtml .= "\n<body id=\"email\">\n\n";
    $posthtml .= '<div class="navbar">'.
    '<a target="_blank" href="'.$CFG->wwwroot.'/course/view.php?id='.$site->id.'">'.$site->shortname.'</a> &raquo; '.
    '<a target="_blank" href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">'.$course->shortname.'</a> &raquo; '.
    '<a target="_blank" href="'.$CFG->wwwroot.'/mod/referentiel/view.php?d='.$post->instanceid.'&amp;noredirect=1">'.$strreferentiel.'</a> &raquo; '.
    '<a target="_blank" href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$post->instanceid.'">'.get_string('tasks','referentiel').'</a>';
    $posthtml .= '</div>';  
    $posthtml .= referentiel_make_mail_post($type, $context, $course, $post, $userfrom, $userto, false, true, false);  
  }
  else{
    $strreferentiel = referentiel_get_referentiel_name($post->referentielid).' ('.referentiel_get_instance_name($post->instanceid).') ';
    $posthtml .= '</head>';
    $posthtml .= "\n<body id=\"email\">\n\n";
    $posthtml .= '<div class="navbar">'.
    '<a target="_blank" href="'.$CFG->wwwroot.'/course/view.php?id='.$site->id.'">'.$site->shortname.'</a> &raquo; '.    
    '<a target="_blank" href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">'.$course->shortname.'</a> &raquo; '.
    '<a target="_blank" href="'.$CFG->wwwroot.'/mod/referentiel/view.php?d='.$post->instanceid.'&amp;noredirect=1">'.$strreferentiel.'</a> &raquo; '.
    '<a target="_blank" href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$post->instanceid.'">'.get_string('activites','referentiel').'</a>';
    $posthtml .= '</div>';  
    $posthtml .= referentiel_make_mail_post($type, $context, $course, $post, $userfrom, $userto, false, true, false);
  }
  $posthtml .= '</body>';
  return $posthtml;
}



/**
* Given the data about a posting, builds up the HTML to display it and
* returns the HTML in a string.  This is designed for sending via HTML email.
*/
function referentiel_make_mail_post($type, $context, $course, $post, $userfrom, $userto,
                              $ownpost=false, $link=false, $rate=false, $footer="") {

  global $CFG;
  // DEBUG
  // mtrace("referentiel_make_mail_post TYPE: $type");

  if (!isset($userto->viewfullnames[$post->id])) {
        $viewfullnames = has_capability('moodle/site:viewfullnames', $context, $userto->id);
  } else {
        $viewfullnames = $userto->viewfullnames[$post->id];
  }

  // format the post body
  
  $strreferentiel = get_string('referentiel', 'referentiel').' '.referentiel_get_referentiel_name($post->referentielid);
  
  $output = '<table border="0" cellpadding="3" cellspacing="0" class="forumpost">';

  $output .= '<tr class="header"><td width="35" valign="top" class="picture left">';
  $output .= print_user_picture($userfrom, $course->id, $userfrom->picture, false, true);
  $output .= '</td>';
  $output .= '<td class="topic starter">';

  if ($type==TYPE_CERTIFICAT){    
  
    $output .= '<div class="subject">'.format_string(get_string('certificat', 'referentiel')).'</div>';

    $fullname = fullname($userfrom, $viewfullnames);
    
    $by = new object();
    $by->name = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$userfrom->id.'&amp;course='.$course->id.'">'.$fullname.'</a>';
    if ($post->date_decision){
      $by->date = userdate($post->date_decision, "", $userto->timezone);
    } 
    else {
      $by->date = userdate(time(), "", $userto->timezone);
    }

    $strbynameondate = get_string('bynameondate', 'referentiel', $by);
   
    $output .= '<div class="author">'.$strbynameondate.'</div>';
    $output .= '</td></tr>';
    $output .= '<tr><td class="left side" valign="top">';

    if (isset($userfrom->groups)) {
        $groups = $userfrom->groups[$post->id];
    } else {
        $group = groups_get_all_groups($course->id, $userfrom->id);
    }

    if ($groups) {
        $output .= print_group_picture($groups, $course->id, false, true, true);
    } else {
        $output .= '&nbsp;';
    }

    $output .= '</td><td class="content">';
 
    $output .= "<hr>\n";
    $output .= trusttext_strip("<b>".get_string('certificat','referentiel')."</b> </i>".$post->id."</i>");

    $output .= "<br />";
    
    $la_liste_competences=referentiel_digest_competences_certificat($post->competences_certificat, $post->referentielid, true);
    $output .= trusttext_strip($la_liste_competences);
    $output .= "<br />\n";
    
    if ($post->decision_jury){
        $output.= trusttext_strip(get_string('decision_jury', 'referentiel').': '.$post->decision_jury);
        if ($post->date_decision){
          $output.= ' '.trusttext_strip(get_string('date_decision', 'referentiel').': '.$post->date_decision);
        }
        $output .= "<br />\n";
    }

    if ($post->teacherid){ 
        $output.= trusttext_strip(get_string('referent', 'referentiel').': '.referentiel_get_user_info($post->teacherid));
        if ($post->verrou){ 
          $output.= ' '.trusttext_strip(get_string('verrou', 'referentiel'));
        }
        $output .= "<br />\n";
    }
    
    if ($post->comment) {
        $output.= trusttext_strip(get_string('commentaire', 'referentiel').': '.$post->comment);
        $output .= "<br />\n";
    }
    if ($post->synthese_certificat) {
        $output.= trusttext_strip(get_string('synthese_certificat', 'referentiel').': '.$post->synthese_certificat);
        $output .= "<br />\n";
    }

    $output .= "<hr>\n";
    

// Context link to post if required
    if ($link) {
        $output .= '<div class="link">';
        $output .= '<a target="_blank" href="'.$CFG->wwwroot.'/">'.
                     get_string('postincontext', 'referentiel').'</a>';
        $output .= '</div>';
    }
  }
  elseif ($type==TYPE_TACHE){
    if (isset($post->auteurid) && ($post->auteurid)){
      $auteur_info=referentiel_get_user_info($post->auteurid);
    } 
    else {
      $auteur_info=get_string('un_enseignant','referentiel');
    }
  
    $output .= '<div class="subject">'.format_string($post->type).'</div>';

    $fullname = fullname($userfrom, $viewfullnames);
    
    $by = new object();
    $by->name = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$userfrom->id.'&amp;course='.$course->id.'">'.$fullname.'</a>';
    if ($post->timemodified){
      $by->date = userdate($post->timemodified, "", $userto->timezone);
    } 
    else {
      $by->date = userdate($post->timecreated, "", $userto->timezone);
    }

    $strbynameondate = get_string('bynameondate', 'referentiel', $by);
   
    $output .= '<div class="author">'.$strbynameondate.'</div>';
    $output .= '</td></tr>';
    $output .= '<tr><td class="left side" valign="top">';

    if (isset($userfrom->groups)) {
        $groups = $userfrom->groups[$post->id];
    } else {
        if (!$cm = get_coursemodule_from_instance('referentiel', $post->instanceid, $course->id)) {
            error('Course Module ID was incorrect');
        }
        $group = groups_get_all_groups($course->id, $userfrom->id);
    }

    if ($groups) {
        $output .= print_group_picture($groups, $course->id, false, true, true);
    } else {
        $output .= '&nbsp;';
    }

    $output .= '</td><td class="content">';
    /* 
    // Plus tard proposer les documents attachÃ©s ?
    if ($post->attachment) {
        $post->course = $course->id;
        $output .= '<div class="attachments">';
        $output .= forum_print_attachments($post, 'html');
        $output .= "</div>";
    }
    */
    // $output .= $formattedtext;
    
    $auteur_info=referentiel_get_user_info($post->auteurid);
    
    $output .= "<hr>\n";
    $output .= trusttext_strip("<b>".get_string('task','referentiel')."</b> </i>".$post->id."</i>");

    $output .= "<br />";
    $output .= trusttext_strip(get_string('description','referentiel').' : '.$post->description);
    $output .= "<br />";
    $output .= trusttext_strip(get_string('certificate_sel_activite_competences', 'referentiel').': '.$post->competences_task);
    $output .= "<br />\n";

    $output.= trusttext_strip(get_string('auteur', 'referentiel').': '.$auteur_info);
    $output .= "<br />\n";
    $output.= trusttext_strip(get_string('timeend', 'referentiel').': '.userdate($post->timeend));
    $output .= "<br />\n";


    if ($post->criteres_evaluation) {
        $output.= trusttext_strip(get_string('criteres_evaluation', 'referentiel').': '.$post->criteres_evaluation);
        $output .= "<br />\n";
    }
    
    if ($post->souscription_libre) {
        $output.= trusttext_strip(get_string('souscription_libre', 'referentiel'));
    }

    if (isset($post->cle_souscription) && ($post->cle_souscription!='')){
        $output.= ' '.trusttext_strip(get_string('obtenir_cle_souscription', 'referentiel', $auteur_info));
    }
    
    if (isset($post->hidden) && ($post->hidden!=0)){
        $output.= ' '.trusttext_strip(get_string('tache_masquee_num', 'referentiel', $post->id));
        $output .= "\n";
    }
    
    $output .= "<br />\n";    
    $output .= "<hr>\n";
    

// Context link to post if required
    if ($link) {
        $output .= '<div class="link">';
//        $output .= '<a target="_blank" href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$post->instanceid.'&amp;task_id='.$post->id.'&amp;userid='.$post->auteurid.'&amp;mode=listtasksingle">'.
          $output .= '<a target="_blank" href="'.$CFG->wwwroot.'/mod/referentiel/task.php?d='.$post->instanceid.'&amp;task_id='.$post->id.'">'.
                     get_string('postincontext', 'referentiel').'</a>';
        $output .= '</div>';
    }
  
  }
  else {           // ACTIVITE
    $output .= '<div class="subject">'.format_string($post->type_activite).'</div>';

    $fullname = fullname($userfrom, $viewfullnames);
    
    $by = new object();
    $by->name = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$userfrom->id.'&amp;course='.$course->id.'">'.$fullname.'</a>';
    if ($post->timemodified){
      $by->date = userdate($post->timemodified, "", $userto->timezone);
    }
    elseif ($post->timemodifiedstudent){
      $by->date = userdate($post->timemodifiedstudent, "", $userto->timezone);
    } 
    else {
      $by->date = userdate($post->timecreated, "", $userto->timezone);
    }

    $strbynameondate = get_string('bynameondate', 'referentiel', $by);
    $strreferentiel = get_string('referentiel', 'referentiel').': '.referentiel_get_instance_name($post->referentielid);
   
    $output .= '<div class="author">'.$strbynameondate.'</div>';
    $output .= '</td></tr>';
    $output .= '<tr><td class="left side" valign="top">';

    if (isset($userfrom->groups)) {
        $groups = $userfrom->groups[$post->id];
    } else {
        if (!$cm = get_coursemodule_from_instance('referentiel', $post->instanceid, $course->id)) {
            error('Course Module ID was incorrect');
        }
        $group = groups_get_all_groups($course->id, $userfrom->id, $cm->groupingid);
    }

    if ($groups) {
        $output .= print_group_picture($groups, $course->id, false, true, true);
    } else {
        $output .= '&nbsp;';
    }

    $output .= '</td><td class="content">';
    /* 
    // Plus tard proposer les documents attachÃ©s ?
    if ($post->attachment) {
        $post->course = $course->id;
        $output .= '<div class="attachments">';
        $output .= forum_print_attachments($post, 'html');
        $output .= "</div>";
    }
    */
    
    // $output .= $formattedtext;

    $output .= "<hr>\n";
    $output .= trusttext_strip('<b>'.get_string('activite','referentiel').'</b> </i>'.$post->id.'</i>');
    $output .= "<br />";
    $output .= trusttext_strip('<b>'.get_string('auteur', 'referentiel').'</b> ');
    $output .= trusttext_strip(referentiel_get_user_info($post->userid));
    $output .= "<br />";

    $output .= trusttext_strip('<b>'.get_string('description','referentiel').'</b> : '.$post->description);
    $output .= "<br />";
    $output .= trusttext_strip('<b>'.get_string('certificate_sel_activite_competences', 'referentiel').'</b> : '.$post->comptencies);
    $output .= "<br />\n";

    if ($post->teacherid){ 
        $output.= trusttext_strip('<b>'.get_string('referent', 'referentiel').'</b> : '.referentiel_get_user_info($post->teacherid));
        if ($post->approved){
          $output .= ' '.trusttext_strip(get_string('approved', 'referentiel'));
        }        
        $output .= "<br />\n";
    }
    
    if ($post->comment) {
        $output.= trusttext_strip('<b>'.get_string('commentaire', 'referentiel').'</b> : '.$post->comment);
        $output .= "<br />\n";
    }
    $output .= "<hr>\n";
    

// Context link to post if required
    if ($link) {
        $output .= '<div class="link">';
        // $output .= '<a target="_blank" href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$post->instanceid.'&amp;activite_id='.$post->id.'&amp;userid='.$post->userid.'&amp;mode=listactivitysingle">'.
        $output .= '<a target="_blank" href="'.$CFG->wwwroot.'/mod/referentiel/activite.php?d='.$post->instanceid.'&amp;activite_id='.$post->id.'">'.        
                     get_string('postincontext', 'referentiel').'</a>';
        $output .= '</div>';
    }
  }

  
  if ($footer) {
        $output .= '<div class="footer">'.$footer.'</div>';
  }
  $output .= '</td></tr></table>'."\n\n";

  return $output;
}

/**
 * Builds and returns the body of the email notification in plain text.
 *
 * @param object $course
 * @param object $forum
 * @param object $discussion
 * @param object $post
 * @param object $userfrom
 * @param object $userto
 * @param boolean $bare
 * @return string The email body in plain text format.
 */
                    
function referentiel_make_mail_text($type, $context, $course, $post, $userfrom, $userto, $bare = false) {
    global $CFG, $USER;
// DEBUG 
// mtrace("referentiel_make_mail_text()");

  if (!isset($userto->viewfullnames[$post->id])) {
        $viewfullnames = has_capability('moodle/site:viewfullnames', $context, $userto->id);
  } else {
        $viewfullnames = $userto->viewfullnames[$post->id];
  }

  $by = New stdClass;
  $by->name = fullname($userfrom, $viewfullnames);

  $a = New stdClass;  
  $a->site=$course->shortname;
  
  $posttext = '';
  
  if ($type==TYPE_CERTIFICAT){
    if ($post->date_decision){
      $by->date = userdate($post->date_decision, "", $userto->timezone);
    } 
    else {
      $by->date = userdate(time(), "", $userto->timezone);
    }

    $strbynameondate = get_string('bynameondate', 'referentiel', $by);   
    $strreferentiel = get_string('certificat', 'referentiel').' '.referentiel_get_referentiel_name($post->referentielid);

    if (!$bare) {
        $posttext  = "$course->shortname -> ";    
        $posttext .= format_string($strreferentiel,true);
        $posttext .= " ($CFG->wwwroot/)";
    }
    $posttext .= "\n".$strbynameondate."\n";
  
    $posttext .= format_text_email(trusttext_strip(get_string('certificat', 'referentiel').' '.$post->id), FORMAT_PLAIN);
    $posttext .= "\n\n";
    $posttext .= format_text_email(trusttext_strip(get_string('certificate_sel_certificate_competences', 'referentiel')), FORMAT_PLAIN);
    $posttext .= "\n";
    $posttext .= format_text_email(trusttext_strip($post->competences_certificat), FORMAT_PLAIN);
    $posttext .= "\n\n";
    
    
    if ($post->comment) {
        $posttext .=format_text_email(trusttext_strip($post->comment), FORMAT_PLAIN);
        $posttext .= "\n\n";
    }
    if ($post->synthese_certificat) {
        $posttext .=format_text_email(trusttext_strip($post->synthese_certificat), FORMAT_PLAIN);
        $posttext .= "\n\n";
    }

    if (!$bare) {
        $posttext .= "---------------------------------------------------------------------\n";
        $a->type=get_string('certificat', 'referentiel');          
        $posttext .= get_string("postmailinfo", "referentiel", $a)."\n";
        $posttext .= "$CFG->wwwroot/\n";
    }
       
  }
  else  if ($type==TYPE_TACHE){
    if (isset($post->auteurid) && ($post->auteurid)){
      $auteur_info=referentiel_get_user_info($post->auteurid);
    } 
    else {
      $auteur_info=get_string('un_enseignant','referentiel');
    }
    if ($post->timemodified){
      $by->date = userdate($post->timemodified, "", $userto->timezone);
    } 
    else {
      $by->date = userdate($post->timecreated, "", $userto->timezone);
    }
    
    $strbynameondate = get_string('bynameondate', 'referentiel', $by);
    $strreferentiel = get_string('referentiel', 'referentiel').': '.referentiel_get_instance_name($post->referentielid);

    if (!$bare) {
        $posttext  = "$course->shortname -> $strreferentiel -> ".format_string($post->type,true);
    }
    $posttext .= "\n".$strbynameondate."\n";
    
    $posttext .= "\n---------------------------------------------------------------------\n";
    $posttext .= format_text_email(trusttext_strip(get_string('task', 'referentiel').' '.$post->id), FORMAT_PLAIN);
    $posttext .= "\n---------------------------------------------------------------------\n\n";
    $posttext .= format_string($post->type,true);
    if ($bare) {
        $posttext .= "(".get_string('postincontext', 'referentiel')." $CFG->wwwroot/mod/referentiel/task.php?d=$post->instanceid&amp;task_id=$post->id)";
    }
    $posttext .= "\n\n";
    $posttext .= format_text_email(trusttext_strip($post->description), FORMAT_PLAIN);
    $posttext .= "\n\n";

    $posttext .= format_text_email(trusttext_strip(get_string('certificate_sel_activite_competences', 'referentiel')), FORMAT_PLAIN);
    $posttext .= "\n";
    $posttext .= format_text_email(trusttext_strip($post->competences_task), FORMAT_PLAIN);
    $posttext .= "\n\n";

    if ($post->criteres_evaluation) {
        $posttext.= format_text_email(trusttext_strip(get_string('criteres_evaluation', 'referentiel').': '.$post->criteres_evaluation), FORMAT_PLAIN);
        $posttext .= "\n\n";
    }
    
    if ($post->souscription_libre) {
        $posttext .= format_text_email(trusttext_strip(get_string('souscription_libre', 'referentiel')), FORMAT_PLAIN);
        $posttext .= "\n";
    }

    if (isset($post->cle_souscription) && ($post->cle_souscription!='')){
          $posttext.= format_text_email(trusttext_strip(get_string('obtenir_cle_souscription', 'referentiel', $auteur_info)), FORMAT_PLAIN);
        $posttext .= "\n";
    }
    if (isset($post->hidden) && ($post->hidden!=0)){
        $posttext.= format_text_email(trusttext_strip(get_string('tache_masquee_num', 'referentiel', $post->id)), FORMAT_PLAIN);
        $posttext .= "\n";
    }

    $posttext .= "\n";
    if (!$bare) {
        $posttext .= "---------------------------------------------------------------------\n";    
        $a->type=get_string('task', 'referentiel'); 
        $posttext .= get_string("postmailinfo", "referentiel",  $a)."\n";
        $posttext .= "$CFG->wwwroot/mod/referentiel/task.php?d=$post->instanceid&amp;task_id=$post->id\n";
    }
  }
  else
  {     // ACTIVITE
    if ($post->timemodified){
      $by->date = userdate($post->timemodified, "", $userto->timezone);
    }
    else if ($post->timemodifiedstudent){
      $by->date = userdate($post->timemodifiedstudent, "", $userto->timezone);
    } 
    else {
      $by->date = userdate($post->timecreated, "", $userto->timezone);
    }
    $strbynameondate = get_string('bynameondate', 'referentiel', $by);
    $strreferentiel = get_string('referentiel', 'referentiel').': '.referentiel_get_instance_name($post->referentielid);

    if (!$bare) {
        $posttext  = "$course->shortname -> $strreferentiel -> ".format_string($post->type_activite,true);
    }
    $posttext .= "\n".$strbynameondate."\n";
    $posttext .= "\n---------------------------------------------------------------------\n";
    $posttext .= format_text_email(trusttext_strip(get_string('activite', 'referentiel').' '.$post->id), FORMAT_PLAIN);
    $posttext .= "\n---------------------------------------------------------------------\n";
    $posttext .= format_string($post->type_activite,true);
    if ($bare) {
        $posttext .= "(".get_string('postincontext', 'referentiel')." $CFG->wwwroot/mod/referentiel/activite.php?d=$post->instanceid&amp;activite_id=$post->id)";
    }
    
    $posttext .= "\n";
    $posttext .= format_text_email(trusttext_strip(get_string('auteur', 'referentiel').' '), FORMAT_PLAIN);
    $posttext .= format_text_email(trusttext_strip(referentiel_get_user_info($post->userid)), FORMAT_PLAIN);
    $posttext .= "\n";
    
    $posttext .= format_text_email(trusttext_strip($post->description), FORMAT_PLAIN);
    $posttext .= "\n\n";

    $posttext .= format_text_email(trusttext_strip(get_string('certificate_sel_activite_competences', 'referentiel')), FORMAT_PLAIN);
    $posttext .= "\n";
    $posttext .= format_text_email(trusttext_strip($post->comptencies), FORMAT_PLAIN);
    $posttext .= "\n\n";
        
    if ($post->comment) {
        $posttext .=format_text_email(trusttext_strip($post->comment), FORMAT_PLAIN);
        $posttext .= "\n\n";
    }
    if (!$bare) {
        $posttext .= "---------------------------------------------------------------------\n";
        $a->type=get_string('activite', 'referentiel'); 
        $posttext .= get_string("postmailinfo", "referentiel",  $a)."\n";
        $posttext .= "$CFG->wwwroot/mod/referentiel/activite.php?d=$post->instanceid&amp;activite_id=$post->id\n";
    }
  }

  return $posttext;
}


/**
 * Returns a list of all new activities that have not been mailed yet
 * @param int $starttime - activity created after this time
 * @param int $endtime - activity created before this time
 */
function referentiel_get_unmailed_activities($starttime, $endtime) {
// detournement du module forum
    global $CFG;
        $sql="SELECT a.* FROM {$CFG->prefix}referentiel_activity a
 WHERE (a.mailed = '0')
 AND (((a.timecreated >= '".$starttime."') AND (a.timecreated < '".$endtime."')) 
 OR ((a.timemodified >= '".$starttime."') AND (a.timemodified < '".$endtime."')) 
 OR ((a.timemodifiedstudent >= '".$starttime."') AND (a.timemodifiedstudent < '".$endtime."'))  
 OR (a.mailnow = '1'))
 ORDER BY a.timecreated ASC, a.timemodified ASC, a.timemodifiedstudent ASC ";
    // mtrace("DEBUG : lib.php : 2150 : SQL : $sql");
    return get_records_sql($sql);
}

/**
 * Returns a list of all new activities that have not been mailed yet
 * @param int $starttime - activity created after this time
 * @param int $endtime - activity created before this time
 */
function referentiel_get_unmailed_tasks($starttime, $endtime) {
// detournement du module forum
    global $CFG;
        $sql="SELECT a.* FROM {$CFG->prefix}referentiel_task a
 WHERE a.mailed = '0'
 AND a.timemodified >= '".$starttime."'
 AND (a.timemodified < '".$endtime."' OR a.mailnow = '1')
 ORDER BY a.timemodified ASC, a.timecreated ASC ";
    // mtrace("DEBUG : lib.php : 2167 : SQL : $sql");
    return get_records_sql($sql);
}

/**
 * Returns a list of all new activities that have not been mailed yet
 * @param int $starttime - activity created after this time
 * @param int $endtime - activity created before this time
 */
function referentiel_get_unmailed_certificats($starttime, $endtime) {
// detournement du module forum
    global $CFG;
        $sql="SELECT a.* FROM {$CFG->prefix}referentiel_certificate a
 WHERE a.mailed = '0'
 AND a.date_decision >= '".$starttime."'
 AND (a.date_decision < '".$endtime."' OR a.mailnow = '1')
 ORDER BY a.date_decision ASC ";
    // mtrace("DEBUG : lib.php : 2184 : SQL : $sql");
    return get_records_sql($sql);
}


/**
 * Marks posts before a certain time as being mailed already
 */
function referentiel_mark_old_activities_as_mailed($endtime) {
// detournement du module forum  
  global $CFG;
  $sql="UPDATE {$CFG->prefix}referentiel_activity
 SET mailed = '1'
 WHERE (((timemodified != '0') AND (timemodified < '".$endtime."'))
 OR ((timecreated != '0') AND (timecreated < '".$endtime."'))
 OR ((timemodifiedstudent != '0') AND (timemodifiedstudent < '".$endtime."')))
 OR ((mailnow = '1') AND (mailed = '0')) ";
        // mtrace("DEBUG : lib.php : 2201 : SQL : $sql");                              
	      return execute_sql( $sql, false);
}

/**
 * Marks posts before a certain time as being mailed already
 */
function referentiel_mark_old_tasks_as_mailed($endtime) {
// detournement du module forum
    global $CFG;

        return execute_sql("UPDATE {$CFG->prefix}referentiel_task
                               SET mailed = '1'
                             WHERE (timemodified < '".$endtime."' OR mailnow = '1')
                                   AND mailed = '0'", false);
	
}

/**
 * Marks posts before a certain time as being mailed already
 */
function referentiel_mark_old_certificates_as_mailed($endtime) {
// detournement du module forum
    global $CFG;

        return execute_sql("UPDATE {$CFG->prefix}referentiel_certificate
                               SET mailed = '1'
                             WHERE (date_decision < '".$endtime."' OR mailnow = '1')
                                   AND mailed = '0'", false);
	
}


/**
 * Must return an instance name
 * @param int $referentielid ID of an instance of this module
 * @return string
 **/
function referentiel_get_instance_name($id){
global $CFG;
	if (isset($id) && ($id>0)){
		  $un_referentiel=get_record_sql('SELECT name FROM '. $CFG->prefix . 'referentiel WHERE id='.$id.' ');
	    if (!empty($un_referentiel->name)){
          return $un_referentiel->name;
      }
  }
	else 
		return ''; 

}

/**
 * Must return an id
 * @param none
 * @return boolean
 **/
function referentiel_referentiel_exists(){
    return(count_records_select('referentiel_referentiel'));
}

/**
 * Must return an instance name
 * @param int $referentielid ID of an instance of this module
 * @return string
 **/
function referentiel_get_referentiel_name($id){
global $CFG;
	if (isset($id) && ($id>0)){
		  $un_referentiel=get_record_sql('SELECT name FROM '. $CFG->prefix . 'referentiel_referentiel WHERE id='.$id.' ');
	    if (!empty($un_referentiel->name)){
          return $un_referentiel->name;
      }
  }
	else 
		return ''; 

}

// ----------------------------------------------------
function referentiel_digest_competences_certificat($liste, $referentiel_referentiel_id, $invalide=true){
// affiche les compétences en mode texte 

$separateur1='/';
$separateur2=':';
$liste_empreintes = referentiel_get_liste_empreintes_competence($referentiel_referentiel_id);
// Affiche les codes competences en tenant compte de l'empreinte
// si detail = true les compétences non validees sont aussi affichees
	$t_empreinte=explode($separateur1, $liste_empreintes);
  $yes=get_string('yes');
  $no=get_string('no');
	$s=get_string('competences','referentiel')."<br />\n";

	$tc=array();
	
	$liste=referentiel_purge_dernier_separateur($liste, $separateur1);
	if (!empty($liste) && ($separateur1!="") && ($separateur2!="")){
			$tc = explode ($separateur1, $liste);
			// DEBUG 
			// echo "<br />CODE <br />\n";
			// print_r($tc);
			$i=0;
			while ($i<count($tc)){
				// CODE1:N1
				// DEBUG 
				// echo "<br />".$tc[$i]." <br />\n";
				// exit;
				if ($tc[$i]!=''){
					$tcc=explode($separateur2, $tc[$i]);
					// echo "<br />".$tc[$i]." <br />\n";
					// print_r($tcc);
					// exit;
					
					if (isset($tcc[1]) && ($tcc[1]>=$t_empreinte[$i])){
						$s.=$tcc[0].' ';						
						if ($invalide==true){
              $s.=' '.$tcc[1].' [/'.$t_empreinte[$i].'] : ';
					    $s.=$yes.' ; ';
            }
          }
					elseif ($invalide==true){
						$s.=$tcc[0].' ';
						$s.=' '.$tcc[1].' [/'.$t_empreinte[$i].'] : ';
						$s.=$no.' ; ';
					}
				  $s.="<br /> \n";
        }				
				$i++;
			} 
		}
	return $s;
}



// ###################################  FIN CRON




/// FONCTIONS UTILITAIRES /////////////////////////////////////////////////////////////////////////


/**
* @param string Y:2008m:09d:26
* @return timestamp
*/
function referentiel_date_special_date($date_special){
	// Y:2008m:09d:26 -> 2008/09/26
	$ladate="";
	$matches=array();
	preg_match("/Y:(\d+)m:(\d+)d:(\d+)/",$date_special,$matches);
	// print_r($matches);
	if (isset($matches[1]) && $matches[1]){
		$ladate=$matches[1];
	    if (isset($matches[2]) && $matches[2]){
			$ladate.='/'.$matches[2];
		    if (isset($matches[3]) && $matches[3]){
				$ladate.='/'.$matches[3];
			}
		}
	}
	return $ladate; 
}

/**
* @param int timestamp
* @return string Y:2008m:09d:26
*/
function referentiel_timestamp_date_special($timestamp){
	// 1222380000 -> Y:2008m:09d:26
	$ladate="Y:".date("Y",$timestamp)."m:".date("m",$timestamp)."d:".date("d",$timestamp);
	return $ladate; 
}

/**
* @param string Y:2008m:09d:26
* @return string Y/m/d
*/
function referentiel_date_special_timestamp($date_special){
	// Y:2008m:09d:26 -> 1222380000
	$ladate="";
	$matches=array();
	preg_match("/Y:(\d+)m:(\d+)d:(\d+)/",$date_special,$matches);
	// print_r($matches);
	if (isset($matches[1]) && $matches[1]){
		$ladate=$matches[1];
	    if (isset($matches[2]) && $matches[2]){
			$ladate.='/'.$matches[2];
		    if (isset($matches[3]) && $matches[3]){
				$ladate.='/'.$matches[3];
			}
		}
	}
	return strtotime($ladate); 
}



/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will update instance and return true or false
 *
 * @param object $form An object from the form in edit.html
 * @return int The id of the newly inserted referentiel record
 **/
function referentiel_associe_referentiel_instance($form){
// importation ou selection ou creation
	if (isset($form->instance) && ($form->instance)
		&& isset($form->new_referentiel_id) && ($form->new_referentiel_id)){
		// id referentiel doit Ãªtre numerique
		$referentiel_id = intval(trim($form->instance));
		$referentiel_referentiel_id = intval(trim($form->new_referentiel_id));
		$referentiel = referentiel_get_referentiel($referentiel_id);
		$referentiel->name_instance = addslashes($referentiel->name);
		$referentiel->description = addslashes($referentiel->description);
		$referentiel->domainlabel = addslashes($referentiel->domainlabel);
		$referentiel->skilllabel = addslashes($referentiel->skilllabel);
		$referentiel->itemlabel = addslashes($referentiel->itemlabel);
		$referentiel->referentielid = $referentiel_referentiel_id;
		
		// DEBUG
		// echo "<br />DEBUG :: lib.php :: 152 :: referentiel_associe_referentiel_instance()<br />\n";
		// print_object($referentiel);
		// echo "<br />";
		$ok = update_record("referentiel", $referentiel);
		return $ok;
	}
	return 0;
}

/**
 * Given an object containing referentiel id, 
 * will set referentiel_id to 0
 *
 * @param id 
 * @return 0
 **/
function referentiel_de_associe_referentiel_instance($id){
// suppression de la reference vers un referentiel_referentiel
	if (isset($id) && ($id)){
		// id referentiel doit Ãªtre numerique
		$id = intval(trim($id));
		$referentiel = referentiel_get_referentiel($id);
		$referentiel->name_instance = addslashes($referentiel->name);
		$referentiel->description = addslashes($referentiel->description);
		$referentiel->domainlabel = addslashes($referentiel->domainlabel);
		$referentiel->skilllabel = addslashes($referentiel->skilllabel);
		$referentiel->itemlabel = addslashes($referentiel->itemlabel);		
		$referentiel->referentielid = 0;
		// DEBUG
		// print_object($referentiel);
		// echo "<br />";
		return (update_record("referentiel", $referentiel));
	}
	return 0;
}

/***
TABLES referentiel_referentiel
*/


/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in pass.html) this function
 * checks the md5 pass
 * 
 * @return int The boolean
 **/
function referentiel_check_pass($referentiel_referentiel, $pass){
//	
	if ($pass!=""){
		$pass=md5($pass);
		if (isset($referentiel_referentiel->password) && ($referentiel_referentiel->password!='')){
			return ($referentiel_referentiel->password==$pass);
		}
		else{
			return true;
		}
	}
	return false;
}

/**
 * Given an object containing all the necessary referentiel,
 * (defined by the form in pass.html) this function
 * set the md5 pass
 *
 * @return int The boolean
 **/
 function referentiel_set_pass($referentiel_referentiel_id, $pass){
// met à jour le mot de passe
	if ($pass!=''){
		// MD5
		$password=md5($pass);
        //  sauvegarde
    	$ok=false;
        if (!empty($referentiel_referentiel_id) && !empty($password)){
            $ok=set_field('referentiel_referentiel','password',$password,'id',$referentiel_referentiel_id);
	    }
        return $ok;
	}
	return false;
}

/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in add.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 *
 * @param object $instance An object from the form in add.html
 * @return int The id of the newly inserted referentiel record
 **/

function referentiel_add_referentiel_domaines($form) {
global $USER;
// La premiere creation permet aussi la saisie d'un domaine, d'une compÃ©tence et d'un item 
	$referentiel_referentiel_id=0;
    // temp added for debugging
    // echo "<br />DEBUG :: lib.php :: 196 :: ADD INSTANCE CALLED";
    // DEBUG
	// print_object($form);
    // echo "<br />";
	// exit;
	// saisie d'un referentiel
	if (isset($form->name) && ($form->name != "") 
		&& isset($form->code) && ($form->code != "")){
		// creer
		$referentiel_referentiel = new object();
		$referentiel_referentiel->name = ($form->name);
		$referentiel_referentiel->code = ($form->code);
		$referentiel_referentiel->description = ($form->description);
		$referentiel_referentiel->url = ($form->url);
		$referentiel_referentiel->certificatethreshold = $form->certificatethreshold;
		$referentiel_referentiel->liste_codes_competence = $form->liste_codes_competence;	
		$referentiel_referentiel->liste_empreintes_competence = $form->liste_empreintes_competence;
		// Modif JF 2009/10/16
		if (isset($form->liste_poids_competence)){
			$referentiel_referentiel->liste_poids_competence = $form->liste_poids_competence;
		} else {
			$referentiel_referentiel->liste_poids_competence = '';
		}
		
		$referentiel_referentiel->timemodified = time();
		if (isset($form->local) && ($form->local != 0) && isset($form->course) && ($form->course != 0)){
			$referentiel_referentiel->local = $form->course;
		} else {
			$referentiel_referentiel->local = 0;
		}
		$referentiel_referentiel->logo = $form->logo;


		// traitements speciaux
		if (isset($form->referentielauthormail) && ($form->referentielauthormail != '')){
			$referentiel_referentiel->referentielauthormail = $form->referentielauthormail;
		} else {
			// Modif JF 2009/10/16
			if (isset($USER->email) && ($USER->email!='')){
				$referentiel_referentiel->referentielauthormail = $USER->email;
			} else {
				$referentiel_referentiel->referentielauthormail = '';
			}
		}
		if (isset($form->refkey) && (trim($form->refkey) != '')){
			$referentiel_referentiel->refkey = $form->refkey;	
		}
		else{
			// Modif JF 2009/10/16
			if (isset($USER->email) && ($USER->email != '')){
				// MD5
				$referentiel_referentiel->refkey = md5($USER->email.$referentiel_referentiel->code);
			} else {
				$referentiel_referentiel->refkey = '';
			}
		}
		// Modif JF 2009/10/16
		if (isset($form->old_pass_referentiel)){ // mot de passe stocke au format Crypte MD5()
			$referentiel_referentiel->old_pass_referentiel = $form->old_pass_referentiel;	
		} else {
			$referentiel_referentiel->old_pass_referentiel = '';	
		}
		if (isset($form->password) && (trim($form->password) != '')){ // mot de passe changÃ©
			// MD5
			$referentiel_referentiel->password = md5($form->password);
		}
		
		
	    // DEBUG
	    // echo "<br />DEBUG :: lib.php :: 221";		
		// print_object($referentiel_referentiel);
	    // echo "<br />";
		
		$referentiel_referentiel_id = insert_record("referentiel_referentiel", $referentiel_referentiel);
    	// echo "REFERENTIEL ID : $referentiel_referentiel_id<br />";
		
		if ($referentiel_referentiel_id > 0 ){
			// saisie de l'instance
			$referentiel = new object();
			$referentiel->name = ($form->name);
			$referentiel->description=($form->description);
			$referentiel->domainlabel=($form->domainlabel);
			$referentiel->skilllabel=($form->skilllabel);
			$referentiel->itemlabel=($form->itemlabel);
		    $referentiel->timecreated = time();
			$referentiel->course=$form->course;
			$referentiel->referentielid=$referentiel_referentiel_id;
		    // DEBUG
			// echo "<br />DEBUG :: lib.php :: 240";
			// print_object($referentiel);
		    // echo "<br />";
			$referentiel_id= insert_record("referentiel", $referentiel);
				
			// saisie du domaine
			$domaine = new object();
			$domaine->referentielid = $referentiel_referentiel_id;
			$domaine->code = $form->code;
			$domaine->description = $form->description;
			$domaine->sortorder = $form->sortorder;
			$domaine->nb_competences = $form->nb_competences;
		    // DEBUG
			// echo "<br />DEBUG :: lib.php :: 253";
			// print_object($domaine);
			// echo "<br />";
			
			$domaine_id = insert_record("referentiel_domain", $domaine);
    		// echo "DOMAINE ID / $domaine_id<br />";
			if ($domaine_id>0){
				$competence = new object();
				$competence->domainid=$domaine_id;
				$competence->code=($form->code);
				$competence->description=($form->description);
				$competence->sortorder=$form->sortorder;
				$competence->nb_item_competences=$form->nb_item_competences;
				
    			// DEBUG
				// echo "<br />DEBUG :: lib.php :: 268";
				// print_object($competence);
    			// echo "<br />";
				
				$competence_id = insert_record("referentiel_skill", $competence);
		    	// echo "COMPETENCE ID / $competence_id<br />";
				if ($competence_id>0){
					$item = new object();
					$item->referentielid=$referentiel_referentiel_id;
					$item->skillid=$competence_id;
					$item->code=($form->code);
					$item->description=($form->description);
					$item->type=$form->type;		
					$item->weight=$form->weight;
					$item->footprint=$form->footprint;
					$item->sortorder=$form->sortorder;
    				// DEBUG
					// echo "<br />DEBUG :: lib.php :: 283";
					// print_object($item);
    				// echo "<br />";
					
					$item_id=insert_record("referentiel_skill_item", $item);
				    // echo "ITEM ID / $item_id<br />";	
				}
			}
		}
		if ($referentiel_referentiel_id>0){
			// MODIF JF 2009/10/16
			$liste_codes_competence = referentiel_new_liste_codes_competence($referentiel_referentiel_id);
			referentiel_set_liste_codes_competence($referentiel_referentiel_id, $liste_codes_competence);
			$liste_empreintes_competence = referentiel_new_liste_empreintes_competence($referentiel_referentiel_id);
			referentiel_set_liste_empreintes_competence($referentiel_referentiel_id, $liste_empreintes_competence);		
			$liste_poids_competence=referentiel_new_liste_poids_competence($referentiel_referentiel_id);
			referentiel_set_liste_poids_competence($referentiel_referentiel_id, $liste_poids_competence);
		}
    	# May have to add extra stuff in here #
	}
	else{
		return get_string('erreur_creation','referentiel');
		// "Name and code mandatory";
	}
	return $referentiel_referentiel_id;
}


/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in edit.html) this function 
 * will update an existing instance .
 *
 * @param object $instance An object from the form in edit.html
 * @return boolean Success/Fail
 **/
 /*
function referentiel_update_referentiel_domaines($form) {
global $USER;
	$ok=true;	
	// DEBUG
	// echo "<br />DEBUG :: lib.php :: 446 <br />";
	// print_object($form);
	// echo "<br />";
	if (isset($form->referentiel_id) && ($form->referentiel_id>0)){
		if (isset($form->action) && ($form->action=="modifierreferentiel")){
			// referentiel
			$referentiel_referentiel = new object();
			$referentiel_referentiel->name=($form->name);
			$referentiel_referentiel->code=($form->code);
			$referentiel_referentiel->description=($form->description);
			$referentiel_referentiel->url=($form->url);
			$referentiel_referentiel->certificatethreshold=($form->certificatethreshold);
    		$referentiel_referentiel->timemodified = time();
			$referentiel_referentiel->nb_domaines=$form->nb_domaines;
			$referentiel_referentiel->liste_codes_competence=$form->liste_codes_competence;
			$referentiel_referentiel->liste_empreintes_competence=$form->liste_empreintes_competence;
			// Modif JF 2009/10/16
			if (isset($form->liste_poids_competence)){
				$referentiel_referentiel->liste_poids_competence=$form->liste_poids_competence;
			}
			else{
				$referentiel_referentiel->liste_poids_competence='';
			}
			$referentiel_referentiel->referentielauthormail=$form->referentielauthormail;
			$referentiel_referentiel->cle_referentiel=$form->cle_referentiel;	
			$referentiel_referentiel->password=$form->old_pass_referentiel;	// sera modifie par traitement special

			// traitements speciaux
			if (isset($form->referentielauthormail) && ($form->referentielauthormail!='')){
				$referentiel_referentiel->referentielauthormail=$form->referentielauthormail;
			}
			else{
				// Modif JF 2009/10/16
				if (isset($USER->email) && ($USER->email!='')){
					$referentiel_referentiel->referentielauthormail=$USER->email;
				}
				else{
					$referentiel_referentiel->referentielauthormail='';
				}
			}
			
			if (isset($form->cle_referentiel) && (trim($form->cle_referentiel)!='')){
				$referentiel_referentiel->cle_referentiel=$form->cle_referentiel;	
			}
			else{
				// Modif JF 2009/10/16
				if (isset($USER->email) && ($USER->email!='')){
					// MD5
					$referentiel_referentiel->cle_referentiel=md5($USER->email.$referentiel_referentiel->code);
				}
				else{
					$referentiel_referentiel->cle_referentiel='';
				}
			}
			// Modif JF 2009/10/16
			$referentiel_referentiel->password=$form->old_pass_referentiel;	// sera modifie par traitement special
			if ($form->password!=''){ // le pass a Ã©tÃ© ressaisi
				// MD5
				$referentiel_referentiel->password=md5($form->password);
			}
			
			
			// local ou global
			if (isset($form->local) && ($form->local!=0) && isset($form->course) && ($form->course!=0))
				$referentiel_referentiel->local=$form->course;
			else
				$referentiel_referentiel->local=0;
			
			$referentiel_referentiel->timemodified = time();
    		$referentiel_referentiel->id = $form->referentiel_id;
			$referentiel_referentiel->logo = $form->logo;
			
	    	// DEBUG
		    // echo "<br />";		
			// print_object($referentiel_referentiel);
	    	// echo "<br />";
			// exit;
			$ok=update_record("referentiel_referentiel", $referentiel_referentiel);
		}
		else if (isset($form->action) && ($form->action=="completerreferentiel")){
			if (isset($form->domaine_id) && is_array($form->domaine_id)){
				for ($i=0; $i<count($form->domaine_id); $i++){
					$domaine = new object();
					$domaine->id=$form->domaine_id[$i];
					$domaine->referentielid=$form->referentiel_id;
					$domaine->code=($form->code[$i]);
					$domaine->description=($form->description[$i]);
					$domaine->sortorder=$form->sortorder[$i];
					$domaine->nb_competences=$form->nb_competences[$i];
					
					if (!update_record("referentiel_domain", $domaine)){
						// DEBUG
						// print_object($domaine);
						// echo "<br />ERREUR DE MISE A JOUR...";
						$ok=$ok && false;
						// exit;
					}
					else{
						// DEBUG
						// print_object($domaine);
						// echo "<br />MISE A JOUR DOMAINE...";
						$ok=$ok && true;
					}
				}
			}
			// NOUVEAU DOMAINE
			if (isset($form->new_code_domaine) && is_array($form->new_code_domaine)){
				for ($i=0; $i<count($form->new_code_domaine); $i++){
					$domaine = new object();
					$domaine->referentielid=$form->referentiel_id;
					$domaine->code=($form->new_code_domaine[$i]);
					$domaine->description=($form->new_description_domaine[$i]);
					$domaine->sortorder=$form->new_num_domaine[$i];
					$domaine->nb_competences=$form->new_nb_competences[$i];
					// DEBUG
					// print_object($domaine);
					// echo "<br />";
					$new_domaine_id = insert_record("referentiel_domain", $domaine);
					$ok=$ok && ($new_domaine_id>0); 
    				// echo "DOMAINE ID / $new_domaine_id<br />";
				}
			}
			// COMPETENCES
			if (isset($form->competence_id) && is_array($form->competence_id)){
				for ($i=0; $i<count($form->competence_id); $i++){
					$competence = new object();
					$competence->id=$form->competence_id[$i];
					$competence->code=($form->code[$i]);
					$competence->description=($form->description[$i]);
					$competence->domainid=$form->domainid[$i];
					$competence->sortorder=$form->sortorder[$i];
					$competence->nb_item_competences=$form->nb_item_competences[$i];
					// DEBUG
					// print_object($competence);
					if (!update_record("referentiel_skill", $competence)){
						// echo "<br />ERREUR DE MISE A JOUR...";
						$ok=$ok && false;
						// exit;
					}
					else{
						// echo "<br />MISE A JOUR COMPETENCES...";
						$ok=$ok && true;
					}
				}
			}
			// NOUVElle competence
			if (isset($form->new_code_competence) && is_array($form->new_code_competence)){
				for ($i=0; $i<count($form->new_code_competence); $i++){
					$competence = new object();
					$competence->code=($form->new_code_competence[$i]);
					$competence->description=($form->new_description_competence[$i]);
					$competence->domainid=$form->new_ref_domaine[$i];
					$competence->sortorder=$form->new_num_competence[$i];
					$competence->nb_item_competences=$form->new_nb_item_competences[$i];
					// DEBUG
					// print_object($competence);
					// echo "<br />";
					$new_competence_id = insert_record("referentiel_skill", $competence);
					$ok=$ok && ($new_competence_id>0); 
   					// echo "competence ID / $new_competence_id<br />";
				}
			}
			// ITEM COMPETENCES
			if (isset($form->item_id) && is_array($form->item_id)){
				for ($i=0; $i<count($form->item_id); $i++){
					$item = new object();
					$item->id=$form->item_id[$i];
					$item->referentielid=$form->referentiel_id;
					$item->skillid=$form->skillid[$i];
					$item->code=($form->code[$i]);
					$item->description=($form->description[$i]);
					$item->sortorder=$form->sortorder[$i];
					$item->type=$form->type[$i];
					$item->weight=$form->weight[$i];
					$item->footprint=$form->footprint[$i];
					
					// DEBUG
					// print_object($item);
					// echo "<br />";
					if (!update_record("referentiel_skill_item", $item)){
						// echo "<br />ERREUR DE MISE A JOUR ITEM COMPETENCE...";
						$ok=$ok && false;
						// exit;
					}
					else {
						// echo "<br />MISE A JOUR ITEM COMPETENCES...";
						$ok=$ok && true;
					}
				}
			}
			// NOUVEL item
			if (isset($form->new_code_item) && is_array($form->new_code_item)){
				for ($i=0; $i<count($form->new_code_item); $i++){
					$item = new object();
					$item->referentielid=$form->referentiel_id;
					$item->skillid=$form->new_ref_competence[$i];
					$item->code=($form->new_code_item[$i]);
					$item->description=($form->new_description_item[$i]);
					$item->sortorder=$form->new_num_item[$i];
					$item->type=($form->new_type_item[$i]);
					$item->weight=$form->new_poids_item[$i];
					$item->footprint=$form->new_empreinte_item[$i];
					
					// DEBUG
					// print_object($item);
					// echo "<br />";
					$new_item_id = insert_record("referentiel_skill_item", $item);
					$ok=$ok && ($new_item_id>0); 
   					// echo "item ID / $new_item_id<br />";
				}
			}
			
			// Mise Ã  jour de la liste de competences
			$liste_codes_competence=referentiel_new_liste_codes_competence($form->referentiel_id);
			// echo "<br />LISTE_CODES_COMPETENCE : $liste_codes_competence\n";
			referentiel_set_liste_codes_competence($form->referentiel_id, $liste_codes_competence);
			$liste_empreintes_competence=referentiel_new_liste_empreintes_competence($form->referentiel_id);
			// echo "<br />LISTE_empreintes_COMPETENCE : $liste_empreintes_competence\n";
			referentiel_set_liste_empreintes_competence($form->referentiel_id, $liste_empreintes_competence);
			// Modif JF 2009/10/16
			$liste_poids_competence=referentiel_new_liste_poids_competence($form->referentiel_id);
			referentiel_set_liste_poids_competence($form->referentiel_id, $liste_poids_competence);
		}
	}
	return $ok;
}
*/


/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 *
 * @param object $form An object
 * @return int The id of the newly inserted referentiel record
 **/
function referentiel_add_referentiel($form) {
global $USER;
// Creer un referentiel sans domaine ni competence ni item
    // temp added for debugging
    // echo "<br />DEBUG : ADD REFERENTIEL CALLED :: lib.php Ligne 633";
    // DEBUG
	// print_object($form);
    // echo "<br />";
	
	// referentiel
	$referentiel = new object();
	$referentiel->name=($form->name);
	$referentiel->code=($form->code);
	$referentiel->description=($form->description);
	$referentiel->url=($form->url);
	$referentiel->certificatethreshold = $form->certificatethreshold;
	$referentiel->nb_domaines = $form->nb_domaines;	
	$referentiel->liste_codes_competence = ($form->liste_codes_competence);
    $referentiel->timemodified = time();
	$referentiel->liste_empreintes_competence = $form->liste_empreintes_competence;
	// Modif JF 2009/10/16
	if (isset($form->liste_poids_competence)){
		$referentiel->liste_poids_competence = $form->liste_poids_competence;
	}
	else{
		$referentiel->liste_poids_competence = '';
	}		
	$referentiel->logo = $form->logo;		
	// local ou global
	if (isset($form->local) && ($form->local != 0) && isset($form->course) && ($form->course != 0))
		$referentiel->local = $form->course;
	else
		$referentiel->local = 0;

	// traitements speciaux
	if (!isset($form->referentielauthormail)){
		$form->referentielauthormail = '';
	}

	if (!isset($form->cle_referentiel)){
		$form->cle_referentiel = '';
	}
	
	if (!isset($form->old_pass_referentiel)){
		$form->old_pass_referentiel = '';
	}
	if (!isset($form->password)){
		$form->password = '';
	}
	
	if ($form->referentielauthormail==''){
		if (isset($USER->id)  && ($USER->id>0)){
			// mail auteur
			$referentiel_referentiel->referentielauthormail = referentiel_get_user_mail($USER->id);
		}
		else{
			$referentiel_referentiel->referentielauthormail = '';
		}	
	}

	if (($form->refkey == '') && ($form->referentielauthormail != '')){
		// MD5
		$referentiel_referentiel->refkey = md5($referentiel_referentiel->referentielauthormail.$form->code);
	}
	else{
		$referentiel_referentiel->refkey = '';
	}
	if ($form->password != ''){
		// MD5
		$referentiel_referentiel->password = md5($form->password);
	}
	else{
		$referentiel_referentiel->password = $form->old_pass_referentiel; // archive md5()
	}


    // DEBUG
    // echo "<br />DEBUG :: lib.php Ligne 658";	
	// print_object($referentiel);
    // echo "<br />";
	
	$new_referentiel_id= insert_record("referentiel_referentiel", $referentiel);
    // echo "REFERENTIEL ID / $referentiel_id<br />";
	
	return $new_referentiel_id;
}

/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will update an instance and return true
 *
 * @param object $form An object from the form in mod.html
 * @return boolean 
 **/
function referentiel_update_referentiel($form) {
// $form : formulaire
	// DEBUG
	// echo "<br />DEBUG lib.php Ligne 676";
	// print_object($form);
	// echo "<br />";
	$ok=false;
	if (isset($form->referentiel_id) && ($form->referentiel_id>0)){
		// referentiel
		$referentiel = new object();
		$referentiel->name = addslashes($form->name);
		$referentiel->code = addslashes($form->code);
		$referentiel->description = addslashes($form->description);
		$referentiel->url = addslashes($form->url);
		$referentiel->certificatethreshold = $form->certificatethreshold;
    	$referentiel->timemodified = time();
		$referentiel->liste_codes_competence = $form->liste_codes_competence;
		$referentiel->liste_empreintes_competence = $form->liste_empreintes_competence;

			// Modif JF 2009/10/16
	if (isset($form->liste_poids_competence)){
		$referentiel->liste_poids_competence = $form->liste_poids_competence;
	}
	else{
		$referentiel->liste_poids_competence = '';
	}		
	if (isset($form->logo)){
		$referentiel->logo = $form->logo;		
	}
	else{
		$referentiel->logo = '';
	}
	
	// local ou global
	if (isset($form->local) && ($form->local != 0) && isset($form->course) && ($form->course != 0))
		$referentiel->local = $form->course;
	else
		$referentiel->local = 0;

	// traitements speciaux
	if (!isset($form->referentielauthormail)){
		$form->referentielauthormail = '';
	}

	if (!isset($form->refkey)){
		$form->refkey = '';
	}
	
	if (!isset($form->old_pass_referentiel)){
		$form->old_pass_referentiel = '';
	}
	if (!isset($form->password)){
		$form->password = '';
	}
	
	if ($form->referentielauthormail==''){
		if (isset($USER->id)  && ($USER->id > 0)){
			// mail auteur
			$referentiel_referentiel->referentielauthormail = referentiel_get_user_mail($USER->id);
		}
		else{
			$referentiel_referentiel->referentielauthormail = '';
		}	
	}

	if (($form->cle_referentiel=='') && ($form->referentielauthormail != '')){
		// MD5
		$referentiel_referentiel->cle_referentiel=md5($referentiel_referentiel->referentielauthormail.$form->code);
	}
	else{
		$referentiel_referentiel->cle_referentiel = '';
	}
	if ($form->password != ''){
		// MD5
		$referentiel_referentiel->password = md5($form->password);
	}
	else{
		if (isset($form->old_pass_referentiel)){
			$referentiel_referentiel->password = $form->old_pass_referentiel; // archive md5()
		}
		else{
			$referentiel_referentiel->password = '';
		}
	}


		$referentiel->timemodified = time();
    	$referentiel->id = $form->referentiel_id;
	    // DEBUG
	    // echo "<br />";		
		// print_object($referentiel);
	    // echo "<br />";
		if (!update_record("referentiel_referentiel", $referentiel)){
			// echo "<br />ERREUR DE MISE A JOUR...";
			$ok = false;
		}
		else {
			$ok = true;
		}
    }
	// DEBUG
	// exit;
    return $ok;
}

/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will update an existing instance with new referentiel.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
 /*
function referentiel_update_domaine($form) {
	$ok=false;	
	// DEBUG
	// echo "<br />DEBUG :: lib.php :: 652 <br />\n";
	// print_object($form);
	// echo "<br />";

	if (isset($form->domaine_id) && ($form->domaine_id>0)){
			$domaine = new object();
			$domaine->id=$form->domaine_id;
			$domaine->referentielid=$form->instance;
			$domaine->code=($form->code);
			$domaine->description=($form->description);
			$domaine->sortorder=$form->sortorder;
			$domaine->nb_competences=$form->nb_competences;
			if (!update_record("referentiel_domain", $domaine)){
				// DEBUG
				// print_object($domaine);
				// echo "<br />ERREUR DE MISE A JOUR...";
				$ok=false;
				// exit;
			}
			else{
				// DEBUG
				// print_object($domaine);
				// echo "<br />MISE A JOUR DOMAINE...";
				$ok=true;
			}
	}

	return $ok;
}
*/

/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will add an existing instance with new domaine.
 *
 * @param object $instance An object from the form in mod.html
 * @return new_domaine_id
 **/
 /*
function referentiel_add_domaine($form) {
	$new_domaine_id=0;	
    // temp added for debugging
    // echo "<br />DEBUG : ADD DOMAINE CALLED";
    // DEBUG
	// print_object($form);
    // echo "<br />";

		// NOUVEAU DOMAINE
		if (isset($form->new_code_domaine) && ($form->new_code_domaine!="")){
			$domaine = new object();
			$domaine->referentielid=$form->instance;
			$domaine->code=$form->new_code_domaine;
			$domaine->description=($form->new_description_domaine);
			$domaine->sortorder=$form->new_num_domaine;
			$domaine->nb_competences=$form->new_nb_competences;
			// DEBUG
			// print_object($domaine);
			// echo "<br />";
			$new_domaine_id = insert_record("referentiel_domain", $domaine); 
    		// echo "DOMAINE ID / $new_domaine_id<br />";
		}

	return $new_domaine_id; 
}
*/
/**
 * Given a domain id, 
 * this function will delete this domain.
 *
 * @param int id
 * @return boolean 
 **/
 /*
function referentiel_delete_domaine($domaine_id){
// suppression
$ok_domaine=true;
$ok_competence=true;
$ok_item=true;
    # Delete any dependent records here #
	// Competences
	if ($competences = get_records("referentiel_skill", "domainid", $domaine_id)) {
		// DEBUG
		// print_object($competences);
		// echo "<br />";
		// Item
		foreach ($competences as $competence){
			if ($items = get_records("referentiel_skill_item", "skillid", $competence->id)) {
				// DEBUG
				// print_object($items);
				// echo "<br />";
				foreach ($items as $item){
					// suppression
					$ok_item=$ok_item && delete_records("referentiel_skill_item", "id", $item->id);
				}
			}	
			$ok_competence=$ok_competence && delete_records("referentiel_skill", "id", $competence->id);
		}
	}
	// suppression
	$ok_domaine=$ok_domaine && delete_records("referentiel_domain", "id", $domaine_id);
	// Mise Ã  jour de la liste de competences dans le referentiel_referentiel associe
    return ($ok_domaine && $ok_competence && $ok_item);
}
*/

/**
 * Given a domain id, 
 * this function will delete this domain.
 *
 * @param int id
 * @return boolean 
 **/
 /*
function referentiel_supprime_domaine($domaine_id){
// suppression avec mise a jour du nombre de domaines dans le referentiel
global $CFG;
	if (!$domaine_id){
		return false;
	}
	$ok=false;
	// suppression du domaine avec mise a jour dans le referentiel associe
	$referentielid = get_record_sql('SELECT referentielid FROM '. $CFG->prefix . 'referentiel_domain WHERE id='.$domaine_id);
	$ok=referentiel_delete_domaine($domaine_id);
	
	// mise a jour du referentiel
	if ($ok && $referentielid){
		$r_record=get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_referentiel WHERE id='.$referentielid->referentielid);
		if ($r_record){
			$referentiel = new object();
			$referentiel->id=$r_record->id;
			$referentiel->name=addslashes($r_record->name);
			$referentiel->code=addslashes($r_record->code);
			$referentiel->description=addslashes($r_record->description);
			$referentiel->url=addslashes($r_record->url);
			$referentiel->certificatethreshold=$r_record->certificatethreshold;
    		$referentiel->timemodified = time();
			$referentiel->nb_domaines=$r_record->nb_domaines-1;
			$referentiel->logo=$r_record->logo;		
			$referentiel->cle_referentiel=$r_record->cle_referentiel;
			$referentiel->local=$r_record->local;
			$referentiel->liste_codes_competence=referentiel_new_liste_codes_competence($r_record->id);
			$referentiel->liste_empreintes_competence=referentiel_new_liste_empreintes_competence($r_record->id);
			// Modif JF 2009/10/16
			$referentiel->liste_poids_competence=referentiel_new_liste_poids_competence($r_record->id);
			
			update_record("referentiel_referentiel", $referentiel);
		}
	}
	
	return $ok;
}
*/

/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will add an existing instance with new domaine.
 *
 * @param object $instance An object from the form in mod.html
 * @return new_competence_id
 **/
 /*
function referentiel_add_competence($form) {
	$new_competence_id=0;	
    // temp added for debugging
    // echo "<br />DEBUG : ADD COMPETENCE CALLED";
    // DEBUG
	// print_object($form);
    // echo "<br />";

		// NOUVElle competence
		if (isset($form->new_code_competence) && ($form->new_code_competence!="")){
			$competence = new object();
			$competence->code=($form->new_code_competence);
			$competence->description=($form->new_description_competence);
			$competence->domainid=$form->new_ref_domaine;
			$competence->sortorder=$form->new_num_competence;
			$competence->nb_item_competences=$form->new_nb_item_competences;
			// DEBUG
			// print_object($competence);
			// echo "<br />";
			$new_competence_id = insert_record("referentiel_skill", $competence);
			// echo "competence ID / $new_competence_id<br />";
		}

	return $new_competence_id; 
}
*/

/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will update an existing instance with new referentiel.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
 /*
function referentiel_update_competence($form) {
	$ok=false;	
	// DEBUG
	// print_object($form);
	// echo "<br />";

		if (isset($form->competence_id) && ($form->competence_id>0)){
			$competence = new object();
			$competence->id=$form->competence_id;
			$competence->code=($form->code);
			$competence->description=($form->description);
			$competence->domainid=$form->domainid;
			$competence->sortorder=$form->sortorder;
			$competence->nb_item_competences=$form->nb_item_competences;
			// DEBUG
			// print_object($competence);
			if (!update_record("referentiel_skill", $competence)){
				// echo "<br />ERREUR DE MISE A JOUR...";
				$ok=false;
				// exit;
			}
			else{
				// echo "<br />MISE A JOUR COMPETENCES...";
				$ok=true;
			}
		}

	return $ok;
}
*/

/**
 * Given a competence id, 
 * this function will delete of this competence.
 *
 * @param int id
 * @return boolean 
 **/
 /*
function referentiel_delete_competence($competence_id){
// suppression
$ok_competence=true;
$ok_item=true;
    # Delete any dependent records here #
	// items
	if ($items = get_records("referentiel_skill_item", "skillid", $competence_id)) {
		// DEBUG
		// print_object($items);
		// echo "<br />";
		foreach ($items as $item){
			// suppression
			$ok_item=$ok_item && delete_records("referentiel_skill_item", "id", $item->id);
		}
	}	
	// suppression
	$ok_competence=$ok_competence && delete_records("referentiel_skill", "id", $competence_id);
	
    return ($ok_competence && $ok_item);
}
*/

/**
 * Given an cometence id, 
 * this function will delete this competence and update competence number in domain linked.
 *
 * @param int id
 * @return boolean 
 **/
 /*
function referentiel_supprime_competence($competence_id){
// suppression avec mise a jour du nombre de competences dans le domaine associe
global $CFG;
	if (!$competence_id){
		return false;
	}
	$ok_competence=true;
	$ok_item=true;
    # Delete any dependent records here #
	// items
	if ($items = get_records("referentiel_skill_item", "skillid", $competence_id)) {
		// DEBUG
		// print_object($items);
		// echo "<br />";
		foreach ($items as $item){
			// suppression
			$ok_item=$ok_item && delete_records("referentiel_skill_item", "id", $item->id);
		}
	}	
	// suppression de la competence avec mise a jour dans le domaine associe
	$domainid = get_record_sql('SELECT domainid FROM '. $CFG->prefix . 'referentiel_skill WHERE id='.$competence_id);
	if ($domainid){
		$d_record=get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_domain WHERE id='.$domainid->domainid);
		if ($d_record){
			$domaine = new object();
			$domaine->id=$d_record->id;
			$domaine->referentielid=$d_record->referentielid;
			$domaine->code=addslashes($d_record->code);
			$domaine->description=addslashes($d_record->description);
			$domaine->sortorder=$d_record->sortorder;
			$domaine->nb_competences=$d_record->nb_competences-1;
			$ok=update_record("referentiel_domain", $domaine);
			
			// Mise Ã  jour de la liste de competences dans le referentiel_referentiel associe
			if ($ok && $d_record->referentielid){
				$liste_codes_competence=referentiel_new_liste_codes_competence($d_record->referentielid);
				// echo "<br />LISTE_CODES_COMPETENCE : $liste_codes_competence\n";
				referentiel_set_liste_codes_competence($d_record->referentielid, $liste_codes_competence);
				$liste_empreintes_competence=referentiel_new_liste_empreintes_competence($d_record->referentielid);
				// echo "<br />LISTE_empreintes_COMPETENCE : $liste_empreintes_competence\n";
				referentiel_set_liste_empreintes_competence($d_record->referentielid, $liste_empreintes_competence);
				// Modif JF 2009/10/16
				$liste_poids_competence=referentiel_new_liste_poids_competence($d_record->referentielid);
				// echo "<br />LISTE_poids_COMPETENCE : $liste_poids_competence\n";
				referentiel_set_liste_poids_competence($d_record->referentielid, $liste_poids_competence);
			}
		}
	}
	$ok_competence=$ok_competence && delete_records("referentiel_skill", "id", $competence_id);
    return ($ok_competence && $ok_item);
}
*/

/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will update an existing instance with new referentiel.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
 /*
function referentiel_update_item($form) {
	$ok=false;	
	// DEBUG
	// print_object($form);
	// echo "<br />";
		// ITEM COMPETENCES
		if (isset($form->item_id) && ($form->item_id>0)){
			$item = new object();
			$item->id=$form->item_id;
			$item->referentielid=$form->instance;
			$item->skillid=$form->skillid;
			$item->code=($form->code);
			$item->description=($form->description);
			$item->sortorder=$form->sortorder;
			$item->type=($form->type);
			$item->weight=$form->weight;
			$item->footprint=$form->footprint;
			// DEBUG
			// print_object($item);
			// echo "<br />";
			if (!update_record("referentiel_skill_item", $item)){
				// echo "<br />ERREUR DE MISE A JOUR ITEM COMPETENCE...";
				$ok=false;
			}
			else {
				// echo "<br />MISE A JOUR ITEM COMPETENCES...";
				$ok=true;
				// Mise Ã  jour de la liste de competences
				$liste_codes_competence=referentiel_new_liste_codes_competence($form->referentiel_id);
				// echo "<br />LISTE_CODES_COMPETENCE : $liste_codes_competence\n";
				referentiel_set_liste_codes_competence($form->referentiel_id, $liste_codes_competence);
				$liste_empreintes_competence=referentiel_new_liste_empreintes_competence($form->referentiel_id);
				// echo "<br />LISTE_empreintes_COMPETENCE : $liste_empreintes_competence\n";
				referentiel_set_liste_empreintes_competence($form->referentiel_id, $liste_empreintes_competence);
				// Modif JF 2009/10/16
				$liste_poids_competence=referentiel_new_liste_poids_competence($form->referentiel_id);
				// echo "<br />LISTE_poids_COMPETENCE : $liste_poids_competence\n";
				referentiel_set_liste_poids_competence($form->referentiel_id, $liste_poids_competence);
			}
		}
	return $ok;
}
*/

/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will add an existing instance with new item.
 *
 * @param object $instance An object from the form
 * @return new_item_id
 **/
/*
function referentiel_add_item($form) {		
// NOUVEL item
	$new_item_id=0;	
	if (isset($form->new_code_item) && ($form->new_code_item!="")){
		$item = new object();
		$item->referentielid=$form->instance;
		$item->skillid=$form->new_ref_competence;
		$item->code=($form->new_code_item);
		$item->description=($form->new_description_item);
		$item->sortorder=$form->new_num_item;
		$item->type=($form->new_type_item);
		$item->weight=$form->new_poids_item;
		$item->footprint=$form->new_empreinte_item;
		
		// DEBUG
		// echo "<br />DEBUG :: lib.php :: 921<br />\n";
		// print_object($item);
		// echo "<br />";
		$new_item_id = insert_record("referentiel_skill_item", $item);
   		// echo "item ID / $new_item_id<br />";
		if ($new_item_id > 0){
			// Mise Ã  jour de la liste de competences
			$liste_codes_competence=referentiel_new_liste_codes_competence($form->instance);
			// echo "<br />LISTE_CODES_COMPETENCE : $liste_codes_competence\n";
			referentiel_set_liste_codes_competence($form->instance, $liste_codes_competence);
			$liste_empreintes_competence=referentiel_new_liste_empreintes_competence($form->instance);
			// echo "<br />LISTE_empreintes_COMPETENCE : $liste_empreintes_competence\n";
			referentiel_set_liste_empreintes_competence($form->instance, $liste_empreintes_competence);
			// Modif JF 2009/10/16
			$liste_poids_competence=referentiel_new_liste_poids_competence($form->instance);
			// echo "<br />LISTE_poids_COMPETENCE : $liste_poids_competence\n";
			referentiel_set_liste_poids_competence($form->instance, $liste_poids_competence);
		}
	}
	return $new_item_id;
}
*/

/**
 * Given an item id, 
 * this function will delete of this item.
 *
 * @param int id
 * @return boolean 
 **/
 /*
function referentiel_delete_item($item_id){
// suppression
	if ($item_id){
		return delete_records("referentiel_skill_item", "id", $item_id);
	}
}
*/
/**
 * Given an item id, 
 * this function will delete of this item.
 *
 * @param int id
 * @return boolean 
 **/
/*
function referentiel_supprime_item($item_id){
// suppression avec mise a jour de la liste des item dans la competence associee
global $CFG;
$ok=false;
	if ($item_id){
		$reference = get_record_sql('SELECT skillid, referentielid FROM '. $CFG->prefix . 'referentiel_skill_item WHERE id='.$item_id);
		if ($reference){
			$c_record=get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_skill WHERE id='.$reference->skillid);
			if ($c_record){
				$competence = new object();
				$competence->id=$c_record->id;
				$competence->code=addslashes($c_record->code);
				$competence->description=addslashes($c_record->description);
				$competence->domainid=$c_record->domainid;
				$competence->sortorder=$c_record->sortorder;
				$competence->nb_item_competences=$c_record->nb_item_competences-1;
				// DEBUG
				// print_object($competence);
				update_record("referentiel_skill", $competence);
			}
		}
		$ok=delete_records("referentiel_skill_item", "id", $item_id);
		// Mise Ã  jour de la liste de competences
		if ($ok && $reference && $reference->referentielid){
			$liste_codes_competence=referentiel_new_liste_codes_competence($reference->referentielid);
			// echo "<br />LISTE_CODES_COMPETENCE : $liste_codes_competence\n";
			referentiel_set_liste_codes_competence($reference->referentielid, $liste_codes_competence);
			$liste_empreintes_competence=referentiel_new_liste_empreintes_competence($reference->referentielid);
			// echo "<br />LISTE_empreintes_COMPETENCE : $liste_empreintes_competence\n";
			referentiel_set_liste_empreintes_competence($reference->referentielid, $liste_empreintes_competence);
			// Modif JF 2009/10/16 
			$liste_poids_competence=referentiel_new_liste_poids_competence($reference->referentielid);
			// echo "<br />LISTE_poids_COMPETENCE : $liste_poids_competence\n";
			referentiel_set_liste_poids_competence($reference->referentielid, $liste_poids_competence);
		}
	}
	return $ok;
}
*/

/**
 * Given referentiel_referentiel id
 * this function 
 * will return a list of referentiel instance.
 *
 * @param  referentiel_referentiel id
 * @return a array of instance id
 **/
function referentiel_referentiel_list_of_instance($id){
	global $CFG;

	if (isset($id) && ($id)){
		// ref id has to be integer
		$id = intval(trim($id));
		if ($records_instance = get_records_sql('SELECT id FROM '. $CFG->prefix . 'referentiel WHERE referentielid='.$id)){
			return ($records_instance);
		}
	}
	return NULL;
}


/**
 * Given an id of  referentiel_referentiel, 
 * this function 
 * will delete all object associated to this referentiel_referentiel.
 *
 * @param id
 * @return boolean Success/Fail
 **/
function referentiel_delete_referentiel_domaines($id) {
$ok_domaine=true;
$ok_competence=true;
$ok_item=true;
$ok=true;
	// verifier existence
    if (!$id) return false;
	if (!$referentiel_referentiel = get_record("referentiel_referentiel", "id", "$id")) {
        return false;
    }
	
    # Delete any dependent records here #
    if ($domaines = get_records("referentiel_domain", "referentielid", "$id")) {
		// DEBUG
		// print_object($domaines);
		// echo "<br />";
		foreach ($domaines as $domaine){
			// Competences
			if ($competences = get_records("referentiel_skill", "domainid", "$domaine->id")) {
				// DEBUG
				// print_object($competences);
				// echo "<br />";
				// Item
				foreach ($competences as $competence){
					if ($items = get_records("referentiel_skill_item", "skillid", "$competence->id")) {
						// DEBUG
						// print_object($items);
						// echo "<br />";
						foreach ($items as $item){
							// suppression
							$ok_item=$ok_item && delete_records("referentiel_skill_item", "id", "$item->id");
						}
					}	
					$ok_competence=$ok_competence && delete_records("referentiel_skill", "id", "$competence->id");
				}
			}
			// suppression
			$ok_domaine=$ok_domaine && delete_records("referentiel_domain", "id", "$domaine->id");			
		}
    }
    if (! delete_records("referentiel_referentiel", "id", "$id")) {
        $ok = $ok && false;
    }
	
    return ($ok && $ok_domaine && $ok_competence && $ok_item);
}




/**
 * Given a document id, 
 * this function will permanently delete the document instance 
 *
 * @param object $id
 * @return boolean Success/Failure
 **/

function referentiel_delete_document_record($id) {
// suppression document
$ok_document=false;
	if (isset($id) && ($id>0)){
		if ($document = get_record("referentiel_document", "id", $id)) {
			//  CODE A AJOUTER SI GESTION DE FICHIERS DEPOSES SUR LE SERVEUR
			$ok_document = delete_records("referentiel_document", "id", $id);
		}
	}
	return $ok_document;
}


/**
 * Given an activity id, 
 * this function will permanently delete the activite instance 
 * and any document that depends on it. 
 *
 * @param object $id
 * @return boolean Success/Failure
 **/

function referentiel_delete_activity_record($id) {
// suppression activite + documents associes
global $CFG;
$ok_activite=false;	
	if (isset($id) && ($id>0)){
		if ($activite = get_record("referentiel_activity", "id", $id)) {
	   		// Delete any dependent records here 
			
			// Si c'est une activitÃ© - tÃ¢che il faut aussi supprimer les liens vers cette tache
			if (isset($activite->taskid) && ($activite->taskid>0) && isset($activite->userid) && ($activite->userid>0)){
				$a_t_records = get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_a_user_task WHERE taskid='.$activite->taskid. '  AND userid='.$activite->userid);
				if ($a_t_records){
					foreach ($a_t_records as $a_t_record){
						// suppression
						referentiel_delete_a_user_task_record($a_t_record->id);
					}
				}
			}
			
			$ok_document=true;
			if ($documents = get_records("referentiel_document", "activityid", $id)) {
				// DEBUG
				// print_object($documents);
				// echo "<br />";
				// suppression des documents associes dans la table referentiel_document
				foreach ($documents as $document){
					// suppression
					$ok_document=$ok_document && referentiel_delete_document_record($document->id);
				}
			}
			// suppression activite
			if ($ok_document){
				$ok_activite = delete_records("referentiel_activity", "id", $id);
				if 	($ok_activite
					&& isset($activite->userid) && ($activite->userid>0) 
					&& isset($activite->comptencies) && ($activite->comptencies!='')){
					// mise a jour du certificate 
					referentiel_mise_a_jour_competences_certificate_user($activite->comptencies, '', $activite->userid, $activite->referentielid, $activite->approved, true, true);
				}
			}
		}
	}
    return $ok_activite;
}


/**
 * Given a form, 
 * this function will permanently delete the activite instance 
 * and any document that depends on it. 
 *
 * @param object $form
 * @return boolean Success/Failure
 **/

function referentiel_delete_activity($form) {
// suppression activite + document
$ok_activite_detruite=false;
$ok_document=false;
    // DEBUG
	// echo "<br />";
	// print_object($form);
    // echo "<br />";
	if (isset($form->action) && ($form->action=="modifier_activite")){
		// suppression d'une activite et des documents associes
		if (isset($form->activite_id) && ($form->activite_id>0)){
			$activite=referentiel_get_activite($form->activite_id);
			$ok_activite_detruite=referentiel_delete_activity_record($form->activite_id);
			
			// MODIF JF 2009/09/21
			// mise a zero du certificate associe a cette personne pour ce referentiel 
			// referentiel_certificate_user_invalider($form->userid, $form->referentielid);
			// referentiel_regenere_certificate_user($form->userid, $form->referentielid);
			if 	($ok_activite_detruite  
				&& $activite->userid>0 
				&& ($activite->comptencies!='')){
				// mise a jour du certificate 
				referentiel_mise_a_jour_competences_certificate_user($activite->comptencies, '', $activite->userid, $activite->referentielid, $activite->approved, true, true);
			}
		}
	}
	else if (isset($form->action) && ($form->action=="modifier_document")){
		// suppression d'un document
		if (isset($form->document_id) && ($form->document_id>0)){
			$ok_document=referentiel_delete_document_record($form->document_id);
		}
	}
	
    return $ok_activite_detruite or $ok_document;
}


/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 *
 * @param object $instance An object from the form in activite.html
 * @return int The id of the newly inserted referentiel record
 **/
function referentiel_add_activity($form) {
// creation activite + document
global $USER;
    // DEBUG
    // echo "DEBUG : ADD ACTIVITY CALLED : lib.php : ligne 1033";
	// print_object($form);
    // echo "<br />";
	// referentiel
	$activite = new object();
	$activite->type_activite=($form->type_activite);
	if (!empty($form->code)){
		$activite->comptencies=reference_conversion_code_2_liste_competence('/', $form->code);
	}
	else{
		$activite->comptencies='';
	}
	$activite->description=($form->description);
	$activite->comment=($form->comment);
	$activite->instanceid=$form->instance;
	$activite->referentielid=$form->referentielid;
	$activite->course=$form->course;
	$activite->timecreated=time();
	$activite->timemodifiedstudent=time();
	$activite->timemodified=0;
	$activite->approved=0;
	$activite->userid=$USER->id;
	$activite->teacherid=0;
	$activite->taskid=0;

	$activite->mailed=1;  // MODIF JF 2010/10/05  pour empêcher une notification intempesttive
    if (isset($form->mailnow)){
        $activite->mailnow=$form->mailnow;
        if ($form->mailnow=='1'){ // renvoyer
            $activite->mailed=0;   // forcer l'envoi
        }
    }
    else{ 
      $activite->mailnow=0;
    }	
    
    // DEBUG
    // echo "<br />DEBUG :: lib.php : 1163 : APRES CREATION\n";	
	// print_object($activite);
    // echo "<br /> EXIT lib.php Ligne 1734 <br />";
	// exit;
	$activite_id= insert_record("referentiel_activity", $activite);
	// DEBUG
	// echo "ACTIVITE ID / $activite_id<br />";
	
	// MODIF JF 2009/09/21
	// mise a zero du certificate associe a cette personne pour ce referentiel 
	// referentiel_certificate_user_invalider($activite->userid, $activite->referentielid);
	// referentiel_regenere_certificate_user($activite->userid, $activite->referentielid);
	if 	(($activite_id>0) && ($activite->comptencies!='')){
		// mise a jour du certificate 
		referentiel_mise_a_jour_competences_certificate_user('', $activite->comptencies, $activite->userid, $activite->referentielid, $activite->approved, true, false);
	}
	
    
	if 	(isset($activite_id) && ($activite_id>0)
			&& 
			(	(isset($form->url) && !empty($form->url))
				|| 
				(isset($form->description) && !empty($form->description))
			)
	){
		$document = new object();
		$document->url=($form->url);
		$document->type=($form->type);
		$document->description=($form->description);
		$document->activityid=$activite_id;
		if (isset($form->target)){
			$document->target=$form->target;
   		}
		else{
			$document->target=1;
		}
		if (isset($form->label)){
			$document->label=$form->label;
   		}
		else{
			$document->label='';
		}

	   	// DEBUG
		// print_object($document);
    	// echo "<br />";
		
		$document_id = insert_record("referentiel_document", $document);
    	// echo "DOCUMENT ID / $document_id<br />";
	}
    return $activite_id;
}

function referentiel_update_activity($form) {
// MAJ activite + document;
global $USER;
$ok=true;
    // DEBUG
	// echo "<br />UPDATE ACTIVITY<br />\n";
	// print_object($form);
    // echo "<br />";
	
	if (isset($form->action) && ($form->action=="modifier_activite")){
		
		// recuperer l'ancien enregistrement pour les mises Ã  jour du certificat
		$old_liste_competences='';
		if ($form->activite_id){
			$record_activite=referentiel_get_activite($form->activite_id);
			if ($record_activite){
				$old_liste_competences=$record_activite->comptencies;
			}
		}
		if (($old_liste_competences=='') && isset($form->old_liste_competences)){
			$old_liste_competences=$form->old_liste_competences;
		}

		// activite
		$activite = new object();
		$activite->id=$form->activite_id;	
		$activite->type_activite=($form->type_activite);
		// $activite->comptencies=$form->comptencies;
		if (isset($form->code) && is_array($form->code)){
			$activite->comptencies=reference_conversion_code_2_liste_competence('/', $form->code);
		}
		else if (isset($form->comptencies)){
			$activite->comptencies=$form->comptencies;
		}
		else{
			$activite->comptencies='';
		}
		$activite->description=($form->description);
		$activite->comment=($form->comment);
		$activite->instanceid=$form->instance;
		$activite->referentielid=$form->referentielid;
		$activite->course=$form->course;
		$activite->timecreated=$form->timecreated;
		$activite->approved=$form->approved;
		$activite->userid=$form->userid;	
		$activite->teacherid=$form->teacherid;
		
		
		// MODIF JF 2009/10/27
		if ($USER->id==$activite->userid){
			$activite->timemodifiedstudent=time();
			$activite->timemodified=$form->timemodified;
		}
		else{
			$activite->timemodified=time();
			$activite->timemodifiedstudent=$form->timemodifiedstudent;
		}
		
		// MODIF JF 2010/02/11
        if (isset($form->mailnow)){
            $activite->mailnow=$form->mailnow;
            if ($form->mailnow=='1'){ // renvoyer
                $activite->mailed=0;   // annuler envoi precedent
            }
        }
        else{
            $activite->mailnow=0;
        }
    
		// DEBUG
		// print_object($activite);
	    // echo "<br />";
		$ok = $ok && update_record("referentiel_activity", $activite);
		
	    // echo "DEBUG :: lib.php :: 1803 :: ACTIVITE ID / $activite->id<br />";
		// exit;
		
		// MODIF JF 2009/09/21
		// mise a zero du certificate associe a cette personne pour ce referentiel 
		// referentiel_certificate_user_invalider($activite->userid, $activite->referentielid);
		// referentiel_regenere_certificate_user($activite->userid, $activite->referentielid);
		if 	($ok && ($activite->userid>0)){
			// mise a jour du certificate 
			referentiel_mise_a_jour_competences_certificate_user($old_liste_competences, $activite->comptencies, $activite->userid, $activite->referentielid, $activite->approved, true, $activite->approved);
		}
	}
	else if (isset($form->action) && ($form->action=="modifier_document")){
		$document = new object();
		$document->id=$form->document_id;
		$document->url=($form->url);
		$document->type=($form->type);
		$document->description=($form->description);
		$document->activityid=$form->activityid;
		if (isset($form->target)){
			$document->target=$form->target;
   		}
		else{
			$document->target=1;
		}
		if (isset($form->label)){
			$document->label=$form->label;
   		}
		else{
			$document->label='';
		}
		
   		// DEBUG
		// print_object($document);
    	// echo "<br />";
		$ok= $ok && update_record("referentiel_document", $document);
		// exit;
	}
	else if (isset($form->action) && ($form->action=="creer_document")){
		$document = new object();
		$document->url=($form->url);
		$document->type=($form->type);
		$document->description=($form->description);
		$document->activityid=$form->activityid;
		if (isset($form->target)){
			$document->target=$form->target;
   		}
		else{
			$document->target=1;
		}
		if (isset($form->label)){
			$document->label=$form->label;
   		}
		else{
			$document->label='';
		}
		
   		// DEBUG
		// print_object($document);
    	// echo "<br />";
		$ok = insert_record("referentiel_document", $document);
    	// echo "DOCUMENT ID / $ok<br />";
		// exit;
	}
    return $ok;
}

function referentiel_update_document($form) {
// MAJ document;
    // DEBUG
	// echo "<br />UPDATE ACTIVITY<br />\n";
	// print_object($form);
    // echo "<br />";
	if (isset($form->document_id) && $form->document_id
		&&
		isset($form->activityid) && $form->activityid){
		$document = new object();
		$document->id=$form->document_id;
		$document->url=($form->url);
		$document->type=($form->type);
		$document->description=($form->description);
		$document->activityid=$form->activityid;
		if (isset($form->target)){
			$document->target=($form->target);
   		}
		else{
			$document->target=1;
		}
		if (isset($form->label)){
			$document->label=$form->label;
   		}
		else{
			$document->label='';
		}

   		// DEBUG
		// print_object($document);
    	// echo "<br />";
		return update_record("referentiel_document", $document);
	}
	return false;
}

function referentiel_add_document($form) {
// MAJ document;
	$id_document=0;
	if (isset($form->activityid) && $form->activityid){
		$document = new object();
		$document->url=($form->url);
		$document->type=($form->type);
		$document->description=($form->description);
		$document->activityid=$form->activityid;
		if (isset($form->target)){
			$document->target=$form->target;
   		}
		else{
			$document->target=1;
		}
		if (isset($form->label)){
			$document->label=$form->label;
   		}
		else{
			$document->label='';
		}
		// DEBUG
		// print_object($document);
    	// echo "<br />";
		$id_document = insert_record("referentiel_document", $document);
    	// echo "DOCUMENT ID / $ok<br />";
		// exit;
	}
    return $id_document;
}





//////////////////////////////////////////////////////////////////////////////////////
/// Any other referentiel functions go here.  Each of them must have a name that 
/// starts with referentiel_

/**
 * This function returns max id from table passed
 *
 * @param table name
 * @return id
 * @todo Finish documenting this function
 **/
function referentiel_get_max_id($table){
global $CFG;
	if (isset($table) && ($table!="")){
		$r = get_record_sql('SELECT MAX(id) as id FROM '. $CFG->prefix . $table);
		if ($r){
			return $r->id;
		}
	}
	else 
		return 0; 
}

/**
 * This function returns min id from table passed
 *
 * @param table name
 * @return id
 * @todo Finish documenting this function
 **/
function referentiel_get_min_id($table){
global $CFG;
	if (isset($table) && ($table!="")){
		$r = get_record_sql('SELECT MIN(id) as id FROM '. $CFG->prefix . $table);
		if ($r){
			return $r->id;
		}
	}
	else 
		return 0; 
}


function referentiel_get_table($id, $table) {
// retourn un objet  
    // DEBUG
    // temp added for debugging
    // echo "DEBUG : GET INSTANCE CALLED";
    // echo "<br />";
	
	// referentiel
	$objet = get_record($table, "id", $id);
    // DEBUG
	// print_object($objet);
    // echo "<br />";
	return $objet;
}

/**
 * This function returns number of domains from table referentiel
 *
 * @param id
 * @return int
 * @todo Finish documenting this function
 **/
function referentiel_get_nb_domaines($id){

	if (isset($id) && ($id > 0)){
		return count_records('referentiel_domain', 'referentielid', $id);
	}
	else 
		return 0; 
}

/**
 * This function returns records from table referentiel_domain
 *
 * @param ref
 * @return record
 * @todo Finish documenting this function
 **/
function referentiel_get_domaines($referentielid){
global $CFG;
	if (isset($referentielid) && ($referentielid>0)){
		return get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_domain WHERE referentielid='.$referentielid. ' ORDER BY sortorder ASC');
	}
	else 
		return 0; 
}


/**
 * This function returns nomber of competences from table referentiel_domain
 *
 * @param id
 * @return int
 * @todo Finish documenting this function
 **/
function referentiel_get_nb_competences($id){

	if (isset($id) && ($id > 0)){
		return count_records('referentiel_skill', 'domainid', $id);
	}
	else 
		return 0; 
}

/**
 * This function returns records from table referentiel_skill_item
 *
 * @param ref
 * @return id
 * @todo Finish documenting this function
 **/
function referentiel_get_competences($domainid){
global $CFG;
	if (isset($domainid) && ($domainid>0)){
		return get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_skill WHERE domainid='.$domainid. ' ORDER BY sortorder ASC');
	}
	else 
		return 0; 
}

/**
 * This function returns nomber of items from table referentiel_skill
 *
 * @param id
 * @return int
 * @todo Finish documenting this function
 **/
function referentiel_get_nb_item_competences($id){

	if (isset($id) && ($id > 0)){
		return count_records('referentiel_skill_item', 'skillid', $id);
	}
	else 
		return 0; 
}

/**
 * This function returns records from table referentiel_skill_item
 *
 * @param id
 * @return int
 * @todo Finish documenting this function
 **/
function referentiel_get_item_competences($skillid){
global $CFG;
	if (isset($skillid) && ($skillid>0)){
		return get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_skill_item WHERE skillid='.$skillid. ' ORDER BY sortorder ASC ');
	}
	else 
		return 0; 
}

/**
 * This function returns an int from table referentiel_skill_item
 *
 * @param id
 * @return int of poids
 * @todo Finish documenting this function
 **/
function referentiel_get_poids_item($code, $referentiel_id){
global $CFG;
	if (isset($code) && ($code!="")){
		$record = get_record_sql("SELECT weight FROM ". $CFG->prefix . "referentiel_skill_item WHERE code='".$code."' AND referentielid=".$referentiel_id." ");
		if ($record){
			return $record->weight;
		}
	}
	return 0;
}


/**
 * This function returns an int from table referentiel_skill_item
 *
 * @param referentiel id
 * @return int of empreinte
 * @todo Finish documenting this function
 **/
function referentiel_get_empreinte_item($code, $referentiel_id){
global $CFG;
	if (isset($code) && ($code!="")){
		$record=get_record_sql("SELECT footprint FROM ". $CFG->prefix . "referentiel_skill_item WHERE code='".$code."' AND referentielid=".$referentiel_id." ");
		if ($record){
			return $record->footprint;
		}
	}
	return 0;
}


/**
 * This function returns a string from table referentiel_skill_item
 *
 * @param referentiel id
 * @return string of poids
 * @todo Finish documenting this function
 **/
function referentiel_get_liste_poids($referentiel_id){
global $CFG;
$liste="";
	$records=get_records_sql("SELECT id, description, weight FROM ". $CFG->prefix . "referentiel_skill_item WHERE referentielid=".$referentiel_id." ");
	if ($records){
		 foreach ($records as $record) {
		 	$liste.= $record->description.'#'.$record->weight.'|';
		 }
	}
	return $liste;
}



/**
 * This function returns a string from table referentiel_skill_item
 *
 * @param code, referentiel id
 * @return string
 * @todo Finish documenting this function
 **/

function referentiel_get_description_item($code, $referentiel_id=0){

global $CFG;
	if (isset($code) && ($code!="")){
		if ($referentiel_id){
			$record = get_record_sql("SELECT description FROM ". $CFG->prefix . "referentiel_skill_item WHERE code='".$code."' AND referentielid=".$referentiel_id." ");
			if ($record){
				return $record->description;
			}
		}
		else{
			$records = get_records_sql("SELECT description FROM ". $CFG->prefix . "referentiel_skill_item WHERE code='".$code."' ");
			if ($records){
				$s="";
				foreach ($records as $record){
					$s .= $record->description." ";
				}
				return $s;
			}
		}
	}
	return "";
}
/*
function referentiel_get_description_item($code, $referentiel_id){

global $CFG;
	if (isset($code) && ($code!="") && ($referentiel_id)){
		$record=get_record_sql("SELECT description FROM ". $CFG->prefix . "referentiel_skill_item WHERE code='".$code."' AND referentielid=".$referentiel_id." ");
		if ($record){
			return $record->description;
		}
	}
	return "";
}
*/

/**
 * This function returns records from table referentiel
 *
 * @param $id : int id refrentiel to filter
 * $params filtering clause
 * @return int
 * @todo Finish documenting this function
 **/
function referentiel_filtrer($id, $params){
global $CFG;
	if (isset($id) && ($id>0)){
		$where = "WHERE id=".$id." ";
		if (isset($params)){
			if (isset($params->filtrerinstance) && ($params->filtrerinstance!=0)){
				if (isset($params->localinstance) && ($params->localinstance==0)){
					$where .= " AND local=0 ";
				}
				else {
					$where .= " AND local!=0 ";
				}
			}
			// $params->referentiel_pass
			if (isset($params->referentiel_pass) && ($params->referentiel_pass!='')){
				$where .= " AND password='".$params->referentiel_pass."' ";
			}
		}
		$record = get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_referentiel '.$where.' ');
		if ($record){
			return $record->id;
		}
		else {
			return 0;
		}
	}
	else{
		return 0;
	}
}

/**
 * This function returns records from table referentiel_referentiel
 *
 * @param id
 * @return int
 * @todo Finish documenting this function
 **/
function referentiel_get_referentiel_referentiel($id){
global $CFG;
	if (isset($id) && ($id > 0)){
		return get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_referentiel WHERE id='.$id.' ');
	}
	else 
		return 0; 
}

/**
 * This function returns string from table referentiel_referentiel
 *
 * @param id
 * @return int
 * @todo Finish documenting this function
 **/
function referentiel_get_nom_referentiel($id){
global $CFG;
$s="";
	if (isset($id) && ($id>0)){
		$record=get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_referentiel WHERE id='.$id.' ');
		if ($record){
			$s=$record->name;
		}
	}
	return $s; 
}


/**
 * This function returns records from table referentiel
 *
 * @param $params filtering clause
 * @return records
 * @todo Finish documenting this function
 **/
function referentiel_get_referentiel_referentiels($params){
global $CFG;
	$where = "";
	if (isset($params)){
		if (isset($params->filtrerinstance) && ($params->filtrerinstance!=0)){
			if (isset($params->localinstance) && ($params->localinstance==0)){
				$where = " WHERE local=0 ";
			}
			else {
				$where = " WHERE local!=0 ";
			}
		}
	}
	
	return get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_referentiel '.$where.' ORDER BY id ASC ');
}


/**
 * This function returns records from table referentiel
 *
 * @param id
 * @return int
 * @todo Finish documenting this function
 **/
function referentiel_get_referentiel($id){
global $CFG;
	if (isset($id) && ($id>0)){
		return get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel WHERE id='.$id.' ');
	}
	else 
		return 0; 
}


// ACTIVITES

/**
 * This function returns record from table referentiel_activity
 *
 * @param id
 * @return object
 * @todo Finish documenting this function
 **/
function referentiel_get_activite($id){
global $CFG;
	if (isset($id) && ($id>0)){
		return get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_activity WHERE id='.$id.' ');
	}
	else 
		return 0; 
}

/**
 * This function returns records from table referentiel_activity
 *
 * @param referentiel_id referentiel_activity->referentielid : referentiel_referentiel id
 * @param user_id  referentiel_activity->userid : user id
 * @return array of objects
 * @todo Finish documenting this function
 **/
function referentiel_user_activites($referentiel_id, $user_id){
global $CFG;
	if (($user_id>0) && ($referentiel_id>0)){
		$records=get_records_sql('SELECT id FROM '. $CFG->prefix . 'referentiel_activity WHERE referentielid='.$referentiel_id.' AND userid='.$user_id);
		return $records;
	}
	else 
		return 0; 
}

/**
 * This function returns records owned by user_id from table referentiel_activity 
 *
 * @param id reference id , user id
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_all_activites_user_course($referentiel_id, $user_id, $course_id, $sql_filtre_where='', $sql_filtre_order=''){
global $CFG;
	if (isset($referentiel_id) && ($referentiel_id>0) && isset($course_id) && ($course_id>0)){
		return get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_activity WHERE referentielid='.$referentiel_id.' AND course='.$course_id.' AND userid='.$user_id.' '.$sql_filtre_where.' ORDER BY timecreated DESC '.$sql_filtre_order);
	}
	else 
		return 0; 
}



/**
 * This function returns records owned by user_id from table referentiel_activity for $referentiel_id
 *
 * @param id reference id , user id
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_all_activites_user($referentiel_id, $user_id, $sql_filtre_where='', $sql_filtre_order=''){
global $CFG;
// DEBUG
	
	if (isset($referentiel_id) && ($referentiel_id>0)){
		if ($sql_filtre_order==''){
			$sql_filtre_order='  timecreated DESC ';
		}
		$sql = 'SELECT * FROM '. $CFG->prefix . 'referentiel_activity WHERE referentielid='.$referentiel_id.' AND userid='.$user_id.' '.$sql_filtre_where.' ORDER BY '.$sql_filtre_order;
		// DEBUG
		// echo "<br>DEBUG :: lib.sql :: Ligne 2459 :: SQL&gt; $sql\n";
		
		return get_records_sql($sql);
	}
	else 
		return 0; 
}

/**
 * This function returns records from table referentiel_activity for referentiel_instance_id and user_id
 *
 * @param id reference activite
 * @param select clause : ' AND champ=valeur,  ... '
 * @param order clause : ' champ ASC|DESC, ... '
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_instance_get_activites_user($referentiel_instance_id, $user_id, $sql_filtre_where='', $sql_filtre_order=''){
global $CFG;
	if (isset($referentiel_instance_id) && ($referentiel_instance_id>0)){
		if ($sql_filtre_order==''){
			$sql_filtre_order='  timecreated DESC ';
		}
		
		return get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_activity WHERE instanceid='.$referentiel_instance_id.' AND userid='.$user_id.' '.$sql_filtre_where.' ORDER BY '.$sql_filtre_order);
	}
	else 
		return NULL; 
}


/**
 * This function returns records from table referentiel_activity
 *
 * @param id reference activite
 * @param select clause : ' AND champ=valeur,  ... '
 * @param order clause : ' champ ASC|DESC, ... ' 
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_users_activites($referentiel_id, $sql_filtre_where='', $sql_filtre_order=''){
global $CFG;
	if (isset($referentiel_id) && ($referentiel_id>0)){
		if ($sql_filtre_order==''){
			$sql_filtre_order=' userid ASC ';
		}
		else{
			$sql_filtre_order=' userid ASC, '.$sql_filtre_order;
		}
		return get_records_sql('SELECT DISTINCT userid FROM '. $CFG->prefix . 'referentiel_activity WHERE referentielid='.$referentiel_id.' '.$sql_filtre_where.' ORDER BY '.$sql_filtre_order.' ');
	}
	else 
		return 0; 
}



/**
 * This function returns records from table referentiel_activity
 *
 * @param id reference activite
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_teachers_activites($referentiel_id){
global $CFG;
	if (isset($referentiel_id) && ($referentiel_id>0)){
		return get_records_sql('SELECT DISTINCT teacherid FROM '. $CFG->prefix . 'referentiel_activity WHERE referentielid='.$referentiel_id.' ORDER BY teacherid ASC ');
	}
	else 
		return 0; 
}



/**
 * This function returns records from table referentiel_activity
 *
 * @param id reference activite
 * @param select clause : ' AND champ=valeur,  ... '
 * @param order clause : ' champ ASC|DESC, ... '
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_activites_instance($referentiel_instance_id, $select="", $order=""){
global $CFG;
	if (isset($referentiel_instance_id) && ($referentiel_instance_id>0)){
		if (empty($order)){
			$order= 'userid ASC, timecreated DESC ';
		}
		return get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_activity WHERE instanceid='.$referentiel_instance_id.' '.$select.' ORDER BY '.$order.' ');
	}
	else 
		return NULL; 
}



/**
 * This function returns records from table referentiel_activity
 *
 * @param id reference activite
 * @param select clause : ' AND champ=valeur,  ... '
 * @param order clause : ' champ ASC|DESC, ... '
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_users_activites_instance($referentiel_instance_id, $userid=0, $select='', $order=''){
global $CFG;
	$where='';
	if (isset($referentiel_instance_id) && ($referentiel_instance_id>0)){
		if ($userid!=0){
			$where= ' AND userid='.$userid.' ';
		}
		if (empty($order)){
			$order= 'userid ASC, timecreated DESC ';
		}
		
		return get_records_sql('SELECT DISTINCT userid FROM '. $CFG->prefix . 'referentiel_activity WHERE instanceid='.$referentiel_instance_id.' '.$where.' '.$select.' ORDER BY '.$order.' ');
	}
	else 
		return NULL; 
}



/**
 * This function returns records from table referentiel_activity
 *
 * @param id reference activite
 * @param select clause : ' AND champ=valeur,  ... '
 * @param order clause : ' champ ASC|DESC, ... '
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_activites($referentiel_id, $select="", $order=""){
global $CFG;
	if (isset($referentiel_id) && ($referentiel_id>0)){
		if (empty($order)){
			$order= 'userid ASC, timecreated DESC ';
		}
		return get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_activity WHERE referentielid='.$referentiel_id.' '.$select.' ORDER BY '.$order.' ');
	}
	else 
		return 0; 
}

/**
 * This function returns record document from table referentiel_document
 *
 * @param id activityid
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_documents($activite_id){
global $CFG;
	if (isset($activite_id) && ($activite_id>0)){
		return get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_document WHERE activityid='.$activite_id.' ORDER BY id ASC ');
	}
	else 
		return NULL;
}

/**
 * This function returns number of document from table referentiel_document
 *
 * @param id activityid
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_nombre_documents($activite_id){
global $CFG;
	if (isset($activite_id) && ($activite_id>0)){
		$r=get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_document WHERE activityid='.$activite_id);
        print_r($r) ;
        return (count($r));
	}
	else
		return 0;
}

//function referentiel_user_can_addactivity($referentiel, $currentgroup, $groupmode) {
function referentiel_user_can_addactivity($referentiel) {
    global $USER;

    if (!$cm = get_coursemodule_from_instance('referentiel', $referentiel->id, $referentiel->course)) {
        print_error('Course Module ID was incorrect');
    }
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    if (!has_capability('mod/referentiel:write', $context)) {
        return false;
    }
/*
    if (!$groupmode or has_capability('moodle/site:accessallgroups', $context)) {
        return true;
    }

    if ($currentgroup) {
        return ismember($currentgroup);
    } else {
        //else it might be group 0 in visible mode
        if ($groupmode == VISIBLEGROUPS){
            return true;
        } else {
            return false;
        }
    }
*/
	return true;
}


function referentiel_activite_isowner($id){
global $USER;
	if (isset($id) && ($id>0)){
		$record=get_record("referentiel_activity", "id", "$id");
		// DEBUG
		// echo "<br >USERID : $USER->id ; OWNER : $record->userid\n";
		return ($USER->id == $record->userid);
	}
	else 
		return false; 
} 


// COMPETENCES

// Liste des codes de competences du rÃ©fÃ©rentiel
// retourne la liste des codes de competences pour la table referentiel
function referentiel_get_liste_codes_competence($id){
	global $CFG;

	if (isset($id) && ($id > 0)){
		if ($items = get_records_menu('referentiel_skill_item', 'referentielid', $id, 'sortorder', 'id, code')){
			$codelist = implode('/', array_values($items));
		}
		return $codelist;
	}
	return 0;
}


// Liste des codes de competences du rÃ©fÃ©rentiel
function referentiel_new_liste_codes_competence($id){
// regenere la liste des codes de competences pour la table referentiel
global $CFG;
$liste_codes_competence="";
	if (isset($id) && ($id>0)){
		$record_r=get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_referentiel WHERE id='.$id.' ');	    
		if ($record_r){
    		// afficher
			// DEBUG
			// echo "<br/>DEBUG ::<br />\n";
			// print_r($record_r);
			$old_liste_codes_competence=$record_r->liste_codes_competence;
			$liste_codes_competence="";
			// charger les domaines associes au referentiel courant
			$referentiel_id=$id; // plus pratique
			// LISTE DES DOMAINES
			$records_domaine = get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_domain WHERE referentielid='.$referentiel_id. ' ORDER BY sortorder ASC ');
			if ($records_domaine){
    			// afficher
				// DEBUG
				// echo "<br/>DEBUG ::<br />\n";
				// print_r($records_domaine);
				foreach ($records_domaine as $record_d){
        			$domaine_id=$record_d->id;
					$records_competences = get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_skill WHERE domainid='.$domaine_id. ' ORDER BY sortorder ASC ');
			   		if ($records_competences){
						// DEBUG
						// echo "<br/>DEBUG :: COMPETENCES <br />\n";
						// print_r($records_competences);
						foreach ($records_competences as $record_c){
							$competence_id=$record_c->id;
							// ITEM
							$compteur_item=0;
							$records_items = get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_skill_item WHERE skillid='.$competence_id. ' ORDER BY sortorder ASC ');
					    	if ($records_items){
								// DEBUG
								// echo "<br/>DEBUG :: ITEMS <br />\n";
								// print_r($records_items);
								foreach ($records_items as $record_i){
									$liste_codes_competence.=$record_i->code."/";
								}
							}
						}
					}
				}
			}
		}
	}
	return $liste_codes_competence;
}
/**
 * Given an id referentiel_referentiel, 
 * will update an existing instance with new liste_codes_competence.
 *
 * @param id, list
 * @return boolean Success/Fail
**/
function referentiel_set_liste_codes_competence($id, $liste_codes_competence){
	if (isset($id) && ($id>0)){
		$referentiel=referentiel_get_referentiel_referentiel($id);
		$referentiel->name=addslashes($referentiel->name);
		$referentiel->code=addslashes($referentiel->code);
		$referentiel->description=addslashes($referentiel->description);
	    $referentiel->timemodified = time();
		$referentiel->liste_codes_competence=$liste_codes_competence;
	    // DEBUG
		// echo "<br />DEBUG :: lib.php :: 1857";
		// print_object($referentiel);
	    // echo "<br />";
	    return(update_record("referentiel_referentiel", $referentiel));
	}
	return false;
}

// Liste des poids de competences du referentiel
// retourne la liste des poids de competences pour la table referentiel
// MODIF JF 2009/10/16
function referentiel_get_liste_poids_competence($id){
	global $CFG;

	if (isset($id) && ($id > 0)){
		if ($items = get_records_menu('referentiel_skill_item', 'referentielid', $id, 'sortorder', 'id, weight')){
			$codelist = implode('/', array_values($items));
		}
	}
	return 0;
}


// Liste des poids de competences du rÃ©fÃ©rentiel
// regenere la liste des poids de competences pour la table referentiel
function referentiel_new_liste_poids_competence($id){
	global $CFG;

	$liste_poids_competence = '';
	if (isset($id) && ($id>0)){
		$record_r = get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_referentiel WHERE id='.$id);	    
		if ($record_r){
    		// afficher
			// DEBUG
			// echo "<br/>DEBUG ::<br />\n";
			// print_r($record_r);
			$old_liste_poids_competence = @$record_r->liste_poids_competence;
			$liste_poids_competence = '';
			// charger les domaines associes au referentiel courant
			$referentiel_id=$id; // plus pratique
			// LISTE DES DOMAINES
			$records_domaine = get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_domain WHERE referentielid='.$referentiel_id. ' ORDER BY sortorder ASC');
			if ($records_domaine){
    			// afficher
				// DEBUG
				// echo "<br/>DEBUG ::<br />\n";
				// print_r($records_domaine);
				foreach ($records_domaine as $record_d){
        			$domaine_id = $record_d->id;
					$records_competences = get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_skill WHERE domainid='.$domaine_id. ' ORDER BY sortorder ASC');
			   		if ($records_competences){
						// DEBUG
						// echo "<br/>DEBUG :: COMPETENCES <br />\n";
						// print_r($records_competences);
						foreach ($records_competences as $record_c){
							$competence_id = $record_c->id;
							// ITEM
							$compteur_item = 0;
							$records_items = get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_skill_item WHERE skillid='.$competence_id. ' ORDER BY sortorder ASC');
					    	if ($records_items){
								// DEBUG
								// echo "<br/>DEBUG :: ITEMS <br />\n";
								// print_r($records_items);
								foreach ($records_items as $record_i){
									$liste_poids_competence .= $record_i->weight."/";
								}
							}
						}
					}
				}
			}
		}
	}
	return $liste_poids_competence;
}

/**
 * Given an id referentiel_referentiel and a list of poids
 * this function will update liste_poids_competence.
 *
 * @param id, list
 * @return boolean Success/Fail
 **/
function referentiel_set_liste_poids_competence($id, $liste_poids_competence){
	if (isset($id) && ($id>0)){
		$referentiel=referentiel_get_referentiel_referentiel($id);
	    $referentiel->timemodified = time();
		$referentiel->code = addslashes($referentiel->code);
		$referentiel->description = addslashes($referentiel->description);
		$referentiel->url = addslashes($referentiel->url);
		$referentiel->liste_poids_competence = $liste_poids_competence;
	    // DEBUG
		// print_object($referentiel);
	    // echo "<br />";
	    return(update_record("referentiel_referentiel", $referentiel));
	}
	return false;
}


/**
 * Given an array , 
 * return a new liste_codes_competence.
 *
 * @param array $instance An object from the form in mod_activite.html
 * @return string
 **/
function reference_conversion_code_2_liste_competence($separateur, $tab_code_item){
$lc="";
// print_r($tab_code_item);
// echo "<br />DEBUG\n";

	if (count($tab_code_item)>0){
		for ($i=0; $i<count($tab_code_item); $i++){
			$lc.=$tab_code_item[$i].$separateur;
		}
	}
	return $lc;
}


// Liste des empreintes de competences du rÃ©fÃ©rentiel
function referentiel_get_liste_empreintes_competence($id){
	global $CFG;

	if (isset($id) && ($id > 0)){
		if ($items = get_records_menu('referentiel_skill_item', 'referentielid', $id, '', 'id, footprint')){
			$codelist = implode('/', array_values($items));
		}
	}
	return 0;
}


// Liste des empreintes de competences du rÃ©fÃ©rentiel
function referentiel_new_liste_empreintes_competence($id){
// regenere la liste des empreintes de competences pour la table referentiel
global $CFG;
$liste_empreintes_competence="";
	if (isset($id) && ($id>0)){
		$record_r=get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_referentiel WHERE id='.$id);	    
		if ($record_r){
    		// afficher
			// DEBUG
			// echo "<br/>DEBUG ::<br />\n";
			// print_r($record_r);
			$old_liste_empreintes_competence=$record_r->liste_empreintes_competence;
			$liste_empreintes_competence="";
			// charger les domaines associes au referentiel courant
			$referentiel_id=$id; // plus pratique
			// LISTE DES DOMAINES
			$records_domaine = get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_domain WHERE referentielid='.$referentiel_id. ' ORDER BY sortorder ASC');
			if ($records_domaine){
    			// afficher
				// DEBUG
				// echo "<br/>DEBUG ::<br />\n";
				// print_r($records_domaine);
				foreach ($records_domaine as $record_d){
        			$domaine_id=$record_d->id;
					$records_competences = get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_skill WHERE domainid='.$domaine_id. ' ORDER BY sortorder ASC');
			   		if ($records_competences){
						// DEBUG
						// echo "<br/>DEBUG :: COMPETENCES <br />\n";
						// print_r($records_competences);
						foreach ($records_competences as $record_c){
							$competence_id=$record_c->id;
							// ITEM
							$compteur_item=0;
							$records_items = get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_skill_item WHERE skillid='.$competence_id. ' ORDER BY sortorder ASC');
					    	if ($records_items){
								// DEBUG
								// echo "<br/>DEBUG :: ITEMS <br />\n";
								// print_r($records_items);
								foreach ($records_items as $record_i){
									$liste_empreintes_competence.=$record_i->footprint."/";
								}
							}
						}
					}
				}
			}
		}
	}
	return $liste_empreintes_competence;
}

/**
 * Given an id referentiel_referentiel and a list of empreintes
 * this function will update liste_empreintes_competence.
 *
 * @param id, list
 * @return boolean Success/Fail
 **/
function referentiel_set_liste_empreintes_competence($id, $liste_empreintes_competence){
	if (isset($id) && ($id>0)){
		$referentiel=referentiel_get_referentiel_referentiel($id);
	    $referentiel->timemodified = time();
		$referentiel->code=addslashes($referentiel->code);
		$referentiel->description=addslashes($referentiel->description);
		$referentiel->url=addslashes($referentiel->url);
		$referentiel->liste_empreintes_competence=$liste_empreintes_competence;
	    // DEBUG
		// print_object($referentiel);
	    // echo "<br />";
	    return(update_record("referentiel_referentiel", $referentiel));
	}
	return false;
}

function referentiel_admins_liste($id_course) {
//liste des admins d'un cours
// version  MOODLE 1.7 et suivants !!!
    $context = get_context_instance(CONTEXT_COURSE, $id_course);
    $admins=get_users_by_capability($context,"moodle/legacy:admin", "u.id,firstname,lastname,email","lastname");
    $liste="";
    foreach ($admins as $p){
       $liste .=$p->id. ' '.$p->firstname. ' '.$p->lastname.'<'.$p->email.'>';
	}
   return $liste;
}

function referentiel_is_admin($userid, $id_course){
    $context = get_context_instance(CONTEXT_COURSE, $id_course);
    $admins=get_users_by_capability($context,"moodle/legacy:admin", "u.id","lastname");
    foreach ($admins as $p){
       if($userid==$p->id){
            return true;
       }
	}
   return false;
}


function referentiel_editingteachers($id_course) {
//liste des profs d'un cours
// version  MOODLE 1.7 !!!
    $context = get_context_instance(CONTEXT_COURSE, $id_course);
    $profs=get_users_by_capability($context,"moodle/legacy:editingteacher", "firstname,lastname,email","lastname");
    $liste="";
    foreach ($profs as $p){
       $liste .=$p->firstname. ' '.$p->lastname.'<'.$p->email.'>';
	}
   return $liste;
}

/**
 * Returns user object 
 *
 * @param user id
 * @return user info.
 */
function referentiel_get_user($user_id){
    global $CFG;
	if (isset($user_id) && ($user_id>0)){  
    return get_record_sql("SELECT u.id, u.username, u.firstname, u.lastname, u.maildisplay, u.mailformat, u.maildigest, u.emailstop, u.imagealt,
                                   u.email, u.city, u.country, u.lastaccess, u.lastlogin, u.picture, u.timezone, u.theme, u.lang, u.trackforums, u.mnethostid
                              FROM {$CFG->prefix}user u 
                              WHERE u.id=".$user_id."  
                          ORDER BY u.email ASC");
  }  
  return false;
}

// ------------------------------------------
function referentiel_get_user_info($user_id) {
// retourne le NOM prenom Ã  partir de l'id
global $CFG;
	$user_info="";
	if (isset($user_id) && ($user_id>0)){
		$sql = "SELECT firstname, lastname FROM {$CFG->prefix}user as a WHERE a.id = ".$user_id." ";
		$user = get_record_sql($sql);
		if ($user){
			$user_info=$user->firstname.' '.$user->lastname;
		}
	}
	return $user_info;
}

// ------------------------------------------
function referentiel_get_user_prenom($user_id) {
// retourne le NOM prenom Ã  partir de l'id
global $CFG;
	$user_info = "";
	if (isset($user_id) && ($user_id>0)){
		$sql = "SELECT firstname FROM {$CFG->prefix}user as a WHERE a.id = ".$user_id." ";
		$user = get_record_sql($sql);
		if ($user){
			$user_info = $user->firstname;
		}
	}
	return $user_info;
}

// ------------------------------------------
function referentiel_get_user_nom($user_id) {
// retourne le NOM prenom Ã  partir de l'id
global $CFG;
	$user_info="";
	if (isset($user_id) && ($user_id>0)){
		$sql = "SELECT lastname FROM {$CFG->prefix}user as a WHERE a.id = ".$user_id." ";
		$user = get_record_sql($sql);
		if ($user){
			$user_info=$user->lastname;
		}
	}
	return $user_info;
}



function referentiel_get_user_login($id) {
// retourne le login Ã  partir de l'id
global $CFG;
	if ($id>0){
		$sql = "SELECT username FROM {$CFG->prefix}user as a WHERE a.id = ".$id." ";
		$user = get_record_sql($sql);
		if ($user){
			return $user->username;
		}
	}
	return '';
}

function referentiel_get_userid_by_login($login){
// retourne l'id a partir du login
global $CFG;
	if ($login!=''){
		$sql = "SELECT id FROM {$CFG->prefix}user as a WHERE a.username = '".$login."' ";
		$user = get_record_sql($sql);
		if ($user){
			return $user->id;
		}
	}
	return 0;
}


function referentiel_get_user_mail($user_id) {
// retourne le NOM prenom Ã  partir de l'id
global $CFG;
	$user_info="";
	if (isset($user_id) && ($user_id>0)){
		$sql = "SELECT email FROM {$CFG->prefix}user as a WHERE a.id = ".$user_id." ";
		$user = get_record_sql($sql);
		if ($user){
			$user_info=$user->email;
		}
	}
	return $user_info;
}


// TACHES 
// -----------------------
function referentiel_user_can_use_task($referentiel) {
    global $USER;

    if (!$cm = get_coursemodule_from_instance('referentiel', $referentiel->id, $referentiel->course)) {
        print_error('Course Module ID was incorrect');
    }
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    if (has_capability('mod/referentiel:addtask', $context) || has_capability('mod/referentiel:viewtask', $context)) {
        return true;
    }
	else{
		return false;
	}
}



// URL

    /**
     * display an url accorging to moodle file mangement 
     * @return string active link
	 * @ input $url : an uri
	 * @ input $etiquette : a label 
     */
    function referentiel_affiche_url($url, $etiquette="", $cible="") {
	global $CFG;
		if ($etiquette==""){
			$l=strlen($url);
			$posr=strrpos($url,'/');
			if ($posr===false){ // pas de separateur
				$etiquette=$url;
			}
			else if ($posr==$l-1){ // separateur en fin
				$etiquette=get_string("etiquette_inconnue", "referentiel");
			}
			else if ($posr==0){ // separateur en tete et en fin !
				$etiquette=get_string("etiquette_inconnue", "referentiel");
			}
			else {
				$etiquette=substr($url,$posr+1);
			}
		}
		$importfile = "{$CFG->dataroot}/{$url}";
		if (file_exists($importfile)) {
	        if ($CFG->slasharguments) {
    	    	$efile = "{$CFG->wwwroot}/file.php/$url";
        	}
		    else {
				$efile = "{$CFG->wwwroot}/file.php?file=/$url";
        	}
		}
		else{
			$efile = "$url";
		}
		
		return "<a href=\"$efile\" target=\"".$cible."\">$etiquette</a>";
    }
    

    /**
     * display an url accorging to moodle file mangement
     * @return string active link
	 * @ input $url : an uri
	 * @ input $etiquette : a label
     */
    function referentiel_get_url($url, $etiquette="", $cible="") {
	global $CFG;
		if ($etiquette==""){
			$l=strlen($url);
			$posr=strrpos($url,'/');
			if ($posr===false){ // pas de separateur
				$etiquette=$url;
			}
			else if ($posr==$l-1){ // separateur en fin
				$etiquette=get_string("etiquette_inconnue", "referentiel");
			}
			else if ($posr==0){ // separateur en tete et en fin !
				$etiquette=get_string("etiquette_inconnue", "referentiel");
			}
			else {
				$etiquette=substr($url,$posr+1);
			}
		}
		$importfile = "{$CFG->dataroot}/{$url}";
		if (file_exists($importfile)) {
	        if ($CFG->slasharguments) {
    	    	$efile = "{$CFG->wwwroot}/file.php/$url";
        	}
		    else {
				$efile = "{$CFG->wwwroot}/file.php?file=/$url";
        	}
		}
		else{
			$efile = "$url";
		}
        if ($cible){
    		return '<a href=\"'.$efile.'\" target=\"'.$cible.'\">'.$etiquette.'</a>';
        }
        else{
    		return '<a href='.$efile.'>'.$etiquette.'</a>';
        }
    }

	
// TRAITEMENT DES LISTES code, poids, empreintes

/**
 purge 
*/
function referentiel_purge_dernier_separateur($s, $sep){
	if ($s){
		$s=trim($s);
		if ($sep){
			$pos = strrpos($s, $sep);
			if ($pos === false) { // note : trois signes Ã©gal  
				// pas trouvÃ©
			}
			else{
				// supprimer le dernier "/"
				if ($pos==strlen($s)-1){
					return substr($s,0, $pos);
				}
			}
		}
	}
	return $s;
}



// ----------------
function referentiel_purge_caracteres_indesirables($texte){
	$cherche = array(",", "\"", "'","\r\n", "\n", "\r");
	$remplace= array(" ", " ", " " , " ", " ", " ");
	return str_replace($cherche, $remplace, $texte);
}


function referentiel_initialise_descriptions_items_referentiel($referentiel_referentiel_id){
// calcule la table des descriptions des items de competences
// necessaire Ã  l'affichage des overlib
global $t_item_code; // codes
global $t_item_description_competence; // descriptifs
	$t_item_code=array();
	$t_item_description_competence=array(); // table des descriptions d'item
	$compteur_domaine=0;
	$compteur_competence=0;
	$compteur_item=0;
	
	// ITEMS
	if (isset($referentiel_referentiel_id) && ($referentiel_referentiel_id>0)){
		$record_a = referentiel_get_referentiel_referentiel($referentiel_referentiel_id);
		$code = $record_a->code;
		// $nb_domaines = $record_a->nb_domaines;
		$liste_codes_competence=$record_a->liste_codes_competence;
		// charger les domaines associes au referentiel courant
		// DOMAINES
		$records_domaine = referentiel_get_domaines($referentiel_referentiel_id);
	   	if ($records_domaine){
			foreach ($records_domaine as $record){
				$domaine_id = $record->id;
				$nb_competences = $record->nb_competences;
				// LISTE DES COMPETENCES DE CE DOMAINE
				$records_competences = referentiel_get_competences($domaine_id);
		    	if ($records_competences){
					foreach ($records_competences as $record_c){
						$competence_id=$record_c->id;
						$nb_item_competences=$record_c->nb_item_competences;
						// ITEM
						$records_items = referentiel_get_item_competences($competence_id);
					    if ($records_items){
							foreach ($records_items as $record_i){
								$t_item_code[$compteur_item]=stripslashes($record_i->code);
								$t_item_description_competence[$t_item_code[$compteur_item]]=referentiel_purge_caracteres_indesirables(stripslashes($record_i->description));
								$compteur_item++;
							}
						}
						$compteur_competence++;
					}
				}
				$compteur_domaine++;
			}
		}
	}
	return ($compteur_item>0);
}




/**
 * This function sets referentiel_referentiel contents in arrays
 *
 * @param id
 * @return int
 * @todo Finish documenting this function
 **/
function referentiel_initialise_data_referentiel($referentiel_referentiel_id, $mode_calcul=0){
	if ($mode_calcul==0){
		return (referentiel_initialise_data_referentiel_new($referentiel_referentiel_id));
	}
	else{
		return (referentiel_initialise_data_referentiel_old($referentiel_referentiel_id));
	}
}
/**
 * This function sets referentiel_referentiel contents in arrays
 *
 * @param id
 * @return int
 * @todo Finish documenting this function
 **/

function referentiel_initialise_data_referentiel_new($referentiel_referentiel_id){
/*
Je comprends mieux maintenant ton approche.
Finalement ce que j'appelais POIDS est pour toi quelque chose  comme 
EMPREINT * POIDS
et cela donne pour ta formule
SOMME(V / E * P * E) / SOMME(P * E)  =  SOMME(V *  P) / SOMME(P * E)
qui comme tu le disais est Ã©quivalent Ã 
SOMME(V / E * P ) / SOMME(P ) si E identique partout...
*/
// calcule la table des coefficients poids/empreintes pour les item, competences, domaines
// necessaire Ã  l'affichage de la liste des 'notes' dans un certificate (pourcentages de competences validees) 
// return true or false
global $t_domaine;
global $t_domaine_coeff;
global $t_competence;
global $t_competence_coeff;
global $t_item_code;
global $t_item_description_competence; // descriptifs
global $t_item_coeff; // coefficients
global $t_item_domaine; // index du domaine associÃ© Ã  un item 
global $t_item_competence; // index de la competence associÃ©e Ã  un item 
global $t_item_poids;
global $t_item_empreinte;
global $t_nb_item_domaine;
global $t_nb_item_competence;

	$cherche=array();
	$remplace=array(); 
	$compteur_domaine=0;
	$compteur_competence=0;
	$compteur_item=0;
	
	// DOMAINES
	$t_domaine=array();
	$t_domaine_coeff=array();
	
	// COMPETENCES
	$t_competence=array();
	$t_competence_coeff=array();

	// ITEMS
	$t_item_domaine=array(); // table des domaines d' item
	$t_item_competence=array(); // table des competences d' item
	$t_item_description_competence=array(); // table des descriptions d'item
	$t_item_code=array();
	$t_item_poids=array();
	$t_item_empreinte=array();
	$t_item_coeff=array(); // poids / empreinte
	$t_nb_item_domaine=array(); // nb item dans le domaine
	$t_nb_item_competence=array(); // nb items dans la competence

	if (isset($referentiel_referentiel_id) && ($referentiel_referentiel_id>0)){
		$record_a = referentiel_get_referentiel_referentiel($referentiel_referentiel_id);
		$code=$record_a->code;
		$certificatethreshold = $record_a->certificatethreshold;
		// $nb_domaines = $record_a->nb_domaines;
		$liste_codes_competence=$record_a->liste_codes_competence;
		$liste_empreintes_competence=$record_a->liste_empreintes_competence;
		/*
		echo "<br>DEBUG :: lib.php :: Ligne 1959 :: ".$code." ".$certificatethreshold."\n";
		echo "<br>CODES : ".$liste_codes_competence." EMPREINTES : ".$liste_empreintes_competence."\n";
		echo "<br><br>".referentiel_affiche_liste_codes_empreintes_competence('/', $liste_codes_competence, $liste_empreintes_competence); 
		*/
		// charger les domaines associes au referentiel courant
		// DOMAINES

		$records_domaine = referentiel_get_domaines($referentiel_referentiel_id);
	   	if ($records_domaine){
    		// afficher
			// DEBUG
			// echo "<br/>DEBUG ::<br />\n";
			// print_r($records_domaine);
			
			foreach ($records_domaine as $record){
				$domaine_id=$record->id;
				$nb_competences = $record->nb_competences;
				$t_domaine[$compteur_domaine]=stripslashes($record->code);
				$t_nb_item_domaine[$compteur_domaine]=0;
				
				// LISTE DES COMPETENCES DE CE DOMAINE
				$records_competences = referentiel_get_competences($domaine_id);
		    	if ($records_competences){
					// DEBUG
					// echo "<br/>DEBUG :: COMPETENCES <br />\n";
					// print_r($records_competences);
					foreach ($records_competences as $record_c){
       					$competence_id=$record_c->id;
						$nb_item_competences=$record_c->nb_item_competences;
						$t_competence[$compteur_competence]=stripslashes($record_c->code);
						$t_nb_item_competence[$compteur_competence]=0;
						
						// ITEM
						$records_items = referentiel_get_item_competences($competence_id);
					    if ($records_items){
							foreach ($records_items as $record_i){
								$t_item_code[$compteur_item]=stripslashes($record_i->code);
								$t_item_description_competence[$t_item_code[$compteur_item]]=referentiel_purge_caracteres_indesirables(stripslashes($record_i->description));
								$t_item_poids[$compteur_item]=$record_i->weight;	
								$t_item_empreinte[$compteur_item]=$record_i->footprint;
								$t_item_domaine[$compteur_item]=$compteur_domaine;
								$t_item_competence[$compteur_item]=$compteur_competence;
								$t_nb_item_domaine[$compteur_domaine]++;
								$t_nb_item_competence[$compteur_competence]++;
								$compteur_item++;
							}
						}
						$compteur_competence++;
					}
				}
				$compteur_domaine++;
			}
		}
		
		// consolidation
		// somme des poids pour les domaines
		for ($i=0; $i<count($t_domaine); $i++){
			$t_domaine_coeff[$i]=0.0;
		}
		for ($i=0; $i<count($t_item_poids); $i++){
			if (($t_item_poids[$i]) && ($t_item_empreinte[$i])){
				// $t_domaine_coeff[$t_item_domaine[$i]]+= ((float)$t_item_poids[$i] / (float)$t_item_empreinte[$i]);
				$t_domaine_coeff[$t_item_domaine[$i]]+= (float)$t_item_poids[$i] * (float)$t_item_empreinte[$i];
			}
		}
		
		// somme des poids pour les competences
		for ($i=0; $i<count($t_competence); $i++){
			$t_competence_coeff[$i]=0.0;
		}
		for ($i=0; $i<count($t_item_poids); $i++){
			if (($t_item_poids[$i]>0.0) && ($t_item_empreinte[$i]>0)){
				// $t_competence_coeff[$t_item_competence[$i]]+= ((float)$t_item_poids[$i] / (float)$t_item_empreinte[$i]);
				$t_competence_coeff[$t_item_competence[$i]]+= (float)$t_item_poids[$i] * (float)$t_item_empreinte[$i];
			}
		}
		
		// coefficient poids / empreinte pour les items
		for ($i=0; $i<count($t_competence); $i++){
			$t_item_coeff[$i]=0.0;
		}
		for ($i=0; $i<count($t_item_poids); $i++){
			if (($t_item_poids[$i]) && ($t_item_empreinte[$i])){
				$t_item_coeff[$i] = (float)$t_item_poids[$i];
			}
		}
	}
	return ($compteur_item>0);
}



/**
 * This function sets referentiel_referentiel contents in arrays
 *
 * @param id
 * @return int
 * @todo Finish documenting this function
 **/

function referentiel_initialise_data_referentiel_old($referentiel_referentiel_id){
// calcule la table des coefficients poids/empreintes pour les item, competences, domaines
// necessaire Ã  l'affichage de la liste des 'notes' dans un certificate (pourcentages de competences validees) 
// return true or false
/*
ALGO

SOMME(V / E * P ) / SOMME(P) 

*/
global $t_domaine;
global $t_domaine_coeff;
global $t_competence;
global $t_competence_coeff;
global $t_item_code;
global $t_item_description_competence; // descriptifs
global $t_item_coeff; // coefficients
global $t_item_domaine; // index du domaine associÃ© Ã  un item 
global $t_item_competence; // index de la competence associÃ©e Ã  un item 
global $t_item_poids;
global $t_item_empreinte;
global $t_nb_item_domaine;
global $t_nb_item_competence;

	$cherche=array();
	$remplace=array(); 
	$compteur_domaine=0;
	$compteur_competence=0;
	$compteur_item=0;
	
	// DOMAINES
	$t_domaine=array();
	$t_domaine_coeff=array();
	
	// COMPETENCES
	$t_competence=array();
	$t_competence_coeff=array();

	// ITEMS
	$t_item_domaine=array(); // table des domaines d' item
	$t_item_competence=array(); // table des competences d' item
	$t_item_description_competence=array(); // table des descriptions d'item
	$t_item_code=array();
	$t_item_poids=array();
	$t_item_empreinte=array();
	$t_item_coeff=array(); // poids / empreinte
	$t_nb_item_domaine=array(); // nb item dans le domaine
	$t_nb_item_competence=array(); // nb items dans la competence

	if (isset($referentiel_referentiel_id) && ($referentiel_referentiel_id>0)){
		$record_a = referentiel_get_referentiel_referentiel($referentiel_referentiel_id);
		$code=$record_a->code;
		$certificatethreshold = $record_a->certificatethreshold;
		// $nb_domaines = $record_a->nb_domaines;
		$liste_codes_competence=$record_a->liste_codes_competence;
		$liste_empreintes_competence=$record_a->liste_empreintes_competence;
		/*
		echo "<br>DEBUG :: lib.php :: Ligne 1959 :: ".$code." ".$certificatethreshold."\n";
		echo "<br>CODES : ".$liste_codes_competence." EMPREINTES : ".$liste_empreintes_competence."\n";
		echo "<br><br>".referentiel_affiche_liste_codes_empreintes_competence('/', $liste_codes_competence, $liste_empreintes_competence); 
		*/
		// charger les domaines associes au referentiel courant
		// DOMAINES

		$records_domaine = referentiel_get_domaines($referentiel_referentiel_id);
	   	if ($records_domaine){
    		// afficher
			// DEBUG
			// echo "<br/>DEBUG ::<br />\n";
			// print_r($records_domaine);
			
			foreach ($records_domaine as $record){
				$domaine_id=$record->id;
				$nb_competences = $record->nb_competences;
				$t_domaine[$compteur_domaine]=stripslashes($record->code);
				$t_nb_item_domaine[$compteur_domaine]=0;
				
				// LISTE DES COMPETENCES DE CE DOMAINE
				$records_competences = referentiel_get_competences($domaine_id);
		    	if ($records_competences){
					// DEBUG
					// echo "<br/>DEBUG :: COMPETENCES <br />\n";
					// print_r($records_competences);
					foreach ($records_competences as $record_c){
       					$competence_id=$record_c->id;
						$nb_item_competences=$record_c->nb_item_competences;
						$t_competence[$compteur_competence]=stripslashes($record_c->code);
						$t_nb_item_competence[$compteur_competence]=0;
						
						// ITEM
						$records_items = referentiel_get_item_competences($competence_id);
					    if ($records_items){
							foreach ($records_items as $record_i){
								$t_item_code[$compteur_item]=stripslashes($record_i->code);
								$t_item_description_competence[$t_item_code[$compteur_item]]=referentiel_purge_caracteres_indesirables(stripslashes($record_i->description));
								$t_item_poids[$compteur_item]=$record_i->weight;	
								$t_item_empreinte[$compteur_item]=$record_i->footprint;
								$t_item_domaine[$compteur_item]=$compteur_domaine;
								$t_item_competence[$compteur_item]=$compteur_competence;
								$t_nb_item_domaine[$compteur_domaine]++;
								$t_nb_item_competence[$compteur_competence]++;
								$compteur_item++;
							}
						}
						$compteur_competence++;
					}
				}
				$compteur_domaine++;
			}
		}
		
		// consolidation
		// somme des poids pour les domaines
		for ($i=0; $i<count($t_domaine); $i++){
			$t_domaine_coeff[$i]=0.0;
		}
		for ($i=0; $i<count($t_item_poids); $i++){
			if (($t_item_poids[$i]) && ($t_item_empreinte[$i])){
				// $t_domaine_coeff[$t_item_domaine[$i]]+= ((float)$t_item_poids[$i] / (float)$t_item_empreinte[$i]);
				$t_domaine_coeff[$t_item_domaine[$i]]+= (float)$t_item_poids[$i];
			}
		}
		
		// somme des poids pour les competences
		for ($i=0; $i<count($t_competence); $i++){
			$t_competence_coeff[$i]=0.0;
		}
		for ($i=0; $i<count($t_item_poids); $i++){
			if (($t_item_poids[$i]>0.0) && ($t_item_empreinte[$i]>0)){
				// $t_competence_coeff[$t_item_competence[$i]]+= ((float)$t_item_poids[$i] / (float)$t_item_empreinte[$i]);
				$t_competence_coeff[$t_item_competence[$i]]+= (float)$t_item_poids[$i];
			}
		}
		
		// coefficient poids / empreinte pour les items
		for ($i=0; $i<count($t_competence); $i++){
			$t_item_coeff[$i]=0.0;
		}
		for ($i=0; $i<count($t_item_poids); $i++){
			if (($t_item_poids[$i]) && ($t_item_empreinte[$i])){
				$t_item_coeff[$i] = ((float)$t_item_poids[$i] / (float)$t_item_empreinte[$i]);
			}
		}
	}
	return ($compteur_item>0);
}


// ----------------
function referentiel_affiche_tableau_1d_old($tab_1d){
// DEBUG
	if ($tab_1d){
		echo '<table border="1"><tr>'."\n";
		for ($i=0;$i<count($tab_1d); $i++){
			echo '<td>'.$tab_1d[$i].'</td>'."\n";
		}
		echo '</tr></table>'."\n";
	}
}

// ----------------
function referentiel_affiche_tableau_1d($tab_1d){
// DEBUG
	if ($tab_1d){
		echo '<table border="1"><tr>'."\n";
		foreach ($tab_1d as $val){
			echo '<td>'.$val.'</td>'."\n";
		}
		echo '</tr></table>'."\n";
	}
}

// ----------------
function referentiel_affiche_tableau($tab_1d){
// DEBUG
	if ($tab_1d){
		echo '<table border="1"><tr>'."\n";
		foreach ($tab_1d as $val){
			echo '<td>'.$val.'</td>'."\n";
		}
		echo '</tr></table>'."\n";
	}
}

// ------------------------------
function referentiel_affiche_data_referentiel($referentiel_referentiel_id, $params=NULL){
// 
global $OK_REFERENTIEL_DATA;
global $t_domaine;
global $t_domaine_coeff;
		
// COMPETENCES
global $t_competence;
global $t_competence_coeff;
		
// ITEMS
global $t_item_code;
global $t_item_description_competence;
global $t_item_coeff; // poids / empreinte
global $t_item_domaine; // index du domaine associÃ© Ã  un item 
global $t_item_competence; // index de la competence associÃ©e Ã  un item 
	if (!isset($OK_REFERENTIEL_DATA) || ($OK_REFERENTIEL_DATA==false)){
		$OK_REFERENTIEL_DATA=referentiel_initialise_data_referentiel($referentiel_referentiel_id);
	}
	if (isset($OK_REFERENTIEL_DATA) && ($OK_REFERENTIEL_DATA==true)){
		$label_d="";
		$label_c="";
		$label_i="";
		if (isset($params) && !empty($params)){
			if (isset($params->domainlabel)){
				$label_d=$params->domainlabel;
			}
			if (isset($params->skilllabel)){
				$label_c=$params->skilllabel;
			}
			if (isset($params->itemlabel)){
				$label_i=$params->itemlabel;
			}
		}
		
		// affichage
		// DOMAINES
		echo "<br />DOMAINES<br />\n";
		if (!empty($label_d)){
			p($label_d);
		}
		else {
			p(get_string('domaine','referentiel'));
		}
		
		echo '<br />'."\n";
		referentiel_affiche_tableau_1d($t_domaine);
		echo "<br />DOMAINES COEFF\n";
		referentiel_affiche_tableau_1d($t_domaine_coeff);
		
		echo "<br />COMPETENCES\n";
		if (!empty($label_c)){
			p($label_c);
		}
		else {
			p(get_string('competence','referentiel')) ;
		}
		echo '<br />'."\n";
		referentiel_affiche_tableau_1d($t_competence);
		echo "<br />COMPETENCES COEFF\n";
		referentiel_affiche_tableau_1d($t_competence_coeff);
		
		// ITEMS
		echo "<br />ITEMS\n";
		if (!empty($label_i)){
			p($label_i);
		}
		else {
			p(get_string('item_competence','referentiel')) ;
		}
		
		echo '<br />'."\n";
		echo "<br />CODES ITEM\n";
		referentiel_affiche_tableau_1d($t_item_code);
		echo "<br />DESCRIPTION ITEM\n";
		referentiel_affiche_tableau($t_item_description_competence);
		echo "<br />COMPETENCES COEFF\n";
		referentiel_affiche_tableau_1d($t_item_coeff);
		
		echo "<br />POIDS ITEM\n";
		referentiel_affiche_tableau_1d($t_item_poids);
		echo "<br />EMPREINTES ITEM\n";
		referentiel_affiche_tableau_1d($t_item_empreinte);
	}
}


// COURSE USERS
/**
 * This function returns records list of students from course
 *
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_all_users_course_by_role($courseid, $userid=0, $roleid=0){
global $CFG;

	if (! $course = get_record('course', 'id', $courseid)) {
		print_error("Course ID is incorrect");
	}
	if (! $context = get_context_instance(CONTEXT_COURSE, $course->id)) {
		print_error("Context ID is incorrect");
	}
    // we are looking for all users with this role assigned in this context or higher
    if ($usercontexts = get_parent_contexts($context)) {
        $listofcontexts = '('.implode(',', $usercontexts).')';
    } else {
        $listofcontexts = '('.$sitecontext->id.')'; // must be site
    }

	$rq="SELECT distinct u.id as userid
FROM {$CFG->prefix}user u
	LEFT OUTER JOIN {$CFG->prefix}context ctx
    	ON (u.id=ctx.instanceid AND ctx.contextlevel = ".CONTEXT_USER.")
    JOIN {$CFG->prefix}role_assignments r
    	ON u.id=r.userid
    LEFT OUTER JOIN {$CFG->prefix}user_lastaccess ul
    	ON (r.userid=ul.userid and ul.courseid = $course->id) 
WHERE (r.contextid = $context->id OR r.contextid in $listofcontexts)
	AND u.deleted = 0 
    AND (ul.courseid = $course->id OR ul.courseid IS NULL)
    AND u.username != 'guest'
    AND r.hidden = 0  ";

	if ($roleid){
		$rq.=" AND r.roleid = $roleid ";
	}	
	if ($userid){
		$rq.=" AND u.id = $userid ";
	}	
	$rq.= " ORDER BY u.lastname ".$order_order;
	return get_records_sql($rq); 
}


/**
 * This function returns records list of teachers from course
 *
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_teachers_course($courseid){
// This function returns records list of teachers from course
global $CFG;
    $teachersids=array();
	if (! $course = get_record('course', 'id', $courseid)) {
		print_error("Course ID is incorrect");
	}
	if (! $context = get_context_instance(CONTEXT_COURSE, $course->id)) {
		print_error("Context ID is incorrect");
	}
    // enseignants avec droits d'édition
    $role = get_record('role', 'shortname', 'editingteacher');
    $users= get_role_users($role->id, $context);
    if ($users){
        foreach($users as $user){
            $teachersids[]->userid=$user->id;
        }
    }
    // enseignant tuteurs
    $role = get_record('role', 'shortname', 'teacher');
    $users= get_role_users($role->id, $context);
    if ($users){
        foreach($users as $user){
            $teachersids[]->userid=$user->id;
        }
    }
    return $teachersids;
  }

/**
 * This function returns records list of teachers from course
 *
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_teachers_course_old($courseid, $userid=0, $roleid=0, $roleidexclude=NULL){
// This function returns records list of students from course
global $CFG;
	if (! $course = get_record('course', 'id', $courseid)) {
		print_error("Course ID is incorrect");
	}
	if (! $context = get_context_instance(CONTEXT_COURSE, $course->id)) {
		print_error("Context ID is incorrect");
	}
	
  // we are looking for all users with this role assigned in this context or higher
  if ($usercontexts = get_parent_contexts($context)) {
        $listofcontexts = '('.implode(',', $usercontexts).')';
  } else {
        $listofcontexts = '('.$sitecontext->id.')'; // must be site
  }

	/*
	  1 Administrator admin Administrators can usually do anything on the site... 0 
      2 Course creator coursecreator Course creators can create new courses and teach i... 1 
      3 Teacher editingteacher Teachers can do anything within a course, includin... 2 
      4 Non-editing teacher teacher Non-editing teachers can teach in courses and grad... 3 
      5 Student student Students generally have fewer privileges within a ... 4 
      6 Guest guest Guests have minimal privileges and usually can not... 5 
      7 Authenticated user user All logged in users. 
	*/

	if (!empty($roleidexclude)&& is_array($roleidexclude)){
      $listofexclude = '('.implode(',', $roleidexclude).')';
	}
	else if (!empty($roleidexclude) && is_string($roleidexclude)){
    $listofexclude = '('.$roleidexclude.')';  
  } 
  else if (!empty($roleidexclude) && is_integer($roleidexclude)){
    $listofexclude = '('.$roleidexclude.')';;
  } 
	else{
    $listofexclude = '';
  }
	
// DEBUG
//
// echo "<br />DEBUG :: lib.php 7033 :: LISTOFEXCLUDE : $listofexclude \n";
// echo "<br />\n";
// print_r($roleidexclude);
	
	$rq="SELECT distinct u.id as userid FROM {$CFG->prefix}user u ";

	$rq.= " LEFT OUTER JOIN {$CFG->prefix}context ctx
    	ON (u.id=ctx.instanceid AND ctx.contextlevel = ".CONTEXT_USER.")
    JOIN {$CFG->prefix}role_assignments r
    	ON u.id=r.userid
    LEFT OUTER JOIN {$CFG->prefix}user_lastaccess ul
    	ON (r.userid=ul.userid and ul.courseid = $course->id) 
  WHERE ((r.contextid = $context->id) 
	OR (r.contextid in $listofcontexts))
	AND u.deleted = 0 
  AND (ul.courseid = $course->id OR ul.courseid IS NULL)
  AND u.username != 'guest'
	AND r.roleid IN (1,2,3,4)
  AND r.hidden = 0  ";

	if ($roleid){
		$rq.=" AND r.roleid = ".$roleid." ";
	}	
	if (!empty($listofexclude)){
		$rq.=" AND r.roleid NOT IN ".$listofexclude." ";
	}	
	
	if ($userid){
		$rq.=" AND u.id = ".$userid." ";
	}	
	
	// DEBUG
	// echo "<br>DEBUG :: lib.php :: 4048 :: SQL&gt;".$rq."\n";
	// exit;
	return get_records_sql($rq); 
}


/**
 * This function returns records list of teachers from course
 *
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_students_course_new($courseid, $userid=0, $roleid=0, $quiet=false){
// This function returns records list of students from course
// BUG : IL EN MANQUE !
global $CFG;
    $studentsids=array();
	if (! $course = get_record('course', 'id', $courseid)) {
		if (!$quiet) print_error("Course ID is incorrect");
		else return false;
	}
	if (! $context = get_context_instance(CONTEXT_COURSE, $course->id)) {
		if (!$quiet) print_error("Context ID is incorrect");
		else return false;
	}
    // student

    if ($roleid){
        $users= get_role_users($roleid, $context);
    }
    else{
        $role = get_record('role', 'shortname', 'student');
        $users= get_role_users($role->id, $context);
    }
    if ($users){
        foreach($users as $user){
            if ($userid){
                if ($userid==$user->id){
                    $studentsids[]->userid=$user->id;
                }
            }
            else{
                $studentsids[]->userid=$user->id;
            }
        }
    }
    return $studentsids;
  }

/**
 * This function returns records list of teachers from course
 *
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_students_course($courseid, $userid=0, $roleid=0, $quiet=false){
// This function returns records list of students from course
global $CFG;
	if (! $course = get_record('course', 'id', $courseid)) {
		if (!$quiet) print_error("Course ID is incorrect");
		else return false;
	}
	if (! $context = get_context_instance(CONTEXT_COURSE, $course->id)) {
		if (!$quiet) print_error("Context ID is incorrect");
		else return false;
	}
	
    // we are looking for all users with this role assigned in this context or higher
    if ($usercontexts = get_parent_contexts($context)) {
        $listofcontexts = '('.implode(',', $usercontexts).')';
    } else {
        $listofcontexts = '('.$sitecontext->id.')'; // must be site
    }
	
	/*
	  1 Administrator admin Administrators can usually do anything on the site... 0 
      2 Course creator coursecreator Course creators can create new courses and teach i... 1 
      3 Teacher editingteacher Teachers can do anything within a course, includin... 2 
      4 Non-editing teacher teacher Non-editing teachers can teach in courses and grad... 3 
      5 Student student Students generally have fewer privileges within a ... 4 
      6 Guest guest Guests have minimal privileges and usually can not... 5 
      7 Authenticated user user All logged in users. 
	*/
	$rq="SELECT distinct u.id as userid FROM {$CFG->prefix}user u ";

	$rq.= " LEFT OUTER JOIN {$CFG->prefix}context ctx
    	ON (u.id=ctx.instanceid AND ctx.contextlevel = ".CONTEXT_USER.")
    JOIN {$CFG->prefix}role_assignments r
    	ON u.id=r.userid
    LEFT OUTER JOIN {$CFG->prefix}user_lastaccess ul
    	ON (r.userid=ul.userid and ul.courseid = $course->id) 
WHERE ((r.contextid = $context->id) 
	OR (r.contextid in $listofcontexts))
	AND 
	u.deleted = 0 
    AND (ul.courseid = $course->id OR ul.courseid IS NULL)
    AND u.username != 'guest'
	AND r.roleid NOT IN (1,2,3,4)
    AND r.hidden = 0  ";

	if ($roleid){
		$rq.=" AND r.roleid = ".$roleid." ";
	}	
	if ($userid){
		$rq.=" AND u.id = ".$userid." ";
	}	
	
	// DEBUG
	// echo "<br>DEBUG :: lib.php :: 4048 :: SQL&gt;".$rq."\n";
	// exit;
	return get_records_sql($rq); 
}


/**
 * This function get all user role in current course
 *
 * @param courseid reference course id
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_course_users($referentiel_instance){
global $CFG;
    if ($cm = get_coursemodule_from_instance('referentiel', $referentiel_instance->id, $referentiel_instance->course)) {
		// SQL
	    $rq = "SELECT DISTINCT u.id FROM {$CFG->prefix}user u 
LEFT OUTER JOIN
    {$CFG->prefix}user_lastaccess ul on (ul.courseid = ".$referentiel_instance->course.")
	WHERE u.deleted = 0  
        AND (ul.courseid = ".$referentiel_instance->course." OR ul.courseid IS NULL)
        AND u.username != 'guest' ";
		// DEBUG
			// echo "<br /> DEBUG <br />\n";
			// echo "<br /> lib.php :: referentiel_get_course_users() :: 1986<br />$select<br />\n";
		$ru=get_records_sql($rq);
			// print_r($ru);
			// exit;
		return $ru;
	}
	return NULL;
}

/**
 * This function return course link
 *
 * @param courseid reference course id
 * @return string
 * @todo Finish documenting this function
 **/
function referentiel_get_course_link($courseid, $complet=false){
global $CFG;
	if ($courseid){
		$that_course=get_record("course", "id", $courseid);
		if ($that_course){
            if ($complet){
    			return '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$that_course->id.'" target="_blank">'.$that_course->shortname.'</a> ';
            }
			else{
                return '<a href="../../course/view.php?id='.$that_course->id.'">'.$that_course->shortname.'</a> ';
            }
		}
	}
	return '';
}



// CERTIFICATS

function referentiel_genere_competences_declarees_vide($liste_competences){
//
// retourne une liste de la forme 
// input :: A.1.1:0/A.1.2:0/A.1.3:0/A.1.4:0/A.1.5:0/A.2.1:0 ...
// output A.1.1:0/A.1.2:0/A.1.3:0/A.1.4:0/A.1.5:0/A.2.1:0 ...
	// collecter les competences
	$jauge_competences_declarees='';
	$tcomp=explode("/", $liste_competences);
	while (list($key, $val) = each($tcomp)) {
		// echo "$key => $val\n";
		if ($val!=""){
			$jauge_competences_declarees.=$val.":0/";
		}
	}
	return $jauge_competences_declarees;
}

/**
 * This function get all competencies declared in activities and return a competencies list
 *
 * @param userid reference user id
 * @param $referentielid reference a referentiel id (not an instance of it !)
 * @return bolean
 * @todo Finish documenting this function
 * algorithme : cumule pour chaque competences le nombre d'activitÃ©s oÃ¹ celle ci est validee
 **/
function referentiel_genere_certificate_liste_competences_declarees($userid, $referentielid){
	$t_liste_competences_declarees=array();
	$t_competences_declarees=array();
	$t_competences_referentiel=array(); // les competences du rÃ©fÃ©rentiel
	
	$liste_competences_declarees=""; // la liste sous forme de string
	$jauge_competences_declarees=""; // la juge sous forme CODE_COMP_0:n0/CODE_COMP_1:n1/...
	// avec 0 si competence declaree 0 fois, n>0 sinon
	
	if (isset($userid) && ($userid>0) && isset($referentielid) && ($referentielid>0)){
		// liste des competences definies dans le referentiel
		$liste_competences_referentiel=referentiel_purge_dernier_separateur(referentiel_get_liste_codes_competence($referentielid), "/");
		// DEBUG
		// echo "<br />DEBUG :: Ligne 2706 :: USERID : $userid :: REFERENTIEL : $referentielid\n";
		
		$t_competences_referentiel=explode("/", $liste_competences_referentiel);
		// creer un tableau dont les indices sont les codes de competence
		while (list($key, $val) = each($t_competences_referentiel)) {    
			$t_competences_declarees[$val]=0;
		}
		// collecter les activites validees
		$select=" AND userid=".$userid." ";
		$order= ' id ASC ';
		$records_activite = referentiel_get_activites($referentielid, $select, $order);
		if (!$records_activite){
			return referentiel_genere_competences_declarees_vide($liste_competences_referentiel);
		}
		// DEBUG
		// echo "<br />Debug :: lib.php :: Ligne 2721 \n";
		// print_r($records_activite);
		
		// collecter les competences
		foreach ($records_activite  as $activite){
			$t_liste_competences_declarees[]=referentiel_purge_dernier_separateur($activite->comptencies, "/");
		}
 		for ($i=0; $i<count($t_liste_competences_declarees); $i++){
			$tcomp=explode("/", $t_liste_competences_declarees[$i]);
			while (list($key, $val) = each($tcomp)) {    
				// echo "$key => $val\n";
				if (isset($t_competences_declarees[$val])) $t_competences_declarees[$val]++;
			}
		}
		$i=0;
		while (list($key, $val) = each($t_competences_declarees)) {    
			// echo "$key => $val\n";
			if ((!is_numeric($key) && ($key!=""))  && ($val!="") && ($val>0)){
				$liste_competences_declarees.=$key."/";
			}
			$jauge_competences_declarees.=$key.":".trim($val)."/";
		}
	}
	// DEBUG
	// echo "<br />DEBUG :: Ligne lib.php :: 4055 :: $jauge_competences_declarees\n";

	return $jauge_competences_declarees; 
}



/**
 * This function get all valid competencies in activite and return a competencies list
 *
 * @param userid reference user id
 * @param $referentielid reference a referentiel id (not an instance of it !)
 * @return bolean
 * @todo Finish documenting this function
 * algorithme : cumule pour chaque competences le nombre d'activitÃ©s oÃ¹ celle ci est validee
 **/
function referentiel_genere_certificate_liste_competences($userid, $referentielid){
	$t_liste_competences_valides=array();
	$t_competences_valides=array();
	$t_competences_referentiel=array(); // les competences du rÃ©fÃ©rentiel
	
	$liste_competences_valides=""; // la liste sous forme de string
	$jauge_competences=""; // la juge sous forme CODE_COMP_0:n0/CODE_COMP_1:n1/...
	// avec 0 si competence valide 0 fois, n>0 sinon
	
	if (isset($userid) && ($userid>0) && isset($referentielid) && ($referentielid>0)){
		// liste des competences definies dans le referentiel
		$liste_competences_referentiel=referentiel_purge_dernier_separateur(referentiel_get_liste_codes_competence($referentielid), "/");
		// DEBUG
		// echo "<br />DEBUG :: lib.php :: Ligne 7275 ::<br />USERID : $userid :: REFERENTIEL : $referentielid<br>$liste_competences_referentiel\n";
		
		$t_competences_referentiel=explode("/", $liste_competences_referentiel);
		// creer un tableau dont les indices sont les codes de competence
		while (list($key, $val) = each($t_competences_referentiel)) {    
			$t_competences_valides[$val]=0;
		}
		// collecter les activites validees
		$select=" AND approved!=0 AND userid=".$userid." ";
		$order= ' id ASC ';
		$records_activite = referentiel_get_activites($referentielid, $select, $order);
		if ($records_activite){
			// DEBUG
			// echo "<br />Debug :: lib.php :: Ligne 7288<br />COMPETENCES REFERENTIEL VALIDES AVANT :<br />\n";
			// print_r($t_competences_valides);
			
      // echo "<br />Debug :: lib.php :: Ligne 7291 :<br />ACTIVIE<br />\n";
			// print_r($records_activite);
			
			// collecter les competences
			foreach ($records_activite  as $activite){
				$t_liste_competences_valides[]=referentiel_purge_dernier_separateur($activite->comptencies, "/");
  			// DEBUG
	   		// echo "<br />Debug :: lib.php :: Ligne 7298<br />COMPETENCES ACTIVITE :<br />".$activite->comptencies."\n";
			}

			// print_r($t_liste_competences_valides);
			// exit;
 			
      for ($i=0; $i<count($t_liste_competences_valides); $i++){
				if ($t_liste_competences_valides[$i]){
          $tcomp=explode("/", $t_liste_competences_valides[$i]);
				  while (list($key, $val) = each($tcomp)) {    
					 // echo "$key => $val\n";
					 // if (isset($t_competences_valides[$val])) 
            $t_competences_valides[$val]++;
				  }
				}
			}
		}
		
		$i=0;
		while (list($key, $val) = each($t_competences_valides)) {    
			// echo "$key => $val\n";
			if ((!is_numeric($key) && ($key!=""))  && ($val!="") && ($val>0)){
				$liste_competences_valides.=$key."/";
			}
			$jauge_competences.=$key.":".trim($val)."/";
		}
	}
	
	// DEBUG
	// echo "<br />DEBUG :: Ligne 4123 :: $jauge_competences\n";

	return $jauge_competences; 
}


/**
 * This function returns record certificate from table referentiel_certificate
 *
 * @param userid reference user id of certificat
 * @param referentielid reference referentiel id of certificate 
 * @return object
 * @todo Finish documenting this function
 **/
function referentiel_get_certificate_user($userid, $referentielid){
global $CFG;
	if (isset($userid) && ($userid>0) && isset($referentielid) && ($referentielid>0)){
		return get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_certificate WHERE referentielid='.$referentielid.' AND userid='.$userid.' ');
	}
	else {
		return false; 
	}
}

/**
 * This function returns record certificate from table referentiel_certificate
 *
 * @param userid reference user id of certificat
 * @param referentielid reference referentiel id of certificate 
 * @return object
 * @todo Finish documenting this function
 **/
 /*
function referentiel_get_certificate_user($userid, $referentielid){
global $CFG;
	if (isset($userid) && ($userid>0) && isset($referentielid) && ($referentielid>0)){
		$r=get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_certificate WHERE referentielid='.$referentielid.' AND userid='.$userid.' ');
		if (!$r || (($r->competences_certificat!='') && ($r->comptencies==''))){
			$certificate_id=referentiel_genere_certificat($userid, $referentielid);
			if ($certificate_id){
				return referentiel_get_certificat($certificate_id);
			}
			else{
				return false;
			}
		}
		else{
			return $r;
		}
	}
	else {
		return false; 
	}
}
*/

/**
 * This function  create / update with valid competencies a certificate for the userid
 *
 * @param userid reference user id
 * @param $referentielid reference a referentiel id (not an instance of it !)
 * @return bolean
 * @todo Finish documenting this function
 **/
function referentiel_genere_certificat($userid, $referentielid){
	$certificate_id=0; // id du certificate cree / modifie
	if (isset($userid) && ($userid>0) && isset($referentielid) && ($referentielid>0)){
		// MODIF JF 28/11/2009
		$comptencies=referentiel_genere_certificate_liste_competences_declarees($userid, $referentielid);
		$competences_certificat=referentiel_genere_certificate_liste_competences($userid, $referentielid);
		// DEBUG
		// echo "<br />DEBUG :: lib.php :: LIGNE 4194 :: $comptencies\n";
		// echo "<br />DEBUG :: lib.php :: LIGNE 4195 :: $competences_certificat\n";
		
		if (
			($competences_certificat!="")
			||
			($comptencies!="")
			){
			// si existe update
			if ($certificat=referentiel_get_certificate_user($userid, $referentielid)){
				$certificate_id=$certificat->id;
				
				// update ?
				
				if ($certificat->verrou==0){
					$certificat->comment=addslashes($certificat->comment);
	                $certificat->synthese_certificat=addslashes($certificat->synthese_certificat);
					$certificat->decision_jury=addslashes($certificat->decision_jury);
					$certificat->evaluation=addslashes($certificat->evaluation);
					$certificat->competences_certificat=$competences_certificat;
					$certificat->comptencies=$comptencies;
					$certificat->evaluation=referentiel_evaluation($competences_certificat, $referentielid);
					$certificat->valide=1;					
					if(!update_record("referentiel_certificate", $certificat)){
						// DEBUG 
						// echo "<br /> ERREUR UPDATE CERTIFICAT\n";
					}
				}
			}
			else {
				// sinon creer
				$certificate = new object();
				$certificat->competences_certificat=$competences_certificat;
				$certificat->comptencies=$comptencies;
				$certificat->comment="";
                $certificat->synthese_certificat="";
				$certificat->decision_jury="";
				$certificat->date_decision=0;
				$certificat->referentielid=$referentielid;
				$certificat->userid=$userid;	
				$certificat->teacherid=0;
				$certificat->verrou=0;
				$certificat->valide=1;
				$certificat->evaluation=referentiel_evaluation($competences_certificat, $referentielid);
    			// DEBUG
				// print_object($certificat);
    			// echo "<br />";
				$certificate_id= insert_record("referentiel_certificate", $certificat);
			}
		}
	}
	return $certificate_id;
}

/**
 * This function modify referentiel_certificate list of competencies 
 *
 * @param liste_competences 'A.1.1/A.1.3/A.2.3/'
 * @param userid reference user id
 * @param referentiel_id reference referentiel
 * @return string certificate_jauge
 * A.1.1:0/A.1.2:1/A.1.3:2/A.1.4:0/A.1.5:0/A.2.1:1/A.2.2:1/A.2.3:1/A.3.1:0/A.3.2:0/A.3.3:0/A.3.4:0/B.1.1:0/B.1.2:0/B.1.3:0/B.2.1:0/B.2.2:0/B.2.3:1/B.2.4:0/B.3.1:0/B.3.2:0/B.3.3:0/B.3.4:0/B.3.5:0/B.4.1:1/B.4.2:1/B.4.3:0/
 * @todo Finish documenting this function
 **/
function referentiel_mise_a_jour_competences_certificate_user($liste_competences_moins, $liste_competences_plus, $userid, $referentiel_id, $approved, $modif_declaration=true, $modif_validation=false ){
// 	la liste sous forme de string
//  IN#1  : 'A.1.1/A.1.3/A.2.3/' 
//  IN#2  : '      A.1.3/A.2.3/A.3.1'

// 	la jauge sous forme CODE_COMP_0:n0/CODE_COMP_1:n1/...
//  avec 0 si competence valide 0 fois, n>0 sinon
//  GET  : 'A.1.1:1/A.1.2:1/A.1.3:2/A.1.4:0/A.1.5:0/A.2.1:1/A.2.2:1/A.2.3:1/A.3.1:0/A.3.2:0/A.3.3:0/A.3.4:0/B.1.1:0/B.1.2:0/B.1.3:0/B.2.1:0/B.2.2:0/B.2.3:1/B.2.4:0/B.3.1:0/B.3.2:0/B.3.3:0/B.3.4:0/B.3.5:0/B.4.1:1/B.4.2:1/B.4.3:0/'
//  la jauge sous forme CODE_COMP_0:n0/CODE_COMP_1:n1/...
//  PUT  : 'A.1.1:0/A.1.2:1/A.1.3:2/A.1.4:0/A.1.5:0/A.2.1:1/A.2.2:1/A.2.3:1/A.3.1:1/A.3.2:0/A.3.3:0/A.3.4:0/B.1.1:0/B.1.2:0/B.1.3:0/B.2.1:0/B.2.2:0/B.2.3:1/B.2.4:0/B.3.1:0/B.3.2:0/B.3.3:0/B.3.4:0/B.3.5:0/B.4.1:1/B.4.2:1/B.4.3:0/'	
//                -               =                                       =       +
	$debug=false;
	$certificate_id=0;
	
	// Competences validees
	$liste_competences_valides='';
	$jauge_competences='';	

	$t_competences_jauge=array();
	$t_competences_supprimees=array(); // les competences Ã  supprimer de la liste
	$t_competences_valides=array(); // les competences du certificate validees
	
	// Competences declarees
	$liste_jauge_declarees=''; // competences declarees dans les activites
	$t_competences_jauge_declarees=array();
	$t_competences_declarees=array(); // les competences du certificate declarees
	$jauge_competences_declarees='';
	
	// outils
	$t_jauge= array();
	$tcomp= array();
	
	// preparation
	// competences a supprimer 
	if ($liste_competences_moins!=''){
		// DEBUG
		if ($debug) echo "<br />COMPETENCES MOINS<br />\n";
		$liste_competences_moins=referentiel_purge_dernier_separateur($liste_competences_moins, "/");
	}
	// competences a ajouter 
	if ($liste_competences_plus!=''){
		if ($debug) echo "<br />COMPETENCES PLUS<br />\n";			
		$liste_competences_plus=referentiel_purge_dernier_separateur($liste_competences_plus, "/");
	}
	
	// DEBUG
	if ($debug) echo "<br />DEBUG :: lib.php :: Ligne 4346 :: USERID : $userid :: REFERENTIEL : $referentiel_id<br />LISTE MOINS : $liste_competences_moins <br />LISTE PLUS : $liste_competences_plus<br />\n";
	
	if (!referentiel_certificate_user_exists($userid, $referentiel_id)){
		// CREER ce certificat
		referentiel_genere_certificat($userid, $referentiel_id);
	}
	
	$certificat=referentiel_get_certificate_user($userid, $referentiel_id);
	
	if ($certificat){
		$certificate_id=$certificat->id;
		// DEBUG
		if ($debug) {
			echo "<br />DEBUG : lib.php :: Ligne 4315 :: CERTIFICAT<br /> ";
			print_object($certificat);
    		echo "<br />";
		}
		
		// Competences declarees
		if (!$modif_declaration){ // une validation ou une devalidation d'activite sans ajout ni suppression des competences
			$jauge_competences_declarees=$certificat->comptencies; // Pas de changement
		}
		else{
			// mise Ã  jour des competences declarees
			$liste_competences_declarees=$certificat->comptencies;
			if ($liste_competences_declarees!=''){
				$liste_competences_declarees=referentiel_purge_dernier_separateur($liste_competences_declarees, "/");
				// DEBUG
				//echo "<br />DEBUG :: lib.pho :: 4326 :: JAUGE GET : $liste_competences_declarees<br />\n";
				$t_competences_jauge_declarees=explode("/", $liste_competences_declarees); // [A.1.1:0]  [A.1.2:1] [A.1.3:2] [A.1.4:0] ...
				//echo "<br />TABLEAU JAUGE GET :<br />\n";
				//print_r($t_competences_jauge_activite);
				//echo "<br />\n";
				
				// creer et initialise un tableau dont les indices sont les codes de competence
				// echo "<br />JAUGE GET<br />\n";
				while (list($key, $val) = each($t_competences_jauge_declarees)) {
					//echo "$key => $val\n";
					$t_jauge=explode(':',$val);
					$t_competences_declarees[$t_jauge[0]]=$t_jauge[1]; // // [A.1.1]=0  [A.1.2]=1 [A.1.3]=2 [A.1.4]=0 
				}
				if ($debug) {
					echo "<br />TABLEAU COMPETENCES DECLAREEES AVANT SUPPRESSION :<br />\n";
					print_r($t_competences_declarees);
					echo "<br />\n";
				}
				
				// supprimer des competences 
				if ($liste_competences_moins!=''){
					$tcomp0=explode("/", $liste_competences_moins);
					while (list($key0, $val0) = each($tcomp0)) {    
						// echo "<br />$key0 => $val0\n";
						if ($t_competences_declarees[$val0]>0){
							$t_competences_declarees[$val0]--;
						}
					}
				}
				if ($debug) {
					echo "<br />TABLEAU COMPETENCES DECLAREEES APRES SUPPRESSION :<br />\n";
					print_r($t_competences_declarees);
				}
				// Ajouter des competences 
				if ($liste_competences_plus!=''){
					$tcomp1=explode("/", $liste_competences_plus);
					while (list($key1, $val1) = each($tcomp1)) {    
						//echo "<br />$key1 => $val1\n";
						$t_competences_declarees[$val1]++;
					}
				}
				if ($debug) {
					echo "<br />TABLEAU COMPETENCES DECLAREEES APRES AJOUT :<br />\n";
					print_r($t_competences_declarees);
				}
				// reconstitution de la jauge des competences declarees
				
				while (list($key2, $val2) = each($t_competences_declarees)) {
					// echo "<br>$key2 => $val2\n";
					if ((!is_numeric($key2) && ($key2!=""))  && ($val2!="") && ($val2>0)){
						$liste_jauge_declarees.=$key2."/";
					}
					$jauge_competences_declarees.=$key2.":".trim($val2)."/";
				}
			}
		}
		
		if ($debug) {
			echo "<br /><br />COMPETENCES DECLAREEES :<br />$jauge_competences_declarees<br />\n";
		}
		
		// Competences validees
		if (($certificat->verrou!=0) || (!$modif_validation)) { // une mise a jour des competences sans validation ou devalidation
			$jauge_competences=$certificat->competences_certificat; // Pas de changement
		}
		else{
			// sinon modification de la liste des competences validees
			$liste_jauge_competences=$certificat->competences_certificat;
			$liste_jauge_competences=referentiel_purge_dernier_separateur($liste_jauge_competences, "/");
			//
			$t_competences_jauge=explode("/", $liste_jauge_competences); // [A.1.1:0]  [A.1.2:1] [A.1.3:2] [A.1.4:0] ...
			if ($debug) {
				echo "<br />JAUGE certificate : $liste_jauge_competences<br />\n";
				echo "<br />TABLEAU COMPETENCES certificate :<br />\n";
				print_r($t_competences_jauge);
				echo "<br />\n";
			}
			// creer et initialise un tableau dont les indices sont les codes de competence
			// echo "<br />JAUGE GET<br />\n";
			while (list($key, $val) = each($t_competences_jauge)) {
				// echo "$key => $val\n";
				$t_jauge=explode(':',$val);
				$t_competences_valides[$t_jauge[0]]=$t_jauge[1]; // // [A.1.1]=0  [A.1.2]=1 [A.1.3]=2 [A.1.4]=0 
			}
			if ($debug) {
				echo "<br />TABLEAU COMPETENCES VALIDES AVANT SUPPRESSION :<br />\n";
				print_r($t_competences_valides);
				// echo "<br />lib.php :: EXIT ligne 4457\n";
				// exit;
			}
			
			// competences a supprimer 
			if ($liste_competences_moins!=''){
				$tcomp=explode("/", $liste_competences_moins);
				while (list($key1, $val1) = each($tcomp)) {    
					// echo "<br />$key1 => $val1\n";
					if ($t_competences_valides[$val1]>0){
						$t_competences_valides[$val1]--;
					}
				}
			}
			
			if ($debug) {
				echo "<br />TABLEAU COMPETENCES VALIDES APRES SUPPRESSION :<br />\n";
				print_r($t_competences_valides);
			}
			
			// competences a ajouter 
			if ($approved){ // on ajoute si l'activite est validee
				if ($liste_competences_plus!=''){
					$tcomp=explode("/", $liste_competences_plus);
					while (list($key1, $val1) = each($tcomp)) {    
						//echo "<br />$key1 => $val1\n";
						$t_competences_valides[$val1]++;
					}
				}
				
				if ($debug) {
					echo "<br />TABLEAU COMPETENCES VALIDES APRES AJOUT :<br />\n";
					print_r($t_competences_valides);
				}
			}
			
			// reconstitution de la jauge
			while (list($key2, $val2) = each($t_competences_valides)) {
				// echo "<br>$key2 => $val2\n";
				if ((!is_numeric($key2) && ($key2!=""))  && ($val2!="") && ($val2>0)){
					$liste_competences_valides.=$key2."/";
				}
				$jauge_competences.=$key2.":".trim($val2)."/";
			}
		}	
		
		// DEBUG
		if ($debug) {
			echo "<br />DEBUG :: lib.php :: Ligne 4499 :: USERID : $userid :: REFERENTIEL : $referentiel_id<br />LISTE COMPETENCES : $liste_competences_valides<br />JAUGE : $jauge_competences\n";
		}

		// mise a jour
		$certificat->comment=addslashes($certificat->comment);
        $certificat->synthese_certificat=addslashes($certificat->synthese_certificat);
		$certificat->decision_jury=addslashes($certificat->decision_jury);
		$certificat->evaluation=addslashes($certificat->evaluation);
		$certificat->competences_certificat=$jauge_competences;
		$certificat->comptencies=$jauge_competences_declarees;
		$certificat->evaluation=referentiel_evaluation($certificat->competences_certificat, $referentiel_id);
		$certificat->valide=1;
		// DEBUG
		if ($debug) {
			echo "<br />DEBUG : lib.php :: Ligne 4519 <br /> ";
			print_object($certificat);
    		// echo "<br />lib.php :: EXIT LIGNE 4524";
			// exit;
		}
		
		if (!update_record("referentiel_certificate", $certificat)){
			// DEBUG 
			// echo "<br />DEBUG : lib_certificate :: Ligne 162  :: ERREUR UPDATE CERTIFICAT\n";
		}
	}
	return $certificate_id;
}


/**
 * This function returns records list of users from table referentiel_certificate
 *
 * @param userid reference user id
 * @param referentiel_id reference referentiel
 * @return object
 * @todo Finish documenting this function
 **/
function referentiel_certificate_user_exists($userid, $referentiel_id){
global $CFG;
	if (isset($userid) && ($userid>0) && isset($referentiel_id) && ($referentiel_id>0)){
		$r=get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_certificate WHERE referentielid='.$referentiel_id.' AND userid='.$userid.' ');
		if ($r){
			// echo "<br />\n";
			// print_r($r);
			// MODIF JF 2009/11/28
			// controler la completude du certificate post version 4.1.1
			if (($r->competences_certificat!='') || ($r->comptencies=='')){
				return 0;
			}
			else{
				return ($r->id);
			}
		}
	}
	return 0; 
}

/**
 * This function returns record certificate from table referentiel_certificate
 *
 * @param userid reference user id
 * @param referentiel_id reference referentiel
 * @return object
 * @todo Finish documenting this function
 **/
function referentiel_certificate_user($userid, $referentiel_id){
// Si certificate n'existe pas, cree le certificate et le retourne

	if (isset($userid) && ($userid>0) && isset($referentiel_id) && ($referentiel_id>0)){
		if (!referentiel_certificate_user_exists($userid, $referentiel_id)){
			if (referentiel_genere_certificat($userid, $referentiel_id)){
				return referentiel_get_certificate_user($userid, $referentiel_id);
			}
			else{
				return false;
			}
		}
		else{
			return referentiel_get_certificate_user($userid, $referentiel_id);
		}
	}
	else {
		return false; 
	}
}




/**
 * This function returns records list of users from table referentiel_certificate
 *
 * @param id reference certificat
 * @param select clause : ' AND champ=valeur,  ... '
 * @param order clause : ' champ ASC|DESC, ... ' 
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_users_certificats($referentiel_id, $select="", $order=""){
global $CFG;
	if (isset($referentiel_id) && ($referentiel_id>0)){
		if (empty($order)){
			$order= 'userid ASC ';
		}
		return get_records_sql('SELECT DISTINCT userid FROM '. $CFG->prefix . 'referentiel_certificate WHERE referentielid='.$referentiel_id.' '.$select.' ORDER BY '.$order.' ');
	}
	else 
		return 0; 
}


/**
 * This function returns records list of users from table referentiel_activity
 *
 * @param id reference certificat
 * @param select clause : ' AND champ=valeur,  ... '
 * @param order clause : ' champ ASC|DESC, ... ' 
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_users_referentiel_cours($referentiel_id, $course_id, $select="", $order=""){
global $CFG;
	if (isset($referentiel_id) && ($referentiel_id>0)){
		if (empty($order)){
			$order= 'userid ASC ';
		}
		return get_records_sql('SELECT DISTINCT userid FROM '. $CFG->prefix . 'referentiel_activity WHERE referentielid='.$referentiel_id.' AND course='.$course_id.' '.$select.' ORDER BY '.$order.' ');
	}
	else 
		return 0; 
}

/**
 * This function returns records list of teachers from table referentiel_certificate
 *
 * @param id reference certificat
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_teachers_certificats($referentiel_id){
global $CFG;
	if (isset($referentiel_id) && ($referentiel_id>0)){
		return get_records_sql('SELECT DISTINCT teacherid FROM '. $CFG->prefix . 'referentiel_certificate WHERE referentielid='.$referentiel_id.' ORDER BY teacherid ASC ');
	}
	else 
		return 0; 
}


/**
 * This function get a competencies list and return a float
 *
 * @param userid reference user id
 * @param $referentielid reference a referentiel id (not an instance of it !)
 * @return bolean
 * @todo Finish documenting this function
 **/
function referentiel_evaluation($listecompetences, $referentiel_id){
//A.1.1:0/A.1.2:0/A.1.3:0/A.1.4:0/A.1.5:0/A.2.1:0/A.2.2:0/A.2.3:0/A.3.1:0/A.3.2:0/A.3.3:0/A.3.4:0/B.1.1:0/B.1.2:0/B.1.3:0/B.2.1:0/B.2.2:0/B.2.3:0/B.2.4:0/B.3.1:0/B.3.2:0/B.3.3:0/B.3.4:0/B.3.5:0/B.4.1:1/B.4.2:1/B.4.3:0/
	// DEBUG
	// echo "<br />LISTE ".$listecompetences."\n";
	$evaluation=0.0;
	$tcode=array();
	$tcode=explode("/",$listecompetences);
	for ($i=0; $i<count($tcode); $i++){
		$tvaleur=explode(":",$tcode[$i]);
		
		$code="";
		$svaleur="";
		
		if (isset($tvaleur[0])){ // le code
			$code=trim($tvaleur[0]);
		}
		if (isset($tvaleur[1])){ // la valeur
			$svaleur=trim($tvaleur[1]);
		} 
		// DEBUG
		// echo "<br />DEBUG :: lib.php : 2260 :: CODE : ".$code." VALEUR : ".$svaleur."\n";
		if (($code!="") && ($svaleur!="")){ 
			$poids=referentiel_get_poids_item($code, $referentiel_id);
			$empreinte=referentiel_get_empreinte_item($code, $referentiel_id);
			// echo "<br />POIDS : ".$poids."\n";
			if ($empreinte)
				$evaluation+= ( $poids * $svaleur / $empreinte);
			else
				$evaluation+= ( $poids * $svaleur);
		}
	}
	// echo "<br />EVALUATION : ".$evaluation."\n";
	return $evaluation;
}


/**
 * This function set all certificates
 *
 * @param $referentiel_instance reference an instance of referentiel !)
 * @return bolean
 * @todo Finish documenting this function
 **/
function referentiel_regenere_certificats($referentiel_instance){
	if ($referentiel_instance){
		$records_users=referentiel_get_course_users($referentiel_instance);
		// echo "<br /> lib.php :: referentiel_get_course_users() :: 2018<br />\n";		
		if ($records_users){
			foreach ($records_users as $record_u){
				// echo "<br />DEBUG :: lib.php :: LIGNE 2948 \n";
				// print_r($record_u);
				referentiel_regenere_certificate_user($record_u->id, $referentiel_instance->referentielid);	
			}
		}
	}
}

/**
 * This function set all certificates
 *
 * @param $referentiel_instance reference an instance of referentiel !)
 * @return bolean
 * @todo Finish documenting this function
 **/
function referentiel_regenere_certificate_user($userid, $referentielid){
	if ($referentielid && $userid){
		if (!referentiel_certificate_user_exists($userid, $referentielid)){
			// CREER ce certificat
			referentiel_genere_certificat($userid, $referentielid);
		}
		if (!referentiel_certificate_user_valide($userid, $referentielid)){ 
		// drapeau positionne par l'ancienne version <= 3 quand une activite est validee ou devalidee
		// n'est plus utilise car desormais on modifie directement la jauge du certificate dans la partie activite
			// METTRE A JOUR ce certificat
			referentiel_genere_certificat($userid, $referentielid);
		}
	}
}


/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted referentiel record
 **/
function referentiel_add_certificat($form) {
// creation certificat
global $USER;
    // DEBUG
    //echo "DEBUG : ADD certificate CALLED";
	//print_object($form);
    //echo "<br />";
	// referentiel
	$certificate = new object();
	$certificat->competences_certificat=$form->competences_certificat;
	$certificat->comment=$form->comment;
	$certificat->synthese_certificat=$form->synthese_certificat;
	if (!empty($form->decision_jury)){
        $certificat->date_decision=time();
    }
    else{
        $certificat->date_decision='';
    }
    $certificat->decision_jury=($form->decision_jury);
	$certificat->date_decision='';
	$certificat->referentielid=$form->referentielid;
	$certificat->userid=$USER->id;	
	$certificat->teacherid=$USER->id;
	$certificat->verrou=0;
	$certificat->valide=$form->valide;
	$certificat->evaluation=referentiel_evaluation($form->competences_certificat, $form->referentielid);	


	$certificat->mailed=1; // MODIF JF 2010/10/05
	if (isset($form->mailnow)){
        $certificat->mailnow=$form->mailnow;
        if ($form->mailnow=='1'){ // renvoyer
            $certificat->mailed=0;   // annuler envoi precedent
        }
    }
    else{ 
        $certificat->mailnow=0;
    }	


    // DEBUG
	//print_object($certificat);
    //echo "<br />";
	
	$certificate_id= insert_record("referentiel_certificate", $certificat);
    // echo "certificate ID / $certificate_id<br />";
    // DEBUG
    return $certificate_id;
}

/**
 * Given an object containing all the necessary referentiel, 
 * (defined by the form in mod.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted referentiel record
 **/
function referentiel_update_certificat($form) {
// MAJ certificat
$ok=true;
    // DEBUG

    //echo "\nDEBUG : UPDATE certificate CALLED:: 8035\n";
	//print_object($form);
    //echo "<br />\n";
    //exit;

    // certificat
	if (isset($form->action) && ($form->action=="modifier_certificat")){
		$certificate = new object();
		$certificat->id=$form->certificate_id;
		$certificat->comment=$form->comment;
        $certificat->synthese_certificat=$form->synthese_certificat;
		$certificat->competences_certificat=$form->competences_certificat;

        if (!empty($form->decision_jury_sel) && empty($form->decision_jury)){
            $form->decision_jury=$form->decision_jury_sel;
        }
		if (isset($form->decision_jury_old) && ($form->decision_jury_old!=$form->decision_jury)){
	       	$certificat->date_decision=time();
        }
        else{
            $certificat->date_decision=$form->date_decision;
        }
        $certificat->decision_jury=$form->decision_jury;

		$certificat->referentielid=$form->referentielid;
		$certificat->userid=$form->userid;
		$certificat->teacherid=$form->teacherid;
		$certificat->verrou=$form->verrou;
		$certificat->valide=$form->valide;
		$certificat->evaluation=referentiel_evaluation($form->competences_certificat, $form->referentielid);	

		// MODIF JF 2010/02/11
		if (isset($form->mailnow)){
            $certificat->mailnow=$form->mailnow;
            if ($form->mailnow=='1'){ // renvoyer
                $certificat->mailed=0;   // annuler envoi precedent
            }
        }
        else{
            $certificat->mailnow=0;
        }
		
	    // DEBUG
		// print_object($certificat);
	    // echo "<br />";
		if(!update_record("referentiel_certificate", $certificat)){
			//echo "<br /> ERREUR UPDATE CERTIFICAT\n";
			$ok=false;
		}
		else {
			// echo "<br /> UPDATE certificate $certificat->id\n";		
			$ok=true;
		}
		// exit;
		return $ok; 
	}
}

function referentiel_user_can_add_certificat($referentiel, $currentgroup, $groupmode) {
    global $USER;

    if (!$cm = get_coursemodule_from_instance('referentiel', $referentiel->id, $referentiel->course)) {
        print_error('Course Module ID was incorrect');
    }
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    if (!has_capability('mod/referentiel:writecertificat', $context)) {
        return false;
    }

    if (!$groupmode or has_capability('moodle/site:accessallgroups', $context)) {
        return true;
    }

    if ($currentgroup) {
        return ismember($currentgroup);
    } else {
        //else it might be group 0 in visible mode
        if ($groupmode == VISIBLEGROUPS){
            return true;
        } else {
            return false;
        }
    }
}


function referentiel_certificate_isowner($id){
global $USER;
	if (isset($id) && ($id>0)){
		$record=get_record("referentiel_certificate", "id", "$id");
		// DEBUG
		// echo "<br >USERID : $USER->id ; OWNER : $record->userid\n";
		return ($USER->id == $record->userid);
	}
	else 
		return false; 
} 


/**
 * Given a userid  and referentiel id
 * this function will set certificate valide to 0
 *
 * @param $userid user id
  * @param $referentielid refrentiel id
 * @return 
 **/

function referentiel_certificate_user_invalider($userid, $referentielid){
	if ($userid && $referentielid){
		$certificate = get_record('referentiel_certificate', 'userid', $userid, 'referentielid', $referentielid);
		if ($certificat) {
			// if ($record->verrou==0) // BUG car en cas de deverrouillage les activites crees entretemps ne seraient pas prises en compte
				$certificat->valide=0; // le certificate doit Ãªtre recalcule
				// $certificat->competences_certificat='';
				// DEBUG
				// echo "<br />DEBUG : lip.php : ligne 3237<br />\n";
				// print_r($certificat);
				// echo "<br />\n";
				
				$certificat->comment=addslashes($certificat->comment);
                $certificat->synthese_certificat=addslashes($certificat->synthese_certificat);
				$certificat->decision_jury=addslashes($certificat->decision_jury);
				$certificat->evaluation=addslashes($certificat->evaluation);
	            update_record('referentiel_certificate', $certificat);
			// }
        }
	}
}

/**
 * Given a userid and referentiel id
 * this function will get valide 
 *
 * @param $userid user id
  * @param $referentielid refrentiel id
 * @return 
 **/

function referentiel_certificate_user_valide($userid, $referentielid){
	if ($userid && $referentielid){
		$record = get_record('referentiel_certificate', 'userid', $userid, 'referentielid', $referentielid);
		if ($record) {
			return (($record->valide==1) or ($record->verrou==1));
        }
	}
	return false;
}


// -------------------------------
function referentiel_pourcentage($a, $b){
	if ($b!=0) return round(($a * 100.0) / (float)$b,1);
	else return NULL;
}


// -------------------
function referentiel_affiche_certificate_consolide($separateur1, $separateur2, $liste_code, $referentielid, $bgcolor, $params=NULL){
// ce certificate comporte des pourcentages par domaine et competence
  echo referentiel_retourne_certificate_consolide($separateur1, $separateur2, $liste_code, $referentielid, $bgcolor, $params=NULL);
}

// -------------------
function referentiel_retourne_certificate_consolide($separateur1, $separateur2, $liste_code, $referentielid, $bgcolor, $params=NULL){
// ce certificate comporte des pourcentages par domaine et competence

global $OK_REFERENTIEL_DATA;
global $t_domaine;
global $t_domaine_coeff;
		
// COMPETENCES
global $t_competence;
global $t_competence_coeff;
		
// ITEMS
global $t_item_code;
global $t_item_coeff; // coefficient poids determeine par le modele de calcul (soit poids soit poids / empreinte)
global $t_item_domaine; // index du domaine associÃ© Ã  un item 
global $t_item_competence; // index de la competence associÃ©e Ã  un item 
global $t_item_poids; // poids
global $t_item_empreinte;
global $t_nb_item_domaine;
global $t_nb_item_competence;

	$s='';
	
	// nom des domaines, compÃ©tences, items
	$label_d="";
	$label_c="";
	$label_i="";
	if (isset($params) && !empty($params)){
		if (isset($params->domainlabel)){
					$label_d=$params->domainlabel;
		}
		if (isset($params->skilllabel)){
					$label_c=$params->skilllabel;
		}
		if (isset($params->itemlabel)){
					$label_i=$params->itemlabel;
		}
	}
	$t_certif_item_valeur=array();	// table des nombres d'items valides 
	$t_certif_item_coeff=array(); // somme des poids du domaine
	$t_certif_competence_poids=array(); // somme des poids de la competence
	$t_certif_domaine_poids=array(); // poids certifies
	for ($i=0; $i<count($t_item_code); $i++){
		$t_certif_item_valeur[$i]=0.0;
		$t_certif_item_coeff[$i]=0.0;
	}
	for ($i=0; $i<count($t_competence); $i++){
		$t_certif_competence_poids[$i]=0.0;
	}
	for ($i=0; $i<count($t_domaine); $i++){
		$t_certif_domaine_poids[$i]=0.0;
	}
	// affichage

		
	// donnees globales du referentiel
	if ($referentielid){
		
		if (!isset($OK_REFERENTIEL_DATA) || ($OK_REFERENTIEL_DATA==false) ){
			$OK_REFERENTIEL_DATA=referentiel_initialise_data_referentiel($referentielid);
		}

		if (isset($OK_REFERENTIEL_DATA) && ($OK_REFERENTIEL_DATA==true)){
		// DEBUG 
		// echo "<br />CODE <br />\n";
		// referentiel_affiche_data_referentiel($referentielid, $params);
		
		// recuperer les items valides
		$tc=array();
		$liste_code=referentiel_purge_dernier_separateur($liste_code, $separateur1);
			
		// DEBUG 
		// echo "<br />DEBUG :: print_lib_certificate.php :: 917 :: LISTE : $liste_code<br />\n";

		if (!empty($liste_code) && ($separateur1!="") && ($separateur2!="")){
			$tc = explode ($separateur1, $liste_code);
			
			// DEBUG 

		
			for ($i=0; $i<count($t_item_domaine); $i++){
				$t_certif_domaine_poids[$i]=0.0;
			}
			for ($i=0; $i<count($t_item_competence); $i++){
				$t_certif_competence_poids[$i]=0.0;
			}

			$i=0;
			while ($i<count($tc)){
				// CODE1:N1
				// DEBUG 
				// echo "<br />".$tc[$i]." <br />\n";
				// exit;
				$t_cc=explode($separateur2, $tc[$i]); // tableau des items valides
				
				// print_r($t_cc);
				// echo "<br />\n";
				// exit;
				if (isset($t_cc[1])){
					if (isset($t_item_poids[$i]) && isset($t_item_empreinte[$i])){
						if (($t_item_poids[$i]>0) && ($t_item_empreinte[$i]>0)){
							// echo "<br>".min($t_cc[1],$t_item_empreinte[$i]);
							$t_certif_item_valeur[$i]=min($t_cc[1],$t_item_empreinte[$i]);
							// calculer le taux
							$coeff=(float)$t_certif_item_valeur[$i] * (float)$t_item_coeff[$i];
							// stocker la valeur pour l'item
							$t_certif_item_coeff[$i]=$coeff;
							// stocker le taux pour la competence
							$t_certif_domaine_poids[$t_item_domaine[$i]]+=$coeff;
							// stocker le taux pour le domaine
							$t_certif_competence_poids[$t_item_competence[$i]]+=$coeff;
						}
						else{
							// echo "<br>".min($t_cc[1],$t_item_empreinte[$i]);
							$t_certif_item_valeur[$i]=0.0;
							$t_certif_item_coeff[$i]=0.0;
							// $t_certif_domaine_poids[$t_item_domaine[$i]]+=0.0;
							// $t_certif_competence_poids[$t_item_competence[$i]]+=0.0;
						}
					}
				}
				
				$i++;
			}
			
			// DEBUG 
			
			// DOMAINES
			$s.= '<table width="100%" cellspacing="0" cellpadding="2"><tr valign="top" >'."\n";
			// if (!empty($label_d)){
			//	$s.='<td  width="5%">'.$label_d.'</td>';
			//}
			// else {
			//	$s.='<td $t_certif_item_coeff width="5%">'.get_string('domaine','referentiel').'</td>';
			//}
			for ($i=0; $i<count($t_domaine_coeff); $i++){
				if ($t_domaine_coeff[$i]){
					$s.='<td  align="center" colspan="'.$t_nb_item_domaine[$i].'"><b>'.$t_domaine[$i].'</b> ('.referentiel_pourcentage($t_certif_domaine_poids[$i], $t_domaine_coeff[$i]).'%)</td>';
				}
				else{
					$s.='<td  align="center" colspan="'.$t_nb_item_domaine[$i].'"><b>'.$t_domaine[$i].'</b> (0%)</td>';
				}
			}
			$s.='</tr>'."\n";

			$s.=  '<tr valign="top"  >'."\n";
			for ($i=0; $i<count($t_competence); $i++){
				if ($t_competence_coeff[$i]){
					$s.='<td align="center" colspan="'.$t_nb_item_competence[$i].'"><b>'.$t_competence[$i].'</b> ('.referentiel_pourcentage($t_certif_competence_poids[$i], $t_competence_coeff[$i]).'%)</td>'."\n";
				}
				else{
					$s.='<td align="center" colspan="'.$t_nb_item_competence[$i].'"><b>'.$t_competence[$i].'</b> (0%)</td>'."\n";
				}
			}
			$s.='</tr>'."\n";
			
      // ITEMS
			$s.= '<tr valign="top" >'."\n";
			for ($i=0; $i<count($t_item_code); $i++){
				if ($t_item_empreinte[$i]){
					if ($t_certif_item_valeur[$i]>=$t_item_empreinte[$i])
						$s.='<td'.$bgcolor.'><span  class="valide">'.$t_item_code[$i].'</span></td>'."\n";
					else
						$s.='<td'.$bgcolor.'><span class="invalide">'.$t_item_code[$i].'</span></td>'."\n";
				}
				else{
					$s.='<td class="nondefini"><span class="nondefini"><i>'.$t_item_code[$i].'</i></span></td>'."\n";
				}
			}
			$s.='</tr>'."\n";
      $s.='<tr valign="top" >'."\n";
			// <td  width="5%">'.get_string('coeff','referentiel').'</td>'."\n";
			// for ($i=0; $i<count($t_item_coeff); $i++){
			for ($i=0; $i<count($t_item_code); $i++){
				if ($t_item_empreinte[$i]){
					if ($t_certif_item_valeur[$i]>=$t_item_empreinte[$i]){
						$s.='<td'.$bgcolor.'><span class="valide">100%</span></td>'."\n";
					}
					else{
						$s.='<td'.$bgcolor.'><span class="invalide">'.referentiel_pourcentage($t_certif_item_valeur[$i], $t_item_empreinte[$i]).'%</span></td>'."\n";
					}
				}
				else {
					$s.='<td class="nondefini"><span class="nondefini">&nbsp;</span></td>'."\n";
				}
			}
			$s.='</tr></table>'."\n";			
    }
	}
	}
	return $s;
}




// TACHES

/**
 * This function returns records from table referentiel_task
 *
 * @param id reference activite
 * @param select clause : ' AND champ=valeur,  ... '
 * @param order clause : ' champ ASC|DESC, ... '
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_tasks_instance($referentiel_instance_id){
global $CFG;
	if (isset($referentiel_instance_id) && ($referentiel_instance_id>0)){
		return get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_task WHERE instanceid='.$referentiel_instance_id.' ');
	}
	else 
		return NULL; 
}

/**
 * Given an task id, 
 * this function will permanently delete the task instance 
 * and any consigne that depends on it. 
 *
 * @param object $id
 * @return boolean Success/Failure
 **/

 // -----------------------
function referentiel_delete_task_record($id) {
// suppression task + consignes associes
$ok_task=false;	
	if (isset($id) && ($id>0)){
		if ($task = get_record("referentiel_task", "id", $id)) {
	   		// Delete any dependent records here 
			$ok_association=true;
			if ($r_a_users_tasks = get_records("referentiel_a_user_task", "taskid", $id)) {
				// DEBUG
				// print_object($r_a_users_tasks);
				// echo "<br />";
				// suppression des associations
				foreach ($r_a_users_tasks as $r_a_user_task){
					// suppression
					$ok_association=$ok_association && referentiel_delete_a_user_task_record($r_a_user_task->id);
				}
			}
			
			$ok_consigne=true;
			if ($consignes = get_records("referentiel_consigne", "taskid", $id)) {
				// DEBUG
				// print_object($consignes);
				// echo "<br />";
				// suppression des consignes associes dans la table referentiel_consigne
				foreach ($consignes as $consigne){
					// suppression
					$ok_consigne=$ok_consigne && referentiel_delete_consigne_record($consigne->id);
				}
			}

			// suppression task
			if ($ok_consigne && $ok_association){
				$ok_task = delete_records("referentiel_task", "id", $id);
			}
		}
	}
    return $ok_task;
}


/**
 * Given a a_user_task id, 
 * this function will permanently delete the instance 
 *
 * @param object $id
 * @return boolean Success/Failure
 			
 **/

// -----------------------
function referentiel_delete_a_user_task_record($id){
// suppression association user task
$ok_association=false;
	if (isset($id) && ($id>0)){
		if ($association = get_record("referentiel_a_user_task", "id", $id)) {
			$ok_association= delete_records("referentiel_a_user_task", "id", $id);
		}
	}
	return $ok_association;
}


/**
 * Given a consigne id, 
 * this function will permanently delete the consigne instance 
 *
 * @param object $id
 * @return boolean Success/Failure
 **/

 // -----------------------
function referentiel_delete_consigne_record($id) {
// suppression consigne
$ok_consigne=false;
	if (isset($id) && ($id>0)){
		if ($consigne = get_record("referentiel_consigne", "id", $id)) {
			//  CODE A AJOUTER SI GESTION DE FICHIERS DEPOSES SUR LE SERVEUR
			$ok_consigne= delete_records("referentiel_consigne", "id", $id);
		}
	}
	return $ok_consigne;
}

// CERTIFICATS
/**
 * This function returns record of certificate from table referentiel_certificate
 *
 * @param id reference certificate id
 * @return object
 * @todo Finish documenting this function
 **/
function referentiel_get_certificat($id){
global $CFG;
	if (isset($id) && ($id>0)){
		return get_record_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_certificate WHERE id='.$id.' ');
	}
	else 
		return 0; 
}

/**
 * This function returns records of certificate from table referentiel_certificate
 *
 * @param id reference referentiel (no instance)
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_certificats($referentiel_referentiel_id, $select="", $order=""){
global $CFG;
	if (isset($referentiel_referentiel_id) && ($referentiel_referentiel_id>0)){
		if (empty($order)){
			$order= 'userid ASC ';
		}
		return get_records_sql('SELECT * FROM '. $CFG->prefix . 'referentiel_certificate WHERE referentielid='.$referentiel_referentiel_id.' '.$select.' ORDER BY '.$order.' ');
	}
	else 
		return 0; 
}

/**
 * This function returns records of certificate from table referentiel_certificate
 *
 * @param id reference referentiel (no instance)
 * @return objects
 * @todo Finish documenting this function
 **/
function referentiel_get_all_users_with_certificate($referentiel_referentiel_id, $select="", $order=""){
global $CFG;
	if (isset($referentiel_referentiel_id) && ($referentiel_referentiel_id>0)){
		if (empty($order)){
			$order= 'userid ASC ';
		}
		return get_records_sql('SELECT userid FROM '. $CFG->prefix . 'referentiel_certificate WHERE referentielid='.$referentiel_referentiel_id.' '.$select.' ORDER BY '.$order.' ');
	}
	else 
		return 0; 
}


/**
 * Given an certificate id, 
 * this function will permanently delete the certificate instance  
 *
 * @param object $id
 * @return boolean Success/Failure
 **/

function referentiel_delete_certificate_record($id) {
// suppression certificat
$ok_certificat=false;	
	if (isset($id) && ($id>0)){
		if ($certificate = get_record("referentiel_certificate", "id", $id)) {
			// suppression 
			$ok_certificate = delete_records("referentiel_certificate", "id", $id);
		}
	}
    return $ok_certificat;
}


    /**
     * get directory into which export is going 
     * @return string file path
	 * @ input $course_id : id of current course
	 * @ input $sous_repertoire : a relative path	 
     */
function referentiel_get_export_dir($course_id, $sous_repertoire="") {
	global $CFG;
	/*
    // ensure the files area exists for this course	
	// $path_to_data=referentiel_get_export_dir($course->id,"$referentiel->id/$USER->id");
	$path_to_data=referentiel_get_export_dir($course->id);
    make_upload_directory($path_to_data);	
	*/
        $dirname = get_string('exportfilename', 'referentiel');
        $path = $course_id.'/'.$CFG->moddata.'/'.$dirname; 
		if ($sous_repertoire!=""){
			$pos=strpos($sous_repertoire,'/');
			if (($pos===false) || ($pos!=0)){ // separateur pas en tete
				// RAS
			}
			else {
				$sous_repertoire = substr($sous_repertoire,$pos+1);
			}
			$path .= '/'.$sous_repertoire;
		}
        return $path;
    }


	
	
    /**
     * write a file 
     * @return boolean
	 * @ input $path_to_data : a data path
	 * @ input $filename : a filename
     */
    function referentiel_enregistre_fichier($path_to_data, $filename, $expout) {
        global $CFG;
        // create a directory for the exports (if not already existing)
        if (! $export_dir = make_upload_directory($path_to_data)) {
              print_error( get_string('cannotcreatepath', 'referentiel', $export_dir) );
			  return "";
        }
        $path = $CFG->dataroot.'/'.$path_to_data;

        // write file
        $filepath = $path."/".$filename;
		
		// echo "<br />DEBUG : 2580 :: FILENAME : $filename <br />PATH_TO_DATA : $path_to_data <br />PATH : $path <br />FILEPATH : $filepath\n";
		
        if (!$fh=fopen($filepath,"w")) {
            return "";
        }
        if (!fwrite($fh, $expout, strlen($expout) )) {
            return "";
        }
        fclose($fh);
        return $path_to_data.'/'.$filename;
    }

    /**
     * write a file 
     * @return boolean
	 * @ input $path_to_data : a data path
	 * @ input $filename : a filename
     */
    function referentiel_upload_fichier($path_to_data, $filename_source, $filename_dest) {
        global $CFG;
        // create a directory for the exports (if not already existing)
        if (! $export_dir = make_upload_directory($path_to_data)) {
              print_error( get_string('cannotcreatepath', 'referentiel', $export_dir) );
			  return "";
        }
        $path = $CFG->dataroot.'/'.$path_to_data;
		
		if (referentiel_deplace_fichier($path, $filename_source, $filename_dest, '/', true)){
			return $path_to_data.'/'.$filename_dest;
		}
		else {
			return "";
		}
    }
	
// ------------------
function referentiel_deplace_fichier($dest_path, $source, $dest, $sep, $deplace) {
// recopie un fichier sur le serveur
// pour effectuer un deplacement $deplace=true
// @ devant une fonction signifie qu'aucun message d'erreur n'est affichÃ©
// $dest_path est le dossier de destination du fichier
// source est le nom du fichier source (sans chemin)
// dest est le nom du fichier destination (sans chemin)
// $sep est le sÃ©parateur de chemin
// retourne true si tout s'est bien dÃ©roulÃ©

	// Securite
	if (strstr($dest, "..") || strstr($dest, $sep)) {
		// interdire de remonter dans l'arborescence
		// la source est detruite
		if ($deplace) @unlink($source);
		return false;
	}
	
	// repertoire de stockage des fichiers
	$loc = $dest_path.$sep.$dest;
// 	$ok = @copy($source, $loc);
	$ok =  @copy($source, $loc);
	if ($ok){ 
		// le fichier temporaire est supprimÃ©
		if ($deplace)  @unlink($source);
	}
	else{ 
		// $ok = @move_uploaded_file($source, $loc);
		$ok =  @move_uploaded_file($source, $loc);
	}
	return $ok;
}

	// ------------------	
	function referentiel_get_logo($referentiel){
	// A TERMINER
		return "pix/logo_men.jpg";
	}
	
	// ------------------
	function referentiel_get_file($filename, $course_id, $path="" ) {
	// retourne un path/nom_de_fichier dans le dossier moodledata
 		global $CFG;
 		if ($path==""){
			$currdir = $CFG->dataroot."/$course_id/$CFG->moddata/referentiel/";
  		}
		else {
			$currdir = $CFG->dataroot."/$course_id/$CFG->moddata/referentiel/".$path;
		}
		  
	    if (!file_exists($currdir.'/'.$filename)) {
      		return "";
      	}
		else{
			return $currdir.'/'.$filename;
		}
 	}  




// coaching

// -----------------------
function referentiel_get_accompagnements_user($referentiel_instance_id, $course_id, $userid) {
// retourne la liste des id des accompagnateurs
// 
global $CFG;
	if (!empty($referentiel_instance_id) && !empty($course_id) && !empty($userid)){
    return (get_records_sql('SELECT teacherid as userid FROM '. $CFG->prefix . 'referentiel_accompagnement 
 WHERE instanceid='.$referentiel_instance_id. ' 
 AND courseid='.$course_id.' AND userid='.$userid.' 
 ORDER BY teacherid ASC '));
  }
  return false;
}


// REFERENTIEL_REFERENTIEL IMPORT XML

// -----------------------
function referentiel_set_competence_nb_item($competence_id, $nbitems){
    if ($competence_id && ($nbitems>0)){
        set_field ('referentiel_skill','nb_item_competences',$nbitems,'id',$competence_id);
    }
}

// -----------------------
function referentiel_set_domaine_nb_competence($domaine_id, $nbcompetences){
    if ($domaine_id && ($nbcompetences>0)){
        set_field ('referentiel_domain','nb_competences',$nbcompetences,'id',$domaine_id);
    }
}

// -----------------------
function referentiel_set_referentiel_nb_domaine($referentiel_referentiel_id, $nbdomaines){
    if ($referentiel_referentiel_id && ($nbdomaines>0)){
        set_field ('referentiel_referentiel','nb_domaines',$nbdomaines,'id',$referentiel_referentiel_id);
    }
}

function referentiel_get_full_item_menu($referentiel){
	
	$separator = (@$referentiel->separator) ? $referentiel->separator : '/';
	
	$itemmenu = array();
	if($domains = get_records('referentiel_domain', 'referentielid', $referentiel->referentielid, 'sortorder')){
		foreach($domains as $domain){
			if($skills = get_records('referentiel_skill', 'domainid', $domain->id, 'sortorder')){
				foreach($skills as $skill){
					if($skillitems = get_records('referentiel_skill_item', 'skillid', $skill->id, 'sortorder')){
						foreach($skillitems as $item){
							$itemmenu[$item->id] = $domain->code.$separator.$skill->code.$separator.$item->code.' '.shorten_text($item->description, 80);
						}
					}
				}
			}
		}
	}
	return $itemmenu;	
}

?>
