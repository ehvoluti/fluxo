$.fn.richtext = function(){
	$(this).each(function(){
		var textarea = this;
		$(this).wysiwyg({
			formWidth:"600px",
			initialContent:"",
			events:({
				keypress: function(){ $(textarea).html($("#" + $(textarea).attr("id") + "-wysiwyg-iframe").contents().find("body").html()); },
				keydown:  function(){ $(textarea).html($("#" + $(textarea).attr("id") + "-wysiwyg-iframe").contents().find("body").html()); },
				keyup:    function(){ $(textarea).html($("#" + $(textarea).attr("id") + "-wysiwyg-iframe").contents().find("body").html()); }
			}),
			rmUnusedControls:true,
			controls:({
				bold:({
					tags:["b","strong"],
					css:{fontWeight:"bold"},
					tooltip:"Negrito",
					hotkey:{"ctrl":1,"key":66}
				}),
				createLink:({
					tags:["a"],
					tooltip:"Inserir link"
				}),
				decreaseFontSize:({
					visible:true,
					tooltip:"Diminuir fonte"
				}),
				h1:({
					visible:true,
					className:"h1",
					command:($.browser.msie || $.browser.safari) ? "formatBlock" : "heading",
					arguments:($.browser.msie || $.browser.safari) ? "<h4>" : "h1",
					tags:["h4"],
					tooltip:"Título 1"
				}),
				h2:({
					visible:true,
					className:"h2",
					command:($.browser.msie || $.browser.safari) ? "formatBlock" : "heading",
					arguments:($.browser.msie || $.browser.safari) ? "<h5>" : "h5",
					tags:["h5"],
					tooltip:"Título 2"
				}),
				h3:({
					visible:true,
					className:"h3",
					command:($.browser.msie || $.browser.safari) ? "formatBlock" : "heading",
					arguments:($.browser.msie || $.browser.safari) ? "<h3>" : "h3",
					tags:["h3"],
					tooltip:"Título 3"
				}),
                html:({
                    visible:true,
                    tooltip:"HTML"
                }),
				increaseFontSize:({
					visible:true,
					tooltip:"Aumentar fonte"
				}),
				indent:({
					tooltip:"Aumentar recuo"
				}),
				insertHorizontalRule:({
					tags:["hr"],
					tooltip:"Inserir Linha Horizontal"
				}),
				insertImage:({
					tags:["img"],
					tooltip:"Inserir imagem",
					exec:function(){
						var editorDoc = this.editorDoc;
						$("#div_richtext_image input:text").val("");
						$("#richtext_image_image").attr("src","");
						$("#richtext_image_btn_inserir").unbind("click").bind("click",function(){
							if($("#richtext_image_url").val().length == 0){
								$.messageBox({
									type:"error",
									text:"Informe a URL da imagem a ser inserida.",
									focusOnClose:$("#richtext_image_url")
								});
								return false;
							}
							var img_html = "<img src='" + $("#richtext_image_url").val() + "' style='";
							if($("#richtext_image_height").val().length > 0){
								img_html += "height:" + $("#richtext_image_height").val() + "px; ";
							}
							if($("#richtext_image_width").val().length > 0){
								img_html += "width:" + $("#richtext_image_width").val() + "px; ";
							}
							img_html += "'>";
							editorDoc.execCommand("insertHTML",false,img_html);
							$.modalWindow("close");
						});
						$.modalWindow({
							title:"Inserir Imagem",
							content:$("#div_richtext_image"),
							width:"400px",
						});
						$.gear();
					},
				}),
				insertOrderedList:({
					tags:["ol"],
					tooltip:"Numeração"
				}),
				insertTable:{visible:false},
				insertUnorderedList:({
					tags:["ul"],
					tooltip:"Marcadores"
				}),
				italic:({
					tags:["i","em"],
					css:{fontStyle:"italic"},
					tooltip:"Itálico"
				}),
				justifyCenter:({
					tags:["center"],
					css:{textAlign: "center"},
					tooltip:"Centralizar"
				}),
				justifyFull:({
					css:{textAlign: "justify"},
					tooltip:"Justificar"
				}),
				justifyLeft:({
					css:{textAlign: "left"},
					tooltip:"Alinhar texto à esquerda"
				}),
				justifyRight:({
					css:{textAlign:"right"},
					tooltip:"Alinhar texto à direita"
				}),
				outdent:{tooltip:"Diminuir recuo"},
				paste:{ tooltip:"Colar"},
				redo:{tooltip:"Refazer"},
				removeFormat:{tooltip:"Remover formatação"},
				strikeThrough:({
					tags:["s","strike"],
					css:{textDecoration:"line-through"},
					tooltip:"Tachado"
				}),
				subscript:({
					tags:["sub"],
					tooltip:"Subscrito"
				}),
				superscript:({
					tags:["sup"],
					tooltip:"Sobrescrito"
				}),
				underline:({
					tags:["u"],
					css:{textDecoration:"underline"},
					tooltip:"Sublinhado"
				}),
				undo:{tooltip:"Desfazer"}
			})
		});
		
		var iframe = $("#" + $(this).attr("id") + "-wysiwyg-iframe")[0];
		$(iframe).css({
			height:"90%",
			width:"100%"
		}).parent().css({
			height:"96%",
			width:"99%"
		});
		$(iframe).contents().find("body").css({
			"font-family":"Verdana",
			"font-size":"8pt"
		});
        
        var btn_html = $(iframe).parent().find("li[role='menuitem'].html");
        $(btn_html).bind("click",function(){
            var richtext = $(this).parent().parent();
            if(parseInt($(richtext).height()) != 16){
                $(richtext).attr("o_height",$(richtext).height());
                $(richtext).height("16px");
            }else{
                $(richtext).height($(richtext).attr("o_height"));
            }
        });
		
		// Habilita/desabilita o richtext
		$(this).disabled($(this).disabled());
	});
	
	if($("#div_richtext_image").length == 0){
		var content = "<div id='div_richtext_image' style='display:none'><table>";
		content += "<tr><td class='label2'><label for='richtext_image_url'>URL:</td>";
		content += "<td class='field'><input type='text' id='richtext_image_url' class='field_g'></td></tr>";
		content += "<tr><td class='label2'><label for='richtext_image_width'>Dimens&atilde;o (L x A):</td>";
		content += "<td class='field'><input type='text' id='richtext_image_width' class='field_pp' mask='inteiro'>&nbsp;<input type='text' id='richtext_image_height' class='field_pp' mask='inteiro'></td></tr>";
		content += "<tr><td class='label2'><label>Pr&eacute; Visualiza&ccedil;&atilde;o:</td>";
		content += "<td class='field'><div style='border:1px solid #CCC; height:100px; width:150px'><img id='richtext_image_image' style='width:100%'><div></td></tr>";
		content += "<tr><td colspan='2' style='padding-top:10px; text-align:center'><input type='button' id='richtext_image_btn_inserir' value='Inserir'><input type='button' value='Cancelar' style='margin-left:4px' onclick='$.modalWindow(\"close\")'></td>";
		content += "</table></div>";
		$("body").append(content);
		$("#richtext_image_url").bind("change",function(){
			$("#richtext_image_image").attr("src",$(this).val());
		});
	}
	
	return this;
}