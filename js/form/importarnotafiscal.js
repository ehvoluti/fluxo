var geranotafiscal = "S";
var natoperacaosubst = "";
var arquivo_carregado = "";
var controleoperacao = "N";

$(document).bind("ready", function(){
	var params = urlParams();
	if(params.arquivo !== undefined && params.codestabelec !== undefined && params.tipoqtdenfe !== undefined && params.numpedido !== undefined && params.nfegerarnota !== undefined){
		$("#nfe_codestabelec").val(params.codestabelec);
		$("#nfe_tipoqtdenfe").val(params.tipoqtdenfe);
		$("#ped_numpedido").val(params.numpedido);
		$("#ped_codestabelec").val(params.codestabelec);
		$("#nfe_gerarnota").val(params.nfegerarnota);
		notafiscaleletronica_carregarxml(params.arquivo);
	}

	// Ajusta o botao (lupa) de pesquisa do pedido
	var search_pedido = $("#ped_numpedido").parent().next().find("img")[0];
	search_pedido.onclick = null;
	$(search_pedido).bind("click", function(){
		var elements = $("#ped_numpedido");
		elements.push($("#ped_codestabelec"));
		$(elements).locate({
			table: "pedido",
			filter: "operacao:'CP'"
		});
	});

	$("input:file").bind("change", function(){
		if($("#nfe_codestabelec").val().length != 0){
			buscar_filename();
			$(this).upload({
				filename: ($("#filename").val()),
				onComplete: function(){
					$.ajax({
						url: "../ajax/importarnotafiscal_notafiscaleletronica_renomeararquivoupload.php",
						async: false,
						data: {
							codestabelec: $("#nfe_codestabelec").val(),
							filename: $("#filename").val()
						}
					});
					$.messageBox({
						type: "success",
						text: "Arquivo enviado com Sucesso!",
						focusOnClose: $(this)
					});
				}
			});
		}else{
			$.messageBox({
				type: "error",
				text: "Escolha um Estabelecimento.",
				focusOnClose: $(this)
			});
		}
	});
	$("#div_botoes_01 input:button").hide();
	switch(tipo){
		case "cupom":
			$("#btn_cupomfiscal").show();
			break;
		case "nfe":
			$("#btn_notafiscaleletronica").show();
			break;
	}
	$("#table_total label").css("padding-left", "20px");
	cancelar();
	$("#nfe_gerarnota option").first().remove();

	$("#codparceiro").change(function(){
		if($("#codparceiro").length > 0){
			$.ajax({
				url: "../ajax/importarnotafiscal_comprador.php",
				data: {
					codparceiro: $("#codparceiro").val()
				},
				success: function(html){
					extractScript(html);
				}
			});
		}
	});

	$("#btnGravarProdXML").disabled(true);
	$("#prod_codproduto").keyup(function(){
		if($("#prod_codproduto").val().length <= 0){
			$("#btnGravarProdXML").disabled(true);
		}else{
			$("#btnGravarProdXML").disabled(false);
		}
	});
	$("#prod_codproduto").change(function(){
		if($("#prod_codproduto").val().length <= 0){
			$("#btnGravarProdXML").disabled(true);
		}else{
			$("#btnGravarProdXML").disabled(false);
		}
	});
});

function buscar_filename(){
	$.ajax({
		async: false,
		url: "../ajax/importarnotafiscal_upload.php",
		data: ({
			codestabelec: $("#nfe_codestabelec").val()
		}),
		success: function(html){
			$("#filename").val(html);
		}
	});
}

function cancelar(){
	$("#grade_itens").html("");
	$("#div_principal").find("input:text,select,textarea").val("").disabled(true);
	$("label[for='codfunc']").html("Funcion&aacute;rio:");
	$("#div_botoes_01").show();
	$("#div_botoes_02").hide();
	$("body").focusFirst();
}

function desenharitem(){
	$("#grade_itens").html("<label>Carregando...</label>");
	$.ajax({
		url: "../ajax/importarnotafiscal_desenharitem.php",
		success: function(html){
			$("#grade_itens").html(html).find("input[seqitem][coluna],select[seqitem][coluna]").each(function(){
				$(this).attr("old_value", $(this).val());
			}).bind("change", function(){
				if(!(parseFloat($(this).val().replace(",", ".")) > 0)){
					$(this).val($(this).attr("old_value"));
					return false;
				}else{
					$(this).attr("old_value", $(this).val());
				}
				$.ajax({
					url: "../ajax/importarnotafiscal_alteraritem.php",
					data: ({
						seqitem: $(this).attr("seqitem"),
						coluna: $(this).attr("coluna"),
						valor: $(this).val()
					})
				});
			});
			$.gear();
		}
	});
}

