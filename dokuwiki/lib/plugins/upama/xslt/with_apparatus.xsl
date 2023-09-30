<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
                xmlns:x="http://www.tei-c.org/ns/1.0"
                exclude-result-prefixes="x">

<xsl:import href="definitions.xsl"/>
<xsl:import href="common.xsl"/>

<xsl:output omit-xml-declaration="yes" encoding="utf-8" method="html"/>

<xsl:variable name="testurl" select="/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:listWit[@resp='upama']/x:witness[1]/@ref"/>
<xsl:variable name="urlprefix">
    <xsl:choose>
        <xsl:when test="contains($testurl,'?')">&amp;</xsl:when>
        <xsl:otherwise>?</xsl:otherwise>
    </xsl:choose>
</xsl:variable>

<xsl:template match="x:p[@xml:id]">
        <xsl:element name="div">
            <xsl:attribute name="class">
                <xsl:text>para upama-block</xsl:text>
            </xsl:attribute>
            <xsl:attribute name="id"><xsl:value-of select="@xml:id"/></xsl:attribute>
            <xsl:apply-templates />
        </xsl:element>
</xsl:template>

<xsl:template match="x:maintext">
    <!--td class="maintext hyphenate" lang="sa"-->
    <!--div class="maintext" lang="sa"-->
    <div>
        <xsl:attribute name="class">
            <xsl:text>maintext</xsl:text>
            <xsl:if test="../@style">
                <xsl:text> </xsl:text><xsl:value-of select="../@style"/>
            </xsl:if>
        </xsl:attribute>
    <xsl:comment>SECTION_START</xsl:comment>
    <xsl:comment><xsl:value-of select="../@xml:id"/>=sa<xsl:text>=UPAMA_SECTION</xsl:text></xsl:comment>
        <xsl:apply-templates />
    <xsl:comment>SECTION_END</xsl:comment>
    </div><xsl:text>
    </xsl:text>
</xsl:template>

<!--<xsl:template match="x:div2[@type='apparatus']/x:maintext">
    <div class="maintext">
        <xsl:comment>SECTION_START</xsl:comment>
        <xsl:comment><xsl:value-of select="../@xml:id"/>=en<xsl:text>=UPAMA_SECTION</xsl:text></xsl:comment>
        <xsl:element name="ul">
            <xsl:attribute name="class">accordion</xsl:attribute>
            <xsl:apply-templates />
        </xsl:element>
        <xsl:comment>SECTION_END</xsl:comment>
    </div><xsl:text>
    </xsl:text>
</xsl:template>
-->
<xsl:template match="x:div[@type='apparatus']">
    <xsl:element name="div">
        <xsl:attribute name="class">apparatus</xsl:attribute>
        <xsl:attribute name="data-target"><xsl:value-of select="translate(@target,'#','')"/></xsl:attribute>
        <xsl:call-template name="app2"/>
        <xsl:apply-templates/>
    </xsl:element>
</xsl:template>

<xsl:template name="app2">
    <xsl:variable name="target"><xsl:value-of select="translate(@target,'#','')"/></xsl:variable>
    <xsl:if test="$target">
        <div class="apparatus2">
        <xsl:for-each select="//*[@xml:id=$target]/x:maintext//x:app">
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
        </div>
    </xsl:if>
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

<xsl:template match="x:listApp">
    <xsl:element name="div">
        <xsl:attribute name="class">variorum</xsl:attribute>
        <xsl:if test="@exclude">
            <xsl:attribute name="data-exclude">
                <xsl:value-of select="translate(@exclude,'#','')"/>
            </xsl:attribute>        
            <xsl:element name="span">
                <xsl:attribute name="class">bracketopen bracketclose excludebracket</xsl:attribute>
                <xsl:call-template name="splitexclude">
                    <xsl:with-param name="list" select="@exclude"/>
                </xsl:call-template>
            </xsl:element>
        </xsl:if>
        <xsl:text>
        </xsl:text>
            <xsl:apply-templates/>
    </xsl:element>
    <xsl:text>
    </xsl:text>
</xsl:template>

