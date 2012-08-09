<?php
/**
 * AutoLexicon
 *
 * Copyright 2011 by Oleg Pryadko (websitezen.com)
 *
 * Based on packages by Shaun McCormick and Bob Ray
 *
 * This file is part of AutoLexicon, a quick-start site package for MODx Revolution
 * AutoLexicon is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * AutoLexicon is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along wit
 * AutoLexicon; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 
 * @package AutoLexicon
 */
/**
 * AutoLexicon build script
 *
 * @package AutoLexicon
 * @subpackage build
 */
$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tstart = $mtime;
set_time_limit(0);

/* define package */
/* Set package info be sure to set all of these */
define('PKG_NAME','AutoLexicon');
define('PKG_NAME_LOWER','autolexicon');
define('PKG_VERSION','1.0.8');
define('PKG_RELEASE','beta');
define('PKG_CATEGORY','AutoLexicon');

/* Set package options - you can turn these on one-by-one
 * as you create the transport package
 * */
$hasAssets = true; /* Transfer the files in the assets dir. */
$hasCore = true;   /* Transfer the files in the core dir. */
$hasSnippets = true;
$hasChunks = true;
$hasResources = true;
$hasSettings = true; /* Add new MODx System Settings */
$hasMenu = true; /* Add items to the MODx Top Menu */
$hasSetupOptions = true; /* Update system settings from PHP/ HTML form. */
$hasResolvers = true; /* Add additional custom resolvers */
$resolver_files = array(
    'resources.resolver.php',
    'system_settings.resolver.php',
    'finish.resolver.php',
);
/* not yet working */
$hasAccessPolicies = false;
$hasPolicyTemplates = false;
/* does not have */
$hasSubPackages = false; /* add in other component packages (transport.zip files) - copy only, no auto-install */
$hasTemplates = false;
$hasPropertySets = false;
$hasValidator = false; /* Run a validator before installing anything */
$hasTemplateVariables = false;
$hasPlugins = false;
/* $hasPluginEvents = false; */

/* define sources */
$root = dirname(dirname(__FILE__)).'/';
$sources= array (
    'root' => $root,
    'build' => $root .'_build/',
    'resolvers' => $root . '_build/resolvers/',
    'data' => $root . '_build/data/',
    'events' => $root . '_build/data/events/',
    'permissions' => $root . '_build/data/permissions/',
    'properties' => $root . '_build/data/properties/',
    'validators'=> $root . '_build/validators/',
    'install_options' => $root . '_build/install.options/',
    'packages'=> $root . 'core/packages',
    'source_core' => $root.'core/components/'.PKG_NAME_LOWER,
    'source_assets' => $root.'assets/components/'.PKG_NAME_LOWER,
    'plugins' => $root.'core/components/'.PKG_NAME_LOWER.'/elements/plugins/',
    'snippets' => $root.'core/components/'.PKG_NAME_LOWER.'/elements/snippets/',
    'lexicon' => $root . 'core/components/'.PKG_NAME_LOWER.'/lexicon/',
    'docs' => $root.'core/components/'.PKG_NAME_LOWER.'/docs/',
    'model' => $root.'core/components/'.PKG_NAME_LOWER.'/model/',
);
unset($root);

/* set package attributes options */
$packageAttributeArray = array(
    'license' => file_get_contents($sources['docs'] . 'license.txt'),
    'readme' => file_get_contents($sources['docs'] . 'readme.txt'),
    'changelog' => file_get_contents($sources['docs'] . 'changelog.txt'),
);

/* override with your own defines here */
require_once ($sources['build'] . 'includes/functions.php');
require_once ($sources['build'] . 'build.config.php');
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';


/* start of work - get MODx */
$modx= new modX();
$modx->initialize('mgr');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget('ECHO'); echo '<pre>'; flush();

/* load package builder */
$modx->loadClass('transport.modPackageBuilder','',false, true);
$builder = new modPackageBuilder($modx);
$builder->createPackage(PKG_NAME_LOWER,PKG_VERSION,PKG_RELEASE);
$builder->registerNamespace(PKG_NAME_LOWER,false,true,'{core_path}components/'.PKG_NAME_LOWER.'/');
$modx->getService('lexicon','modLexicon');
$modx->lexicon->load('autolexicon:properties');

/* create new category w/ package name - required */
/** @var $category modCategory */
$category=$modx->newObject('modCategory');
$category->set('id',1);
$category->set('category',PKG_CATEGORY);
$modx->log(modX::LOG_LEVEL_INFO,'Packaged in category.'); flush();

