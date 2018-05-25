<?php
namespace SM\Validator;

class MethodCollection extends \ArrayObject
{
	const JQUERY_SELECTOR = '/^([a-z]*[.#])?([a-z0-9-_]+)(\[[a-z-]+="?([a-z0-9-_]+)"?\])?(:([a-z]+))?$/i';
	const VALID_TEL       = '/^(([0\+]\d{2,3}-)?(0\d{2,3})-)?(\d{7,8})(-(\d{3,}))?$/';
	const VALID_DATE      = '/^\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])$/';
	const VALID_MOBILE    = '/^1[3456789]\d{9}$/';
	const VALID_DIGITS    = '/^\d+$/';
	const VALID_CHINESE   = '/^[\x{4e00}-\x{9fa5}]+$/u';
	const VALID_USERNAME  = '/^[\x{4e00}-\x{9fa5}\w\_]+$/u';
	
	private function parseJQuery($selector)
	{
		preg_match_all(static::JQUERY_SELECTOR, $selector, $match);
		
		$return = new \stdClass;
		$return->fieldName = !empty($match[4][0]) ? $match[4][0] : $match[2][0];
		$return->filter    = $match[6][0];
		
		return $return;
	}
	
	public function __call($name, array $param)
	{
		if (isset($this[$name]) && is_callable($this[$name])) {
			return call_user_func_array($this[$name], $param);
		}
		return true;
	}
	
	public function regxp($value, $regex)
	{
		return preg_match($regex, $value) == 1;
	}
	
	public function digits($value)
	{
		return $this->regxp($value, static::VALID_DIGITS);
	}
	
	public function chinese($value)
	{
		return $this->regxp($value, static::VALID_CHINESE);
	}
	
	public function username($value)
	{
		return $this->regxp($value, static::VALID_USERNAME);
	}
	
	public function tel($value)
	{
		return $this->regxp($value, static::VALID_TEL);
	}
	
	public function date($value)
	{
		return $this->regxp($value, static::VALID_DATE);
	}
	
	public function mobile($value)
	{
		return $this->regxp($value, static::VALID_MOBILE);
	}
	
	public function email($value)
	{
		return !!filter_var($value, FILTER_VALIDATE_EMAIL);
	}
	
	public function url($value)
	{
		return !!filter_var($value, FILTER_VALIDATE_URL);
	}
	
	public function ip($value)
	{
		return !!filter_var($value, FILTER_VALIDATE_IP);
	}
	
	public function idcard($value)
	{
		$idc = new \SM\Util\Idcard;
		$idc->parseIdc($value);
		
		return !!$idc->info[IDC_INDEX_CAT];
	}
	
	public function image($value)
	{
		$mime = \SM\Util\File::mime($value);
		return is_string($mime) && 0 === strpos($mime, 'image/');
	}
	
	public function base64($value)
	{
		return is_string($value) && base64_encode(base64_decode($value)) === $value;
	}
	
	public function arr($value)
	{
		return is_array($value);
	}
	
	public function string($value)
	{
		return is_string($value);
	}
	
	public function boolean($value)
	{
		return in_array($value, [true, false, 0, 1, '0', '1'], true);
	}
	
	public function enum($value, array $enum)
	{
		return in_array($value, $enum);
	}
	
	public function number($value)
	{
		return is_numeric($value);
	}
	
	public function max($value, $max)
	{
		return is_numeric($value) && $value <= $max;
	}
	
	public function min($value, $min)
	{
		return is_numeric($value) && $value >= $min;
	}
	
	public function range($value, array $range)
	{
		$value = intval($value);
		return $value >= $range[0] && $value <= $range[1];
	}
	
	public function maxlength($value, $length)
	{
		return $this->getLength($value) <= $length;
	}
	
	public function minlength($value, $length)
	{
		return $this->getLength($value) >= $length;
	}
	
	public function rangeLength($value, array $range)
	{
		$length = $this->getLength($value);
		return $length >= $range[0] && $length <= $range[1];
	}
	
	private function getLength($value)
	{
		if (is_array($value)) {
			$length = count($value);
		} else {
			$length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
		}
		return $length;
	}
	
	public function creditCard($value)
	{
		$total  = 0;
		$length = strlen($value);
		$parity = $length % 2;
		
		for ($i = $length - 1; $i >= 0; $i--) {
			$digit = (int) $value{$i};
			
			if ($parity == ($i % 2)) {
				$digit <<= 1;
				
				if ($digit > 9) {
					$digit -= 9;
				}
			}
			$total += $digit;
		}
		return ($total % 10) == 0;
	}
	
	public function equalTo($value, $equalTo, $fullList)
	{
		$equalTo = $this->parseJQuery($equalTo)->fieldName;
		return isset($fullList[$equalTo]) && strcmp($value, $fullList[$equalTo]) === 0;
	}
	
	public function required($value, $param = null, $fullList = [])
	{
		if (!is_null($param) && !$this->requiredCondinally($value, $param, $fullList)) {
			return true;
		}
		
		$value = is_string($value) ? trim($value) : $value;
		return !empty($value) || $value === '0';
	}
	
	public function requiredCondinally($value, $param = null, $fullList = [])
	{
		$required = true;
		
		if (!is_null($param)) {
			if (is_callable($param)) {
				$required = $param($value, $fullList);
			} elseif (is_string($param)) {
				$jQuery = $this->parseJQuery($param);
				
				if (!isset($fullList[$jQuery->fieldName]) || empty($fullList[$jQuery->fieldName])) {
					return false;
				}
			}
		}
		return $required;
	}
}
