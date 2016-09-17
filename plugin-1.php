<?php 

/*
Plugin Name: AC App Pages
Plugin URI:  http://www.collierwebdesign.com
Description: Admin UI and import scheduler for App Pages post type
Version:     1.0
Author:      Cyrus Collier
*/

define('AC_APP_PAGES_DIR',plugin_dir_path(__FILE__));
define('AC_APP_PAGES_URL',plugin_dir_url(__FILE__));
define('AC_POD_APP_PAGE', 'app_page');
define('AC_POD_APP_CATEGORY', 'app_category');
define('AC_POD_APP_BADGE', 'app_badge');
define('AC_POD_APP_PAGE_IMPORT', 'app_page_import');
define('AC_SCHEDULE_EVENT_HOOK','app_page_import');
define('AC_IMPORT_TIME_LIMIT',180);

class AC_AppPages {
    
    protected $app_api_url;
    protected $import_interval;
    protected $activate_import_schedule;
    protected $partial_import = false;
    protected $start_results_at = 1;
    protected $run_importer_now = false;
    protected $start_date;
    protected $end_date;
    protected $email_notifications;
    protected $badges = array();
    protected $messages = array();
    protected $date_intervals_map = array(
        'twicedaily' => '-12 hours',
        'daily' => '-1 day',
        'weekly' => '-7 days',
        '2weekly' => '-14 days',
        '4weekly' => '-28 days'    
    );
    
    /**
     * Adds top-level plugin hooks
     */
    function __construct() {
        add_action('admin_init',array(&$this, 'init'));
        add_filter('cron_schedules',array(&$this, 'cron_schedules'));
        add_action(AC_SCHEDULE_EVENT_HOOK,array(&$this,'cron_import'));
    }
    
    /**
     * Main initialization of properties and more hooks
     * action: admin_init
     */
    function init() {
        //plugin requires Pods plugin
        if(!defined( 'PODS_VERSION' )) {
            add_action('admin_notices', array(&$this, 'pods_plugin_required'));
            return;
        }
        
        $app_page_import = pods(AC_POD_APP_PAGE_IMPORT);
        $this->app_api_url = $app_page_import->field('app_api_url');
        $this->import_interval = $app_page_import->field('import_interval');
        $this->activate_import_schedule = $app_page_import->field('activate_import_schedule');
        $this->import_result_limit = $app_page_import->field('import_result_limit');
        $this->start_results_at = $app_page_import->field('start_results_at');
        $this->run_importer_now = $app_page_import->field('run_importer_now');
        $this->email_notifications = $app_page_import->field('email_notifications');
        if(!$this->email_notifications) $this->email_notifications = get_option('admin_email');
        $this->start_date = $app_page_import->field('start_date');
        $this->end_date = $app_page_import->field('end_date');
        
        $app_page_badges = pods(AC_POD_APP_BADGE, array('limit'=>-1));
        while($app_page_badges->fetch()) {
            $this->badges[$app_page_badges->field('term_id')] = $app_page_badges->field('slug');
        }
        
        add_action('pods_ui_form', array(&$this, 'form_post'));
        add_filter('pods_form_ui_field_number_value',array(&$this, 'start_results_at_value'), 10, 2);
    }
    
    /**
     * Custom WP cron frequencies
     * filter: cron_schedules
     * 
     * @param array $schedules
     * @return array
     */
    function cron_schedules( $schedules ) {
        $schedules['weekly'] = array(
            'interval' => WEEK_IN_SECONDS,
            'display' => __( 'Once Weekly' )
        );
        $schedules['2weekly'] = array(
            'interval' => 2 * WEEK_IN_SECONDS,
            'display' => __( 'Once Every 2 Weeks' )
        );
        $schedules['4weekly'] = array(
            'interval' => 4 * WEEK_IN_SECONDS,
            'display' => __( 'Once Every 4 Weeks' )
        );
        return $schedules;
    }
    
