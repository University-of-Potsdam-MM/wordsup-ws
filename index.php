<?php

require 'Slim/Slim.php';
require "NotORM.php";

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();
$key = 'sharedsecret';



////////////////////////////////
////////  CONNECTION   /////////
////////////////////////////////



$host = "ittabu.no-ip.org";
$port = "3306";
$user = "Tester";
$pass = "test123";


// Connect to Database
function getConnection($dbname) {
	global $host, $port, $user, $pass;

	$dsn = "mysql:host=".$host.";port=".$port.";dbname=".$dbname;
	$pdo = new PDO($dsn, $user, $pass);
	$pdo->query('SET NAMES utf8');
	$db = new NotORM($pdo);
	
	return $db;
}



////////////////////////////////
//////////  GETS   /////////////
////////////////////////////////



// GET Topic
$app->get("/topic", function () use ($app) {
	$data = (object) $app->request()->post();
	$data = json_decode($data->data, true);
	
	if (array_key_exists("db", $data)) { $name = $data["db"]; };
	$db = getConnection("wup_".$name);
	
	if (array_key_exists("id", $data)) { 
		$id = $data["id"];
		$_topic = $db->topics()->where("id", $id);
	}
	else if (array_key_exists("topicname", $data)){
		$topicname = $data["topicname"];
		$_topic = $db->topics()->where("topicname", $topicname);
	}
	else
		$_topic = $db->topics()->where("id", 0);
	
	if ($topic = $_topic->fetch()) {
		$result[] = array(
			"id" => $topic["id"],
			"topicname" => $topic["topicname"]
		);
	}
	else {
		$result[] = array(
			"status" => false,
			"message" => "Topic with id $id does not exist"
		);
	}
	$app->response()->header("Content-Type", "application/json");
	echo json_encode($result);
});



// GET Subtopic
$app->get("/subtopic", function () use ($app) {
	$data = (object) $app->request()->post();
	$data = json_decode($data->data, true);
	
	if (array_key_exists("db", $data)) { $name = $data["db"]; };
	$db = getConnection("wup_".$name);
	
	if (array_key_exists("id", $data)) { 
		$id = $data["id"];
		$_subtopic = $db->subtopics()->where("id", $id);
	}
	else if (array_key_exists("subtopicname", $data)){
		$subtopicname = $data["subtopicname"];
		$_subtopic = $db->subtopics()->where("subtopicname", $subtopicname);
	}
	else
		$_subtopic = $db->subtopics()->where("id", 0);
		
	if ($subtopic = $_subtopic->fetch()) {
		$result[] = array(
			"id" => $subtopic["id"],
			"subtopicname" => $subtopic["subtopicname"],
			"reference1" => $subtopic["reference1"],
			"reference2" => $subtopic["reference2"],
			"reference3" => $subtopic["reference3"]
		);
	}
	else {
		$result[] = array(
			"status" => false,
			"message" => "Subtopic with id $id does not exist"
		);
	}
	$app->response()->header("Content-Type", "application/json");
	echo json_encode($result);
});



