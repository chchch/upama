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
        <s:entry key="newa-gap-filler">&#x1144E;</s:entry>
        <s:entry key="newa-old-gap-filler">&#x1144E;</s:entry>
    </s:entities>
    <s:entitynames>
        <s:entry key="newa-gap-filler">gap filler</s:entry>
        <s:entry key="newa-old-gap-filler">old-style gap filler</s:entry>
    </s:entitynames>
    <s:entityclasses>
        <s:entry key="newa-old-gap-filler">trad</s:entry>
    </s:entityclasses>
</xsl:variable>

</xsl:stylesheet>
