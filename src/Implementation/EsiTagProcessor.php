<?php

declare(strict_types=1);

namespace Webfactory\Html5TagRewriter\Implementation;

/**
 * Preserves ESI (Edge Side Includes) tags verbatim during HTML5 parsing and serialization.
 *
 * ESI tags present multiple challenges for HTML5 parsing:
 *
 * 1. Self-closing syntax: ESI tags like <esi:include src="..." /> use self-closing syntax,
 *    which does not exist in HTML5. The parser treats them as opening tags without closing
 *    tags, causing all following content to be incorrectly nested inside the ESI element.
 *
 * 2. Arbitrary interleaving: ESI tags can span across HTML element boundaries in ways that
 *    violate well-formedness rules. For example, an opening ESI tag might appear in one
 *    HTML element while its closing tag appears in another. HTML5 parsers would "repair"
 *    such structures, breaking the intended ESI behavior.
 *
 * 3. Attribute preservation: ESI tags must not be modified because they may be processed
 *    on a text basis by an upstream component (e.g., a caching proxy or CDN) that does not
 *    apply HTML rules. Any transformation - such as encoding & as &amp; in attribute
 *    values - would break the ESI processor's ability to parse the tag correctly.
 *
 * This class solves these problems by wrapping every ESI tag (opening, closing, or
 * self-closing) in an HTML comment during pre-processing, using the ESI comment syntax
 * defined in Section 3.7 of the ESI Language Specification. The original tags are restored
 * verbatim during post-processing.
 *
 * Important: During processing, ESI tags do not appear as Elements in the DOM, but as
 * Comment nodes. If RewriteHandler transformations move or delete these comment nodes,
 * the final result may not match expectations.
 */
final class EsiTagProcessor
{
    private const COMMENT_PREFIX = 'esi html5-tagrewriter ';

    /**
     * Wraps all ESI tags in HTML comments.
     *
     * Each ESI tag (opening, closing, or self-closing) is wrapped as
     * <!--esi html5-tagrewriter <original-tag> --> to hide it from the HTML5 parser
     * while preserving the original content verbatim.
     */
    public function preProcess(string $html): string
    {
        // Match opening tags: <esi:name ...>
        // Match closing tags: </esi:name>
        // Match self-closing tags: <esi:name ... />
        // Note: The [^>]*? pattern does not correctly handle ">" inside quoted attribute
        // values (e.g., <esi:include src="a>b" />). This is a known limitation that we
        // ignore for now, as such attribute values are uncommon in practice.
        return preg_replace_callback(
            '#<(/?)esi:([a-z]+)([^>]*?)(/?)>#',
            function (array $matches): string {
                return '<!--'.self::COMMENT_PREFIX.$matches[0].'-->';
            },
            $html
        ) ?? $html;
    }

    /**
     * Restores original ESI tags from HTML comments.
     */
    public function postProcess(string $html): string
    {
        $prefix = preg_quote(self::COMMENT_PREFIX, '#');

        return preg_replace_callback(
            '#<!--'.$prefix.'(.+?)-->#',
            function (array $matches): string {
                return $matches[1];
            },
            $html
        ) ?? $html;
    }
}
