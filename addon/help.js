$.help = function(){
    $.background(true);
    
    var div_base = document.createElement("div");
    $(div_base).attr({
        "__help":true
    }).css({
        "height":"210px",
        "width":"260px"
    }).center({"left":true,"top":true});
    document.body.appendChild(div_base);
    
    var div_caixa = document.createElement("div");
    $(div_caixa).css({
        "background":"-webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(149,149,149,1)), color-stop(46%,rgba(13,13,13,1)), color-stop(50%,rgba(1,1,1,1)), color-stop(53%,rgba(10,10,10,1)), color-stop(76%,rgba(78,78,78,1)), color-stop(87%,rgba(56,56,56,1)), color-stop(100%,rgba(27,27,27,1)))",
        "border-radius":"10px",
        "height":"90px",
        "padding":"20px",
        "width":"200px"
    });
    div_base.appendChild(div_caixa);
    
    btn_documento = document.createElement("button");
    $(btn_documento).attr({
        "__help_documento":true
    }).css({
        "height":"90px",
        "width":"90px",
        "margin-right":"20px"
    }).html("<img src='../img/help_documento.png'><div style='color:#444; margin-top:5px'>Documento</div>");
    div_caixa.appendChild(btn_documento);
    
    btn_video = document.createElement("button");
    $(btn_video).attr({
        "__help_video":true
    }).css({
        "height":"90px",
        "width":"90px"
    }).html("<img src='../img/help_video.png'><div style='color:#444; margin-top:5px'>V&iacute;deo</div>");
    div_caixa.appendChild(btn_video);
    
    btn_fechar = document.createElement("input");
    $(btn_fechar).attr({
        "src":"../img/help_fechar.png",
        "title":"Fechar janela",
        "type":"image"
    }).css({
        "left":"243px",
        "position":"absolute",
        "top":"5px"
    }).bind("click",function(){
        $("[__help]").remove();
        $.background(false);
    }).fadeTo(0,0.75);
    div_base.appendChild(btn_fechar);
    
    $.ajax({
        async:false,
        url:"../ajax/help_verificararquivos.php",
        data:({
            idtable:idtable
        }),
        success:function(html){
            extractScript(html);
        }
    });
}