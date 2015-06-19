<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output omit-xml-declaration="yes" />

<xsl:template match="body">
    <xsl:apply-templates />
</xsl:template>

<xsl:template match="milestone[@unit]">
    <tr class="milestone">
    <td class="maintext">(From <xsl:value-of select="@unit"/><xsl:text> </xsl:text><xsl:value-of select="@n"/>)</td>
    </tr>
</xsl:template>
<xsl:template match="milestone[not(@unit)]">
    <tr class="milestone">
    <td class="maintext">(From folio <xsl:value-of select="@n"/>)</td>
    </tr>
</xsl:template>

<xsl:template match="p">
        <tr class="para">
        <xsl:apply-templates />
        </tr>
</xsl:template>

<xsl:template match="maintext">
    <td class="maintext hyphenate" lang="sa">
        <xsl:apply-templates />
    </td>
</xsl:template>
<xsl:template match="apparatus">
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

<xsl:template match="varGroup">
    <xsl:apply-templates/>
</xsl:template>

<xsl:template match="reading">
    <xsl:element name="span"><xsl:attribute name="data-ms"><xsl:value-of select="@ms"/></xsl:attribute><xsl:attribute name="class">reading</xsl:attribute><xsl:attribute name="lang">sa</xsl:attribute><xsl:apply-templates /></xsl:element>
</xsl:template>

<xsl:template match="mainreading">
    <xsl:element name="span">
        <xsl:attribute name="class">variant</xsl:attribute>
        <xsl:attribute name="lang">sa</xsl:attribute>
        <xsl:apply-templates />
    </xsl:element>
</xsl:template>

<xsl:template match="variant">
    <xsl:element name="span">
        <xsl:attribute name="data-loc">
            <xsl:value-of select="@location"/>
        </xsl:attribute>
        <xsl:attribute name="class">varcontainer</xsl:attribute>
        <xsl:call-template name="split"/><xsl:text>: </xsl:text>
        <xsl:apply-templates />
    </xsl:element>
</xsl:template>

<xsl:template match="varGroup/variant[1]">
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

<xsl:template match="varGroup/variant[last()]">
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

<xsl:template match="lg[@type='verse']/l/maintext">
    <td class="verse maintext" lang="sa">
        <xsl:apply-templates />
    </td>
</xsl:template>

<xsl:template match="lg[@type='quote']/l/maintext">
    <td class="quotedverse maintext" lang="sa">
        <xsl:apply-templates />
    </td>
</xsl:template>

<xsl:template match="lg">
    <xsl:for-each select = "l">
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
</xsl:template>

<xsl:template match="editor">
    <span class="editor"><xsl:apply-templates /></span>
</xsl:template>
<xsl:template match="div1">
    <table class="section">
        <xsl:apply-templates />
    </table>
</xsl:template>

<xsl:template match="unclear">
    <span class="unclear" title="unclear"><xsl:apply-templates/></span>
</xsl:template>
<xsl:template match="unclear[@ignored='TRUE']">
    <span class="unclear ignored" title="unclear"><xsl:apply-templates/></span>
</xsl:template>

<xsl:template match="subst">
    <xsl:apply-templates/>
</xsl:template>
<xsl:template match="subst[@ignored='TRUE']">
    <span class="ignored"><xsl:apply-templates/></span>
</xsl:template>

<xsl:template match="choice">
    <xsl:apply-templates/>
</xsl:template>
<xsl:template match="choice[@ignored='TRUE']">
    <span class="ignored"><xsl:apply-templates/></span>
</xsl:template>

<xsl:template match="del">
    <del title="deleted"><xsl:apply-templates /></del>
</xsl:template>
<xsl:template match="del[@ignored='TRUE']">
    <del class="ignored" title="deleted"><xsl:apply-templates/></del>
</xsl:template>

<xsl:template match="sic">
    <del class="sic" title="sic"><xsl:apply-templates /></del>
</xsl:template>
<xsl:template match="sic[@ignored='TRUE']">
    <del class="sic ignored" title="sic"><xsl:apply-templates/></del>
</xsl:template>

<xsl:template match="orig">
    <del class="orig"><xsl:apply-templates /></del>
</xsl:template>
<xsl:template match="orig[@ignored='TRUE']">
    <del class="orig ignored"><xsl:apply-templates /></del>
</xsl:template>

<xsl:template match="add">
    <ins class="add" title="inserted"><xsl:apply-templates /></ins>
</xsl:template>
<xsl:template match="add[@ignored='TRUE']">
    <ins class="add ignored" title="inserted"><xsl:apply-templates/></ins>
</xsl:template>

<xsl:template match="corr">
    <ins class="corr" title="editor's correction"><xsl:apply-templates /></ins>
</xsl:template>
<xsl:template match="corr[@ignored='TRUE']">
    <ins class="corr ignored" title="editor's correction"><xsl:apply-templates/></ins>
</xsl:template>

<xsl:template match="lb">
    <xsl:element name="span"><xsl:attribute name="title">line <xsl:value-of select="@n"/></xsl:attribute><xsl:attribute name="class">lb</xsl:attribute><xsl:text disable-output-escaping="yes">⸤</xsl:text></xsl:element>
</xsl:template>
<xsl:template match="lb[@ignored='TRUE']">
    <xsl:element name="span"><xsl:attribute name="title">line <xsl:value-of select="@n"/></xsl:attribute><xsl:attribute name="class">lb ignored</xsl:attribute><xsl:text disable-output-escaping="yes">⸤</xsl:text></xsl:element>
</xsl:template> 
 
<xsl:template match="pb">
    <xsl:element name="span"><xsl:attribute name="title">page <xsl:value-of select="@n"/></xsl:attribute><xsl:attribute name="class">pb</xsl:attribute>⌊</xsl:element>
</xsl:template>
<xsl:template match="pb[@ignored='TRUE']">
    <xsl:element name="span"><xsl:attribute name="title">page <xsl:value-of select="@n"/></xsl:attribute><xsl:attribute name="class">pb ignored</xsl:attribute>⌊</xsl:element>
</xsl:template>

<xsl:template match="g">
    <xsl:apply-templates/>
</xsl:template>
<xsl:template match="g[@ignored='TRUE']">
    <span class="ignored"><xsl:apply-templates/></span>
</xsl:template>
<xsl:template match="gap">
    <xsl:element name="span"><xsl:attribute name="class">gap</xsl:attribute><xsl:attribute name="title">gap of <xsl:value-of select="@extent"/></xsl:attribute><xsl:text>_</xsl:text></xsl:element>
</xsl:template>
<xsl:template match="gap[@ignored='TRUE']">
    <xsl:element name="span"><xsl:attribute name="class">gap ignored</xsl:attribute><xsl:attribute name="title">gap of <xsl:value-of select="@extent"/></xsl:attribute><xsl:text>_</xsl:text></xsl:element>
</xsl:template>

<xsl:template match="note">
    <span class="note inline"><xsl:apply-templates /></span>
</xsl:template>
<xsl:template match="note[@place='above']">
    <span class="note super"><xsl:apply-templates /></span>
</xsl:template>
<xsl:template match="note[@ignored='TRUE']">
    <span class="note inline ignored"><xsl:apply-templates /></span>
</xsl:template>
<xsl:template match="note[@ignored='TRUE' and @place='above']">
    <span class="note super ignored"><xsl:apply-templates /></span>
</xsl:template>

<xsl:template match="@*|node()">
    <xsl:copy><xsl:apply-templates select="@* | node()"/></xsl:copy>
</xsl:template>

</xsl:stylesheet>
