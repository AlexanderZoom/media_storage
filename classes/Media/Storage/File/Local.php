<?php defined('SYSPATH') OR die('No direct script access.');
/**
 *
 *
 * @package    PegasLab/Media
 * @author     PegasLab
 * @copyright  (c) 2013 PegasLab
 * @license    http://pegaslab.com/license
 */
class Media_Storage_File_Local extends Media_Storage_File {
	
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
        $path = $this->getDirectoryObject()->getUploadPath($dataType);
    
        $fileName = $this->generateFileName($extension, $path, $this->getDirectoryObject()->getLocationObject()->getFileLength());
        $fileNameNew = '';
    
        try {
            $fileNameNew = $path . DIRECTORY_SEPARATOR . $fileName;
            $this->copyFile($file, $fileNameNew);
        }
        catch (Exception $e){
            $this->deleteFile($path . DIRECTORY_SEPARATOR . $fileName);
            throw $e;
        }
    
        return $fileNameNew;
    }
    
    public function copyFile($from, $to){
        if (!@copy($from, $to)) {
            throw new Media_Storage_Exception_File('Can not copy file from ' . $from . ' to ' . $to);
        }
    
        return true;
    }
    
    public function moveFile($from, $to){
        if (!@rename($from, $to)) {
            throw new Media_Storage_Exception_File('Can not move file from ' . $from . ' to ' . $to);
        }
    
        return true;
    }
    
    public function deleteFile($file){
        if (!@unlink($file)){
            throw new Media_Storage_Exception_File('Can not delete file ' . $file);
        }
    
        return true;
    }
    
    public function isFileExist($file){
        if (file_exists($this->getDirectoryObject()->getLocationObject()->getRootPath() . DIRECTORY_SEPARATOR . $file)){
            return true;
        }
    
        return false;
    }
    
    public function getFileContent($file, $start = null, $stop = null){
        if (!$this->isFileExist($file)){
            throw new Media_Storage_Exception_File('File not exist. File: ' . $file . ' Location: ' . $this->getDirectoryObject()->getLocationObject()->getCode());
        }
    
        $length = $this->getFilesize($file);
        $file = $this->getDirectoryObject()->getLocationObject()->getRootPath() . DIRECTORY_SEPARATOR . $file;
    
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
            throw new Media_Storage_Exception_File('File not exist. File: ' . $file . ' Location: ' . $this->getDirectoryObject()->getLocationObject()->getCode());
        }
    
        return filesize($this->getDirectoryObject()->getLocationObject()->getRootPath() . DIRECTORY_SEPARATOR . $file);
    }
    
    public function getFileETag($file){
        if (!$this->isFileExist($file)){
            throw new Media_Storage_Exception_File('File not exist. File: ' . $file . ' Location: ' . $this->getDirectoryObject()->getLocationObject()->getCode());
        }
    
        $file = $this->getDirectoryObject()->getLocationObject()->getRootPath() . DIRECTORY_SEPARATOR . $file;
    
        return sprintf('%x-%x-%x', fileinode($file), filesize($file), filemtime($file));
    }
    
    public function getFileLastModified($file){
        if (!$this->isFileExist($file)){
            throw new Media_Storage_Exception_File('File not exist. File: ' . $file . ' Location: ' . $this->getDirectoryObject()->getLocationObject()->getCode());
        }
    
        $file = $this->getDirectoryObject()->getLocationObject()->getRootPath() . DIRECTORY_SEPARATOR . $file;
    
        return gmdate('r', filemtime($file));
    }
}