$(document).bind("ready", function(){
	$("#codcliente").bind("keypress", function(e){
		if(e.keyCode == 13){
			imprimir();
		}
	});
});

function imprimir(){
	$.loading(true);
	$.ajax({
		sync: false,
		url: "../ajax/impetiqcliente_tipoimpressao.php",
		data: ({
			codetiqcliente: $("#codetiqcliente").val()
		}),
		success: function(html){
			arr_cliente = JSON.stringify($("input:checkbox[codcliente]:checked").map(function(){return $(this).attr("codcliente"); }).get());

			if(html == "1"){
				window.open("../ajax/impetiqcliente_imprimir.php?codetiqcliente=" + $("#codetiqcliente").val() + "&qtdeetiqueta=" + $("#qtdeetiqueta").val() + "&arr_cliente=" + arr_cliente);
			}else{
				$.ajax({
					url: "../ajax/impetiqcliente_imprimir.php",
					data: ({
						codetiqcliente: $("#codetiqcliente").val(),
						qtdeetiqueta: $("#qtdeetiqueta").val(),
						arr_cliente: arr_cliente
					}),
					dataType: "html",
					success: function(html){
						$.loading(false);
						extractScript(html);
					}
				});
			}

			$.loading(false);
		}
	});
}

function filtrar(){
	$.loading(true);
	if($("#impressao:not(:visible)").length > 0){
		$("#impressao").animate({
			height: "toggle"
		}, "slow");
	}
	$("#gradeImpressao").html("<label>Carregando...</label>");
	$.ajax({
		url: "../ajax/impetiqcliente_filtrar.php",
		data: ({
			codcliente: $("#codcliente").val(),
			codstatus: $("#codstatus").val(),
			codclassif: $("#codclassif").val(),
			codinteresse: $("#codinteresse").val()
		}),
		dataType: "html",
		success: function(html){
			$.loading(false);
			$("#gradeImpressao").html(html);
		}
	});
}