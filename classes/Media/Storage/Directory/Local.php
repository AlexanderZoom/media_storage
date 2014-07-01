<?php defined('SYSPATH') OR die('No direct script access.');
/**
 *
 *
 * @package    PegasLab/Media
 * @author     PegasLab
 * @copyright  (c) 2013 PegasLab
 * @license    http://pegaslab.com/license
 */
class Media_Storage_Directory_Local extends Media_Storage_Directory {
    
    const  DIR_DATA_TYPE_PUBLIC = 'public';
    const  DIR_DATA_TYPE_PRIVATE = 'private';
    
    public function __construct(Media_Storage_Location $location){
        parent::__construct($location);
        
        if (!file_exists($location->getRootPath())){
            throw new Media_Storage_Exception_Directory("Directory {$location->getRootPath()} not exist. Location: {$location->getCode()}");
        }
    }
    
    public function init(){
        $this->_fileObject = new Media_Storage_File_Local($this);
    }
    
    public function getDiskFreeSpace(){
        return disk_free_space($this->_location->getRootPath());
    }
 
    protected function makeDir($name){
        if ($name{0} != DIRECTORY_SEPARATOR){
            $name = DIRECTORY_SEPARATOR . $name;
            $dir = $this->_location->getRootPath() . $name;
        }
        else $dir = $name;
        
        if (!@mkdir($dir, 0777, true)){
            throw new Media_Storage_Exception_Directory_MakeDir("Directory not created {$dir}");
        }
        
        return true;
    }
    
    public function getUploadPath($dataType){
        if (!in_array($dataType, array(Media_Storage_Location::DATA_TYPE_PRIVATE, Media_Storage_Location::DATA_TYPE_PUBLIC))){
            throw new Media_Storage_Exception_Directory_UploadPath("Incorrect DataType " . $dataType);
        }
        
        $dirDataType = self::DIR_DATA_TYPE_PUBLIC;
        if ($dataType == Media_Storage_Location::DATA_TYPE_PRIVATE) $dirDataType = self::DIR_DATA_TYPE_PRIVATE;
        $path = $this->_location->getRootPath() . DIRECTORY_SEPARATOR . $dirDataType;
        if (!file_exists($path)) $this->makeDir($path);
        return $this->lastDir($path);
        
    }
    
    public function getFullPath($path){
        return $path = $this->_location->getRootPath() . DIRECTORY_SEPARATOR . $path;
    }
    
    public function countFiles($path){
        $files = 0;
        if ($handle = opendir($path)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    if (is_file($path.DIRECTORY_SEPARATOR.$entry)) $files++;
                }
                }
                closedir($handle);
        }
        else throw new Media_Storage_Exception_Directory('Can not open dir ' . $path);
        
        return $files;
    }
    
    public function lastDir($path){
        $dirs = $this->getDirNames($path);
        $dirFirst = 0;
        $dirFirstCount = count($dirs);
        if ($dirFirstCount){
            $dirFirst = $dirFirstCount - 1;
        }
        else $dirFirst = 0;
        
        $pathFirst = $path . DIRECTORY_SEPARATOR . $dirFirst;
        if (!file_exists($pathFirst)) $this->makeDir($pathFirst);
        
        $dirs = $this->getDirNames($pathFirst);
        $dirSecond = 0;
        $dirSecondCount = count($dirs);
        if ($dirSecondCount){
            $dirSecond = $dirSecondCount -1;
        }
        else $dirSecondCount = 0;
        
        $pathSecond = $pathFirst . DIRECTORY_SEPARATOR . $dirSecond;
        if (!file_exists($pathSecond)) $this->makeDir($pathSecond);
        
        $cntFiles = $this->countFiles($pathSecond);
        
        if ($cntFiles >= $this->getFileObject()->getFileMax()){
            $dirSecond ++;
            if ($dirSecond >= $this->getSubFolderMax()){
                $dirSecond = 0;
                $dirFirst ++;
                if ($dirFirst >= $this->getFolderMax()){
                    throw new Media_Storage_Exception_Directory_UploadPath("Location {$this->_location->getCode()} excided by folders and files");
                }
                $pathFirst = $path . DIRECTORY_SEPARATOR . $dirFirst;
                if (!file_exists($pathFirst)) $this->makeDir($pathFirst);
            }
            $pathSecond = $pathFirst . DIRECTORY_SEPARATOR . $dirSecond;
            if (!file_exists($pathSecond)) $this->makeDir($pathSecond);
        }
        
        return $pathSecond;
    }
    
    protected function getDirNames($path){
        $dirs = array();
        if ($handle = opendir($path)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    if (is_dir($path.DIRECTORY_SEPARATOR.$entry)) $dirs[] = $entry;
                }
            }
            closedir($handle);
        }
        else throw new Media_Storage_Exception_Directory('Can not open dir ' . $path);
        
        return $dirs;
    }
    
    
}
