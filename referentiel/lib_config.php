<?php
/// CONFIGURATION
// inclus dans lib.php

/**
 * Given an object containing all the necessary configuration data,
 * this function
 * will update an existing record.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
*/

/**
*
* @param string $type 'config' ou 'printconfig'
*
*/
function referentiel_affiche_config($str_config, $type = 'config'){ 
	global $CFG;
	
	$str = '';
	if ($str_config == ''){
		$str_config = referentiel_make_config($type);
	}

	if ($str_config != ''){
		$configs = explode(';', $str_config);

		foreach($configs as $config){
			$config = trim($config);
			if ($config != ''){
				list($key, $value) = explode(':', $config);
				$key = trim($key);
				$value = trim($value);
				if ($key != ''){

					$str .= get_string($key, 'referentiel').' ';
					$str_conf = referentiel_associe_item_configuration($key);

					// creer le parametre si necessaire
					if (!isset($CFG->$str_conf)) $CFG->$str_conf = 0;

					if ($CFG->$str_conf == 2){
						$str .= ' (<i>'.$key.'</i>) <b>'.get_string('config_verrouillee','referentiel').'</b>'."\n";
					}
					elseif ($value == 1){
						$str .=' (<i>'.$key.'</i>) <b>'.get_string('yes')."</b>\n";
					} else {
						$str .=' (<i>'.$key.'</i>) <b>'.get_string('no')."</b>\n";
					}
					$str .= '<br />'."\n";
				}
			}
		}
	}
	return $str;
}

// -----------------------
function referentiel_is_author($userid, $referentiel_referentiel){
    // return true if userid is refrentiel_referentiel author
	return (!empty($referentiel_referentiel->referentielauthormail)
        && ($referentiel_referentiel->referentielauthormail==referentiel_get_user_mail($userid)));
}


// -----------------------
function referentiel_instance_get_referentiel($instanceid){
// retourne l'id du referentiel associé à une instance
    global $CFG;
    if ($instanceid){
		$instance=get_record_sql('SELECT referentielid FROM '. $CFG->prefix . 'referentiel WHERE id='.$instanceid.' ');
		if ($instance){
            return $instance->referentielid;
        }
    }
    return 0;
}

// -----------------------
function referentiel_global_can_write_or_import_ref($referentiel_referentiel_id) {
// examine en cascade la configuration au niveau du site, du referentiel
// verifier si autorisation de creation de referentiel au niveau des cours
    global $CFG;
	// configuration
    if (!isset($CFG->referentiel_creation_limitee)){
		$CFG->referentiel_creation_limitee=0;
	}

	if ($CFG->referentiel_creation_limitee!=2){
        if ($referentiel_referentiel_id){
            return (referentiel_ref_get_item_config('creref', $referentiel_referentiel_id, 'config')==0);
        }
    }
    return false;
}

// -----------------------
function referentiel_global_can_select_referentiel($referentiel_referentiel_id) {
// examine en cascade la configuration au niveau du site, du referentiel
// verifier si autorisation de selection d'un referentiel existant au niveau des cours
    global $CFG;
	// configuration
    if (!isset($CFG->referentiel_selection_autorisee)){
		$CFG->referentiel_selection_autorisee=0;
	}
	if ($CFG->referentiel_selection_autorisee!=2) {
        /// verifier valeur globale
        if ($referentiel_referentiel_id){
            return(referentiel_ref_get_item_config('selref', $referentiel_referentiel_id, 'config')==0);
        }
    }
    return false;
}

// -----------------------
function referentiel_global_can_print_referentiel($referentiel_referentiel_id) {
// examine en cascade la configuration au niveau du site, du referentiel
// verifier si autorisation d'impression d'un certificate au niveau des cours
    global $CFG;
	// configuration
    if (!isset($CFG->impression_referentiel_autorisee)){
		$CFG->impression_referentiel_autorisee=0;
	}
	if ($CFG->impression_referentiel_autorisee!=2) {
        /// verifier valeur globale
        if ($referentiel_referentiel_id){
            return(referentiel_ref_get_item_config('impcert', $referentiel_referentiel_id, 'config')==0);
        }
    }
    return false;
}


// -----------------------
function referentiel_site_can_write_or_import_referentiel($referentiel_instance_id) {
// examine en cascade la configuration au niveau du site, du referentiel, de l'instance
// verifier si autorisation de creation de referentiel au niveau des cours
    global $CFG;
	// configuration
    if (!isset($CFG->referentiel_creation_limitee)){
		$CFG->referentiel_creation_limitee=0;
	}

	if ($CFG->referentiel_creation_limitee!=2){
        /// verifier valeur globale
        $referentiel_referentiel_id=referentiel_instance_get_referentiel($referentiel_instance_id);
        if ($referentiel_referentiel_id){
            if (referentiel_ref_get_item_config('creref', $referentiel_referentiel_id, 'config')==0){
                /// retourner valeur locale
                return (referentiel_get_configuration_item('creref', $referentiel_instance_id, 'config')==0);
            }
        }
    }
    return false;
}

