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
    
    public function getFileList($options){
        $defaultOptions = array(
            'location_code' => null, // code or list codes in array
            'category_code' => null, // code or list codes in array
            'vfolder_id' => '', // empty string
            'is_private' => null, // true|false
            'limit' => null, // positive int
            'offset' => null, // positive int
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
        
        if (!count($locations)) throw new Media_Storage_Exception_Main('Location for upload not found');
        
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
            throw new Media_Storage_Exception_Main("Category code {$filter['category_code']} not found");
        }
        
        if (!in_array($filter['data_type'], array(Media_Storage_Location::DATA_TYPE_PRIVATE, Media_Storage_Location::DATA_TYPE_PUBLIC))){
            throw new Media_Storage_Exception_Main("Data type {$filter['data_type']} incorrect");
        }
        
        $locations = array();
        
        if ($filter['location_code']){
            $location = null;
            if ( !($location = $this->getLocation($filter['location_code']))){
                throw new Media_Storage_Exception_Main("Location {$filter['location_code']} not found");
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