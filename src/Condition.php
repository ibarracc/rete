<?php

declare(strict_types=1);

require_once 'Field.php';
require_once 'WMEFieldType.php'; // For NumFields constant

class Condition {
    /** @var array<int, Field> */
    public array $attrs = []; // Indexed by WMEFieldType value

    public function __construct(Field $ident, Field $attr, Field $val) {
        $this->attrs[WMEFieldType::Ident->value] = $ident;
        $this->attrs[WMEFieldType::Attr->value] = $attr;
        $this->attrs[WMEFieldType::Val->value] = $val;
    }

    public function __toString(): string {
        $fieldStrings = [];
        // Ensure order for string representation if that's important,
        // though direct indexing by WMEFieldType during construction handles storage.
        $fieldStrings[] = (string)$this->attrs[WMEFieldType::Ident->value];
        $fieldStrings[] = (string)$this->attrs[WMEFieldType::Attr->value];
        $fieldStrings[] = (string)$this->attrs[WMEFieldType::Val->value];
        return "(" . implode(" ", $fieldStrings) . ")";
    }
}
