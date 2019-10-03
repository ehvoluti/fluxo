$.cadastro.after.limpar = function(){
	$("#btnNovoFilho").disabled(true);
}

$.cadastro.after.retornar = function(){
	itcomposicao_limpar();
}

$.cadastro.before.cancelar = function(){
	if($.cadastro.status() == 2){
		itcomposicao_limpar();
	}
	return true;
}

$.cadastro.after.inserir = function(){
	$("#btnNovoFilho").disabled(false);
	$("#codprodutofilho").disabled(true);
//	explosaoauto();
	itcomposicao_limpar();
}

$.cadastro.after.carregar = function(){
	itcomposicao_desenhar();
	$("#btnCadProducao,#btnCadDesmembramento").hide();
	if($("#tipo").val() == "P"){
		$("#btnCadProducao").show();
	}else if($("#tipo").val() == "D"){
		$("#btnCadDesmembramento").show();
	}
	$("#relatorio_codestabelec").disabled(false);
	$("#impcusto").disabled(false);
}

$.cadastro.after.alterar = function(){
	$("#tipopreco").change();
	$("#btnNovoFilho").disabled(false);
	$("#codprodutofilho").disabled(true);
	$("#griditcomposicao img").show();
	explosaoauto(false);
}

$.cadastro.after.deletar = function(){
	itcomposicao_limpar();
}

$(document).bind("ready",function(){
	$("#arr_codcomposicao").val("1,3,5");
	$("#" + $("#itcomposicao_codproduto").attr("description")).width("267px");
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnCadProducao\" value=\"Produzir\" style=\"margin-right:7px\" onclick=\"openProgram('ComposProducao','codcomposicao='+$('#codcomposicao').val())\">");
	$("#btnCadRetornar1").before("<input type=\"button\" name=\"btnCadGerarRelatorio\" id=\"btnCadGerarRelatorio\" value=\"Gerar Relat&oacute;rio\" style=\"margin-right:7px\">");
	$("#btnCadRetornar1").before("<input type=\"button\" id=\"btnCadDesmembramento\" value=\"Desmembrar\" style=\"margin-right:7px\" onclick=\"openProgram('ComposDesmembramento','codcomposicao='+$('#codcomposicao').val())\">");
	itcomposicao_limpar();
	$("#btnCadGerarRelatorio").click(function(){
//		if(contestab > 1){
			filtro_relatorio();
//		}else{
//			composicao = $("#codcomposicao").val();
//			window.open("runreport.php?report=relcomposicao&format=pdf1&composicao="+composicao);
//		}
	});

	$("#tipopreco").change(function(){
		if(($(this).val() == "A" || $(this).val() == "O") && fatoratuavenda == "S"){
			$("#fatorvenda").disabled(false);
		}else{
			$("#fatorvenda").disabled(true).val("1,00");
		}
	})
});

function explosaoauto(alterar){
	if(typeof(alterar) == "undefined"){
		alterar = true;
	}
	if(in_array($("#tipo").val(),["D","P","V","A"])){
		$("#explosaoauto").disabled(false);
		if(alterar){($("#tipo").val() == "A") ? $("#explosaoauto").val("S") : $("#explosaoauto").val("N");}
	}else{
		$("#explosaoauto").disabled(true);
		if(alterar){
			if($("#tipo").val().length > 0){
				$("#explosaoauto").val("S");
			}else{
				$("#explosaoauto").val("");
			}
		}
	}
}

function itcomposicao_buscarembalagem(){
	$.ajax({
		url:"../ajax/composicao_buscarembalagem.php",
		data:({
			codproduto:$("#itcomposicao_codproduto").val()
		}),
		success:function(html){
			extractScript(html);
		}
	});
}

