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
	case "getGameCards":
		$play = Game::load(Game::FILENAME);
		echo json_encode( [ 'data' => $play->getDeck()->getCards(), 'dbg' => ob_get_clean() ] );
		break;
	case "registerPlayer":
		$play = Game::load(Game::FILENAME);
		$pid = $play->assignPlayer($data->name);
		if ($pid) {
			$play->save();
			echo json_encode( [ 'data' => $pid, 'dbg' => ob_get_clean() ] );
		} else {
			http_response_code(403);
			echo json_encode( [ 'message' => 'Player with similar name is already registered', 'dbg' => ob_get_clean() ] ); 
		}
		break;
	case "setActivePlayer": // and start game
		$play = Game::load(Game::FILENAME);
		if ($play->getActivePlayerId() === '') {
			$play->givePlayersFirstHands(); // give each some cards
			$play->setActivePlayer($data->playerId);
			$play->status = 'playing';
			$play->save();
			echo json_encode( [ 'data' => true, 'dbg' => ob_get_clean() ] );
		} else {
			http_response_code(403);
			echo json_encode( [ 'message' => 'Active player was already set.', 'dbg' => ob_get_clean() ] ); 		
		}
		break;
	case "getGameInfo":
		$changedAt = filemtime(Game::FILENAME); // unit timestamp
		if ($data->knownStateFrom < $changedAt) { // performance optimization for very often requests
			$play = Game::load(Game::FILENAME);
			echo json_encode( [ 'data' => [ 'players' => $play->getPlayersInfo(), 'gameStatus' => $play->status , 
				'amIActivePlayer' => ($play->getActivePlayerId() && $play->getActivePlayerId() === $data->playerId),
				'lastModifiedAt' => $changedAt ], 'dbg' => ob_get_clean() ] );
		} else {
			http_response_code(304);
		}
		break;
	case "getHand":
		$play = Game::load(Game::FILENAME);
		$hand = $play->getPlayerCopy($data->playerId)->getHand()->getCardIds();
		echo json_encode( [ 'data' => $hand, 'dbg' => ob_get_clean() ] );
		break;
	case "getTable":
		$play = Game::load(Game::FILENAME);
		$table = $play->getTable()->getGroupsAsArray();
		echo json_encode( [ 'data' => $table, 'dbg' => ob_get_clean() ] );
		break;
	case "getCard":
		$play = Game::load(Game::FILENAME);
		if ($play->getActivePlayerId() === $data->playerId && $play->doTurnAsGetCard($data->playerId)) {
			//$hand = $play->getPlayerCopy($data->playerId)->getHand()->getCardIds();
			$play->save();
			echo json_encode( [ 'data' => true, 'dbg' => ob_get_clean() ] );
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
			$play->save(); // won = player won the game
			echo json_encode( [ 'data' => ($play->status === 'finished') ? 'won' : 'done', 'dbg' => ob_get_clean() ] );
		} else {
			http_response_code(403);
			echo json_encode( [ 'message' => 'Table or player hand is invalid', 'dbg' => ob_get_clean() ] );
		}
		break;
}

