<?php defined('SYSPATH') OR die('No direct script access.');
/**
 *
 *
 * @package    PegasLab/Media
 * @author     PegasLab
 * @copyright  (c) 2013 PegasLab
 * @license    http://pegaslab.com/license
 */
class Media_Storage_Location_Local extends Media_Storage_Location {
    
    public function getRootPath(){
        return $this->_model->path;
    }
    
    public function getDirectoryType(){
        return 'local';
    }
    
    /**
     * Upload file
     * @param unknown $fileInfo
     */
    public function uploadFile($fileInfo){
    
        $fileInfoDefault = array(
        'file' => null, // full path to the file on disk
        'name' => '',
        'token' => null, // token
        'category_code' => null, //if not token
        'vfolder_id' => '', //if not token
        'data_type'  => null, // public or private
        );
        $fileInfo = array_merge($fileInfoDefault, $fileInfo);
         
        if ($fileInfo['file'] instanceof Model_Media_Storage_File && $fileInfo['file']->isFile()){
            $lc = Media_Storage_Main::getInstance()->getLocation($fileInfo['file']->location_code);
            if ($this->getHostId() != $lc->getHostId()){
                throw new Media_Storage_Exception_Location_UploadFile('File copy from diferent host not implemented');
            }
            
            $fileInfo['name'] = $fileInfo['file']->name;
            $fileInfo['file'] = $lc->getDirectoryObject()->getFullPath($fileInfo['file']->location_path . DIRECTORY_SEPARATOR . $fileInfo['file']->file_name);
        }
        
        
        if (!is_readable($fileInfo['file'])){
            throw new Media_Storage_Exception_Location_UploadFile('File not exists or not readable ' . $fileInfo['file']);
        }
         
        $categoryCode = null;
        $vfolderId = null;
        $fileName = $fileInfo['name'];
        $fileNameExt = '';
        if (preg_match('|([^/]*?)(\.(.{1,5}))?$|', $fileName, $match)){
            $fileNameExt = strtolower($match[3]);
        }
         
        if ($fileInfo['token'] && ($fileInfo['token'] instanceof Model_Media_Storage_AccessToken)){
            $fileInfo['token']->checkExpire();
            if (!$fileInfo['token']->isUpload()) throw new Media_Storage_Exception_Location_UploadFile('Incorrect token action for upload file');
            if ($fileInfo['token']->get('location_code') != $this->getCode()){
                throw new Media_Storage_Exception_Location_UploadFile("Incorrect location from token" .
                        "upload file. Location: {$this->getCode()} TokenLocation: {$fileInfo['token']->get('location_code')}");
            }
             
            $categoryCode = $fileInfo['token']->get('category_code');
            $vfolderId    = $fileInfo['token']->get('vfolder_id');
            $fileInfo['data_type'] = $fileInfo['token']->get('data_type');
        }
        else {
            $categoryCode = $fileInfo['category_code'];
            $vfolderId    = $fileInfo['vfolder_id'];
        }
         
        if (!in_array($fileInfo['data_type'], array(self::DATA_TYPE_PRIVATE, self::DATA_TYPE_PUBLIC))){
            throw new Media_Storage_Exception_Location_UploadFile("Incorrect data type {$fileInfo['data_type']}");
        }
    
        if (count($this->_model->get('data_type')) && !in_array($fileInfo['data_type'], $this->_model->get('data_type'))){
            throw new Media_Storage_Exception_Location_UploadFile("Data type {$fileInfo['data_type']} not supporting for it location {$this->getCode()}");
        }
         
        $categories = $this->_model->category;
        if (count($categories) && !in_array($categoryCode, $categories)){
            throw new Media_Storage_Exception_Location_UploadFile("Incorrect category code {$categoryCode} for location {$this->getCode()}");
        }
    
        if ($vfolderId){
            $vmodel = ORM::factory('Media_Storage_VFolder')
            ->where('category_code', '=', $categoryCode)
            ->and_where('id', '=', $vfolderId)
            ->find();
            if (!$vmodel->loaded()){
                throw new Media_Storage_Exception_Location_UploadFile("Incorrect vmodel id {$vfolderId} for category {$categoryCode}");
            }
        }
         
         
        $fileSize = filesize($fileInfo['file']);
         
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileMime = finfo_file($finfo, $fileInfo['file']);
        finfo_close($finfo);
         
        $this->_directory->addReservedSpace($fileSize);
        try {
            $newFile = $this->_directory->getFileObject()->uploadFile($fileInfo['data_type'], $fileInfo['file'], $fileNameExt);
             
             
            //check on dup
            $newFileTmp = str_replace($this->getRootPath(), '', $newFile);
            $newFileTmp = explode('/',$newFileTmp);
            $newFileName = $newFileTmp[count($newFileTmp)-1];
            unset($newFileTmp[count($newFileTmp)-1]);
            if (!$newFileTmp[0]) unset($newFileTmp[0]);
            $newPath = implode('/', $newFileTmp);
             
            try {
                 
                $attempts = 8;
                while($attempts){
                    $attempts--;
                    try {
                        $model = ORM::factory('Media_Storage_File');
                        $model->location_code = $this->getCode();
                        $model->category_code = $categoryCode;
                        $model->vfolder_id = $vfolderId;
                        $model->location_path = $newPath;
                        $model->file_name = $newFileName;
                        $model->file_extension = $fileNameExt;
                        $model->file_size = $fileSize;
                        $model->file_mime = $fileMime;
                        $model->name = $fileName;
                        $model->type = Model_Media_Storage_File::FILE_TYPE_NORMAL;
                        $model->private = $fileInfo['data_type'] == self::DATA_TYPE_PRIVATE
                        ? Model_Media_Storage_File::FILE_PRIVATE_YES : Model_Media_Storage_File::FILE_PRIVATE_NO;
                        $model->status = Model_Media_Storage_File::FILE_STATUS_OK;
                        $model->save();
                        $this->_directory->delReservedSpace($fileSize);
                        return $model;
                    }
                    catch (Database_Exception $e){
                        if($e->getCode() == 23000){
                            $fileName = explode('.', $fileName);
                            $fileName = $fileName[0] . '_' . rand(1, 1000) . '.' . $fileName[1];
                        }
                        else throw $e;
                    }
                     
                    if ($attempts < 1){
                        throw new Media_Storage_Exception_Location_UploadFile("Attempts for save file model excided");
                    }
                }
            }
            catch (Exception $e){
                $this->_directory->getFileObject()->deleteFile($newFile);
                throw $e;
            }
        }
        catch (Exception $e){
            $this->_directory->delReservedSpace($fileSize);
            throw $e;
        }
         
    }
}