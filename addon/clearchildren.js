$.fn.clearChildren = function(){
	$(this).each(function(){
		$(this).find("input:not(:button,:checkbox), select, textarea").val("");
		$(this).find("input:checkbox").attr("checked", false);
		$(this).find("[description]").each(function(){
			$("#" + $(this).attr("description")).val("");
		});
	});
	return this;
};