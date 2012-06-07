<?php // $Id: index.php,v 1.18.2.3 2006/09/29 20:53:09 skodak Exp $

/**
 * file index.php
 * index page to view biis. if no bii is specified then site wide entries are shown
 * if a bii id is specified then the latest entries from that bii are shown
 */

// ACTIVITES</td>export des activites
class pprint_pdf extends pprint_default {

    function provide_print() {
      return true;
    }


}



?>