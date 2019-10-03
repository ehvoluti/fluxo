/* ******************************
 V A R I A V E I S   G L O B A I S
 ****************************** */
var supervisor_elemento = null; // Elemento que sera alterado apos a senha do supervisor
var supervisor_verificar = true; // Se deve verificar a senha do supervisor
var solicitavendedor = "N";
var dectovalorperc = "V";
var percdescsemsuper;

/* ******************************
 F U N C O E S   E L E M E N T O S
 ****************************** */
window.onbeforeunload = function(){
	return "Tem certeza que deseja encerrar o sistema?";
}

$(window).bind("resize", function(){
	redimensionar();
});

$(document).bind("ready", function(){
	$("#codestabelec").trigger("change");
	$("#codfunc").bind("change", function(){
		if($("#codestabelec").val().length == 0){
			$.messageBox({
				type: "error",
				title: " ",
				text: "Informe o estabelecimento."
			});
			$("#codfunc").val("").description();
		}else{
			$.ajax({
				url: "../ajax/pedido_estabfunc.php",
				data: {
					codfunc: $("#codfunc").val(),
					codestabelec: $("#codestabelec").val()
				},
				success: function(html){
					extractScript(html);
				}
			});
		}
	});

	if(!mobile){
		$("[mobile]").remove();
	}

	// Se for mobile a tela de observacao que aparece no final da venda deve ser modificada
	if(mobile){
		$("#div_observacao tr:first").get(0).appendChild($("#div_observacao tr:last td").get(0));
		$("#div_observacao tr:last").remove();
		$("#div_observacao td:first").css({
			"width": "80%"
		});
		$("#div_observacao td:last").css({
			"padding-left": "2%",
			"padding-top": "0%",
			"vertical-align": "top",
			"width": "20%",
			"word-wrap": "break-word"
		});
		$("#div_observacao input:button").css({
			"height": "auto",
			"padding": "10% 0%",
			"margin-bottom": "2%",
			"width": "100%"
		}).addClass("botao_mobile");
	}

	$("#table_digitacaoitem td:has(input:text)").css({
		"padding-bottom": "0%",
		"padding-right": "3%"
	});
	$("#table_digitacaoitem input:text").attr({
		"fonte": "m"
	}).css({
		"height": "100%",
		"width": "100%"
	});
	$("#table_digitacaoitem td:has(label)").css({
		"vertical-align": "bottom"
	});
	$("#table_digitacaoitem label").attr({
		"fonte": "p"
	}).css({
		"color": "#444"
	});

	// Ajusta fonte quando for WebSac
	$(".websac1,.websac2").css3({
		"text-shadow": "0px 0px 8px #000"
	});

	// Bloquear o fornecedor referencia quando esta informado nos parametros do sistema
	if($("#codfornec").val().length > 0){
		$("#codfornec").disabled(true);
	}

	// Bloquear campos de desconto na finalizacao de acordo com parametro
	$("#finalizacao_totaldesctoval").disabled(!finalizdescto_val);
	$(".finalizacao_totaldesctoperc").disabled(!finalizdescto_per);

	// Ao alterar o estabelacimento carrega os parametros do ponto de venda
	$("#codestabelec").bind("change", function(){
		parametro_parampontovenda();
	});

	// Cria o controle de senha de supervisor para alteracao de preco e desconto
	$("#preco,#percdescto,#finalizacao_totaldesctoval,.finalizacao_totaldesctoperc").bind("focus", function(){		
		$(this).attr("old_value", $(this).val());
	});
	$("#preco,#percdescto,#finalizacao_totaldesctoval,#finalizacao_totaldesctoperc").bind("change", function(){

		if($(this).attr("id") == "finalizacao_totaldesctoperc" || ($(this).attr("id") == "percdescto" && dectovalorperc == "P")){
			if(parseFloat($(this).val().replace(",", ".")) < percdescsemsuper){
				return true;
			}
		}

		if($(this).attr("id") == "finalizacao_totaldesctoval"){
			if(((parseFloat($("#finalizacao_totaldesctoval").val().replace(",", ".")) / parseFloat($("#finalizacao_subtotal").val().replace(",","."))) * 100)   < percdescsemsuper ){
				return true;
			}
		}

		if($(this).attr("id") == "percdescto" && dectovalorperc == "V"){
			let aux_preco = parseFloat($("#preco").val().replace(",","."));
			let aux_quantidade = parseFloat($("#quantidade").val().replace(",","."));
			if(((parseFloat($(this).val().replace(",", ".")) / (aux_preco * aux_quantidade) ) * 100)   < percdescsemsuper ){
				return true;
			}
		}

		if($(this).attr("id") == "preco"){
			var old_value = parseFloat($(this).attr("old_value").replace(",", "."));
			var new_value = parseFloat($(this).val().replace(",", "."));
			if(new_value >= old_value && old_value > 0){
				return true;
			}
		}
		if(supervisor && supervisor_verificar){
			supervisor_verificar = false;
			supervisor_elemento = this;
			$("#div_supervisor").find("input:text,input:password").val("");
			$.modalWindow({
				content: $("#div_supervisor"),
				title: "Restri&ccedil;&atilde;o - Senha de Supervisor",
				width: "35%",
				position: ({
					left: "30%",
					top: "20%"
				}),
				center: ({
					left: false
				})
			}).css({
				"height": "auto",
				"padding": "2%"
			});
			redimensionar();
		}
	});

	// Quando der ENTER nos campos de usuario e senha de supervisor deve confirmar o formulario
	$("#supervisor_login,#supervisor_senha").bind("keypress", function(e){
		if(e.keyCode == 13){
			supervisor_confirmar();
		}
	});

	// Cria o atalho para abrir a tela de ajuda
	$(document).bind("keydown", function(e){
//		alert(e.keyCode);
		if(e.shiftKey && e.ctrlKey && e.keyCode == 65){
			$.modalWindow({
				content: $("#div_ajuda"),
				title: "Ajuda",
				width: "60%",
				position: ({
					left: "20%",
					top: "20%"
				}),
				center: ({
					left: false
				})
			}).css({
				"height": "auto",
				"padding": "2%"
			});
			$("[__modalWindow]").find("div:first").attr({
				"fonte": "p"
			}).css({
				"height": "auto",
				"padding": "1% 2%"
			});
			redimensionar();
			$("[__modalWindow]").find("fieldset").width("100%");
		}else if(e.shiftKey && (e.keyCode == 69 || e.keyCode == 82)){
			$.modalWindow({
				content: $("#div_reimprimir"),
				title: "Reimpress&atilde;o de Cupom",
				width: "40%",
				position: ({
					left: "30%",
					top: "20%"
				}),
				center: ({
					left: false
				})
			}).css({
				"height": "auto",
				"padding": "2%"
			});
			$("[__modalWindow]").find("div:first").attr({
				"fonte": "p"
			}).css({
				"height": "auto",
				"padding": "1% 2%"
			});
			redimensionar();
		}
	});

	$("#percdescto").bind("keydown", function(e){
		if(e.keyCode == 86){
			if(dectovalorperc == "V"){
				dectovalorperc = "P";
				$("[for=percdescto]").html("Percentual de Desconto %");
				$("#percdescto").trigger("change");
			}else{
				dectovalorperc = "V";
				$("[for=percdescto]").html("Valor de Desconto");
				$("#percdescto").trigger("change");
			}
			item_calculartotal();
		}
	});

	// Ao pressionar qualqer tecla no campo do codigo do cliente
	var ajax_cliente_nome = null;
	$("#codcliente").bind("keyup", function(e){
		if($(this).css("text-align") == "left"){
			if(in_array(e.keyCode, [38, 40])){
				return false;
			}
			if(e.keyCode == 27){
				$("[__consultaDescricao]").remove();
				$(this).val("").css("text-align", "center");
				return false;
			}
			if(ajax_cliente_nome != null){
				ajax_cliente_nome.abort();
			}
			ajax_cliente_nome = $.ajax({
				url: "../ajax/pontovenda_cliente_nome.php",
				data: ({
					nome: $("#codcliente").val()
				}),
				success: function(html){
					$.consultaDescricao({
						eCodigo: $("#codcliente"),
						eDescricao: $("#codcliente"),
						htmlLista: html,
						onSelect: function(){
							$("#codcliente").css("text-align", "center");
							cliente_selecionar_aceitar();
						}
					});
					ajax_cliente_nome = null;
				}
			});
		}
	}).bind("keypress", function(e){
		if($(this).css("text-align") == "left"){

		}else{
			switch(e.keyCode){
				case 13: // Enter
					cliente_selecionar_aceitar();
					break;
				case  48: // 0
				case  49: // 1
				case  50: // 2
				case  51: // 3
				case  52: // 4
				case  53: // 5
				case  54: // 6
				case  55: // 7
				case  56: // 8
				case  57: // 9
					break;
				case  80:
				case 112:
					parametro_abrir();
					break;
				case  68: // D
				case 100: // d
					cliente_consulta();
					return false;
					break;
				default:
					return false;
					break;
			}
		}
	});

	// Ao pressionar qualquer tecla no campo do codigo do produto
	$("#codproduto").bind("keydown", function(e){
		switch(e.keyCode){
			case  27: // Esc
				if(parampontovenda_supervisor == "S"){
					$.superVisor({
						type: "venda",
						success: function(){
							venda_cancelar();
						},
					});
				}else{
					venda_cancelar();
				}
				break;
		}
	}).bind("keypress", function(e){
		switch(e.keyCode){
			case  13: // Enter
				verificarvenda();
				item_pesquisar();
				break;
			case  48: // 0
			case  49: // 1
			case  50: // 2
			case  51: // 3
			case  52: // 4
			case  53: // 5
			case  54: // 6
			case  55: // 7
			case  56: // 8
			case  57: // 9
				break;
			case  68: // D
			case 100: // d
				item_consulta();
				return false;
				break;
			case  69: // E
			case 101: // e
				estoque_abrir();
				return false;
				break;
			case  70: // F
			case 102: // f
				verificarvenda();
				venda_finalizar();
				return false;
				break;
			case  84: // T
			case 116: // t
				item_buscarlista();
				return false;
				break;
			case  85: // U
			case 117: // u
				ultimavenda();
				return false;
				break;
			case  42: // *
			case  88: // X
			case 120: // x
				if($("#codproduto").val().length == 0){
					$("#codproduto").val("1");
				}
				if(strpos($("#codproduto").val(), "x") === false){
					$("#codproduto").val($("#codproduto").val() + "x");
				}
				return false;
				break;
			default:
				return false;
				break;
		}
	});

	$("#estoque_codproduto").bind("keydown", function(e){
		switch(e.keyCode){
			case  27: // Esc
				estoque_fechar();
				break;
		}
	}).bind("keypress", function(e){
		switch(e.keyCode){
			case  13: // Enter
				estoque_produto_pesquisar();
				break;
			case  48: // 0
			case  49: // 1
			case  50: // 2
			case  51: // 3
			case  52: // 4
			case  53: // 5
			case  54: // 6
			case  55: // 7
			case  56: // 8
			case  57: // 9
				break;
			case  68: // D
			case 100: // d
				estoque_produto_consulta();
				return false;
				break;
			default:
				return false;
				break;
		}
	});

	// Eventos na referencia do fornecedor
	var ajax_produto_reffornec = null;
	$("#reffornec").bind("keypress", function(e){
		switch(e.keyCode){
			case  13: // Enter
				item_pesquisar();
				break;
		}
	}).bind("keyup", function(e){
		if(in_array(e.keyCode, [38, 40])){
			return false;
		}
		if(ajax_produto_reffornec != null){
			ajax_produto_reffornec.abort();
		}
		ajax_produto_reffornec = $.ajax({
			url: "../ajax/pontovenda_item_reffornec.php",
			data: ({
				codfornec: $("#codfornec").val(),
				reffornec: $("#reffornec").val()
			}),
			success: function(html){
				$.consultaDescricao({
					eCodigo: $("#reffornec"),
					eDescricao: $("#reffornec"),
					htmlLista: html,
					onSelect: function(){
						item_pesquisar();
					}
				});
				ajax_produto_reffornec = null;
			}
		});
	});

	// Ao pressionar qualquer tecla no campo de quantidade do item
	$("#numeroserie,#preco,#quantidade,#totalitem").bind("keydown", function(e){
		switch(e.keyCode){
			case  27: // Esc
				item_cancelar_entrada();
				break;
		}
	}).bind("keypress", function(e){
		switch(e.keyCode){
			case  13: // Enter
				// Protecao para nao entrar com codigo de barras na quantidade
				if(String(Math.floor($("#quantidade").val().replace(",", "."))).length < 8){
					item_confirmar();
				}else{
					$("#quantidade").val("1,0000");
					return false;
				}
				break;
		}
	});

	// Ao pressionar qualquer tecla no campo de descontro do item
	$("#percdescto").bind("keydown", function(e){
		switch(e.keyCode){
			case  27: // Esc
				item_cancelar_entrada();
				break;
		}
	}).bind("keypress", function(e){
		switch(e.keyCode){
			case  13: // Enter
				$("#quantidade").focus();
				if(supervisor && supervisor_verificar){
					$("#supervisor_login").focus();
				}
				break;
		}
	});

	// Ao alterar o campo de descricao do produto
	var ajax_produto_descricao = null;
	$("#descricao").bind("keyup", function(e){
		if(in_array(e.keyCode, [38, 40])){
			return false;
		}
		if($(this).attr("readonly") != true){
			if(e.keyCode == 27){
				item_consulta_cancelar();
				return false;
			}
			if(ajax_produto_descricao != null){
				ajax_produto_descricao.abort();
			}
			ajax_produto_descricao = $.ajax({
				url: "../ajax/pontovenda_item_descricao.php",
				data: ({
					codestabelec: $("#codestabelec").val(),
					descricao: $("#descricao").val()
				}),
				success: function(html){
					$.consultaDescricao({
						eCodigo: $("#codproduto"),
						eDescricao: $("#descricao"),
						htmlLista: html,
						onSelect: function(){
							item_pesquisar();
						}
					});
					ajax_produto_descricao = null;
				}
			});
		}
	});

	// Ao alterar o campo de descricao do produto na consulta de estoque
	var ajax_estoque_produto_descricao = null;
	$("#estoque_descricao").bind("keyup", function(e){
		if(in_array(e.keyCode, [38, 40])){
			return false;
		}
		if($(this).attr("readonly") == false){
			if(e.keyCode == 27){
				estoque_produto_consulta_cancelar();
				return false;
			}
			if(ajax_estoque_produto_descricao != null){
				ajax_estoque_produto_descricao.abort();
			}
			ajax_estoque_produto_descricao = $.ajax({
				url: "../ajax/pontovenda_item_descricao.php",
				data: ({
					descricao: $("#estoque_descricao").val()
				}),
				success: function(html){
					$.consultaDescricao({
						eCodigo: $("#estoque_codproduto"),
						eDescricao: $("#estoque_descricao"),
						htmlLista: html,
						onSelect: function(){
							estoque_produto_pesquisar();
						}
					});
					ajax_estoque_produto_descricao = null;
				}
			});
		}
	});

	// Tipo de pessoa do cadastro de cliente
	$("#cliente_tppessoa").bind("change", function(){
		settipopessoa("cpfcnpj", $(this).val(), $("#cliente_cpfcnpj"));
		settipopessoa("rgie", $(this).val(), $("#cliente_rgie"));
	});

	// CEP do cadastro de cliente
	$("#cliente_cep").bind("change", function(){
		$(this).cep({
			endereco: $("#cliente_endereco"),
			bairro: $("#cliente_bairro"),
			uf: $("#cliente_uf"),
			cidade: $("#cliente_codcidade")
		});
	});


	$("#preco,#quantidade,#percdescto,#totalitem").css({
		"text-align": "right"
	}).bind("keypress", function(e){
		if(!in_array(e.keyCode, [44, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 57])){
			return false;
		}
	}).bind("change", function(){
		// Recalcula o total do item
		item_calculartotal();
	}).bind("focus", function(){
		$(this).select();
	}).bind("blur", function(){
		if($(this).val().length > 0){
			var n = ($(this).attr("id") == "quantidade" ? 4 : 2);
			$(this).val(number_format($(this).val().replace(",", "."), n, ",", ""));
		}
	});

	$("#finalizacao_totaldesctoval").bind("change", function(){
		$.ajax({
			async: false,
			url: "../ajax/pontovenda_rateardesconto.php",
			data: ({
				codestabelec: $("#codestabelec").val(),
				totalbruto: $("#finalizacao_subtotal").val(),
				totaldescto: $(this).val(),
				tipodescto: "V"
			}),
			success: function(html){
				extractScript(html);
			}
		});
	});


//	item_grade_limpar();
	item_status(0);

	continuar_venda();

	redimensionar();
	$("#finalizacao_totaldesctoval").val("0,00");
	$(".finalizacao_totaldesctoperc").val("0,00");
	$("#finalizacao_total").val($("#finalizacao_subtotal").val());
	desconto_change("Z");

	$(".finalizacao_totaldesctoperc").bind("change", function(){
		desconto_change("P");
	});
});

