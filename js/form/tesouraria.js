$(document).bind("ready", function(){

	$("#btnCadNovaPesquisa").after("<input type=\"button\" value=\"Financeiro\" style=\"margin-left:6px\" onclick=\"financeiro()\">");

	$("#codestabelec").bind("change", function(){
		tesourariafinaliz_desenhar(false);
	});

	/*
	$("#codfunc").bind("change", function(){
		operador_ok();
	});
	*/
});

function acerta_rodape(){
	var rodape = [];
	$("#div_tesourariafinaliz table thead tr th").each(function(i){
		rodape.push($(this));
	});

	$("#div_tesourariafinaliz table tfoot tr td").each(function(i){
		$(this).width($(rodape[i]).width());
	});
}

function calcularcoluna(coluna){
	var colcalc = [];
	if(coluna == undefined){
		colcalc.push("valortotal");
		colcalc.push("fundocaixa");
		colcalc.push("totalizador");
	}else{
		colcalc.push($(coluna).attr("coluna"));
	}

	for(var i = 0; i <= (colcalc.length - 1); i++){
		var total = 0;
		$("#div_tesourariafinaliz table tbody tr").each(function(j){
			console.info()
			$(this).find("td").each(function(){
				var var_coluna = $(this).find("input");
				if($(var_coluna).attr("coluna") == colcalc[i]){
					var valor = parseFloat($(var_coluna).val().replace(".","").replace(",","."));
					total = total + (isNaN(valor) ? 0 : valor);
				}
			});
		});

		if(total > 0){
			if(colcalc[i] == "valortotal"){
				$("#div_rp_totalgeral").html("<b>" + number_format(total,2,",", ".") + "</b>");
			}
			if(colcalc[i] == "fundocaixa"){
				$("#div_rp_fundocaixa").html("<b>" + number_format(total,2,",", ".") + "</b>");
			}
			if(colcalc[i] == "totalizador"){
				$("#div_rp_totalizadores").html("<b>" + number_format(total,2,",", ".") + "</b>");
				$("#valortotal").val(number_format(total,2,",", "."));
				$("#recebimento").val(number_format(0,2,",", "."));
			}
		}
	}
}

function operador_ok(){
	if($("#codfunc").val().length > 0){
		$.ajax({
			url: "../ajax/tesouraria_operadorok.php",
			type: "POST",
			dataType: "html",
			data: {
				codfunc: $("#codfunc").val()
			},
			beforeSend: function(xHr){
				$.loading(true);
			},
			success: function(html){
				extractScript(html);
			},
			complete: function(xHr){
				$.loading(false);
			}
		});
	}
}

function financeiro(){
	financeiro_limpar();
	financeiro_carregar();
	$.modalWindow({
		content: $("#div_financeiro"),
		title: "Finanaceiro da Tesouraria",
		width: "600px"
	});
}

