$(document).bind("ready", function(){
	carregar(true, true);

	$("#codconcorrente").bind("change", function(){
		$("#btn_coletordados").disabled($(this).val().length === 0 || $(this).val() !== $("#codconcorrente_").val());
	}).trigger("change");

	$("#frm_prodconcorrente").bind("load", function(){
		iframe_ok = true;
	});

	$("#btnCadColetorDados").bind("change", function(){
		var arquivo = filename($("#coletor_codlayout").val(), "arqlayout");
		$(this).upload({
			filename: arquivo,
			onComplete: function(){
				coletordados_importar();
			}
		});
	});
});

var arr_prodconcorrente = null;
var avancar = true;
var m_codproduto = null;
var m_codconcorrente = null;
var iframe_ok = false;
var var_html;

function atualizar(){
	$.messageBox({
		type: "info",
		text: "Tem certeza que deseja atualizar o pre&ccedil;o dos produtos selecionados?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				arr_prodconcorrente = new Array();
				$("#grd_prodconcorrente [coluna='url']").each(function(){
					if($(this).val().length > 0 && $("#grd_prodconcorrente :checkbox[codproduto='" + $(this).attr("codproduto") + "']:checked").length > 0){
						arr_prodconcorrente[arr_prodconcorrente.length] = ({
							codconcorrente: $(this).attr("codconcorrente"),
							codproduto: $(this).attr("codproduto"),
							url: $(this).val(),
							jquerypreco: $(this).attr("jquerypreco")
						});
					}
				});

				$.background(true);
				var border = document.createElement("div");
				var div = document.createElement("div");
				var pbar = document.createElement("div");
				var text = document.createElement("label");
				document.body.appendChild(border);
				border.appendChild(div);
				div.appendChild(pbar);
				div.appendChild(text);
				$(border).attr({
					"iframeProgress": true,
					"tabindex": "0"
				}).css({
					"background": "-webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(242,246,248,1)), color-stop(50%,rgba(216,225,231,1)), color-stop(51%,rgba(181,198,208,1)), color-stop(100%,rgba(224,239,249,1)))",
					"border": "1px solid #666",
					"border-radius": "3px",
					"height": "60px",
					"padding": "5px",
					"width": "270px"
				}).center({left: true, top: true}).focus();
				$(div).css({
					"background": "-webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(255,255,255,1)), color-stop(50%,rgba(243,243,243,1)), color-stop(51%,rgba(237,237,237,1)), color-stop(100%,rgba(255,255,255,1)))",
					"border-radius": "3px",
					"height": "40px",
					"padding": "10px",
					"text-align": "center",
					"width": "250px"
				});
				$(pbar).attr({
					"class": "progressbar"
				}).css({
					"height": "15px",
					"margin-bottom": "10px",
					"width": "100%"
				});
				$(text).css({
					"color": "#444",
					"font-size": "7pt"
				});
				$.gear();
				$(pbar).progressbar(0);

				avancar = true;
				m_codproduto = null;
				atualizar_avancar();
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}

function atualizar_avancar(){
	if(avancar){
		var i = 0;
		var finalizado = true;
		var f_codproduto = false;
		for(var index in arr_prodconcorrente){
			var codproduto = arr_prodconcorrente[index].codproduto;
			var codconcorrente = arr_prodconcorrente[index].codconcorrente;
			var url = arr_prodconcorrente[index].url;
			var jquerypreco = arr_prodconcorrente[index].jquerypreco;
			i++;
			if(codproduto === m_codproduto && m_codconcorrente === codconcorrente){
				f_codproduto = true;
			}else if(codproduto !== undefined && (f_codproduto || (codproduto === m_codproduto && m_codconcorrente === codconcorrente) || m_codproduto === null)){
				finalizado = false;
				m_codproduto = codproduto;
				m_codconcorrente = codconcorrente;
				avancar = false;
				iframe_ok = false;
				$("[iframeProgress] label").html("Atualizando produto " + i + " de " + arr_prodconcorrente.length);
				$("[progressbar]").progressbar(i / arr_prodconcorrente.length * 100);
				$("body", $("#frm_prodconcorrente").contents()).remove();
				$.ajax({
					url: "../ajax/prodconcorrente_carregarsite.php",
					async: false,
					data: ({
						url:url
					}),
					success: function(html){
						var_html = html;
					}
				});
				$("#frm_prodconcorrente").attr({
					"codconcorrente": codconcorrente,
					"codproduto": codproduto,
					"src": url,
					"jquerypreco": jquerypreco
				});
				atualizar_buscarpreco();
				break;
			}
		}

		if(finalizado){
			$.background(false);
			$("[iframeProgress]").remove();
			carregar();
			$.messageBox({
				type: "success",
				text: "Produtos atualizados com sucesso!"
			});
			return false;
		}
	}
	setTimeout("atualizar_avancar()", 3000);
}

function coletordados_estab(){
	$.modalWindow({
		content: $("#div_coletordados"),
		title: "Coletor de dados",
		width: "400px",
		hint: "Escolha o layout para importa&ccedil;&atilde;o do arquivo do concorrente."
	});
}

function coletordados_importar(){
	$.loading(true);
	arr_prodconcorrente = new Array();
	$("#grd_prodconcorrente :checkbox[codproduto]:checked").each(function(i){
		arr_prodconcorrente[i] = $(this).attr("codproduto");
	});

	$.ajax({
		url: "../ajax/prodconcorrente_coletordados_importar.php",
		async: false,
		data: ({
			codconcorrente: $("#codconcorrente").val(),
			codlayout: $("#coletor_codlayout").val(),
			arr_prodconcorrente: arr_prodconcorrente
		}),
		success: function(html){
			extractScript(html);
			$.loading(false);
		}
	});
}


function atualizar_buscarpreco(ultima_tentativa){
	ultima_tentativa = (ultima_tentativa === undefined ? false : ultima_tentativa);

	/*
	var preco = "";
	var preco_aux = $($("#frm_prodconcorrente").attr("jquerypreco"), $("#frm_prodconcorrente").contents()).html();
	var preco_ok = (preco_aux !== undefined && preco_aux !== null && trim(preco_aux) !== "");
	*/
   var jquerypreco = $("#frm_prodconcorrente").attr("jquerypreco").split("|");

	var re = new RegExp(jquerypreco[0]+"(.*?)"+"<\/"+jquerypreco[1]+">", "g");
	if(var_html.match(re) == null){
		avancar = true;
		return true;
	}

	var result = var_html.match(re).map(function(val){
	   return val.replace(jquerypreco[0],'').replace("</"+jquerypreco[1]+">",'');
	});

	var preco = "";
	var preco_aux = result[0];
	var preco_ok = trim(preco_aux) !== "";

	if((preco_ok) || ultima_tentativa){
		if(preco_ok){
			for(var i = 0; i < preco_aux.length; i++){
				var c = preco_aux.substr(i, 1);
				if(in_array(c, ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", ","])){
					preco += c;
				}
			}
			$.ajax({
				url: "../ajax/prodconcorrente_atualizarvalor.php",
				data: ({
					async: false,
					codconcorrente: $("#frm_prodconcorrente").attr("codconcorrente"),
					codproduto: $("#frm_prodconcorrente").attr("codproduto"),
					coluna: "preco",
					valor: preco
				}),
				success: function(html){
					extractScript(html);
				}
			});
		}
		avancar = true;
	}else{
		$.ajax({
			url: "../ajax/prodconcorrente_atualizarvalor.php",
			data: ({
				async: false,
				codconcorrente: $("#frm_prodconcorrente").attr("codconcorrente"),
				codproduto: $("#frm_prodconcorrente").attr("codproduto"),
				coluna: "preco",
				valor: 0
			}),
			success: function(html){
				extractScript(html);
			}
		});
		setTimeout(function(){
			atualizar_buscarpreco(iframe_ok);
		}, 3000);
	}
}

function carregar(forcar, embranco){
	forcar = (forcar === undefined ? false : forcar);
	embranco = (embranco === undefined ? false : embranco);

	if(!forcar){
		/*
		 if($("#codconcorrente").val().length == 0){
		 $.messageBox({
		 type: "error",
		 text: "Informe o concorrente.",
		 focusOnClose: $("#codconcorrente")
		 });
		 return false;
		 }
		 */
	}

	// Guarda qual eram os produtos marcados antes de recarregar para marcar novamente depois de redesenhar
	var arr_codproduto_checked = $("#grd_prodconcorrente :checkbox[codproduto]:checked").map(function(){
		return $(this).attr("codproduto");
	}).get();

	$.loading(true);
	$.ajax({
		url: "../ajax/prodconcorrente_carregar.php",
		data: ({
			embranco: (embranco ? "S" : "N"),
			codconcorrente: $("#codconcorrente").val()
		}),
		success: function(html){
			$.loading(false);

			var codconcorrente_igual = ($("#codconcorrente_").val() == $("#codconcorrente").val());

			$("#codconcorrente_").val($("#codconcorrente").val());
			$("#codconcorrente").trigger("change");
			$("#grd_prodconcorrente").html(html).find("[codproduto][coluna]").bind("change", function(){
				$.ajax({
					url: "../ajax/prodconcorrente_atualizarvalor.php",
					data: ({
						codconcorrente: $(this).attr("codconcorrente"),
						codproduto: $(this).attr("codproduto"),
						coluna: $(this).attr("coluna"),
						valor: $(this).val()
					}),
					success: function(html){
						extractScript(html);
					}
				});
			});

			if(codconcorrente_igual){
				for(var i in arr_codproduto_checked){
					if($.isNumeric(arr_codproduto_checked[i])){
						$("#grd_prodconcorrente :checkbox[codproduto='" + arr_codproduto_checked[i] + "']").checked(true);
					}
				}
			}
			$.gear();
			$("[ativo=N]").removeAttr("checked");
		}
	});
}

function incluirproduto(){
	$.modalWindow({
		content: $("#div_incluirproduto"),
		title: "Incluir Produtos",
		width: "800px"
	});
}

function incluirproduto_filtrar(){
	$.loading(true);
	$.ajax({
		url: "../ajax/prodconcorrente_incluirproduto_filtrar.php",
		data: ({
			codconcorrente: $("#ip_codconcorrente").val(),
			codproduto: $("#ip_codproduto").val(),
			coddepto: $("#ip_coddepto").val(),
			codgrupo: $("#ip_codgrupo").val(),
			codsubgrupo: $("#ip_codsubgrupo").val(),
			pesqconcorrente: ($("#chk_pesqconcorrente").checked() ? "S" : ""),
			buscaurl: ($("#chk_buscaurl").checked() ? "S" : "N")
		}),
		success: function(html){
			$.loading(false);
			$("#grd_incluirproduto").html(html);
		}
	});
}

function incluirproduto_incluir(){
	$.ajaxProgress({
		type: "POST",
		url: "../ajax/prodconcorrente_incluirproduto_incluir.php",
		data: ({
			codconcorrente: $("#codconcorrente_").val(),
			arr_prodconcorrente: $("#grd_incluirproduto :checkbox:checked[codproduto]").map(function(){
				return [[$(this).attr("codproduto"), $(this).parents("tr").find("[codconcorrente]").attr("codconcorrente"), $(this).parents("tr").find("[codconcorrente]").val()]];
			}).get()
		}),
		success: function(html){
			extractScript(html);
		}
	});
}

function removerproduto(codconcorrente, codproduto){
	$.messageBox({
		type: "error",
		text: "Tem certeza que deseja remover o produto da lista?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				$.loading(true);
				$.ajax({
					url: "../ajax/prodconcorrente_removerproduto.php",
					data: ({
						codconcorrente: codconcorrente,
						codproduto: codproduto
					}),
					success: function(html){
						$.loading(false);
						extractScript(html);
					}
				})
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}

function ativo(codproduto, ativo){
	$.ajax({
		url: "../ajax/prodconcorrente_ativo.php",
		data: ({
			codproduto: codproduto,
			ativo: ativo
		}),
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	})
	$("[type=checkbox][codproduto=" + codproduto + "]").attr("ativo", ativo);
	if(ativo === "N"){
		$("[type=checkbox][codproduto=" + codproduto + "]").removeAttr("checked");
	}else{
		$("[type=checkbox][codproduto=" + codproduto + "]").checked(true);
	}
}