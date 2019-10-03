var supervenda = "";
var superrateio = "";

$(document).ready(function(){

	$("#btnCadRetornar1").before("<input type=\"button\" style=\"margin-right:4px; margin-bottom:4px\" id=\"btnCadImprimirOrcamento\" value=\"Imprimir Or\u00E7amento\" onclick=\"orcamento_imprimir()\" alt=\"Imprimir or\u00E7amento\" title=\"Imprimir or\u00E7amento\">");
	$("#btnCadRetornar1").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnCadEnviarEmail\" value=\"Enviar E-mail\" onclick=\"orcamento_enviaremail()\" alt=\"Enviar or\u00E7amento via e-mail\" title=\"Enviar or\u00E7amento via e-mail\">");
	$("#btnCadRetornar1").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnCadGerarPedido\" value=\"Gerar Pedido\" onclick=\"orcamento_gerarpedido()\" title=\"Gerar pedido a partir do or\u00E7amento\">");
	$("#btnCadRetornar1").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnCadGerarPreVenda\" value=\"Gerar Pr&eacute;-venda\" onclick=\"orcamento_gerarprevenda()\" title=\"Gerar pre-venda a partir do or\u00E7amento\">");
	$("#btnCadRetornar1").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnCadRestaurarOrcamento\" value=\"Restaurar Or\u00E7amento\" onclick=\"orcamento_restaurar()\" title=\"Alterar status do or\u00E7amento para pendente\">");
	$("#btnCadRetornar1").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnCadConcluirOrcamento\" value=\"Concluir Or\u00E7amento\" onclick=\"orcamento_concluir()\" title=\"Alterar status do or\u00E7amento para conclu\u00EEdo\">");
	$("#btnCadRetornar1").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnCadCancelarOrcamento\" value=\"Cancelar Or\u00E7amento\" onclick=\"orcamento_cancelar()\" title=\"Alterar status do or\u00E7amento para cancelado\">");
	$("#btnCadRetornar1").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnAbrirPedido\" value=\"Abrir Pedido\" onclick=\"orcamento_abrirpedido()\" title=\"Abrir Pedido\">");
	$("#btnCadInserir").val("Novo Or\u00E7amento");
	$("#btnCadAlterar").val("Alterar Or\u00E7amento");
	$("#btnCadExcluir").val("Excluir Or\u00E7amento");
	$("#btnCadGravar").val("Gravar Or\u00E7amento");
	$("#btnCadCancelar").val("Cancelar Or\u00E7amento");
	//$("#btnCadAlterar").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnGerarRecibo\" value=\"Gerar Recibo\" onclick=\"orcamento_gerarrecibo()\" alt=\"Gerar Recibo\" title=\"Gerar recibo\">");
	$("#btnCadAlterar").before("<input type=\"button\" style=\"margin-right:4px\" id=\"btnGerarRecibo\" value=\"Gerar Recibo\" onclick=\"autoriza_orcamento_gerarrecibo()\" alt=\"Gerar Recibo\" title=\"Gerar recibo\">");

	$("#divtabsorc").css("padding-bottom", "5px");

	top.original_status = $("#status").html();

	$("#btnitlista").bind("click", function(){
		$("#divitdados").animate({"opacity": "hide"}, "fast", function(){
			$("#divitlista").animate({"opacity": "show"}, "fast");
		});
	});
	$("#btnitdados").bind("click", function(){
		$("#divitlista").animate({"opacity": "hide"}, "fast", function(){
			$("#divitdados").animate({"opacity": "show"}, "fast", function(){
				$("#it_codproduto").focus();
			});
		});
	});
	$("#btnrefreshgrid").bind("click", function(){
		orcamento_itemdesenhar();
	});

	$("#it_codproduto").parent().attr("nowrap", true);

	$("#codcliente").bind("change", function(){
		orcamento_carregarcliente();
	});

	$("#codfunc").bind("change", function(){
		$.ajax({
			url: "../ajax/orcamento_funcionario.php",
			data: {
				codfunc: $("#codfunc").val()
			},
			success: function(result){
				extractScript(result);
			}
		});
	});
    /*
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
    */
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
					codestabelec: $("#codestabelec").val()
				},
				success: function(html){
					$.loading(false);
					extractScript(html);
				}
			});
		}
	});
	operacao = "OC";
	$("#btnConfigTelaItem").css({
		"left": $("#divitdados").position().left + 850,
		"position": "absolute",
		"width": "28px"
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
						url: "../ajax/orcamento_itorcamentoconf_gravar.php",
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
		url: "../ajax/orcamento_itorcamentoconf_buscar.php",
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
			orcamento_itconf_atualizarcontroles();
		}
	});

	$("#codcliente").change(function(){
		orcamento_carregarcliente();
	});

	$("input:file").bind("change", function(){
		arqorcamento_upload();
		$(this).upload({
			dirname: "../temp/upload/orcamento/" + $("#codorcamento").val() + "/",
			onComplete: function(){
				$.messageBox({
					type: "success",
					text: "Arquivo enviado com sucesso!",
				});
				arqorcamento_listar();
				return true;
			}
		});
	});

	$("#itemconfig input:checkbox").bind("click", function(){
		if($(this).attr("name") === "block" && $(this).is(":checked")){
			proximo = $(this).next();
			$(proximo).disabled(true);
		}else if($(this).attr("name") === "block" && !$(this).is(":checked")){
			proximo = $(this).next();
			$(proximo).disabled(false);
		}
	});

	/*OS 5240 - ao selecionar uma forma de pagamento para saber se a mesma tem uma condição de pagamento vinculada*/
	$("#codespecie").bind("change", function(){
		if($(this).val().length > 0){
			var codcondpagamento = $("#codespecie option:selected").attr("codcondpagto");
			/*if(codcondpagamento != "" && codcondpagamento != undefined){*/
				if(codcondpagamento.length > 0){
					$("#codcondpagto").val(codcondpagamento);
				}
				$("#codcondpagto").disabled($("#codespecie option:selected").attr("permalterarcondpagto") == "N");
				descontopadraoespecie = $("#codespecie option:selected").attr("percdescpadrao");
				if(!isNaN(descontopadraoespecie)){
					descontopadraoespecie = parseFloat(descontopadraoespecie);
				}
				$("#codcondpagto").trigger("change");
			/*}*/
		}else{
			$("#codcondpagto").disabled(false);
			$("#codcondpagto").trigger("change");
		}
	});

	$("#codcondpagto").bind("change", function(){

		if($(this).val().length > 0){
			permiteprecooferta = $("#codcondpagto option:selected").attr("permiteprecooferta");
			permitedescontooferta = $("#codcondpagto option:selected").attr("permdescprecooferta");
		}else{
			permiteprecooferta = "S"
		}
	});
});

