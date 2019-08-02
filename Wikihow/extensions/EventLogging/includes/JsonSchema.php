<?php
/**
 * JSON Schema Validation Library
 *
 * Copyright (c) 2005-2012, Rob Lanphier
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 * 	* Redistributions of source code must retain the above copyright
 * 	  notice, this list of conditions and the following disclaimer.
 *
 * 	* Redistributions in binary form must reproduce the above
 * 	  copyright notice, this list of conditions and the following
 * 	  disclaimer in the documentation and/or other materials provided
 * 	  with the distribution.
 *
 * 	* Neither my name nor the names of my contributors may be used to
 * 	  endorse or promote products derived from this software without
 * 	  specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Rob Lanphier <robla@wikimedia.org>
 * @copyright © 2011-2012 Rob Lanphier
 * @license http://jsonwidget.org/LICENSE BSD-3-Clause
 */

/*
 * Note, this is a standalone component.  Please don't mix MediaWiki-specific
 * code or library calls into this file.
 */

class JsonSchemaException extends Exception {

	/**
	 * Arguments for the message
	 *
	 * @var array
	 */
	public $args;

	/**
	 * @var string 'validate-fail' or 'validate-fail-null'
	 */
	public $subtype;

	public function __construct( $code /* ... */ ) {
		parent::__construct( $code );
		$this->code = $code;
		$this->args = func_get_args();
		array_shift( $this->args );
	}
}

class JsonUtil {
	/**
	 * Converts the string into something safe for an HTML id.
	 * performs the easiest transformation to safe id, but is lossy
	 * @param int|string $var
	 * @return string
	 * @throws JsonSchemaException
	 */
	public static function stringToId( $var ) {
		if ( is_int( $var ) ) {
			return (string)$var;
		}
		if ( is_string( $var ) ) {
			return preg_replace( '/[^a-z0-9\-_:\.]/i', '', $var );
		}

		throw new JsonSchemaException( 'jsonschema-idconvert', self::encodeForMsg( $var ) );
	}

	/**
	 * Converts data to JSON format with pretty-formatting, but limited to a single line and escaped
	 * to be suitable for wikitext message parameters.
	 * @param array $data
	 * @return string
	 */
	public static function encodeForMsg( $data ) {
		if ( class_exists( 'FormatJson' ) && function_exists( 'wfEscapeWikiText' ) ) {
			$json = FormatJson::encode( $data, "\t", FormatJson::ALL_OK );
			// Literal newlines can't appear in JSON string values, so this neatly folds the formatting
			$json = preg_replace( "/\n\t+/", ' ', $json );
			return wfEscapeWikiText( $json );
		}

		return json_encode( $data );
	}

	/**
	 * Given a type (e.g. 'object', 'integer', 'string'), return the default/empty
	 * value for that type.
	 * @param string $thistype
	 * @return mixed
	 */
	public static function getNewValueForType( $thistype ) {
		switch ( $thistype ) {
			case 'object':
				$newvalue = [];
				break;
			case 'array':
				$newvalue = [];
				break;
			case 'number':
				case 'integer':
					$newvalue = 0;
					break;
				case 'string':
					$newvalue = '';
					break;
				case 'boolean':
					$newvalue = false;
					break;
				default:
					$newvalue = null;
					break;
		}

		return $newvalue;
	}

	/**
	 * Return a JSON-schema type for arbitrary data $foo
	 * @param mixed $foo
	 * @return mixed
	 */
	public static function getType( $foo ) {
		if ( $foo === null ) {
			return null;
		}

		switch ( gettype( $foo ) ) {
			case 'array':
				$retval = 'array';
				foreach ( array_keys( $foo ) as $key ) {
					if ( !is_int( $key ) ) {
						$retval = 'object';
					}
				}
				return $retval;
			case 'integer':
			case 'double':
				return 'number';
			case 'boolean':
				return 'boolean';
			case 'string':
				return 'string';
			default:
				return null;
		}
	}