// -----------------------
function referentiel_site_can_select_referentiel($referentiel_instance_id) {
// examine en cascade la configuration au niveau du site, du referentiel, de l'instance
// verifier si autorisation de selection d'un referentiel existant au niveau des cours
    global $CFG;
	// configuration
    if (!isset($CFG->referentiel_selection_autorisee)){
		$CFG->referentiel_selection_autorisee=0;
	}
	if ($CFG->referentiel_selection_autorisee!=2) {
        /// verifier valeur globale
        $referentiel_referentiel_id=referentiel_instance_get_referentiel($referentiel_instance_id);
        if ($referentiel_referentiel_id){
            if (referentiel_ref_get_item_config('selref', $referentiel_referentiel_id, 'config')==0){
            	/// renvoyer valeur locale
                return (referentiel_get_configuration_item('selref', $referentiel_instance_id, 'config')==0);
            }
        }
    }
    return false;
}

// -----------------------
function referentiel_site_can_print_referentiel($referentiel_instance_id) {
// examine en cascade la configuration au niveau du site, du referentiel, de l'instance
// verifier si autorisation d'impression d'un certificate au niveau des cours
    global $CFG;
	// configuration
    if (!isset($CFG->impression_referentiel_autorisee)){
		$CFG->impression_referentiel_autorisee=0;
	}
	if ($CFG->impression_referentiel_autorisee!=2) {
        /// verifier valeur globale
        $referentiel_referentiel_id=referentiel_instance_get_referentiel($referentiel_instance_id);
        if ($referentiel_referentiel_id){
            if (referentiel_ref_get_item_config('impcert', $referentiel_referentiel_id, 'config')==0){
            	/// renvoyer valeur locale
                return (referentiel_get_configuration_item('impcert', $referentiel_instance_id, 'config')==0);
            }
        }
    }
    return false;
}

// -----------------------
function referentiel_associe_item_configuration($item){
// retourne le nom du parametre de configuration
		switch($item){
			case 'scol' :	return 'referentiel_scolarite_masquee'; break;
			case 'creref' :	return 'referentiel_creation_limitee'; break;
			case 'selref' :	return 'referentiel_selection_autorisee'; break;
			case 'impcert' : return 'referentiel_impression_autorisee'; break;
			case 'refcert' : return 'certificate_sel_referentiel'; break;
			case 'instcert' : return 'certificate_sel_referentiel_instance'; break;
			case 'numetu' : return 'certificate_sel_student_numero'; break;
			case 'nometu' : return 'certificate_sel_student_nom_prenom'; break;
			case 'etabetu' : return 'certificate_sel_student_etablissement'; break;
			case 'ddnetu' : return 'certificate_sel_student_ddn'; break;
			case 'lieuetu' : return 'certificate_sel_student_lieu_naissance'; break;
			case 'adretu' : return 'certificate_sel_student_adresse'; break;
			case 'detail' : return 'certificate_sel_certificate_detail'; break;
			case 'pourcent' : return 'certificate_sel_certificate_pourcent'; break;
			case 'compdec' : return 'certificate_sel_activite_competences'; break;
			case 'compval' : return 'certificate_sel_certificate_competences'; break;
			case 'nomreferent' : return 'certificate_sel_certificate_referents'; break;
			case 'jurycert' : return 'certificate_sel_decision_jury'; break;
			case 'comcert' : return 'certificate_sel_commentaire'; break;
		}
		return '';
}

