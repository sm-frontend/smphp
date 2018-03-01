<?php
namespace SM\View;

use SM\Util\Str;
use SM\Util\Dir;
use SM\Util\File;

class View
{
	protected $_compileDir    = '';
	protected $_debugMode     = false;
	protected $_vars          = [];
	protected $_mappings      = [];
	protected $_resources     = [];
	protected $_captureStack  = [];
	protected $_templateStack = [];
	protected $_renderIdStack = [];
	protected $_renderId      = '';
	
	public function __construct($compileDir = '', $debugMode = false)
	{
		$this->_compileDir = Dir::normalizePath($compileDir);
		
		if (!is_dir($this->_compileDir)) {
			throw new \Exception('CompileDir "' . $this->_compileDir . '" does not exist.');
		}
		
		if (!is_writeable($this->_compileDir)) {
			throw new \Exception('CompileDir "' . $this->_compileDir . '" is not writeable.');
		}
		
		$this->_debugMode = $debugMode;
		$this->_mappings  = [
			'render'     => 'echo $this->render',
			'include'    => 'include $this->_render',
			'eval'       => 'eval( "?>" . $this->_compile',
			'extend'     => '$this->_addParentTemplate',
			'capture'    => 'ob_start(); $this->_captureStack[] = ',
			'endcapture' => '${array_pop($this->_captureStack)} = ob_get_clean',
			'jsEscape'   => '$this->_jsEscape',
			'htmlEscape' => '$this->_htmlEscape'
		];
	}
	
	public function pass($vars)
	{
		$this->_vars = (array) $vars;
	}
	
	public function render($tpl, $vars = null, $resourceHandler = 'file')
	{
		if (is_null($vars)) {
			$vars = $this->_vars;
		}
		
		$this->_renderId                          = Str::random();
		$this->_renderIdStack[]                   = $this->_renderId;
		$this->_templateStack[$this->_renderId][] = $this->_render($tpl, $resourceHandler);
		
		if (!empty($vars) && is_array($vars)) {
			extract($vars, EXTR_REFS);
		}
		
		ob_start();
		
		while (count($this->_templateStack[$this->_renderId]) > 0) {
			include(array_shift($this->_templateStack[$this->_renderId]));
			
			if (count($this->_templateStack[$this->_renderId]) > 0) {
				ob_clean();
			}
		}
		
		array_pop($this->_renderIdStack);
		$this->_renderId = end($this->_renderIdStack);
		
		return ob_get_clean();
	}
	
	public function addResource($name, ResourceInterface $resource)
	{
		$this->_resources[$name] = $resource;
	}
	
	public function addMappings(array $mappings)
	{
		$this->_mappings = array_merge($this->_mappings, $mappings);
	}
	
	protected function _cleanFilename($filename)
	{
		return preg_replace('=[^a-z0-9_\.\-/]=i', '%', $filename);
	}
	
	protected function _render($tpl, $resourceHandler = 'file')
	{
		if (is_null($resourceHandler) || !isset($this->_resources[$resourceHandler])) {
			reset($this->_resources);
			$resourceHandler = key($this->_resources);
			
			if (!isset($this->_resources[$resourceHandler])) {
				throw new \Exception('ResourceHandler "' . $resourceHandler . '" does not exist.');
			}
		}
		
		$resource    = $this->_resources[$resourceHandler];
		$compiledTpl = $this->_compileDir . '/' . $this->_cleanFilename($resourceHandler) . '/' . $this->_cleanFilename($resource->getTemplateId($tpl));
		
		if ($this->_debugMode || !is_file($compiledTpl) || !filesize($compiledTpl)) {
			$this->_compileTemplate($tpl, $resource, $compiledTpl);
			return $compiledTpl;
		}
		
		$compiledTplTime = File::lastModified($compiledTpl);
		$rawTplMtime     = $resource->getTimestamp($tpl);
		
		if ($compiledTplTime != $rawTplMtime) {
			$this->_compileTemplate($tpl, $resource, $compiledTpl);
			return $compiledTpl;
		}
		
		return $compiledTpl;
	}
	
	protected function _compileTemplate($tpl, $resource, $compiledTpl)
	{
		$source    = $resource->getTemplate($tpl);
		$timestamp = $resource->getTimestamp($tpl);
		
		$compiled  = $this->_compile($source);
		
		if (File::write($compiledTpl, $compiled, 0755)) {
			touch($compiledTpl, $this->_debugMode ? $timestamp - 1 : $timestamp);
			
			if (function_exists('opcache_invalidate')) {
				opcache_invalidate($compiledTpl, true);
			}
		}
	}
	
