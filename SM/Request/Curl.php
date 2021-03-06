<?php
namespace SM\Request;

class Curl
{
	private $window      = 5;
	private $callback    = null;
	private $timeout     = 0.5;
	private $headers     = ['Expect:'];
	private $requests    = [];
	private $requestMap  = [];
	private $requestSize = 0;
	private $responses   = [];
	
	protected $options   = [
		CURLOPT_SSL_VERIFYPEER => 0,
		CURLOPT_SSL_VERIFYHOST => 0,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_NOSIGNAL       => 1,
		CURLOPT_FOLLOWLOCATION => 1,
		CURLOPT_MAXREDIRS      => 5,
		CURLOPT_HEADER         => 0
	];
	
	const HTTP_METHOD_GET    = 'GET';
	const HTTP_METHOD_POST   = 'POST';
	const HTTP_METHOD_HEAD   = 'HEAD';
	const HTTP_METHOD_PUT    = 'PUT';
	const HTTP_METHOD_PATCH  = 'PATCH';
	const HTTP_METHOD_DELETE = 'DELETE';
	
	const DEFAULT_CONNECTTIMEOUT = 1;
	const DEFAULT_TIMEOUT        = 3;
	
	const HTTP_FORM_CONTENT_TYPE_APPLICATION = 0;
	const HTTP_FORM_CONTENT_TYPE_MULTIPART   = 1;
	
	public function __construct($callback = null)
	{
		$this->callback = $callback;
	}
	
	public function __get($name)
	{
		return (isset($this->{$name})) ? $this->{$name} : [];
	}
	
	public function __set($name, $value)
	{
		if ($name == 'options' || $name == 'headers') {
			$this->{$name} = $value + $this->{$name};
		} else {
			$this->{$name} = $value;
		}
		return true;
	}
	
	public function setOption($key, $value)
	{
		$this->options[$key] = $value;
	}
	
	public function add($request)
	{
		$this->requests[] = $request;
		return $this;
	}
	
	public function request($url, $method = self::HTTP_METHOD_GET, $data = null, $headers = null, $options = null, $callback = null, $formContentType = self::HTTP_FORM_CONTENT_TYPE_APPLICATION)
	{
		$this->requests[] = new CurlRequest($url, $method, $data, $headers, $options, $callback, $formContentType);
		return $this;
	}
	
	public function get($url, $headers = null, $options = null, $callback = null)
	{
		return $this->request($url, static::HTTP_METHOD_GET, null, $headers, $options, $callback);
	}
	
	public function head($url, $headers = null, $options = null, $callback = null)
	{
		return $this->request($url, static::HTTP_METHOD_HEAD, null, $headers, $options, $callback);
	}
	
	public function delete($url, $headers = null, $options = null, $callback = null)
	{
		return $this->request($url, static::HTTP_METHOD_DELETE, null, $headers, $options, $callback);
	}
	
	public function post($url, $data = null, $headers = null, $options = null, $callback = null, $formContentType = self::HTTP_FORM_CONTENT_TYPE_APPLICATION)
	{
		return $this->request($url, static::HTTP_METHOD_POST, $data, $headers, $options, $callback, $formContentType);
	}
	
	public function put($url, $data = null, $headers = null, $options = null, $callback = null)
	{
		return $this->request($url, static::HTTP_METHOD_PUT, $data, $headers, $options, $callback);
	}
	
	public function patch($url, $data = null, $headers = null, $options = null, $callback = null)
	{
		return $this->request($url, static::HTTP_METHOD_PATCH, $data, $headers, $options, $callback);
	}
	
	public function execute($window = 0)
	{
		$this->requestSize = count($this->requests);

		if ($this->requestSize == 1) {
			return $this->singleCurl();
		} elseif ($this->requestSize > 1) {
			return $this->multiCurl($window);
		}
	}
	
	private function createFile($filename, $mimetype = null, $postname = null)
	{
		if (class_exists('\CURLFile')) {
			return new \CURLFile($filename, $mimetype, $postname);
		}
		
		$file  = '@' . $filename;
		$file .= ';filename=' . ($postname ?: basename($filename));
		
		if (!empty($mimetype)) {
			$file .= ';type=' . $mimetype;
		}
		
		return $file;
	}
	
	private function singleCurl()
	{
		$request = array_shift($this->requests);
		$options = $this->getOptions($request);
		
		$ch = curl_init();
		curl_setopt_array($ch, $options);
		$output = curl_exec($ch);
		
		$info   = curl_getinfo($ch);
		$errno  = curl_errno($ch);
		$this->logError($errno, $ch, $info, $request);
		
		curl_close($ch);
		
		$callback = $request->callback ?: $this->callback;
		
		if ($callback && is_callable($callback)) {
			return call_user_func($callback, $output, $info, $request);
		} else {
			return $output;
		}
	}
	
