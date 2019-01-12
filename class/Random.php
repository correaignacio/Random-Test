<?php
/*
* Author: Ignacio Correa
* Clase para generar series random y evaluar si realmente lo son
* Tests: monobit y Run Tes
*/

class Random
{
    protected $items = [];
    protected $verbose = false;
    protected $validCharacters = '0123456789';
    protected $probabilityTolerance = 0.0001; // one in ten thousand
    protected $monobitTolerance = 0.01;
    protected $runTestTolerance = 1.96;
    protected $runTestToleranceA = 1.96;
    protected $runTestToleranceB = 0.01;
    protected $runTestFunc = 'A';

    protected $min = 0;
    protected $max = 99999999999999;
    protected $length = 14;

    public function __construct()
    {
    }

    public function verbose()
    {
        $this->verbose = true;
    }

    public function setItems($items)
    {
        $this->items = is_string($items) ? array($items) : $items;
    }

    public function setValidCharacters($validCharacters)
    {
        $this->validCharacters = $validCharacters;
    }

    public function setMin($min)
    {
        $this->min = $min;
    }

    public function setMax($max)
    {
        $this->max = $max;
    }

    public function setLength($length)
    {
        $this->length = $length;
    }

    public function setProbabilityTolerance($tolerance)
    {
        $this->probabilityTolerance = $tolerance;
    }

    public function setMonobitTolerance($tolerance)
    {
        $this->monobitTolerance = $tolerance;
    }

    public function setRunTestTolerance($tolerance)
    {
        $this->runTestTolerance = $tolerance;
    }

    public function setRunTestFunc($func)
    {
        $this->runTestFunc = $func;
    }

    public function isRandom($item)
    {
        $ok = true;
        $prob = $this->probability($item);
        $binStr = $this->toBinary($item);
        $mbr = $this->monoBitTest($binStr);
        $rtr = $this->runTest($binStr);

        if($prob > $this->probabilityTolerance) {
            $ok = false;
        }
        
        if($mbr <= $this->monobitTolerance) {
            $ok = false;
        }

        if( ($this->runTestFunc == 'A' && $rtr >= $this->runTestTolerance) 
            || ($this->runTestFunc == 'B' && $rtr <= $this->runTestTolerance) 
        ) {
            $ok = false;
        }

        if ($this->verbose) {
            $prob = round($prob, 5);
            $mbr = round($mbr, 5);
            $rtr = round($rtr, 5);
            echo "$item - ($binStr) Probability: $prob - Monobit: $mbr - RunTest: $rtr - " . ($ok ? "OK" : "NOT Random") . PHP_EOL;
        }
        
        
        return $ok;
    }

    public function evaluate()
    {
        $itemEval = [];
        if($this->verbose) {
            echo 'Parameter to evaluate: ' . PHP_EOL;
            echo 'Probability tolerance: must be smaller than ' . $this->probabilityTolerance . PHP_EOL;
            echo 'Monobit tolerance: must be bigger than ' . $this->monobitTolerance . PHP_EOL;
            echo 'Run Test tolerance: must be ' . ($this->runTestFunc == 'A' ? 'smaller' : 'bigger') . ' than ' . ($this->runTestFunc == 'A' ? $this->runTestToleranceA : $this->runTestToleranceB) . PHP_EOL . PHP_EOL;
        }
        foreach ($this->items as $item) {
            $result = $this->isRandom($item);
            $itemEval[] = [$item, $result];
        }
    
        return $itemEval;
    }

    // only generate numbers
    public function generateRandomSerials($q = 1, $rand_func = 'mt_rand')
    {
        $min = $this->min;
        ;
        $max = $this->max;
        $length = $this->length;
        $series = [];
        for ($i = 0; $i < $q; $i++) {
            switch ($rand_func) {
                case 'mt_rand':
                    $series[] = str_pad(mt_rand($min, $max), $length, '0', STR_PAD_LEFT);
                break;
                case 'random_int':
                    $series[] = str_pad(random_int($min, $max), $length, '0', STR_PAD_LEFT);
                break;
                default:
                    if (function_exists($rand_func)) {
                        $series[] = str_pad($rand_func($min, $max), $length, '0', STR_PAD_LEFT);
                    }
                break;
            }
        }

        return $series;
    }

    // one In Ten Thousand test
    public function probability($item) {
        // Variaciones con repeticion donde n es el numero de caracteres validos y p la longitud: n^p
        // Ej: solo numeros del 0 al 9 y serie de 14 de largo: 10^14 = 100.000.000.000.000 posibilidades (casos posibles)
        // Probabilidad de adivinar una serie es la division entre casos favorables y casos posibles: 1 / 100.000.000.000.000 < 1 / 10.000
        $length = strlen($item);
        $characters = strlen($this->validCharacters);
        $posibilities = pow($characters, $length);
        $probability = 1/$posibilities;

        return $probability;
    }

    public function monoBitTest($binStr) {
        $count = 0;
        $length = strlen($binStr);
        $binStr = str_split($binStr, 1);
        for($i=0; $i < $length; $i++) {
            $count += $binStr[$i] == '0' ? -1 : 1;
        }
        $sobs = $count / sqrt($length);
        $p_val = $this->complementaryErrorFunction(abs($sobs) / sqrt(2));
        return $p_val;
    }

    public function runTest($binStr) {
        if($this->runTestFunc == 'A') {
            $this->runTestTolerance = $this->runTestToleranceA;
            return $this->runtTestA($binStr);
        } else {
            $this->runTestTolerance = $this->runTestToleranceB;
            return $this->runtTestB($binStr);
        }

        //return runtTestB();
    }

