<?php
class PluginSapanMozammel
{
  public function __construct()
  {

    //create custom post type
    add_action('init', array($this, 'create_custom_post_type'));

    // add assets (js, css, etc)
    add_action('wp_enqueue_scripts', array($this, 'load_assets'));

    // add shortcode
    add_shortcode("contact-form", array($this, 'contact_form_shortcode'));

    // add script
    add_action('wp_footer', array($this, 'load_scripts'));

    // register rest api
    add_action('rest_api_init', array($this, 'register_rest_api'));
  }

  public function create_custom_post_type()
  {
    $args = array(
      'public' => true,
      'has_archive' => true,
      'support' => array('title'),
      'exclude_form_search' => true,
      'publicly_queryable' => false,
      'capability' => 'manage_options',
      'labels' => array(
        'name' => 'Contact Form',
        'singular_name' => 'Contact Fomr Entry'
      ),
      'menu_icon' => 'dashicons-media-text',
    );

    register_post_type('simple_contact_form', $args);
  }

  public function load_assets()
  {
    wp_enqueue_style(
      'simple-contact-form',
      plugin_dir_url(__FILE__) . '/css/simple-contact-form.css',
      array(),
      1,
      'all'
    );
    wp_enqueue_script(
      'simple-contact-form',
      plugin_dir_url(__FILE__) . '/js/simple-contact-form.js',
      array('jquery'),
      1,
      true,
    );
  }

  public function contact_form_shortcode()
  {
?>
    <div class="simple-contact-form-wrapper">
      <h1 class="scf-title">Send us what you want to know.</h1>
      <p class="scf-desc">please full the below form</p>
      <form id="simple-contact-form">
        <input name="name" type="text" class="scf-name scf-control" placeholder="Full Name">
        <input name="email" type="email" class="scf-email scf-control" placeholder="Email Address">
        <input name="phone" type="tel" class="scf-phone scf-control" placeholder="Phone Number">
        <textarea name="message" class="scf-message scf-control" placeholder="Message"></textarea>
        <button type="submit" class="scf-button">Submit</button>
      </form>
    </div>
  <?php
  }

  public function load_scripts()
  {
  ?>
    <script>
      var nonce = '<?php echo wp_create_nonce('wp_rest') ?>';
      (function($) {
        $("#simple-contact-form").submit(function(event) {
          event.preventDefault();
          var form = $(this).serialize();
          $.ajax({
            method: 'post',
            url: "<?php echo get_rest_url(null, 'simple-contact-form/v1/send-email'); ?>",
            headers: {
              'X-WP-Nonce': nonce
            },
            data: form
          })
        })
      })(jQuery)
    </script>
<?php
  }

  public function register_rest_api()
  {
    register_rest_route('simple-contact-form/v1', 'send-email', array(
      'methods' => 'POST',
      'callback' => array($this, 'handle_contact_form')
    ));
  }

  public function handle_contact_form($data)
  {
    $headers = $data->get_headers();
    $params = $data->get_params();
    $nonce = $headers['x_wp_nonce'][0];

    if (!wp_verify_nonce($nonce, 'wp_rest')) {
      return new WP_REST_Response('Message not sent', 422);
    }

    $post_id = wp_insert_post([
      'post_type' => 'simple_contact_form',
      'post_title' => 'Contact enquary',
      'post_status' => 'publish',
    ]);

    if ($post_id) {
      return new WP_REST_Response('Thenk you for the email', 200);
    }
  }
}
new PluginSapanMozammel;
