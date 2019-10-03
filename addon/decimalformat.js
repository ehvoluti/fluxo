$.fn.decimalFormat = function(){
	// Quando sair dos campos decimais deve ser formatado
	$(this).filter("[mask^='decimal']").bind("blur",function(){
		if($(this).val().length > 0){
			var n = $(this).attr("mask").substr(7);
			var val = $(this).val();
			if(val.indexOf(",") === -1 && val.indexOf(",") === -1){
				// Nao faz nada
			}else if(val.indexOf(".") > -1 && val.indexOf(",") === -1){
				// Nao faz nada
			}else if(val.indexOf(".") === -1 && val.indexOf(",") > -1){
				val = val.replace(",", ".");
			}else if(val.indexOf(".") > val.indexOf(",")){
				val = val.replace(",", "");
			}else{
				val = val.replace(".", "").replace(",", ".");
			}
			$(this).val(number_format(val,n,",",""));
		}

	// Trata o uso da virgula no campo
	}).bind("keyup",function(e){
		// Se o primeiro caracter for a virgula, joga o zero na frente
		if($(this).val().substr(0,1) == ","){
			$(this).val("0" + $(this).val());
		}
		var value = "";
		var f_virg = false;
		for(var i = 0; i < $(this).val().length; i++){
			var c = $(this).val().substr(i,1);
			if(c == ","){
				if(f_virg == false){
					f_virg = true;
					value += c;
				}
			}else if(!isNaN(c)){
				value += c;
			}
		}
		var ss = this.selectionStart;
		var se = this.selectionEnd;
		$(this).val(value);
		this.selectionStart = ss;
		this.selectionEnd = se;
	}).css("text-align","right");
	return this;
}