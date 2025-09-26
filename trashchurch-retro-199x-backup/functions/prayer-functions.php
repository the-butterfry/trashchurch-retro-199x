<?php
/**
 * Prayer List Feature - Classifieds style Prayer Request system
 * Version: with image, delete, and classified title support
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Register Prayer Request Custom Post Type (add 'thumbnail' support)
add_action('init', function() {
    register_post_type('prayer_request', [
        'labels' => [
            'name' => __('Prayer Requests', 'trashchurch-retro-199x'),
            'singular_name' => __('Prayer Request', 'trashchurch-retro-199x'),
            'add_new_item' => __('Add New Prayer Request', 'trashchurch-retro-199x'),
            'edit_item' => __('Edit Prayer Request', 'trashchurch-retro-199x'),
        ],
        'public' => false,
        'show_ui' => true,
        'capability_type' => 'post',
        'supports' => ['title', 'custom-fields', 'thumbnail'],
        'menu_icon' => 'dashicons-welcome-write-blog',
    ]);
});

// 2. Prayer Request Submission Form Shortcode
add_shortcode('prayer_request_form', function() {
    ob_start(); ?>
    <form id="prayer-request-form" method="post" action="" enctype="multipart/form-data">
        <div class="prayer-form-inner">
            <label>Title (1-3 word description)<br>
              <input type="text" name="prayer_title" maxlength="48" pattern=".{2,48}">
            </label><br>
            <label>Name (required)<br><input type="text" name="prayer_name" required></label><br>
            <label>Basic Request (required, 1-2 sentences)<br><input type="text" name="prayer_basic" required maxlength="180"></label><br>
            <label>Date or Timeframe<br><input type="text" name="prayer_when"></label><br>
            <label>Urgency<br>
                <select name="prayer_urgency">
                    <option value="Normal">Normal</option>
                    <option value="Urgent">Urgent</option>
                </select>
            </label><br>
            <label>Full Request (required)<br><textarea name="prayer_full" required></textarea></label><br>
            <label>Preferred Contact (required)<br>
                <select name="prayer_contact" id="prayer-contact-select" required>
                    <option value="">Select</option>
                    <option>Phone</option>
                    <option>Email</option>
                    <option>Text</option>
                    <option>Signal</option>
                    <option>Trash Rodent</option>
                </select>
            </label>
            <div id="contact-email-field" style="display:none;">
              <label>Email Address (required)<br>
                <input type="email" name="prayer_email" id="prayer-email-input">
              </label>
            </div>
            <div id="contact-phone-field" style="display:none;">
              <label>Phone Number (required)<br>
                <input type="text" name="prayer_phone" id="prayer-phone-input">
              </label>
            </div>
            <!-- Themed file upload -->
            <div class="prayer-file-outer">
                <label class="prayer-file-label" tabindex="0">Choose Image</label>
                <span class="prayer-file-chosen"></span>
                <input type="file" accept="image/*" name="prayer_photo" style="display:none;">
            </div>
        </div>
        <input type="hidden" name="prayer_form_submitted" value="1">
        <?php wp_nonce_field('prayer_request_submit', 'prayer_nonce'); ?>
        <div class="prayer-form-submit-wrap">
            <button type="submit">Submit Prayer Request</button>
        </div>
    </form>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      var fileInput = document.querySelector('#prayer-request-form input[type="file"]');
      var fileLabel = document.querySelector('#prayer-request-form .prayer-file-label');
      var fileChosen = document.querySelector('#prayer-request-form .prayer-file-chosen');
      if(fileInput && fileLabel) {
        fileLabel.addEventListener('click', function() { fileInput.click(); });
        fileInput.addEventListener('change', function() {
          if (fileInput.files.length > 0) {
            fileChosen.textContent = fileInput.files[0].name;
          } else {
            fileChosen.textContent = '';
          }
        });
      }
      // Dynamic show/hide for email/phone based on contact selection
      var select = document.getElementById('prayer-contact-select');
      var emailField = document.getElementById('contact-email-field');
      var phoneField = document.getElementById('contact-phone-field');
      var emailInput = document.getElementById('prayer-email-input');
      var phoneInput = document.getElementById('prayer-phone-input');
      function updateContactFields() {
        if (select.value === 'Email') {
          emailField.style.display = '';
          phoneField.style.display = 'none';
          emailInput.required = true;
          phoneInput.required = false;
        } else if (select.value === 'Phone') {
          emailField.style.display = 'none';
          phoneField.style.display = '';
          emailInput.required = false;
          phoneInput.required = true;
        } else {
          emailField.style.display = 'none';
          phoneField.style.display = 'none';
          emailInput.required = false;
          phoneInput.required = false;
        }
      }
      select.addEventListener('change', updateContactFields);
      updateContactFields();
    });
    </script>
    <?php
    if (isset($_GET['prayer_submitted'])) {
        echo '<div class="prayer-submit-success">Your prayer request was submitted!</div>';
    }
    return ob_get_clean();
});

// 3. Prayer Request Form Handler (support file upload & classified title)
add_action('init', function() {
    if (
        isset($_POST['prayer_form_submitted']) &&
        wp_verify_nonce($_POST['prayer_nonce'], 'prayer_request_submit')
    ) {
        $title = sanitize_text_field($_POST['prayer_title'] ?? '');
        $name   = sanitize_text_field($_POST['prayer_name']);
        $basic  = sanitize_text_field($_POST['prayer_basic']);
        $when   = sanitize_text_field($_POST['prayer_when']);
        $urg    = sanitize_text_field($_POST['prayer_urgency']);
        $full   = sanitize_textarea_field($_POST['prayer_full']);
        $contact= sanitize_text_field($_POST['prayer_contact']);
        $email  = isset($_POST['prayer_email']) ? sanitize_email($_POST['prayer_email']) : '';
        $phone  = isset($_POST['prayer_phone']) ? sanitize_text_field($_POST['prayer_phone']) : '';
        $status = 'Unanswered';
        $assignment = 0; // default to Unassigned (user ID 0)

        $dt = date('Y-m-d');
        $post_title = "$name-$dt";
        $post_id = wp_insert_post([
            'post_type' => 'prayer_request',
            'post_title' => $post_title,
            'post_status' => 'publish',
            'meta_input' => [
                'prayer_classified_title' => $title,
                'prayer_name' => $name,
                'prayer_basic' => $basic,
                'prayer_when' => $when,
                'prayer_urgency' => $urg,
                'prayer_full' => $full,
                'prayer_contact' => $contact,
                'prayer_status' => $status,
                'prayer_assignment' => $assignment,
                'prayer_date' => $dt,
                'prayer_email' => $email,
                'prayer_phone' => $phone,
            ]
        ]);
        // Handle photo upload
        if ($post_id && !empty($_FILES['prayer_photo']['tmp_name'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            $attachment_id = media_handle_upload('prayer_photo', $post_id);
            if (is_numeric($attachment_id)) {
                set_post_thumbnail($post_id, $attachment_id);
            }
        }
        wp_redirect(add_query_arg('prayer_submitted', 1, get_permalink()));
        exit;
    }
});

// 4. Prayer List Tiles Shortcode (show photo thumb & classified title)
add_shortcode('prayer_list_tiles', function() {
    $args = [
        'post_type'      => 'prayer_request',
        'posts_per_page' => 100,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'     => 'prayer_status',
                'value'   => 'Unanswered',
                'compare' => '=',
            ],
            [
                'key'     => 'prayer_status',
                'value'   => 'Answered!',
                'compare' => '=',
            ]
        ]
    ];
    $prayers = get_posts($args);

    $unanswered = [];
    $answered   = [];
    foreach ($prayers as $pr) {
        $status = get_post_meta($pr->ID, 'prayer_status', true);
        if ($status === 'Answered!') {
            $answered[] = $pr;
        } else {
            $unanswered[] = $pr;
        }
    }
    $tiles = array_merge($unanswered, $answered);

    ob_start();
    echo '<div class="prayer-list-classifieds">';
    foreach ($tiles as $pr) {
        $id              = esc_attr($pr->ID);
        $basic           = esc_html(get_post_meta($id, 'prayer_basic', true));
        $urg             = esc_html(get_post_meta($id, 'prayer_urgency', true));
        $name            = esc_html(get_post_meta($id, 'prayer_name', true));
        $status          = esc_html(get_post_meta($id, 'prayer_status', true));
        $assign_id_raw   = get_post_meta($id, 'prayer_assignment', true);

        // Safely convert assignment to user ID (int), fallback to 0 if not valid
        $assign_id = (is_numeric($assign_id_raw) && intval($assign_id_raw) > 0) ? intval($assign_id_raw) : 0;
        $assign_name = 'Unassigned';
        if ($assign_id > 0) {
            $assigned_user = get_user_by('id', $assign_id);
            if ($assigned_user && !is_wp_error($assigned_user)) {
                $assign_name = $assigned_user->display_name;
            }
        }
        $classified_title = get_post_meta($id, 'prayer_classified_title', true);

        echo "<div class='prayer-tile prayer-$status' data-id='$id' tabindex='0'>";
        // Optional thumbnail
        if (has_post_thumbnail($id)) {
            $thumb = get_the_post_thumbnail($id, 'thumbnail', ['class'=>'prayer-thumb']);
            echo "<div class='prayer-tile-img'>$thumb</div>";
        }
        // Random styled classified title
        $headline_styles = [
            '<span class="prayer-head-bold">%s</span>',
            '<span class="prayer-head-italics"><em>%s</em></span>',
            '<span class="prayer-head-bold-italics"><strong><em>%s</em></strong></span>',
            '<span class="prayer-head-highlight">%s</span>',
        ];
        if ($classified_title) {
            $random_class = $headline_styles[array_rand($headline_styles)];
            echo sprintf($random_class, esc_html($classified_title));
        }
        // --- PATCHED HEADER BLOCK ---
        echo "<div class='prayer-tile-header'>";
        if ($status === 'Answered!') {
            echo "<span class='prayer-tag prayer-status-answered'>ANSWERED!</span>";
            if ($assign_id > 0 && $assign_name !== 'Unassigned') {
                echo " <span class='prayer-tag prayer-assignment'>$assign_name</span>";
            }
        } else {
            echo "<span class='prayer-tag prayer-status-unanswered'>UNANSWERED</span>";
            if ($assign_id > 0 && $assign_name !== 'Unassigned') {
                echo " <span class='prayer-tag prayer-assignment'>ANSWERING: $assign_name</span>";
            } else {
                echo " <span class='prayer-tag prayer-assignment'>UNASSIGNED</span>";
            }
        }
        echo "</div>";
        // --- END PATCHED HEADER BLOCK ---
        echo "<div class='prayer-meta-block'>";
        echo "<div><strong>Request:</strong> $basic</div>";
        echo "<div><strong>Urgency:</strong> $urg</div>";
        echo "<div><strong>By:</strong> $name</div>";
        echo "</div>";
        echo "</div>";
    }
    echo '</div>';
    // Add modal HTML
    ?>
    <div id="prayer-modal" class="prayer-modal" style="display:none;">
        <div class="prayer-modal-content">
            <span class="prayer-modal-close">&times;</span>
            <div id="prayer-modal-body"></div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.prayer-tile').forEach(function(tile) {
                tile.addEventListener('click', function() {
                    var pid = this.getAttribute('data-id');
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=prayer_modal&id=' + pid)
                        .then(r => r.text())
                        .then(html => {
                            document.getElementById('prayer-modal-body').innerHTML = html;
                            document.getElementById('prayer-modal').style.display = 'block';
                        });
                });
            });
            document.querySelector('.prayer-modal-close').onclick = function() {
                document.getElementById('prayer-modal').style.display = 'none';
            }
            window.onclick = function(event) {
                var modal = document.getElementById('prayer-modal');
                if (event.target == modal) modal.style.display = "none";
            }
        });

        // Global AJAX handler for modal form and delete button (event delegation)
        document.body.addEventListener('submit', function(e) {
            if (e.target && e.target.id === 'prayer-modal-form') {
                e.preventDefault();
                var form = new FormData(e.target);
                form.append('action', 'prayer_modal_update');
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: form
                })
                .then(r => r.text())
                .then(msg => {
                    document.getElementById('prayer-modal').style.display = 'none';
                    location.reload();
                })
                .catch(function(error) {
                    alert("AJAX error: " + error);
                    console.error(error);
                });
            }
        });
        document.body.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'prayer-delete-btn') {
                if (confirm('Are you sure you want to delete this ad?')) {
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'action=prayer_delete&id=' + e.target.dataset.id + '&_wpnonce=<?php echo wp_create_nonce('prayer_delete'); ?>'
                    })
                    .then(r => r.text())
                    .then(msg => {
                        location.reload();
                    })
                    .catch(function(error) {
                        alert("Delete AJAX error: " + error);
                        console.error(error);
                    });
                }
            }
        });
    </script>
    <?php
    return ob_get_clean();
});

// 5. AJAX handler for modal detail + assignment/status select + photo + delete button + classified title
add_action('wp_ajax_prayer_modal', 'prayer_modal_ajax');
add_action('wp_ajax_nopriv_prayer_modal', 'prayer_modal_ajax');
function prayer_modal_ajax() {
    $id = intval($_GET['id']);
    $basic = esc_html(get_post_meta($id, 'prayer_basic', true));
    $when  = esc_html(get_post_meta($id, 'prayer_when', true));
    $urg   = esc_html(get_post_meta($id, 'prayer_urgency', true));
    $name  = esc_html(get_post_meta($id, 'prayer_name', true));
    $status= esc_html(get_post_meta($id, 'prayer_status', true));
    $assign_id_raw = get_post_meta($id, 'prayer_assignment', true);
    $assign_id = (is_numeric($assign_id_raw) && intval($assign_id_raw) > 0) ? intval($assign_id_raw) : 0;
    $assign_name = 'Unassigned';
    if ($assign_id > 0) {
        $assigned_user = get_user_by('id', $assign_id);
        if ($assigned_user && !is_wp_error($assigned_user)) {
            $assign_name = $assigned_user->display_name;
        }
    }
    $full  = esc_html(get_post_meta($id, 'prayer_full', true));
    $contact= esc_html(get_post_meta($id, 'prayer_contact', true));
    $classified_title = get_post_meta($id, 'prayer_classified_title', true);

    $prayer_email = esc_html(get_post_meta($id, 'prayer_email', true));
    $prayer_phone = esc_html(get_post_meta($id, 'prayer_phone', true));

    // Get all users (members) for assignment dropdown
    $users = get_users(['fields'=>['ID', 'display_name']]);
    $assign_opts = [['id' => 0, 'name' => 'Unassigned']];
    foreach ($users as $u) {
        $assign_opts[] = ['id' => $u->ID, 'name' => $u->display_name];
    }

    // Optional large image
    if (has_post_thumbnail($id)) {
        $large = get_the_post_thumbnail($id, 'large', ['class'=>'prayer-modal-img']);
        echo "<div class='prayer-modal-img-wrap'>$large</div>";
    }

    // Random styled classified title
    $headline_styles = [
        '<div class="prayer-head-bold">%s</div>',
        '<div class="prayer-head-italics"><em>%s</em></div>',
        '<div class="prayer-head-bold-italics"><strong><em>%s</em></strong></div>',
        '<div class="prayer-head-highlight">%s</div>',
    ];
    if ($classified_title) {
        $random_class = $headline_styles[array_rand($headline_styles)];
        echo sprintf($random_class, esc_html($classified_title));
    }
    ?>
    <div class="prayer-modal-fields">
        <!-- Classified meta block -->
        <div class="prayer-meta-block" style="margin-bottom:0.16em;">
            <div><strong>Request:</strong> <?php echo $basic; ?></div>
            <div><strong>Urgency:</strong> <?php echo $urg; ?></div>
            <div><strong>By:</strong> <?php echo $name; ?></div>
        </div>
        <div><strong>Date/Timeframe:</strong> <?php echo $when; ?></div>
        <div><strong>Full Request:</strong> <?php echo $full; ?></div>
        <div><strong>Preferred Contact:</strong> <?php echo $contact; ?></div>
        <?php
        if ($contact === 'Email' && $prayer_email) {
            echo "<div><strong>Email Address:</strong> $prayer_email</div>";
        }
        if ($contact === 'Phone' && $prayer_phone) {
            echo "<div><strong>Phone Number:</strong> $prayer_phone</div>";
        }
        ?>
        <form method="post" id="prayer-modal-form">
            <div class="prayer-modal-actions">
                <div class="prayer-modal-fields-row">
                    <label>Assign to:<br>
                        <select name="prayer_assignment">
                            <?php foreach($assign_opts as $opt): ?>
                                <option value="<?php echo esc_attr($opt['id']); ?>" <?php selected($assign_id, intval($opt['id'])); ?>><?php echo esc_html($opt['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Status:<br>
                        <select name="prayer_status">
                            <option value="Unanswered" <?php selected($status, 'Unanswered'); ?>>Unanswered</option>
                            <option value="Answered!" <?php selected($status, 'Answered!'); ?>>Answered!</option>
                        </select>
                    </label>
                </div>
                <div class="prayer-modal-buttons-row">
                    <?php wp_nonce_field('prayer_modal_update','prayer_modal_nonce'); ?>
                    <button type="submit">Update</button>
                    <?php if (current_user_can('delete_post', $id)) : ?>
                        <button type="button" id="prayer-delete-btn" data-id="<?php echo $id; ?>" data-nonce="<?php echo wp_create_nonce('prayer_delete'); ?>">Delete</button>
                    <?php endif; ?>
                </div>
            </div>
            <input type="hidden" name="prayer_modal_id" value="<?php echo $id; ?>">
        </form>
    </div>
    <?php
    die();
}

// 6. Prayer Modal Update Handler (assign/status)
add_action('wp_ajax_prayer_modal_update', function() {
    // Debug: log all POST data for troubleshooting, including request method and user
    error_log('prayer_modal_update AJAX called by user ID: ' . get_current_user_id());
    error_log('prayer_modal_update POST: ' . print_r($_POST, true));

    // Check for required POST fields and nonce
    if (
        isset($_POST['prayer_modal_id']) &&
        isset($_POST['prayer_modal_nonce']) &&
        wp_verify_nonce($_POST['prayer_modal_nonce'], 'prayer_modal_update')
    ) {
        $id = intval($_POST['prayer_modal_id']);
        $assign_value = isset($_POST['prayer_assignment']) ? intval($_POST['prayer_assignment']) : 0;
        $status_value = isset($_POST['prayer_status']) ? sanitize_text_field($_POST['prayer_status']) : 'Unanswered';

        // Log assignment and status values, plus post ID
        error_log("Updating Prayer Request #$id: assign_value=$assign_value, status_value=$status_value");

        // Permission check
        if (current_user_can('edit_post', $id)) {
            // Update meta and log results
            $assign_result = update_post_meta($id, 'prayer_assignment', $assign_value);
            $status_result = update_post_meta($id, 'prayer_status', $status_value);
            error_log("Meta update results for #$id: assignment=$assign_result, status=$status_result");
            echo "Updated!";
        } else {
            error_log("prayer_modal_update: user cannot edit post $id (user ID: " . get_current_user_id() . ")");
            echo "Permission denied.";
        }
    } else {
        error_log("prayer_modal_update: nonce or prayer_modal_id missing/invalid. Data: " . print_r($_POST, true));
        echo "Invalid request.";
    }
    die();
});

// 7. AJAX handler for delete
add_action('wp_ajax_prayer_delete', function() {
    $id = intval($_POST['id']);
    if (current_user_can('delete_post', $id) && check_ajax_referer('prayer_delete', '_wpnonce', false)) {
        wp_delete_post($id, true);
        echo "Deleted";
    }
    exit;
});
?>