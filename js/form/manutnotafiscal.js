$(document).bind("ready", function(){
	$("#grade_notafiscal").bind("scroll", function(){
		if($("#arr_idnotafiscal").val().length > 0){
			var outerHeight = parseInt($(this).height());
			var scrollPos = parseInt($(this).scrollTop());
			var innerHeight = parseInt($(this).children().filter("table").height());
			if(innerHeight - scrollPos - outerHeight < outerHeight){
				listar();
			}
		}
	});
	$("#table_opcoes legend > input:checkbox").bind("change", function(){
		$(this).parent().parent().find("input:checkbox:not(:disabled)").not(this).checked($(this).checked()).trigger("change");
	});
	var chk_obrig_recalcular = $("#c_tptribicms,#c_aliqicms,#c_redicms,#c_aliqiva,#c_valorpauta,#c_aliqii,#c_aliqpis,#c_aliqcofins,#c_ipi");
	$(chk_obrig_recalcular).bind("change", function(){
		if($(chk_obrig_recalcular).filter(":checked").length > 0){
			$("#c_recalcular").checked(true).disabled(true);
		}else{
			$("#c_recalcular").checked(false).disabled(false);
		}
	});

	$("#box_carregaritem").find("input:text,select").change(function(){
		$.ajax({
			url: "../ajax/manutnotafiscal_manual_gravaritem.php",
			data: ({
				iditnotafiscal: $("#iditnotafiscal").val(),
				coluna: $(this).attr("id").substr(3),
				valor: $(this).val()
			}),
			success: function(html){
				extractScript(html);
			}
		});
	});
	$("#it_tptribicms").change(function(){
		if($.inArray($("#it_tptribicms").val(), ["I", "N"]) >= 0){
			$("#it_aliqicms").val("0,0000");
			$("#it_aliqiva").val("0,0000");
			$("#it_redicms").val("0,0000");
		}else if($.inArray($("#it_tptribicms").val(), ["T"]) >= 0){
			$("#it_aliqicms").val(aliqicms);
			$("#it_aliqiva").val("0,0000");
			$("#it_redicms").val("0,0000");
		}else if($.inArray($("#it_tptribicms").val(), ["R"]) >= 0){
			$("#it_aliqicms").val(aliqicms);
			$("#it_aliqiva").val("0,0000");
			$("#it_redicms").val(reduc);
		}else{
			$("#it_aliqicms").val(aliqicms);
			$("#it_aliqiva").val(aliqiva);
			$("#it_redicms").val(reduc);
		}
	});

	//Geral
	if(habmanutnotafiscal.substr(0, 1) == "0")
		$("#c_recalcular").disabled(true);
	if(habmanutnotafiscal.substr(1, 1) == "0")
		$("#c_seqitem").disabled(true);
	if(habmanutnotafiscal.substr(2, 1) == "0")
		$("#c_natoperacao").disabled(true);
	if(habmanutnotafiscal.substr(3, 1) == "0")
		$("#c_chavenfe").disabled(true);
	if(habmanutnotafiscal.substr(0, 4) == "0000")
		$("#chk_geral").disabled(true);

	//PIS COFINS
	if(habmanutnotafiscal.substr(4, 1) == "0")
		$("#chk_piscofins, #c_aliqpis,#c_aliqcofins").disabled(true);

	//ICMS
	if(habmanutnotafiscal.substr(5, 1) == "0")
		$("#chk_icms,#c_aliqicms,#c_tptribicms,#c_redicms,#c_valorpauta,#c_csticms,#c_aliqii,#c_aliqiva").disabled(true);

	//IPI
	if(habmanutnotafiscal.substr(6, 1) == "0")
		$("#chk_ipi,#c_ipi").disabled(true);
});

