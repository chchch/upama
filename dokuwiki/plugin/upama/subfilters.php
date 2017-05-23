<?php
// (?!\S) is used to mean a word boundary and (?=\S) is used to mean not a word boundary. \P{L} does not seem to catch end-of-line.

return array(
//        "visarga aḥ + vowel" => array('aḥ(?=\s+[āiīeuūo])', "replace_with" => 'a'), 
// this causes variants to be reported when there are none, i.e. 
// "vacanaḥ sā..." vs "vacanaḥ ā..." becomes "vacano" and "vacana"
    ["name" => "visarga āḥ + voiced syllable",
     "find" => 'āḥ?(?=\s+[āiīeuūogjḍḍbnmyrlv])',
     "replace" => 'ā'],
        
    ["name" => "visarga aḥ variants",
     "find" => 'a[ḥsśrṣ][sś]?(?!\S)',
     "replace" => 'o'],
    
    ["name" => "other visarga variants",
     "find" => '[rsśṣ](?!\S)',
     "replace" => 'ḥ'],

    ["name" => "internal visarga variants",
     "find" => array('(?<=u)ṣ(?=k)','s(?=s)'),
     "replace" => 'ḥ'],
//      "nasals" => array('[ñṅṇ](?![\s\Z])','m(?=[db])','n(?=[tdn])', "replace_with" => 'ṃ'),
//       "nasals" => array('m(?=[pbd])','n(?=[tdn])','ṇ(?=[ṭḍ])','ñ(?=[cj])','ṅ(?=[kg])', "replace_with" => 'ṃ'),
//       "nasal variants" => array('ṅ(?=\s*[kg])|ñ(?=\s*[cj])|ṇ(?=\s*[ṭḍ])|m(?=\s*[pbd])|n(?=\s*[tdnj])', "replace_with" => 'ṃ'),
       // n(?=\s*j) takes care of (jhātkārān jaitrajanye|jhātkārāñ jaitrajanye)
    
    ["name" => "final nasal variants",
    "find" => '(?:ṃ[lś]|nn)(?!\S)', 
    "replace" => 'n'],

    ["name" => "internal nasal variants",
    "find" => '[mnñṇṅ](?=[pbdtnṭḍcjkg])', 
    "replace" => 'ṃ'],

    ["name" => "final au/āv",
     "find" => 'āv(?!\S)',
     "replace" => 'au'],

    ["name" => "final anusvāra variants",
     "exclude" => "//textLang[@mainLang='sa-Mlym']",
     "find" => ['ṃ?[mṅ](?!\S)', '(?<=k[ai])n(?=\s+[t])', 'ñ(?=\s+[jc])'],
     "replace" => 'ṃ'],
//        "final anusvāra" => array('ṃ?[mṅ](?!\S)', 'n(?=\s+t[uūv])', "replace_with" => 'ṃ'),
        // final ṅ can be written as ṃ in "ohāṅ gatau"
        // kin tu/ kiṃ tu/ kim tu
//        "final ṃl" => array('ṃl(?!\Sl)', "replace_with" => 'n'),
//        "kcch/kś" => 'k(?:[ c]ch| ?ś)',
    ["name" => "final anusvāra variants (malayālam)",
     "include" => "//textLang[@mainLang='sa-Mlym']",
     "find" => ['n(?=\s[tdn])','ṃ?[mṅ](?!\S)','ñ(?=\s[jc])'],
     "replace" => 'ṃ'],
     
     ["name" => "kcch/kś",
     "find" => 'k\s*(?:ś|c?ch)',
     "replace" => 'kś'],
//      "cch/ch" => array('(?<!\s)c(?:c| c|)h(?=\S)','(?<!\s)t ś(?=\S)'),

    ["name" => "cch/ch",
     "find" => ['c\s*(?:ch|ś)', 't\s+ś'],
     "replace" => 'ch'],
//        "ddh/dh" => array('ddh', "replace_with" => 'dh'),
//        "jjh/jh" => array('jjh', "replace_with" => 'jh'),
//        "t ś/c ch" => array('t(\s+)ś', 'c(\s+)ch', "replace_with" => '\1ch'),
    ["name" => "final t + voiced syllable",
     "find" => 'd(?=\s+[aāiīeuūogdbyrv])',
     "replace" => 't'],
        
    ["name" => "final t + n/m",
     "find" => 't(?=\s+[nm])',
     "replace" => 'n'],
        
    ["name" => "final t + c/j",
     "find" => 'j(?=\s+j)|c(?=\s+c)',
     "replace" => 't'],
        // also t + ḍ = ḍḍ, t + ṭ = ṭṭ
      
        // add "iva" and "eva" to this rule?
    ["name" => "sya,tra,ma before iti",
     "find" => '(?<=sy|tr|m)a\s+i(?=t[iīy])',
     "replace" => 'e'],

    ["name" => "a a/ā",
     "find" => 'a\s+a',
     "replace" => 'ā'],

    ["name" => "-ena,-sya + u-",
     "find" => '(?<=en|sy)a u',
     "replace" => "o"],

// most of these iti rules can be applied only to printed editions
        //"i i/īti" => array('i\s+i(?=t[iīy])', "replace_with" => 'ī'),
        
    ["name" => "i i/ī",
     "find" => 'i\s+i',
     "replace" => 'ī'],
    
    ["name" => "ā + iti",
     "find" => 'ā\s+i(?=t[iīy])',
     "replace" => 'e'],
    
    ["name" => "e/a + i",
     "find" => 'e(?=\s+i)',
     "replace" => 'a'],
        
    ["name" => "i/y + vowel",
     "find" => 'y(?=\s+[āauūeo])',
     "replace" => "i"],
        // replacing i with y fails in some cases,
        // as in "abhyupaiti | etad"
        // or in "ity bhāvaḥ" compared to "iti āśaṇkyaḥ"
        
//        "geminated j" => array('(?<=[rṛ])jj', "replace_with" => "j"),
//        "geminated ṭ" => array('ṭṭ', "replace_with" => 'ṭ'),
    ["name" => "geminated t",
     "find" => ['(?<=[rṛi]|pa)tt','tt(?=[rv]\S)'],
     "replace" => "t"],
//        "geminated d" => array('(?<=r)dd', "replace_with" => "d"),
//        "geminated y" => array('(?<=[rṛ])yy', "replace_with" => "y"),
//        "geminated v" => array('(?<=[rṛ]\s*)vv', "replace_with" => "v"),
//        "other geminated consonants" => array('(?<=[rṛ\s])([jṭṇdnmyv])\1', '([jṭd])\1(?=h)', "replace_with" => '\1'),
    ["name" => "geminated consonants after r",
     "find" => '(?<=[rṛ\s])([gjṭṇdnmyv])\1', 
     "replace" => '\1'],

    ["name" => "other geminated consonants",
     "find" => '([jṭd])\1(?=h)',
     "replace" => '\1'],

);
?>
