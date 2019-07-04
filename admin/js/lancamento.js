/*
  Carregar referencia ao passar com o Mouse
  */
$(function(){
		$('[data-toggle="tooltip"]').tooltip()
	});

/* Mostra nome do fornecedor após seleção do Código do mesmo */
function getFornec()
{
	var selector = document.getElementById('codparceiro').value;
	var passavalor 		 = document.getElementById('dtlfornec').options.namedItem(selector).text;
	document.getElementById("fornecedor").innerHTML = passavalor; 

}

/*verifica o tamanho da referencia digitada */
function limitachar() {
	var texto = document.getElementById('referencia').value;
	var msglimite = document.getElementById('limitachar');
	msglimite.innerHTML = `Recebe texto`;
	if (texto.length > 35 ) {
		msglimite.innerHTML = `${texto.length}: (Ultrapassou limite de 35 caracteres)`
		msglimite.style.color = 'red';
	} else {
		msglimite.style.color = 'rgb(146, 143, 143)';
		msglimite.innerHTML = `${texto.length}`;
	}		
  
	/*
	var texto = $('#referencia').val();

  if (texto.length > 5 ) {
	$('.limitachar').text((texto.length) + ': (Ultrapassou limite de 35 caracteres)');
	
	//document.span.style.color = 'rgb(138, 118, 7)';

  } else {
	   $('.limitachar').text(texto.length);
  }
*/
}
