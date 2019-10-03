$.fn.refreshComboBox = function(){
	$(this).each(function(){
		var value = $(this).val();
		if($(this).attr("parentId")){
			filterchild($(this).attr("parentId"), value);
		}else{
			$(this).html("<option value=''>Carregando...</option>");
			var element = this;
			$.ajax({
				async: false,
				url: "../ajax/combobox.php",
				data: {
					table: $(this).attr("table"),
					value: value,
					filter: $(this).attr("filter")

				},
				dataType: "html",
				success: function(html){
					$(element).html(html).val(value);
					if($(this).is("[multiple]")){
						$(this).multipleSelect("refresh");
					}
				}
			});
		}
	});
	return this;
};