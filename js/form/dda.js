$(document).bind("ready", function(){
//	$(".button-upload").disabled(true);

	$("#codestabelec,#codbanco,#dtreconc").bind("change", function(){
		$(".button-upload").disabled(false);
		$("#codestabelec,#codbanco,#dtreconc").each(function(){if($(this).val().length == 0) $(".button-upload").disabled(true)});
	});

	$("input:file").bind("change", function(){
		var elements = $("[required]");
		for(var i = 0; i < $(elements).length; i++){
			var e = elements[i];
			if(trim($(e).val()).length === 0){
				$.messageBox({
					type: "error",
					text: "O campo <b>" + e.getAttribute("required") + "</b> &eacute; de preenchimento obrigat&oacute;rio.",
					buttons: ({
						"Ok": function(){
							$.messageBox("close");
							$(e).focus();
						}
					})
				});
				return false;
			}
		}
		$(this).upload({
			filename: "../temp/upload/dda/itau.dda",
			onComplete: function(){
				buscar();
			}
		});
	});
});

function buscar(){
	$.loading(true);
	$.ajax({
		url: "../ajax/dda_buscar.php",
		data:({
			codestabelec: $("#codestabelec").val()
		}),
		success: function(html){
			if(!$("#divDda").is(":visible")){
				$("#divDda").height("0px").show().animate({
					"height": "345px"
				}, "slow");
			}
			$("#divGrade").html(html);
			$.loading(false);
		}
	});
	return true;
}

function aceite(){
	$.loading(true);
	var arr_dda = [];

	$("#divdda input:checked").closest("[class=row]").find("td div[codigodebarras]").each(function(){
		//arr_dda.push("{\"iddda\":\"" + $(this).attr("iddda") + "\",\"codlancto\":\"" + $(this).attr("codlancto") +"\",\"dtvencto\":\"" + $(this).attr("dtvencto") + "\",\"codigodebarras\":\"" + $(this).attr("codigodebarras") +"\"}");
		arr_dda.push("{\"iddda\":\"" + $(this).attr("iddda") + "\",\"codlancto\":\"" + $(this).attr("codlancto") +"\",\"dtvencto\":\"" + $(this).attr("dtvencto") + "\",\"codigodebarras\":\"" + $(this).attr("codigodebarras") + "\",\"dda_vencto\":\"" + $(this).attr("dda_vencto") +"\"}");
	});

	$.ajax({
		url: "../ajax/dda_aceite.php",
		data:({
			arr_dda:arr_dda
		}),
		success: function(html){
			extractScript(html);
			$.loading(false);
			buscar();
		}
	});
}

function buscarlancamento(elem){
	$.messageBox({
		type: "info",
		title: "Informe o código do lançamento",
		text: $("#divIncluir"),
		buttons: ({
			"Continuar": function(){
				$.messageBox("close");
				if($("#codlancto").val().length <= 0){
					return(false);
				}
				$.ajax({
					async: false,
					url: "../ajax/dda_incluir.php",
					data: ({
						codlancto: $("#codlancto").val(),
						desc_codlancto: $("#desc_codlancto").val(),
						iddda: $(elem).attr("iddda"),
						dtvencto: $(elem).attr("dtvencto"),
						codigodebarras: $(elem).attr("codigodebarras")
					}),
					success: function(html){
						extractScript(html);
					}
				});
			},
			"Cancelar": function(){
				$.messageBox("close");
			}
		})
	});
	$('#codlancto').locate({table:$('#codlancto').attr('identify'),filter:$('#codlancto').attr('filter')});
}
