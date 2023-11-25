# Bakame Aide for Errors

A collection of class to avoid repeating the same methods on PHP's Enum.

> [!CAUTION]
> Sub-split of Aide for Error.
> ⚠️ this is a sub-split, for pull requests and issues, visit: https://github.com/bakame-php/aide

## Installation

### Composer

~~~
composer require bakame-php/aide-error
~~~

### System Requirements

You need:

- **PHP >= 8.1** but the latest stable version of PHP is recommended

## Usage

Traditionnally to correctly handle errors with PHP's function you have two (2) options. Either
you use the `@` to suppres the error or you need to add some boilerplate code around `set_error_handler`

The `Bakame\Aide\Error\Cloak` utility class helps remove that burden.

```php
<?php

use Bakame\Aide\Error\Cloak;

//before

if (!touch('/foo')) {
    //something went wrong and I have a warning.
}

//after

$touch = Cloak::warning(touch(...));
if (!$lambda('/foo')) {
    $lambda->lastError(); //returns the last error as an \ErrorException;
}
````

You can control its behaviour on your global codebase

```php
<?php

use Bakame\Aide\Error\Cloak;

Cloak::throwOnError();

try {
    $touch = Cloak::warning(touch(...));
} catch (\ErrorException $exception)
}
````

Or you can decide to specifically change its default behaviour for a specific call.

```php
<?php

use Bakame\Aide\Error\Cloak;

Cloak::throwOnError(); // by default calls via Cloack should throw

if (!$touch = Cloak::warning(touch(...), Cloak::SILENCE_ERROR)) {
    //the error is still available via 
    // but no throwing will happen
    $touch->lastError();
}
````

## Available properties and methods

### Accessing the Error Reporting Level

Once instantiated, you can always access the error reporting level via the `suppress*` methods
For instance if you need to know if the instance will suppress user deprecated error
you can do the following:

```php
$touch = Cloak::warning(touch(...));
if ($touch->suppressWarning()) {
    //tells wether or not E_WARNING is suppressed
}
```

The following methods are available.

```php
<?php
use Bakame\Aide\Error\Cloak;

Cloak::suppressAll();
Cloak::suppressWarning();
Cloak::suppresNotice();
Cloak::suppressDeprecated();
Cloak::suppresStrict();
Cloak::suppresUserWarning();
Cloak::suppressUserNotice();
Cloak::suppressUserDeprecated();
```

### Accessing the error

To access the last error store in the instance you need to call the `Cloak::lastError` method.
If no error occurred during the last execution of the class the method will return `null`,
otherwise you will get an `ErrorException` class containing all the detail about the
last error.

### Controlling when to throw or not your errors.

The class general behaviour is controlled by two (2) static method:

- `Cloak::throwOnError`: every instance of `Cloak` will throw on error;
- `Cloak::silenceError`: every instance of `Cloak` will record the error but won't throw it;

> [!NOTE]
> to respect PHP's default behaviour by default `Cloak` uses `Cloak::silenceError`

### Named constructors

To ease usage the following named constructors are added:

```php
<?php
use Bakame\Aide\Error\Cloak;

Cloak::all();
Cloak::warning();
Cloak::notice();
Cloak::deprecated();
Cloak::strict();
Cloak::userWarning();
Cloak::userNotice();
Cloak::userDeprecated();
```

They all share the same signature:

```php
Cloack::namedConstructor(Closure $closure, int $behaviour = Cloak::FORCE_NOTHING);
```

the `$behaviour` argument is used to tweak the instance behaviour on error:

- `Cloak::THROW_ON_ERROR` will override the general behaviour and force throwing an exception if available
- `Cloak::SILENCE_ERROR` will override the general behaviour and silence the error if it exists
- `Cloak::FORCE_NOTHING` will comply with the general behaviour.

If you really need other fined grained error level you can still use the constructor
as shown below:

```php
<?php
use Bakame\Aide\Error\Cloak;

$touch = new Cloak(
    touch(...),
    E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED,
    Cloak::THROW_ON_ERROR
);
```

## Credits

- [Bishop Bettini and Haldayne PHP Componentry](https://github.com/haldayne/fox)
- [ignace nyamagana butera](https://github.com/nyamsprod)
- [All Contributors](https://github.com/bakame-php/aide/graphs/contributors)
