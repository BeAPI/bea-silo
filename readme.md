# BEA - Silo

Dev oriented plugin to add silo feature :

* It will use `wp_localize_script` to localize (javascript), depending on some conditions, terms from wanted taxonomies against wanted post type.
* You can overwrite default term's object to add/remove some values.
* By default a custom [REST Api](https://bitbucket.org/beapi/bea-silo#markdown-header-rest-api) route is created to ease matching post type and taxonomy silo content.
* These matching content will be displayed using a _s template located by default into `templates/` folder. But they can be overwrited into theme or child-theme.

# Installation

## WordPress

* Download and install using the built-in WordPress plugin installer.
* Site Activate in the "Plugins" area of the admin.
* Optionally drop the entire `bea-silo` directory into `plugins`.
* Add into your theme's functions.php file or through a plugin, the expected [Steps](https://bitbucket.org/beapi/bea-silo#markdown-header-steps).

## Composer

// TODO : make composer

# Steps

## Register post type support

While waiting [ticket/40413](https://core.trac.wordpress.org/ticket/40413) merge into core, add post type support like this :
```
#!php
<?php add_post_type_support( {post_type}, 'silo', {taxonomy_1}, {taxonomy_2}, {etc} ); ```
After merge, you could do :
```
#!php
<?php register_post_type( {post_type}, [ 'supports' => [ 'silo' => [ {taxonomy_1}, {taxonomy_2}, {etc} ] ] ] ); ```

## Define localize conditions 

On the hook `bea\silo\localize_terms` you specify where to localize your terms taxonomy for a post type.
```
#!php
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

### Example

```
#!php
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

## Customize queried terms for the taxonomy

On the hook `bea\silo\term_query\args` you filter the args in order to retrieve the taxonomy's terms. By default `$args` has only `'hide_empty' => false`.
```
#!php 
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
#!php
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
#!php
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

# REST Api

// TODO : Finish rest api documentation !

# Changelog ##

## 1.0.0 - 11 Apr 2017
* Refactoring & reformatting.
* Update readme with usage & example.
* Add plugin's .pot.
* Init with boilerplate 2.1.6