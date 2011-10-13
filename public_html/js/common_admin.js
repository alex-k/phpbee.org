$(document).ready (function (){
	$(".lMany2Many").gs_multiselect();
	$(".fDateTime").datepicker();
	$(".fDateTimeFilter").datepicker();
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

	$("table.tb").children("tbody").children("tr").mouseover(function() {
		$(this).addClass("over");
	});

	$("table.tb tr").mouseout(function() {
		$(this).removeClass("over");
	});
	
	$('#tpl_content').each(function (){
		window['tpl_codemirror'] = CodeMirror.fromTextArea(this, { mode:"text/html", tabMode:"indent",lineNumbers: true });
	});
	
});
