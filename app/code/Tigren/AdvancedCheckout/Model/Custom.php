<?php

namespace Tigren\AdvancedCheckout\Model;

use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\QuoteFactory;
use function PHPUnit\Framework\isEmpty;


class Custom implements \Tigren\AdvancedCheckout\Api\CustomInterface
{
    protected $logger;
    protected $productRepository;
    protected $searchCriteriaBuilder;
    private $checkoutSession;
    protected $quoteFactory;

    public function __construct(
        LoggerInterface $logger,
        ProductRepository $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        QuoteFactory $quoteFactory,
        CheckoutSession $checkoutSession
    ) {
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->quoteFactory = $quoteFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @inheritdoc
     */

    public function getCustomAttributeByProductId($productId, $attributeCode)
    {
        $response = ['success' => false, 'message' => 'false'];
        $quoteId = $this->checkoutSession->getQuote()->getId();
        $quote = \Magento\Framework\App\ObjectManager::getInstance()
            ->create(\Magento\Quote\Model\Quote::class)
            ->load($quoteId);


        $items = $quote->getAllItems();
        $cartProductIds = array();
        $cartProductQtys = array();

        // Lặp qua từng sản phẩm và lấy ID sản phẩm
        foreach ($items as $item) {
            $cartProductIds[] = $item->getProductId();
            $cartProductQtys[$item->getProductId()] = $item->getQty();
        }


        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $productId)
            ->create();

        $product = $this->productRepository->getList($searchCriteria)->getItems();
        if (!empty($product)) {
            $product = reset($product);
            $customAttributeValue = $product->getData($attributeCode);
            $flag = false;
            if ($cartProductQtys[$productId] > 1 && $customAttributeValue == 0 && $customAttributeValue != null) {
                $flag = true;
            }

            if ($flag) {
                $response = ['success' => true, 'message' => 'success'];
            }

        }

        return json_encode($response);
    }
}
