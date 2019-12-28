<?php

namespace RA;

abstract class Emulators
{
    // reference for the numbers used here:
    // https://github.com/RetroAchievements/RAInterface/blob/master/RA_Interface.h
    // enum EmulatorID
    const RAGens = 0;
    const RAP64 = 1;
    const RASnes9x = 2;
    const RAVBA = 3;
    const RANester = 4; // unused
    const RANes = 5;
    const RAPCE = 6;
    const RALibretro = 7;
    const RAMeka = 8;
    const RAQUASI88 = 9;
    const RAppleWin = 10;
}
