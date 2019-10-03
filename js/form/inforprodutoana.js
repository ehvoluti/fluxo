
$(document).bind("ready", function(){
	$("#div_filtro").height("auto");

	$("#div_filtro *").filter("input:text,select").bind("keydown",function(e){
		if(keycode(e) == 13){
			filtrar();
		}
	});
}).bind("keydown",function(e){
	if(e.keyCode == 27){
		limpar_filtro();
		$("#tab_filtro").click();
	}
});

var ajax_buscarlista = null;
function buscarlista(){
	if(ajax_buscarlista == null && parseInt($("#listaproduto")[0].scrollHeight) - parseInt($("#listaproduto")[0].scrollTop) - 335 < 100){
		ajax_buscarlista = $.ajax({
			url: "../ajax/infoprodutoana_buscarlista.php",
			data: ({
				query: $("#query").val(),
				codestabelec: $("#codestabelec").val(),
				offset: $("#listaproduto option").length
			}),
			success: function(html){
				ajax_buscarlista = null;
				var scrolltop = $("#listaproduto").scrollTop();
				extractScript(html);
				$("#listaproduto").scrollTop(scrolltop);
			}
		});
	}
}

var buscardados_timeout;
function buscardados(){
	clearTimeout(buscardados_timeout);
	buscardados_timeout = setTimeout("buscardados_()", 500);
}

function buscardados_(){
	var option = $("#listaproduto option:selected");
	$.ajax({
		async: false,
		url: "../ajax/infoprodutoana_buscardados.php",
		data: ({
			codestabelec: $(option).attr("codestabelec"),
			codproduto: $(option).attr("codproduto"),
			codfornec: $("#codfornec").val()
		}),
		dataType: "html",
		success: function(html){
			extractScript(html);
		}
	});
}

function filtrar(){
	if($("#descricaofiscal").val().length > 0 && $("#tipodescricao").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o tipo de pesquisa pela descri&ccedil;&atilde;o do produto.",
			focusOnClose: $("#tipodescricao")
		});
		return false;
	}
	if($("#codestabelec").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o estabelecimento.",
			focusOnClose: $("#tipodescricao")
		});
		return false;
	}

	$("#listaproduto option").remove();

	$.loading(true);
	$.ajax({
		url: "../ajax/infoprodutoana_buscarlista.php",
		data: ({
			offset: 0,
			codestabelec: $("#codestabelec").val(),
			codproduto: $("#codproduto").val(),
			descricaofiscal: $("#descricaofiscal").val(),
			tipodescricao: $("#tipodescricao").val(),
			codfornec: $("#codfornec").val(),
			coddepto: $("#coddepto").val(),
			codgrupo: $("#codgrupo").val(),
			codsubgrupo: $("#codsubgrupo").val(),
			codfamilia: $("#codfamilia").val(),
			codsimilar: $("#codsimilar").val(),
			custopreco: $("#custopreco").val(),
			numpedido: $("#numpedido").val(),
			numnotafis: $("#numnotafis").val(),
			serie: $("#serie").val(),
			dtentrada1: $("#dtentrada1").val(),
			dtentrada2: $("#dtentrada2").val(),
			dtdigitacao1: $("#dtdigitacao1").val(),
			dtdigitacao2: $("#dtdigitacao2").val(),
			foralinha: $("#foralinha").val(),
			sugcompra: $("#filtro_sugcompra").val(),
			reffornec: $("#reffornec").val(),
			estoque: $("#estoque").val()
		}),
		success: function(html){
			$.loading(false);
			extractScript(html);
			if($("#listaproduto option").length > 0){
				$("#tab_produto").click();
				$("#listaproduto option:first").attr("selected", true);
				buscardados_();
			}else{
				$.messageBox({
					type: "alert",
					text: "Nenhum produto foi encontrado no filtro informado."
				});
			}
		}
	});
}

function limpar_filtro(){
	$("#div_filtro").find("input:text,select").not("#codestabelec,#tipodescricao").val("");
}

function ultimascompras(codproduto){
	$.ajax({
		async: false,
		url: "../ajax/infoprodutoana_ultimascompras.php",
		data: ({
			codestabelec: $("#codestabelec").val(),
			codproduto: codproduto
		}),
		success: function(html){
			$("#grd_ultimascompras").html(html);
		}
	});
}

function vendamensal(codestabelec, codproduto){
	$.ajax({
		async: false,
		url: "../ajax/infoprodutoana_vendamensal.php",
		data: ({
			codestabelec: codestabelec,
			codproduto: codproduto
		}),
		success: function(html){
			$("#grd_vendamensal").html(html);
		}
	});
}