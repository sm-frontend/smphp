<?php
namespace SM\Util;

class Calendar
{
	private $year;
	private $month;
	private $dateTime;
	private $firstWeekday = 1;
	private $filledBlank  = false;
	
	public function __construct($year = null, $month = null)
	{
		$this->year     = isset($year)  ? $year  : $this->getYear();
		$this->month    = isset($month) ? $month : $this->getMonth();
		
		$this->dateTime = $this->getDateTime($this->year, $this->month);
	}
	
	public function getCalendar()
	{
		$lastDay         = $this->getLastDay();
		$firstDay        = $this->getFirstDay();
		
		$prevFilledMonth = $this->getPrevMonthFilledArrayOffset($firstDay);
		
		$currentMonth    = $this->getFullDays($this->year, $this->month, 1, $lastDay);
		
		$combined        = array_merge($prevFilledMonth, $currentMonth);
		
		$nextFilledMonth = $this->getNextMonthFilledArrayOffset(count($combined));
		
		$combined        = array_merge($combined, $nextFilledMonth);
		
		return array_chunk($combined, 7, true);
	}
	
	public function getPrevMonth()
	{
		$prevYear  = $this->year;
		$prevMonth = $this->month - 1;
		
		if ($prevMonth <= 0) {
			$prevYear -= 1;
			$prevMonth = 12;
		}
		
		return ['year' => $prevYear, 'month' => $prevMonth];
	}
	
	public function getNextMonth()
	{
		$nextYear  = $this->year;
		$nextMonth = $this->month + 1;
		
		if ($nextMonth > 12) {
			$nextYear += 1;
			$nextMonth = 1;
		}
		
		return ['year' => $nextYear, 'month' => $nextMonth];
	}
	
	public function setFirstWeekday($firstWeekday)
	{
		$this->firstWeekday = (int) $firstWeekday;
	}
	
	public function setFilledBlank($filledBlank)
	{
		$this->filledBlank = (bool) $filledBlank;
	}
	
	private function getPrevMonthFilledArrayOffset($offset)
	{
		if (!$this->filledBlank && $offset > 0) {
			$prevMonth = $this->getPrevMonth();
			
			$datetime  = $this->getDateTime($prevMonth['year'], $prevMonth['month']);
			$lastDay   = (int) $datetime->format('t');
			
			return $this->getFullDays($prevMonth['year'], $prevMonth['month'], $lastDay - $offset + 1, $lastDay);
		}
		
		return $this->getNullFilledArrayOffset($offset);
	}
	
	private function getNextMonthFilledArrayOffset($offset)
	{
		$mod = $offset % 7;
		
		if (!$this->filledBlank && $mod > 0) {
			$nextMonth = $this->getNextMonth();
			
			return $this->getFullDays($nextMonth['year'], $nextMonth['month'], 1, 7 - $mod);
		}
		
		return $mod > 0 ? $this->getNullFilledArrayOffset(7 - $mod) : [];
	}
	
	private function getNullFilledArrayOffset($offset)
	{
		return ($offset > 0) ? array_fill(0, $offset, '') : [];
	}
	
	private function getFirstDay()
	{
		if (0 === $this->firstWeekday) {
			return (int) $this->dateTime->format('w');
		} else {
			return (int) $this->dateTime->format('N') - 1;
		}
	}
	
	private function getLastDay()
	{
		return (int) $this->dateTime->format('t');
	}
	
	private function getFullDays($year, $month, $start, $end)
	{
		$days = [];
		for ($i = $start; $i <= $end; $i ++) {
			$days[$year . '-' . $month . '-' . $i] = $i;
		}
		return $days;
	}
	
	private function getYear()
	{
		return date('Y');
	}
	
	private function getMonth()
	{
		return date('m');
	}
	
	private function getDateTime($year, $month)
	{
		$datetime = new \DateTime();
		$datetime->setDate($year, $month, 1);
		
		return $datetime;
	}
}
