$(document).ready (function (){
	$(".lMany2Many").gs_multiselect();
	$(".fDateTime").datepicker();
	$('.fWysiwyg').rte({
		//css: ['default.css'],
		controls_rte: rte_toolbar,
		controls_html: html_toolbar
	});

	$("[-data-href]").dblclick(function() {
		window.document.location.href=$(this).attr('-data-href');
		return false;
	});
	$("[-data-href]").each(function() {
		$(this).attr('title',$(this).attr('-data-href'));
	});
});
