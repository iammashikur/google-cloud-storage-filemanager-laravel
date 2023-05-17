<?php

namespace App\Lib;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class GCSFileManager
{
    /**
     * ftp driver
     *
     *
     * @var object
     */

    protected $driver;

    /**
     * The file which will be uploaded
     *
     *
     * @var object
     */

    protected $file;

    /**
     * The path where will be uploaded
     *
     * @var string
     */
    public $path;

    /**
     * The size, if the file is image
     *
     * @var string
     */
    public $size;

    /**
     * Check the file is image or not
     *
     * @var boolean
     */
    protected $isImage;

    /**
     * Thumbnail version size, if required
     * and if the file is image
     *
     * @var string
     */
    public $thumb;

    /**
     * Old filename, which will be removed
     *
     * @var string
     */
    public $old;

    /**
     * Current filename, which is uploading
     *
     * @var string
     */
    public $filename;


    public function __construct($file = null)
    {
       
        
        $this->file = $file;

        if ($file) {
            $imageExtensions = ['jpg', 'jpeg', 'png', 'JPG', 'JPEG', 'PNG'];
            if (in_array($file->getClientOriginalExtension(), $imageExtensions)) {
                $this->isImage = true;
            } else {
                $this->isImage = false;
            }
        }


        $this->driver = Storage::Disk('gcs');
    }

    public function upload()
    {
        //create the directory if doesn't exists
        $path = $this->makeDirectory();

        if (!$path) throw new \Exception('File could not been created.');

        $filename = $this->getFileName();
        $this->filename = $filename;

        //upload file or image
        if ($this->isImage == true) {
            $this->uploadImage();
        } else {
            $this->uploadFile();
        }
    }

    public function uploadImage($image = null, $filename = null, $isThumb = false)
    {
        if ($filename) {
            $this->filename = $filename;
        }

        if ($isThumb) {
            $this->thumb = true;
            $separator = '/thumb_';
        } else {
            $this->thumb = false;
            $separator = '/';
        }

        //remove the old file if exist
        if ($this->old) {
            $this->removeFile();
        }

        $image = $image->stream();
        $this->driver->put($this->path . $separator . $this->filename,  $image->__toString());
    }


    /**
     * Upload the file if this is not a image
     *
     * @return void
     */

    protected function uploadFile()
    {
        //remove the old file if exist
        if ($this->old) {
            $this->removeFile();
        }

        $this->driver->put($this->path . '/' . $this->filename, fopen($this->file, 'r+'));
    }


    /**
     * Generating the filename which is uploading
     *
     * @return string
     */

    protected function getFileName()
    {
        return uniqid() . time() . '.' . $this->file->getClientOriginalExtension();
    }


    /**
     * Remove the file if exists
     * Developer can also call this method statically
     *
     * @param $path
     * @return void
     */

    public function removeFile($path = null)
    {
        if (str_contains($this->old, '/')) {
            $files = explode('/', $this->old);
            $this->old = end($files);
        }

        if ($this->thumb) {
            $path = $this->path . '/thumb_' . $this->old;
        }

        if (!$path) $path = $this->path . '/' . $this->old;

        if ($this->driver->exists($path)) {
            $this->driver->delete($path);
        }
    }
    


    /**
     * Make directory doesn't exists
     * Developer can also call this method statically
     *
     * @param $location
     * @return string
     */

    protected function makeDirectory()
    {
        if ($this->driver->exists($this->path)) {
            return true;
        }
        return $this->driver->makeDirectory($this->path);
    }
}
