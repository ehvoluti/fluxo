var vTipoPesquisa = "PLU";
var desabilita = false;
var supervenda = "";
var supercompra = "";
var superrateio = "";
var observacao_cupom = "";
var observacao_final = "";
var arr_cupom = new Array();
var limpar_cupom = "S";
var offset = 0;
var filtro = "N";
var pedidoautomatico_qtdeunidade_old = 0;
var it_seqitem = 0;
var it_preco;
var it_tipopesquisa = "PLU";
var super_codproduto = 0;
var emissaopropria;
var alterarpedido;
var mostraratendido = "S";

$(document).ready(function(){
	top.original_status = $("#status").html();

	// Verifica se deve continuar um pedido perdido no meio
	pedido_perdido_verificar();
});

$(document).bind("ready", function(){
	$("#codfunc").change(function(){
		if($("#codestabelec").val().length === 0){
			$.messageBox({
				type: "error",
				title: " ",
				text: "Informe o estabelecimento."
			});
			$("#codfunc").val("");
			$("#desc_codfunc").val("");
		}else{
			$.ajax({
				url: "../ajax/pedido_estabfunc.php",
				data: {
					codfunc: $("#codfunc").val(),
					codestabelec: $("#codestabelec").val(),
					operacao: $("#operacao").val()
				},
				success: function(html){
					$.loading(false);
					extractScript(html);
				}
			});
		}
	});

	$("#table_campoitem input:not(#it_seqitem),#table_campoitem select").bind("change", function(){
		$("#it_alterado").val("S");
	});

	if(operacao !== "IM"){
		$("[importacao]").hide();
	}
	if(operacao === "EX"){
		$("[exportacao]").show();
	}

	if(!in_array(operacao, ["TS", "DF", "PD"])){
		$("[tipoembalagem]").hide();
	}else{
		$("[tipoembalagem]").show();
	}

	// Aumenta o tamanho do campo para que possa ser visualizado melhor quando for entrar com um codigo de barras
	$("#it_codproduto").width("110px");
	$("#" + $("#it_codproduto").attr("description")).width("350px");

	// Usuario nao tem permissao para alterar o pedido
	if(!alterarpedido){
		$("#btnCadAlterar").remove();
	}

	// Usuario nao tem permissao para excluir o pedido
	if(!excluirpedido){
		$("#btnCadExcluir").remove();
	}

	//$("#btnCadCancelar").after("<input type=\"button\" style=\"margin-left:5px\" id=\"btnCadColetorDados\" value=\"Coletor de Dados\" onclick=\"pedido_coletordados()\" alt=\"Importar itens do coletor de dados\" title=\"Importar itens do coletor de dados\">");
	$("#btnCadCancelar").after("<div style=\"margin-left: 5px; display: inline;\"><input id=\"btnCadColetorDados\" type=\"file\" text=\"Coletor de Dados\" style=\"margin-left:5px\" /></dib>");

	if(param_geraprevenda == "S"){
		$("#btnCadClonar").after("<input type=\"button\" style=\"margin-left:4px\" id=\"btnCadGerarPreVenda\" value=\"Gerar Pre Venda\" onclick=\"gerar_prevenda()\" alt=\"Gerar Pre Venda\" title=\"Gerar Pre Venda\">");
	}

	if(in_array(operacao, ["CP", "PR"])){
		$("#btnCadColetorDados").after("<input type=\"button\" style=\"margin-left:5px\" id=\"btnCadPedidoAutomatico\" value=\"Pedido Autom&aacute;tico\" onclick=\"pedido_pedidoautomatico()\">");
		//		$("#btnCadNovaPesquisa").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnCadImportarXml\" value=\"Importar Nota Fiscal Eletr&ocirc;nica\" onclick=\"pedido_importarxml()\" alt=\"Importar XML Nota Fiscal Eletronica\" title=\"Importar XML Nota Fiscal Eletronica\">");
	}else{
		if(in_array(operacao, ["VD", "EX"])){
			$("#btnCadColetorDados").before("<input type=\"button\" style=\" margin-right: 5px;\" id=\"btnCadImportarOrcamento\" value=\"Importar Or&ccedil;amento\" onclick=\"pedido_buscarorcamento()\" alt=\"Importar Orcamento\" title=\"Importar Orcamento\">");
			$("#btnCadNovaPesquisa").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnCadImportarCupom\" value=\"Importar Cupom\" onclick=\"pedido_buscarcupom(),limpar_cupons()\" alt=\"Importar Cupom\" title=\"Importar Cupom\">");
		}
		if(operacao === "DC"){
			$("#btnCadNovaPesquisa").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnCadImportarCupom\" value=\"Importar Cupom\" onclick=\"pedido_buscarcupom(),limpar_cupons()\" alt=\"Importar Cupom\" title=\"Importar Cupom\">");
		}
		if(in_array(operacao, ["DC", "DF"])){
			$("#btnCadCancelar").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnNotaRef\" value=\"Referenciar Nota\" onclick=\"notafiscal_referenciada()\" alt=\"Referenciar Nota\" title=\"Referenciar Nota\">");
		}

		$("#tdRatIcmsSubst").hide();
		$("#totalarecolher,label[for='totalarecolher']").hide();
	}
	$("#btnCadClonar").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnCadEmitirNotaFiscal\" value=\"" + (operacao == "CP" || $('#emissaopropria').val() == "N" ? "Entrar" : "Emitir") + " Nota Fiscal\" onclick=\"pedido_abrirnotafiscal(true)\" alt=\"Emitir Nota Fiscal\" title=\"Emitir Nota Fiscal\">");
	$("#btnCadClonar").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnCadImprimirPedido\" value=\"Imprimir Pedido\" onclick=\"_pedido_imprimir()\" alt=\"Imprimir pedido\" title=\"Imprimir pedido\">");
	if(in_array(operacao, ["CP", "VD"])){
		$("#btnCadClonar").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnCadImprimirRomaneio\" value=\"Imprimir Romaneio\" onclick=\"pedido_romaneio()\" alt=\"Imprimir romaneio de entrega\" title=\"Imprimir romaneio de entrega\">");
		$("#btnCadClonar").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnCadEnviarEmail\" value=\"Enviar E-mail\" onclick=\"pedido_enviaremail()\" alt=\"Enviar pedido via e-mail\" title=\"Enviar pedido via e-mail\">");

	}
	if(in_array(operacao, ["VD","DC"])){
		//$("#btnCadClonar").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnGerarRecibo\" value=\"Gerar Recibo\" onclick=\"pedido_gerarrecibo()\" alt=\"Gerar Recibo\" title=\"Gerar recibo\">");
		$("#btnCadClonar").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnGerarRecibo\" value=\"Gerar Recibo\" onclick=\"autoriza_pedido_gerarrecibo()\" alt=\"Gerar Recibo\" title=\"Gerar recibo\">");
	}
	if(in_array(operacao, ["CP"]) && habcompradistrib == "2"){
		$("#btnCadClonar").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnCadImprimirDistribuicao\" value=\"Imprimir Distribui&ccedil;&atilde;o\" onclick=\"pedido_distribuicao_imprimir()\">");
	}
	if(in_array(operacao, ["CP", "NC"])){
		$("#finalidade").removeAttr("required");
		$("#tipoemissao").removeAttr("required");
		$("#origem").removeAttr("required");
	}
	if(in_array(operacao, ["PD"])){
		$("#btnCadColetorDados").after("<input type=\"button\" style=\"margin-left:5px\" id=\"btnCadImportarMovimento\" value=\"Importar Movimento\" onclick=\"pedido_movimento()\">");
	}

	$("#btnCadClonar").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnCadRestaurarPedido\" value=\"Restaurar Pedido\" onclick=\"pedido_restaurar()\" alt=\"Alterar status do pedido para pendende\" title=\"Alterar status do pedido para pendente\">");
	$("#btnCadClonar").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnCadCancelarPedido\" value=\"Cancelar Pedido\" onclick=\"pedido_cancelar()\" alt=\"Alterar status do pedido para cancelado\" title=\"Alterar status do pedido para cancelado\">");
	$("#btnCadClonar").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnCadLancamento\" value=\"Lan&ccedil;amentos\" onclick=\"$.lancamento({codestabelec:$('#codestabelec').val(),numpedido:$('#numpedido').val()})\" alt=\"Visualizar lan&ccedil;amentos financeiros do pedido\" title=\"Visualizar lan&ccedil;amentos financeiros do pedido\">");
	$("#btnCadClonar").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnDivergencia\" value=\"Divergências\" onclick=\"pedido_divergencia()\" alt=\"Verificar divergências no pedido\" title=\"Verificar divergências no pedido\">");
	$("#btnCadClonar").before("<input type=\"button\" style=\"margin-right:4px;\" id=\"btnCadAbrirNota\" value=\"Abrir Nota Fiscal\" onclick=\"pedido_abrirnotafiscal()\" alt=\"Visualizar Nota Fiscal\" title=\"Visualizar Nota Fiscal\">");
	$("#btnCadClonar").before("<input type=\"button\" style=\"margin-right:4px;\" id=\"btnEncerrarPedido\" value=\"Encerrar Pendencia \" onclick=\"pedido_encerrar()\" alt=\"Encerrar Pedido\" title=\"Encerrar Pedido\">");

	$("#btnCadInserir").val("Novo Pedido");
	$("#btnCadAlterar").val("Alterar Pedido");
	$("#btnCadExcluir").val("Excluir Pedido");
	$("#btnCadGravar").val("Gravar Pedido");
	$("#btnCadClonar").val("Clonar Pedido");

	if(in_array(operacao, ["TE"])){
		$("#btnCadClonar").remove();
	}

	$("#btnCadImportarOrcamento").width("150px");

	$("#divtabsped").css("padding-bottom", "5px");

	$("#btnitlista").bind("click", function(){
		$("#divitdados").animate({
			"opacity": "hide"
		}, "fast", function(){
			$("#divitlista").animate({
				"opacity": "show"
			}, "fast");
		});
	});
	$("#btnitdados").bind("click", function(){
		$("#divitlista").animate({
			"opacity": "hide"
		}, "fast", function(){
			$("#divitdados").animate({
				"opacity": "show"
			}, "fast");
		});
	});

	// Verifica se deve ficar disponivel a data de entrega por item
	if(!dtentregaporitem){
		$("#it_dtentrega,label[for='it_dtentrega']").parent().hide();
	}

	// Ao alterar a quantidade por embalagem no item, recalcula o preco da embalagem
	$("#it_qtdeunidade").bind("change", function(){
		var qtdeunidade_old = parseFloat($(this).data("oldValue").replace(",", "."));
		var qtdeunidade_new = parseFloat($(this).val().replace(",", "."));
		$("#it_preco").val(number_format(((qtdeunidade_new * parseFloat($("#it_preco").val().replace(",", "."))) / qtdeunidade_old), 4, ",", ""));
		$("#it_valdescto").val(number_format(((qtdeunidade_new * parseFloat($("#it_valdescto").val().replace(",", "."))) / qtdeunidade_old), 4, ",", ""));
		$("#it_valacresc").val(number_format(((qtdeunidade_new * parseFloat($("#it_valacresc").val().replace(",", "."))) / qtdeunidade_old), 4, ",", ""));
		$("#it_valfrete").val(number_format(((qtdeunidade_new * parseFloat($("#it_valfrete").val().replace(",", "."))) / qtdeunidade_old), 4, ",", ""));
		$("#it_valseguro").val(number_format(((qtdeunidade_new * parseFloat($("#it_valseguro").val().replace(",", "."))) / qtdeunidade_old), 4, ",", ""));
		pedido_atualizarinfoitem();
	});

	// Trata no item, que quando o tipo de tributacao for Tributado ou Isento, zera a Reducao e o Iva
	var varItAliqIcms = "0,0000";
	var varItRedIcms = "0,0000";
	var varItAliqIva = "0,0000";
	var arrTipoTrib = ["T", "I"];
	$("#it_tptribicms").bind("change", function(){
		$("#it_redicms").disabled(!in_array($(this).val(), ["F", "R"]));
		if(in_array($(this).val(), arrTipoTrib)){
			if($(this).val() == "I"){
				$("#it_aliqicms").val("0,0000");
			}
			$("#it_redicms").val("0,0000");
			$("#it_aliqiva").val("0,0000");
		}else{
			if(!in_array(operacao, ["SS", "SE"])){
				$("#it_aliqicms").val(varItAliqIcms);
				$("#it_redicms").val(varItRedIcms);
				$("#it_aliqiva").val(varItAliqIva);
			}
		}
	});

	$("#it_redicms").bind("change", function(){
		if(!in_array($("#it_tptribicms").val(), arrTipoTrib)){
			varItRedIcms = $(this).val();
		}
	});
	$("#it_aliqiva").bind("change", function(){
		if(!in_array($("#it_tptribicms").val(), arrTipoTrib)){
			varItAliqIva = $(this).val();
		}
	});


	$("#codparceiro, #codestabelec").bind("change", function(){
		if($("#codparceiro").length === 0 || $("#codestabelec").length === 0){
			return false;
		}
		pedido_dadosparceiro();
		natoperacao_filtrar(operacao);
	});

	if(in_array(operacao, ["CP", "DF", "PR", "RF"]) || (operacao == "VD" && vendareffornec)){
		$("#it_codproduto").next().attr("onclick", "").bind("click", function(){
			$(this).prev().locate({
				table: "produto",
				values: ({
					codfornec: $("#codparceiro").val()
				})
			});
		});
	}

	if(operacao === "TE"){
		$("#btnCadInserir,#btnCadAlterar,#btnCadExcluir").remove();
	}

	if(!in_array(operacao, ["DC", "VD", "RC", "EX","EC"])){
		$("[tabelapreco]").remove();
		$("#codtabela").remove();
		$("[codfornecref]").hide();
	}else if(!vendareffornec){
		$("[codfornecref]").hide();
	}

	// Verifica se nao veio de nenhum orcamento
	if($("#codorcamento").val().length > 0 && pesquisar != "S"){
		pedido_importarorcamento();
	}

	// Pesquisa o produto ao teclar ENTER
	$("#it_codproduto").bind("keypress", function(e){
		if(e.keyCode === 13){
			pedido_itempesquisar();
		}
	});

	// Ajusta o espacamento dos botoes quando houver quebra de linha
	$("#divCadButtonsAll > div").each(function(){
		var top = null;
		var visible = $(this).is(":visible");
		if(!visible){
			$(this).show();
		}
		$(this).find("input:button").each(function(){
			if(top === null){
				top = $(this).position().top;
			}else if($(this).position().top > top){
				$(this).css("margin-top", "5px");
			}
		});
		if(!visible){
			$(this).hide();
		}
	});

	// Quando nao for uma compra ou uma devolucao do compra, remove o campo de tipo de pesquisa do produto
	if(!in_array(operacao, ["CP", "DF", "IM", "PR", "RF", "AE", "AS"]) && !(operacao == "VD" && vendareffornec)){
		var a = $("#it_tipopesquisa").parent();
		var b = $(a).next().find("table:first");
		$(a).remove();
		$(b).width("300px");
	}


	// Cria botao para abrir as configuracoes de tela para digitacao dos itens
	$("#btnConfigTelaItem").css({
		"left": $("#divitdados").position().left + 850,
		"position": "absolute",
		"width": "28px",
		"padding": "1px"
	}).bind("click", function(){
		$.messageBox({
			type: "info",
			text: $("#itemconfig"),
			buttons: ({
				"Ok": function(){
					$.messageBox("close");
					$.loading(true);
					var arr_tab = [];
					var arr_block = [];
					$("#itemconfig input:checkbox").each(function(){
						if($(this).checked()){
							if($(this).attr("name") == "tab"){
								arr_tab[arr_tab.length] = $(this).attr("id").replace("conf_tab_it_", "");
							}else if($(this).attr("name") == "block"){
								arr_block[arr_block.length] = $(this).attr("id").replace("conf_block_it_", "");
							}
						}
					});
					$.ajax({
						url: "../ajax/pedido_itpedidoconf_gravar.php",
						data: ({
							operacao: operacao,
							arr_tab: arr_tab,
							arr_block: arr_block
						}),
						success: function(html){
							$.loading(false);
							extractScript(html);
							$("#it_qtdetotal,#it_precounit").disabled(true);
						}
					});
				},
				"Cancelar": function(){
					$.messageBox("close");
				}
			})
		});
	});

	// Cria botão anterior e proximo
	$("#btanterior").css({
		"left": $("#divitdados").position().left + 168,
		"position": "absolute",
		"width": "28px"
	}).disabled(true);
	$("#btproximo").css({
		"left": $("#divitdados").position().left + 704,
		"position": "absolute",
		"width": "28px"
	}).disabled(true);

	// Cria campos de configuracoes da tela de digitacao dos itens
	var campos_item = $("#table_campoitem").find("input:text,select");
	var td1 = $("#itemconfig").find("td:first");
	var td2 = $(td1).next();
	$(td1).append("&nbsp;D&nbsp;&nbsp;&nbsp;T<br>");
	$(td2).append("&nbsp;D&nbsp;&nbsp;&nbsp;T<br>");
	$(campos_item).each(function(i){
		var id_block = "conf_block_" + $(this).attr("id");
		var id_tab = "conf_tab_" + $(this).attr("id");
		var elem_str = "<input type='checkbox' id='" + id_block + "' name='block'>";
		elem_str += "<input type='checkbox' id='" + id_tab + "' name='tab'><label for='" + id_tab + "'>" + $("label[for='" + $(this).attr("id") + "']").html().replace(":", "") + "</label><br>";
		if(i < $(campos_item).length / 2){
			$(td1).append(elem_str);
		}else{
			$(td2).append(elem_str);
		}
	});

	// Atualiza os campos de configuracoes da tela de digitacao dos itens de acordo com o banco de dados
	$.ajax({
		url: "../ajax/pedido_itpedidoconf_buscar.php",
		data: ({
			operacao: operacao
		}),
		success: function(html){
			$("#itemconfig input:checkbox").attr("checked", false);
			if(html.length > 0){
				extractScript(html);
				$("#itemconfig input:checkbox").each(function(){
					//$(this).attr("checked",!$(this).is(":checked"));
					if($(this).attr("name") == "block" && $(this).is(":checked")){
						$(this).next().attr("disabled", true);
					}
				});
			}
		}
	});

	// Verifica se deve bloquear a alteracao de preco dos itens
	$("#it_preco").bind("focus", function(){
		$(this).attr("old_value", $(this).val());
	}).bind("change", function(){
		if(bloqaltpreco != "N"){
			it_preco = parseFloat(it_preco.toString().replace(",", "."));
			var it_preco_novo = parseFloat($(this).val().replace(",", "."));
			if((bloqaltpreco == 1 && it_preco > it_preco_novo) || (bloqaltpreco == 2 && it_preco < it_preco_novo)){
				return true;
			}
			$.superVisor({
				type: "venda",
				success: function(){
					$("#it_preco").focus();
				},
				fail: function(){
					$("#it_preco").val($("#it_preco").attr("old_value")).focus();
				}
			});
		}
	});

	// Verifica se o representante esta ativado
	if(!ativarrepresentante || operacao != "VD"){
		$("td[representante]").hide();
	}

	// Verifica se deve pedir senha do supervisor ao alterar o vendedora da venda
	if(operacao == "VD" && bloqclientevendedor){
		$("#codfunc").bind("change", function(){
			if($("#codfunc").attr("value_old").length > 0){
				$.superVisor({
					type: "venda",
					success: function(){
						$("#codfunc").attr("value_old", $("#codfunc").val());
					},
					fail: function(){
						$("#codfunc").val($("#codfunc").attr("value_old")).description();
					}
				});
			}
		});
	}

	// Tratamento com a natureza de operacao
	$("#natoperacao")[0].setAttribute("old_value", "");
	$("#natoperacao").bind("change", function(){
		if(interestadsupervisao && operacao == "VD" &&
				(
						$(this).attr("value_old") === undefined ||
						$(this).attr("value_old").length === 0 ||
						$(this).attr("value_old").substr(0, 1) === "5"
						) &&
				$(this).val().substr(0, 1) == "6"
				){
			$.superVisor({
				type: "venda",
				success: function(){
					pedido_atualizarnatoperacao();
				},
				fail: function(){
					$("#natoperacao").val($("#natoperacao").attr("value_old"));
				}
			});
		}else{
			pedido_atualizarnatoperacao();
		}
	});

	// Ao alterar a natureza de operacao do item
	$("#it_natoperacao").bind("change", function(){
		if($(this).val().substr(2, 1) == "9" && in_array(operacao, ["CP", "IM"])){
			$("#it_bonificado").val("S");
		}else{
			$("#it_bonificado").val("N");
		}
	});

	// Ao alterar os estabelecimentos no pedido automatico com distribuicao, deve somar no produto
	$("#grd_pedidoautomatico_distribuicao [codestabelec]").bind("change", function(){
		$("#grd_pedidoautomatico [coluna='__quantidade'][codproduto='" + $("#pedidoautomatico_codproduto").val() + "'][codestabelec='" + $(this).attr("codestabelec") + "']").val($(this).val());
		$("#grd_pedidoautomatico [coluna='quantidade'][codproduto='" + $("#pedidoautomatico_codproduto").val() + "']").val(number_format(($("#grd_pedidoautomatico [coluna='__quantidade'][codproduto='" + $("#pedidoautomatico_codproduto").val() + "']").map(function(){
			return parseFloat($(this).val().replace(",", "."));
		}).get().sum()), 4, ",", ""));
		$("#grd_pedidoautomatico input:checkbox[codproduto='" + $("#pedidoautomatico_codproduto").val() + "']").checked(parseFloat($("#grd_pedidoautomatico [coluna='quantidade'][codproduto='" + $("#pedidoautomatico_codproduto").val() + "']").val().replace(",", ".")) > 0);
	});

	if(pesquisar === "S"){
		$.cadastro.pesquisar();
	}

	$("[name=emissaopropria_escolha]").change(function(){
		$("#emissaopropria").val($(this).attr("emitir"));
		$("#chavenfe").disabled($("#emissaopropria").val() == "S" || $("#emissaopropria").val() == "");
		$("#btndownloadsefaz").disabled($("#emissaopropria_S").checked() || $("#emissaopropria").val() == "")
		if($("#emissaopropria").val() == "S"){
			$("#chavenfe").val("");
		}else{
			$("#chavenfe").focus();
		}
		if(in_array(operacao, ["PR","TS","VD"])){
			$("#emissaopropria").val("S");
			$("#emissaopropria_S").checked(true);
			$("#emissaopropria_S,#emissaopropria_N").disabled(true);
		}
	});

	// Faz os ajustes necessarios na tela de divergencia
	$("#divergencia-content").each(function(){
		// Controle de abas
		$(this).find("#tab-divergencia li").bind("click", function(){
			$(this).parents("#tab-divergencia").find("li").removeClass("active");
			$(this).addClass("active");
			$(".page-divergencia").hide();
			$($(".page-divergencia").get($(this).index())).show();
		});

		// Upload do arquivo XML
		$("#divergencia-validacaoxml-upload").bind("change", function(){
			if($(this).val().length > 0){
				pedido_divergencia_validacaoxml_upload();
			}
		});

		// Upload arquivo do layout
		$("#divergencia-importalayout-upload").bind("change", function(){
			if($(this).val().length > 0){
				pedido_divergencia_layout_upload();
			}
		});
	});

	$.gear();

	// Verifica se deve continuar um pedido perdido
	if(pedidoperdido){
		pedido_perdido_questionar();
	}

	$("[geracupom]").change(function(){
		if($("[name=geracupom]:checked").attr("geracupom") == "S"){
			$("#cupom_codcliente").disabled(false);
			$("#desc_cupom_codcliente").disabled(false);
			$("#desc_cupom_codcliente").removeAttr("readonly");
			$("#cupom_codempresa").disabled(true);
			$("#cupom_datafim").disabled(true);
			$("#cupom_finalizadora").disabled(true);
			$("#btnCupomManual").disabled(false);
			$("#btnCupomConluir").disabled(false);
		}else{
			$("#cupom_codcliente").disabled(false);
			$("#desc_cupom_codcliente").disabled(false);
			$("#desc_cupom_codcliente").removeAttr("readonly");
			$("#cupom_codempresa").disabled(false);
			$("#cupom_datafim").disabled(false);
			$("#cupom_finalizadora").disabled(false);
			$("#btnCupomManual").disabled(true);
			$("#btnCupomConluir").disabled(true);
		}
	});

	if(param_maxidesconto > 0){

		let it_descto;

		$("#it_percdescto").change(function(){
			it_descto = Number($(this).val().replace(",", "."));
			if(it_descto > Number(param_maxidesconto)){
				$.superVisor({
					type: "venda",
					success: function(){
						$("#it_percdescto").focus();
					},
					fail: function(){
						$("#it_percdescto").val("0,0000").focus();
					}
				});
			}
		});

		$("#it_valdescto").change(function(){
			let it_preco;

			it_descto = Number($(this).val().replace(",", "."));
			it_preco = Number($("#it_preco").val().replace(",", "."));

			it_descto = (it_descto /  it_preco) * 100;

			if(it_descto > Number(param_maxidesconto)){
				$.superVisor({
					type: "venda",
					success: function(){
						$("#it_valdescto").focus();
					},
					fail: function(){
						$("#it_valdescto").val("0,0000").focus();
					}
				});
			}
		});

	}

	$("#_total_liquido").bind("keyup", function(){
		if(!isNaN($(this).val().replace(",",""))){
			var _difajustar = Number($(this).val().replace(",",".")) - Number($("#_total_liquido_tmp").val().replace(",","."))
			$("#_total_ajustar").val(number_format(_difajustar, 2, ","));
		}
	})
});

