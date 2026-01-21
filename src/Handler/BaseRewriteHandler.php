<?php

declare(strict_types=1);

namespace Webfactory\Html5TagRewriter\Handler;

use Dom\Document;
use Dom\Element;
use Dom\XPath;
use Webfactory\Html5TagRewriter\RewriteHandler;

/**
 * Abstract base class for RewriteHandler implementations.
 */
abstract class BaseRewriteHandler implements RewriteHandler
{
    public function match(Element $element): void
    {
    }

    public function afterMatches(Document $document, XPath $xpath): void
    {
    }
}
