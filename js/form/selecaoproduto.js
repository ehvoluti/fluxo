var __btn_continuar_timeout = null;

$(document).bind("ready", function(){

	// Desabilita alguns filtros do produto dependendo da tabela
	switch(tabela){
		case "pedido":
			$("#div-filtroproduto-relacaoproduto").hide();
			$("#div-filtroproduto-prodcotacao").hide();
			break;
	}

	// Mostrar titulo da grade ao passar mouse em cima
	$(".gridborder").each(function(){
		var h2 = $(this).prev();
		if($(h2).length > 0 && $(h2).is("h2.grid-title")){
			$(h2).width($(this).width());
			$(this).bind("mouseenter", function(){
				$(this).prev().stop().fadeIn("fast");
			}).bind("mouseleave", function(){
				$(this).prev().stop().fadeOut("fast");
			});
		}
	});

	// Abre o cadastro do produto ao clicar na descricao
	$("#produto-descricaofiscal").bind("click", function(){
		openProgram("Produto", "codproduto=" + $("#produto-codproduto").val());
	});

	// Exibe o botao "confirmar e continuar..."
	$("#btn-confirmar, #btn-continuar").bind("mouseenter", function(){
		$("#btn-continuar").css("display", "inline-block");
		if(__btn_continuar_timeout !== null){
			clearTimeout(__btn_continuar_timeout);
		}
	}).bind("mouseleave", function(){
		__btn_continuar_timeout = setTimeout(function(){
			$("#btn-continuar").css("display", "none");
		}, 500);
	});

	// Carrega os estabelecimentos a serem vizualizados
	var arr_codestabelec_aux = JSON.parse($.cookie("selecaoproduto-selecaoestabelecimento"));
	if(arr_codestabelec_aux !== null){
		arr_codestabelec = arr_codestabelec_aux;
	}
	selecaoestabelecimento_carregar();

	switch(tabela){
		case "cotacao":
			// Chama a tela de filtro de produtos
			filtroproduto();
			break;
		case "pedido":
			if(arr_codfornec.length === 1){
				$("#filtroproduto-codfornec").val(arr_codfornec[0]).description();
				filtroproduto_aplicar();
			}
			break;
	}

	// Verifica se a janela pai esta vinculada
	verificar_janela_pai();
});

$(window).bind("resize", function(){
	$(".fancyTable").parents(".gridborder").each(function(){
		if($(this).find("tbody").css("position") === "absolute"){
			$(this).find("tbody").css("position", "relative");
			fixa_topogrid("#" + $(this).attr("id"));
		}
	});
});

function calcular_totais(){
	let qtdeitens = 0;
	let totalliquido = 0;
	$("#grid-produto :checkbox[codproduto]:checked").each(function(){
		let tr = $(this).closest("tr.row");

		let qtdeunidade = strtofloat($(tr).find("[coluna='qtdeunidade']").val());
		let quantidade = strtofloat($(tr).find("[coluna='quantidade']").val());
		let preco = 0;
		let valdescto = 0;
		if(tabela === "pedido"){
			preco = strtofloat($(tr).find("[coluna='preco']").val());
			valdescto = strtofloat($(tr).find("[coluna='valdescto']").val());
		}

		qtdeitens += quantidade * qtdeunidade;
		totalliquido += (quantidade * preco) - (quantidade * valdescto);
	});

	$("#total-qtdeitens").html(number_format(qtdeitens, 3, ",", "."));
	$("#total-totalliquido").html("R$ " + number_format(totalliquido, 2, ",", "."));
}

function cancelar(){
	$.messageBox({
		text: "Tem certeza que deseja cancelar a seleção de produtos?",
		buttons: {
			"Sim": function(){
				window.opener.$.selecaoProduto.settings.fail();
				window.close();
			},
			"Não": function(){
				$.messageBox("close");
			}
		}
	});
}

function colorir_grade(element){
	var cores = ["#c0d0e4", "#fbaeae", "#bdeac1", "#fffec9", "#f5d7f9", "#ffd9a7", "#bcfafb", "#d7f3b3", "#f9f9f9", "#ececec"];
	var i = 0;
	$(element).find(".row").each(function(){
		$(this).attr("cor", cores[i]).css("background-color", cores[i]);
		if(++i === cores.length){
			i = 0;
		}
	});
}

