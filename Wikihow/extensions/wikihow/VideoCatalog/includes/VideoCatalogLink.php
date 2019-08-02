<?php

/* Database schema */
/*
CREATE TABLE video_catalog_link (
  vcl_article_id INT UNSIGNED NOT NULL DEFAULT 0,
  vcl_item_id INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (vcl_article_id),
  KEY (vcl_item_id)
);
*/

/**
 * Link between an article and a video catalog item.
 */
class VideoCatalogLink extends VideoCatalogObject {

	/* Protected Members */

	protected $articleId;
	protected $itemId;

	/* Protected Methods */

	/**
	 * Construct link.
	 *
	 * @param {StdObject|array} $row Row object or array of values keyed by column name
	 */
	protected function __construct( $row ) {
		$row = (object)$row;
		$this->articleId = $row->vcl_article_id;
		$this->itemId = $row->vcl_item_id;
	}

	/* Public Methods */

	public function getArticleId() {
		return $this->articleId;
	}

	public function getItemId() {
		return $this->itemId;
	}

	/**
	 * Create link.
	 *
	 * @return {boolean} Created successfully
	 */
	public function create() {
		$link = $this;
		return parent::createObject( function () use ( $link ) {
			return static::getDB( DB_MASTER )->insert(
				'video_catalog_link',
				[
					'vcl_item_id' => $link->itemId,
					'vcl_article_id' => $link->articleId
				],
				__METHOD__
			);
		} );
	}

	/**
	 * Update link.
	 *
	 * @return {boolean} Created successfully
	 */
	public function update() {
		$link = $this;
		return parent::updateObject( function () use ( $link ) {
			return static::getDB( DB_MASTER )->update(
				'video_catalog_link',
				[ 'vcl_item_id' => $link->itemId ],
				[ 'vcl_article_id' => $link->articleId ],
				__METHOD__
			);
		} );
	}

	/**
	 * Delete link.
	 *
	 * @return {boolean} Created successfully
	 */
	public function delete() {
		$link = $this;
		return parent::deleteObject( function () use ( $link ) {
			return static::getDB( DB_MASTER )->delete(
				'video_catalog_link',
				[ 'vcl_article_id' => $link->articleId ],
				__METHOD__
			);
		} );
	}

	/**
	 * Set item ID.
	 *
	 * @param {string} $itemID ID of item to link to
	 */
	public function setItemId( $itemId ) {
		$this->itemId = $itemId;
		$this->dirty = true;
	}

	/* Public Static Methods */

	/**
	 * Get existing link from article ID.
	 *
	 * @param {integer} $articleId ID of article to get link for
	 * @return {VideoCatalogLink|null} Found link or null if not found
	 */
	public static function getFromArticleId( $articleId ) {
		return parent::readObject( function () use ( $articleId ) {
			return static::getDB()->selectRow(
				'video_catalog_link',
				[ '*' ],
				[ 'vcl_article_id' => $articleId ],
				__METHOD__
			);
		} );
	}

	/**
	 * Make new link from article ID and item ID.
	 *
	 * @param {integer} $articleId ID of article to link
	 * @param {integer} $articleId ID of item to link
	 * @return {VideoCatalogLink} New link
	 */
	public static function newFromArticleIdAndItemId( $articleId, $itemId ) {
		return new static( [
			'vcl_article_id' => $articleId,
			'vcl_item_id' => $itemId
		] );
	}
}
