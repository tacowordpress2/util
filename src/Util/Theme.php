<?php
namespace Taco\Util;

use \Taco\Config as Config;

class Theme {
  
  /**
   * Apply filter to string
   * @param string $input
   * @param bool $is_title
   * @return string
   */
  public static function the($input, $is_title=false) {
    return ($is_title)
      ? apply_filters('the_title', $input)
      : apply_filters('the_content', $input);
  }
  
  
  /**
   * Get URL for an image in the media library
   * @param string $path
   * @param string $size (size keys that you've passed to add_image_size)
   * @return string Relative URL
   */
  public static function media($path, $size) {
    // Which image size was requested?
    global $_wp_additional_image_sizes;
    $image_size = $_wp_additional_image_sizes[$size];
    
    // Get the path info
    $pathinfo = pathinfo($path);
    $fname = $pathinfo['basename'];
    $fext = $pathinfo['extension'];
    $dir = $pathinfo['dirname'];
    $fdir = realpath(str_replace('//', '/', ABSPATH.$dir)).'/';
    
    // Filename without any size suffix or extension (e.g. without -144x200.jpg)
    $fname_prefix = preg_replace('/(?:-\d+x\d+)?\.'.$fext.'$/i', '', $fname);
    $out_fname = sprintf(
      '%s-%sx%s.%s',
      $fname_prefix,
      $image_size['width'],
      $image_size['height'],
      $fext
    );
    
    // See if the file that we're predicting exists
    // If so, we can avoid a call to the database
    $fpath = $fdir.$out_fname;
    if(file_exists($fpath)) {
      return sprintf(
        '%s/%s',
        $pathinfo['dirname'],
        $out_fname
      );
    }
    
    // Can't find the file? Figure out the correct path from the database
    global $wpdb;
    $guid = site_url().$dir.'/'.$fname_prefix.'.'.$fext;
    $prepared = $wpdb->prepare(
      "SELECT
        pm.meta_value
      FROM $wpdb->posts p
      INNER JOIN $wpdb->postmeta pm
        ON p.ID = pm.post_id
      WHERE p.guid = %s
      AND pm.meta_key = '_wp_attachment_metadata'
      LIMIT 1",
      $guid
    );
    $row = $wpdb->get_row($prepared);
    if(is_object($row)) {
      $meta = unserialize($row->meta_value);
      if(isset($meta['sizes'][$size]['file'])) {
        $meta_fname = $meta['sizes'][$size]['file'];
        return sprintf(
          '%s/%s',
          $pathinfo['dirname'],
          $meta_fname
        );
      }
    }
    
    // Still nothing? Just return the path given
    return $path;
  }
  
  
  /**
   * Get the asset path
   * @param string $relative_path
   * @param bool $include_version
   * @return string
   */
  public static function asset($relative_path, $include_version=true) {
    $clean_relative_path = preg_replace('/^[\/_]+/', '', $relative_path);
    return sprintf(
      '%s/_/%s%s',
      THEME_URL,
      $clean_relative_path,
      ($include_version) ? THEME_SUFFIX : null
    );
  }
  
  
  /**
   * Get edit link when admin is logged in
   * @param int $id (post ID or term ID)
   * @param string $edit_type (post type or taxonomy slug)
   * @param string $label (optional admin-facing name for $edit_type)
   * @param bool $display_inline (omit wrapping paragraph)
   * @return string (HTML)
   */
  public static function editLink($id=null, $edit_type='post', $label=null, $display_inline=false) {
    if(!(is_user_logged_in() && current_user_can('manage_options'))) return null;
    
    $link_class = 'class="front-end-edit-link"';
    $link_tag = ($display_inline) ? 'span' : 'p';
    if(is_null($label)) {
      $label = Str::human(str_replace('-', ' ', $edit_type));
    }
    $subclasses = \Taco\Post\Loader::getSubclasses();
    $subclasses_machine = array_map(function($el){
      $el = substr($el, strrpos($el, '\\'));
      $el = Str::camelToHuman($el);
      $el = Str::chain($el);
      return $el;
    }, $subclasses);
    if(in_array($edit_type, $subclasses_machine)) {
      // Edit post or display list of posts of this type
      $post_type_url = (!is_null($id))
        ? get_edit_post_link($id)
        : '/wp-admin/edit.php?post_type='.$edit_type;
      return View::make('util/edit-link', [
        'link_tag' => $link_tag,
        'link_class' => $link_class,
        'url' => $post_type_url,
        'label' => 'Edit '.$label,
        'link_tag' => $link_tag,
      ]);
    }
    
    // Find an applicable post type for editing a custom term
    $post_type = null;
    $post_types_by_taxonomy = [];
    foreach($subclasses as $subclass) {
      if(strpos($subclass, '\\') !== false) {
        $subclass = '\\'.$subclass;
      }
      $taxonomies = \Taco\Post\Factory::create($subclass)->getTaxonomies();
      if(Arr::iterable($taxonomies)) {
        foreach($taxonomies as $key => $taxonomy) {
          $taxonomy_slug = (is_array($taxonomy))
            ? $key
            : $taxonomy;
          $post_types_by_taxonomy[$taxonomy_slug][] = $subclass;
        }
      }
    }
    $post_types_by_taxonomy = array_unique($post_types_by_taxonomy);
    if(array_key_exists($edit_type, $post_types_by_taxonomy)) {
      $post_type = reset($post_types_by_taxonomy[$edit_type]);
      $post_type = substr($post_type, strrpos($post_type, '\\'));
      $post_type = Str::camelToHuman($post_type);
      $post_type = Str::chain($post_type);
    } else {
      $post_type = 'post';
    }
    
    if(is_null($id)) {
      // View taxonomy term list
      $term_list_url = sprintf(
        '/wp-admin/edit-tags.php?taxonomy=%s&post_type=%s',
        $edit_type,
        $post_type
      );
      return View::make('util/edit-link', [
        'link_tag' => $link_tag,
        'link_class' => $link_class,
        'url' => $term_list_url,
        'label' => 'View '.$label,
        'link_tag' => $link_tag,
      ]);
    }
    
    // Edit term
    return View::make('util/edit-link', [
      'link_tag' => $link_tag,
      'link_class' => $link_class,
      'url' => get_edit_term_link($id, $edit_type, $post_type),
      'label' => 'Edit '.$label,
      'link_tag' => $link_tag,
    ]);
  }