function confirmar(close){
	close = (close === undefined ? true : close);

	var data = [];
	$("#grid-produto :checkbox[codproduto]:checked").each(function(){
		var tr = $(this).parents("tr.row");
		var codproduto = $(this).attr("codproduto");

		var row = {
			codproduto: codproduto,
			codunidade: $(tr).find("[coluna='codunidade']").val(),
			qtdeunidade: $(tr).find("[coluna='qtdeunidade']").val(),
			quantidade: $(tr).find("[coluna='quantidade']").val(),
			distribuicao: JSON.parse($(tr).find("[coluna='quantidade']").attr("distribuicao"))
		}

		switch(tabela){
			case "pedido":
				row.preco = $(tr).find("[coluna='preco']").val();
				row.valdescto = $(tr).find("[coluna='valdescto']").val();
				break;
		}

		data.push(row);
	});
	window.opener.$.selecaoProduto.settings.success(data);
	if(close){
		window.close();
	}
}

function confirmarcontinuar(){
	$("#grid-produto :checkbox[codproduto]:checked").each(function(){
		arr_codproduto.push($(this).attr("codproduto"));
	});
	confirmar(false);
	filtroproduto_aplicar();
}

function filtroproduto(){
	$.modalWindow({
		title: "Filtrar produtos...",
		content: $("#modal-filtroproduto"),
		width: "900px",
		closeButton: true
	});
}

function filtroproduto_aplicar(){
	$.loading(true);
	$.ajax({
		url: "../ajax/selecaoproduto_filtroproduto_aplicar.php",
		type: "POST",
		data: {
			tabela: tabela,
			arr_codestabelec: arr_codestabelec.join(","),
			arr_codfornec: arr_codfornec.join(","),
			arr_codproduto: arr_codproduto.join(","),
			codproduto: $("#filtroproduto-codproduto").val(),
			descricaofiscal: $("#filtroproduto-descricaofiscal").val(),
			codfornec: $("#filtroproduto-codfornec").val(),
			coddepto: $("#filtroproduto-coddepto").val(),
			codgrupo: $("#filtroproduto-codgrupo").val(),
			codsubgrupo: $("#filtroproduto-codsubgrupo").val(),
			codfamilia: $("#filtroproduto-codfamilia").val(),
			relacaoproduto: ($("#filtroproduto-relacaoproduto").checked() ? "S" : "N"),
			sugestaocompra: ($("#filtroproduto-sugestaocompra").checked() ? "S" : "N"),
			prodcotacao: ($("#filtroproduto-prodcotacao").checked() ? "S" : "N"),
			tipodescricao: $("#filtroproduto-tipodescricao").val(),
			codsimilar: $("#filtroproduto-codsimilar").val(),
			codmarca: $("#filtroproduto-codmarca").val(),
			foralinha: $("#filtroproduto-foralinha").val(),
			curvaabc: $("#filtroproduto-curvaabc").val(),
			dtultcpa1: $("#filtroproduto-dtultcpa1").val(),
			dtultcpa2: $("#filtroproduto-dtultcpa2").val(),
		},
		success: function(result){
			$.loading(false);
			extractScript(result);
		}
	});
}

function formatar_cor_grade(grid){
	grid = $(grid);
	$(grid).find("tr[cor]").each(function(){
		$(this).removeAttr("onmouseover");
		$(this).removeAttr("onmouseout");
		$(this).removeAttr("onclick");
	}).bind("mouseover", function(){
		var cor = $(this).attr("cor");
		$(this).find("td").each(function(i){
			$(this).css("background-color", shadeColor(cor, 0));
		});
	}).bind("mouseout", function(){
		var cor = $(this).attr("cor");
		$(this).find("td").each(function(i){
			if(i % 2 === 0){
				$(this).css("background-color", shadeColor(cor, 40));
			}else{
				$(this).css("background-color", shadeColor(cor, 50));
			}
		});
	}).trigger("mouseout");
}

