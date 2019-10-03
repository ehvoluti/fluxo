$.cadastro.after.alterar = function(){
	$("#numrecibo, #status, #valorliquido, #dtliquid, #dtreconc, #codocorrencia, #motivoocorrencia, #valorpago, #valorparcelaorig, #codespecieliq, #codtipoaceite, #historico, #tipoliquidacao").disabled(true);
	$("#fds_infoboletobancario").find("input:text,select,textarea").disabled($("#pagrec").val() == "R");
	$("#docliquidacao").disabled(!param_lancamento_liberardocumento);
	$("body").focusFirst();
};

$.cadastro.before.pesquisar = function(){
	if($("#codlancto").val().length > 0){
		$.ajax({
			async: false,
			url: "../ajax/lancamento_juros.php",
			data: ({
				codlancto: $("#codlancto").val()
			})
		});
	}

	if($("#numrecibo").val().length > 0){
		$.ajax({
			async: false,
			url: "../ajax/lancamento_buscarecibo.php",
			data: ({
				numrecibo: $("#numrecibo").val()
			}),
			success: function(html){
				$("#codrecibo").val(html);
			}
		});
	}
	return true;
};

$.cadastro.after.carregar = function(){
	$("#tipoparceiro").change();
	$("#codparceiro").description();
	$("#btnCadAlterar, #btnCadExcluir, #btnLancLiquidar, #btnLancReconciliar, #btnLancLiberar, #btnLancBoleto, #btnLancParcelar, #btnAceiteDuplicata, #btnImprimirRecibo, #btnImpCarne, #btnImprimirLote, #btnGerarPontos").hide();
	switch($("#status").val()){
		case "A":
			if(lanctoliquidar === true){
				$("#btnLancLiquidar").show();
			}
			$("#btnCadAlterar, #btnCadExcluir, #btnLancParcelar").show();
			if($("#pagrec").val() === "R"){
				if($("#tipoparceiro").val() === "C"){
					$("#btnImpCarne").show();
				}
			}else if($("#pagrec").val() === "P"){
				$("#btnAceiteDuplicata").show();
			}
			break;
		case "L":
			$("#btnLancReconciliar").show();
			$("#btnImprimirRecibo").show();
			if($("#codcheque").val().length > 0 && $("#numlote").val().length > 0){
				$("#btnImprimirLote").show();
			}
		case "R":
		case "B":
			if(lanctoliberar == true){
				$("#btnLancLiberar").show();
			}
			break;
	}

	if(recibonaoliquid === "S" && $("#prevreal").val() === "R"){
		$("#btnImprimirRecibo").show();
	}

	if($("#prevreal").val() === "P"){
		$("#btnLancLiquidar, #btnLancParcelar, #btnAceiteDuplicata").hide();
	}
	if($("#usuario").val().length > 0 && $("#datalog").val().length > 0){
		usuario_nome($("#usuario").val(), function(nome){
			$("#spn_usuario").html(nome);
		});
		$("#spn_datalog").html($("#datalog").val());
		$("#lbl_ultalteracao").show();
	}

	$.ajax({
		url: "../ajax/lancamento_aftercarregar.php",
		data: ({
			codlancto: $("#codlancto").val()
		}),
		success: function(html){
			extractScript(html);
		}
	});
}

$.cadastro.after.limpar = function(){
	$("#lbl_ultalteracao").hide();
}

$.cadastro.after.novapesquisa = function(){
	if(lanctorecebimento && !lanctopagamento){
		$("#pagrec").val("R").disabled(true);
	}else if(!lanctorecebimento && lanctopagamento){
		$("#pagrec").val("P").disabled(true);
	}
}

