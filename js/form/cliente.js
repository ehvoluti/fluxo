$(document).bind("ready", function(){
	$("input:file").bind("change", function(){
		arqcliente_upload();
		$(this).upload({
			dirname: "../temp/upload/cliente/" + $("#codcliente").val() + "/",
			onComplete: function(){
				$.messageBox({
					type: "success",
					text: "Arquivo enviado com sucesso!",
				});
				arqcliente_listar();
				return true;
			}
		});
	});

	$("#grdclienteestab input").bind("change", function(){
		clienteestab_alterar(this);
	});
	interesse_buscar();
	changetppessoa();
	verifica_contribuinte();
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnCadVerDados\" style=\"margin-right:4px\" value=\"Dados do Cliente\" onClick=\"openProgram('WRExtratoCli','codcliente=' + $('#codcliente').val())\" title=\"Visualizar dados do cliente\">");
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnCadImprimir\" style=\"margin-right:4px\" value=\"Imprimir Etiqueta\" onClick=\"imprimir_etiqueta()\" title=\"Imprimir Etiqueta\">");

	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnCadImprimirCliente\" style=\"margin-right:4px\" value=\"Imprimir Cliente\" onClick=\"imprimir_cliente()\" title=\"Imprimir Cliente\">");

	$("#nome").bind("change", function(){
		if($("#tppessoa").val() === "F"){
			$("#razaosocial").val($(this).val());
		}
	});

	$("#cpfcnpj").buscarcnpj({
		razaosocial: $("#razaosocial"),
		nomefantasia: $("#nome"),
		cep: $("#cepres,#cepfat,#cepent"),
		numero: $("#numerores,#numerofat,#numeroent"),
		complemento: $("#complementores,#complementofat,#complementoent")
	});

	$("#cepres").bind("change", function(){
		$(this).cep({
			endereco: $("#enderres"),
			bairro: $("#bairrores"),
			uf: $("#ufres"),
			cidade: $("#codcidaderes"),
			pais: $("#codpaisres")
		});
	});
	$("#cepfat").bind("change", function(){
		$(this).cep({
			endereco: $("#enderfat"),
			bairro: $("#bairrofat"),
			uf: $("#uffat"),
			cidade: $("#codcidadefat"),
			pais: $("#codpaisfat")
		});
	});
	$("#cepent").bind("change", function(){
		$(this).cep({
			endereco: $("#enderent"),
			bairro: $("#bairroent"),
			uf: $("#ufent"),
			cidade: $("#codcidadeent"),
			pais: $("#codpaisent")
		});
	});

	$("#tppessoa").bind("change", function(){
		if(verificacao_contribuinte){
			verifica_contribuinte();
		}
	});

	$("#rgie").change(function(){
		if(verificacao_contribuinte){
			verifica_contribuinte();
		}
	});

	var campos = new Array("cep", "ender", "numero", "complemento", "bairro", "uf", "codcidade", "fone");
	for(var i = 0; i < campos.length; i++){
		$("#" + campos[i] + "res").bind("change", function(){
			if($.cadastro.status() != 4){
				var tipos = new Array("fat", "ent");
				for(var j = 0; j < tipos.length; j++){
					var campo = $(this).attr("id").substr(0, $(this).attr("id").length - 3);
					if($("#" + campo + tipos[j]).val().length == 0 || $("#" + campo + tipos[j]).val() == $(this).val()){
						$("#" + campo + tipos[j]).val($(this).val()).trigger("change");
					}
				}
			}
		});
	}

	if($("#grdcomplcadastro tr").length == 0){
		$("#abacomplcadastro").remove();
		redimensionar_abafinal();
	}else{
		$("#grdcomplcadastro").find("input,select").bind("change", function(){
			clientecompl_gravar($(this).attr("id").split("_")[1], $(this).val());
		});
	}

	$("#cpfcnpj").bind("change", function(){
		if($.cadastro.status() == "2"){
			$.ajax({
				async: false,
				url: "../ajax/cliente_cpfcnpjcadastrado.php",
				data: ({
					cpfcnpj: $("#cpfcnpj").val()
				}),
				success: function(html){
					extractScript(html);
				}
			});
		}
	});

	if(campobloqcliente.rgie == "S"){
		document.getElementById("rgie").setAttribute("required", "RG/IE");
		$("#email").removeAttr("gear");
	}
	if(campobloqcliente.cpfcnpj == "S"){
		document.getElementById("cpfcnpj").setAttribute("required", "CPF CNPJ");
	}
	if(campobloqcliente.cep == "S"){
		document.getElementById("cepres").setAttribute("required", "CEP Res");
	}
	if(campobloqcliente.enderecores == "S"){
		document.getElementById("enderecores").setAttribute("required", "Endereço Res");
	}
	if(campobloqcliente.email == "S"){
		document.getElementById("email").setAttribute("required", "Email");
	}
	if(campobloqcliente.fone == "S"){
		document.getElementById("foneres").setAttribute("required", "Fone Res");
	}
	if(campobloqcliente.contato == "S"){
		document.getElementById("contato").setAttribute("required", "Contato");
	}

	$("input").removeAttr("gear");
	$.gear();
});

