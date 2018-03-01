<?php
namespace SM\Util\Lunar;

class Festival
{
	private static $sFtv = [
		'0101' => '元旦',
		'0202' => '湿地日,1997',
		'0214' => '情人节',
		'0303' => '爱耳日,1999',
		'0308' => '妇女节,1975',
		'0312' => '植树节,1979',
		'0315' => '消费者权益日,1983',
		'0401' => '愚人节',
		'0407' => '卫生日,1948',
		'0422' => '地球日,1990',
		'0501' => '劳动节',
		'0504' => '青年节,1939',
		'0512' => '护士节,1912',
		'0518' => '博物馆日,1977',
		'0531' => '无烟日,1988',
		'0601' => '儿童节,1950',
		'0605' => '环境日,1972',
		'0606' => '爱眼日,1996',
		'0623' => '奥林匹克日,1948',
		'0625' => '土地日,1991',
		'0626' => '禁毒日,1987',
		'0701' => '建党节,1941',
		'0801' => '建军节,1933',
		'0903' => '抗战胜利纪念日,2015',
		'0910' => '教师节,1985',
		'0918' => '九一八,1931',
		'0920' => '爱牙日,1989',
		'1001' => '国庆节,1949',
		'1031' => '万圣节',
		'1108' => '记者节,2000',
		'1111' => '光棍节,1993',
		'1117' => '学生日,1946',
		'1201' => '艾滋病日,1988',
		'1224' => '平安夜',
		'1225' => '圣诞节'
	];
	
	private static $lFtv = [
		'正月十五' => '元宵节',
		'二月初二' => '龙抬头',
		'五月初五' => '端午节',
		'七月初七' => '七夕节',
		'七月十五' => '中元节',
		'八月十五' => '中秋节',
		'九月初九' => '重阳节',
		'十月初一' => '寒衣节',
		'十月十五' => '下元节',
		'腊月初八' => '腊八节',
		'腊月廿三' => '小年',
		'正月初一' => '春节'
	];
	
	private static $wFtv = [
		'0520' => '母亲节',
		'0630' => '父亲节',
		'1144' => '感恩节'
	];
	
	public static function getFestival(&$data)
	{
		$m = date('n', $data['timestamp']);
		$d = date('j', $data['timestamp']);
		$k = date('md', $data['timestamp']);
		
		if (isset(static::$sFtv[$k])) {
			$ex = explode(',', static::$sFtv[$k]);
			
			if (!isset($ex[1])) {
				$data['festival'] = static::$sFtv[$k];
			} elseif (date('Y', $data['timestamp']) >= intval($ex[1])) {
				$data['festival'] = $ex[0];
			}
		}
		
		$k = date('m', $data['timestamp']) . ceil($d / 7) . date('w', $data['timestamp']);
		
		if (isset(static::$wFtv[$k])) {
			$data['festival'] = static::$wFtv[$k];
		}
		
		if (($m == 3 || $m == 4) && function_exists('easter_date')) {
			$estDay = easter_date(date('Y', $data['timestamp']));
			
			if ($m . $d == date('nj', $estDay)) {
				$data['festival'] = '复活节';
			}
		}
		
		$k = $data['lMonth'] . $data['lDate'];
		
		if (isset(static::$lFtv[$k])) {
			$data['festival'] = static::$lFtv[$k];
		} elseif ($data['lMonth'] == '腊月' && $data['lDate'] == ($data['isBigMonth'] ? '三十' : '廿九')) {
			$data['festival'] = '除夕';
		}
	}
}
