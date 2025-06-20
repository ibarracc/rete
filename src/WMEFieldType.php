<?php

declare(strict_types=1);

enum WMEFieldType: int {
    case None = -1;
    case Ident = 0;
    case Attr = 1;
    case Val = 2;
    case NumFields = 3;
}
