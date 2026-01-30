<?php

declare(strict_types=1);

namespace Webfactory\Html5TagRewriter\Tests\Implementation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webfactory\Html5TagRewriter\Implementation\EsiTagProcessor;

final class EsiTagProcessorTest extends TestCase
{
    private EsiTagProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new EsiTagProcessor();
    }

    #[Test]
    #[DataProvider('providePreProcessCases')]
    public function preProcess(string $input, string $expected): void
    {
        $result = $this->processor->preProcess($input);

        self::assertSame($expected, $result);
    }

    public static function providePreProcessCases(): iterable
    {
        yield 'wraps self-closing tag in comment' => [
            '<esi:include src="url" />',
            '<!--esi html5-tagrewriter <esi:include src="url" />-->',
        ];

        yield 'wraps opening tag in comment' => [
            '<esi:remove>',
            '<!--esi html5-tagrewriter <esi:remove>-->',
        ];

        yield 'wraps closing tag in comment' => [
            '</esi:remove>',
            '<!--esi html5-tagrewriter </esi:remove>-->',
        ];

        yield 'wraps opening and closing tags in separate comments' => [
            '<esi:remove>content</esi:remove>',
            '<!--esi html5-tagrewriter <esi:remove>-->content<!--esi html5-tagrewriter </esi:remove>-->',
        ];

        yield 'handles multiple tags' => [
            '<esi:include src="a" /><esi:include src="b" />',
            '<!--esi html5-tagrewriter <esi:include src="a" />--><!--esi html5-tagrewriter <esi:include src="b" />-->',
        ];

        yield 'preserves non-esi content' => [
            '<div><p>Hello</p><esi:include src="url" /><span>World</span></div>',
            '<div><p>Hello</p><!--esi html5-tagrewriter <esi:include src="url" />--><span>World</span></div>',
        ];

        yield 'handles esi tags spanning html element boundaries' => [
            '<p>Start <esi:remove>content</p><p>more</esi:remove> end</p>',
            '<p>Start <!--esi html5-tagrewriter <esi:remove>-->content</p><p>more<!--esi html5-tagrewriter </esi:remove>--> end</p>',
        ];
    }

    #[Test]
    #[DataProvider('provideRoundtripCases')]
    public function roundtrip(string $html): void
    {
        $preProcessed = $this->processor->preProcess($html);
        $result = $this->processor->postProcess($preProcessed);

        self::assertSame($html, $result);
    }

    public static function provideRoundtripCases(): iterable
    {
        yield 'self-closing tag without attributes' => ['<esi:include />'];
        yield 'self-closing tag with attribute' => ['<esi:include src="url" />'];
        yield 'self-closing tag with multiple attributes' => ['<esi:include src="url" alt="fallback" onerror="continue" />'];
        yield 'self-closing tag with ampersand in query string' => ['<esi:include src="url?foo=bar&bar=baz" />'];
        yield 'multiple self-closing tags' => ['<esi:include src="a" /><esi:include src="b" />'];
        yield 'opening and closing tags' => ['<esi:remove>content</esi:remove>'];
        yield 'nested esi structure' => ['<esi:try><esi:attempt><esi:include src="url" /></esi:attempt><esi:except><esi:include src="fallback" /></esi:except></esi:try>'];
        yield 'esi tags spanning html boundaries' => ['<p>Start <esi:remove>content</p><p>more</esi:remove> end</p>'];
        yield 'esi wrapping partial html' => ['<p><esi:remove><b>Important:</esi:remove>text<esi:remove></b></esi:remove></p>'];
        yield 'mixed esi and html content' => ['<div><esi:include src="header" /><p>Content</p><esi:include src="footer" /></div>'];
    }
}
