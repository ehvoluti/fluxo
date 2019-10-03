var fastMessage_n = 0;
$.fastMessage = function(text){
	var div = document.createElement("div");
	var last_message = $("[__fastMessage]:first");	
	if($(last_message).length == 0){
		$("#divScreen")[0].appendChild(div);
	}else{
		$("#divScreen")[0].insertBefore(div,$(last_message)[0]);
	}
	
	$(div).attr({
		"__fastMessage":fastMessage_n
	}).css({
		"background-color":"RGBA(220,240,200,0.5)",
		"border":"1px solid #594",
		"color":"#454",
		"display":"none",
		"font-size":"12px",
		"margin-top":"5px",
		"padding":"10px",
		"text-align":"center",
		"width":"876px"
	}).html(text).slideDown("fast");
	
	setTimeout("$(\"[__fastMessage='" + fastMessage_n + "']\").fastMessageRemove()",(text.length * 100));
	
	fastMessage_n++;
}

$.fn.fastMessageRemove = function(){
	$(this).fadeOut("normal",function(){
		$(this).remove();
	});
	return this;
}