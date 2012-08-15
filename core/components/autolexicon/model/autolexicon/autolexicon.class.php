<?php
/**
 * AutoLexicon
 *
 * Copyright 2012 Oleg Pryadko <oleg@websitezen.com>
 * Based on Babel 2.2.5 by Jakob Class <jakob.class@class-zec.de>
 *
 * This file is part of AutoLexicon for MODX Revolution.
 *
 * AutoLexicon is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * AutoLexicon is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * AutoLexicon; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package autolexicon
 */
class AutoLexiconException extends Exception {
    protected $params = array();

    function __construct($message = "", array $params = array(), $code = 0, Exception $previous = null) {
        parent::__construct($message, $code);
        $this->params = $params;
    }

    public function getParams() {
        return $this->params;
    }
}

require_once dirname(__FILE__) . '/autolexiconeventhandler.class.php';
require_once dirname(__FILE__) . '/autolexiconresourcefield.class.php';
// todo: make sure pagetitle, alias, and uri are never empty in lexicon
// todo-important: make alias in non-default language not update from DB via javascript
// todo: add support for MODX lexicon manager ANY language
// todo: VersionX integration
// todo: subdivide topics by resource or context? Change namespace to autolexicon-resource?
class AutoLexicon {
    /**
     * @access protected
     * @var array A collection of preprocessed chunk values.
     */
    protected $chunks = array();
    /**
     * @access public
     * @var modX A reference to the modX object.
     */
    public $modx = null;
    /**
     * @access public
     * @var array A collection of properties to adjust AutoLexicon behaviour.
     */
    public $config = array();
    public $alfieldobjects;
    public $topics = array();
    public $field_classes = array();
    /** @var AutoLexiconEventHandler */
    public $event_handler;

    /**
     * The AutoLexicon Constructor.
     *
     * This method is used to create a new AutoLexicon object.
     *
     * @param modX &$modx A reference to the modX object.
     * @param array $config A collection of properties that modify AutoLexicon
     * behaviour.
     * @throws AutoLexiconException
     * @return AutoLexicon A unique AutoLexicon instance.
     */
    function __construct(modX &$modx, array $config = array()) {
        $this->field_classes = array(
            'modResource' => 'AutoLexiconResourceField',
        );
        $this->modx =& $modx;
        $corePath = $this->modx->getOption('autolexicon.core_path', null, $modx->getOption('core_path') . 'components/autolexicon/');
        $assetsUrl = $this->modx->getOption('autolexicon.assets_url', null, $modx->getOption('assets_url') . 'components/autolexicon/');
        $this->config = array_merge(array(
            'corePath' => $corePath,
            'chunksPath' => $corePath . 'elements/chunks/',
            'chunkSuffix' => '.chunk.tpl',
            'cssUrl' => $assetsUrl . 'css/',
            'jsUrl' => $assetsUrl . 'js/',
            'translate_settings' => $this->commasToArray($modx->getOption('autolexicon.translate_settings', null, 'base_url,site_url')),
            'langs' => $this->commasToArray($modx->getOption('autolexicon.languages', null, 'en, es')),
            'sync_fields' => $this->commasToArray($modx->getOption('autolexicon.sync_fields', null, 'pagetitle,uri,alias,content,longtitle,description,introtext,menutitle')),
            'sync_tvs' => $this->commasToArray($modx->getOption('autolexicon.sync_tvs', null, 'content_below')),
            'skip_value_replacement' => $this->commasToArray($modx->getOption('autolexicon.skip_value_replacement', null, 'pagetitle,longtitle,alias,uri')),
            'required_fields' => $this->commasToArray($modx->getOption('autolexicon.required_fields', null, 'pagetitle,alias,uri')),
            'set_pagetitle_as_default' => $this->commasToArray($modx->getOption('autolexicon.set_pagetitle_as_default', null, 'menutitle')),
            'session_edit_lang_key' => $modx->getOption('autolexicon.session_edit_lang_key', null, 'autolexicon.edit_lang'),
            'lexicon_key_prefix' => $modx->getOption('autolexicon.lexicon_key_prefix', null, ''),
            'null_value' => $modx->getOption('autolexicon.null_value', null, 'NULL'),
            'resource_cache_key_prefix' => $modx->getOption('autolexicon.resource_cache_key_prefix', null, 'resource-'),
            'default_lang' => $this->modx->getOption('autolexicon.default_language', null, $this->modx->getOption('cultureKey', null, 'en')),
        ), $config);
        // todo: allow config fields for different topics
        $this->config['fields'] = array_merge($this->config['sync_tvs'], $this->config['sync_fields']);
        // clean
        /* load autolexicon lexicon */
        $this->modx->getService('lexicon', 'modLexicon');
        if (!$this->modx->lexicon) {
            throw new AutoLexiconException("AutoLexicon: Could not load modLexicon");
        }
        $this->modx->lexicon->load('autolexicon:default');
        foreach ($this->field_classes as $classname) {
            // todo: generate
            $topic = 'resource';
            $this->topics[] = array();
            $this->modx->lexicon->load('autolexicon:' . $topic);
        }
    }

