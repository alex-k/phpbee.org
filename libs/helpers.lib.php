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
