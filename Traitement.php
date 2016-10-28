
<?php

class Traitement {

    private $bdd;
    private $semestre;

    /**
     * Traitement constructor.
     */
    public function __construct($sem)
    {
        $this->semestre=$sem;
        try
        {
            //$this->bdd = new PDO('mysql:host=ipabdd.iut-lens.univ-artois.fr; dbname=gabriellefevre' , 'gabriel.lefevre' , 'JRQW0Dpt'); // acces a la BDD
            $this->bdd = new PDO('mysql:host=localhost; dbname=gestdpt' , 'root' , ''); // acces a la BDD

        }
        catch (Exception $e)
        {
            die('Erreur : ' . $e->getMessage());
        }
    }



    function listeProfs() {
        $filename = 'professeurs.xml';
        if(!file_exists($filename)) {
            fopen("professeurs.xml","w+");
            echo "création fichier professeurs.xml".PHP_EOL;
        }
        file_put_contents('professeurs.xml', '');
        $sql ='SELECT distinct shortname FROM teacher JOIN planning on teacher.id=planning.teacher_id JOIN course on planning.course_id=course.id WHERE teacher.dept ="INFO" and course.semester=? ';
        $stmt=$this->bdd->prepare($sql);
        $stmt ->bindParam(1,$this->semestre);
        $stmt->execute();
        $xml = '<Teachers_List>'.PHP_EOL;
        while($ligne = $stmt->fetch(PDO :: FETCH_ASSOC)){
            $xml.="<Teacher>".PHP_EOL."    <Name>".$ligne['shortname']."</Name>".PHP_EOL."</Teacher>".PHP_EOL;
        }
        $xml.="</Teachers_List>".PHP_EOL;
        file_put_contents("professeurs.xml",$xml);
        echo "Generation du fichiers professeurs.xml réussi ...".PHP_EOL;
    }// listeProfs


    function listeMatieres() {;
        $filename = 'matieres.xml';
        if(!file_exists($filename)) {
            fopen("matieres.xml","w+");
            echo "création fichier matieres.xml".PHP_EOL;
        }
        file_put_contents('matieres.xml', '');
        $sql='SELECT shortname FROM subject JOIN course ON subject.id=course.subject_id WHERE course.dept="INFO" AND course.semester=?';
        $stmt=$this->bdd->prepare($sql);
        $stmt ->bindParam(1,$this->semestre);
        $stmt->execute();
        $xml = '<Subjects_List>'.PHP_EOL;
        while($ligne = $stmt->fetch(PDO :: FETCH_ASSOC)){
            $xml.="<Subject>".PHP_EOL."    <Name>".$ligne['shortname']."</Name>".PHP_EOL."</Subject>".PHP_EOL;
        }
        $xml.="</Subjects_List>".PHP_EOL;
        file_put_contents("matieres.xml",$xml);
        echo "Generation du fichiers matieres.xml réussi ...".PHP_EOL;
    }// listeMatieres


    function traitementHoraire($tabH, $tabJ) {
        $tabDispo = array();
        for ($i=0;$i<5;$i++) {
            if(array_key_exists($tabJ[$i],$tabH)) {
                if(in_array(8,$tabH[$tabJ[$i]]) || in_array(9,$tabH[$tabJ[$i]])  ) {
                    $tabDispo[$tabJ[$i]][] = "08:15-09:45";
                }
                if(in_array(10,$tabH[$tabJ[$i]]) || in_array(11,$tabH[$tabJ[$i]]) ) {
                    $tabDispo[$tabJ[$i]][] = "10:00-11:30";
                }
                if(in_array(12,$tabH[$tabJ[$i]])) {
                    $tabDispo[$tabJ[$i]][] = "11:30-12:45";
                }
                if(in_array(13,$tabH[$tabJ[$i]]) || in_array(14,$tabH[$tabJ[$i]]) ) {
                    $tabDispo[$tabJ[$i]][] = "12:45-14:15";
                }
                if(in_array(14,$tabH[$tabJ[$i]]) || in_array(15,$tabH[$tabJ[$i]]) ) {
                    $tabDispo[$tabJ[$i]][] = "14:15-15:45";
                }
                if(in_array(16,$tabH[$tabJ[$i]]) || in_array(17,$tabH[$tabJ[$i]]) ) {
                    $tabDispo[$tabJ[$i]][] = "16:00-17:30";
                }
            }
        }
        return $tabDispo;
    } // traitementHoraire

