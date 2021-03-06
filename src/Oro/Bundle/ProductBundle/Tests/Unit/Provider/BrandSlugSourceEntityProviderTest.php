<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Entity\Repository\BrandRepository;
use Oro\Bundle\ProductBundle\Provider\BrandSlugSourceEntityProvider;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\Slug;

class BrandSlugSourceEntityProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var BrandSlugSourceEntityProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->provider = new BrandSlugSourceEntityProvider($this->registry, $this->configManager);
    }

    public function testGetSourceEntityBySlugWhenConfigIsOff()
    {
        $slug = new Slug();
        $this->registry->expects($this->never())
            ->method('getManagerForClass');
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::ENABLE_DIRECT_URL))
            ->willReturn(false);
        $this->assertNull($this->provider->getSourceEntityBySlug($slug));
    }

    public function testGetSourceEntityBySlug()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::ENABLE_DIRECT_URL))
            ->willReturn(true);
        $slug = new Slug();
        $sourceEntity = new Brand();
        $repository = $this->createMock(BrandRepository::class);
        $repository->expects($this->once())
            ->method('findOneBySlug')
            ->with($slug)
            ->willReturn($sourceEntity);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(Brand::class)
            ->willReturn($repository);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Brand::class)
            ->willReturn($manager);
        $this->assertSame($sourceEntity, $this->provider->getSourceEntityBySlug($slug));
    }
}
