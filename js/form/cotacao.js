var cotacaoperdido = false; // Se existe uma cotacao perdida (fechou no meio da cotacao) para restaurar

$(document).ready(function(){
	// Verifica se deve continuar uma cotacao perdida no meio
	perdido_verificar();
});

$(document).ready(function(){
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnSelecaoFornecedor\" value=\"Sele&ccedil;&atilde;o de Fornecedores\" onclick=\"selecao_fornecedor('" + param_notafiscal_tipocotacao + "')\">");

	// Verifica se deve continuar um pedido perdido
	if(cotacaoperdido){
		setTimeout(function(){
			perdido_questionar();
		}, 200);
	}

	$("#cotrap_codproduto").bind("keydown", function(e){
		if(e.keyCode === 13){
			$("#cotrap_quantidade").val("1,00");
			$("#cotrap_quantidade").focus();
			$("#cotrap_quantidade").select();
		}
	});

	$("#cotrap_quantidade").bind("keydown", function(e){
		if(e.keyCode === 13){
			$("#btnCotRapIncluir").focus();
		}
	});

	$("#nfe_tipoqtdenfe").bind("change", function(){
		if($(this).val() == "U"){
			$.ajax({
				url: "../ajax/cotacaorapida_pegarunidade.php",
				type: "POST",
				dataType: "html",
				beforeSend: function(xHr){
					$.loading(true);
				},
				success: function(html){
					$("#cotrap_codunidade").val(html);
					;
				},
				complete: function(xHr){
					$.loading(false);
				}
			});
		}else{
			$("#cotrap_codunidade").val("");
		}
	});

	$("#arqcotacao").bind("change", function(){
		$(this).upload({
			filename: "../temp/upload/cotacaofornecedor.txt",
			onComplete: function(){
				upload_arqfornec();
				return true;
			}
		});
	});
});

var alterado = false;
$.cadastro.after.alterar = function(){
	$("#btnexcluirprodutos").disabled(true)
	$("#btn_buscarfornecedor,#btn_buscarproduto,#btn_cotacaorapida").disabled(false);
	$("#grid_produto").find("input:text, select").disabled(false);
	$("#status").disabled(true);
	$("#grid_produto .itemdelete").show();
	$.ajax({
		url: "../ajax/cotacao_verificatemporario.php",
		data: ({
			codestabelec: $("#codestabelec").val()
		}),
		success: function(result){
			extractScript(result);
		}
	});
};

$.cadastro.before.cancelar = function(){
	limpar_gradefornecedor();
	limpar_gradeproduto();
	perdido_limpar();
	return true;
};

$.cadastro.after.carregar = function(){
	desenhar_gradefornecedor();
	desenhar_gradeproduto();
	$("#btnexcluirprodutos").disabled(false);
	$("#btnCadAlterar, #btnCadExcluir").hide();
	$("#btnEncerrar, #btnLiberar, #btnSuspender").hide();
	$("#btnGerarPedidos, #btnCotacaoFornecedor, #btnSelecaoFornecedor, #btnVisualizarPedido, #btnProdNaoCotado, #btnRanking, #btnexcluirprodutos").hide();
	switch($("#status").val()){
		case "A":
			$("#btnCadAlterar").show();
			$("#btnCadExcluir").show();
			//$("#btnAvisoVendedor").show();
			$("#btnRanking, #btnexcluirprodutos").show();
			$("#btnSuspender").show();
			if(restricao_encerracotacao){
				$("#btnEncerrar").show();
			}
			if(restricao_digitafornec){
				$("#btnCotacaoFornecedor").show();
			}
			break;
		case "E":
			$("#btnSelecaoFornecedor").show();
			$("#btnProdNaoCotado").show();
			$("#btnRanking").show();
			$("#btnGerarPedidos").show();
			$("#btnLiberar").show();
			break;
		case "G":
			$("#btnVisualizarPedido").show();
			$("#btnProdNaoCotado").show();
			$("#btnRanking").show();
			break;
		case "S":
			$("#btnLiberar").show();
			break;
	}

	if($("#distribuicao").val() === "S"){
		$("#btn_cotacaorapida").hide();
	}else{
		$("#btn_cotacaorapida").show();
	}

	if(cotacaoperdido){
		$.cadastro.alterar();
	}
};

$.cadastro.after.clonar = function(){
	$("#status").val("A");
};

$.cadastro.after.deletar = function(){
	limpar_gradefornecedor();
	limpar_gradeproduto();
	perdido_gravar();
};

$.cadastro.after.inserir = function(){
	$("#btnexcluirprodutos").disabled(true);
	$("#btn_buscarfornecedor, #btn_buscarproduto, #btn_cotacaorapida").disabled(false);
	$("#status").disabled(true).val("A");
	$("#tipodescricao").val(1);
	if(param_notafiscal_habcompradistrib !== "0"){
		$.messageBox({
			title: "Cota&ccedil;&atilde;o com Distribui&ccedil;&atilde;o",
			text: "A nova cota&ccedil;&atilde;o ter&aacute; distribui&ccedil;&atilde;o entre os estabelecimentos ou ser&aacute; uma cota&ccedil;&atilde;o individual para apenas um estabelecimento?",
			buttons: {
				"Distribui\u00E7\u00E3o": function(){
					$("#distribuicao").val("S");
					$("#btn_cotacaorapida").hide();
					$.messageBox("close");
				},
				"Individual": function(){
					$("#distribuicao").val("N");
					$("#btn_cotacaorapida").show();
					$.messageBox("close");
				}
			},
			afterClose: function(){
				if(cotacaoperdido){
					desenhar_gradeproduto();
				}
			}
		});
	}else{
		$("#distribuicao").val("N");
	}
};

$.cadastro.after.limpar = function(){
	$("#btn_buscarfornecedor,#btn_buscarproduto,#btn_cotacaorapida,#btnexcluirprodutos").disabled(true);
};

