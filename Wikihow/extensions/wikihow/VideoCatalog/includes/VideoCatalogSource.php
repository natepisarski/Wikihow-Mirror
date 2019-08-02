<?php

/* Database schema */
/*
CREATE TABLE video_catalog_source (
  vcs_item_id INT UNSIGNED NOT NULL DEFAULT 0,
  vcs_size INT UNSIGNED NOT NULL DEFAULT 0,
  vcs_url VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (vcs_item_id,vcs_size),
  KEY (vcs_item_id)
);
 */

/**
 * Video catalog source.
 */
class VideoCatalogSource extends VideoCatalogObject {

	/* Protected Members */

	protected $itemId;
	protected $url;
	protected $size;

	/* Protected Methods */

	/**
	 * Construct item.
	 *
	 * @param {StdObject|array} $row Row object or array of values keyed by column name
	 */
	protected function __construct( $row ) {
		$row = (object)$row;
		$this->itemId = $row->vcs_item_id;
		$this->url = $row->vcs_url;
		$this->size = $row->vcs_size;
	}

	/* Public Methods */

	public function getItemId() {
		return $this->itemId;
	}

	public function getSize() {
		return $this->size;
	}

	public function getUrl() {
		return $this->url;
	}

	/**
	 * Create item.
	 *
	 * @return {boolean} Created successfully
	 */
	public function create() {
		$source = $this;
		return parent::createObject( function () use ( $source ) {
			$db = static::getDB( DB_MASTER );
			return $db->replace(
				'video_catalog_source',
				[ 'vcs_item_id', 'vcs_size' ],
				[
					'vcs_item_id' => $source->itemId,
					'vcs_url' => $source->url,
					'vcs_size' => $source->size
				],
				__METHOD__
			);
		} );
	}

	/**
	 * Update item.
	 *
	 * @return {boolean} Created successfully
	 */
	public function update() {
		$source = $this;
		return parent::updateObject( function () use ( $source ) {
			return static::getDB( DB_MASTER )->update(
				'video_catalog_source',
				[
					'vcs_url' => $source->url
				],
				[
					'vcs_item_id' => $source->itemId,
					'vcs_size' => $source->size
				],
				__METHOD__
			);
		} );
	}

	/**
	 * Delete item.
	 *
	 * @return {boolean} Created successfully
	 */
	public function delete() {
		$source = $this;
		return parent::deleteObject( function () use ( $source ) {
			return static::getDB( DB_MASTER )->delete(
				'video_catalog_source',
				[
					'vcs_item_id' => $source->itemId,
					'vcs_size' => $source->size
				],
				__METHOD__
			);
		} );
	}

	/* Public Static Methods */

	/**
	 * Make new item from video source URL.
	 *
	 * @param {integer} $itemId Item ID to create source for
	 * @param {string} $url Video source URL
	 * @return {VideoCatalogSource} New source
	 */
	public static function newFromItemIdAndUrl( $itemId, $url ) {
		$source = VideoCatalog::parseSourceUrl( $url );
		if ( !$source ) {
			// Parse failed
			return null;
		}
		return new static( [
			'vcs_item_id' => $itemId,
			'vcs_url' => $url,
			'vcs_size' => $source->size
		] );
	}
}