	/**
	 * Generate a schema from a data example ($parent)
	 * @param mixed $parent
	 * @return array
	 */
	public static function getSchemaArray( $parent ) {
		$schema = [];
		$schema['type'] = self::getType( $parent );
		switch ( $schema['type'] ) {
			case 'object':
				$schema['properties'] = [];
				foreach ( $parent as $name ) {
					$schema['properties'][$name] = self::getSchemaArray( $parent[$name] );
				}

				break;
			case 'array':
				$schema['items'] = [];
				$schema['items'][0] = self::getSchemaArray( $parent[0] );
				break;
		}

		return $schema;
	}
}

/*
 * Internal terminology:
 *   Node: "node" in the graph theory sense, but specifically, a node in the
 *    raw PHP data representation of the structure
 *   Ref: a node in the object tree.  Refs contain nodes and metadata about the
 *    nodes, as well as pointers to parent refs
 */

/**
 * Structure for representing a generic tree which each node is aware of its
 * context (can refer to its parent).  Used for schema refs.
 */
class TreeRef {
	public $node;
	public $parent;
	public $nodeindex;
	public $nodename;
	public function __construct( $node, $parent, $nodeindex, $nodename ) {
		$this->node = $node;
		$this->parent = $parent;
		$this->nodeindex = $nodeindex;
		$this->nodename = $nodename;
	}
}

/**
 * Structure for representing a data tree, where each node (ref) is aware of its
 * context and associated schema.
 */
class JsonTreeRef {
	public function __construct( $node, $parent = null, $nodeindex = null,
			$nodename = null, $schemaref = null ) {
		$this->node = $node;
		$this->parent = $parent;
		$this->nodeindex = $nodeindex;
		$this->nodename = $nodename;
		$this->schemaref = $schemaref;
		$this->fullindex = $this->getFullIndex();
		$this->datapath = [];
		if ( $schemaref !== null ) {
			$this->attachSchema();
		}
	}

	/**
	 * Associate the relevant node of the JSON schema to this node in the JSON
	 * @param null|string $schema
	 */
	public function attachSchema( $schema = null ) {
		if ( $schema !== null ) {
			$this->schemaindex = new JsonSchemaIndex( $schema );
			$this->nodename =
				isset( $schema['title'] ) ? $schema['title'] : 'Root node';
			$this->schemaref = $this->schemaindex->newRef( $schema, null, null, $this->nodename );
		} elseif ( $this->parent !== null ) {
			$this->schemaindex = $this->parent->schemaindex;
		}
	}

	/**
	 * Return the title for this ref, typically defined in the schema as the
	 * user-friendly string for this node.
	 * @return string
	 */
	public function getTitle() {
		if ( isset( $this->nodename ) ) {
			return $this->nodename;
		}
		if ( isset( $this->node['title'] ) ) {
			return $this->node['title'];
		}

		return $this->nodeindex;
	}

	/**
	 * Rename a user key.  Useful for interactive editing/modification, but not
	 * so helpful for static interpretation.
	 * @param string $newindex
	 */
	public function renamePropname( $newindex ) {
		$oldindex = $this->nodeindex;
		$this->parent->node[$newindex] = $this->node;
		$this->nodeindex = $newindex;
		$this->nodename = $newindex;
		$this->fullindex = $this->getFullIndex();
		unset( $this->parent->node[$oldindex] );
	}

	/**
	 * Return the type of this node as specified in the schema.  If "any",
	 * infer it from the data.
	 * @return mixed
	 */
	public function getType() {
		if ( array_key_exists( 'type', $this->schemaref->node ) ) {
			$nodetype = $this->schemaref->node['type'];
		} else {
			$nodetype = 'any';
		}

		if ( $nodetype === 'any' ) {
			if ( $this->node === null ) {
				return null;
			}
			return JsonUtil::getType( $this->node );
		}

		return $nodetype;
	}

	/**
	 * Return a unique identifier that may be used to find a node.  This
	 * is only as robust as stringToId is (i.e. not that robust), but is
	 * good enough for many cases.
	 * @return string
	 */
	public function getFullIndex() {
		if ( $this->parent === null ) {
			return 'json_root';
		}

		return $this->parent->getFullIndex() . '.' . JsonUtil::stringToId( $this->nodeindex );
	}

