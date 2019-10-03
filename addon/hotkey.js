$(window).bind("focus",function(){
	$.hotkey.hide();
});

$(document).bind("keydown",function(e){
	if(e.keyCode == 18){
		$.hotkey.show();
	}else if(e.altKey){
		var element = $("[hotkey='" + String.fromCharCode(e.keyCode) + "']");
		if($(element).length > 0){
			if($(element).is("input:text,select,textarea")){
				$(element).focus();
			}else{
				$(element).click();
				$.hotkey.show();
			}
		}
	}
}).bind("keyup",function(e){
	if(e.keyCode == 18){
		$.hotkey.hide();
	}
});

$.hotkey = ({
	variable:({
		keys:[]
	}),
	show:function(){
		$.hotkey.hide();
		$.hotkey.variable.keys = new Array();
		var background = $("[__background]");
		$("input,select,textarea").not("table.grid *").filter(":enabled").add(".tabon,.taboff").filter(":visible").each(function(){
			var criar_hotkey = true;
			if($(background).length > 0){
				if($(background).prevAll().find("*").filter(this).length > 0){
					if(this.id.length > 0){
						criar_hotkey = false;
					}
				}
			}
			if(criar_hotkey){
				var key = null;
				if($(this).is("input:text,input:checkbox,select,textarea")){
					var label = $("label[for='" + $(this).attr("id") + "']");
					if($(label).length > 0){
						key = $.hotkey.find_available($(label).html());
						var left = 2;
						var top = 4;
					}
				}else if($(this).is("input:button")){
					key = $.hotkey.find_available($(this).val());
					var left = 12;
					var top = 2;
				}else if($(this).is(".tabon,.taboff")){
					key = $.hotkey.find_available($(this).find("div").html());
					var left = -7;
					var top = -10;
				}
				if(key !== null){
					$(this).attr("hotkey",key);
					var div = document.createElement("div");
					document.body.appendChild(div);
					$(div).attr({
						"hotkey_tip":true
					}).css({
						"background-color":"#FFEECC",
						"border":"1px solid #666666",
						"color":"#555555",
						"cursor":"default",
						"font-size":"7pt",
						"padding":"2px",
						"position":"absolute",
						"text-align":"center",
						"width":"8px"
					}).css3({
						"border-radius":"3px",
						"box-shadow":"0px 0px 2px #666666"
					}).html(key).css({
						"left":$(this).offset().left + $(this).width() - $(div).width() + left,
						"top":$(this).offset().top + $(this).height() - $(div).height() + top
					});
				}
			}
		});
	},
	hide:function(){
		$("[hotkey]").removeAttr("hotkey");
		$("[hotkey_tip]").remove();
	},
	find_available:function(str){
		var abc = "ABCDEFGHIJKLMNOPQRSTUVXWYZ123456789";
		// Busca um caracter no texto passado por parametro
		for(var i = 0; i < str.length; i++){
			var a = remove_special(str.substr(i,1)).toUpperCase();
			if(strpos(abc,a) !== false){
				var f = false;
				for(var j = 0; j < $.hotkey.variable.keys.length; j++){
					if(a == $.hotkey.variable.keys[j]){
						f = true;
						break;
					}
				}
				if(!f){
					$.hotkey.variable.keys[$.hotkey.variable.keys.length] = a;
					return a;
				}
			}
		}
		// Procura um caracter valido quando nao achou no parametro passado
		for(var i = 0; i < abc.length; i++){
			var f = false;
			for(var j = 0; j < $.hotkey.variable.keys.length; j++){
				if(abc[i] == $.hotkey.variable.keys[j]){
					f = true;
					break;
				}
			}
			if(!f){
				$.hotkey.variable.keys[$.hotkey.variable.keys.length] = abc[i];
				return abc[i];
			}
		}
	}
});