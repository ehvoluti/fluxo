$.fn.description = function(){
	$(this).each(function(){
		if($(this).attr("description") !== undefined && $(this).attr("description") !== null && $(this).attr("description").length > 0){
			var e_chave = this;
			var e_body = e_chave;
			while(!$(e_body).is("body")){
				e_body = $(e_body).parent();
			}
			var e_descricao = $("#" + $(this).attr("description"), e_body); //$.parentSelector("#" + $(this).attr("description"));
			var e_quantidade = $("#" + $(this).attr("quantidade"), e_body); //$.parentSelector("#" + $(this).attr("quantidade"));
			var filter = strtoobj($(this).attr("filter"), true);
			var data = ({
				table: $(this).attr("identify"),
				key: $(this).val()
			});
			$.extend(data, filter);
			$(e_descricao).val("");
			if(data.key.length > 0){
				$.ajax({
					async: false,
					url: "../ajax/description.php",
					data: data,
					dataType: "html",
					success: function(html){
						html = trim(html);
						var chave = trim(html.substr(0, 20));
						var descricao = trim(html.substr(20, 120));
						var quantidade = trim(html.substr(140, 15));
						$(e_chave).val(chave);
						//$(e_descricao).off(); // Comentado para corrigir OS 3849
						$(e_descricao).val(descricao);
						if(quantidade.length > 0 && (ean_quantidade !== undefined)){
							ean_quantidade = quantidade;
						}
						if($(e_quantidade).length > 0 && quantidade.length > 0){
							$(e_quantidade).val(quantidade);
						}
					}
				});
			}
		}
	});
	return this;
};