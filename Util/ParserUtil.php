<?php
/*
 * Copyright 2016 CampaignChain, Inc. <info@campaignchain.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CampaignChain\CoreBundle\Util;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;

class ParserUtil
{
    // TODO: Should also catch URLs that only start with www.
    const REGEX_URL = '/\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|$!:,.;]*[A-Z0-9+&@#\/%=~_|$]/i';

    static function getHTMLTitle($website, $page = null)
    {
        if(self::isSameHost($website)){
            $urlParts = parse_url($website);
            if(isset($urlParts['path'])){
                return $urlParts['path'];
            } else {
                return '/';
            }
        }

        if($page == null){
            $page = $website;
        }
        // Grab the HTML
        $client = new Client([
            'base_uri' => $website,
        ]);
        try {
            $response = $client->get($page);
        } catch (\Exception $e) {
            return $website;
        }
        // Crawl the DOM tree
        $crawler = new Crawler($response->getBody()->getContents());
        return $crawler->filter('title')->first()->text();
    }

    static function extractURLsFromText($text){
        // If this is HTML, then get the content within the <body> tag.
        preg_match("/<body[^>]*>(.*?)<\/body>/is", $text, $matches);
        if(count($matches) && isset($matches[1])){
            $text = $matches[1];
        }

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
        $url = self::completeLocalPath($url);

        // If not a valid URL, return false.
        if(!self::validateUrl($url)){
            throw new \Exception('Invalid URL '.$url);
        }

        $urlParts = parse_url($url);

        // If the URL has no query string, we're done.
        if(!isset($urlParts['query'])){
            return $url;
        } else {
            $url = str_replace('?'.$urlParts['query'], '', $url);
        }

        // Remove hash before we parse for the parameter.
        if(isset($urlParts['fragment'])) {
            $url = str_replace('#'.$urlParts['fragment'], '', $url);
        }

        parse_str( $urlParts['query'], $queryParts );
        if(isset($queryParts[$key])) {
            unset($queryParts[$key]);
        }

        $urlParts['query'] = '';

        if(is_array($queryParts) && count($queryParts)) {
            foreach ($queryParts as $paramKey => $paramVal) {
                $urlParts['query'] .= $paramKey . '=' . $paramVal . '&';
            }
            $urlParts['query'] = '?'.rtrim($urlParts['query'], '&');
            $url = $url.$urlParts['query'];
        }

        if(isset($urlParts['fragment'])){
            $url .= '#'.$urlParts['fragment'];
        }

        return $url;
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

    /**
     * Remove trailing slash if no path included in URL.
     *
     * For example:
     * - http://www.example.com/ <- remove
     * - http://www.example.com/news/ <- do not remove
     * - http://www.exmaple.com/?id=2/ <- remove
     * - http://www.example.com/?id=2 <- do not remove
     *
     * @param $url
     * @return string
     * @throws \Exception
     */
    static function sanitizeUrl($url)
    {
        if(
            self::validateUrl($url) &&
            substr($url, -1) === '/'
        ) {
            $urlParts = parse_url($url);
            if(
                !isset($urlParts['path']) ||
                ($urlParts['path'] == '/' && !isset($urlParts['query'])) ||
                (isset($urlParts['query']) && substr($urlParts['query'], -1) === '/')
            ){
                $url = rtrim($url, '/');
            }
        }

        return $url;
    }

    /**
     * Validates the syntax of a URL.
     *
     * @param $url
     * @return bool
     * @throws \Exception
     */
    static function validateUrl($url)
    {
        if(!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        return true;
    }

    static function truncateMiddle($text, $maxChars)
    {
        $textLength = strlen($text);

        if ($textLength > $maxChars){
            return substr_replace($text, '...', $maxChars/2, $textLength-$maxChars);
        }

        return $text;
    }

    static function strReplaceLast($search, $replace, $subject)
    {
        $pos = strrpos($subject, $search);

        if($pos !== false)
        {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }

        return $subject;
    }

    /**
     * Get length of a text which includes URLs that will be shortened.
     *
     * Basically, this function can be used to calculate the length of a Twitter
     * message.
     *
     * @param $text
     * @param int $shortUrlLength
     * @return int
     * @throws \Exception
     */
    static function getTextLengthWithShortUrls($text, $shortUrlLength = 23)
    {
        if($shortUrlLength < 7){
            throw new \Exception('URL must be at least 7 characters long.');
        }

        // Create dummy short URL.
        $shortUrl = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $shortUrlLength - 7);
        $shortUrl = 'http://'.$shortUrl;

        return mb_strlen(preg_replace(self::REGEX_URL, $shortUrl, $text), 'UTF-8');
    }

    static function safeTruncateTextWithShortUrls($text, $max, $shortUrlLength = 23)
    {
        $textLengthWithShortUrls = self::getTextLengthWithShortUrls($text, $shortUrlLength);
        if($textLengthWithShortUrls > $max){
            $text = wordwrap($text, $textLengthWithShortUrls);
        }

        return $text;
    }

    static function urlExists($url, $graceful = true)
    {
        // If not a valid URL, return false.
        if(!self::validateUrl($url)){
            return false;
        }

        // Avoid loop of get_headers() requests if same host.
        if(self::isSameHost($url)){
            return true;
        }

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if( $httpCode < 400 ){
                return true;
            }

            return false;
        } catch (\Exception $e) {
            if($graceful){
                return true;
            }
            return false;
        }
    }

    /**
     * Avoid loop of get_headers() requests if same host.
     *
     * @param $url
     * @return bool
     */
    static function isSameHost($url)
    {
        $urlParts = parse_url($url);
        if(
            isset($_SERVER['SERVER_NAME']) &&
            $_SERVER['SERVER_NAME'] == $urlParts['host']
        ){
            return true;
        }

        return false;
    }

    static function completeLocalPath($url)
    {
        // If no scheme and host included, then it's a URL on the same host.
        $urlParts = parse_url($url);

        if(!isset($urlParts['host'])){
            if(isset($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS']){
                $scheme = 'https';
            } else {
                $scheme = 'http';
            }

            $url = $scheme.'://'.$_SERVER['HTTP_HOST'].$url;
        }

        return $url;
    }
}