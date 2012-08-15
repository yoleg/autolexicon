<?php
/**
 * @package:
 * @author: Oleg Pryadko (oleg@websitezen.com)
 * @createdon: 8/11/12
 * @license: GPL v.3 or later
 */
abstract class AutoLexiconField {
    /** @var modX A reference to the modX object. */
    public $modx = null;
    /** @var AutoLexicon A reference to the AutoLexicon object. */
    public $al = null;
    /** @var array A collection of properties to adjust AutoLexicon behaviour. */
    public $config = array();
    /** @var xPDOObject The xpdo object */
    public $object;
    /** @var string The xpdoobject field name */
    public $field;
    /** @var string The language code */
    public $lang;
    /** @var string The lexicon key name */
    public $_lexicon_key;
    public $topic = null;
    function __construct(AutoLexicon &$al, xPDOObject $object, $field, $lang, $config = array()) {
        if (empty($object) || empty($field) || empty($lang)) {
            throw new AutoLexiconException("Incorrect parameters for getALFieldObject");
        }
        $topic = $this->topic;
        if (empty($topic)) {
            throw new AutoLexiconException("Topic cannot be empty");
        }
        $this->al =& $al;
        $this->modx =& $al->modx;
        $this->object =& $object;
        $this->field = $field;
        $this->lang = $lang;
        $this->config = $config;
        $this->object_id = $this->modx->getOption('object_id',$config,$object->get('id'));
        $this->_lexicon_key = $this->al->getLexiconKey($object->get('id'), $field);
        // moved to getLexiconValue
//        if (!$this->modx->lexicon->exists($this->_lexicon_key,$lang)) {
//            $this->modx->lexicon->load($this->lang . ':autolexicon:resource');
//        }
        // The value to save in the lexicon if the lexicon entry is not to be used
        $this->config['null_value'] = $this->al->config['null_value'];
    }
    public function getConfig($key) {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }
        switch($key) {
            case 'skip_value_replacement':
                return false;
            case 'use_another_as_default':
                return null;
        }
        throw new AutoLexiconException("AutoLexiconField::getConfig({$key}) - key not in config.");
    }
    /**
     * Creates a lexicon tag for use when the object field is directly parsed.
     *
     * @return string The lexicon tag
     */
    public function generateLexiconTag() {
        return $this->al->getLexiconTag($this->_lexicon_key,$this->topic);
    }
    /**
     * Gets the lexicon value for this object and field.
     *
     * @return null|string The result or null if not found.
     */
    public function _getLexiconValue() {
        return $this->al->getLexiconValue($this->_lexicon_key, $this->topic, $this->lang);
    }
    /**
     * Updates the lexicon entries for a object field.
     *
     * @param mixed $new_value
     */
    public function _setLexiconValue($new_value) {
        $entry = $this->_getLexiconEntry();
        // update the current language
        $new_value = (string) $new_value ? $new_value : '';
        // save new or changed values
        if ($new_value != $entry->get('value') || !$entry->get('id')) {
            $entry->set('value', $new_value);
            $entry->save();
        }
    }
    /**
     * Finds or creates a lexicon entry object for the object id, field, and language.
     *
     * @return modLexiconEntry|null|object
     */
    public function _getLexiconEntry() {
        $lexicon_key = $this->_lexicon_key;
        $base_array = array(
            'name' => $lexicon_key,
            'topic' => $this->topic,
            'namespace' => 'autolexicon',
            'language' => $this->lang,
        );
        $entry = $this->modx->getObject('modLexiconEntry',$base_array);
        if(!($entry instanceof modLexiconEntry)) {
            /** @var $entry modLexiconEntry */
            $entry = $this->modx->newObject('modLexiconEntry');
            $entry->fromArray($base_array);
        }
        if(!($entry instanceof modLexiconEntry)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR,"AutoLexicon could not create a lexicon entry for field {$this->field}, lang {$this->lang}, and object id {$this->object_id}");
        }
        return $entry;
    }
    public function _hasLexiconTag($string) {
        foreach(array('[[%','[[!%') as $marker) {
            if (strpos($string, $marker) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Update OTHER lexicon entries
     *
     * @param null|string $new_value
     * @param null|string $old_value
     */
    public function _syncLexiconEntry($new_value, $old_value=null) {
        $entry = $this->_getLexiconEntry();
        // if other entry is new or previously synced, update with the new value
        $new = $entry->get('id');
        $previously_synced = $new ? false : (!is_null($old_value) && $entry->get('value') == $old_value);
        if ($new || $previously_synced) {
            $entry->set('value', $new_value);
            $entry->save();
        }
    }

    public function _getObjectFieldValue() {
        return $this->object->get($this->field);
    }

    /**
     * @param mixed $value Set the object field
     * @param bool $allow_save True if saving related objects is allowed.
     * Used for situations where related objects cannot be temporarily value-replaced
     * and must be saved to the database.
     */
    public function _setObjectField($value, $allow_save=false) {
        $this->object->set($this->field, $value);
    }

    /** updaters */
    /**
     * Loads the translation from the lexicon and temporarily replaces the object field with the new value.
     *
     * @param bool $process_tags Whether or not to process MODX tags in the field before setting.
     */
    public function translate($process_tags=false) {
        $lexicon_content = $this->_getLexiconValue();
        if (is_null($lexicon_content)) {
            return;
        }
        // if the lexicon tag is NULL, but not the object content, skip this field
        if ($lexicon_content == $this->getConfig('null_value')) {
            $object_content = $this->_getObjectFieldValue();
            if ($object_content != $this->getConfig('null_value')) {
                return;
            }
        }
        // unused
        if ($process_tags) {
            $this->modx->parser->processElementTags('', $lexicon_content, true, false);
        }
        $this->_setObjectField($lexicon_content);
    }

    /**
     * Syncs the lexicon with the current set object value, and replaces the object value with lexicon tags if applicable.
     *
     * @param null $object_value Use this as the new object value instead of the currently loaded value.
     * @return mixed|null The new value synced to the lexicon, or null if nothing was saved.
     */
    public function sync($object_value=null) {
        $object_value = is_null($object_value) ? $this->_getObjectFieldValue() : $object_value;
        if (empty($object_value) && $this->getConfig('required_field')) {
            $this->modx->log(modX::LOG_LEVEL_ERROR,"AutoLexicon: {$this->field} cannot be empty. Lang: {$this->lang}. Object: {$this->topic} {$this->object_id}");
            return null;
        }
        $value_has_lexicon_tag = $this->_hasLexiconTag($object_value);
        $lexicon_value = $this->_getNewLexiconValue($object_value, $value_has_lexicon_tag);
        if (!is_null($lexicon_value)) {
            $this->_updateLexiconValue($lexicon_value);
        }
        // save lexicon tag to field in object DB if not already has one
        $substitute_value = $this->_getSubstituteObjectValue($object_value, $value_has_lexicon_tag);
        if (!is_null($substitute_value) && $substitute_value !== $object_value) {
            // allow save
            $this->_setObjectField($substitute_value, true);
        }
        return $lexicon_value;
    }

    protected function _updateLexiconValue($lexicon_value) {
        $this->_setLexiconValue($lexicon_value);
        // create empty slots for the other languages if they don't already exist
        $old_value = $this->_getLexiconValue();
        foreach ($this->al->config['langs'] as $other_lang) {
            if ($other_lang == $this->lang) {
                continue;
            }
            $this->_syncLexiconEntry($lexicon_value, $old_value);
        }
        return $lexicon_value;
    }

    protected function _getNewLexiconValue($object_value, $value_has_lexicon_tag) {
        $lexicon_value = $object_value;
        // special treatment for fields that already contain at least one lexicon key
        if ($value_has_lexicon_tag) {
            $lexicon_tag = $this->generateLexiconTag();
            // prevent temporary lexicon tag from overwriting content
            if ($object_value == $lexicon_tag) {
                $lexicon_tag = null;
            }
            // prevent lexicon tag from being stored in lexicon
            $lexicon_value = $this->getConfig('null_value');
        }
        return $lexicon_value;
    }

    public function _getSubstituteObjectValue($object_value, $value_has_lexicon_tag) {
        if ($value_has_lexicon_tag) {
            return null;
        }
        // for non-tag-replaced fields, only store value of default language
        if ($this->getConfig('skip_value_replacement')) {
            if ($this->lang == $this->al->config['default_lang']) {
                $substitute_value = $object_value;
            } else {
                $default_lang_field = $this->al->getALFieldObject($this->object, $this->field, $this->al->config['default_lang']);
                $substitute_value = $default_lang_field->_getLexiconValue();
            }
        } elseif ($object_value) {
            // create a lexicon tag for the value
            $substitute_value = $this->generateLexiconTag();
        } elseif ($other_field = $this->getConfig('use_another_as_default')) {
            // use another field's value as the default
            $other_alf = $this->al->getALFieldObject($this->object, $other_field, $this->lang);
            $substitute_value = $other_alf->generateLexiconTag();
        } else {
            // or use a blank value as the default
            $substitute_value = '';
        }
        return $substitute_value;
    }

}
