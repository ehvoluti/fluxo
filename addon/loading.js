$.loading = function(status){
	var id = "cwLoadingScreen";
	if(status === true){
		$("body").css("overflow","hidden");
		if($("#" + id).length == 0){
			var element = document.createElement("div");
			element.id = id;
			document.body.appendChild(element);
			$("#" + id).attr({
				"tabindex":"0"
			}).css({
				"background":"#FFFFFF url(../img/loading.gif) center no-repeat",
				"height":"100%",
				"left":"0px",
				"opacity":"0.75",
				"position":"absolute",
				"top":$(window).scrollTop(),
				"width":"100%",
				"z-index":"100"
			}).bind("blur",function(){
				$(this).focus();
			}).focus();
		}else{
			document.body.appendChild($("#" + id)[0]);
			$("#" + id).css("top",$(window).scrollTop()).show().focus();
		}
	}else{
		$("#" + id).hide();
		$("body").css("overflow","auto").focusFirst();
	}
};