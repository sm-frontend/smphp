<?php
namespace SM\Transform\Driver;

class Byte
{
	public function encode($data, $unit = 'M', $precision = 2)
	{
		$unit    = strtoupper($unit);
		$unitPow = ['B' => 0, 'K' => 1, 'M' => 2, 'G' => 3];
		
		$data   /= pow(1024, $unitPow[$unit]);
		
		return round($data, $precision) . $unit;
	}
	
	public function decode($data)
	{
		$unit    = strtoupper(substr($data, -1));
		$unitPow = ['B' => 0, 'K' => 1, 'M' => 2, 'G' => 3];
		
		$bytes   = substr($data, 0, -1);
		
		if (isset($unitPow[$unit])) {
			$bytes = $bytes * pow(1024, $unitPow[$unit]);
		}
		return $bytes;
	}
}
