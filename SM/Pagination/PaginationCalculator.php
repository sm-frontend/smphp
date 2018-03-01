<?php
namespace SM\Pagination;

class PaginationCalculator
{
	private $totalItems;
	private $opts;
	
	public function __construct($totalItems, $opts)
	{
		$this->totalItems = $totalItems;
		$this->opts       = $opts;
	}
	
	public function numPages()
	{
		return ceil($this->totalItems / $this->opts['perPage']);
	}
	
	public function getInterval($currentPage)
	{
		$half = floor($this->opts['displayNum'] / 2);
		$np   = $this->numPages();
		
		if ($currentPage > $half) {
			$start = max(min($currentPage - $half, $np - $this->opts['displayNum']), 0);
			$end   = min($currentPage + $half + ($this->opts['displayNum'] % 2), $np);
		} else {
			$start = 0;
			$end   = min($this->opts['displayNum'], $np);
		}
		
		return ['start' => $start, 'end' => $end];
	}
}
