<?php

/**
 */
class PluginManager extends Survey_Common_Action
{
    public function init()
    {
    }

    /**
     * Overview for plugins
     * Copied from PluginsController 2015-10-02
     */
    public function index()
    {
        $oPluginManager = App()->getPluginManager();

        // Scan the plugins folder.
        $aDiscoveredPlugins = $oPluginManager->scanPlugins();
        $aInstalledPlugins  = $oPluginManager->getInstalledPlugins();
        $aInstalledNames    = array_map(function ($installedPlugin) {
                return $installedPlugin->name;
            }, $aInstalledPlugins);

        // Install newly discovered plugins.
        foreach ($aDiscoveredPlugins as $discoveredPlugin)
        {
            if (!in_array($discoveredPlugin['pluginClass'], $aInstalledNames))
            {
                $oPlugin         = new Plugin();
                $oPlugin->name   = $discoveredPlugin['pluginClass'];
                $oPlugin->active = 0;
                $oPlugin->save();
            }
        }

        $aoPlugins = Plugin::model()->findAll();
        $data      = array();
        foreach ($aoPlugins as $oPlugin)
        {
            /* @var $plugin Plugin */
            if (array_key_exists($oPlugin->name, $aDiscoveredPlugins))
            {
                $aPluginSettings = App()->getPluginManager()->loadPlugin($oPlugin->name, $oPlugin->id)->getPluginSettings(false);
                $data[]          = array(
                    'id'          => $oPlugin->id,
                    'name'        => $aDiscoveredPlugins[$oPlugin->name]['pluginName'],
                    'description' => $aDiscoveredPlugins[$oPlugin->name]['description'],
                    'active'      => $oPlugin->active,
                    'settings'    => $aPluginSettings,
                    'new'         => !in_array($oPlugin->name, $aInstalledNames)
                );
            } else
            {
                // This plugin is missing, maybe the files were deleted but the record was not removed from the database
                // Now delete this record. Depending on the plugin the settings will be preserved
                App()->user->setFlash('pluginDelete' . $oPlugin->id, sprintf(gT("Plugin '%s' was missing and is removed from the database."), $oPlugin->name));
                $oPlugin->delete();
            }
        }

        $this->_renderWrappedTemplate('pluginmanager', 'index', array('data' => $data));
    }

    /**
     * Activate a plugin
     *
     * @todo Defensive programming
     * @param int $id Plugin id
     * @return void
     */
    public function activate($id)
    {
        $oPlugin = Plugin::model()->findByPk($id);
        if (!is_null($oPlugin))
        {
            $iStatus = $oPlugin->active;
            if ($iStatus == 0)
            {
                // Load the plugin:
                App()->getPluginManager()->loadPlugin($oPlugin->name, $id);
                $result = App()->getPluginManager()->dispatchEvent(new PluginEvent('beforeActivate', $this), $oPlugin->name);
                if ($result->get('success', true))
                {
                    $iStatus = 1;
                } else
                {
                    Yii::app()->user->setFlash('error', gT('Failed to activate the plugin.'));
                    $this->getController()->redirect(array('admin/pluginmanager/sa/index/'));
                }
            }
            $oPlugin->active = $iStatus;
            $oPlugin->save();
            Yii::app()->user->setFlash('success', gT('Plugin was activated'));
        }
        $this->getController()->redirect(array('admin/pluginmanager/sa/index/'));
    }

    /**
     * Deactivate plugin with $id
     *
     * @param int $id
     * @return void
     */
    public function deactivate($id)
    {
        $oPlugin = Plugin::model()->findByPk($id);
        if (!is_null($oPlugin))
        {
            $iStatus = $oPlugin->active;
            if ($iStatus == 1)
            {
                $result = App()->getPluginManager()->dispatchEvent(new PluginEvent('beforeDeactivate', $this), $oPlugin->name);
                if ($result->get('success', true))
                {
                    $iStatus = 0;
                } else
                {
                    Yii::app()->user->setFlash('error', gT('Failed to deactivate the plugin.'));
                    $this->getController()->redirect(array('admin/pluginmanager/sa/index/'));
                }
            }
            $oPlugin->active = $iStatus;
            $oPlugin->save();
            Yii::app()->user->setFlash('success', gT('Plugin was deactivated'));
        }
        $this->getController()->redirect(array('admin/pluginmanager/sa/index/'));
    }

    /**
     * Configure for plugin
     */
    public function configure($id)
    {
        App()->getClientScript()->registerPackage('select2');

        $arPlugin      = Plugin::model()->findByPk($id)->attributes;
        $oPluginObject = App()->getPluginManager()->loadPlugin($arPlugin['name'], $arPlugin['id']);

        if ($arPlugin === null)
        {
            Yii::app()->user->setFlash('error', gT('Plugin not found'));
            $this->getController()->redirect(array('admin/pluginmanager/sa/index'));
        }

        // If post handle data, yt0 seems to be the submit button
        if (App()->request->isPostRequest)
        {

            $aSettings = $oPluginObject->getPluginSettings(false);
            $aSave     = array();
            foreach ($aSettings as $name => $setting)
            {
                $aSave[$name] = App()->request->getPost($name, null);
            }
            $oPluginObject->saveSettings($aSave);
            Yii::app()->user->setFlash('success', gT('Settings saved'));
            if(App()->request->getPost('redirect'))
            {
                $this->getController()->redirect(App()->request->getPost('redirect'), true);
            }
        }

        // Prepare settings to be send to the view.
        $aSettings = $oPluginObject->getPluginSettings();
        if (empty($aSettings))
        {
            // And show a message
            Yii::app()->user->setFlash('notice', gt('This plugin has no settings'));
            $this->getController()->redirect('admin/pluginmanager/sa/index', true);
        }

        // Send to view plugin porperties: name and description
        $aPluginProp = App()->getPluginManager()->getPluginInfo($arPlugin['name']);

        $this->_renderWrappedTemplate('pluginmanager', 'configure', array('settings' => $aSettings, 'plugin' => $arPlugin, 'properties' => $aPluginProp));
    }

    /**
    * Renders template(s) wrapped in header and footer
    *
    * @param string $sAction Current action, the folder to fetch views from
    * @param string|array $aViewUrls View url(s)
    * @param array $aData Data to be passed on. Optional.
    */
    protected function _renderWrappedTemplate($sAction = 'pluginmanager', $aViewUrls = array(), $aData = array())
    {
        parent::_renderWrappedTemplate($sAction, $aViewUrls, $aData);
    }
}
