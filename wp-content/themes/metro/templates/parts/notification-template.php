<?php
$show = MMNotification::$show;
$params = MMNotification::$params;
$class_name = (array_key_exists('status', $params) && !empty($params['status'])) ? 'notification ' . $params['status'] : 'notification';
?>

<?php if ($show) : ?>
    <div data-target="global_notification" class="<?php echo $class_name ?>">
        <div class="container">
            <div class="content">
                <div class="icon">
                </div>
                <div class="text">
                    <?php if (array_key_exists('title', $params) && !empty($params['title'])) : ?>
                        <h3><?php echo $params['title'] ?></h3>
                    <?php endif; ?>
                    <?php if (array_key_exists('message', $params) && !empty($params['message'])) : ?>
                        <p><?php echo $params['message'] ?></p>
                    <?php endif; ?>
                </div>
                <form method="post" action="">
                    <input type="hidden" name="clear_notification" value="1">
                    <button class="hide-button" type="submit">Close</button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>