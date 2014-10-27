<?php

namespace PHPGit;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Git config
 *
 * @author Moritz Schwörer <mr.mosch@gmail.com>
 */
class Config
{
    private $config = array();

    /**
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        $this->config = $configuration;
    }

    /**
     * Sets the prefix of ProcessBuilder to the configuration
     *
     * @param ProcessBuilder $builder
     */
    public function configureProcess(ProcessBuilder $builder)
    {
        $config = $this->getResolvedOptions();
        list($config, $binary) = $this->extractBinaray($config);
        $prefix = array($binary);

        foreach($config as $option => $value) {
            $prefix[] = "-c";
            $prefix[] = "$option=$value";
        }
        $builder->setPrefix($prefix);
    }

    /**
     * @param array $config
     * @return array ($config, BINARY)
     */
    private function extractBinaray(array $config)
    {
        $binary = $config['binary'];
        unset($config['binary']);
        return array($config, $binary);
    }

    /**
     * @return array
     */
    private function getResolvedOptions()
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setDefaults(array('binary' => 'git'));
        $optionsResolver->setOptional(array(
                'user.email',
                'user.name'
            ));

        $resolvedConfiguration = $optionsResolver->resolve($this->config);

        return $resolvedConfiguration;
    }

} 