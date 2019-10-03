var filtro_codproduto = "";

function mix_buscar(){
//	if($("#codproduto").val().length == 0 && $("#coddepto").val().length == 0 && $("#codgrupo").val().length == 0 && $("#codsubgrupo").val().length == 0 && $("#codfornec").val().length == 0 && $("#foralinha").val().length == 0){
//		$.messageBox({
//			type:"alert",
//			text:"Preencha algum filtro.",
//			onclose:function(){
//				$("#qtdatacado").focus();
//			}
//		});
//		return false
//	}

	var arr_codestabelec = $("#arr_codestabelec input:checkbox:checked").map(function(){
		return $(this).attr("key");
	}).get();

	$.loading(true);

	$.ajax({
		url: "../ajax/mixproduto_buscar.php",
		data: ({
			arr_codestabelec: arr_codestabelec,
			codproduto: $("#codproduto").val(),
			coddepto: $("#coddepto").val(),
			codgrupo: $("#codgrupo").val(),
			codsubgrupo: $("#codsubgrupo").val(),
			codfornec: $("#codfornec").val(),
			foralinha: $("#foralinha").val(),
			codmarca: $("#codmarca").val(),
			codfamilia: $("#codfamilia").val(),
			datasemmovto1: $("#datasemmovto1").val(),
			datasemmovto2: $("#datasemmovto2").val(),
			datasemvenda1: $("#datasemvenda1").val(),
			datasemvenda2: $("#datasemvenda2").val(),
			limite: $("#limite").val()

		}),
		success: function(html){
			$.loading(false);
			$("#div_mixproduto").html(html);
			if(!$("#div_mixproduto,.div_btnmix").is(":visible")){
				$("#div_mixproduto,.div_btnmix").animate({
					"height": "toggle"
				}, "slow").css("overflow", "auto");
			}
			$("#hint_mix").hint_show();
			check_estabelecimento2();
		}
	});
}

function check_estabelecimento2(){
	$("[box=estabelecimento] :checkbox[codproduto]").bind("change", function(){
		var codproduto = $(this).attr("codproduto");

		$.ajax({
			url: "../ajax/mixproduto_alterar.php",
			data: ({
				codestabelec: $(this).attr("codestabelec"),
				codproduto: $(this).attr("codproduto"),
				disponivel: ($(this).checked() ? "S" : "N")
			}),
			success: function(html){
				extractScript(html);
			}
		});

		var all_checked = true;
		var aux = " ";

		$(this).parents("tbody").first().find("tr input").each(function(){
			if(aux != " " && aux != ($(this).checked() ? "S" : "N")){
				all_checked = false;
			}
			aux = $(this).checked() ? "S" : "N";
			if(aux == "N"){
				$("#div_mixproduto :checkbox[codproduto=" + codproduto + "]").first().checked(false);
			}
		});

		if(all_checked){
			$("#div_mixproduto :checkbox[codproduto=" + codproduto + "]").checked($(this).checked());
			$("#div_mixproduto :checkbox[codproduto=" + codproduto + "]:nth(1)").parents("table").first().find("input").first().checked($(this).checked());
		}
	});
}

function check_all(e){
	if($(e).checked()){
		$("#div_mixproduto :checkbox:not(:checked)").checked($(e).checked());
	}else{
		$("#div_mixproduto :checkbox:checked").checked($(e).checked());
	}
}

function check_all_estab(e, codproduto){
	if($(e).checked()){
		$("[box=estabelecimento] :checkbox:not(:checked)").attr("codproduto", codproduto).checked($(e).checked()).trigger("change");
		$("#div_mixproduto :checkbox[codproduto=" + codproduto + "]").checked($(e).checked());
	}else{
		$("[box=estabelecimento] :checkbox:checked").attr("codproduto", codproduto).checked($(e).checked()).trigger("change");
		$("#div_mixproduto :checkbox[codproduto=" + codproduto + "]").checked($(e).checked());
	}
}

