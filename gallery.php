<html>
<head><title> My Gallery</title>
<link type="text/css" href="bottom.css" rel="stylesheet" />
<link href='https://fonts.googleapis.com/css?family=Open+Sans|Indie+Flower' rel='stylesheet' type='text/css'>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.js"></script>
<script type="text/javascript" src="jquery.jcarousel.min.js"> </script>
<script type="text/javascript" src="jquery.pikachoose.min.js"> </script>
<script type="text/javascript" src="jquery.touchwipe.min.js"> </script>
<script language="javascript">
$(document).ready(function(){
$("#pikame").PikaChoose({showToolTips:true});
});
</script>
</head>
<body>
<div class="header"> Image Gallery </div>
<div class="pikachoose">
<ul id ="pikame">
<?php
session_start();
$getemail=$_SESSION["email"];


require 'vendor/autoload.php';

use Aws\Rds\RdsClient;
$client = RdsClient::factory([
'region'  => 'us-west-2' ,
'version' => 'latest'
]);

$result = $client->describeDBInstances([
    'DBInstanceIdentifier' => 'project1readonly',
]);



$endpoint = $result['DBInstances'][0]['Endpoint']['Address'];
//echo "begin database";
$link = mysqli_connect($endpoint,"nandini","nandinipwd","Project1db") or die("Error " . mysqli_error($link));

/* check connection */
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}
if(!empty($getemail))
{


$link->real_query("SELECT * FROM Projectrec where email='$getemail'");

$res = $link->use_result();

while ($row = $res->fetch_assoc()) {
    echo " <li><img src =\" " . $row['raws3url'] . "\" /></li>";
echo " <li><img src =\" " . $row['finisheds3url'] . "\" /></li>";
}
}
else 
{
$link->real_query("SELECT * FROM Projectrec");
$res = $link->use_result();

while ($row = $res->fetch_assoc()) {
    echo " <li><img src =\" " . $row['raws3url'] . "\" /></li>";
    
}
}

$link->close();
?>
</ul>
</div>

</body>
</html>
