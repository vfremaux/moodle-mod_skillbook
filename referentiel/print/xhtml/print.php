<?php 
// Based on default.php, included by ../import.php


// ACTIVITES</td><td class='referentiel'>export des activites
class pprint_xhtml extends pprint_default {

	function provide_print() {
      return true;
    }

	function repchar( $text ) {
	    // escapes 'reserved' characters # = ~ { ) and removes new lines
    	$reserved = array( '#','=','~','{','}',"\n","\r" );
	    $escaped = array( '\#','\=','\~','\{','\}',' ','' );
		return str_replace( $reserved, $escaped, $text ); 
	}

	function presave_process( $content ) {
	  // override method to allow us to add xhtml headers and footers
  	global $CFG;

  	// get css bit
		$css_lines = file( "$CFG->dirroot/mod/referentiel/print/xhtml/xhtml.css" );
		$css = implode( ' ',$css_lines ); 
		$xp =  "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n";
		$xp .= "  \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
		$xp .= "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n";
  	$xp .= "<head>\n";
  	$xp .= "<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\" />\n";
  	$xp .= "<title>Moodle Referentiel :: Certificats XHTML Export</title>\n";
  	$xp .= $css;
  	$xp .= "</head>\n";
		$xp .= "<body>\n";
		$xp .= $content;
		$xp .= "</body>\n";
		$xp .= "</html>\n";

  	return $xp;
	}

	function export_file_extension() {
  		return ".html";
	}

    /**
     * Include an image encoded in base 64
     * @param string imagepath The location of the image file
     * @return string xml code segment 
     */
    function writeimage( $imagepath ) {
        global $CFG;
   		
        if (empty($imagepath)) {
            return '';
        }

        $courseid = $this->course->id;
        if (!$binary = file_get_contents( "{$CFG->dataroot}/$courseid/$imagepath" )) {
            return '';
        }

        $content = "    <image_base64>\n".addslashes(base64_encode( $binary ))."\n".
            "\n    </image_base64>\n";
        return $content;
    }

	
	/**
     * generates <text></text> tags, processing raw text therein 
     * @param int ilev the current indent level
     * @param boolean short stick it on one line
     * @return string printted text
     */

    function write_ligne( $raw, $sep="/", $nmaxcar=80) {
        // insere un saut de ligne apres le 80 caracter 
		$nbcar=strlen($raw);
		if ($nbcar>$nmaxcar){
			$s1=substr( $raw,0,$nmaxcar);
			$pos1=strrpos($s1,$sep);
			if ($pos1>0){
				$s1=substr( $raw,0,$pos1);
				$s2=substr( $raw,$pos1+1);
			}
			else {
				$s1=substr( $raw,0,$nmaxcar);
				$s2=substr( $raw,$nmaxcar);
			}
		    return $s1." ".$s2;
		}
		else{
			return $raw;
		}
    }