$(document).bind("ready", function(){
	$("#tabhistorico").click(function(){
		if($("#codlancto").val().length > 0){
			$("#historico").val("Carregando histórico, aguarde por favor...");
			nome_banco = [];
			nome_label = [];
			$("label").each(function(){
				nome_label[nome_label.length] = $(this).html();
				nome_banco[nome_banco.length] = $(this).attr("for");
			});
			nome_label = JSON.stringify(nome_label);
			nome_banco = JSON.stringify(nome_banco);
			$.ajax({
				url: "../ajax/lancamento_buscahistorico.php",
				data: ({nome_label: nome_label, nome_banco: nome_banco, chave: $("#codlancto").val()}),
				success: function(html){
					$("#historico").val(html.replace("/<br>/gi","\n"));
					setTimeout(function(){ $("#historico").val($("#historico").val().replace(/<br>/gi,"\n")); }, 100);
				}
			});
		}
	});


	$("#btnCadClonar").remove();
	$("#btnCadInserir").before("<input type=\"button\" id=\"btnNovoLancamento\" value=\"Novo Lan&ccedil;amento\" onclick=\"openProgram('LanctoGru')\">").remove();
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnImprimirRecibo\" value=\"Imprimir Recibo\" onclick=\"imprimirrecibo()\">");
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnImprimirLote\" value=\"Imprimir Lote\" onclick=\"imprimirlote()\">");
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnLancLiquidar\" value=\"Liquidar\" onclick=\"liquidar()\">");
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnLancReconciliar\" value=\"Reconciliar\" onclick=\"reconciliar()\">");
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnLancLiberar\" value=\"Liberar\" onclick=\"liberar()\">");
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnLancBoleto\" value=\"Gerar Boleto\" onclick=\"gerarboleto()\">");
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnImpCarne\" value=\"Imprimir Carn&ecirc;\" onclick=\"openProgram('ImpCarne','codlancto='+$('#codlancto').val()+'&filtrar=S')\">");
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnLancParcelar\" value=\"Parcelar\" onclick=\"parcelar()\">");
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnGerarPontos\" value=\"Gerar Pontos\" onclick=\"gerarpontos()\">");
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnAceiteDuplicata\" value=\"Aceite Duplicata\" onclick=\"aceiteduplicata()\">");
	$("#btnCadRetornar1").css("margin-top", "7px");
	$("#btnLancLiquidar, #btnLancReconciliar, #btnLancLiberar, #btnLancBoleto, #btnLancParcelar, #btnAceiteDuplicata, #btnImprimirRecibo, #btnImpCarne, #btnImprimirLote, #btnGerarPontos").css("margin-right", "7px");
	$("#_valorpago").bind("change", function(){
		var valorpago = parseFloat($("#_valorpago").val().replace(",", "."));
		var valorliquido = parseFloat($("#valorliquido").val().replace(",", "."));
		if(valorpago >= valorliquido){
			$("#_valorpago").val($("#valorliquido").val());
			$("#_dtvencto").disabled(true);
		}else{
			$("#_dtvencto").disabled(false);
		}
	});

	// Recalcula o valor do desconto baseado na administradora
	$("#codestabelec, #valorparcela").bind("change", function(){
		if($("#codestabelec").val().length > 0 && $("#tipoparceiro").val() == "A" && $("#codparceiro").val().length > 0){
			$.ajax({
				async: false,
				url: "../ajax/lancamento_administradora.php",
				data: ({
					codestabelec: $("#codestabelec").val(),
					codadminist: $("#codparceiro").val(),
					valorparcela: $("#valorparcela").val()
				}),
				success: function(html){
					extractScript(html);
				}
			});
		}
	});
	// Faz o mesmo tratamento para o tipo e codigo do parceiro, porem ao perder o foco
	$("#tipoparceiro, #codparceiro").bind("blur", function(){
		$("#codestabelec").trigger("change");
	});

	$("#valorparcela, #valordescto, #valoracresc, #valorjuros, #valorabatimento").bind("change", function(){
		if($.cadastro.status() != 4){
			$("#valorliquido").val(number_format(parseFloat($("#valorparcela").val().replace(",", ".")) + parseFloat($("#valoracresc").val().replace(",", ".")) + parseFloat($("#valorjuros").val().replace(",", ".")) - parseFloat($("#valordescto").val().replace(",", ".")) - parseFloat($("#valorabatimento").val().replace(",", ".")), 2, ",", ""));
		}
	});
	$("#ac_valorparcela, #ac_valordescto, #ac_valoracresc, #valorjuros").bind("change", function(){
		$("#ac_valorliquido").val(number_format(parseFloat($("#ac_valorparcela").val().replace(",", ".")) + parseFloat($("#ac_valoracresc").val().replace(",", ".")) + parseFloat($("#valorjuros").val().replace(",", ".")) - parseFloat($("#ac_valordescto").val().replace(",", ".")), 2, ",", ""));
	});
	$("#frmLiquidar").find("input,label,select").css("font-size", "14px").filter(":button").css({"font-size": "13px", height: "27px", width: "140px"});
	$("#lq_valorjuros, #lq_valorjurosprorrog, #lq_valordescto, #lq_valoracresc, #lq_valorabatimento").bind("change", function(){
		liquidar_recalcular();
	});
	$("#lq_valorpago").bind("change", function(){
		$("#lq_dtvencto").disabled(parseFloat($(this).val().replace(",", ".")) == parseFloat($("#lq_valorliquido").val().replace(",", ".")));
	}).bind("blur", function(){
		if($(this).val().length == 0){
			$(this).val("0,00");
		}
	});
	$("#ac_codtipoaceite").change(function(){
		if($("#ac_codtipoaceite").val() == 1 || $("#ac_codtipoaceite").val() == 2){
			$("#ac_codbarras").attr("maxlength", 47);
		}else{
			$("#ac_codbarras").attr("maxlength", 48);
		}
	});

	$("#fstipodocumento input:radio").bind("change", function(){
		$("#fstipodocumento input:radio").not(this).attr("checked", false);
		$("#ac_numeroduplicata, #ac_codbarras").disabled(false);
		$("#ac_codbanco").disabled(false).val("");
		$("#ac_agencia").disabled(false).val("");
		$("#ac_conta").disabled(false).val("");
		$("#ac_codbarras").css("background-color", "");
		if($("#rdtipof").checked()){
			$("#ac_codbarras").disabled(true).val("");
//          $("#ac_codbanco").disabled(true).val("");
			$("#ac_agencia").disabled(true).val("");
			$("#ac_conta").disabled(true).val("");
		}else{
			var mask = ($("#rdtipob").checked() ? "barrasboleto" : "barrasconcessionaria");
			$("#ac_codbarras").attr("mask", mask).setMask(mask);
			$("#ac_numeroduplicata").disabled(true).val("");
			$("#ac_codbarras").focus();
			if($("#rdtipoc").checked()){
				$("#ac_codbanco").disabled(true).val("");
				$("#ac_agencia").disabled(true).val("");
				$("#ac_conta").disabled(true).val("");
			}
		}
		$("#tbl_aceiteduplicata").focusFirst();
	});

	var timeout_validacao = null;
	$("#ac_codbarras").bind("change", function(){
		if($("#ac_codtipoaceite").val() == 1){ // Boleto
			var codigo_barras = $(this).val();
			var banco_boleto = codigo_barras.substr(0, 3);
			var data_vencto = $("#ac_dtvencto").val();
			var valor_boleto = $("#ac_valorparcela").val()
			var temp = codigo_barras.substr(40, 14);

			if(codigo_barras.length == 54){
				var fator_venc = parseFloat(temp.substr(0, 4));
				valor_boleto = temp.substr(4, 10) / 100;
				data_vencto = $.date.getFormat("d/m/Y", 1997, 9, 7 + fator_venc);
				$("#ac_dtvencto").val(data_vencto).disabled(true);
				$("#ac_valorparcela").val(number_format(valor_boleto, 2, ",", "")).disabled(true).trigger("change");
				$("#ac_codbanco").disabled(false);
				$("#ac_codbanco option").each(function(){
					if($(this).html() == banco_boleto){
						$(this).parent().val($(this).val()).disabled(true);
					}
				});
			}else{
				$("#ac_dtvencto, #ac_valorparcela").disabled(false);
			}
		}
	}).bind("keyup", function(){
		if(timeout_validacao != null){
			clearTimeout(timeout_validacao);
		}
		timeout_validacao = setTimeout("validacodbarras()", 500);
	});

	// Na liquidacao, se for pressionado a tecla de percentual (%), deve calcular o percentual de juros informado sobre o vaor da parcela para liquidacao
	$("#lq_valorjuros").bind("keypress", function(e){
		if(e.keyCode == 37){ // Percentual %
			var arr = $("#dtvencto").val().split("/");
			var dtvencto = new Date(arr[2], arr[1], arr[0]);
			var dtatual = new Date(server.year, server.month, server.day);
			if(dtvencto.getTime() > dtatual.getTime()){
				var dias = diasDecorridos(dtvencto, dtatual);
			}else{
				var dias = 0;
			}
			var percjuros = parseFloat($(this).val().replace(",", ".")) / 3000;
			var valorparcela = parseFloat($("#lq_valorparcela").val().replace(",", "."));
			$(this).val(number_format((valorparcela * percjuros * dias), 2, ",", "."));
		}
	});
});