$.cadastro.after.limpar = function(){
	orcamento_statusit(3);
	$("#btnaplicarrateio, #btnrateiopelototalorcamento").disabled(true);
	$("#status").html(original_status);
	$("#orcprev").val("O");
	$("#tr_complemento").hide();
    $("#reciboimpresso").val("");
};

$.cadastro.after.inserir = function(){
	orcamento_statusit(0);
	$("#divitlista").hide();
	$("#divitdados").show();
	$("#status *").remove(":not([value=''],[value='P'],[value='L'])");
	$("#btnaplicarrateio, #btnrateiopelototalorcamento").disabled(false);
	$("#status").val("P");
	$("#dtemissao").val(auxdata);
	$("#codfunc").val(WCodFunc).description();
	$("#abacabecalho").click();
    $("#reciboimpresso").val("N");

	if(diasvalorcamento.length > 0){
		$.ajax({
			url: "../ajax/dataservidor.php",
			data: {param: diasvalorcamento},
			success: function(html){
				$("#dtvalidade").val(html)
			}
		});
	}
	itorcamentoconf_buscar();
	verifica_bloqcabecalho();
}

$.cadastro.after.cancelar = function(){
	if($.cadastro.status() == 2){
		orcamento_limpar();
	}
	$("#abacabecalho").click();
    $("#reciboimpresso").val("");
};

