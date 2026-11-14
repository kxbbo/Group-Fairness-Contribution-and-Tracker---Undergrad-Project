<?php
/**
 * FairShare Standalone Engine (Zero-Dependency Laravel 11 Compatibility Runner)
 * Enables FairShare to run out-of-the-box in XAMPP (PHP 8.0+) without requiring
 * separate Composer installation, while maintaining strict Laravel 11 MVC architecture.
 */

namespace {

// 1. Session Initialization
if (!isset($_SESSION)) {
    $_SESSION = [];
}
if (php_sapi_name() !== 'cli') {
    $sessionSavePath = dirname(__DIR__) . '/storage/framework/sessions';
    if (is_dir($sessionSavePath) && is_writable($sessionSavePath)) {
        session_save_path($sessionSavePath);
    }
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['_token'])) {
        $_SESSION['_token'] = bin2hex(random_bytes(32));
    }
}

// 2. Environment loader
function fairshare_env($key, $default = null) {
    static $envVars = null;
    if ($envVars === null) {
        $envVars = [];
        $envFile = dirname(__DIR__) . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') continue;
                if (strpos($line, '=') !== false) {
                    list($k, $v) = explode('=', $line, 2);
                    $k = trim($k);
                    $v = trim($v);
                    $v = trim($v, '"\'');
                    $envVars[$k] = $v;
                }
            }
        }
    }
    return $envVars[$key] ?? getenv($key) ?: $default;
}

if (!function_exists('env')) {
    function env($key, $default = null) {
        return fairshare_env($key, $default);
    }
}

// 3. Database Connection (PDO)
class FairShareDB {
    private static ?PDO $pdo = null;

    public static function pdo(): PDO {
        if (self::$pdo === null) {
            $host = env('DB_HOST', '127.0.0.1');
            $port = env('DB_PORT', '3306');
            $dbname = env('DB_DATABASE', 'fairshare_db');
            $user = env('DB_USERNAME', 'root');
            $pass = env('DB_PASSWORD', '');

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return self::$pdo;
    }
}

// 4. Autoloader for App\
spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') === 0) {
        $rel = str_replace('App\\', '', $class);
        $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', $rel) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

} // end global namespace

// -------------------------------------------------------------
// Illuminate Support & Contracts
// -------------------------------------------------------------
namespace Illuminate\Contracts\Support {
    interface Arrayable { public function toArray(); }
}

namespace Illuminate\Support {
    use ArrayAccess;
    use Countable;
    use IteratorAggregate;
    use ArrayIterator;

    class Collection implements ArrayAccess, Countable, IteratorAggregate {
        protected array $items = [];

        public function __construct($items = []) {
            if ($items instanceof Collection) {
                $this->items = $items->all();
            } elseif (is_array($items)) {
                $this->items = $items;
            } else {
                $this->items = $items ? [$items] : [];
            }
        }

        public static function make($items = []): self {
            return new static($items);
        }

        public function all(): array { return $this->items; }
        public function count(): int { return count($this->items); }
        public function isEmpty(): bool { return empty($this->items); }
        public function isNotEmpty(): bool { return !empty($this->items); }

        public function first(?callable $callback = null, $default = null) {
            if ($callback === null) {
                return !empty($this->items) ? reset($this->items) : $default;
            }
            foreach ($this->items as $k => $v) {
                if ($callback($v, $k)) return $v;
            }
            return $default;
        }

        public function firstWhere($key, $operator = null, $value = null) {
            if (func_num_args() === 2) {
                $value = $operator;
                $operator = '=';
            }
            return $this->first(function ($item) use ($key, $value) {
                $v = is_object($item) ? ($item->$key ?? null) : ($item[$key] ?? null);
                return $v == $value;
            });
        }

        public function where($key, $operator = null, $value = null): self {
            if (func_num_args() === 2) {
                $value = $operator;
                $operator = '=';
            }
            $results = [];
            foreach ($this->items as $item) {
                $v = is_object($item) ? ($item->$key ?? null) : ($item[$key] ?? null);
                if ($v == $value) {
                    $results[] = $item;
                }
            }
            return new static($results);
        }

        public function contains($key, $value = null): bool {
            if (func_num_args() === 2) {
                return $this->firstWhere($key, $value) !== null;
            }
            foreach ($this->items as $item) {
                if ($item == $key) return true;
                if (is_object($item) && isset($item->id) && is_object($key) && isset($key->id) && $item->id == $key->id) return true;
            }
            return false;
        }

        public function push(...$values): self {
            foreach ($values as $v) $this->items[] = $v;
            return $this;
        }

        public function sortBy($callback): self {
            $items = $this->items;
            usort($items, function($a, $b) use ($callback) {
                $v1 = is_string($callback) ? (is_object($a) ? $a->$callback : $a[$callback]) : $callback($a);
                $v2 = is_string($callback) ? (is_object($b) ? $b->$callback : $b[$callback]) : $callback($b);
                return $v1 <=> $v2;
            });
            return new static($items);
        }

        public function sum($callback = null): float|int {
            $total = 0;
            foreach ($this->items as $item) {
                if ($callback === null) {
                    $total += is_numeric($item) ? $item : 0;
                } elseif (is_string($callback)) {
                    $val = is_object($item) ? ($item->$callback ?? 0) : ($item[$callback] ?? 0);
                    $total += (float) $val;
                } elseif (is_callable($callback)) {
                    $total += (float) $callback($item);
                }
            }
            return $total;
        }

        public function whereIn($key, $values): self {
            $values = is_array($values) ? $values : ($values instanceof Collection ? $values->all() : [$values]);
            $results = [];
            foreach ($this->items as $item) {
                $v = is_object($item) ? ($item->$key ?? null) : ($item[$key] ?? null);
                if (in_array($v, $values)) {
                    $results[] = $item;
                }
            }
            return new static($results);
        }

        public function whereNotIn($key, $values): self {
            $values = is_array($values) ? $values : ($values instanceof Collection ? $values->all() : [$values]);
            $results = [];
            foreach ($this->items as $item) {
                $v = is_object($item) ? ($item->$key ?? null) : ($item[$key] ?? null);
                if (!in_array($v, $values)) {
                    $results[] = $item;
                }
            }
            return new static($results);
        }

