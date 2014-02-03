<?php defined('SYSPATH') OR die('No direct script access.');
/**
 *
 *
 * @package    PegasLab/Media
 * @author     PegasLab
 * @copyright  (c) 2013 PegasLab
 * @license    http://pegaslab.com/license
 */
abstract class Media_Storage_Location {
    
    const DATA_TYPE_PUBLIC  = 'public';
    const DATA_TYPE_PRIVATE = 'private';
    
    /**
     * @var Model_Media_Storage_Location
     */
    protected $_model;
    
    /**
     * @var Media_Storage_Directory
     */
    protected $_directory;
    
    abstract public function getRootPath();
    abstract public function getDirectoryType();
    
    /**
     * Upload file
     * @param unknown $fileInfo
     * @return Model_Media_Storage_File
     */
    abstract public function uploadFile($fileInfo);
    
    public static function find(Model_Media_Storage_Location $model){
        $class = null;
	    switch ($model->type){
	        case 'local':
	            $class = new Media_Storage_Location_Local($model);
	        break;

	        case 'remote':
	            $class = new Media_Storage_Location_Remote($model);
	        break;
	    }
	    
	    if (!$class) throw new Exception("Specific class for location type '{$model->type}' not found");
	    
	    return $class;
	}
	
	public function __construct(Model_Media_Storage_Location $model){
	    $this->_model = $model;
	    $this->_directory = Media_Storage_Directory::find($this);
	}
	
	public function allowCategory($code){
	    if (!count($this->_model->category)) return true;
	    
	    if (in_array($code, $this->_model->category)) return true;
	    
	    return false;
	}
	
	public function allowDataType($dataType){
	    if (!count($this->_model->data_type)) return true;
	    
	    if (in_array($dataType, $this->_model->data_type)) return true;
	     
	    return false;
	}
	
	public function getCode(){
	    return $this->_model->location_code;
	}
	
	public function getHostId(){
	    return $this->_model->host_id;
	}

	public function getFolderMax(){
	    return $this->_model->folder_max;
	}
	
	public function getSubFolderMax(){
	    return $this->_model->subfolder_max;
	}
	
	public function getFileMax(){
	    return $this->_model->file_max;
	}
	
	public function getFileLength(){
	    return $this->_model->file_length;
	}
	
	public function getFreeSpace(){
	    return $this->_directory->getFreeSpace();
	}
	
	public function getDownloadUrlPublic(){
	    return $this->_model->get('url');
	}
	
	public function getDownloadUrlPrivate(){
	    return $this->_model->get('url_private_download');
	}
	
	/**
	 *
	 * @param unknown $file file name with relative path
	 */
	public function isFileExist($file){
	    return $this->_directory->isFileExist($file);
	}
	
	public function getFileContent($file, $start = null, $stop = null){
	    $this->_directory->getFileContent($file, $start, $stop);
	}
	
	public function getFileSize($file){
	    return $this->_directory->getFilesize($file);
	}
	
	public function getUploadUrl(Model_Media_Storage_AccessToken $token){
	    
	    return Util_Url::addParameter($this->_model->url_post_upload,
	                                  array('token' => $token->generateToken()));
	}
	
	/**
	 *
	 * @param unknown $options
	 * @return Model_Media_Storage_AccessToken
	 */
	protected function createToken($options = array()){
	  
	    $options['location_code'] = $this->_model->location_code;
	    return Media_Storage_Main::getInstance()->createToken($options);
	}
	
	public function nginxSupportDownload(){
	    return $this->_model->get('nginx_support_download');
	}
	
	public function getFileETag($file){
	    return $this->_directory->getFileETag($file);
	}
	
	public function getFileLastModified($file){
	    return $this->_directory->getFileLastModified($file);
	}
}