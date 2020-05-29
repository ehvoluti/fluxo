<?php

require("../include/config.php");

if ($_POST) {
    alterar("fornecedor", "codfornec={$_GET["id"]}",$_POST);
    header('Location: fornecedor.php');
    echo $_POST;
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
    var idcatlancto = codcatlancto[codcatlancto.selectedIndex].value;
    var codsubcatlancto = document.getElementById('codsubcatlancto')
    var botao = document.getElementById('')
    var page = 'ajax/ver.php'
    var listarSubCat = 'ajax/listar.php'
    var valget = location.href

    console.log(idcatlancto)
    valget = `codfornec=`+valget.substr(valget.search('=')+1) 
    //str.substr(str.search("W3")+1);
    //descricao.value="teste aqui"

            codsubcatlancto.innerHTML=''
            console.log (valget)
    
        //Carregando dados dos campos
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
                    
                    //document.getElementById('codsubcatlancto').value = resp[4]
            }
    });

       //Este AJAX carrega o combo do subgrupo    
        $.ajax ({
            url:listarSubCat,
            data:{tabela:"subcatlancto", campos:"*", valor:`codcatlancto=(SELECT codcatlancto FROM fornecedor WHERE `+valget+`)`},
            success: function(msg2){
                let resp2 = []
                resp2 = msg2.split(",")
                //console.log(x)
                //console.log(typeof resp2)
                //console.log(msg2[])
                for (let pos in resp2) {
                    console.log(resp2[pos].split(":"))
                    let item = document.createElement('option')
                    let descricao_subcat = resp2[pos].split(":")
                    item.text = descricao_subcat[1]
                    item.value = descricao_subcat[0]
                    if (item.value.length>0){
                        codsubcatlancto.appendChild(item)    
                    }
                    
                }
                
            }
        })
 



   /* 
    var codfornec = document.getElementById('codfornec')
    var nome = document.getElementById('nome')
    var codbanco = document.getElementById('codbanco')
    var codcatlancto = document.getElementById('codcatlancto')
    var codsubcatlancto = document.getElementById('codsubcatlancto')
    var botao = document.getElementById('')
    var page = 'ajax/ver.php'
    var listarSubCat = 'ajax/listar.php'
    var valget = location.href

    valget = `codfornec=`+valget.substr(valget.search('=')+1)

            //codsubcatlancto.innerHTML=''
            //console.log (valget)

        //Carregando dados dos campos
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
            }); */
</script>