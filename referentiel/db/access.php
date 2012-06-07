<?php
//
// Capability definitions for the referentiel module.
// recopie de data module
//
// The capabilities are loaded into the database table when the module is
// installed or updated. Whenever the capability definitions are updated,
// the module version number should be bumped up.
//
// The system has four possible values for a capability:
// CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
//
//
// CAPABILITY NAMING CONVENTION
//
// It is important that capability names are unique. The naming convention
// for capabilities that are specific to modules and blocks is as follows:
//   [mod/block]/<component_name>:<capabilityname>
//
// component_name should be the same as the directory name of the mod or block.
//
// Core moodle capabilities are defined thus:
//    moodle/<capabilityclass>:<capabilityname>
//
// Examples: mod/forum:viewpost
//           block/recent_activity:view
//           moodle/site:deleteuser
//
// The variable name for the capability definitions array follows the format
//   $<componenttype>_<component_name>_capabilities
//
// For the core capabilities, the variable is $moodle_capabilities.


$mod_referentiel_capabilities = array(

    'mod/referentiel:view' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'guest' => CAP_ALLOW,
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
			'coursecreator' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),

    'mod/referentiel:write' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
			'coursecreator' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),


    'mod/referentiel:addactivity' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW
        )
    ),

    'mod/referentiel:comment' => array(

        'riskbitmask' => RISK_SPAM,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
			'coursecreator' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),


    'mod/referentiel:managecomments' => array(

        'riskbitmask' => RISK_SPAM,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
			'coursecreator' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),

    'mod/referentiel:managecertif' => array(

        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
			'coursecreator' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),

    'mod/referentiel:exportcertif' => array(

        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
        'admin' => CAP_ALLOW
        )
    ),

    'mod/referentiel:viewrate' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
			'coursecreator' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),

    'mod/referentiel:rate' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
			'coursecreator' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),

    'mod/referentiel:approve' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
			'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
			'admin' => CAP_ALLOW
        )
    ),


	'mod/referentiel:writereferentiel' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'coursecreator' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),

	'mod/referentiel:addtask' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
			'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
			'coursecreator' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),

	    'mod/referentiel:viewtask' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'guest' => CAP_ALLOW,
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
			'coursecreator' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),

	    'mod/referentiel:selecttask' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'student' => CAP_ALLOW
        )
    ),


    'mod/referentiel:export' => array(

        'riskbitmask' => RISK_XSS,

        'captype' => 'edit',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
			'admin' => CAP_ALLOW
        )
    ),

	    'mod/referentiel:import' => array(

        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
			'editingteacher' => CAP_ALLOW,		
			'coursecreator' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),

	'mod/referentiel:viewscolarite' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
			'coursecreator' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),

	'mod/referentiel:managescolarite' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
			'coursecreator' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),


	    'mod/referentiel:select' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
			'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
			'coursecreator' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    )
);

?>
