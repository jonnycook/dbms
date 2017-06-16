<?php $subject = 'Need Your Confirmation' ?>

Hi <?php echo $name ?>!
 
Welcome to divvyDOSE, you lucky duck.  We are excited that you're interested in choosing us as your pharmacy.
 
Let us take the worry out of the everyday task of managing medications, either for yourself or for a loved one.

<?php if ($type == 'html'): ?>
<a href="<?php echo $url ?>">Click here</a> to confirm your email and finish the sign-up process.  We won't let you down.
<?php else: ?>
Visit this URL: <?php echo $url ?> to confirm your email and finish the sign-up process.  We won't let you down.
<?php endif ?>

Our best,
The divvyDOSE Team