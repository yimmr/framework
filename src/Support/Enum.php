<?php
namespace Impack\Support;

use UnexpectedValueException;

abstract class Enum implements \JsonSerializable
{
    protected $value;

    protected static $cache = [];

    protected function __construct($value)
    {
        if ($value instanceof static ) {
            $value = $value->getValue();
        }

        if (!$this->in($value)) {
            throw new \UnexpectedValueException("{$value}不属于枚举类：" . static::class);
        }

        $this->value = $value;
    }

    /** 当前枚举值 */
    public function getValue()
    {
        return $this->value;
    }

    /** 当前枚举键 */
    public function getKey()
    {
        return static::search($this->value);
    }

    /** 所有枚举键 */
    public static function keys()
    {
        return \array_keys(static::toArray());
    }

    /** 所有枚举类的实例 */
    public static function values()
    {
        foreach (static::toArray() as $key => $value) {
            $values[$key] = new static($value);
        }
        return $values ?: [];
    }

    /** 是否有指定值 */
    public static function in($value)
    {
        return \in_array($value, static::toArray(), true);
    }

    /** 是否存在索引键 */
    public static function exist($key)
    {
        return \array_key_exists($key, static::toArray());
    }

    /** 返回值的键，不存在值则为false */
    public static function search($value)
    {
        return \array_search($value, static::toArray(), true);
    }

    /** 所有枚举值转键值数组 */
    public static function toArray()
    {
        if (!isset(static::$cache[$class = static::class])) {
            static::$cache[$class] = (new \ReflectionClass($class))->getConstants();
        }
        return static::$cache[$class];
    }

    /** 当前枚举是否与指定枚举相等 */
    final public function equals($variable = null): bool
    {
        return $variable instanceof self
        && $this->getValue() === $variable->getValue()
        && static::class === \get_class($variable);
    }

    public static function __callStatic($name, $args)
    {
        return new static(static::exist($name) ? static::toArray()[$name] : $name);
    }

    public function __toString()
    {
        return (string) $this->value;
    }

    public function jsonSerialize()
    {
        return $this->getValue();
    }
}