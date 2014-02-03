<?php defined('SYSPATH') or die('No direct script access.');
/**
 *
 *
 * @package    PegasLab/Media
 * @author     PegasLab
 * @copyright  (c) 2013 PegasLab
 * @license    http://pegaslab.com/license
 */
class Model_Media_Storage_ReservedSize extends ORM {
    protected $_table_name = 'media_storage_reserved_size';
    protected $_primary_key = 'location_code';
    protected $_created_column = array('column' => 'created_at', 'format' => 'Y-m-d H:i:s');
    protected $_updated_column = array('column' => 'updated_at', 'format' => 'Y-m-d H:i:s');
    
    public function rules(){
        return array(
            'location_code' => array(
                array('not_empty'),
                array('max_length', array(':value', 50)),
            ),
            
        );
    }
    
    public function labels(){
        return array(
            'location_code' => ___('media_storage.fields.location_code'),
            'size'          => ___('media_storage.fields.size'),
            'created_at'    => ___('media_storage.fields.created_at'),
            'updated_at'    => ___('media_storage.fields.updated_at'),
        
        );
    }
}