// GET Term
$app->get("/term", function () use ($app) {
	$data = (object) $app->request()->post();
	$data = json_decode($data->data, true);
	
	if (array_key_exists("db", $data)) { $name = $data["db"]; };
	$db = getConnection("wup_".$name);
	
	if (array_key_exists("id", $data)) { 
		$id = $data["id"];
		$_term = $db->terms()->where("id", $id);
	}
	else if (array_key_exists("term", $data)){
		$termname = $data["term"];
		$_term = $db->terms()->where("term", $termname);
	}
	else
		$_term = $db->terms()->where("id", 0);
		
	if ($term = $_term->fetch()) {
	
		$termid = $term["id"];
		$mapset = $db->topics()->join("subtopics", 
			"INNER JOIN subtopics ON subtopics.topicid = topics.id 
			 INNER JOIN topicmappers ON topicmappers.subtopicid = subtopics.id 
			 WHERE topicmappers.termid = $termid ORDER BY topicname ASC, subtopicname ASC")->select(
			"topics.topicname as tn, 
			 subtopics.subtopicname as stn");
		$mappings = array();
		if ($mapset->fetch()) {
			foreach ($mapset as $map) {
				$mappings[] = $map["tn"]. " > " .$map["stn"];
			}
		}
		
		$result[] = array(
			"id" => $term["id"],
			"term" => $term["term"],
			"badword1" => $term["badword1"],
			"badword2" => $term["badword2"],
			"badword3" => $term["badword3"],
			"badword4" => $term["badword4"],
			"badword5" => $term["badword5"],
			"level" => $term["level"],
			"lookuplink" => $term["lookuplink"],
			"mappings" => $mappings
		);
	}
	else {
		$result[] = array(
			"status" => false,
			"message" => "Term with id $id does not exist"
		);
	}
	$app->response()->header("Content-Type", "application/json");
	echo json_encode($result);
});



// GET Topics
$app->get("/topics", function () use ($app) {

	$data = (object) $app->request()->post();
	$data = json_decode($data->data, true);
	
	if (!array_key_exists("order", $data))
		$data["order"] = "topicname ASC";
	
	if (!array_key_exists("limit", $data)) { 
		$limit = 9999;
		$offset = 0;
	}
	else {
		$limval = explode(",", $data["limit"]);
		$offset = intval($limval[0]);
		$limit = intval($limval[1]);
	}
	
	if (array_key_exists("db", $data)) { $name = $data["db"]; };
	$db = getConnection("wup_".$name);
	
	$topics = $db->topics()->order($data["order"])->limit($limit, $offset);

	$result = array();
	if ($topics->fetch()) {
		foreach ($topics as $topic) {
			$subtopics = $db->subtopics()->where("topicid", $topic["id"])->order("subtopicname ASC")->select("subtopicname");
			$subnames = array();
			if ($subtopics->fetch()) {
				foreach ($subtopics as $subtopic)
					$subnames[] = $subtopic["subtopicname"];
			}
			$result[] = array(
				"id" => $topic["id"],
				"topicname" => $topic["topicname"],
				"subtopics" => $subnames
			);
		}
	}
    else {
		$result[] = array(
			"status" => false,
            "message" => "Topics with this conditions do not exist"
		);
    }
	$app->response()->header("Content-Type", "application/json");
	echo json_encode($result);
});



// GET Subtopics
$app->get("/subtopics", function () use ($app) {

	$data = (object) $app->request()->post();
	$data = json_decode($data->data, true);
	
	$cond = array();
	if (array_key_exists("id", $data))
		$cond["id"] = $data["id"];
	if (array_key_exists("topicid", $data))
		$cond["topicid"] = $data["topicid"];
		
	if (!array_key_exists("order", $data))
		$data["order"] = "subtopicname ASC";
	
	if (!array_key_exists("limit", $data)) { 
		$limit = 9999;
		$offset = 0;
	}
	else {
		$limval = explode(",", $data["limit"]);
		$offset = $limval[0];
		$limit = $limval[1];
	}
	
	if (array_key_exists("db", $data)) { $name = $data["db"]; };
	$db = getConnection("wup_".$name);
	
	if (empty($cond))
		$subtopics = $db->subtopics()->order($data["order"])->limit($limit, $offset);
	else
		$subtopics = $db->subtopics()->where($cond)->order($data["order"])->limit($limit, $offset);

	$result = array();
	if ($subtopics->fetch()) {
		foreach ($subtopics as $subtopic) {
			$topic = $db->topics()->where("id", $subtopic["topicid"])->select("topicname")->fetch();
			$subtopic["topic"] = $topic["topicname"];
			$result[] = array(
				"id" => $subtopic["id"],
				"subtopicname" => $subtopic["subtopicname"],
				"topicid" => $subtopic["topicid"],
				"topic" => $subtopic["topic"],
				"reference1" => $subtopic["reference1"],
				"reference2" => $subtopic["reference2"],
				"reference3" => $subtopic["reference3"]
			);
		}
	}
    else {
		$result[] = array(
			"status" => false,
            "message" => "Subtopics with this conditions do not exist"
		);
    }
	$app->response()->header("Content-Type", "application/json");
	echo json_encode($result);
});



// GET Subtopics (which are not used)
$app->get("/notusedsubtopics", function () use ($app) {

	$data = (object) $app->request()->post();
	$data = json_decode($data->data, true);
	
	$query = "LEFT JOIN subtopics ON topicmappers.subtopicid = subtopics.id 
			  LEFT JOIN topics ON subtopics.topicid = topics.id 
			  WHERE 1=1";
	
	if (array_key_exists("termid", $data)) { $x = $data["termid"]; $query .= " AND topicmappers.termid = $x"; }
	if (array_key_exists("topicid", $data)) { $tid = $data["topicid"]; }
	
	if (array_key_exists("db", $data)) { $name = $data["db"]; };
	$db = getConnection("wup_".$name);
	
	$maps = $db->topicmappers()->join("subtopics", $query)->select("subtopics.id AS sid");
	
	$not_ids = array();
	if ($maps->fetch()) {
		foreach ($maps as $m) {
			$not_ids[] = $m["sid"];
		}
	}
	$subtopics = $db->subtopics("NOT id", $not_ids)->where("topicid", $tid)->order("subtopicname ASC");
	
	$result = array();
	if ($subtopics->fetch()) {
		foreach ($subtopics as $subtopic) {
			$result[] = array(
				"id" => $subtopic["id"],
				"subtopicname" => $subtopic["subtopicname"]
			);
		}
	}
    else {
		$result[] = array(
			"status" => false,
            "message" => "Subtopics with this conditions do not exist"
		);
    }
	
	$app->response()->header("Content-Type", "application/json");
	echo json_encode($result);
});



// GET Terms
$app->get("/terms", function () use ($app) {

	$data = (object) $app->request()->post();
	$data = json_decode($data->data, true);
	
	$query = "LEFT JOIN topicmappers ON terms.id = topicmappers.termid
			  LEFT JOIN subtopics ON topicmappers.subtopicid = subtopics.id 
			  LEFT JOIN topics ON subtopics.topicid = topics.id 
			  WHERE 1=1";
	
	if (array_key_exists("term", $data)) { $x = $data["term"]; $query .= " AND terms.term = '$x'"; };
	if (array_key_exists("level", $data)) { $x = $data["level"]; $query .= " AND terms.level = $x"; };
	if (array_key_exists("topicid", $data)) { $x = $data["topicid"]; $query .= " AND topics.id = $x"; };
	if (array_key_exists("subtopicid", $data)) { $x = $data["subtopicid"]; $query .= " AND subtopics.id = $x"; };
	if (array_key_exists("order", $data)) { $x = $data["order"]; $query .= " ORDER BY $x"; };
	if (array_key_exists("limit", $data)) { $x = $data["limit"]; $query .= " LIMIT $x"; };
	
	if (array_key_exists("db", $data)) { $name = $data["db"]; };
	$db = getConnection("wup_".$name);

	$terms = $db->terms()->join("topicmappers", $query)->select("DISTINCT terms.*");

	$result = array();
	if ($terms->fetch()) {
		foreach ($terms as $term) {
			$termid = $term["id"];
			$mapset = $db->topics()->join("subtopics", 
			"INNER JOIN subtopics ON subtopics.topicid = topics.id 
			 INNER JOIN topicmappers ON topicmappers.subtopicid = subtopics.id 
			 WHERE topicmappers.termid = $termid ORDER BY topicname ASC, subtopicname ASC")->select(
			"topics.topicname as tn, 
			 subtopics.subtopicname as stn");
			$mappings = array();
			if ($mapset->fetch()) {
				foreach ($mapset as $map) {
					$mappings[] = $map["tn"]. " > " .$map["stn"];
				}
			}
			$result[] = array(
				"id" => $term["id"],
				"term" => $term["term"],
				"badword1" => $term["badword1"],
				"badword2" => $term["badword2"],
				"badword3" => $term["badword3"],
				"badword4" => $term["badword4"],
				"badword5" => $term["badword5"],
				"level" => $term["level"],
				"lookuplink" => $term["lookuplink"],
				"mappings" => $mappings
			);
		}
	}
    else {
		$result[] = array(
			"status" => false,
            "message" => "Terms newer as $time do not exist"
		);
    }
	$app->response()->header("Content-Type", "application/json");
	echo json_encode($result);
});



// GET Mappings
$app->get("/mappings", function () use ($app) {

	$data = (object) $app->request()->post();
	$data = json_decode($data->data, true);
	
	$query = "LEFT JOIN subtopics ON topicmappers.subtopicid = subtopics.id 
			  LEFT JOIN topics ON subtopics.topicid = topics.id 
			  WHERE 1=1";
	
	if (array_key_exists("termid", $data)) { $x = $data["termid"]; $query .= " AND topicmappers.termid = $x"; }
	
	if (array_key_exists("db", $data)) { $name = $data["db"]; };
	$db = getConnection("wup_".$name);
	
	$query .= " ORDER BY topics.topicname ASC, subtopics.subtopicname ASC";
	
	$maps = $db->topicmappers()->join("subtopics", $query)->select("topicmappers.id AS mapid, subtopics.subtopicname AS stn, subtopics.topicid AS tid");
	
	$result = array();
	if ($maps->fetch()) {
		foreach ($maps as $m) {
			$topic = $db->topics()->where("id", $m["tid"])->select("topicname")->fetch();
			$m["tn"] = $topic["topicname"];
			$result[] = array(
				"mapid" => $m["mapid"],
				"mapname" => $m["tn"]. " > " .$m["stn"]
			);
		}
	}
    else {
		$result[] = array(
			"status" => false,
            "message" => "Mappings do not exist"
		);
    }
	$app->response()->header("Content-Type", "application/json");
	echo json_encode($result);
});



// GET Results
$app->get("/results", function () use ($app) {

	$data = (object) $app->request()->post();
	$data = json_decode($data->data, true);
	
	$cond = array();
	if (array_key_exists("topicid", $data)) {
		$cond["topicid"] = $data["topicid"];
		$tid = $data["topicid"];
	}
	
	if (array_key_exists("db", $data)) { $name = $data["db"]; };
	$db = getConnection("wup_".$name);
	
	$subtopics = $db->subtopics()->where($cond);
	
	$result = array();
	if ($subtopics->fetch()) {
		foreach ($subtopics as $subtopic) {
			$sid = $subtopic["id"];
			$query = "LEFT JOIN topicmappers ON results.topicmapperid = topicmappers.id
					  LEFT JOIN subtopics ON topicmappers.subtopicid = subtopics.id 
					  LEFT JOIN topics ON subtopics.topicid = topics.id 
					  WHERE topics.id = $tid AND subtopics.id = $sid";
			$res = $db->results()->join("topicmappers", $query)->select("results.timesplayed AS tp, results.timessolved AS ts");
			
			$tp = 0;
			$ts = 0;
			if ($res->fetch()) {
				foreach ($res as $r) {
					$tp += $r["tp"];
					$ts += $r["ts"];
				}
				$procent = round($ts/$tp*100);
				$width = round($ts/$tp*400);
			}
			else {
				$procent = 0;
				$width = 0;
			}
			$result[] = array(
						"subtopicname" => $subtopic["subtopicname"],
						"tp" => $tp,
						"ts" => $ts,
						"procent" => $procent,
						"width" => $width
					);
		}
		
		// Comparefunction for usort, compares the procent values of the subtopics
		function cmp($a, $b) {
			return strcmp($b["procent"], $a["procent"]);
		}
	
		// If there are more than one subtopic, sort them with the cmp function
		if ($result && (count($result) > 1)) {
			usort($result, "cmp");
		}
	}		
    else {
		$result[] = array(
			"status" => false,
            "message" => "Results do not exist"
		);
    }
	$app->response()->header("Content-Type", "application/json");
	echo json_encode($result);
});



// GET Statistics
$app->get("/statistics", function () use ($app) {

	$data = (object) $app->request()->post();
	$data = json_decode($data->data, true);
	
	$cond = array();
	if (array_key_exists("topicid", $data)) {
		$cond["topicid"] = $data["topicid"];
		$tid = $data["topicid"];
	}
	
	if (array_key_exists("db", $data)) { $name = $data["db"]; };
	$db = getConnection("wup_".$name);
	
	$subtopics = $db->subtopics()->where($cond);
	
	$query = "LEFT JOIN subtopics ON topicmappers.subtopicid = subtopics.id 
			  LEFT JOIN topics ON subtopics.topicid = topics.id 
			  WHERE topics.id = $tid";
	
	$topiccount = $db->topicmappers()->join("subtopics", $query)->count("*");
	
	$result = array();
	if ($subtopics->fetch()) {
		foreach ($subtopics as $subtopic) {
			$sid = $subtopic["id"];
			$query = "LEFT JOIN subtopics ON topicmappers.subtopicid = subtopics.id 
					  LEFT JOIN topics ON subtopics.topicid = topics.id 
					  WHERE subtopics.id = $sid AND topics.id = $tid";
			
			$subtopiccount = $db->topicmappers()->join("subtopics", $query)->count("*");
		
			$procent = round($subtopiccount/$topiccount*100);
			$width = round($subtopiccount/$topiccount*400);
		
			
			$result[] = array(
						"subtopicname" => $subtopic["subtopicname"],
						"count_from" => $subtopiccount,
						"count_to" => $topiccount,
						"procent" => $procent,
						"width" => $width
					);
		}
		
		// Comparefunction for usort, compares the procent values of the subtopics
		function cmp($a, $b) {
			return strcmp($b["procent"], $a["procent"]);
		}
	
		// If there are more than one subtopic, sort them with the cmp function
		if ($result && (count($result) > 1)) {
			usort($result, "cmp");
		}
	}		
    else {
		$result[] = array(
			"status" => false,
            "message" => "Results do not exist"
		);
    }
	$app->response()->header("Content-Type", "application/json");
	echo json_encode($result);
});



// GET all newer as a timestamp
$app->get("/all/:name/newer/:time", function ($name, $time) use ($app) {
    
	$db = getConnection("wup_".$name);
	
	$_terms = $db->terms()->where("lastedit > ?", $time);
	$terms = array();
	if ($_terms->fetch() && is_numeric($time)) {
		foreach ($_terms as $term) {
			$terms[] = array(
				"id" => $term["id"],
				"term" => $term["term"],
				"badword1" => $term["badword1"],
				"badword2" => $term["badword2"],
				"badword3" => $term["badword3"],
				"badword4" => $term["badword4"],
				"badword5" => $term["badword5"],
				"level" => $term["level"],
				"lookuplink" => $term["lookuplink"]
			);
		}
	}
    
	$_topics = $db->topics()->where("lastedit > ?", $time);
	$topics = array();
	if ($_topics->fetch() && is_numeric($time)) {
		foreach ($_topics as $topic) {
			$topics[] = array(
				"id" => $topic["id"],
				"topicname" => $topic["topicname"]
			);
		}
	}
	
	$_subtopics = $db->subtopics()->where("lastedit > ?", $time);
	$subtopics = array();
	if ($_subtopics->fetch() && is_numeric($time)) {
		foreach ($_subtopics as $subtopic) {
			$subtopics[] = array(
				"id" => $subtopic["id"],
				"subtopicname" => $subtopic["subtopicname"],
				"topicid" => $subtopic["topicid"],
				"reference1" => $subtopic["reference1"],
				"reference2" => $subtopic["reference2"],
				"reference3" => $subtopic["reference3"]
			);
		}
	}
	
	$_mappings = $db->topicmappers()->where("lastedit > ?", $time);
	$mappings = array();
	if ($_mappings->fetch() && is_numeric($time)) {
		foreach ($_mappings as $mapping) {
			$mappings[] = array(
				"id" => $mapping["id"],
				"termid" => $mapping["termid"],
				"subtopicid" => $mapping["subtopicid"]
			);
		}
	}
	
	$results = array();
	$results['terms'] = $terms;
	$results['topics'] = $topics;
	$results['subtopics'] = $subtopics;
	$results['topicmappers'] = $mappings;
	
	
	
	
	
	$terms = $db->terms()->select("terms.id");
	$termids = array();
	if ($terms->fetch()) {
		foreach ($terms as $term) {
			$termids[] = $term["id"];
		}
		$alltermids = range(1, max($termids));                                                    
		$missingtermids = array_diff($alltermids, $termids);
		$termids = array();
		foreach ($missingtermids as $mid) {
			$termids[] = array("id"=>$mid);
		}
	}
	
	$topics = $db->topics()->select("topics.id");
	$topicids = array();
	if ($topics->fetch()) {
		foreach ($topics as $topic) {
			$topicids[] = $topic["id"];
		}
		$alltopicids = range(1, max($topicids));                                                    
		$missingtopicids = array_diff($alltopicids, $topicids);
		$topicids = array();
		foreach ($missingtopicids as $mtid) {
			$topicids[] = array("id"=>$mtid);
		}
	}
	
	$subtopics = $db->subtopics()->select("subtopics.id");
	$subtopicids = array();
	if ($subtopics->fetch()) {
		foreach ($subtopics as $subtopic) {
			$subtopicids[] = $subtopic["id"];
		}
		$allsubtopicids = range(1, max($subtopicids));                                                    
		$missingsubtopicids = array_diff($allsubtopicids, $subtopicids);
		$subtopicids = array();
		foreach ($missingsubtopicids as $mstid) {
			$subtopicids[] = array("id"=>$mstid);
		}
	}
	
	$mappings = $db->topicmappers()->select("topicmappers.id");
	$mappingids = array();
	if ($mappings->fetch()) {
		foreach ($mappings as $mapping) {
			$mappingids[] = $mapping["id"];
		}
		$allmappingids = range(1, max($mappingids));                                                    
		$missingmappingids = array_diff($allmappingids, $mappingids);
		$mappingids = array();
		foreach ($missingmappingids as $mmid) {
			$mappingids[] = array("id"=>$mmid);
		}
	}
	
	$results['delterms'] = $termids;
	$results['deltopics'] = $topicids;
	$results['delsubtopics'] = $subtopicids;
	$results['deltopicmappers'] = $mappingids;
	
	$app->response()->header("Content-Type", "application/json");
	echo json_encode($results);
});



////////////////////////////////
//////////  DELETES   //////////
////////////////////////////////



// DELETE Topic
$app->delete("/topic/:name/:id/:sig", function ($name, $id, $sig) use($app, $key) {
	$checksum = hash_hmac('sha256', $id, $key);
	$app->response()->header("Content-Type", "application/json");
	if ($sig == $checksum) {
		$db = getConnection("wup_".$name);
		$i = $db->topics("id", $id)->delete();
		$subtopics = $db->subtopics()->where("topicid", $id);
		$j = 0;
		$k = 0;
		foreach ($subtopics as $subtopic) {
			$k += $db->topicmappers("subtopicid", $subtopic["id"])->delete();
			$j += $subtopic->delete();
		}
		if ($i>0) {
			echo json_encode(array(
				"status" => true,
				"message" => "Topic with id $id and its $j subtopics and $k mappings deleted"
				));
		}
		else{
			echo json_encode(array(
				"status" => false,
				"message" => "Topic with id $id does not exist"
			));
		}
	}
	else 
		echo json_encode(array(
				"status" => false,
				"message" => "Message not trusted"
			));
});



// DELETE Subtopic
$app->delete("/subtopic/:name/:id/:sig", function ($name, $id, $sig) use($app, $key) {
	$checksum = hash_hmac('sha256', $id, $key);
	$app->response()->header("Content-Type", "application/json");
	if ($sig == $checksum) {
		$db = getConnection("wup_".$name);
		$i = $db->subtopics("id", $id)->delete();
		$j = $db->topicmappers("subtopicid", $id)->delete();
		if ($i>0) {
			echo json_encode(array(
				"status" => true,
				"message" => "Subtopic with id $id and its $j mappings deleted"
				));
		}
		else{
			echo json_encode(array(
				"status" => false,
				"message" => "Subtopic with id $id does not exist"
			));
		}
	}
	else 
		echo json_encode(array(
				"status" => false,
				"message" => "Message not trusted"
			));
});



// DELETE Term
$app->delete("/term/:name/:id/:sig", function ($name, $id, $sig) use($app, $key) {
	$checksum = hash_hmac('sha256', $id, $key);
	$app->response()->header("Content-Type", "application/json");
	if ($sig == $checksum) {
		$db = getConnection("wup_".$name);
		$i = $db->terms("id", $id)->delete();
		$j = $db->topicmappers("termid", $id)->delete();
		if ($i>0) {
			echo json_encode(array(
				"status" => true,
				"message" => "Term with id $id and its $j mappings deleted"
				));
		}
		else{
			echo json_encode(array(
				"status" => false,
				"message" => "Term with id $id does not exist"
			));
		}
	}
	else 
		echo json_encode(array(
				"status" => false,
				"message" => "Message not trusted"
			));
});



// DELETE Mapping
$app->delete("/mapping/:name/:id/:sig", function ($name, $id, $sig) use($app, $key) {
	$checksum = hash_hmac('sha256', $id, $key);
	$app->response()->header("Content-Type", "application/json");
	if ($sig == $checksum) {
		$db = getConnection("wup_".$name);
		$i = $db->topicmappers("id", $id)->delete();
		if ($i>0) {
			echo json_encode(array(
				"status" => true,
				"message" => "Mapping with id $id deleted"
				));
		}
		else{
			echo json_encode(array(
				"status" => false,
				"message" => "Mapping with id $id does not exist"
			));
		}
	}
	else 
		echo json_encode(array(
				"status" => false,
				"message" => "Message not trusted"
			));
});



////////////////////////////////
//////////  PUTS   /////////////
////////////////////////////////



// PUT Topic
$app->put("/topic/:name/:id", function ($name, $id) use($app, $key) {
	$data = (object) $app->request()->put();
	$checksum = hash_hmac('sha256', $data->data, $key);
	$app->response()->header("Content-Type", "application/json");
	if ($data->sig == $checksum) {
		$db = getConnection("wup_".$name);
		$topic = $db->topics()->where("id", $id);
		if ($topic->fetch()) {
			$new_topic = json_decode($data->data, true);
			$result = $topic->update($new_topic);
			echo json_encode(array(
				"status" => (bool)$result,
				"message" => "Topic updated successfully"
				));
		}
		else{
			echo json_encode(array(
				"status" => false,
				"message" => "Topic id $id does not exist"
			));
		}
	}
	else 
		echo json_encode(array(
				"status" => false,
				"message" => "Message not trusted"
		));
});



// PUT Subtopic
$app->put("/subtopic/:name/:id", function ($name, $id) use($app, $key) {
	$data = (object) $app->request()->put();
	$checksum = hash_hmac('sha256', $data->data, $key);
	$app->response()->header("Content-Type", "application/json");
	if ($data->sig == $checksum) {
		$db = getConnection("wup_".$name);
		$subtopic = $db->subtopics()->where("id", $id);
		if ($subtopic->fetch()) {
			$new_subtopic = json_decode($data->data, true);
			$result = $subtopic->update($new_subtopic);
			echo json_encode(array(
				"status" => (bool)$result,
				"message" => "Subtopic updated successfully"
				));
		}
		else{
			echo json_encode(array(
				"status" => false,
				"message" => "Subtopic id $id does not exist"
			));
		}
	}
	else 
		echo json_encode(array(
				"status" => false,
				"message" => "Message not trusted"
		));
});



// PUT Term
$app->put("/term/:name/:id", function ($name, $id) use($app, $key) {
	$data = (object) $app->request()->put();
	$checksum = hash_hmac('sha256', $data->data, $key);
	$app->response()->header("Content-Type", "application/json");
	if ($data->sig == $checksum) {
		$db = getConnection("wup_".$name);
		$term = $db->terms()->where("id", $id);
		if ($term->fetch()) {
			$new_term = json_decode($data->data, true);
			$result = $term->update($new_term);
			echo json_encode(array(
				"status" => (bool)$result,
				"message" => "Term updated successfully"
				));
		}
		else{
			echo json_encode(array(
				"status" => false,
				"message" => "Term id $id does not exist"
			));
		}
	}
	else 
		echo json_encode(array(
				"status" => false,
				"message" => "Message not trusted"
		));
});



// PUT Mapping
$app->put("/mapping/:name/:id", function ($name, $id) use($app, $key) {
	$data = (object) $app->request()->put();
	$checksum = hash_hmac('sha256', $data->data, $key);
	$app->response()->header("Content-Type", "application/json");
	if ($data->sig == $checksum) {
		$db = getConnection("wup_".$name);
		$mapping = $db->topicmappers()->where("id", $id);
		if ($mapping->fetch()) {
			$new_mapping = json_decode($data->data, true);
			$result = $mapping->update($new_mapping);
			echo json_encode(array(
				"status" => (bool)$result,
				"message" => "Mapping updated successfully"
				));
		}
		else{
			echo json_encode(array(
				"status" => false,
				"message" => "Mapping id $id does not exist"
			));
		}
	}
	else 
		echo json_encode(array(
				"status" => false,
				"message" => "Message not trusted"
		));
});



// PUT Result
$app->put("/result/:name/:id", function ($name, $id) use($app, $key) {
	$data = (object) $app->request()->put();
	$checksum = hash_hmac('sha256', $data->data, $key);
	$app->response()->header("Content-Type", "application/json");
	if ($data->sig == $checksum) {
		$db = getConnection("wup_".$name);
		$res = $db->results()->where("id", $id);
		if ($res->fetch()) {
			$new_res = json_decode($data->data, true);
			$result = $res->update($new_res);
			echo json_encode(array(
				"status" => (bool)$result,
				"message" => "Result updated successfully"
				));
		}
		else{
			echo json_encode(array(
				"status" => false,
				"message" => "Result id $id does not exist"
			));
		}
	}
	else 
		echo json_encode(array(
				"status" => false,
				"message" => "Message not trusted"
		));
});



////////////////////////////////
//////////  POSTS   ////////////
////////////////////////////////



// POST Topic
$app->post("/topic/:name", function ($name) use($app, $key) {
	$data = (object) $app->request()->post();
	$checksum = hash_hmac('sha256', $data->data, $key);
	$app->response()->header("Content-Type", "application/json");
	if ($data->sig == $checksum) {
		$topic = json_decode($data->data, true);
		$db = getConnection("wup_".$name);
		$result = $db->topics->insert($topic);
		echo json_encode(array("id" => $result["id"]));
	}
	else 
		echo json_encode(array(
				"status" => false,
				"message" => "Message not trusted"
		));
});



// POST Subtopic
$app->post("/subtopic/:name", function ($name) use($app, $key) {
	$data = (object) $app->request()->post();
	$checksum = hash_hmac('sha256', $data->data, $key);
	$app->response()->header("Content-Type", "application/json");
	if ($data->sig == $checksum) {
		$subtopic = json_decode($data->data, true);
		$db = getConnection("wup_".$name);
		$result = $db->subtopics->insert($subtopic);
		echo json_encode(array("id" => $result["id"]));
	}
	else 
		echo json_encode(array(
				"status" => false,
				"message" => "Message not trusted"
		));
});



// POST Term
$app->post("/term/:name", function ($name) use($app, $key) {
	$data = (object) $app->request()->post();
	$checksum = hash_hmac('sha256', $data->data, $key);
	$app->response()->header("Content-Type", "application/json");
	if ($data->sig == $checksum) {
		$term = json_decode($data->data, true);
		$db = getConnection("wup_".$name);
		$result = $db->terms->insert($term);
		echo json_encode(array("id" => $result["id"]));
	}
	else 
		echo json_encode(array(
				"status" => false,
				"message" => "Message not trusted"
		));
});



// POST Mapping
$app->post("/mapping/:name", function ($name) use($app, $key) {
	$data = (object) $app->request()->post();
	$checksum = hash_hmac('sha256', $data->data, $key);
	$app->response()->header("Content-Type", "application/json");
	if ($data->sig == $checksum) {
		$mapping = json_decode($data->data, true);
		$db = getConnection("wup_".$name);
		$response = $db->subtopics()->where("subtopicname", $mapping["subtopic"])->select("id")->fetch();
		$m["subtopicid"] = $response["id"];
		$m["termid"] = $mapping["termid"];
		$m["lastedit"] = $mapping["lastedit"];
		$m["author"] = $mapping["author"];
		$result = $db->topicmappers->insert($m);
		echo json_encode(array("id" => $result["id"]));
	}
	else 
		echo json_encode(array(
				"status" => false,
				"message" => "Message not trusted"
		));
});



// POST Results
$app->post("/results/:name", function ($name) use ($app, $key) {
	$data = (object) $app->request()->post();
	$checksum = hash_hmac('sha256', $data->data, $key);
	$app->response()->header("Content-Type", "application/json");
	if ($data->sig == $checksum) {
		$res = json_decode($data->data, true);
		$db = getConnection("wup_".$name);
		
		if (array_key_exists("topicmapperid", $res))
			$res = array($res);
		
		foreach ($res as $r) {
			$count = $db->results()->where("topicmapperid", $r["topicmapperid"])->count("*");
			if ($count > 0) {
				$update_res = $db->results()->where("topicmapperid", $r["topicmapperid"])->fetch();
				$update_res["timesplayed"] += $r["timesplayed"];
				$update_res["timessolved"] += $r["timessolved"];
				$tp = $update_res["timesplayed"];
				$ts = $update_res["timessolved"];
				$update_res->update();
			}
			else {
				$db->results->insert($r);
				$tp = $r["timesplayed"];
				$ts = $r["timessolved"];
			}
			if ($tp > 1000) {
				$procent = round($ts/$tp*100);
				if ($procent > 90) {
					$map = $db->topicmappers()->where("id", $r["topicmapperid"])->fetch();
					$term = $db->terms()->where("id", $map["termid"])->fetch();
					if ($term["level"] > 1) {
						$term["level"] += -1;
						$term->update();
					}
				}
				if ($procent < 30) {
					$map = $db->topicmappers()->where("id", $r["topicmapperid"])->fetch();
					$term = $db->terms()->where("id", $map["termid"])->fetch();
					if ($term["level"] < 3) {
						$term["level"] += 1;
						$term->update();
					}
				}
			}
		}
		$result = array(
			"status" => true,
			"message" => "Results updated successfully"
		);
		echo json_encode($result);
	}
	else 
		echo json_encode(array(
				"status" => false,
				"message" => "Message not trusted"
		));
});



//////////////////////////////////
//////////  DATABASES   //////////
//////////////////////////////////



// GET Databases
$app->get("/databases", function () use ($app, $host, $port, $user, $pass) {
	$dsna = "mysql:host=".$host.";port=".$port.";dbname=INFORMATION_SCHEMA";
	$pdoa = new PDO($dsna, $user, $pass);
	$dba = new NotORM($pdoa);
	
    $dblist = $dba->schemata()->where("SCHEMA_NAME LIKE ?", "wup_%");
    $schemas = array();
    foreach ($dblist as $schema) {
		$schema_value = explode("_", $schema["SCHEMA_NAME"]);
        $schemas[] = array("name" => $schema_value[1]);
    }
	
    $app->response()->header("Content-Type", "application/json");
    echo json_encode(array("database"=>$schemas));
});



// POST Database
$app->post("/database/:name/:sig", function ($name, $sig) use($app, $key, $host, $port, $user, $pass) {
    $checksum = hash_hmac('sha256', $name, $key);
	$app->response()->header("Content-Type", "application/json");
	if ($sig == $checksum) {
		$name = "wup_".$name;
		
		$dsna = "mysql:host=".$host.";port=".$port.";dbname=INFORMATION_SCHEMA";
		$pdoa = new PDO($dsna, $user, $pass);
		$dba = new NotORM($pdoa);
		
		$dbexists = $dba->schemata()->where("SCHEMA_NAME", $name)->count("*");
		if ($dbexists > 0) {
			echo json_encode(array(
				"status" => false,
				"message" => "Database already exists"
			));
		}
		else {
			try {
				$dsn_new = "mysql:host=".$host.";port=".$port;
				$dbh = new PDO($dsn_new, $user, $pass);
				
				$dbh->exec("
				CREATE DATABASE `$name`;
				GRANT ALL ON `$name`.* TO '$user'@'$host';
				FLUSH PRIVILEGES;
				USE `$name;");
				
				$dbh->exec("
				CREATE  TABLE IF NOT EXISTS `$name`.`terms` (
					`id` INT NOT NULL AUTO_INCREMENT ,
					`term` VARCHAR(45) NOT NULL ,
					`badword1` VARCHAR(45) NOT NULL ,
					`badword2` VARCHAR(45) NOT NULL ,
					`badword3` VARCHAR(45) NOT NULL ,
					`badword4` VARCHAR(45) NOT NULL ,
					`badword5` VARCHAR(45) NOT NULL ,
					`level` INT NOT NULL ,
					`lookuplink` VARCHAR(255) NOT NULL ,
					`lastedit` BIGINT NOT NULL ,
					`author` VARCHAR(45) NOT NULL ,
					PRIMARY KEY (`id`) )
					ENGINE = InnoDB;

				CREATE  TABLE IF NOT EXISTS `$name`.`topics` (
					`id` INT NOT NULL AUTO_INCREMENT ,
					`topicname` VARCHAR(45) NOT NULL ,
					`lastedit` BIGINT NOT NULL ,
					`author` VARCHAR(45) NOT NULL ,
					PRIMARY KEY (`id`) )
					ENGINE = InnoDB;

				CREATE  TABLE IF NOT EXISTS `$name`.`subtopics` (
					`id` INT NOT NULL AUTO_INCREMENT ,
					`subtopicname` VARCHAR(45) NOT NULL ,
					`topicid` INT NOT NULL ,
					`reference1` VARCHAR(255) NULL ,
					`reference2` VARCHAR(255) NULL ,
					`reference3` VARCHAR(255) NULL ,
					`lastedit` BIGINT NOT NULL ,
					`author` VARCHAR(45) NOT NULL ,
					PRIMARY KEY (`id`) )
					ENGINE = InnoDB;

				CREATE  TABLE IF NOT EXISTS `$name`.`topicmappers` (
					`id` INT NOT NULL AUTO_INCREMENT ,
					`termid` INT NOT NULL ,
					`subtopicid` INT NOT NULL ,
					`lastedit` BIGINT NOT NULL ,
					`author` VARCHAR(45) NOT NULL ,
					PRIMARY KEY (`id`) )
					ENGINE = InnoDB;

				CREATE  TABLE IF NOT EXISTS `$name`.`results` (
					`id` INT NOT NULL AUTO_INCREMENT ,
					`topicmapperid` INT NOT NULL ,
					`timesplayed` INT NOT NULL ,
					`timessolved` INT NOT NULL ,
					PRIMARY KEY (`id`) )
					ENGINE = InnoDB;
				");

				echo json_encode(array(
					"status" => true,
					"message" => "Database and Tables created"
				));

			} catch (PDOException $e) {
				echo json_encode(array(
					"status" => false,
					"message" => "Database and Tables creation failed"
				));
			}
		}
	}
	else 
		echo json_encode(array(
				"status" => false,
				"message" => "Message not trusted"
		));
});



$app->run();

?>