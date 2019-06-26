<?php
require_once dirname(__DIR__, 2) . "/php/lib/uuid.php";
require_once dirname(__DIR__, 2) . "/php/lib/xsrf.php";
require_once(dirname(__DIR__, 2) . "/vendor/autoload.php");
//prepare an empty reply
$reply = new stdClass();
$reply->status = 200;
$reply->data = null;

try {

	$userJson = @file_get_contents("users.json");

	if($userJson === false) {
		throw(new RuntimeException("Unable to read diceware data", 500));
	}
	$users = json_decode($userJson);

	$method = $_SERVER["HTTP_X_HTTP_METHOD"] ?? $_SERVER["REQUEST_METHOD"];

	$id = filter_input(INPUT_GET, "id", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
	$postUserId = filter_input(INPUT_GET, "postUserId", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

	// verify the session, start if not active
	if(session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}
	if($method === "GET") {
		//set XSRF cookie
		setXsrfCookie();


		if(empty($id) === false) {
			foreach($users as $user) {
				if($user->userId === $id) {
					$reply->data = $user;
					break;
				}
			}
		}
		if(empty($postUserId) === false) {


			$postJson = @file_get_contents("posts.json");

			if($postJson === false) {
				throw(new RuntimeException("Unable to read diceware data", 500));
			}

			$posts = json_decode($postJson);
			$postArray = [];

			foreach($posts as $post) {
				if($post->postUserId === $postUserId) {
					$postArray[] = $post;
				}
			}

			$reply->data = $postArray;
		} else {
			$reply->data = $users;
		}
	} elseif($method = "POST") {

		$requestContent = file_get_contents("php://input");
		$requestObject = json_decode($requestContent);

		if(empty($requestObject->name) === true) {
			throw(new \InvalidArgumentException ("name was not provided ", 405));
		}

		if(empty($requestObject->username) === true) {
			throw(new \InvalidArgumentException ("name was not provided ", 405));
		}

		if(empty($requestObject->email) === true) {
			throw(new \InvalidArgumentException ("email was not provided ", 405));
		}

		if(empty($requestObject->phone) === true) {
			throw(new \InvalidArgumentException ("phone was not provided ", 405));
		}

		if(empty($requestObject->website) === true) {
			throw(new \InvalidArgumentException ("website was not provided ", 405));
		}
		//name username email phone, website

		$requestObject->userId = generateUuidV4();

		$users[] = $requestObject;

		file_put_contents("users.json", json_encode($users));
		$reply->message = "User created successfully";

	} else {
		throw (new InvalidArgumentException("Invalid HTTP method request", 418));
	}
} catch
(\Exception | \TypeError $exception) {
	$reply->status = $exception->getCode();
	$reply->message = $exception->getMessage();
}
// encode and return reply to front end caller
header("Content-type: application/json");
header("Access-Control-Allow-Origin: *");
echo json_encode($reply);