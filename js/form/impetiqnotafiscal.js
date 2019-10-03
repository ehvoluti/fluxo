$(document).bind("ready", function(){
	$("#idnotafiscal").bind("keypress", function(e){
		if(e.keyCode == 13){
			imprimir();
		}
	});
});

function imprimir(){
	$.loading(true);
	$.ajax({
		sync: false,
		url: "../ajax/impetiqnotafiscal_tipoimpressao.php",
		data: ({
			codetiqnotafiscal: $("#codetiqnotafiscal").val()
		}),
		success: function(html){
			var arr_idnotafiscal = $("input:checkbox[idnotafiscal]:checked").map(function(){return $(this).attr("idnotafiscal"); }).get();
			if(html == "1"){
				window.open("../ajax/impetiqnotafiscal_imprimir.php?codetiqnotafiscal=" + $("#codetiqnotafiscal").val() + "&arr_idnotafiscal=" + arr_idnotafiscal);
			}else{
				$.ajax({
					url: "../ajax/impetiqnotafiscal_imprimir.php",
					data: ({
						codetiqnotafiscal: $("#codetiqnotafiscal").val(),
						qtdeetiqueta: $("#qtdeetiqueta").val(),
						arr_idnotafiscal: arr_idnotafiscal
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
	var data = $("#filtro").find("input:text,select").map(function(){
		return $(this).attr("id") + "=" + $(this).val();
	}).get().join("&");
	$.ajax({
		url: "../ajax/impetiqnotafiscal_filtrar.php",
		data:data,
		dataType: "html",
		success: function(html){
			$.loading(false);
			$("#gradeImpressao").html(html);
		}
	});
}