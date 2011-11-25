	var params = {  
		BGColor: "#FFFFFF"
	};
	
	var attributes = {  
		id: "MultiPowUpload",  
		name: "MultiPowUpload"
	};

	
	var flashvars = {
      "language.autoDetect": "true",
	  "serialNumber": "0081661701492178151138183160131275818427540204",
	  "uploadUrl": "/widgets/MultiPowUpload/FileProcessingScripts/PHP/uploadfiles.php?stayHere=true&",
	  "customPostFields":params_str,
	  //"uploadUrl": "http://demo.element-it.com/Examples/multipow/FileProcessingScripts/PHP/uploadfiles.php?stayHere=true",
	  "fileFilter.types":"Images|*.jpg:*.jpeg:*.gif:*.png:*.bmp",
	  "sendThumbnails": "true",
	  "sendOriginalImages": "false",
	  "useExternalInterface": "true",
	  "fileView.defaultView":"thumbnails",
	  "thumbnail.width": "1600",
	  "thumbnail.height": "1600",
	  "thumbnail.resizeMode": "fit",
	  "thumbnail.format": "AUTO",
	  "thumbnail.jpgQuality": "85",
	  "thumbnail.resizeSmallImages":"false",
	  "thumbnail.backgroundColor": "#000000",
	  "thumbnail.transparentBackground": "true",
	  "thumbnail.autoRotate": "true",
	  "readImageMetadata": "true",
	  "thumbnailView.allowCrop": "true",
	  "thumbnailView.allowRotate": "true",
	  "thumbnailView.cropRectKeepAspectRatio": "NO",
	  "thumbnailView.showCropRectDimensions": "true",

	  "thumbnail.watermark.enabled":"true",
	  //"thumbnail.watermark.imageUrl":"images/element-it.jpg",
		//Center.Left Bottom.Left
	  "thumbnail.watermark.position":"bottom.right",
	  "thumbnail.watermark.alpha":"0.5",
	  "thumbnail.watermark.text":"www.bandw.ru",
	  "thumbnail.watermark.textStyle.size":"22",
	  "thumbnail.watermark.textStyle.color":"#FF0000",
	  "thumbnail.watermark.textStyle.font":"_sans",
	  "thumbnail.watermark.textStyle.style":"bold"

	};
	//Default MultiPowUpload should have minimum width=400 and minimum height=180
	swfobject.embedSWF("/widgets/MultiPowUpload/ElementITMultiPowUpload.swf", "MultiPowUpload_holder", "900", "350", "10.0.0", "/widgets/MultiPowUpload/Extra/expressInstall.swf", flashvars, params, attributes);


	var path_to_file = "";
	
	function MultiPowUpload_onThumbnailUploadComplete(li, response)
	{
			addThumbnail(response);
	}
	
	function addThumbnail(source)
	{
		/*
		var Img = document.createElement("img");
		Img.style.margin = "5px";
		Img.src = path_to_file+source+"?"+(new Date()).getTime();;
		document.getElementById("gallery_"+hash).appendChild(Img);
		*/
		var g=document.getElementById("gallery_"+hash);
		var s=g.innerHTML;
		s+="<li><img src=\""+source+"\"></li>";
		g.innerHTML=s;
	}


