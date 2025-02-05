<?php

namespace Apache\Avro\Schema;

use Apache\Avro\Exception\SchemaParseException;

/**
 * Field of an {@link RecordSchema}.
 */
class Field extends Schema
{
    public const FIELD_NAME_ATTR = 'name';
    public const DEFAULT_ATTR = 'default';
    public const ORDER_ATTR = 'order';

    private const ASC_SORT_ORDER = 'ascending';
    private const DESC_SORT_ORDER = 'descending';
    private const IGNORE_SORT_ORDER = 'ignore';

    private static $validFieldSortOrders = [
        self::ASC_SORT_ORDER,
        self::DESC_SORT_ORDER,
        self::IGNORE_SORT_ORDER,
    ];

    private $name;
    private $isTypeFromSchemata;
    private $hasDefault;
    private $default;
    private $order;
    private $doc;

    /**
     * @param mixed $default
     *
     * @todo Check validity of $default value
     * @todo Check validity of $order value
     */
    public function __construct(
        string $name,
        Schema $schema,
        bool $isTypeFromSchemata,
        bool $hasDefault,
        $default,
        ?string $order = null,
        ?string $doc = null
    ) {
        if (!Name::isWellFormedName($name)) {
            throw new SchemaParseException('Field requires a "name" attribute');
        }

        $this->checkOrderValue($order);

        parent::__construct($schema);

        $this->name = $name;
        $this->isTypeFromSchemata = $isTypeFromSchemata;
        $this->hasDefault = $hasDefault;
        $this->default = $default;
        $this->order = $order;
        $this->doc = $doc;
    }

    public function toAvro(): array
    {
        $avro = [self::FIELD_NAME_ATTR => $this->name];

        $avro[Schema::TYPE_ATTR] = $this->isTypeFromSchemata ? $this->type->getQualifiedName() : $this->type->toAvro();

        if ($this->hasDefault) {
            $avro[self::DEFAULT_ATTR] = $this->default;
        }

        if (null !== $this->order) {
            $avro[self::ORDER_ATTR] = $this->order;
        }

        if (null !== $this->doc) {
            $avro[self::DOC_ATTR] = $this->doc;
        }

        return $avro;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function defaultValue()
    {
        return $this->default;
    }

    public function hasDefaultValue(): bool
    {
        return $this->hasDefault;
    }

    private function checkOrderValue(?string $order): void
    {
        if (null !== $order && !in_array($order, self::$validFieldSortOrders, true)) {
            throw new SchemaParseException(sprintf('Invalid field sort order %s', $order));
        }
    }
}
