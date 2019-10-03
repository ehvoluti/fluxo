$(document).bind("ready",function(){
	$("#tipogeracao").change(function(){
		if($(this).val() == "F"){
			$("#data_lancamento").disabled(false);
			$("#codbanco").disabled(false);
		}
		if($(this).val() == "N"){
			$("#data_lancamento").val("").disabled(true);
			$("#codbanco").val("").disabled(true);
		}
	});
});

function gerar(){
	if($("#codestabelec").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o estabelecimento.",
			focusOnClose: $("#codestabelec")
		});
		return false;
	}else if($("#dtinicial").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a data inicial.",
			focusOnClose: $("#dtinicial")
		});
		return false;
	}else if($("#dtfinal").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a data final.",
			focusOnClose: $("#dtfinal")
		});
		return false;
	}
	$.ajaxProgress({
		url: "../ajax/dominio_gerar.php",
		data: ({
			codestabelec: $("#codestabelec").val(),
			dtinicial: $("#dtinicial").val(),
			dtfinal: $("#dtfinal").val(),
			codbanco: $("#codbanco").val(),
			data_lancamento: $("#data_lancamento").val(),
			gera_favorecido: $("#gera_favorecido").is(":checked") ? "S" : "N",
			identificador: $("#identificador").val(),
			tipogeracao: $("#tipogeracao").val()
		}),
		success: function(html){
			extractScript(html);
		}
	});
}