<?php

class FileStorage {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains array of available files in database as id=>filedata
     *
     * @var array
     */
    protected $allFiles = array();

    /**
     * Contains current filestorage items scope
     *
     * @var string
     */
    protected $scope = '';

    /**
     * Contains current instance item ID in the current scope
     *
     * @var string
     */
    protected $itemId = '';

    /**
     * Contains current administrator login
     *
     * @var string
     */
    protected $myLogin = '';

    /**
     * Current instance database abstraction layer placeholder
     *
     * @var object
     */
    protected $storageDb = '';

    /**
     * Contains default file preview container size in px
     *
     * @var int
     */
    protected $filePreviewSize = 128;

    /**
     * Contains allowed file extensions. May be configurable in future.
     *
     * @var array
     */
    protected $allowedExtensions = array();

    /**
     * System message helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Some predefined paths and URLs
     */
    const TABLE_STORAGE = 'filestorage';
    const STORAGE_PATH = 'content/documents/filestorage/';
    const URL_ME = '?module=filestorage';
    const URL_UPLOAD_FILE = '?module=filestorage&uploadfile=true';
    const EX_NOSCOPE = 'NO_OBJECT_SCOPE_SET';
    const EX_WRONG_EXT = 'WRONG_FILE_EXTENSION';

    /**
     * Initializes filestorage engine for some scope/item id
     * 
     * @param string $scope
     * @param string $itemid
     * 
     * @return void
     */
    public function __construct($scope = '', $itemid = '') {
        $this->initMessages();
        $this->loadAlter();
        $this->setAllowedExtenstions();
        $this->setScope($scope);
        $this->setItemid($itemid);
        $this->setLogin();
        $this->initDatabase();
    }

    /**
     * Inits system message helper for further usage
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads system alter config into private prop
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets allowed file extensions for this instance
     * 
     * @return void
     */
    protected function setAllowedExtenstions() {
        $this->allowedExtensions = array('jpg', 'gif', 'png', 'jpeg', 'xls', 'doc', 'odt', 'docx', 'pdf', 'txt');
        $this->allowedExtensions = array_flip($this->allowedExtensions); //extension string => index
    }

    /**
     * Object scope setter
     * 
     * @param string $scope Object actual scope
     * 
     * @return void
     */
    protected function setScope($scope) {
        $this->scope = ubRouting::filters($scope, 'mres');
    }

    /**
     * Object scope item Id setter
     * 
     * @param string $scope Object actual id in current scope
     * 
     * @return void
     */
    protected function setItemid($itemid) {
        $this->itemId = ubRouting::filters($itemid, 'mres');
    }

    /**
     * Administrator login setter
     * 
     * @return void
     */
    protected function setLogin() {
        $this->myLogin = whoami();
    }

    /**
     * Inits protected database absctaction layer for current instance
     * 
     * @return void
     */
    protected function initDatabase() {
        $this->storageDb = new NyanORM(self::TABLE_STORAGE);
    }

    /**
     * Loads files list from database into private prop
     * 
     * @return void
     */
    protected function loadAllFiles() {
        if ((!empty($this->scope)) AND ( !empty($this->itemId))) {
            $this->allFiles = $this->storageDb->getAll('id');
        }
    }

    /**
     * Registers uploaded file in database
     * 
     * @param string $filename
     * 
     * @return void
     */
    protected function registerFile($filename) {
        if ((!empty($this->scope)) AND ( !empty($this->itemId))) {
            $filename = ubRouting::filters($filename, 'mres');
            $date = curdatetime();

            $this->storageDb->data('scope', $this->scope);
            $this->storageDb->data('item', $this->itemId);
            $this->storageDb->data('date', $date);
            $this->storageDb->data('admin', $this->myLogin);
            $this->storageDb->data('filename', $filename);
            $this->storageDb->create();

            log_register('FILESTORAGE CREATE SCOPE `' . $this->scope . '` ITEM [' . $this->itemId . ']');
        }
    }

