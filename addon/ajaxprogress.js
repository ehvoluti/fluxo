var __ajaxProgress_focused = null; // Elemento que estava focado antes de iniciar o processo

$.ajaxProgress = function(settings){
	settings = $.extend({
		cache: false,
		dataType: "html"
	}, settings);

	__ajaxProgress_focused = $("[focused='true']");

	$.ajaxProgressMec.element = settings.progressbar;
	if(typeof (settings.beforeSend) === "function"){
		$.ajaxProgressMec.beforeSend = settings.beforeSend;
		settings.beforeSend = function(){
			$.ajaxProgressMec.beforeSend();
			$.ajaxProgressMec.start();
		};
	}else{
		settings.beforeSend = $.ajaxProgressMec.start;
	}
	if($.isFunction(settings.complete)){
		$.ajaxProgressMec.complete = settings.complete;
		settings.complete = function(result){
			$.ajaxProgressMec.stop();
			$.ajaxProgressMec.complete(result);
		};
	}else{
		settings.complete = $.ajaxProgressMec.stop;
	}

	$.ajax(settings);

	return this;
};

$.ajaxProgressMec = ({
	beforeSend: function(){},
	complete: function(){},
	running: false, // Se o processo esta rodando
	element: null,
	start: function(){
		$.background(true).focus();
		var border = document.createElement("div");
		var div = document.createElement("div");
		var pbar = document.createElement("div");
		var text = document.createElement("label");
		document.body.appendChild(border);
		border.appendChild(div);
		div.appendChild(pbar);
		div.appendChild(text);
		$(border).attr({
			"ajaxProgress": true
		}).css({
			"background": "-webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(242,246,248,1)), color-stop(50%,rgba(216,225,231,1)), color-stop(51%,rgba(181,198,208,1)), color-stop(100%,rgba(224,239,249,1)))",
			"border": "1px solid #666",
			"border-radius": "3px",
			"height": "60px",
			"padding": "5px",
			"width": "270px"
		}).center({left: true, top: true});
		$(div).css({
			"background": "-webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(255,255,255,1)), color-stop(50%,rgba(243,243,243,1)), color-stop(51%,rgba(237,237,237,1)), color-stop(100%,rgba(255,255,255,1)))",
			"border-radius": "3px",
			"height": "40px",
			"padding": "10px",
			"text-align": "center",
			"width": "250px"
		});
		$(pbar).attr({
			"class": "progressbar"
		}).css({
			"height": "15px",
			"margin-bottom": "10px",
			"width": "100%"
		});
		$(text).css({
			"color": "#444",
			"font-size": "7pt"
		});
		$.gear();
		$(pbar).progressbar(0);
		$.ajaxProgressMec.element = pbar;
		$.ajaxProgressMec.running = true;
		$.ajaxProgressMec.verify();
	},
	stop: function(){
		var focused = $(":focus");
		$("div[ajaxProgress]").remove();
		$.background(false);
		$(__ajaxProgress_focused).focus();
		$.ajaxProgressMec.running = false;
		$(focused).focus();
	},
	verify: function(){
		$.ajax({
			cache: false,
			url: "../ajax/progress.php",
			success: function(html){
				var info = html.split(";");
				var percent = info[0];
				var text = info[1];
				$("div[ajaxProgress] label").html(text);
				$($.ajaxProgressMec.element).progressbar(percent);
				if($.ajaxProgressMec.running){
					setTimeout("$.ajaxProgressMec.verify()", 500);
				}
			}
		});
	}
});