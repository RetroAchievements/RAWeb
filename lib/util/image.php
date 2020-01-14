<?php

function ConvertBMP2GD($src, $dest = false)
{
    if (!($src_f = fopen($src, "rb"))) {
        return false;
    }
    if (!($dest_f = fopen($dest, "wb"))) {
        return false;
    }
    $header = unpack("vtype/Vsize/v2reserved/Voffset", fread($src_f, 14));
    $info = unpack("Vsize/Vwidth/Vheight/vplanes/vbits/Vcompression/Vimagesize/Vxres/Vyres/Vncolor/Vimportant", fread($src_f, 40));

    extract($info);
    extract($header);

    if ($type != 0x4D42) { // signature "BM"
        return false;
    }

    $palette_size = $offset - 54;
    $ncolor = $palette_size / 4;
    $gd_header = "";
    // true-color vs. palette
    $gd_header .= ($palette_size == 0) ? "\xFF\xFE" : "\xFF\xFF";
    $gd_header .= pack("n2", $width, $height);
    $gd_header .= ($palette_size == 0) ? "\x01" : "\x00";
    if ($palette_size) {
        $gd_header .= pack("n", $ncolor);
    }
    // no transparency
    $gd_header .= "\xFF\xFF\xFF\xFF";

    fwrite($dest_f, $gd_header);

    if ($palette_size) {
        $palette = fread($src_f, $palette_size);
        $gd_palette = "";
        $j = 0;
        while ($j < $palette_size) {
            $b = $palette[$j++];
            $g = $palette[$j++];
            $r = $palette[$j++];
            $a = $palette[$j++];
            $gd_palette .= "$r$g$b$a";
        }
        $gd_palette .= str_repeat("\x00\x00\x00\x00", 256 - $ncolor);
        fwrite($dest_f, $gd_palette);
    }

    $scan_line_size = (($bits * $width) + 7) >> 3;
    $scan_line_align = ($scan_line_size & 0x03) ? 4 - ($scan_line_size & 0x03) : 0;

    for ($i = 0, $l = $height - 1; $i < $height; $i++, $l--) {
        // BMP stores scan lines starting from bottom
        fseek($src_f, $offset + (($scan_line_size + $scan_line_align) * $l));
        $scan_line = fread($src_f, $scan_line_size);
        if ($bits == 24) {
            $gd_scan_line = "";
            $j = 0;
            while ($j < $scan_line_size) {
                $b = $scan_line[$j++];
                $g = $scan_line[$j++];
                $r = $scan_line[$j++];
                $gd_scan_line .= "\x00$r$g$b";
            }
        } else {
            if ($bits == 8) {
                $gd_scan_line = $scan_line;
            } else {
                if ($bits == 4) {
                    $gd_scan_line = "";
                    $j = 0;
                    while ($j < $scan_line_size) {
                        $byte = ord($scan_line[$j++]);
                        $p1 = chr($byte >> 4);
                        $p2 = chr($byte & 0x0F);
                        $gd_scan_line .= "$p1$p2";
                    }
                    $gd_scan_line = mb_substr($gd_scan_line, 0, $width);
                } else {
                    if ($bits == 1) {
                        $gd_scan_line = "";
                        $j = 0;
                        while ($j < $scan_line_size) {
                            $byte = ord($scan_line[$j++]);
                            $p1 = chr(( int )(($byte & 0x80) != 0));
                            $p2 = chr(( int )(($byte & 0x40) != 0));
                            $p3 = chr(( int )(($byte & 0x20) != 0));
                            $p4 = chr(( int )(($byte & 0x10) != 0));
                            $p5 = chr(( int )(($byte & 0x08) != 0));
                            $p6 = chr(( int )(($byte & 0x04) != 0));
                            $p7 = chr(( int )(($byte & 0x02) != 0));
                            $p8 = chr(( int )(($byte & 0x01) != 0));
                            $gd_scan_line .= "$p1$p2$p3$p4$p5$p6$p7$p8";
                        }
                        $gd_scan_line = mb_substr($gd_scan_line, 0, $width);
                    }
                }
            }
        }

        fwrite($dest_f, $gd_scan_line);
    }
    fclose($src_f);
    fclose($dest_f);
    return true;
}

function imagecreatefrombitmap($filename)
{
    $tmp_name = tempnam("/tmp", "GD");
    if (ConvertBMP2GD($filename, $tmp_name)) {
        $img = imagecreatefromgd($tmp_name);
        unlink($tmp_name);
        return $img;
    }
    return false;
}
