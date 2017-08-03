<?php
/*
Plugin Name: Eventbrite for Pods
Plugin URI: http://scottkclark.com/
Description: Eventbrite Event Registration on your site and API syncing to show Attendees on site.
Version: 1.1
Author: Scott Kingsley Clark
Author URI: http://scottkclark.com/wordpress/eventbrite-for-pods/
*/

define('EVENTBRITE_PODS_URL', plugin_dir_url(__FILE__));
define('EVENTBRITE_PODS_DIR', plugin_dir_path(__FILE__));

function eventbrite_pods ()
{
    if (false === eventbrite_pods_validate_plugins())
        return;
    $min_cap = 'eventbrite_for_pods';
    if (current_user_can('manage_options'))
        $min_cap = 'read';
    add_menu_page('Eventbrite', 'Eventbrite', $min_cap, 'eventbrite-pods', null, EVENTBRITE_PODS_URL . 'icons/16.png');
    add_submenu_page('eventbrite-pods', 'Events', 'Events', $min_cap, 'eventbrite-pods', 'eventbrite_pods_admin_handler');
    add_submenu_page('eventbrite-pods', 'Attendees', 'Attendees', $min_cap, 'eventbrite-pods-attendees', 'eventbrite_pods_admin_handler');
    add_submenu_page('eventbrite-pods', 'Tickets', 'Tickets', $min_cap, 'eventbrite-pods-tickets', 'eventbrite_pods_admin_handler');
    add_submenu_page('eventbrite-pods', 'Sync from Eventbrite', 'Sync from Eventbrite', $min_cap, 'eventbrite-pods-sync-from-eventbrite', 'eventbrite_pods_admin_handler');
    add_submenu_page('eventbrite-pods', 'API Settings', 'API Settings', $min_cap, 'eventbrite-pods-api-settings', 'eventbrite_pods_admin_handler');
}

function eventbrite_pods_admin_handler ()
{
    if (false === eventbrite_pods_validate_plugins())
        return;
    $ui = array('icon' => EVENTBRITE_PODS_URL . 'icons/32.png',
                'sort' => 't.name');
    $header_columns = null;
    $form_fields = null;
    $page = str_replace('eventbrite-pods-', '', $_GET['page']);
    switch ($page) {
        case 'attendees':
        case 'tickets':
            $event_filter = '';
            $events = new Pod('eventbrite');
            $params = array('search' => false,
                            'page' => 1,
                            'orderby' => 't.name',
                            'limit' => -1,
                            'where' => 't.display_flag=1',
                            'select' => 't.event_id, t.name');
            $events->findRecords($params);
            if (0 < $events->getTotalRows()) {
                $event_filter = '<label for="eventbrite_event_id">Event:</label> <select name="eventbrite_event_id" id="eventbrite_event_id">';
                if (isset($_GET['eventbrite_event_id']) && !empty($_GET['eventbrite_event_id'])) {
                    $event_id = (int) pods_url_variable('eventbrite_event_id', 'get');
                    $ui['where'] = "t.event_id={$event_id}";
                }
                while ($events->fetchRecord())
                {
                    $selected = (isset($_GET['eventbrite_event_id']) && !empty($_GET['eventbrite_event_id']) && $_GET['eventbrite_event_id'] == $events->get_field('id') ? ' SELECTED' : '');
                    $event_filter .= '<option value="' . $events->get_field('event_id') . '"' . $selected . '>' . $events->get_field('name') . '</option>';
                }
                $event_filter .= '</select>';
            }
            $ui['custom_filters'] = $event_filter;
    }
    switch ($page)
    {
        case 'attendees':
            $pod_name = 'eventbrite_attendee';
            $plural_title = 'Attendees';
            $singular_title = 'Attendee';
            $header_columns = array('name',
                                    'ticket_id' => array('label' => 'Ticket',
                                                         'display_helper' => 'eventbrite_ui_ticket'),
                                    'order_created' => 'Order Date');
            $ui['sort'] = 't.order_created DESC';
            $ui['disable_actions'] = array('add',
                                           'edit',
                                           'delete',
                                           'duplicate');
            $ui['custom_actions'] = array('view' => 'eventbrite_pods_view');
            break;
        case 'tickets':
            $pod_name = 'eventbrite_ticket';
            $plural_title = 'Tickets';
            $singular_title = 'Ticket';
            $header_columns = array('name',
                                    'ticket_id' => 'Eventbrite Ticket ID',
                                    'currency',
                                    'price',
                                    'quantity_available',
                                    'quantity_sold',
                                    'start_date' => array('label' => 'Start Date',
                                                          'display_helper' => 'eventbrite_ui_date'),
                                    'end_date' => array('label' => 'End Date',
                                                        'display_helper' => 'eventbrite_ui_date'));
            $ui['disable_actions'] = array('add',
                                           'edit',
                                           'delete',
                                           'duplicate');
            $ui['custom_actions'] = array('view' => 'eventbrite_pods_view');
            break;
        case 'sync-from-eventbrite':
            eventbrite_pods_sync();
            return;
        case 'api-settings':
            eventbrite_pods_settings();
            return;
        default:
            $pod_name = 'eventbrite';
            $plural_title = 'Events';
            $singular_title = 'Event';
            $header_columns = array('name',
                                    'event_id' => 'Eventbrite Event ID');
            $form_fields = array('name',
                                 'display_flag',
                                 'event_id');
    }
    $options = array('pod' => $pod_name,
                     'title' => $plural_title,
                     'item' => $singular_title,
                     'columns' => $header_columns,
                     'add_fields' => $form_fields,
                     'edit_fields' => $form_fields);
    $ui = array_merge($ui, $options);
    pods_ui_manage($ui);
}

