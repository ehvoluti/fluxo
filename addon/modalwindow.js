__modalWindow = new Array();

$.modalWindow = function(settings){
	if(typeof (settings) === "string"){
		switch(settings){
			case "close":
				var x = 0;
				var parent_window = parent;
				while(parent_window.__modalWindow.length === 0 && x < 10){
					parent_window = parent;
					x++;
				}
				var modalWindow = $.parentSelector("[__modalWindow]:last");
				var content = parent_window.__modalWindow.pop();
				if(content !== null){
					$(modalWindow).find("[__modalWindow_body] > *").each(function(){
						$(content)[0].appendChild(this);
					});
				}
				$.background(false);
				if($(modalWindow).next().is("[__modalWindow_hint]")){
					$(modalWindow).next().remove();
				}
				$(modalWindow).remove();
				break;
		}
		return true;
	}

	settings = $.extend({
		closeButton: false,
		content: null,
		height: null,
		hint: null,
		padding: true,
		title: "",
		width: null
	}, settings);

	settings.center = $.extend({
		left: true,
		top: false
	}, settings.center);

	settings.position = $.extend({
		left: 0,
		top: "15%"
	}, settings.position);

	$.background(true);

	var div = document.createElement("div");
	document.body.appendChild(div);
	$(div).attr({
		"__modalWindow": true
	}).css({
		"background": "-webkit-gradient(linear,left top,left bottom,from(RGBA(250,250,250,.9)),to(RGBA(250,250,250,.9)),color-stop(.5,RGBA(255,255,255,1)))",
		"border": "1px solid #8899AA",
		"left": settings.position.left,
		"position": "absolute",
		"top": settings.position.top
	});
	if(settings.height !== null){
		$(div).css("height", settings.height);
	}
	if(settings.width !== null){
		$(div).css("width", settings.width);
	}
	$(div).center({
		left: settings.center.left,
		top: settings.center.top
	});

	if(settings.title.length > 0 || settings.closeButton){
		var title = document.createElement("div");
		div.appendChild(title);
		$(title).css({
			"background-color": "#E0E7E9",
			"color": "#333",
			"font-size": "15px",
			//"height": "20px",
			"padding": "7px 12px",
			"width": "100%"
		}).html(settings.title);
		if(settings.closeButton){
			var bclose = document.createElement("div");
			title.appendChild(bclose);
			$(bclose).attr({
				"alt": "Fechar Janela",
				"title": "Fechar Janela"
			}).css({
				"color": "#FFF",
				"cursor": "pointer",
				"height": "16px",
				"left": (parseInt(settings.width) - 25),
				"position": "absolute",
				"text-align": "center",
				"top": "8px",
				"width": "16px"
			}).css3({
				"border-radius": "5px"
			}).html("-").bind("click", function(){
				$.modalWindow("close");
			}).bind("mouseenter", function(){
				$(this).css({
					"background-color": "#E66",
					"border": "1px solid #C11"
				});
			}).bind("mouseout", function(){
				$(this).css({
					"background-color": "#E44",
					"border": "1px solid #C11"
				});
			}).trigger("mouseout");
		}
	}

	var wbody = document.createElement("div");
	div.appendChild(wbody);
	$(wbody).attr({
		"__modalWindow_body": true
	}).css3({
		"box-sizing": "border-box"
	});

	__modalWindow.push(settings.content);
	if(settings.content !== null){
		$(settings.content).children().each(function(){
			wbody.appendChild(this);
		});
	}

	$(wbody).css({
		"height": $(div).height() - (title !== undefined ? $(title).height() : 0),
		"padding": (settings.padding ? "10px" : "0px"),
		"width": "100%"
	}).focusFirst();

	if(typeof (settings.hint) === "string" && settings.hint.length > 0){
		var hint = document.createElement("div");
		document.body.appendChild(hint);
		$(hint).attr({
			"__modalWindow_hint": true
		}).css({
			"background-color": "RGBA(255,255,200,0.85)",
			"border": "1px solid #EC9",
			"color": "#AB8B0C",
			"font-size": "11px",
			"left": $(div).position().left,
			"padding": "10px 10px",
			"position": "absolute",
			"text-align": "center",
			"top": ($(div).position().top + $(div).height() + 7),
			"width": ($(div).width() - 20)
		}).html(settings.hint);
	}

	$.gear();

	return $(wbody);
};
