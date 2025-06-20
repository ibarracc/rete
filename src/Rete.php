<?php

declare(strict_types=1);

require_once 'WME.php';
require_once 'WMEFieldType.php';
require_once 'AlphaMemory.php';
require_once 'ConstTestNode.php';
require_once 'BetaMemory.php';
require_once 'TestAtJoinNode.php';
require_once 'JoinNode.php';
require_once 'ProductionNode.php';
require_once 'Field.php'; // Will be created in a subsequent step
require_once 'Condition.php'; // Will be created in a subsequent step

class Rete {
    public ConstTestNode $alpha_top;
    /** @var list<AlphaMemory> */
    public array $alphamemories = [];
    /** @var list<BetaMemory> */
    public array $betamemories = []; // Includes ProductionNodes
    /** @var list<ConstTestNode> */
    public array $consttestnodes = [];
    /** @var list<JoinNode> */
    public array $joinnodes = [];
    /** @var list<ProductionNode> */
    public array $productions = []; // Specific list for ProductionNodes for convenience
    /** @var list<WME> */
    public array $working_memory = [];

    public function __construct() {
        $this->alpha_top = ConstTestNode::dummy_top();
        $this->consttestnodes[] = $this->alpha_top;
    }

    // Corresponds to: bool const_test_node_activation(ConstTestNode *node, WME *w)
    // and void alpha_memory_activation(AlphaMemory *node, WME *w)
    private function activate_const_test_node(ConstTestNode $node, WME $w): bool {
        echo "activate_const_test_node: Node: " . $node . " | WME: " . $w . "
";

        if ($node->field_to_test !== WMEFieldType::None) {
            if ($w->get_field($node->field_to_test) !== $node->field_must_equal) {
                return false;
            }
        }

        if ($node->output_memory !== null) {
            $this->activate_alpha_memory($node->output_memory, $w);
        }

        foreach ($node->children as $childNode) {
            $this->activate_const_test_node($childNode, $w);
        }
        return true;
    }

    private function activate_alpha_memory(AlphaMemory $am, WME $w): void {
        array_unshift($am->items, $w); // push_front
        echo "activate_alpha_memory: AM: " . $am . " | WME: " . $w . "
";
        foreach ($am->successors as $childJoinNode) {
            $childJoinNode->alpha_activation($w);
        }
    }

    // Corresponds to: void addWME(Rete &r, WME *w)
    public function addWME(WME $w): void {
        $this->working_memory[] = $w;
        $this->activate_const_test_node($this->alpha_top, $w);
    }

    // Corresponds to: ConstTestNode *build_or_share_constant_test_node(Rete &r, ConstTestNode *parent, WMEFieldType f, string sym)
    public function build_or_share_constant_test_node(ConstTestNode $parent, WMEFieldType $f, string $sym): ConstTestNode {
        foreach ($parent->children as $child) {
            if ($child->field_to_test === $f && $child->field_must_equal === $sym) {
                return $child;
            }
        }
        $newNode = new ConstTestNode($f, $sym, null);
        $this->consttestnodes[] = $newNode;
        echo "build_or_share_constant_test_node: newconsttestnode: " . spl_object_hash($newNode) . "
";
        $parent->children[] = $newNode;
        return $newNode;
    }

    // Corresponds to: bool wme_passes_constant_tests(WME *w, Condition c)
    // This will be moved to Condition class or a helper if appropriate, but placed here for now.
    private function wme_passes_constant_tests(WME $w, Condition $c): bool {
        for ($f_idx = 0; $f_idx < WMEFieldType::NumFields->value; ++$f_idx) {
            $fieldEnum = WMEFieldType::from($f_idx);
            if ($c->attrs[$f_idx]->type === FieldType::Const) {
                if ($c->attrs[$f_idx]->v !== $w->get_field($fieldEnum)) {
                    return false;
                }
            }
        }
        return true;
    }

