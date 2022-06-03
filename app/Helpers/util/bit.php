<?php

function isBitSet(int|string $value, int $index): bool
{
    return ((int) $value & (1 << $index)) !== 0;
}