function natoperacao_filtrar(operacao_pedido){
	/*
	 if(in_array(operacao_pedido, ["CP","DC","PR","TE","AE"]) && $.cadastro.status() != 4){
	 if($("#codparceiro").attr("operacao_interestadual") == "S"){
	 $("#natoperacao").attr("filter", natoperacao_filtro + " and natoperacao ilike '2%'");
	 $("#it_natoperacao").attr("filter", natoperacao_filtro + " and natoperacao ilike '2%'");
	 }else{
	 $("#natoperacao").attr("filter", natoperacao_filtro + " and natoperacao ilike '1%'");
	 $("#it_natoperacao").attr("filter", natoperacao_filtro + " and natoperacao ilike '1%'");
	 }
	 }else if(in_array(operacao_pedido, ["VD","DF","EX","TS","PD","RC","RF","NC"]) && $.cadastro.status() != 4){
	 if($("#codparceiro").attr("operacao_interestadual") == "S"){
	 $("#natoperacao").attr("filter", natoperacao_filtro + " and natoperacao ilike '6%'");
	 $("#it_natoperacao").attr("filter", natoperacao_filtro + " and natoperacao ilike '6%'");
	 }else{
	 $("#natoperacao").attr("filter", natoperacao_filtro + " and natoperacao ilike '5%'");
	 $("#it_natoperacao").attr("filter", natoperacao_filtro + " and natoperacao ilike '5%'");
	 }
	 }else{
	 $("#it_natoperacao").attr("filter", natoperacao_filtro);
	 $("#natoperacao").attr("filter", natoperacao_filtro);
	 }
	 $("#natoperacao").refreshComboBox();
	 $("#it_natoperacao").refreshComboBox();

	 if(in_array($.cadastro.status(), [2, 3])){
	 if($("#codparceiro").val().length > 0){
	 $("#natoperacao").disabled(false);
	 }else{
	 $("#natoperacao").val("");
	 $("#natoperacao").disabled(true);
	 }
	 }
	 */
}

function pedido_abrirnotafiscal(emitir){
	emitir = (typeof (emitir) == "undefined" ? false : emitir);

	openProgram(getidtable_notafiscal(operacao), "codestabelec=" + $("#codestabelec").val() + "&numpedido=" + $("#numpedido").val() + "&emitir=" + (emitir ? "S" : "N"));
}

function verificaestabelecimento(){
	if($("#codestabelec").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Para preencher a natureza de opera&ccedil;&atilde;o, preencha primeiro o estabelecimento."
		});
	}
}

function pedido_aplicarrateio_super(){

	if(operacao == "VD" && superrateio == "S" && param_maxidesconto > 0){
		$.superVisor({
			type: "venda",
			success: function(supervisor){
				if(supervisor){
					pedido_aplicarrateio();
				}else{
					$("#ratdesconto_").val("");
					$("#ratvaldesconto_").val("");
				}
			}
		});
	}else{
		if(operacao == "VD" && param_maxidesconto > 0){
			let rat_descto, rat_total, rat_percdescto;

			if($("#rattipodesconto_").val() == "V"){
				rat_descto = Number($("#ratvaldesconto_").val().replace(",", "."));
				rat_total = Number($("#totalbruto").val().replace(",", "."));

				rat_percdescto = (rat_descto /  rat_total) * 100;
			}else{
				rat_percdescto = Number($("#ratvaldesconto_").val().replace(",", "."));
			}


			if(rat_percdescto > Number(param_maxidesconto)){
				$.superVisor({
					type: "venda",
					success: function(supervisor){
						if(supervisor){
							pedido_aplicarrateio();
						}else{
							$("#ratdesconto_").val("");
							$("#ratvaldesconto_").val("");
						}
					}
				});
			}else{
				pedido_aplicarrateio();
			}
		}else{
			pedido_aplicarrateio();
		}

	}
}

