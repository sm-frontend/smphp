<?php
namespace SM\Pagination;

class Pagination
{
	private $pagination;
	private $opts = [
		'url'         => '',
		'pageName'    => 'page',
		'perPage'     => 10,
		'displayNum'  => 11,
		'edgeNum'     => 2,
		'prevText'    => 'Prev',
		'nextText'    => 'Next',
		'ellipseText' => '...',
		'prevShow'    => false,
		'nextShow'    => false,
		'numShow'     => true
	];
	
	public function __construct($totalItems, $currentPage = 1, array $opts = [])
	{
		$totalItems       = $this->setNumber($totalItems);
		
		$currentPage      = $this->setNumber($currentPage);
		
		$opts             = $this->setOpts($opts);
		
		$renderer         = new PaginationRender($totalItems, $opts);
		
		$this->pagination = $renderer->getPagination(--$currentPage);
	}
	
	public function getPages()
	{
		return $this->pagination->getPages();
	}
	
	private function setNumber($num)
	{
		$num = intval($num);
		$num = !$num ? 1 : $num;
		
		return $num;
	}
	
	private function setOpts($opts)
	{
		$opts        = array_merge($this->opts, $opts);
		$opts['url'] = empty($opts['url']) ? \SM\Http\Url::current() : $opts['url'];
		
		return $opts;
	}
	
	public function __toString()
	{
		return $this->pagination->render();
	}
}