function eventbrite_pods_view ($object)
{
    if (false === eventbrite_pods_validate_plugins())
        return;
    ?>
<h2>View <?php echo $object->ui['item']; ?>
    <small>(<a href="<?php echo pods_ui_var_update(array('action' => 'manage')); ?>">&laquo; Back to List</a>)</small>
</h2>
<link rel="stylesheet" type="text/css" href="<?php echo PODS_URL; ?>/ui/style.css"/>
<div class="pods_form">
    <?php
    if (isset($object->data['pod_id']))
        unset($object->data['pod_id']);
    if (isset($object->data['type']))
        unset($object->data['type']);
    foreach ($object->data as $field => $data)
    {
        $label = trim(ucwords(str_replace(' id ', ' ID ', str_replace('_', ' ', ' ' . $field . ' '))));
        ?>
        <div class="leftside">
            <label for="view_<?php echo $field; ?>"><?php echo $label; ?></label>
        </div>
        <div class="rightside" id="view_<?php echo $field; ?>">
            <?php echo $data; ?>
        </div>
        <div class="clear"></div>
        <?php
    }
    ?>
</div>
<?php
}

function eventbrite_pods_sync ()
{
    if (false === eventbrite_pods_validate_plugins())
        return;
    $app_key = get_option('eventbrite_pods_app_key');
    $user_key = get_option('eventbrite_pods_user_key');
    $api_url = 'https://www.eventbrite.com/xml/%s?app_key=' . $app_key . '&user_key=' . $user_key . '&id=%s';
    ?>
<div class="wrap">
<div id="icon-edit-pages" class="icon32" style="background-position:0 0;background-image:url(<?php echo EVENTBRITE_PODS_URL . 'icons/32.png'; ?>);">
    <br/></div>
<h2>Syncing Eventbrite Data</h2><br/> <a name="top"></a>

<h3>EventBrite Updating now..</h3>

<p><a href="#bottom">Go to Bottom of Page</a></p>
    <?php
    $events = new Pod('eventbrite');
    $params = array('search' => false,
                    'page' => 1,
                    'orderby' => 't.name',
                    'limit' => -1,
                    'where' => 't.display_flag=1'); // AND (t.end_date="0000-00-00 00:00:00" OR t.end_date >= NOW())
    $events->findRecords($params);

    if ($events->getTotalRows() < 1) {
        echo '<strong>No Events Found</strong>';
    }
    while ($events->fetchRecord())
    {
        ?>
    <h4>Updating <?php echo $events->get_field('name'); ?></h4>
    <p>Updating Event Data..</p>
        <?php
        // Columns to save
        $columns = array('title' => '',
                         'description' => '',
                         'category' => '',
                         'tags' => '',
                         'start_date' => '',
                         'end_date' => '',
                         'timezone' => '',
                         'event_created' => '',
                         'event_modified' => '',
                         'privacy' => '',
                         'password' => '',
                         'capacity' => '',
                         'url' => '',
                         'logo' => '',
                         'logo_ssl' => '',
                         'status' => '',
                         'background_color' => '',
                         'text_color' => '',
                         'link_color' => '',
                         'title_text_color' => '',
                         'box_background_color' => '',
                         'box_text_color' => '',
                         'box_border_color' => '',
                         'box_header_background_color' => '',
                         'box_header_text_color' => '');
        $params = array('datatype' => 'eventbrite',
                        'columns' => $columns);
        // begin alternate universe
        $url = sprintf($api_url, 'event_get', $events->get_field('event_id'));
        $xml = file_get_contents($url);
        $options = array('params' => $params,
                         'xml' => $xml);
        echo $events->pod_helper('eventbrite_update_event', $options);
        ?>
    <p><strong>Event Data Update Completed</strong></p>
    <p>Updating Tickets..</p>
        <?php
        // Columns to save
        $columns = array('eventbrite_id' => $events->get_field('id'),
                         'event_id' => $events->get_field('event_id'),
                         'ticket_id' => '',
                         'name' => '',
                         'description' => '',
                         'ticket_type' => '',
                         'currency' => '',
                         'price' => '',
                         'start_date' => '',
                         'end_date' => '',
                         'quantity_available' => '',
                         'quantity_sold' => '',
                         'visible' => '');
        $params = array('datatype' => 'eventbrite_ticket',
                        'columns' => $columns);
        // begin alternate universe
        //$url = sprintf($api_url, 'event_get', $events->get_field('event_id'));
        //$xml = file_get_contents($url);
        $options = array('params' => $params,
                         'xml' => $xml);
        echo $events->pod_helper('eventbrite_update_ticket', $options);
        ?>
    <p><strong>Ticket Update Completed</strong></p>
    <p>Updating Attendees..</p>
        <?php
        // Columns to save
        $columns = array('eventbrite_id' => $events->get_field('id'),
                         'event_id' => $events->get_field('event_id'),
                         'name' => '',
                         'attendee_id' => '',
                         'ticket_id' => '',
                         'quantity' => '',
                         'currency' => '',
                         'amount_paid' => '',
                         'barcode' => '',
                         'order_id' => '',
                         'order_type' => '',
                         'order_created' => '',
                         'order_modified' => '',
                         'event_date' => '',
                         'discount' => '',
                         'notes' => '',
                         'email' => '',
                         'prefix' => '',
                         'first_name' => '',
                         'last_name' => '',
                         'suffix' => '',
                         'home_address' => '',
                         'home_address_2' => '',
                         'home_city' => '',
                         'home_postal_code' => '',
                         'home_region' => '',
                         'home_country' => '',
                         'home_country_code' => '',
                         'home_phone' => '',
                         'cell_phone' => '',
                         'ship_address' => '',
                         'ship_address_2' => '',
                         'ship_city' => '',
                         'ship_postal_code' => '',
                         'ship_region' => '',
                         'ship_country' => '',
                         'ship_country_code' => '',
                         'work_address' => '',
                         'work_address_2' => '',
                         'work_city' => '',
                         'work_postal_code' => '',
                         'work_region' => '',
                         'work_country' => '',
                         'work_country_code' => '',
                         'work_phone' => '',
                         'job_title' => '',
                         'company' => '',
                         'website' => '',
                         'blog' => '',
                         'gender' => '',
                         'birth_date' => '',
                         'age' => '');
        $params = array('datatype' => 'eventbrite_attendee',
                        'columns' => $columns);
        // begin alternate universe
        $url = sprintf($api_url, 'event_list_attendees', $events->get_field('event_id'));
        $xml = file_get_contents($url);
        $options = array('params' => $params,
                         'xml' => $xml);
        echo $events->pod_helper('eventbrite_update_attendee', $options);
        ?>
    <p><strong>Attendee Update Completed</strong></p>
    <p>Pushing Ticket / Attendee / Attendee Answers temp data live..</p>
        <?php
        $api = new PodAPI();
        // Tickets
        $logical_delete = new Pod('eventbrite_ticket');
        $logical_delete->findRecords('t.id', -1, 't.eventbrite_id=' . $events->get_field('id') . ' AND t.event_id=' . $events->get_field('event_id') . ' AND t.display_flag=1');
        while ($logical_delete->fetchRecord())
        {
            $api->drop_pod_item(array('tbl_row_id' => $logical_delete->get_field('id'),
                                      'datatype' => $logical_delete->datatype,
                                      'datatype_id' => $logical_delete->datatype_id));
        }
        $logical_delete->findRecords('t.id', -1, 't.eventbrite_id=' . $events->get_field('id') . ' AND t.event_id=' . $events->get_field('event_id') . ' AND t.display_flag=0');
        while ($logical_delete->fetchRecord())
        {
            pod_query("UPDATE @wp_pod_tbl_" . $logical_delete->datatype . " SET display_flag=1");
        }
        // Attendees
        $logical_delete = new Pod('eventbrite_attendee');
        $logical_delete->findRecords('t.id', -1, 't.eventbrite_id=' . $events->get_field('id') . ' AND t.event_id=' . $events->get_field('event_id') . ' AND t.display_flag=1');
        while ($logical_delete->fetchRecord())
        {
            $api->drop_pod_item(array('tbl_row_id' => $logical_delete->get_field('id'),
                                      'datatype' => $logical_delete->datatype,
                                      'datatype_id' => $logical_delete->datatype_id));
        }
        $logical_delete->findRecords('t.id', -1, 't.eventbrite_id=' . $events->get_field('id') . ' AND t.event_id=' . $events->get_field('event_id') . ' AND t.display_flag=0');
        while ($logical_delete->fetchRecord())
        {
            pod_query("UPDATE @wp_pod_tbl_" . $logical_delete->datatype . " SET display_flag=1");
        }
        // Attendee Answers
        $logical_delete = new Pod('eventbrite_attendee_answer');
        $logical_delete->findRecords('t.id', -1, 't.eventbrite_id=' . $events->get_field('id') . ' AND t.event_id=' . $events->get_field('event_id') . ' AND t.display_flag=1');
        while ($logical_delete->fetchRecord())
        {
            $api->drop_pod_item(array('tbl_row_id' => $logical_delete->get_field('id'),
                                      'datatype' => $logical_delete->datatype,
                                      'datatype_id' => $logical_delete->datatype_id));
        }
        $logical_delete->findRecords('t.id', -1, 't.eventbrite_id=' . $events->get_field('id') . ' AND t.event_id=' . $events->get_field('event_id') . ' AND t.display_flag=0');
        while ($logical_delete->fetchRecord())
        {
            pod_query("UPDATE @wp_pod_tbl_" . $logical_delete->datatype . " SET display_flag=1");
        }
        ?>
    <p><strong>**<?php echo $events->get_field('name'); ?> Complete!**</strong></p>
        <?php
    }
    ?>
<a name="bottom"></a>
<h4>All done!</h4>

<p><a href="#top">Go to Top of Page</a></p>
</div>
<?php
}

