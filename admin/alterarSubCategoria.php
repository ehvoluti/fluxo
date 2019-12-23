<?php

require("../include/config.php");

if ($_POST) {
    alterar("subcatlancto", "codsubcatlancto={$_GET["id"]}",$_POST);
    header('Location: subcategoria.php');
 }

if($_GET["id"]){
    $tipo = ver("subcatlancto", "*", "codsubcatlancto={$_GET["id"]}");
}

?>
<?php include("SubCategoriaForm.php"); ?>

<!--<p id="teste"> Teste de texto aqui</p> -->


<?php include("rodape.php"); ?>

<script>
    var codsubcatlancto = document.getElementById('codsubcatlancto')
    var descricao = document.getElementById('descricao')
    var codcatlancto = document.getElementById('codcatlancto')
    var botao = document.getElementById('')
    var page = 'ajax/ver.php'
    var valget = location.href
    let temp = "0"

    valget = `codsubcatlancto=`+valget.substr(valget.search('=')+1) 
    //str.substr(str.search("W3")+1);
    //descricao.value="teste aqui"
		$.ajax
			({
				type: 'GET',
				dataType: 'html',
				url: page,
				beforeSend: function() {
					$("#codsubcatlancto").html("Carrengado...");
				},
				data: {tabela: "subcatlancto", campos: "*", valor: valget},
                success: function(msg) {
                    resp = msg.split(":")
                    //$("#teste").html(resp[1])
                    $("#codsubcatlancto").val(resp[0])
                    $("#descricao").val(resp[1])
                    temp = resp[2]
                    document.getElementById('codcatlancto').value = resp[2]
            }
			});
        
</script>