$.cadastro.before.salvar = function(){
	var dtvencto = parseInt($("#dtvencto").val().replace("/", "").split("").reverse().join(""));
	var dtprorrog = parseInt($("#dtprorrog").val().replace("/", "").split("").reverse().join(""));

	if(dtvencto > dtprorrog){
		$.messageBox({
			type: "error",
			title: "",
			text: "A data de vencimento n&atilde;o pode ser maior que a data de prorroga&ccedil;&atilde;o."
		});
		$("#dtvencto").focus();
		return false;
	}
	return true;
}

function validacodbarras(){
	if($("#ac_codtipoaceite").val() == 1){ // Boleto
		var codigo_barras = $("#ac_codtipoaceite").val();
		var valido = true;
		if(codigo_barras.length >= 11 || codigo_barras.length >= 24 || codigo_barras.length >= 37 || codigo_barras.length == 54){
			valido = valid_codigo_barras_boletos(codigo_barras, "B");
		}
		$("#ac_codbarras").css("background-color", (valido ? "" : "#FE7085"));
	}else if($("#ac_codtipoaceite").val() == 3){ // Concessionaria
		var codigo_barras = $("#ac_codtipoaceite").val();
		var valido = true;
		if(codigo_barras.length == 12 || codigo_barras.length == 25 || codigo_barras.length == 38 || codigo_barras.length == 51){
			valido = valid_codigo_barras_boletos(codigo_barras, "C");
		}
		$("#ac_codbarras").css("background-color", (valido ? "" : "#FE7085"));
	}
}

