$.fn.hint = function(){
	$(this).filter("[hint]").each(function(){
		if($(this).is(":checkbox,:radio")){
			$(this).bind("mouseenter", function(){
				$(this).hint_show();
			}).bind("mouseout", function(){
				$.hint_hide();
			});
		}
		$(this).bind("focus", function(){
			$(this).hint_show();
		}).bind("blur", function(){
			$.hint_hide();
		});
	});
	return this;
};

$.fn.hint_show = function(){
	$.hint_hide();
	var div = document.createElement("div");
	$("#divScreen").get(0).appendChild(div);
	$(div).attr({
		"__hint": true
	}).html($(this).attr("hint")).slideDown("fast");
};

$.hint_hide = function(){
	$("[__hint]").animate({
		"opacity": "hide"
	}, "fast", function(){
		$(this).remove();
	});
};
