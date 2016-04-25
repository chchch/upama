<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
                xmlns:x="http://www.tei-c.org/ns/1.0"
                exclude-result-prefixes="x">

<xsl:import href="common.xsl"/>

<xsl:output omit-xml-declaration="yes"/>

<xsl:template match="x:p[@xml:id]">
    <xsl:element name="div">
        <xsl:attribute name="class">para</xsl:attribute>
        <xsl:attribute name="id"><xsl:value-of select="@xml:id"/></xsl:attribute>
        <xsl:element name="div">
                <xsl:attribute name="class">maintext</xsl:attribute>
                <xsl:call-template name="lang"/>
                <xsl:apply-templates/>
        </xsl:element>
    </xsl:element>
</xsl:template>

<xsl:template match="x:lg[@type='verse']">
    <xsl:element name="div">
        <xsl:attribute name="class">verse</xsl:attribute>
        <xsl:if test="@xml:id">
        <xsl:attribute name="id"><xsl:value-of select="@xml:id"/></xsl:attribute>
        </xsl:if>
        <xsl:element name="div">
            <xsl:attribute name="class">maintext</xsl:attribute>
            <xsl:call-template name="lang"/>
            <xsl:apply-templates/>
        </xsl:element>
    </xsl:element>
</xsl:template>

<xsl:template match="x:l">
    <xsl:element name="div">
        <xsl:attribute name="class">verseline</xsl:attribute>
        <xsl:apply-templates/>
    </xsl:element>
</xsl:template>

<!--xsl:template match="x:lg[@type='verse']">
    <xsl:for-each select = "x:l">
    <xsl:choose>
        <xsl:when test="position() = last()">
            <xsl:element name="tr">
                <xsl:attribute name="class">verse</xsl:attribute>
                <td class="verse maintext versebottompadding" lang="sa">
                    <xsl:apply-templates />
                </td>
            </xsl:element>
        </xsl:when>
        <xsl:otherwise>
            <xsl:element name="tr">
                <xsl:attribute name="class">verse</xsl:attribute>
                <xsl:if test="../@xml:id">
                <xsl:attribute name="id"><xsl:value-of select="../@xml:id"/></xsl:attribute>
                </xsl:if>
                <td class="verse maintext" lang="sa">
                    <xsl:apply-templates />
                </td>
            </xsl:element>
        </xsl:otherwise>
    </xsl:choose>
    </xsl:for-each>
</xsl:template-->

<xsl:template match="x:lg[@type='quote']">
    <div class="quotedverse">
        <xsl:apply-templates />
    </div>
</xsl:template>

<xsl:template match="x:lg[@type='quote']/x:l">
    <div class="verseline">
        <xsl:apply-templates />
    </div>
</xsl:template>

<!--xsl:template match="x:lg[@type='quote']">
    <xsl:for-each select = "x:l">
    <xsl:choose>
        <xsl:when test="position() = last()">
            <tr>
            <td class="quotedverse maintext" lang="sa">
                <xsl:apply-templates />
            </td>
            </tr>
            <tr><td class="versebottompadding"><xsl:text> </xsl:text></td></tr>
        </xsl:when>
        <xsl:otherwise>
        <tr>
        <td class="quotedverse maintext" lang="sa">
            <xsl:apply-templates />
        </td>
        </tr>
        </xsl:otherwise>
    </xsl:choose>
    </xsl:for-each>
</xsl:template-->

<xsl:template match="x:caesura">
<xsl:variable name="pretext" select="preceding::text()[1]"/>
<xsl:if test="normalize-space(substring($pretext,string-length($pretext))) != ''">
    <span class="hyphen ignored">-</span>
</xsl:if>
    <xsl:element name="br">
    <xsl:if test="@ignored='TRUE'">
        <xsl:attribute name="class">ignored</xsl:attribute>
    </xsl:if>
    </xsl:element>
</xsl:template>

</xsl:stylesheet>
