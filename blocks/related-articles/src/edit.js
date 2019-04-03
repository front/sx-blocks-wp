const { __ } = wp.i18n;
const apiFetch = wp.apiFetch;
const {
  InspectorControls,
  RichText,
} = wp.editor;
const {
  BaseControl,
  PanelBody,
  Placeholder,
  RangeControl,
  Spinner,
  ToggleControl,
  Toolbar,
  TreeSelect,
} = wp.components;
const { withSelect } = wp.data;
const { dateI18n, format, __experimentalGetSettings } = wp.date;
const { Component, Fragment, RawHTML } = wp.element;
const { addQueryArgs } = wp.url;

/**
 * Group by key
 *
 * @param {Array} xs Array of object
 *
 * @param {String} key Property
 *
 * @return {Array} Array of object grouped by key.
 */
function groupBy (xs, key) {
  return xs.reduce(function (rv, x) {
    (rv[x[key]] = rv[x[key]] || []).push(x);
    return rv;
  }, {});
}

/**
 * Returns terms in a tree form.
 *
 * @param {Array} flatTerms  Array of terms in flat format.
 *
 * @return {Array} Array of terms in tree format.
 */
function buildCategoriesTree (flatTerms) {
  const termsByParent = groupBy(flatTerms, 'parent');
  const fillWithChildren = terms => {
    return terms.map(term => {
      const children = termsByParent[ term.id ];
      return {
        ...term,
        children: children && children.length ?
          fillWithChildren(children) :
          [],
      };
    });
  };

  return fillWithChildren(termsByParent[ '0' ] || []);
}

/**
 * Module Constants
 */
const MIN_POSTS_COLUMNS = 2;
const MAX_POSTS_COLUMNS = 6;

class RelatedArticlesEdit extends Component {
  constructor () {
    super(...arguments);
    this.state = {
      categoriesList: [],
      tagsList: [],
    };
  }

  componentDidMount () {
    this.isStillMounted = true;
    this.fetchCategoriesRequest = apiFetch({
      path: addQueryArgs(`/wp/v2/categories`, { per_page: -1 }),
    }).then(
      categoriesList => {
        if (this.isStillMounted) {
          this.setState({ categoriesList });
        }
      }
    ).catch(
      () => {
        if (this.isStillMounted) {
          this.setState({ categoriesList: [] });
        }
      }
    );

    this.fetchTagsRequest = apiFetch({
      path: addQueryArgs(`/wp/v2/tags`, { per_page: -1 }),
    }).then(
      tagsList => {
        if (this.isStillMounted) {
          this.setState({ tagsList });
        }
      }
    ).catch(
      () => {
        if (this.isStillMounted) {
          this.setState({ tagsList: [] });
        }
      }
    );
  }

  componentWillUnmount () {
    this.isStillMounted = false;
  }

  render () {
    const { attributes, setAttributes, latestPosts } = this.props;
    const { categoriesList, tagsList } = this.state;
    const { displayPostDate, categories, tags, columns, title, titleLevel, offset } = attributes;

    const inspectorControls = (
      <InspectorControls>
        <PanelBody title={ __('Related Articles Settings') }>
          <p>Title Level</p>
          <Toolbar
            controls={ [1, 2, 3, 4, 5, 6].map(index => ({
              icon: 'heading',
              isActive: index === titleLevel,
              onClick: () => setAttributes({ titleLevel: index }),
              subscript: String(index),
            })) } />

          <RangeControl
            label={ __('Columns') }
            value={ columns }
            onChange={ value => setAttributes({ columns: value }) }
            min={ MIN_POSTS_COLUMNS }
            max={ MAX_POSTS_COLUMNS }
          />

          <TreeSelect
            key="query-controls-category-select"
            label={ __('Category') }
            noOptionLabel={ __('All') }
            tree={ buildCategoriesTree(categoriesList) }
            selectedId={ categories }
            onChange={ value => setAttributes({ categories: '' !== value ? value : undefined }) }
          />

          <TreeSelect
            key="query-controls-tag-select"
            label={ __('Tag') }
            noOptionLabel={ __('All') }
            tree={ tagsList }
            selectedId={ tags }
            onChange={ value => setAttributes({ tags: '' !== value ? value : undefined }) }
          />

          <ToggleControl
            label={ __('Display post date') }
            checked={ displayPostDate }
            onChange={ () => setAttributes({ displayPostDate: ! displayPostDate }) }
          />

          <BaseControl label={ __('Offset') }>
            <input
              type="number"
              onChange={ event => setAttributes({ offset: parseInt(event.target.value) }) }
              value={ offset }
              min="0"
              step="1"
            />
          </BaseControl>
        </PanelBody>
      </InspectorControls>
    );

    const hasPosts = Array.isArray(latestPosts) && latestPosts.length;
    if (! hasPosts) {
      return (
        <Fragment>
          { inspectorControls }
          <Placeholder
            icon="admin-post"
            label={ __('Related Articles') }
          >
            { ! Array.isArray(latestPosts) ?
              <Spinner /> :
              __('No articles found.')
            }
          </Placeholder>
        </Fragment>
      );
    }

    const dateFormat = __experimentalGetSettings().formats.date;
    const tagName = 'h' + titleLevel;

    return (
      <Fragment>
        { inspectorControls }
        <div className="related-articles">
          <RichText
            identifier="title"
            className="related-articles__title"
            tagName={ tagName }
            value={ title }
            onChange={ value => setAttributes({ title: value }) }
            formattingControls={ [] }
          />
          <div className={ `related-articles__items wp-block-columns has-${columns}-columns` }>
            { Array.from({ length: columns }).map((_v, i) => {
              const post = latestPosts[i];

              if (!post) {
                return <div className="related-articles__item wp-block-column" key={ i }></div>;
              }

              const titleTrimmed = post.title.rendered.trim();
              return (
                <div className="related-articles__item wp-block-column" key={ i }>
                  <img src={ post.featured_media_data.source_url } alt={ post.featured_media_data.alt_text } />

                  { displayPostDate && post.date_gmt &&
                  <time dateTime={ format('c', post.date_gmt) } className="related-articles__post-date">
                    { dateI18n(dateFormat, post.date_gmt) }
                  </time>
                  }

                  <p className="related-articles__item__title">
                    { titleTrimmed ? (
                      <RawHTML>{ titleTrimmed }</RawHTML>) : __('(Untitled)') }
                  </p>
                </div>
              );
            })}
          </div>
        </div>
      </Fragment>
    );
  }
}

export default withSelect((select, props) => {
  const { columns, categories, tags, offset } = props.attributes;
  const { getEntityRecords } = select('core');
  const latestPostsQuery = {
    categories,
    tags,
    order: 'desc',
    orderby: 'date',
    per_page: columns,
    offset,
  };

  return {
    latestPosts: getEntityRecords('postType', 'post', latestPostsQuery),
  };
})(RelatedArticlesEdit);
