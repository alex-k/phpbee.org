<?php
class helper_clone {
	function show($label,$txt,$errors=array()) {
		return sprintf('<fieldset class="helper_clone"><legend>%s</legend>
		%s</fieldset>
		<input type="button" value="+" class="button_helper_clone">

		<script>
		$(".button_helper_clone").click(function() {
			var fs=$($(this).siblings(".helper_clone").get(0));
			var newfs=fs.clone();
			fs.after(newfs);
		});
		</script>
		',$label,$txt);
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