$.cadastro.before.salvar = function(){
	cliente_mix();

	if(param_cadastro_validarcpfcnpj && $("#ufres").val() !== "EX"){
		var valido = false;
		switch($("#tppessoa").val()){
			case "F":
				valido = valid_cpf($("#cpfcnpj").val());
				break;
			case "J":
				valido = valid_cnpj($("#cpfcnpj").val());
				break;
		}
		if(!valido && $("#ufres").val() !== "EX"){
			$.messageBox({
				type: "error",
				text: "O CPF/CNPJ informado &eacute; inv&aacute;lido.",
				focusOnClose: $("#cpfnpj")
			});
			return false;
		}
	}

	if($("#ufres").val() === "EX" && $("#idestrangeiro").val().length === 0){
		$("#abaprincipal").click();
		$.messageBox({
			type: "error",
			text: "O campo <b>Id Estrangeiro</b> deve ser preenchido.",
			focusOnClose: $("#idestrangeiro")
		});
		return false;
	}

	if(param_cadastro_verificaemail && $("#email").val().length > 0){
		$.ajax({
			async: false,
			data: ({
				email: $("#email").val()
			}),
			url: "../ajax/cliente_verificaemail.php",
			success: function(html){
				salvar = html;
			}
		});
	}else{
		return true;
	}

	if(salvar === "S"){
		return true;
	}else{
		$.messageBox({
			type: "error",
			text: "Email j&aacute; cadastrado."
		});
		return false;
	}
};

$.cadastro.after.clonar = function(){
	$("#dtinclusao").val(dataatual);
}

$.cadastro.after.carregar = function(){
	clienteestab_desenhar();
	arqcliente_listar();

	$.ajax({
		async: false,
		data: ({
			codcliente: $("#codcliente").val(),
			pedido: "ClientePF"
		}),
		url: "../ajax/cliente_mix.php",
		success: function(html){
			extractScript(html);
		}
	});
};

$.cadastro.after.salvar = function(){
	verifica_contribuinte = false;
};

$.cadastro.after.alterar = function(){
	if(alterarstatuslimite === "N"){
		$("#codstatus,#limite1,#limite2").disabled(true);
	}
};

$.cadastro.after.alterar = function(){
	if(alterarstatuslimite == "N"){
		$("#codstatus,#limite1,#limite2").disabled(true);
	}
	verificacao_contribuinte = true;
	$("#debito1,#debito2,#dtinclusao").disabled(true);
	$("#codvendedor").disabled(!param_cadastro_habvendcadcliente);
	var cpfcnpj = $("#cpfcnpj").val();
	var rgie = $("#rgie").val();
	changetppessoa();
	$("#cpfcnpj").val(cpfcnpj);
	$("#rgie").val(rgie);
	contribuinte_val = $("#contribuinteicms").val();
	verifica_contribuinte(contribuinte_val);
};

$.cadastro.after.inserir = function(){
	verificacao_contribuinte = true;
	$("#status").val("A");
	$("#debito1,#debito2,#dtinclusao").disabled(true);
	$("#codvendedor").disabled(!param_cadastro_habvendcadcliente);
	$("body").focusFirst();
	changetppessoa();
	verifica_contribuinte();
};

$.cadastro.after.limpar = function(){
	changetppessoa();
};

