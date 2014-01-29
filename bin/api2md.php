<?php

namespace KzykHys\bin;

use PHPGit\Command;
use PHPGit\Git;

require __DIR__ . '/../vendor/autoload.php';

function println($args = '')
{
    $args = func_get_args();
    echo implode('', $args) . "\n";
}

function stringify($mixed)
{
    if (is_array($mixed)) {
        return '[]';
    }
    if ($mixed === '') {
        return "''";
    }
    if (is_null($mixed)) {
        return 'null';
    }

    return print_r($mixed, true);
}

function slugify($string)
{
    $string = str_replace(' ', '-', $string);

    return strtolower(preg_replace('/[^0-9a-zA-Z-]/', '', $string));
}

$git = new Git();
$document = array();

$refGitClass   = new \ReflectionClass($git);
$refProperties = $refGitClass->getProperties(\ReflectionProperty::IS_PUBLIC);

foreach ($refProperties as $refProperty) {
    // Class DocBlock
    if (!$object = $refProperty->getValue($git)) {
        continue;
    }
    $refClass = new \ReflectionClass($object);
    $docBlock = $refClass->getDocComment();
    $docBlock = preg_replace('/^(\/\*\*| *\*\/| *\* *)/m', '', $docBlock);
    $docBlock = trim($docBlock);
    if (!preg_match('/`(.*)`/', $docBlock, $matches)) {
        continue;
    }
    $gitCommand = $matches[1];

    $document[$refProperty->getName()] = array(
        'git' => $gitCommand,
        'methods' => array()
    );

    // Method Docblock
    $refMethods = $refClass->getMethods(\ReflectionMethod::IS_PUBLIC);

    foreach ($refMethods as $refMethod) {
        $name = $refMethod->getName();
        if ((substr($name, 0, 2) === '__' && $name != '__invoke') || $name === 'setDefaultOptions' || $name === 'resolve') {
            continue;
        }

        $refParameters = $refMethod->getParameters();

        if ($name == '__invoke') {
            $methodName = $refProperty->getName();
        } else {
            $methodName = $refProperty->getName() . '->' . $name;
        }

        $docs = $refMethod->getDocComment();
        $docs = preg_replace('/^(\/\*\*| *\*\/| *\* ?)/m', '', $docs);
        //list($description, $api) = explode('@', $docs, 2);
        list($description, $api) = preg_split('/^@/m', $docs, 2);
        $api = '@' . $api;

        $defaults = array();
        foreach ($refParameters as $refParameter) {
            if ($refParameter->isDefaultValueAvailable()) {
                $defaultValue = $refParameter->getDefaultValue();
                $defaults[$refParameter->getName()] = stringify($defaultValue);
            }
        }

        preg_match_all('/^@param *([^ ]+) *\$([^ ]*)/m', $api, $paramMatches, PREG_SET_ORDER);
        $params = array();
        foreach ($paramMatches as $paramMatch) {
            $param = '_' . $paramMatch[1] . '_ $' . $paramMatch[2];
            if (isset($defaults[$paramMatch[2]])) {
                $param .= ' = ' . $defaults[$paramMatch[2]];
            }
            $params[] = $param;
        }

        $document[$refProperty->getName()]['methods'][] = array(
            'title' => '$git->' . $methodName . '(' . implode(', ', $params) . ')',
            'desc'  => trim($description)
        );
    }

    // Sub-commands
    $refSubProperties = $refClass->getProperties(\ReflectionProperty::IS_PUBLIC);
    foreach ($refSubProperties as $refSubProperty) {
        if (!$subObject = $refSubProperty->getValue($object)) {
            continue;
        }
        $refSubClass = new \ReflectionClass($subObject);
        $refSubMethods = $refSubClass->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($refSubMethods as $refSubMethod) {
            $subName = $refSubMethod->getName();
            if ((substr($subName, 0, 2) === '__' && $subName != '__invoke') || $subName === 'setDefaultOptions' || $subName === 'resolve') {
                continue;
            }
            $refSubParameters = $refSubMethod->getParameters();
            if ($subName == '__invoke') {
                $subMethodName = $refProperty->getName() . '->' . $refSubProperty->getName();
            } else {
                $subMethodName = $refProperty->getName() .'->' . $refSubProperty->getName() . '->' . $subName;
            }

            $subDocs = $refSubMethod->getDocComment();
            $subDocs = preg_replace('/^(\/\*\*| *\*\/| *\* ?)/m', '', $subDocs);
            //list($subDescription, $subApi) = explode('@', $subDocs, 2);
            list($subDescription, $subApi) = preg_split('/^@/m', $subDocs, 2);
            $subApi = '@' . $subApi;

            $subDefaults = array();
            foreach ($refSubParameters as $refSubParameter) {
                if ($refSubParameter->isDefaultValueAvailable()) {
                    $subDefaultValue = $refSubParameter->getDefaultValue();
                    $subDefaults[$refSubParameter->getName()] = stringify($subDefaultValue);
                }
            }

            preg_match_all('/^@param *([^ ]+) *\$([^ ]*)/m', $subApi, $subParamMatches, PREG_SET_ORDER);
            $subParams = array();
            foreach ($subParamMatches as $subParamMatch) {
                $param = '_' . $subParamMatch[1] . '_ $' . $subParamMatch[2];
                if (isset($subDefaults[$subParamMatch[2]])) {
                    $param .= ' = ' . $subDefaults[$subParamMatch[2]];
                }
                $subParams[] = $param;
            }

            $document[$refProperty->getName()]['methods'][] = array(
                'title' => '$git->' . $subMethodName . '(' . implode(', ', $subParams) . ')',
                'desc'  => trim($subDescription)
            );
        }
    }
}

$gitDockBlock = $refGitClass->getDocComment();
$gitDockBlock = preg_replace('/^(\/\*\*| *\*\/| *\* ?)/m', '', $gitDockBlock);
list($readme) = explode('@', $gitDockBlock, 2);

println(trim($readme));
println();
println('API');
println('---');
println();

foreach ($document as $definition) {
    println('* [', $definition['git'], '](#', slugify($definition['git']), ')');
    foreach ($definition['methods'] as $method) {
        $anchor = slugify($method['title']);
        $title  = $method['title'];
        $title  = preg_replace('/->([^ -]*)\(/', '->[\1](#' . $anchor . ')(', $title);
        println('    * ', $title);
    }
}

println();

foreach ($document as $definition) {
    println('* * * * *');
    println();

    println('### ', $definition['git']);
    println();

    foreach ($definition['methods'] as $method) {
        println('#### ', $method['title']);
        println();
        println($method['desc']);
        println();
    }
}

println('License
-------

The MIT License

Author
------

Kazuyuki Hayashi (@kzykhys)');