// -----------------------
// initialise le vecteur de configuration
function referentiel_make_config($type = 'config'){
	global $CFG;

	$str = '';
	if ($type == 'config'){
		// configuration
		$config['scol'] = (isset($CFG->referentiel_scolarite_masquee)) ? $CFG->referentiel_scolarite_masquee : 0 ;
		$config['creref'] = (isset($CFG->referentiel_creation_limitee)) ? $CFG->referentiel_creation_limitee : 0 ;
		$config['selref'] = (isset($CFG->referentiel_selection_autorisee)) ? $CFG->referentiel_selection_autorisee : 0 ;
		$config['impcert'] = (isset($CFG->referentiel_impression_autorisee)) ? $CFG->referentiel_impression_autorisee : 0 ;
	} else {
		// impression certificat
		// instcert:0;numetu:1;nometu:1;etabetu:0;ddnetu:0;lieuetu:0;adretu:0;pourcent:0;compdec:0;compval:1;nomreferent:0;jurycert:1;comcert:0;

		// impression certificat
		$config['refcert'] = (isset($CFG->certificate_sel_referentiel)) ? $CFG->certificate_sel_referentiel : 0 ;
		$config['instcert'] = (isset($CFG->certificate_sel_referentiel_instance)) ? $CFG->certificate_sel_referentiel_instance : 0 ;
		$config['numetu'] = (isset($CFG->certificate_sel_student_numero)) ? $CFG->certificate_sel_student_numero : 1 ;
		$config['nometu'] = (isset($CFG->certificate_sel_student_nom_prenom)) ? $CFG->certificate_sel_student_nom_prenom : 1 ;
		$config['etabetu'] = (isset($CFG->certificate_sel_student_etablissement)) ? $CFG->certificate_sel_student_etablissement : 1 ;
		$config['ddnetu'] = (isset($CFG->certificate_sel_student_ddn)) ? $CFG->certificate_sel_student_ddn : 0 ;
		$config['lieuetu'] = (isset($CFG->certificate_sel_student_lieu_naissance)) ? $CFG->certificate_sel_student_lieu_naissance : 0 ;
		$config['adretu'] = (isset($CFG->certificate_sel_student_adresse)) ? $CFG->certificate_sel_student_adresse : 0 ;
		$config['detail'] = (isset($CFG->certificate_sel_student_detail)) ? $CFG->certificate_sel_student_detail : 1 ;
		$config['pourcent'] = (isset($CFG->certificate_sel_student_pourcent)) ? $CFG->certificate_sel_student_pourcent : 0 ;
		$config['compdec'] = (isset($CFG->certificate_sel_activite_competences)) ? $CFG->certificate_sel_activite_competences : 0 ;
		$config['compval'] = (isset($CFG->certificate_sel_certificate_competences)) ? $CFG->certificate_sel_certificate_competences : 1 ;
		$config['nomreferent'] = (isset($CFG->certificate_sel_certificate_referents)) ? $CFG->certificate_sel_certificate_referents : 0 ;
		$config['jurycert'] = (isset($CFG->certificate_sel_decision_jury)) ? $CFG->certificate_sel_decision_jury : 1 ;
		$config['comcert'] = (isset($CFG->certificate_sel_commentaire)) ? $CFG->certificate_sel_commentaire : 0 ;
	}

	foreach($config as $key => $value){
		$configarr[] = "$key:$value";
	}
	$str = implode(';', $configarr);

	return $str;
}


// ---------------------------------
//  sauvegarde de la configuration globale
function referentiel_global_set_vecteur_config($str_config, $referentiel_referentiel_id){

	$ok = false;
	if (!empty($referentiel_referentiel_id) && !empty($str_config)){
        $ok = set_field('referentiel_referentiel', 'config', $str_config, 'id', $referentiel_referentiel_id);
	}
	return $ok;
}


// ---------------------------------
function referentiel_set_vecteur_configuration($str_config, $referentiel_instance_id){
//  sauvegarde de la configuration locale
	$ok=false;
	if (!empty($referentiel_instance_id) && !empty($str_config)){
        $ok = set_field('referentiel', 'config', $str_config, 'id', $referentiel_instance_id);
	}
	return $ok;
}

// ---------------------------------
function referentiel_global_set_vecteur_config_imp($str_config, $referentiel_referentiel_id){
//  sauvegarde de la configuration d'impression globale
	$ok = false;
	if (!empty($referentiel_referentiel_id) && !empty($str_config)){
		$ok = set_field('referentiel_referentiel','printconfig',$str_config,'id',$referentiel_referentiel_id);
	}
	return $ok;
}

// ---------------------------------
function referentiel_set_vecteur_configuration_impression($str_config, $referentiel_instance_id){
//  sauvegarde de la configuration d'impression locale
	$ok=false;
	if (!empty($referentiel_instance_id) && !empty($str_config)){
		$ok = set_field('referentiel','printconfig',$str_config,'id',$referentiel_instance_id);
	}
	return $ok;
}


// ---------------------------------
function referentiel_ref_set_option_imp_certificat($referentiel_referentiel_id, $form){
//  sauvegarde de la configuration d'impression globale
// $form : un formulaire de saisie
	$ok=false;
	if (!empty($referentiel_referentiel_id) && !empty($form)){
		$str_config = referentiel_form2config($form, 'printconfig');
		return referentiel_global_set_vecteur_config_imp($str_config, $referentiel_referentiel_id);
	}
	return $ok;
}

