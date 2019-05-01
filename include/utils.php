<?php

function encurtar($texto, $limite) 
{
    if(strlen($texto) > $limite) {
        $texto = substr($texto, 0, $limite);
        $texto.= '...';
    }
    
    return $texto;
}

function verifica_pagina($link) 
{
    if(basename($_SERVER['PHP_SELF']) == $link['url']) { 
        return true; 
    }
    return false;
}