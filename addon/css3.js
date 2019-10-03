// Os parametros devem ser passados no mesmo formato que a funcao attr() e css()
$.fn.css3 = function(style,value){
	var types = ["","-icab-","-khtml-","-moz-","-o-","-webkit-"];
	$(this).each(function(){
		if(typeof(style) == "string"){
			for(var i in types){
				$(this).css(types[i] + style,value);
			}
		}else{
			for(var i in types){
				for(var j in style){
					$(this).css(types[i] + j,style[j]);
				}
			}
		}
	});
	return this;
}