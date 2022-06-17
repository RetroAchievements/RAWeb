<?php

namespace RA;

// TODO split requests
abstract class GameAction
{
    public const ModifyTitle = 1;

    public const UnlinkAllHashes = 2;

    public const UnlinkHash = 3;

    public const UpdateHash = 4;
}
