<?php
namespace SM\Pagination;

class PaginationRender
{
	private $totalItems;
	private $totalPage;
	private $opts;
	private $pc;
	private $pages = [];
	
	public function __construct($totalItems, $opts)
	{
		$this->totalItems = $totalItems;
		$this->opts       = $opts;
		
		$this->pc         = new PaginationCalculator($totalItems, $opts);
		$this->totalPage  = $this->pc->numPages();
	}
	
	public function getPagination($currentPage)
	{
		if ($this->totalItems > $this->opts['perPage']) {
			$this->getPrevButton($currentPage);
			$this->getRangePages($currentPage);
			$this->getNextButton($currentPage);
		}
		
		return $this;
	}
	
	public function getPages()
	{
		return $this->pages;
	}
	
	private function getPrevButton($currentPage)
	{
		if ($this->opts['prevText'] && ($currentPage > 0 || $this->opts['prevShow'])) {
			$this->createLink($currentPage - 1, $currentPage, ['text' => $this->opts['prevText'], 'class' => 'prev']);
		}
	}
	
	private function getNextButton($currentPage)
	{
		if ($this->opts['nextText'] && ($currentPage < $this->totalPage - 1 || $this->opts['nextShow'])) {
			$this->createLink($currentPage + 1, $currentPage, ['text' => $this->opts['nextText'], 'class' => 'next']);
		}
	}
	
	private function getRangePages($currentPage)
	{
		if ($this->opts['numShow']) {
			$interval = $this->pc->getInterval($currentPage);
			
			if ($interval['start'] > 0 && $this->opts['edgeNum'] > 0) {
				$end = min($this->opts['edgeNum'], $interval['start']);
				$this->appendRange($currentPage, 0, $end);
				
				if ($this->opts['edgeNum'] < $interval['start'] && $this->opts['ellipseText']) {
					$this->createPages(['text' => $this->opts['ellipseText']]);
				}
			}
			
			$this->appendRange($currentPage, $interval['start'], $interval['end']);
			
			if ($interval['end'] < $this->totalPage && $this->opts['edgeNum'] > 0) {
				if ($this->totalPage - $this->opts['edgeNum'] > $interval['end'] && $this->opts['ellipseText']) {
					$this->createPages(['text' => $this->opts['ellipseText']]);
				}
				
				$start = max($this->totalPage - $this->opts['edgeNum'], $interval['end']);
				$this->appendRange($currentPage, $start, $this->totalPage);
			}
		}
	}
	
	public function render()
	{
		return sprintf('<ul class="pagination">%s</ul>', $this->getPageWrapper());
	}
	
	protected function getPageWrapper()
	{
		$html = '';
		
		foreach ($this->pages as $v) {
			$html .= '<li' . (isset($v['class']) ? ' class="' . $v['class'] . '"' : '') . '>';
			
			if (isset($v['url'])) {
				$html .= '<a href="' . $v['url'] . '">' . $v['text'] . '</a>';
			} else {
				$html .= '<span>' . $v['text'] . '</span>';
			}
			
			$html .= '</li>';
		}
		return $html;
	}
	
	private function createLink($page, $currentPage, $opts = [])
	{
		$page = $page < 0 ? 0 : ($page < $this->totalPage ? $page : $this->totalPage - 1);
		$opts = array_merge(['text' => $page + 1], $opts);
		
		if ($page == $currentPage) {
			$opts['class'] = 'current' . (isset($opts['class']) ? ' ' . $opts['class'] : '');
		} else {
			$opts['url'] = $this->createUrl($page + 1);
		}
		
		$this->createPages($opts);
	}
	
	private function createUrl($page)
	{
		if (false === strpos($this->opts['url'], '[PAGE]')) {
			return \SM\Http\Url::append($this->opts['url'], [$this->opts['pageName'] => $page]);
		}
		
		return str_replace('[PAGE]', $page, $this->opts['url']);
	}
	
	private function createPages($opts)
	{
		$this->pages[] = $opts;
	}
	
	private function appendRange($currentPage, $start, $end, $opts = [])
	{
		for ($i = $start; $i < $end; $i++) {
			$this->createLink($i, $currentPage, $opts);
		}
	}
}
