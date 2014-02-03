<?php defined('SYSPATH') or die('No direct script access.');
/**
 *
 *
 * @package    PegasLab/Media
 * @author     PegasLab
 * @copyright  (c) 2013 PegasLab
 * @license    http://pegaslab.com/license
 */
class Model_Media_Storage_VFolder extends ORM {
    protected $_table_name = 'media_storage_vfolders';
    protected $_primary_key = 'id';
    protected $_created_column = array('column' => 'created_at', 'format' => 'Y-m-d H:i:s');
    protected $_updated_column = array('column' => 'updated_at', 'format' => 'Y-m-d H:i:s');
    
    protected $_belongs_to = array(
        'category'  => array(
            'model'       => 'Media_Storage_Category',
            'foreign_key' => 'category_code',
        ),
    
        'parent'  => array(
            'model'       => 'Media_Storage_VFolder',
            'foreign_key' => 'parent_id',
        ),
    );
    
    public function rules(){
        return array(
            'category_code' => array(
                array('not_empty'),
                array('max_length', array(':value', 50)),
            ),
        
            'name' => array(
                array('not_empty'),
                array('max_length', array(':value', 75)),
            ),
        );
    }
    
    public function labels(){
        return array(
            'id'             => ___('media_storage.fields.id'),
            'category_code'  => ___('media_storage.fields.category_code'),
            'name'           => ___('media_storage.fields.vfolder.name'),
            'parent_id'      => ___('media_storage.fields.vfolder.parent_id'),
            'created_at' => ___('media_storage.fields.created_at'),
            'updated_at' => ___('media_storage.fields.updated_at'),
        
        );
    }
}