<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
                xmlns:x="http://www.tei-c.org/ns/1.0"
                exclude-result-prefixes="x">
<xsl:output method="html" omit-xml-declaration="yes"/>

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

<!--xsl:template match="x:titleStmt">
    <h1>
        <xsl:apply-templates select="x:title"/>
    </h1>
    <h2>
        <xsl:apply-templates select="x:author"/>
    </h2>
</xsl:template -->

<xsl:template match="x:teiHeader">
    <xsl:apply-templates />
    <hr />
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
                <xsl:attribute name="class">milestone</xsl:attribute>
                <xsl:attribute name="lang">en</xsl:attribute>
            <xsl:text>(</xsl:text>
            <xsl:choose>
            <xsl:when test="@unit">
                <xsl:value-of select="@unit"/>
                <xsl:text> </xsl:text>
            </xsl:when>
            <xsl:when test="/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:msDesc/x:physDesc/x:objectDesc[@form = 'pothi']">
                <xsl:text>folio </xsl:text>
            </xsl:when>
<xsl:when test="/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:msDesc/x:physDesc/x:objectDesc[@form = 'book']">
                <xsl:text>page </xsl:text>
            </xsl:when>
            </xsl:choose>
            <xsl:value-of select="$no"/>
            <xsl:text>)</xsl:text>
        </xsl:element>
    </xsl:when>
    <xsl:otherwise>
        <xsl:element name="span">
            <xsl:attribute name="class">milestone</xsl:attribute>
                <xsl:attribute name="lang">en</xsl:attribute>
            <xsl:text>(</xsl:text>
            <xsl:choose>
            <xsl:when test="@unit">
                <xsl:value-of select="@unit"/>
                <xsl:text> </xsl:text>
            </xsl:when>
            <xsl:when test="/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:msDesc/x:physDesc/x:objectDesc[@form = 'pothi']">
                <xsl:text>folio </xsl:text>
            </xsl:when>
<xsl:when test="/x:TEI/x:teiHeader/x:fileDesc/x:sourceDesc/x:msDesc/x:physDesc/x:objectDesc[@form = 'book']">
                <xsl:text>page </xsl:text>
            </xsl:when>
            </xsl:choose>
<xsl:value-of select="$no"/>
            <xsl:text>)</xsl:text>
        </xsl:element>
    </xsl:otherwise>
    </xsl:choose>
</xsl:template>

<xsl:template match="x:sup">
    <sup><xsl:apply-templates /></sup>
</xsl:template>

<xsl:template match="x:sub">
    <sub><xsl:apply-templates /></sub>
</xsl:template>

<xsl:template match="x:quote">
    <xsl:element name="span">
        <xsl:call-template name="lang"/>
        <xsl:attribute name="class">quote</xsl:attribute>
        <xsl:apply-templates />
    </xsl:element>
</xsl:template>

<xsl:template match="x:foreign">
    <xsl:element name="em">
        <xsl:call-template name="lang"/>
        <xsl:apply-templates />
    </xsl:element>
</xsl:template>

<xsl:template match="x:term">
    <xsl:element name="em">
        <xsl:call-template name="lang"/>
        <xsl:apply-templates />
    </xsl:element>
</xsl:template>

<xsl:template match="x:titleStmt/x:title">
    <h1><xsl:apply-templates/></h1>
</xsl:template>
<xsl:template match="x:titleStmt/x:title[@type='alt']">
    <h3 style="font-style:italic"><xsl:apply-templates/></h3>
</xsl:template>

<xsl:template match="x:titleStmt/x:editor">
    <h4>Edited by <xsl:apply-templates/></h4>
</xsl:template>

<xsl:template match="x:titleStmt/x:author"/>

<xsl:template match="x:titleStmt/x:respStmt">
    <p><xsl:apply-templates/></p>
</xsl:template>

<xsl:template match="x:respStmt/x:resp">
    <xsl:apply-templates/>
</xsl:template>

<xsl:template match="x:respStmt/x:name">
    <xsl:apply-templates/>
</xsl:template>

<xsl:template match="x:publicationStmt">
    <p>Published in <xsl:apply-templates select="x:date"/> by <xsl:apply-templates select="x:publisher"/> in <xsl:apply-templates select="x:pubPlace"/>.</p>