    /**
     * Deletes uploaded file from database
     * 
     * @param int $fileId
     * 
     * @return void
     */
    protected function unregisterFile($fileId) {
        if ((!empty($this->scope)) AND ( !empty($this->itemId))) {
            $fileId = ubRouting::filters($fileId, 'int');
            $date = curdatetime();

            $this->storageDb->where('id', '=', $fileId);
            $this->storageDb->delete();

            log_register('FILESTORAGE DELETE SCOPE `' . $this->scope . '` ITEM [' . $this->itemId . ']');
        }
    }

    /**
     * Returns basic file controls
     * 
     * @param int $fileId existing file ID
     * 
     * @return string
     */
    protected function fileControls($fileId) {
        $fileId = ubRouting::filters($fileId, 'int');
        $result = wf_tag('br');
        $downloadUrl = self::URL_ME . '&scope=' . $this->scope . '&itemid=' . $this->itemId . '&download=' . $fileId;

        $result .= wf_Link($downloadUrl, wf_img('skins/icon_download.png') . ' ' . __('Download'), false, 'ubButton') . ' ';
        if (cfr('FILESTORAGEDELETE')) {
            $deleteUrl = self::URL_ME . '&scope=' . $this->scope . '&itemid=' . $this->itemId . '&delete=' . $fileId;
            $result .= wf_AjaxLink($deleteUrl, web_delete_icon() . ' ' . __('Delete'), 'ajRefCont_' . $fileId, false, 'ubButton') . ' ';
        }
        return ($result);
    }

    /**
     * Returns file upload controls
     * 
     * @return string
     */
    public function uploadControlsPanel() {
        $result = '';
        if ((!empty($this->scope)) AND ( !empty($this->itemId))) {
            $result .= wf_Link(self::URL_ME . '&scope=' . $this->scope . '&itemid=' . $this->itemId . '&mode=loader', wf_img('skins/photostorage_upload.png') . ' ' . __('Upload file from HDD'), false, 'ubButton');
        }

        return ($result);
    }

    /**
     * Returns custom module backlinks for some scopes
     * 
     * @return string
     */
    protected function backUrlHelper() {
        $result = '';
        if ($this->scope == 'USERPROFILE') {
            $result = web_UserControls($this->itemId);
        }
        return ($result);
    }

    /**
     * Renders file preview icon
     * 
     * @param string $filename
     * 
     * @return string
     */
    protected function renderFilePreviewIcon($filename) {
        $result = '';
        if (!empty($filename)) {
            $result .= wf_img('skins/somebox.png', $filename);
        }
        return($result);
    }

    /**
     * Returns current scope/item files list
     * 
     * @return string
     */
    public function renderFilesList() {
        if (empty($this->allFiles)) {
            $this->loadAllFiles();
        }

        $result = wf_AjaxLoader();

        if (!empty($this->allFiles)) {
            foreach ($this->allFiles as $io => $eachFile) {
                if (($eachFile['scope'] == $this->scope) AND ( $eachFile['item'] == $this->itemId)) {
                    $dimensions = 'width:' . ($this->filePreviewSize + 220) . 'px;';
                    $dimensions .= 'height:' . ($this->filePreviewSize + 60) . 'px;';
                    $result .= wf_tag('div', false, '', 'style="border: 1px solid; float:left;  ' . $dimensions . ' margin:15px;" id="ajRefCont_' . $eachFile['id'] . '"');
                    $result .= wf_tag('center');
                    $result .= $this->renderFilePreviewIcon($eachFile['filename']);
                    $result .= $this->fileControls($eachFile['id']);
                    $result .= wf_tag('center', true);
                    $result .= wf_tag('div', true);
                }
            }
        }

        $result .= wf_CleanDiv();
        $result .= wf_delimiter();
        $result .= $this->backUrlHelper();
        return ($result);
    }

    /**
     * Downloads file by its id
     * 
     * @param int $fileId database file ID
     * 
     * @return void
     */
    public function catchDownloadFile($fileId) {
        $fileId = ubRouting::filters($fileId, 'int');

        if (empty($this->allFiles)) {
            $this->loadAllFiles();
        }
        if (!empty($fileId)) {
            @$filename = $this->allFiles[$fileId]['filename'];
            if (file_exists(self::STORAGE_PATH . $filename)) {
                zb_DownloadFile(self::STORAGE_PATH . $filename, 'default');
            } else {
                show_error(__('File not exist'));
            }
        } else {
            show_error(__('File not exists'));
        }
    }

