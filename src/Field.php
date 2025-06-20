<?php

declare(strict_types=1);

require_once 'FieldType.php';

class Field {
    public FieldType $type;
    public string $v; // Value or variable name

    private function __construct(FieldType $type, string $v) {
        $this->type = $type;
        $this->v = $v;
    }

    public static function var(string $name): Field {
        return new Field(FieldType::Var, $name);
    }

    public static function constant(string $value): Field {
        return new Field(FieldType::Const, $value);
    }

    public function __toString(): string {
        return $this->type === FieldType::Var ? "<{$this->v}>" : $this->v;
    }
}