<xsl:template name="split">
    <xsl:param name="scrollid" select="ancestor::*[@xml:id]/@xml:id"/>
    <xsl:param name="list"/>
    <xsl:param name="mss" select="$list"/>

    <xsl:if test="string-length($mss)">
        <xsl:if test="not($mss=$list)">, </xsl:if>
        <xsl:variable name="msstring" select="substring-before(
                                    concat($mss,' '),
                                  ' ')"/>
        <xsl:variable name="cleanstr" select="substring-after($msstring,'#')"/>
        <xsl:variable name="witel" select="/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:listWit[@resp='upama']/x:witness[@xml:id=$cleanstr]"/>

        <xsl:choose>
         <xsl:when test="$witel/@corresp">
          <xsl:element name="span">
            <xsl:attribute name="class">msid msgroup</xsl:attribute>
            <span class="msgroupname">
                <xsl:attribute name="data-msid"><xsl:value-of select="$cleanstr"/></xsl:attribute>
                <xsl:apply-templates select="/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:listWit[@resp='upama']/x:witness[@xml:id=$cleanstr]/x:idno/node()"/>
            </span>
            <span class="msdetail">
                <xsl:text>(</xsl:text>
                    <xsl:call-template name="split">
                        <xsl:with-param name="list" select="$witel/@corresp"/>
                    </xsl:call-template>
                <xsl:text>)</xsl:text>
            </span>
          </xsl:element>
         </xsl:when>
         <xsl:otherwise>
           <xsl:element name="a">
            <xsl:attribute name="data-msid"><xsl:value-of select="$cleanstr"/></xsl:attribute>
            <xsl:choose>
             <xsl:when test="./x:rdg[@wit=$msstring][not(@type='main')]">
              <xsl:attribute name="class">msid mshover</xsl:attribute>
             </xsl:when>
             <xsl:otherwise>
                <xsl:attribute name="class">msid</xsl:attribute>
             </xsl:otherwise>
            </xsl:choose>
            <xsl:attribute name="href"><xsl:value-of select="$witel/@ref"/><xsl:value-of disable-output-escaping="yes" select="$urlprefix"/>upama_scroll=<xsl:value-of select="$scrollid"/></xsl:attribute>
            <xsl:apply-templates select="/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:listWit[@resp='upama']/x:witness[@xml:id=$cleanstr]/x:idno/node()"/>
           </xsl:element>
         </xsl:otherwise>
        </xsl:choose>
        <xsl:call-template name="split">
            <xsl:with-param name="mss" select=
                "substring-after($mss, ' ')"/>
        </xsl:call-template>
    </xsl:if>
</xsl:template>

<xsl:template name="splitexclude">
    <xsl:param name="list"/>
    <xsl:param name="mss" select="$list"/>
    <xsl:variable name="msstring" select="substring-before(
                                            concat($mss,' '),
                                          ' ')"/>
    <xsl:variable name="cleanstr" select="substring-after($msstring,'#')"/>
    <xsl:variable name="witel" select="/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:listWit[@resp='upama']/x:witness[@xml:id=$cleanstr]"/>

    <xsl:if test="string-length($mss)">
        <xsl:if test="not($mss=$list)">, </xsl:if>
    
        <xsl:choose>
         <xsl:when test="$witel/@corresp">
          <xsl:element name="span">
            <xsl:attribute name="class">msid exclude msgroup</xsl:attribute>
            <span class="msgroupname">
                <xsl:apply-templates select="/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:listWit[@resp='upama']/x:witness[@xml:id=$cleanstr]/x:idno/node()"/>
            </span>
            <span class="msdetail">
                <xsl:text>(</xsl:text>
                    <xsl:call-template name="splitexclude">
                        <xsl:with-param name="list" select="$witel/@corresp"/>
                    </xsl:call-template>
                <xsl:text>)</xsl:text>
            </span>
          </xsl:element>
         </xsl:when>
         <xsl:otherwise>
            <xsl:element name="span">
                 <xsl:attribute name="class">msid exclude</xsl:attribute>
                 <xsl:apply-templates select="/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:listWit[@resp='upama']/x:witness[@xml:id=$cleanstr]/x:idno/node()"/>
            </xsl:element>
           </xsl:otherwise>
          </xsl:choose>
            <xsl:call-template name="splitexclude">
                <xsl:with-param name="mss" select=
                    "substring-after($mss, ' ')"/>
            </xsl:call-template>
        </xsl:if>
</xsl:template>

<xsl:template match="x:div[@type='apparatus']//x:rdgGrp">
    <xsl:apply-templates/>
</xsl:template>

