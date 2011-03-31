<?php
class helper_clone {
	function show($label,$txt,$errors=array()) {
		return sprintf('<div class="helper_clone">
		%s</div>
		<input type="button" value="+" class="button_helper_clone">

		<script>
		$(".button_helper_clone").click(function() {
			var fs=$($(this).siblings(".helper_clone").get(-1));
			var newfs=fs.clone();
			var id=parseInt($("input,textarea,select",newfs).get(0).name.match(/%s:(-?\d+):/)[1])-1;
			$("input,textarea,select",newfs).each(function() {
				this.name=this.name.replace(/%s:-?\d+:/,"%s:"+id+":");
			});
			fs.after(newfs);
		});
		</script>
		',$txt,$label,$label,$label);
	}
}
class helper_fieldset {
	function show($label,$txt,$errors=array()) {
		return sprintf('<fieldset><legend>%s</legend>%s</fieldset>',$label,$txt);
	}
}
class helper_dl {
	function show($label,$txt,$errors=array()) {
		return sprintf('<dl>%s</dl>',$txt);
	}
}
class helper_dt {
	function show($label,$txt,$errors=array()) {
		$e = $errors ? '<div class="error">Error: '.implode(',',$errors).'</div>' : '';
		return sprintf('<dt>%s</dt><dd>%s%s</dd>',$label,$txt,$e);
	}
}


class helper_table {
	function show($label,$txt,$errors=array()) {
		return sprintf('<table class="helper_table">%s</table>',$txt);
	}
}
class helper_table_submit{
    function show($label,$txt,$errors=array()) {
         return sprintf('<table class="helper_table"><tr><td class="helper_table_submit_l"><input type="submit" value="%s">
         </td><td class="helper_table_submit_r"><input type="submit" value="%s"></td></tr>%s<tr><td class="helper_table_submit_l">
         <input type="submit" value="%s"></td><td class="helper_table_submit_r"><input type="submit" value="%s"></td></tr></table>',
         gs_dict::get('SUBMIT_FORM'),
         gs_dict::get('SUBMIT_FORM'),
         $txt,
         gs_dict::get('SUBMIT_FORM'),
         gs_dict::get('SUBMIT_FORM'));
    }
}


class helper_empty {
	function show($label,$txt,$errors=array()) {
		return sprintf('%s',$txt);
	}
}

class helper_inline {
	function show($label,$txt,$errors=array()) {
		return sprintf('<div class="inline"><div>%s</div>%s</div>',$label,$txt);
	}
}


class helper_tr {
	function show($label,$txt,$errors=array()) {
		$e = $errors ? '<div class="error">Error: '.implode(',',$errors).'</div>' : '';
		return sprintf('<tr class="helper_tr"><td class="helper_tr_title">%s</td><td class="helper_tr_field">%s%s</td></tr>',$label,$txt,$e);
	}
}