        public function values(): self {
            return new static(array_values($this->items));
        }

        public function filter(?callable $callback = null): self {
            if ($callback === null) {
                return new static(array_filter($this->items));
            }
            return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
        }

        public function map(callable $callback): self {
            $keys = array_keys($this->items);
            $items = array_map($callback, $this->items, $keys);
            return new static(array_combine($keys, $items));
        }

        public function pluck($value, $key = null): self {
            $results = [];
            foreach ($this->items as $item) {
                $v = is_object($item) ? ($item->$value ?? null) : ($item[$value] ?? null);
                if ($key !== null) {
                    $k = is_object($item) ? ($item->$key ?? null) : ($item[$key] ?? null);
                    $results[$k] = $v;
                } else {
                    $results[] = $v;
                }
            }
            return new static($results);
        }

        public function getIterator(): \Traversable { return new ArrayIterator($this->items); }
        public function offsetExists($offset): bool { return isset($this->items[$offset]); }
        public function offsetGet($offset): mixed { return $this->items[$offset] ?? null; }
        public function offsetSet($offset, $value): void {
            if ($offset === null) $this->items[] = $value;
            else $this->items[$offset] = $value;
        }
        public function offsetUnset($offset): void { unset($this->items[$offset]); }
    }

    class Str {
        public static function slug($title, $separator = '-'): string {
            $title = preg_replace('![^\pL\pN]+!u', $separator, $title);
            return trim(strtolower($title), $separator);
        }

        public static function limit($value, $limit = 100, $end = '...'): string {
            $value = (string) $value;
            if (mb_strwidth($value, 'UTF-8') <= $limit) {
                return $value;
            }
            return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
        }
    }
}

namespace {
    if (!class_exists('Str')) {
        class_alias('Illuminate\Support\Str', 'Str');
    }
}

// -------------------------------------------------------------
// Carbon Date Library Shim
// -------------------------------------------------------------
namespace Carbon {
    use DateTime;
    class Carbon extends DateTime {
        public static function parse($time = null, $tz = null): self {
            if ($time instanceof DateTime) {
                return new static($time->format('Y-m-d H:i:s'));
            }
            return new static($time ?? 'now');
        }

        public function isPast(): bool {
            return $this->getTimestamp() < time();
        }

        public function format($format): string {
            return parent::format($format);
        }
    }
}

// -------------------------------------------------------------
// Eloquent Relationships
// -------------------------------------------------------------
namespace Illuminate\Database\Eloquent\Relations {
    use Illuminate\Support\Collection;
    use Illuminate\Database\Eloquent\Model;
    use FairShareDB;
    use PDO;

    abstract class Relation {
        public Model $parent;
        public string $related;

        public function __construct(Model $parent, string $related) {
            $this->parent = $parent;
            $this->related = $related;
        }

        abstract public function getResults();

        public function __call($name, $arguments) {
            return $this; // Fluent chaining support like ->withPivot(), ->wherePivotNull(), etc.
        }
    }

    class BelongsTo extends Relation {
        protected ?string $foreignKey;
        protected string $ownerKey;

        public function __construct(Model $parent, string $related, ?string $foreignKey = null, string $ownerKey = 'id') {
            parent::__construct($parent, $related);
            $this->foreignKey = $foreignKey ?: strtolower(basename(str_replace('\\', '/', $related))) . '_id';
            $this->ownerKey = $ownerKey;
        }

        public function getResults(): ?Model {
            $val = $this->parent->{$this->foreignKey};
            if (!$val) return null;
            $related = $this->related;
            return $related::where($this->ownerKey, '=', $val)->first();
        }
    }

    class HasMany extends Relation {
        protected ?string $foreignKey;
        protected string $localKey;

        public function __construct(Model $parent, string $related, ?string $foreignKey = null, string $localKey = 'id') {
            parent::__construct($parent, $related);
            $this->foreignKey = $foreignKey ?: strtolower(basename(str_replace('\\', '/', get_class($parent)))) . '_id';
            $this->localKey = $localKey;
        }

        public function getResults(): Collection {
            $val = $this->parent->{$this->localKey};
            if (!$val) return new Collection();
            $related = $this->related;
            return $related::where($this->foreignKey, '=', $val)->get();
        }
    }

    class BelongsToMany extends Relation {
        protected ?string $pivotTable;
        protected ?string $foreignPivotKey;
        protected ?string $relatedPivotKey;
        protected ?string $pivotNullCol = null;

        public function __construct(Model $parent, string $related, ?string $pivotTable = null, ?string $foreignPivotKey = null, ?string $relatedPivotKey = 'id') {
            parent::__construct($parent, $related);
            $this->pivotTable = $pivotTable ?: 'team_members';
            $this->foreignPivotKey = $foreignPivotKey ?: 'team_id';
            $this->relatedPivotKey = $relatedPivotKey ?: 'user_id';
        }

        public function withPivot(...$cols): self { return $this; }
        public function wherePivotNull($col): self { 
            $this->pivotNullCol = $col;
            return $this; 
        }
        public function withTimestamps(): self { return $this; }

        public function getResults(): Collection {
            $localVal = $this->parent->id ?? null;
            if (!$localVal) return new Collection();

            $related = $this->related;
            $relatedTable = (new $related)->getTable();

            $sql = "SELECT r.*, p.role_title FROM `{$relatedTable}` r 
                    JOIN `{$this->pivotTable}` p ON p.`{$this->relatedPivotKey}` = r.id 
                    WHERE p.`{$this->foreignPivotKey}` = ?";
            if ($this->pivotNullCol) {
                $sql .= " AND p.`{$this->pivotNullCol}` IS NULL";
            }
            $stmt = FairShareDB::pdo()->prepare($sql);
            $stmt->execute([$localVal]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $models = [];
            foreach ($rows as $row) {
                $m = new $related($row, true);
                if (isset($row['role_title'])) $m->role_title = $row['role_title'];
                $models[] = $m;
            }
            return new Collection($models);
        }
    }
}

// -------------------------------------------------------------
// Eloquent Query & Model Shim
// -------------------------------------------------------------
namespace Illuminate\Database\Eloquent {
    use FairShareDB;
    use PDO;
    use Carbon\Carbon;
    use Illuminate\Support\Collection;
    use Illuminate\Database\Eloquent\Relations\Relation;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\Relations\HasMany;
    use Illuminate\Database\Eloquent\Relations\BelongsToMany;

