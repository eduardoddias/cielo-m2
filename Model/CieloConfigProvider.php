<?php

namespace Az2009\Cielo\Model;

use Magento\Framework\View\Asset\Source;

class CieloConfigProvider
    extends \Magento\Payment\Model\CcConfigProvider
        implements \Magento\Checkout\Model\ConfigProviderInterface
{

    public function __construct(
        CcConfig $ccConfig,
        Source $assetSource,
        \Az2009\Cielo\Helper\Data $helper
    ) {
        $this->helper = $helper;
        $this->ccConfig = $ccConfig;
        $this->assetSource = $assetSource;
    }

    /**
     * @var array
     */
    private $icons = [];

    public function getConfig()
    {
        return [
            'payment' => [
                'az2009_cielo' => [
                    'icons' => $this->getIcons(),
                    'availableTypes' => $this->getCcAvailableTypes()
                ]
            ]
        ];
    }

    public function getCcAvailableTypes()
    {
        $types = $this->helper->getCardTypesAvailable();
        return $types;
    }

    /**
     * Get icons for available payment methods
     *
     * @return array
     */
    public function getIcons()
    {
        if (!empty($this->icons)) {
            return $this->icons;
        }

        $types = $this->ccConfig->getCcAvailableTypes();
        foreach (array_keys($types) as $code) {
            if (!array_key_exists($code, $this->icons)) {
                $asset = $this->ccConfig->createAsset('Az2009_Cielo::images/cc/' . strtolower($code) . '.png');
                $placeholder = $this->assetSource->findSource($asset);
                if ($placeholder) {
                    list($width, $height) = getimagesize($asset->getSourceFile());
                    $this->icons[$code] = [
                        'url' => $asset->getUrl(),
                        'width' => $width,
                        'height' => $height
                    ];
                }
            }
        }

        return $this->icons;
    }

    public function isValidDoc()
    {

    }

    public function getImgURLBrandCard()
    {

    }

    public function isAvailable()
    {

    }

    public function isValid()
    {

    }
}