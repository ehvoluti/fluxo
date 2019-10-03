var codproduto;
var elem;
var option;

$(document).bind("ready",function(){
	if(param_estoque_gravaautoajuste == "S"){
		$("#frmCampos input").change(function(){
			elem = $(this);
			if(!$("#rapida").checked()){
				gravardados();
			}
		});

		$("#frmCampos input").focus(function(){
			codproduto = $("#boxproduto option:selected").attr("codproduto");
		});
	}

	$("#frmCampos input").keypress(function(e) {
		if(e.which == 13){ // enter
			if($("#rapida").checked()){
				gravardados();
			}
		}
	});

	if(codestabelec.length > 0){
		$("#arr_codestabelec input:checkbox[key='" + codestabelec + "']").checked(true);
	}
	if(filtrar == "S"){
		filtrar();
	}
	$("#divFiltro *").filter("input:text,select").bind("keydown",function(e){
		if(keycode(e) == 13){
			filtrar();
		}
	});

	// Esc para voltar e limpar.
	$("#divFiltro *").filter("input:text,select").bind("keydown",function(e){
		if(keycode(e) == 27){
			$("#divFiltro input:text,select:not(#tipodescricao)").val("");
		}
	});
	$("#margemvendaatc,#margemcustoatc,#margemvendavrj,#margemcustovrj,#precopdv,#_reffornec,#margempratatc,#precosugestaoatc,#margempratvrj,#precomargemzerovrj,#precomargemzeroatc,#precosugestaovrj,#custorepant,#icmsnfe,#icmsnfs,#icmspdv,#aliqpis,#aliqcofins,#custotabant,#codigoncm").disabled(true);

	$("#custorep,#margemvrj,#margematc").bind("change",function(){
		$.ajax({
			url:"../ajax/produto_buscarsugestao.php",
			data:({
				atualiza: 1,
				codproduto:$("#boxproduto option:selected").attr("codproduto"),
				arr_codestabelec:$("#arr_codestabelec_").val().split(","),
				margemvrj: $("#margemvrj").val(),
				margematc: $("#margematc").val(),
				custotab:$("#custotab").val(),
				custorep:$("#custorep").val()
			}),
			dataType:"html",
			success:function(html){
				extractScript(html);
			}
		});
	});

	// Carrega as configuracoes da tecla TAB
	tabcontrol_carregar();

	$("#frmCampos input[alterado]").change(function(){
		$(this).attr("alterado","S");
	});

}).bind("keydown",function(e){
	if(e.keyCode == 27){
		//limpar_filtro();
		$("#tabFiltro").click();
	}
});

function gravardados_similar(){
	$.loading(true);
	$("#alterar_similar").val("N");
	var option = $("#boxproduto option:selected");
	$.ajax({
		async:false,
		url:"../ajax/ajusteproduto_similar.php",
		data:({
			codproduto:$(option).attr("codproduto")
		}),
		success:function(html){
			$.loading(false);
			if(trim(html) == "TRUE"){
				if(param_estoque_escolheatualizasimi == "S"){
					$.modalWindow({
						closeButton: false,
						title: "",
						content: $("#div_similar"),
						width: "360px",
						height: "120px"
					});
				}else{
					$.messageBox({
						type:"info",
						text:"O produto faz parte de um grupo de similares.<br>Deseja atualizar os pre&ccedil;os "+param_estoque_simcustorep+" dos produtos similares?",
						buttons:({
							"Sim":function(){
								$("#alterar_similar").val("S");
								$.messageBox("close");
							},
							"N\u00E3o":function(){
								$.messageBox("close");
							}
						}),
						afterClose:function(){
							$("#div_similar").html(div_similar);
							gravardados_composicao();
						}
					});
				}
			}else{
				gravardados_composicao();
			}
		}
	});
}



function alterartodos(){
	$.modalWindow({
		closeButton:true,
		title:"Alterar todos os produtos filtrados",
		content:$("#div_alterartodos"),
		width:"400px"
	});
}