function financeiro_cancelar(){
	$.messageBox({
		type: "alert",
		title: "Aten&ccedil;&atilde;o",
		text: "Ao cancelar, todos os lan&ccedil;amentos financeiros ser&atilde;o exclu&iacute;dos, ap&oacute;s isso o processo n&atilde;o poder&aacute; ser desfeito.<br>Tem certeza que deseja exclu&iacute;r agora?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				$.loading(true);
				$.ajax({
					url: "../ajax/tesouraria_financeiro_cancelar.php",
					data: ({
						codestabelec: $("#financeiro_codestabelec").val(),
						dtmovto: $("#financeiro_dtmovto").val()
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

function financeiro_carregar(){
	$.loading(true);
	$.ajax({
		url: "../ajax/tesouraria_financeiro_carregar.php",
		data: ({
			codestabelec: $("#financeiro_codestabelec").val(),
			dtmovto: $("#financeiro_dtmovto").val()
		}),
		success: function(html){
			$.loading(false);
			$("#div_financeiro_tesouraria").html(html);
			$("#financeiro_codestabelec").focus();
		}
	});
}

function financeiro_gerar(){
	$.loading(true);
	$.ajax({
		url: "../ajax/tesouraria_financeiro_gerar.php",
		data: ({
			codestabelec: $("#financeiro_codestabelec").val(),
			dtmovto: $("#financeiro_dtmovto").val()
		}),
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function financeiro_limpar(){
	$("#financeiro_codestabelec,#financeiro_dtmovto").val("").disabled(false);
	$("#btn_financeiro_carregar").disabled(false);
	$("#btn_financeiro_gerar,#btn_financeiro_cancelar").disabled(true);
}

function tesourariafinaliz_atualizar(){
	var arr_tesourariafinaliz = {};
	$("#div_tesourariafinaliz [codfinaliz]").each(function(){
		if(typeof (arr_tesourariafinaliz[$(this).attr("codfinaliz")]) == "undefined"){
			arr_tesourariafinaliz[$(this).attr("codfinaliz")] = {};
		}
		arr_tesourariafinaliz[$(this).attr("codfinaliz")][$(this).attr("coluna")] = $(this).val();
	});
	$.ajax({
		async: false,
		url: "../ajax/tesouraria_tesourariafinaliz_atualizar.php",
		data: ({
			arr_tesourariafinaliz: JSON.stringify(arr_tesourariafinaliz)
		}),
		success: function(html){
			extractScript(html);
		}
	});
}


function tesourariafinaliz_desenhar(preencher){
	preencher = (typeof (preencher) == "undefined" ? false : preencher);
	$.ajax({
		async: false,
		url: "../ajax/tesouraria_tesourariafinaliz_desenhar.php",
		data: ({
			codestabelec: $("#codestabelec").val(),
			preencher: preencher
		}),
		success: function(html){
			$("#div_tesourariafinaliz").html(html);
			$.gear();
			$("input[codfinaliz]").bind("change", function(){
				tesourariafinaliz_atualizar();
			});
			if(!in_array($.cadastro.status(), [2, 3, 4])){
				$("input[codfinaliz]").disabled(true);
			}
			if(preencher){
				tesourariafinaliz_preencher();
			}
			acerta_rodape();
		}
	});
}

function tesourariafinaliz_preencher(){
	$.ajax({
		url: "../ajax/tesouraria_tesourariafinaliz_preencher.php",
		async: false,
		success: function(html){
			extractScript(html);
			calcularcoluna();
		}
	});
}

function iniciar_fechamento_manual(){
	if($("#div_fech_codestabelec").val().length == 0){
		$.messageBox({
			type: "error",
			title: " ",
			text: "Favor preencher o estabelecimento."
		});
		return false;
	}

	if($("#div_fech_dtmovto").val() == 0){
		$.messageBox({
			type: "error",
			title: " ",
			text: "Favor preencher a data do movimento."
		});
		return false;
	}

	if($("#div_fech_codfunc").val() ==0){
		$.messageBox({
			type: "error",
			title: " ",
			text: "Favor preencher o operador."
		});
		return false;
	}

	$.modalWindow('close');
	$("#codestabelec").val($("#div_fech_codestabelec").val());
	$("#codfunc").val($("#div_fech_codfunc").val());
	$("#dtmovto").val($("#div_fech_dtmovto").val());
	$("#caixa").val("");
	$("#codestabelec").trigger("change");
	$("#valortotal").disabled(false);
	$("#caixa").disabled(false);
}

function iniciar_fechamento(){
	setTimeout(function(){ __iniciar_fechamento(); }, 3000);
}

function __iniciar_fechamento(){
	if($("#div_fech_codestabelec").val().length == 0){
		$.messageBox({
			type: "error",
			title: " ",
			text: "Favor preencher o estabelecimento."
		});
		return false;
	}

	if($("#div_fech_codfunc").val() ==0){
		$.messageBox({
			type: "error",
			title: " ",
			text: "Favor preencher o operador."
		});
		return false;
	}

	if($("#div_fech_dtmovto").val() == 0){
		$.messageBox({
			type: "error",
			title: " ",
			text: "Favor preencher a data do movimento."
		});
		return false;
	}

	if($("#div_fech_caixa").val().length == 0){
		$.messageBox({
			type: "error",
			title: " ",
			text: "Favor preencher o numero do caixa."
		});
		return false;
	}

	$.modalWindow('close');
	$("#codestabelec").val($("#div_fech_codestabelec").val());
	$("#codfunc").val($("#div_fech_codfunc").val());
	$("#dtmovto").val($("#div_fech_dtmovto").val());
	$("#caixa").val($("#div_fech_caixa").val());
	$("#codestabelec").trigger("change");

	$.ajax({
		url: "../ajax/tesouraria_carregar_totais_pdv.php",
		type: "POST",
		dataType: "html",
		data:{
			dtmovto     : $("#dtmovto").val(),
			operador    : $("#codfunc").val(),
			codestabelec: $("#codestabelec").val(),
			caixa       : $("#caixa").val()
		},
		beforeSend: function(xHr){
			$.loading(true);
		},
		success: function(html){
			extractScript(html)
			calcularcoluna();
			$("#valortotal").disabled($("#valortotal").val().length == 0)
		},
		complete: function(xHr){
			$.loading(false);
		},
		error:function(xHr){
			$.cadastro.cancelar();
		}
	});
}

function limpar_temporario(){
	$.ajax({
		url: "../ajax/tesouraria_tesourariafinaliz_limpar.php",
		type: "POST",
		dataType: "html",
		beforeSend: function(xHr){
			$.loading(true);
		},
		success:function(html){

		},
		complete: function(xHr){
			$.loading(false);
		}
	});
}

var salvar_forcado = false;
$.cadastro.before.salvar = function(){
	if($("#recebimento").val().length === 0){
		$.messageBox({
			type: "error",
			title: " ",
			text: "Informe o recebimento."
		});
		return false;
	}

	var totalinformado = parseFloat($("#valortotal").val().replace(".","").replace(",", ".")) + parseFloat($("#recebimento").val().replace(".","").replace(",", "."));
	var totalcalculado = $("input[codfinaliz]:not(input[coluna=fundocaixa]):not(input[coluna=totalizador])").map(function(){
		return ($(this).val().length > 0 ? parseFloat($(this).val().replace(".","").replace(",", ".")) : 0);
	}).get().sum();
	var fundocaixa = $("input[coluna=fundocaixa]").map(function(){
		return ($(this).val().length > 0 ? parseFloat($(this).val().replace(",", ".")) : 0);
	}).get().sum();

	if(param_tesouraria_fundocaixa == "1"){
		totalcalculado += fundocaixa;
	}else{
		totalcalculado -= fundocaixa;
	}


	if(!salvar_forcado && totalinformado != totalcalculado){
		$.messageBox({
			type: "alert",
			title: "Aten&ccedil;&atilde;o",
			text: "O total informado do fechamento n&atilde;o confere com o total calculado de cada finalizadora informada.<br><br>Total informado: " + number_format(totalinformado, 2, ",", ".") + "<br>Total calculado: " + number_format(totalcalculado, 2, ",", ".") + "<br>Diferença: " + number_format(Math.abs(totalinformado - totalcalculado), 2, ",", ".") + "<br><br>Deseja prosseguir mesmo com a diferença?<br>",
			buttons: ({
				"Sim": function(){
					$.messageBox("close");
					salvar_forcado = true;
					$.cadastro.salvar();
					salvar_forcado = false;
				},
				"N\u00E3o": function(){
					$.messageBox("close");
					salvar_forcado = false;
				}
			})
		});
		return false;
	}

	return true;
};

$.cadastro.after.alterar = function(){
	$("#status,#codestabelec,#dtmovto").disabled(true);
};

$.cadastro.after.carregar = function(){
	tesourariafinaliz_desenhar(true);
	switch($("#status").val()){
		case "0":
			$("#btnCadAlterar").show();
			$("#btnCadExcluir").show();
			break;
		case "1":
			$("#btnCadAlterar").hide();
			$("#btnCadExcluir").hide();
			break;
	}
};

$.cadastro.after.cancelar = function(){
	limpar_temporario();
}

$.cadastro.after.retornar = function(){
	limpar_temporario();
}

$.cadastro.after.inserir = function(){
	$("#status, #codestabelec, #codfunc, #dtmovto, #caixa, #valortotal").val("").disabled(true);
	$("#div_fech_codestabelec, #div_fech_codfunc, #div_fech_dtmovto, #div_fech_caixa").val("");
	$("#div_fech_codestabelec").trigger("change");
	$.modalWindow({
		content: $("#div_fechamento_caixa"),
		closeButton: true,
		title: "Fechamento Caixa Operador",
		width: "650px",
		hint: "Preencha as Informações Para Prosseguir."
	});
};

$.cadastro.after.limpar = function(){
	tesourariafinaliz_desenhar(false);
};