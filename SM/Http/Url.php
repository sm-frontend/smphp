<?php
namespace SM\Http;

use SM\Util\Str;
use SM\Transform\Transform;

class Url
{
	public static function current()
	{
		$protocol = (Input::isSSL() ? 'https' : 'http') . '://';
		return $protocol . Input::fetchHost() . Input::server('REQUEST_URI');
	}
	
	public static function append($url, $param = null)
	{
		$url = rtrim($url, '?#&');
		
		if (!empty($param)) {
			is_array($param) || parse_str($param, $param);
			
			$parseUrl = parse_url($url);
			
			if (isset($parseUrl['query'])) {
				parse_str($parseUrl['query'], $output);
				$intersect = array_intersect_key($output, $param);
				
				if (!empty($intersect)) {
					foreach ($intersect as $k => $v) {
						$url = str_replace("$k=$v", "$k=" . urlencode($param[$k]), $url);
						unset($param[$k]);
					}
				}
			}
			
			if (!empty($param)) {
				if (isset($parseUrl['fragment'])) {
					$url = substr($url, 0, -(strlen($parseUrl['fragment']) + 1));
				}
				
				$url .= isset($parseUrl['query']) ? '&' : '?';
				$url .= static::buildQuery($param);
				$url .= isset($parseUrl['fragment']) ? '#' . $parseUrl['fragment'] : '';
			}
		}
		
		return $url;
	}
	
	public static function normalize($url, $schema = true)
	{
		$url = trim($url);
		
		if (!$url) {
			return '';
		}
		
		if ($schema && !preg_match('#^(https?|ext):#i', $url)) {
			$url = 'http://' . $url;
		}
		
		$url = str_replace('&amp;amp;', '&amp;', $url);
		$url = str_replace('&amp;', '&', $url);
		$url = str_replace(array("\n", "\r"), '', $url);
		
		return $url;
	}
	
	public static function sign($url, $secret = '_-_uRlSiGn_-_', $algo = 'md5', $rawOutput = false)
	{
		return hash_hmac($algo, static::normalize($url, false), $secret, $rawOutput);
	}
	
	public static function base64UrlEncode($url)
	{
		return Transform::base64Encode($url, 'url');
	}
	
	public static function base64UrlDecode($url)
	{
		return Transform::base64Decode($url, 'url');
	}
	
	public static function getMainDomain($domain)
	{
		$len   = preg_match('/\.(com|co|org|net|gov)\.(cn|uk|jp|kr|hk|tw|us|ca|de)$/', $domain) ? 3 : 2;
		$parts = explode('.', $domain);
		
		return implode('.', array_slice($parts, max(count($parts) - $len, 0)));
	}
	
	public static function getHost($url)
	{
		return parse_url($url, PHP_URL_HOST);
	}
	
	public static function getQuery($url, $key = null)
	{
		if ($query = parse_url($url, PHP_URL_QUERY)) {
			if ($key === null) {
				return $query;
			}
			
			parse_str($query, $output);
		
			if (isset($output[$key])) {
				return trim($output[$key]);
			}
		}
	}
	
	public static function buildQuery($param)
	{
		return http_build_query($param, null, '&');
	}
	
	public static function redirect($url, $delay = 0, $js = false, $nocache = false)
	{
		if ($nocache) {
			Output::nocacheHeaders();
		}
		
		$url   = static::normalize($url, false);
		$delay = (int) $delay;
		
		if (!$js) {
			if (headers_sent()) {
				echo '<html><head><meta http-equiv="refresh" content="' . $delay . ';URL=' . $url . '" /></head></html>';
			} else {
				if ($delay > 0) {
					header('Refresh:' . $delay . ';url=' . $url);
				} else {
					header('Location: ' . $url, 0, 302);
				}
			}
		} else {
			$url = Str::jsEscape($url);
			$out = '<script>';
			
			if ($delay > 0) {
				$out .= "window.setTimeout(function(){window.location.href='" . $url . "';}," . $delay . ");";
			} else {
				$out .= "window.location.href='" . $url . "';";
			}
			$out .= '</script>';
			echo $out;
		}
		exit;
	}
}