	/**
	 * Get a path to the element in the array.  if $foo['a'][1] would load the
	 * node, then the return value of this would be array('a',1)
	 * @return array
	 */
	public function getDataPath() {
		if ( !is_object( $this->parent ) ) {
			return [];
		}
		$retval = $this->parent->getDataPath();
		$retval[] = $this->nodeindex;
		return $retval;
	}

	/**
	 * Return path in something that looks like an array path.  For example,
	 * for this data: [{'0a':1,'0b':{'0ba':2,'0bb':3}},{'1a':4}]
	 * the leaf node with a value of 4 would have a data path of '[1]["1a"]',
	 * while the leaf node with a value of 2 would have a data path of
	 * '[0]["0b"]["oba"]'
	 * @return string
	 */
	public function getDataPathAsString() {
		$retval = '';
		foreach ( $this->getDataPath() as $item ) {
			$retval .= '[' . json_encode( $item ) . ']';
		}
		return $retval;
	}

	/**
	 * Return data path in user-friendly terms.  This will use the same
	 * terminology as used in the user interface (1-indexed arrays)
	 * @return string
	 */
	public function getDataPathTitles() {
		if ( !is_object( $this->parent ) ) {
			return $this->getTitle();
		}

		return $this->parent->getDataPathTitles() . ' -> '
			. $this->getTitle();
	}

	/**
	 * Return the child ref for $this ref associated with a given $key
	 * @param string $key
	 * @return JsonTreeRef
	 * @throws JsonSchemaException
	 */
	public function getMappingChildRef( $key ) {
		$snode = $this->schemaref->node;
		$schemadata = [];
		$nodename = $key;
		if ( array_key_exists( 'properties', $snode ) &&
			array_key_exists( $key, $snode['properties'] ) ) {
			$schemadata = $snode['properties'][$key];
			$nodename = isset( $schemadata['title'] ) ? $schemadata['title'] : $key;
		} elseif ( array_key_exists( 'additionalProperties', $snode ) ) {
			// additionalProperties can *either* be a boolean or can be
			// defined as a schema (an object)
			if ( gettype( $snode['additionalProperties'] ) === 'boolean' ) {
				if ( !$snode['additionalProperties'] ) {
					throw new JsonSchemaException( 'jsonschema-invalidkey',
												$key, $this->getDataPathTitles() );
				}
			} else {
				$schemadata = $snode['additionalProperties'];
				$nodename = $key;
			}
		}
		$value = $this->node[$key];
		$schemai = $this->schemaindex->newRef( $schemadata, $this->schemaref, $key, $key );

		return new JsonTreeRef( $value, $this, $key, $nodename, $schemai );
	}

	/**
	 * Return the child ref for $this ref associated with a given index $i
	 * @param int $i
	 * @return JsonTreeRef
	 */
	public function getSequenceChildRef( $i ) {
		// TODO: make this conform to draft-03 by also allowing single object
		if ( array_key_exists( 'items', $this->schemaref->node ) ) {
			$schemanode = $this->schemaref->node['items'][0];
		} else {
			$schemanode = [];
		}
		$itemname = isset( $schemanode['title'] ) ? $schemanode['title'] : "Item";
		$nodename = $itemname . " #" . ( (string)$i + 1 );
		$schemai = $this->schemaindex->newRef( $schemanode, $this->schemaref, 0, $i );

		return new JsonTreeRef( $this->node[$i], $this, $i, $nodename, $schemai );
	}