function desconto_change(tipodescto){		
	if(tipodescto == "P" && parseFloat($("#finalizacao_totaldesctoperc").val().replace(",", ".")) == 0){
		$("#finalizacao_totaldesctoval").val("0,00");		
		$(".finalizacao_totaldesctoperc").val("0,00");
		$("#finalizacao_total").val($("#finalizacao_subtotal").val());
		desconto_change("Z");
		return true;
	}
	var totaldescto = 100;
		$(".finalizacao_totaldesctoperc").each(function(){
			totaldescto -= totaldescto * (parseFloat($(this).val().replace(",", ".")) / 100);
		});
		totaldescto = (100 - totaldescto).toFixed(4);
	$.ajax({
		async: false,
		url: "../ajax/pontovenda_rateardesconto.php",
		data: ({
			codestabelec: $("#codestabelec").val(),
			totalbruto: $("#finalizacao_subtotal").val(),
			totaldescto: totaldescto,
			tipodescto: tipodescto
		}),
		success: function(html){
			extractScript(html);
		}
	});
	$(".finalizacao_totaldesctoperc").removeAttr("gear");
	$.gear();
}

/* ********************************
 F U N C O E S   J A V A S C R I P T
 ******************************** */

function atualizar_dados(){
	$.ajax({
		url: "../ajax/pontovenda_atualizardados.php",
		data: ({
			codestabelec: $("#codestabelec").val(),
			natoperacao: $("#natoperacao").val(),
			codtabela: $("#codtabela").val(),
			codfunc: $("#codfunc").val(),
			codcliente: $("#codcliente").val()
		}),
		success: function(html){
			extractScript(html);
		}
	});
}

