$(document).bind("ready",function(){
		buscar_ultimo();
		produto_limpar();
		$("#codproduto").bind("keypress",function(e){
			if(e.keyCode == 13){
				$("#quantidade").select();
			}
		});
		$("#quantidade").bind("keypress",function(e){
			if(e.keyCode == 13){
				produto_incluir();
			}
		});
		$("#dtmovto").val(data_atual);

		$("#btnColetorDados").bind("change", function(){
			$(this).upload({
				filename: "../temp/upload/coletor.txt",
				onComplete: function(){
					coletor_dados();
				}
			});
		});
	});

	function verificar_estabelecimento(){
		if($("#codestabelec").val().length == 0){
			$.messageBox({
				type: "error",
				text: "Informe o estabelecimento.",
				focusOnClose: $("#codestabelec")
			});
			return false;
		}
		return true;
	}

	function buscar_ultimo(){
		$.ajax({
			async:false,
			url:"../ajax/movimentolote_buscarultimo.php",
			success:function(html){
				extractScript(html);
			}
		});
	}

	function coletor_dados(){
		if($("#codestabelecorig").val().length == 0){
			$.messageBox({
				type:"error",
				text:"Informe o estabelecimento origem.",
				focusOnClose:$("#codestabelecorig")
			});
			return false;
		}
        if($("#codestabelecdest").val().length == 0){
			$.messageBox({
				type:"error",
				text:"Informe o estabelecimento destino.",
				focusOnClose:$("#codestabelecdest")
			});
			return false;
		}
        if($("#codestabelecorig").val() == $("#codestabelecdest").val()){
            $.messageBox({
				type:"error",
				text:"O estabelecimento origem deve ser diferente do estabelecimento destino.",
				focusOnClose:$("#codestabelecdest")
			});
			return false;
        }
		if($("#tipoembal").val().length == 0){
			$.messageBox({
				type:"error",
				text:"Informe o tipo de embalagem.",
				focusOnClose:$("#tipoembal")
			});
			return false;
		}
		$.messageBox({
			type:"info",
			text:$("#div_coletordados"),
			buttons:({
				"Importar":function(){
					var infcoletor = $("#ul_infcoletordados input:radio:checked").val();
					if(infcoletor == 1){
						produto_limpar(false);
					}
					$.messageBox("close");
					$.ajaxProgress({
						url:"../ajax/transferenciarapida_coletordados.php",
						data:({
							codestabelec:$("#codestabelecorig").val(),
							tipoembal:$("#tipoembal").val()
						}),
						success:function(html){
							extractScript(html);
						}
					});
				},
				"Cancelar":function(){
					$.messageBox("close");
				}
			})
		});
	}

	function gravar(){
		$.messageBox({
			type:"info",
			text:"Tem certeza que deseja finalizar a transfer&ecirc;nica agora?",
			buttons:({
				"Sim":function(){
					$.messageBox("close");
					if($("#codestabelecorig").val().length == 0){
						$.messageBox({
							type:"error",
							text:"Informe o estabelecimento origem.",
							focusOnClose:$("#codestabelecorig")
						});
						return false;
					}
					if($("#codestabelecdest").val().length == 0){
						$.messageBox({
							type:"error",
							text:"Informe o estabelecimento destino.",
							focusOnClose:$("#codestabelecdest")
						});
						return false;
					}
					if($("#tipoembal").val().length == 0){
						$.messageBox({
							type:"error",
							text:"Informe o tipo de embalagem.",
							focusOnClose:$("#tipoembal")
						});
						return false;
					}
					$.ajaxProgress({
						url:"../ajax/transferenciarapida_gravar.php",
						data:({
							codestabelecorig:$("#codestabelecorig").val(),
							codestabelecdest:$("#codestabelecdest").val(),
							codtpdocto:$("#codtpdocto").val(),
							tipoembal:$("#tipoembal").val(),
							dtmovto:$("#dtmovto").val()
						}),
						success:function(html){
							extractScript(html);
						}
					});
				},
				"N\u00E3o":function(){
					$.messageBox("close");
				}
			})
		});
	}

    function imprimir_lote(codlote){
		window.open("../form/movimentolote_imprimir.php?codlote=" + codlote + "&codestabelecorig="+$("#codestabelecorig").val()+"&codestabelecdest="+$("#codestabelecdest").val());
	}

	function produto_desenhar(){
		$.ajax({
			url:"../ajax/transferenciarapida_desenhar.php",
			success:function(html){
				$("#grd_produto").html(html);
				$("#grd_produto input[codproduto][coluna]").bind("change",function(){
					$.ajax({
						async:false,
						url:"../ajax/transferenciarapida_alterarvalor.php",
						data:({
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
	}

	function produto_incluir(){
		if($("#tipoembal").val().length == 0){
			$.messageBox({
				type:"error",
				text:"Informe o tipo de embalagem.",
				focusOnClose:$("#tipoembal")
			});
			return false;
		}
		if($("#codproduto").val().length == 0){
			$.messageBox({
				type:"error",
				text:"Informe o produto.",
				focusOnClose:$("#codproduto")
			});
			return false;
		}
		if(!(parseFloat($("#quantidade").val().replace(",",".")) > 0)){
			$.messageBox({
				type:"error",
				text:"A quantidade do produto deve ser maior que zero.",
				focusOnClose:$("#quantidade")
			});
			return false;
		}
		$.loading(true);
		$.ajax({
			url:"../ajax/transferenciarapida_incluir.php",
			data:({
				tipoembal:$("#tipoembal").val(),
				codproduto:$("#codproduto").val(),
				quantidade:$("#quantidade").val(),
				codestabelecorig:$("#codestabelecorig").val()
			}),
			success:function(html){
				$.loading(false);
				extractScript(html);
			}
		});
	}

	function produto_limpar(async){
		async = (typeof(async) == "undefined" ? true : async);
		$.ajax({
			async:async,
			url:"../ajax/transferenciarapida_limpar.php",
			success:function(html){
				extractScript(html);
			}
		});
	}

	function produto_remover(codproduto){
		$.messageBox({
			type:"info",
			text:"Tem certeza que deseja remover o produto da lista?",
			buttons:({
				"Sim":function(){
					$.messageBox("close");
					$.loading(true);
					$.ajax({
						url:"../ajax/transferenciarapida_remover.php",
						data:({
							codproduto:codproduto
						}),
						success:function(html){
							$.loading(false);
							extractScript(html);
						}
					});
				},
				"N\u00E3o":function(){
					$.messageBox("close");
				}
			})
		});
	}