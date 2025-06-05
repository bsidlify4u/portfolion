<?php

namespace Portfolion\Database\Schema;

use Portfolion\Database\SchemaBuilder;

class Blueprint
{
    /**
     * The schema builder instance.
     *
     * @var SchemaBuilder
     */
    protected $builder;

    /**
     * Create a new blueprint instance.
     *
     * @param SchemaBuilder $builder
     * @return void
     */
    public function __construct(SchemaBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Add an ID column to the table.
     *
     * @param string $column
     * @return SchemaBuilder
     */
    public function id(string $column = 'id'): SchemaBuilder
    {
        return $this->builder->id($column);
    }

    /**
     * Add a string column to the table.
     *
     * @param string $column
     * @param int $length
     * @return SchemaBuilder
     */
    public function string(string $column, int $length = 255): SchemaBuilder
    {
        return $this->builder->string($column, $length);
    }

    /**
     * Add a text column to the table.
     *
     * @param string $column
     * @return SchemaBuilder
     */
    public function text(string $column): SchemaBuilder
    {
        return $this->builder->text($column);
    }

    /**
     * Add a long text column to the table.
     *
     * @param string $column
     * @return SchemaBuilder
     */
    public function longText(string $column): SchemaBuilder
    {
        return $this->builder->addColumn($column, 'LONGTEXT');
    }

    /**
     * Add an integer column to the table.
     *
     * @param string $column
     * @param bool $autoIncrement
     * @return SchemaBuilder
     */
    public function integer(string $column, bool $autoIncrement = false): SchemaBuilder
    {
        return $this->builder->integer($column, $autoIncrement);
    }

    /**
     * Add a big integer column to the table.
     *
     * @param string $column
     * @param bool $autoIncrement
     * @return SchemaBuilder
     */
    public function bigInteger(string $column, bool $autoIncrement = false): SchemaBuilder
    {
        return $this->builder->bigInteger($column, $autoIncrement);
    }

    /**
     * Add an unsigned big integer column to the table.
     *
     * @param string $column
     * @return SchemaBuilder
     */
    public function unsignedBigInteger(string $column): SchemaBuilder
    {
        return $this->builder->addColumn($column, 'BIGINT UNSIGNED');
    }

    /**
     * Add a boolean column to the table.
     *
     * @param string $column
     * @return SchemaBuilder
     */
    public function boolean(string $column): SchemaBuilder
    {
        return $this->builder->boolean($column);
    }

    /**
     * Add a date column to the table.
     *
     * @param string $column
     * @return SchemaBuilder
     */
    public function date(string $column): SchemaBuilder
    {
        return $this->builder->date($column);
    }

    /**
     * Add a datetime column to the table.
     *
     * @param string $column
     * @return SchemaBuilder
     */
    public function dateTime(string $column): SchemaBuilder
    {
        return $this->builder->dateTime($column);
    }

    /**
     * Add a timestamp column to the table.
     *
     * @param string $column
     * @return SchemaBuilder
     */
    public function timestamp(string $column): SchemaBuilder
    {
        return $this->builder->timestamp($column);
    }

    /**
     * Add created_at and updated_at columns to the table.
     *
     * @return SchemaBuilder
     */
    public function timestamps(): SchemaBuilder
    {
        return $this->builder->timestamps();
    }

    /**
     * Add an enum column to the table.
     *
     * @param string $column
     * @param array $values
     * @return SchemaBuilder
     */
    public function enum(string $column, array $values): SchemaBuilder
    {
        // Format values correctly for MySQL ENUM
        $formattedValues = [];
        foreach ($values as $value) {
            $formattedValues[] = "'" . addslashes($value) . "'";
        }
        $valuesStr = implode(', ', $formattedValues);
        
        // Create the ENUM type correctly without backticks in the type itself
        return $this->builder->addColumn($column, "ENUM({$valuesStr})");
    }
    
    /**
     * Set a default value for the last column.
     *
     * @param mixed $value
     * @return SchemaBuilder
     */
    public function default(mixed $value): SchemaBuilder
    {
        return $this->builder->default($value);
    }

    /**
     * Add an unsigned tiny integer column to the table.
     *
     * @param string $column
     * @return SchemaBuilder
     */
    public function unsignedTinyInteger(string $column): SchemaBuilder
    {
        return $this->builder->addColumn($column, 'TINYINT UNSIGNED');
    }
    
    /**
     * Add an unsigned integer column to the table.
     *
     * @param string $column
     * @return SchemaBuilder
     */
    public function unsignedInteger(string $column): SchemaBuilder
    {
        return $this->builder->addColumn($column, 'INT UNSIGNED');
    }

    /**
     * Add an index to the table.
     *
     * @param string|array $columns
     * @param string|null $name
     * @return SchemaBuilder
     */
    public function index($columns, ?string $name = null): SchemaBuilder
    {
        $columns = (array) $columns;
        
        if (!$name) {
            $table = $this->builder->getTable();
            $name = $table . '_' . implode('_', $columns) . '_index';
        }
        
        // Format column names with backticks
        $columnList = [];
        foreach ($columns as $column) {
            $columnList[] = "`{$column}`";
        }
        
        // Create the index
        return $this->builder->addIndex($name, $columnList);
    }
    
    /**
     * Make the column use current timestamp as default value.
     *
     * @return SchemaBuilder
     */
    public function useCurrent(): SchemaBuilder
    {
        return $this->builder->default('CURRENT_TIMESTAMP');
    }

    /**
     * Make the column unique.
     *
     * @return SchemaBuilder
     */
    public function unique(): SchemaBuilder
    {
        return $this->builder->unique();
    }
} 