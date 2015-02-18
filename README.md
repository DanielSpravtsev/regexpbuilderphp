```asciidoc
 ____            _____            ____        _ _     _           ____  _   _ ____  
|  _ \ ___  __ _| ____|_  ___ __ | __ ) _   _(_) | __| | ___ _ __|  _ \| | | |  _ \ 
| |_) / _ \/ _` |  _| \ \/ / '_ \|  _ \| | | | | |/ _` |/ _ \ '__| |_) | |_| | |_) |
|  _ <  __/ (_| | |___ >  <| |_) | |_) | |_| | | | (_| |  __/ |  |  __/|  _  |  __/ 
|_| \_\___|\__, |_____/_/\_\ .__/|____/ \__,_|_|_|\__,_|\___|_|  |_|   |_| |_|_|    
           |___/           |_|                                                      
```
## human-readable regular expressions for PHP 5.3+
[![Travis](https://img.shields.io/travis/gherkins/regexpbuilderphp.svg?style=flat-square)](https://travis-ci.org/gherkins/regexpbuilderphp)
[![Coveralls](https://img.shields.io/coveralls/gherkins/regexpbuilderphp.svg?style=flat-square)](https://coveralls.io/r/gherkins/regexpbuilderphp?branch=master)
[![SensioLabs Insight](https://img.shields.io/sensiolabs/i/555ad19b-0c18-4434-ad43-5b19779e2e9c.svg?style=flat-square)](https://insight.sensiolabs.com/projects/555ad19b-0c18-4434-ad43-5b19779e2e9c)

PHP port of https://github.com/thebinarysearchtree/regexpbuilderjs

> RegExpBuilder integrates regular expressions into the programming language, thereby making them easy to read and maintain. Regular Expressions are created by using chained methods and variables such as arrays or strings.

Installation
----

```text
composer require gherkins/regexpbuilderphp:dev-master
```


Documentation
---

https://github.com/gherkins/regexpbuilderphp/wiki


Usage example
----

```php
$builder = new \Gherkins\RegExpBuilderPHP\RegExpBuilder();

$regExp = $builder
    ->startOfInput()
    ->exactly(4)->digits()
    ->then("_")
    ->exactly(2)->digits()
    ->then("_")
    ->min(3)->max(10)->letters()
    ->then(".")
    ->eitherFind("png")->orFind("jpg")->orFind("gif")
    ->endOfInput()
    ->getRegExp();

//true
$regExp->test("2020_10_doge.jpg");
$regExp->test("2030_11_octocat.png");
$regExp->test("4000_99_cats.gif");

//false
$regExp->test("4000_99_f.gif");
$regExp->test("4000_09_abcdef.pdf");
$regExp->test("2015_05_thisnameistoolong.jpg");
$regExp->test("2015_05_doge.jpeg");
$regExp->test("202301_cat.png");
$regExp->test("2023001_cats.jpeg");

```
        
Take a look at the [tests](tests/RegExpBuilderTest.php) for more examples
    