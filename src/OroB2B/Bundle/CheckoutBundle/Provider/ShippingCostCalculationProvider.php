<?php

namespace OroB2B\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\CheckoutBundle\Entity\BaseCheckout;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Factory\ShippingContextProviderFactory;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry;

class ShippingCostCalculationProvider
{
    /** @var ShippingMethodRegistry */
    protected $registry;

    /**
     * ShippingCostCalculationProvider constructor.
     * @param ShippingMethodRegistry $registry
     * @param ShippingContextProviderFactory $shippingContextProviderFactory
     */
    public function __construct(
        ShippingMethodRegistry $registry,
        ShippingContextProviderFactory $shippingContextProviderFactory
    ) {
        $this->registry = $registry;
        $this->shippingContextProviderFactory = $shippingContextProviderFactory;
    }

    /**
     * @param BaseCheckout $entity
     * @param ShippingRuleConfiguration $config
     * @return Price
     */
    public function calculatePrice(BaseCheckout $entity, ShippingRuleConfiguration $config)
    {
        $method = $this->registry->getShippingMethod($config->getMethod());
        $shippingContext = $this->shippingContextProviderFactory->create($entity);
        $cost = $method->calculatePrice($shippingContext, $config);
        
        return $cost;
    }
}
