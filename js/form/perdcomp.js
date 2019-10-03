
function gerar() {
    if($("#codestabelec").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe o estabelecimento.",
			focusOnClose: $("#codestabelec")
		});
		return false;
	}else if($("#dtmovtoini").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a data inicial.",
			focusOnClose: $("#dtmovtoini")
		});
		return false;
	}else if($("#dtmovtofim").val().length == 0){
		$.messageBox({
			type: "error",
			text: "Informe a data final.",
			focusOnClose: $("#dtmovtofim")
		});
		return false;
    }
    $.ajaxProgress({
		url: "../ajax/perdcomp_gerar.php",
		data: ({
			codestabelec: $("#codestabelec").val(),
			dtmovtoini: $("#dtmovtoini").val(),
			dtmovtofim: $("#dtmovtofim").val()			
		}),
		success: function(html){
			extractScript(html);
		}
	});
}