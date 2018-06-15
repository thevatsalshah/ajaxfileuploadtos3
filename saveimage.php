<?php

if(isset($_POST) && !empty($_POST['filetemppath']) && !empty($_POST['img'])){
    define('UPLOAD_DIR', 'uploads/');
    $ext = pathinfo($_POST['filetemppath'], PATHINFO_EXTENSION);
    $filename = explode(".",substr($_POST['filetemppath'], strrpos($_POST['filetemppath'], '\\') + 1))[0];
    $arr = explode(',', urldecode($_POST['img'])); 
    $aws_filename = $filename.time().".".$ext;
    $new_filename = UPLOAD_DIR .$aws_filename;
    file_put_contents($new_filename, base64_decode($arr[1]));
    
   
try {
	    require_once 'awsglacier.php';
	    
	    $bucketName = 'seatscanuploads';
	    $accessKey = 'AKIAJNLX26ZHPQYJ4JYQ';
	    $secretKey = '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';
	    $region = 'us-west-1';
	    $classObject = new AwsGlacier();
	    $result = $classObject->uploadImage($accessKey, $secretKey, $bucketName, $aws_filename, $new_filename, $region);
	    
	    unlink($new_filename);    
	    
} catch (S3Exception $e) {
     echo "error";
}
    $protocol = isset($_SERVER["HTTPS"]) ? 'https://' : 'http://';
    echo $s3file= $protocol.$bucketName.'.s3.amazonaws.com/'.$aws_filename;
}else{
    echo "error";
}
?>
