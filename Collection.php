<?php

namespace Alphavel\Support;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    protected array $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function all(): array
    {
        return $this->items;
    }

    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return $this->items[array_key_first($this->items)] ?? $default;
        }

        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    public function last(?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return $this->items[array_key_last($this->items)] ?? $default;
        }

        return $this->reverse()->first($callback, $default);
    }

    public function map(callable $callback): self
    {
        return new static(array_map($callback, $this->items, array_keys($this->items)));
    }

    public function filter(?callable $callback = null): self
    {
        if ($callback === null) {
            return new static(array_filter($this->items));
        }

        return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    public function where(string $key, mixed $operator, mixed $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        return $this->filter(function ($item) use ($key, $operator, $value) {
            $itemValue = is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null);

            return match ($operator) {
                '=' => $itemValue == $value,
                '===' => $itemValue === $value,
                '!=' => $itemValue != $value,
                '!==' => $itemValue !== $value,
                '>' => $itemValue > $value,
                '>=' => $itemValue >= $value,
                '<' => $itemValue < $value,
                '<=' => $itemValue <= $value,
                default => false,
            };
        });
    }

    public function whereIn(string $key, array $values): self
    {
        return $this->filter(function ($item) use ($key, $values) {
            $itemValue = is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null);

            return in_array($itemValue, $values, true);
        });
    }

    public function pluck(string $value, ?string $key = null): array
    {
        $results = [];

        foreach ($this->items as $item) {
            $itemValue = is_array($item) ? ($item[$value] ?? null) : ($item->$value ?? null);

            if ($key === null) {
                $results[] = $itemValue;
            } else {
                $itemKey = is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null);
                $results[$itemKey] = $itemValue;
            }
        }

        return $results;
    }

    public function sortBy(string|callable $callback, int $options = SORT_REGULAR, bool $descending = false): self
    {
        $results = $this->items;

        if (is_callable($callback)) {
            uasort($results, $callback);
        } else {
            uasort($results, function ($a, $b) use ($callback, $options) {
                $aValue = is_array($a) ? ($a[$callback] ?? null) : ($a->$callback ?? null);
                $bValue = is_array($b) ? ($b[$callback] ?? null) : ($b->$callback ?? null);

                return $aValue <=> $bValue;
            });
        }

        if ($descending) {
            $results = array_reverse($results, true);
        }

        return new static($results);
    }

    public function groupBy(string|callable $groupBy): self
    {
        $results = [];

        foreach ($this->items as $key => $value) {
            $groupKey = is_callable($groupBy) ? $groupBy($value, $key) : ($value[$groupBy] ?? null);

            if (! isset($results[$groupKey])) {
                $results[$groupKey] = [];
            }

            $results[$groupKey][] = $value;
        }

        return new static($results);
    }

    public function unique(?string $key = null): self
    {
        if ($key === null) {
            return new static(array_unique($this->items));
        }

        $exists = [];
        $results = [];

        foreach ($this->items as $item) {
            $value = is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null);

            if (! in_array($value, $exists, true)) {
                $exists[] = $value;
                $results[] = $item;
            }
        }

        return new static($results);
    }

    public function values(): self
    {
        return new static(array_values($this->items));
    }

    public function keys(): self
    {
        return new static(array_keys($this->items));
    }

    public function reverse(): self
    {
        return new static(array_reverse($this->items, true));
    }

    public function chunk(int $size): self
    {
        return new static(array_chunk($this->items, $size, true));
    }

    public function take(int $limit): self
    {
        return new static(array_slice($this->items, 0, $limit, true));
    }

    public function skip(int $count): self
    {
        return new static(array_slice($this->items, $count, null, true));
    }

    public function slice(int $offset, ?int $length = null): self
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    public function get(mixed $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    public function contains(mixed $key, mixed $operator = null, mixed $value = null): bool
    {
        if (func_num_args() === 1) {
            return in_array($key, $this->items, true);
        }

        return $this->where($key, $operator, $value)->isNotEmpty();
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    public function sum(string|callable|null $callback = null): mixed
    {
        if ($callback === null) {
            return array_sum($this->items);
        }

        if (is_callable($callback)) {
            return array_sum($this->map($callback)->all());
        }

        return array_sum($this->pluck($callback));
    }

    public function avg(string|callable|null $callback = null): float
    {
        $count = $this->count();

        return $count ? $this->sum($callback) / $count : 0;
    }

    public function max(string|callable|null $callback = null): mixed
    {
        if ($callback === null) {
            return max($this->items);
        }

        if (is_callable($callback)) {
            return max($this->map($callback)->all());
        }

        return max($this->pluck($callback));
    }

    public function min(string|callable|null $callback = null): mixed
    {
        if ($callback === null) {
            return min($this->items);
        }

        if (is_callable($callback)) {
            return min($this->map($callback)->all());
        }

        return min($this->pluck($callback));
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $value) {
            if ($callback($value, $key) === false) {
                break;
            }
        }

        return $this;
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->items, $options);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function jsonSerialize(): array
    {
        return $this->items;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    public function __toString(): string
    {
        return $this->toJson();
    }

    public static function make(array $items = []): static
    {
        return new static($items);
    }
}