</xsl:template>

<xsl:template match="x:title">
    <xsl:element name="em">
        <xsl:call-template name="lang"/>
        <xsl:apply-templates />
    </xsl:element>
</xsl:template>

<xsl:template match="x:msContents/x:summary/x:title">
    <xsl:element name="em">
        <xsl:call-template name="lang"/>
        <xsl:apply-templates/>
    </xsl:element>
</xsl:template>

<xsl:template match="x:msContents/x:summary/x:sub">
    <xsl:element name="sub">
        <xsl:apply-templates/>
    </xsl:element>
</xsl:template>
<xsl:template match="x:msContents/x:summary/x:sup">
    <xsl:element name="sup">
        <xsl:apply-templates/>
    </xsl:element>
</xsl:template>


<xsl:template match="x:msContents/x:summary/x:ptr">
    <xsl:element name="a">
        <xsl:attribute name="href"><xsl:value-of select="@target"/></xsl:attribute>
        <xsl:value-of select="@target"/>
    </xsl:element>
</xsl:template>

<xsl:template match="x:msIdentifier">
    <ul>
    <xsl:if test="x:collection">
        <li>
            <xsl:apply-templates select="x:collection"/>
        </li>
    </xsl:if>
    <xsl:if test="x:repository">
        <li>
            <xsl:apply-templates select="x:repository"/>
            <xsl:if test="normalize-space(x:repository/@ref) != ''">
                <xsl:element name="a">
                    <xsl:attribute name="href">
                        <xsl:value-of select="x:repository/@ref"/>
                    </xsl:attribute>
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="12px"
	 height="12px" viewBox="0 0 12 12" style="enable-background:new 0 0 12 12;" xml:space="preserve">
                        <g id="Icons" style="opacity:0.75;">
                            <g id="external">
                                <polygon id="box" style="fill-rule:evenodd;clip-rule:evenodd;" points="2,2 5,2 5,3 3,3 3,9 9,9 9,7 10,7 10,10 2,10 		"/>
                                <polygon id="arrow_13_" style="fill-rule:evenodd;clip-rule:evenodd;" points="6.211,2 10,2 10,5.789 8.579,4.368 6.447,6.5
                                    5.5,5.553 7.632,3.421 		"/>
                            </g>
                        </g>
                        <g id="Guides" style="display:none;">
                        </g>
                    </svg>
                </xsl:element>
            </xsl:if>
        </li>
    </xsl:if>
    <xsl:if test="x:institution">
        <li>
            <xsl:apply-templates select="x:institution"/>
            <xsl:if test="normalize-space(x:institution/@ref) != ''">
                <xsl:element name="a">
                    <xsl:attribute name="href">
                        <xsl:value-of select="x:institution/@ref"/>
                    </xsl:attribute>
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="12px"
	 height="12px" viewBox="0 0 12 12" style="enable-background:new 0 0 12 12;" xml:space="preserve">
                        <g id="Icons" style="opacity:0.75;">
                            <g id="external">
                                <polygon id="box" style="fill-rule:evenodd;clip-rule:evenodd;" points="2,2 5,2 5,3 3,3 3,9 9,9 9,7 10,7 10,10 2,10 		"/>
                                <polygon id="arrow_13_" style="fill-rule:evenodd;clip-rule:evenodd;" points="6.211,2 10,2 10,5.789 8.579,4.368 6.447,6.5
                                    5.5,5.553 7.632,3.421 		"/>
                            </g>
                        </g>
                        <g id="Guides" style="display:none;">
                        </g>
                    </svg>
                </xsl:element>
            </xsl:if>
        </li>
    </xsl:if>
    <xsl:if test=".//x:settlement">
    <li>
        <xsl:apply-templates select=".//x:settlement"/>
        <xsl:if test=".//x:region">
            <xsl:text>, </xsl:text>
            <xsl:apply-templates select=".//x:region"/>
        </xsl:if>
        <xsl:if test=".//x:country">
            <xsl:text>, </xsl:text>
            <xsl:apply-templates select=".//x:country"/>
        </xsl:if>
    </li>
    </xsl:if>
    <!--xsl:for-each select="*[not(self::x:idno)]">
    <li>
        <xsl:apply-templates/>
    </li>
    </xsl:for-each-->
    <xsl:if test="x:idno[not(@type='siglum')]">
    <li><xsl:text>Known as: </xsl:text>
    <xsl:for-each select="x:idno[not(@type='siglum')][position() != last()]">
        <xsl:apply-templates/>
        <xsl:if test="@type"><xsl:text> (</xsl:text><xsl:value-of select="@type"/><xsl:text>)</xsl:text></xsl:if>
        <xsl:text>, </xsl:text>
    </xsl:for-each>
    <xsl:apply-templates select="x:idno[not(@type='siglum')][last()]" />
    <xsl:if test="x:idno[not(@type='siglum')][last()]/@type"><xsl:text> (</xsl:text><xsl:value-of select="x:idno[not(@type='siglum')][last()]/@type"/><xsl:text>)</xsl:text></xsl:if>
    <xsl:text>.</xsl:text>
    </li>
    </xsl:if>
    <xsl:if test="x:idno[@type='siglum']">
        <li><xsl:text>Siglum: </xsl:text><xsl:apply-templates select="x:idno[@type='siglum']"/></li>
    </xsl:if>
    </ul>
