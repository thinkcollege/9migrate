<?php
print drupal_render($form['title']);

//print "<h2>" . $form['field_idd_reporting_period']['und'][0]['value'] . "</h2>";
//print drupal_render($form['additional_settings']);
//print drupal_render($form['actions']);

print drupal_render_children($form);
print '<div id="idd_popup" class="well popup_content"><div id="popupText">

</div>

    <button class="basic_close btn btn-default">Close</button>

  </div>

';
