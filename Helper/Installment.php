<?php
/**
 * Jefferson Porto
 *
 * Do not edit this file if you want to update this module for future new versions.
 *
 * @category  Az2009
 * @package   Az2009_Cielo
 *
 * @copyright Copyright (c) 2018 Jefferson Porto - (https://www.linkedin.com/in/jeffersonbatistaporto/)
 *
 * @author    Jefferson Porto <jefferson.b.porto@gmail.com>
 */
namespace Az2009\Cielo\Helper;

use Magento\Framework\App\Helper\Context;

class Installment extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Checkout\Model\Type\Onepage
     */
    protected $onepage;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $sessionQuote;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;

    public function __construct(
        Context $context,
        \Magento\Checkout\Model\Type\Onepage $onepage,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\App\State $state
    ) {
        parent::__construct($context);
        $this->onepage = $onepage;
        $this->checkoutSession = $checkoutSession;
        $this->sessionQuote = $sessionQuote;
        $this->storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
        $this->state = $state;
    }

    /**
     * @return mixed
     */
    public function getInstallmentsAvailable()
    {
        $quote = ($this->onepage !== false) ?
                        $this->onepage->getQuote() : $this->checkoutSession->getQuote();

        if ($this->state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
            $quote = $this->sessionQuote->getQuote();
        }

        $quote->setTotalsCollectedFlag(false)->collectTotals();
        $amount = (double) $quote->getGrandTotal();
        $maxInstallments = $this->getConfigValue($amount);
        for ($i=1; $i <= $maxInstallments; $i++) {
            $partialAmount = ((double)$amount)/$i;
            $result[(string)$i] = $i . "x " . $this->formatPrice($partialAmount, false);
        }

        return $result;
    }

    /**
     * @param $price
     * @param bool $includeContainer
     *
     * @return float
     */
    public function formatPrice($price, $includeContainer = true)
    {
        return $this->priceCurrency->format(
            $price,
            $includeContainer,
            \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->storeManager->getStore()
        );
    }

    /**
     * @return array|mixed
     */
    public function getInstallments() {
        $value = $this->scopeConfig->getValue(
                'payment/az2009_cielo/installments',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        
        $value = $this->_unserializeValue($value);
        
        return $value;
    }

    /**
     * Create a value from a storable representation
     *
     * @param mixed $value
     *
     * @return array
     */
    protected function _unserializeValue($value)
    {
        if (is_string($value) && !empty($value)) {
            return json_decode($value, true);
        } else {
            return array();
        }
    }

    /**
     * Retrieve maximum number for installments for given amount with config
     *
     * @param int $customerGroupId
     * @param mixed $store
     *
     * @return float|null
     */
    public function getConfigValue($amount)
    {
        $value = $this->getInstallments();
        if ($this->_isEncodedArrayFieldValue($value)) {
            $value = $this->_decodeArrayFieldValue($value);
        }

        $curMinimalBoundary = -1;
        $resultingFreq = 1;
        foreach ($value as $row) {
            list($boundary, $frequency) = $row;
            if ($amount <= $boundary && ($boundary <= $curMinimalBoundary || $curMinimalBoundary == -1)) {
                $curMinimalBoundary = $boundary;
                $resultingFreq = $frequency;
            }

            if ($boundary == "" && $curMinimalBoundary == -1) {
                $resultingFreq = $frequency;
            }
        }

        return $resultingFreq;
    }

    /**
     * Check whether value is in form retrieved by _encodeArrayFieldValue()
     *
     * @param mixed
     *
     * @return bool
     */
    protected function _isEncodedArrayFieldValue($value)
    {
        if (!is_array($value)) {
            return false;
        }

        unset($value['__empty']);
        foreach ($value as $_id => $row) {
            if (
                !is_array($row)
                || !array_key_exists('installment_boundary', $row)
                || !array_key_exists('installment_frequency', $row )
            ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Decode value from used in \Magento\Config\Model\Config\Backend\Serialized\ArraySerialized
     * HTML form => deserialized DB entry
     *
     * @param array
     *
     * @return array
     */
    protected function _decodeArrayFieldValue(array $value)
    {
        $result = array();
        unset($value['__empty']);
        foreach ($value as $_id => $row) {

            if (
                !is_array($row)
                || !array_key_exists('installment_boundary', $row)
                || !array_key_exists('installment_frequency', $row)
            ) {
                continue;
            }

            $boundary = $row['installment_boundary'];
            $frequency = $row['installment_frequency'];
            $result[] = array($boundary, $frequency);
        }

        return $result;
    }
}