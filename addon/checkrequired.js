$.fn.checkRequired = function(){
	var retorno = true;
	$(this).each(function(){
		$(this).find("[required]").each(function(){
			if(trim($(this).val()).length == 0 && retorno){
				var element = this;
				$.messageBox({
					type:"error",
					title:"",
					text:"O campo " + $(element)[0].getAttribute("required") + " &eacute; de preenchimento obrigat&oacute;rio.",
					focusOnClose:element
				});
				retorno = false;
			}
		});
	});
	return retorno;
}