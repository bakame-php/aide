# Aide for Enums

A collection of traits to avoid repeating the same methods on PHP's Enum.

> [!CAUTION]  
> Sub-split of Aide for Enum helpers.  
> ⚠️ this is a sub-split, for pull requests and issues, visit: https://github.com/bakame-php/aide

## Installation

### Composer

~~~
composer require bakame-php/aide-enums
~~~

### System Requirements

You need:

- **PHP >= 8.1** but the latest stable version of PHP is recommended

## Usage

### Factory

Enable creating Pure or Backed Enum based on their name. The trait adds two (2) new static methods,
`tryFromName` and `fromName` and re-introduce the `tryFrom` and `from` method on pure Enums.
Once added to your Enum you can do the following:

```php
<?ph

HttpMethod::fromName('Get') === HttpMethod::Get;
HttpMethod::tryFromName('Get') === HttpMethod::Get;
HttpMethod::tryFromName('Unknown'); // returns null
HttpMethod::tryFrom('Put') === HttpMethod::Put
HttpMethod::From('Unknown'); //throw a ValueError
```

You need the `Bakame\Aide\Enum\Factory` trait to expose the new API.

```php
<?php

use Bakame\Aide\Enum\Factory;

enum HttpMethod
{
    use Factory;

    case Get;
    case Post;
    case Put;
    case Head;
    case Options;
}
```

### Info

Gather information regarding the current Enum via **public static methods**. This trait enables getting:

- the number of cases via the `size` method;
- the type of enum via the `isBacked` and `isNotBacked` method;
- the names of each cases with the `names` method;
- the possible values for the Enum with the `values` method;
- the `associative` method which returns an associative array contains the string name and their respective values;
- the `nameOf` which returns the name associated with a specific `value`

```php
<?php

HttpMethod::size();        //returns the number of cases
HttpMethod::isBacked();
HttpMethod::isNotBacked(); // returns the inverse of the `isBacked` method
HttpMethod::names();       // returns a list of all the names in the enumeration
HttpMethod::values();      // returns a list of all the names in the enumeration
HttpMethod:nameOf(404);    // returns the name associated with the given value
                           // or null if it does not exist for the submitted value.
```

You need the `Bakame\Aide\Enum\Info` trait to expose the new API.

```php
<?php

use Bakame\Aide\Enum\Info;

enum HttpMethod: string
{
    use Info;

    case Get = 'GET';
    case Post = 'POST';
    case Put = 'PUT';
    case Head = 'HEAD';
    case Options = 'OPTIONS'
}
```

### Hasser/Isser methods

Enables asking whether some data are present in the Enum

```php
<?php

HttpMethod::hasName('GET'); //returns false;
HttpMethod::hasValue('GET'); //returns true;
HttpMethod::has('Head'); //returns true;
HttpMethod::hasCase('Header', 'HEAD'); //returns false;
```

`hasValue` and `hasCase` will always return false for a Pure enumeration.

You need the `Bakame\Aide\Enum\Hasser` trait to expose the new API.

```php
<?php

use Bakame\Aide\Enum\Hasser;

enum HttpMethod: string
{
    use Hasser;

    case Get = 'GET';
    case Post = 'POST';
    case Put = 'PUT';
    case Head = 'HEAD';
    case Options = 'OPTIONS'
}
```

### Comparison

The `Compare` trait which adds four (4) methods to compare Enums instances.
The `equals` and `notEquals` methods do strict comparison whereas `isOneOf`
and `isNotOneOf` do loose comparison taking into account the value or the name
of the Enum.
 
```php
<?php

HttpMethod::Get->equals(HttpMethod::Post);      //returns false
HttpMethod::Get->isOneOf('GET', 'Get', 'get');  //returns true because `Get` is present
HttpMethod::Get->notEquals('get');              //returns true;
HttpMethod::Get->isNotOneOf('Head');            //returns true;
```

You need the `Bakame\Aide\Enum\Compare` trait to expose the new API.

```php
<?php

use Bakame\Aide\Enum\Compare;

enum HttpMethod: string
{
    use Compare;

    case Get = 'GET';
    case Post = 'POST';
    case Put = 'PUT';
    case Head = 'HEAD';
    case Options = 'OPTIONS'
}
```

### All in one

If you want to apply all the traits together just use the single one which encompass all the traits
already mentioned `Bakame\Aide\Enum\Helper`. Once added to your enum all the methods described here
will be made available to your codebase.

You need the `Bakame\Aide\Enum\Helper` to expose the new API.

```php
<?php

use Bakame\Aide\Enum\Helper;

enum HttpMethod: string
{
    use Helper;

    case Get = 'GET';
    case Post = 'POST';
    case Put = 'PUT';
    case Head = 'HEAD';
    case Options = 'OPTIONS'
}
```

## Credits

- [ignace nyamagana butera](https://github.com/nyamsprod)
- [All Contributors](https://github.com/bakame-php/aide/graphs/contributors)