function gravar(){
	var abortar = false;

	$("#natoperacao,#codcondpagto,#codespecie,#dtentrega,#modfrete").each(function(){
		if(!abortar){
			if($(this).val() === null || $(this).val().length <= 0){
				$.messageBox({
					type: "error",
					text: "O campo <b>" + $("label[for='" + $(this).attr("id") + "']").html().replace(":", "") + "</b> &eacute; de preenchimento obrigat&oacute;rio.",
					focusOnClose: $(this)
				});
				abortar = true;
			}
		}
	});

	if(bloqdtentmaiordtatu === "S"){
		dtentrega = $("#dtentrega").val().split("/");
		if(parseInt(dtentrega[2] + dtentrega[1] + dtentrega[0]) > parseInt(server.year + server.month + server.day)){
			$.messageBox({
				type: "error",
				title: "",
				text: "A data de entrega deve ser menor ou igual a data atual."
			});
			return false;
		}
	}

	if(abortar){
		return false;
	}
	$.loading(true);
	$.ajax({
		url: "../ajax/importarnotafiscal_notafiscaleletronica_gravarxml.php",
		data: ({
			codestabelec: $("#nfe_codestabelec").val()
		}),
		dataType: "html",
		async: "false",
		success: function(html){
			if(html.length > 0){
				$.loading(false);
				if(param_percvarcusto_bloquear == "S"){
					$.messageBox({
						type: "error",
						text: "O custo unit&aacute;rio informado para os produtos <br>" + html + " ultrapassa o percentual m&aacute;ximo de varia&ccedil;&atilde;o."
					});
				}else if(param_percvarcusto_bloquear == "P"){
					$.messageBox({
						type: "error",
						text: "O custo unit&aacute;rio informado para os produtos <br>" + html + " ultrapassa o percentual m&aacute;ximo de varia&ccedil;&atilde;o. <br> Deseja prosseguir com a gravação da nota fiscal?",
						buttons: ({
							"Sim": function(){
								$.superVisor({
									"type": "compra",
									success: function(){
										gravardados_alerta();
										$.messageBox("close");
									},
									fail: function(){
										$.messageBox("close");
									}
								});
							},
							"N\u00E3o": function(){
								$.messageBox("close");
							}
						})
					});
				}else{
					$.messageBox({
						type: "alert",
						text: "O custo unit&aacute;rio informado para os produtos <br>" + html + " ultrapassa o percentual m&aacute;ximo de varia&ccedil;&atilde;o. <br> Deseja prosseguir com a gravação da nota fiscal?",
						buttons: ({
							"Sim": function(){
								$.loading(true);
								gravardados_alerta();
								$.messageBox("close");
							},
							"N\u00E3o": function(){
								abortar = true;
								$.messageBox("close");
							}
						})
					});
				}
			}else{
				gravardados_alerta();
			}
		}
	});

}

function gravardados_alerta(){
	$.ajax({
		url: "../ajax/importarnotafiscal_gravarxml_alerta.php",
		data: ({
			codestabelec: $("#nfe_codestabelec").val()
		}),
		dataType: "html",
		success: function(html){
			if(html.length > 0){
				$.loading(false);
				$.messageBox({
					type: "alert",
					text: html,
					buttons: ({
						"Sim": function(){
							gravardados();
							$.messageBox("close");
						},
						"N\u00E3o": function(){
							$.messageBox("close");
						}
					})
				});
			}else{
				gravardados();
			}
		}
	});
}