function atualizar_tipovenda(){
	$.ajax({
		url: "../ajax/pontovenda_atualizartipovenda.php",
		data: ({
			codestabelec: $("#codestabelec").val()
		}),
		success: function(html){
			$("#td_finalizacao_tipovenda").html(html);
		}
	});
}

function botao_estilo(){
	$("button,input:button").attr({
		"fonte": "p"
	}).not(".botao_mobile").css({
		"height": "auto",
		"padding": "2% 5%",
		"margin": "0% 1%",
		"width": "auto"
	});
}

function cliente_cadastrar(){
	if(!$("#div_cadastro_cliente").is(":visible")){
		$("[__modalWindow]").animate({
			top: "5%"
		}, "slow");
		$("#div_cadastro_cliente").animate({
			"height": "toggle"
		}, "slow");
	}
	$("#cliente_codvendedor").val($("#codfunc").val());
	$("#cliente_nome").focus();
}

function cliente_consulta(){
	$("#codcliente").css("text-align", "left").val("").focus();
}

function cliente_gravar(){
	if($("#cliente_nome").val().length == 0){
		$.messageBoxPV({
			type: "error",
			title: "Campo obrigat&oacute;rio",
			text: "&Eacute; necess&aacute;rio informar um valor para o campo <b>Nome</b> para prosseguir com o cadastro do cliente.",
			focusOnClose: this
		});
		return false;
	}
	if((!(valid_cpf($("#cliente_cpfcnpj").val()) || valid_cnpj($("#cliente_cpfcnpj").val()))) && param_cadastro_validarcpfcnpj == "S"){
		$.messageBoxPV({
			type: "error",
			title: "CPF/CNPJ inv&aacute;lido",
			text: "O CPF/CNPJ informado n&atilde;o &eacute; v&aacute;lido.",
			focusOnClose: this
		});
		return false;
	}

	var data = $("#table_cadastro_cliente").find("input:text,select").map(function(){
		return $(this).attr("id").replace("cliente_", "") + "=" + $(this).val();
	}).get();
	data[data.length] = "codvendedor=" + $("#codfunc").val();
	$.ajax({
		async: false,
		url: "../ajax/pontovenda_cliente_gravar.php",
		data: data.join("&"),
		success: function(html){
			extractScript(html);
		}
	});
}

