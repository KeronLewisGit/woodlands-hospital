<?php

namespace App\Support;

/**
 * Builds a tiny one-page text PDF without any library. Used for demo data and tests only,
 * so the results portal can be shown end to end without a real patient report.
 */
final class SamplePdf
{
    /** @param list<string> $lines */
    public static function make(string $heading, array $lines): string
    {
        $esc = fn (string $s) => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
        $text = "BT /F2 18 Tf 56 770 Td ({$esc($heading)}) Tj ET\n";
        $y = 736;
        foreach ($lines as $line) {
            $text .= "BT /F1 11 Tf 56 {$y} Td ({$esc($line)}) Tj ET\n";
            $y -= 18;
        }
        $text .= "0.8 0.06 0.18 RG 2 w 56 790 m 539 790 l S\n";

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>',
            '<< /Length '.strlen($text)." >>\nstream\n".$text.'endstream',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$body}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref'."\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }

        return $pdf.'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }
}