function gravardados(){
	$.loading(true);

	var data = $("#div_principal").find("input:hidden,input:text,select").add("#ped_numpedido").map(function(){
		var id = $(this).attr("id");
		var value = $(this).val();
		id = id.replace("ped_", "");
		return (id + "=" + value);
	}).get();

	$.ajax({
		url: "../ajax/importarnotafiscal_gravar.php",
		data: data.join("&") + "&gerarnota=" + $("#nfe_gerarnota").val() + "&observacao=" + $("#observacao").val(),
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function notafiscaleletronica(){
	$("#ped_codestabelec,#ped_numpedido,#chavenfedown").val("");
	$("#chavenfedown").attr("placeholder", "Informe aqui a chave da NF-e para download direto do sefaz");
	$.modalWindow({
		content: $("#infopedido"),
		title: "Pedido de compra relacionado",
		width: "550px",
		hint: "Prossiga com os dados em branco para importar o XML sem um pedido de compra relacionado."
	});
}

function notafiscaleletronica_verificar_pedido_download(){
	if($("#ped_codestabelec").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o estabelecimento.",
			focusOnClose: $("#ped_codestabelec")
		});
		return false;
	}

	temparamcomissao = false;
	$(json_paramcomissao).each(function(){
		if(this.fields.codestabelec == $("#ped_codestabelec").val()){
			temparamcomissao = true;
		}
	});

	if(!temparamcomissao){
		$.messageBox({
			type: "error",
			text: "Informe dados da comissão no <a  onclick=\"openProgram('CadParamComissao')\">Parâmetros de Comissão</a> para esse estabelecimento.",
			focusOnClose: $("#ped_codestabelec")
		});
		return false;
	}

	if(($("#chavenfedown").val().length > 0 || $("#ped_numpedido").val().length > 0) && $("#ped_codestabelec").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o estabelecimento da chave da NF-e informada.",
			focusOnClose: $("#ped_codestabelec")
		});
		return false;
	}
	if($("#ped_numpedido").val().length > 0 && $("#chavenfedown").val().length == 0){
		notafiscaleletronica_verificarpedido();
	}else if($("#chavenfedown").val().length > 0){
		$.loading(true);
		$.ajax({
			url: "../ajax/importarnotafiscal_notafiscaleletronica_downloadsefaz.php",
			data: ({
				codestabelec: $("#ped_codestabelec").val(),
				chavenfe: $("#chavenfedown").val(),
				ped_numpedido: $("#ped_numpedido").val()
			}),
			success: function(html){
				$.loading(false);
				extractScript(html);

			}
		});
	}else{
		notafiscaleletronica_selecionarxml();
	}
}

function notafiscaleletronica_verificarpedido(){
	if($("#ped_numpedido").val().length > 0){
		$.ajax({
			async: false,
			url: "../ajax/importarnotafiscal_notafiscaleletronica_verificarpedido.php",
			data: ({
				codestabelec: $("#ped_codestabelec").val(),
				numpedido: $("#ped_numpedido").val()
			}),
			success: function(html){
				extractScript(html);
			}
		});
	}
}

function notafiscaleletronica_selecionarxml(){
	$.modalWindow("close");
	$("#nfe_codestabelec,#nfe_tipoqtdenfe").disabled(false).val("");
	if($("#ped_codestabelec").val().length > 0){
		$("#nfe_codestabelec").val($("#ped_codestabelec").val()).disabled(true);
	}
	$.modalWindow({
		content: $("#notafiscaleletronica"),
		title: "Importa&ccedil;&atilde;o de Nota Fiscal Eletr&ocirc;nica (XML)",
		width: "500px"
	});
	if($("#nfe_codestabelec").val().length > 0){
		notafiscaleletronica_recarregarlista();
	}else{
		$("#div_gradexml").html("");
	}
}

function notafiscaleletronica_divergenciasfiscal(arquivo){
	$.ajax({
		url: "../ajax/importarnotafiscal_divergenciafiscal.php",
		dataType: "html",
		data: ({
			codestabelec: $("#nfe_codestabelec").val(),
			tipoqtdenfe: $("#nfe_tipoqtdenfe").val(),
			arquivo: arquivo
		}),
		success: function(texto){
			if(texto.length > 0){
				$("#gridinfoclassfiscal").html(texto);
				$.modalWindow({
					closeButton: false,
					content: $("#infoclassfiscal"),
					title: "Classifica&ccedil;&atilde;o fiscal de ICMS da nota diferente do cadastro de produto",
					width: "1040px"
				});
				$("#gridinfoclassfiscal").html(texto).find("[codproduto]").bind("change", function(){
					$.ajax({
						url: "../ajax/importarnotafiscal_divergenciafiscal_alterarvalor.php",
						data: ({
							codproduto: $(this).attr("codproduto"),
							coluna: $(this).attr("coluna"),
							valor: ($(this).is(":checkbox") ? ($(this).checked() ? "S" : "N") : $(this).val())
						})
					});
				});
			}
		}
	});
}

