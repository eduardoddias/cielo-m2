<?php

namespace Az2009\Cielo\Model\Method;

abstract class Transaction extends \Az2009\Cielo\Model\Method\Response
{
    /**
     * @var array
     */
    protected $_transactionData = array();

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_session;

    public function __construct(
        \Magento\Customer\Model\Session $session,
        \Magento\Sales\Model\Order\Email\Sender\OrderCommentSender $comment,
        array $data = []
    ) {
        $this->_session = $session;
        parent::__construct($data);
    }

    /**
     * set body response to transaction of payment
     * @param array $data
     */
    public function prepareBodyTransaction(Array $data, $key = '')
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $this->prepareBodyTransaction($v, $k);
            } else {
                if (is_bool($v)) {
                    if ($v) {
                        $v = 'true';
                    } else {
                        $v = 'false';
                    }
                }

                $this->_transactionData[$key."_".$k] = $v;
            }
        }
    }

    /**
     * get instance payment
     * @return \Magento\Sales\Model\Order\Payment
     * @throws \Az2009\Cielo\Exception\Cc
     */
    public function getPayment()
    {
        $payment = $this->getData('payment');
        if (!($payment instanceof \Magento\Payment\Model\InfoInterface)) {
            throw new \Az2009\Cielo\Exception\Cc(
                __('Occurred an error during payment process. Try Again.')
            );
        }

        return $payment;
    }

    public function saveCardToken()
    {
        $bodyArray = $this->getBody(\Zend\Json\Json::TYPE_ARRAY);

        if ($this->_session->isLoggedIn()
            && isset($bodyArray['Payment']['CreditCard']['CardToken'])
            && isset($bodyArray['Payment']['CreditCard']['SaveCard'])
            && $bodyArray['Payment']['CreditCard']['SaveCard']
            && $cardToken = $bodyArray['Payment']['CreditCard']['CardToken']
        ) {
            $this->getPayment()
                 ->setCardToken($cardToken);
        }
    }

    /**
     * @return array
     */
    public function getTransactionData()
    {
        return $this->_transactionData;
    }

}