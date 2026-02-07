MemoryBank (file-based)

Overview

A tiny PHP `MemoryBank` class to persist small JSON-serializable memories to disk. Data is written to `app/memory/memory.json` by default.

Usage

Example:

```php
require_once __DIR__ . '/app/memory/memory.php';

$mb = new MemoryBank();
$mb->save('user:123:note', ['text' => 'Likes blue', 'source' => 'chat'], ['user', 'preference']);
$note = $mb->get('user:123:note');

// search by tag
$results = $mb->searchByTag('preference');

// delete
$mb->delete('user:123:note');
```

Notes

- Designed for small-scale use and development. For production or large datasets, use a database (MySQL, Redis, etc.).
- File writes are atomic where supported (writes to a temp file then rename).
- The class stores value + tags + updated timestamp per key.
