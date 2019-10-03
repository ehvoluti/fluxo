$.fn.sortList = function(){
	$(this).each(function(){
		var list = $($(this).find("li").get().reverse());
		$(list).each(function(){
			var sorting = this;
			$(list).each(function(inner){
				if($(this).text().localeCompare($(sorting).text()) > 0){
					this.parentNode.insertBefore(sorting.parentNode.removeChild(sorting),this);
				}
			});
		});
	});
	
	// Repete o processo novamente (nao sei proque, mas orde errado as vezes quando executa apenas uma vez)
	$(this).each(function(){
		var list = $($(this).find("li").get().reverse());
		$(list).each(function(){
			var sorting = this;
			$(list).each(function(inner){
				if($(this).text().localeCompare($(sorting).text()) > 0){
					this.parentNode.insertBefore(sorting.parentNode.removeChild(sorting),this);
				}
			});
		});
	});
	
	return this;
}