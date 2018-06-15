<?php
require 'aws/vendor/autoload.php';
//require_once 'vendor/autoload.php';
use \Aws\S3\S3Client;

/**
 * @desc Check file is on glacier or not
 */
class AwsGlacier {
    
    /**
     * @desc Check and send request to retore file in bucket
     * 
     * @param type $accessKey
     * @param type $secretKey
     * @param type $bucket
     * @param type $file
     * @param type $restoreDays
     * @return int
     */
    public function checkAwsGlacierFile($accessKey, $secretKey, $bucket, $file, $restoreDays) {
        
        $s3Client = S3Client::factory(array(
                    'region' => 'us-east-1',
                    'version' => 'latest',
                    'credentials' => array(
                        'key' => $accessKey,
                        'secret' => $secretKey,
                    ),
        ));

        try {
            $result = $s3Client->getObject(array(
                'Bucket' => $bucket,
                'Key' => $file
            ));
            $arrResult = $result->toArray();
            if ($arrResult['@metadata']['statusCode'] == '200') {
                return 1;
            } else {
                return 0;
            }
        } catch (Exception $e) {
            $arrMessage = $this->getAwsMessageArray($e->getMessage());
            if (!empty($arrMessage)) {
                $fkey = array_search('CODE', array_column($arrMessage, 'tag'));
                if (!empty($arrMessage[$fkey]['value']) && $arrMessage[$fkey]['value'] == 'InvalidObjectState') {
                    try {
                        $resultAws = $s3Client->restoreObject(array(
                            // Bucket is required
                            'Bucket' => $bucket,
                            // Key is required
                            'Key' => $file,
                            // Days is required
                            'RestoreRequest' => array(
                                'Days' => $restoreDays,
                            )
                        ));
                        $arAwsResult = $resultAws->toArray();
                        if ($arAwsResult['@metadata']['statusCode'] == '202') {
                            return 2;
                        }
                    } catch (Exception $ex) {
                        $restoreMessage = $this->getAwsMessageArray($ex->getMessage());
                        $fikey = array_search('CODE', array_column($restoreMessage, 'tag'));
                        if (!empty($restoreMessage[$fikey]['value']) && $restoreMessage[$fikey]['value'] == 'RestoreAlreadyInProgress') {
                            return 3;
                        } else {
                            return 0;
                        }
                    }
                } else {
                    return 0;
                }
            }
        }
    }
    
    /**
     * @desc Convert string to array of message
     * 
     * @param type $message
     * @return type
     */
    private function getAwsMessageArray($message) {
        $xmlString = substr($message, strpos($message, "<?xml"));
        $parse = xml_parser_create();
        xml_parse_into_struct($parse, $xmlString, $vals, $index);
        xml_parser_free($parse);
        return $vals;
    }
    
    /**
     * @desc Upload image on s3 bucket
     * 
     * @param type $accessKey
     * @param type $secretKey
     * @param type $bucket
     * @param type $file
     * @param type $sourcePath
     */
    public function uploadImage($accessKey, $secretKey, $bucket, $file, $sourcePath, $region){
	//echo $accessKey ."--". $secretKey."--". $bucket."--". $file."--". $sourcePath."--". $region; die;
	
        $s3Client = S3Client::factory(array(
                    'region' => $region,
                    'version' => 'latest',
		    'scheme'=>'http',
		    'credentials' => array(
                        'key' => $accessKey,
                        'secret' => $secretKey,
                    ),
        ));
	
         try {
		$result = $s3Client->putObject(array(
                'Bucket' => $bucket,
                'Key'    => $file,
                'SourceFile' => $sourcePath,
                'ACL'    => 'public-read'
            ));
            return 1;
         } catch (Exception $e) {
	     echo "<pre>"; print_r($e->getMessage()); die; 
	     return 0;
         }
    }
    
    /**
     * @desc Check file is exist or not
     * 
     * @param type $accessKey
     * @param type $secretKey
     * @param type $bucket
     * @param type $file
     * @return int
     */
    public function checkFileExist($accessKey, $secretKey, $bucket, $file,$region){
	
        $s3Client = S3Client::factory(array(
                    'region' => $region,
                    'version' => 'latest',
                    'credentials' => array(
                        'key' => $accessKey,
                        'secret' => $secretKey,
                    ),
        ));
        try {
	   
 
            $response = $s3Client->doesObjectExist($bucket, $file);
	   
	    return $response;
        } catch (Exception $e) {
	   
             return 0;
        }
    }
    
    /**
     * @desc Download s3 bucket file
     * 
     * @param type $accessKey
     * @param type $secretKey
     * @param type $bucket
     * @param type $file
     * @return int
     */
    public function donwloadFile($accessKey, $secretKey, $bucket, $file){
        $s3Client = S3Client::factory(array(
                    'region' => 'us-west-1',
                    'version' => 'latest',
                    'credentials' => array(
                        'key' => $accessKey,
                        'secret' => $secretKey,
                    ),
        ));
        try {
            $filename = basename($file);
            $result = $s3Client->getObject(array(
                'Bucket' => $bucket,
                'Key' => $file
            ));
            header("Content-Type: {$result['ContentType']}");
            header("Content-Description: File Transfer");
            header("Content-Type: application/octet-stream"); 
            header("Content-Disposition: attachment; filename=\"{$filename}\"");
            header('Expires: 0');
            header("X-Sendfile: $filename");
            echo $result['Body'];
            return $result;
        } catch (Exception $e) {
	     echo "<pre>"; print_r($e->getMessage()); die; 
             return 0;
        }
    }
    
    public function deleteimage($accessKey, $secretKey, $bucket, $file, $region){
	
	
        $s3Client = S3Client::factory(array(
                    'region' => $region,
                    'version' => 'latest',
		    'scheme'=>'http',
		    'credentials' => array(
                        'key' => $accessKey,
                        'secret' => $secretKey,
                    ),
        ));
	
         try {
		$result = $s3Client->deleteObject(array(
                'Bucket' => $bucket,
                'Key'    => $file
            ));
            return 1;
         } catch (Exception $e) {
	     echo "<pre>"; print_r($e->getMessage()); die; 
	     return 0;
         }
    }
    
    
    public function copyImage($accessKey, $secretKey, $bucket, $sfile, $tfile, $region){
	//echo $accessKey ."--". $secretKey."--". $bucket."--". $sfile."--". $tfile."--". $region; die;
	
        $s3Client = S3Client::factory(array(
                    'region' => $region,
                    'version' => 'latest',
		    'scheme'=>'http',
		    'credentials' => array(
                        'key' => $accessKey,
                        'secret' => $secretKey,
                    ),
        ));
	
         try {
		$result = $s3Client->copyObject(array(
                'Bucket' => $bucket,
                'Key'    => $tfile,
                'CopySource' => "{$bucket}/{$sfile}",
            ));
//		echo "<pre>"; print_r($result); die;
            return 1;
         } catch (Exception $e) {
//	     echo "<pre>"; print_r($e->getMessage()); die; 
	     return 0;
         }

}
}
