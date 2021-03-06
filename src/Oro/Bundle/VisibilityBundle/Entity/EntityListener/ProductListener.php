<?php

namespace Oro\Bundle\VisibilityBundle\Entity\EntityListener;

use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Sends MQ message to change product category when a product is created.
 */
class ProductListener implements OptionalListenerInterface
{
    /** @var MessageProducerInterface */
    private $messageProducer;

    /** @var bool */
    private $enabled = true;

    /**
     * @param MessageProducerInterface $messageProducer
     */
    public function __construct(MessageProducerInterface $messageProducer)
    {
        $this->messageProducer = $messageProducer;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * @param Product $product
     */
    public function postPersist(Product $product): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->messageProducer->send(Topics::CHANGE_PRODUCT_CATEGORY, ['id' => $product->getId()]);
    }
}
