<?php

namespace Omnibus\Canpar;

use Omnibus\Model\Address;
use Omnibus\Model\Parcel;
use Omnibus\Model\Shipment;
use Omnibus\Model\TrackingStatus;

/** Canpar's XML for ours. Services: 1 Ground, 2 Select, 3 Overnight, 4 USA, 5 Select USA, 6 International. */
final class Mapping
{
    public const SERVICES = ['1' => 'Canpar Ground', '2' => 'Select', '3' => 'Overnight', '4' => 'USA', '5' => 'Select USA', '6' => 'International', '7' => 'Select Letter', '8' => 'Overnight Letter', '9' => 'USA Letter'];

    public static function address(string $tag, Address $a, ?string $province): string
    {
        return '<xsd:'.$tag.'><xsd:name>'.Api::e(mb_substr($a->company ?? $a->name, 0, 40)).'</xsd:name><xsd:attention>'.Api::e(mb_substr($a->name, 0, 40)).'</xsd:attention>'
            .'<xsd:address_line_1>'.Api::e(mb_substr($a->line(0), 0, 40)).'</xsd:address_line_1><xsd:address_line_2>'.Api::e(mb_substr($a->line(1), 0, 40)).'</xsd:address_line_2>'
            .'<xsd:city>'.Api::e($a->city).'</xsd:city><xsd:province>'.Api::e((string) $province).'</xsd:province><xsd:country>'.strtoupper($a->country).'</xsd:country><xsd:postal_code>'.Api::e(strtoupper(str_replace(' ', '', $a->postcode))).'</xsd:postal_code>'
            .'<xsd:phone>'.Api::e(preg_replace('/\D+/', '', (string) $a->phone) ?: '').'</xsd:phone><xsd:email>'.Api::e((string) $a->email).'</xsd:email></xsd:'.$tag.'>';
    }

    public static function package(Parcel $p): string
    {
        return '<xsd:packages><xsd:reported_weight>'.number_format(max(0.1, $p->weight / 1000), 2, '.', '').'</xsd:reported_weight>'
            .($p->length && $p->width && $p->height ? '<xsd:length>'.$p->length.'</xsd:length><xsd:width>'.$p->width.'</xsd:width><xsd:height>'.$p->height.'</xsd:height>' : '')
            .($p->value ? '<xsd:declared_value>'.number_format($p->value / 100, 2, '.', '').'</xsd:declared_value>' : '')
            .'</xsd:packages>';
    }

    public static function shipment(Shipment $s, string $shipperNumber): string
    {
        return '<xsd:shipment><xsd:shipper_num>'.Api::e($shipperNumber).'</xsd:shipper_num><xsd:shipping_date>'.($s->shippingDate ?? new \DateTimeImmutable())->format('Y-m-d\TH:i:s').'</xsd:shipping_date>'
            .'<xsd:service_type>'.Api::e($s->service ?? ('CA' === strtoupper($s->recipient->country) ? '1' : '6')).'</xsd:service_type><xsd:dimention_unit>C</xsd:dimention_unit><xsd:reported_weight_unit>K</xsd:reported_weight_unit>'
            .'<xsd:send_email_to_delivery>'.($s->recipient->email ? 'true' : 'false').'</xsd:send_email_to_delivery>'
            .self::address('pickup_address', $s->sender, $s->option('sender_province')).self::address('delivery_address', $s->recipient, $s->option('recipient_province'))
            .implode('', array_map([self::class, 'package'], $s->parcels))
            .($s->reference ? '<xsd:reference>'.Api::e(mb_substr($s->reference, 0, 40)).'</xsd:reference>' : '')
            .'</xsd:shipment>';
    }

    public static function status(?string $code, ?string $description = null): TrackingStatus
    {
        $d = strtolower((string) $description);

        return match (true) {
            'DEL' === $code || str_contains($d, 'delivered') => TrackingStatus::DELIVERED,
            'OFD' === $code || str_contains($d, 'out for delivery') => TrackingStatus::OUT_FOR_DELIVERY,
            str_contains($d, 'available for pickup') || str_contains($d, 'held for pickup') => TrackingStatus::AVAILABLE_FOR_PICKUP,
            str_contains($d, 'return') => TrackingStatus::RETURNED,
            str_contains($d, 'attempt') || str_contains($d, 'exception') || str_contains($d, 'unable') => TrackingStatus::EXCEPTION,
            'MAN' === $code || str_contains($d, 'manifest') || str_contains($d, 'shipment created') => TrackingStatus::PENDING,
            null !== $code && '' !== $code => TrackingStatus::IN_TRANSIT,
            default => TrackingStatus::UNKNOWN,
        };
    }
}
