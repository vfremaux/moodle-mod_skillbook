<?php // $Id: pagelib.php,v 1.8 2007/01/04 18:23:58 skodak Exp $
// Reprise du module data
require_once($CFG->libdir.'/pagelib.php');
require_once($CFG->dirroot.'/course/lib.php'); // needed for some blocks

define('PAGE_REFERENTIEL_VIEW',   'mod-referentiel-view');


page_map_class(PAGE_REFERENTIEL_VIEW, 'page_referentiel');


$DEFINEDPAGES = array(PAGE_REFERENTIEL_VIEW);
/*
*/

/**
 * Class that models the behavior of a referentiel
 *
 * @author Jon Papaioannou
 * @package pages
 */

class page_referentiel extends page_generic_activity {

    function init_quick($referentiel) {
        if(empty($referentiel->pageid)) {
            print_error('Cannot quickly initialize page: empty course id');
        }
        $this->activityname = 'referentiel';
        parent::init_quick($referentiel);
    }

    function print_header($title, $morebreadcrumbs = NULL, $meta) {
        global $USER, $CFG;

        $this->init_full();
        $replacements = array(
            '%fullname%' => format_string($this->activityrecord->name)
        );
        foreach($replacements as $search => $replace) {
            $title = str_replace($search, $replace, $title);
        }

        if($this->courserecord->id == SITEID) {
            $breadcrumbs = array();
        }
        else {
            $breadcrumbs = array($this->courserecord->shortname => $CFG->wwwroot.'/course/view.php?id='.$this->courserecord->id);
        }

        $breadcrumbs[get_string('modulenameplural', 'referentiel')] = $CFG->wwwroot.'/mod/referentiel/index.php?id='.$this->courserecord->id;
        $breadcrumbs[format_string($this->activityrecord->name)]            = $CFG->wwwroot.'/mod/referentiel/view.php?id='.$this->modulerecord->id;

        if(!empty($morebreadcrumbs)) {
            $breadcrumbs = array_merge($breadcrumbs, $morebreadcrumbs);
        }

        $total     = count($breadcrumbs);
        $current   = 1;
        $crumbtext = '';
        foreach($breadcrumbs as $text => $href) {
            if($current++ == $total) {
                $crumbtext .= ' '.$text;
            }
            else {
                $crumbtext .= ' <a href="'.$href.'">'.$text.'</a> ->';
            }
        }

        if(empty($morebreadcrumbs) && $this->user_allowed_editing()) {
            $buttons = '<table><tr><td>'.update_module_button($this->modulerecord->id, $this->courserecord->id, get_string('modulename', 'referentiel')).'</td>';
            if(!empty($CFG->showblocksonmodpages)) {
                $buttons .= '<td><form '.$CFG->frametarget.' method="get" action="view.php">'.
                    '<input type="hidden" name="id" value="'.$this->modulerecord->id.'" />'.
                    '<input type="hidden" name="edit" value="'.($this->user_is_editing()?'off':'on').'" />'.
                    '<input type="submit" value="'.get_string($this->user_is_editing()?'blockseditoff':'blocksediton').'" /></form></td>';
            }
            $buttons .= '</tr></table>';
        }
        else {
            $buttons = '&nbsp;';
        }
        print_header($title, $this->courserecord->fullname, $crumbtext, '', $meta, true, $buttons, navmenu($this->courserecord, $this->modulerecord));

    }

    function get_type() {
        return PAGE_REFERENTIEL_VIEW;
    }
}

?>
