<?php
// (?!\S) is used to mean a word boundary and (?=\S) is used to mean not a word boundary. \P{L} does not seem to catch end-of-line.

return array(
//        "visarga aḥ + vowel" => array('aḥ(?=\s+[āiīeuūo])', "replace_with" => 'a'), 
// this causes variants to be reported when there are none, i.e. 
// "vacanaḥ sā..." vs "vacanaḥ ā..." becomes "vacano" and "vacana"
        "visarga āḥ + voiced syllable" => array('āḥ(?=\s+[āiīeuūogjḍḍbnmyrlv])', "replace_with" => 'ā'),
        "visarga aḥ variants" => array('a[ḥsśrṣ](?!\S)', "replace_with" => 'o'),
        "other visarga variants" => array('[rsśṣ](?!\S)', "replace_with" => 'ḥ'),
        "internal visarga variants" => array('(?<=u)ḥ(?=k)', "replace_with" => 'ṣ'),
//      "nasals" => array('[ñṅṇ](?![\s\Z])','m(?=[db])','n(?=[tdn])', "replace_with" => 'ṃ'),
//       "nasals" => array('m(?=[pbd])','n(?=[tdn])','ṇ(?=[ṭḍ])','ñ(?=[cj])','ṅ(?=[kg])', "replace_with" => 'ṃ'),
//       "nasal variants" => array('ṅ(?=\s*[kg])|ñ(?=\s*[cj])|ṇ(?=\s*[ṭḍ])|m(?=\s*[pbd])|n(?=\s*[tdnj])', "replace_with" => 'ṃ'),
       // n(?=\s*j) takes care of (jhātkārān jaitrajanye|jhātkārāñ jaitrajanye)
        "final nasal variants" => array('(?:(?<=(?<![āī][nṇ])ā)[ñn]|ṃ[lś]|nn)(?!\S)', "replace_with" => 'n'),
        "internal nasal variants" => array('[mnñṇṅ](?=[pbdtnṭḍcjkg])', "replace_with" => 'ṃ'),
          "final au/āv" => array('āv(?!\S)', "replace_with" => 'au'),
        "final anusvāra" => array('ṃ?[mṅ](?!\S)', 'n(?=\s+[tdn])', 'ñ(?=\s+[jc])',"replace_with" => 'ṃ'),
//        "final anusvāra" => array('ṃ?[mṅ](?!\S)', 'n(?=\s+t[uūv])', "replace_with" => 'ṃ'),
        // final ṅ can be written as ṃ in "ohāṅ gatau"
        // kin tu/ kiṃ tu/ kim tu
//        "final ṃl" => array('ṃl(?!\Sl)', "replace_with" => 'n'),
//        "kcch/kś" => 'k(?:[ c]ch| ?ś)',
        "kcch/kś" => array('k\s*(?:ś|c?ch)', "replace_with" => 'kś'),
//      "cch/ch" => array('(?<!\s)c(?:c| c|)h(?=\S)','(?<!\s)t ś(?=\S)'),
        "cch/ch" => array('c\s*(?:ch|ś)', 't\s+ś', "replace_with" => 'ch'),
//        "ddh/dh" => array('ddh', "replace_with" => 'dh'),
//        "jjh/jh" => array('jjh', "replace_with" => 'jh'),
//        "t ś/c ch" => array('t(\s+)ś', 'c(\s+)ch', "replace_with" => '\1ch'),
        "final t + voiced syllable" => array('d(?=\s+[aāiīeuūogdbyrv])', "replace_with" => 't'),
        "final t + n/m" => array('t(?=\s[nm])', "replace_with" => 'n'),
        "final t + c/j" => array('j(?=\s+j)|c(?=\s+c)', "replace_with" => 't'),
        // also t + ḍ = ḍḍ, t + ṭ = ṭṭ
      
        // add "iva" and "eva" to this rule?
        "sya,tra,ma before iti" => array('(?<=sy|tr|m)a\s+i(?=t[iīy])', "replace_with" => 'e'),
        "a a/ā" => array('a\s+a', "replace_with" => 'ā'),
        "-ena,-sya + u-" => array('(?<=en|sy)a u', "replace_with" => "o"),
// most of these iti rules can be applied only to printed editions
        //"i i/īti" => array('i\s+i(?=t[iīy])', "replace_with" => 'ī'),
        "i i/ī" => array('i\s+i', "replace_with" => 'ī'),
        "ā + iti" => array('ā\s+i(?=t[iīy])', "replace_with" => 'e'),
        "e/a + i" => array('e(?=\s+i)',"replace_with" => 'a'),
        "i/y + vowel" => array('y(?=\s+[āauūeo])', "replace_with" => "i"),
        // replacing i with y fails in some cases,
        // as in "abhyupaiti | etad"
        // or in "ity bhāvaḥ" compared to "iti āśaṇkyaḥ"
        
//        "geminated j" => array('(?<=[rṛ])jj', "replace_with" => "j"),
//        "geminated ṭ" => array('ṭṭ', "replace_with" => 'ṭ'),
        "geminated t" => array('(?<=[rṛi]|pa)tt','tt(?=[rv]\S)', "replace_with" =>"t"),
//        "geminated d" => array('(?<=r)dd', "replace_with" => "d"),
//        "geminated y" => array('(?<=[rṛ])yy', "replace_with" => "y"),
//        "geminated v" => array('(?<=[rṛ]\s*)vv', "replace_with" => "v"),
//        "other geminated consonants" => array('(?<=[rṛ\s])([jṭṇdnmyv])\1', '([jṭd])\1(?=h)', "replace_with" => '\1'),
        "geminated consonants after r" => array( '(?<=[rṛ\s])([gjṭṇdnmyv])\1', "replace_with" => '\1'),
        "other geminated consonants" => array('([jṭd])\1(?=h)', "replace_with" => '\1'),

);
?>