function gerarboleto(){
	if($("#codbanco").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o banco antes de gerar o boleto banc&aacute;rio."
		});
		return false;
	}

	if($("#codoficial").val().length > 0 && in_array($("#codoficial").val(), ["001", "033", "237", "341"])){
		$.boletobancario({
			"codlancto"    : $("#codlancto").val(),
			"nossonumero"  : $("#nossonumero").val(),
			"tipoparceiro" : $("#tipoparceiro").val(),
			"codparceiro"  : $("#codparceiro").val()
		});
	}else{
		window.open("../form/boleto.php?codlancto=" + $("#codlancto").val());
	}
}

function liberar(){
	$.messageBox({
		type: "info",
		text: "Tem certeza que deseja alterar o lan&ccedil;amento para o status <b>Aberto</b>?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				$.loading(true);
				$.ajax({
					url: "../ajax/lancamento_liberar.php",
					data: ({
						codlancto: $("#codlancto").val()
					}),
					dataType: "html",
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
}

function liquidar(){
	var win = $.modalWindow({
		closeButton: true,
		content: $("#frmLiquidar"),
		title: "Liquida&ccedil;&atilde;o de Lan&ccedil;amento",
		width: "450px"
	}).find("input:text,select").disabled(false).each(function(){
		var e = $("#" + $(this).attr("id").substr(3));
		if($(e).length > 0){
			$(this).val($(e).val());
		}
	});
	$("#lq_valorparcela, #lq_valorliquido, #lq_tipoliquidacao").disabled(true);
	$("#lq_dtliquid, #lq_dtvencto").val(date);
	$("#lq_tipoliquidacao").val("M");
	liquidar_recalcular();
	$("#lq_valorjuros").focus();
	if(bloqdatapagamento == "S"){
		$("#lq_dtliquid").disabled(true);
	}
}

function liquidar_gravar(status){
	console.info(status);
	if(status == "L" || status == undefined){
		if($("#lq_valorjuros").val().length == 0){
			$.messageBox({
				type: "error",
				text: "Informe o valor de juros.",
				focusOnClose: $("#lq_valorjuros")
			});
			return false;
		}else if($("#lq_valordescto").val().length == 0){
			$.messageBox({
				type: "error",
				text: "Informe o valor de desconto.",
				focusOnClose: $("#lq_valordescto")
			});
			return false;
		}else if($("#lq_valoracresc").val().length == 0){
			$.messageBox({
				type: "error",
				text: "Informe o valor de acr&eacute;scimo.",
				focusOnClose: $("#lq_valoracresc")
			});
			return false;
		}else if($("#lq_valorpago").val().length == 0){
			$.messageBox({
				type: "error",
				text: "Informe o valor pago.",
				focusOnClose: $("#lq_valorpago")
			});
			return false;
		}else if($("#lq_dtliquid").val().length == 0){
			$.messageBox({
				type: "error",
				text: "Informe a data de liquida&ccedil;&atilde;o.",
				focusOnClose: $("#lq_dtliquid")
			});
			return false;
		}else if(!$("#lq_dtvencto").is(":disabled") && $("#lq_dtvencto").val().length == 0){
			$.messageBox({
				type: "error",
				text: "Informe a data de vencimento para o novo lan&ccedil;amento gerado com a diferen&ccedil;a a pagar.",
				focusOnClose: $("#lq_dtvencto")
			});
			return false;
		}else if($("#lq_codespecie").val().length == 0){
			$.messageBox({
				type: "error",
				text: "Informe a forma de pagamento.",
				focusOnClose: $("#lq_codespecie")
			});
			return false;
		}else if($("#lq_codbanco").val().length == 0){
			$.messageBox({
				type: "error",
				text: "Informe o banco.",
				focusOnClose: $("#lq_codbanco")
			});
			return false;
		}
		var valorliquido = parseFloat($("#lq_valorliquido").val().replace(",", "."));
		var valorpago = parseFloat($("#lq_valorpago").val().replace(",", "."));
		if(valorpago > valorliquido){
			$.messageBox({
				type: "error",
				text: "N&atilde;o &eacute; poss&iacute;vel efetuar um pagamento com o valor maior que o total a pagar.",
				focusOnClose: $("#lq_valorpago")
			});
			return false;
		}
		if(valorpago <= 0){
			$.messageBox({
				type: "error",
				text: "N&atilde;o &eacute; poss&iacute;vel efetuar um pagamento com o valor menor ou igual a zero.",
				focusOnClose: $("#lq_valorpago")
			});
			return false;
		}
		var status = null;
	}else{
		if($("#lq_codbanco").val().length == 0){
			$.messageBox({
				type: "error",
				text: "Informe o banco.",
				focusOnClose: $("#lq_codbanco")
			});
		}
		$("#lq_valorjuros").val("0");
		$("#lq_valordescto").val("0");
		$("#lq_valoracresc").val("0");
		$("#lq_valorpago").val("0");
		$("#lq_dtliquid").val("");
		$("#lq_codespecie").val("");
		//$("#lq_codbanco").val("");
		$("#lq_tipoliquidacao").val("");
		var valorliquido = 0.00;
		var valorpago = 0.00;
		var status = "B";
	}

	$.modalWindow("close");
	$.loading(true);
	$.ajax({
		url: "../ajax/lancamento_liquidar.php",
		data: ({
			codlancto: $("#codlancto").val(),
			valorjuros: $("#lq_valorjuros").val(),
			valorjurosprorrog: $("#lq_valorjurosprorrog").val(),
			valordescto: $("#lq_valordescto").val(),
			valoracresc: $("#lq_valoracresc").val(),
			valorpago: $("#lq_valorpago").val(),
			dtliquid: $("#lq_dtliquid").val(),
			dtvencto: $("#lq_dtvencto").val(),
			codespecie: $("#lq_codespecie").val(),
			codbanco: $("#lq_codbanco").val(),
			docliquidacao: $("#lq_docliquidacao").val(),
			status: status,
			tipoliquidacao: "M",
			dtreconc: $("#lq_dtreconc").val()
		}),
		dataType: "html",
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function aceiteduplicata(){
	if($("#cnpjboleto").val().length == 0){
		$.ajax({
			async : false,
			url: "../ajax/lancamento_aceiteduplicata_cnpjboleto.php",
			type: "POST",
			dataType: "html",
			data:{
				codparceiro: $("#codparceiro").val(),
				tipoparceiro: $("#tipoparceiro").val()
			},
			beforeSend: function(xhr){
				$.loading(true);
			},
			success: function(html){
				extractScript(html);
			},
			complete: function(){
				$.loading(false);
			}
		});
	}

	$.modalWindow({
		closeButton: true,
		content: $("#div_aceiteduplicata"),
		title: "Aceite de Duplicata ou Boleto",
		width: "630px"
	}).find("input,select").disabled(false).filter("input:text,select").each(function(){
		var e = $("#" + $(this).attr("id").substr(3));
		if($(e).length > 0){
			$(this).val($(e).val());
		}
	});
	$("#ac_codbarras").css("background-color", "");
	$("#ac_valorliquido").disabled(true);
}

function aceiteduplicata_gravar(){
	if($("#ac_codtipoaceite").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o tipo de aceite de duplicata.",
			focusOnClose: $("#ac_codtipoaceite")
		});
		return false;
	}else if(in_array($("#ac_codtipoaceite").val(), [1, 3]) && $("#ac_codbarras").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o c&oacute;digo de barras do boleto.",
			focusOnClose: $("#ac_codbarras")
		});
		return false;
	}else if($("#ac_codtipoaceite").val() == 2){
		if($("#ac_numeroduplicata").val().length == 0){
			$.messageBox({
				type: "error",
				text: "Informe o numero do fatura/duplicata.",
				focusOnClose: $("#ac_numeroduplicata")
			});
			return false;
		}else if($("#ac_dtvencto").val().length == 0){
			$.messageBox({
				type: "error",
				text: "Informe a data de vencimento.",
				focusOnClose: $("#ac_dtvencto")
			});
			return false;
		}else if($("#ac_valorparcela").val().length == 0){
			$.messageBox({
				type: "error",
				text: "Informe valor da parcela.",
				focusOnClose: $("#ac_valorparcela")
			});
			return false;
		}else if($("#ac_valordescto").val().length == 0){
			$.messageBox({
				type: "error",
				text: "Informe valor do desconto.",
				focusOnClose: $("#ac_valordescto")
			});
			return false;
		}else if($("#ac_valoracresc").val().length == 0){
			$.messageBox({
				type: "error",
				text: "Informe valor do acr&eacute;scimo.",
				focusOnClose: $("#ac_valoracresc")
			});
			return false;
		}
	}else if($("#ac_codtipoaceite").val() == 1){
		if(!valid_codigo_barras_boletos($("#ac_codbarras").val(), "B")){
			$.messageBox({
				type: "error",
				text: "C&oacute;digo de barras informado &eacute; inv&aacute;lido.",
				focusOnClose: $("#ac_codbarras")
			});
			return false;
		}

	}else if($("#ac_codtipoaceite").val() == 3){
		if(!valid_codigo_barras_boletos($("#ac_codbarras").val(), "C")){
			$.messageBox({
				type: "error",
				text: "C&oacute;digo de barras informado &eacute; inv&aacute;lido.",
				focusOnClose: $("#ac_codbarras")
			});
			return false;
		}
	}

	var valorparcela = parseFloat($("#ac_valorparcela").val().replace(",", "."));
	if(valorparcela <= 0){
		$.messageBox({
			type: "error",
			text: "O valor da parcela deve ser maior que <b>zero</b>",
			focusOnClose: $("#ac_valorparcela")
		});
		return false;
	}
	$.modalWindow("close");
	$.loading(true);
	$.ajax({
		url: "../ajax/lancamento_aceiteduplicata.php",
		data: ({
			codlancto: $("#codlancto").val(),
			codtipoaceite: $("#ac_codtipoaceite").val(),
			codbarras: $("#ac_codbarras").val(),
			numeroduplicata: $("#ac_numeroduplicata").val(),
			dtvencto: $("#ac_dtvencto").val(),
			numnotafis: $("#ac_numnotafis").val(),
			valorparcela: $("#ac_valorparcela").val(),
			valordescto: $("#ac_valordescto").val(),
			valoracresc: $("#ac_valoracresc").val(),
			dtaceite: date,
			codbanco: $("#ac_codbanco").val(),
			agenciacedente: $("#ac_agenciacedente").val(),
			contacedente: $("#ac_contacedente").val(),
			cnpjboleto: $("#cnpjboleto").val()
		}),
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function imprimirrecibo(){
	$.ajax({
		url: "../ajax/lancamento_recibo.php",
		data: ({
			codlancto: $("#codlancto").val()
		}),
		success: function(html){
			extractScript(html);
		}
	});
}

function liquidar_recalcular(){
	$("#lq_valorparcela, #lq_valorjuros, #lq_valordescto, #lq_valoracresc, #lq_valorabatimento, #lq_valorjurosprorrog").each(function(){
		if($(this).val().length == 0){
			$(this).val("0,00");
		}
	});
	var valorparcela = parseFloat($("#lq_valorparcela").val().replace(",", "."));
	var valorjuros = parseFloat($("#lq_valorjuros").val().replace(",", "."));
	var valorjurosprorrog = parseFloat($("#lq_valorjurosprorrog").val().replace(",", "."));
	var valordescto = parseFloat($("#lq_valordescto").val().replace(",", "."));
	var valoracresc = parseFloat($("#lq_valoracresc").val().replace(",", "."));
	var valorabatimento = parseFloat($("#lq_valorabatimento").val().replace(",", "."));
	var totalliquido = valorparcela + valorjuros + valorjurosprorrog - valordescto + valoracresc - valorabatimento;
	$("#lq_valorliquido, #lq_valorpago").val(number_format(totalliquido, 2, ",", "")).trigger("change");
}

var __parcelar_comentrada = false;
function parcelar(){
	$("#frmParcelar").find("input,select").disabled(false);
	$.modalWindow({
		closeButton: true,
		content: $("#frmParcelar"),
		title: "Parcelar Lan&ccedil;amento",
		width: "500px"
	});
	parcelar_gerar();
}

function parcelar_gerar(){
	$.loading(true);
	$.ajax({
		url: "../ajax/lancamento_parcelar_gerar.php",
		data: ({
			codlancto: $("#codlancto").val(),
			codcondpagto: $("#parcelar_codcondpagto").val()
		}),
		success: function(html){
			$.loading(false);
			$("#gridParcelar").html(html);
			$.gear();
			$("#parcelar_codcondpagto").focus();
			if(__parcelar_comentrada){
				$("#gridParcelar input:text[colname='valorparcela']:first").bind("change", function(){
					var e_valorparcela = $("#gridParcelar input:text[colname='valorparcela']").not(this);
					var valorparcela = (parseFloat($("#valorparcela").val().replace(",", ".")) - parseFloat($(this).val().replace(",", "."))) / $(e_valorparcela).length;
					$(e_valorparcela).not(this).val(number_format(valorparcela, 2, ",", ""));
					var somaparcelas = 0;
					$(e_valorparcela).add(this).each(function(){
						somaparcelas += parseFloat($(this).val().replace(",", "."));
					});
					var diferenca = parseFloat($("#valorparcela").val().replace(",", ".")) - somaparcelas;
					$(e_valorparcela).last().val(number_format((parseFloat($(e_valorparcela).last().val().replace(",", ".")) + diferenca), 2, ",", ""));
					$(e_valorparcela).add(this).each(function(){
						if(parseFloat($(this).val().replace(",", ".")) < 0){
							$(this).val("0,00");
						}
					});
				});
			}
		}
	});
}

function parcelar_gravar(){
	var e_data = $("#gridParcelar").find("[parcela]");
	if($(e_data).filter("[colname='dtvencto']").length < 2){
		$.messageBox({
			type: "error",
			text: "Informe uma condi&ccedil;&atilde;o de pagamento que contenha no m&iacute;nimo duas parcelas.",
			focusOnClose: $("#parcelar_codcondpagto")
		});
		return false;
	}
	var data = $(e_data).map(function(i){
		return "parcela[" + $(this).attr("parcela") + "][" + $(this).attr("colname") + "]=" + $(this).val();
	}).get().join("&");
	$.loading(true);
	$.ajax({
		url: "../ajax/lancamento_parcelar_gravar.php",
		data: "codlancto=" + $("#codlancto").val() + "&" + data,
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function reconciliar(){
	$.modalWindow({
		closeButton: true,
		content: $("#frmReconciliar"),
		height: "145px",
		title: "Reconcilia&ccedil;&atilde;o",
		width: "300px"
	});
	$("#_dtreconc").disabled(false).val(date);
}

function reconciliar_(){
	if($("#_dtreconc").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a data de reconcilia&ccedil;&atilde;o.",
			focusOnClose: $("#_dtreconc")
		});
		return false;
	}
	$.loading(true);
	$.ajax({
		url: "../ajax/lancamento_reconciliar.php",
		data: ({
			codlancto: $("#codlancto").val(),
			dtreconc: $("#_dtreconc").val()
		}),
		dataType: "html",
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function imprimirlote(){
	window.open("../form/liqlotelancamento_liquidar_imprimir.php?codestabelec=" + $("#codestabelec").val() + "&numlote=" + $("#numlote").val());
}

function aceiteduplicata_limpar(){
	$("#ac_codbarras").val("");
	$("#ac_numeroduplicata").val("");
	$("#cnpjboleto").val("");
	$("#ac_numnotafis").val("");
	$("#ac_agenciacedente").val("");
	$("#ac_codtipoaceite").val("");
	$.loading(true);
	$.ajax({
		url: "../ajax/lancamento_aceiteduplicata.php",
		data: ({
			codlancto: $("#codlancto").val(),
			codtipoaceite: $("#ac_codtipoaceite").val(),
			codbarras: $("#ac_codbarras").val(),
			numeroduplicata: $("#ac_numeroduplicata").val(),
			dtvencto: $("#ac_dtvencto").val(),
			numnotafis: $("#ac_numnotafis").val(),
			valorparcela: $("#ac_valorparcela").val(),
			valordescto: $("#ac_valordescto").val(),
			valoracresc: $("#ac_valoracresc").val(),
			dtaceite: date,
			codbanco: $("#ac_codbanco").val(),
			agenciacedente: $("#ac_agenciacedente").val(),
			contacedente: $("#ac_contacedente").val(),
			cnpjboleto: $("#cnpjboleto").val(),
			tipo: "L"
		}),
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function gerarpontos(){
	$.superVisor({
		type: "venda",
		success: function(){
			$.ajax({
				url: "../ajax/lancamento_gerarpontos.php",
				type: "POST",
				dataType: "html",
				data:({
					codlancto: $("#codlancto").val(),
					codparceiro: $("#codparceiro").val(),
					codespecie: $("#codespecie").val(),
					codestabelec: $("#codestabelec").val(),
					dtemissao: $("#dtemissao").val(),
					idnotafiscal: $("#idnotafiscal").val(),
					pagrec: $("#pagrec").val(),
					valorliquido: parseFloat($("#valorliquido").val().replace(",","."))
				}),
				beforeSend: function(){
					$.loading(true);
				},
				success: function(html){
					extractScript(html);
				},
				complete: function(){
					$.loading(false);
				}
			});
		},
		fail: function(){
			$.messageBox({
				type: "error",
				text: "Pontos não gerado.",
				focusOnClose: $("#btnGerarPontos")
			});
		}
	});

}
