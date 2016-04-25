<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
                xmlns:x="http://www.tei-c.org/ns/1.0"
                exclude-result-prefixes="x">
<xsl:output omit-xml-declaration="yes"/>

<xsl:template match="x:teiHeader"/>

<xsl:template match="x:TEI">
    <xsl:apply-templates />
</xsl:template>

<xsl:template match="x:text">
    <xsl:apply-templates />
</xsl:template>

<xsl:template match="x:body">
    <xsl:apply-templates />
</xsl:template>

<xsl:template match="x:milestone">
    <span class="milestone">
    (From <xsl:choose><xsl:when test="@unit"><xsl:value-of select="@unit"/></xsl:when><xsl:otherwise>folio</xsl:otherwise></xsl:choose><xsl:text> </xsl:text><xsl:value-of select="@n"/>)
    </span>
</xsl:template>

<xsl:template match="x:p">
    <p>
        <xsl:apply-templates/>
    </p>
</xsl:template>
<xsl:template match="x:p[@xml:id]">
        <xsl:element name="tr">
            <xsl:attribute name="class">para</xsl:attribute>
            <xsl:if test="@xml:id">
                <xsl:attribute name="id"><xsl:value-of select="@xml:id"/></xsl:attribute>
            </xsl:if>
            <xsl:apply-templates />
        </xsl:element>
</xsl:template>

<xsl:template match="x:maintext">
    <!--td class="maintext hyphenate" lang="sa"-->
    <td class="maintext" lang="sa">
        <xsl:apply-templates />
    </td><xsl:text>
    </xsl:text>
</xsl:template>
<xsl:template match="x:apparatus">
    <!--td class="apparatus hyphenate"-->
    <td class="apparatus"><xsl:text>
    </xsl:text>
        <xsl:apply-templates />
    </td><xsl:text>
    </xsl:text>
</xsl:template>
<xsl:template name="split">
    <xsl:param name="mss" select="@mss"/>
        <xsl:if test="string-length($mss)">
            <xsl:if test="not($mss=@mss)">, </xsl:if>
            <a class="msid">
             <xsl:value-of select="substring-before(concat($mss,';'),';')"/>
            </a>
            <xsl:call-template name="split">
                <xsl:with-param name="mss" select=
                    "substring-after($mss, ';')"/>
            </xsl:call-template>
        </xsl:if>
 </xsl:template>

<xsl:template match="x:varGroup">
    <xsl:apply-templates/>
</xsl:template>

<xsl:template match="x:reading">
    <xsl:element name="span"><xsl:attribute name="data-ms"><xsl:value-of select="@ms"/></xsl:attribute><xsl:attribute name="class">reading</xsl:attribute><xsl:attribute name="lang">sa</xsl:attribute><xsl:apply-templates /></xsl:element>
</xsl:template>

<xsl:template match="x:mainreading">
    <xsl:element name="span">
        <xsl:attribute name="class">variant</xsl:attribute>
        <xsl:attribute name="lang">sa</xsl:attribute>
        <xsl:apply-templates />
    </xsl:element>
</xsl:template>

<xsl:template match="x:variant">
    <xsl:element name="span">
        <xsl:attribute name="data-loc">
            <xsl:value-of select="@location"/>
        </xsl:attribute>
        <xsl:attribute name="class">varcontainer</xsl:attribute>
        <xsl:call-template name="split"/><xsl:text>: </xsl:text>
        <xsl:apply-templates />
    </xsl:element><xsl:text>
    </xsl:text>
</xsl:template>

<xsl:template match="x:varGroup/x:variant">
    <xsl:element name="span">
        <xsl:attribute name="data-loc">
            <xsl:value-of select="@location"/>
        </xsl:attribute>
        <xsl:attribute name="class">varcontainer</xsl:attribute>
        <xsl:call-template name="split"/><xsl:text>: </xsl:text>
        <xsl:apply-templates />
    </xsl:element><xsl:text> </xsl:text>
</xsl:template>

<xsl:template match="x:varGroup/x:variant[1]">
    <xsl:element name="span">
        <xsl:attribute name="data-loc">
            <xsl:value-of select="@location"/>
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

<xsl:template match="x:varGroup/x:variant[last()]">
    <xsl:element name="span">
        <xsl:attribute name="data-loc">
            <xsl:value-of select="@location"/>
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

