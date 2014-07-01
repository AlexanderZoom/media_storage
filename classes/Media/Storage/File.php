<?php defined('SYSPATH') OR die('No direct script access.');
/**
 *
 *
 * @package    PegasLab/Media
 * @author     PegasLab
 * @copyright  (c) 2013 PegasLab
 * @license    http://pegaslab.com/license
 */
abstract class Media_Storage_File {
    
    /**
     *
     * @var Media_Storage_Directory
     */
    protected $_directory;
    
    public function __construct(Media_Storage_Directory $directory){
        $this->_directory = $directory;
    }
    
    public function getDirectoryObject(){
        return $this->_directory;
    }
    
    public function getFileMax(){
        return $this->getDirectoryObject()->getLocationObject()->getFileMax();
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
    
    abstract public function uploadFile($dataType, $file, $extension);
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