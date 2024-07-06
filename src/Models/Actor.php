<?php

namespace MovieChill\Core\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Backpack\Settings\app\Models\Setting;
use MovieChill\Core\Contracts\TaxonomyInterface;
use MovieChill\CachingModel\Contracts\Cacheable;
use MovieChill\CachingModel\HasCache;
use Illuminate\Database\Eloquent\Model;
use MovieChill\Core\Contracts\SeoInterface;
use MovieChill\Core\Traits\HasFactory;
use MovieChill\Core\Traits\HasTitle;
use MovieChill\Core\Traits\HasDescription;
use MovieChill\Core\Traits\HasKeywords;
use MovieChill\Core\Traits\HasUniqueName;
use MovieChill\Core\Traits\Sluggable;
use Illuminate\Support\Str;
use Artesaos\SEOTools\Facades\JsonLdMulti;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\TwitterCard;

class Actor extends Model implements TaxonomyInterface, Cacheable, SeoInterface
{
    use CrudTrait;
    use Sluggable;
    use HasUniqueName;
    use HasFactory;
    use HasCache;
    use HasTitle;
    use HasDescription;
    use HasKeywords;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'actors';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];
    // protected $dates = [];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    public static function primaryCacheKey(): string
    {
        $site_routes = setting('site_routes_actors', '/dien-vien/{actor}');
        if (strpos($site_routes, '{actor}')) return 'slug';
        if (strpos($site_routes, '{id}')) return 'id';
        return 'slug';
    }

    public function getUrl()
    {
        $params = [];
        $site_routes = setting('site_routes_actors', '/dien-vien/{actor}');
        if (strpos($site_routes, '{actor}')) $params['actor'] = $this->slug;
        if (strpos($site_routes, '{id}')) $params['id'] = $this->id;
        return route('actors.movies.index', $params);
    }

    protected function titlePattern(): string
    {
        return Setting::get('site_actor_title', '');
    }

    protected function descriptionPattern(): string
    {
        return Setting::get('site_actor_des', '');
    }

    protected function keywordsPattern(): string
    {
        return Setting::get('site_actor_key', '');
    }

    public function generateSeoTags()
    {
        $seo_title = $this->getTitle();
        $seo_des = Str::limit($this->getDescription(), 150, '...');
        $seo_key = $this->getKeywords();
        $movie_thumb_url = '';
        $movie_poster_url = '';
        $updated_at = '';
        if(count($this->movies)) {
            $movie_thumb_url = filter_var($this->movies->last()->thumb_url, FILTER_VALIDATE_URL) ? $this->movies->last()->thumb_url : request()->root() . $this->movies->last()->thumb_url;
            $movie_poster_url = filter_var($this->movies->last()->poster_url, FILTER_VALIDATE_URL) ? $this->movies->last()->poster_url : request()->root() . $this->movies->last()->poster_url;
            $updated_at = $this->movies->last()->updated_at;
        }
        $getUrl = $this->getUrl();
        $site_meta_siteName = setting('site_meta_siteName');

        SEOMeta::setTitle($seo_title, false)
            ->setDescription($seo_des)
            ->addKeyword([$seo_key])
            ->setCanonical($getUrl)
            ->setPrev(request()->root())
            ->setPrev(request()->root());

        OpenGraph::setSiteName($site_meta_siteName)
            ->setType('website')
            ->setTitle($seo_title, false)
            ->addProperty('locale', 'vi-VN')
            ->addProperty('updated_time', $updated_at)
            ->addProperty('url', $getUrl)
            ->setDescription($seo_des)
            ->addImages([$movie_thumb_url, $movie_poster_url]);

        TwitterCard::setSite($site_meta_siteName)
            ->setTitle($seo_title, false)
            ->setType('summary')
            ->setImage($movie_thumb_url)
            ->setDescription($seo_des)
            ->setUrl($getUrl);

        JsonLdMulti::newJsonLd()
            ->setSite($site_meta_siteName)
            ->setTitle($seo_title, false)
            ->setType('WebPage')
            ->addValue('dateCreated', $updated_at)
            ->addValue('dateModified', $updated_at)
            ->addValue('datePublished', $updated_at)
            ->setDescription($seo_des)
            ->setImages([$movie_thumb_url, $movie_poster_url])
            ->setUrl($getUrl);

        $breadcrumb = [];
        array_push($breadcrumb, [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => url('/')
        ]);
        array_push($breadcrumb, [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => $this->name,
            'item' => $getUrl
        ]);
        array_push($breadcrumb, [
            '@type' => 'ListItem',
            'position' => 3,
            'name' => "Trang " . (request()->get('page') ?: 1),
        ]);
        JsonLdMulti::newJsonLd()
            ->setType('BreadcrumbList')
            ->addValue('name', '')
            ->addValue('description', '')
            ->addValue('itemListElement', $breadcrumb);
    }


    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function movies()
    {
        return $this->belongsToMany(Movie::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
