<?php
// Start the session
session_start();
// In PHP versions earlier than 4.1.0, $HTTP_POST_FILES should be used instead
// of $_FILES.
echo $_POST['useremail'];
$uploaddir = '/tmp/';
$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
$fname = $_FILES['userfile']['name'];
$_SESSION["email"]=$_POST['useremail'];
echo '<pre>';
if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
    echo "File is valid, and was successfully uploaded.\n";
} else {
    header("location: gallery.php");
}
echo 'Here is some more debugging info:';
print_r($_FILES);
print "</pre>";
require 'vendor/autoload.php';

$s3 = new Aws\S3\S3Client([
    'version' => 'latest',
    'region'  => 'us-west-2'
]);
#print_r($s3);
$bucket = uniqid("nandinibuckettest",false);

echo $bucket;
# AWS PHP SDK version 3 create bucket
$result = $s3->createBucket([
    'ACL' => 'public-read',
    'Bucket' => $bucket,
]);

# PHP version 3
$result = $s3->putObject([
    'ACL' => 'public-read',
    'Bucket' => $bucket,
   'Key' => $fname,
'SourceFile' => $uploadfile,
]);
$objectrule = $s3->putBucketLifecycleConfiguration([
    'Bucket' => $bucket,
    'LifecycleConfiguration' => [
        'Rules' => [ 
            [
                'Expiration' => [
                    'Date' => '2015-11-24',
                ],
                              
                'Prefix' => ' ',
                'Status' => 'Enabled',
                
            ],
            
        ],
    ],
]);
  
$url = $result['ObjectURL'];
echo $url;
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
/* Prepared statement, stage 1: prepare */
if (!($stmt = $link->prepare("INSERT INTO Projectrec (uname, email, phone, raws3url, finisheds3url, jpegfilename, state, DateTime) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"))) {
    echo "Prepare failed: (" . $link->errno . ") " . $link->error;
}

$path = new Imagick($uploadfile);
$path->flipImage();
mkdir("/tmp/Imagick");
#$imagickpath ='/var/www/html/Imagick/';
$ext = end(explode('.', $fname));
echo $ext;
$i = '/tmp/Imagick/';
$j = uniqid("DestinationImage");
$k = $j . '.' . $ext;
$DestPath = $i . $k;
echo $DestPath;
$path->writeImage($DestPath);
$flipbucket = uniqid("flippedimage",false);

echo $flipbucket;
# AWS PHP SDK version 3 create bucket
$result = $s3->createBucket([
    'ACL' => 'public-read',
    'Bucket' => $flipbucket,
]);

# PHP version 3
$result = $s3->putObject([
    'ACL' => 'public-read',
    'Bucket' => $flipbucket,
   'Key' => $k,
'SourceFile' => $DestPath,
]);

$finisheds3url=$result['ObjectURL'];

$uname = "MyName";
$email = $_POST['useremail'];
$phone = $_POST['phone'];
$raws3url = $url; 
$jpegfname = basename($fname);
$state = 0;
$DateTime=date("Y-m-d H:i:s");
$stmt->bind_param("ssssssis", $uname, $email, $phone, $raws3url, $finisheds3url, $jpegfilename, $state, $DateTime);


if (!$stmt->execute()) {
    echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
}
printf("%d Row inserted.\n", $stmt->affected_rows);
/* explicit close recommended */
$sns = new Aws\Sns\SnsClient([
    'version' => 'latest',
    'region'  => 'us-west-2'
]);
$Arn = $sns->createTopic([
'Name' => 'mp2',
]);

$subscribe = $sns->subscribe([
'Endpoint' => $email,
'Protocol' => 'email',
'TopicArn' => $Arn['TopicArn'],
]);

$settopic = $sns->setTopicAttributes([
'AttributeName' => 'DisplayName',
'AttributeValue' => 'mp2' ,
'TopicArn' => $Arn['TopicArn'],
]);

$publisher = $sns->publish([
'Message' => 'Congrats!! Your Image has been uploaded Successfully',
'TopicArn' => $Arn['TopicArn'],
]);



header("location: gallery.php");
$stmt->close();

$link->real_query("SELECT * FROM Projectrec");
$res = $link->use_result();
echo "Result set order...\n";
while ($row = $res->fetch_assoc()) {
    echo $row['id'] . " " . $row['email']. " " . $row['phone'];
}
$link->close();

?>