<?php

require("../include/config.php");

if ($_POST) {
    alterar("catlancto", "codcatlancto={$_GET["id"]}",$_POST);
    header('Location: categoria.php');
 }

if($_GET["id"]){
    $tipo = ver("catlancto", "*", "codcatlancto={$_GET["id"]}");
}

?>
<?php include("CategoriaForm.php"); ?>

<!--<p id="teste"> Teste de texto aqui</p> -->

<?php include("rodape.php"); ?>

<script>
    var codcatlancto = document.getElementById('codcatlancto')
    var descricao = document.getElementById('descricao')
    var teste = document.getElementById('teste')
    var botao = document.getElementById('')
    var page = 'ajax/ver.php'
    var valget = location.href
    //valget = `codcatlancto=`+valget.substr(-1) 
    valget = `codcatlancto=`+valget.substr(valget.search('=')+1) 
    //str.substr(str.search("W3")+1);
    //descricao.value="teste aqui"
		$.ajax
			({
				type: 'GET',
				dataType: 'html',
				url: page,
				beforeSend: function() {
					$("#codcatlancto").html("Carrengado...");
				},
				data: {tabela: "catlancto", campos: "*", valor: valget},
                success: function(msg) {
                    resp = msg.split(":")
                    //$("#teste").html(resp[1])
                    $("#codcatlancto").val(resp[0])
                    $("#descricao").val(resp[1])
                }
                
			});

</script>