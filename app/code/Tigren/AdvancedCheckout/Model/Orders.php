<?php

namespace Tigren\AdvancedCheckout\Model;

use Tigren\AdvancedCheckout\Api\OrdersInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection;

class Orders implements OrdersInterface
{
    private $orderCollectionFactory;

    /**
     * Orders constructor.
     *
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Collection $orderCollection,
    ) {
        $this->orderCollection = $orderCollection;
    }

    /**
     * Check if customer has any pending orders
     *
     * @param int $customerId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function hasPendingOrders($customerId)
    {
        $customerOrders = $this->orderCollection
            ->addAttributeToFilter('customer_id', $customerId)->load()->getData();

        $orders = array();
        foreach ($customerOrders as $customerOrder) {
            $orders[] = $customerOrder['status'];
        }

        foreach ($orders as $order) {
            if ($order != "complete") {
                return true;
            }
        }

        return false;
    }
}
