<?php

require('./sys.php');
header('Content-Type: application/json');
ob_start();

// Get the JSON contents
$json = file_get_contents('php://input');

// decode the json data
$data = json_decode($json);

switch ($data->action) {
	case "initGame":
		unlink(Game::FILENAME); // should not be necessary
		$play = new Game();
		$play->startNewGame();
		$play->save();
		echo json_encode( [ 'data' => $play->getDeck()->getCards(), 'dbg' => ob_get_clean() ] );
		break;
	case "registerPlayer":
		$play = new Game();
		$play = Game::load(Game::FILENAME);
		$pid = $play->assignPlayer($data->name);
		$play->setActivePlayer($pid);		// change later !!
		$play->save();
		echo json_encode( [ 'data' => $pid, 'dbg' => ob_get_clean() ] );
		break;
	case "getHand":
		$play = new Game();
		$play = Game::load(Game::FILENAME);
		$hand = $play->getPlayerCopy($data->playerId)->getHand()->getCardIds();
		echo json_encode( [ 'data' => $hand, 'dbg' => ob_get_clean() ] );
		break;
	case "getTable":
		$play = new Game();
		$play = Game::load(Game::FILENAME);
		$table = $play->getTable()->getGroupsAsArray();
		echo json_encode( [ 'data' => $table, 'dbg' => ob_get_clean() ] );
		break;
	case "getCard":
		$play = new Game();
		$play = Game::load(Game::FILENAME);
		if ($play->doTurnAsGetCard($data->playerId)) {
			$hand = $play->getPlayerCopy($data->playerId)->getHand()->getCardIds();
			$play->save();
			echo json_encode( [ 'data' => $hand, 'dbg' => ob_get_clean() ] );
		} else {
			http_response_code(403);
			// many different errors should happen !!
			echo json_encode( [ 'message' => 'Player is not allowed to get card.', 'dbg' => ob_get_clean() ] ); 
		}
		break;
	case "validateGroup":
		$newSet = new Cards();
		foreach ($data->cards as $c) {
			$newSet->pushCard(new Card($c->value, $c->type, $c->id));
		}
		if (Group::validate($newSet)) {
			echo json_encode( [ 'data' => true, 'dbg' => ob_get_clean() ] );
		} else {
			http_response_code(403);
			echo json_encode( [ 'message' => 'Card set is invalid', 'dbg' => ob_get_clean() ] );
		}
		break;
	case "doTableChange":
		$hand = new Cards();
		foreach ($data->hand as $c) {
			$hand->pushCard(new Card($c->value, $c->type, $c->id));
		}
		$table = new Table();
		foreach ($data->table as $grp) {
			$newSet = new Cards();
			foreach ($grp as $c) {
				$newSet->pushCard(new Card($c->value, $c->type, $c->id));
			}
			$table[] = Group::createSet($newSet);
		}
		$play = new Game();
		$play = Game::load(Game::FILENAME);
		if ($play->doTurnAsTableChange($data->playerId, $table, $hand)) {
			$play->save();			
			echo json_encode( [ 'data' => true, 'dbg' => ob_get_clean() ] );
		} else {
			http_response_code(403);
			echo json_encode( [ 'message' => 'Table or player hand is invalid', 'dbg' => ob_get_clean() ] );
		}
		break;
}

