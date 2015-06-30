<?php
if(!defined('OSTSCPINC') || !$thisstaff) die('Access Denied');

$qs = array();

$users = User::objects()
    ->annotate(array('ticket_count'=>SqlAggregate::COUNT('tickets')));

if ($_REQUEST['query']) {
    $search = $_REQUEST['query'];
    $users->filter(Q::any(array(
        'emails__address__contains' => $search,
        'name__contains' => $search,
        'org__name__contains' => $search,
        // TODO: Add search for cdata
    )));
    $qs += array('query' => $_REQUEST['query']);
}

$sortOptions = array('name' => 'name',
                     'email' => 'emails__address',
                     'status' => 'account__status',
                     'create' => 'created',
                     'update' => 'updated');
$orderWays = array('DESC'=>'-','ASC'=>'');
$sort= ($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])]) ? strtolower($_REQUEST['sort']) : 'name';
//Sorting options...
if ($sort && $sortOptions[$sort])
    $order_column =$sortOptions[$sort];

$order_column = $order_column ?: 'name';

if ($_REQUEST['order'] && $orderWays[strtoupper($_REQUEST['order'])])
    $order = $orderWays[strtoupper($_REQUEST['order'])];

if ($order_column && strpos($order_column,','))
    $order_column = str_replace(','," $order,",$order_column);

$x=$sort.'_sort';
$$x=' class="'.($order == '' ? 'asc' : 'desc').'" ';

$total = $users->count();
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total,$page,PAGE_LIMIT);
$pageNav->paginate($users);

$qstr = '&amp;'. Http::build_query($qs);
$qs += array('sort' => $_REQUEST['sort'], 'order' => $_REQUEST['order']);
$pageNav->setURL('users.php', $qs);
$qstr.='&amp;order='.($order=='-' ? 'ASC' : 'DESC');

//echo $query;
$_SESSION[':Q:users'] = clone $users;

$users->values('id', 'name', 'default_email__address', 'account__id',
    'account__status', 'created', 'updated');
$users->order_by($order . $order_column);
?>
<h2><?php echo __('User Directory'); ?></h2>
<div class="pull-left">
    <form action="users.php" method="get">
        <?php csrf_token(); ?>
        <input type="hidden" name="a" value="search">
        <table>
            <tr>
                <td><input type="search" id="basic-user-search" name="query"
                    autofocus size="30" value="<?php echo Format::htmlchars($_REQUEST['query']); ?>"
                    autocomplete="off" autocorrect="off" autocapitalize="off"></td>
                <td><input type="submit" name="basic_search" class="button" value="<?php echo __('Search'); ?>"></td>
                <!-- <td>&nbsp;&nbsp;<a href="" id="advanced-user-search">[advanced]</a></td> -->
            </tr>
        </table>
    </form>
 </div>

<div class="pull-right">
<?php if ($thisstaff->hasPerm(User::PERM_CREATE)) { ?>
    <a class="action-button popup-dialog"
        href="#users/add">
        <i class="icon-plus-sign"></i>
        <?php echo __('Add User'); ?>
    </a>
    <a class="action-button popup-dialog"
        href="#users/import">
        <i class="icon-upload"></i>
        <?php echo __('Import'); ?>
    </a>
<?php } ?>
    <span class="action-button" data-dropdown="#action-dropdown-more"
        style="/*DELME*/ vertical-align:top; margin-bottom:0">
        <i class="icon-caret-down pull-right"></i>
        <span ><i class="icon-cog"></i> <?php echo __('More');?></span>
    </span>
    <div id="action-dropdown-more" class="action-dropdown anchor-right">
        <ul>
<?php if ($thisstaff->hasPerm(User::PERM_DELETE)) { ?>
            <li><a class="users-action" href="#delete">
                <i class="icon-trash icon-fixed-width"></i>
                <?php echo __('Delete'); ?></a></li>
<?php }
if ($thisstaff->hasPerm(User::PERM_EDIT)) { ?>
            <li><a href="#orgs/lookup/form" onclick="javascript:
$.dialog('ajax.php/orgs/lookup/form', 201);
return false;">
                <i class="icon-group icon-fixed-width"></i>
                <?php echo __('Add to Organization'); ?></a></li>
<?php
}
if ('disabled' != $cfg->getClientRegistrationMode()) { ?>
            <li><a class="users-action" href="#reset">
                <i class="icon-envelope icon-fixed-width"></i>
                <?php echo __('Send Password Reset Email'); ?></a></li>
<?php if ($thisstaff->hasPerm(User::PERM_MANAGE)) { ?>
            <li><a class="users-action" href="#register">
                <i class="icon-smile icon-fixed-width"></i>
                <?php echo __('Register'); ?></a></li>
            <li><a class="users-action" href="#lock">
                <i class="icon-lock icon-fixed-width"></i>
                <?php echo __('Lock'); ?></a></li>
            <li><a class="users-action" href="#unlock">
                <i class="icon-unlock icon-fixed-width"></i>
                <?php echo __('Unlock'); ?></a></li>
<?php }
} # end of registration-enabled? ?>
        </ul>
    </div>
</div>

<div class="clear"></div>
<?php
$showing = $search ? __('Search Results').': ' : '';
if($users->exists(true))
    $showing .= $pageNav->showing();
