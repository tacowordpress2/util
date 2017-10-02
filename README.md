# Taco Util

Utility methods for Tacos

This is required when using [Taco](https://github.com/tacowordpress/tacowordpress). Add it to `composer.json`:

```json
{
  "require": {
    "tacowordpress/tacowordpress": "dev-master",
    "tacowordpress/util": "dev-master"
  }
}
```


## Usage of the View class

In `config.php`, specify any number of directories containing view files. You may have theme-specific views, as well as views included in the boilerplate for common elements. Specify the directories in the order in which they should be checked for view files.

```php
\Taco\Util\View::setDirectories([
  __DIR__.'/../views/', // Theme-specific views directory
  __DIR__.'/views/',    // Fallback views directory
]);
```

To render the view, call the `make()` method, specifying the path to the view file, along with any required parameters.

```php
echo \Taco\Util\View::make('article/article-list', [
  'articles' => Article::getRecent(),
  'header' => 'Recent articles',
]);
```

The View class will look in the directories you specified in `config.php`, starting with the first. If no view file is found, it will check the next one, and so on.

## Changelog

### v1.0.1
Fixing static function calls in Str::convert which was causing PHP 7 errors

### v1.0
First version ported from tacowordpress
