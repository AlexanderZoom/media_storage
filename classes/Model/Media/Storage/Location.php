<?php defined('SYSPATH') or die('No direct script access.');
/**
 *
 *
 * @package    PegasLab/Media
 * @author     PegasLab
 * @copyright  (c) 2013 PegasLab
 * @license    http://pegaslab.com/license
 */
class Model_Media_Storage_Location extends Model {
    
    protected $_fields = array(
        'category', 'folder_max', 'subfolder_max', 'file_max',
        'thumb_width', 'thumb_height', 'reserved_size_time', 'private_auth', 'location_code',
        'type', 'data_type', 'path', 'url', 'url_post_upload', 'url_post_upload_return',
        'url_post_upload_return_redirect', 'secret_key', 'ftp_host', 'ftp_user', 'ftp_pass',
        'remote_type','token_default_expire_time', 'token_upload_expire_time',
        'token_download_expire_time','host_id', 'file_length', 'url_private_download', 'nginx_support_download'
    );
    
    protected $_values = array();
    
    private $_replaceList;
    
    public function loadData($local, $global){
        foreach ($this->_fields as $field){
            if (isset($local[$field])) $this->_values[$field] = $this->replaceText($local[$field]);
            elseif (isset($global[$field])) $this->_values[$field] = $this->replaceText($global[$field]);
            else {
                switch ($field){
                    case 'category':
                    case 'reserved_size_time':
                    case 'data_type':
                        $this->_values[$field] = array();
                    break;
                        
                    default:
                        $this->_values[$field] = null;
                }
            }
        }

    }
    
    public function check($options = array()){
        $error = '';
        //not empty
        $fields = array('folder_max', 'subfolder_max', 'file_max',
                        'thumb_width', 'thumb_height','location_code',
                        'type', 'path', 'url', 'url_post_upload',
                        'url_post_upload_return', 'secret_key', 'host_id', 'file_length',
                        'url_private_download', 'nginx_support_download'
        );
        if ( ($error = $this->_check($fields, 'not_empty')) ){
            throw new Media_Storage_Exception_Model_Location("Field {$error} is empty. Location: {$this->_values['location_code']}");
        }
        
        
        //check category
        if (count($this->get('category')) && isset($options['categories']) && count($options['categories'])) {
            $categories = $options['categories'];
                        
            foreach ($this->get('category') as $category){
                if (!in_array($category, $categories)){
                    throw new Media_Storage_Exception_Model_Location("Category {$category} not found from DB. Location: {$this->_values['location_code']}");
                }
            }
        }
        
        //check datatype
        if (count($this->get('data_type'))){
            foreach ($this->get('data_type') as $dt){
                if (!in_array($dt, array(Media_Storage_Location::DATA_TYPE_PRIVATE, Media_Storage_Location::DATA_TYPE_PUBLIC))){
                    throw new Media_Storage_Exception_Model_Location("Data Type {$dt} incorrect. Location: {$this->_values['location_code']}");
                }
            }
        }
    }
    
    protected function _check ($fields, $validator){
        foreach ($fields as $field){
            if (!call_user_func("Valid::{$validator}", $this->_values[$field])){
                return $field;
            }
        }
    }
    
    public function set($key, $value){
        if (isset($this->_values[$key])) $this->_values[$key] = $value;
    }
    
    public function get($key, $default = null){
        if (isset($this->_values[$key])) return $this->_values[$key];
        return $default;
    }
    
    public function has($key){
        return (bool) isset($this->_values[$key]);
    }
    
    public function values(){
        return $this->values();
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
    
    protected function replaceText($val){
        if (!is_string($val)) return $val;
        
        if (!$this->_replaceList){
            $this->_replaceList = array();
            
            $url = URL::base(true);
            if ($url{strlen($url)-1} == '/') $url = substr($url, 0, strlen($url)-1);
            $this->_replaceList['%www_url%'] = $url;
            $this->_replaceList['%www_host%'] = $_SERVER['HTTP_HOST'];
            $docRoot = DOCROOT;
            if ($docRoot{strlen($docRoot)-1} == '/') $docRoot = substr($docRoot, 0, strlen($docRoot)-1);
            $this->_replaceList['%www_dir%'] = $docRoot;
        }

        return strtr($val, $this->_replaceList);
        
    }
}