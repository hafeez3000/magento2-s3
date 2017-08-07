<?php

namespace Arkade\S3\Helper\Swatches\Media;
use Magento\Framework\App\Filesystem\DirectoryList;

class Plugin
{
     /**
     * Swatch area inside media folder
     *
     */
    const  SWATCH_MEDIA_PATH = 'attribute/swatch';


    /**
     * @var \Magento\Catalog\Model\Product\Media\Config
     */
    protected $mediaConfig;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $mediaDirectory;

    /**
     * Core file storage database
     *
     * @var \Magento\MediaStorage\Helper\File\Storage\Database
     */
    protected $fileStorageDb = null;

    public function __construct(
        \Magento\Catalog\Model\Product\Media\Config $mediaConfig,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDb
    ) {
        $this->mediaConfig = $mediaConfig;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->fileStorageDb = $fileStorageDb;
    }
    

    public function aroundMoveImageFromTmp($subject, $proceed, $file)
    {
        if (strrpos($file, '.tmp') == strlen($file) - 4) {
            $file = substr($file, 0, strlen($file) - 4);
        }
        $destinationFile = $this->getUniqueFileName($file);

        /** @var $storageHelper \Magento\MediaStorage\Helper\File\Storage\Database */
        $storageHelper = $this->fileStorageDb;

        if ($storageHelper->checkDbUsage()) {
            $storageHelper->renameFile(
                $this->mediaConfig->getTmpMediaShortUrl($file),
                $this->getAttributeSwatchPath($destinationFile)
            );

            //Need to also save to file system otherwise the generateSwatchVariations function will fail in swatch module
            $storageHelper->saveFileToFileSystem(
                $this->getAttributeSwatchPath($destinationFile),
                true
            );

            $this->mediaDirectory->delete($this->mediaConfig->getTmpMediaPath($file));
        } else {
            $this->mediaDirectory->renameFile(
                $this->mediaConfig->getTmpMediaPath($file),
                $this->mediaConfig->getMediaPath($destinationFile)
            );
        }

        return str_replace('\\', '/', $destinationFile);
    }


    protected function getUniqueFileName($file)
    {
        if ($this->fileStorageDb->checkDbUsage()) {
            $destFile = $this->fileStorageDb->getUniqueFilename(
                $this->mediaConfig->getBaseMediaUrlAddition(),
                $file
            );
        } else {
            $destFile = dirname($file) . '/' . \Magento\MediaStorage\Model\File\Uploader::getNewFileName(
                $this->mediaDirectory->getAbsolutePath($this->getAttributeSwatchPath($file))
            );
        }

        return $destFile;
    }

     /**
     * Return example: attribute/swatch/m/a/magento.jpg
     *
     * @param string $file
     * @return string
     */
    public function getAttributeSwatchPath($file)
    {
        return $this->getSwatchMediaPath() . $this->prepareFile($file);
    }

    /**
     * Media swatch path
     *
     * @return string
     */
    public function aroundGetSwatchMediaPath()
    {
        return $this->getSwatchMediaPath();
    }

    /**
     * Media swatch path
     *
     * @return string
     */
    public function getSwatchMediaPath()
    {
        return self::SWATCH_MEDIA_PATH.'/';
    }


    /**
     * Prepare file for saving
     *
     * @param string $file
     * @return string
     */
    protected function prepareFile($file)
    {
        return ltrim(str_replace('\\', '/', $file), '/');
    }

}