function pedido_aplicarrateio(){
	$.messageBox({
		type: "alert",
		title: "Aten&ccedil;&atilde;o",
		text: "O rateio deve ser aplicado ap&oacute;s a inclus&atilde;o de todos os itens no pedido.<br>Tem certeza que deseja aplicar o rateio agora?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				if($("#ratdesconto_").val().length == 0){
					$("#ratdesconto_").val("N");
				}else if($("#ratdesconto_").val() == "S"){
					if($("#rattipodesconto_").val().length == 0){
						$.messageBox({
							type: "error",
							text: "Informe o tipo do desconto a ser rateado.",
							focusOnClose: $("#rattipodesconto_")
						});
						return false;
					}else if($("#ratvaldesconto_").val().length == 0){
						$.messageBox({
							type: "error",
							text: "Informe o valor do desconto a ser rateado.",
							focusOnClose: $("#ratvaldesconto_")
						});
						return false;
					}
				}
				if($("#ratacrescimo_").val().length == 0){
					$("#ratacrescimo_").val("N");
				}else if($("#ratacrescimo_").val() == "S"){
					if($("#rattipoacrescimo_").val().length == 0){
						$.messageBox({
							type: "error",
							text: "Informe o tipo do acr&eacute;scimo a ser rateado.",
							focusOnClose: $("#rattipoacrescimo_")
						});
						return false;
					}else if($("#ratvalacrescimo_").val().length == 0){
						$.messageBox({
							type: "error",
							text: "Informe o valor do acr&eacute;scimo a ser rateado.",
							focusOnClose: $("#ratvalacrescimo_")
						});
						return false;
					}
				}
				if($("#ratfrete_").val().length == 0){
					$("#ratfrete_").val("N");
				}else if($("#ratfrete_").val() == "S"){
					if($("#rattipofrete_").val().length == 0){
						$.messageBox({
							type: "error",
							text: "Informe o tipo do frete a ser rateado.",
							focusOnClose: $("#rattipofrete_")
						});
						return false;
					}else if($("#ratvalfrete_").val().length == 0){
						$.messageBox({
							type: "error",
							text: "Informe o valor do frete a ser rateado.",
							focusOnClose: $("#ratvalfrete_")
						});
						return false;
					}
				}
				if($("#raticmssubst_").val().length == 0 || !$("#raticmssubst_").is(":visible")){
					$("#raticmssubst_").val("N");
				}else if($("#raticmssubst_").val() == "S"){
					if($("#ratvalicmssubst_").val().length == 0){
						$.messageBox({
							type: "error",
							text: "Informe o valor do ICMS substituto a ser rateado.",
							focusOnClose: $("#ratvalicmssubst_")
						});
						return false;
					}
				}
				$.loading(true);
				$.ajax({
					url: "../ajax/pedido_aplicarrateio.php",
					data: ({
						operacao: operacao,
						totalbruto: strtofloat($("#totalbruto").val(), true),
						ratdesconto: $("#ratdesconto_").val(),
						rattipodesconto: $("#rattipodesconto_").val(),
						ratvaldesconto: strtofloat($("#ratvaldesconto_").val(), true),
						ratacrescimo: $("#ratacrescimo_").val(),
						rattipoacrescimo: $("#rattipoacrescimo_").val(),
						ratvalacrescimo: strtofloat($("#ratvalacrescimo_").val(), true),
						ratfrete: $("#ratfrete_").val(),
						rattipofrete: $("#rattipofrete_").val(),
						ratvalfrete: strtofloat($("#ratvalfrete_").val(), true),
						raticmssubst: $("#raticmssubst_").val(),
						ratvalicmssubst: strtofloat($("#ratvalicmssubst_").val(), true),
						permdescprecooferta: permitedescontooferta /* OS 5240 */
					}),
					dataType: "html",
					success: function(html){
						$.loading(false);
						$("#ratdesconto").val($("#ratdesconto_").val());
						$("#rattipodesconto").val($("#rattipodesconto_").val());
						$("#ratvaldesconto").val($("#ratvaldesconto_").val());
						$("#ratacrescimo").val($("#ratacrescimo_").val());
						$("#rattipoacrescimo").val($("#rattipoacrescimo_").val());
						$("#ratvalacrescimo").val($("#ratvalacrescimo_").val());
						$("#ratfrete").val($("#ratfrete_").val());
						$("#rattipofrete").val($("#rattipofrete_").val());
						$("#ratvalfrete").val($("#ratvalfrete_").val());
						$("#raticmssubst").val($("#raticmssubst_").val());
						$("#ratvalicmssubst").val($("#ratvalicmssubst_").val());

						if($("#ratdesconto_").val() == "S"){
							//							$("#it_codproduto").disabled(true);
							$("#ratdesconto_").disabled(true);
						}
						if($("#ratacrescimo_").val() == "S"){
							//							$("#it_codproduto").disabled(true);
							$("#ratacrescimo_").disabled(true);
						}
						if($("#ratfrete_").val() == "S"){
							//							$("#it_codproduto").disabled(true);
							$("#ratfrete_").disabled(true);
						}

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

function pedido_atualizarinfoitem(){
	var qtdeunidade = $("#it_qtdeunidade").val().replace(",", ".");
	var quantidade = $("#it_quantidade").val().replace(",", ".");
	var preco = $("#it_preco").val().replace(",", ".");
	var qtdetotal = qtdeunidade * quantidade;
	var precounit = preco / qtdeunidade;
	if(!isFinite(qtdetotal)){
		qtdetotal = 0;
	}
	if(!isFinite(precounit)){
		precounit = 0;
	}
	qtdetotal = number_format(qtdetotal, 4, ",", "");
	precounit = number_format(precounit, 4, ",", "");
	$("#it_qtdetotal").val(qtdetotal);
	$("#it_precounit").val(precounit);
}

function pedido_atualizarmodeloemail(){
	if($("#email_codmodeloemail").val().length == 0){
		$("#email_titulo,#email_corpo").val("");
	}else{
		$.ajax({
			async: false,
			url: "../ajax/pedido_atualizarmodeloemail.php",
			data: ({
				codmodeloemail: $("#email_codmodeloemail").val()
			}),
			success: function(html){
				extractScript(html);
			}
		});
	}
}

function pedido_atualizarnatoperacao(){
//	pedido_verificarnatoperacao();
	if(in_array(operacao, ["SS", "SE"])){
		return false
	}
	$("#natoperacao").attr("value_old", $("#natoperacao").val());

	// Atualiza natureza de operacao dos itens
	var numeroitens = $("#numeroitens").val();
	if($("#natoperacao").val() !== null && $("#natoperacao").val().length > 0 && !isNaN(numeroitens) && numeroitens > 0){
		$.messageBox({
			type: "alert",
			text: "Deseja alterar a natureza de opera&ccedil;&atilde;o de todos os itens para <b>" + $("#natoperacao").val() + "</b>?",
			buttons: ({
				"Sim": function(){
					$.messageBox("close");
					$.ajaxProgress({
						url: "../ajax/pedido_atualizarnatoperacao.php",
						data: ({
							operacao: operacao,
							codestabelec: $("#codestabelec").val(),
							codparceiro: $("#codparceiro").val(),
							natoperacao: $("#natoperacao").val(),
							modfrete: $("#modfrete").val()
						}),
						success: function(html){
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
}

var ajaxAtualizaTotais = null;
function pedido_atualizartotais(){
	if(ajaxAtualizaTotais !== null){
		ajaxAtualizaTotais.abort();
	}
	ajaxAtualizaTotais = $.ajax({
		url: "../ajax/pedido_atualizartotais.php",
		data: ({
			operacao: operacao,
			codparceiro: $("#codparceiro").val(),
			codestabelec: $("#codestabelec").val(),
			modfrete: $("#modfrete").val(),
			tipoajuste: $("#tipoajuste").val()
		}),
		dataType: "html",
		success: function(html){
			extractScript(html);
			ajaxAtualizaTotais = null;
			// Verifica se pode usar os botoes de acao dos produtos
			/*if($("#codparceiro").attr("disabled")){
			 $("#divitgrid img").hide();
			 }*/
			if(in_array($.cadastro.status(), [2, 3])){
				$("#divitgrid img").show();
			}else{
				$("#divitgrid img").hide();
			}

			if(gravarPedido){
				$.cadastro.salvar();
				gravarPedido = false;
			}

			if($("#totaldesconto").val().replace(",",".") > 0){
				superrateio = "S";
			}else{
				superrateio = "N";
			}
		}
	});
}

function pedido_buscarcupom_old(){
	$("#importarcupom").find("input,select").disabled(false);
	$.messageBox({
		type: "info",
		title: "Informe os dados do cupom",
		text: $("#importarcupom"),
		buttons: ({
			"Importar Cupom": function(){
				$.messageBox("close");
				if($("#cupom_codestabelec").val().length == 0){
					$.messageBox({
						type: "error",
						text: "Informe o estabelecimento.",
						afterClose: function(){
							pedido_buscarcupom();
						}
					});
				}else if($("#cupom_data").val().length == 0){
					$.messageBox({
						type: "error",
						text: "Informe a data do cupom.",
						afterClose: function(){
							pedido_buscarcupom();
						}
					});
				}else if($("#cupom_caixa").val().length == 0){
					$.messageBox({
						type: "error",
						text: "Informe o caixa do cupom.",
						afterClose: function(){
							pedido_buscarcupom();
						}
					});
				}else if($("#cupom_cupom").val().length == 0){
					$.messageBox({
						type: "error",
						text: "Informe o n&uacute;mero do cupom.",
						afterClose: function(){
							pedido_buscarcupom();
						}
					});
				}else{
					$.loading(true);
					var codestabelec = $("#cupom_codestabelec").val();
					var data = $("#cupom_data").val();
					var caixa = $("#cupom_caixa").val();
					var cupom = $("#cupom_cupom").val();
					$.cadastro.inserir();
					$.ajax({
						url: "../ajax/pedido_importarcupom.php",
						data: ({
							operacao: operacao,
							codestabelec: codestabelec,
							data: data,
							caixa: caixa,
							cupom: cupom,
							limpar_cupom: limpar_cupom
						}),
						success: function(html){
							$.loading(false);
							extractScript(html);
						}
					});
				}
			},
			"Manualmente": function(){
				$.messageBox("close");
				if($("#cupom_codestabelec").val().length == 0){
					$.messageBox({
						type: "error",
						text: "Informe o estabelecimento.",
						afterClose: function(){
							pedido_buscarcupom();
						}
					});
				}else if($("#cupom_data").val().length == 0){
					$.messageBox({
						type: "error",
						text: "Informe a data do cupom.",
						afterClose: function(){
							pedido_buscarcupom();
						}
					});
				}else if($("#cupom_caixa").val().length == 0){
					$.messageBox({
						type: "error",
						text: "Informe o caixa do cupom.",
						afterClose: function(){
							pedido_buscarcupom();
						}
					});
				}else if($("#cupom_cupom").val().length == 0){
					$.messageBox({
						type: "error",
						text: "Informe o n&uacute;mero do cupom.",
						afterClose: function(){
							pedido_buscarcupom();
						}
					});
				}else{
					var caixa = $("#cupom_caixa").val();
					var cupom = $("#cupom_cupom").val();
					var cupom_codestabelec = $("#cupom_codestabelec").val();
					$.cadastro.inserir();
					$("#cupom").val(cupom);
					$("#numeroecf").val(caixa);
					$("#codestabelec").val(cupom_codestabelec);
					$("#manualmente").val("1");
				}
			}
		})
	});
}

function pedido_buscaremail(){
	$.ajax({
		url: "../ajax/pedido_buscaremail.php",
		data: ({
			codparceiro: $("#codparceiro").val(),
			"operacao": operacao
		}),
		success: function(html){
			$("#email_destinatario").val(html);
		}
	});
}

function pedido_buscarorcamento(){
	$("#codorcamento").disabled(false).locate({
		table: "orcamento",
		afterClose: function(){
			pedido_importarorcamento();
		}
	});
}

function pedido_cancelar(){
	$.messageBox({
		type: "info",
		text: "Tem certeza que deseja alterar o status do pedido para cancelado?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				if(statussupervisao && in_array(operacao, ["CP", "VD"])){
					$.superVisor({
						type: (operacao == "VD" ? "venda" : "compra"),
						success: function(){
							pedido_cancelar_();
						}
					});
				}else{
					pedido_cancelar_();
				}
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}

function pedido_cancelar_(){
	$.loading(true);
	$.ajax({
		url: "../ajax/pedido_status.php",
		data: ({
			"codestabelec": $("#codestabelec").val(),
			"numpedido": $("#numpedido").val(),
			"status": "C"
		}),
		dataType: "html",
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function pedido_carregar(){
	pedido_statusit(0);
	$("#divitgrid").html("<label>Carregando...</label>");
	$.ajax({
		async: true,
		url: "../ajax/pedido_itpedido.php",
		data: ({
			"acao": "carregar",
			"operacao": operacao,
			"codestabelec": $("#codestabelec").val(),
			"numpedido": $("#numpedido").val(),
			"modfrete": $("#modfrete").val(),
			"cadstatus": $("#hdnCadStatus").val(),
			"mostraratendido" : mostraratendido
		}),
		dataType: "html",
		success: function(html){
			extractScript(html);
			// Verifica se pode usar os botoes de acao dos produtos
			if($("#codparceiro").disabled()){
				$("#divitgrid img").hide();
			}
			/*OS 5240 - nao permitir dar desconto quando o produto estiver em oferta*/
			if(operacao == "VD"){
				if(parseFloat($("#it_precovrjof").val()) > 0 && permitedescontooferta == "N"){
					$("#it_percdescto, #it_valdescto").disabled(true).val("0,0000");
				}
			}
			/*OS 5240*/
		}
	});
}

function pedido_mostraratendido(){
	if(mostraratendido == "N"){
		mostraratendido = "S";
		$("#btnmostraratendido").val("Mostrar todos");
		pedido_carregar();
		pedido_statusit(3);
	}else{
		mostraratendido = "N";
		$("#btnmostraratendido").val("Esconder itens atendidos");
		pedido_carregar();
		pedido_statusit(3);
	}
}

function pedido_carregarxml(arquivo){
	if($("#nfe_codestabelec").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o estabelecimento.",
			focusOnClose: $("#nfe_codestabelec")
		});
	}else if($("#nfe_tipoqtdenfe").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o tipo de quantidade dos itens da nota fiscal eletr&ocirc;nica.",
			focusOnClose: $("#nfe_tipoqtdenfe")
		});
	}else{
		$.modalWindow("close");
		$.loading(true);
		$.ajax({
			url: "../ajax/pedido_importarxml_carregar.php",
			data: ({
				operacao: operacao,
				codestabelec: $("#nfe_codestabelec").val(),
				tipoqtdenfe: $("#nfe_tipoqtdenfe").val(),
				arquivo: arquivo
			}),
			dataType: "html",
			success: function(html){
				$.loading(false);
				$("#arqxmlnfe").val("importar"); // Preenchendo o campo para nao limpar os itens quando limpar a tela
				$.cadastro.inserir();
				extractScript(html);
			}
		});
	}
}

function pedido_coletordados(){
	$.messageBox({
		type: "info",
		text: "Tem certeza que deseja importar os produtos de um coletor de dados?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				if($("#codestabelec").val().length == 0){
					$.messageBox({
						type: "error",
						text: "Informe o estabelecimento.",
						focusOnClose: $("#codestabelec")
					});
					return false;
				}else if($("#codparceiro").val().length == 0){
					$.messageBox({
						type: "error",
						text: "Informe o parceiro.",
						focusOnClose: $("#codparceiro")
					});
					return false;
				}else if($("#natoperacao").val().length == 0){
					$.messageBox({
						type: "error",
						text: "Informe a natureza de opera&ccedil;&atilde;o.",
						focusOnClose: $("#natoperacao")
					});
					return false;
				}else if($("#codtabela").is(":visible") && $("#codtabela").val().length == 0){
					$.messageBox({
						type: "error",
						text: "Informe a tabela de pre&ccedil;o.",
						focusOnClose: $("#codtabela")
					});
					return false;
				}
				$.loading(true);
				$.ajax({
					url: "../ajax/pedido_coletordados.php",
					data: ({
						operacao: operacao,
						codestabelec: $("#codestabelec").val(),
						codparceiro: $("#codparceiro").val(),
						natoperacao: $("#natoperacao").val(),
						codtabela: $("#codtabela").val(),
						tipoembal: $("#tipoembal").val(),
						dtemissao: $("#dtemissao").val()
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
}

function pedido_dadosparceiro(buscartudo, forcar){
	buscartudo = padrao(buscartudo, true);
	forcar = padrao(forcar, false);
	if($("#codparceiro").val().length > 0 && in_array($.cadastro.status(), [2, 3])){
		$.ajax({
			url: "../ajax/pedido_dadosparceiro.php",
			async: false,
			data: ({
				operacao: operacao,
				codestabelec: $("#codestabelec").val(),
				tipoparceiro: tipoparceiro,
				data: $("#dtemissao").val(),
				codparceiro: $("#codparceiro").val(),
				buscartudo: (buscartudo ? "S" : "N"),
				forcar: (forcar ? "S" : "N")
			}),
			dataType: "html",
			success: function(html){
				extractScript(html);
				if(in_array(operacao, ["SS", "SE"])){
					if(operacao === "SS"){
						$("#natoperacao").val("5.99999");
					}else{
						$("#natoperacao").val("1.99999");
					}
				}
			}
		});
	}
}

function pedido_distribuicao_imprimir(){
	window.open("../form/pedido_distribuicao.php?codestabelec=" + $("#codestabelec").val() + "&numpedido=" + $("#numpedido").val());
}

function pedido_enviaremail(){
	$("#enviaremail").find("input,select,textarea").disabled(false);
	$.messageBox({
		type: "info",
		title: "Enviar pedido via e-mail",
		text: $("#enviaremail"),
		buttons: ({
			"Enviar": function(){
				$.messageBox("close");
				$.loading(true);
				$.ajax({
					url: "../form/pedido_imprimir.php",
					data: ({
						codestabelec: $("#codestabelec").val(),
						numpedido: $("#numpedido").val(),
						enviaremail: "S",
						destinatario: $("#email_destinatario").val(),
						codmodeloemail: $("#email_codmodeloemail").val(),
						titulo: $("#email_titulo").val(),
						corpo: $("#email_corpo").val(),
						enviar_remetente: $("#enviar_remetente").checked()
					}),
					dataType: "html",
					success: function(html){
						$.loading(false);
						extractScript(html);
					}
				});
			},
			"Cancelar": function(){
				$.messageBox("close");
			}
		}),
		afterOpen: function(){
			$("#email_destinatario").focus();
		}
	});
}

function pedido_importarorcamento(){
	if($("#codorcamento").val().length > 0){
		var codorcamento = $("#codorcamento").val();
		$.cadastro.inserir();
		$.loading(true);
		$.ajax({
			url: "../ajax/pedido_importarorcamento.php",
			data: ({
				operacao: operacao,
				codorcamento: codorcamento,
				dtemissao: $("#dtemissao").val()
			}),
			dataType: "html",
			success: function(html){
				$.loading(false);
				extractScript(html);
			}
		});
	}
}

function pedido_importarxml(){
	$("#importarxml").find("input,select").disabled(false);
	$.modalWindow({
		closeButton: true,
		content: $("#importarxml"),
		title: "Importa&ccedil;&atilde;o de nota fiscal eletr&ocirc;nica",
		width: "520px"
	});
	if($("#divGradeXml").html().length == 0 && $("#nfe_codestabelec").val().length > 0){
		pedido_recarregarxml();
	}
}

function pedido_imprimir(){
	if(autorizaimppedido && $("#impresso").val() == "S" && operacao == "VD"){
		$.superVisor({
			type: "venda",
			success: function(supervisor){
				$("#superimpressao").val(supervisor);
				pedido_imprimir_(supervisor);
			}
		});
	}else{
		pedido_imprimir_();
	}
}

function pedido_imprimir_(supervisor){
	supervisor = (supervisor === undefined ? null : supervisor);

	$.postWindow("../form/pedido_imprimir.php", {
		codestabelec: $("#codestabelec").val(),
		numpedido: $("#numpedido").val(),
		supervisor: supervisor,
		tipodesc: $("#tipodesc").val()
	});

	$("#impresso").val("S");
}

function pedido_itconf_atualizarcontroles(){
	if(supervenda == "" || supercompra == ""){
		$.ajax({
			async: false,
			url: "../ajax/pesquisa_supervenda.php",
			success: function(html){
				extractScript(html);
			}
		});
	}
	$("#table_campoitem").find("input:text,select").each(function(){
		if((supervenda == "N" && operacao != "CP") || (supercompra == "N" && operacao != "VD")){
			$(this).attr("disabled", ($("#conf_block_" + $(this).attr("id")).is(":checked") ? true : false));
		}else{
			$(this).attr("disabled", false);
		}
		$(this).attr("tabindex", ($("#conf_tab_" + $(this).attr("id")).is(":checked") ? "0" : "-1"));
	});
	if(operacao == "DF" && $("#snf_idnotafiscalref").val() != ""){
		//$("#it_quantidade").attr("disabled",true);
	}
}

function pedido_itconf_elem2var(){
	$("#itemconfig input:checkbox").each(function(){
		conf_item[$(this).attr("id")] = $(this).is(":checked");
	});
}

function pedido_itconf_var2elem(){
	$("#itemconfig input:checkbox").each(function(){
		$(this).attr("checked", conf_item[$(this).attr("id")]);
	});
}

function pedido_itemcalculo(seqitem){
	seqitem = (typeof (seqitem) == "undefined" ? "" : seqitem);
	$.ajax({
		async: false,
		url: "../ajax/pedido_itemcalculo.php",
		data: ({
			operacao: operacao,
			codestabelec: $("#codestabelec").val(),
			codparceiro: $("#codparceiro").val(),
			modfrete: $("#modfrete").val(),
			seqitem: seqitem
		}),
		success: function(html){
			extractScript(html);
		}
	});
}

function pedido_itemcancelar(){
	$("#divitdados *").filter("input:text,input:hidden,select,button").val("");
	/* OS 5242 */
	$("#divitdados *").filter("input:checkbox").checked(false);
	pedido_statusit(0);
	pedido_tipopesquisa(1);
	if($("#it_codproduto").is(":visible")){
		$("#it_codproduto").focus();
	}else if($("#it_reffornec").is(":visible")){
		$("#it_reffornec").focus();
	}
}

var ajaxPedidoDesenhar = null;
function pedido_itemdesenhar(loading, atualizar_totais){
	loading = (typeof (loading) == "undefined" ? false : loading);
	atualizar_totais = (typeof (atualizar_totais) == "undefined" ? true : atualizar_totais);
	$("#totalitens,#totalpedido").val("Calculando");
	$("#divitgrid").html("<label>Carregando...</label>");
	if(ajaxPedidoDesenhar !== null){
		ajaxPedidoDesenhar.abort();
	}
	if(loading){
		$.loading(true);
	}
	ajaxPedidoDesenhar = $.ajax({
		url: "../ajax/pedido_itpedido.php",
		data: {
			"acao": "desenhar",
			"operacao": operacao,
			"codestabelec": $("#codestabelec").val(),
			"numpedido": $("#numpedido").val(),
			"modfrete": $("#modfrete").val(),
			"atualizar_totais": (atualizar_totais ? "S" : "N"),
			"cadstatus": $("#hdnCadStatus").val(),
			"mostraratendido" : mostraratendido
		},
		dataType: "html",
		success: function(html){
			if(loading){
				$.loading(false);
			}
			ajaxPedidoDesenhar = null;
			$("#divitgrid").html(html);
			pedido_seqitem();
			pedido_statusit(statusit);
			//			pedido_atualizartotais();
		}
	});
}

function pedido_itemeditar(seqitem){
	$.loading(true);
	$.ajax({
		url: "../ajax/pedido_itpedido.php",
		data: {
			"acao": "editar",
			"operacao": operacao,
			"codestabelec": $("#codestabelec").val(),
			"numpedido": $("#numpedido").val(),
			"dec_valor": dec_valor,
			"seqitem": seqitem,
			"cadstatus": $("#hdnCadStatus").val()
		},
		dataType: "html",
		success: function(html){
			$.loading(false);
			extractScript(html);
			pedido_statusit(2);
			var codproduto = $("#it_codproduto").val();
			$("#it_tipopesquisa").val("PLU").trigger("change");
			$("#it_codproduto").val(codproduto);
			$("#it_codproduto").trigger("change");

			$("#it_codproduto").description();
			$("#btnitdados").click();
			if(operacao !== "DC"){
				var elements = $("#table_campoitem").find("input, select");
				if(cupomhabilitaedicao === "N" && $("#cupom").val().length !== 0 && $("#numeroecf").val().length !== 0 && $("#manualmente").val().length === 0){
					$(elements).attr("readonly", true);
				}else{
					$(elements).attr("readonly", false);
				}
			}
			$("#btproximo").disabled(seqitem == $("#numeroitens").val());
			$("#btanterior").disabled(seqitem == "1");
			/*OS 5240 - nao permitir dar desconto quando o produto estiver em oferta*/
			if(operacao == "VD"){
				if(parseFloat($("#it_precovrjof").val()) > 0 && permitedescontooferta == "N"){
					$("#it_percdescto, #it_valdescto").disabled(true).val("0,0000");
				}
			}
			/*OS 5240*/
		}
	});
	desabilita = true;
}

function pedido_itemestoque(){
	$("#grd_estabelecimentotransf").html();
	$.modalWindow({
		title: "Selecione um Estabelecimento",
		content: $("#div_estabelecimentotransf"),
		width: "650px"
	});
	$.ajax({
		url: "../ajax/pedido_estabelecimentotransf.php",
		data: ({
			codproduto: $("#it_codproduto").val()
		}),
		success: function(html){
			$("#grd_estabelecimentotransf").html(html);
		}
	});
}

function pedido_itemexcluir(seqitem){
	$.messageBox({
		type: "info",
		text: "Tem certeza que deseja excluir o produto da lista?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				$.loading(true);
				$.ajax({
					url: "../ajax/pedido_itpedido.php",
					data: {
						"acao": "remover",
						"operacao": operacao,
						"codestabelec": $("#codestabelec").val(),
						"numpedido": $("#numpedido").val(),
						"seqitem": seqitem,
						"cadstatus": $("#hdnCadStatus").val()
					},
					dataType: "html",
					success: function(html){
						$("[num=" + seqitem + "]").remove();
						$("#divitgrid .grid").refreshGridColor();
						$.loading(false);
						extractScript(html);
						pedido_seqitem();
						pedido_atualizartotais();
					}
				});
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}

function pedido_itemproximo(proximo){
	it_seqitem = $("#it_seqitem").val();

	if($("#it_alterado").val() == "S"){
		$.messageBox({
			type: "info",
			text: "O item atual foi alterado.<br>Deseja gravar as altera&ccedil;&otilde;es?",
			buttons: ({
				"Sim": function(){
					pedido_itemgravar(false, (proximo == "P" ? parseInt(it_seqitem) + 1 : parseInt(it_seqitem) - 1));
					$.messageBox("close");
				},
				"N\u00E3o": function(){
					pedido_itemeditar(proximo == "P" ? parseInt(it_seqitem) + 1 : parseInt(it_seqitem) - 1);
					$.messageBox("close");
				},
				"Cancelar": function(){
					$.messageBox("close");
				}
			})
		});
	}else{
		pedido_itemeditar(proximo == "P" ? (parseInt(it_seqitem) + 1) : (parseInt(it_seqitem) - 1));
	}
	$("#it_alterado").val("N");
}

var ig_forcar = null;
var ig_proximo = null;
function pedido_itemgravar(forcar, proximo, baixaselecionada){
	baixaselecionada = (baixaselecionada == undefined ? "N" : baixaselecionada)
	it_pedcliente = $("#it_pedcliente").val();

	forcar = padrao(forcar, false);

	ig_forcar = forcar;
	ig_proximo = proximo;

	// Verifica se existe campo que nao esta preenchido
	desabilita = false;
	/* OS 5242 */
	var elements = $("#divitdados *").filter("input,select").not("#it_seqitem,#it_codestabelectransf,#desc_it_codproduto,#it_complemento,#it_pedcliente,#it_seqitemcliente, #it_entregaretira").filter(":visible:enabled:not([readonly])").filter("[value='']");
	if(in_array(operacao, ["SS", "SE"]) && ($("#it_idcodigoservico").val() == "" || $("#it_idcodigoservico").val().length == 0)){
		$.messageBox({
			type: "error",
			text: "Informe o servi&ccedil;o.",
			focusOnClose: $("#it_idcodigoservico")
		});
		return false;
	}

	if(in_array(operacao, ["SS", "SE"]) && ($("#it_nattributacao").val() == "" || $("#it_nattributacao").val().length == 0)){
		$.messageBox({
			type: "error",
			text: "Informe a natureza da tributa&ccedil;&atilde;o.",
			focusOnClose: $("#it_nattributacao")
		});
		return false;
	}

	if(in_array(operacao, ["SS", "SE"]) && ($("#it_issretido").val() == "" || $("#it_issretido").val().length == 0)){
		$.messageBox({
			type: "error",
			text: "Informe se o ISS é retido.",
			focusOnClose: $("#it_issretido")
		});
		return false;
	}

	if(elements.length > 0){
		var id = elements.attr("id");
		var label = $("label[for='" + id + "']").text().replace(":", "");
		$.messageBox({
			type: "error",
			text: "O campo <b>" + label + "</b> deve ser preenchido.",
			focusOnClose: $("#" + id)
		});
		return false;
	}

	if($("#it_tptribicms").val() == "R" && parseFloat($("#it_redicms").val().replace(",", ".")) == 0){
		$.messageBox({
			type: "error",
			text: "Informe o valor da redu&ccedil;&atilde;o.",
			focusOnClose: $("#it_redicms")
		});
		return false;
	}
	// Verifica a quantidade por unidade
	if((Math.floor(parseFloat($("#it_qtdeunidade").val().replace(",", ".")) * 10000) / 10000) <= 0 && (operacao != "AE" && operacao != "AS")){
		$.messageBox({
			type: "error",
			text: "Quantidade por Unidade deve ser maior que 0 (zero).",
			focusOnClose: $("#it_qtdeunidade")
		});
		return false;
	}
	// Verifica a quantidade de unidades
	if((Math.floor(parseFloat($("#it_quantidade").val().replace(",", ".")) * 10000) / 10000) <= 0 && (operacao != "AE" && operacao != "AS")){
		$.messageBox({
			type: "error",
			text: "Quantidade de Unidades deve ser maior que 0 (zero).",
			focusOnClose: $("#it_quantidade")
		});
		return false;
	}
	// Verifica o preco
	if(parseFloat($("#it_preco").val().replace(",", ".")) <= 0 && (operacao != "AE" && operacao != "AS")){
		$.messageBox({
			type: "alert",
			text: "Pre&ccedil;o deve ser maior que 0 (zero).",
			focusOnClose: $("#it_preco")
		});
		//return false;
	}
	if($("#it_natoperacao").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a natureza da opera&ccedil;&atilde;o.",
			focusOnClose: $("#it_natoperacao")
		});
		return false;
	}
	totalzero = parseFloat($("#it_quantidade").val().replace(",", ".")).toFixed(2) * parseFloat($("#it_preco").val().replace(",", ".")).toFixed(2);
	if(totalzero.toFixed(2) <= 0 & (operacao != "AE" && operacao != "AS")){
		$.messageBox({
			type: "alert",
			text: "Pre&ccedil;o vezes a quantidade deve ser maior que 0 (zero).",
			focusOnClose: $("#it_preco")
		});
		//return false;
	}

	if($("#compradistrib").val() == "S" && operacao == "CP" && $("#numdistribuicao").val().length == 0){
		$("#cd_descricaofiscal").html($("#desc_it_codproduto").val());
		$("#cd_qtdetotal").val($("#it_quantidade").val());
		$("#cd_qtdetotal,#cd_qtdedisponivel").disabled(true);
		$.loading(true);
		$.ajax({
			url: "../ajax/pedido_compradistrib_carregar.php",
			data: ({
				codestabelec: $("#codestabelec").val(),
				codproduto: $("#it_codproduto").val(),
				seqitem: $("#it_seqitem").val(),
				quantidade: $("#it_quantidade").val()
			}),
			success: function(html){
				$.loading(false);
				$("#cd_gradeestabelec").html(html);
				$.gear();
				$("#cd_gradeestabelec input[codestabelec]").bind("change", function(){
					var qtdeinformado = $("#cd_gradeestabelec input[codestabelec]").map(function(){
						return parseFloat($(this).val().replace(",", "."));
					}).get().reduce(function(a, b){
						return a + b;
					}, 0);
					$("#cd_qtdedisponivel").val(number_format((parseFloat($("#cd_qtdetotal").val().replace(",", ".")) - qtdeinformado), 4, ",", "."));
				}).first().trigger("change").focus();
			}
		});
		$.modalWindow({
			title: "Pedido com Distribui&ccedil;&atilde;o",
			content: $("#div_compradistrib"),
			width: "600px"
		});
	}else{
		pedido_itemgravar_(baixaselecionada);
	}

	if(ig_proximo == undefined){
		$("#btproximo").disabled(true);
		$("#btanterior").disabled(true);
	}
}

function pegaraliquotaservico(){
	if($("#it_idcodigoservico").val() == "" || $("#it_idcodigoservico").val().length === 0){
		return true
	}

	$.ajax({
		url: "../ajax/pegaaliquotaservico.php",
		async: false,
		dataType: "html",
		data: ({
			idcodigoservico: $("#it_idcodigoservico").val()
		}),
		success: function(html){
			extractScript(html);
		}
	});
}

function pedido_itemgravar_(baixaselecionada){
	baixaselecionada = (baixaselecionada == undefined ? "N" : baixaselecionada);
	var data = ({
		acao: "gravar",
		operacao: operacao,
		codestabelec: $("#codestabelec").val(),
		numpedido: $("#numpedido").val(),
		natoperacao_cab: $("#natoperacao").val(),
		modfrete: $("#modfrete").val(),
		codparceiro: $("#codparceiro").val(),
		forcar: (ig_forcar ? "S" : "N"),
		proximo: ig_proximo,
		"cadstatus": $("#hdnCadStatus").val(),
		baixaselecionada: baixaselecionada
	});

	$("[id^='it_']").each(function(){
		/* OS 5242 */
		if($(this).is("input:checkbox")){
			data[$(this).attr("id").substr(3)] = $(this).checked();
		}else{
			data[$(this).attr("id").substr(3)] = $(this).val().replace(",", ".");
		}
	});

	$.loading(true);
	$.ajax({
		url: "../ajax/pedido_itpedido.php",
		data: data,
		async: false,
		dataType: "html",
		success: function(html){
			$.loading(false);
			extractScript(html);

			if($("#totaldesconto").val().replace(",",".") > 0){
				superrateio = "S";
			}else{
				superrateio = "N";
			}
		}
	});
	it_seqitem = $('#it_seqitem').val();
}

// Grava o pedido com distribuicao
function pedido_compradistrib_gravar(){
	if(parseFloat($("#cd_qtdedisponivel").val().replace(",", ".")) != 0){
		$.messageBox({
			type: "error",
			text: "A quantidade distribu&iacute;da entre os estabelecimentos n&atilde;o confere com a quantidade total do item."
		});
		return false;
	}

	var data = "operacao=" + operacao + "&seqitem=" + $("#it_seqitem").val() + "&" + $("#cd_gradeestabelec input[codestabelec]").map(function(){
		return "arr_qtdeestab[" + $(this).attr("codestabelec") + "]=" + $(this).val().replace(",", ".");
	}).get().join("&");

	$.ajax({
		url: "../ajax/pedido_compradistrib_gravar.php",
		data: data,
		success: function(html){
			extractScript(html);
		}
	});
	$.modalWindow("close");
	pedido_itemgravar_();
}

// Trata a natureza de operacao no item
function pedido_itemnatoperacao(){
	if($("#it_tptribicms").val() == "F" && $("#natoperacao").val().length > 0){
		$.ajax({
			url: "../ajax/pedido_natoperacaosubst.php",
			data: ({
				natoperacao: $("#natoperacao").val()
			}),
			success: function(html){
				html = trim(html);
				if(html.length > 0){
					$("#it_natoperacao").val(html);
				}
			}
		});
	}
}

function pedido_itempesquisar(force){
	// Se deve forcar a entrada do produto na lista
	if(operacao == "DF" && $("#snf_idnotafiscalref").val() != ""){
		$.messageBox({
			type: "error",
			text: "N&atilde;o foi poss&iacute;vel adicionar mais itens a esse pedido, pois o mesmo pertence a nota fiscal de compra: " + $("#snf_numnotafis").val() + "-" + $("#snf_serie").val()
		});
		return false;
	}
	force = (typeof force === "undefined" ? false : force);

	var tipopesquisa = (tipoparceiro == "F" || (operacao == "VD" && vendareffornec) ? $("#it_tipopesquisa").val() : "PLU");
	var codproduto = $("#it_codproduto").val();
	var codestabelec = $("#codestabelec").val();
	var codparceiro = $("#codparceiro").val();
	var desc_codparceiro = $("#desc_codparceiro").val();
	var natoperacao = $("#natoperacao").val();
	var dtentrega = $("#dtentrega").val();
	var codtabela = $("#codtabela").val();
	var codfornecref = $("#codfornecref").val();
	var tipoembal = $("#tipoembal").val();
	var bonificacao = $("#bonificacao").val();
	var status = $("#status").val();
	var dtemissao = $("#dtemissao").val();
	var finalidade = $("#finalidade").val();
	var tipoemissao = $("#tipoemissao").val();
	var codcondpagto = $("#codcondpagto").val();
	var codespecie = $("#codespecie").val();
	var origem = $("#origem").val();
	desabilita = true;

	if(tipopesquisa === undefined){
		tipopesquisa = "PLU";
	}

	if(tipoparceiro == "F" && tipopesquisa.length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o tipo de pesquisa.",
			focusOnClose: $("#it_tipopesquisa")
		});
		return false;
	}else if(codproduto.length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o c&oacute;digo do produto.",
			focusOnClose: $("#it_codproduto")
		});
		return false;
	}else if(codestabelec.length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o estabelecimento.",
			afterClose: function(){
				$("#abacabecalho").click();
				$("#codestabelec").focus();
			}
		});
		return false;
	}else if(codparceiro.length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o " + (tipoparceiro == "C" ? "cliente" : (tipoparceiro == "E" ? "estabelecimento destino" : "fornecedor")) + ".",
			afterClose: function(){
				$("#abacabecalho").click();
				$("#codparceiro").focus();
			}
		});
		return false;
	}else if(!in_array(operacao, ["SS", "SE"]) && (natoperacao == null || natoperacao.length == 0)){
		$.messageBox({
			type: "error",
			text: "Informe a natureza de opera&ccedil;&atilde;o.",
			afterClose: function(){
				$("#abacabecalho").click();
				$("#natoperacao").focus();
			}
		});
		return false;
	}else if(status.length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o status do pedido.",
			afterClose: function(){
				$("#abacabecalho").click();
				$("#status").focus();
			}
		});
		return false;
	}else if(dtemissao.length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a data de emiss&atilde;o do pedido.",
			afterClose: function(){
				$("#abacabecalho").click();
				$("#dtemissao").focus();
			}
		});
		return false;
	}else if(dtentrega.length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a data de entrega do pedido.",
			afterClose: function(){
				$("#abacabecalho").click();
				$("#dtentrega").focus();
			}
		});
		return false;
	}else if(bonificacao.length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a bonifica&ccedil;&atilde;o do pedido.",
			afterClose: function(){
				$("#abacabecalho").click();
				$("#bonificacao").focus();
			}
		});
		return false;
	}else if(in_array(operacao, ["CP", "VD"]) && codcondpagto.length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a condi&ccedil;&atilde;o de pagamento do pedido.",
			afterClose: function(){
				$("#abacabecalho").click();
				$("#codcondpagto").focus();
			}
		});
		return false;
	}else if(in_array(operacao, ["CP", "VD"]) && codespecie.length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a forma de pagamento do pedido.",
			afterClose: function(){
				$("#abacabecalho").click();
				$("#codespecie").focus();
			}
		});
		return false;
	}else if(in_array(operacao, ["DC", "VD"]) && codtabela.length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a tabela de pre&ccedil;o a utilizar.",
			afterClose: function(){
				$("#abacabecalho").click();
				$("#codtabela").focus();
			}
		});
		return false;
	}else if(operacao == "VD" && tipopesquisa == "REF" && vendareffornec && $("#codfornecref").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o fornecedor refer&ecirc;ncia.",
			afterClose: function(){
				$("#abacabecalho").click();
				$("#codfornecref").focus();
			}
		});
		return false;
	}else if(!in_array(operacao, ["CP", "NC", "SS", "SE"]) && finalidade.length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a finalidade.",
			afterClose: function(){
				$("#abacabecalho").click();
				$("#finalidade").focus();
			}
		});
		return false;
	}else if(!in_array(operacao, ["CP", "NC", "SS", "SE"]) && tipoemissao.length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o tipo de emiss&atilde;o.",
			afterClose: function(){
				$("#abacabecalho").click();
				$("#tipoemissao").focus();
			}
		});
		return false;
	}else if(tipoembal.length == 0 && in_array(operacao, ["DF", "TS"])){
		$.messageBox({
			type: "error",
			text: "Informe o tipo de embalagem.",
			afterClose: function(){
				$("#abacabecalho").click();
				$("#tipoembal").focus();
			}
		});
		return false;
	}