$.cadastro.after.retornar = function(){
	limpar_gradefornecedor();
	limpar_gradeproduto();
};

$.cadastro.after.salvar = function(){
	perdido_limpar();
};

$(document).bind("ready", function(){
	// Botoes para o status 'A' (aberto)
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnEncerrar\" value=\"Encerrar Cota&ccedil;&atilde;o\" onclick=\"encerrar_cotacao()\">");
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnCotacaoFornecedor\" value=\"Digita&ccedil;&atilde;o Fornecedores\" onclick=\"cotacaofornecedor()\">");
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnRanking\" value=\"Ranking\" onclick=\"tipo_ranking()\">");
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnSuspender\" value=\"Suspender\" onclick=\"suspender_cotacao()\">");
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnexcluirprodutos\" value=\"Excluir Produtos\" onclick=\"excluirprodutos()\">");

	// Botoes para status 'S' (suspenso)
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnLiberar\" value=\"Liberar\" onclick=\"liberar_cotacao()\">");

	// Botoes para o status 'E' (encerrado)
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnProdNaoCotado\" value=\"Produtos N&atilde;o Cotados\" onclick=\"produtos_nao_cotados()\">");

	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnGerarPedidos\" value=\"Gerar Pedidos\" onclick=\"gerar_pedido()\" style=\"margin-right: 7px;\">");

	// Botoes para status 'G' (pedidos gerados)
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnVisualizarPedido\" value=\"Visualizar Pedidos\" onclick=\"visualizar_pedido()\">");

	$("#btnCadRetornar1").before("<input type=\"button\" style=\"margin-right: 6px;\" id=\"btnAviso\" value=\"Aviso Cotacao\" onclick=\"aviso_cotacao_modeloemail()\">");

//    $("#btnCadCancelar").after("<input type=\"button\" id=\"btnColetorDados\" value=\"Coletor de Dados\" onclick=\"importar_coletordados()\">");
	$("#btnCadCancelar").after("<div style=\"margin-left: 5px; display: inline;\"><input id=\"btnColetorDados\" type=\"file\" text=\"Coletor de Dados\" /></div>");
//	$("#btnCadCancelar").after("<input type=\"button\" id=\"btnAvisoVendedor\" value=\"Aviso Vendedor\" onclick=\"email_avisovendedor()\">");
	$("#btnEncerrar,#btnCotacaoFornecedor,#btnSelecaoFornecedor,#btnVisualizarPedido,#btnProdNaoCotado,#btnSuspender,#btnLiberar,#btnRanking,#btnexcluirprodutos").css("margin-right", "7px");
	$("#btnEncerrar,#btnCotacaoFornecedor,#btnSelecaoFornecedor,#btnVisualizarPedido,#btnProdNaoCotado").width("160px");
	$("#btnColetorDados").css("margin-left", "7px");
	$("#btnCadExcluir").css("margin-bottom", "7px");

	// Carrega a lista de produtos conforme vai descendo a grade
	$("#grid_produto").bind("scroll", function(){
		var outerHeight = parseInt($(this).height());
		var scrollPos = parseInt($(this).scrollTop());
		var innerHeight = parseInt($(this).children().filter("table").height());
		if(innerHeight - scrollPos - outerHeight < outerHeight){
			if(__desenhar_gradeproduto_running === false){
				desenhar_gradeproduto(false);
			}
		}
	});
	/*
	 // Carrega a lista de produtos da busca conforme vai descendo a grade
	 $("#grid_buscarproduto").bind("scroll", function(){
	 var outerHeight = parseInt($(this).height());
	 var scrollPos = parseInt($(this).scrollTop());
	 var innerHeight = parseInt($(this).children().filter("table").height());
	 if(innerHeight - scrollPos - outerHeight < outerHeight){
	 buscarproduto_filtrar(false, false);
	 }
	 });
	 */
	// Libera o campo da descricao do produto para o filtro na busca dos produtos
	$("#desc_bp_codproduto").removeAttr("readonly");
	$("#btnColetorDados").bind("change", function(){
		$(this).upload({
			filename: diretorio($("#codestabelec").val(), "dircoletor"),
			onComplete: function(){
				importar_coletordados();
			}
		});
	});

	$("#ep_coddepto, #ep_codgrupo, #ep_codsubgrupo").bind("change", function(){
		$("#grid_excluirprodutos tbody").html("");
	})
});

function buscarfornecedor(){
	if($("#codestabelec").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe o estabelecimento.",
			focusOnClose: $("#codestabelec")
		});
		return false;
	}
	$("#div_buscarfornecedor").find("input:text,select").val("");
	$.modalWindow({
		closeButton: true,
		content: $("#div_buscarfornecedor"),
		title: "Busca de Fornecedores",
		width: "750px",
		position: ({
			top: "7%"
		})
	});
	buscarfornecedor_filtrar(true);
}

function excluirprodutos(){
	$("#grid_excluirprodutos input:checkbox").checked(false).disabled(false);
	$("#ep_coddepto").val("").disabled(false);
	$("#ep_codgrupo").val("").disabled(false);
	$("#ep_codsubgrupo").val("").disabled(false);
	$("#grid_excluirprodutos tbody").html("");
	$.modalWindow({
		closeButton: true,
		content: $("#div_excluirprodutos"),
		title: "Exclusão de produtos em lote",
		width: "600px",
		position: ({
			top: "7%"
		})
	})
}

function excluirprodutos_filtrar(){
	if($("#ep_coddepto").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Necessário informar pelo menos o departamento para filtro",
			focusOnClose: $("#codestabelec")
		});
		return false;
	}

	$.ajax({
		url: "../ajax/cotacao_excluirprodutos_filtrar.php",
		type: "POST",
		dataType: "html",
		data: {
			codcotacao: $("#codcotacao").val(),
			coddepto: $("#ep_coddepto").val(),
			codgrupo: $("#ep_codgrupo").val(),
			codsgrup: $("#ep_codsubgrupo").val()
		},
		beforeSend: function(xHr){
			$.loading(true);
		},
		success: function(html){
			$("#grid_excluirprodutos tbody").html("");
			$("#grid_excluirprodutos tbody").append(html);
		},
		complete: function(xHr){
			$.loading(false);
		}
	});
}