    class Builder {
        protected string $modelClass;
        protected string $table;
        protected array $selects = ['*'];
        protected array $joins = [];
        protected array $wheres = [];
        protected array $orders = [];
        protected array $withs = [];
        protected ?int $limit = null;

        public function __construct(string $modelClass, string $table) {
            $this->modelClass = $modelClass;
            $this->table = $table;
        }

        public function select(...$columns): self {
            if (count($columns) === 1 && is_array($columns[0])) {
                $columns = $columns[0];
            }
            $this->selects = $columns;
            return $this;
        }

        public function join(string $table, string $first, string $operator, string $second): self {
            $this->joins[] = [$table, $first, $operator, $second];
            return $this;
        }

        public function where($column, $operator = null, $value = null): self {
            if (func_num_args() === 2) {
                $value = $operator;
                $operator = '=';
            }
            $this->wheres[] = [$column, $operator, $value];
            return $this;
        }

        public function orderBy($column, $direction = 'asc'): self {
            $this->orders[] = [$column, strtolower($direction) === 'desc' ? 'DESC' : 'ASC'];
            return $this;
        }

        public function orderByDesc($column): self {
            return $this->orderBy($column, 'desc');
        }

        public function with(...$relations): self {
            if (count($relations) === 1 && is_array($relations[0])) {
                $relations = $relations[0];
            }
            $this->withs = array_merge($this->withs, $relations);
            return $this;
        }

        public function max(string $column) {
            $sql = "SELECT MAX(`{$column}`) as max_val FROM `{$this->table}`";
            $params = [];
            if (!empty($this->wheres)) {
                $sql .= " WHERE " . implode(" AND ", array_map(function ($w) use (&$params) {
                    $params[] = $w[2];
                    return "`{$w[0]}` {$w[1]} ?";
                }, $this->wheres));
            }
            $stmt = FairShareDB::pdo()->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['max_val'] ?? null;
        }

        public function get(): Collection {
            $cols = implode(', ', $this->selects);
            $sql = "SELECT {$cols} FROM `{$this->table}`";
            if (!empty($this->joins)) {
                foreach ($this->joins as $j) {
                    $sql .= " JOIN `{$j[0]}` ON {$j[1]} {$j[2]} {$j[3]}";
                }
            }

            $params = [];
            if (!empty($this->wheres)) {
                $sql .= " WHERE " . implode(" AND ", array_map(function ($w) use (&$params) {
                    $params[] = $w[2];
                    $col = $w[0];
                    if (strpos($col, '.') === false) $col = "`{$col}`";
                    return "{$col} {$w[1]} ?";
                }, $this->wheres));
            }
            if (!empty($this->orders)) {
                $sql .= " ORDER BY " . implode(", ", array_map(function ($o) {
                    $col = $o[0];
                    if (strpos($col, '.') === false) $col = "`{$col}`";
                    return "{$col} {$o[1]}";
                }, $this->orders));
            }
            if ($this->limit !== null) {
                $sql .= " LIMIT {$this->limit}";
            }

            $stmt = FairShareDB::pdo()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $models = [];
            foreach ($rows as $row) {
                $m = new $this->modelClass($row, true);
                $models[] = $m;
            }

            $collection = new Collection($models);

            // Eager loading
            if (!empty($this->withs) && $collection->isNotEmpty()) {
                foreach ($this->withs as $relation) {
                    foreach ($collection as $model) {
                        $model->loadRelation($relation);
                    }
                }
            }

            return $collection;
        }

        public function first(): ?Model {
            $this->limit = 1;
            return $this->get()->first();
        }

        public function find($id): ?Model {
            return $this->where('id', '=', $id)->first();
        }

        public function findOrFail($id): Model {
            $m = $this->find($id);
            if (!$m) {
                throw new \RuntimeException("Model [{$this->modelClass}] not found with ID {$id}.");
            }
            return $m;
        }
    }

    abstract class Model {
        protected $table;
        protected $primaryKey = 'id';
        protected $fillable = [];
        protected $casts = [];
        protected array $attributes = [];
        protected array $relations = [];
        protected bool $exists = false;

        public function __construct(array $attributes = [], bool $exists = false) {
            $this->attributes = $attributes;
            $this->exists = $exists;
        }

        public function getTable(): string {
            if ($this->table) return $this->table;
            $base = basename(str_replace('\\', '/', get_class($this)));
            return strtolower($base) . 's';
        }

        public static function query(): Builder {
            $inst = new static();
            return new Builder(static::class, $inst->getTable());
        }

        public static function __callStatic($name, $arguments) {
            return call_user_func_array([static::query(), $name], $arguments);
        }

        public static function all(): Collection {
            return static::query()->get();
        }

        public static function find($id): ?static {
            return static::query()->find($id);
        }

        public static function findOrFail($id): static {
            return static::query()->findOrFail($id);
        }

        public static function where($column, $operator = null, $value = null): Builder {
            return static::query()->where(...func_get_args());
        }

        public static function with(...$relations): Builder {
            return static::query()->with(...$relations);
        }

        public static function orderBy($column, $direction = 'asc'): Builder {
            return static::query()->orderBy($column, $direction);
        }

        public static function orderByDesc($column): Builder {
            return static::query()->orderByDesc($column);
        }

        public static function join(string $table, string $first, string $operator, string $second): Builder {
            return static::query()->join($table, $first, $operator, $second);
        }

        public static function select(...$columns): Builder {
            return static::query()->select(...$columns);
        }

