$.fn.center = function(settings){
	settings = $.extend({
		left:true, // Alinhar horizontalmente
		top:false, // Alinhar verticalmente
		onResize:true // Sempre deixar alinhado quando redimensionar a tela
	},settings);
	
	$(this).each(function(){
		var left = (parseInt($("body").width()) / 2) - (parseInt($(this).css("width")) / 2);
		
		// Se for centralizar a tela, deve adicionar mais um pouco por causa do menu
		if($(this).attr("id") == "divScreen" && $("[screenMenu]").length > 0 && left < $("[screenMenu]").width() + 10){
			left = $("[screenMenu]").width() + 15;
		}
		
		$(this).css("position","absolute");
		if(settings.left){
			$(this).css("left",left);
			if(parseInt($(this).css("left")) < 0){
				$(this).css("left","0px");
			}
		}
		if(settings.top){
			var wheight = $("body").height();
			if(!(wheight > 0)){
				wheight = $(document).height();
			}
			var top = Math.floor(parseInt(wheight) / 2 - parseInt($(this).css("height")) / 2);
			top = (top < 0 ? 0 : top);
			$(this).css("top",top);
		}
		
		if(settings.onResize){
			var e = $(this);
			settings.onResize = false;
			$(window).bind("resize",function(){
				e.center(settings);
			});
		}
	});
	return this;
}