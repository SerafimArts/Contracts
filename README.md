<p align="center">
    <img width="128" src="https://user-images.githubusercontent.com/2461257/160720788-267b6691-62eb-4b9a-b10d-973e7907156d.png">
</p>

<p align="center">
    <a href="https://github.com/SerafimArts/Contracts/actions"><img src="https://github.com/SerafimArts/Contracts/workflows/build/badge.svg"></a>
    <a href="https://packagist.org/packages/serafim/dbc"><img src="https://img.shields.io/badge/PHP-^8.1-ff0140.svg"></a>
    <a href="https://packagist.org/packages/serafim/dbc"><img src="https://poser.pugx.org/serafim/dbc/version" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/serafim/dbc"><img src="https://poser.pugx.org/serafim/dbc/v/unstable" alt="Latest Unstable Version"></a>
    <a href="https://packagist.org/packages/serafim/dbc"><img src="https://poser.pugx.org/serafim/dbc/downloads" alt="Total Downloads"></a>
    <a href="https://raw.githubusercontent.com/SerafimArts/Contracts/master/LICENSE.md"><img src="https://poser.pugx.org/ffi-headers/vulkan-headers/license" alt="License MIT"></a>
</p>

Contracts for PHP, is a contract programming framework and test tool 
for PHP, which uses attributes to provide run-time checking. 
(In particular, this is not a static analysis tool.)

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
    - [Invariants](#invariants)
    - [Method Contracts](#method-contracts)
- [Features](#features)

## Requirements

- PHP 8.1+

## Installation

Library is available as composer repository and can be installed using the 
following command in a root of your project.

```bash
$ composer require serafim/dbc
```

## Configuration

By default, the behavior of contracts depends on whether assertions 
(`assert.active` in php.ini configuration) are enabled on your system.

However, you can force them to enable or disable contracts:

```php
use Serafim\Contracts\Runtime;

// Enable runtime contract assertions
Runtime::enable();

// Disable runtime contract assertions
Runtime::disable();

// Enable runtime contract assertions if PHP
// assertions are enabled or disable otherwise.
Runtime::auto();
```

By default, the framework does not listen to any namespaces. To add 
a namespace for your application, use the `Runtime::listen()` method.

```php
use Serafim\Contracts\Runtime;

Runtime::listen('App\\Entity', 'App\\Http\\Controllers');
```

In addition, you can specify the directory where the cache 
files should be stored.

> Please note that these cache files are included by the PHP, therefore they 
> are cached by opcache extension and do not degrade performance.

```php
use Serafim\Contracts\Runtime;

Runtime::cache(__DIR__ . '/storage');
```

## Usage

Contracts are written as PHP code within quoted strings, embedded in 
attributes. E.g., `#[Verify('$x < 100')]` states that `$x` must be less 
than `100`. Any PHP expression, except anonymous classes, may be used, 
provided the string is properly escaped.

An annotation binds a contract to a code element: either a method or a class. 
Library defines three main annotation types, which live in the 
`Serafim\Contracts\Attribute` namespace:

- `#[Verify]` for method preconditions;
- `#[Ensure]` for method postconditions;
- `#[Invariant]` for class invariants;

Contract annotations work only (yet) with classes.

### Invariants

A class may have associated invariants. Instead of specifying a contract 
between a caller and a callee, those invariants describe the state of a 
valid object of the qualified type. Calling methods on an object may 
cause it to change; invariants guarantee that after any such change, 
the object remains in a consistent state.

Of course, internal operations are allowed to muck around and temporarily 
invalidate invariants to do their job, but they agree to eventually put 
everything back into their proper places. Intuitively, any operation made 
against this is considered internal and does not need to obey the invariants. 
Only method invocations on other variables do.

Any defined invariant in a class has access to all of its fields, 
including any protected and private.

```php
use Serafim\Contracts\Attribute\Invariant;

#[Invariant('$this->balance >= 0')]
class Account
{
    private int $balance = 0;
    
    public function deposit(int $amount): void
    {
        $this->balance += $amount + 1;
    }
}
```

When determining such an invariant, the account's balance will always be 
greater or equal than zero.

```php
$account = new Account();

$account->deposit(42);      // OK
$account->deposit(-666);    // Serafim\Contracts\Exception\InvariantException: $this->balance >= 0
```

### Method contracts

A method may have preconditions and postconditions attached to it. Together, 
they specify the contract between caller and callee: if the precondition is 
satisfied on entry of the method, then the caller may assume the postcondition 
on exit. The precondition is what the callee demands of the caller, and in 
return the caller expects the postcondition to hold after the call.

As an example, consider the following specification of the square root 
function, which states that for any non-negative double x given, sqrt will 
return a non-negative result.

```php
use Serafim\Contracts\Attribute\Verify;
use Serafim\Contracts\Attribute\Ensure;

class Math
{
    #[Verify('x >= 0')]
    #[Ensure('$result >= 0')]
    public static function sqrt(float $x): float 
    {
        // ...code
    }
}
```

As shown in this example, a precondition may access parameter values; in 
fact, preconditions and postconditions are evaluated in the context of the 
method they are bound to. More precisely, each annotation behaves as if it 
were a method, with the same arguments and in the same scope as the qualified
method. In terms of scoping, the previous code is equivalent to the following:

```php
use Serafim\Contracts\Attribute\Verify;
use Serafim\Contracts\Attribute\Ensure;

class Math
{
    public static function sqrt(float $x): float 
    {
        if ($x < 0) {
            throw new \Serafim\Contracts\Exception\PreconditionException('$x >= 0');
        }
        
        // ...code
        
        if ($result < 0) {
            throw new \Serafim\Contracts\Exception\PostconditionException('$result >= 0');
        }
        
        return $result;
    }
}
```

In addition, postconditions may contain a few extensions:

- As we have seen, they may refer to the returned value, using the `$result`
  keyword.
- Within a postcondition,`$old` is a keyword that contains the state of the 
  object before its changes.

At run time, when contracts are enabled, preconditions and postconditions
translate to checks on entry and exit, respectively, of the method. A failure
results in a `PreconditionException` or `PostconditionException` being thrown,
depending on the origin: failure to meet a precondition means that the method
was called incorrectly, whereas an unsatisfied postcondition points to a bug
in the implementation of the method itself.
  
#### Keywords

| Keyword    | May appear in | Description           |
|------------|---------------|-----------------------|
| `$old`     | `#[Ensure]`   | Value on method entry |
| `$result`  | `#[Ensure]`   | Value to be returned  |

### Features

- [x] Class Contracts
  - [ ] Abstract methods
  - [ ] Inheritance (import from parent classes, traits and interfaces)
- [x] Trait Contracts
  - [ ] Abstract methods
- [ ] Interface Contracts
