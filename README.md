ls-filebrowser
---------------------

ls-filebrowser is a simple addon for formbehaviors in Lemonstand.

It is designed to work in a backend controller implementing the Db_FormBehavior.

It adds an additional tinymce plugin that can be inserted in the html editor toolbar.

The plugin offers both browse and upload functionalities.

### Usage

filebrowser is defined as a behavior that can be added to an existing backend controller

To use it:

1. Add Filebrowser_Editor_Formbehavior in the controller's `implement` list
2. In your model's define_form_fields, patch the field's html editor config to add the Filebrowser button:
	
	
		$editor_config = System_HtmlEditorConfig::get('cms', 'cms_page_content');
		$field = $this->add_form_field('my_rich_text')->tab('Somewhere')->renderAs(frm_html);
		$editor_config->apply_to_form_field($field);
		$field->htmlPlugins .= ',Filebrowser';
		$field->htmlButtons1 .= ',Filebrowser';
	
	
	_This step would better if you could customize this directly from the editor settings section of the admin (/backdoor/system/editor_config/) but I couldn't find an easy way to do this (open to suggestions)_
	
That's all that's required to get it going. By default it will show all the files available on the server.

### Customisation

#### Providing your own data

You can add a filebrowserPrepareData($recordId) method to your controller to return your own data set.

The function should return a collection of Db_File.

#### Providing your own config

filebrowser has its own default config. If you would like to customise this, simply copy the settings variable form the behavior class onto the controller and name it filebrowser_settings


The default config is:

	$settings = array(
		'thumbnails'=>array(
			'zoom'=>'fit',
			'width'=>100,
			'height'=>100,
			'page_size'=>21
		),
		'insert'=>array(
			'mode'=>'presets',	// only presets mode for now
			'default'=>'Medium',
			'options' => array(
				'Thumbnail'=> array(
					'zoom'=>'fit',
					'width'=>100,
					'height'=>100
				),
				'Medium'=> array(
					'zoom'=>'keep_ratio',
					'width'=>300,
					'height'=>'auto'
				),
				'Large'=> array(
					'zoom'=>'keep_ratio',
					'width'=>500,
					'height'=>'auto'
				)
			)
		)
	);

The `thumbnails` section defines how the thumbnails list will look in the popup.

The `insert` section will define how inserting images will work.

Currently, it only works with _presets_ mode (i.e. predefined insertion settings, kind of like wordpress).

_There's plans for a "custom" mode where the user can specify actual sizes and mode but that hasn't been implemented yet_

_Note: there isn't a lot of checking on the quality of the settings array so if it's crashing you're probably messed up the format_

#### Callback on upload

If you ever need additional processing when a new file is uploaded through the behaviour, you can provide a `filebrowserBeforeSaveFile($file, $recordId, $model)` method in your controller.

