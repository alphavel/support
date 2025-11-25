# Alphavel Support

> Helper functions and collections

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.4-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## ✨ Features

- 📦 **Collections** - 40+ methods for data manipulation
- 🛠️ **Helper functions** - Utility functions
- 🎯 **Laravel-compatible** - Familiar API
- 🚀 **Fast** - Minimal overhead

## 📦 Installation

```bash
composer require alphavel/support
```

## 🚀 Quick Start

```php
use function collect;

$collection = collect([1, 2, 3, 4, 5]);

$result = collect($users)
    ->where('active', true)
    ->sortBy('name')
    ->pluck('email')
    ->unique()
    ->toArray();

// Available methods:
// map, filter, reduce, sum, avg, max, min, count, chunk,
// groupBy, sortBy, pluck, where, first, last, random, etc.
```

## 📚 Documentation

**Full documentation**: https://github.com/alphavel/documentation/blob/master/packages/support/README.md

## 📄 License

MIT License
