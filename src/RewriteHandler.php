<?php

namespace Webfactory\Html5TagRewriter;

use Dom\Document;
use Dom\Element;
use Dom\XPath;

/**
 * Interface für einen Handler, der eine bestimmte Art von Tags
 * umschreibt.
 */
interface RewriteHandler
{
    /**
     * Gibt an, welche Tags der Handler verarbeiten möchte. Der XPath-Ausdruck muss
     * für HTML5-Tags das Namespace-Prefix "html" verwenden. Zum Beispiel trifft
     * '//html:a' alle <a>-Tags.
     *
     * @return string Ein XPath-Ausdruck, der die zu verarbeitenden Tags qualifiziert.
     */
    public function appliesTo(): string;

    /**
     * Verarbeitet ein passendes DOM-Element.
     *
     * @param Element $element Ein DOM-Node, der vom Handler bearbeitet werden kann.
     */
    public function match(Element $element): void;

    /**
     * Schablonenmethode. Teilt dem Handler mit, dass alle Treffer gefunden und an
     * match() übergeben wurden.
     * Der Handler kann in match() die DOM-Nodes sammeln und in dieser Methode im
     * batch verarbeiten.
     *
     * @return void
     */
    public function afterMatches(Document $document, XPath $xpath);
}
