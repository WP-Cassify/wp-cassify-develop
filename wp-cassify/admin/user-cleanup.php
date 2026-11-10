<?php
namespace wp_cassify;
defined( 'ABSPATH' ) || exit;

if ( ! self::allowed() || ( is_multisite() && ! is_network_admin() ) ) {
    echo '<p>You do not have permission to use the manual CAS account tools. On multisite, network activation and network user management permissions are required.</p>';
    return;
}
if ( ! self::supported() ) {
    echo '<p>Manual account tools require WordPress 4.9 or later and a single network. Authentication remains available.</p>';
    return;
}
$days = self::configured_days();
wp_enqueue_script( 'wp-cassify-cleanup', plugins_url( '../js/user-cleanup.js', __FILE__ ), array(), WP_CASSIFY_VERSION, true );
wp_localize_script( 'wp-cassify-cleanup', 'wpCassifyCleanup', array( 'url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'wp_cassify_cleanup' ) ) );
?>
<style>
    /* WordPress button styles override the browser default for [hidden]. */
    #wp-cassify-cleanup [hidden] { display: none !important; }
</style>
<div id="wp-cassify-cleanup">
    <p>Manual operations only. No scheduled purge. Administrators, the current operator and accounts with application passwords are protected from deletion. Active browser sessions also protect accounts during inactivity purges.</p>
    <p>Marking changes which accounts may be purged later; it does not authenticate them with CAS. Previous CAS connections cannot be reconstructed. Mark pre-created CAS accounts explicitly before including accounts without a connection date.</p>
    <fieldset id="cassify-cleanup-settings">
        <h3>Mark accounts as CAS</h3>
        <label>Scope <select id="cassify-cleanup-scope"><option value="role">Accounts with a role</option><option value="all">All WordPress accounts (including local accounts)</option></select></label>
        <p><label><input id="cassify-cleanup-all-accounts" type="checkbox" /> I explicitly intend to mark all WordPress accounts, including local accounts, as CAS accounts.</label></p>
        <label>Role slug <input id="cassify-cleanup-role" type="text" placeholder="subscriber" /></label>
        <?php if ( is_multisite() ) : ?>
        <label>Site ID for this role <input id="cassify-cleanup-blog" type="number" min="1" value="<?php echo esc_attr( get_main_site_id() ); ?>" /></label>
        <?php endif; ?>
        <button type="button" class="button" id="cassify-cleanup-mark">Preview CAS marking</button>
        <h3>Purge inactive CAS accounts</h3>
        <div class="notice notice-warning inline"><p><strong>Before any purge, create a complete backup of the database and all site files (including uploads, plugins, themes and configuration), and verify that you can restore it. On multisite, back up the entire network. Deletion is irreversible.</strong></p></div>
        <p>The date measures successful logins, not all activity. Third-party data may be deleted by other plugins. Interrupted operations can retain partial content transfers; recreating a CAS account does not restore its previous data.</p>
        <label>Inactivity (days) <input id="cassify-cleanup-days" type="number" min="1" max="36600" step="1" value="<?php echo esc_attr( $days ); ?>" /></label>
        <p><label><input id="cassify-cleanup-missing" type="checkbox" /> Include CAS accounts without a connection date only after their CAS marking is older than the selected period; accounts with an unknown marking date are kept</label></p>
        <button type="button" class="button" id="cassify-cleanup-purge">Preview purge</button>
        <h3>Delete CAS accounts from a list</h3>
        <p>Only the listed CAS accounts are selected, regardless of login or marking dates. Active browser sessions do not prevent this deletion. Local accounts and protected accounts are excluded. WordPress accounts are deleted; identities on the CAS server are not deleted.</p>
        <p><label>Identifier type <select id="cassify-cleanup-identifier-type"><option value="login">WordPress username</option><option value="id">WordPress numeric ID</option></select></label></p>
        <p><label for="cassify-cleanup-identifiers">Identifiers (one per line, up to 1000)</label><br /><textarea id="cassify-cleanup-identifiers" rows="8" cols="50" spellcheck="false" autocomplete="off"></textarea></p>
        <button type="button" class="button" id="cassify-cleanup-list">Preview listed accounts</button>
    </fieldset>
    <p id="cassify-cleanup-status" role="status" aria-live="polite"></p>
    <p id="cassify-cleanup-summary"></p>
    <p id="cassify-cleanup-warning"></p>
    <ul id="cassify-cleanup-input-report"></ul>
    <ul id="cassify-cleanup-errors"></ul>
    <ul id="cassify-cleanup-users"></ul>
    <button type="button" class="button" id="cassify-cleanup-prev" hidden>Previous page</button>
    <button type="button" class="button" id="cassify-cleanup-next" hidden>Next page</button>
    <fieldset id="cassify-cleanup-recipient" hidden>
        <label>Reassign content to username <input id="cassify-cleanup-target" type="text" autocomplete="off" spellcheck="false" /></label>
        <p>The recipient must be outside the purge preview. Posts, attachments, custom post types and links are reassigned; comments are retained.</p>
    </fieldset>
    <p id="cassify-cleanup-backup-row" hidden><label><input type="checkbox" id="cassify-cleanup-backup" /> I have created a complete backup of the database and all site files and verified that it can be restored.</label></p>
    <button type="button" class="button button-primary" id="cassify-cleanup-confirm" hidden>Confirm operation</button>
    <button type="button" class="button" id="cassify-cleanup-resume" hidden>Resume operation</button>
    <button type="button" class="button" id="cassify-cleanup-discard">Clear current task</button>
    <p>Clearing a task keeps its results and does not undo completed changes.</p>
    <h3>Task results</h3>
    <ul id="cassify-cleanup-history"></ul>
    <button type="button" class="button" id="cassify-cleanup-history-first" hidden>Latest results</button>
    <button type="button" class="button" id="cassify-cleanup-history-next" hidden>Older results</button>
    <p>The database lock only serializes Cassify requests. Pause imports and other writers while purging. Direct SQL and third-party deletion hooks can have effects outside these safeguards.</p>
    <p>Keep this page open while processing. After an interruption, reopen it and click Resume. A deleted CAS account may be recreated on its next CAS login if automatic account creation is enabled.</p>
</div>
<?php
