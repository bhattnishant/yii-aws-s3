<?php

/**
 * YIIS3 class file.
 *
 * @version 1.0
 *
 * @uses CFile
 * @author Nishant Bhatt
 * @since 10 Oct 2017
 * @usage:
 *      Add credential file for amazon key,secret key inside config folder
 *      Yii::getPathOfAlias('webroot').'/protected/config/credentials.ini
 *      Add inside main config
 *      //import
 *      <code>
 *      'ext.aws.*',
 *      </code>
 *
 *      // Add in component
 *      <code>
 *      'components'=>array(
 *          's3'=>array(
 *              'class'=>'ext.aws.YIIS3',
 *              'bucket'=> 'website-sitedata',
 *              'version' => 'latest',
 *              'region'  => 'ap-southeast-2',
 *           ),
 *      ),
 *      </code>
 *
 *      For Uploading file
 *      <code>
 *          Yii::app()->s3->upload($source, $destination);
 *      </code>
 */

require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class YIIS3 extends CApplicationComponent {

    private $_s3;
    public $bucket = "website-sitedata";
    public $version = "latest";
    public $region = "ap-southeast-2";
    public $lastError = "";
    public $provider = "";

    private function getInstance() {
        
        $Credentials = parse_ini_file(Yii::getPathOfAlias('webroot') . '/protected/config/credentials.ini');
        
        $this->_s3 = S3Client::factory(array(
            'key' => $Credentials['aws_access_key_id'],
            'secret' => $Credentials['aws_secret_access_key'],
            'version' => $this->version,
            'region' => $this->region,
        ));
        return $this->_s3;
    }

    /**
     *
     * @param type $file_path
     * @return type boolean
     * @throws CException
     */
    public function doesObjectExist($file_path) {

        if (empty($file_path)) {
            throw new CException('file path can not blank');
        }

        $s3 = $this->getInstance();
        $response = $s3->doesObjectExist($this->bucket, $file_path);

        if (!$response)
            return false;

        return $response;
    }

    /**
     *
     * @param type $file_path Amazon s3 file path
     * @param type $download_file_name
     * @param type $expire
     * @return type string
     * @throws CException
     */
    public function getPresignedRequestUrl($file_path, $download_file_name = "", $expire = '+10 seconds') {

        if (empty($file_path)) {
            throw new CException('file path can not blank');
        }

        if (!$this->doesObjectExist($file_path)) {
            throw new CException('file does not exits');
        }

        $s3 = $this->getInstance();

        $params = [
            'Bucket' => $this->bucket,
            'Key' => $file_path,
        ];

        if (!empty($download_file_name)) {
            $ResponseContentDisposition = array(
                'ResponseContentDisposition' => "attachment; filename=" . $download_file_name);
            $params = array_push($param, $ResponseContentDisposition);
        }

        $cmd = $s3->getCommand('GetObject', $params);

        $request = $s3->createPresignedRequest($cmd, $expire);

        // Get the actual presigned-url
        return $request->getUri();
    }

    /**
     *
     * @param type $file_path
     * @return type file content
     * @throws CException
     */
    public function download($file_path) {

        if (empty($file_path)) {
            throw new CException('file path can not blank');
        }

        if (!$this->doesObjectExist($file_path)) {
            throw new CException('file does not exits');
        }

        $s3 = $this->getInstance();

        $result = $s3->getObject(array(
            'Bucket' => $this->bucket,
            'Key' => $file_path
        ));

        header("Content-Type: {$result['ContentType']}");
        return $result['Body'];
    }

    /**
     * @param string $source File to upload - can be any valid CFile filename
     * @param string $destination Name of the file on destination -- can include directory separators
     */
    public function upload($source, $destination = "") {
        $s3 = $this->getInstance();

        $file = Yii::app()->file->set($source);

        if (!$file->exists)
            throw new CException('Origin file not found');

        $fs1 = $file->size;

        if (!$fs1) {
            $this->lastError = "Attempted to upload empty file.";
            return false;
        }

        if (trim($destination) == "") {
            $destination = $source;
        }

        try {
            $response = $s3->putObject([
                'Bucket' => $this->bucket,
                'Key' => $destination,
                'Body' => fopen($source, 'rb'),
                    //'ACL'    => 'private',
            ]);
            gc_disable();
            gc_collect_cycles();
        } catch (Aws\S3\Exception\S3Exception $e) {
            $this->lastError = "There was an error uploading the file.\n";
            return false;
        }
        return true;
    }

}

?>