		function write_etablissement( $record, $nbchamps_referentiel ) {
        // initial string;
        $expout = "";
        // add comment
        $expout .= "\n\n<!-- etablissement: $record->id  -->\n";
		if ($record){
			$expout .= "<tr class='referentiel'><td class='referentiel' colspan='".$nbchamps_referentiel."'><i>".get_string('etablissement','referentiel')."</i></td></tr>\n";
    		$expout .= "<tr class='referentiel'><td class='referentiel' colspan='".$nbchamps_referentiel."'>\n";

			$id = trim( $record->id );
			$idnumber = trim( $record->idnumber);
			$name = trim( $record->name);
			$address = trim( $record->address);
			$logo = trim( $record->logo);
						
    		$expout .= "<table class='referentiel'>\n";
			$expout .= "<tr class='referentiel'>\n";
			$expout .= " <th class='referentiel'>".get_string('idnumber','referentiel')."</th>\n";
            $expout .= " <th class='referentiel' colspan='2'>".get_string('name','referentiel')."</th>\n";
            $expout .= " <th class='referentiel'>".get_string('logo','referentiel')."</th>\n";
            $expout .= " <th class='referentiel' colspan='3'>".get_string('address','referentiel')."</th>\n";
			$expout .= " </tr>\n";
			$expout .= "<tr class='referentiel'>\n";
            $expout .= " <td class='referentiel'>$idnumber</td>\n";
            $expout .= " <td class='referentiel' colspan='2'>$name</td>\n";			
			if ($logo){
            	$expout .= " <td class='referentiel'><img src='$logo' border='0' alt='logo'></td>\n";
			}
			else{
            	$expout .= " <td class='referentiel'>&nbsp;</td>\n";
			}
            $expout .= " <td class='referentiel' colspan='3'>$address</td>\n";
			$expout .= " </tr>\n";
			$expout .= "</table>\n";
			$expout .= "</td></tr>\n";
        }
        return $expout;
    }


	
	function write_student( $record , $nbchamps_referentiel) {
        // initial string;
        $s1='';
		$s2='';
		$nbchamps=0;
		$expout = "";
        // add comment

		if ($record){
			// DEBUG
			// echo "<br />\n";
			// print_r($record);
	    	// add header
			//
			$id = trim( $record->id );
			$userid = trim( $record->userid );
            $ref_etablissement = trim( $record->ref_etablissement);
			$num_student = trim( $record->num_student);
			$ddn_student = trim( $record->ddn_student);
			$lieu_naissance = trim( $record->lieu_naissance);
			$departement_naissance = trim( $record->departement_naissance);
			$adresse_student = trim( $record->adresse_student);			

			if ($this->certificate_sel_param->certificate_sel_student_nom_prenom){
				$nbchamps++;
				$s2 .= "<th class='referentiel'>".get_string('lastname')." ".get_string('firstname')."</th>\n";
			}
			if ($this->certificate_sel_param->certificate_sel_student_numero){
				$nbchamps++;
				$s2 .= "<th class='referentiel'>".get_string('num_student','referentiel')."</th>\n";
			}
			if ($this->certificate_sel_param->certificate_sel_student_ddn){
				$nbchamps++;
				$s2 .= "<th class='referentiel'>".get_string('ddn_student','referentiel')."</th>\n";
			}
			if ($this->certificate_sel_param->certificate_sel_student_lieu_naissance){
				$s2.= "<th class='referentiel'>".get_string('lieu_naissance','referentiel')."</th>\n";
				$s2.= "<th class='referentiel'>".get_string('departement_naissance','referentiel')."</th>\n";
				$nbchamps+=2;
			}
			if ($this->certificate_sel_param->certificate_sel_student_adresse){
				$nbchamps++;
				$s2.= "<th class='referentiel' colspan='2'>".get_string('adresse_student','referentiel')."</th>\n";
			}
			$s1 .= "\n\n<!-- student: $record->id  -->\n";			
			$s1 .= "<tr class='referentiel'><td class='referentiel' colspan='".$nbchamps_referentiel."'><b>".get_string('student','referentiel')."</b></td></tr>\n";
    		$s1 .= "<tr class='referentiel'><td class='referentiel' colspan='".$nbchamps_referentiel."'>\n";
			$s1 .= "<table class='referentiel'>\n<tr class='referentiel'>\n";
			$s1.=$s2;
			$s1 .= "</tr>\n<tr class='referentiel'>\n";
			
			$s2='';
			if ($this->certificate_sel_param->certificate_sel_student_nom_prenom){
				$s2 .= " <td class='referentiel'>".referentiel_get_user_info($record->userid)."</td>\n";
			}

			if ($this->certificate_sel_param->certificate_sel_student_numero){
				$s2 .= " <td class='referentiel'>$num_student</td>\n";
			}
			if ($this->certificate_sel_param->certificate_sel_student_ddn){
				$s2 .= " <td class='referentiel'>$ddn_student</td>\n";
			}
			if ($this->certificate_sel_param->certificate_sel_student_lieu_naissance){
	            $s2 .= " <td class='referentiel'>$lieu_naissance</td>\n";
    			$s2 .= " <td class='referentiel'>$departement_naissance</td>\n";			
            }
			if ($this->certificate_sel_param->certificate_sel_student_adresse){
				$s2 .= " <td class='referentiel' colspan='2'>$adresse_student</td>\n";
			}
			$s1.=$s2;
			$s1 .= " </tr>\n";
			$s1 .= "</table>\n";
			$s1 .= "</td></tr>\n";
			// Etablissement
			$record_etablissement=referentiel_get_etablissement($record->ref_etablissement);
	    	if ($record_etablissement){
				if ($this->certificate_sel_param->certificate_sel_student_etablissement){
					$s1 .= $this->write_etablissement( $record_etablissement, $nbchamps_referentiel);
				}
			}
			$expout.=$s1;
        }
        return $expout;
    }

	
	 /**
     * Turns referentiel instance into an xml segment
     * @param referentiel instanceobject
     * @return string xml segment
     */

