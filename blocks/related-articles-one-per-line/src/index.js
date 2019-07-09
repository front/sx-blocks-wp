/**
 * Internal dependencies
 */
import edit from './edit';
import translations from './translations';

const { __, setLocaleData } = wp.i18n;
const { registerBlockType } = wp.blocks;
const { dispatch, select } = wp.data;

const current_lang = document.documentElement.lang;

setLocaleData(translations[current_lang]);

const category = {
  slug: 'starterx', // needs to match the css class of the block container: ".wp-block-starterx-[block-name]"
  title: __('StarterX Blocks'),
};

// Check the category
const currentCategories = select('core/blocks').getCategories()
.filter(item => item.slug !== category.slug);
// Register the category
dispatch('core/blocks').setCategories([category, ...currentCategories]);

// Register the block
registerBlockType('starterx/related-articles-one-per-line', {
  title: __('Related Articles One Per Line One Per Line'),
  icon: 'admin-page',
  category: category.slug,
  keywords: [ __('recent posts'), __('recent articles one per line'), __('articles') ],

  edit,

  save () {
    return null;
  },
});