        public static function create(array $attributes = []): static {
            $inst = new static();
            $fillable = array_intersect_key($attributes, array_flip($inst->fillable));
            
            $cols = array_keys($fillable);
            $placeholders = array_fill(0, count($cols), '?');
            $sql = "INSERT INTO `{$inst->getTable()}` (`" . implode('`, `', $cols) . "`) VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = FairShareDB::pdo()->prepare($sql);
            $stmt->execute(array_values($fillable));
            
            $id = FairShareDB::pdo()->lastInsertId();
            return static::findOrFail($id);
        }

        public function setRelation($name, $value): self {
            $this->relations[$name] = $value;
            return $this;
        }

        public function update(array $attributes = []): bool {
            $fillable = array_intersect_key($attributes, array_flip($this->fillable));
            if (empty($fillable)) return true;

            $sets = [];
            $vals = [];
            foreach ($fillable as $col => $val) {
                $sets[] = "`{$col}` = ?";
                $vals[] = $val;
                $this->attributes[$col] = $val;
            }
            $vals[] = $this->attributes[$this->primaryKey];
            $sql = "UPDATE `{$this->getTable()}` SET " . implode(', ', $sets) . " WHERE `{$this->primaryKey}` = ?";
            
            $stmt = FairShareDB::pdo()->prepare($sql);
            return $stmt->execute($vals);
        }

        public function save(): bool {
            if ($this->exists && isset($this->attributes[$this->primaryKey])) {
                return $this->update($this->attributes);
            }
            $m = static::create($this->attributes);
            $this->attributes = $m->attributes;
            $this->exists = true;
            return true;
        }

        public function delete(): bool {
            if (!isset($this->attributes[$this->primaryKey])) return false;
            $sql = "DELETE FROM `{$this->getTable()}` WHERE `{$this->primaryKey}` = ?";
            $stmt = FairShareDB::pdo()->prepare($sql);
            return $stmt->execute([$this->attributes[$this->primaryKey]]);
        }

        public function __get($key) {
            if (array_key_exists($key, $this->relations)) {
                return $this->relations[$key];
            }
            if (method_exists($this, $key)) {
                $relation = $this->$key();
                if ($relation instanceof Relation) {
                    $this->relations[$key] = $relation->getResults();
                    return $this->relations[$key];
                }
                return $relation;
            }
            $val = $this->attributes[$key] ?? null;
            $casts = $this->casts;
            if (method_exists($this, 'casts')) {
                $casts = array_merge($casts, $this->casts());
            }
            if (isset($casts[$key])) {
                $cast = $casts[$key];
                if ($cast === 'date' || $cast === 'datetime') {
                    return $val ? Carbon::parse($val) : null;
                }
                if ($cast === 'integer') return (int) $val;
                if ($cast === 'boolean') return (bool) $val;
                if ($cast === 'array' || $cast === 'json') return is_array($val) ? $val : json_decode($val ?: '[]', true);
            }
            // Standard timestamps in Eloquent
            if ($val !== null && ($key === 'created_at' || $key === 'updated_at' || str_ends_with($key, '_at'))) {
                return Carbon::parse($val);
            }
            return $val;
        }

        public function __set($key, $value) {
            $this->attributes[$key] = $value;
        }

        public function __isset($key) {
            return isset($this->attributes[$key]) || isset($this->relations[$key]) || method_exists($this, $key);
        }

        // Relationship helpers
        public function belongsTo($related, $foreignKey = null, $ownerKey = 'id'): BelongsTo {
            return new BelongsTo($this, $related, $foreignKey, $ownerKey);
        }

        public function hasMany($related, $foreignKey = null, $localKey = 'id'): HasMany {
            return new HasMany($this, $related, $foreignKey, $localKey);
        }

        public function belongsToMany($related, $pivotTable = null, $foreignPivotKey = null, $relatedPivotKey = 'user_id'): BelongsToMany {
            if (get_class($this) === 'App\Models\Team') {
                $foreignPivotKey = $foreignPivotKey ?: 'team_id';
                $relatedPivotKey = 'user_id';
            } elseif (get_class($this) === 'App\Models\User') {
                $foreignPivotKey = $foreignPivotKey ?: 'user_id';
                $relatedPivotKey = 'team_id';
            }
            return new BelongsToMany($this, $related, $pivotTable, $foreignPivotKey, $relatedPivotKey);
        }

        public function loadRelation($name) {
            if (strpos($name, '.') !== false) {
                list($first, $rest) = explode('.', $name, 2);
                $rel = $this->$first;
                if ($rel instanceof Model) {
                    $rel->loadRelation($rest);
                } elseif ($rel instanceof Collection) {
                    foreach ($rel as $item) {
                        if ($item instanceof Model) $item->loadRelation($rest);
                    }
                }
                return;
            }
            if (method_exists($this, $name)) {
                $relation = $this->$name();
                if ($relation instanceof Relation) {
                    $this->relations[$name] = $relation->getResults();
                } else {
                    $this->relations[$name] = $relation;
                }
            }
        }
    }
}

namespace Illuminate\Database\Eloquent\Factories {
    trait HasFactory {}
}

namespace Illuminate\Notifications {
    trait Notifiable {}
}

namespace Illuminate\Foundation\Auth {
    use Illuminate\Database\Eloquent\Model;
    class User extends Model {}
}

// -------------------------------------------------------------
// HTTP Request & Response Shims
// -------------------------------------------------------------
namespace Symfony\Component\HttpFoundation {
    class BinaryFileResponse {
        protected string $file;
        protected string $name;
        protected array $headers;

        public function __construct(string $file, string $name, array $headers = []) {
            $this->file = $file;
            $this->name = $name;
            $this->headers = $headers;
        }

        public function send(): void {
            foreach ($this->headers as $k => $v) {
                header("{$k}: {$v}");
            }
            header('Content-Disposition: attachment; filename="' . basename($this->name) . '"');
            header('Content-Length: ' . filesize($this->file));
            readfile($this->file);
            exit;
        }
    }
}

namespace Illuminate\Http {
    use Illuminate\Support\Collection;

    class UploadedFile {
        public string $name;
        public string $tmpPath;
        public int $size;
        public int $error;

