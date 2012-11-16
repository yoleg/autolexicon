<?php
/**
 * @package AutoLexicon
 * @var MODx $modx
 * @var array $sources
 * @var array $scriptProperties
 */
$settings = array();
$settings['install'] = array(
    'enabled' => false,
    'languages' => 'en,es',
    'default_language' => array(
        'value' => 'en',
        'xtype' => 'modx-combo-language'
    ),
);
$settings['upgrade'] = array(
    'translate_settings' => 'base_url,site_url,site_name',
    'session_edit_lang_key' => 'edit_lang',
    'resource_cache_key_prefix' => 'default/autolexicon/',
    'cleanup_empty' => true,
    'null_value' => 'NULL',
    'resource.translate_fields' => 'pagetitle,uri,alias,content,longtitle,description,introtext,menutitle',
    'resource.translate_tvs' => '',
    'resource.replace_fields' => true,
    'resource.replace_tvs' => true,
    'resource.never_replace_fields_list' => 'pagetitle,uri,alias',
    'resource.default_field' => 'pagetitle',
    'resource.set_as_default' => 'menutitle',
    // todo: replace with normal or static required fields
    'resource.required_fields' => 'pagetitle,alias,uri',
);
foreach($settings as $type => $s1) {
    foreach ($s1 as $name => $config) {
        $area = '';
        unset($settings[$type][$name]);
        $config = is_array($config) ? $config : array(
            'value' => $config,
        );
        if(substr($name,0,strlen('resource.')) == 'resource.') {
            $name = substr($name,strlen('resource.'));
            $area = 'resource';
        }
        $settings[$type][PKG_NAME_LOWER.'.'.$name] = array_merge(array(
            'area' => $area,
        ),$config);
    }
}
return $settings;
