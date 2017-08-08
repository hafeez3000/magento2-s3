<?php
namespace Arkade\S3\Model\Catalog\Category;

use Magento\Catalog\Model\Category;
class Plugin
{


     /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;



     /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_storeManager = $storeManager;
    }


  /**
     * Retrieve image URL
     *
     * @return string
     */
    public function aroundGetImageUrl(Category $subject, $proceed)
    {
        $url = false;
        $image = $subject->getImage();
        if ($image) {
            if (is_string($image)) {
                $url = $this->_storeManager->getStore()->getBaseUrl(
                    \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . 'catalog/category' . $image;
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Something went wrong while getting the image url.')
                );
            }
        }
        return $url;
    }
}