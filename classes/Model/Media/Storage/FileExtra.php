<?php defined('SYSPATH') or die('No direct script access.');
/**
 *
 *
 * @package    PegasLab/Media
 * @author     PegasLab
 * @copyright  (c) 2013 PegasLab
 * @license    http://pegaslab.com/license
 */
class Model_Media_Storage_FileExtra extends ORM {
    protected $_table_name = 'media_storage_file_extras';
    protected $_primary_key = 'file_id';
    protected $_created_column = array('column' => 'created_at', 'format' => 'Y-m-d H:i:s');
    protected $_updated_column = array('column' => 'updated_at', 'format' => 'Y-m-d H:i:s');
    

    protected $_belongs_to = array(
        'file' => array(
            'model'       => 'Media_Storage_File',
            'foreign_key' => 'file_id',
        ),
    );
   
    public function labels(){
        return array(
            'file_id'          => ___('media_storage.fields.file_id'),
            'width'            => ___('media_storage.fields.width'),
            'height'           => ___('media_storage.fields.height'),
            'created_at'       => ___('media_storage.fields.created_at'),
            'updated_at'       => ___('media_storage.fields.updated_at'),
        );
    }
}