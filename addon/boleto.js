$.boletobancario = function(settings){

	settings = $.extend({
		codlancto	: null,
		nossonumero : null,
		tipoparceiro: null,
		codparceiro : null
	}, settings);

	var win = $.modalWindow({
		title:"Boleto Bancário ",
		width:"550px",
		height:"150px",
		"id" : "win_email"
	})[0];

	var div_janela = document.createElement("div");
	$(div_janela).css({
		"width" : "100%",
		"height" : "100%"
	})

	win.appendChild(div_janela);


	$(div_janela)
			.attr({
			"id":"opcoes_boleto",
	});

	var table = document.createElement("table");
	div_janela.appendChild(table);

	var trradiobutton = document.createElement("tr");
	table.appendChild(trradiobutton);

	var tdradiobutton = document.createElement("td");
	$(tdradiobutton)
			.css(
				"valign", "top"
			);
	trradiobutton.appendChild(tdradiobutton);

	var fieldset = document.createElement("fieldset");
	$(fieldset)
			.attr({
				"id": "fsopcoesboleto"
			})
			.css(
				"width","100%"
			);
	tdradiobutton.appendChild(fieldset);

	var legend = document.createElement("legend");
	$(legend).html("Selecione a opção");
	fieldset.appendChild(legend);

	var table_fieldset = document.createElement("table");
	fieldset.appendChild(table_fieldset);

	var tr_table_fieldset = document.createElement("tr");
	table_fieldset.appendChild(tr_table_fieldset);

	var td_radioI = document.createElement("td");
	tr_table_fieldset.appendChild(td_radioI);

	var radiobuttonI = document.createElement("input");
	radiobuttonI.type = "radio";
	$(radiobuttonI).attr({
		"name" : "opcoesboleto",
		"id" : "rdimpressao",
		"tipo": "I",
		"checked": "true"
	})
	td_radioI.appendChild(radiobuttonI)

	var label_radioI = document.createElement("label");
	$(label_radioI).attr({
		"for": "rdimpressao"
	}).html("Gerar Para Impressão");
	td_radioI.appendChild(label_radioI);


	var td_radioE = document.createElement("td");
	tr_table_fieldset.appendChild(td_radioE);

	var radiobuttonE = document.createElement("input");
	radiobuttonE.type = "radio";
	$(radiobuttonE).attr({
		"name" : "opcoesboleto",
		"id" : "rdemail",
		"tipo": "E"
	})
	td_radioE.appendChild(radiobuttonE)

	var label_radioE = document.createElement("label");
	$(label_radioE).attr({
		"for": "rdemail"
	}).html("Gerar e Enviar por Email");
	td_radioE.appendChild(label_radioE);


	var td_radioC = document.createElement("td");
	tr_table_fieldset.appendChild(td_radioC);

	var radiobuttonC = document.createElement("input");
	radiobuttonC.type = "radio";
	$(radiobuttonC).attr({
		"name"		: "opcoesboleto",
		"id"		: "rdcancelar",
		"tipo"		: "C"
	})

	td_radioC.appendChild(radiobuttonC);

	var label_radioC = document.createElement("label");
	$(label_radioC).attr({
		"for": "rdcancelar"
	}).html("Cancelar Boleto");
	td_radioC.appendChild(label_radioC);

	if(settings.nossonumero == null || settings.nossonumero.length == 0){
		$(radiobuttonC).hide();
		$(label_radioC).hide();
	}

	var tr_email = document.createElement("tr");
	$(tr_email).attr("name", "tr_email");
	$(tr_email).css("display", "none");
	table_fieldset.appendChild(tr_email);

	var td_email = document.createElement("td");
	$(td_email)
			.attr(
				"colspan", "3"
			)
			.css(
				"padding-top", "10px"
			);
	tr_email.appendChild(td_email);

	var email_destinatario = document.createElement("input");
	email_destinatario.type = "text"
	$(email_destinatario).attr({
		"id"	      : "emailenviar",
		"class"		  : "field_full",
		"placeholder" : "Informe aqui o email para envio do boleto. Use ; para informar mais de um email"
	});
	$(email_destinatario).disabled(true);
	td_email.appendChild(email_destinatario);

	var tr_fieldset_email = document.createElement("tr");
	$(tr_fieldset_email).attr("name", "tr_fieldset_email");
	$(tr_fieldset_email).css("display", "none")
	table_fieldset.appendChild(tr_fieldset_email);

	var td_fieldset_email = document.createElement("td");
	$(td_fieldset_email).attr("colspan", "3");
	tr_fieldset_email.appendChild(td_fieldset_email);

	var fieldset_email = document.createElement("fieldset");
	td_fieldset_email.appendChild(fieldset_email);

	var legend_email = document.createElement("legend");
	$(legend_email).html("Modelos de Email");
	fieldset_email.appendChild(legend_email);


//	var label_modelo_email = document.createElement("label");
//	$(label_modelo_email).attr({
//		"for" : "email_codmodeloemail"
//	});
//	$(label_modelo_email).val("Modelos de Email");
//	legend_email.appendChild(label_modelo_email);

	var table_fieldset_email = document.createElement("table");
	$(fieldset_email).css("width", "94%");
	fieldset_email.appendChild(table_fieldset_email);

	var tr_select_modelo = document.createElement("tr");
	$(tr_select_modelo).css("display", "none");
	table_fieldset_email.appendChild(tr_select_modelo);

	var td_select_modelo = document.createElement("td");
	td_select_modelo.colspan = "colspan=3";
	tr_select_modelo.appendChild(td_select_modelo);

	var select_modelo = document.createElement("select");
	td_select_modelo.appendChild(select_modelo);
	$(select_modelo).on("change", _atualizarmodeloemail);
	select_modelo.setAttribute("id", "email_codmodeloemail");
	select_modelo.setAttribute("table", "modeloemail");
	select_modelo.setAttribute("class", "field_full");
	//$(td_select_modelo).css("padding-right", "5px");
	$(select_modelo).refreshComboBox();

	var tr_assunto_email = document.createElement("tr");
	table_fieldset_email.appendChild(tr_assunto_email);

	var td_assunto_email = document.createElement("td");
	$(td_assunto_email).attr("colspan" , "3");
	tr_assunto_email.appendChild(td_assunto_email);

	var assunto_email = document.createElement("input");
	assunto_email.type = "text";
	$(assunto_email).attr("id", "email_titulo");
	$(assunto_email).css({
		"width" : "100%",
		"margin-top" : "5px"
	});
	td_assunto_email.appendChild(assunto_email);

	var tr_corpo_email = document.createElement("tr");
	table_fieldset_email.appendChild(tr_corpo_email);

	var td_corpo_email = document.createElement("td");
	tr_corpo_email.appendChild(td_corpo_email);

	var textarea_corpo_email = document.createElement("textarea");
	$(textarea_corpo_email).attr("id", "email_corpo");
	$(textarea_corpo_email).css({
		"height" : "120px",
		"width" : "100%",
		"margin-top" : "5px"
	});
	td_corpo_email.appendChild(textarea_corpo_email);

	var trbutton = document.createElement("tr");
	table.appendChild(trbutton);

	var tdbutton = document.createElement("td");
	trbutton.appendChild(tdbutton);
	$(tdbutton).css({
		"text-align" : "center",
		"padding-top" : "5px"
	});

	var button_confirmar = document.createElement("input");
	button_confirmar.type = "button";
	tdbutton.appendChild(button_confirmar)

	$(button_confirmar).css({
		"margin-right" : "10px"
	}).bind("click", function(){

		var tipo = $("#fsopcoesboleto input:radio:checked").attr("tipo");
		if((tipo == "E" && $(email_destinatario).val().length === 0) ||(tipo == "E" && !validateemail($(email_destinatario).val()))){
			var msgbox = ($(email_destinatario).val().length === 0 ? "Favor informar o email para envio do boleto bancário" : "Favor informar um email válido");
			$.messageBox({
				type: "error",
				title: "Boleto Bancário",
				text: msgbox
			});
			$(email_destinatario).focus();
			return false;
		}

		if(tipo == "E"){
			if($(select_modelo).val().length == 0){
				$.messageBox({
					type: "error",
					title: "Boleto Bancário",
					text: "Selecione um modelo de email"
				});
				$(select_modelo).focus();
				return false;
			}

			if($(assunto_email).val().length == 0){
				$.messageBox({
					type: "error",
					title: "Boleto Bancário",
					text: "Informe um assunto para o email"
				});
				$(assunto_email).focus();
				return false;
			}
		}

		if(tipo == "C"){
			$.messageBox({
				type: "info",
				text: "Confirma o cancelamento do boleto bancário para este lançamento?",
				buttons: ({
					"Sim": function(){
						$.messageBox("close");
						_gerarboleto(tipo,
									settings.codlancto,
									$(email_destinatario).val(),
									$(select_modelo).val(),
									$(assunto_email).val(),
									$(textarea_corpo_email).val());
					},
					"N\u00E3o": function(){
						$.messageBox("close");
						return false;
					}
				})
			});
		}else{
			_gerarboleto(tipo,
						settings.codlancto,
						$(email_destinatario).val(),
						$(select_modelo).val(),
						$(assunto_email).val(),
						$(textarea_corpo_email).val());
		}
	}).val("Confirmar");


	var button_cancelar = document.createElement("input");
	button_cancelar.type = "button";
	tdbutton.appendChild(button_cancelar)
	$(button_cancelar).bind("click", function(){
		$.modalWindow("close");
	}).val("Cancelar");

	$("[name='opcoesboleto']").bind("change", function(){
		var disabled = !$(radiobuttonE).checked();
		$(email_destinatario).disabled(disabled);
		if(!disabled){
			$(email_destinatario).focus();
			$(this).parents("[__modalwindow]").animate({"height":"400px"},"slow");
			$(this).parents("[__modalwindow_body]").animate({"height":"400px"},"slow");
			$(tr_email).css("display", "table-row");
			$(tr_fieldset_email).css("display", "table-row");
			$( tr_select_modelo).css("display", "table-row");
			$.ajax({
				url: "../ajax/boleto_email_parceiro.php",
				type: "POST",
				dataType: "json",
				beforeSend: function(){
					$.loading(true);
				},
				data : ({
					tipoparceiro: settings.tipoparceiro,
					codparceiro: settings.codparceiro
				}),
				complete: function(){
					$.loading(false);
				},
				success: function(oResult){
					$(email_destinatario).val(oResult.destinatario);
				}
			});
		}else{
			$(email_destinatario).val("");
			$(this).parents("[__modalwindow]").animate({"height":"150px"},"slow");
			$(this).parents("[__modalwindow_body]").animate({"height":"150px"},"slow");
			$(tr_email).css("display", "none");
			$(tr_fieldset_email).css("display", "none");
			$( tr_select_modelo).css("display", "none");
		}
	});

	$.gear();
};

function _gerarboleto(tipo, codlancto, destinatario, codmodeloemail, titulo, corpo){
	$.ajax({
		url: "../ajax/boleto.php",
		type: "POST",
		dataType: "json",
		beforeSend: function(){
			$.loading(true);
		},
		data: ({
			codlancto: codlancto,
			tipo: tipo,
			codmodeloemail: codmodeloemail,
			destinatario: destinatario,
			titulo: titulo,
			corpo: corpo
		}),
		complete: function(){
			$.modalWindow("close");
			$.loading(false);
		},
		success: function(oResult){
			if(in_array(tipo, ["I", "C"])){
				if(tipo == "I"){
					if(oResult.status){
						window.open(oResult.arquivo);
					}
					$.cadastro.pesquisar();
				}
			}

			$.messageBox({
				type: (oResult.status ? "success" :"error"),
				title: "Boleto Bancário",
				text: oResult.msgbox
			});
		}
	});
}

function _atualizarmodeloemail(){
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

function validateemail(email) {
    var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}
