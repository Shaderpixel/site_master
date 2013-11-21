<?php
namespace SiteMaster\Plugins\Example;

use SiteMaster\Plugin\PluginInterface;
use SiteMaster\Events\RoutesCompile;

class Plugin extends PluginInterface
{
    /**
     * @return bool|mixed
     */
    public function onInstall()
    {
        return true;
    }

    /**
     * @return bool|mixed
     */
    public function onUninstall()
    {
        return true;
    }

    /**
     * @return mixed|string
     */
    public function getName()
    {
        return 'Example Plugin';
    }

    /**
     * @return mixed|string
     */
    public function getDescription()
    {
        return 'Just a very basic example plugin';
    }

    /**
     * Called when the plugin is updated (a newer version exists).
     *
     * @param $previousVersion int The previous installed version
     * @return mixed
     */
    public function onUpdate($previousVersion)
    {
        return true;
    }

    /**
     * Returns the version of this plugin
     * Follow a mmddyyyyxx syntax.
     *
     * for example 1118201301
     * would be 11/18/2013 - increment 1
     *
     * @return mixed
     */
    public function getVersion()
    {
        return true;
    }

    /**
     * Get an array of event listeners
     *
     * @return array
     */
    function getEventListeners()
    {
        $listeners = array();

        $listeners[] = array(
            'event'    => RoutesCompile::EVENT_NAME,
            'listener' => function (RoutesCompile $event) {
                    $event->addRoute('/^\/example\/$/', __NAMESPACE__ . '\Example');
                }
        );

        return $listeners;
    }
}