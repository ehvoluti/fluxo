
		/* ******
		 G E R A L
		 ****** */
		window.name = "Index";
		window.onbeforeunload = function(){
			return "Tem certeza que deseja encerrar o sistema?";
		};

		/* ******************************
		 M E N U   D E   N A V E G A C A O
		 ****************************** */
		var arrBorder = [["#D3D3D3", "#999999"], ["#C3C3C3", "#888888"], ["#B3B3B3", "#777777"]];
		var arrColor = [["#F3F3FF", "#E3E3EE"], ["#E3E3EE", "#D3D3DD"], ["#D3D3DD", "#C3C3CC"]];
		var colorNone = "RGB(255,255,255)";
		$(document).ready(function(){
			proc_oferta();
			$("div[accordion]").accordion({
				active: false,
				autoHeight: true,
				collapsible: true
			});

			$("div[accordion]").css("height", "auto");
			$("div[accordion]").not(":first").css({
				"padding": "5px",
				"padding-top": "0px"
			});

			$("h3:has(a[idtable])").unbind("keypress keydown keyup");

			var div = $("h3").next();
			for(var i = 0; i < div.length; i++){
				if($(div[i]).html().length === 0){
					var cont = $(div[i]).prev().contents();
					cont.filter("span").remove();
					$(div[i]).prev().unbind("click");
				}
			}

			$("a[idtable]").bind("click", function(){
				var id = $(this).attr("idtable");
				openProgram(id);
			});

			$("#txt_pesquisa").bind("keypress", function(e){
				if(e.keyCode === 13){
					openProgram("Pesquisa", "p=" + $("#txt_pesquisa").val());
				}
			});

			notificacao_servico();
			notificacao_verificar();
		});


		/* *********************************
		 C O N T E U D O   D A   P A G I N A
		 ********************************* */
		$(document).bind("ready", function(){
			if(mensagemlogin.length > 0){
				$.messageBox({
					type: "info",
					text: mensagemlogin
				});
			}

			// Movimentacao do menu
			var menu_move = true;
			$("#menu").bind("mousemove", function(e){
				if(menu_move){
					if($(window).height() - e.pageY < 60){
						menu_move = false;
						$("#menu_overflow").animate({"scrollTop": ($("#menu_cont").height() - $(window).height())}, "slow", function(){
							menu_move = true;
						});
					}else if(event.pageY < 60){
						menu_move = false;
						$("#menu_overflow").animate({"scrollTop": 0}, "slow", function(){
							menu_move = true;
						});
					}
				}
			});

			usuarioLogado();
			verificarMensagem();
			enviarErros();
			$(window).bind("resize", function(){
				atualizaFavoritos();
				atualizaAcessados();
			}).resize();
			$("#conteudo h3").attr({
				alt: "Clique aqui para atualizar a lista",
				title: "Clique aqui para atualizar a lista"
			});
		});

		// Previne da tela correr para baixo selecionando o conteudo da pagina
		$(window).bind("scroll", function(){
			this.scrollTo(0, 0);
		});

		var ajaxUsuarioLogado = null;
		function usuarioLogado(){
			//if(param_sistema_loginunico == "S" && ajaxUsuarioLogado == null){
			if(ajaxUsuarioLogado === null){
				ajaxUsuarioLogado = $.ajax({
					url: "../ajax/inicial_usuariologado.php",
					complete: function(){
						ajaxUsuarioLogado = null;
					},
					success: function(){
						setTimeout("usuarioLogado()", 5000);
					}
				});
			}
		}

		var ajaxMensagem = null;
		function verificarMensagem(){
			if(ajaxMensagem == null){
				ajaxMensagem = $.ajax({
					url: "../ajax/inicial_mensagem.php",
					success: function(html){
						ajaxMensagem = null;
						setTimeout("verificarMensagem()", (1000 * 60 * 60));
					}
				});
			}
		}

		var ajaxErros = null;
		function enviarErros(){
			if(ajaxErros == null){
				ajaxErros = $.ajax({
					url: "../ajax/inicial_enviarerros.php",
					success: function(html){
						ajaxErros = null;
//					setTimeout("enviarErros()",150000);
					}
				});
			}
		}

		var ajaxFavoritos = null;
		function atualizaFavoritos(){
			if(ajaxFavoritos != null){
				ajaxFavoritos.abort();
			}
			$("#divFavoritos").html("<center><label>Carregando...</label></center>");
			ajaxFavoritos = $.ajax({
				url: "../ajax/inicial_favoritos.php",
				data: ({
					height: $("body").height()
				}),
				success: function(html){
					ajaxFavoritos = null;
					$("#divFavoritos").html(html);
				}
			});
		}

		var ajaxAcessados = null;
		function atualizaAcessados(){
			if(ajaxAcessados != null){
				ajaxAcessados.abort();
			}
			$("#divAcessados").html("<center><label>Carregando...</label></center>");
			ajaxAcessados = $.ajax({
				url: "../ajax/inicial_acessados.php",
				data: ({
					height: $("body").height()
				}),
				success: function(html){
					ajaxAcessados = null;
					$("#divAcessados").html(html);
				}
			});
		}

		function removerFavoritos(idtable){
			$.ajax({
				url: "../ajax/favoritos.php",
				data: ({
					action: "delete",
					idtable: idtable
				}),
				dataType: "html",
				success: function(html){
					if(html == "T"){
						atualizaFavoritos();
					}else{
						$.messageBox({
							type: "error",
							text: "Houve uma falha ao tentar remover p&aacute;gina dos favoritos."
						});
					}
				}
			});
		}

		function notificacao_ciencia(){
			$.loading(true);
			$.ajax({
				url: "../ajax/inicial_notificacao_ciencia.php",
				data: {
					idnotificacao: $("#notificacao_idnotificacao").val()
				},
				success: function(result){
					$.loading(false);
					extractScript(result);
				}
			});
		}

		function notificacao_servico(){
			$.ajax({
				url: "../ajax/inicial_notificacao_servico.php",
				success: function(){
					setTimeout("notificacao_servico()", (1000 * 60 * 5)) // Executa o servico a cada 5 minutos
				}
			});
		}

		function notificacao_verificar(){
			$.ajax({
				url: "../ajax/inicial_notificacao_verificar.php",
				success: function(result){
					extractScript(result);
					setTimeout("notificacao_verificar()", (1000 * 30)) // Executa o servico a cada 30 segundos
				}
			});
		}
