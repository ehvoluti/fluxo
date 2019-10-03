var windowSize = null;
var seqitem = null;
$(window).bind("resize", function(){
	var width = $(this).width();
	if(width >= 1200){
		windowSize = "lg";
	}else if(width >= 992){
		windowSize = "md";
	}else if(width >= 768){
		windowSize = "sm";
	}else{
		windowSize = "xs";
	}
});

$(document).bind("ready", function(){

	// Verifica o tamanho da tela
	$(window).trigger("resize");

	// Tratamento para poder colocar um modal sobre o outro
	$(".modal").on("show.bs.modal", function(){
		$(this).css("z-index", 1401);
	}).on("shown.bs.modal", function(){
		$(this).before($(".modal-backdrop").last());
		$(this).css("z-index", 1400);
	});

	// Ao inserir algum valor em um campo que estava marcado como obrigatorio, remove o "vermelho"
	$("input, select, textarea").bind("keyup change", function(){
		var formGroup = $(this).parents(".form-group, .form-group-lg");
		if($(formGroup).length > 0 && $(formGroup).is(".required") && $(this).val().length > 0){
			$(formGroup).removeClass("required");
		}
	});

	// Ao mudar a operacao de nota fiscal
	$("#operacao").bind("change", function(){
		operacaonota_verificar();
	});

	// Verifica se soh exite um valor no campo da operacao
	if($("#operacao option").length === 2){
		$("#operacao").val($("#operacao option:last").val());
	}

	// Verifica a operacao de nota fiscal
	operacaonota_verificar();

	// Foca na operacao ao abrir os parametros
	$("#modal-parametro").on("shown.bs.modal", function(){
		$("#operacao").focus();
	});

	// Foca no campo digitavel ao abrir a selecao de parceiro
	$("#modal-parceiro").on("shown.bs.modal", function(){
		$("#codparceiro").select();
	});

	// Ao abrir a consulta de parceiro
	$("#modal-parceiroconsulta").on("show.bs.modal", function(){
		$("#modal-parceiroconsulta .table tbody").html("");
		$("#parceiroconsulta-pesquisa").val("");
	}).on("shown.bs.modal", function(){
		$("#parceiroconsulta-pesquisa").focus();
	});

	// Ao abrir a consulta de produto
	$("#modal-produtoconsulta").on("show.bs.modal", function(){
		$("#modal-produtoconsulta .table tbody").html("");
		$("#produtoconsulta-pesquisa").val("");
	}).on("shown.bs.modal", function(){
		$("#produtoconsulta-pesquisa").val("").focus();
	});

	// Foca no primeiro botao ao abrir a caixa de mensagem
	$("#modal-messagebox").on("shown.bs.modal", function(){
		$("#modal-messagebox .btn:first").focus();
	});

	// Ao pressionar a tecla ENTER no parceiro deve prosseguir
	$("#codparceiro").bind("keypress", function(e){
		if(e.keyCode === 13){
			parceiro_prosseguir();
		}
	});

	// Ao pressionar a tecla ENTER na consulta de parceiro
	$("#parceiroconsulta-pesquisa").bind("keypress", function(e){
		if(e.keyCode === 13){
			parceiroconsulta_pesquisar();
		}
	});

	// Ao pressionar a tecla ENTER na consulta de produto
	$("#produtoconsulta-pesquisa, #produtoconsulta-produto, #produtoconsulta-fornecedor, #produtoconsulta-reffornec").bind("keypress", function(e){
		if(e.keyCode === 13){
			produtoconsulta_pesquisar();
		}
	});

	// Ao pressionar a tecla ENTER na consulta de produto
	$("#produto-codproduto").bind("keypress", function(e){
		if(e.keyCode === 13){
			produto_carregar();
		}
	});

	// Ao pressionar a tecla ENTER no preco do produto
	$("#produto-preco").bind("keypress", function(e){
		if(e.keyCode === 13){
			$("#produto-quantidade").select();
		}
	});

	// Ao pressionar a tecla ENTER na quantidade do produto
	$("#produto-quantidade").bind("keypress", function(e){
		if(e.keyCode === 13){
			produto_confirmar();
		}
	});

	// Aplica mascara de campo numerico em alguns campos
	$("#produto-codproduto").numericInput(0);
	$("#produto-quantidade").numericInput(4);
	$("#produto-percdescto").numericInput(4);
	$("#produto-percacresc").numericInput(4);
	$("#produto-percfrete").numericInput(4);

	// Aplica mascara de entrada em alguns campos
	$("[mask]").cwSetMask();
	$("#parceirocadastro-tppessoa").bind("change", function(){
		settipopessoa("cpfcnpj", $(this).val(), $("#parceirocadastro-cpfcnpj"));
		settipopessoa("rgie", $(this).val(), $("#parceirocadastro-rgie"));
	});

	// Ao alterar o CEP, completa o endereco
	$("#parceirocadastro-cep").bind("change", function(){
		$(this).cep({
			endereco: $("#parceirocadastro-endereco"),
			bairro: $("#parceirocadastro-bairro"),
			uf: $("#parceirocadastro-uf"),
			cidade: $("#parceirocadastro-codcidade")
		});
	});

	// Ao alterar o preco ou a quantidade, recalcula o total
	$("#div-produto").find("input").not("#produto-codproduto").bind("keyup", function(){
		produto_recalcular();
	});

	// Muda o status da tela principal
	produto_status(0);

	// Verifica se existe alguma operacao disponivel
	if($("#operacao").find("option").length === 1){
		$.messageBox({
			size: "md",
			text: "Por favor, crie a parametrização para as operações desejadas em:<br><a onclick=\"openProgram('CadParamPontoOperacao')\">Parâmetros do Ponto de Operação</a><br><br>Após criar a parametrização, recarregue a página.",
			buttons: [
				{
					text: "Recarregar página",
					class: "btn-primary",
					click: function(){
						window.location.reload();
					}
				}
			]
		});
		return false;
	}

	// Carrega o arquivo temporario, caso exista
	temporario_carregar();


	$.ajax({
		url: "../ajax/pontooperacao_carregar.php",
		data: {
			operacao: $("#operacao").val(),
			codestabelec: $("#codestabelec").val()
		},
		success: function(result){
			extractScript(result);
		}
	});


	$("#operacao,#codestabelec").change(function(){
		if($("#codestabelec").val().length > 0 && $("#operacao").val().length > 0)
		$.ajax({
			url: "../ajax/pontooperacao_carregar.php",
			data: {
				operacao: $("#operacao").val(),
				codestabelec: $("#codestabelec").val()
			},
			success: function(result){
				extractScript(result);
			}
		});
	});

	$("#codestabelec").val(codestabelecpadrao);

	$("#codestabelec").promise().done(function(){
		$(this).trigger("change");
	});

});

