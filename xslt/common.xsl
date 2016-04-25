<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
                xmlns:x="http://www.tei-c.org/ns/1.0"
                exclude-result-prefixes="x">
<xsl:output method="html" omit-xml-declaration="yes"/>

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

<xsl:template match="x:div1">
    <div class="section">
        <xsl:apply-templates />
    </div>
</xsl:template>

<xsl:template match="x:div">
    <div class="section">
        <xsl:apply-templates />
    </div>
</xsl:template>

<xsl:template match="x:text//x:title">
    <xsl:element name="h2">
        <xsl:call-template name="lang"/>
        <xsl:apply-templates />
    </xsl:element>
</xsl:template>

<xsl:template match="x:text//x:subtitle">
    <xsl:element name="h3">
        <xsl:call-template name="lang"/>
        <xsl:apply-templates />
    </xsl:element>
</xsl:template>

<xsl:template match="x:milestone">
    <xsl:variable name="no" select="@n"/>
    <xsl:variable name="facs" select="/x:TEI/x:facsimile/x:graphic[@n=$no]/@url"/>
    <xsl:choose>
    <xsl:when test="string($facs)">
        <xsl:element name="a">
            <xsl:attribute name="href">
                <xsl:value-of select="$facs"/>
            </xsl:attribute>
                <xsl:attribute name="class">milestone<xsl:call-template name="ignore" /></xsl:attribute>
                <xsl:attribute name="lang">en</xsl:attribute>
            <xsl:text>(From </xsl:text>
            <xsl:if test="@unit">
                <xsl:value-of select="@unit"/>
                <xsl:text> </xsl:text>
            </xsl:if>
            <xsl:value-of select="$no"/>
            <xsl:text>)</xsl:text>
        </xsl:element>
    </xsl:when>
    <xsl:otherwise>
        <xsl:element name="span">
            <xsl:attribute name="class">milestone<xsl:call-template name="ignore" /></xsl:attribute>
                <xsl:attribute name="lang">en</xsl:attribute>
            <xsl:text>(From </xsl:text>
            <xsl:if test="@unit">
                <xsl:value-of select="@unit"/>
                <xsl:text> </xsl:text>
            </xsl:if>
            <xsl:value-of select="$no"/>
            <xsl:text>)</xsl:text>
        </xsl:element>
    </xsl:otherwise>
    </xsl:choose>
</xsl:template>

<xsl:template match="x:p">
    <xsl:element name="p">
        <xsl:call-template name="lang"/>
        <xsl:apply-templates/>
    </xsl:element>
</xsl:template>

<xsl:template match="x:sub">
    <sub>
        <xsl:apply-templates/>
    </sub>
</xsl:template>

<xsl:template match="x:sup">
    <sup>
        <xsl:apply-templates/>
    </sup>
</xsl:template>

<xsl:template match="x:editor">
    <span class="editor" lang="en"><xsl:apply-templates /></span>
</xsl:template>

<xsl:template name="ignore">
    <xsl:if test="@ignored='TRUE'"> ignored</xsl:if>
    <xsl:if test="@upama-show='TRUE'"> upama-show</xsl:if>
</xsl:template>

<xsl:template match="x:unclear">
    <xsl:element name="span">
        <xsl:attribute name="class">unclear<xsl:call-template name="ignore" /></xsl:attribute>
        <xsl:attribute name="title">unclear</xsl:attribute>
        <xsl:apply-templates/>
    </xsl:element>
</xsl:template>

<xsl:template match="x:subst">
    <xsl:element name="span">
    <xsl:attribute name="class">subst<xsl:call-template name="ignore"/></xsl:attribute>
    <xsl:apply-templates />
    </xsl:element>
</xsl:template>

<xsl:template match="x:choice">
    <xsl:element name="span">
    <xsl:attribute name="class">choice<xsl:call-template name="ignore"/></xsl:attribute>
    <xsl:apply-templates />
    </xsl:element>
</xsl:template>

