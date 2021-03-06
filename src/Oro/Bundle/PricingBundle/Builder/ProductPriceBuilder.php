<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Compiler\PriceListRuleCompiler;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\ORM\ShardQueryExecutorInterface;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Builder for product prices
 */
class ProductPriceBuilder
{
    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ShardQueryExecutorInterface
     */
    protected $shardInsertQueryExecutor;

    /**
     * @var PriceListRuleCompiler
     */
    protected $ruleCompiler;

    /**
     * @var ProductPriceRepository
     */
    protected $productPriceRepository;

    /**
     * @var PriceListTriggerHandler
     */
    protected $priceListTriggerHandler;

    /**
     * @var int|null
     */
    private $version;

    /**
     * @param ManagerRegistry $registry
     * @param ShardQueryExecutorInterface $shardInsertQueryExecutor
     * @param PriceListRuleCompiler $ruleCompiler
     * @param PriceListTriggerHandler $priceListTriggerHandler
     * @param ShardManager $shardManager
     */
    public function __construct(
        ManagerRegistry $registry,
        ShardQueryExecutorInterface $shardInsertQueryExecutor,
        PriceListRuleCompiler $ruleCompiler,
        PriceListTriggerHandler $priceListTriggerHandler,
        ShardManager $shardManager
    ) {
        $this->registry = $registry;
        $this->shardInsertQueryExecutor = $shardInsertQueryExecutor;
        $this->ruleCompiler = $ruleCompiler;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
        $this->shardManager = $shardManager;
    }

    /**
     * @param ShardQueryExecutorInterface $shardInsertQueryExecutor
     */
    public function setShardInsertQueryExecutor(ShardQueryExecutorInterface $shardInsertQueryExecutor)
    {
        $this->shardInsertQueryExecutor = $shardInsertQueryExecutor;
    }

    /**
     * @param PriceList $priceList
     * @param array|Product[] $products
     */
    public function buildByPriceList(PriceList $priceList, array $products = [])
    {
        if (!$products) {
            $this->version = time();
        }
        $this->buildByPriceListWithoutTriggers($priceList, $products);

        if ($products || count($priceList->getPriceRules()) === 0) {
            $productsBatches = [$products];
        } else {
            $productsBatches = $this->getProductPriceRepository()->getProductsByPriceListAndVersion(
                $this->shardManager,
                $priceList,
                $this->version
            );
        }

        foreach ($productsBatches as $batch) {
            $this->priceListTriggerHandler->handlePriceListTopic(
                Topics::RESOLVE_COMBINED_PRICES,
                $priceList,
                $batch
            );
        }

        $this->version = null;
    }

    /**
     * @param PriceRule $priceRule
     * @param array|Product[] $products
     */
    protected function applyRule(PriceRule $priceRule, array $products = [])
    {
        $fields = $this->ruleCompiler->getOrderedFields();
        $qb = $this->ruleCompiler->compile($priceRule, $products);
        if ($this->version) {
            $fields[] = 'version';
            $qb->addSelect((string)$qb->expr()->literal($this->version));
        }

        $this->shardInsertQueryExecutor->execute(ProductPrice::class, $fields, $qb);
    }

    /**
     * @return ProductPriceRepository
     */
    protected function getProductPriceRepository()
    {
        if (!$this->productPriceRepository) {
            $this->productPriceRepository = $this->registry
                ->getManagerForClass(ProductPrice::class)
                ->getRepository(ProductPrice::class);
        }

        return $this->productPriceRepository;
    }

    /**
     * @param PriceList $priceList
     * @return array
     */
    protected function getSortedRules(PriceList $priceList)
    {
        $rules = $priceList->getPriceRules()->toArray();
        usort(
            $rules,
            function (PriceRule $a, PriceRule $b) {
                if ($a->getPriority() === $b->getPriority()) {
                    return 0;
                }

                return $a->getPriority() < $b->getPriority() ? -1 : 1;
            }
        );

        return $rules;
    }

    /**
     * @param PriceList $priceList
     * @param array|Product[] $products
     */
    public function buildByPriceListWithoutTriggers(PriceList $priceList, array $products = [])
    {
        $this->getProductPriceRepository()->deleteGeneratedPrices($this->shardManager, $priceList, $products);
        if (count($priceList->getPriceRules()) > 0) {
            $rules = $this->getSortedRules($priceList);
            foreach ($rules as $rule) {
                $this->applyRule($rule, $products);
            }
        }
    }
}