function cliente_selecionar(){
	if($("#codcliente").is(":visible")){
		$("#codcliente").focus();
		return false;
	}
	limpar_venda();
	$("#codcliente").val("");
	$("#table_cadastro_cliente").find("input:text,select").val("");
	$("#div_cadastro_cliente").hide();
	$.modalWindow({
		content: $("#div_cliente"),
		title: "Informe o c&oacute;digo ou o CPF/CNPJ do cliente",
		width: "60%",
		position: ({
			left: "20%",
			top: "20%"
		}),
		center: ({
			left: false
		})
	}).css({
		"height": "auto",
		"padding": "2%"
	});
	$("[__modalWindow]").find("div:first").attr({
		"fonte": "p"
	}).css({
		"height": "auto",
		"padding": "1% 2%"
	});
	redimensionar();
	$("#codcliente").focus();
}

function cliente_selecionar_aceitar(){
	if($("#codcliente").val().length > 0){
		$.ajax({
			async: false,
			url: "../ajax/pontovenda_cliente_verificar.php",
			data: ({
				codcliente: $("#codcliente").val(),
				codestabelec: $("#codestabelec").val()
			}),
			success: function(html){
				extractScript(html);
			}
		});
	}else{
		$("#codcliente").focus();
	}
}

function continuar_venda(){
	$.ajax({
		async: false,
		url: "../ajax/pontovenda_continuarvenda.php",
		success: function(html){
			extractScript(html);
		}
	});
}

function estoque_abrir(){
	$.modalWindow({
		content: $("#div_estoque"),
		title: "Consulta de Estoque",
		height: "70%",
		width: "45%",
		position: ({
			left: "25%",
			top: "10%"
		}),
		center: ({
			left: false
		})
	}).css({
		"height": "auto",
		"padding": "2%"
	});
	$("[__modalWindow]").find("div:first").attr({
		"fonte": "p"
	}).css({
		"height": "auto",
		"padding": "1% 2%"
	});
	redimensionar();
	estoque_produto_consulta_cancelar();
}

function estoque_fechar(){
	$.modalWindow("close");
	$("#codproduto").focus();
}

function estoque_produto_consulta(){
	$("#estoque_codproduto").disabled(true);
	$("#estoque_descricao").disabled(false);
	$("#estoque_descricao").val("").focus();
}

function estoque_produto_consulta_cancelar(){
	$("[__consultaDescricao]").remove();
	$("#estoque_descricao").val("").disabled(true);
	$("#estoque_codproduto").val("").disabled(false).focus();
	estoque_produto_pesquisar(true);
}

function estoque_produto_pesquisar(empty){
	empty = (typeof (empty) == "undefined" ? false : empty);

	if($("#estoque_codproduto").val().length == 0 && !empty){
		$("#estoque_codproduto").focus();
		return false;
	}

	$.ajax({
		async: false,
		url: "../ajax/pontovenda_estoque_produto_pesquisar.php",
		data: ({
			codproduto: $("#estoque_codproduto").val()
		}),
		success: function(html){
			$("#grade_estoque").html(html);
		}
	});
}

function iniciar_venda(){
	$("#observacao").val("");
	$.ajax({
		async: false,
		url: "../ajax/pontovenda_iniciarvenda.php",
		data: ({
			codestabelec: $("#codestabelec").val(),
			natoperacao: $("#natoperacao").val(),
			codtabela: $("#codtabela").val(),
			codfunc: $("#codfunc").val(),
			codfornec: $("#codfornec").val(),
			codcliente: $("#codcliente").val()
		}),
		success: function(html){
			extractScript(html);
		}
	});
}

function item_buscarlista(){
	window.open("../form/pontovenda_buscar.php?codestabelec=" + $("#codestabelec").val() + "&codfornec=" + $("#codfornec").val(), "pontovenda_buscar");
}

function item_calculartotal(){
	var totalliquido = 0;
	var totaldescto = 0;
	var preco = parseFloat($("#preco").val().replace(",", "."));
	var percdescto = parseFloat($("#percdescto").val().replace(",", "."));
	var quantidade = parseFloat($("#quantidade").val().replace(",", "."));
	var totalbruto = preco * quantidade;
	if(dectovalorperc == "V"){
		totaldescto = percdescto;
		totalliquido = (preco - totaldescto) * quantidade;
	}else{
		totaldescto = totalbruto * percdescto / 100;
		totalliquido = totalbruto - totaldescto;
	}

	$("#totalitem").val(number_format(totalliquido, 2, ","));
}

function _item_cancelar(codproduto, descricao){
	$.messageBoxPV({
		type: "alert",
		title: "Cancelamento de item",
		text: "Tem certeza que deseja cancelar o produto<br><b>" + descricao + "</b> ?",
		buttons: ({
			"Sim": function(){
				$.messageBoxPV("close");
				$.ajax({
					async: false,
					url: "../ajax/pontovenda_item_cancelar.php",
					data: ({
						codproduto: codproduto
					}),
					success: function(html){
						extractScript(html);
					}
				});
			},
			"N\u00E3o": function(){
				$.messageBoxPV("close");
			}
		})
	});
}

function item_cancelar(codproduto, descricao){
	if(parampontovenda_supervisor == "S"){
		$.superVisor({
			type: "venda",
			success: function(){
				_item_cancelar(codproduto, descricao)
			},
		});
	}else{
		_item_cancelar(codproduto, descricao)
	}
}

function item_cancelar_entrada(){
	item_limpar();
	item_status(1);
	$("#codproduto").focus();
}

function item_confirmar(){
	item_calculartotal();
	item_gravar();
}

function item_consulta(){
	item_status(3);
	$("#descricao").focus();
}

function item_consulta_cancelar(){
	$("[__consultaDescricao]").remove();
	item_limpar();
	item_status(1);
	$("#codproduto").focus();
}

function item_grade_desenhar(){
	$.ajax({
		async: false,
		url: "../ajax/pontovenda_item_grade_desenhar.php",
		data:({
			codestabelec: $("#codestabelec").val()
		}),
		success: function(html){
			$("#div_gradeitem").html(html);
			redimensionar();
			var div = $("#div_gradeitem_linhas")[0];
			div.scrollTop = div.scrollHeight;
		}
	});
}

