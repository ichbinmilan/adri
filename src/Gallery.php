<?php

namespace App;

use Psr\Container\ContainerInterface;

class Gallery
{
    public $images;

    /**
     * Gallery constructor.
     * @param $imgDir
     */
    public function __construct($folderName)
    {
        $imgDir = realpath($folderName) . '/';
        $thumbDir = $imgDir . 'thumb';

        if (!file_exists($imgDir) || !is_dir($imgDir) || !file_exists($thumbDir) || !is_dir($thumbDir)) {
            return $this->images = false;
        }

        $filesInImg = scandir($imgDir);

        foreach ($filesInImg as $imgFile) {
            if (is_file($imgDir . $imgFile) && !is_dir($imgDir . $imgFile)) {
                $images[] = $imgFile;
//                $images[] = '/' . $folderName . '/' . $imgFile;
            }
        }
        return $this->images = $images;


    }

}