function alterartodos_gravar(){
	$("#frmCampos input[alterado]").attr("alterado","S");
	if($("#tds_tipopercprecovrj").val().length == 0){
		$.messageBox({
			type:"error",
			text:"Informe o tipo de ajuste para pre&ccedil;o varejo.",
			focusOnClose:$("#tds_tipopercprecovrj")
		});
		return false;
	}
	if($("#tds_precovrj").val().length == 0){
		$.messageBox({
			type:"error",
			text:"Informe o percentual para pre&ccedil;o varejo.",
			focusOnClose:$("#tds_precovrjo")
		});
		return false;
	}
	if($("#tds_tipopercprecoatc").val().length == 0){
		$.messageBox({
			type:"error",
			text:"Informe o tipo de ajuste para pre&ccedil;o atacado.",
			focusOnClose:$("#tds_tipopercprecoatc")
		});
		return false;
	}
	if($("#tds_precoatc").val().length == 0){
		$.messageBox({
			type:"error",
			text:"Informe o percentual para pre&ccedil;o atacado.",
			focusOnClose:$("#tds_precoatc")
		});
		return false;
	}
	$.ajaxProgress({
		url:"../ajax/ajusteproduto_atualizartodos.php",
		data:({
			query:$("#query").val(),
			arr_codestabelec:$("#arr_codestabelec_").val().split(","),
			tipopercprecoatc:$("#tds_tipopercprecoatc").val(),
			percprecoatc:$("#tds_precoatc").val().replace(",","."),
			tipopercprecovrj:$("#tds_tipopercprecovrj").val(),
			percprecovrj:$("#tds_precovrj").val().replace(",","."),
			tipoperccustorep:$("#tds_tipoperccustorep").val(),
			perccustorep:$("#tds_custorep").val().replace(",","."),
		}),
		success:function(html){
			extractScript(html);
		}
	});
}

var ajaxLista = null;
function buscarlista(){
	if(ajaxLista == null && parseInt($("#boxproduto")[0].scrollHeight) - parseInt($("#boxproduto")[0].scrollTop) - 348 < 110){
		ajaxLista = $.ajax({
			url:"../ajax/ajusteproduto_buscarlista.php",
			data:({
				query:$("#query").val(),
				arr_codestabelec:$("#arr_codestabelec_").val().split(","),
				offset:$("#boxproduto option").length,
				itemcomp: $("#itemcomp").checked() ? "S" : "N"
			}),
			success:function(html){
				ajaxLista = null;
				var scrolltop = $("#boxproduto").scrollTop();
				extractScript(html);
				$("#boxproduto").scrollTop(scrolltop);
			}
		});
	}
}

function ajusteindividual(){
	var option = $("#boxproduto option:selected");
	$.ajax({
		async:false,
		url:"../ajax/ajusteproduto_ajusteindividual_grid.php",
		data:({
			arr_codestabelec:$(option).attr("arr_codestabelec").split(","),
			codproduto:$(option).attr("codproduto")
		}),
		success:function(html){
			$("#grd_ajusteindividual").html(html);
			$("#grd_ajusteindividual [codproduto]").change(function(){
				$.ajax({
					url:"../ajax/ajusteproduto_ajusteindividual_atualizar.php",
					data:({
						codestabelec:$(this).attr("codestabelec"),
						codproduto:$(this).attr("codproduto"),
						coluna:$(this).attr("coluna"),
						valor:$(this).is(":checkbox") ? ($(this).checked() ? "S" : "N") : $(this).val()
					}),
					success:function(html){
						extractScript(html);
					}
				});
			});
		}
	});

	$.modalWindow({
		title:"Ajuste individual",
		content:$("#div_ajusteindividual"),
		width:"960px",
		height:"auto",
		closeButton:false
	});
}

var ajaxBusca = null;
function buscardados(){
	$("#frmCampos input").removeAttr("title");
	var option = $("#boxproduto option:selected");

	if($(option).attr("arr_codestabelec") != undefined){
		if(ajaxBusca != null){
			ajaxBusca.abort();
		}
		ajaxBusca = $.ajax({
			async:false,
			url:"../ajax/ajusteproduto_buscardados.php",
			data:({
				arr_codestabelec:$(option).attr("arr_codestabelec").split(","),
				codproduto:$(option).attr("codproduto")
			}),
			dataType:"html",
			success:function(html){
				ajaxBusca = null;
				extractScript(html);
				$("#frmCampos input[alterado]").attr("alterado","N");
			}
		});
	}
	if($("#usuario").val().length > 0 && $("#datalog").val().length > 0){
		usuario_nome($("#usuario").val(), function(nome){
			$("#spn_usuario").html(nome);
		});
		$("#spn_datalog").html($("#datalog").val());
		$("#lbl_ultalteracao").show();
	}
}

