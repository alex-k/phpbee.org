$(document).ready (function (){
	$(".fDateTime").datepicker();
	$('.fWysiwyg').rte({
		//css: ['default.css'],
		controls_rte: rte_toolbar,
		controls_html: html_toolbar
	});
});