function item_grade_limpar(){
	$.ajax({
		async: false,
		url: "../ajax/pontovenda_item_grade_limpar.php",
		success: function(html){
			item_grade_desenhar();
		}
	});
}

function item_grade_mover(top){
	var grade = $("#div_gradeitem_linhas")[0];
	grade.scrollTop = grade.scrollTop + top;
}

function item_gravar(forcar){
	forcar = (forcar === true);
	$("#preco,#percdescto,#quantidade").trigger("blur");
	if(!(parseFloat($("#preco").val().replace(",", ".")) > 0)){
		$.messageBoxPV({
			type: "error",
			title: "Pre&ccedil;o zerado",
			text: "O pre&ccedil;o do produto deve ser maior que zero.",
			focusOnClose: $("#preco")
		});
		return false;
	}
	if(!(parseFloat($("#quantidade").val().replace(",", ".")) > 0)){
		$.messageBoxPV({
			type: "error",
			title: "Quantidade zerada",
			text: "A quantidade do produto deve ser maior que zero.",
			focusOnClose: $("#quantidade")
		});
		return false;
	}
	if($("#numeroserie").is(":visible") && trim($("#numeroserie").val()).length == 0){
		$.messageBoxPV({
			type: "error",
			title: "N&uacute;mero de S&eacute;rie",
			text: "Informe o n&uacute;mero de s&eacute;rie do produto.",
			focusOnClose: $("#numeroserie")
		});
		return false;
	}
	$.ajax({
		async: false,
		url: "../ajax/pontovenda_item_gravar.php",
		data: ({
			codestabelec: $("#codestabelec").val(),
			codproduto: $("#codproduto").val(),
			descricao: $("#descricao").val(),
			unidade: $("#unidade").val(),
			preco: $("#preco").val(),
			percdescto: $("#percdescto").val(),
			quantidade: $("#quantidade").val(),
			totalitem: $("#totalitem").val(),
			numeroserie: $("#numeroserie").val(),
			forcar: (forcar ? "S" : "N"),
			dectovalorperc: dectovalorperc
		}),
		success: function(html){
			extractScript(html);
		}
	});
}

function item_limpar(){
	$("#table_digitacaoitem").find("input:text").val("");
}

function item_listar(){
	window.open("../form/pontovenda_lista.php");
}

function item_pesquisar(){
	if(($("#codproduto").val().length == 0 || $("#codproduto").val().substr(-1, 1) == "x") && $("#reffornec").val().length == 0){
		$("#codproduto").focus();
		return false;
	}
	if(strpos($("#codproduto").val(), "x") === false){
		var codproduto = $("#codproduto").val();
		var quantidade = 1;
	}else{
		var codproduto = $("#codproduto").val().split("x")[1];
		var quantidade = $("#codproduto").val().split("x")[0];
	}
	$.ajax({
		async: false,
		url: "../ajax/pontovenda_item_pesquisar.php",
		data: ({
			mobile: (mobile ? "S" : "N"),
			codestabelec: $("#codestabelec").val(),
			codtabela: $("#codtabela").val(),
			codproduto: codproduto,
			quantidade: quantidade,
			codfornec: $("#codfornec").val(),
			reffornec: $("#reffornec").val()
		}),
		success: function(html){
			extractScript(html);
		}
	});
}

var __item_status = null;
function item_status(status){
	if(typeof (status) == "undefined"){
		return __item_status;
	}
	__item_status = status;
	$("[mobile] .botao_mobile").disabled(true);
	switch(status){
		case 0: // Todos os campos bloqueados
			$("[numeroserie]").hide();
			$("#codproduto,#reffornec,#descricao,#numeroserie,#unidade,#preco,#percdescto,#quantidade,#totalitem").disabled(true);
			break;
		case 1: // Apenas o codigo do produto livre para digitacao
			$("[numeroserie]").hide();
			$("#codproduto,#descricao,#numeroserie,#unidade,#preco,#percdescto,#quantidade,#totalitem").disabled(true);
			$("#codproduto,#reffornec").disabled(false);
			$("#btn_item_finalizar,#btn_item_consultar,#btn_item_buscarlista,#btn_item_cancelarvenda").disabled(false);
			break;
		case 2: // Com o produto ja informado, confirma quantidade e preco
			$("#codproduto,#reffornec,#descricao,#unidade,#preco,#totalitem").disabled(true);
			$("#numeroserie,#preco,#percdescto").disabled(false);
			$("#quantidade").disabled($("#numeroserie").is(":visible"));
			$("#btn_item_confirmar,#btn_item_cancelaritem").disabled(false);
			break;
		case 3: // Com a descricao do produto aberta para pesquisa
			$("[numeroserie]").hide();
			$("#codproduto,#reffornec,#numeroserie,#unidade,#preco,#percdescto,#quantidade,#totalitem").disabled(true);
			$("#descricao").disabled(false);
			$("#btn_item_cancelarconsulta").disabled(false);
			break;
	}
}

function limpar_venda(){
	$.ajax({
		async: false,
		url: "../ajax/pontovenda_limparvenda.php",
		success: function(html){
			extractScript(html);
		}
	});
	$("#finalizacao_totaldesctoval").val("0,00");
	$(".finalizacao_totaldesctoperc").val("0,00");
}

function parametro_abrir(){
	if(solicitavendedor != "S"){
		$("#codestabelec").val("");
		if($("#codestabelec").is(":visible")){
			$("#codestabelec").val("");
			return false;
		}
	}

	$.modalWindow({
		content: $("#div_parametro"),
		title: "Par&acirc;metros de Venda",
		width: "50%",
		position: ({
			left: "25%",
			top: "20%"
		}),
		center: ({
			left: false
		})
	}).css({
		"height": "auto",
		"padding": "2%"
	});
	$("[__modalWindow]").find("div:first").attr({
		"fonte": "p"
	}).css({
		"height": "auto",
		"padding": "1% 2%"
	});

	if($("#natoperacao").val().length == 0 || $("#codtabela").val().length == 0){
		parametro_parampontovenda();
	}

	redimensionar();
	$("#codestabelec").focus();
}

