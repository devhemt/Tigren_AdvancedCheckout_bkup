<?php

namespace Tigren\AdvancedCheckout\Observer\Sales;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;

class OrderPlaceBefore implements ObserverInterface
{
    private $orderCollectionFactory;

    /**
     * Orders constructor.
     *
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     */

    /**
     * @var PhpCookieManager
     */
    private PhpCookieManager $cookieManager;
    /**
     * @var CookieMetadataFactory
     */
    private CookieMetadataFactory $cookieMetadataFactory;

    public function __construct(
        Collection $orderCollection,
        CookieMetadataFactory $cookieMetadataFactory,
        PhpCookieManager $cookieManager,
    ) {
        $this->orderCollection = $orderCollection;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->cookieManager = $cookieManager;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $customerEmail = $order->getCustomerEmail();

        $customerOrders = $this->orderCollection
            ->addAttributeToFilter('customer_email', $customerEmail)->load()->getData();

        $orders = array();
        foreach ($customerOrders as $customerOrder) {
            $orders[] = $customerOrder['status'];
        }

        foreach ($orders as $order) {
            if ($order != "complete") {
                $cookieMetadata = $this->cookieMetadataFactory
                    ->createPublicCookieMetadata()
                    ->setPath('/')
                    ->setHttpOnly(false)
                    ->setDurationOneYear();
                $this->cookieManager->setPublicCookie('redirect_triggered', '1', $cookieMetadata);
                throw new LocalizedException(__('This email address is not allowed to place an order.'));
            }
        }

    }
}
