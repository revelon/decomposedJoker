<?php
error_reporting(-1);
ini_set('html_errors', 0);
$dbgBuffer = '';
require('./sys.php');
define('REQID', bin2hex(random_bytes(2)));
mylog('API start');
header('Content-Type: application/json');

// Get the JSON contents
$json = file_get_contents('php://input');

// decode the json data
$data = json_decode($json);

switch ($data->action) {
	case "initGame":
		mylog('CASE initGame start');
		$id = '';
		if ($data->gameId) { // play again case, with existing ID
			$play = Game::load($data->gameId);
			dbg('game status', $play->status);
			mylog('CASE initGame data loaded');
			if ($play && $play->status === 'finished') {
				$id = $data->gameId;
			}
		}
		while ($id === '') {
			dbg(" searching for new game id... ");
			$id = strtoupper(bin2hex(random_bytes(2)));
			// game exists and is younger than one day
			if (file_exists(Game::getGameFileName($id)) && 
					(time() - filemtime(Game::getGameFileName($id) < (60*60*24)))) {
				$id = ''; // try it again
			}
		}
		$play = new Game($id);
		$play->startNewGame();
		$play->save();
		mylog('CASE initGame data saved');
		//usleep(500000); // wait for 0.5s, could be removed
		echo json_encode( [ 'data' => $id, 'dbg' => $dbgBuffer ] );
		break;
	case "getGameCards":
		mylog('CASE getGameCards start');
		$play = Game::load($data->gameId);
		mylog('CASE getGameCards data loaded');
		if ($play && $play->status === 'inactive') {
			$givenCards = $play->getCardsInPlayersHands();
			echo json_encode( [ 'data' => array_merge($givenCards, $play->getDeck()->getCards()), 'dbg' => $dbgBuffer ] );
		} else {
			http_response_code(403);
			echo json_encode( [ 'message' => 'Too late, game has been already started or was not found', 'dbg' => $dbgBuffer ] ); 
		}
		break;
	case "registerPlayer":
		mylog('CASE registerPlayer start');
		$play = Game::load($data->gameId);
		mylog('CASE registerPlayer data loaded');
		if ($play && $play->status === 'inactive') {
			$pid = $play->assignPlayer($data->name);
			if ($pid) {
				$play->save();
				mylog('CASE registerPlayer data saved');
				echo json_encode( [ 'data' => [ 'playerId' => $pid , 'hand' => $play->getPlayerCopy($pid)->getHand()->getCardIds() ], 'dbg' => $dbgBuffer ] );
			} else {
				http_response_code(403);
				echo json_encode( [ 'message' => 'Player with similar name is already registered', 'dbg' => $dbgBuffer ] ); 
			}
		} else {
			http_response_code(403);
			echo json_encode( [ 'message' => 'Game has either started or finished already', 'dbg' => $dbgBuffer ] );
		}
		break;
	case "setActivePlayer": // and start game
		mylog('CASE setActivePlayer start');
		$play = Game::load($data->gameId);
		mylog('CASE setActivePlayer data loaded');
		if ($play && $play->getActivePlayerId() === '') {
			$play->setActivePlayer($data->playerId);
			$play->status = 'playing';
			$play->save();
			mylog('CASE setActivePlayer data saved');
			echo json_encode( [ 'data' => true, 'dbg' => $dbgBuffer ] );
		} else {
			http_response_code(403);
			echo json_encode( [ 'message' => 'Active player was already set.', 'dbg' => $dbgBuffer ] ); 		
		}
		break;
	case "getGameInfo":
		$changedAt = filemtime(Game::getGameFileName($data->gameId)); // unix timestamp
		if ($data->knownStateFrom < $changedAt) { // performance optimization for very often requests
			$play = Game::load($data->gameId);
			echo json_encode( [ 'data' => [ 'players' => $play->getPlayersInfo(), 'gameStatus' => $play->status , 
				'amIActivePlayer' => ($play->getActivePlayerId() && $play->getActivePlayerId() === $data->playerId),
				'lastModifiedAt' => $changedAt, 'turns' => $play->turns, 'allPlayersHands' => $play->getAllPlayersHands()], 'dbg' => $dbgBuffer ] );
		} else {
			http_response_code(304);
		}
		break;
	case "getHand":
		mylog('CASE getHand start');
		$play = Game::load($data->gameId);
		mylog('CASE getHand data loaded');
		$hand = $play->getPlayerCopy($data->playerId)->getHand()->getCardIds();
		echo json_encode( [ 'data' => $hand, 'dbg' => $dbgBuffer ] );
		break;
	case "getTable":
		mylog('CASE getTable start');
		$play = Game::load($data->gameId);
		mylog('CASE getTable data loaded');
		$table = $play->getTable()->getGroups();
		echo json_encode( [ 'data' => $table, 'dbg' => $dbgBuffer ] );
		break;
	case "getCard":
		mylog('CASE getCard start');
		$play = Game::load($data->gameId);
		mylog('CASE getCard data loaded');
		if ($play && $play->getActivePlayerId() === $data->playerId && $play->doTurnAsGetCard($data->playerId)) {
			mylog('CASE getCard data saved');
			$play->save();
			echo json_encode( [ 'data' => true, 'dbg' => $dbgBuffer ] );
		} else {
			http_response_code(403);
			// many different errors should happen !!
			echo json_encode( [ 'message' => 'Player is not allowed to get card.', 'dbg' => $dbgBuffer ] ); 
		}
		break;
	case "validateGroup":
		mylog('CASE validateGroup start');
		$newSet = new Cards();
		foreach ($data->cards->cards as $c) {
			$newSet->pushCard(new Card($c->value, $c->type, $c->id));
		}
		if (Group::validate($newSet)) {
			echo json_encode( [ 'data' => true, 'dbg' => $dbgBuffer ] );
		} else {
			http_response_code(403);
			echo json_encode( [ 'message' => 'Card set is invalid', 'dbg' => $dbgBuffer ] );
		}
		break;
	case "doTableChange":
		mylog('CASE doTableChange start');
		$hand = new Cards();
		foreach ($data->hand as $c) {
			$hand->pushCard(new Card($c->value, $c->type, $c->id));
		}
		$table = new Table();
		$problem = false;
		foreach ($data->table as $grp) {
			$newSet = new Cards();
			foreach ($grp->cards as $c) {
				$newSet->pushCard(new Card($c->value, $c->type, $c->id));
			}
			dbg('new set to validate', $newSet);
			$g = Group::createSet($newSet, $grp->id);
			if ($g) {
				$table[] = $g;
			} else {
				$problem = true;
			}
		}
		if ($problem) {
			http_response_code(403);
			echo json_encode( [ 'message' => 'Some table set is invalid', 'dbg' => $dbgBuffer ] );
			break;
		}
		mylog('CASE doTableChange validations done');
		$play = Game::load($data->gameId);
		mylog('CASE doTableChange data loaded');
		if ($play && $play->doTurnAsTableChange($data->playerId, $table, $hand)) {
			$play->save(); // won = player won the game
			mylog('CASE doTableChange data saved');
			echo json_encode( [ 'data' => ($play->status === 'finished') ? 'won' : 'done', 'dbg' => $dbgBuffer ] );
		} else {
			http_response_code(403);
			echo json_encode( [ 'message' => 'Table or player hand is invalid', 'dbg' => $dbgBuffer ] );
		}
		break;
}

flush();
mylog('API end');