        public function __construct(array $file) {
            $this->name = $file['name'];
            $this->tmpPath = $file['tmp_name'];
            $this->size = $file['size'];
            $this->error = $file['error'];
        }

        public function isValid(): bool {
            return $this->error === UPLOAD_ERR_OK && is_uploaded_file($this->tmpPath);
        }

        public function getClientOriginalName(): string {
            return $this->name;
        }

        public function getClientOriginalExtension(): string {
            return strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
        }

        public function getSize(): int {
            return $this->size;
        }

        public function getRealPath(): string {
            return $this->tmpPath;
        }
    }

    class Request {
        protected array $inputData = [];
        protected array $filesData = [];

        public function __construct() {
            $this->inputData = array_merge($_GET, $_POST);
            foreach ($_FILES as $k => $f) {
                if (!empty($f['name'])) {
                    $this->filesData[$k] = new UploadedFile($f);
                }
            }
        }

        public static function capture(): self {
            return new static();
        }

        public function all(): array {
            return $this->inputData;
        }

        public function input($key, $default = null) {
            return $this->inputData[$key] ?? $default;
        }

        public function has($key): bool {
            return isset($this->inputData[$key]);
        }

        public function file($key): ?UploadedFile {
            return $this->filesData[$key] ?? null;
        }

        public function validate(array $rules): array {
            $validated = [];
            $errors = [];

            foreach ($rules as $field => $ruleList) {
                if (is_string($ruleList)) $ruleList = explode('|', $ruleList);
                $val = $this->inputData[$field] ?? null;
                $isFile = isset($this->filesData[$field]);

                $required = in_array('required', $ruleList);
                if ($required) {
                    if ($isFile) {
                        if (!$this->filesData[$field]->isValid()) $errors[$field] = "The {$field} is required.";
                    } elseif ($val === null || trim((string)$val) === '') {
                        $errors[$field] = "The {$field} field is required.";
                    }
                }

                if ($val !== null && trim((string)$val) !== '') {
                    foreach ($ruleList as $r) {
                        if ($r === 'integer' && !is_numeric($val)) {
                            $errors[$field] = "The {$field} must be an integer.";
                        } elseif (strpos($r, 'between:') === 0) {
                            list($min, $max) = explode(',', substr($r, 8));
                            if ($val < $min || $val > $max) {
                                $errors[$field] = "The {$field} must be between {$min} and {$max}.";
                            }
                        } elseif (strpos($r, 'max:') === 0 && is_numeric($val) && strlen($val) > (int)substr($r, 4)) {
                            $errors[$field] = "The {$field} cannot exceed " . substr($r, 4) . " characters.";
                        }
                    }
                }

                $validated[$field] = $val;
            }

            if (!empty($errors)) {
                $_SESSION['errors'] = $errors;
                $_SESSION['old_input'] = $this->inputData;
                $back = $_SERVER['HTTP_REFERER'] ?? route('home');
                header("Location: {$back}");
                exit;
            }

            return $validated;
        }
    }

    class RedirectResponse {
        protected string $url;

        public function __construct(string $url) {
            $this->url = $url;
        }

        public function with($key, $value): self {
            $_SESSION[$key] = $value;
            return $this;
        }

        public function send(): void {
            header("Location: {$this->url}");
            exit;
        }
    }
}

namespace Illuminate\View {
    class View {
        protected string $viewName;
        protected array $data;

        public function __construct(string $viewName, array $data = []) {
            $this->viewName = $viewName;
            $this->data = $data;
        }

        public function render(): string {
            return fairshare_render_view($this->viewName, $this->data);
        }
    }
}

// -------------------------------------------------------------
// Global Blade Compiler & View Renderer
// -------------------------------------------------------------
namespace {

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

function collect($items = []) {
    return new \Illuminate\Support\Collection($items);
}

function optional($value = null, ?callable $callback = null) {
    if (is_null($callback)) {
        return new class($value) {
            private $val;
            public function __construct($v) { $this->val = $v; }
            public function __get($k) { return is_object($this->val) ? ($this->val->$k ?? null) : null; }
            public function __call($m, $a) { return is_object($this->val) ? call_user_func_array([$this->val, $m], $a) : null; }
            public function __toString() { return (string) $this->val; }
        };
    }
    return is_null($value) ? null : $callback($value);
}

function storage_path(string $path = ''): string {
    $base = dirname(__DIR__) . '/storage';
    return $path ? $base . '/' . ltrim($path, '/\\') : $base;
}

function abort(int $code, string $message = '') {
    http_response_code($code);
    echo "<h1>{$code} Error</h1><p>" . htmlspecialchars($message) . "</p>";
    exit;
}

function response() {
    return new class {
        public function download(string $file, string $name, array $headers = []): BinaryFileResponse {
            return new BinaryFileResponse($file, $name, $headers);
        }
    };
}

function view(string $name, array $data = []): View {
    return new View($name, $data);
}

function redirect(?string $to = null) {
    if ($to !== null) {
        return new RedirectResponse($to);
    }
    return new class {
        public function route(string $name, $params = []): RedirectResponse {
            return new RedirectResponse(fairshare_route($name, $params));
        }
        public function back(): RedirectResponse {
            $back = $_SERVER['HTTP_REFERER'] ?? fairshare_route('home');
            return new RedirectResponse($back);
        }
    };
}

function route(string $name, $params = []): string {
    return fairshare_route($name, $params);
}

function asset(string $path): string {
    $base = rtrim(fairshare_base_url(), '/');
    return $base . '/' . ltrim($path, '/');
}

function session($key = null, $default = null) {
    if (is_array($key)) {
        foreach ($key as $k => $v) {
            $_SESSION[$k] = $v;
        }
        return null;
    }
    if ($key === null) {
        return new class {
            public function get($k, $d = null) { return $_SESSION[$k] ?? $d; }
            public function put($k, $v) { $_SESSION[$k] = $v; }
            public function has($k) { return isset($_SESSION[$k]); }
        };
    }
    return $_SESSION[$key] ?? $default;
}

function old($key, $default = null) {
    return $_SESSION['old_input'][$key] ?? $default;
}

function csrf_token(): string {
    return $_SESSION['_token'] ?? '';
}

function fairshare_base_url(): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $path = parse_url($uri, PHP_URL_PATH) ?? '';