	private function multiCurl($window = 0)
	{
		$master = curl_multi_init();
		
		if ($window > 0) {
			$this->window = $window;
		}
		
		if ($this->requestSize < $this->window) {
			$this->window = $this->requestSize;
		}
		
		for ($i = 0; $i < $this->window; $i++) {
			$this->addRequestMap($master, $i);
		}
		
		$this->responses = [];
		
		do {
			$running = 0;
			
			do {
				$execrun = curl_multi_exec($master, $running);
			} while ($execrun == CURLM_CALL_MULTI_PERFORM);
			
			if ($execrun != CURLM_OK) {
				break;
			}
			
			$this->processResponse($master, $i);
			
			if ($running) {
				curl_multi_select($master, $this->timeout);
			}
		} while ($running);
		
		curl_multi_close($master);
		$this->requests = [];

		return $this->responses;
	}
	
	private function addRequestMap($master, $i)
	{
		$options = $this->getOptions($this->requests[$i]);
		
		$ch = curl_init();
		curl_setopt_array($ch, $options);
		curl_multi_add_handle($master, $ch);
		
		$this->requestMap[(string) $ch] = $i;
	}
	
	private function processResponse($master, &$i)
	{
		while ($done = curl_multi_info_read($master)) {
			$ri      = $this->requestMap[(string) $done['handle']];
			$request = $this->requests[$ri];
			
			$info    = curl_getinfo($done['handle']);
			$output  = curl_multi_getcontent($done['handle']);

			$this->logError($done['result'], $done['handle'], $info, $request);
			
			$callback = $request->callback ?: $this->callback;
			
			if ($callback && is_callable($callback)) {
				call_user_func($callback, $output, $info, $request);
			} else {
				$this->responses[$ri] = $output;
			}
			
			if ($i < $this->requestSize && isset($this->requests[$i])) {
				$this->addRequestMap($master, $i);
				$i++;
			}
			
			curl_multi_remove_handle($master, $done['handle']);
			curl_close($done['handle']);
		}
	}
	
	private function getOptions($request)
	{
		$options = $this->__get('options');
		
		$options[CURLOPT_URL] = $request->url;
		
		if ($request->options) {
			$options = $request->options + $options;
		}
		
		switch ($request->method) {
			case static::HTTP_METHOD_POST:
				$this->parsePostdata($request);
				
				$options[CURLOPT_POST]       = true;
				$options[CURLOPT_POSTFIELDS] = $request->data;
				break;
				
			case static::HTTP_METHOD_HEAD:
				$options[CURLOPT_NOBODY]        = true;
				$options[CURLOPT_HEADER]        = true;
				$options[CURLOPT_CUSTOMREQUEST] = $request->method;
				break;
				
			case static::HTTP_METHOD_PUT:
			case static::HTTP_METHOD_PATCH:
			case static::HTTP_METHOD_DELETE:
				$options[CURLOPT_CUSTOMREQUEST] = $request->method;
				if (!empty($request->data)) {
					$options[CURLOPT_POSTFIELDS] = $request->data;
				}
				break;
		}
		
		$headers = $this->__get('headers');
		
		if ($request->headers) {
			$headers = array_merge($headers, $request->headers);
		}
		
		if (!empty($headers)) {
			$options[CURLOPT_HTTPHEADER] = $headers;
		}
		
		if (!isset($options[CURLOPT_CONNECTTIMEOUT]) && !isset($options[CURLOPT_CONNECTTIMEOUT_MS])) {
			$options[CURLOPT_CONNECTTIMEOUT] = static::DEFAULT_CONNECTTIMEOUT;
		}
		
		if (!isset($options[CURLOPT_TIMEOUT]) && !isset($options[CURLOPT_TIMEOUT_MS])) {
			$options[CURLOPT_TIMEOUT] = static::DEFAULT_TIMEOUT;
		}
		
		return $options;
	}
	
	private function parsePostdata($request)
	{
		if (is_array($request->data)) {
			foreach ($request->data as $key => $value) {
				if (is_string($value) && strpos($value, '@') === 0) {
					$request->data[$key]      = $this->createFile(ltrim($value, '@'));
					$request->formContentType = static::HTTP_FORM_CONTENT_TYPE_MULTIPART;
				}
			}
			
			if ($request->formContentType === static::HTTP_FORM_CONTENT_TYPE_APPLICATION) {
				$request->data = \SM\Http\Url::buildQuery($request->data);
			}
		}
	}
	
	private function logError($errno, $ch, $info, $request)
	{
		if ($errno) {
			$error = curl_error($ch);
			\SM\Error\ErrorTrigger::notice('[PHP cURL error] ' . $errno . ':' . $error . ':' . serialize($info) . ':' . $request->url, __FILE__, __LINE__);
		}
	}
	
	public function __destruct()
	{
		unset($this->callback, $this->options, $this->headers, $this->requests, $this->responses);
	}
}
