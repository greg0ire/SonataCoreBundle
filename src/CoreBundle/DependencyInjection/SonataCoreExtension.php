<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\DependencyInjection;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use Sonata\Serializer\BaseSerializerHandler;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
final class SonataCoreExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig('sonata_admin');

        foreach ($configs as $config) {
            if (isset($config['options']['form_type'])) {
                $container->prependExtensionConfig(
                    $this->getAlias(),
                    ['form_type' => $config['options']['form_type']]
                );
            }
        }
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);
        $bundles = $container->getParameter('kernel.bundles');

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('date.xml');
        $loader->load('flash.xml');
        $loader->load('form_types.xml');
        $loader->load('validator.xml');
        $loader->load('twig.xml');

        $this->registerFlashTypes($container, $config);
        $container->setParameter('sonata.form.form_type', $config['form_type']);

        if (isset($bundles['JMSSerializerBundle'])) {
            $this->configureSerializerFormats($config);
        }
    }

    /**
     * Registers flash message types defined in configuration to flash manager.
     */
    public function registerFlashTypes(ContainerBuilder $container, array $config): void
    {
        $mergedConfig = array_merge_recursive($config['flashmessage'], [
            'success' => ['types' => [
                'success' => [],
                'sonata_flash_success' => [],
                'sonata_user_success' => [],
                'fos_user_success' => [],
            ]],
            'warning' => ['types' => [
                'warning' => [],
                'sonata_flash_info' => [],
            ]],
            'danger' => ['types' => [
                'error' => [],
                'sonata_flash_error' => [],
                'sonata_user_error' => [],
            ]],
        ]);

        $types = $cssClasses = [];

        foreach ($mergedConfig as $typeKey => $typeConfig) {
            $types[$typeKey] = $typeConfig['types'];
            $cssClasses[$typeKey] = \array_key_exists('css_class', $typeConfig) ? $typeConfig['css_class'] : $typeKey;
        }

        $identifier = 'sonata.twig.flashmessage.manager';

        $definition = $container->getDefinition($identifier);
        $definition->replaceArgument(2, $types);
        $definition->replaceArgument(3, $cssClasses);

        $container->setDefinition($identifier, $definition);
    }

    public function configureSerializerFormats(array $config): void
    {
        if (interface_exists(SubscribingHandlerInterface::class)) {
            BaseSerializerHandler::setFormats($config['serializer']['formats']);
        }
    }
}
