<?php

$triggers = [
    [
        'conditions' => '
             0xhFFFFFF=0xXFFFFFF
            _0xhFFFFFF=19229

            _0xh888888
            _0xH888888

            _0x 000000
            _0x100000
            _0x200000
            _0x300000
            _0x400000
            _0x500000
            _0x600000
            _0x700000
            _0x800000
            _0x900000
            _0xA00000
            _0xB00000
            _0xC00000
            _0xD00000
            _0xE00000
            _0xF00000

            _0xI161616

            _0xW242424
            _0xJ242424

            _0xx323232
            _0xX323232
            _0xG323232

            _0xK111111

            _0xM111111
            _0xN111111
            _0xO111111
            _0xP111111
            _0xQ111111
            _0xR111111
            _0xS111111
            _0xT111111

            _0xL444444
            _0xU444444

            _0xV000000
            _0xY000000
            _0xZ000000

            _P:0x55555
            _R:0x55555
            _A:0x55555
            _B:0x55555
            _C:0x55555
            _D:0x55555
            _N:0x55555
            _O:0x55555
            _M:0x55555
            _Q:0x55555
            _I:0x55555
            _T:0x55555
            _Z:0x55555
            _G:0x55555

            _D0x777777
            _P0x777777
            _B0x777777
            _~0x777777

            _FM999999
            _FF999999
            _FF999999>F98765.4.100
            _F+0.1
            _F-0.1
            _F.1
            _F0
            _F1
            _F0.1
            _F1.2
            _F2.3
            _F3.4
            _F4.5
            _F5.6
            _F6.7
            _F7.8
            _F8.9
            _F9.0

            _V0
            _V2
            _V100
            _V19229
            _V16777215
            ',
        'notes' => [
            ['Address' => '0x000064', 'Note' => "Value"],
            ['Address' => '0x004b1d', 'Note' => "Value"],
            ['Address' => '0xFFFFFF', 'Note' => "

?=\"'äöüß=

0000 - abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz
                1111 - abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz
                2222 - abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz
                3333 - abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz
4444 - abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz
                5555 - abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz
                6666 - abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz
                7777 - abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz
8888 - abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz
9999 - abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz


aaaa - abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz
bbbb - abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz
cccc - abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz
dddd - abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz
eeee - abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz
ffff - abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz"],
        ],
    ],
];
?>
<x-app-layout>
    <h1>Triggers</h1>
    @foreach ($triggers as $trigger)
        @php
        $conditions = $trigger['conditions'];
        $conditions = trim($conditions);
        $conditions = str_replace("\n", '', $conditions);
        echo getAchievementPatchReadableHTML($conditions, $trigger['notes']);
        @endphp
    @endforeach
</x-app-layout>
