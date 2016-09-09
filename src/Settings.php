<?php
namespace Scarbous\MrSpeed;

use Scarbous\MrSpeed\Utility\GeneralUtility;

class Settings
{

    protected $theSettings = [
        'css' => [
            'title' => 'CSS-Optimization-Options',
            'description' => '<p>Options for CSS-Optimization</p>',
            'callback' => 'cssSettingsSection',
            'fields' => [
                'active' => [
                    'type' => 'checkbox',
                    'label' => 'Activate CSS-Optimization'
                ],
                'gzip' => [
                    'type' => 'checkbox',
                    'label' => 'Use gzip'
                ],
                'excludeStyles' => [
                    'type' => 'textarea',
                    'label' => 'Exclude Styles',
                    'description' => 'Styles which should not optimized<br>each Line one Style-Name'
                ],
            ]
        ],
        'js' => [
            'title' => 'JS-Optimization-Options',
            'description' => '<p>Options for JS-Optimization</p>',
            'callback' => 'jsSettingsSection',
            'fields' => [
                'active' => [
                    'type' => 'checkbox',
                    'label' => 'Activate JS-Optimization'
                ],
                'gzip' => [
                    'type' => 'checkbox',
                    'label' => 'Use gzip'
                ],
                'JShrink' => [
                    'type' => 'checkbox',
                    'label' => 'Use JShrink'
                ],
                'excludeScripts' => [
                    'type' => 'textarea',
                    'label' => 'Exclude Scripts',
                    'description' => 'Scripts which should not optimized<br>each Line one Script-Name'
                ],
                'toFooter' => [
                    'type' => 'checkbox',
                    'label' => 'Include at Page end'
                ],
            ]
        ]
    ];

    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_plugin_page']);
        add_action('admin_init', [$this, 'page_init']);
    }

    /**
     * JS Section Description
     *
     * @return void
     */
    public function cssSettingsSection()
    {
        echo $this->theSettings['css']['description'];
    }

    /**
     * JS Section Description
     *
     * @return void
     */
    public function jsSettingsSection()
    {
        echo $this->theSettings['js']['description'];
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
// This page will be under "Settings"
        add_options_page(
            'Settings Admin',
            'Mr.Speed',
            'manage_options',
            'mr_speed_settings',
            [$this, 'create_admin_page']
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        if($_GET['do'] == 'clean_cache') {
            GeneralUtility::cleanTempDir();
        }
// Set class property
        ?>
        <div class="wrap">
            <h1>My Settings</h1>
            <h2>Clean Cache</h2>
            <form method="get" action="options-general.php">
                <input type="hidden" name="page" value="mr_speed_settings" />
                <input type="hidden" name="do" value="clean_cache" />
                <p class="submit"><input type="submit" id="submit" class="button button-primary"
                                         value="Empty Cache"></p>
            </form>
            <hr>
            <form method="post" action="options.php">
                <?php
                settings_fields('mrSpeed');
                do_settings_sections('mrSpeedAdminSettings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            'mrSpeed', // Option group
            'mr_speed', // Option name
            [$this, 'sanitize'] // Sanitize
        );
        $this->options = get_option('mr_speed');

        foreach ($this->theSettings as $sectionName => $sectionConfig) {
            add_settings_section(
                $sectionName, // ID
                $sectionConfig['title'], // Title
                [$this, $sectionConfig['callback']], // Callback
                'mrSpeedAdminSettings' // Page
            );
            if ($sectionConfig['fields']) {
                foreach ($sectionConfig['fields'] as $fieldKey => $fieldConfig) {
                    add_settings_field(
                        $fieldKey, // ID
                        $fieldConfig['label'], // Title
                        [$this, 'renderField'], // Callback
                        'mrSpeedAdminSettings', // Page
                        $sectionName, // Section
                        ['section' => $sectionName, 'prefix' => 'mr_speed', 'key' => $fieldKey, 'config' => $fieldConfig]
                    );
                }
            }
        }

    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     * @return array
     */
    public function sanitize($input)
    {
        foreach ($this->theSettings as $sectionName => $sectionConfig) {
            foreach ($sectionConfig['fields'] as $fieldKey => $fieldConfig) {
                switch ($fieldConfig['type']) {
                    case 'checkbox':
                        if ($input[$sectionName][$fieldKey])
                            $input[$sectionName][$fieldKey] = true;
                        break;
                    case 'text':
                        $input[$sectionName][$fieldKey] = sanitize_text_field($input[$sectionName][$fieldKey]);
                        break;
                }
            }
        }
        return $input;
    }

    /**
     * Returns Tag-Arguments-String
     * @param $arguments
     * @return mixed
     */
    protected function renderArguments($arguments)
    {
        $result = [];
        foreach ($arguments as $name => $value) {
            $result[] = $name . ' = "' . $value . '"';
        }
        return implode(' ', $result);
    }

    public function renderField($args)
    {
        $attributes['id']   = $args['key'];
        $attributes['name'] = $args['prefix'] . '[' . $args['section'] . '][' . $args['key'] . ']';
        $currentValue       = $this->options[$args['section']][$args['key']];

        switch ($args['config']['type']) {
            case 'text':
                $attributes['type']  = "text";
                $attributes['value'] = isset($currentValue) ? esc_attr($currentValue) : '';
                echo '<input ' . $this->renderArguments($attributes) . ' />';
                break;
            case 'textarea':
                $value = isset($currentValue) ? esc_attr($currentValue) : '';
                echo '<textarea ' . $this->renderArguments($attributes) . ' >' . $value . '</textarea>';
                break;
            case 'checkbox':
                $attributes['type']  = "checkbox";
                $attributes['value'] = 1;
                if ($currentValue)
                    $attributes['checked'] = '';
                echo '<input ' . $this->renderArguments($attributes) . ' />';
                break;
            default:
                echo '<p>' . $args['config']['type'] . '</p>';
                break;

        }
    }

}