// ---------------------------------
function referentiel_set_option_impression_certificat($referentiel_instance_id, $form){
//  sauvegarde de la configuration d'impression locale
// $form : un formulaire de saisie
	$ok = false;
	if (!empty($referentiel_instance_id) && !empty($form)){
		$str_config = referentiel_form2config($form, 'printconfig');
		return referentiel_set_vecteur_configuration_impression($str_config, $referentiel_instance_id);
	}
	return $ok;
}

// ---------------------------------
/**
* initialise le vecteur de configuration en fonction des parametres saisis dans le formulaire
* item type config = 'scol', 'creref', 'selref', 'impcert',
* item type printconfig = 'refcert', 'instcert', 'numetu', nometu, etabetu, ddnetu, lieuetu, adretu, pourcent, compdec, compval, nomreferent, jurycert, comcert,
* Valeurs par defaut 'scol:0;creref:0;selref:0;impcert:0;
* Valeurs par defaut : refcert:1;instcert:0;numetu:1;nometu:1;etabetu:0;ddnetu:0;lieuetu:0;adretu:0;detail:1;pourcent:0;compdec:0;compval:1;nomreferent:0;jurycert:1;comcert:0;
*/
function referentiel_form2config($form, $type = 'config'){

	if ($type == 'config'){
		$config['scol'] = isset($form->scol) ? $form->scol : 0 ;
		$config['creref'] = isset($form->creref) ? $form->creref : 0 ;
		$config['selref'] = isset($form->selref) ? $form->selref : 0 ;
		$config['impcert'] = isset($form->impcert) ? $form->impcert : 0 ;
	} else {
		$config['refcert'] = isset($form->refcert) ? $form->refcert : 1 ;
		$config['instcert'] = isset($form->instcert) ? $form->instcert : 0 ;
		$config['numetu'] = isset($form->numetu) ? $form->numetu : 1 ;
		$config['nometu'] = isset($form->nometu) ? $form->nometu : 1 ;
		$config['etabetu'] = isset($form->etabetu) ? $form->etabetu : 0 ;
		$config['ddnetu'] = isset($form->ddnetu) ? $form->ddnetu : 0 ;
		$config['lieuetu'] = isset($form->lieuetu) ? $form->lieuetu : 0 ;
		$config['adretu'] = isset($form->adretu) ? $form->adretu : 0 ;
		$config['detail'] = isset($form->detail) ? $form->detail : 0 ;
		$config['pourcent'] = isset($form->pourcent) ? $form->pourcent : 0 ;
		$config['compdec'] = isset($form->compdec) ? $form->compdec : 0 ;
		$config['compval'] = isset($form->compdec) ? $form->compval : 1 ;
		$config['nomreferent'] = isset($form->nomreferent) ? $form->nomreferent : 0 ;
		$config['jurycert'] = isset($form->jurycert) ? $form->jurycert : 1 ;
		$config['comcert'] = isset($form->comcert) ? $form->comcert : 0 ;
	}

	foreach($config as $key => $value){
		$configarr[] = "$key:$value";
	}
	$str = implode(';', $configarr);

	return ($str);
}

// -----------------------------
// item = 'scol', 'creref', 'selref', 'impcert', refcert, instcert, numetu, nometu, etabetu, ddnetu, lieuetu, adretu, pourcent, compdec, compval, referent, jurycert, comcert,
// 'scol:0;creref:0;selref:0;impcert:0;refcert:1;instcert:0;numetu:1;nometu:1;etabetu:0;ddnetu:0;lieuetu:0;adretu:0;detail:1;pourcent:0;compdec:0;compval:1;nomreferent:0;jurycert:1;comcert:0;'
// retourne une liste de selecteurs
// $type : config ou printconfig
function referentiel_selection_configuration($str_config, $type = 'config'){

	global $CFG;

	$str = '';

	if ($str_config == ''){
		$str_config = referentiel_make_config($type);
	}

	$s = '';
	if ($str_config != ''){
		$configs = explode(';', $str_config);
		foreach($configs as $config){
			$config = trim($config);
			if ($config != ''){
				list($key, $value) = explode(':',$config);
				$key = trim($key);
				$value = trim($value);
				if ($key != ''){

					$s .= get_string($key,'referentiel').' ';
					$str_conf = referentiel_associe_item_configuration($key);
					// creer le parametre si necessaire
					if (!isset($CFG->$str_conf)){
						$CFG->$str_conf=0;
					}
					if ($CFG->$str_conf == 2){
						$str .= '<input type="hidden" name="'.$key.'" value="2" /> <b>'.get_string('config_verrouillee','referentiel').'</b>'."\n";
					}
					elseif ($CFG->$str_conf == 1){
						$str .= ' <input type="radio" name="'.$key.'" value="0" />'.get_string('no').' <input type="radio" name="'.$key.'" value="1"  checked="checked" />'.get_string('yes')."\n";
					} else {
						$str .=' <input type="radio" name="'.$key.'" value="0" checked="checked" />'.get_string('no').' <input type="radio" name="'.$key.'" value="1" />'.get_string('yes')."\n";
					}
					$str .= '<br />'."\n";
				}
			}
		}
	}
	$str .= ' <input type="hidden" name="config" value="'.$str_config.'" />'."\n";
	return $str;
}