$.cadastro.after.alterar = function(){
	orcamento_statusit(0);
	$("#divitlista").hide();
	$("#divitdados").show();
	$("#status *").remove(":not([value=''],[value='P'],[value='L'],[value='E'])");
	$("#btnaplicarrateio, #btnrateiopelototalorcamento").disabled(false);
	$("#divitgrid img").show();
};

$.cadastro.after.carregar = function(){
	orcamento_itemdesenhar();
	orcamento_statusit(3);
	$("#divitdados").hide();
	$("#divitlista").show();
	status = $("#status").val();

	if(in_array(status, ["P", "E"])){
		$("#btnCadAlterar,#btnCadExcluir,#btnCadCancelarOrcamento,#btnCadConcluirOrcamento,#btnCadGerarPedido,#btnCadGerarPreVenda,#btnCadEnviarEmail").show();
		$("#btnAbrirPedido,#btnCadRestaurarOrcamento").hide();
	}else if(status === "L"){
		$("#btnCadAlt=erar,#btnCadExcluir,#btnCadCancelarOrcamento,#btnCadConcluirOrcamento,#btnCadEnviarEmail,#btnCadGerarPedido,#btnCadGerarPreVenda").show();
		$("#btnAbrirPedido").hide();
	}else if(status === "C"){
		$("#btnCadRestaurarOrcamento").show();
		$("#btnAbrirPedido,#btnCadAlterar,#btnCadExcluir,#btnCadCancelarOrcamento,#btnCadConcluirOrcamento,#btnCadEnviarEmail,#btnCadGerarPedido,#btnCadGerarPreVenda").hide();
	}else if(status === "A"){
		$("#btnAbrirPedido").show();
		$("#btnCadAlterar,#btnCadExcluir,#btnCadRestaurarOrcamento,#btnCadCancelarOrcamento,#btnCadConcluirOrcamento,#btnCadGerarPedido").hide();
	}

	$("#codcliente").description();
	$("#totalbruto2").val($("#totalbruto").val());
	$("#totalliquido2").val($("#totalliquido").val());
	$("#ratdesconto_").val($("#ratdesconto").val());
	$("#ratvaldesconto_").val($("#ratvaldesconto").val());
	$("#ratacrescimo_").val($("#ratacrescimo").val());
	$("#ratvalacrescimo_").val($("#ratvalacrescimo").val());
	arqorcamento_listar();

	$("#codfunc").trigger("change");
};

$.cadastro.after.retornar = function(){
	orcamento_limpar();
};

$.cadastro.after.salvar = function(){
	$.ajax({
		url: "../ajax/orcamento_statuscliente.php",
		data: {codcliente: $("#codcliente").val()},
		success: function(html){
			extractScript(html);
		}
	});
    $("#reciboimpresso").val("");
};

function orcamento_itconf_elem2var(){
	$("#itemconfig input:checkbox").each(function(){
		conf_item[$(this).attr("id")] = $(this).is(":checked");
	});
}

function orcamento_itconf_var2elem(){
	$("#itemconfig input:checkbox").each(function(){
		$(this).attr("checked", conf_item[$(this).attr("id")]);
	});
}

