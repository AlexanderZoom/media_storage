<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Media_Storage extends Controller {
    
    
    public function action_upload(){
        $params = array(
            'template'   => 'media_storage/upload',
            'error'      => '',
            'error_code' => '',
        );
        $token = Arr::get($_GET, 'token');
        if (!$token){
            $params['error'] = 'Token is empty';
            $params['error_code'] = 'ERROR_TOKEN_EMPTY';
            return $this-> out($params);
        }
        
        try {
            $msm = Media_Storage_Main::getInstance();
            $mat = $msm->getAccessToken($token);
            $mat->checkAction(Model_Media_Storage_AccessToken::ACTION_UPLOAD);
            $file = Arr::get($_FILES, 'file', null);
            
            if (!$file){
                $params['error'] = 'File not loaded';
                $params['error_code'] = 'ERROR_FILE_NO_FILE';
                return $this-> out($params);
            }
            
            if ($file['error'] > 0){
                switch ($file['error']){
                    case UPLOAD_ERR_INI_SIZE :
                        $params ['error_code'] = 'ERROR_FILE_INI_SIZE';
                        break;
                    
                    case UPLOAD_ERR_FORM_SIZE :
                        $params ['error_code'] = 'ERROR_FILE_FROM_SIZE';
                        break;
                    
                    case UPLOAD_ERR_PARTIAL :
                        $params ['error_code'] = 'ERROR_FILE_PATIAL';
                        break;
                    
                    case UPLOAD_ERR_NO_FILE :
                        $params ['error_code'] = 'ERROR_FILE_NO_FILE';
                        break;
                    
                    case UPLOAD_ERR_NO_TMP_DIR :
                        $params ['error_code'] = 'ERROR_FILE_NO_TMP_DIR';
                        break;
                    
                    case UPLOAD_ERR_CANT_WRITE :
                        $params ['error_code'] = 'ERROR_FILE_CANT_WRITE';
                        break;
                    
                    case UPLOAD_ERR_EXTENSION :
                        $params ['error_code'] = 'ERROR_FILE_EXTENSION';
                        break;
                    
                    default:
                        $params ['error_code'] = 'ERROR_FILE_UNKNOWN';
                        
                }
                $params['error'] = 'File not loaded';
                return $this-> out($params);
            }
            
            $msl = $msm->getUploadLocationByAccessToken($mat);
            $fileInfo = array(
                'file' => trim($file['tmp_name']), // full path to the file on disk
                'name' => trim($file['name']),
                'token' => $mat, // token
            );
            
            $model = $msl->uploadFile($fileInfo);
            $params['file_id'] = $model->id;
            $params['file_name'] = $model->name;
            $params['file_size'] = $model->file_size;
            $params['file_type'] = $model->file_mime;
            $params['file_private'] = $model->private;
            $params['file_url'] = $msm->getDownloadUrl($model);
            
            
        }
        catch (Media_Storage_Exception_Model_AccessToken_Hash $e){
            $params['error'] = 'Token is incorrect';
            $params['error_code'] = 'ERROR_TOKEN_HASH';
        }
        catch (Media_Storage_Exception_Model_AccessToken_Expire $e){
            $params['error'] = 'Token is incorrect';
            $params['error_code'] = 'ERROR_TOKEN_EXPIRE';
        }
        catch (Media_Storage_Exception_Model_AccessToken_Action $e){
            $params['error'] = 'Token is incorrect';
            $params['error_code'] = 'ERROR_TOKEN_ACTION';
        }
//         catch (Exception $e){
//             if (Kohana::$environment > Kohana::PRODUCTION){
//                 $params['debug'] = $e->getCode() . ':' . $e->getMessage() . "\n" .$e->getTraceAsString();
//             }
//             $params['error'] = 'Unknown error';
//             $params['error_code'] = 'ERROR_UNKNOWN';
//         }
        
        return $this-> out($params);
            
    }
    
    protected function out($params){
        $paramsDefault = array(
            'error'      => '',
            'error_code' => '',
            'template'   => '',
            'status'     => 'OK'
        );
        
        $params = array_merge($paramsDefault, $params);
        
        if ($params['error']){
            $params['status'] = 'ERROR';
            $this->response->status(404);
        }
        
        $templ = View::factory($params['template'], $params);
        $this->response->body($templ);
    }
    
    public function action_download(){
        
            $params = array(
            'template'   => 'media_storage/download',
            'error'      => '',
            'error_code' => '',
        );
        $token = Arr::get($_GET, 'token');
        if (!$token){
            $params['error'] = 'Token is empty';
            $params['error_code'] = 'ERROR_TOKEN_EMPTY';
            return $this-> out($params);
        }
        
        try {
            $msm = Media_Storage_Main::getInstance();
            $mat = $msm->getAccessToken($token);
            $mat->checkAction(Model_Media_Storage_AccessToken::ACTION_DOWNLOAD);
            
            if (!$mat->get('file_id')){
                $params['error'] = 'File id is empty';
                $params['error_code'] = 'ERROR_TOKEN_EMPTY_FILEID';
                return $this-> out($params);
            }
            
            $model = ORM::factory('Media_Storage_File')
                    ->where('id', '=',  $mat->get('file_id'))
                    ->and_where('location_code', '=', $mat->get('location_code'))
                    ->and_where('category_code', '=', $mat->get('category_code'))
                    ->and_where('vfolder_id', '=', $mat->get('vfolder_id'))
                    ->find();
                        
            if (!$model->loaded()){
                $params['error'] = 'File not found';
                $params['error_code'] = 'ERROR_FILE_NOT_FOUND';
                return $this-> out($params);
            }
            
            
            if ($model->status =! Model_Media_Storage_File::FILE_STATUS_OK){
                switch ($model->status){
                    case Model_Media_Storage_File::FILE_STATUS_BANNED :
                        $params ['error'] = 'File banned';
                        $params ['error_code'] = 'ERROR_FILE_BANNED';
                        break;
                    
                    case Model_Media_Storage_File::FILE_STATUS_DELETED :
                        $params ['error'] = 'File deleted';
                        $params ['error_code'] = 'ERROR_FILE_DELETED';
                        break;
                    
                    case Model_Media_Storage_File::FILE_STATUS_NOTFOUND :
                        $params ['error'] = 'File not found';
                        $params ['error_code'] = 'ERROR_FILE_NOT_FOUND';
                        break;
                    
                    case Model_Media_Storage_File::FILE_STATUS_UPLOAD :
                        $params ['error'] = 'File not exist';
                        $params ['error_code'] = 'ERROR_FILE_UPLOAD';
                        break;
                    
                    default:
                        $params ['error'] = 'File status is unknown';
                        $params ['error_code'] = 'ERROR_FILE_STATUS_UNKNOWN';
                        break;
                }
                
                return $this-> out($params);
            }
            
            $location = $msm->getLocation($model->location_code);
            if (!$location){
                $params['error'] = 'Location not found';
                $params['error_code'] = 'ERROR_LOCATION_NOT_FOUND';
                return $this-> out($params);
            }
            
            $file = $model->location_path . DIRECTORY_SEPARATOR . $model->file_name;
            
            if (!$location->isFileExist($file)){
                $params['error'] = 'File not exist';
                $params['error_code'] = 'ERROR_LOCATION_NOT_FOUND';
                return $this-> out($params);
            }
            
            //get file
            $fileMime = $model->file_mime ? $model->file_mime : 'application/octet-stream';
            
            
            $start = null;
            $stop = null;
            if (!empty($_SERVER['HTTP_RANGE'])){
                $rangeList = Util_HTTPRange::parse($location->getFileSize($file), $_SERVER['HTTP_RANGE']);
                if (is_array($rangeList)){
                    $start = $rangeList[0][0];
                    $stop = $rangeList[0][1];
                }
            }
            
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            if (!is_null($start)){
                header('HTTP/1.1 206 Partial Content');
                header('Accept-Ranges: bytes');
                header('Content-Range: bytes ' . $start . '-' . $stop . '/' . $location->getFileSize($file));
            }
            else {
                header($_SERVER["SERVER_PROTOCOL"] . ' 200 OK');
            }
           
            // заставляем браузер показать окно сохранения файла
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $fileMime);
            header('Content-Disposition: attachment; filename=' . basename($model->name));
            header('Last-Modified: ' . $location->getFileLastModified($file));
            header('ETag: ' . $location->getFileETag($file));
            header('Content-Length: ' . $location->getFileSize($file));
            header('Connection: close');
            
            /* NGINX SUPPORT DOWNLOAD*/
            if ($location->nginxSupportDownload()){
                $file = $location->nginxSupportDownload() . $file;
                header('X-Accel-Redirect: ' . $file);
                exit;
            }
            
            // читаем файл и отправляем его пользователю
            $location->getFileContent($file, $start, $stop);
            exit;
        }
        catch (Media_Storage_Exception_Model_AccessToken_Hash $e){
            $params['error'] = 'Token is incorrect';
            $params['error_code'] = 'ERROR_TOKEN_HASH';
        }
        catch (Media_Storage_Exception_Model_AccessToken_Expire $e){
            $params['error'] = 'Token is incorrect';
            $params['error_code'] = 'ERROR_TOKEN_EXPIRE';
        }
        catch (Media_Storage_Exception_Model_AccessToken_Action $e){
            $params['error'] = 'Token is incorrect';
            $params['error_code'] = 'ERROR_TOKEN_ACTION';
        }
        catch (Exception $e){
            $params['error'] = 'Unknown error' . $e->getMessage();
            $params['error_code'] = 'ERROR_UNKNOWN';
        }
        
        return $this-> out($params);
    }
    
    
}