function filtrar(){
	var	arr_codestabelec = $("#arr_codestabelec input:checkbox:checked").map(function(){
		return $(this).attr("key");
	}).get();

	if(arr_codestabelec.length == 0){
		$.messageBox({
			type:"error",
			text:"Informe os estabelecimentos para a ajuste de pre&ccedil;os.",
			foncusOnClose:$("#arr_codestabelec")
		});
		return false;
	}
	if($("#descricaofiscal").val().length > 0 && $("#tipodescricao").val().length == 0){
		$.messageBox({
			type:"error",
			text:"Informe o tipo de pesquisa pela descri&ccedil;&atilde;o do produto.",
			focusOnClose:$("#tipodescricao")
		});
		return false;
	}

	$("#boxproduto option").remove();

	$("#arr_codestabelec_").val(arr_codestabelec.join(","));

	$.loading(true);
	$.ajax({
		url:"../ajax/ajusteproduto_buscarlista.php",
		data:({
			offset:0,
			arr_codestabelec:arr_codestabelec,
			codproduto:$("#codproduto").val(),
			descricaofiscal:$("#descricaofiscal").val(),
			tipodescricao:$("#tipodescricao").val(),
			codfornec:$("#codfornec").val(),
			coddepto:$("#coddepto").val(),
			codgrupo:$("#codgrupo").val(),
			codsubgrupo:$("#codsubgrupo").val(),
			codfamilia:$("#codfamilia").val(),
			codsimilar:$("#codsimilar").val(),
			custopreco:$("#custopreco").val(),
			reffornec:$("#reffornec").val(),
			numpedido:$("#numpedido").val(),
			numnotafis:$("#numnotafis").val(),
			serie:$("#serie").val(),
			dtentrada1:$("#dtentrada1").val(),
			dtentrada2:$("#dtentrada2").val(),
			dtdigitacao1:$("#dtdigitacao1").val(),
			dtdigitacao2:$("#dtdigitacao2").val(),
			foralinha:$("#foralinha").val(),
			custoalterado:$("#custoalterado").val(),
			marca:$("#marca").val(),
			itemcomp: $("#itemcomp").checked() ? "S" : "N"
		}),
		success:function(html){
			$.loading(false);
			extractScript(html);
			if($("#boxproduto option").length > 0){
				/*
				 * Murilo 01/08/2016 - Comentado porque estava alterando a ordenacao
				 * de quandos sao itens de um pedido ou nota fiscal, entao a ordenacao
				 * estao sendo feita no arquivo ajax
				 *
				var itensOrdenados = $("#boxproduto option").sort(function (a, b) {
					return a.text < b.text ? -1 : 1;
				});
				$("#boxproduto").html(itensOrdenados);
				*/
				$("#tabProdutos").click();
				$("#boxproduto option:first").attr("selected",true);
				$("#preco").focus();
			}else{
				$.messageBox({
					type:"alert",
					text:"Nenhum produto foi encontrado no filtro informado."
				});
			}
			buscardados();
		}
	});
}

function gravardados(){
	if($("#precoatc").val().replace(",", ".") == 0 && $("#precoatcof").val().replace(",", ".") == 0 && $("#qtdatacado").val().replace(",", ".") > 0 ){
		$.messageBox({
			type:"error",
			text:"Pre&ccedil;o atacado n&atilde;o preenchido para a quantidade atacado informada.",
			onclose:function(){
				$("#qtdatacado").focus();
			}
		});
		return false;
	}

	gravardados_similar();
	codproduto = $("#boxproduto option:selected").attr("codproduto");
}

