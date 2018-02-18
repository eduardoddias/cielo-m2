<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Az2009\Cielo\Model\Method\Cc\Request;

class Payment extends \Magento\Framework\DataObject
{

    const TYPE = 'CreditCard';
    const INTEREST = 'ByMerchant';
    const SAVE_CARD = 'true';

    protected $_cctype;

    public function __construct(\Az2009\Cielo\Model\Source\Cctype $cctype, array $data = [])
    {
        $this->_cctype = $cctype;
        parent::__construct($data);
    }

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $order;

    public function getRequest()
    {
        $this->order = $this->getOrder();
        $payment = $this->order
                        ->getPayment()
                        ->getMethodInstance();

        $info = $payment->getInfoInstance();

        $this->setInfo($info);
        $this->setPayment($payment);

        return $this->setData(
                        [
                           'Payment' => [
                               'Type' => Payment::TYPE,
                               'Amount' => $this->order->getGrandTotal(),
                               'ServiceTaxAmount' => 0,
                               'Installments' => $this->getInstallments(),
                               'Interest' => Payment::INTEREST,
                               'Capture' => $info->getAdditionalInformation('can_capture'),
                               'Authenticate' => false,
                               'SoftDescriptor' => $this->getSoftDescriptor(),
                               'CreditCard' => $this->getCreditCard(),
                           ]
                        ]
                      )->toArray();


    }

    /**
     * @return bool|string
     */
    public function getSoftDescriptor()
    {
        $desc = $this->getPayment()
                     ->getConfigData(
                         'billing_description',
                         $this->order->getStoreId()
                     );

        return substr($desc, 0, 13);
    }

    /**
     * prepare installments
     * @return int
     */
    public function getInstallments()
    {
        $installments = $this->getInfo()->getAdditionalInformation('installments');
        if ((int)$installments <= 0) {
            $installments = 1;
        }

        return $installments;
    }

    /**
     * mock data credit card
     * @return array
     */
    public function getCreditCard()
    {
        return [
            'CardNumber' => $this->getInfo()->getAdditionalInformation('cc_number_enc'),
            'Holder' => $this->getInfo()->getAdditionalInformation('cc_owner'),
            'ExpirationDate' => $this->getExpDate(),
            'SecurityCode' => $this->getInfo()->getAdditionalInformation('cc_cid_enc'),
            'SaveCard' => (boolean)$this->getPayment()->getConfigData('can_save_cc', $this->order->getStoreId()),
            'Brand' => $this->_cctype->getBrandFormatCielo($this->getInfo()->getAdditionalInformation('cc_type'))
        ];
    }

    /**
     * mock data date due of card
     * @return string
     */
    public function getExpDate()
    {
        $date = [
            $this->getInfo()->getAdditionalInformation('cc_exp_month'),
            $this->getInfo()->getAdditionalInformation('cc_exp_year')
        ];

        return implode('/', $date);
    }

}