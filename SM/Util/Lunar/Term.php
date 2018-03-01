<?php
namespace SM\Util\Lunar;

class Term
{
	use LunarTrait;
	
	protected $cache = [];
	protected $correction;
	private static $_instance = null;
	
	public static function getInstance()
	{
		if (!static::$_instance) {
			static::$_instance = new static();
		}
		return static::$_instance;
	}
	
	private function __construct()
	{
		$this->correction = $this->unzip('FrcFs11AFsckF1tsDtFqEtF3posFdFgiFseFtmelpsEfhkF1anmelpFlF3ikrotcnEqEq1FfqmcDsrFor11FgFrcgDscFs11FgEeFtE1sfFs11sCoEsaF1tsD3FpeE1eFsssEciFsFnmelpFcFhkF1tcnEqEpFgkrotcnEqrEtFermcDsrE111FgBmcmr11DaEfnaF111sD3FpeForeF1tssEfiFpEoeFssD3iFstEqFppDgFstcnEqEpFg33FscnEqrAoAF1ClAEsDmDtCtBaDlAFbAEpAAAAAD1FgBiBqoBbnBaBoAAAAAAAEgDqAdBqAFrBaBoACdAAf3AACgAAAeBbCamDgEifAE1AABa3C3BgFdiAAACoCeE3ADiEifDaAEqAAFe3AcFbcAAAAAF3iFaAAACpACmFmAAAAAAAACrDaAAADG0');
	}
	
	public function getTerms($jd)
	{
		$winterDay = $this->getNearestWinter($jd);
		
		if (isset($this->cache[$winterDay])) {
			return $this->cache[$winterDay];
		}
		
		$terms = $hash = [];
		
		for ($i = 0; $i < 25; $i++) {
			$JD   = $this->calc($winterDay + 15.2184 * $i);
			$name = $this->termsCn[$i % 24];
			
			$terms[]   = ['JD' => $JD, 'name' => $name];
			$hash[$JD] = $name;
		}
		
		$this->cache[$winterDay] = ['terms' => $terms, 'hash' => $hash];
		return $this->cache[$winterDay];
	}
	
	protected function getNearestWinter($jd)
	{
		$winterDay = floor(($jd - 355 + 183) / 365.2422) * 365.2422 + 355;
		
		if ($this->calc($winterDay) > $jd) {
			$winterDay -= 365.2422;
		}
		return $winterDay;
	}
	
	protected function calcForLow($W)
	{
		$v  = 628.3319653318;
		$t  = ($W - 4.895062166) / $v;
		$t -= (53 * $t * $t + 334116 * cos(4.67 + 628.307585 * $t) + 2061 * cos(2.678 + 628.3076 * $t) * $t) / $v / 10000000;
		$L  = 48950621.66 + 6283319653.318 * $t + 53 * $t * $t + 334166 * cos(4.669257 + 628.307585 * $t) + 3489 * cos(4.6261 + 1256.61517 * $t) + 2060.6 * cos(2.67823 + 628.307585 * $t) * $t - 994 - 834 * sin(2.1824 - 33.75705 * $t);
		$t -= ($L / 10000000 - $W) / 628.332 + (32 * ($t + 1.8) * ($t + 1.8) - 20) / 86400 / 36525;
		
		return $t * 36525 + 8 / 24;
	}
	
	protected function calcForHigh($W)
	{
		$t = XL::S_aLon_t2($W) * 36525;
		$t = $t - XL::dt_T($t) + 8 / 24;
		$v = (($t + 0.5) % 1) * 86400;
		
		if ($v < 1200 || $v > 86400 - 1200) {
			$t = XL::S_aLon_t($W) * 36525 - XL::dt_T($t) + 8 / 24;
		}
		return $t;
	}
	
	protected function calc($jd)
	{
		$jd += JulianDay::JD2000;
		$pc  = 7;
		
		$JDstart = 2322147.76;
		$JD1960  = 2436935;
		
		if ($jd >= $JD1960) {
			return floor($this->calcForHigh(floor(($jd + $pc - 2451259) / 365.2422 * 24) * pi() / 12) + 0.5);
		}
		
		if ($jd >= $JDstart && $jd < $JD1960) {
			$D = floor($this->calcForLow(floor(($jd + $pc - 2451259) / 365.2422 * 24) * pi() / 12) + 0.5);
			$n = substr($this->correction, floor(($jd - $JDstart) / 365.2422 * 24), 1) - 0;
			
			return $D + ($n ? $n - 2 : $n);
		}
	}
}