// ---------------------------------
// retourne la valeur de configuration globale pour ce referentiel
function referentiel_ref_get_vecteur_config($referentielid) {
	global $CFG;

	if (!empty($referentielid)){
		$configrec = get_field('referentiel_referentiel', 'config', 'id', $referentielid);
		if ($configrec){
			return($config->config);
		}
	}
	return '';
}


// ---------------------------------
function referentiel_get_vecteur_configuration($refinstanceid) {
// retourne la valeur de configuration locale pour cette instance de referentiel
global $CFG;
	if (!empty($refinstanceid)){
		$config = get_field('referentiel', 'config', 'id', $referentielinstanceid);
		if ($config){
			return($config->config);
		}
	}
	return '';
}

// ---------------------------------
function referentiel_ref_get_vecteur_config_imp($ref_referentiel_referentiel) {
// retourne la valeur de configuration globale pour ce referentiel
global $CFG;
	if (isset($ref_referentiel_referentiel) && ($ref_referentiel_referentiel>0)){
		$config = new object();
		$config = get_record_sql('SELECT printconfig FROM '. $CFG->prefix . 'referentiel_referentiel WHERE id='.$ref_referentiel_referentiel);
		if ($config){
			return($config->printconfig);
		}
	}
	return '';
}


// ---------------------------------
function referentiel_get_vecteur_configuration_impression($ref_instance_referentiel) {
// retourne la valeur de configuration locale pour cette instance de referentiel
global $CFG;
	if (isset($ref_instance_referentiel) && ($ref_instance_referentiel>0)){
		$config = new object();
		$config = get_record_sql('SELECT printconfig FROM '. $CFG->prefix . 'referentiel WHERE id='.$ref_instance_referentiel);
		if ($config){
			return($config->printconfig);
		}
	}
	return '';
}

// ---------------------------------
function referentiel_ref_get_item_config($item, $ref_referentiel_referentiel, $type='config') {
// retourne la valeur de configuration globale pour l'item considere
// 'scol:0;creref:0;selref:0;impcert:0;refcert:1;instcert:0;numetu:1;nometu:1;etabetu:0;ddnetu:0;lieuetu:0;adretu:0;detail:1;pourcent:0;compdec:0;compval:1;nomreferent:0;jurycert:1;comcert:0;'
// type : config ou printconfig
global $CFG;
	if (isset($ref_referentiel_referentiel) && ($ref_referentiel_referentiel>0)){
		if ($type == 'config'){
			$str_config = get_field('referentiel_referentiel', 'config', 'id', $ref_referentiel_referentiel);
		}
		else{
			$str_config = referentiel_ref_get_vecteur_config_imp($ref_referentiel_referentiel);
		}
		if ($str_config!=''){
			$tconfig=explode(';',$str_config);
			$n=count($tconfig);
			if ($n>0){
				$i=0;
				while ($i<$n){
					$tconfig[$i]=trim($tconfig[$i]);
					if ($tconfig[$i]!=''){
						list($cle, $val)=explode(':',$tconfig[$i]);
						$cle=trim($cle);
						$val=trim($val);

						if ($cle==$item){
							return ($val);
						}
					}
					$i++;
				}
			}
		}
	}
	return 0;
}

// ---------------------------------
function referentiel_get_configuration_item($item, $ref_instance_referentiel, $type='config') {
// retourne la valeur de configuration locale pour l'item considere
// 'scol:0;creref:0;selref:0;impcert:0;refcert:1;instcert:0;numetu:1;nometu:1;etabetu:0;ddnetu:0;lieuetu:0;adretu:0;detail:1;pourcent:0;compdec:0;compval:1;nomreferent:0;jurycert:1;comcert:0;'
// type : config ou printconfig
	global $CFG;

	if (!empty($ref_instance_referentiel)){
		if ($type == 'config'){
			$str_config = get_field('referentiel', 'config', 'id', $ref_instance_referentiel);
		} else {
			$str_config = get_field('referentiel', 'printconfig', 'id', $ref_instance_referentiel);
		}
		if (!empty($str_config)){
			$configs = explode(';', $str_config);
			foreach($configs as $config){
				$config = trim($config);
				if ($config != ''){
					list($key, $value) = explode(':', $config);
					$key = trim($key);
					$val = trim($value);
					if ($key == $item){
						return ($value);
					}
				}
			}
		}
	}
	return 0;
}


