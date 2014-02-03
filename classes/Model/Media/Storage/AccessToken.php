<?php defined('SYSPATH') or die('No direct script access.');
/**
 *
 *
 * @package    PegasLab/Media
 * @author     PegasLab
 * @copyright  (c) 2013 PegasLab
 * @license    http://pegaslab.com/license
*/
class Model_Media_Storage_AccessToken extends Model {
    const ACTION_UPLOAD   = 'upload';
    const ACTION_DOWNLOAD = 'download';
    
    protected $_fields = array(
        'location_code', 'data_type', 'category_code', 'vfolder_id', 'file_id', 'action', 'expire_at',
    );
    
    protected $_fieldsHash = array(
        'location_code', 'data_type', 'category_code', 'vfolder_id', 'file_id', 'action', 'expire_at',
    );
    
    protected $_secret_key;
    
    protected $_values = array();
    
    public function __construct($secretKey){
        $this->_secret_key = $secretKey;
        $this->init();
    }
    
    public function init(){
        foreach ($this->_fields as $field){
            $this->_values[$field] = null;
        }
    }
    
    public function loadDataByToken($token){
        $data = Util_Url::parseQuery(base64_decode(rawurldecode($token)));
        
        $defaultData = array();
        foreach ($this->_fieldsHash as $fld) $defaultData[$fld] = null;
        $data = array_merge($defaultData, $data);
        
        $this->checkHash(Arr::get($data, 'hash'), $data);
        $this->loadData($data);
        $this->checkExpire();
        
    }
    
    public function loadData($data){
        
        foreach ($this->_fields as $field){
            if (isset($data[$field])) $this->_values[$field] = $data[$field];
        }
    }
    
    public function check($options){
        $error = '';
        //not empty
        $fields = array(
            'location_code', 'category_code', 'action', 'expire_at'
        );
                
        if ( ($error = $this->_check($fields, 'not_empty')) ){
            throw new Media_Storage_Exception_Model_AccessToken("Field {$error} is empty");
        }
        
        //check digit
        $digitCheck = array();
        
        if ( ($error = $this->_check($fields, 'not_empty')) ){
            throw new Media_Storage_Exception_Model_AccessToken("Field {$error} not digit");
        }
        
        //check location
        $locations = array_keys($options['locations']);
        if (!in_array($this->get('location_code'), $locations)){
            throw new Media_Storage_Exception_Model_AccessToken("Location code {$this->get('location_code')} not found");
        }
        
        //check category
        $categories = $options['categories'];
        if (!in_array($this->get('category_code'), $categories)){
            throw new Media_Storage_Exception_Model_AccessToken("Category code {$this->get('category_code')} not found from DB");
        }
            
        //check datatype
        if ($this->get('data_type')){
            if (!in_array($this->get('data_type'), array(Media_Storage_Location::DATA_TYPE_PRIVATE, Media_Storage_Location::DATA_TYPE_PUBLIC))){
                throw new Media_Storage_Exception_Model_AccessToken("Data Type {$this->get('data_type')} incorrect");
            }
        }
        
        //check action
        if (!in_array($this->get('action'), array(self::ACTION_DOWNLOAD, self::ACTION_UPLOAD))){
                throw new Media_Storage_Exception_Model_AccessToken("Action {$this->get('action')} incorrect");
        }

        //check expire_at
        if ( ($error = $this->_check(array('expire_at'), 'date')) ){
            throw new Media_Storage_Exception_Model_AccessToken("Field {$error} not date");
        }
    }
    
    protected function _check ($fields, $validator){
        foreach ($fields as $field){
            if (!call_user_func("Valid::{$validator}", $this->_values[$field])){
                return $field;
            }
        }
    }
    
    protected function checkHash($hash, $data = null){
        if (is_null($data)){
            $data = $this->_values;
        }
        
        $str ='';
        foreach ($this->_fieldsHash as $field){
            $str .= $data[$field];
        }
        $str .= Media_Storage_Main::getInstance()->getCurrentHost();
        $str .= $this->_secret_key;
        
        if ( $hash == md5(md5($str))) return true;
        
        throw new Media_Storage_Exception_Model_AccessToken_Hash('Incorrect hash');
    }
    
    protected function generateHash(){
        $str ='';
        foreach ($this->_fieldsHash as $field){
            $str .= $this->_values[$field];
        }
        $str .= Media_Storage_Main::getInstance()->getLocation($this->get('location_code'))->getHostId();
        $str .= $this->_secret_key;
        
        return md5(md5($str));
    }
    
    public function  generateToken(){
        $this->check(
                array('categories' => Media_Storage_Main::getInstance()->getCategories(),
                      'locations'  => Media_Storage_Main::getInstance()->getLocations(),
                ));
        
        $listParams = array();
        foreach ($this->_fieldsHash as $field){
            $listParams[$field] = $this->_values[$field];
        }
        $listParams['hash'] = $this->generateHash();
        return  base64_encode(http_build_query($listParams, '', '&'));
    }
    
    /**
     * Add seconds to expireAt
     * @param unknown $time seconds
     */
    public function addTimeToExpireAt($time){
        $expireTime = time();
        if ($this->get('expire_at')) $expireTime = strtotime($this->get('expire_at'));
    
        $this->set('expire_at', date('Y-m-d H:i:s', $expireTime + $time));
    }
    
    public function checkExpire(){
        if (strtotime($this->get('expire_at')) < time()){
            throw new Media_Storage_Exception_Model_AccessToken_Expire('Token is expire');
        }
    }
    
    public function isUpload(){
        return self::ACTION_UPLOAD == $this->get('action');
    }
    
    public function isDownload(){
        return self::ACTION_DOWNLOAD == $this->get('action');
    }
    
    public function checkAction($action){
        switch ($action){
            case self::ACTION_DOWNLOAD:
                if (!$this->isDownload()){
                    throw new Media_Storage_Exception_Model_AccessToken_Action('Action is incorrect ' . $action . ' Token action ' . $this->get('action'));
                }
            break;

            case self::ACTION_UPLOAD:
                if (!$this->isUpload()){
                    throw new Media_Storage_Exception_Model_AccessToken_Action('Action is incorrect ' . $action . ' Token action ' . $this->get('action'));
                }
            break;
            
            default:
                throw new Media_Storage_Exception_Model_AccessToken_Action('Unknown action' . $action . ' Token action ' . $this->get('action'));
        }
        
        return true;
    }
    
    /**
     *
     * @return array $statuses:
     */
    public function getStatusesAction(){
        $statuses = array();
        $constantPrefix = 'ACTION_';
        $reflectionClass = new ReflectionClass(get_class($this));
        foreach ($reflectionClass->getConstants() as $constantName => $constantValue){
            if (strpos($constantName, $constantPrefix) !== false) $statuses[] = $constantValue;
        }
        return $statuses;
    }
    
    public function set($key, $value){
        if (isset($this->_values[$key])) $this->_values[$key] = $value;
    }
    
    public function get($key, $default = null){
        if ($key == 'token') return $this->generateToken();
        if (isset($this->_values[$key])) return $this->_values[$key];
        return $default;
    }
    
    
    public function has($key){
        return (bool) isset($this->_values[$key]);
    }
    
    public function values(){
        return $this->values();
    }
    
    public function __toString(){
        return $this->generateToken();
    }
    public function __set($key, $value){
        $this->set($key, $value);
    }
    
    public function __get($key){
        return $this->get($key);
    }
    
    public function __isset($key){
        return $this->has($key);
    }
    
    public function __unset($key){
        throw new Exception('Operation not permited');
    }
}