function estabelecimentotransf_abrir(){
	$("#modal-estabelecimentotransf").modal();
	$("#table-estabelecimentotransf tbody > *").remove();
	$.ajax({
		url: "../ajax/pontooperacao_estabelecimentotransf_carregar.php",
		data: {
			codproduto: $("#produto-codproduto").val()
		},
		success: function(result){
			$("#table-estabelecimentotransf tbody").html(result);
		}
	});
}

function estabelecimentotransf_selecionar(codestabelec){
	$("#modal-estabelecimentotransf").modal("hide");
	$("#produto-codestabelectransf").val(codestabelec);
	produto_gravar();
}

function finalizacao_abrir(){
	$("#totalbruto, #totalliquido").disabled(true);
	$("#modal-finalizacao").modal({
		backdrop: "static",
		keyboard: false
	});

	// Parametros de nota fiscal
	$.ajax({
		url: "../ajax/pontooperacao_paramnotafiscal.php",
		data: {
			operacao: $("#operacao").val(),
			codestabelec: $("#codestabelec").val(),
			codparceiro: $("#codparceiro").val(),
		},
		success: function(result){
			extractScript(result);
		}
	});
}

function finalizacao_cancelar(){
	$("#modal-finalizacao").modal("hide");
}

function finalizacao_erro(texto, titulo){
	titulo = (titulo === undefined ? false : titulo);
	$.messageBox({
		text: (titulo ? "<h3>Houve uma falha!</h3>" : "") + texto
	});
}

