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
    private OSM_Data_Portability $data_portability;
    private array $table_schema_callbacks = [];

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
        $this->register_table_schema_callback('osm_leads', [OSM_Leads::class, 'activate']);
        $this->data_portability = new OSM_Data_Portability($this->sites, $this->translations, $this->leads, $this->logger, $this->crypto, $this);

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

    public function sites(): OSM_Sites
    {
        return $this->sites;
    }

    public function logger(): OSM_Logger
    {
        return $this->logger;
    }

    public function crypto(): OSM_Crypto
    {
        return $this->crypto;
    }

    public function leads(): OSM_Leads
    {
        return $this->leads;
    }

    public function register_table_schema_callback(string $logical_name, callable $callback): void
    {
        $logical_name = sanitize_key($logical_name);

        if ($logical_name === '') {
            return;
        }

        $this->table_schema_callbacks[$logical_name] = $callback;
    }

    public function table_schema_callbacks(): array
    {
        return $this->table_schema_callbacks;
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
