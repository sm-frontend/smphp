<?php
namespace SM\Util;

class Ip
{
	public static function parse($ip)
	{
		$markLen = 32;
		
		if (strpos($ip, '/') > 0) {
			list($ip, $markLen) = explode('/', $ip);
		}
		
		$long    = static::safeIp2long($ip);
		$mark    = static::getMaskByMarkLen($markLen);
		
		$ipStart = $long & $mark;
		$ipEnd   = $long | (~$mark) & 0xFFFFFFFF;
		
		return [$long, $mark, $ipStart, $ipEnd];
	}
	
	public static function in($ipLong, $ipMark)
	{
		if (strpos($ipMark, '/') > 0) {
			list($ipMark, $markLen) = explode('/', $ipMark);
			
			$rightLen = 32 - $markLen;
			return $ipLong >> $rightLen == static::safeIp2long($ipMark) >> $rightLen;
		}
		
		return false;
	}
	
	public static function fetchLocalIp()
	{
		return gethostbyname(gethostname());
	}
	
	public static function fetchIp()
	{
		return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
	}
	
	public static function fetchAltIp()
	{
		$altIp = static::fetchIp();

		if (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$altIp = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && (($ip = static::getIpv4FromXFF($_SERVER['HTTP_X_FORWARDED_FOR'])) || ($ip = static::getIpv6FromXFF($_SERVER['HTTP_X_FORWARDED_FOR'])))) {
			$altIp = $ip;
		} elseif (isset($_SERVER['HTTP_FROM'])) {
			$altIp = $_SERVER['HTTP_FROM'];
		}

		return $altIp;
	}

	public static function getIpv4FromXFF($xForwardedFor)
	{
		$ipv4 = null;

		if (preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $xForwardedFor, $matches)) {
			foreach ($matches[0] as $ip) {
				if (!static::checkPrivateIp($ip)) {
					$ipv4 = $ip;
					break;
				}
			}
		}

		return $ipv4;
	}

	public static function getIpv6FromXFF($xForwardedFor)
	{
		$ipv6 = null;
		$ips  = explode(',', $xForwardedFor);
		
		foreach ($ips as $ip) {
			$ip = trim($ip);

			if (static::isIpv6($ip) && !static::checkPrivateIpv6($ip)) {
				$ipv6 = $ip;
				break;
			}
		}

		return $ipv6;
	}

	public static function checkPrivateIp($ip)
	{
		$ranges = ['10.0.0.0/8', '127.0.0.0/8', '169.254.0.0/16', '172.16.0.0/12', '192.168.0.0/16'];
		$ipLong = static::safeIp2long($ip);
		
		foreach ($ranges as $v) {
			if (static::in($ipLong, $v)) {
				return true;
			}
		}
		return false;
	}
	
	public static function parseIpList(array $ipList)
	{
		if (empty($ipList)) {
			return [];
		}
		
		$ipArr = $ipSort = [];
		
		foreach ($ipList as $v) {
			$range    = static::parse($v);
			
			$ipArr[]  = ['start' => $range[2], 'end' => $range[3]];
			$ipSort[] = $range[2];
		}
		
		array_multisort($ipSort, SORT_ASC, $ipArr);
		
		$start  = $end = 0;
		$ipList = [];
		
		foreach ($ipArr as $v) {
			if (!$start) {
				$start = $v['start'];
				$end   = $v['end'];
			} elseif ($v['start'] > $end + 1) {
				$ipList[] = $start;
				$ipList[] = $end;
				$start    = $v['start'];
				$end      = $v['end'];
			} elseif ($v['end'] > $end) {
				$end = $v['end'];
			}
		}
		
		$ipList[] = $start;
		$ipList[] = $end;
		
		return $ipList;
	}
	
	public static function lookup($ip, array $ipList)
	{
		$low    = 0;
		$high   = count($ipList) - 1;
		$ipLong = static::safeIp2long($ip);
		
		while ($low <= $high) {
			$mid = (($low + $high) >> 1);
			
			if ($ipList[$mid] > $ipLong) {
				$high = $mid - 1;
			} elseif ($ipList[$mid] < $ipLong) {
				$low = $mid + 1;
			} else {
				return true;
			}
		}
		return $high % 2 == 0;
	}
	
	public static function safeIp2long($ip)
	{
		$ip = ip2long($ip);
		
		if ($ip < 0 && PHP_INT_SIZE == 4) {
			$ip = sprintf('%u', $ip);
		}
		return $ip;
	}
	
	public static function getMaskByMarkLen($markLen)
	{
		return 0xFFFFFFFF << (32 - $markLen) & 0xFFFFFFFF;
	}
	
	public static function calculateCIDRToFit($ipStart, $ipEnd)
	{
		return (int) floor(32 - log((static::safeIp2long($ipEnd) ^ static::safeIp2long($ipStart)) + 1, 2));
	}

	public static function ipv62Bin($ip)
	{
		$ipbin = '';
		if (($ptons = inet_pton($ip)) === false) {
			return false;
		}

		for ($i = 15; $i >= 0; $i --) {
			$bin   = sprintf("%08b", (ord($ptons[$i])));
			$ipbin = $bin . $ipbin;
		}

		return $ipbin;
	}

	public static function checkPrivateIpv6($ip)
	{
		return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) == false || static::ipv6Format($ip) == '0000:0000:0000:0000:0000:0000:0000:0001';
	}

	public static function ipv6In($ipBin, $ipMark)
	{
		$ret = false;
		if (strpos($ipMark, '/') > 0) {
			list($ipMark, $markLen) = explode('/', $ipMark);
			
			$ret = substr($ipBin, 0, $markLen) == substr(static::ipv62Bin($ipMark), 0, $markLen);
		}

		return $ret;
	}

	public static function isIpv6($ip)
	{
		return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
	}

	public static function isIpv4($ip)
	{
		return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
	}

	public static function ipv6Format($ip)
	{
		if (substr_count($ip, '::')) {
			$ip = str_replace('::', str_repeat(':0000', 8 - substr_count($ip, ':')) . ':', $ip);
		}

		$ips = explode(':', $ip);

		foreach ($ips as &$v) {
			$v = str_pad($v, 4, '0', STR_PAD_LEFT);
		};

		return implode(':', $ips);
	}
}
