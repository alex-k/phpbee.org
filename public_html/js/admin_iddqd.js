$(document).ready( function() {
	$("beeblock").dblclick(function() {
		var url=window.document.location.href.replace("/admin/wizard/iddqd","/admin/wizard/iddqdblock");
		url+="/"+this.id.replace("bee_block_","");
		window.open(url,'_blank',"width=1000px,height=700px");
	});
	
});
