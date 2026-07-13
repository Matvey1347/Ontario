<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class OSM_Plugin
{
    private static ?self $instance = null;

    private OSM_Logger $logger;
    private OSM_Crypto $crypto;
    private OSM_Sites $sites;
    private OSM_Current_Site $current_site;
    private OSM_Translations $translations;
    private OSM_Leads $leads;
    private OSM_Zoho_CRM $zoho;
    private OSM_Rest_Forms $rest_forms;
    private OSM_Admin $admin;

    private function __construct()
    {
        $this->logger = new OSM_Logger();
        $this->crypto = new OSM_Crypto($this->logger);
        $this->sites = new OSM_Sites($this->crypto, $this->logger);
        $this->current_site = new OSM_Current_Site($this->sites, $this->logger);
        $this->translations = new OSM_Translations($this->sites, $this->current_site);
        $this->leads = new OSM_Leads($this->logger);
        $this->zoho = new OSM_Zoho_CRM($this->logger);
        $this->rest_forms = new OSM_Rest_Forms($this->current_site, $this->translations, $this->leads, $this->zoho, $this->logger);
        $this->admin = new OSM_Admin($this->sites, $this->current_site, $this->translations, $this->leads, $this->logger);

        add_action('init', [$this, 'maybe_upgrade'], 40);
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function activate(): void
    {
        $logger = new OSM_Logger();
        $crypto = new OSM_Crypto($logger);
        $sites = new OSM_Sites($crypto, $logger);
        $sites->register_post_type();
        OSM_Leads::activate();
        flush_rewrite_rules(false);
    }

    public function current_site(): OSM_Current_Site
    {
        return $this->current_site;
    }

    public function translations(): OSM_Translations
    {
        return $this->translations;
    }

    public function maybe_upgrade(): void
    {
        $installed = (string) get_option('osm_plugin_version', '');

        if ($installed === OSM_PLUGIN_VERSION) {
            return;
        }

        $this->sites->register_post_type();
        OSM_Leads::activate();
        flush_rewrite_rules(false);
        update_option('osm_plugin_version', OSM_PLUGIN_VERSION, true);
    }
}
