<?php
mb_internal_encoding('UCS-2LE');
function binarySearch($str1,$str2) {
        if($str1[0] != $str2[0]) return 0;
        $arr2 = preg_split('//u',$str2,-1,PREG_SPLIT_NO_EMPTY);
        $arr1 = preg_split('//u',$str1,-1,PREG_SPLIT_NO_EMPTY);

        $pointermin = 0;
        $pointermax = min(count($arr1),count($arr2));
        $pointermid = $pointermax;
        $pointerstart = 0;


        while ($pointermin < $pointermid) {
            if (array_slice($arr1,$pointerstart,$pointermid-$pointerstart) ==                     array_slice($arr2,$pointerstart,$pointermid-$pointerstart)) {
                $pointermin = $pointermid;
                $pointerstart = $pointermin;
            } else {
                $pointermax = $pointermid;
            }
            $pointermid = (int)(($pointermax - $pointermin) / 2 + $pointermin);
        }
        return $pointermid;
}

function binarySearch2($str1,$str2) {
        if($str1[0] != $str2[0]) return 0;
        $pointermin = 0;
        //$pointermax = min(count($arr1), count($arr2));
        $pointermax = min(mb_strlen($str1),mb_strlen($str2));
        $pointermid = $pointermax;
        $pointerstart = 0;


        while ($pointermin < $pointermid) {
            if (mb_substr($str1,$pointerstart,$pointermid-$pointerstart) ==                     mb_substr($str2,$pointerstart,$pointermid-$pointerstart)) {
                $pointermin = $pointermid;
                $pointerstart = $pointermin;
            } else {
                $pointermax = $pointermid;
            }
            $pointermid = (int)(($pointermax - $pointermin) / 2 + $pointermin);
        }
        return $pointermid;
}

function linearSearch($str1,$str2) {
        if($str1[0] != $str2[0]) return 0;
        $arr2 = preg_split('//u',$str2,-1,PREG_SPLIT_NO_EMPTY);
        $arr1 = preg_split('//u',$str1,-1,PREG_SPLIT_NO_EMPTY);
        $pre_end = 0;
        foreach($arr1 as $r => $pre1) {
            $pre_end = $r;
            $pre2 = isset($arr2[$r]) ? $arr2[$r] : false;
            if($pre1 != $pre2) {
                break;
            }
        }
        return $pre_end;
}

function linearSearch2($str1,$str2) {
        if($str1[0] != $str2[0]) return 0;
        $pre_end = 0;
        $len1 = mb_strlen($str1);
        $len2 = mb_strlen($str2);
        $max = min($len1,$len2);

        for($i=0;$i<=$max;$i++) {
            $pre_end = $i;
            if($pre_end === $max) break;
            if(mb_substr($str1,$i,1) != mb_substr($str2,$i,1)) 
                break;
        }
        
        return $pre_end;
}

function linearSearch3($str1,$str2) {
        if($str1[0] != $str2[0]) return 0;
        $arr2 = preg_split('//u',$str2,-1,PREG_SPLIT_NO_EMPTY);
        $arr1 = preg_split('//u',$str1,-1,PREG_SPLIT_NO_EMPTY);
        $pre_end = 0;
        $max = min(count($arr1),count($arr2));

        for($r=0;$r<$max;$r++) {
            if($arr1[$r] == $arr2[$r]) {
                if($r == $max-1) $pre_end = $r+1;
            }
            else {
                $pre_end = $r;
                break;
            }
        }
        return $pre_end;
}

function randStr($length = 100) {
        return mb_substr(str_shuffle(str_repeat($x=' aābcdḍeghiījklṃmnṇṅñopṛrsśṣtṭuūvy', ceil($length/strlen($x)) )),1,$length);
}

$strs1 = [];
$strstrs1 = [];
$strs2 = [];
$strstrs2 = [];

for($i=0;$i<1000;$i++) {
    $str = randStr();
    $strstrs1[] = $str;
//    $strs1[] = preg_split('//u',$str,-1,PREG_SPLIT_NO_EMPTY);
    $str = randStr();
    $strstrs2[] = $str;
//    $strs2[] = preg_split('//u',$str,-1,PREG_SPLIT_NO_EMPTY);
}

$results1 = array();
$results2 = array();
$results3 = array();
$results4 = array();
$results5 = array();

$start = microtime(true);
for($i=0;$i<1000;++$i) {
$results1[] = binarySearch($strstrs1[$i],$strstrs2[$i]);
}
echo "Binary Search: ".(microtime(true) - $start)."\n";

$start = microtime(true);
for($i=0;$i<1000;++$i) {
$results2[] = binarySearch2($strstrs1[$i],$strstrs2[$i]);
}
echo "Multibyte Binary Search: ".(microtime(true) - $start). "\n";

$start = microtime(true);
for($i=0;$i<1000;++$i) {
$results3[] = linearSearch($strstrs1[$i],$strstrs2[$i]);
}
echo "Linear Search: ".(microtime(true) - $start). "\n";

$start = microtime(true);
for($i=0;$i<1000;++$i) {
$results4[] = linearSearch2($strstrs1[$i],$strstrs2[$i]);
}
echo "Multibyte Linear Search: ".(microtime(true) - $start). "\n";

$start = microtime(true);
for($i=0;$i<1000;++$i) {
$results5[] = linearSearch($strstrs1[$i],$strstrs2[$i]);
}
echo "New Linear Search: ".(microtime(true) - $start). "\n";


if(empty(array_diff($results1,$results2))) echo "OK\n";
if(empty(array_diff($results1,$results3))) echo "OK\n";
if(empty(array_diff($results1,$results4))) echo "OK\n";
if(empty(array_diff($results1,$results5))) echo "OK\n";
?>
