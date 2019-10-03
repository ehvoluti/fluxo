$(document).bind("ready", function(){
	$("#btnCadClonar").before("<input id=\"btnCadLayout\" style=\"width: 120px\" type=\"button\" value=\"Carga Coletor\" onclick=\"abrir_geracaolayout();\">");
	if(scanmob == "S"){
		$("#btnCadClonar").before("<input type=\"button\" id=\"btnCadScanMob\" style=\"width:140px\" value=\"Enviar para ScanMob\" onClick=\"scanmob_enviar()\" alt=\"Enviar para o ScanMob\" title=\"Enviar para o ScanMob\">");
	}
	$("#btnCadClonar").before("<input type=\"button\" id=\"btnCadAtualizarInv\" style=\"width:140px\" value=\"Atualizar Invent&aacute;rio\" onClick=\"openProgram('Digitacao','codinventario='+$('#codinventario').val())\" alt=\"Atualizar Inventario\" title=\"Atualizar Inventario\">");
	$("#btnCadClonar").before("<input type=\"button\" id=\"btnCadCongInv\" value=\"Congelar\" onClick=\"congelar()\" alt=\"Congelar Inventario\" title=\"Congelar Inventario\">");
	$("#btnCadClonar").before("<input type=\"button\" id=\"btnCadDescongInv\" value=\"Descongelar\" onClick=\"descongelar()\" alt=\"Descongelar Inventario\" title=\"Descongelar Inventario\">");
	$("#btnCadClonar").before("<input type=\"button\" id=\"btnCadImprimirInv\" value=\"Imprimir\" onClick=\"imprimir()\" alt=\"Imprimir Inventario\" title=\"Imprimir Inventario\">");
	$("#griditens").bind("scroll", function(){
		var outerHeight = parseInt($(this).height());
		var scrollPos = parseInt($(this).scrollTop());
		var innerHeight = parseInt($(this).children().filter("table").height());
		if(innerHeight - scrollPos - outerHeight < outerHeight){
			listaprodutos();
		}
	});
});

$.cadastro.before.salvar = function(){
	if($("#griditens tr").length <= 1){
		$.messageBox({
			type: "error",
			text: "Informe pelo menos um produto para efetuar o cadastro de invent&aacute;rio.",
			focusOnClose: $("#codfornec")
		});
		return false;
	}
	return true;
}

$.cadastro.after.limpar = function(){
	$("#fds_filtro").hide();
	listaprodutos(true);
}

$.cadastro.after.inserir = function(){
	$("#atualizado").disabled(true);
	$("#fds_filtro").show();
	$("#data").val(date);
	$("[multiple][gear]").multipleSelect("refresh");
}

$.cadastro.after.alterar = function(){
	$("#datacong,#atualizado").disabled(true);
	$("#fds_filtro").show();
}

$.cadastro.after.carregar = function(){
	var botoes1 = $("#btnCadAlterar,#btnCadExcluir,#btnCadCongInv,#btnCadLayout,#btnCadScanMob");
	var botoes2 = $("#btnCadAtualizarInv,#btnCadDescongInv,#btnCadScanMob");
	$("#btnCadClonar").hide();

	if($("#atualizado").val() == "S"){
		botoes1.hide();
		botoes2.hide();
	}else if($("#datacong").val().length > 0){
		botoes1.hide();
		botoes2.show();
	}else{
		botoes2.hide();
		botoes1.show();
	}
	buscarprodutos();
};

function buscarprodutos(){
	var novo = ($.cadastro.status() == 2 || $.cadastro.status() == 3);
	if(novo){
		var codestabelec = $("#codestabelec").val();
		var tipo = $("#tipofiltro").val();
		if(codestabelec.length == 0){
			$.messageBox({
				type: "error",
				text: "Informe o estabelecimento.",
				focusOnClose: $("#codestabelec")
			});
			return false;
		}
	}
	$.loading(true);
	$("#griditens").html("<label>Carregando...</label>");
	$.ajax({
		url: "../ajax/inventario_buscarprodutos.php",
		data: ({
			novo: (novo ? "S" : "N"),
			codinventario: $("#codinventario").val(),
			codestabelec: $("#codestabelec").val(),
			tipo: tipo,
			codfornec: $("#codfornec").val(),
			coddepto: $("#coddepto").val(),
			codgrupo: $("#codgrupo").val(),
			codsubgrupo: $("#codsubgrupo").val(),
			codmarca: $("#codmarca").val(),
			foralinha: $("#foralinha").val(),
			codfamilia: $("#codfamilia").val(),
			codlayout: $("#coletor_codlayout").val(),
			estoque: $("#estoque").val()
		}),
		dataType: "html",
		success: function(html){
			$.loading(false);
			extractScript(html);
			$("#coletor_codlayout").val("");
		}
	});
}

function coletordados(){
	$.modalWindow({
		title: "Filtro",
		content: $("#div_coletordados"),
		width: "400px",
		closeButton: true
	});
}

var ajax_lista = null;
function listaprodutos(limpar){
	limpar = (typeof (limpar) == "undefined" ? false : limpar);
	if(ajax_lista == null){
		var offset = $("#griditens tr").length - 1;
		if(offset == -1){
			$.loading(true);
		}
		ajax_lista = $.ajax({
			url: "../ajax/inventario_listaprodutos.php",
			data: ({
				novo: ($("#griditens tr.row").length == 0 ? "S" : "N"), //($.cadastro.status() == 2 || $.cadastro.status() == 3 ? "S" : "N"),
				limpar: (limpar ? "S" : "N"),
				congelado: ($("#datacong").val().length > 0 ? "S" : "N"),
				query: $("#query").val(),
				offset: offset
			}),
			dataType: "html",
			success: function(html){
				$.loading(false);
				if(limpar || $("#griditens table").length == 0){
					$("#griditens").html(html);
				}else{
					$("#griditens table:first").append(html);
				}
				ajax_lista = null;
			}
		});
	}
}

function congelar(){
	$.messageBox({
		type: "info",
		text: "Tem certeza que deseja congelar o invent&aacute;rio?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				$.loading(true);
				$.ajax({
					url: "../ajax/inventario_congelar.php",
					data: ({
						codinventario: $("#codinventario").val()
					}),
					dataType: "html",
					success: function(html){
						$.loading(false);
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

function descongelar(){
	$.messageBox({
		type: "info",
		text: "Tem certeza que deseja descongelar o invent&aacute;rio?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				$.ajaxProgress({
					url: "../ajax/inventario_descongelar.php",
					data: ({
						codinventario: $("#codinventario").val()
					}),
					dataType: "html",
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

function imprimir(){
	$.messageBox({
		type: "info",
		text: "Tem certeza que deseja imprimir o invent&aacute;rio?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				window.open("../form/inventario_imprimir.php?codinventario=" + $("#codinventario").val());
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}

function recarregar_inventario(){ // funcao usada no arquivo "../ajax/inventario_congelar.php"
	var codinventario = $("#codinventario").val();
	$.cadastro.limpar();
	$("#codinventario").val(codinventario);
	$.cadastro.pesquisar();
}

function scanmob_enviar(){
	$.loading(true);
	$.ajax({
		url: "../ajax/inventario_scanmob_enviar.php",
		data: ({
			codinventario: $("#codinventario").val()
		}),
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function abrir_geracaolayout(){
	openProgram('GeracaoLayout', 'codinventario=' + $("#codinventario").val());
}