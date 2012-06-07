<?php 

require_once($CFG->dirroot.'/mod/referentiel/lib.php');

// Valeurs par defaut
// scol:0;creref:0;selref:0;impcert:0;refcert:1;instcert:0;numetu:1;nometu:1;etabetu:0;ddnetu:0;lieuetu:0;adretu:0;pourcent:0;compdec:0;compval:1;refcert:0;jurycert:1;comcert:0;


$options = array();
$options[0] = 0;
$options[1] = 1;
$options[2] = 2;
// scolarite
if (isset($CFG->referentiel_scolarite_masquee)){
    $settings->add(new admin_setting_configselect('referentiel_scolarite_masquee', get_string('scolarite_user', 'referentiel'),
                   get_string('config_scolarite', 'referentiel'), $CFG->referentiel_scolarite_masquee, $options));
}
else{
    $settings->add(new admin_setting_configselect('referentiel_scolarite_masquee', get_string('scolarite_user', 'referentiel'),
                   get_string('config_scolarite', 'referentiel'), 0, $options));
}
// creation de referentiels
unset($options);
$options[0] = 0;
$options[1] = 1;
$options[2] = 2;
if (isset($CFG->referentiel_creation_limitee)){
    $settings->add(new admin_setting_configselect('referentiel_creation_limitee', get_string('create_referentiel', 'referentiel'),
                   get_string('config_creer_referentiel', 'referentiel'), $CFG->referentiel_creation_limitee, $options));
}
else{
    $settings->add(new admin_setting_configselect('referentiel_creation_limitee', get_string('create_referentiel', 'referentiel'),
                   get_string('config_creer_referentiel', 'referentiel'), 0, $options));
}
// selection de referentiels
unset($options);
$options[0] = 0;
$options[1] = 1;
$options[2] = 2;
if (isset($CFG->referentiel_selection_autorisee)){
    $settings->add(new admin_setting_configselect('referentiel_selection_autorisee', get_string('select_referentiel', 'referentiel'),
                   get_string('config_select_referentiel', 'referentiel'), $CFG->referentiel_selection_autorisee, $options));
}
else{
    $settings->add(new admin_setting_configselect('referentiel_selection_autorisee', get_string('select_referentiel', 'referentiel'),
                   get_string('config_select_referentiel', 'referentiel'), 0, $options));
}
// impression des certificats
unset($options);
$options[0] = 0;
$options[1] = 1;
$options[2] = 2;
if (isset($CFG->referentiel_impression_autorisee)){
    $settings->add(new admin_setting_configselect('referentiel_impression_autorisee', get_string('referentiel_impression_autorisee', 'referentiel'),
                   get_string('config_impression_referentiel', 'referentiel'), $CFG->referentiel_impression_autorisee, $options));
}
else{
    $settings->add(new admin_setting_configselect('referentiel_impression_autorisee', get_string('referentiel_impression_autorisee', 'referentiel'),
                   get_string('config_impression_referentiel', 'referentiel'), 0, $options));
}

unset($options);
$options[0] = 1;
$options[1] = 0;
if (isset($CFG->certificate_sel_referentiel)){
    $settings->add(new admin_setting_configselect('certificate_sel_referentiel', get_string('certificate_sel_referentiel', 'referentiel'),
                   get_string('config_certificate_sel_referentiel', 'referentiel'), $CFG->certificate_sel_referentiel, $options));
}
else{
    $settings->add(new admin_setting_configselect('certificate_sel_referentiel', get_string('certificate_sel_referentiel', 'referentiel'),
                   get_string('config_certificate_sel_referentiel', 'referentiel'), 0, $options));
}

unset($options);
$options[0] = 0;
$options[1] = 1;
if (isset($CFG->certificate_sel_referentiel_instance) ){
    $settings->add(new admin_setting_configselect('certificate_sel_referentiel_instance', get_string('certificate_sel_referentiel_instance', 'referentiel'),
                   get_string('config_certificate_sel_referentiel_instance', 'referentiel'), $CFG->certificate_sel_referentiel_instance, $options));
}
else{
    $settings->add(new admin_setting_configselect('certificate_sel_referentiel_instance', get_string('certificate_sel_referentiel_instance', 'referentiel'),
                   get_string('config_certificate_sel_referentiel_instance', 'referentiel'), 0, $options));
}