function finalizacao_prosseguir(){
	if($("#codespecie").val().length === 0){
		$("#codespecie").required();
		return false;
	}
	if($("#codcondpagto").val().length === 0){
		$("#codcondpagto").required();
		return false;
	}
	$("#modal-finalizacao").find(".btn-success").button("loading");
	$("#modal-finalizacao").find("button, input, select").disabled(true);
	$.ajax({
		url: "../ajax/pontooperacao_finalizar.php",
		data: {
			operacao: $("#operacao").val(),
			codestabelec: $("#codestabelec").val(),
			natoperacao: $("#natoperacao").val(),
			codparceiro: $("#codparceiro").val(),
			codtabela: $("#codtabela").val(),
			codfunc: $("#codfunc").val(),
			dtemissao: $("#dtemissao").val(),
			dtentrega: $("#dtentrega").val(),
			codespecie: $("#codespecie").val(),
			codcondpagto: $("#codcondpagto").val(),
			codtransp: $("#codtransp").val(),
			numpedido: $("#numpedido").val(),
			observacao: $("#observacao").val(),
			observacaofiscal: $("#observacaofiscal").val(),
			codrepresentante: $("#codrepresentante").val(),
		},
		complete: function(){
			$("#modal-finalizacao").find(".btn-success").button("reset");
			$("#modal-finalizacao").find("button, input, select").disabled(false);
		},
		success: function(result){
			extractScript(result);
		}
	});
}

function finalizacao_sucesso(numpedido){
	$("#modal-finalizacao").modal("hide");

	temporario_limpar();
	produto_tabela();
	produto_cancelar();

	$("#codparceiro").val("");
	$("#observacao").val("");
	$("#observacaofiscal").val("");

	$.messageBox({
		text: "Pedido gravado com sucesso!<br>O número do pedido é: <strong>" + numpedido + "</strong>",
		buttons: [
			{
				text: "Imprimir",
				class: "btn-primary",
				click: function(){
					$.postWindow("../form/pedido_imprimir.php", {
						codestabelec: $("#codestabelec").val(),
						numpedido: numpedido,
					});
				}
			},
			{
				text: "OK",
				class: "btn-primary",
				click: function(){
					$("#modal-messagebox").modal("hide");
				}
			}
		],
		close: function(){
			parceiro_abrir();
		}
	});
}

var __financeiro_numpedido = null;
function financeiro_abrir(codestabelec, numpedido){
	__financeiro_numpedido = numpedido;
	$.ajax({
		url: "../ajax/pontooperacao_financeiro_carregar.php",
		data: {
			codestabelec: codestabelec,
			numpedido: numpedido
		},
		success: function(result){
			$("#table-financeiro tbody").html(result);
			financeiro_preparar();

			$("#modal-financeiro").modal({
				backdrop: "static",
				keyboard: false
			});
		}
	});
}

function financeiro_gravar(){
	// Verifica se a soma das parcelas estao batendo com o valor original
	var totalcalculado = 0;
	$("#table-financeiro [coluna='valorparcela']").each(function(){
		totalcalculado += parseFloat($(this).val().replace(",", "."));
	});
	var totaloriginal = parseFloat($("#financeiro-totalparcelas").val().replace(",", "."));
	var diferenca = Math.round((totaloriginal - totalcalculado) * 100) / 100;

	var totalcalculado_f = number_format(totalcalculado, 2, ",", ".");
	var totaloriginal_f = number_format(totaloriginal, 2, ",", ".");
	var diferenca_f = number_format(Math.abs(diferenca), 2, ",", ".");
	if(diferenca !== 0){
		var palavra = (diferenca > 0 ? "faltando" : "sobrando");
		$.messageBox({
			text: [
				"O total das parcelas deveria ser <b>R$ " + totaloriginal_f + "</b>",
				"O total informado para as parcelas é <b>R$ " + totalcalculado_f + "</b>",
				"Está " + palavra + " <b>R$ " + diferenca_f + "</b> no total das parcelas."
			].join("<br>")
		});
		return false;
	}

	var data = {};
	$("#table-financeiro tr[codlancto]").each(function(){
		var lancamento = {};
		$(this).find("[coluna]").each(function(){
			lancamento[$(this).attr("coluna")] = $(this).val();
		});
		data[$(this).attr("codlancto")] = lancamento;
	});

	$("#btn-financeiro-gravar").button("loading");
	$.ajax({
		method: "POST",
		url: "../ajax/pontooperacao_financeiro_gravar.php",
		data: {
			numpedido: __financeiro_numpedido,
			dados: JSON.stringify(data)
		},
		success: function(result){
			$("#btn-financeiro-gravar").button("reset");
			extractScript(result);
		}
	});
}

