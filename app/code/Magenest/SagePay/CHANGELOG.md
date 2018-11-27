# Change Log
All notable changes to SagePay Payment Gateway and Subscription extension will be documented in this file.
This extension adheres to [Magenest](http://magenest.com/).

## [1.5.0] - 2018-01-26
Sagepay compatible with 
```
Magento Commerce 2.1.x, 2.2.x, 
Magento OpenSource 2.1.x, 2.2.x
```
-   Added ReferrerId
-   Improve security
-   Fix sagepay dropin error when load checkout page

## [1.4.0] - 2018-01-03
Sagepay compatible with 
```
Magento Commerce 2.1.x, 2.2.x, 
Magento OpenSource 2.1.x, 2.2.x
```
### Added
-   Improve payment security
-   Add validate payment response
-   Sage own form: PCI-DSS compliant using the SAQ-A-EP self assessment questionnaire
-   Sage drop-in: PCI-DSS compliant using the SAQ-A self assessment questionnaire
-   Add alert box when check API key
### Fixed
-   Fix bug gift aid is not selected
-   Submit order bug freeze
-   Fix bug response duplicated
-   Fix Sage Js loaded two time(speed up checkout page)
### Remove
-   Remove option debug log. The debug file now stored in `{magento}/var/log/sagepay/debug.log`

## [1.3.5] - 2017-12-05
### Added
-   Improve security
-   Add check Api in config backend
-   Add support information in config backend
-   Change backend config layout
### Fixed
-   Fix bug refund order error when vendor prefix code input very long
-   Fix bug require js loading

## [1.3.0] - 2017-11-13
### Added
-   Add Sage Moto Payment Service
-   Add total amount order check.
### Fixed
-   Fix bug 3d secure response with error status

## [1.2.0] - 2017-10-14
### Update
-   Sagepay compatible with Magento Commerce 2.1.x, 2.2.x, Magento OpenSource 2.1.x, 2.2.x
-   Fix bug in edit product page
### Added
-   Notify user when merchant key error, payment error return from bank
-   Add browser output console log.
-   Add some helpful comment in backend config.
-   Add capture notification for customer when cron job running.
### Fixed
-   Fix checkout layout broken in mobile screen.
-   Fix default config sagepay.
-   Fix cron job running everyday.

## [1.1.0] - 2017-08-11
### Big update
-   A lot of bug was fix
-   Compatible with CE, EE: 2.1.x

### Fixed
-   Fix send mail error when checkout with 3d secure
-   Partial refunds after create invoice
-   Fix bug restore cart when 3d secure fail
-   Fix bug order state pending when 3d secure response fail
-   Fix bug conflict javascript with some one step checkout
-   Fix bug different billing address and shipping address
-   Fix bug currency multiply error with some non-decimal currency type
-   Fix bug save card with some type of credit card
-   Fix bug show logo error (404 blank image)
-   Fix bug post code and state missing with some country

### Added
-   Gift Aid: end-user now have a checkbox to donate the taxes.
-   Sort order: User can input the number to sort the payment method.
-   Payment instructions: Admin can input html payment instructions 
-   Add some javascript response to website
-   Add ignore address check(in test mode)

### Changed
-   Debug logger now clean and easy to read.
-   Change all the sage-pay javascript library to pi-live.sagepay.com 
-   Sagepay drop-in method now using custom i-frame form

## [1.0.3] - 2017-07-10
### Fixed
-   Sagepay payment error
-   Subscription
-   Sagepay create product error
-   Some bug when capture, refund
-   Checkout loader
### Added
-   Save card token
-   Save card and usable

## [1.0.2] - 2016-12-23
### Added
1. Multiple subscription profiles

## [1.0.1] - 2016-10-20
### Added
1. Fix sagepay.js error for test/live integration

## [1.0.0] - 2016-08-13
### Added
1. Allow customers to checkout using credit card payment
2. Allow admins to integrate their SagePay account to the Magento store
    - Enable or disable the gateway
    - Integrate using vendor credential
    - Specify allowed credit card types and countries
    - Specify minimum and maximum order amount
3. Add a layer of security with 3D checkout
4. Support multiple stores
5. Easily check transaction history
6. Admins can create subscription plans for each products, each includes
    - Subscription frequency
    - Total amount of cycles for a profile (0 for ongoing subscription)
7. Customers can subscribe to an available plan on a specific product. A profile will then be created.
8. Admins can easily manage profiles or disable them.
9. Customers can also manage their profiles and disable them as well.