<?php
namespace SM\Http;

class Output
{
	public static function header($data)
	{
		if (!headers_sent()) {
			header($data);
		}
	}
	
	public static function nocacheHeaders()
	{
		if (!headers_sent()) {
			header('Expires: 0');
			header('Cache-Control: private, post-check=0, pre-check=0, max-age=0', false);
			header('Pragma: no-cache');
		}
	}
	
	public static function contentType($type, $charset = 'utf-8')
	{
		if (!headers_sent()) {
			header('Content-type: ' . $type . ($charset ? '; charset=' . $charset : ''));
		}
	}
	
	public static function parseHeaders($data)
	{
		$headers = [];
		
		foreach (explode("\r\n", $data) as $line) {
			if (false !== strpos($line, ':')) {
				list($header, $value) = explode(': ', $line, 2);
			} else {
				$header = $line;
			}
			
			if (preg_match('%^http/(\d(?:\.\d)?)\s+(\d{3})\s?+(.+)?$%i', $header, $match)) {
				$headers['http-response']['version']    = $match[1];
				$headers['http-response']['statuscode'] = $match[2];
				$headers['http-response']['statustext'] = $match[3];
				
			} elseif (!empty($header)) {
				$header = strtolower($header);
				$value  = trim($value);
				
				if (isset($headers[$header])) {
					if (is_array($headers[$header])) {
						$headers[$header] = array_merge($headers[$header], [$value]);
					} else {
						$headers[$header] = array_merge([$headers[$header]], [$value]);
					}
				} else {
					$headers[$header] = $value;
				}
			}
		}
		return $headers;
	}
}