function produtos_excluir(){

	$.messageBox({
		type: "info",
		text: "Tem certeza que deseja excluir os produtos selecionados da cotação agora?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				_produtosexcluir();
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}

function _produtosexcluir(){
	let arr_produtos = [];
	$("#grid_excluirprodutos tbody input:checkbox:checked").each(function(count){
		arr_produtos.push($(this).attr("id"));
	});
	if(arr_produtos.length === 0){
		$.messageBox({
			type: "alert",
			text: "Nenhum produto foi marcado para exclusão. Marque os produtos que deseja excluir!",
		});
		return false;
	}

	$.ajax({
		url: "../ajax/cotacao_excluirprodutos_excluir.php",
		type: "POST",
		dataType: "html",
		data: {
			codcotacao: $("#codcotacao").val(),
			arr_produtos: arr_produtos
		},
		beforeSend: function(xHr){
			$.loading(true);
		},
		complete: function(xHr){
			$.loading(false);
		},
		success: function(html){
			$.modalWindow("close");
			desenhar_gradeproduto();
			extractScript(html);
		}
	});
}

function buscarproduto(){
	if($("#codestabelec").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe o estabelecimento antes de incluir produtos na lista.",
			afterClose: function(){
				$("#aba_cabecalho").click();
				$("#codestabelec").focus();
			}
		});
		return false;
	}

	if(param_pedido_modelopedidoauto === "2" || (param_pedido_modelopedidoauto === "3" && $("#distribuicao").val() === "S")){
		var codestabelec = $("#codestabelec").val();
		if($("#distribuicao").val() === "S"){
			codestabelec = $("#codestabelec option").map(function(){
				return $(this).attr("value");
			}).get().filter(Boolean);
		}

		var codfornec = $("#grid_fornecedor tr.row td:first-child").map(function(){
			return $(this).text();
		}).get();

		var codproduto = $("#grid_produto tr.row td:first-child").map(function(){
			return $(this).text();
		}).get();

		$.selecaoProduto({
			data: {
				tabela: "cotacao",
				codestabelec: codestabelec,
				codfornec: codfornec,
				codproduto: codproduto
			},
			success: function(data){
				$.loading(true);
				$.ajax({
					url: "../ajax/cotacao_incluirproduto.php",
					type: "POST",
					data: {
						distribuicao: $("#distribuicao").val(),
						substituir: "N",
						data: JSON.stringify(data)
					},
					success: function(result){
						$.loading(false);
						extractScript(result);
						desenhar_gradeproduto();
						perdido_gravar();
					}
				});
			}
		});
	}else{
		_buscarproduto_data = {};
		$("#div_buscarproduto").find("input:text,select").not("#tipodescricao").val("");
		$("#bp_foralinha").val("N");
		buscarproduto_filtrar(true);
		$.modalWindow({
			closeButton: true,
			content: $("#div_buscarproduto"),
			title: "Busca de Produtos",
			width: "850px",
			position: ({
				top: "7%"
			})
		});
	}
}

function buscarfornecedor_filtrar(limpar){
	if(limpar === undefined){
		limpar = false;
	}
	$.loading(true);
	$.ajax({
		url: "../ajax/cotacao_filtrarfornecedor.php",
		data: ({
			codfornec: $("#bf_codfornec").val(),
			codproduto: $("#bf_codproduto").val(),
			codestabelec: $("#codestabelec").val(),
			uf: $("#bf_uf").val(),
			codcidade: $("#bf_codcidade").val(),
			relacionado: ($("#chk_relacaofornecedor").checked() ? "S" : "N"),
			apenascotacao: ($("#chk_apenascotacao").checked() ? "S" : "N"),
			limpar: (limpar ? "S" : "N")
		}),
		success: function(html){
			$.loading(false);
			$("#grid_buscarfornecedor").html(html);
		}
	});
}

function buscarproduto_checkproduto(checkbox){
	if($("#distribuicao").val() === "N"){
		var quantidade = $("#grid_buscarproduto input:text[coluna='quantidade'][codproduto='" + $(checkbox).attr("codproduto") + "']");
		if($(checkbox).checked() && parseFloat($(quantidade).val().replace(",", ".")) == 0){
			$(quantidade).val("1,0000");
			buscarproduto_temp_atualizar(checkbox);
		}
	}
}

var ajax_buscarproduto_filtrar = null;

function buscarproduto_checked(check){
	if(check){
		var arr_prod_aux = {};
		$("#grid_buscarproduto [coluna=quantidade]").each(function(){
			arr_prod_aux[$(this).attr("codproduto")] = parseFloat($(this).val().replace(",", "."));
		});

		var prodcotacao = null;
		if($("#chk_prodcotacao").is(":visible")){
			if($("#chk_prodcotacao").checked()){
				prodcotacao = "S";
			}else{
				prodcotacao = "N";
			}
		}

		$.loading(true);
		$.ajax({
			url: "../ajax/cotacao_filtrarproduto.php",
			data: ({
				distribuicao: $("#distribuicao").val(),
				codestabelec: $("#codestabelec").val(),
				codproduto: $("#bp_codproduto").val(),
				//descricaofiscal: $("#desc_bp_codproduto").val(),
				tipodescricao: $("#bp_tipodescricao").val(),
				descricaofiscal: $("#bp_descricaofiscal").val(),
				codfornec: $("#bp_codfornec").val(),
				coddepto: $("#bp_coddepto").val(),
				codgrupo: $("#bp_codgrupo").val(),
				codsubgrupo: $("#bp_codsubgrupo").val(),
				codfamilia: $("#bp_codfamilia").val(),
				curvaabc: $("#bp_curvaabc").val(),
				relacionado: ($("#chk_relacaoproduto").checked() ? "S" : "N"),
				sugestaocompra: ($("#chk_sugestaocompra").checked() ? "S" : "N"),
				prodcotacao: prodcotacao,
				limpar: "N",
				offset: 0,
				codsimilar: $("#codsimilar").val(),
				limit: "N",
				arr_prod_aux: arr_prod_aux

			}),
			success: function(html){
				$.loading(false);
				ajax_buscarproduto_filtrar = null;
				$("#grid_buscarproduto").html(html);
				$("#grid_buscarproduto input:checkbox").attr("checked", true);
			}
		});
	}
}

