<?php

function parseOperand($mem)
{
    $type = '';
    switch ($mem[0]) {
        case 'd': case 'D': $type = 'Delta'; $mem = substr($mem, 1); break;
        case 'p': case 'P': $type = 'Prior'; $mem = substr($mem, 1); break;
        case 'b': case 'B': $type = 'BCD'; $mem = substr($mem, 1); break;
        case '~':           $type = 'Inverted'; $mem = substr($mem, 1); break;
    }

    $size = '';
    $max = strlen($mem);
    if ($max > 3 && $mem[0] == '0' && $mem[1] == 'x') {
        switch ($mem[2]) {
            case 'h': case 'H': $size = '8-bit'; break;
            case ' ':           $size = '16-bit'; break;
            case 'x': case 'X': $size = '32-bit'; break;

            case 'm': case 'M': $size = 'Bit0'; break;
            case 'n': case 'N': $size = 'Bit1'; break;
            case 'o': case 'O': $size = 'Bit2'; break;
            case 'p': case 'P': $size = 'Bit3'; break;
            case 'q': case 'Q': $size = 'Bit4'; break;
            case 'r': case 'R': $size = 'Bit5'; break;
            case 's': case 'S': $size = 'Bit6'; break;
            case 't': case 'T': $size = 'Bit7'; break;
            case 'l': case 'L': $size = 'Lower4'; break;
            case 'u': case 'U': $size = 'Upper4'; break;
            case 'k': case 'K': $size = 'BitCount'; break;
            case 'w': case 'W': $size = '24-bit'; break;
            case 'g': case 'G': $size = '32-bit BE'; break;
            case 'i': case 'I': $size = '16-bit BE'; break;
            case 'j': case 'J': $size = '24-bit BE'; break;

            case '0': case '1': case '2': case '3': case '4':
            case '5': case '6': case '7': case '8': case '9':
            case 'a': case 'b': case 'c': case 'd': case 'e': case 'f':
            case 'A': case 'B': case 'C': case 'D': case 'E': case 'F':
                // no size specified, implied 16-bit. convert to explicit
                $size = '16-bit';
                $mem = substr($mem, 0, 2) . ' ' . substr($mem, 2);
                break;

            default:
                $size = $mem[2];
                break;
        }

        $mem = substr($mem, 3);
    } elseif ($max > 2 && $mem[0] == 'f' || $mem[0] == 'F') {
        switch ($mem[1]) {
            case 'f': case 'F': $size = 'Float'; break;
            case 'm': case 'M': $size = 'MBF32'; break;

            case '+': case '-': case '.':
            case '0': case '1': case '2': case '3': case '4':
            case '5': case '6': case '7': case '8': case '9':
                $type = 'Float';
                $count = 1;
                if ($mem[1] == '+' || $mem[1] == '-') {
                    $count++;
                }
                while ($count < $max && (ctype_digit($mem[$count]) || $mem[$count] == '.')) {
                    $count++;
                }

                $value = substr($mem, 1, $count - 1); // ignore 'f'
                $mem = substr($mem, $count);
                return [$type, $size, $value, $mem];

            default: $size = $mem[1]; break;
        }

        $mem = substr($mem, 2);
    } elseif ($max > 1 && $mem[0] == 'h' || $mem[0] == 'H') {
        $type = 'Value';

        $mem = substr($mem, 1);
        $count = 0;
        $max = strlen($mem);
        while ($count < $max && ctype_alnum($mem[$count])) {
            $count++;
        }

        $value = '0x' . str_pad(substr($mem, 0, $count), 6, '0', STR_PAD_LEFT);
        $mem = substr($mem, $count);
        return [$type, $size, $value, $mem];
    } else {
        $type = 'Value';
        if ($mem[0] == 'v' || $mem[0] == 'V') {
            $mem = substr($mem, 1);
        }
        $count = 0;
        if ($mem[0] == '+' || $mem[0] == '-') {
            $count++;
        }
        $max = strlen($mem);
        while ($count < $max && ctype_digit($mem[$count])) {
            $count++;
        }
        if ($count < $max && $mem[$count] == '.') {
            $hitsStart = $count;
            $count++;
            while ($count < $max && ctype_digit($mem[$count])) {
                $count++;
            }
            if ($count < $max && $mem[$count] == '.') {
                $count = $hitsStart;
            } else {
                $size = 'Float';
            }
        }

        $value = '0x' . str_pad(dechex((int) substr($mem, 0, $count)), 6, '0', STR_PAD_LEFT);
        $mem = substr($mem, $count);
        return [$type, $size, $value, $mem];
    }

    if (!$type) {
        $type = 'Mem';
    }

    $count = 0;
    $max = strlen($mem);
    while ($count < $max && ctype_alnum($mem[$count])) {
        $count++;
    }

    $address = '0x' . str_pad(substr($mem, 0, $count), 6, '0', STR_PAD_LEFT);
    $mem = substr($mem, $count);
    return [$type, $size, $address, $mem];
}

