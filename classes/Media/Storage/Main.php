<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Main class for media storage
 *
 * @package    PegasLab/Media
 * @author     PegasLab
 * @copyright  (c) 2013 PegasLab
 * @license    http://pegaslab.com/license
 */
class Media_Storage_Main {

    /**
     *
     * @var self
     */
    protected static $_instance;
    
    
    protected $_locations = array();
    protected $_categories = array();
    protected $_secret_key;
    protected $_current_host;
    
    
    private function __construct() {
        $this->loadCategoies();
        $this->loadConfig();
    }
    
    private function __clone() {
        
    }
    
    /**
     * @return self
     */
    public static function getInstance() {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    protected function loadConfig(){
        
        
        $config = Kohana::$config->load('media_storage');
        $this->_secret_key = $config->global['secret_key'];
        $this->_current_host = $config->global['current_host'];
        
        foreach ($config->location as $locationCode => $locationValues){
            $locationValues['location_code'] = $locationCode;
            $model = Model::factory('Media_Storage_Location');
            $model->loadData($locationValues, $config->global);
            $model->check(array('categories' => $this->getCategories()));
            $this->_locations[$locationCode] = Media_Storage_Location::find($model);
        }
        
        if (!count($this->_locations)) throw new Media_Storage_Exception_Main('Location in config not found');
    }
    
    protected function loadCategoies(){
        $this->_categories = ORM::factory('Media_Storage_Category')->find_all()->as_array('code');
        
        if (!count($this->_categories)) throw new Media_Storage_Exception_Main("Load categories failed. Categories from DB not found.");
    }
    
   
    
    public function folderByPath($options){
        $defaultOptions = array(
        'category_code' => null, // code or list codes in array
        'path' => '',
        );
        
        $options = array_merge($defaultOptions, $options);
        
        if (!$this->checkCategoryCode($options['category_code'])){
            throw new Media_Storage_Exception_Main_CategoryNotFound("Category code {$categoryCode} not found");
        }
        
        if (!$options['path']){
            throw new Media_Storage_Exception_Main_FolderPathEmpty("Folder path is empty");
        }
        elseif ($options['path'] instanceof Model_Media_Storage_File ) return $options['path'];
        elseif ($options['path'] == '/'){
            $fileModel = ORM::factory('Media_Storage_File');
            $fileModel->rootFolder($options['category_code']);
            return $fileModel;
        }
        
        $path = explode('/', $options['path']);
        $name = array_pop($path);
        $path = implode('/', $path);
        
        $fileModel = ORM::factory('Media_Storage_File');
        $fileModel->where('category_code', '=', $options['category_code']);
        $fileModel->and_where('location_code', '=', '$'.$options['category_code']);
        $fileModel->and_where('type', '=', Model_Media_Storage_File::FILE_TYPE_FOLDER);
        $fileModel->and_where('location_path', '=', $path);
        $fileModel->and_where('name', '=', $name);
        $fileModel->and_where('file_name', '=', $name);
        $fileModel->limit(1);
        $folder = $fileModel->find();
        
        if ($folder->loaded()) return $folder;
        
        throw new Media_Storage_Exception_Main_FolderNotFound("Folder by path not found");
    }
    
    public function folderCheck($options){
        $defaultOptions = array(
        'category_code' => null, // code or list codes in array
        'path' => '',
        );
        
        $options = array_merge($defaultOptions, $options);
        
        if (!$this->checkCategoryCode($options['category_code'])){
            throw new Media_Storage_Exception_Main_CategoryNotFound("Category code {$categoryCode} not found");
        }
        
        if ($options['path'] instanceof Model_Media_Storage_File && $options['path']->loaded() &&
            $options['path']->type == Model_Media_Storage_File::FILE_TYPE_FOLDER) return true;
        elseif (is_string($options['path'])){
            try {
                $this->folderByPath($options);
                return true;
            }
            catch (Media_Storage_Exception_Main_FolderNotFound $e){
                return false;
            }
        }
        else ;
        
        return false;
    }
    
    public function folderCreate($options){
        $defaultOptions = array(
            'category_code' => null, // code or list codes in array
            'name' => '',
            'path' => '',
            
        );
        
        $options = array_merge($defaultOptions, $options);
        
        if (!$options['name']){
            throw new Media_Storage_Exception_Main_FolderNameEmpty("Folder name is empty");
        }
        
        if (!$this->checkCategoryCode($options['category_code'])){
            throw new Media_Storage_Exception_Main_CategoryNotFound("Category code {$categoryCode} not found");
        }
        
        if (!$this->folderCheck($options)){
            throw new Media_Storage_Exception_Main_FolderPathIncorrect("Folder path is incorrect {$options['path']}");
        }
        
        
        $path = $options['path'] instanceof Model_Media_Storage_File ? $options['path']->location_path : $options['path'];
        /*if ($this->folderCheck(array('category_code' => $options['category_code'], 'path' => $path . '/' . $options['name']))){
            throw new Media_Storage_Exception_Main_FolderExists("Folder exists");
        }*/
        
        $parentFolder = $options['path'] instanceof Model_Media_Storage_File ? $options['path'] : $this->folderByPath($options);
        
        try {
            $fileModel = ORM::factory('Media_Storage_File');
            $fileModel->location_code = '$'.$options['category_code'];
            $fileModel->category_code = $options['category_code'];
            $fileModel->vfolder_id = $parentFolder->id;
            $fileModel->location_path = $path;
            $fileModel->file_name = $options['name'];
            $fileModel->name = $options['name'];
            $fileModel->type = Model_Media_Storage_File::FILE_TYPE_FOLDER;
            $fileModel->status = Model_Media_Storage_File::FILE_STATUS_OK;
            $fileModel->save();
        }
        catch (Database_Exception $e){
            if($e->getCode() == 23000){
                throw new Media_Storage_Exception_Main_FolderExists("Folder exists");
            }
            else throw $e;
        }
        
        return true;
        
    }
    
    public function folderRename($options){
        $defaultOptions = array(
        'category_code' => null, // code or list codes in array
        'name' => '',
        'path' => '',
        
        );
        
        $options = array_merge($defaultOptions, $options);
        
        if (!$options['name']){
            throw new Media_Storage_Exception_Main_FolderNameEmpty("Folder name is empty");
        }
        
        if (!$this->checkCategoryCode($options['category_code'])){
            throw new Media_Storage_Exception_Main_CategoryNotFound("Category code {$categoryCode} not found");
        }
        
        $folder = null;
        
        if ($options['path'] instanceof Model_Media_Storage_File && $options['path']->loaded() &&
            $options['path']->type == Model_Media_Storage_File::FILE_TYPE_FOLDER) $folder = $options['path'];
        elseif (is_string($options['path'])) $folder = $this->folderByPath($options);
        else throw new Media_Storage_Exception_Main_FolderPathIncorrect("Folder path is incorrect");
        
        $oldName = $fileModel->file_name;
        
        try {
            DB::query('START TRANSACTION');
            $folder->file_name = $options['name'];
            $folder->name = $options['name'];
            $folder->save();
            
            $select = DB::select('id', 'location_path')->from($folder->table_name())
            ->where('location_code', '=', $folder->location_code)
            ->and_where('category_code', '=', $folder->category_code)
            ->and_where('type', '=', Model_Media_Storage_File::FILE_TYPE_FOLDER)
            ->and_where('location_path', 'like', $folder->location_path . '/' . $oldName . '%');
            $rows = $select->execute()->as_array();
            
            $pathOld = str_replace('/', '\/', $folder->location_path . '/' . $oldName);
            $pathNew = $folder->location_path . '/' . $folder->name;
            
            foreach (rows as $row){
                $pathTmp = preg_replace('/^'. $pathOld . '/', $pathNew, $row['location_path']);
                $update = DB::update()->table($folder->table_name());
                $update->set(array('location_path', $pathTmp));
                $update->where('id', '=', $row['id']);
                $update->execute();
            }
            
            DB::query('COMMIT');
        }
        catch (Database_Exception $e){
            DB::query('ROLLBACK');
            if($e->getCode() == 23000){
                throw new Media_Storage_Exception_Main_FolderExists("Folder exists");
            }
            else throw $e;
        }
        catch (Exception $e){
            DB::query('ROLLBACK');
            throw $e;
        }
        
        return true;
    }
    
    public function folderMove($options){
        $defaultOptions = array(
        'category_code' => null, // code or list codes in array
        'path_from' => '',
        'path_to' => '',
        
        );
        
        $options = array_merge($defaultOptions, $options);
                
        if (!$this->checkCategoryCode($options['category_code'])){
            throw new Media_Storage_Exception_Main_CategoryNotFound("Category code {$categoryCode} not found");
        }
        
        $folderFrom = null;
        if ($options['path_from'] instanceof Model_Media_Storage_File && $options['path_from']->loaded() &&
        $options['path_from']->type == Model_Media_Storage_File::FILE_TYPE_FOLDER) $folderFrom = $options['path'];
        elseif (is_string($options['path_from'])) $folderFrom = $this->folderByPath(array('category_code' => $options['category_code'], 'path' => $options['path_from']));
        else throw new Media_Storage_Exception_Main_FolderPathIncorrect("Folder path_from is incorrect");
        
        $folderTo = null;
        if ($options['path_to'] instanceof Model_Media_Storage_File && $options['path_to']->loaded() &&
        $options['path_to']->type == Model_Media_Storage_File::FILE_TYPE_FOLDER) $folderFrom = $options['path'];
        elseif (is_string($options['path_to'])) $folderFrom = $this->folderByPath(array('category_code' => $options['category_code'], 'path' => $options['path_to']));
        else throw new Media_Storage_Exception_Main_FolderPathIncorrect("Folder path_from is incorrect");
        
        if($folderFrom->category_code != $folderTo->category_code){
            throw new Media_Storage_Exception_Main_FolderOperationNotPermited("Move folder only for one category");
        }
        
        $pathExp = str_replace('/', '\/', $folderFrom->location_path . '/' . $folderFrom->file_name);
        if (preg_match('/^'. $pathExp .'/', $folderTo->location_path)){
            throw new Media_Storage_Exception_Main_FolderOperationNotPermited("Move folder in child folder not permited");
        }

        $chkOpt = array(
            'category_code' => $options['category_code'],
            'path' => $folderTo->location_path . '/' . $folderFrom->file_name
        
        );
        
        if ($this->folderCheck($chkOpt)){
            throw new Media_Storage_Exception_Main_FolderExists("Folder exists");
        }
        
        $oldLocationPath = $folderFrom->location_path;
        
        try {
            DB::query('START TRANSACTION');
            $folderFrom->vfolder_id = $folderTo->id;
            $folderFrom->location_path = $folderTo->location_path . '/' . $folderTo->file_name;
            $folder->save();
        
            $select = DB::select('id', 'location_path')->from($folderFrom->table_name())
            ->where('location_code', '=', $folderFrom->location_code)
            ->and_where('category_code', '=', $folderFrom->category_code)
            ->and_where('type', '=', Model_Media_Storage_File::FILE_TYPE_FOLDER)
            ->and_where('location_path', 'like', $oldLocationPath . '/' . $folderFrom->file_name . '%');
            $rows = $select->execute()->as_array();
        
            $pathOld = str_replace('/', '\/', $oldLocationPath . '/' . $folderFrom->file_name);
            $pathNew = $folderFrom->location_path . '/' . $folderFrom->file_name;
        
            foreach (rows as $row){
                $pathTmp = preg_replace('/^'. $pathOld . '/', $pathNew, $row['location_path']);
                $update = DB::update()->table($folder->table_name());
                $update->set(array('location_path', $pathTmp));
                $update->where('id', '=', $row['id']);
                $update->execute();
            }
        
            DB::query('COMMIT');
        }
        catch (Database_Exception $e){
            DB::query('ROLLBACK');
            if($e->getCode() == 23000){
                throw new Media_Storage_Exception_Main_FolderExists("Folder exists");
            }
            else throw $e;
        }
        catch (Exception $e){
            DB::query('ROLLBACK');
            throw $e;
        }
        
        return true;
        
    }
    
    public function folderDelete($options){
        $defaultOptions = array(
        'category_code' => null, // code or list codes in array
        'path' => '',
        );
        
        $options = array_merge($defaultOptions, $options);
        
        if (!$this->checkCategoryCode($options['category_code'])){
            throw new Media_Storage_Exception_Main_CategoryNotFound("Category code {$categoryCode} not found");
        }
        
        $folder =$this->folderByPath($options);
        $folder->delete();
        
        return true;
    }
    
    public function fileCreate($options){
        $defaultOptions = array(
            'category_code' => '',
            'vfolder_id' => '',
            'data_type' => Media_Storage_Location::DATA_TYPE_PUBLIC,
            'free_space' => 0,
            'location_code' => null,
            'direct_upload' => false,
            'file_path' => null,
            'data' => null,
            'file_name' => '',
            'tmp_path'  => '',
            'upload_token' => null, // token
            'file' => null,
        );
        
        $options = array_merge($defaultOptions, $options);
        
        $file = null;
        $isTmpFile = false;
        $fileName = !$options['file_name'] ? 'newfile' : $options['file_name'];
        
        if ($options['file_path'] && is_readable($options['file_path'])) $file = $options['file_path'];
        elseif ($options['data']){
            $tmpPath = sys_get_temp_dir();
            if ($options['tmp_path'] && is_writable($options['tmp_path'])) $tmpPath = $options['tmp_path'];
            $file = tempnam($tmpPath, 'MediaStorage');
            $isTmpFile = true;
            $fp = fopen($file, 'w');
            fwrite($fp, $options['data']);
            fclose($fp);
            
            
        }
        elseif($options['file'] instanceof Model_Media_Storage_File && $options['file']->isFile()) $file = $options['file'];
        
        if (!$file){
            throw new Media_Storage_Exception_Main_FileCreateNotFound('File or data for create not found');
        }
        $locations = array();
        
        if ($options['upload_token'] instanceof Model_Media_Storage_AccessToken){
            $locations[] = $this->getUploadLocationByAccessToken($options['upload_token']);
        }
        else {
            $filter = array(
            'category_code' => $options['category_code'],
            'data_type' => $options['data_type'],
            'free_space' => $options['free_space'],
            'location_code' => $options['location_code'],
            'direct_upload' => $options['direct_upload'],
            );
            
            $locations = $this->getLocationsByFilter($filter);
        }
                
        if (!count($locations)) throw new Media_Storage_Exception_Main_LocationNotFound('Location for create file not found');
        
        
        $location = $locations[0];
        
        $fileInfo = array(
        'file' => $file, // full path to the file on disk
        'name' => $fileName,
        'category_code' => $options['category_code'], //if not token
        'vfolder_id' => $options['vfolder_id'], //if not token
        'data_type'  => $options['data_type'], // public or private
        'token' => $options['upload_token'],
        );
        
        $location->uploadFile($fileInfo);
        
        if ($isTmpFile) @unlink($file);
        
        return true;
        
    }
    
    public function fileCopy($options){
        $defaultOptions = array(
        'category_code' => '',
        'path_to' => '',
        'file'  => '',
        );
        
        $options = array_merge($defaultOptions, $options);
        
        $file = $this->getFile($options['file']);
        if (!$file){
            throw new Media_Storage_Exception_Main_FileNotFound('File not found');
        }
        
        
        $folder = $this->folderByPath($options);
        
        $fileOptions = array(
        'category_code' => $folder->category_code,
        'vfolder_id' => $folder->id,
        'data_type' => $file->private == Model_Media_Storage_File::FILE_PRIVATE_YES ? Media_Storage_Location::DATA_TYPE_PRIVATE : Media_Storage_Location::DATA_TYPE_PUBLIC,
        'file_name' => '',
        'file' => $file,
        );
        
        $this->fileCreate($fileOptions);
        
        return true;
    }
    
    public function fileMove($options){
        $defaultOptions = array(
        'path_to' => '',
        'file'  => '',
        );
        
        $options = array_merge($defaultOptions, $options);
        
        $file = $this->getFile($options['file']);
        if (!$file){
            throw new Media_Storage_Exception_Main_FileNotFound('File not found');
        }
        
        $folder = $this->folderByPath(array(
            'category_code' => $file->category_code,
            'path' => $options['path_to']
        ));
        
        if ($folder->id != $file->vfolder_id){
            $file->vfolder_id = $folder->id;
            $file->save();
        }
        
        return true;
    }
    
    public function fileDelete($file){
        $file = $this->getFile($file);
        
        if (!$file || !$file->isFile()){
            throw new Media_Storage_Exception_Main_FileDeleteNotFound('File for delete not found');
        }
        
        try {
            DB::query('START TRANSACTION');
            $locationCode = $file->location_code;
            $path = $file->location_path . DIRECTORY_SEPARATOR . $file->file_name;
            $file->delete();
            $location = $this->getLocation($locationCode);
            if (!$location) throw new Media_Storage_Exception_Main_LocationNotFound('Location not found for delete file');
            
            $location->getDirectoryObject()->getFileObject()->deleteFile(
                    $location->getDirectoryObject()->getFullPath($path));
            
            DB::query('COMMIT');
        }
        catch (Exception $e){
            DB::query('ROLLBACK');
            throw new Media_Storage_Exception_Main_FileDeleteCanNot($e->getMessage());
        }
        
        
    }
    
    public function fileCreateThumb($options){
        $defaultOptions = array(
        'file'  => '',
        
        );
        
        $options = array_merge($defaultOptions, $options);
        
        
        $file = $this->getFile($options['file']);
        if (!$file){
            throw new Media_Storage_Exception_Main_FileNotFound('File not found');
        }
        
        //get content file
        //check graph libs
        //check supporting image type
        //resize imgage
        //create new file
        //return file model
       
        
    }
    
    public function getTotalElementsFolder($options){
        $defaultOptions = array(
        'category_code' => null, // code or list codes in array
        'path' => '',
        );
        
        $options = array_merge($defaultOptions, $options);
        
        $folder = $this->folderByPath($options);
        
        return $folder->getElementsCount();
    }
    
    public function getFile($file){
        if ($file instanceof Model_Media_Storage_File){
            if (!$file->isFile()) return null;

            return $file;
        }
        elseif (is_string($file)){
            if (!trim($file)) return null;
            
            $fileModel = ORM::factory('Media_Storage_File');
            $fileModel->where('id', '=', $file);
            $fileModel->andwhere('status', '=', Model_Media_Storage_File::FILE_STATUS_OK);
            $fileModel->andwhere('type', '>=', Model_Media_Storage_File::FILE_TYPE_NORMAL);
            $fileModel->limit(1);
            $file = $fileModel->find();
            
            if ($file->loaded()) return $file;
            
        }
        else ;
        
        return null;
    }
    
    public function getFileList($options){
        $defaultOptions = array(
            'location_code' => null, // code or list codes in array
            'category_code' => null, // code or list codes in array
            'vfolder_id' => '', // empty string
            'is_private' => null, // true|false
            'limit' => null, // positive int
            'offset' => null, // positive int
            'type'   => Model_Media_Storage_File::FILE_TYPE_NORMAL,
        );
        
        $options = array_merge($defaultOptions, $options);
        $whereFields = array('location_code', 'category_code', 'vfolder_id', 'is_private');
        
        $out = array('count_total' => 0, 'count' => 0, 'offset' => 0, 'entities' => array());
        
        $fileModel = ORM::factory('Media_Storage_File');
        $fileModelCount = ORM::factory('Media_Storage_File');
        
        
        
        
        foreach ($whereFields as $field){
            $key = $field;
            $value = $options[$key];
            
            switch ($key){
                case 'location_code':
                case 'category_code':
                case 'type':
                case 'vfolder_id':
                    if (!is_null($value)){
                        if (is_array($value)){
                            $fileModel->and_where($key, 'IN', $value);
                            $fileModelCount->and_where($key, 'IN', $value);
                        }
                        elseif (is_string($value)){
                            $fileModel->and_where($key, '=', $value);
                            $fileModelCount->and_where($key, '=', $value);
                        }
                        else ;
                    }
                 break;
                 
                 case 'is_private':
                     if (!is_null($value)){
                         if ($value) $value = 'yes';
                         else $value = 'no';
                         
                         $fileModel->and_where('private', '=', $value);
                         $fileModelCount->and_where('private', '=', $value);
                     }
                     break;
            }
        }
        
        $out['count_total'] = $fileModelCount->count_all();
        
        if ($out['count_total']){
            if (((int) $options['limit']) > 0){
                $fileModel->limit((int) $options['limit']);
                
                if (((int) $options['offset']) > 0){
                    $fileModel->offset((int) $options['offset']);
                    $out['offset'] = (int) $options['offset'];
                }
            }
            
            $out['entities'] = $fileModel->find_all();
            $out['count'] = count($out['entities']);
            
        }
        
        return $out;
    }
    
    /**
     * Get download url
     * @param Model_Media_Storage_File $file
     * @param number $expireTime
     * @param string $forcePrivateLink for public files
     */
    public function getDownloadUrl(Model_Media_Storage_File $file, $expireTime = 86400, $forcePrivateLink = false){
        $location = $this->getLocation($file->location_code);
        if (!$location) throw new Media_Storage_Exception_Main('Location not found. File: ' . $file->id);
        $dataType = $file->private == 'yes' ? Media_Storage_Location::DATA_TYPE_PRIVATE : Media_Storage_Location::DATA_TYPE_PUBLIC;
        if ($forcePrivateLink){
            $dataType = Media_Storage_Location::DATA_TYPE_PRIVATE;
        }
        
        $url = '';
        if ($dataType == Media_Storage_Location::DATA_TYPE_PRIVATE){
            $urlLocation =  $location->getDownloadUrlPrivate();

            $ctime = time();
            $expireAt = date('Y-m-d H:i:s', mktime(
                    date('H', $ctime),
                    date('i', $ctime),
                    date('s', $ctime) + $expireTime,
                    date('m', $ctime),
                    date('d', $ctime),
                    date('Y', $ctime)
            ));
            
            $tokenOptions = array (
                'location_code' => $file->location_code,
                'category_code' => $file->category_code,
                'vfolder_id' => $file->vfolder_id,
                'data_type' => $dataType,
                'file_id' => $file->id,
                'expire_at' => $expireAt,
                'action' => Model_Media_Storage_AccessToken::ACTION_DOWNLOAD
            );
            $token = $this->createAccessToken($tokenOptions);
            $url = Util_Url::addParameter($urlLocation,
                array('token' => $token->generateToken()));
        }
        else {
            $urlLocation = $location->getDownloadUrlPublic();
            $filePath = $file->location_path;
            $fileName = $file->file_name;
            $url = $urlLocation . '/' . $filePath . '/' . $fileName;
        }
        
        return $url;
    }
    
    /**
     * Upload location for direct upload (from server)
     * @return Media_Storage_Location
     */
    public function getUploadLocation($categoryCode, $dataType = Media_Storage_Location::DATA_TYPE_PUBLIC,
                                      $freeSpace = 0, $locationCode = null){
        $filter = array(
        'category_code' => $categoryCode,
        'data_type' => $dataType,
        'free_space' => $freeSpace,
        'location_code' => $locationCode,
        'direct_upload' => true
        );
        
        $locations = $this->getLocationsByFilter($filter);
        
        if (!count($locations)) throw new Media_Storage_Exception_LocationNotFound('Location for upload not found');
        
        return $locations[0];
    }
    
    /**
     *
     * @param Model_Media_Storage_AccessToken $token
     * @return Media_Storage_Location
     */
    public function getUploadLocationByAccessToken(Model_Media_Storage_AccessToken $token){
        return $this->getLocation($token->get('location_code'));
    }
    
    /**
     * Get upload url for uploading from browser
     * @param unknown $categoryCode
     * @param unknown $dataType
     * @param number $freeSpace
     * @param number $expireTime
     * @param string $locationCode
     * @return string
     */
    public function getUploadUrl($categoryCode, $dataType = Media_Storage_Location::DATA_TYPE_PUBLIC,
                                 $freeSpace = 0, $expireTime = 120, $locationCode = null){
        $filter = array(
            'category_code' => $categoryCode,
            'data_type' => $dataType,
            'free_space' => $freeSpace,
            'location_code' => $locationCode
        );
        
        
        $locations = $this->getLocationsByFilter($filter);
        if (count($locations)){
            $ctime = time();
            $expireAt = date('Y-m-d H:i:s', mktime(
                date('H', $ctime),
                date('i', $ctime),
                date('s', $ctime) + $expireTime,
                date('m', $ctime),
                date('d', $ctime),
                date('Y', $ctime)
            ));
            $optsToken = array(
                'location_code' => $locations[0]->getCode(),
                'category_code' => $categoryCode,
                'vfolder_id'    => null,
                'data_type'     => $dataType,
                'file_id'       => null,
                'expire_at'     => $expireAt,
                'action'        => Model_Media_Storage_AccessToken::ACTION_UPLOAD,
            );
                        
            return $locations[0]->getUploadUrl($this->createAccessToken($optsToken));
        }
        else {
            throw new Media_Storage_Exception_Main('Location for upload not found');
        }
    }
    
    
    public function getLocationsByFilter($filter = array()){
        //
        $defaultFilter = array(
            'category_code' => '',
            'data_type' => Media_Storage_Location::DATA_TYPE_PUBLIC,
            'free_space' => 0,
            'location_code' => null,
            'direct_upload' => false
        );
                
        $filter = array_merge($defaultFilter, $filter);
        
        if (!$this->checkCategoryCode($filter['category_code'])){
            throw new Media_Storage_Exception_Main_CategoryNotFound("Category code {$filter['category_code']} not found");
        }
        
        if (!in_array($filter['data_type'], array(Media_Storage_Location::DATA_TYPE_PRIVATE, Media_Storage_Location::DATA_TYPE_PUBLIC))){
            throw new Media_Storage_Exception_LocationDataType("Data type {$filter['data_type']} incorrect");
        }
        
        $locations = array();
        
        if ($filter['location_code']){
            $location = null;
            if ( !($location = $this->getLocation($filter['location_code']))){
                throw new Media_Storage_Exception_LocationNotFound("Location {$filter['location_code']} not found");
            }
            $locations[$location->getCode()] = $location;
        }
        else $locations = $this->_locations;
        
        $locationsSort = array();
        $locationsSelect = array();
        
        foreach ($locations as $location){
            if (!$location->allowCategory($filter['category_code'])) continue;
            if (!$location->allowDataType($filter['data_type'])) continue;
            
            $locationFreeSpace = $location->getFreeSpace();
            if ($locationFreeSpace <= 0 || $locationFreeSpace < $filter['free_space'] ) continue;

            $prefixSort = 'pref_0_';
            if ($filter['direct_upload'] && $location->getHostId() == $this->getCurrentHost()
                && $filter['free_space'] > 0 && $locationFreeSpace > $filter['free_space']){
                $prefixSort = 'pref_1_';
            }
            
            $locationsSort[$prefixSort . $locationFreeSpace] = $location;
        }
        
        if (count($locationsSort)){
            uksort($locationsSort, array($this, 'sortLocation'));
            foreach ($locationsSort as $v) $locationsSelect[] = $v;
        }
        
        return $locationsSelect;
    }
    
    /**
     *
     * @param string $code
     * @return multitype:Media_Storage_Location|NULL
     */
    public function getLocation($code){
        if (isset($this->_locations[$code])) return $this->_locations[$code];
        
        return null;
    }
    
    public function getCategories(){
        return $this->_categories;
    }
    
    public function getLocations(){
        return $this->_locations;
    }
    
    public function checkCategoryCode($code){
        return in_array($code, array_keys($this->getCategories()));
    }
    
    public function getCurrentHost(){
        return call_user_func($this->_current_host);
    }
    
    /**
     *
     * @param unknown $options
     * @return Model_Media_Storage_AccessToken
     */
    public function createAccessToken($options){
         
        $defaultOptions = array(
        'location_code' => null,
        'category_code' => null,
        'vfolder_id'    => null,
        'data_type'     => null,
        'file_id'       => null,
        'expire_at'     => null,
        'action'        => null,
        );
         
        $options = array_merge($defaultOptions, $options);
         
        $model = new Model_Media_Storage_AccessToken($this->_secret_key);
        $model->loadData($options);
        $model->check(
                array('categories' => $this->getCategories(),
                      'locations' => $this->getLocations(),
                )
        );
        return $model;
    }
    
    /**
     *
     * @param string $token
     * @return Model_Media_Storage_AccessToken
     */
    public function getAccessToken($token){
        $model = new Model_Media_Storage_AccessToken($this->_secret_key);
        $model->loadDataByToken($token);
        return $model;
    }
    
    public function sortLocation($a, $b){
        $a = explode('_', $a);
        $b = explode('_', $b);
    
        if ($a[1] < $b[1]) return 1;
        elseif ($a[1] > $b[1]) return -1;
        else ;
    
        if ($a[2] < $b[2]) return 1;
        elseif ($a[2] > $b[2]) return -1;
        else ;
    
        return 0;
    }
}