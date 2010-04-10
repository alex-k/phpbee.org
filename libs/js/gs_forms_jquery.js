function gs_forms() {
    this.show_id;
    this.show=function (selector,obj) {
        this.show_id=obj.show_id;
        var res=JSON.stringify(obj);
        //$(obj.show_id).load('/admin/gs_forms/show','json='.res);
        jQuery.ajax({
           url: "/admin/gs_forms/show",
           data: 'json='+res,
           success: function(msg) {
            $(selector).append(msg);
             //alert( "Data Saved: " + msg );
           }
        });

        
    }
}