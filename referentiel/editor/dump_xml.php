<?php
// dump_xml.php
// dossier des referentiels enregistres; doit exister sur le serveur
$dossier_referentiel='data';
$ext_xml='.xml';	// fichier de donnees sauvegardee
$suffixe='_sxml';   // pour distinguer du format xml basique des sauvegardes du référentiel

// ###################### Initialisation variables PHP

if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
	$uri = 'https://';
} else {
	$uri = 'http://';
}


$url_serveur_local = $uri.$_SERVER['HTTP_HOST'].':'.$_SERVER['SERVER_PORT'].get_url_pere($_SERVER['SCRIPT_NAME']);
// DEBUG
// echo "<br>URL : $url_serveur_local\n";

$dir_serveur = dirname($_SERVER['SCRIPT_FILENAME']);
// DEBUG
// echo "<br>Répertoire serveur : $dir_serveur\n";
// Nom du script chargé dynamiquement.
$appli=$_SERVER["PHP_SELF"];

$editor='';           // donnees a archiver
$nom_fichier=='referentiel_'.date("YmdHis");


// ----------------------------
function get_url_pere($path) {
// Retourne l'URL du répertoire contenant le script
// global $PHP_SELF;
// DEBUG
// echo "<br>PHP_SELF : $PHP_SELF\n";
//	$path = $PHP_SELF;
	$nomf = substr( strrchr($path, "/" ), 1);
	if ($nomf){
		$pos = strlen($path) - strlen($nomf) - 1;
		$pere = substr($path,0,$pos);
	}
	else
		$pere = $path;
	return $pere;
}

// ----------------
function conversion_xml($s){
//
    $search  = array("<br>","<br/>","<br />");
    $replace = array("\n","\n","\n");
    $s=str_replace($search, $replace, $s);
    $s=strip_tags($s);
    $search  = array('[referentiel]','[/referentiel]','[domaine]','[/domaine]','[competence]','[/competence]','[item]','[/item]','[idcode]','[/idcode]','[name]','[/name]','[url]','[/url]','[definition]','[/definition]','[text]','[/text]');
    $replace = array('<referentiel>','</referentiel>','<domaine>','</domaine>','<competence>','</competence>','<item>','</item>','<idcode>','</idcode>','<name>','</name>','<url>','</url>','<definition>','</definition>','<text>','</text>');

    $s=str_replace($search, $replace, $s);
    return $s;
}


// ----------------
function recode_nom($nom){
// retourne un nom d'url acceptable
    $nom=html_entity_decode(trim($nom),ENT_QUOTES,'UTF-8');

	//$s = strtr(trim($nom), " -%£&/'àéèêïîöôùüûç", "_______aeeeiioouuuc");
	$search =array('"'," ","'","%","£","&","/","à","é","è","ê","ï","î","ö","ô","ù","ü","û","ç");
    $replace=array('_','_','_','_','_',"_","_","a","e","e","e","i","i","o","o","u","u","u","c");
    //$s = urlencode(str_replace($search, $replace,trim($nom)));
    $s = str_replace($search, $replace,$nom);
	return $s;
}


// -----------------------
function enregistrer_fichier($contenu, $nom_fichier, $dossier, $ext){
// sauvegarde le fichier dans le dossier
global $dir_serveur;
global $suffixe;
	$trouve=false;
	$f_name=$dossier.'/'.$nom_fichier.$suffixe.$ext;
	
   //
   // if (file_exists($dir_serveur.'/'.$f_name)){
   //     // confirmer l'écrasement ?
   // }

    $fp_data = fopen($dir_serveur.'/'.$f_name, 'w');
	if ($fp_data){
	   fwrite($fp_data, $contenu);
	   fclose($fp_data);
	   return $f_name;
	}
	return '';
}


// ################### PROGRAMME ###################
    if (!empty($_GET) || !empty($_POST)){

        if (!empty($_GET)){
            if (!empty($_GET['editor'])){
                //$editor=html_entity_decode(urldecode($_GET['editor']),ENT_QUOTE,'UTF-8');
                $editor=html_entity_decode($_GET['editor'],ENT_QUOTES,'UTF-8');
            }
        }

        if (!empty($_POST)){ // traiter les valeurs
            if (!empty($_POST['editor'])){
                //$editor=urldecode($_POST['editor']);
                // html_entity_decode(urldecode($_POST['editor']),ENT_QUOTE,'UTF-8');
                $editor=html_entity_decode($_POST['editor'],ENT_QUOTES,'UTF-8');
            }
        }

        if (!empty($editor)){

            // DEBUG
            if (preg_match('/\[name\](.*)\[\/name\]/i',$editor,$matches)){
                $nom_fichier=recode_nom($matches[1]);
                // echo 'Nom du fichier : '.$nom_fichier."<br />\n";
            }

            // // $search  = array('[', ']', '&nbsp;');
            // // $replace = array('<', '>', '');
            /*
            $search  = array('[',']');
            $replace = array('<','>');
            $xml='<?xml version="1.0" encoding="UTF-8"?>'."\n".str_replace($search, $replace, strip_tags($editor))."\n";
            */
            $xml='<?xml version="1.0" encoding="UTF-8"?>'."\n".conversion_xml($editor)."\n";

            // enregistrer le fichier
            $nom_complet=enregistrer_fichier($xml, $nom_fichier, $dossier_referentiel, $ext_xml);
            // renvoyer les données
            //echo "Nom du fichier : ".$nom_complet."<br />\nURL : ".$url_serveur_local.'/'.$dossier_referentiel.'/'.$nom_fichier.$ext_xml."\n<br />\n<pre>".htmlentities($xml,ENT_QUOTES,'UTF-8')."</pre>\n";
            echo $dossier_referentiel.'/'.$nom_fichier.$suffixe.$ext_xml;

        }
        else{
            echo "Rien à traiter\n";
        }

    }
    else{
        echo "Rien à traiter\n";
    }

?>