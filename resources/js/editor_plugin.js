// Docu : http://wiki.moxiecode.com/index.php/TinyMCE:Create_plugin/3.x#Creating_your_own_plugins


(function() {
	// Load plugin specific language pack
	tinymce.PluginManager.requireLangPack('Filebrowser');
	
	tinymce.create('tinymce.plugins.Filebrowser', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url) {
			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');

			ed.addCommand('mceFilebrowser', function() {
				
				
				Filebrowser_Core.ed = ed;
				
				new PopupForm(Filebrowser_Settings.load_browser_handler);

			});

			// Register button
			ed.addButton('Filebrowser', {
				cmd : 'mceFilebrowser',
				image : url + '/../images/images.png'
			});
		
			//setTimeout(function(){
			//
			//	ed.execCommand('mceFilebrowser');
			//
			//});
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
					longname  : 'Filebrowser',
					author 	  : 'Gozer',
					authorurl : 'https://github.com/doginthehat/',
					infourl   : 'https://github.com/doginthehat/ls-filebrowser',
					version   : "0.1"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('Filebrowser', tinymce.plugins.Filebrowser);
	
	
})();

var	Filebrowser_Core = {
		send_to_editor : function()
		{
			if (!Filebrowser_Core.ed)
			{
				return;
			}
			
			$(this).getForm().sendPhpr(Filebrowser_Settings.get_image_url_handler,{	
																					loadIndicator: {show: false},
																					onBeforePost: LightLoadingIndicator.show.pass('Transferring to editor...'),
																					onComplete: LightLoadingIndicator.hide,
																					extraFields: { image: this.getAttribute('image')},
																					});
			
		},
		
		send_to_editor_apply : function(url)
		{
		
			Filebrowser_Core.ed.execCommand('mceInsertContent', false, '<img src="'+url+'"/>');		
			
		}
	};
