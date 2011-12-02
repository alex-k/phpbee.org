$(document).ready (function () {
	$(".lMany2Many").gs_multiselect();
	$(".fMultiSelect").gs_multiselect();
$(".lOne2One").sel_filter( {slide_width: 150, min_options: 1, crop: false});
	$(".fSelect").sel_filter();
	$(".fDateTime").datepicker();
	$('.fWysiwyg').rte( {
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

	$('#tpl_content').each(function () {
		//window['tpl_codemirror'] = CodeMirror.fromTextArea(this, { mode:"text/html", tabMode:"indent",lineNumbers: true });

		window['tpl_codemirror'] = CodeMirror.fromTextArea(this,
		{
lineNumbers: true,
matchBrackets: true,
mode: "application/x-httpd-php",
			indentUnit: 8,
indentWithTabs: true,
enterMode: "keep",
tabMode: "shift"
		});
	});

	$('.ch_all').click(
	function() {
		$('.ch1').attr('checked',this.checked);
	}
	);
	$('.fDateTimeFilter').each(function() {
		$(this).daterangepicker(
		{
dateFormat: $.datepicker.ATOM,
onOpen: function() {
				$('.ui-daterangepicker:visible .ui-daterangepicker-specificDate').trigger('click');
			}
		}
		);
	});

});


function toggle(target,def) {
	$(target+' .toggle_head').click(function() {
		window['last_opened_'+target]=$(this).attr('id');
		$(this).next().toggle();
		return false;
	}).next().hide();
	$(def).show();
	$("#"+window['last_opened_'+target]).next().show();
	//$($(target).get(0).getAttribute('last_opened')).next().show();
}


function toggle_complex(target,items) {
	$(target+' .toggle_head').click(function() {
		var id=$(this).attr('id');
		var t_id='toggle_last_opened_'+$(target).attr('id');
		$(target+' .toggle_head').css('font-weight','normal');
		if(window[t_id]!=id) {
			$(items+"#toggle_"+window[t_id]).toggle();
		}
		$(items+' #toggle_'+id).toggle();
		if ($(items+' #toggle_'+id).is(":visible")) $(target+' #'+id).css('font-weight','bold');
		window[t_id]=id;
		return false;
	});
	$(items+' .toggle_item').hide();
	return $(target);
}
function toggle(target,def) {
	$(target+' .head').click(function() {
	window['last_opened_'+target]=$(this).attr('id');
	$(this).next().toggle();
	return false;
	}).next().hide();
	$(def).show();
	$("#"+window['last_opened_'+target]).next().show();
	//$($(target).get(0).getAttribute('last_opened')).next().show();
}
