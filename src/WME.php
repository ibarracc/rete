<?php

declare(strict_types=1);

require_once 'WMEFieldType.php';

class WME {
    /** @var array<int, string> */
    public array $fields = [];

    public function __construct(string $id, string $attr, string $val) {
        $this->fields[WMEFieldType::Ident->value] = $id;
        $this->fields[WMEFieldType::Attr->value] = $attr;
        $this->fields[WMEFieldType::Val->value] = $val;
    }

    public function get_field(WMEFieldType $ty): string {
        if ($ty === WMEFieldType::None) {
            throw new InvalidArgumentException("Cannot get field for WMEFieldType::None");
        }
        return $this->fields[$ty->value];
    }

    public function __toString(): string {
        $fieldStrings = [];
        for ($f = 0; $f < WMEFieldType::NumFields->value; ++$f) {
            $fieldStrings[] = $this->fields[$f];
        }
        return "(" . implode(" ", $fieldStrings) . ")";
    }
}
