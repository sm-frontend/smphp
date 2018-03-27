<?php
namespace App\Project;

trait ProjectTrait
{
	public function loadConfig($file, $path = '')
	{
		return include((empty($path) ? PROJECT_PATH : $path) . '/Config/' . $file . '.php');
	}
	
	public function loadFormRules($rule)
	{
		return $this->loadConfig('forms/' . $rule);
	}
	
	public function redirect($url, $delay = 0, $js = false, $nocache = false)
	{
		\SM\Http\Url::redirect($url, $delay, $js, $nocache);
	}
}

