<?php

declare(strict_types=1);

require_once 'BetaMemory.php';
require_once 'Token.php';
require_once 'WME.php';

class ProductionNode extends BetaMemory {
    /** @var list<Token> */
    public array $matched_items = []; // Changed from 'items' to avoid conflict with BetaMemory's items
    public string $rhs;

    public function __construct(string $rhs, JoinNode $parentJoinNode) {
        parent::__construct();
        $this->rhs = $rhs;
        $this->parent = $parentJoinNode; // Set the parent JoinNode
    }

    // Override join_activation from BetaMemory
    public function join_activation(?Token $t, WME $w): void {
        $newToken = new Token($w, $t);
        $this->matched_items[] = $newToken; // Add to its specific list of matched items
        // Output the production firing
        echo "## (PROD " . $newToken . " ~ " . $this->rhs . ") ##
";
        // Production nodes typically don't have children in the Rete network data flow
        // So, no loop through $this->children to call further activations.
    }

    public function __toString(): string {
        return "(production " . $this->rhs . ")";
    }
}
