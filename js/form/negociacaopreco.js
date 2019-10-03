$(document).ready(function(){
	$("#btnitgravar, #btnFiltrar").disabled(true);
	$("#table_campoitem input").change(function(){
		negociacaopreco_sugestao();
	});
});

$.cadastro.after.carregar = function(){
	$("#btnitgravar, #btnFiltrar").disabled(true);
	$("#boxproduto").disabled(false);
	
	$("#tipoparceiro").change();
	$("#codparceiro").description();
	$("#boxproduto option").remove();
	$.ajax({
		url: "../ajax/negociacaopreco_itnegociacaopreco.php",
		data: {
			codnegociacaopreco: $("#codnegociacaopreco").val()
		},
		dataType: "html",
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});	
}

$.cadastro.after.inserir = function(){
	$("#btnitgravar, #btnFiltrar").disabled(false);		
}

$.cadastro.before.limpar = function(){
	$("#codnegociacaopreco,#descricao,#codestabelec,#operacao,#codparceiro,#tipoparceiro,#dtvigor,#desc_codparceiro,#table_campoitem input").val("");
	$("#boxproduto option").remove();
}

$.cadastro.after.alterar = function(){
	$("#btnitgravar, #btnFiltrar").disabled(false);
	$("#it_margemprat, #it_pmz, #it_precosugestaovrj").disabled(true);
	
}

function negociacaopreco_itempesquisar(){
	var codproduto = $("#boxproduto option:selected").attr("codproduto");
	var codestabelec = $("#codestabelec").val();
	var operacao = $("#operacao").val();

	$.loading(true);
	$.ajax({
		url: "../ajax/negociacaopreco_pesquisaitem.php",
		data: {
			"codproduto": codproduto,
			"codestabelec": codestabelec,
			"operacao": operacao
		},
		dataType: "html",
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function negociacaopreco_itemgravar(){
	var codproduto = $("#boxproduto option:selected").attr("codproduto");
	
	$.loading(true);
	$.ajax({
		url: "../ajax/itnegociacaopreco_gravar.php",
		data: {			
			codproduto: codproduto,
			codestabelec: $("#codestabelec").val(),						
			precovrj: $("#it_precovrj").val(),
			custotab: $("#it_custotab").val(),
			percdescto: $("#it_percdescto").val(),
			percacresc: $("#it_percacresc").val(),
			percfrete: $("#it_percfrete").val(),
			percseguro: $("#it_percseguro").val(),
			valdescto: $("#it_valdescto").val(),
			valacresc: $("#it_valacresc").val(),
			valfrete: $("#it_valfrete").val(),
			valseguro: $("#it_valseguro").val(),
			margemvrj: $("#it_margemvrj").val()
		},
		dataType: "html",
		success: function(html){
			$.loading(false);
			extractScript(html);			
		}
	});
}

function negociacaopreco_itemexcluir(codproduto){
	$.ajax({
		url: "../ajax/negociacaopreco_itnegociacaopreco.php",
		data: {
			"acao": "remover",
			"codproduto": codproduto
		},
		dataType: "html",
		success: function(html){
			extractScript(html);
		}
	});
}

function negociacaopreco_sugestao(){
	var codproduto = $("#boxproduto option:selected").attr("codproduto");
	
	$.ajax({
		url: "../ajax/negociacaopreco_buscarsugestao.php",
		data: {
			codproduto: codproduto,
			codestabelec: $("#codestabelec").val(),
			margemvrj: $("#it_margemvrj").val(),
			custotab: $("#it_custotab").val(),
			percdescto: $("#it_percdescto").val(),
			percacresc: $("#it_percacresc").val(),
			percfrete: $("#it_percfrete").val(),
			percseguro: $("#it_percseguro").val(),
			valdescto: $("#it_valdescto").val(),
			valacresc: $("#it_valacresc").val(),
			valfrete: $("#it_valfrete").val(),
			valseguro: $("#it_valseguro").val()
		},
		dataType: "html",
		success: function(html){
			extractScript(html);
		}
	});
}

function filtrar(){	
	$("#boxproduto option").remove();

	$.loading(true);
	$.ajax({
		url: "../ajax/negociacaopreco_buscarlista.php",
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
			reffornec: $("#reffornec").val(),
			numpedido: $("#numpedido").val(),
			numnotafis: $("#numnotafis").val(),
			serie: $("#serie").val(),
			dtentrada1: $("#dtentrada1").val(),
			dtentrada2: $("#dtentrada2").val(),
			dtdigitacao1: $("#dtdigitacao1").val(),
			dtdigitacao2: $("#dtdigitacao2").val(),
			foralinha: $("#foralinha").val(),
			custoalterado: $("#custoalterado").val(),
			marca: $("#marca").val(),
			itemcomp: $("#itemcomp").checked() ? "S" : "N"
		}),
		success: function(html){
			$.loading(false);
			extractScript(html);
			if($("#boxproduto option").length > 0){
				$("#tabProdutos").click();
				$("#boxproduto").disabled(false);
				$("#boxproduto option:first").attr("selected", true);
				$("#preco").focus();
				negociacaopreco_itempesquisar();
			}else{
				$.messageBox({
					type: "alert",
					text: "Nenhum produto foi encontrado no filtro informado."
				});
			}			
		}
	});
}