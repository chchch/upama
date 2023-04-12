<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
                xmlns:exsl="http://exslt.org/common"
                xmlns:x="http://www.tei-c.org/ns/1.0"
                xmlns:s="https://saktumiva.org"
                exclude-result-prefixes="x exsl s">

<xsl:output method="html" encoding="UTF-8" omit-xml-declaration="yes"/>

<xsl:template match="s:entry">
    <xsl:apply-templates/>
</xsl:template>

<!-- definitions -->
<xsl:variable name="defRoot">
    <s:entities>
        <s:entry key="newa-siddhi">&#x1144A;</s:entry>
        <s:entry key="newa-gap-filler">&#x1144E;</s:entry>
        <s:entry key="newa-old-gap-filler">&#x1144E;</s:entry>
        <s:entry key="newa-comma">&#x1144D;</s:entry>
        <s:entry key="newa-double-comma">&#x1145A;</s:entry>

        <s:entry key="sarada-ekam">&#x11DA;</s:entry><!-- deprecated -->
        <s:entry key="sarada-siddhi">&#x11DB;</s:entry><!-- deprecated -->
        <s:entry key="sharada-ekam">&#x11DA;</s:entry>
        <s:entry key="sharada-sign-siddham">&#x11DB;</s:entry>
        <s:entry key="sharada-section-mark-1">&#x111DE;</s:entry>

        <s:entry key="broken-danda">&#x964;</s:entry>
        <s:entry key="danda-with-slash">&#x964;</s:entry>
    </s:entities>
    <s:entitynames>
        <s:entry key="newa-siddhi">newa siddhi</s:entry>
        <s:entry key="newa-gap-filler">gap filler</s:entry>
        <s:entry key="newa-old-gap-filler">old-style gap filler</s:entry>

        <s:entry key="sarada-ekam">śāradā ekam</s:entry><!-- deprecated -->
        <s:entry key="sarada-siddhi">śāradā siddhi</s:entry><!-- deprecated -->
        <s:entry key="sharada-ekam">śāradā ekam</s:entry>
        <s:entry key="sharada-sign-siddham">śāradā siddham</s:entry>
        <s:entry key="sharada-section-mark-1">śāradā section mark-1</s:entry>
        
        <s:entry key="broken-danda">broken daṇḍa</s:entry>
        <s:entry key="danda-with-slash">daṇḍa with slash</s:entry>
    </s:entitynames>
    <s:entityclasses>
        <s:entry key="newa-old-gap-filler">cv01</s:entry>
        <s:entry key="broken-danda">cv01</s:entry>
        <s:entry key="danda-with-slash">cv02</s:entry>
    </s:entityclasses>
</xsl:variable>

</xsl:stylesheet>
