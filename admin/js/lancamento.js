/*
  Carregar referencia ao passar com o Mouse
  */
$(function(){
		$('[data-toggle="tooltip"]').tooltip()
	});

/* Mostra nome do Categoria e SubCat após seleção do Código do mesmo */
function getFornec()
{
	var selector = document.getElementById('codparceiro').value;
	var codparceiro = selector.split(":")
	codparceiro[0]
	//Tratamento para carregar a Categoria e Sub do fornecedor selecionado
	$.ajax({
		url: 'ajax/ver_categ_fornec.php',
		data:{tabela:'fornecedor',campos:` (SELECT cat.descricao||' >> '||sub.descricao FROM catlancto as cat INNER JOIN subcatlancto as sub ON (cat.codcatlancto=sub.codcatlancto) WHERE sub.codsubcatlancto=fornecedor.codsubcatlancto) as categ `,valor:`codfornec=`+codparceiro[0]},
		success:function(retorno){
			//console.log(retorno)
			document.getElementById("fornecedor").innerHTML = retorno; 
		}
	})

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


//Buscar Saldo do banco ao passar codbanco
function versaldo(valor) {
	//console.log(valor)
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