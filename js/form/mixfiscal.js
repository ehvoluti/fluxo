$(document).bind("ready", function(){
	contadores();
});

function contadores(){
	$.ajax({
		url: "../ajax/mixfiscal_contadores.php",
		success: function(result){
			extractScript(result);
		}
	});
}

function sanear(){
	if(!verificar_sanear()){
		return false;
	}
	$.ajaxProgress({
		url: "../ajax/mixfiscal_sanear.php",
		data: {
			codestabelec: $("#codestabelec").val(),
			tipocontribuinte: $("#tipocontribuinte").val(),
			codproduto: $("#codproduto").val()
		},
		success: function(result){
			extractScript(result);
			contadores();
		}
	});
}

function verificar_sanear(){
	if($("#codestabelec").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe o estabelecimento desejado para consulta.",
			focusOnClose: $("#codestabelec")
		});
		return false;
	}
	if($("#tipocontribuinte").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe o tipo de contribuinte desejado para consulta.",
			focusOnClose: $("#tipocontribuinte")
		});
		return false;
	}
	return true;
}