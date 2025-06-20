<?php

declare(strict_types=1);

require_once 'WMEFieldType.php';

class TestAtJoinNode {
    public WMEFieldType $field_of_arg1;
    public WMEFieldType $field_of_arg2;
    public int $ix_in_token_of_arg2;

    public function __construct(WMEFieldType $field_of_arg1, WMEFieldType $field_of_arg2, int $ix_in_token_of_arg2) {
        $this->field_of_arg1 = $field_of_arg1;
        $this->field_of_arg2 = $field_of_arg2;
        $this->ix_in_token_of_arg2 = $ix_in_token_of_arg2;
    }

    public function equals(TestAtJoinNode $other): bool {
        return $this->field_of_arg1 === $other->field_of_arg1 &&
               $this->field_of_arg2 === $other->field_of_arg2 &&
               $this->ix_in_token_of_arg2 === $other->ix_in_token_of_arg2;
    }

    public function __toString(): string {
        return "(test-at-join " . $this->field_of_arg1->name . " == " .
               $this->ix_in_token_of_arg2 . "[" . $this->field_of_arg2->name . "])";
    }
}