    function listeDispoProfs() {
        $filename = 'dispo.xml';
        if(!file_exists($filename)) {
            fopen("dispo.xml","w+");
            echo "création fichier dispo.xml".PHP_EOL;
        }
        file_put_contents('dispo.xml', '');
        $req = $this->bdd->query('SELECT t.name, d.* FROM teacher t JOIN disponibilite d ON t.id=d.teacher_id WHERE t.dept="INFO" and d.dept="INFO" ');
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
                    $test = $this->traitementHoraire($tabDispo,$tabJour);
                    $nbCreneau = count($test,COUNT_RECURSIVE)-count($test);
                    $xml.="    <Teacher>".$tmp."</Teacher>".PHP_EOL."    <Number_of_Not_Available_Times>".$nbCreneau."</Number_of_Not_Available_Times>".PHP_EOL;

                    for ($i=0;$i<5;$i++) {
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
        $test = $this->traitementHoraire($tabDispo,$tabJour);
        $nbCreneau = count($test,COUNT_RECURSIVE)-count($test);
        $xml.="    <Teacher>".$tmp."</Teacher>".PHP_EOL."    <Number_of_Not_Available_Times>".$nbCreneau."</Number_of_Not_Available_Times>".PHP_EOL;

        for ($i=0;$i<5;$i++) {
            if(array_key_exists($tabJour[$i],$test)) {
                for($j=0;$j<count(array_keys($test[$tabJour[$i]]));$j++) {
                    $xml.="    <Not_Available_Time>".PHP_EOL."        <Day>".$tabJour[$i]."</Day>".PHP_EOL."        <Hour>".$test[$tabJour[$i]][$j]."</Hour>".PHP_EOL."    </Not_Available_Time>".PHP_EOL;
                }
            }
        }
        $xml.=" <Active>true</Active>".PHP_EOL."<Comments></Comments>".PHP_EOL."</ConstraintTeacherNotAvailableTimes>";
        file_put_contents("dispo.xml",$xml);
        echo "Generation du fichiers dispo.xml réussi ...".PHP_EOL;
    } // listeDispoProfs

    function dataActivity() {
        $tabGroup = array();
        $tabNbHoursSubject = array();
        $tabMatProf = array();
        if($this->semestre=="S2") {
            $sem="S1";
        }
        else {
            $sem=$this->semestre;
        }

        $sql='SELECT name FROM groupe WHERE groupe.dept="INFO" AND groupe.semester=?';
        $stmt=$this->bdd->prepare($sql);
        $stmt ->bindParam(1,$sem);
        $stmt->execute();
        while($ligne = $stmt->fetch(PDO :: FETCH_ASSOC)){
            $tabGroup[]=$ligne['name'];
        }

        $sql='select subject.shortname, nbcmhours, nbtdhours, nbtphours from subject join course on subject.id=course.subject_id where dept="INFO" and semester=?';
        $stmt=$this->bdd->prepare($sql);
        $stmt ->bindParam(1,$sem);
        $stmt->execute();
        $k=0;
        while($ligne = $stmt->fetch(PDO :: FETCH_ASSOC)){
            if($ligne['nbcmhours'] !== null && $ligne['nbtdhours']!== null && $ligne['nbtphours']!== null ) {
                $tabNbHoursSubject[]=$ligne['shortname'];
                $tabNbHoursSubject[$ligne['shortname']]['CM'] = $ligne['nbcmhours'];
                $tabNbHoursSubject[$ligne['shortname']]['TD'] = $ligne['nbtdhours'];
                $tabNbHoursSubject[$ligne['shortname']]['TP']= $ligne['nbtphours'];
                unset($tabNbHoursSubject[$k]);
                $k++;
            }

        }

        $sql='select t.name, s.shortname from teacher t join planning pl on t.id=pl.teacher_id join course c on pl.course_id=c.id join subject s on c.subject_id=s.id where t.dept="INFO" and c.semester=? order by s.shortname';
        $stmt=$this->bdd->prepare($sql);
        $stmt ->bindParam(1,$sem);
        $stmt->execute();
        $i=0;
        while($ligne = $stmt->fetch(PDO :: FETCH_ASSOC)){
            if(in_array($ligne['shortname'],$tabNbHoursSubject)) {
                $tabMatProf[] =  $ligne['name'];
                $tabMatProf[$ligne['name']] =  $ligne['shortname'];
                unset($tabMatProf[$i]);
                $i++;
            }
        }
        //print_r($tabNbHoursSubject);
        $this->listeActivity($tabNbHoursSubject,$tabMatProf);
    }

    function listeActivity($tabHS,$tabMP) {
        if($this->semestre=="S2") {
            $sem="S1";
        }
        else {
            $sem=$this->semestre;
        }
        for ($i=0;$i<count($tabHS);$i++) {
            for($j=0;$j<3;$j++) {
               // if($tabHS[$i][$j]!==0) {
                   // echo $tabHS[$i].PHP_EOL;
                //}
            }
        }
    }

    /*
     * [PPP] => Array
            (
                [CM] => 0.00
                [TD] => 12.00
                [TP] => 0.00
            )

        [SE-1] => Array
            (
                [CM] => 12.00
                [TD] => 24.00
                [TP] => 24.00
            )
     */


    function generationFichiersXML() {
        $this->listeProfs();
        $this->listeMatieres();
        $this->listeDispoProfs();
    }

}










