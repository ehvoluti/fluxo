$.cadastro.before.cancelar = function(){
	if($.cadastro.status() == 2){
		limpar_grade();
		limpar_estabelecimento();
	}
	return true;
}

$.cadastro.before.gravar = function(){
	if($("#listaestabelecimentos input:checkbox:checked").length == 0){
		messageBox({
			type: "error",
			text: "&Eacute; necess&aacute;rio informar a(s) para a oferta.",
			foncusOnClose: $("#listaestabelecimentos")
		});
		return false;
	}
}

$.cadastro.before.deletar = function(){
	if($("#status").val() == "A"){
		$.messageBox({
			type: "alert",
			text: "A oferta será encerrada antes de ser deletada, deseja continuar?",
			buttons: ({
				"Sim": function(){
					$.messageBox("close");
					var data = $.cadastro.getData();
					$.extend(data,{action:"delete"});
					$.loading(true);
					$.ajax({
						url:"../ajax/cadastro_action.php",
						type:"POST",
						data:data,
						success:function(html){
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
	}else{
		return true;
	}
}

$.cadastro.after.alterar = function(){
	$("#btnIncluirItem").disabled(false);
	$("#griditoferta").find("input[preco]").disabled(false);
	$("#griditoferta").find("img[remover]").show();
	$("#status").disabled(true);
}

$.cadastro.after.carregar = function(){
	$("#btnImprimir").hide();
	desenhar_grade();
	carregar_estabelecimentos();
	if($("#listaestablecimentos input:checbox.checked").length == 0){
		$("#btnImprimir").show();
	}
}

$.cadastro.after.clonar = function(){
	$("#descricao,#datainicio,#datafinal,#horainicio,#horafinal").val("");
	$("#status").val("I");
}

$.cadastro.after.deletar = function(){
	limpar_grade();
	limpar_estabelecimento();
}

$.cadastro.after.inserir = function(){
	$("#horainicio").val("00:00:00");
	$("#horafinal").val("23:59:59");
	$("#tipopreco").val("V");
	$("#btnIncluirItem").disabled(false);
	$("#status").val("I").disabled(true);
}

$.cadastro.after.limpar = function(){
	$("#btnIncluirItem").disabled(true);
}

$.cadastro.after.novapesquisa = function(){
	limpar_grade();
	limpar_estabelecimento();
}

$.cadastro.after.retornar = function(){
	limpar_grade();
	limpar_estabelecimento();
}

$(document).bind("ready", function(){
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnImprimir\" value=\"Imprimir Ofertas\" onclick=\"ofertaimprimir()\">");
	$("#btnImprimir").css("margin-right", "6px");
	$("#codproduto").bind("change", function(){
		$.ajax({
			async: false,
			url: "../ajax/oferta_buscarpreco.php",
			data: ({
				codproduto: $("#codproduto").val(),
				tipopreco: $("#tipopreco").val()
			}),
			success: function(html){
				extractScript(html);
			}
		});
		verificar_similar();
	});
	$("#listaestabelecimentos input:checkbox[key]").bind("change", function(){
		$.ajax({
			async: false,
			url: "../ajax/ofertaestab_gravarremoverestab.php",
			data: ({
				codestabelec: $(this).attr("key"),
				checked: ($(this).checked() ? "S" : "N")
			}),
			success: function(html){
				extractScript(html);
			}
		});
	});
	$("#grid_buscarproduto").bind("scroll", function(){
		var outerHeight = parseInt($(this).height());
		var scrollPos = parseInt($(this).scrollTop());
		var innerHeight = parseInt($(this).children().filter("table").height());
		if(innerHeight - scrollPos - outerHeight < outerHeight){
			buscarproduto_filtrar(false, false);
		}
	});
});

function desenhar_grade(){
	$.ajax({
		async: false,
		url: "../ajax/oferta_desenhargrade.php",
		success: function(html){
			$("#griditoferta").html(html);
			$.gear();
			if(!in_array($.cadastro.status(), [2, 3])){
				$("#griditoferta").find("input[preco]").disabled(true);
				$("#griditoferta").find("img[remover]").hide();
			}else{
				$("#griditoferta").find("input[preco]").disabled(false);
				$("#griditoferta").find("img[remover]").show();
			}
			$("#griditoferta").find("input[preco]").each(function(){
				$(this).attr("oldvalue", $(this).val());
			}).bind("change", function(){
				var preco = $(this).val();
				if(preco.length == 0 || parseFloat(preco.replace(",", ".")) == 0){
					$(this).val($(this).attr("oldvalue"));
				}else{
					$(this).attr("oldvalue", $(this).val());
					$.ajax({
						sync: false,
						url: "../ajax/oferta_alteraritem.php",
						data: ({
							preco: $(this).val(),
							codproduto: $(this).attr("codproduto")
						}),
						success: function(html){
							extractScript(html);
						}
					})
				}
			});
		}
	});
}

function limpar_grade(){
	$.ajax({
		async: false,
		url: "../ajax/oferta_limpargrade.php",
		success: function(html){
			desenhar_grade();
			carregar_estabelecimentos()
		}
	});
}

function carregar_estabelecimentos(){
	$.ajax({
		async: false,
		url: "../ajax/oferta_carregarestabelecimentos.php",
		success: function(html){
			extractScript(html);
		}
	});
}

function limpar_estabelecimento(){
	$.ajax({
		async: false,
		url: "../ajax/oferta_limparestabelecimentos.php",
		success: function(html){
			carregar_estabelecimentos();
		}
	});
}

function incluir_item(){
	if($("#listaestabelecimentos input:checkbox:checked").length == 0){
		$.messageBox({
			type: "error",
			text: "Informe os estabelecimentos para a oferta.",
			foncusOnClose: $("#listaestabelecimentos")
		});
		return false;
	}
	if($("#tipopreco").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o tipo de pre&ccedil;o.",
			focusOnClose: $("#tipopreco")
		});
		return false;
	}
	var win = $.modalWindow({
		closeButton: true,
		content: $("#divIncluirItem"),
		title: "Incluir Produto",
		width: "500px"
	});
	verificar_similar();
}

function incluir_item_(){
	if($("#codproduto").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o produto a ser inclu&iacute;do na lista de ofertas",
			focusOnClose: $("#codproduto")
		});
		return false;
	}
	var precooferta = parseFloat($("#it_preco").val().replace(",", "."));
	if(precooferta = 0){
		$.messageBox({
			type: "error",
			text: "Informe o pre&ccedil;o de oferta para o produto",
			focusOnClose: $("#it_preco")
		});
		return false;
	}
	$.modalWindow("close");

	$("#listaestabelecimentos input:checkbox[key]:checked").each(function(){
		alert($(this).attr("key"));
	});

	$.ajax({
		async: false,
		url: "../ajax/oferta_gravaritem.php",
		data: ({
			arr_codestabelec: $("#listaestabelecimentos input:checkbox[key]:checked").map(function(){
				return $(this).attr("key");
			}).get(),
			codproduto: $("#codproduto").val(),
			preco: $("#it_preco").val(),
			similar: ($("#it_similar").checked() ? "S" : "N")
		}),
		success: function(html){
			extractScript(html);
			$("#codproduto,#it_preco").val("").description();
		}
	})
}
;

function remover_item(codproduto){
	$.messageBox({
		type: "info",
		text: "Deseja remover o produto da lista?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				$.ajax({
					async: false,
					url: "../ajax/oferta_removeritem.php",
					data: ({
						codproduto: codproduto
					}),
					success: function(html){
						extractScript(html);
					}
				})
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	})
}

function ofertaimprimir(){
	$.messageBox({
		type: "info",
		text: "Confirma a impress&atilde;o da listagem das oferta?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				window.open("../form/oferta_imprimir.php?codoferta=" + $("#codoferta").val());
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}

function verificar_similar(){
	$.ajax({
		async: false,
		url: "../ajax/oferta_verificarsimilar.php",
		data: ({
			codproduto: $("#codproduto").val()
		}),
		success: function(html){
			extractScript(html);
		}
	});
}

function buscarproduto(){
	var codestabelec = $('#listaestabelecimentos input:checkbox:checked').map(function(){
		return ($(this).attr("key"));
	}).get();
	if(codestabelec == ""){
		$.messageBox({
			type: "error",
			text: "Informe os estabelecimentos onde ser&atilde;o aplicados as ofertas."
		});
		$.loading(false);
		return false;
	}
	if($("#datainicio").val().length == 0 || $("#datafinal").val().length == 0 || $("#horainicio").val().length == 0 || $("#horafinal").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o periodo da oferta."
		});
		$.loading(false);
		return false;
	}
	$("#div_buscarproduto").find("input:text,select").not("#tipodescricao").val("");
	$.modalWindow({
		closeButton: true,
		content: $("#div_buscarproduto"),
		title: "Busca de Produtos",
		width: "850px",
		position: ({
			top: "7%"
		})
	});
	buscarproduto_filtrar(true);
}

var ajax_buscarproduto_filtrar = null;
function buscarproduto_filtrar(limpar, nova_lista){
	var codestabelec = $('#listaestabelecimentos input:checkbox:checked').map(function(){
		return ($(this).attr("key"));
	}).get();
	limpar = (typeof (limpar) == "undefined" ? false : limpar);
	nova_lista = (typeof (nova_lista) == "undefined" ? true : nova_lista);
	if(ajax_buscarproduto_filtrar == null){
		if(nova_lista){
			$.loading(true);
		}

		ajax_buscarproduto_filtrar = $.ajax({
			url: "../ajax/oferta_filtrarproduto.php",
			data: ({
				codestabelec: codestabelec,
				codproduto: $("#bp_codproduto").val(),
				descricaofiscal: $("#desc_bp_codproduto").val(),
				codfornec: $("#bp_codfornec").val(),
				reffornec: $("#bp_reffornec").val(),
				coddepto: $("#bp_coddepto").val(),
				codgrupo: $("#bp_codgrupo").val(),
				codsubgrupo: $("#bp_codsubgrupo").val(),
				codfamilia: $("#bp_codfamilia").val(),
				limpar: (limpar ? "S" : "N"),
				descricaofiscal: $("#descricaofiscal").val(),
				tipodescricao: $("#tipodescricao").val(),
				codsimilar: $("#codsimilar").val(),
				offset: (nova_lista ? 0 : $("#grid_buscarproduto tr.row").length),
				codoferta:$("#codoferta").val(),
				datainicio:$("#datainicio").val(),
				horainicio:$("#horainicio").val(),
				datafinal:$("#datafinal").val(),
				horafinal:$("#horafinal").val(),
				tipopreco:$("#tipopreco").val(),
				foralinha:$("#foralinha").val()
			}),
			success: function(html){
				$.loading(false);
				ajax_buscarproduto_filtrar = null;
				if(nova_lista){
					$("#grid_buscarproduto").html(html);
				}else{
					$("#grid_buscarproduto").append(html);
					var grid_1 = $("#grid_buscarproduto table.grid").first();
					var grid_2 = $(grid_1).next();
					$(grid_2).find("tr.row").each(function(){
						$(grid_1).find("tbody")[0].appendChild(this);
					});
					$(grid_2).remove();
				}
			}
		});
	}
}

function buscarproduto_incluir(){
	$.modalWindow("close");
	$.loading(true);
	$.ajax({
		url: "../ajax/oferta_incluirproduto.php",
		data: ({arr_codproduto: $("#grid_buscarproduto :checkbox[codproduto]:checked").map(function(){
				var elems = $("#grid_buscarproduto [codproduto='" + $(this).attr("codproduto") + "']");
				return $(this).attr("codproduto") + "=" + $(elems).filter("[preco='true']").val();
			}).get().join("&"),
			arr_codestabelec: (($("#listaestabelecimentos input:checkbox[key]:checked").map(function(){
				return $(this).attr("key");
			}).get())),
			codoferta:$("#codoferta").val(),
			datainicio:$("#datainicio").val(),
			horainicio:$("#horainicio").val(),
			datafinal:$("#datafinal").val(),
			horafinal:$("#horafinal").val(),
			tipopreco:$("#tipopreco").val()
		}),
		type: "POST",
		success: function(html){
			$.loading(false);
			desenhar_gradeproduto();
			extractScript(html);
		}
	});
}

function desenhar_gradeproduto(){
	$.loading(true);
	$.ajax({
		url: "../ajax/oferta_desenharproduto.php",
		success: function(html){
			$.loading(false);
			$("#griditoferta").html(html);
			$.gear();
			if(!in_array($.cadastro.status(), [2, 3])){
				$("#griditoferta").find("input:text,select").disabled(true);
			}
			$("#griditoferta [coluna][codproduto]").bind("change", function(){
				$.ajax({
					async: false,
					url: "../ajax/oferta_atualizarproduto.php",
					data: ({
						codproduto: $(this).attr("codproduto"),
						coluna: $(this).attr("coluna"),
						valor: $(this).val()
					})
				});
			});
			$("#griditoferta").find("input[preco]").each(function(){
				$(this).attr("oldvalue", $(this).val());
			}).bind("change", function(){
				var preco = $(this).val();
				if(preco.length == 0 || parseFloat(preco.replace(",", ".")) == 0){
					$(this).val($(this).attr("oldvalue"));
				}else{
					$(this).attr("oldvalue", $(this).val());
					$.ajax({
						sync: false,
						url: "../ajax/oferta_alteraritem.php",
						data: ({
							preco: $(this).val(),
							codproduto: $(this).attr("codproduto")
						}),
						success: function(html){
							extractScript(html);
						}
					})
				}
			});
		}
	});
}

function adicionar_similar(codproduto, preco){
	$.ajax({
		async: false,
		url: "../ajax/oferta_adicionarsimilar.php",
		data: ({
			codproduto: codproduto,
			preco: preco,
			foralinha: $("#foralinha").val()
		}),
		success: function(){
			desenhar_gradeproduto();
		}
	});
}

function adicionar_composicao(codproduto, preco){
	$.ajax({
		async: false,
		url: "../ajax/oferta_adicionarcomposicao.php",
		data: ({
			codproduto: codproduto,
			preco: preco
		}),
		success: function(){
			desenhar_gradeproduto();
		}
	});
}

function alert_oferta(text){
	$.messageBox({
		type: "info",
		text: text
	});
}