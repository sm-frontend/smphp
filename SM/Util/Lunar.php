<?php
namespace SM\Util;

use SM\Util\Lunar\Term;
use SM\Util\Lunar\Moon;
use SM\Util\Lunar\Festival;
use SM\Util\Lunar\Worktime;
use SM\Util\Lunar\JulianDay;
use SM\Util\Lunar\LunarTrait;

class Lunar
{
	use LunarTrait;
	
	protected $mjd;
	protected $terms;
	protected $moons;
	protected $data = [];
	
	public function __construct($date)
	{
		$this->data['timestamp'] = Date::getTimeStamp($date);
		
		$this->mjd               = JulianDay::fromDate($this->data['timestamp']) - JulianDay::JD2000;
		$this->terms             = Term::getInstance()->getTerms($this->mjd);
		$this->moons             = Moon::getInstance()->getMoons($this->terms['terms']);
		
		$this->getLunarDate();
		$this->getTerm();
		$this->getZodiac();
		$this->getCyclical();
		$this->getFestival();
		$this->getWorktime();
	}
	
	public function getLunarData()
	{
		return $this->data;
	}
	
	protected function getTerm()
	{
		if (isset($this->terms['hash'][$this->mjd])) {
			$this->data['term'] = $this->terms['hash'][$this->mjd];
		}
	}
	
	protected function getFestival()
	{
		Festival::getFestival($this->data);
	}
	
	protected function getWorktime()
	{
		Worktime::getWorktime($this->data);
	}
	
	protected function getZodiac()
	{
		$this->data['animal'] = $this->chineseZodiac[($this->data['lYear'] + 6000) % 12];
	}
	
	protected function getCyclical()
	{
		$this->getGzYear();
		$this->getGzMonth();
		$this->getGzDate();
	}
	
	protected function getGzYear()
	{
		$this->data['gzYear'] = $this->getGanZhi($this->data['lYear'] + 6000);
	}
	
	protected function getGzMonth()
	{
		$month = floor(($this->mjd - $this->terms['terms'][0]['JD']) / 30.43685);
		
		if ($month < 12 && $this->mjd >= $this->terms['terms'][2 * $month + 1]['JD']) {
			$month ++;
		}
		
		$month += floor(($this->terms['terms'][12]['JD'] + 390) / 365.2422) * 12 + 900000;
		
		$this->data['gzMonth'] = $this->getGanZhi($month);
	}
	
	protected function getGzDate()
	{
		$this->data['gzDate'] = $this->getGanZhi($this->mjd - 6 + 9000000);
	}
	
	protected function getGanZhi($t)
	{
		return $this->heavenlyStems[$t % 10] . $this->earthlyBranches[$t % 12];
	}
	
	protected function getLunarDate()
	{
		$days = $this->round($this->mjd - $this->moons[0]['JD']);
		$j    = count($this->moons) - 1;
		
		for ($i = 0; $i < $j; $i++) {
			$o = $this->moons[$i];
			
			if ($days < $o['days']) {
				$this->data['isBigMonth'] = $o['days'] == 30 ? 1 : 0;
				$this->data['lMonth']     = $o['name'] . '月';
				$this->data['lDate']      = $this->dateCn[$days];
				break;
			} else {
				$days -= $o['days'];
			}
		}
		
		$this->mjd           = $this->round($this->mjd);
		$this->data['lYear'] = floor(($this->terms['terms'][3]['JD'] + ($this->mjd < $this->terms['terms'][3]['JD'] ? -365 : 0) + 365.25 * 16 - 35) / 365.2422 + 0.5);
	}
}