unset($options);
$options[0] = 1;
$options[1] = 0;
if (isset($CFG->certificate_sel_student_numero)){
    $settings->add(new admin_setting_configselect('certificate_sel_student_numero', get_string('certificate_sel_student_numero', 'referentiel'),
                   get_string('config_certificate_sel_student_numero', 'referentiel'), $CFG->certificate_sel_student_numero, $options));
}
else{
    $settings->add(new admin_setting_configselect('certificate_sel_student_numero', get_string('certificate_sel_student_numero', 'referentiel'),
                   get_string('config_certificate_sel_student_numero', 'referentiel'), 0, $options));
}
				 
unset($options);
$options[0] = 1;
$options[1] = 0;
if (isset($CFG->certificate_sel_student_nom_prenom) ){
    $settings->add(new admin_setting_configselect('certificate_sel_student_nom_prenom', get_string('certificate_sel_student_nom_prenom', 'referentiel'),
                   get_string('config_certificate_sel_student_nom_prenom', 'referentiel'), $CFG->certificate_sel_student_nom_prenom, $options));
}
else{
    $settings->add(new admin_setting_configselect('certificate_sel_student_nom_prenom', get_string('certificate_sel_student_nom_prenom', 'referentiel'),
                   get_string('config_certificate_sel_student_nom_prenom', 'referentiel'), 0, $options));
}

unset($options);
$options[0] = 0;
$options[1] = 1;
if (isset($CFG->certificate_sel_student_etablissement) ){
    $settings->add(new admin_setting_configselect('certificate_sel_student_etablissement', get_string('certificate_sel_student_etablissement', 'referentiel'),
                   get_string('config_certificate_sel_student_etablissement', 'referentiel'), $CFG->certificate_sel_student_etablissement, $options));
}
else{
    $settings->add(new admin_setting_configselect('certificate_sel_student_etablissement', get_string('certificate_sel_student_etablissement', 'referentiel'),
                   get_string('config_certificate_sel_student_etablissement', 'referentiel'), 0, $options));
}

unset($options);
$options[0] = 0;
$options[1] = 1;
if (isset($CFG->certificate_sel_student_ddn) ){
    $settings->add(new admin_setting_configselect('certificate_sel_student_ddn', get_string('certificate_sel_student_ddn', 'referentiel'),
                   get_string('config_certificate_sel_student_ddn', 'referentiel'), $CFG->certificate_sel_student_ddn, $options));
}
else{
    $settings->add(new admin_setting_configselect('certificate_sel_student_ddn', get_string('certificate_sel_student_ddn', 'referentiel'),
                   get_string('config_certificate_sel_student_ddn', 'referentiel'), 0, $options));
}

unset($options);
$options[0] = 0;
$options[1] = 1;
if (isset($CFG->certificate_sel_student_lieu_naissance)){
    $settings->add(new admin_setting_configselect('certificate_sel_student_lieu_naissance', get_string('certificate_sel_student_lieu_naissance', 'referentiel'),
                   get_string('config_certificate_sel_student_lieu_naissance', 'referentiel'), $CFG->certificate_sel_student_lieu_naissance, $options));
}
else{
    $settings->add(new admin_setting_configselect('certificate_sel_student_lieu_naissance', get_string('certificate_sel_student_lieu_naissance', 'referentiel'),
                   get_string('config_certificate_sel_student_lieu_naissance', 'referentiel'), 0, $options));
}

unset($options);
$options[0] = 0;
$options[1] = 1;
if (isset($CFG->certificate_sel_student_adresse)){
    $settings->add(new admin_setting_configselect('certificate_sel_student_adresse', get_string('certificate_sel_student_adresse', 'referentiel'),
                   get_string('config_certificate_sel_student_adresse', 'referentiel'), $CFG->certificate_sel_student_adresse, $options));
}
else{
    $settings->add(new admin_setting_configselect('certificate_sel_student_adresse', get_string('certificate_sel_student_adresse', 'referentiel'),
                   get_string('config_certificate_sel_student_adresse', 'referentiel'), 0, $options));
}