function parametro_aceitar(){
	if($("#codestabelec").val().length == 0){
		$.messageBoxPV({
			type: "error",
			title: "Estabecimento n&atilde;o informado",
			text: "Informe o estabelecimento antes de confirmar a parametriza&ccedil;&atilde;o.",
			focusOnClose: $("#codestabelec")
		});
		return false;
	}
	if($("#natoperacao").val().length == 0){
		$.messageBoxPV({
			type: "error",
			title: "Natureza de opera&ccedil;&atilde;o n&atilde;o informada",
			text: "Informe a natureza de opera&ccedil;&atilde;o antes de confirmar a parametriza&ccedil;&atilde;o.",
			focusOnClose: $("#natoperacao")
		});
		return false;
	}
	if($("#codtabela").val().length == 0){
		$.messageBoxPV({
			type: "error",
			title: "Tabela de pre&ccedil;o n&atilde;o informada",
			text: "Informe a tabela de pre&ccedil;o antes de confirmar a parametriza&ccedil;&atilde;o.",
			focusOnClose: $("#codtabela")
		});
		return false;
	}
	if($("#codfunc").val().length == 0){
		$.messageBoxPV({
			type: "error",
			title: "Vendedor n&atilde;o informado",
			text: "Informe o vendedor antes de confirmar a parametriza&ccedil;&atilde;o.",
			focusOnClose: $("#codfunc")
		});
		return false;
	}

	$.modalWindow("close");

	if($("#codfornec").val().length > 0){
		$("[reffornec]").show();
	}else{
		$("[reffornec]").hide();
	}
	atualizar_dados();
	if(item_status() == 0){
		cliente_selecionar();
	}
}

function parametro_parampontovenda(){
	if($("#codestabelec").val().length > 0){
		$.ajax({
			async: false,
			url: "../ajax/pontovenda_parampontovenda.php",
			data: ({
				codestabelec: $("#codestabelec").val()
			}),
			success: function(html){
				extractScript(html);
				if(codfunc.length > 0){
					$("#codfunc").val(codfunc);
				}
			}
		});
	}
}

var redimensionar_primeiro = false; // Variavel para identificar que ja redimensionou a tela pelo menos uma vez
function redimensionar(){
	if(!tela_dinamica && redimensionar_primeiro){
		return true;
	}
	botao_estilo();
	$("[fonte]").each(function(){
		switch($(this).attr("fonte")){
			case "ppp":
				var fator = 140;
				break;
			case "pp" :
				var fator = 125;
				break;
			case "p"  :
				var fator = 100;
				break;
			case "m"  :
				var fator = 75;
				break;
			case "g"  :
				var fator = 50;
				break;
			case "gg" :
				var fator = 25;
				break;
		}
		if(tela_dinamica){
			$(this).css("font-size", Math.floor(($(document).width() + $(document).height()) / fator) + "px");
		}else{
			$(this).css("font-size", Math.floor(($("body").width() + $("body").height()) / fator) + "px");
		}
	});
	$("select[fonte]").height("auto");

	var grade = $("#div_gradeitem");
	$(grade).height(0).height($(grade).parent().height() + 50);
	redimensionar_primeiro = true;
}

function reimprimir(){
	if($("#reimprimir_tipovenda").val().length == 0){
		$.messageBoxPV({
			type: "error",
			title: "Reimpress&atilde;o de Cupom",
			text: "Informe o tipo de venda para reimpress&atilde;o do cupom."
		});
		return false;
	}else if($("#reimprimir_documento").val().length == 0){
		$.messageBoxPV({
			type: "error",
			title: "Reimpress&atilde;o de Cupom",
			text: "Informe o documento para reimpress&atilde;o do cupom."
		});
		return false;
	}
	$.ajax({
		async: false,
		url: "../ajax/pontovenda_reimprimir.php",
		data: ({
			tipovenda: $("#reimprimir_tipovenda").val(),
			documento: $("#reimprimir_documento").val()
		}),
		success: function(html){
			extractScript(html);
		}
	});
}

function venda_gravar(){
	$.ajax({
		async: false,
		url: "../ajax/pontovenda_limitecliente.php",
		data: ({
			codcliente: $("#codcliente").val(),
			codespecie: $("#finalizacao_codespecie").val(),
			finalizacao_total: $("#finalizacao_total").val(),
			supervisor: supervisor
		}),
		success: function(html){
			extractScript(html);
		}
	});
}

function supervisor_limite(){
	supervisor_elemento = "gravar";
	$.modalWindow({
		content: $("#div_supervisor"),
		title: "Restri&ccedil;&atilde;o - Senha de Supervisor",
		width: "35%",
		hint: "O cliente está com o limite de debito negativo para continuar digite a senha de supervisor",
		position: ({
			left: "30%",
			top: "20%"
		}),
		center: ({
			left: false
		})
	}).css({
		"height": "auto",
		"padding": "2%"
	});
}

function supervisor_cancelar(){
	supervisor_verificar = false;
	$.modalWindow("close");
	$(supervisor_elemento).val($(supervisor_elemento).attr("old_value")).trigger("change");
	supervisor_verificar = true;
}

function supervisor_confirmar(){
	if(trim($("#supervisor_login").val()).length == 0){
		$("#supervisor_login").focus();
		return false;
	}
	if(trim($("#supervisor_senha").val()).length == 0){
		$("#supervisor_senha").focus();
		return false;
	}
	var senha_valida = false;
	$.ajax({
		async: false,
		url: "../ajax/pontovenda_supervisor.php",
		data: ({
			login: $("#supervisor_login").val(),
			senha: $("#supervisor_senha").val()
		}),
		success: function(html){
			senha_valida = (html == "T");
		}
	});
	if(senha_valida){
		$.modalWindow("close");
                $("#quantidade").focus();
		if(supervisor_elemento == "gravar"){
			venda_gravar_();
		}else if(supervisor_elemento == "gravar_item"){
			item_gravar(true);			
		}
		$("#supervisor_senha").val("");
	}else{
		$("#supervisor_senha").val("");
		$.messageBoxPV({
			type: "error",
			title: "Altera&ccedil;&atilde;o n&atilde;o permitida",
			text: "Verifique se o usu&aacute;rio e senha foram corretamente informados ou se usu&aacute;rio informado tem permiss&otilde;es de supervisor.",
			focusOnClose: $("#supervisor_senha")
		});
	}
}

function ultimavenda(){
//	$("#div_ultvenda_cont").html("");
//	var win = $.modalWindow({
//		content: $("#div_ultvenda"),
//		height: "80%",
//		title: "&Uacute;ltimas compras do cliente",
//		width: "95%",
//		position: ({
//			left: "2.5%",
//			top: "10%"
//		}),
//		center: ({
//			left: false
//		})
//	}).css({
//		"padding": "2%"
//	});
//	$("[__modalWindow]").find("div:first").attr({
//		"fonte": "p"
//	}).css({
//		"height": "auto",
//		"padding": "1% 2%"
//	});
//	$("#div_ultvenda_cont").html("");
	$.loading(true);
	$.ajax({
		url: "../ajax/pontovenda_ultvenda_carregar.php",
		data: ({
			codcliente: $("#codcliente").val()
		}),
		success: function(html){
			$.loading(false);
			$("#div_ultvenda_cont").html(html);
//			$("[__modalWindow] input:button:last").focus();
			$.modalWindow({
				content: $("#div_ultvenda"),
				title: "&Uacute;ltimas compras do cliente",
				width: "95%",
				height: "80%",
			})
		}
	});
}