// -----------------------
function referentiel_associe_item_param_configuration($param, $item, $value){
// retourne un objet intitialisé
		switch($item){
		// type config
			case 'scol' :	$param->referentiel_scolarite_masquee=$value; break;
			case 'creref' :	$param->referentiel_creation_limitee=$value; break;
			case 'selref' :	$param->referentiel_selection_autorisee=$value; break;
			case 'impcert' : $param->referentiel_impression_autorisee=$value; break;
		// type printconfig
			case 'refcert' : $param->certificate_sel_referentiel=$value; break;
			case 'instcert' : $param->certificate_sel_referentiel_instance=$value; break;
			case 'numetu' : $param->certificate_sel_student_numero=$value; break;
			case 'nometu' : $param->certificate_sel_student_nom_prenom=$value; break;
			case 'etabetu' : $param->certificate_sel_student_etablissement=$value; break;
			case 'ddnetu' : $param->certificate_sel_student_ddn=$value; break;
			case 'lieuetu' : $param->certificate_sel_student_lieu_naissance=$value; break;
			case 'adretu' : $param->certificate_sel_student_adresse=$value; break;
			case 'detail' : $param->certificate_sel_certificate_detail=$value; break;
			case 'pourcent' : $param->certificate_sel_certificate_pourcent=$value; break;
			case 'compdec' : $param->certificate_sel_activite_competences=$value; break;
			case 'compval' : $param->certificate_sel_certificate_competences=$value; break;
			case 'nomreferent' : $param->certificate_sel_certificate_referents=$value; break;
			case 'jurycert' : $param->certificate_sel_decision_jury=$value; break;
			case 'comcert' : $param->certificate_sel_commentaire=$value; break;
		}
		return $param;
}


// ---------------------------------
function referentiel_ref_set_param_config($param, $ref_referentiel_referentiel, $type='config'){
// enregistre la configuration globale
// type config : 'scol:0;creref:0;selref:0;impcert:0;'
// type printconfig : 'refcert:1;instcert:0;numetu:1;nometu:1;etabetu:0;ddnetu:0;lieuetu:0;adretu:0;detail:1;pourcent:0;compdec:0;compval:1;nomreferent:0;jurycert:1;comcert:0;'
//
global $CFG;
$str_config='';
	if (!empty($param) && isset($ref_referentiel_referentiel) && ($ref_referentiel_referentiel>0)){
		if ($type=='config'){
			if (!empty($param->referentiel_scolarite_masquee) && ($param->referentiel_scolarite_masquee==1)) $str_config.='scol:1;'; else $str_config.='scol:0;';
			if (!empty($param->referentiel_creation_limitee) && ($param->referentiel_creation_limitee==1)) $str_config.='creref:1;'; else $str_config.='creref:0;';
			if (!empty($param->referentiel_selection_autorisee) && ($param->referentiel_selection_autorisee==1)) $str_config.='selref:1;'; else $str_config.='selref:0;';
			if (!empty($param->referentiel_impression_autorisee) && ($param->referentiel_impression_autorisee==1)) $str_config.='impcert:1;'; else $str_config.='impcert:0;';
			if ($str_config!='') {
				referentiel_global_set_vecteur_config($str_config, $ref_referentiel_referentiel);
			}
		}
		else{
		// type printconfig
			if (!empty($param->certificate_sel_referentiel) && ($param->certificate_sel_referentiel==1)) $str_config.='refcert:1;';  else $str_config.='refcert:0;';
			if (!empty($param->certificate_sel_referentiel_instance) && ($param->certificate_sel_referentiel_instance==1)) $str_config.='instcert:1;'; else $str_config.='instcert:0;';
			if (!empty($param->certificate_sel_student_numero) && ($param->certificate_sel_student_numero==1)) $str_config.='numetu:1;'; else $str_config.='numetu:0;';
			if (!empty($param->certificate_sel_student_nom_prenom) && ($param->certificate_sel_student_nom_prenom==1)) $str_config.='nometu:1;'; else $str_config.='nometu:0;';
			if (!empty($param->certificate_sel_student_etablissement) && ($param->certificate_sel_student_etablissement==1)) $str_config.='etabetu:1;'; else $str_config.='etabetu:0;';
			if (!empty($param->certificate_sel_student_ddn) && ($param->certificate_sel_student_ddn==1)) $str_config.='ddnetu:1;'; else $str_config.='ddnetu:0;';
			if (!empty($param->certificate_sel_student_lieu_naissance) && ($param->certificate_sel_student_lieu_naissance==1)) $str_config.='lieuetu:1;'; else $str_config.='lieuetu:0;';
			if (!empty($param->certificate_sel_student_adresse) && ($param->certificate_sel_student_adresse==1)) $str_config.='adretu:1'; else $str_config.='adretu:0';
			if (!empty($param->certificate_sel_certificate_detail) && ($param->certificate_sel_certificate_detail==1)) $str_config.='detail:1;'; else $str_config.='detail:0;';
			if (!empty($param->certificate_sel_certificate_pourcent) && ($param->certificate_sel_certificate_pourcent==1)) $str_config.='pourcent:1;'; else $str_config.='pourcent:0;';
			if (!empty($param->certificate_sel_activite_competences) && ($param->certificate_sel_activite_competences==1)) $str_config.='compdec:1;'; else $str_config.='compdec:0;';
			if (!empty($param->certificate_sel_certificate_competences) && ($param->certificate_sel_certificate_competences==1)) $str_config.='compval:1;'; else $str_config.='compval:0;';
			if (!empty($param->certificate_sel_certificate_referents) && ($param->certificate_sel_certificate_referents==1)) $str_config.='nomreferent:1;'; else $str_config.='nomreferent:0;';
			if (!empty($param->certificate_sel_decision_jury) && ($param->certificate_sel_decision_jury==1)) $str_config.='jurycert:1;'; else $str_config.='jurycert:0;';
			if (!empty($param->certificate_sel_commentaire) && ($param->certificate_sel_commentaire==1)) $str_config.='comcert:1;'; else $str_config.='comcert:0;';
			if ($str_config!='') {
				referentiel_global_set_vecteur_config_imp($str_config, $ref_referentiel_referentiel);
			}
		}
		// DEBUG
		// echo "<br />DEBUG :: lib_config.php :: 815 :: $str_config\n";
	}
}