	/**
	 * Validate the JSON node in this ref against the attached schema ref.
	 * Return true on success, and throw a JsonSchemaException on failure.
	 * @return bool
	 */
	public function validate() {
		if ( array_key_exists( 'enum', $this->schemaref->node ) &&
			!in_array( $this->node, $this->schemaref->node['enum'] ) ) {
			$e = new JsonSchemaException( 'jsonschema-invalid-notinenum',
				JsonUtil::encodeForMsg( $this->node ), $this->getDataPathTitles() );
			$e->subtype = 'validate-fail';
			throw $e;
		}
		$datatype = JsonUtil::getType( $this->node );
		$schematype = $this->getType();
		if ( $datatype === 'array' && $schematype === 'object' ) {
			// PHP datatypes are kinda loose, so we'll fudge
			$datatype = 'object';
		}
		if ( $datatype === 'number' && $schematype === 'integer' &&
			 $this->node == (int)$this->node ) {
			// Alright, it'll work as an int
			$datatype = 'integer';
		}
		if ( $datatype != $schematype ) {
			if ( $datatype === null && !is_object( $this->parent ) ) {
				$e = new JsonSchemaException( 'jsonschema-invalidempty' );
				$e->subtype = 'validate-fail-null';
				throw $e;
			}
			$datatype = $datatype ?: 'null';
			$e = new JsonSchemaException( 'jsonschema-invalidnode',
				$schematype, $datatype, $this->getDataPathTitles() );
			$e->subtype = 'validate-fail';
			throw $e;
		}
		switch ( $schematype ) {
			case 'object':
				$this->validateObjectChildren();
				break;
			case 'array':
				$this->validateArrayChildren();
				break;
		}
		return true;
	}

	private function validateObjectChildren() {
		if ( array_key_exists( 'properties', $this->schemaref->node ) ) {
			foreach ( $this->schemaref->node['properties'] as $skey => $svalue ) {
				$keyRequired = array_key_exists( 'required', $svalue ) ? $svalue['required'] : false;
				if ( $keyRequired && !array_key_exists( $skey, $this->node ) ) {
					$e = new JsonSchemaException( 'jsonschema-invalid-missingfield', $skey );
					$e->subtype = 'validate-fail-missingfield';
					throw $e;
				}
			}
		}

		foreach ( $this->node as $key => $value ) {
			$jsoni = $this->getMappingChildRef( $key );
			$jsoni->validate();
		}
		return true;
	}

	private function validateArrayChildren() {
		$length = count( $this->node );
		for ( $i = 0; $i < $length; $i++ ) {
			$jsoni = $this->getSequenceChildRef( $i );
			$jsoni->validate();
		}
	}
}

/**
 * The JsonSchemaIndex object holds all schema refs with an "id", and is used
 * to resolve an idref to a schema ref.  This also holds the root of the schema
 * tree.  This also serves as sort of a class factory for schema refs.
 */
class JsonSchemaIndex {
	public $root;
	public $idtable;
	/**
	 * The whole tree is indexed on instantiation of this class.
	 * @param string $schema
	 * @return void
	 */
	public function __construct( $schema ) {
		$this->root = $schema;
		$this->idtable = [];

		if ( $this->root === null ) {
			return;
		}

		$this->indexSubtree( $this->root );
	}

	/**
	 * Recursively find all of the ids in this schema, and store them in the
	 * index.
	 * @param string $schemanode
	 */
	public function indexSubtree( $schemanode ) {
		if ( !array_key_exists( 'type', $schemanode ) ) {
			$schemanode['type'] = 'any';
		}
		$nodetype = $schemanode['type'];
		switch ( $nodetype ) {
			case 'object':
				foreach ( $schemanode['properties'] as $value ) {
					$this->indexSubtree( $value );
				}

				break;
			case 'array':
				foreach ( $schemanode['items'] as $value ) {
					$this->indexSubtree( $value );
				}

				break;
		}
		if ( isset( $schemanode['id'] ) ) {
			$this->idtable[$schemanode['id']] = $schemanode;
		}
	}

	/**
	 * Generate a new schema ref, or return an existing one from the index if
	 * the node is an idref.
	 * @param string $node
	 * @param string $parent
	 * @param int $nodeindex
	 * @param string $nodename
	 * @return TreeRef
	 * @throws JsonSchemaException
	 */
	public function newRef( $node, $parent, $nodeindex, $nodename ) {
		if ( array_key_exists( '$ref', $node ) ) {
			if ( strspn( $node['$ref'], '#' ) != 1 ) {
				throw new JsonSchemaException( 'jsonschema-badidref', $node['$ref'] );
			}
			$idref = $node['$ref'];
			try {
				$node = $this->idtable[$idref];
			}
			catch ( Exception $e ) {
				throw new JsonSchemaException( 'jsonschema-badidref', $node['$ref'] );
			}
		}

		return new TreeRef( $node, $parent, $nodeindex, $nodename );
	}
}
