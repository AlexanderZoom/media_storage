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
        
        if ($cntFiles >= $this->getFileMax()){
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
    
    /**
     * Create empty file
     */
    public function generateFileName($extension, $path, $length){
        $attempts = 7;
        while ($attempts--){
            $fileName = $this->_generateFileName($length) . '.' . $extension;
            $fullFilename = $path . DIRECTORY_SEPARATOR . $fileName;
            if (touch($fullFilename)){
                if (filesize($fullFilename) == 0) return $fileName;
            }
        }
        
        throw new Media_Storage_Exception_Directory('Can not generate file name');
    }
    
    public function uploadFile($dataType, $file, $extension){
        $path = $this->getUploadPath($dataType);
        
        $fileName = $this->generateFileName($extension, $path, $this->_location->getFileLength());
        $fileNameNew = '';
        
        try {
            $fileNameNew = $path . DIRECTORY_SEPARATOR . $fileName;
            $this->moveFile($file, $fileNameNew);
        }
        catch (Exception $e){
            $this->deleteFile($path . DIRECTORY_SEPARATOR . $fileName);
            throw $e;
        }
        
        return $fileNameNew;
    }
    
    public function copyFile($from, $to){
        if (!@copy($from, $to)) {
            throw new Media_Storage_Exception_Directory('Can not copy file from ' . $from . ' to ' . $to);
        }
        
        return true;
    }
    
    public function moveFile($from, $to){
        if (!@rename($from, $to)) {
            throw new Media_Storage_Exception_Directory('Can not move file from ' . $from . ' to ' . $to);
        }
        
        return true;
    }
    
    public function deleteFile($file){
        if (!@unlink($file)){
            throw new Media_Storage_Exception_Directory('Can not delete file ' . $file);
        }
        
        return true;
    }
    
    public function isFileExist($file){
        if (file_exists($this->_location->getRootPath() . DIRECTORY_SEPARATOR . $file)){
            return true;
        }
        
        return false;
    }
    
    public function getFileContent($file, $start = null, $stop = null){
        if (!$this->isFileExist($file)){
            throw new Media_Storage_Exception_Directory('File not exist. File: ' . $file . ' Location: ' . $this->_location->getCode());
        }
        
        $length = $this->getFilesize($file);
        $file = $this->_location->getRootPath() . DIRECTORY_SEPARATOR . $file;
        
        if ($fd = fopen($file, 'rb')) {
            if (!is_null($start)){
                fseek($fd, $start, SEEK_SET );
            }
            
            
            if (!is_null($stop)){
                $length = $stop - $start;
            }
            
            $defBufer = 1024;
            while($length){
                $bufer = $defBufer;
                if ($defBufer > $length) $bufer = $length;
                print fread($fd, $bufer);
                flush(); ob_flush();
                $length -= $bufer;
            }
            fclose($fd);
        }
    }
    
    public function getFilesize($file){
        if (!$this->isFileExist($file)){
            throw new Media_Storage_Exception_Directory('File not exist. File: ' . $file . ' Location: ' . $this->_location->getCode());
        }
        
        return filesize($this->_location->getRootPath() . DIRECTORY_SEPARATOR . $file);
    }
    
    public function getFileETag($file){
        if (!$this->isFileExist($file)){
            throw new Media_Storage_Exception_Directory('File not exist. File: ' . $file . ' Location: ' . $this->_location->getCode());
        }
        
        $file = $this->_location->getRootPath() . DIRECTORY_SEPARATOR . $file;
        
        return sprintf('%x-%x-%x', fileinode($file), filesize($file), filemtime($file));
    }
    
    public function getFileLastModified($file){
        if (!$this->isFileExist($file)){
            throw new Media_Storage_Exception_Directory('File not exist. File: ' . $file . ' Location: ' . $this->_location->getCode());
        }
        
        $file = $this->_location->getRootPath() . DIRECTORY_SEPARATOR . $file;
        
        return gmdate('r', filemtime($file));
    }
}
