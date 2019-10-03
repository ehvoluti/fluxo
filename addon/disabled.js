var _disabled_readonlyElements = "input:text:not([id^='desc_']), input:password";

$.fn.disabled = function(b){
	if(b === undefined){
		if($(this).is(_disabled_readonlyElements)){
			return $(this).is("[readonly]");
		}else{
			return $(this).is(":disabled");
		}
	}else{
		$(this).each(function(){
			if($(this).is(_disabled_readonlyElements)){
				if(b){
					$(this).attr("readonly", true);
					$(this).attr("tabindex", -1);
				}else{
					$(this).removeAttr("readonly");
					$(this).attr("tabindex", 0);
				}
			}else{
				$(this).attr("disabled", b);
			}

			// Altera a cor do label
			if(typeof $(this).attr("id") === "string" && $(this).attr("id").length > 0){
				$("label[for='" + $(this).attr("id") + "']").attr("disabled", b).css("color", (b ? "#999999" : "#000000"));
			}
			// Habilita/desabilita o calendario
			if($(this).attr("mask") === "data"){
				$(this).datepicker(b ? "disable" : "enable");
			}
			// Habilita/desabilita a lupa de pesquisa
			if($(this).filter("[identify][description]").length > 0){
				$("#" + $(this).attr("description")).disabled(b);
				$(this).parent().next().find("img").fadeTo(0, (b ? 0.5 : 1));
			}
			// Habilita/desabilita a caixa rich-text
			var richtext = $("#" + $(this).attr("id") + "-wysiwyg-iframe");
			if($(richtext).length > 0){
				if($(this).disabled()){
					var div = document.createElement("div");
					$(richtext).parent()[0].appendChild(div);

					$(div).attr({
						"richtext_disabled": true
					}).css({
						"background-color": "rgba(255,255,255,0.75)",
						"height": "100%",
						"position": "relative",
						"top": ($(richtext).parent().is(":visible") ? $(richtext).parent().offset().top * (-0.69) : $(richtext).parent().height() * (-2)),
						"width": "100%"
					});
				}else{
					$(richtext).parent().find("[richtext_disabled]").remove();
				}
			}
		});
		return this;
	}
};