    function write_certificat( $record) {
    	global $CFG;
        // initial string;
        $s1='';
		$s2='';
		$nbchamps=0;
		$expout = "";

    	// add comment and div tags
		if ($record){
			// DEBUG
			// echo "<br />DEBUG LIGNE 1021<br />\n";
			// print_r($referentiel_instance);
      $id = trim( $record->id );
      $comment = trim($record->comment);
      $synthese_certificate = trim($record->synthese_certificat);
      $competences_certificate =  trim($record->competences_certificat) ;
      $comptencies = trim($record->comptencies);
      $decision_jury = trim($record->decision_jury);
      if ($record->date_decision){
	       $date_decision = userdate(trim($record->date_decision));
      }
			else{
			   $date_decision ="";
			}
      $userid = trim( $record->userid);
      $teacherid = trim( $record->teacherid);
			if ($teacherid!=0){
				$nom_prenom_teacher=referentiel_get_user_info($teacherid);
			}
			else{
				$nom_prenom_teacher="";
			}
      $referentielid = trim( $record->referentielid);
			$verrou = trim( $record->verrou );
			$valide = trim( $record->valide );
			$evaluation = trim( $record->evaluation );
			
			$pourcentages='';
			// calcul des pourcentages
			if ($this->certificate_sel_param->certificate_sel_certificate_pourcent){
  			if (isset($verrou) && ($verrou!="")) {
          if ($verrou!=0){
		  		  $bgcolor='verrouille';
			    }
			    else{
				    $bgcolor='deverrouille';;
			    }
		    }
		    else{
			   $bgcolor='deverrouille';
		    }
		    // Tableau
		    $pourcentages=referentiel_retourne_certificate_consolide('/',':',$competences_certificat, $referentielid, ' class="'.$bgcolor.'"');
		  }
		  
			 // USER
			if (isset($record->userid) && ($record->userid>0)){
				$record_student = referentiel_get_student_user($record->userid);
		    	if ($record_student){
					$s2='';
					if ($this->certificate_sel_param->certificate_sel_decision_jury){
						$s2 .= "<th class='referentiel'>".get_string('decision','referentiel')."</th>\n";
						$s2 .= "<th class='referentiel'>".get_string('date decision','referentiel')."</th>\n";
						$nbchamps+=2;
					}
					if ($this->certificate_sel_param->certificate_sel_certificate_referents){
						$s2 .= "<th class='referentiel'>".get_string('valide_par','referentiel')."</th>\n";
						$nbchamps++;
					}
					if ($this->certificate_sel_param->certificate_sel_certificate_detail){
						$s2 .= "<th class='referentiel'>".get_string('verrou','referentiel')."</th>\n";
						$s2 .= "<th class='referentiel'>".get_string('evaluation','referentiel')."</th>\n";
						$nbchamps+=2;
					}

					if ($this->certificate_sel_param->certificate_sel_commentaire){
						$s2 .= "<th class='referentiel'>".get_string('commentaire','referentiel')."</th>\n";
						$s2 .= "<th class='referentiel'>".get_string('synthese','referentiel')."</th>\n";
						$nbchamps+=2;
					}
					if ($this->certificate_sel_param->certificate_sel_activite_competences){
						$s2 .= "<th class='referentiel'>".get_string('competences_declare','referentiel')."</th>\n";
						$nbchamps++;
					}
					if ($this->certificate_sel_param->certificate_sel_certificate_competences){
						$s2 .= "<th class='referentiel'>".get_string('competences_certificat','referentiel')."</th>\n";
						$nbchamps++;
					}
					
			    $s1 .= "<!-- certification : $record->id  -->\n";
					$s1 .= "<table class='referentiel'>\n";
					$s1 .= $this->write_student( $record_student, $nbchamps);
    				
					$s1 .= "<tr class='referentiel'><td class='referentiel' colspan='".$nbchamps."'><b>".get_string('certificat','referentiel')."</b></td></tr>\n";
					$s1 .= "<tr class='referentiel'>\n</tr>\n";
					$s1 .= $s2;
					$s1 .="</tr>\n";

					$s2='';
					if ($this->certificate_sel_param->certificate_sel_decision_jury){
	        		    $s2 .= "<td class='referentiel'>$decision_jury</td>\n";
						if ($date_decision!=""){
					        $s2 .= "<td class='referentiel'>$date_decision</td>\n";
    					}
						else {
							$s2 .= "<td class='referentiel'>&nbsp;</td>\n";
						}
					}
					if ($this->certificate_sel_param->certificate_sel_certificate_referents){
						$s2 .= "<td class='referentiel'>".$nom_prenom_teacher."</td>\n";
    				}
					if ($this->certificate_sel_param->certificate_sel_certificate_detail){
		    		    $s2 .= "<td class='referentiel'>$verrou</td>\n";
			            $s2 .= "<td class='referentiel'>$evaluation</td>\n";
					}
					if ($this->certificate_sel_param->certificate_sel_commentaire){
						$s2 .= "<td class='referentiel'>$comment &nbsp;</td>\n";
						$s2 .= "<td class='referentiel'>$synthese_certificate &nbsp;</td>\n";
					}
					if ($this->certificate_sel_param->certificate_sel_activite_competences){
				    	$s2 .= "<td class='referentiel'>".referentiel_affiche_competences_certificat('/',':',$comptencies, $this->liste_empreintes_competence)."</td>\n";
					}
					if ($this->certificate_sel_param->certificate_sel_certificate_competences){
	    	    		$s2 .= "<td class='referentiel'>".referentiel_affiche_competences_certificat('/',':',$competences_certificat, $this->liste_empreintes_competence, false)."</td>\n";
					}
					if (($this->certificate_sel_param->certificate_sel_certificate_competences) 
                        && ($this->certificate_sel_param->certificate_sel_certificate_detail)){
						$s2 .= "</tr>\n<tr class='referentiel'>\n<th class='referentiel' colspan='".$nbchamps."'>\n".get_string('certificate_sel_certificate_detail','referentiel')."</th></tr>\n";
						$s2 .= "<tr class='referentiel'>\n<td class='referentiel' colspan='".$nbchamps."'>\n<table class='referentiel'>\n";
						$s2 .= '<tr valign="top"><th>'.get_string('code','referentiel').'</th><th>'.get_string('approved','referentiel').'</th><th colspan="3">'.get_string('description','referentiel').'</th><th>'.get_string('p_item','referentiel').'</th><th>'.get_string('e_item','referentiel').'</th></tr>'."\n";
						$s2 .= referentiel_affiche_detail_competences('/',':',$competences_certificat, $this->liste_empreintes_competence, $this->liste_poids_competence)."</table>\n</td>\n";
					}
					if ($this->certificate_sel_param->certificate_sel_certificate_pourcent){
						$s2 .= "</tr>\n<tr class='referentiel'>\n<th class='referentiel' colspan='".$nbchamps."'>\n".get_string('pourcentage','referentiel')."</th></tr>\n";
						$s2 .= "<tr class='referentiel'>\n<td class='referentiel' colspan='".$nbchamps."'>".$pourcentages."</td>\n";
					}					
					$s1 .= "<tr class='referentiel'>\n";
					$s1.=$s2;
					$s1 .= "</tr>\n";
					$s1 .= "</table>\n\n";
					$expout.=$s1;
				}
			}
		}
        return $expout;
    }
	
