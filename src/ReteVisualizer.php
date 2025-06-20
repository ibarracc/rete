<?php

declare(strict_types=1);

require_once 'Rete.php';
require_once 'WME.php';
require_once 'Token.php';
require_once 'AlphaMemory.php';
require_once 'ConstTestNode.php';
require_once 'BetaMemory.php';
require_once 'JoinNode.php';
require_once 'ProductionNode.php';
require_once 'WMEFieldType.php';

class ReteVisualizer {

    private int $uid_counter = 0;
    /** @var array<mixed, string> */
    private array $node_ids; // Map object hash or unique id to DOT node name

    private function get_node_id(mixed $node_obj): string {
        $hash = spl_object_hash($node_obj);
        if (!isset($this->node_ids[$hash])) {
            $this->node_ids[$hash] = 'node' . ($this->uid_counter++);
        }
        return $this->node_ids[$hash];
    }

    private function escape_html_label(string $label): string {
        // For DOT HTML-like labels, some characters need escaping.
        // Simplified: focusing on direct string attributes for now, not full HTML.
        // If using actual HTML tables, ensure proper escaping or structure.
        return htmlspecialchars($label, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function wme_to_string_for_label(WME $wme): string {
        // Similar to WME::__toString but might need different formatting for labels
        return (string)$wme;
    }

    private function token_to_string_for_label(Token $token): string {
        $s = "";
        for ($p = $token; $p !== null; $p = $p->parent) {
            $s .= $this->wme_to_string_for_label($p->wme);
            if ($p->parent !== null) {
                $s .= " -> "; // Use " -> " for readability in DOT label
            }
        }
        return "(" . $s . ")";
    }

    private function table1d_to_html_label(string $heading, array $data): string {
        $html = '<TABLE BORDER="0" CELLBORDER="1" CELLSPACING="0" CELLPADDING="4">';
        $html .= '<TR><TD BGCOLOR="lightblue"><B>' . $this->escape_html_label($heading) . '</B></TD></TR>';
        foreach ($data as $item) {
            $html .= '<TR><TD BGCOLOR="lightgrey">' . $this->escape_html_label($item) . '</TD></TR>';
        }
        $html .= '</TABLE>';
        return $html;
    }

    public function generate_dot_string(Rete $rete): string {
        $this->uid_counter = 0;
        $this->node_ids = [];
        $dot = "digraph G {
";
        $dot .= "  rankdir=TB;
";
        $dot .= "  fontname="monospace";
";
        $dot .= "  overlap=compress;
";
        $dot .= "  concentrate=true;

";

        // Subgraph for WMEs (source)
        $dot .= "  subgraph cluster_wmes {
";
        $dot .= "    label="Working Memory";
";
        $dot .= "    style=filled;
";
        $dot .= "    color=lightgrey;
";
        $dot .= "    node [shape=box, style=filled, color=white];
";
        $wme_node_id = "wmes_source_node";
        $wme_data = [];
        foreach($rete->working_memory as $idx => $wme) {
            $wme_data[] = "w" . $idx . ": " . $this->wme_to_string_for_label($wme);
        }
        $dot .= "    "" . $wme_node_id . "" [label=<" . $this->table1d_to_html_label("WMEs", $wme_data) . ">, shape=none, margin=0];
";
        $dot .= "  }

";


        // Alpha Network
        $dot .= "  subgraph cluster_alpha_network {
";
        $dot .= "    label="Alpha Network";
";
        $dot .= "    color="#CCCCCC";
";
        $dot .= "    penwidth=3;
";

        // Alpha Top (dummy)
        $alpha_top_id = $this->get_node_id($rete->alpha_top);
        $dot .= "    "" . $alpha_top_id . "" [label="AlphaTop (dummy)", shape=box];
";
        $dot .= "    "" . $wme_node_id . "" -> "" . $alpha_top_id . "" [style=dashed, label="input"];
";


        foreach ($rete->consttestnodes as $ctn) {
            if ($ctn === $rete->alpha_top) continue; // Already defined
            $ctn_id = $this->get_node_id($ctn);
            $label = "(const-test " . $ctn->field_to_test->name . " =? " . $this->escape_html_label($ctn->field_must_equal) . ")";
            $dot .= "    "" . $ctn_id . "" [label="" . $this->escape_html_label($label) . "", shape=box];
";
        }

        foreach ($rete->alphamemories as $idx => $am) {
            $am_id = $this->get_node_id($am);
            $items = [];
            foreach ($am->items as $wme) $items[] = $this->wme_to_string_for_label($wme);
            $label = $this->table1d_to_html_label("AlphaMemory " . $idx, $items);
            $dot .= "    "" . $am_id . "" [label=<" . $label . ">, shape=none, margin=0];
";
        }
        $dot .= "  }

";


        // Beta Network
        $dot .= "  subgraph cluster_beta_network {
";
        $dot .= "    label="Beta Network";
";
        $dot .= "    color="#CCCCCC";
";
        $dot .= "    penwidth=3;
";

        foreach ($rete->betamemories as $idx => $bm) {
            if ($bm instanceof ProductionNode) continue; // Handle ProductionNodes separately
            $bm_id = $this->get_node_id($bm);
            $items = [];
            foreach ($bm->items as $token) $items[] = $this->token_to_string_for_label($token);
            $label = $this->table1d_to_html_label("BetaMemory " . $idx, $items);
            $dot .= "    "" . $bm_id . "" [label=<" . $label . ">, shape=none, margin=0];
";
        }

        foreach ($rete->joinnodes as $idx => $jn) {
            $jn_id = $this->get_node_id($jn);
            $tests = [];
            foreach ($jn->tests as $test) $tests[] = (string)$test; // TestAtJoinNode has __toString
            $label = $this->table1d_to_html_label("JoinNode " . $idx, $tests);
            $dot .= "    "" . $jn_id . "" [label=<" . $label . ">, shape=none, margin=0, color=blue];
"; // color for distinction
        }

        $dot .= "    subgraph cluster_productions {
";
        $dot .= "        label="Productions"; style=filled; color=lightyellow;
";
        foreach ($rete->productions as $idx => $pn) {
            $pn_id = $this->get_node_id($pn);
            $items = [];
            foreach ($pn->matched_items as $token) $items[] = $this->token_to_string_for_label($token);
            $label = $this->table1d_to_html_label("Production: " . $this->escape_html_label($pn->rhs), $items);
            $dot .= "    "" . $pn_id . "" [label=<" . $label . ">, shape=none, margin=0, color=green];
"; // color for distinction
        }
        $dot .= "    }
"; // end cluster_productions
        $dot .= "  }

";


        // Edges
        // Alpha network edges
        foreach ($rete->consttestnodes as $ctn) {
            foreach ($ctn->children as $child_ctn) {
                $dot .= "  "" . $this->get_node_id($ctn) . "" -> "" . $this->get_node_id($child_ctn) . "";
";
            }
            if ($ctn->output_memory !== null) {
                $dot .= "  "" . $this->get_node_id($ctn) . "" -> "" . $this->get_node_id($ctn->output_memory) . "";
";
            }
        }

        // Edges from AlphaMemories to JoinNodes
        foreach ($rete->alphamemories as $am) {
            foreach ($am->successors as $successor_jn) {
                $dot .= "  "" . $this->get_node_id($am) . "" -> "" . $this->get_node_id($successor_jn) . "" [label="AM->JN"];
";
            }
        }

        // Beta network edges
        // Edges from BetaMemories to JoinNodes
        foreach ($rete->betamemories as $bm) {
            if ($bm instanceof ProductionNode) continue;
            foreach ($bm->children as $child_jn) { // children of BetaMemory are JoinNodes
                $dot .= "  "" . $this->get_node_id($bm) . "" -> "" . $this->get_node_id($child_jn) . "" [label="BM->JN"];
";
            }
        }

        // Edges from JoinNodes to BetaMemories (or ProductionNodes)
        foreach ($rete->joinnodes as $jn) {
            foreach ($jn->children as $child_bm_or_pn) { // children of JoinNode are BetaMemory or ProductionNode
                $dot .= "  "" . $this->get_node_id($jn) . "" -> "" . $this->get_node_id($child_bm_or_pn) . "" [label="JN->BM/PN"];
";
            }
        }

        $dot .= "}
";
        return $dot;
    }
}

// Example of how to use it in test_rete.php (do not add this part to ReteVisualizer.php itself):
/*
require_once 'src/ReteVisualizer.php';

// ... (inside a test function after a Rete network is built)
// $visualizer = new ReteVisualizer();
// $dot_output = $visualizer->generate_dot_string($rete);
// file_put_contents("test_name.dot", $dot_output);
// echo "Generated test_name.dot
";
*/

```