    // Corresponds to: AlphaMemory *build_or_share_alpha_memory_dataflow(Rete &r, Condition c)
    public function build_or_share_alpha_memory_dataflow(Condition $condition): AlphaMemory {
        $currentNode = $this->alpha_top;
        for ($f_idx = 0; $f_idx < WMEFieldType::NumFields->value; ++$f_idx) {
            $fieldEnum = WMEFieldType::from($f_idx);
            if ($condition->attrs[$f_idx]->type === FieldType::Const) {
                $sym = $condition->attrs[$f_idx]->v;
                $currentNode = $this->build_or_share_constant_test_node($currentNode, $fieldEnum, $sym);
            }
        }

        if ($currentNode->output_memory !== null) {
            return $currentNode->output_memory;
        }

        $currentNode->output_memory = new AlphaMemory();
        $this->alphamemories[] = $currentNode->output_memory;
        echo "build_or_share_alpha_memory_dataflow: currentNode->output_memory: " . spl_object_hash($currentNode->output_memory) . "
";

        foreach ($this->working_memory as $w) {
            if ($this->wme_passes_constant_tests($w, $condition)) {
                // Directly call activate_alpha_memory, similar to C++
                // alpha_memory_activation(currentNode->output_memory, w);
                $this->activate_alpha_memory($currentNode->output_memory, $w);
            }
        }
        return $currentNode->output_memory;
    }

    // Corresponds to: void update_new_node_with_matches_from_above(BetaMemory *beta)
    private function update_new_node_with_matches_from_above(BetaMemory $betaNode): void {
        echo "update_new_node_with_matches_from_above: betaNode: " . spl_object_hash($betaNode) . "
";
        $parentNode = $betaNode->parent; // parent is JoinNode
        if ($parentNode === null) {
             error_log("BetaMemory node has no parent JoinNode during update_new_node_with_matches_from_above");
             return;
        }

        $savedListOfChildren = $parentNode->children;
        $parentNode->children = [$betaNode]; // Temporarily set children to only the new node

        // Push alpha memory through join node.
        // This replicates: for(WME *item : join->amem_src->items) { join->alpha_activation(item); }
        if ($parentNode->amem_src === null) {
            error_log("Parent JoinNode has no amem_src during update_new_node_with_matches_from_above");
            $parentNode->children = $savedListOfChildren; // Restore children
            return;
        }

        foreach ($parentNode->amem_src->items as $itemWME) {
            $parentNode->alpha_activation($itemWME);
        }
        $parentNode->children = $savedListOfChildren; // Restore original children
    }


    // Corresponds to: BetaMemory *build_or_share_beta_memory_node(Rete &r, JoinNode *parent)
    public function build_or_share_beta_memory_node(JoinNode $parentJoinNode): BetaMemory {
        // In rete1.cpp, a JoinNode can only have one BetaMemory child.
        // "for (BetaMemory *child : parent->children) { return child; }"
        // This implies if a child BetaMemory exists, it's reused.
        foreach ($parentJoinNode->children as $child) {
            // Ensure it's a BetaMemory and not a ProductionNode if we only want "pure" BetaMemory
            if ($child instanceof BetaMemory && !($child instanceof ProductionNode)) {
                return $child;
            }
        }

        $newBetaNode = new BetaMemory();
        $this->betamemories[] = $newBetaNode;
        $newBetaNode->parent = $parentJoinNode;
        echo "build_or_share_beta_memory_node: newBeta: " . spl_object_hash($newBetaNode) . " | parent: " . spl_object_hash($newBetaNode->parent) . "
";
        $parentJoinNode->children[] = $newBetaNode;
        $this->update_new_node_with_matches_from_above($newBetaNode);
        return $newBetaNode;
    }

