<?php

function dump(...$args): void
{
    print_r(...$args);
}

function dd(...$args): void
{
    dump(...$args);
    exit();
}
