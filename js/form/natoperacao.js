$(document).bind("ready", function(){
	$("#codcf,#geracsticms090").bind("change", function(){
		if($("#geracsticms090").val() == "S"){
			$("#alteracfisento,#alteracficmssubst").disabled(false);
		}else{
			if($("#codcf").val().length == 0){
				$("#alteracfisento,#alteracficmssubst").attr("checked", false).trigger("change").disabled(true);
			}else{
				$("#alteracfisento,#alteracficmssubst").disabled(false);
			}
		}
	});
	$("#totnfigualbcicms").bind("change", function(){
		if($(this).checked()){
			$("#sumicmstotalnf").checked(false).disabled(true);
		}else{
			$("#sumicmstotalnf").disabled(false);
		}
	});
	$("#operacao").bind("change", function(){
		if($(this).val() == "VD"){
			$("#vendaagenciada").disabled(false);
		}else{
			$("#vendaagenciada").disabled(true).checked(false);
		}

		var istransf = $.inArray($(this).val(), ["TS", "TE"]) < 0;
		$("#explodetransf").disabled(istransf ? true : false).checked(istransf ? true : false);

		$("#aprovicmsprop").disabled($.inArray($(this).val(), ["TS", "TE"]) < 0);
	});

	$("#vendaagenciada").bind("change", function(){
		if($(this).checked()){
			$("#geraestoque").disabled(true).val("N");
		}else{
			$("#geraestoque").disabled(false);
		}
	});
	$("#grdfornecestab input").bind("change", function(){
		natoperacaoestab_alterar(this);
	});
	$(".codconta").bind("click", function(){
		var estabelecimento = $(this).attr("codestabelec");
		var coluna = $(this).attr("coluna");
		var elem = $(this);
		$.messageBox({
			type: "info",
			title: "Informe o N&uacute;mero da Conta Contabil",
			text: $("#contacontabil"),
			buttons: ({
				"Incluir": function(){
					$.messageBox("close");
					$.ajax({
						async: false,
						url: "../ajax/natoperacaoestab_alterar.php",
						data: ({
							codestabelec: estabelecimento,
							coluna: coluna,
							valor: $("#codconta_estab").val()
						}),
						success: function(html){
							extractScript(html);
						}
					});
				},
				"Cancelar": function(){
					$.messageBox("close");
				}
			})
		});
	});
});

$.cadastro.after.cancelar = function(){
	$("#natoperacaoestab input").attr("disabled","disabled");
}

$.cadastro.after.inserir = function(){
	$("#natoperacaoestab input").removeAttr("disabled");
	$("#codcf").trigger("change");
	$("#totnfigualbcicms").trigger("change");
	$("#vendaagenciada").trigger("change");
	verificar_exterior();
}

$.cadastro.after.alterar = function(){
	$("#natoperacaoestab input").removeAttr("disabled");
	$("#operacao").trigger("change");
	$("#codcf").trigger("change");
	$("#totnfigualbcicms").trigger("change");
	$("#vendaagenciada").trigger("change");
	$(".natdeletar").show();
	verificar_exterior();
}
$.cadastro.after.carregar = function(){
	natoperacaoestab_desenhar();
	$(".natdeletar").hide();
	if($("#explodetranf").val() == "S"){
		$("#explodetranf").checked(true);
	}
	if($("#usuario").val().length > 0 && $("#datalog").val().length > 0){
		usuario_nome($("#usuario").val(), function(nome){
			$("#spn_usuario").html(nome);
		});
		$("#spn_datalog").html($("#datalog").val());
		$("#lbl_ultalteracao").show();
	}
}
function verificar_exterior(){
	if(in_array($("#operacao").val(), ["EX", "IM"])){
		$("#totnfigualbcicms,#sumicmstotalnf").disabled(false);
	}else{
		$("#totnfigualbcicms,#sumicmstotalnf").disabled(true).checked(false);
	}
}
function natoperacaoestab_desenhar(){
	$.ajax({
		data: ({
			natoperacao: $("#natoperacao").val()
		}),
		async: false,
		url: "../ajax/natoperacaoestab_desenhar.php",
		success: function(html){
			extractScript(html);
		}
	});
}
function natoperacaoestab_deletar(codestabelec){
	$("#natoperacaoestab input[coluna='codconta'][codestabelec='" + codestabelec + "']").val(null);

	$.ajax({
		async: false,
		url: "../ajax/natoperacaoestab_alterar.php",
		data: ({
			codestabelec: codestabelec,
			coluna: "codconta",
			valor: ""
		}),
		success: function(html){
			extractScript(html);
		}
	});
}