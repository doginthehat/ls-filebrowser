<?php
/**
 * Filebrowser Editor Formbehavior
 */
class Filebrowser_Editor_Formbehavior extends Phpr_ControllerBehavior
{

	static protected $settings = array(
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

	protected $prepared = false;

	public function __construct($controller)
	{
		parent::__construct($controller);
	
		$this->_controller->addCss('/modules/filebrowser/resources/css/filebrowser.css');

		$this->addEventHandler('onLoadBrowser');
		$this->addEventHandler('onGetImageUrl');

		$this->addEventHandler('onFilebrowserPrevPage');
		$this->addEventHandler('onFilebrowserNextPage');
		$this->addEventHandler('onFilebrowserSetPage');

		$this->hideAction('filebrowserGetUploadUrl');

		$this->_controller->addPublicAction('filebrowser_upload');

		Phpr_View::beginBlock( 'head' );
		$this->renderPartial('init');
		Phpr_View::endBlock( 'head' );


	}
	
	
	public function onLoadBrowser($recordId = null)
	{
	
		$this->viewData['recordId'] = $recordId;
		
		$this->prepareData();
		
		$this->renderPartial('browser');
	}
	
	public function prepareData()
	{
		if ($this->prepared)
			return;
	
		if ($this->_controller->filebrowser_settings)
			$settings = $this->_controller->filebrowser_settings;
		else
			$settings = self::$settings;
	
		if ($new_settings = Backend::$events->fireEvent('filebrowser:onFileBrowserSettings', $settings, $this))
		{
			$settings = $new_settings;
		}
		
		$this->viewData['settings'] = $settings;

		if (method_exists($this->_controller,'filebrowserPrepareData'))
			$files = $this->_controller->filebrowserPrepareData($this->viewData['recordId']);
		else
		{
			$files = Backend::$events->fireEvent('filebrowser:onGetFiles', $this->viewData['recordId'], $this);
		
			if (!$files)
			{
				$files = Db_File::create()->where("mime_type like 'image/%'")->order('created_at desc');
			}
		}
		
	
		$page_index = $this->evalPageNumber();
	
		$pagination = $files->paginate($page_index, $settings['thumbnails']['page_size']);

		$this->viewData['files'] = $files->find_all();

		$this->viewData['pagination'] = $pagination;	

		$this->prepared = true;

	}
	
	public function renderFiles($recordId)
	{
		$this->viewData['recordId'] = $recordId;
		
		$this->prepareData();

		$this->renderPartial('files');
	}
	
	public function onFilebrowserNextPage($recordId = null)
	{
		try
		{
			$page = $this->evalPageNumber() + 1;
			$this->setPageNumber($page);
		
			$this->renderFiles($recordId);
		}
		catch (Exception $ex)
		{
			Phpr::$response->ajaxReportException($ex, true, true);
		}
	
	}
	
	public function onFilebrowserPrevPage($recordId = null)
	{
		try
		{
			$page = $this->evalPageNumber() - 1;
			$this->setPageNumber($page);
		
			$this->renderFiles($recordId);
		}
		catch (Exception $ex)
		{
			Phpr::$response->ajaxReportException($ex, true, true);
		}
	
	}
	
	public function onFilebrowserSetPage($recordId = null)
	{
		try
		{
			$this->setPageNumber(post('pageIndex'));
		
			$this->renderFiles($recordId);
		}
		catch (Exception $ex)
		{
			Phpr::$response->ajaxReportException($ex, true, true);
		}
	
	}
	
	public function onGetImageUrl()
	{
		try
		{

			$settings = self::$settings;
		
			if ($new_settings = Backend::$events->fireEvent('filebrowser:onFileBrowserSettings', $settings, $this))
			{
				$settings = $new_settings;
			}
			
			$image = post('image');
			$size = post('size');

			$setting = $settings['insert']['options'][$size];
			
			$file = Db_File::create()->find_by_id($image);
			
			$zoom = isset($setting['zoom']) ? $setting['zoom'] : 'fit';
			
			$path = $file->getThumbnailPath($setting['width'], $setting['height'],true,array('mode' => $zoom));
		
			$this->viewData['path'] = $path;
			
			$this->renderPartial('apply');

		}
		catch (Exception $ex)
		{
			Phpr::$response->ajaxReportException($ex, true, true);
		}
	}
	
	protected function evalPageNumber()
	{
		return Phpr::$session->get($this->browserGetName().'_page', 0);
	}
		
	protected function setPageNumber($page)
	{
		Phpr::$session->set($this->browserGetName().'_page', $page);
	}

	public function browserGetName()
	{
		return get_class($this->_controller).'_'.Phpr::$router->action.'_browser';
	}


	public function renderUploadform()
	{
		$this->renderPartial('upload_form');
	}
	
	public function filebrowserGetUploadUrl($recordId)
	{
		$model = $this->_controller->form_model_class;

		$url = Backend_Html::controllerUrl();
		$url = substr($url, 0, -1);
		
		
		$ticket = method_exists($this->_controller,'filebrowserGetTicket') ? $this->_controller->filebrowserGetTicket() : Phpr::$security->getTicket();
		
		$parts = array(
			$url,
			'filebrowser_upload',
			$ticket,
			$this->_controller->formGetEditSessionKey()
		);

		if ($recordId)
			$parts[] = $recordId;

		return root_url(implode('/', $parts),true);

	}
	
	public function filebrowser_upload($ticket, $session_key, $recordId = null)
	{
		$this->_controller->suppressView();

		$result = array();
		try
		{
			if (!Phpr::$security->validateTicket($ticket, true))
				throw new Phpr_ApplicationException('Authorization error.');

			if (!array_key_exists('file', $_FILES))
				throw new Phpr_ApplicationException('File was not uploaded.');
			
			$model = $recordId ? $this->_controller->formFindModelObject($recordId) : $this->_controller->formCreateModelObject();
			
			$file = Db_File::create();
			$file->is_public = true;
				
			$file->fromPost($_FILES['file']);
			$file->master_object_class = get_class($model);
			$file->master_object_id = $recordId ? $recordId : null;
			$file->field = null;
			
			if (method_exists($this->_controller,'filebrowserBeforeSaveFile'))
				$this->_controller->filebrowserBeforeSaveFile($file, $recordId, $model, $ticket, $this);
			else
				Backend::$events->fireEvent('filebrowser:onBeforeSaveFile', $file, $recordId, $model, $ticket, $this);
			
			
			$file->save();

			$result['result'] = 'success';
		}
		catch (Exception $ex)
		{
			$result['result'] = 'failed';
			$result['error'] = $ex->getMessage();
		}
		
		header('Content-type: application/json');
		echo json_encode($result);
	}
	
}