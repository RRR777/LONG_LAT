
<?php 
##########################################################################################
//Prisijungimas prie Duomenų Bazės
function connect(){
    $servername = 'localhost';
    $dbname = 'distance';
    $username = 'root';
    $password = 'nif02081';

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die('Nepavyko prisjungti: ' . $conn->connect_error);
    }
    return $conn;
}
##########################################################################################
//Pradinių duomenų Įvedimo forma
function form1($conn){ 
?>
<h1>Įveskite duomenis:</h1>
<form action="" method="post">
    <input type="hidden" name="brewery_id" value="home">              
    Įveskite latitude koordinates: <br>
    <input type="text" name="latitude"><br> 
    Įveskite longitude koordinates: <br>
    <input type="text" name="longitute"><br>
    <input type="hidden" name="accuracy" value="">  
    <input type="hidden" name="distance" value=0>
    <input type="hidden" name="factoryName" value="home"> 
    <input type="hidden" name="beerTypes" value=0><br> 
    <input type="submit" name ="send" value="Patvirtinti"><br><br>
</form>

<?php } ?>
<?php 
##########################################################################################
//Duomenų bazės ištrynimas
function delete($conn, $tableName){
    $sql = "DELETE FROM $tableName"; 
    $stmt = $conn->prepare($sql);
    $stmt->execute();    
}
##########################################################################################
//Long/lat koordinačių tikrinimas
function ValidateLatLng($lat1, $long1) {
    if ($lat1< -90 || $lat1> 90) {
        echo "<h2>Latitude reikšmė turi būti daugiau nei -90 ir mažiau kaip 90 laipsnių.</h2>";
    } else if ($long1< -180 || $long1> 180) {
        echo "<h2>Longitude reikšmė turi būti daugiau nei  -180 ir mažiau kaip 180 laipsnių.</h2>";
    } else if ($long1== "" || $long1 == "") {
        echo "<h2>Įveskite Latitude ir Longitude reikšmes!</h2>";
    }
}
##########################################################################################
//Įrašymas į DB iš Formos
function insertForm($conn){
    $stmt = $conn->prepare("INSERT INTO `geocodes` (`brewery_id`, `latitude`, `longitude`, `accuracy`, `distance`, `factoryName`, `beerTypes`) VALUES(?, ?, ?, ?, ?, ?, ?)");
   
    $brewery_id = mysqli_real_escape_string($conn, $_POST['brewery_id']);
    $latitude = mysqli_real_escape_string($conn,  $_POST['latitude']);
    $longitute = mysqli_real_escape_string($conn, $_POST['longitute']);
    $accuracy = mysqli_real_escape_string($conn, $_POST['accuracy']);
    $distance = mysqli_real_escape_string($conn, $_POST['distance']);
    $factoryName = mysqli_real_escape_string($conn,$_POST['factoryName']);
    $beerTypes = mysqli_real_escape_string($conn, $_POST['beerTypes']);

    $stmt->bind_param("sddsdsi", $brewery_id, $latitude, $longitute, $accuracy, $distance, $factoryName, $beerTypes);
    $stmt->execute(); 
}
##########################################################################################
// Skaičiuojamas atstumas tarp dviejų taškų
function getDistance($lat1, $long1, $lat2, $long2){
        $earth_radius = 6371;
 
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($long2 - $long1);
    
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * asin(sqrt($a));
        $d = $earth_radius * $c;
    
        return round($d);
}
##########################################################################################
//Nuskaitomas gamyklos pavadinimas
function factoryName($getData){
$row = 0;
$file2 = fopen("DataFiles/breweries.csv","r");
    while (($getData2 = fgetcsv($file2, 10000, ",")) !== FALSE)
	{       
        $row++;
        if ($row == 1) continue;   

        if ($getData2[0] == $getData[1]){
            return $getData2[1];
        }                 
    } 
fclose($file2);
}
    