$.cadastro.after.novapesquisa = function(){
	changetppessoa();
};

$.cadastro.before.clonar = function(){
	$("#limite1,#limite2,#debito1,#debito2").val("0,00");
	return true;
};

function interesse_buscar(){
	$("#divinteresses").html("<label>Carregando...</label>");
	$.ajax({
		url: "../ajax/cliente_buscarinteresse.php",
		success: function(html){
			$("#divinteresses").html(html);
			var checkbox = $("#divinteresses input:checkbox");
			checkbox.bind("click", function(){
				interesse_alterar();
			});
			if($("#hdnCadStatus").val() == 1){
				checkbox.disabled(true);
			}
		}
	});
}

function clientecompl_carregar(){
	$.ajax({
		url: "../ajax/cliente_clientecompl_carregar.php",
		success: function(html){
			extractScript(html);
		}
	});
}

function clientecompl_gravar(codcomplcadastro, valor){
	$.ajax({
		url: "../ajax/cliente_clientecompl_gravar.php",
		data: ({
			codcomplcadastro: codcomplcadastro,
			valor: valor
		}),
		success: function(html){
			extractScript(html);
		}
	});
}

function clienteestab_desenhar(){
	$.ajax({
		async: false,
		url: "../ajax/cliente_clienteestab_desenhar.php",
		success: function(html){
			extractScript(html);
		}
	});
}

function clienteestab_alterar(){
	arr_codestabelec = $("#grdclienteestab :checkbox[codestabelec]:checked:enabled").map(function(){
		return $(this).attr("codestabelec");
	}).get();
	if(arr_codestabelec.length == 0){
		arr_codestabelec = arr_codestabelec = $("#grdclienteestab :checkbox[codestabelec]").map(function(){
			return $(this).attr("codestabelec");
		}).get();
	}
	$.ajax({
		async: false,
		data: ({
			codcliente: $("#codcliente").val(),
			arr_codestabelec: arr_codestabelec
		}),
		url: "../ajax/cliente_clienteestab_alterar.php",
		success: function(html){
			extractScript(html);
		}
	});
}

function clienteestab_selecionar(){
	$.modalWindow({
		title: "Mix de Cliente",
		content: $("#div_clienteestab"),
		width: "480px"
	});
}

