<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <style>
    table, td, th {
        border: 1px solid black;
    }
    table {
        border-collapse: collapse;
    }
    </style>
</head>
<body>

<?php 
$executionStartTime = microtime(true);

require 'functions.php';

$totalKm = 2000;        // bendras maršruto ilgis
$limitKm = 485;         //parenkamas maksimalus paieškos ratas
##########################################################################################
//Jungiames prie duomenu bazes:
$conn = connect();

##########################################################################################
## Įvedame į formą pradinius duomenis
form1($conn);
// Į formą įvestų naujų duomenų išsaugojimas Duomenų bazėje
if (isset($_POST['send'])){

    $lat1 = $_POST['latitude'];
    $long1 = $_POST['longitute'];
    ValidateLatLng($lat1, $long1);

    delete($conn, 'geocodes');
    insertForm($conn);   

    $availableFactories[] = ['','home', $lat1, $long1,'', 0, 0, 0];

##########################################################################################
//Nuskaitomas failas ir išrinkti duomenys įrašomi į duomenš bazę
    $file = fopen("DataFiles/geocodes.csv","r");
    $row = 0;
    while (($getData = fgetcsv($file, 10000, ",")) !== FALSE)
	{
        $row++;
        if ($row == 1) continue;      
        
        $lat2 = $getData[2];
        $long2 = $getData[3];
 
        $dist = getDistance($lat1, $long1, $lat2, $long2);
                
        if ( ($dist <= $limitKm)  && (count(beerTypes($getData)) > 0)){
            $getData[] = $dist;
            $getData[] = factoryName($getData);
            $getData[] = count(beerTypes($getData));                   
            $availableFactories[] = $getData;
            insert($conn, $availableFactories);
        }           
    } 
    
    fclose($file);
    echo "<h2> Įvestos Pradinės kordinatės: latitude: " .$lat1 . ",  latitude: " . $long1 ."</h2>";

##########################################################################################
//Spausdinamas galimų gamyklų sąrašas

//echo "<h1>Galimų aplankyti gamyklų sąrašas:</h1>";
//table1($conn);

##########################################################################################
//Sudarome masyvą gamyklos id - 2 artimiausių gamyklų id - atstumas tarp gamyklų
//Masyvas surūšiuojamas pagal id ir atstumą

foreach ($availableFactories as $factory){
    $a = [];
    foreach ($availableFactories as $stop){ 
        if ($factory[1] != $stop[1] /* && ($factory[7] > 0) */){
            $betweendest = getDistance($factory[2], $factory[3], $stop[2], $stop[3]);
            $homedest = getDistance($lat1, $long1, $stop[2], $stop[3]);

            $a [] = [$factory[1], $stop[1], $betweendest, $homedest];
        }      
    } 
    sortBy(2, $a, 'asc');
    //pasirenkame kelių artimiausių maršrutų matricą sudarysime
    for ($i =0; $i < 10; $i++){
        $testFactDb [] = $a[$i];
    }    
}
##########################################################################################
//Spausdiname gautą galimų gamyklų ir atstumų tarp jų sąrašas
delete($conn, 'routes');
foreach ($testFactDb as $db){
    //echo $db[0] .", " .$db[1] .", " .$db[2] .", " .$db[3]."<br>";
    insertroutes($conn, $db);
}
##########################################################################################
//Randame arčiausiai home esančią gamyklą
foreach($testFactDb as $db){
    if ($db[0] == 'home'){
        $nextDestId = $db;
        $finalDistance = $db[2]; +  $db[3];
        break;
    }   
}

$routeDistance = 0;
while ($finalDistance < $totalKm){   
    $visited [] = $nextDestId; 
    $routeDistance += $nextDestId[2];
    $final = [$nextDestId[1], 'home', $nextDestId[3], 0];
    $nextDestId = nextDest($testFactDb, $nextDestId, $visited);
        if ($nextDestId[1] == 'home'){
            break;
        }    
    $finalDistance = $routeDistance + $nextDestId[2] +$nextDestId[3];
    
}
$visited [] = $final;
$routeDistance += $final[2];

##########################################################################################
//Spausdinamos aplankytos gamyklos;
echo "<h1>Aplankytos alaus gamyklos:</h1>";
table2($visited, $availableFactories);
echo "<h2>Bendras kelionės atstumas: $routeDistance km</h2>";
##########################################################################################
//Spausdinam kiek surinkta alaus rūšių: 

foreach ($visited as $unit){  
    $beerList [] = beerList($unit);    
} 

echo "<h1>Parsivežtos alaus rūšys:</h1>";
table3($beerList);

   
##########################################################################################
// Skaičiuojama kiek laiko trunka programa
$executionEndTime = microtime(true);
 
$seconds = $executionEndTime - $executionStartTime;
 
echo "<h3>This script took $seconds to execute.</h3>";
}
?>
    
</body>
</html>