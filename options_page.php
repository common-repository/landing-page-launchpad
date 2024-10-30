<?php
require_once(ABSPATH . 'wp-includes/option.php');
?>
<style>
    .clear_fix {
        clear:both;
    }

</style>

<div>

    <?php
    global $wpdb;

    $myarray = array();

    if (isset($_POST['lplToken'])) {
        $retrieved_nonce = $_REQUEST['_wpnonce'];
        if (!wp_verify_nonce($retrieved_nonce, 'save_lplp_settings'))
            die('Failed security check');
        $lplToken = (isset($_POST['lplToken'])) ? sanitize_text_field($_POST['lplToken']) : '';
        update_option('lplToken', $lplToken);
        ?>
        <script>document.location.reload();</script>
        <?php
    } else {
        $lplToken = get_option('lplToken');
    }
    ?>

    <!-- action="" id="ag_settings"-->
<?php $nonceUrl = wp_nonce_url(str_replace('%7E', '~', $_SERVER['REQUEST_URI']), 'save_lplp_settings'); ?>
    <form method="post" name="coupon_hidden" action="<?php echo $nonceUrl; ?>">
        <input type="hidden" name="action" value="save_ag_settings" />
        <input type="hidden" name="coupon_hidden" value="Y">

        <div class="display_set">
            <h3>LPL Settings</h3>
            <table>

                <tr>
                    <td valign="top">
                        <table id="dcopon_settings" style="font-size:12px; font-family:sans-serif; line-height:30px;">

                            <tr>
                                <td ></td>
                                <td>
                                    <b>How to find LPL Secret Key?</b>
                                    <p>1) Login into your Landingpagelaunchpad.com account</p>
                                    <p>2) Click on profile link and go to "Account Settings" </p>
                                    <p>3) Copy and Paste "Secret Key" here</p>

                                </td>

                            </tr>

                        </table>
                    </td>

                </tr>
                <tr>
                    <td valign="top">
                        <table id="dcopon_settings" style="font-size:12px; font-family:sans-serif; line-height:30px;">

                            <tr>
                                <td style="font-weight:bold">LPL Secret Key:</td>
                                <td>
                                    <input style="font-style:italic;width:800px" type="text" name="lplToken" id="lplToken" value="<?php
                                    if (isset($lplToken)) {
                                        echo $lplToken;
                                    }
                                    ?>"  /> *


                                </td>

                            </tr>

                        </table>
                    </td>

                </tr>
            </table>

        </div>

        <input class="button button-primary" type="submit" value="Save Changes" />
    </form>
</div>
<script>


</script>