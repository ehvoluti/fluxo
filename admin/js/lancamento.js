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
  
}



/*Ver Saldo em carteira */
/*
function versaldo() {
	var saldo4 = document.getElementById('saldo4')
	var textospan = saldo4.innerText
	var saldo = document.getElementById('saldotemp')
	//var saldo = "<?php $versaldo = saldo(4); echo $versaldo[saldo]; ?>"
	//	saldo4.innerHTML += teste
//	teste.innerHTML +=saldo4.innerText
	if (textospan.length==0) {
		saldo4.innerHTML = ` Saldo em pessoal = ` + saldo.innerText
	} else {
		saldo4.innerHTML = ``			
	}

}
*/

function versaldo(valor) {
	var saldo4 = document.getElementById('saldo4')
	var textospan = saldo4.innerText
	var page = 'ajax/saldobanco.php'
	if (textospan.length==0) {
		$.ajax
			({
				type: 'GET',
				dataType: 'html',
				url: page,
				beforeSend: function() {
					$("#saldo4").html("Carrengado...");
				},
				data: {valor: valor},
				success: function(msg) {$("#saldo4").html(msg)}
			});
	} else {
		saldo4.innerHTML = ``			
	}		
}