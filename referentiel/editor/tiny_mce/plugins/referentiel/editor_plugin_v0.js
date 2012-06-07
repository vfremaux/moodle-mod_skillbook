/**
 * editor_plugin_src.js
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://tinymce.moxiecode.com/license
 * Contributing: http://tinymce.moxiecode.com/contributing
 */
(function() {
	// Load plugin specific language pack
	// tinymce.PluginManager.requireLangPack('referentiel');

	tinymce.create('tinymce.plugins.ReferentielPlugin', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url) {
			var t = this;

			t.editor = ed;
            dialect = 'moodle';

			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');
			
		    ed.addCommand('mceReferentiel', t._addReferentiel, t);
		    ed.addCommand('mceDomaine', t._addDomaine, t);
		    ed.addCommand('mceCompetence', t._addCompetence, t);
		    ed.addCommand('mceItem', t._addItem, t);
            ed.addCommand('mceIdCode', t._addIdCode, t);
            ed.addCommand('mceNom', t._addNom, t);
            ed.addCommand('mceText', t._addText, t);
            ed.addCommand('mceUrlRef', t._addUrl, t);
            
            // Register referentiel button
			ed.addButton('referentiel', {
				title : 'referentiel',
				cmd : 'mceReferentiel',
				image : url + '/img/referentiel.gif'
			});

			// Register domaine button
			ed.addButton('domaine', {
				title : 'domaine',
				cmd : 'mceDomaine',
				image : url + '/img/domaine.gif'
			});

			// Register competence button
			ed.addButton('competence', {
				title : 'competence',
				cmd : 'mceCompetence',
				image : url + '/img/competence.gif'
			});

			// Register item button
			ed.addButton('item', {
				title : 'item',
				cmd : 'mceItem',
				image : url + '/img/item.gif'
			});

			// Register code button
			ed.addButton('idcode', {
				title : 'idcode',
				cmd : 'mceIdCode',
				image : url + '/img/code.gif'
			});

			// Register nom button
			ed.addButton('nom', {
				title : 'nom',
				cmd : 'mceNom',
				image : url + '/img/nom.gif'
			});
			// Register definition button
			ed.addButton('text', {
				title : 'definition',
				cmd : 'mceText',
				image : url + '/img/text.gif'
			});


 			// Register definition button
			ed.addButton('url', {
				title : 'url',
				cmd : 'mceUrlRef',
				image : url + '/img/url.gif'
			});


			ed.onBeforeSetContent.add(function(ed, o) {
				o.content = t['_' + dialect + '_referentiel2html'](o.content);
			});

			ed.onPostProcess.add(function(ed, o) {
				if (o.set)
					o.content = t['_' + dialect + '_referentiel2html'](o.content);

				if (o.get)
					o.content = t['_' + dialect + '_html2referentiel'](o.content);
			});



			// Add a node change handler, selects the button in the UI when a image is selected
			ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('referentiel', n.nodeName == 'IMG');
			});

		},

		/**
		 * Creates control instances based in the incomming name. This method is normally not
		 * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
		 * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
		 * method can be used to create those.
		 *
		 * @param {String} n Name of the control to create.
		 * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
		 * @return {tinymce.ui.Control} New control instance or null if no control was created.
		 */
		createControl : function(n, cm) {
			return null;
		},

		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo : function() {
			return {
				longname : 'Referentiel plugin',
				author : 'J.F.',
				authorurl : 'http://univ-nantes.fr',
				infourl : '../../plugins/referentiel/readme.html',
				version : "1.0"
			};
		},


		// Private methods

 		_addReferentiel: function() {

            var ed = this.editor, formObj;
			formObj = tinymce.DOM.get(ed.id).form || tinymce.DOM.getParent(ed.id, 'form');
			if (formObj) {
                var selection = ed.selection.getContent({format : 'text'});
                tinymce.trim(selection);
                if (selection){
                    // rechercher le idcode
                    var idcode = tinymce.trim(selection.substr(0,selection.indexOf(" ",0)));

                    if (idcode){
                        // ed.windowManager.alert("IDCODE: "+idcode);
                        var le_reste = tinymce.trim(selection.substring(selection.indexOf(" ",0)+1 , selection.length));
                        // var definition = selection.substring(selection.indexOf(" ",0)+1 , selection.indexOf("[",0) - 1);

                        if (le_reste){
                            // ed.windowManager.alert("Le reste:"+le_reste);
                            var definition = tinymce.trim(le_reste.substring(0,le_reste.indexOf("[",0), le_reste.length));
                            if (definition){
                                // ed.windowManager.alert("Definition:"+definition);
                                var corpus = tinymce.trim(le_reste.substring(le_reste.indexOf("[",0),le_reste.length));
                                if (corpus){
                                    // ed.windowManager.alert("Corpus:"+corpus);
                                    var str_html  = "<br>[referentiel]<br>"+"[idcode]"+idcode+"[/idcode]<br>[name]<br><br>[/name]<br>[definition][text]"+definition+"[/text][/definition]<br>"+corpus+"<br>[/referentiel]";
                                }
                                else{
                                    var str_html  = "<br>[referentiel]<br>"+"[idcode]"+idcode+"[/idcode][name]<br><br>[/name]<br>[definition][text]"+definition+"[/text][/definition]<br>[/referentiel]";
                                }
                            }
                            else{
                              var str_html  = "<br>[referentiel]<br>"+"[idcode]"+idcode+"[/idcode][name]<br><br>[/name]<br>[definition][text]"+le_reste+"[/text][/definition]<br>[/referentiel]";
                            }
                        }
                        else{
                            var str_html  = "<br>[referentiel]<br>[idcode]"+idcode+"[/idcode][name]<br><br>[/name]<br>[definition][text]<br><br>[/text][/definition]<br>[/referentiel]";
                        }
                    }
                    else{
                        var str_html  = "<br>[referentiel]<br>[idcode]<br><br>[/idcode][name]<br><br>[/name]<br>[definition][text]"+selection+"[/text][/definition]<br><br>[/referentiel]";
                    }

                    ed.execCommand('mceReplaceContent', false, str_html );
                    //ed.undoManager.clear();
                    ed.nodeChanged();

                }
            } else {
				ed.windowManager.alert("Error: No form element found.");
            }
		},


 		_addDomaine: function() {

            var ed = this.editor, formObj;
			formObj = tinymce.DOM.get(ed.id).form || tinymce.DOM.getParent(ed.id, 'form');
			if (formObj) {
                var selection = ed.selection.getContent({format : 'text'});
                tinymce.trim(selection);

                if (selection){
                    // rechercher le idcode
                    var idcode = tinymce.trim(selection.substr(0,selection.indexOf(" ",0)));

                    if (idcode){
                        // ed.windowManager.alert("IDCODE: "+idcode);
                        var le_reste = tinymce.trim(selection.substring(selection.indexOf(" ",0)+1 , selection.length));
                        // var definition = selection.substring(selection.indexOf(" ",0)+1 , selection.indexOf("[",0) - 1);

                        if (le_reste){
                            // ed.windowManager.alert("Le reste:"+le_reste);
                            var definition = tinymce.trim(le_reste.substring(0,le_reste.indexOf("[",0), le_reste.length));
                            if (definition){
                                // ed.windowManager.alert("Definition:"+definition);
                                var corpus = tinymce.trim(le_reste.substring(le_reste.indexOf("[",0),le_reste.length));
                                if (corpus){
                                    // ed.windowManager.alert("Corpus:"+corpus);
                                    var str_html  = "<br>[domaine]<br>"+"[idcode]"+idcode+"[/idcode]<br>[definition][text]"+definition+"[/text][/definition]<br>"+corpus+"<br>[/domaine]";
                                }
                                else{
                                    var str_html  = "<br>[domaine]<br>"+"[idcode]"+idcode+"[/idcode][definition][text]"+definition+"[/text][/definition]<br>[/domaine]";
                                }
                            }
                            else{
                              var str_html  = "<br>[domaine]<br>"+"[idcode]"+idcode+"[/idcode][definition][text]"+le_reste+"[/text][/definition]<br>[/domaine]";
                            }
                        }
                        else{
                            var str_html  = "<br>[domaine]<br>[idcode]"+idcode+"[/idcode][definition][text]<br><br>[/text][/definition]<br>[/domaine]";
                        }
                    }
                    else{
                        var str_html  = "<br>[domaine]<br>[idcode]<br><br>[/idcode][definition][text]"+selection+"[/text][/definition]<br><br>[/domaine]";
                    }

                    ed.execCommand('mceReplaceContent', false, str_html );
                    //ed.undoManager.clear();
                    ed.nodeChanged();
                }
            } else {
				ed.windowManager.alert("Error: No form element found.");
            }

		},

 		_addCompetence: function() {

            var ed = this.editor, formObj;
			formObj = tinymce.DOM.get(ed.id).form || tinymce.DOM.getParent(ed.id, 'form');
			if (formObj) {
                var selection = ed.selection.getContent({format : 'text'});
                tinymce.trim(selection);

                if (selection){
                    //ed.windowManager.alert(selection);
                    // rechercher le idcode
                    var idcode = tinymce.trim(selection.substr(0,selection.indexOf(" ",0)));

                    if (idcode){
                        // ed.windowManager.alert("IDCODE: "+idcode);
                        var le_reste = tinymce.trim(selection.substring(selection.indexOf(" ",0)+1 , selection.length));
                        // var definition = selection.substring(selection.indexOf(" ",0)+1 , selection.indexOf("[",0) - 1);

                        if (le_reste){
                            // ed.windowManager.alert("Le reste:"+le_reste);
                            var definition = tinymce.trim(le_reste.substring(0,le_reste.indexOf("[",0), le_reste.length));
                            if (definition){
                                // ed.windowManager.alert("Definition:"+definition);
                                var corpus = tinymce.trim(le_reste.substring(le_reste.indexOf("[",0),le_reste.length));
                                if (corpus){
                                    // ed.windowManager.alert("Corpus:"+corpus);
                                    var str_html  = "<br>[competence]<br>"+"[idcode]"+idcode+"[/idcode]<br>[definition][text]"+definition+"[/text][/definition]<br>"+corpus+"<br>[/competence]";
                                }
                                else{
                                    var str_html  = "<br>[competence]<br>"+"[idcode]"+idcode+"[/idcode][definition][text]"+definition+"[/text][/definition]<br>[/competence]";
                                }
                            }
                            else{
                              var str_html  = "<br>[competence]<br>"+"[idcode]"+idcode+"[/idcode][definition][text]"+le_reste+"[/text][/definition]<br>[/competence]";
                            }
                        }
                        else{
                            var str_html  = "<br>[competence]<br>[idcode]"+idcode+"[/idcode][definition][text]<br><br>[/text][/definition]<br>[/competence]";
                        }
                    }
                    else{
                        var str_html  = "<br>[competence]<br>[idcode]<br><br>[/idcode][definition][text]"+selection+"[/text][/definition]<br><br>[/competence]";
                    }

                    // var str_html  = "<br>[competence]<br>"+selection+"<br>[/competence]";
                    ed.execCommand('mceReplaceContent', false, str_html );
                    //ed.undoManager.clear();
                    ed.nodeChanged();
                }
            } else {
				ed.windowManager.alert("Error: No form element found.");
            }

		},

 		_addItem: function() {

            var ed = this.editor, formObj;
			formObj = tinymce.DOM.get(ed.id).form || tinymce.DOM.getParent(ed.id, 'form');
			if (formObj) {
                var selection = ed.selection.getContent({format : 'text'});
                tinymce.trim(selection);

                if (selection){
                    //ed.windowManager.alert(selection);
                    // rechercher le idcode
                    var idcode = selection.substr(0,selection.indexOf(" ",0));
                    // ed.windowManager.alert(idcode);
                    if (idcode){
                        var definition = selection.substr(selection.indexOf(" ",0), selection.length);

                        if (definition){
                            var str_html  = "<br>[item]<br>"+"[idcode]"+idcode+"[/idcode]<br>[definition][text]"+definition+"[/text][/definition]<br>[/item]";
                        }
                        else{
                            var str_html  = "<br>[item]<br>[idcode]"+idcode+"[/idcode]<br>[definition][text]"+selection+"[/text][/definition]<br>[/item]";
                        }
                    }
                    else{
                        var str_html  = "<br>[item]<br>[idcode]<br><br>[/idcode]<br>[definition][text]<br><br>[/text][/definition][/item]";
                    }

                    ed.execCommand('mceReplaceContent', false, str_html );
                    //ed.undoManager.clear();
                    ed.nodeChanged();
                }
            } else {
				ed.windowManager.alert("Error: No form element found.");
            }

		},

        _addIdCode: function() {

            var ed = this.editor, formObj;
			formObj = tinymce.DOM.get(ed.id).form || tinymce.DOM.getParent(ed.id, 'form');
			if (formObj) {
                var selection = ed.selection.getContent({format : 'text'});
                tinymce.trim(selection);

                if (selection){
                    //ed.windowManager.alert(selection);
                    var str_html  = "<br>[idcode]<br>"+selection+"<br>[/idcode]</br>";
                    ed.execCommand('mceReplaceContent', false, str_html );
                    // ed.undoManager.clear();
                    ed.nodeChanged();
                }
            } else {
				ed.windowManager.alert("Error: No form element found.");
            }

		},

        _addNom: function() {

            var ed = this.editor, formObj;
			formObj = tinymce.DOM.get(ed.id).form || tinymce.DOM.getParent(ed.id, 'form');
			if (formObj) {
                var selection = ed.selection.getContent({format : 'text'});
                tinymce.trim(selection);

                if (selection){
                    //ed.windowManager.alert(selection);
                    var str_html  = "<br>[name]<br>"+selection+"<br>[/name]</br>";
                    ed.execCommand('mceReplaceContent', false, str_html );
                    // ed.undoManager.clear();
                    ed.nodeChanged();
                }
            } else {
				ed.windowManager.alert("Error: No form element found.");
            }

		},

        _addText: function() {

            var ed = this.editor, formObj;
			formObj = tinymce.DOM.get(ed.id).form || tinymce.DOM.getParent(ed.id, 'form');
			if (formObj) {
                var selection = ed.selection.getContent({format : 'text'});
                tinymce.trim(selection);

                if (selection){
                    //ed.windowManager.alert(selection);
                    var str_html  = "<br>[definition][text]<br>"+selection+"<br>[/text][definition]</br>";
                    ed.execCommand('mceReplaceContent', false, str_html );
                    // ed.undoManager.clear();
                    ed.nodeChanged();
                }
            } else {
				ed.windowManager.alert("Error: No form element found.");
            }

		},

        _addUrl: function() {

            var ed = this.editor, formObj;
			formObj = tinymce.DOM.get(ed.id).form || tinymce.DOM.getParent(ed.id, 'form');
			if (formObj) {
                var selection = ed.selection.getContent({format : 'text'});
                tinymce.trim(selection);

                if (selection){
                    //ed.windowManager.alert(selection);
                    var str_html  = "[url]"+selection+"[/url]";
                    ed.execCommand('mceReplaceContent', false, str_html );
                    // ed.undoManager.clear();
                    ed.nodeChanged();
                }
            } else {
				ed.windowManager.alert("Error: No form element found.");
            }

		},
		// HTML -> Referentiel

		_moodle_html2referentiel : function(s) {
			s = tinymce.trim(s);

			function rep(re, str) {
				s = s.replace(re, str);
			};

			// referentiel: <strong> to [b]
            rep(/<a.*?href=\"(.*?)\".*?>(.*?)<\/a>/gi,"[url=$1]$2[/url]");
            
//            rep(/\r\n/>/gi,"<br>\n");
/*
			rep(/<br \/>/gi,"\n");
			rep(/<br\/>/gi,"\n");
			rep(/<br>/gi,"\n");
*/
/*
            rep(/&nbsp;/gi,"");
			rep(/\[referentiel\]/gi,"[referentiel]");
			rep(/\[\/referentiel\]/gi,"<br>[/referentiel]<br>\n");
			rep(/\[domaine\]/gi,"<br>\n &nbsp; [domaine]");
			rep(/\[\/domaine\]/gi,"<br> &nbsp; [/domaine]");
            rep(/\[competence\]/gi,"<br>\n &nbsp; &nbsp; &nbsp; [competence]");
			rep(/\[\/competence\]/gi,"<br>\n &nbsp; &nbsp; &nbsp; [/competence]");
            rep(/\[item\]/gi,"<br>\n &nbsp; &nbsp; &nbsp; &nbsp; [item]");
			rep(/\[\/item\]/gi,"<br>\n &nbsp; &nbsp; &nbsp; &nbsp; [/item]");
            rep(/\[idcode\]/gi,"<br>\n &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; [idcode]");
			rep(/\[\/idcode\]/gi,"<br>\n &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; [/idcode]");
            rep(/\[name\]/gi,"<br>\n &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; [name]");
			rep(/\[\/name\]/gi,"<br>\n &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; [/name]");
            rep(/\[definition\]\[text\]/gi,"<br>\n &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; [definition][text]");
			rep(/\[\/text\]\[\/definition\]/gi,"<br>\n &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; [/text][/definition]");
            rep(/\[url=([^\]]+)\](.*?)\[\/url\]/gi,"<a href=\"$1\">$2</a>");
			rep(/\[url\](.*?)\[\/url\]/gi,"<a href=\"$1\">$1</a>");
*/
            return s;
		},

		// Referentiel -> HTML
		_moodle_referentiel2html : function(s) {
			s = tinymce.trim(s);

			function rep(re, str) {
				s = s.replace(re, str);
			};

			// referentiel: [b] to <strong>
			rep(/&nbsp;/gi,"");
            /*
            rep(/<br \/>/gi,"");
			rep(/<br\/>/gi,"");
			rep(/<br>/gi,"");
            */

			rep(/\[referentiel\]/gi,"[referentiel]");
			rep(/\[\/referentiel\]/gi,"<br>[/referentiel]<br>\n");
			rep(/\[domaine\]/gi,"<br>\n &nbsp; [domaine]");
			rep(/\[\/domaine\]/gi,"<br> &nbsp; [/domaine]");
            rep(/\[competence\]/gi,"<br>\n &nbsp; &nbsp; &nbsp; [competence]");
			rep(/\[\/competence\]/gi,"<br>\n &nbsp; &nbsp; &nbsp; [/competence]");
            rep(/\[item\]/gi,"<br>\n &nbsp; &nbsp; &nbsp; &nbsp; [item]");
			rep(/\[\/item\]/gi,"<br>\n &nbsp; &nbsp; &nbsp; &nbsp; [/item]");
            rep(/\[idcode\]/gi,"<br>\n &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; [idcode]");
			rep(/\[\/idcode\]/gi,"<br>\n &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; [/idcode]");
            rep(/\[name\]/gi,"<br>\n &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; [name]");
			rep(/\[\/name\]/gi,"<br>\n &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; [/name]");
            rep(/\[definition\]\[text\]/gi,"<br>\n &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; [definition][text]");
			rep(/\[\/text\]\[\/definition\]/gi,"<br>\n &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; [/text][/definition]");
            rep(/\[url=([^\]]+)\](.*?)\[\/url\]/gi,"<a href=\"$1\">$2</a>");
			rep(/\[url\](.*?)\[\/url\]/gi,"<a href=\"$1\">$1</a>");
			
			return s;
		}

	});

	// Register plugin
	tinymce.PluginManager.add('referentiel', tinymce.plugins.ReferentielPlugin);
})();