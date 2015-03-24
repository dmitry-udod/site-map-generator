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
    public function getMainPageContent($url);
//    public function extractLinks();
//    public function runAsyncQueries(array $urls);
}