//	else if(!in_array(operacao, ["CP", "SS", "SE"]) && origem.length == 0){
//		$.messageBox({
//			type: "error",
//			text: "Informe a origem do pedido",
//			afterClose: function(){
//				$("#abacabecalho").click();
//				$("#origem").focus();
//			}
//		});
//		return false;
//	}

	// Verifica CFOP
	if(operacao == "VD"){
		var clienteoperacao = true;
		$.ajax({
			url: "../ajax/pedido_clienteoperacao.php",
			data: ({
				codcliente: $("#codparceiro").val(),
				codestabelec: $("#codestabelec").val(),
				natoperacao: $("#natoperacao").val()

			}),
			async: false,
			success: function(html){
				if(html == "incorreto"){
					$.messageBox({
						type: "error",
						title: "Natureza da operação incorreta",
						text: "Natureza da operação incorreta, para operação interestadual de venda com cliente não contribuinte de ICMS, o correto seria usar <b>"+param_fiscal_natvendforancontrib+"</b>"
					});
					clienteoperacao = false;
				}
			}
		});
		if(!clienteoperacao){
			$("#it_codproduto").val("");
			$("#it_codproduto").triger("change");
			return false;
		}

	}

	$.loading(true);
	$.ajax({
		url: "../ajax/pedido_pesquisaitem.php",
		data: {
			"force": (force ? "S" : "N"),
			"operacao": operacao,
			"dec_valor": dec_valor,
			"tipopesquisa": tipopesquisa,
			"codproduto": codproduto,
			"codestabelec": codestabelec,
			"natoperacao": natoperacao,
			"codparceiro": codparceiro,
			"dtentrega": dtentrega,
			"codtabela": codtabela,
			"codfornecref": codfornecref,
			"tipoembal": tipoembal,
			"dtemissao": dtemissao,
			"codnegociacaopreco": $("#codnegociacaopreco").val(),
			"descontopadraoespecie": descontopadraoespecie, /*OS 5240 - enviar o codigo da forma de pagamento*/
			"permiteprecooferta": permiteprecooferta
		},
		dataType: "html",
		success: function(html){
			$.loading(false);
			extractScript(html);
			/*OS 5240 - nao permitir dar desconto quando o produto estiver em oferta*/
			if(operacao == "VD"){
				if(parseFloat($("#it_precovrjof").val()) > 0 && permitedescontooferta == "N"){
					$("#it_percdescto, #it_valdescto").disabled(true).val("0,0000");
				}
			}
			/*OS 5240*/
		}
	});
}

