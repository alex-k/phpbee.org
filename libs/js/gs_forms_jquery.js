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
           }
        });
    }
}

gsf_events={
    myforms_show_inline:function() {
            var gsf_selector=this.getAttribute('gsf_selector');
            var gsf_id=this.getAttribute('gsf_id');
            var gsf_action=this.getAttribute('gsf_action');
            var gsf_message=this.getAttribute('gsf_message');
            var gsf_template=this.getAttribute('gsf_template');
            var gsf_template_type=this.getAttribute('gsf_template_type');
            var gsf_classname=this.getAttribute('gsf_classname');

	    if (gsf_selector) {
		    var cont=$(gsf_selector);
	    } else {
		    var cont=$(this).parents('.gsf_inline');
		    cont.addClass('gsf_load');
	    }
            obj={_id:gsf_id,_action:gsf_action,_message:gsf_message,_template:gsf_template,_template_type:gsf_template_type,_classname:gsf_classname};
            
            jQuery.ajax({
                url: "/admin/gs_forms/myforms/",
                data: 'json='+escape(Obj2JSON(obj)),
                success: function(msg) {
                    var res=$(msg);
		    cont.empty();
                    cont.prepend(res);
                }
            });
    },
    myforms_close_inline:function() {
            var cont=$(this).parents('.gsf_inline');
            cont.remove();
    },
    myforms_add_inline:function() {
            var gsf_selector=this.getAttribute('gsf_selector');
            var gsf_id=this.getAttribute('gsf_id');
            var gsf_action=this.getAttribute('gsf_action');
            var gsf_template=this.getAttribute('gsf_template');
            var gsf_template_type=this.getAttribute('gsf_template_type');
            var gsf_classname=this.getAttribute('gsf_classname');
	    if (gsf_selector) {
		    var cont=$(gsf_selector);
	    } else {
		    var cont=$(this).parents('.gsf_table');
		    var cont=$('tbody',cont);
		    cont.addClass('gsf_load');
	    }
            obj={_id:gsf_id,_action:gsf_action,_template:gsf_template,_template_type:gsf_template_type,_classname:gsf_classname};
            jQuery.ajax({
                url: "/admin/gs_forms/myforms/",
                data: 'json='+escape(Obj2JSON(obj)),
                success: function(msg) {
                    var res=$(msg);
                    cont.prepend(res);
                }
            });
    },
    myforms_post_form:function() {
            var gsf_id=this.getAttribute('gsf_id');
            var gsf_action=this.getAttribute('gsf_action');
            var gsf_class=this.getAttribute('gsf_classname');
            var cont=$(this).parents('.gsf_inline');
            cont.addClass('gsf_load');
            obj={_id:gsf_id,_action:gsf_action,_class:gsf_class};
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
                        gsf_events.myforms_show_inline.bind(owner)();
                        return true;
                    }
                    cont.removeClass('gsf_load');
                    $("input",cont).removeClass('gsf_error_field');
                    for (key in res.error_fields.MESSAGES) {
                        $("input[name='"+key+"'],textarea[name='"+key+"']",cont).addClass('gsf_error_field');
                        //$("textarea[name='"+key+"']",cont).addClass('gsf_error_field');
                    }
                    if(res.exception) {
                        alert('ex!');
                        owner.setAttribute('gsf_action','exception');
                        owner.setAttribute('gsf_message',res.exception_message);
                        return true;
                    }
                }
            };
	    $("input,textarea",cont).removeClass('gsf_error_field');
            cont.append('<input type="hidden" name="json" value=\''+Obj2JSON(obj)+'\'>\n');
            cont.append('<input type="hidden" name="gspgid" value="/admin/gs_forms/post">\n');
            cont.ajaxSubmit(options);
    },



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
                    $('.'+ev,obj).unbind('click',e[ev]);
                    $('.'+ev,obj).bind('click',e[ev]);
		    $('.'+ev,obj).each(function() { this.value=this.value+'*'; });
                }
            }
        }
    },
    bind_myforms:function (obj) {
	    	//alert('bind_myforms:function');
                var e=gsf_bindings['gsf_myforms'];
                for (ev in e) {
		    if ($(obj).hasClass(ev)) {
			    $(obj).unbind('click',e[ev]);
			    $(obj).bind('click',e[ev]);
		    }
                }
	        obj.value=obj.value+'*';
	        obj.setAttribute('_gsf_binded',1);
    }
}

gsf_bindings={
        gsf_edit:{
            gsf_b_cancel:gsf_events.show_inline_form,
            gsf_b_save:gsf_events.post_form
        },
        gsf_myforms:{
	    gsf_b_show_inline:gsf_events.myforms_show_inline,
	    gsf_b_add_inline:gsf_events.myforms_add_inline,
	    gsf_b_remove_inline:gsf_events.myforms_close_inline,
	    gsf_b_post:gsf_events.myforms_post_form
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