    /**
     * Deletes file from database and FS by its ID
     * 
     * @param int $fileId database file ID
     * 
     * @return void
     */
    public function catchDeleteFile($fileId) {
        $fileId = ubRouting::filters($fileId, 'int');

        if (empty($this->allFiles)) {
            $this->loadAllFiles();
        }
        if (!empty($fileId)) {
            @$filename = $this->allFiles[$fileId]['filename'];
            if (file_exists(self::STORAGE_PATH . $filename)) {
                if (cfr('FILESTORAGEDELETE')) {
                    unlink(self::STORAGE_PATH . $filename);
                    $this->unregisterFile($fileId);
                    $deleteResult = $this->messages->getStyledMessage(__('Deleted'), 'warning');
                } else {
                    $deleteResult = $this->messages->getStyledMessage(__('Access denied'), 'error');
                }
            } else {
                $deleteResult = $this->messages->getStyledMessage(__('File not exist') . ': ' . $filename, 'error');
            }
        } else {
            $deleteResult = $this->messages->getStyledMessage(__('File not exist') . ': [' . $fileId . ']', 'error');
        }
        die($deleteResult);
    }

    /**
     * Catches file upload in background
     * 
     * @return void
     */
    public function catchFileUpload() {
        if (ubRouting::checkGet('uploadfile')) {
            if (!empty($this->scope)) {
                $fileAccepted = true;
                foreach ($_FILES as $file) {
                    if ($file['tmp_name'] > '') {
                        $uploadedFileExtension = pathinfo(strtolower($file['name']), PATHINFO_EXTENSION);

                        if (!isset($this->allowedExtensions[$uploadedFileExtension])) {
                            $fileAccepted = false;
                        }
                    }
                }

                if ($fileAccepted) {
                    $originalFileName = zb_TranslitString($file['name']); //prevent cyrillic filenames on FS
                    $newFilename = zb_rand_string(6) . '_' . $originalFileName;
                    $newSavePath = self::STORAGE_PATH . $newFilename;
                    @move_uploaded_file($_FILES['filestorageFileUpload']['tmp_name'], $newSavePath);
                    if (file_exists($newSavePath)) {
                        $uploadResult = $this->messages->getStyledMessage(__('File upload complete'), 'success');
                        $this->registerFile($newFilename);
                        rcms_redirect(self::URL_ME . '&scope=' . $this->scope . '&itemid=' . $this->itemId . '&mode=loader&preview=' . $newFilename);
                    } else {
                        $uploadResult = $this->messages->getStyledMessage(__('File upload failed'), 'error');
                    }
                } else {
                    $uploadResult = $this->messages->getStyledMessage(__('File upload failed') . ': ' . self::EX_WRONG_EXT, 'error');
                }
            } else {
                $uploadResult = $this->messages->getStyledMessage(__('Strange exeption') . ': ' . self::EX_NOSCOPE, 'error');
            }

            show_window('', $uploadResult);
            show_window('', wf_BackLink(self::URL_ME . '&scope=' . $this->scope . '&itemid=' . $this->itemId . '&mode=loader'));
        }
    }

    /**
     * Returns file upload form
     * 
     * @return string
     */
    public function renderUploadForm() {
        $postUrl = self::URL_UPLOAD_FILE . '&scope=' . $this->scope . '&itemid=' . $this->itemId;
        $inputs = wf_tag('form', false, 'glamour', 'action="' . $postUrl . '" enctype="multipart/form-data" method="POST"');
        $inputs .= wf_tag('input', false, '', 'type="file" name="filestorageFileUpload"');
        $inputs .= wf_Submit(__('Upload'));
        $inputs .= wf_tag('form', true);

        $result = $inputs;
        $result .= wf_delimiter(2);
        if (wf_CheckGet(array('preview'))) {
            $result .= $this->messages->getStyledMessage(__('File upload complete'), 'success');
            $result .= wf_delimiter();
        }
        $result .= wf_BackLink(self::URL_ME . '&scope=' . $this->scope . '&itemid=' . $this->itemId . '&mode=list');
        return ($result);
    }

}