function Obj2JSON(obj) {
    if(typeof JSON=='undefined') {
        var arr=[];
        for (key in obj) {
            arr.push('"'+key+'":"'+obj[key]+'"');
        }
        str='{'+arr.join(',')+'}';
        return str;
        
    } else {
        return JSON.stringify(obj);
    }
}

function gs_forms() {
    this.show_id;
    this.show=function (selector,obj) {
        this.show_id=obj.show_id;
        var res=escape(Obj2JSON(obj));
        jQuery.ajax({
           url: "/admin/gs_forms/show",
           data: 'json='+res,
           success: function(msg) {
            $(selector).append(msg);
            $(selector+' .gsf_inline').each(function() {
                gsf_events.bind(this);   
            });
            
            if(obj._bind_selector) {
                $(obj._bind_selector).each(function() {
                    gsf_events.bind(this);   
                });
            }
	    //$(selector+' .gsf_b_edit').bind('click',gsf_bindings.show_inline_form);
            //alert( "Data Saved: " + window.$f );
           }
        });
    }
}

gsf_events={
    show_inline_form:function() {
            var gsf_selector=this.getAttribute('gsf_selector');
            var gsf_id=this.getAttribute('gsf_id');
            var gsf_dir=this.getAttribute('gsf_dir');
            var gsf_action=this.getAttribute('gsf_action');
            var gsf_message=this.getAttribute('gsf_message');
	    if (gsf_selector) {
		    var cont=$(gsf_selector);
	    } else {
		    var cont=$(this).parents('.gsf_inline');
		    cont.addClass('gsf_load');
	    }
            obj={_id:gsf_id,_dir:gsf_dir,_action:gsf_action,_message:gsf_message};
            
            jQuery.ajax({
                url: "/admin/gs_forms/show",
                data: 'json='+escape(Obj2JSON(obj)),
                success: function(msg) {
                    var res=$(msg);
                    cont.replaceWith(res);
                    gsf_events.bind(res.get(0));
                }
            });
    },
    show_content_by_value:function() {
		this.setAttribute('gsf_id',this.value);
		var gsf_id=this.getAttribute('gsf_id');
		var gsf_set_field=this.getAttribute('gsf_set_field');
		$(gsf_set_field).each(function() {
			this.value=gsf_id;
			});
		//return gsf_events.show_content.bind(this)();
		return gsf_events.show_inline_form.bind(this)();
    },
    show_content:function() {
            var gsf_selector=this.getAttribute('gsf_selector');
            var gsf_id=this.getAttribute('gsf_id');
            var gsf_dir=this.getAttribute('gsf_dir');
            var gsf_action=this.getAttribute('gsf_action');
            var cont=$(gsf_selector);
            
            obj={_id:gsf_id,_dir:gsf_dir,_action:gsf_action};
            
            jQuery.ajax({
                url: "/admin/gs_forms/show",
                data: 'json='+escape(Obj2JSON(obj)),
                success: function(msg) {
                    var res=$(msg);
                    cont.html(res);
                    gsf_events.bind(res.get(0));
                }
            });
    },
    post_form_content:function() {
            var gsf_id=this.getAttribute('gsf_id');
            var gsf_dir=this.getAttribute('gsf_dir');
            var gsf_action=this.getAttribute('gsf_action');
            var gsf_class=this.getAttribute('gsf_class');
            var gsf_selector=this.getAttribute('gsf_selector');
            var gsf_gspgid= this.getAttribute('gsf_gspgid') ? this.getAttribute('gsf_gspgid') : '/admin/gs_forms/post';
            var cont=$(gsf_selector);
            cont.addClass('gsf_load');
            obj={_id:gsf_id,_dir:gsf_dir,_action:gsf_action,_class:gsf_class};
            var owner=this;
            var options = {
                url: "/admin/",
                type: "POST",
                dataType:  'json',
                semantic: true,
                success: function(res) {
                    if (res.status) {
                        //gsf_events.show_content.bind(owner)();
                        document.location.reload();
                        return true;
                    }
                    cont.removeClass('gsf_load');
                    $("input",cont).removeClass('gsf_error_field');
                    for (key in res.error_fields.MESSAGES) {
                        $("input[name='"+key+"']",cont).addClass('gsf_error_field');
                        $("select[name='"+key+"']",cont).addClass('gsf_error_field');
                    }
                    //debug(res.error_fields.MESSAGES);
                }
            };
            cont.append('<input type="hidden" name="json" value=\''+Obj2JSON(obj)+'\'>\n');
            cont.append('<input type="hidden" name="gspgid" value="'+gsf_gspgid+'">\n');
            cont.ajaxSubmit(options);
    },
    
    show_new_form:function() {
            var gsf_selector=this.getAttribute('gsf_selector');
            var gsf_id=this.getAttribute('gsf_id');
            var gsf_dir=this.getAttribute('gsf_dir');
            var gsf_action=this.getAttribute('gsf_action');
            var cont=$(gsf_selector);
            obj={_id:gsf_id,_dir:gsf_dir,_action:gsf_action};
            jQuery.ajax({
                url: "/admin/gs_forms/show",
                data: 'json='+escape(Obj2JSON(obj)),
                success: function(msg) {
                    var res=$(msg);
                    cont.prepend(res);
                    gsf_events.bind(res.get(0));
                }
            });
    },
    close_inline_form:function() {
            var cont=$(this).parents('.gsf_inline');
            cont.remove();
    },
    remove_content:function() {
            var gsf_selector=this.getAttribute('gsf_selector');
            var cont=$(gsf_selector);
            cont.empty();
    },
    post_form:function() {
            var gsf_id=this.getAttribute('gsf_id');
            var gsf_dir=this.getAttribute('gsf_dir');
            var gsf_action=this.getAttribute('gsf_action');
            var gsf_class=this.getAttribute('gsf_class');
            var cont=$(this).parents('.gsf_inline');
            cont.addClass('gsf_load');
            obj={_id:gsf_id,_dir:gsf_dir,_action:gsf_action,_class:gsf_class};
            var owner=this;
            var options = {
                url: "/admin/",
                type: "POST",
                dataType:  'json',
                semantic: true,
                success: function(res) {
                    if (res.status) {
                        obj._id=res.id;
                        owner.setAttribute('gsf_action','show');
                        owner.setAttribute('gsf_id',obj._id);
                        gsf_events.show_inline_form.bind(owner)();
                        return true;
                    }
                    cont.removeClass('gsf_load');
                    $("input",cont).removeClass('gsf_error_field');
                    for (key in res.error_fields.MESSAGES) {
                        $("input[name='"+key+"']",cont).addClass('gsf_error_field');
                    }
                    if(res.exception) {
                        alert('ex!');
                        owner.setAttribute('gsf_action','exception');
                        owner.setAttribute('gsf_message',res.exception_message);
                        gsf_events.show_inline_form.bind(owner)();
                        return true;
                    }
                    //debug(res.error_fields.MESSAGES);
                }
            };
            cont.append('<input type="hidden" name="json" value=\''+Obj2JSON(obj)+'\'>\n');
            cont.append('<input type="hidden" name="gspgid" value="/admin/gs_forms/post">\n');
            cont.ajaxSubmit(options);
    },
    post_close_form:function() {
            var gsf_id=this.getAttribute('gsf_id');
            var gsf_dir=this.getAttribute('gsf_dir');
            var gsf_action=this.getAttribute('gsf_action');
            var gsf_class=this.getAttribute('gsf_class');
            var cont=$(this).parents('.gsf_inline');
            cont.addClass('gsf_load');
            obj={_id:gsf_id,_dir:gsf_dir,_action:gsf_action,_class:gsf_class};
            var owner=this;
            var options = {
                url: "/admin/",
                type: "POST",
                dataType:  'json',
                semantic: true,
                success: function(res) {
                    if (res.status) {
                        gsf_events.close_inline_form.bind(owner)();
                        return true;
                    }
                    cont.removeClass('gsf_load');
                    $("input",cont).removeClass('gsf_error_field');
                    for (key in res.error_fields.MESSAGES) {
                        $("input[name='"+key+"']",cont).addClass('gsf_error_field');
                    }
                    if(res.exception) {
                        owner.setAttribute('gsf_action','exception');
                        owner.setAttribute('gsf_message',res.exception_message);
                        gsf_events.show_inline_form.bind(owner)();
                        return true;
                    }
                    //debug(res.error_fields.MESSAGES);
                }
            };
            cont.append('<input type="hidden" name="json" value=\''+Obj2JSON(obj)+'\'>\n');
            cont.append('<input type="hidden" name="gspgid" value="/admin/gs_forms/post">\n');
            cont.ajaxSubmit(options);
    },
    demo: function () { alert ('demo'); },
    
    bind:function (obj) {
        for (attr in gsf_bindings) {
            if ($(obj).hasClass(attr)) {
                var e=gsf_bindings[attr];
                for (ev in e) {
                    $('.'+ev,obj).bind('click',e[ev]);
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
            gsf_b_edit:gsf_events.show_inline_form,
            gsf_b_delete:gsf_events.show_inline_form
        },
        gsf_insert:{
            gsf_b_cancel:gsf_events.close_inline_form,
            gsf_b_save:gsf_events.post_form
        },
        gsf_delete:{
            gsf_b_cancel:gsf_events.show_inline_form,
            gsf_b_save:gsf_events.post_close_form
        },
        gsf_form:{
            gsf_b_add:gsf_events.show_new_form
        },
        gsf_show_content:{
            gsf_b_add:gsf_events.show_content,
            gsf_b_save:gsf_events.post_form_content,
            gsf_b_cancel:gsf_events.show_content,
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