function eventbrite_pods_settings ()
{
    if (false === eventbrite_pods_validate_plugins())
        return;
    $fields = array('app_key' => 'App Key',
                    'user_key' => 'User Key',
                    'create_user' => 'Create Users when Syncing Attendees (if e-mail address not found for an Attendee as a WP User)');
    if (isset($_POST['submit'])) {
        foreach ($fields as $field => $label)
        {
            update_option('eventbrite_pods_' . $field, pods_url_variable($field, 'post'));
        }
        pods_ui_message('Updated Settings');
    }
    ?>
<div class="wrap">
    <div id="icon-edit-pages" class="icon32" style="background-position:0 0;background-image:url(<?php echo EVENTBRITE_PODS_URL . 'icons/32.png'; ?>);">
        <br/></div>
    <h2>Manage Eventbrite API Settings</h2>
    <link rel="stylesheet" type="text/css" href="<?php echo PODS_URL; ?>/ui/style.css"/>
    <p>App and User information can be obtained by logging in and going to the
        <a href="http://www.eventbrite.com/userkeyapi" target="_blank">Eventbrite API User Key</a> portion of the
        <a href="http://www.eventbrite.com/" target="_blank">Eventbrite</a> site.</p>

    <form action="" method="post">
        <div class="pods_form">
            <?php
            foreach ($fields as $field => $label)
            {
                $value = get_option('eventbrite_pods_' . $field);
                ?>
                <div class="leftside <?php echo $field; ?>">
                    <label for="<?php echo $field; ?>"><?php echo $label; ?></label>
                </div>
                <div class="rightside <?php echo $field; ?>">
                    <?php
                    if ($field == 'create_user') {
                        ?>
                        <input type="checkbox" name="<?php echo $field; ?>" id="<?php echo $field; ?>" value="1"<?php echo ($value == 1 ? ' CHECKED' : ''); ?> />
                        <?php
                    }
                    else
                    {
                        ?>
                        <input type="text" name="<?php echo $field; ?>" id="<?php echo $field; ?>" value="<?php echo $value; ?>"/>
                        <?php
                    }
                    ?>
                </div>
                <div class="clear"></div>
                <?php
            }
            ?>
            <input type="submit" class="button" name="submit" value=" Update Settings "/>
        </div>
    </form>
</div>
<?php
}

