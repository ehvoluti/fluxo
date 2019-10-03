$(document).bind("ready", function(){
	$("#gerarinventario").click(function(){
		if($("#gerarinventario").is(':checked')){
			$("#datainventario").attr('style', 'display:block');
		}else{
			$("#datainventario").attr('style', 'display:none');
		}
	});
	if(!filtrotecnico){
		$("#filtrotecnico").hide();
	}
});

function gerar(){
	if($("#codestabelec").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o estabelecimento.",
			focusOnClose: $("#codestabelec")
		});
		return false;
	}else if($("#mes").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a m&ecirc;s de movimento.",
			focusOnClose: $("#mes")
		});
		return false;
	}else if($("#ano").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o ano de movimento.",
			focusOnClose: $("#ano")
		});
		return false;
	}
	var open = "";
	open += "&registro=" + ($("input[name=tipolivro]:checked").val());
	window.open("../form/livrofiscal_imprimir.php?codestabelec=" + $("#codestabelec").val() + "&livro=" + $("#livro").val() + "&mes=" + $("#mes").val() + "&ano=" + $("#ano").val() + open);
}


function definitivo(){
	$.messageBox({
		type: "alert",
		title: "",
		text: "Tem certeza que deseja atualizar os contadores fiscais e gerar o arquivo definitivo?",
		buttons: ({
			"Sim": function(){
				$.loading(true);
				$.ajax({
					url: "../ajax/arquivo_remover.php",
					data: {
						codarquivo: codarquivo
					},
					success: function(result){
						$.loading(false);
						extractScript(result);
					}
				});
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}