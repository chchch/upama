<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="text" encoding="UTF-8" omit-xml-declaration="yes"/>

<xsl:template match="p">
<xsl:text>
\pstart
</xsl:text>
<xsl:apply-templates/><xsl:text>
\pend

</xsl:text>
</xsl:template>

<xsl:template match="/l">
<xsl:text>
\pstart
</xsl:text>
<xsl:apply-templates/><xsl:text>
\pend

</xsl:text>
</xsl:template>

<xsl:template match="lg">
<xsl:text>
\setstanzaindents{2,2,2,2,2}
\stanza[\smallskip]

</xsl:text><xsl:apply-templates/><xsl:text>

</xsl:text>
</xsl:template>

<xsl:template match="lg[@type='quote']">
<xsl:text>

</xsl:text>
<xsl:apply-templates/>
</xsl:template>
<xsl:template match="lg[@type='quote']/l">
<xsl:apply-templates/>
<xsl:text>

</xsl:text>
</xsl:template>

<xsl:template match="lg[@type='quote']/l[position()=last()]">
<xsl:text>\smallskip
</xsl:text>
<xsl:apply-templates/>
<xsl:text>\\
</xsl:text>
</xsl:template>

<xsl:template match="lg[not(@type='quote')]/l">
<xsl:text>\large </xsl:text>
<xsl:apply-templates/><xsl:text>&amp;
</xsl:text>
</xsl:template>

<xsl:template match="lg[not(@type='quote')]/l[position()=last()]">
<xsl:text>\large </xsl:text>
<xsl:apply-templates/><xsl:text>\&amp;
</xsl:text>
</xsl:template>

<xsl:template match="milestone">
<xsl:variable name="no" select="@n"/>
<xsl:text>(From </xsl:text><xsl:value-of select="$no"/><xsl:text>)</xsl:text>
</xsl:template>

<xsl:template match="sub">
<xsl:text>\textsubscript{</xsl:text><xsl:apply-templates/><xsl:text>}</xsl:text>
</xsl:template>

<xsl:template match="sup">
<xsl:text>\textsuperscript{</xsl:text><xsl:apply-templates/><xsl:text>}</xsl:text>
</xsl:template>

<xsl:template match="label">
<xsl:text>\textsc{[</xsl:text><xsl:apply-templates /><xsl:text>]}</xsl:text>
</xsl:template>

<xsl:template match="unclear">
<xsl:text>\uwave{</xsl:text><xsl:apply-templates/><xsl:text>}</xsl:text>
</xsl:template>

<xsl:template match="subst">
    <xsl:apply-templates />
</xsl:template>

<xsl:template match="choice">
    <xsl:apply-templates />
</xsl:template>

<xsl:template match="choice/seg[1]">
    <xsl:text>&lt;</xsl:text>
    <xsl:apply-templates/>
    <xsl:text>&gt;</xsl:text>
</xsl:template>
<xsl:template match="choice/seg[position() > 1]">
    <xsl:text>/&lt;</xsl:text>
    <xsl:apply-templates/>
    <xsl:text>&gt;</xsl:text>
</xsl:template>

<xsl:template match="del">
    <xsl:text>\uuline{</xsl:text><xsl:apply-templates /><xsl:text>}</xsl:text>
</xsl:template>

<xsl:template match="sic">
        <xsl:text>\uwave{</xsl:text><xsl:apply-templates /><xsl:text>}</xsl:text>
</xsl:template>

<xsl:template match="surplus">
        <xsl:text>\uwave{</xsl:text><xsl:apply-templates /><xsl:text>}</xsl:text>
</xsl:template>


<xsl:template match="orig">
        <xsl:text>\uwave{</xsl:text><xsl:apply-templates /><xsl:text>}</xsl:text>
</xsl:template>

<xsl:template match="add">
        <xsl:text>\textbf{</xsl:text><xsl:apply-templates /><xsl:text>}</xsl:text>
</xsl:template>

<xsl:template match="corr">
        <xsl:text>(\textbf{</xsl:text><xsl:apply-templates /><xsl:text>})</xsl:text>
</xsl:template>

<xsl:template match="lb">
        <xsl:text>\textsc{(</xsl:text>
        <xsl:choose>
            <xsl:when test="@n">
                <xsl:text>l. </xsl:text><xsl:value-of select="@n"/>
            </xsl:when>
            <xsl:otherwise>
                <xsl:text>line break</xsl:text>
            </xsl:otherwise>
        </xsl:choose>
        <xsl:text>)}</xsl:text>
</xsl:template>

<xsl:template match="pb">
        <xsl:text>\textsc{(</xsl:text>
        <xsl:choose>
            <xsl:when test="@n">
                <xsl:text>f. </xsl:text><xsl:value-of select="@n"/>
            </xsl:when>
            <xsl:otherwise>
                <xsl:text>page break</xsl:text>
            </xsl:otherwise>
        </xsl:choose>
        <xsl:text>)}</xsl:text>
