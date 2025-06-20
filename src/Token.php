<?php

declare(strict_types=1);

require_once 'WME.php';

class Token {
    public ?Token $parent; // Items [0..i-1]
    public int $token_chain_ix;
    public WME $wme; // Item i

    public function __construct(WME $wme, ?Token $parent = null) {
        $this->wme = $wme;
        $this->parent = $parent;
        if ($parent === null) {
            $this->token_chain_ix = 0;
        } else {
            $this->token_chain_ix = $parent->token_chain_ix + 1;
        }
    }

    public function index(int $ix): WME {
        if (!($ix >= 0 && $ix <= $this->token_chain_ix)) {
            error_log("Index: " . $ix . " token_chain_ix: " . $this->token_chain_ix . " wme: " . $this->wme);
            throw new OutOfRangeException("Index out of range.");
        }
        if ($ix === $this->token_chain_ix) {
            return $this->wme;
        }
        if ($this->parent === null) {
            // This should not happen if $ix is within bounds and not $this->token_chain_ix
            throw new LogicException("Parent is null but index is not current token's index.");
        }
        return $this->parent->index($ix);
    }

    public function __toString(): string {
        $s = "";
        for ($p = $this; $p !== null; $p = $p->parent) {
            if ($p->wme === null) {
                throw new LogicException("WME is null in Token chain.");
            }
            $s .= $p->wme;
            if ($p->parent !== null) {
                $s .= "->";
            }
        }
        return "(" . $s . ")";
    }
}
