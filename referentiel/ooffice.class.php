<?php
// #####################################################################

// DEFINITION d'une CLASSE MSWord imitant la classe fpdf
// bricolage a retravailler
// JF

// -----------------------------------
// la classe OOffice
class OOffice{

	// position au pixel près : absurde
	var $x;
	var $y;
	
	// colonnes
	var $col=0;
	// ligne
	var $lig=0;
	// style
	var $fnt_style_ital=0;
	var $fnt_style_bold=0;
	var $fnt_police='Arial';
	var $fnt_size=10;
	
	var $style='';
	// div
	var $divtop='';
	// marges
	var $marge_gauche;
	var $marge_haute;
	var $largeur_page;
	var $hauteur_page;
	var $widthx;
	var $widthy;
	// saut de page
	var $saut_page=true;
	
	function SetFont($fnt_police, $fnt_style, $fnt_size){
		// A completer pour les polices et la taille de la police
			$this->$fnt_police=$fnt_police;
			$this->$fnt_size=$fnt_size;
			
      if (empty($fnt_style)){
				$fnt_style='N';
			}
			switch ($fnt_style) {
				case 'bi' :
				case 'BI' :
				case 'Bi' :
				case 'bI' :
				case 'ib' :
				case 'iB' :
        case 'IB' :
				case 'Ib' :
					$this->fnt_style_ital=1;
					$this->fnt_style_bold=1;
				break;
				case 'i' :
				case 'I' :
					$this->fnt_style_ital=1;
					$this->fnt_style_bold=0;
				break;
				case 'b' :
				case 'B' :
					$this->fnt_style_ital=0;
					$this->fnt_style_bold=1;
				break;
				
				default :
					$this->fnt_style_ital=0;
					$this->fnt_style_bold=0;
				break;
			}
			// DEBUG
			
	} 
	
	function DebutParagraphe(){
		echo "<p>\n";
	}
	
	function FinParagraphe(){
		echo "</p>\n";
	}

	function Write($col, $texte){
		$space='';
		for ($i=0; $i<$col; $i++){
			$space.='&nbsp;'."\n";
		}
		if ($this->fnt_style_ital==1){
			$texte='<i>'.$texte.'</i>';
		}
		if ($this->fnt_style_bold==1){
			$texte='<b>'.$texte.'</b>';
		}		
		echo $space.$texte."\n";
	}
	
	function WriteParagraphe($col, $texte){
		$space='';
		for ($i=0; $i<$col; $i++){
			$space.='&nbsp;'."\n";
		}
		if ($this->fnt_style_ital==1){
			$texte='<i>'.$texte.'</i>';
		}
		if ($this->fnt_style_bold==1){
			$texte='<b>'.$texte.'</b>';
		}		
		echo '<p>'.$space.$texte.'</p>'."\n";
	}

	function Ln($lig=0){
		if ($lig<1) {
			$lig=1;
		}
		
		for ($i=0; $i<$lig; $i++){
			echo '<br />'."\n";
		}
		$this->lig+=$lig;
		if ($this->saut_page==true){
			if ($this->lig  > $this->hauteur_page){
				$this->AddPage();
			}
		}
	}
	
	
	function AddPage(){
		// ----------------- PAGE BREAK ----------
		$this->lig=0;
		echo '
		<br clear=all style="mso-special-character:line-break;page-break-before:always">'."\n";
	}
	
	function SetAutoPageBreak($ok, $hauteur_page){
		// ----------------- HAUTEUR PAGE ----------
		// echo " $hauteur_page<br />\n";
		if ($ok){
			$this->saut_page=true;
		}
		else{
			$this->saut_page=false;
		}
		$this->hauteur_page=$hauteur_page;
	} 
	
	function SetDrawColor($r,$v,$b){
		echo "----------------- SETDRAWCOLOR ----------<br />\n";
		echo " $r,$v,$b<br />\n";
	}    
	
	function SetLineWidth($epaisseurligne){
	
	}
	
	function Image($image_logo,$posy,$posy,$width){
	
	}

	function GetX()
	{
   	 	// x position 
    	return $this->x;
	}
	
	function GetY()
	{
   	 	// y position
    	return $this->y;
	}
	
	function SetX($x)
	{
   	 	//Move position to a x
    	$this->x=$x;
	}
	
	function SetY($y)
	{
   	 	//Move position to a y
    	$this->y=$y;
	}
	
	function SetXY($x)
	{
   	 	//Move position to a x
    	$this->x=$x;
		//Move position to a y
    	$this->y=$y;
	}
	
	function SetCol($cl)
	{
   	 	//Move position to a column
    	$this->col=$cl;
    	$x=15+$cl*95;
    	$this->SetLeftMargin($x);
    	$this->SetX($x);
	}

