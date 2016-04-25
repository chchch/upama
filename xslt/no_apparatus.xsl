<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
                xmlns:x="http://www.tei-c.org/ns/1.0"
                exclude-result-prefixes="x">
<xsl:output omit-xml-declaration="yes"/>

<xsl:template match="x:teiHeader"/>

<xsl:template match="x:text/x:body">
        <xsl:apply-templates />
</xsl:template>

<xsl:template match="x:milestone">
    <span class="milestone">
    (From <xsl:choose><xsl:when test="@unit"><xsl:value-of select="@unit"/></xsl:when><xsl:otherwise>folio</xsl:otherwise></xsl:choose><xsl:text> </xsl:text><xsl:value-of select="@n"/>)
    </span>
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

<xsl:template match="x:p">
    <p>
        <xsl:apply-templates/>
    </p>
</xsl:template>
<xsl:template match="x:p[@xml:id]">
    <tr class="para">
        <td class="maintext" lang="sa">
        <xsl:apply-templates />
        </td>
    </tr>
</xsl:template>

<xsl:template match="x:lg[@type='verse']">
    <xsl:for-each select = "x:l">
    <xsl:choose>
        <xsl:when test="position() = last()">
            <tr>
            <td class="verse maintext" lang="sa">
                <xsl:apply-templates />
            </td>
            </tr>
            <tr><td class="versebottompadding"><xsl:text> </xsl:text></td></tr>
        </xsl:when>
        <xsl:otherwise>
        <tr>
        <td class="verse maintext" lang="sa">
            <xsl:apply-templates />
        </td>
        </tr>
        </xsl:otherwise>
    </xsl:choose>
    </xsl:for-each>
</xsl:template>

<xsl:template match="x:lg[@type='quote']">
    <div class="quotedverse" lang="sa">
        <xsl:apply-templates />
    </div>
</xsl:template>

<xsl:template match="x:lg[@type='quote']/x:l">
    <div class="verseline" lang="sa">
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

<xsl:template match="x:unclear">
    <span class="unclear" title="unclear"><xsl:apply-templates/></span>
</xsl:template>

<xsl:template match="x:lb">
     <xsl:element name="span"><xsl:attribute name="title">line <xsl:value-of select="@n"/></xsl:attribute><xsl:attribute name="class">lb</xsl:attribute><xsl:text>⸤</xsl:text></xsl:element>
</xsl:template>
<xsl:template match="x:pb">
    <xsl:element name="span"><xsl:attribute name="title">page <xsl:value-of select="@n"/></xsl:attribute><xsl:attribute name="class">pb</xsl:attribute>L</xsl:element>
</xsl:template>

<xsl:template match="x:g">
    <xsl:apply-templates/>
</xsl:template>

<xsl:template match="x:gap">
    <xsl:element name="span"><xsl:attribute name="class">gap</xsl:attribute><xsl:attribute name="title">gap of <xsl:value-of select="@extent"/></xsl:attribute><xsl:text>_</xsl:text></xsl:element>
</xsl:template>

<xsl:template match="x:space">
    <xsl:element name="span">
        <xsl:attribute name="class">space</xsl:attribute>
        <xsl:attribute name="title">space of <xsl:value-of select="@quantity"/><xsl:text> </xsl:text> <xsl:value-of select="@unit"/>s</xsl:attribute>
        <xsl:text>_</xsl:text>
    </xsl:element>
</xsl:template>

<xsl:template match="x:editor">
    <span class="editor"><xsl:apply-templates /></span>
</xsl:template>

<xsl:template match="x:subst">
    <xsl:apply-templates/>
</xsl:template>
<xsl:template match="x:choice">
    <xsl:apply-templates/>
</xsl:template>
<xsl:template match="x:del">
    <del title="deleted"><xsl:apply-templates /></del>
</xsl:template>

<xsl:template match="x:sic">
    <span class="sic" title="sic"><xsl:apply-templates /></span>
</xsl:template>
<xsl:template match="x:orig">
    <del title="original text"><xsl:apply-templates /></del>
</xsl:template>

<xsl:template match="x:add">
    <ins class="add" title="inserted"><xsl:apply-templates /></ins>
</xsl:template>

<xsl:template match="x:corr">
    <ins class="corr" title="editor's correction"><xsl:apply-templates /></ins>
</xsl:template>


<xsl:template match="x:note">
    <span class="note inline"><xsl:apply-templates /></span>
</xsl:template>
<xsl:template match="x:note[@place='above']">
    <span class="note super"><xsl:apply-templates /></span>
</xsl:template>

<xsl:template match="x:note[@place='foot']">
    <xsl:element name="span">
        <xsl:attribute name="class">hiddennote</xsl:attribute>
        <xsl:attribute name="title">
            <xsl:value-of select="text()"/>
        </xsl:attribute>
        <xsl:text>*</xsl:text>
    </xsl:element>
</xsl:template>

<xsl:template match="x:caesura">
    <br/>
</xsl:template>

<xsl:template match="x:hi"> 
    <xsl:element name="span"> 
        <xsl:attribute name="class">hi</xsl:attribute> 
        <xsl:attribute name="title">marked by <xsl:value-of select="@rend"/></xsl:attribute> 
        <xsl:apply-templates/> 
    </xsl:element> 
</xsl:template>

</xsl:stylesheet>