function financeiro_preparar(){
	var e_valorparcela = $("#table-financeiro [coluna='valorparcela']");
	var e_dtvencto = $("#table-financeiro [coluna='dtvencto']");

	var totalparcelas = 0;
	$(e_valorparcela).each(function(){
		totalparcelas += parseFloat($(this).val().replace(",", "."));
	});
	$("#financeiro-totalparcelas").val(totalparcelas);

	$(e_valorparcela).attr("alterado", "N").bind("change", function(){
		$(this).attr("alterado", "S");

		var e_valorparcela_s = $(e_valorparcela).filter("[alterado='S']");
		var e_valorparcela_n = $(e_valorparcela).filter("[alterado='N']");

		var totalalterado = 0;
		$(e_valorparcela_s).each(function(){
			totalalterado += parseFloat($(this).val().replace(",", "."));
		});

		var totalparcelas = $("#financeiro-totalparcelas").val();

		var valorparcela = (totalparcelas - totalalterado) / $(e_valorparcela_n).length;
		valorparcela = Math.round(valorparcela * 100) / 100;
		$(e_valorparcela_n).val(valorparcela);

		var somaparcelas = 0;
		$(e_valorparcela_s).add(e_valorparcela_n).each(function(){
			somaparcelas += parseFloat($(this).val().replace(",", "."));
		});

		var diferenca = totalparcelas - somaparcelas;
		var valorultima = parseFloat($(e_valorparcela_n).last().val().replace(",", ".")) + diferenca;
		valorultima = Math.round(valorultima * 100) / 100;
		$(e_valorparcela_n).last().val(valorultima);
		$(e_valorparcela_s).add(e_valorparcela_n).each(function(){
			if(parseFloat($(this).val().replace(",", ".")) < 0){
				$(this).val(0.00);
			}
		});
	});

	$(e_dtvencto).attr("alterado", "N").bind("change", function(){
		$(this).attr("alterado", "S");

		var dia = $(this).val().split("-")[2];

		$(e_dtvencto).filter("[alterado='N']").each(function(){
			$(this).val($(this).val().substr(0, 8) + dia);
		});
	});
}

function numericValue(value){
	if(value.indexOf(".") > -1 && value.indexOf(",") > -1){
		if(value.indexOf(".") < value.indexOf(",")){
			value = parseFloat(value.split(".").join("").replace(",", "."));
		}else{
			value = parseFloat(value.split(",").join(""));
		}
	}else if(value.indexOf(",") > -1){
		value = parseFloat(value.replace(",", "."));
	}
	if(isNaN(value)){
		value = 0;
	}
	return value;
}

function observacao_abrir(){
	$("#modal-observacao").modal("show");
}

function operacao_cabecalho(){
	$.ajax({
		url: "../ajax/pontooperacao_operacao_cabecalho.php",
		data: {
			operacao: $("#operacao").val(),
			codparceiro: $("#codparceiro").val(),
			codfunc: $("#codfunc").val(),
			codrepresentante: $("#codrepresentante").val(),
			natoperacao: $("#natoperacao").val()
		},
		success: function(result){
			$("#navbar-info").html(result);
		}
	});
}

function operacao_cancelar(){
	$.messageBox({
		text: "Tem certeza que deseja cancelar a operação?",
		buttons: [
			{
				text: "Não",
				class: "btn-danger",
				click: function(){
					operacao_cancelar_nao();
				}
			},
			{
				text: "Sim",
				class: "btn-success",
				click: function(){
					operacao_cancelar_sim();
				}
			}
		]
	});
}

function operacao_cancelar_nao(){
	$("#modal-messagebox").modal("hide");
}

function operacao_cancelar_sim(){
	$("#modal-messagebox").modal("hide");
	temporario_limpar();
	produto_tabela();
	produto_cancelar();
	$("#codparceiro").val("");
	parceiro_abrir();
}

