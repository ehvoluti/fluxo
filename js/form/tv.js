var filename;
$(document).bind("ready",function(){
	$("#file_img").bind("change", function(){
		if($("#codtv").val().length <= 0){
			filename = "temp";
		}else{
			filename = $("#codtv").val();
		}

		$(this).upload({
			filename: "../tv/img/" + filename + ".png",
			url: "../ajax/uploadtv.php",
			onComplete: function(){
				var d = new Date();
				$("#banner").css("background-image", "url('../tv/img/" + filename + ".png?" + d.getTime()) + "')";
			}
		});
	});
	$("#btnNovoProduto").disabled(true);
	ittv_limpar();

	$("#btnCadRetornar1").after("<input type=\"button\" style=\"margin-left:4px\" id=\"btnTestar\" value=\"Testar\" onclick=\"tv_testar()\" alt=\"Testar\" title=\"Testar\">");

	$("#linhaVazia").on("click", function(){
		if($(this).is(":checked")){
			$("#ittv_codproduto").val('');
			$("#ittv_codproduto").attr('readonly', true);
			$("#desc_ittv_codproduto").val('');
			$("#desc_ittv_codproduto").attr('readonly',true);
			$("#ittv_div").val('');
			$("#ittv_div").attr('readonly', true);
		}else{			
			$("#ittv_codproduto").attr('readonly', false);
			$("#desc_ittv_codproduto").attr('readonly', false);
			$("#ittv_div").attr('readonly', false);
		}
	});
});


$.cadastro.after.inserir = function(){
	$("#btnNovoProduto").disabled(false);
	$("#codprodutofilho").disabled(true);
//	explosaoauto();
	ittv_limpar();
}

$.cadastro.after.alterar = function(){
	$("#btnNovoProduto").disabled(false);
	$("#gridittv img").show();

}

$.cadastro.after.salvar = function(){
	$("#file_img").trigger("change");
}

$.cadastro.after.limpar = function(){
	$("#banner").html("Selecionar imagem");
	$("#banner").css("background-image", "none");
	$("#btnNovoProduto").disabled(true);
}

$.cadastro.after.carregar = function(){
	$("#banner").html("");
	var d = new Date();
	$("#banner").css("background-image", "url('../tv/img/" + $("#codtv").val() + ".png?" + d.getTime()) + "')";
	ittv_desenhar();
		$("#pick_corfonte").val($("#corfonte").val());
		$("#pick_corfonteoferta").val($("#corfonteoferta").val());
	
}

$.cadastro.after.retornar = function(){
	ittv_limpar();
}

$.cadastro.after.deletar = function(){
	ittv_limpar();
}

$.cadastro.before.cancelar = function(){
	$("#btnNovoProduto").disabled(true);

	if($.cadastro.status() == 2){
		ittv_limpar();
	}
	return true;
}


function ittv_incluir(){
	$.messageBox({
		type:"info",
		title:"Informe os dados do novo produto filho:",
		text:$("#divittv"),
		buttons:({
			"Incluir":function(){
				if($("#ittv_codproduto").val().length == 0 && !$("#linhaVazia").is(":checked")){
					$.messageBox("close");
					$.messageBox({
						type:"error",
						text:"Informe o produto.",
						afterClose:function(){
							ittv_incluir();
						}
					});
				}else{
					$.messageBox("close");
					$.ajax({
						url:"../ajax/tv_ittv_incluir.php",
						data:({
							codproduto:$("#ittv_codproduto").val(),
							div:$("#ittv_div").val(),
							linhaVazia:$("#linhaVazia").is(":checked")
						}),
						dataType:"html",
						success:function(html){
							extractScript(html);
						}
					});
				}
			},
			"Cancelar":function(){
				$.messageBox("close");
			}
		})
	});
}

function ittv_limpar(){
	$.ajax({
		url:"../ajax/tv_ittv_limpar.php",
		success:function(){
			ittv_desenhar();
		}
	});
}

function ittv_excluir(codproduto){
	$.messageBox({
		type:"info",
		text:"Tem certeza que quer excluir o produto da tv? ",
		buttons:({
			"Sim":function(){
				$.ajax({
				url:"../ajax/tv_ittv_excluir.php",
				data:({
					codittv:codproduto
				}),
				dataType:"html",
					success:function(){
						ittv_desenhar();
					}
				});
				$.messageBox("close");
			},
			"N\u00E3o":function(){
				$.messageBox("close");
			}
		}),
		afterClose:function(){
			ittv_desenhar();
		}
	});
}

function ittv_desenhar(){
	$.ajax({
		async:true,
		url:"../ajax/tv_ittv_desenhar.php",
		data: {
			codestabelec: $("#codestabelec").val()
		},
		success:function(html){
			$("#gridittv").html(html);
			$.gear();
			if(!in_array($.cadastro.status(),[2,3])){
				$("#gridittv input:text").disabled(true);
				$("#gridittv img").hide();
			}
			$("#gridittv input[coluna]").bind("change",function(){
				$.ajax({
					async:false,
					url:"../ajax/tv_ittv_alterar.php",
					data:{
						codproduto:$(this).attr("codproduto"),
						coluna:$(this).attr("coluna"),
						valor:$(this).val()
					},
					success:function(html){
						extractScript(html);
					}
				})
			});
		}
	});
}

function tv_testar(){
	let grupo = $("#grupo").val()
	window.open("../tv?"+grupo,"target='_blank'");
}