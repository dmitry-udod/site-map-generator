<?php namespace App\Contracts;

interface SiteMapGeneratorInterface
{
    /**
     * Generate site map xml
     *
     * @return mixed
     */
    public function generate();
    public function getPageContent($url);
    public function extractLinks($html);
    public function sendPoolRequest(array $urls);
}