<xsl:template match="x:del">
    <xsl:element name="del">
        <xsl:attribute name="title">deleted</xsl:attribute>
        <xsl:if test="@ignored='TRUE' or @upama-show='TRUE'">
            <xsl:attribute name="class"><xsl:call-template name="ignore"/></xsl:attribute>
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
    <xsl:variable name="pageno" select="@n"/>
    <xsl:variable name="facs" select="/x:TEI/x:facsimile/x:graphic[@n=$pageno]/@url"/>
    <xsl:choose>
    <xsl:when test="string($facs)">
        <xsl:element name="a">
            <xsl:attribute name="href">
                <xsl:value-of select="$facs"/>
            </xsl:attribute>
            <xsl:attribute name="title">folio <xsl:value-of select="@n"/></xsl:attribute>
                <xsl:attribute name="class">pb<xsl:call-template name="ignore" /></xsl:attribute>
                <xsl:attribute name="lang">en</xsl:attribute>
            <xsl:text>L</xsl:text>
        </xsl:element>
    </xsl:when>
    <xsl:otherwise>
        <xsl:element name="span">
            <xsl:attribute name="title">folio <xsl:value-of select="@n"/></xsl:attribute>
            <xsl:attribute name="class">pb<xsl:call-template name="ignore" /></xsl:attribute>
            <xsl:attribute name="lang">en</xsl:attribute>
        <xsl:text>L</xsl:text>
        </xsl:element>
    </xsl:otherwise>
    </xsl:choose>
</xsl:template>

<xsl:template match="x:g">
    <xsl:choose>
        <xsl:when test="@ignored='TRUE' or @upama-show='TRUE'">
            <xsl:element name="span">
            <xsl:attribute name="class"><xsl:call-template name="ignore"/></xsl:attribute>
                <xsl:apply-templates />
            </xsl:element>
        </xsl:when>
        <xsl:otherwise>
            <xsl:apply-templates />
        </xsl:otherwise>
    </xsl:choose>
</xsl:template>

<xsl:template name="repeat">
    <xsl:param name="output" />
    <xsl:param name="count" />
    <xsl:if test="$count &gt; 0">
        <xsl:value-of select="$output" />
        <xsl:call-template name="repeat">
            <xsl:with-param name="output" select="$output" />
            <xsl:with-param name="count" select="$count - 1" />
        </xsl:call-template>
    </xsl:if>
</xsl:template>

<xsl:template match="x:gap">
    <xsl:element name="span">
        <xsl:attribute name="lang">en</xsl:attribute>
        <xsl:attribute name="class">gap<xsl:call-template name="ignore" /></xsl:attribute>
        <xsl:attribute name="title">
            <xsl:text>gap</xsl:text>
                <xsl:choose>
                    <xsl:when test="@quantity">
                        <xsl:text> of </xsl:text><xsl:value-of select="@quantity"/>
                        <xsl:if test="@unit">
                        <xsl:text> </xsl:text><xsl:value-of select="@unit"/>
                            <xsl:if test="not(@quantity = '1')">
                                <xsl:text>s</xsl:text>
                            </xsl:if>
                        </xsl:if>
                    </xsl:when>
                    <xsl:when test="@extent">
                        <xsl:text> of </xsl:text><xsl:value-of select="@extent"/>
                    </xsl:when>
                </xsl:choose>
                <xsl:if test="@reason">
                    <xsl:text> (</xsl:text><xsl:value-of select="@reason"/><xsl:text>)</xsl:text>
                </xsl:if>
        </xsl:attribute>
        <xsl:choose>
            <xsl:when test="count(./*) &gt; 0">
                <xsl:text>[</xsl:text>
                <xsl:apply-templates/>
                <xsl:text>]</xsl:text>
            </xsl:when>
            <xsl:otherwise>
                <xsl:element name="span">
                <xsl:attribute name="class">ignored</xsl:attribute>
                <xsl:text>...</xsl:text>
                <xsl:choose>
                    <xsl:when test="@quantity &gt; 1">
                        <xsl:call-template name="repeat">
                            <xsl:with-param name="output"><xsl:text>..</xsl:text></xsl:with-param>
                            <xsl:with-param name="count" select="@quantity"/>
                        </xsl:call-template>

                    </xsl:when>
                    <xsl:when test="@extent">
                        <xsl:variable name="extentnum" select="translate(@extent,translate(@extent,'0123456789',''),'')"/>
                        <xsl:if test="number($extentnum) &gt; 1">
                            <xsl:call-template name="repeat">
                                <xsl:with-param name="output"><xsl:text>..</xsl:text></xsl:with-param>
                                <xsl:with-param name="count" select="$extentnum"/>
                            </xsl:call-template>
                        </xsl:if>
                    </xsl:when>
                </xsl:choose>
                </xsl:element>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:element>
