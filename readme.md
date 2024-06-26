# BEA - Silo (IN DEVELOPMENT)

Dev oriented plugin to add silo feature :

* It will use `wp_localize_script` to localize (javascript), depending on some conditions, terms from wanted taxonomies against wanted post type.
* You can overwrite default term's object to add/remove some values.
* By default a custom [REST Api](https://bitbucket.org/beapi/bea-silo#markdown-header-rest-api) route is created to ease matching post type and taxonomy silo content.
* These matching content will be displayed using a _s template located by default into `silo/templates/` folder. But they can be overwrited into theme or child-theme.
* The silo is displayed with an action from the `silo/blocks/` folder. But it can be overwrited into theme or child-theme.
* By default, plugin generate a default silo view into `silo-{taxonomy_name}.php` template.

# Installation

## WordPress

* Download and install using the built-in WordPress plugin installer.
* Site Activate in the "Plugins" area of the admin.
* Optionally drop the entire `bea-silo` directory into `plugins`.
* Add into your theme's functions.php file or through a plugin, the expected [Steps](https://bitbucket.org/beapi/bea-silo#markdown-header-steps).

## Composer

* Add repository source : { "type": "vcs", "url": "https://github.com/BeAPI/bea-silo" }.
* Include "bea/bea-silo": "dev-master" in your composer file for last master's commits or a tag released.

# Steps to use

## Special warning
If using the default taxonomy's silo view. Watch out about SEO with duplicate content.

## Register post type support

While waiting [ticket/40413](https://core.trac.wordpress.org/ticket/40413) merge into core, add post type support like this :

```
<?php add_post_type_support( {post_type}, 'silo', {taxonomy_1}, {taxonomy_2}, {etc} );
```
After merge, you could do :
```
<?php register_post_type( {post_type}, [ 'supports' => [ 'silo' => [ {taxonomy_1}, {taxonomy_2}, {etc} ] ] ] );
```

## Define localize conditions 

### Custom localization

On the hook `bea\silo\localize_terms` you specify where to localize your terms taxonomy for a post type.
```
<?php
/**
 * Check custom conditions to check if to work on current taxonomy against current post type.
 *
 * @author Maxime CULEA
 *
 * @since 1.0.0
 *
 * @param bool $localize_taxonomy Whatever to localize terms against given taxonomy.
 * @param string $taxonomy The taxonomy name as context.
 * @param string $post_type The Post Type name as context.
 */
apply_filters( 'bea\silo\localize_terms', false, $taxonomy, $post_type )
```

#### Example

```
<?php
/**
 * Depending on context, add the thematic silo
 *
 * @author Maxime CULEA
 *
 * @param $hide_or_display
 * @param $taxonomy
 * @param $post_type
 *
 * @return bool
 */
function bea_where_to_localize_thematic( $hide_or_display, $taxonomy, $post_type ) {
    return BEA_TAX_THEMATIC_NAME === $taxonomy && BEA_CPT_POST_NAME === $post_type && is_home() ? true : $hide_or_display;
}
add_filter( 'bea\silo\localize_terms', 'bea_where_to_localize_thematic', 10, 3 );
```

### Default localization

By default the plugin is generating a default template view for your silos located on : `silo-{taxonomy_name}.php`.
You Can overwrite this default template by playing withe the following filter : 

```
<?php
/**
 * Change the default taxonomy silo slug
 *
 * @author Maxime CULEA
 *
 * @since 1.1.0
 *
 * @param string $slug The taxonomy's default silo slug.
 * @param string $$taxonomy The taxonomy name working on.
 */
return apply_filters( 'BEA\Helpers\taxonomy_silo_slug', sprintf( 'silo-%s', $taxonomy ), $taxonomy );
```

It is most useful when displaying a silo on homepage - you will be able to come back into deep silo chosen hierarchy. In this case your homepage template and the default one could be the same. So watch out about duplicate content.

## Customize queried terms for the taxonomy

On the hook `bea\silo\term_query\args` you filter the args in order to retrieve the taxonomy's terms. By default `$args` has only `'hide_empty' => false`.
```
<?php
/**
 * Filter the arguments to retrieve given taxonomy's terms.
 *
 * @author Maxime CULEA
 *
 * @since 1.0.0
 *
 * @param array $args Arguments for the WP_Term_Query or the get_terms();
 * @param string $taxonomy The taxonomy name as context.
 */
$args = apply_filters( 'bea\silo\term_query\args', $args, $taxonomy );
```

## Customize returned localized terms

On the hook `bea\silo\term_object` you filter the given array to add or remove some values from the current term for the given taxonomy and post type.
```
<?php
/**
 * Filter term object to add / delete some attributes.
 *
 * @author Maxime CULEA
 *
 * @since 1.0.0
 *
 * @param array $new_item The formatted term object for response.
 * @param \WP_Term $_term The term object.
 * @param string $taxonomy The taxonomy name as context.
 */
return apply_filters( 'bea\silo\term_object', $new_item, $_term, $_term->taxonomy );
```

### Example

```
<?php
/**
 * Add the term's color
 *
 * @author Maxime CULEA
 *
 * @param $new_item
 * @param \WP_Term $_term
 * @param $_taxonomy
 *
 * @return mixed
 */
function bea_silo_add_color( $new_item, \WP_Term $_term, $_taxonomy ) {
    if ( 0 == $new_item['level'] ) {
        $new_item['color'] = sprintf( '#%s', get_field( 'color', $_term, true ) );
    }

    return $new_item;
}
add_filter( 'bea\silo\term_object', 'bea_silo_add_color', 10, 3 );
```

## Display !

On the action `bea\silo\display` you display the wanted silo for the given post types and the taxonomy.
```
<?php
/**
 * Action in purpose to display silo's underscores and html templates depending on the given post types and taxonomy.
 * Underscore templates :
 * - button
 * - results
 * - no results
 *
 * @author Maxime CULEA
 *
 * @since 1.0.0
 *
 * @param array $post_types : array of wanted post type names
 * @param string $taxonomy : the taxonomy name
 */
do_action( 'bea\silo\display', [ {post_type_1}, {post_type_2} ], [ {taxonomy} ] );
```

With the matching args, custom templates (_s) and views (html) can be made  and they are loaded in the below order :

1. Child theme 
2. Theme
3. Silo plugin

* _S templates are conditionally loaded :
    1. silo/templates/{taxonomy}-{template}-tpl.js (1 & 2)
    2. silo/templates/{template}-tpl.js (1 & 2)
    3. templates/{template}-tpl.js (3)
* View :
    1. silo/blocks/{taxonomy}-silo.php (1 & 2)
    2. silo/blocks/silo.php (1 & 2)
    3. blocks/silo.php (3)

# REST Api

A REST Api route is automaclly registered to get contents depending on post types and a taxonomy term.

REST Api route looks like `{ndd}/wp-json/bea/silo?post_types[0]=post&term_id=4`, where :

* post_types is an array of post type names to retrieve the content for
* a silotable taxonomy's term id for the given post types to retrieve the contents for

# Changelog

## 1.1.7 - 31 May 2024
* Fix compatibility bug on REST_REQUEST

## 1.1.6 - 12 May 2022
* Handle empty taxonomy silo case

## 1.1.5 - 24 Janv 2019
* Add new filter on args for register_rest_route in controller

## 1.1.4 - 12 Nov 2018
* Add errors on function get_contents_check

## 1.1.3 - 11 Mar 2018
* Fix items response with childrens

## 1.1.2 - 06 Mar 2018
* Update items response with childrens
* Load JS/Script Data on wp_enqueue_script hook
* Add composer json file

## 1.1.1 - 01 Jun 2017
* Add some filters.

## 1.1.0 - 29 Mai 2017
* Handle a default silo slug and view for a taxonomy.

## 1.0.2 - 29 Mai 2017
* For hierarchical taxonomies, construct full term link with ancestors.

## 1.0.1 - 13 Apr 2017
* Refactoring.
* Fix REST Api class.
* Add display "Blocks" class for _s and view display.

## 1.0.0 - 11 Apr 2017
* Refactoring & reformatting.
* Update readme with usage & example.
* Add plugin's .pot.
* Init with boilerplate 2.1.6.