</xsl:template>

<xsl:template match="x:msContents">
    <p id="__upama_summary"><xsl:apply-templates select="x:summary"/></p>
    <div id="__upama_read_more">More &#9662;</div>

    <xsl:for-each select="x:msItem">
      <table class="__upama_msItem">
        <xsl:for-each select="x:title[not(@type)]">
            <tr>
              <td>Title</td>
                <xsl:element name="td">
                    <xsl:call-template name="lang"/>
                    <xsl:apply-templates />
                </xsl:element>
            </tr>
        </xsl:for-each>
        <xsl:for-each select="x:title[@type='commentary']">
          <tr>
            <td>Commentary</td>
            <xsl:element name="td">
                <xsl:call-template name="lang"/>
                <xsl:apply-templates />
            </xsl:element>
          </tr>
        </xsl:for-each>
        <xsl:for-each select="x:author[@role='author']">
          <tr>
            <td>Author</td>
            <xsl:element name="td">
                <xsl:call-template name="lang"/>
                <xsl:apply-templates />
            </xsl:element>
          </tr>
        </xsl:for-each>
        <xsl:for-each select="x:author[@role='commentator']">
          <tr>
            <td>Commentator</td>
            <xsl:element name="td">
                <xsl:call-template name="lang"/>
                <xsl:apply-templates />
            </xsl:element>
          </tr>
        </xsl:for-each>

       <xsl:if test="x:rubric">
           <tr>
             <td>Rubric</td>
             <xsl:element name="td">
                <xsl:attribute name="class">excerpt</xsl:attribute>
                <xsl:call-template name="lang"/>
                <xsl:apply-templates select="x:rubric"/>
             </xsl:element>
           </tr>
       </xsl:if> 
       <xsl:if test="x:incipit">
           <tr>
             <td>Incipit</td>
             <xsl:element name="td">
                <xsl:attribute name="class">excerpt</xsl:attribute>
                <xsl:call-template name="lang"/>
                <xsl:apply-templates select="x:incipit"/>
             </xsl:element>
           </tr>
       </xsl:if> 
       <xsl:if test="x:explicit">
           <tr>
             <td>Explicit</td>
             <xsl:element name="td">
                <xsl:attribute name="class">excerpt</xsl:attribute>
                <xsl:call-template name="lang"/>
                <xsl:apply-templates select="x:explicit"/>
             </xsl:element>
           </tr>
       </xsl:if>
       <xsl:if test="x:finalRubric">
           <tr>
             <td>Final Rubric</td>
             <xsl:element name="td">
                <xsl:attribute name="class">excerpt</xsl:attribute>
                <xsl:call-template name="lang"/>
                <xsl:apply-templates select="x:finalRubric"/>
             </xsl:element>
           </tr>
       </xsl:if>
      </table>
    </xsl:for-each>
</xsl:template>