function notafiscaleletronica_carregarxml(arquivo){
	arquivo_carregado = arquivo;
	if($("#nfe_codestabelec").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o estabelecimento.",
			focusOnClose: $("#nfe_codestabelec")
		});
	}else{
		// [robson]Rodrigo camicado reclamou dessa validação antigamente não era assim, no arquivo
		// ../ajax/importarnotafiscal_notafiscaleletronica_carregarxml.php proximo a linha 70 ele
		// pega a embalagem do cadastro de fornecedor, caso o cliente não tenha digitado,
		// ai sim ele valida, por isso comentei essa validação aqui.
		/*if($("#nfe_tipoqtdenfe").val() == ""){
			$.messageBox({
				type: "error",
				text: "Selecione a unidade de entrada.",
				focusOnClose: $("#nfe_tipoqtdenfe")
			});
			return false;
		}*/

		$.modalWindow("close");
		$.loading(true);
		$.ajax({
			url: "../ajax/importarnotafiscal_notafiscaleletronica_carregarxml.php",
			data: ({
				codestabelec: $("#nfe_codestabelec").val(),
				tipoqtdenfe: $("#nfe_tipoqtdenfe").val(),
				numpedido: $("#ped_numpedido").val(),
				arquivo: arquivo
			}),
			dataType: "html",
			success: function(html){
				$.loading(false);
				extractScript(html);
			}
		});
	}
}

function notafiscaleletronica_recarregarlista(){
	if($("#nfe_codestabelec").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o estabelecimento.",
			focusOnClose: $("#nfe_codestabelec")
		});
		return false;
	}else{
		$("#div_gradexml").html("<label>Carregando arquivos. Por favor aguarde...</label>");
		$.ajax({
			url: "../ajax/importarnotafiscal_notafiscaleletronica_recarregarxml.php",
			data: ({
				codestabelec: $("#nfe_codestabelec").val(),
				numnotafis: $("#nfe_numnotafis").val(),
				numpedido: $("#ped_numpedido").val(),
				chavenfe: $("#chavedown").val()
			}),
			dataType: "html",
			success: function(html){
				$("#div_gradexml").html(html);
			}
		});
	}
}

function posgravacao(idnotafiscal, guiagnre){
	var guiagnre = (typeof (guiagnre) == "undefined" ? false : guiagnre);

	$("#span_posgravacao_numnotafis").html($("#numnotafis").val());

	$("#a_posgravacao_notafiscal").unbind("click").bind("click", function(){
		openProgram("NFEntrada", "idnotafiscal=" + idnotafiscal);
	});
	$("#a_posgravacao_lancamento").unbind("click").bind("click", function(){
		$.lancamento({idnotafiscal: idnotafiscal});
	});
	$("#a_posgravacao_ajusteproduto").unbind("click").bind("click", function(){
		openProgram("AjusteProduto", "filtrar=S&codestabelec=" + $("#codestabelec").val() + "&numnotafis=" + $("#numnotafis").val() + "&serie=" + $("#serie").val()+ "&codfornec=" + $("#codparceiro").val());
	});
	$("#a_posgravacao_guiagnre").unbind("click").bind("click", function(){
		openProgram("NFEntrada", "idnotafiscal=" + idnotafiscal + "&guiagnre");
	});

	$("#a_posgravacao_controleoperacao").unbind("click").bind("click", function(){
		openProgram("NumeroSerieOp","codestabelec=" + $("#codestabelec").val() + "&numnotafis=" + $("#numnotafis").val() + "&serie="+ $("#serie").val() + "&operacao=" + $("#operacao").val());
	});

	if(controleoperacao == "S"){
		$("#li_posgravacao_controleoperacao").show();
	}else{
		$("#li_posgravacao_controleoperacao").hide();
	}

	if(guiagnre){
		$("#li_posgravacao_guiagnre").show();
	}else{
		$("#li_posgravacao_guiagnre").hide();
	}

	$.modalWindow({
		title: "Nota Fiscal gravada com sucesso",
		content: $("#posgravacao"),
		width: "500px"
	});

	custobonificado(idnotafiscal);
}

