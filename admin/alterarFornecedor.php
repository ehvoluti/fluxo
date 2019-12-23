<?php

require("../include/config.php");

if ($_POST) {
    alterar("fornecedor", "codfornec={$_GET["id"]}",$_POST);
    header('Location: fornecedor.php');
 }

if($_GET["id"]){
    $tipo = ver("fornecedor", "*", "codfornec={$_GET["id"]}");
}

?>
<?php include("FornecedorForm.php"); ?>

<!--<p id="teste"> Teste de texto aqui</p> -->


<?php include("rodape.php"); ?>

<script>
    var codfornec = document.getElementById('codfornec')
    var nome = document.getElementById('nome')
    var codbanco = document.getElementById('codbanco')
    var codcatlancto = document.getElementById('codcatlancto')
    var codsubcatlancto = document.getElementById('codsubcatlancto')
    var botao = document.getElementById('')
    var page = 'ajax/ver.php'
    var valget = location.href

    valget = `codfornec=`+valget.substr(valget.search('=')+1) 
    //str.substr(str.search("W3")+1);
    //descricao.value="teste aqui"
		$.ajax
			({
				type: 'GET',
				dataType: 'html',
				url: page,
				beforeSend: function() {
					$("#codfornec").html("Carrengado...");
				},
				data: {tabela: "fornecedor", campos: "*", valor: valget},
                success: function(msg) {
                    resp = msg.split(":")
                    //$("#teste").html(resp[1])
                    $("#codfornec").val(resp[0])
                    $("#nome").val(resp[1])
                    document.getElementById('codbanco').value = resp[2]
                    document.getElementById('codcatlancto').value = resp[3]
                    document.getElementById('codsubcatlancto').value = resp[4]
            }
			});
        
</script>