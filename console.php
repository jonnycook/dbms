<script type="text/javascript" src="http://code.jquery.com/jquery-2.1.4.min.js"></script>
<form>
  <input type="text" name="db" placeholder="DB">
  <input type="text" name="clientId" placeholder="Client ID">
  <textarea name="update" placeholder="Update"></textarea>
  <input type="submit">
</form>

<script type="text/javascript">
    $('form').submit(function() {
      var update = JSON.parse($('[name=update]').val());

      $.post('core/main.php', {update:JSON.stringify({id:new Date().getTime(), data:update}), clientId:$('[name=clientId]').val(), db:$('[name=db]').val()});
      return false;
    });
</script>
