$.fn.submit = function(){
	$(this).each(function(){
		this.submit(); 
	});
	return this;
}