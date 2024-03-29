# Laravel Salesforce

[![Tests](https://github.com/SynergiTech/laravel-salesforce/actions/workflows/test.yml/badge.svg)](https://github.com/SynergiTech/laravel-salesforce/actions/workflows/test.yml)

This package uses [omniphx/forrest](https://github.com/omniphx/forrest) to provide an Eloquent-style way of querying sObjects from Salesforce.

:warning: This is an initial version that only allows for the selecting of data. Newer versions will aim to provide a more complete experience.

## Getting Started

Follow the instructions provided at [omniphx/forrest](https://github.com/omniphx/forrest) to connect to your Salesforce environment.
Once done, you can then use the `SynergiTech\Salesforce\Facades\Salesforce` facade to perform queries against a particular table like so:

```php
use SynergiTech\Salesforce\Facades\Salesforce;

// Get an individual record by Id
Salesforce::table('MyTable')->find('YourIdHere');
```

## Available Methods

### find

Allows you to directly pull an individual record as an array by Id.
You can also specify another field name as the second parameter.
If you specify a non-unique column and multiple records are returned then the first record is always returned.

#### Usage

```php
Salesforce::table('MyTable')->find('YourIdHere');
```

### findMany

Allows you to directly pull multiple records as a Laravel Collection by provide an array of their respective Id fields.
You can also specify another field name as the second parameter.

#### Usage

```php
Salesforce::table('MyTable')->findMany(['YourId1Here', 'YourId2Here']);
```

### create

Allows you to create a new record on the specified table using an array of fields.

#### Usage

```php
$response = Salesforce::table('MyTable')->create([
    'Name' => 'John Doe',
]);
```

#### Expected Response

```php
[
    'id' => '', // Salesforce Id
    'success' => true,
    'errors' => [],
    'data' => [
        // Full record data
    ],
]
```

### update

Allows you to update a record using it's Salesforce Id with an array of fields.

#### Usage

```php
$response = Salesforce::table('MyTable')->update('Id', [
    'Name' => 'John Doe',
]);
```

#### Expected Response

See 'create' above

### createOrUpdate

Allows you to upsert a record using an Id field and the associated value.

#### Usage

```php
$response = Salesforce::table('MyTable')->createOrUpdate('My_External_Id__c', 'ExternalId', [
    'Name' => 'John Doe',
]);
```

#### Expected Response

```php
[
    'id' => '', // Salesforce Id
    'success' => true,
    'errors' => [],
    'created' => true, // True/False depending on whether the record was created or updated
    'data' => [
        // Full record data
    ],
]
```

### delete

Allows you to delete a record by it's Id, returning true if successful.

#### Usage

```php
Salesforce::table('MyTable')->delete('Id');
```

## Query Builder

This package allows you to scope your get calls using query builder methods.
Query builders **cannot** currently be used in conjunction with the `update` or `delete` methods (sorry 🙏).

### where

You can also scope your queries with where clauses.

```php
// Basic where clause
Salesforce::table('MyTable')->where('Name', 'John Doe')->get();

// You can also use any of the following operators

// Equals and Not Equals
Salesforce::table('MyTable')->where('Name', '=', 'John Doe')->get();
Salesforce::table('MyTable')->where('Name', '!=', 'John Doe')->get();

// Comparisons
Salesforce::table('MyTable')->where('Age', '<', 30)->get();
Salesforce::table('MyTable')->where('Age', '<=', 30)->get();
Salesforce::table('MyTable')->where('Age', '>', 30)->get();
Salesforce::table('MyTable')->where('Age', '>=', 30)->get();

// Like
Salesforce::table('MyTable')->where('Name', 'LIKE', 'John %')->get();
Salesforce::table('MyTable')->where('Name', 'LIKE', '% Middlename %')->get();
Salesforce::table('MyTable')->where('Name', 'LIKE', '% Doe')->get();
```

### whereIn

You can provide an array of possible values to the `whereIn` method to select any records that match any of the values.

```php
Salesforce::table('MyTable')->whereIn('Country', ['United Kingdom', 'United States'])->get();
```

### orderBy

You can order by a particular field in either ascending or descending order.

```php
// Ascending (default)
Salesforce::table('MyTable')->orderBy('Age')->get();

// Descending
Salesforce::table('MyTable')->orderBy('Age', 'DESC')->get();
```

### nullsLast

By default when chaining an orderBy null values are returned first.
You can chain on `->nullsLast()` to return null values last.

```php
Salesforce::table('MyTable')->orderBy('LastLoginDate')->nullsLast()->get();
```

### limit

You can limit the amount of records returned.

```php
Salesforce::table('MyTable')->where('Name', 'LIKE', 'John%')->limit(20)->get();
```

## Exceptions

By default [omniphx/forrest](https://github.com/omniphx/forrest) typically throws a single exception with more detail contained within a JSON encoded string.
We've wrapped some with our own exceptions to help with debugging.