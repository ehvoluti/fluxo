var vlrdocumento = 0;
var vlrdesconto = 0;
var vlriss = 0;
var vlrbcpis = 0;
var vlrpis = 0;
var vlrbccofins = 0;
var vlrcofins = 0;
var vlrpisretido = 0;
var vlrcofinsretido = 0;
var idnotafiscalservico;
var clonando = false;
$(document).bind("ready", function(){
	$("#valorliquido, #valordesconto, #basecalculopis, #valorpis, #basecalculocofins, #valorcofins, #valorpisretido, #valorcofinsretido, #valoriss").blur(function(){
		if($(this).val() === "" || $(this).val().length === 0){
			$(this).val("0,00");
		}
	})

	$("#it_cstpiscofins").change(function(){
		calcular_piscofins();
	})

	$("#it_valortotal, #it_valordesconto, #it_aliquotapis, #it_aliquotacofins").change(function(){
		var basecalculopis =  parseFloat($("#it_valortotal").val().replace(",",".")) - parseFloat($("#it_valordesconto").val().replace(",","."));
		$("#it_basecalculopis").val(basecalculopis.toFixed(2).replace(".",","));
		var basecalculocofins = parseFloat($("#it_valortotal").val().replace(",",".")) - parseFloat($("#it_valordesconto").val().replace(",","."));
		$("#it_basecalculocofins").val(basecalculocofins.toFixed(2).replace(".",","));
		var valorpis = parseFloat($("#it_basecalculopis").val().replace(",",".")) * (parseFloat($("#it_aliquotapis").val().replace(",",".")) / 100);
		$("#it_valorpis").val(valorpis.toFixed(2).replace(".",","));
		var valorcofins = parseFloat($("#it_basecalculocofins").val().replace(",",".")) * (parseFloat($("#it_aliquotacofins").val().replace(",",".")) / 100)
		$("#it_valorcofins").val(valorcofins.toFixed(2).replace(".",","));
		calcular_piscofins();
	})
	$("#btnCadIncluirItem").disabled(true);
	$("#indicadoroperacao").change(function(){
		if($(this).val() == "0"){
			//alert("entrada");
			$("#it_cstpiscofins").attr("filter","CAST(codcst AS integer) >= 50");
		}else{
			//alert("saida");
			$("#it_cstpiscofins").attr("filter","CAST(codcst AS integer) < 50");
		}
		$("#it_cstpiscofins").refreshComboBox();
	})
});

$.cadastro.after.inserir = function(){
	$("#valorliquido").disabled(true).val("0,00");
	$("#valordesconto").disabled(true).val("0,00");
	$("#basecalculopis").disabled(true).val("0,00");
	$("#valorpis").disabled(true).val("0,00");
	$("#basecalculocofins").disabled(true).val("0,00");
	$("#valorcofins").disabled(true).val("0,00");
	$("#valorpisretido").disabled(false).val("0,00");
	$("#valorcofinsretido").disabled(false).val("0,00");
	$("#valoriss").disabled(false).val("0,00");
	//$("#indicadoroperacao").val("0");
	//$("#indicadoremitente").val("1");
	$("#codigosituacao").val("00");
	$("#btnCadIncluirItem").disabled(false);
	$("#listar_itnotafiscalservico img").css("display", "block");
	if(clonando === false){
		limpar();
	}else{
		clonando = false;
	}
	//$("#tipoparceiro").disabled(true).val("F");
}

$.cadastro.after.alterar = function(){
	$("#valorliquido").disabled(true);
	$("#valordesconto").disabled(true);
	$("#basecalculopis").disabled(true);
	$("#valorpis").disabled(true);
	$("#basecalculocofins").disabled(true);
	$("#valorcofins").disabled(true);
	$("#valorpisretido").disabled(false);
	$("#valorcofinsretido").disabled(false);
	$("#valoriss").disabled(false);
	$("#listar_itnotafiscalservico img").css("display", "block");
	$("#btnCadIncluirItem").disabled(false);
	//$("#tipoparceiro").disabled(true);
}

