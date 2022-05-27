<?php

function BitSet($value, $flagBit): bool
{
    return ($value & (1 << $flagBit)) !== 0;
}
