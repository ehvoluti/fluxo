$.ajaxSetup({
	beforeSend: function (jqXHR, settings) {
		jqXHR.url = settings.url;
	},
	error: function (jqXHR) {
		switch (jqXHR.statusText) {
			case "abort":
				return true;
				break;
			default:
				// Registro o erro no log do navegador
				console.log(jqXHR);

				// Remove a tela de loading caso esteja visivel
				if ($("#cwLoadingScreen").is(":visible")) {
					$.loading(false);
					$.messageBox({
						text: "Desculpe, houve uma falha de conexão.<br>Por favor, tente novamente."
					});
				}

				// Registra o log de erros
				if (jqXHR.url.indexOf("logerror.php") === -1) {
					logError([
						"Erro ao executar arquivo AJAX:",
						"URL: " + jqXHR.url,
						"Status: " + jqXHR.status,
						"Status text: " + jqXHR.statusText,
						"\r\n" + jqXHR.responseText
					].join("\r\n"));
				}
				break;
		}
	}
});