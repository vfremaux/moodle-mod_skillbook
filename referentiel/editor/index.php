<?php
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
//                                                                       //
// Copyright (C) 2010 Jean Fruitet jean.fruitet@univ-nantes.fr           //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
* Editeur wysiwyg de référentiels de compétence
*/

// editeur de référentiel

$url_serveur_local = $uri.$_SERVER['HTTP_HOST'].':'.$_SERVER['SERVER_PORT'].get_url_pere($_SERVER['SCRIPT_NAME']);
// DEBUG
// echo "<br>URL : $url_serveur_local\n";

$dir_serveur = dirname($_SERVER['SCRIPT_FILENAME']);


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

// -------------------
function envoi_editeur($fichier){
// selectionne un fichier en input
global $appli_editeur;

$s='';
    if (!empty($appli_editeur) && is_file($fichier)){
        $s="<td bgcolor='#ffcccc'><i><a href='$appli_editeur?filename=$fichier'>Choisir</a></i>\n</td>\n";
	}
	else{
        $s="<td bgcolor='#ffaaaa'><i>Erreur</i></td>\n";
    }
    return $s;
}

// DEBUT DU PROGRAMME  ####################################################

    $appli_appelante="dump_xml.php"; // reformate en xml
    $appli_input="editeur_input.php"; // application de selection de fichier en format input
    $data_path="data";      // dossier ou sont archives les referentiels saisis avec l'éditeur
                           // a ne pas confondre avec les référentiels intégrés à Moodle


    // charger le contenu du fichier
    $id=0;
    $d=0;
    $filename='';
    $editor='';
    $sesskey='';
    $return_link='';

    if (!empty($_GET) || !empty($_POST)){
        //
        if (!empty($_GET)){
            if (!empty($_GET['filename'])){
                $filename=$data_path.'/'.$_GET['filename'];
            }
            if (!empty($_GET['id'])){
                $id=$_GET['id'];
            }
            if (!empty($_GET['d'])){
                $d=$_GET['d'];
            }
            if (!empty($_GET['sesskey'])){
                $sesskey=$_GET['sesskey'];
            }
            if (!empty($_GET['return_link'])){
                $return_link=$_GET['return_link'];
            }
        }
        if (!empty($_POST)){ // traiter les valeurs
            if (!empty($_POST['editor'])){
                $filename=$data_path.'/'.$_POST['filename'];
            }
            if (!empty($_POST['id'])){
                $id=$_POST['id'];
            }
            if (!empty($_POST['d'])){
                $d=$_POST['d'];
            }
            if (!empty($_POST['sesskey'])){
                $sesskey=$_POST['sesskey'];
            }
            if (!empty($_POST['return_link'])){
                $return_link='../'.$_POST['return_link'];
            }

        }
        if (!empty($filename)){
        // DEBUG

            // ouvrir le fichier
            if (file_exists($filename)){
                // DEBUG
                // echo "FILENAME : <a href='$filename'>$filename</a>\n";
                $editor=file_get_contents($filename);
            }
        }
        if (!empty($editor)){
            $allowedTags='<p><br><br />';
            $data = strip_tags(stripslashes($editor),$allowedTags);
            $search  = array('<', '>', '&nbsp;');
            $replace = array('[', ']', '');
            $editor=str_replace($search, $replace, $editor);
            // echo "EDITOR : <br><pre>$editor</pre>\n";
        }
    }


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="author" content="jean.fruitet@univ-nantes.fr">
  <title>Editeur de référentiel de compétences</title>
  <link type="text/css" href="general.css" rel="stylesheet">


<script type="text/javascript" src="./tiny_mce/tiny_mce.js"></script>
<script src="ajax/ajax.js" type="text/javascript"></script>
<script type="text/javascript">

tinyMCE.init({
    language : "fr", // change language here
	theme : "advanced",
	mode : "textareas",
    plugins : "referentiel, save, advhr,inlinepopups,insertdatetime,preview,searchreplace,print,contextmenu,paste,fullscreen,xhtmlxtras,template,wordcount,autosave,visualchars",
	theme_advanced_buttons1 : "newdocument,print,save,|,item,competence,domaine,referentiel,|,idcode,nom,text,url,|,fontsizeselect,|,selectall,copy,cut,paste,pastetext,pasteword,|,search,replace,undo,redo,cleanup,code,|,fullscreen,help", // visualchars,
    theme_advanced_buttons2 : "",
    theme_advanced_buttons3 : "",
    theme_advanced_buttons4 : "",
    theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "left",
	theme_advanced_statusbar_location : "bottom",
	theme_advanced_resizing : true,
	save_enablewhendirty : false,
	// save_onsavecallback : "MySave"
	save_onsavecallback : "ajaxSave"

	// paste plugin
	/*
[paste_auto_cleanup_on_paste]
    If enabled contents will be automatically processed when you paste using Ctrl+V or similar methods. This is enabled by default.
[paste_preprocess]
    Callback function to execute before the contents is processed into a DOM structure. This callback enables you to do regexp replaces on the clipboard contents before it's inserted.
[paste_postprocess]
    Callback function to execute after the contents has been converted into a DOM structure. This callback enables you to do DOM manipulation on the clipboard nodes before they get inserted.
	*/
    /*
    // A modifer si utile voir plugins/paste wiki
    ,
	        paste_auto_cleanup_on_paste : true,
        paste_preprocess : function(pl, o) {
            // Content string containing the HTML from the clipboard
            alert(o.content);
        },
        paste_postprocess : function(pl, o) {
            // Content DOM node containing the DOM structure of the clipboard
            alert(o.node.innerHTML);
    */
});


