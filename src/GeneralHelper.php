<?php
namespace Kuroragi\GeneralHelper;

class GeneralHelper
{
    public static function getSlug(string $text): string
    {
        $slug = \Str::of($text)->ascii()->lower()->trim()->replaceMatches('/[^\pL\pN]+/u', '-')->replaceMatches('/^-+|-+$/', '')->__toString();
        return $slug;
    }

    public static function convertDateToIndo(string $date): string
    {
        // expects Y-m-d or Y-m-d H:i:s
        $dt = \Carbon\Carbon::parse($date);
        return $dt->translatedFormat('d F Y H:i:s');
    }

    public static function convertDateToIndoShort(string $date): string
    {
        $dt = \Carbon\Carbon::parse($date);
        return $dt->translatedFormat('d M Y');
    }

    public static function getTerbilang($number): string
    {
        // simple Indonesian terbilang (expandable)
        $f = new \NumberFormatter('id_ID', \NumberFormatter::SPELLOUT);
        return ucfirst($f->format($number));
    }

    public static function getIndoDate(string $date): string
    {
        $dt = \Carbon\Carbon::parse($date);
        return $dt->translatedFormat('l, d F Y');
    }

    public static function getIndoDateTerbilang(string $date): string
    {
        $dt = \Carbon\Carbon::parse($date);
        return static::getIndoDate($date) . ' — ' . static::getTerbilang($dt->day);
    }
}
