$.fn.refreshGridColor = function(){
	$(this).each(function(){
		var rows = $(this).find("tr.row:visible");

		$(rows).unbind("mouseover mouseout");

		$(rows).bind("mouseover", function(){
			$(this).css("background-color", $(this).parents(".grid").attr("CwRowColorHot"));
		});

		console.log($(rows).length);

		var i = 0;
		$(rows).each(function(){
			if(i % 2 === 0){
				$(this).bind("mouseout", function(){
					$(this).css("background-color", $(this).parents(".grid").attr("CwRowColor1"));
				}).css("background-color", $(this).parents(".grid").attr("CwRowColor1"));
			}else{
				$(this).bind("mouseout", function(){
					$(this).css("background-color", $(this).parents(".grid").attr("CwRowColor2"));
				}).css("background-color", $(this).parents(".grid").attr("CwRowColor2"));
			}
			i++;
		});
	});

	return this;
};