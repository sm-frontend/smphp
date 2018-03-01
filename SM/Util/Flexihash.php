<?php
namespace SM\Util;

use SM\Util\Flexihash\Hasher;
use SM\Util\Flexihash\Crc32Hasher;

class Flexihash
{
	private $_replicas               = 64;
	private $_hasher                 = null;
	private $_targetCount            = 0;
	private $_positionToTarget       = [];
	private $_targetToPositions      = [];
	private $_positionToTargetSorted = false;
	
	public function __construct(Hasher $hasher = null, $replicas = null)
	{
		$this->_hasher = $hasher ? $hasher : new Crc32Hasher();
		
		if (!empty($replicas)) {
			$this->_replicas = $replicas;
		}
	}
	
	public function addTarget($target, $weight = 1)
	{
		if (isset($this->_targetToPositions[$target])) {
			throw new \Exception("Target '$target' already exists.");
		}
		
		$this->_targetToPositions[$target] = [];
		
		for ($i = 0, $j = round($this->_replicas * $weight); $i < $j; $i++) {
			$position                            = $this->_hasher->hash($target . $i);
			$this->_positionToTarget[$position]  = $target;
			$this->_targetToPositions[$target][] = $position;
		}
		
		$this->_positionToTargetSorted = false;
		$this->_targetCount++;
		
		return $this;
	}
	
	public function addTargets($targets, $weight = 1)
	{
		foreach ($targets as $target) {
			$this->addTarget($target, $weight);
		}
		
		return $this;
	}
	
	public function removeTarget($target)
	{
		if (!isset($this->_targetToPositions[$target])) {
			throw new \Exception("Target '$target' does not exist.");
		}
		
		foreach ($this->_targetToPositions[$target] as $position) {
			unset($this->_positionToTarget[$position]);
		}
		
		unset($this->_targetToPositions[$target]);
		
		$this->_targetCount--;
		
		return $this;
	}
	
	public function getAllTargets()
	{
		return array_keys($this->_targetToPositions);
	}
	
	public function lookup($resource)
	{
		$targets = $this->lookupList($resource, 1);
		
		if (empty($targets)) {
			throw new \Exception('No targets exist');
		}
		return $targets[0];
	}
	
	public function lookupList($resource, $requestedCount)
	{
		if (!$requestedCount) {
			throw new \Exception('Invalid count requested');
		}
		
		if (empty($this->_positionToTarget)) {
			return [];
		}
		
		if ($this->_targetCount == 1) {
			return $this->getAllTargets();
		}
		
		$resourcePosition = $this->_hasher->hash($resource);
		
		$num     = 0;
		$results = [];
		$collect = false;
		
		$this->_sortPositionTargets();
		
		foreach ($this->_positionToTarget as $key => $value) {
			if (!$collect && $key > $resourcePosition) {
				$collect = true;
			}
			
			if ($collect && !in_array($value, $results)) {
				$results[] = $value;
				$num++;
			}
			
			if ($num == $requestedCount || $num == $this->_targetCount) {
				return $results;
			}
		}
		
		foreach ($this->getAllTargets() as $value) {
			if (!in_array($value, $results)) {
				$results[] = $value;
				$num++;
			}
			
			if ($num == $requestedCount || $num == $this->_targetCount) {
				return $results;
			}
		}
		return $results;
	}
	
	private function _sortPositionTargets()
	{
		if (!$this->_positionToTargetSorted) {
			ksort($this->_positionToTarget, SORT_REGULAR);
			$this->_positionToTargetSorted = true;
		}
	}
}
