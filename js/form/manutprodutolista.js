var pagina = 1;
var limit = 30;
var pagtotal = 1;


$(document).bind("ready", function(){
	manutproduto_listar();

	$("#pag-prox").click(function(){
		if((pagina + 1) <= pagtotal){
			pagina += 1;
			manutproduto_listar();
		}
	});

	$("#pag-ante").click(function(){
		pagina -= 1;
		if(pagina < 1){
			pagina = 1;
		}
		manutproduto_listar()
	});

	$("#limit").change(function(){
		pagina = 1;
		manutproduto_listar();
	});

	$("#pag-atual").change(function(){
		pagina = $("#pag-atual").val();
		manutproduto_listar();
	});
});

function manutproduto_listar(){
	limit = $("#limit").val();
	$("#pag-atual").val(pagina);

	$.ajax({
		url: "../ajax/manutprodutolista_listar.php",
		type: "POST",
		data: ({
			pagina: pagina,
			limit: limit,
			codproduto: $("#codproduto").val(),
			codestabelec: $("#codestabelec").val(),
			coddepto: $("#coddepto").val(),
			codgrupo: $("#codgrupo").val(),
			codsubgrupo: $("#codsubgrupo").val(),
			codfamilia: $("#codfamilia").val(),
			codmarca: $("#codmarca").val(),
			idncm: $("#idncm").val(),
			codsimilar: $("#codsimilar").val(),
			descricao: $("#descricao").val()
		}),
		success: function(html){
			$("#grid-produto").html(html);

			fixa_topogrid("#grid-produto");

			$("[coluna]").bind("focus", function(){
				$(this).attr("val", $(this).text());
			});

			$("[coluna]").bind("blur", function(){
				if($(this).text() !== $(this).attr("val")){
					$.ajax({
						url: "../ajax/manutprodutolista_gravar.php",
						type: "POST",
						data: ({
							coluna: $(this).attr("coluna"),
							codproduto: $(this).attr("codproduto"),
							valor: $(this).text()
						}),
						success: function(result){
							extractScript(result);
						}
					});
				}
			});

			$("[coluna=descricaofiscal]").bind("keypress", function(e){
				if($(this).text().length >= param_descricao){
					e.preventDefault();
				}

				if(e.keyCode == 13){
					e.preventDefault();
					$(this).parent().parent().next().find("[coluna]").focus();
				}
			});

			$("[coluna=descricao]").bind("keypress", function(e){
				if($(this).text().length >= param_descricaofiscal){
					e.preventDefault();
				}


				if(e.keyCode == 13){
					e.preventDefault();
					$(this).parents("tr").next().find("[coluna=descricaofiscal]").focus();
				}
			});
		}
	});
}

function filtro(){
	$.modalWindow({
		title: "Filtro",
		content: $("#modal-filtro"),
		width: "720px",
		height: "250px",
		closeButton: true
	});
}