var ajaxPedidoLimpar = null;
function pedido_limpar(){
	if(pedidoperdido){
		return true;
	}

	if(ajaxPedidoLimpar !== null){
		ajaxPedidoLimpar.abort();
	}
	ajaxPedidoLimpar = $.ajax({
		url: "../ajax/pedido_itpedido.php",
		data: {
			"acao": "novo",
			"operacao": operacao,
			"codestabelec": $("#codestabelec").val(),
			"numpedido": $("#numpedido").val(),
			"cadstatus": $("#hdnCadStatus").val()
		},
		dataType: "html",
		success: function(html){
			ajaxPedidoLimpar = null;
			extractScript(html);
		}
	});
}

function pedido_pedidoautomatico(){
	if(param_pedido_modelopedidoauto === "1" || (param_pedido_modelopedidoauto === "3" && $("#compradistrib").val() === "N")){
		return pedido_pedidoautomatico_old();
	}

	if($("#codestabelec").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe o estabelecimento.",
			focusOnClose: $("#codestabelec")
		});
		return false;
	}else if($("#codparceiro").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe o parceiro.",
			focusOnClose: $("#codparceiro")
		});
		return false;
	}else if($("#natoperacao").val() === null || $("#natoperacao").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe a natureza de opera&ccedil;&atilde;o.",
			focusOnClose: $("#natoperacao")
		});
		return false;
	}else if($("#dtentrega").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe a data de entrega do pedido.",
			focusOnClose: $("#dtentrega")
		});
		return false;
	}

	var codestabelec = $("#codestabelec").val();
	if($("#compradistrib").val() === "S"){
		codestabelec = $("#codestabelec option").map(function(){
			return $(this).attr("value");
		}).get().filter(Boolean);
	}

	var codproduto = $("#divitlista td[title^='Código']").map(function(){
		return $(this).text();
	}).get();

	$.selecaoProduto({
		data: {
			tabela: "pedido",
			codestabelec: codestabelec,
			codfornec: $("#codparceiro").val(),
			codproduto: codproduto
		},
		success: function(data){
			$.loading(true);
			$.ajax({
				url: "../ajax/pedido_pedidoautomatico_incluir.php",
				type: "POST",
				data: {
					operacao: $("#operacao").val(),
					codestabelec: $("#codestabelec").val(),
					codparceiro: $("#codparceiro").val(),
					natoperacao: $("#natoperacao").val(),
					dtentrega: $("#dtentrega").val(),
					compradistrib: $("#compradistrib").val(),
					data: JSON.stringify(data)
				},
				success: function(result){
					$.loading(false);
					extractScript(result);
				}
			});
		}
	});
}

function pedido_pedidoautomatico_old(){
	offset = 0;
	if($("#codestabelec").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe o estabelecimento.",
			focusOnClose: $("#codestabelec")
		});
		return false;
	}else if($("#codparceiro").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe o parceiro.",
			focusOnClose: $("#codparceiro")
		});
		return false;
	}else if($("#natoperacao").val() === null || $("#natoperacao").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe a natureza de opera&ccedil;&atilde;o.",
			focusOnClose: $("#natoperacao")
		});
		return false;
	}else if($("#dtentrega").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe a data de entrega do pedido.",
			focusOnClose: $("#dtentrega")
		});
		return false;
	}

	$("#grd_pedidoautomatico").html("");
	$.modalWindow({
		closeButton: true,
		content: $("#pedidoautomatico"),
		position: {
			top: "2%"
		},
		title: "Sele&ccedil;&atilde;o Autom&aacute;tica de Produtos",
		width: "750px"
	});
	$.loading(true);
	$("#pedidoautomatico_quantidadetotal").val("0,0000");
	$("#pedidoautomatico_valortotal").val("0,0000");

	arr_codproduto = [];
	$("#divitgrid [codproduto]").each(function(i){
		arr_codproduto[i] = $(this).attr("codproduto");
	});

	$.ajax({
		url: "../ajax/pedido_pedidoautomatico_buscarprodutos.php",
		data: ({
			codestabelec: $("#codestabelec").val(),
			codfornec: $("#codparceiro").val(),
			compradistrib: $("#compradistrib").val(),
			arr_codproduto: arr_codproduto,
			offset: offset
		}),
		success: function(html){
			$.loading(false);
			$("#grd_pedidoautomatico").html(html);
			$.gear();

			// Atualiza o preco unitario
			$("#grd_pedidoautomatico [coluna=preco]").bind("change", function(){
				var codproduto = $(this).attr("codproduto");
				var qtdeunidade = $("#grd_pedidoautomatico [codproduto=" + codproduto + "][coluna='qtdeunidade']").val().replace(",", ".");
				//var preco_old = $("#grd_pedidoautomatico [codproduto=" + codproduto + "][coluna='preco']").attr("preco").replace(",", ".");
				var preco = $("#grd_pedidoautomatico [codproduto=" + codproduto + "][coluna='preco']").val().replace(",", ".");
				if(qtdeunidade > 0){
					$("#grd_pedidoautomatico [codproduto=" + codproduto + "][coluna='preco']").attr("preco", preco / qtdeunidade);
				}
			});
			if(param_notafiscal_pedautosemcaltotais == "N"){
				$("#grd_pedidoautomatico input").bind("change", function(){
					pedido_pedidoautomatico_recalculartotais();
				});
			}
			offset = 50;
			gridfixo_pedidoautomatico();

			// Alterações em quantidade ou embalagem
			$("#grd_pedidoautomatico [coluna=qtdeunidade]").bind("change", function(){
				var codproduto = $(this).attr("codproduto");
				var qtdeunidade = $("#grd_pedidoautomatico [codproduto=" + codproduto + "][coluna='qtdeunidade']").val().replace(",", ".");
				var preco = $("#grd_pedidoautomatico [codproduto=" + codproduto + "][coluna='preco']").attr("preco").replace(",", ".");

				preco = (preco * qtdeunidade);
				if(preco === 0){
					$("#grd_pedidoautomatico [codproduto=" + codproduto + "][coluna='preco']").val("0,00");
				}else{
					$("#grd_pedidoautomatico [codproduto=" + codproduto + "][coluna='preco']").val(number_format(preco, 2, ",", ""));
				}
				$("#grd_pedidoautomatico [coluna=preco][codproduto=" + codproduto + "]").change();
			});

		}
	});
	var wait_ajax = 1;
}

