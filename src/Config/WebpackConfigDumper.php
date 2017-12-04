<?php

namespace Maba\Bundle\WebpackBundle\Config;

class WebpackConfigDumper
{
    private $path;
    private $includeConfigPath;
    private $manifestPath;
    private $environment;
    private $parameters;

    /**
     * @param string $path full path where config should be dumped
     * @param string $includeConfigPath path of config to be included inside dumped config
     * @param string $manifestPath
     * @param string $environment
     * @param array $parameters
     */
    public function __construct($path, $includeConfigPath, $manifestPath, $environment, array $parameters)
    {
        $this->path = $path;
        $this->includeConfigPath = $includeConfigPath;
        $this->manifestPath = $manifestPath;
        $this->environment = $environment;
        $this->parameters = $parameters;
    }

    /**
     * @param WebpackConfig $config
     * @return string
     */
    public function dump(WebpackConfig $config)
    {
        $configTemplate = 'module.exports = require(%s)(%s);';
        $configContents = sprintf(
            $configTemplate,
            json_encode($this->includeConfigPath),
            json_encode([
                'entry' => (object)$config->getEntryPoints(),
                'groups' => (object)$config->getAssetGroups(),
                'alias' => (object)$config->getAliases(),
                'manifest_path' => $this->manifestPath,
                'environment' => $this->environment,
                'parameters' => (object)$this->parameters,
            ])
        );

        file_put_contents($this->path, $configContents);

        return $this->path;
    }
}
