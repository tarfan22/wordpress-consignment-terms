<?php
/*
Plugin Name: Consignment Terms - Simple Classic
Description: Simple terms for classic WooCommerce checkout
Version: 6.0
*/

if (!defined('ABSPATH')) { exit; }

// Add terms directly to checkout form
add_action('woocommerce_checkout_before_customer_details', 'consignment_terms_classic_display');

function consignment_terms_classic_display() {
    ?>
    <div class="consignment-terms-classic" style="background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; padding: 25px; margin: 20px 0 30px 0; max-width: 700px;">
        <h4 style="color: #856404; margin-top: 0; margin-bottom: 20px; font-size: 18px; font-weight: 600;">⚠️ Important Purchase Terms</h4>
        <ul style="margin-bottom: 20px; padding-left: 20px;">
            <li style="margin-bottom: 12px; color: #333; line-height: 1.5;"><strong style="color: #856404;">Immediate Shipping:</strong> If your item is in stock, we will ship it to you immediately with insurance.</li>
            <li style="margin-bottom: 12px; color: #333; line-height: 1.5;"><strong style="color: #856404;">No Refunds on Handmade Items:</strong> All handmade items are final sale. No refunds or exchanges.</li>
            <li style="margin-bottom: 12px; color: #333; line-height: 1.5;"><strong style="color: #856404;">Delivery Time:</strong> Please allow 2 to 3 weeks for delivery.</li>
        </ul>

        <div class="consignment-checkbox-classic" style="margin: 20px 0;">
            <label for="consignment_terms_agree" style="font-weight: 600; display: flex; align-items: center; cursor: pointer;">
                <input type="checkbox" id="consignment_terms_agree" name="consignment_terms_agree" value="1" required style="width: 20px; height: 20px; margin-right: 10px; cursor: pointer;">
                I agree to these purchase terms
            </label>
        </div>

        <div class="consignment-initials-classic" style="margin: 20px 0;">
            <label for="consignment_initials" style="font-weight: 600; display: block; margin-bottom: 8px; color: #333;">Please Initial to Confirm:</label>
            <input type="text" id="consignment_initials" name="consignment_initials" placeholder="Your initials (e.g., JD)" maxlength="10" required style="width: 100%; max-width: 250px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
            <small style="display: block; margin-top: 5px; color: #666; font-style: italic;">By entering your initials, you agree to all terms above.</small>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkoutForm = document.querySelector('form.woocommerce-checkout, form.checkout');
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', function(e) {
                const termsCheckbox = document.getElementById('consignment_terms_agree');
                const initialsInput = document.getElementById('consignment_initials');

                if (termsCheckbox && !termsCheckbox.checked) {
                    e.preventDefault();
                    alert('You must agree to the purchase terms to continue.');
                    termsCheckbox.focus();
                    return false;
                }

                if (initialsInput && !initialsInput.value.trim()) {
                    e.preventDefault();
                    alert('Please enter your initials to confirm agreement to the terms.');
                    initialsInput.focus();
                    return false;
                }
            });
        }
    });
    </script>
    <?php
}

// Validate terms
add_action('woocommerce_checkout_process', 'consignment_terms_classic_validate');

function consignment_terms_classic_validate() {
    if (!isset($_POST['consignment_terms_agree']) || $_POST['consignment_terms_agree'] !== '1') {
        wc_add_notice('You must agree to the purchase terms to continue.', 'error');
    }
    if (!isset($_POST['consignment_initials']) || empty(trim($_POST['consignment_initials']))) {
        wc_add_notice('Please enter your initials to confirm agreement to the terms.', 'error');
    }
}

// Save terms
add_action('woocommerce_checkout_update_order_meta', 'consignment_terms_classic_save');

function consignment_terms_classic_save($order_id) {
    if (isset($_POST['consignment_terms_agree']) && $_POST['consignment_terms_agree'] === '1') {
        update_post_meta($order_id, '_consignment_terms_agreed', '1');
        update_post_meta($order_id, '_consignment_terms_date', current_time('mysql'));
    }
    if (isset($_POST['consignment_initials'])) {
        update_post_meta($order_id, '_consignment_terms_initials', sanitize_text_field($_POST['consignment_initials']));
        update_post_meta($order_id, '_consignment_terms_ip', $_SERVER['REMOTE_ADDR'] ?? '');
    }
}

// Display in admin
add_action('woocommerce_admin_order_data_after_billing_address', 'consignment_terms_classic_admin');

function consignment_terms_classic_admin($order) {
    $agreed = get_post_meta($order->get_id(), '_consignment_terms_agreed', true);
    $initials = get_post_meta($order->get_id(), '_consignment_terms_initials', true);
    $date = get_post_meta($order->get_id(), '_consignment_terms_date', true);

    if ($agreed === '1') {
        echo '<div style="background:#d4edda;padding:15px;border-radius:5px;border-left:4px solid #28a745;margin:15px 0">';
        echo '<strong style="color:#155724">Purchase Terms Agreement</strong><br>';
        echo '<span style="color:#155724">✓ Customer agreed to purchase terms</span><br>';
        if ($initials) echo '<span style="color:#155724">✓ Initials: ' . esc_html($initials) . '</span><br>';
        if ($date) echo '<span style="color:#155724;font-size:12px">Agreed: ' . esc_html($date) . '</span>';
        echo '</div>';
    }
}

// Display in emails
add_action('woocommerce_email_order_details', 'consignment_terms_classic_email', 10, 4);

function consignment_terms_classic_email($order, $sent_to_admin, $plain_text, $email) {
    $agreed = get_post_meta($order->get_id(), '_consignment_terms_agreed', true);
    $initials = get_post_meta($order->get_id(), '_consignment_terms_initials', true);

    if ($agreed === '1') {
        if ($plain_text) {
            echo "\n\n=== PURCHASE TERMS ===\n";
            echo "✓ Customer agreed to purchase terms\n";
            if ($initials) echo "✓ Initials: " . $initials . "\n";
        } else {
            echo '<div style="margin:20px 0;padding:15px;background:#f9f9f9;border-left:4px solid #28a745">';
            echo '<h4 style="color:#155724;margin:0 0 10px">Purchase Terms Agreement</h4>';
            echo '<p style="color:#155724;margin:5px 0">✓ Customer agreed to purchase terms</p>';
            if ($initials) echo '<p style="color:#155724;margin:5px 0">✓ Initials: ' . esc_html($initials) . '</p>';
            echo '</div>';
        }
    }
}