function ultimavenda_incluiritem(codproduto, descricao){
	$.messageBoxPV({
		type: "info",
		title: "Incluir produto na venda",
		text: "Tem certeza que deseja incluir o produto <b>" + codproduto + "</b> (" + descricao + ") na venda?",
		buttons: ({
			"Sim": function(){
				$.messageBoxPV("close");
				$.modalWindow("close");
				$("#codproduto").val(codproduto);
				item_pesquisar();
			},
			"N\u00E3o": function(){
				$.messageBoxPV("close");
			}
		})
	});
}

function venda_cancelar(){
	$.messageBoxPV({
		type: "alert",
		title: "Cancelamento de Venda",
		text: "Tem certeza que deseja cancelar a venda?",
		buttons: ({
			"Sim": function(){
				item_limpar();
				item_grade_limpar();
				cliente_selecionar();
				$.messageBoxPV("close");
				$("#codcliente").focus();
			},
			"N\u00E3o": function(){
				$.messageBoxPV("close");
			}
		})
	});
}

function venda_finalizar(){
	if(infobservacao){
		$.modalWindow({
			content: $("#div_observacao"),
			height: (mobile ? "50%" : "80%"),
			title: "Observa&ccedil;&atilde;o",
			width: (mobile ? "60%" : "70%"),
			position: ({
				left: "15%",
				top: "10%"
			}),
			center: ({
				left: false
			})
		}).css({
			"padding": "2%"
		});
		if(mobile){
			$("[__modalwindow]").height($("[__modalwindow]").height());
		}
	}else{
		venda_finalizar_();
	}
}

function venda_finalizar_(){
	var win = $.modalWindow({
		content: $("#div_finalizacao"),
		title: "Finaliza&ccedil;&atilde;o de Venda",
		width: "40%",
		position: ({
			left: "35%",
			top: "20%"
		}),
		center: ({
			left: false
		})
	}).css({
		"height": "auto",
		"padding": "2%"
	});
	$("[__modalWindow]").find("div:first").attr({
		"fonte": "p"
	}).css({
		"height": "auto",
		"padding": "1% 2%"
	});
	$("#finalizacao_subtotal,#finalizacao_total").disabled(true);
//	$("#finalizacao_totaldescto").disabled(parseFloat($("#finalizacao_totaldescto").val().replace(",",".")) > 0);
	$("#finalizacao_codespecie").focus();
	redimensionar();
}

function venda_gravar_(){
	if($("#finalizacao_codespecie").val().length == 0){
		$.messageBoxPV({
			type: "error",
			title: "Campo obrigat&oacute;rio",
			text: "&Eacute; necess&aacute;rio informar a <b>Forma de Pagamento</b> antes de finalizar a venda.",
			focusOnClose: $("#finalizacao_codespecie")
		});
		return false;
	}
	if($("#finalizacao_codcondpagto").val().length == 0){
		$.messageBoxPV({
			type: "error",
			title: "Campo obrigat&oacute;rio",
			text: "&Eacute; necess&aacute;rio informar a <b>Condi&ccedil;&atilde;o de Pagamento</b> antes de finalizar a venda.",
			focusOnClose: $("#finalizacao_codcondpagto")
		});
		return false;
	}
	var arr_descto = [];
	$(".finalizacao_totaldesctoperc").each(function(){
		arr_descto.push(parseFloat($(this).val().replace(",", ".")));
	});

	var especie = "";
	if($("#dinheiro").val().length == 0){
		$.ajax({
			async: false,
			url: "../ajax/pontovenda_troco.php",
			data: ({
				codespecie: $("#finalizacao_codespecie").val(),
				codestabelec: $("#codestabelec").val()
			}),
			success: function(text){
				especie = text;
			}
		});
	}

	if(especie == "DH"){
		$.modalWindow({
			content: $("#div_dinheiro"),
			title: "Caixa"
		});
		return false;
	}

	$.ajaxProgress({
		type: "POST",
		url: "../ajax/pontovenda_gravar.php",
		data: ({
			tipovenda: $("#finalizacao_tipovenda").val(),
			codestabelec: $("#codestabelec").val(),
			natoperacao: $("#natoperacao").val(),
			codtabela: $("#codtabela").val(),
			codfunc: $("#codfunc").val(),
			codcliente: $("#codcliente").val(),
			codespecie: $("#finalizacao_codespecie").val(),
			codcondpagto: $("#finalizacao_codcondpagto").val(),
			codtransp: $("#finalizacao_codtransp").val(),
			observacao: $("#observacao").val(),
			dinheiro: $("#dinheiro").val(),
			arr_descto: arr_descto
		}),
		success: function(html){
			extractScript(html);
			$("#dinheiro").val("");
		}
	});
}

function venda_tipovenda(tipovenda){
	$("#finalizacao_tipovenda option").each(function(){
		if($(this).val().length == 0 || strpos(tipovenda, $(this).val()) === false){
			$(this).remove();
		}
	});
	var option = $("#finalizacao_tipovenda option");
	if($(option).length == 1){
		$("#finalizacao_tipovenda").val($(option).val());
		$("#tr_finalizacao_tipovenda").hide();
	}else{
		$("#tr_finalizacao_tipovenda").show();
	}
}

function item_quantidade(codproduto, descricao, quantidade){
	$.modalWindow({
		content: $("#div_item_quantidade"),
		title: "Alterar Quantidade",
		width: "50%"
	})
	$("#item_codproduto").val(codproduto);
	$("#item_descricao").val(descricao);
	$("#item_quantidade").val(quantidade);
	$("#item_quantidade").focus().select();
}

function item_quantidade_alterar(){
	$.ajax({
		url: "../ajax/pontovenda_item_quantidade_alterar.php",
		data: ({
			codproduto: $("#item_codproduto").val(),
			quantidade: $("#item_quantidade").val()
		}),
		success: function(html){
			extractScript(html);
		}
	});
}

function item_quantidade_enter(){
	if(event.keyCode == 13){
		item_quantidade_alterar();
	}
}

/* ************************
 F U N C O E S   J Q U E R Y
 ************************ */

