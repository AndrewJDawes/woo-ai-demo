=== Enterprise Search and Recommendations on local Docker | WPSolr ===
Contributors: wpsolr
Author: wpsolr
Current Version: 24.0.1
Author URI: https://www.wpsolr.com/
Tags: search, ai search, elasticsearch, related posts, similar posts
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 24.0.1
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enterprise Search and Recommendations on local Docker.

== Description ==

= Built on world-class search engines =
- [WordPress search for Weaviate AI (keywords/hybrid/vector)](https://www.wpsolr.com/how-to-easily-configure-weaviate-on-wordpress/?utm_source=wordpress.org&utm_campaign=wpsolr_free)
- [WordPress search for Vespa.ai (keywords/hybrid/vector)](https://www.wpsolr.com/how-to-easily-configure-vespa-ai-search-on-wordpress/?utm_source=wordpress.org&utm_campaign=wpsolr_free)
- [WordPress search for OpenSearch (keywords)](https://www.wpsolr.com/how-to-easily-configure-opensearch-on-wordpress/?utm_source=wordpress.org&utm_campaign=wpsolr_free)
- [WordPress search for Apache Solr (keywords)](https://www.wpsolr.com/how-to-easily-configure-solr-on-wordpress/?utm_source=wordpress.org&utm_campaign=wpsolr_free)
- [WordPress search for Elasticsearch (keywords)](https://www.wpsolr.com/how-to-easily-configure-elasticsearch-on-wordpress/?utm_source=wordpress.org&utm_campaign=wpsolr_free)

= Wizard for OpenSolr included =
If you do not like installing local search engines, our **60 seconds wizard** will configure **100% of your WordPress search** with:
- A **free Solr index** hosted by our [partner **Opensolr**](https://opensolr.com)
- **Ajax search** & **autocompletion**
- **Facets** (filters)
- **Related posts**.

= Need even more features? =
Consider our flagship plugin [WPSolr Enterprise](https://www.wpsolr.com/?utm_source=wordpress.org&utm_campaign=wpsolr_free)
- **Manage more than one engine**. For instance, Elasticsearch for back-end search, Weaviate AI for front-end search, Solr for suggestions, Recombee for AI Recommendation widgets.
- **AI search personalization** (Algolia AI search personalize, Google Retail search personalization)
- **AI recommendations** (Algolia Recommend, Amazon Personalize, Recombee, Google Retail AI Personalization)
- **Use hosting providers** like Amazon Opensearch, Solr Opensolr, Solr Searchstax, Opensearch & Elasticsearch Bonsai, Algolia, Elasticsearch Elastic, Google Retail, Recombee
- **Advanced filters** (hierarchy tree, slider, range, select, select2, color picker, data picker)
- **WooCommerce** (in/out of stock, attribute variations, price variations, add to basket in suggestions, order/coupon search in back-end)
- **WPML**
- **Flatsome**
- **MyListing**
- **Jobify**
- **Listify**
- And much, **much more**…

= Download WPSolr Enterprise =
You can [download](https://www.wpsolr.com/download/?utm_source=wordpress.org&utm_campaign=wpsolr_free) and install it on your staging environments. No signup. No registration key. No email asked. Just a download.

== Changelog ==

= 24.0.1 =
* (fix) Security patch
* (fix) Fatal error due to <a href="https://www.wpsolr.com/forums/topic/php-fatal-error-declaration-of-monologloggeremergency/" target="_new" rel="noopener">Monolog incompatible version</a> detected with WordPress 6.7.2

= 24.0 =
* (fix) OpenSearch shard and replication
* (fix) OpenSearch logs warning

= 23.9.1 =
* (new) Vespa.ai vector/hybrid/keywords search

= 23.9 =
* (new) 60 seconds configuration wizard with free opensolr.com hosting

= 23.8 =
* (new) Related posts with Weaviate: retrieve semantically similar posts, with extra filters.
* (new) Related posts with Elasticsearch: retrieve text similar posts (More Like This), with extra filters.
* (new) Related posts with OpenSearch: retrieve text similar posts (More Like This), with extra filters.
* (new) Related posts with Solr: retrieve text similar posts (More Like This), with extra filters.
* (fix) Fix missing $ajax_delay_ms initialization

= 23.7 =
* (new) Add settings to use any jQuery-Autocomplete option with suggestions
* (new) Add post excerpt to boosts
* (new) Index taxonomy’s featured image url for helping catalog discovery in external tools like Algolia

= 23.6 =
* (new) Index featured image url for helping catalog discovery in external tools
* (fix) real-time indexing not working on creation
* (fix) SQL full-text search should not be executed
* (fix) Random sort with Elasticsearch
* (fix) Deprecated parse_str()

= 23.5 =
* (Fix) Solr syntax error with facets containing ” and ”
* (Fix) Facets containing “:” are not selected
* (fix) Facets javascript error in backend search when several views

= 23.4 =
* (deprecation) Deprecated Elasticsearch server 7.x version. Requires Elasticsearch server 8.x version
* (php client) Update Elasticsearch PHP client from version 7. to version 8.
* (new) Weaviate GPT4All vectorizer
* (new) Self-signed node certificate setting for docker OpenSearch SSL
* (new) Self-signed node certificate setting for docker Elasticsearch SSL
* (new) Self-signed node certificate setting for docker Apache Solr SSL
* (new) Self-signed node certificate setting for docker Weaviate SSL
* (new) Button to clone index settings
* (fix) Option to switch Solarium client from http to curl
* (fix) Weaviate slider (numeric and dates),and range, facets
* (fix) Weaviate sort on archive taxonomies

= 23.3 =
* Tested with PHP 8.1 and WordPress 6.2.2
* (new) Rerank Weaviate search results with the <a href="https://weaviate.io/developers/weaviate/modules/retriever-vectorizer-modules/reranker-transformers" rel="noopener" target="_blank">local cross-encoder transformers</a>.
* (Fix) <a href="https://www.wpsolr.com/forums/topic/wonky-results-when-terms-have-same-name-but-belong-to-different-parents">Taxonomy archives with duplicate term names</a>.
* (Fix) Weaviate maximum number of facet items
* (Fix) Weaviate alphabetical sort of facet items

= 23.1 =
* Tested with PHP 8.1 and WordPress 6.2.2
* (new) Set horizontal/vertical orientation on views’ facets. For instance, choose horizontal facets on admin search and vertical on front-end search.
* (fix) Boost categories does not work
* (fix) Wrong archive results with duplicated category names
* (fix) Filters are wrongly showing results with partial matching
* (Fix) Fix some “utf-8-middle-byte” errors with mb_substr()

= 23.0 =
* (fix) Tested with PHP8.1
* (fix) Apply <a href="https://weaviate.io/developers/weaviate/configuration/schema-configuration#property-tokenization">property tokenization</a> to Weaviate indices, to prevent tokenization on facets.
* (fix) <a href="https://www.wpsolr.com/forums/topic/error-in-region-field/">OpenSolr credentials error</a>.
