<?php
function BitSet($value, $flagBit)
{
    return ($value & (1 << $flagBit)) !== 0;
}
