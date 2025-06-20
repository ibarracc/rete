<?php

declare(strict_types=1);

require_once 'AlphaMemory.php';
require_once 'BetaMemory.php';
require_once 'TestAtJoinNode.php';
require_once 'WME.php';
require_once 'Token.php';

class JoinNode {
    public AlphaMemory $amem_src; // Invariant: must be valid
    public ?BetaMemory $bmem_src; // Can be null

    /** @var list<BetaMemory> */
    public array $children = []; // Children are BetaMemory or ProductionNode (which extends BetaMemory)
    /** @var list<TestAtJoinNode> */
    public array $tests = [];

    public function __construct(AlphaMemory $amem_src, ?BetaMemory $bmem_src = null) {
        $this->amem_src = $amem_src;
        $this->bmem_src = $bmem_src;
    }

    public function alpha_activation(WME $w): void {
        if ($this->bmem_src !== null) {
            foreach ($this->bmem_src->items as $t) {
                if (!$this->perform_join_tests($t, $w)) {
                    continue;
                }
                foreach ($this->children as $child) {
                    $child->join_activation($t, $w);
                }
            }
        } else {
            // If bmem_src is null, this is typically the first join node in a sequence.
            // The token passed to children will be based only on the WME from alpha memory.
            foreach ($this->children as $child) {
                $child->join_activation(null, $w);
            }
        }
    }

    public function beta_activation(Token $t): void {
        foreach ($this->amem_src->items as $w) {
            if (!$this->perform_join_tests($t, $w)) {
                continue;
            }
            foreach ($this->children as $child) {
                $child->join_activation($t, $w);
            }
        }
    }

    public function perform_join_tests(?Token $t, WME $w): bool {
        // If bmem_src is null, it implies this is the first join in a chain,
        // meaning there are no prior tokens to test against from a beta memory.
        // Or, if there are no tests, it's a pass.
        if ($this->bmem_src === null || empty($this->tests)) {
             // However, if there are tests defined, and bmem_src is null,
             // this might indicate an issue unless tests are designed to work with a null token.
             // The original C++ code for perform_join_tests in rete1.cpp has:
             // if (!bmem_src) return true;
             // This means if there's no beta memory source, tests are skipped (effectively passing).
             // Let's stick to that logic.
            if ($this->bmem_src === null) return true;
        }

        // If token is null (can happen if bmem_src is null and join_activation passes null for token)
        // and there are tests, this is problematic as tests require a token.
        // The C++ code for perform_join_tests has `assert(amem_src);`
        // and then `WME *wme2 = t->index(test.ix_in_token_of_arg2);`
        // This implies 't' must not be null if tests are to be performed.
        if ($t === null && !empty($this->tests)) {
            // This case should ideally not be reached if logic is correct,
            // as tests imply a token should be present.
            error_log("perform_join_tests called with null token but tests exist.");
            return false;
        }


        foreach ($this->tests as $test) {
            $arg1 = $w->get_field($test->field_of_arg1);
            // If $t is null here, it means we don't have a token from beta memory.
            // This situation occurs for the very first join node that doesn't have a beta memory parent.
            // In such a case, join tests that refer to token fields are not applicable.
            // The original C++ doesn't explicitly guard t for nullness here because perform_join_tests
            // is called in contexts where 't' is expected if bmem_src is not null.
            // If bmem_src is null, perform_join_tests returns true early.
            // So, if we reach here, 't' should be valid if 'bmem_src' was not null.
            if ($t === null) {
                // This should not happen if bmem_src was not null.
                // If bmem_src was null, we should have returned true already.
                // This indicates a potential logic flaw or an unexpected state.
                error_log("perform_join_tests: Token is null unexpectedly.");
                return false; // Or handle as an error
            }
            $wme2 = $t->index($test->ix_in_token_of_arg2);
            $arg2 = $wme2->get_field($test->field_of_arg2);
            if ($arg1 !== $arg2) {
                return false;
            }
        }
        return true;
    }

    public function __toString(): string {
        $testStrings = array_map(fn($test) => (string)$test, $this->tests);
        return "(join " . implode(" ", $testStrings) . ")";
    }
}