function gravardados_composicao(){
	$.loading(true);
	var option = $("#boxproduto option:selected");
	$("#alterar_composicao").val("N");
	$.ajax({
		async:false,
		url:"../ajax/ajusteproduto_composicao.php",
		data:({
			codproduto:$(option).attr("codproduto")
		}),
		success:function(html){
			$.loading(false);
			if(trim(html) == "TRUE"){
				$.messageBox({
					type:"info",
					text:"O produto faz parte de uma composi&ccedil;&atilde;o.<br>Deseja atualizar os pre&ccedil;os do produto pai da composi&ccedil;&atilde;o?",
					buttons:({
						"Sim":function(){
							$("#alterar_composicao").val("S");
							$.messageBox("close");
						},
						"N\u00E3o":function(){
							$.messageBox("close");
						}
					}),
					afterClose:function(){
						if(param_estoque_gravaautoajuste == "S" && !$("#rapida").checked()){
							ajusteindividual_atualizar();
						}else{
							gravardados_gravar();
						}

					}
				});
			}else{
				if(param_estoque_gravaautoajuste == "S" && !$("#rapida").checked()){
					ajusteindividual_atualizar();
				}else{
					gravardados_gravar();
				}
			}
		}
	});
}

function gravardados_gravar(){
	var option = $("#boxproduto option:selected");

	if($("#similar_precovrj").checked()){
		$("#precovrj,#precovrjof").attr("alterado","S");
	}
	if($("#similar_precoatc").checked()){
		$("#precoatc,#precoatcof").attr("alterado","S");
	}
	if($("#similar_custorep").checked()){
		$("#custorep").attr("alterado","S");
	}
	if($("#similar_custotab").checked()){
		$("#custotab").attr("alterado","S");
	}

	var dados = {
		arr_codestabelec:$(option).attr("arr_codestabelec").split(","),
		codproduto:$(option).attr("codproduto"),
		alterar_similar:$("#alterar_similar").val(),
		similar_precovrj:$("#similar_precovrj").checked() ? "S" : "N",
		similar_precoatc:$("#similar_precoatc").checked() ? "S" : "N",
		similar_custorep:$("#similar_custorep").checked() ? "S" : "N",
		similar_custotab:$("#similar_custotab").checked() ? "S" : "N",
		alterar_composicao:$("#alterar_composicao").val(),
		urgente:($("#urgente").checked() ? "S" : "N")
	}

	$("#frmCampos input[alterado]").each(function(){
		if($(this).attr("alterado") == "S"){
			dados[$(this).attr("id")] = $(this).val();
		}
	});

	$.ajaxProgress({
		url:"../ajax/ajusteproduto_gravardados.php",
		data: dados,
		success:function(html){
			extractScript(html);
			if($("#rapida").checked()){
				next();
			}
		}
	});
}

function historico(){
	var option = $("#boxproduto option:selected");
	var win = $.modalWindow({
		closeButton:true,
		height:"380px",
		padding:false,
		title:"Hist&oacute;rico de Pre&ccedil;os e Custos",
		width:"900px"
	});

	var iframe = document.createElement("iframe");
	iframe.scrolling = "no";
	win[0].appendChild(iframe);
	$(iframe).attr({
		"frameborder":"0",
		"src":"../form/ajusteproduto_historico.php?arr_codestabelec=" + $(option).attr("arr_codestabelec") + "&codproduto=" + $(option).attr("codproduto")
	}).css({
		"height":"100%",
		"width":"100%"
	});
}

function limpar_filtro(){
	$("#divFiltro").find("input:text,select").not("#codestabelec,#tipodescricao").val("");
}

function tabcontrol(){
	$.modalWindow({
		title:"Controle da tecla TAB",
		content:$("#div_tabcontrol"),
		width:"450px"
	});
}

function tabcontrol_carregar(){
	$.ajax({
		async:false,
		url:"../ajax/tabcontrol_carregar.php",
		data:({
			rotina:"ajusteproduto"
		}),
		success:function(html){
			extractScript(html);
			tabcontrol_elemento();
		}
	});
}

function tabcontrol_elemento(){
	$("#div_tabcontrol_chk input:checkbox").each(function(){
		$("#" + $(this).attr("id").substr(11)).attr("tabindex",($(this).checked() ? "0" : "-1"));
	});
}

