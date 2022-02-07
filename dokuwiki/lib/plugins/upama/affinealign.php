<?php
class Trace {
    public $direc;
    public $score;
    
    public function __construct(int $d, float $s) {
        $this->direc = $d;
        $this->score = $s;
    }
}

class AffineTrace {
    public $max;
    public $direc;
    public $lgap;
    public $rgap;
    
    public function __construct(int $d, float $m, ?float $l, ?float $r) {
        $this->direc = $d;
        $this->max = $m;
        $this->lgap = $l;
        $this->rgap = $r;
    }
}

class AffineAlign {
    private $gap_ex = -0.25;
    private $gap_open = -3;
    private $match = 1;
    private $mismatch = -1;
    const UP = 1;
    const LEFT = 2;
    const UL = 4;

    public function setGap($o, $e) {
        $this->gap_open = $o;
        $this->gap_ex = $e;
    }
    
    public function setMatch($ma, $mi) {
        $this->match = $ma;
        $this->mismatch = $mi;
    }
    
    private function split(string $str): array {
        return preg_split('//u',$str,-1,PREG_SPLIT_NO_EMPTY);
    }

    private function init(array $s1arr, array $s2arr) : array {
        $s1len = count($s1arr);
        $s2len = count($s2arr);
        $mat = array();

        for($i=-1;$i<$s1len;$i++) {
            for($j=-1;$j<$s2len;$j++) {
                if($i === -1 || $j === -1) {
                    if($i === $j)
                        $mat[-1][-1] = new AffineTrace(self::UP,0,0,0);
                    else if($i === -1) {
                        // no gap opening penalty at the beginning
                        $score = /*$this->gap_open + */$this->gap_ex * ($j+1);
                        $mat[$i][$j] = new AffineTrace(self::UP,$score,null,$score);
                    }
                    else {
                        $score = /*$this->gap_open + */$this->gap_ex * ($i+1);
                        $mat[$i][$j] = new AffineTrace(self::LEFT,$score,$score,null);
                    }
                }
                else {
                    $diag = $mat[$i-1][$j-1]->max + (
                        ($s1arr[$i] === $s2arr[$j]) ? 
                            $this->match : $this->mismatch);

                    // no gap opening penalty at the end
                    $lastcol = ($i === $s1len - 1);
                    $bottomrow = ($j === $s2len - 1);

                    $lgapopen = $mat[$i-1][$j]->max;
                    if(!$bottomrow) $lgapopen += $this->gap_open;

                    $prevlgap = $mat[$i-1][$j]->lgap;
                    $lgapmax = ($prevlgap !== null) ?
                        max($lgapopen,$prevlgap) : $lgapopen;
                    
                    $lgapmax += $this->gap_ex;

                    $rgapopen = $mat[$i][$j-1]->max;
                    if(!$lastcol) $rgapopen += $this->gap_open;

                    $prevrgap = $mat[$i][$j-1]->rgap;
                    $rgapmax = ($prevrgap !== null) ?
                        max($rgapopen,$prevrgap) : $rgapopen;
                    
                    $rgapmax += $this->gap_ex;

                    $max = max($diag,$lgapmax,$rgapmax);
                    
                    switch($max) {
                        case $lgapmax:
                            $dir = self::LEFT;
                            break;
                        case $rgapmax:
                            $dir = self::UP;
                            break;
                        default:
                            $dir = self::UL;
                            break;
                    }

                    $mat[$i][$j] = new AffineTrace($dir,$max,$lgapmax,$rgapmax);
                }
            }
        }
        
        return $mat;
    }

    private function traceback(array $s1arr, array $s2arr, array $mat) : array {
        $chars = [[],[]];
        $i = count($s1arr)-1;
        $j = count($s2arr)-1;

        while($i > -1 || $j > -1) {
            if($i === -1) {
                array_unshift($chars[0],'');
                array_unshift($chars[1],$s2arr[$j]);
                $j--;
                continue;
            }
            else if($j === -1) {
                array_unshift($chars[0],$s1arr[$i]);
                array_unshift($chars[1],'');
                $i--;
                continue;
            };

            switch ($mat[$i][$j]->direc) {
                case self::UP:
                    array_unshift($chars[0],'');
                    array_unshift($chars[1],$s2arr[$j]);
                    $j--;
                    break;
                case self::LEFT:
                    array_unshift($chars[0],$s1arr[$i]);
                    array_unshift($chars[1],'');
                    $i--;
                    break;
                case self::UL:
                    array_unshift($chars[0],$s1arr[$i]);
                    array_unshift($chars[1],$s2arr[$j]);
                    $i--;
                    $j--;
                    break;
                default: break;
            }
        }
        return $chars;
    }

    public function align(string $s1, string $s2): array {
        $s1arr = $this->split($s1);
        $s2arr = $this->split($s2);

        $direc = $this->init($s1arr, $s2arr);
        return $this->traceback($s1arr,$s2arr,$direc);
    }

    public function jiggle(string $s1, string $s2): ?array {
        if(substr($s1,-1) !== ' ') $s1 .= ' ';
        if(substr($s2,-1) !== ' ') $s2 .= ' ';
        $strs = $this->align($s1,$s2);
        $len = count($strs[0]);
        $breaks = [];
        $ret = [];
        $str1started = false;
        $str2started = false;
        for($n=0;$n<$len-1;$n++) { // ignore last character
            $char1 = $strs[0][$n];
            $char2 = $strs[1][$n];
            if($char1 !=='') $str1started = true;
            if($char2 !== '') $str2started = true;

            if($n === 0) continue; // ignore first character

            if($char1 === ' ' && (!$str2started || $char2 === ' '))
                $breaks[] = $n;
            else if($char2 === ' ' && (!$str1started || $char1 === ' '))
                $breaks[] = $n;
        }
        if(count($breaks) > 0) {
            $start = 0;
            $breaks[] = $len-1;
            $adds = null;
            foreach($breaks as $break) {
                $str1 = implode(array_slice($strs[0],$start,$break + 1 - $start));
                $str2 = implode(array_slice($strs[1],$start,$break + 1 - $start));
                if($str1 === $str2) {
                    if($adds !== null) {
                        $ret[] = [
                            "maintext" => '',
                            "vartext" => $adds,
                            "sharedtext" => ''
                        ];
                        $adds = null;
                    }
                    $ret[] = [
                        "maintext" => '',
                        "vartext" => '',
                        "sharedtext" => $str1
                    ];
                }
                else {
                    if($str1 === '') {
                        $adds ? $adds .= $str2 : $adds = $str2;
                    }
                    else {
                        if($adds) {
                            $ret[] = [
                                "maintext" => '',
                                "vartext" => $adds,
                                "sharedtext" => ''
                            ];
                            $adds = null;
                        }
                        $ret[] = [
                            "maintext" => $str1, 
                            "vartext" => $str2,
                            "sharedtext" => ''
                        ];
                    }
                }
                $start = $break + 1;
            } // end foreach
            // catch any leftovers
            if($adds) {
                $ret[] = [
                    "maintext" => '',
                    "vartext" => $adds,
                    "sharedtext" => ''
                ];
                $adds = null;
            }
            return $ret;
        } // end if(count($breaks) > 0)
        return null;
    }
}
/*
ini_set('display_errors','On');
error_reporting(E_ALL);

$aa = new AffineAlign();
$strs = $aa->jiggle("pṛthivy āpas tejo vāyur iti tattvāni tatsamudāye śarīrendriyaviṣaya","śaktirindriyaviṣaya");
print_r($strs);
*/
?>
