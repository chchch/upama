/* DOKUWIKI:include_once sanscript.js */
/* DOKUWIKI:include_once jquery.hypher.js */
/* DOKUWIKI:include_once sa.js */

const upama = (function() {

    'use strict';
    const jQuery = window.jQuery;
    const Sanscript = window.Sanscript;
    const DOKU_BASE = window.DOKU_BASE;
    const consts = {
        hyphen: String.fromCodePoint('0xAD'),
        hyphenRegex: new RegExp('\u00AD','g'),
        nbsp: String.fromCodePoint('0x0A0'),    
        scripts: ['devanagari','balinese','bengali','malayalam','newa','sarada','telugu'],
        placeholder: String.fromCodePoint('0xFFFD'),
        vowelChars: 'aāiīuūṛṝḷḹeoêô',
        all_long_vowels: document.querySelector('.scriptNote[data-scriptnote="script-all-long-vowels"]')
    };

    Object.freeze(consts);

    const state = {
        mains: [],
        mainClass: '.sectiontext',
        //highlit: [],
        permalit: [],
        highlit: null,
        lowlit: new Set(),
        app2lit: null,
        script: 'iast',
        contentbox: null,
        listWit: [],
        otherWit: [],
        groupWit: new Map(),
        allWit: null,
        middle: null,
    //placeholder: "#",
    };

    const initialize = function() {
    
        const getvars = getUrlVars();

        //if(getvars['do'] && getvars['do'] !== 'show') return;
        if(getvars.has('do') && getvars.get('do') !== 'show') return;
        //    if(getvars['do'] == 'edit') return;

        const upama_ver = document.getElementById('__upama_ver');
        //    const current = false;
        if(upama_ver) {
            if(upama_ver.textContent !== '')
                //getvars['upama_ver'] = upama_ver.textContent;
                getvars.set('upama_ver',upama_ver.textContent);
        }
        else return; // no upama data
   
        //if(getvars['upama_script'] && 
        //consts.scripts.indexOf(getvars['upama_script']) > -1) {
        if(getvars.has('upama_script') &&
           consts.scripts.indexOf(getvars.get('upama_script')) > -1) {
            //state.script = getvars['upama_script'];
            state.script = getvars.get('upama_script');
            jQuery('#__upama_script_selector').val(state.script).change();
        }
        else if(document.getElementById('__upama_script_selector')) {
            state.script = document.getElementById('__upama_script_selector').value || 'iast';
            if(state.script !== 'iast')
                //getvars['upama_script'] = state.script;
                getvars.set('upama_script',state.script);
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
        rewriteURL(getvars);

        const apparati = document.getElementsByClassName('variorum');

        for(const apparatus of apparati) {
            apparatus.addEventListener('mouseover',listener.appMouseOver);
            apparatus.addEventListener('click',listener.appOnClick);
        } 
     
        state.mains = document.querySelectorAll('*:not(.apparatus2) > .maintext > .sectiontext');
        if(state.mains.length === 0) {
            state.mains = document.querySelectorAll('*:not(.apparatus2) > .maintext');
            state.mainClass = '.maintext';
        }

        // put in a space so that highlighting works
        for(const m of state.mains) {
            if(m.childNodes.length === 0) m.append(' ');
            else if(m.textContent.slice(-1) !== ' ')
                m.append(' ');
        }

        const witnesses = document.querySelectorAll('#__upama_listWit li');
        const groupWit = [];
        for(const w of witnesses) {
            if(w.getAttribute('data-source')) 
                state.otherWit.push([(w.getAttribute('data-msid')),w.innerHTML]);
                //state.otherWit.push([w.textContent,w.innerHTML]);
            else if(w.getAttribute('data-corresp')) {
                state.groupWit.set(w.dataset.msid,
                    w.dataset.corresp.split(' ').map(s => s.replace(/^#/,'')) );
                groupWit.push([w.dataset.msid,`<span class="msgroupname">${w.innerHTML}</span>`]);
            }
            else
                state.listWit.push([(w.getAttribute('data-msid')),w.innerHTML]);
                //state.listWit.push([w.textContent,w.innerHTML]);
        }
        state.allWit = new Map([...state.listWit,...state.otherWit,...groupWit]);
        //if(state.script != 'iast') 
        docSetScript(state.script,true);
    
        state.contentbox = document.getElementById('dokuwiki__content') || document.body;
     
        if(apparati.length !== 0) {
            state.contentbox.addEventListener('mouseup',listener.textMouseUp);
            document.addEventListener('keyup',listener.textMouseUp);
            //        if('ontouchstart' in window)
            //            state.contentbox.addEventListener('touchend',findVariants);
        }

        state.contentbox.addEventListener('click',listener.windowClick);

        state.contentbox.addEventListener('copy',listener.removeHyphens);

        state.contentbox.addEventListener('mouseover',listener.toolTipsAndLemmata);

        if(document.getElementById('__upama_script_selector'))
            document.getElementById('__upama_script_selector').addEventListener('change',listener.scriptSelectorChange);

        if(document.getElementById('__upama_export'))
            document.getElementById('__upama_export').addEventListener('change',listener.exportSelectorChange);

        if(document.getElementById('__upama_read_more'))
            document.getElementById('__upama_read_more').addEventListener('click',listener.seeMore);

        //const scrolltoN = getvars['upama_scroll'];
        const scrolltoN = getvars.get('upama_scroll');
        const scrollEl = scrolltoN ? document.getElementById(scrolltoN) : null;
        if(scrolltoN && scrollEl) {
            //        state.mains[scrolltoN].scrollIntoView({behavior: "smooth"});
            /*
            jQuery(document).ready(function() {
                if(jQuery(window).scrollTop() == '0') {
                    jQuery('html, body').animate({
                        scrollTop: jQuery('[id=\''+scrolltoN+'\']').offset().top - 20
                    }, 2000);
                }
            });
            */
            scrollEl.classList.add('__upama_shadow');
            scrollEl.scrollIntoView({behavior: 'smooth',block: 'center'});
            document.addEventListener('click',() => {
               scrollEl.classList.remove('__upama_shadow'); 
            },{once: true});
        }

        for(const el of document.querySelectorAll('.lazyhide'))
            el.style.display = 'unset';

    };

    const viewPos = {
        get: function() {
            const summary = jQuery('#__upama_summary');
            return summary.length === 0 ?
                null :
                viewPos.inViewport(summary) ? 
                    null : 
                    viewPos.findMiddleElement();
            /*
        if(summary.length) {
            const middle = inViewport(summary) ? null : findMiddleElement();
        }
        else const middle = null;
        state.middle = middle;
    */
        },
     
        set: function(middle) {
            if(!middle) return;
            //var $pane = jQuery('.ui-layout-pane-center').first();
            //$pane.scrollTop(0);
            const scrollpos = middle[0].offset().top + middle[1] - jQuery(window).height()/2;
            window.scrollTo(0,scrollpos);
        //$pane.scrollTop(scrollpos);
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
                if(lastDist !== null && Math.abs(currDist) > Math.abs(lastDist)) {
                    $midEl = jQuery(els[i-1]);
                    currDist = lastDist;
                    break;
                }
            }
            if($midEl === null)
                $midEl = jQuery(els[ellen-1]);
            return [$midEl,currDist];
        },

    };

    const listener = {

        showMatrix:  function() {
            showPopup(DOKU_BASE+'lib/plugins/upama/matrix/',true);
        },

        showStemma: function() {
            showPopup(DOKU_BASE+'lib/plugins/upama/stemma/',false);
        },

        appMouseOver: function(e) {
            const targ = e.target;
            const mshover = targ.closest('.mshover');
            if(mshover) {
                listener.msidMouseOver(mshover);
                return;
            }
            if(targ.classList.contains('msgroupname')) {
                listener.msgroupMouseOver(targ);
                return;
            }
            const closest = targ.closest('.variant');
            if(closest) listener.varMouseOver(closest);
        },

        appOnClick: function(e) {
            const targ = e.target;
            const closest = targ.closest('.variant');
            if(closest) listener.varOnClick(false,closest);
        },

        varMouseOver: function(target) {
            const _this = target || this;
            //    _this = _this.target || _this;


            if(state.lowlit.size !== 0) {
                //        if(state.lowlit[0].closest('.variorum') == _this.closest('.variorum')) {
                listener.unLowLight();
                window.getSelection().removeAllRanges();
                //        }
            }
            if(state.permalit.length === 0 || state.permalit[1] !== _this) {
                const hipos = _this.parentNode.getAttribute('data-loc');
                /*        var orignode = state.mains[
                [].indexOf.call(upama.apparati,_this.closest('.variorum'))
                ];
    */
                const orignode = document.getElementById(
                    _this.closest('.apparatus').getAttribute('data-target')
                ).querySelector(state.mainClass);
                if(state.script !== 'iast') {
                    //    if(!orignode.hasOwnProperty('myOldText')) {
                    //        var tempnode = orignode.myIAST.cloneNode(true);
                    //        orignode.myOldText = tempnode.cloneNode(true);
                    //    }
                    //    else var tempnode = orignode.myOldText.cloneNode(true);

                    const oldtext = orignode.myOldText || orignode.myIAST;
                    const tempnode = oldtext.cloneNode(true);
                    highlight(hipos,tempnode);
                    state.highlit = tempnode;
                    orignode.innerHTML = changeScript(tempnode,state.script);
                    //            orignode.innerHTML = '';
                    //            orignode.appendChild(changeScript(tempnode,state.script));
                }

                else {
                    if(!orignode.hasOwnProperty('myIAST')) 
                        orignode.myIAST = orignode.cloneNode(true);
                    highlight(hipos,orignode);
                } 
            }

            _this.addEventListener('mouseout',listener.varMouseOut, {once: true});
        },

        varMouseOut: function(e) {
            const _this = e.target;
            //    _this = _this.target ? _this.target : _this;
            /*
        var orignode = state.mains[
                [].indexOf.call(upama.apparati,_this.closest('.variorum'))
            ];
    */
            const orignode = document.getElementById(
                _this.closest('.apparatus').getAttribute('data-target')
            ).querySelector(state.mainClass);
            //        var orignode = _this.closest('.apparatus').previousSibling;
            const oldtext = orignode.myOldText || orignode.myIAST;

            if(state.permalit[1] !== _this && oldtext) {
                if(state.script !== 'iast') {
                    orignode.innerHTML = changeScript(oldtext,state.script);
                    //            orignoconst = '';
                    //            orignode.appendChild(changeScript(oldtext,state.script));
                }
                else
                // orignode.parentNode.replaceChild(orignode.myOldText,orignode);
                    orignode.innerHTML = oldtext.innerHTML;
            }
        },

        varOnClick: function(ev,el) {

            const _this = el || this;
            //var newnode;

            if(_this.classList.contains('permahighlight')) // clicked on an already highlighted element
                listener.unPermaLight();

            else {
                //        var orignode = _this.closest('.apparatus').previousSibling;
                /*        var orignode = state.mains[
                [].indexOf.call(upama.apparati,_this.closest('.variorum'))
                ];
    */

                const orignode = document.getElementById(
                    _this.closest('.apparatus').getAttribute('data-target')
                ).querySelector(state.mainClass);
                if(state.permalit.length > 0) { 
                // clear other permahighlighted variant

                    if(state.script !== 'iast') {
                        const tempnode = state.permalit[0].myIAST.cloneNode(true);
                        state.permalit[0].innerHTML = changeScript(tempnode,state.script);
                        //                state.permalit[0].innerHTML = '';
                        //                state.permalit[0].appendChild(changeScript(tempnode,state.script));
                    }
                    else 
                        state.permalit[0].innerHTML = state.permalit[0].myIAST.innerHTML;
                    delete state.permalit[0].myOldText;

                    if(state.permalit[0] == orignode) {
                    // re-highlight the node if we just cleared it
                        const hipos = _this.parentNode.getAttribute('data-loc');
                        if(state.script !== 'iast') {
                            const newnode = orignode.myIAST.cloneNode(true);
                            highlight(hipos,newnode);
                            //orignode.myOldText = newnode.cloneNode(true);
                            state.highlit = newnode;
                            orignode.innerHTML = changeScript(newnode,state.script);
                            //                    orignode.innerHTML = '';
                            //                    orignode.appendChild(changeScript(newnode,state.script));
                        } else {
                            //    if(!orignode.hasOwnProperty('myOldText'))
                            //        orignode.myOldText = orignode.cloneNode(true);
                            highlight(hipos,orignode);
                        }
                    }

                    state.permalit[1].classList.remove('permahighlight');
                }

                // permahighlight new element
            
                state.permalit = [orignode,_this];
                if(state.script !== 'iast') {
                    orignode.myOldText = state.highlit.cloneNode(true);
                    /*            if(!newnode) {
                    let hipos = _this.parentNode.getAttribute('data-loc');
                    newnode = orignode.myIAST.cloneNode(true);
                    highlight(hipos,newnode);
                    orignode.myOldText = state.highlit.cloneNode(true);
                    orignode.myOldText = state.highlit.cloneNode(true);
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
            let container = _this.closest('.varcontainer');
            //let varNode = container.getElementsByClassName("variant")[0];
            let varNode = container.querySelector('.variant');
            /*
        if(container.offsetLeft != container.parentNode.offsetLeft) {
            //container.style.whiteSpace = 'normal';
            //container.style.wordWrap = 'break-word';
            container.classList.add('nowrap');
        } // if the container is at the beginning of the line, don't allow line breaks between the sigla and the variant reading, otherwise the sigla might be shifted to the end of the previous line
    */
            if(container.offsetLeft !== container.parentNode.offsetLeft) {
            // allow breaks between sigla and variant if
            // the container isn't at the beginning of the line;
            // otherwise the sigla might be shifted to the end of the previous
            // line
                container.classList.add('wrap');
            }
            if(!varNode.hasOwnProperty('myOldReading'))
                varNode.myOldReading = varNode.innerHTML;
            varNode.innerHTML = container.querySelector('span[data-ms=\''+_this.dataset.msid+'\']').innerHTML;
            varNode.classList.add('varreading');
            _this.addEventListener('mouseout',listener.msidMouseOut,{once: true});
        },

        msidMouseOut: function() {
            let container = this.closest('.varcontainer');
            //let varNode = container.getElementsByClassName("variant")[0];
            let varNode = container.querySelector('.variant');
            //container.style.whiteSpace = 'nowrap';
            //container.style.wordWrap = 'normal';
            //container.classList.remove('nowrap');
            container.classList.remove('wrap');
            varNode.innerHTML = varNode.myOldReading;
            varNode.classList.remove('varreading');
        },
        
        msgroupMouseOver: function(target) {
            const par = target.parentNode;
            const msdetail = par.querySelector('span.msdetail');
            msdetail.style.display = 'inline';
            par.addEventListener('mouseleave',listener.msgroupMouseLeave,{once: true});
        },

        msgroupMouseLeave: function() {
            this.querySelector('span.msdetail').style.display = 'none';
        },

        unPermaLight: function() {
            if(state.permalit.length > 0) {
                if(state.script !== 'iast') {
                    state.permalit[0].innerHTML = changeScript(state.permalit[0].myIAST,state.script);
                    //            state.permalit[0].innerHTML = '';
                    //            state.permalit[0].appendChild(changeScript(state.permalit[0].myIAST,state.script));
                }
                else 
                    state.permalit[0].innerHTML = state.permalit[0].myIAST.innerHTML;

                delete state.permalit[0].myOldText;
                state.permalit[1].classList.remove('permahighlight');
                let lastlit = state.permalit[1];
                state.permalit = [];
                return lastlit;
            }
            return 0;
        },

        removeHyphens: function(ev) {

            // ignore <textarea>s
            //if(ev.target.nodeType === 1 && ev.target.nodeName === 'TEXTAREA') return;
            if(ev.target.closest('textarea')) return;
            ev.preventDefault();
            var sel = window.getSelection().toString();
            sel = sel.replace(consts.hyphenRegex,'');
            (ev.clipboardData || window.clipboardData).setData('Text',sel);
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
                listener.unPermaLight();
            if(e.target.getAttribute('data-anchor'))
                listener.noteClick(e.target);
        },

        seeMore: function() {
            const physDesc = document.getElementById('__upama_physDesc');
            const history = document.getElementById('__upama_history');
            const msItems = document.getElementsByClassName('__upama_msItem');
            const seeMoreEl = document.getElementById('__upama_read_more');
            var display;
            if(physDesc.style.display === 'table') {
                display = 'none';
                seeMoreEl.innerHTML = 'More &#9662;';
            }
            else {
                display = 'table';
                seeMoreEl.innerHTML = 'Less &#9652;';
            }

            physDesc.style.display = display;
            history.style.display = display;
            for(const item of msItems) 
                item.style.display = display;
        },

        toolTipsAndLemmata: function(e) {
            let target = e.target.closest('[data-balloon]');
            while(target && target.hasAttribute('data-balloon')) {
                listener.toolTip(e,target);
                target = target.parentNode;
            }
            const lemma = e.target.classList.contains('lemma');
            if(lemma) {
                if(e.target.classList.contains('embedded'))
                    listener.showReadings(e.target);
                else
                    listener.showLemma(e.target);
            }
        },
        showReadings(targ) {
            const par = targ.closest('.upama-block');
            const allleft = [...par.querySelectorAll('.embedded.lemma')];
            const pos = allleft.indexOf(targ);
            const right = par.querySelector('.apparatus2');
            const allright = right.querySelectorAll('.lemma');
            const tolight = allright[pos];
            tolight.classList.add('lowlight');
            state.app2lit = tolight;
            targ.addEventListener('mouseout',listener.hideReadings);
        },
        showLemma(targ) {
            const par = targ.closest('.apparatus2');
            const allright = [...par.querySelectorAll('.lemma')];
            const pos = allright.indexOf(targ);
            const left = par.closest('.upama-block').querySelector(state.mainClass);
            const allleft = left.querySelectorAll('.embedded.lemma');
            const tolight = allleft[pos];
            tolight.classList.add('lowlight');
            state.app2lit = tolight;
            targ.addEventListener('mouseout',listener.hideReadings);
        },
        hideReadings(e) {
            state.app2lit.classList.remove('lowlight');
            state.app2lit = null;
            e.target.removeEventListener('mouseout',listener.hideReadings);
        },
        /*
        showReadings: function(targ) {
            const apparatus = targ.closest('.apparatus');
            const rdgs = apparatus.querySelectorAll('.embedded.reading');
            for(const rdg of rdgs)
                rdg.style.display = 'inline';
            apparatus.addEventListener('mouseleave',listener.hideReadings);
        },
        
        hideReadings: function(e) {
            const apparatus = e.target;
            const rdgs = apparatus.querySelectorAll('.embedded.reading');
            for(const rdg of rdgs)
                rdg.style.display = 'none';
            apparatus.removeEventListener('mouseleave',listener.hideReadings);
        },
        */
        toolTip: function(e,target) {
            const tooltext = target.getAttribute('data-balloon');
            if(!tooltext) return;

            var tipBox = document.getElementById('__upama_tooltip');
            var tiptext;
        
            if(tipBox) {
                for(const kid of tipBox.childNodes) {
                    if(kid.myTarget === target)
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
                /*
                tipBox.style.opacity = 0;
                tipBox.style.transition = 'opacity 0.2s ease-in';
                */
                state.contentbox.parentNode.insertBefore(tipBox,state.contentbox);

                tiptext = document.createElement('div');
                tiptext.myTarget = target;
            }

            tiptext.appendChild(document.createTextNode(tooltext));
            tiptext.myTarget = target;
            tipBox.appendChild(tiptext);
            target.addEventListener('mouseleave',listener.toolTipRemove,{once: true});

            /*
            const temp = window.getComputedStyle(tipBox).opacity;
            tipBox.style.opacity = 1;
            */
            tBox.animate([
                {opacity: 0},
                {opacity: 1, easing: 'ease-in'}
                ], 200);
        },

        toolTipRemove: function(e) {
            const targ = e.target;
            const tipBox = document.getElementById('__upama_tooltip');

            if(tipBox.children.length === 1)
                tipBox.remove();
            else {
                for(const kid of tipBox.childNodes) {
                    if(kid.myTarget == targ) {
                        kid.remove();
                        break;
                    }
                }
                if(tipBox.children.length === 1) {
                    const kid = tipBox.firstChild.firstChild;
                    if(kid.tagName == 'HR')
                        kid.remove();
                }
            }
        },

        noteClick: function(el) {

            const ref = el.getAttribute('data-anchor');
            const par_id = el.closest('.upama-block').getAttribute('id');
            const app = document.querySelector(`[data-target="${par_id}"]`);
            const app_items = app.getElementsByClassName('apparatus2-item');
            for(const item of app_items) {
                if(item.getAttribute('data-target') === ref) {
                    item.classList.add('lowlight');
                    state.lowlit.add(item);
                    const accordion = item.closest('.accordion-item').querySelector('.accordion-item-input');
                    if(!accordion.checked) accordion.checked = true;
                }
            }
            el.classList.add('lowlight');
            state.lowlit.add(el);
            state.contentbox.addEventListener('mousedown',listener.unLowLight);
            document.addEventListener('keydown',listener.unLowLight);
        },

        scriptSelectorChange: function(e) {
            //const _this = this;
            const _this = e.target;
            const script = _this.value;
            const hidden_sel = document.getElementById('__upama_hidden_script_selector');
            if(hidden_sel) hidden_sel.value = script;
            docSetScript(script,false,state.script);
            state.script = script;
            if(script !== 'iast') rewriteURL('',{upama_script: script});
            else rewriteURL('',{upama_script: null});
            e.target.blur();
        },

        exportSelectorChange: function(e) {
            //const _this = this;
            const _this = e.target;
            const url = window.location.href;
            const prefix = url.indexOf('?') > -1 ? '&' : '?';

            if(_this.value === 'fasta')
                window.location.href = url + prefix + 'do=export_fasta';
        
            else if(_this.value == 'tei')
                window.location.href = url + prefix + 'do=export_tei';

            else if(_this.value == 'latex')
                window.location.href = url + prefix + 'do=export_latex';
           
            else if(_this.value == 'fastt') showExportWindow();
            _this.value = 'default';
        },
        
        textMouseUp: function(/*e*/) {
            const sel = find.selection();
            if(sel === false) return;

            const myData = find.variants(sel);
            if(!myData) return;
            
            const posApp = makePosApp(myData.app,myData.msids);
            myData.app = posApp.appKeys;
            myData.posText = posApp.text;
            showPosApp(myData);
        },

        unLowLight: function() {
            for(const v of state.lowlit) {
                v.classList.remove('lowlight');
            }
            state.lowlit = new Set();

            const appBox = document.getElementById('__upama_positive_apparatus');
            if(appBox) appBox.remove();
        
            state.contentbox.removeEventListener('mousedown',listener.unLowLight);
            document.removeEventListener('keydown', listener.unLowLight);
        },

    }; // end listener

    const showExportWindow = function() {
        const submitClick = function(e) {
            e.preventDefault();
            const opts = [];
            const exportform = document.getElementById('exportform');
            const inputs = exportform.getElementsByTagName('input');
            for(const i of inputs)
                if(i.checked) opts.push(i.name);
            const selects = exportform.getElementsByTagName('select');
            for(const s of selects)
                opts.push(`${s.name}~${s.value}`);
            document.body.removeChild(blackout);
            const url = window.location.href;
            const prefix = url.indexOf('?') > -1 ? '&' : '?';
            const opttxt = opts.join('+');
            window.location.href = url + prefix + 'do=export_fasta2&export_options=' + opttxt;
        };
        const blackoutClick = function(e) {
            const targ = e.target.closest('.popup');
            if(!targ) {
                const blackout = document.querySelector('#__upama_blackout');
                blackout.parentNode.removeChild(blackout);
            }
        };

        const blackout = document.createElement('div');
        blackout.id = '__upama_blackout';
        const frag = document.createRange().createContextualFragment(
            `<div id="exportoptions" class="popup">
    <form id="exportform">
      <div style="font-weight: bold">Export to FASTT</div>
      <div>
        <input type="checkbox" id="option_normalize" name="option_normalize"><label for="option_normalize">Apply filters</label>
      </div>
      <div>
        <input type="checkbox" id="option_zip" name="option_zip" checked><label for="option_zip">Separate files for each node</label>
      </div>
      <div>
        <label for ="option_start">Start node</label><select id="option_start" name="option_start"></select>
      </div>
      <div>
        <label for ="option_end">End node</label><select type="select" id="option_end" name="option_end"></select>
      </div>
      <div>
        <button type="submit">Export</button>
      </div>
    </form>
</div>`
        );
        blackout.appendChild(frag);
        document.body.appendChild(blackout);
        const startnode = blackout.querySelector('#option_start');
        const endnode = blackout.querySelector('#option_end');
        const nodes = [...document.getElementsByClassName('upama-block')];
        const nodeids = nodes.reduce((acc,cur) => {
            if(!cur.classList.contains('apparatus2')) acc.push(cur.id);
            return acc;
        },[]);
        for(const i of nodeids) {
            const o = document.createElement('option');
            o.value = i;
            o.appendChild(document.createTextNode(i));
            const o2 = o.cloneNode(true);
            startnode.appendChild(o);
            endnode.appendChild(o2);
        }
        const submit = blackout.querySelector('button');
        submit.addEventListener('click',submitClick);
        blackout.addEventListener('click',blackoutClick);
    };

    const rewriteURL = function(getvars,morevars) {
        const vars = getvars || getUrlVars();
        const href = window.location.href.split('?')[0];
        if(morevars) {
            for(let prop in morevars) {
                if(morevars.hasOwnProperty(prop)) {
                    if(morevars[prop])
                        //vars[prop] = morevars[prop];
                        vars.set(prop,morevars[prop]);
                    else {
                        //if(vars.hasOwnProperty(prop))
                        //delete vars[prop];
                        if(vars.has(prop))
                            vars.delete(prop);
                    }
                }
            }
        }

        //newurl = newurl.split('?')[0];
        const newparams = vars.toString();
        //if(Object.keys(vars).length > 0) {
        if(newparams !== '') {
            /*newurl = newurl + '?';
            let varkeys = Object.keys(vars);
            for(let v=0;v<varkeys.length;v++) {
                newurl = newurl + varkeys[v] + '=' + vars[varkeys[v]];
                if(v<varkeys.length-1) 
                    newurl = newurl + '&';
            }
            */
            const newurl = href + '?' + vars.toString();
            window.history.replaceState('','Text in '+state.script+' script',newurl);
        }
        else window.history.replaceState('','',href);

    };

    const docSetScript = function(script,init = false,oldscript = false) {
    
        const vpos = !init ? viewPos.get() : null;
        //   var middle = (init || inViewport(jQuery('#__upama_summary'))) ? null : findMiddleElement();
    
        //    var san = document.querySelectorAll("[lang=sa]");
        const san = document.querySelectorAll('h2[lang=sa],h3[lang=sa],p[lang=sa], div.verse[lang=sa], div'+state.mainClass+'[lang=sa], .varcontainer [lang=sa], li.apparatus2-item [lang=sa], #__upama_summary [lang=sa], .__upama_msItem [lang=sa], #__upama_physDesc [lang=sa], #__upama_history [lang=sa]');
        for(const _this of san) {
            if(init) {
                /*            let htmlstr = _this.innerHTML;
            // don't break before a daṇḍa
            htmlstr = htmlstr.replace(/\s+\|/g,"&nbsp;|");
            // don't break between daṇḍa and a numeral or puṣpikā
            htmlstr = htmlstr.replace(/\|\s+(?=[\d꣸])/g,"|&nbsp;");
            _this.innerHTML = htmlstr;
*/
                const textWalk = document.createTreeWalker(_this,NodeFilter.SHOW_TEXT,null,false);
                while(textWalk.nextNode()) {
                    const txt = textWalk.currentNode.data
                        .replace(/\s+\|/g,consts.nbsp+'|') // don't break before daṇḍa
                        .replace(/\|\s+(?=[\d꣸])/g,'|'+consts.nbsp); // don't break between daṇḍa and numeral/puṣpikā
                    textWalk.currentNode.data = window.Hypher.languages.sa.hyphenateText(txt);
                }
                //            jQuery(_this).hyphenate('sa');
                //            jQuery(_this).find('*').hyphenate('sa');
            }
            if(script !== 'iast') {
                if(!_this.myIAST)
                    _this.myIAST = _this.cloneNode(true);

                const oldtext = _this.myOldText || _this.myIAST;
            
                //            _this.innerHTML = '';
                //            _this.appendChild(changeScript(oldtext,script));
                _this.innerHTML = changeScript(oldtext,script);

                if(oldscript)
                    _this.classList.remove(oldscript);
                _this.classList.add(script);
            }
            else if(!init) { // && script == 'iast') {
                const oldtext = _this.myOldText || _this.myIAST;
                if(oldtext) {
                    _this.innerHTML = oldtext.innerHTML;
                    _this.classList.remove(oldscript);
                }
            }
            if(_this.hasOwnProperty('myOldReading'))
                delete _this.myOldReading;
        }

        viewPos.set(vpos);
    };

    const to = {
    
        options: {skip_sgml: true},

        smush: function(text,placeholder) {
            const vowelRegex = new RegExp('([ḍdrmvynhs])\\s+(['+consts.vowelChars+']+)','g');
            const smushed = text.toLowerCase()
            // remove space between a word that ends in a consonant and a word that begins with a vowel
                .replace(vowelRegex, '$1$2'+placeholder)
            // remove space between a word that ends in a consonant and a word that begins with a consonant
                .replace(/([kgcjṭḍtdpb])\s+h/g, '$1\u200C'+placeholder+'h') // is there a better way to deal with this?
                .replace(/([kgcjñḍtdnpbmrlyvśṣsṙ])\s+([kgcjṭḍtdnpbmyrlvśṣshḻ])/g, '$1'+placeholder+'$2')
            // join final o/e/ā and avagraha/anusvāra
                .replace(/([oôeêā])\s+([ṃ'])/g,'$1'+placeholder+'$2')
                .replace(/^ṃ/,'\'\u200Dṃ') // initial anusvāra
                .replace(/^ḥ/,'\'\u200Dḥ') // initial visarga
                .replace(/^_y/,'\'\u200Dy') // half-form of ya
                .replace(/ü/g,'\u200Cu')
                .replace(/ï/g,'\u200Ci')
                .replace(/_{1,2}(?=\s*)/g, function(match) {
                    if(match == '__') return '\u200D';
                    else if(match == '_') return '\u200C';
                });

            return smushed;
        },

        iast: function(text,from) {
            return Sanscript.t(text,(from || 'devanagari'),'iast',to.options);
        },

        devanagari: function(txt,placeholder) {

            var text = txt;

            text = text.replace(/ṙ/g, 'r')
                .replace(/(^|\s)_ā/,'$1\u093D\u200D\u093E')
                .replace(/(^|\s)_r/,'$1\u093D\u200D\u0930\u094D');

            text = to.smush(text, (placeholder || '') );

            text = Sanscript.t(text,'iast','devanagari',to.options);

            text = text.replace(/¯/g, 'ꣻ');

            return text;
        },
        
        newa: function(txt,placeholder) {
            var text = txt;
            text = text.replace(/ṙ/g, 'r')
                .replace(/ḿ/g,'ṃ')
                .replace(/î/g,'i') // no pṛṣṭhamātrās
                .replace(/û/g,'u')
                .replace(/ô/g,'o')
                .replace(/ê/g,'e')
                .replace(/(^|\s)_ā/,'$1\u093D\u200D\u093E')
                .replace(/(^|\s)_r/,'$1\u093D\u200D\u0930\u094D');
            text = to.smush(text, (placeholder || '') );
            text = Sanscript.t(text,'iast','newa',to.options);

            text = text.replace(/¯/g, 'ꣻ');
            return text;
        },

        sarada: function(txt,placeholder) {
            var text = txt;
            text = text.replace(/ṙ/g, 'r')
                .replace(/ḿ/g,'ṃ')
                .replace(/î/g,'i') // no pṛṣṭhamātrās
                .replace(/û/g,'u')
                .replace(/ô/g,'o')
                .replace(/ê/g,'e')
                .replace(/([ḫẖ])\s+/,'$1')
                .replace(/(^|\s)_ā/,'$1\u093D\u200D\u093E')
                .replace(/(^|\s)_r/,'$1\u093D\u200D\u0930\u094D');
            text = to.smush(text, (placeholder || '') );
            text = Sanscript.t(text,'iast','sarada',to.options);

            text = text.replace(/¯/g, 'ꣻ');
            return text;
        },

        malayalam: function(txt,placeholder) {

            var text = txt;
	
            const chillu = {
                'ക':'ൿ',
                'ത':'ൽ',
                'ന':'ൻ',
                'മ':'ൔ',
                'ര':'ർ',
            };

            text = text.replace(/(^|\s)_ā/,'$1\u0D3D\u200D\u0D3E');
            //.replace(/(^|\s)_r/,"$1\u0D3D\u200D\u0D30\u0D4D");
            //FIXME (replaced by chillu r right now)

            text = to.smush(text,(placeholder || ''));
            
            if(!consts.all_long_vowels) {
                text = text.replace(/[eê]/g,'ẽ') // hack to make long e's short
                    .replace(/[oô]/g,'õ') // same with o
                    .replace(/ē/,'e')
                    .replace(/ō/,'o');
            }

            text = text
                .replace(/ṙ/g,'r') // no valapalagilaka
                .replace(/ṁ/g,'ṃ') // no malayalam oṃkāra sign
                .replace(/ḿ/g,'ṃ')
                .replace(/î/g,'i') // no pṛṣṭhamātrās
                .replace(/û/g,'u');

            text = Sanscript.t(text,'iast','malayalam',to.options);
	
            // use dot reph
            text = text.replace(/(^|[^്])ര്(?=\S)/g,'$1ൎ')        
            // use chillu final consonants	
                .replace(/([കതനമര])്(?![^\s\u200C,—’―])/g, function(match,p1) {
                    return chillu[p1];
                });
	
            return text;
        },
    
        telugu: function(txt,placeholder) {

            var text = txt;

            text = text.replace(/(^|\s)_ā/,'$1\u0C3D\u200D\u0C3E')
                .replace(/(^|\s)_r/,'$1\u0C3D\u200D\u0C30\u0C4D');
            // FIXME: should be moved to the right of the following consonant

            text = to.smush(text,(placeholder || ''));        
            if(consts.all_long_vowels) {
                text = text.replace(/[eê]/g,'ẽ') // hack to make long e's short
                    .replace(/[oô]/g,'õ') // same with o
                    .replace(/ē/,'e')
                    .replace(/ō/,'o');
            }

            text = text
                .replace(/ṙ/g,'r\u200D') // valapalagilaka
                .replace(/ṁ/g,'ṃ') // no telugu oṃkāra sign
                .replace(/ḿ/g,'ṃ')
                .replace(/î/g,'i') // no pṛṣṭhamātrās
                .replace(/û/g,'u');

            text = Sanscript.t(text,'iast','telugu',to.options);

            return text;
        },
        
        bengali: function(txt,placeholder) {

            const pretext = txt.replace(/ṙ/g, 'r')
                .replace(/ẽ/g,'e')
                .replace(/õ/g,'o')
                .replace(/(^|\s)_ā/g,'$1\u093D\u200D\u093E')
                .replace(/(^|\s)_r/g,'$1\u093D\u200D\u0930\u094D');

            const smushed = to.smush(pretext, (placeholder || '') );

            const text = Sanscript.t(smushed,'iast','bengali')
                .replace(/¯/g, 'ꣻ')
                .replace(/ত্(?=\s)|ত্$/g,'ৎ');
            return text;
        },

        balinese: function(txt,placeholder) {

            var text = txt;
            const options = {skip_sgml: true};

            const presmush = text.toLowerCase()
                .replace(/ö/g, 'õ')
                .replace(/[əě]/g, 'ẽ')
                .replace(/ [ẽ]/g, ' hẽ')
                .replace(/ õ/g, ' hõ')
                .replace(/w/g, 'v');

            const smushed = to.smush(presmush,(placeholder || ''));

            return Sanscript.t(smushed,'iast','balinese',options)
                .replace(/ᬗ᭄(?![^\s,.:])/g,'ᬂ')
                .replace(/ᬭ᭄(?![^\s,.:])/g,'ᬃ')
                .replace(/ᬳ᭄(?![^\s,.:])/g,'ᬄ');
        },
    }; // end to:

    /*
changeScript: function(node,lang,level = 0) {

    var func = (lang == 'deva') ? toDevanagari : null;
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
                changeScript(kid,lang,level+1);
    }
    if(level == 0)
        return node.innerHTML;
},
*/

    /*
changeScript: function(node,script,level = 0,placeholder = false,cur_script="sa") {
// it seems to be faster to change the innerHTML of a node than to create a DocumentFragment and then replace the node 
    var func = to[script];
    var node;
    if(typeof node == 'string') {
        let dummy = document.createElement('div');
        dummy.innerHTML = node;
        node = dummy;
    } 
    var cur_script;
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
                if(cur_script != 'sa')
                    htmlstr += kid.data;
                else {
    //                var htmlstr = func(kid.nodeValue);
    //                var frag = document.createRange().createContextualFragment(htmlstr);
   //                 kid.parentNode.replaceChild(frag,kid); 
                    if(script == 'devanagari' && 
                       kid.parentNode.getAttribute('data-devanagari-glyph') &&
                       !/\s/g.test(kid.data)
                      ) {
                        htmlstr += kid.parentNode.getAttribute('data-devanagari-glyph');
                    } 
                    else htmlstr += func(kid.data,placeholder);
                }
            }
            else if(kid.hasChildNodes()) {
                let kidscript = kid.getAttribute('lang') || cur_script;
                htmlstr += changeScript(kid,script,level+1,placeholder,kidscript);
            }
            else {
                htmlstr += kid.outerHTML;
            }
    }
    return level > 0 ?
        htmlstr+tags[1] :
        htmlstr;
},
*/
    const changeScript = function(orignode,script,placeholder = false,cur_lang='sa') {
        const func = to[script];
        const node = orignode.cloneNode(true);
        //var cur_lang;
    
        const scriptLoop = function(node,cur_lang) { 
            const kids = node.childNodes;

            for(const kid of kids) {
            
                if(kid.nodeType > 3) continue;

                if(kid.nodeType === 3) {
                    if(cur_lang !== 'sa')
                        continue;
                    else {
                        if(script === 'devanagari' && 
                       node.getAttribute('data-devanagari-glyph') &&
                       !/\s/g.test(kid.data)
                        ) {
                            kid.data = node.getAttribute('data-devanagari-glyph');
                        } 
                        else { 
                            const gscript = node.getAttribute('data-script');
                            if(gscript && gscript === script) {
                                kid.data = node.getAttribute('data-glyph');
                            }
                            else
                                kid.data = func(kid.data,placeholder);
                        }
                    }
                }
                else if(kid.hasChildNodes()) {
                    let kidlang = kid.getAttribute('lang') || cur_lang;
                    if(kidlang === 'sa' && kid.classList.contains('subst'))
                        jiggle(kid,script);
                    scriptLoop(kid,kidlang);
                }
            }
        }; //end function loop

        scriptLoop(node,cur_lang);
        //    var frag = document.createDocumentFragment();
        //    while(node.childNodes.length > 0)
        //        frag.appendChild(node.childNodes[0]);
        //    return frag;
        // using a documentFragment is really slow??
        return node.innerHTML;
    };
    /*
jiggle: function(node,script) {
    let kids = node.childNodes;
    if(kids[0].nodeType != 3 && kids[kids.length-1].nodeType != 3) return;

    let initial_vowels_allowed = (kids[0].nodeType != 3) ? true : false;
    let add_at_beginning = [];
    let vowels = ['ā','i','ī','u','ū','e','o','ṃ','ḥ','ai','au'];
    let vowels_regex = /[aāiīuūeoṃḥ_]$/;
    let cons_regex = /[kgṅcjñṭḍṇtdnpbmyrlvṣśsh]$/;

    let telugu_vowels = ['ā','i','ī','e','o','_','ai','au'];
    let telu_cons_headstroke = ['h','k','ś','y','g','gh','c','ch','jh','ṭh','ḍ','ḍh','t','th','d','dh','n','p','ph','bh','m','r','l','v','ṣ','s'];
    let telugu_del_headstroke = false;
    let telugu_kids = [];
//    let prev_kid;

    
    for (let kid of kids) {
        let txt = kid.textContent;
        if(kid.nodeType == 3) {
            if(txt.trim() == '') continue;
            else if(txt == 'a')
                kid.textContent = '';
            else if(vowels.indexOf(txt) >= 0) {
                let cap = document.createElement('span');
                cap.setAttribute('class','aalt');
                cap.appendChild(kid.cloneNode(false));
                node.replaceChild(cap,kid);
                kid = cap;
            }            
            else if(!txt.trim().match(vowels_regex)) {
                if(script == 'telugu' &&
                   telu_cons_headstroke.indexOf(txt.trim()) >= 0)
                    // if there's a vowel mark above, remove the headstroke from the consonant
                    telugu_kids.push(kid);
                else
                    kid.textContent = txt.replace(/\s+$/,'') + 'a';
            }
         //   prev_kid = kid;
        }

        if(kid.nodeType != 1) continue;

        if(txt == 'a') { 
            kid.textContent = '';
            continue;
        }
        
        //if(!txt.trim().match(vowels_regex)) {
        if(txt.trim().match(cons_regex)) {
            let last_txt = findTextNode(kid,true);
            last_txt.textContent = last_txt.textContent.replace(/\s+$/,'') + 'a';
        }

        if(!initial_vowels_allowed) {

            kid.classList.add('aalt');

            switch (script) {
                case 'devanagari':
                    if(txt == 'i' || txt == 'é') 
                        add_at_beginning.unshift(kid);
//                    else if(txt[0] == '_') {
                        // malayalam and telugu half forms are indicated with an initial underscore
                        // in devanagari, the previous consonant needs to be turned into a half form instead
//                        prev_kid.textContent = prev_kid.textContent.replace(/a$/g,'__');
//                    }
                    break;
                case 'malayalam':
                    if(txt == 'e') add_at_beginning.unshift(kid);
                    else if(txt == 'ai') add_at_beginning.unshift(kid);
                    else if(txt == 'o') {
                        let new_e = kid.cloneNode(true);
                        replaceTextInNode('o','e',new_e);
                        add_at_beginning.unshift(new_e);
                        replaceTextInNode('o','ā',kid);
                    }
                    break;
                case 'telugu':
                    if(!telugu_del_headstroke &&
                       telugu_vowels.indexOf(txt) >= 0)
                        
                        telugu_del_headstroke = true;
                    break;

                }
        }
        //prev_kid = findTextNode(kid);
    } // end for let kid of kids

    for (let el of add_at_beginning) {
        node.insertBefore(el,node.childNodes[0]);
    }

    for (let el of telugu_kids) {
        el.textContent = el.textContent + 'a\u200D\u0C4D';
    }
}, // end jiggle
*/

    const jiggle = function(node,script) {
        if(node.firstChild.nodeType !== 3 && node.lastChild.nodeType !== 3) 
            return;

        const kids = node.childNodes;
        //    const vowels = ['ā','i','ī','u','ū','e','o','ṃ','ḥ','ai','au'];
        //    const vowels_regex = /[aāiīuūeoṛṝḷṃḥ_]$/;
        const starts_with_vowel = /^[aāiīuūeoêôṛṝḷṃḥ]/;
        const ends_with_consonant = /[kgṅcjñṭḍṇtdnpbmyrlvṣśsh]$/;

        const telugu_vowels = ['ā','i','ī','e','o','_','ai','au'];
        const telu_cons_headstroke = ['h','k','ś','y','g','gh','c','ch','jh','ṭh','ḍ','ḍh','t','th','d','dh','n','p','ph','bh','m','r','ḻ','v','ṣ','s'];
        var telugu_del_headstroke = false;
        var telugu_kids = [];
        //const initial_vowels_allowed = (kids[0].nodeType !== 3) ? true : false;
        var add_at_beginning = [];
        const starts_with_text = (kids[0].nodeType === 3);
        //    const ends_with_text = (kids[kids.length-1].nodeType === 3);

        for (let kid of kids) {
            if(kid.nodeType > 3) continue;

            const txt = kid.textContent.trim();
            if(txt === '') continue;
            if(txt === 'a') { 
                kid.textContent = '';
                continue;
            }
            if(txt === 'aḥ') {
                kid.textContent = 'ḥ';
                continue;
            }

            if(txt.match(ends_with_consonant)) {
                // add 'a' if node ends in a consonant
                const last_txt = findTextNode(kid,true);
                last_txt.textContent = last_txt.textContent.replace(/\s+$/,'') + 'a';
                if(script === 'telugu' &&
               telu_cons_headstroke.indexOf(txt) >= 0) {
                //console.log(kid);
                // if there's a vowel mark in the substitution, 
                // remove the headstroke from any consonants
                    telugu_kids.push(kid);
                }
            }
        
            // case 1, use aalt:
            // ta<subst>d <del>ip</del><add>it</add>i</subst>
            // case 2, use aalt:
            // <subst>d <del>apy </del><add>ity </add>i</subst>va
            // case 3, no aalt:
            // <subst><del>apy </del><add>ity </add>i</subst>va
        
            // use aalt if node is a text node or 
            // if it starts with a vowel
            if(kid === node.lastChild && kid.nodeType === 3) {
                const cap = document.createElement('span');
                cap.appendChild(kid.cloneNode(false));
                node.replaceChild(cap,kid);
                kid = cap; // redefines 'kid'
                kid.classList.add('aalt');
            }

            else if(starts_with_text && txt.match(starts_with_vowel))
                kid.classList.add('aalt');
        
            switch (script) {
            case 'newa':
            case 'sarada':
            case 'devanagari':
                if(txt === 'i') 
                    add_at_beginning.unshift(kid);
                else if(txt === 'ê') {
                    kid.classList.remove('aalt');
                    kid.classList.add('cv01');
                    add_at_beginning.unshift(kid);
                }
                else if(txt === 'ô') {
                    const new_e = kid.cloneNode(true);
                    replaceTextInNode('ô','ê',new_e);
                    new_e.classList.remove('aalt');
                    new_e.classList.add('cv01');
                    add_at_beginning.unshift(new_e);
                    replaceTextInNode('ô','ā',kid);
                }
                else if(txt === 'aî') {
                    const new_e = kid.cloneNode(true);
                    replaceTextInNode('aî','ê',new_e);
                    new_e.classList.remove('aalt');
                    new_e.classList.add('cv01');
                    add_at_beginning.unshift(new_e);
                    replaceTextInNode('aî','e',kid);
                }
                else if(txt === 'aû') {
                    const new_e = kid.cloneNode(true);
                    replaceTextInNode('aû','ê',new_e);
                    new_e.classList.remove('aalt');
                    new_e.classList.add('cv01');
                    add_at_beginning.unshift(new_e);
                    replaceTextInNode('aû','o',kid);
                }
                break;
            case 'grantha':
            case 'malayalam':
                if(txt === 'e' || txt === 'ê' || 
                   txt === 'ai' || txt === 'aî') 
                    add_at_beginning.unshift(kid);
                else if(txt === 'o' || txt === 'ô') {
                    const new_e = kid.cloneNode(true);
                    replaceTextInNode(/[oô]/,'e',new_e);
                    add_at_beginning.unshift(new_e);
                    replaceTextInNode(/[oô]/,'ā',kid);
                }
                break;
            case 'telugu':
                if(!telugu_del_headstroke &&
                   telugu_vowels.indexOf(txt) >= 0)
                    
                    telugu_del_headstroke = true;
                break;

            }
        } // end for let kid of kids

        for (const el of add_at_beginning) {
            node.insertBefore(el,node.firstChild);
        }

        if(telugu_del_headstroke) {
            for (const el of telugu_kids) {
                const lasttxtnode = findTextNode(el,true);
                lasttxtnode.textContent = lasttxtnode.textContent + '\u200D\u0C4D';
            }
        }
    };

    const findTextNode  = function(node,last = false) {
        if(node.nodeType === 3) return node;
        const walker = document.createTreeWalker(node,NodeFilter.SHOW_TEXT,null,false);
        if(!last) return walker.nextNode;
        else {
            let txt;
            while(walker.nextNode())
                txt = walker.currentNode;
            return txt;
        }
    };

    const replaceTextInNode = function(text, replace, node) {
        const walker = document.createTreeWalker(node,NodeFilter.SHOW_TEXT,null,false);
        while(walker.nextNode()) {
            const cur_txt = walker.currentNode.textContent;
            if(cur_txt.match(text))
                walker.currentNode.textContent = replace;
        }
    };
    /*
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
*/
    /**** highlight functions start here ****/

    const highlight = function(id,node,countonly = false) {
    //var pos = id.split("x").map(function(e) {return parseInt(e,10)});
        const pos = splitLocs(id);
        /*for(let q=0;q<pos.length;q++)
        pos[q] = parseInt(pos[q]);
    */
        //var spaces = 0;
        var startnode, endnode;
        var startpos = -1;
        var endpos = -1;
        //var kid;
        var spaceAtEndOfContainer = -1;
        var prevnode;

        function countSpaces(node) {
            const re = /\s+/g;
            const stopnode = node.nextSibling || node.parentNode ? 
                node.parentNode.nextSibling : null;
            var skipKids = false;
            var preIgnored = false;
            var spaces = 0;
            for(let kid = node.firstChild;kid !== stopnode;kid = getNextNode(kid,skipKids)) {
                if(!kid) break;

                skipKids = false;
                if(kid.nodeType > 3) continue;
                if(kid.nodeType === 1) {
                    if(kid.classList.contains('ignored')) {
                        if(!preIgnored) preIgnored = kid;
                        skipKids = true;
                    }
                    continue;
                }
                else if(kid.data.length !== 0 && kid.data.trim() === '') {
                    // encountered a space node
                    if(spaceAtEndOfContainer === -1) {
                        spaces++;
                        spaceAtEndOfContainer = 0;
                    }
                    preIgnored = false;
                    continue;
                }
                else {
                    let kidtext = kid.data;
                    let space = re.exec(kidtext);
                    // if the variant starts at the beginning of the section
                    if(startpos === -1 && pos[0] === 0) {
                        if(!space || space.index > 0) {
                            if(preIgnored) {
                                startnode = preIgnored;
                                startpos = -2;
                                if(pos[1] === pos[0]) {
                                    endnode = preIgnored;
                                    endpos = -2;
                                    return true;
                                }
                            }
                            else {
                                startpos = 0;
                                startnode = kid;
                                if(pos[1] === pos[0]) {
                                    endpos = 0;
                                    endnode = kid;
                                    return true;
                                }
                            }
                        }
                        else { // space at the start of this text node
                            if(pos[1] === pos[0]) {
                                startpos = 0;
                                startnode = kid;
                                endpos = re.lastIndex;
                                endnode = kid;
                                return true;
                            } else {
                                startpos = re.lastIndex;
                                startnode = kid;
                            }
                        }
                   
                    }
                    // variant doesn't start at the beginning of the section
                    if(!space || space.index > 0) {
                        if(spaceAtEndOfContainer > -1) {
                            if(startpos === -1 && spaces === pos[0]) {
                                if(preIgnored) {
                                    startnode = preIgnored;
                                    startpos = -2;

                                    if(pos[0] === pos[1]) {
                                        if(spaceAtEndOfContainer > 0) {
                                            startnode = prevnode;
                                            startpos = spaceAtEndOfContainer;
                                        }
                                        endnode = preIgnored;
                                        endpos = -2;
                                        return true;
                                    }
                                }
                                else {
                                    startpos = 0;
                                    startnode = kid;
                                    if(pos[0] === pos[1]) {
                                        if(spaceAtEndOfContainer > 0) {
                                            startpos = spaceAtEndOfContainer;
                                            startnode = prevnode;
                                        }
                                        endpos = 0;
                                        endnode = kid;
                                        return true;
                                    }
                                }
                            }
                    
                            spaceAtEndOfContainer = -1;
                        }
                        // variant ends at the beginning of this text node
                        if(pos[1] === spaces) {
                            if(pos[0] === pos[1]) {
                                endnode = kid;
                                endpos = 0;
                            } else {
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
                        else if(spaceAtEndOfContainer === -1)
                            spaces++;

                        if(re.lastIndex === kidtext.length) {
                            spaceAtEndOfContainer = space.index;
                        }
                        else
                            spaceAtEndOfContainer = -1;

                        if(startpos === -1 &&
                       spaces === pos[0] &&
                       spaceAtEndOfContainer === -1) {

                            if(pos[0] === pos[1]) {
                                startpos = space.index;
                                startnode = kid;
                                endpos = re.lastIndex;
                                endnode = kid;
                                return true;
                            } else {
                                startpos = re.lastIndex;
                                startnode = kid;
                            }
                        }
                        if(startpos !== -1 &&
                       endpos === -1 &&
                       spaces === pos[1]) {

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
                if(prevnode.parentNode.lastChild.nodeType !== 3) {
            
                    // this means that there are some extra bits at the end of the section
                    endnode = prevnode.parentNode.lastChild;
                    endpos = -2;
                }
                else { // otherwise just set it to the end of the section
                    endnode = prevnode;
                    endpos = prevnode.length;
                    if(!startnode) { // an addition at the end of the section
                        startnode = prevnode;
                        startpos = spaceAtEndOfContainer > 0 ?
                            spaceAtEndOfContainer : 
                            prevnode.length;
                    }
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
        if(startpos === -2) 
            middleRange.setStartBefore(startnode);
        else
            middleRange.setStart(startnode,startpos);
        if(endpos === -2) 
            middleRange.setEndAfter(endnode);
        else
            middleRange.setEnd(endnode,endpos);
        if(pos.length > 2) {
            let pos3 = pos.length === 4 ?
                pos[3] :
                false;
            middleRange = countChars(middleRange,pos[2],pos3);
        } 
        /*
    if(pos.length > 2) {
        if(pos[2] > 0) middleRange = countChars(middleRange,pos[2],"prefix");
        else if(pos.length == 4) middleRange = countChars(middleRange,pos[3],"suffix");
    }
*/
        if(!countonly)
            lightTextNodes(middleRange);
        else
            return middleRange;
    };

    const countChars = function(range,pos0,pos1) {
        var start, startpos, end, endpos;
        if(range.startContainer.nodeType === 3) {
            start = range.startContainer;
            startpos = range.startOffset;
        }
        else {
            start = range.startContainer.childNodes[range.startOffset];
            startpos = 0;
        }
   
        if(range.endContainer.nodeType === 3) {
            end = range.endContainer;
            endpos = range.endOffset;
        }
        else {
            end = range.endContainer.childNodes[range.endOffset-1];
            endpos = 0;
        }
        if(start === end) {
        
            let strArr;
            strArr = Array.from(start.data.substring(startpos,endpos));
       
            if(pos1) {
                let a = countCharsLoop(strArr,pos1); 
                range.setEnd(start,startpos+a);
            }
            if(pos0 !== 0) {
                let a = countCharsLoop(strArr,pos0); 
                range.setStart(start,startpos+a);
            }
        }
        else { // start and end positions are in different nodes
            if(pos1) {
                let a = countCharsInNodes(start,end,pos1,startpos);
                range.setEnd(a.node,a.pos);
            }
            if(pos0 !== 0) {
                let a = countCharsInNodes(start,end,pos0,startpos);
                range.setStart(a.node,a.pos);
            }
        }

        return range;
    };

    const countCharsLoop = function(strArr,pos) {
        let b = 0;
        let a = 0;
        for(a;a<strArr.length;a++) {
            if(strArr[a] === consts.hyphen)
                continue;
            if(b === pos)
                break;
            b++;
        }
        return a;
    };

    const countCharsInNodes = function(start,end,pos,startpos) {
        var skipKids = false;
        for(let node = start;node !== getNextNode(end);node = getNextNode(node,skipKids)) {
            skipKids = false;
            if(node.nodeType != 3) {
                if(node.classList.contains('ignored'))
                    skipKids = true;
                continue;
            }
            if(node.data.length !== 0 && node.data.trim() === '') {
            // encountered a space node
                if(startpos)
                    startpos--;
                else
                    pos -= node.data.length;
                continue;
            }

            let substr = node.data.substring(startpos);
            let strArr = Array.from(substr);
        
            let hyphens = substr.match(consts.hyphenRegex);
            hyphens = hyphens ? hyphens.length : 0;
            if((strArr.length-hyphens) <= pos) {
                pos = pos - strArr.length + hyphens;
                startpos = 0;
                continue;
            }
        
            else {
                let a = countCharsLoop(strArr,pos);
                return {node: node,pos: startpos+a};
            }
        }
        return false;
    };

    const getNextNode = function(node,skipKids = false) {
        if(node.firstChild && !skipKids)
            return node.firstChild;
        while(node) {
            if(node.nextSibling) return node.nextSibling;
            node = node.parentNode;
        }
        return null;
    };

    /***** highlightNode(node): highlights a range by surrounding it with a span; this works as long as there are no divs in the range *****/

    const highlightNode = function(range,classname='highlight') {
        const highlightNode = document.createElement('span');
        highlightNode.className = classname;
        highlightNode.appendChild(range.extractContents());
        range.insertNode(highlightNode);
    //range.surroundContents(highlightNode);
        //    state.highlit.push(highlightNode);
    };

    /***** findDivs(range): checks if there are div elements in a range *****/

    const findDivs = function(range) {
        var container = range.cloneContents();
        var walk = document.createTreeWalker(container,NodeFilter.SHOW_ELEMENT,null,false);
        while(walk.nextNode()) {
            if(walk.currentNode.nodeName === 'DIV')
                return 1;
        }
        /*
    node = container.firstChild;
    while(node) {
        if(node.nodeName == 'DIV') {
            return 1;
        }
        node = getNextNode(node);
    }
*/
        return 0;
    };

    const lightTextNodes = function(range,classname='highlight') {

        //        if((start.parentNode == end.parentNode) && !findDivs(range)) {
        if(range.collapsed || range.toString().trim() === '')
            range.insertNode(
                document.createTextNode('\u00A0\u00A0\u00A0')
            );
        if(!findDivs(range)) {
            /* can't surround divs with a span (well, it's ugly, and also the highlightNode function would automatically close open divs, which would generate an extra div) */
            highlightNode(range,classname);
        }
    
        else { // surround only text nodes with the highlight span
        
            /*let start, startpos, end, endpos;

            if(range.startContainer.nodeType === 3) {
                start = range.startContainer;
                //startpos = range.startOffset;
            }
            else {
                start = range.startContainer.childNodes[range.startOffset];
                //startpos = 0;
            }
       
            if(range.endContainer.nodeType === 3) {
                end = range.endContainer;
                //endpos = range.endOffset;
            }
            else {
                end = range.endContainer.childNodes[range.endOffset-1];
                //endpos = 0;
            }
*/
            const toHighlight = [];
            const start = (range.startContainer.nodeType === 3) ?
                range.startContainer :
                range.startContainer.childNodes[range.startOffset];
       
            const end = (range.endContainer.nodeType === 3) ?
                range.endContainer :
                range.endContainer.childNodes[range.endOffset-1];
      
            if(start.nodeType === 3 && range.startOffset !== start.length) {
                const textRange = start.ownerDocument.createRange();
                textRange.setStart(start,range.startOffset);
                textRange.setEnd(start,start.length);
                toHighlight.push(textRange);
            }

            for(let node = getNextNode(start); node !== end; node = getNextNode(node)) {
                if(node.nodeType === 3) {
                    const textRange = node.ownerDocument.createRange();
                    textRange.selectNode(node);
                    toHighlight.push(textRange);
                }
            }
        
            if(end.nodeType === 3 && range.endOffset > 0) {
                const textRange = end.ownerDocument.createRange();
                textRange.setStart(end,0);
                textRange.setEnd(end,range.endOffset);
                toHighlight.push(textRange);
            }
            for(const hiNode of toHighlight) {
                // do highlighting at end so as not to add nodes during the tree traversal
                highlightNode(hiNode,classname);
            }
        }

    };
    /**** end of highlight functions ****/


    /**** reverse-highlighting functions start here ****/



    const find = {
        variants: function(sel) {

            const seltext = cleanString(sel.range);
            const posinfo = find.startEnd(sel,seltext);
            const startcount = posinfo.pre.spaces + posinfo.sel.startspace;
            const endcount = posinfo.pre.spaces + posinfo.sel.spaces + 1 - posinfo.sel.endspace;
        
            const selnode = state.script !== 'iast' ? sel.target.myIAST : sel.target;
            const full_lemma = getLemma(selnode,startcount,endcount);
            const right_boundary = posinfo.sel.endspace || getRightBoundary(sel);
            const lemmaArr = (() => {
                if(state.script !== 'iast') {
                    const lemma = posinfo.pre.stublen ?
                        full_lemma.slice(posinfo.pre.stublen) :
                        full_lemma;
                    const arr = lemma.replace(consts.hyphenRegex,'').trim().split(/\s+/);

                    //if(posinfo.sel.spaces - posinfo.sel.endspace - posinfo.sel.startspace > 0)
                    if(!right_boundary)
                        arr.push(arr.pop().slice(0,posinfo.sel.stublen));
                    
                    return arr;
                }
                else
                    return seltext.replace(consts.hyphenRegex,'').trim().split(/\s+/);
            })();
            const lemmaText = lemmaArr.join(' ');
            /*
            const app = state.mainClass === '.sectiontext' ? 
                sel.target.parentNode.parentNode.querySelector('.variorum') :
                sel.target.parentNode.querySelector('.variorum') ;
            */
            const app = sel.target.closest('.upama-block').querySelector('.variorum');
            const variants = app.getElementsByClassName('varcontainer');
            const matched_variants = [];
            for(const v of variants) {
                const loc = splitLocs(v.getAttribute('data-loc'));
            
                if(loc[0] === loc[1]) { // addition
                //if(loc[0] >= startcount && loc[0] <= endcount)
                    if((right_boundary && loc[0] === endcount) ||
                   (loc[0] > startcount && loc[0] < endcount)
                    ) // only report additions following a lemma, not preceeding it
                        matched_variants.push(v);
                    continue;
                }
            
                // stop checking after reaching variants that begin after the selected text (unless it's an addition immediately following)
                if(loc[0] >= endcount) break;

                //        if( (loc[0] >= startcount && loc[0] < endcount) ||
                // variant starts after selection start, or variant starts before selection start and ends before selection end
                if( (loc[0] >= startcount) || (loc[1] > startcount) ) {

                    if(posinfo.pre.stublen > 0 && loc[3]) {
                        if(loc[0] === (startcount - posinfo.sel.startspace)) {
                            if(loc[3] <= posinfo.pre.stublen)
                                continue;
                        }
                        if(loc[0] < startcount) {
                        /*
                        let node = sel.target;
                        if(state.script != 'iast')
                            node = node.myIAST.cloneNode(true);
                        */
                            const node = state.script === 'iast' ? sel.target : sel.target.myIAST.cloneNode(true);

                            const pos1 =  loc[0]+'x'+loc[1]+'x0x'+loc[3];
                            const txt1 = highlight(pos1,node,true).toString();
                            const len1 = txt1.replace(consts.hyphenRegex,'').replace(/\s+/g,' ').length;
                        
                            const pos2 = loc[0]+'x'+startcount;
                            const txt2 = highlight(pos2,node,true).toString();
                            const len2 = txt2.replace(consts.hyphenRegex,'').replace(/\s+/g,' ').length + 1 + posinfo.pre.stublen;

                            if(len1 <= len2) {
                                continue;
                            }
                        }
                    }
                    if(loc[2] && loc[2] > 0) {
                        if(loc[0] === (startcount - posinfo.sel.startspace) && !posinfo.sel.spaces) {
                            const lemmalen = posinfo.sel.endspace ? 
                                posinfo.pre.stublen + lemmaText.length : 
                                posinfo.pre.stublen + posinfo.sel.stublen;
                            if(loc[2] >= (lemmalen + posinfo.sel.endspace))
                                continue;
                        }
                        else if(loc[0] === (posinfo.pre.spaces + posinfo.sel.spaces)) {
                        //const lemmalen = lemmaText.split(/\s+/).pop().length;
                            const lemmalen = lemmaArr[lemmaArr.length-1].length;
                            if(loc[2] >= lemmalen)
                                continue;
                        } 

                    }
                    matched_variants.push(v);
                }
            }
        
            const msids = new Set();

            if(matched_variants.length > 0) {
                const posdata = posinfo;
                //posinfo.startcount = startcount;
                //posinfo.endcount = endcount;
                posdata.startcount = startcount;
                posdata.endcount = endcount;
                const seldata = {range: sel, lemma: {partial: lemmaText, full: full_lemma}, right_boundary: right_boundary };
            
                const overlapping = checkOverlap(matched_variants,seldata,posdata);
                for(const v of overlapping) {
                    const vv = v.querySelector('.variant');
                    vv.classList.add('lowlight');
                    state.lowlit.add(vv);
                    const mm = v.getElementsByTagName('a');
                    for(const msid of mm) {
                        msids.add(msid.dataset.msid);           
                    }
                }
            }

            const myData = {
                app: app,
                msids: msids,
                lemmaArr: lemmaArr,
                lemmaText: lemmaText,
                posinfo: posinfo,
                rightBoundary: right_boundary,
                counts: [startcount,endcount],
                sel: sel,
            };

            return myData;

        },

        selection: function() {
            const sel = window.getSelection();
            if(sel.isCollapsed) return false;

            const selrange = sel.getRangeAt(0);

            const target = selrange.startContainer.parentNode.closest(state.mainClass);
            const endtarget = selrange.endContainer.parentNode.closest(state.mainClass);
            if(target === null && endtarget === null) return false;
            if(target.classList.contains('ui-accordion')) return false;

            if(target.lastChild === selrange.startContainer && selrange.startOffset === selrange.startContainer.length) {
                const nextindex = [].indexOf.call(state.mains,target) + 1;
                const newtarget = state.mains[nextindex];
                const newrange = (sel.rangeCount > 1) ? // firefox returns multiple ranges, chrome doesn't
                    sel.getRangeAt(1).cloneRange() :
                    selrange;
                selrange.setStart(target.firstChild,0);
                return {range: newrange, target: newtarget};
            }
            else
                return {range: selrange, target: target};
        },

        startEnd: function(sel,seltext) {

            var textFromIAST;
            var start_with_spaces;
            var preinfo = {};
            var selinfo;
            var start = document.createRange();

            start.setStart(sel.target.firstChild,0);
            start.setEnd(sel.range.startContainer,sel.range.startOffset);
            if(!start) return;

            if(state.script !== 'iast') {
                textFromIAST = changeScript(sel.target.myIAST,state.script,consts.placeholder);
                textFromIAST = document.createRange().createContextualFragment(textFromIAST);
                textFromIAST = cleanString(textFromIAST);
            }
            if(start.collapsed === true)
                preinfo = {spaces: 0, startspace: 0, endspace: 0, stublen: 0};
            else {
                let start_txt = cleanString(start);
                // if there is a block of more than one space, and the selection starts in the middle of it
            
                let nostub = false;
                if(/^\s/.test(seltext)) {
                    start_txt = start_txt.replace(/\s+$/,'');
                    nostub = true;
                }

                if(state.script !== 'iast') {
                    start_with_spaces = replaceSpaces(textFromIAST,start_txt,consts.placeholder);
                    preinfo = spaceCount(
                        to.iast(start_with_spaces,state.script),
                        consts.placeholder,nostub
                    );
                } else {
                    preinfo = spaceCount(start_txt,'',nostub);
                }
            
            }
            if(state.script !== 'iast') {
                let seltext_with_spaces = replaceSpaces(textFromIAST.substr(start_with_spaces.length),seltext,consts.placeholder);
                selinfo = spaceCount(
                    to.iast(seltext_with_spaces,state.script),
                    consts.placeholder
                );
            }
            else {
                selinfo = spaceCount(seltext);
            }
            return {pre: preinfo, sel: selinfo};
        },
    };

    const makePosApp = function(app,msids) {
        if(msids.size === 0)
            return {text: 'no variants', appKeys: []};
        
        const exclude = app.getAttribute('data-exclude');
        //var excludeText = '';
        if(exclude) {
            const excludes = exclude.split(' ');
            for(const x of excludes) {
                if(state.groupWit.has(x)) {
                    for(const xx of state.groupWit.get(x))
                        msids.add(xx);
                }
                else
                    msids.add(x);
            }
        //    excludeText = ' <del>' + 
        //        state.listWit.filter(x => excludes.includes(x[0])).map(y => y[1]).join(', ') +
        //        '</del>';
        }
        const bracket = app.querySelector('.excludebracket');
        const excludeText = bracket ? 
            ' <del>' + [...bracket.children].map(el => el.innerHTML).join(', ') + '</del>' : 
            '';
        const posApp = state.listWit.filter((n) => !msids.has(n[0]));
        const posAppKeys = posApp.map(n => n[0]);
        const posAppFiltered = groupsDiff(state.groupWit,posAppKeys);
        var posText = posAppFiltered.map(n => state.allWit.get(n)).join(', ');
        posText += excludeText;
        posText += '<span id="__upama_matrix_icon_span"><svg id="__upama_matrix_icon" viewBox="0 0 256 256"><path d="m22.16 22.16v42.67h211.68v-42.67h-211.68zm0 82.67v46.34h211.68v-46.34h-211.68zm0 86.34v42.67h211.68v-42.67h-211.68z" fill-rule="evenodd"/></svg></span>';
        if(document.getElementById('__upama_stemma'))
            posText += ' <span id="__upama_stemma_icon_span"><svg id="__upama_stemma_icon" viewBox="0 0 20 20"><path d="M14.68,12.621c-0.9,0-1.702,0.43-2.216,1.09l-4.549-2.637c0.284-0.691,0.284-1.457,0-2.146l4.549-2.638c0.514,0.661,1.315,1.09,2.216,1.09c1.549,0,2.809-1.26,2.809-2.808c0-1.548-1.26-2.809-2.809-2.809c-1.548,0-2.808,1.26-2.808,2.809c0,0.38,0.076,0.741,0.214,1.073l-4.55,2.638c-0.515-0.661-1.316-1.09-2.217-1.09c-1.548,0-2.808,1.26-2.808,2.809s1.26,2.808,2.808,2.808c0.9,0,1.702-0.43,2.217-1.09l4.55,2.637c-0.138,0.332-0.214,0.693-0.214,1.074c0,1.549,1.26,2.809,2.808,2.809c1.549,0,2.809-1.26,2.809-2.809S16.229,12.621,14.68,12.621M14.68,2.512c1.136,0,2.06,0.923,2.06,2.06S15.815,6.63,14.68,6.63s-2.059-0.923-2.059-2.059S13.544,2.512,14.68,2.512M5.319,12.061c-1.136,0-2.06-0.924-2.06-2.06s0.923-2.059,2.06-2.059c1.135,0,2.06,0.923,2.06,2.059S6.454,12.061,5.319,12.061M14.68,17.488c-1.136,0-2.059-0.922-2.059-2.059s0.923-2.061,2.059-2.061s2.06,0.924,2.06,2.061S15.815,17.488,14.68,17.488"></path></svg></span>';
        return {text: posText, appKeys: posAppKeys};
    };
    
    const groupsDiff = function(groups, mss) {
        const go = function(key, ss, arr) {
            // need to clone the array because of delete
            const filtered = [...arr];
            const is = [];
            for(const s of ss) {
                const found = filtered.indexOf(s);
                if(found === -1) return false;
                else {
                    is.push(found);
                    delete filtered[found];
                }
            }
            filtered[Math.min(...is)] = key;
            return filtered;
        };
        var ret = mss;
        for(const [key, group] of groups) {
            const found = go(key,group,ret);
            if(found) ret = found;
        }
        return ret.filter(() => true);
    };

    const showPosApp = function(data) {
        const selPos = data.sel.range.getBoundingClientRect();
    
        const appBox = document.getElementById('__upama_positive_apparatus') ||
        (() => {
            const appBox = document.createElement('div');
            appBox.id = '__upama_positive_apparatus';
            appBox.style.opacity = 0;
            appBox.style.transition = 'opacity 0.2s ease-in';
            state.contentbox.parentNode.insertBefore(appBox,state.contentbox);
            return appBox;
        })();

        appBox.innerHTML = data.posText;
        appBox.style.top = (selPos.top - appBox.clientHeight - 5) + 'px';
        appBox.style.left = (selPos.left) + 'px';
        appBox.style.opacity = 1;
    
        appBox.myData = data;

        const matrixIcon = document.getElementById('__upama_matrix_icon_span');
        if(matrixIcon)
            matrixIcon.addEventListener('click',listener.showMatrix);
        const stemmaIcon = document.getElementById('__upama_stemma_icon_span');
        if(stemmaIcon)
            stemmaIcon.addEventListener('click',listener.showStemma);
        state.contentbox.addEventListener('mousedown',listener.unLowLight);
        document.addEventListener('keydown',listener.unLowLight);
    };

    const getRightBoundary = function(sel) {
        const post = document.createRange();
        const postlen = sel.range.endContainer.data.length;
        const ends_in_ignored = sel.range.endContainer.parentNode.closest('.ignored');
        if(sel.range.endOffset < postlen && !ends_in_ignored) {
            post.setStart(sel.range.endContainer,sel.range.endOffset);
            post.setEnd(sel.range.endContainer,sel.range.endOffset+1);
        }
        else {
            const start_node = getNextNode(sel.range.endContainer);
            const boundary_node = sel.range.endContainer.parentNode.closest(state.mainClass).nextSibling;
            var next_node;
            var skipKids;
            for(let node = start_node;node !== boundary_node;node = getNextNode(node,skipKids)) {
                skipKids = false;
                if(node.nodeType !== 3) {
                    if(node.classList.contains('ignored'))
                        skipKids = true;
                    continue;
                }
                else if(node.parentNode.closest('.ignored'))
                    continue;
                else {
                    next_node = node;
                    break;
                }
            }
            if(next_node) {
                post.setStart(next_node,0);
                post.setEnd(next_node,1);
            }
            else
            // reached the end of the section of text; that's a boundary
                return true;
        
        }
        return /\s/.test(post.toString()) ? true : false;
    };

    const getLemmata = function(seldata,posdata) {
    //const full_arr = seldata.lemma.partial.split(/\s+/);
        const full_arr = seldata.lemma.full.split(' ');
        const space_before = posdata.pre.endspace || posdata.sel.startspace;
        const space_after = seldata.right_boundary;
        const lemmata = {};
        if(posdata.endcount - posdata.startcount > 1) { // multi-word lemma
            const lemmasplit = seldata.lemma.partial.split(' ');
            lemmata[posdata.startcount] = {text: lemmasplit[0],
                full: full_arr[0], 
                //arr: full_arr,
                arr: lemmasplit,
                start: posdata.pre.stublen, 
                end: (posdata.pre.stublen + lemmasplit[0].length), 
                rightbound: true};
            if(space_before) lemmata[posdata.startcount].leftbound = true; // starts with a full word

            const partial_lemma = lemmasplit[lemmasplit.length-1];
            lemmata[(posdata.endcount-1)] = {text: partial_lemma, 
                full: full_arr[full_arr.length-1], 
                start: 0, 
                end: partial_lemma.length,
                leftbound: true};
            if(space_after) lemmata[(posdata.endcount-1)].rightbound = true; // ends with a full word
            /*        let n=1;
        while(n < lemmasplit.length-1) {
            lemmata[n] = {text: lemmasplit[n],
                          full: lemmasplit[n],
                          start: 0,
                          end: lemmasplit[n].length,
                          leftbound: true,
                          rightbound: true,
                         };
        } */
        }
        else {
            lemmata[posdata.startcount] = {text: seldata.lemma.partial.trim(), 
                full:  seldata.lemma.full, 
                start: posdata.pre.stublen, 
                end: (posdata.pre.stublen + posdata.sel.stublen)};
            if(space_after) lemmata[posdata.startcount].rightbound = true;
            if(space_before) lemmata[posdata.startcount].leftbound = true;
        }
        return lemmata;
    };

    const getLemma = function(node,start,end) {
        return cleanString(
            highlight(`${start}x${end}`,node,true)
        ).replace(consts.hyphenRegex,'');//.replace(/\s+/g,' ');
    };

    const compareOverlap = function(text1,start1,end1,text2,start2,end2) {
        const overlap1 = end1 ? text1.slice(start1,end1) : text1.slice(start1);
        const overlap2 = end2 ? text2.slice(start2,end2) : text2.slice(start2);
        return (overlap1 === overlap2);
    };

    const checkOverlap = function(variants,seldata,posdata) {
        const selnode = state.script != 'iast' ? seldata.range.target.myIAST : seldata.range.target;
        //const node_start_space = selnode.textContent.match(/^\s/) ? true : false;
        const space_before = posdata.pre.endspace || posdata.sel.startspace;
        //let space_after = posdata.sel.endspace || getRightBoundary(seldata.range);
        const space_after = seldata.right_boundary;
        const fuzzy = 2;
        if(space_before && space_after) {
        // check if first variant starts at the selected text or after
            if(splitLocs(variants[0].getAttribute('data-loc'))[0] >= posdata.startcount)
                return variants;
        }
        const lemmata = getLemmata(seldata,posdata);


        const overlapLoop = function(v) {
            const vv = v.querySelector('.variant');
            if(vv.firstChild.textContent === '[om]') // better way to do this?
                return true;
            const loc = splitLocs(v.getAttribute('data-loc'));
        
            if(space_before && space_after) 
            //&& loc[0] >= posdata.startcount) // no partial lemmata, variant is within range
                return true;
        
            //const variant_is_first_word = node_start_space && loc[0] === 0 && loc[1] === 2;
            const one_word_lemma = (loc[1] - loc[0] === 1);// || variant_is_first_word;
            const lempos = (() => {
            //if(lemmata.hasOwnProperty(loc[0]) && !lemmata[loc[0]].leftbound) && one_word_lemma) // single-word lemmata might include punctuation
                if(lemmata.hasOwnProperty(loc[0])) {
                    if(one_word_lemma && lemmata[loc[0]].rightbound && lemmata[loc[0]].leftbound) {
                        return false;
                    }
                    else
                        return loc[0];
                }
                else if( (loc[1] - loc[0]) > 1 && lemmata.hasOwnProperty(loc[1]-1)) {
                    return loc[1]-1;
                }
                else return false;
            })();
            if(lempos === false) return true; // nothing to compare
            const dirty_var = (state.script === 'iast') ?
                cleanString(vv) :
                cleanString(vv.myIAST);
            const var_txt = dirty_var.replace('°','').replace(consts.hyphenRegex,''); // should also remove all punctuation here, then trim?
            if( lemmata[lempos].start === 0 && lempos === loc[0] ) { // only need to compare left to right
            //console.log(`left-to-right: ${vv.textContent}`);
                const extend_right = (!one_word_lemma && (posdata.endcount - posdata.startcount > 1));
                // if both the variant and the selected text span more than one word
                const lemma_full = extend_right ?
                    getLemma(selnode,loc[0],loc[1]) :
                    lemmata[lempos].full;
                const var_full = (loc[2] > 0) ? lemma_full.slice(0,loc[2]) + var_txt :
                    (loc[3] && loc[1] === posdata.endcount) ? var_txt + lemma_full.slice(loc[3]) :
                        var_txt;
                const possible_endings = [seldata.lemma.partial.length];//,var_full.length];
                if(loc[3]) possible_endings.push(loc[3]);
                //if(!extend_right) 
                if(posdata.endcount === lempos+1)
                    possible_endings.push(lemmata[lempos].end);
                const lemma_end = Math.min(...possible_endings);

                if(lemma_end > var_full.length)
                    return true;
            
                if(compareOverlap(lemmata[lempos].text,0,lemma_end,var_full,0,lemma_end))
                    return false;
            
                else return true;
            }
            else if(lemmata[lempos].rightbound && 
                ( (lempos === loc[1]-1) )// || (lempos === 0 && variant_is_first_word) ) 
            ) { // only need to compare right to left
            //console.log(`right-to-left: ${vv.textContent}`);
                const extend_left = loc[0] < posdata.startcount;
                // if the variant begins before the selected word
                const lemma_full = extend_left ?
                    getLemma(selnode,loc[0],loc[1]) :
                    lemmata[lempos].full;

                const var_full = (loc[2] > 0) ? lemma_full.slice(0,loc[2]) + var_txt :
                    loc[3] ? var_txt + lemma_full.slice(loc[3]) :
                        var_txt;
                const possible_beginnings = [seldata.lemma.partial.length];//,var_full.length];
                if(posdata.startcount === lempos)
                    possible_beginnings.push(lemmata[lempos].text.length);
                const lemma_start = -Math.min(...possible_beginnings);

                if(lemma_start < -var_full.length)
                    return true;

                const lemma_end = false;
                if(compareOverlap(lemma_full,lemma_start,lemma_end,var_full,lemma_start,lemma_end))
                    return false;

                else return true;
            }
            else { // this gets fuzzy...
            //console.log(`fuzzy: ${vv.textContent}`);
                if(seldata.lemma.partial.trim() === '') return true;
                const to_find = seldata.lemma.partial.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                const lemma_regex = new RegExp(to_find,'g');

                const lemma_full = one_word_lemma ?
                    lemmata[lempos].full :
                    getLemma(selnode,loc[0],loc[1]);
            
                const lemma_start = one_word_lemma ?
                    lemmata[lempos].start : (() => {
                        const lemma_arr = lemma_full.split(' ');
                        const selloc = lempos - loc[0];
                        let n = 0;
                        let startpos = 0;
                        while(lemma_arr[n] && n < selloc) {
                            startpos += lemma_arr[n].length + 1;
                            n++;
                        }
                        return startpos + lemmata[lempos].start;
                    })();
                // extend the variant text left or right
                const var_full = (loc[2] > 0) ? lemma_full.slice(0,loc[2]) + var_txt :
                    loc[3] ? var_txt + lemma_full.slice(loc[3]) :
                        var_txt;
                let regex_result;
                while((regex_result = lemma_regex.exec(var_full)) !== null) {
                    if(Math.abs(regex_result.index - lemma_start) <= fuzzy) {// forward search
                    //console.log(var_txt+" should be excluded (forward search)");
                        return false;
                    }
                    else { // backward search
                        if(Math.abs((var_full.length - regex_result.index) - (lemma_full.length - lemma_start)) <= fuzzy) {
                        //console.log(var_txt+' should be excluded (backward search)');
                            return false;
                        }
                    }
                }
                return true;
            }
            //return true;
        }; // end overlapLoop

        return variants.filter(overlapLoop);

    };

    const splitLocs = function(str) {
        return str.split('x').map(n => parseInt(n,10));
    };

    const showPopup = function(path,arrays) {
        const target = document.getElementById('__upama_positive_apparatus');
        const posApp = target.myData.app;
        const lemmaArr = target.myData.lemmaArr;
        const lemmaText = target.myData.lemmaText;
        const posinfo = target.myData.posinfo;
        const sel = target.myData.sel;
        const counts = target.myData.counts;
        const node = (state.script !== 'iast') ? sel.target.myIAST : sel.target;
        //const startspace = node.textContent.match(/^\s/);
        const one_word_lemma = (counts[1] - counts[0] === 1); //|| (startspace && counts[0] === 0 && counts[1] === 2);
        const rightClip = target.myData.rightBoundary ? 0 :
            one_word_lemma ? 
                posinfo.sel.stublen + posinfo.pre.stublen :
                posinfo.sel.stublen;
        const leftClip = (posinfo.pre.endspace || posinfo.sel.startspace || posinfo.pre.stublen === 0) ? 0 :
            one_word_lemma ?
                getLemma(node,counts[0],counts[1]).length - posinfo.pre.stublen :
                lemmaText.split(/\s+/)[0].length;
        const listWit = [];
        for(const wit of state.listWit)
            listWit[wit[0]] = wit[1];
        const otherWit = [];
        for(const wit of state.otherWit)
            otherWit[wit[0]] = wit[1];
        const varTexts = [];
        const unshift = [];
    
        for(const el of state.lowlit) {
            const varcontainer = el.parentElement;
            const msids = varcontainer.getElementsByTagName('a');
            const locs = splitLocs(varcontainer.getAttribute('data-loc'));
            //const full_lem = getLemma(node,locs[0],locs[1]);

            const append_left = (locs.length === 3 && locs[2] > 0) ? getLemma(node,locs[0],locs[1]).slice(0,locs[2]) : '';
            const append_right = (locs.length === 4 && locs[3] > 0) ? getLemma(node,locs[0],locs[1]).slice(locs[3]) : '';
        
            /*
        if(locs.length > 2) {
            //let full_lem = highlight(locs[0]+'x'+locs[1],node,true).cloneContents();
            //full_lem = cleanString(full_lem).replace(consts.hyphenRegex,'');
            const full_lem = getLemma(node,locs[0],locs[1]);
            if(locs[2] > 0) append_left = full_lem.slice(0,locs[2]);
            else if(locs[3]) append_right = full_lem.slice(locs[3]);
        }
        */
            for(const m of msids) {
                const mname = m.dataset.msid;
                const varTextNode = m.classList.contains('mshover') ?
                    varcontainer.querySelector('span[data-ms=\''+mname+'\']') :
                    el;
                const varText = state.script !== 'iast' ? 
                    varTextNode.myIAST.innerHTML.replace(consts.hyphenRegex,'') : 
                    varTextNode.innerHTML.replace(consts.hyphenRegex,'');

                if(!varTexts[mname]) {
                    varTexts[mname] = lemmaArr.slice(0);
                    // if selection starts at the beginning of a paragraph that starts with space, location numbering is incremented by one
                    if( (counts[0] === 0) && 
                     node.textContent.match(/^\s/) ) {
                        varTexts[mname].unshift('');
                        unshift.push(mname);
                    }
                }
                const start = (locs[0] - counts[0] < 0) ? 0 : locs[0] - counts[0];
                const end = locs[1] - counts[0];
                if(locs[0] === locs[1]) { // addition
                    varTexts[mname][start-1] += ' <span class=\'highlight\'>'+varText+'</span> ';
                }
                else if(varText.match(/\[om\]/))
                    varTexts[mname][start] = append_left + '<span class=\'gap\'>'+varText.trim()+'</span>' + append_right;
                else {
                    let str = append_left + '<span class=\'highlight\'>'+varText.replace('°','')+'</span>' + append_right;
                    if(leftClip || rightClip) {
                    //const lemmacounts = {start: (startspace && counts[0] === 0 ? counts[0]+1 : counts[0]),end:counts[1]};
                        const lemmacounts = {start: counts[0],end:counts[1]};
                        if(leftClip && rightClip && locs[0] <= counts[0] && locs[1] >= counts[1]) {
                            str = fragClip(node,str,locs,lemmacounts,{left:leftClip,right:rightClip});
                        }
                        else if(leftClip && locs[0] <= counts[0]) {
                            str = fragClip(node,str,locs,lemmacounts,{left:leftClip,right:0});
                        }
                        else if(rightClip && locs[1] >= counts[1]) {
                            str = fragClip(node,str,locs,lemmacounts,{left:0,right:rightClip});
                        }
                    }
            
                    varTexts[mname][start] = str;

                }
                for(let n=1; n < end-start; n++)
                    varTexts[mname][start+n] = '';
            }
        }
        const features = 'menubar=no,location=no,status=no,height=550,width=550,scrollbars=yes,centerscreen=yes';
        const stemmaWindow = window.open(path,`stemma ${new Date().toLocaleString()}`,features);
        if(!arrays) {
            let xmlDoc = document.getElementById('__upama_stemma').firstChild;
            let dataObject = {nexml: xmlDoc, fileSource: true };
            stemmaWindow.dataObject = dataObject;
    
            let varTexts_linear = {};
            if(state.script === 'iast')
                for(const vT in varTexts)
                    varTexts_linear[vT] = varTexts[vT].join(' ');
            else
                for(const vT in varTexts) {
                    let tempfrag = document.createRange().createContextualFragment(varTexts[vT].join(' '));
                    let tempel = document.createElement('span');
                    tempel.appendChild(tempfrag);
                    varTexts_linear[vT] = changeScript(tempel,state.script);
                }
            stemmaWindow.varTexts = varTexts_linear;
            stemmaWindow.lemmaText = to[state.script](lemmaText);
        }
        else {
        //var stemmaWindow = window.open(path,"stemma",features);
            let varTexts_script = {};
            if(state.script !== 'iast') {
                const cloneandchange = s => {
                    const tempel = document.createElement('span');
                    tempel.appendChild(document.createRange().createContextualFragment(s));
                    return changeScript(tempel,state.script);
                };
                for(const vT in varTexts) {
                    varTexts_script[vT] = varTexts[vT].map(s => cloneandchange(s));
                }
            }
            stemmaWindow.varTexts = state.script === 'iast' ? varTexts : varTexts_script;
            stemmaWindow.lemmaArr = state.script === 'iast' ? lemmaArr : lemmaArr.map(s => to[state.script](s));
        }
        stemmaWindow.posApp = posApp;
        stemmaWindow.listWit = listWit;
        stemmaWindow.otherWit = otherWit;
        stemmaWindow.unshift = unshift;
        stemmaWindow.onload = () => stemmaWindow.init();
    };

    const fragClip = function(node,varstr,locs,lemmacounts,clips) {
        const findPos = function(walker,pos) {
            let counter = pos;
            walker.firstChild();
            do {
                const curlength = walker.currentNode.data.length;
                if(curlength >= counter)
                    return {node: walker.currentNode, offset: counter};
                else counter -= curlength;
            } while (walker.nextNode());
        };
        const lpos = clips.left ? getLemma(node,locs[0],lemmacounts.start+1).length - clips.left : 0;
        const rpos = clips.right ? getLemma(node,lemmacounts.end-1,locs[1]).length - clips.right : 0;
        const frag = document.createRange().createContextualFragment(varstr);
        const temp = document.createElement('div');
        temp.appendChild(frag);
        const walker = () => document.createTreeWalker(temp,NodeFilter.SHOW_TEXT, {acceptNode: function(node) 
        {
            if(node.parentNode.closest('.ignored')) return NodeFilter.FILTER_REJECT;
            else return NodeFilter.FILTER_ACCEPT;
        }
        });
        const lemma = getLemma(node,locs[0],locs[1]);
        const varpos = matchPos(lemma,cleanString(varstr),lpos,rpos);
        const range = document.createRange();
        if(lpos > 0 && rpos > 0) {
            const start = findPos(walker(),varpos.lpos);
            const end = findPos(walker(),varpos.rpos);
            range.setStart(start.node,start.offset);
            range.setEnd(end.node,end.offset);
        }
        else if(rpos > 0) {
            const end = findPos(walker(),varpos.rpos);
            range.setStartBefore(temp.firstChild);
            range.setEnd(end.node,end.offset);
        }
        else if(lpos > 0) {
            const start = findPos(walker(),varpos.lpos);
            range.setStart(start.node,start.offset);
            range.setEndAfter(temp.lastChild);
        }

        const span = document.createElement('span');
        span.appendChild(range.cloneContents());
        const ancest = range.commonAncestorContainer.nodeType !== 1 ? range.commonAncestorContainer.parentNode : range.commonAncestorContainer;
        if(ancest.closest('.highlight')) {
            span.classList.add('highlight');
            return span.outerHTML;
        //range.surroundContents(span);
        } else {
            return span.innerHTML;
        //const serializer = new XMLSerializer();
        //return serializer.serializeToString(range.cloneContents());
        }
    };

    const getPos = function(pos,ar) {
        const arr = (pos > 0) ? ar : [...ar].reverse();
        const end = Math.abs(pos);
        let m=0;
        let n=0;
        while(n<arr.length) {
            if(arr[n] !== '') m++;
            if(m > end) break;
            n++;
        }
        return n;
    };

    const matchPos = function(str1,str2,lpos,rpos) {
    //const str1 = (pos > 0) ? lemma.slice(0,pos) : lemma.slice(pos);
        
        const str1normal = normalize(str1);
        const str2normal = normalize(str2);
        const aligned = needlemanWunsch(str1normal.arr,str2normal.arr);
        const unnormalized = unnormalize(aligned,str1normal.matches,str2normal.matches);
        const str1lpos = lpos ? getPos(lpos,unnormalized[0]) : 0;
        const str1rpos = rpos ? getPos(-rpos,unnormalized[0]) : 0;
        const str2lclip = lpos ? unnormalized[1].slice(0,str1lpos) : false;
        const str2rclip = rpos ? unnormalized[1].slice(0,unnormalized[1].length-str1rpos) : false;
        const str2lpos = str2lclip ? str2lclip.join('').length : 0;
        const str2rpos = str2rclip ? str2rclip.join('').length : 0;
        return {lpos: str2lpos, rpos: str2rpos};
    };
    
    const normalize = function(str) {
        //const re = /[|,.-?—―=_॰+¦·\(\)\[\]\/\\\d;¯꣸❈"'`“”‘’«»]/g;
        const re = /[|,.-?—―=_॰+¦·()[\]/\\\d;¯꣸❈"'`“”‘’«»]/g;
        const matches = [...str.matchAll(re)];
        const strarr = str.split('');
        if(matches.length > 0) {
            for(const match of matches) {
                const i = match.index;
                const len = match[0].length;
                for(let l = 0;l < len;l++)
                    strarr[i+l] = '';
            }
        }
        return {arr: strarr.filter(el => el !== ''),matches: matches};
    };
    const unnormalize = function(aligned,matches1,matches2) {
        //const adjustedmatches1 = matches1.map(m => {m.index = getPos(m.index,aligned[0]);return m;});
        //const adjustedmatches2 = matches2.map(m => {m.index = getPos(m.index,aligned[1]);return m;});
        for(const match of matches1) {
            const i = getPos(match.index,aligned[0]);
            for(let l=0;l<match[0].length;l++) {
                aligned[0].splice(i+l,0,match[0].charAt(l));
                aligned[1].splice(i+l,0,'');
            }
        }
        for(const match of matches2) {
            const i = getPos(match.index,aligned[1]);
            for(let l=0;l<match[0].length;l++) {
                aligned[1].splice(i+l,0,match[0].charAt(l));
                aligned[0].splice(i+l,0,'');
            }
        }
        return aligned;
    };

    const needlemanWunsch = function(s1,s2,op={G:2,P:1,M:-1}) {
        const UP   = 1;
        const LEFT = 2;
        const UL   = 4;

        const mat   = {};
        const direc = {};
        //const s1arr = s1.split('');
        const s1arr = s1;
        const s1len = s1arr.length;
        //const s2arr = s2.split('');
        const s2arr = s2;
        const s2len = s2arr.length;

        // initialization
        for(let i=0; i<s1len+1; i++) {
            mat[i] = {0:0};
            direc[i] = {0:[]};
            for(let j=1; j<s2len+1; j++) {
                mat[i][j] = (i === 0) ? 0 : 
                    (s1arr[i-1] === s2arr[j-1]) ? op.P : op.M;
                direc[i][j] = [];
            }
        }

        // calculate each value
        for(let i=0; i<s1len+1; i++) {
            for(let j=0; j<s2len+1; j++) {
                const newval = (i === 0 || j === 0) ? 
                    -op.G * (i + j) : 
                    Math.max(mat[i-1][j] - op.G, mat[i-1][j-1] + mat[i][j], mat[i][j-1] - op.G);
                if (i > 0 && j > 0) {

                    if( newval === mat[i-1][j] - op.G) direc[i][j].push(UP);
                    if( newval === mat[i][j-1] - op.G) direc[i][j].push(LEFT);
                    if( newval === mat[i-1][j-1] + mat[i][j]) direc[i][j].push(UL);
                }
                else {
                    direc[i][j].push((j === 0) ? UP : LEFT);
                }
                mat[i][j] = newval;
            }
        }

        // get result
        const chars = [[],[]];
        var I = s1len;
        var J = s2len;
        //const max = Math.max(I, J);
        while(I > 0 || J > 0) {
            switch (direc[I][J][0]) {
            case UP:
                I--;
                chars[0].unshift(s1arr[I]);
                chars[1].unshift('');
                break;
            case LEFT:
                J--;
                chars[0].unshift('');
                chars[1].unshift(s2arr[J]);
                break;
            case UL:
                I--;
                J--;
                chars[0].unshift(s1arr[I]);
                chars[1].unshift(s2arr[J]);
                break;
            default: break;
            }
        }

        return chars;
    };
    /*
cleanString: function (node,end) {
    var str = '';
    var skipKids = false;
    //var end = end ? end : false; // if end is undefined and kid is undefined, loop ends
    var kid = node.firstChild || node.startContainer;
    for(kid;kid != end;kid = getNextNode(kid,skipKids)) {
        skipKids = false;
        if(kid.nodeType != '3') {
            if(kid.classList.contains('ignored'))
                skipKids = true;
            continue;
        }
        else str = str + kid.data;
    }
    return str;
}, */

    const cleanString = function(node) {
        const clone = typeof node === 'string' ? (() => {
            const temp = document.createElement('div');
            temp.innerHTML = node;
            return temp;
        })() :
            node instanceof Range ?
                node.cloneContents() :
                node.cloneNode(true);
        const ignored = clone.querySelectorAll('.ignored');
        ignored.forEach(e => e.parentNode.removeChild(e));
        return clone.textContent;
    };

    const spaceCount = function(str,marker,nostub = false) {
        const re = marker ? new RegExp('[\\s'+marker+']+','g') : 
            new RegExp('\\s+','g');
        const info = {spaces: 0, stublen: 0};
        var last_pos;
        while(re.exec(str)) {
            info.spaces++;
            last_pos = re.lastIndex;
        }

        if(!nostub) {
            const substr = str.substring(last_pos);
            const hyphens  = substr.match(consts.hyphenRegex);
            info.stublen = hyphens ? substr.length - hyphens.length : substr.length;
            // check for any vowels that got chopped off the front of the stub
            if(str.charAt(last_pos-1) === marker && 
            !str.charAt(last_pos).match(/['ṃ]/)) {
                let n = last_pos - 2;
                const vowelRegex = new RegExp(`[${consts.vowelChars}]`);
                while(vowelRegex.test(str.charAt(n)) === true) {
                    info.stublen = info.stublen + 1;
                    n--;
                }
            }
            
        }
        info.endspace = str.substring(str.length-1).match(re) ? 1 : 0;
        info.startspace = str.substr(0,1).match(re) ? 1 : 0;
        info.endspace = str.substring(str.length-1).match(/\s/) ? 1 : 0;
        info.startspace = str.substr(0,1).match(/\s/) ? 1 : 0;
        return info;
    };

    const replaceSpaces = function(str1,str2,marker) {
        var spaceIndex;
        var startpos = 0;
        do {
            spaceIndex = str1.indexOf(marker,startpos);
            if(spaceIndex != -1) {
                if(spaceIndex <= str2.length)
                    str2 = str2.substring(0,spaceIndex) + marker + str2.substring(spaceIndex);
                else break;
                startpos = spaceIndex+1;
            }
        } while (spaceIndex != -1 && startpos < str1.length-1);
        return str2;

    };
    /*
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
*/
    const getUrlVars = function() {
        /*var vars = {};
        var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
            vars[key] = value;
        });
        return vars;
        */
        return new URLSearchParams(window.location.search);
    };
    return {
        initialize: initialize,
        getViewPos: viewPos.get,
        setViewPos: viewPos.set,
        rewriteURL: rewriteURL,    
    };

})();

window.addEventListener ('load', upama.initialize);