    // Corresponds to: JoinNode *build_or_share_join_node(Rete &r, BetaMemory *bmem, AlphaMemory *amem, vector<TestAtJoinNode> tests)
    public function build_or_share_join_node(?BetaMemory $betaMemory, AlphaMemory $alphaMemory, array $tests): JoinNode {
        // Sharing/reuse of JoinNodes in rete1.cpp is effectively disabled or very simple.
        // The C++ code `if (bmem ) { bmem->children.push_back(newjoin); }`
        // suggests a new join node is always created and added.
        // Let's check if an identical join node (same alpha memory, same tests, same beta memory parent) already exists as a child of $betaMemory.
        if ($betaMemory !== null) {
            foreach ($betaMemory->children as $existingJoinNode) {
                $testsMatch = true;
                if (count($existingJoinNode->tests) === count($tests)) {
                    for ($i = 0; $i < count($tests); $i++) {
                        if (!$existingJoinNode->tests[$i]->equals($tests[$i])) {
                            $testsMatch = false;
                            break;
                        }
                    }
                } else {
                    $testsMatch = false;
                }

                if ($existingJoinNode->amem_src === $alphaMemory && $testsMatch) {
                    // Found an existing join node that matches criteria
                    return $existingJoinNode;
                }
            }
        }


        $newJoinNode = new JoinNode($alphaMemory, $betaMemory);
        $newJoinNode->tests = $tests;
        $this->joinnodes[] = $newJoinNode;

        $alphaMemory->successors[] = $newJoinNode; // push_front in C++
        if ($betaMemory !== null) {
            $betaMemory->children[] = $newJoinNode;
        }

        echo "build_or_share_join_node: newJoinNode: " . spl_object_hash($newJoinNode) .
             " | bmem_src: " . ($betaMemory ? spl_object_hash($betaMemory) : 'null') .
             " | amem_src: " . spl_object_hash($alphaMemory) . "
";

        // In rete1.cpp, there isn't an explicit "update_new_node_with_matches_from_above" for JoinNodes.
        // Matches are propagated when WMEs are added (for alpha) or tokens arrive (for beta).
        // However, if the join node is created *after* its inputs (AM/BM) are populated,
        // we might need to manually propagate existing matches.

        // Propagate from BetaMemory parent if it exists and has items
        if ($betaMemory !== null) {
            foreach($betaMemory->items as $token) {
                $newJoinNode->beta_activation($token);
            }
        }
        // Propagate from AlphaMemory source if it has items
        // This is tricky because beta_activation on the join node expects a token,
        // but alpha_activation expects a WME.
        // The original `update_new_node_with_matches_from_above(BetaMemory *beta)` handles the BetaMemory side.
        // For the JoinNode, its `alpha_activation` and `beta_activation` handle new incoming data.
        // When a JoinNode is created, its AlphaMemory source (`amem_src`) might already have WMEs.
        // And its BetaMemory source (`bmem_src`) might already have Tokens.
        // The `alpha_activation` of the AM on the new JoinNode should be triggered for existing WMEs.
        // The `beta_activation` of the BM on the new JoinNode should be triggered for existing Tokens.

        // Let's refine the propagation for a new JoinNode:
        // When a new join node is created:
        // 1. Existing tokens from its bmem_src should flow through its beta_activation.
        // 2. Existing WMEs from its amem_src should flow through its alpha_activation.

        if ($newJoinNode->bmem_src !== null) {
            foreach ($newJoinNode->bmem_src->items as $token) {
                $newJoinNode->beta_activation($token); // Pass existing tokens
            }
        }
        // For amem_src, if bmem_src is null (first join), alpha_activation will pass null token.
        // If bmem_src is not null, alpha_activation will iterate bmem_src tokens.
        // So, we just need to trigger alpha_activation for all WMEs in amem_src.
        foreach ($newJoinNode->amem_src->items as $wme) {
            $newJoinNode->alpha_activation($wme); // Pass existing WMEs
        }


        return $newJoinNode;
    }

    // Corresponds to: void lookup_earlier_cond_with_field(...)
    // This will be created when Field.php and Condition.php are defined.
    // For now, placeholder:
    private function lookup_earlier_cond_with_field(array $earlierConds, string $v, &$i, &$f2_idx): void {
        // To be implemented in step 4
        $i = -1; $f2_idx = -1; // Default not found
        $condIdx = count($earlierConds) - 1;
        foreach (array_reverse($earlierConds) as $cond) {
            /** @var Condition $cond */
            for ($fieldNum = 0; $fieldNum < WMEFieldType::NumFields->value; ++$fieldNum) {
                // $fieldEnum = WMEFieldType::from($fieldNum); // Not needed for comparison with $cond->attrs
                if ($cond->attrs[$fieldNum]->type === FieldType::Var && $cond->attrs[$fieldNum]->v === $v) {
                    $i = $condIdx;
                    $f2_idx = $fieldNum;
                    return;
                }
            }
            $condIdx--;
        }
    }

