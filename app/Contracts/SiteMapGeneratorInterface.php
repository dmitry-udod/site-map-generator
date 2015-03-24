<?php namespace App\Contracts;

interface SiteMapGeneratorInterface
{
    /**
     * Generate site map xml
     *
     * @return mixed
     */
    public function generate();

    /**
     * Get main page content
     *
     * @return mixed
     */
    public function getPageContent($url);
    public function extractLinks($html);
//    public function runAsyncQueries(array $urls);
}