function itorcamentoconf_buscar(){
	$.ajax({
		url: "../ajax/orcamento_itorcamentoconf_buscar.php",
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

function orcamento_itconf_atualizarcontroles(){
	if(supervenda == ""){
		$.ajax({
			async: false,
			url: "../ajax/pesquisa_supervenda.php",
			success: function(html){
				extractScript(html);
			}
		});
	}
	$("#table_campoitem").find("input:text,select").each(function(){
		if(supervenda == "N"){
			$(this).attr("disabled", ($("#conf_block_" + $(this).attr("id")).is(":checked") ? true : false));
		}else{
			$(this).attr("disabled", false);
		}
		$(this).attr("tabindex", ($("#conf_tab_" + $(this).attr("id")).is(":checked") ? "0" : "-1"));
	});
}
/*
function orcamento_itconf_atualizarcontroles(){
	$("#table_campoitem").find("input:text,select").each(function(){

		$(this).attr("tabindex", ($("#conf_" + $(this).attr("id")).is(":checked") ? "0" : "-1"));
	});
}
*/

function verifica_bloqcabecalho(){
	if(bloqcabecalho.especificacoes.length > 0){$("#especificacoes").val(bloqcabecalho.especificacoes).disabled(true);}
	if(bloqcabecalho.tipopreco.length > 0){$("#tipopreco").val(bloqcabecalho.tipopreco).disabled(true);}
	if(bloqcabecalho.origemreg.length > 0){$("#origemreg").val(bloqcabecalho.origemreg).disabled(true);}
	if(bloqcabecalho.codmoeda.length > 0){$("#codmoeda").val(bloqcabecalho.codmoeda).disabled(true);}
	if(bloqcabecalho.tipo.length > 0){$("#tipo").val(bloqcabecalho.tipo).disabled(true);}
	if(bloqcabecalho.dtemissao.length > 0){
		if(bloqcabecalho.dtemissao == "current_date"){
			bloqcabecalho.dtemissao = auxdata;
		}
		$("#dtemissao").val(bloqcabecalho.dtemissao).disabled(true);
	}
}

function orcamento_atualizatotais(){
	$.ajax({
		url: "../ajax/orcamento_atualizatotais.php",
		success: function(html){
			extractScript(html);
			// Verifica se pode usar os botoes de acao dos produtos
			if($("#data").attr("disabled")){
				$("#divitgrid img").hide();
			}

            if($("#totaldesconto").val().replace(",",".") > 0){
				superrateio = "S";
			}else{
				superrateio = "N";
			}
		}
	});
}

function orcamento_atualizainfoitem(){
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
	qtdetotal = number_format(qtdetotal, 2, ",", "");
	precounit = number_format(precounit, 2, ",", "");
	$("#it_qtdetotal").val(qtdetotal);
	$("#it_precounit").val(precounit);
}

function orcamento_cancelar(){
	$.messageBox({
		type: "info",
		title: "Cancelar Or&ccedil;amento",
		text: "Tem certeza que deseja alterar o status do or&ccedil;amento para cancelado?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				$.loading(true);
				var codorcamento = $("#codorcamento").val();
				$.ajax({
					url: "../ajax/orcamento_status.php",
					data: ({
						"codorcamento": codorcamento,
						"status": "C"
					}),
					dataType: "html",
					success: function(html){
						$.loading(false);
						$.cadastro.limpar();
						$("#codorcamento").val(codorcamento)
						$.cadastro.pesquisar();
						$.messageBox({
							type: "success",
							text: "Status do or&ccedil;amento alterado para cancelado."
						});
					}
				});
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}

function orcamento_carregarcliente(){
	$.ajax({
		url: "../ajax/orcamento_carregarcliente.php",
		data: ({
			codcliente: $("#codcliente").val()
		}),
		success: function(html){
			extractScript(html);
		}
	});
}

function orcamento_concluir(){
	$.messageBox({
		type: "info",
		title: "Conclu&iacute;r Or&ccedil;amento",
		text: "Tem certeza que deseja alterar o status do or&ccedil;amento para conclu&iacute;do?<br><b>Aten&ccedil;&atilde;o:</b> N&atilde;o ser&aacute; gerado nenhum pedido de vendas para o or&ccedil;amento.",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				$.loading(true);
				var codorcamento = $("#codorcamento").val();
				$.ajax({
					url: "../ajax/orcamento_status.php",
					data: ({
						"codorcamento": codorcamento,
						"status": "A"
					}),
					dataType: "html",
					success: function(html){
						$.loading(false);
						$.cadastro.limpar();
						$("#codorcamento").val(codorcamento)
						$.cadastro.pesquisar();
						$.messageBox({
							type: "success",
							text: "Status do or&ccedil;amento alterado para atendido."
						});
					}
				});
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}

function orcamento_atualizarmodeloemail(){
	if($("#email_codmodeloemail").val().length == 0){
		$("#email_titulo,#email_corpo").val("");
	}else{
		$.ajax({
			async: false,
			url: "../ajax/atualizarmodeloemail.php",
			data: ({
				codmodeloemail: $("#email_codmodeloemail").val()
			}),
			success: function(html){
				extractScript(html);
			}
		});
	}
}

function orcamento_enviaremail(){
	$("#enviaremail").find("input,select,textarea").disabled(false);
	$.messageBox({
		type: "info",
		title: "Enviar or&ccedil;amento via e-mail",
		text: $("#enviaremail"),
		buttons: ({
			"Enviar": function(){
				$.messageBox("close");
				$.loading(true);
				$.ajax({
					url: "../form/orcamento_imprimir.php",
					data: ({
						codorcamento: $("#codorcamento").val(),
						enviaremail: "S",
						destinatario: $("#email_destinatario").val(),
						codmodeloemail: $("#email_codmodeloemail").val(),
						titulo: $("#email_titulo").val(),
						corpo: $("#email_corpo").val(),
						enviaranexo: ($("#enviaranexo").checked() ? "S" : "N")
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
			$.ajax({
				url: "../ajax/orcamento_carregarcliente.php",
				data: ({
					codcliente: $("#codcliente").val()
				}),
				dataType: "html",
				success: function(html){
					extractScript(html);
				}
			});
		}
	});
}

function orcamento_gerarpedido(){
	openProgram("PedVenda", "codorcamento=" + $("#codorcamento").val());
}

function orcamento_gerarprevenda(){
	$.loading(true);
	$.ajax({
		url: "../ajax/orcamento_prevenda.php",
		data: ({
			codorcamento: $("#codorcamento").val()
		}),
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function orcamento_imprimir(){
	window.open("../form/orcamento_imprimir.php?codorcamento=" + $("#codorcamento").val(), "ImpOrcamento", "");
}

function orcamento_itemcancelar(){
	$("#divitdados *").filter("input:text,select").val("");
	orcamento_statusit(0);
	$("#it_tipopesquisa").next().focus();
}

function orcamento_itemdesenhar(){
	$("#totalitens,#totalorcamento").val("Calculando");
	$("#divitgrid").html("<label>Carregando...</label>");
	$.ajax({
		url: "../ajax/orcamento_itorcamento.php",
		data: {
			"acao": "desenhar"
		},
		dataType: "html",
		success: function(html){
			$("#divitgrid").html(html);
			orcamento_atualizatotais();
			if($.cadastro.status() == 1){
				$("#divitgrid img").hide();
			}
		}
	});
}

function orcamento_itemeditar(codproduto){
	$.loading(true);
	$.ajax({
		url: "../ajax/orcamento_itorcamento.php",
		data: {
			"acao": "editar",
			"codproduto": codproduto
		},
		dataType: "html",
		success: function(html){
			$.loading(false);
			extractScript(html);
			orcamento_statusit(2);
			$("#it_codproduto").description();
			$("#btnitdados").click();
		}
	});
}

function orcamento_itemexcluir(codproduto){
	$.messageBox({
		type: "info",
		text: "Tem certeza que deseja excluir o produto da lista?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				$.loading(true);
				$.ajax({
					url: "../ajax/orcamento_itorcamento.php",
					data: {
						"acao": "remover",
						"codproduto": codproduto
					},
					dataType: "html",
					success: function(html){
						$.loading(false);
						extractScript(html);
						orcamento_atualizatotais()
					}
				});
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}

function orcamento_itemgravar(){
	// Verifica se existe campo que nao esta preenchido
	var elements = $("#divitdados *").filter("input,select").filter(":visible:enabled:not(#desc_it_codproduto,#it_complemento,[readonly])").filter("[value='']");
	if($(elements).length > 0){
		var element = $(elements).get(0);
		$.messageBox({
			type: "error",
			text: "O campo <b>" + $("label[for='" + $(element).attr("id") + "']").text().replace(":", "") + "</b> deve ser preenchido.",
			focusOnClose: element
		});
		return false;
	}
	// Passa o valor dos campos para as variaveis
	var codproduto = $("#it_codproduto").val();
	var bonificado = $("#it_bonificado").val();
	var codestabelec = $("#codestabelec").val();
	var codunidade = $("#it_codunidade").val();
	var qtdeunidade = $("#it_qtdeunidade").val().replace(",", ".");
	var quantidade = $("#it_quantidade").val().replace(",", ".");
	var preco = $("#it_preco").val().replace(",", ".");
	var valdescto = $("#it_valdescto").val().replace(",", ".");
	var percdescto = $("#it_percdescto").val().replace(",", ".");
	var valacresc = $("#it_valacresc").val().replace(",", ".");
	var percacresc = $("#it_percacresc").val().replace(",", ".");
	var valfrete = $("#it_valfrete").val().replace(",", ".");
	var percfrete = $("#it_percfrete").val().replace(",", ".");
	var complemento = $("#it_complemento").val().replace(",", ".");
	var codcliente = $("#codcliente").val();
	var precovrjof = $("#it_precovrjof").val().replace(",", ".");
	var precovrj = $("#it_precovrj").val().replace(",", ".");
	// verifica quantidade por unidade
	if(qtdeunidade <= 0){
		$.messageBox({
			type: "error",
			text: "Quantidade por Unidade deve ser maior que 0 (zero).",
			focusOnClose: $("#it_qtdeunidade")
		});
		return false;
	}
	// verifica quantidade de unidades
	if(quantidade <= 0){
		$.messageBox({
			type: "error",
			text: "Quantidade de Unidades deve ser maior que 0 (zero).",
			focusOnClose: $("#it_quantidade")
		});
		return false;
	}
	// verifica preco
	if(preco <= 0){
		$.messageBox({
			type: "error",
			text: "Pre&ccedil;o deve ser maior que 0 (zero).",
			focusOnClose: $("#it_preco")
		});
		return false;
	}
	$.loading(true);
	$.ajax({
		url: "../ajax/orcamento_itorcamento.php",
		data: {
			"acao": "gravar",
			"codproduto": codproduto,
			"codestabelec": codestabelec,
			"bonificado": bonificado,
			"codunidade": codunidade,
			"qtdeunidade": qtdeunidade,
			"quantidade": quantidade,
			"preco": preco,
			"valdescto": valdescto,
			"percdescto": percdescto,
			"valacresc": valacresc,
			"percacresc": percacresc,
			"valfrete": valfrete,
			"percfrete": percfrete,
			"complemento": complemento,
			"codcliente": codcliente,
			"precovrjof": precovrjof,
			"precovrj": precovrj
		},
		dataType: "html",
		success: function(html){
			$.loading(false);
			extractScript(html);
			orcamento_atualizatotais();
			$("#btnitcancelar").click();
			var element = ($("#it_codproduto").is(":visible") ? $("#it_codproduto") : ($("#it_reffornec").is(":visible") ? $("#it_reffornec") : $("#it_tipopesquisa")));
			$.messageBox({
				type: "success",
				text: "Item gravado com sucesso!",
				focusOnClose: element
			});
            if($("#totaldesconto").val().replace(",",".") > 0){
				superrateio = "S";
			}else{
				superrateio = "N";
			}
		}
	});
}




function orcamento_itempesquisar(){
	var codproduto = $("#it_codproduto").val();
	var codestabelec = $("#codestabelec").val();
	var natoperacao = $("#natoperacao").val();
	var tipopreco = $("#tipopreco").val();
	if(codproduto.length == 0){
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
	}else if(natoperacao.length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a natureza de opera&ccedil;&atilde;o.",
			afterClose: function(){
				$("#abacabecalho").click();
				$("#natoperacao").focus();
			}
		});
		return false;
	}else if(tipopreco.length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o tipo de pre&ccedil;o dos produtos.",
			afterClose: function(){
				$("#abacabecalho").click();
				$("#tipopreco").focus();
			}
		});
		return false;
	}
	$.loading(true);
	$.ajax({
		url: "../ajax/orcamento_pesquisaitem.php",
		data: {
			"codproduto": codproduto,
			"codestabelec": codestabelec,
			"natoperacao": natoperacao,
			"tipopreco": tipopreco
		},
		dataType: "html",
		success: function(html){
			$.loading(false);
			extractScript(html);
			console.info($("#it_precovrjof").val());
			if(parseFloat($("#it_precovrjof").val()) > 0 && permitedescontooferta == "N"){
				$("#it_percdescto, #it_valdescto").disabled(true).val("0,0000");
			}else{
				//$("#it_percdescto").val(number_format(descontopadraoespecie.replace(",", "."), 4, ",", "."));
				$("#it_percdescto").val(number_format(descontopadraoespecie, 4, ",", "."));
			}
		}
	});
}

function orcamento_limpar(){
	$.ajax({
		url: "../ajax/orcamento_itorcamento.php",
		data: {"acao": "novo"},
		dataType: "html",
		success: function(html){
			extractScript(html);
		}
	});
}

function orcamento_restaurar(){
	$.messageBox({
		type: "info",
		title: "Restaurar Or&ccedil;amento",
		text: "Tem certeza que deseja alterar o status do or&ccedil;amento para pendente?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				var codorcamento = $("#codorcamento").val();
				$.ajax({
					url: "../ajax/orcamento_status.php",
					data: ({
						"codorcamento": codorcamento,
						"status": "P"
					}),
					dataType: "html",
					success: function(html){
						$.loading(false);
						$.cadastro.limpar();
						$("#codorcamento").val(codorcamento);
						$.cadastro.pesquisar();
						$.messageBox({
							type: "success",
							text: "Status do or&ccedil;amento alterado para pendente."
						});
					}
				});
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}

function orcamento_statusit(status){
	/*
	 Status:
	 0 - Dados dos itens em branco, aguardando acao do usuario
	 1 - Inserindo um novo item
	 2 - Alterando um item
	 3 - Nao permitido nenhuma alteracao na lista (inserir, alterar, excluir)
	 */
	switch(status){
		case 0: // aguardando acao
			$("#divitdados *").filter("input,label,select").disabled(true);
			$("#it_codproduto").disabled(false);
			$("#btnitinserir,#btnitlista,#btnitdados").disabled(false);
			$("#btnCadGravar,#btnCadCancelar").disabled(false);
			$("#divitgrid img").show();
			break;
		case 1: // inserindo novo
			$("#divitdados *").filter("input,label,select").disabled(false);
			$("#it_codproduto").disabled(true);
			$("#btnitinserir,#btnitlista,#btnitdados").disabled(true);
			$("#it_qtdetotal,#it_precounit").disabled(true);
			$("#btnCadGravar,#btnCadCancelar").disabled(true);
			break;
		case 2: // alterando
			$("#divitdados *").filter("input,label,select").disabled(false);
			$("#it_codproduto").disabled(true);
			$("#btnitinserir,#btnitlista,#btnitdados").disabled(true);
			$("#it_qtdetotal,#it_precounit").disabled(true);
			$("#btnCadGravar,#btnCadCancelar").disabled(true);
			$("#it_bonificado").disabled(true);
			break;
		case 3: // nao permite nenhuma alteracao na lista
			$("#divitens *").filter("input,label,select").disabled(true);
			$("#btnCadGravar,#btnCadCancelar").disabled(false);
			$("#divitgrid img").hide();
			break;
	}
}

function orcamento_abrirpedido(){
	openProgram("PedVenda", "pesquisar=S&codestabelec=" + $("#codestabelec").val() + "&codorcamento=" + $("#codorcamento").val());
}

function arqorcamento_upload(){
	$.ajax({
		data: ({
			codorcamento: $("#codorcamento").val()
		}),
		async: false,
		url: "../ajax/orcamento_arqorcamento_upload.php",
		success: function(html){
			extractScript(html);
			$.loading(false);
			arqorcamento_listar();
		}
	});
}

function arqorcamento_listar(){
	$.ajax({
		data: ({
			codorcamento: $("#codorcamento").val()
		}),
		async: false,
		url: "../ajax/orcamento_arqorcamento_listar.php",
		success: function(html){
			$("#gridarquivo").html(html);
			$.loading(false);
		}
	});
}

function arqorcamento_excluir(filename){
	$.superVisor({
		type: "venda",
		success: function(){
			$.ajax({
				data: ({
					codorcamento: $("#codorcamento").val(),
					filename: filename
				}),
				async: false,
				url: "../ajax/orcamento_arqorcamento_excluir.php",
				success: function(html){
					$("#gridarquivo").html(html);
				}
			});
		},
		fail: function(){
			$.messageBox({
				type: "alert",
				text: "&Eacute; preciso da senha de supervisor de venda para excluir o arquivo."
			});
		}
	});
}

function orcamento_aplicarrateio(){
	$.messageBox({
		type: "alert",
		title: "Aten&ccedil;&atilde;o",
		text: "O rateio deve ser aplicado ap&oacute;s a inclus&atilde;o de todos os itens no orçamento.<br>Tem certeza que deseja aplicar o rateio agora?",
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

				$.loading(true);
				$.ajax({
					url: "../ajax/orcamento_aplicarrateio.php",
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

function autoriza_orcamento_gerarrecibo(){
    if(autorizaimppedido && $("#reciboimpresso").val() == "S"){
       $.superVisor({
            type: "venda",
            success: function(){
                orcamento_gerarrecibo();
            },
            fail: function(){

            }
		});
    }else{
        orcamento_gerarrecibo();
    }
    $("#reciboimpresso").val("S");
}

function orcamento_gerarrecibo(){
	$.ajax({
		url: "../ajax/orcamento_gerarrecibo.php",
		type: "POST",
		dataType: "html",
		data:{
			codorcamento: $("#codorcamento").val()
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

function orcamento_aplicarrateio_super(){

	if(operacao == "OC" && superrateio == "S" && param_maxidesconto > 0){
		$.superVisor({
			type: "venda",
			success: function(supervisor){
                console.info(supervisor);
				if(supervisor){
					orcamento_aplicarrateio();
				}else{
					$("#ratdesconto_").val("");
					$("#ratvaldesconto_").val("");
				}
			}
		});
	}else{
		if(operacao == "OC" && param_maxidesconto > 0){
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
							orcamento_aplicarrateio();
						}else{
							$("#ratdesconto_").val("");
							$("#ratvaldesconto_").val("");
						}
					}
				});
			}else{
				orcamento_aplicarrateio();
			}
		}else{
			orcamento_aplicarrateio();
		}
	}
}

/* OS 5241 */
function orcamento_aplicarrateio_totalfinalorcamento(){
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
		content:$("#div_rateiopelototalorcamento"),
		width:"370px"
	});
	$("#_total_liquido").focus().select();
}

/* OS 5241 */
function orcamento_ajustar_valor_liquido(){
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
		//orcamento_aplicarrateio_super();
		orcamento_aplicarrateio();
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
			//orcamento_aplicarrateio_super();
			orcamento_aplicarrateio();
		}
	}
}