var ajax_pedidoautomatico_buscardados = null;
var time = "B";

function liberado(){
	time = "L";
	pedido_pedidoautomatico_buscardados($("#codestabelec").val());
}

function pedido_pedidoautomatico_buscardados(codproduto, codestabelec, modal){
	if(codproduto){
		if(String(codproduto) === $("#pedidoautomatico_codproduto").val()){
			return true;
		}else{
			$("#pedidoautomatico_codproduto").val(codproduto);
		}
	}else{
		codproduto = $("#pedidoautomatico_codproduto").val();
	}

	codestabelec = codestabelec || $("#codestabelec").val();
	modal = modal || false;

	if(modal){
		$.loading(true);
	}

	$.ajax({
		url: "../ajax/pedido_pedidoautomatico_buscardados.php",
		data: ({
			codestabelec: codestabelec,
			codfornec: $("#codparceiro").val(),
			codproduto: $("#pedidoautomatico_codproduto").val()
		}),
		success: function(html){
			ajax_pedidoautomatico_buscardados = null;
			extractScript(html);
			if(modal){
				$.loading(false);
				$.modalWindow({
					closeButton: true,
					content: $("#pedidoautomatico_dadosproduto"),
					title: "Dados do Produto",
					width: "750px"
				});
			}else{
				$("#grd_pedidoautomatico [coluna='__quantidade'][codproduto='" + codproduto + "']").each(function(){
					$("#grd_pedidoautomatico_distribuicao input[codestabelec='" + $(this).attr("codestabelec") + "']").val(number_format($(this).val().replace(",", "."), 4, ",", "."));
				});
			}
		}
	});
}

function pedido_pedidoautomatico_filtro(){
	$("#filtar_produtos select").val("");
	$.modalWindow({
		closeButton: true,
		content: $("#filtar_produtos"),
		title: "Filtro de Produtos",
		width: "600px"
	});
}

function pedido_pedidoautomatico_filtrarprodutos(){
	$.messageBox({
		type: "info",
		text: "Ao iniciar uma nova pesquisa os itens selecionados ser&atilde;o desmarcados.\nTem certeza que deseja continuar?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				$("#pedidoautomatico_valortotal").val("");
				$("#pedidoautomatico_quantidadetotal").val("");
				$.loading(true);

				arr_codproduto = [];
				$("#divitgrid [codproduto]").each(function(i){
					arr_codproduto[i] = $(this).attr("codproduto");
				});

				$.ajax({
					url: "../ajax/pedido_pedidoautomatico_buscarprodutos.php",
					data: ({
						compradistrib: $("#compradistrib").val(),
						coddepto: $("#coddepto").val(),
						codgrupo: $("#codgrupo").val(),
						codsubgrupo: $("#codsubgrupo").val(),
						marca: $("#filtro_marca").val(),
						familia: $("#familia").val(),
						foralinha: $("#foralinha").val(),
						codestabelec: $("#codestabelec").val(),
						codfornec: $("#codparceiro").val(),
						numpedido: $("#numpedido").val(),
						arr_codproduto: arr_codproduto,
						filtro: "S"
					}),
					dataType: "html",
					success: function(html){
						$("#grd_pedidoautomatico").html(html);
						if(param_notafiscal_pedautosemcaltotais === "N"){
							$("#grd_pedidoautomatico input").bind("change", function(){
								pedido_pedidoautomatico_recalculartotais();
							});
						}
						$.loading(false);
						$.modalWindow("close");
						gridfixo_pedidoautomatico();
					}
				});
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}

function pedido_pedidoautomatico_incluirprodutos(){
	$.messageBox({
		type: "info",
		text: "Tem certeza que deseja incluir os produtos selecionados para a lista de itens do pedido?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				var data = ({
					operacao: $("#operacao").val(),
					codestabelec: $("#codestabelec").val(),
					codparceiro: $("#codparceiro").val(),
					natoperacao: $("#natoperacao").val(),
					compradistrib: $("#compradistrib").val(),
					produto: []
				});
				$("#grd_pedidoautomatico input:checkbox:checked").each(function(){
					var codproduto = $(this).attr("codproduto");
					var codunidade = $("#grd_pedidoautomatico [codproduto=" + codproduto + "][coluna='codunidade']").val();
					var qtdeunidade = $("#grd_pedidoautomatico [codproduto=" + codproduto + "][coluna='qtdeunidade']").val();
					var quantidade = $("#grd_pedidoautomatico [codproduto=" + codproduto + "][coluna='quantidade']").val();
					var preco = $("#grd_pedidoautomatico [codproduto=" + codproduto + "][coluna='preco']").val();

					var __quantidade = {};
					$("#grd_pedidoautomatico [codproduto=" + codproduto + "][coluna='__quantidade']").each(function(){
						__quantidade[$(this).attr("codestabelec")] = $(this).val();
					});

					data.produto[data.produto.length] = ({
						codproduto: codproduto,
						codunidade: codunidade,
						qtdeunidade: qtdeunidade,
						quantidade: quantidade,
						preco: preco,
						__quantidade: __quantidade
					});
				});
				if(data.produto.length == 0){
					$.messageBox({
						type: "error",
						text: "Selecione os produtos que dever&atilde;o ser inclusos na lista de itens do pedido."
					});
					return false;
				}

				data = "JSONData=" + JSON.stringify(data);

				$.loading(true);
				$.ajax({
					url: "../ajax/pedido_pedidoautomatico_incluirprodutos.php",
					type: "POST",
					data: data,
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

function pedido_pedidoautomatico_recalculartotais(){
	var arr_total = $("#grd_pedidoautomatico input[type=checkbox]:checked").map(function(){
		var quantidade = parseFloat($(this).parent().parent().parent().find("[coluna='quantidade']").val().replace(",", "."));
		var preco = parseFloat($(this).parent().parent().parent().find("[coluna='preco']").val().replace(",", "."));
		var valortotal = preco * quantidade;
		return {
			quantidade: quantidade,
			valortotal: valortotal
		};
	}).get();

	var quantidadetotal = 0;
	var valortotal = 0;
	for(var i = 0; i < arr_total.length; i++){
		quantidadetotal += arr_total[i].quantidade;
		valortotal += arr_total[i].valortotal;
	}

	$("#pedidoautomatico_quantidadetotal").val(number_format(quantidadetotal), 4, ",", "");
	$("#pedidoautomatico_valortotal").val(number_format(valortotal), 4, ",", "");
}

var ajax_pedidoautomatico_ultimascompras = null;
function pedido_pedidoautomatico_ultimascompras(codproduto){
	if(ajax_pedidoautomatico_ultimascompras != null){
		ajax_pedidoautomatico_ultimascompras.abort();
	}
	$.ajax({
		url: "../ajax/pedido_pedidoautomatico_ultimascompras.php",
		data: ({
			codproduto: codproduto,
			codestabelec: codestabelec,
		}),
		success: function(html){
			ajax_pedidoautomatico_ultimascompras = null;
			$("#grd_pedidoautomatico_ultimascompras").html(html);
		}
	});
}

function pedido_pedidoautomatico_ultimascompras_estabelec(codproduto, codestabelec){
	if(ajax_pedidoautomatico_ultimascompras != null){
		ajax_pedidoautomatico_ultimascompras.abort();
	}
	$.ajax({
		url: "../ajax/pedido_pedidoautomatico_ultimascompras.php",
		data: ({
			codproduto: codproduto,
			codestabelec: codestabelec
		}),
		success: function(html){
			ajax_pedidoautomatico_ultimascompras = null;
			$("#grd_pedidoautomatico_ultimascompras").html(html);
		}
	});
}

var ajax_pedidoautomatico_vendamedia = null;
function pedido_pedidoautomatico_vendamedia(codproduto, codestabelec){
	if(ajax_pedidoautomatico_vendamedia !== null){
		ajax_pedidoautomatico_vendamedia.abort();
	}
	$.ajax({
		url: "../ajax/pedido_pedidoautomatico_vendamedia.php",
		data: ({
			codproduto: codproduto,
			codestabelec: codestabelec
		}),
		success: function(html){
			ajax_pedidoautomatico_vendamedia = null;
			$("#grd_pedidoautomatico_vendamendia").html(html);
		}
	});
}

function pedido_recarregarxml(){
	if($("#nfe_codestabelec").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o estabelecimento.",
			focusOnClose: $("#nfe_codestabelec")
		});
		return false;
	}else{
		$("#divGradeXml").html("<label>Carregando arquivos. Por favor aguarde...</label>");
		$.ajax({
			url: "../ajax/pedido_importarxml_listararquivos.php",
			data: ({
				codestabelec: $("#nfe_codestabelec").val(),
				numnotafis: $("#nfe_numnotafis").val()
			}),
			dataType: "html",
			success: function(html){
				$("#divGradeXml").html(html);
			}
		});
	}
}

function pedido_restaurar(){
	$.messageBox({
		type: "info",
		text: "Tem certeza que deseja alterar o status do pedido para pendente?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				if(in_array(operacao, ["CP", "VD"])){
					$.superVisor({
						type: (operacao == "VD" ? "venda" : "compra"),
						success: function(){
							pedido_restaurar_();
						}
					});
				}else{
					pedido_cancelar_();
				}
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}

function pedido_restaurar_(){
	$.loading(true);
	$.ajax({
		url: "../ajax/pedido_status.php",
		data: ({
			"codestabelec": $("#codestabelec").val(),
			"numpedido": $("#numpedido").val(),
			"status": "P"
		}),
		dataType: "html",
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function pedido_romaneio(){
	window.open("../form/pedido_romaneio.php?codestabelec=" + $("#codestabelec").val() + "&numpedido=" + $("#numpedido").val(), "RomPedido", "");
}

function pedido_selecionarnotafiscal_buscar(){

	if($("#emissaopropria_S").checked() == false && $("#emissaopropria_N").checked() == false){
		$.messageBox({
			type: "error",
			text: "Informe o tipo da NF, se emissão própria ou emitida por terceiros.",
			focusOnClose: $("#emissaopropria")
		});
		return false;
	}

	if($("#snf_codestabelec").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o estabelecimento.",
			focusOnClose: $("#snf_codestabelec")
		});
		return false;
	}
	if($("#snf_codparceiro").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o parceiro.",
			focusOnClose: $("#snf_codparceiro")
		});
		return false;
	}
	if($("#snf_numnotafis").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o n&uacute;mero da nota fiscal.",
			focusOnClose: $("#snf_numnotafis")
		});
		return false;
	}
	if($("#snf_serie").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a s&eacute;rie da nota fiscal.",
			focusOnClose: $("#snf_serie")
		});
		return false;
	}
	if($("#snf_natoperacao").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a natureza de opera&ccedil;&atilde;o para o novo pedido.",
			focusOnClose: $("#snf_natoperacao")
		});
		return false;
	}

	if(in_array(operacao, ["DC", "DF"]) && $("[name=emissaopropria_escolha]").length > 0 && !$("[name=emissaopropria_escolha]").is(":checked")){
		$.messageBox({
			type: "error",
			text: "Informe se é emissão própria ou emitida pelo cliente.",
			focusOnClose: $("[name=emissaopropria_escolha]")
		});
		return false;
	}

	$.ajax({
		url: "../ajax/pedido_selecionarnotafiscal_buscar.php",
		type: "POST",
		dataType: "html",
		data: ({
			operacao: operacao,
			codestabelec: $("#snf_codestabelec").val(),
			codparceiro: $("#snf_codparceiro").val(),
			numnotafis: $("#snf_numnotafis").val(),
			serie: $("#snf_serie").val(),
			dtemissao: $("#snf_dtemissao").val()
		}),
		beforeSend: function(){
			$.loading(true);
		},
		complete: function(){
			$.loading(false);
		},
		success: function(html){
			$("#grd_selecionarnotafiscal").html(html);
			if(!$("#hdn_selecionarnotafiscal").is(":visible")){
				$("#hdn_selecionarnotafiscal").animate({
					height: "toggle"
				}, "slow");
				$("[__modalwindow_body]").animate({
					height: "450px"
				}, "slow");
			}

			$("#grd_selecionarnotafiscal [type=checkbox]").change(function(){
				$("#snf_totalliquido").val("0.00");
				$("#grd_selecionarnotafiscal [type=checkbox]:checked[iditnotafiscal]").each(function(){
					$("#snf_totalliquido").val((Number($("#snf_totalliquido").val()) + Number($(this).attr("preco")) * Number($("[iditnotafiscal=" + $(this).attr("iditnotafiscal") + "][coluna=quantidade]").val().replace(",", "."))).toFixed(2));
				});
				$("#snf_totalliquido").val($("#snf_totalliquido").val().replace(".", ","));
			});

			$("#grd_selecionarnotafiscal [coluna=quantidade]").change(function(){
				$(this).parents("tr").find("[type=checkbox]").trigger("change");
			});
			$.gear();
		}
	});
}

function pedido_selecionarnotafiscal_incluir(){
	if($("#grd_selecionarnotafiscal :checkbox[iditnotafiscal]:checked").length == 0){
		$.messageBox({
			type: "error",
			text: "Nenhum produto informado para ser referenciado.",
			afterClose: function(){
				$(this).focus();
			}
		});
		return false;
	}

	//$.modalWindow("close");
	var arr_itnotafiscal = new Array();
	$("#grd_selecionarnotafiscal :checkbox[iditnotafiscal]:checked").each(function(){
		arr_itnotafiscal[arr_itnotafiscal.length] =
				$(this).attr("iditnotafiscal") + "|" + // Id do item da nota fiscal
				$("#grd_selecionarnotafiscal [iditnotafiscal='" + $(this).attr("iditnotafiscal") + "'][coluna='quantidade']").val(); // Quantidade devolvida
	});
	$.loading(true);
	$.ajax({
		url: "../ajax/pedido_selecionarnotafiscal_incluir.php",
		data: ({
			operacao: operacao,
			idnotafiscalref: $("#snf_idnotafiscalref").val(),
			codestabelec: $("#snf_codestabelec").val(),
			codparceiro: $("#snf_codparceiro").val(),
			natoperacao: $("#snf_natoperacao").val(),
			codtabela: $("#snf_codtabela").val(),
			arr_itnotafiscal: arr_itnotafiscal
		}),
		method: "POST",
		success: function(html){
			$.loading(false);
			extractScript(html);
			//limpar os controles para consultar uma outra nota fiscal
			$("#snf_numnotafis").focus();
		}
	});
}

function pedido_selecionarnotafiscal_fechar(){
	if(parseInt($("#numeroitens").val()) > 0){
		$.modalWindow('close');
	}else{
		if(in_array(operacao,["DC","DF"]) || ($("#emissaopropria_S").checked() == false && $("#emissaopropria_N").checked() == false)){
			$.cadastro.cancelar();
		}
		if(in_array(operacao,["EC","EF"]) && $("#emissaopropria_N").checked()){
			$("#codparceiro").val($("#snf_codparceiro").val());
			$("#codestabelec").val($("#snf_codestabelec").val());
			$("#codparceiro").description();
		}
		$.modalWindow('close');
	}
}

function pedido_selecionarnotafiscal_limpar(){
	$("#tbl_selecionarnotafiscal .desabilitar").disabled(true);
	$("#tbl_selecionarnotafiscal [id^='snf']").not(".desabilitar").val("");
	$("#snf_totalliquido").val("0,00");
	$("#hdn_selecionarnotafiscal").animate({
		height: "toggle"
	}, "slow");
	$("[__modalwindow_body]").animate({
		height: "175px"
	}, "slow");
}

function pedido_seqitem(){
	var i = 1;
	$("#divitgrid [grid_seqitem]").each(function(){
		$(this).html(i++);
	});
}

