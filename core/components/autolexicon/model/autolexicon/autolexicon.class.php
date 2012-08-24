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
require_once dirname(__FILE__) . '/autolexiconhandler.class.php';
// todo-important: do not store lexicon entries for empty fields! (with setting)
// todo: make sure required fields are never empty in lexicon
// todo-important: make alias in non-default language not update from DB via javascript
// todo: add support for MODX lexicon manager ANY language
// todo: VersionX integration
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
    public $handler_config = array();
    public $_cache_class_handler = array();
    public $_cache_event_handler = array();
    public $_cache_lex_load = array();
    public $default_options = array();

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
        $this->handler_config = array(
            'modResource' => array(
                'class_handler' => 'AutoLexiconResourceHandler',
            ),
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
            'translate_settings' => $this->commasToArray($modx->getOption('autolexicon.translate_settings', null, 'base_url,site_url,site_name')),
            'session_edit_lang_key' => $modx->getOption('autolexicon.session_edit_lang_key', null, 'autolexicon.edit_lang'),
            'resource_cache_key_prefix' => $modx->getOption('autolexicon.resource_cache_key_prefix', null, 'resource-'),
            'langs' => $this->commasToArray($modx->getOption('autolexicon.languages', null, 'en, es')),
            'default_lang' => $this->modx->getOption('autolexicon.default_language', null, $this->modx->getOption('cultureKey', null, 'en')),
        ), $config);
        // clean
        /* load autolexicon lexicon */
        $this->modx->getService('lexicon', 'modLexicon');
        if (!$this->modx->lexicon) {
            throw new AutoLexiconException("AutoLexicon: Could not load modLexicon");
        }
        $this->modx->lexicon->load('autolexicon:default');
    }

    /**
     * @param string $class
     * @return AutoLexiconEventHandler
     */
    public function getEventHandler($class='AutoLexiconEventHandler'){
        if (!isset($this->_cache_event_handler[$class]) || !$this->_cache_event_handler[$class] instanceof AutoLexiconEventHandler) {
            $al =& $this;
            $this->_cache_event_handler[$class] = new $class($al, $this->config);
        }
        return $this->_cache_event_handler[$class];
    }

    // todo: finish
    /**
     * @param string $class
     * @throws AutoLexiconException
     * @return AutoLexiconHandler
     */
    public function getClassHandler($class = 'modResource') {
        if (!isset($this->_cache_class_handler[$class]) || !$this->_cache_class_handler[$class] instanceof AutoLexiconHandler) {
            $al =& $this;
            $handler = $this->handler_config[$class]['class_handler'];
            $handler = new $handler($al, $this->handler_config[$class]);
            if (!($handler instanceof AutoLexiconHandler)) {
                throw new AutoLexiconException("Could not load Handler for ".$class);
            }
            $this->_cache_class_handler[$class] = $handler;
        }
        return $this->_cache_class_handler[$class];
    }

    /**
     * Refreshes the cache for all the special resource cache keys used by AutoLexicon.
     */
    public function refreshCache() {
        $providers = array();
        foreach ($this->config['langs'] as $lang) {
            $providers[$this->config['resource_cache_key_prefix'] . $lang] = array();
        }
        ;
        $this->modx->cacheManager->refresh($providers);
        $this->modx->cacheManager->refresh();
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
            'site_url' => $this->translateSystemSetting('site_url', $lang),
            'base_url' => $this->translateSystemSetting('base_url', $lang),
        ),$options);
        $old_alias_map = $this->modx->context->aliasMap;
        $new_alias_map = array();
        $new_alias_map[$this->translateAlias($resource_id, $lang)] = $resource_id;
        $this->modx->context->aliasMap = $new_alias_map;
        $url = $this->modx->makeUrl($resource_id, $this->modx->context->get('key'), $args, $scheme, $options);
        $this->modx->context->aliasMap = $old_alias_map;
        return $url;
    }

    public function translateAlias($resource_id, $lang) {
        $handler = $this->getClassHandler('modResource');
        $lexicon_key = $handler->getLexiconKey($resource_id, 'uri');
        return $handler->getLexiconValue($lexicon_key, $lang);
    }

    public function _loadLexiconTopicOnce($lang, $topic, $reset=false){
        if ($reset || !isset($this->_cache_lex_load[$topic])) {
            $this->_cache_lex_load[$topic] = array();
        }
        if (!in_array($lang, $this->_cache_lex_load[$topic])) {
            $this->modx->lexicon->load($lang.':autolexicon:'.$topic);
            $this->_cache_lex_load[$topic][] = $lang;
        }
    }

    public function translateSystemSetting($name, $lang) {
        $prefix = 'al.setting.';
        $topic = 'setting';
        $this->_loadLexiconTopicOnce($lang, $topic, true);
        $output = null;
        // todo: move prefixes to config
        $prefixes = array(
            $prefix.'user.'.$this->modx->user->get('id').'.',
            $prefix.'context.'.$this->modx->context->get('key').'.',
            $prefix,
        );
        foreach($prefixes as $prefix) {
            $lexicon_key = $prefix.$name;
            if ($this->modx->lexicon->exists($lexicon_key, $lang)) {
                $output = $this->modx->lexicon($lexicon_key, array(), $lang);
                break;
            }
        }
        if (is_null($output)) {
            if(isset($this->default_options[$name])) {
                $output = $this->default_options[$name];
            } else {
                $output = $this->modx->getOption($name);
            }
        }
        return $output;
    }

    public function overrideOption($name, $value) {
        $this->default_options[$name] = $this->modx->getOption($name);
        $this->modx->setOption($name, $value);
    }

    /**
     * Finds or creates a lexicon entry object for the key, topic, and language.
     *
     * @param string $key The lexicon entry name
     * @param string $topic The lexicon entry topic
     * @param string $lang The lexicon entry language
     * @param string $namespace The lexicon entry namespace
     * @return modLexiconEntry|null|object
     */
    public function getLexiconEntry($key, $topic, $lang, $namespace='autolexicon') {
        $base_array = array(
            'name' => $key,
            'topic' => $topic,
            'namespace' => $namespace,
            'language' => $lang,
        );
        $entry = $this->modx->getObject('modLexiconEntry',$base_array);
        if(!($entry instanceof modLexiconEntry)) {
            /** @var $entry modLexiconEntry */
            $entry = $this->modx->newObject('modLexiconEntry');
            $entry->fromArray($base_array);
        }
        if(!($entry instanceof modLexiconEntry)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR,"AutoLexicon could not create a lexicon entry for key {$key}, lang {$lang}, and topic {$topic}");
        }
        return $entry;
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

