<?php namespace Klb\Core\Assets;

use Phalcon\Tag;

/**
 * Class Manager
 *
 * @package Klb\Core\Assets
 */
class Manager extends \Phalcon\Assets\Manager
{
    /**
     * @param null $collectionName
     * @return mixed
     */
    public function outputLess($collectionName = null)
    {
        $this->useImplicitOutput(false);

        return str_replace('stylesheet', 'stylesheet/less', $this->outputCss($collectionName));
    }

    /**
     * Prints the HTML for JS resources
     *
     * @param string $collectionName the name of the collection
     *
     * @return string the result of the collection
     **/
    public function outputJs($collectionName = null)
    {
        $collection = $this->collection($collectionName);
        $config = di('config')->get('performance');
        if (!empty($config->asset_cached_enabled) && $collection->getJoin()) {
            $ttl = (int) $config->asset_cached_ttl;
            $filename = $collection->getTargetPath();
            if (file_exists($filename) && ($ttl === 0 || filemtime($filename) > time())) {
                return Tag::javascriptInclude($collection->getTargetUri());
            }
            $res = parent::outputJs($collectionName);

//            touch($filename, time()+10);
            return $res;
        }

        return parent::outputJs($collectionName);
    }

    /**
     * Prints the HTML for CSS resources
     *
     * @param string $collectionName the name of the collection
     *
     * @return string the result of the collection
     **/
    public function outputCss($collectionName = null)
    {
        $collection = $this->collection($collectionName);
        $config = di('config')->get('performance');
        if (!empty($config->asset_cached_enabled) && $collection->getJoin()) {
            $ttl = (int) $config->asset_cached_ttl;
            $filename = $collection->getTargetPath();
            if (file_exists($filename) && ($ttl === 0 || filemtime($filename) > time())) {
                return Tag::stylesheetLink($collection->getTargetUri());
            }
            $res = parent::outputCss($collectionName);
//            touch($filename, time()+10, time());
            return $res;
        }

        return parent::outputCss($collectionName);
    }
}