    // Check if URI explicitly has /public in its path
    if (preg_match('#^(.*?/public)(?:/|$)#i', $path, $m)) {
        return rtrim($m[1], '/');
    }

    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $dir = dirname($script);
    $dir = str_replace('\\', '/', $dir);

    // If URI does not contain /public but script is in /public (e.g. root .htaccess rewrite), strip /public
    if (strpos($dir, '/public') !== false && strpos($path, '/public') === false) {
        $dir = preg_replace('#/public$#i', '', $dir);
    }

    if ($dir === '/' || $dir === '.') return '';
    return rtrim($dir, '/');
}

function fairshare_route(string $name, $params = []): string {
    if (!is_array($params)) {
        $params = [$params];
    }
    $base = fairshare_base_url();

    switch ($name) {
        case 'home': return $base . '/';
        case 'user.switch': return $base . '/user/switch';
        
        // F6 Tasks
        case 'tasks.teams': return $base . '/tasks/teams';
        case 'tasks.index': return $base . '/tasks/' . ($params['team'] ?? $params[0] ?? '');
        case 'tasks.create': return $base . '/tasks/' . ($params['team'] ?? $params[0] ?? '') . '/create';
        case 'tasks.store': return $base . '/tasks/' . ($params['team'] ?? $params[0] ?? '');
        case 'tasks.edit': return $base . '/tasks/' . ($params['id'] ?? $params[0] ?? '') . '/edit';
        case 'tasks.update': return $base . '/tasks/' . ($params['id'] ?? $params[0] ?? '');
        case 'tasks.updateStatus': return $base . '/tasks/' . ($params['id'] ?? $params[0] ?? '') . '/status';
        case 'tasks.destroy': return $base . '/tasks/' . ($params['id'] ?? $params[0] ?? '');

        // F11 Evidence
        case 'evidence.tasks': return $base . '/evidence/tasks';
        case 'evidence.index': return $base . '/evidence/' . ($params['taskId'] ?? $params[0] ?? '');
        case 'evidence.create': return $base . '/evidence/' . ($params['taskId'] ?? $params[0] ?? '') . '/create';
        case 'evidence.store': return $base . '/evidence/' . ($params['taskId'] ?? $params[0] ?? '');
        case 'evidence.destroy': return $base . '/evidence/' . ($params['id'] ?? $params[0] ?? '') . '/' . ($params['taskId'] ?? $params[1] ?? '');
        case 'evidence.download': return $base . '/evidence/' . ($params['id'] ?? $params[0] ?? '') . '/' . ($params['taskId'] ?? $params[1] ?? '') . '/download';

        default: return $base . '/' . $name;
    }
}

function fairshare_compile_blade(string $blade): string {
    // Escaped and Raw tags
    $php = preg_replace('/\{\{\-\-(.*?)\-\-\}\}/s', '', $blade); // comments
    $php = preg_replace('/\{\{\{\s*(.+?)\s*\}\}\}/s', '<?php echo htmlspecialchars($1 ?? "", ENT_QUOTES, "UTF-8"); ?>', $php);
    $php = preg_replace('/\{\{\s*(.+?)\s*\}\}/s', '<?php echo htmlspecialchars((string)($1 ?? ""), ENT_QUOTES, "UTF-8"); ?>', $php);
    $php = preg_replace('/\{\!\!\s*(.+?)\s*\!\!\}/s', '<?php echo $1 ?? ""; ?>', $php);

    // Balanced parentheses directives: @if, @elseif, @foreach, @switch, @case, @for
    $tags = ['if', 'elseif', 'foreach', 'switch', 'case', 'for'];
    foreach ($tags as $tag) {
        $pattern = '/@' . $tag . '\s*(\((?:[^()]+|(?1))*\))/';
        $php = preg_replace_callback($pattern, function($m) use ($tag) {
            $inner = trim($m[1]);
            $cond = substr($inner, 1, -1);
            if ($tag === 'if') return '<?php if (' . $cond . '): ?>';
            if ($tag === 'elseif') return '<?php elseif (' . $cond . '): ?>';
            if ($tag === 'foreach') return '<?php foreach (' . $cond . '): ?>';
            if ($tag === 'for') return '<?php for (' . $cond . '): ?>';
            if ($tag === 'switch') return '<?php switch (' . $cond . '): ?>';
            if ($tag === 'case') return '<?php case ' . $cond . ': ?>';
            return $m[0];
        }, $php);
    }

    // @forelse($iterable as $item)
    $php = preg_replace_callback('/@forelse\s*(\((?:[^()]+|(?1))*\))/', function($m) {
        $inner = trim($m[1]);
        $cond = substr($inner, 1, -1);
        return '<?php $_forelse_empty = true; foreach (' . $cond . '): $_forelse_empty = false; ?>';
    }, $php);

    // Directives
    $directives = [
        '/@php\b/' => '<?php',
        '/@endphp\b/' => '?>',
        '/@csrf\b/' => '<input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">',
        '/@method\([\'"]([A-Z]+)[\'"]\)/' => '<input type="hidden" name="_method" value="$1">',
        '/@else\b/' => '<?php else: ?>',
        '/@empty\b/' => '<?php endforeach; if ($_forelse_empty): ?>',
        '/@endforelse\b/' => '<?php endif; ?>',
        '/@endif\b/' => '<?php endif; ?>',
        '/@endforeach\b/' => '<?php endforeach; ?>',
        '/@endfor\b/' => '<?php endfor; ?>',
        '/@break\b/' => '<?php break; ?>',
        '/@endswitch\b/' => '<?php endswitch; ?>',
        '/@error\([\'"](.*?)[\'"]\)/' => '<?php if (isset($_SESSION["errors"]["$1"])): $message = $_SESSION["errors"]["$1"]; ?>',
        '/@enderror\b/' => '<?php endif; ?>',
        '/@stack\([\'"].*?[\'"]\)/' => '',
    ];

    foreach ($directives as $pattern => $replacement) {
        $php = preg_replace($pattern, $replacement, $php);
    }

    // Strip HTML/whitespace between switch/case and break/case
    $php = preg_replace('/(<\?php\s+switch\s*\([^:]+\):)\s*\?>\s*<\?php\s*(case\b)/s', '$1 $2', $php);
    $php = preg_replace('/(break;\s*)\?>\s*<\?php\s*(case\b)/s', '$1 $2', $php);
    $php = preg_replace('/(break;\s*)\?>\s*<\?php\s*(default\b)/s', '$1 $2', $php);
    $php = preg_replace('/(<\?php\s+switch\s*\([^:]+\):)\s*\?>\s*<\?php\s*(default\b)/s', '$1 $2', $php);

    return $php;
}

class MessageBag {
    protected array $messages = [];
    public function __construct(array $messages = []) { $this->messages = $messages; }
    public function any(): bool { return !empty($this->messages); }
    public function all(): array { return array_values($this->messages); }
    public function has($key): bool { return isset($this->messages[$key]); }
    public function first($key = null): ?string {
        if ($key === null) return reset($this->messages) ?: null;
        return $this->messages[$key] ?? null;
    }
}

function fairshare_render_view(string $viewPath, array $data = []): string {
    if (!isset($data['errors'])) {
        $data['errors'] = new MessageBag($_SESSION['errors'] ?? []);
    }
    $viewsDir = dirname(__DIR__) . '/resources/views';
    $file = $viewsDir . '/' . str_replace('.', '/', $viewPath) . '.blade.php';

    if (!file_exists($file)) {
        throw new RuntimeException("View [{$viewPath}] not found at {$file}.");
    }

    $raw = file_get_contents($file);

    // Check for @extends
    $layout = null;
    if (preg_match('/@extends\([\'"](.*?)[\'"]\)/', $raw, $matches)) {
        $layout = $matches[1];
        $raw = str_replace($matches[0], '', $raw);
    }

    // Extract short sections: @section('name', 'value' or $expr)
    $sections = [];
    preg_match_all('/@section\s*\(\s*[\'"]([a-zA-Z0-9_\-]+)[\'"]\s*,\s*(.*?)\s*\)/', $raw, $shortSecs, PREG_SET_ORDER);
    foreach ($shortSecs as $s) {
        $secName = $s[1];
        $valExpr = trim($s[2]);
        // If quoted string literal
        if ((str_starts_with($valExpr, "'") && str_ends_with($valExpr, "'")) || 
            (str_starts_with($valExpr, '"') && str_ends_with($valExpr, '"'))) {
            $sections[$secName] = substr($valExpr, 1, -1);
        } else {
            // Evaluate expression with available data
            extract($data);
            try {
                $evalVal = null;
                eval('$evalVal = ' . $valExpr . ';');
                $sections[$secName] = (string) $evalVal;
            } catch (\Throwable $e) {
                $sections[$secName] = $valExpr;
            }
        }
        $raw = str_replace($s[0], '', $raw);
    }

    // Extract block sections: @section('name') ... @endsection
    preg_match_all('/@section\s*\(\s*[\'"]([a-zA-Z0-9_\-]+)[\'"]\s*\)(.*?)@endsection/s', $raw, $blockSecs, PREG_SET_ORDER);
    foreach ($blockSecs as $s) {
        $sections[$s[1]] = trim($s[2]);
        $raw = str_replace($s[0], '', $raw);
    }

    // Evaluate child view sections
    extract($data);
    $compiledSections = [];
    foreach ($sections as $name => $secContent) {
        $compiledCode = fairshare_compile_blade($secContent);
        ob_start();
        eval('?>' . $compiledCode);
        $compiledSections[$name] = ob_get_clean();
    }

    // If layout exists, compile and inject sections
    if ($layout) {
        $layoutFile = $viewsDir . '/' . str_replace('.', '/', $layout) . '.blade.php';
        $layoutRaw = file_get_contents($layoutFile);

        // Replace @yield('name', 'default')
        $layoutCompiled = preg_replace_callback('/@yield\([\'"](.*?)[\'"](?:,\s*[\'"](.*?)[\'"])?\)/', function ($m) use ($compiledSections) {
            $name = $m[1];
            $default = $m[2] ?? '';
            return $compiledSections[$name] ?? $default;
        }, $layoutRaw);

        $layoutPhp = fairshare_compile_blade($layoutCompiled);
        ob_start();
        eval('?>' . $layoutPhp);
        $output = ob_get_clean();
    } else {
        $compiledCode = fairshare_compile_blade($raw);
        ob_start();
        eval('?>' . $compiledCode);
        $output = ob_get_clean();
    }

    // Clear flash session data once consumed
    unset($_SESSION['success'], $_SESSION['error'], $_SESSION['errors'], $_SESSION['old_input']);

    return $output;
}

// -------------------------------------------------------------
// Standalone Front Controller Dispatcher
// -------------------------------------------------------------
function handleStandaloneRequest(): void {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'POST' && isset($_POST['_method'])) {
        $method = strtoupper($_POST['_method']);
    }

