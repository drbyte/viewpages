<?php
/**
 * Vpage model
 */
namespace Delatbabel\ViewPages\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Delatbabel\SiteConfig\Models\Website;
use Wpb\String_Blade_Compiler\StringView;
use Wpb\String_Blade_Compiler\Facades\StringBlade;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Class Vpage
 *
 * This model class is for the database backed website templates table.
 *
 * ### Example
 *
 * <code>
 * $page = Vpage::make('index');
 * </code>
 *
 * ### TODO
 *
 * Extract the logic to find a page for a specific website from the make
 * function and put it into a customised BelongsToMany class.
 *
 * Be able to handle all of the various directives in a normal Blade template
 * such as @extends, @section / @endsection, etc.  @extends should pull in
 * the template from the Vptemplate model class.
 */
class Vpage extends Model
{
    use SoftDeletes;

    protected $fillable = ['key', 'name', 'url', 'description',
        'vptemplate_id', 'is_secure', 'is_homepage', 'content'];

    protected $dates = ['deleted_at'];

    /**
     * Many:Many relationship with Website model
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function websites()
    {
        return $this->belongsToMany('Delatbabel\SiteConfig\Models\Website');
    }

    /**
     * Render page
     *
     * TODO: Be able to handle all of the various directives in a normal
     * Blade template such as @extends, @section / @endsection etc. This
     * may require extending the StringView class.
     *
     * @param Arrayable|array $data
     * @param Arrayable|array $mergeData
     * @return StringView
     */
    public function render($data = array(), $mergeData = array())
    {
        $data = $this->parseData($data);
        $mergeData = $this->parseData($mergeData);

        /** @var StringView $page_view */
        $page_view = StringBlade::make([
            'template'      => $this->content,
            'cache_key'     => $this->id,
            'updated_at'    => $this->updated_at->format('U'),
        ], $data, $mergeData);

        return $page_view;
    }

    /**
     * Parse the given data into a raw array.
     *
     * @param  Arrayable|array  $data
     * @return array
     */
    protected function parseData($data)
    {
        return $data instanceof Arrayable ? $data->toArray() : $data;
    }

    /**
     * Make page
     *
     * Returns a Vpage object for a specific URL for the current website.
     *
     * A Vpage object can either be for a specific website or websites,
     * in which case there will be a join table entry in vpage_website
     * containing (vpage_id, website_id), or the Vpage can be for all
     * websites, which means that there will be no join table entry in
     * vpage_website for that vpage_id at all (for any website).
     *
     * Multiple pages can exist in the vpages table for any given URL.
     *
     * This function finds the correct page in vpages that matches the
     * given URL and has a join to the current website, or if that fails
     * then it will find the correct page in vpages for the given URL
     * that has no joins to any website.
     *
     * @param string $url
     * @return Vpage
     */
    public static function make($url = 'index')
    {
        $url = filter_var($url, FILTER_SANITIZE_STRING);

        if (empty($url)) {
            // An empty URL indicates that the home page is being fetched.
            $url = 'index';
        }

        // Find the current website ID
        $website_id = Website::currentWebsiteId();

        // Try to find a page that is joined to the current website
        $page = static::where('url', '=', $url)
            ->join('vpage_website', 'vpage.id', '=', 'vpage_website.vpage_id')
            ->where('vpage_website.website_id', '=', $website_id)
            ->first();
        if (! empty($page)) {
            return $page;
        }

        // If there is no such page, try to find a page that is not joined
        // to any website
        $page = static::where('url', '=', $url)
            ->leftJoin('vpage_website', 'vpages.id', '=', 'vpage_website.vpage_id')
            ->whereNull('vpage_website.website_id')
            ->first();
        if (! empty($page)) {
            return $page;
        }

        // If we have no page so far, fetch the 410 page
        $page = static::make('410');
        if (! empty($page)) {
            return $page;
        }

        // If we have no page so far, fetch the 404 page
        return static::make('410');
    }
}