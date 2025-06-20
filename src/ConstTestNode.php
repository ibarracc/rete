<?php

declare(strict_types=1);

require_once 'WMEFieldType.php';
require_once 'AlphaMemory.php';

class ConstTestNode {
    public WMEFieldType $field_to_test;
    public string $field_must_equal;
    public ?AlphaMemory $output_memory; // Can be null
    /** @var list<ConstTestNode> */
    public array $children = [];

    public function __construct(WMEFieldType $field_to_test, string $field_must_equal, ?AlphaMemory $output_memory) {
        $this->field_to_test = $field_to_test;
        $this->field_must_equal = $field_must_equal;
        $this->output_memory = $output_memory;
    }

    public static function dummy_top(): ConstTestNode {
        return new ConstTestNode(WMEFieldType::None, "-42", null);
    }

    public function __toString(): string {
        if ($this->field_to_test === WMEFieldType::None) {
            return "(const-test dummy)";
        }
        return "(const-test " . $this->field_to_test->name . " =? " . $this->field_must_equal . ")";
    }
}