var statusit;
function pedido_statusit(status){
	/*
	 Status:
	 0 - Dados dos itens em branco, aguardando acao do usuario
	 1 - Inserindo um novo item
	 2 - Alterando um item
	 3 - Nao permitido nenhuma alteracao na lista (inserir, alterar, excluir)
	 */
	statusit = status;
	switch(status){
		case 0: // aguardando acao
			$("#divitdados *").filter("input,label,select").disabled(true);
			$("#it_tipopesquisa,#it_codproduto,#it_reffornec").disabled(false);
			$("#btnitinserir,#btnitlista,#btnitdados").disabled(false);
			$("#btnCadGravar,#btnCadCancelar").disabled(false);
			$("#divitgrid img").show();
			if(desabilita){
				pedido_itconf_atualizarcontroles();
			}
			break;
		case 1: // inserindo novo
			$("#divitdados *").filter("input,label,select").disabled(false);
			$("#it_tipopesquisa,#it_codproduto,#it_reffornec").disabled(true);
			$("#btnitinserir,#btnitlista,#btnitdados").disabled(true);
			//$("#btnmostraratendido").disabled(false);
			$("#btnCadGravar,#btnCadCancelar").disabled(true);
			$("#it_dtentrega").disabled(dtentregaatual);
			if(desabilita){
				pedido_itconf_atualizarcontroles();
			}
			$("#it_qtdetotal,#it_precounit").disabled(true);
			break;
		case 2: // alterando
			$("#divitdados *").filter("input,label,select").disabled(false);
			$("#it_tipopesquisa,#it_codproduto,#it_reffornec").disabled(true);
			$("#btnitinserir,#btnitlista,#btnitdados").disabled(true);
			//$("#btnmostraratendido").disabled(false);
			$("#btnCadGravar,#btnCadCancelar").disabled(true);
			if(desabilita){
				pedido_itconf_atualizarcontroles();
			}
			$("#it_qtdetotal,#it_precounit").disabled(true);
			break;
		case 3: // nao permite nenhuma alteracao na lista
			$("#divitens *").filter("input,label,select").disabled(true);
			$("#btnCadGravar,#btnCadCancelar").disabled(false);
			$("#btnmostraratendido").disabled(false);
			$("#divitgrid img").hide();
			if(desabilita){
				pedido_itconf_atualizarcontroles();
			}
			break;
	}
	$("#btnrefreshgrid").disabled(false);
}

function pedido_tipoipi(){
	$("#lblvalipi").hide();
	$("#fldvalipi").hide();
	$("#lblpercipi").hide();
	$("#fldpercipi").hide();
	if($("#it_tipoipi").val() == "F"){
		if(!in_array(operacao, ["SS", "SE"])){
			$("#lblvalipi").show();
			$("#fldvalipi").show();
		}
	}else if($("#it_tipoipi").val() == "P"){
		$("#lblpercipi").show();
		$("#fldpercipi").show();
	}
	$("#it_valipi").val("0,0000");
	$("#it_percipi").val("0,0000");
}

function pedido_tipopesquisa(tipo_attr){
	// parametro define a atribuicao do campo e variavel tipo
	// 0 -> nao recebe nada
	// 1 -> campo = variavel
	// 2 -> variavel = campo
	if(typeof (tipo_attr) == "undefined"){
		tipo_attr = 0;
	}
	if(tipoparceiro == "F" || (operacao == "VD" && vendareffornec)){
		switch(tipo_attr){
			case 0:
				break;
			case 1:
				$("#it_tipopesquisa").val(vTipoPesquisa);
				break;
			case 2:
				vTipoPesquisa = $("#it_tipopesquisa").val();
				break;
		}
		$("#it_codproduto,#" + $("#it_codproduto").attr("description")).val("");
		if($("#it_tipopesquisa").length === 0 || $("#it_tipopesquisa").val() == "PLU"){
			$("#it_codproduto").attr("identify", "produto").cwSetMask();
			$("#it_codproduto").parent().next().find("img").show();
		}else if($("#it_tipopesquisa").val() == "REF"){
			$("#it_codproduto").parent().next().find("img").hide();
			$("#it_codproduto").attr("identify", "prodfornec").unsetMask();
		}else{
			$("#it_codproduto").parent().next().find("img").hide();
			$("#it_codproduto").attr("identify", "");
		}
	}
}

function pedido_verificarnatoperacao(){
	// Verifica particularidades da natureza de operacao
	$.ajax({
		async: false,
		url: "../ajax/pedido_verificarnatoperacao.php",
		data: ({
			natoperacao: $("#natoperacao").val()
		}),
		success: function(html){
			extractScript(html);
		}
	});
}

function pedido_perdido_carregar(){
	$.ajax({
		async: false,
		url: "../ajax/pedido_perdido_carregar.php",
		data: {
			operacao: operacao
		},
		success: function(result){
			extractScript(result);
		}
	});
}

function pedido_perdido_itpedido(){
	$.ajax({
		async: false,
		url: "../ajax/pedido_perdido_itpedido.php",
		data: {
			operacao: operacao
		},
		success: function(result){
			extractScript(result);
		}
	});
}

function pedido_perdido_limpar(){
	$.ajax({
		async: false,
		url: "../ajax/pedido_perdido_limpar.php",
		data: {
			operacao: operacao
		}
	});
}

function pedido_perdido_questionar(){
	$.messageBox({
		title: "Pedido anterior não finalizado",
		type: "alert",
		text: "Foi encontrado um pedido não finalizado com os itens já informados.<br>Você deseja continuar a digitação desse pedido ou abandonar para iniciar um novo?",
		buttons: {
			"Continuar Pedido": function(){
				$.messageBox("close");
				pedido_perdido_carregar();
			},
			"Abandonar Pedido": function(){
				$.messageBox("close");
				pedidoperdido = false;
				pedido_perdido_limpar();
			}
		}
	});
}

function pedido_perdido_verificar(){
	$.ajax({
		async: false,
		url: "../ajax/pedido_perdido_verificar.php",
		data: {
			operacao: operacao
		},
		success: function(result){
			extractScript(result);
		}
	});
}

function cliente_mix(){
	if($("#codparceiro").val().length > 0){
		$.ajax({
			async: false,
			url: "../ajax/cliente_mix.php",
			data: ({
				codestabelec: $("#codestabelec").val(),
				codcliente: $("#codparceiro").val()
			}),
			success: function(html){
				extractScript(html);
			}
		});
	}
	return true;
}

function limpar_cliente(){
	$("#codparceiro").val("");
	$("#desc_codparceiro").val("");
}

function importar_cupom(){
	iscupom = "S";
	$.messageBox("close");
	if($("#cupom_codestabelec").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o estabelecimento.",
			afterClose: function(){
				$("#cupom_codestabelec").focus();
			}
		});
	}else if($("#cupom_data").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a data do cupom.",
			afterClose: function(){
				$("#cupom_data").focus();
			}
		});
	}else if($("[name=geracupom]:checked").attr("geracupom") == "S" && $("#cupom_codcliente").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a cliente.",
			afterClose: function(){
				$("#cupom_codcliente").focus();
			}
		});
	}else{
		var codestabelec = $("#cupom_codestabelec").val();
		var datacup = $("#cupom_data").val();
		var datafim = $("#cupom_datafim").val();
		var caixa = $("#cupom_caixa").val();
		var cupom = $("#cupom_cupom").val();
		var codcliente = $("#cupom_codcliente").val();
		var codempresa = $("#cupom_codempresa").val();
		var codfinaliz = $("#cupom_codfinaliz").val();

		observacao_cupom = datacup + " ," + caixa + " ," + cupom;

		if(arr_cupom.indexOf(observacao_cupom) != -1){
			$.messageBox({
				type: "error",
				text: "O cupom informado já foi importado.",
				afterClose: function(){
					$("#cupom_cupom").focus();
				}
			});
			return false;
		}
		$.loading(true);

		$.cadastro.inserir();

		var url_ajax = "";
		if($("[name=geracupom]:checked").attr("geracupom") == "S"){
			url_ajax = "../ajax/pedido_importarcupom.php"
		}else{
			url_ajax = "../ajax/pedido_importarcupom_convenio.php"
		}

		$.ajax({
			url: url_ajax,
			data: ({
				operacao: operacao,
				codestabelec: codestabelec,
				data: datacup,
				datafim: datafim,
				caixa: caixa,
				cupom: cupom,
				codparceiro: codcliente,
				codempresa: codempresa,
				codfinaliz: codfinaliz,
				limpar_cupom: limpar_cupom
			}),
			success: function(html){
				$.loading(false);
				extractScript(html);
			}
		});
	}
}

function observacao_pedido(){
	if(observacao_final.length <= 0){
		observacao_final = "Data, Caixa, Cupom\r\n";
	}
	observacao_final += observacao_cupom + "\r\n";
	$("#observacaofiscal").val(observacao_final);
	return true;
}

function manualmente(){
	$.messageBox("close");
	if($("#cupom_codestabelec").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o estabelecimento."
		});
	}else if($("#cupom_data").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a data do cupom."
		});
	}else if($("#cupom_caixa").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o caixa do cupom."
		});
	}else if($("#cupom_cupom").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o n&uacute;mero do cupom."
		});
	}else{
		var caixa = $("#cupom_caixa").val();
		var cupom = $("#cupom_cupom").val();
		var data = $("#cupom_data").val();
		var cupom_codestabelec = $("#cupom_codestabelec").val();

		$.cadastro.inserir();
		observacao_cupom = data + " ," + caixa + " ," + cupom;
		observacao_pedido();

		$("#cupom").val(cupom);
		$("#numeroecf").val(caixa);
		$("#codestabelec").val(cupom_codestabelec);
		$("#manualmente").val("1");
		$.modalWindow('close');
	}
}

function pedido_buscarcupom(){
	$("#box_importarcupom input, select").disabled(false);
	$.modalWindow({
		title: "Buscar Cupom",
		content: $("#div_importarcupom"),
		width: "700px",
		height: "280px"
	});
	$("#cupom_codcliente").disabled(false);
	$("#cupom_codempresa").disabled(true);
	$("#cupom_datafim").disabled(true);
	$("[geracupom=S]").checked(true);
}

function limpar_cupons(){
	limpar_cupom = "S";
	observacao_cupom = "";
	observacao_final = "";
	arr_cupom = [];
}

function recebimento(){
	$.ajax({
		url: "../ajax/pedido_recebimento_verificar.php",
		data: ({
			codestabelec: $("#codestabelec").val(),
			numpedido: $("#numpedido").val()
		}),
		success: function(html){
			$.modalWindow({
				closeButton: true,
				title: "Recebimento",
				content: $("#div_recebimento"),
				width: "800px",
				height: "400px"
			});
			$("#grid_recebimento").html(html);
		}
	});
}

function verificar_recebimento(){
	$.ajax({
		url: "../ajax/pedido_recebimento.php",
		data: ({
			codestabelec: $("#codestabelec").val(),
			numpedido: $("#numpedido").val()
		}),
		success: function(html){
			extractScript(html);
		}
	});
}

function itpedidoconf_buscar(){
	$.ajax({
		url: "../ajax/pedido_itpedidoconf_buscar.php",
		data: ({
			operacao: operacao
		}),
		success: function(html){
			$("#itemconfig input:checkbox").checked(false);
			if(html.length > 0){
				extractScript(html);
				$("#itemconfig input:checkbox").each(function(){
					//$(this).attr("checked",!$(this).is(":checked"));
					if($(this).attr("name") === "block" && $(this).checked()){
						$(this).next().disabled(true);
					}
				});
			}
		}
	});
}

function gridfixo_pedidoautomatico(){
	grid = "#grd_pedidoautomatico";

	if($(grid + " table tbody").css("position") !== "absolute"){
		$(grid + " table tbody").css("position", "absolute");
		$(grid + " table tbody").css("width", "727px");
		$(grid + " table tbody").css("height", "136px");
		$(grid + " table tbody").css("overflow-y", "scroll");
		$(grid + " table tbody").css("overflow-x", "auto");
	}

	$(grid + " table thead tr th").each(function(i){
		i++;
		if(i < $(grid + " tbody tr:first td").length){
			$(grid + " table tbody tr td:nth-child(" + (i) + ")").width($(this).width());
			$(grid + " table tbody tr td:nth-child(" + (i) + ") div").width($(this).width() - 1);
		}else{
			$(grid + " table tbody tr td:nth-child(" + (i) + ")").width($(this).width() - 17);
			$(grid + " table tbody tr td:nth-child(" + (i) + ") div").width($(this).width() - 18);
		}
	});

	wait_ajax = 1;

	$("#grd_pedidoautomatico tbody").unbind("scroll").bind("scroll", function(){
		if($(this).scrollTop() + $(this).height() + 50 >= $(this).get(0).scrollHeight && wait_ajax === 1 && filtro === "N"){
			wait_ajax = 0;

			arr_codproduto = [];
			$("#divitgrid [codproduto]").each(function(i){
				arr_codproduto[i] = $(this).attr("codproduto");
			});

			$.ajax({
				url: "../ajax/pedido_pedidoautomatico_buscarprodutos.php",
				data: ({
					codestabelec: $("#codestabelec").val(),
					codfornec: $("#codparceiro").val(),
					compradistrib: $("#compradistrib").val(),
					numpedido: $("#numpedido").val(),
					arr_codproduto: arr_codproduto,
					offset: offset
				}),
				success: function(html){
					if($("#grd_pedidoautomatico table").length === 0){
						$("#grd_pedidoautomatico").html(html);
					}else{
						$("#grd_pedidoautomatico > table").append(html);
					}
					$.gear();

					if(param_notafiscal_pedautosemcaltotais === "N"){
						$("#grd_pedidoautomatico input").bind("change", function(){
							pedido_pedidoautomatico_recalculartotais();
						});
					}

					offset += 50;
					wait_ajax = 1;

					gridfixo_pedidoautomatico();

					$("#grd_pedidoautomatico [coluna=preco]").bind("change", function(){
						var codproduto = $(this).attr("codproduto");
						var qtdeunidade = $("#grd_pedidoautomatico [codproduto=" + codproduto + "][coluna='qtdeunidade']").val().replace(",", ".");
						var preco_old = $("#grd_pedidoautomatico [codproduto=" + codproduto + "][coluna='preco']").attr("preco").replace(",", ".");
						var preco = $("#grd_pedidoautomatico [codproduto=" + codproduto + "][coluna='preco']").val().replace(",", ".");
						if(qtdeunidade > 0){
							$("#grd_pedidoautomatico [codproduto=" + codproduto + "][coluna='preco']").attr("preco", preco / qtdeunidade);
						}
					});

					// Atualiza o preco unitario
					$("#grd_pedidoautomatico [coluna=qtdeunidade]").unbind("change");
					$("#grd_pedidoautomatico [coluna=qtdeunidade]").bind("change", function(){
						var codproduto = $(this).attr("codproduto");
						var qtdeunidade = $("#grd_pedidoautomatico [codproduto=" + codproduto + "][coluna='qtdeunidade']").val().replace(",", ".");
						var preco = $("#grd_pedidoautomatico [codproduto=" + codproduto + "][coluna='preco']").attr("preco").replace(",", ".");

						preco = preco * qtdeunidade;
						if(preco === 0){
							$("#grd_pedidoautomatico [codproduto=" + codproduto + "][coluna='preco']").val("0,00");
						}else{
							$("#grd_pedidoautomatico [codproduto=" + codproduto + "][coluna='preco']").val(number_format(preco, 2, ",", ""));
						}
						$("#grd_pedidoautomatico [coluna=preco][codproduto=" + codproduto + "]").change();
					});
				}
			});
		}
	}).focus();

	$("#grd_pedidoautomatico [coluna=qtdeunidade], [coluna=quantidade]").unbind("keypress");
	$("#grd_pedidoautomatico [coluna=qtdeunidade], [coluna=quantidade]").keypress(function(){
		$("#grd_pedidoautomatico [codproduto=" + $(this).attr("codproduto") + "][type='checkbox']").checked("true");
	});
}

