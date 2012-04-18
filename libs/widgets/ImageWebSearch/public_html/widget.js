
/*
function ImageWebSearch_search(obj) {
	ImageWebSearch_search_completed=function() {
		if (!imageSearch.results || imageSearch.results.length==0) return false;
		var d=$(obj).siblings('div').first();
		d.html('');
	
		var results = imageSearch.results;
		for (var i = 0; i < results.length; i++) {
			var result = results[i];
			var img=new Image();
			img.src=result.tbUrl;
			img.url=$(obj).attr('rel');
			img.imgurl=result.url;
			img.onclick=ImageWebSearch_click;
			d.append(img);
		}
	}
	var inp=$(obj).closest('form').find('input[type=text]').get(0);
	imageSearch = new google.search.ImageSearch();
	imageSearch.setSearchCompleteCallback(this, ImageWebSearch_search_completed, null);
	imageSearch.setResultSetSize(8);
	imageSearch.execute(inp.value);

}
*/

function ImageWebSearch_search(obj) {
	var inp=$(obj).closest('form').find('input[type=text]').get(0);
	ImageWebSearch_search_completed=function(imageSearch) {
		if (!imageSearch.results || imageSearch.results.length==0) return false;
		var d=$(obj).siblings('div').first();
		d.html('');
	
		var results = imageSearch.results;
		for (var i = 0; i < results.length; i++) {
			var result = results[i];
			//var img=new Image();
			var img=$('<div class="MultiPowUploadGalleryItem"><a href="#" onClick="return false;" class="preview"><img src="'+result.tbUrl+'"></a></div>').get(0);
			img.url=$(obj).attr('rel');
			img.imgurl=result.url;
			img.onclick=ImageWebSearch_click;
			d.append(img);
		}
	}
    jQuery.ajax('/widgets/ImageWebSearch/search', {
				context: this,
				dataType: "json" ,
				data: {search: inp.value},
				success : ImageWebSearch_search_completed
			});
    return false;

}

function ImageWebSearch_click() {
	$(this).addClass("MultiPowUploadCheckedElement");
    jQuery.ajax(this.url, {
				context: this,
				dataType: "html" ,
				data: {src: this.imgurl},
				success : function(ret) {
					$("#gallery_"+hash).append(ret);
				}
			}
               );
    return false;

}


	$(document).ready(function() {
		$("#gallery_"+hash+" div.MultiPowUploadGalleryItem").live("click",function(){
			var li=$(this);
			var inp=$("input",li);
			inp.attr('checked',!(inp.attr('checked')));
			li.removeClass('MultiPowUploadCheckedElement');
			if(inp.attr('checked')) li.addClass('MultiPowUploadCheckedElement');
		});


		$(".MultiPowUploadGalleryGroup").dblclick(function(){
			$("div.MultiPowUploadGalleryItem",this).click();
			return false;
		});
		$(".MultiPowUploadGallery").dblclick(function(){
			$("div.MultiPowUploadGalleryItem",this).click();
			return false;
		});

		$("#checked_items_submit").click(function(){
			var form=$(this).closest('form');
			//var action=$(':radio[name=checked_items_action]',form).filter(":checked").val();
			var gspgid="widgets/MultiPowUpload/action";
			$('input[name=gspgid_form]',form).val(gspgid);
			form.get(0).submit();
		});
	});