function operacaonota_verificar(){
	var operacao = $("#operacao").val();

	// Natureza de operacao
	$("#natoperacao").refreshComboBox();
	$("#natoperacaoitem").refreshComboBox();

	// Parceiro
	$.ajax({
		url: "../ajax/pontooperacao_operacaonota_parceiro.php",
		data: {
			operacao: operacao
		},
		success: function(result){
			var parceiro = result;
			switch(parceiro){
				case "C":
					parceiro_desc = "Cliente";
					break;
				case "E":
					parceiro_desc = "Estabelecimento Destino";
					break;
				case "F":
					parceiro_desc = "Fornecedor";
					break;
				default :
					parceiro_desc = parceiro;
					break;
			}
//			$("#modal-parceiro").find(".modal-title").html("Informe o " + parceiro_desc);
			$("#modal-parceiroconsulta").find(".modal-title").html("Consultando " + parceiro_desc);
			$("#modal-parceiro label:first").html("Codigo do " + parceiro_desc);
			$("#pedidoconsulta-table thead th:nth(2)").html(parceiro_desc);
		}
	});

	// Tabela de preco
	if(operacao === "VD"){
		$.ajax({
			url: "../ajax/pontooperacao_operacaonota_tabelapreco.php",
			data: {
				operacao: operacao
			},
			success: function(result){
				extractScript(result);
			}
		});
	}

	if(param_informarepresentante == "S" && $("#operacao").val() == "VD"){
		$("[divrepresentante]").show();
		$("[divfuncionario]").hide();		
	}else{
		$("[divrepresentante]").hide();
		$("[divfuncionario]").show();
	}

	// Cadastrar novo parceiro (no caso, apenas cliente)
	if(operacao === "VD"){
		$("#codparceiro").next().find(".btn:first").show();
	}else{
		$("#codparceiro").next().find(".btn:first").hide();
	}

	// Tabela de preco
	if(operacao === "VD"){
		$("#codtabela").parents(".form-group").show();
	}else{
		$("#codtabela").parents(".form-group").hide();
	}

	// Campos extras do produto
	produto_campoextra();
}

function parametro_abrir(){
	$("#modal-parametro").modal({
		backdrop: "static",
		keyboard: false
	});
}

function parametro_prosseguir(){
	var required = false;
	if($("#operacao").val().length === 0){
		$("#operacao").required();
		required = true;
	}
	if($("#codestabelec").val().length === 0){
		$("#codestabelec").required();
		required = true;
	}
	if($("#natoperacao").val().length === 0){
		$("#natoperacao").required();
		required = true;
	}
	if($("#codtabela").is(":visible") && $("#codtabela").val().length === 0){
		$("#codtabela").required();
		required = true;
	}
	if(required){
		return false;
	}
	$("#modal-parametro").modal("hide");
	parceiro_abrir();
}

function parceiro_abrir(){
	$("#modal-parceiro").modal({
		backdrop: "static",
		keyboard: false
	});
}

function parceiro_cadastrar(){
	$("#modal-parceirocadastro").modal({
		backdrop: "static",
		keyboard: false
	});
}

function parceiro_cadastrar_finalizar(){
	var data = {};
	$("#modal-parceirocadastro").find("input, select").each(function(){
		var id = $(this).attr("id").replace("parceirocadastro-", "");
		data[id] = $(this).val();
	});
	data["codfunc"] = $("#codfunc").val();

	$.ajax({
		url: "../ajax/pontooperacao_parceiro_cadastrar.php",
		data: data,
		success: function(result){
			extractScript(result);
		}
	});
}

function parceiro_consultar(){
	$("#modal-parceiroconsulta").modal();
}

function parceiro_parametro(){
	$("#modal-parceiro").modal("hide");
	parametro_abrir();
}

function parceiro_prosseguir(){
	if($("#codparceiro").val().length === 0 && $("#numpedido").val().length === 0){
		$("#codparceiro").required();
		return false;
	}

	if($("#codparceiro").val().length > 0){
		$("#btn-parceiro-prosseguir").button("loading");
		$.ajax({
			url: "../ajax/pontooperacao_parceiro_verificar.php",
			data: {
				operacao: $("#operacao").val(),
				codparceiro: $("#codparceiro").val(),
				codrepresentante: $("#codrepresentante").val(),
				
			},
			success: function(result){
				$("#btn-parceiro-prosseguir").button("reset");
				extractScript(result);
			}
		});
	}else{
		$.ajax({
			url: "../ajax/pontooperacao_pedido.php",
			data: {
				operacao: $("#operacao").val(),
				numpedido: $("#numpedido").val(),
				codestabelec: $("#codestabelec").val(),
				codrepresentante: $("#codrepresentante").val(),
			},
			success: function(result){
				extractScript(result);
			}
		});
	}
}

function parceiroconfirmacao_nao(){
	$("#modal-messagebox").modal("hide");
	parceiro_abrir();
}

function parceiroconfirmacao_sim(){
	temporario_gravar();
	$("#modal-messagebox").modal("hide");
	produto_status(0);
	$("#produto-codproduto").focus();
}