$.cadastro.before.salvar = function(){
	if(parseFloat($("#valorliquido").val().replace(",",".")) == 0 || $("#valorliquido").val().lenght == 0){
		$.messageBox({
			type: "info",
			title: "",
			text: "O campo <b>Valor do documento</b> n&atilde;o pode ter seu valor zerado."
		});
		return false;
	}
	return true;
}

$.cadastro.after.carregar = function(){
	if($("#usuario").val().length > 0 && $("#datalog").val().length > 0){
		$("#spn_usuario").html(usuario_nome($("#usuario").val()));
		$("#spn_datalog").html($("#datalog").val());
		$("#lbl_ultalteracao").show();
	}
	itnotafiscalservico_listar();
	$("#listar_itnotafiscalservico img").css("display", "none");
	$("#tipoparceiro").trigger("change");
	$("#indicadoroperacao").trigger("change");
};

$.cadastro.after.limpar = function(){
	$("#btnCadIncluirItem").disabled(true);
	$("#listar_itnotafiscalservico").html("");
	$("#lbl_ultalteracao").hide();
	//$("#tipoparceiro").val("");
}

$.cadastro.before.clonar = function(){
	idnotafiscalservico = $("#idnotadiversa").val();
	vlrdocumento = $("#valorliquido").val();
	vlrdesconto = $("#valordesconto").val();
	vlriss = $("#valoriss").val();
	vlrbcpis = $("#basecalculopis").val();
	vlrpis = $("#valorpis").val();
	vlrbccofins = $("#basecalculocofins").val();
	vlrcofins = $("#valorcofins").val();
	vlrpisretido = $("#valorpisretido").val();
	vlrcofinsretido = $("#valorcofinsretido").val();
	clonando = true;
	return true
}

$.cadastro.after.clonar = function(){
	$("#idnotadiversa").val(idnotafiscalservico);
	$("#valorliquido").val(vlrdocumento);
	$("#valordesconto").val(vlrdesconto);
	$("#valoriss").val(vlriss);
	$("#basecalculopis").val(vlrbcpis);
	$("#valorpis").val(vlrpis);
	$("#basecalculocofins").val(vlrbccofins);
	$("#valorcofins").val(vlrcofins);
	$("#valorpisretido").val(vlrpisretido);
	$("#valorcofinsretido").val(vlrcofinsretido);
	$("#listar_itnotafiscalservico").html("");
	$("#numnotafis").val("");
	$("#serie").val("");
	$("#chavenfse").val("");
	itnotafiscalservico_listar();
}

function itnotafiscalservico_listar(){
	$.ajax({
		async: false,
		url: "../ajax/notafiscalservico_itnotafiscalservico_listar.php",
		data: ({
			idnotafiscalservico: $("#idnotafiscalservico").val()
		}),
		success: function(html){
			$("#listar_itnotafiscalservico").html(html);
		}
	});
}
function itnotafiscalservico_incluir(){
	var campo_null = false;
	var elements = $("#box_notafiscalservico").find("input[required],select[required]");
	for(var i = 0; i < $(elements).length; i++){
		if(trim($(elements[i]).val()).length == 0){
			$.messageBox({
				type: "error",
				title: "",
				text: "O campo <b>" + elements[i].getAttribute("required") + "</b> é de preenchimento obrigatório",
				focusOnClose: $(elements[i])
			});
			campo_null = true;
			return false;
		}
	}
	if(campo_null){
		return false;
	}
	$.modalWindow({
		closeButton: true,
		content: $("#it_notafiscalservico"),
		title: "Item da nota fiscal de serviço",
		width: "900px",
		height:"260px"
	});
	$("#box_itnotafiscalservico input:not(.codigo):not(.seqitem)").val("0,00");
	$("#box_itnotafiscalservico .limparincluir").val("");
	$("#it_codproduto").disabled(false);
	$("#it_valorpis #valorcofins").disabled(true);
	$("#it_valorpis").disabled(true);
	$("#it_valorcofins").disabled(true)
	$("#it_basecalculopis").disabled(true);
	$("#it_basecalculocofins").disabled(true);
	$("#it_aliquotapis").disabled(true);
	$("#it_aliquotacofins").disabled(true);

	if($("#it_seqitem").val().length === 0){
		$("#it_seqitem").val("1");
	}else{
		$("#it_seqitem").val( parseInt($("#it_seqitem").val()) + 1 );
	}
}

