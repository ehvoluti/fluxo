var __lancamento_totalparcelas = 0; // Total da soma de todas as parcelas
var __lancamento_afterClose = null;

$.lancamento = function(settings){
	settings = $.extend({
		codestabelec: null,
		codlanctogru: null,
		numpedido: null,
		idnotafiscal: null,
		idnotafrete: null,
		prevreal: null,
		afterClose: function(){}
	}, settings);

	__lancamento_afterClose = settings.afterClose;
	delete settings.afterClose;

	var win = $.modalWindow({
		height: "340px",
		title: "Lan&ccedil;amentos Financeiros",
		width: "840px"
	})[0];

	var grid = document.createElement("div");
	win.appendChild(grid);
	$(grid).attr({
		"id": "__lancamento_grid",
		"class": "gridborder"
	}).css({
		"height": "250px",
		"margin-bottom": "10px"
	}).html("<label>Carregando...</label>");

	var buttons = document.createElement("div");
	win.appendChild(buttons);
	$(buttons).css({
		"padding-bottom": "10px",
		"padding-left": "50px",
		"text-align": "center"
	});

	var button_boleto = document.createElement("input");
	button_boleto.type = "button";
	buttons.appendChild(button_boleto);
	$(button_boleto).attr({
		"id": "__lancamento_btn_boleto"
	}).css({
		"float":"left",
		"padding-left": "0px",
		"margin-left" :"-50px"
	}).hide();

	$(button_boleto).bind("click", function(){
		var codlancto = $("#__lancamento_grid tr[codlancto]:has(.grid_printer)").map(function(){
			return $(this).attr("codlancto");
		}).get().join(",");

		var codbanco =  $("#__lancamento_grid tr[codlancto]:has(.grid_printer)").map(function(){
			return $(this).attr("codbanco");
		}).get().join(",");

		var tipoparceiro =  $("#__lancamento_grid tr[codlancto]:has(.grid_printer)").map(function(){
			return $(this).attr("tipoparceiro");
		}).get().join(",");

		var codparceiro =  $("#__lancamento_grid tr[codlancto]:has(.grid_printer)").map(function(){
			return $(this).attr("codparceiro");
		}).get().join(",");

		codbanco = codbanco.substr(0,3);
		codparceiro = codparceiro.substr(0, 10);
		tipoparceiro = tipoparceiro.substr(0, 1);
		if(in_array(codbanco, ["033", "001", "237", "341"])){
			$.boletobancario({
				"codlancto"  : codlancto,
				"nossonumero": null,
				"tipoparceiro" : tipoparceiro,
				"codparceiro"  : codparceiro
			})
		}else{
			window.open("../form/boleto.php?codlancto=" + codlancto);
		}
	}).val("Imprimir Boleto");

	var button_save = document.createElement("input");
	button_save.type = "button";
	buttons.appendChild(button_save);
	$(button_save).bind("click", function(){
		var grid = $("#__lancamento_grid");
		if($(grid).find("input[valorparcela][value='']").length > 0){
			$.messageBox({
				type: "error",
				text: "Informe o valor de todas as parcelas."
			});
			return false;
		}else if($(grid).find("input[dtvencto][value='']").length > 0){
			$.messageBox({
				type: "error",
				text: "Informe a data de vencimento para todas as parcelas."
			});
			return false;
		}else if($(grid).find("select[codespecie] :selected[value='']").length > 0){
			$.messageBox({
				type: "error",
				text: "Informe a forma de pagamento para todas as parcelas."
			});
			return false;
		}
		var data = $(grid).find("input[valorparcela]").map(function(){
			return "lancamento[" + $(this).attr("codlancto") + "][valorparcela]=" + $(this).val();
		}).get().join("&");
		data += "&" + $(grid).find("input[dtvencto]").map(function(){
			return "lancamento[" + $(this).attr("codlancto") + "][dtvencto]=" + $(this).val();
		}).get().join("&");
		data += "&" + $(grid).find("select[codespecie]").map(function(){
			return "lancamento[" + $(this).attr("codlancto") + "][codespecie]=" + $(this).val();
		}).get().join("&");
		data += "&" + $(grid).find("select[codbanco]").map(function(){
			return "lancamento[" + $(this).attr("codlancto") + "][codbanco]=" + $(this).val();
		}).get().join("&");
		data += "&" + $(grid).find("input[docliquidacao]").map(function(){
			return "lancamento[" + $(this).attr("codlancto") + "][docliquidacao]=" + $(this).val();
		}).get().join("&");
		if($("#__lancamento_docliquidacao").filter("[alterado]").length > 0){
			data += "&docliquidacao=" + $("#__lancamento_docliquidacao").val();
		}
		if($("#__lancamento_tid").filter("[alterado]").length > 0){
			data += "&tid=" + $("#__lancamento_tid").val();
		}
		if($("#__lancamento_referencia").filter("[alterado]").length > 0){
			data += "&referencia=" + $("#__lancamento_referencia").val();
		}
		if(settings.codestabelec !== null){
			data += "&codestabelec=" + settings.codestabelec;
		}
		if(settings.numpedido !== null){
			data += "&numpedido=" + settings.numpedido;
		}
		if(settings.idnotafiscal !== null){
			data += "&idnotafiscal=" + settings.idnotafiscal;
		}
		if(settings.codlanctogru !== null){
			data += "&codlanctogru=" + settings.codlanctogru;
		}
		$.loading(true);
		$.ajax({
			url: "../ajax/lancamento_gravar.php",
			data: data,
			success: function(html){
				$.loading(false);
				extractScript(html);
			}
		});
	}).val("Gravar");

	var button_close = document.createElement("input");
	button_close.type = "button";
	buttons.appendChild(button_close);
	$(button_close).css("margin-left", "7px");
	$(button_close).bind("click", function(){
		$.modalWindow("close");
	}).val("Cancelar");

	//$(buttons).find("input:button").css("margin-left", "7px");

	var button_mais = document.createElement("input");
	button_mais.type = "button";
	buttons.appendChild(button_mais);
	$(button_mais).val("Mais").css("float", "right").bind("click", function(){
		$.modalWindow({
			title: "Mais op&ccedil;&otilde;es para altera&ccedil;&atilde;o",
			content: $("#__lancamento_div_mais"),
			width: "400px"
		});
	});

	var div_mais = document.createElement("div");
	win.appendChild(div_mais);
	$(div_mais).attr({
		"id": "__lancamento_div_mais"
	}).css({
		"display": "none"
	});

	var info = document.createElement("label");
	div_mais.appendChild(info);
	$(info).css({
		"color": "#666"
	}).html("Os dados informados aqui ser&atilde;o aplicados em todos os lan&ccedil;amentos da grade.");

	var table = document.createElement("table");
	div_mais.appendChild(table);
	$(table).css("margin-top", "10px");
	// Referencia
	var tr = document.createElement("tr");
	table.appendChild(tr);
	var td = document.createElement("td");
	tr.appendChild(td);
	$(td).attr("class", "label2");
	var label = document.createElement("label");
	td.appendChild(label);
	$(label).attr("for", "__lancamento_docliquidacao").html("Documento:");
	td = document.createElement("td");
	tr.appendChild(td);
	$(td).attr("class", "field");
	var input = document.createElement("input");
	input.type = "text";
	td.appendChild(input);
	$(input).attr({
		"class": "field_full",
		"id": "__lancamento_docliquidacao"
	}).bind("change", function(){
		$(this).attr("alterado", true);
	});
	// TiD
	var tr = document.createElement("tr");
	table.appendChild(tr);
	var td = document.createElement("td");
	tr.appendChild(td);
	$(td).attr("class", "label2");
	var label = document.createElement("label");
	td.appendChild(label);
	$(label).attr("for", "__lancamento_tid").html("TiD:");
	td = document.createElement("td");
	tr.appendChild(td);
	$(td).attr("class", "field");
	var input = document.createElement("input");
	input.type = "text";
	td.appendChild(input);
	$(input).attr({
		"class": "field_full",
		"id": "__lancamento_tid"
	}).bind("change", function(){
		$(this).attr("alterado", true);
	});
	// Referencia
	var tr = document.createElement("tr");
	table.appendChild(tr);
	var td = document.createElement("td");
	tr.appendChild(td);
	$(td).attr("class", "label2");
	var label = document.createElement("label");
	td.appendChild(label);
	$(label).attr("for", "__lancamento_referencia").html("Referencia:");
	td = document.createElement("td");
	tr.appendChild(td);
	$(td).attr("class", "field");
	var input = document.createElement("input");
	input.type = "text";
	td.appendChild(input);
	$(input).attr({
		"class": "field_full",
		"id": "__lancamento_referencia"
	}).bind("change", function(){
		$(this).attr("alterado", true);
	});
	// Botoes
	var div_button = document.createElement("div");
	div_mais.appendChild(div_button);
	$(div_button).css("text-align", "center");
	var button_ok = document.createElement("input");
	button_ok.type = "button";
	div_button.appendChild(button_ok);
	$(button_ok).val("Ok").bind("click", function(){
		$.modalWindow("close");
		__lancamento_afterClose();
	}).css("margin-top", "10px");

	$.gear();

	$.ajax({
		url: "../ajax/lancamento_grade.php",
		data: settings,
		success: function(html){
			$("#__lancamento_grid").html(html);
			$.gear();
			$("#__lancamento_grid input:text[valorparcela]").bind("change", function(){
				$(this).attr("alterado", "S");
				var e_valorparcela_s = $("#__lancamento_grid input:text[valorparcela][alterado='S']");
				var e_valorparcela_n = $("#__lancamento_grid input:text[valorparcela][alterado='N']");

				var totalalterado = 0;
				$(e_valorparcela_s).each(function(){
					totalalterado += parseFloat($(this).val().replace(",", "."));
				});

				var valorparcela = (__lancamento_totalparcelas - totalalterado) / $(e_valorparcela_n).length;
				$(e_valorparcela_n).val(number_format(valorparcela, 2, ",", ""));
				var somaparcelas = 0;
				$(e_valorparcela_s).add(e_valorparcela_n).each(function(){
					somaparcelas += parseFloat($(this).val().replace(",", "."));
				});
				var diferenca = __lancamento_totalparcelas - somaparcelas;
				$(e_valorparcela_n).last().val(number_format((parseFloat($(e_valorparcela_n).last().val().replace(",", ".")) + diferenca), 2, ",", ""));
				$(e_valorparcela_s).add(e_valorparcela_n).each(function(){
					if(parseFloat($(this).val().replace(",", ".")) < 0){
						$(this).val("0,00");
					}
				});
			});
			if(request("cadastro") === "notafiscal" && parseFloat($("#financpercentual").val().replace(",", ".")) > 0){
				$("#__lancamento_grid select[codespecie]").disabled(true);
			}

			if($("#__lancamento_grid tr[codlancto]:has(.grid_printer)").length == 0){
				$("#__lancamento_btn_boleto").hide();
			}else{
				$("#__lancamento_btn_boleto").show();
			}
		}
	});
};
