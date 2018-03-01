<?php
namespace SM\Util;

define('IDC_INDEX_CAT', 0);
define('IDC_INDEX_NUM_ORIGIN', 1);
define('IDC_INDEX_NUM_18', 2);
define('IDC_INDEX_ADDR', 3);
define('IDC_INDEX_BIRTH', 4);
define('IDC_INDEX_SEX', 5);

define('IDC_FLAG_INVALID', 0);
define('IDC_FLAG_OLD', 1);
define('IDC_FLAG_NEW', 2);

define('IDC_SEX_UNKNOWN', 0);
define('IDC_SEX_MALE', 1);
define('IDC_SEX_FEMALE', 2);

class Idcard
{
	public $info;
	
	public function __construct()
	{
		$this->info = [
			IDC_INDEX_CAT        => IDC_FLAG_INVALID,
			IDC_INDEX_NUM_ORIGIN => '',
			IDC_INDEX_NUM_18     => '',
			IDC_INDEX_ADDR       => [0 => '', 1 => '', 2 => ''],
			IDC_INDEX_BIRTH      => [0 => '', 1 => '', 2 => ''],
			IDC_INDEX_SEX        => IDC_SEX_UNKNOWN
		];
	}
	
	public function parseIdc($num)
	{
		if (preg_match("/^\d{15}$|^\d{18}$|^\d{17}x$/i", $num) == 0) {
			$this->info[IDC_INDEX_CAT] = IDC_FLAG_INVALID;
			return $this->info;
		}
		
		$this->info[IDC_INDEX_NUM_ORIGIN] = $num;
		
		if (strlen($num) == 15) {
			$this->info[IDC_INDEX_CAT]    = IDC_FLAG_OLD;
			$this->info[IDC_INDEX_NUM_18] = $this->update15($num);
		}
		
		if (strlen($num) == 18) {
			$this->info[IDC_INDEX_CAT]    = IDC_FLAG_NEW;
			$this->info[IDC_INDEX_NUM_18] = $num;
		}
		
		if (!$this->verifyNum($this->info[IDC_INDEX_NUM_18])) {
			$this->info[IDC_INDEX_CAT] = IDC_FLAG_INVALID;
			return $this->info;
		}
		
		$this->info[IDC_INDEX_ADDR]  = $this->parseAddr($this->info[IDC_INDEX_NUM_18]);
		$this->info[IDC_INDEX_BIRTH] = $this->parseBirth($this->info[IDC_INDEX_NUM_18]);
		$this->info[IDC_INDEX_SEX]   = $this->parseSex($this->info[IDC_INDEX_NUM_18]);
		
		if (checkdate($this->info[IDC_INDEX_BIRTH][1], $this->info[IDC_INDEX_BIRTH][2], $this->info[IDC_INDEX_BIRTH][0]) == false) {
			$this->info[IDC_INDEX_CAT] = IDC_FLAG_INVALID;
		} elseif (strtotime($this->info[IDC_INDEX_BIRTH][0] . '/' . $this->info[IDC_INDEX_BIRTH][1] . '/' . $this->info[IDC_INDEX_BIRTH][2]) > TIMENOW) {
			$this->info[IDC_INDEX_CAT] = IDC_FLAG_INVALID;
		}
		
		return $this->info;
	}
	
	protected function update15($num)
	{
		if (strlen($num) != 15) {
			return '';
		}
		
		if (array_search(substr($num, 12, 3), [996, 997, 998, 999]) !== false) {
			$num = substr($num, 0, 6) . 18 . substr($num, 6, 9);
		} else {
			$num = substr($num, 0, 6) . 19 . substr($num, 6, 9);
		}
		
		return $num . $this->calVerify($num);
	}
	
	protected function parseAddr($num)
	{
		$arr_rtn = [0 => '', 1 => '', 2 => ''];
		
		if (strlen($num) != 18) {
			return $arr_rtn;
		}
		
		$file_data = __DIR__ . '/Idcard/area_code.dat';
		
		if (!is_file($file_data)) {
			return $arr_rtn;
		}
		
		$s1 = str_pad(substr($num, 0, 2), 6, '0', STR_PAD_RIGHT);
		$s2 = str_pad(substr($num, 0, 4), 6, '0', STR_PAD_RIGHT);
		$s3 = str_pad(substr($num, 0, 6), 6, '0', STR_PAD_RIGHT);
		
		$h  = fopen($file_data, 'r');
		
		while (!feof($h)) {
			$buffer = fgets($h, 4096);
			$arr    = explode(',', trim($buffer));
			
			if (strcmp($arr[0], $s1) == 0) {
				$arr_rtn[0] = $arr[1];
			}
			
			if (strcmp($arr[0], $s2) == 0) {
				$arr_rtn[1] = $arr[1];
			}
			
			if (strcmp($arr[0], $s3) == 0) {
				$arr_rtn[2] = $arr[1];
				break;
			}
		}
		fclose($h);
		return $arr_rtn;
	}
	
	protected function parseBirth($num)
	{
		$arr_rtn = [0 => '', 1 => '', 2 => ''];
		
		if (strlen($num) != 18) {
			return $arr_rtn;
		}
		
		$arr_rtn[0] = substr($num, 6, 4);
		$arr_rtn[1] = substr($num, 10, 2);
		$arr_rtn[2] = substr($num, 12, 2);
		
		return $arr_rtn;
	}
	
	protected function parseSex($num)
	{
		$rtn_sex = IDC_SEX_UNKNOWN;
		
		if (strlen($num) != 18) {
			return $rtn_sex;
		}
		
		if (is_int(substr($num, 16, 1) / 2)) {
			$rtn_sex = IDC_SEX_FEMALE;
		} else {
			$rtn_sex = IDC_SEX_MALE;
		}
		return $rtn_sex;
	}
	
	protected function calVerify($num)
	{
		if (strlen($num) != 17) {
			if (strlen($num) == 18) {
				$num = substr($num, 0, 17);
			} else {
				return false;
			}
		}
		
		$factor           = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
		$verifyNumberList = [1, 0, 'X', 9, 8, 7, 6, 5, 4, 3, 2];
		$checksum         = 0;
		
		for ($i = 0, $j = strlen($num); $i < $j; $i++) {
			$checksum += substr($num, $i, 1) * $factor[$i];
		}
		
		return $verifyNumberList[$checksum % 11];
	}
	
	protected function verifyNum($num)
	{
		if (strlen($num) != 18) {
			return false;
		}
		
		if (strcasecmp(substr($num, 17, 1), $this->calVerify($num)) == 0) {
			return true;
		} else {
			return false;
		}
	}
}
