<?php

declare(strict_types=1);

class AlphaMemory {
    /** @var list<WME> */
    public array $items = []; // Every item must be a valid WME
    /** @var list<JoinNode> */
    public array $successors = []; // Every item must be a valid JoinNode

    public function __toString(): string {
        $wmeStrings = array_map(fn($wme) => (string)$wme, $this->items);
        return "(alpha-memory:" . count($this->items) . " " . implode(" ", $wmeStrings) . ")";
    }
}