function tabcontrol_gravar(){
	$.loading(true);
	$.ajax({
		url:"../ajax/tabcontrol_gravar.php",
		data:({
			rotina:"ajusteproduto",
			arr_campo:$("#div_tabcontrol_chk input:checkbox").not(":checked").map(function(){ return $(this).attr("id"); }).get()
		}),
		success:function(html){
			$.loading(false);
			extractScript(html);
			tabcontrol_elemento();
		}
	});
}

function ajusteestoque(){
	var option = $("#boxproduto option:selected");
	$.ajax({
		async:false,
		url:"../ajax/ajusteproduto_ajusteestoque_grid.php",
		data:({
			arr_codestabelec:$(option).attr("arr_codestabelec").split(","),
			codproduto:$(option).attr("codproduto")
		}),
		success:function(html){
			$("#grd_ajusteestoque").html(html);
			$("#grd_ajusteestoque [codproduto]").change(function(){
				$.ajax({
					url:"../ajax/ajusteproduto_ajusteindividual_atualizar.php",
					data:({
						codestabelec:$(this).attr("codestabelec"),
						codproduto:$(this).attr("codproduto"),
						coluna:$(this).attr("coluna"),
						valor:$(this).val()
					}),
					success:function(html){
						extractScript(html);
					}
				});
			});
		}
	});
	$.modalWindow({
		title:"Ajuste de Estoque",
		content:$("#div_ajusteestoque"),
		width:"900px",
		height:"auto",
		closeButton:false
	});
}

function composicao_atualizar(){
	$.ajax({
		url:"../ajax/ajusteproduto_composicao_atualizar.php",
		data:({
			arr_codestabelec:$("#arr_codestabelec_").val(),
			codproduto:$("#boxproduto option:selected").attr("codproduto")
		}),
		success:function(html){
			extractScript(html);
		}
	});
}

function dados_produto(codproduto){
    $.ajax({
        url:"../ajax/ajusteproduto_dadosdoproduto.php",
        data:({
            arr_codestabelec:$("#arr_codestabelec_").val(),
            codproduto:codproduto
        }),
        success:function(html){
            extractScript(html);
            $.loading(false);
            $.modalWindow({
                closeButton:true,
                content:$("#div_dadosproduto"),
                title:"Dados do Produto",
                width:"750px"
            });
        }
    });

    $.ajax({
        url:"../ajax/cotacao_vendamedia.php",
        data:({
            codestabelec:$("#arr_codestabelec_").val(),
            codproduto:codproduto
        }),
        success:function(html){
            ajax_pedidoautomatico_vendamedia = null;
            $("#grd_dados_vendamendia").html(html);
        }
    });
    $.ajax({
        url:"../ajax/cotacao_ultimascompras.php",
        data:({
            codestabelec:$("#arr_codestabelec_").val(),
            codproduto:codproduto
        }),
        success:function(html){
            $("#grd_dados_ultimascompras").html(html);
        }
    });
}

function next(){
	var next = $("#boxproduto option:selected").next();
	if(next.length > 0){
		$("#boxproduto").val($("#boxproduto :selected").next().val()).trigger("change");
	}
	$("#precovrj").select();
}

function ajusteindividual_atualizar(){
	$.ajax({
		url:"../ajax/ajusteproduto_ajusteindividual_atualizar.php",
		data:({
			codestabelec:$("#arr_codestabelec_").val().split(","),
			codproduto:$("#boxproduto option:selected").attr("codproduto"),
			coluna:$(elem).attr("id"),
			valor:$(elem).val(),
			urgente:($("#urgente").checked() ? "S" : "N"),
			alterar_similar:$("#alterar_similar").val(),
			alterar_composicao:$("#alterar_composicao").val()
		}),
		success:function(html){
			extractScript(html);
		}
	});
}

function ultimosprecos(){
	$.ajax({
		url:"../ajax/ajusteproduto_ultimosprecos.php",
		data:({
			codproduto: $("#boxproduto option:selected").attr("codproduto"),
			codestabelec:$("#arr_codestabelec_").val().split(",")
		}),
		success:function(html){
			$("#div_ultimosprecos_grid").html(html);
		}
	});

	$.modalWindow({
		title:"Ultimos Pre&ccedil;os no Concorrente",
		content:$("#div_ultimosprecos"),
		width:"450px",
		height:"300px"
	});
}
