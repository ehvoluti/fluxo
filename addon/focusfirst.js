$.fn.focusFirst = function(){
	$(this).firstElement().focus().select();
	return this;
}