// ---------------------------------
function referentiel_set_param_configuration($param, $ref_instance_referentiel, $type='config'){
// enregistre la configuration locale
// type config : 'scol:0;creref:0;selref:0;impcert:0;'
// type printconfig : 'refcert:1;instcert:0;numetu:1;nometu:1;etabetu:0;ddnetu:0;lieuetu:0;adretu:0;detail:1;pourcent:0;compdec:0;compval:1;nomreferent:0;jurycert:1;comcert:0;'
//
global $CFG;
$str_config='';
	if (!empty($param) && isset($ref_instance_referentiel) && ($ref_instance_referentiel>0)){
		if ($type=='config'){
			if (!empty($param->referentiel_scolarite_masquee) && ($param->referentiel_scolarite_masquee==1)) $str_config.='scol:1;'; else $str_config.='scol:0;';
			if (!empty($param->referentiel_creation_limitee) && ($param->referentiel_creation_limitee==1)) $str_config.='creref:1;'; else $str_config.='creref:0;';
			if (!empty($param->referentiel_selection_autorisee) && ($param->referentiel_selection_autorisee==1)) $str_config.='selref:1;'; else $str_config.='selref:0;';
			if (!empty($param->referentiel_impression_autorisee) && ($param->referentiel_impression_autorisee==1)) $str_config.='impcert:1;'; else $str_config.='impcert:0;';
			if ($str_config!='') {
				referentiel_set_vecteur_configuration($str_config, $ref_instance_referentiel);
			}
		}
		else{
		// type printconfig
			if (!empty($param->certificate_sel_referentiel) && ($param->certificate_sel_referentiel==1)) $str_config.='refcert:1;';  else $str_config.='refcert:0;';
			if (!empty($param->certificate_sel_referentiel_instance) && ($param->certificate_sel_referentiel_instance==1)) $str_config.='instcert:1;'; else $str_config.='instcert:0;';
			if (!empty($param->certificate_sel_student_numero) && ($param->certificate_sel_student_numero==1)) $str_config.='numetu:1;'; else $str_config.='numetu:0;';
			if (!empty($param->certificate_sel_student_nom_prenom) && ($param->certificate_sel_student_nom_prenom==1)) $str_config.='nometu:1;'; else $str_config.='nometu:0;';
			if (!empty($param->certificate_sel_student_etablissement) && ($param->certificate_sel_student_etablissement==1)) $str_config.='etabetu:1;'; else $str_config.='etabetu:0;';
			if (!empty($param->certificate_sel_student_ddn) && ($param->certificate_sel_student_ddn==1)) $str_config.='ddnetu:1;'; else $str_config.='ddnetu:0;';
			if (!empty($param->certificate_sel_student_lieu_naissance) && ($param->certificate_sel_student_lieu_naissance==1)) $str_config.='lieuetu:1;'; else $str_config.='lieuetu:0;';
			if (!empty($param->certificate_sel_student_adresse) && ($param->certificate_sel_student_adresse==1)) $str_config.='adretu:1'; else $str_config.='adretu:0';
			if (!empty($param->certificate_sel_certificate_detail) && ($param->certificate_sel_certificate_detail==1)) $str_config.='detail:1;'; else $str_config.='detail:0;';
			if (!empty($param->certificate_sel_certificate_pourcent) && ($param->certificate_sel_certificate_pourcent==1)) $str_config.='pourcent:1;'; else $str_config.='pourcent:0;';
			if (!empty($param->certificate_sel_activite_competences) && ($param->certificate_sel_activite_competences==1)) $str_config.='compdec:1;'; else $str_config.='compdec:0;';
			if (!empty($param->certificate_sel_certificate_competences) && ($param->certificate_sel_certificate_competences==1)) $str_config.='compval:1;'; else $str_config.='compval:0;';
			if (!empty($param->certificate_sel_certificate_referents) && ($param->certificate_sel_certificate_referents==1)) $str_config.='nomreferent:1;'; else $str_config.='nomreferent:0;';
			if (!empty($param->certificate_sel_decision_jury) && ($param->certificate_sel_decision_jury==1)) $str_config.='jurycert:1;'; else $str_config.='jurycert:0;';
			if (!empty($param->certificate_sel_commentaire) && ($param->certificate_sel_commentaire==1)) $str_config.='comcert:1;'; else $str_config.='comcert:0;';
			if ($str_config!='') {
				referentiel_set_vecteur_configuration_impression($str_config, $ref_instance_referentiel);
			}
		}
		// DEBUG
		// echo "<br />DEBUG :: lib.php :: 3922 :: $str_config\n";
	}
}

