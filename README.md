# Servaxle

Servaxle is a fast Dependency Injection Container for PHP.

## How it use?

Dependency Injection Container is presented as a scope. The scope discovers dependencies by reflection.
Classes in namespaces will be resolved only.

```php
namespace Tutorial;

class Foo
{
}

class Bar
{
    public function __construct(Foo $foo)
    {
    }
}

$scope = new \LinguaLeo\DI\Scope([
    'bar' => Bar::class
]);

$scope->bar; // instance of Tutorial\Bar class
```

You can define common interface implementation.

```php
namespace Tutorial;

interface AnimalInterface {}

class Dog implements AnimalInterface {}

class Cat implements AnimalInterface {}

class Home
{
    public function __construct(AnimalInterface $animal)
    {
    }
}

$scope = new \LinguaLeo\DI\Scope([
    'home' => Home::class,
    AnimalInterface::class => Dog::class
]);

$scope->home; // instanse of Tutorial\Home class with Tutorial\Dog object injection
```

But you can put simple data types into the scope too.

```php
$scope = new LinguaLeo\DI\Scope([
    'user' => 'root'
]);

$scope->getValue('user'); // "root"
```

You can define a constant also. Constants in a class namespace will be processed too.

```php
$scope = new LinguaLeo\DI\Scope([
    'max' => 'PHP_INT_MAX'
]);

$scope->max; // 9223372036854775807 (in 64 bit OS)
```

## Symlinks

You can define a symlink to another variable.

```php
$scope = new LinguaLeo\DI\Scope([
    'super' => 'admin',
    '@user' => 'super',
    'member' => '@user'
]);

$scope->getValue('member'); // "admin" as @user -> super -> admin
```

## Variables

You can define a target variable without symlinks lookup.

```php
$scope = new LinguaLeo\DI\Scope([
    'super' => 'admin',
    'member' => '$super'
]);

$scope->getValue('member'); // "admin"
```

## Cache

The scope parses dependencies every time when you call `getValue` method. But if you access to a value as a property the scope will cache it.

```php
$scope = new LinguaLeo\DI\Scope([
    'now' => function () {
        return uniqid();
    }
]);

$scope->getValue('now') === $scope->getValue('now'); // false
$scope->now === $scope->now; // true
```

## Factory Design Pattern

You can create own factories for custom init. No special interfaces or classes are required. Just use a magic!

```php
namespace Tutorial;

class RedisFactory
{
    private $host;

    public function __construct($host)
    {
        $this->host = $host;
    }

    public function __invoke()
    {
        $redis = new \Redis();
        $redis->connect($this->host);
        return $redis;
    }
}

$scope = new \LinguaLeo\DI\Scope([
    'redis' => RedisFactory::class,
    'redis.host' => '192.168.1.1'
]);

$scope->redis; // instance of \Redis class
```

## Compilation

For complex tree of dependencies we created the compilation into PHP script.

```php
namespace Tutorial;

// define classes
class Foo {}

class Bar
{
    public function __construct(Foo $foo) {}
}

class Baz
{
    public function __construct(Bar $bar) {}
}

// compiles a tree
$script = \LinguaLeo\DI\Scope::compile([
    'baz' => Baz::class
]);

// put into PHP file
file_put_contents('cache.php', '<?php return '.$script.';');

// init a scope from file
$scope = new \LinguaLeo\DI\Scope(include 'cache.php');

$scope->baz; // instance of Tutorial\Baz class
```

## Immutable scope

You should instantiate `ImmutableScope` class if you don't require auto reflection. It's useful for early compiled dependencies.

```php
$scope = new LinguaLeo\DI\ImmutableScope(include 'cache.php');
```

Source: https://github.com/LinguaLeo/php-servaxle
