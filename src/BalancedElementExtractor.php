<?php
namespace Volcy\Translator;
final class BalancedElementExtractor
{
    /**
     * Locate the element whose opening tag contains the given literal
     * attribute string (e.g. data-i18n="abc123"), then extract its exact
     * inner content using depth counting — so nested tags of the same
     * name resolve correctly instead of matching the first closing tag.
     */
    public static function extractByAttribute(string $content, string $attributeMatch): ?array
    {
        $attrPos = strpos($content, $attributeMatch);
        if ($attrPos === false) {
            return null;
        }

        $tagStart = strrpos(substr($content, 0, $attrPos), '<');
        if ($tagStart === false || !preg_match('/<([a-zA-Z][a-zA-Z0-9]*)/', substr($content, $tagStart), $tm)) {
            return null;
        }
        $tagName = $tm[1];

        $openTagEnd = strpos($content, '>', $attrPos);
        if ($openTagEnd === false) {
            return null;
        }
        $innerStart = $openTagEnd + 1;

        $openPattern = '/<' . preg_quote($tagName, '/') . '(?=[\s>\/])/i';
        $closePattern = '/<\/' . preg_quote($tagName, '/') . '\s*>/i';

        $depth = 1;
        $pos = $innerStart;

        while ($depth > 0) {
            $hasOpen = preg_match($openPattern, $content, $om, PREG_OFFSET_CAPTURE, $pos);
            $hasClose = preg_match($closePattern, $content, $cm, PREG_OFFSET_CAPTURE, $pos);

            if (!$hasClose) {
                return null; // malformed markup — bail safely, leave source untouched
            }

            if ($hasOpen && $om[0][1] < $cm[0][1]) {
                $depth++;
                $pos = $om[0][1] + strlen($om[0][0]);
            } else {
                $depth--;
                if ($depth === 0) {
                    $innerEnd = $cm[0][1];
                    return [
                        'inner_start' => $innerStart,
                        'inner_end'   => $innerEnd,
                        'inner_html'  => substr($content, $innerStart, $innerEnd - $innerStart),
                    ];
                }
                $pos = $cm[0][1] + strlen($cm[0][0]);
            }
        }

        return null;
    }
}