unset($options);
$options[0] = 1;
$options[1] = 0;
if (isset($CFG->certificate_sel_certificate_detail)){
    $settings->add(new admin_setting_configselect('certificate_sel_certificate_detail', get_string('certificate_sel_certificate_detail', 'referentiel'),
                   get_string('config_certificate_sel_certificate_detail', 'referentiel'), $CFG->certificate_sel_certificate_detail, $options));
}
else{
    $settings->add(new admin_setting_configselect('certificate_sel_certificate_detail', get_string('certificate_sel_certificate_detail', 'referentiel'),
                   get_string('config_certificate_sel_certificate_detail', 'referentiel'), 0, $options));
}

unset($options);
$options[0] = 0;
$options[1] = 1;
if (isset($CFG->certificate_sel_certificate_pourcent)){
    $settings->add(new admin_setting_configselect('certificate_sel_certificate_pourcent', get_string('certificate_sel_certificate_pourcent', 'referentiel'),
                   get_string('config_certificate_sel_certificate_pourcent', 'referentiel'), $CFG->certificate_sel_certificate_pourcent, $options));
}
else{
    $settings->add(new admin_setting_configselect('certificate_sel_certificate_pourcent', get_string('certificate_sel_certificate_pourcent', 'referentiel'),
                   get_string('config_certificate_sel_certificate_pourcent', 'referentiel'), 0, $options));
}

unset($options);
$options[0] = 0;
$options[1] = 1;
if (isset($CFG->certificate_sel_activite_competences) ){
    $settings->add(new admin_setting_configselect('certificate_sel_activite_competences', get_string('certificate_sel_activite_competences', 'referentiel'),
                   get_string('config_certificate_sel_activite_competences', 'referentiel'), $CFG->certificate_sel_activite_competences, $options));
}
else{
    $settings->add(new admin_setting_configselect('certificate_sel_activite_competences', get_string('certificate_sel_activite_competences', 'referentiel'),
                   get_string('config_certificate_sel_activite_competences', 'referentiel'), 0, $options));
}

unset($options);
$options[0] = 1;
$options[1] = 0;
if (isset($CFG->certificate_sel_certificate_competences)){
    $settings->add(new admin_setting_configselect('certificate_sel_certificate_competences', get_string('certificate_sel_certificate_competences', 'referentiel'),
                   get_string('config_certificate_sel_certificate_competences', 'referentiel'), $CFG->certificate_sel_certificate_competences, $options));
}
else{
    $settings->add(new admin_setting_configselect('certificate_sel_certificate_competences', get_string('certificate_sel_certificate_competences', 'referentiel'),
                   get_string('config_certificate_sel_certificate_competences', 'referentiel'), 0, $options));
}

unset($options);
$options[0] = 0;
$options[1] = 1;
if (isset($CFG->certificate_sel_certificate_referents)){
    $settings->add(new admin_setting_configselect('certificate_sel_certificate_referents', get_string('certificate_sel_certificate_referents', 'referentiel'),
                   get_string('config_certificate_sel_certificate_referents', 'referentiel'), $CFG->certificate_sel_certificate_referents, $options));
}
else{
    $settings->add(new admin_setting_configselect('certificate_sel_certificate_referents', get_string('certificate_sel_certificate_referents', 'referentiel'),
                   get_string('config_certificate_sel_certificate_referents', 'referentiel'), 0, $options));
}

unset($options);
$options[0] = 1;
$options[1] = 0;
if (isset($CFG->certificate_sel_decision_jury)){
    $settings->add(new admin_setting_configselect('certificate_sel_decision_jury', get_string('certificate_sel_decision_jury', 'referentiel'),
                   get_string('config_certificate_sel_decision_jury', 'referentiel'), $CFG->certificate_sel_decision_jury, $options));
}
else{
    $settings->add(new admin_setting_configselect('certificate_sel_decision_jury', get_string('certificate_sel_decision_jury', 'referentiel'),
                   get_string('config_certificate_sel_decision_jury', 'referentiel'), 0, $options));
}

unset($options);
$options[0] = 0;
$options[1] = 1;
if (isset($CFG->certificate_sel_commentaire)){
    $settings->add(new admin_setting_configselect('certificate_sel_commentaire', get_string('certificate_sel_commentaire', 'referentiel'),
                   get_string('config_certificate_sel_commentaire', 'referentiel'), $CFG->certificate_sel_commentaire, $options));
}
else{
    $settings->add(new admin_setting_configselect('certificate_sel_commentaire', get_string('certificate_sel_commentaire', 'referentiel'),
                   get_string('config_certificate_sel_commentaire', 'referentiel'), 0, $options));
}
?>