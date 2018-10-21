# Uk Postcode Geocoder

A geocoder (PHP) for UK postcodes, based on free open postcode data.

In simple terms, that means you can obtain the latitude and longitude of a postcode.

Data comes from the Office for National Statistics Postcode Directory, and is stored in an SQLite database for blazing fast lookups.

If you want to provide it as a self-hosted web service, you might find [this repository](https://github.com/lukaswhite/uk-postcode-web-service) useful.

## A Note about Geocoding UK postcodes

There are a few different ways of geocoding postcodes, each with their own pros and cons; and indeed there is a key limitation to this library. That's the fact that the data set is not 100% complete. It's very comprehensive; there are currently over 2.6 million postcodes in it. However, PAF &mdash; that's the official data set from the Royal Mail &mdash; licenses are very expensive.

Because of this, you may need an alternative method such as Google Maps for those rare cases that a postcode is not in this database. However using this library means that the overwhelming majority of your geocoding requests will not incur a cost, and will not be subject to the overhead of using a third-party API. 

The other alternative is [postcodes.io](http://postcodes.io/), a free API for doing the same thing; albeit with additional information. This is based on the same ONS data, so if you use this package and keep the data up-to-date, then it's essentially the same as using this package, but with the overhead of an HTTP request. It's open source so you can host it yourself; the primary difference between that and this library is that it's written in Node.js; this is essentially an alternative for those working in a PHP environment.

## Getting Started

Install the package using Composer:

```bash
composer require lukaswhite/uk-postcode-geocoder
```

**Important**: the data is not included in the package. This is due to the size of the file, and the fact that it's updated periodically.

The package does, however, include a bash script that will create and populate the database for you.

You'll find the data on the [ONS Geo Portal](https://geoportal.statistics.gov.uk/geoportal/catalog/main/home.page), just search for "Postcode Directory".

At time of writing (November 2018), the latest file was published in August 2018.

The file you're looking for is a CSV with a filename similar to `ONSPD_AUG_2018_UK.csv`.

To run the setup process:

```bash
vendor/lukaswhite/uk-postcode-geocoder/bin/setup.sh [STORAGE DIRECTORY] [PATH TO CSV]
```

For example:

```bash
vendor/lukaswhite/uk-postcode-geocoder/bin/setup.sh ./storage/database ONSPD_AUG_2018_UK.csv
```

You may also run the provisioning process programmatically; it's all done using the `Provisioner` class; take a look at `setup.sh` to see how to use it.

## Usage

Create an instance, passing it the directory which contains the SQLite database:

```php
use Lukaswhite\UkPostCodeGeocoder\Service;

$service = new Service( '/path/to/db' );
```

This assumes that the database filename is `postcodes.sqlite`, but you can override this:

```php
$service = new Service( '/path/to/db', 'db.sqlite );
```

## Geocoding a Single Postcode

To geocode &mdash; that's to say, get the latitude and longitude of &mdash; a UK postcode, call the `get()` method:

```php
$coordinates = $service->get( 'SW1A 2AA' );
```

> The service is quite forgiving about the format; `sw1a2aa` would also work.

This returns an object that contains two key methods; `getLatitude()` and `getLongitude()`. It also includes a `toArray()` method, and implements the `JsonSerializeable` interface.

## Geocoding Multiple Postcodes

You can also query multiple postcodes in one go with the `getMultiple()` method, for example:

```php
$results = $service->getMultiple( 'AB1 0AG', 'AB1 0AL', 'AB1 0AD' );
```

This returns an associative array of instances of `Coordinate`, keyed by the properly formatted postcode.

## Adding Postcodes

The data is comprehensive &mdash; over 2.6 million postcodes &mdash; but it's known not to be absolutely complete. 

As such, you may need to use another source such as Google Maps if the postcode that you're looking up cannot be found. In such an instance you may wish to add it to this database, which you can do so using the `add()` method; for example:

```php
$service->add(
	'sw1A2aa',
	new Coordinate( 51.50354, -0.127695 )
);
```

Note that the library is quite forgiving about how you format the postcode, as you can see above. However passing an invald postcode will throw an `InvalidPostcodeException`. If the postcode is already in the database, it'll throw a `DuplicatePostcodeException`.