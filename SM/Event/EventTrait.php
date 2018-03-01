<?php
namespace SM\Event;

trait EventTrait
{
	private $events = [];
	
	public function register($event, $callback, $priority = 0, $once = false)
	{
		if (!is_callable($callback)) {
			return false;
		}
		
		if (!isset($this->events[$event])) {
			$this->events[$event] = [];
		}
		
		$this->events[$event][] = compact('callback', 'priority', 'once');
	}
	
	public function on($event, $callback, $priority = 0)
	{
		$this->register($event, $callback, $priority);
	}
	
	public function once($event, $callback, $priority = 0)
	{
		$this->register($event, $callback, $priority, true);
	}
	
	public function off($event, $index = null)
	{
		if (is_null($index)) {
			unset($this->events[$event]);
		} else {
			unset($this->events[$event][$index]);
		}
	}
	
	public function trigger($event, $params = null)
	{
		$params = func_get_args();
		array_shift($params);
		
		$result = [];
		if (isset($this->events[$event])) {
			$events =& $this->events[$event];
			$events = \SM\Util\Arr::sortByCol($events, 'priority', SORT_DESC);
			
			foreach ($events as $index => $item) {
				if (true === $item['once']) {
					$this->off($event, $index);
				}
				
				$result[$index] = call_user_func_array($item['callback'], $params);
				
				if (false === $result[$index]) {
					break;
				}
			}
		}
		return $result;
	}
}
