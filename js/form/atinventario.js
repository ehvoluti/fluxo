$(document).bind("ready", function(){
	$("input:file").bind("change", function(){
		var file = $(this);
		$.ajax({
			url: "../ajax/atinventario_dirname.php",
			data: ({
				codinventario: $("#codinventario").val()
			}),
			success: function(html){
				file.upload({
					filename: html,
					onComplete: function(){
						$.messageBox({
							type: "success",
							text: "Arquivo enviado com sucesso!",
						});
						coletordados_importar();
						return true;
					}
				});
			}
		});
	});
	$("#ulInfColetorRadio input:radio").bind("click", function(){
		$("#ulInfColetorRadio input:radio").not(this).attr("checked", false);
	});

	if(request_codinventario.length > 0){
		$("#codinventario").val(request_codinventario);
		buscar();
	}
	$("#divGridItens").bind("scroll", function(){
		var outerHeight = parseInt($(this).height());
		var scrollPos = parseInt($(this).scrollTop());
		var innerHeight = parseInt($(this).children().filter("table").height());
		if(innerHeight - scrollPos - outerHeight < outerHeight){
			carregarlista();
		}
	});
	$("#codproduto,#embalagem,#quantidade").bind("keypress", function(e){
		if(e.keyCode == 13){
			acumular_individual();
		}
	}).not("#codproduto,#quantidade").bind("focus", function(){
		if($(this).val().length == 0){
			$(this).val("1,0000");
			$(this).select();
		}
	});

	$("#codproduto").change(function(){
		$.ajax({
			url: "../ajax/atinventario_acumulado.php",
			data: ({
				codproduto: $("#codproduto").val(),
				codinventario: $("#codinventario").val(),
				contagem: $("#contagem").val()
			}),
			success: function(html){
				extractScript(html);
			}
		});
	});

	$("#codinventario").change(function(){
		$.ajax({
			url: "../ajax/inventario_contagem.php",
			data: ({
				codinventario: $("#codinventario").val()
			}),
			success: function(html){
				extractScript(html);
			}
		});
	});

	$("#contagem").change(function(){
		buscar();
	});
});

function acumular_individual(){
	if($("#codproduto").val().length == 0){
//			$(this).trigger("blur");
		$("#codproduto").focus();
		return false;
	}
	if( ini_atinventbloqcampo_embalagem == "N"){
		if($("#embalagem").val().length == 0){
	//			$(this).trigger("blur");
			$("#embalagem").focus();
			return false;
		}
	}
	if($("#quantidade").val().length == 0){
//			$(this).trigger("blur");
		$("#quantidade").focus();
		return false;
	}
	var acumulado = $("[codproduto='" + $("#codproduto").val() + "'][acumulado]");
	$("#acumulado").val("0,0000");
	var embalagem = 1
	if( ini_atinventbloqcampo_embalagem == "N"){
		embalagem = $("#embalagem").val()
	}

	if($(acumulado).length > 0){
		var val_acumulado = parseFloat($(acumulado).html().replace(",", ".")) + (parseFloat(embalagem.replace(",", ".")) * parseFloat($("#quantidade").val().replace(",", ".")));
		$(acumulado).html(number_format(val_acumulado, 4, ",", ""));
		$("[codproduto='" + $("#codproduto").val() + "']:checkbox").attr("checked", true);
		gravartemporario($("#codproduto").val());
	}else{
		$.ajax({
			url: "../ajax/atinventario_individual.php",
			data: ({
				codinventario: $("#codinventario_").val(),
				contagem: $("#contagem").val(),
				codproduto: $("#codproduto").val(),
				embalagem: embalagem,
				quantidade: $("#quantidade").val()
			}),
			success: function(html){
				extractScript(html);
			}
		});
	}
	if( ini_atinventbloqcampo_embalagem == "N"){
		$("#codproduto,#embalagem,#quantidade,#desc_codproduto").val("").first().focus();
	}else{
		$("#codproduto,#quantidade,#desc_codproduto").val("").first().focus();
	}
}

