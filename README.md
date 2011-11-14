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
	
3. [Optional] If your controller view uses a custom head block content [ie. `Phpr_View::beginBlock("head")`] to load resources, you *must* pass `true` to the matching `endBlock()`.
	If you don't, because the filebrowser behaviour has already written to the view, your custom content will simply be ignore by the view engine on Lemonstand.
	
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

### Adding the filebrowser to the cms pages controller (Editing Content)

_I'm guessing someone will eventually want to do this_

It's straight forward enough - though it currently needs tweaking some core files which is far from ideal (see below). 

In a custom module, you'll need to hook into a couple of events as follow:

	/*
		First, subscribe to event onControllerReady.
		Note, we use the generic onControllerReady event because there's no page-specific
		event you can hook to when editing page content unfortunately.
		
		Add this line in your module's subscribeEvents method
	*/
	Backend::$events->addEvent('backend:onControllerReady', $this, 'on_controller_ready');

	/*
		Then add the code for the event handler 
	*/
	function on_controller_ready($controller)
	{
		if ($controller instanceof Cms_Pages)
			$controller->extend_with('Filebrowser_Editor_Formbehavior');
				
	}


	/*
		You also need to extend the page model to add the Filebrowser button to the editor.

		Add this line in your module's subscribeEvents method
	*/
	Backend::$events->addEvent('cms:onExtendPageForm', $this, 'on_extend_page_form');
	
	/*
		Then add the code for the event handler 
	*/
	function on_extend_page_form($page, $context)
	{
		if ($context == 'content')
		{
			$blocks = $page->list_content_blocks();
			foreach ($blocks as $block)
			{
				$column_name = 'content_block_'.$block->code;
				$field = $page->find_form_field($column_name);
	
				$field->htmlPlugins .= ',Filebrowser';
				$field->htmlButtons1 .= ',Filebrowser';
					
			}
		}
	}

Now for the annoying bit.

I can't really see at this stage a better spot to add some of the required code for the module, and because of the problem mentioned in point 3 in the usage section above, you'll need to tweak the cms view to prevent the module from overwriting its own head content.
_I did [request an additional event](http://forum.lemonstandapp.com/index.php?app=tracker&showissue=83) that would prevent that problem as it would give me a better hook spot. Maybe when that's fixed this won't be necessary.._

In the mean time: Open `modules/cms/controllers/cms_pages/edit.htm' and add `true` to the first endBlock() - for the head section.

That's it.