function itnotafiscalservico_editar(idnotafiscalservico, codproduto){
	$.ajax({
		async: false,
		url: "../ajax/notafiscalservico_itnotafiscalservico_editar.php",
		data: ({
			idnotafiscalservico: idnotafiscalservico,
			codproduto: codproduto
		}),
		success: function(html){
			extractScript(html);
			$.modalWindow({
				closeButton: true,
				content: $("#it_notafiscalservico"),
				title: "Item da nota fiscal de serviço",
				width: "900px",
				height:"260px"
			});
		}
	});
	$("#it_codproduto").disabled(true);
	$("#it_valorpis").disabled(true);
	$("#it_valorcofins").disabled(true)
	$("#it_basecalculopis").disabled(true);
	$("#it_basecalculocofins").disabled(true);
	$("#it_aliquotapis").disabled(true);
	$("#it_aliquotacofins").disabled(true);
	$("#it_descricao").focus();
}

function itnotafiscalservico_gravar(){
	var campo_null = false;
	var elements = $("#box_itnotafiscalservico").find("input[required],select[required], textarea[required]");
	for(var i = 0; i < $(elements).length; i++){
		if(trim($(elements[i]).val()).length == 0){
			$.messageBox({
				type: "error",
				title: "",
				text: "O campo <b>" + (elements[i]).getAttribute("required") + "</b> é de preenchimento obrigatório",
				focusOnClose: $(elements[i])
			});
			campo_null = true;
			return false;
		}
	}

	$("#box_itnotafiscalservico").find("input[required],select,textarea").each(function(){
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
	if(parseFloat($("#it_basecalculopis").val().replace(",",".")) > 0){
		if(parseFloat($("#it_aliquotapis").val().replace(",",".")) === 0){
			$.messageBox({
				type: "error",
				title: "",
				text: "Selecione um CST com a aliquota de PIS informada."
			})
			$("#it_cstpiscofins").focus();
			return false;
		}
	}

	if(parseFloat($("#it_basecalculocofins").val().replace(",",".")) > 0){
		if(parseFloat($("#it_aliquotacofins").val().replace(",",".")) === 0){
			$.messageBox({
				type: "error",
				title: "",
				text: "Selecione um CST com a aliquota de COFINS informada."
			})
			$("#it_cstpiscofins").focus();
			return false;
		}
	}

	if(campo_null){
		return false;
	}
	var json_itnotafiscalservico = {};

	$("#box_itnotafiscalservico").find("input, textarea, select").each(function(){
		json_itnotafiscalservico[$(this).attr("id").replace("it_","")] = $(this).val();
	})

	$.ajax({
		async: false,
		url: "../ajax/notafiscalservico_itnotafiscalservico_gravar.php",
		data:({
			itnotafiscalservico: json_itnotafiscalservico,
			codproduto: $("#it_codproduto").val(),
			seqitem: $("#it_seqitem").val()
		}),
		success: function(html){
			extractScript(html);
		}
	})
	itnotafiscalservico_listar();
}

function itnotafiscalservico_deletar(codproduto){
	$.ajax({
		async: false,
		url: "../ajax/notafiscalservico_itnotafiscalservico_deletar.php",
		data: ({
			codproduto: codproduto
		}),
		success: function(html){
			extractScript(html);
		}
	});
	itnotafiscalservico_listar();
}


function limpar(){
	$.ajax({
		async: false,
		url: "../ajax/notafiscalservico_itnotafiscalservico_limpar.php"
	});
}

function calcular_piscofins(){
	$.ajax({
		async: false,
		url: "../ajax/notafiscalservico_itnotafiscalservico_aliquotapiscofins_pegar.php",
		data: ({
			cstpiscofins: $("#it_cstpiscofins").val(),
			basepiscofins: $("#it_basecalculopis").val().replace(",",".")
		}),
		success: function(html){
			extractScript(html)
		}
	});
}