    /**
     * Turns item into an xml segment
     * @param item object
     * @return string xml segment
     */

    function write_item( $item ) {
    global $CFG;
        // initial string;
        $expout = "";
        // add comment
        $expout .= "\n\n<!-- item: $item->id  -->\n";
		// 
		if ($item){
			// DEBUG
			// echo "<br />\n";
			// print_r($item);
            $code = $item->code;
            $description = $item->description;
            $referentielid = $item->referentielid;
            $skillid = $item->skillid;
			$type = $item->type;
			$weight = $item->weight;
			$footprint = $item->footprint;
			$sortorder = $item->sortorder;
            $expout .= "<tr class='referentiel'>";
			$expout .= "<td class='referentiel'>".stripslashes($code)."</td>\n";   
            $expout .= "<td class='referentiel'>".stripslashes($description)."</td>\n";
            // $expout .= "<td>".$referentielid."</td>\n";
            // $expout .= "<td>".$skillid."</td>\n";
            $expout .= "<td class='referentiel'>".stripslashes($type)."</td>\n";
            $expout .= "<td class='referentiel'>".$weight."</td>\n";
            $expout .= "<td>".$footprint."</td>\n";
            $expout .= "<td class='referentiel'>".$sortorder."</td>\n";			
			$expout .= "</tr>\n";   
        }
		$expout .= "\n";
        return $expout;
    }
	 /**
     * Turns competence into an xml segment
     * @param competence object
     * @return string xml segment
     */

