# upama
A PHP library for comparing two or more Sanskrit TEI XML files and generating an apparatus with variants

This library depends on the php port of Google's diff-match-patch library by yetanotherape: https://github.com/yetanotherape/diff-match-patch

Here is an example of how to compare two files:

```
<?php
include "upama.php";

$upama = new Upama();
$comparison = $upama->compare('file1.xml','file2.xml');
$stylesheet = 'apparatus.xsl';

echo $upama->transform($comparison,$stylesheet);

?>
```

This will render the file named "file1.xml" with an apparatus containing variants from "file2.xml", using the XSLT stylesheet called "apparatus.xsl" and outputting in HTML. Both XML files should have text structure elements with xml:id attributes; these will be matched by the collation engine. In addition, at least "file2.xml" should have a siglum defined in the &lt;teiHeader&gt;. A typical, minimal file should look like this:

```
<TEI xmlns="http://www.tei-c.org/ns/1.0">
  
  <teiHeader>
    <fileDesc>
      <titleStmt>
        <title>Title of the text</title>
        <author>Author of the text</author>
      </titleStmt>
      <sourceDesc>
        <msDesc>
          <msIdentifier>
            <idno type="siglum">Siglum used for this witness in the apparatus</idno>
          </msIdentifier>
          
          <msContents>
             <msItem>
                <textLang mainlang="sa-Deva">Sanskrit.</textLang>
             </msItem>
          </msContents>
       </msDesc>
      </sourceDesc>
    </fileDesc>
  </teiHeader>
  
  <text xml:lang="sa-Latn">
    <body>
      <div>
        <p xml:id="paragraph_1">Some paragraph elements</p>
        <lg xml:id="verse_1">
          <l>a verse</l>
          <l>another verse</l>
        </lg>
      </div>
    </body>
  </text>

</TEI>
```

The text should be transcribed in IAST in order for Upama to work properly. The mainLang attribute of the textLang tag should be sa-Latn, sa-Deva, sa-Mlym, sa-Telu, etc. depending on what script the original document is written in. Some of the text normalization functions rely on this attribute being set.

In order to compare a text with more than two witnesses, use the "collate" function:

```
<?php
include "upama.php";

$upama = new Upama();
$comparison1 = $upama->compare('file1.xml','file2.xml');
$comparison2 = $upama->compare('file1.xml','file3.xml');

$collation = $upama->collate($comparison1,$comparison2);
$stylesheet = 'apparatus.xsl';

echo $upama->transform($collation,$stylesheet);

?>
```

# the DokuWiki plugin

A live demo of upama.php in action can be found at http://saktumiva.org/wiki:dravyasamuddesa:start.

The code for the plugin and template used on saktumiva.org, which runs on DokuWiki, is in the dokuwiki directory. The plugin includes the hyphenation engine hypher (https://github.com/bramstein/hypher) with a Sanskrit hyphenation library (both IAST and indic scripts supported) as well as some functions based on sanscript.js (https://github.com/sanskrit/sanscript) to convert between scripts.

