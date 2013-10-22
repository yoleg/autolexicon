<?php
/**
 * @package: autolexicon
 * @author: Oleg Pryadko (oleg@websitezen.com)
 * @createdon: 8/15/12
 * @license: GPL v.3 or later
 */
require_once dirname(__FILE__) . '/autolexiconfield.class.php';
class AutoLexiconResourceHandler extends AutoLexiconHandler {
	function __construct(AutoLexicon &$al, array $config = array()) {
		$modx =& $al->modx;
		$config = array_merge(array(
			'field_class' => 'AutoLexiconResourceField',
			'topic' => 'resource',
			'prefix' => null,
			'cleanup_empty' => $modx->getOption('autolexicon.cleanup_empty', null, true),
			'translate_fields' => $al->commasToArray($modx->getOption('autolexicon.translate_fields', null, 'pagetitle,uri,alias,content,longtitle,description,introtext,menutitle')),
			'translate_tvs' => $al->commasToArray($modx->getOption('autolexicon.translate_tvs', null, '')),
			'replace_fields' => $al->commasToArray($modx->getOption('autolexicon.replace_fields', null, true)),
			'replace_tvs' => $al->commasToArray($modx->getOption('autolexicon.replace_tvs', null, false)),
			'never_replace_fields_list' => $al->commasToArray($modx->getOption('autolexicon.never_replace_fields_list', null, 'pagetitle,uri,alias')),
			'required_fields' => $al->commasToArray($modx->getOption('autolexicon.required_fields', null, 'pagetitle,alias,uri')),
			'default_field' => $modx->getOption('autolexicon.default_field', null, 'pagetitle'),
			'set_as_default' => $al->commasToArray($modx->getOption('autolexicon.set_as_default', null, 'menutitle')),
			'null_value' => $modx->getOption('autolexicon.null_value', null, 'NULL'),
		), $config);
		$config['fields'] = array_merge($config['translate_tvs'], $config['translate_fields']);
		if (!$config['replace_fields']) {
			$config['never_replace_fields_list'] = array_merge($config['never_replace_fields_list'], $config['translate_fields']);
		}
		if (!$config['replace_tvs']) {
			$config['never_replace_fields_list'] = array_merge($config['never_replace_fields_list'], $config['translate_tvs']);
		}
		parent::__construct($al, $config);
	}
}
class AutoLexiconHandler {
	/** @var AutoLexicon A reference to the AutoLexicon object. */
	public $al = null;
	/** @var modX A reference to the modX object. */
	public $modx = null;
	/** @var array A collection of properties to adjust behaviour. */
	public $config = array();
	public $alfieldobjects = array();

	function __construct(AutoLexicon &$al, array $config = array()) {
		$this->al =& $al;
		$this->modx =& $al->modx;
		$this->defaults = array();
		$this->config = array_merge(array(
			'fields' => array(),
			'class_handler' => null,
			'field_class' => null,
			'topic' => 'resource',
			'separator' => '.',
			'prefix' => null,
			'translate_fields' => array(),
			'skip_value_replacement' => array(),
			'required_fields' => array(),
			'default_field' => null,
			'set_as_default' => array(),
			'null_value' => 'NULL',
			'field_class_config' => array(),
		), $config);
		$this->topic = $this->config['topic'];
		$this->prefix = !is_null($this->config['prefix']) ? $this->config['prefix'] : ('al.' . $this->config['topic'] . '.');
		if (empty($this->topic)) {
			throw new AutoLexiconException("Topic cannot be empty in a AutoLexiconField");
		}
	}

	/**
	 * Gets the lexicon key for this object field.
	 *
	 * @param int $object_id
	 * @param string $field
	 * @return string The lexicon key
	 */
	public function getLexiconKey($object_id, $field) {
		return $this->prefix . $object_id . '.' . $field;
	}

	/**
	 * Gets a lexicon value.
	 *
	 * @param string $lexicon_key
	 * @param string $lang
	 * @return null|string The result or null if not found.
	 */
	public function getLexiconValue($lexicon_key, $lang) {
		$this->al->_loadLexiconTopicOnce($lang, $this->topic);
		if (!$this->modx->lexicon->exists($lexicon_key, $lang)) {
			$output = null;
		} else {
			$output = $this->modx->lexicon($lexicon_key, array(), $lang);
			$output = ($output == $lexicon_key) ? null : $output;
		}
		return $output;
	}

	/**
	 * Creates a lexicon tag.
	 *
	 * @param string $key The lexicon key
	 * @return string The lexicon tag
	 */
	public function getLexiconTag($key) {
		return "[[!%{$key}? &topic=`{$this->topic}` &namespace=`autolexicon`]]";
	}

