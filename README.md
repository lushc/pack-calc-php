## Getting started

```
composer install
```

GraphViz is needed for generating a graph visualisation on the CLI.
Note that this could take a very long time.

## CLI

```
php cli.php 500 23,31,53,151,757
```

Outputs:

```
4x 23
2x 53
2x 151
```

## Tests

```
./vendor/bin/phpunit tests --testdox
```
