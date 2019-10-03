$.postWindow = function(url, data){
	// Cria o form
	var form = document.createElement("form");
	$(form).attr({
		action: url,
		method: "POST",
		target: "_blank"
	}).hide();

	// Cria os inputs com os dados desejados
	var input = null;
	for(var name in data){
		input = document.createElement("textarea");
		input.name = name;
		input.value = data[name];
		form.appendChild(input);
	}

	// Inclui o form e executa o submit
	document.body.appendChild(form);
	form.submit();
	
	// Remove o form no final de tudo
	$(form).remove();
};