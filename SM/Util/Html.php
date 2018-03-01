<?php
namespace SM\Util;

use SM\Http\Input;

class Html
{
	public static function constructSelectOptions(array $array, $selectedId = '', $css = false, $default = false, $xssClean = true)
	{
		$html = '';
		if (is_array($array)) {
			$xssClean && $array = Input::xssClean($array);
			
			$default === true && $html .= '<option value="">请选择</option>';
			
			foreach ($array as $key => $val) {
				$xssClean && $key = Input::xssClean($key);
				
				if (is_array($val)) {
					$html .= sprintf('<optgroup label="%s">%s</optgroup>', $key, static::constructSelectOptions($val, $selectedId, $css, $default, $xssClean));
				} else {
					$selected = ((is_array($selectedId) && in_array($key, $selectedId)) || ($selectedId !== '' && $key == $selectedId)) ? true : false;
					$html .= sprintf('<option %s value="%s"%s>%s</option>', $css ? sprintf('class="%s"', $css) : '', $key, $selected ? ' selected="selected"' : '', $val);
				}
			}
		}
		
		return $html;
	}
}
