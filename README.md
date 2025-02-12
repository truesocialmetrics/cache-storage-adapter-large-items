# laminas-cache-storage-adapter-large-items

A Laminas Cache Storage adapter that enables storing large items by automatically splitting them into smaller chunks.

## Installation

Install via composer:
```
composer require truesocialmetrics/cache-storage-adapter-large-items
```

## Features

- Transparently handles large cache items by splitting them into smaller chunks
- Compatible with any existing Laminas Cache Storage adapter
- Configurable maximum item size
- Preserves complex data structures through JSON serialization
- Automatic cleanup of chunked items on removal

## Usage

```
$cache = new \Laminas\Cache\Storage\Adapter\Memory();
$packer = new \Twee\Cache\Storage\Adapter\Packer($cache, 1000);

$packer->setItem('large_item', ['data' => str_repeat('a', 100000)]);
$item = $packer->getItem('large_item');
var_dump($item);
```

## How It Works

The adapter automatically handles large items by:

1. Attempting to store items directly if they're under the maximum size limit
2. For larger items:
   - Splits the data into smaller chunks
   - Stores each chunk with a unique key
   - Creates an index to track all chunks
   - Automatically reassembles the data when retrieving
   - Cleans up all chunks when removing items

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| maxItemSize | int | 300000 | Maximum size in bytes for a single cache item |

## Requirements

- PHP 8.0 or later
- laminas/laminas-cache

## License

MIT License

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.