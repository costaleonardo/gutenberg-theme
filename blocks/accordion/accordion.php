<?php

/**
 * @param array $block The block settings and attributes.
 * @param string $content The block inner HTML (empty).
 * @param boolean $is_preview True during AJAX preview.
 * @param integer $post_id The post ID this block is saved to.
 */
$id = $block['name'] . '-' . $block['id'];
$blockName = str_replace('acf/', '', $block['name']);
$classes = 'pxblock pxblock--' . $blockName;
$blockFields = get_fields();

if (!empty($block['className'])) $classes .= ' ' . $block['className'];
if (!empty($block['align'])) $classes .= ' align' . $block['align'];
if (!empty($blockFields['block_id'])) $id = str_replace(' ', '-', strtolower($blockFields['block_id']));
?>

<div id="<?= $id ?>" class="<?= $classes ?> fpo">

	<p class="fpo-title"><?= $blockName ?></p>

	<?php if ($is_preview) : ?>
		<span class="block-badge"><?= $block['title'] ?></span>
	<?php endif; ?>
</div>