	function SetLig($lg)
	{
   	 	//Move position to a column
    	$this->lig=$lg;
    	$y=15+$lg*95;
    	$this->SetY($y);
	}
	
	// --------------------------------
	function SetDiv($page, $div, $ordre, $cadre=1){
		$this->divtop=$page*30+".$this->fnt_size."*$ordre+".$this->fnt_size.";
		$this->style= "
#".$div."_".$page." {
	position:relative;
	left:10
	top:".$this->divtop.";
	width:720;
	z-index: 0;
	color: black;
	background-color : #ffffff;
";
		if ($cadre){	
		  $this->style.= "   border : thin solid Black;\n";
		}
		$this->style.= "	font-family : ".$this->fnt_police.";
	font-weight : normal;
    margin : 1pt;
	padding : 4px;
	voice-family : male;
	volume : inherit;
	white-space : normal;	
}

";
		return $this->style;
	}



	// --------------------------------
	function SetPage($mgauche, $mhaute, $lpage, $hpage, $wx, $wy){
		$this->marge_gauche=$mgauche;
		$this->marge_haute=$mhaute;
		$this->largeur_page=$lpage;
		$this->hauteur_page=$hpage;
		$this->widthx=$wx;
		$this->widthy=$wy;
		
		// DEBUG
		// $this->Write(0,"DEBUG :: ".$this->marge_gauche.', '.$this->marge_haute.', '.$this->largeur_page.', '.$this->hauteur_page.', '.$this->widthx.', '.$this->widthy."<br />");
	}
	
	// --------------------------------
	function SetLeftMargin($leftmargin){
		$this->marge_gauche=$leftmargin;
	}

	// --------------------------------
	function SetEntete($titre=''){
		$s='';
		// =====================================================
		// header info
		$type_fichier = 'vnd.oasis.opendocument.text';
		$nom_fichier = 'certification-'.date("Ymshis").'-'.md5(uniqid()).'.odt';
		$sep = "\t";
		// $size = filesize($nom_fichier);
		header("Expires: 0");
		header("Content-Type: application/$type_fichier");
		header("Content-Transfer-Encoding: binary");
		header("Content-Disposition: attachment; filename=$nom_fichier");
		// header("Content-Length: ".$size);
		header("Cache-Control: no-cache, must-revalidate");

		// AFFICHAGE  ==========================================
		// Format A4 paysage en mm
		$hauteur_page       = 290; 
		$largeur_page       = 210; 
		$marge_haute        = 30;
		$marge_basse        = 40;
		$marge_gauche       = ".$this->fnt_size.";
		$marge_droite       = ".$this->fnt_size.";
		$page=0;
		
		echo "<html>
<head>
<title>".$titre."</title>
<META NAME=\"Description\" CONTENT=\"TICE\">
<META NAME=\"Keywords\" CONTENT=\"Referentiel Moodle\">
<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=iso-8859-1\">
<META HTTP-EQUIV=\"Cache-Control\" CONTENT=\"no-cache\">
<style>
BODY {
	FONT-SIZE: ".$this->fnt_size."pt; MARGIN: 3px; FONT-FAMILY: ".$this->fnt_police.", Helvetica, sans-serif;  COLOR:#000000; BACKGROUND-COLOR: #ffffff;
}
A {
	COLOR: blue; TEXT-DECORATION: none;
}
A:hover {
	COLOR: #000000; TEXT-DECORATION: underline;
}
A.left {
	FONT-WEIGHT: normal; FONT-SIZE: 8pt; COLOR: #555555; FONT-FAMILY: ".$this->fnt_police.", Helvetica, sans-serif; TEXT-DECORATION: none;
}
A.left:hover {
	FONT-WEIGHT: normal; FONT-SIZE: 8pt; COLOR: #000000; FONT-FAMILY: ".$this->fnt_police.", Helvetica, sans-serif; TEXT-DECORATION: none;
}
.item0 {
	BACKGROUND-COLOR: #eeeeee;
}
.item1 {
	BACKGROUND-COLOR: #fffeee;
}
.item2 {
	BACKGROUND-COLOR: #fbefdf;
}
.header {
	COLOR: #224433; BACKGROUND-COLOR: #ddddcc;
}
.caption {
	BACKGROUND-COLOR: #dedeee;
}

.vert08n {font-family:  ".$this->fnt_police.";  font-size: 8pt; color: green;}
.rouge08n {font-family: ".$this->fnt_police.";   font-size: 8pt; color: #FF0000;}
.norm08n {font-family: ".$this->fnt_police.";   font-size: 8pt; color: #275372;}

.norm".$this->fnt_size."n {font-family:  ".$this->fnt_police.";  font-size: ".$this->fnt_size."pt; color: #275372;}
.rouge".$this->fnt_size."n {font-family: ".$this->fnt_police."; font-size: ".$this->fnt_size."pt; color: #FF0000;}
.vert".$this->fnt_size."n {font-family: ".$this->fnt_police.";  font-size: ".$this->fnt_size."pt; color: green;}

#div_logo 	{
	position:relative;
	left:0;
	top:0;
	width:200;	
	height:300;
	z-index: 0;
	color: black;
	background-color : white;
	font-family : ".$this->fnt_police.";
	font-weight : normal;
    margin : 1pt;
	padding : 4px;
	voice-family : male;
	volume : inherit;
	white-space : normal;	
}

#div_date 	{
	position:relative;
	left:0;
	top:0;
	width:680;	
	height:20;
	z-index: 0;
	color: black;
	background-color : white;
	font-family : ".$this->fnt_police.";
	font-weight : normal;
    margin : 1pt;
	padding : 4px;
	voice-family : male;
	volume : inherit;
	white-space : normal;	
}


#div_cartouche 	{
	position:absolute;
	left:200;
	top:20;
	width:510	
	height:300;
	z-index: 0;
	color: black;
	background-color : white;
	font-family : ".$this->fnt_police.";
	font-weight : normal;
    margin : 1pt;
	padding : 4px;
	voice-family : male;
	volume : inherit;
	white-space : normal;	
}

#div_note 	{
	position:absolute;
	left:200;
	top:2;
	width:10;	
	height:8;
	z-index: 0;
	color: black;
	background-color : white;
    border : thin solid Black;
	font-family : ".$this->fnt_police.";
	font-weight : normal;
    margin : 1pt;
	padding : 4px;
	voice-family : male;
	volume : inherit;
	white-space : normal;	
}

#div_signatures 	{
	position:relative;
	left:10;
	top:0;
	width:680;	
	height:120;
	z-index: 0;
	color: black;
	background-color : white;
	font-family : ".$this->fnt_police.";
	font-weight : normal;
    margin : 1pt;
	padding : 4px;
	voice-family : male;
	volume : inherit;
	white-space : normal;	
}

