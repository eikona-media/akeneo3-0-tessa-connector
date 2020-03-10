<p align="center">
  <a href="https://www.tessa-dam.com/" target="_blank" rel="noopener noreferrer">
    <img src="tessa-logo.svg" width=250 alt="TESSA Logo"/>
  </a>
</p>

<p>&nbsp;</p>

<h1 align="center">
  TESSA Connector for Akeneo 3.0
</h1>

<p>&nbsp;</p>

With this Connector Bundle you seamlessly connect Akeneo with the Digital Asset Management solution "TESSA" (https://www.tessa-dam.com).
This provides you with a professional and fully integrated DAM solution for Akeneo to centrally store,
manage and use all additional files for your products (e.g. images, videos, documents, etc.) in all channels.

More informationen is available at our [website](https://www.tessa-dam.com/). 

## Requirements

| Akeneo                        | Version |
|:-----------------------------:|:-------:|
| Akeneo PIM Community Edition  | ~3.0.15 |
| Akeneo PIM Enterprise Edition | ~3.0.15 |

<span style="color:red">__IMPORTANT!__</span> Ensure, that your Akeneo API ist working. Tessa needs an API connection to your Akeneo.
In some cases Apache is configured wrong, see https://api.akeneo.com/documentation/troubleshooting.html#apache-strip-the-authentication-header.

## Installation

1) Install the bundle with composer
```bash
composer require eikona-media/akeneo3-0-tessa-connector
```

2) Then add the following lines **at the end** of your app/config/routing.yml :
```yaml
tessa_media:
    resource: "@EikonaTessaConnectorBundle/Resources/config/routing.yml"
```

3) Enable the bundle in the `app/AppKernel.php` file in the `registerProjectBundles()` method:
```php
protected function registerProjectBundles()
{
    return [
        // ...
        new Eikona\Tessa\ConnectorBundle\EikonaTessaConnectorBundle(),
    ];
}

```

4) Run the following commands in your project root:
```bash
php bin/console cache:clear --env=prod --no-warmup
php bin/console cache:warmup --env=prod
php bin/console pim:installer:dump-require-paths --env=prod
php bin/console pim:installer:assets --env=prod
yarn run webpack
```

5) Update your database schema

```bash
php bin/console doctrine:schema:update --dump-sql # Show changes
php bin/console doctrine:schema:update --force # Update database schema
```

6) Configure the Tessa Connector in your Akeneo System Settings.

7) (Optionally) Create a cronjob to synchronize data with TESSA in the background

This is only necessary if you use the option "Sync in background" in the system settings

```
php bin/console eikona_media:tessa:notification_queue:execute --env=prod
```

Recommended to run every 5 minutes (`*/5 * * * *`). If the command is started twice at the same time, the second command exists with a notice.


## How to use with reference entities (Enterprise Edition >3.0)

1) Enable the ReferenceDataAttributeBundle in the `app/AppKernel.php` file in the `registerProjectBundles()` method (after the `EikonaTessaConnectorBundle`):
```php
protected function registerProjectBundles()
{
    return [
        // ...
        new Eikona\Tessa\ConnectorBundle\EikonaTessaConnectorBundle(), // Already registered
        new Eikona\Tessa\ReferenceDataAttributeBundle\EikonaTessaReferenceDataAttributeBundle(), // New
    ];
}
```

2) Select TESSA in the type dropdown when you add a new reference entity attribute

## How to use with CustomEntityBundle

1) Make sure you have the [CustomEntityBundle](https://github.com/akeneo-labs/CustomEntityBundle) installed

2) Enable the ReferenceDataBundle in the `app/AppKernel.php` file in the `registerProjectBundles()` method (after the `EikonaTessaConnectorBundle`):
```php
protected function registerProjectBundles()
{
    return [
        // ...
        new Eikona\Tessa\ConnectorBundle\EikonaTessaConnectorBundle(), // Already registered
        new Eikona\Tessa\ReferenceDataBundle\EikonaTessaReferenceDataBundle(), // New
    ];
}
```

3) Use the module in your edit forms:
```yaml
    pim-brand-edit-logo:
        module: eikona/tessa/connector/reference-data/form/field # Use this module
        parent: pim-brand-edit-form-properties-common
        targetZone: content
        position: 100
        config:
            fieldName: logo # This field has to be of type "string"
            label: acme_custom.brand.field.label.logo
            customEntityName: brand
            allowedExtensions: ['jpg', 'jpeg', 'png'] # Allowed extensions (default: no restriction)
            maximumCount: 4 # Maximum assets (default: unlimited)
```

4) (Optionally) Show thumbnails in the datagrid:

```yml
datagrid:
    brand:
        # ...
            columns:
                 # ...
                 logo:
                    label: acme_custom.brand.field.label.logo
                    type: twig
                    frontend_type: html
                    template: EikonaTessaReferenceDataBundle:datagrid:thumbnail.html.twig
```