function toggleEditor(id) {
	if (!tinyMCE.get(id))
		tinyMCE.execCommand('mceAddControl', false, id);
	else
		tinyMCE.execCommand('mceRemoveControl', false, id);
}

// AJAX IMPLANTATION

/*
Noter qu'il faut créer un nouvel objet XMLHttpRequest, pour chaque fichier que vous voulez charger.
Il faut attendre la disponibilité des données, et l'état est donné par l'attribut readyState de XMLHttpRequest.

Les états de readyState sont les suivants (seul le dernier est vraiment utile):
0: non initialisé.
1: connexion établie.
2: requête reçue.
3: réponse en cours.
4: terminé.
*/

/*
La récursivité n'est pas assurée. Ainsi du code javascript présent dans la page chargée via XMLHttpRequest ne sera pas exécuté.
Il faut extraire le code javascript depuis la page mère afin de l'exécuter.
Le code suivant exécute le javascript présent entre des balises <script><ESPACE_A_SUPPRIMER/script>
dans la page fille, après l'avoir chargé comme décrit précédemment :
*/
// Extraire du code JavaScript de la page fille (renvoyee par le serveur)
/*
if (xhr.readyState == 4)      // pas d'erreur, requête terminée
{
	document.getElementById('contenu').innerHTML = xhr.responseText;
	var js = document.getElementById('contenu').getElementsByTagName('script');
	for( var i in js )
	{
		eval(js[i].text);
	}
	//
}

// recuperer le contenu XML de la page fille (renvoyee par le serveur)
if (xhr.readyState == 4)      // pas d'erreur, requête terminée
{

	var contenu_xml = xhr.responseXml;
    if (contenu_xml){
        window.alert(contenu_xml);
	}
    //
}
*/

function processData(s)
{
   /*
    s=s.replace(/</g, "&lt;");
    s=s.replace(/>/gi, "&gt;");
    s=s.replace(/\\n/g, "<br />");
   */
    /*
    if (s.length>1024){
        s=s.substr(0, 1024)+ " (...)\n";
    }
    */

    return s;
}


function ajaxSave() {
  var sesskey='<?php echo $sesskey; ?>';
  var id=<?php echo $id; ?>;
  var d=<?php echo $d; ?>;
  var url='<?php echo $url_serveur_local; ?>';
  var dir_serveur='<?php echo $dir_serveur; ?>';
  
    if (id && (sesskey!='')){
        var link= "import_referentiel_xml.php?id="+id+"&amp;sesskey="+sesskey; // +"&amp;sesskey="+sesskey;
    }
    else if (d && (sesskey!='')){
        var link= "import_referentiel_xml.php?d="+d+"&amp;sesskey="+sesskey;
    }
    else{
        var link= "";
    }
	var ed = tinyMCE.get('content');
    var s=ed.getContent();
    s=s.replace(/\&nbsp;/g, "");

    // window.alert(ed.getContent());
	ed.setProgressState(1); // Show progress

    var data_sent = "editor="+encodeURIComponent(s);
    // window.alert(data_sent);

	var xhr=createXHR();
    if (xhr){
        xhr.open("POST", "dump_xml.php", true);
        xhr.onreadystatechange=function(){
            ed.setProgressState(0); // Hide progress
            if(xhr.readyState == 4){
            
                if (link!=''){
                    document.getElementById("zone").innerHTML= "<a href=\""+url+"/"+xhr.responseText+"\"><b>Fichier créé</b></a>"+"&nbsp; &nbsp; &nbsp; <a href=\""+link+"&amp;file="+dir_serveur+"/"+xhr.responseText+"\"><b>Importer dans Moodle</b></a><br />"+processData(xhr.responseText);
                }
                else{
                    document.getElementById("zone").innerHTML= "<a href=\""+url+"/"+xhr.responseText+"\"><b>Fichier créé</b></a><br />"+processData(xhr.responseText);
                }
		  }
        };
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.send(data_sent);
    }
}
</script>



</head>
<body>
<table width="100%" cellspacing="0" cellpadding="2"><tr valign="top"><td>
<h3>Editeur de Référentiel de compétence pour le <a href="http://moodle.org/mod/data/view.php?d=13&rid=2488">module Référentiel</a></h3>
</td><td>(c)<a href="mailto:jean.fruitet@univ-nantes.fr">JF</a> - Version 0.5 - Mai 2010<br>
Basé sur <a href="http://tinymce.moxiecode.com/examples/">TinyMCE</a></td><td>
<?php
    if (!empty($return_link)&& !empty($id) && !empty($sesskey)){
        echo '<a href="../'.$return_link.'?id='.$id.'&amp;sesskey='.$sesskey.'"><b>Revenir au module Référentiel</b></a>'."<br>\n";
    }
?>
<a href="./tiny_mce/plugins/referentiel/readme.html" target="_blank">Aide</a>
</td>
</tr></table>
<div id="zone" class="small">
Pour enregistrer le r&eacute;f&eacute;rentiel cliquez sur le bouton <img src="img/save.gif" border="0" title="Enregistrer" alt="enregistrer">
</div>

<div align="center">
<form method="post" name="saisie" action="">

	<textarea name="content" id="content" style="width:90%" rows="30"><?php echo $editor;?></textarea>
<br>
	<a href="#" onclick="tinyMCE.execCommand('mceToggleEditor',false,'content');">[Bascule WYSIWYG]</a>
    <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
    <input type="hidden" name="d" id="d" value="<?php echo $d; ?>" />
</form>
</div>

<!--
<a href="javascript:toggleEditor('content');">Add/Remove editor</a>
-->
  </body>
</html>