if ($hasSetupOptions) {
	$packageAttributeArray['setup-options'] = array();
	$packageAttributeArray['setup-options']['source'] = $sources['install_options'].'user.input.php';
    $modx->log(modX::LOG_LEVEL_INFO,'Allowing user options.');  flush();
}
/* add Resources */
if ($hasResources) {
    $resources = include $sources['data'].'transport.resources.php';
    if (!is_array($resources)) {
        $modx->log(modX::LOG_LEVEL_ERROR,'Could not package in resources.');
    } else {
        $attributes= array(
			xPDOTransport::PRESERVE_KEYS => false,
			xPDOTransport::UPDATE_OBJECT => true,
			xPDOTransport::UNIQUE_KEY => 'pagetitle',
			xPDOTransport::RELATED_OBJECTS => true,
			xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
				'ContentType' => array(
					xPDOTransport::PRESERVE_KEYS => false,
					xPDOTransport::UPDATE_OBJECT => true,
					xPDOTransport::UNIQUE_KEY => 'name',
				),
			),
		);
		foreach ($resources as $resource) {
			$vehicle = $builder->createVehicle($resource,$attributes);
			$builder->putVehicle($vehicle);
		}
		$modx->log(modX::LOG_LEVEL_INFO,'Packaged in '.count($resources).' resources.');
    }
    unset($resources,$attributes);
}

/* Transport Menus */
if ($hasMenu) {
    /* load menu */
    $modx->log(modX::LOG_LEVEL_INFO,'Packaging in menu...');
    $menus = include $sources['data'].'transport.menu.php';
    if (!is_array($menus)) {
        $modx->log(modX::LOG_LEVEL_ERROR,'Could not package in menu.');
    } else {
        $attributes = array(
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'text',
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array(
                'Action' => array(
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => array('namespace', 'controller'),
                ),
            ),
        );
        foreach($menus as $menu) {
            $vehicle= $builder->createVehicle($menu, $attributes);
            $builder->putVehicle($vehicle);
        }

        $modx->log(modX::LOG_LEVEL_INFO,'Packaged in '.count($menu).' menu items.');
        unset($vehicle,$menus);
    }
}

/* load system settings */
if ($hasSettings) {
	$settings = include_once $sources['data'].'transport.settings.php';
    if (!is_array($settings)) {
        $modx->log(modX::LOG_LEVEL_ERROR,'Could not package in settings.');
    } else {
		$attributes= array(
			xPDOTransport::UNIQUE_KEY => 'key',
			xPDOTransport::PRESERVE_KEYS => true,
			xPDOTransport::UPDATE_OBJECT => false,
		);
		if (!is_array($settings)) { $modx->log(modX::LOG_LEVEL_FATAL,'Adding settings failed.'); }
		foreach ($settings as $setting) {
			$vehicle = $builder->createVehicle($setting,$attributes);
			$builder->putVehicle($vehicle);
		}
		$modx->log(modX::LOG_LEVEL_INFO,'Packaged in '.count($settings).' system settings.'); flush();
		unset($settings,$setting,$attributes);
    }
}

/* package in default access policy template */
if ($hasPolicyTemplates) {
	$templates = include $sources['data'].'transport.policytemplates.php';
	$attributes = array (
		xPDOTransport::PRESERVE_KEYS => false,
		xPDOTransport::UNIQUE_KEY => array('name'),
		xPDOTransport::UPDATE_OBJECT => true,
		xPDOTransport::RELATED_OBJECTS => true,
		xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
			'Permissions' => array (
				xPDOTransport::PRESERVE_KEYS => false,
				xPDOTransport::UPDATE_OBJECT => true,
				xPDOTransport::UNIQUE_KEY => array ('template','name'),
			),
		)
	);
	if (is_array($templates)) {
		foreach ($templates as $template) {
			$vehicle = $builder->createVehicle($template,$attributes);
			$builder->putVehicle($vehicle);
		}
		$modx->log(modX::LOG_LEVEL_INFO,'Packaged in '.count($templates).' Access Policy Templates.'); flush();
	} else {
		$modx->log(modX::LOG_LEVEL_ERROR,'Could not package in Access Policy Templates.');
	}
	unset ($templates,$template,$idx,$ct,$attributes);
}

