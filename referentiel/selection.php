<?php  // $Id: selection.php,v 1.0 2008/04/29/ 00:00:00 jfruitet Exp $
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
* association d'un referentiel a l'instance
* @package referentiel
*/
	
  require_once('../../config.php');
  require_once('lib.php');
	require_once('print_lib_referentiel.php');

  $id    = optional_param('id', 0, PARAM_INT);    // course module id	
  $d     = optional_param('d', 0, PARAM_INT);    // referentiel instance id
	$pass  = optional_param('pass', 0, PARAM_INT);    // mot de passe ok
  $checkpass = optional_param('checkpass','', PARAM_ALPHA); // mot de passe fourni

  $action  			= optional_param('action','', PARAM_ALPHA); // pour distinguer differentes formes de creation de referentiel
  $mode  				= optional_param('mode','', PARAM_ALPHA);	
  $format 			= optional_param('format','', PARAM_FILE );

	$name_instance		= optional_param('name_instance','', PARAM_CLEAN);
	$description		= optional_param('description','', PARAM_CLEAN);
	$domainlabel    = optional_param('domainlabel','', PARAM_CLEAN);
	$skilllabel = optional_param('skilllabel','', PARAM_CLEAN);
	$itemlabel= optional_param('itemlabel','', PARAM_CLEAN);

  $sesskey     		= optional_param('sesskey', '', PARAM_ALPHA);
	$instance 			= optional_param('instance', 0, PARAM_INT);
	$select_acc = optional_param('select_acc', 0, PARAM_INT);      // coaching

	if ($d) {
        if (! $referentiel = get_record('referentiel', 'id', $d)) {
            print_error('Referentiel instance is incorrect');
        }
		
		if (! $course = get_record('course', 'id', $referentiel->course)) {
	            print_error('Course is misconfigured');
    	}
        	
		if (! $cm = get_coursemodule_from_instance('referentiel', $referentiel->id, $course->id)) {
    	        print_error('Course Module ID is incorrect');
		}
	} 
	elseif ($id) {
        if (! $cm = get_coursemodule_from_id('referentiel', $id)) {
        	print_error('Course Module ID was incorrect');
        }
        if (! $course = get_record('course', 'id', $cm->course)) {
            print_error('Course is misconfigured');
        }
        if (! $referentiel = get_record('referentiel', 'id', $cm->instance)) {
            print_error('Referentiel instance is incorrect');
        }
    } 
	else{
        // print_error('You cannot call this script in that way');	
		error(get_string('erreurscript','referentiel','Erreur01 : selection.php'));
	}
	
	if (!isset($mode)){
		$mode='add'; // un seul mode possible
	}
	if (!isset($action) || empty($action)){
		$action='selectreferentiel'; // une seule action possible
	}
    
	// get parameters
	
  $params = new stdClass;
	$params->filtrerinstance = optional_param('filtrerinstance', 0, PARAM_BOOL);
  $params->localinstance = optional_param('localinstance', 0, PARAM_BOOL);
	// $params->globalinstance = optional_param('localinstance', 1, PARAM_BOOL);

  // get display strings
  $txt = new stdClass();
  $txt->referentiel = get_string('referentiel','referentiel');
	$txt->modulename = get_string('modulename','referentiel');
  $txt->modulenameplural = get_string('modulenameplural','referentiel');
  $txt->onlyteachersselect = get_string('onlyteachersselect','referentiel');
  $txt->selectnoreferentiel = get_string('selectnoreferentiel', 'referentiel');
	$txt->selectreferentiel	= get_string('selectreferentiel','referentiel');
	$txt->selecterror_referentiel_id = get_string('selecterror_referentiel_id','referentiel');
	$txt->localinstance	= get_string('localinstance','referentiel');	
	$txt->choix_instance = get_string('choix_instance','referentiel');
	$txt->choix_filtrerinstance = get_string('choix_filtrerinstance','referentiel');
	$txt->choix_oui_filtrerinstance = get_string('choix_oui_filtrerinstance','referentiel');
	$txt->choix_non_filtrerinstance = get_string('choix_non_filtrerinstance','referentiel');
	$txt->choix_localinstance = get_string('choix_localinstance','referentiel');
	$txt->choix_globalinstance = get_string('choix_globalinstance','referentiel');
	$txt->select = get_string('select','referentiel');
	$txt->select2= get_string('filtrer','referentiel');
	$txt->cancel = get_string('quit','referentiel');	
	$txt->filtrerlocalinstance = get_string('filtrerlocalinstance','referentiel');
	$txt->pass	= get_string('password','referentiel');	
		
	$returnlink_erreur=$CFG->wwwroot.'/course/view.php?id='.$course->id;
	$returnlink_suite=$CFG->wwwroot.'/mod/referentiel/add.php?id='.$cm->id.'&amp;sesskey='.sesskey();

  require_login();
  if (!isloggedin() or isguest()) {
        redirect($returnlink_erreur);
  }

  // check role capability
  $context = get_context_instance(CONTEXT_COURSE, $course->id);
	
  if ($referentiel->id) {    // So do you have access?
        if (!has_capability('mod/referentiel:select', $context) or !confirm_sesskey() ) {
            error(get_string('noaccess','referentiel'));
        }
  }
	else{
		print_error('Referentiel instance is incorrect');
	}

	/// RSS and CSS and JS meta
  $meta = '';

	/// Print the page header
	$strreferentiels = get_string('modulenameplural','referentiel');
	$strreferentiel = get_string('referentiel','referentiel');
	$strmessage = get_string('selectreferentiel','referentiel');		
	$icon = '<img class="icon" src="'.$CFG->wwwroot.'/mod/referentiel/icon.gif" alt="'.get_string('modulename','referentiel').'"/>';
	$strpagename=get_string('modifier_referentiel','referentiel');
	if (function_exists('build_navigation')){
		// Moodle 1.9
		$navigation = build_navigation($strpagename, $cm);
		
		print_header($course->shortname.': '.$strreferentiel, $course->fullname, $navigation, 
		'', // focus
		$meta,
		true, // page is cacheable
//		update_module_button($cm->id, $course->id, get_string('modulename', 'referentiel')), // HTML code for a button (usually for module editing)
		update_module_button($cm->id, $course->id, get_string('modulename-intance', 'referentiel')), // HTML code for a button (usually for module editing)
        navmenu($course, $cm), // HTML code for a popup menu
		false, // use XML for this page
		'', // This text will be included verbatim in the <body> tag (useful for onload() etc)
		false); // If true, return the visible elements of the header instead of echoing them.
	}
	else{
		// 1.8
		print_header_simple("$course->shortname : $strreferentiels", // title
		"", // heading
		"<a href='index.php?id=$course->id'>$strreferentiel</a> -> $strmessage", // navigation
		'', // focus
		$meta, // meta tag
		true, // page is cacheable
		update_module_button($cm->id, $course->id, get_string('modulename-intance', 'referentiel')), // HTML code for a button (usually for module editing)
        navmenu($course, $cm), // HTML code for a popup menu
		false, // use XML for this page
		'', // This text will be included verbatim in the <body> tag (useful for onload() etc)
		false) // If true, return the visible elements of the header instead of echoing them.
		;
	}
  print_heading_with_help($strmessage, 'selectreferentiel', 'referentiel', $icon);

  // file upload form submitted
  if (!confirm_sesskey()) {
        	print_error( 'sesskey' );
  }
	
	// RECUPERER LES FORMULAIRES
  if (isset($SESSION->modform)) {   // Variables are stored in the session
        $form = $SESSION->modform;
        unset($SESSION->modform);
  } else {
        $form = (object)$_POST;
  }
	
	// Traitement des POST
	$msg="";
	
	if (!empty($course) && isset($form)) {   
    // debug
    // echo "<br />DEBUG 197 ::<br />\n";
    // print_object($form);
    // echo "<br />DEBUG 197 ::<br />\n";
		// select form submitted	

		// lien de retour en cas d'erreur
		// $returnlink=$CFG->wwwroot.'/mod/referentiel/add.php?id='.$cm->id;
		// $returnlink=$CFG->wwwroot.'/course/view.php?id='.$course->id;
		$returnlink=$CFG->wwwroot.'/mod/referentiel/add.php?id='.$cm->id.'&amp;sesskey='.sesskey();
		
		// variable d'action cancel
		if (!empty($form->cancel)){
			if ($form->cancel == get_string("quit", "referentiel")){
				// Abandonner
    	  if (!empty($SESSION->returnpage)) {
	            	$return = $SESSION->returnpage;
    		        unset($SESSION->returnpage);
   	        		redirect($return);
       	} 
				else {
    		  redirect($returnlink);
   	    }
       	exit;
			}
		}
		
		// variable d'action 
		else if (!empty($form->action)){
			if ($form->action=="filtrerreferentiel"){
				// enregistre les modifications
				// $return=referentiel_update_referentiel($form);
				// $msg=get_string("referentiel", "referentiel")." ".$form->instance;
				// $action="update";
				if (isset($form->filtrerinstance)){
					$form->filtrerinstance = $form->filtrerinstance;
					if ($form->filtrerinstance!=0){
						if (isset($form->localinstance )){
							$params->localinstance = $form->localinstance;
						}
					}
				}
				else {
					$params->filtrerinstance = 0;
				}
				// mot de passe
				$params->referentiel_pass='';
				if (isset($form->givepass) && ($form->givepass=='1')){
					if (isset($form->referentiel_pass)){
						$params->referentiel_pass = md5($form->referentiel_pass);
					}
				}
			}
			if ($form->action=="selectreferentiel"){
				if (isset($form->referentiel_id) && ($form->referentiel_id>0)){
          $form2 = $_POST;
          // mot de passe
		   		if (isset($form2['givepass_'.$form->referentiel_id]) 
            && ($form2['givepass_'.$form->referentiel_id]=='1')){			   		       
            if (isset($form2['referentiel_pass_'.$form->referentiel_id])){
						  $params->referentiel_pass = md5($form2['referentiel_pass_'.$form->referentiel_id]);
					  }
				  }
		  
					$new_referentiel_id=referentiel_filtrer($form->referentiel_id, $params);
	        // Verifier si  referentiel charge
					if (! $new_referentiel_id) {
    	            	// print_error( $new_referentiel_id , $returnlink);
						// PAS D'ERREUR on propose un autre choix
        	}
					else{
						echo "<hr />";
						// 
?>
<form name="form" method="post" action="add.php?id=<?php echo $cm->id; ?>">

<input type="hidden" name="name_instance" value="<?php  p($name_instance) ?>" />
<input type="hidden" name="description" value="<?php  p($description) ?>" />
<input type="hidden" name="domainlabel" value="<?php  p($domainlabel) ?>" />
<input type="hidden" name="skilllabel" value="<?php  p($skilllabel) ?>" />
<input type="hidden" name="itemlabel" value="<?php  p($itemlabel) ?>" />

<input type="hidden" name="new_referentiel_id" value="<?php  p($new_referentiel_id); ?>" />
<input type="hidden" name="action" value="<?php  p($action); ?>" />	

<input type="hidden" name="course"        value="<?php  p($course->id); ?>" />
<input type="hidden" name="sesskey"     value="<?php  p(sesskey()); ?>" />
<input type="hidden" name="instance"      value="<?php  echo $referentiel->id; ?>" />
<input type="hidden" name="mode"          value="<?php  p($mode); ?>" />
<center>
<input type="submit" value="<?php  print_string("continue"); ?>" />
</center>
</form>
<?php
        			    print_footer($course);
            			exit;
					}
				}
			}
		}
	}
	
	//==========
    // DISPLAY
    //==========
 	
	$str_selection=referentiel_select_referentiels($params);
	if (empty($str_selection)){
?>
<p align="center"><?php  p($txt->selectnoreferentiel); ?></p>
<center>
<form name="form" method="post" action="add.php?id=<?php echo $cm->id; ?>">

<input type="hidden" name="name_instance" value="<?php  echo(stripslashes($name_instance)); ?>" />
<input type="hidden" name="description" value="<?php  echo(stripslashes($description)); ?>" />
<input type="hidden" name="domainlabel" value="<?php  echo(stripslashes($domainlabel)); ?>" />
<input type="hidden" name="skilllabel" value="<?php  echo(stripslashes($skilllabel)); ?>" />
<input type="hidden" name="itemlabel" value="<?php  echo(stripslashes($itemlabel)); ?>" />

<input type="hidden" name="action" value="<?php  p($action); ?>" />	

<!-- These hidden variables are always the same -->
<input type="hidden" name="sesskey" value="<?php p(sesskey()); ?>" />
<input type="hidden" name="course" value="<?php p($course->id); ?>" />
<input type="hidden" name="instance" value="<?php  echo $referentiel->id; ?>" />
<input type="hidden" name="mode" value="<?php  p($mode); ?>" />	
<input type="submit" value="<?php print_string('continue'); ?>" />
</form>
</center>
	<?php
		// print_error( $txt->selectnoreferentiel , $returnlink);
	}
	else {
    ?>


    <form id="form" enctype="multipart/form-data" method="post" action="selection.php?id=<?php echo $cm->id; ?>">
        <fieldset class="invisiblefieldset" style="display: block;">
            <?php print_box_start('generalbox boxwidthnormal boxaligncenter'); ?>
            <table cellpadding="5">
                <tr>					
                   <td align="left"><?php p( $txt->choix_filtrerinstance); ?></td>
				   <td>
<?php
		if (isset($params->filtrerinstance) && ($params->filtrerinstance!=0)){			   		   
			echo ('<input name="filtrerinstance" type="checkbox" checked="checked" />');
		}
		else {
			echo ('<input name="filtrerinstance" type="checkbox" />');	
		}
?>
	                <?php helpbutton('selection_referentiel', $txt->selectreferentiel, 'referentiel'); ?>
					</td>
                </tr>
				<tr>
                   <td align="left"><?php p( $txt->filtrerlocalinstance); ?></td>
				   <td>
<?php

		if (isset($params->localinstance) && ($params->localinstance==0)){
			echo ('<input name="localinstance" type="radio" value="1" />'.$txt->choix_localinstance);
			echo ('<input name="localinstance" type="radio" value="0" checked="checked" />'.$txt->choix_globalinstance);		
		}
		else {
			echo ('<input name="localinstance" type="radio" value="1" checked="checked" />'.$txt->choix_localinstance);	
			echo ('<input name="localinstance" type="radio" value="0" />'.$txt->choix_globalinstance);
		}
?>     
	                <?php helpbutton('referentiel_local', $txt->selectreferentiel, 'referentiel'); ?>
					</td>
                </tr>
                <tr>
                    <td>
<input type="submit" name="action" value="<?php echo( $txt->select2); ?>" />
					
<input type="submit" name="cancel" value="<?php echo( $txt->cancel); ?>" />					
					</td>
                </tr>
				
            </table>
            <?php
            print_box_end();
?>

<input type="hidden" name="name_instance" value="<?php  p($name_instance) ?>" />
<input type="hidden" name="description" value="<?php  p($description) ?>" />
<input type="hidden" name="domainlabel" value="<?php  p($domainlabel) ?>" />
<input type="hidden" name="skilllabel" value="<?php  p($skilllabel) ?>" />
<input type="hidden" name="itemlabel" value="<?php  p($itemlabel) ?>" />
		
<!-- These hidden variables are always the same -->
<input type="hidden" name="sesskey" value="<?php p(sesskey()); ?>" />
<input type="hidden" name="course" value="<?php p($course->id); ?>" />
<input type="hidden" name="instance" value="<?php  echo $referentiel->id; ?>" />
<input type="hidden" name="mode" value="<?php  p($mode); ?>" />	
        </fieldset>
    </form>

	
    <form id="form" enctype="multipart/form-data" method="post" action="selection.php?id=<?php echo $cm->id; ?>">
        <fieldset class="invisiblefieldset" style="display: block;">
    <?php
			

            print_box_start('generalbox boxwidthnormal boxaligncenter'); ?>
            <b><?php p( $txt->selectreferentiel); ?></b>
            <table cellpadding="5">
                <tr>
                    <td colspan="2"><?php echo $str_selection; ?></td>
                </tr>

                <tr>
                    <td>&nbsp;</td>
                    <td>
<input type="submit" name="action" value="<?php echo( $txt->select); ?>" />
					
<input type="submit" name="cancel" value="<?php echo( $txt->cancel); ?>" />					
					</td>
                </tr>
            </table>
            <?php
            print_box_end(); ?>
<input type="hidden" name="action" value="<?php  p($action); ?>" />	

<input type="hidden" name="name_instance" value="<?php  p($name_instance) ?>" />
<input type="hidden" name="description" value="<?php  p($description) ?>" />
<input type="hidden" name="domainlabel" value="<?php  p($domainlabel) ?>" />
<input type="hidden" name="skilllabel" value="<?php  p($skilllabel) ?>" />
<input type="hidden" name="itemlabel" value="<?php  p($itemlabel) ?>" />
		
<!-- These hidden variables are always the same -->
<input type="hidden" name="sesskey" value="<?php p(sesskey()); ?>" />
<input type="hidden" name="course" value="<?php p($course->id); ?>" />
<input type="hidden" name="instance" value="<?php  echo $referentiel->id; ?>" />
<input type="hidden" name="mode" value="<?php  p($mode); ?>" />	
        </fieldset>
    </form>
    <?php
    }
	print_footer($course);

?>