function parceiroconsulta_pesquisar(){
//	if($("#parceiroconsulta-pesquisa").val().length < 3){
//		$("#parceiroconsulta-pesquisa").required();
//		return false;
//	}
	$.ajax({
		url: "../ajax/pontooperacao_parceiroconsulta_pesquisar.php",
		data: {
			operacao: $("#operacao").val(),
			pesquisa: $("#parceiroconsulta-pesquisa").val(),
			codrepresentante: $("#codrepresentante").val(),
		},
		success: function(result){
			$("#parceiroconsulta-table tbody").html(result);
		}
	});
}

function pedidoconsulta_pesquisar(){
	$.ajax({
		url: "../ajax/pontooperacao_pedidoconsulta_pesquisar.php",
		data: {
			operacao: $("#operacao").val(),
			pesquisa: $("#pedidoconsulta-pesquisa").val()
		},
		success: function(result){
			$("#pedidoconsulta-table tbody").html(result);
		}
	});
}

function pedido_consultar(){
	$("#modal-pedidoconsulta").modal();
}

function pedidoconsulta_selecionar(numpedido){
	$("#modal-pedidoconsulta").modal("hide");
	$("#numpedido").val(numpedido);
	parceiro_prosseguir();
}

function parceiroconsulta_selecionar(codparceiro){
	$("#modal-parceiroconsulta").modal("hide");
	$("#codparceiro").val(codparceiro);
	parceiro_prosseguir();
}

function produto_campoextra(){
	$("#produto-campoextra").find("input, select").parents(".form-group, .form-group-lg").hide();
	$("#codtransp").parents(".form-group, .form-group-lg").hide();
	$.ajax({
		url: "../ajax/pontooperacao_produto_campoextra.php",
		data: {
			operacao: $("#operacao").val()
		},
		success: function(result){
			extractScript(result);
		}
	});
}

function produto_cancelar(){
	$("#div-produto").find("input, select").val("");
	produto_status(0);
	$("#produto-codproduto").focus();
}

function produto_carregar(){
	if($("#produto-codproduto").val().length === 0){
		$("#produto-codproduto").required();
		return false;
	}
	$.ajax({
		async: false,
		url: "../ajax/pontooperacao_produto_carregar.php",
		data: {
			operacao: $("#operacao").val(),
			codestabelec: $("#codestabelec").val(),
			natoperacao: $("#natoperacao").val(),
			codparceiro: $("#codparceiro").val(),
			codtabela: $("#codtabela").val(),
			codproduto: $("#produto-codproduto").val()
		},
		success: function(result){
			extractScript(result);
		}
	});
}

function produto_confirmar(){
	$.ajax({
		url: "../ajax/pontooperacao_produto_confirmar.php",
		data: {
			operacao: $("#operacao").val(),
			codestabelec: $("#codestabelec").val(),
			codproduto: $("#produto-codproduto").val(),
			quantidade: $("#produto-quantidade").val()
		},
		success: function(result){
			extractScript(result);
		}
	});
}

function produto_consultar(){
	$("#modal-produtoconsulta").modal();
}

function produto_gravar(){
	if($("#produto-preco").val().length === 0 || numericValue($("#produto-preco").val()) === 0){
		$("#produto-preco").required();
		return false;
	}
	if($("#produto-quantidade").val().length === 0 || numericValue($("#produto-quantidade").val()) === 0){
		$("#produto-quantidade").required();
		return false;
	}
	var data = {
		operacao: $("#operacao").val(),
		codestabelec: $("#codestabelec").val(),
		natoperacao: $("#natoperacao").val(),
		status: __produto_status,
		seqitem: seqitem
	};
	$("#div-produto").find("input, select").each(function(){
		data[$(this).attr("id").replace("produto-", "")] = $(this).val();
	});
	$.ajax({
		async: false,
		url: "../ajax/pontooperacao_produto_gravar.php",
		data: data,
		success: function(result){
			extractScript(result);
		}
	});
}