function eventbrite_pods_shortcode ($tags)
{
    if (false === eventbrite_pods_validate_plugins())
        return;
    $pairs = array('event_id' => null,
                   'name' => null,
                   'id' => null,
                   'iframe' => true,
                   'attendees' => null);
    $tags = shortcode_atts($pairs, $tags);

    if (empty($tags['id']) && empty($tags['event_id']) && empty($tags['name'])) {
        return '<strong>Error:</strong> Please define an Event in your Shortcode.';
    }

    $eventbrite = new Pod('eventbrite');
    $where = 't.display_flag=1 AND ';
    if (!empty($tags['id'])) {
        $tags['id'] = (int) $tags['id'];
        $where .= "t.id={$tags['id']}";
    }
    elseif (!empty($tags['event_id']))
    {
        $tags['event_id'] = (int) $tags['event_id'];
        $where .= "t.event_id={$tags['event_id']}";
    }
    elseif (!empty($tags['name']))
    {
        $tags['name'] = pods_sanitize($tags['name']);
        $where .= "t.name={$tags['name']}";
    }
    $params = array('search' => false,
                    'page' => 1,
                    'orderby' => 't.id DESC',
                    'limit' => 1,
                    'where' => $where);
    $eventbrite->findRecords($params);
    if ($eventbrite->getTotalRows() > 0) {
        $eventbrite->fetchRecord();
    }
    else
    {
        return '<strong>Error:</strong> Event not found.';
    }
    if (!empty($tags['attendees'])) {
        $attendees = new Pod('eventbrite_attendee');
        $params = array('search' => false,
                        'page' => 1,
                        'orderby' => 't.attendee_id ASC',
                        'limit' => -1,
                        'where' => 't.eventbrite_id=' . $eventbrite->get_field('id') . ' AND t.event_id=' . $eventbrite->get_field('event_id') . ' AND t.display_flag=1',
                        'groupby' => 't.order_id');
        $attendees->findRecords($params);
        if (0 < $attendees->getTotalRows()) {
            ?>
        <div id="eventbrite_pods_attendees_list" class="eventbrite_pods_multi_column_list eventbrite_pods_3_column_list">
            <h2>Attendees</h2>
            <ul>
                <?php echo $attendees->showTemplate('eventbrite_attendee_list'); ?>
            </ul>
            <br>
        </div>
        <?php
        }
    }
    elseif ($tags['iframe'] === true)
    {
        // ref code set to skc to help support plugin development / maintenance - feel free to change this
        ?>
    <iframe src="http://www.eventbrite.com/tickets-external?eid=<?php echo $eventbrite->get_field('event_id'); ?>&amp;ref=skc" width="100%" height="950" allowtransparency="true" scrolling="auto" frameborder="0" marginwidth="5" marginheight="5" vspace="0" hspace="0"></iframe>
    <?php
    }
    else
    {
        $eventbrite_tickets = new Pod('eventbrite_ticket');
        $params = array('search' => false,
                        'page' => 1,
                        'orderby' => 't.id DESC',
                        'limit' => -1,
                        'where' => 't.event_id=' . $eventbrite->get_field('event_id') . ' AND t.display_flag=1');
        $eventbrite_tickets->findRecords($params);
        if (0 < $eventbrite_tickets->getTotalRows()) {
            ?>
        <ul class="eventbrite_ticket_list">
            <?php echo $eventbrite_tickets->showTemplate('eventbrite_ticket_list'); ?>
        </ul>
        <?php
        }
        else
        {
            echo 'No tickets found for this event, try running the Eventbrite Sync.';
        }
    }
}

