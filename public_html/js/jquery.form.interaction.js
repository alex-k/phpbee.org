(function($) {

	var options = {
		'start_field':false,
		'start_pattern':false
		};

	var events = {
		'.fRadio':'change',	
		'.fCheckbox':'change',	
		'.fSelect':'change',	
		'.lOne2One':'change',	
		};
	
	$.fn.interaction= function(params) {
		options = $.extend({}, $.fn.interaction.defaults, params);

		show = function(obj,d) {
			obj.show();
		}
		hide= function(obj,d) {
			obj.hide();
		}

		replace_element = function (obj,d) {
			$('[name='+d.field+']',obj).replaceWith(d.html);
		}
				
		answer = function(data) {
			for ( k in data) {
				var d=data[k];
				var obj=$('[name='+d.field+']');
				var i_box=obj.closest('.interact_box');
				if (i_box.size()) obj=i_box;
				self[d.action](obj,d);
			}
		}
		postform=function() {
			var form = $(this).closest('form');
			var data = {} ;
			data['gsform_interact'] = this.name;
			data['gspgid_form']=$('[name=gspgid_form]',form).val();
			$('.fInteract',form).each(function() {
				if ($(this).hasClass('fCheckbox')) {
					data[this.name]=this.checked ? 1 : 0;
				} else if ($(this).hasClass('fRadio')) {
					if (this.checked) data[this.name]=this.value;
				} else {
					data[this.name]=this.value;
				}
			});

			$.ajax({
				url:document.location.href,
				data: data,
				type: 'POST',
				dataType: 'JSON',
				success : answer
			});


		}

		return this.each(function() {

			$('.fInteract',this).each(function() {
				for (ev in events) {
					$(this).filter(ev).bind(events[ev],postform);
				}

			});
	
			if (options.start_field) {
				$('[name='+options.start_field+']',this).each(postform);
			}
			if (options.start_pattern) {
				$(options.start_pattern,this).each(postform);
			}

		});

	};


	
	
	
	$.fn.interaction.defaults = {
		
	};
	
})(jQuery);
