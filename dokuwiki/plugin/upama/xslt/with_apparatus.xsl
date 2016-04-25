<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
                xmlns:x="http://www.tei-c.org/ns/1.0"
                exclude-result-prefixes="x">

<xsl:import href="common.xsl"/>

<xsl:output omit-xml-declaration="yes"/>


<xsl:template match="x:p[@xml:id]">
        <xsl:element name="div">
            <xsl:attribute name="class">para</xsl:attribute>
            <xsl:attribute name="id"><xsl:value-of select="@xml:id"/></xsl:attribute>
            <xsl:apply-templates />
        </xsl:element>
</xsl:template>

<xsl:template match="x:maintext">
    <!--td class="maintext hyphenate" lang="sa"-->
    <div class="maintext" lang="sa">
        <xsl:apply-templates />
    </div><xsl:text>
    </xsl:text>
</xsl:template>

<xsl:template match="x:listApp">
    <!--td class="apparatus hyphenate"-->
    <div class="apparatus"><xsl:text>
    </xsl:text>
        <xsl:apply-templates />
    </div><xsl:text>
    </xsl:text>
</xsl:template>
<xsl:template name="split">
    <xsl:param name="mss" select="@mss"/>
        <xsl:if test="string-length($mss)">
            <xsl:if test="not($mss=@mss)">, </xsl:if>
            <a class="msid">
             <xsl:variable name="msstring" select="substring-after(
                                      substring-before(
                                        concat($mss,' '),
                                      ' '),
                                   '#')"/>
             <xsl:apply-templates select="/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:listWit/x:witness[@xml:id=$msstring]/x:idno/node()"/>
            </a>
            <xsl:call-template name="split">
                <xsl:with-param name="mss" select=
                    "substring-after($mss, ' ')"/>
            </xsl:call-template>
        </xsl:if>
</xsl:template>

<xsl:template match="x:rdgGrp">
    <xsl:apply-templates/>
</xsl:template>

<xsl:template match="x:rdg">
    <xsl:element name="span"><xsl:attribute name="data-ms"><xsl:value-of select="substring-after(@wit,'#')"/></xsl:attribute><xsl:attribute name="class">reading</xsl:attribute><xsl:attribute name="lang">sa</xsl:attribute><xsl:apply-templates /></xsl:element>
</xsl:template>

<xsl:template match="x:rdg[@type='main']">
    <xsl:element name="span">
        <xsl:attribute name="class">variant</xsl:attribute>
        <xsl:attribute name="lang">sa</xsl:attribute>
        <xsl:apply-templates />
    </xsl:element>
</xsl:template>

<xsl:template match="x:app">
    <xsl:element name="span">
        <xsl:attribute name="data-loc">
            <xsl:value-of select="@loc"/>
        </xsl:attribute>
        <xsl:attribute name="class">varcontainer</xsl:attribute>
        <xsl:call-template name="split"/><xsl:text>: </xsl:text>
        <xsl:apply-templates />
    </xsl:element><xsl:text>
    </xsl:text>
</xsl:template>

<xsl:template match="x:rdgGrp/x:app">
    <xsl:element name="span">
        <xsl:attribute name="data-loc">
            <xsl:value-of select="@loc"/>
        </xsl:attribute>
        <xsl:attribute name="class">varcontainer</xsl:attribute>
        <xsl:call-template name="split"/><xsl:text>: </xsl:text>
        <xsl:apply-templates />
    </xsl:element><xsl:text> </xsl:text>
</xsl:template>

<xsl:template match="x:rdgGrp/x:app[1]">
    <xsl:element name="span">
        <xsl:attribute name="data-loc">
            <xsl:value-of select="@loc"/>
        </xsl:attribute>
        <xsl:attribute name="class">varcontainer</xsl:attribute>
        <xsl:element name="span">
            <xsl:attribute name="class">varbracket</xsl:attribute>
            <xsl:text>❲ </xsl:text>
        </xsl:element>
        <xsl:call-template name="split"/><xsl:text>: </xsl:text>
        <xsl:apply-templates />
    </xsl:element><xsl:text> </xsl:text>
</xsl:template>

<xsl:template match="x:rdgGrp/x:app[last()]">
    <xsl:element name="span">
        <xsl:attribute name="data-loc">
            <xsl:value-of select="@loc"/>
        </xsl:attribute>
        <xsl:attribute name="class">varcontainer</xsl:attribute>
        <xsl:call-template name="split"/><xsl:text>: </xsl:text>
        <xsl:apply-templates />
        <xsl:element name="span">
            <xsl:attribute name="class">varbracket</xsl:attribute>
            <xsl:text> ❳</xsl:text>
        </xsl:element>
    </xsl:element><xsl:text>
    </xsl:text>
</xsl:template>

<xsl:template match="x:app//x:lg">
    <xsl:apply-templates />
</xsl:template>

<xsl:template match="x:app//x:l">
    <xsl:apply-templates />
</xsl:template>

<xsl:template match="x:lg[@type='verse']">
    <xsl:element name="div">
        <xsl:attribute name="class">verse</xsl:attribute>
        <xsl:if test="@xml:id">
        <xsl:attribute name="id"><xsl:value-of select="@xml:id"/></xsl:attribute>
        </xsl:if>
        <xsl:apply-templates/>
    </xsl:element>
</xsl:template>

<xsl:template match="x:lg[@type='verse']/x:maintext">
    <div class="maintext" lang="sa">
        <xsl:apply-templates />
    </div><xsl:text>
    </xsl:text>
</xsl:template>


<xsl:template match="x:lg/x:maintext/x:l">
    <div class="verseline">
    <xsl:apply-templates />
    </div>
</xsl:template>

<xsl:template match="x:maintext//x:lg[@type='quote']">
    <div class="quotedverse">
        <xsl:apply-templates />
    </div>
</xsl:template>

<xsl:template match="x:maintext//x:lg[@type='quote']/x:l">
    <div class="verseline">
        <xsl:apply-templates />
    </div>
</xsl:template>

<!--xsl:template match="x:lg/x:maintext">
    <xsl:for-each select = "x:l">
    <xsl:choose>
        <xsl:when test="position() = last()">
            <tr>
                <xsl:apply-templates />
            </tr>
            <tr><td class="versebottompadding"><xsl:text> </xsl:text></td></tr>
        </xsl:when>
        <xsl:otherwise>
        <tr>
            <xsl:apply-templates />
        </tr>
        </xsl:otherwise>
    </xsl:choose>
    </xsl:for-each>
</xsl:template-->

<xsl:template match="x:maintext//x:caesura">
<xsl:variable name="pretext" select="preceding::text()[1]"/>
<xsl:if test="normalize-space(substring($pretext,string-length($pretext))) != ''">
    <span class="hyphen ignored">-</span>
</xsl:if>
    <xsl:element name="br">
    <xsl:attribute name="class">caesura<xsl:call-template name="ignore"/></xsl:attribute>
    </xsl:element>
</xsl:template>

<xsl:template match="x:app//x:caesura">
    <xsl:element name="span">
    <xsl:attribute name="class">caesura</xsl:attribute>
    <xsl:if test="@ignored='TRUE' or @upama-show='TRUE'">
        <xsl:attribute name="class"><xsl:call-template name="ignore"/></xsl:attribute>
    </xsl:if>
    <xsl:element name="span">
        <xsl:attribute name="class">ignored</xsl:attribute>
        <xsl:text>/</xsl:text>
    </xsl:element>
    </xsl:element>
</xsl:template>

</xsl:stylesheet>