  /**
   * Get App Options link when admin is logged in
   * @param string $description
   * @param bool $display_inline
   * @return string
   */
  public static function optionsLink($description=null, $display_inline=false) {
    if(!(is_user_logged_in() && current_user_can('manage_options'))) return null;
    
    if(is_null($description)) {
      $description = 'this';
    }
    $options = AppOption::getInstance();
    return self::editLink($options->ID, 'app-option', $description.' in options', $display_inline);
  }
  
  
  /**
   * Get the page title
   * @return string
   */
  public static function pageTitle() {
    $short_site_name = Config::get()->site_name;
    $separator = self::pageTitleSeparator();
    $title = wp_title(null, false);
    
    // From an SEO/SEM perspective, appending the site name to the title is
    // superfluous if the title already contains some form of the site name, as
    // may be the case with press releases and other news-related posts
    $title_has_site_name = (strpos($title, $short_site_name) !== false);
    if(!empty($title) && $title_has_site_name) {
      return $title;
    }
    
    $site_name = get_bloginfo('name');
    if(!empty($title)) {
      return $title.$separator.$site_name;
    }
    
    global $post;
    $this_post = $post;
    
    // Create Taco post only if $post is not already a Taco object
    if(!is_subclass_of($post, 'Taco\Base')) {
      $this_post = \Taco\Post\Factory::create($post);
    }
    
    $page_title_taxonomy = $this_post->pageTitleTaxonomy();
    
    if(Config::get()->use_yoast) {
      $yoast_title = $this_post->getSafe('_yoast_wpseo_title');
      $yoast_title_has_site_name = preg_match('/'.$site_name.'$/', $yoast_title);
      if(!empty($yoast_title) && $yoast_title_has_site_name) {
        return $yoast_title;
      }
      
      if(!empty($yoast_title)) {
        return $yoast_title.$separator.$site_name;
      }
    }
    
    if(empty($page_title_taxonomy)) {
      return $this_post->getTheTitle().$separator.$site_name;
    }
    
    $term_name = null;
    $terms = $this_post->getTerms($page_title_taxonomy);
    if(!empty($terms)) {
      $primary_term_id = $this_post->{'_yoast_wpseo_primary_'.$page_title_taxonomy};
      $term = (!empty($primary_term_id))
        ? $terms[$primary_term_id]
        : reset($terms);
      $term_name = $separator.$term->name;
    }
    return $this_post->getTheTitle().$term_name.$separator.$site_name;
  }
  
  
  /**
   * Yoast title separator
   * Also defined in Yoast in WP admin
   * @link /wp-admin/admin.php?page=wpseo_titles
   * @return string
   */
  private static function pageTitleSeparator() {
    return Config::get()->page_title_separator;
  }
  
  
  /**
   * Get the page meta tags
   * @return string
   */
  public static function pageMeta() {
    $title = wp_title(null, false);
    
    // If the title is not empty, we can assume that we're not bypassing
    // WordPress routing, and therefore Yoast will give us all the meta tags
    if(!empty($title)) return null;
    
    global $post;
    $this_post = $post;
    
    // Create Taco post only if $post is not already a Taco object
    if(!is_subclass_of($post, 'Taco\Base')) {
      $this_post = \Taco\Post\Factory::create($post, false);
    }
    
    if(!Obj::iterable($this_post)) return null;
    
    $description = null;
    if(Config::get()->use_yoast) {
      $description = $this_post->getSafe('_yoast_wpseo_metadesc');
    }
    if(empty($description)) {
      $description = strip_tags($this_post->getBareExcerpt());
    }
    
    $image = (Config::get()->use_yoast)
      ? $this_post->{'_yoast_wpseo_opengraph-image'}
      : null;
    
    return View::make('meta-tags', [
      'title' => self::pageTitle(),
      'description' => trim($description),
      'image' => $image,
      'full_url' => URL_REQUEST,
      'site_name' => get_bloginfo('name'),
      'separator' => self::pageTitleSeparator(),
    ]);
  }
  
  
  /**
   * Get HTML for all icons
   * @link http://www.favicon-generator.org/
   * @return string
   */
  public static function appIcons() {
    $config = Config::instance();
    $icons_directory = THEME_DIRECTORY.'/'.$config->app_icons_directory;
    $icons_url = THEME_URL.'/'.$config->app_icons_directory;
    if(!file_exists($icons_directory)) return null;
    
    $files = scandir($icons_directory);
    if(!Arr::iterable($files)) return null;
    
    // Icon files should start with one of these, and be delimited by hyphens
    $icon_types = ['apple', 'android', 'favicon'];
    $paths = [];
    
    foreach($files as $file)  {
      if(!preg_match('/\.png$/', $file)) continue;
      
      // Get size from image name
      preg_match('/(\d+x\d+)/', $file, $sizes);
      if(!Arr::iterable($sizes)) continue;
      
      $size = reset($sizes);
      $icon_type = reset(explode('-', $file));
      if(!in_array($icon_type, $icon_types)) continue;
      
      $file_path = $icons_url.'/'.$file;
      $paths[] = View::make('meta/icon-'.$icon_type, [
        'file' => $file_path,
        'size' => $size,
      ]);
    }
    
    $paths[] = View::make('meta/icon-favicon-ico', [
      'file' => $icons_url.'/favicon.ico',
    ]);
    return join('', $paths);
  }
  
}
