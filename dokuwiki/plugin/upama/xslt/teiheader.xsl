<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
                xmlns:x="http://www.tei-c.org/ns/1.0"
                exclude-result-prefixes="x">
<xsl:output method="html" omit-xml-declaration="yes"/>

<xsl:template name="lang">
    <xsl:choose>
    <xsl:when test="@xml:lang">
        <xsl:attribute name="lang"><xsl:value-of select="@xml:lang"/></xsl:attribute>
    </xsl:when>
    <xsl:otherwise>
        <xsl:attribute name="lang">sa</xsl:attribute>
    </xsl:otherwise>
    </xsl:choose>
</xsl:template>

<!--xsl:template match="x:titleStmt">
    <h1>
        <xsl:apply-templates select="x:title"/>
    </h1>
    <h2>
        <xsl:apply-templates select="x:author"/>
    </h2>
</xsl:template -->
<xsl:template match="x:titleStmt/x:title">
    <h1><xsl:apply-templates/></h1>
</xsl:template>
<xsl:template match="x:titleStmt/x:title[@type='alt']">
    <h3 style="font-style:italic"><xsl:apply-templates/></h3>
</xsl:template>
<xsl:template match="x:titleStmt/x:editor" />

<xsl:template match="x:titleStmt/x:author"/>

<xsl:template match="x:publicationStmt">
    <p>Published in <xsl:apply-templates select="x:date"/> by <xsl:apply-templates select="x:publisher"/> in <xsl:apply-templates select="x:pubPlace"/>.</p>
</xsl:template>

<xsl:template match="x:msContents/x:summary/x:title">
    <xsl:element name="em">
        <xsl:call-template name="lang"/>
        <xsl:apply-templates/>
    </xsl:element>
</xsl:template>

<xsl:template match="x:msContents/x:summary/x:sub">
    <xsl:element name="sub">
        <xsl:apply-templates/>
    </xsl:element>
</xsl:template>
<xsl:template match="x:msContents/x:summary/x:sup">
    <xsl:element name="sup">
        <xsl:apply-templates/>
    </xsl:element>
</xsl:template>


<xsl:template match="x:msContents/x:summary/x:ptr">
    <xsl:element name="a">
        <xsl:attribute name="href"><xsl:value-of select="@target"/></xsl:attribute>
        <xsl:value-of select="@target"/>
    </xsl:element>
</xsl:template>

<xsl:template match="x:msIdentifier">
    <ul>
    <xsl:for-each select="*[not(self::x:idno)]">
    <li>
        <xsl:apply-templates/>
    </li>
    </xsl:for-each>
    <xsl:if test="x:idno[not(@type='siglum')]">
    <li><xsl:text>Known as: </xsl:text>
    <xsl:for-each select="x:idno[not(@type='siglum')][position() != last()]">
        <xsl:apply-templates/><xsl:text>, </xsl:text>
    </xsl:for-each>
    <xsl:apply-templates select="x:idno[not(@type='siglum')][last()]" />
    <xsl:text>.</xsl:text>
    </li>
    </xsl:if>
    <xsl:text>
    </xsl:text>
    </ul>
</xsl:template>

<xsl:template match="x:msContents">
    <p id="__upama_summary"><xsl:apply-templates select="x:summary"/></p>
    
    <xsl:for-each select="x:msItem">
        <h3><xsl:apply-templates select="x:author[1]"/></h3>
        <xsl:for-each select="x:author[position()>1]">
            <h4><xsl:value-of select="."/></h4>
        </xsl:for-each>
        <h3><xsl:apply-templates select="x:title[1]"/></h3>
    
        <xsl:for-each select="x:title[position()>1]">
            <h4><xsl:value-of select="."/></h4>
        </xsl:for-each>
    </xsl:for-each>
</xsl:template>

<xsl:template match="x:xenoData/x:stemma[@format='newick']"/>
<xsl:template match="x:xenoData/x:stemma[@format='nexml']">
    <div id="__upama_stemma"><xsl:copy-of select="./*"/></div>
</xsl:template>

<xsl:template match="x:physDesc"/>

<xsl:template match="x:history"/>

<xsl:template match="x:additional"/>

<xsl:template match="x:encodingDesc"/>

<xsl:template match="x:profileDesc"/>

<xsl:template match="x:revisionDesc"/>

<xsl:template match="x:facsimile"/>

<xsl:template match="x:text"/>
</xsl:stylesheet>
