<?php

declare(strict_types=1);

require_once 'src/WME.php';
require_once 'src/WMEFieldType.php';
require_once 'src/Token.php';
require_once 'src/AlphaMemory.php';
require_once 'src/ConstTestNode.php';
require_once 'src/TestAtJoinNode.php';
require_once 'src/BetaMemory.php';
require_once 'src/JoinNode.php';
require_once 'src/ProductionNode.php';
require_once 'src/Rete.php';
require_once 'src/FieldType.php';
require_once 'src/Field.php';
require_once 'src/Condition.php';

// Basic assertion function for testing
function assert_true(bool $condition, string $message = "Assertion failed"): void {
    if (!$condition) {
        echo "Assertion Failed: " . $message . "
";
        // Optionally, throw an exception or exit
        // throw new Exception($message);
    } else {
        echo "Assertion Passed: " . $message . "
";
    }
}

// Test 1: Add production, then add WMEs
function test1(): void {
    echo "====test1:====
";
    $rete = new Rete();

    echo "adding production
";
    $p = $rete->add_production(
        [new Condition(Field::var("x"), Field::constant("on"), Field::var("y"))],
        "prod1"
    );
    echo "added production
";

    $rete->addWME(new WME("B1", "on", "B2"));
    assert_true(count($p->matched_items) === 1, "Test1: Production items count after 1st WME");

    $rete->addWME(new WME("B1", "on", "B3"));
    assert_true(count($p->matched_items) === 2, "Test1: Production items count after 2nd WME");

    echo "====
";
}

// Test 2: Add WMEs, then add production
function test2(): void {
    echo "====test2:====
";
    $rete = new Rete();

    $rete->addWME(new WME("B1", "on", "B2"));
    $rete->addWME(new WME("B1", "on", "B3"));

    $p = $rete->add_production(
        [new Condition(Field::var("x"), Field::constant("on"), Field::var("y"))],
        "prod1"
    );

    assert_true(count($p->matched_items) === 2, "Test2: Production items count");
    echo "====
";
}

// Test 3: Add WMEs (including non-matching), then add production
function test3(): void {
    echo "====test3:====
";
    $rete = new Rete();

    $rete->addWME(new WME("B1", "on", "B2"));
    $rete->addWME(new WME("B1", "on", "B3"));
    $rete->addWME(new WME("B1", "color", "red")); // Non-matching for this specific production

    $p1 = $rete->add_production(
        [new Condition(Field::var("x"), Field::constant("on"), Field::var("y"))],
        "prod1"
    );
    assert_true(count($p1->matched_items) === 2, "Test3: Production items count");
    echo "====
";
}

// Test 5: Production with 2 conditions. Add WMEs, then production.
// (B1 on B2) (B2 left-of B3) should join.
function test5(): void {
    echo "====test5:====
";
    $rete = new Rete();

    $rete->addWME(new WME("B1", "on", "B2"));     // w1
    $rete->addWME(new WME("B1", "on", "B3"));     // w2 (y=B3, won't join with w3's B2)
    $rete->addWME(new WME("B2", "left-of", "B3"));// w3 (y=B2)

    $conditions = [
        new Condition(Field::var("x"), Field::constant("on"), Field::var("y")),      // C1: (<x> ^on <y>)
        new Condition(Field::var("y"), Field::constant("left-of"), Field::var("z")) // C2: (<y> ^left-of <z>)
    ];

    $p1 = $rete->add_production($conditions, "prod1 (2 conditions)");

    // Expected match: (B1 on B2) then (B2 left-of B3)
    // Token should be: ((B1 on B2) -> (B2 left-of B3))
    assert_true(count($p1->matched_items) === 1, "Test5: Production items count for 2 conditions");

    if (count($p1->matched_items) === 1) {
        $token = $p1->matched_items[0];
        // Verify the token structure
        // Last WME in token is (B2 left-of B3)
        assert_true((string)$token->wme === "(B2 left-of B3)", "Test5: Token's last WME content");
        if ($token->parent !== null) {
            // Parent WME in token is (B1 on B2)
            assert_true((string)$token->parent->wme === "(B1 on B2)", "Test5: Token's parent WME content");
        } else {
            assert_true(false, "Test5: Token parent is null, but expected structure");
        }
    }
    echo "====
";
}