// ---------------------------------
function referentiel_ref_get_param_config($ref_referentiel_referentiel, $type='config') {

// retourne la valeur de configuration globale sous forme d'un objet
// type config : 'scol:0;creref:0;selref:0;impcert:0;'
// type printconfig : 'refcert:1;instcert:0;numetu:1;nometu:1;etabetu:0;ddnetu:0;lieuetu:0;adretu:0;detail:1;pourcent:0;compdec:0;compval:1;nomreferent:0;jurycert:1;comcert:0;'
//
global $CFG;
$param = new Object();
	if (isset($ref_referentiel_referentiel) && ($ref_referentiel_referentiel>0)){
		if ($type=='config'){
			$str_config = get_field('referentiel_referentiel', 'config', 'id', $ref_referentiel_referentiel);
		}
		else{
			$str_config = referentiel_ref_get_vecteur_config_imp($ref_referentiel_referentiel);
		}
		if ($str_config!=''){
			$tconfig=explode(';',$str_config);
			$n=count($tconfig);
			if ($n>0){
				$i=0;
				while ($i<$n){
					$tconfig[$i]=trim($tconfig[$i]);
					if ($tconfig[$i]!=''){
						list($cle, $val)=explode(':',$tconfig[$i]);
						$cle=trim($cle);
						$val=trim($val);
						$param=referentiel_associe_item_param_configuration($param, $cle, $val);
					}
					$i++;
				}
			}
		}
	}

	return $param;
}

// ---------------------------------
function referentiel_get_param_configuration($ref_instance_referentiel, $type='config') {
// retourne la valeur de configuration locale sous forme d'un objet
// type config : 'scol:0;creref:0;selref:0;impcert:0;'
// type printconfig : 'refcert:1;instcert:0;numetu:1;nometu:1;etabetu:0;ddnetu:0;lieuetu:0;adretu:0;detail:1;pourcent:0;compdec:0;compval:1;nomreferent:0;jurycert:1;comcert:0;'
//
global $CFG;
$param = new Object();
	if (isset($ref_instance_referentiel) && ($ref_instance_referentiel>0)){
		if ($type=='config'){
			$str_config = referentiel_get_vecteur_configuration($ref_instance_referentiel);
		}
		else{
			$str_config = referentiel_get_vecteur_configuration_impression($ref_instance_referentiel);
		}
		if ($str_config!=''){
			$tconfig=explode(';',$str_config);
			$n=count($tconfig);
			if ($n>0){
				$i=0;
				while ($i<$n){
					$tconfig[$i]=trim($tconfig[$i]);
					if ($tconfig[$i]!=''){
						list($cle, $val)=explode(':',$tconfig[$i]);
						$cle=trim($cle);
						$val=trim($val);
						$param=referentiel_associe_item_param_configuration($param, $cle, $val);
					}
					$i++;
				}
			}
		}
	}

	return $param;
}

?>
