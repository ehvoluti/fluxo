// Retorna os elementos da janela pai (funcao usada em frames)
$.parentSelector = function(selector){
	return $(parent.document).contents().find(selector);
};