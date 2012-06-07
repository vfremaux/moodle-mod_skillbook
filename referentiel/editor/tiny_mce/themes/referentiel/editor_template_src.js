/**
 * editor_template_src.js
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://tinymce.moxiecode.com/license
 * Contributing: http://tinymce.moxiecode.com/contributing
 */

(function() {
	var DOM = tinymce.DOM;

	// Tell it to load theme specific language pack(s)
	tinymce.ThemeManager.requireLangPack('referentiel');

	tinymce.create('tinymce.themes.ReferentielTheme', {
		init : function(ed, url) {
			var t = this, states = ['Referentiel', 'Domaine', 'Competence', 'Item'], s = ed.settings;

			t.editor = ed;

			ed.onInit.add(function() {
				ed.onNodeChange.add(function(ed, cm) {
					tinymce.each(states, function(c) {
						cm.get(c.toLowerCase()).setActive(ed.queryCommandState(c));
					});
				});

				ed.dom.loadCSS(url + "/skins/" + s.skin + "/content.css");
			});

			DOM.loadCSS((s.editor_css ? ed.documentBaseURI.toAbsolute(s.editor_css) : '') || url + "/skins/" + s.skin + "/ui.css");
		},

		renderUI : function(o) {
			var t = this, n = o.targetNode, ic, tb, ed = t.editor, cf = ed.controlManager, sc;

			n = DOM.insertAfter(DOM.create('span', {id : ed.id + '_container', 'class' : 'mceEditor ' + ed.settings.skin + 'ReferentielSkin'}), n);
			n = sc = DOM.add(n, 'table', {cellPadding : 0, cellSpacing : 0, 'class' : 'mceLayout'});
			n = tb = DOM.add(n, 'tbody');

			// Create iframe container
			n = DOM.add(tb, 'tr');
			n = ic = DOM.add(DOM.add(n, 'td'), 'div', {'class' : 'mceIframeContainer'});

			// Create toolbar container
			n = DOM.add(DOM.add(tb, 'tr', {'class' : 'last'}), 'td', {'class' : 'mceToolbar mceLast', align : 'center'});

			// Create toolbar
			tb = t.toolbar = cf.createToolbar("tools1");
			tb.add(cf.createButton('undo', {title : 'referentiel.undo_desc', cmd : 'Undo'}));
			tb.add(cf.createButton('redo', {title : 'referentiel.redo_desc', cmd : 'Redo'}));
			tb.add(cf.createSeparator());
			tb.add(cf.createButton('cleanup', {title : 'referentiel.cleanup_desc', cmd : 'mceCleanup'}));
			tb.add(cf.createSeparator());
			tb.renderTo(n);

			return {
				iframeContainer : ic,
				editorContainer : ed.id + '_container',
				sizeContainer : sc,
				deltaHeight : -20
			};
		},

		getInfo : function() {
			return {
				longname : 'Referentiel theme',
				author : 'JF',
				authorurl : 'http://univ-nantes.fr',
				version : '0.1'
			}
		}
	});

	tinymce.ThemeManager.add('referentiel', tinymce.themes.ReferentielTheme);
})();