    function write_competence( $competence ) {
    global $CFG;
        // initial string;
        $expout = "";
		if ($competence){
            $code = $competence->code;
            $description = $competence->description;
            $domainid = $competence->domainid;
			$sortorder = $competence->sortorder;
			$nb_item_competences = $competence->nb_item_competences;
			$expout .= "<tr class='referentiel'>\n";	
			$expout .= " <td class='referentiel'>".stripslashes($code)."</td>\n";   
            $expout .= " <td class='referentiel'>".stripslashes($description)."</td>\n";
            // $expout .= " class='referentiel'".$domainid."</td>\n";
            $expout .= " <td class='referentiel'>".$sortorder."</td>\n";
            $expout .= " <td class='referentiel'>".$nb_item_competences."</td>\n";
			$expout .= "</tr>\n";
			
			// ITEM
			$compteur_item=0;
			$records_items = referentiel_get_item_competences($competence->id);
			
			if ($records_items){
				// DEBUG
				// echo "<br/>DEBUG :: ITEMS <br />\n";
				// print_r($records_items);
				$expout .= "<tr class='referentiel'><td colspan='4'>\n";	
            	$expout .= "<table class='referentiel'>\n<tr>\n";
				$expout .= "<th class='referentiel'>".get_string('code','referentiel')."</th>\n";   
            	$expout .= "<th class='referentiel'>".get_string('description','referentiel')."</th>\n";
            	// $expout .= "<th>".get_string('referentielid','referentiel')."</th>\n";
	            // $expout .= "<th>".get_string('skillid','referentiel')."</th>\n";
    	        $expout .= "<th class='referentiel'>".get_string('type','referentiel')."</th>\n";
        	    $expout .= "<th class='referentiel'>".get_string('weight','referentiel')."</th>\n";
            	$expout .= "<th class='referentiel'>".get_string('footprint','referentiel')."</th>\n";
	            $expout .= "<th class='referentiel'>".get_string('sortorder','referentiel')."</th>\n";			
				$expout .= "</tr>\n";   
				
				foreach ($records_items as $record_i){
					$expout .= $this->write_item( $record_i );
				}
				$expout .= "</table></td></tr>\n";   
			}
        }
        return $expout;
    }


