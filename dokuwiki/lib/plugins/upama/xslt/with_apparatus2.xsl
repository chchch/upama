<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
                xmlns:x="http://www.tei-c.org/ns/1.0"
                exclude-result-prefixes="x">

<xsl:import href="definitions.xsl"/>
<xsl:import href="common.xsl"/>

<xsl:output omit-xml-declaration="yes"/>

<xsl:template match="x:p[@xml:id]">
    <xsl:variable name="xmlid" select="@xml:id"/>
    <xsl:element name="div">
        <xsl:attribute name="class">
            <xsl:text>para upama-block</xsl:text>
        </xsl:attribute>
        <xsl:attribute name="id"><xsl:value-of select="$xmlid"/></xsl:attribute>
        <xsl:element name="div">
            <xsl:attribute name="class">
                <xsl:text>maintext</xsl:text>
                <xsl:if test="@style">
                    <xsl:text> </xsl:text><xsl:value-of select="@style"/>
                </xsl:if>
            </xsl:attribute>
                <!--xsl:call-template name="lang"/-->
                <xsl:comment>SECTION_START</xsl:comment>
                <xsl:comment><xsl:value-of select="$xmlid"/>=sa<xsl:text>=UPAMA_SECTION</xsl:text></xsl:comment>
                <xsl:apply-templates/>
                <xsl:comment>SECTION_END</xsl:comment>
        </xsl:element>
        <xsl:element name="div">
            <xsl:attribute name="class">apparatus</xsl:attribute>
            <!--xsl:attribute name="data-target"><xsl:value-of select="translate(@target,'#','')"/></xsl:attribute-->
            <xsl:if test=".//x:app">
                <xsl:element name="div">
                    <xsl:attribute name="class">apparatus2</xsl:attribute>
                    <xsl:for-each select=".//x:app">
                        <xsl:element name="div">
                            <xsl:attribute name="class">varcontainer</xsl:attribute>
                            <xsl:call-template name="lemma"/>
                            <xsl:for-each select="x:rdg">
                                <xsl:element name="span">
                                    <xsl:attribute name="class">rdg</xsl:attribute>
                                    <xsl:attribute name="lang">sa</xsl:attribute>
                                    <xsl:apply-templates/>
                                    <xsl:call-template name="splitwit"/>
                                </xsl:element>
                            </xsl:for-each>
                        </xsl:element>
                    </xsl:for-each>
                </xsl:element>
            </xsl:if>

            <xsl:variable name="app2" select="//x:div2[@type='apparatus' and @target=concat('#',$xmlid)] | //x:ab[@type='apparatus' and @corresp=concat('#',$xmlid)]"/>
            <xsl:if test="$app2">
                <xsl:variable name="id2" select="$app2/@xml:id"/>
                <xsl:element name="div">
                    <xsl:attribute name="class">apparatus2 upama-block</xsl:attribute>
                    <xsl:variable name="target" select="$app2/@target | $app2/@corresp"/>
                    <xsl:attribute name="data-target"><xsl:value-of select="translate($target,'#','')"/></xsl:attribute>
                    <xsl:attribute name="id"><xsl:value-of select="$id2"/></xsl:attribute>
                    <xsl:element name="div">
                        <xsl:attribute name="class">maintext</xsl:attribute>
                        <!--xsl:call-template name="lang"/-->
                        <xsl:comment>SECTION_START</xsl:comment>
                        <xsl:comment><xsl:value-of select="$id2"/>=en<xsl:text>=UPAMA_SECTION</xsl:text></xsl:comment>
                        <xsl:element name="ul">
                            <xsl:attribute name="class">accordion</xsl:attribute>
                            <xsl:apply-templates select="$app2/*"/>
                        </xsl:element>
                        <xsl:comment>SECTION_END</xsl:comment>
                    </xsl:element>
                </xsl:element>
            </xsl:if>
        </xsl:element>
    </xsl:element>
</xsl:template>

<xsl:template match="x:div2[@type='apparatus'] | x:ab[@type='apparatus']"/>

