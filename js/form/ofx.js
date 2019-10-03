$(document).bind("ready", function(){
	$(".button-upload").disabled(true);

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
			filename: "../temp/upload/ofx/extrato.ofx",
			onComplete: function(){
				buscar();
			}
		});
	});
});

function buscar(){
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

	$.ajax({
		url: "../ajax/ofx_buscar.php",
		data: ({
			codestabelec: $("#codestabelec").val(),
			dtreconcini: $("#dtreconcini").val(),
			dtreconcfim: $("#dtreconcfim").val(),
			codbanco: $("#codbanco").val()
		}),
		success: function(html){
			$.loading(false);
			if(!$("#divOfx").is(":visible")){
				$("#divOfx").height("0px").show().animate({
					"height": "345px"
				}, "slow");
			}
			$("#divGrade").html(html);
//			somar_saldoatual();
		}
	});
	return true;
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
					url: "../ajax/ofx_incluir.php",
					data: ({
						codlancto: $("#codlancto").val(),
						desc_codlancto: $("#desc_codlancto").val(),
						idofx: $(elem).attr("idofx"),
						dtofx: $(elem).attr("dtofx")
					}),
					success: function(html){
						extractScript(html);
//						somar_saldoatual();
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

function reconciliar(){
	var arr_ofx = [];

	$("#divofx input:checked").parents("[class=row]").find("td div[dtofx]").each(function(){
		arr_ofx.push("{\"idofx\":\"" + $(this).attr("idofx") + "\",\"codlancto\":" + $(this).attr("codlancto") + ",\"dtofx\":\"" + $(this).attr("dtofx") +"\"}");
	});

	$.ajax({
		async: false,
		url: "../ajax/ofx_reconciliar.php",
		data: ({
			arr_ofx: arr_ofx
		}),
		success: function(html){
			extractScript(html);
//			somar_saldoatual();
		}
	});
}

function somar_saldoatual(){
	var saldo = parseFloat($("#saldoatual").attr("saldo").replace(",", "."));
	$("#saldoatual").val($("#saldoatual").attr("saldo"));

	$("#divgrade [type=checkbox][idofx]:checked").each(function(){
		if($(this).attr("pagrec") == "P" ){
			saldo -= parseFloat($(this).attr("valorliquido").replace(",", "."));
		}else{
			saldo += parseFloat($(this).attr("valorliquido").replace(",", "."));
		}
		$("#saldoatual").val(saldo.toFixed(2).replace(".",","));
	});
}

function verificar_lancamentos(idofx){
	var arr_codlancto = [];
	$("#divofx [idofx='"+idofx+"']").parents("tr").find("div[codlancto][idofx='"+idofx+"']").each(function(){
		arr_codlancto.push($(this).attr("codlancto"));
	});

	$.ajax({
		async: false,
		url: "../ajax/ofx_verificar_lancamentos.php",
		data: ({
			arr_codlancto: arr_codlancto,
			valorofx: $("#divofx [idofx='"+idofx+"'][valorliquido]").attr("valorliquido"),
			idofx: idofx
		}),
		success: function(html){
			extractScript(html);
//			somar_saldoatual();
		}
	});
}