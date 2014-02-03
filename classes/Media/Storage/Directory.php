<?php defined('SYSPATH') OR die('No direct script access.');
/**
 *
 *
 * @package    PegasLab/Media
 * @author     PegasLab
 * @copyright  (c) 2013 PegasLab
 * @license    http://pegaslab.com/license
 */
abstract class Media_Storage_Directory {
    
    /**
     *
     * @var Media_Storage_Location
     */
    protected $_location;
    
    public static function find(Media_Storage_Location $location){
        $class = null;
        switch ($location->getDirectoryType()){
            case 'local':
                $class = new Media_Storage_Directory_Local($location);
                break;
    
            case 'ftp':
                $class = new Media_Storage_Directory_Ftp($location);
                break;
        }
         
        if (!$class) throw new Exception("Specific class for directory type '{$model->type}' not found");
         
        return $class;
    }
    
    public function __construct(Media_Storage_Location $location){
        $this->_location = $location;
    }
    
    public function getReservedSpace(){
        $model = ORM::factory('Media_Storage_ReservedSize', $this->_location->getCode());
        if ($model->loaded()) return $model->size;
        return 0;
    }
    
    public function addReservedSpace($size){
        $model = ORM::factory('Media_Storage_ReservedSize', $this->_location->getCode());
        if ($model->loaded()){
            $model->size += $size;
            
            $query = DB::update($model->table_name())
            ->value('size', DB::expr("size + {$size}"))
            ->value('updated_at', date('Y-m-d H:i:s'))
            ->where('location_code', '=', $this->_location->getCode());
            DB::query(Database::UPDATE, $query)->execute();
        }
        else {
            $model->location_code = $this->_location->getCode();
            $model->size = $size;
            $model->save();
        }
    
        
        return true;
    }
    
    public function delReservedSpace($size){
        $model = ORM::factory('Media_Storage_ReservedSize', $this->_location->getCode());
        if ($model->loaded()){
            $model->size -= $size;
            if ($model->size < 0) $model->size = 0;
            
            $query = DB::update($model->table_name())
            ->value('size', $model->size ? DB::expr("size - {$size}") : 0)
            ->value('updated_at', date('Y-m-d H:i:s'))
            ->where('location_code', '=', $this->_location->getCode());
            DB::query(Database::UPDATE, $query)->execute();
            
            //$model->save();
        }
    
        return true;
    }
    
    public function getFolderMax(){
        return $this->_location->getFolderMax();
    }
    
    public function getSubFolderMax(){
        return $this->_location->getSubFolderMax();
    }
    
    public function getFileMax(){
        return $this->_location->getFileMax();
    }
    
    public function getFreeSpace(){
        return ($this->getDiskFreeSpace() - $this->getReservedSpace());
    }
    
    protected function _generateFileName($length){
        $chars = 'abcdefghijklmnopqrstuvwxyz1234567890';
        $name = '';
        
        if ($length < 1) return $name;
                
        $charsLength = (strlen($chars) - 1);
        for($i = 0; $i < $length; $i++){
            $name .= $chars{rand(0, $charsLength)};
        }
        
        return $name;
    }
    
    abstract public function getDiskFreeSpace();
    abstract public function getUploadPath($dataType);
    abstract public function uploadFile($dataType, $file, $extension);
    abstract protected function makeDir($name);
    
    abstract public function countFiles($path);
    abstract public function lastDir($path);
    abstract public function generateFileName($extension, $path, $length);
    abstract public function copyFile($from, $to);
    abstract public function moveFile($from, $to);
    abstract public function deleteFile($file);
    abstract public function isFileExist($file);
    abstract public function getFileContent($file, $start = null, $stop = null);
    abstract public function getFilesize($file);
    abstract public function getFileETag($file);
    abstract public function getFileLastModified($file);
    
    
    
}