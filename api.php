<?php

require('./sys.php');
ob_start();

header('Content-Type: application/json');

// Get the JSON contents
$json = file_get_contents('php://input');

// decode the json data
$data = json_decode($json);

switch ($data->action) {
	case "initGame":
		$play = new Game();
		$play->startNewGame();
		$play->save();
		ob_end_clean();
		echo json_encode( [ 'data' => Game::FILENAME ] );	// change later
		break;
	case "registerPlayer":
		$play = new Game();
		$play = Game::load(Game::FILENAME);
		$pid = $play->assignPlayer($data->name);
		$play->setActivePlayer($pid);		// change later !!
		$play->save();
		ob_end_clean();
		echo json_encode( [ 'data' => $pid ] );
		break;
	case "getHand":
		$play = new Game();
		$play = Game::load(Game::FILENAME);
		$hand = $play->getPlayerCopy($data->playerId)->getHand()->getCards();
		ob_end_clean();
		echo json_encode( [ 'data' => $hand ] );
		break;

}

//var_dump($data);

