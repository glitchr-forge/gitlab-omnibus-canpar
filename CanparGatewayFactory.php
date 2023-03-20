<?php

namespace Omnibus\Canpar;

use Omnibus\Core\GatewayFactory;

class CanparGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'omnibus.factory_name' => 'canpar',
            'omnibus.factory_title' => 'Canpar'
        ]);
    }
}