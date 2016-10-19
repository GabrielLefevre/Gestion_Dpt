<?php
try
{
    $bdd = new PDO('mysql:host=localhost;dbname=gestdpt;charset=utf8', 'root', ''); // acces a la BDD
}
catch (Exception $e)
{
    die('Erreur : ' . $e->getMessage());
}

$reponse = $bdd->query('SELECT * FROM subject LIMIT 0,5');



while ($donnees = $reponse->fetch())
    {
    ?>
    <p>
        <strong>nom</strong> : <?php echo $donnees['name']; ?><br />
        shortname : <?php echo $donnees['shortname']; ?><br />
        cnu : <?php echo $donnees['cnu']; ?>
    </p>
<?php
}
//affichage des 5 premieres matieres ( pour tester l'acces a la BDD )

$ress  = $bdd->query('SELECT * FROM subject LIMIT 0,10');

$xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        while($ligne = $ress->fetch()){
        $xml.="<item>".$ligne['name']."</item>";
        }
file_put_contents("matiere.xml",$xml);
// generation du fichier xml
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>index</title>
</head>
<body>
<p>test</p>
</body>
</html>



