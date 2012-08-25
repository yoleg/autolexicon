<?php
/**
 * @package AutoLexicon
 * @var MODx $modx
 * @var array $sources
 * @var array $scriptProperties
 */
$settings = array(
    'autolexicon.enabled' => false,
    'autolexicon.languages' => 'en,es',
    'autolexicon.default_language' => 'en',
    'autolexicon.translate_settings' => 'base_url,site_url,site_name',
    'autolexicon.session_edit_lang_key' => 'autolexicon.edit_lang',
    'autolexicon.resource_cache_key_prefix' => 'resource-',
    'autolexicon.cleanup_empty' => true,
    'autolexicon.sync_fields' => 'pagetitle,uri,alias,content,longtitle,description,introtext,menutitle',
    'autolexicon.sync_tvs' => 'pagetitle,uri,alias,content,longtitle,description,introtext,menutitle',
    'autolexicon.replace_fields' => 'content,longtitle,description,introtext,menutitle',
    'autolexicon.required_fields' => 'pagetitle,alias,uri',
    'autolexicon.default_field' => 'pagetitle',
    'autolexicon.set_as_default' => 'menutitle',
    'autolexicon.null_value' => 'NULL',
);
return $settings;
