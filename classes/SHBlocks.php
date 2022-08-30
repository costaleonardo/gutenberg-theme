<?php 

require_once get_template_directory() . '/classes/helpers/BlockFileGenerator.php';

/**
 * Site Hub Block class
 * 
 * @property array $customBlocks All custom blocks.
 * @property array $customBlockCategories Block meta.
 */
class SHBlocks {
  public $customBlocks;
  protected $customBlockCategories;

  /**
   * Constructor function.
   * 
   * Set block type and block name.
   *
   * @param string $blockSlug The block slug.
   */
  public function __construct () {
    $this->customBlockCategories = [
      [
        'slug' => 'custom-blocks',
        'title' => __( 'Custom Blocks', 'custom-blocks' )
      ]
    ];

    // Add custom block here
    $this->customBlocks = [
			[
				'name'            => 'test',
				'title'           => __('Test'),
				'description'     => __('A custom test block.'),
				'render_template' => get_template_directory() . '/blocks/test/test.php',
				'category'        => 'custom-blocks',
				'icon'            => 'align-center',
				'keywords'        => ['test', 'display'],
				'supports' => [
					'align' => false
				]
			],
			[
				'name'            => 'accordion',
				'title'           => __('Accordion'),
				'description'     => __('A custom accordion block.'),
				'render_template' => get_template_directory() . '/blocks/accordion/accordion.php',
				'category'        => 'custom-blocks',
				'icon'            => 'align-center',
				'keywords'        => ['accordion', 'display'],
				'supports' => [
					'align' => false
				]
			]
    ];

    $this->init();

    // echo '<pre>' . var_dump( $this ) . '</pre>';
  }

  /**
	 * Initialize block logic (check that ACF has the functionality needed and register hooks)
	 *
	 * @return void
	 */
  public function init () {

    if ( !function_exists( 'acf_register_block_type' ) ) return;

    add_action( 'acf/init', [ $this, 'register_acf_block_types' ] );
    add_action( 'acf/field_group/admin_head', [ $this, 'generate_block_files' ] ); // generate new block when new custom field is registered
    add_filter( 'allowed_block_types', [ $this, 'allowed_block_types' ] );
    add_filter( 'block_categories', [ $this, 'register_custom_block_categories' ], 10, 2 );
  }

	/**
	 * Register acf block types.
	 *
	 * @return void
	 */
  public function register_acf_block_types () {

    foreach ( $this->customBlocks as $block ) {
      acf_register_block_type( $block );
    }

  }

	/**
	 * Restrict the default available block types
	 * cf. https://wordpress.stackexchange.com/a/326963
	 *
	 * @param Array $allowedBlocks
	 * @return void
	 */
  public function allowed_block_types ( $allowedBlocks ) {
    $allowedBlocks = [
			// 'core/paragraph',
			// 'core/heading',
			// 'core/list'      
    ];

		foreach ($this->customBlocks  as $block) {
			$allowedBlocks[] = 'acf/' . $block['name'];
		}

		return $allowedBlocks;
  }  

	/**
	 * Generate all the necessary files for our custom blocks
	 *
	 * @return void
	 */
  public function generate_block_files () {
    foreach ( $this->customBlocks as $block ) {
      $generator = new BlockFileGenerator($block['name']);
			$generator->generate();
    }
  }

	/**
	 * Add custom categories to the blocks list
	 *
	 * @param array $categories Array of block categories
	 * @param WP_Post $post Post being loaded
	 * @return array
	 */
  public function register_custom_block_categories ( $categories, $post ) {
    return array_merge($categories, $this->customBlockCategories);
  }
}