<xsl:template match="x:variant//x:lg">
    <xsl:apply-templates />
</xsl:template>
<xsl:template match="x:variant//x:l">
    <xsl:apply-templates />
</xsl:template>

<xsl:template match="x:lg[@type='verse']">
    <xsl:element name="tr">
        <xsl:attribute name="class">verse</xsl:attribute>
        <xsl:if test="@xml:id">
        <xsl:attribute name="id"><xsl:value-of select="@xml:id"/></xsl:attribute>
        </xsl:if>
        <xsl:apply-templates/>
    </xsl:element>
</xsl:template>

<xsl:template match="x:lg[@type='verse']/x:maintext">
    <td class="verse maintext versebottompadding" lang="sa">
        <xsl:apply-templates />
    </td><xsl:text>
    </xsl:text>
</xsl:template>


<xsl:template match="x:lg/x:maintext/x:l">
    <div class="verseline">
    <xsl:apply-templates />
    </div>
</xsl:template>
<!--xsl:template match="x:lg[@type='verse']/x:maintext/x:l">
    <td class="verse maintext" lang="sa">
        <xsl:apply-templates />
    </td>
</xsl:template-->

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

<xsl:template match="x:editor">
    <span class="editor" lang="en"><xsl:apply-templates /></span>
</xsl:template>

<xsl:template match="x:div1">
    <table class="section">
        <xsl:apply-templates />
    </table>
</xsl:template>

<xsl:template match="x:div">
    <table class="section">
        <xsl:apply-templates />
    </table>
</xsl:template>

<xsl:template match="x:text//x:title">
    <h2>
        <xsl:apply-templates />
    </h2>
</xsl:template>

<xsl:template match="x:text//x:subtitle">
    <h3>
        <xsl:apply-templates />
    </h3>
</xsl:template>

<xsl:template name="ignore">
    <xsl:if test="@ignored='TRUE'"> ignored</xsl:if>
</xsl:template>

<xsl:template match="x:unclear">
    <xsl:element name="span">
        <xsl:attribute name="class">unclear<xsl:call-template name="ignore" /></xsl:attribute>
        <xsl:attribute name="title">unclear</xsl:attribute>
        <xsl:apply-templates/>
    </xsl:element>
</xsl:template>

<xsl:template match="x:subst">
    <xsl:choose>
        <xsl:when test="@ignored='TRUE'">
            <span class="ignored"><xsl:apply-templates /></span>
        </xsl:when>
        <xsl:otherwise>
            <xsl:apply-templates />
        </xsl:otherwise>
    </xsl:choose>
</xsl:template>

<xsl:template match="x:choice">
    <xsl:choose>
        <xsl:when test="@ignored='TRUE'">
            <span class="ignored"><xsl:apply-templates /></span>
        </xsl:when>
        <xsl:otherwise>
            <xsl:apply-templates />
        </xsl:otherwise>
    </xsl:choose>
</xsl:template>

<xsl:template match="x:del">
    <xsl:element name="del">
        <xsl:attribute name="title">deleted</xsl:attribute>
        <xsl:if test="@ignored='TRUE'">
            <xsl:attribute name="class">ignored</xsl:attribute>
        </xsl:if>
        <xsl:apply-templates />
    </xsl:element>
</xsl:template>

<xsl:template match="x:sic">
    <xsl:element name="del">
        <xsl:attribute name="title">sic</xsl:attribute>
        <xsl:attribute name="class">sic<xsl:call-template name="ignore" /></xsl:attribute>
        <xsl:apply-templates />
    </xsl:element>
<span class="sic" title="sic"><xsl:apply-templates /></span>
</xsl:template>

<xsl:template match="x:orig">
    <xsl:element name="del">
        <xsl:attribute name="title">original text</xsl:attribute>
        <xsl:attribute name="class">orig<xsl:call-template name="ignore" /></xsl:attribute>
        <xsl:apply-templates />
    </xsl:element>
</xsl:template>

<xsl:template match="x:add">
    <xsl:element name="ins">
        <xsl:attribute name="title">inserted</xsl:attribute>
        <xsl:attribute name="class">add<xsl:call-template name="ignore" /></xsl:attribute>
        <xsl:apply-templates />
    </xsl:element>
</xsl:template>

