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

<xsl:template match="x:milestone[@unit]">
    <tr class="milestone">
    <td class="maintext">(From <xsl:value-of select="@unit"/><xsl:text> </xsl:text><xsl:value-of select="@n"/>)</td>
    </tr>
</xsl:template>
<xsl:template match="x:milestone[not(@unit)]">
    <tr class="milestone">
    <td class="maintext">(From folio <xsl:value-of select="@n"/>)</td>
    </tr>
</xsl:template>

<xsl:template match="x:p">
        <tr class="para">
        <xsl:apply-templates />
        </tr>
</xsl:template>

<xsl:template match="x:maintext">
    <td class="maintext hyphenate" lang="sa">
        <xsl:apply-templates />
    </td>
</xsl:template>
<xsl:template match="x:apparatus">
    <td class="apparatus hyphenate">
        <xsl:apply-templates />
    </td>
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
    </xsl:element>
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
    </xsl:element>
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
    </xsl:element>
</xsl:template>

<xsl:template match="x:variant//x:lg">
    <xsl:apply-templates />
</xsl:template>
<xsl:template match="x:variant//x:l">
    <xsl:apply-templates />
</xsl:template>

<xsl:template match="x:lg[@type='verse']">
    <tr type="verse">
        <xsl:apply-templates/>
    </tr>
</xsl:template>

<xsl:template match="x:lg[@type='verse']/x:maintext">
    <td class="verse maintext versebottompadding" lang="sa">
        <xsl:apply-templates />
    </td>
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
    <span class="editor"><xsl:apply-templates /></span>
</xsl:template>
<xsl:template match="x:div1">
    <table class="section">
        <xsl:apply-templates />
    </table>
</xsl:template>

<xsl:template match="x:unclear">
    <span class="unclear" title="unclear"><xsl:apply-templates/></span>
</xsl:template>
<xsl:template match="x:unclear[@ignored='TRUE']">
    <span class="unclear ignored" title="unclear"><xsl:apply-templates/></span>
</xsl:template>

<xsl:template match="x:subst">
    <xsl:apply-templates/>
</xsl:template>
<xsl:template match="x:subst[@ignored='TRUE']">
    <span class="ignored"><xsl:apply-templates/></span>
</xsl:template>

<xsl:template match="x:choice">
    <xsl:apply-templates/>
</xsl:template>
<xsl:template match="x:choice[@ignored='TRUE']">
    <span class="ignored"><xsl:apply-templates/></span>
</xsl:template>

<xsl:template match="x:del">
    <del title="deleted"><xsl:apply-templates /></del>
</xsl:template>
<xsl:template match="x:del[@ignored='TRUE']">
    <del class="ignored" title="deleted"><xsl:apply-templates/></del>
</xsl:template>

<xsl:template match="x:sic">
    <span class="sic" title="sic"><xsl:apply-templates /></span>
</xsl:template>
<xsl:template match="x:sic[@ignored='TRUE']">
    <span class="sic ignored" title="sic"><xsl:apply-templates/></span>
</xsl:template>

<xsl:template match="x:orig">
    <del class="orig" title="original text"><xsl:apply-templates /></del>
</xsl:template>
<xsl:template match="x:orig[@ignored='TRUE']">
    <del class="orig ignored" title="original text"><xsl:apply-templates /></del>
</xsl:template>

<xsl:template match="x:add">
    <ins class="add" title="inserted"><xsl:apply-templates /></ins>
</xsl:template>
<xsl:template match="x:add[@ignored='TRUE']">
    <ins class="add ignored" title="inserted"><xsl:apply-templates/></ins>
</xsl:template>

<xsl:template match="x:corr">
    <ins class="corr" title="editor's correction"><xsl:apply-templates /></ins>
</xsl:template>
<xsl:template match="x:corr[@ignored='TRUE']">
    <ins class="corr ignored" title="editor's correction"><xsl:apply-templates/></ins>
</xsl:template>

<xsl:template match="x:lb">
    <xsl:element name="span"><xsl:attribute name="title">line <xsl:value-of select="@n"/></xsl:attribute><xsl:attribute name="class">lb</xsl:attribute><xsl:text disable-output-escaping="yes">⸤</xsl:text></xsl:element>
