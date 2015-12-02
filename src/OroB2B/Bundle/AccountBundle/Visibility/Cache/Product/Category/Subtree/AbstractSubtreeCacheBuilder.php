<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Visibility\Resolver\CategoryVisibilityResolverInterface;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

abstract class AbstractSubtreeCacheBuilder
{
    /**
     * @param Registry $registry
     * @param CategoryVisibilityResolverInterface $categoryVisibilityResolver
     */
    public function __construct(Registry $registry, CategoryVisibilityResolverInterface $categoryVisibilityResolver)
    {
        $this->registry = $registry;
        $this->categoryVisibilityResolver = $categoryVisibilityResolver;
    }

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    abstract protected function restrictStaticFallback(QueryBuilder $qb);

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    abstract protected function restrictToParentFallback(QueryBuilder $qb);

    /**
     * @param QueryBuilder $qb
     * @param object|null $target
     * @return QueryBuilder
     */
    abstract protected function joinCategoryVisibility(QueryBuilder $qb, $target);

    /**
     * @param bool $visibility
     * @return int
     */
    protected function convertVisibility($visibility)
    {
        return $visibility
            ? BaseProductVisibilityResolved::VISIBILITY_VISIBLE
            : BaseProductVisibilityResolved::VISIBILITY_HIDDEN;
    }

    /**
     * @param Category $category
     * @param $target
     * @return array
     */
    protected function getCategoryIdsForUpdate(Category $category, $target)
    {
        $categoriesWithStaticFallback = $this->getChildCategoriesWithFallbackStatic($category, $target);
        $childCategories = $this->getChildCategoriesWithFallbackToParent(
            $category,
            $categoriesWithStaticFallback,
            $target
        );

        $categoryIds = array_map(
            function ($category) {
                return $category['id'];
            },
            $childCategories
        );

        $categoryIds[] = $category->getId();

        return $categoryIds;
    }

    /**
     * @param Category $category
     * @param object|null $target
     * @return array
     */
    protected function getChildCategoriesWithFallbackStatic(Category $category, $target)
    {
        $qb = $this->registry
            ->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->getChildrenQueryBuilder($category, false, 'level');

        $qb = $this->joinCategoryVisibility($qb, $target);
        $qb = $this->restrictStaticFallback($qb);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param Category $category
     * @param array $categoriesWithStaticFallback
     * @param $target
     * @return array
     */
    protected function getChildCategoriesWithFallbackToParent(
        Category $category,
        array $categoriesWithStaticFallback,
        $target
    ) {
        $qb = $this->registry
            ->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->getChildrenQueryBuilder($category, false, 'level');

        $qb = $this->joinCategoryVisibility($qb, $target);
        $qb = $this->restrictToParentFallback($qb);

        foreach ($categoriesWithStaticFallback as $node) {
            $qb->andWhere(
                $qb->expr()->not(
                    $qb->expr()->andX(
                        $qb->expr()->gt('node.level', $node['level']),
                        $qb->expr()->gt('node.left', $node['left']),
                        $qb->expr()->lt('node.right', $node['right'])
                    )
                )
            );
        }

        return $qb->getQuery()->getArrayResult();
    }
}