function isScalerOperator($cmp)
{
    switch ($cmp) {
        case '*':
        case '/':
        case '&':
            return true;

        default:
            return false;
    }
}

function parseCondition($mem)
{
    $flag = '';
    $lType = '';
    $lSize = '';
    $lMemory = '';
    $cmp = '';
    $rType = '';
    $rSize = '';
    $rMemVal = '';
    $hits = '';
    $scalable = false;

    if ($mem[1] == ':') {
        switch ($mem[0]) {
            case 'p': case 'P': $flag = 'Pause If'; break;
            case 'r': case 'R': $flag = 'Reset If'; break;
            case 'a': case 'A': $flag = 'Add Source'; $scalable = true; break;
            case 'b': case 'B': $flag = 'Sub Source'; $scalable = true; break;
            case 'c': case 'C': $flag = 'Add Hits'; break;
            case 'd': case 'D': $flag = 'Sub Hits'; break;
            case 'n': case 'N': $flag = 'And Next'; break;
            case 'o': case 'O': $flag = 'Or Next'; break;
            case 'm': case 'M': $flag = 'Measured'; break;
            case 'q': case 'Q': $flag = 'Measured If'; break;
            case 'i': case 'I': $flag = 'Add Address'; $scalable = true; break;
            case 't': case 'T': $flag = 'Trigger'; break;
            case 'z': case 'Z': $flag = 'Reset Next If'; break;
            case 'g': case 'G': $flag = 'Measured %'; break;
            default: $flag = $mem[0]; break;
        }

        $mem = substr($mem, 2);
    }

    [$lType, $lSize, $lMemory, $mem] = parseOperand($mem);

    if (strlen($mem) == 0) {
        // no operator
    } elseif ($scalable && !isScalerOperator($mem[0])) {
        $mem = ''; // non-scaler operator ignored for scalable operations
    } else {
        $cmp = $mem[0];
        $cmplen = 1;
        switch ($mem[0]) {
            case '=':
                if ($mem[1] == '=') {
                    $cmplen = 2;
                }
                break;

            case '!':
                if ($mem[1] == '=') {
                    $cmp = '!=';
                    $cmplen = 2;
                }
                break;

            case '<':
                if ($mem[1] == '=') {
                    $cmp = '<=';
                    $cmplen = 2;
                }
                break;

            case '>':
                if ($mem[1] == '=') {
                    $cmp = '>=';
                    $cmplen = 2;
                }
                break;
        }
        $mem = substr($mem, $cmplen);

        [$rType, $rSize, $rMemVal, $mem] = parseOperand($mem);

        $hits = '0';
        if (strlen($mem) > 0 && ($mem[0] == '(' || $mem[0] == '.')) {
            $hits = substr($mem, 1, strlen($mem) - 2);
        }
    }

    return [$flag, $lType, $lSize, $lMemory, $cmp, $rType, $rSize, $rMemVal, $hits];
}

function getNoteForAddress($memNotes, $address)
{
    foreach ($memNotes as $nextMemNote) {
        if ($nextMemNote['Address'] === $address) {
            return $nextMemNote['Note'];
        }
    }

    return null;
}

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
            [$flag, $lType, $lSize, $lMemory, $cmp, $rType, $rSize, $rMemVal, $hits] = parseCondition($reqs[$j]);

            $lTooltip = '';
            if ($lSize) {
                $lNote = getNoteForAddress($memNotes, $lMemory);
                if ($lNote) {
                    $lTooltip = " title=\"" . htmlspecialchars($lNote) . "\"";
                    $codeNotes[$lMemory] = '<strong><u>' . $lMemory . '</u></strong>: ' . htmlspecialchars($lNote);
                }
            }

            $res .= "\n<tr>\n  <td>" . ($j + 1) . "</td>";
            $res .= "\n  <td> " . $flag . " </td>";
            $res .= "\n  <td> " . $lType . " </td>";
            $res .= "\n  <td> " . $lSize . " </td>";
            $res .= "\n  <td" . $lTooltip . "> " . $lMemory . " </td>";
            if (!$cmp) {
                $res .= "\n  <td colspan=5 style='text-align: center'> </td>";
            } else {
                $rTooltip = '';
                if ($rSize) {
                    $rNote = getNoteForAddress($memNotes, $rMemVal);
                    if ($rNote) {
                        $rTooltip = " title=\"" . htmlspecialchars($rNote) . "\"";
                        $codeNotes[$rMemVal] = '<strong><u>' . $rMemVal . '</u></strong>: ' . htmlspecialchars($rNote);
                    }
                }

                $res .= "\n  <td> " . htmlspecialchars($cmp) . " </td>";
                $res .= "\n  <td> " . $rType . " </td>";
                $res .= "\n  <td> " . $rSize . " </td>";
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
