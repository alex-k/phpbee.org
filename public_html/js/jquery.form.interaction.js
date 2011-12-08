(function($) {

	var options = {
		'startfield':false
		};

	var events = {
		'.fRadio':'change',	
		'.fCheckbox':'change',	
		'.fSelect':'change',	
		};
	
	$.fn.interaction= function(params) {
		options = $.extend({}, $.fn.interaction.defaults, params);

		show = function(obj) {
			obj.show();
		}
		hide= function(obj) {
			obj.hide();
		}
				
		answer = function(data) {
			for ( k in data) {
				var d=data[k];
				var obj=$('[name='+d.field+']');
				var i_box=obj.closest('.interact_box');
				if (i_box.size()) obj=i_box;
				self[d.action](obj);
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
	
			if (options.startfield) {
				$('[name='+options.startfield+']',this).each(postform);
			}

		});

	};


	
	
	
	$.fn.interaction.defaults = {
		
	};
	
})(jQuery);