<xsl:template match="x:xenoData/x:stemma[@format='newick']"/>
<xsl:template match="x:xenoData/x:stemma[@format='nexml']">
    <div id="__upama_stemma"><xsl:copy-of select="./*"/></div>
</xsl:template>

<xsl:template match="x:physDesc">
  <table id="__upama_physDesc">
  <th colspan="2">Physical description</th>
  <tr>
    <td>Language/Script</td> 
    <td><xsl:apply-templates select="//x:textLang"/>
        <xsl:if test="x:scriptDesc/x:scriptNote">
            <ul>
                <xsl:apply-templates select="x:scriptDesc/x:scriptNote"/>
            </ul>
        </xsl:if>
    </td>
  </tr>
  <xsl:if test="x:objectDesc/@form">
      <tr>
        <td>Format</td> <td><xsl:value-of select="x:objectDesc/@form"/></td>
      </tr>
  </xsl:if>
  <xsl:if test="x:objectDesc/x:supportDesc/@material">
      <tr>
        <td>Material</td> <td><xsl:value-of select="x:objectDesc/x:supportDesc/@material"/></td>
      </tr>
  </xsl:if>
  <xsl:if test="x:objectDesc/x:supportDesc/x:extent">
      <tr>
        <td>Extent</td> 
        <td>
            <xsl:value-of select="x:objectDesc/x:supportDesc/x:extent/x:measure/@quantity"/><xsl:text> </xsl:text><xsl:value-of select="x:objectDesc/x:supportDesc/x:extent/x:measure/@unit"/>
            <xsl:text>. </xsl:text><xsl:apply-templates select="x:objectDesc/x:supportDesc/x:extent/x:measure" />
        </td>
      </tr>
  </xsl:if>
  <xsl:if test="x:objectDesc/x:supportDesc/x:extent/x:dimensions">
      <tr>
        <td>Dimensions</td> 
        <td><ul>
            <xsl:if test="x:objectDesc/x:supportDesc/x:extent/x:dimensions[@type='leaf']">
                <li>(leaf) <xsl:value-of select="x:objectDesc/x:supportDesc/x:extent/x:dimensions[@type='leaf']/x:height"/><xsl:text> x </xsl:text><xsl:value-of select="x:objectDesc/x:supportDesc/x:extent/x:dimensions[@type='leaf']/x:width"/><xsl:text> </xsl:text><xsl:value-of select="x:objectDesc/x:supportDesc/x:extent/x:dimensions/@unit"/></li>
            </xsl:if>
            <xsl:if test="x:objectDesc/x:supportDesc/x:extent/x:dimensions[@type='written']">
                <li>(written) <xsl:value-of select="x:objectDesc/x:supportDesc/x:extent/x:dimensions[@type='written']/x:height"/><xsl:text> x </xsl:text><xsl:value-of select="x:objectDesc/x:supportDesc/x:extent/x:dimensions[@type='written']/x:width"/><xsl:text> </xsl:text><xsl:value-of select="x:objectDesc/x:supportDesc/x:extent/x:dimensions/@unit"/></li>
            </xsl:if>
        </ul></td>
      </tr>
    </xsl:if>
    <xsl:if test="x:objectDesc/x:supportDesc/x:foliation">
      <tr>
        <td>Foliation</td>
        <td><ul>
            <xsl:for-each select="x:objectDesc/x:supportDesc/x:foliation">
              <li>(<xsl:value-of select="@type"/><xsl:text>) </xsl:text><xsl:apply-templates /></li>
            </xsl:for-each>
        </ul></td>
      </tr>
    </xsl:if>
    <xsl:if test="x:objectDesc/x:supportDesc/x:condition">
        <tr>
          <td>Condition</td> <td><xsl:apply-templates select="x:objectDesc/x:supportDesc/x:condition"/></td>
        </tr>
    </xsl:if>
    <xsl:if test="x:objectDesc/x:layoutDesc/x:layout">
      <tr>
        <td>Layout</td> 
        <td>
          <xsl:if test="x:objectDesc/x:layoutDesc/x:layout/@writtenLines">
            <xsl:value-of select="translate(x:objectDesc/x:layoutDesc/x:layout/@writtenLines,' ','-')"/>
            <xsl:text> lines per page. </xsl:text>
          </xsl:if>
          <xsl:if test="x:objectDesc/x:layoutDesc/x:layout/@ruledLines">
            <xsl:value-of select="translate(x:objectDesc/x:layoutDesc/x:layout/@ruledLines,' ','-')"/>
            <xsl:text> ruled lines per page. </xsl:text>
          </xsl:if>