</xsl:template>

<xsl:template match="g">
    <xsl:text>\uwave{</xsl:text><xsl:apply-templates/><xsl:text>}</xsl:text>           
</xsl:template>

<xsl:template match="ptr">
</xsl:template>

<xsl:template match="supplied">
    <xsl:text>(\textbf{</xsl:text><xsl:apply-templates/><xsl:text>})</xsl:text>
</xsl:template>

<xsl:template match="locus">
    <xsl:text>\textsc{</xsl:text>
    <xsl:choose>
    <xsl:when test="@target">
        <xsl:text>&lt;</xsl:text><xsl:value-of select="@target"/><xsl:text>&gt;</xsl:text>
    </xsl:when>
    <xsl:otherwise>
        <xsl:text>&lt;</xsl:text><xsl:apply-templates/><xsl:text>&gt;</xsl:text>
    </xsl:otherwise>
    </xsl:choose>
    <xsl:text>}</xsl:text>
</xsl:template>

<xsl:template match="gap">
    <xsl:text>\textsc{(gap</xsl:text>
    <xsl:choose>
        <xsl:when test="@quantity">
            <xsl:text> of </xsl:text><xsl:value-of select="@quantity"/>
            <xsl:if test="@unit">
                <xsl:text> </xsl:text><xsl:value-of select="@unit"/>
                <xsl:if test="@quantity &gt; '1'">
                    <xsl:text>s</xsl:text>
                </xsl:if>
            </xsl:if>
        </xsl:when>
        <xsl:when test="@extent">
            <xsl:text> of </xsl:text><xsl:value-of select="@extent"/>
        </xsl:when>
    </xsl:choose>
    <xsl:if test="@reason">
        <xsl:text>, </xsl:text><xsl:value-of select="@reason"/>
    </xsl:if>
    <xsl:text>)}</xsl:text>
</xsl:template>

<xsl:template match="space">
    <xsl:text>\textsc{(space</xsl:text>
    <xsl:choose>
        <xsl:when test="@quantity">
            <xsl:text> of </xsl:text><xsl:value-of select="@quantity"/>
            <xsl:if test="@unit">
                <xsl:text> </xsl:text><xsl:value-of select="@unit"/>
                <xsl:if test="@quantity &gt; '1'">
                    <xsl:text>s</xsl:text>
                </xsl:if>
            </xsl:if>
        </xsl:when>
        <xsl:when test="@extent">
            <xsl:text> of </xsl:text><xsl:value-of select="@extent"/>
        </xsl:when>
    </xsl:choose>
    <xsl:if test="@reason">
        <xsl:text>, </xsl:text><xsl:value-of select="@reason"/>
    </xsl:if>
    <xsl:text>)}</xsl:text>
</xsl:template>

<xsl:template match="caesura">
<xsl:variable name="pretext" select="preceding::text()[1]"/>
<xsl:if test="normalize-space(substring($pretext,string-length($pretext))) != ''">
    <xsl:text>\-</xsl:text>
</xsl:if>
    <xsl:text>\caesura</xsl:text>
</xsl:template>

<xsl:template match="note">
    <xsl:text>\emph{</xsl:text><xsl:apply-templates/><xsl:text>}</xsl:text>
</xsl:template>

<xsl:template match="head[@type='sub']">
    <xsl:text>\textbf{</xsl:text><xsl:apply-templates/><xsl:text>}</xsl:text>
</xsl:template>

<xsl:template match="hi">
    <xsl:text>\textbf{</xsl:text><xsl:apply-templates/><xsl:text>}</xsl:text>
</xsl:template>

<xsl:template match="emph">
    <xsl:text>\emph{</xsl:text><xsl:apply-templates/><xsl:text>}</xsl:text>
</xsl:template>

<xsl:template match="foreign">
    <xsl:text>\emph{</xsl:text><xsl:apply-templates/><xsl:text>}</xsl:text>
</xsl:template>

<xsl:template match="metamark">
    <xsl:text>\textbf{</xsl:text><xsl:apply-templates/><xsl:text>}</xsl:text>
</xsl:template>

<xsl:template match="item">
    <xsl:apply-templates/>
</xsl:template>

<xsl:template match="item/quote">
    <xsl:apply-templates/>
</xsl:template>
<!--
<xsl:template match="item/quote/lg">
    <xsl:apply-templates/>
</xsl:template>

<xsl:template match="item/quote/lg/l">
    <xsl:apply-templates/>
</xsl:template>
-->
<xsl:template match="item/title">
    <xsl:text>\emph{</xsl:text><xsl:apply-templates/><xsl:text>}</xsl:text>
</xsl:template>

<!--xsl:template match="pc">
    <xsl:text>&#173;</xsl:text>
</xsl:template-->

</xsl:stylesheet>