</xsl:template>

<xsl:template match="x:space">
    <xsl:element name="span">
        <xsl:attribute name="lang">en</xsl:attribute>
        <xsl:attribute name="class">space<xsl:call-template name="ignore" /></xsl:attribute>
        <xsl:attribute name="title">
            <xsl:text>space</xsl:text>
            <xsl:if test="@quantity">
                <xsl:text> of </xsl:text><xsl:value-of select="@quantity"/>
                <xsl:if test="@unit">
                <xsl:text> </xsl:text><xsl:value-of select="@unit"/>
                    <xsl:if test="not(@quantity = '1')">
                        <xsl:text>s</xsl:text>
                    </xsl:if>
                </xsl:if>
            </xsl:if>
        </xsl:attribute>
        <xsl:choose>
            <xsl:when test="count(./*) &gt; 0">
                <xsl:apply-templates/>
            </xsl:when>
            <xsl:otherwise>
                <xsl:element name="span">
                <xsl:attribute name="class">ignored</xsl:attribute>
                <xsl:text>_</xsl:text>
                <xsl:choose>
                    <xsl:when test="@quantity &gt; 1">
                        <xsl:call-template name="repeat">
                            <xsl:with-param name="output"><xsl:text>_</xsl:text></xsl:with-param>
                            <xsl:with-param name="count" select="@quantity"/>
                        </xsl:call-template>

                    </xsl:when>
                    <xsl:when test="@extent">
                        <xsl:variable name="extentnum" select="translate(@extent,translate(@extent,'0123456789',''),'')"/>
                        <xsl:if test="number($extentnum) &gt; 1">
                            <xsl:call-template name="repeat">
                                <xsl:with-param name="output"><xsl:text>_</xsl:text></xsl:with-param>
                                <xsl:with-param name="count" select="$extentnum"/>
                            </xsl:call-template>
                        </xsl:if>
                    </xsl:when>
                </xsl:choose>
                </xsl:element>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:element>
</xsl:template>

<xsl:template match="x:note">
    <xsl:element name="span">
        <xsl:call-template name="lang"/>
        <xsl:attribute name="class">note
            <xsl:choose>
                <xsl:when test="@place='above'"> super</xsl:when>
                <xsl:otherwise> inline</xsl:otherwise>
            </xsl:choose>
            <xsl:call-template name="ignore" />
        </xsl:attribute>
        <xsl:apply-templates />
    </xsl:element>
</xsl:template>

<xsl:template match="x:note[@place='foot']">
    <xsl:element name="span">
        <xsl:attribute name="class">hidenote<xsl:call-template name="ignore"/></xsl:attribute>
        <xsl:attribute name="title">
            <xsl:value-of select="text()"/>
        </xsl:attribute>
        <xsl:element name="span">
            <xsl:attribute name="class">ignored</xsl:attribute>
            <xsl:text>*</xsl:text>
        </xsl:element>
        <xsl:element name="span">
            <xsl:attribute name="style">display: none;</xsl:attribute>
            <xsl:apply-templates/>
        </xsl:element>
    </xsl:element>
</xsl:template>


<xsl:template match="x:hi">
    <xsl:element name="span">
        <xsl:attribute name="class">hi<xsl:call-template name="ignore" /></xsl:attribute>
        <xsl:attribute name="title">marked by <xsl:value-of select="@rend"/></xsl:attribute>
        <xsl:apply-templates/>
    </xsl:element>
</xsl:template>

<xsl:template match="x:foreign">
    <xsl:element name="span">
        <xsl:call-template name="lang"/>
        <xsl:apply-templates/>
    </xsl:element>
</xsl:template>

<xsl:template match="@upama-show">
    <xsl:copy>
    <xsl:attribute name="class">upama-show</xsl:attribute>
    <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
</xsl:template>

<xsl:template match="@*|node()">
    <xsl:copy><xsl:apply-templates select="@* | node()"/></xsl:copy>
</xsl:template>

</xsl:stylesheet>
