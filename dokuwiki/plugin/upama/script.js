/* DOKUWIKI:include_once sanscript.js */
/* DOKUWIKI:include_once jquery.hypher.js */
/* DOKUWIKI:include_once sa.js */

var upama = {

mains: [],
mainClass: '.sectiontext',
//highlit: [],
permalit: [],
lowlit: [],
script: 'iast',
hyphen: String.fromCodePoint("0xAD"),
hyphenRegex: new RegExp("\u00AD","g"),
contentbox: null,
scripts: ['devanagari','malayalam','telugu'],
listWit: [],
middle: null,
placeholder: String.fromCodePoint("0xFFFD"),
//placeholder: "#",

initialize: function() {
    
    var getvars = upama.getUrlVars();

    if(getvars['do'] && getvars['do'] != 'show') return;

//    if(getvars['do'] == 'edit') return;

    var upama_ver = document.getElementById("__upama_ver");
    var current = false;
    if(upama_ver) {
        if(upama_ver.textContent != '')
            getvars['upama_ver'] = upama_ver.textContent;
    }
    else return; // no upama data
   
    if(getvars['upama_script'] && 
       upama.scripts.indexOf(getvars['upama_script']) > -1) {
        
        upama.script = getvars['upama_script'];
        jQuery('#__upama_script_selector').val(upama.script).change();
    }
    else if(document.getElementById('__upama_script_selector')) {
        upama.script = document.getElementById('__upama_script_selector').value || 'iast';
        if(upama.script != 'iast')
            getvars['upama_script'] = upama.script;
    }
    
/*    if(typeof JSINFO !== 'undefined') {
        var current = JSINFO['_upama_current'];
        if(current === undefined)
            return;
    }
    else 
        var current = false;

    if(current) { // if current is 0, it means there's no apparatus
        getvars['upama_ver'] = current;
    }

 */
    upama.rewriteURL(getvars);

     upama.apparati = document.getElementsByClassName("variorum");

     //upama.mains = document.getElementsByClassName("sectiontext");
     upama.mains = document.querySelectorAll("*:not(.apparatus2) > .maintext > .sectiontext");
     if(upama.mains.length == 0) {
         upama.mains = document.getElementsByClassName("maintext");
         upama.mainClass = '.maintext';
     }
     /*
     var sidebar_sigla = document.getElementsByClassName("sidebar-siglum");
     var sigla = new Object();
     for(let siglum of sidebar_sigla) {
         sigla[siglum.textContent] = siglum.getAttribute('data-pageid'); 
     }
    */
     for(let apparatus of upama.apparati) {
        /*
        let allmsids = apparatus.getElementsByClassName("msid");
        let mainId = apparatus.parentElement.id;
        var rewrite = ( document.location.href.indexOf("id=") == -1 );
        for(let msid of allmsids) {
            msid.href = rewrite ? 
                "/" + sigla[msid.textContent] + "?upama_scroll="+mainId :
                "?id="+sigla[msid.textContent] + "&upama_scroll="+mainId;
        }  
        */
        //upama.mains[n].myOldText = upama.mains[n].innerHTML;
        
        apparatus.addEventListener('mouseover',upama.appMouseOver);
        apparatus.addEventListener('click',upama.appOnClick);
      
    
    } // end for(let apparatus of upama.apparati)
    
    var witnesses = document.querySelectorAll('#__upama_listWit li');
    for(w of witnesses)
        upama.listWit.push([(w.getAttribute('data-msid')),w.innerHTML]);

    //if(upama.script != 'iast') 
    upama.docSetScript(upama.script,true);
    
    upama.contentbox = document.getElementById('dokuwiki__content') || document.body;
     
    if(upama.apparati.length != 0) {
        upama.contentbox.addEventListener('mouseup',upama.findVariants);
        document.addEventListener('keyup',upama.findVariants);
//        if('ontouchstart' in window)
//            upama.contentbox.addEventListener('touchend',upama.findVariants);
    }

    upama.contentbox.addEventListener("click",upama.windowClick);

    upama.contentbox.addEventListener('copy',upama.removeHyphens);

    upama.contentbox.addEventListener('mouseover',upama.toolTipBubble);

    if(document.getElementById('__upama_script_selector'))
        document.getElementById('__upama_script_selector').addEventListener("change",upama.scriptSelectorChange);

    if(document.getElementById('__upama_export'))
        document.getElementById('__upama_export').addEventListener("change",upama.exportSelectorChange);

    var scrolltoN = getvars["upama_scroll"];
    if(scrolltoN &&
       document.getElementById(scrolltoN)) {
//        upama.mains[scrolltoN].scrollIntoView({behavior: "smooth"});
        jQuery(document).ready(function() {
        if(jQuery(window).scrollTop() == '0') {
            jQuery('html, body').animate({
                scrollTop: jQuery("[id='"+scrolltoN+"']").offset().top - 20
                }, 2000);
            }
        });
    }
},

getViewPos: function() {
    var summary = jQuery('#__upama_summary');
    if(summary.length) {
        var middle = upama.inViewport(jQuery('#__upama_summary')) ? null : upama.findMiddleElement();
    }
    else var middle = null;
    upama.middle = middle;
},
 
setViewPos: function() {
    if(upama.middle !== null) {
        var middle = upama.middle;
        //var $pane = jQuery('.ui-layout-pane-center').first();
        //$pane.scrollTop(0);
        var scrollpos = middle[0].offset().top + middle[1] - jQuery(window).height()/2;
        window.scrollTo(0,scrollpos);
        //$pane.scrollTop(scrollpos);
    }
    upama.middle = null;

},

toolTipBubble: function(e) {
    var target = e.target;
    target = target.closest('[data-balloon]');
    while(target && target.hasAttribute('data-balloon')) {
        upama.toolTip(e,target);
        target = target.parentNode;
    }
},

toolTip: function(e,target) {
    var tooltext = target.getAttribute('data-balloon');
    if(!tooltext) return;

    var tipBox = document.getElementById('__upama_tooltip');
    var tiptext;
    
    if(tipBox) {
        for(kid of tipBox.childNodes) {
            if(kid.myTarget == target)
                return;
        }
        // no tiptext associated with target found; make a new one
        tiptext = document.createElement('div');
        tiptext.appendChild(document.createElement('hr'));
    }
    else {
        tipBox = document.createElement('div');
        tipBox.id = '__upama_tooltip';
        tipBox.style.top = (e.clientY + 10) + 'px';
        tipBox.style.left = (e.clientX) + 'px';
        tipBox.style.opacity = 0;
        tipBox.style.transition = "opacity 0.2s ease-in";
        upama.contentbox.parentNode.insertBefore(tipBox,upama.contentbox);

        tiptext = document.createElement('div');
        tiptext.myTarget = target;
    }

    tiptext.appendChild(document.createTextNode(tooltext));
    tiptext.myTarget = target;
    tipBox.appendChild(tiptext);
    target.addEventListener('mouseleave',upama.toolTipRemove,{once: true});

    window.getComputedStyle(tipBox).opacity;
    tipBox.style.opacity = 1;
},

toolTipRemove: function(e) {
    var targ = e.target;
    var tipBox = document.getElementById('__upama_tooltip');

    if(tipBox.children.length == 1)
        tipBox.remove();
    else {
        for(kid of tipBox.childNodes) {
            if(kid.myTarget == targ) {
                kid.remove();
                break;
            }
        }
        if(tipBox.children.length == 1) {
            var kid = tipBox.firstChild.firstChild;
            if(kid.tagName == "HR")
                kid.remove();
        }
    }
},

scriptSelectorChange: function() {
    var _this = this;
    var script = _this.value;
    document.getElementById('__upama_hidden_script_selector').value = script;
    var oldscript = upama.script;
    upama.script = script;
    upama.docSetScript(script,false,oldscript);
    if(script != 'iast') upama.rewriteURL('',{upama_script: script});
    else upama.rewriteURL('',{upama_script: null});
},

exportSelectorChange: function() {
    var _this = this;
    var url = window.location.href;
    var prefix = url.indexOf("?") > -1 ? "&" : "?";

    if(_this.value == 'fasta')
        window.location.href = url + prefix + "do=export_fasta";
    
    else if(_this.value == 'tei')
        window.location.href = url + prefix + "do=export_tei";

    else if(_this.value == 'latex')
        window.location.href = url + prefix + "do=export_latex";
    
    _this.value = "default";
},

rewriteURL: function(getvars,morevars) {
    var getvars = getvars || upama.getUrlVars();
    var newurl = window.location.href;
    if(morevars) {
        for(var prop in morevars) {
            if(morevars.hasOwnProperty(prop)) {
                if(morevars[prop])
                    getvars[prop] = morevars[prop];
                else {
                    if(getvars.hasOwnProperty(prop))
                        delete getvars[prop];
                }
            }
        }
    }

    newurl = newurl.split('?')[0];

    if(Object.keys(getvars).length > 0) {
        newurl = newurl + '?';
        let varkeys = Object.keys(getvars);
        for(let v=0;v<varkeys.length;v++) {
            newurl = newurl + varkeys[v] + '=' + getvars[varkeys[v]];
            if(v<varkeys.length-1) 
                newurl = newurl + '&';
        }
        window.history.replaceState('','Text in '+upama.script+' script',newurl);
    }
    else window.history.replaceState('','',newurl);

},

inViewport: function(el) {

    return el.offset().bottom <= jQuery(window).scrollTop();
},

findMiddleElement: function() {
    var els = document.getElementsByClassName('upama-block');
    var $midEl = null;
    var lastDist;
    var currDist = null;
    var midHeight = jQuery(window).scrollTop() + jQuery(window).height()/2;
    var ellen = els.length;
    for(let i=0;i<ellen;i++) {
        lastDist = currDist;
        currDist = midHeight - jQuery(els[i]).offset().top;
        if(lastDist != null && Math.abs(currDist) > Math.abs(lastDist)) {
            $midEl = jQuery(els[i-1]);
            currDist = lastDist;
            break;
        }
    }
    if($midEl === null)
        $midEl = jQuery(els[ellen-1]);
    return [$midEl,currDist];
},

docSetScript: function(script,init = false,oldscript = false) {
    
    if(!init) upama.getViewPos();
//   var middle = (init || upama.inViewport(jQuery('#__upama_summary'))) ? null : upama.findMiddleElement();
    
    var san = document.querySelectorAll("[lang=sa]");
    for(let _this of san) {
       if(init) {
            let htmlstr = _this.innerHTML;
            // don't break before a daṇḍa
            htmlstr = htmlstr.replace(/\s+\|/g,"&nbsp;|");
            // don't break between daṇḍa and a numeral or puṣpikā
            htmlstr = htmlstr.replace(/\|\s+(?=[\d꣸])/g,"|&nbsp;");
            _this.innerHTML = htmlstr;
            jQuery(_this).hyphenate('sa');
            jQuery(_this).find('*').hyphenate('sa');
        }
        if(script != 'iast') {
            if(!_this.myIAST)
                _this.myIAST = _this.cloneNode(true);

            let oldtext = _this.myOldText || _this.myIAST;
            
            _this.innerHTML = upama.changeScript(oldtext,script);
            if(oldscript)
                _this.classList.remove(oldscript);
            _this.classList.add(upama.script);
/*            if(_this.hasOwnProperty('myOldText')) 
               // _this.myOldText = upama.changeScript(_this.myOldText,'deva');
               delete _this.myOldText; */
        }
        else if(!init) { // && script == 'iast') {
            let oldtext = _this.myOldText || _this.myIAST;
            if(oldtext) {
                _this.innerHTML = oldtext.innerHTML;
                _this.classList.remove(oldscript);
            }
/*            if(_this.hasOwnProperty('myOldText'))
                //_this.myOldText = _this.innerHTML;
                delete _this.myOldText; */
        }
        if(_this.hasOwnProperty('myOldReading'))
            delete _this.myOldReading;
    }
/*
    if(middle !== null) {
        //var $pane = jQuery('.ui-layout-pane-center').first();
        //$pane.scrollTop(0);
        var scrollpos = middle[0].offset().top + middle[1] - jQuery(window).height()/2;
        window.scrollTo(0,scrollpos);
        //$pane.scrollTop(scrollpos);
    }
*/
    upama.setViewPos();
},

appMouseOver: function(e) {
    var targ = e.target;
    var closest = targ.closest('.mshover');
    if(closest)
        upama.msidMouseOver(closest);
    else {
        closest = targ.closest('.variant');
        if(closest) upama.varMouseOver(closest);
    }
},

appOnClick: function(e) {
    var targ = e.target;
    var closest = targ.closest('.variant');
    if(closest) upama.varOnClick(false,closest);
},

varMouseOver: function(target) {
    var _this = target || this;
//    _this = _this.target || _this;


    if(upama.lowlit.length != 0) {
        if(upama.lowlit[0].closest('.variorum') == _this.closest('.variorum')) {
            upama.unLowLight();
            window.getSelection().removeAllRanges();
        }
    }
    if(upama.permalit.length == 0 || upama.permalit[1] != _this) {
        var hipos = _this.parentNode.getAttribute('data-loc');
/*        var orignode = upama.mains[
            [].indexOf.call(upama.apparati,_this.closest('.variorum'))
            ];
*/
        var orignode = document.getElementById(
            _this.closest('.apparatus').getAttribute('data-target')
            ).querySelector(upama.mainClass);
        if(upama.script != 'iast') {
        //    if(!orignode.hasOwnProperty('myOldText')) {
        //        var tempnode = orignode.myIAST.cloneNode(true);
        //        orignode.myOldText = tempnode.cloneNode(true);
        //    }
        //    else var tempnode = orignode.myOldText.cloneNode(true);

            var tempnode = orignode.myIAST.cloneNode(true);
            upama.highlight(hipos,tempnode);
            upama.highlit = tempnode;
            orignode.innerHTML = upama.changeScript(tempnode,upama.script);
        }

        else {
            if(!orignode.hasOwnProperty('myIAST')) 
                orignode.myIAST = orignode.cloneNode(true);
            upama.highlight(hipos,orignode);
           } 
    }

    _this.addEventListener('mouseout',upama.varMouseOut, {once: true});
},

varMouseOut: function(e) {
    var _this = e.target;
//    _this = _this.target ? _this.target : _this;
/*
    var orignode = upama.mains[
            [].indexOf.call(upama.apparati,_this.closest('.variorum'))
        ];
*/
        var orignode = document.getElementById(
            _this.closest('.apparatus').getAttribute('data-target')
            ).querySelector(upama.mainClass);
//        var orignode = _this.closest('.apparatus').previousSibling;
        var oldtext = orignode.myOldText || orignode.myIAST;

    if(upama.permalit[1] != _this && oldtext) {
        if(upama.script != 'iast') {
            orignode.innerHTML = upama.changeScript(oldtext.cloneNode(true),upama.script);
        }
        else
           // orignode.parentNode.replaceChild(orignode.myOldText,orignode);
            orignode.innerHTML = oldtext.innerHTML;
    }
},

varOnClick: function(event,el) {

    var _this = el || this;
    var newnode;

    if(_this.classList.contains('permahighlight')) // clicked on an already highlighted element
       upama.unPermaLight();

    else {
//        var orignode = _this.closest('.apparatus').previousSibling;
/*        var orignode = upama.mains[
            [].indexOf.call(upama.apparati,_this.closest('.variorum'))
            ];
*/

            var orignode = document.getElementById(
                _this.closest('.apparatus').getAttribute('data-target')
                ).querySelector(upama.mainClass);
            if(upama.permalit.length > 0) { 
            // clear other permahighlighted variant

            if(upama.script != 'iast') 
                upama.permalit[0].innerHTML = upama.changeScript(upama.permalit[0].myIAST,upama.script);
            else 
                upama.permalit[0].innerHTML = upama.permalit[0].myIAST.innerHTML;
            delete upama.permalit[0].myOldText;

            if(upama.permalit[0] == orignode) {
                // re-highlight the node if we just cleared it
                let hipos = _this.parentNode.getAttribute('data-loc');
                if(upama.script != 'iast') {
                    newnode = orignode.myIAST.cloneNode(true);
                    upama.highlight(hipos,newnode);
                    //orignode.myOldText = newnode.cloneNode(true);
                    upama.highlit = newnode;
                    orignode.innerHTML = upama.changeScript(newnode,upama.script);
                } else {
                //    if(!orignode.hasOwnProperty('myOldText'))
                //        orignode.myOldText = orignode.cloneNode(true);
                    upama.highlight(hipos,orignode);
                }
            }

            upama.permalit[1].classList.remove('permahighlight');
        }

        // permahighlight new element
        
        upama.permalit = [orignode,_this];
        if(upama.script != 'iast') {
            orignode.myOldText = upama.highlit.cloneNode(true);
/*            if(!newnode) {
                let hipos = _this.parentNode.getAttribute('data-loc');
                newnode = orignode.myIAST.cloneNode(true);
                upama.highlight(hipos,newnode);
                orignode.myOldText = upama.highlit.cloneNode(true);
                orignode.myOldText = upama.highlit.cloneNode(true);
            }
                orignode.myOldText = newnode.cloneNode(true);
          */      
        } else {
        //    if(!orignode.hasOwnProperty('myIAST'))
        //        orignode.myIAST = orignode.myOldText.cloneNode(true);
            orignode.myOldText = orignode.cloneNode(true);
        }

        _this.classList.add('permahighlight');
    }
},

msidMouseOver: function(target) {
    var _this = target;
    let container = _this.parentNode;
    let varNode = container.getElementsByClassName("variant")[0];
    if(container.offsetLeft != container.parentNode.offsetLeft) {
        //container.style.whiteSpace = 'normal';
        //container.style.wordWrap = 'break-word';
        container.classList.add('nowrap');
    } // if the container is at the beginning of the line, don't allow line breaks between the sigla and the variant reading, otherwise the sigla might be shifted to the end of the previous line
    if(!varNode.hasOwnProperty('myOldReading'))
        varNode.myOldReading = varNode.innerHTML;
    varNode.innerHTML = container.querySelector("span[data-ms='"+_this.textContent+"']").innerHTML;
    varNode.classList.add("varreading");
    _this.addEventListener('mouseout',upama.msidMouseOut,{once: true});
},

msidMouseOut: function() {
    let container = this.parentNode;
    let varNode = container.getElementsByClassName("variant")[0];
    //container.style.whiteSpace = 'nowrap';
    //container.style.wordWrap = 'normal';
    container.classList.remove('nowrap');
    varNode.innerHTML = varNode.myOldReading;
    varNode.classList.remove("varreading");
},

unPermaLight: function() {
   if(upama.permalit.length > 0) {
        if(upama.script != 'iast') 
            upama.permalit[0].innerHTML = upama.changeScript(upama.permalit[0].myIAST,upama.script);
        else 
            upama.permalit[0].innerHTML = upama.permalit[0].myIAST.innerHTML;

        delete upama.permalit[0].myOldText;
        upama.permalit[1].classList.remove('permahighlight');
        let lastlit = upama.permalit[1];
        upama.permalit = [];
        return lastlit;
    }
    return 0;
},

windowClick: function(e) {
/*    var node = event.target;
    var max = 10;
    for(let i=0;i<max;i++) {
        
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
    */
    if(e.target.closest('.permahighlight') === null)
        upama.unPermaLight();
},

to: {

    smush: function(text,placeholder) {
        text = text.toLowerCase();
        
        // remove space between a word that ends in a consonant and a word that begins with a vowel
        text = text.replace(/([drmvynhs]) ([aāiīuūṛeo])/g, '$1$2'+placeholder);
        
        // remove space between a word that ends in a consonant and a word that begins with a consonant
        text = text.replace(/([kgcjñḍtdnpbmrlyvśṣs]) ([kgcjṭḍtdnpbmyrlvśṣshḻ])/g, '$1'+placeholder+'$2');

        // join final o/e/ā and avagraha/anusvāra
        text = text.replace(/([oeā]) ([ṃ'])/g,'$1'+placeholder+'$2');

        // make nice daṇḍas
    //    text = text.replace(/\|\|/g,"॥");
    //    text = text.replace(/\|/g,"।");
       
        text = text.replace(/(_{1,2})(\s*)/g, function(match,p1,p2) {
            if(p1 == '__') //return '&zwj;' + p2;
                           return '\u200D' + p2;
            else if(p1 == '_') //return '&zwnj;' + p2;
                           return '\u200C' + p2;
        });
        
        return text;
    },

    iast: function(text,from) {
        var from = from || 'devanagari';
        return Sanscript.t(text,from,'iast',{skip_sgml: true});
    },

    devanagari: function(text,placeholder) {

        var text;
        var placeholder = placeholder || '';
        var options = {skip_sgml: true};

    //    text = jQuery("<textarea/>").html(text).text(); // decode HTML special characters
//        text = text.toLowerCase();

        text = upama.to.smush(text,placeholder);
        text = Sanscript.t(text,'iast','devanagari',options);
/*
        var initialVowels = {
            'अ': '',
            'आ':  'ा',
            'ए':  'े',
            'ऐ': 'ै',
            'इ':  'ि',
            'ई':  'ी',
            'उ':  'ु',
            'ऊ':  'ू',
            'ऋ':  'ृ',
    //        'अं': 'ं',
    //        'आं':'ां',
        };

        // remove space between a word that ends in a consonant and a word that begins with a vowel
        text = text.replace(/([दरमवयनहभस])् ([अआएइईउऐऊऋ])/g,  function(match,p1,p2) {
              return p1+initialVowels[p2]+placeholder;
        });
        
        // remove space between a word that ends in a consonant and a word that begins with a consonant
        text = text.replace(/([कगचजञडतदनमरवयसशषल]्)\s+([बभहङगघदधजझडढपफरकखतथचछटठमणनवलळसशषय])/g, '$1'+placeholder+'$2');

        // join final o/e/ā and avagraha/anusvāra
        text = text.replace(/([ेाो])\s+([ंऽ])/g,'$1'+placeholder+'$2');

        // make nice daṇḍas
    //    text = text.replace(/\|\|/g,"॥");
    //    text = text.replace(/\|/g,"।");
       
        text = text.replace(/(_{1,2})(\s*)/g, function(match,p1,p2) {
            if(p1 == '__') return '&zwj;';
            else if(p1 == '_') return '&zwnj;' + p2;
        });

    //    text = text.replace(/‾/g, 'ꣻ');

*/
        text = text.replace(/¯/g, 'ꣻ');


    /*
        // double-underscore represents a zero-width joiner
        text = text.replace(/__/g, "&zwj;");

        // underscore represents a virāma
        text = text.replace(/_/g, "&zwnj;");
    */

        // add zero-width joiner to viramas before a closing tag 
        //text = text.replace(/्</g, "्&zwj;<");


        return text;
    },
    
    malayalam: function(text,placeholder) {

        var text;
        var placeholder = placeholder || '';
        var options = {skip_sgml: true};
/*
	var initialVowels = {
	            'അ': '',
	            'ആ':  'ാ',
	            'ഇ':  'ി',
	            'ഈ': 'ീ',
	            'ഉ':  'ു',
	            'ഊ':  'ൂ',
	            'ഋ':  'ൃ',
	            'ൠ':  'ൄ',
	            'ഌ':  'ൢ',
	            'ൡ': 'ൣ',
	            'എ': 'െ',
                'ഏ': 'േ',
	            'ഐ': 'ൈ',
	            'ഒ': 'ൊ',
	            'ഓ': 'ോ',
	            'ഔ': 'ൌ',
        };
*/
	
	var chillu = {
		'ക':'ൿ',
		'ത':'ൽ',
		'ന':'ൻ',
		'മ':'ൔ',
		'ര':'ർ',
	};

    //    text = jQuery("<textarea/>").html(text).text(); // decode HTML special characters
//        text = text.toLowerCase();
        
        text = upama.to.smush(text,placeholder);
        text = text.replace(/e/g,'ẽ'); // hack to make long e's short
        text = text.replace(/o/g,'õ'); // same with o

        text = Sanscript.t(text,'iast','malayalam',options);
/*
	text = text.replace(/([ദനഭമയരവസഹ])് ([അആഇഈഉഊഋഎഏഐ])/g,  function(match,p1,p2) {
              return p1+initialVowels[p2]+placeholder;
        });
        
	text = text.replace(/([കഗചജഞതദനമയരലവശഷസ]്)\s+([കഖഗഘങചഛജഝടഠഡഢതഥദധനപഫബഭമയരലവശഷസഹ])/g, '$1'+placeholder+'$2');
*/	
        // use dot reph
        text = text.replace(/(^|[^്])ര്(?=\S)/g,'$1ൎ');
/*
        // join final o/e/ā and avagraha/anusvāra
        text = text.replace(/([ാെൊ])\s+([ൎഽ])/g,'$1'+placeholder+'$2');
*/
        
        // use chillu final consonants	
	text = text.replace(/([കതനമര])്(?![^\s_,])/g, function(match,p1) {
		return chillu[p1];
	});

/*	
	text = text.replace(/(_{1,2})(\s*)/g, function(match,p1,p2) {
            if(p1 == '__') return '&zwj;';
            else if(p1 == '_') return '&zwnj;' + p2;
        });
	

    //    text = text.replace(/‾/g, 'ꣻ');
        text = text.replace(/¯/g, 'ꣻ');

*/
        return text;
    },
    
    telugu: function(text,placeholder) {

        var text;
        var placeholder = placeholder || '';
        var options = {skip_sgml: true};

        text = upama.to.smush(text,placeholder);        
        text = text.replace(/e/g,'ẽ'); // hack to make long e's short
        text = text.replace(/o/g,'õ'); // same with o


/*
	var initialVowels = {
	                  'అ': '',
	                  'ఆ':  'ా',
	                  'ఇ':  'ి',
	                  'ఈ': 'ీ',
	                  'ఉ':  'ు',
	                  'ఊ':  'ూ',
	                  'ఋ': 'ృ',
	                  'ౠ':  'ౄ',
	                  'ఌ': 'ౢ',
	                  'ౡ': 'ౣ',
	                  'ఎ':  'ె',
	                  'ఏ': 'ే',
	                  'ఒ': 'ొ',
                                              'ఓ': 'ో',
	                  'ఐ': 'ై',
	                  'ఔ': 'ౌ',
        };
	

    //    text = jQuery("<textarea/>").html(text).text(); // decode HTML special characters
        text = text.toLowerCase();
*/

        text = Sanscript.t(text,'iast','telugu',options);
/*

       // remove space between a word that ends in a consonant and a word that begins with a vowel
        text = text.replace(/([దనబమయరవసహ])్ ([అఆఇఈఉఊఋఎఒఐ])/g,  function(match,p1,p2) {
              return p1+initialVowels[p2]+placeholder;
        });
        
        // remove space between a word that ends in a consonant and a word that begins with a consonant
        text = text.replace(/([కగచజఞతదనమయరలవశషస]్)\s+([కఖగఘచఛజఝటఠడఢతథదధనపఫబభమయరలవశషసహ])/g, '$1'+placeholder+'$2');

        // join final o/e/ā and avagraha/anusvāra
        text = text.replace(/([ేో])\s+([ంఽ])/g,'$1'+placeholder+'$2');

	
	text = text.replace(/(_{1,2})(\s*)/g, function(match,p1,p2) {
            if(p1 == '__') return '&zwj;';
            else if(p1 == '_') return '&zwnj;' + p2;
        });
	

    //    text = text.replace(/‾/g, 'ꣻ');
        text = text.replace(/¯/g, 'ꣻ');

*/
        return text;
    },
}, // end to:

/*
changeScript: function(node,lang,level = 0) {

    var func = (lang == 'deva') ? upama.toDevanagari : null;
    var node;
    if(typeof node == 'string') {
        let dummy = document.createElement('div');
        dummy.innerHTML = node;
        node = dummy;
    } 
    var kids = node.childNodes;
    var tags;

    for(let kid of kids) {

            if(kid.nodeType == 3) {
                kid.nodeValue = func(kid.nodeValue);
            }
            else if(kid.hasChildNodes() &&
                    kid.getAttribute('lang') != 'en')
                upama.changeScript(kid,lang,level+1);
    }
    if(level == 0)
        return node.innerHTML;
},
*/

changeScript: function(node,lang,level = 0,placeholder = false) {
/* it seems to be faster to change the innerHTML of a node than to create a DocumentFragment and then replace the node */
    var func = upama.to[lang];
    var node;
    if(typeof node == 'string') {
        let dummy = document.createElement('div');
        dummy.innerHTML = node;
        node = dummy;
    } 
    var kids = node.childNodes;
    var tags;
    var htmlstr;
    if(level > 0) {
        tags = upama.outerTags(node);
        htmlstr = tags[0];
    }
    else htmlstr = ''; 

    for(let kid of kids) {
            
            if(kid.nodeType == 8) continue;

            if(kid.nodeType == 3) {
/*                var htmlstr = func(kid.nodeValue);
                var frag = document.createRange().createContextualFragment(htmlstr);
                kid.parentNode.replaceChild(frag,kid); */
               if(lang == 'devanagari' && 
                  kid.parentNode.getAttribute('data-devanagari-glyph') &&
                  !/\s/g.test(kid.data)
                  ) {
                    htmlstr += kid.parentNode.getAttribute('data-devanagari-glyph');
                } 
                else htmlstr += func(kid.data,placeholder);
            }
            else if(kid.hasChildNodes() &&
                    kid.getAttribute('lang') != 'en') {
                // upama.changeScript(kid,lang,level+1);
                htmlstr += upama.changeScript(kid,lang,level+1,placeholder);
            }
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
    for(let attr of attrs) {
        start += " "+attr.name+"='"+attr.value+"'";
    }
    start += ">";
    var end = "</"+node.nodeName+">";
    return [start,end];

},
/**** highlight functions start here ****/

highlight: function(id,node,countonly = false) {
    var pos = id.split("x").map(function(e) {return parseInt(e,10)});
    /*for(let q=0;q<pos.length;q++)
        pos[q] = parseInt(pos[q]);
    */
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
                let kidtext = kid.data;
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
                        endnode = prevnode;
                        endpos = prevnode.length;
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
    
    if(pos.length > 2) {
        if(pos[2] > 0) middleRange = upama.countChars(middleRange,pos[2],"prefix");
        else if(pos.length == 4) middleRange = upama.countChars(middleRange,pos[3],"suffix");
    } 
    if(!countonly)
        upama.lightTextNodes(middleRange);
    else
        return middleRange;
},

countChars: function(range,pos,affix) {
    var affix = affix || "prefix";

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
        
       let strArr;
       strArr = Array.from(start.data.substring(startpos,endpos));
       
       let b = 0;
       let a = 0;
       for(a;a<strArr.length;a++) {
           if(strArr[a] == upama.hyphen)
               continue;
            if(b == pos)
                break;
            b++;
       }
       
       if(affix == "suffix")
           range.setEnd(start,startpos+a);
        else
            range.setStart(start,startpos+a);

        return range;
   } // end if(start == end)
   var skipKids = false;
    for(let node = start;node != upama.getNextNode(end);node = upama.getNextNode(node,skipKids)) {
        skipKids = false;
        if(node.nodeType != 3) {
            if(node.classList.contains('ignored'))
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

        let substr = node.data.substring(startpos);
        let strArr = Array.from(substr);
        
        let hyphens = substr.match(upama.hyphenRegex);
        hyphens = hyphens ? hyphens.length : 0;
        if((strArr.length-hyphens) <= pos) {
            pos = pos - strArr.length + hyphens;
            startpos = 0;
            continue;
        }
        
        else {
            let b = 0;
            let a = 0;
            for(a;a<strArr.length;a++) {
                if(strArr[a] == upama.hyphen)
                    continue;
                 if(b == pos)
                     break;
                 b++;
            }
            if(affix == "suffix")
                range.setEnd(node,startpos+a);
            else
                range.setStart(node,startpos+a);
            return range;
        }
   }
   return false;
},

getNextNode: function(node,skipKids = false) {
    if(node.firstChild && !skipKids)
        return node.firstChild;
    while(node) {
        if(node.nextSibling) return node.nextSibling;
        node = node.parentNode;
    }
//    return false;
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
            let textRange = start.ownerDocument.createRange();
            textRange.setStart(start,range.startOffset);
            textRange.setEnd(start,start.length);
            toHighlight.push(textRange);
        }

        for(let node = upama.getNextNode(start); node != end; node = upama.getNextNode(node)) {
            if(node.nodeType == 3) {
                let textRange = node.ownerDocument.createRange();
                textRange.selectNode(node);
                toHighlight.push(textRange);
            }
        }
        
        if(end.nodeType == 3 && range.endOffset > 0) {
            let textRange = end.ownerDocument.createRange();
            textRange.setStart(end,0);
            textRange.setEnd(end,range.endOffset);
            toHighlight.push(textRange);
        }
        for(let hiNode of toHighlight) {
// do highlighting at end so as not to add nodes during the tree traversal
            upama.highlightNode(hiNode);
        }
    }

},
/**** end of highlight functions ****/


/**** reverse-highlighting functions start here ****/

findVariants: function(e) {
    
    var sel = window.getSelection();
    if(sel.isCollapsed) return;

    var selrange = sel.getRangeAt(0);

    var targetClass = upama.mainClass;

    var target = selrange.startContainer.parentNode.closest(targetClass);
    var endtarget = selrange.endContainer.parentNode.closest(targetClass);
    if(target == null && endtarget == null) return;
    if(target.classList.contains('ui-accordion')) return;

    if(target.lastChild == selrange.startContainer && selrange.startOffset == selrange.startContainer.length) {
        let nextindex = [].indexOf.call(upama.mains,target) + 1;
        target = upama.mains[nextindex];
        if(sel.rangeCount > 1) // firefox returns multiple ranges, chrome doesn't
            selrange = sel.getRangeAt(1).cloneRange();
        selrange.setStart(target.firstChild,0);
    }
    
    if(upama.script != 'iast') {
        var iastRange = upama.changeScript(target.myIAST,upama.script,0,upama.placeholder);
        var iastText = document.createRange().createContextualFragment(iastRange);
        iastText = upama.cleanString(iastText);
    }

    var startinfo = {};
    var start = document.createRange();
    start.setStart(target.firstChild,0);
    start.setEnd(selrange.startContainer,selrange.startOffset);
    if(!start) return;
    if(start.collapsed == true)
        startinfo = {spaces: 0, startspace: 0, endspace: 0, stublen: 0};
    else {
        start = upama.cleanString(start.cloneContents());
        if(upama.script != 'iast') {
            start = upama.replaceSpaces(iastText,start,upama.placeholder);
            startinfo = upama.spaceCount(start,upama.placeholder);
        } else {
            startinfo = upama.spaceCount(start);
        }
    }

    var seltext = upama.cleanString(selrange.cloneContents());
    var lemmaText = seltext;
    var endinfo;
    if(upama.script != 'iast') {
        seltext = upama.replaceSpaces(iastText.substr(start.length),seltext,upama.placeholder);
        endinfo = upama.spaceCount(seltext,upama.placeholder);
    }
    else {
        endinfo = upama.spaceCount(seltext);
    }
    var startcount = startinfo.spaces + endinfo.startspace;
    var endcount = startinfo.spaces + endinfo.spaces + 1 - endinfo.endspace;
    
    if(startcount == 1 && startinfo.startspace == 1)
        startcount = 0;

    var app = target.classList.contains('sectiontext') ? 
        target.parentNode.parentNode.getElementsByClassName('variorum')[0] :
        target.parentNode.getElementsByClassName('variorum')[0];

    var variants = app.getElementsByClassName('varcontainer');
    var msids = new Set();
    for(let v of variants) {
        let loc = v.getAttribute('data-loc').split('x');
        if( (loc[0] >= startcount && loc[0] < endcount) ||
            (loc[0] <= startcount && loc[1] > startcount)
            ){
            if(startinfo.stublen > 0 && loc[3]) {
                if(loc[0] == (startcount - endinfo.startspace) && loc[3] <= startinfo.stublen)
                    continue;
                if(loc[0] < startcount) {
                    let range;
                    let pos = loc[0] + "x" + (loc[1] - 1);
                    let node = target;
                    if(upama.script != 'iast')
                        node = node.myIAST.cloneNode(true);
                    range = upama.highlight(pos,node,true);
                    let rangeCount = range.toString().replace(upama.hyphenRegex,'').length;
                    let prelen = loc[3] - (rangeCount + 1); // add 1 for a space
                    if(prelen <= startinfo.stublen)
                        continue;
                }
            }
            
            if(loc[2] && loc[2] > 0) {
                if(loc[0] == (startcount - endinfo.startspace) && !endinfo.spaces) {
                    let lemmalen = endinfo.endspace ? 
                        startinfo.stublen + lemmaText.replace(upama.hyphenRgex,'').length : 
                        startinfo.stublen + endinfo.stublen;
                    if(loc[2] >= (lemmalen + endinfo.endspace))
                        continue;
                }
                else if(loc[0] == (startinfo.spaces + endinfo.spaces)) {
                    let lemmalen = lemmaText.split(" ").pop().replace(upama.hyphenRegex,'').length;
                    if(loc[2] >= lemmalen)
                        continue;
                } 

            }
            
            let vv = v.getElementsByClassName('variant')[0];
            vv.classList.add('lowlight');
            upama.lowlit.push(vv);
            let mm = v.getElementsByTagName('a');
            for(let msid of mm) {
                msids.add(msid.textContent);           
            }
        }
    }
    
    var posText = '';
    var posApp = [];

    if(msids.size == 0) posText = 'no variants';
    else {
        let exclude = app.getAttribute('data-exclude');
        if(exclude) {
            exclude = exclude.split(' ');
            for(let x of exclude) msids.add(x);
        }
        posApp = upama.listWit.filter(([x,y]) => !msids.has(x));
        posText = posApp.map(function(n) {return n[1];}).join(", ");
        posAppKeys = posApp.map(function(n) {return n[0];});
        if(document.getElementById('__upama_stemma')) 
            posText += ' <span><svg id="__upama_stemma_icon" viewBox="0 0 20 20"><path d="M14.68,12.621c-0.9,0-1.702,0.43-2.216,1.09l-4.549-2.637c0.284-0.691,0.284-1.457,0-2.146l4.549-2.638c0.514,0.661,1.315,1.09,2.216,1.09c1.549,0,2.809-1.26,2.809-2.808c0-1.548-1.26-2.809-2.809-2.809c-1.548,0-2.808,1.26-2.808,2.809c0,0.38,0.076,0.741,0.214,1.073l-4.55,2.638c-0.515-0.661-1.316-1.09-2.217-1.09c-1.548,0-2.808,1.26-2.808,2.809s1.26,2.808,2.808,2.808c0.9,0,1.702-0.43,2.217-1.09l4.55,2.637c-0.138,0.332-0.214,0.693-0.214,1.074c0,1.549,1.26,2.809,2.808,2.809c1.549,0,2.809-1.26,2.809-2.809S16.229,12.621,14.68,12.621M14.68,2.512c1.136,0,2.06,0.923,2.06,2.06S15.815,6.63,14.68,6.63s-2.059-0.923-2.059-2.059S13.544,2.512,14.68,2.512M5.319,12.061c-1.136,0-2.06-0.924-2.06-2.06s0.923-2.059,2.06-2.059c1.135,0,2.06,0.923,2.06,2.059S6.454,12.061,5.319,12.061M14.68,17.488c-1.136,0-2.059-0.922-2.059-2.059s0.923-2.061,2.059-2.061s2.06,0.924,2.06,2.061S15.815,17.488,14.68,17.488"></path></svg></span>';
    }
    var selPos = selrange.getBoundingClientRect();
    var appBox = document.getElementById('__upama_positive_apparatus');
    if(!appBox) {
        appBox = document.createElement('div');
        appBox.id = '__upama_positive_apparatus';
        appBox.style.opacity = 0;
        appBox.style.transition = "opacity 0.2s ease-in";
        upama.contentbox.parentNode.insertBefore(appBox,upama.contentbox);
    }
    appBox.innerHTML = posText;
    appBox.style.top = (selPos.top - appBox.clientHeight - 5) + 'px';
    appBox.style.left = (selPos.left) + 'px';
    appBox.style.opacity = 1;
    var stemmaIcon = document.getElementById('__upama_stemma_icon');
    if(stemmaIcon) {
        stemmaIcon.addEventListener('click',upama.showStemma);
        stemmaIcon.myApp = posAppKeys;
        stemmaIcon.myLemma = lemmaText;
        stemmaIcon.myCounts = [startcount,endcount];
    }
    upama.contentbox.addEventListener('mousedown',upama.unLowLight);
    document.addEventListener('keydown',upama.unLowLight);
},

showStemma: function(ev) {
    var target = document.getElementById('__upama_stemma_icon');
    var posApp = target.myApp;
    var lemmaText = target.myLemma;
    var counts = target.myCounts;
    var listWit = [];
    for(let wit of upama.listWit)
        listWit[wit[0]] = wit[1];
    var varTexts = [];

    for(let el of upama.lowlit) {
        let msids = el.parentElement.getElementsByTagName('a');
        let varText = el.innerHTML;
        let locs = el.parentElement.getAttribute('data-loc').split('x');
        for(let m of msids) {
            let mname = m.textContent;
/*
            if(!varTexts[mname])
                varTexts[mname] = new Array();
            varTexts[mname].push(varText);
*/ 
            if(!varTexts[mname])
                varTexts[mname] = lemmaText.split(' ');
            
            let lemmaArr = varTexts[mname];
           // if(locs.length == 2) {
                let start = locs[0] - counts[0];
                let end = locs[1] - counts[0];
                if(end - start == 1) {
                    lemmaArr[start] = varText;
                    varTexts[mname] = lemmaArr;
                }
                else {
                    let lemmaStart = lemmaArr.slice(0,start);
                    let lemmaEnd = lemmaArr.slice(end);
                    let varArr = [varText];
                    for(let n=0;n<(end-start);n++)
                        varArr.push('');
                    varTexts[mname] = lemmaStart.concat(varText,lemmaEnd);
                }
         //   }

        }
    }
    var features = "menubar=no,location=no,status=no,height=520,width=520,centerscreen=yes";
    var stemmaWindow = window.open(DOKU_BASE+"lib/plugins/upama/stemma/","stemma",features);
    var xmlDoc = document.getElementById('__upama_stemma').firstChild;
    var dataObject = {nexml: xmlDoc, fileSource: true };
    stemmaWindow.dataObject = dataObject;
    stemmaWindow.posApp = posApp;
    stemmaWindow.listWit = listWit;
    stemmaWindow.lemmaText = lemmaText;
    stemmaWindow.varTexts = varTexts;
    stemmaWindow.onload = function() {
        stemmaWindow.init();
    }
},

cleanString: function (node,end) {
    var str = '';
    var skipKids = false;
    //var end = end ? end : false; // if end is undefined and kid is undefined, loop ends
    var kid = node.firstChild || node.startContainer;
    for(kid;kid != end;kid = upama.getNextNode(kid,skipKids)) {
        skipKids = false;
        if(kid.nodeType != '3') {
            if(kid.classList.contains('ignored'))
                skipKids = true;
            continue;
        }
        else str = str + kid.data;
    }
    return str;
},

spaceCount: function(str,marker) {
    var re = marker ? new RegExp("\\s+|"+marker,"g") : 
                      new RegExp("\\s+","g");
    var info = {spaces: 0};
    var last;
    while(re.exec(str)) {
        info.spaces++;
        last = re.lastIndex;
    }
    info.stublen = str.length-1 - last;
    var substr = upama.script == 'iast' ? str.substring(last) : upama.to.iast(str.substring(last),upama.script);
    var hyphens  = substr.match(upama.hyphenRegex);
    info.stublen = hyphens ? substr.length - hyphens.length : substr.length;
    info.endspace = str.substring(str.length-1).match(/\s/) ? 1 : 0;
    info.startspace = str.substr(0,1).match(/\s/) ? 1 : 0;
    return info;
},

replaceSpaces: function(str1,str2,marker) {
    var spaceArray = [];
    var spaceIndex;
    var startpos = 0;
    do {
        spaceIndex = str1.indexOf(marker,startpos);
        if(spaceIndex != -1) {
            spaceArray.push(spaceIndex);
            startpos = spaceIndex+1;
        }
    } while (spaceIndex != -1 && startpos < str1.length-1);
    for(let space of spaceArray) {
        if(space <= str2.length)
            str2 = str2.substring(0,space) + marker + str2.substring(space);
    }
    return str2;

},

unLowLight: function() {
    for(let v of upama.lowlit) {
        v.classList.remove('lowlight');
    }
    upama.lowlit = [];

    var appBox = document.getElementById('__upama_positive_apparatus');
    if(appBox) appBox.remove();
    upama.contentbox.removeEventListener('mousedown',upama.unLowLight);
    document.removeEventListener('keydown', upama.unLowLight);
},

removeHyphens: function(ev) {

    // only run the function on text nodes (i.e., ignore <textarea>s)
    if(ev.target.nodeType != '3') return;
    ev.preventDefault();
    var sel = window.getSelection().toString();
    sel = sel.replace(upama.hyphenRegex,'');
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