<xsl:template match="x:corr">
    <xsl:element name="ins">
        <xsl:attribute name="title">editor's correction</xsl:attribute>
        <xsl:attribute name="class">corr<xsl:call-template name="ignore" /></xsl:attribute>
        <xsl:apply-templates />
    </xsl:element>
</xsl:template>

<xsl:template match="x:lb">
    <xsl:element name="span">
        <xsl:attribute name="title">line <xsl:value-of select="@n"/></xsl:attribute>
        <xsl:attribute name="lang">en</xsl:attribute>
        <xsl:attribute name="class">lb<xsl:call-template name="ignore" /></xsl:attribute>
    <xsl:text disable-output-escaping="yes">⸤</xsl:text>
    </xsl:element>
</xsl:template>

<xsl:template match="x:pb">
    <xsl:element name="span">
        <xsl:attribute name="title">folio <xsl:value-of select="@n"/></xsl:attribute>
        <xsl:attribute name="class">pb<xsl:call-template name="ignore" /></xsl:attribute>
        <xsl:attribute name="lang">en</xsl:attribute>
    <xsl:text>L</xsl:text>
    </xsl:element>
</xsl:template>

<xsl:template match="x:g">
    <xsl:choose>
        <xsl:when test="@ignored='TRUE'">
            <span class="ignored"><xsl:apply-templates /></span>
        </xsl:when>
        <xsl:otherwise>
            <xsl:apply-templates />
        </xsl:otherwise>
    </xsl:choose>
</xsl:template>

<xsl:template match="x:gap">
    <xsl:element name="span">
        <xsl:attribute name="title">gap of <xsl:value-of select="@extent"/></xsl:attribute>
        <xsl:attribute name="class">gap<xsl:call-template name="ignore" /></xsl:attribute>
        <xsl:text>_</xsl:text>
    </xsl:element>
</xsl:template>

<xsl:template match="x:space">
    <xsl:element name="span">
        <xsl:attribute name="title">space of <xsl:value-of select="@quantity"/><xsl:text> </xsl:text><xsl:value-of select="@unit"/>s</xsl:attribute>
        <xsl:attribute name="class">space<xsl:call-template name="ignore" /></xsl:attribute>
        <xsl:text>_</xsl:text>
    </xsl:element>
</xsl:template>

<xsl:template match="x:note">
    <xsl:element name="span">
        <xsl:if test="@xml:lang">
            <xsl:attribute name="lang"><xsl:value-of select="@xml:lang"/></xsl:attribute>
        </xsl:if>
        <xsl:attribute name="class">note<xsl:choose><xsl:when test="@place='above'"> super</xsl:when><xsl:otherwise> inline</xsl:otherwise></xsl:choose><xsl:call-template name="ignore" /></xsl:attribute>
        <xsl:apply-templates />
    </xsl:element>
</xsl:template>

<xsl:template match="x:note[@place='foot']">
    <xsl:element name="span">
        <xsl:attribute name="class">hidenote</xsl:attribute>
        <xsl:attribute name="title">
            <xsl:value-of select="text()"/>
        </xsl:attribute>
        <xsl:text>*</xsl:text>
    </xsl:element>
</xsl:template>

<xsl:template match="x:maintext//x:caesura">
    <xsl:element name="br">
    <xsl:if test="@ignored='TRUE'">
        <xsl:attribute name="class">ignored</xsl:attribute>
    </xsl:if>
    </xsl:element>
</xsl:template>

<xsl:template match="x:variant//x:caesura">
    <xsl:element name="span">
    <xsl:attribute name="class">caesura</xsl:attribute>
    <xsl:if test="@ignored='TRUE'">
        <xsl:attribute name="class">ignored</xsl:attribute>
    </xsl:if>
    <xsl:text>/</xsl:text>
    </xsl:element>
</xsl:template>
<xsl:template match="x:hi">
    <xsl:element name="span">
        <xsl:attribute name="class">hi<xsl:call-template name="ignore" /></xsl:attribute>
        <xsl:attribute name="title">marked by <xsl:value-of select="@rend"/></xsl:attribute>
        <xsl:apply-templates/>
    </xsl:element>
</xsl:template>

<xsl:template match="@*|node()">
    <xsl:copy><xsl:apply-templates select="@* | node()"/></xsl:copy>
</xsl:template>

</xsl:stylesheet>
