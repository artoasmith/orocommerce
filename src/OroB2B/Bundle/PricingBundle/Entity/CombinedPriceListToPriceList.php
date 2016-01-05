<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orob2b_cmb_pl_to_pl")
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository")
 */
class CombinedPriceListToPriceList
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var CombinedPriceList
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList")
     * @ORM\JoinColumn(name="combined_price_list_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $combinedPriceList;

    /**
     * @var PriceList
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\PricingBundle\Entity\PriceList")
     * @ORM\JoinColumn(name="price_list_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $priceList;

    /**
     * @var int order ASC
     *
     * @ORM\Column(name="sort_order", type="integer", nullable=false)
     */
    protected $sortOrder;

    /**
     * @var bool
     *
     * @ORM\Column(name="merge_allowed", type="boolean", nullable=false)
     */
    protected $mergeAllowed;

    /**
     * @return CombinedPriceList
     */
    public function getCombinedPriceList()
    {
        return $this->combinedPriceList;
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @return CombinedPriceListToPriceList
     */
    public function setCombinedPriceList(CombinedPriceList $combinedPriceList)
    {
        $this->combinedPriceList = $combinedPriceList;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isMergeAllowed()
    {
        return $this->mergeAllowed;
    }

    /**
     * @param boolean $mergeAllowed
     * @return CombinedPriceListToPriceList
     */
    public function setMergeAllowed($mergeAllowed)
    {
        $this->mergeAllowed = $mergeAllowed;

        return $this;
    }

    /**
     * @return PriceList
     */
    public function getPriceList()
    {
        return $this->priceList;
    }

    /**
     * @param PriceList $priceList
     * @return CombinedPriceListToPriceList
     */
    public function setPriceList(PriceList $priceList)
    {
        $this->priceList = $priceList;

        return $this;
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @param int $sortOrder
     * @return CombinedPriceListToPriceList
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }
}