<xsl:template match="x:lg[@type='verse' or @xml:id] | x:l[@xml:id]">
    <xsl:element name="div">
        <xsl:attribute name="class">
            <xsl:text>verse upama-block</xsl:text>
            <xsl:if test="@type='mangala'"><xsl:text> mangala</xsl:text></xsl:if>
        </xsl:attribute>
        <xsl:variable name="xmlid" select="@xml:id"/>
        <xsl:if test="$xmlid">
        <xsl:attribute name="id"><xsl:value-of select="$xmlid"/></xsl:attribute>
        </xsl:if>
        <xsl:element name="div">
            <xsl:attribute name="class">
                <xsl:text>maintext</xsl:text>
                <xsl:if test="@style">
                    <xsl:text> </xsl:text><xsl:value-of select="@style"/>
                </xsl:if>
            </xsl:attribute>
            <!--xsl:call-template name="lang"/-->
            <xsl:comment>SECTION_START</xsl:comment>
            <xsl:comment><xsl:value-of select="$xmlid"/>=sa<xsl:text>=UPAMA_SECTION</xsl:text></xsl:comment>
            <xsl:apply-templates/>
            <xsl:comment>SECTION_END</xsl:comment>
        </xsl:element>
        <xsl:element name="div">
            <xsl:attribute name="class">apparatus</xsl:attribute>
            <!--xsl:attribute name="data-target"><xsl:value-of select="translate(@target,'#','')"/></xsl:attribute-->
            <xsl:if test=".//x:app">
                <xsl:element name="div">
                    <xsl:attribute name="class">apparatus2</xsl:attribute>
                    <xsl:for-each select=".//x:app">
                        <xsl:element name="div">
                            <xsl:attribute name="class">varcontainer</xsl:attribute>
                            <xsl:call-template name="lemma"/>
                            <xsl:for-each select="x:rdg">
                                <xsl:element name="span">
                                    <xsl:attribute name="class">rdg</xsl:attribute>
                                    <xsl:attribute name="lang">sa</xsl:attribute>
                                    <xsl:apply-templates select="./node()"/>
                                    <xsl:call-template name="splitwit"/>
                                </xsl:element>
                            </xsl:for-each>
                        </xsl:element>
                    </xsl:for-each>
                </xsl:element>
            </xsl:if>


            <xsl:variable name="app2" select="//x:div2[@type='apparatus' and @target=concat('#',$xmlid)] | //x:ab[@type='apparatus' and @corresp=concat('#',$xmlid)]"/>
            <xsl:if test="$app2">
                <xsl:variable name="id2" select="$app2/@xml:id"/>
                <xsl:element name="div">
                    <xsl:attribute name="class">apparatus2 upama-block</xsl:attribute>
                    <xsl:variable name="target" select="$app2/@target | $app2/@corresp"/>
                    <xsl:attribute name="data-target"><xsl:value-of select="translate($target,'#','')"/></xsl:attribute>
                    <xsl:attribute name="id"><xsl:value-of select="$id2"/></xsl:attribute>
                    <xsl:element name="div">
                        <xsl:attribute name="class">maintext</xsl:attribute>
                        <xsl:comment>SECTION_START</xsl:comment>
                        <xsl:comment><xsl:value-of select="$id2"/>=en<xsl:text>=UPAMA_SECTION</xsl:text></xsl:comment>
                        <xsl:element name="ul">
                            <xsl:attribute name="class">accordion</xsl:attribute>
                            <xsl:apply-templates select="$app2/*"/>
                        </xsl:element>
                        <xsl:comment>SECTION_END</xsl:comment>
                    </xsl:element>
                </xsl:element>
            </xsl:if>
        </xsl:element>

    </xsl:element>
</xsl:template>

<xsl:template name="lemma">
    <span>
        <xsl:attribute name="class">lemma</xsl:attribute>
        <xsl:apply-templates select="x:lem/node()"/>
    </span>
    <xsl:if test="x:lem/@wit">
        <span>
            <xsl:attribute name="class">lem-wit</xsl:attribute>
            <xsl:call-template name="splitwit">
                <xsl:with-param name="mss" select="x:lem/@wit"/>
            </xsl:call-template>
        </span>
    </xsl:if>
    <xsl:text> </xsl:text>
</xsl:template>
<!--xsl:template match="x:lg[@type='mangala']">
    <xsl:element name="div">
        <xsl:attribute name="class">mangala upama-block</xsl:attribute>
        <xsl:if test="@xml:id">
        <xsl:attribute name="id"><xsl:value-of select="@xml:id"/></xsl:attribute>
        </xsl:if>
        <xsl:element name="div">
            <xsl:attribute name="class">maintext</xsl:attribute>
            <xsl:comment>SECTION_START</xsl:comment>
            <xsl:comment><xsl:value-of select="@xml:id"/>=sa<xsl:text>=UPAMA_SECTION</xsl:text></xsl:comment>
            <xsl:apply-templates/>
            <xsl:comment>SECTION_END</xsl:comment>
        </xsl:element>
    </xsl:element>
