$(document).bind("ready", function(){
	$("#file_cielo").bind("change", function(){
		$(this).upload({
			filename: "../temp/cielo.txt",
			onComplete: function(){
				processar_arquivo();
			}
		});
	});

	$("#btn_processar_lancamento").disabled(true);
	recalcular_arquivo();
});

function alterar_lancamento(docliquidacao, codlancto){
	$.ajax({
		url: "../ajax/cielo_alterar_lancamento.php",
		data: {
			docliquidacao: docliquidacao,
			codlancto: codlancto
		},
		success: function(result){
			extractScript(result);
		}
	});
}

// Tipo pode ser: 0: todos do arquivo, 1: todos a serem liquidados, 2: todos nao encontrados
function listar_arquivo(tipo){
	$.loading(true);
	$.ajax({
		url: "../ajax/cielo_listar_arquivo.php",
		data: {
			tipo: tipo
		},
		success: function(result){
			$.loading(false);
			$("#listalancamento").html(result);
			$.modalWindow({
				content: $("#modal_listalancamento"),
				title: "Lan&ccedil;amentos " + (tipo === 0 ? "no arquivo" : (tipo === 1 ? "a serem liquidados" : "n&atilde;o encontrados")),
				width: "500px",
				hint: "Os lan&ccedil;amentos vinculados s&atilde;o gravados automaticamente."
			});
			$("#listalancamento input[identify]").bind("change", function(){
				alterar_lancamento($(this).attr("docliquidacao"), $(this).val());
			});
			$("#listalancamento_imprimir").unbind("click").bind("click", function(){
				window.open("../ajax/cielo_imprimir.php?tipo=" + tipo);
			});
		}
	});
}

function processar_arquivo(){
	$.loading(true);
	$.ajax({
		url: "../ajax/cielo_processar_arquivo.php",
		success: function(result){
			$.loading(false);
			extractScript(result);
		}
	});
}

function processar_lancamento(){
	$.ajaxProgress({
		url: "../ajax/cielo_processar_lancamento.php",
		success: function(result){
			extractScript(result);
		}
	});
}

function recalcular_arquivo(){
	$.ajax({
		url: "../ajax/cielo_recalcular_arquivo.php",
		success: function(result){
			extractScript(result);
		}
	});
}