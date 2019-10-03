$.pressEnter = function(settings){
	settings = $.extend({
		button:$(""),
		elements:$("")
	},settings);
	
	$(settings.elements).bind("keypress",function(e){
		if(e.keyCode == 13){
			$(settings.button).click();
		}
	});
}