$(document).bind("ready", function(){
	$("#gerar_lancamento,#gerar_analitico").change(function(){
		if($("#gerar_lancamento").checked() || $("#gerar_analitico").checked()){
			$("#data_lancamento").disabled(false);
		}else{
			$("#data_lancamento").disabled(true);
		}
	});
	$("#gerar_estoque").change(function(){
		if($(this).checked()){
			$("#box-opcoes input:not(#gerar_estoque)").checked(false).disabled(true);
			$("#data_lancamento").val("M");
		}else{
			$("#box-opcoes input:not(#gerar_estoque)").disabled(false);
			$("#data_lancamento").val("");
		}
	});
	$("#data_lancamento").change(function(){
		if($(this).val() == "M"){
			$("#box-opcoes input:not(#gerar_estoque)").checked(false).disabled(true);
			$("#gerar_estoque").checked(true);
		}else{
			$("#box-opcoes input:not(#gerar_estoque)").disabled(false);
			$("#gerar_estoque").checked(false);
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
	}
	if($("#data_lancamento").val().length == 0 && $("#gerar_lancamento,#gerar_analitico,#gerar_estoque").checked()){
		$.messageBox({
			type: "error",
			text: "Informe o filtro data.",
			focusOnClose: $("#data_lancamento")
		});
		return false;
	}else if($("#dtmovto1").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a data inicial.",
			focusOnClose: $("#dtmovto1")
		});
		return false;
	}else if($("#dtmovto2").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a data final.",
			focusOnClose: $("#dtmovto2")
		});
		return false;
	}else if(!$("#gerar_lancamento").is(":checked") && !$("#gerar_fiscal").is(":checked") && !$("#gerar_analitico").is(":checked") && !$("#gerar_estoque").is(":checked")){
		$.messageBox({
			type: "error",
			text: "Informe pelo menos uma op&ccedil;&atilde;o de gera&ccedil;&atilde;o do arquivo."
		});
		return false;
	}
	$.ajaxProgress({
		url: "../ajax/prosoft_gerar.php",
		data: ({
			codestabelec: $("#codestabelec").val(),
			dtmovto1: $("#dtmovto1").val(),
			dtmovto2: $("#dtmovto2").val(),
			gerar_lancamento: $("#gerar_lancamento").is(":checked"),
			gerar_fiscal: $("#gerar_fiscal").is(":checked"),
			gerar_forca: $("#gerar_forca").is(":checked"),
			gerar_estoque: $("#gerar_estoque").is(":checked"),
			gerar_analitico: $("#gerar_analitico").is(":checked"),
			data_lancamento: $("#data_lancamento").val(),
			identifiestab: $("#identifiestab").val(),
			codbanco: $("#codbanco").val()
		}),
		success: function(html){
			extractScript(html);
		}
	});
}