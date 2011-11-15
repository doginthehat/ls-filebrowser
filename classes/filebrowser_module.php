<?php
/**
 * Filebrowser module
 */
class Filebrowser_Module extends Core_ModuleBase 
{
	/**
	 * Module information
	 *
	 * @return Core_ModuleInfo
	 */
	protected function createModuleInfo()
	{
		return new Core_ModuleInfo('Filebrowser', 'Filebrowser button behavior for html editors', 'Dog in the hat');
	}


	/**
	 * Subscribe to system events
	 *
	 * @return void
	 */
	public function subscribeEvents()
	{
	}
	

}