<xsl:apply-templates select="x:objectDesc/x:layoutDesc/x:layout/x:p"/>
        </td>
      </tr>
    </xsl:if>
    <xsl:if test="x:handDesc/x:handNote">
        <xsl:variable name="LowerCase" select="'abcdefghijklmnopqrstuvwxyz'"/>
        <xsl:variable name="UpperCase" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZ'"/>
        <tr>
          <td>Hand</td>
          <td><ul>
            <xsl:for-each select="x:handDesc/x:handNote">
              <xsl:variable name="script" select="@script"/>
              <li>  
                <xsl:text>(</xsl:text><xsl:value-of select="@scope"/><xsl:text>) </xsl:text>
                <xsl:value-of select="translate(
                  substring($script,1,1),
                  $LowerCase,
                  $UpperCase
                  )"/>
                <xsl:value-of select="substring($script,2,string-length($script)-1)"/>
                <xsl:text> script in </xsl:text>
                <xsl:value-of select="@medium"/>
                <xsl:text>. </xsl:text>
                <xsl:apply-templates select="x:desc"/>
              </li>
            </xsl:for-each>
          </ul></td>
        </tr>
    </xsl:if>
    <xsl:if test="x:additions/x:p">
      <tr>
        <td>Additions</td>
        <td><ul>
          <xsl:for-each select="x:additions/x:p">
            <li><xsl:apply-templates /></li>
          </xsl:for-each>
        </ul></td>
      </tr>
    </xsl:if>
    <xsl:if test="x:bindingDesc/x:p">
        <tr>
            <td>Binding</td>
            <td><xsl:apply-templates select="x:bindingDesc/x:p"/></td>
        </tr>
    </xsl:if>
  </table>

</xsl:template>
<xsl:template match="x:scriptNote">
    <xsl:element name="li">
        <xsl:attribute name="class">scriptNote</xsl:attribute>
        <xsl:attribute name="data-scriptnote"><xsl:value-of select="@xml:id"/></xsl:attribute>
        <xsl:apply-templates/>
    </xsl:element>
</xsl:template>
<xsl:template match="x:history">
    <table id="__upama_history">
        <th colspan="2">History</th>
        <tr>
            <td>Date of production</td>
            <td><xsl:apply-templates select="x:origin/x:origDate"/></td>
        </tr>
        <tr>
            <td>Place of origin</td>
            <td><xsl:apply-templates select="x:origin/x:origPlace"/></td>
        </tr>
        <xsl:if test="x:provenance">
            <tr>
                <td>Provenance</td>
                <td><xsl:apply-templates select="x:provenance"/></td>
            </tr>
        </xsl:if>
        <xsl:if test="x:acquisition">
            <tr>
                <td>Acquisition</td>
                <td><xsl:apply-templates select="x:acquisition"/></td>
            </tr>
        </xsl:if>
    </table>
</xsl:template>

<xsl:template match="x:listWit[not(@resp='upama')]" />

<xsl:template match="x:additional"/>

<xsl:template match="x:encodingDesc"/>

<xsl:template match="x:profileDesc"/>

<xsl:template match="x:revisionDesc"/>
<!--
<xsl:template match="x:revisionDesc">
    <h5>Revision history</h5>
    <ul class="revisionDesc">
        <xsl:apply-templates/>
    </ul>
</xsl:template>

<xsl:template match="x:revisionDesc/x:change">
    <xsl:element name="li">
        <xsl:element name="span">
            <xsl:attribute name="class">when</xsl:attribute>
            <xsl:value-of select="@when"/>
        </xsl:element>
        <xsl:text> </xsl:text>
        <xsl:apply-templates/>
    </xsl:element>
</xsl:template>
-->

<xsl:template match="x:facsimile"/>

<xsl:template match="x:text"/>
</xsl:stylesheet>