function notafiscaleletronica_divergenciasfiscal_gravar(){
	$.loading(true);
	$.ajaxProgress({
		url: "../ajax/importarnotafiscal_divergenciafiscal_gravar.php",
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function verificardivergencia(){
	if($("#ped_codestabelec").val().length == 0 || $("#ped_numpedido").val().length == 0){
		gravar();
	}else{
		if($("#modfrete").val().length <= 0){
			$.messageBox({
				type: "error",
				text: "O campo <b>" + $("label[for='" + $(this).attr("id") + "']").html().replace(":", "") + "</b> &eacute; de preenchimento obrigat&oacute;rio.",
				focusOnClose: $(this)
			});
		}else{
			$.loading(true);
		}
		$.ajax({
			url: "../ajax/importarnotafiscal_divergencia_verificar.php",
			data: ({
				codestabelec: $("#ped_codestabelec").val(),
				numpedido: $("#ped_numpedido").val()
			}),
			success: function(html){
				$.loading(false);
				extractScript(html);
			}
		});
	}
}

function verificardivergencia_exibir(){
	$.ajax({
		async: false,
		url: "../ajax/importarnotafiscal_divergencia_grade.php",
		success: function(html){
			$("#grd_divergencia").html(html);
		}
	});
	$.modalWindow({
		title: "Diverg&ecirc;ncias",
		content: $("#divergencia"),
		width: "800px"
	});
}

function verificardivergencia_supervisor(){
	$.superVisor({
		"type": "compra",
		success: function(){
			gravar();
		}
	});
}

function pedido_qtdeatendia(){
	$.ajax({
		url: "../ajax/notafiscal_pedido_qtdeatendida.php",
		data: ({
			codestabelec: $("#codestabelec").val(),
			numpedido: $("#ped_numpedido").val(),
			operacao: "CP"
		}),
		dataType: "html",
		success: function(html){
			extractScript(html);
		}
	});
}

function custobonificado(idnotafiscal){
	$.ajax({
		url: "../ajax/notafiscal_custobonificado.php",
		data: ({
			idnotafiscal: idnotafiscal
		}),
		dataType: "html",
		success: function(html){
			extractScript(html);
		}
	});
}

function natoperacao_verificaitens(){
	if(ativanatimportxml != "S"){
		return true;
	}
	$.messageBox({
		type: "alert",
		title: "Atenção",
		text: "Deseja alterar a natureza de operação dos itens da nota?",
		buttons: ({
			"Sim": function(){
				$.ajax({
					url: "../ajax/pedido_natoperacaosubst.php",
					data: ({
						natoperacao: $("#natoperacao").val()
					}),
					dataType: "html",
					async: false,
					success: function(html){
						natoperacaosubst = html.trim();
						natoperacaosubst = natoperacaosubst.length > 0 ? natoperacaosubst : $("#natoperacao").val();

						$("[coluna=natoperacao]").each(function(){
							if($(this).attr("tptribicms") == "F"){
								$(this).val(natoperacaosubst);
							}else{
								$(this).val($("#natoperacao").val());
							}
							$(this).change();
						});
					}
				});

				$.messageBox("close");
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}

function excluirxml(xml){
	$.messageBox({
		type: "alert",
		title: "Atenção",
		text: "Tem certeza que deseja excluir o xml?",
		buttons: ({
			"Sim": function(){
				$.ajax({
					url: "../ajax/notafiscal_excluirxml.php",
					data: ({
						xml: xml
					}),
					dataType: "html",
					success: function(html){
						extractScript(html);
					}
				});
				$.messageBox("close");
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}

function buscaprodutoxml(codfornec,reffornec,descricao, ean){
	$.modalWindow({
		content: $("#buscaprodutoxml"),
		title: "Cadastrar referencia fornecedor",
		width: "500px"
	});

	$("#prod_codfornec").val(codfornec);
	$("#prod_codfornec").trigger("change");
	$("#desc_prod_codfornec").disabled(true);
	$("#prod_reffornec").val(reffornec);
	$("#prod_ean").val(ean);
	$("#desc_prod_codproduto").val(descricao);
	$("#prod_codproduto").val("");
	$("#btnGravarProdXML").disabled(true);
}

function buscaprodutoxml_gravar(){
	if($("#prod_codproduto").val().length <= 0){
		return false;
	}

	$.ajax({
		url: "../ajax/importarnotafiscal_gravarprodutoxml.php",
		data: ({
			codfornec: $("#prod_codfornec").val(),
			codproduto: $("#prod_codproduto").val(),
			reffornec: $("#prod_reffornec").val(),
			ean: $("#prod_ean").val()
		}),
		dataType: "html",
		success: function(html){
			extractScript(html);
		}
	});
}

function importarnotafiscal_incluirmix(arr_codproduto){
	$.ajax({
		url: "../ajax/importarnotafiscal_incluirmix.php",
		data: ({
			arr_codproduto: arr_codproduto,
			codestabelec: $("#codestabelec").val()
		}),
		dataType: "html",
		success: function(html){
			extractScript(html);
		}
	});
}

function super_percvarcusto(){
	$.superVisor({
		"type": "compra",
		success: function(){

		},
		fail: function(){
			cancelar();
		}
	});
}
