/* DOKUWIKI:include_once sanscript.js */
/* DOKUWIKI:include_once jquery.hypher.js */
/* DOKUWIKI:include_once sa.js */

var upama = {

mains: [],
//highlit: [],
permalit: [],
script: '',
hyphen: String.fromCodePoint("0xAD"),

initialize: function() {
    var san = jQuery("[lang=sa]");
    upama.script = jQuery('#__upama_script_selector').val();
//    upama.script = 'deva';
    var rewrite = ( document.location.href.indexOf("id=") == -1 ) ? true : false;
    san.each(function(index) {
        if(!this.myCleaned) {
            /// don't break before a daṇḍa
            this.innerHTML = this.innerHTML.replace(/\s+\|/g,"&nbsp;|");
            this.innerHTML = this.innerHTML.replace(/\|\s+(?=\d)/g,"|&nbsp;");
            this.myCleaned = true;
        }
        if(upama.script == 'deva') {
            if(!this.myIAST) this.myIAST = this.innerHTML;
            this.innerHTML = upama.changeScript(this,'deva');
            //this.innerHTML = upama.toDevanagari(this.innerHTML);
            this.classList.add("devanagari");
        }
        else if(upama.script == 'iast') {
            if(this.myIAST) { 
                this.innerHTML = this.myIAST;
                this.classList.remove("devanagari");
            }
        }

    });

    san.hyphenate('sa');
    san.find('*').hyphenate('sa'); // also hyphenates childnodes
    document.addEventListener('copy',upama.removeHyphens);
    //Hyphenator.run();

     var apparati = document.getElementsByClassName("apparatus");
     upama.mains = document.getElementsByClassName("maintext");
     var sidebar_sigla = document.getElementsByClassName("sidebar-siglum");
     var sigla = new Object();
     
     for(var ss=0;ss<sidebar_sigla.length;ss++) {
         sigla[sidebar_sigla[ss].innerHTML] = sidebar_sigla[ss].getAttribute('data-pageid'); 
    } 
        
     for(var n=0;n<apparati.length;n++) {

        var allmsids = apparati[n].getElementsByClassName("msid");
        var mainId = apparati[n].parentElement.id;
        for(var am = 0;am<allmsids.length;am++) {
            allmsids[am].href = rewrite ? 
                "/" + sigla[allmsids[am].innerHTML] + "?upama_scroll="+mainId :
                "?id="+sigla[allmsids[am].innerHTML] + "&upama_scroll="+mainId;
        }  

        upama.mains[n].myOldContent = upama.mains[n].innerHTML;

        var variants = apparati[n].getElementsByClassName("variant");
        
        for(var i=0;i<variants.length;i++) {
            
            variants[i].myMainText = n;
            variants[i].addEventListener('mouseover',upama.varMouseOver);
            variants[i].addEventListener('mouseout',upama.varMouseOut);
            variants[i].addEventListener('click',upama.varOnClick);

            var readings = variants[i].parentNode.getElementsByClassName("reading");
            var readlength = readings.length;
            if(readlength > 0) {
                var msarray = new Object();
                var msids = variants[i].parentNode.getElementsByClassName("msid");
                for(var p=0;p<msids.length;p++) {
                    msarray[msids[p].innerHTML] = msids[p];
                }
                variants[i].myOldText = variants[i].innerHTML;

                for(var q=0;q<readlength;q++) {

                    var ms = readings[q].getAttribute("data-ms");
                    var msNode = msarray[ms];
                    msNode.myNewText = readings[q].innerHTML;
                    msNode.classList.add("mshover");
                    msNode.onmouseover = function() {
                        var varNode = this.parentNode.getElementsByClassName("variant")[0];
                        varNode.innerHTML = this.myNewText;
                        varNode.classList.add("varreading");
                        };
                    msNode.onmouseout = function() {
                        var varNode = this.parentNode.getElementsByClassName("variant")[0];
                        varNode.innerHTML = varNode.myOldText;
                        varNode.classList.remove("varreading");
                        };
                } 
            } // end if(readlength > 0)
        }
    }

    var getvars = upama.getUrlVars();
    var scrolltoN = getvars["upama_scroll"];
    if(scrolltoN) {
//        upama.mains[scrolltoN].scrollIntoView({behavior: "smooth"});
        $(document).ready(function() {
        jQuery("html, body").animate({
            scrollTop: jQuery("[id='"+scrolltoN+"']").offset().top
       //     scrollTop: 1000   
        }, 2000); 
        });
    }
},

varMouseOver: function() {
    var _this = this;
//    if(upama.permalit.length == 0 || upama.permalit[0] != _this) {
    if(upama.permalit.length == 0 || upama.permalit[1] != _this) {
        var hipos = _this.parentNode.getAttribute('data-loc');
        upama.highlight(hipos,upama.mains[_this.myMainText]);
    }
},

varMouseOut: function() {
    var _this = this;
    if(upama.permalit[1] != _this) {
        upama.mains[_this.myMainText].innerHTML = upama.mains[_this.myMainText].myOldContent;
   //     upama.unHighlight();
    }
},
/*
varMouseOut: function() {
    var _this = this;
    upama.unHighlight(upama.highlit);
    upama.highlit = [];
},

varOnClick: function() {
    var _this = this;
    
    if(upama.permalit[0] == _this) { // clicked on an already permahighlit element
        _this.classList.remove('permahighlight');
        upama.unHighlight(upama.permalit[1]);
        upama.permalit = [];
    }
    else {
        if(upama.permalit.length > 0) {
            upama.unHighlight(upama.permalit[1]);
            upama.permalit[0].classList.remove('permahighlight');
            upama.permalit = [];

        }
        _this.classList.add('permahighlight');
        upama.permalit[0] = _this;
        upama.permalit[1] = upama.highlit;
        upama.highlit = [];
    }
}, */


varOnClick: function() {

    var _this = this;

    if(_this.classList.contains('permahighlight')) { // clicked on an already highlighted element
        _this.classList.remove('permahighlight');
        //upama.permalit[0].innerHTML = upama.permalit[0].myOldContent;
        upama.permalit[0].myOldContent = upama.permalit[2];
        upama.permalit = [];
    }

    else {
        if(upama.permalit.length > 0) { // clear other permahighlighted variant
            upama.permalit[0].myOldContent = upama.permalit[2];
            upama.permalit[0].innerHTML = upama.permalit[2];

            if(upama.permalit[0] == upama.mains[_this.myMainText]) {
                var hipos = _this.parentNode.getAttribute('data-loc');
                upama.highlight(hipos,upama.mains[_this.myMainText]);
            }

            upama.permalit[1].classList.remove('permahighlight');
        }
        // permahighlight new element
        upama.permalit = [upama.mains[_this.myMainText],_this,upama.mains[_this.myMainText].myOldContent];
        upama.mains[_this.myMainText].myOldContent = upama.mains[_this.myMainText].innerHTML;
        _this.classList.add('permahighlight');
    }
},


windowClick: function(event) {
    var node = event.target;
    var max = 5;
    for(var i=0;i<max;i++) {
        
        if(node && node.nodeType == 1 &&
            node.classList.contains('permahighlight'))
            return;
        
        parnode = node.parentNode;
        if(parnode)
            node = node.parentNode;
        else
            break;

        i++;
    }
/*    
    if(upama.permalit.length > 0) {
        upama.unHighlight(upama.permalit[1]);
        upama.permalit[0].classList.remove('permahighlight');
        upama.permalit = [];
    }*/
    if(upama.permalit.length > 0) {
        upama.permalit[0].innerHTML = upama.permalit[2];
        upama.permalit[0].myOldContent = upama.permalit[2];
        upama.permalit[1].classList.remove('permahighlight');
        upama.permalit = [];
    }

},

toDevanagari: function(text) {

    var options = {};
    options.skip_sgml = true;
    text = jQuery("<textarea/>").html(text).text(); // decode HTML special characters
    text = text.toLowerCase();

/*
    text = text.replace(/([drmvyn]) ([aāiīuūṛṝeo])/g, function(match,p1,p2,offset,string) {
        return p1+p2+"S";
    });
    
    text = text.replace(/([kgcjtdnmrlśs]) ([kgcjṭḍtdpbnmyrlvśṣs])/g, function(match,p1,p2,offset,string) {
        if(p1 == 'r') return "Sr"+p2;
        else return p1+"ZS"+p2;
    });

    text = text.replace(/o '/g,"oS'");
*/
    text = Sanscript.t(text,'iast','devanagari',options);

/*
    text = text.replace(/S/g,"<span class='spacer'> </span>");
    text = text.replace(/Z/g,"&zwj;");
*/
    var initialVowels = {
        'अ': '',
        'आ':  'ा',
        'ए':  'े',
        'इ':  'ि',
        'ई':  'ी',
        'उ':  'ु',
        'ऊ':  'ू',
        'ऋ':  'ृ',
        'अं': 'ं',
        'आं':'ां',
    };

    // remove space between a word that ends in a consonant and a word that begins with a vowel
    text = text.replace(/([दरमवयन])् (अं|आं|[अआएइईउऊऋ])/g,  function(match,p1,p2,offset,string) {
        return "<span class='deva-changed' data-orig='"+p1+"्'>"+p1+initialVowels[p2]+"</span><span class='spacer'> </span><span class='deva-removed'>"+p2+"</span>";
    });
    
    // remove space between a word that ends in a consonant and a word that begins with a consonant
    text = text.replace(/([दरवयतचसशनज]्) ([बभहङगघदधजझडढपफरकखतथचछटठमणनवलळसशषय])/g, function(match,p1,p2,offset,string) {
        if(p1 == 'र्') return  "<span class='deva-removed'>र्</span><span class='spacer'> </span><span class='deva-added'>"+p1+"</span>"+p2;
        else return p1+"<span class='spacer'> </span>"+p2;
        });

    // join final o and anusvara 
    text = text.replace(/ो ऽ/g,"ो<span class='spacer'> </span>ऽ"); 

    text = text.replace(/ो ं/g,"<span class='deva-changed' data-origig='ो'>ों</span><span class='spacer'> </span><span class='deva-removed'>ऽं</span>");

    // add zero-width joiner to viramas before a closing tag 
    //text = text.replace(/्</g, "्&zwj;<");


    return text;
},

changeScript: function(node,lang,level = 0) {
/* it seems to be faster to change the innerHTML of a node than to create a DocumentFragment and then replace the node */
    if(lang == 'deva') func = upama.toDevanagari;
    var kids = node.childNodes;
    var i = 0;
    if(level > 0) {
        var tags = upama.outerTags(node);
        var htmlstr = tags[0];
    }
    else htmlstr = '';

    for(var i=0;i<kids.length;i++) {
            kid = kids[i];

            if(kid.nodeType == 3) {
            //    var htmlstr = func(kid.nodeValue);
            //    var frag = document.createRange().createContextualFragment(htmlstr);
            //    kid.parentNode.replaceChild(frag,kid);
                htmlstr += func(kid.data);
            }
            else if(kid.hasChildNodes() &&
                    kid.getAttribute('lang') != 'en')
                htmlstr += upama.changeScript(kid,lang,level+1);
            else {
                htmlstr += kid.outerHTML;
            }
    }
    if(level > 0)
        return htmlstr+tags[1];
    else return htmlstr;
},

outerTags: function(node) {
    var start = "<"+node.nodeName;
    var attrs = node.attributes;
    for(var i=0; i<attrs.length; i++) {
        start += " "+attrs[i].name+"='"+attrs[i].value+"'";
    }
    start += ">";
    var end = "</"+node.nodeName+">";
    return [start,end];

},
/**** highlight functions start here ****/

highlight: function(id,node) {
    var pos = id.split("x");
    for(var q=0;q<pos.length;q++)
        pos[q] = parseInt(pos[q]);

    var spaces = 0;
    var startnode, endnode;
    var startpos = -1;
    var endpos = -1;
    var kid;
    var spaceAtEndOfContainer = false;
    var prevnode;

    function countSpaces(node) {
        var re = /\s+/g;
        var skipKids = false;
        var preIgnored = false;
        var spaces = 0;

        for(kid = node.firstChild;kid != node.nextSibling;kid = upama.getNextNode(kid,skipKids)) {
            skipKids = false;
            if(kid.nodeType != 3) {
                if(kid.classList.contains('ignored')) {
                    if(!preIgnored) preIgnored = kid;
                    skipKids = true;
                }
                else if(kid.classList.contains('upama-show'))
                    spaceAtEndOfContainer = false;
                continue;
            }
            else if(kid.data.length != 0 && kid.data.trim() == '') {
            // encountered a space node
                if(!spaceAtEndOfContainer) {
                    spaces++;
                    spaceAtEndOfContainer = true;
                }
                preIgnored = false;
                continue;
            }
            else {
                var kidtext = kid.data;
                space = re.exec(kidtext);
                
                if(startpos == -1 && pos[0] == 0) {
                  
                  if(!space || space.index > 0) {
                        if(preIgnored) {
                            startnode = preIgnored;
                            startpos = -2;
                        }
                        else {
                            startpos = 0;
                            startnode = kid;
                        }
                    }
                    else {
                        startpos = re.lastIndex;
                        startnode = kid;
                    }
                   
                }

                if(!space || space.index > 0) {

                    if(spaceAtEndOfContainer) {
                        if(startpos == -1 && spaces == pos[0]) {
                            if(preIgnored) {
                                startnode = preIgnored;
                                startpos = -2;
                            }
                            else {
                                startpos = 0;
                                startnode = kid;
                            }
                        }
            
                        spaceAtEndOfContainer = false;
                    }

                    if(pos[1] == spaces) {
                        if(kid.parentNode.classList.contains('deva-changed')) {
                            endnode = kid.parentNode;
                            endpos = -2;
                        }
                        else if(kid.parentNode.classList.contains('deva-removed')) {
                            if(kid.parentNode.nextSibling.nodeType == 1 &&
                               kid.parentNode.nextSibling.classList.contains('spacer')) {
                                endnode = kid.parentNode;
                                endpos = -2;
                            }
                            else {
                                endnode = prevnode.parentNode;
                                endpos = -2;
                            }
                        }
                        else if (kid.parentNode.classList.contains('deva-added')) {
                            endnode = prevnode.parentNode;
                            endpos = -2;
                        }
                        else {
                            endnode = prevnode;
                            endpos = prevnode.length;
                        }
                        return true;
                    }
                }
                
                while(space) {
                    
                    preIgnored = false;

                    if(space.index > 0)
                        spaces++;
                    else if(!spaceAtEndOfContainer)
                        spaces++;

                    if(re.lastIndex == kidtext.length) {
                        spaceAtEndOfContainer = true;
                    }
                    else
                        spaceAtEndOfContainer = false;

                    if(startpos == -1 &&
                       spaces == pos[0] &&
                       !spaceAtEndOfContainer) {

                        startpos = re.lastIndex;
                        startnode = kid;

                    }
                    if(startpos != -1 &&
                       endpos == -1 &&
                       spaces == pos[1]) {

                        endpos = space.index;
                        endnode = kid;
                        return true;
                    
                    }
                    
                    space = re.exec(kidtext);
                
                }
            prevnode = kid;
            } // end for(kid = node.firstChild...
        }
        
        // cycled till end of section, no endnode found
        if(prevnode) {
            if(prevnode.parentNode.lastChild.nodeType != 3) {
            
            // this means that there are some extra bits at the end of the section
            endnode = prevnode.parentNode.lastChild;
            endpos = -2;
            }
            else { // otherwise just set it to the end of the section
                endnode = prevnode;
                endpos = prevnode.length;
            }
        }
        else { // as a last resort; maybe maintext is empty
            if(startnode) {
                endnode = startnode;
                endpos = startnode.length;
            }
            else {
                startnode = node.firstChild;
                startpos = 0;
                endnode = node.firstChild;
                endpos = endnode.length;
            }
        }

        // no endnode found
        return false;
    }
    
    countSpaces(node);
    var middleRange = node.ownerDocument.createRange();
    if(startpos == -2) 
        middleRange.setStartBefore(startnode);
    else
        middleRange.setStart(startnode,startpos);
    if(endpos == -2) 
        middleRange.setEndAfter(endnode);
    else
        middleRange.setEnd(endnode,endpos);

    if(upama.script == 'deva') {
        if(startpos == 0)
            if(startnode.previousSibling)
                upama.addSpaces(startnode.previousSibling);
            else
                upama.addSpaces(startnode.parentNode.previousSibling);
        if(endpos == -2 || endpos == endnode.length)
            upama.addSpaces(endnode.nextSibling);
        else
            upama.addSpaces(endnode);
    }
    
    if(pos.length > 2) {
        if(upama.script == 'deva' && startnode != endnode) {
            var innernode = upama.getNextNode(startnode);
            while(innernode && innernode != endnode) {
                if(innernode.nodeType == 1) {
                    upama.addSpaces(innernode);
                }
                innernode = upama.getNextNode(innernode);
            }
        }
        if(pos[2] > 0) middleRange = upama.countCharsPrefix(middleRange,pos[2]);
        else if(pos.length == 4) middleRange = upama.countCharsSuffix(middleRange,pos[3]);
    }  
    upama.lightTextNodes(middleRange);
    
},

countCharsSuffix: function(range,pos) {

    if(range.startContainer.nodeType == 3) {
        var start = range.startContainer;
        var startpos = range.startOffset;
    }
    else {
        var start = range.startContainer.childNodes[range.startOffset];
        var startpos = 0;
    }
   
   if(range.endContainer.nodeType == 3) {
        var end = range.endContainer;
        var endpos = range.endOffset;
    }
    else {
        var end = range.endContainer.childNodes[range.endOffset-1];
        var endpos = 0;
    }

  if(start == end) {
       if(upama.script == 'deva') {
            var testStr = Sanscript.t(start.data.substring(startpos,endpos),"devanagari","iast",{skip_sgml: true});
            var strArr = Array.from(testStr);
       }
       else
           var strArr = Array.from(start.data.substring(startpos,endpos));
       var b = 0;
       for(var a=0;a<strArr.length;a++) {
           if(strArr[a] == upama.hyphen)
               continue;
            if(b == pos)
                break;
            b++;
       }

       if(upama.script == 'deva') {
            
            testStr = strArr.join('');
            testStr = testStr.substring(0,a);
            testStr = Sanscript.t(testStr,"iast","devanagari",{skip_sgml: true});
            range.setEnd(start,startpos+testStr.length);
        }
        else
            range.setEnd(start,startpos+a);

        return range;
   } // end if(start == end)
   
   var skipKids = false;
   for(var node = start;node != upama.getNextNode(end);node = upama.getNextNode(node,skipKids)) {
        skipKids = false;
        if(node.nodeType != 3) {
            if(node.classList.contains('ignored') ||
                node.getAttribute('deva-added'))
                skipKids = true;
            continue;
        }
        if(node.data.length != 0 && node.data.trim() == '') {
            // encountered a space node
            if(startpos)
                startpos--;
            else pos -= node.data.length;
            continue;
        }
        if(upama.script == 'deva') {
        
            var testStr = Sanscript.t(node.data.substring(startpos),"devanagari","iast",{skip_sgml: true});
            var strArr = Array.from(testStr);
        }
       
        else {
            var strArr = Array.from(node.data.substring(startpos));
        }
        var hyphens = strArr.join('').match(/\u00AD/g);
        var hyphens = hyphens ? hyphens.length : 0;
        if((strArr.length-hyphens) <= pos) {
            pos = pos - strArr.length + hyphens;
            startpos = 0;
            continue;
        }
        
        else {
            var b = 0;
            for(var a=0;a<strArr.length;a++) {
                if(strArr[a] == upama.hyphen)
                    continue;
                 if(b == pos)
                     break;
                 b++;
            }
            if(upama.script == 'deva') {
                testStr = strArr.join('');
                testStr = testStr.substring(0,a);
                testStr = Sanscript.t(testStr,"iast","devanagari",{skip_sgml: true});
                range.setEnd(node,startpos+testStr.length);
            }
            else
                range.setEnd(node,startpos+a);
            
            return range;
        }
   }
   return false;
},

countCharsPrefix: function(range,pos) {

    if(range.startContainer.nodeType == 3) {
        var start = range.startContainer;
        var startpos = range.startOffset;
    }
    else {
        var start = range.startContainer.childNodes[range.startOffset];
        var startpos = 0;
    }
   
    if(range.endContainer.nodeType == 3) {
        var end = range.endContainer;
        var endpos = range.endOffset;
    }
    else {
        var end = range.endContainer.childNodes[range.endOffset-1];
        var endpos = 0;
    }
    if(start == end) {

       if(upama.script == 'deva') {
            var testStr = Sanscript.t(start.data.substring(startpos,endpos),"devanagari","iast",{skip_sgml: true});
            var strArr = Array.from(testStr);
       }
       else
           var strArr = Array.from(start.data.substring(startpos,endpos));
       var b = 0;
       for(var a=0;a<strArr.length;a++) {
           if(strArr[a] == upama.hyphen)
               continue;
            if(b == pos)
                break;
            b++;
       }

       if(upama.script == 'deva') {
            
            testStr = strArr.join('');
            testStr = testStr.substring(0,a);
            testStr = Sanscript.t(testStr,"iast","devanagari",{skip_sgml: true});
            range.setStart(start,startpos+testStr.length);
        }
        else
            range.setStart(start,startpos+a);

        return range;
   } // end if(start == end)
   var skipKids = false;
    for(var node = start;upama.getNextNode(end);node = upama.getNextNode(node,skipKids)) {
        skipKids = false;
        if(node.nodeType != 3) {
            if(node.classList.contains('ignored') ||
                node.classList.contains('deva-added'))
                skipKids = true;
            continue;
        }
        if(node.data.length != 0 && node.data.trim() == '') {
            // encountered a space node
            if(startpos)
                startpos--;
            else
                pos -= node.data.length;
            continue;
        }
        if(upama.script == 'deva') {
        
            var testStr = Sanscript.t(node.data.substring(startpos),"devanagari","iast",{skip_sgml: true});
            var strArr = Array.from(testStr);
        }
       
        else {
            var strArr = Array.from(node.data.substring(startpos));
        }
        
        var hyphens = strArr.join('').match(/\u00AD/g);
        var hyphens = hyphens ? hyphens.length : 0;
        if((strArr.length-hyphens) <= pos) {
            pos = pos - strArr.length + hyphens;
            startpos = 0;
            continue;
        }
        
        else {
            var b = 0;
            for(var a=0;a<strArr.length;a++) {
                if(strArr[a] == upama.hyphen)
                    continue;
                 if(b == pos)
                     break;
                 b++;
            }
            if(upama.script == 'deva') {
                testStr = strArr.join('');
                testStr = testStr.substring(0,a);
                testStr = Sanscript.t(testStr,"iast","devanagari",{skip_sgml: true});
                range.setStart(node,startpos+testStr.length);
            }
            else
                range.setStart(node,startpos+a);
            return range;
        }
   }
   return false;
},

/*regexIndexOf: function(str,regex,startpos) {
    var i = str.substring(startpos || 0).search(regex);
    return (i >= 0) ? (i + (startpos || 0)) : i;
},*/

/*unHighlight: function(list) {
    for(var i=0;i<list.length;i++) {
        var nodePar = list[i].parentNode;
        console.log(nodePar.innerHTML);
        while(list[i].firstChild) {
            nodePar.insertBefore(list[i].firstChild,list[i]);
        }
        nodePar.removeChild(list[i]);
        console.log(nodePar.innerHTML);
        nodePar.normalize();
    }
}, */

getNextNode: function(node,skipKids = false) {
    if(node.firstChild && !skipKids)
        return node.firstChild;
    while(node) {
        if(node.nextSibling) return node.nextSibling;
        node = node.parentNode;
    }
},

/***** highlightNode(node): highlights a range by surrounding it with a span; this works as long as there are no divs in the range *****/

highlightNode: function(range) {
    var highlightNode = document.createElement('span');
    highlightNode.className = "highlight";
    highlightNode.appendChild(range.extractContents());
    range.insertNode(highlightNode);
    //range.surroundContents(highlightNode);
//    upama.highlit.push(highlightNode);
},

/***** findDivs(range): checks if there are div elements in a range *****/

findDivs: function(range) {
    var container = range.cloneContents();
    node = container.firstChild;
    while(node) {
        if(node.nodeName == 'DIV') {
            return 1;
        }
        node = upama.getNextNode(node);
    }
    return 0;
},

lightTextNodes: function(range) {

    if(range.startContainer.nodeType == 3) {
        var start = range.startContainer;
        var startpos = range.startOffset;
    }
    else {
        var start = range.startContainer.childNodes[range.startOffset];
        var startpos = 0;
    }
   
    if(range.endContainer.nodeType == 3) {
        var end = range.endContainer;
        var endpos = range.endOffset;
    }
    else {
        var end = range.endContainer.childNodes[range.endOffset-1];
        var endpos = 0;
    }

//        if((start.parentNode == end.parentNode) && !findDivs(range)) {
    if(!upama.findDivs(range)) {
/* can't surround divs with a span (well, it's ugly, and also the highlightNode function would automatically close open divs, which would generate an extra div) */
            upama.highlightNode(range);
    }
    
    else { // surround only text nodes with the highlight span
        
        var toHighlight = [];
        
        if(start.nodeType == 3 && range.startOffset != start.length) {
            var textRange = start.ownerDocument.createRange();
            textRange.setStart(start,range.startOffset);
            textRange.setEnd(start,start.length);
            toHighlight.push(textRange);
        }

        for(node = upama.getNextNode(start); node != end; node = upama.getNextNode(node)) {
            if(node.nodeType == 3) {
                var textRange = node.ownerDocument.createRange();
                textRange.selectNode(node);
                toHighlight.push(textRange);
            }
        }
        
        if(end.nodeType == 3 && range.endOffset > 0) {
            var textRange = end.ownerDocument.createRange();
            textRange.setStart(end,0);
            textRange.setEnd(end,range.endOffset);
            toHighlight.push(textRange);
        }
        for(var k=0;k<toHighlight.length;k++) {
// do highlighting at end so as not to add nodes during the tree traversal
            upama.highlightNode(toHighlight[k]);
        }
    }

},

addSpaces: function(node) {
    if(!node) return false;
    if(node.nodeType == 1 && node.classList.contains('spacer'))
        var spacer = node;
    else if(node.parentNode.classList.contains('spacer'))
        var spacer = node.parentNode;
    else
        return false;
    
    spacer.style.display = 'inline';
            
    var inodes = [];
    var prev = spacer.previousSibling;
    if(prev.nodeType != 3) {
        if(prev.classList.contains('highlight'))
// highlightNode might create an empty spacer element inside the highlight span
            prev = prev.lastChild.previousSibling;
        if(prev.nodeType != 3)
            inodes.push(prev);
    }
    
    var next = spacer.nextSibling;
    if(next.nodeType != 3) {
        if(next.classList.contains('highlight'))
            next = next.firstChild.nextSibling;
        if(next.nodeType != 3) 
            inodes.push(next);
    }
    
    for(var i=0;i < inodes.length;i++) {
        
        if(inodes[i].classList.contains('deva-changed'))
            inodes[i].innerHTML = inodes[i].getAttribute('data-orig');
        else if(inodes[i].classList.contains('deva-removed')) {
            inodes[i].style.display = 'inline';
        }
        
        else if(inodes[i].classList.contains('deva-added'))
            inodes[i].style.display = 'none';
    }

},
/**** end of highlight functions ****/

removeHyphens: function(ev) {
    ev.preventDefault();
    var sel = window.getSelection().toString();
    sel = sel.replace(/\u00AD/g,'');
    (ev.clipboardData || window.clipboardData).setData('Text',sel);
},

getUrlVars: function() {
    var vars = {};
    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
}


}; // end upama class

window.addEventListener ("load", upama.initialize);
window.addEventListener ("click",upama.windowClick);
