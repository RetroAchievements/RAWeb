<?php

namespace RA;

abstract class Permissions
{
    public const Spam = -2;

    public const Banned = -1;

    public const Unregistered = 0;

    public const Registered = 1;

    public const SuperUser = 2;

    public const Developer = 3;

    public const Admin = 4;

    public const Root = 5;
}
