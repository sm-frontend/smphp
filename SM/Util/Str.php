<?php
namespace SM\Util;

class Str
{
	public static function clean($str)
	{
		return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $str);
	}
	
	public static function guid($mix)
	{
		if (is_object($mix)) {
			return spl_object_hash($mix);
		} elseif (is_resource($mix)) {
			$mix = get_resource_type($mix) . strval($mix);
		} else {
			$mix = serialize($mix);
		}
		return md5($mix);
	}
	
	public static function camelCase($str)
	{
		return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', strtolower($str))));
	}
	
	public static function getChars($str)
	{
		preg_match_all('/./su', $str, $m);
		return $m[0];
	}
	
	public static function isUTF8($str)
	{
		return (bool) preg_match('//u', $str);
	}
	
	public static function hasChinese($str)
	{
		return preg_match('/[\x{4e00}-\x{9fa5}]+/u', $str);
	}
	
	public static function highlight($str, $rReplace, $sPrefix = '<em>', $sPostfix = '</em>')
	{
		if (!empty($rReplace)) {
			if (is_array($rReplace)) {
				usort($rReplace, [__CLASS__, 'sortByLen']);
				
				foreach ($rReplace as $v) {
					$str = static::highlight($str, $v, $sPrefix, $sPostfix);
				}
			} else {
				$len1 = strlen($str);
				$len2 = strlen($sPrefix);
				$len3 = strlen($sPostfix);
				$len4 = strlen($rReplace);
				
				if ($len4 <= $len1) {
					$c        = false;
					$sReplace = strtolower($rReplace);
					
					for ($i = 0; $i <= $len1 - $len4; ++ $i) {
						if (substr($str, $i, $len2) == $sPrefix) {
							$i += $len2;
							$c  = true;
						} elseif (substr($str, $i, $len3) == $sPostfix) {
							$i += $len3;
							
							if (substr($str, $i, $len2) != $sPrefix) {
								$c = false;
							} else {
								$c = true;
							}
						}
						
						if ($c) {
							continue;
						}
						
						$substr = substr($str, $i, $len4);
						if (strtolower($substr) == $sReplace) {
							$str   = substr($str, 0, $i) . $sPrefix . $substr . $sPostfix . substr($str, $i + $len4, $len1);
							$len1 += $len2 + $len3;
							$i    += $len4 + $len2 + $len3 - 1;
						}
					}
				}
			}
		}
		return str_replace($sPostfix . $sPrefix, '', $str);
	}
	
	public static function sortByLen($a, $b)
	{
		$a = strlen($a);
		$b = strlen($b);
		
		if ($a == $b) {
			return 0;
		}
		return ($a < $b) ? 1 : -1;
	}
	
	public static function length($str)
	{
		$len = 0;
		for ($i = 0, $j = strlen($str); $i < $j; $i ++) {
			$value = ord($str[$i]);
			
			if ($value > 127) {
				$len ++;
				if ($value >= 192 && $value <= 223) {
					$i ++;
				} elseif ($value >= 224 && $value <= 239) {
					$i = $i + 2;
				} elseif ($value >= 240 && $value <= 247) {
					$i = $i + 3;
				}
			}
			$len ++;
		}
		return $len;
	}
	
	public static function decode($str)
	{
		if (preg_match('~&#(\d{2,5});~', $str)) {
			$str = preg_replace_callback('/&#(\d{2,5});/', function ($m) {
				return mb_convert_encoding("&#{$m[1]};", 'UTF-8', 'HTML-ENTITIES');
			}, $str);
			
			return $str;
		}
		
		if (preg_match('~&#x([0-9a-f]{2,4});~i', $str)) {
			$str = preg_replace_callback('/&#x([0-9a-f]{2,4});/i', function ($m) {
				return '\\u' . str_pad($m[1], 4, 0, STR_PAD_LEFT);
			}, $str);
			
			return static::decodeUnicode($str);
		}
		
		return $str;
	}
	
	public static function decodeUnicode($str)
	{
		return preg_replace_callback('/\\\u([0-9a-f]{4})/i', function ($m) {
			return mb_convert_encoding(pack('H*', $m[1]), 'UTF-8', 'UCS-2BE');
		}, $str);
	}
	
	public static function truncate($str, $limitLen, $ellipse = '...')
	{
		$return   = '';
		$totalLen = 0;
		$len      = mb_strlen($str, 'UTF-8');
		
		for ($i = 0; $i < $len; $i++) {
			$currChar = mb_substr($str, $i, 1, 'UTF-8');
			$currLen  = ord($currChar) > 127 ? 2 : 1;
			
			if ($i != $len - 1) {
				$nextChar = mb_substr($str, $i + 1, 1, 'UTF-8');
				$nextLen  = ord($nextChar) > 127 ? 2 : 1;
			} else {
				$nextLen = 0;
			}
			
			if ($totalLen + $currLen + $nextLen > $limitLen) {
				$return .= $currChar;
				return $return . $ellipse;
			} else {
				$return   .= $currChar;
				$totalLen += $currLen;
			}
		}
		return $return;
	}
	
	public static function random($len = 32)
	{
		if (false !== ($rand = \SM\Security\Random::getBytes(16))) {
			$hash = bin2hex($rand);
		} else {
			$hash = md5(microtime() . uniqid(mt_rand(), true));
		}
		return substr($hash, 0, $len);
	}
	
	public static function jsEscape($str, $quoteType = "'")
	{
		if ($quoteType == "'") {
			$replaced = str_replace(['\\', '\'', "\n", "\r"], ['\\\\', "\\'", "\\n", "\\r"], $str);
		} else {
			$replaced = str_replace(['\\', '"', "\n", "\r"], ['\\\\', "\\\"", "\\n", "\\r"], $str);
		}
		
		$replaced = preg_replace('#(-(?=-))#', "-$quoteType + $quoteType", $replaced);
		$replaced = preg_replace('#</script#i', "<\\/scr$quoteType + {$quoteType}ipt", $replaced);
		
		return $replaced;
	}
	
	public static function htmlEscape($str)
	{
		return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
	}
	
	public static function htmlUnEscape($str)
	{
		return htmlspecialchars_decode($str, ENT_QUOTES);
	}
	
	public static function convert($str, $inEncoding = 'GBK', $targetEncoding = 'UTF-8')
	{
		if (function_exists('mb_convert_encoding') && $encodedData = mb_convert_encoding($str, $targetEncoding, $inEncoding)) {
			return $encodedData;
		} elseif (function_exists('iconv') && $encodedData = iconv($inEncoding, $targetEncoding . '//IGNORE', $str)) {
			return $encodedData;
		} else {
			return $str;
		}
	}
}
