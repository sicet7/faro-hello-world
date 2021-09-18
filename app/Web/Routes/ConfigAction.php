<?php


namespace App\Web\Routes;

use Psr\Http\Message\ResponseInterface;
use Sicet7\Faro\Config\Config;
use Sicet7\Faro\Slim\Attributes\Routing\Get;

#[Get('/config')]
class ConfigAction
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * Config constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function __invoke(ResponseInterface $response): ResponseInterface
    {
        $response->getBody()->write(var_export($this->config->getConfig(), true));
        return $response;
    }
}