    // one implementation of The Run Test (probabilidad)
    private function runtTestA($binStr){
        $zeros = 0;
        $ones = 0;
        $bit = 0;
        $sequences = 0;
        $length = strlen($binStr);
        $binStr = str_split($binStr, 1);
        for($i=0; $i<$length; $i++) {
            if ($i == 0) {
                $bit = $binStr[$i];
                $sequences++;
            } else {
                if($bit != $binStr[$i]) {
                    $bit = $binStr[$i];
                    $sequences++;
                }
            }

            if($binStr[$i] == '0') {
                $zeros++;
            } else {
                $ones++;
            }
        }

        // Mediana
        $mean = (2 * $zeros * $ones) / ($zeros + $ones) + 1;
        // Varianza
        $var = sqrt((($mean - 1) * ($mean - 2)) / ($zeros + $ones - 1));
        // Probabilidad
        $prob = ($sequences - $mean) / $var;

        return $prob;
    }

    // another implementation of runTest (NIST)
    private function runtTestB($binStr){
        $zeros = 0;
        $ones = 0;
        $bit = 0;
        $sequences = 0;
        $length = strlen($binStr);
        $binStr = str_split($binStr, 1);
        for($i=0; $i<$length; $i++) {
            if($binStr[$i] == '0') {
                $zeros++;
            } else {
                $ones++;
            }
        }
        $p = $ones / $length;
        $tau = 2 / sqrt($length);
        if (abs($p - 0.5) > $tau) {
            return 0.0;
        }

        for($i=0; $i<$length; $i++) {
            if ($i == 0) {
                $bit = $binStr[$i];
                $sequences++;
            } else {
                if($bit != $binStr[$i]) {
                    $bit = $binStr[$i];
                    $sequences++;
                }
            }
        }

        $num = abs($sequences - 2.0 * $length * $p * (1.0 - $p));
        $den = 2.0 * sqrt(2.0 * $length) * $p * (1.0 - $p);
        $p_val = $this->complementaryErrorFunction($num / $den);
        
        return $p_val;
    }


    /*******
    * Utils functions
    *******/

    static function toBinary($s)
    {
        $value = unpack('H*', $s);
        //return base_convert($value[1], 16, 2);
        return self::convBase($value[1], '0123456789ABCDEF', '01');
    }

    // for large numbers
    static function convBase($numberInput, $fromBaseInput, $toBaseInput)
    {
        if ($fromBaseInput == $toBaseInput) {
            return $numberInput;
        }
        $fromBase = str_split($fromBaseInput, 1);
        $toBase = str_split($toBaseInput, 1);
        $number = str_split($numberInput, 1);
        $fromLen = strlen($fromBaseInput);
        $toLen = strlen($toBaseInput);
        $numberLen = strlen($numberInput);
        $retval = '';
        if ($toBaseInput == '0123456789') {
            $retval = 0;
            for ($i = 1;$i <= $numberLen; $i++) {
                $retval = bcadd($retval, bcmul(array_search($number[$i - 1], $fromBase), bcpow($fromLen, $numberLen - $i)));
            }

            return $retval;
        }
        if ($fromBaseInput != '0123456789') {
            $base10 = self::convBase($numberInput, $fromBaseInput, '0123456789');
        } else {
            $base10 = $numberInput;
        }
        if ($base10 < strlen($toBaseInput)) {
            return $toBase[$base10];
        }
        while ($base10 != '0') {
            $retval = $toBase[bcmod($base10, $toLen)] . $retval;
            $base10 = bcdiv($base10, $toLen, 0);
        }

        return $retval;
    }

    static function numberOfSetBits($v) {
        $c = $v - (($v >> 1) & 0x55555555); 
        $c = (($c >> 2) & 0x33333333) + ($c & 0x33333333);
        $c = (($c >> 4) + $c) & 0x0F0F0F0F; 
        $c = (($c >> 8) + $c) & 0x00FF00FF;
        $c = (($c >> 16) + $c) & 0x0000FFFF;    
        return $c;
    }
    
    static function countbinaryStr($binstr, $char = '1') {
        return substr_count($binstr, $char); 
    }
    
    static function compare($a, $b) {
        $total = ($a + $b);
        return round(abs(($a / $total) - ($b / $total)) * 100, 2);
    }

    /**
     * Error function (Gauss error function)
     * https://en.wikipedia.org/wiki/Error_function
     *
     * This is an approximation of the error function (maximum error: 1.5×10−7)
     *
     * erf(x) ≈ 1 - (a₁t + a₂t² + a₃t³ + a₄t⁴ + a₅t⁵)ℯ^-x²
     *
     *       1
     * t = ------
     *     1 + px
     *
     * p = 0.3275911
     * a₁ = 0.254829592, a₂ = −0.284496736, a₃ = 1.421413741, a₄ = −1.453152027, a₅ = 1.061405429
     *
     * @param  float $x
     *
     * @return float
     */
    private function errorFunction(float $x): float
    {
        if ($x == 0) {
            return 0;
        }

        $p  = 0.3275911;
        $t  = 1 / ( 1 + $p*abs($x) );

        $a₁ = 0.254829592;
        $a₂ = -0.284496736;
        $a₃ = 1.421413741;
        $a₄ = -1.453152027;
        $a₅ = 1.061405429;

        $error = 1 - ( $a₁*$t + $a₂*$t**2 + $a₃*$t**3 + $a₄*$t**4 + $a₅*$t**5 ) * exp(-abs($x)**2);

        return ( $x > 0 ) ? $error : -$error;
    }

    /**
     * Complementary error function (erfc)
     * erfc(x) ≡ 1 - erf(x)
     *
     * @param  number $x
     *
     * @return float
     */
    private function complementaryErrorFunction($x): float
    {
        return 1 - self::errorFunction($x);
    }
}