$.fn.progressbar = function(percent){
	$(this).filter(":not([progressbar])").attr({
		"progressbar":true
	}).css({
		"background-color":"#FFF",
		"border":"1px solid #666"
	}).css3({
		"border-radius":"5px"
	}).each(function(){
		var div = document.createElement("div");
		this.appendChild(div);
		$(div).css({
			"background-image":"url(../img/pbar-ani.gif)",
			"border-right":"1px solid #778",
			"height":"100%",
			"width":"0%"
		});
	});
	
	if(!isNaN(percent)){
		percent = Math.floor(percent) + "%";
		$(this).find("div").width(percent);
		return this;
	}
	return this;
}