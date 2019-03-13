/**
 * Internal dependencies
 */
import { category, registerCategory } from '../../index.js';
import edit from './edit';
import './style.scss';
import './editor.scss';

const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;

// Register the category
registerCategory(category);

// Register the block
registerBlockType('starterx/related-articles', {
  title: __('Related Articles'),
  icon: 'admin-page',
  category: category.slug,
  keywords: [ __('recent posts'), __('recent articles'), __('articles') ],

  edit,

  save () {
    return null;
  },
});
