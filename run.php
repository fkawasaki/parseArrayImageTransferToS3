<?php
date_default_timezone_set('Asia/Tokyo');

require 'vendor/autoload.php';

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseFile;
use Aws\S3\S3Client;

# Please set your value #
///////////////////////////////////////////////////////////
// Parse Server Configure
$parseServerURL = '${YOUR SERVER URL}' // ex:https://yourdomain.com
$appId = '${YOUR APPID}';
$restAPIKey = '${YOUR RESTAPI KEY}';
$masterKey = '${YOUR MASTER KEY}';

// S3 Configure
$S3Key = '${YOUR S3 KEY}';
$S3SecretKey = '${YOUR SECRET S3 KEY}';
$S3Bucket = '${YOUR S3 BUCKET NAME}';
$S3Region = '' // us-east-1

// target Class Name
$className = '${TARGET CLASS NAME}'; // ex: Commemt
$targetImageArrayColumnName = '${TARGET IMAGE ARRAY COLUMN NAME}'; // ex: postImageArray

// image directory
$serverpathBase = '${FULL PATH}' ;  // '/home/yourusername/tmp'

// log directory
$logpath = $serverpathBase . 'filelist.csv';
///////////////////////////////////////////////////////////


// count
$currentCount = 0;
$totalCount = 0;

// page
$limit = 50;
$skip = 0;

# Parse Server
ParseClient::setServerURL(parseServerURL, 'parse');
ParseClient::initialize($appId, $restAPIKey, $masterKey);

// S3
$bucket = $S3Bucket;
// Instantiate the client.
$s3 = S3Client::factory(array(

    'region' => $S3Region,
    'version' => 'latest',
    'credentials' => array(
        'key' => $S3Key,
        'secret'  => $S3SecretKey,
  )
));


// MAIN
while (true) {
    
    try {
        $query = new ParseQuery($targetClassName);
        $query->limit($limit);
        $query->skip($skip*$limit);

        $results = $query->find();
        $resultCount = count($results);

        for ($i = 0; $i < count($results); $i++) {
            $obj = $results[$i];

            $postImageFile = $obj->get($targetImageArrayColumnName);
            
            // [postImageFile]
            $postImageFileArray = [];
            $objectId = $obj->getObjectId();
            echo '[' . $totalCount . ']' . $objectId . '\n';
            
            if($postImageFile != null) {
                foreach ($postImageFile as $value) {

                    $fileName = $value->getNAME();
                    $fileUrl = $value->getURL();

                    // only contain ‘tfss-‘ file should be copied.
                    $prefex = 'tfss-';
                    if(strpos($fileName,$prefex) !== false){

                        // save image to local
                        $res = saveimage($fileUrl, $fileName, $serverpathBase);
                        
                        // log
                        $str = $objectId . ',' . $fileName . ',' . $fileUrl . '\n';
                        output($logpath, $str);

                        // save image to S3
                        if($res) {

                            $newURL = saveImageToS3($bucket, $fileName, $serverpathBase, $s3);
                            echo '2.image s3 saved. \n';
                            echo $newURL . '\n';

                            if($newURL != '') {
                                // create data object to array
                                $postImageFile = ParseFile::createFromFile($newURL, 'image.jpg');
                                array_push($postImageFileArray, $postImageFile);
                                echo '3.image data array_push \n';

                            } else {
                                //
                                echo 'new image url is null.';
                            }
                        }
                    } else {
                        echo '1.this image is already in S3. \n';
                    }



                }
            }

            if(count($postImageFileArray) > 0) {
                // save image objects to DB
                $obj->setArray('postImageFile', $postImageFileArray);
                $obj->save();
            
                echo '4.parse server db updated. :' . $obj->getObjectId() . '\n' ;

            } else {
                echo 'this post is not contain a image or already saved on S3. :' . $obj->getObjectId() . '\n' ;
            }
            
            

            $totalCount++;
            $currentCount++;



        }

        $currentCount = 0;
        $skip++;

        // finish after all files have copied.
        if($resultCount == 0) {
            // goal
            print('finished.');
            break;
        }
    } catch (Exception $ex) {
        print('error:' . $ex);
        continue;
    }
    
        
}


function output($filename, $str) {
    try {
        file_put_contents($filename, $str, FILE_APPEND );

        return true;
    } catch (Exception $ex) {
        echo $ex;
        return false;
    }
}

function saveimage ($imageurl, $filename, $serverpathBase) {

    $serverpath = $serverpathBase . $filename ; 
    
    // if a target file already exist, return false.
    if (file_exists($serverpath)) {
        echo '1.already exists. \n';
        return false;
    } else {
        
        try {
            // get data from URL
            $data = file_get_contents($imageurl);
            file_put_contents($serverpath, $data);
            //echo 'new file created. \n';
            
            echo '1.image local saved. \n';
            
            return true;
            
        } catch (Exception $ex) {
            // error
            echo $ex;
            
            return false;

        }
    }
    
    


}

function saveImageToS3($bucket, $keyname, $serverpathBase, $client) {
    
    $filepath = $serverpathBase . $keyname;

    try {
        // Upload a file.
        $result = $client->putObject(array(
            'Bucket'       => $bucket,
            'Key'          => $keyname,
            'SourceFile'   => $filepath,
            'ContentType'  => 'image.png',
            'ACL'          => 'public-read',
            'StorageClass' => 'STANDARD',

        ));
        //echo $result['ObjectURL'];
        return $result['ObjectURL'];
    } catch (Exception $ex) {
        
        echo $ex;
        return '';

    }

    
}

