Provides recurring billing for Drupal Commerce, powered by Advanced queue.

The successor to Commerce Recurring and Commerce License Billing for D7.

Features:
- Configurable billing intervals (charge every N days/weeks/months/years).
- Fixed and rolling interval types (charge on the 1st of the month VS 1 month from the subscription date)
- Prepaid and postpaid payment types (charge at the beginning or at the end of the billing period).
- Free trial of any interval (14 days followed by a regular monthly subscription, etc)
- Prorating (adjusting the charged price based on the duration of its usage)
- Usage tracking (track bandwidth and charge per GB, etc).

## Use cases

1) Recurring membership (via commerce_license)

Prepaid billing for a license (usually of type "role").

2) Recurring SaaS subscription

Postpaid billing, with optional usage, for a license.

3) Donations

Prepaid billing for a product variation (no license), or an order item without a purchasable entity.
Customers can usually select between multiple billing schedules (monthly/yearly, etc).

Future use cases: Physical products (Dollar Shave Club, etc)

## Setup

1) Go to /admin/commerce/config/billing-schedules/ and create a billing schedule.
2) Edit your product variation type and enable the "Allow subscriptions" trait.
3) Create a product variation with a "Subscription type" and a "Billing schedule" selected.

That's it! Each time your product variation is purchased, a subscription will be created for it.
By default, subscriptions are renewed on cron. This can be changed to a Drush/Drupal Console
daemon by editing the queue at /admin/config/system/queues/manage/commerce_recurring.

## Payment gateway requirements

The module requires an on-site payment gateway such as Commerce Braintree.
On-site gateways allow using tokenized payment methods for repeated charges, avoiding the need to store sensitive information such as credit card numbers.
The "Example (On-site)" payment gateway provided by commerce_payment_example can be used for testing purposes.