function buscar(){
	if($("#codinventario").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o invent&aacute;rio.",
			focusOnClose: $("#codinventario")
		});
		return false;
	}
	$("#codinventario_").val($("#codinventario").val());
	$("#divGridItens").html("");
	$.loading(true);
	$.ajax({
		url: "../ajax/atinventario_buscar.php",
		data: ({
			contagem: $("#contagem").val(),
			codinventario: $("#codinventario").val()
		}),
		dataType: "html",
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

var ajax_lista = null;
function carregarlista(){
	if(ajax_lista == null){
		var offset = $("#divGridItens tr").length - 1;
		if(offset == -1){
			$.loading(true);
		}
		ajax_lista = $.ajax({
			url: "../ajax/atinventario_lista.php",
			data: ({
				query: $("#query").val(),
				offset: offset
			}),
			dataType: "html",
			success: function(html){
				$.loading(false);
				if($("#divGridItens table").length == 0){
					$("#divGridItens").html(html);
				}else{
					$("#divGridItens table:first").append(html);
				}
				$("#div_preenchmanual").fadeIn("slow");
				$("#divListaItens:not(:visible)").animate({
					height: "toggle"
				}, "slow");
				$.gear();
				$("#divGridItens input[embalagem]:not([gear_inventario])").bind("keypress", function(e){
					if(e.keyCode == 13){
						$(this).parent().parent().parent().find("input[quantidade]").focus();
					}
				});
				$("#divGridItens input[quantidade]:not([gear_inventario])").bind("keypress", function(e){
					if(e.keyCode == 13){
						if($(this).val().length > 0){
							acumulaquant($(this).attr("codproduto"));
						}
						if( ini_atinventbloqcampo_embalagem == "N"){
							$(this).parents("tr").first().next().find("input[embalagem]").focus();
						}else{
							$(this).parents("tr").first().next().find("input[quantidade]").focus();
						}
					}
				});
				$("#divGridItens input[codproduto]:not([gear_inventario])").bind("change", function(){
					gravartemporario($(this).attr("codproduto"));
				}).attr("gear_inventario", true);
				ajax_lista = null;
				$("#codproduto").focus();
			}
		});
	}
}

function gravartemporario(codproduto){
	var elements = $("#divGridItens [codproduto='" + codproduto + "']");
	$.ajax({
		url: "../ajax/atinventario_itinventariotemp.php",
		data: ({
			codinventario: $("#codinventario_").val(),
			codproduto: codproduto,
			contagem: $("#contagem").val(),
			checkbox: ($(elements).filter(":checkbox").checked() ? "S" : "N"),
			embalagem: $(elements).filter("[embalagem]").val(),
			quantidade: $(elements).filter("[quantidade]").val(),
			acumulado: $(elements).filter("[acumulado]").html()
		}),
		dataType: "html"
	});
}

var ajaxCheckAll = null;
function gravarcheckall(){
	if(ajaxCheckAll != null){
		ajaxCheckAll.abort();
	}
	ajaxCheckAll = $.ajax({
		url: "../ajax/atinventario_checkall.php",
		data: ({
			codinventario: $("#codinventario_").val(),
			checkbox: ($("#checkall").checked() ? "S" : "N")
		}),
		dataType: "html",
		success: function(html){
			extractScript(html);
			ajaxCheckAll = null;
		}
	});
}

function acumulaquant(codproduto){
	var elements = $("#divGridItens [codproduto='" + codproduto + "']");
	var embalagem = elements.filter("[embalagem]");
	var quantidade = elements.filter("[quantidade]");
	if(embalagem.val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a quantidade por embalagem.",
			focusOnClose: embalagem
		});
	}else if(quantidade.val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a quantidade.",
			focusOnClose: quantidade
		});
	}else{
		var acumulado = elements.filter("[acumulado]");
		if(acumulado.html().length == 0){
			acumulado.html("0,0000");
		}
		acumulado.html(number_format(parseFloat(acumulado.html().replace(",", ".")) + parseFloat(embalagem.val().replace(",", ".")) * parseFloat(quantidade.val().replace(",", ".")), 4, ",", ""));
		embalagem.val("1,0000");
		quantidade.val("");
		elements.filter(":checkbox").attr("checked", true);
		gravartemporario(codproduto);
	}
}

function coletordados(){
	if(param_sistema_tiposervidor == "0"){
		$.messageBox({
			type: "info",
			text: $("#divInfColetor"),
			buttons: ({
				"Importar": function(){
					coletordados_importar();
				},
				"Cancelar": function(){
					$.messageBox("close");
				}
			})
		});
	}else{
		$.messageBox({
			type: "info",
			text: $("#divInfColetor"),
			height: "200px",
			buttons: ({
			})
		});
		$("[messagebox=true]").css("height", "200px");
	}

}

function coletordados_importar(){
	$.messageBox("close");
	$.ajaxProgress({
		url: "../ajax/atinventario_coletordados.php",
		data: ({
			codinventario: $("#codinventario_").val(),
			infcoletor: $("#ulInfColetorRadio input:radio:checked").val(),
			contagem: $("#contagem").val()
		}),
		success: function(html){
			buscar();
			extractScript(html);
		}
	});
}

function correcaoproduto(){
	correcaoproduto_limpar();
	$.modalWindow({
		title: "Corre&ccedil;&atilde;o de Produtos",
		content: $("#divCorrecaoProduto"),
		width: "500px"
	});
}

