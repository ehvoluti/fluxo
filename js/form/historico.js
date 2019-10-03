function filtrar(){
	if($("#dataini").val().length == 0 || $("#datafim").val().length == 0){
		$.messageBox({
			type: "error",
			title: "",
			text: "Informe a data."
		});
		return false;
	}

	$.loading(true);
	$.ajax({
		url: "../ajax/historico_filtro.php",
		data: ({
			login: $("#login").val(),
			dataini: $("#dataini").val(),
			datafim: $("#datafim").val(),
			tabela: $("#tabela").val(),
			chave: $("#chave").val(),
			alteracao: $("#alteracao").val(),
			codestabelec: $("#codestabelec").val(),
			tipo: $("#tipo").val()
		}),
		success: function(html){
			$.loading(false);
			$("#grd_historico").html(html);
			if($("#div_hidden:not(:visible)").length > 0){
				$("#div_hidden").animate({
					height: "toggle"
				}, "slow");
			}
			$("#div_historico").focus();
		}
	});
}

function alteracao(nome, tabela, chave, data, hora, alteracao){
	$("#bh_nome").html(nome);
	$("#bh_tabela").html(tabela);
	$("#bh_chave").html(chave);
	$("#bh_data").html(data);
	$("#bh_hora").html(hora);
	$("#bh_alteracao").html(alteracao);
	$.modalWindow({
		title: "Hist&oacute;rico de Altera&ccedil;&atilde;o",
		content: "#box_historico",
		width: "600px"
	});
}