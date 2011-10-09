$(document).ready(function() { 
	$('.slider').tinycarousel({ display: 1 });
	//$('.slider').tinycarousel();

	//jQuery('a[href$=jpg], a[href$=png], a[href$=gif], a[href$=jpeg]').prettyPhoto({theme:"light_square"});
	//$('#frontpage-slider').aviaSlider({blockSize:{height:80,width:80},transition:'slide',display:'all',transitionOrder:['diagonaltop','diagonalbottom','topleft','bottomright','random']});
	//$('#diagonal-blocks').aviaSlider({blockSize:{height:80,width:80},transition:'slide',display:'diagonaltop',switchMovement:true});
	//$('#frontpage-slider').aviaSlider({blockSize:{height:80,width:80},transition:'slide',display:'topleft',switchMovement:true});
	$('#frontpage-slider').aviaSlider({blockSize:{height:80,width:80},transition:'slide',display:'random'});
	//$('#frontpage-slider').aviaSlider({blockSize:{height:'full',width:40},display:'topleft',transition:'fade',betweenBlockDelay:150,animationSpeed:600,switchMovement:true});
	//$('#fading-top-curtain').aviaSlider({blockSize:{height:40,width:'full'},display:'topleft',transition:'fade',betweenBlockDelay:150,animationSpeed:600,switchMovement:true});
	//$('#fullwidth-fade-slider').aviaSlider();
	//$('#direction-fade-slider').aviaSlider({blockSize:{height:3,width:'full'},display:'topleft',transition:'fade',betweenBlockDelay:10,animationSpeed:400,switchMovement:true});
	//$('#droping-curtain').aviaSlider({blockSize:{height:'full',width:40},display:'topleft',transition:'drop',betweenBlockDelay:80,animationSpeed:800,switchMovement:true,slideControlls:'items',appendControlls:'.aviaslider'})
});

