<?php
/* ************************  RECODAGES de tables de caracteres *************************/
require_once('../../config.php');
require_once($CFG->libdir .'/textlib.class.php'); // pour utiliser $textlib

// FONCTIONS ===================================================================

// -------------------------
function CleanFiles($dir, $ext)
{
	// $ext ='.doc'
    //Efface les fichiers temporaires de plus de $delai secondes dont le nom contient tmp
    $t=time();
    $h=opendir($dir);
    while($file=readdir($h))
    {
        if(substr($file,0,3)=='tmp' and substr($file,-4)==$ext)
        {
            $path=$dir.'/'.$file;
			// DEBUG
			// echo "<br> $path";			
            if ($t-filemtime($path)>120)
                @unlink($path);
        }
    }
    closedir($h);
}  




/// Select encoding
if (function_exists('current_charset')){
    $encoding = current_charset();
}

/// Select direction
if ( get_string('thisdirection') == 'rtl' ) {
	$direction = ' dir="rtl"';
} else {
	$direction = ' dir="ltr"';
}

/// Loading the textlib singleton instance. We are going to need it.
/// pour les fonctions strpos(), substr() et autres conversions de chaines UTF8
$textlib = textlib_get_instance();


/* ************************  RECODAGES de tables de caracteres ************************/
// seule table de caracteres acceptee par fpdf.php est le latin1 : ISO-8859-1


// ----------------
function recode_latin1_vers_utf8($string) {
     return mb_convert_encoding($string, "UTF-8", mb_detect_encoding($string, "UTF-8, ISO-8859-1, ISO-8859-15", true));
}


// ----------------
function recode_utf8_vers_latin1($string) {
     return mb_convert_encoding($string, "ISO-8859-1", mb_detect_encoding($string, "UTF-8, ISO-8859-1, ISO-8859-15", true));
}

// ----------------
function recode_chaine_vers_html($string){
	return mb_convert_encoding($string, 'HTML-ENTITIES', mb_detect_encoding($string, "UTF-8, ISO-8859-1, ISO-8859-15", true));
}

// ----------------
function recode_nom_fichier_latin1($fileName){
	return strtr(mb_convert_encoding($fileName,'ASCII', mb_detect_encoding($fileName, "UTF-8, ISO-8859-1, ISO-8859-15", true)),
    ' ,;:?*#!§$%&/(){}<>=`΄|\\\'"',
    '____________________________');
}



// ----------------
function recode_html_vers_latin1($s0){
// retourne un nom recode
$s="";
    if (is_string($s0) && ereg("&", $s0)){
    // ληφωρ
    // Γ«Γ§Γ¶ΓΉΓ±
                    $s0=ereg_replace( "&eacute;", "ι", $s0 );
                    $s0=ereg_replace( "&egrave;", "θ", $s0 );
                    $s0=ereg_replace( "&ecirc;", "κ", $s0 );
                    $s0=ereg_replace( "&euml;", "λ", $s0 );
                    $s0=ereg_replace( "&iuml;", "ο", $s0 ); 
                    $s0=ereg_replace( "&icirc;", "ξ", $s0 ); 
                    $s0=ereg_replace( "&agrave;", "ΰ", $s0); 
					$s0=ereg_replace( "&acirc;", "β", $s0);
                    $s0=ereg_replace( "&ocirc;", "τ", $s0);
                    $s0=ereg_replace( "&oulm;", "φ", $s0); 
                    $s0=ereg_replace( "&acirc;", "β", $s0);
                    $s0=ereg_replace( "&ccedil;", "η", $s0);
                    $s0=ereg_replace( "&ugrave;", "ω", $s0);
                    $s0=ereg_replace( "&ntilde;", "ρ", $s0);
                    $s0=ereg_replace( "&deg;","°",  $s0);
					$s0=ereg_replace( "&oelig;", "", $s0);
					$s0=ereg_replace( "&Ecirc;", "Κ", $s0);
    }
return $s0;
}

// -----------------
function recode_nom_latin1_html($s0){
// retourne un nom d'url acceptable non accentue
// input : latin1
// output : html 
$s="";
    if (is_string($s0)){
	    for ($i=0; $i<strlen($s0); $i++){
            if (isset($s0[$i])){
                $c=$s0[$i];
                if (  ($c=="'") || ($c=="\\") || ($c=="\r") || ($c=="=")  || ($c=="{")  || ($c=="}")  || ($c=="[")  
                    || ($c=="]")  || ($c=="(")  || ($c==")")  ||  ($c=="'") || ($c==",")  || ($c==":")  || ($c=="!")  || ($c=="?")  || ($c==";")  || ($c==".")  ||  ($c=="'")  || ($c=='-')    || ($c=='_')   || ($c=='/')   || ($c=='+')   || ($c=='*') || ($c=='"') || ($c==' ') 
                    || (($c>='0') && ($c<='9')) || (($c>='A') && ($c<='Z'))  || (($c>='a') && ($c<='z'))){
                    $s.=$c;
                }
                else {
                    switch($c) {
                    case 'ΰ' :  $s.='&agrave;'; break;
					case 'β' :  $s.='&acirc;'; break;
                    case 'δ' :  $s.='&auml;'; break; 
                    case 'β' :  $s.='&acirc;'; break; 
                    case 'ι' :  $s.='&eacute;'; break; 
                    case 'θ' :  $s.='&egrave;'; break; 
                    case 'κ' :  $s.='&ecirc;'; break; 
                    case 'λ' :  $s.='&euml;'; break; 
                    case 'ο' :  $s.='&iuml;'; break; 
                    case 'ξ' :  $s.='&icirc;'; break; 
                    case 'φ' :  $s.='&ouml;'; break; 
                    case 'τ' :  $s.='&ocirc;'; break; 
                    case 'υ' :  $s.='&otilde';  break;
                    case 'ό' :  $s.='&uuml;'; break; 
                    case 'ϋ' :  $s.='&ucirc;'; break; 
                    case 'ω' :  $s.='&ugrave;'; break; 
                    case 'η' :  $s.='&ccedil;'; break; 
                    case 'ρ' :  $s.='&ntilde;'; break;
					case '' :  $s.='&oelig;'; break;
					case 'Κ' :  $s.='&Ecirc;'; break;
                      default :
                    $s.='_';
                    break;   
                    } 
                }
            }
        }
    }    
return $s;
}



?>