<?php

class SiteMapGeneratorTest extends TestCase
{
    /** @var  \App\Generators\SiteMapGenerator */
    public $generator;

    public function setUp()
    {
        parent::setUp();
        $this->generator = \App::make('Generator')->setUrl('http://google.com');
    }

    public function testGetMainPageContentSuccess()
    {
        $result = $this->generator->getPageContent('http://google.com');
        $this->assertContains('<!doctype html>', $result);
    }

    /**
     * @expectedException Exception
     */
    public function testGetMainPageContentException()
    {
        $this->generator->getPageContent('http://google.fail');
    }

    public function testExtractLinks()
    {
        $html = '
        <!DOCTYPE html>
        <html>
            <body>
                <p class="message">Hello World!</p>
                <p>Hello Crawler!</p>
                <a href="http://google.com">Link</a>
                <a href="http://yahoo.com">Link</a>
            </body>
            <a class="testme" href="#"></a>
            <a class="testme" href="http://google.com/1234"></a>
            <a class="testme" href="http://google.com/12345"></a>
        </html>
        ';

        $links = $this->generator->extractLinks($html);
        $this->assertCount(2, $links, 'We have only 2 links for this domain');
        $this->assertEquals('http://google.com/1234', $links[0]);
        $this->assertEquals('http://google.com/12345', $links[1]);
    }
}