##########################################################################################
//Nuskaitomos alaus rūšys
function beerTypes($factory){
$row = 0;
$file = fopen("DataFiles/beers.csv","r");
$types = [];
    while (($getData = fgetcsv($file, 10000, ",")) !== FALSE)
	{       
        $row++;
        if ($row == 1) continue;   

        if ($getData[1] == $factory[1] &&  $factory[1] != 'home'){
             $types [] = $getData[2];            
        }             
    } 
    
    return $types;
fclose($file);
}
##########################################################################################
//Nuskaitomos alaus rūšys
function beerList($unit){
$row = 0;
$file = fopen("DataFiles/beers.csv","r");
$types = [];

    while (($getData = fgetcsv($file, 10000, ",")) !== FALSE)
	{       
        $row++; 
        if ($row == 1) continue;   

        if ($getData[1] == $unit[0]){
             $types [] = $getData[2];                   
        }                    
    } 
    if ($types != ""){
        return $types;
    }
fclose($file);
}
##########################################################################################
//Įrašymas į DB iš .csv failo
function insert($conn, $availableFactories){
    $stmt = $conn->prepare("INSERT INTO `geocodes` (`brewery_id`, `latitude`, `longitude`, `accuracy`, `distance`, `factoryName`, `beerTypes`) VALUES(?, ?, ?, ?, ?, ?, ?)");
   
    foreach ($availableFactories as $line){
        $brewery_id = mysqli_real_escape_string($conn, $line[1]);
        $latitude = mysqli_real_escape_string($conn,  $line[2]);
        $longitute = mysqli_real_escape_string($conn, $line[3]);
        $accuracy = mysqli_real_escape_string($conn, $line[4]);
        $distance = mysqli_real_escape_string($conn, $line[5]);
        $factoryName = mysqli_real_escape_string($conn,$line[6]);
        $beerTypes = mysqli_real_escape_string($conn, $line[7]);
    }
    $stmt->bind_param("sddsdsi", $brewery_id, $latitude, $longitute, $accuracy, $distance, $factoryName, $beerTypes);
    $stmt->execute(); 
}
##########################################################################################
//Galimų gamyklų sąrašo spausdinimas lentelėje:
function table1($conn){
    $sql = "SELECT * FROM `geocodes`ORDER BY `distance`";  

    $result = $conn->query($sql);
    $nr = 0;
    if ($result->num_rows > 0): ?>
        <h3>Rūšiuojama pagal distance, didėjimo tvarka: </h3>
        <table>
            <tr>
                <th bgcolor="#CCCCCC">Nr.</th>
                <th bgcolor="#CCCCCC">Brewery_id</th>
                <th bgcolor="#CCCCCC">Latitude</th>
                <th bgcolor="#CCCCCC">Longitude</th>
                <th bgcolor="#CCCCCC">Distance,km</th>
                <th bgcolor="#CCCCCC">Factory Name</th>
                <th bgcolor="#CCCCCC">Beer Types Qty</th>
            </tr>
            <?php while($row = $result->fetch_assoc()) :    // output data of each row?>
                <tr>
                    <td><?php echo ++$nr; ?></td>
                    <td><?php echo $row['brewery_id']; ?></td>
                    <td><?php echo $row['latitude']; ?></td>
                    <td><?php echo $row['longitude']; ?></td>
                    <td><?php echo $row['distance']; ?></td>
                    <td><?php echo $row['factoryName']; ?></td>
                    <td><?php echo $row['beerTypes']; ?></td>
                </tr>  
            <?php endwhile; ?>     
        </table>
    <?php else: 
      echo "Nėra duomenų.";
    endif;
}
##########################################################################################
function sortBy($field, &$array, $direction = 'asc')
{
    usort($array, create_function('$a, $b', '
        $a = $a["' . $field . '"];
        $b = $b["' . $field . '"];

        if ($a == $b)
        {
            return 0;
        }

        return ($a ' . ($direction == 'desc' ? '>' : '<') .' $b) ? -1 : 1;
    '));

    return true;
}
##########################################################################################
//Įrašymas į DB iš .csv failo
function insertroutes($conn, $db){
    $stmt = $conn->prepare("INSERT INTO `routes` (`fromFactoryId`, `toFactoryId`, `betweendest`, `homedest`) VALUES(?, ?, ?, ?)");
   
        $fromFactoryId = mysqli_real_escape_string($conn, $db[0]);
        $toFactoryId = mysqli_real_escape_string($conn,  $db[1]);
        $betweendest = mysqli_real_escape_string($conn, $db[2]);
        $homedest = mysqli_real_escape_string($conn, $db[3]);

    $stmt->bind_param("ssdd", $fromFactoryId, $toFactoryId, $betweendest, $homedest);
    $stmt->execute(); 
}

##########################################################################################
//Suranda arčiausiai esančią gamyklą
function nextDest($testFactDb, $nextDestId, $visited){

    foreach ($testFactDb as $db){
        if ($db[0] == $nextDestId[1] && !visited($visited, $db[1]) ){
            $nextDestId = $db;

            return $nextDestId;                 
        }
    } 

     $final  = [$nextDestId[0], 'home', $nextDestId[3], 0];

    return $final;     
}
##########################################################################################
//Tikrina ar gamykla jau buvo aplankyta

function visited($visited, $db){
    foreach ($visited as $name ){
        if ($name[0] == $db){

            return true;
        }
    }
}
##########################################################################################
//Spausdinamas aplankytų gamyklų sąrašas

function table2($visited, $availableFactories){
       
 ?>
        <table>
            <tr>
                <th bgcolor="#CCCCCC">Nr.</th>
                <th bgcolor="#CCCCCC">Išvykimas</th>
                <th bgcolor="#CCCCCC">Atvykimas</th>
                <th bgcolor="#CCCCCC">Gamyklos pavadinimas </th>
                <th bgcolor="#CCCCCC">Latitude</th>
                <th bgcolor="#CCCCCC">Longitude</th>
                <th bgcolor="#CCCCCC">Atstumas,km</th>
                <th bgcolor="#CCCCCC">Alaus Rūšių Kiekis</th>
            </tr>
            
            <?php $nr = 0;
                foreach ($visited as $unit):
                    foreach($availableFactories as $factory):
                        if ($unit[0] == $factory[1]): ?>  
                            <tr> 
                                <td><?php echo ++$nr; ?></td>
                                <td><?php echo $unit[0]; ?></td>
                                <td><?php echo $unit[1]; ?></td>
                                <td><?php echo $factory[6]; ?></td>
                                <td><?php echo $factory[2]; ?></td>
                                <td><?php echo $factory[3]; ?></td>
                                <td><?php echo $unit[2]; ?></td>
                                <td><?php echo $factory[7]; ?></td>
                            </tr>
                        <?php endif ?>
                    <?php endforeach ?>
                <?php endforeach ?>
             
        </table>
    <?php 
}

##########################################################################################
//Spausdinamas parsivežtų alaus rušių sąrašas
function table3($beerTypes){
?>
        <table>
            <tr>
                <th bgcolor="#CCCCCC">Nr.</th>
                <th bgcolor="#CCCCCC">Alaus rūšis</th>
            </tr>
            
            <?php $nr = 0;
                foreach ($beerTypes as $beerType):
                    foreach($beerType as $beer): ?>  
                        <tr> 
                            <td><?php echo ++$nr; ?></td>
                            <td><?php echo $beer; ?></td>
                        </tr>
                    <?php endforeach ?>
                <?php endforeach ?>             
        </table>
    <?php 
}