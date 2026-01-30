<?php

declare(strict_types=1);

namespace Webfactory\Html5TagRewriter\Implementation;

use Dom\Document;
use Dom\HTMLDocument;
use Dom\Node;
use Dom\XPath;
use Override;
use Webfactory\Html5TagRewriter\RewriteHandler;
use Webfactory\Html5TagRewriter\TagRewriter;

final class Html5TagRewriter implements TagRewriter
{
    /** @var list<RewriteHandler> */
    private array $rewriteHandlers = [];

    #[Override]
    public function register(RewriteHandler $handler): void
    {
        $this->rewriteHandlers[] = $handler;
    }

    #[Override]
    public function process(string $html5): string
    {
        $esiProcessor = new EsiTagProcessor();

        $document = HTMLDocument::createFromString($esiProcessor->preProcess($html5), LIBXML_NOERROR);

        $this->applyHandlers($document, $document);

        return $esiProcessor->postProcess($document->saveHtml());
    }

    #[Override]
    public function processBodyFragment(string $html5Fragment): string
    {
        /*
         * Different parser states and tokenization modes
         * (https://html.spec.whatwg.org/multipage/parsing.html#parse-state,
         * https://html.spec.whatwg.org/multipage/parsing.html#tokenization)
         * may apply at different parts of the HTML input. Currently, there is
         * no (documented) way to create HTML fragements with the necessary
         * context with the new DOM API. So, for the time being, we must restrict
         * handling of fragments to such inputs that can equally be considered to be
         * placed directly after the `<body>` tag.
         */
        $esiProcessor = new EsiTagProcessor();

        $document = HTMLDocument::createFromString('', overrideEncoding: 'utf-8');
        $container = $document->body;
        assert($container !== null);

        $container->innerHTML = $esiProcessor->preProcess($html5Fragment);

        $this->applyHandlers($document, $container);

        return $esiProcessor->postProcess($container->innerHTML);
    }

    private function applyHandlers(Document $document, Node $context): void
    {
        $xpath = new XPath($document);
        $xpath->registerNamespace('html', 'http://www.w3.org/1999/xhtml');
        $xpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');
        $xpath->registerNamespace('mathml', 'http://www.w3.org/1998/Math/MathML');

        foreach ($this->rewriteHandlers as $handler) {
            /** @var iterable<Node> $nodeList */
            $nodeList = $xpath->query($handler->appliesTo(), $context);
            foreach ($nodeList as $node) {
                $handler->match($node);
            }
            $handler->afterMatches($document, $xpath);
        }
    }

}