function correcaoproduto_buscar(){
	if($("#cp_codproduto").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o produto.",
			focusOnClose: $("#codproduto")
		});
		return false;
	}
	$.loading(true);
	$.ajax({
		url: "../ajax/atinventario_correcaoproduto_buscar.php",
		data: ({
			codinventario: $("#codinventario_").val(),
			contagem: $("#contagem").val(),
			codproduto: $("#cp_codproduto").val()
		}),
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function correcaoproduto_corrigir(){
	if($("#cp_sldcorr").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a corre&ccedil;&atilde;o de estoque.",
			focusOnClose: $("#cp_sldcorr")
		});
		return false;
	}

	var acumulado = $("[codproduto='" + $("#cp_codproduto").val() + "'][acumulado]");
	if($(acumulado).length > 0){
		$(acumulado).html(number_format(parseFloat($("#cp_sldcorr").val().replace(",", ".")), 4, ",", ""));
		$("[codproduto='" + $("#cp_codproduto").val() + "']:checkbox").attr("checked", true);
		gravartemporario($("#cp_codproduto").val());
		correcaoproduto_limpar();
	}else{
		$.loading(true);
		$.ajax({
			url: "../ajax/atinventario_correcaoproduto_corrigir.php",
			data: ({
				codinventario: $("#codinventario_").val(),
				contagem: $("#contagem").val(),
				codproduto: $("#cp_codproduto").val(),
				acumulado: $("#cp_sldcorr").val()
			}),
			success: function(html){
				$.loading(false);
				extractScript(html);
			}
		});
	}
}

function correcaoproduto_limpar(){
	$("#cp_sldcong,#cp_sldcont,#cp_sldcorr").val("");
	$("#cp_codproduto").val("").description().focus();
}

function divergencia(){
	$.messageBox({
		type: "info",
		title: "Relat&oacute;rio de Diverg&ecirc;ncias",
		text: $("#boxdivergencia"),
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				window.open("../form/atinventario_divergencia.php?codinventario=" + $("#codinventario_").val() + "&divergencia=" + $("[name=divergencia]").filter(':checked').val());
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}

function gravar(){
	$.ajax({
		url: "../ajax/atinventario_buscardivergencia.php",
		data: ({
			codinventario: $("#codinventario_").val(),
			contagem: $("#contagem").val()
		}),
		dataType: "html",
		success: function(html){
			if(html == "divergencia"){
				$.messageBox({
					type: "alert",
					title: "Aten&ccedil;&atilde;o",
					text: "As contagens se encontram em divergência, para atualizar  com  a Contagem " + $("#contagem").val() + ", selecione a mesma para confirmar: <br> Contagem " + combobox_contagem + "<br>Deseja realmente prosseguir?",
					buttons: ({
						"Sim": function(){
							if($("#contagem").val() == $("#validcontagem").val()){
								$.messageBox("close");
								verificar_naoselecionado();
							}else{
								$.messageBox({
									type: "error",
									title: "Aten&ccedil;&atilde;o",
									text: "A contagem selecionada diverge da contagem atual.",
								});
								$.messageBox("close");
							}
						},
						"N\u00E3o": function(){
							$.messageBox("close");
						}
					})
				});
			}else{
				verificar_naoselecionado();
			}
		}
	});
}

function gravar_(){
	$.messageBox({
		type: "alert",
		title: "Aten&ccedil;&atilde;o",
		text: "Ao atualizar o invent&aacute;rio n&atilde;o ser&aacute; mais poss&iacute;vel fazer qualquer tipo de altera&ccedil;&atilde;o no invent&aacute;rio e seus itens.<br>Confira a quantidade acumulada de cada item e se os itens que ser&atilde;o inventariados est&atilde;o com suas caixas marcadas na lista.<br><br>Deseja gravar as atualiza&ccedil;&atilde;o do invent&aacute;rio agora?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				$.loading(true);
				$.ajax({
					url: "../ajax/atinventario_gravar.php",
					data: ({
						codinventario: $("#codinventario_").val(),
						contagem: $("#contagem").val()
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

function limparitem(codproduto){
	$.messageBox({
		type: "info",
		text: "Tem certeza que deseja limpar a contagem do item?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				var elements = $("#divGridItens [codproduto='" + codproduto + "']");
				elements.filter("[acumulado]").html("0,0000");
				elements.filter(":checkbox").attr("checked", false);
				gravartemporario(codproduto);
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}

function scanmob(){
	$.messageBox({
		type: "alert",
		title: "Aten&ccedil;&atilde;o",
		text: "Ao carregar as coletas do ScanMob, os valores carregados substituirão os valor já informados.<br>Deseja carregar os produtos coletados do ScanMob agora?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				$.loading(true);
				$.ajax({
					url: "../ajax/atinventario_scanmob.php",
					data: ({
						codinventario: $("#codinventario_").val()
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

function verificar_naoselecionado(){
	if($("#divGridItens :checkbox:not(:checked)").size() > 0){
		$.messageBox({
			type: "alert",
			title: "Aten&ccedil;&atilde;o",
			text: "Existem itens congelados que não estão marcados para atualização, caso eles não forem marcados seus estoques não sofrerão as alterações.<br> deseja marcar todos os itens agora?",
			buttons: ({
				"Sim": function(){
					$("#divGridItens tbody [type=checkbox]").checked(true);
					$.messageBox("close");
					$.loading(true);
					setTimeout(function(){ gravar_(); }, 1000);
					$.loading(false);
				},
				"N\u00E3o": function(){
					$.messageBox("close");
					gravar_();
				},
				"Cancelar": function(){
					$.messageBox("close");
				}
			})
		});
	}else{
		gravar_();
	}
}