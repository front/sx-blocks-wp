const { __ } = wp.i18n;
const { dispatch, select } = wp.data;

export const category = {
  slug: 'starterx', // needs to match the css class of the block container: ".wp-block-starterx-[block-name]"
  title: __('StarterX Blocks'),
};

export const registerCategory = cat => {
  // Check the category
  const currentCategories = select('core/blocks')
  .getCategories()
  .filter(item => item.slug !== cat.slug);

  dispatch('core/blocks').setCategories([cat, ...currentCategories]);
};
