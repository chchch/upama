# upama
A PHP library for comparing two or more Sanskrit TEI XML files and generating an apparatus with variants

This library depends on the php port of Google's diff-match-patch library by yetanotherape: https://github.com/yetanotherape/diff-match-patch

Here is an example of how to compare two files:

```
<?php
include "upama.php";

$upama = new Upama();
$compared = $upama->compare('file1.xml','file2.xml');

echo $upama->tramsform($compared);

?>
```

This will render the file named "file1.xml" with an apparatus containing variants from "file2.xml", outputting in HTML. Both XML files need to have the same number of "block-level" text structure elements, like &lt;p&gt;, &lt;div&gt;, and &lt;l&gt;. In addition, at least "file2.xml" should have a siglum defined in the &lt;teiHeader&gt;. A typical, minimal file should look like this:

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
        </msDesc>
      </sourceDesc>
    </fileDesc>
  </teiHeader>
  
  <text xml:lang="sa-Latn">
    <body>
      <div>
        <p>Some paragraph elements</p>
        <lg>
          <l>a verse</l>
          <l>another verse</l>
        </lg>
      </div>
    </body>
  </text>

</TEI>
```

See the "template.xml" file for more details. The text should be transcribed in IAST in order for Upama to work properly.

In order to compare a text with more than two witnesses, use the "collate" function:

```
<?php
include "upama.php";

$upama = new Upama();
$compared1 = $upama->compare('file1.xml','file2.xml');
$compared2 = $upama->compare('file1.xml','file2.xml');

$collated = $upama->collate($compared1,$compared2);

echo $upama->transform($collated);

?>
```

# the DokuWiki plugin

A live demo of upama.php in action can be found at http://saktumiva.org/wiki:dravyasamuddesa:start.

The code for the plugin and template used on saktumiva.org, which runs on DokuWiki, is in the dokuwiki directory. The plugin includes the hyphenation engine hypher (https://github.com/bramstein/hypher) with a Sanskrit hyphenation library (both IAST and indic scripts supported) as well as a some functions based on sanscript.js (https://github.com/sanskrit/sanscript) to convert between scripts.