	/**
	 * Replaces the object field values from the lexicon without saving the object.
	 *
	 * @param xPDOObject $object
	 * @param string $lang The language code to translate to
	 * @param bool $process_tags Whether or not to process MODX tags before saving.
	 */
	public function translateObjectFields(xPDOObject $object, $lang, $process_tags = false) {
		foreach ($this->config['fields'] as $field) {
			/** @var $alfo AutoLexiconField */
			$alfo = $this->getALFieldObject($object, $field, $lang);
			$alfo->translate($process_tags);
		}
	}

	/**
	 * PERMANENTLY syncs object with lexicon per AutoLexicon rules.
	 *
	 * @param xPDOObject $object
	 * @param string $lang
	 */
	public function syncObject(xPDOObject $object, $lang) {
		// todo: choose default for missing entries: leave blank, use default lang value, or static default
		foreach ($this->config['fields'] as $field) {
			/** @var $alfo AutoLexiconField */
			$alfo = $this->getALFieldObject($object, $field, $lang);
			$alfo->sync();
		}
		$object->save();
	}

	/**
	 * Get an object representing a single field of a single language of a single object.
	 *
	 * @param xPDOObject $object
	 * @param $field
	 * @param $lang
	 * @throws AutoLexiconException
	 * @return AutoLexiconField
	 */
	public function getALFieldObject(xPDOObject $object, $field, $lang) {
		if (empty($field) || empty($lang)) {
			throw new AutoLexiconException('Empty parameters for getALFieldObject');
		}
		$class = $this->config['field_class'];
		$id = (int)$object->get('id');
		if (empty($id)) {
			throw new AutoLexiconException("AutoLexicon cannot yet handle objects without ids.");
		}
		if (!isset($this->alfieldobjects[$id])) {
			$this->alfieldobjects[$id] = array();
		}
		if (!isset($this->alfieldobjects[$id][$field])) {
			$this->alfieldobjects[$id][$field] = array();
		}
		if (!isset($this->alfieldobjects[$id][$field][$lang])) {
			$config = $this->config['field_class_config'];
			$al =& $this;
			$alf = new $class($al, $object, $field, $lang, $config);
			if (!($alf instanceof AutoLexiconField)) {
				throw new AutoLexiconException("Could not generate AutoLexiconField");
			}
			$this->alfieldobjects[$id][$field][$lang] = $alf;
		}
		return $this->alfieldobjects[$id][$field][$lang];
	}

	public function _removeLexiconLinks(array $object_ids) {
		$name_array = array();
		$prefix = '';
		foreach ($object_ids as $object_id) {
			$search = array();
			$search[$prefix . 'name:LIKE'] = ($this->prefix . $object_id . $this->config['separator'] . '%');
			$name_array[] = $search;
			$prefix = 'OR:';
		}
		$query = $this->modx->newQuery('modLexiconEntry', array(
			'topic' => $this->topic,
			'namespace' => 'autolexicon',
			$name_array,
		));
		$entries = $this->modx->getCollection('modLexiconEntry', $query);
		foreach ($entries as $entry) {
			/** @var $entry modLexiconEntry */
			$entry->remove();
		}
	}

	public function _getLexiconTagRegEx() {
		$prefix = str_replace('.', '\.', $this->prefix);
		$namespace = 'autolexicon';
		$part_t = '[\&]topic\=\`' . $this->topic . '\`';
		$part_n = '[\&]namespace\=\`' . $namespace . '\`';
		return '\[\[[\!]*\%' . $prefix . '([0-9]+)\.([\w-]+)\?\s*(' .
			'(' . $part_t . '\s*' . $part_n . ')|' .
			'(' . $part_n . '\s*' . $part_t . ')' .
			')\]\]';
	}

	public function _parseLexiconTags($string, $lang, $regex = null, $forced_values = null) {
		if (is_null($regex)) {
			$regex = $this->_getLexiconTagRegEx();
		}
		if (is_null($forced_values)) {
			$forced_values = array(
				$this->config['default_field'] => '',
			);
		}
		// example tv tag output: [[!%al.resource.11.test? &amp;topic=`resource` &amp;namespace=`autolexicon`]]
		$regex2 = str_replace('[\&]', '[\&]amp[\;]', $regex);
		$regexes = array($regex, $regex2);
		foreach ($regexes as $regex) {
			$regex = '/' . $regex . '/';
			preg_match_all($regex, $string, $matches, PREG_SET_ORDER);
			foreach ($matches as $val) {
				$full_lexicon_tag = $val[0];
				$object_id = $val[1];
				$field = $val[2];
				if (array_key_exists($field, $forced_values)) {
					$lexicon_value = $forced_values[$field];
				} else {
					$lexicon_key = $this->getLexiconKey($object_id, $field);
					$lexicon_value = $this->getLexiconValue($lexicon_key, $lang);
				}
				$string = str_replace($full_lexicon_tag, $lexicon_value, $string);
			}
		}
		return $string;
	}

}
