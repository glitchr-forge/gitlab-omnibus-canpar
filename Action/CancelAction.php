<?php

namespace Omnibus\Canpar\Action;

use Omnibus\Action\ActionInterface;
use Omnibus\Action\ApiAwareInterface;
use Omnibus\Action\ApiAwareTrait;
use Omnibus\Canpar\Api;
use Omnibus\Request\Cancel;
use Omnibus\Request\Request;

/** voidShipment: Canpar takes the shipment id, which the label's tracking number maps back to through the option kept at shipping (shipment id = the barcode's shipment). */
final class CancelAction implements ActionInterface, ApiAwareInterface
{
    /** @use ApiAwareTrait<Api> */
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function supports(Request $request): bool
    {
        return $request instanceof Cancel;
    }

    public function execute(Request $request): void
    {
        \assert($request instanceof Cancel);
        $tracked = $this->api->call(Api::TRACKING, 'trackByBarcode', '<xsd:barcode>'.Api::e($request->trackingNumber).'</xsd:barcode><xsd:track_shipment>true</xsd:track_shipment>');
        $id = (string) ($tracked->result->shipment_id ?? $tracked->result->id ?? '');
        if ('' === $id) {
            $request->setResult(false);

            return;
        }
        $this->api->call(Api::SHIPPING, 'voidShipment', '<xsd:id>'.Api::e($id).'</xsd:id>');
        $request->setResult(true);
    }
}
