<?php defined('SYSPATH') or die('No direct script access.');
/**
 *
 *
 * @package    PegasLab/Media
 * @author     PegasLab
 * @copyright  (c) 2013 PegasLab
 * @license    http://pegaslab.com/license
 */
class Model_Media_Storage_Category extends ORM {
    protected $_table_name = 'media_storage_categories';
    protected $_primary_key = 'code';
    protected $_created_column = array('column' => 'created_at', 'format' => 'Y-m-d H:i:s');
    protected $_updated_column = array('column' => 'updated_at', 'format' => 'Y-m-d H:i:s');
    
    protected $_has_many = array(
        'vfolders'    => array(
            'model'       => 'Media_Storage_VFolder',
            'foreign_key' => 'category_code',
        )
    );
    
    public function rules(){
        return array(
            'code' => array(
                array('not_empty'),
                array('max_length', array(':value', 50)),
            ),
                
            'hidden' => array(
                array('not_empty'),
                array('max_length', array(':value', 3)),
                array('regex', array(':value', '/^(yes|no)$/')),
            ),
            
        );
    }
    
    public function labels(){
        return array(
            'code' => ___('media_storage.fields.category.code'),
            'hidden' => ___('media_storage.fields.category.hidden'),
            'created_at' => ___('media_storage.fields.created_at'),
            'updated_at' => ___('media_storage.fields.updated_at'),
        
        );
    }
}