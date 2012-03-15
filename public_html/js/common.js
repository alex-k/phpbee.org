jQuery(document).ready(function() { 
	$("form").interaction({startfield:'Make_id',start_pattern:'.lOne2One,.interact_start_field [name]'});
	$(".chosen_multiselect").chosen();
	$(".chosen").chosen();
	$(".lMany2Many").gs_multiselect();
	$(".fMultiSelect").gs_multiselect();
	$(".fSelect").sel_filter();
	$(".fDateTime").datepicker();
});