<xsl:template match="x:div[@type='apparatus']//x:app[not(@loc)]">
    <xsl:apply-templates />
</xsl:template>
<xsl:template match="x:div[@type='apparatus']//x:lem">
    <xsl:apply-templates />
</xsl:template>

<xsl:template match="x:div[@type='apparatus']//x:rdg">
    <xsl:element name="span"><xsl:attribute name="data-ms"><xsl:value-of select="substring-after(@wit,'#')"/></xsl:attribute><xsl:attribute name="class">reading</xsl:attribute><xsl:attribute name="lang">sa</xsl:attribute><xsl:apply-templates /></xsl:element>
</xsl:template>

<xsl:template match="x:div[@type='apparatus']//x:rdg[@type='main']">
    <xsl:element name="span">
        <xsl:attribute name="class">variant</xsl:attribute>
        <xsl:attribute name="lang">sa</xsl:attribute>
        <xsl:apply-templates />
    </xsl:element>
</xsl:template>

<xsl:template match="x:div[@type='apparatus']//x:app[@loc]">
    <xsl:element name="span">
        <xsl:attribute name="data-loc">
            <xsl:value-of select="@loc"/>
        </xsl:attribute>
        <xsl:attribute name="class">varcontainer</xsl:attribute>
        <xsl:call-template name="split">
            <xsl:with-param name="list" select="@mss"/>
        </xsl:call-template>
        <xsl:text>:&#160;</xsl:text>
        <xsl:apply-templates />
    </xsl:element><xsl:text>
    </xsl:text>
</xsl:template>

<xsl:template match="x:div[@type='apparatus']//x:rdgGrp/x:app">
    <xsl:element name="span">
        <xsl:attribute name="data-loc">
            <xsl:value-of select="@loc"/>
        </xsl:attribute>
        <xsl:attribute name="class">varcontainer</xsl:attribute>
        <xsl:call-template name="split">
            <xsl:with-param name="list" select="@mss"/>
        </xsl:call-template>
        <xsl:text>:&#160;</xsl:text>
        <xsl:apply-templates />
    </xsl:element><xsl:text> </xsl:text>
</xsl:template>

<xsl:template match="x:div[@type='apparatus']//x:rdgGrp/x:app[1]">
    <xsl:element name="span">
        <xsl:attribute name="data-loc">
            <xsl:value-of select="@loc"/>
        </xsl:attribute>
        <xsl:attribute name="class">varcontainer bracketopen</xsl:attribute>
        <!--xsl:element name="span">
            <xsl:attribute name="class">varbracket</xsl:attribute>
            <xsl:text>❲ </xsl:text>
        </xsl:element-->
        <xsl:call-template name="split">
            <xsl:with-param name="list" select="@mss"/>
        </xsl:call-template>
        <xsl:text>:&#160;</xsl:text>
        <xsl:apply-templates />
    </xsl:element><xsl:text> </xsl:text>
</xsl:template>

<xsl:template match="x:div[@type='apparatus']//x:rdgGrp/x:app[last()]">
    <xsl:element name="span">
        <xsl:attribute name="data-loc">
            <xsl:value-of select="@loc"/>
        </xsl:attribute>
        <xsl:attribute name="class">varcontainer bracketclose</xsl:attribute>
        <xsl:call-template name="split">
            <xsl:with-param name="list" select="@mss"/>
        </xsl:call-template>
        <xsl:text>:&#160;</xsl:text>
        <xsl:apply-templates />
        <!--xsl:element name="span">
            <xsl:attribute name="class">varbracket</xsl:attribute>
            <xsl:text> ❳</xsl:text>
        </xsl:element-->
    </xsl:element><xsl:text>
    </xsl:text>
</xsl:template>

<xsl:template match="x:lg[@type='verse' or @xml:id]">
    <xsl:element name="div">
        <xsl:attribute name="class">
            <xsl:text>verse upama-block</xsl:text>
            <xsl:if test="@type='mangala'"><xsl:text> mangala</xsl:text></xsl:if>
        </xsl:attribute>
        <xsl:if test="@xml:id">
        <xsl:attribute name="id"><xsl:value-of select="@xml:id"/></xsl:attribute>
        </xsl:if>
        <xsl:apply-templates/>
    </xsl:element>
</xsl:template>

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