var _messageBoxPV = ({
	focusOnClose: null
});
$.messageBoxPV = function(settings){
	if(typeof (settings) == "string"){
		switch(settings){
			case "close":
				$("[__messageBoxPV]").remove();
				$.background(false);
				$(_messageBoxPV.focusOnClose).focus().trigger("focus");
				break;
		}
		return true;
	}

	settings = $.extend({
		type: "info",
		title: "&nbsp;",
		text: "",
		focusOnClose: $("body").firstElement(),
		buttons: ({
			"Ok": function(){
				$.messageBoxPV("close");
			}
		})
	}, settings);

	_messageBoxPV.focusOnClose = settings.focusOnClose;

	switch(settings.type){
		case "alert":
			var cor_sombra = "#BB8";
			var cor_titulo = "#EEA";
			break;
		case "error":
			var cor_sombra = "#B98";
			var cor_titulo = "#EBA";
			break;
		case "info":
			var cor_sombra = "#CCC";
			var cor_titulo = "#DDD";
			break;
		case "success":
			var cor_sombra = "#9B8";
			var cor_titulo = "#DEC";
			break;
	}


	$.background(true);

	var caixa = document.createElement("div");
	document.body.appendChild(caixa);
	$(caixa).attr({
		"__messageBoxPV": true
	}).css({
		"border": "1px solid #999",
		"background-color": "#FDFDFD",
		"left": "25%",
		"position": "absolute",
		"top": "20%",
		"width": "50%"
	}).css3({
		"box-shadow": "0px 0px 25px 5px " + cor_sombra
	});

	var titulo = document.createElement("div");
	caixa.appendChild(titulo);
	$(titulo).attr({
		"fonte": "p"
	}).css({
		"background-color": cor_titulo,
		"padding": "1% 2%",
		"width": "100%"
	}).html(settings.title);

	var texto = document.createElement("div");
	caixa.appendChild(texto);
	$(texto).attr({
		"fonte": "p"
	}).css({
		"padding": "3% 2%",
		"width": "100%"
	}).html(settings.text);

	var caixa_botao = document.createElement("div");
	caixa.appendChild(caixa_botao);
	$(caixa_botao).css({
		"padding": "2% 2%",
		"text-align": "center",
		"width": "100%"
	});

	for(var nome_botao in settings.buttons){
		var botao = document.createElement("input");
		botao.type = "button";
		caixa_botao.appendChild(botao);
		$(botao).val(nome_botao).bind("click", settings.buttons[nome_botao]);
	}

	setTimeout(function(){
		$(caixa_botao).find("input:button:first").focus();
	}, 0);
//	$("input:button:first", caixa_botao).find("input:button:first").focus();

	redimensionar();
	$.gear();
}

$.consultaDescricao = function(settings){
	settings = $.extend({
		eCodigo: null,
		eDescricao: null,
		htmlLista: "",
		onSelect: function(){
		}
	}, settings);

	$("[__consultaDescricao]").remove();

	if($(settings.eDescricao).val().length == 0){
		return true;
	}

	var div = document.createElement("div");
	document.body.appendChild(div);

	var lista = $(div).hide().html(settings.htmlLista).find("*");
	switch($(settings.eDescricao).attr("id")){
		case "reffornec":
			$(lista).attr("fonte", "p");
			break;
		default:
			$(lista).attr("fonte", "ppp");
			break;
	}

	if($(div).find("li[key]").length == 0){
		$(div).remove();
		return true;
	}

	redimensionar();

	$(div).show().attr({
		"__consultaDescricao": true
	}).css({
		"left": $(settings.eDescricao).offset().left,
		"position": "absolute",
		"top": $(settings.eDescricao).offset().top + $(settings.eDescricao).height(),
		"width": ($(settings.eDescricao).width() + parseInt($(settings.eDescricao).css("border-left-width")) * 2),
		"max-height": "400px",
		"overflow": "auto"
	});

	$("[produto_descricao]").each(function(){
		var div = $(this).find("div:first");
		$(div).css("display", "none");
		var w = $(this).width();
		$(div).css("display", "block").width(w);
	});

	$(div).find("li[key]").first().attr("selecionado", true);

	$(div).find("li[key]").bind("click", function(){
		$(settings.eCodigo).val($(this).attr("key"));
		$("[__consultaDescricao]").remove();
		settings.onSelect();
	});

	if($(settings.eDescricao).filter("[consultaDescricao_keydown]").length == 0){
		$(settings.eDescricao).bind("keydown", function(e){
			var li_atual = $("[__consultaDescricao] li[selecionado]");
			if($(li_atual).length > 0){
				switch(e.keyCode){
					case  13: // Enter
						$(settings.eCodigo).val($(li_atual).attr("key"));
						$("[__consultaDescricao]").remove();
						settings.onSelect();
						return false;
						break;
					case  38: // Seta para cima
						var li_anterior = $(li_atual).prev();
						if($(li_anterior).length > 0 && $(li_anterior).is("[key]")){
							$(li_atual).removeAttr("selecionado");
							$(li_anterior).attr("selecionado", true);
						}
						$("[__consultaDescricao]").scrollTop($("[__consultaDescricao] li[selecionado]").index() * 20);
						return false;
						break;
					case  40: // Seta para baixo
						var li_proximo = $(li_atual).next();
						if($(li_proximo).length > 0){
							$(li_atual).removeAttr("selecionado");
							$(li_proximo).attr("selecionado", true);
						}
						$("[__consultaDescricao]").scrollTop($("[__consultaDescricao] li[selecionado]").index() * 20);
						return false;
						break;
				}
			}
		});
		$(settings.eDescricao).attr("consultaDescricao_keydown", true);
	}
}

function troco(){
	var total = 0;
	var dinheiro = 0;
	var _troco = 0;

	total = Number($("#finalizacao_total").val().replace(",", "."));
	dinheiro = Number($("#dinheiro").val().replace(",", "."));
	_troco = dinheiro - total;


	if(_troco < 0){
		$.messageBoxPV({
			type: "error",
			title: "Troco",
			text: "Recebido menor que total.",
			focusOnClose: $("#dinheiro")
		});
		return true;
	}else{
		$.modalWindow("close");
		venda_gravar_();
	}

}

function troco_calcula(){
	var total = 0;
	var dinheiro = 0;
	var troco = 0;

	total = Number($("#finalizacao_total").val().replace(",", "."));
	dinheiro = Number($("#dinheiro").val().replace(",", "."));


	troco = (dinheiro - total).toFixed(2);
	$("#troco").val(troco.toString().replace(".", ","));
}

function verificarvenda(){
	var arr_codproduto = [];

	$("#div_gradeitem_linhas [codproduto]").each(
			function(){
				arr_codproduto.push($(this).attr("codproduto"));
			});

	$.ajax({
		async: false,
		url: "../ajax/pontovenda_verificarvenda.php",
		data: ({
			arr_codproduto: arr_codproduto
		}),
		success: function(html){
			if(html.length > 0){
				$.messageBoxPV({
					type: "alert",
					title: "Atenção",
					text: html
				});
			}
		}
	});
}

function finalizar_histcupom(codcupom, tipovenda, numpedido, idnotafiscal){
	$.ajax({
		type: "POST",
		url: "../ajax/pontovenda_imprimir.php",
		data: {
			codcupom: codcupom,
			tipovenda: tipovenda,
			numpedido: numpedido,
			idnotafiscal: idnotafiscal
		},
		success: function(html){
			extractScript(html);
		}
	});
}

function finalizcancelar(){
	supervisor_verificar = true;
	$('.finalizacao_totaldesctoperc').val('0,00');
	desconto_change('Z');
	$.modalWindow('close');
	$('#codproduto').focus();
}
