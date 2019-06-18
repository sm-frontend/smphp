<?php
namespace SM\Request;

class CurlRequest
{
	public $url             = null;
	public $method          = 'GET';
	public $data            = null;
	public $headers         = null;
	public $options         = null;
	public $callback        = null;
	public $formContentType = null;
	
	public function __construct($url, $method = 'GET', $data = null, $headers = null, $options = null, $callback = null, $formContentType = null)
	{
		$this->url             = $url;
		$this->method          = $method;
		$this->data            = $data;
		$this->headers         = $headers;
		$this->options         = $options;
		$this->callback        = $callback;
		$this->formContentType = $formContentType;
	}
	
	public function __destruct()
	{
		unset($this->url, $this->method, $this->data, $this->headers, $this->options, $this->callback, $this->formContentType);
	}
}
