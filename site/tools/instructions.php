<?php

/**
 *	print instructions
 **********************************************/

/* required functions */
require_once('../../functions/functions.php');

/* fetch instructions and print them in instructions div */
$instructions = fetchInstructions();
$instructions = $instructions[0]['instructions'];

/* format line breaks */
$instructions = str_replace("\n\r", "<br>", $instructions);

?>

<h3>Instructions for managing IP addresses</h3>

<div class="normalTable instructions">
<?php print $instructions; ?>
</div>