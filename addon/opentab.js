$.fn.openTab = function(){
	$(this).each(function(){
		$(this).parent().children().filter("[pageid]").each(function(){
			$(this).attr("class","taboff");
			$("#" + $(this).attr("pageid")).find("div:visible").each(function(){
				if($(this).css("overflow") == "auto"){
					$(this).attr("_scrollTop",$(this).scrollTop());
				}
			});
			$("#" + $(this).attr("pageid")).hide();
		});
		$(this).attr("class","tabon");
		var page = $("#" + $(this).attr("pageid"));
		$(page).show().focusFirst().find("div[_scrollTop]:visible").each(function(){
			$(this).scrollTop($(this).attr("_scrollTop"));
		});
        $.gear();
	});
	return this;
}