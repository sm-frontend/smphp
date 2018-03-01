<?php
namespace SM\Util\Lunar;

class JulianDay
{
	const JD2000 = 2451545;
	
	public static function fromDate($time)
	{
		$y = date('Y', $time);
		$m = date('n', $time);
		$d = date('j', $time) + ((date('s', $time) / 60 + date('i', $time)) / 60 + date('H', $time)) / 24;
		
		$isGregory = ($y * 372 + $m * 31 + floor($d)) >= 588829;
		$numLeap   = 0;
		
		if ($m <= 2) {
			$m += 12;
			$y -= 1;
		}
		
		if ($isGregory) {
			$numLeap = floor($y / 100);
			$numLeap = 2 - $numLeap + floor($numLeap / 4);
		}
		
		return floor(365.25 * ($y + 4716)) + floor(30.6001 * ($m + 1)) + $d + $numLeap - 1524.5;
	}
}
