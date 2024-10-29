<?php
/**
 * Plugin Name: LH Taxonomy Pinned Posts
 * Plugin URI: https://lhero.org/portfolio/lh-taxonomy-pinned-posts/
 * Description: A better way of pinning posts to taxonomy archives.
 * Author: Peter Shaw
 *  Author URI: https://shawfactor.com
 * Version: 1.00
 * Text Domain: lh_tpps
 * Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!class_exists('LH_Taxonomy_pinned_posts_plugin')) {

class LH_Taxonomy_pinned_posts_plugin {
    
    private static $instance;
    
    private $pinned_post_id;
    
    private $is_sticky_post;
    
static function return_plugin_namespace(){

return 'lh_tpps';

}

static function return_plugin_post_type(){

return 'lh_tpps-post_type';

}

static function return_taxonomy($query){
    
    if (!empty($query->query_vars['taxonomy'])){
        
        return $query->query_vars['taxonomy'];
        
    } elseif (!empty($query->tax_query->queries[0]['taxonomy'])){
        
    return $query->tax_query->queries[0]['taxonomy'];   
        
    } else {
        
        return false;
        
    }
    
}


static function maybe_return_query_var($taxonomy){
    
if ($taxonomy == 'category'){
    
return get_query_var( 'cat' );     
    
} else {
    
 return get_query_var( $taxonomy );    
    
}
    
    
}

static function maybe_return_taxonomy_object($query){
    
$taxonomy = self::return_taxonomy($query);  

$query_var = self::maybe_return_query_var($taxonomy);

if (is_int($query_var)){
    
$term = get_term_by('id', $query_var, $taxonomy);
    
} else {
    
$term = get_term_by('slug', $query_var, $taxonomy);    
    
}

if (isset($term->term_id)){

return $term;

} else {
    
    return false;
}
    
}

static function eligible_taxonomies(){
    
return apply_filters('lh_tpps_taxonomies',array('category'));    
    
    
}

private function register_pinned_post_type() {

$label = 'Pinned Post';

$labels = array(
    'name' => 'Pinnable Post',
      'singular_name' => 'Pinnable Post',
      'menu_name'	=> 'Pinnable Posts',
       'all_items' => 'Pinnable Posts',
      'add_new' => 'Add New',
      'add_new_item' => 'Add New Pinnable Post',
      'edit' => 'Edit pinnable post',
      'edit_item' => 'Edit Pinned Post',
      'new_item' => 'New Pinned Post',
      'view' => 'View Pinned Post',
      'view_item' => 'View Pinned Post',
      'search_items' => 'Search Pinned Posts',
      'not_found' => 'No pinned posts Found',
      'not_found_in_trash' => 'No pinned posts Found in Trash');


register_post_type(self::return_plugin_post_type(), array(
        'label' => $label,
        'description' => '',
        'public' => true,
        'show_ui' => true,
        'show_in_rest' => true,
        'hierarchical' => false,
        'supports' =>  array( 'title','editor', 'author'),
        'labels' => $labels,
         'rewrite' => array('slug' => 'pinned-post'),
        'has_archive' => false,
        'show_in_menu'  => 'edit.php',
        )
    );



}



public function setup_post_types() {


$this->register_pinned_post_type();
 

}


public function edit_form_field($term_obj, $taxonomy = null) {
    
$term_id = $term_obj->term_id;

$taxonomy_post_id = get_term_meta($term_id, self::return_plugin_namespace().'-post_id', true);


$the_taxonomy_post_object = get_post($taxonomy_post_id);

 wp_nonce_field( self::return_plugin_namespace().'-post_id', self::return_plugin_namespace().'-post_id-nonce_field' ); 
    
?>  
  
<tr class="form-field">  
    <th scope="row" valign="top">  
        <label for="<?php echo self::return_plugin_namespace(); ?>-post_id"><?php _e('Pinned Post', self::return_plugin_namespace() ); ?></label>  
    </th>  
    <td>  
<select name="<?php echo self::return_plugin_namespace(); ?>-post_id" id="<?php self::return_plugin_namespace(); ?>-post_id">
<option value="0"><?php _e( 'Nothing Selected', self::return_plugin_namespace() ); ?></option>    
<?php
    
$args = array( 'numberposts' => -1,
'post_type' => self::return_plugin_post_type() );
$taxonomy_posts = get_posts($args);
foreach( $taxonomy_posts as $taxonomy_post ){
?>
<option value="<? echo $taxonomy_post->ID; ?>" <?php if (isset($the_taxonomy_post_object->ID) && ($the_taxonomy_post_object->ID == $taxonomy_post->ID)){ echo 'selected="selected"';  } ?>><?php echo $taxonomy_post->post_title; ?></option>
<?php
    
    
}
    
?>    
    
</select>
    </td>  
</tr>  
  
<?php  
    
    
}


public function save_form_fields( $term_id, $tt_id ) {  
    
	if( isset($_POST[self::return_plugin_namespace().'-post_id-nonce_field']) && wp_verify_nonce( $_POST[self::return_plugin_namespace().'-post_id-nonce_field'], self::return_plugin_namespace().'-post_id' ) ) {
    
$post_id = intval($_POST[self::return_plugin_namespace().'-post_id']);

    if ( isset( $_POST[self::return_plugin_namespace().'-post_id']) && get_post($post_id)  ) {


        update_term_meta($term_id, self::return_plugin_namespace().'-post_id', $post_id);

 
    }  elseif ($post_id == 0  ) {
        
        
        delete_term_meta($term_id, self::return_plugin_namespace().'-post_id');
        
    }
    
	}
}  



	private function get_sticky_query( $pinned_post_id ) {
	    
	    $args = array(
			 		'p'			=>	$pinned_post_id,
			 		'post_type'			=>	self::return_plugin_post_type(),
			 		'posts_per_page'	=>	'1',
		 		);

// The Query
$the_query = new WP_Query($args);

// The Loop
if ( $the_query->have_posts() ) {

return   $the_query->posts[0];  
    
} else {
    
return false;    
    
}

	}


	 /**
	  * Places the pinned post at the top of the list of posts for the taxonomy that is being displayed.
	  *
	  * @param	    array	$posts	The lists of posts to be displayed for the given taxonomy
	  * @return	    array			The updated list of posts with the pinned post set as the first titem
	  *
	  * @since      1.0.0
	  */
	 public function reorder_taxonomy_posts( $posts, $query ) {
	     


	     
	     	 	// We only care to do this for the first page of the archives
	 	if( $query->is_main_query() && is_archive() && 0 == get_query_var( 'paged' ) && !is_admin() && ($term_object = self::maybe_return_taxonomy_object($query))) {
	 	    
	 	if (in_array($term_object->taxonomy, self::eligible_taxonomies())){

		 	
		 	$taxonomy_post_id = get_term_meta($term_object->term_id, self::return_plugin_namespace().'-post_id', true);
		 	
		 	
		 	if (!empty($taxonomy_post_id)){
		 	    
		 	    
		 	    
		 	    
		 	wp_reset_postdata();

		 	// If the query returns an actual post ID, then let's update the posts
		 	if( $sticky_query = $this->get_sticky_query( $taxonomy_post_id ) ) {
		 	    
		 	    $this->pinned_post_id = $taxonomy_post_id;

		 		// Store the sticky post in an array
			 	$new_posts = array( $sticky_query );

			 	// Look to see if the post exists in the current list of posts.
			 	foreach( $posts as $post_index => $post ) {

			 		// If so, then remove it so we don't duplicate its display
			 		if( $sticky_query->ID == $posts[ $post_index ]->ID ) {
				 		unset( $posts[ $post_index ] );
			 		}

			 	}

			 	// Merge the existing array (with the sticky post first and the original posts second)
			 	$posts = array_merge( $new_posts, $posts );

		 	}
		 	    
		 	}
	 	    
	 	}  
	 	    
	 	}
	     
	 return $posts;    
	     
	 }
	 
	 
	/**
	  * Adds a CSS class to make it easy to style the sticky post.
	  *
	  * @param		array	$classes	The array of classes being applied to the given post
	  * @return		array				The updated array of classes for our posts
	  *
	  * @since      1.0.0
	  */
	  public function set_taxonomy_sticky_class( $classes ) {
	      
	      global $post;

	 	// If we've not set the taxonomy sticky post...
	 	if( (is_category() or is_tax()) && empty($this->is_sticky_post) && isset($post->ID) && ($this->pinned_post_id == $post->ID) ) {

		 	// ...append the class to the first post (or the first time this event is raised)
			$classes[] = 'taxonomy-pinned-post';
			
			$this->is_sticky_post = true;


		}

		return $classes;

	 }


    
    public function plugins_loaded(){
        
        
    //Register custom post type 
    add_action('init', array($this,'setup_post_types'));
    
    
    $taxonomy_array = array('category');
    
    
    foreach( self::eligible_taxonomies() as $taxonomy){
        
        
        
    // Add the custom field to the screen 
    add_action( $taxonomy.'_edit_form_fields', array($this,'edit_form_field'), 100, 2 );
    //add_action($taxonomy.'_add_form_fields',array($this,'edit_form_field'), 100, 2 );
    
    
    add_action('edited_'.$taxonomy,  array($this,'save_form_fields'), 10, 2);
    add_action('created_'.$taxonomy,  array($this,'save_form_fields'), 10, 2);
        
    }
    
    
    // Filters for displaying the pinned taxonomy post
	add_filter( 'the_posts', array( $this, 'reorder_taxonomy_posts' ), 10, 2 );
	
	// Adds a class to the pinned taxonomy post
	add_filter( 'post_class', array( $this, 'set_taxonomy_sticky_class' ) );
        
    }
    
      /**
     * Gets an instance of our plugin.
     *
     * using the singleton pattern
     */
    public static function get_instance(){
        if (null === self::$instance) {
            self::$instance = new self();
        }
 
        return self::$instance;
    }

public function __construct() {
    
    // Initialize the count of the sticky post
		$this->is_sticky_post = false;
    
    
    	 //run our hooks on plugins loaded to as we may need checks       
    add_action( 'plugins_loaded', array($this,'plugins_loaded'));
    
    
    
}
    
    
}

$lh_taxonomy_pinned_posts_instance = LH_Taxonomy_pinned_posts_plugin::get_instance();


}




?>