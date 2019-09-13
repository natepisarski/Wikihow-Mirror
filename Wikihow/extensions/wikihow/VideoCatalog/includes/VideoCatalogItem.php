<?php

/* Database schema */
/*
CREATE TABLE video_catalog_item (
  vci_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  vci_name VARCHAR(255) NOT NULL DEFAULT '',
  vci_step INT UNSIGNED NOT NULL DEFAULT 0,
  vci_version INT UNSIGNED NOT NULL DEFAULT 0,
  vci_published VARCHAR(14) NOT NULL DEFAULT '',
  vci_original_article_id INT UNSIGNED,
  vci_poster_url VARCHAR(255) NOT NULL DEFAULT '',
  vci_clip_url VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (vci_id),
  UNIQUE KEY (vci_name,vci_step,vci_version)
);
 */

/**
 * Video catalog item.
 */
class VideoCatalogItem extends VideoCatalogObject {

	/* Protected Members */

	protected $id;
	protected $name;
	protected $step;
	protected $version;
	protected $published;
	protected $originalArticleId;
	protected $posterUrl;
	protected $clipUrl;

	/* Protected Methods */

	/**
	 * Construct item.
	 *
	 * @param {StdObject|array} $row Row object or array of values keyed by column name
	 */
	protected function __construct( $row ) {
		$row = (object)$row;
		$this->id = $row->vci_id;
		$this->name = $row->vci_name;
		$this->step = $row->vci_step;
		$this->version = $row->vci_version;
		$this->published = $row->vci_published;
		$this->originalArticleId = $row->vci_original_article_id;
		$this->posterUrl = $row->vci_poster_url;
		$this->clipUrl = $row->vci_clip_url;
	}

	/* Public Methods */

	public function getId() {
		return $this->id;
	}

	public function getName() {
		return $this->name;
	}

	public function getStep() {
		return $this->step;
	}

	public function getVersion() {
		return $this->version;
	}

	public function getPublishedDate() {
		return $this->published;
	}

	public function getOriginalArticleId() {
		return $this->originalArticleId;
	}

	public function getPosterUrl() {
		return $this->posterUrl;
	}

	public function getClipUrl() {
		return $this->clipUrl;
	}

	/**
	 * Create item.
	 *
	 * @return {boolean} Created successfully
	 */
	public function create() {
		$item = $this;
		return parent::createObject( function () use ( $item ) {
			$db = static::getDB( DB_MASTER );
			$inserted = $db->insert(
				'video_catalog_item',
				[
					'vci_name' => $item->name,
					'vci_step' => $item->step,
					'vci_version' => $item->version,
					'vci_published' => wfTimestamp( TS_MW ),
					'vci_original_article_id' => $item->originalArticleId,
					'vci_poster_url' => $item->posterUrl,
					'vci_clip_url' => $item->clipUrl
				],
				__METHOD__
			);
			if ( $inserted ) {
				$item->id = $db->insertId();
			}

			return $inserted;
		} );
	}

	/**
	 * Update item.
	 *
	 * @return {boolean} Created successfully
	 */
	public function update() {
		$item = $this;
		return parent::updateObject( function () use ( $item ) {
			return static::getDB( DB_MASTER )->update(
				'video_catalog_item',
				[
					'vci_published' => wfTimestamp( TS_MW ),
					'vci_original_article_id' => $item->originalArticleId,
					'vci_poster_url' => $item->posterUrl,
					'vci_clip_url' => $item->clipUrl
				],
				[ 'vci_id' => $item->id ],
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
		$item = $this;
		return parent::deleteObject( function () use ( $item ) {
			return static::getDB( DB_MASTER )->delete(
				'video_catalog_item',
				[ 'vci_id' => $item->id ],
				__METHOD__
			);
		} );
	}

	/**
	 * Set poster URL.
	 *
	 * @param {string} $posterUrl URL of poster image
	 */
	public function setPosterUrl( $posterUrl ) {
		$this->posterUrl = $posterUrl;
		$this->dirty = true;
	}

	/**
	 * Set clip URL.
	 *
	 * @param {string} $clipUrl URL of short clip video
	 */
	public function setClipUrl( $clipUrl ) {
		$this->clipUrl = $clipUrl;
		$this->dirty = true;
	}

	/**
	 * Set published date.
	 *
	 * @param {string} $published Set published date in TS_MW format
	 */
	public function setPublishedDate( $published ) {
		$this->published = $published;
		$this->dirty = true;
	}

	/**
	 * Set orignal article ID.
	 *
	 * @param {string} $originalArticleId Set original article ID
	 */
	public function setOriginalArticleId( $originalArticleId ) {
		$this->originalArticleId = $originalArticleId;
		$this->dirty = true;
	}

	/* Public Static Methods */

	/**
	 * Get existing item from video source URL.
	 *
	 * The source URL can be from any size, only the name, step and version are used to find the
	 * corresponding item.
	 *
	 * @param {string} $sourceUrl Video source URL to get item from
	 * @return {VideoCatalogItem|null} Found item or null if not found
	 */
	public static function getFromSourceUrl( $sourceUrl ) {
		$source = VideoCatalog::parseSourceUrl( $sourceUrl );
		if ( !$source ) {
			// Parse failed
			return null;
		}
		return parent::readObject( function () use ( $source ) {
			return static::getDB()->selectRow(
				'video_catalog_item',
				[ '*' ],
				[
					'vci_name' => $source->name,
					'vci_step' => $source->step,
					'vci_version' => $source->version
				],
				__METHOD__
			);
		} );
	}

	/**
	 * Make new item from video source URL.
	 *
	 * The source URL can be from any size, only the name, step and version are used to find the
	 * corresponding item.
	 *
	 * @param {string} $sourceUrl Video source URL
	 * @return {VideoCatalogItem} New item
	 */
	public static function newFromSourceUrl( $sourceUrl ) {
		$source = VideoCatalog::parseSourceUrl( $sourceUrl );
		if ( !$source ) {
			// Parse failed
			return null;
		}
		return new static( [
			'vci_id' => null,
			'vci_name' => $source->name,
			'vci_step' => $source->step,
			'vci_version' => $source->version,
			'vci_published' => null,
			'vci_original_article_id' => null,
			'vci_poster_url' => '',
			'vci_clip_url' => ''
		] );
	}
}