	 /**
     * Turns domaine into an xml segment
     * @param domaine object
     * @return string xml segment
     */

    function write_domaine( $domaine ) {
    global $CFG;
        // initial string;
        $expout = "";
		if ($domaine){
            $code = $domaine->code;
            $description = $domaine->description;
            $referentielid = $domaine->referentielid;
			$sortorder = $domaine->sortorder;
			$nb_competences = $domaine->nb_competences;
			
			
			// LISTE DES COMPETENCES DE CE DOMAINE
			$compteur_competence=0;
			$records_competences = referentiel_get_competences($domaine->id);
			if ($records_competences){
				$expout .= "<tr class='referentiel'>\n";			
				$expout .= "   <td class='referentiel'>".stripslashes($code)."</td>\n";   
        	    $expout .= "   <td class='referentiel'>".stripslashes($description)."</td>\n";
            	// $expout .= "   <td class='referentiel'>".$referentielid</td>\n";
	            $expout .= "   <td class='referentiel'>".$sortorder."</td>\n";
    	        $expout .= "   <td class='referentiel'>".$nb_competences."</td>\n";
				$expout .= "</tr>\n";
				
				foreach ($records_competences as $record_c){
					$expout .= "<tr class='referentiel'>\n";	
					$expout .= "<th class='referentiel'>".get_string('code','referentiel')."</th>\n";   
            		$expout .= "<th class='referentiel'>".get_string('description','referentiel')."</th>\n";
            		// $expout .= "<th class='referentiel'>".get_string('domainid','referentiel')."</th>\n";
            		$expout .= "<th class='referentiel'>".get_string('sortorder','referentiel')."</th>\n";
            		$expout .= "<th class='referentiel'>".get_string('nb_item_competences','referentiel')."</th>\n";
					$expout .= "</tr>\n";
					$expout .= $this->write_competence( $record_c );
				}
			}
        }
        return $expout;
    }



	 /**
     * Turns referentiel instance into an xml segment
     * @param referentiel instanceobject
     * @return string xml segment
     */

