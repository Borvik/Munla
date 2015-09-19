<?php
log::debug($_POST);
//form testing....
$form->setEchoOff(true);
echo $form->open(array('default', 'form'), array('novalidate' => 'novalidate'));
$errors = $form->getErrors();
if( $errors )
    echo '<div style="color: red;">'.implode('<br />', array_map(array('get', 'entities'), $errors)).'</div>';
$name = 'dtWeek';
$v = isset($_POST[$name]) ? $_POST[$name] : '';
echo $form->week($name, $v, 'autofocus', 'required', array('datemode' => 'us', 'max' => '6/13/2013'));
echo '<br />'.$form->submit('cmdSubmit', 'Sign In');
echo $form->close();
$test = array('fe_text', 'fe_textarea', 'fe_date', 'fe_datetime', 'fe_datetimelocal', 'fe_month', 'fe_time', 'fe_number', 'fe_week');
$table = array(); $maxRows = 0;
foreach($test as $class){
    $table[$class] = $class::acceptedAttributes();
    $maxRows = max($maxRows, count($table[$class]));
}
echo '<table><tr>';
foreach($test as $class)
    printf('<th>%s</th>', $class);
echo '</tr>';
for($i = 0; $i < $maxRows; $i++){
    echo '<tr>';
    foreach($test as $class)
        printf('<td>%s</td>', (isset($table[$class][$i]) ? $table[$class][$i] : '&nbsp;'));
    echo '</tr>';
}
echo '</table>';
log::displayDebug();