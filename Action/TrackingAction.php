<?php

namespace Omnibus\Canpar\Action;

use Omnibus\Action\ActionInterface;
use Omnibus\Action\ApiAwareInterface;
use Omnibus\Action\ApiAwareTrait;
use Omnibus\Canpar\Api;
use Omnibus\Canpar\Mapping;
use Omnibus\Model\Tracking as TrackingModel;
use Omnibus\Model\TrackingEvent;
use Omnibus\Model\TrackingStatus;
use Omnibus\Request\Request;
use Omnibus\Request\Tracking;

/** trackByBarcode: the events, oldest first. */
final class TrackingAction implements ActionInterface, ApiAwareInterface
{
    /** @use ApiAwareTrait<Api> */
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function supports(Request $request): bool
    {
        return $request instanceof Tracking;
    }

    public function execute(Request $request): void
    {
        \assert($request instanceof Tracking);
        $data = $this->api->call(Api::TRACKING, 'trackByBarcode', '<xsd:barcode>'.Api::e($request->trackingNumber).'</xsd:barcode><xsd:track_shipment>true</xsd:track_shipment>');
        $events = [];
        foreach ($data->result->events ?? [] as $event) {
            $events[] = new TrackingEvent(new \DateTimeImmutable((string) $event->local_date_time), Mapping::status((string) $event->code, (string) $event->code_description_en), (string) ($event->code_description_en ?: $event->code_description_fr), trim((string) $event->address->city.' '.(string) $event->address->province) ?: null, (string) $event->code ?: null);
        }
        usort($events, static fn (TrackingEvent $a, TrackingEvent $b) => $a->at <=> $b->at);
        $status = $events ? $events[array_key_last($events)]->status : TrackingStatus::UNKNOWN;
        if (!empty($data->result->delivered_date_time)) {
            $status = TrackingStatus::DELIVERED;
        }
        $request->setResult(new TrackingModel('canpar', $request->trackingNumber, $status, $events));
    }
}
