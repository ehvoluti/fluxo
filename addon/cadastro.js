/* ********************************************************************************
 C O N T R O L A   T O D O S   O S   F O R M U L A R I O S   D E   C A D A S T R O S
 ******************************************************************************** */
var alterar = "N";
var totreg = 0;
$.cadastro = ({
	/* ********************************
	 V A R I A V E I S   D I V E R S A S
	 ******************************** */
	variavel: ({
		index_pesquisa: null, // Index do registro da grade de pesquisa
		count_pesquisa: null,
		posicao_pesquisa: 0, // Posicao em pixels da grade de pesquisa
		query: null, // Instrucao usada na pesquisa do cadastro
		ajax: { // Ajax dos metodos
			salvar: null
		}
	}),
	/* ****************************************************
	 V A R I A V E I S   Q U E   A R M A Z E N A M   A J A X
	 **************************************************** */
	ajax: ({
		pesquisar_grade: null // Usado para carregar a grade de pesquisa
	}),
	/* **********
	 A L T E R A R
	 ********** */
	alterar: function(){
		alterar = "S";
		if(!$.cadastro.before.alterar()){
			return false;
		}
		$.cadastro.status(3);
		$("#mainForm").focusFirst();
		$.cadastro.after.alterar();
	},
	/* ******************************
	 R E G I S T R O   A N T E R I O R
	 ****************************** */
	anterior: function(){
		if($.cadastro.variavel.index_pesquisa !== null){
			var tr = null;
			var index = $.cadastro.variavel.index_pesquisa - 1;
			while(!$(tr).is("tr")){
				tr = $("#divgradepesquisa tbody > *").get(index--);
				if(tr === undefined || tr === null){
					break;
				}
			}
			$(tr).click();
		}
	},
	/* ************
	 C A N C E L A R
	 ************ */
	cancelar: function(){
		if(!$.cadastro.before.cancelar()){
			return false;
		}
		var primarykey = {};
		var foundEmpty = false;
		$("[primarykey]").each(function(){
			if($(this).val().length > 0 && $(this).disabled()){
				primarykey[$(this).attr("id")] = $(this).val();
			}else{
				foundEmpty = true;
			}
		});
		$.cadastro.limpar();
		if(foundEmpty){
			$.cadastro.status(0);
			$("#mainForm").focusFirst();
		}else{
			for(key in primarykey){
				$("#" + key).val(primarykey[key]);
			}
			$.cadastro.pesquisar();
		}
		$.cadastro.after.cancelar();
	},
	/* ********
	 C L O N A R
	 ******** */
	clonar: function(){
		alterar = "S";
		if(!$.cadastro.before.clonar()){
			return false;
		}
		$.cadastro.status(2);
		if($("select[primarykey]").length < 2){
			$("[primarykey]").val("");
		}
		$("body").focusFirst();
		$.cadastro.after.inserir();
		$.cadastro.after.clonar();
	},
	/* **********
	 D E L E T A R
	 ********** */
	deletar: function(){
		$.messageBox({
			type: "info",
			text: "Tem certeza que deseja excluir o registro?",
			buttons: ({
				"Sim": function(){
					$.messageBox("close");
					if(!$.cadastro.before.deletar()){
						return false;
					}
					var data = $.cadastro.getData();
					$.extend(data, {action: "delete"});
					$.loading(true);
					$.ajax({
						url: "../ajax/cadastro_action.php",
						type: "POST",
						data: data,
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
	},
	/* ********************
	 I N C L U I R   N O V O
	 **********************/
	inserir: function(){
		alterar = "N";
		if(!$.cadastro.before.inserir()){
			return false;
		}
		$.cadastro.limpar();
		$.cadastro.status(2);
		$("[default]").each(function(){
			$(this).val($(this).attr("default"));
			filterchild($(this).attr("id"));
		}).description();
		$("#mainForm").focusFirst();
		$.cadastro.after.inserir();
	},
	/* **************************************
	 L I M P A R   C A M P O S   D A   T E L A
	 ************************************** */
	limpar: function(){
		if(!$.cadastro.before.limpar()){
			return false;
		}
		$("#mainForm").clearChildren();
		$("[alertValid]").remove();
		$("#lbl_ultalteracao").hide();
		$.cadastro.after.limpar();
	},
	/* *************************************************************************
	 V E R I F I C A   S E   D E V E    H A B I L I T A R   A   N A V E G A C A O
	 ************************************************************************* */
	verificar_navegacao: function(){
		$("#btnCadAnterior,#btnCadProximo").hide();
		if($.cadastro.status() == "1"){
			if($.cadastro.variavel.index_pesquisa == 0){
				$("#btnCadAnterior").hide();
			}else{
				$("#btnCadAnterior").show();
			}
			if(($.cadastro.variavel.index_pesquisa + 1) == $.cadastro.variavel.count_pesquisa){
				$("#btnCadProximo").hide();
			}else{
				$("#btnCadProximo").show();
			}
		}
	},
	/* ********************************************
	 I N I C I A   U M A   N O V A   P E S Q U I S A
	 ******************************************** */
	novapesquisa: function(){
		if(!$.cadastro.before.novapesquisa()){
			return false;
		}
		$.cadastro.limpar();
		$.cadastro.status(4);
		$("#mainForm").focusFirst();
		$.cadastro.after.novapesquisa();
	},
	/* **************************************************************************
	 P E S Q U I S A   A P A R T I R   D O S   V A L O R E S   I N F O R M A D O S
	 ************************************************************************** */
	pesquisar: function(){
		if($.cadastro.before.pesquisar()){
			var data = $.cadastro.getData();
			if(data.cor !== undefined){
				data.cor = null;
			}

			$.loading(true);
			$.ajax({
				url: "../ajax/cadastro_pesquisar.php",
				data: data,
				type: "POST",
				success: function(html){
					$.loading(false);
					extractScript(html);
				}
			});
		}
	},
	/* ******************************************************************************
	 C R I A   A   G R A D E   D E   P E S Q U I S A   C O M   O S   R E G I S T R O S
	 ****************************************************************************** */
	pesquisar_grade: function(async){
		async = (async === undefined ? true : async);
		if($.cadastro.ajax.pesquisar_grade === null && $.cadastro.variavel.query !== null){
			var offset = $("#divgradepesquisa tr").length;
			offset = (offset === 0 ? offset : offset - 1);
			$.cadastro.ajax.pesquisar_grade = $.ajax({
				async: async,
				url: "../ajax/cadastro_pesquisar_grade.php",
				data: ({
					tabela: $("#hdnCadTable").val(),
					query: $.cadastro.variavel.query,
					offset: offset
				}),
				dataType: "html",
				type: "POST",
				success: function(html){
					if($("#divgradepesquisa > table").length > 0){
						$("#divgradepesquisa > table").append(html);
					}else{
						$("#divgradepesquisa").html(html);
					}

					if($("#divgradepesquisa tr").length <= 76){
						$.ajax({
							async: false,
							url: "../ajax/cadastro_pesquisar_totreg.php",
							data: ({
								tabela: $("#hdnCadTable").val(),
								query: $.cadastro.variavel.query
							}),
							success: function(html){
								totreg = html;
							}
						});
					}

					if(parseInt(totreg) !== 0){
						$("#c_treg").html(($("#divgradepesquisa tr").length - 1) + " de " + totreg);
					}else{
						$("#c_treg").html($("#divgradepesquisa tr").length - 1);
					}

					$.cadastro.ajax.pesquisar_grade = null;

					$("#divgradepesquisa tbody").bind("scroll", function(){
						var outerHeight = parseInt($("#divgradepesquisa tbody").height());
						var scrollPos = parseInt($("#divgradepesquisa tbody").scrollTop());
						var innerHeight = parseInt($("#divgradepesquisa tbody").get(0).scrollHeight);
						if(innerHeight - scrollPos - outerHeight < outerHeight){
							$.cadastro.pesquisar_grade();
						}
					}).focus()
					fixa_topogrid();
					ordenar_grid();
				}
			});
			if(!async){
				$.cadastro.ajax.pesquisar_grade = null;
			}
		}
	},
	/* ****************************
	 P R O X I M O   R E G I S T R O
	 **************************** */
	proximo: function(){
		if($.cadastro.variavel.index_pesquisa != null){
			var tr = null;
			var index = $.cadastro.variavel.index_pesquisa + 1;
			while(!$(tr).is("tr")){
				tr = $("#divgradepesquisa tbody > *").get(index++);
				if(tr === undefined || tr === null){
					break;
				}
			}
			if(tr != null){
				$(tr).click();
			}else{
				var arr_tr = $("#divgradepesquisa tbody tr");
				$.cadastro.pesquisar_grade(false);
				if($("#divgradepesquisa tbody tr").length > $(arr_tr).length){
					$.cadastro.proximo();
				}
			}
		}
	},
	/* ***********************************************************************************************************************************
	 L I M P A   A   T E L A   E   H A B I L I T A   P O R   P A D R A O   O S   B O T O E S   D E    I N C L U I R   E   P E S Q U I S A R
	 *********************************************************************************************************************************** */
	retornar: function(){
		$.cadastro.before.retornar();
		$.cadastro.limpar();
		$.cadastro.status(0);
		$.cadastro.variavel.index_pesquisa = null;
		$.cadastro.variavel.count_pesquisa = null;
		$("#mainForm").focusFirst();
		$.cadastro.after.retornar();
	},
	/* ********
	 S A L V A R
	 ******** */
	salvar: function(){
		if(!$.cadastro.before.salvar()){
			return false;
		}

		if($.cadastro.status() == 2){
			var cadastroExiste = "N";

			var chave_vazia = false;
			var arr_primarykey = $("[primarykey]").map(function(){
				if($(this).val().length === 0){
					chave_vazia = true;
				}
				return $(this).val();
			}).get();

			if(!chave_vazia){
				if($.cadastro.variavel.ajax.salvar !== null){
					return false;
				}
				$.cadastro.variavel.ajax.salvar = $.ajax({
					async: false,
					url: "../ajax/cadastro_existe.php",
					data: ({
						tabela: $("#hdnCadTable").val(),
						arr_primarykey: arr_primarykey
					}),
					dataType: "html",
					complete: function(){
						$.cadastro.variavel.ajax.salvar = null;
					},
					success: function(html){
						cadastroExiste = html;
					}
				});
				$.cadastro.variavel.ajax.salvar = null;
			}
			if(cadastroExiste === "S"){
				$.messageBox({
					type: "error",
					title: "",
					text: "A chave primaria ja se encontra cadastrada."
				});
				return false;
			}
		}

		$("[required][mask]").filter("[mask='inteiro'], [mask^='decimal']").not("[primarykey], [identify]").each(function(){
			var mask = $(this).attr("mask");
			if($(this).val().length === 0 && (mask === "inteiro" || mask.substr(0, 7) === "decimal")){
				$(this).val("0");
				if($(this).attr("mask") !== "inteiro"){
					var n = $(this).attr("mask").substr(7);
					$(this).val(number_format($(this).val().replace(",", "."), n, ",", ""));
				}
			}
		});
		if($.cadastro.checkRequired()){
			var data = $.cadastro.getData(true);
			$.extend(data, {action: "save"});
			$.loading(true);
			$.ajax({
				url: "../ajax/cadastro_action.php",
				data: data,
				type: "POST",
				success: function(html){
					$.loading(false);
					extractScript(html);
				}
			});
		}
	},
	/* ******************************************************************************************************************************************
	 A L T E R A   O   S T A T U S   D O   C A D A S T R O   ( H A B I L I T A / D E S A B I L I T A   O S   C A M P O S   E   O S   B O T O E S )
	 ****************************************************************************************************************************************** */
	status: function(status){
		/*
		 0 - Inserir um novo registro ou iniciar uma nova pesquisa
		 1 - Registro na tela, onde o usuario pode alterar ou excluir
		 2 - Inserindo um novo registro
		 3 - Alterando um registro existente
		 4 - Pesquisando um registro
		 */
		if(status === undefined){
			return $("#hdnCadStatus").val();
		}else{
			if(!$.cadastro.before.status(status)){
				return false;
			}
			$("#hdnCadStatus").val(status);
			$("#divCadButtons0,#divCadButtons1,#divCadButtons2,#divCadButtons3").hide();
			switch(status){
				case 0:
					$("#divCadButtons0").show();
					$.cadastro.disabled(true);
					if($("[primarykey]").length === 1){
						$("[primarykey]").disabled(false);
					}
					break;
				case 1:
					$("#divCadButtons1").show();
					$.cadastro.disabled(true);
					break;
				case 2:
					$("#divCadButtons2").show();
					$.cadastro.disabled(false);
					$("[primarykey][mask='inteiro']").disabled(true);
					$("[primarykey][required]").disabled(false);
					$("[primarykey][autoinc=false]").disabled(false);
					break;
				case 3:
					$("#divCadButtons2").show();
					$.cadastro.disabled(false);
					$("[primarykey]").disabled(true);
					break;
				case 4:
					$("#divCadButtons3").show();
					$.cadastro.disabled(false);
					break;
			}
			$.cadastro.after.status(status);
		}
		$.cadastro.verificar_navegacao();
	},
	/* ******************************************************************************
	 H A B I L I T A / D E S A B I L I T A   O S   C A M P O S   D O   C A D A S T R O
	 ****************************************************************************** */
	disabled: function(bool){
		$("#divcadastro input,select,textarea").filter(":not(input:button)").disabled(bool);
	},
	/* **************************************************************************************************************
	 C A P T U R A   T O D O S   O S   C A M P O S   D O   C A D A S T R O   E   R E T O R N A   C O M O   O B J E T O
	 ************************************************************************************************************** */
	getData: function(emptyCheckBox){
		if(emptyCheckBox === undefined){
			emptyCheckBox = false;
		}
		var data = ({
			cadastro: $("#hdnCadTable").val()
		});
		$("#mainForm input:not(:button),#mainForm select,#mainForm textarea").each(function(){
			var value;
			if($(this).is(":checkbox")){
				value = (emptyCheckBox ? ($(this).checked() ? "S" : "N") : "");
			}else{
				value = $(this).val();
			}
			data[$(this).attr("id")] = value;
		});
		return data;
	},
	/* ****************************************************************************************************************
	 V E R I F I C A   S E   T O D O S   O S   C A M P O S   O B R I G A T O R I O S   F O R A M   P R E E N C H I D O S
	 **************************************************************************************************************** */
	checkRequired: function(){
		var elements = $("[required]");
		for(var i = 0; i < $(elements).length; i++){
			var e = elements[i];
			if(trim($(e).val()).length === 0){
				$.messageBox({
					type: "error",
					text: "O campo <b>" + e.getAttribute("required") + "</b> &eacute; de preenchimento obrigat&oacute;rio.",
					buttons: ({
						"Ok": function(){
							$.messageBox("close");
							$(e).focus();
						}
					})
				});
				return false;
			}
		}
		return true;
	},
	/* ********************************************************************************************
	 F U N C O E S   Q U E   S E R A O   E X E C U T A D A S   D E P O I S   D E   C A D A   A C A O
	 ******************************************************************************************** */
	after: ({
		alterar: function(){
			return true;
		},
		cancelar: function(){
			return true;
		},
		carregar: function(){
			return true;
		},
		clonar: function(){
			return true;
		},
		deletar: function(){
			return true;
		},
		inserir: function(){
			return true;
		},
		limpar: function(){
			return true;
		},
		novapesquisa: function(){
			return true;
		},
		retornar: function(){
			return true;
		},
		salvar: function(){
			return true;
		},
		status: function(){
			return true;
		}
	}),
	/* ******************************************************************************************
	 F U N C O E S   Q U E   S E R A O   E X E C U T A D A S   A N T E S   D E   C A D A   A C A O
	 ****************************************************************************************** */
	before: ({
		alterar: function(){
			return true;
		},
		cancelar: function(){
			return true;
		},
		carregar: function(){
			return true;
		},
		clonar: function(){
			return true;
		},
		deletar: function(){
			return true;
		},
		inserir: function(){
			return true;
		},
		limpar: function(){
			return true;
		},
		novapesquisa: function(){
			return true;
		},
		pesquisar: function(){
			return true;
		},
		retornar: function(){
			return true;
		},
		salvar: function(){
			return true;
		},
		status: function(){
			return true;
		}
	})
});

function errodetalhe(){
	$("#erro-tecnico").toggle();
	$("[messagebox] input").parent().css("top","445px");
	$("[messagebox]").css("height","483px");
}
