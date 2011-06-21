$(document).ready (function (){
	$(".lMany2Many").gs_multiselect();
	$(".fDateTime").datepicker();
	$('.fWysiwyg').rte({
		//css: ['default.css'],
		controls_rte: rte_toolbar,
		controls_html: html_toolbar
	});
});