function gerar_pedido(){
	$.messageBox({
		type: "info",
		text: "Tem certeza que deseja gerar os pedidos de compra para os fornecedores agora?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				gravar_alteracoes(true);
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}

function gerar_pedido_(){
	$.loading(true);
	$.ajax({
		url: "../ajax/cotacaoselecao_gerarpedido.php",
		data: ({
			codcotacao: $("#codcotacao").val()
		}),
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function gravar_alteracoes(gerar_pedido){
	gerar_pedido = (gerar_pedido === undefined ? false : gerar_pedido);
	if(alterado){
		var arr_produto = new Array();
		$("ul[codfornec]").each(function(){
			var codfornec = $(this).attr("codfornec");
			$(this).find("li[codproduto]").each(function(){
				arr_produto[arr_produto.length] = $(this).attr("codproduto") + ";" + codfornec;
			});
		});
		$.ajaxProgress({
			url: "../ajax/cotacaoselecao_gravar.php",
			type: "POST",
			data: ({
				codcotacao: $("#codcotacao").val(),
				arr_produto: arr_produto,
				gerar_pedido: (gerar_pedido ? "S" : "N")
			}),
			success: function(html){
				extractScript(html);
			}
		});
	}else if(gerar_pedido){
		gerar_pedido_();
	}
}

function sair(force){
	force = (force === undefined ? false : force);
	if(force){
		if(location.pathname.indexOf("cotacao_selecao") > 0){
			window.close();
			opener.recarregar($("#codcotacao").val());
		}else{
			recarregar($("#codcotacao").val());
			$.messageBox("close");
		}
	}else{
		$.messageBox({
			type: "alert",
			text: "Tem certeza que deseja sair?",
			buttons: ({
				"Sim": function(){
					$.messageBox("close");
					sair(true);
				},
				"N\u00E3o": function(){
					$.messageBox("close");
				}
			})
		});
	}
}

function buscarproduto_filtrar(limpar, nova_lista, offset){
	limpar = (limpar === undefined ? false : limpar);
	nova_lista = (nova_lista === undefined ? true : nova_lista);

	if(nova_lista){
		offset = 0;
	}

	if(ajax_buscarproduto_filtrar === null){
		$.loading(true);
		ajax_buscarproduto_filtrar = $.ajax({
			url: "../ajax/cotacao_filtrarproduto.php",
			data: ({
				distribuicao: $("#distribuicao").val(),
				codestabelec: $("#codestabelec").val(),
				codproduto: $("#bp_codproduto").val(),
				//descricaofiscal: $("#desc_bp_codproduto").val(),
				descricaofiscal: $("#bp_descricaofiscal").val(),
				codfornec: $("#bp_codfornec").val(),
				coddepto: $("#bp_coddepto").val(),
				codgrupo: $("#bp_codgrupo").val(),
				codsubgrupo: $("#bp_codsubgrupo").val(),
				codfamilia: $("#bp_codfamilia").val(),
				curvaabc: $("#bp_curvaabc").val(),
				relacionado: ($("#chk_relacaoproduto").checked() ? "S" : "N"),
				sugestaocompra: ($("#chk_sugestaocompra").checked() ? "S" : "N"),
				prodcotacao: ($("#chk_prodcotacao").checked() ? "S" : "N"),
				limpar: (limpar ? "S" : "N"),
				tipodescricao: $("#bp_tipodescricao").val(),
				codsimilar: $("#bp_codsimilar").val(),
				codmarca: $("#bp_codmarca").val(),
				foralinha: $("#bp_foralinha").val(),
				offset: offset
			}),
			success: function(html){
				$.loading(false);
				ajax_buscarproduto_filtrar = null;
				$("#grid_buscarproduto").html(html);
				$("#grid_buscarproduto").scrollTop(0);
				$("#grid_buscarproduto input[codproduto][coluna='quantidade']").filter(":not([cotacao-quantidade-change])").bind("change", function(){
					if(parseFloat($(this).val().replace(",", ".")) > 0){
						$("#grid_buscarproduto :checkbox[codproduto=" + $(this).attr("codproduto") + "]").checked(true);
					}else{
						$("#grid_buscarproduto :checkbox[codproduto=" + $(this).attr("codproduto") + "]").checked(false);
					}
				}).attr("cotacao-quantidade-change", true);

				for(var codproduto in _buscarproduto_data){
					var data = _buscarproduto_data[codproduto];
					var checkbox = $("#grid_buscarproduto :checkbox[codproduto=" + codproduto + "]");
					if($(checkbox).length > 0){
						var tr = $(checkbox).parents(".row");
						$(checkbox).checked(true);
						$(tr).find("[coluna='codunidade']").val(data.codunidade);
						$(tr).find("[coluna='qtdeunidade']").val(data.qtdeunidade).trigger("blur");
						if($("#distribuicao").val() === "N"){
							$(tr).find("[coluna='quantidade']").val(data.quantidade).trigger("blur");
						}else{
							$(tr).find("[coluna='quantidade']").attr("distribuicao", JSON.stringify(data.distribuicao));
						}
					}
				}

				var disabledCSS = {"opacity": 0, "pointer-events": "none"};
				if($("#grid_buscarproduto .row").length === 0){
					$("#bp_paginacao a").css(disabledCSS);
				}else{
					if($("#grid_buscarproduto .row").length < 50){
						$("#bp_paginacao a:last").css(disabledCSS);
					}
					if($("#bp_paginacao_pagina").text() === "1"){
						$("#bp_paginacao a:first").css(disabledCSS);
					}
				}

				$("#bp_paginacao_pagina").bind("keypress", function(e){
					if(e.keyCode === 13){
						var offset = ($(this).val() - 1) * 50;
						if(isNaN(offset) || offset < 0){
							offset = 0;
						}
						buscarproduto_filtrar(false, false, offset);
					}
				}).css("text-align", "center");

				fixa_topogrid("#grid_buscarproduto");
			}
		});
	}
}

function buscarproduto_filtrar_anterior(){
	var pagina = $("#bp_paginacao_pagina").val() - 1;
	pagina = (pagina === 0 ? 1 : pagina);
	var offset = (pagina - 1) * 100;

	buscarproduto_filtrar(false, false, offset);
}

function buscarproduto_filtrar_proximo(){
	var pagina = parseInt($("#bp_paginacao_pagina").val()) + 1;
	pagina = (pagina === 0 ? 1 : pagina);
	var offset = (pagina - 1) * 100;

	buscarproduto_filtrar(false, false, offset);
}

function buscarfornecedor_incluir(substituir){
	if(substituir === undefined){
		substituir = false;
	}
	var arr_codfornec = new Array();
	var data = $("#grid_buscarfornecedor :checkbox[codfornec]:checked").map(function(){
		arr_codfornec[arr_codfornec.length] = $(this).attr("codfornec");
	});
	$.loading(true);
	$.ajax({
		url: "../ajax/cotacao_incluirfornecedor.php",
		data: ({
			substituir: (substituir ? "S" : "N"),
			arr_codfornec: arr_codfornec
		}),
		success: function(html){
			$.loading(false);
			extractScript(html);
			perdido_gravar();
		}
	});
}

function buscarproduto_incluir(substituir){
	substituir = (substituir === undefined ? false : substituir);
	$.modalWindow("close");

	$.loading(true);
	$.ajax({
		type: "POST",
		url: "../ajax/cotacao_incluirproduto.php",
		data: {
			substituir: (substituir ? "S" : "N"),
			distribuicao: $("#distribuicao").val(),
			data: JSON.stringify(_buscarproduto_data)
		},
		success: function(){
			$.loading(false);
			desenhar_gradeproduto();
			perdido_gravar();
		}
	});
}

var _buscarproduto_data = {};
function buscarproduto_temp_atualizar(element){
	var tr = $(element).parents(".row");

	var checkbox = $(tr).find(":checkbox");
	var codproduto = $(checkbox).attr("codproduto");

	if(!$(checkbox).checked()){
		delete _buscarproduto_data[codproduto];
		return true;
	}

	_buscarproduto_data[codproduto] = {
		codproduto: codproduto,
		codunidade: $(tr).find("[coluna='codunidade']").val(),
		qtdeunidade: $(tr).find("[coluna='qtdeunidade']").val(),
		quantidade: ($("#distribuicao").val() === "N" ? $(tr).find("[coluna='quantidade']").val() : 0),
		distribuicao: ($("#distribuicao").val() === "S" ? JSON.parse($(tr).find("[coluna='quantidade']").attr("distribuicao")) : 0)
	};
}

function buscarfornecedor_substituir(){
	$.messageBox({
		type: "alert",
		text: "Tem certeza que deseja substituir a lista de fornecedores j&aacute; existente?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				buscarfornecedor_incluir(true);
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}

function buscarproduto_substituir(){
	$.messageBox({
		type: "alert",
		text: "Tem certeza que deseja substituir a lista de produtos j&aacute; existente?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				buscarproduto_incluir(true);
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}

function cotacaofornecedor(){
	$("#grd_cotacaofornecedor").html("<label>Carregando...</label>");
	$.ajax({
		url: "../ajax/cotacao_cotacaofornecedor.php",
		data: ({
			codcotacao: $("#codcotacao").val()
		}),
		success: function(html){
			$("#grd_cotacaofornecedor").html(html);
		}
	});
	$.modalWindow({
		title: "Selecione o fornecedor para abrir a cota&ccedil;&atilde;o correspondente",
		content: $("#div_cotacaofornecedor"),
		width: "700px",
		closeButton: true
	});
}

function desenhar_gradefornecedor(){
	$.loading(true);
	$.ajax({
		url: "../ajax/cotacao_desenharfornecedor.php",
		success: function(html){
			$.loading(false);
			$("#grid_fornecedor").html(html);
		}
	});
}

var __desenhar_gradeproduto_running = false;
function desenhar_gradeproduto(limpar){
	limpar = (limpar === undefined ? true : limpar);

	if(limpar === true){
		$("#grid_produto .row").remove();
		$.loading(true);
	}

	__desenhar_gradeproduto_running = true;
	$.ajax({
		url: "../ajax/cotacao_desenharproduto.php",
		data: {
			distribuicao: $("#distribuicao").val(),
			offset: $("#grid_produto .row").length
		},
		success: function(html){
			if(limpar === true){
				$.loading(false);
			}
			$("#grid_produto tbody").append(html);
			$.gear();
			if(!in_array($.cadastro.status(), [2, 3])){
				$("#grid_produto").find("input:text, select").disabled(true);
			}
			$("#grid_produto [coluna][codproduto]").not("[configurado]").bind("change", function(){
				$.ajax({
					async: false,
					url: "../ajax/cotacao_atualizarproduto.php",
					data: ({
						codproduto: $(this).attr("codproduto"),
						coluna: $(this).attr("coluna"),
						valor: $(this).val()
					}),
					success: function(){
						perdido_gravar();
					}
				});
			}).attr("configurado", true);
			if(!in_array($.cadastro.status(), [2, 3])){
				$(".itemdelete").hide();
			}
			__desenhar_gradeproduto_running = false;
		}
	});
}

function encerrar_cotacao(){
	$.messageBox({
		type: "alert",
		title: "Aten&ccedil;&atilde;o",
		text: "Ao encerrar a cota&ccedil;&atilde;o, n&atilde;o ser&aacute; permitido qualquer tipo de altera&ccedil;&atilde;o ou digita&ccedil;&atilde;o posteriormente do mesmo.<br>Tem certeza que deseja encerrar a cota&ccedil;&atilde;o agora?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				$.ajaxProgress({
					url: "../ajax/cotacao_encerrar.php",
					data: ({
						codcotacao: $("#codcotacao").val()
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

function abrir_cotacao(){
	$.ajaxProgress({
		url: "../ajax/cotacao_abrir.php",
		data: ({
			codcotacao: $("#codcotacao").val()
		}),
		success: function(html){
			extractScript(html);
		}
	});
}

function importar_coletordados(){
	$.messageBox({
		type: "info",
		text: $("#div_coletordados"),
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				if($("#codestabelec").val().length === 0){
					$.messageBox({
						type: "error",
						text: "Informe o estabelecimento.",
						focusOnClose: $("#codestabelec")
					});
					return false;
				}
				$.ajaxProgress({
					url: "../ajax/cotacao_coletordados.php",
					data: ({
						codestabelec: $("#codestabelec").val(),
						tipoqtdeunidade: ($("#chk_coletordados_unidade").checked() ? "U" : "E")
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

function liberar_cotacao(){
	$.messageBox({
		type: "info",
		text: "Tem certeza que deseja alterar o status da cota&ccedil;&atilde;o para aberto?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				$.loading(true);
				$.ajax({
					url: "../ajax/cotacao_liberar.php",
					data: ({
						codcotacao: $("#codcotacao").val()
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

function limpar_gradefornecedor(){
	$.ajax({
		async: false,
		url: "../ajax/cotacao_limparfornecedor.php"
	});
	desenhar_gradefornecedor();
}

function limpar_gradeproduto(){
	$.ajax({
		async: false,
		url: "../ajax/cotacao_limparproduto.php"
	});
	desenhar_gradeproduto();
}

function perdido_carregar(){
	$.ajax({
		async: false,
		url: "../ajax/cotacao_perdido_carregar.php",
		success: function(result){
			extractScript(result);
		}
	});
}

function perdido_gravar(){
	$.ajax({
		url: "../ajax/cotacao_perdido_gravar.php",
		data: {
			codcotacao: $("#codcotacao").val()
		}
	});
}

function perdido_limpar(){
	$.ajax({
		async: false,
		url: "../ajax/cotacao_perdido_limpar.php"
	});
}

function perdido_preencher(){
	$.ajax({
		async: true,
		url: "../ajax/cotacao_perdido_preencher.php",
		success: function(result){
			extractScript(result);
		}
	});
}

function perdido_questionar(){
	$.messageBox({
		title: "Cotação anterior não finalizada",
		type: "alert",
		text: "Foi encontrado uma cotação não finalizada com os itens já informados.<br>Você deseja continuar a digitação dessa cotação ou abandonar para iniciar uma nova?",
		buttons: {
			"Continuar Cotação": function(){
				$.messageBox("close");
				perdido_carregar();
			},
			"Abandonar Cotação": function(){
				$.messageBox("close");
				cotacaoperdido = false;
				perdido_limpar();
			}
		}
	});
}

function perdido_verificar(){
	$.ajax({
		async: false,
		url: "../ajax/cotacao_perdido_verificar.php",
		success: function(result){
			extractScript(result);
		}
	});
}

function produtos_nao_cotados(){
	window.open("../form/runreport.php?report=relprodnaocotado&format=pdf1&codcotacao=" + $("#codcotacao").val());
}

function ranking(){
	if($("#cotacao_tiporank").val() === "P"){
		window.open("../form/cotacao_ranking.php?codcotacao=" + $("#codcotacao").val() + "&tiporank=P");
	}else if($("#cotacao_tiporank").val() === "F"){
		window.open("../form/cotacao_ranking.php?codcotacao=" + $("#codcotacao").val() + "&tiporank=F");
	}else if($("#cotacao_tiporank").val() === "D"){
		window.open("../form/cotacao_ranking_excel.php?codcotacao=" + $("#codcotacao").val());
	}
	$.modalWindow("close");
}

function recarregar(codcotacao){
	if(String(codcotacao) === $("#codcotacao").val()){
		$.cadastro.retornar();
		$("#codcotacao").val(codcotacao);
		$.cadastro.pesquisar();
	}
}

function remover_fornecedor(codfornec){
	$.messageBox({
		type: "info",
		text: "Tem certeza que deseja remover o fornecedor da lista?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				$.ajax({
					url: "../ajax/cotacao_removerfornecedor.php",
					data: ({
						codfornec: codfornec
					}),
					success: function(html){
						extractScript(html);
						perdido_gravar();
					}
				});
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}

function remover_produto(codproduto){
	$.messageBox({
		type: "info",
		text: "Tem certeza que deseja remover o produto da lista?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				$.ajax({
					url: "../ajax/cotacao_removerproduto.php",
					data: ({
						codproduto: codproduto
					}),
					success: function(html){
						extractScript(html);
						perdido_gravar();
					}
				});
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}

function selecao_fornecedor(param){
	if(param === "N"){
		window.open("../form/cotacao_selecao.php?codcotacao=" + $("#codcotacao").val());
	}else{
		selecao_fornecedor_simples();
	}
}

function suspender_cotacao(){
	$.messageBox({
		type: "info",
		text: "Tem certeza que deseja alterar o status da cota&ccedil;&atilde;o para suspenso?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				$.loading(true);
				$.ajax({
					url: "../ajax/cotacao_suspender.php",
					data: ({
						codcotacao: $("#codcotacao").val()
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

function visualizar_pedido(){
	$("#grd_pedido").html("<label>Carregando...</label>");
	$.ajax({
		url: "../ajax/cotacao_buscarpedido.php",
		data: ({
			codcotacao: $("#codcotacao").val()
		}),
		success: function(html){
			$("#grd_pedido").html(html);
		}
	});
	$.modalWindow({
		title: "Pedidos gerados da cota&ccedil;&atilde;o",
		content: $("#div_pedido"),
		width: "700px",
		closeButton: true
	});
}

function modal_enviar_email(){
	$.modalWindow({		
		content: $("#div_cotacao_modeloemail_pedido"),
		title: "Modelo de email para aviso de pedidos",
		width: "400px",
		closeButton: true
	});	
	$("#pedido_codmodeloemail").disabled(false);
}

function pedido_email(){
	arr_pedido_email_numpedido = [];
	
	$.loading(true);
	$("[pedido_check]:checked").each(function(){	
		$.loading(true,"Enviando para Pedido");
		$.ajax({
			url: "../form/pedido_imprimir.php",
			data: ({
				codestabelec: $("#codestabelec").val(),
				numpedido: $(this).attr("numpedido"),
				enviaremail: "S",			
				codmodeloemail: $("#pedido_codmodeloemail").val()	
			}),
			dataType: "html",
			success: function(html){
				$.loading(false);
				extractScript(html);
			}
		});
	});
}

function selecao_fornecedor_simples(){
	$.loading(true);
	$.ajax({
		url: "../ajax/cotacao_selecaofornecedor.php",
		data: ({
			codcotacao: $("#codcotacao").val()
		}),
		success: function(html){
			$.loading(false);
			$("#grd_selecaofornecedor").html(html);
		}
	});

	$.modalWindow({
		title: "Sele&ccedil;&atilde;o de fornecedor.",
		content: $("#div_selecaofornecedor"),
		width: "700px",
		closeButton: true
	});
}

var distribuicao_element = null;
function distribuicao(element){
	if(!in_array($.cadastro.status(), [2, 3])){
		return false;
	}
	distribuicao_element = element;
	$.loading(true);
	$.ajax({
		url: "../ajax/cotacao_distribuicao.php",
		data: {
			codproduto: $(distribuicao_element).attr("codproduto"),
			distribuicao: JSON.parse($(distribuicao_element).attr("distribuicao"))
		},
		complete: function(){
			$.loading(false);
		},
		success: function(html){
			$("#grid_cotacao_distribuicao").html(html);
			$.modalWindow({
				content: $("#div_cotacao_distribuicao"),
				title: "Distribui&ccedil;&atilde;o entre os estabelecimentos",
				width: "700px"
			});
		}
	});
}

function distribuicao_confirmar(){
	var distribuicao = {};
	var quantidade_total = 0;
	var codproduto = $(distribuicao_element).attr("codproduto");

	$("#grid_cotacao_distribuicao input[codestabelec]").each(function(){
		var quantidade = parseFloat($(this).val().replace(",", "."));
		if(isNaN(quantidade)){
			quantidade = 0;
		}
		quantidade_total += quantidade;
		distribuicao[$(this).attr("codestabelec")] = quantidade.toString();
	});

	$("#grid_buscarproduto :checkbox[codproduto=" + codproduto + "]").checked(quantidade_total > 0);
	$(distribuicao_element).attr("distribuicao", JSON.stringify(distribuicao)).html(number_format(quantidade_total, 4, ",", ""));

	if($(distribuicao_element).parents("#grid_produto").length > 0){
		$.ajax({
			async: false,
			url: "../ajax/cotacao_atualizarproduto.php",
			data: ({
				codproduto: codproduto,
				coluna: "distribuicao",
				valor: JSON.stringify(distribuicao)
			}),
			success: function(){
				perdido_gravar();
			}
		});
	}
	$.modalWindow("close");
}

function cotacao_selecaofornecedor_buscaprecofornec(codproduto, codfornec, codcotacao){
	$.ajax({
		url: "../ajax/cotacao_selecaofornecedor_buscaprecofornec.php",
		data: ({
			codproduto: codproduto,
			codfornec: codfornec,
			codcotacao: codcotacao
		}),
		success: function(html){
			$("[precodecisao=" + codproduto + "]").html(html.replace(".", ","));
		}
	});
}

function gravar_selecao_fornecedor(){
	$.loading(true);
	var arr_codfornec = [];
	var arr_codproduto = [];
	$("#grd_selecaofornecedor [codfornec]").each(function(i){
		arr_codfornec[i] = $(this).val();
		arr_codproduto[i] = $(this).attr("codproduto");
	});


	$.ajax({
		type: "POST",
		url: "../ajax/cotacao_selecaofornecedor_gravaprecofornec.php",
		data: ({
			codcotacao: $("#codcotacao").val(),
			arr_codfornec: arr_codfornec,
			arr_codproduto: arr_codproduto
		}),
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function tipo_ranking(){
	$.modalWindow({
		closeButton: true,
		content: $("#div_tiporanking"),
		title: "Aprensenta&ccedil;&atilde;o do Ranking",
		width: "350px"
	});
	$("#cotacao_tiporank").disabled(false);
}

function dados_produto(codproduto){
	$.ajax({
		url: "../ajax/cotacao_buscardadosproduto.php",
		data: ({
			codestabelec: $("#codestabelec").val(),
			codproduto: codproduto
		}),
		success: function(html){
			extractScript(html);
			$.modalWindow({
				closeButton: true,
				content: $("#div_dadosproduto"),
				title: "Dados do Produto",
				width: "750px"
			});
		}
	});
	$.ajax({
		url: "../ajax/cotacao_vendamedia.php",
		data: ({
			codestabelec: $("#codestabelec").val(),
			codproduto: codproduto
		}),
		success: function(html){
			ajax_pedidoautomatico_vendamedia = null;
			$("#grd_cotacao_vendamendia").html(html);
		}
	});
	$.ajax({
		url: "../ajax/cotacao_ultimascompras.php",
		data: ({
			codestabelec: $("#codestabelec").val(),
			codproduto: codproduto
		}),
		success: function(html){
			$("#grd_cotacao_ultimascompras").html(html);
		}
	});
}

function aviso_cotacao(){
	$.loading(true);
	$.ajax({
		url: "../ajax/cotacao_avisocotacao.php",
		data: ({
			codestabelec: $("#codestabelec").val(),
			codcotacao: $("#codcotacao").val(),
			codmodeloemail: $("#codmodeloemail").val()
		}),
		success: function(html){
			extractScript(html);
			$.loading(false);
		}
	});
}

function aviso_cotacao_modeloemail(){
	$.modalWindow({
		closeButton: true,
		content: $("#div_cotacao_modeloemail"),
		title: "Modelo de email para cotacao",
		width: "400px"
	});
	$("#codmodeloemail").disabled(false);
}

function cotacaorapida(){
	if($("#codestabelec").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe o estabelecimento antes de incluir produtos na lista.",
			afterClose: function(){
				$("#aba_cabecalho").click();
				$("#codestabelec").focus();
			}
		});
		return false;
	}

	$("#grade_cotacaorapida .row").remove();

	$.modalWindow({
		closeButton: true,
		content: $("#div_cotacaorapida"),
		title: "Cotação Rápida",
		width: "500px"
	});
}

function cotacaorapida_incluir(){
	if($("#nfe_tipoqtdenfe").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe o tipo de quantidade.",
			afterClose: function(){
				$("#cotrap_codunidade").focus();
			}
		});
		return false;
	}

	if($("#cotrap_codproduto").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe o produto.",
			afterClose: function(){
				$("#cotrap_codproduto").focus();
			}
		});
		return false;
	}

	if($("#desc_cotrap_codproduto").val() == "(Produto não encontrado)"){
		$.messageBox({
			type: "error",
			text: "Produto não encontrado",
			afterClose: function(){
				$("#cotrap_codproduto").focus();
				$("#cotrap_codproduto").select();
			}
		});
			return false;
		}

	if($("#cotrap_quantidade").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe a quantidade.",
			afterClose: function(){
				$("#cotrap_quantidade").focus;
			}
		});
		return false;
	}

	let variavel = {repetido:"N"};
	$("#grade_cotacaorapida .row[codproduto]").each(function(){
		if($(this).attr("codproduto") == $("#cotrap_codproduto").val()){
			variavel = {repetido:"S"};
			return true;
		}
	});

	$.when.call($, variavel, null).then(function(){
		if(variavel.repetido == "N"){
			$.ajax({
				async:false,
				url: "../ajax/cotacaorapida_verificarepetido.php",
				data: ({
					codproduto: $("#cotrap_codproduto").val(),
				}),
				success: function(res){
					variavel = {repetido:res}
				}
			});
		}

		if(variavel.repetido == "N"){
			$.ajax({
				url: "../ajax/cotacaorapida_linha.php",
				data: ({
					codproduto: $("#cotrap_codproduto").val(),
					quantidade: $("#cotrap_quantidade").val(),
					codunidade: $("#cotrap_codunidade").val(),
					descricao: $("#desc_cotrap_codproduto").val()
				}),
				success: function(html){
					$("#grade_cotacaorapida tbody").append(html);
					$("#desc_cotrap_codproduto").val("");
					$("#cotrap_codproduto").val("");
					$("#cotrap_quantidade").val("");
					$("#cotrap_codproduto").focus();

					var e = $("#grade_cotacaorapida tr:last");
					$(e).insertBefore("#grade_cotacaorapida tbody tr:first");
				}
			});
		}else{
			$.messageBox({
				type: "error",
				text: "Produto ja informado",
				focusOnClose: $("#cotrap_codproduto")
			});
		}
	});
}

function cotacaorapida_concluir(){
	var dados = [];
	$("#grade_cotacaorapida .row[codproduto]").each(function(){
		dados.push({
			codproduto: $(this).attr("codproduto"),
			codunidade: $(this).attr("codunidade"),
			quantidade: $(this).attr("quantidade")
		});
	});

	$.loading(true);
	$.ajax({
		url: "../ajax/cotacaorapida_concluir.php",
		type: "POST",
		data: ({
			dados: JSON.stringify(dados)
		}),
		success: function(result){
			$.loading(false);
			extractScript(result);
		}
	});
}

function upload_cotacao(codfornec){
	$("#upload_quantidadeunidade").disabled(false);
	$("#arqcotacao").disabled(false);
	$("#upload_codfornec").val(codfornec);
	$.modalWindow({
		closeButton: true,
		content: $("#div_uploadcotacao"),
		title: "Upload cota&ccedil;&atilde;o",
		width: "450px",
		position: ({
			top: "7%"
		})
	});
}

function upload_arqfornec(){
	$.ajaxProgress({
		url: "../ajax/cotacao_arquivofornec.php",
		data: ({
			codcotacao: $("#codcotacao").val(),
			codfornec: $("#upload_codfornec").val(),
			quantidadeunidade: $("#upload_quantidadeunidade").val()
		}),
		success: function(result){
			extractScript(result);
		}
	});
}

function cotacaotemporario(){
	$.ajax({
		url: "../ajax/cotacao_temporario.php",
		data: ({
			codproduto: $("#codproduto").val(),
			codestabelec: $("#codestabelec").val()
		}),
		success: function(result){
			extractScript(result);
		}
	});
}
