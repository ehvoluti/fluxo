$(document).bind("ready", function(){
	$("#btnCadExcluir").before("<input type=\"button\" style=\"margin-right:6px; width:130px\" id=\"btnGerarNfe\" value=\"NF-e Sefaz\" onClick=\"nfesefaz()\" title=\"Mostrar op&ccedil;&otilde;es para NF-e\">");
	$("#idnotafiscal").bind("change", function(){
		if(in_array($.cadastro.status(), [2, 3])){
			notafiscal_buscar();
		}
	});
	$("#totalicmssubst,#totalipi").bind("change", function(){
		if(in_array($.cadastro.status(), [2, 3])){
			var totalicmssubst = parseFloat($("#totalicmssubst").val().replace(",", "."));
			var totalipi = parseFloat($("#totalipi").val().replace(",", "."));
			$("#totalliquido").val(number_format((isNaN(totalicmssubst) ? 0 : totalicmssubst) + (isNaN(totalipi) ? 0 : totalipi)), 2, ",", "");
		}
	});
	$("#idnotafiscal,#totalbaseicms,#totalicms,#totalbaseicmssubst,#totalicmssubst,#totalipi").bind("change", function(){
		if(in_array($.cadastro.status(), [2, 3]) && $("#finalidade").val() != "3"){
			var arr_complemento = new Array();
			if($("#totalbaseicms").val().replace(",", ".") > 0){
				arr_complemento[arr_complemento.length] = "BASE DE ICMS"
			}
			if($("#totalicms").val().replace(",", ".") > 0){
				arr_complemento[arr_complemento.length] = "ICMS"
			}
			if($("#totalbaseicmssubst").val().replace(",", ".") > 0){
				arr_complemento[arr_complemento.length] = "BASE DE ICMS ST"
			}
			if($("#totalicmssubst").val().replace(",", ".") > 0){
				arr_complemento[arr_complemento.length] = "ICMS ST"
			}
			if($("#totalipi").val().replace(",", ".") > 0){
				arr_complemento[arr_complemento.length] = "IPI"
			}
			$("#observacao").val("EMISSAO DE NOTA FISCAL COMPLEMENTAR DE " + arr_complemento.join(", ") + " REFERENTE A NOTA FISCAL " + $("#notafiscal_numnotafis").val() + " SERIE " + $("#notafiscal_serie").val() + " EMITIDA EM " + $("#notafiscal_dtemissao").val());
		}
	});

	$("#justificativa_cancelamento").bind("keydown", function(){
		$("#btnConfirmar_cancelamento").disabled($(this).val().length < 15);
	});
	$("#xmotivo").disabled(true);

	$("#emissaopropria").change(function(){
		if($(this).val() == "N"){
			$("#numnotafis,#serie,#chavenfe").disabled(false);
			$("#status").val("A");
		}else{
			$("#numnotafis,#serie,#chavenfe").disabled(true);
			$("#status").val("P");
		}
	});
});

$.cadastro.after.alterar = function(){
	$("#codestabelec,#totalliquido,#idnotafiscal,#emissaopropria").disabled(true);
	$("body").focusFirst();
};

$.cadastro.after.inserir = function(){
	$("#codestabelec,#totalliquido,#idnotafiscal,#status").disabled(true);
	$("#dtemissao").val(date);
	$("#status").val("P").disabled(true);
	$("body").focusFirst();
};

$.cadastro.after.carregar = function(){
	var status = $("#status").val();
	if($("#status").val() == "P"){
		$("#xmotivo").css("color", "red");
	}

	if($("#status").val() == "A"){
		$("#xmotivo").css("color", "green");
	}

	if($("#status").val() == "C"){
		$("#xmotivo").css("color", "rgb(216,123,0)");
	}

	if($("#status").val() == "I"){
		$("#xmotivo").css("color", "blue");
	}

	if($("#status").val() == "D"){
		$("#xmotivo").css("color", "black");
	}

	if((status == "A" || status == "C" || status == "I" || status == "D") && $("#emissaopropria").val() == "S"){
		$("#btnCadAlterar").hide();
		$("#btnCadClonar").hide();
		$("#btnCadExcluir").hide();
	}else{
		$("#btnCadAlterar").show();
		$("#btnCadClonar").show();
		$("#btnCadExcluir").show();
	}
	if($("#emissaopropria").val() == "S"){
		$("#btnGerarNfe").show();
	}else{
		$("#btnGerarNfe").hide();
	}
};

function notafiscal_buscar(){
	if($("#idnotafiscal").val().length > 0 && $("#finalidade").val().length === 0){
		$.messageBox({
			type: "error",
			title: "",
			text: "Informe a finalidade da NF-e",
		});
		$("#idnotafiscal").val("");
		$("#desc_idnotafiscal").val("");
		return false;
	}

	$.ajax({
		async: false,
		url: "../ajax/notacomplemento_notafiscal.php",
		data: ({
			idnotafiscal: $("#idnotafiscal").val(),
			finalidade: $("#finalidade").val()
		}),
		success: function(html){
			extractScript(html);
		}
	});
}

