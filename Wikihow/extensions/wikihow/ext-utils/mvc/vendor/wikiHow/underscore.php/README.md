Underscore.php
==============
Underscore.php is a PHP port of [Underscore.js](http://documentcloud.github.com/underscore/).

In addition to porting Underscore's functionality, Underscore.php includes matching unit tests. Thanks to Jeremy Ashkenas and all contributors to Underscore.js.

## Table of Contents

[Object-Oriented and Static Styles](#styles)

### Collections
[each](#each), [map](#map), [reduce](#reduce), [reduceRight](#reduceRight), [find](#find), [filter](#filter), [reject](#reject), [all](#all), [any](#any), [includ](#includ), [invoke](#invoke), [pluck](#pluck), [max](#max), [min](#min), [groupBy](#groupBy), [sortBy](#sortBy), [sortedIndex](#sortedIndex), [shuffle](#shuffle), [toArray](#toArray), [size](#size)

### Arrays
[first](#first), [initial](#initial), [rest](#rest), [last](#last), [compact](#compact), [flatten](#flatten), [without](#without), [uniq](#uniq), [union](#union), [intersection](#intersection), [difference](#difference), [zip](#zip), [indexOf](#indexOf), [lastIndexOf](#lastIndexOf), [range](#range)

### Functions
~~bind~~, ~~bindAll~~ [memoize](#memoize), ~~delay~~, ~~defer~~, [throttle](#throttle), ~~debounce~~, [once](#once), [after](#after), [wrap](#wrap), [compose](#compose)

### Objects
[keys](#keys), [values](#values), [functions](#functions), [extend](#extend), [defaults](#defaults), [clon](#clon), [tap](#tap), [has](#has), [isEqual](#isEqual), [isEmpty](#isEmpty), ~~isElement~~, [isObject](#isObject), [isArray](#isArray), ~~isArguments~~, [isFunction](#isFunction), [isString](#isString), [isNumber](#isNumber), [isBoolean](#isBoolean), [isDate](#isDate), ~~isRegExp~~, [isNaN](#isNaN), [isNull](#isNull), ~~isUndefined~~

### Utility
~~noConflict~~, [identity](#identity), [times](#times), [mixin](#mixin), [uniqueId](#uniqueId), [escape](#escape), [template](#template)

### Chaining
[chain](#chain), [value](#value)

> Some functions were not ported from Underscore.js to Underscore.php for technical reasons or because they weren't applicable to PHP. They have been marked with ~~strikethrough~~

## Single Underscore vs. Double Underscore
In many PHP installations, `_()` already exists as an alias to `gettext()`. The previous declaration of `_` in PHP forces Underscore.php to use another name. For consistency and memorability, `__` has been chosen for both the function and class name.

## Object-Oriented and Static Styles
Underscore.php works in both object-oriented and static styles. The following lines are identical ways to double a list of numbers.

```php
__::map([1, 2, 3], function($n) { return $n * 2; });
__([1, 2, 3])->map(function($n) { return $n * 2; });
```

## Collections (Arrays or Objects)
###each
`__::each(collection, iterator)`

Iterates over the collection and yield each in turn to the iterator function. Arguments passed to iterator are (value, key, collection). Unlike Underscore.js, context is passed using PHP's `use` statement. Underscore.php does not contain the forEach alias because 'foreach' is a reserved keyword in PHP.

```php
__::each(array(1, 2, 3), function($num) { echo $num . ','; });
// 1,2,3,
$multiplier = 2;
__::each([1, 2, 3], function($num, $index) use ($multiplier) {
  echo $index . '=' . ($num * $multiplier) . ',';
});
// 0=2,1=4,2=6,
```

### map
_Alias: [collect](#collect)_

```php
__::map(collection, iterator)
```

Returns an array of values by mapping each in collection through the iterator. Arguments passed to iterator are (value, key, collection). Unlike Underscore.js, context is passed using PHP's `use` statement.

```php
__::map([1, 2, 3], function($num) { return $num * 3; });
// [3, 6, 9]
__::map(['one'=>1, 'two'=>2, 'three'=>3], function($num, $key) {
  return $num * 3;
});
// [3, 6, 9];
```

### reduce
 _Aliases: [inject](#inject), [foldl](#foldl)_

```php
__::reduce(collection, iterator, memo)
```

Reduce the collection into a single value. Memo is the initial state of the reduction, updated by the return value of the iterator. Unlike Underscore.js, context is passed using PHP's `use` statement.

```php
__::reduce([1, 2, 3], function($memo, $num) { return $memo + $num; }, 0);
// 6
```

### reduceRight
_Alias: [foldl](#foldr)_

```php
__::reduceRight(collection, iterator, memo)
```

Right-associative version of reduce.

```php
$list = [[0, 1], [2, 3], [4, 5]];
$flat = __::reduceRight($list, function($a, $b) { return array_merge($a, $b); }, []);
// [4, 5, 2, 3, 0, 1]
```

### find
_Alias: [detect](#detect)_

```php
__::find(collection, iterator)
```

Return the value of the first item in the collection that passes the truth test (**iterator**).

```php
__::find([1, 2, 3, 4], function($num) { return $num % 2 === 0; });
// 2
```

### filter
_Alias: [select](#select)_

```php
__::filter(collection, iterator)
```

Return the values in the collection that pass the truth test (**iterator**).

```php
__::filter([1, 2, 3, 4], function($num) { return $num % 2 === 0; });
// [2, 4]
```

### reject
```php
__::reject(collection, iterator)
```

Return an array where the items failing the truth test (**iterator**) are removed.

```php
__::reject([1, 2, 3, 4], function($num) { return $num % 2 === 0; });
// [1, 3]
```

### all

```php
__::all(collection, iterator)
```

Returns true if all values in the collection pass the truth test (**iterator**).

```php
__::all([1, 2, 3, 4], function($num) { return $num % 2 === 0; });
// false
__::all([1, 2, 3, 4], function($num) { return $num < 5; });
// true
```

### any

```php
__::any(collection, iterator)
```

Returns true if any values in the collection pass the truth test (**iterator**).

```php
__::any([1, 2, 3, 4], function($num) { return $num % 2 === 0; });
// true
__::any([1, 2, 3, 4], function($num) { return $num === 5; });
// false
```

### includ
_Alias: [contians](#contains)_

```php
__::includ(collection, value)
```

Returns true if value is found in the collection using === to test equality. This function is called 'include' in Underscore.js, but was renamed to 'includ' in Underscore.php because 'include' is a reserved keyword in PHP.

```php
__::includ([1, 2, 3], 3);
// true
```

### invoke

```php
__::invoke(collection, functionName)
```

Returns a copy of the collection after running functionName across all elements.

```php
__::invoke([' foo', ' bar '], 'trim');
// ['foo', 'bar']
```

### pluck

```php
__::pluck(collection, propertyName)
```

Extract an array of property values

```php
$stooges = [
  ['name'=>'moe', 'age'=>40],
  ['name'=>'larry', 'age'=>50],
  ['name'=>'curly', 'age'=>60]
];
__::pluck($stooges, 'name');
// ['moe', 'larry', 'curly']
```

### max
`__::max(collection, [iterator])`

Returns the maximum value from the collection. If passed an iterator, max will return max value returned by the iterator. Unlike Underscore.js, context is passed using PHP's `use` statement.

```php
$stooges = [
  ['name'=>'moe', 'age'=>40],
  ['name'=>'larry', 'age'=>50],
  ['name'=>'curly', 'age'=>60]
];
__::max($stooges, function($stooge) { return $stooge['age']; });
// ['name'=>'curly', 'age'=>60]
```

### min

```php
__::min(collection, [iterator])
```

Returns the minimum value from the collection. If passed an iterator, min will return min value returned by the iterator. Unlike Underscore.js, context is passed using PHP's `use` statement.

```php
$stooges = array(
  array('name'=>'moe', 'age'=>40),
  array('name'=>'larry', 'age'=>50),
  array('name'=>'curly', 'age'=>60)
);
__::min($stooges, function($stooge) { return $stooge['age']; });
// array('name'=>'moe', 'age'=>40)```

### groupBy

```php
__::groupBy(collection, iterator)
```

Group values by their return value when passed through the iterator. If iterator is a string, the result will be grouped by that property.

```php
__::groupBy(array(1, 2, 3, 4, 5), function($n) { return $n % 2; });
// array(0=>array(2, 4), 1=>array(1, 3, 5))
$values = array(
  ['name'=>'Apple',   'grp'=>'a'),
  ['name'=>'Bacon',   'grp'=>'b'),
  []'name'=>'Avocado', 'grp'=>'a')
);
__::groupBy($values, 'grp');
//array(
//  'a'=>array(
//    array('name'=>'Apple',   'grp'=>'a'),
//    array('name'=>'Avocado', 'grp'=>'a')
//  ),
//  'b'=>array(
//    array('name'=>'Bacon',   'grp'=>'b')
//  )
//);
```

### sortBy

```php
__::sortBy(collection, iterator)
```

Returns an array sorted in ascending order based on the iterator results. If passed an iterator, min will return min value returned by the iterator. Unlike Underscore.js, context is passed using PHP's `use` statement.

```php
__::sortBy([1, 2, 3], function($n) { return -$n;});
// [3, 2, 1]
```

### sortedIndex

```php
__::sortedIndex(collection, value, [iterator])
```

Returns the index at which the value should be inserted into the sorted collection.

```php
__::sortedIndex(array(10, 20, 30, 40), 35); // 3
```

### shuffle

```php
__::shuffle(collection)
```

Returns a shuffled copy of the collection.

```php
__::shuffle(array(10, 20, 30, 40)); // 30, 20, 40, 10
```

### toArray

```php
__::toArray(collection)
```

Converts the collection into an array.

```php
$stooge = new StdClass;
$stooge->name = 'moe';
$stooge->age = 40;
__::toArray($stooge);
// array('name'=>'moe', 'age'=>40)
```

### size

```php
__::size(collection)
```

Returns the number of values in the collection.

```php
$stooge = new StdClass;
$stooge->name = 'moe';
$stooge->age = 40;
__::size($stooge); // 2
```

## Arrays

### first
_Alias: [head](#head)_

```php
__::first(array, [n])
```

Get the first element of an array. Passing n returns the first n elements.

```php
__::first(array(5, 4, 3, 2, 1)); // 5
__::first(array(5, 4, 3, 2, 1), 3); // array(5, 4, 3)
```

### initial

```php
__::initial(array, [n])
```

Get everything but the last array element. Passing n excludes the last n elements.

```php
__::initial(array(5, 4, 3, 2, 1)); // array(5, 4, 3, 2)
__::initial(array(5, 4, 3, 2, 1), 3); // array(5, 4)
```

### rest
_Alias: [tail](#tail)_

```php
__::rest(array, [index])
```

Get the rest of the array elements. Passing an index returns from that index onward.

```php
__::rest(array(5, 4, 3, 2, 1)); // array(4, 3, 2, 1)
```

### last

```php
__::last(array, [n])
```

Get the last element of an array. Passing n returns the last n elements.

```php
__::last(array(5, 4, 3, 2, 1)); // 1
__::last(array(5, 4, 3, 2, 1), 2); // array(2, 1)
```

### compact

```php
__::compact(array)
```

Returns a copy of the array with falsy values removed

```php
__::compact(array(false, true, 'a', 0, 1, '')); // array(true, 'a', 1)
```

### flatten

```php
__::flatten(array, [shallow])
```

Flattens a multidimensional array. If you pass shallow, the array will only be flattened a single level.

```php
__::flatten(array(1, array(2), array(3, array(array(array(4))))));
// array(1, 2, 3, 4)
__::flatten(array(1, array(2), array(3, array(array(array(4))))), true);
// array(1, 2, 3, array(array(4)))
```

### without

```php
__::without(array, [*values])
```

Returns a copy of the array with all instances of **values** removed. === is used for equality testing. Keys are maintained.

```php
__::without(array(5, 4, 3, 2, 1), 3, 2); // array(5, 4, 4=>1)
```

### uniq
_Alias: [unique](#unique)_

```php
__::uniq(array, [isSorted [iterator]])
```

Returns a copy of the array containing no duplicate values. Unlike Underscore.js, passing isSorted does not currently affect the performance of `uniq`. You can optionally compute uniqueness by passing an iterator function.

```php
__::uniq(array(2, 2, 4, 4, 4, 1, 1, 1)); // array(2, 4, 1)
```

### union

```php
__::union(*arrays)
```

Returns an array containing the unique items in one or more of the arrays.

```php
$arr1 = array(1, 2, 3);
$arr2 = array(101, 2, 1, 10);
$arr3 = array(2, 1);
__::union($arr1, $arr2, $arr3); // array(1, 2, 3, 101, 10)
```

### intersection

```php
__::intersection(*arrays)
```

Returns an array containing the intersection of all the arrays. Each value in the resulting array exists in all arrays.

```php
$arr1 = array(0, 1, 2, 3);
$arr2 = array(1, 2, 3, 4);
$arr3 = array(2, 3, 4, 5);
__::intersection($arr1, $arr2, $arr3);
// array(2, 3)
```

### difference

```php
__::difference(array, *others)
```

Returns an array containing the items existing in one array, but not the other.

```php
__::difference(array(1, 2, 3, 4, 5), array(5, 2, 10));
// array(1, 3, 4)
```

### zip

```php
__::zip(*arrays)
```

Merges arrays

```php
$names = array('moe', 'larry', 'curly');
$ages = array(30, 40, 50);
$leaders = array(true, false, false);
__::zip($names, $ages, $leaders);
// array(
//   array('moe', 30, true),
//   array('larry', 40, false),
//   array('curly', 50, false)
// )
```

### indexOf

```php
__::indexOf(array, value)
```

Returns the index of the first match. Returns -1 if no match is found. Unlike Underscore.js, Underscore.php does not take a second isSorted parameter.

```php
__::indexOf(array(1, 2, 3, 2, 2), 2);
// 1
```

### lastIndexOf

```php
__::lastIndexOf(array, value)
```

Returns the index of the last match. Returns -1 if no match is found.

```php
__::lastIndexOf(array(1, 2, 3, 2, 2), 2);
// 4
```

### range

```php
__::range([start], stop, [step])
```

Returns an array of integers from **start** to **stop** (exclusive) by **step**. Defaults: **start**=0, **step**=1.

```php
__::range(10);         // array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9)
__::range(1, 5);       // array(1, 2, 3, 4)
__::range(0, 30, 5);   // array(0, 5, 10, 15, 20, 25)
__::range(0, -5, -1);  // array(0, -1, -2, -3, -4)
__::range(0);          // array()
```

## Functions

### memoize
`__::memoize(function, [hashFunction])`

Memoizes a function by caching the computed result. Useful for computationally expensive functions. Optionally, pass a hashFunction to calculate the key for the cached value.

```php
$fibonacci = function($n) use (&$fibonacci) {
  return $n < 2 ? $n : $fibonacci($n - 1) + $fibonacci($n - 2);
};
$fastFibonacci = __::memoize($fibonacci);
```

### throttle

```php
__::throttle(function, wait)
```

Throttles a function so that it can only be called once every **wait** milliseconds.

```php
$func = function() { return 'x'; }
__::throttle($func);
```

## once

```php
__::once(function)
```

Creates a version of the function that can only be called once.

```php
$num = 0;
$increment = __::once(function() use (&$num) { return $num++; });
$increment();
$increment();
echo $num;
// 1
```

## after

```php
__::after(count, function)
```

Creates a version of the function that will only run after being called **count** times.

```php
$func = __::after(3, function() { return 'x'; });
$func(); //
$func(); //
$func();
// 'x'
```

### wrap

```php
__::wrap(function, wrapper)
```

Wraps the **function** inside the **wrapper** function, passing it as the first argument. Lets the **wrapper** execute code before and/or after the **function** runs.

```php
$hello = function($name) { return 'hello: ' . $name; };
$hi = __::wrap($hello, function($func) {
  return 'before, ' . $func('moe') . ', after';
});
$hi();
// 'before, hello: moe, after'
```

### compose

```php
__::compose(*functions)
```

Returns the composition of the **functions**, where the return value is passed to the following function.

```php
$greet = function($name) { return 'hi: ' . $name; };
$exclaim = function($statement) { return $statement . '!'; };
$welcome = __::compose($exclaim, $greet);
$welcome('moe');
// 'hi: moe!'
```

## Objects

### keys

```php
__::keys(object)
```

Get the keys

```php
__::keys((object) array('name'=>'moe', 'age'=>40));
// array('name', 'age')
```

### values

```php
__::values(object)
```

Get the values

```php
__::values((object) array('name'=>'moe', 'age'=>40));
// array('moe', 40)
```

### functions
_Alias: [methods](#methods)_

```php
__::functions(object)
```

Get the names of functions available to the object

```php
class Stooge {
  public function getName() { return 'moe'; }
  public function getAge() { return 40; }
}
$stooge = new Stooge;
__::functions($stooge);
// array('getName', 'getAge')
```

### extend

```php
__::extend(destination, *sources)
```

Copy all properties from the **source** objects into the **destination** object. Copying happens in order, so rightmost sources have override power.

```php
__::extend((object) array('name'=>'moe'), (object) array('age'=>50));
// (object) array('name'=>'moe', 'age'=>50)```

### defaults

```php
__::defaults(object, *defaults)
```

Returns the object with any missing values filled in using the defaults. Once a default is applied for a given property, it will not be overridden by following defaults.

```php
$food = (object) array('dairy'=>'cheese');
$defaults = (object) array('meat'=>'bacon');
__::defaults($food, $defaults);
// (object) array('dairy'=>'cheese', 'meat'=>'bacon');
```

### clon

```php
__::clon(object)
```

Returns a shallow copy of the object. This function is called 'clone' in Underscore.js, but was renamed to 'clon' in Underscore.php because 'clone' is a reserved keyword in PHP.

```php
$stooge = (object) array('name'=>'moe');
__::clon($stooge); // (object) array('name'=>'moe');
```

## tap

```php
__::tap(object, interceptor)
```

Invokes the **interceptor** on the **object**, then returns the object. Useful for performing intermediary operations on the object.

```php
$interceptor = function($obj) { return $obj * 2; };
__::chain(array(1, 2, 3))->max()
                         ->tap($interceptor)
                         ->value();
// 6
```

### has

```php
__::has(object, key)
```

Does the object have this key?

```php
__::has((object) array('a'=>1, 'b'=>2, 'c'=>3), 'b');
// true
```

### isEqual

```php
__::isEqual(object, other)
```

Are these items equal? Uses === equality testing. Objects tested using values.

```php
$stooge = (object) array('name'=>'moe');
$clon = __::clon($stooge);
$stooge === $clon; // false
__::isEqual($stooge, $clon);
// true
```

### isEmpty

```php
__::isEmpty(object)
```

Returns true if the **object** contains no values.

```php
$stooge = (object) array('name'=>'moe');
__::isEmpty($stooge);
// false
__::isEmpty(new StdClass);
// true
__::isEmpty((object) array());
// true
```

### isObject

```php
__::isObject(object)
```

Returns true if passed an object.

```php
__::isObject((object) array(1, 2));
// true
__::isObject(new StdClass);
// true
```

### isArray

```php
__::isArray(object)
```

Returns true if passed an array.

```php
__::isArray(array(1, 2));
// true
__::isArray((object) array(1, 2));
// false
```

### isFunction

```php
__::isFunction(object)
```

Returns true if passed a function.

```php
__::isFunction(function() {});
// true
__::isFunction('trim');
// false
```

### isString

```php
__::isString(object)
```

Returns true if passed a string.

```php
__::isString('moe');
// true
__::isString('');
// true
```

### isNumber

```php
__::isNumber(object)
```

Returns true if passed a number.

```php
__::isNumber(1); // true
__::isNumber(2.5); // true
__::isNumber('5'); // false
```

### isBoolean

```php
__::isBoolean(object)
```

Returns true if passed a boolean.

```php
__::isBoolean(null); // false
__::isBoolean(true); // true
__::isBoolean(0); // false
```

### isDate

```php
__::isDate(object)
```

Returns true if passed a DateTime object

```php
__::isDate(null); // false
__::isDate('2011-06-09 01:02:03'); // false
__::isDate(new DateTime); // true
```

### isNaN

```php
__::isNaN(object)
```

Returns true if value is NaN

```php
__::isNaN(null); // false
__::isNaN(acos(8)); // true
```

### isNull

```php
__::isNull(object)
```

Returns true if value is null

```php
__::isNull(null); // true
__::isNull(false); // false
```

## Utility

### identity

```php
__::identity(value)
```

Returns the same value passed as the argument

```php
$moe = array('name'=>'moe');
$moe === __::identity($moe); // true
```

### times

```php
__::times(n, iterator)
```

Invokes the **iterator** function **n** times.

```php
__::times(3, function() { echo 'a'; }); // 'aaa'
```

### mixin

```php
__::mixin(array)
```

Extend Underscore.php with your own functions.

```php
__::mixin(array(
  'capitalize'=> function($string) { return ucwords($string); },
  'yell'      => function($string) { return strtoupper($string); }
));
__::capitalize('moe'); // 'Moe'
__::yell('moe');       // 'MOE'
```

### uniqueId

```php
__::uniqueId([prefix])
```

Generate a globally unique id.

```php
__::uniqueId(); // 0
__::uniqueId('stooge_'); // 'stooge_1'
__::uniqueId(); // 2
```

### escape

```php
__::escape(html)
```

Escapes the string.

```php
__::escape('Curly, Larry & Moe'); // 'Curly, Larry &amp; Moe'
```

### template

```php
__::template(templateString, [context])
```

Compile templates into functions that can be evaluated for rendering. Templates can interpolate variables and execute arbitrary PHP code.

```php
$compiled = __::template('hello: <%= $name %>');
$compiled(array('name'=>'moe'));
// 'hello: moe'
$list = '<% __::each($people, function($name) { %> <li><%= $name %></li> <% }); %>';
__::template($list, array('people'=>array('moe', 'curly', 'larry')));
// '<li>moe</li><li>curly</li><li>larry</li>'
```

### Single vs. double quotes

Note: if your template strings include variables, wrap your template strings in single quotes, not double quotes. Wrapping in double quotes will cause your variables to be interpolated prior to entering the template function.

```php
// Correct
$compiled = __::template('hello: <%= $name %>');
// Incorrect
$compiled = __::template("hello: <%= $name %>");
```

### Custom delimiters

You can set custom delimiters (for instance, Mustache style) by calling `__::templateSettings()` and passing interpolate and/or evaluate values:

```php
// Mustache style
__::templateSettings(array(
  'interpolate' => '/\{\{(.+?)\}\}/'
));
$mustache = __::template('Hello {{$planet}}!');
$mustache(array('planet'=>'World')); // "Hello World!"
```

## Chaining

### chain
Returns a wrapped object. Methods will return the object until you call `value()`

```php
__::chain(item);
```

```php
// filter and reverse the numbers
$numbers = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
$result = __::chain($numbers)->select(function($n) { return $n < 5; })
                             ->reject(function($n) { return $n === 3; })
                             ->sortBy(function($n) { return -$n; })
                             ->value();
// [4, 2, 1]
```

### value

```php
__(obj)->value()
```

Extracts the value of a wrapped object.

```php
__(array(1, 2, 3))->value();
// array(1, 2, 3)
```