<xsl:template match="x:lg[@type='verse' or @xml:id]/x:maintext">
    <!--div class="maintext" lang="sa"-->
    <div>
        <xsl:attribute name="class">
            <xsl:text>maintext</xsl:text>
            <xsl:if test="../@style">
                <xsl:text> </xsl:text><xsl:value-of select="../@style"/>
            </xsl:if>
        </xsl:attribute>
        <xsl:comment>SECTION_START</xsl:comment>
        <xsl:comment><xsl:value-of select="../@xml:id"/>=sa<xsl:text>=UPAMA_SECTION</xsl:text></xsl:comment>
        <xsl:apply-templates />
    <xsl:comment>SECTION_END</xsl:comment>
    </div><xsl:text>
    </xsl:text>
</xsl:template>

<xsl:template match="x:l[@xml:id]">
    <xsl:element name="div">
        <xsl:attribute name="class">
            <xsl:text>verse upama-block</xsl:text>
        </xsl:attribute>
        <xsl:attribute name="id"><xsl:value-of select="@xml:id"/></xsl:attribute>
        <xsl:apply-templates/>
    </xsl:element>
</xsl:template>

<xsl:template match="x:l[@xml:id]/x:maintext">
    <xsl:element name="div">
        <xsl:attribute name="class">
            <xsl:text>maintext</xsl:text>
            <xsl:if test="@style">
                <xsl:text> </xsl:text><xsl:value-of select="@style"/>
            </xsl:if>
        </xsl:attribute>
        <xsl:comment>SECTION_START</xsl:comment>
        <xsl:comment><xsl:value-of select="../@xml:id"/>=sa<xsl:text>=UPAMA_SECTION</xsl:text></xsl:comment>
        <xsl:apply-templates/>
        <xsl:comment>SECTION_END</xsl:comment>
    </xsl:element><xsl:text>
    </xsl:text>
</xsl:template>

<!--xsl:template match="x:lg[@type='mangala']">
    <xsl:element name="div">
        <xsl:attribute name="class">mangala upama-block</xsl:attribute>
        <xsl:if test="@xml:id">
        <xsl:attribute name="id"><xsl:value-of select="@xml:id"/></xsl:attribute>
        </xsl:if>
        <xsl:apply-templates/>
    </xsl:element>
</xsl:template-->

<!--xsl:template match="x:lg[@type='mangala']/x:maintext">
    <div class="maintext">
        <xsl:comment>SECTION_START</xsl:comment>
        <xsl:comment><xsl:value-of select="../@xml:id"/>=sa<xsl:text>=UPAMA_SECTION</xsl:text></xsl:comment>
        <xsl:apply-templates />
    <xsl:comment>SECTION_END</xsl:comment>
    </div><xsl:text>
    </xsl:text>
</xsl:template-->

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
    <xsl:if test="@ignored='TRUE'">
        <xsl:attribute name="class"><xsl:call-template name="ignore"/></xsl:attribute>
    </xsl:if>
    <xsl:element name="span">
        <xsl:attribute name="data-balloon">caesura</xsl:attribute>
        <xsl:attribute name="class">ignored</xsl:attribute>
        <xsl:attribute name="style">display: none;</xsl:attribute>
        <xsl:text>/</xsl:text>
    </xsl:element>
    </xsl:element>
</xsl:template>

<xsl:template match="x:div2[@type='apparatus'] | x:ab[@type='apparatus']">
    <xsl:element name="div">
        <xsl:attribute name="id"><xsl:value-of select="@xml:id"/></xsl:attribute>
        <xsl:attribute name="class">apparatus2 upama-block</xsl:attribute>
        <xsl:variable name="target" select="@target | @corresp"/>
        <xsl:attribute name="data-target"><xsl:value-of select="translate($target,'#','')"/></xsl:attribute>
        <xsl:element name="div">
            <xsl:attribute name="class">
                <xsl:text>maintext</xsl:text>
            </xsl:attribute>
            <xsl:comment>SECTION_START</xsl:comment>
            <xsl:comment><xsl:value-of select="@xml:id"/>=en<xsl:text>=UPAMA_SECTION</xsl:text></xsl:comment>
            <xsl:element name="ul">
                <xsl:attribute name="class">accordion</xsl:attribute>
                <xsl:apply-templates />
            </xsl:element>
            <xsl:comment>SECTION_END</xsl:comment>
        </xsl:element><xsl:text>
        </xsl:text>
    </xsl:element>
</xsl:template>

</xsl:stylesheet>
