var offset_menssagem = 0;

$(document).bind("ready", function(){
	$.screenMenu.create();
});

$.screenMenu = {
	create: function(){
		if($("#mainForm").length === 0){
			$("#screenmenu").remove();
		}

		$("#screenmenu-inicial").bind("click", function(){
			window.open("", indexWindow.name);
		});

		$("#screenmenu-favoritos").bind("click", function(){
			$.ajax({
				url: "../ajax/favoritos.php",
				data: ({
					action: "save",
					idtable: idtable
				}),
				success: function(html){
					if(html === "T"){
						$.messageBox({
							type: "success",
							text: "P&aacute;gina adicionada aos favoritos com sucesso!",
						});
					}else{
						$.messageBox({
							type: "error",
							text: "Houve uma falha ao tentar adicionar a p&aacute;gina aos favoritos."
						});
					}
				}
			});
		});

		$("#screenmenu-calculadora").bind("click", function(){
			$.calculadora(true);
		});

		$("#screenmenu-mensagem").bind("click", function(){
			abrir_mensagem();
		});

		// Não consegui descobrir porque cria o bind duas vezes ai eu removo uma antes de crialo
		$("#screenmenu-ajuda").unbind();
		$("#screenmenu-ajuda").bind("click", function(){
			$.loading(true);
			$.ajax({
				async: false,
				url: "../ajax/help_verificararquivos.php",
				data: ({
					idtable: idtable
				}),
				complete: function(){
					$.loading(false);
				},
				success: function(html){
					extractScript(html);
					$.modalWindow({
						content: $("#modal-ajuda"),
						width: "500px",
						height: "auto"
					});
				}
			});
		});

		$("#screenmenu-fechar").bind("click", function(){
			window.close();
		});

		$("#modal-caixamensagem-dtcriacao, #modal-caixamensagem-codtipomensagem").bind("change", function(){
			offset_menssagem = 0;
			caixa_mensagem();
		});

		$("#screenmenu-mensagem-balao > div").bind("click", function(){
			$("#screenmenu-mensagem").trigger("click");
		});

		$("#modal-ajuda-corpo ws-button").bind("click", function(){
			$.modalWindow("close");
		});

		$.screenMenu.mensagem.verificar();
	},
	mensagem: {
		balao: function(exibir){
			exibir = (exibir === undefined ? true : exibir);
			if(exibir && $("[__background]").length === 0){
				if(!$("#screenmenu-mensagem-balao").is(":visible")){
					$("#screenmenu-mensagem-balao").fadeIn("fast");
				}
			}else{
				if($("#screenmenu-mensagem-balao").is(":visible")){
					$("#screenmenu-mensagem-balao").stop().fadeOut("fast");
				}
			}
		},
		verificar: function(){
			$.ajax({
				url: "../ajax/caixamensagem_verificar.php",
				success: function(html){
					extractScript(html);
					setTimeout("$.screenMenu.mensagem.verificar()", (5 * 60 * 1000)); // minutos * segundos * milisegundos
				}
			});
		}
	}
};

function caixa_mensagem(){
	$.ajax({
		url: "../ajax/caixamensagem_carregar.php",
		data: ({
			dtcriacao: $("#modal-caixamensagem-dtcriacao").val(),
			texto: $("#modal-caixamensagem-texto").val(),
			codtipomensagem: $("#modal-caixamensagem-codtipomensagem").val(),
			offset: offset_menssagem
		}),
		success: function(html){
			if(offset_menssagem > 0){
				$("#modal-caixamensagem-grade").html($("#modal-caixamensagem-grade").html()+html);
			}else{
				$("#modal-caixamensagem-grade").html(html);
			}
			$("#count_mensagem").html($("tr[codmensagem]").length);
		}
	});
}

function abrir_mensagem(){
	$("#modal-caixamensagem-codtipomensagem").disabled(false)
	$("#modal-caixamensagem-texto").off();
	$("#modal-caixamensagem-grade").off();

	offset_menssagem = 0;
	$("#modal-caixamensagem-grade").html("");
	$.screenMenu.mensagem.balao(false);
	caixa_mensagem();
	$("#modal-caixamensagem-grade").bind("scroll", function() {
		if($(this).scrollTop() + $(this).innerHeight() >= this.scrollHeight){
			caixa_mensagem();
		}
	});
	$.modalWindow({
		closeButton: true,
		title: "Caixa de Mensagens",
		content: $("#modal-caixamensagem"),
		height: "500px",
		width: "600px",
		center: ({
			left: true,
			top: true
		})
	});

	$("#modal-caixamensagem-texto").keyup(function(e){
		if(e.keyCode == 13){
			offset_menssagem = 0;
			caixa_mensagem();
		}
	});
}