#div_sign1 	{
	position:relative;
	left:10;
	top:0;
	width:300;	
	height:120;
	z-index: 0;
	color: black;
	background-color : white;
	font-family : ".$this->fnt_police.";
	font-weight : normal;
    margin : 1pt;
	padding : 4px;
	voice-family : male;
	volume : inherit;
	white-space : normal;	
}

#div_sign2 	{
	position:absolute;
	left:380;
	top:25;
	width:280;	
	height:120;
	z-index: 0;
	color: black;
	background-color : white;
	font-family : ".$this->fnt_police.";
	font-weight : normal;
    margin : 1pt;
	padding : 4px;
	voice-family : male;
	volume : inherit;
	white-space : normal;	
}

#div_entete 	{
	position:relative;
	left:560;
	top:0;
	width:140;	
	height:20;
	z-index: 0;
	color: black;
	background-color : white;
	font-family : ".$this->fnt_police.";
	font-weight : normal;
    margin : 1pt;
	padding : 4px;
	voice-family : male;
	volume : inherit;
	white-space : normal;	
}


.normal8 {font-family:  ".$this->fnt_police.";  font-size: 8pt;}
.normal".$this->fnt_size." {font-family:  ".$this->fnt_police.";  font-size: ".$this->fnt_size."pt;}
.normal12 {font-family:  ".$this->fnt_police.";  font-size: 12pt;}
.normal14 {font-family:  ".$this->fnt_police.";  font-size: 14pt;}
".$this->style."
</STYLE>
</head>
<body>
\n";
 		// A4 portrait en mm
		$this->SetPage($marge_gauche, $marge_haute, $largeur_page, $hauteur_page, 700, 600); 

	}

	function SetEnqueue(){
		return "</body></html>\n";
	}	


	function AcceptPageBreak()
	{
		// passage a la colonne / page suivante
   
   		// if($this->col<1)
   		// {
        //Go to next column
   		//     $this->SetCol($this->col+1);
   		//     $this->SetY(34);
   		//     return false;
   		// }
   		// else
   		// {
        	//Go back to first column and issue page break
        	$this->SetCol(0);
        	return true;
  		//   }
	}

	//Pied de page
	function Footer()
	{
		
    	// Positionnement à 1,5 cm du bas
    	$this->SetY(-15);
    	//Police ".$this->fnt_police." italique 8
    	// $this->SetFont('".$this->fnt_police."','I',8);
    	// Numéro de page
    	// $this->Cell(0,".$this->fnt_size.",'Page '.$this->PageNo().'/{nb}',0,0,'C');
	}


	function SetDisplayMode($mode){
		// mode real ?
		// RAS
		;
	}
	
	function Open(){
		// RAS
		;
	}
	
	function AliasNbPages(){
		// RAS
		;
	}
	
	function Output(){
		// RAS
		;
	}

}

// #####################################################################

?>