add_action('admin_menu', 'eventbrite_pods');
add_shortcode('eventbrite', 'eventbrite_pods_shortcode');

///////////////////////////////////////////////////////////////////
// Pods-based Plugin Standard
// v1.0 - 07/28/2010 - @sc0ttkclark
///////////////////////////////////////////////////////////////////
function eventbrite_pods_validate_plugins ()
{
    if (!function_exists('pod_query') || !function_exists('pods_ui_manage')) {
        add_thickbox();
        add_action('admin_notices', 'eventbrite_pods_validate_plugins_notice');
        return false;
    }
    return true;
}

function eventbrite_pods_validate_plugins_notice ()
{
    $this_plugin = 'Eventbrite for Pods';
    if (!function_exists('pod_query') || !function_exists('pods_ui_manage')) {
        $plugin_name = 'Pods CMS Framework';
        $plugin_slug = 'pods';
        ?>
    <div class="updated fade">
        <p>The <?php echo $plugin_name; ?> plugin is required for the <?php echo $this_plugin; ?> plugin to function properly.
            <a href="<?php echo admin_url('plugin-install.php?tab=plugin-information&plugin=' . $plugin_slug . '&TB_iframe=true&width=640&height=517'); ?>" class="thickbox onclick">Install now</a>.
        </p>
    </div>
    <?php
    }
}

function eventbrite_pods_activate ()
{
    if (false === eventbrite_pods_validate_plugins())
        return false;
    $api = new PodAPI();
    ob_start();
    $package = file_get_contents(dirname(__FILE__) . '/package.txt');
    $package = addslashes(trim($package));
    $imported = $api->import_package($package, true);
    $void = ob_get_clean();
    return (true === $imported ? true : false);
}

register_activation_hook(__FILE__, 'eventbrite_pods_activate');