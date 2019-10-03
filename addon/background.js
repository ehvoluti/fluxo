$.background = function(bool){
	if(bool){
		var background = document.createElement("div");
		document.body.appendChild(background);
		$(background).attr({
			"__background": true,
			"tabindex": "0"
		}).css({
			"background-color": "#EEE",
			"height": "100%",
			"left": "0px",
			"opacity": "0.65",
			"position": "fixed",
			"top": "0px",
			"width": "100%"
		});
		return $(background);
	}else{
		$.parentSelector("[__background]:last").remove();
	}
};