    /**
     * @param string $class
     * @return AutoLexiconEventHandler
     */
    public function getEventHandler($class='AutoLexiconEventHandler'){
        if (!$this->event_handler instanceof AutoLexiconEventHandler) {
            $al =& $this;
            $this->event_handler = new $class($al, $this->config);
        }
        return $this->event_handler;
    }

    /**
     * Gets the lexicon key for this object field.
     *
     * @param int $object_id
     * @param string $field
     * @return string The lexicon key
     */
    public function getLexiconKey($object_id, $field) {
        // todo: get prefix from topic?
        return $this->config['lexicon_key_prefix'] . $object_id . '_' . $field;
    }

    /**
     * Gets a lexicon value.
     *
     * @param string $lexicon_key
     * @param string $topic
     * @param string $lang
     * @return null|string The result or null if not found.
     */
    public function getLexiconValue($lexicon_key, $topic, $lang) {
        if (!$this->modx->lexicon->exists($lexicon_key,$lang)) {
            $this->modx->lexicon->load($lang . ':autolexicon:'.$topic);
        }
        $output = $this->modx->lexicon($lexicon_key, array(), $lang);
        $output = ($output == $lexicon_key) ? null : $output;
        return $output;
    }

    /**
     * Creates a lexicon tag.
     *
     * @param string $key The lexicon key
     * @param string $topic The lexicon topic
     * @return string The lexicon tag
     */
    public function getLexiconTag($key, $topic) {
        return "[[!%{$key}? &topic=`{$topic}` &namespace=`autolexicon`]]";
    }

    public function debug(modResource $resource, $current_lang){
        $urls = array();
        $urls['this_url_1'] = $this->makeUrl($resource->get('id'),$current_lang);
        foreach ($this->config['langs'] as $lang) {
            $urls[$lang] = $this->makeUrl($resource->get('id'),$lang);
        }
        $options = array(
            'site_url' => $this->modx->getOption('site_url'),
            'base_url' => $this->modx->getOption('base_url'),
        );
        $urls['this_url_2'] = $this->makeUrl($resource->get('id'),$current_lang);
        foreach ($urls as $k => $v) {
            $urls[$k] = '<a href="'.$v.'">'.$v.'</a>';
        }
        $output = array_merge($urls, $options);
        die('<pre>'.print_r($output,1).'</pre>');
    }

    // todo: add support for other contexts
    /**
     * Generates a URL to another language for THIS context only.
     *
     * @param string $resource_id The resource ID
     * @param string $lang The language code
     * @param string $args The URL parameters.
     * @param mixed $scheme The MODX URL scheme
     * @param array $options Override system options used to generate URL.
     * @return string The full URL to the resource translation.
     */
    public function makeUrl($resource_id, $lang, $args='', $scheme='full', array $options=array()) {
        $options = array_merge(array(
            'site_url' => $this->translateSetting('site_url', $lang),
            'base_url' => $this->translateSetting('base_url', $lang),
        ),$options);
        $old_alias_map = $this->modx->context->aliasMap;
        $new_alias_map = array();
        $new_alias_map[$this->translateAlias($resource_id, $lang)] = $resource_id;
        $this->modx->context->aliasMap = $new_alias_map;
        $url = $this->modx->makeUrl($resource_id, $this->modx->context->get('key'), $args, $scheme, $options);
        $this->modx->context->aliasMap = $old_alias_map;
        return $url;
    }

