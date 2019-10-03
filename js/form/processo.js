function executa_processo(processo){
	var currentUrl = window.location.href;
	var arrUrl = currentUrl.split("/");
	var formIndex = arrUrl.indexOf("form");
	var procUrl = arrUrl.splice(0, formIndex).join("/") + "/proc/" + processo.toLowerCase() + ".php?executa=S";
	window.open(procUrl, "Janela", "width=400, height=60, top=200, left=300, scrollbars=no, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no");
	$.messageBox({
		type: "success",
		title: "",
		text: "Seu processo foi inicicado em uma nova janela."
	});
}

function processo_alterar(){
	var arr_processo = {};
	$("#divprocesso [idprocesso]").each(function(){
		if(arr_processo[$(this).attr("idprocesso")] === undefined){
			arr_processo[$(this).attr("idprocesso")] = {};
		}
		arr_processo[$(this).attr("idprocesso")][$(this).attr("coluna")] = ($(this).is(":checkbox") ? ($(this).checked() ? "S" : "N") : $(this).val());
	});
	$.ajax({
		async: false,
		url: "../ajax/processo_alterar.php",
		data: ({
			arr_processo: JSON.stringify(arr_processo)
		}),
		success: function(html){
			extractScript(html);
		}
	});
}

function processo_parametro(idprocesso){
	var textarea = $("#divprocesso [idprocesso='" + idprocesso + "'][coluna='parametro']");

	$("#parametro").val($(textarea).val());
	$("#parametro_idprocesso").val(idprocesso);
	if(idprocesso == "LEITURAONLINE"){
		hint = "Atualmente o tempo espera esta funcionando apenas para o PDV Coral.";
	}else{
		hint = "";
	}

	$.modalWindow({
		content: $("#div_parametro"),
		title: "Parâmetros de <b>" + idprocesso + "</b>",
		width: "400px",
		hint: hint
	});
}

function processo_parametro_gravar(){
	var textarea = $("#divprocesso [idprocesso='" + $("#parametro_idprocesso").val() + "'][coluna='parametro']");
	$(textarea).val($("#parametro").val());
	$(textarea).next().html($("#parametro").val());
	$.modalWindow("close");
}