var __ajax_gradeestabelecimento_carregar = null;
function gradeestabelecimento_carregar(codproduto, focus){
	focus = (focus === undefined ? false : focus);

	if(__ajax_gradeestabelecimento_carregar !== null){
		__ajax_gradeestabelecimento_carregar.abort();
	}

	var distribuicao = $("#grid-produto [coluna='quantidade'][codproduto='" + codproduto + "']").attr("distribuicao");
	distribuicao = JSON.parse(distribuicao);

	$("#grid-estabelecimento").addClass("grid-loading");

	__ajax_gradeestabelecimento_carregar = $.ajax({
		url: "../ajax/selecaoproduto_gradeestabelecimento_carregar.php",
		data: {
			tabela: tabela,
			arr_codestabelec: arr_codestabelec,
			arr_codestabelec_visivel: selecaoestabelecimento_selecionados(),
			codproduto: codproduto,
			distribuicao: distribuicao
		},
		success: function(result){
			__ajax_gradeestabelecimento_carregar = null;
			$("#grid-estabelecimento").removeClass("grid-loading");
			$("#grid-estabelecimento").html(result);
			fixa_topogrid("#grid-estabelecimento");
			formatar_cor_grade("#grid-estabelecimento");
			$.gear();

			$("#grid-estabelecimento input[codestabelec]").bind("focus", function(){
				$("#grid-estabelecimento tr.grid-selected").removeClass("grid-selected");
				$(this).parents("tr:first").addClass("grid-selected");
			}).bind("keydown", function(e){
				var inputs = $("#grid-estabelecimento input[codestabelec]").not(":disabled");
				if($(inputs).first().get(0) === this){
					if(e.keyCode === 9 && e.shiftKey === true){ // TAB
						var tr = $("#tr-produto-" + $("#produto-codproduto").val()).prev();
						if($(tr).length > 0){
							$(this).trigger("change");
							$(this).attr("ignoreChange", true);
							var codproduto = $(tr).attr("id").split("-")[2];
							produto_carregar(codproduto, true);
							gradeproduto_posicionar();
							return false;
						}
					}
				}else if($(inputs).last().get(0) === this){
					if(e.keyCode === 9 && e.shiftKey === false){ // TAB
						var tr = $("#tr-produto-" + $("#produto-codproduto").val()).next();
						if($(tr).length > 0){
							$(this).trigger("change");
							$(this).attr("ignoreChange", true);
							var codproduto = $(tr).attr("id").split("-")[2];
							produto_carregar(codproduto, true);
							gradeproduto_posicionar();
							return false;
						}
					}
				}
			}).bind("change", function(){
				if($(this).is("[ignoreChange]")){
					$(this).removeAttr("ignoreChange");
					return true;
				}

				var t_quantidade = 0;
				var distribuicao = {};
				$("#grid-estabelecimento input[codestabelec]").not(":disabled").each(function(){
					var quantidade = parseFloat($(this).val().replace(",", "."));
					t_quantidade += quantidade;
					distribuicao[$(this).attr("codestabelec")] = quantidade;
				});
				var element = $("#grid-produto [coluna='quantidade'][codproduto='" + $("#produto-codproduto").val() + "']");
				$(element).val(number_format(t_quantidade, 4, ",", ""));
				$(element).attr("distribuicao", JSON.stringify(distribuicao));
				var checkbox = $("#grid-produto :checkbox[codproduto='" + $("#produto-codproduto").val() + "']");
				$(checkbox).checked(t_quantidade > 0);
				calcular_totais();
			});

			if(focus){
				$("#grid-estabelecimento input[codestabelec]").not(":disabled").first().select();
			}
		}
	});
}

