$.fn.enlargePicture = function(){
	$.background(true);

	var div 			= document.createElement("div");
	var img 			= document.createElement("img");
	var center 			= document.createElement("center");
	var button 			= document.createElement("input");
	var btnExcluir 		= document.createElement("input");
	var btnPrincipal 	= document.createElement("input");
	var divFechar   	= document.createElement("div");

	button.type 		= "button";
	btnExcluir.type 	= "button";
	btnPrincipal.type	= "button";

	document.body.appendChild(div);
	document.body.appendChild(divFechar);
	div.appendChild(img);
	div.appendChild(center);
	div.appendChild(divFechar);
	divFechar.appendChild(button);
	center.appendChild(btnExcluir);
	center.appendChild(btnPrincipal);

	$(divFechar).css({
		"position":"absolute",
		"right":"5px",
		"top":"5px"
	});
	
	$(div).attr({
		enlargePicture:true
	}).css({
		"background-color":"RGBA(255,255,255,0.5)",
		"border":"1px solid #666666",
		"padding":"10px",
		"position":"absolute"
	}).css3({
		"border-radius":"5px"
	});

	$(img).attr({
		src:$(this).attr("src")
	}).css({
		"background-color":"#FFFFFF",
		"border":"1px solid #666666",
		"margin-bottom":"10px"
	}).bind("load",function(){
		var max_height = 400;
		var max_width = 600;
		if($(this).height() > max_height){
			$(this).height(max_height);
		}
		if($(this).width() > max_width){
			$(this).width(max_width);
		}
		$(this).parent().center({
			left:true,
			top:true
		});
	}).trigger("load");
	
	$(button).val("X").css({
        "height":"20px",
        "width":"20px",
        "text-align":"center",
        "padding": "0px"
    }).bind("click",function(){
		$("div[enlargePicture]").remove();
		$.background(false);
	}).bind("keydown",function(e){
		if(keycode(e) == 27){ // Esc
			$(this).trigger("click");
		}
	}).focus();

	$(btnExcluir).val("Excluir").css({
        "height":"30px",
        "width":"60px",
        "background-color":"red",
        "color": "white",
        "float": "right",        
        "background-image": "none"
    }).attr("title", "Excluir Imagem").bind("click",function(){
		var imagem = $(this).parent().parent().find('img').attr('src').split('=')[1];		
    	//Fecho o modal
    	$("div[enlargePicture]").remove();
		$.background(false);
		//Deleto o arquivo da pasta
		alert(arquivo_deletar(imagem));
		//Retiro a imagem da tela
		$(".imgProd").each(function(){
			if($(this).attr('src').split('?')[0] == imagem){
				$(this).remove();
			}
		});
	});

	$(btnPrincipal).val("Destaque").css({
		"height":"30px",
        "width":"60px",       
        "float": "left",
        "text-align":"center",
        "padding": "0px"        
	}).attr("title", "Definir imagem como destaque").bind("click", function(){
		var imagem = $(this).parent().parent().find('img').attr('src').split('=')[1];				
		if(imagem.split("/").reverse()[0] == "0.jpg"){
			alert("Essa imagem já está marcada como destaque");
			return false;
		}		
		defineImagemDestaque(imagem, this);
	});
}