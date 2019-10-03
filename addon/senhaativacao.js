$.senhaAtivacao = function(){
	var win = $.modalWindow({
		height: "220px",
		padding: false,
		title: "Senha de Ativa&ccedil;&atilde;o",
		width: "450px",
		center: ({
			left: true,
			top: true
		}),
		position: ({
			left: "auto",
			top: "auto"
		})
	});

	$.ajax({
		url: "../form/senhaativacao.php",
		success: function(html){
			$(win).html(html).find("table td *").css({
				"font-size": "11pt"
			});
			$.gear();
			$("#senhaativacao").focus();
		}
	});
}