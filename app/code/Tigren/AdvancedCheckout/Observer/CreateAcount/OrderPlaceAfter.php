<?php

namespace Tigren\AdvancedCheckout\Observer\CreateAcount;

use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class OrderPlaceAfter implements ObserverInterface
{
    protected $customerFactory;

    /**
     * @var AddressFactory
     */
    protected $addressFactory;

    public function __construct(
        CustomerFactory $customerFactory,
        AddressFactory $addressFactory,
    ) {
        $this->customerFactory = $customerFactory;
        $this->addressFactory = $addressFactory;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        if ($order->getCustomerIsGuest()) {

            $shippingAddress = $order->getShippingAddress();

            $password = bin2hex(random_bytes(8));

            $customer = $this->customerFactory->create();
            $customer->setWebsiteId(1);
            $customer->setEmail($shippingAddress->getEmail());
            $customer->setFirstname($shippingAddress->getFirstname());
            $customer->setLastname($shippingAddress->getLastname());
            $customer->setPassword($password);
            $customer->save();

            $address = $this->addressFactory->create();
            $address->setCustomerId($customer->getId());
            $address->setFirstname($shippingAddress->getFirstname());
            $address->setLastname($shippingAddress->getLastname());
            $address->setTelephone($shippingAddress->getTelephone());
            $address->setCountryId($shippingAddress->getCountryId());
            $address->setPostcode($shippingAddress->getPostcode());
            $address->setCity($shippingAddress->getCity());
            $address->setStreet($shippingAddress->getStreet());
            $address->setIsDefaultBilling(true);
            $address->setIsDefaultShipping(true);
            $address->save();

            $order->setCustomerId($customer->getId());
            $order->save();
        }
    }
}

