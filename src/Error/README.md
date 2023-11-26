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

Traditionally to correctly handle errors with PHP's functions you have two (2) options. Either
you use the `@` to suppress the error which is not recommended or you need to add some
boilerplate code around `set_error_handler` and `restore_error_handler`.

The `Bakame\Aide\Error\Cloak` utility class helps you remove that burden by doing the heavylifting for you.

```php
<?php

use Bakame\Aide\Error\Cloak;

//before
$res = @touch('/foo'); // bad and not recommended

set_error_handler(fn (int $errno, string $errstr, string $errfile, int $errline) => true);
$res = touch('/foo'); 
restore_error_handler();
// better but you lost some information in case of error
// having to write this everytime is overkill

//using Cloak
$touch = Cloak::all(touch(...));
$res = $touch('/foo');
$touch->errors(); //returns a CloakedErrors
// returns a CloakedErrors instance
// the instance is empty on success
// otherwise contains all the \ErrorExceptions
// generated during the closure execution
````

You can control its behaviour on your global codebase

```php
<?php

use Bakame\Aide\Error\Cloak;
use Bakame\Aide\Error\CloakedErrors;

Cloak::throwOnError();

try {
    $touch = Cloak::warning(touch(...));
} catch (CloakedErrors $exception)
}
````

Or you can decide to specifically change its default behaviour for a specific call.

```php
<?php

use Bakame\Aide\Error\Cloak;

Cloak::throwOnError(); // by default calls via Cloack should throw

if (!$touch = Cloak::warning(touch(...), Cloak::SILENT)) {
    // errors are still available via the `errors` methpd
    // but throwing will not happen
    $touch->errors();
}
````

## Available properties and methods

### Accessing the Error Reporting Level

Once instantiated, you can always access the error reporting level via
the `errorLevel` method. For instance if you need to know if a 
specific error is included you can do the following:

```php
$touch = Cloak::all(touch(...));
$touch->errorLevel()->contains(E_WARNING);  //tells if the E_WARNING is included or not
```

### Accessing the error

To access the errors store in the instance you need to call the `Cloak::errors` method
which will return a `CloakedErrors` instance. This exception which extends PHP's 
`RuntimeException` is a container where you can access all the `ErrorException` generated
during the execution of the closure.
If no error occurred during the last execution of the class the `CloakedErrors` instance
will be empty, otherwise you will get all the `\ErrorException` instances generated.

```php
$touch = Cloak::all(touch(...));
$touch('/foobar');
$errors = $touch->errors(); // $errors is a CloakedErrors instance
$errors->isEmpty(); //true if the execution generated 0 ErrorException; false otherwise
foreach ($errors as $error) {
    $error; //ErrorException instances ordered from the newest one -> oldest one.
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

The class general behaviour is controlled by two (2) static method:

- `Cloak::throwOnError`: every instance of `Cloak` will throw on error;
- `Cloak::silentOnError`: every instance of `Cloak` will record the error but won't throw it;

> [!NOTE]
> to respect PHP's default behaviour by default `Cloak` uses `Cloak::silentOnError`

### Named constructors

To ease usage the following named constructors are added:

```php
<?php
use Bakame\Aide\Error\Cloak;

Cloak::all();
Cloak::warning();
Cloak::notice();
Cloak::deprecated();
Cloak::userWarning();
Cloak::userNotice();
Cloak::userDeprecated();
Cloak::fromEnvironment(); // will use the current environment error reporting value
```

They all share the same signature:

```php
static method(Closure $closure, int $onError = Cloak::FOLLOW_ENV);
```

the `$onError` argument is used to tweak the instance behaviour on error:

- `Cloak::THROW` will override the general behaviour and force throwing an exception if available
- `Cloak::SILENT` will override the general behaviour and silence the error if it exists
- `Cloak::FOLLOW_ENV` will comply with the general behaviour.

If you really need other fined grained error level you can still use the constructor
as shown below:

```php
<?php
use Bakame\Aide\Error\Cloak;

$touch = new Cloak(
    touch(...),
    Cloak::THROW,
    E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED
);
```

The code above can be rewritten using the `ErrorLevel` class which ships with the package:

```php
<?php
use Bakame\Aide\Error\Cloak;
use Bakame\Aide\Error\ErrorLevel;

$touch = new Cloak(
    touch(...),
    Cloak::THROW,
    ErrorLevel::fromExclusion(E_NOTICE, E_STRICT, E_DEPRECATED)
);
```

The class contains five (5) methods to ease working with error reporting level:

`ErrorLevel::fromValue` allow instantiating the class with any value you want. Alternatively, you can
instantiate the class to match your current environment settings using `ErrorLevel::fromEnvironment`.
`ErrorLevel::fromInclusion` instantiate the error level by adding all the submitted values via a 
bitwise `OR` operation starting at `0` meaning that no Error reporting level exists if none is added.
Conversely `ErrorLevel::fromExclusion` does the opposite, each value given will be remove from the
maximum value `E_ALL`.

Last but not least you can tell which error reporting is being configured using the `contains` method.

```php
<?php

use Bakame\Aide\Error\ErrorLevel;

ErrorLevel::fromEnvironment()->contains(E_WARNING);
// returns true if the current value in error_reporting contains `E_WARNING`
// returns false otherwise.

 $errorLevel = ErrorLevel::fromExclusion(E_NOTICE, E_DEPRECATED)->value();
// `value` returns the int value corresponding to the calculated error level.
//  the errorLevel calculated will ignore notice, and deprecated error.
```

## Credits

- [Bishop Bettini and Haldayne PHP Componentry](https://github.com/haldayne/fox)
- [ignace nyamagana butera](https://github.com/nyamsprod)
- [All Contributors](https://github.com/bakame-php/aide/graphs/contributors)
