<?php
class jauge {
// Jauge
	var $s = "";
	var $sep1 = "/";
	var $sep2 = ":";
	var $tcode = array();
	var $tvaleur = array();
	
	 /**
     * set the referentiel
     * @param object referentiel the referentiel object
     */
	function setJaugeListe( $liste ) {
        $this->s = $liste;
    }

	function setsetJaugeSep1( $c ) {
        $this->sep1 = $c;
    }

	function setJaugeSep2( $c ) {
        $this->sep2 = $c;
    }

	// -----------------------
	function la_classe($index){
		switch 	($index) {
		case 0 : return "zero"; break;
		// case 1 : return "un"; break;
		// case 2 : return "deux"; break;
		// case 3 : return "trois"; break;		
		default : return "deux"; break;	
		}
	}


	//-------------------------
	function affiche(){
		// liste competence de la forme CODE1:N1/CODE2:N2, etc.
		$tcode=array();
		$tvaleur=array();
    	// ICI mettre à jour la table 
    	$this->retourne_tables_codes_valeurs($this->s, $this->sep1, $this->sep2);
		
	    // echo '<div id="jaugeDiv">';
		echo '<table border="0" cellpadding="1" cellspacing="1" bgcolor="#eeffff">'."\n";
		/*
		echo '<tr bgcolor="white">'."\n";
		for ($i = 0; $i < count($this->tcode); $i++) {
			echo '<td class="jauge">'.$this->tcode[$i].'</td>'."\n";
		}
		echo '</tr>'."\n";
		*/
		echo '<tr bgcolor="white">'."\n";
		for ($i = 0; $i < count($this->tcode); $i++) {
			if ($this->tvaleur[$i]>0){
				echo '<td class="'.$this->la_classe(1).'">';
    		}
			else {
				echo '<td class="'.$this->la_classe(0).'">';
    		}
	    	/*
	         <a href="javascript:void(0);" onmouseover="return overlib('This is an ordinary popup.');" onmouseout="return nd();">here</a>
    		*/
	        $mesg=$this->tcode[$i];
    	    // $mesg=ereg_replace("'", "&acute;", $mesg); 
        	$mesg=ereg_replace("'", "&acute;", $mesg); 
	        $mesg=ereg_replace('"', " ", $mesg); 
    	    // echo $mesg;
        	?>
	        <a class="overlib" href="javascript:void(0);" onmouseover="return overlib('<?php echo $mesg; ?>');" onmouseout="return nd();">&nbsp; &nbsp; &nbsp;</a>
    	    <?php
        	echo '</td>';
		}
		echo '
</tr>
</table>
';
// echo '</div>';
	}
	
	//-------------------------
	function retourne_jauge(){
		// liste competence de la forme CODE1:N1/CODE2:N2, etc.
		$s="";
		$tcode=array();
		$tvaleur=array();
    	// ICI mettre à jour la table 
    	$this->retourne_tables_codes_valeurs($this->s, $this->sep1, $this->sep2);
		

	    // echo '<div id="jaugeDiv">';
		echo '<table border="0" cellpadding="1" cellspacing="1" bgcolor="#eeffff">'."\n";
		/*
		echo '<tr bgcolor="white">'."\n";
		for ($i = 0; $i < count($this->tcode); $i++) {
			echo '<td class="jauge">'.$this->tcode[$i].'</td>'."\n";
		}
		echo '</tr>'."\n";
		*/
		$s.= '<tr bgcolor="white">'."\n";
		for ($i = 0; $i < count($this->tcode); $i++) {
			if ($this->tvaleur[$i]>0){
				$s.= '<td class="'.$this->la_classe(1).'">';
    		}
			else {
				$s.= '<td class="'.$this->la_classe(0).'">';
    		}
	        $mesg=$this->tcode[$i];
    	    // $mesg=ereg_replace("'", "&acute;", $mesg); 
        	$mesg=ereg_replace("'", "&acute;", $mesg); 
	        $mesg=ereg_replace('"', " ", $mesg); 
    	    // echo $mesg;
        	$s.= '<a class="overlib" href="javascript:void(0);" onmouseover="return overlib(\''.$mesg.'\');" onmouseout="return nd();">&nbsp; &nbsp; &nbsp;</a>'."\n";
        	$s.= '</td>';
		}
		$s.= '</tr>'."\n".'</table>'."\n";
// echo '</div>';
		return $s;
	}
	
	//-------------------------
	function retourne_tables_codes_valeurs(){
	// liste competence de la forme CODE1:N1/CODE2:N2, etc.
		$tc=array();
		if (!empty($this->s) && ($this->sep1!="") && ($this->sep2!="")){
			$tc = explode ($this->sep1, $this->s);
			// DEBUG 
			// echo "<br />CODE <br />\n";
			// print_r($tc);
			$i=0;
			while ($i<count($tc)){
				// CODE1:N1
				// DEBUG 
				// echo "<br />".$tc[$i]." <br />\n";
				// exit;
				$tcc=explode($this->sep2, $tc[$i]);
				// echo "<br />".$tc[$i]." <br />\n";
				// print_r($tcc);
				// exit;
				$this->tcode[]=$tcc[0];
				$this->tvaleur[]=$tcc[1];
				$i++;
			} 
		}
	}
	
}
// FIN DE JAUGE

// ----------------------------------------------------
function referentiel_affiche_jauge_competence($liste){
// LISTE de la forme 'CODE''SEPARATEUR2''nombre''SEPARATEUR1''CODE''SEPARATEUR2''nombre' ...
// par exemple A.1.1:0/A.1.2:1/A.1.3:0 etc.
// separateur 
	$une_jauge = new jauge();
	$une_jauge->setJaugeListe(referentiel_purge_dernier_separateur($liste, "/"));
	$une_jauge->setsetJaugeSep1("/");
	$une_jauge->setJaugeSep2(":");
	$une_jauge->affiche();
}

// ----------------------------------------------------
function referentiel_jauge_competence($liste){
// LISTE de la forme 'CODE''SEPARATEUR2''nombre''SEPARATEUR1''CODE''SEPARATEUR2''nombre' ...
// par exemple A.1.1:0/A.1.2:1/A.1.3:0 etc.
// separateur 
	$une_jauge = new jauge();
	$une_jauge->setJaugeListe(referentiel_purge_dernier_separateur($liste, "/"));
	$une_jauge->setsetJaugeSep1("/");
	$une_jauge->setJaugeSep2(":");
	return $une_jauge->retourne_jauge();
}



?>
