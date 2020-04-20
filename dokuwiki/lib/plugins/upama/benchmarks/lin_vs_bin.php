<?php

function binarySearch($arr1,$arr2,$len1,$len2) {
        $pointermin = 0;
        //$pointermax = min(count($arr1), count($arr2));
        $pointermax = min($len1,$len2);
        $pointermid = $pointermax;
        $pointerstart = 0;

        if($arr1[0] != $arr2[0]) return 0;

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
        $pointermin = 0;
        //$pointermax = min(count($arr1), count($arr2));
        $pointermax = min(count($str1),count($str2));
        $pointermid = $pointermax;
        $pointerstart = 0;

        if(mb_substr($str1,0,1) != mb_substr($str2,0,1)) return 0;

        while ($pointermin < $pointermid) {
            if (substr($str1,$pointerstart,$pointermid-$pointerstart) ==                     substr($str2,$pointerstart,$pointermid-$pointerstart)) {
                $pointermin = $pointermid;
                $pointerstart = $pointermin;
            } else {
                $pointermax = $pointermid;
            }
            $pointermid = (int)(($pointermax - $pointermin) / 2 + $pointermin);
        }
        return $pointermid;
}

function linearSearch($arr1,$arr2) {
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
function randStr($length = 30) {
        return mb_substr(str_shuffle(str_repeat($x='aābcdḍeghiījklṃmnṇṅñopṛrsśṣtṭuūvy', ceil($length/strlen($x)) )),1,$length);
}

$strs1 = [];
$strstrs1 = [];
$strs2 = [];
$strstrs2 = [];

for($i=0;$i<1000;$i++) {
    $str = randStr();
    $strstrs1[] = $str;
    $strs1[] = preg_split('//u',$str,-1,PREG_SPLIT_NO_EMPTY);
    $str = randStr();
    $strstrs2[] = $str;
    $strs2[] = preg_split('//u',$str,-1,PREG_SPLIT_NO_EMPTY);
}

$start = microtime(true);
for($i=0;$i<1000;++$i) {
binarySearch($strs1[$i],$strs2[$i],30,30);
}
echo "Binary Search: ".(microtime(true) - $start)."\n";

$start = microtime(true);
for($i=0;$i<1000;++$i) {
linearSearch($strs1[$i],$strs2[$i]);
}
echo "Linear Search: ".(microtime(true) - $start). "\n";

?>
