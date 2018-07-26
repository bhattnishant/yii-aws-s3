# yii-aws-s3
AWS S3 Basic Methods for YII (YII Aws-S3 Bucket)

What's that
-----------
An extension for Yii Framework, bundling commonly used functions for aws-s3 objects.

Requirements
------------

   * PHP 5.1+ and Yii 1.0 or above to use as Yii extension. 
   * PHP 5.1+ to use without Yii.
   * AWS SDK for PHP http://aws.amazon.com/sdkforphp/
   * CFile Yii Extention http://github.com/idlesign/ist-yii-cfile

Installation
------------

    * For Yii: extract extension files under `protected/extensions/YIIS3`.
    * W/o Yii: extract extension files into a directory of choise.
	
Usage
-----

To use with Yii Framework:

  * Introduce YIIS3 to Yii.
  * Add credential file for amazon key,secret key inside config folder
  
    ```php
    Yii::getPathOfAlias('webroot').'/protected/config/credentials.ini   
    ```
	
  * Add definition to CWebApplication config file (main.php)
  
	```php
	'import'=>array(
		...
		'ext.aws.*',
		...
	),

	'components'=>array(
		...
		's3'=>array(
			'class'=>'ext.aws.YIIS3',
			'bucket'=> 'website-sitedata', // change your bucket
			'version' => 'latest',
			'region'  => 'ap-southeast-2', //change your region
		),
		...	
	),
	```

Upload file

	
	/**
	 * @param string $source File to upload - can be any valid CFile filename
	 * @param string $destination Name of the file on destination -- can include directory separators
	 */
	Yii::app()->s3->upload($source, $destination);
    

Check File Exits on bucket
			
	
	/**
	 *
	 * @param type $file_path
	 * @return type boolean
	 * @throws CException
	 */
	Yii::app()->s3->doesObjectExist($file_path)
    
	
Get Download link with Presigned Request Url
		
	
	/**
	 *
	 * @param type $file_path Amazon s3 file path
	 * @param type $download_file_name
	 * @param type $expire
	 * @return type string
	 * @throws CException
	 */
	Yii::app()->s3->getPresignedRequestUrl($file_path, $download_file_name, '+10 seconds');	
	

Download File
		
	/**
	 *
	 * @param type $file_path
	 * @return type file content
	 * @throws CException
	 */	
 
	 Yii::app()->s3->download($file_path);
		 
Custom Download Example

	
	$s3destination = "pdf/xyz.pdf"; // Fixed Custom Path
	$objectExitsOnS3 = Yii::app()->s3->doesObjectExist($s3destination);
	if($objectExitsOnS3) {                
			header("Expires: 0");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Pragma: ");
			header("Cache-Control: ");
			header("Content-Type: application/force-download");                
			// set file name
			header('Content-disposition: attachment; filename="' . $fileName . '"');
			ob_clean();
			echo Yii::app()->s3->download($s3destination);
	}
	