    /**
     * WP Cron hook for importing
     * action: AC_SCHEDULE_EVENT_HOOK
     */
    function cron_import() {
        error_log('Cron Import Started');
        $this->init();
        $result = $this->import();
        if(is_wp_error($result)) {
            $result_message = 'Failed';
            $body = "Import Error: {$result->get_error_message()}";
        } else {
            $result_message = 'Successful';
            $body = "$this->imported Apps Imported Successfully!";
        }
        
        $body .= "\n\nMessages:\n";
        $body .= implode("\n",$this->messages);
        //send email notification
        $to = $this->email_notifications;
        $subject = 'App Pages Importer: Import '.$result_message;
        error_log($subject);
        error_log($body);
        $mailed = wp_mail($to, $subject, $body);
        $mailed = (int) $mailed;
        error_log('Cron Import Finished, mailed='.$mailed);
    }
    
    /**
     * Admin notice if Pods plugin is not installed
     * action: admin_notices
     */
    function pods_plugin_required() {
?>
    <div class="error">
        <p>XXXXXXXXX App Pages Plugin requires the Pods Framework plugin to operate.</p>
    </div>
<?php        
    }
    
    /**
     * Override start value for 'pods_field_start_results_at' field
     * filter: pods_form_ui_field_number_value
     * 
     * @param mixed $value
     * @param string $name
     * @return mixed
     */
    function start_results_at_value($value, $name) {
        if($name == 'pods_field_start_results_at') {
            $value = $this->start_results_at;
        }
        return $value;
    }
    
    /**
     * Pods form POST response
     * action: pods_ui_form
     * 
     * @param Pods_UI $pods_ui
     */
    function form_post($pods_ui) {        
        if(!(isset($_GET['do']) && $_GET['do'] == 'save')) return;
        
        //schedule/unschedule importer
        if(
            $this->activate_import_schedule && 
            $this->import_interval &&
            false === wp_next_scheduled(AC_SCHEDULE_EVENT_HOOK
        )) {
            $timestamp_start = strtotime('midnight tomorrow',current_time('timestamp'));
            //switch to gmt for wp-cron
            $timestamp_start -= get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
            wp_schedule_event($timestamp_start, $this->import_interval, AC_SCHEDULE_EVENT_HOOK);
            $this->messages[] = 'Import has been scheduled. Next import will run at midnight tonight';
        }
        if(!$this->activate_import_schedule && $timestamp = wp_next_scheduled(AC_SCHEDULE_EVENT_HOOK)) {
            wp_unschedule_event($timestamp, AC_SCHEDULE_EVENT_HOOK);
            $this->messages[] = 'Import has been unscheduled';
        }
        
        if($pods_ui->pod->field('run_importer_now')) {
            $result = $this->import();
            if(is_wp_error($result)) {
?>
<div class="error">
    <p>Import Error: <?php echo $result->get_error_message(); ?></p>
</div>
<?php 
            }
        }
        if(!empty($this->messages)) {
?>
<div class="updated">
    <?php if(isset($this->imported)): ?>
    <p><?php echo $this->imported; ?> Apps Imported Successfully!</p>
    <?php endif; ?>
    <?php foreach($this->messages as $message): ?>
    <p><?php echo $message; ?></p>
    <?php endforeach; ?>
</div>
<?php
        }
    }
    
    
    /**
     * Imports app records via remote API
     * 
     * @return int|WP_Error
     */
    protected function import() {
        set_time_limit(AC_IMPORT_TIME_LIMIT);
        $time_pre = microtime(true);
        $paged = 0;
        $next_page = $this->app_api_url . '?order_by=date_modified&order=desc';
        if('0000-00-00' != $this->start_date) $next_page .= '&start_date='.$this->start_date;
        if('0000-00-00' != $this->end_date) $next_page .= '&end_date='.$this->end_date;
        $date_interval = date('Y-m-d',strtotime($this->get_date_diff()));
        if(defined('DOING_CRON') && DOING_CRON) {
            $now = date('Y-m-d');
            $this->messages[] = "Import Interval: $now to $date_interval ({$this->get_date_diff()})";
        }
        $imported = 0;
        $duplicate_dates = array();
        try {
            while($next_page) {
                $result = wp_remote_get($next_page,array('timeout'=>30));
                if(is_wp_error($result)) {
                    $this->set_partial_import($imported);
                    return $result;
                }
                $url_parts = explode('?',$next_page);
                $url_query = wp_parse_args($url_parts[1]);
                $data = json_decode($result['body']);
                $per_page = count($data->results);
                $next_page = $data->next;
                if($next_page && $per_page && $this->start_results_at > ($url_query['page'] * $per_page)) {
                    $paged_new = ceil($this->start_results_at / $per_page);
                    $url_query['page'] = $paged_new;
                    $next_page = implode('?',array(
                        $url_parts[0],
                        build_query($url_query)
                    ));
                    continue;
                }
                if($this->import_result_limit <= $imported) {
                    $this->partial_import = true;
                    break;
                }
                foreach($data->results as $row) {
                    //skip blank entries not in itunes
                    if($row->not_in_itunes && empty($row->description))    continue;            
                    $app_page = pods(AC_POD_APP_PAGE,array('where'=>"t.guid='$row->url'"));
                    if(0 < $app_page->total_found) $app_page->fetch();
                    $post_date = $row->date_created;
                    //duplicate date, generate incremental timestamp
                    if(isset($duplicate_dates[$row->date_created])) {
                        $timestamp = strtotime($post_date);
                        $timestamp += 60 * $duplicate_dates[$row->date_created];
                        $post_date = date('Y-m-d H:i:s',$timestamp);
                    } else {
                        $duplicate_dates[$row->date_created] = 0;
                    }
                    $duplicate_dates[$row->date_created]++;
                    //don't need raw iTunes data
                    unset($row->itunes_data_serialized);
                    $fields = array(
                        'guid' => $row->url,
                        'name' => $row->name,
                        'post_date' => $post_date,
                        'post_modified' => $row->date_modified,
                        'post_excerpt' => serialize($row),
                        'post_status' => 'publish'
                    );
                    try {
                        $post_id = $app_page->save($fields);
                        $matched_badges = $this->import_get_badges($row);
                        wp_set_object_terms($post_id, $matched_badges, AC_POD_APP_BADGE);
                        if(DOING_CRON) {
                            $this->messages[] = "Imported [$post_id]: $row->name, modified $row->date_modified";
                        }
                    } catch(Exception $e) {
                        $this->set_partial_import($imported);
                        return new WP_Error('app-pages-import',$e->getMessage());
                    }
                    $imported++;
                }
                //end of page loop
            }//end of total loop
            $time_post = microtime(true);
            $exec_time = $time_post - $time_pre;
            error_log("$imported pages imported in $exec_time seconds");
            $finished = defined('DOING_CRON') && DOING_CRON;
            return $this->set_partial_import($imported,true);
            
        } catch (Exception $e) {
            $this->set_partial_import($imported);
            return new WP_Error('app-pages-import',$e->getMessage());
        }
    }
    
