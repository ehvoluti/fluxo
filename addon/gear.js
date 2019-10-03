$.gear = function(){

	var elements = ({
		button: [],
		div: [".listbox", ".progressbar", ".tabpage", "[background]"],
		embed: [],
		fieldset: [],
		file: [],
		img: [".picture"],
		input: [],
		li: [".tabend", ".taboff", ".tabon"],
		select: [],
		table: [".grid"],
		textarea: [],
		ul: [".tabs"]
	});

	var selector = [];
	for(var element in elements){
		if(elements[element].length === 0){
			selector[selector.length] = element + ":not([gear])";
		}else{
			for(var i = 0; i < elements[element].length; i++){
				selector[selector.length] = element + elements[element][i] + ":not([gear])";
			}
		}
	}
	selector = selector.join(", ");

	var elemGear = $(selector);

	// Ajusta o botao de arquivo (upload)
	$(elemGear).filter("input:file").each(function(){
		if($(this).is(".hidden")){
			return true;
		}
		$(this).before("<button></button>");
		var button = $(this).prev();
		$(button).addClass("button-upload");
		$(button).get(0).appendChild(this);
		$(button).append($(this).attr("text") === undefined ? "Selecionar&nbsp;Arquivo" : $(this).attr("text"));
		elemGear = $(elemGear).add(button);
	});

	// Ajusta tamanho dos botoes
	$(elemGear).filter("button:not([role=presentation],[hidefocus=1]),input:button").each(function(){
		if(strpos($(this).attr("style"), "height") === false){
			$(this).css("min-height", "28px");
		}
		if(strpos($(this).attr("style"), "width") === false){
			$(this).css("min-width", "110px");
		}
	});

	// Ajusta a posicao do fundo das abas (logo da controlware)
	$(elemGear).filter("div.tabpage").css3({
		"background-clip": "border-box",
		"background-origin": "content-box"
	});

	// Implementa um atributo para controlar quais elementos estao focados/desfocados
	$(elemGear).bind("focus", function(){
		$("[focused=true]").attr("focused", false);
		$(this).attr("focused", true);

		// Quando houver uma caixa de mensagem, apenas os elementos da caixa devem ser focados
		if($("[messageBox]").length > 0 && $(this).parents().filter("[messageBox]").length === 0){
//			$("[messageBox]").focusFirst(true);
		}
	}).bind("blur", function(){
		$(this).attr("focused", false);
	}).attr("focused", false);

	// Cria um atributo para guardar os valores antigos dos campos
	$(elemGear).filter("input,select").bind("focus", function(){
		$(this).data("oldValue", $(this).val());
	});

	// Ajusta o tamanho das paginas das abas
	$(elemGear).filter(".tabpage").each(function(){
		$(this).width($(this).width() - 22);
	});

	// Arredonda a pagina de cada aba
	$(elemGear).filter(".tabpage").css3({
		"border-bottom-left-radius": "3px",
		"border-bottom-right-radius": "3px"
	});

	// Desabilita o autocomplete dos inputs
	$(elemGear).filter("input").attr("autocomplete", "off");

	// Cria os final das abas (para os que ainda nao tem)
	$(elemGear).filter("ul.tabs").each(function(){
		if($(this).find("li.title,li.tabend").length === 0){
			var tabend = document.createElement("li");
			this.appendChild(tabend);
			$(tabend).attr("class", "tabend");
			elemGear = $(elemGear).add(tabend);
		}
	});

	// Alinha o texto das abas
	$(elemGear).filter(".tabon,.taboff,.tabend").each(function(){
		if($(this).find("div[cwTabAlign]").length === 0){
			$(this).html("<div>" + $(this).html() + "</div>");
			var div = $(this).find("div:first");
			$(div).attr("cwTabAlign", true).css({
				position: "relative",
				top: $(this).height() / 2 - $(div).height() / 2 - 3
			});
		}
	});

	// Estende o final da aba
	$(elemGear).filter(".tabend").width("100%").each(function(){
		var width = 0;
		$(this).parent().children().filter(".tabon, .taboff").each(function(){
			width += $(this).width();
		});
		$(this).width($(this).width() - width);
	});

	// Se as abas estiverem quebrando linha (abas de mais), vai criar uma rolagem
	$(elemGear).filter("ul.tabs").each(function(){
		var ul = this;
		var top = null;
		var rolagem = false;
		$(this).find("li.tabon,li.taboff").each(function(){
			if(top !== null && $(this).position().top !== top){
				rolagem = true;
			}else{
				top = $(this).position().top;
			}
		});
		if(rolagem){
			$(this).find(".tabend").remove();
			var table = document.createElement("table");
			$(this).parent()[0].appendChild(table);
			var tr = document.createElement("tr");
			table.appendChild(tr);
			var td_left = document.createElement("td");
			tr.appendChild(td_left);
			var td_center = document.createElement("td");
			tr.appendChild(td_center);
			var td_right = document.createElement("td");
			tr.appendChild(td_right);
			var area = document.createElement("div");
			td_center.appendChild(area);
			area.appendChild(this);
			$(table).css({
				"height": $(this).find("li:first").height(),
				"position": "relative",
				"top": "2px",
				"width": "100%"
			});

			var width_table = $(table).width();
			var width_tab = $(this).find("li.tabon,li.taboff").first().width();
			var width_center = Math.floor(width_table / width_tab) * 100;
			var width_dif = (width_table - width_center) / 2;
			var width_left = Math.ceil(width_dif);
			var width_right = Math.floor(width_dif);

			$(table).find("td").css({
				"padding": "0px",
				"margin": "0px"
			});
			$(td_left).add(td_center).add(td_right).css({
				"background-image": "url(../img/tabend.gif)",
				"background-position": "bottom",
				"background-repeat": "repeat-x"
			}).not(td_center).css({
				"cursor": "pointer",
				"text-align": "center"
			});
			$(td_left).html("<img src=\"../img/moveL.png\">").css({
				"width": width_left
			}).bind("click", function(){
				$(ul).stop(false, true);
				if($(ul).position().left < 0){
					$(ul).animate({left: "+=" + width_tab}, "normal");
				}else{
					$(ul).animate({left: "+=50"}, "normal", function(){
						$(ul).animate({left: $(area).width() - $(ul).width()}, "normal");
					});
				}
			});
			$(td_right).html("<img src=\"../img/moveR.png\">").css({
				"width": width_right
			}).bind("click", function(){
				$(ul).stop(false, true);
				if($(ul).width() - $(area).width() + $(ul).position().left > 0){
					$(ul).animate({left: "-=" + width_tab}, "normal");
				}else{
					$(ul).animate({left: "-=50"}, "normal", function(){
						$(ul).animate({left: "0"}, "normal");
					});
				}
			});
			$(td_center).css({
				"vertical-align": "top"
			});
			$(area).css({
				"height": "100%",
				"position": "absolute",
				"width": width_center
			});
			$(this).css({
				"position": "absolute",
				"top": "0px",
				"width": $(this).find("li.tabon,li.taboff").map(function(){
					return $(this).width();
				}).get().sum()
			});
		}
	});

	// Aplica as acoes em cada aba
	$(elemGear).filter("li[pageid]").each(function(){
		$(this).bind("click", function(){
			$(this).openTab();
		}).bind("mouseover", function(){
			if($(this).attr("class") != "tabon"){
				$(this).attr("class", "tabhot");
			}
		}).bind("mouseout", function(){
			if($(this).attr("class") != "tabon"){
				$(this).attr("class", "taboff");
			}
		}).attr("class", "taboff");
	});

	// Destaca campos obrigatorios
	$(elemGear).filter("[required]").each(function(){
		$("label[for='" + $(this).attr("id") + "']").css("font-weight", "bold");
	});

	// Quando houver apenas um estabelecimento cadastrado, deixar ele selecionado no combobox
	var estab = $(elemGear).filter("select[table='estabelecimento'] > option[value!='']");
	if(estab.length === 1){
		estab.attr("selected", true);
		estab.parent().attr("default", estab.val());
	}

	// Cria elemento progressbar
	$(elemGear).filter("div.progressbar").progressbar();

	// Arredonda borda do elemento fieldset
	$(elemGear).filter("fieldset").css3({
		"border-radius": "3px"
	}).each(function(){
		if($(this).is(":visible")){
			$(this).width($(this).width() - 30);
		}
	});

	// Verifica se existe dicas ao entrar em um campo
	$(elemGear).filter("input, select, textarea, .ms-choice").hint();

	// Cria a validacao de CPF e CNPJ nos campos
	$(elemGear).filter("input:text").bind("change", function(){
		if($(this).val().length === 0 || !in_array($(this).attr("mask"), ["cnpj", "cpf"])){
			$("[alertValid][for='" + $(this).attr("id") + "']").remove();
		}else{
			var element = this;
			$.ajax({
				url: "../ajax/validar.php",
				data: ({
					tipo: $(this).attr("mask"),
					valor: $(this).val()
				}),
				dataType: "html",
				success: function(html){
					if(html === "T"){
						$("[alertValid][for='" + $(element).attr("id") + "']").remove();
					}else{
						var label = document.createElement("label");
						$(element).parent()[0].appendChild(label);
						$(label).attr({
							"alertValid": true,
							"for": $(element).attr("id")
						}).css({
							"background-color": "#ECC",
							"border": "1px solid #DAA",
							"border-radius": "2px",
							"color": "#A66",
							"left": $(element).position().left + $(element).width() + 13,
							"line-height": "14px",
							"padding": "2px 4px",
							"position": "absolute",
							"text-shadow": "0px 1px 1px RGBA(255,255,255, 0.75)",
							"top": $(element).position().top
						}).html($(element).attr("mask").toUpperCase() + " inválido");
					}
				}
			});
		}
	});

	// Cria um botao de atualizar os combos que sao dependentes de outra tabela
	$(elemGear).filter("input:text[idtable],select[idtable]").fkPopupMenu();
	$(elemGear).filter("select[foreignkey]:not([idtable])").each(function(){
		$("label[for='" + $(this).attr("id") + "']").prepend("<img id='cwOptions_" + $(this).attr("id") + "' src='../img/plus11.png' style='cursor:pointer' onClick='showelementmenu(&quot;" + $(this).attr("id") + "&quot;)' alt='Exibir opop&ccedil;&otilde;eses' title='Exibir op&ccedil;&otilde;es'>&nbsp;");
	});

	// Pesquisa se existe descricao no campo
	$(elemGear).filter("[description]").bind("change", function(){
		$(this).description();
	}).bind("keypress", function(e){
		if(e.keyCode === 13){
			$(this).description();
		}
	});

	// Aplica mascara de entrada nos campos
	$(elemGear).cwSetMask();

	// Alinha um pouco mais para cima o label dos campos select (combobox)
	$(elemGear).filter("select").each(function(){
		$("label[for='" + $(this).attr("id") + "']").parent().css({
			"padding-bottom": "4px"
		});
	});

	// Alinha em cima o label dos campos textarea
	$(elemGear).filter("textarea,div.listbox").each(function(){
		$("label[for='" + $(this).attr("id") + "']").parent().css({
			"padding-top": "2px",
			"vertical-align": "top"
		});
	});

	// Alinha os labels dos checkbox/radio
	/*if($("#mainForm").length > 0){
		$(elemGear).filter("input:checkbox,input:radio").each(function(){
			$("label[for='" + $(this).attr("id") + "']").css({
				position: "relative",
				top: "-2px"
			});
		});
	}*/

	// Permite ampliar as fotos quando clicadas
	$(elemGear).filter("img.picture").each(function(){
		$(this).attr({
			"alt": "Clique para ampliar",
			"title": "Clique para ampliar"
		}).css({
			"background-color": "#FFFFFF",
			"cursor": "pointer"
		}).bind("click", function(){
			$(this).enlargePicture();
		});
	});

	// Nao deixa focar os elementos que estao atraz do 'background'
	$(elemGear).not("[__background]").bind("focus", function(){
		if($("[__background]").length > 0){
			if($("[__background]").prevAll().find("*").filter(this).length > 0){
				$("[__background]").focus();
			}
		}
	});

	// Estica os campos editaveis que estao dentro das grades
	$(elemGear).filter(".grid td > div:has(input:text)").css({
		"height": "100%",
		"width": "100%"
	});

	// Faz com que os elementos Flash nao fiquem na frente dos elementos HTML
	$(elemGear).filter("embed").attr({
		"z-index": "0",
		"wmode": "transparent"
	});

	// No campo de pesquisa (SearchField), se for liberado a descricao para digitacao deve fazer uma consulta
	$(elemGear).filter("input[description]").each(function(){
		var description = $("#" + $(this).attr("description"));
		if($(description).is(":not([readonly])")){

			// Cria o datalist
			var id_datalist = $(description).attr("id") + "_dl";
			var datalist = document.createElement("datalist");
			$(datalist).attr({
				id: id_datalist,
				description: $(description).attr("id")
			});
			document.body.appendChild(datalist);

			$(description).attr("list", id_datalist);

			var ajax_description_consulta = null;
			$(description).bind("keyup", function(e){
				if($(this).val().length < 3){
					$("datalist[description='" + $(this).attr("id") + "']").html("");
				}else if($(this).val() !== $(this).attr("old_value")){
					var description = this;
					var input = $("input[description='" + $(description).attr("id") + "']");
					if(ajax_description_consulta !== null){
						ajax_description_consulta.abort();
					}
					$("datalist[description='" + $(description).attr("id") + "']").html("<option value='Carregando...'>");
					ajax_description_consulta = $.ajax({
						url: "../ajax/description_consultadesc.php",
						data: ({
							tabela: $(input).attr("identify"),
							consulta: $(this).val()
						}),
						success: function(html){
							$("datalist[description='" + $(description).attr("id") + "']").html(html);
							ajax_description_consulta = null;
						}
					});
				}
				$(this).attr("old_value", $(this).val());
			}).bind("change keyup", function(){
				$("input[description='" + $(this).attr("id") + "']").val($("datalist[description='" + $(this).attr("id") + "'] option[value='" + $(this).val() + "']").attr("chave"));
			});
		}
	});

	$(elemGear).filter("select[table='natreceita_tabelacodigo']").each(function(){
		var input_id = $(this).attr("id") + "_input";

		$(this).after("<input maxlength='3' type='text'>");

		var input = $(this).next();

		$(input).attr({
			id: input_id,
			mask: "natreceita",
			natreceita_input: true
		}).css({
			"border": "none",
			"box-shadow": "none",
			"position": "absolute"
		}).bind("change", function(){
			$("#natreceita").val($(this).val());
		});

		$(this).bind("change", function(){
			var natreceita = $(this).find("option:selected").html();
			natreceita = natreceita.substr(0, natreceita.indexOf(" -"));
			$("#info_natreceita_input").val(natreceita);
			$("#natreceita").val($("#info_natreceita_input").val());
		});

		elemGear = $(elemGear).add(input);
	});

	$(elemGear).filter("[natreceita_input]").each(function(){
		var select = $("#" + $(this).attr("id").substr(0, $(this).attr("id").length - 6));
		$(this).css({
			height: ($(select).height() - 1),
			left: ($(select).position().left + 4),
			top: ($(select).position().top + 2),
			width: ($(select).width() + 2)
		});
	});


	// Cria caixinha para buscar NCM dentro do select
	$(elemGear).filter("select[table='ncm']").each(function(){
		var input_id = $(this).attr("id") + "_input";
		var datalist_id = $(this).attr("id") + "_datalist";

		$(this).after("<input type='text'><datalist></datalist>");

		var input = $(this).next();
		var datalist = $(input).next();

		$(input).attr({
			id: input_id,
			mask: "codigoncm",
			list: datalist_id,
			ncm_input: true
		}).css({
			"border": "none",
			"box-shadow": "none",
			"position": "absolute"
		}).bind("change", function(){
			var input = this;
			var select = $("#" + $(this).attr("id").substr(0, $(this).attr("id").length - 6));
			var found = false;
			$(select).find("option").each(function(){
				var codigoncm = $(this).html();
				codigoncm = codigoncm.substr(0, codigoncm.indexOf(" -"));
				if($(input).val() === codigoncm){
					$(select).val($(this).val());
					found = true;
				}
			});
			if(!found){
				$(this).val("");
			}
		}).cwSetMask();

		$(datalist).attr({
			id: datalist_id,
			ncm_datalist: true
		});

		$(this).find("option").each(function(){
			if($(this).val().length > 0){
				var codigoncm = $(this).html();
				codigoncm = codigoncm.substr(0, codigoncm.indexOf(" -"));
				$(datalist).append("<option>" + codigoncm + "</option>");
			}
		});

		$(this).bind("change", function(){
			var codigoncm = $(this).find("option:selected").html();
			codigoncm = codigoncm.substr(0, codigoncm.indexOf(" -"));
			$("#" + $(this).attr("id") + "_input").val(codigoncm);
		});

		elemGear = $(elemGear).add(input).add(datalist);
	});

	// Ajusta o tamanho da caixinha de busca do NCM
	$(elemGear).filter("[ncm_input]").each(function(){
		var select = $("#" + $(this).attr("id").substr(0, $(this).attr("id").length - 6));
		$(this).css({
			height: ($(select).height() - 1),
			left: ($(select).position().left + 1),
			top: ($(select).position().top + 2),
			width: ($(select).width() - 10)
		});
	});

	// Aplica o multiple-select nos elementos selects de multipla selecao
	$(elemGear).filter("select[multiple]").cwMultipleSelect();

	// Marca todos os elementos que ja foram passados pela funcao
	$(elemGear).not("[ncm_input],[natreceita_input]").attr("gear", true);
};
