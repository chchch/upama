/* DOKUWIKI:include_once sanscript.js */
/* DOKUWIKI:include_once jquery.hypher.js */
/* DOKUWIKI:include_once sa.js */

var upama = {

highlight: function(id,node) {
    var pos = id.split("x");
    var spaces = 0;
    var startnode, endnode;
    var startpos = -1;
    var endpos = -1;
    if(pos[0] == 0) {
        var startpos = 0;
        var startnode = node.childNodes[0];
    }

    function recurseElements(node) {
        var kids=node.childNodes;
        for(var i=0;i<kids.length;i++) {
            var kid = kids[i];
            if(kid.nodeType == 3) { // text node
                var kidtext = kid.data;
               // var space = kidtext.indexOf(" "); // doesn't work with non-breaking spaces
                
                //var space = upama.regexIndexOf(kidtext,/\s+/);
                var re = /\s+/g;
                var space = re.exec(kidtext);
                while(space && space.index >= 0) {
                    
                    if(spaceAtEndOfContainer == true && space.index == 0) {
                        space = re.exec(kidtext);
                        continue;
                    }
                    else {
                        spaces++;
                        spaceAtEndOfContainer = false;
                    }

                    if(spaces == pos[0] && startpos == -1) {
                        //startpos = space+1;
                        startpos = re.lastIndex;
                        startnode = kid;
                    }
                    else if(spaces == pos[1]) {
                        //endpos = space;
                        endpos = space.index;
                        endnode = kid;
                        return 1;
                    }
                    //space = kidtext.indexOf(" ",space+1);
                    //space = upama.regexIndexOf(kidtext,/\s+/,space+1);
                    
                    // we don't want to count as separate spaces that are divided by tags
                    if(re.lastIndex == kid.length)
                        spaceAtEndOfContainer = true;
                    
                    space = re.exec(kidtext);
                }
            } 
            else {
                if(!kid.classList.contains('ignored'))
                    if(recurseElements(kid)) return 1;
            }
        }
        return 0;
    }
    
    function getNextNode(node) {
        if(node.firstChild)
            return node.firstChild;
        while(node) {
            if(node.nextSibling) return node.nextSibling;
            node = node.parentNode;
        }
    }
    
    function highlightNode(range) {
        var highlightNode = document.createElement('span');
        highlightNode.className = "highlight";
        range.surroundContents(highlightNode);
    }

    function lightTextNodes(range) {
        
        if(range.startContainer == range.endContainer) {
            // beginning and end of range are in the same container
            highlightNode(range);
        }
        
        else {

            var start = range.startContainer;
            var end = range.endContainer;

            if(start.nodeType == 3) {
                var textRange = start.ownerDocument.createRange();
                textRange.setStart(start,range.startOffset);
                textRange.setEnd(start,start.length);
                highlightNode(textRange);
            }

            for(node = getNextNode(start); node != end; node = getNextNode(node)) {
                if(node.nodeType == 3) {
                    var textRange = node.ownerDocument.createRange();
                    textRange.selectNode(node);
                    highlightNode(textRange);
                }
            }
            
            if(end.nodeType == 3) {
                var textRange = end.ownerDocument.createRange();
                textRange.setStart(end,0);
                textRange.setEnd(end,range.endOffset);
                highlightNode(textRange);
            }

        }
    
    }
    
    var spaceAtEndOfContainer = false;
    recurseElements(node);

//    var newNode = document.createElement('span');
//    newNode.className = "highlight";
    var middleRange = node.ownerDocument.createRange();
    middleRange.setStart(startnode,startpos);
    middleRange.setEnd(endnode,endpos);
//    var middlebit = middleRange.extractContents();
//    newNode.appendChild(middlebit);
//    middleRange.insertNode(newNode);

//    middleRange.surroundContents(newNode);

    lightTextNodes(middleRange);
},

/*regexIndexOf: function(str,regex,startpos) {
    var i = str.substring(startpos || 0).search(regex);
    return (i >= 0) ? (i + (startpos || 0)) : i;
},*/

initialize: function() {
    var san = jQuery("[lang=sa]");
    var script = jQuery('#__upama_script_selector').val();
    var rewrite = ( document.location.href.indexOf("id=") == -1 ) ? true : false;

    san.each(function(index) {
        if(!this.myCleaned) {
            /// don't break before a daṇḍa
            this.innerHTML = this.innerHTML.replace(/ \|/g,"&nbsp;|");
            this.innerHTML = this.innerHTML.replace(/\| (?=\d)/g,"|&nbsp;");
            this.myCleaned = true;
        }
        if(script == 'deva') {
            if(!this.myIAST) this.myIAST = this.innerHTML;
            this.innerHTML = upama.toDevanagari(this.innerHTML);
            jQuery(this).addClass("devanagari");
        }
        else if(script == 'iast') {
            if(this.myIAST) {
                this.innerHTML = this.myIAST;
                jQuery(this).removeClass("devanagari");
            }
        }

    });

    san.hyphenate('sa');
    san.find('*').hyphenate('sa'); // also hyphenates childnodes

     var apparati = document.getElementsByClassName("apparatus");
     var mains = document.getElementsByClassName("maintext");
     var sidebar_sigla = document.getElementsByClassName("sidebar-siglum");
     var sigla = new Object();

     for(var ss=0;ss<sidebar_sigla.length;ss++) {
         sigla[sidebar_sigla[ss].innerHTML] = sidebar_sigla[ss].getAttribute('data-pageid'); 
    } 
        
     for(var n=0;n<apparati.length;n++) {

        var allmsids = apparati[n].getElementsByClassName("msid");

        for(var am = 0;am<allmsids.length;am++) {
            allmsids[am].href = rewrite ? 
                "/" + sigla[allmsids[am].innerHTML] + "?upama_scroll="+n :
                "?id="+sigla[allmsids[am].innerHTML] + "&upama_scroll="+n;
        }  

        mains[n].myOldContent = mains[n].innerHTML;

        var variants = apparati[n].getElementsByClassName("variant");
        
        for(var i=0;i<variants.length;i++) {
            
            variants[i].myMainText = n;
            variants[i].onmouseover = function() {
                var hipos = this.parentNode.getAttribute('data-loc');
                upama.highlight(hipos,mains[this.myMainText]);
                };
            variants[i].onmouseout = function() {
                mains[this.myMainText].innerHTML = mains[this.myMainText].myOldContent;
                }; 

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
        mains[scrolltoN].scrollIntoView();
     /*   jQuery("html, body").animate({
            scrollTop: jQuery(mains[scrolltoN]).offset().top
            scrollTop: 1000   
        }, 2000); */
    }
},

toDevanagari: function(text) {

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
    };

    var options = {};
    options.skip_sgml = true;
    text = jQuery("<textarea/>").html(text).text(); // decode HTML special characters
    text = text.toLowerCase();
    text = Sanscript.t(text,'iast','devanagari',options);

    // remove space between a word that ends in a consonant and a word that begins with a vowel
    text = text.replace(/([दरमवयन])् (अं|[अआएइईउऊऋ])/g,  function(match,p1,p2,offset,string) {
        return p1+initialVowels[p2]+"<span class='spacer'> </span>";
    });
    
    // remove space between a word that ends in a consonant and a word that begins with a consonant
    text = text.replace(/([दरवयतचसशनज]्) ([बभहङगघदधजझडढपफरकखतथचछटठमणनवलळसशषय])/g, function(match,p1,p2,offset,string) {
        if(p1 == 'र्') return "<span class='spacer'> </span>र्"+p2;
        else return p1+"&zwj;<span class='spacer'> </span>"+p2;
        });

    // join final o and anusvara
    text = text.replace(/ो ं/g, "ों<span class='spacer'> </span>"); 
    
    // add zero-width joiner to viramas before a closing tag 
    //text = text.replace(/्</g, "्&zwj;<");
    return text;
},

getUrlVars: function() {
    var vars = {};
    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
}


}; // upama

window.addEventListener ("load", upama.initialize);
