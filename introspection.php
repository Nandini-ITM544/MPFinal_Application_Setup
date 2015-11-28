<?php
require 'vendor/autoload.php';
$rds = new Aws\Rds\RdsClient([
    'version' => 'latest',
    'region'  => 'us-west-2'
]);
$result = $rds->describeDBInstances([
    'DBInstanceIdentifier' => 'Project1db',
    ]);
$endpoint = $result['DBInstances'][0]['Endpoint']['Address'];
    echo "============\n". $endpoint . "================";

$link = mysqli_connect($endpoint,"nandini","nandinipwd","Project1db") or die("Error " . mysqli_error($link));
/* check connection */
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}



mkdir("/tmp/Backup");

$path = '/tmp/Backup/';
$bname = uniqid("Bckupname", false);
$append = $bname . '.' . sql;
$BackPath = $path . $append;
echo $BackPath;
$cmd="mysqldump --user=nandini --password=nandinipwd --host=$endpoint Project1db > $BackPath";
exec($cmd);
$bucketname = uniqid("dbbackupbucket", false);
$s3 = new Aws\S3\S3Client([
    'version' => 'latest',
    'region'  => 'us-west-2'
]);
# AWS PHP SDK version 3 create bucket
$result = $s3->createBucket([
    'ACL' => 'public-read',
    'Bucket' => $bucketname,
]);

# PHP version 3
$result = $s3->putObject([
    'ACL' => 'public-read',
    'Bucket' => $bucketname,
   'Key' => $append,
'SourceFile' => $BackPath,
]);
header("location: gallery.php");
?>