# Aide for Enums

A collection of traits and classes to improve handling PHP's Enum.

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

### Traits

#### Factory

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

#### Info

Gather information regarding the current Enum via **public static methods**. This trait enables getting:

- the number of cases via the `size` method;
- the type of enum via the `isBacked` and `isPure` method;
- the names of each cases with the `names` method;
- the possible values for the Enum with the `values` method;
- the `nameOf` which returns the name associated with a specific `value`

```php
<?php

HttpMethod::size();        //returns the number of cases
HttpMethod::isBacked();
HttpMethod::isPure();      // returns the inverse of the `isBacked` method
HttpMethod::names();       // returns a list of all the names in the enumeration
HttpMethod::values();      // returns a list of all the names in the enumeration
HttpMethod::nameOf(404);   // returns the name associated with the given value
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

#### Hasser/Isser methods

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

#### Comparison

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

#### Convert

The `Convert` trait adds three (3) methods to convert Enums instances.
The `toAssociative` converts the Enum instance into an associative 
array whereas the `toJavaScriptObject` and `toJavaScriptClass` methods
convert the Enum into a JavaScript equivalent structure.

```php
<?php

HttpMethod::toAssociative(); // returns tha associative array
HttpMethod::toJavaScriptObject();  // returns a JavaScript object equivalent code as string
HttpMethod::toJavaScriptClass();  // returns a JavaScript class equivalent code as string
```

You need the `Bakame\Aide\Enum\Convert` trait to expose the new API.

```php
<?php

use Bakame\Aide\Enum\Convert;

enum HttpMethod: string
{
    use Convert;

    case Get = 'GET';
    case Post = 'POST';
    case Put = 'PUT';
    case Head = 'HEAD';
    case Options = 'OPTIONS'
}
```

#### All in one

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

### Converting the Enum into a JavaScript structure

While the `Convert::toJavaScriptObject` and `Convert::toJavaScriptClass` methods are enough
to convert your Enum to JavaScript code, behind the scene the method makes use of the
`JavaScriptConverter` class. The class enables returning a more fine-tuned representation
that suite your constraints better.

Because there are two (2) ways to create an Enum like structure in JavaScript, the class provides
two (2) methods to allow the conversion. 

In both cases, the conversion is configurable via wither methods to control the formatting and the
JavaScript structure properties. 

#### Backed Enum

For instance, given I have the following enum:

```php
enum HttpStatusCode: int
{
    case HTTP_OK = 200;
    case HTTP_REDIRECTION = 302;
    case HTTP_NOT_FOUND = 404;
    case HTTP_SERVER_ERROR = 500;
}
```

It can be converted into an object using the `convertToObject` method:

```php
use Bakame\Aide\Enum\JavaScriptConverter;

echo JavaScriptConverter::new()->convertToObject(HttpStatusCode::class);
```

will produce the following JavaScript code snippet:

```javascript
const HttpStatusCode = Object.freeze({
  HTTP_OK: 200,
  HTTP_REDIRECTION: 302,
  HTTP_NOT_FOUND: 404,
  HTTP_SERVER_ERROR: 500
})
```

conversely using `convertToClass` as follows:

```php
echo JavaScriptConverter::new()->convertToClass(HttpStatusCode::class);
```

will produce the following JavaScript code snippet:

```javascript
class HttpStatusCode {
  static HTTP_OK = new HttpStatusCode(200)
  static HTTP_REDIRECTION = new HttpStatusCode(302)
  static HTTP_NOT_FOUND = new HttpStatusCode(404)
  static HTTP_SERVER_ERROR = new HttpStatusCode(500)
    
  constructor(name) {
    this.name = name
  }
}
```

Of course there are ways to improve the output depending on your use case you can

- ignore or use object immutability;
- ignore or use JavaScript `export` or `export default`;
- change the class name or add and/or change the object variable name;
- use `Symbol` when declaring the object property value;
- define indentation spaces and thus end of line;
- define the presence or absence of a trailing comma in the object representation;

Here's a more advance usage of the converter to highlight how you can configure it.

```php
<?php
use Bakame\Aide\Enum\JavaScriptConverter;
use Illuminate\Support\Str;

$converter = JavaScriptConverter::new()
    ->useImmutability()
    ->useExportDefault()
    ->useTrailingComma()
    ->useSymbol()
    ->indentSize(4)
    ->propertyNameCase(
        fn (string $name) => Str::of($name)->replace('HTTP_', '')->lower()->studly()->toString()
    );

echo $converter->convertToObject(HttpStatusCode::class, 'StatusCode');
```

will return the following JavaScript code:

```javascript
const StatusCode = Object.freeze({
    Ok: Symbol(200),
    Redirection: Symbol(302),
    NotFound: Symbol(404),
    ServerError: Symbol(500),
});
export default StatusCode;
```

#### Pure Enum

For Pure PHP Enum, the converter will assign a unique `Symbol` value for each case, starting
wih the `Symbol(0)` and following the PHP order of case declaration. you can optionally
configure the start value using the `startAt` method.

Let's take the following PHP Pure Enum:

```php
enum Color
{
    case Red;
    case Blue;
    case Green;
}
```

It can be converted into an object using the `convertToObject` method:

```php
use Bakame\Aide\Enum\JavaScriptConverter;

echo JavaScriptConverter::new()->convertToObject(Color::class);
```

will produce the following JavaScript code snippet:

```javascript
const Color = Object.freeze({
  Red: Symbol(0),
  Blue: Symbol(1),
  Green: Symbol(2)
})
```

If you set up the starting value to increment you will get a different value:

```php
use Bakame\Aide\Enum\JavaScriptConverter;

echo JavaScriptConverter::new()
    ->ignoreSymbol()
    ->valueStartAt(2)
    ->convertToClass(Color::class, 'Colour');
```

Then the start at value will be taken into account as shown below:

```javascript
class Colour {
  static Red = new Colour(Symbol(2))
  static Blue = new Colour(Symbol(3))
  static Green = new Colour(Symbol(4))

  constructor(name) {
    this.name = name
  }
}
```

> [!CAUTION]  
>  For Pure Enum the `ignoreSymbol` and `useSymbol` methods have no effect on the output.

#### Storing the output

The converter will not store the resulting string into a Javascriot file as this part is
left to the discretion of the implementor. There are several ways to do so:

- using vanilla PHP with `file_put_contents` or `SplFileObject`
- using more robust and battle tested packages you can find on packagist for instance.

## Credits

- [ignace nyamagana butera](https://github.com/nyamsprod)
- [All Contributors](https://github.com/bakame-php/aide/graphs/contributors)
