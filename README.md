# omnibus/canpar

Canpar for [glitchr/omnibus](https://github.com/glitchr-studio/omnibus): rates, shipments with
their labels, tracking and voids - the CanShip web services (SOAP).

```yaml
omnibus:
    gateways:
        canpar:
            factory: canpar
            options:
                user_id: '%env(CANPAR_USER)%'
                password: '%env(CANPAR_PASSWORD)%'
                shipper_number: '%env(CANPAR_SHIPPER)%'
                sandbox: true
                rates: [...]        # optional: configured prices instead of rateShipment
```

The service is Canpar's service type: 1 Ground, 2 Select, 3 Overnight, 4 USA, 5 Select USA,
6 International. Rating asks once per service (option `services` picks them). Shipment options:
`sender_province` and `recipient_province` (two letters), `label_format` (PDF, ZPL for thermal).
No pickup points: Canpar delivers to the door.

Credentials: a Canpar account with CanShip web services access (ask your account manager for the
sandbox login) and your shipper number.

Built from Canpar's published CanShip documentation and tested on recorded answers; not yet run
against the sandbox: that needs the credentials above.

License: LGPL-3.0-or-later.
