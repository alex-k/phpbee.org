<?php
class helper_dl {
	function show($label,$txt,$errors) {
		return sprintf('<dl>%s</dl>',$txt);
	}
}
class helper_dt {
	function show($label,$txt,$errors) {
		$e = $errors ? '<div class="error">Error: '.implode(',',$errors).'</div>' : '';
		return sprintf('<dt>%s</dt><dd>%s%s</dd>',$label,$txt,$e);
	}
}
