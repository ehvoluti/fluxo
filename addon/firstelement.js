$.fn.firstElement = function(){
	return $(this).find("input:visible:enabled:first[tabindex!='-1'],select:visible:enabled:first[tabindex!='-1'],textarea:visible:enabled:first[tabindex!='-1']").first();
}