function pedido_divergencia(){
	pedido_divergencia_romaneio_carregar();
	$("#tab-divergencia li:first").click();
	$.modalWindow({
		content: $("#divergencia"),
		width: "700px"
	});
	$(".button-upload").disabled(false);
}

function pedido_divergencia_romaneio_aceitar(){
	$.superVisor({
		type: "compra",
		success: function(){
			$.changeValue({
				table: "pedido",
				key: [$("#codestabelec").val(), $("#numpedido").val()],
				columns: {
					"divergenciaromaneio": "N"
				},
				success: function(){
					$("#divergenciaromaneio").val("N");
					pedido_divergencia_sincronizar();
				},
				fail: function(error){
					$.messageBox({
						type: "error",
						text: error
					});
				}
			});
		}
	});
}

function pedido_divergencia_romaneio_carregar(){
	$.ajax({
		url: "../ajax/pedido_divergencia_romaneio_carregar.php",
		data: ({
			codestabelec: $("#codestabelec").val(),
			numpedido: $("#numpedido").val(),
			divergenciaromaneio: $("#divergenciaromaneio").val()
		}),
		success: function(html){
			$("#grid-divergencia-romaneio").html(html);
			pedido_divergencia_sincronizar();
		}
	});
}

function pedido_divergencia_sincronizar(){
	var arr = ["divergenciavalidacaoxml", "divergenciaromaneio"];
	for(var i = 0; i < arr.length; i++){
		var tab = $("#tab-" + arr[i]);
		$(tab).find(".fa").hide();
		switch($("#" + arr[i]).val()){
			case "":
				$(tab).find(".fa-question-circle").show();
				break;
			case "N":
				$(tab).find(".fa-check-circle").show();
				break;
			case "S":
				$(tab).find(".fa-minus-circle").show();
				break;
		}
	}

	$("#divergencia-validacaoxml-upload,#divergencia-importalayout-upload").disabled(false);
	$("#divergencia-validacaoxml-aceitar").disabled($("#divergenciavalidacaoxml").val() !== "S");
	$("#divergencia-romaneio-aceitar").disabled($("#divergenciaromaneio").val() !== "S");
}

function pedido_divergencia_validacaoxml_aceitar(){
	$.superVisor({
		type: "compra",
		success: function(){
			$.changeValue({
				table: "pedido",
				key: [$("#codestabelec").val(), $("#numpedido").val()],
				columns: {
					"divergenciavalidacaoxml": "N"
				},
				success: function(){
					$("#divergenciavalidacaoxml").val("N");
					pedido_divergencia_sincronizar();
				},
				fail: function(error){
					$.messageBox({
						type: "error",
						text: error
					});
				}
			});
		}
	});
}

function pedido_divergencia_validacaoxml_upload(){
	var filename = null;
	$.ajax({
		async: false,
		url: "../ajax/importarnotafiscal_upload.php",
		data: ({
			codestabelec: $("#codestabelec").val()
		}),
		success: function(result){
			filename = result;
		}
	});
	$("#divergencia-validacaoxml-upload").upload({
		filename: filename,
		onComplete: function(){
			$.loading(true);
			$.ajax({
				url: "../ajax/pedido_divergencia_validacaoxml_validar.php",
				async: false,
				data: {
					codestabelec: $("#codestabelec").val(),
					numpedido: $("#numpedido").val(),
					filename: filename
				},
				success: function(result){
					$.loading(false);
					$("#grid-divergencia-validacaoxml").html(result);
				}
			});
		}
	});
}


function pedido_divergencia_layout_upload(){
	$("#divergencia-importalayout-upload").upload({
		filename: "../temp/upload/pedidoromaneio.txt",
		onComplete: function(){
			$.loading(true);
				$.ajax({
					async: true,
					url: "../ajax/pedido_romaneio_importarlayout.php",
					type: "POST",
					dataType: "html",
					data: ({
						operacao: operacao,
						codestabelec: $("#codestabelec").val(),
						numpedido: $("#numpedido").val()
					}),
					success: function(html){
						$.loading(false);
						extractScript(html);
					}
				});
		}
	});
}

function pedido_encerrar(){
	$.ajax({
		async: true,
		url: "../ajax/notafiscal_pedido_qtdeatendida.php",
		data: ({
			codestabelec: $("#codestabelec").val(),
			numpedido: $("#numpedido").val(),
			operacao: $("#operacao").val()
		}),
		dataType: "html",
		success: function(html){
			extractScript(html);
		}
	});
}

function emissaopropria_(ep){
	emissaopropria = ep;
	$.cadastro.inserir();
}

function alerta_continuar(texto){
	$.messageBox({
		type: "alert",
		title: "Aten&ccedil;&atilde;o",
		text: texto,
		buttons: ({
			"Sim": function(){
				pedido_itemgravar(true, ig_proximo);
				$.messageBox("close");
			},
			"N\u00E3o": function(){
				pedido_itemcancelar();
				$.messageBox("close");
			}
		})
	});
}

function tabelapreco(){
	if($("#codparceiro").val().length > 0){
		$.ajax({
			async: true,
			url: "../ajax/notafiscal_tabelapreco.php",
			data: ({
				codparceiro: $("#codparceiro").val()
			}),
			dataType: "html",
			success: function(html){
				extractScript(html);
			}
		});
	}
}

function pedido_movimento(){
	if($("#codestabelec").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe o estabelecimento.",
			afterClose: function(){
				$("#abacabecalho").click();
				$("#codestabelec").focus();
			}
		});
		return false;
	}else if($("#codparceiro").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe o parceiro.",
			afterClose: function(){
				$("#abacabecalho").click();
				$("#codparceiro").focus();
			}
		});
		return false;
	}else if($("#natoperacao").val() === null || $("#natoperacao").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe a natureza da operação.",
			afterClose: function(){
				$("#abacabecalho").click();
				$("#natoperacao").focus();
			}
		});
		return false;
	}else if($("#tipoembal").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe o tipo de embalagem.",
			afterClose: function(){
				$("#abacabecalho").click();
				$("#tipoembal").focus();
			}
		});
		return false;
	}else if($("#dtentrega").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe a data de entrega.",
			afterClose: function(){
				$("#abacabecalho").click();
				$("#dtentrega").focus();
			}
		});
		return false;
	}

	$.modalWindow({
		closeButton: true,
		content: $("#importarmovimentacao"),
		title: "Importação de Movimentação",
		width: "768px"
	});
}

function pedido_movimento_filtrar(){
	$.loading(true);
	$.ajax({
		async: true,
		url: "../ajax/pedido_movimento_filtrar.php",
		data: ({
			codestabelec: $("#mov_codestabelec").val(),
			codtpdocto: $("#mov_codtpdocto").val(),
			dtmovtoini: $("#mov_dtmovtoini").val(),
			dtmovtofim: $("#mov_dtmovtofim").val(),
			coddepto: $("#mov_coddepto").val(),
			codgrupo: $("#mov_codgrupo").val(),
			codsubgrupo: $("#mov_codsubgrupo").val()
		}),
		dataType: "html",
		success: function(html){
			var grid = $("#grid_movimento");
			var modalbody = $(grid).parents("[__modalwindow_body]");

			$(grid).show().animate({
				height: "235px"
			}, "slow", function(){
				$("#btn_movimento").css("display", "block");
			}).css("overflow", "auto");

			$(modalbody).animate({
				height: "494px"
			}, "slow");

			$("#grid_movimento").html(html);

			$("#grid_movimento :checkbox").change(function(){
				if($("#grid_movimento [codproduto]:checked").length > 0){
					$("#btnMovimentoIncluir").disabled(false);
				}else{
					$("#btnMovimentoIncluir").disabled(true);
				}
			});
			$("#btnMovimentoIncluir").disabled(true);

			$.loading(false);
		}
	});
}

function pedido_movimento_incluir(){
	$.loading(true);
	var arr_produto = [];
	$("#grid_movimento [codproduto]:checked").each(function(){
		arr_produto.push([$(this).attr("codproduto"), $(this).attr("quantidade"), $(this).attr("qtdeunidade"), $(this).attr("codunidade"), $(this).attr("preco")]);
	});

	$.ajax({
		async: true,
		url: "../ajax/pedido_movimento_incluir.php",
		type: "POST",
		dataType: "html",
		data: ({
			operacao: operacao,
			codestabelec: $("#codestabelec").val(),
			codparceiro: $("#codparceiro").val(),
			natoperacao: $("#natoperacao").val(),
			codtabela: $("#codtabela").val(),
			tipoembal: $("#tipoembal").val(),
			custoatual: $("#custo_atual").checked(),
			custotabela: $("#custo_tabela").checked(),
			arr_produto: arr_produto
		}),
		success: function(html){
			extractScript(html);
			$("#grid_movimento").html("");
			$.loading(false);
		}
	});
}

function pedido_movimento_concluir(){
	$.modalWindow("close");
}

function alerta_continuar_supervisor(texto){
	$.messageBox({
		type: "alert",
		title: "Aten&ccedil;&atilde;o",
		text: texto,
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				$.superVisor({
					type: "venda",
					success: function(){
						pedido_itemgravar(true, ig_proximo);
					},
					fail: function(){
						pedido_itemcancelar();
					}
				});
			},
			"N\u00E3o": function(){
				pedido_itemcancelar();
				$.messageBox("close");
			}
		})
	});
}

function notafiscal_referenciada(){
	$("#hdn_selecionarnotafiscal").hide();
	$.modalWindow({
		closeButton:false,
		title:"Selecione a nota fiscal para refer&ecirc;ncia",
		content:$("#div_selecionarnotafiscal"),
		width:"800px"
	});

	$("#emissaopropria_N, #emissaopropria_S").checked(false).trigger("change");
	$("#chavenfe").disabled(true);
	$("#btndownloadsefaz").disabled(true);
	if(in_array(operacao,["DF","TS","DC"])){
		$("#emissaopropria_S").checked(true).trigger("change");
	}

	$("#snf_totalliquido").disabled(true).val("0,00");
	if(in_array(operacao, ["EC", "EF"])){
		$("#snf_numnotafis").disabled(true);
		$("#snf_serie").disabled(true);
		$("#snf_dtemissao").disabled(true);
		$("#snf_natoperacao").disabled(true);
		$("#emissaopropria_N").checked(true).trigger("change");
	}

	if(param_notafiscal_devolucaosemref === "N" && in_array(operacao, ["DC", "DF"])){
		$("#semref").hide(true);
	}
}

function gerar_prevenda(){
	$.ajax({
		async: true,
		url: "../ajax/orcamento_prevenda.php",
		type: "POST",
		dataType: "html",
		data: ({
			codorcamento: $("#codorcamento").val(),
			numpedido: $("#numpedido").val(),
			codestabelec: $("#codestabelec").val()
		}),
		success: function(html){
			extractScript(html);
			$.loading(false);
		}
	});
}

function downloadxml(){
	if($("#emissaopropria_N").checked()){
		if($("#chavenfe").val().length < 44){
			$.messageBox({
				type: "error",
				text: "Chave da NF-e não é valida.",
				afterClose: function(){
					$("#chavenfe").focus();
				}
			});
			return false;
		}

		if($("#snf_codestabelec").val().length  == 0){
			$.messageBox({
				type: "error",
				text: "Informe o estabelecimento destinatário da NF-e.",
				afterClose: function(){
					$("#codestabelec").focus();
				}
			});
			return false;
		}

		$.ajax({
			url: "../ajax/notafiscal_downloadxml_sefaz.php",
			type: "POST",
			dataType: "html",
			data:{
				chavenfe: $("#chavenfe").val(),
				codestabelec: $("#snf_codestabelec").val(),
				operacao: operacao
			},
			beforeSend: function(){
				$.loading(true);
			},
			success: function(html){
				extractScript(html);
				if($("#snf_codparceiro").val().length > 0 ){
					$("#snf_codparceiro").description();
				}
			},
			complete: function(){
				$.loading(false);
			}
		});
	}
}

function autoriza_pedido_gerarrecibo(){
    if(autorizaimppedido && $("#reciboimpresso").val() == "S"){
       $.superVisor({
            type: "venda",
            success: function(){
                pedido_gerarrecibo();
            },
            fail: function(){

            }
		});
    }else{
        pedido_gerarrecibo();
    }
    $("#reciboimpresso").val("S");
}

function pedido_gerarrecibo(){
	$.ajax({
		url: "../ajax/pedido_gerarrecibo.php",
		type: "POST",
		dataType: "html",
		data:{
			codestabelec: $("#codestabelec").val(),
			numpedido: $("#numpedido").val()
		},
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
}

/* OS 5241 */
function pedido_aplicarrateio_totalfinalpedido(){
	$("[name=descontoacrescimo]").disabled(false);
	$("#descontoacrescimo_D").checked(true);

	$("#_total_bruto").val($("#totalbruto").val()).disabled(true);
	$("#_total_desconto").val($("#totaldesconto").val()).disabled(true);
	$("#_total_acrescimo").val($("#totalacrescimo").val()).disabled(true);
	$("#_total_liquido").val($("#totalliquido").val());
	$("#_total_liquido_tmp").val($("#_total_liquido").val());
	$("#_total_ajustar").val("0,00").disabled(true);

	$.modalWindow({
		closeButton:false,
		title:"Ajuste do Total Final do Pedido",
		content:$("#div_rateiopelototalpedido"),
		width:"370px"
	});
	$("#_total_liquido").focus().select();
}

/* OS 5241 */
function pedido_ajustar_valor_liquido(){
	if($("#_total_liquido").val().length == 0 || isNaN($("#_total_liquido").val().replace(",","."))){
		$.messageBox({
			type: "error",
			text: "Total liquido informado para o pedido não é válido.",
			afterClose: function(){
				$("#_total_liquido").focus().select();
			}
		});
		return false;
	}
	var _total_bruto, _total_liquido, _total_liquido_tmp, _val_desc_acres, _perc_desc_acres, _total_desconto;
	_total_bruto = Number($("#_total_bruto").val().replace(",", "."));
	_total_liquido = Number($("#_total_liquido").val().replace(",", "."));
	_total_liquido_tmp = Number($("#_total_liquido_tmp").val().replace(",", "."));
	_total_desconto = Number($("#_total_desconto").val().replace(",", "."));
	_val_desc_acres = _total_liquido - _total_liquido_tmp;
	if(_val_desc_acres < 0){
		$("#rattipodesconto_").val("V");
		$("#ratvaldesconto_").val(number_format(Math.abs(_val_desc_acres), 2,","));
		$("#ratdesconto_").val("S");
		$.modalWindow('close');
		pedido_aplicarrateio_super();
	}else{
		if(_total_desconto > 0){
			$("#rattipodesconto_").val("V");
			$("#ratvaldesconto_").val(number_format(_val_desc_acres * -1, 2,","));
			$("#ratdesconto_").val("S");
			$.modalWindow('close');
			pedido_aplicarrateio() //Quando o arredondamento for para cima, não pedir autorizacao
		}else{
			$("#rattipoacrescimo_").val("V");
			$("#ratvalacrescimo_").val(number_format(_val_desc_acres, 2,","));
			$("#ratacrescimo_").val("S");
			$.modalWindow('close');
			pedido_aplicarrateio_super();
		}
	}
}

/* OS 5242 */
function definetitle(checkbox){
	if($(checkbox).checked()){
		$(checkbox).attr("title","Retira");
	}else{
		$(checkbox).attr("title","Entrega");
	}

	$.ajax({
		url: "../ajax/pedido_itpedido_define_entrega_retira.php",
		type: "POST",
		data: {
			codproduto: $(checkbox).attr("codproduto"),
			operacao: operacao,
			entregaretira: ($(checkbox).checked() ? "R" : "E")
		},
		success: function(){

		},
		error: function(){
			if($(checkbox).checked()){
				$(checkbox).checked(false);
				$(checkbox).attr("title","Entrega");
			}else{
				$(checkbox).checked(true);
				$(checkbox).attr("title","Retira");
			}
		}
	});
}