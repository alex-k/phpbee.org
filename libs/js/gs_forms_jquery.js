function gs_forms() {
    this.show_id;
    this.show=function (selector,obj) {
        this.show_id=obj.show_id;
        var res=JSON.stringify(obj);
        jQuery.ajax({
           url: "/admin/gs_forms/show",
           data: 'json='+res,
           success: function(msg) {
            $(selector).append(msg);
            $(selector+' .gsf_inline').each(function() {
                gsf_events.bind(this);   
            });
	    //$(selector+' .gsf_b_edit').bind('click',gsf_bindings.show_inline_form);
            //alert( "Data Saved: " + window.$f );
           }
        });
    }
}

gsf_events={
    show_inline_form:function() {
            var gsf_id=this.getAttribute('gsf_id');
            var gsf_dir=this.getAttribute('gsf_dir');
            var gsf_action=this.getAttribute('gsf_action');
            var cont=$(this).parents('.gsf_inline');
            obj={_id:gsf_id,_dir:gsf_dir,_action:gsf_action};
            jQuery.ajax({
                url: "/admin/gs_forms/show",
                data: 'json='+JSON.stringify(obj),
                success: function(msg) {
                    var res=$(msg);
                    cont.replaceWith(res);
                    //alert(res.get(0).className);
                    gsf_events.bind(res.get(0));
                }
            });
    },
    post_form:function() {
            var gsf_id=this.getAttribute('gsf_id');
            var gsf_dir=this.getAttribute('gsf_dir');
            var gsf_action=this.getAttribute('gsf_action');
            var gsf_class=this.getAttribute('gsf_class');
            var cont=$(this).parents('.gsf_inline');
            obj={_id:gsf_id,_dir:gsf_dir,_action:gsf_action,_class:gsf_class};
            var owner=this;
            var options = {
                url: "/admin/",
                type: "POST",
                dataType:  'json',
                semantic: true,
                success: function(res) {
                    if (res.status) {
                        owner.setAttribute('gsf_action','show');
                        gsf_events.show_inline_form.bind(owner)();
                        return true;
                    }
                    for (key in res.error_fields.MESSAGES) {
                        $("input[name='"+key+"']",cont).addClass('gsf_error_field');
                    }
                    debug(res.error_fields.MESSAGES);
                    /*
                    var res=$(responseText);
                    cont.replaceWith(res);
                    */
                }
            };
            cont.append('<input type="hidden" name="json" value=\''+JSON.stringify(obj)+'\'>\n');
            cont.append('<input type="hidden" name="gspgid" value="/admin/gs_forms/post">\n');
            cont.ajaxSubmit(options);
            /* 
            jQuery.ajax({
                url: "/admin/gs_forms/post",
                type: "POST",
                data: 'json='+JSON.stringify(obj),
                success: function(msg) {
                    var res=$(msg);
                    cont.replaceWith(res);
                    //alert(res.get(0).className);
                    gsf_events.bind(res.get(0));
                }
            });
            */
    },
    
    demo: function () { alert ('demo'); },
    
    bind:function (obj) {
        for (attr in gsf_bindings) {
            if ($(obj).hasClass(attr)) {
                var e=gsf_bindings[attr];
                for (event in e) {
                    $('.'+event,obj).bind('click',e[event]);
                }
            }
        }
    }
}

gsf_bindings={
        gsf_edit:{
            gsf_b_cancel:gsf_events.show_inline_form,
            gsf_b_save:gsf_events.post_form
        },
        gsf_show:{
            gsf_b_edit:gsf_events.show_inline_form
        }
}


function debug (v) {
    var str='';
    for (key in v) {
        str+=key+': '+v[key]+'\n';
    }
    alert (str);
}


Function.prototype.bind = function(object) {
    var method = this
    return function() {
        return method.apply(object, arguments) 
    }
}
