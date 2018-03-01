<?php
namespace SM\Validator;

class Validator
{
	protected $value          = [];
	protected $message        = [];
	protected $classRule      = [];
	protected $errorMessage   = [];
	
	protected $invalidHandler;
	protected $methodCollection;
	
	public function __construct(array $option = null)
	{
		if (!is_null($option)) {
			if (isset($option['rules']) && is_array($option['rules'])) {
				foreach ($option['rules'] as $field => $rule) {
					$this->addClassRules($rule, $field);
				}
			}
			
			if (isset($option['messages']) && is_array($option['messages'])) {
				$this->extendMessages($option['messages']);
			}
			
			if (isset($option['invalidHandler'])) {
				if (!is_callable($option['invalidHandler'])) {
					throw new \Exception('Invalid option set for "invalidHandler: not callable"');
				}
				
				$this->invalidHandler = $option['invalidHandler'];
			}
		}
		
		$this->methodCollection = new MethodCollection(new \ArrayIterator);
	}
	
	public function addMethod($name, $functionHandle, $message = null)
	{
		$this->methodCollection[$name] = $functionHandle;
		$this->message[$name]          = $message;
	}
	
	public function addClassRules($rule, $field)
	{
		if (!is_array($rule)) {
			$rule = [$rule => []];
		}
		
		if (!isset($this->classRule[$field])) {
			$this->classRule[$field] = [];
		}
		
		$this->classRule[$field] = $rule + $this->classRule[$field];
	}
	
	public function extendMessages(array $message)
	{
		$this->message = $message + $this->message;
	}
	
	public function getMessage($field, $rule, $ruleParam)
	{
		$message = '';
		
		if (isset($this->message[$field])) {
			if (is_array($this->message[$field]) && isset($this->message[$field][$rule])) {
				$message = $this->message[$field][$rule];
			} else {
				$message = $this->message[$field];
			}
		} elseif (isset($this->message[$rule])) {
			$message = $this->message[$rule];
		}
		
		return preg_replace_callback('/{(\d+)}/', function ($match) use ($ruleParam) {
			return $ruleParam[$match[1]];
		}, $message);
	}
	
	public function element($field, $value)
	{
		if (isset($this->classRule[$field])) {
			foreach ($this->classRule[$field] as $rule => $ruleParam) {
				if ((!empty($value) || !$this->optional($field)) && !$this->methodCollection->{$rule}($value, $ruleParam, $this->value)) {
					$this->errorMessage[$field] = $this->getMessage($field, $rule, $ruleParam);
					return false;
				}
			}
		}
		return true;
	}
	
	public function optional($field)
	{
		if (isset($this->classRule[$field]) && isset($this->classRule[$field]['required']) && isset($this->value[$field])) {
			return !$this->methodCollection->requiredCondinally($this->value[$field], $this->classRule[$field]['required'], $this->value);
		}
		return false;
	}
	
	public function validate(array $value)
	{
		$this->value        = $value;
		$this->errorMessage = [];
		
		foreach ($value as $field => $v) {
			$this->element($field, $v);
		}
		
		foreach ($this->classRule as $field => $rules) {
			if (!isset($value[$field]) && isset($rules['required'])) {
				$this->element($field, '');
			}
		}
		
		if (is_callable($this->invalidHandler)) {
			$func = $this->invalidHandler;
			$func($value, $this);
		}
		
		return $this->errorMessage;
	}
	
	public function numberOfInvalids()
	{
		return count($this->errorMessage);
	}
	
	public static function __callStatic($name, array $param)
	{
		static $methodCollection = null;
		
		if (!$methodCollection) {
			$methodCollection = new MethodCollection();
		}
		
		return call_user_func_array([$methodCollection, $name], $param);
	}
}