var __ajax_gradeestabelecimentoestoque_carregar = null;
function gradeestabelecimentoestoque_carregar(codproduto){
	if(__ajax_gradeestabelecimentoestoque_carregar !== null){
		__ajax_gradeestabelecimentoestoque_carregar.abort();
	}

	$("#grid-estabelecimentoestoque").addClass("grid-loading");

	__ajax_gradeestabelecimentoestoque_carregar = $.ajax({
		url: "../ajax/selecaoproduto_gradeestabelecimentoestoque_carregar.php",
		data: {
			tabela: tabela,
			arr_codestabelec: selecaoestabelecimento_selecionados(),
			codproduto: codproduto
		},
		success: function(result){
			__ajax_gradeestabelecimentoestoque_carregar = null;
			$("#grid-estabelecimentoestoque").removeClass("grid-loading");
			$("#grid-estabelecimentoestoque").html(result);
			fixa_topogrid("#grid-estabelecimentoestoque");
			formatar_cor_grade("#grid-estabelecimentoestoque");
		}
	});
}

var __ajax_gradeestabelecimentovenda_carregar = null;
function gradeestabelecimentovenda_carregar(codproduto){
	if(__ajax_gradeestabelecimentovenda_carregar !== null){
		__ajax_gradeestabelecimentovenda_carregar.abort();
	}

	$("#grid-estabelecimentovenda").addClass("grid-loading");

	__ajax_gradeestabelecimentovenda_carregar = $.ajax({
		url: "../ajax/selecaoproduto_gradeestabelecimentovenda_carregar.php",
		data: {
			tabela: tabela,
			arr_codestabelec: selecaoestabelecimento_selecionados(),
			codproduto: codproduto
		},
		success: function(result){
			__ajax_gradeestabelecimentovenda_carregar = null;
			$("#grid-estabelecimentovenda").removeClass("grid-loading");
			$("#grid-estabelecimentovenda").html(result);
			fixa_topogrid("#grid-estabelecimentovenda");
			formatar_cor_grade("#grid-estabelecimentovenda");
		}
	});
}