/* package in default access policy */
if ($hasAccessPolicies) {
	$attributes = array (
		xPDOTransport::PRESERVE_KEYS => false,
		xPDOTransport::UNIQUE_KEY => array('name'),
		xPDOTransport::UPDATE_OBJECT => true,
	);
	$policies = include $sources['data'].'transport.policies.php';
	if (!is_array($policies)) { 
		$modx->log(modX::LOG_LEVEL_FATAL,'Adding policies failed.'); 
	} else {
		foreach ($policies as $policy) {
			$vehicle = $builder->createVehicle($policy,$attributes);
			$builder->putVehicle($vehicle);
		}
		$modx->log(modX::LOG_LEVEL_INFO,'Packaged in '.count($policies).' Access Policies.'); flush();
		unset($policies,$policy,$attributes);
	}
}

/* add plugins - DONE */
if ($hasPlugins) {
	$plugins = include $sources['data'].'transport.plugins.php';
	if (!is_array($plugins)) { $modx->log(modX::LOG_LEVEL_FATAL,'Adding plugins failed.'); }
	$attributes= array(
		xPDOTransport::UNIQUE_KEY => 'name',
		xPDOTransport::PRESERVE_KEYS => false,
		xPDOTransport::UPDATE_OBJECT => true,
		xPDOTransport::RELATED_OBJECTS => true,
		xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
			'PluginEvents' => array(
				xPDOTransport::PRESERVE_KEYS => true,
				xPDOTransport::UPDATE_OBJECT => false,
				xPDOTransport::UNIQUE_KEY => array('pluginid','event'),
			),
		),
	);
	foreach ($plugins as $plugin) {
		$vehicle = $builder->createVehicle($plugin, $attributes);
		$builder->putVehicle($vehicle);
	}
	$modx->log(modX::LOG_LEVEL_INFO,'Packaged in '.count($plugins).' plugins.'); flush();
	unset($plugins,$plugin,$attributes);
}

/* add snippets */
if ($hasSnippets) {
    $modx->log(modX::LOG_LEVEL_INFO,'Adding in snippets.');
	$snippets = include $sources['data'].'transport.snippets.php';
	if (is_array($snippets)) {
		$category->addMany($snippets,'Snippets');
	} else { $modx->log(modX::LOG_LEVEL_FATAL,'Adding snippets failed.'); }
	$modx->log(modX::LOG_LEVEL_INFO,'Packaged in '.count($snippets).' snippets.'); flush();
	unset($snippets);
}

/* add chunks */
if ($hasChunks) { /* add chunks  */
    /* note: Chunks' default properties are set in transport.chunks.php */
    $chunks = include $sources['data'].'transport.chunks.php';
    if (is_array($chunks)) {
        $category->addMany($chunks, 'Chunks');
    } else { $modx->log(modX::LOG_LEVEL_FATAL,'Adding chunks failed.'); }
    $modx->log(modX::LOG_LEVEL_INFO,'Packaged in '.count($chunks).' snippets.'); flush();
}

/* add templates  */
if ($hasTemplates) { 
    /* note: Templates' default properties are set in transport.templates.php */
    $templates = include $sources['data'].'transport.templates.php';
    if (is_array($templates)) {
        if (! $category->addMany($templates,'Templates')) {
            $modx->log(modX::LOG_LEVEL_INFO,'addMany failed with templates.');  flush();
        };
    } else { $modx->log(modX::LOG_LEVEL_FATAL,'Adding templates failed.'); }
    $modx->log(modX::LOG_LEVEL_INFO,'Packaged in '.count($templates).' templates.'); flush();
}

/* add templatevariables  */
if ($hasTemplateVariables) { 
    /* note: Template Variables' default properties are set in transport.tvs.php */
    $templatevariables = include $sources['data'].'transport.tvs.php';
    if (is_array($templatevariables)) {
        $category->addMany($templatevariables, 'TemplateVars');
    } else { $modx->log(modX::LOG_LEVEL_FATAL,'Adding templatevariables failed.'); }
    $modx->log(modX::LOG_LEVEL_INFO,'Packaged in '.count($templatevariables).' template variables.'); flush();
}

/* add property sets */
if ($hasPropertySets) { 
    $modx->log(modX::LOG_LEVEL_INFO,'Adding in property sets.');  flush();
    $propertysets = include $sources['data'].'transport.propertysets.php';
    /* note: property set' properties are set in transport.propertysets.php */
    if (is_array($propertysets)) {
        $category->addMany($propertysets, 'PropertySets');
    } else { $modx->log(modX::LOG_LEVEL_FATAL,'Adding property sets failed.'); }
    $modx->log(modX::LOG_LEVEL_INFO,'Packaged in '.count($propertysets).' property sets.'); flush();
}

