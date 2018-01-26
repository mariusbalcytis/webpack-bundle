<?php

namespace Maba\Bundle\WebpackBundle\Config;

class WebpackConfigDumper
{
    private $path;
    private $tsPath;
    private $includeConfigPath;
    private $manifestPath;
    private $environment;
    private $parameters;
    private $typescript;

    /**
     * @param string $path full path where config should be dumped
     * @param string $tsPath full path where config should be dumped in typescript
     * @param string $includeConfigPath path of config to be included inside dumped config
     * @param string $manifestPath
     * @param string $environment
     * @param array $parameters
     * @param bool $typescript is config in typescript
     */
    public function __construct($path, $tsPath, $includeConfigPath, $manifestPath, $environment, array $parameters, $typescript)
    {
        $this->path = $path;
        $this->tsPath = $tsPath;
        $this->includeConfigPath = $includeConfigPath;
        $this->manifestPath = $manifestPath;
        $this->environment = $environment;
        $this->parameters = $parameters;
        $this->typescript = $typescript;
    }

    /**
     * @param WebpackConfig $config
     * @return string
     */
    public function dump(WebpackConfig $config)
    {
        $configTemplate = 'module.exports = require(%s)(%s);';
        $configTemplateTS = 'export default require(%s)(%s);';
        $configContents = sprintf(
            $this->typescript ? $configTemplateTS : $configTemplate,
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

        file_put_contents($this->typescript ? $this->tsPath : $this->path, $configContents);

        return $this->path;
    }
}