    // Corresponds to: vector<TestAtJoinNode> get_join_tests_from_condition(...)
    // This will be created when Field.php and Condition.php are defined.
    // For now, placeholder:
    public function get_join_tests_from_condition(Condition $c, array $earlierConds): array {
        // To be implemented in step 4
        $result = [];
        for ($f = 0; $f < WMEFieldType::NumFields->value; ++$f) {
            $fieldEnum = WMEFieldType::from($f); // e.g., WMEFieldType::Ident
            if ($c->attrs[$f]->type === FieldType::Var) {
                $v = $c->attrs[$f]->v;
                $i = -1; $f2_idx = -1;
                $this->lookup_earlier_cond_with_field($earlierConds, $v, $i, $f2_idx);

                if ($i !== -1 && $f2_idx !== -1) {
                    $field2Enum = WMEFieldType::from($f2_idx);
                    $result[] = new TestAtJoinNode($fieldEnum, $field2Enum, $i);
                }
            }
        }
        return $result;
    }

    // Corresponds to: ProductionNode *add_production(Rete &r, vector<Condition> lhs, string rhs)
    public function add_production(array $lhsConditions, string $rhs): ProductionNode {
        /** @var array<Condition> $earlierConds */
        $earlierConds = [];
        /** @var ?BetaMemory $currentBetaMemory */
        $currentBetaMemory = null; // For the very first join, there's no preceding BetaMemory

        // Handle the first condition to create the first JoinNode
        if (empty($lhsConditions)) {
            throw new InvalidArgumentException("LHS conditions cannot be empty for a production.");
        }

        $firstCondition = $lhsConditions[0];
        $tests = $this->get_join_tests_from_condition($firstCondition, $earlierConds);
        $alphaMemory = $this->build_or_share_alpha_memory_dataflow($firstCondition);

        // The first JoinNode might not have a BetaMemory source.
        $currentJoinNode = $this->build_or_share_join_node(null, $alphaMemory, $tests);
        $earlierConds[] = $firstCondition;

        // Loop for subsequent conditions
        for ($idx = 1; $idx < count($lhsConditions); ++$idx) {
            $condition = $lhsConditions[$idx];
            // The current JoinNode's output feeds into a new BetaMemory
            $currentBetaMemory = $this->build_or_share_beta_memory_node($currentJoinNode);

            $tests = $this->get_join_tests_from_condition($condition, $earlierConds);
            $alphaMemory = $this->build_or_share_alpha_memory_dataflow($condition);

            // This new JoinNode takes input from the new BetaMemory and a new AlphaMemory
            $currentJoinNode = $this->build_or_share_join_node($currentBetaMemory, $alphaMemory, $tests);
            $earlierConds[] = $condition;
        }

        // After all conditions, the last JoinNode feeds into a ProductionNode
        $prodNode = new ProductionNode($rhs, $currentJoinNode);
        $this->productions[] = $prodNode;
        $this->betamemories[] = $prodNode; // ProductionNode is a type of BetaMemory

        // Link ProductionNode as a child of the last JoinNode
        $currentJoinNode->children[] = $prodNode;
        // Ensure the parent is set (already done in ProductionNode constructor)
        // $prodNode->parent = $currentJoinNode; // This is JoinNode as per rete1.cpp structure for ProductionNode

        echo "add_production: prod: " . spl_object_hash($prodNode) . " | parent JoinNode: " . spl_object_hash($currentJoinNode) . "
";

        // Update the new ProductionNode with matches from above.
        // This means propagating tokens from its parent JoinNode.
        // The C++ code calls `update_new_node_with_matches_from_above(prod)`
        // which for a ProductionNode (a BetaMemory) would call its `join_activation`
        // by simulating flow from its parent JoinNode.
        $this->update_new_node_with_matches_from_above($prodNode);

        return $prodNode;
    }
}
