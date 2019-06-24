<?php

namespace RA;

abstract class Emulators
{
    // reference for the numbers used here:
    // https://github.com/RetroAchievements/RAIntegration/blob/master/src/RA_Interface.h
    // enum EmulatorID
    const RAGens = 0;
    const RAP64 = 1;
    const RASnes9x = 2;
    const RAVBA = 3;
    // const RA_Nester = 4; // unused
    const RANes = 5;
    const RAPCE = 6;
    const RALibretro = 7;
    const RAMeka = 8;
    const RAQUASI88 = 9;
    const RAppleWin = 10;
}
