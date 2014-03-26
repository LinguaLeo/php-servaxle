# Servaxle

Simple class locator for PHP

## How it works?

You have some classes in a project:

```php
class Foo
{
}

class Bar
{
    protected $foo;

    public function __construct(Foo $foo)
    {
        $this->foo = $foo;
    }
}
```

Servaxle uses reflection of constructors:

```php
$locator = new LinguaLeo\Servaxle\ClassLocator();

$bar = $locator->createInstance(Bar::class);
```

It's similar to:

```php
$bar = new Bar(new Foo());
```
