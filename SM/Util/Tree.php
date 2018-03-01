<?php
namespace SM\Util;

class Tree
{
	private $level   = 0;
	private $data    = [];
	private $options = [
		'id'   => 'id',
		'pid'  => 'pid',
		'name' => 'name'
	];
	
	public function __construct(array $data, array $options = [])
	{
		if (!empty($options)) {
			$this->options = array_merge($this->options, $options);
		}
		
		$this->data = Arr::toHashmap($data, $this->options['id']);
	}
	
	public function getTreeNoFormat()
	{
		return $this->build($this->data);
	}
	
	public function getTree($selectedId = 0, $prefix = '|--')
	{
		return $this->make($this->getTreeNoFormat(), $selectedId, $prefix);
	}
	
	public function getChild($pid)
	{
		$child = [];
		foreach ($this->data as $row) {
			if ($row[$this->options['pid']] == $pid) {
				$child[] = $row;
			}
		}
		
		return $child;
	}
	
	public function getParent($cid)
	{
		if (isset($this->data[$cid])) {
			$pid = $this->data[$cid][$this->options['pid']];
			return isset($this->data[$pid]) ? $this->data[$pid] : null;
		}
	}
	
	private function make($data, $selectedId, $prefix)
	{
		$treeOption = '';
		
		$namePrefix = str_repeat($prefix, ++$this->level);
		$selectedId = is_array($selectedId) ? $selectedId : [$selectedId];
		
		foreach ($data as $row) {
			$id          = $row[$this->options['id']];
			$name        = $namePrefix . $row[$this->options['name']];
			$selected    = in_array($id, $selectedId) ? ' selected="selected"' : '';
			
			$treeOption .= '<option value="' . $id . '"' . $selected . '>' . $name . '</option>';
			
			if (isset($row['son'])) {
				$treeOption .= $this->make($row['son'], $selectedId, $prefix);
			}
		}
		
		$this->level--;
		
		return $treeOption;
	}
	
	private function build(array $data)
	{
		foreach ($data as $row) {
			$id  = $row[$this->options['id']];
			$pid = $row[$this->options['pid']];
			
			$data[$pid]['son'][$id] = &$data[$id];
		}
		
		return isset($data[0]['son']) ? $data[0]['son'] : [];
	}
}