function itcomposicao_desenhar(){
	$.ajax({
		async:false,
		url:"../ajax/composicao_itcomposicao_desenhar.php",
		success:function(html){
			$("#griditcomposicao").html(html);
			$.gear();
			if(!in_array($.cadastro.status(),[2,3])){
				$("#griditcomposicao input:text").disabled(true);
				$("#griditcomposicao img").hide();
			}
			$("#griditcomposicao input[coluna]").bind("change",function(){
				$.ajax({
					async:false,
					url:"../ajax/composicao_itcomposicao_alterar.php",
					data:{
						codproduto:$(this).attr("codproduto"),
						coluna:$(this).attr("coluna"),
						valor:$(this).val()
					},
					success:function(html){
						extractScript(html);
					}
				})
			});
		}
	});
}

function itcomposicao_excluir(codproduto){
	$.messageBox({
		type:"info",
		text:"Tem certeza que quer excluir o produto da composi&ccedil;&atilde;o? ",
		buttons:({
			"Sim":function(){
				$.ajax({
				url:"../ajax/composicao_itcomposicao_excluir.php",
				data:({
					codproduto:codproduto
				}),
				dataType:"html",
					success:function(html){
						itcomposicao_desenhar();
					}
				});
				$.messageBox("close");
			},
			"N\u00E3o":function(){
				$.messageBox("close");
			}
		}),
		afterClose:function(){
			gravardados_gravar();
		}
	});
}

function itcomposicao_incluir(){
	$.messageBox({
		type:"info",
		title:"Informe os dados do novo produto filho:",
		text:$("#divitcomposicao"),
		buttons:({
			"Incluir":function(){
				if($("#itcomposicao_codproduto").val().length == 0){
					$.messageBox("close");
					$.messageBox({
						type:"error",
						text:"Informe o produto.",
						afterClose:function(){
							itcomposicao_incluir();
						}
					});
				}else if($("#desc_itcomposicao_codproduto").val() == "(Produto não encontrado)"){
					$.messageBox("close");
					$.messageBox({
						type:"error",
						text:"Produto n&atilde;o encontrado.",
						afterClose:function(){
							itcomposicao_incluir();
						}
					});
				}else if($("#itcomposicao_codunidade").val().length == 0){
					$.messageBox("close");
					$.messageBox({
						type:"error",
						text:"Informe a unidade.",
						afterClose:function(){
							itcomposicao_incluir();
						}
					});
				}else if($("#itcomposicao_quantidade").val().length == 0){
					$.messageBox("close");
					$.messageBox({
						type:"error",
						text:"Informe a quantidade.",
						afterClose:function(){
							itcomposicao_incluir();
						}
					});
				}else{
					$.messageBox("close");
					_itcomposicao_incluir();
				}
			},
			"Cancelar":function(){
				$.messageBox("close");
			}
		}),
		focusOnClose:$("#btnNovoFilho")
	});
}

function _itcomposicao_incluir(){
	$.ajax({
		url:"../ajax/composicao_itcomposicao_incluir.php",
		data:({
			codproduto:$("#itcomposicao_codproduto").val(),
			codunidade:$("#itcomposicao_codunidade").val(),
			quantidade:$("#itcomposicao_quantidade").val()
		}),
		dataType:"html",
		success:function(html){
			extractScript(html);
		}
	});
}

function itcomposicao_limpar(){
	$.ajax({
		url:"../ajax/composicao_itcomposicao_limpar.php",
		success:function(html){
			itcomposicao_desenhar();
		}
	});
}

function filtro_relatorio(){
	$.modalWindow({
		title: "Filtro",
		content: $("#modal-filtro"),
		width: "420px",
		height: "160px",
		closeButton: true
	});
}

function gerar_relatorio(){
	$.modalWindow("close");
	composicao = $("#codcomposicao").val();
	codestabelec = $("#relatorio_codestabelec").val();
	impcusto = $("#impcusto").val();
	window.open("runreport.php?report=relcomposicao&format=pdf1&composicao="+composicao+"&codestabelec="+codestabelec+"&impcusto="+impcusto, "_blank");
}