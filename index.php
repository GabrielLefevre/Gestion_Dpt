<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>index</title>
</head>
<body>

<?php
try
{
    $bdd = new PDO('mysql:host=localhost;dbname=gestdpt;charset=utf8', 'root', ''); // acces a la BDD
}
catch (Exception $e)
{
    die('Erreur : ' . $e->getMessage());
}


function listeProfs() {
    global $bdd;
    $filename = 'professeurs.xml';
    if(!file_exists($filename)) {
        fopen("professeurs.xml","w+");
        echo "création fichier professeurs.xml".PHP_EOL;
    }
    file_put_contents('professeurs.xml', '');

    $req = $bdd->query('SELECT shortname FROM teacher WHERE dept ="INFO" ');
    $xml = '<Teachers_List>'.PHP_EOL;
    while($ligne = $req->fetch()){
        $xml.="<Teacher>".PHP_EOL."    <Name>".$ligne['shortname']."</Name>".PHP_EOL."</Teacher>".PHP_EOL;
    }
    $xml.="</Teachers_List>".PHP_EOL;
    file_put_contents("professeurs.xml",$xml);
    echo "Generation du fichiers professeurs.xml réussi ...".PHP_EOL;

}// listeProfs

function listeMatieres($semestre) {
    global $bdd;
    $filename = 'matieres.xml';
    if(!file_exists($filename)) {
        fopen("matieres.xml","w+");
        echo "création fichier matieres.xml".PHP_EOL;
    }
    file_put_contents('matieres.xml', '');

    $req = $bdd->query('SELECT shortname FROM subject JOIN course ON subject.id=course.subject_id WHERE course.dept="INFO" AND course.semester="'.$semestre.'" ');
    $xml = '<Subjects_List>'.PHP_EOL;
    while($ligne = $req->fetch()){
        $xml.="<Subject>".PHP_EOL."    <Name>".$ligne['shortname']."</Name>".PHP_EOL."</Subject>".PHP_EOL;
    }
    $xml.="</Subjects_List>".PHP_EOL;
    file_put_contents("matieres.xml",$xml);
    echo "Generation du fichiers matieres.xml réussi ...".PHP_EOL;

}// listeMatieres

function listeDispoProfs() {
    global $bdd;
    $filename = 'dispo.xml';
    if(!file_exists($filename)) {
        fopen("dispo.xml","w+");
        echo "création fichier dispo.xml".PHP_EOL;
    }
    file_put_contents('dispo.xml', '');

    $req = $bdd->query('SELECT t.name, d.* FROM teacher t JOIN disponibilite d ON t.id=d.teacher_id WHERE t.dept="INFO" ');
    $xml='<ConstraintTeacherNotAvailableTimes>'.PHP_EOL.'    <Weight_Percentage>100</Weight_Percentage>'.PHP_EOL;

    $tmp ="";
    $nbIndispo=0;
    $tabDispo = array();
    $tabJour=array('Lundi','Mardi','Mercredi','Jeudi','Vendredi');
    while ($ligne = $req->fetch())
    {
       $nom = $ligne['name'];
        if($tmp != $nom) {
            if($nbIndispo!=0) {
                $xml.="    <Teacher>".$tmp."</Teacher>".PHP_EOL."    <Number_of_Not_Available_Times>".$nbIndispo."</Number_of_Not_Available_Times>".PHP_EOL;
                $test = traitementHoraire($tabDispo,$tabJour);
                for ($i=0;$i<count($test);$i++) {
                    if(array_key_exists($tabJour[$i],$test)) {
                        for($j=0;$j<count(array_keys($test[$tabJour[$i]]));$j++) {
                            $xml.="    <Not_Available_Time>".PHP_EOL."        <Day>".$tabJour[$i]."</Day>".PHP_EOL."        <Hour>".$test[$tabJour[$i]][$j]."</Hour>".PHP_EOL."    </Not_Available_Time>".PHP_EOL;
                        }
                    }
                }

            } // if($nbIndispo!=0

            $nbIndispo=0;
            $tabDispo = array();
            $tmp = $nom;
            for ($i=8;$i<18;$i++) {
                if($ligne['h'.$i]== -1 || $ligne['h'.$i]== 0) {
                    $nbIndispo++;
                    $tabDispo[$ligne['jour']][]= $i;
                }
            }
        } // if($tmp != $nom)

        else {
            for ($i=8;$i<18;$i++) {
                if($ligne['h'.$i]== -1 || $ligne['h'.$i]== 0) {
                    $nbIndispo++;
                    $tabDispo[$ligne['jour']][]= $i;
                }
            }
        } // else

    } //while
    $xml.="    <Teacher>".$tmp."</Teacher>".PHP_EOL."    <Number_of_Not_Available_Times>".$nbIndispo."</Number_of_Not_Available_Times>".PHP_EOL;
    for ($i=0;$i<count($tabDispo);$i++) {
        if(array_key_exists($tabJour[$i],$tabDispo)) {
            for($j=0;$j<count(array_keys($tabDispo[$tabJour[$i]]));$j++) {
                $xml.="    <Not_Available_Time>".PHP_EOL."        <Day>".$tabJour[$i]."</Day>".PHP_EOL."        <Hour>".$tabDispo[$tabJour[$i]][$j]."</Hour>".PHP_EOL."    </Not_Available_Time>".PHP_EOL;
            }
        }
    }

    $xml.=" <Active>true</Active>".PHP_EOL."<Comments></Comments>".PHP_EOL."</ConstraintTeacherNotAvailableTimes>";
     file_put_contents("dispo.xml",$xml);
    echo "Generation du fichiers dispo.xml réussi ...".PHP_EOL;

} // listeDispoProfs

function traitementHoraire($tabH, $tabJ) {
    $tabDispo = array();
    for ($i=0;$i<count($tabH);$i++) {
        if(array_key_exists($tabJ[$i],$tabH)) {
           if(in_array(8,$tabH[$tabJ[$i]]) && in_array(9,$tabH[$tabJ[$i]]) ) {
               $tabDispo[$tabJ[$i]][] = "08:15-09:45";
           }
            if(in_array(10,$tabH[$tabJ[$i]]) && in_array(11,$tabH[$tabJ[$i]]) ) {
                $tabDispo[$tabJ[$i]][] = "10:00-11:30";
            }
            if(in_array(12,$tabH[$tabJ[$i]])) {
                $tabDispo[$tabJ[$i]][] = "11:30-12:45";
            }
            if(in_array(13,$tabH[$tabJ[$i]]) && in_array(14,$tabH[$tabJ[$i]]) ) {
                $tabDispo[$tabJ[$i]][] = "12:45-14:15";
            }
            if(in_array(14,$tabH[$tabJ[$i]]) && in_array(15,$tabH[$tabJ[$i]]) ) {
                $tabDispo[$tabJ[$i]][] = "14:15-15:45";
            }
            if(in_array(16,$tabH[$tabJ[$i]]) && in_array(17,$tabH[$tabJ[$i]]) ) {
                $tabDispo[$tabJ[$i]][] = "16:00-17:30";
            }
        }
    }
    return $tabDispo;
} // traitementHoraire




listeProfs();
listeMatieres("S1");
listeDispoProfs();
/*
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
}*/
?>

</body>
</html>



