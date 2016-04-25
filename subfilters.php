<?php
// (?!\S) is used to mean a word boundary and (?=\S) is used to mean not a word boundary. \P{L} does not seem to catch end-of-line.

return array(
        "final d/t" => array('d(?!\S)', "replace_with" => 't'),
        "visarga aḥ" => array('a[ḥsśrṣ](?!\S)', "replace_with" => 'o'),
        "other visargas" => array('[rsśṣ](?!\S)', "replace_with" => 'ḥ'),
        "final au/āv" => array('āv(?!\S)', "replace_with" => 'au'),
        "final anusvāra" => array('ṃ?m(?!\S)', "replace_with" => 'ṃ'),
//        "kcch/kś" => 'k(?:[ c]ch| ?ś)',
        "kcch/kś" => array('k[(\s+?)c]ch', "replace_with" => 'k\1ś'),
//      "cch/ch" => array('(?<!\s)c(?:c| c|)h(?=\S)','(?<!\s)t ś(?=\S)'),
        "cch/ch" => array('c\s+?ch','t\s+ś', "replace_with" => 'ch'),
//      "nasals" => array('[ñṅṇ](?![\s\Z])','m(?=[db])','n(?=[tdn])', "replace_with" => 'ṃ'),
//       "nasals" => array('m(?=[pbd])','n(?=[tdn])','ṇ(?=[ṭḍ])','ñ(?=[cj])','ṅ(?=[kg])', "replace_with" => 'ṃ'),
//       "nasals" => array('ṅ(?=[kg])|ñ(?=[cj])|ṇ(?=[ṭḍ])|m(?=[pbd])|n(?=[tdn])', "replace_with" => 'ṃ'),
        "nasals" => array('[mnñṇṅ](?=[pbdtnṭḍcjkg])', "replace_with" => 'ṃ'),
        "ddh/dh" => array('ddh', "replace_with" => 'dh'),
        
        // add "iva" and "eva" to this rule?
        "sya,tra,ma before iti" => array('(?<=sy|tr|m)a\s+i(?=t[iīy])', "replace_with" => 'e'),

// most of these iti rules can be applied only to printed editions
        "e/a + iti" => array('e(?=\s+it[iīy])',"replace_with" => 'a'),
        "i iti/īti" => array('i\s+i(?=t[iīy])', "replace_with" => 'ī'),
        "i/y + vowel" => array('y(?=\s+[āauūeo])', "replace_with" => "i"),
        // replacing i with y fails in some cases,
        // as in "abhyupaiti | etad"
        // or in "ity bhāvaḥ" compared to "iti āśaṇkyaḥ"
        
        "tt/t" => array('(?<=[rṛi]|pa)tt','tt(?=v\S)', "replace_with" =>"t"),
        );
?>