function produto_recalcular(){
	var preco = numericValue($("#produto-preco").val());
	var quantidade = numericValue($("#produto-quantidade").val());
	var valdescto = numericValue($("#produto-valdescto").val());
	var percdescto = numericValue($("#produto-percdescto").val());
	var valacresc = numericValue($("#produto-valacresc").val());
	var percacresc = numericValue($("#produto-percacresc").val());
	var valfrete = numericValue($("#produto-valfrete").val());
	var percfrete = numericValue($("#produto-percfrete").val());

	var totalbruto = quantidade * preco;
	var totaldesconto = totalbruto * percdescto / 100 + quantidade * valdescto;
	var totalacrescimo = (totalbruto - totaldesconto) * percacresc / 100 + quantidade * valacresc;
	var totalfrete = (totalbruto - totaldesconto + totalacrescimo) * percfrete / 100 + quantidade * valfrete;
	var totalliquido = totalbruto - totaldesconto + totalacrescimo + totalfrete;

	$("#produto-totalbruto").val(totalbruto);
	$("#produto-totalliquido").val(totalliquido);
}

function produto_remover(i){
	$.ajax({
		async: false,
		url: "../ajax/pontooperacao_produto_remover.php",
		data: {
			i: i
		},
		success: function(result){
			extractScript(result);
		}
	});
}

var __produto_status = null;
function produto_status(status){
	/* Status disponiveis:
	 * 0 - Aguardando informar o codigo do produto
	 * 1 - Informando dados do produto selecionado
	 * 2 - Editando o produto selecionado
	 */
	status = (status === undefined ? __produto_status : status);
	status = parseInt(status);
	switch(status){
		case 0:
			$("#div-produto").find("input, select").disabled(true);
			$("#produto-codproduto").disabled(false);
			$("#btn-operacao-finalizar").show();
			$("#btn-operacao-cancelar").show();
			$("#btn-produto-gravar").hide();
			$("#btn-produto-cancelar").hide();
			break;
		case 1:
		case 2:
			$("#div-produto").find("input, select").disabled(false);
			$("#produto-codproduto").disabled(true);
			$("#produto-descricaofiscal").disabled(true);
			$("#produto-totalbruto").disabled(true);
			$("#produto-totalliquido").disabled(true);
			$("#btn-operacao-finalizar").hide();
			$("#btn-operacao-cancelar").hide();
			$("#btn-produto-gravar").show();
			$("#btn-produto-cancelar").show();
			break;
	}
	__produto_status = status;
}

function produto_tabela(){
	$.ajax({
		async: false,
		url: "../ajax/pontooperacao_produto_tabela.php",
		success: function(result){
			$("#table-produto tbody").html(result);
		}
	});
}

function produtoconsulta_pesquisar(){
//	if($("#produtoconsulta-pesquisa").val().length < 3){
//		$("#produtoconsulta-pesquisa").required();
//		return false;
//	}
	$.ajax({
		url: "../ajax/pontooperacao_produtoconsulta_pesquisar.php",
		data: {
			codestabelec: $("#codestabelec").val(),
			pesquisa: $("#produtoconsulta-pesquisa").val(),
			produto: $("#produtoconsulta-produto").val(),
			fornecedor: $("#produtoconsulta-fornecedor").val(),
			reffornec: $("#produtoconsulta-reffornec").val(),
			onclick: ($("#modal-parceiro").is(":visible") ? "N" : "S")
		},
		success: function(result){
			$("#produtoconsulta-table").html(result);
			$("#produtoconsulta-table [data-toggle='tooltip']").tooltip({
				html: true
			}).bind("click", function(e){
				e.stopPropagation();
			});
		}
	});
}

function produtoconsulta_selecionar(codproduto){
	$("#modal-produtoconsulta").modal("hide");
	$("#produto-codproduto").val(codproduto);
	produto_carregar();
}

function selecionarcodfunc(){
	$("#codfunc-aux").val("");
	$("#modal-selecionarcodfunc").modal("show");
}

function selecionarcodfunc_confirmar(){
	var codfunc = $("#codfunc-aux").val();
	var length = $("#codfunc option[value='"+codfunc+"']").length;

	$("#modal-selecionarcodfunc").modal("hide");

	if(length > 0){
		$("#codfunc").val(codfunc);
	}else{
		$.messageBox({
			text: "Nenhum vendedor foi encontrado com o código informado."
		});
	}
}

function temporario_carregar(){
	$.ajax({
		async: false,
		url: "../ajax/pontooperacao_temporario_carregar.php",
		success: function(result){
			extractScript(result);
			if($("#operacao").val().length > 0 && $("#codparceiro").val().length > 0){
				$("#produto-codproduto").focus();
			}else{
				temporario_limpar();
				parametro_abrir();
			}
			produto_tabela();
			operacao_cabecalho();
		}
	});
}

