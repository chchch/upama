<?php
        $text1 = "svabhāvo";
        $text2 = "svabhāvāpy";
        $arr1 = preg_split('//u',$text1,-1,PREG_SPLIT_NO_EMPTY);
        $arr2 = preg_split('//u',$text2,-1,PREG_SPLIT_NO_EMPTY);
        $len1 = count($arr1);
        $len2 = count($arr2);

        $pointermin = 0;
        $pointermax = min($len1, $len2);
        $pointermid = $pointermax;
        $pointerstart = 0;
        while ($pointermin < $pointermid) {
                echo ("pointerstart: $pointerstart pointermin: $pointermin pointermax: $pointermax pointermid: $pointermid\n");
            if (array_slice($arr1,$pointerstart,$pointermid-$pointerstart) ==
                   array_slice($arr2,$pointerstart,$pointermid-$pointerstart)) {
                echo ("equal: ". implode('',array_slice($arr1,$pointerstart,$pointermid))."\n");
                $pointermin = $pointermid;
                $pointerstart = $pointermin-1;
            } else {
                echo ("unequal: ". implode('',array_slice($arr1,$pointerstart,$pointermid))." & ".implode('',array_slice($arr2,$pointerstart,$pointermid))."\n");
                $pointermax = $pointermid;
            }
            $pointermid = floor(($pointermax - $pointermin) / 2 + $pointermin);
                echo ("pointerstart: $pointerstart pointermin: $pointermin pointermax: $pointermax pointermid: $pointermid\n\n");
        }
        echo "final: ".implode('',array_slice($arr1,0,$pointermid))."\n";
?>