function check_estabelecimento(){
	$("#div_mixproduto table tbody tr td:nth-child(3) span").click(function(){
		if($(this).parent().find("#arr_estab").length == 0){
			$("#arr_estab").remove();

			$(this).parent().append($("<div id=\"arr_estab\"></div>"));

			$.ajax({
				url: "../ajax/mixproduto_estabelecimento.php",
				data: ({
					codproduto: $(this).parents("tr").first().find("td:nth-child(2)").text(),
				}),
				success: function(html){
					$("#arr_estab").html(html);
					$("#arr_estab :checkbox[codproduto]").bind("change", function(){
						var codproduto = $(this).attr("codproduto");

						$.ajax({
							url: "../ajax/mixproduto_alterar.php",
							data: ({
								codestabelec: $(this).attr("codestabelec"),
								codproduto: $(this).attr("codproduto"),
								disponivel: ($(this).checked() ? "S" : "N")
							}),
							success: function(html){
								extractScript(html);
							}
						});

						var all_checked = true;
						var aux = " ";

						$(this).parents("tbody").first().find("tr input").each(function(){
							if(aux != " " && aux != ($(this).checked() ? "S" : "N")){
								all_checked = false;
							}
							aux = $(this).checked() ? "S" : "N";
							if(aux == "N"){
								$("#div_mixproduto :checkbox[codproduto=" + codproduto + "]").first().checked(false);
							}
						});

						if(all_checked){
							$("#div_mixproduto :checkbox[codproduto=" + codproduto + "]").checked($(this).checked());
							$("#div_mixproduto :checkbox[codproduto=" + codproduto + "]:nth(1)").parents("table").first().find("input").first().checked($(this).checked());
						}
					});
					$("#arr_estab").slideDown("slow");
				}
			});
		}else{
			$("#arr_estab").slideUp("slow", function(){
				$("#arr_estab").remove();
			});
		}
	});
}

function mix(){
	$.modalWindow({
		closeButton: true,
		content: $("#mixestab"),
		title: "Escolha os estabelecimentos",
		width: "520px"
	});
}

function mix_alterar(){
	var arr_codproduto = [];
	var arr_mixestabelec_s = [];
	var arr_mixestabelec_n = [];

	$("#div_mixproduto :checkbox:checked").each(function(){
		arr_codproduto.push($(this).attr("codproduto"));
	});

	$("#grid_mixestabelec :checkbox:checked").each(function(){
		arr_mixestabelec_s.push($(this).attr("codestabelec"));
	});

	$("#grid_mixestabelec :checkbox:not(:checked)").each(function(){
		arr_mixestabelec_n.push($(this).attr("codestabelec"));
	});

	$.loading(true);
	$.ajax({
		url: "../ajax/mixproduto_alterar.php",
		data: ({
			arr_codproduto: arr_codproduto,
			arr_mixestabelec_s: arr_mixestabelec_s,
			arr_mixestabelec_n: arr_mixestabelec_n
		}),
		method: "POST",
		success: function(html){
			extractScript(html);
			mix_buscar();
			$.loading(false);
		}
	});
}

function mix_produto_alterar(checked,codestabelec,codproduto){
	$.loading(true);
	$.ajax({
		url: "../ajax/mixproduto_alterar.php",
		data: ({
			tipo: "produto",
			checked:checked ? "S" : "N",
			codestabelec: codestabelec,
			codproduto:codproduto
		}),
		success: function(html){
			extractScript(html);
			$.loading(false);
			$("[linha="+codproduto+"]").checked($("[codproduto="+codproduto+"]").length==$("[codproduto="+codproduto+"]:checked").length);
			var bool = ($("[codestabelec="+codestabelec+"]").length - 2) == $("[codestabelec="+codestabelec+"]:checked").length;
//			alert(($("[codestabelec="+codestabelec+"]").length - 2) + "zzz" + $("[codestabelec="+codestabelec+"]:checked").length);
			$("[coluna][codestabelec="+codestabelec+"]").checked(bool);

			$("#div_mixproduto :checkbox:first").checked(($("#div_mixproduto [coluna]:not(:checked),#div_mixproduto [linha]:not(:checked)").length == 0));
		}
	});
}

function mix_estabelecimento_alterar(checked,codproduto){
	$.loading(true);
	$.ajax({
		url: "../ajax/mixproduto_alterar.php",
		data: ({
			tipo: "estabelecimento",
			checked:checked ? "S" : "N",
			codproduto:codproduto,
		}),
		success: function(html){
			extractScript(html);
			$.loading(false);
			$("#div_mixproduto :checkbox:first").checked(($("#div_mixproduto [coluna]:not(:checked),#div_mixproduto [linha]:not(:checked)").length == 0));
		}
	});
}

function mix_coluna_alterar(checked,codestabelec){
	$.loading(true);
	$.ajax({
		url: "../ajax/mixproduto_alterar.php",
		data: ({
			tipo: "coluna",
			checked:checked ? "S" : "N",
			codestabelec: codestabelec,
			filtro_codproduto: filtro_codproduto

		}),
		success: function(html){
			extractScript(html);
			$.loading(false);
			$("#div_mixproduto :checkbox:first").checked(($("#div_mixproduto [coluna]:not(:checked),#div_mixproduto [linha]:not(:checked)").length == 0));
		}
	});
}

function mix_total_alterar(checked){
	$.loading(true);
	$.ajax({
		url: "../ajax/mixproduto_alterar.php",
		data: ({
			tipo: "total",
			checked:checked ? "S" : "N",
			filtro_codproduto: filtro_codproduto
		}),
		success: function(html){
			extractScript(html);
			$.loading(false);
		}
	});
}