else
    $showing .= __('No users found!');
?>
<form id="users-list" action="users.php" method="POST" name="staff" >
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
 <input type="hidden" id="action" name="a" value="" >
 <input type="hidden" id="selected-count" name="count" value="" >
 <input type="hidden" id="org_id" name="org_id" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th nowrap width="12"> </th>
            <th width="350"><a <?php echo $name_sort; ?> href="users.php?<?php
                echo $qstr; ?>&sort=name"><?php echo __('Name'); ?></a></th>
            <th width="250"><a  <?php echo $status_sort; ?> href="users.php?<?php
                echo $qstr; ?>&sort=status"><?php echo __('Status'); ?></a></th>
            <th width="100"><a <?php echo $create_sort; ?> href="users.php?<?php
                echo $qstr; ?>&sort=create"><?php echo __('Created'); ?></a></th>
            <th width="145"><a <?php echo $update_sort; ?> href="users.php?<?php
                echo $qstr; ?>&sort=update"><?php echo __('Updated'); ?></a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        foreach ($users as $U) {
                // Default to email address mailbox if no name specified
                if (!$U['name'])
                    list($name) = explode('@', $U['default_email__address']);
                else
                    $name = new UsersName($U['name']);

                // Account status
                if ($U['account__id'])
                    $status = new UserAccountStatus($U['account__status']);
                else
                    $status = __('Guest');

                $sel=false;
                if($ids && in_array($U['id'], $ids))
                    $sel=true;
                ?>
               <tr id="<?php echo $U['id']; ?>">
                <td nowrap>
                    <input type="checkbox" value="<?php echo $U['id']; ?>" class="ckb mass nowarn"/>
                </td>
                <td>&nbsp;
                    <a class="preview"
                        href="users.php?id=<?php echo $U['id']; ?>"
                        data-preview="#users/<?php echo $U['id']; ?>/preview"><?php
                        echo Format::htmlchars($name); ?></a>
                    &nbsp;
                    <?php
                    if ($U['ticket_count'])
                         echo sprintf('<i class="icon-fixed-width icon-file-text-alt"></i>
                             <small>(%d)</small>', $U['ticket_count']);
                    ?>
                </td>
                <td><?php echo $status; ?></td>
                <td><?php echo Format::date($U['created']); ?></td>
                <td><?php echo Format::datetime($U['updated']); ?>&nbsp;</td>
               </tr>
<?php   } //end of foreach. ?>
    </tbody>
    <tfoot>
     <tr>
        <td colspan="7">
            <?php if ($res && $num) { ?>
            <?php echo __('Select');?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
            <?php }else{
                echo '<i>';
                echo __('Query returned 0 results.');
                echo '</i>';
            } ?>
        </td>
     </tr>
    </tfoot>
</table>
<?php
if ($total) {
    echo sprintf('<div>&nbsp;'.__('Page').': %s &nbsp; <a class="no-pjax"
            href="users.php?a=export&qh=%s">'.__('Export').'</a></div>',
            $pageNav->getPageLinks(),
            $qhash);
}
?>
</form>

<script type="text/javascript">
$(function() {
    $('input#basic-user-search').typeahead({
        source: function (typeahead, query) {
            $.ajax({
                url: "ajax.php/users/local?q="+query,
                dataType: 'json',
                success: function (data) {
                    typeahead.process(data);
                }
            });
        },
        onselect: function (obj) {
            window.location.href = 'users.php?id='+obj.id;
        },
        property: "/bin/true"
    });

    $(document).on('click', 'a.popup-dialog', function(e) {
        e.preventDefault();
        $.userLookup('ajax.php/' + $(this).attr('href').substr(1), function (user) {
            var url = window.location.href;
            if (user && user.id)
                url = 'users.php?id='+user.id;
            $.pjax({url: url, container: '#pjax-container'})
            return false;
         });

        return false;
    });
    var goBaby = function(action, confirmed) {
        var ids = [],
            $form = $('form#users-list');
        $(':checkbox.mass:checked', $form).each(function() {
            ids.push($(this).val());
        });
        if (ids.length) {
          var submit = function(data) {
            $form.find('#action').val(action);
            $.each(ids, function() { $form.append($('<input type="hidden" name="ids[]">').val(this)); });
            if (data)
              $.each(data, function() { $form.append($('<input type="hidden">').attr('name', this.name).val(this.value)); });
            $form.find('#selected-count').val(ids.length);
            $form.submit();
          };
          var options = {};
          if (action === 'delete')
              options['deletetickets']
                =  __('Also delete all associated tickets and attachments');
          if (!confirmed)
              $.confirm(__('You sure?'), undefined, options).then(submit);
          else
              submit();
        }
        else {
            $.sysAlert(__('Oops'),
                __('You need to select at least one item'));
        }
    };
    $(document).on('click', 'a.users-action', function(e) {
        e.preventDefault();
        goBaby($(this).attr('href').substr(1));
        return false;
    });
    $(document).on('dialog:close', function(e, json) {
        $form = $('form#users-list');
        try {
            var json = $.parseJSON(json),
                org_id = $form.find('#org_id');
            if (json.id) {
                org_id.val(json.id);
                goBaby('setorg', true);
            }
        }
        catch (e) { }
    });
});
</script>