var __ajax_gradeproduto_carregar = null;
function gradeproduto_carregar(limpar){
	limpar = (limpar === undefined ? false : limpar);

	if(__ajax_gradeproduto_carregar !== null){
		return true;
	}

	if(limpar){
		$("#table-selecaoproduto .gridborder > *").remove();
		$("#grid-produto").addClass("grid-loading");
		$("#produto-codproduto").val("");
		$.loading(true);
	}

	__ajax_gradeproduto_carregar = $.ajax({
		url: "../ajax/selecaoproduto_gradeproduto_carregar.php",
		type: "POST",
		data: {
			tabela: tabela,
			arr_codestabelec: arr_codestabelec,
			codfornec: $("#filtroproduto-codfornec").val(),
			query: $("#query-produto").val(),
			offset: $("#grid-produto > table > tbody > tr").length,
			checkall: $("[checkall]").checked() ? "S" : "N"
		},
		success: function(result){
			__ajax_gradeproduto_carregar = null;
			if(limpar){
				$.loading(false);
				$("#grid-produto").removeClass("grid-loading");
				$("#grid-produto").html(result);
				$("#grid-produto > table > tbody").bind("scroll", function(){
					if(($(this).prop("scrollHeight") - $(this).height() - $(this).scrollTop()) < $(this).height() * 2){
						gradeproduto_carregar(false);
					}
				});
				if($("#grid-produto tr.row").length === 0){
					$.messageBox({
						type: "alert",
						text: "Nenhum produto foi encontrado no filtro especificado.<br>Por favor, verifique o filtro informado e tente novamente.",
						afterClose: function(){
							filtroproduto();
						}
					});
					return false;
				}
			}else{
				$("#grid-produto > table > tbody").append(result);
			}

			$("#grid-produto > table").find("input, select").filter("[codproduto]:not([gear])").bind("focus", function(){
				produto_carregar($(this).attr("codproduto"));
			}).bind("keypress", function(e){
				if(e.keyCode === 13){
					var codproduto = $(this).attr("codproduto");
					var checkbox = $("#grid-produto :checkbox[codproduto='" + codproduto + "']");

					$(checkbox).checked(verificanegativo_gridproduto($(this)));
					calcular_totais();
				}
			});

			$("#grid-produto input[coluna='qtdeunidade']").not("[gear]").bind("focus", function(){
				$(this).attr("value-old", $(this).val());
			}).bind("change", function(){
				var tr = $(this).parents("tr.row");
				var preco_old = parseFloat($(tr).find("[coluna='preco']").val().replace(",", "."));
				var qtdeunidade_old = parseFloat($(this).attr("value-old").replace(",", "."));
				var qtdeunidade_new = parseFloat($(this).val().replace(",", "."));
				var preco_new = (qtdeunidade_new * preco_old) / qtdeunidade_old;
				preco_new = number_format(preco_new, 2, ",", ".");
				$(tr).find("[coluna='preco']").val(preco_new);
				var checkbox = $("#grid-produto :checkbox[codproduto='" + codproduto + "']");
				$(checkbox).checked(verificanegativo_gridproduto($(this)));
				calcular_totais();
			});

			$("#grid-produto input[coluna='quantidade']").not("[gear]").bind("change", function(){
				if(arr_codestabelec.length === 1){
					var codproduto = $(this).attr("codproduto");
					var distribuicao = {};
					distribuicao[arr_codestabelec[0]] = parseFloat($(this).val().replace(",", "."));
					$(this).attr("distribuicao", JSON.stringify(distribuicao));
					gradeestabelecimento_carregar(codproduto);

					var checkbox = $("#grid-produto :checkbox[codproduto='" + codproduto + "']");
					$(checkbox).checked(verificanegativo_gridproduto($(this)));
					calcular_totais();
				}
			});

			$("#grid-produto input[coluna='preco']").not("[gear]").bind("change", function(){
				var checkbox = $("#grid-produto :checkbox[codproduto='" + codproduto + "']");

				$(checkbox).checked(verificanegativo_gridproduto($(this)));
				calcular_totais();
			});

			$("#grid-produto input[coluna='valdescto']").not("[gear]").bind("change", function(){
				var checkbox = $("#grid-produto :checkbox[codproduto='" + codproduto + "']");

				$(checkbox).checked(verificanegativo_gridproduto($(this)));
				calcular_totais();
			});

			$("#grid-produto :checkbox").not("[gear]").bind("change", function(){
				calcular_totais();
			});

			if($("#grid-produto > table").length > 0){
				fixa_topogrid("#grid-produto");
			}

			$.gear();

			if($("#produto-codproduto").val().length === 0){
				var tr = $("#grid-produto > table > tbody > tr:first");
				if($(tr).length > 0){
					var codproduto = $(tr).find("[codproduto]").attr("codproduto");
					produto_carregar(codproduto);
				}
			}

			$("#grid-produto :checkbox[codproduto]").change(function(){
				if(!verificanegativo_gridproduto($(this))){
					$(this).checked(false);
				}
			});
		}
	});
}

function gradeproduto_posicionar(){
	var tr = $("#tr-produto-" + $("#produto-codproduto").val());
	var tbody = $(tr).parent();
	var currScroll = $(tbody).scrollTop();
	$(tbody).scrollTop(0);
	var top = $(tr).position().top;
	var scrollTop = top - $(tbody).height() / 2;
	if(scrollTop < 0){
		return true;
	}
	$(tbody).scrollTop(currScroll).stop().animate({
		"scrollTop": scrollTop
	}, 2000);
}

var __ajax_gradeultimascompras_carregar = null;
function gradeultimascompras_carregar(codproduto){
	if(__ajax_gradeultimascompras_carregar !== null){
		__ajax_gradeultimascompras_carregar.abort();
	}

	$("#grid-ultimascompras").addClass("grid-loading");

	__ajax_gradeultimascompras_carregar = $.ajax({
		url: "../ajax/selecaoproduto_gradeultimascompras_carregar.php",
		data: {
			tabela: tabela,
			arr_codestabelec: arr_codestabelec,
			codproduto: codproduto
		},
		success: function(result){
			__ajax_gradeultimascompras_carregar = null;
			$("#grid-ultimascompras").removeClass("grid-loading");
			$("#grid-ultimascompras").html(result);
			fixa_topogrid("#grid-ultimascompras");
			formatar_cor_grade("#grid-ultimascompras");
		}
	});
}

function opcoes(){
	$.modalWindow({
		title: "Menu de opções",
		content: $("#modal-opcoes"),
		width: "220px",
		closeButton: true
	});
}

