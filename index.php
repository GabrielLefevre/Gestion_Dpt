<?php
/**
 * Created by PhpStorm.
 * User: gabriel.lefevre
 * Date: 27/10/16
 * Time: 12:21
 */
function __autoload($classname) {
    $filename ="./".$classname.".php";
    include_once($filename);
}

$exec = new Traitement("S2");

$exec->generationFichiersXML();