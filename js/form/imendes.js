$(document).bind("ready", function(){
	contadores();
});

function contadores(){
	$.ajax({
		url: "../ajax/imendes_contadores.php",
		success: function(result){
			extractScript(result);
		}
	});
}

function sanear(completo){
	if(!verificar_sanear()){
		return false;
	}
	$.ajaxProgress({
		url: "../ajax/imendes_sanear.php",
		data: ({
			codestabelec: $("#codestabelec").val(),
			completo: completo
		}),
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
	return true;
}

function verificar_imendes(){
	if(!verificar_sanear()){
		return false;
	}
	$.loading(true);
	$.ajax({
		url: "../ajax/imendes_verificar.php",
		data: ({
			codestabelec: $("#codestabelec").val()
		}),
		complete: function(){
			$.loading(false);
		},
		success: function(result){
			extractScript(result);
		}
	});
}