    function write_certification() {
    	global $CFG;
		
		$nbchamps=0;
        // initial string;
	    $expout = "";
		

		if (($this->referentiel_referentiel) && ($this->referentiel_instance)){
      $name = trim($this->referentiel_referentiel->name);
      $code = trim($this->referentiel_referentiel->code);
			$description = trim($this->referentiel_referentiel->description);
			
			$id = $this->referentiel_instance->id;
      $name_instance = trim($this->referentiel_instance->name);
      $description = trim($this->referentiel_instance->description);
      $domainlabel = trim($this->referentiel_instance->domainlabel);
      $skilllabel = trim($this->referentiel_instance->skilllabel);
      $itemlabel = trim($this->referentiel_instance->itemlabel);
      $timecreated = userdate($this->referentiel_instance->timecreated);
      $course = $this->referentiel_instance->course;
      $referentielid = $this->referentiel_instance->referentielid;
			$visible = $this->referentiel_instance->visible;
			
			// add comment and div tags
			$expout .= "<!-- certification-->\n";
	    $expout .= "<h2>$name - ($code)</h2>\n";
			$expout .= "<p>$description</p>\n";

			$s='';
			if ($this->certificate_sel_param->certificate_sel_referentiel_instance){
				//$expout .= "<th class='referentiel'>id</th>\n";
				$s.= "<th class='referentiel'>".get_string('name_instance','referentiel')."</th>\n";
				$s.= "<th class='referentiel'>".get_string('description','referentiel')."</th>\n";
				$s.= "<th class='referentiel'>".get_string('domainlabel','referentiel')."</th>\n";
				$s.= "<th class='referentiel'>".get_string('skilllabel','referentiel')."</th>\n";
				$s.= "<th class='referentiel'>".get_string('itemlabel','referentiel')."</th>\n";
				$s.= "<th class='referentiel'>".get_string('timecreated','referentiel')."</th>\n";
				$s.= "<th class='referentiel'>".get_string('course')."</th>\n";
				$s.= "<th class='referentiel'>".get_string('referentielid','referentiel')."</th>\n";
				$s.= "<th class='referentiel'>".get_string('visible','referentiel')."</th>\n";
				$nbchamps+=9;
			}
			
			if ($nbchamps==0) $nbchamps=1;
			
			if ($this->certificate_sel_param->certificate_sel_referentiel
				&& isset($this->referentiel_referentiel->id) && ($this->referentiel_referentiel->id>0)){
				
				// DOMAINES
				// LISTE DES DOMAINES
				$compteur_domaine=0;
				$records_domaine = referentiel_get_domaines($this->referentiel_referentiel->id);
		    if ($records_domaine){
					$expout .= "<table class='referentiel'>\n";
					$expout .= "<tr class='referentiel'><th class='referentiel' colspan='".$nbchamps."'>".get_string('detail_referentiel','referentiel')."</th></tr>\n";
					$expout .= "<tr class='referentiel'><td class='referentiel' colspan='".$nbchamps."'>\n";
					$expout .= "<table class='referentiel'>\n";
					
					foreach ($records_domaine as $record_d){
						$expout .= "<tr class='referentiel'>\n";
						$expout .= "<th class='referentiel'>".get_string('code','referentiel')."</th>\n";   
            			$expout .= "<th class='referentiel'>".get_string('description','referentiel')."</th>\n";
	            		// $expout .= "<th class='referentiel'>".get_string('referentielid','referentiel')."</th>\n";
    	        		$expout .= "<th class='referentiel'>".get_string('sortorder','referentiel')."</th>\n";
        	    		$expout .= "<th class='referentiel'>".get_string('nb_competences','referentiel')."</th>\n";
						$expout .= "</tr>\n";
						
						$expout .= $this->write_domaine($record_d );
					}
					$expout .= "</table>\n</td></tr>\n";
				}
			} 
			


			if ($s!=''){
				$expout .= "<tr class='referentiel'>\n";
				$expout .= $s;
				$expout .= "</tr>\n";
			}
			
			$s='';
			
			if ($this->certificate_sel_param->certificate_sel_referentiel_instance){
				// $expout .= " <td class='referentiel'>$id</td>\n";
				$s .= " <td class='referentiel'>$name_instance</td>\n";
				$s .= " <td class='referentiel'>$description</td>\n";   
        $s .= " <td class='referentiel'>$domainlabel</td>\n";
        $s .= " <td class='referentiel'>$skilllabel</td>\n";
	      $s .= " <td class='referentiel'>$itemlabel</td>\n";			
    	  $s .= " <td class='referentiel'>$timecreated</td>\n";
        $s .= " <td class='referentiel'>$course</td>\n";
	      $s .= " <td class='referentiel'>$referentielid</td>\n";
    	  $s .= " <td class='referentiel'>$visible</td>\n";
			}
			if ($s!=''){
				$expout .= "<tr class='referentiel'>\n";
				$expout .= $s;
				$expout .= "</tr>\n";
			}
			

			$expout .= "</table>\n";
			
			// CERTIFICATS
			if ($this->referentiel_referentiel){
			  if ($this->userid>0){
					$record = referentiel_get_certificate_user($this->userid, $this->referentiel_referentiel->id);
          if ($record){
					  $expout .= $this->write_certificat( $record);
					}
				}	
				else {
				  if (!$this->records_certificats){
              $this->records_certificats = referentiel_get_certificats($this->referentiel_referentiel->id);
          }    
				  // print_r($records_certificats);
		    	if ($this->records_certificats){
					 foreach ($this->records_certificats as $record){
						if ($record){
							$expout .= $this->write_certificat( $record);
						}
					 }
				  }
			   }
			  }
      }
      return $expout;
    }
}


?>
