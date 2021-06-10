<?php

require('./sys.php');

//phpinfo(); die;


echo "creating game\n";
$play = new Game('abcd');
$play->startNewGame();

//$play = Game::load(Game::FILENAME); dbg('whole game', $play); die('...loaded...');

/*
$play->startNewGame();
$pid = $play->assignPlayer('marek');
$play->setActivePlayer($pid);

//var_dump($play->getPlayerCopy($pid)->getHand()); die("ughhh");

*/

//predelat to na ukladani ID konkretnich karet z Decku, ktere pripadaji v uvahu pro nahradu, bude to pak asi jednodussi !!

//new Cards(new Card(1, Card::SPADES), new Card(2, Card::SPADES), new Card(3, Card::SPADES));

$newSet = Group::createSet(
	new Cards(new Card(13, Card::SPADES), new Card(0, Card::WILD), new Card(11, Card::SPADES)), 
	'dummy' );

$newSet = Group::createSet(
	new Cards( new Card(1, Card::CLUBS), new Card(0, Card::WILD), new Card(12, Card::CLUBS)), 
	'aaaa' );

/*
$newSet = Group::createSet(
	new Cards(new Card(1, Card::SPADES), new Card(0, Card::WILD), new Card(3, Card::SPADES)), 
	'dummy' );

$newSet = Group::createSet(
	new Cards(new Card(0, Card::WILD), new Card(1, Card::SPADES), new Card(0, Card::WILD), new Card(3, Card::SPADES)), 
	'dummy' );

$newSet = Group::createSet(
	new Cards(new Card(0, Card::WILD), new Card(1, Card::SPADES), new Card(0, Card::WILD), new Card(3, Card::SPADES)), 
	'dummy' );

$newSet = Group::createSet(
	new Cards(new Card(12, Card::SPADES), new Card(0, Card::WILD), new Card(1, Card::SPADES)), 
	'dummy' );

$newSet = Group::createSet(
	new Cards(new Card(12, Card::SPADES), new Card(0, Card::WILD), new Card(12, Card::HEARTS)), 
	'dummy' );
*/


//$newSet->fillJokerReplacements($play->getDeck());
var_dump($dbgBuffer, $newSet); die();

//Group::tests(); 
die($dbgBuffer);

// make very big hand
for ($i = 0; $i < 103; $i++) {
	$play->doTurnAsGetCard($pid);
	$plr = $play->getPlayerCopy($pid);
	var_dump(sizeOf($plr->getHand()));
}

$tbl = $play->getCurrentTableCopy();
$plr = $play->getPlayerCopy($pid);

$newSet = Group::createSet( 
	new Cards( $plr->getCardByType(Card::SPADES, 1), $plr->getCardByType(Card::SPADES, 2), $plr->getCardByType(Card::SPADES, 3) ) );
$tbl[] = $newSet;
//var_dump($play->getCurrentTable(), $tbl); die;
$newHand = $plr->getHand()->getCardDiff($newSet);
//var_dump($newHand, new Cards(...$newHand)); die;
/*
var_dump(Group::validate(new Cards( new Card(1, Card::SPADES), new Card(2, Card::SPADES), new Card(3, Card::SPADES) )));
var_dump(Group::validate(

Group::createSet( 
	new Cards( new Card(1, Card::SPADES), new Card(2, Card::SPADES), new Card(3, Card::SPADES) ) )

	 ));
*/
echo "doing turn with table change\n\n\n";
var_dump($play->doTurnAsTableChange($pid, $tbl, new Cards(...$newHand)));

$play->save();


$tbl = $play->getCurrentTableCopy();
$plr = $play->getPlayerCopy($pid);

$newSet = Group::createSet( 
	new Cards( $plr->getCardByType(Card::CLUBS, 1), $plr->getCardByType(Card::CLUBS, 2), $plr->getCardByType(Card::CLUBS, 3) ) );
$tbl[] = $newSet;
$newHand = $plr->getHand()->getCardDiff($newSet);

echo "doing 2nd turn with table change\n\n\n";
var_dump($play->doTurnAsTableChange($pid, $tbl, new Cards(...$newHand)));

$play->save();





//dbg('deck', $play->getDeck());
dbg('whole game', $play);

//$play->save();

# validace a datatypy nastudovat   https://www.php.net/manual/en/language.oop5.decon.php

echo "\n\n\n";

//Group::tests();


echo "...end... \n";
