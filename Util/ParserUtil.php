<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Util;

use Guzzle\Http\Client;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;

class ParserUtil
{
    // TODO: Should also catch URLs that only start with www.
    const REGEX_URL = '/\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|$!:,.;]*[A-Z0-9+&@#\/%=~_|$]/i';

    static function getHTMLTitle($website, $page = null)
    {
        if($page == null){
            $page = $website;
        }
        // Grab the HTML
        $client = new Client($website);
        $request = $client->get($page);
        $response = $request->send();
        // Crawl the DOM tree
        $crawler = new Crawler($response->getBody(true));
        return $crawler->filter('title')->first()->text();
    }

    static function extractURLsFromText($text){
        preg_match_all(self::REGEX_URL, $text, $matches);
        return $matches[0];
    }

    /*
     * Replaces all URLs in a .txt file with new URLs. If the same URL appears
     * multiple times in the text, each URL will be replaced with a related new
     * URL.
     *
     * @param $replaceUrls An associative array that maps old to new URLs. The
     *                     format should be:
     *                     array(
     *                         'http://www.oldurl.com' => array(
     *                             'http://www.newurl1.com', 'http://www.newurl2.com'
     *                             )
     *                         )
     */
    static function replaceURLsInText($text, $replaceUrls)
    {
        $text = preg_replace_callback(
            self::REGEX_URL,
            function ($matches) use (&$replaceUrls) {
                // Make sure that even if the same URL appears multiple times in
                // the content, each URL gets its own Tracking ID.
                if(array_key_exists($matches[0], $replaceUrls)){
                    $newUrl = $replaceUrls[$matches[0]][0];
                    unset($replaceUrls[$matches[0]][0]);
                    $replaceUrls[$matches[0]] = array_values($replaceUrls[$matches[0]]);
                    return $newUrl;
                }
            },
            $text);

        return $text;
    }

    /*
     * Removes a parameter and its value from the query string of a full URL.
     */
    static function removeUrlParam($url, $key)
    {
        return preg_replace('/[\?|&]'.$key.'=[a-zA-Z0-9]*$|'.$key.'=[a-zA-Z0-9]*[&]/', '', $url);
    }

    /*
     * Adds a parameter and value to a URL.
     */
    static function addUrlParam($url, $key, $val)
    {
        // Separate the anchor element from the URL if it exists.
        $urlParts = parse_url($url);
        if(isset($urlParts['fragment'])){
            $url = str_replace('#'.$urlParts['fragment'], '', $url);
        }

        $separator = (parse_url($url, PHP_URL_QUERY) == NULL) ? '?' : '&';
        $url .= $separator.$key.'='.$val;

        // Add the anchor back to the URL.
        if(isset($urlParts['fragment'])){
            $url .= '#'.$urlParts['fragment'];
        }

        return $url;
    }

    /*
     * Checks whether this is a shortened URL (e.g. using bit.ly).
     */
    static function isShortUrl($url){
        // Overall URL length - May be a max of 30 characters
        if (strlen($url) > 30) return false;

        $parts = parse_url($url);

        if(isset($parts["path"])){
            $path = $parts["path"];
            $pathParts = explode("/", $path);

            // Number of '/' after protocol (http://) - Max 2
            if (count($pathParts) > 2) return false;

            // URL length after last '/' - May be a max of 10 characters
            $lastPath = array_pop($pathParts);
            if (strlen($lastPath) > 10) return false;

            // Max length of host
            if (strlen($parts["host"]) > 10) return false;
        } else {
            return false;
        }

        // Get headers and see if Location is set.
        $headers = get_headers($url, 1);
        if(!is_array($headers) || !isset($headers['Location'])){
            return false;
        }

        return true;
    }

    static function getFavicon($websiteUrl){
        // Get Website's HTML
        $websiteHtml = file_get_contents($websiteUrl);

        // Extract the favicon URL from the HTML.
        $regex_pattern = "/rel=\"shortcut icon\" (?:href=[\'\"]([^\'\"]+)[\'\"])?/";
        preg_match_all($regex_pattern, $websiteHtml, $matches);

        if(!isset($matches[1][0])){
            return false;
        }

        $favicon = $matches[1][0];

        if(isset($favicon)){
            // Favicon's URL was specified in HTML.
            $faviconUrl = $matches[1][0];

            # check if absolute url or relative path
            $faviconUrlParts = parse_url($faviconUrl);

            # if relative
            if(!isset($faviconUrlParts['host'])){
                $faviconUrl = rtrim($websiteUrl, '/').'/'.$favicon;
            }

            return $faviconUrl;
        } else {
            // Not specified in HTML, so try to get it from Website root.
            $faviconUrl = rtrim($websiteUrl, '/').'/'.$favicon;

            $fs = new Filesystem();

            if($fs->exists($faviconUrl)){
                return $faviconUrl;
            }

        }

        return false;
    }

    static function makeLinks($text, $target='_blank', $class=''){
        return preg_replace('!((http\:\/\/|ftp\:\/\/|https\:\/\/)|www\.)([-a-zA-Zа-яА-Я0-9\~\!\@\#\$\%\^\&\*\(\)_\-\=\+\\\/\?\.\:\;\'\,]*)?!ism',
            '<a class="'.$class.'" href="//$3" target="'.$target.'">$1$3</a>',
            $text);
    }
}