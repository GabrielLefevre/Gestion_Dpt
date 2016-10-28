<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>index</title>
</head>
<body>


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





$exec = new Traitement("S1");

//$exec->generationFichiersXML();
$exec->dataActivity();
?>
</body>
</html>