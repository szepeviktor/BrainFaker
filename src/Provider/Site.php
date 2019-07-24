<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the BrainFaker package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Brain\Faker\Provider;

use Brain\Monkey;

class Site extends Provider
{
    /**
     * @param array $properties
     * @return \WP_Site
     */
    public function __invoke(array $properties = []): \WP_Site
    {
        if (function_exists('is_multisite') && !is_multisite()) {
            throw new \Error('WP_Site class is only available in multisite installations.');
        }

        $hasBlogId = array_key_exists('blog_id', $properties);
        $id = $hasBlogId || array_key_exists('id', $properties)
            ? ($hasBlogId ? $properties['blog_id'] : $properties['id'])
            : $this->uniqueGenerator->numberBetween(1, 99999999);

        $siteId = $properties['site_id']
            ?? $properties['network_id']
            ?? (rand(1, 100) > 90 ? $this->generator->numberBetween(2, 5) : 1);

        $url = $properties['site_url']
            ?? $properties['home']
            ?? $properties['url']
            ?? null;

        if ($url) {
            parse_url($url, PHP_URL_SCHEME) or $url = "http://{$url}";
            if (!array_key_exists('path', $properties)) {
                $path = parse_url($url, PHP_URL_PATH);
                $path and $properties['path'] = '/' . trim($path, '/');
            }
        }

        $defaults = [
            'domain' => $this->generator->domainName,
            'path' => rand(1, 100) > 50 ? $this->generator->domainWord : '',
            'registered' => $this->generator->dateTimeThisDecade->format('Y-m-d H:i:s'),
            'last_updated' => $this->generator->dateTimeThisMonth->format('Y-m-d H:i:s'),
            'public' => rand(1, 100) > 80 ? '0' : '1',
            'archived' => rand(1, 100) > 80 ? '1' : '0',
            'mature' => rand(1, 100) > 80 ? '1' : '0',
            'spam' => rand(1, 100) > 80 ? '1' : '0',
            'deleted' => rand(1, 100) > 80 ? '1' : '0',
            'lang_id' => rand(1, 100) > 80 ? '1' : '0',
        ];

        $site = \Mockery::mock(\WP_Site::class);
        $site->blog_id = (string)((int)$id);
        $site->site_id = (string)((int)$siteId);
        $site->id = $site->blog_id;
        $site->network_id = $site->site_id;

        $toArray = ['blog_id' => $site->blog_id, 'site_id' => $site->site_id];
        foreach ($defaults as $key => $value) {
            $field = array_key_exists($key, $properties) ? $properties[$key] : $value;
            is_numeric($field) and $field = (string)((int)$field);
            $toArray[$key] = $field;
            $site->{$key} = $field;
        }

        $details = [
            'blogname' => $this->generator->sentence,
            'siteurl' => $url ?? "http://{$this->generator->domainName}/{$site->path}",
            'home' => $url ?? "http://{$this->generator->domainName}/{$site->path}",
            'post_count' => $this->generator->numberBetween(0, 9999),
        ];

        foreach ($details as $key => $value) {
            $field = array_key_exists($key, $properties) ? $properties[$key] : $value;
            $site->{$key} = $field;
        }

        $site->shouldReceive('to_array')->andReturn($toArray);

        Monkey\Functions\expect('get_site')
            ->zeroOrMoreTimes()
            ->with(\Mockery::anyOf($site->blog_id, (int)$site->blog_id))
            ->andReturn($site);

        return $site;
    }
}