    // Determine path relative to base URL
    $base = fairshare_base_url();
    $rawPath = parse_url($uri, PHP_URL_PATH) ?? '/';

    if (isset($_GET['r'])) {
        $path = '/' . trim($_GET['r'], '/');
    } elseif ($base !== '' && strpos($rawPath, $base) === 0) {
        $path = substr($rawPath, strlen($base));
    } else {
        $path = $rawPath;
    }

    $path = '/' . trim($path, '/');
    if ($path === '/index.php') $path = '/';
    if (strpos($path, '/index.php/') === 0) $path = substr($path, 10);
    $path = '/' . trim($path, '/');

    // CSRF Check for mutating requests
    if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
        $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!$token || $token !== $_SESSION['_token']) {
            http_response_code(419);
            echo "<h1>419 Page Expired</h1><p>CSRF token mismatch. Please <a href='javascript:history.back()'>go back</a> and reload the page.</p>";
            exit;
        }
    }

    try {
        $request = new \Illuminate\Http\Request();

        // 1. Home
        if ($path === '/' && $method === 'GET') {
            echo view('home')->render();
            return;
        }

        // 2. User Switcher
        if ($path === '/user/switch' && $method === 'POST') {
            $userId = (int) $request->input('user_id', 1);
            $_SESSION['active_user_id'] = $userId;
            $user = \App\Models\User::find($userId);
            $name = $user ? $user->name : 'User #' . $userId;
            redirect()->back()->with('success', "Active simulated user switched to: {$name}")->send();
            return;
        }

        // 3. F6 Tasks
        if ($path === '/tasks/teams' && $method === 'GET') {
            $c = new \App\Http\Controllers\TaskController();
            echo $c->teams()->render();
            return;
        }

        if (preg_match('#^/tasks/(\d+)$#', $path, $m)) {
            $teamId = (int) $m[1];
            $c = new \App\Http\Controllers\TaskController();
            if ($method === 'GET') {
                echo $c->index($teamId)->render();
                return;
            } elseif ($method === 'POST') {
                $c->store($request, $teamId)->send();
                return;
            }
        }

        if (preg_match('#^/tasks/(\d+)/create$#', $path, $m) && $method === 'GET') {
            $teamId = (int) $m[1];
            $c = new \App\Http\Controllers\TaskController();
            echo $c->create($teamId)->render();
            return;
        }

        if (preg_match('#^/tasks/(\d+)/edit$#', $path, $m) && $method === 'GET') {
            $taskId = (int) $m[1];
            $c = new \App\Http\Controllers\TaskController();
            echo $c->edit($taskId)->render();
            return;
        }

        if (preg_match('#^/tasks/(\d+)$#', $path, $m) && $method === 'PUT') {
            $taskId = (int) $m[1];
            $c = new \App\Http\Controllers\TaskController();
            $c->update($request, $taskId)->send();
            return;
        }

        if (preg_match('#^/tasks/(\d+)/status$#', $path, $m) && $method === 'POST') {
            $taskId = (int) $m[1];
            $c = new \App\Http\Controllers\TaskController();
            $c->updateStatus($request, $taskId)->send();
            return;
        }

        if (preg_match('#^/tasks/(\d+)$#', $path, $m) && $method === 'DELETE') {
            $taskId = (int) $m[1];
            $c = new \App\Http\Controllers\TaskController();
            $c->destroy($taskId)->send();
            return;
        }

        // 4. F11 Evidence Vault
        if ($path === '/evidence/tasks' && $method === 'GET') {
            $c = new \App\Http\Controllers\EvidenceController();
            echo $c->tasks()->render();
            return;
        }

        if (preg_match('#^/evidence/(\d+)$#', $path, $m)) {
            $taskId = (int) $m[1];
            $c = new \App\Http\Controllers\EvidenceController();
            if ($method === 'GET') {
                echo $c->index($taskId)->render();
                return;
            } elseif ($method === 'POST') {
                $c->store($request, $taskId)->send();
                return;
            }
        }

        if (preg_match('#^/evidence/(\d+)/create$#', $path, $m) && $method === 'GET') {
            $taskId = (int) $m[1];
            $c = new \App\Http\Controllers\EvidenceController();
            echo $c->create($taskId)->render();
            return;
        }

        if (preg_match('#^/evidence/(\d+)/(\d+)$#', $path, $m) && $method === 'DELETE') {
            $id = (int) $m[1];
            $taskId = (int) $m[2];
            $c = new \App\Http\Controllers\EvidenceController();
            $c->destroy($id, $taskId)->send();
            return;
        }

        if (preg_match('#^/evidence/(\d+)/(\d+)/download$#', $path, $m) && $method === 'GET') {
            $id = (int) $m[1];
            $taskId = (int) $m[2];
            $c = new \App\Http\Controllers\EvidenceController();
            $res = $c->download($taskId, $id);
            if ($res instanceof BinaryFileResponse) {
                $res->send();
            }
            return;
        }

        http_response_code(404);
        echo "<h1>404 Not Found</h1><p>Route [{$path}] is not registered. <a href='" . fairshare_route('home') . "'>Return to Home</a></p>";
    } catch (\Throwable $e) {
        http_response_code(500);
        $err = htmlspecialchars($e->getMessage());
        $file = htmlspecialchars($e->getFile() . ':' . $e->getLine());
        echo "<h1>Application Error (500)</h1><p><strong>{$err}</strong></p><p><small>{$file}</small></p>";
        if (strpos($e->getMessage(), 'Connection refused') !== false || strpos($e->getMessage(), 'database') !== false) {
            echo "<div style='background:#fff3cd;padding:15px;border-radius:8px;border:1px solid #ffeeba;margin-top:15px;'>";
            echo "<strong>Database Setup Needed:</strong> Please ensure MySQL is started in XAMPP and <code>fairshare_db</code> has been imported in phpMyAdmin.";
            echo "</div>";
        }
    }
}

// -------------------------------------------------------------
// Standalone Artisan Runner
// -------------------------------------------------------------
function handleStandaloneArtisan(array $argv): int {
    $command = $argv[1] ?? 'help';
    if ($command === 'serve') {
        $host = '127.0.0.1';
        $port = '8000';
        foreach ($argv as $arg) {
            if (strpos($arg, '--host=') === 0) $host = substr($arg, 7);
            if (strpos($arg, '--port=') === 0) $port = substr($arg, 7);
        }
        $public = dirname(__DIR__) . '/public';
        echo "FairShare Development Server running at: http://{$host}:{$port}\n";
        echo "Press Ctrl+C to stop the server.\n";
        passthru(PHP_BINARY . " -S {$host}:{$port} -t \"{$public}\"");
        return 0;
    }
    echo "FairShare Standalone Artisan (PHP " . PHP_VERSION . ")\n";
    echo "Available commands:\n";
    echo "  serve    Start the PHP development server (e.g. php artisan serve)\n";
    return 0;
}

// Execute standalone request if run directly through web server
if (php_sapi_name() !== 'cli') {
    handleStandaloneRequest();
}

} // end namespace