    /**
     * Sets intermediate state for partial/batch importing
     * 
     * @param int $imported
     * @param bool $finished
     * @return int
     */
    protected function set_partial_import($imported,$finished = false) {
        $this->imported = $imported;
        if($finished && !$this->partial_import) {
            $this->start_results_at = 1;
        } else {
            $this->start_results_at += $imported;
            $this->partial_import = true;
            if($this->run_importer_now)
                $this->messages[] = 'Run importer again to load the next batch of results';
        }
        return $this->imported;
    }
    
    /**
     * Translates and matches remote badge data with current WP "badge" taxonomy 
     * 
     * @param object $row
     * @return array
     */
    protected function import_get_badges($row) {
        $matched_badges = array();
        foreach($this->badges as $id => $badge) {
            $badge_formatted = str_replace('-', '_', $badge);
            if(
                property_exists($row, $badge_formatted) &&
                true === $row->{$badge_formatted}
            ) {
                $matched_badges[] = $id;
            }
        }
        return $matched_badges;
    }
    
    /**
     * Maps from import intervals to string used by strtotime
     * 
     * @return string|false
     */
    protected function get_date_diff() {
        return $this->import_interval && array_key_exists($this->import_interval, $this->date_intervals_map) ?
            $this->date_intervals_map[$this->import_interval] :
            false;
    }
}

$GLOBALS['AC_AppPages'] = new AC_AppPages();