function produto_carregar(codproduto, focusEstabelecimento){
	focusEstabelecimento = (focusEstabelecimento === undefined ? false : focusEstabelecimento);

	if(codproduto === undefined){
		codproduto = $("#produto-codproduto").val();
	}else if($("#produto-codproduto").val() == codproduto){
		return true;
	}

	var descricaofiscal = $("#grid-produto [coluna='descricaofiscal'][codproduto='" + codproduto + "']").text();

	$("#produto-codproduto").val(codproduto);
	$("#produto-descricaofiscal").html(codproduto + " - " + descricaofiscal);
	gradeestabelecimento_carregar(codproduto, focusEstabelecimento);
	gradeultimascompras_carregar(codproduto);
	gradeestabelecimentoestoque_carregar(codproduto);
	gradeestabelecimentovenda_carregar(codproduto);

	$("#grid-produto tr.grid-selected").removeClass("grid-selected");
	$("#tr-produto-" + codproduto).addClass("grid-selected");
}

function selecaoestabelecimento(){
	$.modalWindow({
		title: "Selecione os estabelecimentos...",
		content: $("#modal-selecaoestabelecimento"),
		width: "400px",
		closeButton: true
	});
}

function selecaoestabelecimento_carregar(){
	$.ajax({
		url: "../ajax/selecaoproduto_selecaoestabelecimento_carregar.php",
		success: function(result){
			$("#grid-selecaoestabelecimento").html(result);
			var arr_codestabelec = JSON.parse($.cookie("selecaoproduto-selecaoestabelecimento"));
			if(arr_codestabelec === null || arr_codestabelec.length === 0){
				$("#grid-selecaoestabelecimento :checkbox").checked(true);
			}else{
				for(var i in arr_codestabelec){
					$("#grid-selecaoestabelecimento :checkbox[codestabelec='" + arr_codestabelec[i] + "']").checked(true);
				}
			}
		}
	});
}

function selecaoestabelecimento_confirmar(){
	$.cookie("selecaoproduto-selecaoestabelecimento", JSON.stringify(selecaoestabelecimento_selecionados()));
	$.modalWindow("close");
	if($("#grid-estabelecimentovenda tr").length > 0){
		arr_codestabelec = selecaoestabelecimento_selecionados();
		filtroproduto_aplicar();
	}
}

function selecaoestabelecimento_selecionados(){
	return $("#grid-selecaoestabelecimento [codestabelec]:checked").map(function(){
		return $(this).attr("codestabelec");
	}).get();
}

function verificar_janela_pai(){
	if(window.opener === undefined || window.opener === null){
		window.close();
	}else{
		setTimeout("verificar_janela_pai()", 5000);
	}
}

function verificanegativo_gridproduto(elem){
	var quantidade = parseFloat($(elem).closest("tr").find("[coluna=quantidade]").val().replace(",", "."));
	var qtdeunidade = parseFloat($(elem).closest("tr").find("[coluna=qtdeunidade]").val().replace(",", "."));

	if(tabela != "cotacao"){
		var valdescto = parseFloat($(elem).closest("tr").find("[coluna=valdescto]").val().replace(",", "."));
		var preco = parseFloat($(elem).closest("tr").find("[coluna=preco]").val().replace(",", "."));
	}

	if(tabela != "cotacao"){
		if(isNaN(quantidade) || isNaN(valdescto) || isNaN(preco) || isNaN(qtdeunidade)){
			return false;
		}

		return (quantidade * qtdeunidade * (preco - valdescto)) > 0;
	}else{
		if(isNaN(quantidade) || isNaN(qtdeunidade)){
			return false;
		}

		return (quantidade * qtdeunidade) > 0;
	}
}

function verificar_checked_gradeproduto(){
	$("#grid-produto tbody :checkbox").each(function(){
		if($(this).closest("tr").find("[coluna=quantidade]").val() != "0,0000"){
			$(this).checked($("[checkall]").checked());
		}
	});
	return true;
}

function dadosproduto(){
	openProgram("ExtratoProd", "codproduto=" + $("#produto-codproduto").val());
}
