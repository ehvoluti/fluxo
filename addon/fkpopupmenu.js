$.fn.fkPopupMenu = function(){
	var popId = "cwFkPopupMenu";
	$(this).each(function(){
		var arrAttr = $(this).attr("idtable").split(",");
		var idtable = arrAttr[0];
		var descricao = arrAttr[1];
		var label = $("label[for='" + $(this).attr("id") + "']");

		label.before("<img>&nbsp;");
		var button = label.prev();
		button.attr({
			"alt":"Exibir op\u00E7\u00F5es",
			"src":"../img/plus11.png",
			"title":"Exibir op\u00E7\u00F5es",
			"idtable":idtable,
			"descricao":descricao,
			"idelement":$(this).attr("id")
		}).bind("click",function(){
			var element = $("#" + $(this).attr("idelement"));
			var html = "<table style=\"font-size:11px; margin:2px; width:auto\">";
			if(idtable != request("idtable")){
				html += "<tr onclick=\"openProgram('" + $(this).attr("idtable") + "')\"><td><img src=\"../img/windows16.png\"></td><td style=\"white-space: nowrap\">Abrir cadastro de " + $(this).attr("descricao") + "</td></tr>";
			}
			if(element.attr("identify") == "produto"){
				html += "<tr onclick=\"openProgram('ExtratoProd','codproduto=" + element.val() + "')\"><td><img src=\"../img/chart16.png\"></td><td>Abrir dados do Produto</td>";
			}
			if(element.is("select") && element.attr("habilitaRefresh") != "N"){
				html += "<tr onclick=\"$('#" + element.attr("id") + "').refreshComboBox()\"><td><img src=\"../img/refresh16.png\"></td><td>Recarregar lista</td>";
			}
			html += "</table>";
			$("#" + popId).remove();
			$("body").append("<div id='" + popId + "'></div>");
			$("#" + popId).css({
				"background-color":"#FAFAFA",
				"border":"1px solid #AAAAAA",
				"position":"absolute",
				"left":mouseX,
				"top":mouseY
			}).css3({
				"box-shadow":"3px 3px 3px #555555"
			}).html(html);
			$("#" + popId + " table tr").bind("mouseover",function(){
				$(this).prev().css({
					"border-bottom":"none"
				});
				$(this).css({
					"background-color":"#EAEAFF",
					"border":"1px solid #CCCCFF",
					"cursor":"pointer"
				});
			}).bind("mouseout",function(){
				$(this).css({
					"background":"none",
					"border-color":"RGBA(0, 0, 0, 0)"
				});
			}).find("td img").css({
				"padding":"2px",
				"padding-right":"5px"
			});
			$("#" + popId + " table td").css({
				"padding-right":"5px"
			});
			$(document).bind("click",function(){
				var popup = $("#" + popId);
				if(mouseY != popup.offset().top || mouseX != popup.offset().left){
					popup.animate({
						"opacity":"hide"
					},"fast",function(){
						$(this).remove();
						$(document).unbind("click");
					});
				}
			});
		});
	});
	return this;
}