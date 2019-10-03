var mouse_estabelecimento = false;

$(document).bind("ready", function(){

	atualizar_estabelecimento();

	$("#arr_estabelecimento input:checkbox").bind("change", function(){
		atualizar_estabelecimento();
	});

	$("#selecionarestabelecimento").bind("click", function(){
		if($("#arr_estabelecimento").is(":visible")){
			esconder_estabelecimento();
		}else{
			exibir_estabelecimento();
		}
	});

	$("#selecionarestabelecimento, #arr_estabelecimento").bind("mouseenter", function(){
		mouse_estabelecimento = true;
	}).bind("mouseleave", function(){
		mouse_estabelecimento = false;
	});

	$("#menu_li li").bind("click", function(){
		var pagina = $("#" + $(this).attr("pagina"));

		$(".menu li").removeAttr("selected");
		$(this).attr("selected", true);

		$("div.pagina:visible").stop().fadeOut("fast");
		$(pagina).stop().fadeIn("fast");

		if(!$(pagina).is("[loaded]")){
			$(pagina).find("canvas").each(function(){
				$(this).attr({
					"height": $(this).parent().height(),
					"width": $(this).parent().width()
				});
			});

			$.loading(true);
			$.ajax({
				url: "../ajax/bi_" + $(this).attr("pagina").replace("pag_", "") + ".php",
				data: ({
					arr_codestabelec: $("#arr_estabelecimento [codestabelec]:checked").map(function(){
						return $(this).attr("codestabelec");
					}).get(),
					dtinicial: $("#dtinicial").val(),
					dtfinal: $("#dtfinal").val(),
					foralinha: $("#foralinha").val(),
					tipovenda: $("#tipovenda").val(),
					coddepto: $("#coddepto").val(),
				}),
				success: function(result){
					$.loading(false);
					extractScript(result);
				}
			});

			$(pagina).attr("loaded", true);
		}
	});

	// Ajusta a ordem e quais modulos devem ser visiveis
	var categoria = null;
	$("[idbi]").each(function(){
		categoria = $(this).parents("[categoria]").attr("categoria");
		if(arr_biconfig[categoria] === undefined || !in_array($(this).attr("idbi"), arr_biconfig[categoria])){
			$(this).remove();
		}
	});
	for(categoria in arr_biconfig){
		for(var i = 0; i < arr_biconfig[categoria].length; i++){
			var elem = $("[idbi='" + arr_biconfig[categoria][i] + "']").get(0);
			if($(elem).length > 0){
				$(elem).parent().get(0).appendChild(elem);
			}
		}
	}

	$("#inifil_dtini").change(function(){
		setTimeout('$("#dtinicial").val($("#inifil_dtini").val())',1);
	});
	$("#inifil_dtfim").change(function(){
		setTimeout('$("#dtfinal").val($("#inifil_dtfim").val())',1);
	});
	$("#inifil_foralinha").change(function(){
		$("#foralinha").val($("#inifil_foralinha").val());
	});
	$("#inifil_tipovenda").change(function(){
		$("#tipovenda").val($("#inifil_tipovenda").val());
	});
	$("#inifil_coddepto").change(function(){
		$("#coddepto").val($("#inifil_coddepto").val());
	});
	$("#inifil_codestabelec").change(function(){
		$("#arr_estabelecimento [type=checkbox]").removeAttr("checked");
		$(this).each(function(){
			$("#estabelecimento_"+$(this).val()).checked(true);
		});
		atualizar_estabelecimento();
	});

}).bind("click", function(){
	if($("#arr_estabelecimento").is(":visible") && !mouse_estabelecimento){
		esconder_estabelecimento();
	}
});

function atualizar_estabelecimento(){
	var texto = null;
	var n = $("#arr_estabelecimento input:checked[codestabelec]").length;
	switch(n){
		case  0:
			texto = "Nenhum estabelecimento selecionado";
			break;
		case  1:
			texto = "Um estabelecimento selecionado";
			break;
		default:
			texto = n + " estabelecimentos selecionados";
			break;
	}
	$("#selecionarestabelecimento").html(texto);
}

function filtromais(){
	$("#filtro-mais-conteudo").stop().toggle("fast");
}

function esconder_estabelecimento(){
	$("#arr_estabelecimento").stop().animate({
		"height": "hide"
	}, "fast");
}

function exibir_estabelecimento(){
	$("#arr_estabelecimento").stop().animate({
		"height": "show"
	}, "fast");
}

function recarregar(){
	$(".pagina").removeAttr("loaded");
	if($("#dtinicial").val().length > 0 && $("#dtfinal").val().length > 0){
		if($(".menu li[selected]").length > 0){
			$(".menu li[selected]").trigger("click");
		}else{
			$("[pagina=pag_venda]").trigger("click");
		}
	}
}