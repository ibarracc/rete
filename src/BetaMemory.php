<?php

declare(strict_types=1);

require_once 'Token.php';
require_once 'WME.php';
// Forward declaration for JoinNode handled by require_once order in Rete.php or main file

class BetaMemory {
    public ?JoinNode $parent = null; // Invariant: must be valid if not null (null for ProductionNode's direct parent if it's a join)
                                 // Or, can be null if it's the initial BetaMemory in some Rete structures.
                                 // For ProductionNode, this will be the JoinNode it's attached to.
    /** @var list<Token> */
    public array $items = [];
    /** @var list<JoinNode> */
    public array $children = [];

    // This method will be called by a JoinNode
    public function join_activation(?Token $t, WME $w): void {
        $newToken = new Token($w, $t);
        array_unshift($this->items, $newToken); // Equivalent to push_front
        foreach ($this->children as $child) {
            // In rete1.cpp, BetaMemory activates JoinNode with beta_activation
            $child->beta_activation($newToken);
        }
    }

    public function __toString(): string {
        $tokenStrings = array_map(fn($token) => (string)$token, $this->items);
        return "(beta-memory " . implode(" ", $tokenStrings) . ")";
    }
}
