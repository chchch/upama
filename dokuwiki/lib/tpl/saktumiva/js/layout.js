window.addEventListener("load", function() {
    document.getElementById('sidebar-toggler').addEventListener("click", function(e) {
        upama.getViewPos();
        var toggler = e.currentTarget;
        toggler.classList.toggle('toggler-closed');
        document.getElementById('sidebar').classList.toggle('sidebar-hide');
        document.getElementById('sidebar-wrapper').classList.toggle('sidebar-shrink');
        upama.setViewPos();
        if(toggler.classList.contains('toggler-closed'))
            upama.rewriteURL('',{sidebar: 'closed'});
        else
            upama.rewriteURL('',{sidebar: null});
    });

    jQuery('#__upama_options label').on('click',function(ev) {
        var radioel = document.getElementById(this.htmlFor);
        if(radioel.checked){
            ev.preventDefault();
            radioel.checked = false;
        }
    });
/*
    var apparati2 = document.querySelector('.sectiontext') ? jQuery('.apparatus2 > .maintext > .sectiontext') : jQuery('.apparatus2 > .maintext');
//    apparati2.accordion({collapsible: true, heightStyle:content,active: false});
    if(document.querySelector('.apparatus')) {
        for(let app of document.querySelectorAll('.apparatus2')) {
            var target = app.getAttribute('data-target');
            console.log(document.getElementById(target));
            document.getElementById(target).querySelector('.apparatus').appendChild(app);
        }
    }
    
    jQuery('.apparatus2 .maintext').css('display','block');
*/
});

