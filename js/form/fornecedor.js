var rgie;
$(document).bind("ready", function(){
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnCadVerDados\" style=\"margin-right:4px; width:140px\" value=\"Dados do Fornecedor\" onClick=\"openProgram('ExtratoFornec','codfornec=' + $('#codfornec').val())\" alt=\"Visualizar dados do fornecedor\" title=\"Visualizar dados do fornecedor\">");
	$("#tppessoa").bind("change", function(){
		settipopessoa("cpfcnpj", $(this).val(), $("#cpfcnpj"));
		settipopessoa("rgie", $(this).val(), $("#rgie"));
		if($("#tppessoa").val().length > 0){
			$("#cpfcnpj,#rgie").disabled(false);
			$("#cpfcnpj,#rgie").removeAttr("disabled");
		}else{
			$("#cpfcnpj,#rgie").disabled(true);
		}
	});

	$("#rgie").keydown(function(){
		if($("#tppessoa").val() === "F"){
			rgie = $(this).val();
			if(rgie.length === 12){
				settipopessoa("rgie", "J", $("#rgie"));
				$("#rgie").val(rgie);
			}
			if(rgie.length === 0 || rgie.length === 11){
				settipopessoa("rgie", "F", $("#rgie"));
				$("#rgie").val(rgie);
			}
		}
	});

	$("#cep").bind("change", function(){
		$(this).cep({
			endereco: $("#endereco"),
			bairro: $("#bairro"),
			uf: $("#uf"),
			cidade: $("#codcidade"),
			pais: $("#codpais")
		});
	});

	$("#cpfcnpj").buscarcnpj({
		razaosocial: $("#razaosocial"),
		nomefantasia: $("#nome"),
		cep: $("#cep"),
		numero: $("#numero"),
		complemento: $("#complemento")
	});

	if($("#grdcomplcadastro tr").length === 0){
		$("#abacomplcadastro").remove();
		redimensionar_abafinal();
	}else{
		$("#grdcomplcadastro").find("input,select").bind("change", function(){
			fornecedorcompl_gravar($(this).attr("id").split("_")[1], $(this).val());
		});
	}
	$("#grdfornecestab input").bind("change", function(){
		fornecestab_alterar(this);
	});

	$("#codfornec_vinculado").change(function(){
		$("#fornecprincipal").disabled($(this).val().length > 0);		
	});
	$("#fornecprincipal").change(function(){
		$("#codfornec_vinculado").disabled($(this).val() == "S");		
	});
});

function fornecedorcompl_carregar(){
	$.ajax({
		url: "../ajax/fornecedor_fornecedorcompl_carregar.php",
		success: function(html){
			extractScript(html);
		}
	});
}

function fornecedorcompl_gravar(codcomplcadastro, valor){
	$.ajax({
		url: "../ajax/fornecedor_fornecedorcompl_gravar.php",
		data: ({
			codcomplcadastro: codcomplcadastro,
			valor: valor
		}),
		success: function(html){
			extractScript(html);
		}
	});
}

function redimensionar_abafinal(){
	var t_width = 0;
	$("#tabsforn").find(".tabon,.taboff").filter(":visible").each(function(){
		t_width += parseFloat($(this).width());
	});
	$("#tabsforn .tabend").width($("#tabsforn").width() - t_width);
}

function fornecestab_desenhar(){
	$.ajax({
		data: ({
			codfornec: $("#codfornec").val()
		}),
		async: false,
		url: "../ajax/fornecedor_fornecestab_desenhar.php",
		success: function(html){
			extractScript(html);
		}
	});
}

function fornecestab_alterar(elem){
	var valor;

	if($(elem).attr("coluna") === "disponivel"){
		valor = $(elem).checked() ? "S" : "N";
	}else{
		valor = $(elem).val();
	}

	$.ajax({
		async: false,
		url: "../ajax/fornecedor_fornecestab_alterar.php",
		data: ({
			codestabelec: $(elem).attr("codestabelec"),
			coluna: $(elem).attr("coluna"),
			valor: (valor)
		}),
		success: function(html){
			extractScript(html);
		}
	});
}

$.cadastro.after.alterar = function(){
	$("#datainclusao").disabled(true);
	$("#cpfcnpj,#rgie").disabled(false);
	$("#cpfcnpj,#rgie").removeAttr("disabled");
};

$.cadastro.after.inserir = function(){
	limpar();
};

$.cadastro.after.clonar = function(){
	if($("#tppessoa").val().length > 0){
		$("#cpfcnpj,#rgie").disabled(false);
		$("#cpfcnpj,#rgie").removeAttr("disabled");
	}else{
		$("#cpfcnpj,#rgie").disabled(true);
	}

	$("#datainclusao").val(moment().format("DD/MM/YYYY"));
};

function limpar(){
	$.ajax({
		async: false,
		url: "../ajax/fornecedor_fornecestab_novo.php",
		success: function(){
			$("#grdfornecestab").find("input[coluna='disponivel']").checked(true).trigger("change");
		}
	});
}