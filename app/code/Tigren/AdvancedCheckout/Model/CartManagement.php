<?php

namespace Tigren\AdvancedCheckout\Model;


use Magento\Quote\Api\CartManagementInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Quote\Model\QuoteManagement;

class CartManagement implements \Tigren\AdvancedCheckout\Api\CartManagementInterface
{
    /**
     * @var CartManagementInterface
     */
    protected $cartManagement;

    /**
     * @var Request
     */
    protected $request;


    public function __construct(
        CartManagementInterface $cartManagement,
        Request $request
    ) {
        $this->cartManagement = $cartManagement;
        $this->request = $request;
    }

    /**
     * Clear cart
     * @return bool
     */
    public function clearCartById()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cartObject = $objectManager->create('Magento\Checkout\Model\Cart')->truncate()->save();

        return true;
    }
}