function clienteestab_gravar(){
	$.loading(true);
	$.ajax({
		url: "../ajax/cliente_clienteestab_gravar.php",
		data: ({
			codcliente: $("#ce_codcliente").val(),
			arr_codestabelec: $("#grd_clienteestab :checkbox[codestabelec]:checked:enabled").map(function(){
				return $(this).attr("codestabelec");
			}).get()
		}),
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function imprimir_etiqueta(){
	$("#div_imprimiretiqueta").find("input:text,select").disabled(false);
	$.modalWindow({
		title: "Imprimir Etiqueta",
		content: $("#div_imprimiretiqueta"),
		width: "300px"
	});
}

function imprimir_etiqueta_(){
	if($("#ie_codetiqcliente").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o modelo da etiqueta.",
			focusOnClose: $("#ie_codetiqcliente")
		});
		return false;
	}
	$.modalWindow("close");
	$.loading(true);
	$.ajax({
		url: "../ajax/impetiqcliente_imprimir.php",
		data: ({
			codcliente: $("#codcliente").val(),
			codetiqcliente: $("#ie_codetiqcliente").val(),
			qtdeetiqueta: "1"
		}),
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function interesse_alterar(){
	var codinteresse = new Array();
	var e = $("#divinteresses input:checkbox:checked");
	for(var i = 0; i < e.length; i++){
		codinteresse[i] = $(e[i]).val();
	}
	$.ajax({
		url: "../ajax/cliente_alterarinteresse.php",
		data: ({
			codinteresse: codinteresse
		}),
		dataType: "html",
		success: function(html){

		}
	});
}

function changetppessoa(){
	var tppessoa = $("#tppessoa").val();
	settipopessoa("cpfcnpj", $("#tppessoa").val(), $("#cpfcnpj"));
	settipopessoa("rgie", $("#tppessoa").val(), $("#rgie"));

	$("#abainteresses").show();
	redimensionar_abafinal();

	$("#divConvenioPF").hide();
	$("#divConvenioPJ").hide();
	$("#cpfcnpj").disabled(false);
	$("#orgaopublico").disabled(!in_array($.cadastro.status(), [2, 3, 4]));
	$("#razaosocial").disabled(true);
	if(tppessoa == "F"){
		$("#divConvenioPF").show();
		$("#orgaopublico").val("N");
		$("#orgaopublico").disabled(true);
		$("#razaosocial").val($("#nome").val());
	}else if(tppessoa == "J"){
		$("#divConvenioPJ").show();
		$("#razaosocial").disabled(false);
	}else{
		$("#cpfcnpj").disabled(true);
	}
	var efocus = $("[focused=true]");
	$("#abaprincipal").click();
	if($(efocus).length > 0){
		efocus.focus();
	}
}

function redimensionar_abafinal(){
	var t_width = 0;
	$("#tabscli").find(".tabon,.taboff").filter(":visible").each(function(){
		t_width += parseFloat($(this).width());
	});
	$("#tabscli .tabend").width($("#tabscli").width() - t_width);
}

function verifica_contribuinte(x){
	if($("#tppessoa").val() === "F"){
		$("#contribuinteicms").val("N");
		$("#contribuinteicms").attr("disabled", true);
	}else if($("#tppessoa").val() === "J" && $("#rgie").val() === ""){
		$("#contribuinteicms").val("N");
		$("#contribuinteicms").attr("disabled", true);
	}else if($("#tppessoa").val() === "J" && $("#rgie").val() !== ""){
		if(x === undefined){
			$("#contribuinteicms").val("S");
		}
		$("#contribuinteicms").attr("disabled", false);
	}
	return true;
}

function imprimir_cliente(){
	var i = 0;
	var arr_codestabelec = [];
	$("#grdclienteestab [codestabelec]").each(function(){
		if($(this).is(":checked")){
			arr_codestabelec[i] = $(this).attr("codestabelec");
			i++;
		}
	});

	if(i === 1){
		window.open("../form/cliente_imprimir.php?codcliente=" + $("#codcliente").val() + "&codestabelec=" + arr_codestabelec[0], "Cliente");
	}else{
		$.modalWindow({
			title: "Imprimir Cliente",
			content: $("#div_imprimicliente"),
			width: "400px"
		});

		$("#impcliente_codestabelec").disabled(false);
	}
}

function imprimir_cliente_(){
	window.open("../form/cliente_imprimir.php?codcliente=" + $("#codcliente").val() + "&codestabelec=" + $("#impcliente_codestabelec").val(), "Cliente");
	$.modalWindow('close');
}

function cliente_mix(){
	$.ajax({
		async: false,
		data: ({
			codcliente: $("#codcliente").val(),
			pedido: "ClientePF"
		}),
		url: "../ajax/cliente_mix.php",
		success: function(html){
			extractScript(html);
		}
	});
	return true;
}

function arqcliente_excluir(filename){
	$.superVisor({
		type: "venda",
		success: function(){
			$.ajax({
				data: ({
					codcliente: $("#codcliente").val(),
					filename: filename
				}),
				async: false,
				url: "../ajax/cliente_arqcliente_excluir.php",
				success: function(html){
					$("#gridarquivo").html(html);
				}
			});
		},
		fail: function(){
			$.messageBox({
				type: "alert",
				text: "&Eacute; preciso da senha de supervisor de venda para excluir o arquivo."
			});
		}
	});
}

function arqcliente_listar(){
	$.ajax({
		data: ({
			codcliente: $("#codcliente").val()
		}),
		async: false,
		url: "../ajax/cliente_arqcliente_listar.php",
		success: function(html){
			$("#gridarquivo").html(html);
		}
	});
}

function arqcliente_upload(){
	$.ajax({
		data: ({
			codcliente: $("#codcliente").val()
		}),
		async: false,
		url: "../ajax/cliente_arqcliente_upload.php",
		success: function(html){
			extractScript(html);
		}
	});
}

function valida_ex(){
	if($("#ufres").val() == "EX"){
		$("[for=idestrangeiro]").css("font-weight", "bold");
	}else{
		$("[for=idestrangeiro]").css("font-weight", "normal");
	}
	filterchild("ufres");
}