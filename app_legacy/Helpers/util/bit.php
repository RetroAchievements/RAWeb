<?php

declare(strict_types=1);

function BitSet(int $value, int $flagBit): bool
{
    return ($value & (1 << $flagBit)) !== 0;
}
