<?php

namespace Oro\Bundle\WebCatalogBundle\ContentNodeUtils;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

/**
 * Returns resolved content node tree
 */
interface ContentNodeTreeResolverInterface
{
    /**
     * @param ContentNode $node
     * @param Scope       $scope
     *
     * @return ResolvedContentNode|null
     */
    public function getResolvedContentNode(ContentNode $node, Scope $scope): ?ResolvedContentNode;
}
