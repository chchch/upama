<?php
function pregSplitTest($str) {
    $arr = preg_split('//u',$str,-1,PREG_SPLIT_NO_EMPTY);
    return count($arr);

}

function randStr($length = 30) {
    $chars = preg_split('//u','aābcdḍeghiījklṃmnṇṅñopṛrsśṣtṭuūvy',null,PREG_SPLIT_NO_EMPTY);
    $charno = count($chars);
    $randstr = '';
    for($i=0;$i<$length;$i++) {
        $randstr .= $chars[rand(0,$charno-1)];
    }
    return $randstr;
}
$strs = array();

for($i=0;$i<1000;$i++) {
    $strs[] = randStr();
}

$start = microtime(true);
for($i=0;$i<1000;++$i) {
    pregSplitTest($strs[$i]);
}
echo "preg_split: ".(microtime(true) - $start)."\n";

mb_internal_encoding('UCS-2LE');
$start = microtime(true);
for($i=0;$i<1000;++$i) {
    $newstr = iconv('UTF-8','UCS-2LE',$strs[$i]);
    mb_strlen($newstr);
}
echo "mb_strlen (UCS-2LE): ".(microtime(true) - $start). "\n";

mb_internal_encoding('UTF-8');
$start = microtime(true);
for($i=0;$i<1000;++$i) {
    mb_strlen($strs[$i]);
}
echo "mb_strlen (UTF-8): ".(microtime(true) - $start). "\n";

$start = microtime(true);
for($i=0;$i<1000;++$i) {
    mb_substr($strs[$i],12,20);
}
echo "mb_substr (UTF-8): ".(microtime(true) - $start). "\n";

$start = microtime(true);
for($i=0;$i<1000;++$i) {
    strlen($strs[$i]);
}
echo "strlen: ".(microtime(true) - $start). "\n";

?>