</xsl:template>
<xsl:template match="x:lb[@ignored='TRUE']">
    <xsl:element name="span"><xsl:attribute name="title">line <xsl:value-of select="@n"/></xsl:attribute><xsl:attribute name="class">lb ignored</xsl:attribute><xsl:text disable-output-escaping="yes">⸤</xsl:text></xsl:element>
</xsl:template> 
 
<xsl:template match="x:pb">
    <xsl:element name="span"><xsl:attribute name="title">page <xsl:value-of select="@n"/></xsl:attribute><xsl:attribute name="class">pb</xsl:attribute>L</xsl:element>
</xsl:template>
<xsl:template match="x:pb[@ignored='TRUE']">
    <xsl:element name="span"><xsl:attribute name="title">page <xsl:value-of select="@n"/></xsl:attribute><xsl:attribute name="class">pb ignored</xsl:attribute>L</xsl:element>
</xsl:template>

<xsl:template match="x:g">
    <xsl:apply-templates/>
</xsl:template>
<xsl:template match="x:g[@ignored='TRUE']">
    <span class="ignored"><xsl:apply-templates/></span>
</xsl:template>
<xsl:template match="x:gap">
    <xsl:element name="span"><xsl:attribute name="class">gap</xsl:attribute><xsl:attribute name="title">gap of <xsl:value-of select="@extent"/></xsl:attribute><xsl:text>_</xsl:text></xsl:element>
</xsl:template>
<xsl:template match="x:gap[@ignored='TRUE']">
    <xsl:element name="span"><xsl:attribute name="class">gap ignored</xsl:attribute><xsl:attribute name="title">gap of <xsl:value-of select="@extent"/></xsl:attribute><xsl:text>_</xsl:text></xsl:element>
</xsl:template>

<xsl:template match="x:space">
    <xsl:element name="span">
        <xsl:attribute name="class">space</xsl:attribute>
        <xsl:attribute name="title">space of <xsl:value-of select="@quantity"/><xsl:text> </xsl:text> <xsl:value-of select="@unit"/>s</xsl:attribute>
    <xsl:text>_</xsl:text>
    </xsl:element>
</xsl:template>
<xsl:template match="x:space[@ignored='TRUE']">
    <xsl:element name="span">
        <xsl:attribute name="class">space ignored</xsl:attribute>
        <xsl:attribute name="title">space of <xsl:value-of select="@quantity"/><xsl:text> </xsl:text> <xsl:value-of select="@unit"/>s</xsl:attribute>
    <xsl:text>_</xsl:text>
    </xsl:element>
</xsl:template>

<xsl:template match="x:note">
    <span class="note inline"><xsl:apply-templates /></span>
</xsl:template>
<xsl:template match="x:note[@place='above']">
    <span class="note super"><xsl:apply-templates /></span>
</xsl:template>
<xsl:template match="x:note[@ignored='TRUE']">
    <span class="note inline ignored"><xsl:apply-templates /></span>
</xsl:template>
<xsl:template match="x:note[@ignored='TRUE' and @place='above']">
    <span class="note super ignored"><xsl:apply-templates /></span>
</xsl:template>

<xsl:template match="x:hi">
    <xsl:element name="span">
        <xsl:attribute name="class">hi</xsl:attribute>
        <xsl:attribute name="title">marked by <xsl:value-of select="@rend"/></xsl:attribute>
        <xsl:apply-templates/>
    </xsl:element>
</xsl:template>

<xsl:template match="x:hi[@ignored='TRUE']">
    <xsl:element name="span">
        <xsl:attribute name="class">hi ignored</xsl:attribute>
        <xsl:attribute name="title">marked by <xsl:value-of select="@rend"/></xsl:attribute>
        <xsl:apply-templates/>
    </xsl:element>
</xsl:template>

<xsl:template match="@*|node()">
    <xsl:copy><xsl:apply-templates select="@* | node()"/></xsl:copy>
</xsl:template>

</xsl:stylesheet>
