<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2023 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\AdvancedCheckout\Model;

use Tigren\AdvancedCheckout\Api\LoginInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;

class Login implements LoginInterface
{
    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * Login constructor.
     * @param CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        CustomerFactory $customerFactory,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->customerFactory = $customerFactory;
        $this->customerSession = $customerSession;
    }


    public function login($username, $password)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $accountManagementInterface = $objectManager->get('Magento\Customer\Api\AccountManagementInterface');
        try {
            $customer = $accountManagementInterface->authenticate($username, $password);
            $this->customerSession->setCustomerDataAsLoggedIn($customer);
            $this->customerSession->regenerateId();
        } catch (\Exception $e) {
            throw new AuthenticationException(__('Invalid login or password.'));
        }

        $customerData = [
            'id' => $this->customerSession->getCustomerId(),
            'firstname' => $this->customerSession->getCustomer()->getFirstname(),
            'lastname' => $this->customerSession->getCustomer()->getLastname(),
            'email' => $this->customerSession->getCustomer()->getEmail(),
        ];

        return json_encode($customerData);
    }
}

