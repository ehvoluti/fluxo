$(document).bind("ready", function(){
	$("#btnCadExcluir").before("<input type=\"button\" style=\"margin-right:6px\" id=\"btnCadLancamento\" value=\"Lan&ccedil;amentos\" onclick=\"$.lancamento({idnotadiversa:$('#idnotadiversa').val()})\" alt=\"Visualizar lan&ccedil;amentos financeiros do pedido\" title=\"Visualizar lan&ccedil;amentos financeiros do pedido\">");
	$("#codfornec").change(function(){
		planocontas();
	});
	$("#btnCadIncluirItem").disabled(true);

	$("#tipodocumentofiscal").change(function(){
		if($(this).val() == "02"){
			$("#numnotafisfinal").disabled(false);
		}else{
			$("#numnotafisfinal").disabled(true).val("");
		}
	});

	$("#baseicms,#totalicmssubst,#totalipi,#totalbaseoutras,#totalbaseisenta").change(function(){
		var baseicms = parseFloat($("#baseicms").val().replace(",","."));
		var totalicmssubst = parseFloat($("#totalicmssubst").val().replace(",","."));
		var totalipi = parseFloat($("#totalipi").val().replace(",","."));
		var totalbaseoutras = parseFloat($("#totalbaseoutras").val().replace(",","."));
		var totalbaseisenta = parseFloat($("#totalbaseisenta").val().replace(",","."));
		var it_totalliquido = baseicms + totalicmssubst + totalipi + totalbaseoutras + totalbaseisenta;
		var it_totalliquido = it_totalliquido.toFixed(2).replace(".",",");

		$("#it_totalliquido").val(it_totalliquido);
	});

	$("#tipoparceiro").change(function(){
		if($.inArray($("#tipoparceiro").val(), ["F","R","T","U"]) >= 0){
			$("#pagrec").val("P");
		}else{
			$("#pagrec").val("R");
		}
	});
});

$.cadastro.after.inserir = function(){
	$("#baseicms,#totalicms,#aliqicms,#baseicmssubst,#totalicmssubst,#aliqicmssubst,#basepis,#totalpis,#aliqpis,#basecofins,#totalcofins,#aliqcofins,#baseipi,#totalipi,#aliqipi").val("0,00");
	$("#btnCadIncluirItem").disabled(false);
	$("#tipodocumentofiscal").trigger("change");
	$("#totalliquido,#it_totalliquido").disabled(true);
	limpar();
};

$.cadastro.after.alterar = function(){
	$("#listar_itnotadiversa img").css("display", "block");
	$("#btnCadIncluirItem").disabled(false);
	$("#tipodocumentofiscal").trigger("change");
	$("#totalliquido,#it_totalliquido").disabled(true);
};

$.cadastro.before.salvar = function(){
	if(parseFloat($("#totalliquido").val().replace(",",".")) == 0 || $("#totalliquido").val().length == 0){
		$.messageBox({
			type: "info",
			title: "",
			text: "O campo <b>Total Liquido</b> n&atilde;o pode ter seu valor zerado."
		});
		$("#totalliquido").val("0,00");
		return false;
	}else if(parseFloat($("#totalbruto").val().replace(",",".")) == 0 || $("#totalbruto").val().length == 0){
		$.messageBox({
			type: "info",
			title: "",
			text: "O campo <b>Total Bruto</b> n&atilde;o pode ter seu valor zerado."
		});
		$("#totalbruto").val("0,00");
		return false;
	}
	if($("#codconta").val().length == 0){
		planocontas();
	}

	return true;
};

$.cadastro.after.carregar = function(){
	if($("#usuario").val().length > 0 && $("#datalog").val().length > 0){
		usuario_nome($("#usuario").val(), function(nome){
			$("#spn_usuario").html(nome);
		});
		$("#spn_datalog").html($("#datalog").val());
		$("#lbl_ultalteracao").show();
	}
	itnotadiversa_listar();
	$("#listar_itnotadiversa img").css("display", "none");
	$("#tipoparceiro").trigger("change");
};

$.cadastro.after.limpar = function(){
	$("#lbl_ultalteracao").hide();
	$("#btnCadIncluirItem").disabled(true);
	$("#listar_itnotadiversa").html("");
};

function planocontas(){
	if($("#desc_codfornec").val() != "(Fornecedor não encontrado)"){
		$.ajax({
			async: false,
			url: "../ajax/notadiversa_planocontas_buscar.php",
			data: ({
				codfornec: $("#codfornec").val()
			}),
			success: function(html){
				extractScript(html);
			}
		});
	}
}

function itnotadiversa_listar(){
	$.ajax({
		async: false,
		url: "../ajax/notadiversa_itnotadiversa_listar.php",
		data: ({
			idnotadiversa: $("#idnotadiversa").val()
		}),
		success: function(html){
			$("#listar_itnotadiversa").html(html);
		}
	});
}

function itnotadiversa_incluir(){
	$.modalWindow({
		closeButton: true,
		content: $("#itnotadiversa"),
		title: "Item da nota diversa",
		width: "900px"
	});
	$("#box-itnotadiversa input").val("0,00");
	$("#it_natoperacao").disabled(false);
	$("#it_natoperacao").val($("#natoperacao").val());
	$("#codcstpiscofins").val("99");
	$("#codcstipi").val("49");
}

function itnotadiversa_editar(idnotadiversa, natoperacao){
	$.ajax({
		async: false,
		url: "../ajax/notadiversa_itnotadiversa_editar.php",
		data: ({
			idnotadiversa: idnotadiversa,
			natoperacao: natoperacao
		}),
		success: function(html){
			extractScript(html);
			$.modalWindow({
				closeButton: true,
				content: $("#itnotadiversa"),
				title: "Item da nota diversa",
				width: "900px"
			});
			$("#it_natoperacao").disabled(true);
		}
	});
}

function itnotadiversa_gravar(){
	var campo_null = false;
	$("#box-itnotadiversa").find("input,select").each(function(){
		if($(this).val().length == 0){
			$.messageBox({
				type: "error",
				title: "",
				text: "Preencha todos os campos."
			});
			campo_null = true;
			return false;
		}
	});
	if(campo_null){
		return false;
	}


	var json_itnotadiversa = {};

	$("#box-itnotadiversa").find("input,select").each(function(){
		json_itnotadiversa[$(this).attr("id").replace("it_","")] = $(this).val();
	});


	$.ajax({
		async: false,
		url: "../ajax/notadiversa_itnotadiversa_gravar.php",
		data: ({
			natoperacao: $("#it_natoperacao").val(),
			itnotadiversa: json_itnotadiversa
		}),
		success: function(html){
			extractScript(html);
			$("#it_natoperacao").disabled(true);
		}
	});
	itnotadiversa_listar();
}

function itnotadiversa_deletar(natoperacao){
	$.ajax({
		async: false,
		url: "../ajax/notadiversa_itnotadiversa_deletar.php",
		data: ({
			natoperacao: natoperacao
		}),
		success: function(html){
			extractScript(html);
			$("#it_natoperacao").disabled(true);
		}
	});
	itnotadiversa_listar();
}

function limpar(){
	$.ajax({
		async: false,
		url: "../ajax/notadiversa_itnotadiversa_limpar.php"
	});
}