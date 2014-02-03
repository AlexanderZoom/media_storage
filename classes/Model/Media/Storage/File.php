<?php defined('SYSPATH') or die('No direct script access.');
/**
 *
 *
 * @package    PegasLab/Media
 * @author     PegasLab
 * @copyright  (c) 2013 PegasLab
 * @license    http://pegaslab.com/license
 */
class Model_Media_Storage_File extends ORM {
    protected $_table_name = 'media_storage_files';
    protected $_primary_key = 'id';
    protected $_created_column = array('column' => 'created_at', 'format' => 'Y-m-d H:i:s');
    protected $_updated_column = array('column' => 'updated_at', 'format' => 'Y-m-d H:i:s');
    protected $_load_with = array('extra');
    
    protected $_has_one = array(
        'extra' => array(
            'model' => 'Media_Storage_FileExtra',
            'foreign_key' => 'file_id',
        ),
    );
    
    
    const FILE_STATUS_UPLOAD     = 'upload';
    const FILE_STATUS_OK         = 'ok';
    const FILE_STATUS_NOTFOUND   = 'notfound';
    const FILE_STATUS_BANNED     = 'banned';
    const FILE_STATUS_DELETED    = 'deleted';
    
    const FILE_PRIVATE_YES = 'yes';
    const FILE_PRIVATE_NO  = 'no';
    
    public function rules(){
        
        $statusRegexp = '/(' . implode('|', $this->getStatusesFile()) . ')/';
        
        return array(
            'location_code' => array(
                array('not_empty'),
                array('max_length', array(':value', 50)),
            ),
        
            'category_code' => array(
                array('not_empty'),
                array('max_length', array(':value', 50)),
            ),
        
            'location_path' => array(
                array('not_empty'),
                array('max_length', array(':value', 100)),
            ),
        
            'file_name' => array(
                array('not_empty'),
                array('max_length', array(':value', 100)),
            ),
        
            'file_extension' => array(
                array('not_empty'),
                array('max_length', array(':value', 10)),
            ),
            
            'file_size' => array(
                array('not_empty'),
                array('digit'),
            ),
            
            'file_mime' => array(
                array('not_empty'),
                array('max_length', array(':value', 100)),
            ),
            
            'name' => array(
                array('not_empty'),
                array('max_length', array(':value', 75)),
            ),
            
            'private' => array(
                array('not_empty'),
                array('max_length', array(':value', 3)),
                array('regex', array(':value', '/^(yes|no)$/')),
            ),
            
            'status' => array(
                array('not_empty'),
                array('max_length', array(':value', 40)),
                array('regex', array(':value', $statusRegexp)),
            ),
        );
    }
    
    public function labels(){
       
        return array(
            'id'              => ___('media_storage.fields.id'),
            'location_code'   => ___('media_storage.fields.location_code'),
            'category_code'   => ___('media_storage.fields.category_code'),
            'vfolder_id'      => ___('media_storage.fields.vfolder_id'),
            'location_path'   => ___('media_storage.fields.location_path'),
            'file_name'       => ___('media_storage.fields.file_name'),
            'file_extension'  => ___('media_storage.fields.file_extension'),
            'file_size'       => ___('media_storage.fields.file_size'),
            'file_mime'       => ___('media_storage.fields.file_mime'),
            'name'            => ___('media_storage.fields.file.name'),
            'private'         => ___('media_storage.fields.private'),
            'status'          => ___('media_storage.fields.status'),
            'created_at'      => ___('media_storage.fields.created_at'),
            'updated_at'      => ___('media_storage.fields.updated_at'),
        
        );
    }
    
    public function delete(){
        if ($this->extra->loaded()) $this->extra->delete();
        parent::delete();
    }
    
    /**
     *
     * @return array $statuses:
     */
    public function getStatusesFile(){
        $statuses = array();
        $constantPrefix = 'FILE_STATUS';
        $reflectionClass = new ReflectionClass(get_class($this));
        foreach ($reflectionClass->getConstants() as $constantName => $constantValue){
            if (strpos($constantName, $constantPrefix) !== false) $statuses[] = $constantValue;
        }
        return $statuses;
    }
    
    public function create(Validation $validation = NULL)
    {
        $try = 7;
        if (!$this->id){
            
            while ($try > 0){
                $this->id = Util_UUID::v4();
                try {
                    parent::create($validation);
                    break;
                }
                catch (Database_Exception $e){
                    if($e->getCode() == 23000 && strpos($e->getMessage(), $this->id) !== FALSE){
                        ;
                    }
                    else throw $e;
                }
                $try--;
            }
                
        }
        else parent::create($validation);
        
        if ($try < 1) parent::create($validation);
        
    }
}