function gerarnfe(){
	$.loading(true);
	$.ajax({
		url: "../ajax/notacomplemento_gerarnfe.php",
		data: ({
			idnotacomplemento: $("#idnotacomplemento").val()
		}),
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function habilita_estab(finalidade){
	if(finalidade === "3"){
		$("#codestabelec").disabled(false);
	}else{
		$("#codestabelec").disabled(true).val("");
	}
}

function nfesefaz(){
	$("#btnTransmitirNFe,#btnInutilizarNFe,#btnConsultarNFe,#btnCancelarNFe,#btnImprimirNFe,#btnEnviarEmailNFe").disabled(true);

	$("#btnTransmitirNFe").disabled($("#status").val() != "P");
	$("#btnConsultarNFe").disabled($("#status").val() == "D" || $("#status").val() == "I");
	$("#btnInutilizarNFe").disabled($("#status").val() != "P");
	$("#btnCancelarNFe").disabled($("#status").val() != "A");
	$("#btnImprimirNFe").disabled($("#status").val() == "I" || $("#status").val() == "C");
	$("#btnEnviarEmailNFe").disabled($("#status").val() != "A" && $("#status").val() != "C");
	$("#btnConfirmar_inutilizacao").disabled(true);
	$("#btnConfirmar_cancelamento").disabled(true);
	$.modalWindow({
		title: "NF-e Sefaz",
		width: 300,
		content: $("#div_nfesefaz"),
		closeButton: true
	});
}

function gerararqnfe_opcao(operacaonfe){
	switch(operacaonfe){
		case "1":
			$.messageBox({
				type: "success",
				title: "Transmitir NF-e",
				text: "Confirma a transmiss&atilde;o da NF-e?",
				buttons: ({
					"Sim": function(){
						$.messageBox("close");
						$.loading(true);
						$.ajax({
							url: "../ajax/notacomplemento_gerarnfe.php",
							dataType: "html",
							data: ({
								idnotacomplemento: $("#idnotacomplemento").val()
							}),
							success: function(html){
								$.loading(false);
								extractScript(html);
							}
						});
					},
					"N\u00E3o": function(){
						$.messageBox("close");
					}
				})
			});
			break;

		case "2":
			$.messageBox({
				type: "success",
				title: "Consultar NF-e",
				text: "Confirma a consulta da NF-e no sefaz?",
				buttons: ({
					"Sim": function(){
						$.messageBox("close");
						$.loading(true);
						$.ajax({
							url: "../ajax/notacomplemento_consultarnfe.php",
							data: ({
								idnotacomplemento: $("#idnotacomplemento").val()
							}),
							success: function(html){
								$.loading(false);
								extractScript(html);
							}
						});
					},
					"N\u00E3o": function(){
						$.messageBox("close");
					}
				})
			});
			break;

		case "3":
			gerararqnfe("inutilizarnfe");
			break;

		case "4":
			$.loading(true);
			$.ajax({
				url: "../ajax/notacomplemento_cancelarnfe.php",
				data: ({
					idnotacomplemento: $("#idnotacomplemento").val(),
					justificativa_cancelamento: $("#justificativa_cancelamento").val()
				}),
				success: function(html){
					$.loading(false);
					extractScript(html);
				}
			});
			break;

		case "5":
			$.messageBox({
				type: "success",
				title: "Imprimir NF-e",
				text: "Confirma a impress&atilde;o da NF-e?",
				buttons: ({
					"Sim": function(){
						$.messageBox("close");
						$.loading(true);
						$.ajax({
							url: "../ajax/notacomplemento_imprimirnfe.php",
							data: ({
								idnotacomplemento: $("#idnotacomplemento").val()
							}),
							success: function(html){
								$.loading(false);
								extractScript(html);
							}
						});
					},
					"N\u00E3o": function(){
						$.messageBox("close");
					}
				})
			});
			break;

		case "6":
			$.messageBox({
				type: "success",
				title: "Imprimir NF-e",
				text: "Confirma o envio o email da NF-e ao destinatario?",
				buttons: ({
					"Sim": function(){
						$.messageBox("close");
						$.loading(true);
						$.ajax({
							url: "../ajax/notacomplemento_enviaremailnfe.php",
							data: ({
								idnotacomplemento: $("#idnotacomplemento").val(),
								emailextra: $("#emailextra").val()
							}),
							success: function(html){
								$.loading(false);
								extractScript(html);
							}
						});
					},
					"N\u00E3o": function(){
						$.messageBox("close");
					}
				})
			});
			break;

		case "7":
			emitircce();
			break;

		case "8":
			gerarpdf();
			break;
	}
}

function emailextra(){
	$("#emailextra").show().val("").attr("placeholder", 'Informe aqui emails extras. Use ";" para separar os emails');
	$("#emailextra").disabled(false);
	$.modalWindow({
		title: "Selecione o que deseja enviar por email", //"Op&ccedil;&otilde;es para impress&atilde;o",
		width: 400,
		content: $("#div_opcoesimpressaoemail"),
		closeButton: true
	});
}

function cancelarnfe(){
	$.modalWindow({
		title: "Justificativa do Cancelamento",
		width: 400,
		content: $("#div_justificativa_cancelamento"),
		closeButton: true
	});
	$("#justificativa_cancelamento").disabled(false).attr("placeholder", "Informe aqui o texto de justificativa do cancelamento da NF-e com no minimo 15 caracteres").focus();
	$("#justificativa_cancelamento").trigger("keydown");
}

function cancelarnfe_confirma(){
	$.modalWindow('close');
	$.messageBox({
		type: "success",
		title: "Cancelar NF-e",
		text: "Confirma o cancelamento da NF-e?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				gerararqnfe_opcao("4");
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}