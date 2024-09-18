<?php
// (?!\S) is used to mean a word boundary and (?=\S) is used to mean not a word boundary. \P{L} does not seem to catch end-of-line.

return array(
        
//        "geminated j" => array('(?<=[rṛ])jj', "replace_with" => "j"),
//        "geminated ṭ" => array('ṭṭ', "replace_with" => 'ṭ'),
    ["name" => "geminated t",
     "find" => ['(?<=[rṛr̥i]|pa)tt','tt(?=[rvy]\S)'],
     "replace" => "t"],
//        "geminated d" => array('(?<=r)dd', "replace_with" => "d"),
//        "geminated y" => array('(?<=[rṛ])yy', "replace_with" => "y"),
//        "geminated v" => array('(?<=[rṛ]\s*)vv', "replace_with" => "v"),
//        "other geminated consonants" => array('(?<=[rṛ\s])([jṭṇdnmyv])\1', '([jṭd])\1(?=h)', "replace_with" => '\1'),
    ["name" => "geminated consonants after r", // A 8.4.46; include tripled consonants, as in ryyya
     "find" => '(?<=[rṛr̥]|[rṛr̥]\s)([kgcjṭḍṇdnpbmyvl])\1{1,2}', 
     //"find" => '(?<=[rṛṙ\s])([gjṭṇdnbmyv])\1', 
     "replace" => '\1'],

    ["name" => "geminated m after h", // A 8.4.46; include hnn in apahnnute?
     "find" => ['ṃhm','hmm'],
     "replace" => 'hm'],

    ["name" => "geminated aspirated consonants", // excluding cch
     "find" => '([kgjṭḍtdpb])\1(?=h)',
     "replace" => '\1',
     "first" => true],

    ["name" => "ṭh written as ṭ (some scripts)",
     "include" => "//scriptNote[@xml:id='script-ṭha-ṭa']",
     "find" => 'ṭh',
     "replace" => 'ṭ'],

    ["name" => "final nasal variants",
    "find" => '(?:ṃ[lśs]|nn)(?!\S)', 
    "replace" => 'n'],

    ["name" => "internal nasal variants",
    "find" => '[mm̐nñṇṅ](?=[pbmdtnṭḍcjkg])', 
    "replace" => 'ṃ'],

//        "visarga aḥ + vowel" => array('aḥ(?=\s+[āiīeuūo])', "replace_with" => 'a'), 
// this causes variants to be reported when there are none, i.e. 
// "vacanaḥ vā..." vs "vacanaḥ ā..." becomes "vacano" and "vacana"
    ["name" => "visarga āḥ variants",
     "find" => 'āḥ(?=\s[aāiīeêuūogjḍdbnmyrlvh])',
     "replace" => 'ā'],
/*        
    ["name" => "visarga aḥ variants",
     "find" => 'a[ḥsśrṙṣ][sś]?(?!\S)',
     //"find" => 'a[ḥrṙ](?=\s+[aāiīeuūogjḍdbnmrylv])',
     "replace" => 'o'],
*/
    ["name" => "visarga aḥ before voiced consonants",
     "find" => '(?<!\sbh)(?:a[ḥr]|[o])(?=\s[gjḍdbnmrylvh])', // ignore bho
     "replace" => 'aḥ'],
    ["name" => "visarga aḥ before vowels", // other than short a
     "find" => 'aḥ(?=\s[āiīeuūoṛ])',
     "replace" => 'a'],
/*
    ["name" => "visarga s variants",
     "find" => 'ḥ?s(?=\s+t)',
     "replace" => 'ḥ'],
    ["name" => "visarga ś variants",
     "find" => 'ḥ?ś(?=\s+c)',
     "replace" => 'ḥ'],
*/
    ["name" => "other visarga variants",
     "find" => 'ḥ?[rsśṣ](?!\S)',
     //"find" => '[rṙṣ](?=\s+[aāiīeuūogjḍdbnmyrlv])',
     "replace" => 'ḥ'],

    ["name" => "internal visarga variants",
     "find" => array('(?<=u)ṣ','ṣ(?=k)','s(?=s)'),
     "replace" => 'ḥ'],
//      "nasals" => array('[ñṅṇ](?![\s\Z])','m(?=[db])','n(?=[tdn])', "replace_with" => 'ṃ'),
//       "nasals" => array('m(?=[pbd])','n(?=[tdn])','ṇ(?=[ṭḍ])','ñ(?=[cj])','ṅ(?=[kg])', "replace_with" => 'ṃ'),
//       "nasal variants" => array('ṅ(?=\s*[kg])|ñ(?=\s*[cj])|ṇ(?=\s*[ṭḍ])|m(?=\s*[pbd])|n(?=\s*[tdnj])', "replace_with" => 'ṃ'),
       // n(?=\s*j) takes care of (jhātkārān jaitrajanye|jhātkārāñ jaitrajanye)
    
    ["name" => "Vedic visarga variants",
     "find" => '[ẖḫ]',
     "replace" => 'ḥ',
     "first" => true
    ],
    ["name" => "final au/āv",
     "find" => 'āv(?!\S)',
     "replace" => 'au'],

    ["name" => "final anusvāra variants", // A 8.4.59
     "exclude" => "//textLang[@mainLang='sa-Mlym']",
     "find" => ['ṃ?[mm̐ṅ](?!\S)', '(?<=k[ai])n(?=\st)', 'ñ(?=\s[jc])'],
     "replace" => 'ṃ'],
//        "final anusvāra" => array('ṃ?[mṅ](?!\S)', 'n(?=\s+t[uūv])', "replace_with" => 'ṃ'),
        // final ṅ can be written as ṃ in "ohāṅ gatau"
        // kin tu/ kiṃ tu/ kim tu
//        "final ṃl" => array('ṃl(?!\Sl)', "replace_with" => 'n'),
//        "kcch/kś" => 'k(?:[ c]ch| ?ś)',
    ["name" => "final anusvāra variants (Malayālam, etc.)",
     "include" => "//textLang[@mainLang='sa-Mlym']|//scriptNote[@xml:id='savarna-nasals']",
     "find" => ['n(?=\s[tdn])','ṃ?[mm̐ṅ](?!\S)','ñ(?=\s[jc])'],
     "replace" => 'ṃ'],

     ["name" => "sth written as sch (some scripts)",
      "include" => "//scriptNote[@xml:id='script-stha-scha']",
      "find" => 'sch',
      "replace" => 'sth',
      "first" => true,
     ],
     
     ["name" => "kcch/kś", // A 8.4.63
     "find" => 'k\s*(?:ś|c?ch)',
     "replace" => 'kś',
     "first" => true
     ],
//      "cch/ch" => array('(?<!\s)c(?:c| c|)h(?=\S)','(?<!\s)t ś(?=\S)'),

    ["name" => "cch/ch/cś/tś", // A 8.4.40
     "find" => ['c\s*(?:ch|ś)', 't\sś'],
     "replace" => 'ch',
     "first" => true
     ],
//        "ddh/dh" => array('ddh', "replace_with" => 'dh'),
//        "jjh/jh" => array('jjh', "replace_with" => 'jh'),
//        "t ś/c ch" => array('t(\s+)ś', 'c(\s+)ch', "replace_with" => '\1ch'),
    ["name" => "final t + voiced syllable", // t + h = ddh
     "find" => 'd(?=(?:\s[aāiīeuūogdbyrv]|\s*$))',
     "replace" => 't'
    ],
        
    ["name" => "final t + n/m",
     //"find" => 't(?=(?:\s+[nm]|\s*$))',
     "find" => 't(?=(?:\s[nm]))',
     "replace" => 'n'],
        
    ["name" => "final t + c/j",
     "find" => 'j(?=\sj)|c(?=\sc)',
     "replace" => 't'],
        // also t + ḍ = ḍḍ, t + ṭ = ṭṭ
      
        // add "iva" and "eva" to this rule?
    ["name" => "sya,tra,ma before iti",
     "find" => '(?<=sy|tr|m)a\si(?=t[iīy])',
     "replace" => 'e'],

    ["name" => "a a/ā",
     "find" => 'a\sa',
     "replace" => 'ā'],

    ["name" => "-ena,-sya + u-",
     "find" => '(?<=en|sy)a [u]',
     "replace" => "o"],

// most of these iti rules can be applied only to printed editions
        //"i i/īti" => array('i\s+i(?=t[iīy])', "replace_with" => 'ī'),
        
    ["name" => "i i/ī",
     "find" => 'i\si',
     "replace" => 'ī'],
    
    ["name" => "ā + iti",
     "find" => 'ā\si(?=t[iīy])',
     "replace" => 'e'],
    
    ["name" => "e/a + i",
     "find" => 'e(?=\si)',
     "replace" => 'a'],
        
    ["name" => "i/y + vowel",
     "find" => 'y(?=\s[āauūeo])',
     "replace" => "i"],
        // replacing i with y fails in some cases,
        // as in "abhyupaiti | etad"
        // or in "ity bhāvaḥ" compared to "iti āśaṇkyaḥ"
    ["name" => "l and ḻ",
//     "include" => "//textLang[@mainLang='sa-Mlym']",
        // also want to include C, which is a Devanāgarī transcript of Malayālam
     "find" => 'ḻ',
     "replace" => 'l',
     "first" => true,
     ],

    ["name" => "pṛṣṭhamātrā e (Devanāgarī)",
     "include" => "//textLang[@mainLang='sa-Deva']",
     "find" => 'ê',
     "replace" => 'e',
     "first" => true,
    ],
    ["name" => "pṛṣṭhamātrā ai (Devanāgarī)",
     "include" => "//textLang[@mainLang='sa-Deva']",
     "find" => 'î',
     "replace" => 'i',
     "first" => true,
    ],
    ["name" => "pṛṣṭhamātrā o (Devanāgarī)",
     "include" => "//textLang[@mainLang='sa-Deva']",
     "find" => 'ô',
     "replace" => 'o',
     "first" => true,
    ],  
    ["name" => "pṛṣṭhamātrā au (Devanāgarī)",
     "include" => "//textLang[@mainLang='sa-Deva']",
     "find" => 'û',
     "replace" => 'u',
     "first" => true,
    ],
    ["name" => "b written as v (some scripts)",
     "include" => "//scriptNote[@xml:id='script-ba-va']",
     "find" => 'b(?!h)',
     "replace" => 'v',
     "first" => true,
    ],
    ["name" => "dbh written as bhd (some scripts)",
     "include" => "//scriptNote[@xml:id='script-dbha-bhda']",
     "find" => 'bh(\s?)d(?!h)',
     "replace" => 'd\1bh',
     "first" => true,
    ],
    ["name" => "śa written as sa (some scripts)",
     "include" => "//scriptNote[@xml:id='script-śa-sa']",
     "find" => 'ś',
     "replace" => 's'
    ],
/*
    ["name" => "pṛṣṭhamātrās (Devanāgarī)",
     "include" => "//textLang[@mainLang='sa-Deva']",
     "find" => array('ê','î','ô','û'),
     "replace" => array('e','i','o','u'),
     ],
*/
    ["name" => "valapalagilaka (Telugu)",
     "include" => "//textLang[@mainLang='sa-Telu']",
     "find" => 'ṙ',
     "replace" => 'r',
     "first" => true,
    ],

);
?>
