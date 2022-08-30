<?php 

/**
 * Generate component files for custom blocks.
 */
class BlockFileGenerator {
	protected $blockType;
	protected $blockName;

  /**
   * Constructor function.
   * 
   * Set block type and block name.
   *
   * @param string $blockSlug The block slug.
   */
  public function __construct ( String $blockSlug ) {
    $this->blockType = $blockSlug;

    $this->blockName = array_reduce(explode('-', $this->blockType), function ($carry, $item) {
			$carry .= ucfirst($item);
			return $carry;
		});

    // echo '<pre>' . var_dump( $this ) . '</pre>';
  }

  /**
   * Generate custom block files when registering block.
   * 
   */
  public function generate () {
    $blockDirPath = get_template_directory() . '/blocks/' . $this->blockType;

    if ( is_dir( $blockDirPath ) ) return;

    // Generate files
    mkdir( $blockDirPath, 0777, true );

    file_put_contents($blockDirPath . '/' . $this->blockType . '.scss', $this->get_template(get_template_directory() . '/src/templates/block-template.scss'));
		file_put_contents($blockDirPath . '/' . $this->blockType . '.php', $this->get_template(get_template_directory() . '/src/templates/block-template.php'));
		file_put_contents($blockDirPath . '/' . $this->blockType . '.js', $this->get_template(get_template_directory() . '/src/templates/block-template.js'));
  }

  /**
   * Get template path.
   * 
   * @param string $templatePath Template file path name.
   */
  protected function get_template ( $templatePath ) {
    // genius!
    return str_replace(['*PX_BLOCK_TYPE*', '*PX_BLOCK_NAME*'], [$this->blockType, $this->blockName], file_get_contents($templatePath));
  }
}