function filtrar(){
	$("#grade_notafiscal").html("");
	$("#arr_idnotafiscal").val("");
	var data = $("#table_filtro").find("input:text,select").map(function(){
		return $(this).attr("id").substr(4) + "=" + $(this).val();
	}).get().join("&");
	$.loading(true);
	$.ajax({
		url: "../ajax/manutnotafiscal_filtrar.php",
		data: data,
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function listar(){
	var arr_idnotafiscal = [];
	var arr_idnotafiscal_full = $("#arr_idnotafiscal").val().split(";");
	for(var i = 0; i < 99; i++){
		arr_idnotafiscal[i] = arr_idnotafiscal_full.shift();
	}
	$("#arr_idnotafiscal").val(arr_idnotafiscal_full.join(";"));
	$.ajax({
		url: "../ajax/manutnotafiscal_listar.php",
		data: ({
			novo: ($("#grade_notafiscal > table.grid").length == 0 ? "S" : "N"),
			arr_idnotafiscal: arr_idnotafiscal,
			checked: ($("#chk_notafiscal_todos").length > 0 && $("#chk_notafiscal_todos").checked() ? "S" : "N")
		}),
		success: function(html){
			if($("#grade_notafiscal > table.grid").length == 0){
				$("#grade_notafiscal").html(html);
			}else{
				$("#grade_notafiscal > table.grid").append(html);
			}
			if(!$("#div_hidden").is(":visible")){
				$("#div_hidden").animate({height: "toggle"}, "slow");
			}
		}
	});
}

function limpar_filtro(){
	$("#table_filtro").find("input:text,select").first().focus();
}

function listar_opcoes(){
	$("#div_alterar").find("input:text,select").val("");
	$.modalWindow({
		closeButton: true,
		content: $("#div_opcoes"),
		title: "Informe os dados que devem ser atualizados",
		width: "700px"
	});
}

function listar_itens(idnotafiscal){
	$("#grd_listaritens").html("");
	$.ajax({
		url: "../ajax/manutnotafiscal_manual_listaitens.php",
		data: ({
			idnotafiscal: idnotafiscal
		}),
		success: function(html){
			$("#grd_listaritens").html(html);
		}
	});

	$.ajax({
		url: "../ajax/manutnotafiscal_manual_buscar.php",
		data: ({
			idnotafiscal: idnotafiscal
		}),
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});

	$.modalWindow({
		title: "Manuten&ccedil;&atilde;o de Notas Fiscais",
		content: $("#div_listaritens"),
		width: "840px",
		height: "auto",
		closeButton: false
	});

	$.loading(true);
}

function carregar_item(iditnotafiscal){
	$.ajax({
		async: false,
		url: "../ajax/manutnotafiscal_manual_carregaritem.php",
		data: ({
			iditnotafiscal: iditnotafiscal
		}),
		success: function(html){
			extractScript(html);
		}
	});
	$.modalWindow({
		title: "Manuten&ccedil;&atilde;o do Item da Nota",
		content: $("#div_carregaritem"),
		width: "840px",
		height: "auto",
		closeButton: false
	});
}

function manual_gravar(){
	$.loading(true);
	$.ajax({
		url: "../ajax/manutnotafiscal_manual_gravar.php",
		data: ({
			idnotafiscal: $("#idnotafiscal").val(),
			natoperacao: $("#natoperacao").val(),
			dtemissao: $("#dtemissao").val(),
			dtentrega: $("#dtentrega").val(),
			chavenfe: $("#chavenfe").val(),
			observacaofiscal: $("#observacaofiscal").val()

		}),
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function atualizar_notafiscal(){
	$.messageBox({
		type: "info",
		text: "Tem certeza que deseja atualizar as notas fiscais selecionadas?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				var data = $("#table_opcoes input:checkbox[id^='c_']").map(function(){
					return $(this).attr("id") + "=" + ($(this).checked() ? "S" : "N");
				}).get().join("&");
				var arr_idnotafiscal = [];
				$("#grade_notafiscal input:checkbox[idnotafiscal]:checked").each(function(){
					arr_idnotafiscal[arr_idnotafiscal.length] = $(this).attr("idnotafiscal");
				});
				if($("#arr_idnotafiscal").val().length > 0 && $("#chk_notafiscal_todos").checked()){
					arr_idnotafiscal = arr_idnotafiscal.concat($("#arr_idnotafiscal").val().split(";"));
				}
				for(var i = 0; i < arr_idnotafiscal.length; i++){
					data += "&arr_idnotafiscal[]=" + arr_idnotafiscal[i];
				}
				$.ajaxProgress({
					url: "../ajax/manutnotafiscal_atualizar.php",
					data: data,
					type: "POST",
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

function item_fechar(){
	let bool_trib;
	let csticms;
	let csosn;
	let aliqicms;
	let redicms;
	let aliqiva;
	
	aliqicms = parseFloat($("#it_aliqicms").val().replace(",","."));
	redicms = parseFloat($("#it_redicms").val().replace(",","."));
	aliqiva = parseFloat($("#it_aliqiva").val().replace(",","."));

	if($("#it_csticms").val().length <= 0 || $("#it_tptribicms").val().length <= 0){
		$.messageBox({
			type: "error",
			text: "Preencha todos os campos",
		});
		return false
	}

	bool_trib = false;

	csticms = $("#it_csticms").val().substr(-2);
	csosn = $("#it_csticms").val().substr(-3);



	switch($("#it_tptribicms").val()){		
		case "T":
			if($.inArray(csticms,["00"]) >= 0 && redicms == 0 && aliqicms > 0 && aliqiva == 0){
				bool_trib = true;
			}
			break;
		case "I":
			if($.inArray(csticms,["40"]) >= 0 && redicms == 0 && aliqicms == 0 && aliqiva == 0){
				bool_trib = true;
			}
			break;
		case "F":			
			if($.inArray(csticms,["70"]) >= 0 && redicms > 0 && aliqicms > 0 && aliqiva > 0){				
				bool_trib = true;
			}else if($.inArray(csticms,["10"]) >= 0 && redicms == 0){
				bool_trib = true;
			}else if($.inArray(csticms,["60"]) >= 0 && redicms == 0 && aliqicms == 0 && aliqiva == 0){
				bool_trib = true;
			}
			break;
		case "R":
			if($.inArray(csticms,["20"]) >= 0 && aliqicms > 0 && redicms > 0 && aliqiva == 0){
				bool_trib = true;
			}
			break;
		case "N":
			if($.inArray(csticms,["41","50","90"]) >= 0 && redicms == 0 && aliqiva == 0 && aliqicms == 0){
				bool_trib = true;
			}
			break;
	}

	if($.inArray(csosn,["101","102","103","201","202","203","300","400","500","900"]) == 0){
		bool_trib = true;
	}

	if(!bool_trib){
		$.messageBox({
			type: "error",
			text: "A combinação de tributação, cst, IVA e redução precisa estar correta.",
		});
		return false;
	}

	$.modalWindow('close');
	return true;
}