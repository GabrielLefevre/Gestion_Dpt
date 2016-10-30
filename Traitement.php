
<?php

class Traitement {

    private $bdd;
    private $semestre;

    /**
     * Traitement constructor, connexion à la BDD.
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

    /**
     * Fonction generant le fichier professeurs.xml
     * <Teachers_List>
    <Teacher>
    <Name>G. Audemard</Name>
    </Teacher>
    <Teacher>
    <Name>F. Boussemart</Name>
    </Teacher>
     */

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


    /**
     * Fonction generant le fichier matieres.xml
     * <Subjects_List>
    <Subject>
    <Name>Projet</Name>
    </Subject>
    <Subject>
    <Name>PPP</Name>
    </Subject>
    <Subject>
    <Name>SE-2</Name>
    </Subject>
     */

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


    /**
     * fonction qui recupere les information de la table disponibilite et les convertie dans un tableau en string qui correspondent aux créneaux de l'IUT
     */

    function traitementHoraire($tabH, $tabJ) {
        $tabDispo = array();
        for ($i=0;$i<5;$i++) {
            if(array_key_exists($tabJ[$i],$tabH)) {
                if(in_array(8,$tabH[$tabJ[$i]]) && in_array(9,$tabH[$tabJ[$i]])  ) {
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


    /**
     * Fonction generant le fichier dispo.xml
     * <ConstraintTeacherNotAvailableTimes>
    <Weight_Percentage>100</Weight_Percentage>
    <Teacher>Coste</Teacher>
    <Number_of_Not_Available_Times>24</Number_of_Not_Available_Times>
    <Not_Available_Time>
    <Day>Lundi</Day>
    <Hour>11:30-12:45</Hour>
    </Not_Available_Time>
    <Not_Available_Time>
    <Day>Lundi</Day>
    <Hour>12:45-14:15</Hour>
    </Not_Available_Time>
     */

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

    /**
     * Fonction qui recupère les informations dans la BDD qui vont permettre le traitement du fichier activity.xml
     */

    function dataActivity() {
        $tabNbHoursSubject = array();
        $tabMatProf = array();

        $sql='select subject.shortname, nbcmhours, nbtdhours, nbtphours from subject join course on subject.id=course.subject_id where dept="INFO" and semester=?';
        $stmt=$this->bdd->prepare($sql);
        $stmt ->bindParam(1,$this->semestre);
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
        $stmt ->bindParam(1,$this->semestre);
        $stmt->execute();
        while($ligne = $stmt->fetch(PDO :: FETCH_ASSOC)){
            if(in_array( $ligne['shortname'],array_keys($tabNbHoursSubject))) {
                $tabMatProf[] = array($ligne['name'],$ligne['shortname']);
            }

        }
       // print_r($tabMatProf);
        $this->listeActivity($tabNbHoursSubject,$tabMatProf);
    }


    /**
     * Fonction generant le fichier activity.xml
     * <Activities_List>
    <Activity>
    <Teacher>-</Teacher>
    <Subject>Projet</Subject>
    <Activity_Tag>TD</Activity_Tag>
    <Students>groupe 1-A</Students>
    <Duration>1</Duration>
    <Total_Duration>6.00</Total_Duration>
    </Activity>
    <Activity>
    <Teacher>-</Teacher>
    <Subject>Projet</Subject>
    <Activity_Tag>TD</Activity_Tag>
    <Students>groupe 1-B</Students>
    <Duration>1</Duration>
    <Total_Duration>6.00</Total_Duration>
    </Activity>
     */

    function listeActivity($tabHS,$tabMP) {
        if($this->semestre=="S2") {
            $sem="S1";
        }
        else {
            $sem=$this->semestre;
        }
        $filename = 'activity.xml';
        if(!file_exists($filename)) {
            fopen("activity.xml","w+");
            echo "création fichier activity.xml".PHP_EOL;
        }
        file_put_contents('activity.xml', '');
        $xml="<Activities_List>".PHP_EOL;

        $tabCours=["CM","TD","TP"];
        for ($i=0;$i<count($tabHS);$i++) {
           // echo "<br />".key($tabHS)." "."<br/>"; // nom des matieres
            $tabProf = array();
            for ($z=0;$z<count($tabMP);$z++) {
                if(key($tabHS) == $tabMP[$z][1]) {
                    $sql='select shortname from teacher where name=?';
                    $stmt=$this->bdd->prepare($sql);
                    $stmt ->bindParam(1,$tabMP[$z][0]);
                    $stmt->execute();
                    $ligne = $stmt->fetch(PDO :: FETCH_ASSOC);
                    $tabProf[] = $ligne['shortname'];
                }
            }

            //print_r($tabProf); // tab des profs dispo pour la matiere

             for ($j=0;$j<3;$j++) {
                 if($tabHS[key($tabHS)][$tabCours[$j]] != 0.00) {
                    // echo $tabCours[$j]." : ".$tabHS[key($tabHS)][$tabCours[$j]]." "; // le cours + les heures

                     $sql='select name from groupe where semester=? and maingroupe=? and defaulttype=?';
                     $stmt=$this->bdd->prepare($sql);
                     $stmt ->bindParam(1,$sem);
                     $mainG="";
                     if($this->semestre == "S1" || $this->semestre == "S2") {
                         $mainG="INFO 1";
                     }
                     if($this->semestre == "S3" || $this->semestre == "S4") {
                         $mainG="INFO 2";
                     }
                     if($this->semestre == "S5") {
                         $mainG="LP Sécurite";
                     }
                     $stmt ->bindParam(2,$mainG);
                     $stmt ->bindParam(3,$tabCours[$j]);

                     $stmt->execute();
                     $tabG= array();
                     while($ligne = $stmt->fetch(PDO :: FETCH_ASSOC)){
                         $tabG[] = $ligne['name'];
                     }
                     //print_r($tabG);
                     for($x=0;$x<count($tabG);$x++) {
                         if((count($tabProf)-1)>=0) {
                             $rdm = rand(0,(count($tabProf)-1));
                             $xml.="<Activity>".PHP_EOL."    <Teacher>".$tabProf[$rdm]."</Teacher>".PHP_EOL."    <Subject>".key($tabHS)."</Subject>".PHP_EOL."    <Activity_Tag>".$tabCours[$j]."</Activity_Tag>".PHP_EOL;
                         }
                         else {
                             $xml.="<Activity>".PHP_EOL."    <Teacher>-</Teacher>".PHP_EOL."    <Subject>".key($tabHS)."</Subject>".PHP_EOL."    <Activity_Tag>".$tabCours[$j]."</Activity_Tag>".PHP_EOL;
                         }

                         $xml.="    <Students>".$tabG[$x]."</Students>".PHP_EOL."    <Duration>1</Duration>".PHP_EOL."    <Total_Duration>".$tabHS[key($tabHS)][$tabCours[$j]]."</Total_Duration>".PHP_EOL."</Activity>".PHP_EOL;
                     }


                 }

             }


             next($tabHS);

        }
        $xml.="</Activities_List>";
        file_put_contents("activity.xml",$xml);
        echo "Generation du fichiers activity.xml réussi ...".PHP_EOL;
    }


    /**
     * Fonction d'appel de generation des fichiers .xml
     */

    function generationFichiersXML() {
        $this->listeProfs();
        $this->listeMatieres();
        $this->listeDispoProfs();
        $this->dataActivity();
    }

}