	protected function _compile($content)
	{
		$content  = preg_replace("=/\*.*?\*/\s*=is", '', $content);
		$content  = str_replace('\~', '<?php echo chr(126) ?>', $content);
		$content  = preg_replace_callback('=(~~?)(.*?)~=s', [$this, '_callbackProcessTildes'], $content);
		$content  = preg_replace('#<\?\s#', '<?php ', $content);
		$content  = str_replace('<?=', '<?php echo ', $content);
		
		$phpBlock = false;
		$tokens   = token_get_all($content);
		$content  = '';
		
		foreach ($tokens as $token) {
			$tokenName = 'UNDEFINED';
			
			if (is_array($token)) {
				$tokenName = token_name($token[0]);
				$token     = $token[1];
			}
			
			if ($tokenName == 'T_COMMENT') {
				continue;
			}
			
			if ($tokenName == 'T_OPEN_TAG') {
				$content   .= '<?php ';
				$phpContent = '';
				$phpBlock   = true;
			} elseif ($tokenName == 'T_CLOSE_TAG') {
				$content .= $this->_callbackProcessPhpBlocks($phpContent) . ' ' . $token;
				$phpBlock = false;
			} elseif (!$phpBlock) {
				$content .= $token;
			} elseif ($phpBlock) {
				$phpContent .= $token;
			}
		}
		
		return $this->_debugMode ? $content : $this->_callbackHtmlMin($content);
	}
	
	protected function _callbackHtmlMin($content)
	{
		$content = preg_replace('#>\s+<#', '><', $content);
		$content = preg_replace('#\s+#', ' ', $content);
		$content = preg_replace('#\s*<!--[^\[<>].*(?<!!)-->\s*#msU', '', $content);
		
		$content = preg_replace_callback('#<style([^>]*?)>(.+)</style>#siU', [$this, '_callbackCssMin'], $content);
		$content = preg_replace_callback('#<script([^>]*?)>(.+)</script>#siU', [$this, '_callbackJsMin'], $content);
		
		return trim($content);
	}
	
	protected function _callbackCssMin($content)
	{
		$content[2] = preg_replace('/\s+/', ' ', $content[2]);
		$content[2] = preg_replace('/\s+([\!\{\}\;\:\>\+\(\)\]\~\=,])/', '$1', $content[2]);
		$content[2] = preg_replace('/([\!\{\}\:\;\>\+\(\[\~\=,])\s+/S', '$1', $content[2]);
		$content[2] = preg_replace('/;;+/', ';', $content[2]);
		$content[2] = preg_replace('/;+\}/', '}', $content[2]);
		$content[2] = preg_replace('/[^\};\{\/]+\{\}/S', '', $content[2]);
		
		return '<style' . $content[1] . '>' . trim($content[2]) . '</style>';
	}
	
	protected function _callbackJsMin($content)
	{
		return '<script' . $content[1] . '>' . trim($content[2]) . '</script>';
	}
	
	protected function _callbackProcessPhpBlocks($content)
	{
		$content = preg_replace_callback('=(\$[a-z0-9_]+)((\.[a-z0-9_]+)+)=i', [$this, '_callbackExpandArraySyntax'], $content);
		
		while ($this->_expandFunctionSyntax($content)) {
			continue;
		}
		
		return $content;
	}
	
	protected function _callbackProcessTildes($content)
	{
		$return = '<?php ';
		
		if ($content[1] == '~~') {
			$return .= 'echo ';
		}
		
		$return .= $content[2] . '?>';
		
		return $return;
	}
	
	protected function _expandFunctionSyntax(&$content)
	{
		if (!preg_match('=(?<!:):([a-z0-9_]+)\s*(\()=i', $content, $match, PREG_OFFSET_CAPTURE)) {
			return false;
		}
		
		$mappingName = $match[1][0];
		
		if (isset($this->_mappings[$mappingName])) {
			$mapping = $this->_mappings[$mappingName];
		} else {
			throw new \Exception('Mapping for function call "' . $mappingName . '" does not exist.');
		}
		
		$i     = $match[2][1];
		$count = null;
		
		while ($count !== 0 && isset($content{$i})) {
			if ($content{$i} == '(') {
				$count ++;
			}
			
			if ($content{$i} == ')') {
				$count --;
			}
			$i ++;
		}
		
		$start       = substr($content, 0, $match[0][1]);
		$parameters  = substr($content, $match[2][1], $i - $match[2][1]);
		$end         = substr($content, $i);
		
		$paranthesis = str_repeat(')', substr_count($mapping, '(') - substr_count($mapping, ')'));
		$content     = $start . $mapping . $parameters . $paranthesis . $end;
		
		return true;
	}
	
	protected function _callbackExpandArraySyntax($matches)
	{
		$parts = explode('.', $matches[2]);
		array_shift($parts);
		
		$return  = $matches[1];
		$return .= "['" . implode("']['", $parts) . "']";
		
		return $return;
	}
	
	protected function _addParentTemplate($tpl)
	{
		$this->_templateStack[$this->_renderId][] = $this->_render($tpl);
	}
	
	protected function _jsEscape($str, $quotetype = "'")
	{
		return Str::jsEscape($str, $quotetype);
	}
	
	protected function _htmlEscape($str)
	{
		return Str::htmlEscape($str);
	}
}
