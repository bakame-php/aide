# Aide for Errors

A Cloak system to help dealing with error reporting in PHP.

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

Traditionally to correctly handle errors with PHP's functions you have two (2) options. Either
you use the `@` to suppress the error which is not recommended; or you need to add some
boilerplate code around `set_error_handler` and `restore_error_handler`.

The `Bakame\Aide\Error\Cloak` utility class helps you remove that burden by doing the heavy-lifting for you.

```php
<?php

use Bakame\Aide\Error\Cloak;

//using the @ suppression operator
$res = @touch('/foo'); // bad and not recommended

//using error handler
set_error_handler(fn (int $errno, string $errstr, string $errfile = null, int $errline = null) {
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return;
    }

    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
}, E_WARNING);
$res = touch('/foo'); 
restore_error_handler();
// better but having to write this everytime is overkill
// and you have little control

//using Cloak
$touch = Cloak::warning(touch(...));
$res = $touch('/foo');
$touch->errors();
// returns a CloakedErrors instance
// the instance is empty on success
// otherwise contains all the \ErrorException
// generated during the closure execution
````

You can control its behaviour on your global codebase

```php
<?php

use Bakame\Aide\Error\Cloak;

Cloak::throwOnError();

try {
    $touch = Cloak::warning(touch(...));
} catch (ErrorException $exception)
}
````

Or you can decide to specifically change its default behaviour for a specific call.

```php
<?php

use Bakame\Aide\Error\Cloak;

Cloak::throwOnError(); // by default calls via Cloak should throw

if (!$touch = Cloak::warning(touch(...), Cloak::SILENT)) {
    // errors are still available via the `errors` method
    // but throwing will not happen
    $touch->errors();
}
````

## Available properties and methods

### Accessing the error

To access the errors store in the instance you need to call the `Cloak::errors` method
which will return a `CloakedErrors` instance. This container gives you can access all
the `ErrorException` generated during the last execution of the callback.
If no error occurred during the last execution of the class, the `CloakedErrors` instance
will be empty.

```php
$touch = Cloak::all(touch(...));
$touch('/foobar');
$errors = $touch->errors(); // $errors is a CloakedErrors instance
$errors->isEmpty(); //true if the execution generated 0 ErrorException; false otherwise
foreach ($errors as $error) {
    $error; //ErrorException instances ordered from the newest to the oldest one.
}
$errors->first(); // the oldest ErrorException
$errors->last();  // the newest ErrorException
$errors->get(2); 
$errors->get(-2);
// returns any of the ErrorException and accept negative index.
// the three (3) methods will return `null` if no exception
// exists for the specified offset
```

### Controlling when to throw or not your errors.

The class general behaviour is controlled by two (2) static methods.
In all cases if an error occurred, it is converted into an `ErrorException`
and will be made accessible via the `Cloak::errors` method. The difference
being that with:

- `Cloak::throwOnError`: every instance will throw on the first error;
- `Cloak::silentOnError`: no exception will be thrown;

> [!NOTE]
> to respect PHP's behaviour, `Cloak` uses `Cloak::silentOnError` by default

### Named constructors

To ease usage the named constructors are added:

```php
<?php
use Bakame\Aide\Error\Cloak;

Cloak::env(); // will use the current environment error reporting value
// and one for each error reporting level that exists in PHP
Cloak::all();
Cloak::error();
Cloak::warning();
Cloak::notice();
Cloak::deprecated();
Cloak::userError();
Cloak::userWarning();
Cloak::userNotice();
Cloak::userDeprecated();
// some Error reporting will never get triggered
// they exist for completeness but won't be usable.
```

They all share the same signature:

```php
static method(Closure $callback, int $onError = Cloak::OBEY);
```

the `$onError` argument is used to tweak the instance behaviour on error:

- `Cloak::THROW` will override the general behaviour and force throwing an exception if available
- `Cloak::SILENT` will override the general behaviour and silence the error if it exists
- `Cloak::OBEY` will comply with the curring general behaviour.

If you really need other fined grained error level you can still use the constructor
as shown below:

```php
<?php
use Bakame\Aide\Error\Cloak;

$touch = new Cloak(
    touch(...),
    Cloak::THROW,
    E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED,
);
```

### ReportingLevel class

Because dealing with PHP error reporting level can be confusing sometimes, the package ships with an friendlier
approach to deal with them. As an example, the previous code example can be rewritten using the
`ReportingLevel` class.

```php
<?php
use Bakame\Aide\Error\Cloak;
use Bakame\Aide\Error\ReportingLevel;

$touch = new Cloak(
    touch(...),
    Cloak::THROW,
    ReportingLevel::fromExclusion(E_NOTICE, E_STRICT, E_DEPRECATED),
);
```

The class exposes a friendlier API to ease working with error reporting level:

- `ReportingLevel::fromValue` allow instantiating the class with any value you want. 
- `ReportingLevel::fromName` allow instantiating the class with the string corresponding to one of the `E_*` constants.
- `ReportingLevel::fromEnv` instantiates the class to match your current environment settings.
- `ReportingLevel::fromInclusion` instantiates the error level by adding all the submitted values via a 
bitwise `OR` operation starting at `0` meaning that no Error reporting level exists if none is added.
- `ReportingLevel::fromExclusion` does the opposite, each value given will be removed from the maximum value, represented by `E_ALL`.

on top of that the class expose a construct for each error reporting level using the following syntax:

```php

use Bakame\Aide\Error\ReportingLevel;

ReportingLevel::warning()->value(); // returns the same value as E_WARNING.
ReportingLevel::userDeprecated()->value(); // returns the same value as E_USER_DEPRECATED.
// and so on for each error reporting level
```

You can tell which error reporting is being configured using the `contains` method.
The class also provides the `excluded` and `included` methods which returns the 
error reporting level names.

```php
<?php

use Bakame\Aide\Error\ReportingLevel;

ReportingLevel::fromEnv()->contains(E_WARNING);
// returns true if the current value in error_reporting contains `E_WARNING`
// returns false otherwise.

$reportingLevel = ReportingLevel::fromInclusion(E_NOTICE, "E_DEPRECATED");
 
$reportingLevel->value();
// `value` returns the int value corresponding to the calculated error level.
//  the errorLevel calculated will ignore notice, and deprecated error.

$reportingLevel->excluded(); 
// returns all the error reporting level name no present in the current error Level

$reportingLevel->included(); 
// returns all the error reporting level name present in the current error Level
// ["E_NOTICE", "E_DEPRECATED"]
```

### Accessing the Error Reporting Level from a Cloak instance

Once instantiated, you can always access the error reporting level via
the `errorLevel` method on a `Cloak` instance. For example, if you need to know if a
specific error is included you can do the following:

```php
$touch = Cloak::all(touch(...));
$touch->reportingLevel()->contains(E_WARNING);  //tells if the E_WARNING is included or not
```

## Credits

- [Bishop Bettini and Haldayne PHP Componentry](https://github.com/haldayne/fox)
- [ignace nyamagana butera](https://github.com/nyamsprod)
- [All Contributors](https://github.com/bakame-php/aide/graphs/contributors)