</xsl:template-->

<!--xsl:template match="x:lg">
    <xsl:element name="div">
        <xsl:attribute name="class">verse</xsl:attribute>
        <xsl:element name="div">
            <xsl:attribute name="class">maintext</xsl:attribute>
            <xsl:attribute name="lang">sa</xsl:attribute>
            <xsl:apply-templates/>
        </xsl:element>
    </xsl:element>
</xsl:template-->


<xsl:template match="x:l">
    <xsl:element name="div">
        <xsl:choose>
            <xsl:when test="@xml:id">
                <xsl:attribute name="class">verseline upama-block</xsl:attribute>
                <xsl:attribute name="id"><xsl:value-of select="@xml:id"/></xsl:attribute>
                <xsl:element name="div">
                    <xsl:attribute name="class">
                        <xsl:text>maintext</xsl:text>
                        <xsl:if test="@style">
                            <xsl:text> </xsl:text><xsl:value-of select="@style"/>
                        </xsl:if>
                    </xsl:attribute>
                    <!--xsl:call-template name="lang"/-->
                    <xsl:comment>SECTION_START</xsl:comment>
                    <xsl:comment><xsl:value-of select="@xml:id"/>=sa<xsl:text>=UPAMA_SECTION</xsl:text></xsl:comment>
                    <xsl:apply-templates/>
                    <xsl:comment>SECTION_END</xsl:comment>
                </xsl:element>

                <xsl:element name="div">
                    <xsl:attribute name="class">apparatus</xsl:attribute>
                    <!--xsl:attribute name="data-target"><xsl:value-of select="translate(@target,'#','')"/></xsl:attribute-->
                    <xsl:if test=".//x:app">
                        <xsl:element name="div">
                            <xsl:attribute name="class">apparatus2</xsl:attribute>
                            <xsl:for-each select=".//x:app">
                                <xsl:element name="div">
                                    <xsl:attribute name="class">varcontainer</xsl:attribute>
                                    <xsl:call-template name="lemma"/>
                                    <xsl:for-each select="x:rdg">
                                        <xsl:element name="span">
                                            <xsl:attribute name="class">rdg</xsl:attribute>
                                            <xsl:attribute name="lang">sa</xsl:attribute>
                                            <xsl:apply-templates select="./node()"/>
                                            <xsl:call-template name="splitwit"/>
                                        </xsl:element>
                                    </xsl:for-each>
                                </xsl:element>
                            </xsl:for-each>
                        </xsl:element>
                    </xsl:if>


                    <xsl:variable name="app2" select="//x:div2[@type='apparatus' and @target=concat('#',$xmlid)] | //x:ab[@type='apparatus' and @corresp=concat('#',$xmlid)]"/>
                    <xsl:if test="$app2">
                        <xsl:variable name="id2" select="$app2/@xml:id"/>
                        <xsl:element name="div">
                            <xsl:variable name="target" select="$app2/@target | $app2/@corresp"/>
                            <xsl:attribute name="data-target"><xsl:value-of select="translate($target,'#','')"/></xsl:attribute>
                            <xsl:attribute name="class">apparatus2 upama-block</xsl:attribute>
                            <xsl:attribute name="id"><xsl:value-of select="$id2"/></xsl:attribute>
                            <xsl:element name="div">
                                <xsl:attribute name="class">maintext</xsl:attribute>
                                <xsl:comment>SECTION_START</xsl:comment>
                                <xsl:comment><xsl:value-of select="$id2"/>=en<xsl:text>=UPAMA_SECTION</xsl:text></xsl:comment>
                                <xsl:element name="ul">
                                    <xsl:attribute name="class">accordion</xsl:attribute>
                                    <xsl:apply-templates select="$app2/*"/>
                                </xsl:element>
                                <xsl:comment>SECTION_END</xsl:comment>
                            </xsl:element>
                        </xsl:element>
                    </xsl:if>
                </xsl:element>
            </xsl:when>
            <xsl:otherwise>
                <xsl:attribute name="class">verseline</xsl:attribute>
                <xsl:apply-templates/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:element>
</xsl:template>

<xsl:template match="x:quote/x:lg/x:l">
    <xsl:element name="span">
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