// Test 6: Production with 2 conditions. Add production, then WMEs.
// (B1 on B2) (B2 left-of B3) should join.
function test6(): void {
    echo "====test6:====
";
    $rete = new Rete();

    $conditions = [
        new Condition(Field::var("x"), Field::constant("on"), Field::var("y")),
        new Condition(Field::var("y"), Field::constant("left-of"), Field::var("z"))
    ];

    $p1 = $rete->add_production($conditions, "prod1 (2 conditions, prod first)");

    $rete->addWME(new WME("B1", "on", "B2"));
    // At this point, p1->matched_items should be 0 because the second condition is not met.
    assert_true(count($p1->matched_items) === 0, "Test6: Production items count after 1st relevant WME");

    $rete->addWME(new WME("B1", "on", "B3")); // This WME won't complete the chain with the next WME
    assert_true(count($p1->matched_items) === 0, "Test6: Production items count after non-chaining WME");

    $rete->addWME(new WME("B2", "left-of", "B3")); // This WME should complete the chain with (B1 on B2)
    assert_true(count($p1->matched_items) === 1, "Test6: Production items count after all WMEs");

    if (count($p1->matched_items) === 1) {
        $token = $p1->matched_items[0];
        assert_true((string)$token->wme === "(B2 left-of B3)", "Test6: Token's last WME content");
        if ($token->parent !== null) {
            assert_true((string)$token->parent->wme === "(B1 on B2)", "Test6: Token's parent WME content");
        } else {
            assert_true(false, "Test6: Token parent is null, but expected structure");
        }
    }
    echo "====
";
}

// Test from paper (simplified, focusing on a few conditions from rete1.cpp's test_from_paper)
// Original C++ test_from_paper has many conditions, some of which might not lead to matches with the given WMEs.
// Let's test a sequence that *should* match.
// C1: (<x> ^on <y>)
// C2: (<y> ^left-of <z>)
// C3: (<z> ^color red)
function test_from_paper_simplified(): void {
    echo "====test_from_paper_simplified:====
";
    $rete = new Rete();

    // Add WMEs first
    $wmeB1onB2 = new WME("B1", "on", "B2");
    $wmeB2leftofB3 = new WME("B2", "left-of", "B3");
    $wmeB3colorRed = new WME("B3", "color", "red");

    $rete->addWME($wmeB1onB2);
    $rete->addWME($wmeB2leftofB3);
    $rete->addWME($wmeB3colorRed);

    // Add some other WMEs that shouldn't interfere with this specific production
    $rete->addWME(new WME("B1", "on", "B3")); // Will match C1, but not C2 with B3
    $rete->addWME(new WME("B1", "color", "blue"));

    $conditions = [
        new Condition(Field::var("x"), Field::constant("on"), Field::var("y")),      // C1
        new Condition(Field::var("y"), Field::constant("left-of"), Field::var("z")), // C2
        new Condition(Field::var("z"), Field::constant("color"), Field::constant("red")) // C3
    ];

    $p1 = $rete->add_production($conditions, "prod_paper_simplified");

    assert_true(count($p1->matched_items) === 1, "TestPaperSimplified: Production items count");

    if (count($p1->matched_items) === 1) {
        $token = $p1->matched_items[0];
        // Expected token: ( (B1 on B2) -> (B2 left-of B3) -> (B3 color red) )
        assert_true((string)$token->wme === "(B3 color red)", "TestPaperSimplified: Token's last WME");
        if ($token->parent !== null) {
            assert_true((string)$token->parent->wme === "(B2 left-of B3)", "TestPaperSimplified: Token's middle WME");
            if ($token->parent->parent !== null) {
                assert_true((string)$token->parent->parent->wme === "(B1 on B2)", "TestPaperSimplified: Token's first WME");
            } else {
                assert_true(false, "TestPaperSimplified: Token's first part (parent->parent) is null");
            }
        } else {
            assert_true(false, "TestPaperSimplified: Token's middle part (parent) is null");
        }
    }
    echo "====
";
}


// Run tests
test1();
test2();
test3();
// test4_disabled(); // test4 was disabled in C++ and involved intra-condition checks not explicitly handled.
test5();
test6();
test_from_paper_simplified(); // Using a simplified version for clarity

echo "PHP script execution finished.
";

```