/* Create Category attributes array dynamically
 * based on which elements are present
 */

$attr = array(xPDOTransport::UNIQUE_KEY => 'category',
    xPDOTransport::PRESERVE_KEYS => false,
    xPDOTransport::UPDATE_OBJECT => true,
    xPDOTransport::RELATED_OBJECTS => true,
);
if ($hasValidator) {
    $attr[xPDOTransport::ABORT_INSTALL_ON_VEHICLE_FAIL] = true;
}
if ($hasSnippets) {
    $attr[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Snippets'] = array(
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',
        );
}
if ($hasPropertySets) {
    $attr[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['PropertySets'] = array(
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',
        );
}
if ($hasChunks) {
    $attr[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Chunks'] = array(
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => false,
            xPDOTransport::UNIQUE_KEY => 'name',
        );
}
if ($hasPlugins) {
    $attr[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Plugins'] = array(
        xPDOTransport::PRESERVE_KEYS => false,
        xPDOTransport::UPDATE_OBJECT => true,
        xPDOTransport::UNIQUE_KEY => 'name',
    );
}
if ($hasTemplates) {
    $attr[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Templates'] = array(
        xPDOTransport::PRESERVE_KEYS => false,
        xPDOTransport::UPDATE_OBJECT => true,
        xPDOTransport::UNIQUE_KEY => 'templatename',
    );
}
if ($hasTemplateVariables) {
    $attr[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['TemplateVars'] = array(
        xPDOTransport::PRESERVE_KEYS => false,
        xPDOTransport::UPDATE_OBJECT => true,
        xPDOTransport::UNIQUE_KEY => 'name',
    );
}
/* create a vehicle for the category and all the things
 * we've added to it.
 */
$vehicle = $builder->createVehicle($category,$attr);

/* copy core folder */
if ($hasCore) {
    $modx->log(modX::LOG_LEVEL_INFO, 'Adding in core.'); flush();
	$vehicle->resolve('file',array(
		'source' => $sources['source_core'],
		'target' => "return MODX_CORE_PATH . 'components/';",
	));
}

/* copy assets folder */
if ($hasAssets) {
    $modx->log(modX::LOG_LEVEL_INFO, 'Adding in assets.');  flush();
	$vehicle->resolve('file',array(
		'source' => $sources['source_assets'],
		'target' => "return MODX_ASSETS_PATH . 'components/';",
	));
}

/* Add subpackages */
/* The transport.zip files will be copied to core/packages
 * but will have to be installed manually with "Add New Package and
 *  "Search Locally for Packages" in Package Manager
 */

if ($hasSubPackages) {
    $modx->log(modX::LOG_LEVEL_INFO, 'Adding in subpackages.');  flush();
    $vehicle->resolve('file', array(
        'source' => $sources['packages'],
        'target' => "return MODX_CORE_PATH;",
    ));
}

if ($hasValidator) {
    $modx->log(modX::LOG_LEVEL_INFO,'Adding in Script Validator.'); flush();
    $vehicle->validate('php',array(
        'source' => $sources['validators'] . 'preinstall.script.php',
    ));
}

/* resolvers */
if ($hasResolvers) {
	// add as many other resolvers as necessary
    foreach ($resolver_files as $filename) {
        $modx->log(modX::LOG_LEVEL_INFO, 'Adding in resolver: '.$filename); flush();
        $vehicle->resolve('php', array(
            'source' => $sources['resolvers'] . $filename,
        ));
    }
}
$modx->log(modX::LOG_LEVEL_INFO,'Packaged in '.count($resolver_files).' resolvers.'); flush();

/* Put the category vehicle (with all the stuff we added to the
 * category) into the package 
 */
$builder->putVehicle($vehicle);

/* now pack in the license file, readme and setup options */
$builder->setPackageAttributes($packageAttributeArray);
$modx->log(modX::LOG_LEVEL_INFO,'Packaged in package attributes.'); flush();

$modx->log(modX::LOG_LEVEL_INFO,'Packing...'); flush();
$builder->pack();

$mtime= microtime();
$mtime= explode(" ", $mtime);
$mtime= $mtime[1] + $mtime[0];
$tend= $mtime;
$totalTime= ($tend - $tstart);
$totalTime= sprintf("%2.4f s", $totalTime);

$modx->log(modX::LOG_LEVEL_INFO,"\n<br />Package Built.<br />\nExecution time: {$totalTime}\n");

exit ();
