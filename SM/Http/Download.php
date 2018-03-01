<?php
namespace SM\Http;

class Download
{
	private $url;
	private $http;
	private $blockSize       = 1048576;
	private $isBreakContinue = false;
	private $breakContinue   = true;
	
	public function __construct($url, $headers = null, $options = null)
	{
		$this->url  = $url;
		$this->http = new \SM\Request\Curl();
		
		if (!empty($headers)) {
			$this->http->headers = $headers;
		}
		
		if (!empty($options)) {
			$this->http->options = $options;
		}
	}
	
	public function setBreakContinue($bc)
	{
		$this->breakContinue = (bool) $bc;
	}
	
	public function setBlockSize($size)
	{
		$this->blockSize = (int) $size;
	}
	
	private function getFileSize()
	{
		$this->http->get($this->url, ['Range: bytes=0-1'], [CURLOPT_NOBODY => 1, CURLOPT_HEADER => 1]);
		
		$headers = Output::parseHeaders($this->http->execute());
		
		if (isset($headers['content-range'])) {
			$this->isBreakContinue = true;
			
			list(, $length) = explode('/', $headers['content-range']);
			return (int) $length;
		}
		
		return false;
	}
	
	public function download($filename)
	{
		if ($this->breakContinue) {
			$fileSize = $this->getFileSize();
		}
		
		if ($this->isBreakContinue) {
			$this->http->options = [CURLOPT_NOBODY => 0, CURLOPT_HEADER => 1];
			
			$fp = fopen($filename, 'a+');
			
			if (false === $fp) {
				throw new \Exception('Open file failed');
			}
			
			$begin = filesize($filename);
			
			if (false === $begin) {
				throw new \Exception('Get file size failed');
			}
			
			while ($begin < $fileSize) {
				$length = min($fileSize - $begin, $this->blockSize);
				
				$this->http->get($this->url, ['Range: bytes=' . $begin . '-' . ($begin + $length)]);
				
				$data    = explode("\r\n\r\n", $this->http->execute(), 2);
				
				$headers = Output::parseHeaders($data[0]);
				$begin  += $headers['content-length'];
				
				fwrite($fp, $data[1]);
			}
		} else {
			$fp = fopen($filename, 'w+');
			
			if (false === $fp) {
				throw new \Exception('Open file failed');
			}
			
			$this->http->get($this->url, null, [CURLOPT_NOBODY => 0, CURLOPT_HEADER => 0]);
			
			fwrite($fp, $this->http->execute());
		}
		fclose($fp);
	}
}
