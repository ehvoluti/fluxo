$.fn.checked = function(bool){
	if(bool === undefined){
		return $(this).is(":checked");
	}else{
		$(this).each(function(){
			this.checked = bool;
		});
		return this;
	}
};