    public function translateAlias($id, $lang) {
        $lexicon_key = $this->getLexiconKey($id, 'uri');
        $translated_uri = $this->getLexiconValue($lexicon_key, 'resource', $lang);
        return $translated_uri;
    }

    // todo: move this to site-specific code
    // todo: automatically translate other settings such as site_url
    public function translateSetting($name, $lang){
        $output = null;
        switch ($name) {
            case 'base_url':
                $output = '/' . $lang . '/';
                break;
            case 'site_url':
                $site_url = $this->modx->getOption('site_url');
                $site_url = substr($site_url, 0, -4) . '/' . $lang . '/';
                $output = $site_url;
                break;
        }
        return $output;
    }


    /**
     * Get an object representing a single field of a single language of a single object.
     *
     * @param xPDOObject $object
     * @param $field
     * @param $lang
     * @throws Exception If missing or empty parameters.
     * @return AutoLexiconField
     */
    public function getALFieldObject(xPDOObject $object, $field, $lang) {
        $class = null;
        foreach ($this->field_classes as $field_class => $al_class) {
            if ($object instanceof $field_class) {
                $class = $al_class;
                break;
            }
        }
        if (is_null($class)) {
            throw new Exception("Could not find AutoLexicon class for " . get_class($object));
        }
        $key = $class . '--' . $field . '--' . $lang;
        if (!isset($this->alfieldobjects[$key])) {
            $al =& $this;
            $this->alfieldobjects[$key] = new $class($al, $object, $field, $lang);
        }
        return $this->alfieldobjects[$key];
    }

    /**
     * Replaces the resource field values from the lexicon without saving the resource.
     *
     * @param modResource $object
     * @param string $lang The language code to translate to
     * @param bool $process_tags Whether or not to process MODX tags before saving.
     */
    public function translateObjectFields(modResource $object, $lang, $process_tags = false) {
        foreach ($this->config['fields'] as $field) {
            /** @var $alfo AutoLexiconResourceField */
            $alfo = $this->getALFieldObject($object, $field, $lang);
            $alfo->translate($process_tags);
        }
    }

    /*******************************************/
    /*               Utility Stuff             */
    /*******************************************/
    /**
     * Cleans a comma-separated string into an array of non-empty values
     *
     * @param string $string Input comma-separated string
     * @param string $separator Explode separator
     * @param string $trim_chars Characters to trim from each value
     * @return array Trimmed array of non-empty values
     */
    public function commasToArray($string, $separator = ',', $trim_chars = ' ') {
        $raw = explode($separator, $string);
        $output = array();
        foreach ($raw as $v) {
            $v = trim($v, $trim_chars);
            if (!empty($v)) {
                $output[] = $v;
            }
        }
        return $output;
    }

    /**
     * Gets a Chunk and caches it; also falls back to file-based templates
     * for easier debugging.
     *
     * @access public
     * @param string $name The name of the Chunk
     * @param array $properties The properties for the Chunk
     * @return string The processed content of the Chunk
     */
    public function getChunk($name, array $properties = array()) {
        $chunk = null;
        if (!isset($this->chunks[$name])) {
            $chunk = $this->modx->getObject('modChunk', array('name' => $name), true);
            if (empty($chunk)) {
                $chunk = $this->_getTplChunk($name, $this->config['chunkSuffix']);
                if ($chunk == false) return false;
            }
            $this->chunks[$name] = $chunk->getContent();
        } else {
            $o = $this->chunks[$name];
            $chunk = $this->modx->newObject('modChunk');
            $chunk->setContent($o);
        }
        $chunk->setCacheable(false);
        return $chunk->process($properties);
    }

    /**
     * Returns a modChunk object from a template file.
     *
     * @access private
     * @param string $name The name of the Chunk. Will parse to name.chunk.tpl by default.
     * @param string $suffix The suffix to add to the chunk filename.
     * @return modChunk/boolean Returns the modChunk object if found, otherwise
     * false.
     */
    private function _getTplChunk($name, $suffix = '.chunk.tpl') {
        $chunk = false;
        $f = $this->config['chunksPath'] . strtolower($name) . $suffix;
        if (file_exists($f)) {
            $o = file_get_contents($f);
            /** @var $chunk modChunk */
            $chunk = $this->modx->newObject('modChunk');
            $chunk->set('name', $name);
            $chunk->setContent($o);
        }
        return $chunk;
    }
}