function temporario_gravar(){
	var data = {};
	$("#modal-parametro, #modal-parceiro").find("input, select").each(function(){
		data[$(this).attr("id")] = $(this).val();
	});
	$.ajax({
		async: false,
		url: "../ajax/pontooperacao_temporario_gravar.php",
		data: data,
		success: function(){
			operacao_cabecalho();
		}
	});
}

function temporario_limpar(){
	$.ajax({
		async: false,
		url: "../ajax/pontooperacao_temporario_limpar.php",
		success: function(){
			operacao_cabecalho();
		}
	});
}

function operacao_editaitem(item){
	$.ajax({
		async: false,
		data: {
			item: item
		},
		url: "../ajax/pontooperacao_produto_editar.php",
		success: function(result){
			extractScript(result);
		}
	});
}

// Desabilita os elementos selecionados
$.fn.disabled = function(bool){
	if(bool === undefined){
		return $(this).is(":disabled");
	}else{
		$(this).each(function(){
			var elements = $(this).parents(".form-group, .form-group-lg").find("input, select, label");
			elements = $(elements).add($(this).filter("button"));
			if(bool){
				$(elements).attr("disabled", true).addClass("disabled");
			}else{
				$(elements).removeAttr("disabled").removeClass("disabled");
			}
		});
		return this;
	}
};

// Cria um balaozinho com uma mensagem abaixo de um elemento escolhido
$.fn.inputPopover = function(text){
	//var element = $(this).parents(".form-group, .form-group-lg");
	var element = this;
	$(element).popover({
		content: text,
		placement: "auto bottom"
	}).popover("show");
	setTimeout(function(){
		$(element).popover("destroy");
	}, 2000);
};

// Permite digitar apenas numeros e virgula (se for decimal)
$.fn.numericInput = function(decimal){
	$(this).attr("numericInput", decimal).bind("keypress", function(e){
		if(e.keyCode === 8 || e.keyCode === 0){
			return true;
		}
		if((e.keyCode !== 44) && (e.keyCode < 48 || e.keyCode > 57)){
			return false;
		}
		if(e.keyCode === 44){
			if($(this).val().indexOf(",") !== -1 || $(this).attr("numericInput") === "0"){
				return false;
			}else if($(this).val().length === 0){
				$(this).val("0");
			}
		}
		return true;
	}).bind("change", function(){
		var max_dec = $(this).attr("numericInput");
		var dec = 0;
		var value = numericValue($(this).val());
		if(value.indexOf(".") > -1){
			dec = value.length - value.indexOf(",") + 1;
			if(dec > max_dec){
				dec = max_dec;
			}
		}
		//$(this).val(number_format(value, dec, ",", ""));
		$(this).val(number_format(value, dec, ".", ""));
	});
};

// Marca um campo como obrigatorio, poe na cor "vermelho"
$.fn.required = function(){
	$(this).each(function(){
		$(this).parents(".form-group, .form-group-lg").addClass("required");
	});
	return this;
};

$.messageBox = function(settings){
	settings = $.extend({
		size: "sm",
		text: "",
		close: function(){},
		buttons: [
			{
				text: "Ok",
				class: "btn-primary",
				click: function(){
					$("#modal-messagebox").modal("hide");
				}
			}
		]
	}, settings);

	var modalDialog = $("#modal-messagebox").find("modal-dialog");
	$(modalDialog).removeClass("modal-xs");
	$(modalDialog).removeClass("modal-sm");
	$(modalDialog).removeClass("modal-md");
	$(modalDialog).removeClass("modal-lg");
	$(modalDialog).addClass("modal-" + settings.size);

	$("#modal-messagebox").find("p").html(settings.text);
	$("#modal-messagebox").find(".modal-footer").children().remove();

	var button = null;
	for(var i  in settings.buttons){
		button = document.createElement("button");
		$(button).html(settings.buttons[i].text);
		$(button).bind("click", settings.buttons[i].click);

		var arrClass = ("btn " + settings.buttons[i].class).split(" ");
		for(var j in arrClass){
			$(button).addClass(arrClass[j]);
		}

		$("#modal-messagebox").find(".modal-footer").get(0).appendChild(button);
	}

	$("#modal-messagebox").modal({
		backdrop: "static",
		keyboard: false
	});

	$("#modal-messagebox").off("hidden.bs.modal");
	$("#modal-messagebox").on("hidden.bs.modal", settings.close);
};