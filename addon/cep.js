// Busca o endereco do CEP informado
$.fn.cep = function(settings){
	settings = $.extend({
		bairro:null,
		cidade:null,
		endereco:null,
		uf:null,
        pais:null
	},settings);

	$(settings.pais).add(settings.bairro).add(settings.cidade).add(settings.endereco).add(settings.uf).val("");

	$(this).each(function(){
		var cep_id = $(this).attr("id");
		$(this).after("<img src='../img/loading_16.gif' style='margin-left:5px; position:absolute' cep_id='" + cep_id + "'>");
		$.ajax({
			url:"../ajax/cep_buscar.php",
			data:({
				cep:$(this).val()
			}),
			dataType:"html",
			success:function(html){
				$("[cep_id='" + cep_id + "']").remove();
				var arr = html.split("|");
				var uf = arr[0];
				var cidade = arr[1];
				var bairro = arr[2];
				var endereco = arr[3];
				$.ajax({
					url:"../ajax/cep_cidade.php",
					data:({
						cidade:cidade,
                                                uf:uf
					}),
					dataType:"html",
					success:function(html){
						var cidade = html;
						$(settings.bairro).val(bairro.toUpperCase());
						$(settings.endereco).val(endereco.toUpperCase());
						$(settings.uf).val(uf);
                        $(settings.pais).val(uf.length > 0 ? "01058" : "");
						filterchild($(settings.uf).attr("id"),cidade);
						$(settings.cidade).val(cidade);
					}
				});
			}
		});
	});

	return this;
}