$.fn.verifyRequired = function(settings){
	
	settings = $.extend({
		label: null, // Label alternativo para o campo (se nao preenchido, pega o conteudo do elemento label associado ao elemento a ser validado)
		text: null // Texto a ser exibido quando houve erro na validacao (se nao preenchido, monta uma mensagem padrao utilizando o label informado)
	}, settings);
	
	if($(this).val().length === 0){
		settings.label = (settings.label === null ? $("label[for='" + $(this).attr("id") + "']").text().toLowerCase() : settings.label);
		if(settings.label.substr(-1) === ":"){
			settings.label = settings.label.substr(0, (settings.label.length - 1));
		}
		settings.text = (settings.text === null ? "Por favor, preencha o campo <strong>" + settings.label + "</strong> antes de prosseguir." : settings.text);
		
		var messageBoxSettings = $.extend(settings, {
			text: settings.text,
			focusOnClose: this
		});
		
		$.messageBox(messageBoxSettings);
		
		return false;
	}else{
		return true;
	}
};