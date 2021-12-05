<?php

function getAchievementPatchReadableHTML($mem, $memNotes)
{
    $tableHeader = '
    <tr>
      <th>ID</th>
      <th>Flag</th>
      <th>Type</th>
      <th>Size</th>
      <th>Memory</th>
      <th>Cmp</th>
      <th>Type</th>
      <th>Size</th>
      <th>Mem/Val</th>
      <th>Hits</th>
    </tr>';

    $specialFlags = [
        'R' => 'Reset If',
        'P' => 'Pause If',
        'A' => 'Add Source',
        'B' => 'Sub Source',
        'C' => 'Add Hits',
        'N' => 'And Next',
        'O' => 'Or Next',
        'M' => 'Measured',
        'Q' => 'Measured If',
        'I' => 'Add Address',
        'Z' => 'Reset Next If',
        'D' => 'Sub Hits',
        'T' => 'Trigger',
        '' => '',
    ];

    $memSize = [
        '0xM' => 'Bit0',
        '0xN' => 'Bit1',
        '0xO' => 'Bit2',
        '0xP' => 'Bit3',
        '0xQ' => 'Bit4',
        '0xR' => 'Bit5',
        '0xS' => 'Bit6',
        '0xT' => 'Bit7',
        '0xL' => 'Lower4',
        '0xU' => 'Upper4',
        '0xH' => '8-bit',
        '0xW' => '24-bit',
        '0xK' => 'BitCount',
        '0xX' => '32-bit', // needs to be before the 16bits below to make the RegEx work
        '0x ' => '16-bit',
        '0x' => '16-bit',
        '' => '',
    ];

    $memTypes = [
        'd' => 'Delta',
        'p' => 'Prior',
        'm' => 'Mem',
        'v' => 'Value',
        'b' => 'BCD',
        '' => '',
    ];

    // kudos to user "stt" for showing that it's possible to parse MemAddr with regex
    $operandRegex = '(d|p|b)?(' . implode('|', array_keys($memSize)) . ')?([0-9a-f]*)';
    $memRegex = '/(?:([' . implode(
        '',
        array_keys($specialFlags)
    ) . ']):)?' . $operandRegex . '(<=|>=|<|>|=|!=|\*|\/|&|)' . $operandRegex . '(?:[(.](\\d+)[).])?/';
    // memRegex is this monster:
    // (?:([RPABCNOMQIZDT]):)?(d|p|b)?(0xM|0xN|0xO|0xP|0xQ|0xR|0xS|0xT|0xL|0xU|0xH|0xW|0xK|0xX|0x |0x|)?([0-9a-f]*)(<=|>=|<|>|=|!=|\*|\/|&|)(d|p|b)?(0xM|0xN|0xO|0xP|0xQ|0xR|0xS|0xT|0xL|0xU|0xH|0xW|0xK|0xX|0x |0x|)?([0-9a-f]*)(?:[(.](\d+)[).])?/
    // I was about to add comments explaining this long RegEx, but realized that the best way
    // is to copy the regex string and paste it in the proper field at https://regex101.com/

    $res = "\n<table>";

    // separating CoreGroup and AltGroups
    $groups = preg_split("/(?<!0x)S/", $mem);
    for ($i = 0; $i < count($groups); $i++) {
        $res .= "<tr><td colspan=10><p style='text-align: center'><strong>";
        $res .= $i === 0 ? "Core Group" : "Alt Group $i";
        $res .= "</p></strong></td></tr>\n";
        $res .= $tableHeader;

        $codeNotes = [];
        // iterating through the requirements
        $reqs = explode('_', $groups[$i]);
        for ($j = 0; $j < count($reqs); $j++) {
            preg_match_all($memRegex, $reqs[$j], $parsedReq);
            $flag = $parsedReq[1][0];
            $lType = $parsedReq[2][0];
            $lSize = $parsedReq[3][0];
            $lMemory = $parsedReq[4][0];
            $cmp = $parsedReq[5][0];
            $rType = $parsedReq[6][0];
            $rSize = $parsedReq[7][0];
            $rMemVal = $parsedReq[8][0];
            $hits = $parsedReq[9][0];

            $lMemory = '0x' . str_pad(($lSize ? $lMemory : dechex($lMemory)), 6, '0', STR_PAD_LEFT);
            $rMemVal = '0x' . str_pad(($rSize ? $rMemVal : dechex($rMemVal)), 6, '0', STR_PAD_LEFT);
            $hits = $hits ? $hits : "0";
            if ($lType !== "d" && $lType !== "p" && $lType !== "b") {
                $lType = $lSize === '' ? 'v' : 'm';
            }
            if ($rType !== "d" && $rType !== "p" && $rType !== "b") {
                $rType = $rSize === '' ? 'v' : 'm';
            }

            $lTooltip = $rTooltip = null;
            foreach ($memNotes as $nextMemNote) {
                if ($nextMemNote['Address'] === $lMemory) {
                    $lTooltip = " title=\"" . htmlspecialchars($nextMemNote['Note']) . "\"";
                    $codeNotes[$lMemory] = '<strong><u>' . $lMemory . '</u></strong>: ' . htmlspecialchars($nextMemNote['Note']);
                }

                if ($rSize && $nextMemNote['Address'] === $rMemVal) {
                    $rTooltip = " title=\"" . htmlspecialchars($nextMemNote['Note']) . "\"";
                    $codeNotes[$rMemVal] = '<strong><u>' . $rMemVal . '</u></strong>: ' . htmlspecialchars($nextMemNote['Note']);
                }

                if ($lTooltip && $rTooltip) {
                    break;
                }
            }

            $res .= "\n<tr>\n  <td>" . ($j + 1) . "</td>";
            $res .= "\n  <td> " . $specialFlags[$flag] . " </td>";
            $res .= "\n  <td> " . $memTypes[$lType] . " </td>";
            $res .= "\n  <td> " . $memSize[$lSize] . " </td>";
            $res .= "\n  <td" . $lTooltip . "> " . $lMemory . " </td>";
            if (($flag == 'A' || $flag == 'B' || $flag == 'I') && ($cmp != '*' && $cmp != '/' && $cmp != '&')) {
                $res .= "\n  <td colspan=5 style='text-align: center'> </td>";
            } else {
                $res .= "\n  <td> " . htmlspecialchars($cmp) . " </td>";
                $res .= "\n  <td> " . $memTypes[$rType] . " </td>";
                $res .= "\n  <td> " . $memSize[$rSize] . " </td>";
                $res .= "\n  <td" . $rTooltip . "> " . $rMemVal . " </td>";
                $res .= "\n  <td> (" . $hits . ") </td>";
            }
            $res .= "\n</tr>\n";
        }
        $res .= "<tr><td colspan=10><ul><small>";
        foreach ($codeNotes as $nextCodeNote) {
            $res .= "<li>" . $nextCodeNote . "</li>\n";
        }
        $res .= "</small></ul></td></tr>";
    }
    $res .= "\n</table>\n";

    return $res;
}
