function loadSubCat(){

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
            //codsubcatlancto.empty();
            console.log (valget)
	
            
        //Este AJAX carrega o combo do subgrupo    
        $.ajax ({
            url:listarSubCat,
            data:{tabela:"subcatlancto", campos:"*", valor:`codcatlancto=`+idcatlancto},
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
 

}