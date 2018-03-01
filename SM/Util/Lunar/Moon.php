<?php
namespace SM\Util\Lunar;

class Moon
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
		$this->correction = $this->unzip('EqoFscDcrFpmEsF1DfFideFelFpFfFfFiaipqti3ksttikptikqckstekqttgkqttgkqteksttikptikq1fjstgjqttjkqttgkqtekstfkptikq1tijstgjiFkirFsAeACoFsiDaDiADc3AFbBfgdfikijFifegF3FhaikgFag3E1btaieeibggiffdeigFfqDfaiBkF3kEaikhkigeidhhdiegcFfakF3ggkidbiaedksaFffckekidhhdhdikcikiakicjF3deedFhFccgicdekgiFbiaikcfi3kbFibefgEgFdcFkFeFkdcfkF3kfkcickEiFkDacFiEfbiaejcFfffkhkdgkaiei3ehigikhdFikfckF3dhhdikcfgjikhfjicjicgiehdikcikggcifgiejF3jkieFhegikggcikFegiegkfjebhigikggcikdgkaFkijcfkcikfkcifikiggkaeeigefkcdfcfkhkdgkegieidhijcFfakhfgeidieidiegikhfkfckfcjbdehdikggikgkfkicjicjF3dbidikFiggcifgiejkiegkigcdiegfggcikdbgfgefjF3kfegikggcikdgFkeeijcfkcikfkekcikdgkabhkFikaffcfkhkdgkegbiaekfkiakicjhfgqdq1fkiakgkfkhfkfcjiekgFebicggbedF3jikejbbbiakgbgkacgiejkijjgigfiakggfggcibFifjefjF3kfekdgjcibFeFkijcfkfhkfkeaieigekgbhkfikidfcjeaibgekgdkiffiffkiakF3jhbakgdki3dj3ikfkicjicjieeFkgdkicggkighdF3jfgkgfgbdkicggfggkidFkiekgijkeigfiskiggfaidheigF3jekijcikickiggkidhhdbgcfkFikikhkigeidieFikggikhkffaffijhidhhakgdkhkijF3kiakF3kfheakgdkifiggkigicjiejkieedikgdfcggkigieeiejfgkgkigbgikicggkiaideeijkefjeijikhkiggkiaidheigcikaikffikijgkiahi3hhdikgjfifaakekighie3hiaikggikhkffakicjhiahaikggikhkijF3kfejfeFhidikggiffiggkigicjiekgieeigikggiffiggkidheigkgfjkeigiegikifiggkidhedeijcfkFikikhkiggkidhh3ehigcikaffkhkiggkidhh3hhigikekfiFkFikcidhh3hitcikggikhkfkicjicghiediaikggikhkijbjfejfeFhaikggifikiggkigiejkikgkgieeigikggiffiggkigieeigekijcijikggifikiggkideedeijkefkfckikhkiggkidhh3ehijcikaffkhkiggkidhh3hhigikhkikFikfckcidhh3hiaikgjikhfjicjicgiehdikcikggifikigiejfejkieFhegikggifikiggfghigkfjeijkhigikggifikiggkigieeijcijcikfksikifikiggkidehdeijcfdckikhkiggkhghh3ehijikifffffkhsFngErD3pAfBoDd3BlEtFqA1AqoEpDqElAEsEeB1BmADlDkqBtC3FnEpDqnEmFsFsAFnllBbFmDsDiCtDmAB1BmtCgpEplCpAEiBiEoFqFtEqsDcCnFtADnFlEgdkEgmEtEsCtDmADqFtAFrAtEcCqAE3BoFqC3F3DrFtBmFtAC1ACnFaoCgADcADcCcFfoFtDlAFgmFqBq1bpEoAEmkqnEeCtAE3bAEqgDfFfCrgEcBrACfAAABqAAB3AAClEnFeCtCgAADqDoBmtAAACbFiAAADsEtBqAB1FsDqpFqEmFsCeDtFlCeDtoEpClEqAAFrAFoCgFmFsFqEnAEcCqFeCtFtEnAEeFtAAEkFnErAABbFkADnAAeCtFeAfBoAEpFtAABtFqAApDcCGJ');
	}
	
	public function getMoons($terms)
	{
		$jd       = $terms[0]['JD'];
		$firstDay = $this->calc($jd);
		
		if ($firstDay > $jd) {
			$firstDay -= 29.53;
		}
		
		if (isset($this->cache[$firstDay])) {
			return $this->cache[$firstDay];
		}
		
		$o = [];
		for ($i = 0; $i < 16; $i++) {
			$JD    = $this->calc($firstDay + 29.5306 * $i);
			$o[$i] = ['JD' => $JD, 'index' => $i];
			
			if ($i) {
				$o[$i - 1]['days'] = $o[$i]['JD'] - $o[$i - 1]['JD'];
			}
		}
		
		if ($o[13]['JD'] <= $terms[24]['JD']) {
			for ($i = 1; $o[$i + 1]['JD'] > $terms[2 * $i]['JD'] && $i < 13; $i++) {
			}
			$o[$i]['isLeap'] = true;
			
			for (; $i < 16; $i++) {
				$o[$i]['index']--;
			}
		}
		
		for ($i = 0; $i < 16; $i++) {
			$o[$i]['name'] = (isset($o[$i]['isLeap']) ? '闰' : '') . $this->monthCn[$o[$i]['index'] % 12];
		}
		
		$this->cache[$firstDay] = $o;
		return $o;
	}
	
	protected function calcForLow($W)
	{
		$v  = 7771.37714500204;
		$t  = ($W + 1.08472) / $v;
		$t -= (-0.0000331 * $t * $t + 0.10976 * cos(0.785 + 8328.6914 * $t) + 0.02224 * cos(0.187 + 7214.0629 * $t) - 0.03342 * cos(4.669 + 628.3076 * $t)) / $v + (32 * ($t + 1.8) * ($t + 1.8) - 20) / 86400 / 36525;
		
		return $t * 36525 + 8 / 24;
	}
	
	protected function calcForHigh($W)
	{
		$t = XL::MS_aLon_t2($W) * 36525;
		$t = $t - XL::dt_T($t) + 8 / 24;
		$v = (($t + 0.5) % 1) * 86400;
		
		if ($v < 1800 || $v > 86400 - 1800) {
			$t = XL::MS_aLon_t($W) * 36525 - XL::dt_T($t) + 8 / 24;
		}
		return $t;
	}
	
	protected function calc($jd)
	{
		$jd += JulianDay::JD2000;
		$pc  = 14;
		
		$JDstart = 1947168.00;
		$JD1960  = 2436935;
		
		if ($jd >= $JD1960) {
			return floor($this->calcForHigh(floor(($jd + $pc - 2451551) / 29.5306) * pi() * 2) + 0.5);
		}
		
		if ($jd >= $JDstart && $jd < $JD1960) {
			$D = floor($this->calcForLow(floor(($jd + $pc - 2451551) / 29.5306) * pi() * 2) + 0.5);
			$n = substr($this->correction, floor(($jd - $JDstart) / 29.5306), 1) - 0;
			
			return $D + ($n ? $n - 2 : $n);
		}
	}
}
