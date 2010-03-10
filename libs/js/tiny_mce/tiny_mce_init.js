function setTinyMCE() {
	tinyMCE.init({
	    theme : "advanced",
	    mode : "textareas",
	    plugins : "table,paste,nonbreaking",
	    //theme_advanced_toolbar_location : "external",
	    theme_advanced_toolbar_location : "top",
	    theme_advanced_toolbar_align : "left",
	    theme_advanced_buttons1: "bold,italic,underline,|,fontselect,fontsizeselect,|,forecolor,backcolor,|,justifyleft,justifycenter,justifyright,|,image,link,unlink,|,bullist,numlist,charmap,|,formatselect",
	    theme_advanced_buttons2 : "",
	    theme_advanced_buttons3 : "",
	    add_unload_trigger : false,
	    remove_linebreaks : false,
	    debug : false,
	    width: "100%",
	    //handle_event_callback : "set_current_editor"
	});
}
