<?php

namespace Dunique\AntwanVanBoheemen\Tags;

require_once PATH_ADDONS . 'structure/sql.structure.php';

use ExpressionEngine\Service\Addon\Controllers\Tag\AbstractRoute;

class Sitemap extends AbstractRoute
{
    // Example tag: {exp:dunique:sitemap}
    // The sitemap is generated based on structure pages and listings
    public function process()
    {
        // Disable EE's template profiler and set correct XML headers
        ee()->output->enable_profiler(false);
        ee('Response')->setHeader('Content-Type', "application/xml; charset=utf-8");

        $structure = new \Sql_structure();
        $excludeEntries = explode('|', ee()->TMPL->fetch_param('exclude', ''));

        // Get pages and site page URIs
        $pages = array_values($structure->get_data());
        $entry_ids = array_column($pages, 'entry_id');
        $site_pages = $structure->get_site_pages();
        $base = rtrim(ee()->functions->fetch_site_index(0, 0), '/');

        $entries = ee('Model')->get('ChannelEntry')->filter('entry_id', 'IN', $entry_ids)->all();
        $html = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
                '<urlset' . "\n" .
                '  xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n" .
                '  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n" .
                '  xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 ' .
                'http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";

        // Add Structure-managed pages
        foreach ($pages as $page) {
            $entry_id = $page['entry_id'];
            if (!empty($site_pages['uris'][$entry_id])) {
                $entry = $entries->filter(fn($entry) => $entry->entry_id == $entry_id)->first();
                if (\in_array($entry_id, $excludeEntries)) continue;
                $uri = $site_pages['uris'][$entry_id];
                $url = htmlspecialchars($base . $uri);
                $listingChannelId = $page['listing_cid'] ;
                $priority = ($uri == '/') ? '1' : '0.9';
                $priority = ee()->TMPL->fetch_param('priority:'.$entry->Channel->channel_name, $priority);
                $changefreq = ($priority == '1' || $listingChannelId) ? 'daily':'weekly';
                $changefreq = ee()->TMPL->fetch_param('changefreq:'.$entry->Channel->channel_name, $changefreq);
                $html .= $this->sitemap_item($entry, $url, $changefreq, $priority);
                // Add the listing's
                if (!empty($listingChannelId)) {
                    $listingEntries = ee()->db
                        ->select('ct.entry_id, ct.channel_id, c.channel_name, ct.status, ct.edit_date, ct.entry_date, c.channel_name, sl.uri')
                        ->from('channel_titles AS ct')
                        ->join('channels AS c', 'ct.channel_id = c.channel_id')
                        ->join('structure_listings AS sl', 'sl.entry_id = ct.entry_id')
                        ->where('ct.channel_id', $listingChannelId)
                        ->where('ct.status', 'open')
                        ->get()
                        ->result();
                    foreach ($listingEntries as $entry) {
                        if (\in_array($entry_id, $excludeEntries)) continue;
                        $changefreq = ee()->TMPL->fetch_param('listing:changefreq:'.$entry->channel_name, 'weekly');
                        $priority = ee()->TMPL->fetch_param('listing:priority:'.$entry->channel_name, '0.8');
                        $listingUrl = $base . $uri. '/'.$entry->uri;
                        $html .= $this->sitemap_item($entry, $listingUrl, $changefreq, $priority);
                    }
                }
            }
        }


        $html .= "</urlset>\n";
        return $html;
    }

    private function sitemap_item($entry, $url, $changefreq, $priority):string {
        $loc = htmlspecialchars($url);
        if (\is_int($entry->edit_date)) {
            $lastmod = gmdate('Y-m-d\TH:i:s+00:00', $entry->edit_date ? $entry->edit_date : $entry->entry_date);

        } else {
            $timestamp = $entry->edit_date ? $entry->edit_date->getTimestamp() : $entry->entry_date->getTimestamp();
            $lastmod = gmdate('Y-m-d\TH:i:s+00:00', $timestamp);
        }
        // @TODO: Configurable change frequency and priority

        $html = '';
        $html .= "  <url>\n";
        $html .= "    <loc>{$loc}</loc>\n";
        $html .= "    <lastmod>{$lastmod}</lastmod>\n";
        $html .= "    <changefreq>{$changefreq}</changefreq>\n";
        $html .= "    <priority>{$priority}</priority>\n";
        $html .= "  </url>\n";
        return $html;
    }
}
